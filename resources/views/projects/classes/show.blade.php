@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Projeto {{ $project->name }} · Turma</p>
            <h1 class="h3 mb-1">🎓 {{ $class->name }}</h1>
            <p class="text-muted mb-0">
                @if($class->teacher) Prof. {{ $class->teacher->name }} · @endif
                @if($class->weekdayLabels()) {{ $class->weekdayLabels() }} · @endif
                @if($class->default_start_time) {{ substr($class->default_start_time, 0, 5) }}{{ $class->default_end_time ? ' → ' . substr($class->default_end_time, 0, 5) : '' }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('projects.classes.index', $project->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Turmas
            </a>
            <a href="{{ route('projects.classes.edit', [$project->id, $class->id]) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Editar
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning rounded-3">{{ session('warning') }}</div>
    @endif

    <div class="row g-4">
        {{-- Coluna: Matriculados --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">👥 Alunos matriculados</h5>
                        <span class="badge bg-primary">{{ $class->enrollments->where('status', 'ativo')->count() }} ativos</span>
                    </div>

                    @if($class->enrollments->isEmpty())
                        <p class="text-muted small mb-3">Nenhum aluno matriculado ainda.</p>
                    @else
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($class->enrollments as $e)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        {{ optional($e->person)->name ?? '—' }}
                                        @if($e->status === 'saiu')
                                            <span class="badge bg-secondary ms-2">Saiu</span>
                                        @elseif($e->status === 'concluido')
                                            <span class="badge bg-info ms-2">Concluído</span>
                                        @endif
                                    </div>
                                    @if($e->status === 'ativo')
                                        <form method="POST" action="{{ route('projects.classes.unenroll', [$project->id, $class->id, $e->id]) }}"
                                              onsubmit="return confirm('Desmatricular este aluno?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($availablePeople->isNotEmpty())
                        <form method="POST" action="{{ route('projects.classes.enroll-bulk', [$project->id, $class->id]) }}">
                            @csrf
                            <label class="form-label small fw-600 mb-1">Matricular novos alunos:</label>
                            <select name="person_ids[]" class="form-select mb-2" multiple size="5">
                                @foreach($availablePeople as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Segure Ctrl/Cmd pra selecionar múltiplos.</small>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-user-plus me-1"></i> Matricular selecionados
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Coluna: Chamadas / Gerar sessões --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📅 Chamadas (últimas 30)</h5>
                        <span class="badge bg-secondary">{{ $sessions->count() }}</span>
                    </div>

                    {{-- Gerar sessões automático --}}
                    @if(!empty($class->weekdays))
                        <form method="POST" action="{{ route('projects.classes.generate-sessions', [$project->id, $class->id]) }}"
                              class="mb-3 p-3 bg-light rounded-3">
                            @csrf
                            <div class="small fw-600 mb-2">🪄 Gerar chamadas do período</div>
                            <div class="row g-2 align-items-end">
                                <div class="col">
                                    <label class="form-label small mb-0">De</label>
                                    <input type="date" name="from" class="form-control form-control-sm"
                                           value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-0">Até</label>
                                    <input type="date" name="to" class="form-control form-control-sm"
                                           value="{{ now()->addMonth()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm">Gerar</button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">Dias configurados: <strong>{{ $class->weekdayLabels() }}</strong>. Idempotente — não cria duplicatas.</small>
                        </form>
                    @else
                        <div class="alert alert-warning small mb-3">
                            Configure os <strong>dias da semana</strong> na turma para gerar chamadas automaticamente.
                        </div>
                    @endif

                    @if($sessions->isEmpty())
                        <p class="text-muted small mb-0">Nenhuma chamada gerada ainda.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small text-uppercase">
                                        <th>Data</th>
                                        <th>Modo</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sessions as $s)
                                        <tr>
                                            <td>{{ $s->date instanceof \Carbon\Carbon ? $s->date->format('d/m/Y') : $s->date }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ ucfirst($s->mode) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ url('/projects/' . $project->id . '/class-sessions/' . $s->id) }}"
                                                   class="btn btn-sm btn-outline-primary">Abrir</a>
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

    {{-- Descricao/notas --}}
    @if($class->description || $class->notes)
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body p-4">
                @if($class->description)
                    <h6 class="fw-bold">Descrição</h6>
                    <p class="text-muted">{{ $class->description }}</p>
                @endif
                @if($class->notes)
                    <h6 class="fw-bold mt-3">Notas internas</h6>
                    <p class="text-muted small">{{ $class->notes }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Deletar turma (perigoso) --}}
    <div class="mt-4 text-end">
        <form method="POST" action="{{ route('projects.classes.destroy', [$project->id, $class->id]) }}"
              onsubmit="return confirm('Excluir turma? As chamadas geradas ficarão como avulsas.');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash me-1"></i> Excluir turma
            </button>
        </form>
    </div>

</div>
@endsection
