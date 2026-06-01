<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogPageVisit
{
    private const SKIP_PREFIXES = ['api/', '_', 'livewire/', 'admin/api/'];
    private const SKIP_EXTENSIONS = ['.js', '.css', '.png', '.jpg', '.ico', '.svg', '.woff', '.map'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            try {
                $user = Auth::user();
                PageVisit::create([
                    'tenant_id'  => $user?->tenant_id,
                    'user_id'    => $user?->id,
                    'path'       => substr($request->path(), 0, 500),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Tabela não existe ainda — não interrompe a requisição
            }
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (!$request->isMethod('GET')) return false;
        if ($request->ajax()) return false;

        $path = $request->path();

        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) return false;
        }

        foreach (self::SKIP_EXTENSIONS as $ext) {
            if (str_ends_with($path, $ext)) return false;
        }

        return true;
    }
}
