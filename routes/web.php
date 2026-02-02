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

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['pt_BR', 'es', 'en'])) {
        session()->put('locale', $locale);
    }
    return back();
})->name('lang.switch');


Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [App\Http\Controllers\LoginController::class, 'authenticate']);
Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

// Register Routes
Route::get('/register', [App\Http\Controllers\RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [App\Http\Controllers\RegisterController::class, 'register']);

// Public Campaign Route
Route::get('/c/{slug}', [App\Http\Controllers\CampaignController::class, 'show']);

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
    Route::delete('/projects/{id}/members/{memberId}', [App\Http\Controllers\ProjectController::class, 'removeMember']);
    
    // Kanban Routes
    Route::get('/projects/{id}/kanban', [App\Http\Controllers\TaskController::class, 'kanban']);
    // Task Routes
    Route::get('/tasks', [App\Http\Controllers\TaskController::class, 'index']);
    Route::get('/tasks/calendar', [App\Http\Controllers\TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks/create', [App\Http\Controllers\TaskController::class, 'create']);
    Route::post('/tasks', [App\Http\Controllers\TaskController::class, 'store']);
    Route::post('/api/tasks/update-status', [App\Http\Controllers\TaskController::class, 'updateStatus']); // Usando web auth for simplicity
    
    // Chat API
    Route::post('/api/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage']);

    // Transaction Routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::get('/create', [App\Http\Controllers\TransactionController::class, 'create']);
        Route::post('/', [App\Http\Controllers\TransactionController::class, 'store']);
        Route::get('/export', [App\Http\Controllers\TransactionController::class, 'export']);
        Route::delete('/{id}', [App\Http\Controllers\TransactionController::class, 'destroy']);
    });

    // Manager Routes (Moved to top level)
    Route::get('/manager/team', [App\Http\Controllers\ManagerController::class, 'team'])->name('manager.team');
    Route::get('/manager/team/{id}', [App\Http\Controllers\ManagerController::class, 'teamDetail'])->name('manager.team_detail');
    Route::post('/manager/team/store-quick', [App\Http\Controllers\ManagerController::class, 'storeQuick']);
    Route::get('/manager/approvals', [App\Http\Controllers\ManagerController::class, 'approvals'])->name('manager.approvals');
    Route::get('/manager/contracts', [App\Http\Controllers\ManagerController::class, 'contracts'])->name('manager.contracts');
    Route::get('/manager/landing-pages', [App\Http\Controllers\ManagerController::class, 'landingPages'])->name('manager.landing_pages');
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

        // Contracts
        Route::get('/contracts', [App\Http\Controllers\ContractController::class, 'index']);
        Route::get('/contracts/create', [App\Http\Controllers\ContractController::class, 'create']);
        Route::post('/contracts', [App\Http\Controllers\ContractController::class, 'store']);

        // Grants (Editais)
        Route::get('/grants', [App\Http\Controllers\NgoGrantController::class, 'index']);
        Route::get('/grants/create', [App\Http\Controllers\NgoGrantController::class, 'create']);
        Route::get('/grants/create-ai', [App\Http\Controllers\NgoGrantController::class, 'createFromAi']);
        Route::post('/grants/analyze', [App\Http\Controllers\NgoGrantController::class, 'analyze']);
        Route::post('/grants', [App\Http\Controllers\NgoGrantController::class, 'store']);

        // Transparency
        Route::get('/transparency', [App\Http\Controllers\TransparencyController::class, 'index']);
        
        // HR & Volunteers
        Route::get('/hr', [App\Http\Controllers\HumanResourcesController::class, 'index']);
        Route::post('/hr/employees', [App\Http\Controllers\HumanResourcesController::class, 'storeEmployee']);
        Route::post('/hr/volunteers', [App\Http\Controllers\HumanResourcesController::class, 'storeVolunteer']);

        // Beneficiaries
        Route::get('/beneficiaries', [App\Http\Controllers\BeneficiaryController::class, 'index']);
        Route::get('/beneficiaries/create', [App\Http\Controllers\BeneficiaryController::class, 'create']);
        Route::post('/beneficiaries', [App\Http\Controllers\BeneficiaryController::class, 'store']);
        Route::get('/beneficiaries/{id}', [App\Http\Controllers\BeneficiaryController::class, 'show']);
        Route::post('/beneficiaries/{id}/attendance', [App\Http\Controllers\BeneficiaryController::class, 'storeAttendance']);

        // Assets
        Route::get('/assets', [App\Http\Controllers\AssetController::class, 'index']);
        Route::post('/assets', [App\Http\Controllers\AssetController::class, 'store']);
        Route::delete('/assets/{id}', [App\Http\Controllers\AssetController::class, 'destroy']);

        // Reconciliation
        Route::get('/reconciliation', [App\Http\Controllers\ReconciliationController::class, 'index']);
        Route::post('/reconciliation/upload', [App\Http\Controllers\ReconciliationController::class, 'upload']);
        Route::post('/reconciliation/store', [App\Http\Controllers\ReconciliationController::class, 'store']);

        // Reports
        Route::get('/reports/dre', [App\Http\Controllers\ReportController::class, 'dre']);

        // Audit Trail
        Route::get('/audit', [App\Http\Controllers\AuditController::class, 'index']);
        Route::get('/audit/{id}', [App\Http\Controllers\AuditController::class, 'show']);
    });

    // Notifications API
    Route::get('/api/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
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
    Route::get('/ngo/landing-pages/{id}/leads', [App\Http\Controllers\LandingPageLeadController::class, 'index']);

    // WhatsApp & AI Chatbot
    Route::get('/whatsapp/settings', [App\Http\Controllers\WhatsappController::class, 'settings'])->name('whatsapp.settings');
    Route::post('/whatsapp/settings', [App\Http\Controllers\WhatsappController::class, 'saveSettings']);
    Route::get('/whatsapp/chat', [App\Http\Controllers\WhatsappController::class, 'chatIndex'])->name('whatsapp.chat');
    Route::get('/whatsapp/chat/{id}/messages', [App\Http\Controllers\WhatsappController::class, 'getChatMessages']);
    Route::post('/whatsapp/chat/send', [App\Http\Controllers\WhatsappController::class, 'sendMessage']);
    // New Routes
    Route::post('/whatsapp/notes', [App\Http\Controllers\WhatsappController::class, 'addNote']);
    Route::get('/whatsapp/canned', [App\Http\Controllers\WhatsappController::class, 'getCannedResponses']);
    Route::post('/whatsapp/canned', [App\Http\Controllers\WhatsappController::class, 'saveCannedResponse']);
    Route::post('/whatsapp/test/receive', [App\Http\Controllers\WhatsappController::class, 'simulateWebhook']);
    Route::get('/whatsapp/status', [App\Http\Controllers\WhatsappController::class, 'getStatus']);


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
Route::get('/lp/{slug}', [App\Http\Controllers\LandingPageController::class, 'renderPage']);
Route::post('/lp/{slug}/lead', [App\Http\Controllers\LandingPageController::class, 'submitLead']);

// Public Transparency Portal
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
        Route::get('/tenants', [App\Http\Controllers\AdminController::class, 'tenants']);
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

Route::get('/p/{slug}', [App\Http\Controllers\PublicController::class, 'showPage'])->name('public.page');
Route::get('/blog', [App\Http\Controllers\PublicController::class, 'blogIndex'])->name('public.blog.index');
Route::get('/blog/{slug}', [App\Http\Controllers\PublicController::class, 'blogShow'])->name('public.blog.show');

// Public Routes (No Auth)
Route::get('/t/{tenant_id}', [App\Http\Controllers\TransparencyController::class, 'publicView']);
Route::get('/r/{id}', [App\Http\Controllers\ReceiptController::class, 'show'])->name('public.receipt');
Route::get('/sign/{token}', [App\Http\Controllers\ContractController::class, 'showPublic'])->name('public.contract');
Route::post('/sign/{token}', [App\Http\Controllers\ContractController::class, 'sign']);
