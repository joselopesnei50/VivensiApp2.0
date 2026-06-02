<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'target_name',
        'context',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function record(string $action, array $context = []): void
    {
        try {
            static::create([
                'admin_id'    => auth()->id(),
                'action'      => $action,
                'target_type' => $context['target_type'] ?? 'tenant',
                'target_id'   => $context['target_id'] ?? null,
                'target_name' => $context['target_name'] ?? null,
                'context'     => $context,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::critical('AdminAuditLog falhou ao gravar', [
                'action'  => $action,
                'context' => $context,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
