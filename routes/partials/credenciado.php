<?php

use Illuminate\Support\Facades\Route;

// ── Painel do Credenciado ────────────────────────────────────────────────────
// User com role=credenciado esta restrito a este painel + rotas /projects/{id}/*
// dos projetos onde tem ProjectMember. Middleware EnsureCredenciadoScope
// (global no grupo web) faz o gate real.
Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/credenciado',                  [App\Http\Controllers\CredenciadoController::class, 'index'])
        ->name('credenciado.index');
    Route::get('/credenciado/projeto/{id}',     [App\Http\Controllers\CredenciadoController::class, 'showProject'])
        ->where('id', '[0-9]+')
        ->name('credenciado.project');
});
