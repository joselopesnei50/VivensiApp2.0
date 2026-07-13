<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WhatsappConfig;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    /** Chaves de configuração do bot salvas no SystemSetting */
    private const BOT_KEYS = [
        'bot_enabled',
        'bot_phone',
        'bot_instance_name',
        'bot_msg_welcome_ngo',
        'bot_msg_welcome_manager',
        'bot_msg_welcome_employee',
        'bot_msg_welcome_common',
        'bot_msg_help',
    ];

    /** Mensagens padrão caso ainda não estejam configuradas */
    private const DEFAULTS = [
        'bot_msg_welcome_ngo'      => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Registrar atendimento\n4️⃣ Consultar beneficiário\n5️⃣ Ajuda",
        'bot_msg_welcome_manager'  => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Lançar despesa\n5️⃣ Ajuda",
        'bot_msg_welcome_employee' => "1️⃣ Ver saldo financeiro\n2️⃣ Minhas tarefas\n3️⃣ Concluir tarefa\n4️⃣ Lançar despesa (aguarda aprovação)\n5️⃣ Ajuda",
        'bot_msg_welcome_common'   => "1️⃣ Ver meu saldo pessoal\n2️⃣ Minhas tarefas\n3️⃣ Lançar receita\n4️⃣ Lançar despesa\n5️⃣ Ajuda",
        'bot_msg_help'             => "• *menu* — Voltar ao menu principal\n• *cancelar* — Cancelar operação\n• ATEND: Nome | Tipo | Desc\n• DESP: 100,00 | Descrição\n• RECV: 100,00 | Descrição\n• BENEF: Nome ou CPF",
    ];

    /** Chaves do Bot de Atendimento (contatos externos) */
    private const ATEND_KEYS = [
        'atend_enabled', 'atend_welcome_msg', 'atend_off_hours_msg',
        'atend_work_start', 'atend_work_end', 'atend_faq',
    ];

    private const ATEND_DEFAULTS = [
        'atend_enabled'      => '0',
        'atend_welcome_msg'  => "Olá! 👋 Seja bem-vindo(a). Como posso ajudar?\n\nDigite sua dúvida ou escolha uma opção:",
        'atend_off_hours_msg'=> "Olá! Nosso atendimento funciona de segunda a sexta, das 08h às 18h. Retornaremos em breve! 🙏",
        'atend_work_start'   => '08:00',
        'atend_work_end'     => '18:00',
        'atend_faq'          => '[]',
    ];

    public function index()
    {
        // Ler configurações atuais
        $settings = [];
        foreach (self::BOT_KEYS as $key) {
            $settings[$key] = SystemSetting::getValue($key, self::DEFAULTS[$key] ?? null);
        }

        // Bot de Atendimento: configurações
        $atendSettings = [];
        foreach (self::ATEND_KEYS as $key) {
            $atendSettings[$key] = SystemSetting::getValue($key, self::ATEND_DEFAULTS[$key] ?? null);
        }
        $atendFaq = json_decode($atendSettings['atend_faq'] ?? '[]', true) ?: [];

        // WhatsappConfig do tenant do super_admin (para ai_enabled, ai_training, ai_provider)
        $tenantId    = auth()->user()->tenant_id;
        $waConfig    = WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();

        // Usuários do sistema com seus telefones
        $users = User::withoutGlobalScopes()
            ->whereNotNull('tenant_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'phone', 'status']);

        // Status da instância na Evolution API
        $instanceStatus = null;
        $instanceName   = $settings['bot_instance_name'] ?? '';

        if ($instanceName) {
            try {
                $evo            = $this->makeEvolutionService($instanceName);
                $instanceStatus = $evo->getConnectionState();
            } catch (\Throwable $e) {
                Log::error('BotController: getConnectionState failed', ['error' => $e->getMessage()]);
                $instanceStatus = ['error' => 'Falha ao obter status da instância.'];
            }
        }

        $webhookUrl = rtrim(config('app.url'), '/') . '/api/whatsapp/bot';
        $activeTab  = request()->get('tab', 'config');

        return view('admin.bot.index', compact(
            'settings', 'users', 'instanceStatus', 'webhookUrl',
            'atendSettings', 'atendFaq', 'waConfig', 'activeTab'
        ));
    }

    public function save(Request $request)
    {
        $request->validate([
            'bot_phone'                => 'nullable|string|max:20',
            'bot_instance_name'        => 'nullable|string|max:100',
            'bot_enabled'              => 'nullable|boolean',
            'bot_msg_welcome_ngo'      => 'nullable|string|max:2000',
            'bot_msg_welcome_manager'  => 'nullable|string|max:2000',
            'bot_msg_welcome_employee' => 'nullable|string|max:2000',
            'bot_msg_welcome_common'   => 'nullable|string|max:2000',
            'bot_msg_help'             => 'nullable|string|max:2000',
        ]);

        SystemSetting::setValue('bot_enabled', $request->has('bot_enabled') ? '1' : '0', 'bot');

        $textFields = ['bot_phone', 'bot_instance_name', 'bot_msg_welcome_ngo',
                       'bot_msg_welcome_manager', 'bot_msg_welcome_employee',
                       'bot_msg_welcome_common', 'bot_msg_help'];

        $changed = [];
        foreach ($textFields as $field) {
            if ($request->filled($field)) {
                $value = $field === 'bot_phone'
                    ? preg_replace('/\D/', '', $request->input($field))
                    : $request->input($field);
                SystemSetting::setValue($field, $value, 'bot');
                $changed[] = $field;
            }
        }

        \App\Models\AdminAuditLog::record('bot.config_updated', [
            'target_type'   => 'system_setting',
            'target_name'   => 'bot',
            // Chaves alteradas, nunca valores — mensagens do bot podem conter dados sensiveis.
            'fields_changed' => array_values(array_unique(array_merge(['bot_enabled'], $changed))),
        ]);

        return back()->with('success', '✅ Configurações do bot salvas com sucesso!');
    }

    public function updateUserPhone(Request $request, int $id)
    {
        $request->validate(['phone' => 'nullable|string|max:20']);

        $user = User::withoutGlobalScopes()->findOrFail($id);
        $previousPhone = $user->phone;

        $phone = $request->filled('phone')
            ? preg_replace('/\D/', '', $request->input('phone'))
            : null;

        $user->phone = $phone;
        $user->save();

        \App\Models\AdminAuditLog::record('bot.user_phone_updated', [
            'target_type'   => 'user',
            'target_id'     => $user->id,
            'target_name'   => $user->name,
            'had_phone'     => $previousPhone !== null,
            'has_phone_now' => $phone !== null,
        ]);

        return back()->with('success', "✅ Telefone de {$user->name} atualizado.");
    }

    public function createInstance(Request $request)
    {
        $request->validate(['instance_name' => 'required|string|max:100']);

        $instanceName = $request->input('instance_name');
        $baseUrl      = rtrim(config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL')), '/');
        $globalApiKey = config('whatsapp.evolution_global_key');

        // Inclui o token na URL para que a Evolution API o envie como query string
        $botSecret  = config('services.whatsapp.bot_secret');
        $botWebhook = rtrim(config('app.url'), '/') . '/api/whatsapp/bot'
            . ($botSecret ? '?bot_token=' . urlencode($botSecret) : '');

        try {
            // 1. Verifica se a instância já existe na Evolution API
            $stateResponse = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders(['apikey' => $globalApiKey])
                ->get("{$baseUrl}/instance/connectionState/{$instanceName}");

            if ($stateResponse->successful()) {
                // Instância já existe — atualiza o webhook e salva o nome
                SystemSetting::setValue('bot_instance_name', $instanceName, 'bot');

                // Garante que o webhook aponta para este sistema
                \Illuminate\Support\Facades\Http::timeout(10)
                    ->withHeaders(['apikey' => $globalApiKey])
                    ->post("{$baseUrl}/webhook/set/{$instanceName}", [
                        'webhook' => [
                            'enabled'  => true,
                            'url'      => $botWebhook,
                            'byEvents' => false,
                            'base64'   => false,
                            'events'   => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE', 'QRCODE_UPDATED'],
                        ],
                    ]);

                Log::info('Bot Admin: instância já existia, webhook atualizado', [
                    'instance' => $instanceName,
                    'webhook'  => $botWebhook,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Instância '{$instanceName}' já existe e foi vinculada. Webhook atualizado para: {$botWebhook}",
                    'data'    => $stateResponse->json(),
                ]);
            }

            // 2. Instância não existe — criar com payload mínimo (sem qrcode, sem settings extras)
            // Evolution API v2.3.6: campos extras causam TypeError durante criação.
            $payload = [
                'instanceName' => $instanceName,
                'integration'  => 'WHATSAPP-BAILEYS',
                'webhook'      => [
                    'enabled'  => true,
                    'url'      => $botWebhook,
                    'byEvents' => false,
                    'base64'   => false,
                    'events'   => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE', 'QRCODE_UPDATED'],
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['apikey' => $globalApiKey])
                ->post("{$baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                SystemSetting::setValue('bot_instance_name', $instanceName, 'bot');

                Log::info('Bot Admin: instância criada', ['instance' => $instanceName, 'webhook' => $botWebhook]);

                return response()->json([
                    'success' => true,
                    'message' => "Instância '{$instanceName}' criada com sucesso! Webhook: {$botWebhook}",
                    'data'    => $response->json(),
                ]);
            }

            Log::error('Bot Admin: Evolution API recusou criação', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Evolution API retornou erro {$response->status()}: " . $response->body(),
            ], 500);

        } catch (\Throwable $e) {
            Log::error('Bot Admin: createInstance failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Falha ao criar instância. Tente novamente.'], 500);
        }
    }

    public function getQrCode(Request $request)
    {
        $instanceName = $request->query('instance', SystemSetting::getValue('bot_instance_name'));

        if (!$instanceName) {
            return response()->json(['error' => 'Instância não configurada'], 400);
        }

        try {
            $evo    = $this->makeEvolutionService($instanceName);
            $result = $evo->fetchConnectionCode($instanceName);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('BotController: fetchConnectionCode failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao obter QR Code. Tente novamente.'], 500);
        }
    }

    public function getInstanceStatus(Request $request)
    {
        $instanceName = $request->query('instance', SystemSetting::getValue('bot_instance_name'));

        if (!$instanceName) {
            return response()->json(['state' => 'not_configured']);
        }

        try {
            $evo    = $this->makeEvolutionService($instanceName);
            $result = $evo->getConnectionState();
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('BotController: getInstanceStatus failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao obter status. Tente novamente.'], 500);
        }
    }

    public function saveAtendimento(Request $request)
    {
        $request->validate([
            'atend_welcome_msg'  => 'nullable|string|max:2000',
            'atend_off_hours_msg'=> 'nullable|string|max:2000',
            'atend_work_start'   => 'nullable|string|max:5',
            'atend_work_end'     => 'nullable|string|max:5',
            'faq_keyword'        => 'nullable|array',
            'faq_keyword.*'      => 'nullable|string|max:100',
            'faq_response'       => 'nullable|array',
            'faq_response.*'     => 'nullable|string|max:1000',
            'ai_enabled'         => 'nullable|boolean',
            'ai_provider'        => 'nullable|in:deepseek,gemini',
            'ai_training'        => 'nullable|string|max:5000',
        ]);

        // Toggle
        SystemSetting::setValue('atend_enabled', $request->has('atend_enabled') ? '1' : '0', 'attendance_bot');

        // Text fields
        foreach (['atend_welcome_msg', 'atend_off_hours_msg', 'atend_work_start', 'atend_work_end'] as $field) {
            if ($request->filled($field)) {
                SystemSetting::setValue($field, $request->input($field), 'attendance_bot');
            }
        }

        // FAQ: zip keywords + responses
        $keywords  = $request->input('faq_keyword', []);
        $responses = $request->input('faq_response', []);
        $faq = [];
        foreach ($keywords as $i => $keyword) {
            $keyword  = trim($keyword ?? '');
            $response = trim($responses[$i] ?? '');
            if ($keyword && $response) {
                $faq[] = ['keyword' => $keyword, 'response' => $response];
            }
        }
        SystemSetting::setValue('atend_faq', json_encode($faq, JSON_UNESCAPED_UNICODE), 'attendance_bot');

        // WhatsappConfig AI settings for this tenant
        $tenantId = auth()->user()->tenant_id;
        $waConfig = WhatsappConfig::withoutGlobalScopes()->where('tenant_id', $tenantId)->first();
        if ($waConfig) {
            $waConfig->update([
                'ai_enabled'  => $request->has('ai_enabled'),
                'ai_provider' => $request->input('ai_provider', $waConfig->ai_provider ?? 'deepseek'),
                'ai_training' => $request->input('ai_training', $waConfig->ai_training),
            ]);
        }

        \App\Models\AdminAuditLog::record('bot.attendance_updated', [
            'target_type'    => 'system_setting',
            'target_name'    => 'attendance_bot',
            'faq_entries'    => count($faq),
            'tenant_id'      => $tenantId,
            'ai_enabled'     => $request->has('ai_enabled'),
        ]);

        return back()->with('success', '✅ Configurações do Bot de Atendimento salvas!');
    }

    private function makeEvolutionService(string $instanceName): EvolutionApiService
    {
        // Cria um objeto anônimo para passar como contextModel ao service
        $context = new \stdClass();
        $context->evolution_instance_name  = $instanceName;
        $context->evolution_instance_token = null;
        return new EvolutionApiService($context);
    }
}
