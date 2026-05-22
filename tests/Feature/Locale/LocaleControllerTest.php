<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Session;

// ── POST /locale/{code} ───────────────────────────────────────────────────────

it('locale switcher requires authentication', function () {
    $response = $this->post('/locale/en');
    $response->assertRedirect('/login');
});

it('locale switcher sets session locale', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $response = $this->post('/locale/en');
    $response->assertRedirect();

    expect(session('locale'))->toBe('en');
});

it('locale switcher persists pt_BR locale', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $this->post('/locale/pt_BR');

    expect(session('locale'))->toBe('pt_BR');
});

it('locale switcher persists es locale', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $this->post('/locale/es');

    expect(session('locale'))->toBe('es');
});

it('locale switcher rejects unsupported locales', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $response = $this->post('/locale/zh');
    // Should either redirect back or return validation error — locale unchanged
    expect(session('locale'))->not->toBe('zh');
});

it('locale switcher updates user model if locale column exists', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user);

    $this->post('/locale/en');

    $user->refresh();
    // If the column exists on the table it should be persisted
    if (in_array('locale', \Illuminate\Support\Facades\Schema::getColumnListing('users'))) {
        expect($user->locale)->toBe('en');
    } else {
        // Column not migrated yet — just verify session was set
        expect(session('locale'))->toBe('en');
    }
});
