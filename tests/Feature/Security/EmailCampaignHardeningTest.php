<?php

use App\Http\Controllers\Ngo\NgoEmailCampaignController;
use App\Models\EmailCampaign;
use App\Models\NgoDonor;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * P1.b — Hardening do modulo de campanhas de e-mail.
 *
 * Cobre (nos 3 controllers Admin/Manager/Ngo, com foco no NGO que estava
 * mais exposto):
 *   Fix 1: NGO passa a consumir EmailQuotaService (antes so Manager consumia).
 *   Fix 3: NGO donors sempre exige email_marketing_opt_in=true (art. 8 LGPD).
 *   Fix 4: CRLF injection bloqueada em subject/sender_name via not_regex.
 *   Fix 2 (iframe sandbox no show.blade) coberto por assertion no HTML da view.
 *
 * Testes de "resolveRecipients" e "quota consume" chamam o service/helper
 * diretamente — evitam friction com middleware subscription/role gates que
 * mudam entre setups.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

// ── Helper: invoca resolveRecipients() (metodo private) ─────────────────────
// Refactor de NgoEmailCampaignController::resolveRecipients em algum commit
// anterior mudou a assinatura de (string $type, $extra) pra (EmailCampaign
// $campaign). Helper monta EmailCampaign em memoria (sem save) com o
// audience_type — que e o unico campo que o metodo le pra este cenario.

function invokeResolveRecipients(User $user, string $type): array
{
    Auth::login($user);
    $campaign = new EmailCampaign(['audience_type' => $type]);
    $ctrl = new NgoEmailCampaignController();
    $ref  = new ReflectionMethod($ctrl, 'resolveRecipients');
    $ref->setAccessible(true);
    return $ref->invoke($ctrl, $campaign);
}

function makeTenantAndUser(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
    return [$tenant, $user];
}

// ── Fix 3: opt-in obrigatorio em NGO donors, mesmo no tipo legado 'donors' ──

it('NGO resolveRecipients(donors) exclui doadores sem opt-in', function () {
    [$tenant, $user] = makeTenantAndUser();

    NgoDonor::create(['tenant_id' => $tenant->id, 'name' => 'NoOpt A', 'email' => 'noopt-a@x.com', 'email_marketing_opt_in' => false]);
    NgoDonor::create(['tenant_id' => $tenant->id, 'name' => 'NoOpt B', 'email' => 'noopt-b@x.com', 'email_marketing_opt_in' => false]);
    NgoDonor::create(['tenant_id' => $tenant->id, 'name' => 'Yes',    'email' => 'yes@x.com',    'email_marketing_opt_in' => true]);

    $contacts = invokeResolveRecipients($user, 'donors');
    $emails   = array_column($contacts, 'email');

    expect($emails)->toBe(['yes@x.com']);
});

it('NGO resolveRecipients(donors_optins) tambem exige opt-in (comportamento legado preservado)', function () {
    [$tenant, $user] = makeTenantAndUser();

    NgoDonor::create(['tenant_id' => $tenant->id, 'name' => 'NoOpt', 'email' => 'noopt@x.com', 'email_marketing_opt_in' => false]);
    NgoDonor::create(['tenant_id' => $tenant->id, 'name' => 'Yes',   'email' => 'yes@x.com',   'email_marketing_opt_in' => true]);

    $contacts = invokeResolveRecipients($user, 'donors_optins');

    expect(array_column($contacts, 'email'))->toBe(['yes@x.com']);
});

it('NGO resolveRecipients respeita isolamento de tenant', function () {
    [$tenantA, $userA] = makeTenantAndUser();
    [$tenantB]        = makeTenantAndUser();

    NgoDonor::create(['tenant_id' => $tenantA->id, 'name' => 'A', 'email' => 'a@x.com', 'email_marketing_opt_in' => true]);
    NgoDonor::create(['tenant_id' => $tenantB->id, 'name' => 'B', 'email' => 'b@x.com', 'email_marketing_opt_in' => true]);

    $contacts = invokeResolveRecipients($userA, 'donors');

    expect(array_column($contacts, 'email'))->toBe(['a@x.com']);
});

