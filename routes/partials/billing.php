<?php

// ── Checkout & Assinaturas ────────────────────────────────────────────────────
// Fora do middleware subscription para evitar loops de redirect
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout/success',   [App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{plan_id}', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process',  [App\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process');
});
