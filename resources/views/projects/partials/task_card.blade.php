@php
    $u = auth()->user();
    $canMove = in_array($u->role, ['manager', 'super_admin'], true)
        || ((int) ($task->assigned_to ?? 0) === (int) $u->id)
        || ((int) ($task->created_by ?? 0) === (int) $u->id);

    $prio = $task->priority ?? 'medium';
    $dueLabel = $task->due_date ? $task->due_date->format('d/m') : 'S/P';
    $normalizedStatus = match($task->status ?? 'todo') {
        'pending', 'blocked', 'todo' => 'todo',
        'in_progress', 'doing' => 'doing',
        'completed', 'done' => 'done',
        default => (string) ($task->status ?? 'todo'),
    };
    $taskPayload = [
        'id' => (int) $task->id,
        'title' => (string) $task->title,
        'description' => (string) ($task->description ?? ''),
        'status' => (string) $normalizedStatus,
        'priority' => (string) $prio,
        'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
        'due_label' => $dueLabel,
        'assigned_to' => $task->assigned_to ? (int) $task->assigned_to : null,
        'assignee_name' => $task->assignee?->name,
        'assignee_initial' => $task->assignee?->name ? mb_substr($task->assignee->name, 0, 1) : null,
        'can_move' => (bool) $canMove,
    ];
@endphp
<div class="task-card-prem" 
     id="task-{{ $task->id }}"
     data-task-id="{{ (int) $task->id }}"
     data-task='@json($taskPayload)'
     draggable="{{ $canMove ? 'true' : 'false' }}"
     ondragstart="{{ $canMove ? 'drag(event)' : '' }}"
     onclick="openKanbanTaskModal(this)"
     title="{{ $canMove ? 'Arraste para mover' : 'Sem permissão para mover' }}"
     style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border: 1px solid #f1f5f9; cursor: {{ $canMove ? 'grab' : 'default' }}; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.02); opacity: {{ $canMove ? '1' : '0.85' }};">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        @php
            $pColor = match($task->priority ?? 'medium') {
                'high' => '#f43f5e',
                'critical' => '#b91c1c',
                'low' => '#10b981',
                default => '#f59e0b'
            };
            $pLabel = match($task->priority ?? 'medium') {
                'high' => 'CRÍTICO',
                'critical' => 'CRÍTICO+',
                'low' => 'SUPORTE',
                default => 'ESTRATÉGICO'
            };
        @endphp
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="task-priority-dot" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $pColor }};"></span>
            <span class="task-priority-label" style="font-size: 0.6rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">{{ $pLabel }}</span>
        </div>
        <i class="fas fa-ellipsis-v" style="color: #cbd5e1; font-size: 0.8rem; cursor: pointer;"></i>
    </div>

    <h6 class="task-title" style="color: #1e293b; font-weight: 800; font-size: 0.95rem; margin-bottom: 15px; line-height: 1.5;">{{ $task->title }}</h6>
    
    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #f8fafc;">
        <div style="display: flex; -webkit-mask-image: linear-gradient(to right, black 80%, transparent 100%);">
            @if($task->assignee)
                <div class="task-assignee" title="{{ $task->assignee->name }}" style="width: 28px; height: 28px; background: #f1f5f9; color: #64748b; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 900; border: 2px solid white;">
                    <span class="task-assignee-initial">{{ substr($task->assignee->name, 0, 1) }}</span>
                </div>
            @else
                <div class="task-assignee" title="Sem responsável" style="width: 28px; height: 28px; border: 1.5px dashed #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.7rem;">
                    <i class="fas fa-user-plus"></i>
                </div>
            @endif
        </div>
        
        <div style="display: flex; align-items: center; gap: 5px; color: #94a3b8; font-weight: 700; font-size: 0.7rem;">
            <i class="far fa-calendar-alt"></i>
            <span class="task-due-label">{{ $dueLabel }}</span>
        </div>
    </div>
</div>

<style>
    .task-card-prem:hover {
        transform: translateY(-5px);
        border-color: #6366f1;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.08);
    }
    .task-card-prem:active {
        cursor: grabbing;
    }
</style>
