<?php

// ── Projetos & Tarefas ────────────────────────────────────────────────────────
Route::middleware(['auth', 'subscription'])->group(function () {
    // Projetos
    Route::get('/projects',              [App\Http\Controllers\ProjectController::class, 'index']);
    Route::get('/projects/archived',     [App\Http\Controllers\ProjectController::class, 'archived'])->name('projects.archived');
    Route::get('/projects/create',       [App\Http\Controllers\ProjectController::class, 'create']);
    Route::post('/projects',             [App\Http\Controllers\ProjectController::class, 'store'])->middleware('throttle:web_write');
    Route::get('/projects/details/{id}', [App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{id}',         [App\Http\Controllers\ProjectController::class, 'show']);
    Route::get('/projects/{id}/edit',    [App\Http\Controllers\ProjectController::class, 'edit']);
    Route::put('/projects/{id}',         [App\Http\Controllers\ProjectController::class, 'update']);
    Route::post('/projects/{id}/archive',   [App\Http\Controllers\ProjectController::class, 'archive'])->name('projects.archive')->middleware('throttle:web_write');
    Route::post('/projects/{id}/unarchive', [App\Http\Controllers\ProjectController::class, 'unarchive'])->name('projects.unarchive')->middleware('throttle:web_write');
    Route::post('/projects/{id}/members',             [App\Http\Controllers\ProjectController::class, 'addMember']);
    Route::post('/projects/{id}/members/credential',  [App\Http\Controllers\ProjectController::class, 'addMemberCredential']);
    Route::delete('/projects/{id}/members/{memberId}', [App\Http\Controllers\ProjectController::class, 'removeMember']);
    Route::post('/projects/people/global',            [App\Http\Controllers\ProjectController::class, 'storeGlobalPerson'])->name('projects.people.store.global');
    Route::post('/projects/people/import',            [App\Http\Controllers\ProjectController::class, 'importPeople'])->name('projects.people.import.global');
    Route::post('/projects/{id}/people',              [App\Http\Controllers\ProjectController::class, 'storePerson'])->name('projects.people.store');
    Route::delete('/projects/{id}/people/{personId}', [App\Http\Controllers\ProjectController::class, 'destroyPerson'])->name('projects.people.destroy');
    Route::post('/projects/{id}/broadcast',           [App\Http\Controllers\ProjectController::class, 'createBroadcastList'])->name('projects.broadcast.create');
    Route::get('/projects/{id}/kanban',               [App\Http\Controllers\TaskController::class, 'kanban']);
    Route::get('/projects/{id}/export-pdf',           [App\Http\Controllers\ProjectController::class, 'exportPdf'])->name('projects.export.pdf')->middleware('throttle:web_export');

    // Timeline
    Route::post('/projects/{id}/timeline',              [App\Http\Controllers\ProjectTimelineController::class, 'store'])->name('projects.timeline.store');
    Route::delete('/projects/{id}/timeline/{recordId}', [App\Http\Controllers\ProjectTimelineController::class, 'destroy'])->name('projects.timeline.destroy');

    // Diário de Evolução (Logs)
    Route::post('/projects/{id}/logs',            [App\Http\Controllers\ProjectLogController::class, 'store'])->name('projects.logs.store');
    Route::delete('/projects/{id}/logs/{logId}',  [App\Http\Controllers\ProjectLogController::class, 'destroy'])->name('projects.logs.destroy');
    Route::post('/projects/{id}/logs/summary',    [App\Http\Controllers\ProjectLogController::class, 'generateSummary'])->name('projects.logs.summary')->middleware('throttle:web_ai');
    Route::get('/projects/{id}/logs/summary-status', [App\Http\Controllers\ProjectLogController::class, 'summaryStatus'])->name('projects.logs.summary-status');

    // Planejamento estrategico (Fase 1 — atras de feature flag
    // PROJECT_PLANNING_ENABLED, controlado em config/planning.php)
    Route::get('/projects/{id}/planning',                            [App\Http\Controllers\ProjectPlanningController::class, 'show'])->name('projects.planning.show');
    Route::put('/projects/{id}/planning',                            [App\Http\Controllers\ProjectPlanningController::class, 'updateOverview'])->name('projects.planning.update')->middleware('throttle:web_write');
    Route::post('/projects/{id}/planning/goals',                     [App\Http\Controllers\ProjectPlanningController::class, 'storeGoal'])->name('projects.planning.goals.store')->middleware('throttle:web_write');
    Route::put('/projects/{id}/planning/goals/{goalId}',             [App\Http\Controllers\ProjectPlanningController::class, 'updateGoal'])->name('projects.planning.goals.update')->middleware('throttle:web_write');
    Route::delete('/projects/{id}/planning/goals/{goalId}',          [App\Http\Controllers\ProjectPlanningController::class, 'destroyGoal'])->name('projects.planning.goals.destroy')->middleware('throttle:web_write');
    Route::post('/projects/{id}/planning/milestones',                [App\Http\Controllers\ProjectPlanningController::class, 'storeMilestone'])->name('projects.planning.milestones.store')->middleware('throttle:web_write');
    Route::put('/projects/{id}/planning/milestones/{milestoneId}',   [App\Http\Controllers\ProjectPlanningController::class, 'updateMilestone'])->name('projects.planning.milestones.update')->middleware('throttle:web_write');
    Route::delete('/projects/{id}/planning/milestones/{milestoneId}',[App\Http\Controllers\ProjectPlanningController::class, 'destroyMilestone'])->name('projects.planning.milestones.destroy')->middleware('throttle:web_write');

    // ── Lista de Presenca (class_sessions) ────────────────────────────────
    Route::get('/class-sessions',                                              [App\Http\Controllers\ClassSessionController::class, 'indexAll'])->name('class-sessions.index-all');
    Route::get('/projects/{project}/class-sessions',                           [App\Http\Controllers\ClassSessionController::class, 'index'])->name('class-sessions.index');
    Route::get('/projects/{project}/class-sessions/create',                    [App\Http\Controllers\ClassSessionController::class, 'create'])->name('class-sessions.create');
    Route::post('/projects/{project}/class-sessions',                          [App\Http\Controllers\ClassSessionController::class, 'store'])->name('class-sessions.store')->middleware('throttle:web_write');
    Route::get('/projects/{project}/class-sessions/{session}',                 [App\Http\Controllers\ClassSessionController::class, 'show'])->name('class-sessions.show');
    Route::get('/projects/{project}/class-sessions/{session}/edit',            [App\Http\Controllers\ClassSessionController::class, 'edit'])->name('class-sessions.edit');
    Route::put('/projects/{project}/class-sessions/{session}',                 [App\Http\Controllers\ClassSessionController::class, 'update'])->name('class-sessions.update')->middleware('throttle:web_write');
    Route::delete('/projects/{project}/class-sessions/{session}',              [App\Http\Controllers\ClassSessionController::class, 'destroy'])->name('class-sessions.destroy')->middleware('throttle:web_write');
    Route::post('/projects/{project}/class-sessions/{session}/attendance',     [App\Http\Controllers\ClassSessionController::class, 'saveAttendance'])->name('class-sessions.save-attendance')->middleware('throttle:web_write');
    Route::post('/projects/{project}/class-sessions/{session}/token/generate', [App\Http\Controllers\ClassSessionController::class, 'generateToken'])->name('class-sessions.token.generate')->middleware('throttle:web_write');
    Route::post('/projects/{project}/class-sessions/{session}/token/revoke',   [App\Http\Controllers\ClassSessionController::class, 'revokeToken'])->name('class-sessions.token.revoke')->middleware('throttle:web_write');
    Route::get('/projects/{project}/attendance/report',                        [App\Http\Controllers\AttendanceReportController::class, 'show'])->name('attendance.report');
    Route::get('/projects/{project}/attendance/report.csv',                    [App\Http\Controllers\AttendanceReportController::class, 'exportReport'])->name('attendance.report.csv')->middleware('throttle:web_export');
    Route::get('/projects/{project}/class-sessions/{session}/export.csv',      [App\Http\Controllers\AttendanceReportController::class, 'exportSession'])->name('class-sessions.export.csv')->middleware('throttle:web_export');

    // Tarefas
    Route::get('/tasks',             [App\Http\Controllers\TaskController::class, 'index']);
    Route::get('/tasks/calendar',    [App\Http\Controllers\TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks/create',      [App\Http\Controllers\TaskController::class, 'create']);
    Route::post('/tasks',            [App\Http\Controllers\TaskController::class, 'store'])->middleware('throttle:web_write');
    Route::post('/api/tasks/update-status', [App\Http\Controllers\TaskController::class, 'updateStatus']);
    Route::post('/api/tasks/update',        [App\Http\Controllers\TaskController::class, 'updateTask']);
    Route::post('/api/tasks/create',        [App\Http\Controllers\TaskController::class, 'createApi']);

    // Transações Financeiras
    Route::prefix('transactions')->group(function () {
        Route::get('/',              [App\Http\Controllers\TransactionController::class, 'index']);
        Route::get('/create',        [App\Http\Controllers\TransactionController::class, 'create']);
        Route::post('/',             [App\Http\Controllers\TransactionController::class, 'store'])->middleware('throttle:web_write');
        Route::get('/{id}/attachment', [App\Http\Controllers\TransactionController::class, 'downloadAttachment'])->name('transactions.attachment');
        Route::get('/{id}',          [App\Http\Controllers\TransactionController::class, 'show']);
        Route::put('/{id}',          [App\Http\Controllers\TransactionController::class, 'update'])->middleware('throttle:web_write');
        Route::post('/{id}/approve', [App\Http\Controllers\TransactionController::class, 'approve'])->name('transactions.approve')->middleware('throttle:web_write');
        Route::post('/{id}/reject',  [App\Http\Controllers\TransactionController::class, 'reject'])->name('transactions.reject')->middleware('throttle:web_write');
        Route::get('/export',        [App\Http\Controllers\TransactionController::class, 'export'])->middleware('throttle:web_export');
        Route::delete('/{id}',       [App\Http\Controllers\TransactionController::class, 'destroy'])->middleware('throttle:web_write');
    });
});
