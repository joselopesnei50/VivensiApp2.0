<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Volunteer extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'skills',
        'hours_logged',
        'points',
        'level_badge',
        'availability',
        'status'
    ];

    public function certificates()
    {
        return $this->hasMany(VolunteerCertificate::class);
    }

    public function hourLogs()
    {
        return $this->hasMany(VolunteerHourLog::class);
    }

    public const AVAILABILITY_LABELS = [
        'morning'   => 'Manhã',
        'afternoon' => 'Tarde',
        'night'     => 'Noite',
        'weekends'  => 'Finais de semana',
    ];

    /** Disponibilidade em português para exibição. */
    public function getAvailabilityLabelAttribute(): string
    {
        $v = (string) $this->availability;
        return self::AVAILABILITY_LABELS[$v] ?? ($v !== '' ? ucfirst($v) : 'Variável');
    }
}
