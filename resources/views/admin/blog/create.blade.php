@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Criar Artigo</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Escreva conteúdo de valor para atrair novos clientes.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="vivensi-card" style="padding: 30px;">
            <form action="{{ route('admin.blog.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Título do Artigo</label>
                    <input type="text" name="title" class="form-control" placeholder="Ex: 5 Dicas para sua ONG captar mais recursos" required>
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">URL da Imagem (Capa)</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://exemplo.com/imagem.jpg">
                </div>

                <div class="mb-4">
                    <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Conteúdo (Markdown ou HTML)</label>
                    <textarea name="content" class="form-control" style="height: 400px;" required></textarea>
                </div>

                <div class="mb-4">
                    <label class="d-flex align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" style="width: 20px; height: 20px;">
                        <span style="font-weight: 700; color: #334155;">Publicar imediatamente</span>
                    </label>
                </div>

                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn-premium">Salvar Artigo</button>
                    <a href="{{ route('admin.blog.index') }}" class="btn btn-light" style="padding: 12px 25px; border-radius: 12px; font-weight: 700; border: 1px solid #e2e8f0;">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 25px; background: #f8fafc;">
            <h5 style="font-weight: 800; color: #1e293b; margin-bottom: 15px;">Dicas de SEO</h5>
            <ul style="padding-left: 20px; color: #64748b; font-size: 0.9rem; line-height: 1.6;">
                <li>Use palavras-chave no título.</li>
                <li>O conteúdo deve ter pelo menos 300 palavras.</li>
                <li>Adicione links internos para suas soluções.</li>
                <li>Use imagens chamativas.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
