@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp

<style>
    :root {
        --accent-indigo: #6366f1;
        --bg-glass: rgba(255, 255, 255, 0.9);
        --text-main: #1e293b;
    }
    body { font-family: 'Outfit', sans-serif; background: #f8fafc; }

    .team-header {
        background: white;
        padding: 30px;
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        margin-bottom: 30px;
        border: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .employee-card {
        background: white;
        border-radius: 24px;
        padding: 24px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .employee-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        border-color: var(--accent-indigo);
    }

    .employee-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: var(--accent-indigo);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .employee-card:hover::before { opacity: 1; }

    .avatar-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: var(--accent-indigo);
        margin-bottom: 20px;
        border: 2px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .role-badge {
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        background: #eef2ff;
        color: #4f46e5;
    }

    .btn-add-team {
        background: var(--text-main);
        color: white;
        padding: 12px 24px;
        border-radius: 16px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
    }

    .btn-add-team:hover {
        background: #000;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .stats-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 15px;
    }
</style>

<div class="container-fluid py-4 px-5">
    
    <div class="team-header shadow-sm" style="border-left: 8px solid var(--accent-indigo);">
        <div>
            <h1 class="fw-800 m-0 text-dark" style="letter-spacing: -1.5px; font-size: 2.5rem;">Gestão de Capital Humano</h1>
            <p class="text-slate-500 m-0 mt-1 fw-500">Administre sua equipe, atribua papéis e monitore a performance em tempo real.</p>
        </div>
        <button class="btn-add-team shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-user-plus me-2"></i> Adicionar Colaborador
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="vivensi-card mb-3" style="padding: 16px 18px; border-radius: 18px;">
        <div style="display:flex; gap: 12px; align-items:center; justify-content: space-between; flex-wrap: wrap;">
            <div style="display:flex; gap: 10px; align-items:center; flex: 1; min-width: 260px;">
                <div style="position: relative; flex: 1;">
                    <i class="fas fa-search" style="position:absolute; left: 14px; top: 12px; color:#94a3b8;"></i>
                    <input id="teamSearch" type="text" placeholder="Buscar por nome ou e-mail..."
                           style="width: 100%; padding: 10px 12px 10px 38px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 700; color:#0f172a;">
                </div>
                <button type="button" onclick="clearTeamFilters()" class="btn-outline" style="padding: 8px 12px; border-radius: 12px; font-weight: 900;">
                    Limpar
                </button>
            </div>

            <div style="display:flex; gap: 8px; flex-wrap: wrap; align-items:center;">
                <button type="button" class="btn-outline" data-trole="all" onclick="setTeamRoleFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Todos</button>
                <button type="button" class="btn-outline" data-trole="manager" onclick="setTeamRoleFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Gestores</button>
                <button type="button" class="btn-outline" data-trole="employee" onclick="setTeamRoleFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Funcionários</button>
                <span style="width:1px; height: 26px; background:#e2e8f0; margin: 0 2px;"></span>
                <button type="button" class="btn-outline" data-tstatus="all" onclick="setTeamStatusFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Todos</button>
                <button type="button" class="btn-outline" data-tstatus="active" onclick="setTeamStatusFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Ativos</button>
                <button type="button" class="btn-outline" data-tstatus="inactive" onclick="setTeamStatusFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Inativos</button>
            </div>
        </div>

        <div id="teamFilterHint" style="margin-top: 10px; color:#94a3b8; font-weight:800; font-size:.85rem;"></div>
    </div>

    <div id="teamGrid" class="row g-4">
        @foreach($employees as $e)
        @php
            $role = (string) ($e->role ?? '');
            $status = (string) ($e->status ?? '');
            $searchText = mb_strtolower(trim(($e->name ?? '') . ' ' . ($e->email ?? '')));
            $open = (int) ($e->tasks_open_count ?? 0);
            $overdue = (int) ($e->tasks_overdue_count ?? 0);
            $dueSoon = (int) ($e->tasks_due_soon_count ?? 0);
        @endphp
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="{{ $basePath . '/manager/team/'.$e->id }}"
               class="employee-card"
               data-role="{{ $role }}"
               data-status="{{ $status }}"
               data-search="{{ e($searchText) }}"
               data-open="{{ $open }}"
               data-overdue="{{ $overdue }}">
                <div class="avatar-wrapper">
                    {{ substr($e->name, 0, 1) }}
                </div>
                <div>
                    <div style="display:flex; gap: 8px; align-items:center; flex-wrap: wrap;">
                        <span class="role-badge">{{ $e->role == 'manager' ? 'Gestor' : 'Funcionário' }}</span>
                        @if(($e->project_members_count ?? 0) == 0)
                            <span class="role-badge" style="background:#f1f5f9; color:#475569;">Sem projetos</span>
                        @endif
                        @if($overdue > 0)
                            <span class="role-badge" style="background:#fee2e2; color:#b91c1c;">{{ $overdue }} atraso(s)</span>
                        @elseif($dueSoon > 0)
                            <span class="role-badge" style="background:#fff7ed; color:#c2410c;">{{ $dueSoon }} vencendo</span>
                        @endif
                        @if($open >= 10)
                            <span class="role-badge" style="background:#0f172a; color:#fff;">Sobrecarregado</span>
                        @endif
                    </div>
                    <h5 class="fw-bold mt-2 mb-1">{{ $e->name }}</h5>
                    <p class="text-muted small mb-0">{{ $e->email }}</p>
                </div>
                
                <div class="stats-pill">
                    <i class="fas fa-briefcase"></i>
                    <span>{{ $e->project_members_count ?? 0 }} Projetos Alocados</span>
                </div>
                <div class="stats-pill">
                    <i class="fas fa-list-check"></i>
                    <span>{{ $open }} Tarefas em aberto</span>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    @if($e->status == 'active')
                        <span class="text-success small fw-bold"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Ativo</span>
                    @else
                        <span class="text-muted small fw-bold"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Inativo</span>
                    @endif
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                </div>

            </a>
        </div>
        @endforeach
    </div>
</div>

<script>
    let __teamRole = 'all';
    let __teamStatus = 'all';

    function setBtnActive(nodesSelector, activePredicate) {
        document.querySelectorAll(nodesSelector).forEach(b => {
            const active = activePredicate(b);
            b.style.background = active ? '#0f172a' : '';
            b.style.color = active ? '#fff' : '';
            b.style.borderColor = active ? '#0f172a' : '';
        });
    }

    function setTeamRoleFilter(btn) {
        __teamRole = btn && btn.dataset ? (btn.dataset.trole || 'all') : 'all';
        setBtnActive('[data-trole]', b => b.dataset.trole === __teamRole);
        applyTeamFilters();
    }

    function setTeamStatusFilter(btn) {
        __teamStatus = btn && btn.dataset ? (btn.dataset.tstatus || 'all') : 'all';
        setBtnActive('[data-tstatus]', b => b.dataset.tstatus === __teamStatus);
        applyTeamFilters();
    }

    function clearTeamFilters() {
        const inp = document.getElementById('teamSearch');
        if (inp) inp.value = '';
        __teamRole = 'all';
        __teamStatus = 'all';
        setBtnActive('[data-trole]', b => b.dataset.trole === 'all');
        setBtnActive('[data-tstatus]', b => b.dataset.tstatus === 'all');
        applyTeamFilters();
    }

    function applyTeamFilters() {
        const inp = document.getElementById('teamSearch');
        const q = (inp && inp.value ? inp.value : '').toString().trim().toLowerCase();

        const cols = document.querySelectorAll('#teamGrid .employee-card[data-role]');
        let shown = 0;
        cols.forEach(card => {
            const role = (card.dataset.role || '');
            const st = (card.dataset.status || '');
            const s = (card.dataset.search || '');

            const roleOk = (__teamRole === 'all') ? true : (role === __teamRole);
            const statusOk = (__teamStatus === 'all') ? true : (st === __teamStatus);
            const searchOk = (!q) ? true : (s.indexOf(q) !== -1);

            const ok = roleOk && statusOk && searchOk;
            const col = card.closest('.col-xl-3, .col-lg-4, .col-md-6') || card.parentElement;
            if (col) col.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });

        const hint = document.getElementById('teamFilterHint');
        if (hint) {
            const roleMap = { all: 'Todos', manager: 'Gestores', employee: 'Funcionários' };
            const stMap = { all: 'Todos', active: 'Ativos', inactive: 'Inativos' };
            hint.innerText =
                'Mostrando ' + shown + ' colaborador(es)' +
                ' • Função: ' + (roleMap[__teamRole] || __teamRole) +
                ' • Status: ' + (stMap[__teamStatus] || __teamStatus) +
                (q ? (' • Busca: "' + q + '"') : '');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('teamSearch');
        if (inp) inp.addEventListener('input', applyTeamFilters);
        const roleDefault = document.querySelector('[data-trole="all"]');
        const stDefault = document.querySelector('[data-tstatus="all"]');
        if (roleDefault) setTeamRoleFilter(roleDefault);
        if (stDefault) setTeamStatusFilter(stDefault);
        applyTeamFilters();
    });
</script>

<!-- Modal Cadastrar Equipe -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold">Novo Colaborador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $basePath . '/manager/team/store-quick' }}" method="POST">
                @csrf
                <input type="hidden" name="access_level" value="viewer">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nome Completo</label>
                        <input type="text" name="name" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="Ex: Maria Souza">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">E-mail Profissional</label>
                        <input type="email" name="email" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="maria@empresa.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Senha Temporária</label>
                        <input type="password" name="password" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="Defina uma senha">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Função / Hierarquia</label>
                        <select name="role" class="form-select rounded-3 py-2 bg-light border-0" required>
                            <option value="employee">Funcionário (Operacional)</option>
                            <option value="manager">Gestor de Equipe</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Vincular a um Projeto (Opcional)</label>
                        <select name="project_id" class="form-select rounded-3 py-2 bg-light border-0">
                            <option value="">Sem vínculo inicial</option>
                            @foreach(($projects ?? []) as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1">Você pode vincular a outros projetos depois.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold">Finalizar Cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
