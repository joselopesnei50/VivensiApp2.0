@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Editar Página Institucional</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Atualize o conteúdo oficial de <strong>{{ $page->title }}</strong></p>
    </div>
    <a href="{{ route('admin.pages.index') }}" class="btn-hero-outline" style="font-size: 0.8rem; padding: 10px 20px;">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<form action="{{ route('admin.pages.update', $page->id) }}" method="POST" id="pageForm">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="vivensi-card" style="padding: 30px;">
                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" style="background-color: #fee2e2; color: #b91c1c;">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Título da Página</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control form-control-lg border-0 bg-light rounded-4" placeholder="Ex: Termos de Uso" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Conteúdo Principal</label>
                    <div id="editor-container" style="height: 600px; border-radius: 15px; border: 1px solid #e2e8f0; background: #fff;">
                        {!! sanitize_user_html($page->content) !!}
                    </div>
                    <input type="hidden" name="content" id="contentInput">
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="vivensi-card side-card" style="padding: 25px; position: sticky; top: 20px;">
                <h4 class="fw-800 mb-3" style="font-size: 1.1rem;"><i class="fas fa-gear me-2 text-primary"></i> Configurações</h4>
                
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Slug / URL</label>
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light rounded-start-4">/pagina/</span>
                        <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="form-control border-0 bg-light rounded-end-4" required>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="p-3 rounded-4 bg-light">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-eye text-indigo"></i>
                            <span class="fw-bold small">Visualização</span>
                        </div>
                        <p class="small text-muted mb-0">Esta página é pública e visível para todos os visitantes do site.</p>
                    </div>
                </div>

                <hr style="opacity: 0.1; margin: 20px 0;">

                <button type="submit" class="btn-premium w-100 mb-3 py-3 shadow-lg">
                    <i class="fas fa-save me-2"></i> Salvar Alterações
                </button>
                
                <a href="{{ url('/pagina/' . $page->slug) }}" target="_blank" class="btn btn-light w-100 py-3 rounded-4 fw-bold border" style="color: #6366f1;">
                    <i class="fas fa-external-link-alt me-2"></i> Ver Página Pública
                </a>
            </div>
        </div>
    </div>
</form>

<!-- QUILL EDITOR PREMIUM -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<style>
    .ql-toolbar.ql-snow {
        border: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding: 15px !important;
        background: #f8fafc;
        border-radius: 15px 15px 0 0;
    }
    .ql-container.ql-snow {
        border: none !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 16px !important;
    }
    .ql-editor {
        padding: 25px !important;
        min-height: 500px;
    }
    .form-control-lg:focus {
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
        border: 1px solid rgba(99, 102, 241, 0.4) !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['blockquote', 'code-block', 'link'],
                    ['clean']
                ]
            }
        });

        var form = document.getElementById('pageForm');
        form.onsubmit = function() {
            var content = document.querySelector('#contentInput');
            content.value = quill.root.innerHTML;
        };
    });
</script>
@endsection
