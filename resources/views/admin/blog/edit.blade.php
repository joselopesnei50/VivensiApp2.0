@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 2rem; letter-spacing: -1px;">Editar Artigo</h2>
            <p style="color: #64748b; margin: 5px 0 0 0; font-size: 1.1rem;">Atualize o conteúdo e mantenha seu blog vivo.</p>
        </div>
        <a href="{{ route('admin.blog.index') }}" class="btn-outline-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<form action="{{ route('admin.blog.update', $post->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Main Content (Left) -->
        <div class="col-lg-8">
            <div class="vivensi-card" style="padding: 40px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; background: white;">
                
                <!-- Title -->
                <div class="mb-5">
                    <label class="form-label-premium">Título do Artigo</label>
                    <input type="text" name="title" class="form-control-premium title-input" placeholder="Digite um título impactante..." required value="{{ old('title', $post->title) }}">
                    @error('title') <span class="text-danger-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Cover Image Upload -->
                <div class="mb-5">
                    <label class="form-label-premium">Imagem de Capa</label>
                    <div class="image-upload-area {{ $post->image ? 'has-image' : '' }}" id="drop-zone">
                        <input type="file" name="image" id="file-input" class="file-input-hidden" accept="image/*">
                        
                        <div class="upload-placeholder" id="placeholder" style="{{ $post->image ? 'display: none;' : '' }}">
                            <div class="icon-circle">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p class="upload-text"><strong>Clique para alterar</strong> ou arraste uma nova imagem</p>
                            <p class="upload-hint">Recomendado: 1200x600px (JPG, PNG)</p>
                        </div>

                        <div class="image-preview-container" id="preview-container" style="{{ $post->image ? 'display: block;' : 'display: none;' }}">
                            <img id="image-preview" src="{{ $post->image ?? '#' }}" alt="Preview">
                            <button type="button" class="btn-remove-image" id="btn-remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Editor -->
                <div class="mb-4">
                    <label class="form-label-premium">Conteúdo</label>
                    <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                        <textarea name="content" class="form-control-premium content-area" placeholder="Escreva seu artigo aqui..." required>{{ old('content', $post->content) }}</textarea>
                    </div>
                    </div>

<style>
    /* ... existing styles ... */
        <div class="col-lg-4">
            <!-- Publish Settings -->
            <div class="vivensi-card mb-4" style="padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; background: white;">
                <h3 class="card-title-sm">Status da Publicação</h3>
                
                <label class="toggle-switch mb-4">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                    <div class="toggle-track">
                        <div class="toggle-thumb"></div>
                    </div>
                    <span class="toggle-label">Publicado</span>
                </label>

                @if($post->published_at)
                    <p class="text-sm text-gray-500 mb-4">
                        <i class="far fa-calendar-check"></i> Publicado em: <br>
                        <strong>{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') }}</strong>
                    </p>
                @endif

                <hr style="border-color: #f1f5f9; margin: 20px 0;">

                <button type="submit" class="btn-premium-full">
                    <i class="fas fa-save"></i> Atualizar Artigo
                </button>
            </div>

            <!-- SEO & Tips -->
            <div class="vivensi-card gradient-card" style="padding: 30px; border-radius: 20px;">
                <h3 class="card-title-white"><i class="fas fa-lightbulb"></i> Ao Editar</h3>
                <ul class="tips-list">
                    <li>Mudar o título pode alterar o link (slug) do post. Cuidado com links compartilhados!</li>
                    <li>Revise a formatação após colar texto de outros lugares.</li>
                </ul>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: 'textarea.content-area',
        height: 500,
        menubar: false,
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        language: 'pt_BR',
        skin: 'oxide',
        content_css: 'default',
        branding: false,
        promotion: false
    });
</script>

<style>
    /* Premium Form Styles */
    .form-label-premium {
        display: block;
        font-weight: 700;
        color: #334155;
        margin-bottom: 10px;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control-premium {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f8fafc;
        color: #1e293b;
    }

    .form-control-premium:focus {
        border-color: #4f46e5;
        background: white;
        outline: none;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .title-input {
        font-size: 1.5rem;
        font-weight: 700;
        padding: 20px;
    }

    .content-area {
        min-height: 500px;
        border: none;
        resize: vertical;
        line-height: 1.8;
        font-size: 1.1rem;
    }

    /* Image Upload Styles */
    .image-upload-area {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s;
        min-height: 250px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-upload-area:hover, .image-upload-area.dragover {
        border-color: #4f46e5;
        background: #eef2ff;
    }

    .file-input-hidden {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .upload-placeholder {
        text-align: center;
        pointer-events: none;
    }

    .icon-circle {
        width: 60px;
        height: 60px;
        background: #e0e7ff;
        color: #4f46e5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 15px;
    }

    .upload-text {
        color: #1e293b;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .upload-hint {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .image-preview-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #0f172a;
        z-index: 3;
    }

    #image-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .btn-remove-image {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
    }

    .btn-remove-image:hover {
        background: #ef4444;
        transform: scale(1.1);
    }

    /* Buttons & Toggles */
    .btn-outline-sm {
        padding: 8px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-outline-sm:hover {
        border-color: #94a3b8;
        color: #1e293b;
        background: #f1f5f9;
    }

    .btn-premium-full {
        width: 100%;
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
    }

    .btn-premium-full:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 20px -3px rgba(79, 70, 229, 0.4);
    }

    /* Toggle Switch */
    .toggle-switch {
        display: flex;
        align-items: center;
        cursor: pointer;
        gap: 12px;
    }

    .toggle-switch input { display: none; }

    .toggle-track {
        width: 50px;
        height: 28px;
        background: #cbd5e1;
        border-radius: 50px;
        position: relative;
        transition: all 0.3s;
    }

    .toggle-thumb {
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        position: absolute;
        top: 2px;
        left: 2px;
        transition: all 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .toggle-switch input:checked + .toggle-track {
        background: #10b981;
    }

    .toggle-switch input:checked + .toggle-track .toggle-thumb {
        transform: translateX(22px);
    }

    .toggle-label {
        font-weight: 600;
        color: #334155;
    }

    /* Sidebar Tips */
    .gradient-card {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        color: white;
    }

    .card-title-sm {
        font-size: 1.1rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 20px;
    }

    .card-title-white {
        font-size: 1.1rem;
        font-weight: 800;
        color: white;
        margin-bottom: 15px;
    }

    .tips-list {
        padding-left: 20px;
        margin: 0;
        list-style-type: disc;
    }

    .tips-list li {
        margin-bottom: 10px;
        color: #94a3b8;
        font-size: 0.95rem;
    }
    
    .text-danger-sm {
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 5px;
        display: block;
    }
    
    .helper-text {
        font-size: 0.85rem;
        color: #64748b;
        margin-top: 8px;
        text-align: right;
    }
</style>

<script>
    // Image Preview Logic
    const fileInput = document.getElementById('file-input');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');
    const placeholder = document.getElementById('placeholder');
    const removeBtn = document.getElementById('btn-remove');
    const dropZone = document.getElementById('drop-zone');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.style.display = 'block';
                placeholder.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
    });

    removeBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent accidental form submit
        e.stopPropagation(); // Stop click from triggering file input again
        fileInput.value = '';
        previewContainer.style.display = 'none';
        placeholder.style.display = 'block';
    });
    
    // Drag and Drop Effects
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if(files.length > 0) {
            fileInput.files = files;
            // Trigger change event to load preview
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    });
</script>
@endsection
