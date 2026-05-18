<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SchedulerHealthCheck extends Command
{
    protected $signature   = 'scheduler:health';
    protected $description = 'Mostra o estado das campanhas agendadas e confirma timezone do servidor.';

    public function handle(): void
    {
        $now = now();
        $this->info("Hora atual do servidor : " . $now->toDateTimeString());
        $this->info("Timezone da aplicação  : " . config('app.timezone'));

        $scheduled = \App\Models\BroadcastCampaign::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get(['id', 'tenant_id', 'scheduled_at', 'audience_type', 'group_send_mode']);

        $this->info("Campanhas agendadas    : {$scheduled->count()}");
        $this->line('');

        foreach ($scheduled as $c) {
            $overdue = $c->scheduled_at <= $now ? '⚠️  ATRASADA' : '⏰ pendente';
            $this->line("  #{$c->id} | tenant {$c->tenant_id} | {$c->scheduled_at} ({$c->scheduled_at->diffForHumans()}) | {$overdue}");
        }

        Log::info('SchedulerHealthCheck executado', [
            'pending_count' => $scheduled->count(),
            'now'           => $now->toDateTimeString(),
            'timezone'      => config('app.timezone'),
        ]);
    }
}
