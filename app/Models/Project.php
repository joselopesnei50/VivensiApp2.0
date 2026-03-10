<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Project extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'budget',
        'start_date',
        'end_date',
        'status',
        'ngo_grant_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    // Desativa a coluna updated_at que não existe no banco legado
    const UPDATED_AT = null;
    
    // Relacionamento com Logs (Opicional por enquanto, mas bom ter)
    // Relacionamento com Transações (Opicional)

    public function ngo_grant()
    {
        return $this->belongsTo(NgoGrant::class, 'ngo_grant_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
