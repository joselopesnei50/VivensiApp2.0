<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Raffle extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'rules',
        'image_path',
        'ticket_price',
        'total_tickets',
        'draw_date',
        'status',
        'winner_ticket_id',
    ];

    protected $casts = [
        'draw_date' => 'datetime',
        'ticket_price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($raffle) {
            if (empty($raffle->slug)) {
                $raffle->slug = \Illuminate\Support\Str::slug($raffle->title) . '-' . \Illuminate\Support\Str::random(5);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tickets()
    {
        return $this->hasMany(RaffleTicket::class);
    }

    public function winner()
    {
        return $this->belongsTo(RaffleTicket::class, 'winner_ticket_id');
    }
}
