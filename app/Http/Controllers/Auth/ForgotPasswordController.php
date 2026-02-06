<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // This will call User::sendPasswordResetNotification($token) when user exists.
        $status = Password::sendResetLink($request->only('email'));

        // Security alert for the account owner (shown in the in-app bell when they log in).
        // Does not leak information to the requester because the UI message is always generic.
        if ($status === Password::RESET_LINK_SENT) {
            try {
                $user = User::where('email', $request->input('email'))->first();
                if ($user) {
                    Notification::create([
                        'user_id' => $user->id,
                        'title' => 'Solicitação de redefinição de senha',
                        'message' => 'Recebemos uma solicitação para redefinir sua senha. Se não foi você, recomendamos trocar sua senha assim que possível.',
                        'type' => 'warning',
                        'link' => null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Keep stable.
            }
        }

        // Avoid user enumeration: always show a generic success message.
        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir sua senha.');
    }
}

