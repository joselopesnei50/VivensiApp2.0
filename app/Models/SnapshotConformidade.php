<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SnapshotConformidade extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'snapshots_conformidade';

    protected $fillable = [
        'tenant_id',
        'ciclo_conformidade_id',
        'indice_geral',
        'indice_cebas',
        'indice_suas',
        'indice_mrosc',
        'total_verde',
        'total_amarelo',
        'total_vermelho',
        'total_nao_aplicavel',
        'snapshotado_em',
    ];

    protected $casts = [
        'indice_geral'  => 'decimal:2',
        'indice_cebas'  => 'decimal:2',
        'indice_suas'   => 'decimal:2',
        'indice_mrosc'  => 'decimal:2',
        'snapshotado_em' => 'datetime',
    ];

    public function ciclo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CicloConformidade::class, 'ciclo_conformidade_id');
    }
}
