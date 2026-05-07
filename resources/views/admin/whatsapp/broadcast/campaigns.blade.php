@extends('layouts.app')
@section('title', 'Histórico de Campanhas — Disparo em Massa')

@section('content')
@php
    $statusMap = [
        'scheduled'  => ['bg' => 'rgba(99,102,241,.1)',   'color' => '#4f46e5', 'label' => 'Agendado',     'icon' => 'clock'],
        'processing' => ['bg' => 'rgba(245,158,11,.1)',   'color' => '#d97706', 'label' => 'Processando',  'icon' => 'spinner'],
        'completed'  => ['bg' => 'rgba(16,185,129,.1)',   'color' => '#059669', 'label' => 'Concluído',    'icon' => 'circle-check'],
        'failed'     => ['bg' => 'rgba(239,68,68,.1)',    'color' => '#dc2626', 'label' => 'Falhou',       'icon' => 'circle-xmark'],
    ];
    $audienceLabels  = ['all' => 'Todos', 'selected' => 'Específicos', 'groups' => 'Grupos'];
    $audienceColors  = [
        'all'      => ['bg' => 'rgba(99,102,241,.1)',  'color' => '#4338ca'],
        'selected' => ['bg' => 'rgba(16,185,129,.1)',  'color' => '#166534'],
        'groups'   => ['bg' => 'rgba(245,158,11,.1)',  'color' => '#92400e'],
    ];
