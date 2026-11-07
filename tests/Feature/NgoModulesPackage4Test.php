<?php

use App\Jobs\GeocodeAddressJob;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\ClassSession;
use App\Models\FamilyMember;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\TransparencyPortal;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/**
 * Análise dos módulos da ONG (06/11/2026) — pacote 4: privacidade e
 * correções menores (itens 7, 10, 11, 15, 16, 17, 19, 20).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p4Org(array $tenant = []): User
{
    $t = Tenant::factory()->create(array_merge(['subscription_status' => 'active', 'type' => 'ngo'], $tenant));
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

function p4SqliteDates(): void
{
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    if (method_exists($pdo, 'sqliteCreateFunction')) {
        $pdo->sqliteCreateFunction('MONTH', fn ($d) => $d ? (int) date('n', strtotime($d)) : null, 1);
        $pdo->sqliteCreateFunction('YEAR', fn ($d) => $d ? (int) date('Y', strtotime($d)) : null, 1);
    }
}

// ── 7: auditoria sem dados sensíveis e sem duplicar ───────────────────────

it('cadastro e edição de beneficiário geram 1 registro de auditoria, sem CPF e nascimento', function () {
    Queue::fake();
    $u = p4Org();

    $this->actingAs($u)->post('/ngo/beneficiaries', ['name' => 'Fulano', 'cpf' => '123.456.789-09', 'birth_date' => '1990-05-05', 'status' => 'active']);
    $b = Beneficiary::where('name', 'Fulano')->first();
    $this->actingAs($u)->put('/ngo/beneficiaries/' . $b->id, ['name' => 'Fulano', 'cpf' => '987.654.321-00', 'birth_date' => '1991-06-06', 'status' => 'active']);

    $logs = AuditLog::where('auditable_type', Beneficiary::class)->where('auditable_id', $b->id)->orderBy('id')->get();
    expect($logs->pluck('event')->all())->toBe(['created', 'updated']);

    $json = $logs->map(fn ($l) => json_encode([$l->old_values, $l->new_values]))->implode(' ');
    expect($json)->not->toContain('1990-05-05')->not->toContain('1991-06-06')
        ->not->toContain('12345678909')->not->toContain('98765432100');
    expect($logs[1]->new_values['cpf'] ?? null)->toBe('[oculto]')
        ->and($logs[1]->new_values['birth_date'] ?? null)->toBe('[oculto]');
});

// ── 10: CPF mascarado na lista ────────────────────────────────────────────

it('lista de beneficiários mostra CPF e NIS mascarados', function () {
    $u = p4Org();
    Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Ana', 'cpf' => '12345678909']);
    Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Bia', 'nis' => '12345678901']);

    $this->actingAs($u)->get('/ngo/beneficiaries')
        ->assertOk()
        ->assertSee('***.456.789-**')
        ->assertSee('NIS •••••••8901')
        ->assertDontSee('12345678909')
        ->assertDontSee('12345678901');
});

// ── 11 e 17: portal ───────────────────────────────────────────────────────

it('portal agrupa cidades com menos de 5 beneficiários', function () {
    p4SqliteDates();
    $u = p4Org(['name' => 'Instituto Teste']);
    TransparencyPortal::create(['tenant_id' => $u->tenant_id, 'slug' => 'teste', 'title' => 'Portal', 'is_published' => true]);
    foreach (range(1, 6) as $i) {
        Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => "A{$i}", 'address_city' => 'Araraquara', 'address_state' => 'SP']);
    }
    Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Única', 'address_city' => 'Gavião Peixoto', 'address_state' => 'SP']);

    $this->get('/transparencia/teste')
        ->assertOk()
        ->assertSee('Araraquara')
        ->assertSee('Outras cidades')
        ->assertDontSee('Gavião Peixoto');
});

it('card de atendimentos mostra o ano inteiro quando se vê um ano anterior', function () {
    p4SqliteDates();
    $u = p4Org();
    TransparencyPortal::create(['tenant_id' => $u->tenant_id, 'slug' => 'ano', 'title' => 'Portal', 'is_published' => true]);
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'B']);
    $ano = now()->year - 1;
    foreach (['01-10', '05-10', '11-10'] as $md) {
        Attendance::create(['tenant_id' => $u->tenant_id, 'beneficiary_id' => $b->id, 'user_id' => $u->id, 'date' => "{$ano}-{$md}", 'type' => 'Visita', 'description' => 'x']);
    }

    $this->get('/transparencia/ano?year=' . $ano)
        ->assertOk()
        ->assertSee('Atendimentos (' . $ano . ')')
        ->assertSee('Ações realizadas em ' . $ano);
});

// ── 15: nascimento do familiar ────────────────────────────────────────────

it('data de nascimento do familiar aparece na ficha', function () {
    $u = p4Org();
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Mãe']);
    FamilyMember::create(['beneficiary_id' => $b->id, 'name' => 'Filho', 'kinship' => 'Filho(a)', 'birth_date' => '2015-04-10']);

    $this->actingAs($u)->get('/ngo/beneficiaries/' . $b->id)->assertOk()->assertSee('Nasc.: 10/04/2015');
});

// ── 16: geocodificação uma vez só ─────────────────────────────────────────

it('cadastrar e mudar endereço dispara a geocodificação uma vez só', function () {
    Queue::fake();
    $u = p4Org();
    $data = ['name' => 'Fulano', 'address_street' => 'Rua A', 'address_city' => 'Araraquara', 'address_state' => 'SP', 'status' => 'active'];

    $this->actingAs($u)->post('/ngo/beneficiaries', $data);
    Queue::assertPushed(GeocodeAddressJob::class, 1);

    $b = Beneficiary::where('name', 'Fulano')->first();
    $b->forceFill(['latitude' => -21.79, 'longitude' => -48.17])->saveQuietly();

    $this->actingAs($u)->put('/ngo/beneficiaries/' . $b->id, array_merge($data, ['address_street' => 'Rua B']));
    Queue::assertPushed(GeocodeAddressJob::class, 2);
    expect($b->fresh()->latitude)->toBeNull();

    // Salvar sem mudar o endereço não geocodifica de novo
    $this->actingAs($u)->put('/ngo/beneficiaries/' . $b->id, array_merge($data, ['address_street' => 'Rua B', 'phone' => '(16) 99999-0000']));
    Queue::assertPushed(GeocodeAddressJob::class, 2);
});

// ── 19: professor de outra organização ────────────────────────────────────

it('chamada avulsa não aceita professor de outra organização', function () {
    $u = p4Org();
    $outro = p4Org();
    $colega = User::factory()->create(['tenant_id' => $u->tenant_id, 'role' => 'common']);
    $p = Project::create(['tenant_id' => $u->tenant_id, 'name' => 'P', 'budget' => 1, 'start_date' => '2026-01-01', 'status' => 'active']);
    $base = ['title' => 'Oficina', 'date' => now()->toDateString(), 'mode' => 'fechada'];

    $this->actingAs($u)->post("/projects/{$p->id}/class-sessions", $base + ['teacher_user_id' => $outro->id])
        ->assertSessionHasErrors('teacher_user_id');
    session()->forget('errors');
    $this->actingAs($u)->post("/projects/{$p->id}/class-sessions", $base + ['teacher_user_id' => $colega->id])
        ->assertSessionHasNoErrors();

    expect(ClassSession::pluck('teacher_user_id')->all())->toBe([$colega->id]);
});

// ── 20: limite do tipo de atendimento ─────────────────────────────────────

it('tipo de atendimento muito longo dá aviso em vez de erro 500', function () {
    $u = p4Org();
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'B']);

    $this->actingAs($u)->post("/ngo/beneficiaries/{$b->id}/attendance", [
        'date' => now()->toDateString(), 'type' => str_repeat('x', 300), 'description' => 'ok',
    ])->assertSessionHasErrors('type');
    expect(Attendance::count())->toBe(0);
});
