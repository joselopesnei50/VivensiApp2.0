<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanoTrabalho extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'planos_trabalho';

    public const STATUS_APROVADO    = 'aprovado';
    public const STATUS_EM_REVISAO  = 'em_revisao';
    public const STATUS_SUBSTITUIDO = 'substituido';

    protected $fillable = [
        'tenant_id',
        'termo_id',
        'versao',
        'status',
        'objeto',
        'aprovado_em',
        'documento_url',
    ];

    protected $casts = [
        'aprovado_em' => 'date',
        'versao'      => 'integer',
    ];

    public function termo(): BelongsTo
    {
        return $this->belongsTo(TermoColaboracao::class, 'termo_id');
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class, 'plano_trabalho_id');
    }

    public function isAprovado(): bool
    {
        return $this->status === self::STATUS_APROVADO;
    }
}
