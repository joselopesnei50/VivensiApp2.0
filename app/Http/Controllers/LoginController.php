<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\LoginLockout;

class LoginController extends Controller
{
    // Limites de tentativa (conta+IP e só conta): ver App\Support\LoginLockout.

    // Processa o Login
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = (string) $request->input('email');

        if ($seconds = LoginLockout::lockedFor($email, $request->ip())) {
            $minutes = (int) ceil($seconds / 60);

            return back()->with(
                'error',
                "Muitas tentativas de login. Tente novamente em {$minutes} minuto(s) ou redefina sua senha em \"Esqueceu?\"."
            )->withInput($request->only('email'));
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            LoginLockout::succeeded($email, $request->ip());

            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            if ($user->isSuperAdmin() || $user->is_platform_team) {
                return redirect('/admin');
            }

            // Credenciado nao tem acesso ao dashboard nem a nenhum outro painel;
            // vai direto pro painel proprio /credenciado (que auto-redireciona
            // ao workspace se so ha 1 projeto vinculado).
            if ($user->isCredenciado()) {
                return redirect('/credenciado');
            }

            return redirect('/dashboard');
        }

        LoginLockout::failed($email, $request->ip());

        return back()->with('error', 'As credenciais fornecidas estão incorretas.')
                     ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
 
        $request->session()->invalidate();
     
        $request->session()->regenerateToken();
     
        return redirect('/login');
    }
}
