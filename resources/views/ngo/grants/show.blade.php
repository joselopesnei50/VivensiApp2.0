@extends('layouts.app')

@section('content')
@php
    $days = $grant->deadline ? now()->diffInDays($grant->deadline, false) : null;
    $statusLabels = [
        'open' => ['label' => 'Ativo', 'class' => 'bg-success-subtle text-success'],
        'reporting' => ['label' => 'Prestação de Contas', 'class' => 'bg-warning-subtle text-warning'],
        'closed' => ['label' => 'Encerrado', 'class' => 'bg-light text-muted'],
    ];
    $curr = $statusLabels[$grant->status ?? 'open'] ?? $statusLabels['open'];
    $isExpired = $grant->deadline && $grant->deadline->isPast() && ($grant->status ?? 'open') !== 'closed';
@endphp

<div class="vivensi-card" style="max-width: 900px; margin: 0 auto; padding: 40px;">
    <div style="display:flex; justify-content: space-between; align-items:flex-start; gap: 20px; margin-bottom: 22px;">
        <div>
            <h3 style="margin: 0; font-size: 1.6rem; color: #1e293b; font-weight: 900;">{{ $grant->title }}</h3>
            <div class="text-muted small" style="margin-top: 6px;">ID: #{{ $grant->id }}</div>
        </div>
        <div style="display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
            <button type="button" id="btnGenerateAiProposal" class="btn btn-premium" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; border: none; border-radius: 12px; font-weight: 800; padding: 10px 20px; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);">
                <i class="fas fa-sparkles me-2"></i> Copilot Pro IA
            </button>
            <button type="button" data-bs-toggle="modal" data-bs-target="#editGrantModal" class="btn btn-outline-primary" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-pen me-1"></i> Editar
            </button>
            <a href="{{ url('/ngo/grants') }}" class="btn btn-outline-secondary" style="border-radius: 12px;">Voltar</a>
            <form action="{{ route('ngo.grants.destroy', $grant->id) }}" method="POST" onsubmit="return confirm('Excluir este convênio/edital? Esta ação não pode ser desfeita.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" style="border-radius: 12px;">
                    <i class="fas fa-trash-alt me-1"></i> Excluir
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif
    @if($isExpired)
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <strong><i class="fas fa-triangle-exclamation me-2"></i>Prazo vencido.</strong>
            Este convênio/edital está vencido e ainda não está marcado como <strong>Encerrado</strong>. Revise o status e a prestação de contas.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Concedente</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->agency ?: '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Valor global</div>
                <div style="margin-top: 6px; font-weight: 900; font-size: 1.4rem; color:#4f46e5;">
                    R$ {{ number_format($grant->value, 2, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Processo / Contrato</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->contract_number ?: '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Início da vigência</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->start_date ? $grant->start_date->format('d/m/Y') : '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Prazo / Deadline</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">
                    {{ $grant->deadline ? $grant->deadline->format('d/m/Y') : '-' }}
                </div>
                @if($days !== null)
                    <div class="small" style="margin-top: 6px; color: {{ $days < 0 ? '#b91c1c' : ($days < 30 ? '#b91c1c' : '#64748b') }};">
                        <i class="far fa-clock me-1"></i>
                        @if($grant->deadline && $days < 0)
                            Vencido há {{ abs((int) $days) }} dias
                        @else
                            {{ (int) $days }} dias
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Status</div>
                <div style="margin-top: 10px;">
                    <span class="badge rounded-pill px-3 py-2 {{ $curr['class'] }}" style="font-weight:800;">{{ $curr['label'] }}</span>
                </div>
                <form action="{{ route('ngo.grants.status', $grant->id) }}" method="POST" style="margin-top: 12px; display:flex; gap:10px; align-items:center;">
                    @csrf
                    <select name="status" class="form-select form-select-sm" style="max-width: 220px; border-radius: 12px;">
                        <option value="open" {{ ($grant->status ?? 'open') === 'open' ? 'selected' : '' }}>Ativo</option>
                        <option value="reporting" {{ ($grant->status ?? 'open') === 'reporting' ? 'selected' : '' }}>Prestação de Contas</option>
                        <option value="closed" {{ ($grant->status ?? 'open') === 'closed' ? 'selected' : '' }}>Encerrado</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 12px; background:#4f46e5; border:none;">
                        Atualizar
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Observações / Requisitos</div>
                <div style="margin-top: 10px; color:#0f172a; white-space: pre-wrap;">{{ $grant->notes ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div id="documents" style="display:flex; justify-content: space-between; align-items:flex-end; gap: 16px; margin-bottom: 12px;">
            <div>
                <h4 style="margin:0; font-weight: 900; color:#0f172a;">Documentos</h4>
                <div class="text-muted small">Anexe o edital, plano de trabalho, comprovantes e anexos para centralizar a prestação de contas.</div>
            </div>
        </div>

        <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
            <form action="{{ route('ngo.grants.documents.upload', $grant->id) }}" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1.3fr .8fr 1.2fr; gap: 12px; align-items:end;">
                @csrf
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Título</label>
                    <input type="text" name="title" class="form-control" placeholder="Ex: Edital completo" required style="border-radius: 12px;">
                </div>
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Tipo</label>
                    <select name="type" class="form-select" style="border-radius: 12px;">
                        <option value="edital">Edital</option>
                        <option value="plano_trabalho">Plano de trabalho</option>
                        <option value="comprovante">Comprovante</option>
                        <option value="anexo">Anexo</option>
                        <option value="outros" selected>Outros</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Arquivo</label>
                    <input type="file" name="file" class="form-control" required style="border-radius: 12px;">
                </div>
                <div style="grid-column: 1 / -1; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 14px; background:#4f46e5; border:none; font-weight:800;">
                        <i class="fas fa-paperclip me-1"></i> Anexar
                    </button>
                </div>
            </form>

            <div style="margin-top: 18px;">
                @if(($grant->documents?->count() ?? 0) === 0)
                    <div class="text-muted small" style="padding: 14px; background:#f8fafc; border-radius: 14px;">
                        Nenhum documento anexado ainda.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="small text-muted" style="text-transform: uppercase; letter-spacing: .06em;">
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Arquivo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grant->documents as $doc)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $doc->title }}</td>
                                        <td><span class="badge bg-light text-dark border rounded-pill">{{ $doc->type }}</span></td>
                                        <td class="text-muted small">{{ $doc->original_name ?: basename($doc->file_path) }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-secondary" style="border-radius: 12px;"
                                               href="{{ route('ngo.grants.documents.download', ['id' => $grant->id, 'docId' => $doc->id]) }}">
                                                <i class="fas fa-download me-1"></i> Baixar
                                            </a>
                                            <form action="{{ route('ngo.grants.documents.delete', ['id' => $grant->id, 'docId' => $doc->id]) }}"
                                                  method="POST" style="display:inline;" onsubmit="return confirm('Remover este documento?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 12px;">
                                                    <i class="fas fa-trash-alt me-1"></i> Remover
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    {{-- =====================================================
         SEÇÃO: RESUMO FINANCEIRO DO EDITAL
    ====================================================== --}}
    <div class="mt-4">
        <div style="display:flex; justify-content: space-between; align-items:flex-end; gap: 16px; margin-bottom: 12px;">
            <div>
                <h4 style="margin:0; font-weight: 900; color:#0f172a;">Movimentações Financeiras</h4>
                <div class="text-muted small">Entradas e saídas vinculadas ao projeto deste edital.</div>
            </div>
            @if($project)
            <a href="{{ url('/transactions/create') }}?project_id={{ $project->id }}"
               class="btn btn-sm btn-outline-primary" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-plus me-1"></i> Nova Movimentação
            </a>
            @endif
        </div>

        {{-- KPIs financeiros --}}
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="p-3 text-center" style="border:1px solid #dcfce7; border-radius:16px; background:#f0fdf4;">
                    <div class="text-muted small" style="font-weight:800; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem;">Orçamento</div>
                    <div style="font-size:1.3rem; font-weight:900; color:#1e293b; margin-top:4px;">
                        R$ {{ number_format($budget, 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="border:1px solid #dcfce7; border-radius:16px; background:#f0fdf4;">
                    <div class="text-muted small" style="font-weight:800; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem;">Total Captado</div>
                    <div style="font-size:1.3rem; font-weight:900; color:#16a34a; margin-top:4px;">
                        R$ {{ number_format($totalIncome, 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="border:1px solid #fee2e2; border-radius:16px; background:#fef2f2;">
                    <div class="text-muted small" style="font-weight:800; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem;">Total Gasto</div>
                    <div style="font-size:1.3rem; font-weight:900; color:#dc2626; margin-top:4px;">
                        R$ {{ number_format($totalExpense, 2, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="border:1px solid {{ $balance >= 0 ? '#e0e7ff' : '#fee2e2' }}; border-radius:16px; background:{{ $balance >= 0 ? '#eef2ff' : '#fef2f2' }};">
                    <div class="text-muted small" style="font-weight:800; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem;">Saldo Disponível</div>
                    <div style="font-size:1.3rem; font-weight:900; color:{{ $balance >= 0 ? '#4f46e5' : '#dc2626' }}; margin-top:4px;">
                        R$ {{ number_format(abs($balance), 2, ',', '.') }}
                        @if($balance < 0)<span style="font-size:.75rem;"> (neg.)</span>@endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Barra de progresso do orçamento --}}
        @if($budget > 0)
        <div class="p-3 mb-3" style="border:1px solid #f1f5f9; border-radius:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                <div class="small" style="font-weight:800; color:#64748b;">Execução do Orçamento</div>
                <div class="small" style="font-weight:900; color:{{ $usedPercent >= 90 ? '#dc2626' : ($usedPercent >= 70 ? '#d97706' : '#16a34a') }};">
                    {{ $usedPercent }}% utilizado
                </div>
            </div>
            <div style="background:#f1f5f9; border-radius:99px; height:10px; overflow:hidden;">
                <div style="width:{{ $usedPercent }}%; background:{{ $usedPercent >= 90 ? '#dc2626' : ($usedPercent >= 70 ? '#f59e0b' : '#4f46e5') }}; height:100%; border-radius:99px; transition:width .5s;"></div>
            </div>
        </div>
        @endif

        {{-- Tabela de movimentações --}}
        <div class="p-4" style="border:1px solid #f1f5f9; border-radius:18px; background:#fff;">
            @if($transactions->isEmpty())
                <div class="text-muted small text-center" style="padding:20px; background:#f8fafc; border-radius:14px;">
                    <i class="fas fa-receipt me-2"></i> Nenhuma movimentação registrada para este edital ainda.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="small text-muted" style="text-transform:uppercase; letter-spacing:.06em;">
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th class="text-end">Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions->take(30) as $tx)
                            <tr>
                                <td class="small text-muted">{{ $tx->date?->format('d/m/Y') }}</td>
                                <td class="fw-bold text-dark" style="font-size:.9rem;">{{ $tx->description }}</td>
                                <td>
                                    @if($tx->type === 'income')
                                        <span class="badge" style="background:#dcfce7; color:#16a34a; font-weight:800; border-radius:8px; padding:4px 10px;">
                                            <i class="fas fa-arrow-up me-1"></i>Entrada
                                        </span>
                                    @else
                                        <span class="badge" style="background:#fee2e2; color:#dc2626; font-weight:800; border-radius:8px; padding:4px 10px;">
                                            <i class="fas fa-arrow-down me-1"></i>Saída
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold" style="color:{{ $tx->type === 'income' ? '#16a34a' : '#dc2626' }};">
                                    {{ $tx->type === 'income' ? '+' : '-' }} R$ {{ number_format($tx->amount, 2, ',', '.') }}
                                </td>
                                <td>
                                    @php
                                        $sMap = ['paid'=>['Pago','bg-success-subtle text-success'], 'pending'=>['Pendente','bg-warning-subtle text-warning'], 'canceled'=>['Cancelado','bg-light text-muted']];
                                        $sInfo = $sMap[$tx->status] ?? ['—','bg-light text-muted'];
                                    @endphp
                                    <span class="badge {{ $sInfo[1] }}" style="border-radius:8px; font-weight:800;">{{ $sInfo[0] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($transactions->count() > 30)
                        <div class="text-muted small text-center mt-2">Mostrando 30 de {{ $transactions->count() }} movimentações. <a href="{{ url('/transactions') }}">Ver todas</a></div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- =====================================================
         SEÇÃO: TAREFAS DOS FUNCIONÁRIOS
    ====================================================== --}}
    <div class="mt-4">
        <div style="display:flex; justify-content: space-between; align-items:flex-end; gap: 16px; margin-bottom: 12px;">
            <div>
                <h4 style="margin:0; font-weight: 900; color:#0f172a;">Tarefas da Equipe</h4>
                <div class="text-muted small">Atividades dos funcionários vinculadas ao projeto deste edital.</div>
            </div>
            @if($project)
            <a href="{{ url('/tasks/create') }}?project_id={{ $project->id }}"
               class="btn btn-sm btn-outline-primary" style="border-radius: 12px; font-weight: 700;">
                <i class="fas fa-plus me-1"></i> Nova Tarefa
            </a>
            @endif
        </div>

        <div class="p-4" style="border:1px solid #f1f5f9; border-radius:18px; background:#fff;">
            @if($tasks->isEmpty())
                <div class="text-muted small text-center" style="padding:20px; background:#f8fafc; border-radius:14px;">
                    <i class="fas fa-tasks me-2"></i> Nenhuma tarefa registrada para este edital ainda.
                </div>
            @else
                @php
                    $taskDone = $tasks->whereIn('status', ['done', 'completed'])->count();
                    $taskTotal = $tasks->count();
                    $taskPercent = $taskTotal > 0 ? round(($taskDone / $taskTotal) * 100) : 0;
                @endphp
                {{-- Mini progresso de tarefas --}}
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <div class="small" style="font-weight:700; color:#64748b;">{{ $taskDone }}/{{ $taskTotal }} tarefas concluídas</div>
                    <div class="small" style="font-weight:900; color:#4f46e5;">{{ $taskPercent }}%</div>
                </div>
                <div style="background:#f1f5f9; border-radius:99px; height:6px; overflow:hidden; margin-bottom:18px;">
                    <div style="width:{{ $taskPercent }}%; background:#4f46e5; height:100%; border-radius:99px;"></div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="small text-muted" style="text-transform:uppercase; letter-spacing:.06em;">
                                <th>Tarefa</th>
                                <th>Responsável</th>
                                <th>Prazo</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks->sortBy('due_date') as $task)
                            @php
                                $tStatusMap = [
                                    'todo'        => ['A Fazer',   'bg-light text-muted'],
                                    'doing'       => ['Em Andamento','bg-primary-subtle text-primary'],
                                    'in_progress' => ['Em Andamento','bg-primary-subtle text-primary'],
                                    'done'        => ['Concluída', 'bg-success-subtle text-success'],
                                    'completed'   => ['Concluída', 'bg-success-subtle text-success'],
                                    'pending'     => ['Pendente',  'bg-warning-subtle text-warning'],
                                    'blocked'     => ['Bloqueada', 'bg-danger-subtle text-danger'],
                                ];
                                $tPrioMap = [
                                    'low'      => ['Baixa',   'bg-light text-muted'],
                                    'medium'   => ['Média',   'bg-info-subtle text-info'],
                                    'high'     => ['Alta',    'bg-warning-subtle text-warning'],
                                    'critical' => ['Crítica', 'bg-danger-subtle text-danger'],
                                ];
                                $tStatusInfo = $tStatusMap[$task->status] ?? ['—','bg-light text-muted'];
                                $tPrioInfo   = $tPrioMap[$task->priority] ?? ['—','bg-light text-muted'];
                                $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && !in_array($task->status, ['done','completed']);
                            @endphp
                            <tr>
                                <td class="fw-bold text-dark" style="font-size:.9rem;">
                                    {{ $task->title }}
                                    @if($task->description)
                                        <div class="text-muted small" style="font-weight:400;">{{ \Illuminate\Support\Str::limit($task->description, 60) }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($task->assigned_to)
                                        @php $assignee = \App\Models\User::find($task->assigned_to); @endphp
                                        <span>{{ $assignee?->name ?? 'Usuário #'.$task->assigned_to }}</span>
                                    @else
                                        <span class="text-muted">Não atribuída</span>
                                    @endif
                                </td>
                                <td class="small" style="color:{{ $isOverdue ? '#dc2626' : '#64748b' }}; font-weight:{{ $isOverdue ? '800' : '500' }};">
                                    @if($task->due_date)
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}
                                        @if($isOverdue)<span class="ms-1"><i class="fas fa-triangle-exclamation"></i></span>@endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $tPrioInfo[1] }}" style="border-radius:8px; font-weight:800; font-size:.75rem;">{{ $tPrioInfo[0] }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $tStatusInfo[1] }}" style="border-radius:8px; font-weight:800; font-size:.75rem;">{{ $tStatusInfo[0] }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
</div>

{{-- MODAL EDITAR CONVÊNIO --}}
<div class="modal fade" id="editGrantModal" role="dialog" aria-modal="true" aria-labelledby="editGrantModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="padding: 24px 28px; border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title fw-bold" id="editGrantModalLabel" style="color: #0f172a;"><i class="fas fa-pen me-2 text-primary"></i>Editar Convênio / Edital</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 28px;">
                <form action="{{ route('ngo.grants.update', $grant->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Título / Objeto</label>
                        <input type="text" name="title" class="form-control" value="{{ $grant->title }}" required style="border-radius: 10px;">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-muted">Concedente (Órgão/Empresa)</label>
                            <input type="text" name="grantor_name" class="form-control" value="{{ $grant->agency }}" required style="border-radius: 10px;">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted">Número do Processo/Contrato</label>
                            <input type="text" name="contract_number" class="form-control" value="{{ $grant->contract_number }}" style="border-radius: 10px;" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Valor Global (R$)</label>
                        <input type="text" name="total_amount" id="edit_total_amount" class="form-control"
                               value="{{ number_format($grant->value, 2, ',', '.') }}" required style="border-radius: 10px; font-weight: 700; color: #4f46e5;">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Início da Vigência</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $grant->start_date?->format('Y-m-d') }}" style="border-radius: 10px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Fim da Vigência</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $grant->deadline?->format('Y-m-d') }}" required style="border-radius: 10px;">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Observações / Requisitos</label>
                        <textarea name="notes" class="form-control" rows="4" style="border-radius: 10px;" placeholder="Cole aqui requisitos, objeto, itens de prestação de contas, etc.">{{ $grant->notes }}</textarea>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold" style="border-radius: 10px; background: #4f46e5; border: none; padding: 10px 28px;">
                            <i class="fas fa-save me-2"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL COPILOT IA --}}
<div class="modal fade" id="aiProposalModal" role="dialog" aria-modal="true" aria-labelledby="aiProposalModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 28px; border: none; background: #0f172a; color: white; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 30px 40px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(99, 102, 241, 0.15); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(99, 102, 241, 0.3);">
                        <i class="fas fa-magic" style="color: #6366f1; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" style="font-weight: 950; letter-spacing: -0.5px;">Bruce Copilot Pro</h5>
                        <p style="margin:0; font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Rascunho de Proposta Estruturada</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 40px; max-height: 60vh; overflow-y: auto;">
                <div id="aiProposalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-4" style="color: rgba(255,255,255,0.6); font-weight: 600;">O Bruce está analisando o edital e redigindo sua proposta...</p>
                </div>
                @if($grant->ai_proposal)
                <div id="aiProposalSaved" style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <div style="font-size: 0.82rem; color: #a5b4fc;"><i class="fas fa-history me-2"></i>Proposta salva disponível. Clique em <strong>Carregar Salva</strong> ou gere uma nova.</div>
                    <button type="button" id="btnLoadSavedProposal" class="btn btn-sm" style="background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); border-radius: 8px; font-weight: 700; white-space: nowrap;">Carregar Salva</button>
                </div>
                @endif
                <div id="aiProposalContent" style="display: none; line-height: 1.8; color: #e2e8f0; font-size: 0.95rem;"></div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 25px 40px;">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal" style="border-radius: 12px; font-weight: 800;">Fechar</button>
                <button type="button" id="btnCopyAiProposal" class="btn btn-primary" style="background: #4f46e5; border: none; border-radius: 12px; font-weight: 800; padding: 12px 25px;">
                    <i class="fas fa-copy me-2"></i> Copiar Proposta
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnGenerate = document.getElementById('btnGenerateAiProposal');
        const modal = new bootstrap.Modal(document.getElementById('aiProposalModal'));
        const loading = document.getElementById('aiProposalLoading');
        const content = document.getElementById('aiProposalContent');
        const btnCopy = document.getElementById('btnCopyAiProposal');
        let currentProposal = '';

        @if($grant->ai_proposal)
        const savedProposal = @json($grant->ai_proposal);
        const btnLoadSaved = document.getElementById('btnLoadSavedProposal');
        if (btnLoadSaved) {
            btnLoadSaved.addEventListener('click', function() {
                currentProposal = savedProposal;
                content.innerHTML = marked.parse(savedProposal);
                loading.style.display = 'none';
                content.style.display = 'block';
                btnCopy.style.display = 'block';
            });
        }
        @endif

        btnGenerate.addEventListener('click', async function() {
            modal.show();
            loading.style.display = 'block';
            content.style.display = 'none';
            btnCopy.style.display = 'none';

            try {
                const response = await fetch("{{ route('ngo.grants.generate-proposal', $grant->id) }}");
                const data = await response.json();

                if (data.error) {
                    alert(data.error);
                    modal.hide();
                    return;
                }

                currentProposal = data.proposal;
                content.innerHTML = marked.parse(currentProposal);
                loading.style.display = 'none';
                content.style.display = 'block';
                btnCopy.style.display = 'block';
            } catch (error) {
                alert('Erro ao conectar com Bruce AI. Verifique sua conexão.');
                modal.hide();
            }
        });

        btnCopy.addEventListener('click', function() {
            // Remove markdown format for clipboard or keep it? NGO might use markdown. 
            // We copy original.
            navigator.clipboard.writeText(currentProposal).then(() => {
                const originalText = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fas fa-check me-2"></i> Copiado!';
                btnCopy.classList.replace('btn-primary', 'btn-success');
                setTimeout(() => {
                    btnCopy.innerHTML = originalText;
                    btnCopy.classList.replace('btn-success', 'btn-primary');
                }, 2000);
            });
        });
    });
</script>
@endpush
@endsection


