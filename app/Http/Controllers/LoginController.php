<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    // Rate limit por conta+IP no login — defesa em profundidade além do
    // throttle:10,1 da rota (que só limita por IP e pode ser burlado por
    // atacante distribuído). Após MAX_LOGIN_ATTEMPTS falhas, bloqueia a
    // combinação email+IP por LOGIN_LOCKOUT_SECONDS. Sucesso limpa o contador.
    private const MAX_LOGIN_ATTEMPTS  = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 min

    // Processa o Login
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->loginThrottleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);

            return back()->with(
                'error',
                "Muitas tentativas de login. Tente novamente em {$minutes} minuto(s)."
            )->withInput($request->only('email'));
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            if ($user->isSuperAdmin() || $user->is_platform_team) {
                return redirect('/admin');
            }

            return redirect('/dashboard');
        }

        RateLimiter::hit($throttleKey, self::LOGIN_LOCKOUT_SECONDS);

        return back()->with('error', 'As credenciais fornecidas estão incorretas.')
                     ->withInput($request->only('email'));
    }

    /**
     * Chave de rate limit combinando email normalizado + IP. A combinação
     * limita ataques por conta específica (mesmo com IP variável) e ataques
     * por IP (mesmo com muitas contas), sem depender só do IP como faz o
     * throttle da rota.
     */
    private function loginThrottleKey(Request $request): string
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        return 'login:' . sha1($email) . '|' . $request->ip();
    }
    public function logout(Request $request)
    {
        Auth::logout();
 
        $request->session()->invalidate();
     
        $request->session()->regenerateToken();
     
        return redirect('/login');
    }
}
