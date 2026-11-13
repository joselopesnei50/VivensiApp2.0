<?php

// ── Checkout & Assinaturas ────────────────────────────────────────────────────
// Fora do middleware subscription para evitar loops de redirect
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout/status',    [App\Http\Controllers\CheckoutController::class, 'status'])->name('checkout.status')->middleware('throttle:40,1');
    Route::get('/checkout/success',   [App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{plan_id}', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process',  [App\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process')->middleware('throttle:10,1');

    // Faturas do cliente (auto-serviço) — vê pagas + em aberto + banner cortesia
    Route::get('/minha-conta/faturas',  [App\Http\Controllers\ClientInvoicesController::class, 'index'])->name('client.invoices.index');
    Route::post('/minha-conta/faturas/{invoice}/pagar', [App\Http\Controllers\ClientInvoicesController::class, 'pay'])->whereNumber('invoice')->name('client.invoices.pay')->middleware('throttle:10,1');
    // Cancelamento da assinatura pelo próprio cliente (só o administrador da conta)
    Route::post('/minha-conta/assinatura/cancelar', [App\Http\Controllers\ClientInvoicesController::class, 'cancel'])->name('client.subscription.cancel')->middleware('throttle:5,1');
    Route::post('/minha-conta/assinatura/manter',   [App\Http\Controllers\ClientInvoicesController::class, 'keep'])->name('client.subscription.keep')->middleware('throttle:5,1');
});
