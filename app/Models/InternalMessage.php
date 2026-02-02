<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'sender_id',
        'receiver_id',
        'department',
        'message',
        'is_read',
        'read_at'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
