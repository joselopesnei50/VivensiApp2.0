<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPerson extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'name',
        'address',
        'city',
        'phone',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
