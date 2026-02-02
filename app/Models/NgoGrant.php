<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class NgoGrant extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ngo_grants';

    protected $fillable = [
        'tenant_id',
        'title',
        'agency',
        'value',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];
}
