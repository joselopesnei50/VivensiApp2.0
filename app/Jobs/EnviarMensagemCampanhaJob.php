<?php

namespace App\Jobs;

use App\Models\Campanha;
use App\Models\ContatoWhatsapp;
use App\Models\WhatsappInstance;
use App\Services\CampanhaService;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job legado de disparo de campanhas (modelo Campanha + ContatoWhatsapp).
 *
 * Mantido em paralelo ao ProcessBroadcastCampaignJob enquanto a consolidação
 * dos dois pipelines de WhatsApp não é executada (ver BACKLOG_WHATSAPP_CONSOLIDATION.md).
 *
 * Esta versão integra o AntiBanManager — antes operava com `sleep` cego,
 * expondo o número da instância a risco direto de banimento.
 */
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

        // Aceita 'processando' (primeiro disparo) ou 'pausada' (retomada manual)
        if (!$campanha || !in_array($campanha->status, ['processando', 'pausada'], true)) {
            return;
        }

        if ($campanha->status === 'pausada') {
            $campanha->update(['status' => 'processando']);
        }

        $instance = WhatsappInstance::where('tenant_id', $campanha->tenant_id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            $campanha->update(['status' => 'cancelada']);
            Log::error("EnviarMensagemCampanhaJob: nenhuma instância ativa para tenant {$campanha->tenant_id}");
            return;
        }

        $evo     = new EvolutionApiService($instance);
        $antiBan = new AntiBanManager($evo);

        // Pré-flight: mensagem com URL encurtada é bloqueada antes de qualquer envio
        // (Bitly, TinyURL, etc. — alto risco de ban no WhatsApp não-oficial)
        if ($antiBan->containsBlockedShortener($campanha->mensagem ?? '')) {
            $campanha->update(['status' => 'cancelada']);
            Log::error("EnviarMensagemCampanhaJob: mensagem contém URL encurtada (risco de ban)", [
                'campanha_id' => $campanha->id,
            ]);
            return;
        }

        // Delay orgânico entre mensagens: nunca abaixo de 5s. A configuração
        // do usuário (intervalo_segundos = 1..10) vira o piso de uma faixa
        // aleatória [piso .. piso+10] para parecer cadência humana.
        $minDelay = max(5, (int) $campanha->intervalo_segundos);

        $sentCount         = 0;
        $failedCount       = 0;
        $consecutiveErrors = 0;

        ContatoWhatsapp::ativos()
            ->where('tenant_id', $campanha->tenant_id)
            ->cursor()
            ->each(function (ContatoWhatsapp $contato) use (
                &$sentCount, &$failedCount, &$consecutiveErrors,
                $campanha, $campanhaService, $evo, $antiBan, $instance, $minDelay
            ) {
                // Permitir cancelamento externo durante execução longa
                $campanha->refresh();
                if ($campanha->status !== 'processando') {
                    return false; // break do each
                }

                // ── Anti-ban: janela horária + limite diário/horário ──────
                if (!$antiBan->canSendMessage($instance)) {
                    Log::info("Campanha legada pausada: limite anti-ban atingido", [
                        'campanha_id' => $campanha->id,
                        'enviados'    => $sentCount,
                    ]);
                    $campanha->update(['status' => 'pausada']);
                    return false;
                }

                // Re-verifica opt-in no momento do envio (pode ter mudado desde o start)
                if (!$contato->fresh()->podeReceberMensagem()) {
                    $campanha->increment('total_falhas');
                    $failedCount++;
                    return; // próximo contato
                }

                try {
                    $mensagem = $campanhaService->personalizarMensagem($campanha->mensagem, $contato);

                    // Simula digitação antes do envio (presence "composing" + delay orgânico)
                    $antiBan->simulateHumanTyping($instance, $contato->telefone, $mensagem);

                    // delay=0 pra evitar dupla espera: a digitação simulada já cobre o tempo orgânico
                    $result = $evo->sendMessage($contato->telefone, $mensagem, null, 0);

                    if (isset($result['key']) || isset($result['status'])) {
                        $campanha->increment('total_enviados');
                        $sentCount++;
                        $consecutiveErrors = 0;
                        $antiBan->recordSent($instance); // contabiliza no limite diário/horário
                    } else {
                        $campanha->increment('total_falhas');
                        $failedCount++;
                        $consecutiveErrors++;
                        Log::warning("EnviarMensagemCampanhaJob: falha ao enviar para {$contato->telefone}", [
                            'campanha_id' => $campanha->id,
                            'result'      => $result,
                        ]);

                        // ── Circuit breaker: sinal de ban → restringe instância 24h ──
                        if ($antiBan->isBanSignal($result)) {
                            Log::critical("Campanha legada: sinal de ban detectado — instância restrita 24h, campanha cancelada", [
                                'campanha_id' => $campanha->id,
                                'enviados'    => $sentCount,
                                'response'    => substr(json_encode($result), 0, 300),
                            ]);
                            $antiBan->markAsRestricted($instance, 24);
                            $campanha->update(['status' => 'cancelada']);
                            return false;
                        }

                        // 5 erros consecutivos sem sinal de ban → pausar para revisão manual
                        if ($consecutiveErrors >= 5) {
                            Log::error("Campanha legada pausada: 5 erros consecutivos na API", [
                                'campanha_id' => $campanha->id,
                            ]);
                            $campanha->update(['status' => 'pausada']);
                            return false;
                        }
                    }
                } catch (\Throwable $e) {
                    $campanha->increment('total_falhas');
                    $failedCount++;
                    $consecutiveErrors++;
                    Log::error("EnviarMensagemCampanhaJob: exceção para contato {$contato->id}: " . $e->getMessage());
                }

                // Delay orgânico entre mensagens (substitui o sleep cego antigo)
                sleep(rand($minDelay, $minDelay + 10));

                // Pausa anti-ban: 3-5 minutos a cada 30 mensagens
                if ($sentCount > 0 && $sentCount % 30 === 0) {
                    $pause = rand(180, 300);
                    Log::info("Campanha legada: pausa anti-ban ({$pause}s) após 30 msgs", [
                        'campanha_id' => $campanha->id,
                        'enviados'    => $sentCount,
                    ]);
                    sleep($pause);
                }
            });

        // Conclui só se não foi pausada/cancelada durante o loop
        $campanha->refresh();
        if ($campanha->status === 'processando') {
            $campanha->update(['status' => 'concluida']);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("EnviarMensagemCampanhaJob falhou definitivamente", [
            'campanha_id' => $this->campanhaId,
            'error'       => $e->getMessage(),
        ]);

        Campanha::where('id', $this->campanhaId)
            ->whereIn('status', ['processando', 'pausada'])
            ->update(['status' => 'cancelada']);
    }
}
