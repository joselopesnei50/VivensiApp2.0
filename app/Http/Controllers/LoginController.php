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

        // O Laravel tenta logar magicamente, mas precisamos explicar que 
        // a senha digitada ('password') deve ser comparada com a coluna 'password_hash' do banco.
        // Felizmente, o model User já está configurado para dizer qual é a senha correta.
        
        // No entanto, por padrão o Auth::attempt espera que a chave do array seja 
        // o nome da coluna no banco, A MENOS que renomeemos.
        // Como nossa coluna é 'password_hash', passamos assim:
        
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            if ($user->role === 'super_admin') {
                return redirect('/admin');
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
