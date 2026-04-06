<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class SponsorshipDeal extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'expected_value',
        'stage',
        'contact_date',
        'notes'
    ];

    protected $casts = [
        'contact_date' => 'date',
        'expected_value' => 'decimal:2'
    ];
}
