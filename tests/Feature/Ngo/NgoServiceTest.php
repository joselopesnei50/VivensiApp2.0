<?php

use App\Models\Tenant;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Services\NgoService;

// ── Donor tests ───────────────────────────────────────────────────────────────

it('listDonors returns only records from the given tenant', function () {
    $t1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2 = Tenant::factory()->create(['subscription_status' => 'active']);

    NgoDonor::factory()->count(3)->create(['tenant_id' => $t1->id]);
    NgoDonor::factory()->count(2)->create(['tenant_id' => $t2->id]);

    $service = new NgoService();
    $result  = $service->listDonors($t1->id);

    expect($result->total())->toBe(3);
    expect($result->every(fn($d) => (int) $d->tenant_id === $t1->id))->toBeTrue();
});

it('listDonors filters by name search', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Maria Souza']);
    NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'João Silva']);

    $result = (new NgoService())->listDonors($tenant->id, ['search' => 'Maria']);

    expect($result->total())->toBe(1)
        ->and($result->first()->name)->toBe('Maria Souza');
});

it('listDonors filters by type', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoDonor::factory()->count(2)->create(['tenant_id' => $tenant->id, 'type' => 'individual']);
    NgoDonor::factory()->count(1)->create(['tenant_id' => $tenant->id, 'type' => 'company']);

    $result = (new NgoService())->listDonors($tenant->id, ['type' => 'company']);

    expect($result->total())->toBe(1)
        ->and($result->first()->type)->toBe('company');
});

it('createDonor persists donor for the tenant', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $service = new NgoService();

    $donor = $service->createDonor([
        'name'  => 'Fundação Teste',
        'type'  => 'company',
        'email' => 'fundacao@teste.com',
    ], $tenant->id);

    expect($donor->tenant_id)->toBe($tenant->id)
        ->and($donor->name)->toBe('Fundação Teste');

    $this->assertDatabaseHas('ngo_donors', ['id' => $donor->id, 'tenant_id' => $tenant->id]);
});

it('updateDonor changes the donor fields', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $donor  = NgoDonor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Antes']);

    $updated = (new NgoService())->updateDonor($donor, ['name' => 'Depois']);

    expect($updated->name)->toBe('Depois');
    $this->assertDatabaseHas('ngo_donors', ['id' => $donor->id, 'name' => 'Depois']);
});

it('donorStats returns correct counts per type', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoDonor::factory()->count(3)->create(['tenant_id' => $tenant->id, 'type' => 'individual']);
    NgoDonor::factory()->count(2)->create(['tenant_id' => $tenant->id, 'type' => 'company']);
    NgoDonor::factory()->count(1)->create(['tenant_id' => $tenant->id, 'type' => 'government']);

    $stats = (new NgoService())->donorStats($tenant->id);

    expect($stats['total'])->toBe(6)
        ->and($stats['individual'])->toBe(3)
        ->and($stats['company'])->toBe(2)
        ->and($stats['government'])->toBe(1);
});

it('donorStats does not leak between tenants', function () {
    $t1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2 = Tenant::factory()->create(['subscription_status' => 'active']);

    NgoDonor::factory()->count(5)->create(['tenant_id' => $t1->id]);
    NgoDonor::factory()->count(2)->create(['tenant_id' => $t2->id]);

    $stats = (new NgoService())->donorStats($t1->id);

    expect($stats['total'])->toBe(5);
});

// ── Grant tests ───────────────────────────────────────────────────────────────

it('listGrants returns only records from the given tenant', function () {
    $t1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $t2 = Tenant::factory()->create(['subscription_status' => 'active']);

    NgoGrant::factory()->count(4)->create(['tenant_id' => $t1->id]);
    NgoGrant::factory()->count(2)->create(['tenant_id' => $t2->id]);

    $result = (new NgoService())->listGrants($t1->id);

    expect($result->total())->toBe(4);
});

it('listGrants filters by status', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoGrant::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => 'open']);
    NgoGrant::factory()->count(1)->create(['tenant_id' => $tenant->id, 'status' => 'closed']);

    $result = (new NgoService())->listGrants($tenant->id, ['status' => 'open']);

    expect($result->total())->toBe(2);
});

it('grantStats returns correct approved value sum', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    NgoGrant::factory()->create(['tenant_id' => $tenant->id, 'status' => 'approved', 'value' => 10000]);
    NgoGrant::factory()->create(['tenant_id' => $tenant->id, 'status' => 'approved', 'value' => 5000]);
    NgoGrant::factory()->create(['tenant_id' => $tenant->id, 'status' => 'open',     'value' => 8000]);

    $stats = (new NgoService())->grantStats($tenant->id);

    expect($stats['total'])->toBe(3)
        ->and($stats['approved'])->toBe(2)
        ->and((float) $stats['total_value'])->toBe(15000.0);
});
