<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Suporta coluna role legada E spatie role (HasRoles)
        $isSuperAdmin = $user && (
            $user->role === 'super_admin'
            || $user->hasRole('super_admin')
        );

        if (!$isSuperAdmin) {
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
