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
use App\Models\SystemSetting;
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
        $config = WhatsappConfig::withoutGlobalScopes()->find($this->configId);
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

            // 4. Automações por palavra-chave (tempo real)
            $keywordFired = false;
            if ($type === 'text' && $content) {
                $keywordFired = $this->processKeywordAutomations($tenantId, $chat, $content);
            }

            // 5. Bot de Atendimento: FAQ + off-hours + AI
            $atendEnabled = SystemSetting::getValue('atend_enabled', '0') === '1';
            // Bot só responde se ninguém assumiu E o kill-switch por chat estiver ligado.
            // Antes era `|| status=='open'`, que reativava o bot depois de release.
            $canAutoReply = !$chat->assigned_to && $chat->is_bot_active;

            if (!$keywordFired && $atendEnabled && $canAutoReply && !$chat->opt_out_at && !$chat->blocked_at) {
                // 4a. Verificar horário de atendimento
                $workStart = SystemSetting::getValue('atend_work_start', '00:00');
                $workEnd   = SystemSetting::getValue('atend_work_end',   '23:59');
                $now       = now();
                $inHours   = $now->format('H:i') >= $workStart && $now->format('H:i') <= $workEnd;

                if (!$inHours) {
                    $offMsg = SystemSetting::getValue('atend_off_hours_msg',
                        'Nosso atendimento está indisponível no momento. Retornaremos em breve!');
                    try {
                        $metaService = new MetaCloudApiService($config);
                        $metaService->sendTextMessage($chat->wa_id, $offMsg);
                        WhatsappMessage::create([
                            'chat_id'   => $chat->id,
                            'message_id'=> 'bot_offhours_' . time(),
                            'content'   => $offMsg,
                            'direction' => 'outbound',
                            'type'      => 'text',
                            'status'    => 'sent',
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning("Bot atendimento off-hours send failed: " . $e->getMessage());
                    }
                } else {
                    // 4b. Verificar FAQ por palavra-chave
                    $faqJson  = SystemSetting::getValue('atend_faq', '[]');
                    $faqItems = json_decode($faqJson, true) ?: [];
                    $faqReply = null;

                    foreach ($faqItems as $item) {
                        $keyword = mb_strtolower(trim($item['keyword'] ?? ''));
                        if ($keyword && str_contains(mb_strtolower($content), $keyword)) {
                            $faqReply = $item['response'];
                            break;
                        }
                    }

                    if ($faqReply) {
                        try {
                            $metaService = new MetaCloudApiService($config);
                            $metaService->sendTextMessage($chat->wa_id, $faqReply);
                            WhatsappMessage::create([
                                'chat_id'   => $chat->id,
                                'message_id'=> 'bot_faq_' . time(),
                                'content'   => $faqReply,
                                'direction' => 'outbound',
                                'type'      => 'text',
                                'status'    => 'sent',
                            ]);
                            Log::info("Bot FAQ match para chat {$chat->id}: keyword={$keyword}");
                        } catch (\Throwable $e) {
                            Log::warning("Bot FAQ send failed: " . $e->getMessage());
                        }
                    } elseif ($config->ai_enabled) {
                        // 4c. Dispatch AI response
                        ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                            ->onQueue('whatsapp');
                    }
                }
            } elseif (!$keywordFired && $config->ai_enabled && $canAutoReply && !$chat->opt_out_at && !$chat->blocked_at) {
                // Bot de atendimento desligado mas AI ativada na config do tenant
                ProcessWhatsappAiResponse::dispatch((int) $config->id, (int) $chat->id, $content)
                    ->onQueue('whatsapp');
            }
        }
    }

    private function processKeywordAutomations(int $tenantId, WhatsappChat $chat, string $content): bool
    {
        $automations = \App\Models\WhatsappAutomation::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('trigger', 'keyword_received')
            ->where('is_active', true)
            ->get();

        $fired = false;

        foreach ($automations as $automation) {
            if (!$automation->isWithinSendWindow()) continue;
            if (!$automation->keyword) continue;

            $keyword = mb_strtolower(trim($automation->keyword));
            if (!str_contains(mb_strtolower($content), $keyword)) continue;

            $alreadySent = $automation->send_once
                ? \App\Models\WhatsappAutomationLog::where('automation_id', $automation->id)
                    ->where('contact_phone', $chat->wa_id)
                    ->where('status', 'sent')
                    ->exists()
                : \App\Models\WhatsappAutomationLog::where('automation_id', $automation->id)
                    ->where('contact_phone', $chat->wa_id)
                    ->where('sent_at', '>=', now()->subHours(24))
                    ->exists();

            if ($alreadySent) continue;

            $orgName = \App\Models\Tenant::find($tenantId)?->name ?? 'nossa organização';
            $message = $automation->renderMessage($chat->contact_name ?? 'Olá', $orgName);

            $instance = \App\Models\WhatsappInstance::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'open')
                ->first();

            if (!$instance) continue;

            try {
                (new \App\Services\EvolutionApiService($instance))->sendMessage($chat->wa_id, $message, null, rand(1, 3));

                \App\Models\WhatsappAutomationLog::create([
                    'automation_id' => $automation->id,
                    'tenant_id'     => $tenantId,
                    'contact_phone' => $chat->wa_id,
                    'contact_name'  => $chat->contact_name,
                    'message_sent'  => $message,
                    'status'        => 'sent',
                    'sent_at'       => now(),
                ]);

                $fired = true;
            } catch (\Throwable $e) {
                \App\Models\WhatsappAutomationLog::create([
                    'automation_id' => $automation->id,
                    'tenant_id'     => $tenantId,
                    'contact_phone' => $chat->wa_id,
                    'contact_name'  => $chat->contact_name,
                    'message_sent'  => $message,
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at'       => now(),
                ]);
            }
        }

        return $fired;
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
