<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
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

        // Driver 'brevo' pro Laravel Mailer — todo Mail::send / Mailable / Notification
        // que rodar com MAIL_MAILER=brevo vai pela API (SystemSetting.brevo_api_key)
        // em vez de SMTP. Elimina risco de credencial SMTP expirar em prod.
        Mail::extend('brevo', function () {
            return new \App\Mail\Transport\BrevoApiTransport();
        });

        // Force mail.from.address a partir de SystemSetting.email_from ANTES do
        // Symfony rodar ensureValidity(). O BrevoApiTransport injeta o sender
        // do lado dele tambem, mas a validacao do Symfony Email roda ANTES do
        // transport — se mail.from.address estiver vazio ou 'hello@example.com',
        // mailables sem ->from() explicito (ex: StageOverdueAlertMail) explodem
        // com 'An email must have a From or Sender header'.
        try {
            $emailFrom     = \App\Models\SystemSetting::getValue('email_from');
            $emailFromName = \App\Models\SystemSetting::getValue('email_from_name');
            $current       = config('mail.from.address');
            if ($emailFrom) {
                config([
                    'mail.from.address' => $emailFrom,
                    'mail.from.name'    => $emailFromName ?: (config('mail.from.name') ?: 'Vivensi'),
                ]);
            } elseif (!$current || $current === 'hello@example.com') {
                // Ultimo fallback: nunca deixa placeholder Laravel escapar.
                config([
                    'mail.from.address' => 'noreply@vivensi.app.br',
                    'mail.from.name'    => 'Vivensi',
                ]);
            }
        } catch (\Throwable $e) {
            // Boot resiliente: DB pode nao estar disponivel (migrate:fresh, artisan
            // rodando antes de db up). Mantem o que vier do .env nesse caso.
        }

        // Broadcasting dinâmico via SystemSetting — lê valores que o super_admin
        // salvou em /admin/settings e sobrescreve config('broadcasting.connections.pusher.*')
        // em runtime. Sem isso, o .env vence (e fica vazio no VPS), causando
        // erro "https://:443" no PusherBroadcaster.
        try {
            $pusherAppId     = \App\Models\SystemSetting::getValue('pusher_app_id');
            $pusherAppKey    = \App\Models\SystemSetting::getValue('pusher_app_key');
            $pusherAppSecret = \App\Models\SystemSetting::getValue('pusher_app_secret');
            $pusherHost      = \App\Models\SystemSetting::getValue('pusher_host');
            $pusherPort      = \App\Models\SystemSetting::getValue('pusher_port');
            $pusherScheme    = \App\Models\SystemSetting::getValue('pusher_scheme');
            $pusherCluster   = \App\Models\SystemSetting::getValue('pusher_app_cluster')
                            ?: \App\Models\SystemSetting::getValue('pusher_cluster');

            if ($pusherAppId)     config(['broadcasting.connections.pusher.app_id' => $pusherAppId]);
            if ($pusherAppKey)    config(['broadcasting.connections.pusher.key'    => $pusherAppKey]);
            if ($pusherAppSecret) config(['broadcasting.connections.pusher.secret' => $pusherAppSecret]);
            if ($pusherHost)      config(['broadcasting.connections.pusher.options.host' => $pusherHost]);
            if ($pusherPort)      config(['broadcasting.connections.pusher.options.port' => (int) $pusherPort]);
            if ($pusherScheme) {
                $isHttps = $pusherScheme === 'https';
                config([
                    'broadcasting.connections.pusher.options.scheme'    => $pusherScheme,
                    'broadcasting.connections.pusher.options.encrypted' => $isHttps,
                    'broadcasting.connections.pusher.options.useTLS'    => $isHttps,
                ]);
            }
            if ($pusherCluster) config(['broadcasting.connections.pusher.options.cluster' => $pusherCluster]);
        } catch (\Throwable $e) {
            // DB not ready (durante migrations, ou se a tabela system_settings
            // não existir ainda). Mantém fallback do .env.
        }

        // Registrar Observers para Geocodificação Automática
        // Wrapped in try/catch to prevent boot failure if DB is not ready.
        try {
            \App\Models\Beneficiary::observe(\App\Observers\BeneficiaryObserver::class);
            \App\Models\FamilyMember::observe(\App\Observers\FamilyMemberObserver::class);
            \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
            \App\Models\NgoDonor::observe(\App\Observers\NgoDonorObserver::class);
            \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);
            // P1.7 — timeline do lead a partir de mensagens WhatsApp
            \App\Models\WhatsappMessage::observe(\App\Observers\WhatsappMessageObserver::class);
            // Motor de Conformidade
            \App\Models\Attendance::observe(\App\Observers\AttendanceObserver::class);
            \App\Models\Attachment::observe(\App\Observers\AttachmentConformidadeObserver::class);
        } catch (\Throwable $e) {
            // Silently ignore observer registration errors during boot
        }
    }
}
