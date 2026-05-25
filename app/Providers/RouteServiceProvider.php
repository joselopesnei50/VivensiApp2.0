<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Per-token limit: each Evolution API instance has its own 300 req/min bucket
        RateLimiter::for('evo_webhook', function (Request $request) {
            return Limit::perMinute(300)->by($request->route('token'));
        });

        // Web authenticated routes — keyed by user ID (not IP, to avoid shared-IP false positives)
        RateLimiter::for('web_write', function (Request $request) {
            return Limit::perMinute(60)->by('write|' . ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('web_ai', function (Request $request) {
            return Limit::perMinute(10)->by('ai|' . ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('web_ai_bulk', function (Request $request) {
            return Limit::perMinute(3)->by('ai_bulk|' . ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('web_export', function (Request $request) {
            return Limit::perMinute(15)->by('export|' . ($request->user()?->id ?: $request->ip()));
        });
    }
}
