<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\AuditLog;
use App\Models\Project;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creating a project generates an audit log', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user);

    Project::factory()->create([
        'tenant_id' => $tenant->id,
        'name'      => 'Projeto Teste Auditoria',
    ]);

    $log = AuditLog::where('event', 'created')
        ->where('auditable_type', Project::class)
        ->where('tenant_id', $tenant->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id);
});

it('audit log records ip address', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user);

    Project::factory()->create(['tenant_id' => $tenant->id]);

    $log = AuditLog::where('event', 'created')
        ->where('auditable_type', Project::class)
        ->where('tenant_id', $tenant->id)
        ->latest()
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeNull();
});

it('audit log does not leak between tenants', function () {
    $tenant1 = Tenant::factory()->create(['subscription_status' => 'active']);
    $tenant2 = Tenant::factory()->create(['subscription_status' => 'active']);

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id, 'role' => 'manager']);
    $user2 = User::factory()->create(['tenant_id' => $tenant2->id, 'role' => 'manager']);

    $this->actingAs($user1);
    Project::factory()->create(['tenant_id' => $tenant1->id]);

    // Log do tenant2 não deve aparecer para o user1
    $this->actingAs($user2);
    $logs = AuditLog::where('tenant_id', $tenant1->id)->get();

    expect($logs)->toHaveCount(0);
});
