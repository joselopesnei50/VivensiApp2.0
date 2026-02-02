<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConfig;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Models\CannedResponse;
use App\Models\WhatsappNote;
use App\Services\ZApiService;
use App\Services\GeminiService;
use App\Services\DeepSeekService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Webhook receptor da Z-API
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        // Z-API envia o ClientToken no Header para segurança
        $clientToken = $request->header('Client-Token');
        $config = WhatsappConfig::where('client_token', $clientToken)->first();

        if (!$config) {
            return response()->json(['error' => 'Unauthorized instance'], 401);
        }

        // Processar apenas mensagens recebidas
        if (isset($data['type']) && $data['type'] == 'ReceivedMessage') {
            $this->handleReceivedMessage($config, $data);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleReceivedMessage($config, $data)
    {
        $tenantId = $config->tenant_id;
        $waId = $data['phone']; // ex: 558199999999
        $content = $data['text']['message'] ?? '';
        $messageId = $data['messageId'];

        // 1. Localizar ou criar conversa
        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $tenantId, 'wa_id' => $waId],
            [
                'contact_name' => $data['senderName'] ?? 'Cliente WhatsApp',
                'contact_phone' => $waId,
                'status' => 'open'
            ]
        );

        $chat->update(['last_message_at' => now()]);

        // 2. Salvar mensagem no banco
        WhatsappMessage::create([
            'chat_id' => $chat->id,
            'message_id' => $messageId,
            'content' => $content,
            'direction' => 'inbound'
        ]);

        // 3. Se a IA estiver ativada e não houver atendente humano fixo, responder com IA
        if ($config->ai_enabled && (!$chat->assigned_to || $chat->status == 'open')) {
            $this->replyWithAi($config, $chat, $content);
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
        return view('whatsapp.settings', compact('config'));
    }

    public function saveSettings(Request $request)
    {
        $config = WhatsappConfig::where('tenant_id', auth()->user()->tenant_id)->first();
        $config->update($request->all());

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
        
        $chat = WhatsappChat::where('tenant_id', $tenantId)->findOrFail($chatId);
        
        // Z-API Send
        $zapi = new ZApiService($tenantId);
        $res = $zapi->sendMessage($chat->wa_id, $content);

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
