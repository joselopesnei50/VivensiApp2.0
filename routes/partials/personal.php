<?php

// ── Módulo Pessoal (MEI / Pessoa Física) ──────────────────────────────────────
Route::middleware(['auth', 'subscription'])->prefix('personal')->group(function () {
    Route::get('/reconciliation',         [App\Http\Controllers\PersonalReconciliationController::class, 'index']);
    Route::post('/reconciliation/upload', [App\Http\Controllers\PersonalReconciliationController::class, 'upload'])->middleware('throttle:web_ai_bulk');
    Route::post('/reconciliation/store',  [App\Http\Controllers\PersonalReconciliationController::class, 'store'])->middleware('throttle:web_write');

    Route::get('/budget',                 [App\Http\Controllers\PersonalBudgetController::class, 'index']);
    Route::post('/budget/store',          [App\Http\Controllers\PersonalBudgetController::class, 'store'])->middleware('throttle:web_write');
    Route::get('/budget/ai-tips',         [App\Http\Controllers\PersonalBudgetController::class, 'getAiTips'])->middleware('throttle:web_ai');

    // CRM MEI (Clientes)
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
});
