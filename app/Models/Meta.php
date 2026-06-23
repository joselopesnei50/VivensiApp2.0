<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meta extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'metas';

    public const TIPO_FISICA       = 'fisica';
    public const TIPO_FINANCEIRA   = 'financeira';
    public const TIPO_QUALIFICACAO = 'qualificacao';

    protected $fillable = [
        'tenant_id',
        'plano_trabalho_id',
        'tipo',
        'descricao',
        'unidade',
        'quantidade_prevista',
        'valor_previsto',
    ];

    protected $casts = [
        'quantidade_prevista' => 'decimal:4',
        'valor_previsto'      => 'decimal:2',
    ];

    public function planoTrabalho(): BelongsTo
    {
        return $this->belongsTo(PlanoTrabalho::class);
    }

    public function cronograma(): HasMany
    {
        return $this->hasMany(MetaCronograma::class, 'meta_id')->orderBy('mes');
    }
}
