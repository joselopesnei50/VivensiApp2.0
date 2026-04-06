<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'read_at'
    ];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($notification) {
            broadcast(new \App\Events\NotificationCreated($notification))->toOthers();
        });
    }
}
