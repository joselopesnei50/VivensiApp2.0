<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermoColaboracao extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'termos_colaboracao';

    public const MODALIDADE_DIRETA   = 'direta';
    public const MODALIDADE_INDIRETA = 'indireta';

    public const STATUS_VIGENTE       = 'vigente';
    public const STATUS_ENCERRADO     = 'encerrado';
    public const STATUS_SUSPENSO      = 'suspenso';
    public const STATUS_EM_DILIGENCIA = 'em_diligencia';

    protected $fillable = [
        'tenant_id',
        'numero',
        'mds_sesan_ref',
        'vigencia_inicio',
        'vigencia_fim',
        'valor_global',
        'modalidade_execucao',
        'status',
        'documento_url',
        'created_by',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fim'    => 'date',
        'valor_global'    => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function planosTrabalho(): HasMany
    {
        return $this->hasMany(PlanoTrabalho::class, 'termo_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class, 'termo_id');
    }

    public function cozinhas(): HasMany
    {
        return $this->hasMany(Cozinha::class, 'termo_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDireta(): bool
    {
        return $this->modalidade_execucao === self::MODALIDADE_DIRETA;
    }

    public function planoVigenteEm(\DateTimeInterface $data): ?PlanoTrabalho
    {
        return $this->planosTrabalho()
            ->where('status', PlanoTrabalho::STATUS_APROVADO)
            ->where('aprovado_em', '<=', $data)
            ->orderByDesc('versao')
            ->first();
    }
}
