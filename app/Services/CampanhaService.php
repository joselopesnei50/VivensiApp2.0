<?php

namespace App\Services;

use App\Exceptions\ContatoSemOptInException;
use App\Jobs\EnviarMensagemCampanhaJob;
use App\Models\Campanha;
use App\Models\ContatoWhatsapp;

class CampanhaService
{
    public function disparar(Campanha $campanha): void
    {
        $total = ContatoWhatsapp::ativos()
            ->where('tenant_id', $campanha->tenant_id)
            ->count();

        if ($total === 0) {
            $campanha->update(['status' => 'concluida']);
            return;
        }

        $campanha->update([
            'status'         => 'processando',
            'total_contatos' => $total,
            'total_enviados' => 0,
            'total_falhas'   => 0,
        ]);

        EnviarMensagemCampanhaJob::dispatch($campanha->id)
            ->onQueue('whatsapp');
    }

    public function personalizarMensagem(string $mensagem, ContatoWhatsapp $contato): string
    {
        return str_replace(
            ['{nome}', '{telefone}'],
            [$contato->nome ?? 'amigo(a)', $contato->telefone],
            $mensagem
        );
    }

    public function validarOptIn(ContatoWhatsapp $contato): void
    {
        if (!$contato->podeReceberMensagem()) {
            throw new ContatoSemOptInException(
                "Contato {$contato->telefone} não possui opt-in ativo."
            );
        }
    }
}
