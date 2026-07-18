@extends('layouts.app')

@section('content')
@php
    $basePath  = rtrim(request()->getBaseUrl(), '/');
    $canManage = in_array(auth()->user()->role, ['manager', 'super_admin', 'ngo'], true);

    $statusLabels = [
        'pending'     => ['Pendente',    '#64748b', '#f1f5f9'],
        'in_progress' => ['Em andamento','#2563eb', '#dbeafe'],
        'completed'   => ['Concluida',   '#059669', '#d1fae5'],
        'cancelled'   => ['Cancelada',   '#dc2626', '#fee2e2'],
    ];
    $stTuple = $statusLabels[$stage->status] ?? $statusLabels['pending'];
    [$stLabel, $stColor, $stBg] = $stTuple;

    $taskStatusLabels = [
        'todo'        => ['A fazer',    '#64748b', '#f1f5f9'],
        'doing'       => ['Fazendo',    '#2563eb', '#dbeafe'],
        'pending'     => ['Pendente',   '#64748b', '#f1f5f9'],
        'in_progress' => ['Em andamento','#2563eb','#dbeafe'],
        'done'        => ['Concluida',  '#059669', '#d1fae5'],
        'completed'   => ['Concluida',  '#059669', '#d1fae5'],
        'blocked'     => ['Bloqueada',  '#dc2626', '#fee2e2'],
        'cancelled'   => ['Cancelada',  '#dc2626', '#fee2e2'],
    ];

    $income  = (float) $financial['income'];
    $expense = (float) $financial['expense'];
    $planned = (float) $financial['planned'];
    $spentPct = $planned > 0 ? min(100, (int) round(($expense / $planned) * 100)) : 0;
@endphp

