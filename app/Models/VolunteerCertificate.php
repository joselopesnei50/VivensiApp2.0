<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id',
        'activity_description',
        'hours',
        'issued_at',
        'file_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'hours' => 'integer',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }
}

