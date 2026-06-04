@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp

<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <i class="fas fa-archive" style="color: #64748b;"></i>
            <h6 style="color: #64748b; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Arquivos do Portfólio</h6>
        </div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2rem; letter-spacing: -1px;">Projetos Arquivados</h2>
        <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1rem; font-weight: 500;">Projetos retirados da listagem ativa. Todo o histórico (transações, timeline, beneficiários) está preservado.</p>
    </div>
    <a href="{{ $basePath . '/projects' }}" class="btn-ds btn-ds-outline" style="padding: 10px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-arrow-left"></i> Voltar para Ativos
    </a>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('info'))
    <div style="background: #eff6ff; color: #1e40af; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #bfdbfe; font-weight: 700; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-info-circle"></i> {{ session('info') }}
    </div>
@endif

<div class="vivensi-card mb-3" style="padding: 16px 18px; border-radius: 18px;">
    <form method="GET" action="{{ $basePath . '/projects/archived' }}" style="display:flex; gap: 12px; align-items: end; flex-wrap: wrap;">
        <div style="position: relative; flex: 1; min-width: 280px;">
            <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Busca</label>
            <i class="fas fa-search" style="position:absolute; left: 14px; top: 38px; color:#94a3b8;"></i>
            <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Buscar por nome ou descrição..."
                   style="width: 100%; padding: 10px 12px 10px 38px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 700; color:#0f172a;">
        </div>
        <button type="submit" class="btn-ds btn-ds-primary" style="padding: 10px 18px; font-weight: 800;">
            <i class="fas fa-filter"></i> Filtrar
        </button>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    @if($projects->isEmpty())
        <div style="padding: 48px 24px; text-align: center; color: #64748b;">
            <i class="fas fa-archive" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px;"></i>
            <p style="margin: 0; font-weight: 700;">Nenhum projeto arquivado.</p>
            <p style="margin: 6px 0 0 0; font-size: 0.9rem;">Quando você arquivar um projeto, ele aparece aqui.</p>
        </div>
    @else
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 14px 18px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Nome do Projeto</th>
                    <th style="padding: 14px 18px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Status na Época</th>
                    <th style="padding: 14px 18px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Arquivado em</th>
                    <th style="padding: 14px 18px; text-align: right; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($projects as $project)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 16px 18px;">
                            <a href="{{ $basePath . '/projects/details/' . $project->id }}" style="color: #4338ca; font-weight: 800; text-decoration: none;">{{ $project->name }}</a>
                            @if($project->description)
                                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 4px;">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</div>
                            @endif
                        </td>
                        <td style="padding: 16px 18px;">
                            <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 999px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">{{ $project->status ?? '—' }}</span>
                        </td>
                        <td style="padding: 16px 18px; color: #475569; font-size: 0.9rem;">
                            {{ optional($project->archived_at)->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td style="padding: 16px 18px; text-align: right;">
                            <div style="display: inline-flex; gap: 8px;">
                                <a href="{{ $basePath . '/projects/details/' . $project->id }}" class="btn-premium" title="Ver detalhes" aria-label="Ver detalhes do projeto arquivado" style="padding: 6px 10px !important; font-size: 0.85rem !important; background: #475569 !important; color: #ffffff !important; box-shadow: none !important;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" action="{{ url('/projects/' . $project->id . '/unarchive') }}" onsubmit="return confirm('Reativar este projeto?');" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-premium" title="Reativar" aria-label="Reativar projeto" style="padding: 6px 12px !important; font-size: 0.8rem !important; background: #10b981 !important; color: #ffffff !important; box-shadow: none !important;">
                                        <i class="fas fa-box-open"></i> Reativar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if($projects->hasPages())
    <div style="margin-top: 24px;">
        {{ $projects->links() }}
    </div>
@endif

@endsection
