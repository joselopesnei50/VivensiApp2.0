<?php

use App\Jobs\GenerateGrantAnalysisJob;
use App\Jobs\GenerateGrantProposalJob;
use App\Models\NgoGrant;
use App\Models\NgoGrantDocument;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Auditoria 2026-08-01 — achados 1 a 4 do módulo ngo/grants:
 * gate manage-grants existia mas nunca foi aplicado (qualquer user do tenant
 * lia/excluía convênios e disparava jobs de IA pagos); destroy limpava só o
 * disco public (uploads vivem no local); generate-proposal/ai-analyze eram
 * GET mutando estado e re-despachavam job com status processing.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function gctTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function gctUser(Tenant $tenant, string $role): User
{
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);
}

function gctGrant(Tenant $tenant): NgoGrant
{
    return NgoGrant::create([
        'tenant_id' => $tenant->id,
        'title'     => 'Edital Teste',
        'agency'    => 'Órgão X',
        'value'     => 10000,
        'deadline'  => now()->addMonth()->toDateString(),
        'status'    => 'open',
    ]);
}

it('employee recebe 403 nos endpoints de grants (gate manage-grants)', function (string $method, string $uri) {
    $tenant   = gctTenant();
    $employee = gctUser($tenant, 'employee');
    $grant    = gctGrant($tenant);

    $uri = str_replace('{id}', (string) $grant->id, $uri);

    $this->actingAs($employee)->json($method, $uri)->assertStatus(403);
})->with([
    ['GET',    '/ngo/grants'],
    ['GET',    '/ngo/grants/create'],
    ['POST',   '/ngo/grants'],
    ['GET',    '/ngo/grants/{id}'],
    ['PUT',    '/ngo/grants/{id}'],
    ['DELETE', '/ngo/grants/{id}'],
    ['POST',   '/ngo/grants/{id}/status'],
    ['POST',   '/ngo/grants/{id}/generate-proposal'],
    ['POST',   '/ngo/grants/{id}/ai-analyze'],
    ['GET',    '/ngo/grants/{id}/ai-status'],
    ['POST',   '/ngo/grants/{id}/documents'],
]);

it('ngo continua com acesso: index e store criam grant + projeto', function () {
    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');

    $this->actingAs($ngo)->get('/ngo/grants')->assertOk();

    $this->actingAs($ngo)->post('/ngo/grants', [
        'title'        => 'Convênio Novo',
        'grantor_name' => 'Prefeitura',
        'total_amount' => '5000',
        'end_date'     => now()->addMonths(2)->toDateString(),
    ])->assertRedirect('/ngo/grants');

    $grant = NgoGrant::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->first();
    expect($grant)->not->toBeNull();
    expect($grant->title)->toBe('Convênio Novo');

    expect(\App\Models\Project::withoutGlobalScope('tenant')
        ->where('tenant_id', $tenant->id)
        ->where('ngo_grant_id', $grant->id)->exists())->toBeTrue();
});

it('generate-proposal e ai-analyze nao aceitam mais GET', function () {
    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');
    $grant  = gctGrant($tenant);

    $this->actingAs($ngo)->getJson("/ngo/grants/{$grant->id}/generate-proposal")->assertStatus(405);
    $this->actingAs($ngo)->getJson("/ngo/grants/{$grant->id}/ai-analyze")->assertStatus(405);
});

it('generate-proposal com status processing nao despacha outro job', function () {
    Queue::fake();
    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');
    $grant  = gctGrant($tenant);
    $grant->update(['ai_proposal_status' => 'processing']);

    $this->actingAs($ngo)
        ->postJson("/ngo/grants/{$grant->id}/generate-proposal")
        ->assertOk()
        ->assertJson(['status' => 'processing']);

    Queue::assertNotPushed(GenerateGrantProposalJob::class);
});

it('ai-analyze com status processing nao despacha outro job', function () {
    Queue::fake();
    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');
    $grant  = gctGrant($tenant);
    $grant->update(['ai_analysis_status' => 'processing']);

    $this->actingAs($ngo)
        ->postJson("/ngo/grants/{$grant->id}/ai-analyze")
        ->assertOk()
        ->assertJson(['status' => 'processing']);

    Queue::assertNotPushed(GenerateGrantAnalysisJob::class);
});

it('generate-proposal sem status despacha o job normalmente', function () {
    Queue::fake();
    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');
    $grant  = gctGrant($tenant);

    $this->actingAs($ngo)
        ->postJson("/ngo/grants/{$grant->id}/generate-proposal")
        ->assertOk()
        ->assertJson(['status' => 'processing']);

    Queue::assertPushed(GenerateGrantProposalJob::class, 1);
});

it('destroy do grant apaga os arquivos do disco local (nao so o public)', function () {
    Storage::fake('local');
    Storage::fake('public');

    $tenant = gctTenant();
    $ngo    = gctUser($tenant, 'ngo');
    $grant  = gctGrant($tenant);

    $this->actingAs($ngo)->post("/ngo/grants/{$grant->id}/documents", [
        'title' => 'Edital PDF',
        'type'  => 'edital',
        'file'  => UploadedFile::fake()->create('edital.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $doc = NgoGrantDocument::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->firstOrFail();
    Storage::disk('local')->assertExists($doc->file_path);

    $this->actingAs($ngo)->delete("/ngo/grants/{$grant->id}")->assertRedirect('/ngo/grants');

    Storage::disk('local')->assertMissing($doc->file_path);
    expect(NgoGrantDocument::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(NgoGrant::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count())->toBe(0);
});
