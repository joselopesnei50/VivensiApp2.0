@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Blog CMS</h2>
            <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie os artigos publicados na página inicial.</p>
        </div>
        <a href="{{ route('admin.blog.create') }}" class="btn-premium">
            <i class="fas fa-plus me-2"></i> Novo Artigo
        </a>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin-bottom: 0;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Título</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Status</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Data de Publicação</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 20px 25px;">
                    <div style="font-weight: 700; color: #1e293b;">{{ $post->title }}</div>
                    <div style="font-size: 0.75rem; color: #94a3b8;">/blog/{{ $post->slug }}</div>
                </td>
                <td style="padding: 20px 25px;">
                    @if($post->is_published)
                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">Publicado</span>
                    @else
                        <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">Rascunho</span>
                    @endif
                </td>
                <td style="padding: 20px 25px; color: #64748b; font-size: 0.85rem;">
                    {{ $post->published_at ? $post->published_at : 'Não publicado' }}
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <a href="{{ route('admin.blog.edit', $post->id) }}" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0;"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('admin.blog.destroy', $post->id) }}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0; color: #ef4444;" onclick="return confirm('Tem certeza?')"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
