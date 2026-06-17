<?php

namespace Tests\Unit\Services\Messaging;

use App\Models\User;
use App\Models\WhatsappChat;
use App\Services\Messaging\ChatTransferService;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a parte SEM side-effects do ChatTransferService (Fase 3.A):
 * regra de autoridade (actorCanTransfer). Operações que persistem em DB
 * (transferTo/release) ficam para Feature test ou validação manual no VPS
 * — exigem app bootstrap + RefreshDatabase.
 */
class ChatTransferServiceTest extends TestCase
{
    private ChatTransferService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ChatTransferService();
    }

    private function user(int $id, string $role, int $tenantId = 1): User
    {
        $u = new User();
        $u->forceFill(['id' => $id, 'role' => $role, 'tenant_id' => $tenantId])->exists = true;
        return $u;
    }

    private function chat(int $id, ?int $assignedTo, int $tenantId = 1): WhatsappChat
    {
        $c = new WhatsappChat();
        $c->forceFill([
            'id'          => $id,
            'tenant_id'   => $tenantId,
            'assigned_to' => $assignedTo,
        ])->exists = true;
        return $c;
    }

    public function test_manager_pode_transferir_qualquer_chat(): void
    {
        $manager  = $this->user(10, 'manager');
        $chatLivre  = $this->chat(1, null);
        $chatOutro  = $this->chat(2, 99); // atribuído a outro user
        $chatProprio= $this->chat(3, 10);

        $this->assertTrue($this->svc->actorCanTransfer($chatLivre, $manager));
        $this->assertTrue($this->svc->actorCanTransfer($chatOutro, $manager));
        $this->assertTrue($this->svc->actorCanTransfer($chatProprio, $manager));
    }

    public function test_super_admin_pode_transferir_qualquer_chat(): void
    {
        $admin = $this->user(1, 'super_admin');
        $this->assertTrue($this->svc->actorCanTransfer($this->chat(1, null), $admin));
        $this->assertTrue($this->svc->actorCanTransfer($this->chat(2, 99), $admin));
    }

    public function test_atendente_atual_pode_transferir_o_proprio_chat(): void
    {
        $atendente = $this->user(20, 'ngo');
        $chatMeu   = $this->chat(1, 20);

        $this->assertTrue($this->svc->actorCanTransfer($chatMeu, $atendente));
    }

    public function test_atendente_nao_pode_transferir_chat_de_outro(): void
    {
        $atendente = $this->user(20, 'ngo');
        $chatOutro = $this->chat(1, 99);

        $this->assertFalse($this->svc->actorCanTransfer($chatOutro, $atendente));
    }

    public function test_atendente_nao_pode_transferir_chat_nao_atribuido(): void
    {
        // Chat livre: só manager/super_admin pode atribuir; atendente comum não.
        $atendente = $this->user(20, 'ngo');
        $chatLivre = $this->chat(1, null);

        $this->assertFalse($this->svc->actorCanTransfer($chatLivre, $atendente));
    }

    public function test_user_sem_role_de_agente_nao_pode_transferir(): void
    {
        $randomUser = $this->user(30, 'beneficiary');
        $this->assertFalse($this->svc->actorCanTransfer($this->chat(1, null), $randomUser));
        $this->assertFalse($this->svc->actorCanTransfer($this->chat(2, 30), $randomUser),
            'mesmo sendo assigned_to, role não-agente não autoriza');
    }

    public function test_constante_agent_roles_contem_papeis_esperados(): void
    {
        $this->assertContains('manager',     ChatTransferService::AGENT_ROLES);
        $this->assertContains('super_admin', ChatTransferService::AGENT_ROLES);
        $this->assertContains('ngo',         ChatTransferService::AGENT_ROLES);
    }
}
