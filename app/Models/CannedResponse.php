<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class CannedResponse extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'title', 'content'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
