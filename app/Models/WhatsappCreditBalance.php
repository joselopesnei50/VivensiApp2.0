<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saldo pré-pago WhatsApp por tenant. 1:1 com Tenant.
 * Toda mutação de saldo passa por WhatsappCreditService pra garantir
 * atomicidade + transação de auditoria.
 */
class WhatsappCreditBalance extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'balance_brl_micros',
        'prepaid_enabled_at',
        'low_balance_alerted_at',
        'last_topup_brl_micros',
    ];

    protected $casts = [
        'balance_brl_micros'      => 'integer',
        'last_topup_brl_micros'   => 'integer',
        'prepaid_enabled_at'      => 'datetime',
        'low_balance_alerted_at'  => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(WhatsappCreditTransaction::class, 'tenant_id', 'tenant_id')
            ->latest('id');
    }

    /** Modo pré-pago ativo pra este tenant? */
    public function isPrepaidEnabled(): bool
    {
        return $this->prepaid_enabled_at !== null;
    }

    /** Conversor pra reais (float) só pra apresentação. */
    public function getBalanceBrlAttribute(): float
    {
        return $this->balance_brl_micros / 1_000_000;
    }

    /**
     * Saldo cruzou o threshold configurável de alerta?
     * (baseado na última recarga)
     */
    public function isLowBalance(): bool
    {
        if ($this->last_topup_brl_micros <= 0) return false;
        $threshold = (int) config('whatsapp_pricing.prepaid.low_balance_threshold_pct', 20);
        return $this->balance_brl_micros < ($this->last_topup_brl_micros * $threshold / 100);
    }
}
