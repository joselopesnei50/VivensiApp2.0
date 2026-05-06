<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
