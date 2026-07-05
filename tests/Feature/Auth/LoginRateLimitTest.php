<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Cobre o rate limit por conta+IP no LoginController.
 * Além do throttle:10,1 da rota (só por IP), há um lockout por
 * combinação email+IP (5 tentativas / 15 min) que persiste mesmo
 * quando o atacante distribui IPs.
 */

function lrUser(string $email, string $password): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'email'     => $email,
        'password'  => Hash::make($password),
    ]);
}

beforeEach(function () {
    RateLimiter::clear('login:' . sha1('victim@vivensi.app') . '|127.0.0.1');
});

it('permite tentativas dentro do limite', function () {
    lrUser('victim@vivensi.app', 'senha-correta');

    // 4 falhas seguidas — ainda dentro do limite (5)
    for ($i = 0; $i < 4; $i++) {
        $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'errada'])
            ->assertSessionHas('error', 'As credenciais fornecidas estão incorretas.');
    }

    // 5ª tentativa (agora com senha certa) deve funcionar — não foi bloqueado ainda
    $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'senha-correta'])
        ->assertRedirect('/dashboard');
});

it('bloqueia após 5 falhas seguidas na mesma conta+IP', function () {
    lrUser('victim@vivensi.app', 'senha-correta');

    // 5 falhas — enche o balde
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'errada']);
    }

    // 6ª tentativa (mesmo com senha certa) deve estar bloqueada
    $response = $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'senha-correta']);

    $error = session('error');
    expect($error)->toContain('Muitas tentativas')
        ->and($error)->toContain('minuto');
    // Não redirecionou pra dashboard
    $response->assertRedirect();
    expect($response->headers->get('Location'))->not->toContain('/dashboard');
});

it('login com sucesso limpa o contador de falhas', function () {
    lrUser('victim@vivensi.app', 'senha-correta');

    // 4 falhas
    for ($i = 0; $i < 4; $i++) {
        $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'errada']);
    }

    // Login com sucesso
    $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'senha-correta'])
        ->assertRedirect('/dashboard');

    // Logout e nova sessão de 5 falhas — deve ser aceito (contador zerado)
    $this->post('/logout');

    for ($i = 0; $i < 4; $i++) {
        $this->post('/login', ['email' => 'victim@vivensi.app', 'password' => 'errada'])
            ->assertSessionHas('error', 'As credenciais fornecidas estão incorretas.');
    }
});

it('bloqueio de uma conta+IP não afeta outra conta no mesmo IP', function () {
    lrUser('alice@vivensi.app', 'senha-alice');
    lrUser('bob@vivensi.app',   'senha-bob');

    // Estoura balde da alice
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'alice@vivensi.app', 'password' => 'errada']);
    }

    // Alice bloqueada
    $this->post('/login', ['email' => 'alice@vivensi.app', 'password' => 'senha-alice']);
    expect(session('error'))->toContain('Muitas tentativas');

    // Bob no mesmo IP não está bloqueado
    $this->post('/login', ['email' => 'bob@vivensi.app', 'password' => 'senha-bob'])
        ->assertRedirect('/dashboard');
});
