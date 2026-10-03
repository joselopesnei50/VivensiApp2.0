<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueDiagnoseDoubleOptin extends Command
{
    protected $signature = 'queue:diagnose-double-optin
        {--sample=1 : quantas exceptions recentes mostrar}';

    protected $description = 'Diagnostica falhas em SendDoubleOptInWhatsapp (distribuicao temporal + exception real)';

    public function handle(): int
    {
        $this->info('=== Diagnostico SendDoubleOptInWhatsapp ===');
        $this->newLine();

        $base = DB::table('failed_jobs')
            ->where('payload', 'like', '%SendDoubleOptInWhatsapp%');

        $agg = (clone $base)
            ->selectRaw('MIN(failed_at) as primeiro, MAX(failed_at) as ultimo, COUNT(*) as total')
            ->first();

        if (!$agg || $agg->total === 0) {
            $this->info('Nenhuma falha encontrada.');
            return self::SUCCESS;
        }

        $this->line("Total:    <info>{$agg->total}</info>");
        $this->line("Primeiro: <info>{$agg->primeiro}</info>");
        $this->line("Ultimo:   <info>{$agg->ultimo}</info>");

        $uniqueExceptions = (clone $base)
            ->selectRaw('SUBSTRING(exception, 1, 120) as head, COUNT(*) as c')
            ->groupBy('head')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $this->newLine();
        $this->line('<comment>Top exceptions (prefixo 120 chars):</comment>');
        foreach ($uniqueExceptions as $ex) {
            $this->line("  [{$ex->c}x] " . trim(preg_replace('/\s+/', ' ', $ex->head)));
        }

        $sample = (int) $this->option('sample');
        if ($sample > 0) {
            $recent = (clone $base)
                ->orderByDesc('id')
                ->limit($sample)
                ->get(['id', 'queue', 'failed_at', 'exception']);

            $this->newLine();
            $this->line('<comment>Ultimas ' . $sample . ' falhas (exception completa 2000 chars):</comment>');
            foreach ($recent as $r) {
                $this->newLine();
                $this->line("--- failed_jobs.id={$r->id} queue={$r->queue} failed_at={$r->failed_at} ---");
                $this->line(substr((string) $r->exception, 0, 2000));
            }
        }

        return self::SUCCESS;
    }
}
