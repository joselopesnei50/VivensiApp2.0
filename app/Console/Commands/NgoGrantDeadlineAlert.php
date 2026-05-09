<?php

namespace App\Console\Commands;

use App\Models\NgoGrant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NgoGrantDeadlineAlert extends Command
{
    protected $signature   = 'grants:deadline-alert';
    protected $description = 'Envia alertas de email para editais com deadline em 7 dias ou 1 dia';

    public function handle(): int
    {
        $today    = now()->startOfDay();
        $in7days  = now()->addDays(7)->toDateString();
        $in1day   = now()->addDay()->toDateString();

        $grants = NgoGrant::whereNotIn('status', ['closed'])
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(deadline)'), [$in7days, $in1day])
            ->with('project')
            ->get();

        if ($grants->isEmpty()) {
            $this->info('Nenhum edital com deadline próximo hoje.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($grants as $grant) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($grant->deadline);

            $managers = User::where('tenant_id', $grant->tenant_id)
                ->whereIn('role', ['manager', 'ngo', 'super_admin'])
                ->whereNotNull('email')
                ->get();

            foreach ($managers as $manager) {
                try {
                    Mail::to($manager->email)->queue(
                        new \App\Mail\GrantDeadlineAlertMail($grant, $manager, $daysLeft)
                    );
                    $sent++;
                } catch (\Exception $e) {
                    Log::error("grants:deadline-alert — erro ao enviar para {$manager->email}: " . $e->getMessage());
                }
            }
        }

        Log::info("grants:deadline-alert — {$grants->count()} edital(is) próximos, {$sent} email(s) enviado(s).");
        $this->info("{$grants->count()} edital(is) detectados, {$sent} email(s) disparados.");

        return self::SUCCESS;
    }
}
