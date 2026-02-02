<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'name',
        'kinship',
        'birth_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
