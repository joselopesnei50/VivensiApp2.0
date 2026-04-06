@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Vivensi Academy</h6>
        <h2 style="margin: 0; color: #111827;">Editar Curso</h2>
    </div>
    <a href="{{ route('admin.academy.index') }}" class="btn-outline">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="vivensi-card" style="padding: 30px;">
            <form action="{{ route('admin.academy.update', $course->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Título do Curso</label>
                    <input type="text" name="title" value="{{ $course->title }}" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Descrição</label>
                    <textarea name="description" class="form-control" style="height: 200px;">{{ $course->description }}</textarea>
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Instrutor</label>
                    <input type="text" name="teacher_name" value="{{ $course->teacher_name }}" class="form-control">
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Capa (Thumbnail)</label>
                    @if($course->thumbnail_url)
                        <img src="{{ $course->thumbnail_url }}" alt="Thumbnail" style="width: 120px; height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; display: block;">
                    @endif
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                </div>

                <div class="mb-4 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" {{ $course->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive" style="font-weight: 600; color: #334155;">Curso Ativo?</label>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" class="btn-premium">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 30px; text-align: center;">
            <div style="font-size: 3rem; color: #6366f1; margin-bottom: 15px;">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3 style="margin-bottom: 10px; color: #1e293b;">Conteúdo do Curso</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Gerencie os módulos e as aulas deste curso.</p>
            
            <a href="{{ route('admin.academy.modules.index', $course->id) }}" class="btn btn-primary" style="width: 100%; border-radius: 8px; padding: 12px; font-weight: 700;">
                <i class="fas fa-edit me-2"></i> Gerenciar Módulos
            </a>
            <small class="text-muted d-block mt-2">Adicionar Aulas e Vídeos</small>
        </div>
    </div>
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
