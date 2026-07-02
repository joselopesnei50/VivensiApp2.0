<?php

namespace App\Services\StrategyRoom;

use App\Models\EmailCampaign;
use App\Models\NgoDonor;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappCampaignMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sala de Estrategia — Fase 1.3. Tools do Agente de Mobilizacao (CMO).
 *
 * Regra dura de LGPD (arquitetura §2, §5): NUNCA expor lista nominal de
 * doador/lead/cliente individual. Toda metrica e AGREGADA — contagem,
 * percentual, taxa. Emails/telefones nao entram no payload em hipotese
 * alguma. Discipline igual a projectContext() do BruceAi.
 *
 * As 3 tools cobrem os 3 canais principais do Vivensi:
 *   - WhatsApp broadcasts (campanhas em massa)
 *   - Email campaigns
 *   - Saude da base de contatos (opt-in %)
 */
class MobilizationAgentTools
{
    public static function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'metricas_whatsapp_broadcasts',
                    'description' => 'Metricas agregadas de campanhas WhatsApp (disparos em massa) no periodo. Retorna total de campanhas, mensagens enviadas, taxa de sucesso agregada, campanhas por status. Sem PII — nao inclui numero de telefone nem nome de contato.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Ultimos N dias considerados. Default 60.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'metricas_email_campaigns',
                    'description' => 'Metricas agregadas de e-mail marketing no periodo. Retorna total de campanhas enviadas, delivered/opens/clicks/bounces/unsubscribes agregados, taxa media de abertura e clique. Sem PII — nao inclui emails de destinatarios.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'periodo_dias' => [
                                'type'        => 'integer',
                                'description' => 'Ultimos N dias considerados. Default 60.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'saude_da_base',
                    'description' => 'Contagem da base de contatos (doadores, se tenant NGO) e percentual com opt-in ativo para email marketing. Sinal de "estoque de audiencia disponivel para campanha". Sem PII — retorna somente numeros agregados.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ];
    }

    public static function execute(string $name, array $args, int $tenantId): array
    {
        Log::info('StrategyRoom/Mobilizacao: tool executada', [
            'name' => $name, 'tenant_id' => $tenantId, 'args' => $args,
        ]);

        return match ($name) {
            'metricas_whatsapp_broadcasts' => self::metricasWhatsappBroadcasts($args, $tenantId),
            'metricas_email_campaigns'     => self::metricasEmailCampaigns($args, $tenantId),
            'saude_da_base'                => self::saudeDaBase($args, $tenantId),
            default => ['error' => "Ferramenta desconhecida: {$name}"],
        };
    }

    private static function metricasWhatsappBroadcasts(array $args, int $tenantId): array
    {
        $periodoDias = isset($args['periodo_dias']) ? max(1, (int) $args['periodo_dias']) : 60;
        $desde       = now()->subDays($periodoDias);

        $campaigns = WhatsappCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $desde)
            ->get(['id', 'status', 'scheduled_at', 'created_at']);

        $totalCampanhas = $campaigns->count();

        $porStatus = $campaigns->groupBy('status')
            ->map(fn ($grp) => $grp->count())
            ->toArray();

        // Mensagens agregadas — via campaign IDs pra respeitar tenant scoping
        $messagesAgg = [
            'total'     => 0,
            'sent'      => 0,
            'failed'    => 0,
            'pending'   => 0,
            'delivered' => 0,
        ];

        if ($totalCampanhas > 0) {
            $campaignIds = $campaigns->pluck('id')->all();
            $agg = WhatsappCampaignMessage::whereIn('whatsapp_campaign_id', $campaignIds)
                ->select('status', DB::raw('COUNT(*) as n'))
                ->groupBy('status')
                ->pluck('n', 'status')
                ->toArray();

            $messagesAgg['total']     = array_sum($agg);
            $messagesAgg['sent']      = (int) ($agg['sent']      ?? 0);
            $messagesAgg['failed']    = (int) ($agg['failed']    ?? 0);
            $messagesAgg['pending']   = (int) ($agg['pending']   ?? 0);
            $messagesAgg['delivered'] = (int) ($agg['delivered'] ?? 0);
        }

        $taxaSucessoAgregada = $messagesAgg['total'] > 0
            ? round((($messagesAgg['sent'] + $messagesAgg['delivered']) / $messagesAgg['total']) * 100, 1)
            : null;

        return [
            'success'         => true,
            'periodo_dias'    => $periodoDias,
            'total_campanhas' => $totalCampanhas,
            'por_status'      => $porStatus,
            'mensagens'       => $messagesAgg,
            'taxa_sucesso_pct'=> $taxaSucessoAgregada,
            'instrucao_llm'   => $totalCampanhas === 0
                ? 'Nao houve campanha WhatsApp no periodo. Se o tenant tem canal WhatsApp configurado, isso e sinal de subutilizacao — mencione. Se nao tem, mencione oportunidade de ativar.'
                : 'Cite total de campanhas, mensagens e taxa_sucesso_pct se relevante. NAO invente nomes de campanhas — voce nao tem esses dados. Foque no VOLUME e QUALIDADE agregada.',
        ];
    }

    private static function metricasEmailCampaigns(array $args, int $tenantId): array
    {
        $periodoDias = isset($args['periodo_dias']) ? max(1, (int) $args['periodo_dias']) : 60;
        $desde       = now()->subDays($periodoDias);

        $campaigns = EmailCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $desde)
            ->get(['status', 'recipient_count', 'stat_delivered', 'stat_opens', 'stat_clicks', 'stat_bounces', 'stat_unsubscribes', 'stat_spam']);

        $totalCampanhas = $campaigns->count();
        $enviadas       = $campaigns->where('status', 'sent')->count();

        $stats = [
            'recipient_count'   => (int) $campaigns->sum('recipient_count'),
            'stat_delivered'    => (int) $campaigns->sum('stat_delivered'),
            'stat_opens'        => (int) $campaigns->sum('stat_opens'),
            'stat_clicks'       => (int) $campaigns->sum('stat_clicks'),
            'stat_bounces'      => (int) $campaigns->sum('stat_bounces'),
            'stat_unsubscribes' => (int) $campaigns->sum('stat_unsubscribes'),
            'stat_spam'         => (int) $campaigns->sum('stat_spam'),
        ];

        $taxa_abertura = $stats['stat_delivered'] > 0
            ? round(($stats['stat_opens'] / $stats['stat_delivered']) * 100, 1)
            : null;
        $taxa_clique   = $stats['stat_delivered'] > 0
            ? round(($stats['stat_clicks'] / $stats['stat_delivered']) * 100, 1)
            : null;
        $taxa_bounce   = $stats['recipient_count'] > 0
            ? round(($stats['stat_bounces'] / $stats['recipient_count']) * 100, 1)
            : null;

        return [
            'success'         => true,
            'periodo_dias'    => $periodoDias,
            'total_campanhas' => $totalCampanhas,
            'enviadas'        => $enviadas,
            'agregado'        => $stats,
            'taxa_abertura_pct' => $taxa_abertura,
            'taxa_clique_pct'   => $taxa_clique,
            'taxa_bounce_pct'   => $taxa_bounce,
            'instrucao_llm'   => $totalCampanhas === 0
                ? 'Nao houve campanha de email no periodo. Se ha base de contatos, mencione oportunidade de reativar canal.'
                : 'Cite taxa_abertura_pct e taxa_clique_pct comparando com referencia setorial (~20% abertura e ~2% clique sao medianos pra terceiro setor — mencione so se convier). NAO invente stats por campanha, use so o agregado.',
        ];
    }

    private static function saudeDaBase(array $args, int $tenantId): array
    {
        // Doadores (so aplicavel a tenant NGO) — se tabela nao existe pra
        // tenant business, simplesmente retorna 0. NgoDonor tem tenant_id.
        $totalDoadores = NgoDonor::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->count();

        $comOptInEmail = 0;
        $pctOptInEmail = null;
        if ($totalDoadores > 0) {
            $comOptInEmail = NgoDonor::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('email_marketing_opt_in', true)
                ->count();
            $pctOptInEmail = round(($comOptInEmail / $totalDoadores) * 100, 1);
        }

        return [
            'success'          => true,
            'total_doadores'   => $totalDoadores,
            'com_opt_in_email' => $comOptInEmail,
            'pct_opt_in_email' => $pctOptInEmail,
            'instrucao_llm'    => $totalDoadores === 0
                ? 'Base de doadores esta vazia. Se tenant e NGO, isso e sinal critico — mencione como oportunidade de captura via formulario/landing. Se nao e NGO, ignore.'
                : ($pctOptInEmail !== null && $pctOptInEmail < 50
                    ? 'Menos da metade da base tem opt-in de email — grande oportunidade de reengajamento via campanha de opt-in.'
                    : 'Base saudavel de opt-in.'),
        ];
    }
}
