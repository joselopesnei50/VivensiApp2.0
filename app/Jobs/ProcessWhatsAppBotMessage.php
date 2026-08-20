<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BeneficiaryLookupService;
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

    /**
     * Aviso curto anexado a mensagens terminais (confirmacoes de sucesso e
     * respostas de consulta) pra reforcar ao cliente que os dados foram
     * apagados. Ao final do fluxo (helpers::finalize), tambem pergunta se
     * o usuario terminou pra convidar a fechar a sessao com sucesso.
     */
    private const SECURITY_TAG = "🔒 _Suas mensagens são apagadas automaticamente após o processamento._";

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
        // Evolution + WhatsApp do cliente. Falha nao interrompe fluxo — o
        // aviso na welcome message ja preveniu o usuario da janela residual.
        $this->purgeMessagesFromEvolution($evo, $sendResult);
    }

    /**
     * Deleta inbound + outbound da Evolution API pra que nenhum operador
     * com acesso a instancia (Evolution UI, WhatsApp Web conectado, etc.)
     * consiga ler os dados que o cliente enviou ou consultou.
     */
    private function purgeMessagesFromEvolution(EvolutionApiService $evo, ?array $sendResult): void
    {
        if (!empty($this->inboundKey['id']) && !empty($this->inboundKey['remoteJid'])) {
            $evo->deleteMessageForEveryone($this->inboundKey);
        }

        $outboundKey = $this->extractOutboundKey($sendResult);
        if (!empty($outboundKey['id']) && !empty($outboundKey['remoteJid'])) {
            $evo->deleteMessageForEveryone($outboundKey);
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
                 . "\n\n_Digite *menu* pra recomeçar quando precisar._";
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
        if ($lower === '1') return $this->cmdSaldo();
        if ($lower === '2') return $this->cmdTarefas();

        if ($lower === '3') {
            if (in_array($role, ['ngo', 'super_admin'])) {
                Cache::put($sessionKey, ['state' => 'atend_nome'], now()->addMinutes(self::SESSION_TTL));
                return "📋 *Registrar Atendimento*\n\nDigite o *nome completo* do beneficiário:";
            }
            if ($role === 'common') {
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
            return "💸 *Lançar Despesa*\n\nDigite o *valor* (ex: 150,00):";
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
        if (str_starts_with(strtoupper($raw), 'RECV:'))  return $this->cmdReceita($raw);
        if (str_starts_with(strtoupper($raw), 'ATEND:')) return $this->cmdAtendimento($raw);
        if (str_starts_with(strtoupper($raw), 'BENEF:')) return $this->cmdBeneficiario(trim(substr($raw, 6)));
        if (str_starts_with(strtoupper($raw), 'HIST:'))  return $this->cmdHistoricoAtendimentos(trim(substr($raw, 5)));
        if (str_starts_with(strtoupper($raw), 'EVOL:'))  return $this->cmdEvolucaoRapida($raw);

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
                . "Status: Aguardando aprovação\n\n"
                . "Responda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'desp_confirm') {
            Cache::forget($sessionKey);
            if (in_array($lower, ['sim', 's', 'yes', '1'])) {
                $this->createTransaction('expense', $session['valor'], $session['desc']);
                return $this->finalize(
                    "✅ Despesa de *R\$ " . number_format($session['valor'], 2, ',', '.') . "* registrada!\n"
                    . "_{$session['desc']}_\n_Status: Aguardando aprovação._"
                );
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
            Cache::put($sessionKey, ['state' => 'atend_tipo', 'nome' => strip_tags($raw)], $ttl);
            return "👤 Nome: *{$raw}*\n\nQual o *tipo* de atendimento?\n(ex: saúde, educação, assistência social)";
        }

        if ($state === 'atend_tipo') {
            Cache::put($sessionKey, [
                'state' => 'atend_desc',
                'nome'  => $session['nome'],
                'tipo'  => strip_tags($raw),
            ], $ttl);
            return "🏷️ Tipo: *{$raw}*\n\nDescreva brevemente o atendimento:";
        }

        if ($state === 'atend_desc') {
            $nome = $session['nome'];
            $tipo = $session['tipo'];
            $desc = strip_tags($raw);

            // Usa lookup unificado (nome LIKE + cpf_bidx/nis_bidx). Se digitar
            // um CPF exato aqui, tambem casa.
            $beneficiary = (new BeneficiaryLookupService())
                ->search($this->user->tenant_id, $nome, 1)
                ->first();

            Cache::put($sessionKey, [
                'state'            => 'atend_confirm',
                'nome'             => $nome,
                'tipo'             => $tipo,
                'desc'             => $desc,
                'beneficiary_id'   => $beneficiary?->id,
                'beneficiary_name' => $beneficiary?->name,
            ], $ttl);

            $benefInfo = $beneficiary
                ? "Beneficiário: *{$beneficiary->name}* ✓"
                : "Beneficiário: _{$nome}_ ⚠️ *não localizado* — o registro não será salvo se confirmar";

            return "📋 *Confirmar atendimento?*\n\n"
                . "{$benefInfo}\n"
                . "Tipo: _{$tipo}_\n"
                . "Descrição: _{$desc}_\n\n"
                . "Responda *SIM* para confirmar ou *NÃO* para cancelar.";
        }

        if ($state === 'atend_confirm') {
            Cache::forget($sessionKey);
            if (!in_array($lower, ['sim', 's', 'yes', '1'])) {
                return "❌ Atendimento cancelado.\n\nDigite *menu* para continuar.";
            }
            if ($session['beneficiary_id'] && \Illuminate\Support\Facades\Schema::hasTable('attendances')) {
                \Illuminate\Support\Facades\DB::table('attendances')->insert([
                    'tenant_id'      => $this->user->tenant_id,
                    'beneficiary_id' => $session['beneficiary_id'],
                    'user_id'        => $this->user->id,
                    'date'           => now()->toDateString(),
                    'type'           => $session['tipo'],
                    'description'    => $session['desc'] ?: "Registrado via WhatsApp Bot",
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                return $this->finalize(
                    "✅ Atendimento registrado!\n👤 *{$session['beneficiary_name']}* — {$session['tipo']}"
                );
            }
            // Nao loga nome do beneficiario — PII do cliente. tenant_id + hash
            // cobrem debug sem vazar identidade no storage/logs/laravel.log.
            Log::info('Bot ATEND: beneficiario nao encontrado', [
                'tenant_id'  => $this->user->tenant_id,
                'query_hash' => substr(hash('sha256', mb_strtolower(trim($session['nome']))), 0, 12),
            ]);
            return "⚠️ Beneficiário *\"{$session['nome']}\"* não encontrado no cadastro.\n\nUse *BENEF: {$session['nome']}* para confirmar o nome exato ou cadastre-o primeiro no sistema.\n\nDigite *menu* para continuar.";
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
            $beneficiary = Beneficiary::where('tenant_id', $this->user->tenant_id)
                ->where('name', 'like', '%' . strip_tags($raw) . '%')
                ->first();

            if (!$beneficiary) {
                return "❌ Beneficiário *\"{$raw}\"* não encontrado.\n\nTente com outro nome ou *cancelar*.";
            }

            Cache::put($sessionKey, [
                'state'            => 'evol_texto',
                'beneficiary_id'   => $beneficiary->id,
                'beneficiary_name' => $beneficiary->name,
            ], $ttl);

            return "👤 *{$beneficiary->name}* encontrado.\n\nDigite o texto da *evolução de caso*:";
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
            \Illuminate\Support\Facades\DB::table('attendances')->insert([
                'tenant_id'      => $this->user->tenant_id,
                'beneficiary_id' => $session['beneficiary_id'],
                'user_id'        => $this->user->id,
                'date'           => now()->toDateString(),
                'type'           => 'Evolução',
                'description'    => $session['texto'],
                'gratuito'       => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
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
            'bot_msg_welcome_ngo'      => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Registrar atendimento\n4️⃣ Consultar beneficiário\n5️⃣ Ajuda\n6️⃣ Atendimentos recentes\n7️⃣ Registrar evolução",
            'bot_msg_welcome_manager'  => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Lançar despesa\n5️⃣ Ajuda",
            'bot_msg_welcome_employee' => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Lançar despesa (aguarda aprovação)\n5️⃣ Ajuda",
            'bot_msg_welcome_common'   => "1️⃣ Ver meu saldo pessoal\n2️⃣ Minhas tarefas\n3️⃣ Lançar receita\n4️⃣ Lançar despesa\n5️⃣ Ajuda",
        ];

        $menu = SystemSetting::getValue($key, $defaults[$key] ?? $defaults['bot_msg_welcome_common']);

        // Aviso de privacidade sempre na abertura: cliente entende que
        // conversa e efemera antes de digitar qualquer dado. Palavra-chave
        // "apagadas" e proposital pra deixar claro que nao ha historico
        // acessivel do lado da Vivensi.
        $privacy = "🔒 *Privacidade dos seus dados*\n"
                 . "As mensagens desta conversa são apagadas automaticamente após o processamento. "
                 . "Nenhum atendente da Vivensi consegue ler o que você envia ou consulta aqui.\n";

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
            "💰 *Resumo — " . now()->translatedFormat('F/Y') . "*\n\n"
            . "✅ Entradas: R\$ " . number_format($income, 2, ',', '.') . "\n"
            . "❌ Saídas:   R\$ " . number_format($expense, 2, ',', '.') . "\n"
            . "{$icon} Saldo:    R\$ " . number_format($balance, 2, ',', '.')
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

        $this->createTransaction('expense', $val, $desc);

        return $this->finalize(
            "✅ Despesa de *R\$ " . number_format($val, 2, ',', '.') . "* registrada!\n"
            . "_{$desc}_\n_Status: Aguardando aprovação._"
        );
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

    private function cmdAtendimento(string $raw): string
    {
        // ATEND: Nome | Tipo | Descrição
        $body  = trim(substr($raw, 6));
        $parts = explode('|', $body, 3);
        $nome  = strip_tags(trim($parts[0] ?? ''));
        $tipo  = strip_tags(trim($parts[1] ?? 'Geral'));
        $desc  = strip_tags(trim($parts[2] ?? ''));

        if (!$nome) return "❌ Formato inválido. Use:\n*ATEND: Nome | Tipo | Descrição*";

        $beneficiary = Beneficiary::where('tenant_id', $this->user->tenant_id)
            ->where('name', 'like', "%{$nome}%")
            ->first();

        if ($beneficiary && \Illuminate\Support\Facades\Schema::hasTable('attendances')) {
            \Illuminate\Support\Facades\DB::table('attendances')->insert([
                'tenant_id'      => $this->user->tenant_id,
                'beneficiary_id' => $beneficiary->id,
                'user_id'        => $this->user->id,
                'date'           => now()->toDateString(),
                'type'           => $tipo,
                'description'    => $desc ?: "Registrado via WhatsApp Bot",
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            return $this->finalize(
                "✅ Atendimento registrado!\n👤 *{$beneficiary->name}* — {$tipo}"
            );
        }

        Log::info('Bot ATEND: beneficiario nao encontrado (comando rapido)', [
            'tenant_id'  => $this->user->tenant_id,
            'query_hash' => substr(hash('sha256', mb_strtolower(trim($nome))), 0, 12),
        ]);
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
            $status = $b->status ? " [{$b->status}]" : '';
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

    private function cmdHistoricoAtendimentos(string $query): string
    {
        if (!$query) return "❌ Informe o nome. Ex: *HIST: João Silva*";

        $tenantId    = $this->user->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)
            ->where('name', 'like', "%{$query}%")
            ->first();

        if (!$beneficiary) {
            return "❌ Beneficiário *\"{$query}\"* não encontrado no cadastro.\n\n_Digite *menu* para voltar._";
        }

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

    private function cmdEvolucaoRapida(string $raw): string
    {
        // EVOL: Nome do Beneficiário | Texto da evolução
        $body  = trim(substr($raw, 5));
        $parts = explode('|', $body, 2);
        $nome  = strip_tags(trim($parts[0] ?? ''));
        $texto = strip_tags(trim($parts[1] ?? ''));

        if (!$nome || !$texto) {
            return "❌ Formato inválido. Use:\n*EVOL: Nome Beneficiário | Texto da evolução*";
        }

        $beneficiary = Beneficiary::where('tenant_id', $this->user->tenant_id)
            ->where('name', 'like', "%{$nome}%")
            ->first();

        if (!$beneficiary) {
            return "❌ Beneficiário *\"{$nome}\"* não encontrado no cadastro.\n\n_Use BENEF: {$nome} para verificar o nome exato._";
        }

        \Illuminate\Support\Facades\DB::table('attendances')->insert([
            'tenant_id'      => $this->user->tenant_id,
            'beneficiary_id' => $beneficiary->id,
            'user_id'        => $this->user->id,
            'date'           => now()->toDateString(),
            'type'           => 'Evolução',
            'description'    => $texto,
            'gratuito'       => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->finalize(
            "✅ Evolução registrada!\n👤 *{$beneficiary->name}*\n_{$texto}_"
        );
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

    private function createTransaction(string $type, float $amount, string $description): void
    {
        Transaction::create([
            'tenant_id'   => $this->user->tenant_id,
            'type'        => $type,
            'amount'      => $amount,
            'description' => $description,
            'date'        => now()->toDateString(),
            'status'      => $type === 'expense' ? 'pending' : 'paid',
        ]);
    }
}
