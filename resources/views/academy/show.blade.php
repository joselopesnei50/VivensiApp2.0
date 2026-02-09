@extends('layouts.app')

@section('content')
<div style="background-color: #0f172a; min-height: 100vh; padding-bottom: 50px; margin: -1.5rem;">
    
    <!-- Top Bar -->
    <div style="background: #1e293b; border-bottom: 1px solid #334155; padding: 15px 25px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ route('academy.index') }}" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-arrow-left me-2"></i> Voltar
            </a>
            <div style="height: 20px; width: 1px; background: #334155;"></div>
            <h1 style="color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0;">{{ $course->title }}</h1>
        </div>
        <div>
            <!-- Progress Circle or Percentage could go here -->
        </div>
    </div>

    <div class="container-fluid" style="padding: 25px;">
        <div class="row">
            
            <!-- Player Area (Left) -->
            <div class="col-lg-8 mb-4">
                @if($currentLesson)
                    <div class="vivensi-card" style="padding: 0; background: #000; overflow: hidden; border-radius: 12px; margin-bottom: 20px; border: 1px solid #334155;">
                        <div class="ratio ratio-16x9">
                            @if($currentLesson->type == 'video' && $currentLesson->video_url)
                                <iframe src="{{ str_replace('watch?v=', 'embed/', $currentLesson->video_url) }}" allowfullscreen></iframe>
                            @elseif($currentLesson->type == 'ebook')
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; flex-direction: column;">
                                    <i class="fas fa-file-pdf fa-4x mb-3" style="color: #f59e0b;"></i>
                                    <h3>Material de Leitura</h3>
                                    @if($currentLesson->document_url)
                                        <a href="{{ $currentLesson->document_url }}" target="_blank" class="btn btn-warning mt-3">Baixar PDF</a>
                                    @else
                                        <p>Nenhum documento anexado.</p>
                                    @endif
                                </div>
                            @else
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #64748b;">
                                    <p>Selecione uma aula Para assistir.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h2 style="color: #fff; font-weight: 700; font-size: 1.5rem; margin: 0;">{{ $currentLesson->title }}</h2>
                            
                            @if(!$currentLesson->is_completed)
                            <button id="btnComplete" onclick="markAsViewed({{ $currentLesson->id }})" class="btn-premium" style="padding: 10px 25px;">
                                <i class="fas fa-check-circle me-2"></i> Marcar como Concluída
                            </button>
                            @else
                            <button class="btn btn-success" disabled style="padding: 10px 25px; opacity: 0.8;">
                                <i class="fas fa-check-double me-2"></i> Concluída
                            </button>
                            @endif
                        </div>
                        <p style="color: #94a3b8;">{{ $currentLesson->duration_minutes }} minutos • Módulo: {{ $currentLesson->module->title }}</p>
                    </div>
                @else
                    <div style="text-align: center; color: #fff; padding: 50px;">
                        <h3>Nenhuma aula disponível.</h3>
                    </div>
                @endif
            </div>

            <!-- Sidebar (Right) -->
            <div class="col-lg-4">
                <div class="vivensi-card" style="background: #1e293b; border: 1px solid #334155; padding: 0; overflow: hidden;">
                    <div style="padding: 15px 20px; border-bottom: 1px solid #334155; background: #1e293b;">
                        <h5 style="color: #fff; margin: 0; font-weight: 700;">Conteúdo do Curso</h5>
                    </div>
                    
                    <div class="accordion accordion-dark" id="accordionCourse">
                        @foreach($course->modules as $module)
                            <div class="accordion-item" style="border: none; background: transparent;">
                                <h2 class="accordion-header" id="heading{{ $module->id }}">
                                    <button class="accordion-button {{ $currentLesson && $currentLesson->module_id == $module->id ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $module->id }}" aria-expanded="{{ $currentLesson && $currentLesson->module_id == $module->id ? 'true' : 'false' }}" aria-controls="collapse{{ $module->id }}" style="background: #1e293b; color: #e2e8f0; border-bottom: 1px solid #334155; box-shadow: none;">
                                        <span style="font-weight: 600; font-size: 0.9rem;">{{ $module->order }}. {{ $module->title }}</span>
                                    </button>
                                </h2>
                                <div id="collapse{{ $module->id }}" class="accordion-collapse collapse {{ $currentLesson && $currentLesson->module_id == $module->id ? 'show' : '' }}" aria-labelledby="heading{{ $module->id }}" data-bs-parent="#accordionCourse">
                                    <div class="accordion-body" style="padding: 0; background: #0f172a;">
                                        <div class="list-group list-group-flush">
                                            @foreach($module->lessons as $lesson)
                                                <a href="{{ route('academy.show', ['slug' => $course->slug]) }}?lesson_id={{ $lesson->id }}" class="list-group-item list-group-item-action d-flex align-items-center {{ $currentLesson && $currentLesson->id == $lesson->id ? 'active-lesson' : '' }}" style="background: transparent; color: #94a3b8; border-bottom: 1px solid #1e293b; padding: 15px 20px;">
                                                    <div style="margin-right: 15px;">
                                                        @if($lesson->is_completed)
                                                            <i class="fas fa-check-circle text-success" style="font-size: 1.1rem;"></i>
                                                        @else
                                                            <i class="far fa-circle" style="font-size: 1.1rem;"></i>
                                                        @endif
                                                    </div>
                                                    <div style="flex: 1;">
                                                        <div style="color: {{ $currentLesson && $currentLesson->id == $lesson->id ? '#fff' : '#cbd5e1' }}; font-weight: {{ $currentLesson && $currentLesson->id == $lesson->id ? '700' : '400' }}; font-size: 0.9rem;">
                                                            {{ $lesson->order }}. {{ $lesson->title }}
                                                        </div>
                                                        <small style="color: #64748b; font-size: 0.75rem;">
                                                            <i class="far fa-clock me-1"></i> {{ $lesson->duration_minutes }} min
                                                        </small>
                                                    </div>
                                                    @if($currentLesson && $currentLesson->id == $lesson->id)
                                                        <div style="color: #6366f1;">
                                                            <i class="fas fa-play" style="font-size: 0.8rem;"></i>
                                                        </div>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .accordion-button::after {
        filter: invert(1);
    }
    .accordion-button:not(.collapsed) {
        color: #6366f1 !important;
        background-color: #1e293b !important;
    }
    .active-lesson {
        background: #1e293b !important;
        border-left: 3px solid #6366f1 !important;
    }
</style>

<script>
    function markAsViewed(lessonId) {
        const btn = document.getElementById('btnComplete');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Salvando...';
        btn.disabled = true;

        fetch(`{{ url('/academy/lessons') }}/${lessonId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.className = 'btn btn-success';
                btn.innerHTML = '<i class="fas fa-check-double me-2"></i> Concluída';
                // Optional: reload to update sidebar or advance to next
                setTimeout(() => {
                    location.reload(); 
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>
@endsection
