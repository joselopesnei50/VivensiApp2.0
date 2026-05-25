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
.ql-toolbar.ql-snow .ql-formats { margin-right: 12px; }
.ql-toolbar.ql-snow button,
.ql-toolbar.ql-snow .ql-picker-label {
    color: #475569;
    transition: color .15s;
}
.ql-toolbar.ql-snow button:hover,
.ql-toolbar.ql-snow button.ql-active {
    color: #6366f1 !important;
}
.ql-toolbar.ql-snow button.ql-active .ql-stroke,
.ql-toolbar.ql-snow button:hover .ql-stroke {
    stroke: #6366f1 !important;
}
.ql-toolbar.ql-snow button.ql-active .ql-fill,
.ql-toolbar.ql-snow button:hover .ql-fill {
    fill: #6366f1 !important;
}
.ql-container.ql-snow {
    border: none !important;
    font-family: 'Inter', sans-serif;
    font-size: 1rem;
}
.ql-editor {
    min-height: 460px;
    padding: 24px 26px;
    line-height: 1.85;
    color: #1e293b;
    font-size: 1rem;
}
.ql-editor.ql-blank::before {
    color: #cbd5e1;
    font-style: normal;
    font-size: 1rem;
}
.ql-editor h1, .ql-editor h2, .ql-editor h3 {
    color: #0f172a;
    font-weight: 800;
    letter-spacing: -.02em;
    margin-top: 1.5em;
    margin-bottom: .5em;
}
.ql-editor p { margin-bottom: .8em; }
.ql-editor blockquote {
    border-left: 4px solid #6366f1;
    background: #eef2ff;
    padding: 12px 18px;
    border-radius: 0 10px 10px 0;
    color: #4f46e5;
    font-weight: 600;
    margin: 1em 0;
}
.ql-editor pre.ql-syntax {
    background: #0f172a;
    color: #a5f3fc;
    border-radius: 10px;
    padding: 16px 20px;
    font-size: .88rem;
}

