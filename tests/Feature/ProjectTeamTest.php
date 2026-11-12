<?php

use App\Mail\TeamInviteMail;
use App\Models\Project;
use App\Models\ProjectLog;
use App\Models\ProjectMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/*
| Equipe do projeto: pessoa com "Acesso exclusivo a este projeto" (credenciado),
| níveis Visualizador/Editor/Admin e quem pode cadastrar o quê.
*/

beforeEach(function () {
    Mail::fake();
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active', 'name' => 'Instituto Girassol']);
    $mk = fn (string $role) => User::factory()->create([
        'tenant_id' => $this->tenant->id, 'role' => $role, 'status' => 'active', 'email' => $role . '_' . uniqid() . '@example.com',
    ]);
    $this->admin = $mk('ngo');
    $this->coord = $mk('manager');
    $this->project = Project::create(['tenant_id' => $this->tenant->id, 'name' => 'Oficina de Música', 'status' => 'active']);
    $this->other = Project::create(['tenant_id' => $this->tenant->id, 'name' => 'Outro projeto', 'status' => 'active']);
    $this->as = function (User $u) { $this->flushSession(); app('auth')->forgetGuards(); return $this->actingAs($u); };
    $this->newCred = function (string $level, ?User $by = null) {
        ($this->as)($by ?? $this->admin)->post("/projects/{$this->project->id}/members/credenciado", [
            'name' => 'Rui Professor', 'email' => "rui.$level@example.com", 'access_level' => $level,
        ])->assertSessionHas('success');
        return User::where('email', "rui.$level@example.com")->sole();
    };
});

it('acesso exclusivo: a pessoa só vê o próprio projeto e recebe convite de 72 h com o nome do projeto', function () {
    $rui = ($this->newCred)('editor');
    expect($rui->role)->toBe('credenciado');

    Mail::assertSent(TeamInviteMail::class, fn ($m) => $m->hasTo($rui->email)
        && $m->projectName === 'Oficina de Música' && str_contains($m->url, 'convite=1'));
    expect(session('invite_link'))->toContain('convite=1');

    ($this->as)($rui)->get("/credenciado/projeto/{$this->project->id}")->assertOk()->assertSee('Oficina de Música');
    ($this->as)($rui)->get("/credenciado/projeto/{$this->other->id}")->assertForbidden();
    foreach (['/ngo/beneficiaries', "/projects/{$this->project->id}", '/transactions', '/ngo/donors'] as $url) {
        ($this->as)($rui)->get($url)->assertRedirect('/credenciado');
    }
});

it('visualizador só consulta; editor registra no diário', function () {
    $viewer = ($this->newCred)('viewer');
    ($this->as)($viewer)->get("/credenciado/projeto/{$this->project->id}")->assertOk()->assertSee('visualização');
    ($this->as)($viewer)->post("/credenciado/projeto/{$this->project->id}/log", ['body' => 'nota do visualizador'])->assertForbidden();
    ($this->as)($viewer)->post("/credenciado/projeto/{$this->project->id}/marco", ['title' => 'x', 'type' => 'milestone'])->assertForbidden();
    expect(ProjectLog::where('body', 'nota do visualizador')->exists())->toBeFalse();

    $editor = ($this->newCred)('editor');
    ($this->as)($editor)->post("/credenciado/projeto/{$this->project->id}/log", ['body' => 'nota do editor'])->assertSessionHas('success');
    expect(ProjectLog::where('body', 'nota do editor')->exists())->toBeTrue();
});

it('coordenação cadastra acesso exclusivo ao projeto, mas não colaborador da organização', function () {
    ($this->as)($this->coord)->get("/projects/{$this->project->id}")->assertOk()
        ->assertSee("/projects/{$this->project->id}/members/credenciado", false);

    ($this->as)($this->coord)->post("/projects/{$this->project->id}/members/credential", [
        'name' => 'Colab', 'email' => 'colab.via.projeto@example.com', 'access_level' => 'editor',
    ])->assertForbidden();
    expect(User::where('email', 'colab.via.projeto@example.com')->exists())->toBeFalse();

    expect(($this->newCred)('editor', $this->coord)->role)->toBe('credenciado');

    ($this->as)($this->admin)->post("/projects/{$this->project->id}/members/credential", [
        'name' => 'Colab', 'email' => 'colab.admin@example.com', 'access_level' => 'editor',
    ])->assertSessionHas('success');
    expect(User::where('email', 'colab.admin@example.com')->value('role'))->toBe('employee');
});

it('com a conta da organização pendente, o acesso exclusivo vê o aviso em vez de cair em loop', function () {
    $rui = ($this->newCred)('editor');
    $this->tenant->update(['subscription_status' => 'awaiting_contract']);
    Cache::forget("tenant.{$this->tenant->id}");

    ($this->as)($rui)->get("/credenciado/projeto/{$this->project->id}")->assertStatus(402)->assertSee('precisa de uma ação do administrador');
});

it('vincular a mesma pessoa de novo só muda o nível, sem duplicar', function () {
    $emp = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'employee', 'email' => 'e' . uniqid() . '@example.com']);
    foreach (['viewer', 'editor'] as $lvl) {
        ($this->as)($this->admin)->post("/projects/{$this->project->id}/members", ['user_id' => $emp->id, 'access_level' => $lvl]);
    }
    expect(ProjectMember::where('project_id', $this->project->id)->where('user_id', $emp->id)->pluck('access_level')->all())->toBe(['editor']);
});

it('colaborador não abre contrato/pagamento; busca sem valores financeiros', function () {
    $emp = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'employee', 'status' => 'active', 'email' => 'e' . uniqid() . '@example.com']);
    \App\Models\Transaction::create(['tenant_id' => $this->tenant->id, 'description' => 'Doação Girassol', 'amount' => 500, 'type' => 'income', 'date' => now(), 'status' => 'paid']);

    foreach (['/register/contrato', '/checkout/1'] as $url) {
        ($this->as)($emp)->get($url)->assertForbidden();
    }
    ($this->as)($this->coord)->get('/register/contrato')->assertForbidden();

    expect(collect(($this->as)($emp)->getJson('/search?q=Girassol')->json())->pluck('category'))->not->toContain('Transações');
    expect(collect(($this->as)($this->admin)->getJson('/search?q=Girassol')->json())->pluck('category'))->toContain('Transações');
});
