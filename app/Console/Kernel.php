<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Trials: lembrete de vencimento — todo dia às 09:00
        $schedule->command('trials:remind')->dailyAt('09:00');

        // ProjectStage: digest diario de etapas atrasadas — 08:00
        $schedule->command('stages:overdue-alert')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // WhatsApp Automações: processa regras de reativação todos os dias às 10:00
        $schedule->job(new \App\Jobs\ProcessWhatsappAutomations())
                 ->dailyAt('10:00')
                 ->withoutOverlapping();

        // WhatsApp: limpeza de dados antigos — todo dia às 03:30
        $days = (int) config('whatsapp.retention_days', 365);
        if ($days > 0) {
            $schedule->command("whatsapp:cleanup --days={$days}")->dailyAt('03:30');
        }

        // Redes Sociais: publica posts agendados a cada 5 minutos
        $schedule->command('posts:publish')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // WhatsApp: envia mensagens agendadas a cada minuto (ex: lembretes, follow-ups)
        $schedule->command('whatsapp:send-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Broadcast: despacha campanhas agendadas no horário programado
        $schedule->command('broadcast:process-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // AbacatePay: reconcilia Transactions pending caso o webhook tenha sido
        // perdido (worker fora do ar, rede ruim). Roda a cada 5 min, varre as
        // que tem > 5 min de idade. Idempotente via processed_webhooks.
        $schedule->command('abacatepay:reconcile --minutes-old=5')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Relatório Semanal com Bruce AI — todo domingo às 08:00
        $schedule->job(new \App\Jobs\SendWeeklyReportJob())
                 ->weeklyOn(0, '08:00')
                 ->withoutOverlapping()
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('SendWeeklyReportJob falhou.');
                 });

        // [AUDIT A01 - CRÍTICO] Limpeza de reservas expiradas de rifas.
        // Sem este agendamento, bilhetes reservados e não pagos ficavam
        // bloqueados permanentemente, impedindo novos compradores.
        $schedule->command('raffles:cleanup-reservations')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();

        // Backup automático do banco — todo dia às 02:00
        $schedule->command('db:backup')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('✅ Backup diário concluído.');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('❌ Falha no backup diário do banco!');
                 });

        // Health-check: confirma que o scheduler está rodando a cada hora
        $schedule->call(function () {
            \Illuminate\Support\Facades\Log::info('🟢 Scheduler alive — ' . now()->toDateTimeString());
        })->hourly()->name('scheduler:health-check')->withoutOverlapping();

        // Conformidade: alerta WhatsApp quando índice < 50% — todo dia às 08:30
        $schedule->command('conformidade:alert-critical')
                 ->dailyAt('08:30')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Editais: alerta de deadline próximo — todo dia às 08:00
        $schedule->command('grants:deadline-alert')
                 ->dailyAt('08:00')
                 ->withoutOverlapping();

        // CRM Doadores: régua de reativação — toda segunda-feira às 10:30
        // Envia mensagem de WhatsApp para doadores que não doam há 60+ dias
        $schedule->command('donors:send-reactivation --days=60')
                 ->weeklyOn(1, '10:30')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('✅ Régua de reativação de doadores concluída.');
                 })
                 ->onFailure(function () {
                     \Illuminate\Support\Facades\Log::error('❌ Falha na régua de reativação de doadores.');
                 });

        // Alertas de Projetos: prazo e orçamento — todo dia às 08:15
        $schedule->command('projects:deadline-alerts')
                 ->dailyAt('08:15')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Illuminate\Support\Facades\Log::info('✅ Alertas de projetos enviados.');
                 });

        // Financeiro: revoga tokens de recibos públicos expirados — todo dia às 04:00
        $schedule->command('receipts:cleanup-expired-tokens')
                 ->dailyAt('04:00')
                 ->withoutOverlapping();

        // IBGE: re-sincroniza indicadores de municípios já pesquisados (toda madrugada às 02:30)
        $schedule->command('vivensi:sync-ibge')
                 ->dailyAt('02:30')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Cache warm: pré-aquece caches de dashboard e Bruce AI para tenants ativos
        $schedule->command('vivensi:cache-warm')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Queue: alerta de jobs falhados a cada 15 minutos
        $schedule->command('queue:alert-failed')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();

        // Sala de Estratégia: snapshot de health + trigger automático por queda de score
        // No-op se STRATEGY_ROOM_AUTO_TRIGGER=false (default). Roda depois dos
        // alertas de projeto (08:15) do dia anterior, antes do expediente.
        $schedule->command('strategy:auto-trigger')
                 ->dailyAt('07:30')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Sala de Estratégia: trigger automático por doador recorrente em declínio
        // No-op se STRATEGY_ROOM_AUTO_TRIGGER_DONOR=false (default).
        $schedule->command('strategy:donor-decline-trigger')
                 ->dailyAt('07:45')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Anti-Ban 2026 (Fase 3): calcula taxa de resposta 7d por instância
        // ativa e emite Log::critical se ficar abaixo do limiar saudável (5%).
        // Semanal aos domingos 03:00 — período de baixíssima carga.
        $schedule->command('antiban:compute-response-rates')
                 ->weeklyOn(0, '03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // LGPD art. 15: executa deletions cujo grace period de 30d expirou.
        // 03:00 UTC diario — baixa carga; anonimiza usuarios em transacao.
        $schedule->command('lgpd:purge-scheduled-deletions')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Conformidade: alertas de documentos vencendo — todo dia às 07:00
        // Jobs já são enfileirados — runInBackground() inválido em CallbackEvent (Laravel 9)
        $schedule->job(new \App\Jobs\AlertaDocumentoVencendoJob())
                 ->dailyAt('07:00')
                 ->withoutOverlapping();

        // Conformidade: snapshot semanal de todos os tenants ativos (domingo 03:30)
        $schedule->job(new \App\Jobs\SnapshotConformidadeJob())
                 ->weeklyOn(0, '03:30')
                 ->withoutOverlapping();

        // Radar de Editais: coleta diária às 06:00 (somente se RADAR_ENABLED=true)
        $schedule->command('radar:collect')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->when(fn() => config('radar.enabled'));

        // Radar de Editais: gera matches para todos os tenants após a coleta (06:30)
        $schedule->job(new \App\Jobs\Radar\GenerateMatches())
                 ->dailyAt('06:30')
                 ->withoutOverlapping()
                 ->when(fn() => config('radar.enabled'));

        // Radar de Editais: digest semanal (segunda-feira às 08:00)
        $schedule->job(new \App\Jobs\Radar\SendRadarDigest())
                 ->weeklyOn(1, '08:00')
                 ->withoutOverlapping()
                 ->when(fn() => config('radar.enabled'));

        // Rotação de logs: truncar laravel.log quando passar de 50MB (evita disco cheio)
        $schedule->call(function () {
            $log = storage_path('logs/laravel.log');
            if (file_exists($log) && filesize($log) > 50 * 1024 * 1024) {
                file_put_contents($log, ''); // Zera o arquivo mantendo-o
                \Illuminate\Support\Facades\Log::info('🗑️ laravel.log foi rotacionado (>50MB).');
            }
        })->hourly()->name('logs:rotate')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     * IMPORTANT: $this->load() já carrega todos os comandos de /Commands automaticamente.
     * NÃO chamar $this->commands([...]) aqui — causa recursão infinita!
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
