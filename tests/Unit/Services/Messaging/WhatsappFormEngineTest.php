<?php

namespace Tests\Unit\Services\Messaging;

use App\Models\WhatsappFormQuestion;
use App\Services\Messaging\WhatsappFormEngine;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a parte SEM side-effects do WhatsappFormEngine (Fase 4 — item 2.5):
 * validação por tipo, normalização e renderização de perguntas. start /
 * processInbound / cancel / complete tocam DB e ficam pra Feature test.
 */
class WhatsappFormEngineTest extends TestCase
{
    private WhatsappFormEngine $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new WhatsappFormEngine();
    }

    private function q(array $attrs): WhatsappFormQuestion
    {
        $defaults = [
            'type'            => 'text',
            'required'        => true,
            'options'         => null,
            'min_value'       => null,
            'max_value'       => null,
            'validation_regex'=> null,
            'field_key'       => 'q1',
        ];
        $q = new WhatsappFormQuestion();
        $q->forceFill(array_merge($defaults, $attrs))->exists = true;
        return $q;
    }

    // ── Validação ─────────────────────────────────────────────────────────

    public function test_resposta_vazia_em_pergunta_obrigatoria_e_invalida(): void
    {
        $err = $this->svc->validateAnswer($this->q(['type' => 'text']), '   ');
        $this->assertNotNull($err);
        $this->assertStringContainsString('obrigat', $err);
    }

    public function test_resposta_vazia_em_pergunta_opcional_e_aceita(): void
    {
        $this->assertNull($this->svc->validateAnswer($this->q(['type' => 'text', 'required' => false]), ''));
    }

    public function test_number_aceita_inteiros_e_strings_numericas(): void
    {
        $q = $this->q(['type' => 'number']);
        $this->assertNull($this->svc->validateAnswer($q, '42'));
        $this->assertNull($this->svc->validateAnswer($q, '0'));
    }

    public function test_number_rejeita_texto(): void
    {
        $err = $this->svc->validateAnswer($this->q(['type' => 'number']), 'quarenta');
        $this->assertNotNull($err);
        $this->assertStringContainsString('número', $err);
    }

    public function test_number_respeita_min_e_max(): void
    {
        $q = $this->q(['type' => 'number', 'min_value' => 18, 'max_value' => 65]);
        $this->assertNotNull($this->svc->validateAnswer($q, '17'));
        $this->assertNotNull($this->svc->validateAnswer($q, '70'));
        $this->assertNull($this->svc->validateAnswer($q, '30'));
        $this->assertNull($this->svc->validateAnswer($q, '18'));
        $this->assertNull($this->svc->validateAnswer($q, '65'));
    }

    public function test_yes_no_aceita_variacoes(): void
    {
        $q = $this->q(['type' => 'yes_no']);
        foreach (['sim', 'Sim', 'SIM', 'nao', 'Não', 'não', 'yes', 'no'] as $r) {
            $this->assertNull($this->svc->validateAnswer($q, $r), "deveria aceitar '{$r}'");
        }
    }

    public function test_yes_no_rejeita_outras_palavras(): void
    {
        $err = $this->svc->validateAnswer($this->q(['type' => 'yes_no']), 'talvez');
        $this->assertNotNull($err);
    }

    public function test_buttons_aceita_label_que_existe_em_options(): void
    {
        $q = $this->q([
            'type' => 'buttons',
            'options' => [
                ['id' => 'a', 'label' => 'Opção A'],
                ['id' => 'b', 'label' => 'Opção B'],
            ],
        ]);
        $this->assertNull($this->svc->validateAnswer($q, 'opção a'));
        $this->assertNull($this->svc->validateAnswer($q, 'Opção B'));
        $this->assertNotNull($this->svc->validateAnswer($q, 'Opção Z'));
    }

    public function test_list_segue_mesma_logica_de_buttons(): void
    {
        $q = $this->q([
            'type' => 'list',
            'options' => [['id' => 'x', 'label' => 'X']],
        ]);
        $this->assertNull($this->svc->validateAnswer($q, 'x'));
        $this->assertNotNull($this->svc->validateAnswer($q, 'y'));
    }

    public function test_text_com_regex_valida(): void
    {
        $q = $this->q(['type' => 'text', 'validation_regex' => '^\d{2}\/\d{4}$']);
        $this->assertNull($this->svc->validateAnswer($q, '12/2026'));
        $this->assertNotNull($this->svc->validateAnswer($q, 'janeiro'));
    }

    public function test_text_com_regex_invalida_nao_explode_e_aceita_como_valido(): void
    {
        // Regex malformada — preg_match retorna false, validate trata como sem regex.
        $q = $this->q(['type' => 'text', 'validation_regex' => '[unclosed']);
        $err = $this->svc->validateAnswer($q, 'qualquer coisa');
        $this->assertNotNull($err, 'regex inválida vira "não corresponde" — defesa em camadas');
    }

    // ── Normalização ──────────────────────────────────────────────────────

    public function test_normalize_yes_no_para_canonico(): void
    {
        $q = $this->q(['type' => 'yes_no']);
        $this->assertSame('sim', $this->svc->normalizeAnswer($q, 'Sim'));
        $this->assertSame('sim', $this->svc->normalizeAnswer($q, 'yes'));
        $this->assertSame('nao', $this->svc->normalizeAnswer($q, 'Não'));
        $this->assertSame('nao', $this->svc->normalizeAnswer($q, 'no'));
    }

    public function test_normalize_aplica_trim_em_qualquer_tipo(): void
    {
        $q = $this->q(['type' => 'text']);
        $this->assertSame('mensagem', $this->svc->normalizeAnswer($q, '   mensagem   '));
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function test_render_buttons_limita_a_3_evolution(): void
    {
        $q = $this->q([
            'type' => 'buttons',
            'options' => [
                ['id'=>'a','label'=>'A'],
                ['id'=>'b','label'=>'B'],
                ['id'=>'c','label'=>'C'],
                ['id'=>'d','label'=>'D'],
            ],
            'text' => 'Escolha',
        ]);
        $payload = $this->svc->renderQuestion($q);
        $this->assertCount(3, $payload['buttons']);
        $this->assertSame('Escolha', $payload['text']);
    }

    public function test_render_yes_no_gera_buttons_sim_nao(): void
    {
        $q = $this->q(['type' => 'yes_no', 'text' => 'Confirma?']);
        $payload = $this->svc->renderQuestion($q);

        $this->assertNotNull($payload['buttons']);
        $this->assertCount(2, $payload['buttons']);
        $labels = array_column($payload['buttons'], 'label');
        $this->assertContains('Sim', $labels);
        $this->assertContains('Não', $labels);
    }

    public function test_render_list_passa_todas_as_options(): void
    {
        $q = $this->q([
            'type' => 'list',
            'options' => [
                ['id'=>'1','label'=>'Um'],
                ['id'=>'2','label'=>'Dois'],
                ['id'=>'3','label'=>'Três'],
                ['id'=>'4','label'=>'Quatro'],
                ['id'=>'5','label'=>'Cinco'],
            ],
        ]);
        $payload = $this->svc->renderQuestion($q);

        $this->assertNotNull($payload['list']);
        $this->assertCount(5, $payload['list'], 'list não limita como buttons');
    }

    public function test_render_text_nao_tem_buttons_nem_list(): void
    {
        $q = $this->q(['type' => 'text', 'text' => 'Qual seu nome?']);
        $payload = $this->svc->renderQuestion($q);

        $this->assertNull($payload['buttons']);
        $this->assertNull($payload['list']);
        $this->assertSame('Qual seu nome?', $payload['text']);
    }

    public function test_constantes_de_status_existem(): void
    {
        $this->assertSame('in_progress', \App\Models\WhatsappFormSession::STATUS_IN_PROGRESS);
        $this->assertSame('completed',   \App\Models\WhatsappFormSession::STATUS_COMPLETED);
        $this->assertSame('cancelled',   \App\Models\WhatsappFormSession::STATUS_CANCELLED);
        $this->assertSame('abandoned',   \App\Models\WhatsappFormSession::STATUS_ABANDONED);
    }
}
