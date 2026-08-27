<?php

use App\Models\EmailCampaign;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Painel NGO — CRUD de listas de contatos + upload de imagem + audience
 * contact_list. Painel NGO eh o produto principal (memoria durable), entao
 * deve ter paridade de features com admin/email_campaigns.
 */

function ngoUserOfTenant(Tenant $t): User
{
    return User::factory()->forTenant($t)->create([
        'role' => 'ngo',
    ]);
}

it('/ngo/email-campaigns/create renderiza pra role ngo', function () {
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    $r = test()->actingAs($user)->get('/ngo/email-campaigns/create');
    $r->assertOk();
    $r->assertSee('Nova Campanha');
    $r->assertSee('Gerenciar listas'); // link novo pra lists.index
    $r->assertSee('Inserir imagem');   // botao upload
});

it('/ngo/email-campaigns/lists renderiza e mostra listas do tenant', function () {
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    EmailContactList::create([
        'tenant_id'           => $tenant->id,
        'name'                => 'Doadores Ativos 2026',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
        'created_by'          => $user->id,
        'tags'                => [],
    ]);

    $r = test()->actingAs($user)->get('/ngo/email-campaigns/lists');
    $r->assertOk();
    $r->assertSee('Doadores Ativos 2026');
});

it('NGO cria lista com opt-in LGPD', function () {
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    $r = test()->actingAs($user)->post('/ngo/email-campaigns/lists', [
        'name'             => 'Ex-Alunos 2026',
        'description'      => 'Turma 2010-2015',
        'tags'             => 'ex-alunos, reencontro',
        'opt_in_confirmed' => '1',
    ]);

    $list = EmailContactList::where('tenant_id', $tenant->id)->first();
    expect($list)->not->toBeNull();
    expect($list->name)->toBe('Ex-Alunos 2026');
    expect($list->tags)->toBe(['ex-alunos', 'reencontro']);
    $r->assertRedirect(route('ngo.email_campaigns.lists.show', $list));
});

it('NGO nao consegue ver lista de outro tenant (404)', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();
    $user2 = ngoUserOfTenant($t2);

    $list = EmailContactList::create([
        'tenant_id'           => $t1->id,
        'name'                => 'Confidencial T1',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
        'tags'                => [],
    ]);

    test()->actingAs($user2)->get("/ngo/email-campaigns/lists/{$list->id}")->assertNotFound();
});

it('NGO cria campanha com audience_type=contact_list e resolve destinatarios', function () {
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    $list = EmailContactList::create([
        'tenant_id'           => $tenant->id,
        'name'                => 'Base Ativa',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
        'tags'                => [],
    ]);
    EmailContact::create([
        'email_contact_list_id' => $list->id,
        'tenant_id'             => $tenant->id,
        'email'                 => 'ana@ong.org',
        'name'                  => 'Ana',
        'status'                => EmailContact::STATUS_ACTIVE,
        'source'                => 'manual',
        'added_at'              => now(),
    ]);

    $r = test()->actingAs($user)->post('/ngo/email-campaigns', [
        'name'                  => 'Newsletter Outubro',
        'subject'               => 'Nossas novidades',
        'html_content'          => '<p>Olá</p>',
        'audience_type'         => 'contact_list',
        'email_contact_list_id' => $list->id,
    ]);

    $campaign = EmailCampaign::where('tenant_id', $tenant->id)->first();
    expect($campaign)->not->toBeNull();
    expect($campaign->audience_type)->toBe('contact_list');
    expect((int) $campaign->email_contact_list_id)->toBe((int) $list->id);
    $r->assertRedirect(route('ngo.email_campaigns.show', $campaign));
});

it('NGO nao consegue criar campanha usando lista de outro tenant', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();
    $user2 = ngoUserOfTenant($t2);

    $listT1 = EmailContactList::create([
        'tenant_id'           => $t1->id,
        'name'                => 'Alheio',
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
        'tags'                => [],
    ]);

    $r = test()->actingAs($user2)->post('/ngo/email-campaigns', [
        'name'                  => 'Tentativa',
        'subject'               => 'x',
        'html_content'          => '<p>x</p>',
        'audience_type'         => 'contact_list',
        'email_contact_list_id' => $listT1->id,
    ]);

    $r->assertSessionHasErrors('email_contact_list_id');
    expect(EmailCampaign::where('tenant_id', $t2->id)->count())->toBe(0);
});

it('NGO uploadImage aceita PNG e devolve URL publica', function () {
    Storage::fake('public');
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');
    $r = test()->actingAs($user)->postJson('/ngo/email-campaigns/upload-image', [
        'image' => $file,
    ]);

    $r->assertOk();
    $r->assertJsonStructure(['url']);
    expect($r->json('url'))->toContain('email_campaigns_uploads');
    expect($r->json('url'))->toContain((string) $tenant->id);
});

it('NGO uploadImage rejeita arquivo maior que 5MB', function () {
    Storage::fake('public');
    $tenant = Tenant::factory()->create();
    $user   = ngoUserOfTenant($tenant);

    // fake image 6MB
    $big = UploadedFile::fake()->create('big.png', 6144, 'image/png');
    $r = test()->actingAs($user)->postJson('/ngo/email-campaigns/upload-image', [
        'image' => $big,
    ]);
    $r->assertStatus(422);
});
