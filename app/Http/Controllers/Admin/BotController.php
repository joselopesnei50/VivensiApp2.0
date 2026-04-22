<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
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

    public function index()
    {
        // Ler configurações atuais
        $settings = [];
        foreach (self::BOT_KEYS as $key) {
            $settings[$key] = SystemSetting::getValue($key, self::DEFAULTS[$key] ?? null);
        }

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
                $instanceStatus = ['error' => $e->getMessage()];
            }
        }

        $webhookUrl = rtrim(config('app.url'), '/') . '/api/whatsapp/bot';

        return view('admin.bot.index', compact('settings', 'users', 'instanceStatus', 'webhookUrl'));
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

        foreach ($textFields as $field) {
            if ($request->filled($field)) {
                $value = $field === 'bot_phone'
                    ? preg_replace('/\D/', '', $request->input($field))
                    : $request->input($field);
                SystemSetting::setValue($field, $value, 'bot');
            }
        }

        return back()->with('success', '✅ Configurações do bot salvas com sucesso!');
    }

    public function updateUserPhone(Request $request, int $id)
    {
        $request->validate(['phone' => 'nullable|string|max:20']);

        $user = User::withoutGlobalScopes()->findOrFail($id);

        $phone = $request->filled('phone')
            ? preg_replace('/\D/', '', $request->input('phone'))
            : null;

        $user->phone = $phone;
        $user->save();

        return back()->with('success', "✅ Telefone de {$user->name} atualizado.");
    }

    public function createInstance(Request $request)
    {
        $request->validate(['instance_name' => 'required|string|max:100']);

        $instanceName = $request->input('instance_name');
        $botWebhook   = rtrim(config('app.url'), '/') . '/api/whatsapp/bot';

        try {
            // Chamar diretamente a Evolution API com o webhook do bot
            $baseUrl      = rtrim(config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL')), '/');
            $globalApiKey = config('whatsapp.evolution_global_key');

            $payload = [
                'instanceName' => $instanceName,
                'qrcode'       => true,
                'integration'  => 'WHATSAPP-BAILEYS',
                'rejectCall'   => false,
                'groupsIgnore' => true,
                'alwaysOnline' => true,
                'readMessages' => true,
                'readStatus'   => true,
                'webhook'      => [
                    'enabled'  => true,
                    'url'      => $botWebhook,
                    'byEvents' => false,
                    'base64'   => false,
                    'events'   => ['messages.upsert', 'connection.update', 'qrcode.updated'],
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['apikey' => $globalApiKey])
                ->post("{$baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                // Salvar o nome da instância no SystemSetting
                SystemSetting::setValue('bot_instance_name', $instanceName, 'bot');

                return response()->json([
                    'success' => true,
                    'message' => "Instância '{$instanceName}' criada! Webhook configurado para: {$botWebhook}",
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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
            return response()->json(['error' => $e->getMessage()], 500);
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
