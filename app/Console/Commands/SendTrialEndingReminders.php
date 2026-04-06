<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BrevoService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTrialEndingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trials:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to users whose trial is ending in 2 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(BrevoService $brevo)
    {
        // Target date: 2 days from now (start of day to find anyone falling in that window)
        $targetDate = Carbon::now()->addDays(2)->format('Y-m-d');

        $tenants = Tenant::where('subscription_status', 'trialing')
            ->whereDate('trial_ends_at', $targetDate)
            ->with('users')
            ->get();

        $this->info("Found " . $tenants->count() . " tenants with trial ending on $targetDate.");

        foreach ($tenants as $tenant) {
            foreach ($tenant->users as $user) {
                if ($user->role === 'manager') {
                    $this->info("Sending reminder to: {$user->email}");
                    $brevo->sendTrialEndingEmail($user, 2);
                }
            }
        }

        return Command::SUCCESS;
    }
}
