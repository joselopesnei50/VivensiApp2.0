<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessWhatsappAiResponse;
use App\Models\WhatsappConfig;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Models\CannedResponse;
use App\Models\WhatsappNote;
use App\Models\WhatsappAuditLog;
use App\Services\EvolutionApiService;
use App\Services\WhatsappOutboundPolicy;
use App\Services\GeminiService;
use App\Services\DeepSeekService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function updateCompliance(Request $request, $chatId)
    {
        abort_unless(in_array(auth()->user()->role, ['manager', 'super_admin'], true), 403);

        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['opt_in', 'opt_out', 'block', 'unblock'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $validated['action'];
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($action === 'opt_in') {
            $chat->opt_in_at = now();
            $chat->opt_out_at = null;
            $chat->blocked_at = null;
            $chat->blocked_reason = null;
            $chat->save();
        } elseif ($action === 'opt_out') {
            $chat->opt_out_at = now();
            $chat->blocked_at = $chat->blocked_at ?: now();
            $chat->blocked_reason = $reason !== '' ? $reason : 'Manual opt-out';
            $chat->status = 'closed';
            $chat->save();
        } elseif ($action === 'block') {
            $chat->blocked_at = now();
            $chat->blocked_reason = $reason !== '' ? $reason : 'Manual block';
            $chat->status = 'closed';
            $chat->save();
        } elseif ($action === 'unblock') {
            $chat->blocked_at = null;
            $chat->blocked_reason = null;
            $chat->save();
        }

        WhatsappAuditLog::create([
            'tenant_id' => $tenantId,
            'chat_id' => $chat->id,
            'actor_user_id' => auth()->id(),
            'actor_type' => 'user',
            'event' => 'compliance_action',
            'details' => ['action' => $action, 'reason' => $reason],
        ]);

        return response()->json(['success' => true, 'chat' => $chat]);
    }

    /**
     * Webhook receptor da Evolution API (Async)
     * 
     * CRITICAL: Returns HTTP 200 immediately and dispatches payload to queue.
     * This prevents Evolution API from retrying due to timeout.
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        // Evolution API v2 envia o token na Query String ou no Header Client-Token
        $clientToken = $request->header('Client-Token') ?? $request->query('token');
        $config = null;
        
        if (!empty($clientToken)) {
            $config = WhatsappConfig::where('client_token_hash', hash('sha256', (string) $clientToken))->first();
            // Fallback para rows sem hash
            if (!$config) {
                $config = WhatsappConfig::where('client_token', $clientToken)->first();
            }
        }

        // Fallback: busca pela instance_name no payload (quando tenant não tem client_token configurado)
        if (!$config) {
            $instanceName = $data['instance'] ?? null;
            if ($instanceName) {
                // Busca o tenant que tem essa instância e pega a config associada
                $tenant = \App\Models\Tenant::where('evolution_instance_name', $instanceName)->first()
                       ?? \App\Models\User::where('evolution_instance_name', $instanceName)->first();
                if ($tenant) {
                    $config = WhatsappConfig::where('tenant_id', $tenant->tenant_id ?? $tenant->id)->first();
                }
            }
        }

        if (!$config) {
            Log::warning('WhatsApp webhook: unauthorized or config not found', [
                'instance' => $data['instance'] ?? null,
                'token_present' => !empty($clientToken),
            ]);
            // Retorna 200 mesmo assim para evitar retries infinitos da Evolution API
            return response()->json(['status' => 'ignored'], 200);
        }

        // IMMEDIATELY dispatch to queue and return 200 OK
        \App\Jobs\ProcessWhatsappWebhook::dispatch((int) $config->id, $data)
            ->onQueue('whatsapp');

        return response()->json(['status' => 'queued'], 200);
    }

    private function handleReceivedMessage($config, $data)
    {
        $tenantId = $config->tenant_id;
        $waId = (string) ($data['phone'] ?? ''); // ex: 558199999999
        $content = (string) ($data['text']['message'] ?? '');
        $messageId = (string) ($data['messageId'] ?? '');

        // Basic validation
        if ($waId === '' || $messageId === '') {
            return;
        }

        // Idempotency: ignore duplicate webhook deliveries
        if (WhatsappMessage::where('message_id', $messageId)->exists()) {
            return;
        }

        // 1. Localizar ou criar conversa
        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $waId],
            [
                'contact_name' => $data['senderName'] ?? 'Cliente WhatsApp',
                'contact_phone' => $waId,
                'status' => 'open',
                // Inbound message implies opt-in for replies (best effort).
                'opt_in_at' => now(),
            ]
        );

        $chat->update(['last_message_at' => now(), 'last_inbound_at' => now()]);

        WhatsappAuditLog::create([
            'tenant_id' => $tenantId,
            'chat_id' => $chat->id,
            'actor_type' => 'webhook',
            'event' => 'webhook_inbound',
            'details' => [
                'message_id' => $messageId,
                'content_len' => mb_strlen($content),
            ],
        ]);

        // STOP / opt-out keywords (compliance). If the customer says stop, we must stop.
        $normalized = mb_strtolower(trim($content));
        $stopKeywords = [
            'stop', 'parar', 'pare', 'sair', 'cancelar', 'cancele', 'descadastrar', 'remover', 'não quero', 'nao quero'
        ];
        foreach ($stopKeywords as $kw) {
            if ($kw !== '' && str_contains($normalized, $kw)) {
                $chat->opt_out_at = now();
                $chat->blocked_at = now();
                $chat->blocked_reason = 'STOP keyword';
                $chat->status = 'closed';
                $chat->save();

                // Add to Global SaaS Blacklist
                \App\Models\WhatsappBlacklist::firstOrCreate(
                    ['tenant_id' => $tenantId, 'phone' => $waId],
                    ['reason' => "Opt-out via keyword: $kw"]
                );

                WhatsappAuditLog::create([
                    'tenant_id' => $tenantId,
                    'chat_id' => $chat->id,
                    'actor_type' => 'webhook',
                    'event' => 'compliance_action',
                    'details' => ['action' => 'opt_out', 'reason' => 'STOP keyword', 'keyword' => $kw],
                ]);
                return;
            }
        }

        // Ensure opt-in is set for inbound contacts (if older chats existed before this field)
        if (!$chat->opt_in_at) {
            $chat->opt_in_at = now();
            $chat->save();
        }

        // 2. Salvar mensagem no banco
        WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $messageId,
            'content' => $content,
            'direction' => 'inbound'
        ]);

        // 3. Se a IA estiver ativada e não houver atendente humano fixo, responder com IA
        if ($config->ai_enabled && (!$chat->assigned_to || $chat->status == 'open') && !$chat->opt_out_at && !$chat->blocked_at) {
            ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                ->onQueue('whatsapp');
        }
    }

    private function replyWithAi($config, $chat, $userMessage)
    {
        // "Treinamento" da IA vindo do banco
        $training = $config->ai_training ?? "Você é o Bruce AI, assistente virtual avançado da Vivensi. Seja gentil, profissional e altamente eficiente.";
        
        // Contexto extra: Conhecer o cliente se ele já existir no banco da Vivensi
        $tenant = Tenant::find($config->tenant_id);
        
        $prompt = "Contexto da Organização ({$tenant->name}): {$training}\n";
        $prompt .= "Instrução: Responda ao cliente de forma curta e objetiva. Se não souber a resposta, peça para ele aguardar um atendente humano.\n";
        $prompt .= "Usuário: {$userMessage}";

        // Tenta Gemini primeiro
        $ai = new GeminiService();
        $aiResponse = $ai->callGemini([['text' => $prompt]]);

        $replyText = "";
        if (isset($aiResponse['candidates'][0]['content']['parts'][0]['text'])) {
            $replyText = $aiResponse['candidates'][0]['content']['parts'][0]['text'];
        } else {
            $ds = new \App\Services\DeepSeekService();
            $dsRes = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $replyText = $dsRes['choices'][0]['message']['content'] ?? "Entendi. Vou encaminhar sua solicitação para um especialista. Aguarde um momento.";
        }

        Log::info("Bruce AI Response for Chat {$chat->id}: " . $replyText);

        // Salvar insight da IA como nota interna, se for relevante (opcional)
        // WhatsappNote::create(['chat_id' => $chat->id, 'content' => "IA Sugeriu: $replyText", 'type' => 'ai_insight']);

        // Enviar via Evolution API
        try {
            $evo = new EvolutionApiService($tenant);
            $res = $evo->sendMessage($chat->wa_id, $replyText, null, 2);
            
            if (isset($res['key']['id']) || isset($res['messageId'])) {
                WhatsappMessage::create([
                    'chat_id' => $chat->id,
                    'message_id' => $res['key']['id'] ?? $res['messageId'],
                    'content' => $replyText,
                    'direction' => 'outbound',
                    'type' => 'text'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Bruce AI Z-API Error: " . $e->getMessage());
        }
    }

    /**
     * Configuração do Chatbot (Painel do Cliente)
     */
    public function settings()
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $contextModel = $this->getContextModel();
        
        if (!$contextModel) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado às configurações de WhatsApp.');
        }

        $tenantId = auth()->user()->tenant_id;
        
        $config = WhatsappConfig::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'instance_id' => '',
                'token' => '',
                'ai_enabled' => false,
                'outbound_enabled' => true,
            ]
        );

        // Ensure client_token exists (webhook security)
        if (empty($config->client_token)) {
            $token = bin2hex(random_bytes(16));
            $config->update([
                'client_token' => $token,
                'client_token_hash' => hash('sha256', $token),
            ]);
        } elseif (empty($config->client_token_hash)) {
            $config->update([
                'client_token_hash' => hash('sha256', (string) $config->client_token),
            ]);
        }

        return view('whatsapp.settings', compact('config', 'contextModel'));
    }

    public function saveSettings(Request $request)
    {
        $contextModel = $this->getContextModel();
        $config = WhatsappConfig::where('tenant_id', auth()->user()->tenant_id)->first();
        
        $validated = $request->validate([
            'ai_training' => 'nullable|string|max:10000',
            'ai_enabled' => 'nullable|boolean',
            'evolution_instance_name' => 'required|string|max:255',
            'client_token' => 'nullable|string|max:255',
            'outbound_enabled' => 'nullable|boolean',
            'require_opt_in' => 'nullable|boolean',
            'max_outbound_per_minute' => 'nullable|integer|min:1|max:120',
            'min_outbound_delay_seconds' => 'nullable|integer|min:0|max:60',
            'enforce_24h_window' => 'nullable|boolean',
            'allow_templates_outside_window' => 'nullable|boolean',
        ]);

        $validated['ai_enabled'] = $request->boolean('ai_enabled');
        $validated['outbound_enabled'] = $request->boolean('outbound_enabled');
        $validated['require_opt_in'] = $request->boolean('require_opt_in');
        $validated['enforce_24h_window'] = $request->boolean('enforce_24h_window');
        $validated['allow_templates_outside_window'] = $request->boolean('allow_templates_outside_window');

        if (!empty($validated['client_token'])) {
            $validated['client_token_hash'] = hash('sha256', (string) $validated['client_token']);
        } else {
            unset($validated['client_token']);
        }

        // Handle Evolution API Instance Creation/Update
        $instanceName = Str::slug($validated['evolution_instance_name']);
        
        if ($contextModel->evolution_instance_name !== $instanceName) {
            $evo = new EvolutionApiService();
            $clientToken = $validated['client_token'] ?? $config->client_token;
            $result = $evo->createInstance($instanceName, $clientToken);
            
            if (isset($result['generated_token'])) {
                $contextModel->evolution_instance_name = $instanceName;
                $contextModel->evolution_instance_token = $result['generated_token'];
                $contextModel->save();
            } else {
                return back()->with('error', 'Erro ao criar instância na Evolution API. Tente outro nome.');
            }
        }

        unset($validated['evolution_instance_name']);
        $config->update($validated);

        // Sempre (re)configura o webhook na Evolution API para garantir recebimento de mensagens
        try {
            $evoForWebhook = new EvolutionApiService();
            $tokenForWebhook = $config->client_token ?? null;
            $evoForWebhook->setWebhook(
                $contextModel->evolution_instance_name,
                $tokenForWebhook
            );
        } catch (\Exception $e) {
            Log::warning('Webhook config failed after settings save', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Configurações de WhatsApp/IA atualizadas! Webhook configurado automaticamente.');
    }


    /**
     * Interface de Chat (Mini CRM)
     */
    public function chatIndex()
    {
        $chats = WhatsappChat::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('last_message_at', 'desc')
                             ->get();
        return view('whatsapp.chat', compact('chats'));
    }

    public function getChatMessages(Request $request, $chatId)
    {
        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);
        
        $query = WhatsappMessage::where('chat_id', $chat->id)->orderBy('created_at', 'asc');

        // Incremental polling: only return messages after given ID
        if ($request->filled('after') && is_numeric($request->query('after'))) {
            $query->where('id', '>', (int) $request->query('after'));
        }

        $messages = $query->get();

        $notes = WhatsappNote::where('chat_id', $chat->id)
                             ->with('user')
                             ->orderBy('created_at', 'desc')
                             ->get();
                                   
        $canned = CannedResponse::where('tenant_id', $tenantId)->get();

        return response()->json([
            'chat' => $chat,
            'messages' => $messages,
            'notes' => $notes,
            'canned_responses' => $canned
        ]);
    }

    public function sendMessage(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $chatId = $request->input('chat_id');
        $content = $request->input('message');
        $isTemplate = $request->boolean('is_template');
        
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $config = WhatsappConfig::where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);
        $policy = app(WhatsappOutboundPolicy::class);
        $reason = null;
        $code = null;
        if (!$policy->canSend($config, $chat, $isTemplate, $reason, $code)) {
            WhatsappAuditLog::create([
                'tenant_id' => $tenantId,
                'chat_id' => $chat->id,
                'actor_user_id' => auth()->id(),
                'actor_type' => 'user',
                'event' => 'outbound_blocked',
                'details' => [
                    'code' => $code,
                    'reason' => $reason,
                    'is_template' => $isTemplate,
                    'content_len' => mb_strlen((string) $content),
                    'content_hash' => hash('sha256', (string) $content),
                ],
            ]);
            return response()->json(['error' => $reason ?: 'Envio não permitido.', 'code' => $code], 422);
        }
        
        // Evolution API Send
        $tenant = Tenant::find($tenantId);
        $evo = new EvolutionApiService($tenant);
        $res = $evo->sendMessage($chat->wa_id, $content, null, 0);
        $policy->recordSend($config, $chat);

        WhatsappAuditLog::create([
            'tenant_id' => $tenantId,
            'chat_id' => $chat->id,
            'actor_user_id' => auth()->id(),
            'actor_type' => 'user',
            'event' => 'outbound_allowed',
            'details' => [
                'is_template' => $isTemplate,
                'provider_message_id' => $res['messageId'] ?? null,
                'content_len' => mb_strlen((string) $content),
                'content_hash' => hash('sha256', (string) $content),
            ],
        ]);

        // Save locally
        $msg = WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $res['key']['id'] ?? ($res['messageId'] ?? 'MANUAL_' . uniqid()),
            'content' => $content,
            'direction' => 'outbound',
            'type' => 'text'
        ]);
        
        return response()->json($msg);
    }

    public function startChat(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'consent' => ['nullable'],
            'is_template' => ['nullable'],
        ]);

        $phoneRaw = (string) $validated['phone'];
        $phone = preg_replace('/\D+/', '', $phoneRaw) ?? '';
        if ($phone === '') {
            return response()->json(['error' => 'Telefone inválido'], 422);
        }

        $name = trim((string) ($validated['name'] ?? ''));
        if ($name === '') {
            $name = 'Contato WhatsApp';
        }

        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $phone],
            [
                'contact_name' => $name,
                'contact_phone' => $phone,
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );

        // Update name/phone if blank
        if (empty($chat->contact_name) || $chat->contact_name === 'Cliente WhatsApp') {
            $chat->contact_name = $name;
        }
        if (empty($chat->contact_phone)) {
            $chat->contact_phone = $phone;
        }
        $chat->last_message_at = now();
        $chat->save();

        $content = trim((string) ($validated['message'] ?? ''));
        $sent = false;
        $reason = null;
        $code = null;

        // Consent: proactive chat must have opt-in (default policy).
        $consent = $request->boolean('consent');
        if ($consent && !$chat->opt_in_at) {
            $chat->opt_in_at = now();
            $chat->save();
        }

        if ($content !== '') {
            $config = WhatsappConfig::where('tenant_id', $tenantId)->firstOrFail();
            $policy = app(WhatsappOutboundPolicy::class);
            $isTemplate = $request->boolean('is_template');
            if (!$policy->canSend($config, $chat, $isTemplate, $reason, $code)) {
                WhatsappAuditLog::create([
                    'tenant_id' => $tenantId,
                    'chat_id' => $chat->id,
                    'actor_user_id' => auth()->id(),
                    'actor_type' => 'user',
                    'event' => 'outbound_blocked',
                    'details' => [
                        'code' => $code,
                        'reason' => $reason,
                        'is_template' => $isTemplate,
                        'content_len' => mb_strlen((string) $content),
                        'content_hash' => hash('sha256', (string) $content),
                    ],
                ]);
                return response()->json([
                    'chat_id' => $chat->id,
                    'sent' => false,
                    'error' => $reason ?: 'Envio não permitido.',
                    'code' => $code,
                ], 422);
            }

            $tenantModel = Tenant::find($tenantId);
            $evo = new EvolutionApiService($tenantModel);
            $res = $evo->sendMessage($chat->wa_id, $content, null, 0);
            $policy->recordSend($config, $chat);

            WhatsappAuditLog::create([
                'tenant_id' => $tenantId,
                'chat_id' => $chat->id,
                'actor_user_id' => auth()->id(),
                'actor_type' => 'user',
                'event' => 'outbound_allowed',
                'details' => [
                    'is_template' => $isTemplate,
                    'provider_message_id' => $res['messageId'] ?? null,
                    'content_len' => mb_strlen((string) $content),
                    'content_hash' => hash('sha256', (string) $content),
                ],
            ]);

            WhatsappMessage::create([
                'chat_id' => $chat->id,
                'message_id' => $res['key']['id'] ?? ($res['messageId'] ?? 'START_' . uniqid()),
                'content' => $content,
                'direction' => 'outbound',
                'type' => 'text',
            ]);

            $sent = empty($res['error']);
        }

        return response()->json([
            'chat_id' => $chat->id,
            'sent' => $sent,
            'error' => $reason,
            'code' => $code,
        ]);
    }

    // --- Novos métodos para CRM ---

    public function addNote(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $chatId = $request->input('chat_id');
        
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);
        
        $note = WhatsappNote::create([
            'chat_id' => $chat->id,
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
            'type' => 'manual'
        ]);
        
        // Carrega o usuario para exibir nome/avatar
        $note->load('user');
        
        return response()->json($note);
    }

    public function getCannedResponses()
    {
        $responses = CannedResponse::where('tenant_id', auth()->user()->tenant_id)->get();
        return response()->json($responses);
    }

    public function saveCannedResponse(Request $request)
    {
        $response = CannedResponse::create([
            'tenant_id' => auth()->user()->tenant_id,
            'title' => $request->input('title'),
            'content' => $request->input('content')
        ]);
        return response()->json($response);
    }

    // --- Simulation for Testing ---
    public function simulateWebhook(Request $request)
    {
        if (!app()->environment('local')) {
            abort(403);
        }

        $phone = $request->input('phone');
        $message = $request->input('message');
        $tenantId = auth()->user()->tenant_id;
        
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();
        if(!$config) return response()->json(['error' => 'Config not found'], 404);

        $payload = [
            'type' => 'ReceivedMessage',
            'phone' => $phone,
            'senderName' => 'Simulated User',
            'messageId' => 'SIM_' . uniqid(),
            'text' => ['message' => $message]
        ];

        // Process message properly (which triggers AI)
        $this->handleReceivedMessage($config, $payload);

        return response()->json(['status' => 'ok']);
    }
    private function getContextModel()
    {
        $user = auth()->user();
        if ($user->role === 'manager') {
            return $user;
        }
        if ($user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }
        return null;
    }

    // --- Connection Status & Pairing Code ---
    public function getStatus()
    {
        $contextModel = $this->getContextModel();

        if (!$contextModel || !$contextModel->evolution_instance_name) {
            return response()->json([
                'status' => 'not_configured', 
                'message' => 'Configure sua instância Evolution API primeiro.'
            ]);
        }

        $evo = new EvolutionApiService($contextModel);
        $state = $evo->getConnectionState();

        $connected = isset($state['instance']['state']) && $state['instance']['state'] === 'open';

        $response = [
            'status' => 'configured',
            'connected' => $connected,
            'details' => $state
        ];

        return response()->json($response);
    }

    /**
     * Retorna o QR Code da instância como base64 para exibição na UI.
     */
    public function getQrCode()
    {
        $contextModel = $this->getContextModel();

        if (!$contextModel || !$contextModel->evolution_instance_name) {
            return response()->json(['error' => 'Instância não configurada.'], 400);
        }

        try {
            $evo = new EvolutionApiService($contextModel);
            $result = $evo->getConnectionQr();

            if (!empty($result['base64'])) {
                return response()->json(['qr_base64' => $result['base64']]);
            }

            if (!empty($result['instance']['state']) && $result['instance']['state'] === 'open') {
                return response()->json(['connected' => true]);
            }

            return response()->json(['error' => 'QR Code não disponível. A instância pode já estar conectada ou em estado inválido.', 'details' => $result], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generatePairingCode(Request $request)
    {
        $contextModel = $this->getContextModel();

        if (!$contextModel || !$contextModel->evolution_instance_name) {
            return response()->json(['error' => 'Instância não configurada.'], 400);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string']
        ]);

        $evo = new EvolutionApiService($contextModel);
        $result = $evo->getPairingCode($validated['phone']);

        // pairingCode só existe quando a instância foi criada sem QR (qrcode: false)
        if (!empty($result['pairingCode'])) {
            return response()->json(['pairing_code' => $result['pairingCode']]);
        }

        // Instância está em modo QR Code — não suporta pairing code
        if (isset($result['code']) || isset($result['base64'])) {
            return response()->json([
                'error' => 'Esta instância foi criada em modo QR Code e não suporta código de pareamento. Exclua a instância no painel Evolution API e recrie-a pelo sistema Vivensi nas Configurações de WhatsApp.',
            ], 422);
        }

        Log::error('Evolution API Pairing Code Error', ['response' => $result]);
        return response()->json(['error' => 'Não foi possível gerar o código de pareamento. Verifique se o formato do número está correto (DDI+DDD+Numero) e tente novamente.', 'details' => $result], 500);
    }
}
