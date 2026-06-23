<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regra de compliance versionada por portaria/edital.
 *
 * NÃO usa BelongsToTenant — regras são globais (vêm de norma federal, valem
 * pra todos os tenants).
 *
 * Resolução por data faz BETWEEN com vigência aberta (vigencia_fim null = ainda
 * vigente). Use scopeVigenteEm($date) + filtro por chave + modalidade no
 * RegrasComplianceService — nunca diretamente em código de negócio.
 *
 * Bloqueador do guardião (R1): regra com curado_em NULL não pode ser aplicada
 * em produção. O service que cuida disso.
 */
class RegraCompliance extends Model
{
    use HasFactory;

    protected $table = 'regras_compliance';

    public const APLICA_DIRETA   = 'direta';
    public const APLICA_INDIRETA = 'indireta';
    public const APLICA_AMBAS    = 'ambas';

    protected $fillable = [
        'chave',
        'parametros',
        'fonte_legal',
        'vigencia_inicio',
        'vigencia_fim',
        'aplica_a_modalidade',
        'curado_por_user_id',
        'curado_em',
        'created_by_user_id',
    ];

    protected $casts = [
        'parametros'      => 'array',
        'vigencia_inicio' => 'date',
        'vigencia_fim'    => 'date',
        'curado_em'       => 'datetime',
    ];

    public function curador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curado_por_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVigenteEm(Builder $query, \DateTimeInterface $data): Builder
    {
        return $query
            ->whereDate('vigencia_inicio', '<=', $data)
            ->where(function ($q) use ($data) {
                $q->whereNull('vigencia_fim')
                  ->orWhereDate('vigencia_fim', '>=', $data);
            });
    }

    public function scopeAplicaA(Builder $query, string $modalidade): Builder
    {
        return $query->whereIn('aplica_a_modalidade', [$modalidade, self::APLICA_AMBAS]);
    }

    public function isCurada(): bool
    {
        return $this->curado_em !== null
            && $this->curado_por_user_id !== null
            && !empty($this->parametros);
    }
}
