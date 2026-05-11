@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Páginas Institucionais (CMS)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie o conteúdo de termos, privacidade e sobre nós.</p>
    </div>
</div>

{{-- Analytics cards --}}
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-white">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                <i class="fas fa-file-lines" style="color:#6366f1;font-size:1.2rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.8rem;color:#1e293b;line-height:1.1;">{{ $pages->count() }}</div>
            <div style="font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-top:5px;font-weight:700;">páginas ativas</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-white">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                <i class="fas fa-eye" style="color:#ef4444;font-size:1.2rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.8rem;color:#1e293b;line-height:1.1;">{{ number_format($totalViews, 0, ',', '.') }}</div>
            <div style="font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-top:5px;font-weight:700;">views acumuladas</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-white">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                <i class="fas fa-chart-line" style="color:#10b981;font-size:1.2rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.8rem;color:#1e293b;line-height:1.1;">{{ number_format($viewsMonth, 0, ',', '.') }}</div>
            <div style="font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;margin-top:5px;font-weight:700;">views este mês</div>
        </div>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.04);">
    <div style="padding: 25px; background: #fff; border-bottom: 1px solid #f1f5f9;">
        <h5 class="fw-800 mb-0" style="font-size: 1rem; color: #1e293b;">Listagem de Conteúdo</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle" style="margin-bottom: 0;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 18px 30px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em;">Página Institucional</th>
                    <th style="padding: 18px 30px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em;">Status & URL</th>
                    <th style="padding: 18px 30px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; text-align: center;">Desempenho</th>
                    <th style="padding: 18px 30px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; text-align: right;">Gerenciamento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                <tr>
                    <td style="padding: 25px 30px;">
                        <div class="d-flex align-items-center">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                <i class="fas fa-file text-muted"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #1e293b; font-size: 1rem;">{{ $page->title }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">Editado {{ $page->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 25px 30px;">
                        <div class="mb-1">
                            <span class="badge" style="background: rgba(16,185,129,0.1); color: #10b981; font-weight: 700; font-size: 0.65rem; text-transform: uppercase; padding: 4px 10px; border-radius: 100px;">Publicado</span>
                        </div>
                        <a href="{{ url('/pagina/' . $page->slug) }}" target="_blank" class="text-decoration-none" style="font-size:.8rem; color:#6366f1; font-weight: 600;">
                            /pagina/{{ $page->slug }} <i class="fas fa-external-link-alt ms-1" style="font-size:.6rem; opacity: 0.7;"></i>
                        </a>
                    </td>
                    <td style="padding: 25px 30px; text-align: center;">
                        <div style="background: #f8fafc; padding: 10px; border-radius: 12px; display: inline-block; min-width: 100px;">
                            <div style="font-size:.9rem; font-weight:800; color:#1e293b;">
                                {{ number_format($page->view_count, 0, ',', '.') }}
                            </div>
                            <div style="font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; font-weight: 700;">visualizações</div>
                        </div>
                    </td>
                    <td style="padding: 25px 30px; text-align: right;">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn btn-light" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 15px; font-weight: 700; font-size: 0.85rem; transition: all 0.2s;">
                                <i class="fas fa-pen-nib me-2 text-primary"></i> Editar Conteúdo
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
    .btn-light:hover {
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        transform: translateY(-2px);
    }
    tr:hover {
        background: #fafafa;
    }
</style>
@endsection
