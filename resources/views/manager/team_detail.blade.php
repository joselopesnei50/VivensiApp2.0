@extends('layouts.app')

@section('content')
@php
    $basePath     = rtrim(request()->getBaseUrl(), '/');
    $avatarColors = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899'];
    $avatarColor  = $avatarColors[$employee->id % 6];
    $isManager    = $employee->role === 'manager';
    $isActive     = $employee->status === 'active';

    $statusMap = [
        'todo'        => ['label' => 'A fazer',      'bg' => 'rgba(99,102,241,.1)',  'color' => '#4f46e5'],
        'in_progress' => ['label' => 'Em progresso', 'bg' => 'rgba(245,158,11,.1)',  'color' => '#d97706'],
        'completed'   => ['label' => 'Concluído',    'bg' => 'rgba(16,185,129,.1)',  'color' => '#059669'],
        'done'        => ['label' => 'Concluído',    'bg' => 'rgba(16,185,129,.1)',  'color' => '#059669'],
        'cancelled'   => ['label' => 'Cancelado',    'bg' => 'rgba(100,116,139,.1)', 'color' => '#475569'],
    ];
    $priorityMap = [
        'high'   => ['label' => 'Alta',   'bg' => 'rgba(239,68,68,.1)',   'color' => '#dc2626'],
        'medium' => ['label' => 'Média',  'bg' => 'rgba(245,158,11,.1)',  'color' => '#d97706'],
        'low'    => ['label' => 'Baixa',  'bg' => 'rgba(100,116,139,.1)', 'color' => '#475569'],
    ];
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <a href="{{ $basePath . '/manager/team' }}"
       class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center gap-1 flex-shrink-0"
       style="padding:6px 14px;font-size:.82rem;margin-top:6px;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Gestão / Equipe / Perfil
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">{{ $employee->name }}</h2>
    </div>
    <button class="btn btn-primary fw-bold rounded-3 d-flex align-items-center gap-2 flex-shrink-0"
            data-bs-toggle="modal" data-bs-target="#addReminderModal" style="margin-top:4px;">
        <i class="fas fa-plus"></i> Nova Tarefa
    </button>
</div>

