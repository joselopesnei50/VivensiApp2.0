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

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }
}
