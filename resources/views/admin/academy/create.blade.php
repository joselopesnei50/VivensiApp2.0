@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Vivensi Academy</h6>
        <h2 style="margin: 0; color: #111827;">Novo Curso</h2>
    </div>
    <a href="{{ route('admin.academy.index') }}" class="btn-outline">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<div class="vivensi-card" style="padding: 30px;">
    @if ($errors->any())
        <div class="alert alert-danger" style="background-color: #fee2e2; border: 1px solid #f87171; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.academy.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Título do Curso</label>
                    <input type="text" name="title" class="form-control" required placeholder="Ex: Gestão Financeira para ONGs">
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Descrição</label>
                    <textarea name="description" class="form-control" style="height: 200px;"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Instrutor</label>
                    <input type="text" name="teacher_name" class="form-control" placeholder="Nome do Professor">
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Capa (Thumbnail)</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                    <small class="text-muted">Recomendado: 1280x720px (JPG/PNG)</small>
                </div>

                <div class="mb-4 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                    <label class="form-check-label" for="isActive" style="font-weight: 600; color: #334155;">Curso Ativo?</label>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 20px;">
            <button type="submit" class="btn-premium">Criar Curso</button>
        </div>
    </form>
</div>

<!-- Summernote Lite -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-pt-BR.min.js"></script>

<script>
    $(document).ready(function() {
        $('textarea[name="description"]').summernote({
            placeholder: 'Descreva o que o aluno vai aprender...',
            tabsize: 2,
            height: 200,
            lang: 'pt-BR',
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['color', ['color']],
            ]
        });
    });
</script>
@endsection
