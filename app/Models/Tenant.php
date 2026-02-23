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
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
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
