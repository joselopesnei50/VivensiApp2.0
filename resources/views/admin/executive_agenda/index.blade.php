@extends('layouts.app')
@section('title', 'Agenda Executiva')

@section('content')
@php
    $priorityConfig = [
        'critical' => ['label'=>'Crítica', 'color'=>'#dc2626', 'bg'=>'#fef2f2', 'dot'=>'#dc2626'],
        'high'     => ['label'=>'Alta',    'color'=>'#d97706', 'bg'=>'#fffbeb', 'dot'=>'#f59e0b'],
        'medium'   => ['label'=>'Média',   'color'=>'#2563eb', 'bg'=>'#eff6ff', 'dot'=>'#3b82f6'],
        'low'      => ['label'=>'Baixa',   'color'=>'#64748b', 'bg'=>'#f1f5f9', 'dot'=>'#94a3b8'],
    ];
    $statusConfig = [
        'todo'        => ['label'=>'A Fazer',    'color'=>'#64748b', 'bg'=>'#f1f5f9'],
        'pending'     => ['label'=>'Pendente',   'color'=>'#64748b', 'bg'=>'#f1f5f9'],
        'doing'       => ['label'=>'Em Progresso','color'=>'#2563eb','bg'=>'#eff6ff'],
        'in_progress' => ['label'=>'Em Progresso','color'=>'#2563eb','bg'=>'#eff6ff'],
        'done'        => ['label'=>'Concluída',  'color'=>'#059669', 'bg'=>'#ecfdf5'],
        'completed'   => ['label'=>'Concluída',  'color'=>'#059669', 'bg'=>'#ecfdf5'],
        'blocked'     => ['label'=>'Bloqueada',  'color'=>'#dc2626', 'bg'=>'#fef2f2'],
    ];
    $dayNames = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
@endphp

