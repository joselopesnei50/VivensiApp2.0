<?php

use App\Models\Cozinha;
use App\Models\EstornoRefeicao;
use App\Models\Meta;
use App\Models\MetaCronograma;
use App\Models\PeriodoFechado;
use App\Models\PlanoTrabalho;
use App\Models\RegistroRefeicao;
use App\Models\Tenant;
use App\Models\TermoColaboracao;
use App\Models\User;
use App\Services\RegistroRefeicaoService;

/**
 * Cozinha Solidária — Fase 1 (critérios de aceite).
 *
 *  - Registro sem foto/geotag/presença é rejeitado ou marcado como pendência
 *    não-prestável.
 *  - Painel mostra realizado vs meta por cozinha e consolidado (cálculo
 *    backend; cobertura de UI fica pra Fase 6).
 *
 * Cobre também: imutabilidade após fechamento, estorno direta (auto) vs
 * indireta (aprovação), raio do geofence, prioridade da meta.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers de cenário ─────────────────────────────────────────────────────

function rrTenant(): Tenant
{
    return Tenant::factory()->create(['type' => 'ngo']);
}

function rrCozinha(Tenant $tenant, string $modalidade = Cozinha::MODALIDADE_INDIRETA, int $metaMes = 1000): Cozinha
{
    $termo = TermoColaboracao::create([
        'tenant_id'           => $tenant->id,
        'numero'              => 'T-' . uniqid(),
        'vigencia_inicio'     => now()->subMonths(2)->toDateString(),
        'vigencia_fim'        => now()->addYear()->toDateString(),
        'valor_global'        => 200000.00,
        'modalidade_execucao' => $modalidade,
        'status'              => TermoColaboracao::STATUS_VIGENTE,
    ]);

    // Em modalidade DIRETA o TermoColaboracaoObserver já cria a cozinha
    // automaticamente. Pra INDIRETA criamos manualmente aqui.
    if ($modalidade === Cozinha::MODALIDADE_DIRETA) {
        $cozinha = $termo->cozinhas()->first();
        $cozinha->update([
            'meta_refeicoes_mes' => $metaMes,
            'latitude'           => -23.55,
            'longitude'          => -46.63,
        ]);
        return $cozinha->refresh();
    }

    return Cozinha::create([
        'tenant_id'           => $tenant->id,
        'termo_id'            => $termo->id,
        'nome'                => 'Cozinha ' . uniqid(),
        'meta_refeicoes_mes'  => $metaMes,
        'modalidade_execucao' => $modalidade,
        'status'              => Cozinha::STATUS_ATIVA,
        'latitude'            => -23.55,
        'longitude'           => -46.63,
    ]);
}

function rrFoto(?float $lat = -23.55, ?float $lng = -46.63): array
{
    return [
        's3_path'     => 's3://bucket/foto-' . uniqid() . '.jpg',
        'latitude'    => $lat,
        'longitude'   => $lng,
        'captured_at' => now(),
        'mime_type'   => 'image/jpeg',
        'size_bytes'  => 100000,
    ];
}

function rrPresenca(string $nome = 'Beneficiário Anon'): array
{
    return ['nome' => $nome];
}

// ── Aceite 1: integridade do lastro ───────────────────────────────────────

it('registro com foto + geotag + presença vira VALIDO', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    $registro = $svc->registrar(
        $cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 120, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()],
        [rrPresenca()]
    );

    expect($registro->status)->toBe(RegistroRefeicao::STATUS_VALIDO);
    expect($registro->fotos)->toHaveCount(1);
    expect($registro->presencas)->toHaveCount(1);
});

it('registro sem foto vira PENDENTE (não-prestável)', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    $registro = $svc->registrar(
        $cozinha,
        ['tipo' => RegistroRefeicao::TIPO_JANTA, 'quantidade' => 50, 'latitude' => -23.55, 'longitude' => -46.63],
        [],
        [rrPresenca()]
    );

    expect($registro->status)->toBe(RegistroRefeicao::STATUS_PENDENTE);
});

it('registro sem presença vira PENDENTE', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    $registro = $svc->registrar(
        $cozinha,
        ['tipo' => RegistroRefeicao::TIPO_SOPA, 'quantidade' => 30, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()],
        []
    );

    expect($registro->status)->toBe(RegistroRefeicao::STATUS_PENDENTE);
});

it('registro sem geotag vira PENDENTE', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    $registro = $svc->registrar(
        $cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 80],
        [rrFoto()],
        [rrPresenca()]
    );

    expect($registro->status)->toBe(RegistroRefeicao::STATUS_PENDENTE);
});

it('geotag fora do raio (50km) vira PENDENTE', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    // Cozinha em -23.55,-46.63; ponto a ~50km
    $registro = $svc->registrar(
        $cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 90, 'latitude' => -23.0, 'longitude' => -46.63],
        [rrFoto()],
        [rrPresenca()]
    );

    expect($registro->status)->toBe(RegistroRefeicao::STATUS_PENDENTE);
});

// ── Aceite 2: realizado vs meta ────────────────────────────────────────────

it('realizadoVsMeta soma só registros VALIDOS no mês', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_INDIRETA, 500);
    $svc = app(RegistroRefeicaoService::class);

    $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 200, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);
    $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_JANTA, 'quantidade' => 150, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);
    // pendente — NÃO conta
    $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_SOPA, 'quantidade' => 999],
        [], []);

    $r = $svc->realizadoVsMeta($cozinha, now());
    expect($r['realizado'])->toBe(350);
    expect($r['meta'])->toBe(500);
    expect($r['percentual'])->toBe(70.0);
    expect($r['status'])->toBe('risco');
    expect($r['fonte_meta'])->toBe('cozinha_operacional');
});

it('realizadoVsMeta prioriza MetaCronograma quando existe', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_INDIRETA, 500);
    $plano = PlanoTrabalho::create([
        'tenant_id'  => $tenant->id,
        'termo_id'   => $cozinha->termo_id,
        'versao'     => 1,
        'status'     => PlanoTrabalho::STATUS_APROVADO,
        'aprovado_em' => now()->subMonth()->toDateString(),
    ]);
    $meta = Meta::create([
        'tenant_id'           => $tenant->id,
        'plano_trabalho_id'   => $plano->id,
        'tipo'                => Meta::TIPO_FISICA,
        'descricao'           => 'Refeições mensais',
        'unidade'             => 'refeições/mês',
        'quantidade_prevista' => 2000,
    ]);
    MetaCronograma::create([
        'meta_id'    => $meta->id,
        'mes'        => now()->startOfMonth()->toDateString(),
        'quantidade' => 2000,
    ]);

    $svc = app(RegistroRefeicaoService::class);
    $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 1900, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);

    $r = $svc->realizadoVsMeta($cozinha, now());
    expect($r['meta'])->toBe(2000);
    expect($r['realizado'])->toBe(1900);
    expect($r['status'])->toBe('em_dia');
    expect($r['fonte_meta'])->toBe('plano_trabalho');
});

// ── Imutabilidade após fechamento ──────────────────────────────────────────

it('fecharPeriodo bloqueia updates diretos via observer', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);
    $ngo = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $registro = $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 100, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);

    $periodo = $svc->fecharPeriodo($cozinha, now(), $ngo);
    expect($periodo->refeicoes_total)->toBe(100);
    expect($periodo->registros_count)->toBe(1);

    expect(fn () => $registro->update(['quantidade' => 999]))
        ->toThrow(RuntimeException::class, 'período fechado');
});

it('fecharPeriodo recusa fechar duas vezes', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);
    $ngo = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $svc->fecharPeriodo($cozinha, now(), $ngo);
    expect(fn () => $svc->fecharPeriodo($cozinha, now(), $ngo))
        ->toThrow(RuntimeException::class, 'já fechado');
});

it('delete de registro sempre é bloqueado', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant);
    $svc = app(RegistroRefeicaoService::class);

    $registro = $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 50, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);

    expect(fn () => $registro->delete())
        ->toThrow(RuntimeException::class, 'não pode ser excluído');
});

// ── Estorno: direta auto, indireta aprovação ──────────────────────────────

it('estorno em modalidade DIRETA é auto-aprovado e marca registro estornado', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_DIRETA, 800);
    $svc = app(RegistroRefeicaoService::class);
    $gestor = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $registro = $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 70, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()], $gestor);

    $estorno = $svc->estornar($registro, 'Duplicidade detectada', $gestor);

    expect($estorno->status)->toBe(EstornoRefeicao::STATUS_APROVADO);
    expect($estorno->aprovado_em)->not->toBeNull();
    expect($registro->fresh()->status)->toBe(RegistroRefeicao::STATUS_ESTORNADO);
});

it('estorno em modalidade INDIRETA fica pendente até aprovação do NGO', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_INDIRETA, 800);
    $svc = app(RegistroRefeicaoService::class);
    $coord = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'coordenador_cozinha']);
    $ngo   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $registro = $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 70, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()], $coord);

    $estorno = $svc->estornar($registro, 'Quantidade incorreta', $coord);

    expect($estorno->status)->toBe(EstornoRefeicao::STATUS_PENDENTE);
    expect($registro->fresh()->status)->toBe(RegistroRefeicao::STATUS_VALIDO);

    $svc->aprovarEstorno($estorno, $ngo);
    expect($registro->fresh()->status)->toBe(RegistroRefeicao::STATUS_ESTORNADO);
});

it('estorno aprovado bypassa imutabilidade após fechamento', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_DIRETA, 800);
    $svc = app(RegistroRefeicaoService::class);
    $gestor = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $registro = $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 60, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()], $gestor);

    $svc->fecharPeriodo($cozinha, now(), $gestor);

    // Update direto bloqueado
    expect(fn () => $registro->update(['quantidade' => 1]))
        ->toThrow(RuntimeException::class);

    // Estorno via service funciona mesmo após fechamento
    $svc->estornar($registro, 'Correção pós-fechamento', $gestor);
    expect($registro->fresh()->status)->toBe(RegistroRefeicao::STATUS_ESTORNADO);
});

// ── Realizado vs meta — cache invalidação ─────────────────────────────────

it('realizadoVsMeta atualiza após novo registro (cache invalida)', function () {
    $tenant = rrTenant();
    $cozinha = rrCozinha($tenant, Cozinha::MODALIDADE_INDIRETA, 300);
    $svc = app(RegistroRefeicaoService::class);

    $r1 = $svc->realizadoVsMeta($cozinha, now());
    expect($r1['realizado'])->toBe(0);

    $svc->registrar($cozinha,
        ['tipo' => RegistroRefeicao::TIPO_ALMOCO, 'quantidade' => 100, 'latitude' => -23.55, 'longitude' => -46.63],
        [rrFoto()], [rrPresenca()]);

    $r2 = $svc->realizadoVsMeta($cozinha, now());
    expect($r2['realizado'])->toBe(100);
});
