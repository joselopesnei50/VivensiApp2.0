<?php

namespace App\Jobs;

use App\Models\WhatsappCampaignMessage;
use App\Models\WhatsappInstance;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use App\Services\Messaging\MetaCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendWhatsAppCampaignMessage
 * 
 * Job de envio de campanha dual-channel com AntiBan real:
 * 1. Carrega a mensagem e a campanha do banco
 * 2. Verifica se a instância Evolution pode enviar (janela + limite)
 * 3. Se tiver Meta configurada → usa Meta Cloud API (templates aprovados)
 * 4. Se não → usa Evolution API com simulação de digitação humana
 * 5. Registra o envio e atualiza o contador diário da instância
 */
class SendWhatsAppCampaignMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff  = [60, 300, 900]; // 1min, 5min, 15min
    public int   $timeout  = 60;

    public function __construct(
        protected int $campaignMessageId,
        protected int $delaySeconds = 15
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(AntiBanManager $antiBan): void
    {
        /** @var \App\Models\WhatsappCampaignMessage $message */
        $message = WhatsappCampaignMessage::with('campaign')->find($this->campaignMessageId);

        if (!$message || $message->status !== 'pending') return;

        $campaign = $message->campaign;
        if (!$campaign || $campaign->status !== 'running') return;

        $tenantId = $campaign->tenant_id;

        // ── Selecionar instância Evolution ativo do tenant ──
        $instance = WhatsappInstance::forTenant($tenantId)->active()->first();

        // ── Verificar AntiBan ────────────────────────────────────────────
        if ($instance && !$antiBan->canSendMessage($instance)) {
            // Reagendar para 30 min (fora da janela ou limite atingido)
            Log::info("SendCampaign: [{$campaign->name}] Reagendando msg #{$message->id} (AntiBan).");
            $this->release(now()->addMinutes(30));
            return;
        }

        $message->update(['status' => 'sending']);

        $phone  = (string) $message->contact_phone;
        $result = [];
        $sent   = false;

        try {
            $config = \App\Models\WhatsappConfig::where('tenant_id', $tenantId)->first();

            // ── CAMINHO 1: Meta Cloud API (Oficial) — templates aprovados ──
            if ($config && !empty($config->meta_phone_number_id) && !empty($config->meta_access_token)) {
                $meta   = new MetaCloudApiService($config);
                $template = $campaign->message_template ?? 'hello_world';

                $result = $meta->sendTemplateMessage($phone, $template, 'pt_BR');
                $sent   = isset($result['messages'][0]['id']);

            // ── CAMINHO 2: Evolution API (Nossa Infra) — texto livre ──
            } elseif ($instance) {
                $evo = new EvolutionApiService($instance);

                // Simular digitação humana antes do envio
                $antiBan->simulateHumanTyping($instance, $phone);

                $result = $evo->sendMessage(
                    $phone,
                    $campaign->message_template ?? '',
                    null,
                    0 // o delay já foi simulado via presence
                );
                $sent = !isset($result['error']);

            } else {
                $message->update([
                    'status'        => 'failed',
                    'error_message' => 'Nenhum canal de envio configurado (Meta ou Evolution).',
                ]);
                return;
            }

        } catch (\Throwable $e) {
            Log::error("SendCampaign: Exceção ao enviar msg #{$message->id}: " . $e->getMessage());
            $message->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return;
        }

        // ── Registrar resultado ──────────────────────────────────────────
        if ($sent) {
            $message->update([
                'status'           => 'sent',
                'rendered_message' => $campaign->message_template,
                'sent_at'          => now(),
            ]);

            // Incrementar contador diário da instância Evolution (anti-ban real)
            if ($instance) {
                $antiBan->recordSent($instance);
            }
        } else {
            $message->update([
                'status'        => 'failed',
                'error_message' => json_encode($result),
            ]);
            Log::warning("SendCampaign: Falha ao enviar msg #{$message->id}", $result);
        }
    }
}
