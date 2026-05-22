<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\BruceAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmCaches extends Command
{
    protected $signature   = 'vivensi:cache-warm {--tenant= : Warm specific tenant ID only}';
    protected $description = 'Pre-warms dashboard and Bruce AI context caches for active tenants';

    public function handle(BruceAiService $bruce): int
    {
        $tenantId = $this->option('tenant');

        $query = Tenant::where('subscription_status', 'active');
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return 0;
        }

        $this->info("Warming caches for {$tenants->count()} tenant(s)...");
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $warmed = 0;
        foreach ($tenants as $tenant) {
            try {
                // 1. Warm Bruce AI tenant context (5 min TTL)
                Cache::forget("bruce.ctx.{$tenant->id}");
                $this->warmBruceContext($tenant->id);

                // 2. Warm Bruce AI daily insight (6h TTL)
                $role = User::where('tenant_id', $tenant->id)
                    ->whereIn('role', ['ngo', 'manager'])
                    ->value('role') ?? 'common';
                $bruce->dailyInsight($tenant->id, $role);

                $warmed++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  Tenant #{$tenant->id} failed: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Warmed {$warmed}/{$tenants->count()} tenants.");

        return 0;
    }

    private function warmBruceContext(int $tenantId): void
    {
        $income  = (float) \App\Models\Transaction::where('tenant_id', $tenantId)->where('type', 'income')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');
        $expense = (float) \App\Models\Transaction::where('tenant_id', $tenantId)->where('type', 'expense')->where('status', 'paid')->whereMonth('date', now()->month)->sum('amount');

        Cache::put("bruce.ctx.{$tenantId}", [
            'income'          => $income,
            'expense'         => $expense,
            'balance'         => $income - $expense,
            'active_projects' => \App\Models\Project::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'open_tasks'      => \App\Models\Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->count(),
            'overdue_tasks'   => \App\Models\Task::where('tenant_id', $tenantId)->whereNotIn('status', ['done', 'completed'])->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
        ], 300);
    }
}
