<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fatura mensal de assinatura Vivensi.
 * Ciclo: gerada dia 1 pelo scheduler → aberta 5 dias pra pagar → overdue.
 * Pagamento: link AbacatePay (webhook confirma) OU PIX manual (admin marca).
 */
class Invoice extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_OPEN     = 'open';
    public const STATUS_PAID     = 'paid';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_OVERDUE  = 'overdue';

    public const PAID_VIA_ABACATEPAY    = 'abacatepay';
    public const PAID_VIA_PIX_MANUAL    = 'pix_manual';
    public const PAID_VIA_MANUAL_ADMIN  = 'manual_admin';
    public const PAID_VIA_FREE_COURTESY = 'free_courtesy';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'amount_cents',
        'description',
        'status',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'paid_via',
        'abacatepay_charge_id',
        'abacatepay_pix_url',
        'abacatepay_pix_qr_base64',
        'abacatepay_billing_url',
        'actor_user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'period_start' => 'date',
        'period_end'   => 'date',
        'due_date'     => 'date',
        'paid_at'      => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────
    public function scopeOpen(Builder $q): Builder     { return $q->where('status', self::STATUS_OPEN); }
    public function scopePaid(Builder $q): Builder     { return $q->where('status', self::STATUS_PAID); }
    public function scopeOverdue(Builder $q): Builder  { return $q->where('status', self::STATUS_OVERDUE); }
    public function scopeUnpaid(Builder $q): Builder   { return $q->whereIn('status', [self::STATUS_OPEN, self::STATUS_OVERDUE]); }

    // ── Helpers de apresentação ────────────────────────────────────
    public function getAmountBrlAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount_cents / 100, 2, ',', '.');
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_OVERDUE], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN     => 'Em aberto',
            self::STATUS_PAID     => 'Paga',
            self::STATUS_CANCELED => 'Cancelada',
            self::STATUS_OVERDUE  => 'Vencida',
            default               => ucfirst((string) $this->status),
        };
    }

    public function paidViaLabel(): ?string
    {
        return match ($this->paid_via) {
            self::PAID_VIA_ABACATEPAY    => 'AbacatePay',
            self::PAID_VIA_PIX_MANUAL    => 'PIX (manual)',
            self::PAID_VIA_MANUAL_ADMIN  => 'Manual (admin)',
            self::PAID_VIA_FREE_COURTESY => 'Cortesia',
            default                      => null,
        };
    }
}
