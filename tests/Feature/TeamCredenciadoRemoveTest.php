<?php

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tenant;
use App\Models\User;

/*
| Equipe: o credenciado (acesso exclusivo a projeto) agora pode ser removido
| pela tela de Equipe, que mostra em quais projetos ele está (19/11/2026).
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $this->admin  = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
    $this->project = Project::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'name' => 'Oficina de Música', 'status' => 'active', 'budget' => 0]);
    $this->cred = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'credenciado', 'name' => 'Carla Oficineira', 'email' => 'carla@example.com']);
    ProjectMember::create(['tenant_id' => $this->tenant->id, 'project_id' => $this->project->id, 'user_id' => $this->cred->id, 'access_level' => 'editor']);
});

it('mostra o projeto do credenciado e o botão de remover', function () {
    $this->actingAs($this->admin)->get('/ngo/team')->assertOk()
        ->assertSee('Só acessa:')->assertSee('Oficina de Música')
        ->assertSee('/ngo/team/' . $this->cred->id, false);
});

it('remove o credenciado e os vínculos dele com os projetos', function () {
    $this->actingAs($this->admin)->delete('/ngo/team/' . $this->cred->id)->assertSessionHas('success');

    expect(User::find($this->cred->id))->toBeNull()
        ->and(ProjectMember::where('user_id', $this->cred->id)->exists())->toBeFalse();
});

it('outra organização não remove o credenciado', function () {
    $other = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $intruder = User::factory()->create(['tenant_id' => $other->id, 'role' => 'ngo']);

    $this->actingAs($intruder)->delete('/ngo/team/' . $this->cred->id)->assertNotFound();
    expect(User::find($this->cred->id))->not->toBeNull();
});
