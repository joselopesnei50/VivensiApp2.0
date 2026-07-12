<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversa faturavel Meta WhatsApp Cloud API (janela de 24h).
 *
 * Meta cobra por conversa (nao por mensagem) — uma conversa agrupa N mensagens
 * no mesmo periodo de 24h com a mesma categoria/origem. Custo em USD micros
 * (1 USD = 1.000.000) pra evitar float rounding.
 *
 * ISOLAMENTO: BelongsToTenant aplica scope automatico. Em webhooks/jobs use
 * ::withoutGlobalScope('tenant') + filtro manual por tenant_id.
 */
class WhatsappConversation extends Model
{
    use HasFactory, BelongsToTenant;

    public const CATEGORY_MARKETING       = 'marketing';
    public const CATEGORY_UTILITY         = 'utility';
    public const CATEGORY_AUTHENTICATION  = 'authentication';
    public const CATEGORY_SERVICE         = 'service';
    public const CATEGORY_REFERRAL        = 'referral_conversion';

    public const PRICING_MODEL_CBP = 'CBP';
    public const PRICING_MODEL_PMP = 'PMP';

    protected $fillable = [
        'tenant_id',
        'whatsapp_instance_id',
        'meta_conversation_id',
        'contact_wa_id',
        'category',
        'origin_type',
        'expires_at',
        'started_at',
        'cost_usd_micros',
        'pricing_model',
        'is_billable',
        'country_code',
    ];

    protected $casts = [
        'expires_at'      => 'datetime',
        'started_at'      => 'datetime',
        'cost_usd_micros' => 'integer',
        'is_billable'     => 'boolean',
    ];

    // ── Relacionamentos ─────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class, 'whatsapp_instance_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'whatsapp_conversation_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('started_at', [$from, $to]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Retorna custo em USD como float (1.000.000 micros = $1.00).
     */
    public function getCostUsdAttribute(): float
    {
        return $this->cost_usd_micros / 1_000_000;
    }

    /**
     * Converte custo em BRL usando taxa de cambio parametrizada.
     * Cambio default via config('services.exchange.usd_brl').
     */
    public function costInBrl(?float $exchangeRate = null): float
    {
        $rate = $exchangeRate ?? (float) config('services.exchange.usd_brl', 5.5);
        return $this->cost_usd * $rate;
    }
}
