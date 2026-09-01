<?php

namespace App\Console\Commands;

use App\Models\LandingPage;
use Illuminate\Console\Command;

/**
 * Diagnostica o estado de uma LP com custom_domain pelo dominio. Uso:
 *   php artisan landing:show-custom-domain coletivobases.com.br
 *
 * Evita paste de comandos longos no tinker (que corrompe em SSH remoto).
 */
class LandingShowCustomDomain extends Command
{
    protected $signature   = 'landing:show-custom-domain {domain : Dominio custom cadastrado na LP}';
    protected $description = 'Mostra dados da LP associada a um custom_domain (diagnostico)';

    public function handle(): int
    {
        $domain = strtolower(trim($this->argument('domain')));

        $lp = LandingPage::withoutGlobalScopes()
            ->where('custom_domain', $domain)
            ->first();

        if (!$lp) {
            $this->error("Nenhuma LP encontrada com custom_domain = {$domain}");
            $this->line('Verifique: o dominio pode ter sido salvo com espaço, maiuscula, ou com/sem www.');
            $this->line("Listando todas as LPs com custom_domain nao-nulo:");
            LandingPage::withoutGlobalScopes()
                ->whereNotNull('custom_domain')
                ->each(fn ($p) => $this->line(sprintf(
                    '  LP #%d | tenant %d | domain=[%s] status=%s',
                    $p->id, $p->tenant_id, $p->custom_domain, $p->custom_domain_status ?? 'null'
                )));
            return self::FAILURE;
        }

        $sectionsCount = $lp->sections()->count();

        $this->info('LP encontrada:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['id',                   $lp->id],
                ['tenant_id',            $lp->tenant_id],
                ['title',                $lp->title],
                ['slug',                 $lp->slug],
                ['status',               $lp->status ?? '(null)'],
                ['custom_domain',        $lp->custom_domain],
                ['custom_domain_status', $lp->custom_domain_status ?? '(null)'],
                ['ssl_expires_at',       $lp->custom_domain_ssl_expires_at?->toIso8601String() ?? '(null)'],
                ['error',                substr((string) $lp->custom_domain_error, 0, 200) ?: '(null)'],
                ['sections_count',       $sectionsCount],
                ['created_at',           $lp->created_at?->toIso8601String()],
                ['updated_at',           $lp->updated_at?->toIso8601String()],
            ]
        );

        // Diagnostico rapido
        $this->line('');
        $this->info('Diagnostico:');
        if ($lp->status !== 'published') {
            $this->warn("  [X] LP status='{$lp->status}' — deve ser 'published'. Va no builder e clique Publicar.");
        } else {
            $this->line('  [ok] LP publicada');
        }
        if ($lp->custom_domain_status !== 'active') {
            $this->warn("  [X] custom_domain_status='{$lp->custom_domain_status}' — deve ser 'active'.");
        } else {
            $this->line('  [ok] custom_domain provisionado (active)');
        }
        if ($sectionsCount === 0) {
            $this->warn('  [X] LP nao tem sections — a pagina publica vai mostrar so o header/footer vazio.');
        } else {
            $this->line("  [ok] LP tem {$sectionsCount} sections");
        }

        return self::SUCCESS;
    }
}
