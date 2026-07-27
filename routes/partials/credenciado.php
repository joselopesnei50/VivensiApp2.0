<?php

use Illuminate\Support\Facades\Route;

// ── Painel do Credenciado ────────────────────────────────────────────────────
// User com role=credenciado esta restrito a este painel + rotas /projects/{id}/*
// dos projetos onde tem ProjectMember. Middleware EnsureCredenciadoScope
// (global no grupo web) faz o gate real.
Route::middleware(['auth', 'subscription'])->prefix('credenciado')->name('credenciado.')->group(function () {
    Route::get('/', [App\Http\Controllers\CredenciadoController::class, 'index'])->name('index');
    Route::get('/projeto/{id}', [App\Http\Controllers\CredenciadoController::class, 'showProject'])
        ->where('id', '[0-9]+')->name('project');
    Route::post('/projeto/{id}/tarefa/{taskId}/status', [App\Http\Controllers\CredenciadoController::class, 'updateTaskStatus'])
        ->where(['id' => '[0-9]+', 'taskId' => '[0-9]+'])
        ->middleware('throttle:web_write')
        ->name('task.status');
});
