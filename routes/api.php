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

// ── Auth ──────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
