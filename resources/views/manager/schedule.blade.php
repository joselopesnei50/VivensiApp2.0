@extends('layouts.app')
@section('title', 'Agenda da Equipe')

@push('styles')
<style>
/* ===================== MANAGER SCHEDULE 2.0 ===================== */
.ms-outer {
    margin: -32px -32px 0 -32px;
    height: calc(100vh - 68px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.ms-wrap {
    display: grid;
    grid-template-columns: 260px 1fr 270px;
    flex: 1;
    overflow: hidden;
    background: #f8fafc;
}

/* ── SIDEBAR ── */
.ms-sidebar {
    background: #fff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    padding: 18px 14px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.ms-section-title {
    font-size: 0.68rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 10px;
}

/* ── STATS ── */
.ms-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
.ms-stat {
    background: #f8fafc;
    border-radius: 12px;
    padding: 11px 8px;
    text-align: center;
    border: 1px solid #f1f5f9;
}
.ms-stat-num { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1e293b; }
.ms-stat-lbl { font-size: 0.67rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 3px; }
.ms-stat.s-danger  .ms-stat-num { color: #dc2626; }
.ms-stat.s-success .ms-stat-num { color: #059669; }
.ms-stat.s-primary .ms-stat-num { color: #4f46e5; }
.ms-stat.s-amber   .ms-stat-num { color: #d97706; }

/* ── TEAM FILTER ── */
.ms-member-chip {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border-radius: 10px;
    cursor: pointer;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    margin-bottom: 6px;
    text-decoration: none;
    transition: all 0.15s;
    color: #475569;
    font-size: 0.81rem;
    font-weight: 500;
}
.ms-member-chip:hover  { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; }
.ms-member-chip.active { border-color: #4f46e5; background: #eef2ff; color: #4f46e5; }
.ms-member-chip.all-chip { font-weight: 600; }
.ms-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

/* ── LEGEND ── */
.ms-legend-item { display: flex; align-items: center; gap: 8px; font-size: 0.79rem; color: #475569; margin-bottom: 6px; }
.ms-legend-dot  { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

/* ── MAIN ── */
.ms-main { display: flex; flex-direction: column; overflow: hidden; }
.ms-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-shrink: 0;
}
.ms-body { flex: 1; overflow-y: auto; padding: 16px; }
.ms-month-title { font-size: 1rem; font-weight: 700; color: #1e293b; text-transform: capitalize; min-width: 160px; text-align: center; }
.ms-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
.ms-btn-outline { background: #fff; color: #475569; border: 1.5px solid #e2e8f0; }
.ms-btn-outline:hover { border-color: #94a3b8; color: #1e293b; }
.ms-btn-primary { background: #4f46e5; color: #fff; }
.ms-btn-primary:hover { background: #4338ca; color: #fff; }
.ms-btn-icon { background: #fff; border: 1.5px solid #e2e8f0; color: #475569; padding: 6px 10px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; font-size: 1rem; line-height: 1; transition: all 0.15s; text-decoration: none; }
.ms-btn-icon:hover { background: #f1f5f9; }

/* ── VIEW TABS ── */
.ms-tabs { display: flex; background: #f1f5f9; border-radius: 8px; padding: 3px; }
.ms-tab { padding: 5px 13px; border-radius: 6px; font-size: 0.79rem; font-weight: 600; color: #64748b; cursor: pointer; border: none; background: none; transition: all 0.15s; }
.ms-tab.active { background: #fff; color: #4f46e5; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }

/* ── MONTH GRID ── */
.ms-grid-wrap { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; }
.ms-grid { display: grid; grid-template-columns: repeat(7, 1fr); flex: 1; }
.ms-dow { background: #f8fafc; color: #94a3b8; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: 9px 6px; text-align: center; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
.ms-dow:last-child { border-right: none; }
.ms-cell { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 5px; min-height: 100px; overflow: hidden; }
.ms-cell:nth-child(7n) { border-right: none; }
.ms-cell.empty { background: #f8fafc; }
.ms-cell.today  { background: #f5f3ff; }
.ms-cell.weekend .ms-day-num { color: #94a3b8; }
.ms-day-num { width: 24px; height: 24px; border-radius: 50%; font-size: 0.78rem; font-weight: 600; color: #475569; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 3px; }
.ms-cell.today .ms-day-num { background: #4f46e5; color: #fff; }

/* ── CHIPS ── */
.ms-chip { display: flex; align-items: center; gap: 4px; padding: 2px 6px 2px 3px; border-radius: 5px; margin-bottom: 2px; font-size: 0.71rem; font-weight: 500; cursor: pointer; white-space: nowrap; overflow: hidden; max-width: 100%; transition: opacity 0.1s, transform 0.1s; border-left: 3px solid transparent; }
.ms-chip:hover { opacity: 0.8; transform: translateX(1px); }
.ms-chip.done  { opacity: 0.45; }
.ms-chip.done .ms-chip-title { text-decoration: line-through; }
.ms-chip-dot   { width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0; }
.ms-chip-av    { width: 14px; height: 14px; border-radius: 50%; font-size: 0.56rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.ms-chip-title { overflow: hidden; text-overflow: ellipsis; flex: 1; color: #1e293b; }
.ms-chip.p-critical { background: #fef2f2; border-left-color: #dc2626; }
.ms-chip.p-critical .ms-chip-dot { background: #dc2626; }
.ms-chip.p-high     { background: #fff7ed; border-left-color: #ea580c; }
.ms-chip.p-high     .ms-chip-dot { background: #ea580c; }
.ms-chip.p-medium   { background: #f5f3ff; border-left-color: #7c3aed; }
.ms-chip.p-medium   .ms-chip-dot { background: #7c3aed; }
.ms-chip.p-low      { background: #f0fdf4; border-left-color: #059669; }
.ms-chip.p-low      .ms-chip-dot { background: #059669; }
.ms-more { font-size: 0.67rem; color: #4f46e5; font-weight: 600; cursor: pointer; padding: 1px 3px; margin-top: 1px; }

/* ── WEEK VIEW ── */
.ms-week-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; height: 100%; }
.ms-week-col { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 7px; overflow-y: auto; }
.ms-week-col.today { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79,70,229,0.1); }
.ms-week-hdr { text-align: center; margin-bottom: 8px; }
.ms-week-dow { font-size: 0.66rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.ms-week-num { width: 30px; height: 30px; border-radius: 50%; font-size: 0.95rem; font-weight: 700; color: #475569; display: inline-flex; align-items: center; justify-content: center; margin-top: 3px; }
.ms-week-col.today .ms-week-num { background: #4f46e5; color: #fff; }

/* ── RIGHT PANEL ── */
.ms-right { background: #fff; border-left: 1px solid #e2e8f0; overflow-y: auto; padding: 18px 14px; display: flex; flex-direction: column; gap: 20px; }
.ms-task-row { display: flex; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
.ms-task-row:last-child { border-bottom: none; }
.ms-task-row:hover .ms-task-title { color: #4f46e5; }
.ms-task-bar { width: 3px; border-radius: 2px; flex-shrink: 0; align-self: stretch; min-height: 36px; }
.ms-task-body { flex: 1; min-width: 0; }
.ms-task-title { font-size: 0.8rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; transition: color 0.1s; }
.ms-task-meta  { font-size: 0.71rem; color: #94a3b8; margin-top: 2px; }

/* ── DETAIL PANEL ── */
.ms-overlay { position: fixed; inset: 0; z-index: 9900; background: rgba(15,23,42,0.35); backdrop-filter: blur(2px); display: none; }
.ms-overlay.open { display: block; }
.ms-panel { position: fixed; top: 0; right: -440px; width: 420px; height: 100vh; background: #fff; z-index: 9901; box-shadow: -6px 0 32px rgba(0,0,0,0.12); transition: right 0.28s cubic-bezier(0.4,0,0.2,1); display: flex; flex-direction: column; }
.ms-panel.open { right: 0; }
.ms-panel-hdr { padding: 18px 18px 14px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 10px; }
.ms-panel-body { flex: 1; overflow-y: auto; padding: 18px; }
.ms-panel-footer { padding: 14px 18px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
.ms-meta-row { display: flex; align-items: flex-start; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.83rem; }
.ms-meta-row:last-child { border-bottom: none; }
.ms-meta-key { font-size: 0.69rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; width: 88px; flex-shrink: 0; padding-top: 2px; }
.ms-meta-val { color: #1e293b; font-weight: 500; }
.ms-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 0.69rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.s-todo        { background: #f1f5f9; color: #64748b; }
.s-doing,.s-in_progress { background: #eff6ff; color: #2563eb; }
.s-done,.s-completed    { background: #f0fdf4; color: #059669; }
.s-pending     { background: #fffbeb; color: #d97706; }
.s-blocked     { background: #fef2f2; color: #dc2626; }
.s-cancelled   { background: #f8fafc; color: #94a3b8; }

/* ── RESPONSIVE ── */
@media (max-width: 1280px) { .ms-wrap { grid-template-columns: 240px 1fr 0; } .ms-right { display: none; } }
@media (max-width: 900px)  { .ms-wrap { grid-template-columns: 0 1fr 0; } .ms-sidebar { display: none; } .ms-right { display: none; } .ms-panel { width: 100%; right: -100%; } .ms-panel.open { right: 0; } }
</style>
@endpush

@section('content')
@php
    \Carbon\Carbon::setLocale('pt_BR');
    $basePath  = rtrim(request()->getBaseUrl(), '/');
    $now       = \Carbon\Carbon::now();
    $todayStr  = $now->format('Y-m-d');

    /* Paleta de avatares por ID de usuário */
    $avPalette = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#14b8a6','#f97316','#06b6d4'];
    $avColor   = fn($id) => $id ? $avPalette[abs((int)$id - 1) % 10] : '#94a3b8';

    /* Priority helpers */
    $chipClass = ['critical'=>'p-critical','high'=>'p-high','medium'=>'p-medium','low'=>'p-low'];
    $prioColor = ['critical'=>'#dc2626','high'=>'#ea580c','medium'=>'#7c3aed','low'=>'#059669'];
    $prioLabel = ['critical'=>'Crítica','high'=>'Alta','medium'=>'Média','low'=>'Baixa'];
    $statusLabel = ['todo'=>'A Fazer','doing'=>'Em Andamento','in_progress'=>'Em Andamento','done'=>'Concluída','completed'=>'Concluída','pending'=>'Pendente','blocked'=>'Bloqueada','cancelled'=>'Cancelada'];

    /* Stats */
    $statsTotal     = $tasks->count();
    $statsCompleted = $tasks->filter(fn($t) => in_array($t->status, ['done','completed']))->count();
    $statsOverdue   = $tasks->filter(fn($t) => $t->due_date && $t->due_date->lt($now) && !in_array($t->status, ['done','completed','cancelled']))->count();
    $statsPending   = $statsTotal - $statsCompleted;

    /* Calendar grid */
    $startOfMonth   = $date->copy()->startOfMonth();
    $startDOW       = $startOfMonth->dayOfWeek;
    $daysInMonth    = $date->daysInMonth;
    $totalCells     = $startDOW + $daysInMonth;
    $trailing       = (ceil($totalCells / 7) * 7) - $totalCells;

    /* Week view */
    $weekAnchor  = ($date->format('Y-m') === $now->format('Y-m')) ? $now->copy() : $date->copy()->startOfMonth();
    $weekStart   = $weekAnchor->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $weekDays    = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];

    /* Upcoming (next 7 days) */
    $upcoming = $tasks->filter(fn($t) =>
        $t->due_date && $t->due_date->gte($now) &&
        $t->due_date->lte($now->copy()->addDays(7)) &&
        !in_array($t->status, ['done','completed','cancelled'])
    )->sortBy('due_date');

    /* Filter URL helper */
    $memberUrl = fn($memberId) => $basePath . '/manager/schedule?date=' . $date->format('Y-m-d') . ($memberId ? '&member=' . $memberId : '');
@endphp

<div class="ms-outer">
<div class="ms-wrap">

{{-- ================ SIDEBAR ================ --}}
<aside class="ms-sidebar">

    {{-- Stats --}}
    <div>
        <div class="ms-section-title">{{ $date->translatedFormat('F Y') }}</div>
        <div class="ms-stat-grid">
            <div class="ms-stat s-primary"><div class="ms-stat-num">{{ $statsTotal }}</div><div class="ms-stat-lbl">Total</div></div>
            <div class="ms-stat s-success"><div class="ms-stat-num">{{ $statsCompleted }}</div><div class="ms-stat-lbl">Concluídas</div></div>
            <div class="ms-stat"><div class="ms-stat-num">{{ $statsPending }}</div><div class="ms-stat-lbl">Pendentes</div></div>
            <div class="ms-stat {{ $statsOverdue > 0 ? 's-danger' : 's-success' }}"><div class="ms-stat-num">{{ $statsOverdue }}</div><div class="ms-stat-lbl">Atrasadas</div></div>
        </div>
    </div>

    {{-- Team filter --}}
    <div>
        <div class="ms-section-title">Filtrar por membro</div>
        <a href="{{ $memberUrl(null) }}" class="ms-member-chip all-chip {{ !$filterMember ? 'active' : '' }}">
            <span class="ms-avatar" style="background:#4f46e5;font-size:0.75rem;">👥</span>
            Toda a equipe
        </a>
        @foreach($teamMembers as $member)
            @php $initial = mb_strtoupper(mb_substr($member->name, 0, 1)); @endphp
            <a href="{{ $memberUrl($member->id) }}"
               class="ms-member-chip {{ (string)$filterMember === (string)$member->id ? 'active' : '' }}">
                <span class="ms-avatar" style="background:{{ $avColor($member->id) }}">{{ $initial }}</span>
                {{ Str::limit($member->name, 20) }}
            </a>
        @endforeach
    </div>

    {{-- Priority legend --}}
    <div>
        <div class="ms-section-title">Prioridade</div>
        <div class="ms-legend-item"><span class="ms-legend-dot" style="background:#dc2626"></span> Crítica</div>
        <div class="ms-legend-item"><span class="ms-legend-dot" style="background:#ea580c"></span> Alta</div>
        <div class="ms-legend-item"><span class="ms-legend-dot" style="background:#7c3aed"></span> Média</div>
        <div class="ms-legend-item"><span class="ms-legend-dot" style="background:#059669"></span> Baixa</div>
    </div>

    {{-- Sem prazo --}}
    @if($noDateTasks->isNotEmpty())
    <div>
        <div class="ms-section-title">⏳ Sem prazo ({{ $noDateTasks->count() }})</div>
        @foreach($noDateTasks->take(5) as $t)
            @php $bar = $prioColor[$t->priority] ?? '#94a3b8'; @endphp
            <div class="ms-task-row" onclick="openPanel(this)"
                 data-id="{{ $t->id }}"
                 data-title="{{ e($t->title) }}"
                 data-desc="{{ e($t->description ?? '') }}"
                 data-status="{{ $t->status }}"
                 data-priority="{{ $t->priority }}"
                 data-due=""
                 data-assigned="{{ e($t->assignee->name ?? '—') }}"
                 data-creator="{{ e($t->creator->name ?? '—') }}"
                 data-project="{{ e($t->project->name ?? '') }}">
                <div class="ms-task-bar" style="background:{{ $bar }}"></div>
                <div class="ms-task-body">
                    <div class="ms-task-title">{{ $t->title }}</div>
                    <div class="ms-task-meta">{{ $t->assignee->name ?? '—' }}</div>
                </div>
            </div>
        @endforeach
        @if($noDateTasks->count() > 5)
            <div style="font-size:0.72rem;color:#4f46e5;margin-top:4px;font-weight:600;">+ {{ $noDateTasks->count() - 5 }} mais</div>
        @endif
    </div>
    @endif

    {{-- Bottom links --}}
    <div style="margin-top:auto;display:flex;flex-direction:column;gap:7px;">
        <a href="{{ $basePath }}/tasks" class="ms-btn ms-btn-outline" style="justify-content:center;">📋 Lista de Tarefas</a>
        <a href="{{ $basePath }}/tasks/create?redirect_to_schedule=1" class="ms-btn ms-btn-outline" style="justify-content:center;">＋ Nova Tarefa</a>
    </div>

</aside>

{{-- ================ MAIN ================ --}}
<main class="ms-main">
    <div class="ms-header">
        <div style="display:flex;align-items:center;gap:6px;">
            <a href="?date={{ $date->copy()->subMonth()->format('Y-m-d') }}{{ $filterMember ? '&member='.$filterMember : '' }}" class="ms-btn-icon">&#8249;</a>
            <span class="ms-month-title">{{ $date->translatedFormat('F Y') }}</span>
            <a href="?date={{ $date->copy()->addMonth()->format('Y-m-d') }}{{ $filterMember ? '&member='.$filterMember : '' }}" class="ms-btn-icon">&#8250;</a>
            <a href="{{ $basePath }}/manager/schedule{{ $filterMember ? '?member='.$filterMember : '' }}" class="ms-btn ms-btn-outline" style="margin-left:4px;font-size:0.76rem;">Hoje</a>
        </div>

        <div class="ms-tabs">
            <button class="ms-tab active" id="tab-month" onclick="switchView('month')">Mês</button>
            <button class="ms-tab"        id="tab-week"  onclick="switchView('week')">Semana</button>
        </div>

        <a href="{{ $basePath }}/tasks/create?redirect_to_schedule=1" class="ms-btn ms-btn-primary">
            <span style="font-size:1.1rem;line-height:1;">+</span> Nova Tarefa
        </a>
    </div>

    {{-- MONTH VIEW --}}
    <div class="ms-body" id="view-month">
        <div class="ms-grid-wrap">
            <div class="ms-grid">
                <div class="ms-dow">DOM</div><div class="ms-dow">SEG</div><div class="ms-dow">TER</div>
                <div class="ms-dow">QUA</div><div class="ms-dow">QUI</div><div class="ms-dow">SEX</div><div class="ms-dow">SÁB</div>

                @for($i = 0; $i < $startDOW; $i++)
                    <div class="ms-cell empty"></div>
                @endfor

                @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dayCrbn  = $date->copy()->day($day);
                        $dayStr   = $dayCrbn->format('Y-m-d');
                        $isToday  = $dayStr === $todayStr;
                        $isWeekend= in_array($dayCrbn->dayOfWeek, [0,6]);
                        $dayTasks = $tasks->filter(fn($t) => $t->due_date && $t->due_date->format('Y-m-d') === $dayStr);
                        $extra    = max(0, $dayTasks->count() - 3);
                    @endphp
                    <div class="ms-cell {{ $isToday ? 'today' : '' }} {{ $isWeekend ? 'weekend' : '' }}">
                        <div class="ms-day-num">{{ $day }}</div>
                        @foreach($dayTasks->take(3) as $task)
                            @php
                                $cc     = $chipClass[$task->priority] ?? 'p-low';
                                $isDone = in_array($task->status, ['done','completed','cancelled']);
                                $ini    = mb_strtoupper(mb_substr($task->assignee->name ?? '?', 0, 1));
                            @endphp
                            <div class="ms-chip {{ $cc }} {{ $isDone ? 'done' : '' }}"
                                 onclick="openPanel(this)"
                                 data-id="{{ $task->id }}"
                                 data-title="{{ e($task->title) }}"
                                 data-desc="{{ e($task->description ?? '') }}"
                                 data-status="{{ $task->status }}"
                                 data-priority="{{ $task->priority }}"
                                 data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                                 data-assigned="{{ e($task->assignee->name ?? '—') }}"
                                 data-creator="{{ e($task->creator->name ?? '—') }}"
                                 data-project="{{ e($task->project->name ?? '') }}">
                                <span class="ms-chip-dot"></span>
                                <span class="ms-chip-av" style="background:{{ $avColor($task->assignee?->id) }}">{{ $ini }}</span>
                                <span class="ms-chip-title">{{ $task->title }}</span>
                            </div>
                        @endforeach
                        @if($extra > 0)<div class="ms-more">+{{ $extra }} mais</div>@endif
                    </div>
                @endfor

                @for($i = 0; $i < $trailing; $i++)
                    <div class="ms-cell empty"></div>
                @endfor
            </div>
        </div>
    </div>

    {{-- WEEK VIEW --}}
    <div class="ms-body" id="view-week" style="display:none;">
        <div class="ms-week-grid">
            @for($d = 0; $d < 7; $d++)
                @php
                    $wDay     = $weekStart->copy()->addDays($d);
                    $wStr     = $wDay->format('Y-m-d');
                    $wIsToday = $wStr === $todayStr;
                    $wTasks   = $tasks->filter(fn($t) => $t->due_date && $t->due_date->format('Y-m-d') === $wStr);
                @endphp
                <div class="ms-week-col {{ $wIsToday ? 'today' : '' }}">
                    <div class="ms-week-hdr">
                        <div class="ms-week-dow">{{ $weekDays[$d] }}</div>
                        <div class="ms-week-num">{{ $wDay->day }}</div>
                    </div>
                    @forelse($wTasks as $task)
                        @php $cc = $chipClass[$task->priority] ?? 'p-low'; $isDone = in_array($task->status, ['done','completed','cancelled']); @endphp
                        <div class="ms-chip {{ $cc }} {{ $isDone ? 'done' : '' }}" style="margin-bottom:4px;"
                             onclick="openPanel(this)"
                             data-id="{{ $task->id }}"
                             data-title="{{ e($task->title) }}"
                             data-desc="{{ e($task->description ?? '') }}"
                             data-status="{{ $task->status }}"
                             data-priority="{{ $task->priority }}"
                             data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                             data-assigned="{{ e($task->assignee->name ?? '—') }}"
                             data-creator="{{ e($task->creator->name ?? '—') }}"
                             data-project="{{ e($task->project->name ?? '') }}">
                            <span class="ms-chip-dot"></span>
                            <span class="ms-chip-title">{{ $task->title }}</span>
                        </div>
                    @empty
                        <div style="font-size:0.71rem;color:#cbd5e1;text-align:center;margin-top:10px;">—</div>
                    @endforelse
                </div>
            @endfor
        </div>
    </div>
</main>

{{-- ================ RIGHT PANEL ================ --}}
<aside class="ms-right">

    {{-- Upcoming --}}
    <div>
        <div class="ms-section-title">Próximos 7 dias</div>
        @forelse($upcoming as $t)
            @php $bar = $prioColor[$t->priority] ?? '#94a3b8'; @endphp
            <div class="ms-task-row" onclick="openPanel(this)"
                 data-id="{{ $t->id }}" data-title="{{ e($t->title) }}" data-desc="{{ e($t->description ?? '') }}"
                 data-status="{{ $t->status }}" data-priority="{{ $t->priority }}"
                 data-due="{{ $t->due_date ? $t->due_date->translatedFormat('d M') : '' }}"
                 data-assigned="{{ e($t->assignee->name ?? '—') }}" data-creator="{{ e($t->creator->name ?? '—') }}"
                 data-project="{{ e($t->project->name ?? '') }}">
                <div class="ms-task-bar" style="background:{{ $bar }}"></div>
                <div class="ms-task-body">
                    <div class="ms-task-title">{{ $t->title }}</div>
                    <div class="ms-task-meta">📅 {{ $t->due_date->translatedFormat('d M') }} · {{ Str::limit($t->assignee->name ?? '—', 16) }}</div>
                </div>
            </div>
        @empty
            <p style="font-size:0.79rem;color:#94a3b8;text-align:center;padding:12px 0;">Nenhuma tarefa nos próximos 7 dias</p>
        @endforelse
    </div>

    {{-- Overdue --}}
    @if($overdueTasks->isNotEmpty())
    <div>
        <div class="ms-section-title" style="color:#dc2626;">⚠ Atrasadas da equipe</div>
        @foreach($overdueTasks as $t)
            @php $bar = $prioColor[$t->priority] ?? '#dc2626'; @endphp
            <div class="ms-task-row" onclick="openPanel(this)"
                 data-id="{{ $t->id }}" data-title="{{ e($t->title) }}" data-desc="{{ e($t->description ?? '') }}"
                 data-status="{{ $t->status }}" data-priority="{{ $t->priority }}"
                 data-due="{{ $t->due_date ? $t->due_date->translatedFormat('d M') : '' }}"
                 data-assigned="{{ e($t->assignee->name ?? '—') }}" data-creator="{{ e($t->creator->name ?? '—') }}"
                 data-project="{{ e($t->project->name ?? '') }}">
                <div class="ms-task-bar" style="background:{{ $bar }}"></div>
                <div class="ms-task-body">
                    <div class="ms-task-title" style="color:#dc2626;">{{ $t->title }}</div>
                    <div class="ms-task-meta">{{ $t->assignee->name ?? '—' }} · venceu {{ $t->due_date->translatedFormat('d M') }}</div>
                </div>
            </div>
        @endforeach
    </div>
    @else
    <div style="text-align:center;padding:20px 0;">
        <div style="font-size:1.8rem;">✅</div>
        <div style="font-size:0.79rem;color:#059669;font-weight:700;margin-top:6px;">Equipe em dia!</div>
    </div>
    @endif

</aside>

</div>{{-- .ms-wrap --}}
</div>{{-- .ms-outer --}}

{{-- ================ DETAIL PANEL ================ --}}
<div class="ms-overlay" id="ms-overlay" onclick="closePanel()"></div>
<div class="ms-panel"   id="ms-panel">
    <div class="ms-panel-hdr">
        <div style="flex:1;">
            <span id="dp-badge" style="margin-bottom:7px;display:inline-flex;"></span>
            <div id="dp-title" style="font-size:1rem;font-weight:700;color:#1e293b;line-height:1.35;"></div>
        </div>
        <button onclick="closePanel()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.1rem;padding:4px;">✕</button>
    </div>
    <div class="ms-panel-body">
        <p id="dp-desc" style="font-size:0.85rem;color:#64748b;line-height:1.65;margin:0 0 14px;"></p>
        <div>
            <div class="ms-meta-row"><span class="ms-meta-key">Status</span><span id="dp-status" class="ms-meta-val"></span></div>
            <div class="ms-meta-row"><span class="ms-meta-key">Vencimento</span><span id="dp-due" class="ms-meta-val"></span></div>
            <div class="ms-meta-row"><span class="ms-meta-key">Responsável</span><span id="dp-assigned" class="ms-meta-val"></span></div>
            <div class="ms-meta-row"><span class="ms-meta-key">Criado por</span><span id="dp-creator" class="ms-meta-val"></span></div>
            <div class="ms-meta-row" id="dp-proj-row"><span class="ms-meta-key">Projeto</span><span id="dp-project" class="ms-meta-val"></span></div>
        </div>
    </div>
    <div class="ms-panel-footer" id="dp-actions">
        <button id="dp-start" onclick="updateStatus('doing')"
                style="flex:1;padding:10px;border-radius:8px;border:none;background:#eef2ff;color:#4f46e5;font-weight:700;cursor:pointer;">
            ▶ Iniciar
        </button>
        <button id="dp-done" onclick="updateStatus('done')"
                style="flex:1;padding:10px;border-radius:8px;border:none;background:#f0fdf4;color:#059669;font-weight:700;cursor:pointer;">
            ✓ Concluir
        </button>
        <a id="dp-edit" href="#" class="ms-btn ms-btn-primary" style="flex:1;justify-content:center;">✏️ Editar</a>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var basePath     = '{{ $basePath }}';
    var csrfToken    = '{{ csrf_token() }}';
    var currentTask  = null;

    var statusLabels  = { todo:'A Fazer', doing:'Em Andamento', in_progress:'Em Andamento', done:'Concluída', completed:'Concluída', pending:'Pendente', blocked:'Bloqueada', cancelled:'Cancelada' };
    var prioLabels    = { critical:'Crítica', high:'Alta', medium:'Média', low:'Baixa' };
    var prioColors    = { critical:'#dc2626', high:'#ea580c', medium:'#7c3aed', low:'#059669' };
    var prioBgs       = { critical:'#fef2f2', high:'#fff7ed', medium:'#f5f3ff', low:'#f0fdf4' };

    /* ── VIEW SWITCH ── */
    window.switchView = function (v) {
        document.getElementById('view-month').style.display = v === 'month' ? '' : 'none';
        document.getElementById('view-week').style.display  = v === 'week'  ? '' : 'none';
        document.getElementById('tab-month').classList.toggle('active', v === 'month');
        document.getElementById('tab-week').classList.toggle('active',  v === 'week');
    };

    /* ── DETAIL PANEL ── */
    window.openPanel = function (el) {
        var d = el.dataset;
        currentTask = { id: d.id, status: d.status };

        var p   = d.priority || 'low';
        var col = prioColors[p] || '#94a3b8';
        var bg  = prioBgs[p]   || '#f8fafc';

        document.getElementById('dp-title').textContent    = d.title    || '';
        document.getElementById('dp-desc').textContent     = d.desc     || 'Sem descrição.';
        document.getElementById('dp-due').textContent      = d.due      || '—';
        document.getElementById('dp-assigned').textContent = d.assigned || '—';
        document.getElementById('dp-creator').textContent  = d.creator  || '—';
        document.getElementById('dp-edit').href            = basePath + '/tasks/create?edit=' + d.id;

        var stEl = document.getElementById('dp-status');
        stEl.textContent = statusLabels[d.status] || d.status || '';
        stEl.className   = 'ms-meta-val ms-badge s-' + (d.status || 'todo');

        var bdEl = document.getElementById('dp-badge');
        bdEl.textContent  = '● ' + (prioLabels[p] || p);
        bdEl.style.cssText = 'background:' + bg + ';color:' + col + ';display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:0.69rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:7px;';

        var projRow = document.getElementById('dp-proj-row');
        document.getElementById('dp-project').textContent = d.project || '';
        projRow.style.display = d.project ? '' : 'none';

        var isDone = ['done','completed','cancelled'].indexOf(d.status) !== -1;
        document.getElementById('dp-start').style.display = isDone ? 'none' : '';
        document.getElementById('dp-done').style.display  = isDone ? 'none' : '';

        document.getElementById('ms-panel').classList.add('open');
        document.getElementById('ms-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closePanel = function () {
        document.getElementById('ms-panel').classList.remove('open');
        document.getElementById('ms-overlay').classList.remove('open');
        document.body.style.overflow = '';
        currentTask = null;
    };

    /* ── AJAX STATUS UPDATE (sem reload) ── */
    window.updateStatus = function (status) {
        if (!currentTask || !currentTask.id) return;

        var startBtn = document.getElementById('dp-start');
        var doneBtn  = document.getElementById('dp-done');
        startBtn.disabled = true;
        doneBtn.disabled  = true;
        doneBtn.textContent = '...';

        fetch(basePath + '/api/tasks/update-status', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentTask.id, status: status })
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);

            /* Atualiza o chip no calendário sem reload */
            var chips = document.querySelectorAll('[data-id="' + currentTask.id + '"]');
            chips.forEach(function (chip) {
                chip.dataset.status = status;
                if (['done','completed'].indexOf(status) !== -1) {
                    chip.classList.add('done');
                } else {
                    chip.classList.remove('done');
                }
            });

            /* Atualiza o panel */
            var stEl = document.getElementById('dp-status');
            stEl.textContent = statusLabels[status] || status;
            stEl.className   = 'ms-meta-val ms-badge s-' + status;
            startBtn.style.display = 'none';
            doneBtn.style.display  = 'none';
            currentTask.status     = status;
        })
        .catch(function (e) {
            alert('Não foi possível atualizar: ' + e.message);
        })
        .finally(function () {
            startBtn.disabled = false;
            doneBtn.disabled  = false;
            doneBtn.textContent = '✓ Concluir';
        });
    };

    /* ── KEYBOARD ── */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });
})();
</script>
@endpush
