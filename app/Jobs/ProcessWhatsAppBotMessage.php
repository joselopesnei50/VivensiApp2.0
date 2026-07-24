<?php

namespace App\Jobs;

use App\Models\Beneficiary;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
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

    public function __construct(
        protected User   $user,
        protected string $waId,
        protected string $text
    ) {}

    public function handle(): void
    {
        $instanceName = SystemSetting::getValue('bot_instance_name', '');
        if (!$instanceName) {
            Log::error('ProcessWhatsAppBotMessage: bot_instance_name não configurado');
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
                'user'  => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            $response = "⚠️ Erro ao processar seu comando. Digite *menu* para recomeçar.";
        }

        if ($response) {
            $evo->sendMessage($phone, $response);
        }
    }

    // ─── Command Router ───────────────────────────────────────────────────────

    private function processCommand(string $lower, string $raw, array $session, string $sessionKey): string
    {
        // Reset / menu triggers
        if (in_array($lower, ['menu', 'inicio', 'início', 'voltar', 'oi', 'olá', 'ola', 'hi', 'hello', '0'])) {
            Cache::forget($sessionKey);
            return $this->welcomeMessage();
        }

        if (in_array($lower, ['cancelar', 'cancel', 'sair'])) {
            Cache::forget($sessionKey);
            return "✅ Operação cancelada. Digite *menu* para ver as opções.";
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

        // Structured prefix commands — power-user mode, sem confirmação
        if (str_starts_with(strtoupper($raw), 'DESP:'))  return $this->cmdDespesa($raw);
        if (str_starts_with(strtoupper($raw), 'RECV:'))  return $this->cmdReceita($raw);
        if (str_starts_with(strtoupper($raw), 'ATEND:')) return $this->cmdAtendimento($raw);
        if (str_starts_with(strtoupper($raw), 'BENEF:')) return $this->cmdBeneficiario(trim(substr($raw, 6)));

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
                return "✅ Despesa de *R\$ " . number_format($session['valor'], 2, ',', '.') . "* registrada!\n"
                    . "_{$session['desc']}_\n_Status: Aguardando aprovação._\n\nDigite *menu* para continuar.";
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
                return "✅ Receita de *R\$ " . number_format($session['valor'], 2, ',', '.') . "* registrada!\n"
                    . "_{$session['desc']}_\n\nDigite *menu* para continuar.";
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

            $beneficiary = Beneficiary::where('tenant_id', $this->user->tenant_id)
                ->where('name', 'like', "%{$nome}%")
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
                : "Beneficiário: _{$nome}_ (não cadastrado — será registrado como nota)";

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
                return "✅ Atendimento registrado!\n👤 *{$session['beneficiary_name']}* — {$session['tipo']}\n\nDigite *menu* para continuar.";
            }
            Log::info("Bot ATEND: Beneficiário '{$session['nome']}' não encontrado. Tenant: {$this->user->tenant_id}");
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
                    return "✅ Tarefa *\"{$task->title}\"* marcada como concluída!\n\nDigite *menu* para continuar.";
                }
            }
            return "❌ Operação cancelada.\n\nDigite *menu* para continuar.";
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
        $menu = SystemSetting::getValue($key, "1️⃣ Ver saldo\n2️⃣ Minhas tarefas\n5️⃣ Ajuda");

        return "👋 Olá, *{$name}*!\n\n{$menu}";
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

        return "💰 *Resumo — " . now()->translatedFormat('F/Y') . "*\n\n"
            . "✅ Entradas: R\$ " . number_format($income, 2, ',', '.') . "\n"
            . "❌ Saídas:   R\$ " . number_format($expense, 2, ',', '.') . "\n"
            . "{$icon} Saldo:    R\$ " . number_format($balance, 2, ',', '.') . "\n\n"
            . "_Digite *menu* para voltar._";
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
            return "✅ *Nenhuma tarefa pendente!* Tudo em dia.\n\n_Digite *menu* para voltar._";
        }

        $lines = ["📋 *Suas próximas tarefas:*\n"];
        foreach ($tasks as $i => $task) {
            $due     = $task->due_date ? Carbon::parse($task->due_date)->format('d/m') : 'sem prazo';
            $overdue = $task->due_date && Carbon::parse($task->due_date)->isPast() ? ' ⚠️' : '';
            $lines[] = ($i + 1) . ". {$task->title} ({$due}){$overdue}";
        }
        $lines[] = "\n_Digite *menu* para voltar._";

        return implode("\n", $lines);
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

        return "✅ Despesa de *R\$ " . number_format($val, 2, ',', '.') . "* registrada!\n_{$desc}_\n_Status: Aguardando aprovação._\n\n_Digite *menu* para continuar._";
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

        return "✅ Receita de *R\$ " . number_format($val, 2, ',', '.') . "* registrada!\n_{$desc}_\n\n_Digite *menu* para continuar._";
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
            return "✅ Atendimento registrado!\n👤 *{$beneficiary->name}* — {$tipo}\n\n_Digite *menu* para continuar._";
        }

        Log::info("Bot ATEND: Beneficiário '{$nome}' não encontrado. Tenant: {$this->user->tenant_id}");
        return "⚠️ Beneficiário *\"{$nome}\"* não encontrado no cadastro.\n\nUse *BENEF: {$nome}* para confirmar o nome exato, ou cadastre-o primeiro no sistema.\n\n_Digite *menu* para continuar._";
    }

    private function cmdBeneficiario(string $query): string
    {
        if (!$query) return "❌ Informe o nome ou CPF. Ex: *BENEF: João Silva*";

        $results = Beneficiary::where('tenant_id', $this->user->tenant_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('cpf', 'like', "%{$query}%");
            })
            ->limit(3)
            ->get();

        if ($results->isEmpty()) {
            return "❌ Nenhum beneficiário encontrado para *\"{$query}\"*.\n\n_Digite *menu* para voltar._";
        }

        $lines = ["🔍 *Resultado para \"{$query}\":*\n"];
        foreach ($results as $b) {
            $cpf    = $b->cpf ? ' | CPF: ' . preg_replace('/^(\d{3})\.\d{3}\.\d{3}-(\d{2})$/', '$1.***.***-$2', $b->cpf) : '';
            $status = $b->status ? " [{$b->status}]" : '';
            $lines[] = "• *{$b->name}*{$cpf}{$status}";
        }
        $lines[] = "\n_Digite *menu* para voltar._";

        return implode("\n", $lines);
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
