<?php

namespace App\Jobs;

use App\Models\LandingPage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Dispara `landing:provision-domain {id}` numa queue pra nao travar
 * o request HTTP do super_admin que clicou "Ativar". Certbot pode
 * levar 30-90s por dominio.
 *
 * Ao concluir, notifica o admin do tenant via bell notification.
 */
class ProvisionLandingDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // certbot + nginx reload cabe em 3min
    public $tries   = 1;   // idempotencia via --force manual se precisar

    public function __construct(public int $landingId)
    {
    }

    public function handle(): void
    {
        $exitCode = Artisan::call('landing:provision-domain', [
            'id' => $this->landingId,
        ]);

        $lp = LandingPage::withoutGlobalScopes()->find($this->landingId);
        if (!$lp) {
            Log::warning("[cd-job] LP #{$this->landingId} sumiu antes do notify");
            return;
        }

        $success = $exitCode === 0 && $lp->custom_domain_status === 'active';

        try {
            $recipients = User::withoutGlobalScopes()
                ->where('tenant_id', $lp->tenant_id)
                ->where('role', 'ngo')
                ->pluck('id');

            foreach ($recipients as $uid) {
                Notification::create([
                    'user_id' => $uid,
                    'title'   => $success ? 'Domínio próprio ativado' : 'Falha ao ativar domínio próprio',
                    'message' => $success
                        ? "Seu domínio {$lp->custom_domain} está funcionando. Acesse em https://{$lp->custom_domain}."
                        : "Falha ao provisionar {$lp->custom_domain}: " . substr((string) $lp->custom_domain_error, 0, 200),
                    'type'    => $success ? 'success' : 'error',
                    'link'    => route('landing-pages.builder', $lp->id),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[cd-job] notify failed: ' . $e->getMessage());
        }
    }
}
