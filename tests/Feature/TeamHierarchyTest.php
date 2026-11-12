<?php

use App\Mail\TeamInviteMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/*
| Equipe da ONG: hierarquia Administrador / Coordenação / Colaborador,
| suspensão, último administrador, convite e troca de senha no 1º acesso.
*/

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active', 'name' => 'Instituto Girassol']);
    $mk = fn (string $role, array $extra = []) => User::factory()->create(array_merge([
        'tenant_id' => $this->tenant->id, 'role' => $role, 'status' => 'active',
        'email' => $role . '_' . uniqid() . '@example.com', 'password' => Hash::make('SenhaForte2026'),
    ], $extra));
    $this->admin    = $mk('ngo', ['name' => 'Ana Admin']);
    $this->coord    = $mk('manager', ['name' => 'Carlos Coordenador']);
    $this->employee = $mk('employee', ['name' => 'Bia Colaboradora']);
    $this->mk = $mk;
    // Troca de usuário na mesma sessão derruba o login (AuthenticateSession compara o hash da senha).
    $this->as = function (User $u) { $this->flushSession(); app('auth')->forgetGuards(); return $this->actingAs($u); };
});

it('membro suspenso não entra e quem já estava logado perde a sessão', function () {
    $this->employee->update(['status' => 'suspended']);

    $this->post('/login', ['email' => $this->employee->email, 'password' => 'SenhaForte2026'])->assertSessionHas('error');
    $this->assertGuest();

    ($this->as)($this->employee)->get('/ngo/beneficiaries')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('o último administrador não se rebaixa nem se suspende; ninguém suspende a si mesmo', function () {
    ($this->as)($this->admin)->put("/ngo/team/{$this->admin->id}", ['name' => 'Ana', 'role' => 'employee', 'status' => 'active'])
        ->assertSessionHas('error');
    expect($this->admin->fresh()->role)->toBe('ngo');

    $other = ($this->mk)('ngo');
    ($this->as)($this->admin)->put("/ngo/team/{$this->admin->id}", ['name' => 'Ana', 'role' => 'ngo', 'status' => 'suspended'])
        ->assertSessionHas('error');
    expect($this->admin->fresh()->status)->toBe('active');

    // com outro administrador ativo, pode deixar de ser administrador
    ($this->as)($this->admin)->put("/ngo/team/{$this->admin->id}", ['name' => 'Ana', 'role' => 'manager', 'status' => 'active'])
        ->assertSessionHas('success');
    expect($this->admin->fresh()->role)->toBe('manager');

    // administrador suspenso não conta como "outro administrador"
    $other->update(['status' => 'suspended']);
    $third = ($this->mk)('ngo');
    ($this->as)($third)->put("/ngo/team/{$third->id}", ['name' => 'X', 'role' => 'employee', 'status' => 'active'])->assertSessionHas('error');
});

it('colaborador: atendimento sim; financeiro, captação, configurações e API não', function () {
    foreach (['/ngo/beneficiaries', '/ngo/beneficiaries/create', '/academy'] as $url) {
        ($this->as)($this->employee)->get($url)->assertOk();
    }
    foreach (['/transactions', '/ngo/reconciliation', '/raffles', '/settings/api-tokens', '/settings/branding', '/ngo/landing-pages', '/ngo/email-campaigns', '/ngo/team', '/ngo/donors'] as $url) {
        ($this->as)($this->employee)->get($url)->assertForbidden();
    }
    ($this->as)($this->employee)->post('/settings/api-tokens', ['name' => 'x'])->assertForbidden();
});

it('painel e menu do colaborador sem valores financeiros e sem links que ele não abre', function () {
    $html = ($this->as)($this->employee)->get('/dashboard')->assertOk()
        ->assertSee('Bia')->assertSee('beneficiários ativos')->assertSee('Colaborador')
        ->getContent();

    expect($html)->not->toMatch('/Receitas? do m[eê]s|Saldo atual|Fluxo de Caixa/u')
        ->and($html)->not->toContain('href="' . url('/transactions') . '"')
        ->and($html)->not->toContain('href="' . url('/ngo/donors') . '"')
        ->and($html)->toContain('href="' . url('/ngo/beneficiaries') . '"');
});

it('coordenação: operação inteira e painel da ONG; sem equipe, RH, configurações e API', function () {
    foreach (['/ngo/donors', '/ngo/grants', '/ngo/budget', '/ngo/receipts', '/transactions', '/ngo/beneficiaries'] as $url) {
        ($this->as)($this->coord)->get($url)->assertOk();
    }
    foreach (['/ngo/team', '/ngo/hr', '/settings/api-tokens'] as $url) {
        ($this->as)($this->coord)->get($url)->assertForbidden();
    }
    ($this->as)($this->coord)->get('/dashboard')->assertOk()->assertSee('Central de Comando — ONG')->assertDontSee('Central de Comando — Projetos');
});

it('token de API do colaborador não vale; o do administrador vale', function () {
    $tEmp = $this->employee->createToken('x')->plainTextToken;
    $tAdm = $this->admin->createToken('x')->plainTextToken;

    $this->withHeader('Authorization', "Bearer $tEmp")->getJson('/api/v1/me')->assertUnauthorized();
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', "Bearer $tAdm")->getJson('/api/v1/me')->assertOk();
});

it('convite por e-mail: a pessoa cria a própria senha pelo link', function () {
    Mail::fake();
    ($this->as)($this->admin)->post('/ngo/team', [
        'name' => 'Davi Novo', 'email' => 'davi@example.com', 'role' => 'employee', 'access_mode' => 'invite',
    ])->assertSessionHas('success');

    $davi = User::where('email', 'davi@example.com')->sole();
    expect($davi->role)->toBe('employee')->and($davi->must_change_password)->toBeFalse();

    $url = null;
    Mail::assertSent(TeamInviteMail::class, function ($m) use (&$url) {
        $url = $m->url;
        return $m->hasTo('davi@example.com') && str_contains($m->url, 'convite=1');
    });

    parse_str(parse_url($url, PHP_URL_QUERY), $q);
    $token = basename(parse_url($url, PHP_URL_PATH));
    auth()->logout();
    $this->get($url)->assertOk()->assertSee('Bem-vindo(a) à equipe!');
    $this->post(route('password.update'), ['token' => $token, 'email' => $q['email'], 'invite' => 1,
        'password' => 'MinhaSenha2026x', 'password_confirmation' => 'MinhaSenha2026x'])
        ->assertRedirect(route('login'))->assertSessionHas('success');

    expect(Hash::check('MinhaSenha2026x', $davi->fresh()->password))->toBeTrue();
});

it('senha definida pelo administrador: troca obrigatória no primeiro acesso', function () {
    ($this->as)($this->admin)->post('/ngo/team', [
        'name' => 'Eva', 'email' => 'eva@example.com', 'role' => 'employee', 'access_mode' => 'password', 'password' => 'SenhaInicial2026',
    ])->assertSessionHas('success');

    $eva = User::where('email', 'eva@example.com')->sole();
    expect($eva->must_change_password)->toBeTrue();

    ($this->as)($eva)->get('/ngo/beneficiaries')->assertRedirect('/profile');
    ($this->as)($eva)->post('/profile/password', [
        'current_password' => 'SenhaInicial2026', 'password' => 'SoMinhaAgora2026', 'password_confirmation' => 'SoMinhaAgora2026',
    ])->assertRedirect('/dashboard');

    expect($eva->fresh()->must_change_password)->toBeFalse();
    $this->actingAs($eva->fresh())->get('/ngo/beneficiaries')->assertOk();
});

it('cadastro: senha curta é recusada com mensagem clara e a função padrão é Colaborador', function () {
    ($this->as)($this->admin)->post('/ngo/team', [
        'name' => 'F', 'email' => 'f@example.com', 'role' => 'employee', 'access_mode' => 'password', 'password' => 'curta',
    ])->assertSessionHasErrors(['password' => 'A senha precisa ter pelo menos 12 caracteres.']);

    ($this->as)($this->admin)->get('/ngo/team')->assertOk()
        ->assertSee('<option value="employee" selected', false)
        ->assertSee('Coordenação')->assertDontSee('Gestor de Projetos');
});

it('com a conta pendente, a equipe vê um aviso (sem cair em loop nem na tela de pagamento); o administrador vai pagar', function () {
    $this->tenant->update(['subscription_status' => 'awaiting_contract']);

    ($this->as)($this->employee)->get('/dashboard')->assertStatus(402)->assertSee('precisa de uma ação do administrador');
    ($this->as)($this->coord)->get('/ngo/donors')->assertStatus(402);
    ($this->as)($this->admin)->get('/dashboard')->assertRedirect(route('adesao.show'));
});

it('colaborador de ONG não importa planilha financeira', function () {
    ($this->as)($this->employee)->post('/finance/import/preview', [])->assertForbidden();
});