<style>
    .st-hero { background: linear-gradient(135deg,#0f172a 0%,#1e293b 100%); color: white; border-radius: 24px; padding: 28px 32px; margin-bottom: 20px; }
    .st-crumbs { font-size:.72rem; text-transform:uppercase; letter-spacing:1.5px; opacity:.55; margin-bottom:6px; }
    .st-crumbs a { color: rgba(255,255,255,.75); text-decoration: none; }
    .st-crumbs a:hover { color: white; }
    .st-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.72rem; font-weight:800; letter-spacing:.3px; }
    .st-kpi { background:white; border:1px solid #e2e8f0; border-radius:16px; padding:16px 18px; }
    .st-kpi-label { font-size:.66rem; font-weight:800; color:#94a3b8; letter-spacing:1.2px; text-transform:uppercase; }
    .st-kpi-val { font-weight:900; color:#0f172a; font-size:1.35rem; margin-top:4px; }
    .st-progress { height:6px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin-top:10px; }
    .st-progress-bar { height:100%; background:linear-gradient(90deg,#2563eb,#059669); transition:width .3s; }
    .st-panel { background:white; border:1px solid #e2e8f0; border-radius:20px; padding:20px 22px; margin-bottom:20px; }
    .st-panel-h { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
    .st-panel-h h4 { margin:0; font-weight:900; color:#0f172a; letter-spacing:-.5px; font-size:1.1rem; }
    .st-list-row { display:grid; grid-template-columns: 1fr auto; gap:14px; align-items:center; padding:12px 0; border-bottom:1px dashed #e2e8f0; }
    .st-list-row:last-child { border-bottom:none; }
    .st-empty { padding:32px 20px; text-align:center; color:#64748b; background:#f8fafc; border-radius:14px; border:2px dashed #cbd5e1; }
    .st-btn-primary { background:#0f172a; color:white; border:none; padding:8px 14px; border-radius:10px; font-weight:800; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .st-btn-primary:hover { background:#1e293b; color:white; }
    .st-btn-outline { background:white; color:#0f172a; border:1px solid #cbd5e1; padding:8px 14px; border-radius:10px; font-weight:800; font-size:.82rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .st-btn-outline:hover { background:#f8fafc; }
</style>

<div class="st-hero">
    <div class="st-crumbs">
        <a href="{{ $basePath . '/projects/details/' . $project->id }}">{{ $project->name }}</a>
        &nbsp;›&nbsp;
        <a href="{{ $basePath . '/projects/' . $project->id . '/stages' }}">Etapas</a>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap;">
        <div>
            <h1 style="font-weight:950; font-size:2rem; letter-spacing:-1px; margin:0;">
                <span style="opacity:.5; font-weight:800; margin-right:6px;">#{{ (int) $stage->order }}</span>
                {{ $stage->title }}
            </h1>
            <div style="margin-top:8px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <span class="st-badge" style="color:{{ $stColor }}; background:{{ $stBg }};">{{ $stLabel }}</span>
                @if($stage->start_date && $stage->end_date)
                    <span style="opacity:.75; font-size:.85rem;"><i class="far fa-calendar me-1"></i>{{ $stage->start_date->format('d/m/Y') }} → {{ $stage->end_date->format('d/m/Y') }}</span>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ $basePath . '/projects/' . $project->id . '/stages' }}" class="st-btn-outline" style="background:rgba(255,255,255,.08); color:white; border:1px solid rgba(255,255,255,.2);">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <a href="{{ $basePath . '/projects/' . $project->id . '/kanban?stage_id=' . $stage->id }}" class="st-btn-outline" style="background:rgba(255,255,255,.08); color:white; border:1px solid rgba(255,255,255,.2);" title="Ver tarefas desta etapa em formato Kanban">
                <i class="fas fa-columns"></i> Kanban desta etapa
            </a>
            @if($canManage && $stage->status !== 'completed')
                <button type="button" class="st-btn-outline" style="background:#22c55e; color:white; border:none;" onclick="completeStage()">
                    <i class="fas fa-check"></i> Concluir etapa
                </button>
            @endif
        </div>
    </div>
</div>

{{-- KPIs financeiros e prazo --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="st-kpi">
            <div class="st-kpi-label">Valor previsto</div>
            <div class="st-kpi-val">R$ {{ number_format($planned, 2, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="st-kpi">
            <div class="st-kpi-label">Recebido (receitas)</div>
            <div class="st-kpi-val" style="color:#059669;">R$ {{ number_format($income, 2, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="st-kpi">
            <div class="st-kpi-label">Gasto (despesas)</div>
            <div class="st-kpi-val" style="color:#dc2626;">R$ {{ number_format($expense, 2, ',', '.') }}</div>
            @if($planned > 0)
                <div class="st-progress"><div class="st-progress-bar" style="width: {{ $spentPct }}%; background: {{ $spentPct >= 100 ? '#dc2626' : ($spentPct >= 80 ? '#f59e0b' : '#059669') }};"></div></div>
                <div style="font-size:.72rem; color:#64748b; margin-top:4px;">{{ $spentPct }}% do previsto</div>
            @endif
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="st-kpi">
            <div class="st-kpi-label">Saldo da etapa</div>
            <div class="st-kpi-val" style="color: {{ $financial['balance'] >= 0 ? '#059669' : '#dc2626' }};">R$ {{ number_format($financial['balance'], 2, ',', '.') }}</div>
            <div style="font-size:.72rem; color:#64748b; margin-top:4px;">receitas − despesas</div>
        </div>
    </div>
</div>

{{-- Transaction sugerida aguardando aprovacao --}}
@if($canManage && !empty($pendingSuggestion))
<div class="st-panel" style="background:linear-gradient(90deg,#fff7ed 0%,#fef3c7 100%); border:1px solid #f59e0b;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <div>
            <div style="font-size:.72rem; color:#b45309; font-weight:800; letter-spacing:1.2px; text-transform:uppercase;">
                <i class="fas fa-hourglass-half me-1"></i> Recebimento sugerido aguardando aprovacao
            </div>
            <div style="font-weight:900; color:#78350f; font-size:1.05rem; margin-top:4px;">
                {{ $pendingSuggestion->description }} · R$ {{ number_format((float) $pendingSuggestion->amount, 2, ',', '.') }}
            </div>
            <div style="font-size:.8rem; color:#92400e; margin-top:2px;">
                Criado ao concluir a etapa. Aprove para lancar como receita do projeto.
            </div>
        </div>
        <form action="{{ $basePath . '/transactions/' . $pendingSuggestion->id . '/approve' }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="st-btn-primary" style="background:#059669;" onclick="return confirm('Aprovar este recebimento de R$ {{ number_format((float) $pendingSuggestion->amount, 2, ',', '.') }}?')">
                <i class="fas fa-check-circle"></i> Aprovar recebimento
            </button>
        </form>
    </div>
</div>
@endif

{{-- Descricao --}}
@if($stage->description)
<div class="st-panel">
    <div style="color:#475569; line-height:1.6;">{{ $stage->description }}</div>
</div>
@endif

{{-- Tarefas --}}
<div class="st-panel">
    <div class="st-panel-h">
        <div>
            <h4><i class="fas fa-tasks me-2" style="color:#2563eb;"></i> Tarefas da etapa <span style="color:#94a3b8; font-weight:800;">({{ $tasks->count() }})</span></h4>
            <div style="color:#64748b; font-size:.85rem;">Tarefas com <code>stage_id</code> vinculado a esta etapa.</div>
        </div>
        @if($canManage)
            <button type="button" class="st-btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal">
                <i class="fas fa-plus"></i> Nova tarefa da etapa
            </button>
        @endif
    </div>

    @forelse($tasks as $t)
        @php
            $tTuple = $taskStatusLabels[$t->status] ?? $taskStatusLabels['todo'];
        @endphp
        <div class="st-list-row">
            <div>
                <div style="font-weight:800; color:#0f172a;">{{ $t->title }}</div>
                <div style="font-size:.78rem; color:#64748b; margin-top:2px;">
                    <span class="st-badge" style="color:{{ $tTuple[1] }}; background:{{ $tTuple[2] }};">{{ $tTuple[0] }}</span>
                    @if($t->assignee) · <i class="far fa-user"></i> {{ $t->assignee->name }} @endif
                    @if($t->due_date) · <i class="far fa-calendar"></i> {{ \Illuminate\Support\Carbon::parse($t->due_date)->format('d/m/Y') }} @endif
                    · <span style="text-transform:capitalize;">prioridade: {{ $t->priority ?? 'medium' }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="st-empty">
            <i class="fas fa-tasks" style="font-size:1.8rem; color:#cbd5e1; margin-bottom:8px;"></i>
            <div style="font-weight:800; color:#334155;">Nenhuma tarefa nesta etapa ainda</div>
            <div style="font-size:.85rem;">Crie a primeira tarefa para acompanhar a execucao da etapa.</div>
        </div>
    @endforelse
</div>

{{-- Financeiro --}}
<div class="st-panel">
    <div class="st-panel-h">
        <div>
            <h4><i class="fas fa-coins me-2" style="color:#059669;"></i> Financeiro da etapa <span style="color:#94a3b8; font-weight:800;">({{ $transactions->count() }})</span></h4>
            <div style="color:#64748b; font-size:.85rem;">Lancamentos com <code>stage_id</code> vinculado; feed com receitas e despesas.</div>
        </div>
        @if($canManage)
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <button type="button" class="st-btn-primary" style="background:#059669;" onclick="openTxModal('income')">
                    <i class="fas fa-arrow-down"></i> Nova receita
                </button>
                <button type="button" class="st-btn-primary" style="background:#dc2626;" onclick="openTxModal('expense')">
                    <i class="fas fa-arrow-up"></i> Nova despesa
                </button>
            </div>
        @endif
    </div>

    @forelse($transactions as $tx)
        <div class="st-list-row">
            <div>
                <div style="font-weight:800; color:#0f172a;">
                    @if($tx->type === 'income')
                        <i class="fas fa-arrow-down" style="color:#059669;"></i>
                    @else
                        <i class="fas fa-arrow-up" style="color:#dc2626;"></i>
                    @endif
                    {{ $tx->description }}
                </div>
                <div style="font-size:.78rem; color:#64748b; margin-top:2px;">
                    @if($tx->category) {{ $tx->category->name }} · @endif
                    {{ \Illuminate\Support\Carbon::parse($tx->date)->format('d/m/Y') }}
                    @if($tx->approval_status === 'pending')
                        · <span class="st-badge" style="color:#b45309; background:#fef3c7;">Aguardando aprovacao</span>
                    @elseif($tx->status === 'paid')
                        · <span class="st-badge" style="color:#059669; background:#d1fae5;">Pago</span>
                    @endif
                </div>
            </div>
            <div style="font-weight:900; color: {{ $tx->type === 'income' ? '#059669' : '#dc2626' }};">
                R$ {{ number_format((float) $tx->amount, 2, ',', '.') }}
            </div>
        </div>
    @empty
        <div class="st-empty">
            <i class="fas fa-receipt" style="font-size:1.8rem; color:#cbd5e1; margin-bottom:8px;"></i>
            <div style="font-weight:800; color:#334155;">Nenhum lancamento nesta etapa ainda</div>
            <div style="font-size:.85rem;">Registre receitas ou despesas para acompanhar o orcamento executado.</div>
        </div>
    @endforelse
</div>

{{-- ── Modais ─────────────────────────────────────────────────────────── --}}
@if($canManage)
{{-- Nova tarefa da etapa --}}
<div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <form action="{{ $basePath . '/tasks' }}" method="POST">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="stage_id"   value="{{ $stage->id }}">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:18px 22px;">
                    <h5 class="modal-title" style="font-weight:900;">Nova tarefa da etapa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:22px;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titulo *</label>
                        <input type="text" name="title" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descricao</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-control">
                                <option value="todo">A fazer</option>
                                <option value="doing">Fazendo</option>
                                <option value="done">Concluida</option>
                                <option value="blocked">Bloqueada</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prioridade</label>
                            <select name="priority" class="form-control">
                                <option value="medium">Media</option>
                                <option value="low">Baixa</option>
                                <option value="high">Alta</option>
                                <option value="critical">Critica</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Responsavel</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">— Nao atribuido —</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prazo</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 22px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark" style="border-radius:10px; font-weight:900;">Criar tarefa</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Nova transacao da etapa --}}
<div class="modal fade" id="txModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px; border:none;">
            <form action="{{ $basePath . '/transactions' }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="stage_id"   value="{{ $stage->id }}">
                <input type="hidden" name="type"       id="tx_type" value="expense">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9; padding:18px 22px;">
                    <h5 class="modal-title" style="font-weight:900;" id="txModalTitle">Novo lancamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:22px;">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descricao *</label>
                        <input type="text" name="description" class="form-control" maxlength="255" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Valor (R$) *</label>
                            <input type="text" name="amount" class="form-control" placeholder="0,00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Data *</label>
                            <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Categoria</label>
                            <select name="category_id" class="form-control">
                                <option value="">— Sem categoria —</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Comprovante (PDF/JPG/PNG opcional)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9; padding:14px 22px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark" style="border-radius:10px; font-weight:900;">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    function openTxModal(type) {
        const modalEl = document.getElementById('txModal');
        document.getElementById('tx_type').value = type;
        document.getElementById('txModalTitle').textContent = type === 'income' ? 'Nova receita da etapa' : 'Nova despesa da etapa';
        new bootstrap.Modal(modalEl).show();
    }
    function completeStage() {
        if (!confirm('Marcar esta etapa como concluida?\nSe houver valor previsto, uma Transaction sugerida sera criada aguardando aprovacao no fluxo financeiro.')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(@json($basePath . '/projects/' . $project->id . '/stages/' . $stage->id . '/complete'), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.ok ? r.json() : Promise.reject())
          .then(res => {
              if (res.suggested_transaction_id) alert('Etapa concluida. Transaction sugerida #' + res.suggested_transaction_id + ' aguardando aprovacao.');
              window.location.reload();
          })
          .catch(() => alert('Erro ao concluir etapa'));
    }
</script>
@endsection
