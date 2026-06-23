<?php

namespace App\Observers;

use App\Models\Cozinha;
use App\Models\TermoColaboracao;
use Illuminate\Support\Facades\Log;

/**
 * R3 do mds-compliance-guardian: na execução direta, a gestora é uma cozinha
 * de equipamento próprio — gestora e cozinha colapsam num cadastro só
 * (roadmap §2). Quando um Termo nasce com modalidade_execucao=direta,
 * criamos a Cozinha vinculada automaticamente, evitando inconsistência
 * (Termo direta sem nenhuma cozinha = dado de execução sem onde plugar).
 *
 * Disparo apenas em created — alterações de modalidade depois exigem ação
 * explícita do gestor (não vamos criar Cozinha "fantasma" num update).
 */
class TermoColaboracaoObserver
{
    public function created(TermoColaboracao $termo): void
    {
        if (!$termo->isDireta()) {
            return;
        }

        try {
            // Se por algum motivo já tem uma cozinha vinculada (criação manual
            // simultânea), mantém — não duplica.
            if ($termo->cozinhas()->withoutGlobalScopes()->exists()) {
                return;
            }

            Cozinha::create([
                'tenant_id'           => $termo->tenant_id,
                'termo_id'            => $termo->id,
                'nome'                => $termo->tenant?->name
                    ?? ('Cozinha direta — Termo ' . $termo->numero),
                'meta_refeicoes_mes'  => 0,
                'modalidade_execucao' => Cozinha::MODALIDADE_DIRETA,
                'status'              => Cozinha::STATUS_ATIVA,
            ]);
        } catch (\Throwable $e) {
            // Falha silenciosa pra não bloquear criação do Termo; loga pra
            // diagnóstico. UX consegue mostrar "criar cozinha" como ação manual.
            Log::warning('TermoColaboracaoObserver: falha ao auto-criar Cozinha direta', [
                'termo_id' => $termo->id,
                'tenant_id' => $termo->tenant_id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
