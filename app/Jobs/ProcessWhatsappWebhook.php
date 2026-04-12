<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WhatsappConfig;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Models\WhatsappAuditLog;
use App\Jobs\ProcessWhatsappAiResponse;
use App\Services\Messaging\MetaCloudApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessWhatsappWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $configId;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param int $configId
     * @param array $data
     */
    public function __construct(int $configId, array $data)
    {
        $this->configId = $configId;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = WhatsappConfig::find($this->configId);
        if (!$config) {
            Log::error("ProcessWhatsappWebhook: Config não encontrada ID {$this->configId}");
            return;
        }

        $tenantId = $config->tenant_id;

        // Estrutura Meta: entry[0].changes[0].value.messages[0]
        $entry = $this->data['entry'][0] ?? null;
        $value = $entry['changes'][0]['value'] ?? null;
        
        if (!$value || !isset($value['messages'])) {
            // Pode ser um evento de Status (sent, delivered, read)
            $this->handleStatusUpdate($config, $value);
            return;
        }

        foreach ($value['messages'] as $msgData) {
            $waId = (string) ($msgData['from'] ?? ''); // Número do cliente
            $messageId = (string) ($msgData['id'] ?? '');
            $type = $msgData['type'] ?? 'text';
            
            // Atualmente suportamos TEXTO. Outros tipos ignoramos ou tratamos como generic.
            $content = "";
            $mediaPath = null;
            $mediaCaption = $msgData[$type]['caption'] ?? null;

            if ($type === 'text') {
                $content = (string) ($msgData['text']['body'] ?? '');
            } elseif ($type === 'interactive') {
                $content = $msgData['interactive']['button_reply']['title'] ?? ($msgData['interactive']['list_reply']['title'] ?? 'Resposta Interativa');
            } elseif (in_array($type, ['image', 'audio', 'video', 'document'])) {
                $mediaId = $msgData[$type]['id'] ?? null;
                $content = "[Mídia recebida: " . strtoupper($type) . "]";
                
                if ($mediaId) {
                    $metaService = new MetaCloudApiService($config);
                    $download = $metaService->downloadMedia($mediaId);
                    
                    if (!isset($download['error'])) {
                        $fileName = "whatsapp/media/{$tenantId}/" . $mediaId . "." . $download['extension'];
                        Storage::put($fileName, $download['binary']);
                        $mediaPath = $fileName;
                        Log::info("Mídia baixada com sucesso: {$fileName}");
                    } else {
                        Log::warning("Falha ao baixar mídia da Meta: " . $download['error']);
                    }
                }
            } else {
                $content = "[Tipo de mensagem não suportado: " . strtoupper($type) . "]";
            }

            if ($waId === '' || $messageId === '') continue;

            // Idempotência
            if (WhatsappMessage::where('message_id', $messageId)->exists()) continue;

            // Localizar Perfil do Contato
            $profileName = "Contato WhatsApp";
            if (isset($value['contacts'][0]['profile']['name'])) {
                $profileName = $value['contacts'][0]['profile']['name'];
            }

            // 1. Criar/Localizar Chat
            $chat = WhatsappChat::updateOrCreate(
                ['tenant_id' => $tenantId, 'wa_id' => $waId],
                [
                    'contact_name' => $profileName,
                    'contact_phone' => $waId,
                    'status' => 'open',
                    'last_message_at' => now(),
                    'last_inbound_at' => now(),
                    'opt_in_at' => now() // Mensagem recebida = Opt-in implícito
                ]
            );

            // 2. Salvar Mensagem
            WhatsappMessage::create([
                'chat_id' => $chat->id,
                'message_id' => $messageId,
                'content' => $content,
                'direction' => 'inbound',
                'type' => $type,
                'status' => 'read', // Inbound sempre lido pelo sistema
                'media_path' => $mediaPath,
                'media_caption' => $mediaCaption
            ]);

            Log::info("Nova mensagem Meta WA recebida: Chat {$chat->id} | Msg: {$content}");

            // 3. Marcar mensagem como lida (envia read receipt ao cliente)
            try {
                $metaService = new MetaCloudApiService($config);
                $metaService->markMessageAsRead($messageId);
            } catch (\Throwable $e) {
                Log::warning("Falha ao marcar mensagem como lida: {$e->getMessage()}");
            }

            // 4. Auditoria
            WhatsappAuditLog::create([
                'tenant_id' => $tenantId,
                'chat_id' => $chat->id,
                'actor_type' => 'webhook_meta',
                'event' => 'inbound_message',
                'details' => ['message_id' => $messageId, 'type' => $type]
            ]);

            // 4. Trigger AI (Bruce) se habilitado
            if ($config->ai_enabled && (!$chat->assigned_to || $chat->status == 'open') && !$chat->opt_out_at && !$chat->blocked_at) {
                ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                    ->onQueue('whatsapp');
            }
        }
    }

    protected function handleStatusUpdate($config, $value)
    {
        $statuses = $value['statuses'] ?? [];
        foreach ($statuses as $statusData) {
            $messageId = $statusData['id'] ?? '';
            $status = $statusData['status'] ?? ''; // sent, delivered, read, failed
            $timestamp = $statusData['timestamp'] ?? null;

            if ($messageId) {
                // Sincroniza o status no banco
                $msg = WhatsappMessage::where('message_id', $messageId)->first();
                if ($msg) {
                    // Se a mensagem já estiver 'read', não voltamos para 'delivered'
                    if ($msg->status === 'read' && $status === 'delivered') continue;
                    
                    $msg->update(['status' => $status]);
                    Log::info("Meta WA Status Sync: {$messageId} -> {$status}");
                }
            }
        }
    }
}
