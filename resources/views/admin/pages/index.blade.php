@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Páginas Institucionais (CMS)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie o conteúdo de termos, privacidade e sobre nós.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin-bottom: 0;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Página</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Slug</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $page)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 20px 25px;">
                    <div style="font-weight: 700; color: #1e293b;">{{ $page->title }}</div>
                </td>
                <td style="padding: 20px 25px; color: #64748b;">
                    {{ $page->slug }}
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0;"><i class="fas fa-edit me-2"></i> Editar Conteúdo</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
