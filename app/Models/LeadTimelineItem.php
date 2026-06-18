<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTimelineItem extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_NOTE              = 'note';
    public const TYPE_WHATSAPP_MESSAGE  = 'whatsapp_message';
    public const TYPE_FORM_COMPLETED    = 'form_completed';
    public const TYPE_CONSENT           = 'consent';
    public const TYPE_NEXT_ACTION       = 'next_action';
    public const TYPE_AI_SUGGESTION     = 'ai_suggestion';
    public const TYPE_STATUS_CHANGE     = 'status_change';

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'author_id',
        'type',
        'body',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
