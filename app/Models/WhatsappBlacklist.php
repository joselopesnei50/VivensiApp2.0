<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappBlacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'phone',
        'reason',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
