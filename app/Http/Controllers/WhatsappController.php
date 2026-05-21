<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
use App\Services\Messaging\MetaCloudApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class WhatsappController extends Controller
{
    public function updateCompliance(Request $request, $chatId)
    {
        Gate::authorize('access-manager');

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

    public function toggleBot(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $reactivating = $request->boolean('is_bot_active');
        $chat->update([
            'is_bot_active' => $reactivating,
            'assigned_to'   => $reactivating ? null : $chat->assigned_to,
            'status'        => $reactivating ? 'open' : $chat->status,
        ]);

        return response()->json(['success' => true, 'chat' => $chat]);
    }

    public function assignChat(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $chat->update([
            'assigned_to' => auth()->id(),
            'is_bot_active' => false, // Ao assumir, o bot é desligado automaticamente
            'status' => 'human_attending'
        ]);

        WhatsappAuditLog::create([
            'tenant_id' => $tenantId,
            'chat_id' => $chat->id,
            'actor_user_id' => auth()->id(),
            'actor_type' => 'user',
            'event' => 'chat_assigned',
            'details' => ['action' => 'take_over'],
        ]);

        return response()->json(['success' => true, 'chat' => $chat]);
    }

    public function webhook(Request $request)
    {
        // 1. Validação de Handshake da Meta (GET)
        if ($request->isMethod('get')) {
            $verifyToken = config('services.meta.webhook_verify_token');
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');
            
            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response($challenge, 200);
            }
            return response()->json(['error' => 'Invalid verify token'], 403);
        }

        // 2. Verificação de Assinatura HMAC obrigatória
        $appSecret = config('whatsapp.meta_app_secret', env('META_APP_SECRET', ''));
        $signature = $request->header('X-Hub-Signature-256', '');

        if (empty($appSecret)) {
            Log::error('Meta Webhook: META_APP_SECRET não configurado — requisição bloqueada.', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Webhook not configured'], 503);
        }

        if (!MetaCloudApiService::verifyWebhookSignature($request->getContent(), $signature, $appSecret)) {
            Log::warning('Meta Webhook: assinatura HMAC inválida', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 3. Recebimento de Eventos (POST)
        $data = $request->all();

        $phoneNumberId = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;

        if (!$phoneNumberId) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $config = WhatsappConfig::withoutGlobalScopes()->where('meta_phone_number_id', $phoneNumberId)->first();
        if (!$config) {
            Log::warning('Meta Webhook recebido mas Phone ID não encontrado no banco', ['phone_number_id' => $phoneNumberId]);
            return response()->json(['status' => 'ignored'], 200);
        }

        // Despacha para a Fila (Obrigatório retornar 200 rápido para a Meta)
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

        $ds = new \App\Services\DeepSeekService();
        $dsRes = $ds->chat([['role' => 'user', 'content' => $prompt]]);
        $replyText = $dsRes['choices'][0]['message']['content']
            ?? "Entendi. Vou encaminhar sua solicitação para um especialista. Aguarde um momento.";

        Log::info("Bruce AI Response for Chat {$chat->id}: " . $replyText);

        // Salvar insight da IA como nota interna, se for relevante (opcional)
        // WhatsappNote::create(['chat_id' => $chat->id, 'content' => "IA Sugeriu: $replyText", 'type' => 'ai_insight']);

        // Enviar via Evolution API
        try {
            $instance = \App\Models\WhatsappInstance::withoutGlobalScopes()->where('tenant_id', $chat->tenant_id)
                ->where('status', 'open')
                ->first();
            if (!$instance) {
                Log::warning("replyWithAi: nenhuma instância conectada para tenant {$chat->tenant_id}");
                return;
            }
            $evo = new EvolutionApiService($instance);
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
     * Dashboard de Instâncias (Gerenciamento)
     */
    public function instances()
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $user = auth()->user();
        Gate::authorize('access-whatsapp');

        $contextModel = $this->getContextModel();
        
        if (!$contextModel) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado.');
        }

        $tenantId = auth()->user()->tenant_id;
        $instances = \App\Models\WhatsappInstance::where('tenant_id', $tenantId)->get();

        return view('whatsapp.instances', compact('instances'));
    }

    /**
     * Configuração do Chatbot (Painel do Cliente)
     */
    public function settings()
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $user = auth()->user();
        Gate::authorize('access-whatsapp');

        $contextModel = $this->getContextModel();
        
        if (!$contextModel) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado às configurações de WhatsApp.');
        }

        $tenantId = auth()->user()->tenant_id; // Will be null for super_admin
        
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();
        if (!$config) {
            $config = WhatsappConfig::create([
                'tenant_id' => $tenantId,
                'instance_id' => '',
                'token' => '',
                'ai_enabled' => false,
                'outbound_enabled' => true,
            ]);
        }

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

        $instances = \App\Models\WhatsappInstance::where('tenant_id', $tenantId)->get();

        return view('whatsapp.settings', compact('config', 'contextModel', 'instances'));
    }

    public function saveSettings(Request $request)
    {
        $contextModel = $this->getContextModel();
        $config = WhatsappConfig::where('tenant_id', auth()->user()->tenant_id)->first();
        
        $validated = $request->validate([
            'ai_training'    => 'nullable|string|max:10000',
            'ai_enabled'     => 'nullable|boolean',
            'ai_provider'    => 'nullable|string|in:gemini,deepseek',
            // Structured training fields
            'bot_name'       => 'nullable|string|max:100',
            'bot_tone'       => 'nullable|string|in:amigável,formal,empático,animado',
            'org_name'       => 'nullable|string|max:255',
            'org_mission'    => 'nullable|string|max:1000',
            'services'        => 'nullable|string|max:2000',
            'training_manual' => 'nullable|string|max:8000',
            'working_hours'   => 'nullable|string|max:500',
            'contact_info'    => 'nullable|string|max:500',
            'faq'             => 'nullable|array',
            'faq.*.question' => 'nullable|string|max:300',
            'faq.*.answer'   => 'nullable|string|max:1000',
            'outbound_enabled' => 'nullable|boolean',
            'require_opt_in' => 'nullable|boolean',
            'enforce_24h_window' => 'nullable|boolean',
            'allow_templates_outside_window' => 'nullable|boolean',
            'meta_waba_id' => 'nullable|string',
            'meta_phone_number_id' => 'nullable|string',
            'meta_access_token' => 'nullable|string',
            'evolution_instance_name' => 'nullable|string|max:255',
            'evolution_instance_token' => 'nullable|string|max:255',
            'pix_key' => 'nullable|string|max:255',
            'pix_key_type' => 'nullable|string|in:cpf_cnpj,email,phone,random',
        ]);

        // Update PIX info on Tenant if applicable
        if (auth()->user()->tenant) {
            auth()->user()->tenant->update([
                'pix_key' => $validated['pix_key'] ?? null,
                'pix_key_type' => $validated['pix_key_type'] ?? null,
            ]);
        }

        // Se campos estruturados foram enviados, monta o prompt automaticamente
        $structuredFields = ['bot_name','bot_tone','org_name','org_mission','services','training_manual','working_hours','contact_info','faq'];
        $hasStructured = collect($structuredFields)->some(fn($f) => $request->filled($f) || ($f === 'faq' && $request->has('faq')));

        if ($hasStructured) {
            $s = $request;
            $botName  = $s->input('bot_name', 'Bruce');
            $tone     = $s->input('bot_tone', 'amigável');
            $faqs     = collect($s->input('faq', []))->filter(fn($f) => !empty($f['question']) && !empty($f['answer']));

            $prompt  = "Você é *{$botName}*, assistente virtual da **{$s->input('org_name', 'organização')}**.\n";
            $prompt .= "Seu tom é {$tone}, empático e prestativo.\n\n";

            if ($s->filled('org_mission')) {
                $prompt .= "**MISSÃO DA ORGANIZAÇÃO:**\n{$s->input('org_mission')}\n\n";
            }
            if ($s->filled('services')) {
                $prompt .= "**SERVIÇOS OFERECIDOS:**\n{$s->input('services')}\n\n";
            }
            if ($s->filled('training_manual')) {
                $prompt .= "**MANUAL DE ATENDIMENTO & BASE DE CONHECIMENTO:**\n{$s->input('training_manual')}\n\n";
            }
            if ($s->filled('working_hours')) {
                $prompt .= "**HORÁRIO DE ATENDIMENTO:**\n{$s->input('working_hours')}\n\n";
            }
            if ($s->filled('contact_info')) {
                $prompt .= "**CONTATOS:**\n{$s->input('contact_info')}\n\n";
            }
            if ($faqs->isNotEmpty()) {
                $prompt .= "**PERGUNTAS FREQUENTES:**\n";
                foreach ($faqs as $faq) {
                    $prompt .= "P: {$faq['question']}\nR: {$faq['answer']}\n\n";
                }
            }
            $prompt .= "**REGRAS:**\n- Respostas curtas (máx. 3 parágrafos).\n- Nunca invente links ou telefones.\n- Se não souber, peça para aguardar atendimento humano.";

            $validated['ai_training'] = $prompt;
            $validated['ai_training_structured'] = $request->only($structuredFields);
        }

        $validated['ai_enabled'] = $request->boolean('ai_enabled');
        $validated['outbound_enabled'] = $request->boolean('outbound_enabled');
        $validated['require_opt_in'] = $request->boolean('require_opt_in');
        $validated['enforce_24h_window'] = $request->boolean('enforce_24h_window');
        $validated['allow_templates_outside_window'] = $request->boolean('allow_templates_outside_window');

        // Atualizar Credenciais JSON da Meta se fornecido pelo JS (OBO Flow)
        if ($request->filled('meta_waba_id')) {
            $contextModel->update([
                'meta_waba_id' => $validated['meta_waba_id'],
                'meta_phone_number_id' => $validated['meta_phone_number_id'],
                'meta_access_token' => $validated['meta_access_token'],
            ]);
            
            $config->update([
                'meta_waba_id' => $validated['meta_waba_id'],
                'meta_phone_number_id' => $validated['meta_phone_number_id'],
                'meta_access_token' => $validated['meta_access_token'],
            ]);
        }

        // Atualizar Credenciais da Evolution API se fornecidas
        if ($request->filled('evolution_instance_name')) {
            $contextModel->update([
                'evolution_instance_name' => $validated['evolution_instance_name'],
                'evolution_instance_token' => $validated['evolution_instance_token'],
            ]);
        }
        
        // Remove meta credentials from config update array to avoid mass assignment issues if not fillable
        $configData = $validated;
        unset($configData['meta_waba_id'], $configData['meta_phone_number_id'], $configData['meta_access_token'], $configData['evolution_instance_name'], $configData['evolution_instance_token']);
        // Remove individual structured fields — já consolidados em ai_training_structured
        foreach (['bot_name','bot_tone','org_name','org_mission','services','working_hours','contact_info','faq'] as $f) {
            unset($configData[$f]);
        }
        
        $config->update($configData);

        return back()->with('success', 'Configurações Salvas com Sucesso!');
    }


    public function templates()
    {
        $user = auth()->user();
        Gate::authorize('access-whatsapp');

        $contextModel = $this->getContextModel();
        
        if (empty($contextModel->meta_waba_id) || empty($contextModel->meta_access_token)) {
            return view('whatsapp.templates', ['error' => 'A integração oficial da Meta não foi configurada. Conecte sua conta no painel de Configurações do WhatsApp.']);
        }

        $service = new \App\Services\Messaging\MetaCloudApiService($contextModel);
        
        try {
            $templatesResult = $service->getTemplates();
            $templates = $templatesResult['data'] ?? [];
            return view('whatsapp.templates', compact('templates'));
        } catch (\Exception $e) {
            return view('whatsapp.templates', ['error' => 'Falha ao buscar templates na Meta: ' . $e->getMessage()]);
        }
    }

    /**
     * Retorna os templates em JSON para o modal de chat.
     */
    public function templatesJson()
    {
        $contextModel = $this->getContextModel();
        if (!$contextModel || empty($contextModel->meta_waba_id)) {
            return response()->json(['templates' => []]);
        }

        $service = new \App\Services\Messaging\MetaCloudApiService($contextModel);
        $res = $service->getTemplates();

        return response()->json(['templates' => $res['data'] ?? []]);
    }

    /**
     * Interface de Chat (Mini CRM)
     */
    public function chatIndex()
    {
        $user = auth()->user();
        Gate::authorize('access-whatsapp');

        $chats = WhatsappChat::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('last_message_at', 'desc')
                             ->get();
                             
        $projects = \App\Models\Project::where('tenant_id', auth()->user()->tenant_id)
                                       ->where('status', 'active')
                                       ->get();

        return view('whatsapp.chat', compact('chats', 'projects'));
    }

    public function sendToKanban(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id'
        ]);

        $project = \App\Models\Project::where('id', $validated['project_id'])
                                      ->where('tenant_id', $tenantId)
                                      ->firstOrFail();

        // Add as ProjectPerson
        $person = \App\Models\ProjectPerson::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'project_id' => $project->id,
                'phone' => $chat->contact_phone,
            ],
            [
                'name' => $chat->contact_name,
            ]
        );

        // Consolida notas do chat para incluir no card
        $notes = \App\Models\WhatsappNote::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')->get();
        $notesBlock = $notes->isNotEmpty()
            ? "\n\n--- Notas do Atendimento ---\n" . $notes->map(fn($n) => "• {$n->content}")->join("\n")
            : '';

        $task = \App\Models\Task::create([
            'tenant_id'  => $tenantId,
            'project_id' => $project->id,
            'created_by' => auth()->id(),
            'title'      => "Lead WhatsApp: {$chat->contact_name}",
            'description'=> "Contato via WhatsApp.\nTelefone: {$chat->contact_phone}{$notesBlock}",
            'status'     => 'todo',
            'priority'   => 'medium',
        ]);

        return response()->json(['success' => true, 'task' => $task, 'person' => $person]);
    }

    /**
     * Envia contato do chat para o Kanban de Patrocínios (role ngo).
     */
    public function sendToSponsorshipKanban(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $notes = \App\Models\WhatsappNote::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')->get();
        $notesText = $notes->isNotEmpty()
            ? $notes->map(fn($n) => "• {$n->content}")->join("\n")
            : '';

        $deal = \App\Models\SponsorshipDeal::create([
            'tenant_id'      => $tenantId,
            'company_name'   => $request->input('company_name') ?: $chat->contact_name,
            'contact_person' => $chat->contact_name,
            'phone'          => $chat->contact_phone,
            'stage'          => 'prospecting',
            'contact_date'   => now()->toDateString(),
            'notes'          => trim("Lead via WhatsApp.\n{$notesText}"),
        ]);

        return response()->json(['success' => true, 'deal' => $deal]);
    }

    public function chatList()
    {
        $tenantId = auth()->user()->tenant_id;

        // Subquery correlated: busca última mensagem em query única (sem N+1)
        $chats = WhatsappChat::where('tenant_id', $tenantId)
            ->addSelect([
                'last_message_preview' => WhatsappMessage::select('content')
                    ->whereColumn('chat_id', 'whatsapp_chats.id')
                    ->latest()
                    ->limit(1),
            ])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($chat) {
                return [
                    'id'                        => $chat->id,
                    'contact_name'              => $chat->contact_name ?? 'Sem Nome',
                    'last_message_at_formatted' => $chat->last_message_at
                        ? \Carbon\Carbon::parse($chat->last_message_at)->format('H:i')
                        : '',
                    'last_message_preview'      => $chat->last_message_preview
                        ? mb_substr($chat->last_message_preview, 0, 40)
                        : '',
                ];
            });

        return response()->json(['chats' => $chats]);
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

        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();

        return response()->json([
            'chat'             => $chat,
            'messages'         => $messages,
            'notes'            => $notes,
            'canned_responses' => $canned,
            'ai_training'      => $config?->ai_training ?? '',
        ]);
    }

    public function updateTraining(Request $request)
    {
        $user = auth()->user();
        Gate::authorize('access-whatsapp');

        $request->validate(['training' => 'nullable|string']);
        
        $config = WhatsappConfig::where('tenant_id', auth()->user()->tenant_id)->first();
        if (!$config) {
            return response()->json(['success' => false, 'message' => 'Configuração não encontrada'], 404);
        }

        $newTraining = $request->training ?? '';

        // Preserva o FAQ estruturado ao salvar o treinamento rápido
        $structured = $config->ai_training_structured ?? [];
        $faqs = collect($structured['faq'] ?? [])->filter(fn($f) => !empty($f['question']) && !empty($f['answer']));

        if ($faqs->isNotEmpty()) {
            $faqBlock  = "\n\n**PERGUNTAS FREQUENTES:**\n";
            foreach ($faqs as $faq) {
                $faqBlock .= "P: {$faq['question']}\nR: {$faq['answer']}\n\n";
            }
            $newTraining = rtrim($newTraining) . $faqBlock;
        }

        $config->update(['ai_training' => $newTraining]);

        return response()->json(['success' => true]);
    }

    public function sendMessage(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $tenantId = auth()->user()->tenant_id;
        $chat     = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($request->input('chat_id'));
        $config   = WhatsappConfig::where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);

        $isTemplate   = $request->boolean('is_template');
        $templateData = $isTemplate ? [
            'template_name' => $request->input('template_name'),
            'language_code' => $request->input('language_code', 'pt_BR'),
            'vars'          => $request->input('template_vars', []),
        ] : [];

        try {
            $result = app(\App\Services\WhatsAppService::class)->sendMessage(
                $chat, $config,
                (string) $request->input('message', ''),
                $isTemplate, $templateData,
                auth()->id()
            );
        } catch (\App\Exceptions\WhatsAppPolicyException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => $e->policyCode], 422);
        } catch (\RuntimeException $e) {
            $status = $e->getCode() >= 400 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }

        return response()->json($result['message']);
    }

    public function startChat(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'phone'       => ['required', 'string', 'max:30'],
            'name'        => ['nullable', 'string', 'max:255'],
            'message'     => ['nullable', 'string', 'max:5000'],
            'consent'     => ['nullable'],
            'is_template' => ['nullable'],
        ]);

        $whatsapp = app(\App\Services\WhatsAppService::class);

        try {
            $chat = $whatsapp->startChat(
                $tenantId,
                (string) $validated['phone'],
                (string) ($validated['name'] ?? ''),
                $request->boolean('consent')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $content = trim((string) ($validated['message'] ?? ''));
        $sent    = false;
        $reason  = null;
        $code    = null;

        if ($content !== '') {
            $config     = WhatsappConfig::where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);
            $isTemplate = $request->boolean('is_template');

            try {
                $whatsapp->sendMessage($chat, $config, $content, $isTemplate, [
                    'template_name' => $request->input('template_name', 'hello_world'),
                    'language_code' => 'pt_BR',
                    'vars'          => [],
                ], auth()->id());
                $sent = true;
            } catch (\RuntimeException $e) {
                $code   = 'send_failed';
                $reason = $e->getMessage();
            }
        }

        return response()->json([
            'chat_id' => $chat->id,
            'sent'    => $sent,
            'error'   => $reason,
            'code'    => $code,
        ]);
    }

    // --- Novos métodos para CRM ---

    public function addNote(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $chatId = $request->input('chat_id');
        
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);
        
        $note = WhatsappNote::create([
            'tenant_id' => $tenantId,
            'chat_id'   => $chat->id,
            'user_id'   => auth()->id(),
            'content'   => $request->input('content'),
            'type'      => 'manual',
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
        if ($user->isManager() || $user->isSuperAdmin()) {
            return $user;
        }
        if ($user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }
        return null;
    }

    /**
     * Obtém o status de conexão da instância Evolution API
     */
    public function getStatus(Request $request)
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $contextModel = $this->getContextModel();
        if (!$contextModel) {
            return response()->json(['error' => 'Context model not found'], 404);
        }
        
        $evo = new EvolutionApiService($contextModel);
        $result = $evo->getConnectionState();
        
        return response()->json($result);
    }

    /**
     * Gera QR Code para conexão da Evolution API
     */
    public function getQrCode(Request $request)
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $contextModel = $this->getContextModel();
        if (!$contextModel) {
            return response()->json(['error' => 'Context model not found'], 404);
        }
        
        $evo = new EvolutionApiService($contextModel);
        $result = $evo->getConnectionQr();
        
        return response()->json($result);
    }

    /**
     * Gera pairing code para conexão da Evolution API
     */
    public function generatePairingCode(Request $request)
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');
        
        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
        ]);
        
        $contextModel = $this->getContextModel();
        if (!$contextModel) {
            return response()->json(['error' => 'Context model not found'], 404);
        }
        
        $evo = new EvolutionApiService($contextModel);
        $result = $evo->getPairingCode($validated['phone_number']);
        
        return response()->json($result);
    }

    // Removemos os metodos de Conexão Evolution API (getStatus, getQrCode, getPairingCode)
    // porque agora a autenticação é o Meta Embedded Flow.

    public function sendMedia(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;
        $chat     = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $validated = $request->validate([
            'base64'   => ['required', 'string'],
            'mimetype' => ['required', 'string', 'in:image/jpeg,image/png,image/webp,image/gif'],
            'caption'  => ['nullable', 'string', 'max:1024'],
        ]);

        $config = WhatsappConfig::where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);

        try {
            $result = app(\App\Services\WhatsAppService::class)->sendMedia(
                $chat, $config,
                $validated['base64'],
                $validated['mimetype'],
                $validated['caption'] ?? '',
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            $status = $e->getCode() >= 400 ? $e->getCode() : 500;
            return response()->json(['success' => false, 'error' => $e->getMessage()], $status);
        }

        return response()->json(['success' => true, 'message' => $result['message']]);
    }

    public function sendAudio(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;
        $chat     = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $validated = $request->validate([
            'base64'   => ['required', 'string'],
            'mimetype' => ['nullable', 'string'],
        ]);

        $config = WhatsappConfig::where('tenant_id', $tenantId)->firstOrCreate(['tenant_id' => $tenantId]);

        try {
            $result = app(\App\Services\WhatsAppService::class)->sendAudio(
                $chat, $config,
                $validated['base64'],
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            $status = $e->getCode() >= 400 ? $e->getCode() : 500;
            return response()->json(['success' => false, 'error' => $e->getMessage()], $status);
        }

        return response()->json(['success' => true, 'message' => $result['message']]);
    }

    /**
     * Agendar mensagem de texto para envio futuro
     */
    public function scheduleMessage(Request $request, $chatId)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);

        $validated = $request->validate([
            'content'      => ['required', 'string', 'max:4096'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        if ($chat->blocked_at || $chat->opt_out_at) {
            return response()->json(['error' => 'Contato bloqueado ou com opt-out. Agendamento cancelado.'], 422);
        }

        $scheduled = \App\Models\ScheduledWhatsappMessage::create([
            'tenant_id'    => $tenantId,
            'chat_id'      => $chat->id,
            'content'      => $validated['content'],
            'scheduled_at' => $validated['scheduled_at'],
            'status'       => 'pending',
            'created_by'   => auth()->id(),
        ]);

        return response()->json([
            'success'    => true,
            'scheduled'  => $scheduled,
            'message'    => 'Mensagem agendada para ' . \Carbon\Carbon::parse($validated['scheduled_at'])->format('d/m/Y H:i'),
        ]);
    }
}
