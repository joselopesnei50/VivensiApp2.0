<?php

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionCancellation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class FinishCanceledSubscriptions extends Command
{
    protected $signature = 'subscriptions:finish-canceled';
    protected $description = 'Encerra o acesso de quem cancelou e chegou ao fim do período pago; avisa o suporte das contas canceladas há mais de 90 dias';

    public function handle(SubscriptionCancellation $service): int
    {
        $finished = $service->finishExpired();
        $this->info("Acessos encerrados: {$finished}");

        // Só às segundas, para não repetir o aviso todo dia.
        $due = $service->dueForDeletion();
        if ($due->isNotEmpty() && now()->isMonday()) {
            $lines = $due->map(fn ($t) => "#{$t->id} {$t->name} — cancelada em " . $t->canceled_at->format('d/m/Y'))->implode("\n");
            Mail::raw("Organizações canceladas há mais de " . SubscriptionCancellation::RETENTION_DAYS . " dias. "
                . "Pelos Termos de Uso, os dados devem ser apagados (Super Admin → Organizações → Excluir):\n\n" . $lines,
                fn ($m) => $m->to(config('legal.email_support'))->subject('Vivensi: dados de contas canceladas para excluir'));
            $this->info("Aviso de exclusão enviado: {$due->count()} organização(ões).");
        }
        return self::SUCCESS;
    }
}
