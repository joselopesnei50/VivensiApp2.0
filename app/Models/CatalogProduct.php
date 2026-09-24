<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogProduct extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    public const TYPES = [
        'service' => 'Serviço',
        'product' => 'Produto',
    ];

    public const COMMON_UNITS = [
        'un' => 'unidade',
        'hr' => 'hora',
        'dia' => 'dia',
        'mês' => 'mês',
        'projeto' => 'projeto',
        'kg' => 'kg',
        'm' => 'metro',
        'm2' => 'm²',
    ];

    protected $fillable = [
        'tenant_id',
        'type',
        'sku',
        'name',
        'description',
        'unit_price',
        'unit',
        'category',
        'stock_quantity',
        'track_stock',
        'active',
    ];

    protected $casts = [
        'unit_price'     => 'decimal:2',
        'stock_quantity' => 'integer',
        'track_stock'    => 'boolean',
        'active'         => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeServices($query)
    {
        return $query->where('type', 'service');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getUnitLabelAttribute(): string
    {
        return self::COMMON_UNITS[$this->unit] ?? $this->unit;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->unit_price, 2, ',', '.');
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->track_stock
            && $this->stock_quantity !== null
            && $this->stock_quantity <= $threshold;
    }
}
