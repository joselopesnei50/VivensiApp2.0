<?php

use App\Models\Raffle;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/*
| Identidade Visual: chave Pix validada e gravada no formato do Banco Central,
| Pix de teste, e o número da autorização SPA/MF nas rifas (18/11/2026).
*/

beforeEach(function () {
    Mail::fake();
    $this->tenant = Tenant::factory()->create([
        'type' => 'ngo', 'subscription_status' => 'active',
        'razao_social' => 'Associação Semente', 'cidade' => 'Araraquara', 'document' => '11222333000181',
    ]);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo', 'email' => 'b_' . uniqid() . '@example.com']);
});

it('abre a página com as três abas', function () {
    $this->actingAs($this->user)->get('/settings/branding')->assertOk()
        ->assertSee('Identidade Visual')->assertSee('Recebimento Pix')->assertSee('Relatório semanal')
        ->assertSee('ASSOCIACAO SEMENTE')
        ->assertDontSee('OpenPix');
});

it('grava o CNPJ digitado com pontuação só com números', function () {
    $this->actingAs($this->user)->post('/settings/branding', [
        '_tab' => 'pix', 'pix_key_type' => 'cpf_cnpj', 'pix_key' => '11.222.333/0001-81',
    ])->assertRedirect(route('settings.branding'))->assertSessionHas('success');

    expect($this->tenant->fresh()->pix_key)->toBe('11222333000181')
        ->and($this->tenant->fresh()->pix_key_type)->toBe('cpf_cnpj');
});

it('recusa chave inválida e não mexe na chave antiga', function () {
    $this->tenant->update(['pix_key' => 'pix@ong.org', 'pix_key_type' => 'email']);

    $this->actingAs($this->user)->post('/settings/branding', [
        '_tab' => 'pix', 'pix_key_type' => 'cpf_cnpj', 'pix_key' => '11.222.333/0001-82',
    ])->assertSessionHasErrors('pix_key');

    $this->actingAs($this->user)->post('/settings/branding', ['pix_key' => 'x@y.com'])
        ->assertSessionHasErrors('pix_key_type');

    expect($this->tenant->fresh()->pix_key)->toBe('pix@ong.org');
});

it('celular vira +55 e o WhatsApp fica só com números', function () {
    $this->actingAs($this->user)->post('/settings/branding', [
        'pix_key_type' => 'phone', 'pix_key' => '(16) 98839-2853', 'whatsapp_support' => '(16) 98839-2853',
    ])->assertSessionHasNoErrors();

    expect($this->tenant->fresh()->pix_key)->toBe('+5516988392853')
        ->and($this->tenant->fresh()->whatsapp_support)->toBe('5516988392853');

    $this->actingAs($this->user)->post('/settings/branding', ['whatsapp_support' => '123'])
        ->assertSessionHasErrors('whatsapp_support');
});

it('enviar só o logo não apaga a chave Pix', function () {
    $this->tenant->update(['pix_key' => 'pix@ong.org', 'pix_key_type' => 'email', 'brand_name' => 'Semente']);

    $this->actingAs($this->user)->post('/settings/branding', ['brand_color' => '#112233'])->assertSessionHasNoErrors();

    $t = $this->tenant->fresh();
    expect($t->pix_key)->toBe('pix@ong.org')->and($t->brand_name)->toBe('Semente')->and($t->brand_color)->toBe('#112233');
});

it('o Pix de teste devolve o QR de R$ 1,00 com a chave certa', function () {
    $r = $this->actingAs($this->user)->postJson('/settings/branding/pix-preview', [
        'pix_key_type' => 'cpf_cnpj', 'pix_key' => '11.222.333/0001-81',
    ])->assertOk()->assertJson(['ok' => true, 'key' => '11222333000181', 'name' => 'ASSOCIACAO SEMENTE', 'city' => 'ARARAQUARA']);

    expect($r->json('payload'))->toContain('011411222333000181')->toContain('54041.00')
        ->and($r->json('warning'))->toBeNull();

    $this->actingAs($this->user)->postJson('/settings/branding/pix-preview', ['pix_key_type' => 'cpf_cnpj', 'pix_key' => '529.982.247-25'])
        ->assertOk()->assertJsonPath('warning', fn ($w) => str_contains($w, 'CPF'));

    $this->actingAs($this->user)->postJson('/settings/branding/pix-preview', ['pix_key_type' => 'phone', 'pix_key' => '3333'])
        ->assertStatus(422)->assertJson(['ok' => false]);
});

