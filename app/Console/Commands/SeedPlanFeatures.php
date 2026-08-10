<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;

/**
 * Popula SubscriptionPlan.features com listas curadas por audience (2026-08-07).
 *
 * Escalona por preco quando ha 2+ planos no mesmo audience:
 *  - 1 plano  -> lista FULL
 *  - 2 planos -> BASIC + FULL
 *  - 3 planos -> BASIC + PRO + FULL
 *  - 4+       -> primeiros BASIC/PRO, restantes FULL (raro)
 *
 * Uso:
 *   php artisan plans:seed-features --dry-run   (preview sem gravar)
 *   php artisan plans:seed-features              (grava; pula planos com features ja preenchidos)
 *   php artisan plans:seed-features --force      (grava e sobrescreve tudo)
 */
class SeedPlanFeatures extends Command
{
    protected $signature   = 'plans:seed-features {--dry-run : Preview sem gravar} {--force : Sobrescreve features existentes}';
    protected $description = 'Popula SubscriptionPlan.features com listas curadas por target_audience (ngo/manager/common).';

    /** Features do painel Terceiro Setor (produto principal). */
    private const NGO = [
        'basic' => [
            'CRM de Doadores com portal privado de recibos',
            'Bruce IA integrado (copywriting, análise, chatbot)',
            'WhatsApp Cloud oficial Meta + chat unificado',
            'Fluxo de caixa por projeto + DRE automático',
            'Portal Público de Transparência (LGPD-nativo)',
            'Gestão de Projetos com Kanban e Turmas',
            'Beneficiários com PII cifrado at-rest',
            'Vivensi Academy (LMS interno com certificados)',
            'Suporte por WhatsApp',
        ],
        'pro' => [
            'Tudo do plano Essencial +',
            'Radar de Editais automatizado (Querido Diário)',
            'Gerador de propostas para editais com IA',
            'Sala de Estratégia (5 agentes de IA)',
            'Motor de Conformidade CEBAS/MROSC/SUAS em tempo real',
            'Marketing IA: posts, calendário editorial, Social AI Hub',
            'E-mail marketing via Brevo integrado',
            'Broadcast WhatsApp com anti-ban (texto, imagem, áudio)',
            'Landing Pages Builder com campos personalizados',
            'CRM de Patrocínios',
            'Rifas Online com QR PIX',
            'Analytics de redes sociais (FB + IG)',
        ],
        'full' => [
            'Tudo do plano Pro +',
            '🎯 Múltiplas instâncias WhatsApp (Cloud + Evolution)',
            '🎯 Inteligência Territorial (mapas + dados sociais)',
            '🎯 Auto-assign round-robin no chat WhatsApp',
            '🎯 Dashboard de produtividade dos agentes',
            '🎯 Auditoria completa (logs LGPD, exportação art. 15/18)',
            '🎯 Almoxarifado + Patrimônio + anexos polimórficos',
            '🎯 Voluntariado com log de horas + certificados digitais',
            '🎯 Contratos digitais com assinatura',
            '🎯 Relatórios anuais de impacto social prontos',
            '🎯 Onboarding assistido + gestor de conta dedicado',
        ],
    ];

    /** Features do painel Gestor de Projetos. */
    private const MANAGER = [
        'basic' => [
            'Gestão de Projetos com Kanban',
            'Bruce IA integrado (copiloto de projetos)',
            'WhatsApp Cloud oficial Meta',
            'Time tracking + gestão de tarefas',
            'Fluxo de caixa por projeto',
            'Equipe: cadastro, permissões, vínculo por projeto',
            'Landing Pages Builder para captação',
            'Suporte por WhatsApp',
        ],
        'pro' => [
            'Tudo do plano Essencial +',
            'Sala de Estratégia (5 agentes de IA)',
            'Marketing IA: posts, calendário, Social AI Hub',
            'E-mail marketing via Brevo integrado',
            'Broadcast WhatsApp com anti-ban',
            'Analytics de projetos + redes sociais',
            'DRE automático + orçamento anual',
            'Conciliação bancária + importação CSV',
            'Kanban de aprovações',
        ],
        'full' => [
            'Tudo do plano Pro +',
            '🎯 Múltiplas instâncias WhatsApp',
            '🎯 Contratos digitais + assinatura',
            '🎯 Central de Auditoria completa',
            '🎯 Dashboard de produtividade da equipe',
            '🎯 Auto-assign round-robin no atendimento',
            '🎯 RH + folha simplificada',
            '🎯 Reconciliação avançada + importação bancária',
            '🎯 Onboarding assistido + gestor de conta dedicado',
        ],
    ];

