<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use App\Services\LandingDomainProvisioner;
use Illuminate\Console\Command;

/**
 * Remove config nginx do custom domain (cliente cancelou add-on ou trocou
 * de dominio). Cert Let's Encrypt fica em /etc/letsencrypt/live/ pra
 * renovacao automatica parar sozinha em 90d (nao revoga — free storage).
 *
 * Se cliente cancelou add-on: recomendo grace period de 90d antes de
 * unprovisionar (cert dura 90d, dominio serve ate expirar).
 */
class UnprovisionLandingDomain extends Command
{
    protected $signature   = 'landing:unprovision-domain {id : ID da landing page} {--keep-cert : Nao remove cert do Lets Encrypt}';
    protected $description = 'Remove custom domain (nginx config) de uma landing page';

    public function handle(LandingDomainProvisioner $p): int
    {
        /** @var LandingPage $lp */
        $lp = LandingPage::withoutGlobalScopes()->find($this->argument('id'));

        if (!$lp) {
            $this->error("LP #{$this->argument('id')} nao encontrada.");
            return self::FAILURE;
        }

        $domain = $lp->custom_domain;

        // Remove nginx config
        $remove = $p->removeNginxConfig((int) $lp->id);
        $this->line(" [nginx-remove] " . $remove['message']);

        // Reload nginx (nao critico se falhar; config removida ja tirou o site do ar)
        $reload = $p->reloadNginx();
        $this->line(" [nginx-reload] " . ($reload['ok'] ? 'ok' : 'FAILED (nao critico)'));

        // Zera colunas da LP
        $lp->update([
            'custom_domain'                => null,
            'custom_domain_status'         => null,
            'custom_domain_ssl_expires_at' => null,
            'custom_domain_error'          => null,
        ]);

        $this->info("LP #{$lp->id} desprovisionada. Dominio {$domain} nao serve mais.");
        return self::SUCCESS;
    }
}
