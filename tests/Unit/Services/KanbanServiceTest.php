<?php

namespace Tests\Unit\Services;

use App\Models\TenantOperationalProfile;
use App\Services\KanbanService;
use PHPUnit\Framework\TestCase;

/**
 * Cobre os pedaços puros do KanbanService (Fase 3 — item 2.2): templates
 * de colunas por categoria de Perfil Operacional. Operações de persistência
 * (ensureDefaultBoard, createCard, moveCard) ficam para Feature test ou
 * validação manual no VPS.
 */
class KanbanServiceTest extends TestCase
{
    private KanbanService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new KanbanService();
    }

    public function test_template_default_para_outro_tem_3_colunas_basicas(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria(TenantOperationalProfile::CATEGORIA_OUTRO);

        $this->assertCount(3, $tpl);
        $this->assertSame('A fazer',      $tpl[0]['name']);
        $this->assertSame('Em andamento', $tpl[1]['name']);
        $this->assertSame('Concluído',    $tpl[2]['name']);
    }

    public function test_template_eleitoral_e_orientado_a_mobilizacao_de_eleitor(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria(TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL);
        $names = array_column($tpl, 'name');

        $this->assertContains('Captação',    $names);
        $this->assertContains('Engajado',    $names);
        $this->assertContains('Mobilizador', $names);
        $this->assertContains('Confirmado',  $names);
        $this->assertNotContains('Backlog', $names, 'template eleitoral não deve usar termos empresariais');
    }

    public function test_template_mobilizacao_social_e_orientado_a_diálogo(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria(TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL);
        $names = array_column($tpl, 'name');

        $this->assertContains('Contato', $names);
        $this->assertContains('Em diálogo', $names);
        $this->assertContains('Apoiando', $names);
    }

    public function test_template_cultural_inclui_captacao_e_execucao(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria(TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL);
        $names = array_column($tpl, 'name');

        $this->assertContains('Ideia', $names);
        $this->assertContains('Captação', $names);
        $this->assertContains('Em execução', $names);
        $this->assertContains('Finalizado', $names);
    }

    public function test_template_empresarial_e_workflow_tradicional(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria(TenantOperationalProfile::CATEGORIA_PROJETO_EMPRESARIAL);
        $names = array_column($tpl, 'name');

        $this->assertContains('Backlog', $names);
        $this->assertContains('Em andamento', $names);
        $this->assertContains('Em revisão', $names);
        $this->assertContains('Concluído', $names);
    }

    public function test_categoria_desconhecida_cai_no_template_default(): void
    {
        $tpl = $this->svc->columnsTemplateForCategoria('foobar');
        $this->assertCount(3, $tpl);
        $this->assertSame('A fazer', $tpl[0]['name']);
    }

    public function test_todas_as_colunas_tem_cor_hex(): void
    {
        foreach ([
            TenantOperationalProfile::CATEGORIA_OUTRO,
            TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL,
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL,
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL,
            TenantOperationalProfile::CATEGORIA_PROJETO_EMPRESARIAL,
        ] as $cat) {
            foreach ($this->svc->columnsTemplateForCategoria($cat) as $col) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $col['color'],
                    "categoria {$cat}: cor '{$col['color']}' deveria ser hex 7 chars");
            }
        }
    }

    public function test_position_gap_e_1024_pra_facilitar_reorder(): void
    {
        $this->assertSame(1024, KanbanService::POSITION_GAP);
    }
}
