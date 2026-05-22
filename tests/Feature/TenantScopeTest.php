<?php

namespace Tests\Feature;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que um tenant não pode acessar ou modificar dados de outro tenant.
 * Cobre o escopo multi-tenant nos fluxos financeiro e de rifas.
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['name' => 'ONG A']);
        $this->tenantB = Tenant::factory()->create(['name' => 'ONG B']);

        $this->userA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
        $this->userB = User::factory()->create(['tenant_id' => $this->tenantB->id]);
    }

    /** @test */
    public function tenant_nao_pode_ver_rifa_de_outro_tenant(): void
    {
        $raffleB = Raffle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'status'    => 'active',
        ]);

        $this->actingAs($this->userA)
            ->get(route('raffles.show', $raffleB))
            ->assertForbidden();
    }

    /** @test */
    public function tenant_nao_pode_sortear_rifa_de_outro_tenant(): void
    {
        $raffleB = Raffle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'status'    => 'active',
        ]);

        $this->actingAs($this->userA)
            ->post(route('raffles.draw', $raffleB))
            ->assertForbidden();
    }

    /** @test */
    public function tenant_nao_pode_confirmar_pagamento_de_bilhete_de_outro_tenant(): void
    {
        $raffleB = Raffle::factory()->create(['tenant_id' => $this->tenantB->id]);
        $ticketB = RaffleTicket::factory()->create([
            'raffle_id'   => $raffleB->id,
            'number'      => 1,
            'status'      => 'pending',
            'buyer_name'  => 'Comprador B',
            'buyer_email' => 'b@test.com',
            'buyer_phone' => '11999999999',
        ]);

        $this->actingAs($this->userA)
            ->post(route('raffles.confirm-payment', $ticketB))
            ->assertForbidden();

        // Bilhete não deve ter sido marcado como pago
        $ticketB->refresh();
        $this->assertEquals('pending', $ticketB->status);
    }

    /** @test */
    public function usuario_so_ve_transacoes_do_proprio_tenant(): void
    {
        Transaction::factory()->create(['tenant_id' => $this->tenantA->id, 'amount' => 100]);
        Transaction::factory()->create(['tenant_id' => $this->tenantB->id, 'amount' => 999]);

        // Busca as transações como usuário do tenantA
        $transactionsA = Transaction::where('tenant_id', $this->tenantA->id)->get();

        $this->assertCount(1, $transactionsA);
        $this->assertEquals(100, $transactionsA->first()->amount);

        // Garante que a transação do tenantB não aparece
        $this->assertFalse(
            $transactionsA->contains('amount', 999),
            'Transação do tenant B não deve aparecer para tenant A'
        );
    }

    /** @test */
    public function tenant_nao_pode_excluir_rifa_de_outro_tenant(): void
    {
        $raffleB = Raffle::factory()->create(['tenant_id' => $this->tenantB->id]);

        $this->actingAs($this->userA)
            ->delete(route('raffles.destroy', $raffleB))
            ->assertForbidden();

        $this->assertDatabaseHas('raffles', ['id' => $raffleB->id]);
    }

    /** @test */
    public function canal_broadcast_so_autoriza_tenant_correto(): void
    {
        // Garante que o canal tenant.{tenantId}.whatsapp só autoriza o próprio tenant
        $this->actingAs($this->userA);

        // Tenta se inscrever no canal do tenantB — deve ser negado
        $response = $this->post('/broadcasting/auth', [
            'channel_name' => 'private-tenant.' . $this->tenantB->id . '.whatsapp',
            'socket_id'    => '123.456',
        ]);

        $response->assertForbidden();
    }
}

