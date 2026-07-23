<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadarNotification extends Model
{
    protected $fillable = [
        'tenant_id',
        'radar_finding_id',
        'channel',
        'sent_at',
        'feedback',
        'feedback_at',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'feedback_at' => 'datetime',
    ];

    public function finding(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RadarFinding::class, 'radar_finding_id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
