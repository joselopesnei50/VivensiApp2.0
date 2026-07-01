<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassAttendance extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'class_session_id',
        'project_person_id',
        'status',
        'checked_in_via',
        'justification',
        'checked_in_at',
        'ip_hash',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function student()
    {
        return $this->belongsTo(ProjectPerson::class, 'project_person_id');
    }
}
