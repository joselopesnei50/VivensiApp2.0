<?php

use App\Models\MeetingBooking;
use App\Models\SalesLead;
use App\Models\SalesLeadActivity;
use App\Models\SalesStage;
use App\Models\Tenant;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── helpers ───────────────────────────────────────────────────────────────────

function spAdmin(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'super_admin']);
}

function regularUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
}

function firstStage(): SalesStage
{
    // A migration já faz o seed; RefreshDatabase a executa antes de cada teste
    return SalesStage::orderBy('position')->firstOrFail();
}

function wonStage(): SalesStage
{
    return SalesStage::where('is_won', true)->firstOrFail();
}

function makeLead(array $attrs = []): SalesLead
{
    return SalesLead::create(array_merge([
        'name'     => 'Lead Teste',
        'email'    => 'lead@teste.com',
        'origin'   => 'manual',
        'stage_id' => firstStage()->id,
        'position' => 0,
    ], $attrs));
}

function makeBooking(array $attrs = []): MeetingBooking
{
    return MeetingBooking::create(array_merge([
        'name'         => 'Demo Teste',
        'email'        => 'demo@teste.com',
        'phone'        => '11999999999',
        'meeting_date' => now()->addDays(3)->toDateString(),
        'meeting_time' => '14:00:00',
        'status'       => 'confirmed',
    ], $attrs));
}

// ── Controle de acesso ────────────────────────────────────────────────────────

it('bloqueia acesso ao funil para usuário não super_admin', function () {
    $this->actingAs(regularUser())
        ->get('/admin/sales')
        ->assertStatus(403);
});

it('super_admin acessa o board com sucesso', function () {
    $this->actingAs(spAdmin())
        ->get('/admin/sales')
        ->assertStatus(200)
        ->assertSee('Funil Comercial');
});

it('usuário não autenticado é redirecionado para login', function () {
    $this->get('/admin/sales')->assertRedirect('/login');
});

// ── Mover card ────────────────────────────────────────────────────────────────

it('mover card atualiza stage_id no banco', function () {
    $admin   = spAdmin();
    $lead    = makeLead();
    $target  = SalesStage::where('is_won', false)->where('is_lost', false)
                         ->where('id', '!=', $lead->stage_id)
                         ->orderBy('position')->first();

    $this->actingAs($admin)
        ->postJson("/admin/sales/leads/{$lead->id}/move", [
            'stage_id' => $target->id,
            'position' => 0,
        ])
        ->assertJson(['success' => true]);

    expect((int) $lead->fresh()->stage_id)->toBe($target->id);
});

it('mover card registra atividade stage_changed', function () {
    $admin  = spAdmin();
    $lead   = makeLead();
    $target = SalesStage::where('is_won', false)->where('is_lost', false)
                        ->where('id', '!=', $lead->stage_id)
                        ->orderBy('position')->first();

    $this->actingAs($admin)
        ->postJson("/admin/sales/leads/{$lead->id}/move", [
            'stage_id' => $target->id,
            'position' => 0,
        ]);

    expect(
        SalesLeadActivity::where('lead_id', $lead->id)
            ->where('type', 'stage_changed')
            ->exists()
    )->toBeTrue();
});

it('mover para estágio Ganho retorna needs_conversion=true', function () {
    $admin = spAdmin();
    $lead  = makeLead();

    $this->actingAs($admin)
        ->postJson("/admin/sales/leads/{$lead->id}/move", [
            'stage_id' => wonStage()->id,
            'position' => 0,
        ])
        ->assertJson(['success' => true, 'needs_conversion' => true]);
});

it('mover para estágio comum retorna needs_conversion=false', function () {
    $admin  = spAdmin();
    $lead   = makeLead();
    $target = SalesStage::where('is_won', false)->where('is_lost', false)
                        ->where('id', '!=', $lead->stage_id)
                        ->orderBy('position')->first();

    $this->actingAs($admin)
        ->postJson("/admin/sales/leads/{$lead->id}/move", [
            'stage_id' => $target->id,
            'position' => 0,
        ])
        ->assertJson(['needs_conversion' => false]);
});

// ── Criar lead ────────────────────────────────────────────────────────────────

it('super_admin cria lead com sucesso', function () {
    $admin = spAdmin();

    $this->actingAs($admin)
        ->postJson('/admin/sales/leads', [
            'name'     => 'Novo Lead',
            'origin'   => 'manual',
            'stage_id' => firstStage()->id,
        ])
        ->assertJson(['success' => true]);

    expect(SalesLead::where('name', 'Novo Lead')->exists())->toBeTrue();
});

it('criar lead registra atividade created', function () {
    $admin = spAdmin();

    $this->actingAs($admin)->postJson('/admin/sales/leads', [
        'name'     => 'Lead Com Atividade',
        'origin'   => 'manual',
        'stage_id' => firstStage()->id,
    ]);

    $lead = SalesLead::where('name', 'Lead Com Atividade')->firstOrFail();

    expect(
        SalesLeadActivity::where('lead_id', $lead->id)->where('type', 'created')->exists()
    )->toBeTrue();
});

// ── Importação de bookings (dedupe) ───────────────────────────────────────────

it('importação ignora booking quando e-mail já existe como lead', function () {
    $admin   = spAdmin();
    $booking = makeBooking(['email' => 'duplicado@teste.com']);
    makeLead(['email' => 'duplicado@teste.com']); // já existe

    $this->actingAs($admin)
        ->postJson('/admin/sales/import/bookings')
        ->assertJson(['success' => true, 'imported' => 0, 'skipped' => 1]);
});

it('importação cria lead para booking sem duplicata', function () {
    $admin   = spAdmin();
    $booking = makeBooking(['email' => 'novo@teste.com']);

    $response = $this->actingAs($admin)
        ->postJson('/admin/sales/import/bookings')
        ->assertJson(['success' => true]);

    $data = $response->json();
    expect($data['imported'])->toBeGreaterThanOrEqual(1);

    expect(
        SalesLead::where('email', 'novo@teste.com')
                 ->where('origin', 'demo_agendada')
                 ->where('meeting_booking_id', $booking->id)
                 ->exists()
    )->toBeTrue();
});

it('importação não duplica booking já vinculado a lead', function () {
    $admin   = spAdmin();
    $booking = makeBooking(['email' => 'javia@teste.com']);
    makeLead(['email' => 'javia@teste.com', 'meeting_booking_id' => $booking->id]);

    $this->actingAs($admin)
        ->postJson('/admin/sales/import/bookings')
        ->assertJson(['imported' => 0]);
});

// ── Soft delete ───────────────────────────────────────────────────────────────

it('excluir lead faz soft delete (permanece no banco)', function () {
    $admin = spAdmin();
    $lead  = makeLead();

    $this->actingAs($admin)
        ->deleteJson("/admin/sales/leads/{$lead->id}")
        ->assertJson(['success' => true]);

    expect(SalesLead::find($lead->id))->toBeNull();                  // escopo normal não vê
    expect(SalesLead::withTrashed()->find($lead->id))->not->toBeNull(); // mas existe no banco
});
