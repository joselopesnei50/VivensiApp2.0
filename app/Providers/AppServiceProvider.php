<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // NOTE: The OpenPix singleton is registered as a lazy binding.
        // The DB query (SystemSetting::getValue) only runs when the service
        // is first resolved — NOT during bootstrap. This prevents Artisan
        // from hanging at boot time when the DB is not yet available.
        $this->app->singleton(\OpenPix\PhpSdk\Client::class, function ($app) {
            try {
                // This DB query is intentionally deferred until first use.
                $appId = \App\Models\SystemSetting::getValue('openpix_app_id');
                return \OpenPix\PhpSdk\Client::create($appId ?? '');
            } catch (\Throwable $e) {
                return \OpenPix\PhpSdk\Client::create('');
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Configuração Dinâmica de Broadcasting (Pusher/Soketi)
        // Broadcasting dinâmico via SystemSetting — só ativa após migrations
        // Configurar manualmente via config/broadcasting.php ou painel admin

        // Registrar Observers para Geocodificação Automática
        // Wrapped in try/catch to prevent boot failure if DB is not ready.
        try {
            \App\Models\Beneficiary::observe(\App\Observers\BeneficiaryObserver::class);
            \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        } catch (\Throwable $e) {
            // Silently ignore observer registration errors during boot
        }
    }
}
