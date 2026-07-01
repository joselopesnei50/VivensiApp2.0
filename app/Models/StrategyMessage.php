<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sala de Estrategia — fala de um agente dentro de uma sessao.
 *
 * facts_used e array JSON com "handles" dos dados usados pra fundamentar
 * a fala (vide md/vivensi-sala-estrategia-arquitetura.md §4). Serve pra
 * UI (Fase 2+) distinguir fato vs interpretacao. Nao contem PII individual.
 */
class StrategyMessage extends Model
{
    use HasFactory;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'strategy_session_id',
        'agent',
        'content',
        'facts_used',
        'confidence',
    ];

    protected $casts = [
        'facts_used' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StrategySession::class, 'strategy_session_id');
    }
}
