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

    /**
     * Sessao em_andamento ha mais que isso e orfa: o job tem timeout de 180s,
     * entao 10min so acontece se o worker morreu no meio (ex: restart do
     * supervisor durante deploy) — catch/failed() nunca rodam nesse caso.
     */
    public const STALE_MINUTES = 10;

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

    public function isStale(): bool
    {
        return $this->status === 'em_andamento'
            && $this->created_at !== null
            && $this->created_at->lt(now()->subMinutes(self::STALE_MINUTES));
    }

    /** Marca como concluida se orfa. Devolve true se curou. */
    public function healIfStale(): bool
    {
        if (!$this->isStale()) {
            return false;
        }
        $this->update(['status' => 'concluida']);
        return true;
    }

    /** Cura em lote as sessoes orfas do tenant (badges da listagem). */
    public static function healStaleForTenant(int $tenantId): int
    {
        return static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'em_andamento')
            ->where('created_at', '<', now()->subMinutes(self::STALE_MINUTES))
            ->update(['status' => 'concluida']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StrategyMessage::class);
    }

    public function kanbanCard(): BelongsTo
    {
        return $this->belongsTo(KanbanCard::class, 'kanban_card_id');
    }
}
