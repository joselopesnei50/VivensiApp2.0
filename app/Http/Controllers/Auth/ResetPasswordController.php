<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token'  => $token,
            'email'  => (string) $request->query('email', ''),
            'invite' => $request->boolean('convite'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()],
        ]);

        // Convite da equipe usa o broker "invites" (link de 72 h); o resto, a redefinição normal.
        $invite = $request->boolean('invite');
        $status = Password::broker($invite ? 'invites' : null)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Quem redefiniu pelo link do e-mail é o dono: destrava a conta.
            \App\Support\LoginLockout::reset((string) $request->input('email'), $request->ip());

            return redirect()->route('login')->with('success', $invite
                ? 'Senha criada! Agora é só entrar com o seu e-mail e a nova senha.'
                : 'Senha redefinida com sucesso. Você já pode entrar.');
        }

        return back()
            ->withErrors(['email' => $invite
                ? 'Este convite expirou ou já foi usado. Peça ao administrador da organização para enviar um novo.'
                : 'Não foi possível redefinir sua senha. Solicite um novo link.'])
            ->withInput($request->only('email'));
    }
}

