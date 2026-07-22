<?php

namespace App\Jobs;

use App\Mail\DocumentoVencendoMail;
use App\Models\Attachment;
use App\Models\RegraAvaliacao;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertaDocumentoVencendoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 180;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // Todas as regras com tipo_documento obrigatório e prazo de alerta
        $regras = RegraAvaliacao::whereNotNull('tipo_documento_obrigatorio')
            ->whereNotNull('alerta_dias_antes')
            ->get();

        if ($regras->isEmpty()) {
            return;
        }

        $tenants = Tenant::withoutGlobalScopes()
            ->where('subscription_status', 'active')
            ->pluck('id');

        foreach ($tenants as $tenantId) {
            $admin = User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('role', 'ngo')
                ->first();

            if (! $admin) {
                continue;
            }

            foreach ($regras as $regra) {
                $this->processarRegra($tenantId, $admin, $regra);
            }
        }
    }

    private function processarRegra(int $tenantId, User $admin, RegraAvaliacao $regra): void
    {
        $alertaDias = $regra->alerta_dias_antes;
        $limite     = now()->addDays($alertaDias);

        $docs = Attachment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('tipo_documento', $regra->tipo_documento_obrigatorio)
            ->whereNull('deleted_at')
            ->whereNotNull('valid_until')
            ->whereNull('alerta_enviado_em')
            ->where('valid_until', '<=', $limite)
            ->get();

        foreach ($docs as $doc) {
            $diasRestantes = (int) now()->diffInDays($doc->valid_until, false);

            try {
                Mail::to($admin->email)->send(
                    new DocumentoVencendoMail($admin, $doc, $regra, $diasRestantes)
                );

                $doc->update(['alerta_enviado_em' => now()]);

                Log::info("AlertaDocumento: tenant={$tenantId} tipo={$regra->tipo_documento_obrigatorio} dias={$diasRestantes}");
            } catch (\Throwable $e) {
                Log::error("AlertaDocumento: falha ao enviar para tenant={$tenantId}: {$e->getMessage()}");
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("AlertaDocumentoVencendoJob falhou: {$e->getMessage()}");
    }
}
