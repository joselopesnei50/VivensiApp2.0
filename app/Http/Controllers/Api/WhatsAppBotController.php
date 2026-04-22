<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppBotController — Bot de Gestão Interna do Vivensi
 *
 * Permite que usuários do sistema (NGO, Manager, Employee, Common)
 * consultem e insiram dados via WhatsApp, respeitando roles e regras de negócio.
 *
 * Autenticação: cruzamento do número de WhatsApp com o campo `phone` na tabela users.
 * Sessão de conversa: Cache com TTL de 5 minutos por número.
 */
class WhatsAppBotController extends Controller
{
    private string $botInstanceName;
    private string $botPhone = '5516997618695'; // Número do bot configurado

    public function __construct()
    {
        $this->botInstanceName = config('whatsapp.bot_instance_name', 'vivensi-bot');
    }

    /**
     * Ponto de entrada do Webhook da Evolution API para o bot de gestão.
     */
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        $event = $data['event'] ?? ($data['type'] ?? '');

        // Ignorar eventos que não sejam mensagens recebidas
        if (!in_array($event, ['messages.upsert', 'MESSAGES_UPSERT', 'message'])) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $waId = $this->extractWaId($data);
        $text = $this->extractText($data);

        // Ignorar mensagens vazias, de grupos ou do próprio bot
        if (!$waId || !$text || str_contains($waId, '@g.us')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $cleanPhone = preg_replace('/\D/', '', explode('@', $waId)[0]);
        if ($cleanPhone === $this->botPhone) {
            return response()->json(['status' => 'self'], 200);
        }

        // Autenticar usuário pelo telefone
        $user = $this->findUserByPhone($cleanPhone);

        if (!$user) {
            $this->send($waId, "❌ *Número não cadastrado no Vivensi.*\n\nPeça ao administrador para vincular seu WhatsApp ao sistema.");
            return response()->json(['status' => 'unauthorized'], 200);
        }

        Log::info("WhatsApp Bot: [{$user->role}] {$user->name} → {$text}");

        $this->processMessage($user, $waId, mb_strtolower(trim($text)));

        return response()->json(['status' => 'ok'], 200);
    }

    // ─── Roteador de Mensagens ────────────────────────────────────────────────

    private function processMessage(User $user, string $waId, string $text): void
    {
        $sessionKey = "wa_bot_session_{$waId}";
        $session    = Cache::get($sessionKey, []);

        // Comandos globais sempre têm prioridade
        if (in_array($text, ['menu', 'oi', 'olá', 'ola', 'inicio', 'início', '0', 'voltar', 'cancelar'])) {
            Cache::forget($sessionKey);
            $this->sendMenu($user, $waId);
            return;
        }

        // Processar sessão multi-passo ativa
        if (!empty($session['step'])) {
            $this->processStep($user, $waId, $text, $session, $sessionKey);
            return;
        }

        // Comandos de menu principal
        match ($text) {
            '1'     => $this->showBalance($user, $waId),
            '2'     => $this->showMyTasks($user, $waId),
            '3'     => $this->startAction3($user, $waId, $sessionKey),
            '4'     => $this->startAction4($user, $waId, $sessionKey),
            '5'     => $this->showHelp($user, $waId),
            default => $this->send($waId, "❓ Comando não reconhecido.\n\nDigite *menu* para ver as opções.")
        };
    }

    // ─── Menu Personalizado por Role ──────────────────────────────────────────

