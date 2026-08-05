<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappChatAssignment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'chat_id',
        'from_user_id',
        'to_user_id',
        'assigned_by_user_id',
        'action',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected $casts = [
        'chat_id'             => 'integer',
        'from_user_id'        => 'integer',
        'to_user_id'          => 'integer',
        'assigned_by_user_id' => 'integer',
        'duration_seconds'    => 'integer',
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(WhatsappChat::class, 'chat_id');
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('ended_at');
    }
}
