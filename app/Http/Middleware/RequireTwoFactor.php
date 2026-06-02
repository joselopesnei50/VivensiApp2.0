<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactor
{
    // Routes excluded from 2FA enforcement to prevent redirect loops
    private const EXEMPT_ROUTES = [
        '2fa.challenge', '2fa.verify',
        '2fa.show', '2fa.enable', '2fa.confirm', '2fa.disable',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) return $next($request);

        // Never block 2FA setup/challenge routes
        if (in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        // Super admins must configure 2FA before accessing any protected route
        if ($user->isSuperAdmin() && !$user->hasTwoFactorEnabled()) {
            return redirect()->route('2fa.show')
                ->with('warning', 'Por segurança, administradores precisam ativar o 2FA antes de continuar.');
        }

        // For users with 2FA configured: enforce session verification
        if (!$user->hasTwoFactorEnabled()) return $next($request);

        if ($request->session()->get('2fa_verified')) return $next($request);

        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'two_factor_required',
                'message' => 'Este endpoint requer verificação 2FA. Inclua o header X-2FA-Code.',
            ], 423);
        }

        $request->session()->put('2fa_redirect', $request->url());
        return redirect()->route('2fa.challenge');
    }
}
