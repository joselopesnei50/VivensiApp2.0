<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoRequisito extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $table = 'avaliacoes_requisito';

    protected $fillable = [
        'tenant_id',
        'ciclo_conformidade_id',
        'requisito_legal_id',
        'resultado',
        'valor_calculado',
        'avaliado_em',
        'avaliado_por',
        'observacoes',
    ];

    protected $casts = [
        'avaliado_em'     => 'datetime',
        'valor_calculado' => 'decimal:2',
    ];

    public function ciclo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CicloConformidade::class, 'ciclo_conformidade_id');
    }

    public function requisito(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RequisitoLegal::class, 'requisito_legal_id');
    }

    public function avaliador(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }

    public function evidencias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Evidencia::class, 'avaliacao_requisito_id');
    }

    public function isVerde(): bool   { return $this->resultado === 'verde'; }
    public function isAmarelo(): bool { return $this->resultado === 'amarelo'; }
    public function isVermelho(): bool{ return $this->resultado === 'vermelho'; }
    public function isSistema(): bool { return $this->avaliado_por === null; }
}
