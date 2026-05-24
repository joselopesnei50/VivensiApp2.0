<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Client extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'document',
        'email',
        'phone',
        'purchase_history',
        'relationship_notes'
    ];

    protected $hidden = ['document'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
