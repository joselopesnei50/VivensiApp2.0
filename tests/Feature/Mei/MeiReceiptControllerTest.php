<?php

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;

/**
 * Vivensi — Recibos MEI.
 * Cobre store (com e sem client), regenerate, revoke + integração com o
 * termômetro do teto MEI (income criada via recibo deve contar).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function meiRcTenantUser(string $role = 'client'): array
{
    $tenant = Tenant::factory()->create(['type' => 'common']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role'      => $role,
    ]);
    return [$tenant, $user];
}

it('store cria Transaction income com token e vincula client', function () {
    [$tenant, $user] = meiRcTenantUser();
    $client = Client::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Maria Cliente',
        'type'      => 'individual',
    ]);

    $resp = $this->actingAs($user)->post('/personal/receipts', [
        'client_id'   => $client->id,
        'description' => 'Servico de design',
        'amount'      => '350,00',
        'date'        => now()->toDateString(),
    ]);

    $resp->assertRedirect('/personal/receipts');
    $tx = Transaction::where('tenant_id', $tenant->id)->first();
    expect($tx)->not->toBeNull();
    expect($tx->type)->toBe('income');
    expect($tx->status)->toBe('paid');
    expect($tx->client_id)->toBe($client->id);
    expect($tx->public_receipt_token)->not->toBeEmpty();
    expect($tx->public_receipt_token_bidx)->not->toBeEmpty();
    expect($tx->receipt_auth_code)->not->toBeEmpty();
});

it('store aceita recibo avulso sem client_id', function () {
    [$tenant, $user] = meiRcTenantUser();

    $this->actingAs($user)->post('/personal/receipts', [
        'recipient_name'     => 'Joao Avulso',
        'recipient_document' => '123.456.789-00',
        'amount'             => '120,00',
        'date'               => now()->toDateString(),
    ]);

    $tx = Transaction::where('tenant_id', $tenant->id)->first();
    expect($tx->client_id)->toBeNull();
    expect($tx->description)->toContain('Joao Avulso');
});

it('store usa description default quando avulso sem nome', function () {
    [$tenant, $user] = meiRcTenantUser();

    $this->actingAs($user)->post('/personal/receipts', [
        'amount' => '99,00',
        'date'   => now()->toDateString(),
    ]);

    $tx = Transaction::where('tenant_id', $tenant->id)->first();
    expect($tx->description)->toBe('Recibo de prestação de serviço/venda');
});

it('regenerateLink troca o token preservando a Transaction', function () {
    [$tenant, $user] = meiRcTenantUser();
    $this->actingAs($user)->post('/personal/receipts', [
        'amount' => '50,00',
        'date'   => now()->toDateString(),
    ]);
    $tx = Transaction::where('tenant_id', $tenant->id)->first();
    $tokenAntigo = $tx->public_receipt_token;
    $idAntigo = $tx->id;

    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/regenerate-link");

    $tx->refresh();
    expect($tx->id)->toBe($idAntigo);
    expect($tx->public_receipt_token)->not->toBe($tokenAntigo);
});

it('revokeLink expira o token imediatamente', function () {
    [$tenant, $user] = meiRcTenantUser();
    $this->actingAs($user)->post('/personal/receipts', [
        'amount' => '40,00',
        'date'   => now()->toDateString(),
    ]);
    $tx = Transaction::where('tenant_id', $tenant->id)->first();

    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/revoke-link");

    $tx->refresh();
    expect($tx->public_receipt_expires_at)->not->toBeNull();
    expect($tx->public_receipt_expires_at->isPast())->toBeTrue();
});

it('recibo conta no termometro do teto MEI', function () {
    [$tenant, $user] = meiRcTenantUser();
    $this->actingAs($user)->post('/personal/receipts', [
        'amount' => '5000,00',
        'date'   => now()->toDateString(),
    ]);

    $teto = app(\App\Services\MeiPanelService::class)->tetoMei($tenant->id);
    expect($teto['realizado_centavos'])->toBe(500_000);
});

it('store rejeita client_id de outro tenant', function () {
    [$tenantA, $userA] = meiRcTenantUser();
    [$tenantB, $userB] = meiRcTenantUser();
    $clientB = Client::create([
        'tenant_id' => $tenantB->id,
        'name'      => 'Cliente do B',
        'type'      => 'individual',
    ]);

    $resp = $this->actingAs($userA)->from('/personal/receipts/create')->post('/personal/receipts', [
        'client_id' => $clientB->id,
        'amount'    => '100',
        'date'      => now()->toDateString(),
    ]);

    $resp->assertSessionHasErrors('client_id');
    expect(Transaction::where('tenant_id', $tenantA->id)->count())->toBe(0);
});
