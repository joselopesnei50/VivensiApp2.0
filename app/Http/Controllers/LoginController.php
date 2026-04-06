<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Processa o Login
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            // Super Admin vai para painel administrativo
            if ($user->role === 'super_admin') {
                return redirect('/admin');
            }

            // Redirecionar baseado no tipo de tenant
            if ($user->tenant) {
                switch ($user->tenant->type) {
                    case 'manager':
                        return redirect('/dashboard'); // Painel de Gestor de Projetos
                    case 'ngo':
                        return redirect('/dashboard'); // Painel de ONG
                    case 'common':
                        return redirect('/dashboard'); // Painel de Pessoa Comum
                    default:
                        return redirect('/dashboard');
                }
            }

            return redirect('/dashboard');
        }

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
