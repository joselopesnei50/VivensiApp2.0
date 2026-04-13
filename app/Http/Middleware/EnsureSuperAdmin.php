<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'super_admin') {
            abort(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
