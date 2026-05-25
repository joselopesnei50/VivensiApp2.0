@extends('layouts.app')

@section('content')
@php $basePath = rtrim(request()->getBaseUrl(), '/'); @endphp

{{-- Quill.js CDN --}}
@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.fw-800  { font-weight: 800; }
.flex-1  { flex: 1; }

.field-label {
    display: block;
    font-size: .75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 8px;
}

.title-input {
    font-size: 1.3rem;
    font-weight: 700;
    padding: 16px 18px;
    border-color: #e2e8f0;
    background: #f8fafc;
    border-radius: 12px !important;
    transition: all .2s;
}
.title-input:focus {
    background: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
}

/* Quill Editor Premium Wrapper */
.editor-wrapper {
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.editor-wrapper:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.ql-toolbar.ql-snow {
    border: none !important;
    border-bottom: 1.5px solid #f1f5f9 !important;
    background: #f8fafc;
    padding: 10px 14px;
    border-radius: 0;
}
.ql-toolbar.ql-snow button,
.ql-toolbar.ql-snow .ql-picker-label {
    color: #475569;
}
.ql-toolbar.ql-snow button:hover,
.ql-toolbar.ql-snow button.ql-active {
    color: #6366f1 !important;
}
.ql-container.ql-snow {
    border: none !important;
    font-family: 'Inter', sans-serif;
}
.ql-editor {
    min-height: 460px;
    padding: 24px 26px;
    line-height: 1.85;
    color: #1e293b;
    font-size: 1rem;
}

/* Image drop zone */
.img-drop-zone {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 190px;
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    position: relative;
    overflow: hidden;
}
.img-drop-zone.has-image { border-style: solid; border-color: #e2e8f0; }
.img-drop-zone:hover, .img-drop-zone.drag-over {
    border-color: #6366f1;
    background: #eef2ff;
}
.img-icon-circle {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 12px;
}
.img-preview-wrap { position: absolute; inset: 0; }
.img-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }
.img-remove-btn {
    position: absolute; top: 12px; right: 12px;
    width: 32px; height: 32px;
    background: rgba(0,0,0,.5);
    border: none; border-radius: 50%;
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    transition: background .15s;
    z-index: 5;
}
.img-remove-btn:hover { background: #ef4444; }

/* Sidebar cards */
.side-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(0,0,0,.03);
    margin-bottom: 20px;
}
.side-card h6 {
    font-weight: 800;
    font-size: .82rem;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 16px;
}

/* Toggle */
.toggle-row { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.toggle-wrap input { display: none; }
.toggle-track {
    width: 46px; height: 26px;
    background: #cbd5e1;
    border-radius: 50px;
    position: relative;
    transition: background .25s;
    flex-shrink: 0;
}
.toggle-thumb {
    width: 22px; height: 22px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
    top: 2px; left: 2px;
    transition: transform .25s;
    box-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.toggle-wrap input:checked + .toggle-track { background: #10b981; }
.toggle-wrap input:checked + .toggle-track .toggle-thumb { transform: translateX(20px); }

/* SEO fields */
.seo-input {
    border-radius: 10px !important;
    border-color: #e2e8f0;
    font-size: .88rem;
    background: #f8fafc;
}
.seo-input:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }

/* Tips */
.tip-card { background: linear-gradient(145deg, #1e293b, #0f172a); border-radius: 20px; padding: 24px; }

/* Save btn */
.btn-save-premium {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px 24px;
    font-weight: 800;
    font-size: .95rem;
    width: 100%;
    transition: all .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
}
.btn-save-premium:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(99,102,241,.4);
    color: #fff;
}
</style>
@endpush

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <a href="{{ route('admin.blog.index') }}"
       class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center gap-1 flex-shrink-0"
       style="padding:6px 14px;font-size:.82rem;margin-top:6px;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Admin / CMS / Editar Artigo
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Editar Artigo</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Mantenha seu conteúdo atualizado e engajador.</p>
    </div>
</div>

<form action="{{ route('admin.blog.update', $post->id) }}" method="POST" enctype="multipart/form-data" id="blog-form">
    @csrf
    @method('PUT')
    {{-- Hidden field that Quill populates --}}
    <input type="hidden" name="content" id="content-hidden">

    <div class="row g-4">

        {{-- ── MAIN CONTENT ── --}}
        <div class="col-lg-8">
            <div class="side-card">

                {{-- Title --}}
                <div class="mb-4">
                    <label class="field-label">Título do Artigo</label>
                    <input type="text" name="title"
                           class="form-control title-input"
                           placeholder="Digite um título impactante..."
                           required value="{{ old('title', $post->title) }}">
                    @error('title')
                        <span class="text-danger" style="font-size:.8rem;">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Excerpt --}}
                <div class="mb-4">
                    <label class="field-label">Resumo / Subtítulo</label>
                    <input type="text" name="excerpt"
                           class="form-control seo-input"
                           placeholder="Uma frase que resume o artigo..."
                           value="{{ old('excerpt', $post->excerpt ?? '') }}">
                </div>

                {{-- Cover Image --}}
                <div class="mb-4">
                    <label class="field-label">Imagem de Capa</label>
                    <label for="file-input" class="img-drop-zone {{ $post->image ? 'has-image' : '' }}" id="drop-zone">
                        <input type="file" name="image" id="file-input" accept="image/*" class="d-none">
                        <div id="placeholder" class="text-center {{ $post->image ? 'd-none' : '' }}">
                            <div class="img-icon-circle">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="fw-bold" style="color:#0f172a;font-size:.9rem;">Clique para alterar</div>
                        </div>
                        <div id="preview-container" class="img-preview-wrap {{ $post->image ? '' : 'd-none' }}">
                            <img loading="lazy" id="image-preview" src="{{ $post->image ?? '#' }}" alt="Preview">
                            <button type="button" id="btn-remove" class="img-remove-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </label>
                </div>

                {{-- QUILL EDITOR --}}
                <div class="mb-2">
                    <label class="field-label">Conteúdo do Artigo</label>
                    <div class="editor-wrapper">
                        <div id="quill-editor">{!! old('content', $post->content) !!}</div>
                        <div class="char-counter" style="font-size:.72rem;color:#94a3b8;text-align:right;padding:8px 14px;background:#f8fafc;border-top:1px solid #f1f5f9;">
                            <span id="char-count">0</span> caracteres
                        </div>
                    </div>
                    @error('content')
                        <span class="text-danger" style="font-size:.8rem;">{{ $message }}</span>
                    @enderror
                </div>

            </div>
        </div>

        {{-- ── SIDEBAR ── --}}
        <div class="col-lg-4">

            {{-- Publish --}}
            <div class="side-card">
                <h6><i class="fas fa-rocket me-2" style="color:#6366f1;"></i>Status</h6>

                <label class="toggle-row mb-4">
                    <div class="toggle-wrap">
                        <input type="checkbox" name="is_published" value="1"
                               {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                        <div class="toggle-track">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <div>
                        <span class="fw-bold" style="font-size:.88rem;color:#334155;">Publicado</span>
                    </div>
                </label>

                @if($post->published_at)
                    <div class="mb-4 px-3 py-2 rounded-3 text-center" style="background:#f0fdf4; border:1px solid #dcfce7; font-size:.78rem; color:#166534;">
                        <i class="far fa-calendar-check me-1"></i>
                        Publicado em <strong>{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') }}</strong>
                    </div>
                @endif

                <button type="submit" class="btn-save-premium">
                    <i class="fas fa-save"></i> Atualizar Artigo
                </button>
            </div>

            {{-- SEO --}}
            <div class="side-card">
                <h6><i class="fas fa-magnifying-glass me-2" style="color:#10b981;"></i>SEO</h6>
                <div class="mb-3">
                    <label class="field-label">Meta Descrição</label>
                    <textarea name="meta_description" class="form-control seo-input" rows="3"
                              placeholder="Descrição para Google...">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="field-label">Tags</label>
                    <input type="text" name="tags" class="form-control seo-input"
                           placeholder="gestão, terceiro setor..."
                           value="{{ old('tags', $post->tags ?? '') }}">
                </div>
            </div>

        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    var charCount = document.getElementById('char-count');
    function updateCounter() {
        charCount.textContent = quill.getText().trim().length;
    }
    quill.on('text-change', updateCounter);
    updateCounter();

    document.getElementById('blog-form').addEventListener('submit', function () {
        document.getElementById('content-hidden').value = quill.root.innerHTML;
    });

    // Image logic
    var fileInput = document.getElementById('file-input');
    var preview = document.getElementById('image-preview');
    var previewWrap = document.getElementById('preview-container');
    var placeholder = document.getElementById('placeholder');
    var removeBtn = document.getElementById('btn-remove');
    var dropZone = document.getElementById('drop-zone');

    fileInput.addEventListener('change', function () {
        if (this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                previewWrap.classList.remove('d-none');
                placeholder.classList.add('d-none');
                dropZone.classList.add('has-image');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    removeBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        fileInput.value = '';
        previewWrap.classList.add('d-none');
        placeholder.classList.remove('d-none');
        dropZone.classList.remove('has-image');
    });
});
</script>
@endsection