<style>
.exec-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e3a5f 100%); border-radius: 24px; padding: 36px 40px; margin-bottom: 28px; position: relative; overflow: hidden; }
.exec-header::before { content: ''; position: absolute; top: -60px; right: -60px; width: 240px; height: 240px; background: rgba(99,102,241,0.15); border-radius: 50%; }
.exec-header::after  { content: ''; position: absolute; bottom: -80px; right: 120px; width: 180px; height: 180px; background: rgba(79,70,229,0.1); border-radius: 50%; }
.exec-metric { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 20px 24px; flex: 1; min-width: 120px; }
.exec-metric-val { font-size: 2rem; font-weight: 900; color: white; letter-spacing: -1px; line-height: 1; }
.exec-metric-lbl { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }
.member-col { background: white; border-radius: 20px; border: 1px solid #f1f5f9; overflow: hidden; display: flex; flex-direction: column; }
.member-header { padding: 20px; border-bottom: 1px solid #f1f5f9; }
.member-avatar { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-weight: 800; font-size: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.task-card { margin: 0 12px 10px; padding: 14px; border-radius: 12px; border: 1px solid #f1f5f9; background: #fafafa; cursor: pointer; transition: all 0.15s; }
.task-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.task-card.overdue { border-color: #fca5a5; background: #fff5f5; }
.priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.workload-bar { height: 4px; border-radius: 2px; background: #f1f5f9; overflow: hidden; margin-top: 8px; }
.workload-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, #4f46e5, #6366f1); transition: width 0.5s; }
.week-day { flex: 1; min-width: 0; }
.week-day-header { padding: 10px 8px; text-align: center; font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; }
.week-day-header.today { color: #4f46e5; background: #eff6ff; }
.week-task-pill { font-size: 0.7rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; margin: 3px 4px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
.section-title { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.add-task-btn { display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 12px; color: #94a3b8; font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.15s; width: 100%; margin: 0 12px 12px; width: calc(100% - 24px); }
.add-task-btn:hover { border-color: #4f46e5; color: #4f46e5; background: #eff6ff; }
</style>

{{-- ── HEADER EXECUTIVO ── --}}
<div class="exec-header mb-4">
    <div style="position:relative; z-index:1;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px; margin-bottom:28px;">
            <div>
                <div style="color:rgba(255,255,255,0.5); font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; margin-bottom:8px;">
                    <i class="fas fa-briefcase me-2"></i>Centro de Comando Executivo
                </div>
                <h1 style="color:white; font-weight:900; font-size:2rem; margin:0; letter-spacing:-1px;">Agenda da Equipe Vivensi</h1>
                <p style="color:rgba(255,255,255,0.5); margin:6px 0 0; font-size:0.9rem;">
                    {{ now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>
            <button onclick="openTaskModal()"
                    style="display:inline-flex; align-items:center; gap:10px; background:#4f46e5; color:white; padding:14px 22px; border-radius:14px; font-weight:800; font-size:0.88rem; border:none; cursor:pointer; white-space:nowrap;">
                <i class="fas fa-plus"></i> Nova Tarefa
            </button>
        </div>

        {{-- Métricas --}}
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            @php
                $execMetrics = [
                    ['val'=>$metrics['total_open'],     'lbl'=>'Em Aberto',       'icon'=>'fa-list-check',    'alert'=>false],
                    ['val'=>$metrics['done_this_week'], 'lbl'=>'Concluídas Semana','icon'=>'fa-check-double',  'alert'=>false],
                    ['val'=>$metrics['overdue'],         'lbl'=>'Atrasadas',       'icon'=>'fa-triangle-exclamation', 'alert'=>$metrics['overdue'] > 0],
                    ['val'=>$metrics['critical'],        'lbl'=>'Críticas',        'icon'=>'fa-fire',          'alert'=>$metrics['critical'] > 0],
                    ['val'=>$metrics['unassigned'],      'lbl'=>'Sem Responsável', 'icon'=>'fa-user-slash',    'alert'=>$metrics['unassigned'] > 0],
                    ['val'=>$team->count(),              'lbl'=>'Equipe Ativa',    'icon'=>'fa-users',         'alert'=>false],
                ];
            @endphp
            @foreach($execMetrics as $m)
            <div class="exec-metric" style="{{ $m['alert'] ? 'border-color:rgba(239,68,68,0.4); background:rgba(239,68,68,0.08);' : '' }}">
                <div class="exec-metric-val" style="{{ $m['alert'] ? 'color:#fca5a5;' : '' }}">
                    <i class="fas {{ $m['icon'] }}" style="font-size:0.9rem; margin-right:6px; opacity:0.7;"></i>{{ $m['val'] }}
                </div>
                <div class="exec-metric-lbl">{{ $m['lbl'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:14px 20px; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0; font-weight:700; font-size:0.88rem;">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

{{-- ── VISÃO SEMANAL ── --}}
<div class="vivensi-card" style="border-radius:20px; margin-bottom:24px; overflow:hidden;">
    <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div class="section-title" style="margin:0;"><i class="fas fa-calendar-week" style="color:#4f46e5;"></i>Visão Semanal</div>
            <p style="color:#64748b; font-size:0.78rem; margin:4px 0 0;">
                {{ $weekStart->locale('pt_BR')->isoFormat('D MMM') }} — {{ $weekEnd->locale('pt_BR')->isoFormat('D MMM YYYY') }}
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="?week={{ $week - 1 }}" style="padding:8px 14px; border-radius:10px; background:#f1f5f9; color:#475569; font-weight:700; font-size:0.8rem; text-decoration:none;"><i class="fas fa-chevron-left"></i></a>
            <a href="?week=0" style="padding:8px 14px; border-radius:10px; background:#4f46e5; color:white; font-weight:700; font-size:0.8rem; text-decoration:none;">Hoje</a>
            <a href="?week={{ $week + 1 }}" style="padding:8px 14px; border-radius:10px; background:#f1f5f9; color:#475569; font-weight:700; font-size:0.8rem; text-decoration:none;"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <div style="display:flex; overflow-x:auto;">
        @for($d = 0; $d < 7; $d++)
            @php
                $day     = $weekStart->copy()->addDays($d);
                $dayKey  = $day->format('Y-m-d');
                $isToday = $day->isToday();
                $dayTasks= $weekTasks[$dayKey] ?? collect();
            @endphp
            <div class="week-day" style="min-width:120px; border-right:{{ $d < 6 ? '1px solid #f1f5f9' : 'none' }};">
                <div class="week-day-header {{ $isToday ? 'today' : '' }}">
                    <div>{{ $dayNames[$day->dayOfWeek] }}</div>
                    <div style="font-size:1rem; font-weight:900; color:{{ $isToday ? '#4f46e5' : '#1e293b' }}; margin-top:2px;">{{ $day->format('d') }}</div>
                </div>
                <div style="padding:8px 0; min-height:80px;">
                    @forelse($dayTasks as $t)
                        @php $pc = $priorityConfig[$t->priority] ?? $priorityConfig['medium']; @endphp
                        <span class="week-task-pill"
                              style="background:{{ $pc['bg'] }}; color:{{ $pc['color'] }};"
                              title="{{ $t->title }}{{ $t->assignee ? ' — '.$t->assignee->name : '' }}">
                            {{ Str::limit($t->title, 20) }}
                        </span>
                    @empty
                        <div style="text-align:center; padding:16px 8px; color:#e2e8f0; font-size:0.7rem;">—</div>
                    @endforelse
                </div>
            </div>
        @endfor
    </div>
</div>

{{-- ── DISTRIBUIÇÃO POR MEMBRO ── --}}
<div class="section-title"><i class="fas fa-users" style="color:#4f46e5;"></i>Carga de Trabalho por Membro</div>
<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px; margin-bottom:28px;">
    @forelse($workload as $memberId => $data)
    @php
        $member    = $data['user'];
        $maxTasks  = $workload->max('total') ?: 1;
        $fillPct   = $maxTasks > 0 ? round(($data['total'] / $maxTasks) * 100) : 0;
        $initial   = strtoupper(substr($member->name, 0, 1));
        $isOverloaded = $data['total'] > 5;
    @endphp
    <div class="member-col">
        {{-- Cabeçalho do membro --}}
        <div class="member-header">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                <div class="member-avatar">{{ $initial }}</div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:800; color:#1e293b; font-size:0.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $member->name }}</div>
                    <div style="font-size:0.72rem; color:#64748b; font-weight:600;">{{ $member->department ?: $member->role }}</div>
                </div>
                @if($data['overdue'] > 0)
                    <span style="background:#fef2f2; color:#dc2626; font-weight:800; font-size:0.65rem; padding:3px 8px; border-radius:10px; white-space:nowrap;">
                        {{ $data['overdue'] }} atrasada{{ $data['overdue'] > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
            <div style="display:flex; gap:8px; font-size:0.72rem; font-weight:700;">
                <span style="color:#64748b;">{{ $data['pending'] }} a fazer</span>
                <span style="color:#3b82f6;">· {{ $data['doing'] }} fazendo</span>
                <span style="color:#059669;">· {{ $data['done'] }} prontas</span>
            </div>
            <div class="workload-bar">
                <div class="workload-fill" style="width:{{ $fillPct }}%; background:{{ $isOverloaded ? 'linear-gradient(90deg,#ef4444,#dc2626)' : 'linear-gradient(90deg,#4f46e5,#6366f1)' }};"></div>
            </div>
        </div>

        {{-- Tarefas do membro --}}
        <div style="flex:1; padding-top:10px; overflow-y:auto; max-height:340px;">
            @forelse($data['tasks'] as $task)
            @php
                $pc      = $priorityConfig[$task->priority] ?? $priorityConfig['medium'];
                $sc      = $statusConfig[$task->status]   ?? $statusConfig['todo'];
                $isOverdue = $task->due_date && $task->due_date->isPast();
            @endphp
            <div class="task-card {{ $isOverdue ? 'overdue' : '' }}" onclick="openEditModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->priority }}', '{{ $task->status }}', '{{ $task->due_date?->format('Y-m-d') ?? '' }}', {{ $task->assigned_to ?? 'null' }})">
                <div style="display:flex; align-items:flex-start; gap:8px;">
                    <div class="priority-dot" style="background:{{ $pc['dot'] }}; margin-top:5px;"></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; color:#1e293b; font-size:0.85rem; line-height:1.4; margin-bottom:6px;">{{ $task->title }}</div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; font-size:0.65rem; font-weight:800; padding:3px 8px; border-radius:8px;">{{ $sc['label'] }}</span>
                            @if($task->due_date)
                                <span style="font-size:0.65rem; font-weight:700; color:{{ $isOverdue ? '#dc2626' : '#64748b' }};">
                                    <i class="fas fa-calendar-day me-1"></i>{{ $task->due_date->format('d/m') }}{{ $isOverdue ? ' ⚠️' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('admin.executive.task.destroy', $task) }}" method="POST" onsubmit="return confirm('Excluir?')" style="flex-shrink:0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none; border:none; color:#cbd5e1; font-size:0.75rem; cursor:pointer; padding:2px 4px;" onclick="event.stopPropagation()">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </form>
                </div>
            </div>
            @empty
                <div style="text-align:center; padding:24px 16px; color:#cbd5e1;">
                    <i class="fas fa-check-circle" style="font-size:1.5rem; display:block; margin-bottom:6px;"></i>
                    <span style="font-size:0.78rem; font-weight:700;">Sem tarefas abertas</span>
                </div>
            @endforelse
        </div>

        <button class="add-task-btn" onclick="openTaskModal({{ $memberId }})">
            <i class="fas fa-plus" style="font-size:0.75rem;"></i> Atribuir tarefa
        </button>
    </div>
    @empty
        <div style="grid-column:1/-1; text-align:center; padding:60px; color:#94a3b8;">
            <i class="fas fa-users" style="font-size:3rem; display:block; margin-bottom:16px; opacity:0.3;"></i>
            <p style="font-weight:700; margin:0 0 16px;">Nenhum membro da equipe interna cadastrado.</p>
            <a href="{{ route('admin.team.index') }}" style="background:#4f46e5; color:white; padding:12px 24px; border-radius:12px; font-weight:700; text-decoration:none; font-size:0.88rem;">
                Gerenciar Equipe
            </a>
        </div>
    @endforelse
</div>

{{-- ── PRÓXIMOS VENCIMENTOS ── --}}
@if($upcoming->count())
<div class="vivensi-card" style="border-radius:20px; overflow:hidden;">
    <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9;">
        <div class="section-title" style="margin:0;"><i class="fas fa-hourglass-half" style="color:#d97706;"></i>Vencendo nos próximos 7 dias</div>
    </div>
    <div style="padding:16px 20px; display:flex; flex-direction:column; gap:10px;">
        @foreach($upcoming->take(10) as $t)
        @php
            $pc = $priorityConfig[$t->priority] ?? $priorityConfig['medium'];
            $daysLeft = now()->startOfDay()->diffInDays($t->due_date->startOfDay(), false);
        @endphp
        <div style="display:flex; align-items:center; gap:14px; padding:12px 16px; background:#f8fafc; border-radius:12px; border:1px solid #f1f5f9;">
            <div class="priority-dot" style="background:{{ $pc['dot'] }};"></div>
            <div style="flex:1; font-weight:700; color:#1e293b; font-size:0.88rem;">{{ $t->title }}</div>
            @if($t->assignee)
                <span style="font-size:0.75rem; color:#64748b; font-weight:600; white-space:nowrap;">{{ explode(' ', $t->assignee->name)[0] }}</span>
            @endif
            <span style="font-size:0.75rem; font-weight:800; padding:5px 12px; border-radius:10px; white-space:nowrap;
                background:{{ $daysLeft <= 1 ? '#fef2f2' : ($daysLeft <= 3 ? '#fffbeb' : '#ecfdf5') }};
                color:{{ $daysLeft <= 1 ? '#dc2626' : ($daysLeft <= 3 ? '#d97706' : '#059669') }};">
                {{ $daysLeft === 0 ? 'Hoje' : ($daysLeft === 1 ? 'Amanhã' : "em {$daysLeft} dias") }}
            </span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── MODAL: NOVA TAREFA ── --}}
<div id="taskModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:white; border-radius:24px; padding:36px; max-width:500px; width:100%; box-shadow:0 30px 70px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="margin:0; font-weight:900; color:#1e293b; font-size:1.2rem;">
                <i class="fas fa-plus-circle me-2" style="color:#4f46e5;"></i>Nova Tarefa
            </h3>
            <button onclick="closeModal('taskModal')" style="background:none; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer;"><i class="fas fa-xmark"></i></button>
        </div>
        <form action="{{ route('admin.executive.task.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:18px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Título *</label>
                <input type="text" name="title" required placeholder="Descreva a tarefa..."
                       style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.9rem; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#f1f5f9'">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Descrição</label>
                <textarea name="description" rows="3" placeholder="Detalhes, contexto, links..."
                          style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.88rem; resize:vertical; box-sizing:border-box;"></textarea>
            </div>
            <div class="row g-3" style="margin-bottom:18px;">
                <div class="col-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Responsável</label>
                    <select name="assigned_to" id="modalAssignedTo"
                            style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.88rem; background:white; box-sizing:border-box;">
                        <option value="">Sem responsável</option>
                        @foreach($team as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Prioridade</label>
                    <select name="priority"
                            style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.88rem; background:white; box-sizing:border-box;">
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                        <option value="critical">Crítica</option>
                        <option value="low">Baixa</option>
                    </select>
                </div>
            </div>
            <div class="row g-3" style="margin-bottom:24px;">
                <div class="col-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Prazo</label>
                    <input type="date" name="due_date"
                           style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.88rem; box-sizing:border-box;">
                </div>
                <div class="col-6">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:8px;">Status inicial</label>
                    <select name="status"
                            style="width:100%; padding:13px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.88rem; background:white; box-sizing:border-box;">
                        <option value="todo">A Fazer</option>
                        <option value="doing">Em Progresso</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="button" onclick="closeModal('taskModal')"
                        style="flex:1; padding:14px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; color:#64748b; cursor:pointer; font-size:0.9rem;">
                    Cancelar
                </button>
                <button type="submit"
                        style="flex:2; padding:14px; border-radius:12px; border:none; background:#4f46e5; color:white; font-weight:800; cursor:pointer; font-size:0.9rem;">
                    <i class="fas fa-plus me-2"></i>Criar Tarefa
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: EDITAR TAREFA ── --}}
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:white; border-radius:24px; padding:36px; max-width:460px; width:100%; box-shadow:0 30px 70px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <h3 style="margin:0; font-weight:900; color:#1e293b; font-size:1.1rem;">
                <i class="fas fa-pen me-2" style="color:#4f46e5;"></i>Editar Tarefa
            </h3>
            <button onclick="closeModal('editModal')" style="background:none; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer;"><i class="fas fa-xmark"></i></button>
        </div>
        <div id="editTaskTitle" style="font-weight:800; color:#1e293b; font-size:0.95rem; padding:14px 16px; background:#f8fafc; border-radius:12px; margin-bottom:20px;"></div>
        <div class="row g-3" style="margin-bottom:18px;">
            <div class="col-6">
                <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Status</label>
                <select id="editStatus" style="width:100%; padding:11px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.85rem; background:white; box-sizing:border-box;">
                    <option value="todo">A Fazer</option>
                    <option value="doing">Em Progresso</option>
                    <option value="done">Concluída</option>
                    <option value="blocked">Bloqueada</option>
                </select>
            </div>
            <div class="col-6">
                <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Prioridade</label>
                <select id="editPriority" style="width:100%; padding:11px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.85rem; background:white; box-sizing:border-box;">
                    <option value="low">Baixa</option>
                    <option value="medium">Média</option>
                    <option value="high">Alta</option>
                    <option value="critical">Crítica</option>
                </select>
            </div>
        </div>
        <div class="row g-3" style="margin-bottom:24px;">
            <div class="col-6">
                <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Responsável</label>
                <select id="editAssigned" style="width:100%; padding:11px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.85rem; background:white; box-sizing:border-box;">
                    <option value="">Sem responsável</option>
                    @foreach($team as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Prazo</label>
                <input type="date" id="editDueDate" style="width:100%; padding:11px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.85rem; box-sizing:border-box;">
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="closeModal('editModal')"
                    style="flex:1; padding:13px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; color:#64748b; cursor:pointer;">
                Cancelar
            </button>
            <button onclick="saveEditTask()"
                    style="flex:2; padding:13px; border-radius:12px; border:none; background:#4f46e5; color:white; font-weight:800; cursor:pointer;">
                <i class="fas fa-save me-2"></i>Salvar
            </button>
        </div>
    </div>
</div>

<script>
let editTaskId = null;

function openTaskModal(assignedTo) {
    if (assignedTo) {
        document.getElementById('modalAssignedTo').value = assignedTo;
    }
    document.getElementById('taskModal').style.display = 'flex';
}

function openEditModal(id, title, priority, status, dueDate, assignedTo) {
    editTaskId = id;
    document.getElementById('editTaskTitle').textContent = title;
    document.getElementById('editPriority').value  = priority;
    document.getElementById('editStatus').value    = status;
    document.getElementById('editDueDate').value   = dueDate;
    document.getElementById('editAssigned').value  = assignedTo || '';
    document.getElementById('editModal').style.display = 'flex';
}

async function saveEditTask() {
    if (!editTaskId) return;
    const data = {
        _method:     'PATCH',
        _token:      '{{ csrf_token() }}',
        status:      document.getElementById('editStatus').value,
        priority:    document.getElementById('editPriority').value,
        assigned_to: document.getElementById('editAssigned').value || null,
        due_date:    document.getElementById('editDueDate').value || null,
    };
    const res = await fetch('/admin/executive/tasks/' + editTaskId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({...data, _method: 'PATCH'}),
    });
    if (res.ok) { closeModal('editModal'); location.reload(); }
    else { alert('Erro ao salvar. Tente novamente.'); }
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

['taskModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endsection
