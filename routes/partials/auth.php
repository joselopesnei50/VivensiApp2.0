<?php

// ── Login / Logout ────────────────────────────────────────────────────────────
Route::get('/login', function () {
    return response()
        ->view('auth.login')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('login');

Route::post('/login',  [App\Http\Controllers\LoginController::class, 'authenticate'])->middleware('throttle:10,1');
Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

// ── Recuperação de senha ──────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password',       [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password',      [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',       [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

// ── Registro ──────────────────────────────────────────────────────────────────
Route::get('/register',             [App\Http\Controllers\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register',            [App\Http\Controllers\RegisterController::class, 'register'])->middleware('throttle:10,1');
Route::get('/interesse-registrado', [App\Http\Controllers\RegisterController::class, 'interestPage'])->name('register.interest');
