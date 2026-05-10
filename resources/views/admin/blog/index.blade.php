@extends('layouts.app')

@section('content')
@php
    $basePath  = rtrim(request()->getBaseUrl(), '/');
    $total     = $totalCount;
    $published = $publishedCount;
    $drafts    = $draftCount;
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Admin / CMS
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Blog CMS</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gerencie os artigos publicados na página inicial.</p>
    </div>
    <a href="{{ route('admin.blog.create') }}"
       class="btn btn-primary fw-bold rounded-3 d-flex align-items-center gap-2 flex-shrink-0"
       style="margin-top:4px;">
        <i class="fas fa-plus"></i> Novo Artigo
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-newspaper" style="color:#6366f1;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#4f46e5;line-height:1.1;">{{ $total }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">artigos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-circle-check" style="color:#10b981;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#059669;line-height:1.1;">{{ $published }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">publicados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(245,158,11,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-pen-to-square" style="color:#f59e0b;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#d97706;line-height:1.1;">{{ $drafts }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">rascunhos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-eye" style="color:#ef4444;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#dc2626;line-height:1.1;">{{ number_format($totalViews, 0, ',', '.') }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">
                views totais
                <span style="display:block;color:#10b981;font-weight:700;">+{{ number_format($viewsMonth, 0, ',', '.') }} este mês</span>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">

        @if($posts->isEmpty())
            <div class="text-center py-5 px-4">
                <div style="width:60px;height:60px;background:rgba(99,102,241,.1);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="fas fa-newspaper" style="font-size:1.4rem;color:#6366f1;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Nenhum artigo ainda</h6>
                <p class="text-muted small mb-3">Crie o primeiro post do blog da Vivensi.</p>
                <a href="{{ route('admin.blog.create') }}" class="btn btn-primary fw-bold rounded-3 px-4">
                    <i class="fas fa-plus me-2"></i> Novo Artigo
                </a>
            </div>
        @else
            <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                 style="background:#f8fafc;border-radius:1rem 1rem 0 0;">
                <span style="font-size:.78rem;font-weight:700;color:#64748b;">{{ $total }} artigo{{ $total !== 1 ? 's' : '' }}</span>
                <span style="font-size:.72rem;color:#94a3b8;">mais recentes primeiro</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th class="px-4 py-3 th-lbl">Artigo</th>
                            <th class="py-3 th-lbl">Status</th>
                            <th class="py-3 th-lbl">Publicação</th>
                            <th class="py-3 th-lbl text-center">Views</th>
                            <th class="pe-4 py-3 th-lbl text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                        <tr style="border-color:#f1f5f9;">
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    @if($post->image)
                                        <img src="{{ $post->image }}" alt=""
                                             style="width:52px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                                    @else
                                        <div style="width:52px;height:40px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i class="fas fa-image" style="color:#cbd5e1;font-size:.85rem;"></i>
                                        </div>
                                    @endif
                                    <div style="min-width:0;">
                                        <div class="fw-bold text-truncate" style="font-size:.88rem;color:#0f172a;max-width:320px;">{{ $post->title }}</div>
                                        <div style="font-size:.72rem;color:#94a3b8;">/blog/{{ $post->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                @if($post->is_published)
                                    <span class="status-pill pill-published"><i class="fas fa-circle" style="font-size:.45rem;"></i> Publicado</span>
                                @else
                                    <span class="status-pill pill-draft"><i class="fas fa-circle" style="font-size:.45rem;"></i> Rascunho</span>
                                @endif
                            </td>
                            <td class="py-3" style="font-size:.83rem;color:#64748b;white-space:nowrap;">
                                @if($post->published_at)
                                    {{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y H:i') }}
                                @else
                                    <span style="color:#cbd5e1;">—</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                <span style="font-size:.82rem;font-weight:800;color:{{ $post->view_count > 0 ? '#6366f1' : '#cbd5e1' }};">
                                    <i class="fas fa-eye me-1" style="font-size:.7rem;"></i>{{ number_format($post->view_count, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="pe-4 py-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.blog.edit', $post->id) }}"
                                       class="action-btn btn-edit" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('admin.blog.destroy', $post->id) }}" method="POST"
                                          onsubmit="return confirm('Excluir este artigo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn btn-del" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($posts->hasPages())
            <div class="px-4 py-3 border-top d-flex justify-content-center">
                {{ $posts->links() }}
            </div>
        @endif

    </div>
</div>

@push('styles')
<style>
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

.th-lbl {
    font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;
    font-weight:700;color:#94a3b8;border-bottom:none;
}

.status-pill {
    display:inline-flex;align-items:center;gap:5px;
    padding:3px 10px;border-radius:20px;
    font-size:.68rem;font-weight:700;white-space:nowrap;
}
.pill-published { background:#dcfce7;color:#166534; }
.pill-draft     { background:#f1f5f9;color:#64748b; }

.action-btn {
    width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.8rem;transition:background .15s,color .15s;
    text-decoration:none;
}
.btn-edit { background:#f1f5f9;color:#475569; }
.btn-edit:hover { background:#e0e7ff;color:#4f46e5; }
.btn-del  { background:#f1f5f9;color:#ef4444; }
.btn-del:hover  { background:#fee2e2;color:#dc2626; }
</style>
@endpush
@endsection
