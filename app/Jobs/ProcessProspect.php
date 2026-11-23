<?php

namespace App\Jobs;

use App\Models\Prospect;
use App\Models\SystemSetting;
use App\Services\GeminiAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Análise do lead pela Bruce AI (Gemini): nota, alinhamento e pitch.
 *
 * 2026-11-22: uma busca gerava 20 jobs ao mesmo tempo; o Gemini recusava
 * (limite por minuto) e o job tentava de novo na hora — falhavam todos.
 * Agora as análises saem espaçadas (dispatchSpread) e, se falhar, o job
 * espera 1 e 5 minutos antes de tentar de novo.
 */
class ProcessProspect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Intervalo entre análises do mesmo cliente (Gemini gratuito: ~10 por minuto). */
    public const SPREAD_SECONDS = 7;

    public int $tries = 3;

    public int $timeout = 120;

    /** Espera antes de cada nova tentativa (segundos). */
    public array $backoff = [60, 300];

    protected int $prospectId;

    public function __construct(Prospect $prospect)
    {
        $this->prospectId = $prospect->id;
    }

    /** Põe a análise na fila espaçada das outras do mesmo cliente. */
    public static function dispatchSpread(Prospect $prospect): void
    {
        $key = "prospect.spread.{$prospect->tenant_id}";
        $now = now()->getTimestamp();
        $slot = max($now, (int) Cache::get($key, 0));
        Cache::put($key, $slot + self::SPREAD_SECONDS, now()->addHour());

        static::dispatch($prospect)->onQueue('default')->delay(now()->addSeconds($slot - $now));
    }

    public function handle(GeminiAnalysisService $ai)
    {
        $prospect = Prospect::withoutGlobalScope('tenant')->find($this->prospectId);

        if (! $prospect) {
            Log::warning("ProcessProspect: Prospect #{$this->prospectId} não encontrado, job ignorado.");

            return;
        }

        if ($prospect->status === 'analyzed') {
            return; // já analisado, ignora
        }

        // Sem chave não adianta tentar: o lead fica "raw" para analisar depois
        // (botão Analisar todos), sem encher a lista de jobs falhados.
        if (! SystemSetting::getValue('gemini_api_key')) {
            Log::warning("ProcessProspect: chave do Gemini não configurada; lead #{$prospect->id} ficou sem análise.");

            return;
        }

        $ai->analyze($prospect);
    }
}
