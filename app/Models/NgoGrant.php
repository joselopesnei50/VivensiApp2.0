<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;
use App\Traits\Auditable;

class NgoGrant extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $table = 'ngo_grants';

    protected $fillable = [
        'tenant_id',
        'title',
        'agency',
        'contract_number',
        'value',
        'start_date',
        'deadline',
        'status',
        'notes',
    ];

    protected $casts = [
        'deadline' => 'date',
        'start_date' => 'date',
    ];

    public function documents()
    {
        return $this->hasMany(NgoGrantDocument::class, 'ngo_grant_id');
    }
}
