<?php

use App\Models\Asset;
use App\Models\Tenant;
use App\Models\User;

it('bem excluído não soma no valor total nem na contagem do patrimônio', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);
    $base = ['tenant_id' => $tenant->id, 'acquisition_date' => '2026-03-10', 'status' => 'active'];
    Asset::withoutGlobalScopes()->forceCreate($base + ['name' => 'Notebook', 'value' => 3800]);
    $excluido = Asset::withoutGlobalScopes()->forceCreate($base + ['name' => 'Impressora', 'value' => 900]);
    $excluido->delete();

    $stats = $this->actingAs($user)->get('/ngo/assets')->assertOk()->viewData('stats');

    expect($stats['total_count'])->toBe(1);
    expect($stats['active_count'])->toBe(1);
    expect($stats['total_value'])->toEqual(3800.0);
});
