<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoAcaoConformidade extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $table = 'planos_acao_conformidade';

    protected $fillable = [
        'tenant_id',
        'requisito_legal_id',
        'titulo',
        'descricao',
        'responsavel',
        'prazo',
        'status',
        'observacoes_resolucao',
        'resolvido_em',
        'resolvido_por',
    ];

    protected $casts = [
        'prazo'        => 'date',
        'resolvido_em' => 'datetime',
    ];

    public function requisito(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RequisitoLegal::class, 'requisito_legal_id');
    }

    public function resolvedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }

    public function isPendente(): bool    { return $this->status === 'pendente'; }
    public function isEmAndamento(): bool { return $this->status === 'em_andamento'; }
    public function isConcluido(): bool   { return $this->status === 'concluido'; }
    public function isCancelado(): bool   { return $this->status === 'cancelado'; }

    public function estaAtrasado(): bool
    {
        return ! $this->isConcluido()
            && ! $this->isCancelado()
            && $this->prazo->isPast();
    }
}
