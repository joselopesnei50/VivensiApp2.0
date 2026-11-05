<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use App\Support\TrafficClassifier;
use App\Support\VisitClassifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registra páginas realmente vistas para o Analytics do super admin.
 * Fica de fora: robôs, chamadas automáticas (fetch/JSON, polling),
 * redirecionamentos e erros, prefetch e arquivos estáticos.
 */
class LogPageVisit
{
    private const SKIP_EXTENSIONS = ['.js', '.css', '.png', '.jpg', '.jpeg', '.webp', '.gif', '.ico', '.svg', '.woff', '.woff2', '.map', '.xml', '.txt', '.pdf'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            try {
                $user = Auth::user();
                $path = $request->path();
                $ua   = (string) $request->userAgent();
                $sessionId = $request->hasSession() ? $request->session()->getId() : null;

                PageVisit::create([
                    'tenant_id'  => $user?->tenant_id,
                    'user_id'    => $user?->id,
                    'path'       => VisitClassifier::normalize($path),
                    'area'       => VisitClassifier::area($path, $user?->id, $user?->role === 'super_admin'),
                    'module'     => VisitClassifier::module($path),
                    'visitor'    => $sessionId ? substr(hash('sha256', $sessionId . config('app.key')), 0, 16) : null,
                    'source'     => TrafficClassifier::source($request->headers->get('referer')),
                    'device'     => TrafficClassifier::device($ua),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Analytics nunca derruba a requisição
            }
        }

        return $response;
    }

    private function shouldLog(Request $request, $response): bool
    {
        if (! $request->isMethod('GET')) return false;
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) return false;
        if ($request->headers->get('Purpose') === 'prefetch' || str_contains((string) $request->headers->get('Sec-Purpose'), 'prefetch')) return false;

        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
        if ($status < 200 || $status >= 300) return false;

        // Download de arquivo / resposta que não é página
        $type = (string) $response->headers->get('Content-Type');
        if ($type !== '' && ! str_contains($type, 'text/html')) return false;

        $path = $request->path();
        if (VisitClassifier::isNoise($path)) return false;
        foreach (self::SKIP_EXTENSIONS as $ext) {
            if (str_ends_with($path, $ext)) return false;
        }

        if (TrafficClassifier::isBot($request->userAgent())) return false;

        return true;
    }
}
