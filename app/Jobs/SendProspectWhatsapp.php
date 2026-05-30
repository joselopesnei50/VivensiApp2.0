<?php

namespace App\Jobs;

use App\Models\WhatsappInstance;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendProspectWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(
        protected int    $prospectId,
        protected int    $tenantId,
        protected int    $instanceId,
        protected string $message
    ) {}

    public function handle(): void
    {
        $instance = WhatsappInstance::find($this->instanceId);
        if (!$instance || $instance->status !== 'open') {
            Log::warning("SendProspectWhatsapp: instância #{$this->instanceId} indisponível.");
            return;
        }

        $prospect = \App\Models\Prospect::find($this->prospectId);
        if (!$prospect || empty($prospect->phone)) return;

        $normalized = EvolutionApiService::normalizeBrazilianPhone($prospect->phone);
        if ($normalized === null) return;

        $evo    = new EvolutionApiService($instance);
        $jidMap = [];
        try {
            $jidMap = $evo->checkWhatsappNumbers([$normalized]);
        } catch (\Throwable $e) {
            Log::warning("SendProspectWhatsapp: checkWhatsappNumbers falhou para {$normalized} — {$e->getMessage()}");
        }

        $sendTo = $jidMap[$normalized] ?? $normalized;

        $res = $evo->sendMessage($sendTo, $this->message, null, 2);

        if (isset($res['error'])) {
            Log::error("SendProspectWhatsapp failed for {$sendTo}: " . ($res['error'] ?? ''));
            return;
        }

        // Registra no chat para aparecer no histórico
        $chat = WhatsappChat::firstOrCreate(
            ['tenant_id' => $this->tenantId, 'wa_id' => $sendTo],
            [
                'contact_name'  => $prospect->company_name,
                'contact_phone' => $normalized,
                'status'        => 'open',
                'opt_in_at'     => now(),
            ]
        );

        $chat->update(['last_message_at' => now()]);

        WhatsappMessage::create([
            'chat_id'    => $chat->id,
            'message_id' => $res['key']['id'] ?? ('PROSPECT_' . uniqid()),
            'content'    => $this->message,
            'direction'  => 'outbound',
            'type'       => 'text',
        ]);
    }
}
