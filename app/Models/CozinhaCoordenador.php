<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model dedicado (decisão §3.4 do plano) — futuro abrigo de auditoria
 * de quem foi adicionado/removido como coordenador de cozinha.
 *
 * Não usa BelongsToTenant (Pivot do Laravel não tem hook de boot adequado
 * pra global scope); coordenadores são sempre acessados via Cozinha::coordenadores
 * ou User::cozinhas, que já estão escopados.
 */
class CozinhaCoordenador extends Pivot
{
    use HasFactory;

    protected $table = 'cozinha_coordenadores';

    public $incrementing = true;

    public const PAPEL_COORDENADOR = 'coordenador';
    public const PAPEL_AUXILIAR    = 'auxiliar';

    protected $fillable = [
        'tenant_id',
        'cozinha_id',
        'user_id',
        'papel',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
