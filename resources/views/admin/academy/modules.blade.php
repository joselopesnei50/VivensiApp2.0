@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Vivensi Academy</h6>
        <h2 style="margin: 0; color: #111827;">Conteúdo: {{ $course->title }}</h2>
    </div>
    <a href="{{ route('admin.academy.edit', $course->id) }}" class="btn-outline">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<div class="row">
    <!-- Adicionar Módulo -->
    <div class="col-md-4 mb-4">
        <div class="vivensi-card" style="padding: 25px;">
            <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 20px;">Novo Módulo</h5>
            <form action="{{ route('admin.academy.modules.store', $course->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label" style="font-weight: 600;">Título do Módulo</label>
                    <input type="text" name="title" class="form-control" required placeholder="Ex: Módulo 1 - Introdução">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-weight: 600;">Ordem</label>
                    <input type="number" name="order" class="form-control" value="0">
                </div>
                <button type="submit" class="btn btn-dark w-100">
                    <i class="fas fa-plus me-2"></i> Adicionar Módulo
                </button>
            </form>
        </div>
    </div>

    <!-- Lista de Módulos (Accordion) -->
    <div class="col-md-8">
        @if(session('success'))
            <div class="alert alert-success" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                {{ session('success') }}
            </div>
        @endif

        <div class="accordion" id="accordionModules">
            @forelse($course->modules as $module)
                <div class="accordion-item" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 8px; overflow: hidden;">
                    <h2 class="accordion-header" id="heading{{ $module->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $module->id }}" aria-expanded="false" aria-controls="collapse{{ $module->id }}" style="background: #f8fafc; color: #0f172a; font-weight: 700;">
                            <div style="display: flex; justify-content: space-between; width: 100%; align-items: center; padding-right: 10px;">
                                <span>{{ $module->order }} - {{ $module->title }}</span>
                                <small style="color: #64748b;">{{ $module->lessons->count() }} aulas</small>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $module->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $module->id }}" data-bs-parent="#accordionModules">
                        <div class="accordion-body" style="background: #fff;">
                            
                            <!-- Ações do Módulo -->
                            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; gap: 10px;">
                                <form action="{{ route('admin.academy.modules.destroy', $module->id) }}" method="POST" onsubmit="return confirm('Excluir este módulo e todas as aulas?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm text-danger">Excluir Módulo</button>
                                </form>
                            </div>

                            <!-- Lista de Aulas -->
                            @if($module->lessons->count() > 0)
                                <ul class="list-group mb-4">
                                    @foreach($module->lessons as $lesson)
                                        <li class="list-group-item d-flex justify-content-between align-items-center" style="border-left: 4px solid {{ $lesson->type == 'video' ? '#6366f1' : '#f59e0b' }};">
                                            <div>
                                                <i class="fas fa-{{ $lesson->type == 'video' ? 'play-circle' : 'file-pdf' }} me-2" style="color: {{ $lesson->type == 'video' ? '#6366f1' : '#f59e0b' }}"></i>
                                                <strong>{{ $lesson->order }} - {{ $lesson->title }}</strong>
                                                <div class="text-muted small" style="margin-left: 22px;">
                                                    {{ $lesson->duration_minutes }} min | 
                                                    @if($lesson->video_url) <a href="{{ $lesson->video_url }}" target="_blank">Link Vídeo</a> @endif
                                                </div>
                                            </div>
                                            <form action="{{ route('admin.academy.lessons.destroy', $lesson->id) }}" method="POST" onsubmit="return confirm('Remover aula?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted text-center py-3">Nenhuma aula neste módulo.</p>
                            @endif

                            <!-- Adicionar Aula -->
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px;">
                                <h6 style="font-weight: 700; margin-bottom: 15px;">Adicionar Aula</h6>
                                <form action="{{ route('admin.academy.lessons.store', $module->id) }}" method="POST" enctype="multipart/form-data" id="lessonForm{{ $module->id }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-8 mb-2">
                                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Título da Aula" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="number" name="order" class="form-control form-control-sm" placeholder="Ord." value="{{ $module->lessons->count() + 1 }}">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <select name="type" class="form-select form-select-sm lesson-type-selector" data-module="{{ $module->id }}">
                                                <option value="video">Vídeo</option>
                                                <option value="ebook">E-book</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Video Fields -->
                                        <div class="col-md-8 mb-2 video-fields-{{ $module->id }}">
                                            <input type="url" name="video_url" class="form-control form-control-sm" placeholder="URL do Vídeo (YouTube/Vimeo)">
                                        </div>
                                        
                                        <!-- Ebook Fields -->
                                        <div class="col-md-8 mb-2 ebook-fields-{{ $module->id }}" style="display: none;">
                                            <input type="file" name="document" class="form-control form-control-sm" accept=".pdf">
                                            <small class="text-muted">Arquivo PDF (máx. 10MB)</small>
                                        </div>
                                        
                                        <div class="col-md-2 mb-2">
                                            <input type="number" name="duration_minutes" class="form-control form-control-sm" placeholder="Min.">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Adicionar</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const selector = document.querySelector('.lesson-type-selector[data-module="{{ $module->id }}"]');
                                    if (selector) {
                                        selector.addEventListener('change', function() {
                                            const moduleId = this.dataset.module;
                                            const videoFields = document.querySelector('.video-fields-' + moduleId);
                                            const ebookFields = document.querySelector('.ebook-fields-' + moduleId);
                                            
                                            if (this.value === 'ebook') {
                                                videoFields.style.display = 'none';
                                                ebookFields.style.display = 'block';
                                            } else {
                                                videoFields.style.display = 'block';
                                                ebookFields.style.display = 'none';
                                            }
                                        });
                                    }
                                });
                            </script>

                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-layer-group fa-3x mb-3"></i>
                    <p>Este curso ainda não tem módulos.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
