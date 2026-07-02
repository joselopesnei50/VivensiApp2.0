<?php

namespace App\Services\StrategyRoom;

use App\Models\StrategySession;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1. Orquestrador do debate.
 *
 * Cria UMA sessao, chama Financeiro → Inteligencia → Chefe em sequencia,
 * cada agente registra 1 StrategyMessage. Se algum agente falhar, o
 * debate segue com o que sobrou (o chefe sintetiza o que tiver), mas
 * marca a sessao apropriadamente.
 *
 * Sem paralelismo por agora — cada agente pode ter comportamento
 * observavel afetado pelo anterior (nao neste momento, mas
 * arquiteturalmente ta preparado). Fase 2+ pode paralelizar Financeiro
 * e Inteligencia se a latencia incomodar.
 */
class StrategyDebateOrchestrator
{
    public function __construct(
        private FinancialAgentService $financeiro,
        private IntelligenceAgentService $inteligencia,
        private MobilizationAgentService $mobilizacao,
        private ChiefStrategistAgentService $chefe,
    ) {}

    /**
     * Retorna:
     *   [
     *     'session_id'   => int,
     *     'financeiro'   => array|null,
     *     'inteligencia' => array|null,
     *     'mobilizacao'  => array|null,
     *     'sintese'      => array|null,
     *     'erros'        => array,
     *   ]
     */
    /**
     * $sessionId opcional. Se null, cria nova sessao (CLI). Se dado, usa a
     * existente (job dispatchado pelo controller ja criou a session vazia).
     */
    public function run(int $tenantId, ?int $sessionId = null): array
    {
        $session = $sessionId
            ? StrategySession::withoutGlobalScopes()->findOrFail($sessionId)
            : StrategySession::create([
                'tenant_id'    => $tenantId,
                'trigger_type' => 'manual_debate',
                'status'       => 'em_andamento',
            ]);

        $out = [
            'session_id'   => $session->id,
            'financeiro'   => null,
            'inteligencia' => null,
            'mobilizacao'  => null,
            'sintese'      => null,
            'erros'        => [],
        ];

        // 1) Financeiro
        $fin = $this->financeiro->speak($tenantId, $session->id);
        if (isset($fin['error'])) {
            $out['erros'][] = ['agente' => 'financeiro', 'msg' => $fin['error']];
            Log::warning('StrategyRoom/Orquestrador: financeiro falhou', [
                'session' => $session->id, 'err' => $fin['error'],
            ]);
        } else {
            $out['financeiro'] = $fin;
        }

        // 2) Inteligencia
        $int = $this->inteligencia->speak($tenantId, $session->id);
        if (isset($int['error'])) {
            $out['erros'][] = ['agente' => 'inteligencia', 'msg' => $int['error']];
            Log::warning('StrategyRoom/Orquestrador: inteligencia falhou', [
                'session' => $session->id, 'err' => $int['error'],
            ]);
        } else {
            $out['inteligencia'] = $int;
        }

        // 3) Mobilizacao
        $mob = $this->mobilizacao->speak($tenantId, $session->id);
        if (isset($mob['error'])) {
            $out['erros'][] = ['agente' => 'mobilizacao', 'msg' => $mob['error']];
            Log::warning('StrategyRoom/Orquestrador: mobilizacao falhou', [
                'session' => $session->id, 'err' => $mob['error'],
            ]);
        } else {
            $out['mobilizacao'] = $mob;
        }

        // 4) Chefe — sintetiza se pelo menos 1 agente falou
        if ($out['financeiro'] || $out['inteligencia'] || $out['mobilizacao']) {
            $chief = $this->chefe->synthesize($session->id);
            if (isset($chief['error'])) {
                $out['erros'][] = ['agente' => 'estrategista_chefe', 'msg' => $chief['error']];
                Log::warning('StrategyRoom/Orquestrador: chefe falhou', [
                    'session' => $session->id, 'err' => $chief['error'],
                ]);
                $session->update(['status' => 'concluida']);
            } else {
                $out['sintese'] = $chief;
            }
        } else {
            $session->update(['status' => 'concluida']);
        }

        return $out;
    }
}
