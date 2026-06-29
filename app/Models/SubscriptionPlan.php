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
        'is_active',
        'asaas_id',
        'abacatepay_product_id', // ID do produto na AbacatePay
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'price_yearly' => 'decimal:2', // New
        'is_active' => 'boolean',
    ];
}
