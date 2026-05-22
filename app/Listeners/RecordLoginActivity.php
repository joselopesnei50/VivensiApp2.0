<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class RecordLoginActivity
{
    public function handle(Login $event): void
    {
        try {
            LoginActivity::record($event->user, true);
            $event->user->update(['last_login_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record login activity: ' . $e->getMessage());
        }
    }
}
