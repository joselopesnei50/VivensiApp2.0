<?php

use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailContactListImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminOfTenant(Tenant $t): User
{
    $u = User::factory()->create([
        'tenant_id' => $t->id,
        'role' => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);
    return $u;
}

it('cria lista com opt-in LGPD e upload de CSV', function () {
    Storage::fake('local');
    $tenant = Tenant::factory()->create();
    $user   = adminOfTenant($tenant);

    $csvContent = "email,name\nmaria@ong.org,Maria\njoao@ong.org,Joao\n";
    $csv = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

    $r = $this->actingAs($user)->post('/admin/email-campaigns/lists', [
        'name'             => 'Doadores 2026',
        'description'      => 'Base pra Q4',
        'tags'             => 'doadores, q4',
        'opt_in_confirmed' => '1',
        'csv'              => $csv,
    ]);

    $list = EmailContactList::where('tenant_id', $tenant->id)->first();
    expect($list)->not->toBeNull();
    expect($list->name)->toBe('Doadores 2026');
    expect($list->opt_in_confirmed)->toBeTrue();
    expect($list->tags)->toBe(['doadores', 'q4']);

    // 2 contatos importados
    expect($list->contacts()->count())->toBe(2);
    expect($list->contacts()->pluck('email')->sort()->values()->all())->toBe(['joao@ong.org', 'maria@ong.org']);

    $r->assertRedirect(route('admin.email_campaigns.lists.show', $list));
});

it('rejeita criacao sem opt-in LGPD', function () {
    $tenant = Tenant::factory()->create();
    $user   = adminOfTenant($tenant);

    $this->actingAs($user)->post('/admin/email-campaigns/lists', [
        'name' => 'Teste', 'opt_in_confirmed' => '0',
    ])->assertSessionHasErrors('opt_in_confirmed');
});

it('import CSV pula emails invalidos e duplicatas', function () {
    Storage::fake('local');
    $tenant = Tenant::factory()->create();
    $list = EmailContactList::create([
        'tenant_id' => $tenant->id, 'name' => 'X',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);

    // 1 duplicata + 1 invalido + 1 valido
    EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id' => $tenant->id,
        'email' => 'ja@ong.org', 'status' => 'active', 'source' => 'manual',
        'added_at' => now(),
    ]);

    $csv = UploadedFile::fake()->createWithContent('c.csv', "email,name\nja@ong.org,X\nlixo-nao-eh-email,Y\nnovo@ong.org,Z\n");

    $summary = (new EmailContactListImportService())->importFromCsv($list, $csv);

    expect($summary['imported'])->toBe(1);
    expect($summary['duplicates_in_list'])->toBe(1);
    expect($summary['invalid_emails'])->toBe(1);
});

it('import CSV detecta ponto-e-virgula (Excel BR) como delimiter', function () {
    Storage::fake('local');
    $tenant = Tenant::factory()->create();
    $list = EmailContactList::create([
        'tenant_id' => $tenant->id, 'name' => 'X',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);

    $csv = UploadedFile::fake()->createWithContent('c.csv', "email;name\nex@ong.org;Fulano\n");

    $summary = (new EmailContactListImportService())->importFromCsv($list, $csv);
    expect($summary['imported'])->toBe(1);
    expect($list->contacts()->first()->email)->toBe('ex@ong.org');
    expect($list->contacts()->first()->name)->toBe('Fulano');
});

it('adiciona contato manual sem duplicar', function () {
    $tenant = Tenant::factory()->create();
    $user   = adminOfTenant($tenant);
    $list = EmailContactList::create([
        'tenant_id' => $tenant->id, 'name' => 'X',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);

    $this->actingAs($user)->post("/admin/email-campaigns/lists/{$list->id}/contacts", [
        'email' => 'novo@ong.org', 'name' => 'Novo',
    ])->assertRedirect();

    expect($list->contacts()->count())->toBe(1);

    // Tenta adicionar mesmo email de novo — nao pode duplicar
    $this->actingAs($user)->post("/admin/email-campaigns/lists/{$list->id}/contacts", [
        'email' => 'novo@ong.org', 'name' => 'Outro',
    ]);
    expect($list->contacts()->count())->toBe(1);
});

it('IDOR: user nao ve lista de outro tenant', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();
    $u2 = adminOfTenant($t2);

    $listT1 = EmailContactList::create([
        'tenant_id' => $t1->id, 'name' => 'T1',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);

    $this->actingAs($u2)->get("/admin/email-campaigns/lists/{$listT1->id}")->assertNotFound();
    $this->actingAs($u2)->delete("/admin/email-campaigns/lists/{$listT1->id}")->assertNotFound();
});

it('exportCsv devolve arquivo com contatos', function () {
    $tenant = Tenant::factory()->create();
    $user   = adminOfTenant($tenant);
    $list = EmailContactList::create([
        'tenant_id' => $tenant->id, 'name' => 'X',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);
    EmailContact::create([
        'email_contact_list_id' => $list->id, 'tenant_id' => $tenant->id,
        'email' => 'a@a.com', 'name' => 'A', 'status' => 'active', 'source' => 'manual', 'added_at' => now(),
    ]);

    $r = $this->actingAs($user)->get("/admin/email-campaigns/lists/{$list->id}/export");
    $r->assertOk();
    expect($r->headers->get('Content-Type'))->toContain('text/csv');
});

it('campanha com audience=contact_list persiste email_contact_list_id', function () {
    $tenant = Tenant::factory()->create();
    $user   = adminOfTenant($tenant);
    $list = EmailContactList::create([
        'tenant_id' => $tenant->id, 'name' => 'X',
        'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);
    EmailContact::create([
        'email_contact_list_id' => $list->id, 'tenant_id' => $tenant->id,
        'email' => 'a@a.com', 'status' => 'active', 'source' => 'manual', 'added_at' => now(),
    ]);

    $r = $this->actingAs($user)->post('/admin/email-campaigns', [
        'name'    => 'Camp',
        'subject' => 'S',
        'html_content' => '<p>oi</p>',
        'audience_type' => 'contact_list',
        'email_contact_list_id' => $list->id,
    ]);

    $r->assertRedirect();
    $c = \App\Models\EmailCampaign::latest()->first();
    expect((int) $c->email_contact_list_id)->toBe($list->id);
    expect($c->audience_type)->toBe('contact_list');
});
