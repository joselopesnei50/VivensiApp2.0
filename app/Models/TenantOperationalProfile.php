<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantOperationalProfile extends Model
{
    use HasFactory;

    public const CATEGORIA_CAMPANHA_ELEITORAL  = 'campanha_eleitoral';
    public const CATEGORIA_PROJETO_CULTURAL    = 'projeto_cultural';
    public const CATEGORIA_PROJETO_EMPRESARIAL = 'projeto_empresarial';
    public const CATEGORIA_MOBILIZACAO_SOCIAL  = 'mobilizacao_social';
    public const CATEGORIA_OUTRO               = 'outro';

    public const CATEGORIAS = [
        self::CATEGORIA_CAMPANHA_ELEITORAL  => 'Campanha Eleitoral',
        self::CATEGORIA_PROJETO_CULTURAL    => 'Projeto Cultural',
        self::CATEGORIA_PROJETO_EMPRESARIAL => 'Projeto Empresarial',
        self::CATEGORIA_MOBILIZACAO_SOCIAL  => 'Mobilização Social',
        self::CATEGORIA_OUTRO               => 'Outro',
    ];

    protected $fillable = [
        'tenant_id',
        'categoria',
        'instrucao',
        'vocabulario',
    ];

    protected $casts = [
        'vocabulario' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isCategoriaEleitoral(): bool
    {
        return $this->categoria === self::CATEGORIA_CAMPANHA_ELEITORAL;
    }
}
