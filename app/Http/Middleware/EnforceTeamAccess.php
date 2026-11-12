<?php

namespace App\Http\Middleware;

use App\Support\TeamAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 1) Membro suspenso não usa o sistema: a sessão é encerrada na hora
 *    (antes, suspender alguém não tinha efeito nenhum).
 * 2) Aplica a hierarquia da equipe da ONG (App\Support\TeamAccess).
 */
class EnforceTeamAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        if ($user->status === 'suspended') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Acesso suspenso.'], 403)
                : redirect()->route('login')->with('error', 'Seu acesso foi suspenso pelo administrador da organização.');
        }

        // Primeiro acesso com senha definida pelo administrador: troca obrigatória.
        if ($user->must_change_password && !$request->is('profile', 'profile/password', 'logout', 'two-factor*', 'profile/2fa*')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Troque a senha no seu perfil antes de continuar.'], 403);
            }
            return redirect('/profile')->with('error', 'Bem-vindo(a)! Por segurança, crie uma senha só sua antes de continuar.');
        }

        if (!TeamAccess::allowsPath($user, $request->path())) {
            if ($request->expectsJson() || !$request->isMethod('GET')) {
                abort(403, 'Seu perfil não tem acesso a esta área.');
            }
            return redirect('/dashboard')->with('error', 'Seu perfil (' . TeamAccess::label($user->role) . ') não tem acesso a esta área. Se precisar, fale com o administrador da organização.');
        }

        return $next($request);
    }
}
