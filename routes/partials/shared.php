<?php

// ── Dashboard & Onboarding ────────────────────────────────────────────────────
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'subscription'])->name('dashboard');
Route::post('/onboarding/complete/{step_id}', [App\Http\Controllers\DashboardController::class, 'completeOnboardingStep'])->middleware('auth')->name('onboarding.complete');
Route::get('/export/csv', [App\Http\Controllers\ExportController::class, 'csv'])->middleware(['auth', 'throttle:web_export'])->name('export.csv');

// ── PDF Reports (async) ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/reports/{id}/status',   [App\Http\Controllers\GeneratedReportController::class, 'status'])->name('reports.status');
    Route::get('/reports/{id}/download', [App\Http\Controllers\GeneratedReportController::class, 'download'])->name('reports.download');
});

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

    // Perfil Operacional do tenant (Fase 1 — Etapa B)
    Route::get( '/manager/perfil-operacional', [App\Http\Controllers\Manager\PerfilOperacionalController::class, 'edit'])
        ->middleware('can:access-manager')->name('manager.perfil_operacional.edit');
    Route::post('/manager/perfil-operacional', [App\Http\Controllers\Manager\PerfilOperacionalController::class, 'update'])
        ->middleware('can:access-manager')->name('manager.perfil_operacional.update');

    // Kanban Geral (Fase 3 — item 2.2)
    Route::middleware('can:access-manager')->prefix('manager/kanban')->name('manager.kanban.')->group(function () {
        Route::get(   '/',                              [App\Http\Controllers\Manager\KanbanController::class, 'index'])->name('index');
        Route::post(  '/boards/{board}/columns',        [App\Http\Controllers\Manager\KanbanController::class, 'storeColumn'])->name('columns.store')->middleware('throttle:30,1');
        Route::delete('/columns/{column}',              [App\Http\Controllers\Manager\KanbanController::class, 'destroyColumn'])->name('columns.destroy')->middleware('throttle:30,1');
        Route::post(  '/columns/{column}/cards',        [App\Http\Controllers\Manager\KanbanController::class, 'storeCard'])->name('cards.store')->middleware('throttle:60,1');
        Route::patch( '/cards/{card}/move',             [App\Http\Controllers\Manager\KanbanController::class, 'moveCard'])->name('cards.move')->middleware('throttle:120,1');
        Route::patch( '/cards/{card}',                  [App\Http\Controllers\Manager\KanbanController::class, 'updateCard'])->name('cards.update')->middleware('throttle:60,1');
        Route::post(  '/cards/{card}/archive',          [App\Http\Controllers\Manager\KanbanController::class, 'archiveCard'])->name('cards.archive')->middleware('throttle:30,1');

        // Integração OmniChannel WhatsApp → Kanban (Fase 3 — 2.2 / etapa B.3)
        Route::get(   '/columns-list',                      [App\Http\Controllers\Manager\KanbanController::class, 'columnsList'])->name('columns.list');
        Route::post(  '/cards/from-whatsapp/{chat}',        [App\Http\Controllers\Manager\KanbanController::class, 'storeCardFromWhatsapp'])->name('cards.from_whatsapp')->middleware('throttle:60,1');
    });

    // E-mail Marketing (CRM) — exige access-manager (bloqueia role=employee).
    // O check por tenant_id (authorizeForTenant no controller) permanece como
    // defesa em profundidade, mas o gate impede que subordinados disparem
    // campanhas ou consumam quota de email do tenant.
    Route::middleware('can:access-manager')->group(function () {
        Route::get('/manager/email-campaigns',                        [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'index'])->name('manager.email_campaigns.index');
        Route::get('/manager/email-campaigns/create',                 [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'create'])->name('manager.email_campaigns.create');
        Route::post('/manager/email-campaigns',                       [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'store'])->name('manager.email_campaigns.store');
        Route::get('/manager/email-campaigns/{emailCampaign}',        [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'show'])->name('manager.email_campaigns.show');
        Route::post('/manager/email-campaigns/{emailCampaign}/send',  [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'send'])->name('manager.email_campaigns.send');
        Route::post('/manager/email-campaigns/{emailCampaign}/stats', [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'refreshStats'])->name('manager.email_campaigns.stats');
        Route::delete('/manager/email-campaigns/{emailCampaign}',     [App\Http\Controllers\Manager\ManagerEmailCampaignController::class, 'destroy'])->name('manager.email_campaigns.destroy');
    });

    // ── E-mail Marketing (IA) — geracao de template via DeepSeek ─────────────
    // Disponivel para Manager E NGO (ambos criam campanhas de e-mail). Cota
    // mensal por tenant (10/mes default) em EmailAiTemplateQuotaService.
    // Throttle web_ai (10/min por usuario) protege contra loop no cliente.
    // Gate access-manager (bloqueia role=employee) alinha com a politica ja
    // aplicada as rotas /manager/email-campaigns e /ngo/email-campaigns —
    // subordinados nao podem queimar cota de IA do tenant sem autorizacao.
    Route::middleware('can:access-manager')->group(function () {
        Route::get( '/email-campaigns/ai/quota',    [App\Http\Controllers\EmailAiGenerationController::class, 'quota'])->name('email_campaigns.ai.quota');
        Route::post('/email-campaigns/ai/generate', [App\Http\Controllers\EmailAiGenerationController::class, 'generate'])->middleware('throttle:web_ai')->name('email_campaigns.ai.generate');
    });

    // ── Radar de Editais (Manager) ────────────────────────────────────────────
    Route::get('/manager/radar', [App\Http\Controllers\Manager\RadarManagerController::class, 'index'])
         ->middleware('can:access-manager')
         ->name('manager.radar.index');

    // ── Marketing & Prospecção ────────────────────────────────────────────────
    Route::middleware('can:access-manager')->group(function () {
        Route::get('/marketing',                    [App\Http\Controllers\MarketingStrategyController::class, 'index'])->name('marketing.index');
        Route::get('/marketing/about',              [App\Http\Controllers\MarketingStrategyController::class, 'about'])->name('marketing.about');
        Route::get('/marketing/create',             [App\Http\Controllers\MarketingStrategyController::class, 'create'])->name('marketing.create');
        Route::post('/marketing',                   [App\Http\Controllers\MarketingStrategyController::class, 'store'])->name('marketing.store')->middleware('throttle:web_ai');
        Route::get('/marketing/{marketing}',        [App\Http\Controllers\MarketingStrategyController::class, 'show'])->name('marketing.show');
        Route::get('/marketing/{marketing}/status', [App\Http\Controllers\MarketingStrategyController::class, 'status'])->name('marketing.status');
        Route::post('/marketing/{marketing}/regenerate-guide', [App\Http\Controllers\MarketingStrategyController::class, 'regenerateGuide'])->name('marketing.regenerate-guide')->middleware('throttle:6,1');
        Route::delete('/marketing/{marketing}',     [App\Http\Controllers\MarketingStrategyController::class, 'destroy'])->name('marketing.destroy');

        Route::get('/prospecting',                  [App\Http\Controllers\ProspectingController::class, 'index'])->name('prospecting.index');
        Route::post('/prospecting/search',          [App\Http\Controllers\ProspectingController::class, 'search'])->name('prospecting.search')->middleware('throttle:web_ai');
        Route::post('/prospecting/analyze-all',     [App\Http\Controllers\ProspectingController::class, 'analyzeAll'])->name('prospecting.analyze-all')->middleware('throttle:web_ai_bulk');
        Route::post('/prospecting/broadcast',       [App\Http\Controllers\ProspectingController::class, 'broadcastWhatsapp'])->name('prospecting.broadcast')->middleware('throttle:web_ai_bulk');
        Route::post('/prospecting/send-to-broadcast',      [App\Http\Controllers\ProspectingController::class, 'sendToBroadcast'])->name('prospecting.send-to-broadcast')->middleware('throttle:10,1');
        Route::post('/prospecting/send-to-email-campaign', [App\Http\Controllers\ProspectingController::class, 'sendToEmailCampaign'])->name('prospecting.send-to-email-campaign')->middleware('throttle:10,1');
        Route::delete('/prospecting/bulk-delete',   [App\Http\Controllers\ProspectingController::class, 'bulkDestroy'])->name('prospecting.bulk-delete');
        Route::post('/prospecting/{id}/analyze',    [App\Http\Controllers\ProspectingController::class, 'analyze'])->name('prospecting.analyze')->middleware('throttle:web_ai');
        Route::post('/prospecting/{id}/convert',    [App\Http\Controllers\ProspectingController::class, 'convertToDeal'])->name('prospecting.convert');
        Route::post('/prospecting/{id}/email',      [App\Http\Controllers\ProspectingController::class, 'updateEmail'])->name('prospecting.update-email')->middleware('throttle:30,1');
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
    Route::post('/api/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->middleware('throttle:30,1');

    // ── Bruce AI Contextual (DeepSeek + memória Redis) ───────────────────────
    Route::post('/api/bruce/chat',           [App\Http\Controllers\Api\BruceAiController::class, 'chat'])->middleware('throttle:30,1');
    Route::delete('/api/bruce/chat/history', [App\Http\Controllers\Api\BruceAiController::class, 'clearHistory']);
    Route::get('/api/bruce/insight',         [App\Http\Controllers\Api\BruceAiController::class, 'insight'])->middleware('throttle:10,1');

    // ── Locale switcher (Phase 6) ─────────────────────────────────────────────
    Route::post('/locale/{code}', [App\Http\Controllers\LocaleController::class, 'set'])->name('locale.set');

    // ── Welcome Modal (dismiss após 1º login) ─────────────────────────────────
    Route::post('/welcome/dismiss', [App\Http\Controllers\WelcomeModalController::class, 'dismiss'])
        ->name('welcome.dismiss')
        ->middleware('throttle:10,1');

    // ── Import de Planilhas de Gastos (multi-painel: NGO/Manager/Common) ──────
    Route::get( '/finance/import',           [App\Http\Controllers\TransactionImportController::class, 'showForm'])->name('finance.import.form');
    Route::get( '/finance/import/template',  [App\Http\Controllers\TransactionImportController::class, 'downloadTemplate'])->name('finance.import.template');
    Route::post('/finance/import/preview',   [App\Http\Controllers\TransactionImportController::class, 'preview'])->name('finance.import.preview')->middleware('throttle:10,1');
    Route::post('/finance/import/confirm',   [App\Http\Controllers\TransactionImportController::class, 'import'])->name('finance.import.confirm')->middleware('throttle:5,1');

    // ── Anexos polimorficos (Patrimonio, Almox/Estoque, Movimentos) ───────────
    // morphType e whitelistado no AttachmentController (asset, inv_item, inv_move).
    Route::get(   '/attachments/{morphType}/{morphId}', [App\Http\Controllers\AttachmentController::class, 'index'])
        ->where(['morphType' => '[a-z_]+', 'morphId' => '[0-9]+'])
        ->name('attachments.index');
    Route::post(  '/attachments/{morphType}/{morphId}', [App\Http\Controllers\AttachmentController::class, 'store'])
        ->where(['morphType' => '[a-z_]+', 'morphId' => '[0-9]+'])
        ->name('attachments.store')
        ->middleware('throttle:20,1');
    Route::get(   '/attachments/{id}/download',        [App\Http\Controllers\AttachmentController::class, 'download'])
        ->where('id', '[0-9]+')
        ->name('attachments.download')
        ->middleware('throttle:60,1');
    Route::delete('/attachments/{id}',                 [App\Http\Controllers\AttachmentController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('attachments.destroy')
        ->middleware('throttle:20,1');

    // ── LGPD Self-Service (art. 15 delecao + art. 18 IV exportacao) ───────────
    Route::get( '/eu/dados',                         [App\Http\Controllers\LgpdSelfServiceController::class, 'index'])->name('lgpd.self.index');
    Route::post('/eu/dados/exportar',                [App\Http\Controllers\LgpdSelfServiceController::class, 'requestExport'])->name('lgpd.self.export')->middleware('throttle:5,60');
    Route::post('/eu/dados/excluir',                 [App\Http\Controllers\LgpdSelfServiceController::class, 'requestDelete'])->name('lgpd.self.delete')->middleware('throttle:3,60');
    Route::post('/eu/dados/excluir/cancelar/{id}',   [App\Http\Controllers\LgpdSelfServiceController::class, 'cancelDelete'])->name('lgpd.self.cancel_delete')->middleware('throttle:10,60');

    // ── Smart Analysis ────────────────────────────────────────────────────────
    Route::get('/smart-analysis',       [App\Http\Controllers\SmartAnalysisController::class, 'index']);
    Route::post('/smart-analysis/deep', [App\Http\Controllers\SmartAnalysisController::class, 'generateDeepAnalysis'])->middleware('throttle:web_ai');

    // ── Autenticação de Dois Fatores (2FA/TOTP) ───────────────────────────────
    Route::get('/profile/2fa',     [App\Http\Controllers\TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/profile/2fa',    [App\Http\Controllers\TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/profile/2fa/confirm', [App\Http\Controllers\TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::delete('/profile/2fa', [App\Http\Controllers\TwoFactorController::class, 'disable'])->name('2fa.disable');

    // ── Perfil do Usuário ─────────────────────────────────────────────────────
    // ── Sala de Estrategia (Fase 2) ──────────────────────────────────────────
    Route::get('/strategy-room',                    [App\Http\Controllers\StrategyRoomController::class, 'index'])->name('strategy-room.index');
    Route::post('/strategy-room',                   [App\Http\Controllers\StrategyRoomController::class, 'store'])->name('strategy-room.store')->middleware('throttle:6,60');
    Route::get('/strategy-room/{session}',          [App\Http\Controllers\StrategyRoomController::class, 'show'])->name('strategy-room.show');
    Route::get('/strategy-room/{session}/status',   [App\Http\Controllers\StrategyRoomController::class, 'status'])->name('strategy-room.status');
    Route::post('/strategy-room/{session}/create-task', [App\Http\Controllers\StrategyRoomController::class, 'createTask'])->name('strategy-room.create-task')->middleware('throttle:30,1');

    Route::get('/profile',           [App\Http\Controllers\ProfileController::class, 'edit']);
    Route::post('/profile/update',        [App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/password',      [App\Http\Controllers\ProfileController::class, 'updatePassword']);
    Route::post('/profile/business-type', [App\Http\Controllers\ProfileController::class, 'updateBusinessType'])->name('profile.business-type');
    Route::get('/profile/export',         [App\Http\Controllers\ProfileController::class, 'exportData'])->name('profile.export')->middleware('throttle:3,60');
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

    // ── API Docs ──────────────────────────────────────────────────────────────
    Route::get('/api-docs', [App\Http\Controllers\ApiDocsController::class, 'index'])->name('api.docs');

    // ── API Tokens (Public API v1) ────────────────────────────────────────────
    Route::get('/settings/api-tokens',          [App\Http\Controllers\ApiTokenController::class, 'index'])->name('settings.api-tokens');
    Route::post('/settings/api-tokens',         [App\Http\Controllers\ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
    Route::delete('/settings/api-tokens/{id}',  [App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');

    // ── Webhooks ──────────────────────────────────────────────────────────────
    Route::get('/settings/webhooks',              [App\Http\Controllers\WebhookSettingsController::class, 'index'])->name('settings.webhooks');
    Route::post('/settings/webhooks',             [App\Http\Controllers\WebhookSettingsController::class, 'store'])->name('settings.webhooks.store');
    Route::post('/settings/webhooks/{id}/toggle', [App\Http\Controllers\WebhookSettingsController::class, 'toggle'])->name('settings.webhooks.toggle');
    Route::delete('/settings/webhooks/{id}',      [App\Http\Controllers\WebhookSettingsController::class, 'destroy'])->name('settings.webhooks.destroy');
    Route::get('/settings/webhooks/{id}/logs',         [App\Http\Controllers\WebhookSettingsController::class, 'logs'])->name('settings.webhooks.logs');
    Route::post('/settings/webhooks/{id}/retry/{log}', [App\Http\Controllers\WebhookSettingsController::class, 'retry'])->name('settings.webhooks.retry');

    // ── Busca Global ──────────────────────────────────────────────────────────
    Route::get('/search', [App\Http\Controllers\GlobalSearchController::class, 'search'])->name('search.global');

    // ── Magic Landing Page ────────────────────────────────────────────────────
    Route::post('/marketing/magic-page', [App\Http\Controllers\LandingPageController::class, 'createMagic'])->name('ngo.landing-pages.create_magic')->middleware('throttle:web_ai_bulk');
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
    Route::get('/gemini',    [App\Http\Controllers\IntegrationTestController::class, 'testGemini']);
    Route::get('/deepseek',  [App\Http\Controllers\IntegrationTestController::class, 'testDeepSeek']);
});

// ── LGPD Export Download (fora de auth — autorizacao pelo token opaco 256 bits) ──
// Rate-limitada pra evitar brute-force do token.
Route::get('/eu/dados/download/{token}', [App\Http\Controllers\LgpdSelfServiceController::class, 'download'])
    ->name('lgpd.self.download')
    ->middleware('throttle:60,1')
    ->where('token', '[A-Za-z0-9_\-]{40,80}');
