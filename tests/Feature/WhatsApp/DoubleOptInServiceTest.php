<?php

use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadDoubleOptInToken;
use App\Models\Tenant;
use App\Services\Messaging\DoubleOptInService;
use Illuminate\Support\Facades\Queue;

/**
 * P0.2 — Double opt-in via WhatsApp.
 * Cobre requestFor() (idempotência, gera token, dispara Job) e
 * processInbound() (matching de confirm/opt-out keywords).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function doiTenant(): Tenant
{
    return Tenant::factory()->create();
}

function doiLead(Tenant $tenant, string $phoneNormalized = '5511987654321'): Lead
{
    return Lead::create([
        'tenant_id'        => $tenant->id,
        'name'             => 'Fulano',
        'phone'            => '+55 11 98765-4321',
        'phone_normalized' => $phoneNormalized,
        'status'           => Lead::STATUS_PENDING,
    ]);
}

it('requestFor cria token e enfileira o Job', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);

    $service = app(DoubleOptInService::class);
    $token = $service->requestFor($lead);

    expect($token)->not->toBeNull();
    expect($token->tenant_id)->toBe($tenant->id);
    expect($token->lead_id)->toBe($lead->id);
    expect($token->expires_at->isFuture())->toBeTrue();
    expect($token->confirmed_at)->toBeNull();
    expect($token->opted_out_at)->toBeNull();

    Queue::assertPushed(\App\Jobs\SendDoubleOptInWhatsapp::class);
});

it('requestFor e idempotente — nao gera dois tokens ativos', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);

    $service = app(DoubleOptInService::class);
    $first  = $service->requestFor($lead);
    $second = $service->requestFor($lead);

    expect($first->id)->toBe($second->id);
    expect(LeadDoubleOptInToken::where('lead_id', $lead->id)->count())->toBe(1);
});

it('requestFor pula leads sem phone_normalized', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = Lead::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Sem Telefone',
        'email'     => 'x@gmail.com',
        'status'    => Lead::STATUS_PENDING,
    ]);

    $token = app(DoubleOptInService::class)->requestFor($lead);

    expect($token)->toBeNull();
    Queue::assertNothingPushed();
});

it('requestFor pula leads ja confirmados', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);
    $lead->update(['status' => Lead::STATUS_CONFIRMED, 'double_opt_in_at' => now()]);

    $token = app(DoubleOptInService::class)->requestFor($lead);

    expect($token)->toBeNull();
});

it('processInbound com SIM confirma o lead', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);

    $service = app(DoubleOptInService::class);
    $token = $service->requestFor($lead);

    $closed = $service->processInbound($lead, 'sim');

    expect($closed)->toBeTrue();
    expect($lead->fresh()->status)->toBe(Lead::STATUS_CONFIRMED);
    expect($lead->fresh()->double_opt_in_at)->not->toBeNull();
    expect($token->fresh()->confirmed_at)->not->toBeNull();

    $consent = LeadConsent::where('lead_id', $lead->id)
        ->where('type', LeadConsent::TYPE_DOUBLE_OPT_IN)
        ->first();
    expect($consent)->not->toBeNull();
});

it('processInbound com NAO marca opt-out', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);

    $service = app(DoubleOptInService::class);
    $token = $service->requestFor($lead);

    $closed = $service->processInbound($lead, 'não');

    expect($closed)->toBeTrue();
    expect($lead->fresh()->status)->toBe(Lead::STATUS_UNSUBSCRIBED);
    expect($token->fresh()->opted_out_at)->not->toBeNull();
});

it('processInbound ignora resposta nao relacionada', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);
    app(DoubleOptInService::class)->requestFor($lead);

    $closed = app(DoubleOptInService::class)->processInbound($lead, 'oi tudo bem?');

    expect($closed)->toBeFalse();
    expect($lead->fresh()->status)->toBe(Lead::STATUS_PENDING);
});

it('processInbound sem token ativo nao faz nada', function () {
    $tenant = doiTenant();
    $lead = doiLead($tenant);

    $closed = app(DoubleOptInService::class)->processInbound($lead, 'sim');

    expect($closed)->toBeFalse();
});

it('processInbound aceita acentos e maiusculas no SIM', function () {
    Queue::fake();
    $tenant = doiTenant();
    $lead = doiLead($tenant);
    app(DoubleOptInService::class)->requestFor($lead);

    $closed = app(DoubleOptInService::class)->processInbound($lead, '  SIM!  ');

    expect($closed)->toBeTrue();
    expect($lead->fresh()->status)->toBe(Lead::STATUS_CONFIRMED);
});
