@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp
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
             <a href="{{ $basePath . '/projects/details/'.$project->id }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-arrow-left me-2"></i> Dashboard
            </a>
            <button type="button" onclick="openNewTaskModal()" class="btn-premium btn-premium-shine" style="border: none;">
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
                    <span class="column-count-badge ms-2" data-colcount="todo">{{ $kanban['todo']->count() }}</span>
                </div>
                <div class="add-task-btn-prem" onclick="openNewTaskModal()">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
            <div class="column-body-prem" data-status="todo" ondrop="drop(event, 'todo')" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
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
                    <span class="column-count-badge ms-2" data-colcount="doing">{{ $kanban['doing']->count() }}</span>
                </div>
            </div>
            <div class="column-body-prem" data-status="doing" ondrop="drop(event, 'doing')" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
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
                    <span class="column-count-badge ms-2" data-colcount="done">{{ $kanban['done']->count() }}</span>
                </div>
            </div>
            <div class="column-body-prem" data-status="done" ondrop="drop(event, 'done')" ondragover="allowDrop(event)" ondragleave="dragLeave(event)">
                @foreach($kanban['done'] as $task)
                    @include('projects.partials.task_card', ['task' => $task])
                @endforeach
            </div>
        </div>

    </div>
</div>

<!-- Task Details Modal -->
<div id="kbTaskOverlay" style="display:none; position:fixed; inset:0; background: rgba(15,23,42,.55); z-index: 2000;">
    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; padding: 20px;">
        <div style="width: min(760px, 96vw); background:#fff; border-radius: 20px; box-shadow: 0 30px 70px rgba(0,0,0,.25); overflow:hidden; border:1px solid #e2e8f0;">
            <div style="padding: 18px 20px; background: #0f172a; color:#fff; display:flex; justify-content: space-between; align-items:center; gap: 12px;">
                <div style="font-weight: 900; letter-spacing: -.3px;" id="kbTitle">Atividade</div>
                <button type="button" onclick="closeKanbanTaskModal()" style="border:none; background: rgba(255,255,255,.12); color:#fff; width: 36px; height: 36px; border-radius: 10px; font-weight: 900; cursor:pointer;">×</button>
            </div>
            <div style="padding: 18px 20px;">
                <div style="display:flex; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                    <span id="kbStatus" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;">Status</span>
                    <span id="kbPriorityTag" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#eef2ff; color:#4f46e5; border:1px solid #e0e7ff;">Prioridade</span>
                    <span id="kbDueTag" style="font-size:.75rem; font-weight:900; padding: 6px 10px; border-radius: 999px; background:#ecfeff; color:#0891b2; border:1px solid #cffafe;">Prazo</span>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div style="padding: 12px 14px; background:#f8fafc; border:1px solid #f1f5f9; border-radius: 14px;">
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900;">Responsável</div>
                        <div id="kbAssigneeName" style="font-weight:900; color:#0f172a;">-</div>
                    </div>
                    <div style="padding: 12px 14px; background:#f8fafc; border:1px solid #f1f5f9; border-radius: 14px;">
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900;">Projeto</div>
                        <div style="font-weight:900; color:#0f172a;">{{ $project->name }}</div>
                    </div>
                </div>

                <div id="kbReadOnlyBox" style="padding: 14px; background:#fff; border:1px solid #e2e8f0; border-radius: 14px;">
                    <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; font-weight:900; margin-bottom: 6px;">Descrição</div>
                    <div id="kbDesc" style="color:#334155; font-weight: 600; line-height: 1.5;">-</div>
                </div>

                @if(($canManageAll ?? false))
                    <div id="kbEditBox" style="display:none; margin-top: 14px;">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label style="display:block; font-weight:900; color:#94a3b8; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom: 8px;">Título</label>
                                <input id="kbTitleInput" type="text" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; font-weight: 800;">
                            </div>
                            <div class="col-md-12">
                                <label style="display:block; font-weight:900; color:#94a3b8; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom: 8px;">Descrição</label>
                                <textarea id="kbDescInput" rows="4" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; font-weight: 700;"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label style="display:block; font-weight:900; color:#94a3b8; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom: 8px;">Prioridade</label>
                                <select id="kbPriority" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; font-weight: 900;">
                                    <option value="low">🟢 Normal</option>
                                    <option value="medium">🟠 Importante</option>
                                    <option value="high">🔴 Crítica</option>
                                    <option value="critical">🟥 Crítica+</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label style="display:block; font-weight:900; color:#94a3b8; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom: 8px;">Prazo</label>
                                <input id="kbDue" type="date" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; font-weight: 900;">
                            </div>
                            @if(($canManageAll ?? false) && isset($users))
                                <div class="col-md-12">
                                    <label style="display:block; font-weight:900; color:#94a3b8; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; margin-bottom: 8px;">Responsável</label>
                                    <select id="kbAssignee" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; font-weight: 900;">
                                        <option value="">Sem responsável</option>
                                        @foreach($users as $u)
                                            <option value="{{ (int) $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div style="padding: 14px 20px; background:#f8fafc; border-top:1px solid #f1f5f9; display:flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="display:flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="kbMoveTodo" onclick="kbMove('todo')" class="btn-outline" style="padding: 10px 12px; border-radius: 12px; font-weight: 900;">Pipeline</button>
                    <button type="button" id="kbMoveDoing" onclick="kbMove('doing')" class="btn-outline" style="padding: 10px 12px; border-radius: 12px; font-weight: 900;">Execução</button>
                    <button type="button" id="kbMoveDone" onclick="kbMove('done')" class="btn-outline" style="padding: 10px 12px; border-radius: 12px; font-weight: 900;">Concluir</button>
                </div>
                @if(($canManageAll ?? false))
                    <div style="display:flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" id="kbEditToggle" onclick="toggleKbEdit()" class="btn-outline" style="padding: 10px 12px; border-radius: 12px; font-weight: 900;">Editar</button>
                        <button type="button" id="kbSaveBtn" onclick="kbSave()" class="btn-outline" style="padding: 10px 12px; border-radius: 12px; font-weight: 900; background:#0f172a; color:#fff; border-color:#0f172a;">Salvar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Premium Task Modal -->
