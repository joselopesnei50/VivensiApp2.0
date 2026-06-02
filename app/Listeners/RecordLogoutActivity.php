<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class RecordLogoutActivity
{
    public function handle(Logout $event): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $event->user->tenant_id,
                'user_id'        => $event->user->id,
                'event'          => 'logout',
                'auditable_type' => \App\Models\User::class,
                'auditable_id'   => $event->user->id,
                'ip_address'     => request()->ip(),
                'url'            => request()->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record logout: ' . $e->getMessage());
        }
    }
}
