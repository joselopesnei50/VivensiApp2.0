<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappForm;
use App\Models\WhatsappFormQuestion;

/**
 * CRUD de formulários conversacionais WhatsApp (Fase 4 — item 2.5, UI).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function wfTenantUser(string $role = 'manager'): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
    return [$tenant, $user];
}

it('lista forms do tenant na index', function () {
    [$tenant, $user] = wfTenantUser();
    WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'Cadastro A', 'is_active' => true]);
    WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'Cadastro B', 'is_active' => false]);

    $resp = $this->actingAs($user)->get('/whatsapp/forms');
    $resp->assertOk();
    $resp->assertSee('Cadastro A');
    $resp->assertSee('Cadastro B');
});

it('store cria form e redireciona pra edit', function () {
    [$tenant, $user] = wfTenantUser();
    $resp = $this->actingAs($user)->post('/whatsapp/forms', [
        'name'        => 'Novo form',
        'description' => 'descricao',
        'is_active'   => '1',
    ]);
    $form = WhatsappForm::where('tenant_id', $tenant->id)->first();
    expect($form)->not->toBeNull();
    expect($form->name)->toBe('Novo form');
    $resp->assertRedirect(route('whatsapp.forms.edit', $form->id));
});

it('update edita os campos do form', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'Antigo', 'is_active' => true]);

    $this->actingAs($user)->put('/whatsapp/forms/' . $form->id, [
        'name'      => 'Atualizado',
        'is_active' => '0',
    ]);

    $form->refresh();
    expect($form->name)->toBe('Atualizado');
    expect($form->is_active)->toBeFalse();
});

it('destroy remove o form', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'X']);

    $this->actingAs($user)->delete('/whatsapp/forms/' . $form->id);
    expect(WhatsappForm::find($form->id))->toBeNull();
});

it('duplicate clona form e perguntas', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'Original', 'is_active' => true]);
    WhatsappFormQuestion::create([
        'form_id' => $form->id, 'position' => 0, 'field_key' => 'name', 'text' => 'Nome?',
        'type' => 'text', 'required' => true,
    ]);

    $this->actingAs($user)->post('/whatsapp/forms/' . $form->id . '/duplicate');

    $clone = WhatsappForm::where('tenant_id', $tenant->id)
        ->where('id', '!=', $form->id)
        ->first();
    expect($clone)->not->toBeNull();
    expect($clone->name)->toBe('Original (cópia)');
    expect($clone->is_active)->toBeFalse(); // cópia nasce inativa
    expect($clone->questions()->count())->toBe(1);
});

it('addQuestion cria pergunta com position incremental', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'F']);

    $r1 = $this->actingAs($user)->postJson('/whatsapp/forms/' . $form->id . '/questions', [
        'field_key' => 'name', 'text' => 'Nome?', 'type' => 'text', 'required' => true,
    ]);
    $r2 = $this->actingAs($user)->postJson('/whatsapp/forms/' . $form->id . '/questions', [
        'field_key' => 'email', 'text' => 'Email?', 'type' => 'text', 'required' => false,
    ]);

    $r1->assertOk();
    $r2->assertOk();
    $qs = $form->questions()->orderBy('position')->get();
    expect($qs)->toHaveCount(2);
    // (int): sqlite devolve string em colunas sem cast no model
    expect((int) $qs[0]->position)->toBe(0);
    expect((int) $qs[1]->position)->toBe(1);
});

it('addQuestion valida tipo permitido', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'F']);

    $r = $this->actingAs($user)->postJson('/whatsapp/forms/' . $form->id . '/questions', [
        'field_key' => 'x', 'text' => '?', 'type' => 'tipo_inexistente',
    ]);
    $r->assertStatus(422);
});

it('reorderQuestions reordena por array', function () {
    [$tenant, $user] = wfTenantUser();
    $form = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'F']);
    $q1 = WhatsappFormQuestion::create(['form_id' => $form->id, 'position' => 0, 'field_key' => 'a', 'text' => '?', 'type' => 'text']);
    $q2 = WhatsappFormQuestion::create(['form_id' => $form->id, 'position' => 1, 'field_key' => 'b', 'text' => '?', 'type' => 'text']);
    $q3 = WhatsappFormQuestion::create(['form_id' => $form->id, 'position' => 2, 'field_key' => 'c', 'text' => '?', 'type' => 'text']);

    $this->actingAs($user)->postJson('/whatsapp/forms/' . $form->id . '/questions/reorder', [
        'ordered_ids' => [$q3->id, $q1->id, $q2->id],
    ])->assertOk();

    expect((int) $q1->fresh()->position)->toBe(1);
    expect((int) $q2->fresh()->position)->toBe(2);
    expect((int) $q3->fresh()->position)->toBe(0);
});

it('reorderQuestions rejeita id de outro form', function () {
    [$tenant, $user] = wfTenantUser();
    $formA = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'A']);
    $formB = WhatsappForm::create(['tenant_id' => $tenant->id, 'name' => 'B']);
    $qA = WhatsappFormQuestion::create(['form_id' => $formA->id, 'position' => 0, 'field_key' => 'a', 'text' => '?', 'type' => 'text']);
    $qB = WhatsappFormQuestion::create(['form_id' => $formB->id, 'position' => 0, 'field_key' => 'b', 'text' => '?', 'type' => 'text']);

    $this->actingAs($user)->postJson('/whatsapp/forms/' . $formA->id . '/questions/reorder', [
        'ordered_ids' => [$qA->id, $qB->id],
    ])->assertStatus(422);
});

it('nao vaza form de outro tenant', function () {
    [$tA, $userA] = wfTenantUser();
    [$tB, $userB] = wfTenantUser();
    $fB = WhatsappForm::create(['tenant_id' => $tB->id, 'name' => 'Do B']);

    $this->actingAs($userA)->get('/whatsapp/forms/' . $fB->id . '/edit')->assertNotFound();
});
