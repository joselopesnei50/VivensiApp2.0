<?php

// ── Módulo Pessoal (MEI / Pessoa Física) ──────────────────────────────────────
// Auditoria 2026-08-29 P3.a: gate access-personal fecha o prefix pra role
// != common (ngo/manager/employee do mesmo tenant nao acessa clientes MEI).
Route::middleware(['auth', 'subscription', 'can:access-personal'])->prefix('personal')->group(function () {
    Route::get('/reconciliation',         [App\Http\Controllers\PersonalReconciliationController::class, 'index']);
    Route::post('/reconciliation/upload', [App\Http\Controllers\PersonalReconciliationController::class, 'upload'])->middleware('throttle:web_ai_bulk');
    Route::post('/reconciliation/store',  [App\Http\Controllers\PersonalReconciliationController::class, 'store'])->middleware('throttle:web_write');

    Route::get('/budget',                 [App\Http\Controllers\PersonalBudgetController::class, 'index']);
    Route::post('/budget/store',          [App\Http\Controllers\PersonalBudgetController::class, 'store'])->middleware('throttle:web_write');
    Route::get('/budget/ai-tips',         [App\Http\Controllers\PersonalBudgetController::class, 'getAiTips'])->middleware('throttle:web_ai');

    // CRM MEI (Clientes)
    Route::resource('clients', \App\Http\Controllers\ClientController::class);

    // MEI — botão "Marcar DAS como pago" do widget no dashboard common
    Route::post('/das/pago',
        [\App\Http\Controllers\Mei\MeiDasController::class, 'marcarPago'])
        ->name('personal.das.pago')
        ->middleware('throttle:web_write');

    // MEI — Recibos pro cliente do MEI
    Route::get('/receipts',                           [\App\Http\Controllers\Mei\MeiReceiptController::class, 'index'])->name('personal.receipts.index');
    Route::get('/receipts/create',                    [\App\Http\Controllers\Mei\MeiReceiptController::class, 'create'])->name('personal.receipts.create');
    Route::post('/receipts',                          [\App\Http\Controllers\Mei\MeiReceiptController::class, 'store'])->name('personal.receipts.store')->middleware('throttle:web_write');
    Route::post('/receipts/{id}/regenerate-link',     [\App\Http\Controllers\Mei\MeiReceiptController::class, 'regenerateLink'])->name('personal.receipts.regenerate_link')->middleware('throttle:web_write');
    Route::post('/receipts/{id}/revoke-link',         [\App\Http\Controllers\Mei\MeiReceiptController::class, 'revokeLink'])->name('personal.receipts.revoke_link')->middleware('throttle:web_write');

    // NFS-e — anexa nota emitida no portal nfse.gov.br (opção C: sem integrar
    // SEFIN diretamente, Vivensi só guarda o dossiê).
    Route::post('/receipts/{id}/nfse',                  [\App\Http\Controllers\Mei\MeiReceiptController::class, 'attachNfse'])->name('personal.receipts.nfse.attach')->middleware('throttle:web_write');
    Route::delete('/receipts/{id}/nfse',                [\App\Http\Controllers\Mei\MeiReceiptController::class, 'detachNfse'])->name('personal.receipts.nfse.detach')->middleware('throttle:web_write');
    Route::get('/receipts/{id}/nfse/download',          [\App\Http\Controllers\Mei\MeiReceiptController::class, 'downloadNfse'])->name('personal.receipts.nfse.download');
});
