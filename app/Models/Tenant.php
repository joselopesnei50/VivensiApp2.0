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
        'asaas_customer_id',
        'plan_id',
        'subscription_status',
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
    ];

    protected $hidden = [
        'pix_key',
        'pix_key_type',
        'openpix_app_id',
    ];

    protected $casts = [
        'trial_ends_at'           => 'date',
        'weekly_report_enabled'   => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function whatsappBlacklists()
    {
        return $this->hasMany(WhatsappBlacklist::class);
    }
}
