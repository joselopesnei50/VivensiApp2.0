<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\LandingPage;
use Illuminate\Http\Request;

class CheckLandingPageLimit
{
    public function handle(Request $request, Closure $next)
    {
        $tenant_id = auth()->user()->tenant_id;
        $count = LandingPage::where('tenant_id', $tenant_id)->count();

        if ($count >= 5) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Limite atingido. Entre em contato com o suporte para contratar novas páginas.'], 403);
            }
            return redirect()->back()->with('error', 'Limite atingido. Entre em contato com o suporte para contratar novas páginas.');
        }

        return $next($request);
    }
}
