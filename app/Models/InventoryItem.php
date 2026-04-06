<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class InventoryItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'unit',
        'quantity',
        'minimum_stock',
        'value_per_unit',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
