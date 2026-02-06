<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Log slow requests (> 2 seconds)
        if ($executionTime > 2000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_mb' => round($memoryUsed, 2),
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()?->tenant_id,
            ]);

            // Send to Sentry for monitoring
            if (app()->bound('sentry')) {
                \Sentry\captureMessage('Slow Request: ' . $request->path(), [
                    'level' => \Sentry\Severity::warning(),
                    'extra' => [
                        'execution_time_ms' => round($executionTime, 2),
                        'memory_mb' => round($memoryUsed, 2),
                        'url' => $request->fullUrl(),
                    ],
                ]);
            }
        }

        // Add performance headers (only in local/development)
        if (app()->environment('local')) {
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', round($memoryUsed, 2) . 'MB');
        }

        return $response;
    }
}
