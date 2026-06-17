<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\EmailQuotaService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

/**
 * Cobre EmailQuotaService (Fase 2 — item 3.3). Usa Cache::store(array) em
 * memória pra evitar dependência de Redis. As regras de cota são puro
 * cálculo aritmético + manipulação de Cache.
 */
class EmailQuotaServiceTest extends TestCase
{
    private EmailQuotaService $svc;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->container);

        // Cache em memória — cada teste começa zerado por causa do setUp.
        $repo = new Repository(new ArrayStore());
        $this->container->instance('cache.store', $repo);
        $this->container->instance('cache', new class($repo) {
            public function __construct(private Repository $repo) {}
            public function get(...$args)       { return $this->repo->get(...$args); }
            public function put(...$args)       { return $this->repo->put(...$args); }
            public function add(...$args)       { return $this->repo->add(...$args); }
            public function increment(...$args) { return $this->repo->increment(...$args); }
            public function forget(...$args)    { return $this->repo->forget(...$args); }
        });

        $this->svc = new EmailQuotaService();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    private function tenant(int $id = 1, ?int $quota = null): Tenant
    {
        $t = new Tenant();
        // Evita Eloquent boot — só seta atributos que o Service lê.
        $t->forceFill(['id' => $id, 'daily_email_quota' => $quota])->exists = true;
        return $t;
    }

    public function test_quota_default_50_quando_tenant_nao_tem_campo(): void
    {
        $this->assertSame(50, $this->svc->getQuota($this->tenant(1, null)));
    }

    public function test_quota_lida_do_tenant_quando_setada(): void
    {
        $this->assertSame(500, $this->svc->getQuota($this->tenant(1, 500)));
    }

    public function test_quota_negativa_e_normalizada_pra_zero(): void
    {
        $this->assertSame(0, $this->svc->getQuota($this->tenant(1, -10)));
    }

    public function test_sent_today_zero_quando_nenhum_envio(): void
    {
        $this->assertSame(0, $this->svc->getSentToday($this->tenant(1, 50)));
    }

    public function test_remaining_e_quota_menos_sent(): void
    {
        $t = $this->tenant(1, 50);
        $this->svc->tryConsume($t, 12);
        $this->assertSame(38, $this->svc->getRemainingToday($t));
    }

    public function test_would_exceed_indica_corretamente_sem_consumir(): void
    {
        $t = $this->tenant(1, 50);
        $this->svc->tryConsume($t, 40);

        $this->assertFalse($this->svc->wouldExceed($t, 10));
        $this->assertTrue($this->svc->wouldExceed($t, 11));

        // Não consumiu nada extra — sent ainda é 40.
        $this->assertSame(40, $this->svc->getSentToday($t));
    }

    public function test_try_consume_dentro_da_cota_incrementa(): void
    {
        $t = $this->tenant(1, 50);

        $this->assertTrue($this->svc->tryConsume($t, 30));
        $this->assertSame(30, $this->svc->getSentToday($t));

        $this->assertTrue($this->svc->tryConsume($t, 20));
        $this->assertSame(50, $this->svc->getSentToday($t));
    }

    public function test_try_consume_que_estoura_nao_incrementa(): void
    {
        $t = $this->tenant(1, 50);
        $this->svc->tryConsume($t, 40);

        $this->assertFalse($this->svc->tryConsume($t, 11), 'tentativa que estoura deve falhar');
        $this->assertSame(40, $this->svc->getSentToday($t),
            'falha não pode incrementar o contador');
    }

    public function test_count_zero_ou_negativo_e_no_op(): void
    {
        $t = $this->tenant(1, 50);
        $this->assertTrue($this->svc->tryConsume($t, 0));
        $this->assertTrue($this->svc->tryConsume($t, -5));
        $this->assertSame(0, $this->svc->getSentToday($t));
        $this->assertFalse($this->svc->wouldExceed($t, 0));
    }

    public function test_refund_devolve_a_cota(): void
    {
        $t = $this->tenant(1, 50);
        $this->svc->tryConsume($t, 30);
        $this->svc->refund($t, 10);

        $this->assertSame(20, $this->svc->getSentToday($t));
        $this->assertSame(30, $this->svc->getRemainingToday($t));
    }

    public function test_refund_alem_do_sent_nao_fica_negativo(): void
    {
        $t = $this->tenant(1, 50);
        $this->svc->tryConsume($t, 5);
        $this->svc->refund($t, 100);

        $this->assertSame(0, $this->svc->getSentToday($t));
    }

    public function test_chave_isola_tenants_diferentes(): void
    {
        $t1 = $this->tenant(1, 50);
        $t2 = $this->tenant(2, 50);

        $this->svc->tryConsume($t1, 30);
        $this->assertSame(30, $this->svc->getSentToday($t1));
        $this->assertSame(0,  $this->svc->getSentToday($t2),
            'cota do tenant 2 não pode ser afetada pelo tenant 1');
    }

    public function test_chave_contem_data_corrente(): void
    {
        $key = $this->svc->key($this->tenant(7, 100));
        $hoje = now()->toDateString();
        $this->assertSame("email_quota:tenant:7:{$hoje}", $key);
    }

    public function test_tenant_com_quota_zero_nao_envia_nada(): void
    {
        $t = $this->tenant(1, 0);
        $this->assertFalse($this->svc->tryConsume($t, 1));
        $this->assertSame(0, $this->svc->getRemainingToday($t));
    }
}
