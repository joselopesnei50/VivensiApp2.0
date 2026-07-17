<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantOperationalProfile;

/**
 * PerfilOperacionalService — Fase 1 do roadmap (Painel Gestor de Projetos).
 *
 * Centraliza a leitura do perfil operacional do tenant e a derivação dos
 * artefatos que dependem dele: KPIs do Centro de Comando, contexto para o
 * system prompt do Bruce e vocabulário (rótulos) da UI.
 *
 * Tenants sem registro caem no perfil "outro" — comportamento default
 * (neutro) mantém compatibilidade com o painel atual.
 */
class PerfilOperacionalService
{
    public function get(Tenant $tenant): ?TenantOperationalProfile
    {
        return $tenant->operationalProfile;
    }

    public function getCategoria(Tenant $tenant): string
    {
        return $this->get($tenant)?->categoria ?? TenantOperationalProfile::CATEGORIA_OUTRO;
    }

    public function getInstrucao(Tenant $tenant): ?string
    {
        return $this->get($tenant)?->instrucao;
    }

    /**
     * KPIs do Centro de Comando, escolhidos pela categoria.
     * Cada KPI: ['label' => string, 'kind' => 'count'|'currency'|'percent', 'source' => string].
     * 'source' é uma chave estável para a view resolver o valor real.
     *
     * @return array<string,array{label:string,kind:string,source:string}>
     */
    public function getKpis(Tenant $tenant): array
    {
        return $this->kpisForCategoria($this->getCategoria($tenant));
    }

    public function getBruceContext(Tenant $tenant): string
    {
        $cat = $this->getCategoria($tenant);
        $instr = $this->getInstrucao($tenant);
        return $this->bruceContextForCategoria($cat, $instr, $tenant->name);
    }

    /**
     * Sobrescrita de rótulos da UI. Ex: ['lead' => 'eleitor', 'projeto' => 'campanha'].
     *
     * @return array<string,string>
     */
    public function getVocabulary(Tenant $tenant): array
    {
        $custom = $this->get($tenant)?->vocabulario ?? [];
        $defaults = $this->vocabularyForCategoria($this->getCategoria($tenant));
        return array_merge($defaults, $custom);
    }

    public function term(Tenant $tenant, string $key, string $fallback): string
    {
        return $this->getVocabulary($tenant)[$key] ?? $fallback;
    }

    public static function categorias(): array
    {
        return TenantOperationalProfile::CATEGORIAS;
    }

    /** KPIs base hardcoded já presentes na hero do Centro de Comando. */
    private const KPIS_BASE = ['active_projects', 'pending_approvals'];

    /**
     * Sources que o Controller precisa resolver pra montar os KPIs operacionais
     * do tenant. Exclui os KPIs base que a hero já renderiza hardcoded.
     *
     * @return list<string>
     */
    public function getKpiSources(Tenant $tenant): array
    {
        return $this->sourcesForCategoria($this->getCategoria($tenant));
    }

    /**
     * KPIs operacionais (não-base) com label + valor já resolvido, prontos pra
     * renderizar. Categorias sem KPIs operacionais (ex.: 'outro') retornam
     * array vazio — a view não deve mostrar o bloco extra nesse caso.
     *
     * @param array<string,mixed> $sourceValues map de source => value já calculado
     * @return array<string,array{label:string,kind:string,value:mixed}>
     */
    public function getResolvedKpis(Tenant $tenant, array $sourceValues = []): array
    {
        return $this->resolvedKpisForCategoria($this->getCategoria($tenant), $sourceValues);
    }

    /**
     * Versão pura — não depende de Tenant. Útil pra testes e pra UI consultar
     * antes de persistir uma categoria.
     *
     * @return list<string>
     */
    public function sourcesForCategoria(string $categoria): array
    {
        $sources = [];
        foreach ($this->kpisForCategoria($categoria) as $kpi) {
            if (in_array($kpi['source'], self::KPIS_BASE, true)) {
                continue;
            }
            $sources[] = $kpi['source'];
        }
        return $sources;
    }

