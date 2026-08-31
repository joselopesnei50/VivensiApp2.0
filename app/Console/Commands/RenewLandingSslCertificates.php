<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use App\Models\Notification;
use App\Models\User;
use App\Services\LandingDomainProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Job diario que renova certs proximos de expirar. Certbot renew e
 * idempotente — so mexe em certs a 30d de expirar por default.
 *
 * Cronograma: agendar em app/Console/Kernel.php como daily()->at('03:00').
 *
 * Alerta super_admins via bell notification se qualquer cert falhar
 * (evita site do cliente cair silenciosamente por cert expirado).
 */
class RenewLandingSslCertificates extends Command
{
    protected $signature   = 'landing:renew-ssl-certs {--dry-run}';
    protected $description = 'Renova certs Lets Encrypt de custom domains proximos de expirar (diario 3am)';

    public function handle(LandingDomainProvisioner $p): int
    {
        if ($this->option('dry-run')) {
            $this->info('DRY-RUN: certbot renew --dry-run');
            exec('sudo certbot renew --dry-run 2>&1', $out, $code);
            $this->line(implode("\n", $out));
            return $code === 0 ? self::SUCCESS : self::FAILURE;
        }

        // Renova o batch todo — certbot decide o que ta perto de expirar (default 30d).
        $result = $p->renewCertificates();

        if (!$result['ok']) {
            Log::error('[landing:renew] certbot renew falhou', ['out' => $result['message']]);
            $this->error($result['message']);
            $this->notifySuperAdmins('Renovacao de SSL falhou', $result['message']);
            return self::FAILURE;
        }

        // Atualiza ssl_expires_at de todas as LPs active (assumindo renovadas +90d)
        // Se cert nao foi renovado nesse batch (nao estava a 30d), fica com data velha
        // e sera pego no proximo run.
        LandingPage::withoutGlobalScopes()
            ->where('custom_domain_status', 'active')
            ->whereNotNull('custom_domain_ssl_expires_at')
            ->where('custom_domain_ssl_expires_at', '<', now()->addDays(30))
            ->each(function (LandingPage $lp) {
                $lp->update(['custom_domain_ssl_expires_at' => now()->addDays(90)]);
            });

        $this->info('Renovacao concluida. ' . substr($result['message'], 0, 200));
        return self::SUCCESS;
    }

    private function notifySuperAdmins(string $title, string $body): void
    {
        try {
            User::withoutGlobalScopes()
                ->where('role', 'super_admin')
                ->pluck('id')
                ->each(fn ($uid) => Notification::create([
                    'user_id' => $uid,
                    'title'   => $title,
                    'message' => substr($body, 0, 500),
                    'type'    => 'error',
                    'link'    => '/admin/landings',
                ]));
        } catch (\Throwable $e) {
            Log::error('[landing:renew] notify super_admins falhou: ' . $e->getMessage());
        }
    }
}
