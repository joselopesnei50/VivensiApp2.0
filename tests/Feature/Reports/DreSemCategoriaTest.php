<?php

use App\Models\FinancialCategory;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;

it('DRE soma lançamentos sem categoria e mostra o nome da organização', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active', 'name' => 'Instituto Exemplo', 'brand_name' => null]);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);
    $cat = FinancialCategory::withoutGlobalScopes()->forceCreate(['tenant_id' => $tenant->id, 'name' => 'Doações', 'type' => 'income']);
    $base = ['tenant_id' => $tenant->id, 'status' => 'paid', 'date' => now()->year . '-05-10', 'description' => 'x'];
    Transaction::withoutGlobalScopes()->forceCreate($base + ['type' => 'income', 'amount' => 100, 'category_id' => $cat->id]);
    Transaction::withoutGlobalScopes()->forceCreate($base + ['type' => 'income', 'amount' => 50, 'category_id' => null]);
    Transaction::withoutGlobalScopes()->forceCreate($base + ['type' => 'expense', 'amount' => 30, 'category_id' => null]);

    $resp = $this->actingAs($user)->get('/ngo/reports/dre');

    $resp->assertOk()->assertSee('Sem categoria')->assertSee('Instituto Exemplo');
    expect($resp->viewData('totalIncome'))->toEqual(150.0);
    expect($resp->viewData('totalExpense'))->toEqual(30.0);
    expect($resp->viewData('result'))->toEqual(120.0);
});

it('nome nos documentos: marca, senão o nome da organização', function () {
    expect((new Tenant(['name' => 'Coletivo A', 'brand_name' => 'Marca A']))->documentName())->toBe('Marca A');
    expect((new Tenant(['name' => 'Coletivo A', 'brand_name' => null]))->documentName())->toBe('Coletivo A');
    expect((new Tenant(['name' => '', 'brand_name' => null]))->documentName())->toBe('ORGANIZAÇÃO SOCIAL');
});

it('PDF da DRE usa o mesmo cálculo da tela (com sem categoria)', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $base = ['tenant_id' => $tenant->id, 'status' => 'paid', 'date' => '2026-05-10', 'description' => 'x', 'category_id' => null];
    Transaction::withoutGlobalScopes()->forceCreate($base + ['type' => 'income', 'amount' => 115]);

    [$inc, $totInc, $exp, $totExp] = app(\App\Services\Reports\DreCalculator::class)->build($tenant->id, '2026-01-01', '2026-12-31');
    expect($totInc)->toEqual(115.0);
    expect($inc[0]['name'])->toBe('Sem categoria');

    $job = file_get_contents(app_path('Jobs/GeneratePdfJob.php'));
    expect($job)->toContain('DreCalculator::class');
});
