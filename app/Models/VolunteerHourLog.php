<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerHourLog extends Model
{
    use HasFactory;

    protected $table = 'volunteer_hour_logs';

    protected $fillable = [
        'tenant_id',
        'volunteer_id',
        'hours',
        'description',
        'logged_by',
    ];

    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
