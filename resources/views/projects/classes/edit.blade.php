@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">

    <div class="mb-4">
        <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Projeto {{ $project->name }} · Turmas</p>
        <h1 class="h3 mb-0">✏️ Editar Turma</h1>
    </div>

    <a href="{{ route('projects.classes.show', [$project->id, $class->id]) }}" class="btn btn-outline-secondary mb-4">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>

    @include('projects.classes.partials.form', [
        'action'    => route('projects.classes.update', [$project->id, $class->id]),
        'method'    => 'PUT',
        'model'     => $class,
        'teachers'  => $teachers,
        'submitText'=> 'Salvar alterações',
    ])

</div>
@endsection