@endphp

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
        <a href="{{ route('whatsapp.broadcast.index') }}"
           class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center gap-1 flex-shrink-0"
           style="padding:6px 14px;font-size:.82rem;margin-top:6px;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <div class="flex-1">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
                WhatsApp / Disparo em Massa / Histórico
            </div>
            <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Histórico de Campanhas</h2>
        </div>
        <a href="{{ route('whatsapp.broadcast.index') }}"
           class="btn btn-primary fw-bold rounded-3 d-flex align-items-center gap-2 flex-shrink-0"
           style="margin-top:4px;">
            <i class="fas fa-rocket"></i> Nova Campanha
        </a>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-rocket" style="color:#6366f1;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#4f46e5;line-height:1.1;">{{ number_format($campaigns->total()) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">campanhas</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-paper-plane" style="color:#10b981;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#059669;line-height:1.1;">{{ number_format($totalSent) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">mensagens enviadas</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-circle-xmark" style="color:#ef4444;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#dc2626;line-height:1.1;">{{ number_format($totalFailed) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">falhas</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-chart-pie" style="color:#6366f1;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;line-height:1.1;color:{{ $successRate >= 80 ? '#059669' : ($successRate >= 50 ? '#d97706' : '#dc2626') }};">
                    {{ $successRate }}%
                </div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">taxa de sucesso</div>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            @if($campaigns->isEmpty())
                <div class="text-center py-5 px-4">
                    <div style="width:60px;height:60px;background:linear-gradient(135deg,rgba(79,70,229,.15),rgba(124,58,237,.15));border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="fas fa-rocket" style="font-size:1.4rem;color:#6366f1;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Nenhuma campanha disparada ainda</h6>
                    <p class="text-muted small mb-4" style="max-width:320px;margin-inline:auto;">
                        Crie sua primeira campanha de disparo em massa para ver o histórico aqui.
                    </p>
                    <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-primary rounded-3 fw-bold px-4">
                        <i class="fas fa-rocket me-2"></i> Criar campanha
                    </a>
                </div>
            @else
                {{-- Table meta bar --}}
                <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                     style="background:#f8fafc;border-radius:1rem 1rem 0 0;">
                    <span style="font-size:.78rem;font-weight:700;color:#64748b;">
                        {{ number_format($campaigns->total()) }} campanha{{ $campaigns->total() !== 1 ? 's' : '' }}
                        @if($campaigns->hasPages())
                            — página {{ $campaigns->currentPage() }} de {{ $campaigns->lastPage() }}
                        @endif
                    </span>
                    @if($campaigns->hasPages())
                        <span style="font-size:.72rem;color:#94a3b8;">25 por página</span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 campaign-table">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th class="px-4 py-3 th-lbl">Data</th>
                                <th class="py-3 th-lbl">Mensagem</th>
                                <th class="py-3 th-lbl">Público</th>
                                <th class="py-3 th-lbl">Cadência</th>
                                <th class="py-3 th-lbl">Status</th>
                                <th class="py-3 th-lbl">Enviados</th>
                                <th class="pe-4 py-3 th-lbl">Taxa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                            @php
                                $total    = $campaign->total_sent + $campaign->total_failed;
                                $rate     = $total > 0 ? round($campaign->total_sent / $total * 100) : 0;
                                $s        = $statusMap[$campaign->status] ?? ['bg' => 'rgba(100,116,139,.1)', 'color' => '#475569', 'label' => $campaign->status, 'icon' => 'circle'];
                                $aud      = $audienceColors[$campaign->audience_type] ?? $audienceColors['all'];
                                $audLabel = $audienceLabels[$campaign->audience_type] ?? $campaign->audience_type;
                            @endphp
                            <tr>
                                {{-- Date --}}
                                <td class="px-4 py-3" style="white-space:nowrap;">
                                    <div style="font-size:.83rem;font-weight:600;color:#1e293b;">
                                        {{ $campaign->created_at->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size:.72rem;color:#94a3b8;">
                                        {{ $campaign->created_at->format('H:i') }}
                                    </div>
                                </td>

                                {{-- Message --}}
                                <td class="py-3" style="max-width:280px;">
                                    @if($campaign->has_image)
                                        <span class="img-badge">
                                            <i class="fas fa-image"></i> Imagem
                                        </span>
                                    @endif
                                    @if($campaign->message)
                                        <div class="campaign-msg" title="{{ $campaign->message }}">
                                            {{ $campaign->message }}
                                        </div>
                                    @else
                                        <span style="font-size:.78rem;color:#94a3b8;font-style:italic;">(apenas imagem)</span>
                                    @endif
                                </td>

                                {{-- Audience --}}
                                <td class="py-3">
                                    <span class="aud-badge" style="background:{{ $aud['bg'] }};color:{{ $aud['color'] }};">
                                        {{ $audLabel }}
                                    </span>
                                </td>

                                {{-- Cadence --}}
                                <td class="py-3">
                                    <span style="font-size:.78rem;color:#64748b;white-space:nowrap;">
                                        <i class="fas fa-stopwatch me-1" style="font-size:.65rem;"></i>
                                        {{ $campaign->cadence }}s
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="py-3">
                                    <span class="status-pill" style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                                        <i class="fas fa-{{ $s['icon'] }} {{ $campaign->status === 'processing' ? 'fa-spin' : '' }}"></i>
                                        {{ $s['label'] }}
                                    </span>
                                    @if($campaign->status === 'scheduled' && $campaign->scheduled_at)
                                        <div style="font-size:.65rem;color:#94a3b8;margin-top:3px;white-space:nowrap;">
                                            <i class="fas fa-calendar me-1"></i>{{ $campaign->scheduled_at->format('d/m H:i') }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Sent / Failed --}}
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-weight:700;font-size:.85rem;color:#059669;">{{ number_format($campaign->total_sent) }}</span>
                                        <span style="color:#e2e8f0;font-size:.8rem;">/</span>
                                        <span style="font-weight:700;font-size:.85rem;color:{{ $campaign->total_failed > 0 ? '#dc2626' : '#94a3b8' }};">{{ number_format($campaign->total_failed) }}</span>
                                    </div>
                                    <div style="font-size:.65rem;color:#94a3b8;margin-top:1px;">sucesso / falha</div>
                                </td>

                                {{-- Success rate bar --}}
                                <td class="pe-4 py-3">
                                    @if($total > 0)
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="flex:1;background:#f1f5f9;border-radius:99px;height:5px;min-width:52px;">
                                                <div style="width:{{ $rate }}%;background:{{ $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#f59e0b' : '#ef4444') }};height:5px;border-radius:99px;"></div>
                                            </div>
                                            <span style="font-size:.78rem;font-weight:700;color:#475569;min-width:30px;">{{ $rate }}%</span>
                                        </div>
                                    @else
                                        <span style="font-size:.75rem;color:#94a3b8;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                    <div class="px-4 py-3 border-top" style="background:#fafafa;border-radius:0 0 1rem 1rem;">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>

<style>
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

.th-lbl {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 700;
    color: #94a3b8;
    border-bottom: none;
}

.campaign-table tbody tr { border-color: #f1f5f9; }
.campaign-table tbody tr:hover td { background: #f8faff; }

.campaign-msg {
    font-size: .8rem;
    color: #334155;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
    margin-top: 3px;
}

.img-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: .65rem;
    font-weight: 700;
    background: rgba(99,102,241,.1);
    color: #4f46e5;
    letter-spacing: .02em;
}

.aud-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .02em;
    white-space: nowrap;
}
</style>
@endsection
