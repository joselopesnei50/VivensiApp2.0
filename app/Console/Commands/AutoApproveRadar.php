<?php

namespace App\Console\Commands;

use App\Services\Radar\AutoApproveService;
use Illuminate\Console\Command;

class AutoApproveRadar extends Command
{
    protected $signature = 'radar:auto-approve
                            {--dry-run : Mostra candidatos sem aprovar}';

    protected $description = 'Auto-aprova findings do Radar que atendem critérios de qualidade';

    public function handle(AutoApproveService $service): int
    {
        if (! config('radar.enabled')) {
            $this->warn('Radar desativado.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->showDryRun($service);
            return self::SUCCESS;
        }

        $approved = $service->run();
        $this->info("{$approved} findings auto-aprovados.");

        return self::SUCCESS;
    }

    private function showDryRun(AutoApproveService $service): void
    {
        $this->line('<options=bold>DRY RUN — nenhuma aprovação será feita</>');
        $this->line('');
        $this->line('<comment>Qualidade por keyword:</comment>');

        foreach ($service->keywordQualityStats() as $kw => $s) {
            $flag = $s['flagged'] ? ' ⚠️  SINALIZADA' : '';
            $this->line("  • {$kw}: {$s['total']} feedbacks | útil {$s['util_count']} | não útil {$s['nao_util_count']}{$flag}");
        }
    }
}
