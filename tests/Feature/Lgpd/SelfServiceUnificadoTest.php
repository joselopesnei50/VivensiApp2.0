<?php

use App\Models\LgpdDataRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Perfil e /eu/dados usam o mesmo autoatendimento LGPD (2026-11-21).
 * Pedidos antigos de exclusão feitos pelo Perfil não têm scheduled_for
 * e não podem derrubar a página.
 */
uses(RefreshDatabase::class);

function lgpdUniUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);

    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

it('perfil leva exportar e excluir para o autoatendimento', function () {
    $this->actingAs(lgpdUniUser())->get('/profile')
        ->assertOk()
        ->assertSee(route('lgpd.self.index').'#exportar', false)
        ->assertSee(route('lgpd.self.index').'#excluir', false)
        ->assertDontSee('lgpd-delete-modal', false);
});

it('pedido antigo de exclusão sem data agendada aparece e pode ser cancelado', function () {
    $user = lgpdUniUser();
    $req = LgpdDataRequest::create([
        'user_id' => $user->id, 'tenant_id' => $user->tenant_id,
        'type' => 'delete', 'status' => 'pending', 'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($user)->get('/eu/dados')
        ->assertOk()
        ->assertSee('Exclusão agendada')
        ->assertSee('em análise pela equipe (protocolo #'.$req->id.')', false);

    $this->actingAs($user)->post("/eu/dados/excluir/cancelar/{$req->id}")
        ->assertSessionHas('success', 'Exclusão cancelada. Sua conta continua ativa normalmente.');
    expect($req->fresh()->cancelled_at)->not->toBeNull();
});

it('mensagens do autoatendimento saem com acento', function () {
    $user = lgpdUniUser();
    $this->actingAs($user)->post('/eu/dados/excluir', ['confirm' => '1'])
        ->assertSessionHas('success', fn ($m) => str_starts_with($m, 'Exclusão agendada para '));
});
