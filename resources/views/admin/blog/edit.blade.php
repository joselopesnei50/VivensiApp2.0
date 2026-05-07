@extends('layouts.app')

@section('content')
@php $basePath = rtrim(request()->getBaseUrl(), '/'); @endphp

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
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Atualize o conteúdo e mantenha seu blog vivo.</p>
    </div>
</div>

<form action="{{ route('admin.blog.update', $post->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row g-4">

        {{-- Main content --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Title --}}
                    <div class="mb-4">
                        <label class="field-label">Título do Artigo</label>
                        <input type="text" name="title" class="form-control rounded-3 title-input"
                               placeholder="Digite um título impactante..." required
                               value="{{ old('title', $post->title) }}">
                        @error('title')
                            <span class="text-danger" style="font-size:.8rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Cover image --}}
                    <div class="mb-4">
                        <label class="field-label">Imagem de Capa</label>
                        <label for="file-input" class="img-drop-zone {{ $post->image ? 'has-image' : '' }}" id="drop-zone">
                            <input type="file" name="image" id="file-input" accept="image/*" class="d-none">
                            <div id="placeholder" class="text-center {{ $post->image ? 'd-none' : '' }}">
                                <div class="img-icon-circle">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="fw-bold" style="color:#0f172a;font-size:.9rem;">Clique para alterar</div>
                                <div style="font-size:.78rem;color:#94a3b8;margin-top:4px;">Recomendado: 1200×600px (JPG, PNG)</div>
                            </div>
                            <div id="preview-container" class="img-preview-wrap {{ $post->image ? '' : 'd-none' }}">
                                <img id="image-preview" src="{{ $post->image ?? '#' }}" alt="Preview">
                                <button type="button" id="btn-remove" class="img-remove-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </label>
                    </div>

                    {{-- Content --}}
                    <div class="mb-2">
                        <label class="field-label">Conteúdo</label>
                        <textarea name="content" class="form-control rounded-3 content-area"
                                  placeholder="Escreva seu artigo aqui..." required>{{ old('content', $post->content) }}</textarea>
                        <div class="text-end mt-1" style="font-size:.75rem;color:#94a3b8;">
                            <i class="fab fa-markdown"></i> Markdown suportado
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">

            {{-- Publish --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#0f172a;font-size:.9rem;">Status da Publicação</h6>

                    <label class="toggle-row mb-3">
                        <div class="toggle-wrap">
                            <input type="checkbox" name="is_published" value="1"
                                   {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                            <div class="toggle-track">
                                <div class="toggle-thumb"></div>
                            </div>
                        </div>
                        <span class="fw-bold" style="font-size:.88rem;color:#334155;">Publicado</span>
                    </label>

                    @if($post->published_at)
                        <div class="mb-3 px-3 py-2 rounded-3" style="background:#f8fafc;font-size:.78rem;color:#64748b;">
                            <i class="far fa-calendar-check me-1"></i>
                            Publicado em <strong>{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') }}</strong>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-3 d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-save"></i> Atualizar Artigo
                    </button>
                </div>
            </div>

            {{-- Tips --}}
            <div class="card border-0 rounded-4 tip-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#fff;font-size:.9rem;">
                        <i class="fas fa-lightbulb me-1"></i> Ao Editar
                    </h6>
                    <ul style="padding-left:18px;margin:0;color:#94a3b8;font-size:.82rem;">
                        <li style="margin-bottom:8px;">Mudar o título pode alterar o link (slug). Cuidado com links compartilhados!</li>
                        <li>Revise a formatação após colar texto de outros lugares.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</form>

@push('styles')
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
    font-size: 1.2rem;
    font-weight: 700;
    padding: 14px 16px;
    border-color: #e2e8f0;
    background: #f8fafc;
}
.title-input:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

.content-area {
    min-height: 420px;
    resize: vertical;
    font-size: 1rem;
    line-height: 1.8;
    border-color: #e2e8f0;
    background: #f8fafc;
    padding: 16px;
}
.content-area:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

.img-drop-zone {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    position: relative;
    overflow: hidden;
}
.img-drop-zone:hover, .img-drop-zone.drag-over {
    border-color: #6366f1;
    background: #eef2ff;
}
.img-drop-zone.has-image { border-style: solid; border-color: #e2e8f0; }
.img-icon-circle {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 12px;
}
.img-preview-wrap {
    position: absolute; inset: 0;
}
.img-preview-wrap img {
    width: 100%; height: 100%; object-fit: cover;
}
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

.toggle-row {
    display: flex; align-items: center; gap: 12px; cursor: pointer;
}
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

.tip-card { background: linear-gradient(145deg, #1e293b, #0f172a); }
</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {
    var fileInput   = document.getElementById('file-input');
    var preview     = document.getElementById('image-preview');
    var previewWrap = document.getElementById('preview-container');
    var placeholder = document.getElementById('placeholder');
    var removeBtn   = document.getElementById('btn-remove');
    var dropZone    = document.getElementById('drop-zone');

    function showPreview(file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            previewWrap.classList.remove('d-none');
            placeholder.classList.add('d-none');
            dropZone.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    }

    fileInput.addEventListener('change', function () {
        if (this.files[0]) showPreview(this.files[0]);
    });

    removeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fileInput.value = '';
        previewWrap.classList.add('d-none');
        placeholder.classList.remove('d-none');
        dropZone.classList.remove('has-image');
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });
    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('drag-over');
    });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        var file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showPreview(file);
        }
    });
});
</script>
@endsection
