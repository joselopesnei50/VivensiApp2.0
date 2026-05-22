<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── WhatsApp Webhooks ──────────────────────────────────────────────────────

// Meta Cloud API (Oficial) — handshake GET + eventos POST
Route::match(['get', 'post'], '/whatsapp/webhook', [App\Http\Controllers\WhatsappController::class, 'webhook'])
    ->middleware('throttle:300,1');

// Evolution API (Nossa Infra) — URL segura por token por instância
// POST /api/evo/webhook/{instance_token}
Route::post('/evo/webhook/{token}', [App\Http\Controllers\Api\EvolutionWebhookController::class, 'handle'])
    ->middleware('throttle:500,1');

// ── Bot de Gestão Interna (Vivensi Command Bot) ───────────────────────────
// Recebe mensagens do número do bot (5516997618695) via Evolution API
Route::post('/whatsapp/bot', [App\Http\Controllers\Api\WhatsAppBotController::class, 'handle'])
    ->middleware('throttle:300,1');


// Legacy Z-API (manter retrocompatibilidade)
Route::post('/webhooks/zapi', [App\Http\Controllers\WhatsappController::class, 'webhook'])
    ->middleware('throttle:300,1');

// ── Gestão de Instâncias (Autenticado) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('whatsapp/instances')->group(function () {
        Route::get('/',             [App\Http\Controllers\Api\WhatsappInstanceController::class, 'index']);
        Route::post('/',            [App\Http\Controllers\Api\WhatsappInstanceController::class, 'store']);
        Route::get('/{id}/status',  [App\Http\Controllers\Api\WhatsappInstanceController::class, 'status']);
        Route::post('/{id}/connect',[App\Http\Controllers\Api\WhatsappInstanceController::class, 'connect']);
        Route::delete('/{id}',      [App\Http\Controllers\Api\WhatsappInstanceController::class, 'destroy']);
    });

    Route::get('/api/whatsapp/templates', [App\Http\Controllers\WhatsappController::class, 'templatesJson']);
});

// ── Outros Webhooks ────────────────────────────────────────────────────────
Route::post('/pagseguro/checkout', [App\Http\Controllers\Api\PagSeguroController::class, 'checkout'])->middleware(['auth:sanctum', 'throttle:20,1']);
Route::post('/webhooks/pagseguro', [App\Http\Controllers\Api\PagSeguroWebhookController::class, 'handle']);
Route::post('/webhooks/asaas',     [App\Http\Controllers\Api\AsaasWebhookController::class, 'handle']);

// ── AbacatePay ────────────────────────────────────────────────────────────────
Route::post('/abacatepay/checkout', [App\Http\Controllers\Api\AbacatePayCheckoutController::class, 'checkout'])->middleware(['auth:sanctum', 'throttle:20,1']);
Route::post('/abacatepay/webhook',  [App\Http\Controllers\Api\AbacatePayWebhookController::class, 'handle'])->middleware('throttle:200,1');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Public API v1 ──────────────────────────────────────────────────────────
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    Route::get('/me', [App\Http\Controllers\Api\V1\MeController::class, 'show']);

    // Transactions
    Route::get('/transactions',         [App\Http\Controllers\Api\V1\TransactionController::class, 'index']);
    Route::post('/transactions',        [App\Http\Controllers\Api\V1\TransactionController::class, 'store']);
    Route::get('/transactions/{id}',    [App\Http\Controllers\Api\V1\TransactionController::class, 'show']);
    Route::patch('/transactions/{id}',  [App\Http\Controllers\Api\V1\TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [App\Http\Controllers\Api\V1\TransactionController::class, 'destroy']);

    // Projects
    Route::get('/projects',              [App\Http\Controllers\Api\V1\ProjectController::class, 'index']);
    Route::get('/projects/{id}',         [App\Http\Controllers\Api\V1\ProjectController::class, 'show']);
    Route::get('/projects/{id}/tasks',   [App\Http\Controllers\Api\V1\ProjectController::class, 'tasks']);

    // Tasks
    Route::get('/tasks',         [App\Http\Controllers\Api\V1\TaskController::class, 'index']);
    Route::post('/tasks',        [App\Http\Controllers\Api\V1\TaskController::class, 'store']);
    Route::get('/tasks/{id}',    [App\Http\Controllers\Api\V1\TaskController::class, 'show']);
    Route::patch('/tasks/{id}',  [App\Http\Controllers\Api\V1\TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [App\Http\Controllers\Api\V1\TaskController::class, 'destroy']);
});
