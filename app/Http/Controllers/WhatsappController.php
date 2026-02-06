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
use App\Services\ZApiService;
use App\Services\WhatsappOutboundPolicy;
use App\Services\GeminiService;
use App\Services\DeepSeekService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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
     * Webhook receptor da Z-API
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        // Z-API envia o ClientToken no Header para segurança
        $clientToken = $request->header('Client-Token');
        $config = null;
        if (!empty($clientToken)) {
            $config = WhatsappConfig::where('client_token_hash', hash('sha256', (string) $clientToken))->first();
        }
        // Backward-compat fallback (older rows without hash)
        if (!$config && !empty($clientToken)) {
            $config = WhatsappConfig::where('client_token', $clientToken)->first();
        }

        if (!$config) {
            return response()->json(['error' => 'Unauthorized instance'], 401);
        }

        // Processar apenas mensagens recebidas
        if (isset($data['type']) && $data['type'] == 'ReceivedMessage') {
            try {
                $this->handleReceivedMessage($config, $data);
            } catch (\Throwable $e) {
                // Never 500 on webhook; Z-API may retry indefinitely.
                Log::warning('WhatsApp webhook processing error', [
                    'tenant_id' => $config->tenant_id,
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['status' => 'ignored'], 200);
            }
        }

        return response()->json(['status' => 'ok']);
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

        // Enviar via Z-API (Em ambiente de teste real, isso requer instância ativa)
        try {
            $zapi = new ZApiService($config->tenant_id);
            $res = $zapi->sendMessage($chat->wa_id, $replyText);
            
            if (isset($res['messageId'])) {
                WhatsappMessage::create([
                    'chat_id' => $chat->id,
                    'message_id' => $res['messageId'],
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
        $config = WhatsappConfig::firstOrCreate(['tenant_id' => auth()->user()->tenant_id]);

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

        return view('whatsapp.settings', compact('config'));
    }

    public function saveSettings(Request $request)
    {
        $config = WhatsappConfig::where('tenant_id', auth()->user()->tenant_id)->first();
        $validated = $request->validate([
            'ai_training' => 'nullable|string|max:10000',
            'ai_enabled' => 'nullable|boolean',
            'instance_id' => 'required|string|max:255',
            'token' => 'nullable|string|max:255',
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

        // Keep existing secret values if left blank
        if (empty($validated['token'])) {
            unset($validated['token']);
        }

        if (!empty($validated['client_token'])) {
            $validated['client_token_hash'] = hash('sha256', (string) $validated['client_token']);
        } else {
            // keep existing token/hash if omitted
            unset($validated['client_token']);
        }

        $config->update($validated);

        return back()->with('success', 'Configurações de WhatsApp/IA atualizadas!');
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

    public function getChatMessages($chatId)
    {
        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);
        
        $messages = WhatsappMessage::where('chat_id', $chat->id)
                                   ->orderBy('created_at', 'asc')
                                   ->get();

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

        $config = WhatsappConfig::where('tenant_id', $tenantId)->firstOrFail();
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
        
        // Z-API Send
        $zapi = new ZApiService($tenantId);
        $res = $zapi->sendMessage($chat->wa_id, $content);
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
            'message_id' => $res['messageId'] ?? 'MANUAL_' . uniqid(),
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

            $zapi = new ZApiService($tenantId);
            $res = $zapi->sendMessage($chat->wa_id, $content);
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
                'message_id' => $res['messageId'] ?? 'START_' . uniqid(),
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
    // --- Connection Status & QR Code ---
    public function getStatus()
    {
        $tenantId = auth()->user()->tenant_id;
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();

        if (!$config || !$config->instance_id || !$config->token) {
            return response()->json([
                'status' => 'not_configured', 
                'message' => 'Configure sua instância Z-API primeiro.'
            ]);
        }

        $zapi = new ZApiService($tenantId);
        $status = $zapi->getStatus(); // { connected: true/false, ... }

        $response = [
            'status' => 'configured',
            'connected' => $status['connected'] ?? false,
            'details' => $status
        ];

        // If not connected, try to fetch QR Code
        if (!($status['connected'] ?? false)) {
            $qr = $zapi->getQrCode();
            if (isset($qr['image'])) {
                $response['qr_code'] = $qr['image']; // base64
            }
        }

        return response()->json($response);
    }
}
