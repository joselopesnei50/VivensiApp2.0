<?php

namespace App\Jobs;

use App\Models\WhatsappInstance;
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

        $phone = preg_replace('/\D/', '', $prospect->phone);
        if (strlen($phone) < 10) return;

        $evo = new EvolutionApiService($instance);
        $res = $evo->sendMessage($phone, $this->message, null, 2);

        if (isset($res['error'])) {
            Log::error("SendProspectWhatsapp failed for {$phone}: " . ($res['error'] ?? ''));
        }
    }
}
