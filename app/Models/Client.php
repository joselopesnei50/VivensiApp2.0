<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Client extends Model
{
    use HasFactory, BelongsToTenant;

    public const STAGES = [
        'lead'     => 'Lead',
        'prospect' => 'Prospect',
        'active'   => 'Ativo',
        'churned'  => 'Perdido',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'stage',
        'document',
        'email',
        'phone',
        'purchase_history',
        'relationship_notes',
        'last_contact_at',
    ];

    protected $hidden = ['document'];

    protected $casts = [
        'last_contact_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'client_id');
    }

    public function getLtvAttribute(): float
    {
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');
    }

    public function getPendingAmountAttribute(): float
    {
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'pending')
            ->sum('amount');
    }

    public function getTransactionCountAttribute(): int
    {
        return (int) $this->transactions()->count();
    }

    public function getLastTransactionAtAttribute()
    {
        return $this->transactions()->max('date');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst((string) $this->stage);
    }
}
