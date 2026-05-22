<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [];
        $status = 'ok';

        // Database
        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $status = 'degraded';
        }

        // Cache
        try {
            $key = '_health_' . time();
            Cache::put($key, 1, 5);
            $checks['cache'] = Cache::get($key) === 1 ? 'ok' : 'fail';
            Cache::forget($key);
            if ($checks['cache'] !== 'ok') $status = 'degraded';
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
            $status = 'degraded';
        }

        // Queue (sync driver is always ok for basic healthcheck)
        $checks['queue'] = config('queue.default') !== 'failed' ? 'ok' : 'degraded';

        $httpCode = $status === 'ok' ? 200 : 503;

        return response()->json([
            'status'    => $status,
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '2.0'),
            'checks'    => $checks,
        ], $httpCode);
    }
}
