<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['tenant_id', 'user_id', 'path', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public static function prune(int $days = 90): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
