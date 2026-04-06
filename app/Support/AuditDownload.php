<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditDownload
{
    /**
     * Record a download/export event in the audit trail.
     *
     * @param  string  $auditableType  Any string/class name to identify module
     * @param  int|null  $auditableId  Optional ID
     * @param  array  $meta  Extra metadata stored in new_values
     */
    public static function log(string $auditableType, ?int $auditableId, array $meta = []): void
    {
        try {
            AuditLog::create([
                'tenant_id' => auth()->user()->tenant_id ?? 1,
                'user_id' => auth()->id(),
                'event' => 'download',
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'old_values' => null,
                'new_values' => $meta,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
            ]);
        } catch (\Throwable $e) {
            // Never block downloads due to audit failures.
        }
    }
}

