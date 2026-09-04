<?php

use App\Models\EmailCampaign;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('salva campanha modo contact_list sem 500', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'ngo_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    $list = EmailContactList::create([
        'tenant_id'           => $tenant->id,
        'name'                => 'Doadores Teste',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
        'created_by'          => $user->id,
    ]);
    EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $tenant->id,
        'email'                 => 'x@y.com',
        'name'                  => 'X',
        'status'                => 'active',
        'source'                => 'manual',
        'added_at'              => now(),
    ]);

    $r = $this->actingAs($user)->post('/ngo/email-campaigns', [
        'name'                  => 'Campanha teste',
        'subject'               => 'Assunto',
        'html_content'          => '<p>oi</p>',
        'audience_type'         => 'contact_list',
        'email_contact_list_id' => $list->id,
    ]);

    $r->assertRedirect();
    $r->assertSessionHasNoErrors();
    expect(EmailCampaign::count())->toBe(1);
});

it('nao explode quando manual_emails_raw eh null (ConvertEmptyStringsToNull)', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'ngo_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    $list = EmailContactList::create([
        'tenant_id'           => $tenant->id,
        'name'                => 'L',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
    ]);
    EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $tenant->id,
        'email'                 => 'x@y.com',
        'status'                => 'active',
        'source'                => 'manual',
        'added_at'              => now(),
    ]);

    // Simula o form real: manual_emails_raw enviado vazio → middleware vira null
    $r = $this->actingAs($user)->post('/ngo/email-campaigns', [
        'name'                  => 'Cx',
        'subject'               => 'Sx',
        'html_content'          => '<p>oi</p>',
        'audience_type'         => 'contact_list',
        'email_contact_list_id' => $list->id,
        'manual_emails_raw'     => null,
    ]);

    $r->assertRedirect();
    $r->assertSessionHasNoErrors();
});

