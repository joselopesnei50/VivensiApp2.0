<?php

use App\Jobs\DispatchWebhook;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Queue;

function webhookTenant(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    return [$tenant, $user];
}

// ── WebhookService::fire() ────────────────────────────────────────────────────

it('fire dispatches job for matching active webhook', function () {
    Queue::fake();
    [$tenant] = webhookTenant();

    Webhook::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Test Hook',
        'url'       => 'https://example.com/hook',
        'secret'    => 'secret123',
        'events'    => ['transaction.created'],
        'active'    => true,
    ]);

    app(WebhookService::class)->fire($tenant->id, 'transaction.created', ['amount' => 100]);

    Queue::assertPushed(DispatchWebhook::class);
});

it('fire does not dispatch for inactive webhook', function () {
    Queue::fake();
    [$tenant] = webhookTenant();

    Webhook::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Inactive',
        'url'       => 'https://example.com/hook',
        'secret'    => 'secret',
        'events'    => ['transaction.created'],
        'active'    => false,
    ]);

    app(WebhookService::class)->fire($tenant->id, 'transaction.created', []);

    Queue::assertNotPushed(DispatchWebhook::class);
});

it('fire does not dispatch when event not subscribed', function () {
    Queue::fake();
    [$tenant] = webhookTenant();

    Webhook::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Task Only',
        'url'       => 'https://example.com/hook',
        'secret'    => 'secret',
        'events'    => ['task.created'],
        'active'    => true,
    ]);

    app(WebhookService::class)->fire($tenant->id, 'transaction.created', []);

    Queue::assertNotPushed(DispatchWebhook::class);
});

it('wildcard webhook receives all events', function () {
    Queue::fake();
    [$tenant] = webhookTenant();

    Webhook::create([
        'tenant_id' => $tenant->id,
        'name'      => 'All Events',
        'url'       => 'https://example.com/hook',
        'secret'    => 'secret',
        'events'    => ['*'],
        'active'    => true,
    ]);

    app(WebhookService::class)->fire($tenant->id, 'task.completed', []);

    Queue::assertPushed(DispatchWebhook::class);
});

it('fire does not dispatch to other tenant webhooks', function () {
    Queue::fake();
    [$tenantA] = webhookTenant();
    [$tenantB] = webhookTenant();

    Webhook::create([
        'tenant_id' => $tenantB->id,
        'name'      => 'Other Tenant',
        'url'       => 'https://example.com/hook',
        'secret'    => 'secret',
        'events'    => ['transaction.created'],
        'active'    => true,
    ]);

    app(WebhookService::class)->fire($tenantA->id, 'transaction.created', []);

    Queue::assertNotPushed(DispatchWebhook::class);
});

// ── Webhook model ─────────────────────────────────────────────────────────────

it('webhook subscribesTo returns true for matching event', function () {
    $wh = new Webhook(['events' => ['task.created', 'transaction.created']]);
    expect($wh->subscribesTo('task.created'))->toBeTrue();
    expect($wh->subscribesTo('project.created'))->toBeFalse();
});

it('webhook subscribesTo wildcard matches any event', function () {
    $wh = new Webhook(['events' => ['*']]);
    expect($wh->subscribesTo('anything.here'))->toBeTrue();
});

// ── Settings routes ───────────────────────────────────────────────────────────

it('webhooks settings page requires authentication', function () {
    $this->get('/settings/webhooks')->assertRedirect('/login');
});

it('webhooks settings page loads for authenticated manager', function () {
    [$tenant, $user] = webhookTenant();
    $this->actingAs($user)->get('/settings/webhooks')->assertOk()->assertSee('Webhooks');
});

it('can create webhook via web form', function () {
    [$tenant, $user] = webhookTenant();
    $this->actingAs($user)->post('/settings/webhooks', [
        'name'   => 'Test Webhook',
        'url'    => 'https://example.com/hook',
        'events' => ['transaction.created'],
    ])->assertRedirect();

    $this->assertDatabaseHas('webhooks', [
        'tenant_id' => $tenant->id,
        'name'      => 'Test Webhook',
    ]);
});

it('can delete webhook', function () {
    [$tenant, $user] = webhookTenant();

    $wh = Webhook::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Del Me',
        'url'       => 'https://example.com',
        'secret'    => 'x',
        'events'    => ['*'],
        'active'    => true,
    ]);

    $this->actingAs($user)->delete("/settings/webhooks/{$wh->id}")->assertRedirect();
    $this->assertDatabaseMissing('webhooks', ['id' => $wh->id]);
});
