<?php

namespace App\Jobs;

use App\Models\Campanha;
use App\Models\ContatoWhatsapp;
use App\Models\WhatsappInstance;
use App\Services\CampanhaService;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarMensagemCampanhaJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 1;

    public int $campanhaId;

    public function __construct(int $campanhaId)
    {
        $this->campanhaId = $campanhaId;
    }

    public function uniqueId(): string
    {
        return 'campanha_' . $this->campanhaId;
    }

    public function handle(CampanhaService $campanhaService): void
    {
        $campanha = Campanha::find($this->campanhaId);

        if (!$campanha || $campanha->status !== 'processando') {
            return;
        }

        $instance = WhatsappInstance::where('tenant_id', $campanha->tenant_id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            $campanha->update(['status' => 'cancelada']);
            Log::error("EnviarMensagemCampanhaJob: nenhuma instância ativa para tenant {$campanha->tenant_id}");
            return;
        }

        $evo = new EvolutionApiService($instance);

        ContatoWhatsapp::ativos()
            ->where('tenant_id', $campanha->tenant_id)
            ->cursor()
            ->each(function (ContatoWhatsapp $contato) use ($campanha, $campanhaService, $evo) {
                // Re-verificar opt-in no momento do envio
                if (!$contato->fresh()->podeReceberMensagem()) {
                    $campanha->increment('total_falhas');
                    return;
                }

                try {
                    $mensagem = $campanhaService->personalizarMensagem($campanha->mensagem, $contato);
                    $result   = $evo->sendMessage($contato->telefone, $mensagem);

                    if (isset($result['key']) || isset($result['status'])) {
                        $campanha->increment('total_enviados');
                    } else {
                        $campanha->increment('total_falhas');
                        Log::warning("EnviarMensagemCampanhaJob: falha ao enviar para {$contato->telefone}", ['result' => $result]);
                    }
                } catch (\Throwable $e) {
                    $campanha->increment('total_falhas');
                    Log::error("EnviarMensagemCampanhaJob: exceção para contato {$contato->id}: " . $e->getMessage());
                }

                if ($campanha->intervalo_segundos > 0) {
                    sleep($campanha->intervalo_segundos);
                }
            });

        $campanha->update(['status' => 'concluida']);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("EnviarMensagemCampanhaJob falhou definitivamente", [
            'campanha_id' => $this->campanhaId,
            'error'       => $e->getMessage(),
        ]);

        Campanha::where('id', $this->campanhaId)
            ->where('status', 'processando')
            ->update(['status' => 'cancelada']);
    }
}
