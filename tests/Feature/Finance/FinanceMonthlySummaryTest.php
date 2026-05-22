<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Transaction;
use App\Services\FinanceService;
use Illuminate\Support\Facades\Cache;

it('monthlySummary returns correct income and expense', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    Transaction::factory()->create([
        'tenant_id'   => $tenant->id,
        'type'        => 'income',
        'status'      => 'paid',
        'amount'      => 1000.00,
        'date'        => now()->toDateString(),
        'description' => 'Receita teste',
    ]);
    Transaction::factory()->create([
        'tenant_id'   => $tenant->id,
        'type'        => 'expense',
        'status'      => 'paid',
        'amount'      => 300.00,
        'date'        => now()->toDateString(),
        'description' => 'Despesa teste',
    ]);

    $summary = (new FinanceService())->monthlySummary($tenant->id);

    expect($summary['income'])->toBe(1000.0)
        ->and($summary['expense'])->toBe(300.0)
        ->and($summary['balance'])->toBe(700.0);
});

it('monthlySummary excludes unpaid transactions', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    Transaction::factory()->create([
        'tenant_id'   => $tenant->id,
        'type'        => 'income',
        'status'      => 'pending',
        'amount'      => 9999.00,
        'date'        => now()->toDateString(),
        'description' => 'Não deve entrar',
    ]);

    $summary = (new FinanceService())->monthlySummary($tenant->id);

    expect($summary['income'])->toBe(0.0);
});

it('monthlySummary excludes previous months', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    Transaction::factory()->create([
        'tenant_id'   => $tenant->id,
        'type'        => 'income',
        'status'      => 'paid',
        'amount'      => 5000.00,
        'date'        => now()->subMonths(2)->toDateString(),
        'description' => 'Mês anterior',
    ]);

    $summary = (new FinanceService())->monthlySummary($tenant->id);

    expect($summary['income'])->toBe(0.0);
});

it('monthlySummary counts pending approvals', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    Transaction::factory()->count(3)->create([
        'tenant_id'       => $tenant->id,
        'type'            => 'expense',
        'status'          => 'pending',
        'approval_status' => 'pending',
        'amount'          => 100.00,
        'date'            => now()->toDateString(),
        'description'     => 'Pendente',
    ]);

    $summary = (new FinanceService())->monthlySummary($tenant->id);

    expect($summary['pending_count'])->toBe(3);
});

it('monthlySummary result is cached', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    $service = new FinanceService();
    $key     = "finance.monthly.{$tenant->id}." . now()->format('Y-m');

    Cache::forget($key);
    expect(Cache::has($key))->toBeFalse();

    $service->monthlySummary($tenant->id);

    expect(Cache::has($key))->toBeTrue();
});

it('monthlySummary does not leak between tenants', function () {
    $t1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2 = Tenant::factory()->create(['subscription_status' => 'active']);

    Transaction::factory()->create([
        'tenant_id' => $t1->id, 'type' => 'income',
        'status' => 'paid', 'amount' => 2000.00,
        'date' => now()->toDateString(), 'description' => 'T1 receita',
    ]);

    $t1Summary = (new FinanceService())->monthlySummary($t1->id);
    $t2Summary = (new FinanceService())->monthlySummary($t2->id);

    expect($t1Summary['income'])->toBe(2000.0)
        ->and($t2Summary['income'])->toBe(0.0);
});
