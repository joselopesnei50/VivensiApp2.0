<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'year',
        'amount',
        'type'
    ];

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }
}
