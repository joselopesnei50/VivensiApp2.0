@extends('layouts.app')

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;font-size:0.8rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Painel
        </a>
        <h2 style="margin:10px 0 4px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
            <i class="fas fa-satellite-dish me-2" style="color:#3b82f6;"></i>Radar de Editais — Curadoria
        </h2>
        <p style="color:#64748b;font-size:0.85rem;">Revise os achados coletados automaticamente. Aprovados ficam visíveis para as ONGs.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabs de status --}}
    <div class="d-flex gap-2 mb-4 flex-wrap">
        @foreach([
            'novo'      => ['label' => 'Novos',      'color' => '#f59e0b', 'bg' => '#fef3c7'],
            'aprovado'  => ['label' => 'Aprovados',  'color' => '#10b981', 'bg' => '#d1fae5'],
            'rejeitado' => ['label' => 'Rejeitados', 'color' => '#ef4444', 'bg' => '#fee2e2'],
            'todos'     => ['label' => 'Todos',      'color' => '#6366f1', 'bg' => '#ede9fe'],
        ] as $key => $meta)
        @php $ativo = $status === $key; @endphp
        <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => 1]) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:20px;font-size:0.82rem;font-weight:700;text-decoration:none;border:2px solid {{ $ativo ? $meta['color'] : '#e2e8f0' }};background:{{ $ativo ? $meta['bg'] : '#f8fafc' }};color:{{ $ativo ? $meta['color'] : '#64748b' }};">
            {{ $meta['label'] }}
            @if($key !== 'todos' && isset($counts[$key]))
                <span style="background:{{ $meta['color'] }};color:#fff;border-radius:10px;padding:1px 7px;font-size:0.72rem;">{{ $counts[$key] }}</span>
            @endif
        </a>
        @endforeach
    </div>

    @if($findings->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-satellite-dish" style="font-size:3rem;margin-bottom:12px;display:block;opacity:.3;"></i>
            <p style="font-size:0.9rem;">Nenhum achado encontrado com este filtro.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($findings as $finding)
            @php
                $statusColor = match($finding->status) {
                    'aprovado'  => '#10b981',
                    'rejeitado' => '#ef4444',
                    default     => '#f59e0b',
                };
                $statusBg = match($finding->status) {
                    'aprovado'  => '#d1fae5',
                    'rejeitado' => '#fee2e2',
                    default     => '#fef3c7',
                };
                $sourceLabel = match($finding->source) {
                    'querido_diario' => 'Querido Diário',
                    'transferegov'   => 'Transferegov',
                    default          => $finding->source,
                };
            @endphp
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;border-left:4px solid {{ $statusColor }};">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span style="font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:10px;background:{{ $statusBg }};color:{{ $statusColor }};">
                                {{ strtoupper($finding->status) }}
                            </span>
                            <span style="font-size:0.72rem;font-weight:600;color:#6366f1;background:#ede9fe;padding:2px 8px;border-radius:10px;">
                                {{ $sourceLabel }}
                            </span>
                            @if($finding->keyword_matched)
                            <span style="font-size:0.72rem;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:10px;">
                                <i class="fas fa-tag me-1"></i>{{ $finding->keyword_matched }}
                            </span>
                            @endif
                        </div>
                        <h6 style="font-weight:800;margin:4px 0 6px;font-size:0.9rem;color:#1e293b;line-height:1.3;">
                            {{ $finding->title }}
                        </h6>
                        <p style="color:#64748b;font-size:0.8rem;margin-bottom:6px;line-height:1.5;">
                            {{ Str::limit($finding->excerpt, 220) }}
                        </p>
                        <div class="d-flex gap-3 flex-wrap" style="font-size:0.75rem;color:#94a3b8;">
                            <span><i class="fas fa-calendar me-1"></i>{{ $finding->published_at ? $finding->published_at->format('d/m/Y') : '—' }}</span>
                            @if($finding->territory_ibge)
                                <span><i class="fas fa-map-marker-alt me-1"></i>IBGE {{ $finding->territory_ibge }}</span>
                            @endif
                            @if($finding->curator)
                                <span><i class="fas fa-user-check me-1"></i>{{ $finding->curator->name }}</span>
                            @endif
                            <a href="{{ $finding->source_url }}" target="_blank" rel="noopener noreferrer"
                               style="color:#3b82f6;text-decoration:none;font-weight:600;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver fonte
                            </a>
                        </div>
                    </div>

                    @if($finding->status === 'novo')
                    <div class="d-flex gap-2 flex-shrink-0">
                        <form action="{{ route('admin.radar.aprovar', $finding->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:6px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                                <i class="fas fa-check me-1"></i>Aprovar
                            </button>
                        </form>
                        <form action="{{ route('admin.radar.rejeitar', $finding->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#ef4444;color:#fff;border:none;border-radius:8px;padding:6px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                                <i class="fas fa-times me-1"></i>Rejeitar
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $findings->links() }}
        </div>
    @endif

</div>
@endsection
