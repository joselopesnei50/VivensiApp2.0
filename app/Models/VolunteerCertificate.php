<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerCertificate extends Model
{
    use HasFactory;
    // Auditoria 2026-08-29 P3.b.1: escopo global por tenant + auto-preenche
    // tenant_id no create (via auth). Fecha IDOR cross-tenant por colisao
    // de volunteer_id no download.
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'volunteer_id',
        'uuid',
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

