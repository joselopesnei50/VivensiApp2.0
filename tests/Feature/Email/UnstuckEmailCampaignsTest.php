<?php

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Testa o unstuck de EmailCampaign travadas em status='sending'. Cenarios:
 *  1. dry-run nao altera nada
 *  2. Brevo tem a campanha (stats retornam algo) -> marca 'sent'
 *  3. Brevo NAO tem (404) -> marca 'error' + refunda cota
 *  4. Sem brevo_campaign_id (job morreu cedo) -> marca 'error' + refunda cota
 *  5. Campanhas recentes (<min-age) nao sao tocadas
 */

function ucMakeCampaign(Tenant $tenant, array $overrides = []): EmailCampaign
{
    $user = User::factory()->forTenant($tenant)->create();
    return EmailCampaign::create(array_merge([
        'tenant_id'       => $tenant->id,
        'created_by'      => $user->id,
        'name'            => 'Newsletter',
        'subject'         => 'x',
        'html_content'    => '<p>x</p>',
        'audience_type'   => 'manual',
        'status'          => 'sending',
        'recipient_count' => 1500,
    ], $overrides));
}

/**
 * Backdate updated_at pra simular campanha travada faz X min.
 */
function ucBackdate(EmailCampaign $c, int $minutes): void
{
    EmailCampaign::withoutGlobalScopes()
        ->where('id', $c->id)
        ->update(['updated_at' => now()->subMinutes($minutes)]);
    $c->refresh();
}

it('dry-run nao altera nada', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => 999]);
    ucBackdate($c, 120);

    Http::fake([
        '*/emailCampaigns/*' => Http::response(['statistics' => ['globalStats' => ['delivered' => 1500]]], 200),
    ]);

    $this->artisan('emails:unstuck-campaigns --dry-run')
         ->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('sending');
});

it('campanha com brevo_campaign_id valido vira sent', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => 999]);
    ucBackdate($c, 120);

    Http::fake([
        '*/emailCampaigns/999' => Http::response([
            'statistics' => ['globalStats' => ['delivered' => 1500, 'uniqueViews' => 42]],
        ], 200),
    ]);

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('sent');
    expect($c->sent_at)->not->toBeNull();
    expect($c->error_message)->toBeNull();
});

it('campanha com brevo_campaign_id inexistente vira error + refunda cota', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => 404]);
    ucBackdate($c, 120);

    Http::fake([
        '*/emailCampaigns/404' => Http::response(['message' => 'not found'], 404),
    ]);

    $quota = app(EmailQuotaService::class);
    // Simula cota ja consumida no send() antes do worker morrer
    $quota->tryConsume($tenant, 1500);
    $consumedBefore = 1500;

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('error');
    expect($c->error_message)->toContain('Brevo');

    // Cota devolvida
    $sentToday = $quota->getSentToday($tenant);
    expect($sentToday)->toBe($consumedBefore - 1500);
});

it('campanha sem brevo_campaign_id vira error + refunda cota', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => null]);
    ucBackdate($c, 120);

    $quota = app(EmailQuotaService::class);
    $quota->tryConsume($tenant, 1500);

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('error');
    expect($c->error_message)->toContain('antes de criar');
    expect($quota->getSentToday($tenant))->toBe(0);
});

it('campanha em sending recente (< min-age) nao eh tocada', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => null]);
    ucBackdate($c, 30); // 30 min < default 60

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('sending');
});

it('campanha ja resolvida (status != sending) nao eh tocada', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['status' => 'sent', 'sent_at' => now()->subDay()]);
    ucBackdate($c, 120);

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('sent');
});

it('option --min-age customizado captura campanhas mais antigas', function () {
    $tenant = Tenant::factory()->create();
    $c = ucMakeCampaign($tenant, ['brevo_campaign_id' => null]);
    ucBackdate($c, 20); // 20 min

    // min-age=15 deve pegar
    $this->artisan('emails:unstuck-campaigns --min-age=15')->assertSuccessful();

    $c->refresh();
    expect($c->status)->toBe('error');
});
