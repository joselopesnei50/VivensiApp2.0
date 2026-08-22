<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BroadcastCampaign extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'created_by', 'name', 'message', 'has_image', 'image_path',
        'has_audio', 'audio_path', 'audio_mime', 'audio_fingerprint',
        'audience_type', 'status', 'scheduled_at', 'cadence',
        'group_ids', 'group_send_mode', 'phones', 'label_ids',
        'send_channel', 'template_id', 'template_variables',
        'total_sent', 'total_failed', 'total_skipped', 'actual_recipients',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'has_image'          => 'boolean',
        'has_audio'          => 'boolean',
        'group_ids'          => 'array',
        'label_ids'          => 'array',
        'template_variables' => 'array',
        'cadence'            => 'integer',
        'scheduled_at'       => 'datetime',
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    /** Canais de envio suportados. */
    public const CHANNEL_EVOLUTION         = 'evolution';
    public const CHANNEL_CLOUD_API_TEMPLATE = 'cloud_api_template';

    /** Regras de seguranca do broadcast de audio (2026-08-05, revisao 2). */
    public const AUDIO_MIN_CADENCE_SECONDS      = 20;
    public const AUDIO_MAX_RECIPIENTS_PER_CAMPAIGN = 100; // audience individual
    public const AUDIO_MAX_GROUPS_PER_CAMPAIGN  = 10;     // audience=groups
    public const AUDIO_MAX_FILE_KB              = 16384;  // 16 MB (limite Meta)
    public const AUDIO_MAX_SAME_FINGERPRINT_DAY = 3;
    public const AUDIO_ACTIVE_WINDOW_HOURS      = 24;     // so vale para audience individual

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

    public function template()
    {
        return $this->belongsTo(\App\Models\WhatsappTemplate::class, 'template_id');
    }
}
