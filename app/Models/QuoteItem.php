<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'catalog_product_id',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'position',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'position'   => 'integer',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function catalogProduct()
    {
        return $this->belongsTo(CatalogProduct::class);
    }
}
