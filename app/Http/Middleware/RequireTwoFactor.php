<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) return $next($request);

        // Only enforce for users who have confirmed 2FA
        if (!$user->hasTwoFactorEnabled()) return $next($request);

        // Already verified in this session
        if ($request->session()->get('2fa_verified')) return $next($request);

        // API requests: require X-2FA-Code header
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'two_factor_required',
                'message' => 'Este endpoint requer verificação 2FA. Inclua o header X-2FA-Code.',
            ], 423);
        }

        // Web: redirect to challenge page
        $request->session()->put('2fa_redirect', $request->url());
        return redirect()->route('2fa.challenge');
    }
}
