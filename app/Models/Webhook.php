<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'url', 'secret', 'events', 'active', 'last_triggered_at',
    ];

    protected $casts = [
        'events'            => 'array',
        'active'            => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true)
            || in_array('*', $this->events ?? [], true);
    }
}
