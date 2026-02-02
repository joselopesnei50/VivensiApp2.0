<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class NgoDonor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ngo_donors';

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'document',
        'type', // individual, company, government
    ];
}
