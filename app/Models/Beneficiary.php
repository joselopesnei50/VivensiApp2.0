<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use \App\Traits\Auditable;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'cpf',
        'nis',
        'birth_date',
        'phone',
        'address',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
