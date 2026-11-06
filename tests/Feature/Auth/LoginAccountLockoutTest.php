<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\TotpService;
use App\Support\LoginLockout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Bloqueio por conta (qualquer IP) — ataque distribuído que troca de IP a
 * cada tentativa. Complementa o LoginRateLimitTest (conta+IP).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function laUser(string $email = 'alvo@vivensi.app', string $password = 'SenhaCerta2026'): User
{
    $t = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $t->id, 'email' => $email, 'password' => Hash::make($password)]);
}

/** Cada tentativa vem de um IP diferente. */
function laTry($test, string $email, string $password, int $i)
{
    return $test->withServerVariables(['REMOTE_ADDR' => '203.0.113.' . ($i % 250 + 1)])
        ->post('/login', ['email' => $email, 'password' => $password]);
}

it('trava a conta após 15 erros vindos de IPs diferentes', function () {
    laUser();
    Log::spy();

    for ($i = 0; $i < LoginLockout::ACCOUNT_MAX - 1; $i++) {
        laTry($this, 'alvo@vivensi.app', 'errada', $i)->assertSessionHas('error', 'As credenciais fornecidas estão incorretas.');
    }
    // 14 erros: ainda entra com a senha certa (de outro IP)
    laTry($this, 'alvo@vivensi.app', 'SenhaCerta2026', 100)->assertRedirect('/dashboard');
    $this->post('/logout');

    laTry($this, 'ALVO@vivensi.app ', 'errada', 101); // 15º erro (maiúsculas/espaço contam igual)

    $res = laTry($this, 'alvo@vivensi.app', 'SenhaCerta2026', 102);
    expect(session('error'))->toContain('Muitas tentativas')->toContain('Esqueceu?');
    expect($res->headers->get('Location'))->not->toContain('/dashboard');
    $this->assertGuest();

    Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'conta bloqueada'))->once();
});

it('trava de uma conta não afeta outra', function () {
    laUser('alvo@vivensi.app');
    laUser('outra@vivensi.app', 'OutraSenha2026');

    for ($i = 0; $i < LoginLockout::ACCOUNT_MAX; $i++) {
        laTry($this, 'alvo@vivensi.app', 'errada', $i);
    }

    laTry($this, 'outra@vivensi.app', 'OutraSenha2026', 5)->assertRedirect('/dashboard');
});

it('e-mail que não existe é contado igual (não revela quais contas existem)', function () {
    for ($i = 0; $i < LoginLockout::ACCOUNT_MAX; $i++) {
        laTry($this, 'ninguem@vivensi.app', 'x', $i)->assertSessionHas('error', 'As credenciais fornecidas estão incorretas.');
    }
    laTry($this, 'ninguem@vivensi.app', 'x', 99);
    expect(session('error'))->toContain('Muitas tentativas');
});

it('redefinir a senha pelo link do e-mail destrava a conta', function () {
    $user = laUser();
    for ($i = 0; $i < LoginLockout::ACCOUNT_MAX; $i++) {
        laTry($this, 'alvo@vivensi.app', 'errada', $i);
    }
    expect(LoginLockout::lockedFor('alvo@vivensi.app', '198.51.100.7'))->toBeGreaterThan(0);

    $token = Password::createToken($user);
    $this->post('/reset-password', [
        'token' => $token, 'email' => 'alvo@vivensi.app',
        'password' => 'NovaSenhaForte2026', 'password_confirmation' => 'NovaSenhaForte2026',
    ])->assertRedirect(route('login'));

    laTry($this, 'alvo@vivensi.app', 'NovaSenhaForte2026', 7)->assertRedirect('/dashboard');
});

function laTotp(TotpService $totp, string $secret): string
{
    return (fn () => $this->hotp($this->base32Decode($secret), (int) floor(time() / 30)))->call($totp);
}

it('código de 2FA certo ainda passa depois de alguns erros', function () {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $user = laUser();
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => []])->save();

    for ($i = 0; $i < 4; $i++) {
        $this->actingAs($user)->post('/2fa/challenge', ['code' => '000000']);
    }
    $this->actingAs($user)->post('/2fa/challenge', ['code' => laTotp($totp, $secret)])->assertRedirect('/dashboard');
    expect(session('2fa_verified'))->toBeTrue();
});

it('código de 2FA: 5 erros travam o usuário mesmo trocando de IP', function () {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $user = laUser();
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => []])->save();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '203.0.113.' . ($i + 1)])
            ->post('/2fa/challenge', ['code' => '000000']);
        expect(session('error'))->toBe('Código inválido. Tente novamente.');
    }

    // Agora nem o código certo passa
    $code = laTotp($totp, $secret);
    $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
        ->post('/2fa/challenge', ['code' => $code]);
    expect(session('error'))->toContain('Muitos códigos incorretos');
    expect(session('2fa_verified'))->toBeNull();
});
