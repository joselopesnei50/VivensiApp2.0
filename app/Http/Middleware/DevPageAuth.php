<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DevPageAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!session('dev_authenticated')) {
            return redirect()->route('admin.dev.gate');
        }

        $authenticatedAt = session('dev_authenticated_at');
        if (!$authenticatedAt || now()->diffInMinutes($authenticatedAt) > 30) {
            session()->forget(['dev_authenticated', 'dev_authenticated_at']);
            return redirect()->route('admin.dev.gate')->with('error', 'Sessão expirada. Autentique-se novamente.');
        }

        return $next($request);
    }
}