.char-counter {
    font-size: .72rem;
    color: #94a3b8;
    font-weight: 600;
    text-align: right;
    padding: 6px 14px 8px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
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

.seo-bar {
    height: 4px;
    border-radius: 4px;
    background: #f1f5f9;
    margin-top: 8px;
    overflow: hidden;
}
.seo-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width .4s, background .4s;
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
            Admin / CMS / Novo Artigo
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Novo Artigo</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Editor premium com formatação rica, SEO e pré-visualização de imagem.</p>
    </div>
</div>

<form action="{{ route('admin.blog.store') }}" method="POST" enctype="multipart/form-data" id="blog-form">
    @csrf
    {{-- Hidden field that Quill populates --}}
    <input type="hidden" name="content" id="content-hidden">

    <div class="row g-4">

        {{-- ── MAIN CONTENT ── --}}
        <div class="col-lg-8">
            <div class="side-card" style="margin-bottom:20px;">

                {{-- Title --}}
                <div class="mb-4">
                    <label class="field-label">Título do Artigo</label>
                    <input type="text" name="title" id="article-title"
                           class="form-control title-input"
                           placeholder="Digite um título impactante..."
                           required value="{{ old('title') }}">
                    @error('title')
                        <span class="text-danger" style="font-size:.8rem;">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Excerpt --}}
                <div class="mb-4">
                    <label class="field-label">Resumo / Subtítulo</label>
                    <input type="text" name="excerpt" id="article-excerpt"
                           class="form-control seo-input"
                           placeholder="Uma frase que resume o artigo (aparece na listagem e no SEO)..."
                           value="{{ old('excerpt') }}">
                </div>

                {{-- Cover Image --}}
                <div class="mb-4">
                    <label class="field-label">Imagem de Capa</label>
                    <label for="file-input" class="img-drop-zone" id="drop-zone">
                        <input type="file" name="image" id="file-input" accept="image/*" class="d-none">
                        <div id="placeholder" class="text-center">
                            <div class="img-icon-circle">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="fw-bold" style="color:#0f172a;font-size:.9rem;">Clique ou arraste a imagem</div>
                            <div style="font-size:.78rem;color:#94a3b8;margin-top:4px;">Recomendado: 1200×600px (JPG, PNG)</div>
                        </div>
                        <div id="preview-container" class="img-preview-wrap d-none">
                            <img loading="lazy" id="image-preview" src="#" alt="Preview">
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
                        <div id="quill-editor">{!! old('content') !!}</div>
                        <div class="char-counter">
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
                <h6><i class="fas fa-rocket me-2" style="color:#6366f1;"></i>Publicação</h6>

                <label class="toggle-row mb-4">
                    <div class="toggle-wrap">
                        <input type="checkbox" name="is_published" value="1"
                               {{ old('is_published') ? 'checked' : '' }} id="togglePublish">
                        <div class="toggle-track">
                            <div class="toggle-thumb"></div>
                        </div>
                    </div>
                    <div>
                        <span class="fw-bold" style="font-size:.88rem;color:#334155;">Publicar imediatamente</span>
                        <div style="font-size:.73rem;color:#94a3b8;">Ficará visível no blog público</div>
                    </div>
                </label>

                <button type="submit" class="btn-save-premium" id="btn-submit">
                    <i class="fas fa-save"></i> Salvar Artigo
                </button>
            </div>

            {{-- SEO --}}
            <div class="side-card">
                <h6><i class="fas fa-magnifying-glass me-2" style="color:#10b981;"></i>SEO</h6>

                <div class="mb-3">
                    <label class="field-label">Meta Descrição</label>
                    <textarea name="meta_description" class="form-control seo-input" rows="3"
                              id="meta-desc"
                              placeholder="Descrição para mecanismos de busca (máx. 160 caracteres)..."
                              style="resize:none;">{{ old('meta_description') }}</textarea>
                    <div class="seo-bar">
                        <div class="seo-bar-fill" id="seo-bar-fill" style="width:0%;background:#10b981;"></div>
                    </div>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:4px;">
                        <span id="meta-count">0</span>/160 caracteres
                    </div>
                </div>

                <div>
                    <label class="field-label">Tags (separadas por vírgula)</label>
                    <input type="text" name="tags" class="form-control seo-input"
                           placeholder="gestão, terceiro setor, ong..."
                           value="{{ old('tags') }}">
                </div>
            </div>

            {{-- Tips --}}
            <div class="tip-card">
                <h6 style="color:#fff;font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                    <i class="fas fa-lightbulb me-2" style="color:#fbbf24;"></i>Dicas
                </h6>
                <ul style="padding-left:18px;margin:0;color:#94a3b8;font-size:.82rem;">
                    <li style="margin-bottom:8px;">Use <strong style="color:#e2e8f0;">H2 e H3</strong> para estruturar o texto.</li>
                    <li style="margin-bottom:8px;">Títulos com <strong style="color:#e2e8f0;">números</strong> têm mais cliques.</li>
                    <li style="margin-bottom:8px;">Preencha sempre a <strong style="color:#e2e8f0;">meta descrição</strong> para SEO.</li>
                    <li>Imagens de <strong style="color:#e2e8f0;">1200×630px</strong> ficam perfeitas no Open Graph.</li>
                </ul>
            </div>

        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Quill Premium Setup ──────────────────────────────────────────────────
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Escreva seu artigo aqui... Use os botões da barra para formatar.',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    // Char counter
    var charCount = document.getElementById('char-count');
    quill.on('text-change', function () {
        var text = quill.getText().trim();
        charCount.textContent = text.length;
    });

    // Before submit, copy HTML to hidden field
    document.getElementById('blog-form').addEventListener('submit', function (e) {
        var html = quill.root.innerHTML;
        if (html === '<p><br></p>' || html === '') {
            e.preventDefault();
            alert('O conteúdo do artigo não pode estar vazio.');
            return;
        }
        document.getElementById('content-hidden').value = html;
    });

    // ── Image Drop Zone ──────────────────────────────────────────────────────
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
        };
        reader.readAsDataURL(file);
    }

    fileInput.addEventListener('change', function () {
        if (this.files[0]) showPreview(this.files[0]);
    });
    removeBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        fileInput.value = '';
        previewWrap.classList.add('d-none');
        placeholder.classList.remove('d-none');
    });
    dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('drag-over'); });
    dropZone.addEventListener('drop', function (e) {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        var file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            var dt = new DataTransfer(); dt.items.add(file);
            fileInput.files = dt.files; showPreview(file);
        }
    });

    // ── SEO Meta Counter ────────────────────────────────────────────────────
    var metaDesc  = document.getElementById('meta-desc');
    var metaCount = document.getElementById('meta-count');
    var barFill   = document.getElementById('seo-bar-fill');

    function updateSeo() {
        var len = metaDesc.value.length;
        metaCount.textContent = len;
        var pct = Math.min((len / 160) * 100, 100);
        barFill.style.width = pct + '%';
        barFill.style.background = len > 160 ? '#ef4444' : (len > 120 ? '#10b981' : '#f59e0b');
    }
    metaDesc.addEventListener('input', updateSeo);
});
</script>
@endsection
