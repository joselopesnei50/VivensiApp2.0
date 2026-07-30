<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extrato imutável de movimentações do saldo pré-pago WhatsApp.
 * Nunca edita/deleta — só insere. Chargebacks/estornos viram
 * transações do tipo refund ou adjustment.
 */
class WhatsappCreditTransaction extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_TOPUP      = 'topup';
    public const TYPE_DEBIT      = 'debit';
    public const TYPE_REFUND     = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'tenant_id',
        'type',
        'amount_brl_micros',
        'balance_after_brl_micros',
        'description',
        'whatsapp_conversation_id',
        'actor_user_id',
    ];

    protected $casts = [
        'amount_brl_micros'        => 'integer',
        'balance_after_brl_micros' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'whatsapp_conversation_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** Sinal humano-legível — "+R$ 50,00" ou "-R$ 0,15" */
    public function getSignedBrlAttribute(): string
    {
        $sign  = $this->amount_brl_micros >= 0 ? '+' : '-';
        $value = abs($this->amount_brl_micros) / 1_000_000;
        return $sign . 'R$ ' . number_format($value, 2, ',', '.');
    }

    public function getBalanceAfterBrlAttribute(): float
    {
        return $this->balance_after_brl_micros / 1_000_000;
    }
}
