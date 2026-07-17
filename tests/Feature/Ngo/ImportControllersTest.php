<?php

use App\Models\Asset;
use App\Models\InventoryItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function importNgoUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
}

function fakeCsv(string $content): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'csvtest');
    file_put_contents($tmp, $content);
    return new UploadedFile($tmp, 'planilha.csv', 'text/csv', null, true);
}

// ── Assets (Patrimonio) ───────────────────────────────────────────────────────

it('importa CSV de patrimonio cria assets no banco', function () {
    $user = importNgoUser();
    $csv  = "nome,codigo,data_aquisicao,valor,status\n"
          . "Notebook Dell,PAT-001,15/01/2026,4500.00,ativo\n"
          . "Cadeira,PAT-002,20/02/2026,\"R\$ 899,90\",ativo\n";

    $this->actingAs($user)->post('/ngo/assets/import/preview', ['file' => fakeCsv($csv)]);
    $this->actingAs($user)->post('/ngo/assets/import/confirm')->assertRedirect();

    expect(Asset::withoutGlobalScope('tenant')->count())->toBe(2);
    $a = Asset::withoutGlobalScope('tenant')->where('code', 'PAT-001')->first();
    expect($a->name)->toBe('Notebook Dell');
    expect((float) $a->value)->toBe(4500.00);
    expect($a->status)->toBe('active');
    expect((int) $a->tenant_id)->toBe($user->tenant_id);
});

it('dedup de asset por code ignora existente', function () {
    $user = importNgoUser();
    Asset::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Existente', 'code' => 'PAT-X', 'acquisition_date' => '2026-01-01',
        'value' => 100, 'status' => 'active',
    ]);

    $csv = "nome,codigo,data_aquisicao,valor\nNovo,PAT-X,01/03/2026,200\n";
    $this->actingAs($user)->post('/ngo/assets/import/preview', ['file' => fakeCsv($csv)]);
    $this->actingAs($user)->post('/ngo/assets/import/confirm');

    expect(Asset::withoutGlobalScope('tenant')->count())->toBe(1);
});

// ── Inventory (Almox/Estoque) ─────────────────────────────────────────────────

it('importa CSV de estoque cria inventory_items no banco', function () {
    $user = importNgoUser();
    $csv  = "nome,sku,unidade,quantidade,estoque_minimo,valor_unitario\n"
          . "Papel A4,SKU-A4,resma,50,10,25.90\n"
          . "Cesta,SKU-CB,unidade,120,20,89.90\n";

    $this->actingAs($user)->post('/ngo/inventory/import/preview', ['file' => fakeCsv($csv)]);
    $this->actingAs($user)->post('/ngo/inventory/import/confirm')->assertRedirect();

    expect(InventoryItem::withoutGlobalScope('tenant')->count())->toBe(2);
    $item = InventoryItem::withoutGlobalScope('tenant')->where('sku', 'SKU-A4')->first();
    expect($item->name)->toBe('Papel A4');
    expect((float) $item->quantity)->toBe(50.0);
    expect($item->unit)->toBe('resma');
    expect((int) $item->tenant_id)->toBe($user->tenant_id);
});

it('unidade default vira un quando ausente no CSV', function () {
    $user = importNgoUser();
    $csv  = "nome,quantidade\nCanetas,25\n";
    $this->actingAs($user)->post('/ngo/inventory/import/preview', ['file' => fakeCsv($csv)]);
    $this->actingAs($user)->post('/ngo/inventory/import/confirm');

    $item = InventoryItem::withoutGlobalScope('tenant')->where('name', 'Canetas')->first();
    expect($item)->not->toBeNull();
    expect($item->unit)->toBe('un');
});

it('dedup de estoque por sku ignora existente', function () {
    $user = importNgoUser();
    InventoryItem::create([
        'tenant_id' => $user->tenant_id, 'name' => 'Papel', 'sku' => 'SKU-A4',
        'unit' => 'resma', 'quantity' => 5, 'minimum_stock' => 1,
    ]);

    $csv = "nome,sku,quantidade\nNovo Papel,SKU-A4,999\n";
    $this->actingAs($user)->post('/ngo/inventory/import/preview', ['file' => fakeCsv($csv)]);
    $this->actingAs($user)->post('/ngo/inventory/import/confirm');

    expect(InventoryItem::withoutGlobalScope('tenant')->count())->toBe(1);
});

it('CSV de patrimonio sem colunas obrigatorias e rejeitado', function () {
    $user = importNgoUser();
    $csv = "coluna_qualquer\nvalor\n";
    $this->actingAs($user)->post('/ngo/assets/import/preview', ['file' => fakeCsv($csv)])->assertRedirect();
    expect(Asset::withoutGlobalScope('tenant')->count())->toBe(0);
});
