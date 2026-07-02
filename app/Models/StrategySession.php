<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sala de Estrategia — sessao de debate entre agentes IA.
 *
 * Fase 0: uma sessao por chamada de teste, so 1 fala (Financeiro).
 * Fase 1+: multiplas mensagens por sessao (Financeiro, Inteligencia,
 * Mobilizacao, Estrategista-Chefe).
 */
class StrategySession extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'trigger_type',
        'status',
        'proposed_action',
        'kanban_card_id',
    ];

    protected $casts = [
        'proposed_action' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(StrategyMessage::class);
    }

    public function kanbanCard(): BelongsTo
    {
        return $this->belongsTo(KanbanCard::class, 'kanban_card_id');
    }
}
