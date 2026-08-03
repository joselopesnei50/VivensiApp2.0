<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_audience',
        'price',
        'price_yearly',
        'interval',
        'features',
        'capabilities',
        'is_active',
        'is_courtesy',
        'asaas_id',
        'abacatepay_product_id', // ID do produto na AbacatePay
        'whatsapp_conversations_included',
        'whatsapp_extra_pack_size',
        'whatsapp_extra_pack_price_brl',
    ];

    protected $casts = [
        'features'     => 'array',
        'capabilities' => 'array',
        'price'        => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active'    => 'boolean',
        'is_courtesy'  => 'boolean',
        'whatsapp_conversations_included' => 'integer',
        'whatsapp_extra_pack_size'        => 'integer',
        'whatsapp_extra_pack_price_brl'   => 'decimal:2',
    ];

    /**
     * Confere se este plano tem a capability técnica solicitada.
     *
     * Regra de default (backward compat): plano SEM capabilities definidas OU
     * sem a chave específica retorna TRUE (grace period pros planos legados
     * que não sabem sobre a nova infra de flags). Só bloqueia quando o admin
     * marcar EXPLICITAMENTE key => false.
     */
    public function hasCapability(string $key): bool
    {
        $caps = $this->capabilities ?? [];
        if (!array_key_exists($key, $caps)) {
            return true; // grace period: default liberado
        }
        return (bool) $caps[$key];
    }
}
