<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Employee extends Model
{
    use \App\Traits\Auditable;
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'name',
        'position',
        'salary',
        'bonus',
        'work_hours_weekly',
        'contract_type',
        'hired_at',
        'status',
        'categoria_profissional',
    ];

    protected $casts = [
        'hired_at' => 'date',
        'salary'   => 'encrypted',
        'bonus'    => 'encrypted',
    ];

    // Relação com Projeto (opcional)
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
