<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryLookupService;
use App\Services\Billing\InvoiceService;
use App\Services\FinanceService;
use App\Services\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppBotMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;

    private const SESSION_TTL = 30; // minutos

    /** Minutos até apagar a troca de mensagens (tempo para o cliente ler). */
    public const PURGE_DELAY_MINUTES = 5;

    /** Máximo de beneficiários listados para o cliente escolher. */
    private const MAX_CHOICES = 5;

    /** Menus padrão (o super admin pode editar o texto em /admin/bot). */
    public const MENU_NGO      = "1️⃣ Resumo financeiro do mês\n2️⃣ Minhas tarefas\n3️⃣ Registrar atendimento\n4️⃣ Consultar beneficiário\n5️⃣ Ajuda\n6️⃣ Atendimentos recentes\n7️⃣ Registrar evolução";
    public const MENU_MANAGER  = "1️⃣ Resumo financeiro do mês\n2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Lançar despesa\n5️⃣ Ajuda";
    public const MENU_EMPLOYEE = "2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Solicitar despesa (vai para aprovação)\n5️⃣ Ajuda";

    /**
     * Aviso curto anexado a mensagens terminais. Reforca ao cliente que o
     * canal e seguro/privado do Vivensi.
     */
    private const SECURITY_TAG = "🔒 _Canal seguro e privado do Vivensi. As mensagens desta conversa são apagadas em cerca de 5 minutos._";

    public function __construct(
        protected User   $user,
        protected string $waId,
        protected string $text,
        protected array  $inboundKey = []
    ) {}

    public function handle(): void
    {
        $instanceName = SystemSetting::getValue('bot_instance_name', '');
        if (!$instanceName) {
            Log::error('ProcessWhatsAppBotMessage: bot_instance_name nao configurado');
            return;
        }

        $context = new \stdClass();
        $context->evolution_instance_name  = $instanceName;
        $context->evolution_instance_token = null;
        $evo   = new EvolutionApiService($context);
        $phone = preg_replace('/\D/', '', explode('@', $this->waId)[0]);
        $text  = trim($this->text);
        $lower = mb_strtolower($text);

        $sessionKey = "bot_session_{$this->user->id}";
        $session    = Cache::get($sessionKey, ['state' => 'idle']);

        try {
            $response = $this->processCommand($lower, $text, $session, $sessionKey);
        } catch (\Throwable $e) {
            Log::error('ProcessWhatsAppBotMessage Error', [
                'user_id' => $this->user->id,
                'error'   => $e->getMessage(),
            ]);
            $response = "⚠️ Erro ao processar seu comando. Digite *menu* para recomeçar.";
        }

        $sendResult = null;
        if ($response) {
            $sendResult = $evo->sendMessage($phone, $response);
        }

        // Privacidade: apaga a mensagem inbound (o que o cliente digitou) e a
        // resposta outbound (que pode revelar dados consultados) do lado da
        // Evolution + WhatsApp do cliente — com atraso, para o cliente ler a
        // resposta antes (apagar na hora virava "Mensagem apagada" no celular).
        $keys = array_values(array_filter(
            [$this->inboundKey, $this->extractOutboundKey($sendResult)],
            fn ($k) => !empty($k['id']) && !empty($k['remoteJid'])
        ));
        if ($keys) {
            PurgeBotMessages::dispatch($instanceName, $keys)
                ->delay(now()->addMinutes(self::PURGE_DELAY_MINUTES));
        }
    }

    /**
     * A Evolution v2 devolve a key da mensagem enviada em formatos variaveis.
     * Aceita as estruturas conhecidas ({key: {...}} ou raiz).
     */
    private function extractOutboundKey(?array $sendResult): array
    {
        if (!is_array($sendResult)) return [];

        $key = $sendResult['key']
            ?? $sendResult['data']['key']
            ?? $sendResult['message']['key']
            ?? [];

        return [
            'id'          => $key['id'] ?? null,
            'remoteJid'   => $key['remoteJid'] ?? null,
            'fromMe'      => (bool) ($key['fromMe'] ?? true),
            'participant' => $key['participant'] ?? null,
        ];
    }

    // ─── Command Router ───────────────────────────────────────────────────────

    private function processCommand(string $lower, string $raw, array $session, string $sessionKey): string
    {
        // Reset / menu triggers
        if (in_array($lower, ['menu', 'inicio', 'início', 'voltar', 'oi', 'olá', 'ola', 'hi', 'hello', '0'])) {
            Cache::forget($sessionKey);
            return $this->welcomeMessage();
        }

        if (in_array($lower, ['cancelar', 'cancel', 'sair', 'encerrar', 'fim', 'terminar'])) {
            Cache::forget($sessionKey);
            return "✅ Sessão encerrada.\n\n" . self::SECURITY_TAG
                 . "\n\n_Digite *menu* quando precisar voltar._";
        }

        // Mesma regra do painel: conta bloqueada (fatura atrasada), suspensa,
        // sem contrato ou com teste vencido não usa o bot.
        if ($blocked = $this->accountBlockedMessage()) {
            Cache::forget($sessionKey);
            return $blocked;
        }

        if (in_array($lower, ['5', 'ajuda', 'help', '?'])) {
            return SystemSetting::getValue('bot_msg_help',
                "• *menu* — Menu principal\n• *cancelar* — Cancelar operação\n• DESP: 150,00 | Descrição\n• RECV: 500,00 | Descrição\n• BENEF: Nome ou CPF"
            );
        }

        // Multi-step session flow
        if ($session['state'] !== 'idle') {
            return $this->handleSessionFlow($lower, $raw, $session, $sessionKey);
        }

        $role = $this->user->role;

        // Numeric menu commands
        if ($lower === '1') {
            return $this->canSeeFinance() ? $this->cmdSaldo() : $this->noFinanceAccess();
        }
        if ($lower === '2') return $this->cmdTarefas();

        if ($lower === '3') {
            if (in_array($role, ['ngo', 'super_admin'])) {
                Cache::put($sessionKey, ['state' => 'atend_nome'], now()->addMinutes(self::SESSION_TTL));
                return "📋 *Registrar Atendimento*\n\nDigite o *nome completo* do beneficiário:";
            }
            if ($role === 'common' && $this->canSeeFinance()) {
                Cache::put($sessionKey, ['state' => 'recv_valor'], now()->addMinutes(self::SESSION_TTL));
                return "💰 *Lançar Receita*\n\nDigite o *valor* (ex: 500,00):";
            }
            // manager / employee
            return $this->startConcluirTarefa($sessionKey);
        }

        if ($lower === '4') {
            if (in_array($role, ['ngo', 'super_admin'])) {
                Cache::put($sessionKey, ['state' => 'benef_busca'], now()->addMinutes(self::SESSION_TTL));
                return "🔍 *Consultar Beneficiário*\n\nDigite o nome ou CPF:";
            }
            Cache::put($sessionKey, ['state' => 'desp_valor'], now()->addMinutes(self::SESSION_TTL));
            return $this->expenseNeedsApproval()
                ? "💸 *Solicitar Despesa* (vai para aprovação)\n\nDigite o *valor* (ex: 150,00):"
                : "💸 *Lançar Despesa*\n\nDigite o *valor* (ex: 150,00):";
        }

        if ($lower === '6') {
            if (in_array($role, ['ngo', 'super_admin'])) {
                return $this->cmdAtendimentos();
            }
        }

        if ($lower === '7') {
            if (in_array($role, ['ngo', 'super_admin'])) {
                Cache::put($sessionKey, ['state' => 'evol_nome'], now()->addMinutes(self::SESSION_TTL));
                return "📝 *Registrar Evolução*\n\nDigite o *nome* do beneficiário:";
            }
        }

        // Structured prefix commands — power-user mode, sem confirmação
        if (str_starts_with(strtoupper($raw), 'DESP:'))  return $this->cmdDespesa($raw);
        if (str_starts_with(strtoupper($raw), 'RECV:'))  return $this->canSeeFinance() ? $this->cmdReceita($raw) : $this->noFinanceAccess();
        if (str_starts_with(strtoupper($raw), 'ATEND:')) return $this->cmdAtendimento($raw, $sessionKey);
        if (str_starts_with(strtoupper($raw), 'BENEF:')) return $this->cmdBeneficiario(trim(substr($raw, 6)));
        if (str_starts_with(strtoupper($raw), 'HIST:'))  return $this->cmdHistoricoAtendimentos(trim(substr($raw, 5)), $sessionKey);
        if (str_starts_with(strtoupper($raw), 'EVOL:'))  return $this->cmdEvolucaoRapida($raw, $sessionKey);

        return "❓ Não entendi o comando. Digite *menu* para ver as opções disponíveis.";
    }

    // ─── Multi-step Flows ─────────────────────────────────────────────────────

    private function handleSessionFlow(string $lower, string $raw, array $session, string $sessionKey): string
    {
        $state = $session['state'];
        $ttl   = now()->addMinutes(self::SESSION_TTL);

        // ── DESPESA ──────────────────────────────────────────────────────────

        if ($state === 'desp_valor') {
            $val = $this->parseCurrency($raw);
            if (!$val) return "❌ Valor inválido. Ex: *150,00*\nOu *cancelar* para sair.";
            Cache::put($sessionKey, ['state' => 'desp_desc', 'valor' => $val], $ttl);
            return "✅ Valor: *R\$ " . number_format($val, 2, ',', '.') . "*\n\nAgora informe a *descrição*:";
        }

        if ($state === 'desp_desc') {
            $val  = $session['valor'];
            $desc = strip_tags($raw);
            Cache::put($sessionKey, ['state' => 'desp_confirm', 'valor' => $val, 'desc' => $desc], $ttl);
            return "📋 *Confirmar despesa?*\n\n"
                . "Valor: *R\$ " . number_format($val, 2, ',', '.') . "*\n"
                . "Descrição: _{$desc}_\n"
                . ($this->expenseNeedsApproval() ? "Status: Aguardando aprovação\n" : "")
                . "\n"
                . "Responda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'desp_confirm') {
            Cache::forget($sessionKey);
            if (in_array($lower, ['sim', 's', 'yes', '1'])) {
                $t = $this->createTransaction('expense', $session['valor'], $session['desc']);
                return $this->finalize($this->expenseSavedMessage($t));
            }
            return "❌ Despesa cancelada.\n\nDigite *menu* para continuar.";
        }

        // ── RECEITA ──────────────────────────────────────────────────────────

        if ($state === 'recv_valor') {
            $val = $this->parseCurrency($raw);
            if (!$val) return "❌ Valor inválido. Ex: *500,00*\nOu *cancelar* para sair.";
            Cache::put($sessionKey, ['state' => 'recv_desc', 'valor' => $val], $ttl);
            return "✅ Valor: *R\$ " . number_format($val, 2, ',', '.') . "*\n\nAgora informe a *descrição*:";
        }

        if ($state === 'recv_desc') {
            $val  = $session['valor'];
            $desc = strip_tags($raw);
            Cache::put($sessionKey, ['state' => 'recv_confirm', 'valor' => $val, 'desc' => $desc], $ttl);
            return "📋 *Confirmar receita?*\n\n"
                . "Valor: *R\$ " . number_format($val, 2, ',', '.') . "*\n"
                . "Descrição: _{$desc}_\n\n"
                . "Responda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'recv_confirm') {
            Cache::forget($sessionKey);
            if (in_array($lower, ['sim', 's', 'yes', '1'])) {
                $this->createTransaction('income', $session['valor'], $session['desc']);
                return $this->finalize(
                    "✅ Receita de *R\$ " . number_format($session['valor'], 2, ',', '.') . "* registrada!\n"
                    . "_{$session['desc']}_"
                );
            }
            return "❌ Receita cancelada.\n\nDigite *menu* para continuar.";
        }

        // ── ATENDIMENTO ──────────────────────────────────────────────────────

        if ($state === 'atend_nome') {
            [$b, $reply] = $this->resolveBeneficiary(strip_tags($raw), 'atend', [], $sessionKey);
            if ($reply) return $reply;
            if (!$b) {
                $this->logNotFound('Bot ATEND: beneficiario nao encontrado', $raw);
                return "❌ Beneficiário *\"{$raw}\"* não encontrado.\n\nDigite outro nome ou CPF, ou *cancelar* para sair.";
            }
            return $this->askAttendanceType($b, $sessionKey);
        }

        if ($state === 'atend_tipo') {
            Cache::put($sessionKey, [
                'state'            => 'atend_desc',
                'beneficiary_id'   => $session['beneficiary_id'],
                'beneficiary_name' => $session['beneficiary_name'],
                'tipo'             => strip_tags($raw),
            ], $ttl);
            return "🏷️ Tipo: *{$raw}*\n\nDescreva brevemente o atendimento:";
        }

        if ($state === 'atend_desc') {
            $desc = strip_tags($raw);
            Cache::put($sessionKey, [
                'state'            => 'atend_confirm',
                'beneficiary_id'   => $session['beneficiary_id'],
                'beneficiary_name' => $session['beneficiary_name'],
                'tipo'             => $session['tipo'],
                'desc'             => $desc,
            ], $ttl);

            return "📋 *Confirmar atendimento?*\n\n"
                . "Beneficiário: *{$session['beneficiary_name']}* ✓\n"
                . "Tipo: _{$session['tipo']}_\n"
                . "Descrição: _{$desc}_\n\n"
                . "Responda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'atend_confirm') {
            Cache::forget($sessionKey);
            if (!in_array($lower, ['sim', 's', 'yes', '1'])) {
                return "❌ Atendimento cancelado.\n\nDigite *menu* para continuar.";
            }
            $this->createAttendance($session['beneficiary_id'], $session['tipo'], $session['desc'] ?: 'Registrado via WhatsApp Bot');
            return $this->finalize(
                "✅ Atendimento registrado!\n👤 *{$session['beneficiary_name']}* — {$session['tipo']}"
            );
        }

        // ── ESCOLHA DE BENEFICIÁRIO (nome com mais de um resultado) ─────────

        if ($state === 'benef_escolha') {
            $idx = (int) $lower - 1;
            $ids = $session['ids'] ?? [];
            if (!ctype_digit($lower) || !isset($ids[$idx])) {
                return "❌ Opção inválida. Digite o *número* da lista ou *cancelar* para sair.";
            }
            $b = Beneficiary::where('tenant_id', $this->user->tenant_id)->find($ids[$idx]);
            if (!$b) {
                Cache::forget($sessionKey);
                return "❌ Beneficiário não encontrado. Digite *menu* para continuar.";
            }
            Cache::forget($sessionKey);
            return $this->continueWithBeneficiary($session['action'], $session['payload'] ?? [], $b, $sessionKey);
        }

        // ── TAREFA ────────────────────────────────────────────────────────────

        if ($state === 'tarefa_escolha') {
            $idx = (int) $lower - 1;
            $ids = $session['task_ids'] ?? [];
            if (!isset($ids[$idx])) {
                return "❌ Opção inválida. Escolha um número da lista ou *cancelar* para sair.";
            }
            $task = Task::find($ids[$idx]);
            if (!$task || $task->tenant_id !== $this->user->tenant_id) {
                Cache::forget($sessionKey);
                return "❌ Tarefa não encontrada. Digite *menu* para continuar.";
            }
            Cache::put($sessionKey, [
                'state'      => 'tarefa_confirm',
                'task_id'    => $task->id,
                'task_title' => $task->title,
            ], $ttl);
            return "✅ Confirmar conclusão de:\n*\"{$task->title}\"*\n\nResponda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'tarefa_confirm') {
            Cache::forget($sessionKey);
            if (in_array($lower, ['sim', 's', 'yes', '1'])) {
                $task = Task::where('id', $session['task_id'])
                    ->where('tenant_id', $this->user->tenant_id)
                    ->first();
                if ($task) {
                    $task->update(['status' => 'done']);
                    return $this->finalize("✅ Tarefa *\"{$task->title}\"* marcada como concluída!");
                }
            }
            return "❌ Operação cancelada.\n\nDigite *menu* para continuar.";
        }

        // ── EVOLUÇÃO ──────────────────────────────────────────────────────────────

        if ($state === 'evol_nome') {
            [$b, $reply] = $this->resolveBeneficiary(strip_tags($raw), 'evol', [], $sessionKey);
            if ($reply) return $reply;
            if (!$b) {
                return "❌ Beneficiário *\"{$raw}\"* não encontrado.\n\nTente com outro nome ou *cancelar*.";
            }
            return $this->askEvolutionText($b, $sessionKey);
        }

        if ($state === 'evol_texto') {
            $texto = strip_tags($raw);
            Cache::put($sessionKey, [
                'state'            => 'evol_confirm',
                'beneficiary_id'   => $session['beneficiary_id'],
                'beneficiary_name' => $session['beneficiary_name'],
                'texto'            => $texto,
            ], $ttl);

            return "📋 *Confirmar evolução?*\n\n"
                . "Beneficiário: *{$session['beneficiary_name']}*\n"
                . "Texto: _{$texto}_\n\n"
                . "Responda *SIM* para salvar ou *NÃO* para cancelar.";
        }

        if ($state === 'evol_confirm') {
            Cache::forget($sessionKey);
            if (!in_array($lower, ['sim', 's', 'yes', '1'])) {
                return "❌ Evolução cancelada.\n\nDigite *menu* para continuar.";
            }
            $this->createAttendance($session['beneficiary_id'], 'Evolução', $session['texto'], true);
            return $this->finalize("✅ Evolução registrada!\n👤 *{$session['beneficiary_name']}*");
        }

        // ── BENEFICIÁRIO ──────────────────────────────────────────────────────

        if ($state === 'benef_busca') {
            Cache::forget($sessionKey);
            return $this->cmdBeneficiario($raw);
        }

        Cache::forget($sessionKey);
        return $this->welcomeMessage();
    }

    // ─── Command Handlers ─────────────────────────────────────────────────────

    private function welcomeMessage(): string
    {
        $role  = $this->user->role;
        $name  = explode(' ', $this->user->name)[0];
        $keyMap = [
            'super_admin' => 'bot_msg_welcome_ngo',
            'ngo'         => 'bot_msg_welcome_ngo',
            'manager'     => 'bot_msg_welcome_manager',
            'employee'    => 'bot_msg_welcome_employee',
            'common'      => 'bot_msg_welcome_common',
        ];
        $key  = $keyMap[$role] ?? 'bot_msg_welcome_common';

        // Fallbacks alinhados com App\Http\Controllers\Admin\BotController::DEFAULTS.
        // Antes: fallback tinha so 3 itens ("1 saldo, 2 tarefas, 5 ajuda") e
        // NGO com SystemSetting vazio via um menu truncado no WhatsApp.
        $defaults = [
            'bot_msg_welcome_ngo'      => self::MENU_NGO,
            'bot_msg_welcome_manager'  => self::MENU_MANAGER,
            'bot_msg_welcome_employee' => self::MENU_EMPLOYEE,
            'bot_msg_welcome_common'   => "1️⃣ Ver meu saldo pessoal\n2️⃣ Minhas tarefas\n3️⃣ Lançar receita\n4️⃣ Lançar despesa\n5️⃣ Ajuda",
        ];

        $menu = SystemSetting::getValue($key, $defaults[$key] ?? $defaults['bot_msg_welcome_common']);

        // Aviso de seguranca na abertura: cliente sabe que esta num canal
        // privado antes de digitar qualquer dado. NAO promete descarte
        // automatico (limite tecnico da Evolution v2.3.6).
        $privacy = "🔒 *Canal seguro*\n"
                 . "Este atendimento é feito por um canal privado do Vivensi. "
                 . "Suas informações são tratadas com sigilo e usadas apenas para as operações da sua conta. "
                 . "As mensagens desta conversa são apagadas em cerca de " . self::PURGE_DELAY_MINUTES . " minutos.\n";

        return "👋 Olá, *{$name}*!\n\n{$privacy}\n{$menu}";
    }

    /**
     * Anexa o aviso de privacidade + pergunta de fim a mensagens terminais
     * (confirmacao de operacao gravada ou resposta de consulta). Convida o
     * usuario a confirmar que terminou, reforcando o descarte dos dados.
     */
    private function finalize(string $body): string
    {
        return $body . "\n\n" . self::SECURITY_TAG
             . "\n\n_Você finalizou? Digite *menu* pra recomeçar ou *sair* pra encerrar._";
    }

    private function cmdSaldo(): string
    {
        $tenantId = $this->user->tenant_id;

        $income = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'income')->where('status', 'paid')
            ->whereMonth('date', now()->month)->whereYear('date', now()->year)
            ->sum('amount');

        $expense = (float) Transaction::where('tenant_id', $tenantId)
            ->where('type', 'expense')->where('status', 'paid')
            ->whereMonth('date', now()->month)->whereYear('date', now()->year)
            ->sum('amount');

        $balance = $income - $expense;
        $icon    = $balance >= 0 ? '📈' : '📉';

        return $this->finalize(
            "💰 *Resumo financeiro — " . now()->translatedFormat('F/Y') . "*\n_Lançamentos pagos neste mês._\n\n"
            . "✅ Entradas: R\$ " . number_format($income, 2, ',', '.') . "\n"
            . "❌ Saídas:   R\$ " . number_format($expense, 2, ',', '.') . "\n"
            . "{$icon} Resultado do mês: R\$ " . number_format($balance, 2, ',', '.')
        );
    }

    private function cmdTarefas(): string
    {
        $tasks = Task::where('tenant_id', $this->user->tenant_id)
            ->where('assigned_to', $this->user->id)
            ->whereNotIn('status', ['done', 'completed'])
            ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($tasks->isEmpty()) {
            return $this->finalize("✅ *Nenhuma tarefa pendente!* Tudo em dia.");
        }

        $lines = ["📋 *Suas próximas tarefas:*\n"];
        foreach ($tasks as $i => $task) {
            $due     = $task->due_date ? Carbon::parse($task->due_date)->format('d/m') : 'sem prazo';
            $overdue = $task->due_date && Carbon::parse($task->due_date)->isPast() ? ' ⚠️' : '';
            $lines[] = ($i + 1) . ". {$task->title} ({$due}){$overdue}";
        }

        return $this->finalize(implode("\n", $lines));
    }

    private function startConcluirTarefa(string $sessionKey): string
    {
        $tasks = Task::where('tenant_id', $this->user->tenant_id)
            ->where('assigned_to', $this->user->id)
            ->whereNotIn('status', ['done', 'completed'])
            ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($tasks->isEmpty()) {
            return "✅ Nenhuma tarefa pendente!\n\n_Digite *menu* para continuar._";
        }

        $lines = ["📋 *Qual tarefa deseja concluir?*\n"];
        $ids   = [];
        foreach ($tasks as $i => $task) {
            $due     = $task->due_date ? Carbon::parse($task->due_date)->format('d/m') : 'sem prazo';
            $overdue = $task->due_date && Carbon::parse($task->due_date)->isPast() ? ' ⚠️' : '';
            $lines[] = ($i + 1) . ". {$task->title} ({$due}){$overdue}";
            $ids[]   = $task->id;
        }
        $lines[] = "\nDigite o *número* da tarefa ou *cancelar*.";

        Cache::put($sessionKey, ['state' => 'tarefa_escolha', 'task_ids' => $ids], now()->addMinutes(self::SESSION_TTL));
        return implode("\n", $lines);
    }

    private function cmdDespesa(string $raw): string
    {
        // DESP: 100,00 | Descrição
        $body  = trim(substr($raw, 5));
        $parts = explode('|', $body, 2);
        $val   = $this->parseCurrency(trim($parts[0] ?? ''));
        $desc  = strip_tags(trim($parts[1] ?? 'Despesa via Bot'));

        if (!$val) return "❌ Formato inválido. Use:\n*DESP: 150,00 | Descrição*";

        $t = $this->createTransaction('expense', $val, $desc);

        return $this->finalize($this->expenseSavedMessage($t));
    }

    private function cmdReceita(string $raw): string
    {
        // RECV: 100,00 | Descrição
        $body  = trim(substr($raw, 5));
        $parts = explode('|', $body, 2);
        $val   = $this->parseCurrency(trim($parts[0] ?? ''));
        $desc  = strip_tags(trim($parts[1] ?? 'Receita via Bot'));

        if (!$val) return "❌ Formato inválido. Use:\n*RECV: 500,00 | Descrição*";

        $this->createTransaction('income', $val, $desc);

        return $this->finalize(
            "✅ Receita de *R\$ " . number_format($val, 2, ',', '.') . "* registrada!\n_{$desc}_"
        );
    }

    private function cmdAtendimento(string $raw, string $sessionKey): string
    {
        // ATEND: Nome | Tipo | Descrição
        $body  = trim(substr($raw, 6));
        $parts = explode('|', $body, 3);
        $nome  = strip_tags(trim($parts[0] ?? ''));
        $tipo  = strip_tags(trim($parts[1] ?? 'Geral')) ?: 'Geral';
        $desc  = strip_tags(trim($parts[2] ?? ''));

        if (!$nome) return "❌ Formato inválido. Use:\n*ATEND: Nome | Tipo | Descrição*";

        [$b, $reply] = $this->resolveBeneficiary($nome, 'atend_rapido', ['tipo' => $tipo, 'desc' => $desc], $sessionKey);
        if ($reply) return $reply;
        if ($b) return $this->continueWithBeneficiary('atend_rapido', ['tipo' => $tipo, 'desc' => $desc], $b, $sessionKey);

        $this->logNotFound('Bot ATEND: beneficiario nao encontrado (comando rapido)', $nome);
        return "⚠️ Beneficiário *\"{$nome}\"* não encontrado no cadastro.\n\nUse *BENEF: {$nome}* para confirmar o nome exato, ou cadastre-o primeiro no sistema.\n\n_Digite *menu* para continuar._";
    }

    private function cmdBeneficiario(string $query): string
    {
        if (!$query) return "❌ Informe o nome ou CPF. Ex: *BENEF: João Silva*";

        // Bug historico (fixado 2026-08-19): antes fazia WHERE cpf LIKE contra
        // ciphertext AES — nunca casava. Agora usa BeneficiaryLookupService que
        // busca via cpf_bidx/nis_bidx (HMAC-SHA256) — mesmo padrao do phone_bidx.
        $lookup  = new BeneficiaryLookupService();
        $results = $lookup->search($this->user->tenant_id, $query, 3);

        if ($results->isEmpty()) {
            return $this->finalize("❌ Nenhum beneficiário encontrado para *\"{$query}\"*.");
        }

        $lines = ["🔍 *Resultado para \"{$query}\":*\n"];
        foreach ($results as $b) {
            $cpf    = $b->cpf ? ' | CPF: ' . $lookup->obfuscateCpf($b->cpf) : '';
            // Status em português (antes saía "[active]" no WhatsApp).
            $label  = ['active' => 'ativo', 'inactive' => 'inativo', 'graduated' => 'graduado'][$b->status] ?? $b->status;
            $status = $b->status ? " [{$label}]" : '';
            $lines[] = "• *{$b->name}*{$cpf}{$status}";
        }

        return $this->finalize(implode("\n", $lines));
    }

    private function cmdAtendimentos(): string
    {
        $tenantId = $this->user->tenant_id;
        $month    = now()->translatedFormat('F/Y');

        $rows = \Illuminate\Support\Facades\DB::table('attendances')
            ->join('beneficiaries', 'attendances.beneficiary_id', '=', 'beneficiaries.id')
            ->where('attendances.tenant_id', $tenantId)
            ->whereMonth('attendances.date', now()->month)
            ->whereYear('attendances.date', now()->year)
            ->orderBy('attendances.date', 'desc')
            ->limit(15)
            ->select('beneficiaries.name as beneficiary_name', 'attendances.date', 'attendances.type')
            ->get();

        if ($rows->isEmpty()) {
            return $this->finalize("📋 Nenhum atendimento registrado em {$month}.");
        }

        $lines = ["📋 *Atendimentos — {$month}*\n"];
        foreach ($rows as $a) {
            $data  = Carbon::parse($a->date)->format('d/m');
            $tipo  = mb_substr($a->type, 0, 22);
            $nome  = mb_substr($a->beneficiary_name, 0, 25);
            $lines[] = "• {$nome} | {$tipo} | {$data}";
        }

        $total = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        $lines[] = "\n_Total no mês: {$total} atendimento(s)_";
        $lines[] = "_HIST: [nome] para histórico individual_";

        return $this->finalize(implode("\n", $lines));
    }

    private function cmdHistoricoAtendimentos(string $query, string $sessionKey): string
    {
        if (!$query) return "❌ Informe o nome. Ex: *HIST: João Silva*";

        [$beneficiary, $reply] = $this->resolveBeneficiary(strip_tags($query), 'hist', [], $sessionKey);
        if ($reply) return $reply;

        if (!$beneficiary) {
            return "❌ Beneficiário *\"{$query}\"* não encontrado no cadastro.\n\n_Digite *menu* para voltar._";
        }

        return $this->historyOf($beneficiary);
    }

    private function historyOf(Beneficiary $beneficiary): string
    {
        $tenantId = $this->user->tenant_id;

        $rows = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('tenant_id', $tenantId)
            ->where('beneficiary_id', $beneficiary->id)
            ->orderBy('date', 'desc')
            ->limit(10)
            ->select('date', 'type', 'description')
            ->get();

        if ($rows->isEmpty()) {
            return $this->finalize("📋 *{$beneficiary->name}*\n\nNenhum registro encontrado.");
        }

        $lines = ["📋 *Histórico: {$beneficiary->name}*\n"];
        foreach ($rows as $a) {
            $data  = Carbon::parse($a->date)->format('d/m/Y');
            $tipo  = mb_substr($a->type, 0, 22);
            $icon  = strtolower($a->type) === 'evolução' ? '📝' : '🤝';
            $desc  = $a->description ? ' — ' . mb_substr($a->description, 0, 35) . '...' : '';
            $lines[] = "{$icon} {$data} | {$tipo}{$desc}";
        }

        $total     = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)->count();
        $totalEvol = \Illuminate\Support\Facades\DB::table('attendances')
            ->where('tenant_id', $tenantId)->where('beneficiary_id', $beneficiary->id)
            ->where('type', 'Evolução')->count();

        $lines[] = "\n_Total: {$total} registro(s) | {$totalEvol} evolução(ões)_";

        return $this->finalize(implode("\n", $lines));
    }

    private function cmdEvolucaoRapida(string $raw, string $sessionKey): string
    {
        // EVOL: Nome do Beneficiário | Texto da evolução
        $body  = trim(substr($raw, 5));
        $parts = explode('|', $body, 2);
        $nome  = strip_tags(trim($parts[0] ?? ''));
        $texto = strip_tags(trim($parts[1] ?? ''));

        if (!$nome || !$texto) {
            return "❌ Formato inválido. Use:\n*EVOL: Nome Beneficiário | Texto da evolução*";
        }

        [$b, $reply] = $this->resolveBeneficiary($nome, 'evol_rapido', ['texto' => $texto], $sessionKey);
        if ($reply) return $reply;

        if (!$b) {
            return "❌ Beneficiário *\"{$nome}\"* não encontrado no cadastro.\n\n_Use BENEF: {$nome} para verificar o nome exato._";
        }

        return $this->continueWithBeneficiary('evol_rapido', ['texto' => $texto], $b, $sessionKey);
    }

    // ─── Beneficiário: busca sem ambiguidade ──────────────────────────────────

    /**
     * Acha o beneficiário pelo nome ou CPF/NIS. Com um resultado (ou um nome
     * exatamente igual), devolve [beneficiário, null]. Com vários, guarda a
     * ação pendente na sessão e devolve [null, lista para o cliente escolher].
     * Sem resultado: [null, null].
     *
     * Antes: LIKE '%nome%' + first() — "Ana" gravava na "Mariana" (2026-11-22).
     */
    private function resolveBeneficiary(string $query, string $action, array $payload, string $sessionKey): array
    {
        $query = trim($query);
        if ($query === '') return [null, null];

        $lookup  = new BeneficiaryLookupService();
        $results = $lookup->search($this->user->tenant_id, $query, self::MAX_CHOICES + 1);

        if ($results->isEmpty()) return [null, null];
        if ($results->count() === 1) return [$results->first(), null];

        $exact = $results->filter(fn ($b) => mb_strtolower(trim($b->name)) === mb_strtolower($query));
        if ($exact->count() === 1) return [$exact->first(), null];

        $shown = $results->take(self::MAX_CHOICES)->values();
        Cache::put($sessionKey, [
            'state'   => 'benef_escolha',
            'ids'     => $shown->pluck('id')->all(),
            'action'  => $action,
            'payload' => $payload,
        ], now()->addMinutes(self::SESSION_TTL));

        $lines = ["🔎 Encontrei mais de um beneficiário para *\"{$query}\"*:\n"];
        foreach ($shown as $i => $b) {
            $cpf = $b->cpf ? ' — CPF ' . $lookup->obfuscateCpf($b->cpf) : '';
            $lines[] = ($i + 1) . ". {$b->name}{$cpf}";
        }
        if ($results->count() > self::MAX_CHOICES) {
            $lines[] = "\n_Há mais resultados. Se não estiver na lista, digite *cancelar* e use o nome completo ou o CPF._";
        }
        $lines[] = "\nDigite o *número* do beneficiário ou *cancelar*.";

        return [null, implode("\n", $lines)];
    }

    /** Segue a ação que estava esperando a escolha do beneficiário. */
    private function continueWithBeneficiary(string $action, array $payload, Beneficiary $b, string $sessionKey): string
    {
        switch ($action) {
            case 'atend':
                return $this->askAttendanceType($b, $sessionKey);
            case 'evol':
                return $this->askEvolutionText($b, $sessionKey);
            case 'hist':
                return $this->historyOf($b);
            case 'atend_rapido':
                $this->createAttendance($b->id, $payload['tipo'] ?? 'Geral', ($payload['desc'] ?? '') ?: 'Registrado via WhatsApp Bot');
                return $this->finalize("✅ Atendimento registrado!\n👤 *{$b->name}* — " . ($payload['tipo'] ?? 'Geral'));
            case 'evol_rapido':
                $this->createAttendance($b->id, 'Evolução', $payload['texto'] ?? '', true);
                return $this->finalize("✅ Evolução registrada!\n👤 *{$b->name}*\n_" . ($payload['texto'] ?? '') . "_");
        }
        return $this->welcomeMessage();
    }

    private function askAttendanceType(Beneficiary $b, string $sessionKey): string
    {
        Cache::put($sessionKey, [
            'state'            => 'atend_tipo',
            'beneficiary_id'   => $b->id,
            'beneficiary_name' => $b->name,
        ], now()->addMinutes(self::SESSION_TTL));
        return "👤 *{$b->name}* ✓\n\nQual o *tipo* de atendimento?\n(ex: saúde, educação, assistência social)";
    }

    private function askEvolutionText(Beneficiary $b, string $sessionKey): string
    {
        Cache::put($sessionKey, [
            'state'            => 'evol_texto',
            'beneficiary_id'   => $b->id,
            'beneficiary_name' => $b->name,
        ], now()->addMinutes(self::SESSION_TTL));
        return "👤 *{$b->name}* encontrado.\n\nDigite o texto da *evolução de caso*:";
    }

    private function createAttendance(int $beneficiaryId, string $type, string $description, bool $gratuito = false): void
    {
        Attendance::create([
            'tenant_id'      => $this->user->tenant_id,
            'beneficiary_id' => $beneficiaryId,
            'user_id'        => $this->user->id,
            'date'           => now()->toDateString(),
            'type'           => mb_substr($type, 0, 100),
            'description'    => $description,
            'gratuito'       => $gratuito,
        ]);
    }

    /** Nao loga o nome consultado — PII. tenant_id + hash bastam para debug. */
    private function logNotFound(string $msg, string $query): void
    {
        Log::info($msg, [
            'tenant_id'  => $this->user->tenant_id,
            'query_hash' => substr(hash('sha256', mb_strtolower(trim($query))), 0, 12),
        ]);
    }

    // ─── Permissões e conta ───────────────────────────────────────────────────

    /** Financeiro: mesma regra do painel (colaborador de ONG não vê). */
    private function canSeeFinance(): bool
    {
        return $this->user->can('access-manager');
    }

    private function noFinanceAccess(): string
    {
        return "🔒 O financeiro é só para administração e coordenação da organização.\n\n_Digite *menu* para ver as suas opções._";
    }

    /** Mesma regra do FinanceService: despesa de colaborador vai para aprovação. */
    private function expenseNeedsApproval(): bool
    {
        return !in_array($this->user->role, ['manager', 'ngo', 'super_admin'], true);
    }

    private function expenseSavedMessage(Transaction $t): string
    {
        $msg = "✅ Despesa de *R\$ " . number_format((float) $t->amount, 2, ',', '.') . "* registrada!\n_{$t->description}_";
        return $t->approval_status === 'pending' ? $msg . "\n_Status: Aguardando aprovação._" : $msg;
    }

    /**
     * Mesma regra do middleware CheckSubscription: o bot só funciona com a
     * conta liberada no painel. Devolve a mensagem de bloqueio, ou null.
     */
    private function accountBlockedMessage(): ?string
    {
        if ($this->user->role === 'super_admin') return null;

        $tenant = $this->user->tenant;
        if (!$tenant) return null;

        $status   = $tenant->subscription_status;
        $courtesy = $tenant->plan && $tenant->plan->is_courtesy;

        $ok = $status !== 'suspended' && $status !== 'awaiting_contract' && (
            $courtesy
            || ($status === 'active' && !app(InvoiceService::class)->isBlocked($tenant))
            || ($status === 'trialing' && optional($tenant->trial_ends_at)->isFuture())
        );
        if ($ok) return null;

        return $this->user->role === 'ngo'
            ? "⚠️ *Conta com pendência*\n\nO bot volta a funcionar quando o painel for liberado. Entre no painel e veja *Minha conta → Faturas*."
            : "⚠️ *Conta com pendência*\n\nO bot volta a funcionar quando a conta da organização for regularizada. Fale com o administrador.";
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function parseCurrency(string $value): ?float
    {
        // Remove prefixo R$, espaços extras
        $v = preg_replace('/^[Rr]\$\s*/', '', trim($value));
        $v = trim($v);

        if ($v === '') return null;

        // Formato BR com separador de milhar: 1.500,00 ou 500,00
        if (preg_match('/^[\d.]+,\d{1,2}$/', $v)) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        // Formato US com separador de milhar: 1,500.00 ou 500.00
        elseif (preg_match('/^[\d,]+\.\d{1,2}$/', $v)) {
            $v = str_replace(',', '', $v);
        }
        // Inteiro ou apenas vírgula sem centavos: 1500 ou 1,500
        else {
            $v = preg_replace('/[^\d]/', '', $v);
        }

        $f = (float) $v;
        return $f > 0 ? $f : null;
    }

    /**
     * Lança pelo mesmo serviço do painel: aprovação de despesa por perfil,
     * cache do financeiro limpo e aviso aos gestores (antes gravava direto,
     * sem approval_status — a despesa não aparecia na lista de aprovação).
     */
    private function createTransaction(string $type, float $amount, string $description): Transaction
    {
        return app(FinanceService::class)->createTransaction([
            'type'        => $type,
            // O serviço espera o formato brasileiro ("1500,50"): ele tira os
            // pontos e troca a vírgula. Um float 150.5 viraria 1505.
            'amount'      => number_format($amount, 2, ',', ''),
            'description' => mb_substr($description, 0, 255) ?: ($type === 'expense' ? 'Despesa via Bot' : 'Receita via Bot'),
            'date'        => now()->toDateString(),
        ], $this->user);
    }
}
