<?php

namespace App\Observers;

use App\Models\PeriodoFechado;
use App\Models\RegistroRefeicao;
use RuntimeException;

/**
 * Cozinha Solidária — Fase 1.
 *
 * Imutabilidade do lastro. A partir do fechamento do período (cozinha_id,
 * mes), updates diretos são BLOQUEADOS — a única via legítima é o fluxo de
 * estorno via RegistroRefeicaoService::aprovarEstorno() (que usa updateQuietly
 * pra bypassar esta verificação).
 *
 * Delete sempre é bloqueado (registros nunca apagam — só viram 'estornado').
 *
 * Updates feitos com updateQuietly() não disparam observer (Laravel padrão) —
 * é assim que o service mantém capacidade de marcar status='estornado' após
 * fechamento.
 */
class RegistroRefeicaoObserver
{
    public function updating(RegistroRefeicao $registro): void
    {
        if ($this->periodoFechadoPara($registro)) {
            throw new RuntimeException(sprintf(
                'Registro de refeição #%d está em período fechado (%s). ' .
                'Correção apenas via estorno auditado.',
                $registro->id,
                $registro->data_servico?->format('m/Y') ?? '?'
            ));
        }
    }

    public function deleting(RegistroRefeicao $registro): void
    {
        throw new RuntimeException(sprintf(
            'Registro de refeição #%d não pode ser excluído. ' .
            'Use o fluxo de estorno (status=estornado) — preserva a auditoria.',
            $registro->id
        ));
    }

    private function periodoFechadoPara(RegistroRefeicao $registro): bool
    {
        if ($registro->data_servico === null) {
            return false;
        }
        $mes = $registro->data_servico->copy()->startOfMonth()->toDateString();
        return PeriodoFechado::withoutGlobalScopes()
            ->where('cozinha_id', $registro->cozinha_id)
            ->where('mes', $mes)
            ->exists();
    }
}
