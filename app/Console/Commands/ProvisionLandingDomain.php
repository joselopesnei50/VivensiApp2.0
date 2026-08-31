<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use App\Services\LandingDomainProvisioner;
use Illuminate\Console\Command;

/**
 * Orquestra provisionamento de custom domain pra uma landing page:
 *   1. Valida DNS aponta pro VPS
 *   2. Emite cert Let's Encrypt via certbot
 *   3. Escreve server block nginx via template
 *   4. Valida nginx -t
 *   5. Reload nginx
 *   6. Testa HTTPS
 *   7. Marca status=active + ssl_expires_at
 *
 * Idempotente com --force. Sem flag, para se ja active.
 * Cada step atualiza custom_domain_status; falha marca 'failed' +
 * mensagem em custom_domain_error pra UX do Fase 3 mostrar.
 */
class ProvisionLandingDomain extends Command
{
    protected $signature   = 'landing:provision-domain {id : ID da landing page} {--dry-run : So valida DNS e sai} {--force : Re-provisiona mesmo se ja active}';
    protected $description = 'Provisiona custom domain (dig + certbot + nginx) pra uma landing page';

    public function handle(LandingDomainProvisioner $p): int
    {
        /** @var LandingPage $lp */
        $lp = LandingPage::withoutGlobalScopes()->find($this->argument('id'));

        if (!$lp) {
            $this->error("LP #{$this->argument('id')} nao encontrada.");
            return self::FAILURE;
        }

        if (!$lp->custom_domain) {
            $this->error("LP #{$lp->id} nao tem custom_domain configurado.");
            return self::FAILURE;
        }

        if ($lp->custom_domain_status === 'active' && !$this->option('force')) {
            $this->warn("LP #{$lp->id} ja provisionada em {$lp->custom_domain}. Use --force pra re-provisionar.");
            return self::SUCCESS;
        }

        $domain = $lp->custom_domain;

        $this->info("Provisionando {$domain} pra LP #{$lp->id}...");
        $lp->update(['custom_domain_status' => 'verifying', 'custom_domain_error' => null]);

        // 1. DNS
        $dns = $p->validateDns($domain);
        $this->line(" [dns] " . $dns['message']);
        if (!$dns['ok']) {
            return $this->failWith($lp, "DNS: {$dns['message']}");
        }

        if ($this->option('dry-run')) {
            $lp->update(['custom_domain_status' => 'pending']);
            $this->info('DRY-RUN: DNS ok. Nao rodou certbot/nginx.');
            return self::SUCCESS;
        }

        // 2. Cert
        $cert = $p->issueCertificate($domain);
        $this->line(" [cert] " . ($cert['ok'] ? 'ok' : 'FAILED'));
        if (!$cert['ok']) {
            return $this->failWith($lp, "Certbot: " . substr($cert['message'], 0, 500));
        }

        // 3. nginx write
        $write = $p->writeNginxConfig((int) $lp->id, $domain);
        $this->line(" [nginx-write] " . $write['message']);
        if (!$write['ok']) {
            return $this->failWith($lp, "nginx write: {$write['message']}");
        }

        // 4. nginx -t
        $test = $p->testNginxConfig();
        $this->line(" [nginx-test] " . ($test['ok'] ? 'ok' : 'FAILED'));
        if (!$test['ok']) {
            $p->removeNginxConfig((int) $lp->id); // rollback
            return $this->failWith($lp, "nginx -t: {$test['message']}");
        }

        // 5. reload
        $reload = $p->reloadNginx();
        $this->line(" [nginx-reload] " . ($reload['ok'] ? 'ok' : 'FAILED'));
        if (!$reload['ok']) {
            return $this->failWith($lp, "nginx reload: {$reload['message']}");
        }

        // 6. HTTPS test
        $https = $p->testHttps($domain);
        $this->line(" [https] " . $https['message']);
        if (!$https['ok']) {
            return $this->failWith($lp, "HTTPS test: {$https['message']}");
        }

        // 7. Success
        $lp->update([
            'custom_domain_status'         => 'active',
            'custom_domain_ssl_expires_at' => now()->addDays(90),
            'custom_domain_error'          => null,
        ]);

        $this->info("SUCESSO: LP #{$lp->id} servindo em https://{$domain}");
        return self::SUCCESS;
    }

    private function failWith(LandingPage $lp, string $error): int
    {
        $lp->update(['custom_domain_status' => 'failed', 'custom_domain_error' => $error]);
        $this->error($error);
        return self::FAILURE;
    }
}
