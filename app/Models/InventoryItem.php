<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use App\Traits\HasAttachments;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAttachments;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'unit',
        'quantity',
        'minimum_stock',
        'value_per_unit',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
