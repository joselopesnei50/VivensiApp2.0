<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Asset extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

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
