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
        // Busca mensagens pendentes com agendamento <= agora (com janela de 10 min para atrasos)
        $due = ScheduledWhatsappMessage::where('status', 'pending')
            ->where('scheduled_at', '<=', Carbon::now())
            ->with('chat')
            ->limit(30) // Limite de segurança por execução
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        $this->info("Processando {$due->count()} mensagens agendadas...");

        foreach ($due as $scheduled) {
            try {
                $chat     = $scheduled->chat;
                $tenantId = $scheduled->tenant_id;

                if (!$chat) {
                    $scheduled->update(['status' => 'failed', 'error_message' => 'Chat não encontrado.']);
                    continue;
                }

                // Compliance: não enviar para bloqueados/opt-out
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

                // Anti-ban: delay aleatório de 5-15 segundos entre mensagens agendadas
                $delay = rand(5, 15);
                sleep($delay);

                $evo = new EvolutionApiService($instance);
                $res = $evo->sendMessage($chat->wa_id, $scheduled->content, null, 0);

                if (isset($res['error'])) {
                    $scheduled->update([
                        'status'        => 'failed',
                        'error_message' => $res['error'] ?? 'Erro na Evolution API',
                    ]);
                    Log::error("Scheduled WA Message #{$scheduled->id} failed", $res);
                    continue;
                }

                $messageId = $res['key']['id'] ?? ('SCHED_' . uniqid());

                WhatsappMessage::create([
                    'chat_id'    => $chat->id,
                    'message_id' => $messageId,
                    'content'    => $scheduled->content,
                    'direction'  => 'outbound',
                    'type'       => 'text',
                ]);

                $chat->update(['last_message_at' => now()]);

                $scheduled->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);

                $this->info("✓ Mensagem #{$scheduled->id} enviada para {$chat->wa_id}");

            } catch (\Throwable $e) {
                Log::error("Scheduled WA error #{$scheduled->id}: " . $e->getMessage());
                $scheduled->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }

        $this->info('Processamento concluído.');
    }
}