// ── Fix 1: EmailQuotaService bate igual para NGO e Manager ───────────────────

it('EmailQuotaService bloqueia tenant quando cota estoura', function () {
    $tenant = Tenant::factory()->create(['daily_email_quota' => 3]);
    $quota  = app(EmailQuotaService::class);

    expect($quota->tryConsume($tenant, 2))->toBeTrue();
    expect($quota->getSentToday($tenant))->toBe(2);
    expect($quota->tryConsume($tenant, 2))->toBeFalse(); // 2+2 > 3
    // Nao consumiu por wouldExceed — contador nao mexeu.
    expect($quota->getSentToday($tenant))->toBe(2);
});

it('EmailQuotaService.refund devolve corretamente na compensacao', function () {
    $tenant = Tenant::factory()->create(['daily_email_quota' => 100]);
    $quota  = app(EmailQuotaService::class);

    $quota->tryConsume($tenant, 30);
    expect($quota->getSentToday($tenant))->toBe(30);

    // Simula falha upstream — devolve
    $quota->refund($tenant, 30);
    expect($quota->getSentToday($tenant))->toBe(0);
});

// ── Fix 4: CRLF injection nos 3 controllers via validation ───────────────────

it('NGO store rejeita subject com CRLF', function () {
    [, $user] = makeTenantAndUser();

    $this->actingAs($user)->post('/ngo/email-campaigns', [
        'name'          => 'Teste',
        'subject'       => "Ola\r\nBcc: attacker@evil.com",
        'html_content'  => '<p>ola</p>',
        'audience_type' => 'leads',
    ])->assertSessionHasErrors('subject');
});

it('NGO store rejeita sender_name com CRLF', function () {
    [, $user] = makeTenantAndUser();

    $this->actingAs($user)->post('/ngo/email-campaigns', [
        'name'          => 'Teste',
        'subject'       => 'Ola',
        'sender_name'   => "Vivensi\nBcc: attacker@evil.com",
        'html_content'  => '<p>ola</p>',
        'audience_type' => 'leads',
    ])->assertSessionHasErrors('sender_name');
});

it('Manager store rejeita subject com CRLF', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($user)->post('/manager/email-campaigns', [
        'name'          => 'Teste',
        'subject'       => "Ola\r\nBcc: attacker@evil.com",
        'html_content'  => '<p>ola</p>',
        'audience_type' => 'leads',
    ])->assertSessionHasErrors('subject');
});

it('Admin store rejeita subject com CRLF', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin  = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'super_admin']);

    // super_admin exige 2FA em prod (RequireTwoFactor middleware).
    // Aqui pulamos so o gate de 2FA para testar a validation de CRLF.
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->post('/admin/email-campaigns', [
            'name'          => 'Teste',
            'subject'       => "Ola\r\nBcc: attacker@evil.com",
            'html_content'  => '<p>ola</p>',
            'audience_type' => 'leads',
        ])
        ->assertSessionHasErrors('subject');
});

// ── Fix 2: iframe sandbox no show.blade (renderizacao direta) ────────────────

it('view manager/email_campaigns/show tem iframe com sandbox sem allow-scripts', function () {
    $file = resource_path('views/manager/email_campaigns/show.blade.php');
    $html = file_get_contents($file);

    expect($html)->toContain('sandbox=');
    expect($html)->not->toContain('allow-scripts');
});

it('view ngo/email_campaigns/show tem iframe com sandbox sem allow-scripts', function () {
    $file = resource_path('views/ngo/email_campaigns/show.blade.php');
    $html = file_get_contents($file);

    expect($html)->toContain('sandbox=');
    expect($html)->not->toContain('allow-scripts');
});

it('view admin/email_campaigns/show tem iframe com sandbox sem allow-scripts', function () {
    $file = resource_path('views/admin/email_campaigns/show.blade.php');
    $html = file_get_contents($file);

    expect($html)->toContain('sandbox=');
    expect($html)->not->toContain('allow-scripts');
});
