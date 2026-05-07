<?php

namespace App\Console\Commands;

use App\Models\ScheduledWhatsappMessage;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledWhatsappMessages extends Command
{
    protected $signature   = 'whatsapp:send-scheduled';
    protected $description = 'Process and send all due scheduled WhatsApp messages with anti-ban randomized delay.';

    public function handle(): void
    {
        // 1. OmniChannel Individual Messages
        $dueMessages = ScheduledWhatsappMessage::where('status', 'pending')
            ->where('scheduled_at', '<=', Carbon::now())
            ->with('chat')
            ->limit(30)
            ->get();

        if ($dueMessages->isNotEmpty()) {
            $this->info("Processando {$dueMessages->count()} mensagens individuais agendadas...");
            $this->processIndividualMessages($dueMessages);
        }

        // 2. Broadcast Campaigns
        $dueCampaigns = \App\Models\BroadcastCampaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', Carbon::now())
            ->get();

        if ($dueCampaigns->isNotEmpty()) {
            $this->info("Iniciando {$dueCampaigns->count()} campanhas de disparo em massa agendadas...");
            foreach ($dueCampaigns as $campaign) {
                \App\Jobs\ProcessBroadcastCampaignJob::dispatch($campaign->id);
                $campaign->update(['status' => 'processing']);
            }
        }

        $this->info('Processamento concluído.');
    }

    protected function processIndividualMessages($due)
    {
        foreach ($due as $scheduled) {
            try {
                $chat     = $scheduled->chat;
                $tenantId = $scheduled->tenant_id;

                if (!$chat) {
                    $scheduled->update(['status' => 'failed', 'error_message' => 'Chat não encontrado.']);
                    continue;
                }

                if ($chat->blocked_at || $chat->opt_out_at) {
                    $scheduled->update(['status' => 'cancelled', 'error_message' => 'Contato com opt-out ou bloqueado.']);
                    continue;
                }

                $instance = WhatsappInstance::where('tenant_id', $tenantId)
                    ->where('status', 'open')
                    ->first();

                if (!$instance) {
                    $scheduled->update(['status' => 'failed', 'error_message' => 'Nenhuma instância WA conectada.']);
                    continue;
                }

                $delay = rand(5, 15);
                sleep($delay);

                $evo = new EvolutionApiService($instance);
                $res = $evo->sendMessage($chat->wa_id, $scheduled->content, null, 0);

                if (isset($res['error'])) {
                    $scheduled->update(['status' => 'failed', 'error_message' => $res['error'] ?? 'Erro na Evolution API']);
                    continue;
                }

                WhatsappMessage::create([
                    'chat_id'    => $chat->id,
                    'message_id' => $res['key']['id'] ?? ('SCHED_' . uniqid()),
                    'content'    => $scheduled->content,
                    'direction'  => 'outbound',
                    'type'       => 'text',
                ]);

                $chat->update(['last_message_at' => now()]);
                $scheduled->update(['status' => 'sent', 'sent_at' => now()]);
                $this->info("✓ Individual #{$scheduled->id} enviada.");

            } catch (\Throwable $e) {
                Log::error("Scheduled individual error #{$scheduled->id}: " . $e->getMessage());
                $scheduled->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }
    }
}
