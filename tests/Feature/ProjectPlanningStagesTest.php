<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Tenant;
use App\Models\User;

/*
| Planejamento: marcos e etapas dividem a tabela project_stages (17/07/2026).
| Etapa criada em /stages não tem target_date, e o Planejamento dava erro 500
| (`target_date->format()` em null) em todo projeto com etapa (19/11/2026).
*/

beforeEach(function () {
    config(['planning.enabled' => true]);
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
    $this->project = Project::withoutGlobalScopes()->create(['tenant_id' => $this->tenant->id, 'name' => 'Oficina', 'status' => 'active', 'budget' => 15000]);
});

it('abre o Planejamento de projeto com etapa sem data prevista', function () {
    ProjectStage::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'project_id' => $this->project->id, 'title' => 'Inscrições e seleção',
        'start_date' => '2026-12-01', 'end_date' => '2026-12-20', 'planned_value' => 1500, 'status' => 'in_progress',
    ]);
    ProjectStage::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'project_id' => $this->project->id, 'title' => 'Etapa sem datas', 'status' => 'pending',
    ]);

    $this->actingAs($this->user)->get("/projects/{$this->project->id}/planning")->assertOk()
        ->assertSee('Inscrições e seleção')->assertSee('20/12/2026')->assertSee('Em andamento')
        ->assertSee('Etapa sem datas')->assertSee('sem data');
});

it('o marco criado no Planejamento continua com a data prevista', function () {
    $this->actingAs($this->user)->post("/projects/{$this->project->id}/planning/milestones", [
        'title' => 'Mostra de música', 'target_date' => '2027-06-15', 'status' => 'pending',
    ])->assertRedirect();

    $this->actingAs($this->user)->get("/projects/{$this->project->id}/planning")->assertOk()
        ->assertSee('Mostra de música')->assertSee('15/06/2027');
});

it('a data do marco usa target_date, depois o fim e depois o início', function () {
    $s = new ProjectStage(['start_date' => '2026-12-01', 'end_date' => '2026-12-20']);
    expect($s->planned_date->format('d/m/Y'))->toBe('20/12/2026');
    $s->target_date = '2026-12-05';
    expect($s->planned_date->format('d/m/Y'))->toBe('05/12/2026');
    expect((new ProjectStage(['start_date' => '2026-12-01']))->planned_date->format('d/m/Y'))->toBe('01/12/2026')
        ->and((new ProjectStage())->planned_date)->toBeNull();
});
