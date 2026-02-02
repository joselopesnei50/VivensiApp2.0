@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

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
            <a href="?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="m3-btn-icon"><span class="material-symbols-rounded">chevron_left</span></a>
            <span>{{ $date->isoFormat('MMMM YYYY') }}</span>
            <a href="?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="m3-btn-icon"><span class="material-symbols-rounded">chevron_right</span></a>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="{{ url('/tasks/calendar') }}" class="m3-btn-today">Hoje</a>
            <a href="{{ url('/tasks/create') }}" class="m3-btn-today" style="background: #6750A4; color: white;">
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
                        <a href="#" class="m3-event-chip {{ $prioClass }}" title="{{ $task->title }}">
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
@endsection
