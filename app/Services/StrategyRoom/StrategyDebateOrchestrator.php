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
        private ChiefStrategistAgentService $chefe,
    ) {}

    /**
     * Retorna:
     *   [
     *     'session_id' => int,
     *     'financeiro' => array|null,   // resultado do agente ou null se falhou
     *     'inteligencia' => array|null,
     *     'sintese' => array|null,
     *     'erros' => array,             // lista de erros parciais
     *   ]
     */
    public function run(int $tenantId): array
    {
        $session = StrategySession::create([
            'tenant_id'    => $tenantId,
            'trigger_type' => 'manual_debate',
            'status'       => 'em_andamento',
        ]);

        $out = [
            'session_id'   => $session->id,
            'financeiro'   => null,
            'inteligencia' => null,
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

        // 3) Chefe — so faz sentido se ao menos 1 agente falou
        if ($out['financeiro'] || $out['inteligencia']) {
            $chief = $this->chefe->synthesize($session->id);
            if (isset($chief['error'])) {
                $out['erros'][] = ['agente' => 'estrategista_chefe', 'msg' => $chief['error']];
                Log::warning('StrategyRoom/Orquestrador: chefe falhou', [
                    'session' => $session->id, 'err' => $chief['error'],
                ]);
                // Chefe falhou — fecha sessao manual pra nao ficar em andamento
                $session->update(['status' => 'concluida']);
            } else {
                $out['sintese'] = $chief;
                // Chefe ja fechou a sessao no proprio synthesize()
            }
        } else {
            // Nem financeiro nem inteligencia falaram — fecha e reporta
            $session->update(['status' => 'concluida']);
        }

        return $out;
    }
}
