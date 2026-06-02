<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class RecordFailedLoginActivity
{
    public function handle(Failed $event): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $event->user?->tenant_id,
                'user_id'        => $event->user?->id,
                'event'          => 'failed_login',
                'auditable_type' => \App\Models\User::class,
                'auditable_id'   => $event->user?->id,
                'new_values'     => ['guard' => $event->guard],
                'ip_address'     => request()->ip(),
                'url'            => request()->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record failed login: ' . $e->getMessage());
        }
    }
}
