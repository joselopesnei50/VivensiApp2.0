<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckFailedJobs extends Command
{
    protected $signature   = 'queue:alert-failed';
    protected $description = 'Verifica jobs falhados e alerta por e-mail se houver novos desde o último check.';

    public function handle(): int
    {
        $total     = (int) DB::table('failed_jobs')->count();
        $lastTotal = (int) Cache::get('queue.failed_jobs.last_count', 0);
        $newCount  = max(0, $total - $lastTotal);

        if ($newCount === 0) {
            $this->info("Nenhum job novo falhado. Total acumulado: {$total}.");
            return self::SUCCESS;
        }

        // Busca os novos jobs (mais recentes) para incluir no alerta
        $newJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($newCount)
            ->get(['uuid', 'queue', 'payload', 'exception', 'failed_at']);

        $lines = $newJobs->map(function ($job) {
            $class = data_get(json_decode($job->payload, true), 'displayName', 'unknown');
            return "  [{$job->failed_at}] queue={$job->queue} job={$class} uuid={$job->uuid}";
        })->implode("\n");

        Log::critical("Queue: {$newCount} job(s) novo(s) falhado(s).", [
            'total_failed' => $total,
            'new_failed'   => $newCount,
            'jobs'         => $newJobs->toArray(),
        ]);

        $this->error("⚠ {$newCount} job(s) novo(s) falhado(s) (total: {$total}).");
        $this->line($lines);

        $this->sendAlert($newCount, $total, $lines);

        Cache::put('queue.failed_jobs.last_count', $total, now()->addDays(7));

        return self::SUCCESS;
    }

    private function sendAlert(int $newCount, int $total, string $lines): void
    {
        $adminEmail = config('mail.from.address');
        if (!$adminEmail) {
            return;
        }

        try {
            $body = "Vivensi — Alerta de Jobs Falhados\n\n"
                . "Novos jobs falhados: {$newCount}\n"
                . "Total acumulado  : {$total}\n\n"
                . "Detalhes:\n{$lines}\n\n"
                . "Acesse o painel: " . config('app.url') . "/admin/failed-jobs\n";

            Mail::raw($body, function ($msg) use ($newCount, $adminEmail) {
                $msg->to($adminEmail)
                    ->subject("[Vivensi] ⚠ {$newCount} job(s) falhado(s) — " . now()->format('d/m H:i'));
            });
        } catch (\Throwable $e) {
            Log::warning('CheckFailedJobs: falha ao enviar e-mail de alerta.', ['error' => $e->getMessage()]);
        }
    }
}
