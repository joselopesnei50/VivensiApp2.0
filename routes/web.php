<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/', [App\Http\Controllers\PublicController::class, 'welcome']);
Route::get('/solucoes/terceiro-setor', [App\Http\Controllers\PublicController::class, 'solutionsNgo'])->name('solutions.ngo');
Route::get('/solucoes/gestor-projetos', [App\Http\Controllers\PublicController::class, 'solutionsManager'])->name('solutions.manager');
Route::get('/solucoes/pessoa-comum', [App\Http\Controllers\PublicController::class, 'solutionsCommon'])->name('solutions.common');

// Public Pages (Terms, Privacy, About)
Route::get('/pagina/{slug}', [App\Http\Controllers\PageController::class, 'show'])->name('public.page');

// Cookie Consent (LGPD)
Route::post('/cookie/accept', [App\Http\Controllers\CookieConsentController::class, 'accept'])->name('cookie.accept');
Route::post('/cookie/revoke', [App\Http\Controllers\CookieConsentController::class, 'revoke'])->name('cookie.revoke');


Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['pt_BR', 'es', 'en'])) {
        session()->put('locale', $locale);
    }
    return back();
})->name('lang.switch');


Route::get('/login', function () {
    return response()
        ->view('auth.login')
        // Prevent cached login page with stale CSRF token (common cause of 419 on re-login).
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('login');

Route::post('/login', [App\Http\Controllers\LoginController::class, 'authenticate']);
Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

// Password reset (guest)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('password.update');
});

