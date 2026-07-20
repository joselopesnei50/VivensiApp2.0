<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPerson extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'beneficiary_id',
        'name',
        'address',
        'city',
        'phone',
        'birth_date',
        'guardian_name',
        'guardian_phone',
        'enrollment_status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class, 'project_person_id');
    }

    public function enrollments()
    {
        return $this->hasMany(ProjectClassEnrollment::class, 'project_person_id');
    }
}
