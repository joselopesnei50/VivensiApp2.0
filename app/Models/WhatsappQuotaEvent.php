<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log imutável de eventos que mudam a cota de um tenant.
 * Cada consumo, pack extra, reset mensal e ajuste manual gera 1 event.
 * Nunca edita/deleta.
 */
class WhatsappQuotaEvent extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_CONSUME    = 'consume';
    public const TYPE_EXTRA_PACK = 'extra_pack';
    public const TYPE_RESET      = 'reset';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'tenant_id',
        'type',
        'conversations_delta',
        'used_after',
        'extra_pack_after',
        'description',
        'whatsapp_conversation_id',
        'actor_user_id',
    ];

    protected $casts = [
        'conversations_delta' => 'integer',
        'used_after'          => 'integer',
        'extra_pack_after'    => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'whatsapp_conversation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** Sinal humano-legível — "-1 conversa" ou "+500 pack extra" */
    public function getSignedLabelAttribute(): string
    {
        $delta = $this->conversations_delta;
        $abs   = abs($delta);
        $sign  = $delta >= 0 ? '+' : '-';
        return "{$sign}{$abs}";
    }
}
