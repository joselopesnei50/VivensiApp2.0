<?php

// ── Redes Sociais & Conteúdo com IA ──────────────────────────────────────────
Route::middleware(['auth', 'subscription'])->group(function () {
    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/accounts',                       [App\Http\Controllers\SocialAccountController::class, 'index'])->name('accounts');
        Route::get('/accounts/connect',               [App\Http\Controllers\SocialAccountController::class, 'connect'])->name('facebook.connect');
        Route::get('/facebook/callback',              [App\Http\Controllers\SocialAccountController::class, 'callback'])->name('facebook.callback');
        // Webhooks públicos (Meta chama sem autenticação) tratados fora deste grupo — ver abaixo.
        Route::patch('/accounts/{account}/disconnect', [App\Http\Controllers\SocialAccountController::class, 'disconnect'])->name('accounts.disconnect');
        Route::delete('/accounts/{account}',          [App\Http\Controllers\SocialAccountController::class, 'destroy'])->name('accounts.destroy');
        Route::get('/posts',                          [App\Http\Controllers\ScheduledPostController::class, 'index'])->name('posts.index');
        Route::get('/posts/calendar',                 [App\Http\Controllers\ScheduledPostController::class, 'calendar'])->name('posts.calendar');
        Route::get('/posts/create',                   [App\Http\Controllers\ScheduledPostController::class, 'create'])->name('posts.create');
        Route::post('/posts',                         [App\Http\Controllers\ScheduledPostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit',              [App\Http\Controllers\ScheduledPostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}',                   [App\Http\Controllers\ScheduledPostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}',                [App\Http\Controllers\ScheduledPostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/generate-caption',        [App\Http\Controllers\ScheduledPostController::class, 'generateCaption'])->name('posts.generate-caption');
    });
});

// ── Webhooks públicos da Meta (Facebook) ──────────────────────────────────────
// Chamados pela Meta sem autenticação — validação via signed_request (HMAC).
Route::post('/social/facebook/deauthorize',   [App\Http\Controllers\SocialAccountController::class, 'handleDeauthorize'])->middleware('throttle:60,1')->name('social.facebook.deauthorize');
Route::post('/social/facebook/data-deletion', [App\Http\Controllers\SocialAccountController::class, 'handleDataDeletion'])->middleware('throttle:60,1')->name('social.facebook.data-deletion');

// ── Social AI Hub (geração de conteúdo com IA) ────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/social-ai',                      [App\Http\Controllers\SocialAIPostController::class, 'index'])->name('social-ai.index');
    Route::post('/social-ai/generate',            [App\Http\Controllers\SocialAIPostController::class, 'generate'])->name('social-ai.generate');
    Route::get('/social-ai/{post}/status',        [App\Http\Controllers\SocialAIPostController::class, 'getStatus'])->name('social-ai.status');
    Route::get('/social-ai/{post}/to-broadcast',  [App\Http\Controllers\SocialAIPostController::class, 'toBroadcast'])->name('social-ai.to-broadcast');
    Route::post('/social-ai/{post}/schedule',     [App\Http\Controllers\SocialAIPostController::class, 'scheduleToCalendar'])->name('social-ai.schedule');
    Route::delete('/social-ai/{post}',            [App\Http\Controllers\SocialAIPostController::class, 'destroy'])->name('social-ai.destroy');

    // Inteligência Territorial (IBGE)
    Route::get('/intelligence/territorial',       [App\Http\Controllers\SocialIndicatorController::class, 'index'])->name('intelligence.territorial');
    Route::get('/api/ibge/cities',                [App\Http\Controllers\SocialIndicatorController::class, 'searchCities'])->name('ibge.cities.search');
    Route::get('/api/ibge/indicators/{cityCode}', [App\Http\Controllers\SocialIndicatorController::class, 'getIndicators'])->name('ibge.indicators');
});
