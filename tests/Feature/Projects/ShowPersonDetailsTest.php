<?php

use App\Models\LandingPage;
use App\Models\Project;
use App\Models\ProjectPerson;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * showPerson (2026-08-06) — endpoint JSON pra Manager ver todos os campos
 * de um inscrito em /projects/{id}, incluindo custom_fields do landing lead
 * (match por telefone normalizado).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function spdSetup(string $role = 'manager'): array
{
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $user    = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
    $project = Project::factory()->create(['tenant_id' => $tenant->id]);
    $person  = ProjectPerson::create([
        'tenant_id'         => $tenant->id,
        'project_id'        => $project->id,
        'name'              => 'Maria Silva',
        'phone'             => '11988887777',
        'address'           => 'Rua X, 10',
        'city'              => 'São Paulo',
        'guardian_name'     => 'Ana Silva',
        'guardian_phone'    => '11999996666',
        'enrollment_status' => 'ativo',
    ]);
    return [$tenant, $user, $project, $person];
}

it('manager consegue ver detalhes de inscrito (endpoint retorna JSON)', function () {
    [$tenant, $user, $project, $person] = spdSetup('manager');

    $resp = $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(200);

    $resp->assertJsonPath('name', 'Maria Silva');
    $resp->assertJsonPath('phone', '11988887777');
    $resp->assertJsonPath('city', 'São Paulo');
    $resp->assertJsonPath('guardian_name', 'Ana Silva');
});

it('ngo consegue ver detalhes (autorizacao inclui role ngo)', function () {
    [$tenant, $user, $project, $person] = spdSetup('ngo');

    $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(200);
});

it('role fora de manager/ngo/super_admin recebe 403', function () {
    [$tenant, $user, $project, $person] = spdSetup('employee');

    $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(403);
});

it('cross-tenant e bloqueado (404)', function () {
    [$tenantA, $userA, $projectA, $personA] = spdSetup('manager');
    $tenantB  = Tenant::factory()->create(['subscription_status' => 'active']);
    $userB    = User::factory()->create(['tenant_id' => $tenantB->id, 'role' => 'manager']);

    $this->actingAs($userB)
        ->getJson("/projects/{$projectA->id}/people/{$personA->id}")
        ->assertStatus(404);
});

it('retorna custom_fields do landing lead quando bate por phone', function () {
    [$tenant, $user, $project, $person] = spdSetup('manager');

    $lp = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'LP',
        'slug'      => 'lp-spd-' . uniqid(),
        'status'    => 'published',
    ]);
    DB::table('landing_page_leads')->insert([
        'landing_page_id' => $lp->id,
        'name'  => 'Maria Silva',
        'email' => 'maria@teste.com',
        'phone' => '(11) 98888-7777',
        'extra_data' => json_encode([
            'source' => 'lead_capture',
            'custom' => ['profissao' => 'Enfermeira', 'escolaridade' => 'Superior'],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $resp = $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(200);

    $resp->assertJsonPath('email', 'maria@teste.com');
    $resp->assertJsonPath('custom_fields.profissao', 'Enfermeira');
    $resp->assertJsonPath('custom_fields.escolaridade', 'Superior');
});

it('sem lead correspondente retorna custom_fields vazio + email null + origin=manual', function () {
    [$tenant, $user, $project, $person] = spdSetup('manager');

    $resp = $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(200);

    $resp->assertJsonPath('email', null);
    $resp->assertJsonPath('custom_fields', []);
    $resp->assertJsonPath('origin', 'manual');
    $resp->assertJsonPath('lead_match', null);
});

it('FK explicito landing_lead_id traz email + custom_fields (novo fluxo)', function () {
    [$tenant, $user, $project, $person] = spdSetup('manager');

    $lp = LandingPage::create([
        'tenant_id' => $tenant->id,
        'title'     => 'LP',
        'slug'      => 'lp-fk-' . uniqid(),
        'status'    => 'published',
    ]);
    $leadId = DB::table('landing_page_leads')->insertGetId([
        'landing_page_id' => $lp->id,
        'name'  => 'Outro nome qualquer',   // phone e nome diferentes de proposito
        'email' => 'fk@teste.com',          // pra provar que so o FK importa
        'phone' => '00000000000',
        'extra_data' => json_encode([
            'custom' => ['profissao' => 'Advogada'],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $person->update(['landing_lead_id' => $leadId]);

    $resp = $this->actingAs($user)
        ->getJson("/projects/{$project->id}/people/{$person->id}")
        ->assertStatus(200);

    $resp->assertJsonPath('email', 'fk@teste.com');
    $resp->assertJsonPath('custom_fields.profissao', 'Advogada');
    $resp->assertJsonPath('origin', 'landing');
    $resp->assertJsonPath('lead_match', 'fk');
});
