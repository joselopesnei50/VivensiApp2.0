<?php

// ── Dashboard & Onboarding ────────────────────────────────────────────────────
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'subscription'])->name('dashboard');
Route::post('/onboarding/complete/{step_id}', [App\Http\Controllers\DashboardController::class, 'completeOnboardingStep'])->middleware('auth')->name('onboarding.complete');
Route::get('/export/csv', [App\Http\Controllers\ExportController::class, 'csv'])->middleware('auth')->name('export.csv');

Route::middleware(['auth', 'subscription'])->group(function () {

    // ── Manager ───────────────────────────────────────────────────────────────
    Route::get('/manager/team',            [App\Http\Controllers\ManagerController::class, 'team'])->name('manager.team');
    Route::get('/manager/team/{id}',       [App\Http\Controllers\ManagerController::class, 'teamDetail'])->name('manager.team_detail');
    Route::post('/manager/team/store-quick', [App\Http\Controllers\ManagerController::class, 'storeQuick']);
    Route::get('/manager/approvals',       [App\Http\Controllers\ManagerController::class, 'approvals'])->name('manager.approvals');
    Route::get('/manager/contracts',       [App\Http\Controllers\ManagerController::class, 'contracts'])->name('manager.contracts');
    Route::get('/manager/landing-pages',   [App\Http\Controllers\LandingPageController::class, 'index'])->name('manager.landing_pages');
    Route::get('/manager/reconciliation',  [App\Http\Controllers\ManagerController::class, 'reconciliation'])->name('manager.reconciliation');
    Route::get('/manager/schedule',        [App\Http\Controllers\ManagerController::class, 'schedule'])->name('manager.schedule');

    // ── Marketing & Prospecção ────────────────────────────────────────────────
    Route::middleware('can:access-manager')->group(function () {
        Route::get('/marketing',                    [App\Http\Controllers\MarketingStrategyController::class, 'index'])->name('marketing.index');
        Route::get('/marketing/create',             [App\Http\Controllers\MarketingStrategyController::class, 'create'])->name('marketing.create');
        Route::post('/marketing',                   [App\Http\Controllers\MarketingStrategyController::class, 'store'])->name('marketing.store');
        Route::get('/marketing/{marketing}',        [App\Http\Controllers\MarketingStrategyController::class, 'show'])->name('marketing.show');
        Route::get('/marketing/{marketing}/status', [App\Http\Controllers\MarketingStrategyController::class, 'status'])->name('marketing.status');
        Route::delete('/marketing/{marketing}',     [App\Http\Controllers\MarketingStrategyController::class, 'destroy'])->name('marketing.destroy');

        Route::get('/prospecting',                  [App\Http\Controllers\ProspectingController::class, 'index'])->name('prospecting.index');
        Route::post('/prospecting/search',          [App\Http\Controllers\ProspectingController::class, 'search'])->name('prospecting.search');
        Route::post('/prospecting/analyze-all',     [App\Http\Controllers\ProspectingController::class, 'analyzeAll'])->name('prospecting.analyze-all');
        Route::post('/prospecting/broadcast',       [App\Http\Controllers\ProspectingController::class, 'broadcastWhatsapp'])->name('prospecting.broadcast');
        Route::delete('/prospecting/bulk-delete',   [App\Http\Controllers\ProspectingController::class, 'bulkDestroy'])->name('prospecting.bulk-delete');
        Route::post('/prospecting/{id}/analyze',    [App\Http\Controllers\ProspectingController::class, 'analyze'])->name('prospecting.analyze');
        Route::post('/prospecting/{id}/convert',    [App\Http\Controllers\ProspectingController::class, 'convertToDeal'])->name('prospecting.convert');
        Route::delete('/prospecting/{id}',          [App\Http\Controllers\ProspectingController::class, 'destroy'])->name('prospecting.destroy');
    });

    // ── Notificações ──────────────────────────────────────────────────────────
    Route::get('/notifications',                  [App\Http\Controllers\NotificationController::class, 'page'])->name('notifications.index');
    Route::post('/notifications/{id}/read',       [App\Http\Controllers\NotificationController::class, 'markAsReadWeb'])->name('notifications.read');
    Route::post('/notifications/read-all',        [App\Http\Controllers\NotificationController::class, 'markAllAsReadWeb'])->name('notifications.read_all');
    Route::get('/api/notifications',              [App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::post('/api/notifications/{id}/read',   [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all',    [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // ── Bruce AI (chat financeiro) ────────────────────────────────────────────
    Route::post('/api/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage']);

    // ── Bruce AI Contextual (DeepSeek + memória Redis) ───────────────────────
    Route::post('/api/bruce/chat',           [App\Http\Controllers\Api\BruceAiController::class, 'chat'])->middleware('throttle:30,1');
    Route::delete('/api/bruce/chat/history', [App\Http\Controllers\Api\BruceAiController::class, 'clearHistory']);
    Route::get('/api/bruce/insight',         [App\Http\Controllers\Api\BruceAiController::class, 'insight'])->middleware('throttle:10,1');

    // ── Locale switcher (Phase 6) ─────────────────────────────────────────────
    Route::post('/locale/{code}', [App\Http\Controllers\LocaleController::class, 'set'])->name('locale.set');

    // ── Smart Analysis ────────────────────────────────────────────────────────
    Route::get('/smart-analysis',       [App\Http\Controllers\SmartAnalysisController::class, 'index']);
    Route::post('/smart-analysis/deep', [App\Http\Controllers\SmartAnalysisController::class, 'generateDeepAnalysis']);

    // ── Perfil do Usuário ─────────────────────────────────────────────────────
    Route::get('/profile',           [App\Http\Controllers\ProfileController::class, 'edit']);
    Route::post('/profile/update',   [App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword']);
    Route::get('/profile/export',    [App\Http\Controllers\ProfileController::class, 'exportData'])->name('profile.export')->middleware('throttle:3,60');
    Route::post('/profile/delete',   [App\Http\Controllers\ProfileController::class, 'requestDelete'])->name('profile.delete')->middleware('throttle:2,60');

    // ── Suporte ───────────────────────────────────────────────────────────────
    Route::prefix('support')->group(function () {
        Route::get('/',           [App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
        Route::get('/create',     [App\Http\Controllers\SupportController::class, 'create'])->name('support.create');
        Route::post('/',          [App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
        Route::get('/{id}',       [App\Http\Controllers\SupportController::class, 'show'])->name('support.show');
        Route::post('/{id}/reply', [App\Http\Controllers\SupportController::class, 'reply'])->name('support.reply');
    });

    // ── Rifas (gestão interna) ────────────────────────────────────────────────
    Route::prefix('raffles')->name('raffles.')->group(function () {
        Route::get('/',                         [App\Http\Controllers\RaffleController::class, 'index'])->name('index');
        Route::get('/create',                   [App\Http\Controllers\RaffleController::class, 'create'])->name('create');
        Route::post('/',                        [App\Http\Controllers\RaffleController::class, 'store'])->name('store');
        Route::get('/{raffle}',                 [App\Http\Controllers\RaffleController::class, 'show'])->name('show');
        Route::post('/ticket/{ticket}/confirm', [App\Http\Controllers\RaffleController::class, 'confirmPayment'])->name('confirm-payment');
        Route::post('/ticket/{ticket}/release', [App\Http\Controllers\RaffleController::class, 'releaseTicket'])->name('release-ticket');
        Route::post('/{raffle}/draw',           [App\Http\Controllers\RaffleController::class, 'draw'])->name('draw');
        Route::delete('/{raffle}/delete',       [App\Http\Controllers\RaffleController::class, 'destroy'])->name('destroy');
    });

    // ── Personalização da Marca (White-Label) ─────────────────────────────────
    Route::get('/settings/branding',        [App\Http\Controllers\TenantBrandingController::class, 'index'])->name('settings.branding');
    Route::post('/settings/branding',       [App\Http\Controllers\TenantBrandingController::class, 'update'])->name('settings.branding.update');
    Route::delete('/settings/branding/logo', [App\Http\Controllers\TenantBrandingController::class, 'removeLogo'])->name('settings.branding.remove-logo');

    // ── API Tokens (Public API v1) ────────────────────────────────────────────
    Route::get('/settings/api-tokens',          [App\Http\Controllers\ApiTokenController::class, 'index'])->name('settings.api-tokens');
    Route::post('/settings/api-tokens',         [App\Http\Controllers\ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
    Route::delete('/settings/api-tokens/{id}',  [App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');

    // ── Webhooks ──────────────────────────────────────────────────────────────
    Route::get('/settings/webhooks',              [App\Http\Controllers\WebhookSettingsController::class, 'index'])->name('settings.webhooks');
    Route::post('/settings/webhooks',             [App\Http\Controllers\WebhookSettingsController::class, 'store'])->name('settings.webhooks.store');
    Route::post('/settings/webhooks/{id}/toggle', [App\Http\Controllers\WebhookSettingsController::class, 'toggle'])->name('settings.webhooks.toggle');
    Route::delete('/settings/webhooks/{id}',      [App\Http\Controllers\WebhookSettingsController::class, 'destroy'])->name('settings.webhooks.destroy');
    Route::get('/settings/webhooks/{id}/logs',    [App\Http\Controllers\WebhookSettingsController::class, 'logs'])->name('settings.webhooks.logs');

    // ── Busca Global ──────────────────────────────────────────────────────────
    Route::get('/search', [App\Http\Controllers\GlobalSearchController::class, 'search'])->name('search.global');

    // ── Magic Landing Page ────────────────────────────────────────────────────
    Route::post('/marketing/magic-page', [App\Http\Controllers\LandingPageController::class, 'createMagic'])->name('ngo.landing-pages.create_magic');
});

// ── Academy (LMS) — auth sem subscription ────────────────────────────────────
Route::group(['prefix' => 'academy', 'as' => 'academy.', 'middleware' => ['auth']], function () {
    Route::get('/',                    [App\Http\Controllers\AcademyController::class, 'index'])->name('index');
    Route::get('/{slug}',              [App\Http\Controllers\AcademyController::class, 'show'])->name('show');
    Route::post('/lessons/{id}/complete', [App\Http\Controllers\AcademyController::class, 'markLessonAsViewed'])->name('lesson.complete');
    Route::get('/certificate/{code}',  [App\Http\Controllers\AcademyController::class, 'downloadCertificate'])->name('certificate.download');
});

// ── Testes de Integração (apenas auth) ───────────────────────────────────────
Route::prefix('test-api')->middleware(['auth'])->group(function () {
    Route::get('/pagseguro', [App\Http\Controllers\IntegrationTestController::class, 'testPagSeguro']);
    Route::get('/gemini',    [App\Http\Controllers\IntegrationTestController::class, 'testGemini']);
    Route::get('/deepseek',  [App\Http\Controllers\IntegrationTestController::class, 'testDeepSeek']);
});
