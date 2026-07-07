<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookDataDeletionRequest extends Model
{
    protected $fillable = [
        'facebook_user_id',
        'confirmation_code',
        'status',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
