<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    use HasFactory, \App\Traits\BelongsToTenant;

    protected $table = 'financial_categories';
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'type'
    ];
}
