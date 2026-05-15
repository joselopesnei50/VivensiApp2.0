<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LgpdBreachNotification extends Model
{
    protected $fillable = [
        'title', 'description', 'severity', 'status',
        'occurred_at', 'identified_at', 'anpd_notified_at',
        'anpd_required', 'affected_data_types', 'estimated_affected_count',
        'reported_by', 'remediation_actions',
    ];

    protected $casts = [
        'occurred_at'      => 'datetime',
        'identified_at'    => 'datetime',
        'anpd_notified_at' => 'datetime',
        'anpd_required'    => 'boolean',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function isOverdue(): bool
    {
        if (!$this->anpd_required || $this->anpd_notified_at) {
            return false;
        }
        // LGPD: 2 dias úteis a partir da identificação
        return $this->identified_at->diffInHours(now()) > 48;
    }
}
