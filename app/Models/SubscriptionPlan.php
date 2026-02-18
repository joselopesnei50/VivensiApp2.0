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
        'price_yearly', // New
        'interval',
        'features',
        'is_active',
        'asaas_id',
        'pagseguro_plan_id_yearly', // New
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'price_yearly' => 'decimal:2', // New
        'is_active' => 'boolean',
    ];
}
