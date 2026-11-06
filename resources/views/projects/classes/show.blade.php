@extends('layouts.app')

@section('content')
<div data-theme-scope>
<div class="container py-4" style="max-width: 1200px;">

    @php
        $ativos    = $class->enrollments->where('status', 'ativo')->sortBy(fn ($e) => mb_strtolower(optional($e->person)->name ?? ''));
        $historico = $class->enrollments->whereIn('status', ['saiu', 'concluido'])->sortBy(fn ($e) => mb_strtolower(optional($e->person)->name ?? ''));
        $lotada    = $vagas !== null && $vagas <= 0;
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Projeto {{ $project->name }} · Turma</p>
            <h1 class="h3 mb-1">{{ $class->name }}</h1>
            <p class="text-muted mb-0">
                @if($class->teacher) Prof. {{ $class->teacher->name }} · @endif
                @if($class->weekdayLabels()) {{ $class->weekdayLabels() }} · @endif
                @if($class->default_start_time) {{ substr($class->default_start_time, 0, 5) }}{{ $class->default_end_time ? ' → ' . substr($class->default_end_time, 0, 5) : '' }} @endif
                @if($class->start_date || $class->end_date)
                    · {{ optional($class->start_date)->format('d/m/Y') ?? '…' }} a {{ optional($class->end_date)->format('d/m/Y') ?? '…' }}
                @endif
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

    {{-- Mensagens de sucesso/aviso já aparecem no topo pelo layout. --}}
    @if($errors->any())
        <div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        {{-- Coluna: Alunos --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                        <h5 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>Alunos matriculados</h5>
                        @if($class->max_students)
                            <span class="badge {{ $lotada ? 'bg-danger' : 'bg-primary' }}">
                                {{ $ativos->count() }} de {{ $class->max_students }} vagas{{ $lotada ? ' · lotada' : '' }}
                            </span>
                        @else
                            <span class="badge bg-primary">{{ $ativos->count() }} {{ $ativos->count() === 1 ? 'aluno' : 'alunos' }}</span>
                        @endif
                    </div>

                    @if($ativos->isEmpty())
                        <p class="text-muted small mb-3">Nenhum aluno matriculado ainda.</p>
                    @else
                        <ul class="list-group list-group-flush mb-3">
                            @foreach($ativos as $e)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>{{ optional($e->person)->name ?? '—' }}</span>
                                    <form method="POST" action="{{ route('projects.classes.unenroll', [$project->id, $class->id, $e->id]) }}"
                                          onsubmit="return confirm('Tirar {{ addslashes(optional($e->person)->name ?? 'este aluno') }} da turma? As presenças já registradas continuam no histórico.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Tirar da turma" aria-label="Tirar da turma">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Matricular: caixinhas com busca (antes era lista com Ctrl/Cmd) --}}
                    @if($availablePeople->isNotEmpty())
                        <div class="border rounded-3 p-3 bg-body-tertiary">
                            <div class="fw-semibold small mb-2">Matricular alunos</div>
                            @if($lotada)
                                <p class="small text-danger mb-0">A turma está lotada. Para matricular mais, aumente o número de vagas em <a href="{{ route('projects.classes.edit', [$project->id, $class->id]) }}">Editar</a>.</p>
                            @else
                                <form method="POST" action="{{ route('projects.classes.enroll-bulk', [$project->id, $class->id]) }}" id="enrollForm">
                                    @csrf
                                    <input type="search" class="form-control form-control-sm mb-2" placeholder="Buscar pelo nome…" id="enrollSearch" autocomplete="off" aria-label="Buscar aluno">
                                    <div class="enroll-list" style="max-height: 260px; overflow-y: auto;">
                                        @foreach($availablePeople as $p)
                                            <label class="d-flex align-items-center gap-2 py-1 enroll-item" style="cursor:pointer" data-name="{{ mb_strtolower($p->name) }}">
                                                <input type="checkbox" class="form-check-input mt-0" name="person_ids[]" value="{{ $p->id }}">
                                                <span>{{ $p->name }}</span>
                                                @if($historico->firstWhere('project_person_id', $p->id))
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">já foi da turma</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-2 gap-2 flex-wrap">
                                        <small class="text-muted" id="enrollCount">
                                            @if($vagas !== null) {{ $vagas }} {{ $vagas === 1 ? 'vaga livre' : 'vagas livres' }} @else Marque quem vai entrar na turma @endif
                                        </small>
                                        <button type="submit" class="btn btn-primary btn-sm" id="enrollBtn" disabled>
                                            <i class="fas fa-user-plus me-1"></i> Matricular
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @else
                        <p class="small text-muted mb-0">Todos os participantes ativos do projeto já estão na turma.</p>
                    @endif

                    @if($historico->isNotEmpty())
                        <details class="mt-3">
                            <summary class="small text-muted" style="cursor:pointer">Saíram ou concluíram ({{ $historico->count() }})</summary>
                            <ul class="list-unstyled small mt-2 mb-0">
                                @foreach($historico as $e)
                                    <li class="py-1 d-flex justify-content-between">
                                        <span>{{ optional($e->person)->name ?? '—' }}</span>
                                        <span class="text-muted">
                                            {{ $e->status === 'concluido' ? 'Concluiu' : 'Saiu' }}{{ $e->unenrolled_at ? ' em ' . $e->unenrolled_at->format('d/m/Y') : '' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </div>
        </div>

        {{-- Coluna: Chamadas --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-calendar-check me-2 text-primary"></i>Chamadas</h5>

                    @if(!empty($class->weekdays))
                        <form method="POST" action="{{ route('projects.classes.generate-sessions', [$project->id, $class->id]) }}"
                              class="mb-3 p-3 bg-body-tertiary rounded-3">
                            @csrf
                            <div class="small fw-semibold mb-2">Gerar chamadas do período</div>
                            <div class="row g-2 align-items-end">
                                <div class="col">
                                    <label class="form-label small mb-0" for="genFrom">De</label>
                                    <input type="date" name="from" id="genFrom" class="form-control form-control-sm"
                                           value="{{ max(now()->format('Y-m-d'), optional($class->start_date)->format('Y-m-d') ?? '') }}" required>
                                </div>
                                <div class="col">
                                    <label class="form-label small mb-0" for="genTo">Até</label>
                                    <input type="date" name="to" id="genTo" class="form-control form-control-sm"
                                           value="{{ $class->end_date && $class->end_date->lt(now()->addMonth()) ? $class->end_date->format('Y-m-d') : now()->addMonth()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm">Gerar</button>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Dias da turma: <strong>{{ $class->weekdayLabels() }}</strong>. Datas que já têm chamada não são duplicadas. Até 1 ano por vez.
                            </small>
                        </form>
                    @else
                        <div class="alert alert-warning small mb-3">
                            Configure os <strong>dias da semana</strong> da turma em Editar para gerar as chamadas automaticamente.
                        </div>
                    @endif


                    <div class="small fw-semibold text-uppercase text-muted mb-1" style="letter-spacing:.06em">Próximas</div>
                    @forelse($proximas as $s)
                        @include('projects.classes.partials.session_row', ['s' => $s, 'futura' => true])
                    @empty
                        <p class="text-muted small">Nenhuma chamada agendada. Use "Gerar chamadas do período".</p>
                    @endforelse

                    <div class="small fw-semibold text-uppercase text-muted mt-3 mb-1" style="letter-spacing:.06em">Anteriores</div>
                    @forelse($anteriores as $s)
                        @include('projects.classes.partials.session_row', ['s' => $s, 'futura' => false])
                    @empty
                        <p class="text-muted small mb-0">Nenhuma chamada anterior.</p>
                    @endforelse
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

    {{-- Excluir turma --}}
    <div class="mt-4 text-end">
        <form method="POST" action="{{ route('projects.classes.destroy', [$project->id, $class->id]) }}"
              onsubmit="return confirm('Excluir a turma? As chamadas já geradas continuam no projeto como chamadas avulsas.');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash me-1"></i> Excluir turma
            </button>
        </form>
    </div>

</div>
</div>

<script>
(function () {
    var form = document.getElementById('enrollForm');
    if (!form) return;
    var search = document.getElementById('enrollSearch');
    var btn = document.getElementById('enrollBtn');
    var count = document.getElementById('enrollCount');
    var vagas = @json($vagas);
    var original = count ? count.textContent : '';

    search.addEventListener('input', function () {
        var q = search.value.trim().toLowerCase();
        form.querySelectorAll('.enroll-item').forEach(function (el) {
            el.style.display = !q || el.dataset.name.indexOf(q) !== -1 ? '' : 'none';
        });
    });

    form.addEventListener('change', function () {
        var n = form.querySelectorAll('input[name="person_ids[]"]:checked').length;
        btn.disabled = n === 0;
        btn.innerHTML = '<i class="fas fa-user-plus me-1"></i> Matricular' + (n ? ' ' + n : '');
        if (!count) return;
        if (n === 0) { count.textContent = original; return; }
        count.textContent = n + (n === 1 ? ' selecionado' : ' selecionados') +
            (vagas !== null ? ' de ' + vagas + (vagas === 1 ? ' vaga livre' : ' vagas livres') : '');
        count.classList.toggle('text-danger', vagas !== null && n > vagas);
    });
})();
</script>
@endsection