    /** Features do painel TopEmpresas (MEI/PME). */
    private const COMMON = [
        'basic' => [
            'CRM de Clientes com histórico integrado',
            'Bruce IA integrado (atendimento + escrita comercial)',
            'WhatsApp comercial (chat + chatbot)',
            'Fluxo de caixa + NFS-e',
            'Termômetro do Teto MEI (R$ 81 mil)',
            'Lembrete automático do DAS (dia 20)',
            'Landing Pages Builder',
            'Suporte por WhatsApp',
        ],
        'pro' => [
            'Tudo do plano Essencial +',
            'Sala de Estratégia (5 agentes de IA)',
            'Marketing IA: posts, calendário editorial',
            'E-mail marketing via Brevo',
            'Broadcast WhatsApp com anti-ban',
            'Rifas Online com QR PIX',
            'Analytics de vendas + redes sociais',
            'Automações de resposta no WhatsApp',
            'Import de planilhas (CSV) de clientes e transações',
        ],
        'full' => [
            'Tudo do plano Pro +',
            '🎯 WhatsApp Cloud oficial Meta (número Business verificado)',
            '🎯 Múltiplos números WhatsApp',
            '🎯 Auto-assign round-robin (equipe atendendo)',
            '🎯 Dashboard de produtividade',
            '🎯 Contratos digitais',
            '🎯 Prospecção IA (leads qualificados)',
            '🎯 Inteligência Territorial (mapas de clientes)',
            '🎯 Onboarding assistido + gestor de conta dedicado',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        $catalog = [
            'ngo'     => self::NGO,
            'manager' => self::MANAGER,
            'common'  => self::COMMON,
        ];

        $audienceLabels = SubscriptionPlan::AUDIENCE_LABELS_SHORT;
        $touched = 0;
        $skipped = 0;

        foreach ($catalog as $audience => $tiers) {
            $plans = SubscriptionPlan::where('target_audience', $audience)
                ->orderBy('price', 'asc')
                ->get();

            if ($plans->isEmpty()) {
                $this->warn("→ {$audienceLabels[$audience]}: nenhum plano cadastrado.");
                continue;
            }

            $this->info("\n▸ {$audienceLabels[$audience]} ({$plans->count()} plano(s)):");

            $tierKeys = $this->distribute($plans->count(), array_keys($tiers));

            foreach ($plans as $i => $plan) {
                $tierKey = $tierKeys[$i];
                $features = $tiers[$tierKey];

                if (!$force && !empty($plan->features)) {
                    $this->line("  · [{$tierKey}] {$plan->name} — já tem " . count($plan->features) . ' features (skip; use --force pra sobrescrever)');
                    $skipped++;
                    continue;
                }

                $this->line("  · [{$tierKey}] {$plan->name} — " . count($features) . ' features');
                foreach ($features as $f) {
                    $this->line("      · {$f}");
                }

                if (!$dryRun) {
                    $plan->features = $features;
                    $plan->save();
                }
                $touched++;
            }
        }

        $this->newLine();
        $verb = $dryRun ? 'seriam atualizados' : 'atualizados';
        $this->info("✔ {$touched} plano(s) {$verb}, {$skipped} pulado(s).");
        if ($dryRun) {
            $this->line('  Rode SEM --dry-run pra aplicar.');
        }

        return self::SUCCESS;
    }

    /**
     * Distribui os tiers pros N planos: 1 plano -> [full]; 2 -> [basic,full];
     * 3+ -> [basic,pro,full,full,full...].
     */
    private function distribute(int $planCount, array $tiers): array
    {
        if ($planCount === 1) return ['full'];
        if ($planCount === 2) return ['basic', 'full'];

        $out = ['basic', 'pro', 'full'];
        for ($i = 3; $i < $planCount; $i++) {
            $out[] = 'full';
        }
        return $out;
    }
}
