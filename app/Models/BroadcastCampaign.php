<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BroadcastCampaign extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'created_by', 'name', 'message', 'has_image', 'image_path',
        'audience_type', 'status', 'scheduled_at', 'cadence',
        'group_ids', 'group_send_mode', 'phones', 'label_ids',
        'total_sent', 'total_failed', 'total_skipped', 'actual_recipients',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'has_image'    => 'boolean',
        'group_ids'    => 'array',
        'label_ids'    => 'array',
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) return null;
        $seconds = $this->completed_at->diffInSeconds($this->started_at);
        if ($seconds < 60) return "{$seconds}s";
        $minutes = floor($seconds / 60);
        $rem = $seconds % 60;
        return $rem > 0 ? "{$minutes}m {$rem}s" : "{$minutes}m";
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
