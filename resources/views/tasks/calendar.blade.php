@extends('layouts.app')

@section('title', 'Calendário de Tarefas')

@push('styles')
<style>
/* =====================================================
   CALENDAR 2.0 — Vivensi
   Prefixo: cal2- para isolamento total de namespace
   ===================================================== */

.cal2-outer {
    margin: -32px -32px 0 -32px;
    height: calc(100vh - 68px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.cal2-wrap {
    display: grid;
    grid-template-columns: 240px 1fr 260px;
    flex: 1;
    overflow: hidden;
    background: #f8fafc;
}

/* ---------- SIDEBAR ---------- */
.cal2-sidebar {
    background: #fff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    padding: 20px 14px;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

/* ---------- MAIN ---------- */
.cal2-main {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.cal2-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-shrink: 0;
}

.cal2-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

/* ---------- RIGHT PANEL ---------- */
.cal2-right {
    background: #fff;
    border-left: 1px solid #e2e8f0;
    overflow-y: auto;
    padding: 20px 14px;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

/* ---------- SECTION TITLE ---------- */
.cal2-section-title {
    font-size: 0.68rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 10px;
}

/* ---------- STATS ---------- */
.cal2-stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 7px;
}
.cal2-stat {
    background: #f8fafc;
    border-radius: 12px;
    padding: 11px 8px;
    text-align: center;
    border: 1px solid #f1f5f9;
}
.cal2-stat-num {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
    color: #1e293b;
}
.cal2-stat-lbl {
    font-size: 0.67rem;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 3px;
}
.cal2-stat.s-danger .cal2-stat-num { color: #dc2626; }
.cal2-stat.s-success .cal2-stat-num { color: #059669; }
.cal2-stat.s-primary .cal2-stat-num { color: #4f46e5; }

/* ---------- FILTER TOGGLES ---------- */
.cal2-filter-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    cursor: pointer;
    font-size: 0.8rem;
    color: #475569;
    font-weight: 500;
    width: 100%;
    text-align: left;
    transition: all 0.15s;
    margin-bottom: 6px;
}
.cal2-filter-btn:last-child { margin-bottom: 0; }
.cal2-filter-btn.active {
    background: #eef2ff;
    border-color: #4f46e5;
    color: #4f46e5;
}

/* ---------- LEGEND ---------- */
.cal2-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.79rem;
    color: #475569;
    margin-bottom: 6px;
}
.cal2-legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ---------- HEADER BUTTONS ---------- */
.cal2-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
    text-decoration: none;
    line-height: 1.4;
}
.cal2-btn-outline {
    background: #fff;
    color: #475569;
    border: 1.5px solid #e2e8f0;
}
.cal2-btn-outline:hover { border-color: #94a3b8; color: #1e293b; }
.cal2-btn-primary { background: #4f46e5; color: #fff; }
.cal2-btn-primary:hover { background: #4338ca; color: #fff; }
.cal2-btn-icon {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    color: #475569;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    font-size: 1rem;
    line-height: 1;
    text-decoration: none;
}
.cal2-btn-icon:hover { background: #f1f5f9; }

/* ---------- VIEW TABS ---------- */
.cal2-view-tabs {
    display: flex;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 3px;
}
.cal2-view-tab {
    padding: 5px 13px;
    border-radius: 6px;
    font-size: 0.79rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border: none;
    background: none;
    transition: all 0.15s;
}
.cal2-view-tab.active {
    background: #fff;
    color: #4f46e5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* ---------- MONTH TITLE ---------- */
.cal2-month-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    text-transform: capitalize;
    min-width: 150px;
    text-align: center;
}

/* ---------- MONTH GRID ---------- */
.cal2-grid-wrap {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.cal2-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    flex: 1;
}

.cal2-dow {
    background: #f8fafc;
    color: #94a3b8;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 9px 6px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
}
.cal2-dow:last-child { border-right: none; }

.cal2-cell {
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    padding: 5px;
    min-height: 100px;
    position: relative;
    cursor: pointer;
    transition: background 0.1s;
    overflow: hidden;
}
.cal2-cell:nth-child(7n) { border-right: none; }
.cal2-cell:hover { background: #fafafa; }
.cal2-cell.cal2-empty { background: #f8fafc; cursor: default; }
.cal2-cell.cal2-today { background: #f5f3ff; }
.cal2-cell.cal2-weekend .cal2-day-num { color: #94a3b8; }

.cal2-day-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 3px;
}
.cal2-cell.cal2-today .cal2-day-num {
    background: #4f46e5;
    color: #fff;
}

/* ---------- TASK CHIPS ---------- */
.cal2-chip {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px 2px 3px;
    border-radius: 5px;
    margin-bottom: 2px;
    font-size: 0.71rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    max-width: 100%;
    transition: opacity 0.15s, transform 0.1s;
    border-left: 3px solid transparent;
    text-decoration: none;
}
.cal2-chip:hover { opacity: 0.8; transform: translateX(1px); }
.cal2-chip.cal2-done { opacity: 0.45; }
.cal2-chip.cal2-done .cal2-chip-title { text-decoration: line-through; }

.cal2-chip-dot {
    width: 5px; height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cal2-chip-avatar {
    width: 14px; height: 14px;
    border-radius: 50%;
    font-size: 0.56rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}
.cal2-chip-title {
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    color: #1e293b;
}

/* Priority chip variants */
.cal2-chip.p-critical { background: #fef2f2; border-left-color: #dc2626; }
.cal2-chip.p-critical .cal2-chip-dot { background: #dc2626; }
.cal2-chip.p-high     { background: #fff7ed; border-left-color: #ea580c; }
.cal2-chip.p-high     .cal2-chip-dot { background: #ea580c; }
.cal2-chip.p-medium   { background: #f5f3ff; border-left-color: #7c3aed; }
.cal2-chip.p-medium   .cal2-chip-dot { background: #7c3aed; }
.cal2-chip.p-low      { background: #f0fdf4; border-left-color: #059669; }
.cal2-chip.p-low      .cal2-chip-dot { background: #059669; }

.cal2-more {
    font-size: 0.67rem;
    color: #4f46e5;
    font-weight: 600;
    cursor: pointer;
    padding: 1px 3px;
    margin-top: 1px;
}

/* ---------- QUICK ADD ---------- */
.cal2-quick-add {
    position: absolute;
    top: 30px;
    left: 0;
    right: 0;
    z-index: 200;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.14);
    padding: 9px;
    display: none;
    border: 1px solid #e2e8f0;
}
.cal2-quick-add.open { display: block; }
.cal2-qa-input {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 8px;
    font-size: 0.79rem;
    outline: none;
    margin-bottom: 6px;
    font-family: inherit;
}
.cal2-qa-input:focus { border-color: #4f46e5; }
.cal2-qa-actions { display: flex; gap: 5px; }
.cal2-qa-btn {
    flex: 1;
    padding: 5px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: inherit;
    transition: all 0.15s;
}
.cal2-qa-btn.save   { background: #4f46e5; color: #fff; }
.cal2-qa-btn.save:hover { background: #4338ca; }
.cal2-qa-btn.cancel { background: #f1f5f9; color: #64748b; }

/* ---------- WEEK VIEW ---------- */
.cal2-week-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    height: 100%;
}
.cal2-week-col {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 10px 7px;
    overflow-y: auto;
}
.cal2-week-col.cal2-today { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79,70,229,0.1); }
.cal2-week-col-hdr { text-align: center; margin-bottom: 8px; }
.cal2-week-dow { font-size: 0.66rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.cal2-week-num {
    width: 30px; height: 30px;
    border-radius: 50%;
    font-size: 0.95rem;
    font-weight: 700;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 3px;
}
.cal2-week-col.cal2-today .cal2-week-num { background: #4f46e5; color: #fff; }

/* ---------- RIGHT PANEL TASK ITEMS ---------- */
.cal2-task-row {
    display: flex;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
}
.cal2-task-row:last-child { border-bottom: none; }
.cal2-task-row:hover .cal2-task-row-title { color: #4f46e5; }
.cal2-task-bar {
    width: 3px;
    border-radius: 2px;
    flex-shrink: 0;
    align-self: stretch;
    min-height: 36px;
}
.cal2-task-row-body { flex: 1; min-width: 0; }
.cal2-task-row-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: #1e293b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: color 0.1s;
}
.cal2-task-row-meta { font-size: 0.71rem; color: #94a3b8; margin-top: 2px; }

/* ---------- DETAIL SLIDE PANEL ---------- */
.cal2-overlay {
    position: fixed;
    inset: 0;
    z-index: 9900;
    background: rgba(15,23,42,0.35);
    backdrop-filter: blur(2px);
    display: none;
}
.cal2-overlay.open { display: block; }
.cal2-panel {
    position: fixed;
    top: 0; right: -440px;
    width: 420px;
    height: 100vh;
    background: #fff;
    z-index: 9901;
    box-shadow: -6px 0 32px rgba(0,0,0,0.12);
    transition: right 0.28s cubic-bezier(0.4,0,0.2,1);
    display: flex;
    flex-direction: column;
}
.cal2-panel.open { right: 0; }
.cal2-panel-hdr {
    padding: 18px 18px 14px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.cal2-panel-body { flex: 1; overflow-y: auto; padding: 18px; }
.cal2-panel-footer {
    padding: 14px 18px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
}
.cal2-meta-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.83rem;
}
.cal2-meta-row:last-child { border-bottom: none; }
.cal2-meta-key {
    font-size: 0.69rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    width: 88px;
    flex-shrink: 0;
    padding-top: 2px;
}
.cal2-meta-val { color: #1e293b; font-weight: 500; }

/* ---------- STATUS BADGE ---------- */
.cal2-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 0.69rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.s-todo      { background:#f1f5f9; color:#64748b; }
.s-doing, .s-in_progress { background:#eff6ff; color:#2563eb; }
.s-done, .s-completed    { background:#f0fdf4; color:#059669; }
.s-pending   { background:#fffbeb; color:#d97706; }
.s-blocked   { background:#fef2f2; color:#dc2626; }
.s-cancelled { background:#f8fafc; color:#94a3b8; }

/* ---------- RESPONSIVE ---------- */
@media (max-width: 1280px) {
    .cal2-wrap { grid-template-columns: 220px 1fr 0; }
    .cal2-right { display: none; }
}
@media (max-width: 900px) {
    .cal2-wrap { grid-template-columns: 0 1fr 0; }
    .cal2-sidebar { display: none; }
    .cal2-right { display: none; }
    .cal2-panel { width: 100%; right: -100%; }
    .cal2-panel.open { right: 0; }
}
</style>
@endpush

@section('content')
@php
    \Carbon\Carbon::setLocale('pt_BR');
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $now      = \Carbon\Carbon::now();
    $todayStr = $now->format('Y-m-d');

    /* Project color palette (no color field on Project, use hash by ID) */
    $palette = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#14b8a6'];
    $projColor = function ($id) use ($palette) {
        return $id ? $palette[abs((int)$id - 1) % 8] : '#94a3b8';
    };

    /* Priority maps */
    $chipClass  = ['critical'=>'p-critical','high'=>'p-high','medium'=>'p-medium','low'=>'p-low'];
    $prioColor  = ['critical'=>'#dc2626','high'=>'#ea580c','medium'=>'#7c3aed','low'=>'#059669'];
    $prioLabel  = ['critical'=>'Crítica','high'=>'Alta','medium'=>'Média','low'=>'Baixa'];
    $prioDefColor = '#94a3b8';

    /* Status labels */
    $statusLabel = [
        'todo'=>'A Fazer','doing'=>'Em Andamento','in_progress'=>'Em Andamento',
        'done'=>'Concluída','completed'=>'Concluída','pending'=>'Pendente',
        'blocked'=>'Bloqueada','cancelled'=>'Cancelada',
    ];

    /* Stats */
    $statsTotal     = $tasks->count();
    $statsCompleted = $tasks->filter(fn($t) => in_array($t->status, ['done','completed']))->count();
    $statsOverdueCt = $tasks->filter(fn($t) =>
        $t->due_date && $t->due_date->lt($now) &&
        !in_array($t->status, ['done','completed','cancelled'])
    )->count();
    $statsPending   = $statsTotal - $statsCompleted;

    /* Calendar grid */
    $startOfMonth   = $date->copy()->startOfMonth();
    $startDayOfWeek = $startOfMonth->dayOfWeek; // 0=Sun
    $daysInMonth    = $date->daysInMonth;
    $totalCells     = $startDayOfWeek + $daysInMonth;
    $trailing       = (ceil($totalCells / 7) * 7) - $totalCells;

    /* Week view — current week if viewing current month, else first week of month */
    $weekAnchor  = ($date->format('Y-m') === $now->format('Y-m'))
                    ? $now->copy()
                    : $date->copy()->startOfMonth();
    $weekStart   = $weekAnchor->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $weekDays    = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];

    /* Upcoming (next 7 days, not done) */
    $upcoming = $tasks->filter(fn($t) =>
        $t->due_date &&
        $t->due_date->gte($now) &&
        $t->due_date->lte($now->copy()->addDays(7)) &&
        !in_array($t->status, ['done','completed','cancelled'])
    )->sortBy('due_date');
@endphp

<div class="cal2-outer">
<div class="cal2-wrap">

{{-- ===================== SIDEBAR ===================== --}}
<aside class="cal2-sidebar">

    {{-- Stats --}}
    <div>
        <div class="cal2-section-title">{{ $date->translatedFormat('F Y') }}</div>
        <div class="cal2-stat-grid">
            <div class="cal2-stat s-primary">
                <div class="cal2-stat-num">{{ $statsTotal }}</div>
                <div class="cal2-stat-lbl">Total</div>
            </div>
            <div class="cal2-stat s-success">
                <div class="cal2-stat-num">{{ $statsCompleted }}</div>
                <div class="cal2-stat-lbl">Concluídas</div>
            </div>
            <div class="cal2-stat">
                <div class="cal2-stat-num">{{ $statsPending }}</div>
                <div class="cal2-stat-lbl">Pendentes</div>
            </div>
            <div class="cal2-stat {{ $statsOverdueCt > 0 ? 's-danger' : 's-success' }}">
                <div class="cal2-stat-num">{{ $statsOverdueCt }}</div>
                <div class="cal2-stat-lbl">Atrasadas</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div>
        <div class="cal2-section-title">Filtros</div>
        <button class="cal2-filter-btn" id="f-done" onclick="toggleFilter('done')">
            ✅ Mostrar concluídas
        </button>
        <button class="cal2-filter-btn" id="f-overdue" onclick="toggleFilter('overdue')">
            🔴 Somente atrasadas
        </button>
    </div>

    {{-- Priority legend --}}
    <div>
        <div class="cal2-section-title">Prioridade</div>
        <div class="cal2-legend-item"><span class="cal2-legend-dot" style="background:#dc2626"></span> Crítica</div>
        <div class="cal2-legend-item"><span class="cal2-legend-dot" style="background:#ea580c"></span> Alta</div>
        <div class="cal2-legend-item"><span class="cal2-legend-dot" style="background:#7c3aed"></span> Média</div>
        <div class="cal2-legend-item"><span class="cal2-legend-dot" style="background:#059669"></span> Baixa</div>
    </div>

    {{-- Bottom links --}}
    <div style="margin-top:auto; display:flex; flex-direction:column; gap:7px;">
        <a href="{{ $basePath }}/tasks" class="cal2-btn cal2-btn-outline" style="justify-content:center;">
            📋 Lista de Tarefas
        </a>
        <a href="{{ $basePath }}/tasks/create" class="cal2-btn cal2-btn-outline" style="justify-content:center;">
            ＋ Nova Tarefa
        </a>
    </div>

</aside>

{{-- ===================== MAIN ===================== --}}
<main class="cal2-main">

    {{-- Header --}}
    <div class="cal2-header">
        <div style="display:flex;align-items:center;gap:6px;">
            <a href="?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="cal2-btn-icon" title="Mês anterior">&#8249;</a>
            <span class="cal2-month-title">{{ $date->translatedFormat('F Y') }}</span>
            <a href="?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="cal2-btn-icon" title="Próximo mês">&#8250;</a>
            <a href="{{ $basePath }}/tasks/calendar" class="cal2-btn cal2-btn-outline" style="margin-left:4px;font-size:0.76rem;">Hoje</a>
        </div>

        <div class="cal2-view-tabs">
            <button class="cal2-view-tab active" id="tab-month" onclick="switchView('month')">Mês</button>
            <button class="cal2-view-tab" id="tab-week" onclick="switchView('week')">Semana</button>
        </div>

        <a href="{{ $basePath }}/tasks/create?redirect_to_schedule=1" class="cal2-btn cal2-btn-primary">
            <span style="font-size:1.1rem;line-height:1;">+</span> Nova Tarefa
        </a>
    </div>

    {{-- ====== MONTH VIEW ====== --}}
    <div class="cal2-body" id="view-month">
        <div class="cal2-grid-wrap">
            <div class="cal2-grid">

                {{-- Day headers --}}
                <div class="cal2-dow">DOM</div>
                <div class="cal2-dow">SEG</div>
                <div class="cal2-dow">TER</div>
                <div class="cal2-dow">QUA</div>
                <div class="cal2-dow">QUI</div>
                <div class="cal2-dow">SEX</div>
                <div class="cal2-dow">SÁB</div>

                {{-- Leading empty cells --}}
                @for($i = 0; $i < $startDayOfWeek; $i++)
                    <div class="cal2-cell cal2-empty"></div>
                @endfor

                {{-- Day cells --}}
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dayCrbn   = $date->copy()->day($day);
                        $dayStr    = $dayCrbn->format('Y-m-d');
                        $isToday   = ($dayStr === $todayStr);
                        $isWeekend = in_array($dayCrbn->dayOfWeek, [0, 6]);
                        $dayTasks  = $tasks->filter(
                            fn($t) => $t->due_date && $t->due_date->format('Y-m-d') === $dayStr
                        );
                        $maxChips  = 3;
                        $extra     = max(0, $dayTasks->count() - $maxChips);
                    @endphp

                    <div class="cal2-cell
                         {{ $isToday   ? 'cal2-today'   : '' }}
                         {{ $isWeekend ? 'cal2-weekend' : '' }}"
                         data-date="{{ $dayStr }}"
                         onclick="onCellClick(event,'{{ $dayStr }}')">

                        <div class="cal2-day-num">{{ $day }}</div>

                        @foreach($dayTasks->take($maxChips) as $task)
                            @php
                                $cc      = $chipClass[$task->priority] ?? 'p-low';
                                $isDone  = in_array($task->status, ['done','completed','cancelled']);
                                $initial = $task->assignee ? mb_strtoupper(mb_substr($task->assignee->name,0,1)) : '?';
                                $aColor  = $projColor($task->assignee?->id);
                                $rawDue  = $task->due_date ? $task->due_date->format('Y-m-d') : '';
                            @endphp
                            <div class="cal2-chip {{ $cc }} {{ $isDone ? 'cal2-done' : '' }} cal2-task-item"
                                 onclick="event.stopPropagation(); openPanel(this)"
                                 data-id="{{ $task->id }}"
                                 data-title="{{ e($task->title) }}"
                                 data-desc="{{ e($task->description ?? '') }}"
                                 data-status="{{ $task->status }}"
                                 data-priority="{{ $task->priority }}"
                                 data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                                 data-rawdue="{{ $rawDue }}"
                                 data-assigned="{{ e($task->assignee->name ?? 'Não atribuído') }}"
                                 data-project="{{ e($task->project->name ?? '') }}"
                                 data-edit="{{ url('/tasks/create') }}?edit={{ $task->id }}">
                                <span class="cal2-chip-dot"></span>
                                <span class="cal2-chip-avatar" style="background:{{ $aColor }}">{{ $initial }}</span>
                                <span class="cal2-chip-title">{{ $task->title }}</span>
                            </div>
                        @endforeach

                        @if($extra > 0)
                            <div class="cal2-more" onclick="event.stopPropagation()">+{{ $extra }} mais</div>
                        @endif

                        {{-- Quick-add form --}}
                        <div class="cal2-quick-add" id="qa-{{ $dayStr }}" onclick="event.stopPropagation()">
                            <form method="POST" action="{{ url('/tasks') }}">
                                @csrf
                                <input type="hidden" name="due_date" value="{{ $dayStr }}">
                                <input type="hidden" name="status"   value="todo">
                                <input type="hidden" name="priority" value="medium">
                                <input type="hidden" name="redirect_to_schedule" value="1">
                                <input class="cal2-qa-input" type="text" name="title"
                                       placeholder="Título da tarefa..." required autocomplete="off">
                                <div class="cal2-qa-actions">
                                    <button type="submit" class="cal2-qa-btn save">Criar</button>
                                    <button type="button" class="cal2-qa-btn cancel"
                                            onclick="closeQA('{{ $dayStr }}')">Cancelar</button>
                                </div>
                            </form>
                        </div>

                    </div>
                @endfor

                {{-- Trailing empty cells --}}
                @for($i = 0; $i < $trailing; $i++)
                    <div class="cal2-cell cal2-empty"></div>
                @endfor

            </div>
        </div>
    </div>

    {{-- ====== WEEK VIEW ====== --}}
    <div class="cal2-body" id="view-week" style="display:none;">
        <div class="cal2-week-grid">
            @for($d = 0; $d < 7; $d++)
                @php
                    $wDay     = $weekStart->copy()->addDays($d);
                    $wStr     = $wDay->format('Y-m-d');
                    $wIsToday = ($wStr === $todayStr);
                    $wTasks   = $tasks->filter(fn($t) => $t->due_date && $t->due_date->format('Y-m-d') === $wStr);
                @endphp
                <div class="cal2-week-col {{ $wIsToday ? 'cal2-today' : '' }}">
                    <div class="cal2-week-col-hdr">
                        <div class="cal2-week-dow">{{ $weekDays[$d] }}</div>
                        <div class="cal2-week-num">{{ $wDay->day }}</div>
                    </div>
                    @forelse($wTasks as $task)
                        @php
                            $cc     = $chipClass[$task->priority] ?? 'p-low';
                            $isDone = in_array($task->status, ['done','completed','cancelled']);
                        @endphp
                        <div class="cal2-chip {{ $cc }} {{ $isDone ? 'cal2-done' : '' }} cal2-task-item"
                             style="margin-bottom:4px;"
                             onclick="openPanel(this)"
                             data-id="{{ $task->id }}"
                             data-title="{{ e($task->title) }}"
                             data-desc="{{ e($task->description ?? '') }}"
                             data-status="{{ $task->status }}"
                             data-priority="{{ $task->priority }}"
                             data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                             data-rawdue="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                             data-assigned="{{ e($task->assignee->name ?? 'Não atribuído') }}"
                             data-project="{{ e($task->project->name ?? '') }}"
                             data-edit="{{ url('/tasks/create') }}?edit={{ $task->id }}">
                            <span class="cal2-chip-dot"></span>
                            <span class="cal2-chip-title">{{ $task->title }}</span>
                        </div>
                    @empty
                        <div style="font-size:0.71rem;color:#cbd5e1;text-align:center;margin-top:10px;">—</div>
                    @endforelse
                </div>
            @endfor
        </div>
    </div>

</main>

{{-- ===================== RIGHT PANEL ===================== --}}
<aside class="cal2-right">

    {{-- Upcoming 7 days --}}
    <div>
        <div class="cal2-section-title">Próximos 7 dias</div>
        @forelse($upcoming as $task)
            @php $bar = $prioColor[$task->priority] ?? $prioDefColor; @endphp
            <div class="cal2-task-row cal2-task-item"
                 onclick="openPanel(this)"
                 data-id="{{ $task->id }}"
                 data-title="{{ e($task->title) }}"
                 data-desc="{{ e($task->description ?? '') }}"
                 data-status="{{ $task->status }}"
                 data-priority="{{ $task->priority }}"
                 data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                 data-rawdue="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                 data-assigned="{{ e($task->assignee->name ?? 'Não atribuído') }}"
                 data-project="{{ e($task->project->name ?? '') }}"
                 data-edit="{{ url('/tasks/create') }}?edit={{ $task->id }}">
                <div class="cal2-task-bar" style="background:{{ $bar }}"></div>
                <div class="cal2-task-row-body">
                    <div class="cal2-task-row-title">{{ $task->title }}</div>
                    <div class="cal2-task-row-meta">
                        📅 {{ $task->due_date->translatedFormat('d M') }}
                        @if($task->assignee) · {{ Str::limit($task->assignee->name, 16) }} @endif
                    </div>
                </div>
            </div>
        @empty
            <p style="font-size:0.79rem;color:#94a3b8;text-align:center;padding:14px 0;">
                Nenhuma tarefa nos próximos 7 dias
            </p>
        @endforelse
    </div>

    {{-- Overdue --}}
    @if(isset($overdueTasks) && $overdueTasks->isNotEmpty())
    <div>
        <div class="cal2-section-title" style="color:#dc2626;">⚠ Atrasadas</div>
        @foreach($overdueTasks as $task)
            @php $bar = $prioColor[$task->priority] ?? '#dc2626'; @endphp
            <div class="cal2-task-row cal2-task-item"
                 onclick="openPanel(this)"
                 data-id="{{ $task->id }}"
                 data-title="{{ e($task->title) }}"
                 data-desc="{{ e($task->description ?? '') }}"
                 data-status="{{ $task->status }}"
                 data-priority="{{ $task->priority }}"
                 data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                 data-rawdue="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
                 data-assigned="{{ e($task->assignee->name ?? 'Não atribuído') }}"
                 data-project="{{ e($task->project->name ?? '') }}"
                 data-edit="{{ url('/tasks/create') }}?edit={{ $task->id }}">
                <div class="cal2-task-bar" style="background:{{ $bar }}"></div>
                <div class="cal2-task-row-body">
                    <div class="cal2-task-row-title" style="color:#dc2626;">{{ $task->title }}</div>
                    <div class="cal2-task-row-meta">
                        Venceu {{ $task->due_date->translatedFormat('d M') }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @elseif($statsOverdueCt === 0)
    <div style="text-align:center;padding:18px 0;">
        <div style="font-size:1.8rem;">✅</div>
        <div style="font-size:0.79rem;color:#059669;font-weight:700;margin-top:6px;">Sem atrasos!</div>
    </div>
    @endif

</aside>

</div>{{-- .cal2-wrap --}}
</div>{{-- .cal2-outer --}}

{{-- ===================== DETAIL PANEL ===================== --}}
<div class="cal2-overlay" id="cal2-overlay" onclick="closePanel()"></div>
<div class="cal2-panel" id="cal2-panel">
    <div class="cal2-panel-hdr">
        <div style="flex:1;">
            <span id="dp-badge" class="cal2-badge" style="margin-bottom:7px;display:inline-flex;"></span>
            <div id="dp-title" style="font-size:1rem;font-weight:700;color:#1e293b;line-height:1.35;"></div>
        </div>
        <button onclick="closePanel()"
                style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.1rem;padding:4px;flex-shrink:0;line-height:1;">✕</button>
    </div>
    <div class="cal2-panel-body">
        <p id="dp-desc" style="font-size:0.85rem;color:#64748b;line-height:1.65;margin:0 0 14px;"></p>
        <div>
            <div class="cal2-meta-row">
                <span class="cal2-meta-key">Status</span>
                <span id="dp-status" class="cal2-meta-val"></span>
            </div>
            <div class="cal2-meta-row">
                <span class="cal2-meta-key">Vencimento</span>
                <span id="dp-due" class="cal2-meta-val"></span>
            </div>
            <div class="cal2-meta-row">
                <span class="cal2-meta-key">Responsável</span>
                <span id="dp-assigned" class="cal2-meta-val"></span>
            </div>
            <div class="cal2-meta-row" id="dp-proj-row">
                <span class="cal2-meta-key">Projeto</span>
                <span id="dp-project" class="cal2-meta-val"></span>
            </div>
        </div>
    </div>
    <div class="cal2-panel-footer">
        <a id="dp-edit" href="#" class="cal2-btn cal2-btn-primary" style="flex:1;justify-content:center;">✏️ Editar</a>
        <button onclick="closePanel()" class="cal2-btn cal2-btn-outline" style="flex:1;justify-content:center;">Fechar</button>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    /* ---------- HELPERS ---------- */
    var statusLabels  = {
        todo:'A Fazer', doing:'Em Andamento', in_progress:'Em Andamento',
        done:'Concluída', completed:'Concluída', pending:'Pendente',
        blocked:'Bloqueada', cancelled:'Cancelada'
    };
    var prioLabels    = { critical:'Crítica', high:'Alta', medium:'Média', low:'Baixa' };
    var prioColors    = { critical:'#dc2626', high:'#ea580c', medium:'#7c3aed', low:'#059669' };
    var prioBg        = { critical:'#fef2f2', high:'#fff7ed', medium:'#f5f3ff', low:'#f0fdf4' };

    /* ---------- VIEW SWITCH ---------- */
    window.switchView = function (v) {
        document.getElementById('view-month').style.display = v === 'month' ? '' : 'none';
        document.getElementById('view-week').style.display  = v === 'week'  ? '' : 'none';
        document.getElementById('tab-month').classList.toggle('active', v === 'month');
        document.getElementById('tab-week').classList.toggle('active',  v === 'week');
    };

    /* ---------- DETAIL PANEL ---------- */
    window.openPanel = function (el) {
        var d   = el.dataset;
        var p   = d.priority || 'low';
        var col = prioColors[p] || '#94a3b8';
        var bg  = prioBg[p]    || '#f8fafc';

        document.getElementById('dp-title').textContent    = d.title    || '';
        document.getElementById('dp-desc').textContent     = d.desc     || 'Sem descrição.';
        document.getElementById('dp-due').textContent      = d.due      || '—';
        document.getElementById('dp-assigned').textContent = d.assigned || '—';
        document.getElementById('dp-edit').href            = d.edit     || '#';

        /* Status badge */
        var stEl = document.getElementById('dp-status');
        stEl.textContent = statusLabels[d.status] || d.status || '';
        stEl.className   = 'cal2-meta-val cal2-badge s-' + (d.status || 'todo');

        /* Priority badge */
        var bdEl = document.getElementById('dp-badge');
        bdEl.textContent = '● ' + (prioLabels[p] || p);
        bdEl.style.cssText = 'background:' + bg + ';color:' + col +
            ';display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;' +
            'font-size:0.69rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:7px;';

        /* Project row */
        var projRow = document.getElementById('dp-proj-row');
        var projEl  = document.getElementById('dp-project');
        if (d.project && d.project.trim() !== '') {
            projEl.textContent      = d.project;
            projRow.style.display   = '';
        } else {
            projRow.style.display   = 'none';
        }

        document.getElementById('cal2-panel').classList.add('open');
        document.getElementById('cal2-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closePanel = function () {
        document.getElementById('cal2-panel').classList.remove('open');
        document.getElementById('cal2-overlay').classList.remove('open');
        document.body.style.overflow = '';
    };

    /* ---------- QUICK ADD ---------- */
    var activeQA = null;

    window.onCellClick = function (e, dateStr) {
        /* Ignore clicks on chips, more button, or the form itself */
        if (e.target.closest('.cal2-chip, .cal2-more, .cal2-quick-add')) return;
        if (activeQA === dateStr) { closeQA(dateStr); return; }
        if (activeQA) closeQA(activeQA);
        var form = document.getElementById('qa-' + dateStr);
        if (!form) return;
        form.classList.add('open');
        var inp = form.querySelector('input[name="title"]');
        if (inp) { inp.value = ''; inp.focus(); }
        activeQA = dateStr;
    };

    window.closeQA = function (dateStr) {
        var el = document.getElementById('qa-' + dateStr);
        if (el) el.classList.remove('open');
        if (activeQA === dateStr) activeQA = null;
    };

    /* Close QA on outside click */
    document.addEventListener('click', function (e) {
        if (!activeQA) return;
        if (!e.target.closest('.cal2-quick-add') && !e.target.closest('.cal2-cell')) {
            closeQA(activeQA);
        }
    });

    /* ---------- FILTERS ---------- */
    var filters = { done: false, overdue: false };

    window.toggleFilter = function (key) {
        /* overdue and done are mutually exclusive for clarity */
        if (key === 'overdue' && !filters.overdue) filters.done = false;
        filters[key] = !filters[key];

        document.getElementById('f-done').classList.toggle('active',    filters.done);
        document.getElementById('f-overdue').classList.toggle('active', filters.overdue);
        applyFilters();
    };

    function applyFilters() {
        var todayRaw = '{{ $todayStr }}';
        document.querySelectorAll('.cal2-task-item').forEach(function (el) {
            var status  = el.dataset.status || '';
            var rawDue  = el.dataset.rawdue || '';
            var isDone  = ['done','completed','cancelled'].indexOf(status) !== -1;
            var isPast  = rawDue && rawDue < todayRaw && !isDone;
            var hide    = false;

            if (!filters.done && isDone)        hide = true;
            if (filters.overdue && !isPast)     hide = true;
            if (filters.overdue && isDone)      hide = true;

            el.style.display = hide ? 'none' : '';
        });
    }

    /* On load: hide done tasks by default */
    applyFilters();

    /* ---------- KEYBOARD ---------- */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePanel();
            if (activeQA) closeQA(activeQA);
        }
    });

})();
</script>
@endpush
