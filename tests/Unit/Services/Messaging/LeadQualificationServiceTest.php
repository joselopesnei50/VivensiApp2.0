<?php

namespace Tests\Unit\Services\Messaging;

use App\Models\TenantOperationalProfile;
use App\Services\DeepSeekService;
use App\Services\GeminiService;
use App\Services\LeadService;
use App\Services\Messaging\LeadQualificationService;
use App\Services\PerfilOperacionalService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Cobre a parte pura do LeadQualificationService (Fase 4 — item 2.3):
 * roteamento de provedor por categoria, parser tolerante de JSON do LLM
 * e normalização do schema. A integração com DeepSeek/Gemini fica para
 * Feature test (exige bootstrap completo + HTTP::fake).
 */
class LeadQualificationServiceTest extends TestCase
{
    private LeadQualificationService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        // Não chamamos os métodos que tocam DeepSeek/Gemini aqui — instanciamos
        // só pra termos o objeto. Os clients são stubs vazios (new no PHP 8.0
        // requer construtores callable, esses são).
        $this->svc = new LeadQualificationService(
            new DeepSeekService(),
            new GeminiService(),
            new PerfilOperacionalService(),
            new LeadService(),
        );
    }

    public function test_categoria_eleitoral_roteia_para_gemini(): void
    {
        $this->assertSame('gemini',
            $this->svc->chooseProvider(TenantOperationalProfile::CATEGORIA_CAMPANHA_ELEITORAL));
    }

    public function test_demais_categorias_roteiam_para_deepseek(): void
    {
        foreach ([
            TenantOperationalProfile::CATEGORIA_PROJETO_CULTURAL,
            TenantOperationalProfile::CATEGORIA_PROJETO_EMPRESARIAL,
            TenantOperationalProfile::CATEGORIA_MOBILIZACAO_SOCIAL,
            TenantOperationalProfile::CATEGORIA_OUTRO,
        ] as $cat) {
            $this->assertSame('deepseek', $this->svc->chooseProvider($cat),
                "categoria {$cat} deveria rotear pra DeepSeek");
        }
    }

    public function test_parser_aceita_json_puro(): void
    {
        $raw = '{"qualification":"quente","intent":"interesse","summary":"quer agendar","next_action":"ligar","confidence":0.92}';
        $parsed = $this->svc->parseLlmJson($raw);

        $this->assertIsArray($parsed);
        $this->assertSame('quente', $parsed['qualification']);
        $this->assertSame(0.92, $parsed['confidence']);
    }

    public function test_parser_desempacota_json_em_markdown_code_block(): void
    {
        $raw = "```json\n{\"qualification\":\"morno\",\"intent\":\"interesse\",\"summary\":\"interessado\",\"confidence\":0.7}\n```";
        $parsed = $this->svc->parseLlmJson($raw);

        $this->assertIsArray($parsed);
        $this->assertSame('morno', $parsed['qualification']);
    }

    public function test_parser_desempacota_codeblock_sem_marcador_de_linguagem(): void
    {
        $raw = "```\n{\"qualification\":\"frio\",\"confidence\":0.3}\n```";
        $parsed = $this->svc->parseLlmJson($raw);

        $this->assertIsArray($parsed);
        $this->assertSame('frio', $parsed['qualification']);
    }

    public function test_parser_extrai_json_em_meio_a_texto(): void
    {
        $raw = "Aqui vai o diagnóstico: {\"qualification\":\"quente\",\"confidence\":0.85} — espero ter ajudado.";
        $parsed = $this->svc->parseLlmJson($raw);

        $this->assertIsArray($parsed);
        $this->assertSame('quente', $parsed['qualification']);
    }

    public function test_parser_devolve_null_em_texto_sem_json(): void
    {
        $this->assertNull($this->svc->parseLlmJson('só texto sem json'));
        $this->assertNull($this->svc->parseLlmJson(''));
    }

    public function test_normalize_filtra_valores_fora_da_whitelist(): void
    {
        $normalize = new ReflectionMethod($this->svc, 'normalize');
        $normalize->setAccessible(true);

        $r = $normalize->invoke($this->svc, [
            'qualification' => 'tepidio',
            'intent'        => 'banana',
            'summary'       => 'ok',
            'confidence'    => 0.5,
        ], 'deepseek');

        $this->assertNull($r['qualification'], 'valor fora da whitelist vira null');
        $this->assertNull($r['intent']);
        $this->assertSame('ok', $r['summary']);
    }

    public function test_normalize_limita_confidence_a_0_1(): void
    {
        $normalize = new ReflectionMethod($this->svc, 'normalize');
        $normalize->setAccessible(true);

        $rAlto = $normalize->invoke($this->svc, ['confidence' => 99], 'deepseek');
        $rBaixo = $normalize->invoke($this->svc, ['confidence' => -2], 'deepseek');

        $this->assertSame(1.0, $rAlto['confidence']);
        $this->assertSame(0.0, $rBaixo['confidence']);
    }

    public function test_normalize_trunca_summary_em_300_chars(): void
    {
        $normalize = new ReflectionMethod($this->svc, 'normalize');
        $normalize->setAccessible(true);

        $longo = str_repeat('a', 500);
        $r = $normalize->invoke($this->svc, ['summary' => $longo], 'deepseek');

        $this->assertSame(300, mb_strlen($r['summary']));
    }

    public function test_normalize_define_confidence_default_quando_faltando(): void
    {
        $normalize = new ReflectionMethod($this->svc, 'normalize');
        $normalize->setAccessible(true);

        $r = $normalize->invoke($this->svc, ['qualification' => 'frio'], 'deepseek');
        $this->assertSame(0.5, $r['confidence']);
    }

    public function test_normalize_inclui_provider_no_resultado(): void
    {
        $normalize = new ReflectionMethod($this->svc, 'normalize');
        $normalize->setAccessible(true);

        $r = $normalize->invoke($this->svc, ['qualification' => 'frio'], 'gemini');
        $this->assertSame('gemini', $r['provider']);
    }

    public function test_render_para_kanban_inclui_classificacao_intencao_acao(): void
    {
        $desc = $this->svc->renderForKanbanDescription([
            'qualification' => 'quente',
            'intent'        => 'interesse',
            'summary'       => 'quer agendar reunião',
            'next_action'   => 'ligar amanhã',
            'confidence'    => 0.88,
            'provider'      => 'deepseek',
            'error'         => null,
        ]);

        $this->assertStringContainsString('quer agendar reunião', $desc);
        $this->assertStringContainsString('QUENTE', $desc);
        $this->assertStringContainsString('88%', $desc);
        $this->assertStringContainsString('Interesse', $desc);
        $this->assertStringContainsString('ligar amanhã', $desc);
        $this->assertStringContainsString('deepseek', $desc);
    }

    public function test_render_aceita_qualificacao_parcial(): void
    {
        $desc = $this->svc->renderForKanbanDescription([
            'qualification' => null,
            'intent'        => null,
            'summary'       => 'resumo sem demais campos',
            'next_action'   => null,
            'confidence'    => 0.4,
            'provider'      => 'deepseek',
            'error'         => null,
        ]);

        $this->assertStringContainsString('resumo sem demais campos', $desc);
        $this->assertStringNotContainsString('Classificação:', $desc);
        $this->assertStringNotContainsString('Intenção:', $desc);
    }
}
