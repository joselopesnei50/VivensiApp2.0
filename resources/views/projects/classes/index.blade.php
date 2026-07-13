@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="mb-4">
        <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Projeto {{ $project->name }}</p>
        <h1 class="h3 mb-1">🎓 Turmas</h1>
        <p class="text-muted mb-0">Aulas recorrentes com alunos matriculados fixos. Cada turma gera N chamadas.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning rounded-3">{{ session('warning') }}</div>
    @endif

    <div class="d-flex gap-2 mb-4">
        <a href="{{ url('/projects/' . $project->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Projeto
        </a>
        <a href="{{ route('projects.classes.create', $project->id) }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nova Turma
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @if($classes->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-2">Nenhuma turma cadastrada ainda.</p>
                    <a href="{{ route('projects.classes.create', $project->id) }}" class="btn btn-primary btn-sm">
                        Criar primeira turma
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th class="ps-4">Nome</th>
                                <th>Professor</th>
                                <th>Dias</th>
                                <th>Horário</th>
                                <th class="text-center">Alunos</th>
                                <th class="text-center">Chamadas</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $c)
                                <tr>
                                    <td class="ps-4"><strong>{{ $c->name }}</strong></td>
                                    <td>{{ optional($c->teacher)->name ?? '—' }}</td>
                                    <td><small>{{ $c->weekdayLabels() ?: '—' }}</small></td>
                                    <td>
                                        @if($c->default_start_time)
                                            <small>{{ substr($c->default_start_time, 0, 5) }}{{ $c->default_end_time ? ' → ' . substr($c->default_end_time, 0, 5) : '' }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $c->students_count }}</td>
                                    <td class="text-center">{{ $c->sessions_count }}</td>
                                    <td class="text-center">
                                        @if($c->status === 'ativo')
                                            <span class="badge bg-success">Ativo</span>
                                        @else
                                            <span class="badge bg-secondary">Encerrado</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('projects.classes.show', [$project->id, $c->id]) }}"
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
@endsection