    private function sendMenu(User $user, string $waId): void
    {
        $firstName = explode(' ', $user->name)[0];
        $role      = $user->role;

        $header = "👋 Olá, *{$firstName}*! Bem-vindo ao *Vivensi Bot*.\n\n";

        if (in_array($role, ['ngo', 'super_admin'])) {
            $options = "1️⃣ Ver saldo financeiro\n"
                . "2️⃣ Minhas tarefas\n"
                . "3️⃣ Registrar atendimento\n"
                . "4️⃣ Consultar beneficiário\n"
                . "5️⃣ Ajuda\n\n"
                . "_Responda com o número da opção._";
            $this->send($waId, $header . "📋 *MENU — TERCEIRO SETOR*\n\n" . $options);

        } elseif ($role === 'manager') {
            $options = "1️⃣ Ver saldo financeiro\n"
                . "2️⃣ Minhas tarefas\n"
                . "3️⃣ Concluir tarefa\n"
                . "4️⃣ Lançar despesa\n"
                . "5️⃣ Ajuda\n\n"
                . "_Responda com o número da opção._";
            $this->send($waId, $header . "📋 *MENU — GESTOR DE PROJETOS*\n\n" . $options);

        } elseif ($role === 'employee') {
            $options = "1️⃣ Ver meu saldo pessoal\n"
                . "2️⃣ Minhas tarefas\n"
                . "3️⃣ Concluir tarefa\n"
                . "4️⃣ Lançar despesa (aguarda aprovação)\n"
                . "5️⃣ Ajuda\n\n"
                . "_Responda com o número da opção._";
            $this->send($waId, $header . "📋 *MENU — COLABORADOR*\n\n" . $options);

        } else {
            // Pessoa comum (personal finance)
            $options = "1️⃣ Ver meu saldo pessoal\n"
                . "2️⃣ Minhas tarefas\n"
                . "3️⃣ Lançar receita\n"
                . "4️⃣ Lançar despesa\n"
                . "5️⃣ Ajuda\n\n"
                . "_Responda com o número da opção._";
            $this->send($waId, $header . "📋 *MENU — FINANÇAS PESSOAIS*\n\n" . $options);
        }
    }

    // ─── Ação 1: Saldo Financeiro ─────────────────────────────────────────────

    private function showBalance(User $user, string $waId): void
    {
        $tenantId = $user->tenant_id;
        $month    = now()->month;
        $year     = now()->year;
        $monthName = ucfirst(now()->translatedFormat('F/Y'));

        $income = (float) DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'income')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');

        $expense = (float) DB::table('transactions')
            ->where('tenant_id', $tenantId)
            ->where('type', 'expense')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->sum('amount');

        $balance      = $income - $expense;
        $balanceEmoji = $balance >= 0 ? '🟢' : '🔴';

        $msg = "💰 *RESUMO — {$monthName}*\n\n"
            . "📈 Entradas: R$ " . number_format($income, 2, ',', '.') . "\n"
            . "📉 Saídas: R$ " . number_format($expense, 2, ',', '.') . "\n"
            . "{$balanceEmoji} Saldo: *R$ " . number_format($balance, 2, ',', '.') . "*\n\n"
            . "_Digite *menu* para voltar._";

