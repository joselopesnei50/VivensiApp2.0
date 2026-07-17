@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $basePath = rtrim(request()->getBaseUrl(), '/');
    $canManage = in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true);

    $statusLabels = [
        'pending'     => ['Pendente',    '#64748b', '#f1f5f9'],
        'in_progress' => ['Em andamento','#2563eb', '#dbeafe'],
        'completed'   => ['Concluida',   '#059669', '#d1fae5'],
        'cancelled'   => ['Cancelada',   '#dc2626', '#fee2e2'],
    ];

    // Range da timeline: min(start_date) -> max(end_date).
    $starts = $stages->pluck('start_date')->filter();
    $ends   = $stages->pluck('end_date')->filter();
    $rangeStart = $starts->min();
    $rangeEnd   = $ends->max();
    $totalDays  = ($rangeStart && $rangeEnd) ? max(1, $rangeStart->diffInDays($rangeEnd) + 1) : 0;
@endphp

<style>
    .stages-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 24px;
        padding: 32px;
        color: white;
        margin-bottom: 24px;
    }
    .stage-card {
        background: white;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        transition: box-shadow .2s, transform .2s;
        cursor: grab;
    }
    .stage-card:hover { box-shadow: 0 8px 24px rgba(15,23,42,.08); }
    .stage-card.is-current { border-color: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }
    .stage-card.dragging { opacity: .45; }
    .stage-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 999px;
        font-size: .72rem; font-weight: 800; letter-spacing: .3px;
    }
    .stage-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .stage-btn {
        border: 1px solid #e2e8f0; background: white; color: #334155;
        padding: 6px 10px; border-radius: 10px; font-size: .78rem; font-weight: 700;
        cursor: pointer; transition: all .15s;
    }
    .stage-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
    .stage-btn-danger { color: #dc2626; border-color: #fecaca; }
    .stage-btn-danger:hover { background: #fef2f2; }
    .stage-btn-primary { background: #0f172a; color: white; border-color: #0f172a; }
    .stage-btn-primary:hover { background: #1e293b; }

    /* Timeline Gantt-lite */
    .gantt-wrap { background: #f8fafc; border-radius: 16px; padding: 20px; margin-bottom: 24px; overflow-x: auto; }
    .gantt-row { display: grid; grid-template-columns: 220px 1fr; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
    .gantt-row:last-child { border-bottom: none; }
    .gantt-label { font-weight: 700; color: #1e293b; font-size: .88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gantt-track { position: relative; height: 24px; background: #e2e8f0; border-radius: 999px; min-width: 400px; }
    .gantt-bar { position: absolute; top: 0; bottom: 0; border-radius: 999px; box-shadow: inset 0 0 0 1px rgba(0,0,0,.05); }
</style>

<div class="stages-hero">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="opacity: .6; font-size: .75rem; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">Etapas do Projeto</div>
            <h1 style="margin: 4px 0 0; font-weight: 950; font-size: 2.2rem; letter-spacing: -1px;">{{ $project->name }}</h1>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ $basePath . '/projects/' . $project->id }}" style="background: rgba(255,255,255,.08); color: white; padding: 10px 16px; border-radius: 12px; font-weight: 800; text-decoration: none;">
                <i class="fas fa-arrow-left me-1"></i> Voltar ao projeto
            </a>
            @if($canManage)
                <button type="button" class="btn-premium" data-bs-toggle="modal" data-bs-target="#stageModal" onclick="stageModalOpen()" style="border: none; background: white; color: #0f172a; padding: 10px 18px; border-radius: 12px; font-weight: 900;">
                    <i class="fas fa-plus me-1"></i> Nova etapa
                </button>
            @endif
        </div>
    </div>

    @if($currentStage)
        <div style="margin-top: 24px; background: rgba(37,99,235,.15); border: 1px solid rgba(37,99,235,.4); border-radius: 16px; padding: 16px 20px;">
            <div style="opacity: .8; font-size: .72rem; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase;">Etapa atual</div>
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 6px;">
                <div>
                    <div style="font-size: 1.3rem; font-weight: 900;">{{ $currentStage->title }}</div>
                    <div style="opacity: .7; font-size: .85rem; margin-top: 4px;">
                        @if($currentStage->start_date && $currentStage->end_date)
                            {{ $currentStage->start_date->format('d/m/Y') }} → {{ $currentStage->end_date->format('d/m/Y') }}
                        @endif
                        @php
                            $prog = $currentStage->progress_percent;
                        @endphp
                        @if($prog !== null)
                            · {{ $prog }}% de tarefas concluidas
                        @endif
                    </div>
                </div>
                <div style="font-size: 1.4rem; font-weight: 900;">R$ {{ number_format((float) $currentStage->planned_value, 2, ',', '.') }}</div>
            </div>
        </div>
    @endif
</div>

@if($calculatedBudget !== null)
    <div style="margin-bottom: 20px; padding: 14px 18px; background: #ecfdf5; border: 1px solid #86efac; border-radius: 14px; display: flex; align-items: center; gap: 10px; font-weight: 700; color: #065f46;">
        <i class="fas fa-info-circle"></i>
        Orcamento calculado pelas etapas: <strong>R$ {{ number_format($calculatedBudget, 2, ',', '.') }}</strong> (soma de planned_value das etapas nao canceladas)
    </div>
@endif

{{-- Timeline Gantt-lite --}}
@if($rangeStart && $rangeEnd)
    <div class="gantt-wrap">
        <div style="font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-stream me-2" style="color: #2563eb;"></i> Timeline</span>
            <span style="font-size: .8rem; color: #64748b; font-weight: 700;">{{ $rangeStart->format('d/m/Y') }} → {{ $rangeEnd->format('d/m/Y') }}</span>
        </div>
        @foreach($stages as $s)
            @php
                if (! $s->start_date || ! $s->end_date) continue;
                $offsetDays = $rangeStart->diffInDays($s->start_date);
                $lenDays    = max(1, $s->start_date->diffInDays($s->end_date) + 1);
                $leftPct    = ($offsetDays / $totalDays) * 100;
                $widthPct   = ($lenDays / $totalDays) * 100;
                [$stLabel, $stColor, $stBg] = $statusLabels[$s->status] ?? $statusLabels['pending'];
            @endphp
            <div class="gantt-row">
                <div class="gantt-label" title="{{ $s->title }}">{{ $s->title }}</div>
                <div class="gantt-track">
                    <div class="gantt-bar" style="left: {{ $leftPct }}%; width: {{ $widthPct }}%; background: {{ $stColor }};" title="{{ $stLabel }}"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Cards drag-drop --}}
<div id="stages-grid" class="row g-3" data-project-id="{{ $project->id }}">
    @forelse($stages as $s)
        @php
            $stTuple = $statusLabels[$s->status] ?? $statusLabels['pending'];
            $stLabel = $stTuple[0];
            $stColor = $stTuple[1];
            $stBg    = $stTuple[2];
            $fin = $s->financial_summary;
            $isCurrent = $currentStage && $currentStage->id === $s->id;
            $stagePayload = json_encode([
                'id'            => $s->id,
                'title'         => $s->title,
                'description'   => $s->description,
                'start_date'    => $s->start_date?->toDateString(),
                'end_date'      => $s->end_date?->toDateString(),
                'planned_value' => (float) $s->planned_value,
                'status'        => $s->status,
            ], JSON_HEX_APOS | JSON_HEX_QUOT);
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="stage-card {{ $isCurrent ? 'is-current' : '' }}" data-stage-id="{{ $s->id }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span style="font-size: .7rem; font-weight: 800; color: #94a3b8;">#{{ (int) $s->order }}</span>
                            <span class="stage-badge" style="color: {{ $stColor }}; background: {{ $stBg }};">{{ $stLabel }}</span>
                            @if($isCurrent)
                                <span class="stage-badge" style="color: #2563eb; background: #dbeafe;"><i class="fas fa-play"></i> Atual</span>
                            @endif
                        </div>
                        <a href="{{ $basePath . '/projects/' . $project->id . '/stages/' . $s->id }}"
                           style="font-weight: 900; color: #0f172a; font-size: 1.05rem; line-height: 1.3; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            {{ $s->title }}
                            <i class="fas fa-arrow-right" style="font-size: .75rem; color: #2563eb;"></i>
                        </a>
                        <div style="font-size: .7rem; color: #64748b; font-weight: 700; margin-top: 3px;">Clique para gerenciar tarefas e financeiro da etapa</div>
                    </div>
                </div>

                @if($s->description)
                    <div style="color: #475569; font-size: .85rem; line-height: 1.5; margin-bottom: 12px;">{{ \Illuminate\Support\Str::limit($s->description, 140) }}</div>
                @endif

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">Periodo</div>
                        <div style="font-size: .85rem; color: #1e293b; font-weight: 700;">
                            {{ $s->start_date?->format('d/m') ?? '—' }} → {{ $s->end_date?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: .65rem; font-weight: 800; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">Valor previsto</div>
                        <div style="font-size: .95rem; color: #0f172a; font-weight: 900;">R$ {{ number_format((float) $s->planned_value, 2, ',', '.') }}</div>
                    </div>
                </div>

                @if($s->progress_percent !== null || $fin['income'] > 0 || $fin['expense'] > 0)
                    <div style="border-top: 1px dashed #e2e8f0; padding-top: 10px; margin-bottom: 12px; font-size: .78rem; color: #475569;">
                        @if($s->progress_percent !== null)
                            <div>Tarefas: <strong>{{ $s->progress_percent }}%</strong></div>
                        @endif
                        @if($fin['income'] > 0 || $fin['expense'] > 0)
                            <div>Saldo: <strong style="color: {{ $fin['balance'] >= 0 ? '#059669' : '#dc2626' }};">R$ {{ number_format($fin['balance'], 2, ',', '.') }}</strong></div>
                        @endif
                    </div>
                @endif

                @if($canManage)
                    <div class="stage-actions">
                        <button type="button" class="stage-btn" onclick='stageModalOpen({!! $stagePayload !!})'>
                            <i class="fas fa-pen"></i> Editar
                        </button>
                        @if($s->status !== 'completed')
                            <button type="button" class="stage-btn stage-btn-primary" onclick="completeStage({{ $s->id }}, {{ (float) $s->planned_value }})">
                                <i class="fas fa-check"></i> Concluir
                            </button>
                        @endif
                        <button type="button" class="stage-btn stage-btn-danger" onclick="deleteStage({{ $s->id }})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-12">
            <div style="padding: 60px 20px; text-align: center; background: #f8fafc; border-radius: 20px; border: 2px dashed #cbd5e1;">
                <i class="fas fa-layer-group" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px;"></i>
                <div style="font-weight: 900; color: #1e293b; font-size: 1.1rem;">Nenhuma etapa cadastrada</div>
                <div style="color: #64748b; font-size: .9rem; margin-top: 6px;">Divida seu projeto em etapas com prazo e valor proprios.</div>
                @if($canManage)
                    <button type="button" class="btn-premium" data-bs-toggle="modal" data-bs-target="#stageModal" onclick="stageModalOpen()" style="border: none; background: #0f172a; color: white; padding: 10px 18px; border-radius: 12px; font-weight: 900; margin-top: 16px;">
                        <i class="fas fa-plus me-1"></i> Criar primeira etapa
                    </button>
                @endif
            </div>
        </div>
    @endforelse
</div>

{{-- Modal Nova/Editar --}}
@if($canManage)
<div class="modal fade" id="stageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 22px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 20px 24px;">
                <h5 class="modal-title" style="font-weight: 900; color: #0f172a;" id="stageModalTitle">Nova etapa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stageForm" onsubmit="return submitStageForm(event)">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="_stage_id" id="_stage_id" value="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titulo *</label>
                        <input type="text" name="title" id="_stage_title" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descricao</label>
                        <textarea name="description" id="_stage_description" class="form-control" rows="2" maxlength="5000"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Inicio</label>
                            <input type="date" name="start_date" id="_stage_start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fim</label>
                            <input type="date" name="end_date" id="_stage_end_date" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor previsto (R$)</label>
                            <input type="number" name="planned_value" id="_stage_planned_value" class="form-control" step="0.01" min="0" max="99999999.99" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" id="_stage_status" class="form-control">
                                <option value="pending">Pendente</option>
                                <option value="in_progress">Em andamento</option>
                                <option value="completed">Concluida</option>
                                <option value="cancelled">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div id="_stage_form_errors" style="color: #dc2626; font-weight: 700; margin-top: 12px; font-size: .85rem;"></div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark" style="border-radius: 10px; font-weight: 900;">Salvar etapa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    const projectId = @json($project->id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const baseUrl = @json($basePath . '/projects/' . $project->id . '/stages');

    function j(url, opts = {}) {
        return fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                ...(opts.body && !(opts.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {})
            },
            ...opts,
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw { status: r.status, data };
            return data;
        });
    }

    window.stageModalOpen = function (stage) {
        const isEdit = !!stage;
        document.getElementById('stageModalTitle').textContent = isEdit ? 'Editar etapa' : 'Nova etapa';
        document.getElementById('_stage_id').value = stage?.id || '';
        document.getElementById('_stage_title').value = stage?.title || '';
        document.getElementById('_stage_description').value = stage?.description || '';
        document.getElementById('_stage_start_date').value = stage?.start_date || '';
        document.getElementById('_stage_end_date').value = stage?.end_date || '';
        document.getElementById('_stage_planned_value').value = stage?.planned_value ?? 0;
        document.getElementById('_stage_status').value = stage?.status || 'pending';
        document.getElementById('_stage_form_errors').textContent = '';
    };

    window.submitStageForm = function (ev) {
        ev.preventDefault();
        const form = ev.target;
        const id = document.getElementById('_stage_id').value;
        const body = JSON.stringify({
            title: form.title.value.trim(),
            description: form.description.value.trim() || null,
            start_date: form.start_date.value || null,
            end_date: form.end_date.value || null,
            planned_value: parseFloat(form.planned_value.value || 0),
            status: form.status.value,
        });
        const url = id ? `${baseUrl}/${id}` : baseUrl;
        const method = id ? 'PUT' : 'POST';
        j(url, { method, body })
            .then(() => window.location.reload())
            .catch(err => {
                const errs = err.data?.errors ? Object.values(err.data.errors).flat().join(' · ') : (err.data?.message || 'Erro ao salvar');
                document.getElementById('_stage_form_errors').textContent = errs;
            });
        return false;
    };

    window.deleteStage = function (id) {
        if (!confirm('Remover esta etapa?')) return;
        j(`${baseUrl}/${id}`, { method: 'DELETE' })
            .then(() => window.location.reload())
            .catch(() => alert('Erro ao remover etapa'));
    };

    window.completeStage = function (id, planned) {
        const suffix = planned > 0 ? '\n\nUma Transaction sugerida sera criada e aguardara aprovacao no fluxo financeiro.' : '';
        if (!confirm('Marcar esta etapa como concluida?' + suffix)) return;
        j(`${baseUrl}/${id}/complete`, { method: 'POST' })
            .then(res => {
                if (res.suggested_transaction_id) {
                    alert('Etapa concluida. Transaction sugerida #' + res.suggested_transaction_id + ' aguardando aprovacao.');
                }
                window.location.reload();
            })
            .catch(() => alert('Erro ao concluir etapa'));
    };

    // Drag-drop de ordem
    const grid = document.getElementById('stages-grid');
    if (grid && grid.children.length > 1) {
        new Sortable(grid, {
            animation: 150,
            handle: '.stage-card',
            filter: '.stage-btn, button, a, input, textarea, select',
            preventOnFilter: false,
            ghostClass: 'dragging',
            onEnd: () => {
                const stages = Array.from(grid.querySelectorAll('.stage-card')).map((el, idx) => ({
                    id: parseInt(el.dataset.stageId, 10),
                    order: idx + 1,
                }));
                j(`${baseUrl}/reorder`, { method: 'POST', body: JSON.stringify({ stages }) })
                    .catch(() => alert('Erro ao reordenar. Recarregue a pagina.'));
            }
        });
    }
})();
</script>
@endsection
