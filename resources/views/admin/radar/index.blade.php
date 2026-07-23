@extends('layouts.app')

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:22px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;font-size:0.78rem;font-weight:700;text-decoration:none;">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Painel
            </a>
            <h2 style="margin:8px 0 3px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
                <i class="fas fa-satellite-dish me-2" style="color:#3b82f6;"></i>Radar de Editais — Curadoria
            </h2>
            <p style="color:#64748b;font-size:0.84rem;margin:0;">Achados coletados automaticamente. Aprovados ficam visíveis para as ONGs.</p>
        </div>
        <a href="{{ route('admin.radar.qualidade') }}"
           style="display:inline-flex;align-items:center;gap:8px;background:#f0fdf4;border:1px solid #6ee7b7;color:#065f46;padding:8px 18px;border-radius:10px;font-size:0.82rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-chart-bar"></i>Painel de Qualidade
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Tabs --}}
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
                <span style="background:{{ $meta['color'] }};color:#fff;border-radius:10px;padding:1px 7px;font-size:0.7rem;">{{ $counts[$key] }}</span>
            @endif
        </a>
        @endforeach
    </div>

    @if($findings->isEmpty())
        <div style="text-align:center;padding:60px 20px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;color:#94a3b8;">
            <i class="fas fa-satellite-dish" style="font-size:2.8rem;margin-bottom:12px;display:block;color:#c7d2fe;"></i>
            <p style="font-size:0.9rem;font-weight:700;color:#64748b;">Nenhum achado com este filtro.</p>
        </div>
    @else
        <div style="font-size:0.8rem;color:#64748b;font-weight:600;margin-bottom:14px;">
            {{ $findings->total() }} {{ $findings->total() === 1 ? 'achado' : 'achados' }}
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($findings as $finding)
            @php
                $statusColor = match($finding->status) {
                    'aprovado'  => '#10b981',
                    'rejeitado' => '#ef4444',
                    default     => '#f59e0b',
                };
                $sourceLabel = match($finding->source) {
                    'querido_diario' => 'Querido Diário',
                    'transferegov'   => 'Transferegov',
                    default          => $finding->source,
                };
            @endphp
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:13px;padding:16px 20px;border-left:4px solid {{ $statusColor }};">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="flex-grow-1" style="min-width:0;">

                        {{-- Status badges --}}
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span style="font-size:0.7rem;font-weight:700;padding:2px 9px;border-radius:10px;background:{{ $statusColor }}18;color:{{ $statusColor }};border:1px solid {{ $statusColor }}40;">
                                {{ strtoupper($finding->status) }}
                            </span>
                            @if($finding->auto_approved)
                            <span style="font-size:0.68rem;font-weight:700;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;border:1px solid #93c5fd;">
                                <i class="fas fa-magic me-1"></i>AUTO
                            </span>
                            @endif
                            <span style="font-size:0.7rem;font-weight:600;color:#6366f1;background:#ede9fe;padding:2px 8px;border-radius:8px;">{{ $sourceLabel }}</span>
                            @if($finding->keyword_matched)
                            <span style="font-size:0.7rem;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:8px;">
                                <i class="fas fa-tag me-1"></i>{{ $finding->keyword_matched }}
                            </span>
                            @endif
                            @if($finding->value_total)
                            <span style="font-size:0.7rem;font-weight:700;color:#0369a1;background:#e0f2fe;padding:2px 8px;border-radius:8px;">
                                R$ {{ number_format($finding->value_total, 0, ',', '.') }}
                            </span>
                            @endif
                            @if($finding->deadline)
                            <span style="font-size:0.7rem;color:#374151;background:#f8fafc;padding:2px 8px;border-radius:8px;">
                                <i class="fas fa-calendar-alt me-1"></i>{{ $finding->deadline->format('d/m/Y') }}
                            </span>
                            @endif
                        </div>

                        <h6 style="font-weight:800;margin:0 0 5px;font-size:0.92rem;color:#1e293b;line-height:1.35;">{{ $finding->title }}</h6>

                        {{-- IA block --}}
                        @if($finding->ai_processed_at)
                        <div style="background:linear-gradient(135deg,#f0fdf4,#f0f9ff);border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:8px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                                     style="width:20px;height:20px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                                <span style="font-weight:700;font-size:0.75rem;color:#166534;">Análise da IA</span>
                                @if($finding->is_relevant)
                                    <span style="font-size:0.68rem;background:#d1fae5;color:#065f46;padding:1px 7px;border-radius:8px;font-weight:700;">Relevante</span>
                                @else
                                    <span style="font-size:0.68rem;background:#fee2e2;color:#991b1b;padding:1px 7px;border-radius:8px;font-weight:700;">Irrelevante</span>
                                @endif
                            </div>
                            @if($finding->object_summary)
                                <p style="font-size:0.8rem;color:#374151;margin:0 0 6px;line-height:1.5;font-style:italic;">{{ $finding->object_summary }}</p>
                            @endif
                            @if($finding->areas)
                            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach($finding->areas as $area)
                                    <span style="background:#d1fae5;color:#065f46;padding:1px 7px;border-radius:8px;font-size:0.67rem;font-weight:600;">{{ $area }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @else
                        <p style="color:#64748b;font-size:0.8rem;margin-bottom:8px;line-height:1.5;">{{ Str::limit($finding->excerpt, 220) }}</p>
                        @endif

                        <div class="d-flex gap-3 flex-wrap" style="font-size:0.73rem;color:#94a3b8;">
                            <span><i class="fas fa-calendar me-1"></i>{{ $finding->published_at?->format('d/m/Y') ?? '—' }}</span>
                            @if($finding->territory_ibge)
                                <span><i class="fas fa-map-marker-alt me-1"></i>IBGE {{ $finding->territory_ibge }}</span>
                            @endif
                            @if($finding->curator)
                                <span><i class="fas fa-user-check me-1"></i>{{ $finding->curator->name }}</span>
                            @endif
                            <a href="{{ $finding->source_url }}" target="_blank" rel="noopener noreferrer"
                               style="color:#3b82f6;text-decoration:none;font-weight:700;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver fonte
                            </a>
                        </div>
                    </div>

                    @if($finding->status === 'novo')
                    <div class="d-flex flex-column gap-2 flex-shrink-0">
                        <form action="{{ route('admin.radar.aprovar', $finding->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:0.8rem;font-weight:700;cursor:pointer;white-space:nowrap;width:100%;">
                                <i class="fas fa-check me-1"></i>Aprovar
                            </button>
                        </form>
                        <form action="{{ route('admin.radar.rejeitar', $finding->id) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#ef4444;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:0.8rem;font-weight:700;cursor:pointer;white-space:nowrap;width:100%;">
                                <i class="fas fa-times me-1"></i>Rejeitar
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $findings->links() }}</div>
    @endif

</div>
@endsection
