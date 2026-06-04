<?php

// ── Healthcheck ───────────────────────────────────────────────────────────────
Route::get('/ping',   fn () => response()->json(['status' => 'ok', 'ts' => now()->toIso8601String()]))->name('health.ping');
Route::get('/health', [App\Http\Controllers\HealthController::class, 'check'])->name('health.check');

// ── Homepage & soluções ───────────────────────────────────────────────────────
Route::get('/', [App\Http\Controllers\PublicController::class, 'welcome']);
Route::get('/solucoes/terceiro-setor',  [App\Http\Controllers\PublicController::class, 'solutionsNgo'])->name('solutions.ngo');
Route::get('/solucoes/gestor-projetos', [App\Http\Controllers\PublicController::class, 'solutionsManager'])->name('solutions.manager');
Route::get('/solucoes/pessoa-comum',    [App\Http\Controllers\PublicController::class, 'solutionsCommon'])->name('solutions.common');

// ── Agendamento público ───────────────────────────────────────────────────────
Route::prefix('agendar')->name('booking.')->group(function () {
    Route::get('/',                    [App\Http\Controllers\MeetingBookingController::class, 'index'])->name('index');
    Route::get('/slots',               [App\Http\Controllers\MeetingBookingController::class, 'slots'])->name('slots');
    Route::post('/',                   [App\Http\Controllers\MeetingBookingController::class, 'store'])->name('store')->middleware('throttle:10,1');
    Route::get('/cancelar/{token}',    [App\Http\Controllers\MeetingBookingController::class, 'cancel'])->name('cancel');
});

// ── Páginas CMS & LGPD ────────────────────────────────────────────────────────
Route::get('/pagina/{slug}', [App\Http\Controllers\PageController::class, 'show'])->name('public.page');
Route::view('/termos',       'legal.terms')->name('legal.terms');
Route::view('/privacidade',  'legal.privacy')->name('legal.privacy');
Route::post('/cookie/accept', [App\Http\Controllers\CookieConsentController::class, 'accept'])->name('cookie.accept');
Route::post('/cookie/revoke', [App\Http\Controllers\CookieConsentController::class, 'revoke'])->name('cookie.revoke');

// ── Idioma ────────────────────────────────────────────────────────────────────
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['pt_BR', 'es', 'en'])) {
        session()->put('locale', $locale);
    }
    return back();
})->name('lang.switch');

// ── Portal do Doador ──────────────────────────────────────────────────────────
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/portal-doador/{token}',         [App\Http\Controllers\DonorPortalController::class, 'show'])->name('donor.portal');
    Route::post('/portal-doador/{token}/update', [App\Http\Controllers\DonorPortalController::class, 'update'])->name('donor.portal.update');
});
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/portal-doador/{token}/ir-pdf',  [App\Http\Controllers\DonorPortalController::class, 'downloadIrPdf'])->name('donor.portal.pdf');
});

// ── Campanha pública ──────────────────────────────────────────────────────────
Route::get('/c/{slug}', [App\Http\Controllers\CampaignController::class, 'show']);

// ── Blog ──────────────────────────────────────────────────────────────────────
Route::get('/blog',        [App\Http\Controllers\PublicController::class, 'blogIndex'])->name('public.blog.index');
Route::get('/blog/{slug}', [App\Http\Controllers\PublicController::class, 'blogShow'])->name('public.blog.show');

// ── Landing Pages públicas ────────────────────────────────────────────────────
Route::get('/robots.txt',      [App\Http\Controllers\LandingPageController::class, 'robots']);
Route::get('/lp-sitemap.xml',  [App\Http\Controllers\LandingPageController::class, 'sitemap']);
Route::get('/lp/{slug}',       [App\Http\Controllers\LandingPageController::class, 'renderPage']);
Route::post('/lp/{slug}/lead', [App\Http\Controllers\LandingPageController::class, 'submitLead'])->middleware('throttle:20,1');

// ── Portal de Transparência público ──────────────────────────────────────────
Route::get('/transparencia/{slug}/docs/{id}',     [App\Http\Controllers\TransparencyController::class, 'downloadDocument'])->middleware('throttle:120,1')->name('transparency.doc');
Route::get('/transparencia/{slug}/dados.csv',     [App\Http\Controllers\TransparencyController::class, 'openDataCsv'])->middleware('throttle:60,1')->name('transparency.opendata');
Route::get('/transparencia/{slug}/relatorio.pdf', [App\Http\Controllers\TransparencyController::class, 'publicReportPdf'])->middleware('throttle:30,1')->name('transparency.report_pdf');
Route::get('/transparencia/{slug}',               [App\Http\Controllers\TransparencyController::class, 'renderPortal'])->name('transparency.portal');

// ── SIC — Serviço de Informação ao Cidadão ───────────────────────────────────
Route::get('/transparencia/{slug}/sic',                          [App\Http\Controllers\SicController::class, 'publicForm'])->name('sic.public.form');
Route::post('/transparencia/{slug}/sic',                         [App\Http\Controllers\SicController::class, 'publicStore'])->middleware('throttle:5,1')->name('sic.public.store');
Route::get('/transparencia/{slug}/sic/{protocol}',               [App\Http\Controllers\SicController::class, 'publicStatus'])->name('sic.public.status');
Route::get('/t/{slug}',                           [App\Http\Controllers\TransparencyController::class, 'publicView'])->where('slug', '[a-z0-9\-]+')->middleware('throttle:10,1');

// ── Recibos & Certificados públicos ──────────────────────────────────────────
Route::get('/r/{token}',               [App\Http\Controllers\ReceiptController::class, 'show'])->middleware('throttle:30,1')->name('public.receipt');
Route::get('/validar-recibo',          [App\Http\Controllers\ReceiptController::class, 'validateForm'])->name('public.receipt.validate');
Route::post('/validar-recibo',         [App\Http\Controllers\ReceiptController::class, 'validateSubmit'])->middleware('throttle:5,1');
Route::get('/validar-certificado/{uuid}', [App\Http\Controllers\HumanResourcesController::class, 'publicValidateVolunteerCertificate'])->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')->middleware('throttle:5,1')->name('public.volunteer_certificate.validate');

// ── Assinatura de contratos pública ──────────────────────────────────────────
Route::get('/sign/{token}',  [App\Http\Controllers\ContractController::class, 'showPublic'])->middleware('throttle:30,1')->name('public.contract');
Route::post('/sign/{token}', [App\Http\Controllers\ContractController::class, 'sign'])->middleware('throttle:10,1');

// ── Rifas públicas ────────────────────────────────────────────────────────────
Route::get('/rifa/{slug}',                       [App\Http\Controllers\PublicRaffleController::class, 'show'])->name('public.raffle.show');
Route::post('/rifa/{slug}/reserve',              [App\Http\Controllers\PublicRaffleController::class, 'reserve'])->middleware('throttle:20,1')->name('public.raffle.reserve');
Route::post('/rifa/ticket/{ticket}/comprovante', [App\Http\Controllers\PublicRaffleController::class, 'uploadReceipt'])->middleware('throttle:10,1')->name('public.raffle.receipt');

// ── Webhooks de pagamento ─────────────────────────────────────────────────────
Route::post('/openpix/webhook', [App\Http\Controllers\OpenPixWebhookController::class, 'receive'])->name('openpix.webhook');
