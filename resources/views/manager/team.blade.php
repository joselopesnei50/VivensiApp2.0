@extends('layouts.app')

@section('content')
@php
    $basePath      = rtrim(request()->getBaseUrl(), '/');
    $totalMembers  = $employees->count();
    $activeCount   = $employees->where('status', 'active')->count();
    $managerCount  = $employees->where('role', 'manager')->count();
    $overdueCount  = $employees->sum('tasks_overdue_count');
    $avatarColors  = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899'];
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Gestão / Equipe
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Capital Humano</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gerencie colaboradores, papéis e performance da equipe.</p>
    </div>
    <button class="btn btn-primary fw-bold rounded-3 d-flex align-items-center gap-2 flex-shrink-0"
            data-bs-toggle="modal" data-bs-target="#addEmployeeModal" style="margin-top:4px;">
        <i class="fas fa-user-plus"></i> Adicionar Colaborador
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-users" style="color:#6366f1;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:#4f46e5;line-height:1.1;">{{ $totalMembers }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">colaboradores</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-circle-check" style="color:#10b981;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:#059669;line-height:1.1;">{{ $activeCount }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">ativos</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-user-tie" style="color:#f59e0b;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:#d97706;line-height:1.1;">{{ $managerCount }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">gestores</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-triangle-exclamation" style="color:#ef4444;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:{{ $overdueCount > 0 ? '#dc2626' : '#94a3b8' }};line-height:1.1;">{{ $overdueCount }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">tarefas atrasadas</div>
        </div>
    </div>
</div>

