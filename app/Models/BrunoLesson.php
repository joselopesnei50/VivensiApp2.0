<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Licao do Bruno — passagem de conversa que fechou/avancou venda,
 * cadastrada manualmente pelo gestor. Bruno le e injeta as mais relevantes
 * no prompt como few_shots dinamicos.
 *
 * IMPORTANTE: sem BelongsToTenant proposital — as licoes podem ser globais
 * (tenant_id NULL). Consulta manual filtra por tenant OR NULL onde caber.
 */
class BrunoLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'tags',
        'situation',
        'lead_said',
        'bruno_replied',
        'notes',
        'created_by',
        'active',
    ];

    protected $casts = [
        'tags'   => 'array',
        'active' => 'boolean',
    ];

    /** Tags sugeridas pra UI — mantem consistencia entre cadastros. */
    public const SUGGESTED_TAGS = [
        // Perfil do lead
        'ong-pequena', 'ong-grande', 'ong-em-captacao', 'ong-consolidada',
        'empresa-mei', 'associacao', 'instituto', 'fundacao',
        // Objecoes
        'objecao-preco', 'objecao-verba', 'objecao-tempo', 'objecao-mudanca',
        'desconfianca-vendor', 'concorrente-atual',
        // Momentos do funil
        'primeiro-contato', 'apresentacao', 'demo-agendada', 'proposta-enviada',
        'negociacao', 'fechamento',
        // Situacoes
        'edital-proximo', 'prestacao-contas', 'radar-editais', 'lgpd',
        'whatsapp-integrado', 'bruce-ia', 'sala-estrategia',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Retorna licoes ativas relevantes pra os tags atuais, ordenadas por
     * match (mais tags em comum primeiro). Considera licoes globais + tenant.
     * Sem tags = devolve as mais recentes.
     *
     * @param  array<int, string>  $currentTags  Tags detectadas da situacao atual
     */
    public static function retrieveRelevant(?int $tenantId, array $currentTags = [], int $limit = 3): \Illuminate\Support\Collection
    {
        $rows = self::active()
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id');
                if ($tenantId) $q->orWhere('tenant_id', $tenantId);
            })
            ->orderByDesc('updated_at')
            ->limit(100) // ceiling defensivo — sort completo em memoria
            ->get();

        if (empty($currentTags)) {
            return $rows->take($limit);
        }

        // Score = qtd de tags em comum. Empate mantem ordem por updated_at.
        return $rows
            ->map(function ($lesson) use ($currentTags) {
                $lessonTags = array_map('strval', (array) ($lesson->tags ?? []));
                $lesson->match_score = count(array_intersect($currentTags, $lessonTags));
                return $lesson;
            })
            ->filter(fn ($l) => $l->match_score > 0) // so relevantes
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }
}
