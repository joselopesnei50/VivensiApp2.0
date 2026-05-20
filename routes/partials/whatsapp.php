<?php

// ── WhatsApp & Mensageria ─────────────────────────────────────────────────────
Route::middleware(['auth', 'subscription'])->group(function () {

    // Configurações & Templates
    Route::get('/whatsapp/settings',         [App\Http\Controllers\WhatsappController::class, 'settings'])->name('whatsapp.settings');
    Route::get('/whatsapp/templates',        [App\Http\Controllers\WhatsappController::class, 'templates'])->name('whatsapp.templates');
    Route::post('/whatsapp/settings',        [App\Http\Controllers\WhatsappController::class, 'saveSettings']);

    // Chat
    Route::get('/whatsapp/chat',                      [App\Http\Controllers\WhatsappController::class, 'chatIndex'])->name('whatsapp.chat');
    Route::get('/whatsapp/chat/list',                 [App\Http\Controllers\WhatsappController::class, 'chatList']);
    Route::get('/whatsapp/chat/{id}/messages',        [App\Http\Controllers\WhatsappController::class, 'getChatMessages']);
    Route::post('/whatsapp/chat/send',                [App\Http\Controllers\WhatsappController::class, 'sendMessage']);
    Route::post('/whatsapp/chat/start',               [App\Http\Controllers\WhatsappController::class, 'startChat'])->name('whatsapp.chat.start');
    Route::post('/whatsapp/chat/{id}/kanban',         [App\Http\Controllers\WhatsappController::class, 'sendToKanban']);
    Route::post('/whatsapp/chat/{id}/kanban-sponsorship', [App\Http\Controllers\WhatsappController::class, 'sendToSponsorshipKanban']);
    Route::post('/whatsapp/chat/{id}/compliance',     [App\Http\Controllers\WhatsappController::class, 'updateCompliance']);
    Route::post('/whatsapp/chat/{id}/toggle-bot',     [App\Http\Controllers\WhatsappController::class, 'toggleBot'])->name('whatsapp.chat.toggle-bot');
    Route::post('/whatsapp/chat/{id}/assign',         [App\Http\Controllers\WhatsappController::class, 'assignChat'])->name('whatsapp.chat.assign');
    Route::post('/whatsapp/chat/{id}/send-media',     [App\Http\Controllers\WhatsappController::class, 'sendMedia'])->name('whatsapp.chat.send-media');
    Route::post('/whatsapp/chat/{id}/send-audio',     [App\Http\Controllers\WhatsappController::class, 'sendAudio'])->name('whatsapp.chat.send-audio');
    Route::post('/whatsapp/chat/{id}/schedule',       [App\Http\Controllers\WhatsappController::class, 'scheduleMessage'])->name('whatsapp.chat.schedule');

    // Outros endpoints
    Route::post('/whatsapp/update-training',          [App\Http\Controllers\WhatsappController::class, 'updateTraining']);
    Route::post('/whatsapp/notes',                    [App\Http\Controllers\WhatsappController::class, 'addNote']);
    Route::get('/whatsapp/canned',                    [App\Http\Controllers\WhatsappController::class, 'getCannedResponses']);
    Route::post('/whatsapp/canned',                   [App\Http\Controllers\WhatsappController::class, 'saveCannedResponse']);
    Route::post('/whatsapp/test/receive',             [App\Http\Controllers\WhatsappController::class, 'simulateWebhook']);
    Route::get('/whatsapp/status',                    [App\Http\Controllers\WhatsappController::class, 'getStatus']);
    Route::post('/whatsapp/pairing-code',             [App\Http\Controllers\WhatsappController::class, 'generatePairingCode'])->middleware('throttle:5,1');
    Route::get('/whatsapp/qr-code',                   [App\Http\Controllers\WhatsappController::class, 'getQrCode'])->middleware('throttle:10,1');
    Route::get('/api/whatsapp/templates',             [App\Http\Controllers\WhatsappController::class, 'templatesJson'])->name('whatsapp.templates.json');

    // Broadcast (Disparo em Massa)
    Route::get('/whatsapp/broadcast',                 [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'index'])->name('whatsapp.broadcast.index');
    Route::post('/whatsapp/broadcast',                [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'sendBroadcast'])->name('whatsapp.broadcast.send');
    Route::post('/whatsapp/broadcast/import',         [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'importContacts'])->name('whatsapp.broadcast.import');
    Route::get('/whatsapp/broadcast/groups',          [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'getGroups'])->name('whatsapp.broadcast.groups');
    Route::get('/whatsapp/broadcast/campaigns',       [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'campaigns'])->name('whatsapp.broadcast.campaigns');
    Route::delete('/whatsapp/broadcast/{id}/cancel',  [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'cancelScheduled'])->name('whatsapp.broadcast.cancel');

    // Instâncias
    Route::get('/whatsapp/instances',                 [App\Http\Controllers\WhatsappController::class, 'instances'])->name('whatsapp.instances');
    Route::post('/whatsapp/instances',                [App\Http\Controllers\Api\WhatsappInstanceController::class, 'store'])->name('whatsapp.instances.store');
    Route::get('/whatsapp/instances/{id}/status',     [App\Http\Controllers\Api\WhatsappInstanceController::class, 'status'])->name('whatsapp.instances.status');
    Route::post('/whatsapp/instances/{id}/connect',   [App\Http\Controllers\Api\WhatsappInstanceController::class, 'connect'])->name('whatsapp.instances.connect');
    Route::delete('/whatsapp/instances/{id}',         [App\Http\Controllers\Api\WhatsappInstanceController::class, 'destroy'])->name('whatsapp.instances.destroy');

    // Automações
    Route::get('/whatsapp/automations',                    [App\Http\Controllers\WhatsappAutomationController::class, 'index'])->name('whatsapp.automations.index');
    Route::get('/whatsapp/automations/create',             [App\Http\Controllers\WhatsappAutomationController::class, 'create'])->name('whatsapp.automations.create');
    Route::post('/whatsapp/automations',                   [App\Http\Controllers\WhatsappAutomationController::class, 'store'])->name('whatsapp.automations.store');
    Route::get('/whatsapp/automations/{automation}/edit',  [App\Http\Controllers\WhatsappAutomationController::class, 'edit'])->name('whatsapp.automations.edit');
    Route::put('/whatsapp/automations/{automation}',       [App\Http\Controllers\WhatsappAutomationController::class, 'update'])->name('whatsapp.automations.update');
    Route::delete('/whatsapp/automations/{automation}',    [App\Http\Controllers\WhatsappAutomationController::class, 'destroy'])->name('whatsapp.automations.destroy');
    Route::patch('/whatsapp/automations/{automation}/toggle', [App\Http\Controllers\WhatsappAutomationController::class, 'toggle'])->name('whatsapp.automations.toggle');
    Route::get('/whatsapp/automations/{automation}/logs',  [App\Http\Controllers\WhatsappAutomationController::class, 'logs'])->name('whatsapp.automations.logs');
});
