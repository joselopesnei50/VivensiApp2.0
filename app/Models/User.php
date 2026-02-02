<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Conectando à tabela existente do sistema atual
    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'department',
        'is_platform_team',
        'supervisor_id',
        'status',
        'last_seen_at',
        'last_login_at',
        'phone',
        'onboarding_steps',
        'onboarding_completed_at'
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'onboarding_steps' => 'array'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }


    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class, 'user_id');
    }
}

