<?php

namespace Tests\Unit\Services;

use App\Services\AntiBanTermService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a parte pura do AntiBanTermService (Fase 2 — item 4.2): leitura do
 * termo vigente, hash determinístico e detecção de versão. As partes que
 * tocam DB (latestAcceptance/hasAcceptedCurrent/accept) ficam para Feature
 * test ou validação manual no VPS.
 *
 * Stub mínimo de Container + Config — sem app bootstrap (XAMPP local PHP 8.0
 * não passa pelo vendor sym/finder).
 */
class AntiBanTermServiceTest extends TestCase
{
    private AntiBanTermService $svc;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);

        // Config stub com 2 versões: 1.0 vigente, 0.9 antiga.
        $config = new \Illuminate\Config\Repository([
            'whatsapp' => [
                'anti_ban_terms' => [
                    'current_version' => '1.0',
                    'versions' => [
                        '1.0' => [
                            'effective_at' => '2026-06-17',
                            'title'        => 'Termo de Responsabilidade',
                            'text'         => "Conteúdo da versão 1.0\nLinha 2",
                        ],
                        '0.9' => [
                            'effective_at' => '2026-05-01',
                            'title'        => 'Termo antigo',
                            'text'         => 'Conteúdo da versão 0.9',
                        ],
                    ],
                ],
            ],
        ]);
        $this->container->instance('config', $config);

        $this->svc = new AntiBanTermService();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    public function test_current_version_le_da_config(): void
    {
        $this->assertSame('1.0', $this->svc->currentVersion());
    }

    public function test_current_term_traz_titulo_texto_e_vigencia(): void
    {
        $term = $this->svc->currentTerm();

        $this->assertIsArray($term);
        $this->assertSame('Termo de Responsabilidade', $term['title']);
        $this->assertStringContainsString('Conteúdo da versão 1.0', $term['text']);
        $this->assertSame('2026-06-17', $term['effective_at']);
    }

    public function test_term_for_versao_inexistente_retorna_null(): void
    {
        $this->assertNull($this->svc->termFor('99.99'));
    }

    public function test_hash_e_deterministico_para_mesma_versao(): void
    {
        $h1 = $this->svc->hashFor('1.0');
        $h2 = $this->svc->hashFor('1.0');

        $this->assertNotNull($h1);
        $this->assertSame($h1, $h2);
        $this->assertSame(64, strlen($h1), 'hash sha256 = 64 hex chars');
    }

    public function test_hash_de_versao_diferente_e_diferente(): void
    {
        $h1 = $this->svc->hashFor('1.0');
        $h2 = $this->svc->hashFor('0.9');

        $this->assertNotNull($h1);
        $this->assertNotNull($h2);
        $this->assertNotSame($h1, $h2,
            'versões com texto diferente devem ter hashes diferentes');
    }

    public function test_hash_da_versao_inexistente_retorna_null(): void
    {
        $this->assertNull($this->svc->hashFor('99.99'));
    }

    public function test_current_hash_corresponde_ao_hash_da_versao_atual(): void
    {
        $this->assertSame(
            $this->svc->hashFor($this->svc->currentVersion()),
            $this->svc->currentHash()
        );
    }

    public function test_hash_e_estavel_a_retoques_cosmeticos_de_whitespace(): void
    {
        // Aceite gravado com o termo original — depois um deploy reformata
        // indentação no config. Hash deve permanecer o MESMO pra não
        // invalidar aceites antigos.
        $hashOriginal = $this->svc->hashFor('1.0');

        // Substitui o config com "mesmo" texto mas com espaçamento diferente.
        $configNovo = new \Illuminate\Config\Repository([
            'whatsapp' => [
                'anti_ban_terms' => [
                    'current_version' => '1.0',
                    'versions' => [
                        '1.0' => [
                            'effective_at' => '2026-06-17',
                            'title'        => 'Termo de Responsabilidade',
                            'text'         => "   Conteúdo  da   versão 1.0\n\n   Linha 2   ",
                        ],
                    ],
                ],
            ],
        ]);
        $this->container->instance('config', $configNovo);

        $svcDepoisDoRetoque = new AntiBanTermService();
        $this->assertSame($hashOriginal, $svcDepoisDoRetoque->hashFor('1.0'),
            'retoque cosmético de whitespace NÃO pode invalidar aceites antigos');
    }
}
