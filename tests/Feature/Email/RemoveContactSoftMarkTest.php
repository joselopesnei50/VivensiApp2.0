<?php

use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * P3 (2026-09-29) — removeContact deve fazer soft-mark 'unsubscribed'
 * em vez de deletar. Preserva historico LGPD e alimenta o safety net
 * do resolveRecipients.
 */

uses(RefreshDatabase::class);

function rmTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function rmList(Tenant $t): EmailContactList
{
    return EmailContactList::create([
        'tenant_id'           => $t->id,
        'name'                => 'Lista teste',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
    ]);
}

function rmContact(EmailContactList $list): EmailContact
{
    return EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $list->tenant_id,
        'email'                 => 'teste@x.com',
        'name'                  => 'Teste',
        'status'                => 'active',
        'source'                => 'manual',
        'added_at'              => now(),
    ]);
}

it('NGO removeContact NAO deleta, marca como unsubscribed', function () {
    $t = rmTenant();
    $list = rmList($t);
    $contact = rmContact($list);
    $user = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);

    $this->actingAs($user)
        ->delete(route('ngo.email_campaigns.lists.contacts.remove', [$list, $contact]))
        ->assertRedirect();

    $fresh = EmailContact::find($contact->id);
    expect($fresh)->not->toBeNull(); // NAO deletou
    expect($fresh->status)->toBe('unsubscribed');
    expect($fresh->unsubscribed_at)->not->toBeNull();
});

it('Admin removeContact NAO deleta, marca como unsubscribed', function () {
    $t = rmTenant();
    $list = rmList($t);
    $contact = rmContact($list);
    $admin = User::factory()->create(['tenant_id' => $t->id, 'role' => 'super_admin']);

    // RequireTwoFactor bloqueia super_admin sem 2FA (redirect /2fa/show).
    $this->actingAs($admin)
        ->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->delete(route('admin.email_campaigns.lists.contacts.remove', [$list, $contact]))
        ->assertRedirect();

    $fresh = EmailContact::find($contact->id);
    expect($fresh)->not->toBeNull();
    expect($fresh->status)->toBe('unsubscribed');
});

it('removeContact eh idempotente em contato ja unsubscribed', function () {
    $t = rmTenant();
    $list = rmList($t);
    $contact = EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $t->id,
        'email'                 => 'ja@x.com',
        'status'                => 'unsubscribed',
        'source'                => 'csv_upload',
        'added_at'              => now()->subDay(),
        'unsubscribed_at'       => now()->subDay(),
    ]);
    $originalUnsubAt = $contact->unsubscribed_at;
    $user = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);

    $this->actingAs($user)
        ->delete(route('ngo.email_campaigns.lists.contacts.remove', [$list, $contact]))
        ->assertRedirect();

    $fresh = EmailContact::find($contact->id);
    expect($fresh->status)->toBe('unsubscribed');
    // Nao sobrescreveu o timestamp antigo (respeita idempotencia)
    expect($fresh->unsubscribed_at->timestamp)->toBe($originalUnsubAt->timestamp);
});