{{-- Profile card --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            {{-- Avatar --}}
            <div style="width:72px;height:72px;border-radius:20px;background:{{ $avatarColor }};display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;flex-shrink:0;">
                {{ strtoupper(substr($employee->name, 0, 1)) }}
            </div>

            {{-- Info --}}
            <div style="flex:1;min-width:0;">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="fw-bold" style="font-size:1.1rem;color:#0f172a;">{{ $employee->name }}</span>
                    <span class="role-pill {{ $isManager ? 'pill-manager' : 'pill-employee' }}">
                        {{ $isManager ? 'Gestor' : 'Funcionário' }}
                    </span>
                    <span class="role-pill {{ $isActive ? 'pill-active' : 'pill-inactive' }}">
                        {{ $isActive ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
                <div style="font-size:.83rem;color:#64748b;">
                    <i class="far fa-envelope me-1"></i> {{ $employee->email }}
                </div>
            </div>

            {{-- Mini stats --}}
            <div class="d-flex gap-3">
                <div class="text-center px-3 py-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;min-width:72px;">
                    <div class="fw-800" style="font-size:1.4rem;color:#4f46e5;line-height:1;">{{ $projects->count() }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-top:2px;">Projetos</div>
                </div>
                <div class="text-center px-3 py-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;min-width:72px;">
                    <div class="fw-800" style="font-size:1.4rem;color:#0f172a;line-height:1;">{{ $reminders->count() }}</div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-top:2px;">Tarefas</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Content columns --}}
<div class="row g-4">

    {{-- Projects --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width:30px;height:30px;background:rgba(99,102,241,.1);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-layer-group" style="color:#6366f1;font-size:.8rem;"></i>
                    </span>
                    <h6 class="fw-bold mb-0" style="font-size:.9rem;color:#0f172a;">Projetos em que atua</h6>
                </div>

                @forelse($projects as $p)
                <a href="{{ $basePath . '/projects/' . $p->project->id }}"
                   class="project-row text-decoration-none">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-folder-open" style="color:#6366f1;font-size:.8rem;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-bold text-truncate" style="font-size:.85rem;color:#0f172a;">{{ $p->project->name }}</div>
                        <div style="font-size:.7rem;color:#94a3b8;">{{ ucfirst($p->access_level ?? 'membro') }}</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:.7rem;flex-shrink:0;"></i>
                </a>
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:rgba(99,102,241,.08);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                        <i class="fas fa-folder-open" style="color:#a5b4fc;font-size:1rem;"></i>
                    </div>
                    <p class="text-muted mb-0" style="font-size:.82rem;">Nenhum projeto alocado ainda.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Reminders / Tasks --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:30px;height:30px;background:rgba(245,158,11,.1);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-bell" style="color:#f59e0b;font-size:.8rem;"></i>
                        </span>
                        <h6 class="fw-bold mb-0" style="font-size:.9rem;color:#0f172a;">Lembretes & Tarefas</h6>
                    </div>
                    <button class="btn btn-sm btn-outline-primary rounded-3 fw-bold"
                            style="font-size:.75rem;"
                            data-bs-toggle="modal" data-bs-target="#addReminderModal">
                        <i class="fas fa-plus me-1"></i> Nova Tarefa
                    </button>
                </div>

                @forelse($reminders as $r)
                @php
                    $s = $statusMap[$r->status]   ?? ['label' => ucfirst($r->status), 'bg' => 'rgba(100,116,139,.1)', 'color' => '#475569'];
                    $pr = $priorityMap[$r->priority] ?? ['label' => ucfirst($r->priority ?? ''), 'bg' => 'rgba(100,116,139,.1)', 'color' => '#475569'];
                    $isDone = in_array($r->status, ['completed', 'done']);
                @endphp
                <div class="reminder-row {{ $loop->last ? '' : 'mb-2' }}">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                        <div class="fw-bold {{ $isDone ? 'text-decoration-line-through text-muted' : '' }}"
                             style="font-size:.88rem;color:#0f172a;">{{ $r->title }}</div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            @if($r->priority)
                            <span class="detail-pill" style="background:{{ $pr['bg'] }};color:{{ $pr['color'] }};">{{ $pr['label'] }}</span>
                            @endif
                            <span class="detail-pill" style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">{{ $s['label'] }}</span>
                        </div>
                    </div>

                    @if($r->description)
                    <p class="text-muted mb-1" style="font-size:.78rem;line-height:1.5;">{{ Str::limit($r->description, 120) }}</p>
                    @endif

                    <div class="d-flex align-items-center gap-3 mt-1">
                        @if($r->project)
                        <span style="font-size:.7rem;color:#64748b;display:inline-flex;align-items:center;gap:4px;">
                            <i class="fas fa-folder-open" style="font-size:.6rem;"></i>{{ $r->project->name }}
                        </span>
                        @endif
                        <span style="font-size:.7rem;color:#94a3b8;display:inline-flex;align-items:center;gap:4px;">
                            <i class="far fa-clock" style="font-size:.6rem;"></i>{{ $r->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <div style="width:44px;height:44px;background:rgba(245,158,11,.1);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                        <i class="fas fa-calendar-check" style="color:#fbbf24;font-size:1rem;"></i>
                    </div>
                    <p class="text-muted mb-0" style="font-size:.82rem;">Nenhuma tarefa enviada recentemente.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- Modal Nova Tarefa --}}
<div class="modal fade" id="addReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" style="color:#0f172a;">Nova Tarefa para {{ $employee->name }}</h5>
                    <p class="text-muted mb-0" style="font-size:.8rem;">A tarefa será atribuída diretamente ao colaborador.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $basePath . '/tasks' }}" method="POST">
                @csrf
                <input type="hidden" name="assigned_to" value="{{ $employee->id }}">
                <input type="hidden" name="priority" value="medium">
                <input type="hidden" name="status" value="todo">
                <input type="hidden" name="redirect_to_schedule" value="1">

                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Assunto / Título</label>
                        <input type="text" name="title" class="form-control rounded-3" required placeholder="Ex: Revisar planilha de custos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Instrução Detalhada</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Descreva o que o colaborador precisa fazer..."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="letter-spacing:.05em;">Projeto Relacionado (Opcional)</label>
                        <select name="project_id" class="form-select rounded-3">
                            <option value="">Nenhum específico</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->project->id }}">{{ $p->project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Tarefa
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

.role-pill {
    display: inline-flex; align-items: center;
    padding: 2px 9px; border-radius: 20px;
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
}
.pill-manager  { background: #eef2ff; color: #4338ca; }
.pill-employee { background: #f1f5f9; color: #475569; }
.pill-active   { background: #dcfce7; color: #166534; }
.pill-inactive { background: #f1f5f9; color: #94a3b8; }

.project-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 12px; border-radius: 12px;
    border: 1px solid #f1f5f9;
    margin-bottom: 8px;
    transition: border-color .15s, background .15s;
    color: inherit;
    text-decoration: none;
}
.project-row:hover { border-color: #c7d2fe; background: #fafafa; color: inherit; }

.reminder-row {
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    background: #fafafa;
    margin-bottom: 8px;
}

.detail-pill {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 20px;
    font-size: .63rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    white-space: nowrap;
}
</style>
@endpush
@endsection
