<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_PENDING      = 'pending';
    public const STATUS_CONFIRMED    = 'confirmed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BLOCKED      = 'blocked';

    protected $fillable = [
        'tenant_id',
        'whatsapp_chat_id',
        'name',
        'phone',
        'phone_normalized',
        'email',
        'city',
        'tags',
        'meta',
        'status',
        'consent_origin',
        'consent_at',
        'consent_ip',
        'consent_user_agent',
        'double_opt_in_at',
        'unsubscribed_at',
        'last_interaction_at',
    ];

    protected $casts = [
        'tags'                => 'array',
        'meta'                => 'array',
        'consent_at'          => 'datetime',
        'double_opt_in_at'    => 'datetime',
        'unsubscribed_at'     => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsappChat::class, 'whatsapp_chat_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(LeadConsent::class)->orderByDesc('recorded_at');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(LeadTimelineItem::class)->orderByDesc('created_at');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isOptedOut(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED || $this->unsubscribed_at !== null;
    }
}
