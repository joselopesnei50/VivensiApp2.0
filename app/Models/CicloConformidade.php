<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CicloConformidade extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ciclos_conformidade';

    protected $fillable = [
        'tenant_id',
        'eixo',
        'data_inicio',
        'data_fim',
        'enquadramento',
        'status',
        'observacoes',
        'overrides',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim'    => 'date',
        'overrides'   => 'array',
    ];

    public function avaliacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AvaliacaoRequisito::class, 'ciclo_conformidade_id');
    }

    public function snapshots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SnapshotConformidade::class, 'ciclo_conformidade_id');
    }

    public function getDiasRestantesAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->data_fim, false));
    }

    public function scopeEmAndamento($query)
    {
        return $query->where('status', 'em_andamento');
    }

    public function scopeDoEixo($query, string $eixo)
    {
        return $query->where('eixo', $eixo);
    }

    public function thresholdOverride(string $codigoRequisito): ?float
    {
        return $this->overrides[$codigoRequisito] ?? null;
    }
}
