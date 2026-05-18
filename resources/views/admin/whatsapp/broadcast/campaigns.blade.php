@extends('layouts.app')
@section('title', 'Relatório de Campanhas — Disparo em Massa')

@section('content')
@php
    $statusMap = [
        'scheduled'  => ['bg' => 'rgba(99,102,241,.12)',  'color' => '#4f46e5', 'label' => 'Agendado',    'icon' => 'clock'],
        'processing' => ['bg' => 'rgba(245,158,11,.12)',  'color' => '#d97706', 'label' => 'Processando', 'icon' => 'spinner'],
        'completed'  => ['bg' => 'rgba(16,185,129,.12)',  'color' => '#059669', 'label' => 'Concluído',   'icon' => 'circle-check'],
        'failed'     => ['bg' => 'rgba(239,68,68,.12)',   'color' => '#dc2626', 'label' => 'Falhou',      'icon' => 'circle-xmark'],
    ];
    $audienceLabels = ['all' => 'Todos os Contatos', 'selected' => 'Números Específicos', 'groups' => 'Grupos'];
    $audienceColors = [
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
            <div class="breadcrumb-trail">
                WhatsApp &rsaquo; Disparo em Massa &rsaquo; Relatório
            </div>
            <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">
                Relatório de Campanhas
            </h2>
        </div>
        <a href="{{ route('whatsapp.broadcast.index') }}"
           class="btn btn-primary fw-bold rounded-3 d-flex align-items-center gap-2 flex-shrink-0"
           style="margin-top:4px;">
            <i class="fas fa-rocket"></i> Nova Campanha
        </a>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(99,102,241,.1);">
                    <i class="fas fa-rocket" style="color:#6366f1;"></i>
                </div>
                <div class="kpi-value" style="color:#4f46e5;">{{ number_format($campaigns->total()) }}</div>
                <div class="kpi-label">Campanhas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(16,185,129,.1);">
                    <i class="fas fa-paper-plane" style="color:#10b981;"></i>
                </div>
                <div class="kpi-value" style="color:#059669;">{{ number_format($totalSent) }}</div>
                <div class="kpi-label">Mensagens Enviadas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(239,68,68,.1);">
                    <i class="fas fa-circle-xmark" style="color:#ef4444;"></i>
                </div>
                <div class="kpi-value" style="color:#dc2626;">{{ number_format($totalFailed) }}</div>
                <div class="kpi-label">Falhas de Entrega</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:rgba(99,102,241,.1);">
                    <i class="fas fa-chart-pie" style="color:#6366f1;"></i>
                </div>
                <div class="kpi-value" style="color:{{ $successRate >= 80 ? '#059669' : ($successRate >= 50 ? '#d97706' : '#dc2626') }};">
                    {{ $successRate }}%
                </div>
                <div class="kpi-label">Taxa de Sucesso</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-3 px-4">
            <form method="GET" action="{{ route('whatsapp.broadcast.campaigns') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="filter-label">Status</label>
                    <select name="status" class="form-select form-select-sm filter-select">
                        <option value="">Todos</option>
                        <option value="completed"  {{ request('status') === 'completed'  ? 'selected' : '' }}>Concluído</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processando</option>
                        <option value="scheduled"  {{ request('status') === 'scheduled'  ? 'selected' : '' }}>Agendado</option>
                        <option value="failed"     {{ request('status') === 'failed'     ? 'selected' : '' }}>Falhou</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Público</label>
                    <select name="audience" class="form-select form-select-sm filter-select">
                        <option value="">Todos</option>
                        <option value="all"      {{ request('audience') === 'all'      ? 'selected' : '' }}>Todos os Contatos</option>
                        <option value="selected" {{ request('audience') === 'selected' ? 'selected' : '' }}>Números Específicos</option>
                        <option value="groups"   {{ request('audience') === 'groups'   ? 'selected' : '' }}>Grupos</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label">De</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm filter-select">
                </div>
                <div>
                    <label class="filter-label">Até</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm filter-select">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary rounded-3 fw-600" style="padding:5px 16px;">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['status','audience','from','to']))
                        <a href="{{ route('whatsapp.broadcast.campaigns') }}" class="btn btn-sm btn-outline-secondary rounded-3" style="padding:5px 12px;">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            @if($campaigns->isEmpty())
                <div class="text-center py-5 px-4">
                    <div style="width:60px;height:60px;background:linear-gradient(135deg,rgba(79,70,229,.15),rgba(124,58,237,.15));border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="fas fa-rocket" style="font-size:1.4rem;color:#6366f1;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Nenhuma campanha encontrada</h6>
                    <p class="text-muted small mb-4" style="max-width:320px;margin-inline:auto;">
                        @if(request()->hasAny(['status','audience','from','to']))
                            Tente ajustar os filtros acima.
                        @else
                            Crie sua primeira campanha de disparo em massa.
                        @endif
                    </p>
                    <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-primary rounded-3 fw-bold px-4">
                        <i class="fas fa-rocket me-2"></i> Nova Campanha
                    </a>
                </div>
            @else
                <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                     style="background:#f8fafc;border-radius:1rem 1rem 0 0;">
                    <span style="font-size:.78rem;font-weight:700;color:#64748b;">
                        {{ number_format($campaigns->total()) }} campanha{{ $campaigns->total() !== 1 ? 's' : '' }}
                        @if($campaigns->hasPages()) — página {{ $campaigns->currentPage() }} de {{ $campaigns->lastPage() }} @endif
                    </span>
                    <span style="font-size:.72rem;color:#94a3b8;">25 por página</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 campaign-table">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th class="px-4 py-3 th-lbl">Data / Hora</th>
                                <th class="py-3 th-lbl">Mensagem</th>
                                <th class="py-3 th-lbl">Público</th>
                                <th class="py-3 th-lbl">Destinatários</th>
                                <th class="py-3 th-lbl">Duração</th>
                                <th class="py-3 th-lbl">Status</th>
                                <th class="py-3 th-lbl">Enviados / Falhas</th>
                                <th class="pe-4 py-3 th-lbl">Taxa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                            @php
                                $total    = $campaign->total_sent + $campaign->total_failed;
                                $rate     = $total > 0 ? round($campaign->total_sent / $total * 100) : 0;
                                $s        = $statusMap[$campaign->status] ?? ['bg'=>'rgba(100,116,139,.1)','color'=>'#475569','label'=>$campaign->status,'icon'=>'circle'];
                                $aud      = $audienceColors[$campaign->audience_type] ?? $audienceColors['all'];
                                $audLabel = $audienceLabels[$campaign->audience_type] ?? $campaign->audience_type;
                                $isGroups = $campaign->audience_type === 'groups';
                                $isMembers = $isGroups && $campaign->group_send_mode === 'members';
                                $duration = $campaign->duration;
                            @endphp
                            <tr>
                                {{-- Data --}}
                                <td class="px-4 py-3" style="white-space:nowrap;">
                                    <div style="font-size:.83rem;font-weight:600;color:#1e293b;">
                                        {{ $campaign->created_at->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size:.72rem;color:#94a3b8;">
                                        {{ $campaign->created_at->format('H:i') }}
                                        @if($campaign->scheduled_at && $campaign->status === 'scheduled')
                                            <span class="ms-1" style="color:#4f46e5;">
                                                <i class="fas fa-clock"></i> {{ $campaign->scheduled_at->format('d/m H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Mensagem --}}
                                <td class="py-3" style="max-width:240px;">
                                    @if($campaign->has_image)
                                        <span class="badge-mini" style="background:rgba(99,102,241,.1);color:#4f46e5;">
                                            <i class="fas fa-image"></i> Imagem
                                        </span>
                                    @endif
                                    @if($campaign->message)
                                        <div class="campaign-msg">{{ $campaign->message }}</div>
                                    @else
                                        <span style="font-size:.78rem;color:#94a3b8;font-style:italic;">(apenas imagem)</span>
                                    @endif
                                </td>

                                {{-- Público + Modo --}}
                                <td class="py-3" style="min-width:140px;">
                                    <span class="aud-badge" style="background:{{ $aud['bg'] }};color:{{ $aud['color'] }};">
                                        {{ $audLabel }}
                                    </span>
                                    @if($isGroups)
                                        <div class="mt-1">
                                            @if($isMembers)
                                                <span class="badge-mini" style="background:rgba(16,185,129,.1);color:#059669;">
                                                    <i class="fas fa-user-check"></i> Individual p/ membros
                                                </span>
                                            @else
                                                <span class="badge-mini" style="background:rgba(245,158,11,.1);color:#92400e;">
                                                    <i class="fas fa-people-group"></i> Mensagem no grupo
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Destinatários --}}
                                <td class="py-3" style="white-space:nowrap;">
                                    @if($campaign->actual_recipients !== null)
                                        <div style="font-size:.85rem;font-weight:700;color:#334155;">
                                            {{ number_format($campaign->actual_recipients) }}
                                        </div>
                                        <div style="font-size:.68rem;color:#94a3b8;">destinatários</div>
                                    @else
                                        <span style="color:#94a3b8;font-size:.8rem;">—</span>
                                    @endif
                                </td>

                                {{-- Duração --}}
                                <td class="py-3" style="white-space:nowrap;">
                                    @if($duration)
                                        <div style="font-size:.82rem;font-weight:600;color:#475569;">
                                            <i class="fas fa-stopwatch me-1" style="font-size:.7rem;color:#94a3b8;"></i>
                                            {{ $duration }}
                                        </div>
                                        <div style="font-size:.68rem;color:#94a3b8;">{{ $campaign->cadence }}s cadência</div>
                                    @else
                                        <span style="font-size:.72rem;color:#94a3b8;">{{ $campaign->cadence }}s</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3">
                                    <span class="status-pill" style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                                        <i class="fas fa-{{ $s['icon'] }} {{ $campaign->status === 'processing' ? 'fa-spin' : '' }}"></i>
                                        {{ $s['label'] }}
                                    </span>
                                </td>

                                {{-- Enviados / Falhas --}}
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="metric-num text-success">{{ number_format($campaign->total_sent) }}</span>
                                        <span style="color:#e2e8f0;">/</span>
                                        <span class="metric-num" style="color:{{ $campaign->total_failed > 0 ? '#dc2626' : '#94a3b8' }};">
                                            {{ number_format($campaign->total_failed) }}
                                        </span>
                                    </div>
                                    <div style="font-size:.65rem;color:#94a3b8;margin-top:1px;">enviados / falhas</div>
                                </td>

                                {{-- Taxa --}}
                                <td class="pe-4 py-3" style="min-width:90px;">
                                    @if($total > 0)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rate-bar-bg">
                                                <div class="rate-bar-fill" style="width:{{ $rate }}%;background:{{ $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#f59e0b' : '#ef4444') }};"></div>
                                            </div>
                                            <span style="font-size:.78rem;font-weight:800;color:{{ $rate >= 90 ? '#059669' : ($rate >= 70 ? '#d97706' : '#dc2626') }};min-width:34px;">
                                                {{ $rate }}%
                                            </span>
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
.fw-800  { font-weight:800; }
.fw-600  { font-weight:600; }
.flex-1  { flex:1; }

.breadcrumb-trail {
    font-size:.68rem;color:#94a3b8;text-transform:uppercase;
    letter-spacing:1.5px;font-weight:600;margin-bottom:4px;
}

.kpi-card {
    background:#fff;border-radius:16px;border:1px solid #e2e8f0;
    padding:18px 16px;text-align:center;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
    height:100%;
}
.kpi-icon {
    width:38px;height:38px;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    margin:0 auto 10px;font-size:.9rem;
}
.kpi-value { font-size:1.7rem;font-weight:800;line-height:1.1; }
.kpi-label { font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:3px; }

.filter-label { font-size:.72rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px; }
.filter-select { border-radius:8px;font-size:.83rem;min-width:140px; }

.th-lbl {
    font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;
    font-weight:700;color:#94a3b8;border-bottom:none;
}

.campaign-table tbody tr { border-color:#f1f5f9; }
.campaign-table tbody tr:hover td { background:#f8faff; }

.campaign-msg {
    font-size:.8rem;color:#334155;
    display:-webkit-box;-webkit-line-clamp:2;
    -webkit-box-orient:vertical;overflow:hidden;
    line-height:1.45;margin-top:3px;
}

.badge-mini {
    display:inline-flex;align-items:center;gap:4px;
    padding:2px 8px;border-radius:12px;
    font-size:.65rem;font-weight:700;letter-spacing:.02em;
}

.aud-badge {
    display:inline-flex;align-items:center;
    padding:3px 10px;border-radius:20px;
    font-size:.68rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;
}

.status-pill {
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 10px;border-radius:20px;
    font-size:.7rem;font-weight:700;
    letter-spacing:.02em;white-space:nowrap;
}

.metric-num { font-weight:700;font-size:.85rem; }

.rate-bar-bg {
    flex:1;background:#f1f5f9;
    border-radius:99px;height:5px;min-width:48px;
}
.rate-bar-fill { height:5px;border-radius:99px;transition:width .3s; }
</style>
@endsection
