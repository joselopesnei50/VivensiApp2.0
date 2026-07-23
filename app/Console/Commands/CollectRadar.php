<?php

namespace App\Console\Commands;

use App\Jobs\Radar\CollectRadarFindings;
use App\Models\RadarTerritory;
use Illuminate\Console\Command;

class CollectRadar extends Command
{
    protected $signature = 'radar:collect
                            {--territory= : Código IBGE de um território específico}
                            {--sync       : Executa sincronamente em vez de despachar para fila}
                            {--dry-run    : Mostra configuração sem coletar}';

    protected $description = 'Coleta achados de editais públicos (Querido Diário + Transferegov)';

    public function handle(): int
    {
        if (! config('radar.enabled')) {
            $this->warn('Radar desativado. Defina RADAR_ENABLED=true no .env para ativar.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->showDryRun();
            return self::SUCCESS;
        }

        $ibge       = (string) ($this->option('territory') ?? '');
        $territories = $ibge
            ? RadarTerritory::where('ibge_code', $ibge)->where('active', true)->get()
            : RadarTerritory::where('active', true)->get();

        if ($territories->isEmpty()) {
            $this->warn('Nenhum território ativo encontrado.' . ($ibge ? " IBGE: {$ibge}" : ''));
            return self::FAILURE;
        }

        $this->info("Iniciando coleta para {$territories->count()} território(s)...");

        if ($this->option('sync')) {
            app(CollectRadarFindings::class, ['ibgeCode' => $ibge])->handle(
                app(\App\Services\Radar\QueridoDiarioService::class),
                app(\App\Services\Radar\TransferegovService::class),
            );
            $this->info('Coleta síncrona concluída.');
        } else {
            CollectRadarFindings::dispatch($ibge);
            $this->info('Job despachado para a fila. Acompanhe em storage/logs/laravel.log.');
        }

        return self::SUCCESS;
    }

    private function showDryRun(): void
    {
        $this->line('<options=bold>DRY RUN — nenhuma coleta será feita</>');
        $this->line('');
        $this->line('<comment>Palavras-chave configuradas:</comment>');
        foreach (config('radar.keywords', []) as $kw) {
            $this->line("  • {$kw}");
        }
        $this->line('');
        $this->line('<comment>Territórios ativos:</comment>');
        RadarTerritory::where('active', true)->each(function ($t) {
            $ultimo = $t->last_collected_at?->diffForHumans() ?? 'nunca';
            $this->line("  • {$t->name} ({$t->ibge_code}) — última coleta: {$ultimo}");
        });
    }
}
