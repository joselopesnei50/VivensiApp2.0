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
Route::post('/webhooks/asaas', [App\Http\Controllers\Api\AsaasWebhookController::class, 'handle']);