        $this->send($waId, $msg);
    }

    // ─── Ação 2: Tarefas ──────────────────────────────────────────────────────

    private function showMyTasks(User $user, string $waId): void
    {
        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'completed'])
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        if ($tasks->isEmpty()) {
            $this->send($waId, "✅ *Nenhuma tarefa pendente!*\n\nTudo em dia. 🎉\n\n_Digite *menu* para voltar._");
            return;
        }

        $msg = "📋 *SUAS TAREFAS PENDENTES*\n\n";
        foreach ($tasks as $i => $task) {
            $due = $task->due_date ? "📅 " . $task->due_date->format('d/m') : "Sem prazo";
            $emoji = match ($task->priority ?? 'medium') {
                'critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', default => '⚪'
            };
            $num = $i + 1;
            $msg .= "{$emoji} *{$num}.* {$task->title}\n   {$due}\n\n";
        }
        $msg .= "_Digite *menu* para voltar._";

        $this->send($waId, $msg);
    }

    // ─── Ação 3 (por role) ────────────────────────────────────────────────────

    private function startAction3(User $user, string $waId, string $sessionKey): void
    {
        $role = $user->role;

        if (in_array($role, ['ngo', 'super_admin'])) {
            // Registrar Atendimento
            $this->send($waId,
                "📝 *REGISTRAR ATENDIMENTO*\n\n"
                . "Envie os dados no formato:\n\n"
                . "*ATEND: [Nome do Beneficiário] | [Tipo] | [Descrição]*\n\n"
                . "_Exemplo:_\n"
                . "ATEND: Maria Silva | Consulta Social | Visita domiciliar realizada\n\n"
                . "_Digite *cancelar* para voltar._"
            );
            Cache::put($sessionKey, ['step' => 'awaiting_attendance'], now()->addMinutes(5));

        } elseif (in_array($role, ['manager', 'employee'])) {
            // Concluir Tarefa
            $this->startCompleteTask($user, $waId, $sessionKey);

        } else {
            // Pessoa comum: Lançar receita
            $this->send($waId,
                "💵 *LANÇAR RECEITA*\n\n"
                . "Envie no formato:\n\n"
                . "*RECV: [Valor] | [Descrição]*\n\n"
                . "_Exemplo:_\n"
                . "RECV: 3500,00 | Salário de abril\n\n"
                . "_Digite *cancelar* para voltar._"
            );
            Cache::put($sessionKey, ['step' => 'awaiting_income'], now()->addMinutes(5));
        }
    }

    // ─── Ação 4 (por role) ────────────────────────────────────────────────────

    private function startAction4(User $user, string $waId, string $sessionKey): void
    {
        $role = $user->role;

        if (in_array($role, ['ngo', 'super_admin'])) {
            // Consultar Beneficiário
            $this->send($waId,
                "🔍 *CONSULTAR BENEFICIÁRIO*\n\n"
                . "Envie o nome ou CPF:\n\n"
                . "*BENEF: [Nome ou CPF]*\n\n"
                . "_Exemplo:_\n"
                . "BENEF: Maria Silva\n\n"
                . "_Digite *cancelar* para voltar._"
            );
            Cache::put($sessionKey, ['step' => 'awaiting_beneficiary_search'], now()->addMinutes(5));

        } else {
            // Lançar Despesa (todos os outros roles)
            $needsApproval = ($role === 'employee');
            $warning = $needsApproval
                ? "\n\n⚠️ _Sua despesa precisará de aprovação do gestor._"
                : "";

            $this->send($waId,
                "💸 *LANÇAR DESPESA*\n\n"
                . "Envie no formato:\n\n"
                . "*DESP: [Valor] | [Descrição]*\n\n"
                . "_Exemplo:_\n"
                . "DESP: 250,00 | Material de escritório{$warning}\n\n"
                . "_Digite *cancelar* para voltar._"
            );
            Cache::put($sessionKey, ['step' => 'awaiting_expense'], now()->addMinutes(5));
        }
    }

    // ─── Processador de Sessões Multi-passo ───────────────────────────────────

    private function processStep(User $user, string $waId, string $text, array $session, string $sessionKey): void
    {
        $step = $session['step'];

        switch ($step) {
            case 'awaiting_attendance':
                $this->processAttendance($user, $waId, $text, $sessionKey);
                break;

            case 'awaiting_beneficiary_search':
                $this->processBeneficiarySearch($user, $waId, $text, $sessionKey);
                break;

            case 'awaiting_expense':
                $this->processExpense($user, $waId, $text, $sessionKey, 'expense');
                break;

            case 'awaiting_income':
                $this->processExpense($user, $waId, $text, $sessionKey, 'income');
                break;

            case 'awaiting_task_number':
                $this->processCompleteTask($user, $waId, $text, $session, $sessionKey);
                break;

            default:
                Cache::forget($sessionKey);
                $this->sendMenu($user, $waId);
        }
    }

    // ─── Handlers de Inserção ─────────────────────────────────────────────────

    private function processAttendance(User $user, string $waId, string $text, string $sessionKey): void
    {
        if (!str_starts_with(strtolower($text), 'atend:')) {
            $this->send($waId, "⚠️ Formato incorreto. Envie:\n\n*ATEND: [Nome] | [Tipo] | [Descrição]*\n\nOu *cancelar*.");
            return;
        }

        $parts = explode('|', substr($text, 6), 3);

        if (count($parts) < 2) {
            $this->send($waId, "⚠️ Dados incompletos. Informe pelo menos Nome e Tipo.\n\nOu *cancelar*.");
            return;
        }

        $benefName = trim($parts[0]);
        $type      = trim($parts[1]);
        $desc      = trim($parts[2] ?? '');

        // Buscar beneficiário pelo nome
        $beneficiary = Beneficiary::withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->where('name', 'like', "%{$benefName}%")
            ->first();

        if (!$beneficiary) {
            $this->send($waId, "❌ Beneficiário *\"{$benefName}\"* não encontrado.\n\nVerifique o nome e tente novamente.\n\nOu *cancelar*.");
            return;
        }

        Attendance::create([
            'tenant_id'      => $user->tenant_id,
            'beneficiary_id' => $beneficiary->id,
            'user_id'        => $user->id,
            'date'           => now()->toDateString(),
            'type'           => $type,
            'description'    => $desc ?: "Registrado via WhatsApp Bot",
        ]);

        Cache::forget($sessionKey);

        $this->send($waId,
            "✅ *Atendimento registrado com sucesso!*\n\n"
            . "👤 Beneficiário: {$beneficiary->name}\n"
            . "📋 Tipo: {$type}\n"
            . "📅 Data: " . now()->format('d/m/Y') . "\n\n"
            . "_Digite *menu* para voltar._"
        );
    }

    private function processExpense(User $user, string $waId, string $text, string $sessionKey, string $type): void
    {
        $prefix = $type === 'expense' ? 'desp:' : 'recv:';

        if (!str_starts_with(strtolower($text), $prefix)) {
            $fmt = $type === 'expense' ? '*DESP: [Valor] | [Descrição]*' : '*RECV: [Valor] | [Descrição]*';
            $this->send($waId, "⚠️ Formato incorreto. Envie:\n\n{$fmt}\n\nOu *cancelar*.");
            return;
        }

        $parts = explode('|', substr($text, strlen($prefix)), 2);

        if (count($parts) < 2) {
            $this->send($waId, "⚠️ Informe valor e descrição separados por | \n\nOu *cancelar*.");
            return;
        }

        // Sanitizar valor (suporte a R$1.000,00 e 1000.00)
        $amountRaw = trim($parts[0]);
        $amountRaw = preg_replace('/[^\d,\.]/', '', $amountRaw);
        $amountRaw = str_replace('.', '', $amountRaw); // Remove separador de milhar
        $amountRaw = str_replace(',', '.', $amountRaw); // Vírgula → ponto decimal
        $amount    = (float) $amountRaw;

        if ($amount <= 0) {
            $this->send($waId, "⚠️ Valor inválido. Informe um valor positivo.\n\nOu *cancelar*.");
            return;
        }

        $description = trim($parts[1]);
        $isEmployee  = $user->role === 'employee';
        $status      = $isEmployee ? 'pending' : 'paid';
        $approvalStatus = $isEmployee ? 'pending' : 'approved';

        $transaction = new Transaction();
        $transaction->tenant_id      = $user->tenant_id;
        $transaction->description    = $description;
        $transaction->amount         = $amount;
        $transaction->type           = $type;
        $transaction->date           = now()->toDateString();
        $transaction->status         = $status;
        $transaction->approval_status = $approvalStatus;
        $transaction->save();

        Cache::forget($sessionKey);

        $typeLabel = $type === 'expense' ? 'Despesa' : 'Receita';
        $approvalNote = $isEmployee ? "\n\n⏳ _Aguardando aprovação do gestor._" : "";

        $this->send($waId,
            "✅ *{$typeLabel} registrada com sucesso!*\n\n"
            . "💰 Valor: R$ " . number_format($amount, 2, ',', '.') . "\n"
            . "📝 Descrição: {$description}\n"
            . "📅 Data: " . now()->format('d/m/Y')
            . $approvalNote . "\n\n"
            . "_Digite *menu* para voltar._"
        );
    }

    private function processBeneficiarySearch(User $user, string $waId, string $text, string $sessionKey): void
    {
        if (!str_starts_with(strtolower($text), 'benef:')) {
            $this->send($waId, "⚠️ Formato incorreto. Envie:\n\n*BENEF: [Nome ou CPF]*\n\nOu *cancelar*.");
            return;
        }

        $query = trim(substr($text, 6));

        $results = Beneficiary::withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('cpf', 'like', "%{$query}%");
            })
            ->limit(3)
            ->get();

        Cache::forget($sessionKey);

        if ($results->isEmpty()) {
            $this->send($waId, "❌ Nenhum beneficiário encontrado para *\"{$query}\"*.\n\n_Digite *menu* para voltar._");
            return;
        }

        $msg = "🔍 *RESULTADO DA BUSCA*\n\n";
        foreach ($results as $b) {
            $status = match ($b->status) {
                'active' => '🟢 Ativo', 'inactive' => '🔴 Inativo', 'graduated' => '🎓 Graduado', default => $b->status
            };
            $attCount = Attendance::withoutGlobalScopes()
                ->where('beneficiary_id', $b->id)
                ->whereMonth('date', now()->month)
                ->count();

            $msg .= "👤 *{$b->name}*\n"
                . "   Status: {$status}\n"
                . "   Atendimentos (mês): {$attCount}\n\n";
        }
        $msg .= "_Digite *menu* para voltar._";

        $this->send($waId, $msg);
    }

    private function startCompleteTask(User $user, string $waId, string $sessionKey): void
    {
        $tasks = Task::withoutGlobalScopes()
            ->where('tenant_id', $user->tenant_id)
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'completed'])
            ->limit(5)
            ->get();

        if ($tasks->isEmpty()) {
            $this->send($waId, "✅ *Nenhuma tarefa pendente para concluir!*\n\n_Digite *menu* para voltar._");
            return;
        }

        $msg = "✅ *CONCLUIR TAREFA*\n\nQual tarefa deseja marcar como concluída?\n\n";
        $taskMap = [];
        foreach ($tasks as $i => $task) {
            $num = $i + 1;
            $msg .= "{$num}. {$task->title}\n";
            $taskMap[$num] = $task->id;
        }
        $msg .= "\n_Responda com o número ou *cancelar*._";

        Cache::put($sessionKey, ['step' => 'awaiting_task_number', 'task_map' => $taskMap], now()->addMinutes(5));
        $this->send($waId, $msg);
    }

    private function processCompleteTask(User $user, string $waId, string $text, array $session, string $sessionKey): void
    {
        $num = (int) $text;
        $taskMap = $session['task_map'] ?? [];

        if (!isset($taskMap[$num])) {
            $this->send($waId, "⚠️ Número inválido. Escolha uma das opções listadas.\n\nOu *cancelar*.");
            return;
        }

        $task = Task::withoutGlobalScopes()
            ->where('id', $taskMap[$num])
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (!$task) {
            Cache::forget($sessionKey);
            $this->send($waId, "❌ Tarefa não encontrada.\n\n_Digite *menu* para voltar._");
            return;
        }

        $task->status = 'done';
        $task->save();

        Cache::forget($sessionKey);

        $this->send($waId,
            "✅ *Tarefa concluída com sucesso!*\n\n"
            . "📋 {$task->title}\n"
            . "📅 " . now()->format('d/m/Y H:i') . "\n\n"
            . "_Digite *menu* para voltar._"
        );
    }

    // ─── Ajuda ────────────────────────────────────────────────────────────────

    private function showHelp(User $user, string $waId): void
    {
        $this->send($waId,
            "ℹ️ *AJUDA — VIVENSI BOT*\n\n"
            . "📌 Comandos disponíveis a qualquer momento:\n\n"
            . "• *menu* — Voltar ao menu principal\n"
            . "• *cancelar* — Cancelar operação atual\n\n"
            . "📱 *Inserção direta:*\n"
            . "• ATEND: Nome | Tipo | Desc\n"
            . "• DESP: 100,00 | Descrição\n"
            . "• RECV: 100,00 | Descrição\n"
            . "• BENEF: Nome ou CPF\n\n"
            . "_Acesse o painel em vivensi.app.br_"
        );
    }

    // ─── Utilidades ───────────────────────────────────────────────────────────

    private function findUserByPhone(string $phone): ?User
    {
        // Tenta match exato primeiro, depois pelos últimos 8 dígitos
        return User::withoutGlobalScopes()
            ->where(function ($q) use ($phone) {
                $q->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$phone])
                  ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%' . substr($phone, -8)]);
            })
            ->where('status', 'active')
            ->first();
    }

    private function send(string $waId, string $text): void
    {
        try {
            $phone = preg_replace('/\D/', '', explode('@', $waId)[0]);
            $evo   = new EvolutionApiService();

            // Acessa a propriedade via reflection ou usa um método público
            // O EvolutionApiService precisa de uma instância configurada
            $config = \App\Models\WhatsappConfig::withoutGlobalScopes()
                ->whereNotNull('evolution_instance_name')
                ->where('ai_enabled', true)
                ->first();

            if ($config) {
                $evo = new EvolutionApiService($config);
                $evo->sendMessage($phone, $text);
            } else {
                Log::warning("WhatsApp Bot: Nenhuma instância configurada para enviar mensagem a {$phone}");
            }
        } catch (\Throwable $e) {
            Log::error("WhatsApp Bot: Falha ao enviar para {$waId}: " . $e->getMessage());
        }
    }

    private function extractWaId(array $data): ?string
    {
        // Evolution API v2 formato
        return $data['data']['key']['remoteJid']
            ?? $data['data']['message']['key']['remoteJid']
            ?? $data['key']['remoteJid']
            ?? null;
    }

    private function extractText(array $data): ?string
    {
        return $data['data']['message']['conversation']
            ?? $data['data']['message']['extendedTextMessage']['text']
            ?? $data['message']['conversation']
            ?? $data['message']['extendedTextMessage']['text']
            ?? null;
    }
}
