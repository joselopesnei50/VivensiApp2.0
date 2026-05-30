<?php

// ── Módulo ONG / Terceiro Setor ───────────────────────────────────────────────
Route::middleware(['auth', 'subscription'])->group(function () {

    Route::prefix('ngo')->group(function () {
        // Doadores
        Route::get('/donors',                  [App\Http\Controllers\NgoDonorController::class, 'index']);
        Route::get('/donors/create',           [App\Http\Controllers\NgoDonorController::class, 'create']);
        Route::post('/donors',                 [App\Http\Controllers\NgoDonorController::class, 'store'])->middleware('throttle:web_write');
        Route::get('/donors/{id}/edit',        [App\Http\Controllers\NgoDonorController::class, 'edit']);
        Route::put('/donors/{id}',             [App\Http\Controllers\NgoDonorController::class, 'update']);
        Route::delete('/donors/{id}',          [App\Http\Controllers\NgoDonorController::class, 'destroy']);
        Route::post('/donors/{id}/send-portal-email',  [App\Http\Controllers\NgoDonorController::class, 'sendPortalEmail'])->name('ngo.donors.send-portal-email');
        Route::post('/donors/{id}/regenerate-token',   [App\Http\Controllers\NgoDonorController::class, 'regenerateToken'])->name('ngo.donors.regenerate-token')->middleware('throttle:web_write');

        // Campanhas
        Route::get('/campaigns',               [App\Http\Controllers\CampaignController::class, 'index']);
        Route::get('/campaigns/create',        [App\Http\Controllers\CampaignController::class, 'create']);
        Route::post('/campaigns',              [App\Http\Controllers\CampaignController::class, 'store']);

        // Orçamento
        Route::get('/budget',                  [App\Http\Controllers\BudgetController::class, 'index']);
        Route::get('/budget/export',           [App\Http\Controllers\BudgetController::class, 'exportCsv'])->middleware('throttle:web_export');
        Route::get('/budget/pdf',              [App\Http\Controllers\BudgetController::class, 'pdf'])->middleware('throttle:web_export');
        Route::post('/budget',                 [App\Http\Controllers\BudgetController::class, 'store'])->middleware('throttle:web_write');

        // Equipe
        Route::get('/team',                    [App\Http\Controllers\TeamController::class, 'index']);
        Route::post('/team',                   [App\Http\Controllers\TeamController::class, 'store']);
        Route::put('/team/{id}',               [App\Http\Controllers\TeamController::class, 'update']);
        Route::delete('/team/{id}',            [App\Http\Controllers\TeamController::class, 'destroy']);

        // Smart Analysis
        Route::get('/smart-analysis',          [App\Http\Controllers\SmartAnalysisController::class, 'index']);

        // Recibos
        Route::get('/receipts',                [App\Http\Controllers\ReceiptController::class, 'index']);
        Route::get('/receipts/create',         [App\Http\Controllers\ReceiptController::class, 'create']);
        Route::post('/receipts',               [App\Http\Controllers\ReceiptController::class, 'store']);
        Route::post('/receipts/{id}/regenerate-link', [App\Http\Controllers\ReceiptController::class, 'regenerateLink'])->name('ngo.receipts.regenerate_link');
        Route::post('/receipts/{id}/revoke-link',     [App\Http\Controllers\ReceiptController::class, 'revokeLink'])->name('ngo.receipts.revoke_link');

        // Contratos
        Route::get('/contracts',               [App\Http\Controllers\ContractController::class, 'index']);
        Route::get('/contracts/create',        [App\Http\Controllers\ContractController::class, 'create']);
        Route::post('/contracts',              [App\Http\Controllers\ContractController::class, 'store']);
        Route::post('/contracts/{id}/regenerate-link', [App\Http\Controllers\ContractController::class, 'regenerateLink'])->name('ngo.contracts.regenerate_link');
        Route::post('/contracts/{id}/revoke-link',     [App\Http\Controllers\ContractController::class, 'revokeLink'])->name('ngo.contracts.revoke_link');

        // CRM Patrocínios
        Route::get('/sponsorships',              [App\Http\Controllers\SponsorshipDealController::class, 'index']);
        Route::post('/sponsorships',             [App\Http\Controllers\SponsorshipDealController::class, 'store']);
        Route::patch('/sponsorships/{id}/stage', [App\Http\Controllers\SponsorshipDealController::class, 'updateStage']);
        Route::patch('/sponsorships/{id}',       [App\Http\Controllers\SponsorshipDealController::class, 'update']);
        Route::delete('/sponsorships/{id}',      [App\Http\Controllers\SponsorshipDealController::class, 'destroy']);

        // Editais (Grants)
        Route::get('/grants',                  [App\Http\Controllers\NgoGrantController::class, 'index']);
        Route::get('/grants/create',           [App\Http\Controllers\NgoGrantController::class, 'create']);
        Route::get('/grants/create-ai',        [App\Http\Controllers\NgoGrantController::class, 'createFromAi']);
        Route::post('/grants/analyze',         [App\Http\Controllers\NgoGrantController::class, 'analyze'])->middleware('throttle:web_ai');
        Route::post('/grants',                 [App\Http\Controllers\NgoGrantController::class, 'store'])->middleware('throttle:web_write');
        Route::get('/grants/{id}/generate-proposal', [App\Http\Controllers\NgoGrantController::class, 'generateProposal'])->name('ngo.grants.generate-proposal')->middleware('throttle:web_ai');
        Route::get('/grants/{id}/ai-analyze',        [App\Http\Controllers\NgoGrantController::class, 'aiAnalyze'])->name('ngo.grants.ai-analyze')->middleware('throttle:web_ai');
        Route::get('/grants/{id}',             [App\Http\Controllers\NgoGrantController::class, 'show'])->name('ngo.grants.show');
        Route::put('/grants/{id}',             [App\Http\Controllers\NgoGrantController::class, 'update'])->name('ngo.grants.update');
        Route::delete('/grants/{id}',          [App\Http\Controllers\NgoGrantController::class, 'destroy'])->name('ngo.grants.destroy');
        Route::post('/grants/{id}/status',     [App\Http\Controllers\NgoGrantController::class, 'updateStatus'])->name('ngo.grants.status');
        Route::post('/grants/{id}/documents',                       [App\Http\Controllers\NgoGrantController::class, 'uploadDocument'])->name('ngo.grants.documents.upload');
        Route::get('/grants/{id}/documents/{docId}/download',       [App\Http\Controllers\NgoGrantController::class, 'downloadDocument'])->name('ngo.grants.documents.download');
        Route::delete('/grants/{id}/documents/{docId}',             [App\Http\Controllers\NgoGrantController::class, 'deleteDocument'])->name('ngo.grants.documents.delete');

        // Transparência (módulo interno)
        Route::get('/transparency', [App\Http\Controllers\TransparencyController::class, 'index']);

        // RH & Voluntários
        Route::get('/hr',                      [App\Http\Controllers\HumanResourcesController::class, 'index']);
        Route::get('/hr/employees/export',     [App\Http\Controllers\HumanResourcesController::class, 'exportEmployeesCsv'])->middleware('throttle:web_export');
        Route::get('/hr/volunteers/export',    [App\Http\Controllers\HumanResourcesController::class, 'exportVolunteersCsv'])->middleware('throttle:web_export');
        Route::get('/hr/payroll/pdf',          [App\Http\Controllers\HumanResourcesController::class, 'payrollPdf'])->middleware('throttle:web_export');
        Route::post('/hr/volunteers/{id}/certificate', [App\Http\Controllers\HumanResourcesController::class, 'issueVolunteerCertificate'])->middleware('throttle:web_export');
        Route::get('/hr/certificates',         [App\Http\Controllers\HumanResourcesController::class, 'certificatesIndex']);
        Route::get('/hr/certificates/export',  [App\Http\Controllers\HumanResourcesController::class, 'exportCertificatesCsv'])->middleware('throttle:web_export');
        Route::get('/hr/certificates/{id}/download', [App\Http\Controllers\HumanResourcesController::class, 'downloadVolunteerCertificate'])->middleware('throttle:web_export');
        Route::post('/hr/employees',           [App\Http\Controllers\HumanResourcesController::class, 'storeEmployee']);
        Route::put('/hr/employees/{id}',       [App\Http\Controllers\HumanResourcesController::class, 'updateEmployee'])->name('ngo.hr.employees.update');
        Route::delete('/hr/employees/{id}',    [App\Http\Controllers\HumanResourcesController::class, 'destroyEmployee'])->name('ngo.hr.employees.destroy');
        Route::post('/hr/volunteers',          [App\Http\Controllers\HumanResourcesController::class, 'storeVolunteer']);
        Route::put('/hr/volunteers/{id}',      [App\Http\Controllers\HumanResourcesController::class, 'updateVolunteer'])->name('ngo.hr.volunteers.update');
        Route::delete('/hr/volunteers/{id}',   [App\Http\Controllers\HumanResourcesController::class, 'destroyVolunteer'])->name('ngo.hr.volunteers.destroy');
        Route::post('/hr/volunteers/{id}/log-hours',    [App\Http\Controllers\HumanResourcesController::class, 'logHours']);
        Route::get('/hr/volunteers/{id}/hour-logs',     [App\Http\Controllers\HumanResourcesController::class, 'hourLogs']);
        Route::patch('/hr/volunteers/{id}/toggle-status', [App\Http\Controllers\HumanResourcesController::class, 'toggleStatus']);

        // Beneficiários
        Route::get('/beneficiaries',           [App\Http\Controllers\BeneficiaryController::class, 'index']);
        Route::get('/beneficiaries/insights',  [App\Http\Controllers\BeneficiaryController::class, 'insights']);
        Route::get('/beneficiaries/attendances/export', [App\Http\Controllers\BeneficiaryController::class, 'exportAllAttendancesCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual',              [App\Http\Controllers\BeneficiaryController::class, 'annualReport']);
        Route::get('/beneficiaries/reports/annual/pdf',          [App\Http\Controllers\BeneficiaryController::class, 'annualReportPdf'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/pdf-appendix', [App\Http\Controllers\BeneficiaryController::class, 'annualReportPdfAppendix'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/export',       [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/export-grouped',        [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportGroupedCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/export-grouped-simple', [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportGroupedSimpleCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/export-pivot-type',     [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportPivotTypeCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/reports/annual/export-pivot-user',     [App\Http\Controllers\BeneficiaryController::class, 'annualReportExportPivotUserCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/create',    [App\Http\Controllers\BeneficiaryController::class, 'create']);
        Route::get('/beneficiaries/import/template', [App\Http\Controllers\BeneficiaryController::class, 'downloadImportTemplate']);
        Route::post('/beneficiaries/import',   [App\Http\Controllers\BeneficiaryController::class, 'import'])->middleware('throttle:web_ai_bulk');
        Route::get('/beneficiaries/export',    [App\Http\Controllers\BeneficiaryController::class, 'exportCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/print',     [App\Http\Controllers\BeneficiaryController::class, 'print']);
        Route::post('/beneficiaries',          [App\Http\Controllers\BeneficiaryController::class, 'store'])->middleware('throttle:web_write');
        Route::get('/beneficiaries/{id}',      [App\Http\Controllers\BeneficiaryController::class, 'show']);
        Route::delete('/beneficiaries/{id}',   [App\Http\Controllers\BeneficiaryController::class, 'destroy']);
        Route::put('/beneficiaries/{id}',      [App\Http\Controllers\BeneficiaryController::class, 'update']);
        Route::post('/beneficiaries/{id}/attendance',                 [App\Http\Controllers\BeneficiaryController::class, 'storeAttendance']);
        Route::put('/beneficiaries/{id}/attendance/{attendanceId}',   [App\Http\Controllers\BeneficiaryController::class, 'updateAttendance']);
        Route::delete('/beneficiaries/{id}/attendance/{attendanceId}', [App\Http\Controllers\BeneficiaryController::class, 'destroyAttendance']);
        Route::get('/beneficiaries/{id}/attendance/export',           [App\Http\Controllers\BeneficiaryController::class, 'exportAttendanceCsv'])->middleware('throttle:web_export');
        Route::get('/beneficiaries/{id}/attendance/print',            [App\Http\Controllers\BeneficiaryController::class, 'printAttendance']);
        Route::get('/beneficiaries/{id}/pdf',                         [App\Http\Controllers\BeneficiaryController::class, 'pdf'])->middleware('throttle:web_export');
        Route::post('/beneficiaries/{id}/family-members',             [App\Http\Controllers\BeneficiaryController::class, 'storeFamilyMember']);
        Route::delete('/beneficiaries/{id}/family-members/{memberId}', [App\Http\Controllers\BeneficiaryController::class, 'destroyFamilyMember']);

        // Almoxarifado (Inventory)
        Route::get('/inventory',               [App\Http\Controllers\InventoryController::class, 'index'])->name('ngo.inventory.index');
        Route::post('/inventory',              [App\Http\Controllers\InventoryController::class, 'store'])->name('ngo.inventory.store');
        Route::put('/inventory/{id}',          [App\Http\Controllers\InventoryController::class, 'update'])->name('ngo.inventory.update');
        Route::delete('/inventory/{id}',       [App\Http\Controllers\InventoryController::class, 'destroy'])->name('ngo.inventory.destroy');
        Route::post('/inventory/{id}/movement', [App\Http\Controllers\InventoryController::class, 'movement'])->name('ngo.inventory.movement');

        // Patrimônio (Assets)
        Route::get('/assets',                  [App\Http\Controllers\AssetController::class, 'index']);
        Route::get('/assets/term',             [App\Http\Controllers\AssetController::class, 'term']);
        Route::get('/assets/term/pdf',         [App\Http\Controllers\AssetController::class, 'termPdf']);
        Route::get('/assets/export',           [App\Http\Controllers\AssetController::class, 'exportCsv']);
        Route::post('/assets',                 [App\Http\Controllers\AssetController::class, 'store']);
        Route::delete('/assets/{id}',          [App\Http\Controllers\AssetController::class, 'destroy']);

        // Conciliação Bancária
        Route::get('/reconciliation',          [App\Http\Controllers\ReconciliationController::class, 'index']);
        Route::post('/reconciliation/upload',  [App\Http\Controllers\ReconciliationController::class, 'upload'])->middleware('throttle:web_ai_bulk');
        Route::post('/reconciliation/store',   [App\Http\Controllers\ReconciliationController::class, 'store'])->middleware('throttle:web_write');

        // Relatórios (DRE)
        Route::get('/reports/dre',             [App\Http\Controllers\ReportController::class, 'dre']);
        Route::get('/reports/dre/export',      [App\Http\Controllers\ReportController::class, 'exportDreCsv'])->middleware('throttle:web_export');
        Route::get('/reports/dre/pdf',         [App\Http\Controllers\ReportController::class, 'drePdf'])->middleware('throttle:web_export');

        // Trilha de Auditoria
        Route::get('/audit',                   [App\Http\Controllers\AuditController::class, 'index']);
        Route::get('/audit/export',            [App\Http\Controllers\AuditController::class, 'exportCsv'])->middleware('throttle:web_export');
        Route::get('/audit/{id}',              [App\Http\Controllers\AuditController::class, 'show']);

        // E-mail Marketing (CRM)
        Route::get('/email-campaigns',                          [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'index'])->name('ngo.email_campaigns.index');
        Route::get('/email-campaigns/create',                   [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'create'])->name('ngo.email_campaigns.create');
        Route::post('/email-campaigns',                         [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'store'])->name('ngo.email_campaigns.store');
        Route::get('/email-campaigns/{emailCampaign}',          [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'show'])->name('ngo.email_campaigns.show');
        Route::post('/email-campaigns/{emailCampaign}/send',    [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'send'])->name('ngo.email_campaigns.send');
        Route::post('/email-campaigns/{emailCampaign}/stats',   [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'refreshStats'])->name('ngo.email_campaigns.stats');
        Route::delete('/email-campaigns/{emailCampaign}',       [App\Http\Controllers\Ngo\NgoEmailCampaignController::class, 'destroy'])->name('ngo.email_campaigns.destroy');
    }); // end prefix('ngo')

    // ── Landing Pages NGO (fora do prefix ngo para manter URLs originais) ────
    Route::get('/ngo/landing-pages',           [App\Http\Controllers\LandingPageController::class, 'index']);
    Route::post('/ngo/landing-pages',          [App\Http\Controllers\LandingPageController::class, 'store'])->middleware('lp.limit');
    Route::get('/ngo/landing-pages/builder/{id}', [App\Http\Controllers\LandingPageController::class, 'builder'])->name('landing-pages.builder');
    Route::post('/ngo/landing-pages/{id}/section',       [App\Http\Controllers\LandingPageController::class, 'addSection']);
    Route::put('/ngo/landing-pages/section/{id}',        [App\Http\Controllers\LandingPageController::class, 'updateSection']);
    Route::delete('/ngo/landing-pages/section/{id}',     [App\Http\Controllers\LandingPageController::class, 'deleteSection']);
    Route::post('/ngo/landing-pages/{id}/publish',       [App\Http\Controllers\LandingPageController::class, 'publish']);
    Route::post('/ngo/landing-pages/{id}/unpublish',     [App\Http\Controllers\LandingPageController::class, 'unpublish']);
    Route::post('/ngo/landing-pages/{id}/duplicate',     [App\Http\Controllers\LandingPageController::class, 'duplicate']);
    Route::post('/ngo/landing-pages/{id}/settings',      [App\Http\Controllers\LandingPageController::class, 'updateSettings']);
    Route::post('/ngo/landing-pages/{id}/upload-og-image', [App\Http\Controllers\LandingPageController::class, 'uploadOgImage']);
    Route::post('/ngo/landing-pages/{id}/upload-favicon',  [App\Http\Controllers\LandingPageController::class, 'uploadFavicon']);
    Route::delete('/ngo/landing-pages/{id}',             [App\Http\Controllers\LandingPageController::class, 'destroy']);
    Route::get('/ngo/landing-pages/{id}/leads',          [App\Http\Controllers\LandingPageLeadController::class, 'index']);
    Route::get('/ngo/landing-pages/{id}/leads/export',   [App\Http\Controllers\LandingPageLeadController::class, 'exportCsv']);

    // ── Portal de Transparência (gestão interna) ──────────────────────────────
    Route::get('/ngo/transparencia',                    [App\Http\Controllers\TransparencyController::class, 'index'])->name('transparency.index');
    Route::post('/ngo/transparencia/portal',            [App\Http\Controllers\TransparencyController::class, 'updatePortal']);
    Route::post('/ngo/transparencia/board',             [App\Http\Controllers\TransparencyController::class, 'addBoardMember']);
    Route::put('/ngo/transparencia/board/{id}',         [App\Http\Controllers\TransparencyController::class, 'updateBoardMember'])->name('ngo.transparency.board.update');
    Route::delete('/ngo/transparencia/board/{id}',      [App\Http\Controllers\TransparencyController::class, 'deleteBoardMember']);
    Route::post('/ngo/transparencia/documents',         [App\Http\Controllers\TransparencyController::class, 'addDocument']);
    Route::delete('/ngo/transparencia/documents/{id}',  [App\Http\Controllers\TransparencyController::class, 'deleteDocument']);
    Route::post('/ngo/transparencia/partnerships',      [App\Http\Controllers\TransparencyController::class, 'addPartnership']);
    Route::put('/ngo/transparencia/partnerships/{id}',  [App\Http\Controllers\TransparencyController::class, 'updatePartnership'])->name('ngo.transparency.partnerships.update');
    Route::delete('/ngo/transparencia/partnerships/{id}', [App\Http\Controllers\TransparencyController::class, 'deletePartnership']);
});
