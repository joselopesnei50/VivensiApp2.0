<?php

namespace Tests\Unit\Services;

use App\Models\TenantOperationalProfile;
use App\Services\PerfilOperacionalService;
use PHPUnit\Framework\TestCase;

/**
 * Cobre os métodos puros do PerfilOperacionalService (Fase 1, Etapa A).
 * Não toca DB — testa as derivações de KPIs, vocabulário e contexto Bruce
 * por categoria, que é a parte sensível (regras eleitorais aparecem aqui).
 */
class PerfilOperacionalServiceTest extends TestCase
{
    private PerfilOperacionalService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new PerfilOperacionalService();
    }

    public function test_categorias_disponiveis_retorna_lista_estavel(): void
    {
        $cats = PerfilOperacionalService::categorias();

        $this->assertArrayHasKey('campanha_eleitoral', $cats);
        $this->assertArrayHasKey('projeto_cultural', $cats);
        $this->assertArrayHasKey('projeto_empresarial', $cats);
        $this->assertArrayHasKey('mobilizacao_social', $cats);
        $this->assertArrayHasKey('outro', $cats);
        $this->assertSame('Campanha Eleitoral', $cats['campanha_eleitoral']);
    }

    public function test_kpis_default_mostram_financeiro_para_outro(): void
    {
        $kpis = $this->svc->kpisForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO);

        $this->assertArrayHasKey('active_projects', $kpis);
        $this->assertArrayHasKey('monthly_revenue', $kpis);
        $this->assertSame('Entrada / Mês', $kpis['monthly_revenue']['label']);
        $this->assertSame('currency', $kpis['monthly_revenue']['kind']);
    }

    public function test_kpis_mobilizacao_substitui_financeiro_por_whatsapp(): void
    {
        $kpis = $this->svc->kpisForCategoria(TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL);

        $this->assertArrayHasKey('whatsapp_inbound_total', $kpis,
            'mobilizacao_social deve mostrar nº de mensagens WhatsApp recebidas (item 2.1 do roadmap)');
        $this->assertArrayNotHasKey('monthly_revenue', $kpis,
            'mobilizacao_social não deve mostrar KPI financeiro como destaque');
        $this->assertSame('Mensagens WhatsApp Recebidas', $kpis['whatsapp_inbound_total']['label']);
    }

    public function test_kpis_eleitoral_tem_whatsapp_e_base_de_eleitores(): void
    {
        $kpis = $this->svc->kpisForCategoria(TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL);

        $this->assertArrayHasKey('whatsapp_inbound_total', $kpis);
        $this->assertArrayHasKey('leads_total', $kpis);
        $this->assertArrayNotHasKey('monthly_revenue', $kpis);
    }

    public function test_kpis_cultural_mantem_captacao_no_mes(): void
    {
        $kpis = $this->svc->kpisForCategoria(TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL);

        $this->assertSame('Captação no Mês', $kpis['monthly_revenue']['label']);
    }

    public function test_vocabulario_eleitoral_renomeia_lead_para_eleitor(): void
    {
        $vocab = $this->svc->vocabularyForCategoria(TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL);

        $this->assertSame('eleitor', $vocab['lead']);
        $this->assertSame('campanha', $vocab['projeto']);
        $this->assertSame('comitê', $vocab['equipe']);
    }

    public function test_vocabulario_default_mantem_termos_neutros(): void
    {
        $vocab = $this->svc->vocabularyForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO);

        $this->assertSame('lead', $vocab['lead']);
        $this->assertSame('projeto', $vocab['projeto']);
    }

    public function test_bruce_context_eleitoral_inclui_aviso_de_lei_e_dado_sensivel(): void
    {
        $ctx = $this->svc->bruceContextForCategoria(
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL,
            null,
            'Comitê X'
        );

        $this->assertStringContainsString('Comitê X', $ctx);
        $this->assertStringContainsString('9.504/97', $ctx, 'lei eleitoral deve constar no contexto');
        $this->assertStringContainsString('dado sensível', $ctx, 'tratamento de dado sensível deve ser explícito');
        $this->assertStringContainsString('consentimento', $ctx);
    }

    public function test_bruce_context_default_nao_aplica_regra_eleitoral(): void
    {
        $ctx = $this->svc->bruceContextForCategoria(
            TenantOperationalProfile::CATEGORIA_OUTRO,
            null,
            'Org Y'
        );

        $this->assertStringNotContainsString('9.504/97', $ctx);
        $this->assertStringNotContainsString('TSE', $ctx);
    }

    public function test_bruce_context_concatena_instrucao_do_operador(): void
    {
        $ctx = $this->svc->bruceContextForCategoria(
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL,
            '  Foco em editais municipais  ',
            'ONG Z'
        );

        $this->assertStringContainsString('Foco em editais municipais', $ctx);
        $this->assertStringNotContainsString('  Foco em editais municipais  ', $ctx,
            'instrução deve ser trimada antes de concatenar');
    }

    public function test_bruce_context_ignora_instrucao_vazia_ou_whitespace(): void
    {
        $ctx = $this->svc->bruceContextForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO, '   ', 'X');
        $this->assertStringNotContainsString('Instrução adicional', $ctx);

        $ctx = $this->svc->bruceContextForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO, '', 'X');
        $this->assertStringNotContainsString('Instrução adicional', $ctx);
    }

    public function test_bruce_context_usa_fallback_de_nome_quando_tenant_sem_nome(): void
    {
        $ctx = $this->svc->bruceContextForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO, null, null);
        $this->assertStringContainsString('a organização', $ctx);
    }

    public function test_categoria_desconhecida_cai_no_perfil_outro(): void
    {
        $kpis  = $this->svc->kpisForCategoria('foobar');
        $vocab = $this->svc->vocabularyForCategoria('foobar');
        $ctx   = $this->svc->bruceContextForCategoria('foobar', null, 'X');

        $this->assertArrayHasKey('monthly_revenue', $kpis, 'categoria desconhecida deve ter fallback default');
        $this->assertSame('lead', $vocab['lead']);
        $this->assertStringNotContainsString('9.504/97', $ctx);
    }
}
