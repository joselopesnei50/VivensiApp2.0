<?php

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Models\User;

/**
 * Editar (rascunhos) e Duplicar campanhas no super admin.
 */

function adminCtx(): array
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin  = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'super_admin']);
    return [$tenant, $admin];
}

function draftCampaign(Tenant $tenant, User $admin, array $attrs = []): EmailCampaign
{
    return EmailCampaign::withoutGlobalScopes()->create(array_merge([
        'tenant_id'     => $tenant->id,
        'created_by'    => $admin->id,
        'name'          => 'Rascunho X',
        'subject'       => 'Assunto antigo',
        'html_content'  => '<p>Texto antigo</p>',
        'audience_type' => 'manual',
        'manual_emails' => json_encode([['email' => 'ana@x.org', 'name' => 'Ana'], ['email' => 'b@x.org', 'name' => 'b@x.org']]),
        'delivery_driver' => 'own',
        'status'        => 'draft',
    ], $attrs));
}

function asAdmin($test, User $admin)
{
    return $test->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)->actingAs($admin);
}

it('editar abre o formulário preenchido, inclusive os e-mails manuais', function () {
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin);

    asAdmin($this, $admin)->get(route('admin.email_campaigns.edit', $c))
        ->assertOk()
        ->assertSee('Editar Campanha')
        ->assertSee('value="Assunto antigo"', false)
        ->assertSee('Texto antigo')
        ->assertSee('Ana &lt;ana@x.org&gt;', false)
        ->assertSee('Salvar alterações');
});

it('salvar a edição atualiza assunto, conteúdo e público', function () {
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin);

    asAdmin($this, $admin)->put(route('admin.email_campaigns.update', $c), [
        'name'              => 'Rascunho X',
        'subject'           => 'Assunto novo',
        'html_content'      => '<p>Texto novo</p>',
        'audience_type'     => 'manual',
        'manual_emails_raw' => "Carla <carla@x.org>\nd@x.org",
        'delivery_driver'   => 'own',
    ])->assertRedirect(route('admin.email_campaigns.show', $c));

    $c->refresh();
    expect($c->subject)->toBe('Assunto novo')
        ->and($c->html_content)->toBe('<p>Texto novo</p>')
        ->and(array_column(json_decode($c->manual_emails, true), 'email'))->toBe(['carla@x.org', 'd@x.org'])
        ->and($c->status)->toBe('draft');
});

it('edição mantém a proteção contra CRLF no assunto', function () {
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin);

    asAdmin($this, $admin)->put(route('admin.email_campaigns.update', $c), [
        'name' => 'X', 'subject' => "Oi\r\nBcc: x@evil.com", 'html_content' => '<p>x</p>', 'audience_type' => 'manual',
    ])->assertSessionHasErrors('subject');

    expect($c->refresh()->subject)->toBe('Assunto antigo');
});

it('campanha já enviada não pode ser editada', function () {
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin, ['status' => 'sent', 'stat_sent' => 10]);

    asAdmin($this, $admin)->get(route('admin.email_campaigns.edit', $c))
        ->assertRedirect(route('admin.email_campaigns.show', $c));

    asAdmin($this, $admin)->put(route('admin.email_campaigns.update', $c), [
        'name' => 'X', 'subject' => 'Novo', 'html_content' => '<p>x</p>', 'audience_type' => 'manual',
    ])->assertRedirect(route('admin.email_campaigns.show', $c));

    expect($c->refresh()->subject)->toBe('Assunto antigo');
});

it('duplicar cria um rascunho igual e abre a edição', function () {
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin, ['status' => 'sent', 'reply_to_email' => 'suporte@vivensi.app.br', 'send_rate_per_minute' => 10]);

    $resp = asAdmin($this, $admin)->post(route('admin.email_campaigns.duplicate', $c));

    $copy = EmailCampaign::withoutGlobalScopes()->latest('id')->first();
    $resp->assertRedirect(route('admin.email_campaigns.edit', $copy));
    expect($copy->id)->not->toBe($c->id)
        ->and($copy->name)->toBe('Rascunho X (cópia)')
        ->and($copy->status)->toBe('draft')
        ->and($copy->html_content)->toBe($c->html_content)
        ->and($copy->manual_emails)->toBe($c->manual_emails)
        ->and($copy->reply_to_email)->toBe('suporte@vivensi.app.br')
        ->and($copy->send_rate_per_minute)->toBe(10)
        ->and($copy->stat_sent)->toBeNull();
});

it('duplicar campanha antiga da Brevo usa o envio próprio quando ele é o padrão', function () {
    config(['campaign_mail.default_driver' => 'own']);
    [$tenant, $admin] = adminCtx();
    $c = draftCampaign($tenant, $admin, ['delivery_driver' => 'brevo', 'status' => 'sent', 'brevo_campaign_id' => 9]);

    asAdmin($this, $admin)->post(route('admin.email_campaigns.duplicate', $c));

    expect(EmailCampaign::withoutGlobalScopes()->latest('id')->value('delivery_driver'))->toBe('own');
});

it('página da campanha mostra Editar só para rascunho e Duplicar sempre', function () {
    [$tenant, $admin] = adminCtx();
    $draft = draftCampaign($tenant, $admin);
    $sent  = draftCampaign($tenant, $admin, ['status' => 'sent', 'stat_sent' => 5, 'name' => 'Enviada']);

    asAdmin($this, $admin)->get(route('admin.email_campaigns.show', $draft))
        ->assertSee(route('admin.email_campaigns.edit', $draft))
        ->assertSee(route('admin.email_campaigns.duplicate', $draft));

    asAdmin($this, $admin)->get(route('admin.email_campaigns.show', $sent))
        ->assertDontSee(route('admin.email_campaigns.edit', $sent))
        ->assertSee(route('admin.email_campaigns.duplicate', $sent));
});

it('criar campanha continua funcionando depois da refatoração', function () {
    [$tenant, $admin] = adminCtx();

    asAdmin($this, $admin)->post(route('admin.email_campaigns.store'), [
        'name' => 'Nova', 'subject' => 'Oi', 'html_content' => '<p>oi</p>', 'audience_type' => 'manual',
        'manual_emails_raw' => 'a@x.org', 'delivery_driver' => 'own',
    ])->assertRedirect(route('admin.email_campaigns.index'));

    $c = EmailCampaign::withoutGlobalScopes()->latest('id')->first();
    expect($c->name)->toBe('Nova')->and($c->status)->toBe('draft')->and($c->created_by)->toBe($admin->id);
});
