<?php

use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;

it('cria item de estoque pela tela do almoxarifado', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);

    $this->withoutExceptionHandling();
    $this->actingAs($user)->post('/ngo/inventory', [
        'name' => 'Kit de material escolar (exemplo)', 'description' => 'Caderno e lápis',
        'sku' => 'KIT-01', 'unit' => 'Unidade', 'minimum_stock' => '10', 'value_per_unit' => '35',
    ])->assertRedirect();

    expect(InventoryItem::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('edita item de estoque', function () {
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $user = User::factory()->forTenant($tenant)->create(['role' => 'ngo']);
    $item = InventoryItem::withoutGlobalScopes()->forceCreate([
        'tenant_id' => $tenant->id, 'name' => 'Cobertor', 'unit' => 'Unidade', 'quantity' => 0, 'minimum_stock' => 1,
    ]);

    $this->withoutExceptionHandling();
    $this->actingAs($user)->put('/ngo/inventory/' . $item->id, [
        'name' => 'Cobertor de casal', 'unit' => 'Unidade', 'minimum_stock' => '5',
    ])->assertRedirect();

    expect($item->fresh()->name)->toBe('Cobertor de casal');
    expect((float) $item->fresh()->minimum_stock)->toBe(5.0);
});
