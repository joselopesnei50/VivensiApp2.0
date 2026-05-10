@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Páginas Institucionais (CMS)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie o conteúdo de termos, privacidade e sobre nós.</p>
    </div>
</div>

{{-- Analytics cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-file-lines" style="color:#6366f1;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#4f46e5;line-height:1.1;">{{ $pages->count() }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">páginas</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-eye" style="color:#ef4444;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#dc2626;line-height:1.1;">{{ number_format($totalViews, 0, ',', '.') }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">views totais</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <div style="width:34px;height:34px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-chart-line" style="color:#10b981;font-size:.8rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.5rem;color:#059669;line-height:1.1;">{{ number_format($viewsMonth, 0, ',', '.') }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">views este mês</div>
        </div>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin-bottom: 0;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Página</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">URL Pública</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: center;">Views</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $page)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 20px 25px;">
                    <div style="font-weight: 700; color: #1e293b;">{{ $page->title }}</div>
                </td>
                <td style="padding: 20px 25px;">
                    <a href="{{ url('/pagina/' . $page->slug) }}" target="_blank"
                       style="font-size:.8rem; color:#6366f1; text-decoration:none;">
                        /pagina/{{ $page->slug }} <i class="fas fa-arrow-up-right-from-square ms-1" style="font-size:.65rem;"></i>
                    </a>
                </td>
                <td style="padding: 20px 25px; text-align: center;">
                    <span style="font-size:.85rem; font-weight:800; color:{{ $page->view_count > 0 ? '#6366f1' : '#cbd5e1' }};">
                        <i class="fas fa-eye me-1" style="font-size:.7rem;"></i>{{ number_format($page->view_count, 0, ',', '.') }}
                    </span>
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0;">
                        <i class="fas fa-edit me-2"></i> Editar
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
