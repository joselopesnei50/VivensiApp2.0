<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'acquisition_date',
        'value',
        'status',
        'location',
        'responsible'
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'value' => 'decimal:2'
    ];
}
