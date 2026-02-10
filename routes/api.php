<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/whatsapp/webhook', [App\Http\Controllers\WhatsappController::class, 'webhook'])
    ->middleware('throttle:300,1');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Asaas Webhook
// Asaas Webhook - Direct Closure to bypass Controller instantiation issues
Route::post('/webhooks/asaas', function (Request $request) {
    // 1. Authenticate
    $webhookToken = config('services.asaas.webhook_token');
    $incomingToken = $request->header('asaas-access-token');

    if ($webhookToken && $incomingToken !== $webhookToken) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // 2. Process
    try {
        $payload = $request->all();
        // Log lightly
        try {
            \Illuminate\Support\Facades\Log::info('Asaas Webhook (Closure)', ['id' => data_get($payload, 'id')]);
        } catch (\Throwable $e) {}

        // Dispatch
        \App\Jobs\HandleAsaasWebhook::dispatch($payload);
    
    } catch (\Throwable $e) {
        // Log critical error
        try {
            \Illuminate\Support\Facades\Log::critical('Asaas Webhook Error', ['msg' => $e->getMessage()]);
        } catch (\Throwable $err) {}
    }

    // 3. Always Return 200
    return response()->json(['status' => 'success', 'strategy' => 'closure_fallback']);
});

