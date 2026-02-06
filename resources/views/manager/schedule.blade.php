@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
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
        border: none;
        width: 100%;
        text-align: left;
    }
    .m3-event-chip:hover { transform: scale(1.02); }
    .m3-event-done { opacity: 0.55; }
    
    .m3-event-high { background: #FFDAD6; color: #410002; border-left: 3px solid #BA1A1A; }
    .m3-event-med { background: #EADDFF; color: #21005D; border-left: 3px solid #6750A4; }
    .m3-event-low { background: #E8DEF8; color: #1D192B; border-left: 3px solid #79747E; }

    .m3-empty-cell { background: #FAFAFA; }

</style>

<div class="m3-schedule-wrapper">
    <!-- Toolbar -->
    <div class="m3-cal-header">
        <div class="m3-cal-title">
            <a href="?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="m3-btn-icon"><span class="material-symbols-rounded">chevron_left</span></a>
            <span>{{ $date->isoFormat('MMMM YYYY') }}</span>
            <a href="?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="m3-btn-icon"><span class="material-symbols-rounded">chevron_right</span></a>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ $basePath . '/manager/schedule' }}" class="m3-btn-today">Hoje</a>
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
                            $taskPayload = [
                                'id' => (int) $task->id,
                                'title' => (string) $task->title,
                                'description' => (string) ($task->description ?? ''),
                                'status' => (string) ($task->status ?? ''),
                                'priority' => (string) ($task->priority ?? ''),
                                'due_date' => optional($task->due_date)->format('d/m/Y'),
                                'assignee' => optional($task->assignee)->name,
                                'project' => optional($task->project)->name,
                                'creator' => optional($task->creator)->name,
                            ];
                        @endphp
                        <button
                            type="button"
                            class="m3-event-chip {{ $prioClass }} {{ in_array($task->status, ['done','completed'], true) ? 'm3-event-done' : '' }}"
                            data-task='@json($taskPayload)'
                            onclick="openTaskModal(this)"
                            title="Ver detalhes"
                        >{{ $task->title }}</button>
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

<!-- Task Details Modal (outside isolation wrapper) -->
<div id="taskModalOverlay" style="display:none; position:fixed; inset:0; background: rgba(15,23,42,.55); z-index: 2000;">
    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; padding: 20px;">
        <div style="width: min(720px, 96vw); background:#fff; border-radius: 20px; box-shadow: 0 30px 70px rgba(0,0,0,.25); overflow:hidden; border:1px solid #e2e8f0;">
            <div style="padding: 18px 20px; background: #0f172a; color:#fff; display:flex; justify-content: space-between; align-items:center; gap: 12px;">
                <div style="font-weight: 900; letter-spacing: -.3px;" id="tmTitle">Evento</div>
                <button type="button" onclick="closeTaskModal()" style="border:none; background: rgba(255,255,255,.12); color:#fff; width: 36px; height: 36px; border-radius: 10px; font-weight: 900; cursor:pointer;">×</button>
            </div>
            <div style="padding: 18px 20px;">
                <div style="display:flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                    <span id="tmStatus" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;">Status</span>
                    <span id="tmPriority" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#eef2ff; color:#4f46e5; border:1px solid #e0e7ff;">Prioridade</span>
                    <span id="tmDue" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#ecfeff; color:#0891b2; border:1px solid #cffafe;">Prazo</span>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div style="padding: 12px 14px; background:#f8fafc; border:1px solid #f1f5f9; border-radius: 14px;">
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900;">Responsável</div>
                        <div id="tmAssignee" style="font-weight:900; color:#0f172a;">-</div>
                    </div>
                    <div style="padding: 12px 14px; background:#f8fafc; border:1px solid #f1f5f9; border-radius: 14px;">
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900;">Projeto</div>
                        <div id="tmProject" style="font-weight:900; color:#0f172a;">-</div>
                    </div>
                </div>

                <div style="padding: 14px; background:#fff; border:1px solid #e2e8f0; border-radius: 14px;">
                    <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900; margin-bottom: 6px;">Descrição</div>
                    <div id="tmDesc" style="color:#334155; font-weight: 600; line-height: 1.5;">-</div>
                </div>
            </div>
            <div style="padding: 14px 20px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                <button type="button" id="tmStartBtn" onclick="tmUpdate('doing')" style="border:none; background:#eef2ff; color:#4f46e5; padding: 10px 14px; border-radius: 12px; font-weight: 900; cursor:pointer;">Iniciar</button>
                <button type="button" id="tmDoneBtn" onclick="tmUpdate('done')" style="border:none; background:#dcfce7; color:#166534; padding: 10px 14px; border-radius: 12px; font-weight: 900; cursor:pointer;">Concluir</button>
                <a id="tmOpenLink" href="{{ $basePath . '/tasks' }}" style="text-decoration:none; background:#0f172a; color:#fff; padding: 10px 14px; border-radius: 12px; font-weight: 900;">Abrir em Tarefas</a>
            </div>
        </div>
    </div>
</div>

<script>
    let __tmCurrent = null;

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function openTaskModal(btn) {
        try {
            __tmCurrent = JSON.parse(btn.dataset.task || '{}');
        } catch (e) {
            __tmCurrent = null;
        }

        if (!__tmCurrent || !__tmCurrent.id) return;

        document.getElementById('tmTitle').innerText = __tmCurrent.title || 'Evento';
        document.getElementById('tmStatus').innerText = (__tmCurrent.status || '—').toString().toUpperCase();
        document.getElementById('tmPriority').innerText = (__tmCurrent.priority || '—').toString().toUpperCase();
        document.getElementById('tmDue').innerText = __tmCurrent.due_date ? ('Prazo: ' + __tmCurrent.due_date) : 'Sem prazo';
        document.getElementById('tmAssignee').innerText = __tmCurrent.assignee || '—';
        document.getElementById('tmProject').innerText = __tmCurrent.project || '—';
        document.getElementById('tmDesc').innerHTML = escapeHtml(__tmCurrent.description || '—').replace(/\n/g, '<br>');

        const status = (__tmCurrent.status || '').toLowerCase();
        document.getElementById('tmStartBtn').style.display = (status === 'done' || status === 'completed') ? 'none' : '';
        document.getElementById('tmDoneBtn').style.display = (status === 'done' || status === 'completed') ? 'none' : '';

        document.getElementById('taskModalOverlay').style.display = 'block';
    }

    function closeTaskModal() {
        document.getElementById('taskModalOverlay').style.display = 'none';
        __tmCurrent = null;
    }

    async function tmUpdate(status) {
        if (!__tmCurrent || !__tmCurrent.id) return;
        try {
            const res = await fetch('{{ $basePath }}/api/tasks/update-status', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: __tmCurrent.id, status })
            });
            if (!res.ok) {
                const txt = await res.text();
                alert('Não foi possível atualizar (' + res.status + ').\n\n' + txt);
                return;
            }
            window.location.reload();
        } catch (e) {
            alert('Falha de conexão ao atualizar.');
        }
    }

    // close when clicking outside
    document.addEventListener('click', function(ev) {
        const overlay = document.getElementById('taskModalOverlay');
        if (!overlay || overlay.style.display !== 'block') return;
        if (ev.target === overlay) closeTaskModal();
    });
</script>
@endsection
