@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Editar Artigo</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Atualize o conteúdo do artigo do blog.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 30px;">
    <form action="{{ route('admin.blog.update', $post->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Título do Artigo</label>
                <input type="text" name="title" value="{{ old('title', $post->title) }}" class="form-control" placeholder="Ex: 5 dicas para gerir sua ONG" required>
            </div>

            <div class="col-md-12 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">URL da Imagem de Capa</label>
                <input type="url" name="image_url" value="{{ old('image_url', $post->image) }}" class="form-control" placeholder="https://images.unsplash.com/...">
                <small class="text-muted">Links do Unsplash ou similares funcionam melhor.</small>
            </div>

            <div class="col-md-12 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Conteúdo</label>
                <textarea name="content" class="form-control" style="height: 400px;" required>{{ old('content', $post->content) }}</textarea>
            </div>

            <div class="col-md-12 mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_published" id="is_published" {{ $post->is_published ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_published">Publicar Artigo</label>
                </div>
                <small class="text-muted">Artigos publicados aparecem na página inicial.</small>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 20px;">
            <button type="submit" class="btn-premium">Salvar Alterações</button>
            <a href="{{ route('admin.blog.index') }}" class="btn btn-light" style="padding: 12px 25px; border-radius: 12px; font-weight: 700; border: 1px solid #e2e8f0;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