it('salvar a configuração do WhatsApp não apaga mais a chave Pix', function () {
    $tenant  = Tenant::factory()->create(['type' => 'manager', 'subscription_status' => 'active', 'pix_key' => 'pix@ong.org', 'pix_key_type' => 'email']);
    $manager = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $this->actingAs($manager)->post('/whatsapp/settings', ['ai_enabled' => '0'])->assertRedirect();

    expect($tenant->fresh()->pix_key)->toBe('pix@ong.org');
});

it('o QR da rifa usa a chave antiga já no formato do Banco Central', function () {
    $this->tenant->update(['pix_key' => '11.222.333/0001-81', 'pix_key_type' => null]);
    $this->actingAs($this->user)->post('/raffles', ['title' => 'Rifa Pix', 'description' => 'Cesta', 'ticket_price' => 10, 'total_tickets' => 20])->assertRedirect();
    auth()->logout();
    $raffle = Raffle::withoutGlobalScopes()->where('title', 'Rifa Pix')->sole();

    $this->post(route('public.raffle.reserve', $raffle->slug), [
        'buyer_name' => 'Maria', 'buyer_email' => 'm@example.com', 'buyer_phone' => '16999990000', 'selected_numbers' => [1],
    ])->assertRedirect();
    $token = \App\Models\RaffleTicket::where('raffle_id', $raffle->id)->where('number', 1)->value('reservation_token');

    $this->get(route('public.raffle.order', [$raffle->slug, $token]))->assertOk()
        ->assertSee('011411222333000181', false)
        ->assertDontSee('011811.222.333/0001-81', false);
});

it('a rifa guarda o número da autorização e mostra na página pública', function () {
    $this->tenant->update(['pix_key' => 'pix@ong.org', 'pix_key_type' => 'email']);
    $this->actingAs($this->user)->get('/raffles/create')->assertOk()->assertSee('Secretaria de Prêmios e Apostas');

    $this->actingAs($this->user)->post('/raffles', [
        'title' => 'Rifa Autorizada', 'description' => 'Cesta', 'ticket_price' => 10, 'total_tickets' => 10,
    ])->assertRedirect();
    $raffle = Raffle::withoutGlobalScopes()->where('title', 'Rifa Autorizada')->sole();
    expect($raffle->authorization_number)->toBeNull();

    $this->get(route('public.raffle.show', $raffle->slug))->assertOk()->assertDontSee('Certificado nº');

    $this->actingAs($this->user)->post(route('raffles.authorization', $raffle), ['authorization_number' => ' 04.001234/2026 '])
        ->assertSessionHas('success');
    expect($raffle->fresh()->authorization_number)->toBe('04.001234/2026');

    $this->get(route('public.raffle.show', $raffle->slug))->assertOk()
        ->assertSee('Sorteio autorizado pelo Ministério da Fazenda')->assertSee('04.001234/2026');
});

it('outra organização não altera a autorização da rifa', function () {
    $raffle = Raffle::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'title' => 'R', 'slug' => 'r-' . uniqid(), 'description' => 'd',
        'ticket_price' => 5, 'total_tickets' => 1, 'status' => 'active',
    ]);
    $other = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $intruder = User::factory()->create(['tenant_id' => $other->id, 'role' => 'ngo']);

    $resp = $this->actingAs($intruder)->post("/raffles/{$raffle->id}/authorization", ['authorization_number' => 'X']);
    expect($resp->status())->toBeIn([403, 404]);
    expect($raffle->fresh()->authorization_number)->toBeNull();
});
