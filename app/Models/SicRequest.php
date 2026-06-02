<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SicRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'protocol',
        'requester_name',
        'requester_email',
        'subject',
        'message',
        'status',
        'response',
        'responded_at',
        'deadline_at',
        'responded_by',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'deadline_at'  => 'date',
    ];

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function generateProtocol(int $tenantId): string
    {
        $year = now()->year;
        do {
            $code = strtoupper(Str::random(6));
            $protocol = "SIC-{$year}-{$code}";
        } while (static::where('protocol', $protocol)->exists());

        return $protocol;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Aguardando',
            'in_review' => 'Em análise',
            'answered'  => 'Respondida',
            'denied'    => 'Negada',
            'closed'    => 'Encerrada',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => '#ca8a04',
            'in_review' => '#2563eb',
            'answered'  => '#16a34a',
            'denied'    => '#dc2626',
            'closed'    => '#64748b',
            default     => '#64748b',
        };
    }

    public function isOverdue(): bool
    {
        return !in_array($this->status, ['answered', 'closed', 'denied'])
            && $this->deadline_at
            && $this->deadline_at->isPast();
    }
}
