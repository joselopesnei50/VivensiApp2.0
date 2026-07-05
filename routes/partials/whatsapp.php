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
    Route::post('/whatsapp/chat/{id}/sales',          [App\Http\Controllers\WhatsappController::class, 'sendToSalesPipeline']);
    Route::post('/whatsapp/chat/{id}/read',           [App\Http\Controllers\WhatsappController::class, 'markRead']);
    Route::patch('/whatsapp/chat/{id}/labels',        [App\Http\Controllers\WhatsappController::class, 'updateLabels']);

    // CRUD de etiquetas customizadas por tenant (Fase 1)
    Route::get('/whatsapp/etiquetas',                 [App\Http\Controllers\WhatsappLabelController::class, 'index'])->name('whatsapp.labels.index');
    Route::get('/api/whatsapp/labels',                [App\Http\Controllers\WhatsappLabelController::class, 'listJson'])->name('whatsapp.labels.list');
    Route::post('/api/whatsapp/labels',               [App\Http\Controllers\WhatsappLabelController::class, 'store'])->name('whatsapp.labels.store');
    Route::patch('/api/whatsapp/labels/{label}',      [App\Http\Controllers\WhatsappLabelController::class, 'update'])->name('whatsapp.labels.update');
    Route::delete('/api/whatsapp/labels/{label}',     [App\Http\Controllers\WhatsappLabelController::class, 'destroy'])->name('whatsapp.labels.destroy');

    Route::post('/whatsapp/chat/{id}/compliance',     [App\Http\Controllers\WhatsappController::class, 'updateCompliance']);
    Route::post('/whatsapp/chat/{id}/toggle-bot',     [App\Http\Controllers\WhatsappController::class, 'toggleBot'])->name('whatsapp.chat.toggle-bot');
    Route::post('/whatsapp/chat/{id}/assign',         [App\Http\Controllers\WhatsappController::class, 'assignChat'])->name('whatsapp.chat.assign');

    // Transferência de atendimento (Fase 3.A)
    Route::post(  '/whatsapp/chat/{id}/transfer', [App\Http\Controllers\WhatsappController::class, 'transferChat'])->name('whatsapp.chat.transfer')->middleware('throttle:30,1');
    Route::delete('/whatsapp/chat/{id}/assignee', [App\Http\Controllers\WhatsappController::class, 'releaseChat'])->name('whatsapp.chat.release')->middleware('throttle:30,1');
    Route::get(   '/whatsapp/eligible-agents',    [App\Http\Controllers\WhatsappController::class, 'eligibleAgents'])->name('whatsapp.eligible_agents');

    // Qualificação de lead via IA (Fase 4 — item 2.3) — chama LLM, custo de tokens.
    Route::post('/whatsapp/chat/{id}/qualify-with-ai', [App\Http\Controllers\WhatsappController::class, 'qualifyChatWithAi'])
        ->name('whatsapp.chat.qualify_with_ai')->middleware('throttle:20,1');

    // Transcrição de áudio (Fase 4 — item 2.4) — Gemini multimodal.
    Route::post('/whatsapp/messages/{id}/transcribe', [App\Http\Controllers\WhatsappController::class, 'transcribeMessage'])
        ->name('whatsapp.messages.transcribe')->middleware('throttle:30,1');

    // Formulário conversacional (Fase 4 — item 2.5).
    Route::get( '/whatsapp/forms/active', [App\Http\Controllers\WhatsappController::class, 'listActiveForms'])
        ->name('whatsapp.forms.active');
    Route::post('/whatsapp/chat/{id}/forms/start', [App\Http\Controllers\WhatsappController::class, 'startFormSession'])
        ->name('whatsapp.chat.forms.start')->middleware('throttle:20,1');

    // CRUD de formulários (Fase 4 — item 2.5, parte UI)
    Route::get(   '/whatsapp/forms',                              [App\Http\Controllers\WhatsappFormController::class, 'index'])->name('whatsapp.forms.index');
    Route::get(   '/whatsapp/forms/create',                       [App\Http\Controllers\WhatsappFormController::class, 'create'])->name('whatsapp.forms.create');
    Route::post(  '/whatsapp/forms',                              [App\Http\Controllers\WhatsappFormController::class, 'store'])->name('whatsapp.forms.store')->middleware('throttle:30,1');
    Route::get(   '/whatsapp/forms/{form}/edit',                  [App\Http\Controllers\WhatsappFormController::class, 'edit'])->name('whatsapp.forms.edit');
    Route::put(   '/whatsapp/forms/{form}',                       [App\Http\Controllers\WhatsappFormController::class, 'update'])->name('whatsapp.forms.update')->middleware('throttle:30,1');
    Route::delete('/whatsapp/forms/{form}',                       [App\Http\Controllers\WhatsappFormController::class, 'destroy'])->name('whatsapp.forms.destroy')->middleware('throttle:10,1');
    Route::post(  '/whatsapp/forms/{form}/duplicate',             [App\Http\Controllers\WhatsappFormController::class, 'duplicate'])->name('whatsapp.forms.duplicate')->middleware('throttle:10,1');
    Route::post(  '/whatsapp/forms/{form}/questions',             [App\Http\Controllers\WhatsappFormController::class, 'addQuestion'])->name('whatsapp.forms.questions.add')->middleware('throttle:60,1');
    Route::patch( '/whatsapp/forms/{form}/questions/{question}',  [App\Http\Controllers\WhatsappFormController::class, 'updateQuestion'])->name('whatsapp.forms.questions.update')->middleware('throttle:60,1');
    Route::delete('/whatsapp/forms/{form}/questions/{question}',  [App\Http\Controllers\WhatsappFormController::class, 'deleteQuestion'])->name('whatsapp.forms.questions.delete')->middleware('throttle:30,1');
    Route::post(  '/whatsapp/forms/{form}/questions/reorder',     [App\Http\Controllers\WhatsappFormController::class, 'reorderQuestions'])->name('whatsapp.forms.questions.reorder')->middleware('throttle:30,1');

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
    Route::post('/whatsapp/broadcast',                [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'sendBroadcast'])->name('whatsapp.broadcast.send')->middleware('throttle:10,1');
    Route::post('/whatsapp/broadcast/import',         [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'importContacts'])->name('whatsapp.broadcast.import')->middleware('throttle:5,1');
    Route::get('/whatsapp/broadcast/groups',          [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'getGroups'])->name('whatsapp.broadcast.groups');
    Route::get('/whatsapp/broadcast/label-count',     [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'labelRecipientsCount'])->name('whatsapp.broadcast.label-count');
    Route::get('/whatsapp/broadcast/campaigns',       [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'campaigns'])->name('whatsapp.broadcast.campaigns');
    Route::delete('/whatsapp/broadcast/{id}/cancel',  [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'cancelScheduled'])->name('whatsapp.broadcast.cancel');
    Route::post('/whatsapp/broadcast/{id}/resume',   [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'resumeCampaign'])->name('whatsapp.broadcast.resume')->middleware('throttle:5,1');
    Route::get('/whatsapp/broadcast/draft/{id}/edit', [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'editDraft'])->name('whatsapp.broadcast.draft.edit');
    Route::delete('/whatsapp/broadcast/draft/{id}',   [App\Http\Controllers\Admin\WhatsappBroadcastController::class, 'discardDraft'])->name('whatsapp.broadcast.draft.discard')->middleware('throttle:web_write');

    // Instâncias
    Route::get('/whatsapp/instances',                 [App\Http\Controllers\WhatsappController::class, 'instances'])->name('whatsapp.instances');
    Route::post('/whatsapp/instances',                [App\Http\Controllers\Api\WhatsappInstanceController::class, 'store'])->name('whatsapp.instances.store')->middleware('throttle:5,1');
    Route::get('/whatsapp/instances/{id}/status',     [App\Http\Controllers\Api\WhatsappInstanceController::class, 'status'])->name('whatsapp.instances.status');
    Route::get('/whatsapp/instances/{id}/health',     [App\Http\Controllers\Api\WhatsappInstanceController::class, 'health'])->name('whatsapp.instances.health');
    Route::post('/whatsapp/instances/{id}/connect',   [App\Http\Controllers\Api\WhatsappInstanceController::class, 'connect'])->name('whatsapp.instances.connect')->middleware('throttle:20,1');
    Route::delete('/whatsapp/instances/{id}',         [App\Http\Controllers\Api\WhatsappInstanceController::class, 'destroy'])->name('whatsapp.instances.destroy')->middleware('throttle:10,1');
    Route::patch('/whatsapp/instances/{id}/proxy',    [App\Http\Controllers\Api\WhatsappInstanceController::class, 'updateProxy'])->name('whatsapp.instances.proxy')->middleware('throttle:10,1');

    // Termo anti-ban (Fase 2 — item 4.2)
    Route::get( '/whatsapp/anti-ban',        [App\Http\Controllers\WhatsappAntiBanController::class, 'show'])->name('whatsapp.anti_ban.show');
    Route::post('/whatsapp/anti-ban/accept', [App\Http\Controllers\WhatsappAntiBanController::class, 'accept'])->name('whatsapp.anti_ban.accept')->middleware('throttle:10,1');

    // Automações
    Route::get('/whatsapp/automations',                    [App\Http\Controllers\WhatsappAutomationController::class, 'index'])->name('whatsapp.automations.index');
    Route::get('/whatsapp/automations/create',             [App\Http\Controllers\WhatsappAutomationController::class, 'create'])->name('whatsapp.automations.create');
    Route::post('/whatsapp/automations',                   [App\Http\Controllers\WhatsappAutomationController::class, 'store'])->name('whatsapp.automations.store');
    Route::get('/whatsapp/automations/{automation}/edit',  [App\Http\Controllers\WhatsappAutomationController::class, 'edit'])->name('whatsapp.automations.edit');
    Route::put('/whatsapp/automations/{automation}',       [App\Http\Controllers\WhatsappAutomationController::class, 'update'])->name('whatsapp.automations.update');
    Route::delete('/whatsapp/automations/{automation}',    [App\Http\Controllers\WhatsappAutomationController::class, 'destroy'])->name('whatsapp.automations.destroy');
    Route::patch('/whatsapp/automations/{automation}/toggle', [App\Http\Controllers\WhatsappAutomationController::class, 'toggle'])->name('whatsapp.automations.toggle');
    Route::get('/whatsapp/automations/{automation}/logs',  [App\Http\Controllers\WhatsappAutomationController::class, 'logs'])->name('whatsapp.automations.logs');

    // Opt-in & Campanhas
    Route::get('/whatsapp/optin',                           [App\Http\Controllers\Admin\ContatoWhatsappController::class, 'index'])->name('whatsapp.optin.index');
    Route::post('/whatsapp/optin/registrar',                [App\Http\Controllers\Admin\ContatoWhatsappController::class, 'registrarManual'])->name('whatsapp.optin.registrar')->middleware('throttle:20,1');
    Route::patch('/whatsapp/optin/{contato}/remover',       [App\Http\Controllers\Admin\ContatoWhatsappController::class, 'removerOptIn'])->name('whatsapp.optin.remover');
    Route::get('/whatsapp/optin/campanhas',                 [App\Http\Controllers\Admin\CampanhaController::class, 'index'])->name('whatsapp.optin.campanhas');
    Route::post('/whatsapp/optin/campanhas',                [App\Http\Controllers\Admin\CampanhaController::class, 'store'])->name('whatsapp.optin.campanhas.store');
    Route::post('/whatsapp/optin/campanhas/{campanha}/disparar',   [App\Http\Controllers\Admin\CampanhaController::class, 'disparar'])->name('whatsapp.optin.campanhas.disparar')->middleware('throttle:5,1');
    Route::patch('/whatsapp/optin/campanhas/{campanha}/cancelar',  [App\Http\Controllers\Admin\CampanhaController::class, 'cancelar'])->name('whatsapp.optin.campanhas.cancelar');
    Route::get('/whatsapp/optin/campanhas/{campanha}/status',      [App\Http\Controllers\Admin\CampanhaController::class, 'status'])->name('whatsapp.optin.campanhas.status');
    Route::get('/whatsapp/optin/campanhas/{campanha}/editar',      [App\Http\Controllers\Admin\CampanhaController::class, 'edit'])->name('whatsapp.optin.campanhas.edit');
    Route::patch('/whatsapp/optin/campanhas/{campanha}',           [App\Http\Controllers\Admin\CampanhaController::class, 'update'])->name('whatsapp.optin.campanhas.update');
    Route::post('/whatsapp/optin/campanhas/{campanha}/duplicar',   [App\Http\Controllers\Admin\CampanhaController::class, 'duplicate'])->name('whatsapp.optin.campanhas.duplicate');
    Route::delete('/whatsapp/optin/campanhas/{campanha}',          [App\Http\Controllers\Admin\CampanhaController::class, 'destroy'])->name('whatsapp.optin.campanhas.destroy');
});