// Register Routes
Route::get('/register', [App\Http\Controllers\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register']);

// Public Campaign Route
Route::get('/c/{slug}', [App\Http\Controllers\CampaignController::class, 'show']);

// Vivensi Academy (LMS)
Route::group(['prefix' => 'academy', 'as' => 'academy.', 'middleware' => ['auth']], function () {
    Route::get('/', [App\Http\Controllers\AcademyController::class, 'index'])->name('index');
    Route::get('/{slug}', [App\Http\Controllers\AcademyController::class, 'show'])->name('show');
    Route::post('/lessons/{id}/complete', [App\Http\Controllers\AcademyController::class, 'markLessonAsViewed'])->name('lesson.complete');
    Route::get('/certificate/{code}', [App\Http\Controllers\AcademyController::class, 'downloadCertificate'])->name('certificate.download');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'subscription'])->name('dashboard');
Route::post('/onboarding/complete/{step_id}', [App\Http\Controllers\DashboardController::class, 'completeOnboardingStep'])->middleware('auth')->name('onboarding.complete');

// Checkout & Subscriptions (Outside subscription middleware to avoid loops)
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout/success', [App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/{plan_id}', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [App\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process');
});

Route::middleware(['auth', 'subscription'])->group(function () {
    Route::get('/projects', [App\Http\Controllers\ProjectController::class, 'index']);
    Route::get('/projects/create', [App\Http\Controllers\ProjectController::class, 'create']);
    Route::post('/projects', [App\Http\Controllers\ProjectController::class, 'store']);
    Route::get('/projects/details/{id}', [App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{id}', [App\Http\Controllers\ProjectController::class, 'show']);
    Route::get('/projects/{id}/edit', [App\Http\Controllers\ProjectController::class, 'edit']);
    Route::put('/projects/{id}', [App\Http\Controllers\ProjectController::class, 'update']);
    Route::post('/projects/{id}/members', [App\Http\Controllers\ProjectController::class, 'addMember']);
    Route::post('/projects/{id}/members/credential', [App\Http\Controllers\ProjectController::class, 'addMemberCredential']);
    Route::delete('/projects/{id}/members/{memberId}', [App\Http\Controllers\ProjectController::class, 'removeMember']);
    
    // Kanban Routes
    Route::get('/projects/{id}/kanban', [App\Http\Controllers\TaskController::class, 'kanban']);
    // Task Routes
    Route::get('/tasks', [App\Http\Controllers\TaskController::class, 'index']);
    Route::get('/tasks/calendar', [App\Http\Controllers\TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks/create', [App\Http\Controllers\TaskController::class, 'create']);
    Route::post('/tasks', [App\Http\Controllers\TaskController::class, 'store']);
    Route::post('/api/tasks/update-status', [App\Http\Controllers\TaskController::class, 'updateStatus']); // Usando web auth for simplicity
    Route::post('/api/tasks/update', [App\Http\Controllers\TaskController::class, 'updateTask']); // Update task fields (safe)
    Route::post('/api/tasks/create', [App\Http\Controllers\TaskController::class, 'createApi']); // Create task (safe JSON)
    
    // Chat API
    Route::post('/api/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage']);

    // Transaction Routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::get('/create', [App\Http\Controllers\TransactionController::class, 'create']);
        Route::post('/', [App\Http\Controllers\TransactionController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\TransactionController::class, 'show']); // Added for tests
        Route::put('/{id}', [App\Http\Controllers\TransactionController::class, 'update']); // Added for tests
        Route::post('/{id}/approve', [App\Http\Controllers\TransactionController::class, 'approve'])->name('transactions.approve');
        Route::post('/{id}/reject', [App\Http\Controllers\TransactionController::class, 'reject'])->name('transactions.reject');
        Route::get('/export', [App\Http\Controllers\TransactionController::class, 'export']);
        Route::delete('/{id}', [App\Http\Controllers\TransactionController::class, 'destroy']);
    });

    // Manager Routes (Moved to top level)
    Route::get('/manager/team', [App\Http\Controllers\ManagerController::class, 'team'])->name('manager.team');
    Route::get('/manager/team/{id}', [App\Http\Controllers\ManagerController::class, 'teamDetail'])->name('manager.team_detail');
    Route::post('/manager/team/store-quick', [App\Http\Controllers\ManagerController::class, 'storeQuick']);
    Route::get('/manager/approvals', [App\Http\Controllers\ManagerController::class, 'approvals'])->name('manager.approvals');
    Route::get('/manager/contracts', [App\Http\Controllers\ManagerController::class, 'contracts'])->name('manager.contracts');
    // Manager uses the same Landing Pages flow as NGO (single source of truth)
    Route::get('/manager/landing-pages', [App\Http\Controllers\LandingPageController::class, 'index'])->name('manager.landing_pages');
    Route::get('/manager/reconciliation', [App\Http\Controllers\ManagerController::class, 'reconciliation'])->name('manager.reconciliation');
    Route::get('/manager/schedule', [App\Http\Controllers\ManagerController::class, 'schedule'])->name('manager.schedule');

    // NGO Routes
    Route::prefix('ngo')->group(function () {
        Route::get('/donors', [App\Http\Controllers\NgoDonorController::class, 'index']);
        Route::get('/donors/create', [App\Http\Controllers\NgoDonorController::class, 'create']);
        Route::post('/donors', [App\Http\Controllers\NgoDonorController::class, 'store']);
        Route::get('/donors/{id}/edit', [App\Http\Controllers\NgoDonorController::class, 'edit']);
        Route::put('/donors/{id}', [App\Http\Controllers\NgoDonorController::class, 'update']);
        Route::delete('/donors/{id}', [App\Http\Controllers\NgoDonorController::class, 'destroy']);
        
        // Campaigns
        Route::get('/campaigns', [App\Http\Controllers\CampaignController::class, 'index']);
        Route::get('/campaigns/create', [App\Http\Controllers\CampaignController::class, 'create']);
        Route::post('/campaigns', [App\Http\Controllers\CampaignController::class, 'store']);

        // Budget
        Route::get('/budget', [App\Http\Controllers\BudgetController::class, 'index']);
        Route::get('/budget/export', [App\Http\Controllers\BudgetController::class, 'exportCsv']);
        Route::get('/budget/pdf', [App\Http\Controllers\BudgetController::class, 'pdf']);
        Route::post('/budget', [App\Http\Controllers\BudgetController::class, 'store']);

        // Team
        Route::get('/team', [App\Http\Controllers\TeamController::class, 'index']);
        Route::post('/team', [App\Http\Controllers\TeamController::class, 'store']);
        Route::delete('/team/{id}', [App\Http\Controllers\TeamController::class, 'destroy']);

        // Smart Analysis
        Route::get('/smart-analysis', [App\Http\Controllers\SmartAnalysisController::class, 'index']);

        // Receipts
        Route::get('/receipts', [App\Http\Controllers\ReceiptController::class, 'index']);
        Route::get('/receipts/create', [App\Http\Controllers\ReceiptController::class, 'create']);
        Route::post('/receipts', [App\Http\Controllers\ReceiptController::class, 'store']);
        Route::post('/receipts/{id}/regenerate-link', [App\Http\Controllers\ReceiptController::class, 'regenerateLink'])
            ->name('ngo.receipts.regenerate_link');
        Route::post('/receipts/{id}/revoke-link', [App\Http\Controllers\ReceiptController::class, 'revokeLink'])
            ->name('ngo.receipts.revoke_link');

        // Contracts
        Route::get('/contracts', [App\Http\Controllers\ContractController::class, 'index']);
        Route::get('/contracts/create', [App\Http\Controllers\ContractController::class, 'create']);
        Route::post('/contracts', [App\Http\Controllers\ContractController::class, 'store']);
        Route::post('/contracts/{id}/regenerate-link', [App\Http\Controllers\ContractController::class, 'regenerateLink'])
            ->name('ngo.contracts.regenerate_link');
        Route::post('/contracts/{id}/revoke-link', [App\Http\Controllers\ContractController::class, 'revokeLink'])
            ->name('ngo.contracts.revoke_link');

        // Grants (Editais)
        Route::get('/grants', [App\Http\Controllers\NgoGrantController::class, 'index']);
        Route::get('/grants/create', [App\Http\Controllers\NgoGrantController::class, 'create']);
        Route::get('/grants/create-ai', [App\Http\Controllers\NgoGrantController::class, 'createFromAi']);
        Route::post('/grants/analyze', [App\Http\Controllers\NgoGrantController::class, 'analyze']);
        Route::post('/grants', [App\Http\Controllers\NgoGrantController::class, 'store']);
        Route::get('/grants/{id}', [App\Http\Controllers\NgoGrantController::class, 'show'])->name('ngo.grants.show');
        Route::delete('/grants/{id}', [App\Http\Controllers\NgoGrantController::class, 'destroy'])->name('ngo.grants.destroy');
        Route::post('/grants/{id}/status', [App\Http\Controllers\NgoGrantController::class, 'updateStatus'])->name('ngo.grants.status');
        Route::post('/grants/{id}/documents', [App\Http\Controllers\NgoGrantController::class, 'uploadDocument'])->name('ngo.grants.documents.upload');
        Route::get('/grants/{id}/documents/{docId}/download', [App\Http\Controllers\NgoGrantController::class, 'downloadDocument'])->name('ngo.grants.documents.download');
        Route::delete('/grants/{id}/documents/{docId}', [App\Http\Controllers\NgoGrantController::class, 'deleteDocument'])->name('ngo.grants.documents.delete');

        // Transparency
        Route::get('/transparency', [App\Http\Controllers\TransparencyController::class, 'index']);
        
        // HR & Volunteers
        Route::get('/hr', [App\Http\Controllers\HumanResourcesController::class, 'index']);
        Route::get('/hr/employees/export', [App\Http\Controllers\HumanResourcesController::class, 'exportEmployeesCsv']);
        Route::get('/hr/volunteers/export', [App\Http\Controllers\HumanResourcesController::class, 'exportVolunteersCsv']);
        Route::get('/hr/payroll/pdf', [App\Http\Controllers\HumanResourcesController::class, 'payrollPdf']);
        Route::post('/hr/volunteers/{id}/certificate', [App\Http\Controllers\HumanResourcesController::class, 'issueVolunteerCertificate']);
        Route::get('/hr/certificates', [App\Http\Controllers\HumanResourcesController::class, 'certificatesIndex']);
        Route::get('/hr/certificates/export', [App\Http\Controllers\HumanResourcesController::class, 'exportCertificatesCsv']);
        Route::get('/hr/certificates/{id}/download', [App\Http\Controllers\HumanResourcesController::class, 'downloadVolunteerCertificate']);
        Route::post('/hr/employees', [App\Http\Controllers\HumanResourcesController::class, 'storeEmployee']);
        Route::post('/hr/volunteers', [App\Http\Controllers\HumanResourcesController::class, 'storeVolunteer']);

        // Beneficiaries
        Route::get('/beneficiaries', [App\Http\Controllers\BeneficiaryController::class, 'index']);
        Route::get('/beneficiaries/insights', [App\Http\Controllers\BeneficiaryController::class, 'insights']);
        Route::get('/beneficiaries/attendances/export', [App\Http\Controllers\BeneficiaryController::class, 'exportAllAttendancesCsv']);
        Route::get('/beneficiaries/reports/annual', [App\Http\Controllers\BeneficiaryController::class, 'annualReport']);
        Route::get('/beneficiaries/reports/annual/pdf', [App\Http\Controllers\BeneficiaryController::class, 'annualReportPdf']);
        Route::get('/beneficiaries/reports/annual/pdf-appendix', [App\Http\Controllers\BeneficiaryController::class, 'annualReportPdfAppendix']);
        Route::get('/beneficiaries/reports/annual/export', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportCsv']);
        Route::get('/beneficiaries/reports/annual/export-grouped', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportGroupedCsv']);
        Route::get('/beneficiaries/reports/annual/export-grouped-simple', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportGroupedSimpleCsv']);
        Route::get('/beneficiaries/reports/annual/export-pivot-type', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportPivotTypeCsv']);
        Route::get('/beneficiaries/reports/annual/export-pivot-user', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportPivotUserCsv']);
        Route::get('/beneficiaries/create', [App\Http\Controllers\BeneficiaryController::class, 'create']);
        Route::get('/beneficiaries/export', [App\Http\Controllers\BeneficiaryController::class, 'exportCsv']);
        Route::get('/beneficiaries/print', [App\Http\Controllers\BeneficiaryController::class, 'print']);
        Route::post('/beneficiaries', [App\Http\Controllers\BeneficiaryController::class, 'store']);
        Route::get('/beneficiaries/{id}', [App\Http\Controllers\BeneficiaryController::class, 'show']);
        Route::delete('/beneficiaries/{id}', [App\Http\Controllers\BeneficiaryController::class, 'destroy']);
        Route::put('/beneficiaries/{id}', [App\Http\Controllers\BeneficiaryController::class, 'update']);
        Route::post('/beneficiaries/{id}/attendance', [App\Http\Controllers\BeneficiaryController::class, 'storeAttendance']);
        Route::put('/beneficiaries/{id}/attendance/{attendanceId}', [App\Http\Controllers\BeneficiaryController::class, 'updateAttendance']);
        Route::delete('/beneficiaries/{id}/attendance/{attendanceId}', [App\Http\Controllers\BeneficiaryController::class, 'destroyAttendance']);
        Route::get('/beneficiaries/{id}/attendance/export', [App\Http\Controllers\BeneficiaryController::class, 'exportAttendanceCsv']);
        Route::get('/beneficiaries/{id}/attendance/print', [App\Http\Controllers\BeneficiaryController::class, 'printAttendance']);
        Route::get('/beneficiaries/{id}/pdf', [App\Http\Controllers\BeneficiaryController::class, 'pdf']);
        Route::post('/beneficiaries/{id}/family-members', [App\Http\Controllers\BeneficiaryController::class, 'storeFamilyMember']);
        Route::delete('/beneficiaries/{id}/family-members/{memberId}', [App\Http\Controllers\BeneficiaryController::class, 'destroyFamilyMember']);

        // Assets
        Route::get('/assets', [App\Http\Controllers\AssetController::class, 'index']);
        Route::get('/assets/term', [App\Http\Controllers\AssetController::class, 'term']);
        Route::get('/assets/term/pdf', [App\Http\Controllers\AssetController::class, 'termPdf']);
        Route::get('/assets/export', [App\Http\Controllers\AssetController::class, 'exportCsv']);
        Route::post('/assets', [App\Http\Controllers\AssetController::class, 'store']);
        Route::delete('/assets/{id}', [App\Http\Controllers\AssetController::class, 'destroy']);

        // Reconciliation
        Route::get('/reconciliation', [App\Http\Controllers\ReconciliationController::class, 'index']);
        Route::post('/reconciliation/upload', [App\Http\Controllers\ReconciliationController::class, 'upload']);
        Route::post('/reconciliation/store', [App\Http\Controllers\ReconciliationController::class, 'store']);

        // Reports
        Route::get('/reports/dre', [App\Http\Controllers\ReportController::class, 'dre']);
        Route::get('/reports/dre/export', [App\Http\Controllers\ReportController::class, 'exportDreCsv']);
        Route::get('/reports/dre/pdf', [App\Http\Controllers\ReportController::class, 'drePdf']);

        // Audit Trail
        Route::get('/audit', [App\Http\Controllers\AuditController::class, 'index']);
        Route::get('/audit/export', [App\Http\Controllers\AuditController::class, 'exportCsv']);
        Route::get('/audit/{id}', [App\Http\Controllers\AuditController::class, 'show']);
    });

    // Notifications API
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'page'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsReadWeb'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsReadWeb'])->name('notifications.read_all');

    Route::get('/api/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/api/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::post('/api/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // Landing Page Builder
    Route::get('/ngo/landing-pages', [App\Http\Controllers\LandingPageController::class, 'index']);
    Route::post('/ngo/landing-pages', [App\Http\Controllers\LandingPageController::class, 'store'])->middleware('lp.limit');
    Route::get('/ngo/landing-pages/builder/{id}', [App\Http\Controllers\LandingPageController::class, 'builder'])->name('landing-pages.builder');
    Route::post('/ngo/landing-pages/{id}/section', [App\Http\Controllers\LandingPageController::class, 'addSection']);
    Route::put('/ngo/landing-pages/section/{id}', [App\Http\Controllers\LandingPageController::class, 'updateSection']);
    Route::delete('/ngo/landing-pages/section/{id}', [App\Http\Controllers\LandingPageController::class, 'deleteSection']);
    Route::post('/ngo/landing-pages/{id}/publish', [App\Http\Controllers\LandingPageController::class, 'publish']);
    Route::post('/ngo/landing-pages/{id}/unpublish', [App\Http\Controllers\LandingPageController::class, 'unpublish']);
    Route::post('/ngo/landing-pages/{id}/duplicate', [App\Http\Controllers\LandingPageController::class, 'duplicate']);
    Route::post('/ngo/landing-pages/{id}/settings', [App\Http\Controllers\LandingPageController::class, 'updateSettings']);
    Route::post('/ngo/landing-pages/{id}/upload-og-image', [App\Http\Controllers\LandingPageController::class, 'uploadOgImage']);
    Route::post('/ngo/landing-pages/{id}/upload-favicon', [App\Http\Controllers\LandingPageController::class, 'uploadFavicon']);
    Route::delete('/ngo/landing-pages/{id}', [App\Http\Controllers\LandingPageController::class, 'destroy']);
    Route::get('/ngo/landing-pages/{id}/leads', [App\Http\Controllers\LandingPageLeadController::class, 'index']);
    Route::get('/ngo/landing-pages/{id}/leads/export', [App\Http\Controllers\LandingPageLeadController::class, 'exportCsv']);

    // WhatsApp & AI Chatbot
    Route::get('/whatsapp/settings', [App\Http\Controllers\WhatsappController::class, 'settings'])->name('whatsapp.settings');
    Route::post('/whatsapp/settings', [App\Http\Controllers\WhatsappController::class, 'saveSettings']);
    Route::get('/whatsapp/chat', [App\Http\Controllers\WhatsappController::class, 'chatIndex'])->name('whatsapp.chat');
    Route::get('/whatsapp/chat/{id}/messages', [App\Http\Controllers\WhatsappController::class, 'getChatMessages']);
    Route::post('/whatsapp/chat/send', [App\Http\Controllers\WhatsappController::class, 'sendMessage']);
    Route::post('/whatsapp/chat/start', [App\Http\Controllers\WhatsappController::class, 'startChat'])->name('whatsapp.chat.start');
    Route::post('/whatsapp/chat/{id}/compliance', [App\Http\Controllers\WhatsappController::class, 'updateCompliance']);
    // New Routes
    Route::post('/whatsapp/notes', [App\Http\Controllers\WhatsappController::class, 'addNote']);
    Route::get('/whatsapp/canned', [App\Http\Controllers\WhatsappController::class, 'getCannedResponses']);
    Route::post('/whatsapp/canned', [App\Http\Controllers\WhatsappController::class, 'saveCannedResponse']);
    Route::post('/whatsapp/test/receive', [App\Http\Controllers\WhatsappController::class, 'simulateWebhook']);
    Route::get('/whatsapp/status', [App\Http\Controllers\WhatsappController::class, 'getStatus']);

    // Marketing Intelligence (AI)
    Route::get('/marketing/strategy', [App\Http\Controllers\MarketingStrategyController::class, 'index'])->name('marketing.index');
    Route::post('/marketing/strategy', [App\Http\Controllers\MarketingStrategyController::class, 'generate'])->name('marketing.generate');
    Route::post('/marketing/magic-page', [App\Http\Controllers\LandingPageController::class, 'createMagic'])->name('ngo.landing-pages.create_magic');



    // Portal da Transparência (Módulo NGO)
    Route::get('/ngo/transparencia', [App\Http\Controllers\TransparencyController::class, 'index'])->name('transparency.index');
    Route::post('/ngo/transparencia/portal', [App\Http\Controllers\TransparencyController::class, 'updatePortal']);
    Route::post('/ngo/transparencia/board', [App\Http\Controllers\TransparencyController::class, 'addBoardMember']);
    Route::delete('/ngo/transparencia/board/{id}', [App\Http\Controllers\TransparencyController::class, 'deleteBoardMember']);
    Route::post('/ngo/transparencia/documents', [App\Http\Controllers\TransparencyController::class, 'addDocument']);
    Route::delete('/ngo/transparencia/documents/{id}', [App\Http\Controllers\TransparencyController::class, 'deleteDocument']);
    Route::post('/ngo/transparencia/partnerships', [App\Http\Controllers\TransparencyController::class, 'addPartnership']);
    Route::delete('/ngo/transparencia/partnerships/{id}', [App\Http\Controllers\TransparencyController::class, 'deletePartnership']);

}); // End of Auth/Subscription Group
// --- PUBLIC ROUTES (No Auth Required) ---

// Public Landing Page
Route::get('/robots.txt', [App\Http\Controllers\LandingPageController::class, 'robots']);
Route::get('/lp-sitemap.xml', [App\Http\Controllers\LandingPageController::class, 'sitemap']);
Route::get('/lp/{slug}', [App\Http\Controllers\LandingPageController::class, 'renderPage']);
Route::post('/lp/{slug}/lead', [App\Http\Controllers\LandingPageController::class, 'submitLead'])
    ->middleware('throttle:20,1');

// Public Transparency Portal
Route::get('/transparencia/{slug}/docs/{id}', [App\Http\Controllers\TransparencyController::class, 'downloadDocument'])
    ->middleware('throttle:120,1')
    ->name('transparency.doc');
Route::get('/transparencia/{slug}/dados.csv', [App\Http\Controllers\TransparencyController::class, 'openDataCsv'])
    ->middleware('throttle:60,1')
    ->name('transparency.opendata');
Route::get('/transparencia/{slug}/relatorio.pdf', [App\Http\Controllers\TransparencyController::class, 'publicReportPdf'])
    ->middleware('throttle:30,1')
    ->name('transparency.report_pdf');
Route::get('/transparencia/{slug}', [App\Http\Controllers\TransparencyController::class, 'renderPortal'])->name('transparency.portal');

Route::middleware(['auth', 'subscription'])->group(function () {
    // Other routes that might need to be inside auth...

    // Smart Analysis Route
    Route::get('/smart-analysis', [App\Http\Controllers\SmartAnalysisController::class, 'index']);
    Route::post('/smart-analysis/deep', [App\Http\Controllers\SmartAnalysisController::class, 'generateDeepAnalysis']);

    // Profile Routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit']);
    Route::post('/profile/update', [App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword']);

    // Support Routes (User Side)
    Route::prefix('support')->group(function () {
        Route::get('/', [App\Http\Controllers\SupportController::class, 'index'])->name('support.index');
        Route::get('/create', [App\Http\Controllers\SupportController::class, 'create'])->name('support.create');
        Route::post('/', [App\Http\Controllers\SupportController::class, 'store'])->name('support.store');
        Route::get('/{id}', [App\Http\Controllers\SupportController::class, 'show'])->name('support.show');
        Route::post('/{id}/reply', [App\Http\Controllers\SupportController::class, 'reply'])->name('support.reply');
    });



    // Super Admin Routes (SaaS)
    Route::prefix('admin')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/health', [App\Http\Controllers\AdminController::class, 'serverHealth'])->name('admin.health');
        Route::get('/tenants', [App\Http\Controllers\AdminController::class, 'tenants'])->name('admin.tenants.index');
        Route::get('/tenants/create', [App\Http\Controllers\AdminController::class, 'createTenant'])->name('admin.tenants.create');
        Route::post('/tenants', [App\Http\Controllers\AdminController::class, 'storeTenant'])->name('admin.tenants.store');
        Route::get('/tenants/{id}', [App\Http\Controllers\AdminController::class, 'showTenant'])->name('admin.tenants.show');
        Route::post('/tenants/{id}/suspend', [App\Http\Controllers\AdminController::class, 'suspendTenant'])->name('admin.tenants.suspend');
        Route::post('/tenants/{id}/activate', [App\Http\Controllers\AdminController::class, 'activateTenant'])->name('admin.tenants.activate');
        Route::resource('academy', App\Http\Controllers\Admin\AcademyController::class, ['as' => 'admin']);
        
        // Academy Modules & Lessons
        Route::get('academy/{course}/modules', [App\Http\Controllers\Admin\AcademyModuleController::class, 'index'])->name('admin.academy.modules.index');
        Route::post('academy/{course}/modules', [App\Http\Controllers\Admin\AcademyModuleController::class, 'storeModule'])->name('admin.academy.modules.store');
        Route::put('academy/modules/{id}', [App\Http\Controllers\Admin\AcademyModuleController::class, 'updateModule'])->name('admin.academy.modules.update');
        Route::delete('academy/modules/{id}', [App\Http\Controllers\Admin\AcademyModuleController::class, 'destroyModule'])->name('admin.academy.modules.destroy');
        
        Route::post('academy/modules/{module}/lessons', [App\Http\Controllers\Admin\AcademyModuleController::class, 'storeLesson'])->name('admin.academy.lessons.store');
        Route::delete('academy/lessons/{id}', [App\Http\Controllers\Admin\AcademyModuleController::class, 'destroyLesson'])->name('admin.academy.lessons.destroy');

        Route::get('/team', [App\Http\Controllers\Admin\AdminTeamController::class, 'index'])->name('admin.team.index');
        Route::get('/team/{id}', [App\Http\Controllers\Admin\AdminTeamController::class, 'profile'])->name('admin.team.profile');
        Route::post('/team', [App\Http\Controllers\Admin\AdminTeamController::class, 'store'])->name('admin.team.store');
        Route::delete('/team/{id}', [App\Http\Controllers\Admin\AdminTeamController::class, 'destroy'])->name('admin.team.destroy');
        Route::get('/settings', [App\Http\Controllers\AdminSettingsController::class, 'index']);
        Route::post('/settings', [App\Http\Controllers\AdminSettingsController::class, 'store']);
        Route::get('/email-logs', [App\Http\Controllers\AdminController::class, 'emailLogs'])->name('admin.email_logs');
        Route::get('/chat', [App\Http\Controllers\InternalChatController::class, 'index'])->name('admin.chat');
        
        // Admin Support
        Route::get('/support', [App\Http\Controllers\SupportController::class, 'adminIndex'])->name('admin.support.index');

        // Subscription Plans
        Route::resource('/plans', App\Http\Controllers\Admin\SubscriptionPlanController::class)->names([
            'index' => 'admin.plans.index',
            'create' => 'admin.plans.create',
            'store' => 'admin.plans.store',
            'edit' => 'admin.plans.edit',
            'update' => 'admin.plans.update',
            'destroy' => 'admin.plans.destroy',
        ]);
        
        // Blog CMS
        Route::resource('/blog', App\Http\Controllers\Admin\BlogController::class)->names([
            'index' => 'admin.blog.index',
            'create' => 'admin.blog.create',
            'store' => 'admin.blog.store',
            'edit' => 'admin.blog.edit',
            'update' => 'admin.blog.update',
            'destroy' => 'admin.blog.destroy',
        ]);

        // Page CMS
        Route::resource('/pages', App\Http\Controllers\Admin\PageController::class)->only(['index', 'edit', 'update'])->names([
            'index' => 'admin.pages.index',
            'edit' => 'admin.pages.edit',
            'update' => 'admin.pages.update',
        ]);

        // Testimonials CMS
        Route::resource('/testimonials', App\Http\Controllers\Admin\TestimonialController::class)->names([
            'index' => 'admin.testimonials.index',
            'create' => 'admin.testimonials.create',
            'store' => 'admin.testimonials.store',
            'edit' => 'admin.testimonials.edit',
            'update' => 'admin.testimonials.update',
            'destroy' => 'admin.testimonials.destroy',
        ]);

        // Chat API (Interno)
        Route::get('/api/chat/messages/{receiverId}', [App\Http\Controllers\InternalChatController::class, 'getMessages']);
        Route::post('/api/chat/send', [App\Http\Controllers\InternalChatController::class, 'sendMessage']);
    });

    // Personal Client Modules
    Route::prefix('personal')->group(function () {
        Route::get('/reconciliation', [App\Http\Controllers\PersonalReconciliationController::class, 'index']);
        Route::post('/reconciliation/upload', [App\Http\Controllers\PersonalReconciliationController::class, 'upload']);
        Route::post('/reconciliation/store', [App\Http\Controllers\PersonalReconciliationController::class, 'store']);
        
        Route::get('/budget', [App\Http\Controllers\PersonalBudgetController::class, 'index']);
        Route::post('/budget/store', [App\Http\Controllers\PersonalBudgetController::class, 'store']);
        Route::get('/budget/ai-tips', [App\Http\Controllers\PersonalBudgetController::class, 'getAiTips']);
    });
});


Route::get('/blog', [App\Http\Controllers\PublicController::class, 'blogIndex'])->name('public.blog.index');
Route::get('/blog/{slug}', [App\Http\Controllers\PublicController::class, 'blogShow'])->name('public.blog.show');

// Public Routes (No Auth)
Route::get('/t/{tenant_id}', [App\Http\Controllers\TransparencyController::class, 'publicView']);
Route::get('/r/{token}', [App\Http\Controllers\ReceiptController::class, 'show'])->name('public.receipt');
Route::get('/validar-recibo', [App\Http\Controllers\ReceiptController::class, 'validateForm'])->name('public.receipt.validate');
Route::post('/validar-recibo', [App\Http\Controllers\ReceiptController::class, 'validateSubmit'])->middleware('throttle:30,1');
Route::get('/validar-certificado/{id}', [App\Http\Controllers\HumanResourcesController::class, 'publicValidateVolunteerCertificate'])
    ->middleware('throttle:60,1')
    ->name('public.volunteer_certificate.validate');
Route::get('/sign/{token}', [App\Http\Controllers\ContractController::class, 'showPublic'])->name('public.contract');
Route::post('/sign/{token}', [App\Http\Controllers\ContractController::class, 'sign']);

// Rota para o Checkout (Pagamentos)
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout/{plan_id}', [App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [App\Http\Controllers\CheckoutController::class, 'process'])->name('checkout.process');
});
