<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Attendance extends Model
{
    use \App\Traits\Auditable;
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'beneficiary_id',
        'user_id',
        'date',
        'type',
        'description'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
