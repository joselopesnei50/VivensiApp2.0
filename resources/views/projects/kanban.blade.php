@extends('layouts.app')

@section('content')
<style>
    .kanban-wrapper {
        margin-top: 20px;
        height: calc(100vh - 250px);
        min-height: 600px;
    }
    .kanban-board-scroll {
        display: flex;
        gap: 30px;
        padding-bottom: 20px;
        height: 100%;
        overflow-x: auto;
    }
    .kanban-column-prem {
        flex: 0 0 380px;
        background: rgba(248, 250, 252, 0.5);
        border-radius: 28px;
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: all 0.3s;
    }
    .kanban-column-prem:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
    }
    .column-header-prem {
        padding: 25px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .column-title-prem {
        font-weight: 800;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .column-count-badge {
        background: white;
        color: #64748b;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        border: 1px solid #f1f5f9;
    }
    .column-body-prem {
        flex: 1;
        padding: 0 20px 25px;
        overflow-y: auto;
    }
    .column-body-prem::-webkit-scrollbar {
        width: 6px;
    }
    .column-body-prem::-webkit-scrollbar-track {
        background: transparent;
    }
    .column-body-prem::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .column-body-prem.drag-over {
        background: rgba(99, 102, 241, 0.05);
        border-radius: 0 0 28px 28px;
    }
    .add-task-btn-prem {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .add-task-btn-prem:hover {
        background: #6366f1;
        color: white;
        border-color: #6366f1;
        transform: rotate(90deg);
    }
</style>

<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Operações Táticas</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Quadro de Missões: {{ $project->name }}</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Orquestração de tarefas e fluxo operacional em tempo real.</p>
        </div>
        <div style="display: flex; gap: 15px;">
             <a href="{{ url('/projects/details/'.$project->id) }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-arrow-left me-2"></i> Dashboard
            </a>
            <button onclick="document.getElementById('newTaskModal').style.display='flex'" class="btn-premium btn-premium-shine" style="border: none;">
                <i class="fas fa-plus me-2"></i> Criar Atividade
            </button>
        </div>
    </div>
</div>

<div class="kanban-wrapper">
    <div class="kanban-board-scroll">
        
        <!-- To Do Column -->
        <div class="kanban-column-prem" id="todo-col">
            <div class="column-header-prem">
                <div class="column-title-prem" style="color: #64748b;">
                    <i class="fas fa-compass" style="color: #94a3b8;"></i> Pipeline
                    <span class="column-count-badge ms-2">{{ $kanban['todo']->count() }}</span>
                </div>
                <div class="add-task-btn-prem" onclick="document.getElementById('newTaskModal').style.display='flex'">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
            <div class="column-body-prem" ondrop="drop(event, 'todo')" ondragover="allowDrop(event)">
                @foreach($kanban['todo'] as $task)
                    @include('projects.partials.task_card', ['task' => $task])
                @endforeach
            </div>
        </div>

        <!-- Doing Column -->
        <div class="kanban-column-prem" id="doing-col">
            <div class="column-header-prem">
                <div class="column-title-prem" style="color: #f59e0b;">
                    <i class="fas fa-bolt"></i> Em Execução
                    <span class="column-count-badge ms-2">{{ $kanban['doing']->count() }}</span>
                </div>
            </div>
            <div class="column-body-prem" ondrop="drop(event, 'doing')" ondragover="allowDrop(event)">
                @foreach($kanban['doing'] as $task)
                    @include('projects.partials.task_card', ['task' => $task])
                @endforeach
            </div>
        </div>

        <!-- Done Column -->
        <div class="kanban-column-prem" id="done-col">
            <div class="column-header-prem">
                <div class="column-title-prem" style="color: #10b981;">
                    <i class="fas fa-check-double"></i> Concluído
                    <span class="column-count-badge ms-2">{{ $kanban['done']->count() }}</span>
                </div>
            </div>
            <div class="column-body-prem" ondrop="drop(event, 'done')" ondragover="allowDrop(event)">
                @foreach($kanban['done'] as $task)
                    @include('projects.partials.task_card', ['task' => $task])
                @endforeach
            </div>
        </div>

    </div>
</div>

<!-- Premium Task Modal -->
<div id="newTaskModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); justify-content: center; align-items: center; z-index: 9999;">
    <div class="vivensi-card" style="background: white; padding: 50px; border-radius: 32px; width: 550px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 50px 100px rgba(0,0,0,0.3);">
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="width: 70px; height: 70px; background: #eef2ff; border-radius: 24px; display: flex; align-items: center; justify-content: center; color: #6366f1; font-size: 1.8rem; margin: 0 auto 20px;">
                <i class="fas fa-tasks"></i>
            </div>
            <h3 style="margin: 0; color: #1e293b; font-weight: 900; letter-spacing: -1px;">Nova Atividade</h3>
            <p style="color: #64748b; font-weight: 500; margin-top: 5px;">Adicione uma nova meta ao projeto.</p>
        </div>
        
        <form action="{{ url('/tasks') }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <input type="hidden" name="status" value="todo">
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Título da Atividade</label>
                <input type="text" name="title" class="form-control-vivensi" required 
                       style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; color: #1e293b;" 
                       placeholder="Ex: Definir plano de marketing">
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 40px;">
                <button type="button" onclick="document.getElementById('newTaskModal').style.display='none'" 
                        style="flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 18px; border-radius: 16px; font-weight: 800; cursor: pointer; transition: all 0.2s;">
                    Cancelar
                </button>
                <button type="submit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 18px; font-weight: 800; border-radius: 16px;">
                    Registrar Meta <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function allowDrop(ev) {
        ev.preventDefault();
        ev.currentTarget.classList.add('drag-over');
    }

    function drag(ev) {
        ev.dataTransfer.setData("text", ev.target.id);
        ev.target.style.opacity = '0.5';
        ev.target.style.transform = 'scale(1.05) rotate(2deg)';
    }

    function drop(ev, status) {
        ev.preventDefault();
        ev.currentTarget.classList.remove('drag-over');
        var data = ev.dataTransfer.getData("text");
        var el = document.getElementById(data);
        
        if (el) {
            ev.currentTarget.appendChild(el);
            el.style.opacity = '1';
            el.style.transform = 'none';

            var taskId = data.split('-')[1];
            
            fetch('{{ url("/api/tasks/update-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: taskId, status: status })
            }).then(response => {
                if(!response.ok) {
                   console.error('Failed to update task status');
                }
            });
        }
    }
    
    document.addEventListener("dragend", function(event) {
        event.target.style.opacity = "1";
        event.target.style.transform = 'none';
        document.querySelectorAll('.column-body-prem').forEach(col => col.classList.remove('drag-over'));
    });
</script>
@endsection
