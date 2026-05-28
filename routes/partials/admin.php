<?php

// ── Painel Super Admin ────────────────────────────────────────────────────────
Route::middleware(['auth', 'subscription'])->group(function () {
    Route::prefix('admin')->middleware('super_admin')->group(function () {

        Route::get('/',          [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/health',    [App\Http\Controllers\AdminController::class, 'serverHealth'])->name('admin.health');
        Route::get('/live-users', [App\Http\Controllers\AdminController::class, 'liveUsers'])->name('admin.live_users');

        // Tenants
        Route::get('/tenants',             [App\Http\Controllers\AdminController::class, 'tenants'])->name('admin.tenants.index');
        Route::get('/tenants/create',      [App\Http\Controllers\AdminController::class, 'createTenant'])->name('admin.tenants.create');
        Route::post('/tenants',            [App\Http\Controllers\AdminController::class, 'storeTenant'])->name('admin.tenants.store');
        Route::get('/tenants/{id}',        [App\Http\Controllers\AdminController::class, 'showTenant'])->name('admin.tenants.show');
        Route::post('/tenants/{id}/suspend',  [App\Http\Controllers\AdminController::class, 'suspendTenant'])->name('admin.tenants.suspend');
        Route::post('/tenants/{id}/activate', [App\Http\Controllers\AdminController::class, 'activateTenant'])->name('admin.tenants.activate');
        Route::delete('/tenants/{id}',     [App\Http\Controllers\AdminController::class, 'destroyTenant'])->name('admin.tenants.destroy');

        // Academy
        Route::resource('academy', App\Http\Controllers\Admin\AcademyController::class, ['as' => 'admin']);
        Route::get('academy/{course}/modules',      [App\Http\Controllers\Admin\AcademyModuleController::class, 'index'])->name('admin.academy.modules.index');
        Route::post('academy/{course}/modules',     [App\Http\Controllers\Admin\AcademyModuleController::class, 'storeModule'])->name('admin.academy.modules.store');
        Route::put('academy/modules/{id}',          [App\Http\Controllers\Admin\AcademyModuleController::class, 'updateModule'])->name('admin.academy.modules.update');
        Route::delete('academy/modules/{id}',       [App\Http\Controllers\Admin\AcademyModuleController::class, 'destroyModule'])->name('admin.academy.modules.destroy');
        Route::post('academy/modules/{module}/lessons', [App\Http\Controllers\Admin\AcademyModuleController::class, 'storeLesson'])->name('admin.academy.lessons.store');
        Route::delete('academy/lessons/{id}',       [App\Http\Controllers\Admin\AcademyModuleController::class, 'destroyLesson'])->name('admin.academy.lessons.destroy');

        // Equipe interna
        Route::get('/team',       [App\Http\Controllers\Admin\AdminTeamController::class, 'index'])->name('admin.team.index');
        Route::get('/team/{id}',  [App\Http\Controllers\Admin\AdminTeamController::class, 'profile'])->name('admin.team.profile');
        Route::post('/team',      [App\Http\Controllers\Admin\AdminTeamController::class, 'store'])->name('admin.team.store');
        Route::delete('/team/{id}', [App\Http\Controllers\Admin\AdminTeamController::class, 'destroy'])->name('admin.team.destroy');

        // Configurações do sistema
        Route::get('/settings',  [App\Http\Controllers\AdminSettingsController::class, 'index']);
        Route::post('/settings', [App\Http\Controllers\AdminSettingsController::class, 'store'])->middleware('throttle:20,1');

        // Logs & Suporte
        Route::get('/email-logs', [App\Http\Controllers\AdminController::class, 'emailLogs'])->name('admin.email_logs');
        Route::get('/chat',       [App\Http\Controllers\InternalChatController::class, 'index'])->name('admin.chat');
        Route::get('/support',    [App\Http\Controllers\SupportController::class, 'adminIndex'])->name('admin.support.index');

        // Bot de Gestão Interna
        Route::get('/bot',                   [App\Http\Controllers\Admin\BotController::class, 'index'])->name('admin.bot');
        Route::post('/bot/settings',         [App\Http\Controllers\Admin\BotController::class, 'save'])->name('admin.bot.save');
        Route::post('/bot/atendimento',      [App\Http\Controllers\Admin\BotController::class, 'saveAtendimento'])->name('admin.bot.atendimento.save');
        Route::post('/bot/users/{id}/phone', [App\Http\Controllers\Admin\BotController::class, 'updateUserPhone'])->name('admin.bot.user.phone');
        Route::post('/bot/instance/create',  [App\Http\Controllers\Admin\BotController::class, 'createInstance'])->name('admin.bot.instance.create')->middleware('throttle:5,1');
        Route::get('/bot/instance/qr',       [App\Http\Controllers\Admin\BotController::class, 'getQrCode'])->name('admin.bot.instance.qr');
        Route::get('/bot/instance/status',   [App\Http\Controllers\Admin\BotController::class, 'getInstanceStatus'])->name('admin.bot.instance.status');

        // Planos de Assinatura
        Route::resource('/plans', App\Http\Controllers\Admin\SubscriptionPlanController::class)->names([
            'index'   => 'admin.plans.index',
            'create'  => 'admin.plans.create',
            'store'   => 'admin.plans.store',
            'edit'    => 'admin.plans.edit',
            'update'  => 'admin.plans.update',
            'destroy' => 'admin.plans.destroy',
        ]);

        // CMS Blog
        Route::resource('/blog', App\Http\Controllers\Admin\BlogController::class)->names([
            'index'   => 'admin.blog.index',
            'create'  => 'admin.blog.create',
            'store'   => 'admin.blog.store',
            'edit'    => 'admin.blog.edit',
            'update'  => 'admin.blog.update',
            'destroy' => 'admin.blog.destroy',
        ]);

        // CMS Páginas
        Route::resource('/pages', App\Http\Controllers\Admin\PageController::class)->only(['index', 'edit', 'update'])->names([
            'index'  => 'admin.pages.index',
            'edit'   => 'admin.pages.edit',
            'update' => 'admin.pages.update',
        ]);

        // CMS Depoimentos
        Route::resource('/testimonials', App\Http\Controllers\Admin\TestimonialController::class)->names([
            'index'   => 'admin.testimonials.index',
            'create'  => 'admin.testimonials.create',
            'store'   => 'admin.testimonials.store',
            'edit'    => 'admin.testimonials.edit',
            'update'  => 'admin.testimonials.update',
            'destroy' => 'admin.testimonials.destroy',
        ]);

        // Chat interno (API)
        Route::get('/api/chat/messages/{receiverId}', [App\Http\Controllers\InternalChatController::class, 'getMessages']);
        Route::post('/api/chat/send',                 [App\Http\Controllers\InternalChatController::class, 'sendMessage']);

        // LGPD / DPO (Arts. 18–20, 48 — Lei 13.709/2018)
        Route::get('/lgpd',                                  [App\Http\Controllers\Admin\LgpdController::class, 'index'])->name('admin.lgpd.index');
        Route::post('/lgpd/requests/{lgpdRequest}/process',  [App\Http\Controllers\Admin\LgpdController::class, 'processRequest'])->name('admin.lgpd.process');
        Route::post('/lgpd/breaches',                        [App\Http\Controllers\Admin\LgpdController::class, 'storeBreach'])->name('admin.lgpd.breach.store');
        Route::patch('/lgpd/breaches/{breach}/status',       [App\Http\Controllers\Admin\LgpdController::class, 'updateBreachStatus'])->name('admin.lgpd.breach.status');

        // Agenda Executiva
        Route::get('/executive',                     [App\Http\Controllers\Admin\ExecutiveAgendaController::class, 'index'])->name('admin.executive.index');
        Route::post('/executive/tasks',              [App\Http\Controllers\Admin\ExecutiveAgendaController::class, 'storeTask'])->name('admin.executive.task.store');
        Route::patch('/executive/tasks/{task}',      [App\Http\Controllers\Admin\ExecutiveAgendaController::class, 'updateTask'])->name('admin.executive.task.update');
        Route::delete('/executive/tasks/{task}',     [App\Http\Controllers\Admin\ExecutiveAgendaController::class, 'destroyTask'])->name('admin.executive.task.destroy');

        // Agenda de Reuniões
        Route::get('/bookings',                      [App\Http\Controllers\Admin\MeetingBookingController::class, 'index'])->name('admin.bookings.index');
        Route::patch('/bookings/{booking}/status',   [App\Http\Controllers\Admin\MeetingBookingController::class, 'updateStatus'])->name('admin.bookings.status');
        Route::patch('/bookings/{booking}/link',     [App\Http\Controllers\Admin\MeetingBookingController::class, 'updateLink'])->name('admin.bookings.link');

        // Jobs Falhados
        Route::get('/failed-jobs',                       [App\Http\Controllers\Admin\FailedJobsController::class, 'index'])->name('admin.failed-jobs.index');
        Route::post('/failed-jobs/{uuid}/retry',         [App\Http\Controllers\Admin\FailedJobsController::class, 'retry'])->name('admin.failed-jobs.retry');
        Route::post('/failed-jobs/retry-all',            [App\Http\Controllers\Admin\FailedJobsController::class, 'retryAll'])->name('admin.failed-jobs.retry-all');
        Route::delete('/failed-jobs/{uuid}',             [App\Http\Controllers\Admin\FailedJobsController::class, 'destroy'])->name('admin.failed-jobs.destroy');
        Route::delete('/failed-jobs',                    [App\Http\Controllers\Admin\FailedJobsController::class, 'flush'])->name('admin.failed-jobs.flush');

        // Dev Portal (senha separada — middleware dev_auth)
        Route::get('/dev/gate',        [App\Http\Controllers\Admin\DevController::class, 'gate'])->name('admin.dev.gate');
        Route::post('/dev/gate',       [App\Http\Controllers\Admin\DevController::class, 'authenticate'])->name('admin.dev.authenticate')->middleware('throttle:10,1');
        Route::post('/dev/logout',     [App\Http\Controllers\Admin\DevController::class, 'logout'])->name('admin.dev.logout');
        Route::get('/dev',             [App\Http\Controllers\Admin\DevController::class, 'dashboard'])->name('admin.dev.dashboard')->middleware('dev_auth');

        // Audit Agent
        Route::get('/audit',      [App\Http\Controllers\Admin\AuditController::class, 'index'])->name('admin.audit');
        Route::post('/audit/run', [App\Http\Controllers\Admin\AuditController::class, 'runCheck'])->name('admin.audit.run')->middleware('throttle:40,1');

        // E-mail Marketing (Brevo Campaigns)
        Route::get('/email-campaigns',               [App\Http\Controllers\Admin\EmailCampaignController::class, 'index'])->name('admin.email_campaigns.index');
        Route::get('/email-campaigns/create',        [App\Http\Controllers\Admin\EmailCampaignController::class, 'create'])->name('admin.email_campaigns.create');
        Route::post('/email-campaigns',              [App\Http\Controllers\Admin\EmailCampaignController::class, 'store'])->name('admin.email_campaigns.store');
        Route::get('/email-campaigns/{emailCampaign}',        [App\Http\Controllers\Admin\EmailCampaignController::class, 'show'])->name('admin.email_campaigns.show');
        Route::post('/email-campaigns/{emailCampaign}/send',  [App\Http\Controllers\Admin\EmailCampaignController::class, 'send'])->name('admin.email_campaigns.send');
        Route::post('/email-campaigns/{emailCampaign}/stats', [App\Http\Controllers\Admin\EmailCampaignController::class, 'refreshStats'])->name('admin.email_campaigns.stats');
        Route::delete('/email-campaigns/{emailCampaign}',     [App\Http\Controllers\Admin\EmailCampaignController::class, 'destroy'])->name('admin.email_campaigns.destroy');
    });
});
