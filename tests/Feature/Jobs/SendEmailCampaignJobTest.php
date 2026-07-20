<?php

use App\Jobs\SendEmailCampaignJob;
use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

/**
 * Testes para SendEmailCampaignJob.
 *
 * O job encapsula as 4 etapas Brevo (createList / importContacts /
 * createCampaign / send) que antes rodavam sincronamente no controller e
 * causavam 504 em campanhas grandes. Aqui verificamos:
 *   - fluxo de sucesso: campanha vai pra 'sent'
 *   - cada etapa de erro: campanha vai pra 'error', quota devolvida
 *   - admin (quotaTenantId=null): sem refund ao errar
 *   - status diferente de 'sending': job aborta sem Brevo
 *   - campanha inexistente: job aborta + refund
 *   - failed(): marca 'error' + devolve quota
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeCampaignWithTenant(?int $tenantId = null): array
{
    $tenant = Tenant::factory()->create([
        'subscription_status' => 'active',
        'daily_email_quota'   => 200,
    ]);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $campaign = EmailCampaign::withoutGlobalScopes()->create([
        'tenant_id'    => $tenant->id,
        'created_by'   => $user->id,
        'name'         => 'Teste',
        'subject'      => 'Assunto',
        'html_content' => '<p>Ola</p>',
        'audience_type'=> 'leads',
        'status'       => 'sending',
    ]);

    return [$tenant, $campaign];
}

$twoContacts = [
    ['email' => 'a@x.com', 'name' => 'Alice'],
    ['email' => 'b@x.com', 'name' => 'Bob'],
];

// ── Fluxo de sucesso ──────────────────────────────────────────────────────────

it('fluxo completo: campanha fica sent com brevo_campaign_id e recipient_count', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andReturn(99);
    $brevo->shouldReceive('importContacts')->once()->with(99, $twoContacts)->andReturn(2);
    $brevo->shouldReceive('createBrevoEmailCampaign')->once()->andReturn(555);
    $brevo->shouldReceive('sendBrevoEmailCampaign')->once()->with(555)->andReturn(true);
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2); // simula consume feito no controller

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('sent');
    expect((int) $campaign->brevo_campaign_id)->toBe(555);
    expect((int) $campaign->recipient_count)->toBe(2);
    expect($campaign->sent_at)->not->toBeNull();
    expect($campaign->error_message)->toBeNull();
    // Quota NAO e devolvida em sucesso
    expect($quota->getSentToday($tenant))->toBe(2);
});

// ── Campanha nao encontrada ───────────────────────────────────────────────────

it('campanha inexistente: job aborta e refunda quota', function () use ($twoContacts) {
    [$tenant] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldNotReceive('createContactList');
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob(99999, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    expect($quota->getSentToday($tenant))->toBe(0); // refundado
});

// ── Campanha ja saiu de 'sending' ────────────────────────────────────────────

it('campanha que nao esta mais em sending: aborta sem chamar Brevo', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();
    $campaign->update(['status' => 'sent']);

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldNotReceive('createContactList');
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    expect($quota->getSentToday($tenant))->toBe(0); // refundado
    $campaign->refresh();
    expect($campaign->status)->toBe('sent'); // nao foi mexido
});

// ── Falha em createContactList ────────────────────────────────────────────────

it('createContactList retorna null: status=error, quota refundada', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andReturn(null);
    $brevo->shouldNotReceive('importContacts');
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect($campaign->error_message)->toContain('lista');
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Falha em importContacts ───────────────────────────────────────────────────

it('importContacts retorna 0: status=error, quota refundada', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andReturn(42);
    $brevo->shouldReceive('importContacts')->once()->andReturn(0);
    $brevo->shouldNotReceive('createBrevoEmailCampaign');
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect((int) $campaign->brevo_list_id)->toBe(42);
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Falha em createBrevoEmailCampaign ────────────────────────────────────────

it('createBrevoEmailCampaign retorna null: status=error, quota refundada', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->lastBrevoError = 'API indisponivel';
    $brevo->shouldReceive('createContactList')->once()->andReturn(42);
    $brevo->shouldReceive('importContacts')->once()->andReturn(2);
    $brevo->shouldReceive('createBrevoEmailCampaign')->once()->andReturn(null);
    $brevo->shouldNotReceive('sendBrevoEmailCampaign');
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect($campaign->error_message)->toContain('API indisponivel');
    expect((int) $campaign->brevo_list_id)->toBe(42);
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Falha em sendBrevoEmailCampaign ──────────────────────────────────────────

it('sendBrevoEmailCampaign retorna false: status=error, quota refundada, brevo_campaign_id salvo', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andReturn(42);
    $brevo->shouldReceive('importContacts')->once()->andReturn(2);
    $brevo->shouldReceive('createBrevoEmailCampaign')->once()->andReturn(777);
    $brevo->shouldReceive('sendBrevoEmailCampaign')->once()->with(777)->andReturn(false);
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect((int) $campaign->brevo_campaign_id)->toBe(777); // campanha criada, mas nao disparada
    expect($campaign->sent_at)->toBeNull();
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Excecao inesperada ────────────────────────────────────────────────────────

it('excecao inesperada: status=error com mensagem, quota refundada', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andThrow(new \RuntimeException('timeout na API'));
    $this->app->instance(BrevoService::class, $brevo);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect($campaign->error_message)->toContain('timeout na API');
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Admin (quotaTenantId=null): sem refund ao errar ──────────────────────────

it('admin dispatch (quotaTenantId=null): erro nao causa refund', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $brevo = Mockery::mock(BrevoService::class);
    $brevo->shouldReceive('createContactList')->once()->andReturn(null);
    $this->app->instance(BrevoService::class, $brevo);

    // Admin nao consome quota antes do dispatch — simulamos sem consumir
    $quota = app(EmailQuotaService::class);

    // job admin: quotaTenantId ausente/null
    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, null);
    $job->handle($brevo, $quota);

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    // Sem crash pq nao tenta refund de tenant null
    expect($quota->getSentToday($tenant))->toBe(0); // nunca foi consumido
});

// ── failed() hook ─────────────────────────────────────────────────────────────

it('failed() marca campanha em sending como error e devolve quota', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 2);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->failed(new \RuntimeException('worker morreu'));

    $campaign->refresh();
    expect($campaign->status)->toBe('error');
    expect($campaign->error_message)->toContain('worker morreu');
    expect($quota->getSentToday($tenant))->toBe(0);
});

it('failed() nao toca campanha que ja saiu de sending (ex: error de tentativa anterior)', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();
    $campaign->update(['status' => 'error', 'error_message' => 'erro anterior']);

    $job = new SendEmailCampaignJob($campaign->id, $twoContacts, $tenant->id);
    $job->failed(new \RuntimeException('segunda falha'));

    $campaign->refresh();
    // O where('status','sending') nao match — mensagem original preservada
    expect($campaign->error_message)->toBe('erro anterior');
});

// ── Dispatch na queue correta ─────────────────────────────────────────────────

it('dispatch enfileira na queue emails', function () use ($twoContacts) {
    [$tenant, $campaign] = makeCampaignWithTenant();

    Queue::fake();

    SendEmailCampaignJob::dispatch($campaign->id, $twoContacts, $tenant->id)->onQueue('emails');

    Queue::assertPushedOn('emails', SendEmailCampaignJob::class);
});
