<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalChat extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'name'
    ];
}