{{-- Filter bar --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="d-flex gap-3 align-items-center flex-wrap">
            {{-- Search --}}
            <div style="position:relative;flex:1;min-width:220px;">
                <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem;"></i>
                <input id="teamSearch" type="text" placeholder="Buscar por nome ou e-mail..."
                       class="form-control rounded-3 ps-4" style="font-size:.85rem;border-color:#e2e8f0;background:#f8fafc;">
            </div>

            {{-- Role filters --}}
            <div class="d-flex gap-1 align-items-center">
                <button type="button" class="filter-pill active" data-trole="all"    onclick="setTeamRoleFilter(this)">Todos</button>
                <button type="button" class="filter-pill"        data-trole="manager" onclick="setTeamRoleFilter(this)">Gestores</button>
                <button type="button" class="filter-pill"        data-trole="employee" onclick="setTeamRoleFilter(this)">Funcionários</button>
            </div>

            <span style="width:1px;height:22px;background:#e2e8f0;"></span>

            {{-- Status filters --}}
            <div class="d-flex gap-1 align-items-center">
                <button type="button" class="filter-pill active" data-tstatus="all"      onclick="setTeamStatusFilter(this)">Todos</button>
                <button type="button" class="filter-pill"        data-tstatus="active"   onclick="setTeamStatusFilter(this)">Ativos</button>
                <button type="button" class="filter-pill"        data-tstatus="inactive" onclick="setTeamStatusFilter(this)">Inativos</button>
            </div>

            <button type="button" onclick="clearTeamFilters()" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.78rem;white-space:nowrap;">
                <i class="fas fa-xmark me-1"></i> Limpar
            </button>
        </div>

        <div id="teamFilterHint" class="text-muted mt-2" style="font-size:.75rem;font-weight:600;"></div>
    </div>
</div>

{{-- Grid --}}
<div id="teamGrid" class="row g-3">
    @forelse($employees as $e)
    @php
        $role       = (string) ($e->role ?? '');
        $status     = (string) ($e->status ?? '');
        $searchText = mb_strtolower(trim(($e->name ?? '') . ' ' . ($e->email ?? '')));
        $open       = (int) ($e->tasks_open_count ?? 0);
        $overdue    = (int) ($e->tasks_overdue_count ?? 0);
        $dueSoon    = (int) ($e->tasks_due_soon_count ?? 0);
        $projCount  = (int) ($e->project_members_count ?? 0);
        $colorIdx   = $e->id % 6;
        $bg         = $avatarColors[$colorIdx];
        $isActive   = $status === 'active';
        $isManager  = $role === 'manager';
    @endphp
    <div class="col-xl-3 col-lg-4 col-md-6">
        <a href="{{ $basePath . '/manager/team/' . $e->id }}"
           class="employee-card"
           data-role="{{ $role }}"
           data-status="{{ $status }}"
           data-search="{{ e($searchText) }}"
           data-open="{{ $open }}"
           data-overdue="{{ $overdue }}"
           data-projects="{{ $projCount }}">

            {{-- Card top: avatar + name --}}
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="emp-avatar" style="background:{{ $bg }};">
                    {{ strtoupper(substr($e->name, 0, 1)) }}
                </div>
                <div style="min-width:0;flex:1;">
                    <div class="fw-bold text-truncate" style="font-size:.92rem;color:#0f172a;">{{ $e->name }}</div>
                    <div class="text-truncate" style="font-size:.75rem;color:#94a3b8;">{{ $e->email }}</div>
                </div>
                <span class="status-dot-badge {{ $isActive ? 'dot-active' : 'dot-inactive' }}"
                      title="{{ $isActive ? 'Ativo' : 'Inativo' }}"></span>
            </div>

            {{-- Badges --}}
            <div class="d-flex gap-1 flex-wrap mb-3">
                <span class="emp-badge {{ $isManager ? 'badge-manager' : 'badge-employee' }}">
                    {{ $isManager ? 'Gestor' : 'Funcionário' }}
                </span>
                @if($overdue > 0)
                    <span class="emp-badge badge-danger">{{ $overdue }} atraso{{ $overdue > 1 ? 's' : '' }}</span>
                @elseif($dueSoon > 0)
                    <span class="emp-badge badge-warn">{{ $dueSoon }} vencendo</span>
                @endif
                @if($open >= 10)
                    <span class="emp-badge badge-dark">Sobrecarregado</span>
                @endif
                @if($projCount === 0)
                    <span class="emp-badge badge-muted">Sem projetos</span>
                @endif
            </div>

            {{-- Stats --}}
            <div class="d-flex gap-3 pt-2 border-top">
                <div class="text-center" style="flex:1;">
                    <div class="fw-800" style="font-size:1.1rem;color:#0f172a;">{{ $projCount }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;">Projetos</div>
                </div>
                <div style="width:1px;background:#f1f5f9;"></div>
                <div class="text-center" style="flex:1;">
                    <div class="fw-800" style="font-size:1.1rem;color:{{ $overdue > 0 ? '#dc2626' : '#0f172a' }};">{{ $open }}</div>

                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;">Tarefas</div>
                </div>
                <div style="width:1px;background:#f1f5f9;"></div>
                <div class="d-flex align-items-center justify-content-center" style="flex:1;">
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:.75rem;"></i>
                </div>
            </div>

        </a>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <div style="width:60px;height:60px;background:rgba(99,102,241,.1);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="fas fa-users" style="font-size:1.4rem;color:#6366f1;"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">Nenhum colaborador cadastrado</h6>
            <p class="text-muted small mb-4" style="max-width:300px;margin-inline:auto;">
                Adicione o primeiro colaborador para começar a gerenciar sua equipe.
            </p>
            <div>
                <button class="btn btn-primary fw-bold rounded-3 px-4"
                        data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-user-plus me-2"></i> Adicionar Colaborador
                </button>
            </div>
        </div>
    </div>
    @endforelse
</div>

{{-- Modal --}}
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" style="color:#0f172a;">Novo Colaborador</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Preencha os dados para criar o acesso.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $basePath . '/manager/team/store-quick' }}" method="POST">
                @csrf
                <input type="hidden" name="access_level" value="viewer">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Nome Completo</label>
                        <input type="text" name="name" class="form-control rounded-3" required placeholder="Ex: Maria Souza">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">E-mail Profissional</label>
                        <input type="email" name="email" class="form-control rounded-3" required placeholder="maria@empresa.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Senha Temporária</label>
                        <input type="password" name="password" class="form-control rounded-3" required placeholder="Defina uma senha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Função</label>
                        <select name="role" class="form-select rounded-3" required>
                            <option value="employee">Funcionário (Operacional)</option>
                            <option value="manager">Gestor de Equipe</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Vincular a Projeto (Opcional)</label>
                        <select name="project_id" class="form-select rounded-3">
                            <option value="">Sem vínculo inicial</option>
                            @foreach(($projects ?? []) as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted mt-1" style="font-size:.75rem;">Você pode vincular a outros projetos depois.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2">
                        <i class="fas fa-user-plus me-2"></i> Finalizar Cadastro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

/* Employee card */
.employee-card {
    display: block;
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #f1f5f9;
    text-decoration: none;
    color: inherit;
    height: 100%;
    position: relative;
    overflow: hidden;
    transition: box-shadow .2s, border-color .2s, transform .2s;
}
.employee-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.07);
    border-color: #e0e7ff;
    transform: translateY(-3px);
    color: inherit;
}
.employee-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 3px; height: 100%;
    background: #6366f1;
    opacity: 0;
    transition: opacity .2s;
}
.employee-card:hover::before { opacity: 1; }

/* Avatar */
.emp-avatar {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; font-weight: 800; color: white;
    flex-shrink: 0;
}

/* Status dot */
.status-dot-badge {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-active   { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.2); }
.dot-inactive { background: #cbd5e1; }

/* Badges */
.emp-badge {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 20px;
    font-size: .63rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    white-space: nowrap;
}
.badge-manager  { background: #eef2ff; color: #4338ca; }
.badge-employee { background: #f1f5f9; color: #475569; }
.badge-danger   { background: #fee2e2; color: #b91c1c; }
.badge-warn     { background: #fff7ed; color: #c2410c; }
.badge-dark     { background: #0f172a; color: #fff; }
.badge-muted    { background: #f1f5f9; color: #94a3b8; }

/* Filter pills */
.filter-pill {
    background: #f1f5f9; border: none;
    padding: 5px 13px; border-radius: 20px;
    font-size: .75rem; font-weight: 700;
    color: #64748b; cursor: pointer;
    transition: all .15s; white-space: nowrap;
}
.filter-pill:hover { background: #e2e8f0; }
.filter-pill.active { background: #0f172a; color: #fff; }
</style>
@endpush

<script>
    let __teamRole = 'all';
    let __teamStatus = 'all';

    function setFilterPillActive(selector, activePredicate) {
        document.querySelectorAll(selector).forEach(b => {
            b.classList.toggle('active', activePredicate(b));
        });
    }

    function setTeamRoleFilter(btn) {
        __teamRole = btn?.dataset?.trole || 'all';
        setFilterPillActive('[data-trole]', b => b.dataset.trole === __teamRole);
        applyTeamFilters();
    }

    function setTeamStatusFilter(btn) {
        __teamStatus = btn?.dataset?.tstatus || 'all';
        setFilterPillActive('[data-tstatus]', b => b.dataset.tstatus === __teamStatus);
        applyTeamFilters();
    }

    function clearTeamFilters() {
        const inp = document.getElementById('teamSearch');
        if (inp) inp.value = '';
        __teamRole   = 'all';
        __teamStatus = 'all';
        setFilterPillActive('[data-trole]',   b => b.dataset.trole === 'all');
        setFilterPillActive('[data-tstatus]', b => b.dataset.tstatus === 'all');
        applyTeamFilters();
    }

    function applyTeamFilters() {
        const inp = document.getElementById('teamSearch');
        const q   = (inp?.value ?? '').trim().toLowerCase();
        const cols = document.querySelectorAll('#teamGrid .employee-card[data-role]');
        let shown = 0;

        cols.forEach(card => {
            const roleOk   = __teamRole   === 'all' || card.dataset.role   === __teamRole;
            const statusOk = __teamStatus === 'all' || card.dataset.status === __teamStatus;
            const searchOk = !q || (card.dataset.search || '').includes(q);
            const ok = roleOk && statusOk && searchOk;
            const col = card.closest('[class*="col-"]') || card.parentElement;
            if (col) col.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });

        const hint = document.getElementById('teamFilterHint');
        if (hint) {
            const roleMap = { all: 'Todos', manager: 'Gestores', employee: 'Funcionários' };
            const stMap   = { all: 'Todos', active: 'Ativos', inactive: 'Inativos' };
            hint.innerText = `${shown} colaborador(es) · Função: ${roleMap[__teamRole] || __teamRole} · Status: ${stMap[__teamStatus] || __teamStatus}` + (q ? ` · "${q}"` : '');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const inp = document.getElementById('teamSearch');
        if (inp) inp.addEventListener('input', applyTeamFilters);
        setTeamRoleFilter(document.querySelector('[data-trole="all"]'));
        setTeamStatusFilter(document.querySelector('[data-tstatus="all"]'));
    });
</script>
@endsection
