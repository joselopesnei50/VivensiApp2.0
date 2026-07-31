<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estado atual da cota mensal WhatsApp Cloud API de um tenant.
 * Uma linha por tenant, criada on-demand no primeiro consumo ou quando
 * admin lança pack extra.
 *
 * Modelo comercial C:
 *   - Cliente paga assinatura mensal do Vivensi (inclui X conversas)
 *   - Cliente paga Meta direto (cartão dele na WABA)
 *   - Se usar mais que X, compra pack extra do Vivensi
 *   - Contador reseta dia 1 às 00:05 (command whatsapp:reset-monthly-quotas)
 *
 * plan_included_snapshot é gravado no início do período — protege se admin
 * trocar o plano do tenant no meio do mês (cliente não perde a cota já paga).
 */
class WhatsappQuotaUsage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_quota_usage';

    protected $fillable = [
        'tenant_id',
        'conversations_used_month',
        'extra_pack_conversations',
        'plan_included_snapshot',
        'period_start',
        'over_quota_alerted_at',
    ];

    protected $casts = [
        'conversations_used_month' => 'integer',
        'extra_pack_conversations' => 'integer',
        'plan_included_snapshot'   => 'integer',
        'period_start'             => 'date',
        'over_quota_alerted_at'    => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(WhatsappQuotaEvent::class, 'tenant_id', 'tenant_id')
            ->latest('id');
    }

    /** Total disponível no mês (plano + packs extras comprados). */
    public function totalAvailable(): int
    {
        return $this->plan_included_snapshot + $this->extra_pack_conversations;
    }

    /** Conversas restantes até estourar cota. Pode ser negativo se estourou. */
    public function remaining(): int
    {
        return $this->totalAvailable() - $this->conversations_used_month;
    }

    /** Tem cota pra enviar pelo menos 1 conversation? */
    public function hasQuota(): bool
    {
        return $this->remaining() > 0;
    }

    /** Percentual usado (0-100+, pode passar de 100 se estourou). */
    public function usagePct(): int
    {
        $total = $this->totalAvailable();
        if ($total <= 0) return 0;
        return (int) round(($this->conversations_used_month / $total) * 100);
    }

    /** Alerta amarelo (>=80% usado). */
    public function isNearQuota(): bool
    {
        return $this->usagePct() >= 80 && $this->hasQuota();
    }

    /** Cota totalmente esgotada (0 conversations restantes). */
    public function isOverQuota(): bool
    {
        return $this->remaining() <= 0;
    }

    /** Dias até reset (baseado no period_start + 1 mês). */
    public function daysUntilReset(): int
    {
        $nextReset = $this->period_start->copy()->addMonth();
        return max(0, (int) now()->startOfDay()->diffInDays($nextReset, false));
    }
}
