@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Editar Página: {{ $page->title }}</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Atualize o conteúdo oficial desta página.</p>
    </div>
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

    <form action="{{ route('admin.pages.update', $page->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-4">
            <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Título</label>
            <input type="text" name="title" value="{{ $page->title }}" class="form-control" required>
        </div>

        <div class="mb-4">
            <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Slug (URL da página)</label>
            <input type="text" name="slug" value="{{ $page->slug }}" class="form-control" required>
            <small class="text-muted">Ex: termos (que vira /pagina/termos)</small>
        </div>

        <div class="mb-4">
            <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Conteúdo da Página</label>
            <textarea name="content" class="form-control" style="height: 600px;" required>{{ $page->content }}</textarea>
            <small class="text-muted">Você pode usar quebras de linha normais.</small>
        </div>

        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn-premium">Salvar Alterações</button>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-light" style="padding: 12px 25px; border-radius: 12px; font-weight: 700; border: 1px solid #e2e8f0;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
