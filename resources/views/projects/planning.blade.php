@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $canManageProject = in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true);
    $statusLabels = [
        'not_started' => ['Nao iniciada', '#94a3b8', '#f1f5f9'],
        'in_progress' => ['Em andamento', '#0ea5e9', '#e0f2fe'],
        'completed'   => ['Concluida',    '#10b981', '#ecfdf5'],
    ];
    $milestoneLabels = [
        'pending' => ['Pendente',   '#f59e0b', '#fffbeb'],
        'reached' => ['Alcancado',  '#10b981', '#ecfdf5'],
        'missed'  => ['Perdido',    '#ef4444', '#fef2f2'],
    ];
@endphp

<style>
    .planning-tab {
        padding: 10px 18px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all var(--ds-transition);
    }
    .planning-tab.is-active {
        background: var(--ds-brand);
        color: #fff;
        box-shadow: 0 4px 14px var(--ds-brand-shadow);
    }
    .planning-tab:not(.is-active) {
        background: transparent;
        color: var(--ds-text-muted);
        border: 1px solid var(--ds-border);
    }
    .planning-tab:not(.is-active):hover {
        color: var(--ds-brand);
        border-color: var(--ds-brand);
    }
    .planning-label {
        display: block;
        font-weight: 700;
        color: var(--ds-text-muted);
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    .planning-textarea, .planning-input {
        width: 100%;
        background: #fff;
        border: 1px solid var(--ds-border);
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 0.9rem;
        font-family: inherit;
        outline: none;
        transition: border-color var(--ds-transition);
    }
    .planning-textarea:focus, .planning-input:focus {
        border-color: var(--ds-brand);
    }
    .planning-textarea[readonly], .planning-input[readonly] {
        background: var(--ds-bg);
        cursor: default;
    }
    .planning-card {
        background: var(--ds-surface);
        border-radius: 20px;
        border: 1px solid var(--ds-border-light);
        padding: 28px;
        margin-bottom: 20px;
    }
    .planning-section-title {
        font-size: 1.2rem;
        font-weight: 900;
        color: var(--ds-text);
        margin: 0 0 24px;
        letter-spacing: -0.5px;
    }
    .item-card {
        background: #fff;
        border: 1px solid var(--ds-border-light);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 12px;
    }
    .item-card .item-title {
        font-weight: 800;
        color: var(--ds-text);
        font-size: 0.95rem;
        margin-bottom: 6px;
    }
    .item-card .item-meta {
        font-size: 0.75rem;
        color: var(--ds-text-muted);
        font-weight: 600;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: var(--ds-radius-full);
        font-weight: 800;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .specific-objectives-list .so-row {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 8px;
    }
    .specific-objectives-list .so-row input {
        flex: 1;
    }
    .so-remove-btn {
        background: var(--ds-danger-bg);
        color: var(--ds-danger);
        border: none;
        border-radius: 10px;
        padding: 8px 12px;
        cursor: pointer;
    }
</style>

<!-- Header com Tabs -->
<div class="header-page" style="margin-bottom: 32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background:var(--ds-brand); width:12px; height:3px; border-radius:2px;"></span>
                <h6 style="color:var(--ds-brand); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">Planejamento Estrategico</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.2rem; letter-spacing:-1px;">{{ $project->name }}</h2>
            <p style="color:#64748b; margin:6px 0 0; font-size:0.95rem; font-weight:500;">Apresentacao institucional, objetivos, metas e marcos planejados.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ $basePath . '/projects/details/' . $project->id }}" class="planning-tab">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="{{ $basePath . '/projects/' . $project->id . '/kanban' }}" class="planning-tab">
                <i class="fas fa-tasks"></i> Kanban
            </a>
            <a href="{{ route('projects.planning.show', $project->id) }}" class="planning-tab is-active">
                <i class="fas fa-bullseye"></i> Planejamento
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="border-radius:14px; border:1px solid #bbf7d0; background:#ecfdf5; color:#065f46; padding:12px 16px; margin-bottom:18px; font-weight:700;">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="border-radius:14px; padding:12px 16px; margin-bottom:18px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<!-- Apresentacao Institucional + Objetivos -->
<form action="{{ route('projects.planning.update', $project->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="planning-card">
        <h4 class="planning-section-title"><i class="fas fa-building me-2" style="color:var(--ds-brand);"></i>Apresentacao Institucional</h4>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="planning-label" for="presentation">Descricao Institucional</label>
                <textarea id="presentation" name="presentation" rows="5" class="planning-textarea" placeholder="Quem e a organizacao e o proposito do projeto..." {{ !$canManageProject ? 'readonly' : '' }}>{{ old('presentation', $project->presentation) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="planning-label" for="justification">Justificativa</label>
                <textarea id="justification" name="justification" rows="5" class="planning-textarea" placeholder="Por que este projeto e necessario..." {{ !$canManageProject ? 'readonly' : '' }}>{{ old('justification', $project->justification) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="planning-label" for="context">Contexto do Projeto</label>
                <textarea id="context" name="context" rows="5" class="planning-textarea" placeholder="Realidade local, dados, situacao a transformar..." {{ !$canManageProject ? 'readonly' : '' }}>{{ old('context', $project->context) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="planning-label" for="target_audience">Publico-Alvo</label>
                <textarea id="target_audience" name="target_audience" rows="5" class="planning-textarea" placeholder="Quem sera beneficiado e em que numero..." {{ !$canManageProject ? 'readonly' : '' }}>{{ old('target_audience', $project->target_audience) }}</textarea>
            </div>
        </div>
    </div>

    <div class="planning-card">
        <h4 class="planning-section-title"><i class="fas fa-flag me-2" style="color:var(--ds-brand);"></i>Objetivos</h4>

        <div class="mb-4">
            <label class="planning-label" for="general_objective">Objetivo Geral</label>
            <textarea id="general_objective" name="general_objective" rows="3" class="planning-textarea" placeholder="O proposito central que orienta todo o projeto..." {{ !$canManageProject ? 'readonly' : '' }}>{{ old('general_objective', $project->general_objective) }}</textarea>
        </div>

        <label class="planning-label">Objetivos Especificos</label>
        <div id="specific-objectives-list" class="specific-objectives-list">
            @php $existingSos = old('specific_objectives', $project->specific_objectives ?? []); @endphp
            @if(!empty($existingSos))
                @foreach($existingSos as $so)
                    <div class="so-row">
                        <input type="text" name="specific_objectives[]" value="{{ $so }}" class="planning-input" placeholder="Ex.: Atender 50 familias com cesta basica mensal" {{ !$canManageProject ? 'readonly' : '' }}>
                        @if($canManageProject)
                            <button type="button" class="so-remove-btn" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        @if($canManageProject)
            <button type="button" id="btn-add-so" class="btn-ds btn-ds-outline" style="margin-top:8px; font-weight:700;">
                <i class="fas fa-plus me-2"></i>Adicionar objetivo especifico
            </button>
        @endif
    </div>

    @if($canManageProject)
        <div style="margin-bottom:24px;">
            <button type="submit" class="btn-premium btn-premium-shine">
                <i class="fas fa-save me-2"></i>Salvar Apresentacao e Objetivos
            </button>
        </div>
    @endif
</form>

<!-- Metas Qualitativas -->
<div class="planning-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <h4 class="planning-section-title" style="margin:0;"><i class="fas fa-bullseye me-2" style="color:var(--ds-brand);"></i>Metas Qualitativas</h4>
        @if($canManageProject)
            <button type="button" class="btn-premium btn-premium-shine" data-bs-toggle="modal" data-bs-target="#newGoalModal">
                <i class="fas fa-plus me-2"></i>Nova Meta
            </button>
        @endif
    </div>

    @forelse($goals as $goal)
        @php [$gLabel, $gColor, $gBg] = $statusLabels[$goal->status] ?? $statusLabels['not_started']; @endphp
        <div class="item-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                <div style="flex:1; min-width:240px;">
                    <div class="item-title">{{ $goal->title }}</div>
                    @if($goal->description)
                        <p style="margin:0 0 8px; color:#475569; font-size:0.88rem; line-height:1.55;">{{ $goal->description }}</p>
                    @endif
                    @if($goal->indicator)
                        <div class="item-meta" style="margin-bottom:6px;"><i class="fas fa-tachometer-alt me-1"></i><strong>Indicador:</strong> {{ $goal->indicator }}</div>
                    @endif
                    <div class="item-meta">
                        @if($goal->due_date)
                            <i class="far fa-calendar-alt me-1"></i>Prazo: {{ $goal->due_date->format('d/m/Y') }}
                        @else
                            <i class="far fa-calendar-alt me-1"></i>Sem prazo definido
                        @endif
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="status-pill" style="background:{{ $gBg }}; color:{{ $gColor }};">{{ $gLabel }}</span>
                    @if($canManageProject)
                        <div style="display:flex; gap:6px;">
                            <form action="{{ route('projects.planning.goals.update', [$project->id, $goal->id]) }}" method="POST" style="display:inline;">
                                @csrf @method('PUT')
                                <select name="status" onchange="this.form.submit()" class="planning-input" style="padding:6px 10px; font-size:0.75rem; border-radius:8px;">
                                    @foreach($statusLabels as $key => [$lbl,,])
                                        <option value="{{ $key }}" {{ $goal->status === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form action="{{ route('projects.planning.goals.destroy', [$project->id, $goal->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remover esta meta?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="so-remove-btn" style="padding:6px 10px; font-size:0.75rem;"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center; padding:40px; color:var(--ds-text-faint);">
            <i class="fas fa-bullseye" style="font-size:2.5rem; margin-bottom:12px; display:block; color:var(--ds-border);"></i>
            <span style="font-weight:600;">Nenhuma meta cadastrada ainda.</span>
        </div>
    @endforelse
</div>

<!-- Marcos Planejados -->
<div class="planning-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <h4 class="planning-section-title" style="margin:0;"><i class="fas fa-flag-checkered me-2" style="color:var(--ds-brand);"></i>Marcos Planejados</h4>
        @if($canManageProject)
            <button type="button" class="btn-premium btn-premium-shine" data-bs-toggle="modal" data-bs-target="#newMilestoneModal">
                <i class="fas fa-plus me-2"></i>Novo Marco
            </button>
        @endif
    </div>
    <p style="margin:-8px 0 18px; color:var(--ds-text-faint); font-size:0.82rem;">Marcos sao datas planejadas (entregas previstas). A <strong>Linha do Tempo de Impacto</strong> no Dashboard registra o que ja aconteceu.</p>

    @forelse($milestones as $milestone)
        @php
            [$mLabel, $mColor, $mBg] = $milestoneLabels[$milestone->status] ?? $milestoneLabels['pending'];
            $isOverdue = $milestone->status === 'pending' && $milestone->target_date && $milestone->target_date->isPast();
        @endphp
        <div class="item-card" style="@if($isOverdue) border-left:4px solid #ef4444; @endif">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                <div style="flex:1; min-width:240px;">
                    <div class="item-title">{{ $milestone->title }}</div>
                    @if($milestone->description)
                        <p style="margin:0 0 8px; color:#475569; font-size:0.88rem; line-height:1.55;">{{ $milestone->description }}</p>
                    @endif
                    <div class="item-meta">
                        <i class="far fa-calendar-alt me-1"></i>Previsto: {{ $milestone->target_date->format('d/m/Y') }}
                        @if($milestone->completed_at)
                            · <i class="fas fa-check-circle me-1" style="color:#10b981;"></i>Concluido em {{ $milestone->completed_at->format('d/m/Y') }}
                        @elseif($isOverdue)
                            · <span style="color:#ef4444; font-weight:700;">Em atraso</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="status-pill" style="background:{{ $mBg }}; color:{{ $mColor }};">{{ $mLabel }}</span>
                    @if($canManageProject)
                        <div style="display:flex; gap:6px;">
                            <form action="{{ route('projects.planning.milestones.update', [$project->id, $milestone->id]) }}" method="POST" style="display:inline;">
                                @csrf @method('PUT')
                                <select name="status" onchange="this.form.submit()" class="planning-input" style="padding:6px 10px; font-size:0.75rem; border-radius:8px;">
                                    @foreach($milestoneLabels as $key => [$lbl,,])
                                        <option value="{{ $key }}" {{ $milestone->status === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form action="{{ route('projects.planning.milestones.destroy', [$project->id, $milestone->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remover este marco?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="so-remove-btn" style="padding:6px 10px; font-size:0.75rem;"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align:center; padding:40px; color:var(--ds-text-faint);">
            <i class="fas fa-flag-checkered" style="font-size:2.5rem; margin-bottom:12px; display:block; color:var(--ds-border);"></i>
            <span style="font-weight:600;">Nenhum marco planejado ainda.</span>
        </div>
    @endforelse
</div>

@if($canManageProject)
    <!-- Modal: Nova Meta -->
    <div class="modal fade" id="newGoalModal" role="dialog" aria-modal="true" aria-labelledby="newGoalModalLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:24px; border:none;">
                <form action="{{ route('projects.planning.goals.store', $project->id) }}" method="POST">
                    @csrf
                    <div class="modal-header" style="border:none; padding:24px 28px 0;">
                        <h4 class="modal-title fw-900" id="newGoalModalLabel" style="color:#1e293b;">Nova Meta</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding:18px 28px;">
                        <div class="mb-3">
                            <label class="planning-label">Titulo da Meta *</label>
                            <input type="text" name="title" required maxlength="255" class="planning-input" placeholder="Ex.: Fortalecer rede com Conselho Tutelar">
                        </div>
                        <div class="mb-3">
                            <label class="planning-label">Descricao</label>
                            <textarea name="description" rows="3" class="planning-textarea" placeholder="Detalhes da meta..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="planning-label">Indicador (como medir)</label>
                            <textarea name="indicator" rows="2" class="planning-textarea" placeholder="Ex.: Numero de reunioes mensais com o Conselho"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="planning-label">Status inicial</label>
                                <select name="status" class="planning-input">
                                    <option value="not_started">Nao iniciada</option>
                                    <option value="in_progress">Em andamento</option>
                                    <option value="completed">Concluida</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="planning-label">Prazo</label>
                                <input type="date" name="due_date" class="planning-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border:none; padding:0 28px 24px;">
                        <button type="submit" class="btn-premium btn-premium-shine w-100"><i class="fas fa-bullseye me-2"></i>Cadastrar Meta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Novo Marco -->
    <div class="modal fade" id="newMilestoneModal" role="dialog" aria-modal="true" aria-labelledby="newMilestoneModalLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:24px; border:none;">
                <form action="{{ route('projects.planning.milestones.store', $project->id) }}" method="POST">
                    @csrf
                    <div class="modal-header" style="border:none; padding:24px 28px 0;">
                        <h4 class="modal-title fw-900" id="newMilestoneModalLabel" style="color:#1e293b;">Novo Marco</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding:18px 28px;">
                        <div class="mb-3">
                            <label class="planning-label">Titulo do Marco *</label>
                            <input type="text" name="title" required maxlength="255" class="planning-input" placeholder="Ex.: Entrega da Fase 1">
                        </div>
                        <div class="mb-3">
                            <label class="planning-label">Descricao</label>
                            <textarea name="description" rows="3" class="planning-textarea" placeholder="O que sera entregue neste marco..."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="planning-label">Data Prevista *</label>
                                <input type="date" name="target_date" required class="planning-input">
                            </div>
                            <div class="col-md-6">
                                <label class="planning-label">Status inicial</label>
                                <select name="status" class="planning-input">
                                    <option value="pending">Pendente</option>
                                    <option value="reached">Alcancado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border:none; padding:0 28px 24px;">
                        <button type="submit" class="btn-premium btn-premium-shine w-100"><i class="fas fa-flag-checkered me-2"></i>Cadastrar Marco</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canManageProject)
<script>
    (function() {
        const btn = document.getElementById('btn-add-so');
        const list = document.getElementById('specific-objectives-list');
        if (!btn || !list) return;
        btn.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'so-row';
            row.innerHTML = '<input type="text" name="specific_objectives[]" class="planning-input" placeholder="Ex.: Atender 50 familias com cesta basica mensal">' +
                '<button type="button" class="so-remove-btn" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>';
            list.appendChild(row);
            row.querySelector('input').focus();
        });
    })();
</script>
@endif

@endsection
