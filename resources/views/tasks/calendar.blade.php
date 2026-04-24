@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    \Carbon\Carbon::setLocale('pt_BR');
@endphp

<style>
    /* ISOLATION WRAPPER */
    .m3-schedule-wrapper {
        all: initial;
        font-family: 'Roboto', sans-serif;
        box-sizing: border-box;
        background-color: #F7F2FA;
        display: flex;
        flex-direction: column;
        width: 100%;
        height: calc(100vh - 80px); /* Adjust based on navbar height */
        overflow: hidden;
    }

    .m3-schedule-wrapper * {
        box-sizing: border-box;
        font-family: 'Roboto', sans-serif;
    }
    
    .m3-schedule-wrapper .material-symbols-rounded {
        font-family: 'Material Symbols Rounded' !important;
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
    }

    /* HEADER */
    .m3-cal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 24px;
        background: #fff;
        border-bottom: 1px solid #E7E0EC;
    }

    .m3-cal-title {
        font-size: 22px;
        color: #1C1B1F;
        font-weight: 500;
        text-transform: capitalize;
        display: flex; align-items: center; gap: 16px;
    }

    .m3-btn-icon {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        color: #49454E;
        display: flex; align-items: center; justify-content: center;
    }
    .m3-btn-icon:hover { background: #E8DEF8; color: #1D192B; }

    .m3-btn-today {
        border: 1px solid #79747E;
        background: transparent;
        color: #6750A4;
        font-weight: 500;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }
    .m3-btn-today:hover { background: #EADDFF; border-color: transparent; color: #21005D; }

    /* GRID */
    .m3-cal-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        padding: 24px;
    }

    .m3-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        background: #fff;
        border-radius: 16px;
        border: 1px solid #E7E0EC;
        overflow: hidden;
        flex: 1;
    }

    .m3-day-header {
        background: #F3EDF7;
        color: #49454F;
        font-weight: 500;
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border-bottom: 1px solid #E7E0EC;
        border-right: 1px solid #E7E0EC;
    }
    .m3-day-header:nth-child(7n) { border-right: none; }

    .m3-day-cell {
        border-right: 1px solid #E7E0EC;
        border-bottom: 1px solid #E7E0EC;
        padding: 8px;
        min-height: 120px;
        position: relative;
        background: #fff;
    }
    .m3-day-cell:nth-child(7n) { border-right: none; }
    
    .m3-day-number {
        font-size: 14px;
        font-weight: 500;
        color: #1C1B1F;
        margin-bottom: 8px;
        display: block;
        width: 28px; height: 28px;
        line-height: 28px;
        text-align: center;
        border-radius: 50%;
    }
    
    .m3-day-today .m3-day-number {
        background: #6750A4;
        color: #fff;
    }

    /* EVENTS */
    .m3-event-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 8px;
        margin-bottom: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        color: #1D192B;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: transform 0.1s;
    }
    .m3-event-chip:hover { transform: scale(1.02); }
    
    .m3-event-high { background: #FFDAD6; color: #410002; border-left: 3px solid #BA1A1A; }
    .m3-event-med { background: #EADDFF; color: #21005D; border-left: 3px solid #6750A4; }
    .m3-event-low { background: #E8DEF8; color: #1D192B; border-left: 3px solid #79747E; }

    .m3-empty-cell { background: #FAFAFA; }

</style>

<div class="m3-schedule-wrapper">
    <!-- Toolbar -->
    <div class="m3-cal-header">
        <div class="m3-cal-title">
            <a href="?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="m3-btn-icon" title="Mês Anterior"><span class="material-symbols-rounded">chevron_left</span></a>
            <span style="text-transform: capitalize;">{{ $date->translatedFormat('F Y') }}</span>
            <a href="?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="m3-btn-icon" title="Próximo Mês"><span class="material-symbols-rounded">chevron_right</span></a>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ $basePath . '/tasks/calendar' }}" class="m3-btn-today">Hoje</a>
            <a href="{{ $basePath . '/tasks/create' }}" class="m3-btn-today" style="background: #6750A4; color: white;">
                <span class="material-symbols-rounded" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">add</span> Novo Evento
            </a>
        </div>
    </div>

    <!-- Calendar Body -->
    <div class="m3-cal-body">
        <div class="m3-cal-grid">
            <!-- Headers -->
            <div class="m3-day-header">DOM</div>
            <div class="m3-day-header">SEG</div>
            <div class="m3-day-header">TER</div>
            <div class="m3-day-header">QUA</div>
            <div class="m3-day-header">QUI</div>
            <div class="m3-day-header">SEX</div>
            <div class="m3-day-header">SÁB</div>

            <!-- Logic for Days -->
            @php
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();
                $startDayOfWeek = $startOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
                $daysInMonth = $date->daysInMonth;
                $today = \Carbon\Carbon::now()->format('Y-m-d');
            @endphp

            <!-- Empty Cells Before -->
            @for($i = 0; $i < $startDayOfWeek; $i++)
                <div class="m3-day-cell m3-empty-cell"></div>
            @endfor

            <!-- Days -->
            @for($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $currentDateStr = $date->copy()->day($day)->format('Y-m-d');
                    $isToday = $currentDateStr === $today;
                    // Filter tasks for this day
                    $dayTasks = $tasks->filter(function($t) use ($currentDateStr) {
                        return $t->due_date && $t->due_date->format('Y-m-d') === $currentDateStr;
                    });
                @endphp

                <div class="m3-day-cell {{ $isToday ? 'm3-day-today' : '' }}">
                    <span class="m3-day-number">{{ $day }}</span>
                    
                    @foreach($dayTasks as $task)
                        @php
                            $prioClass = match($task->priority) {
                                'high' => 'm3-event-high',
                                'medium' => 'm3-event-med',
                                default => 'm3-event-low'
                            };
                        @endphp
                        <a href="#"
                           class="m3-event-chip {{ $prioClass }}"
                           title="{{ $task->title }}"
                           onclick="openTaskModal(this); return false;"
                           data-id="{{ $task->id }}"
                           data-title="{{ e($task->title) }}"
                           data-desc="{{ e($task->description ?? '') }}"
                           data-status="{{ $task->status }}"
                           data-priority="{{ $task->priority }}"
                           data-due="{{ $task->due_date ? $task->due_date->format('d/m/Y') : '' }}"
                           data-assigned="{{ e($task->assignee->name ?? 'Não atribuído') }}"
                           data-edit="{{ url('/tasks/create') }}?edit={{ $task->id }}">
                            {{ $task->title }}
                        </a>
                    @endforeach
                </div>
            @endfor

            <!-- Empty Cells After (to fill grid) -->
            @php
                $totalCells = $startDayOfWeek + $daysInMonth;
                $remainingCells = 35 - $totalCells;
                if($remainingCells < 0) $remainingCells = 42 - $totalCells; // If 6 rows needed
            @endphp

            @for($i = 0; $i < $remainingCells; $i++)
                <div class="m3-day-cell m3-empty-cell"></div>
            @endfor

        </div>
    </div>
</div>
<!-- Task Detail Modal -->
<div id="taskModal" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; font-family:'Roboto',sans-serif;" onclick="if(event.target===this) closeTaskModal()">
    <div style="background:#fff; border-radius:28px; padding:32px; width:100%; max-width:440px; margin:16px; box-shadow:0 8px 40px rgba(0,0,0,0.18); position:relative;">
        <button onclick="closeTaskModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;color:#49454E;font-size:22px;line-height:1;">&#x2715;</button>

        <div id="tm-priority-badge" style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:14px;"></div>

        <h2 id="tm-title" style="margin:0 0 10px;font-size:20px;font-weight:600;color:#1C1B1F;line-height:1.3;"></h2>
        <p id="tm-desc" style="margin:0 0 20px;font-size:14px;color:#49454E;line-height:1.6;"></p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
            <div style="background:#F7F2FA;border-radius:12px;padding:12px;">
                <div style="font-size:11px;font-weight:700;color:#79747E;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Vencimento</div>
                <div id="tm-due" style="font-size:14px;font-weight:600;color:#1C1B1F;"></div>
            </div>
            <div style="background:#F7F2FA;border-radius:12px;padding:12px;">
                <div style="font-size:11px;font-weight:700;color:#79747E;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Responsável</div>
                <div id="tm-assigned" style="font-size:14px;font-weight:600;color:#1C1B1F;"></div>
            </div>
            <div style="background:#F7F2FA;border-radius:12px;padding:12px;">
                <div style="font-size:11px;font-weight:700;color:#79747E;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Status</div>
                <div id="tm-status" style="font-size:14px;font-weight:600;color:#1C1B1F;"></div>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <a id="tm-edit-link" href="#" style="flex:1;background:#6750A4;color:#fff;border:none;border-radius:20px;padding:12px;font-size:14px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;transition:background .2s;" onmouseover="this.style.background='#4f3d8a'" onmouseout="this.style.background='#6750A4'">
                ✏️ Editar Compromisso
            </a>
            <button onclick="closeTaskModal()" style="flex:1;background:#E8DEF8;color:#21005D;border:none;border-radius:20px;padding:12px;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#d0bfff'" onmouseout="this.style.background='#E8DEF8'">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
    const statusLabels = { pending:'Pendente', in_progress:'Em Andamento', completed:'Concluída', cancelled:'Cancelada' };
    const priorityLabels = { high:'Alta', medium:'Média', low:'Baixa' };
    const priorityStyles = {
        high: 'background:#FFDAD6;color:#410002;',
        medium: 'background:#EADDFF;color:#21005D;',
        low: 'background:#E8DEF8;color:#1D192B;'
    };

    function openTaskModal(el) {
        const d = el.dataset;
        document.getElementById('tm-title').textContent = d.title;
        document.getElementById('tm-desc').textContent = d.desc || 'Sem descrição.';
        document.getElementById('tm-due').textContent = d.due || '—';
        document.getElementById('tm-assigned').textContent = d.assigned;
        document.getElementById('tm-status').textContent = statusLabels[d.status] || d.status;

        const badge = document.getElementById('tm-priority-badge');
        badge.textContent = '● ' + (priorityLabels[d.priority] || d.priority);
        badge.style.cssText = (priorityStyles[d.priority] || 'background:#eee;') + 'display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:14px;';

        document.getElementById('tm-edit-link').href = d.edit;

        const modal = document.getElementById('taskModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeTaskModal() {
        document.getElementById('taskModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTaskModal(); });
</script>
@endsection
