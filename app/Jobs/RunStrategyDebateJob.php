<?php

namespace App\Jobs;

use App\Models\StrategySession;
use App\Services\StrategyRoom\StrategyDebateOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 2. Roda o debate em queue.
 *
 * Controller cria uma StrategySession vazia em_andamento e dispatcha esse job.
 * A view do detalhe faz polling (auto-refresh) ate o status virar concluida.
 *
 * Timeout: 180s cobre com folga um debate tipico de ~40-90s no atual
 * mix flash x3 + pro. Se explodir, marca session como concluida com erro.
 */
class RunStrategyDebateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries   = 1;

    public function __construct(
        public int $tenantId,
        public int $sessionId,
    ) {}

    public function handle(StrategyDebateOrchestrator $orchestrator): void
    {
        try {
            $orchestrator->run($this->tenantId, $this->sessionId);
        } catch (\Throwable $e) {
            Log::error('StrategyRoom/Job: debate falhou', [
                'session_id' => $this->sessionId,
                'tenant_id'  => $this->tenantId,
                'err'        => $e->getMessage(),
            ]);
            // Garante que a session nao fica em_andamento pra sempre
            StrategySession::withoutGlobalScopes()
                ->where('id', $this->sessionId)
                ->update(['status' => 'concluida']);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('StrategyRoom/Job: failed()', [
            'session_id' => $this->sessionId, 'err' => $e->getMessage(),
        ]);
        StrategySession::withoutGlobalScopes()
            ->where('id', $this->sessionId)
            ->update(['status' => 'concluida']);
    }
}
