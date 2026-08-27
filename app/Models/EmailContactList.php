<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailContactList extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'tags',
        'opt_in_confirmed',
        'opt_in_confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'tags'                => 'array',
        'opt_in_confirmed'    => 'boolean',
        'opt_in_confirmed_at' => 'datetime',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(EmailContact::class);
    }

    public function activeContacts(): HasMany
    {
        return $this->hasMany(EmailContact::class)->where('status', 'active');
    }

    public function stats(): array
    {
        $rows = $this->contacts()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'total'        => (int) $rows->sum(),
            'active'       => (int) ($rows['active']       ?? 0),
            'bounced'      => (int) ($rows['bounced']      ?? 0),
            'unsubscribed' => (int) ($rows['unsubscribed'] ?? 0),
            'invalid'      => (int) ($rows['invalid']      ?? 0),
        ];
    }
}