    /**
     * Versão pura — não depende de Tenant.
     *
     * @param array<string,mixed> $sourceValues
     * @return array<string,array{label:string,kind:string,value:mixed}>
     */
    public function resolvedKpisForCategoria(string $categoria, array $sourceValues = []): array
    {
        $resolved = [];
        foreach ($this->kpisForCategoria($categoria) as $key => $kpi) {
            if (in_array($key, self::KPIS_BASE, true)) {
                continue;
            }
            $value = $sourceValues[$kpi['source']] ?? null;
            // Source que o Controller não soube resolver é omitido — evita
            // renderizar card "R$ 0" confuso (ex.: monthly_revenue duplicaria
            // a Maré Financeira da hero pra categoria 'outro' e cultural).
            if ($value === null) {
                continue;
            }
            $resolved[$key] = [
                'label' => $kpi['label'],
                'kind'  => $kpi['kind'],
                'value' => $value,
            ];
        }
        return $resolved;
    }

    /**
     * Métodos puros derivativos — não dependem de Tenant nem do banco.
     * Ficam públicos para testabilidade e para a UI consultar antes de
     * persistir (preview na tela de edição da Etapa B).
     *
     * @return array<string,array{label:string,kind:string,source:string}>
     */
    public function kpisForCategoria(string $categoria): array
    {
        $base = [
            'active_projects' => [
                'label'  => 'Missões Ativas',
                'kind'   => 'count',
                'source' => 'active_projects',
            ],
            'pending_approvals' => [
                'label'  => 'Aprovações Pendentes',
                'kind'   => 'count',
                'source' => 'pending_approvals',
            ],
        ];

        return match ($categoria) {
            // MOBILIZACAO_SOCIAL e CAMPANHA_ELEITORAL usam apenas os KPIs base
            // (active_projects + pending_approvals). Os KPIs `whatsapp_inbound_total`
            // e `leads_total` foram removidos por decisao do gestor em 2026-07-17 —
            // ja estao expostos no bloco "Base de Cadastros" logo abaixo do hero.
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL,
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL => $base,
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL => $base + [
                'monthly_revenue' => [
                    'label'  => 'Captação no Mês',
                    'kind'   => 'currency',
                    'source' => 'monthly_revenue',
                ],
            ],
            default => $base + [
                'monthly_revenue' => [
                    'label'  => 'Entrada / Mês',
                    'kind'   => 'currency',
                    'source' => 'monthly_revenue',
                ],
            ],
        };
    }

    /**
     * @return array<string,string>
     */
    public function vocabularyForCategoria(string $categoria): array
    {
        return match ($categoria) {
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL => [
                'lead'    => 'eleitor',
                'projeto' => 'campanha',
                'equipe'  => 'comitê',
            ],
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL => [
                'lead'    => 'apoiador',
                'projeto' => 'projeto cultural',
            ],
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL => [
                'lead'    => 'mobilizado',
                'projeto' => 'mobilização',
            ],
            default => [
                'lead'    => 'lead',
                'projeto' => 'projeto',
            ],
        };
    }

    public function bruceContextForCategoria(string $categoria, ?string $instrucao, ?string $tenantName = null): string
    {
        $label = TenantOperationalProfile::CATEGORIAS[$categoria] ?? 'Outro';
        $org   = $tenantName ?: 'a organização';
        $linhas = [
            "Você atende a organização \"{$org}\", cujo perfil operacional é: {$label}.",
        ];

        $rules = match ($categoria) {
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL =>
                "Contexto eleitoral — siga a Lei 9.504/97 e as regras do TSE. "
                . "Opinião política é dado sensível: não confirme nem registre alinhamento partidário "
                . "sem consentimento explícito. Não prometa benefícios em troca de voto. "
                . "Não oriente disparos em massa sem opt-in válido registrado, nem simule pesquisa "
                . "eleitoral. Respeite as janelas de propaganda e o período de silêncio definidos pela "
                . "Lei 9.504/97.",
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL =>
                "Contexto cultural — fale de modo acolhedor sobre captação, editais e ações culturais.",
            TenantOperationalProfile::CATEGORIA_PROJETO_EMPRESARIAL =>
                "Contexto empresarial — tom profissional, objetivo, foco em resultado e gestão.",
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL =>
                "Contexto de mobilização social — linguagem inclusiva, valorize participação cidadã.",
            default => null,
        };
        if ($rules) {
            $linhas[] = $rules;
        }

        if (is_string($instrucao) && trim($instrucao) !== '') {
            $linhas[] = "Instrução adicional do operador:\n" . trim($instrucao);
        }

        return implode("\n\n", $linhas);
    }
}
