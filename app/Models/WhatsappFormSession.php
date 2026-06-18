<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappFormSession extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUS_ABANDONED   = 'abandoned';

    protected $fillable = [
        'tenant_id',
        'chat_id',
        'form_id',
        'current_question_id',
        'started_by',
        'status',
        'started_at',
        'completed_at',
        'lead_id',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(WhatsappForm::class, 'form_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsappChat::class, 'chat_id');
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(WhatsappFormQuestion::class, 'current_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(WhatsappFormAnswer::class, 'session_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
