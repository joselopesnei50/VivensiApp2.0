<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document',
        'type',
        'business_type',
        'asaas_customer_id',
        'plan_id',
        'subscription_status',
        'daily_email_quota',
        'trial_ends_at',
        'billing_cycle', // monthly, yearly
        // F7 — White-Label
        'brand_logo',
        'brand_color',
        'brand_name',
        // F8 — Relatório Semanal
        'weekly_report_enabled',
        'report_email',
        'pix_key',
        'pix_key_type',
        'openpix_app_id',
        'whatsapp_support',
        // Conformidade
        'cnae_principal',
        'data_fundacao',
        'area_atuacao_cebas',
        'cnas_numero',
        'cnas_validade',
        'cmas_numero',
        'cmas_validade',
        'cneas_codigo',
        'receita_bruta_anual_ref',
        'radar_ibge_code',
        'radar_areas',
        'radar_digest_channel',
        'radar_min_score',
        'radar_last_digest_at',
    ];

    protected $hidden = [
        'pix_key',
        'pix_key_type',
        'openpix_app_id',
    ];

    protected $casts = [
        'trial_ends_at'           => 'date',
        'weekly_report_enabled'   => 'boolean',
        'pix_key'                 => 'encrypted',
        'pix_key_type'            => 'encrypted',
        'openpix_app_id'          => 'encrypted',
        'data_fundacao'           => 'date',
        'cnas_validade'           => 'date',
        'cmas_validade'           => 'date',
        'receita_bruta_anual_ref' => 'decimal:2',
        'radar_areas'             => 'array',
        'radar_min_score'         => 'integer',
        'radar_last_digest_at'    => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Confere se o plano deste tenant libera uma capability técnica.
     * Se o tenant não tem plano vinculado (edge case), libera por default
     * pra não quebrar operação — o admin resolve via UI.
     */
    public function hasCapability(string $key): bool
    {
        return $this->plan?->hasCapability($key) ?? true;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function whatsappBlacklists()
    {
        return $this->hasMany(WhatsappBlacklist::class);
    }

    public function operationalProfile()
    {
        return $this->hasOne(TenantOperationalProfile::class);
    }

    public function isMei(): bool
    {
        return $this->business_type === 'mei';
    }

    /** @return string StrategyRoomMode::Institucional|Negocio */
    public function strategyRoomMode(): string
    {
        // Fallback institucional pra type desconhecido/null: preserva o
        // comportamento original da Sala (gestores type=business inclusos —
        // eles usam Chamada e editais, então NAO são modo negocio).
        return in_array($this->type, ['common', 'personal'], true)
            ? \App\Enums\StrategyRoomMode::Negocio
            : \App\Enums\StrategyRoomMode::Institucional;
    }
}