<div id="newTaskModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); justify-content: center; align-items: flex-start; z-index: 50000; overflow-y: auto; padding: 22px 16px;">
    <div class="vivensi-card" style="position: relative; background: white; padding: 46px 40px; border-radius: 32px; width: min(550px, 94vw); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 50px 100px rgba(0,0,0,0.3); margin: 0 auto; max-height: calc(100vh - 44px); overflow: auto;">
        <div style="position: sticky; top: 0; display: flex; justify-content: flex-end; background: white; padding-top: 6px; margin-top: -6px; z-index: 5;">
            <button type="button" onclick="closeNewTaskModal()" aria-label="Fechar" title="Fechar" style="border:none; background: #0f172a; color:#fff; width: 38px; height: 38px; border-radius: 14px; font-weight: 900; cursor:pointer; box-shadow: 0 10px 25px rgba(15,23,42,.25);">×</button>
        </div>
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="width: 70px; height: 70px; background: #eef2ff; border-radius: 24px; display: flex; align-items: center; justify-content: center; color: #6366f1; font-size: 1.8rem; margin: 0 auto 20px;">
                <i class="fas fa-tasks"></i>
            </div>
            <h3 style="margin: 0; color: #1e293b; font-weight: 900; letter-spacing: -1px;">Nova Atividade</h3>
            <p style="color: #64748b; font-weight: 500; margin-top: 5px;">Adicione uma nova meta ao projeto.</p>
        </div>
        
        <form id="kbNewTaskForm" action="{{ $basePath . '/tasks' }}" method="POST">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <input type="hidden" name="status" value="todo">
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px;">Título da Atividade</label>
                <input type="text" name="title" class="form-control-vivensi" required 
                       style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; color: #1e293b;" 
                       placeholder="Ex: Definir plano de marketing">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Descrição (opcional)</label>
                <textarea name="description" rows="3" class="form-control-vivensi"
                          style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; color: #1e293b; resize: vertical;"
                          placeholder="Contexto, checklist, links..."></textarea>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Prioridade</label>
                    <select name="priority" class="form-control-vivensi" style="width: 100%; padding: 14px 16px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 800; color: #1e293b;">
                        <option value="low">🟢 Normal</option>
                        <option value="medium" selected>🟠 Importante</option>
                        <option value="high">🔴 Crítica</option>
                        <option value="critical">🟥 Crítica+</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Prazo</label>
                    <input type="date" name="due_date" class="form-control-vivensi"
                           style="width: 100%; padding: 14px 16px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 800; color: #1e293b;">
                </div>
            </div>

            @if(($canManageAll ?? false) && isset($users))
                <div style="margin-top: 18px;">
                    <label style="display: block; font-weight: 800; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Responsável</label>
                    <select name="assigned_to" class="form-control-vivensi" style="width: 100%; padding: 14px 16px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 800; color: #1e293b;">
                        <option value="">Mantenha comigo</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <div style="display: flex; gap: 15px; margin-top: 40px;">
                <button type="button" onclick="closeNewTaskModal()" 
                        style="flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 18px; border-radius: 16px; font-weight: 800; cursor: pointer; transition: all 0.2s;">
                    Cancelar
                </button>
                <button type="submit" id="kbNewTaskSubmit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 18px; font-weight: 800; border-radius: 16px;">
                    Registrar Meta <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const __kanbanBasePath = @json($basePath);
    const __kanbanCanManageAll = @json((bool) ($canManageAll ?? false));
    let __kanbanDragging = false;
    let __kbCurrentEl = null;
    let __kbCurrent = null;

    function openNewTaskModal() {
        // avoid overlapping modals
        try { closeKanbanTaskModal(); } catch (e) {}
        const m = document.getElementById('newTaskModal');
        if (m) m.style.display = 'flex';
    }

    function closeNewTaskModal() {
        const m = document.getElementById('newTaskModal');
        if (m) m.style.display = 'none';
    }

    function kbToast(message, type) {
        const t = document.createElement('div');
        const ok = (type || 'success') === 'success';
        t.style.position = 'fixed';
        t.style.right = '18px';
        t.style.bottom = '18px';
        t.style.zIndex = '60000';
        t.style.maxWidth = 'min(420px, 92vw)';
        t.style.padding = '12px 14px';
        t.style.borderRadius = '14px';
        t.style.fontWeight = '900';
        t.style.boxShadow = '0 20px 50px rgba(0,0,0,.20)';
        t.style.background = ok ? '#0f172a' : '#991b1b';
        t.style.color = '#fff';
        t.style.border = ok ? '1px solid rgba(255,255,255,.12)' : '1px solid rgba(255,255,255,.18)';
        t.textContent = String(message || '');
        document.body.appendChild(t);
        setTimeout(() => { try { t.remove(); } catch (e) {} }, 2600);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('kbNewTaskForm');
        if (!form) return;

        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();

            const submitBtn = document.getElementById('kbNewTaskSubmit');
            const oldHtml = submitBtn ? submitBtn.innerHTML : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.75';
                submitBtn.innerHTML = 'Registrando...';
            }

            try {
                const fd = new FormData(form);
                const payload = Object.fromEntries(fd.entries());

                const res = await fetch(__kanbanBasePath + '/api/tasks/create', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    let msg = '';
                    try {
                        const jerr = await res.json();
                        if (jerr?.message) msg += jerr.message;
                        if (jerr?.errors) msg += '\n' + Object.values(jerr.errors).flat().join('\n');
                    } catch (e) {
                        msg = await res.text();
                    }
                    alert('Não foi possível criar (' + res.status + ').\n\n' + msg);
                    return;
                }

                const j = await res.json();
                if (!j?.html) {
                    kbToast('Atividade criada.', 'success');
                    closeNewTaskModal();
                    return;
                }

                const lane = j.lane || 'todo';
                const col = document.querySelector('.column-body-prem[data-status="' + lane + '"]');
                if (col) {
                    const wrap = document.createElement('div');
                    wrap.innerHTML = j.html;
                    const el = wrap.firstElementChild;
                    if (el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(6px)';
                        col.prepend(el);
                        requestAnimationFrame(() => {
                            el.style.transition = 'all .25s ease';
                            el.style.opacity = '1';
                            el.style.transform = 'none';
                        });
                    }
                }

                updateCounts();
                kbToast('Meta registrada com sucesso.', 'success');
                form.reset();
                closeNewTaskModal();
            } catch (e) {
                alert('Falha de conexão ao criar.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = oldHtml ?? 'Registrar Meta';
                }
            }
        });
    });

    function allowDrop(ev) {
        ev.preventDefault();
        ev.currentTarget.classList.add('drag-over');
    }

    function dragLeave(ev) {
        if (ev && ev.currentTarget) ev.currentTarget.classList.remove('drag-over');
    }

    function drag(ev) {
        __kanbanDragging = true;
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
            const fromCol = el.closest('.column-body-prem');
            const fromStatus = fromCol ? (fromCol.getAttribute('data-status') || '') : '';
            ev.currentTarget.appendChild(el);
            el.style.opacity = '1';
            el.style.transform = 'none';

            var taskId = (data.split('-')[1] || '').trim();
            
            updateCounts();

            fetch(__kanbanBasePath + '/api/tasks/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id: taskId, status: status })
            }).then(response => {
                if(!response.ok) {
                   // rollback
                   if (fromCol) fromCol.appendChild(el);
                   updateCounts();
                   alert('Sem permissão ou falha ao mover a tarefa (' + response.status + ').');
                }
            }).catch(() => {
                if (fromCol) fromCol.appendChild(el);
                updateCounts();
                alert('Falha de conexão ao mover a tarefa.');
            });
        }
    }

    function updateCounts() {
        const cols = document.querySelectorAll('.column-body-prem[data-status]');
        cols.forEach(col => {
            const st = col.getAttribute('data-status');
            const badge = document.querySelector('[data-colcount="' + st + '"]');
            if (badge) badge.innerText = col.querySelectorAll('.task-card-prem').length;
        });
    }
    
    document.addEventListener("dragend", function(event) {
        __kanbanDragging = false;
        event.target.style.opacity = "1";
        event.target.style.transform = 'none';
        document.querySelectorAll('.column-body-prem').forEach(col => col.classList.remove('drag-over'));
    });

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function kbStatusMeta(s) {
        const st = String(s || 'todo').toLowerCase();
        if (st === 'done') return { label: 'CONCLUÍDO', bg: '#ecfdf5', color: '#065f46', border: '#a7f3d0' };
        if (st === 'doing') return { label: 'EM EXECUÇÃO', bg: '#fffbeb', color: '#92400e', border: '#fde68a' };
        return { label: 'PIPELINE', bg: '#f1f5f9', color: '#0f172a', border: '#e2e8f0' };
    }

    function kbPriorityMeta(p) {
        const pr = String(p || 'medium').toLowerCase();
        if (pr === 'critical') return { label: 'CRÍTICO+', bg: '#fef2f2', color: '#991b1b', border: '#fecaca', dot: '#b91c1c' };
        if (pr === 'high') return { label: 'CRÍTICO', bg: '#fff1f2', color: '#9f1239', border: '#fecdd3', dot: '#f43f5e' };
        if (pr === 'low') return { label: 'SUPORTE', bg: '#ecfdf5', color: '#065f46', border: '#a7f3d0', dot: '#10b981' };
        return { label: 'ESTRATÉGICO', bg: '#fffbeb', color: '#92400e', border: '#fde68a', dot: '#f59e0b' }; // medium/default
    }

    function kbParseISODate(iso) {
        // iso: YYYY-MM-DD
        if (!iso || typeof iso !== 'string' || iso.length < 10) return null;
        const d = new Date(iso + 'T00:00:00');
        return isNaN(d.getTime()) ? null : d;
    }

    function kbDueMeta(isoDate) {
        const d = kbParseISODate(isoDate);
        if (!d) return { label: 'Sem prazo', bg: '#ecfeff', color: '#0891b2', border: '#cffafe' };

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const diffDays = Math.round((d.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const label = `Prazo: ${dd}/${mm}`;

        if (diffDays < 0) return { label, bg: '#fef2f2', color: '#991b1b', border: '#fecaca' }; // overdue
        if (diffDays <= 3) return { label, bg: '#fffbeb', color: '#92400e', border: '#fde68a' }; // due soon
        return { label, bg: '#ecfeff', color: '#0891b2', border: '#cffafe' }; // ok
    }

    let __kbBusy = false;
    function kbSetBusy(busy, label) {
        __kbBusy = !!busy;
        const ids = ['kbMoveTodo', 'kbMoveDoing', 'kbMoveDone', 'kbEditToggle', 'kbSaveBtn'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.disabled = __kbBusy;
            el.style.opacity = __kbBusy ? '0.65' : '1';
            el.style.pointerEvents = __kbBusy ? 'none' : '';
        });
        const saveBtn = document.getElementById('kbSaveBtn');
        if (saveBtn) saveBtn.innerText = __kbBusy ? (label || 'Salvando...') : 'Salvar';
    }

    function openKanbanTaskModal(el) {
        if (__kanbanDragging) return;
        if (!el || !el.dataset) return;
        let t = null;
        try { t = JSON.parse(el.dataset.task || '{}'); } catch (e) { t = null; }
        if (!t || !t.id) return;

        __kbCurrentEl = el;
        __kbCurrent = t;

        document.getElementById('kbTitle').innerText = t.title || 'Atividade';

        const stMeta = kbStatusMeta(t.status);
        const stEl = document.getElementById('kbStatus');
        if (stEl) {
            stEl.innerText = stMeta.label;
            stEl.style.background = stMeta.bg;
            stEl.style.color = stMeta.color;
            stEl.style.borderColor = stMeta.border;
        }

        const prMeta = kbPriorityMeta(t.priority);
        const prEl = document.getElementById('kbPriorityTag');
        if (prEl) {
            prEl.innerText = prMeta.label;
            prEl.style.background = prMeta.bg;
            prEl.style.color = prMeta.color;
            prEl.style.borderColor = prMeta.border;
        }

        const dueMeta = kbDueMeta(t.due_date);
        const dueEl = document.getElementById('kbDueTag');
        if (dueEl) {
            dueEl.innerText = dueMeta.label;
            dueEl.style.background = dueMeta.bg;
            dueEl.style.color = dueMeta.color;
            dueEl.style.borderColor = dueMeta.border;
        }

        document.getElementById('kbAssigneeName').innerText = t.assignee_name || '—';
        document.getElementById('kbDesc').innerHTML = escapeHtml(t.description || '—').replace(/\n/g, '<br>');

        // edit defaults
        const ti = document.getElementById('kbTitleInput');
        const di = document.getElementById('kbDescInput');
        const pi = document.getElementById('kbPriority');
        const du = document.getElementById('kbDue');
        if (ti) ti.value = t.title || '';
        if (di) di.value = t.description || '';
        if (pi) pi.value = t.priority || 'medium';
        if (du) du.value = t.due_date || '';

        const as = document.getElementById('kbAssignee');
        if (as) as.value = t.assigned_to || '';

        // permissions (server-side also enforced)
        const canMove = __kanbanCanManageAll || !!t.can_move;
        const canEdit = __kanbanCanManageAll; // only managers can edit fields in UI

        const moveTodo = document.getElementById('kbMoveTodo');
        const moveDoing = document.getElementById('kbMoveDoing');
        const moveDone = document.getElementById('kbMoveDone');
        if (moveTodo) moveTodo.style.display = canMove ? '' : 'none';
        if (moveDoing) moveDoing.style.display = canMove ? '' : 'none';
        if (moveDone) moveDone.style.display = canMove ? '' : 'none';

        const editBtn = document.getElementById('kbEditToggle');
        const saveBtn = document.getElementById('kbSaveBtn');
        if (editBtn) editBtn.style.display = canEdit ? '' : 'none';
        if (saveBtn) saveBtn.style.display = canEdit ? '' : 'none';

        // start in read-only
        const eb = document.getElementById('kbEditBox');
        if (eb) eb.style.display = 'none';
        document.getElementById('kbReadOnlyBox').style.display = '';

        kbSetBusy(false);
        document.getElementById('kbTaskOverlay').style.display = 'block';
    }

    function closeKanbanTaskModal() {
        document.getElementById('kbTaskOverlay').style.display = 'none';
        __kbCurrentEl = null;
        __kbCurrent = null;
    }

    function toggleKbEdit() {
        if (!__kanbanCanManageAll) return;
        const eb = document.getElementById('kbEditBox');
        const rb = document.getElementById('kbReadOnlyBox');
        if (!eb || !rb) return;
        const showing = eb.style.display !== 'none';
        eb.style.display = showing ? 'none' : '';
        rb.style.display = showing ? '' : 'none';
    }

    async function kbSave() {
        if (!__kanbanCanManageAll) {
            alert('Sem permissão para editar esta atividade.');
            return;
        }
        if (!__kbCurrent || !__kbCurrent.id) return;
        const payload = {
            id: __kbCurrent.id,
            title: document.getElementById('kbTitleInput')?.value || null,
            description: document.getElementById('kbDescInput')?.value || null,
            priority: document.getElementById('kbPriority')?.value || null,
            due_date: document.getElementById('kbDue')?.value || null,
        };
        const as = document.getElementById('kbAssignee');
        if (as) payload.assigned_to = as.value || null;

        try {
            kbSetBusy(true, 'Salvando...');
            const res = await fetch(__kanbanBasePath + '/api/tasks/update', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (!res.ok) {
                let msg = '';
                try {
                    const jerr = await res.json();
                    if (jerr?.message) msg += jerr.message;
                    if (jerr?.errors) msg += '\n' + Object.values(jerr.errors).flat().join('\n');
                } catch (e) {
                    msg = await res.text();
                }
                alert('Não foi possível salvar (' + res.status + ').\n\n' + msg);
                kbSetBusy(false);
                return;
            }
            const j = await res.json();
            if (!j || !j.task) return;
            const t = j.task;

            // update modal
            document.getElementById('kbTitle').innerText = t.title || 'Atividade';

            const prMeta = kbPriorityMeta(t.priority);
            const prEl = document.getElementById('kbPriorityTag');
            if (prEl) {
                prEl.innerText = prMeta.label;
                prEl.style.background = prMeta.bg;
                prEl.style.color = prMeta.color;
                prEl.style.borderColor = prMeta.border;
            }

            const dueMeta = kbDueMeta(t.due_date);
            const dueEl = document.getElementById('kbDueTag');
            if (dueEl) {
                dueEl.innerText = dueMeta.label;
                dueEl.style.background = dueMeta.bg;
                dueEl.style.color = dueMeta.color;
                dueEl.style.borderColor = dueMeta.border;
            }

            document.getElementById('kbAssigneeName').innerText = t.assignee_name || '—';
            document.getElementById('kbDesc').innerHTML = escapeHtml(t.description || '—').replace(/\n/g, '<br>');

            // update card DOM
            if (__kbCurrentEl) {
                __kbCurrentEl.dataset.task = JSON.stringify({ ...__kbCurrent, ...t });
                const titleEl = __kbCurrentEl.querySelector('.task-title');
                if (titleEl) titleEl.innerText = t.title || '';
                const dueEl = __kbCurrentEl.querySelector('.task-due-label');
                if (dueEl) dueEl.innerText = t.due_label || 'S/P';

                const dotEl = __kbCurrentEl.querySelector('.task-priority-dot');
                if (dotEl) dotEl.style.background = prMeta.dot;
                const labelEl = __kbCurrentEl.querySelector('.task-priority-label');
                if (labelEl) labelEl.innerText = prMeta.label;

                const assigneeBox = __kbCurrentEl.querySelector('.task-assignee');
                if (assigneeBox) {
                    if (t.assignee_name) {
                        assigneeBox.style.borderStyle = 'solid';
                        assigneeBox.style.borderWidth = '2px';
                        assigneeBox.style.borderColor = 'white';
                        assigneeBox.style.background = '#f1f5f9';
                        assigneeBox.style.color = '#64748b';
                        assigneeBox.title = t.assignee_name;
                        assigneeBox.innerHTML = '<span class="task-assignee-initial">' + escapeHtml(t.assignee_initial || '') + '</span>';
                    } else {
                        assigneeBox.title = 'Sem responsável';
                        assigneeBox.style.background = 'transparent';
                        assigneeBox.style.color = '#94a3b8';
                        assigneeBox.style.border = '1.5px dashed #e2e8f0';
                        assigneeBox.innerHTML = '<i class="fas fa-user-plus"></i>';
                    }
                }
            }

            // update current object
            __kbCurrent = { ...__kbCurrent, ...t };
            toggleKbEdit();
            kbSetBusy(false);
        } catch (e) {
            alert('Falha de conexão ao salvar.');
            kbSetBusy(false);
        }
    }

    async function kbMove(status) {
        if (!__kbCurrent || !__kbCurrent.id) return;
        try {
            kbSetBusy(true, 'Movendo...');
            const res = await fetch(__kanbanBasePath + '/api/tasks/update-status', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: __kbCurrent.id, status })
            });
            if (!res.ok) {
                const txt = await res.text();
                alert('Não foi possível mover (' + res.status + ').\n\n' + txt);
                kbSetBusy(false);
                return;
            }

            // move DOM card
            if (__kbCurrentEl) {
                const targetCol = document.querySelector('.column-body-prem[data-status="' + status + '"]');
                if (targetCol) targetCol.prepend(__kbCurrentEl);
                updateCounts();
            }

            __kbCurrent.status = status;
            if (__kbCurrentEl) {
                __kbCurrentEl.dataset.task = JSON.stringify({ ...__kbCurrent });
            }
            const stMeta = kbStatusMeta(status);
            const stEl = document.getElementById('kbStatus');
            if (stEl) {
                stEl.innerText = stMeta.label;
                stEl.style.background = stMeta.bg;
                stEl.style.color = stMeta.color;
                stEl.style.borderColor = stMeta.border;
            }
            kbSetBusy(false);
        } catch (e) {
            alert('Falha de conexão ao mover.');
            kbSetBusy(false);
        }
    }

    // close modal clicking outside
    document.addEventListener('click', function(ev) {
        const overlay = document.getElementById('kbTaskOverlay');
        if (!overlay || overlay.style.display !== 'block') return;
        if (ev.target === overlay) closeKanbanTaskModal();
    });

    // ESC closes task modal
    document.addEventListener('keydown', function(ev) {
        if (ev.key !== 'Escape') return;
        const kb = document.getElementById('kbTaskOverlay');
        if (kb && kb.style.display === 'block') {
            closeKanbanTaskModal();
            return;
        }
        const nt = document.getElementById('newTaskModal');
        if (nt && nt.style.display !== 'none') {
            closeNewTaskModal();
        }
    });

    // click outside closes "new task" modal
    document.addEventListener('click', function(ev) {
        const nt = document.getElementById('newTaskModal');
        if (!nt || nt.style.display === 'none') return;
        if (ev.target === nt) closeNewTaskModal();
    });
</script>
@endsection
