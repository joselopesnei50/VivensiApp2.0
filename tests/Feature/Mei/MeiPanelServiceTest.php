<?php

use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\MeiPanelService;
use Illuminate\Support\Carbon;

/**
 * Vivensi — Módulo MEI (MVP).
 * Cobre as 3 métricas-vendedoras: termômetro do teto, DAS, mini-DRE.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function meiTenant(): Tenant
{
    return Tenant::factory()->create(['type' => 'common']);
}

function meiTx(int $tenantId, string $type, float $amount, string $date, ?string $desc = null): Transaction
{
    return Transaction::create([
        'tenant_id'   => $tenantId,
        'description' => $desc ?? ($type === 'income' ? 'Venda' : 'Despesa'),
        'amount'      => $amount,
        'type'        => $type,
        'status'      => 'paid',
        'date'        => $date,
    ]);
}

// ── tetoMei ────────────────────────────────────────────────────────────────

it('teto soma so income/paid do ano corrente em centavos', function () {
    $t = meiTenant();
    meiTx($t->id, 'income',  3000.00, now()->toDateString());
    meiTx($t->id, 'income',  2500.50, now()->subMonths(2)->toDateString());
    meiTx($t->id, 'expense', 1500.00, now()->toDateString()); // ignora
    meiTx($t->id, 'income',  9999.00, now()->subYear()->toDateString()); // ignora ano

    $r = app(MeiPanelService::class)->tetoMei($t->id);

    expect($r['realizado_centavos'])->toBe(550_050);
    expect($r['teto_centavos'])->toBe(8_100_000);
    expect($r['status'])->toBe('verde');
    expect($r['faltam_centavos'])->toBe(7_549_950);
});

it('teto vira amarelo aos 70-90% e vermelho acima de 90%', function () {
    $t1 = meiTenant();
    meiTx($t1->id, 'income', 60_000, now()->toDateString()); // ~74%
    expect(app(MeiPanelService::class)->tetoMei($t1->id)['status'])->toBe('amarelo');

    $t2 = meiTenant();
    meiTx($t2->id, 'income', 75_000, now()->toDateString()); // ~92%
    expect(app(MeiPanelService::class)->tetoMei($t2->id)['status'])->toBe('vermelho');
});

// ── proximoDas ─────────────────────────────────────────────────────────────

it('proximoDas devolve dia 20 deste mes se hoje for antes', function () {
    $t = meiTenant();
    $hoje = Carbon::create(2026, 5, 10);
    $r = app(MeiPanelService::class)->proximoDas($t->id, $hoje);

    expect($r['vencimento'])->toBe('2026-05-20');
    expect($r['dias_restantes'])->toBe(10);
    expect($r['pago'])->toBeFalse();
});

it('proximoDas pula pro proximo mes se ja passou o dia 20', function () {
    $t = meiTenant();
    $hoje = Carbon::create(2026, 5, 21);
    $r = app(MeiPanelService::class)->proximoDas($t->id, $hoje);

    expect($r['vencimento'])->toBe('2026-06-20');
});

it('proximoDas detecta DAS ja pago pelo description marker', function () {
    $t = meiTenant();
    meiTx($t->id, 'expense', 81.90, '2026-05-15', 'DAS — MEI');

    $hoje = Carbon::create(2026, 5, 10);
    $r = app(MeiPanelService::class)->proximoDas($t->id, $hoje);

    expect($r['pago'])->toBeTrue();
    expect($r['transaction_id'])->not->toBeNull();
});

it('marcarDasPago cria transaction expense com marker', function () {
    $t = meiTenant();
    $svc = app(MeiPanelService::class);

    $tx = $svc->marcarDasPago($t->id, Carbon::create(2026, 6, 18));

    expect($tx->type)->toBe('expense');
    expect($tx->description)->toBe('DAS — MEI');
    expect((float) $tx->amount)->toBe(81.90);

    $r = $svc->proximoDas($t->id, Carbon::create(2026, 6, 5));
    expect($r['pago'])->toBeTrue();
});

it('marcarDasPago e idempotente — nao duplica no mes', function () {
    $t = meiTenant();
    $svc = app(MeiPanelService::class);

    $svc->marcarDasPago($t->id, Carbon::create(2026, 6, 10));
    $svc->marcarDasPago($t->id, Carbon::create(2026, 6, 18));

    expect(Transaction::where('tenant_id', $t->id)
        ->where('description', 'DAS — MEI')->count())->toBe(1);
});

// ── dreMensal ──────────────────────────────────────────────────────────────

it('dreMensal subtrai despesas e DAS da receita', function () {
    $t = meiTenant();
    $hoje = now();
    meiTx($t->id, 'income',  5000.00, $hoje->toDateString());
    meiTx($t->id, 'income',  1000.00, $hoje->toDateString());
    meiTx($t->id, 'expense', 1200.00, $hoje->toDateString(), 'Aluguel');
    meiTx($t->id, 'expense', 300.00,  $hoje->toDateString(), 'Material');
    meiTx($t->id, 'expense', 81.90,   $hoje->toDateString(), 'DAS — MEI');

    $r = app(MeiPanelService::class)->dreMensal($t->id, $hoje->year, $hoje->month);

    expect($r['receita_centavos'])->toBe(600_000);
    expect($r['despesa_centavos'])->toBe(150_000);
    expect($r['das_centavos'])->toBe(8_190);
    expect($r['lucro_centavos'])->toBe(441_810);
});

it('dreMensal isola mes (transactions de outro mes nao entram)', function () {
    $t = meiTenant();
    $hoje = now();
    meiTx($t->id, 'income',  10_000, $hoje->copy()->subMonth()->toDateString()); // mes anterior
    meiTx($t->id, 'income',  500,    $hoje->toDateString());

    $r = app(MeiPanelService::class)->dreMensal($t->id, $hoje->year, $hoje->month);

    expect($r['receita_centavos'])->toBe(50_000);
});
