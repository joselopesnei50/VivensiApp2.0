<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
| Excluir organização no super admin: tabelas com chave estrangeira sem cascata
| (internal_messages, personal_budgets, whatsapp_chats.assigned_to) davam erro 500.
*/

it('exclui a organização mesmo com mensagens internas, orçamento pessoal e conversa de WhatsApp atribuída', function () {
    $admin  = User::factory()->create(['role' => 'super_admin', 'email' => 'sa_' . uniqid() . '@example.com']);
    $tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'canceled']);
    $u1 = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo', 'email' => 'u1_' . uniqid() . '@example.com']);
    $u2 = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo', 'email' => 'u2_' . uniqid() . '@example.com']);
    $other = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);

    DB::table('internal_messages')->insert(['tenant_id' => $tenant->id, 'sender_id' => $u1->id, 'receiver_id' => $u2->id, 'message' => 'oi', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('personal_budgets')->insert(['tenant_id' => $tenant->id, 'user_id' => $u1->id, 'year' => 2026, 'created_at' => now(), 'updated_at' => now()]);
    $chat = DB::table('whatsapp_chats')->insertGetId(['tenant_id' => $other->id, 'wa_id' => '5516999', 'assigned_to' => $u1->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant->id))
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHas('success');

    expect(Tenant::find($tenant->id))->toBeNull()
        ->and(User::withoutGlobalScopes()->whereIn('id', [$u1->id, $u2->id])->count())->toBe(0)
        ->and(DB::table('whatsapp_chats')->where('id', $chat)->value('assigned_to'))->toBeNull()
        ->and(Tenant::find($other->id))->not->toBeNull();
});

it('não exclui organização ativa', function () {
    $admin  = User::factory()->create(['role' => 'super_admin', 'email' => 'sa_' . uniqid() . '@example.com']);
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)->actingAs($admin)
        ->from('/admin/tenants')->delete(route('admin.tenants.destroy', $tenant->id))->assertSessionHas('error');
    expect(Tenant::find($tenant->id))->not->toBeNull();
});
