<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Models\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Correcao unica dos posts agendados que ficaram gravados em UTC quando o
 * app.timezone e America/Sao_Paulo. Ver ScheduledPostController::store fix
 * de 2026-08-11 — os novos posts ja sao gravados em BRT, este comando sobe
 * os antigos pra mesma referencia.
 *
 * Uso:
 *   php artisan posts:fix-timezone --dry-run
 *   php artisan posts:fix-timezone --confirm
 *
 * Idempotente via flag posts_timezone_backfill_done na SystemSetting.
 */
class FixScheduledPostsTimezone extends Command
{
    protected $signature   = 'posts:fix-timezone {--dry-run} {--confirm} {--force}';
    protected $description = 'Subtrai 3h dos scheduled_at gravados em UTC (bug pre-2026-08-11)';

    private const FLAG_KEY = 'posts_timezone_backfill_done';

    public function handle(): int
    {
        $done = SystemSetting::getValue(self::FLAG_KEY);
        if ($done && !$this->option('force')) {
            $this->warn("Backfill ja foi executado em {$done}. Use --force pra rodar de novo.");
            return self::SUCCESS;
        }

        $candidates = ScheduledPost::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at')
            ->get(['id', 'tenant_id', 'scheduled_at', 'caption']);

        if ($candidates->isEmpty()) {
            $this->info('Nenhum post agendado futuro pra corrigir.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Tenant', 'scheduled_at (atual)', 'scheduled_at (corrigido -3h)', 'caption'],
            $candidates->map(fn ($p) => [
                $p->id,
                $p->tenant_id,
                $p->scheduled_at->format('Y-m-d H:i'),
                $p->scheduled_at->copy()->subHours(3)->format('Y-m-d H:i'),
                \Illuminate\Support\Str::limit($p->caption, 40),
            ])->all()
        );

        $count = $candidates->count();

        if ($this->option('dry-run')) {
            $this->info("DRY-RUN: {$count} post(s) seriam ajustados. Rode com --confirm pra aplicar.");
            return self::SUCCESS;
        }

        if (!$this->option('confirm')) {
            $this->error('Rode com --dry-run pra ver o impacto ou --confirm pra aplicar.');
            return self::FAILURE;
        }

        $updated = DB::transaction(function () use ($candidates) {
            $n = 0;
            foreach ($candidates as $p) {
                ScheduledPost::withoutGlobalScopes()
                    ->where('id', $p->id)
                    ->update(['scheduled_at' => $p->scheduled_at->copy()->subHours(3)]);
                $n++;
            }
            return $n;
        });

        SystemSetting::setValue(self::FLAG_KEY, now()->toDateTimeString());

        $this->info("OK: {$updated} post(s) corrigido(s). Flag " . self::FLAG_KEY . ' gravada.');
        return self::SUCCESS;
    }
}
