<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Handoff do Bruno pro atendimento humano. Criado automaticamente quando
 * Bruno decide escalar — carrega briefing TL;DR gerado por IA + dados
 * estruturados extraidos da conversa pra o humano assumir sem re-ler
 * historico inteiro.
 *
 * BelongsToTenant garante isolamento — cada tenant so ve seus handoffs.
 */
class BrunoHandoff extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_PENDING  = 'pendente';
    public const STATUS_ASSUMED  = 'assumido';
    public const STATUS_RESOLVED = 'resolvido';

    protected $fillable = [
        'tenant_id',
        'whatsapp_chat_id',
        'briefing',
        'structured_data',
        'status',
        'assumed_by',
        'assumed_at',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'structured_data' => 'array',
        'assumed_at'      => 'datetime',
        'resolved_at'     => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsappChat::class, 'whatsapp_chat_id');
    }

    public function assumedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assumed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAssumed($query)
    {
        return $query->where('status', self::STATUS_ASSUMED);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAssumed(): bool
    {
        return $this->status === self::STATUS_ASSUMED;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
