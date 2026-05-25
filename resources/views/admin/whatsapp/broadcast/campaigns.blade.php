@extends('layouts.app')
@section('title', 'Relatório de Disparos — WhatsApp')

@section('content')
@php
    $statusMap = [
        'scheduled'  => ['bg' => 'rgba(99,102,241,.12)',  'color' => '#4f46e5', 'border' => '#c7d2fe', 'label' => 'Agendado',    'icon' => 'clock'],
        'queued'     => ['bg' => 'rgba(99,102,241,.12)',  'color' => '#4f46e5', 'border' => '#c7d2fe', 'label' => 'Na Fila',     'icon' => 'hourglass-half'],
        'processing' => ['bg' => 'rgba(245,158,11,.12)',  'color' => '#d97706', 'border' => '#fde68a', 'label' => 'Processando', 'icon' => 'spinner'],
        'completed'  => ['bg' => 'rgba(16,185,129,.12)',  'color' => '#059669', 'border' => '#a7f3d0', 'label' => 'Concluído',   'icon' => 'circle-check'],
        'failed'     => ['bg' => 'rgba(239,68,68,.12)',   'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Falhou',      'icon' => 'circle-xmark'],
        'paused'     => ['bg' => 'rgba(148,163,184,.12)', 'color' => '#64748b', 'border' => '#e2e8f0', 'label' => 'Pausado',     'icon' => 'pause'],
    ];
    $audienceLabels = ['all' => 'Todos os Contatos', 'selected' => 'Números Específicos', 'groups' => 'Grupos'];
    $audienceIcons  = ['all' => 'users', 'selected' => 'list-check', 'groups' => 'people-group'];
    $audienceColors = [
        'all'      => ['bg' => 'rgba(99,102,241,.1)',  'color' => '#4338ca'],
        'selected' => ['bg' => 'rgba(16,185,129,.1)',  'color' => '#166534'],
        'groups'   => ['bg' => 'rgba(245,158,11,.1)',  'color' => '#92400e'],
    ];
@endphp

<div class="bc-wrap">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="bc-header">
        <div class="bc-header-inner">
            <div class="bc-header-left">
                <a href="{{ route('whatsapp.broadcast.index') }}" class="bc-back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <div class="bc-breadcrumb">WhatsApp &rsaquo; Disparo em Massa &rsaquo; Relatório</div>
                    <h1 class="bc-title">
                        <i class="fas fa-chart-bar me-2" style="color:#6366f1;"></i>
                        Relatório de Disparos
                    </h1>
                </div>
            </div>
            <a href="{{ route('whatsapp.broadcast.index') }}" class="bc-new-btn">
                <i class="fas fa-rocket"></i>
                <span>Nova Campanha</span>
            </a>
        </div>
    </div>

    {{-- ── KPIs ─────────────────────────────────────────────────────── --}}
    <div class="bc-kpi-grid">
        <div class="bc-kpi bc-kpi--indigo">
            <div class="bc-kpi-icon"><i class="fas fa-rocket"></i></div>
            <div class="bc-kpi-body">
                <div class="bc-kpi-val">{{ number_format($campaigns->total()) }}</div>
                <div class="bc-kpi-lbl">Total de Campanhas</div>
            </div>
        </div>
        <div class="bc-kpi bc-kpi--green">
            <div class="bc-kpi-icon"><i class="fas fa-paper-plane"></i></div>
            <div class="bc-kpi-body">
                <div class="bc-kpi-val">{{ number_format($totalSent) }}</div>
                <div class="bc-kpi-lbl">Mensagens Enviadas</div>
            </div>
        </div>
        <div class="bc-kpi bc-kpi--red">
            <div class="bc-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="bc-kpi-body">
                <div class="bc-kpi-val">{{ number_format($totalFailed) }}</div>
                <div class="bc-kpi-lbl">Falhas de Entrega</div>
            </div>
        </div>
        <div class="bc-kpi bc-kpi--{{ $successRate >= 80 ? 'green' : ($successRate >= 50 ? 'amber' : 'red') }}">
            <div class="bc-kpi-icon"><i class="fas fa-bullseye"></i></div>
            <div class="bc-kpi-body">
                <div class="bc-kpi-val">{{ $successRate }}%</div>
                <div class="bc-kpi-lbl">Taxa de Sucesso</div>
            </div>
        </div>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────── --}}
    <div class="bc-filter-card">
        <form method="GET" action="{{ route('whatsapp.broadcast.campaigns') }}" class="bc-filter-form">
            <div class="bc-filter-group">
                <label for="status" class="bc-filter-lbl">Status</label>
                <select name="status" class="bc-select" id="status">
                    <option value="">Todos</option>
                    @foreach(['completed'=>'Concluído','processing'=>'Processando','queued'=>'Na Fila','scheduled'=>'Agendado','paused'=>'Pausado','failed'=>'Falhou'] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="bc-filter-group">
                <label for="audience" class="bc-filter-lbl">Público</label>
                <select name="audience" class="bc-select" id="audience">
                    <option value="">Todos</option>
                    <option value="all"      {{ request('audience')==='all'      ? 'selected':'' }}>Todos os Contatos</option>
                    <option value="selected" {{ request('audience')==='selected' ? 'selected':'' }}>Números Específicos</option>
                    <option value="groups"   {{ request('audience')==='groups'   ? 'selected':'' }}>Grupos</option>
                </select>
            </div>
            <div class="bc-filter-group">
                <label for="from" class="bc-filter-lbl">De</label>
                <input type="date" name="from" value="{{ request('from') }}" class="bc-select" id="from">
            </div>
            <div class="bc-filter-group">
                <label for="to" class="bc-filter-lbl">Até</label>
                <input type="date" name="to" value="{{ request('to') }}" class="bc-select" id="to">
            </div>
            <div class="bc-filter-actions">
                <button type="submit" class="bc-btn-filter">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['status','audience','from','to']))
                    <a href="{{ route('whatsapp.broadcast.campaigns') }}" class="bc-btn-clear">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── Campaign List ────────────────────────────────────────────── --}}
    @if($campaigns->isEmpty())
        <div class="bc-empty">
            <div class="bc-empty-icon"><i class="fas fa-rocket"></i></div>
            <h5 class="bc-empty-title">Nenhuma campanha encontrada</h5>
            <p class="bc-empty-sub">
                @if(request()->hasAny(['status','audience','from','to']))
                    Tente ajustar os filtros acima.
                @else
                    Crie sua primeira campanha de disparo em massa.
                @endif
            </p>
            <a href="{{ route('whatsapp.broadcast.index') }}" class="bc-new-btn">
                <i class="fas fa-rocket"></i> Nova Campanha
            </a>
        </div>
    @else
        <div class="bc-list-header">
            <span class="bc-list-count">
                {{ number_format($campaigns->total()) }} campanha{{ $campaigns->total() !== 1 ? 's' : '' }}
                @if($campaigns->hasPages()) · página {{ $campaigns->currentPage() }} / {{ $campaigns->lastPage() }} @endif
            </span>
            <span class="bc-list-per">25 por página</span>
        </div>

        <div class="bc-cards">
            @foreach($campaigns as $campaign)
            @php
                $total     = $campaign->total_sent + $campaign->total_failed;
                $rate      = $total > 0 ? round($campaign->total_sent / $total * 100) : 0;
                $s         = $statusMap[$campaign->status] ?? ['bg'=>'rgba(100,116,139,.1)','color'=>'#475569','border'=>'#e2e8f0','label'=>$campaign->status,'icon'=>'circle'];
                $aud       = $audienceColors[$campaign->audience_type] ?? $audienceColors['all'];
                $audLabel  = $audienceLabels[$campaign->audience_type] ?? $campaign->audience_type;
                $audIcon   = $audienceIcons[$campaign->audience_type] ?? 'users';
                $isGroups  = $campaign->audience_type === 'groups';
                $isMembers = $isGroups && $campaign->group_send_mode === 'members';
                $rateColor = $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#f59e0b' : '#ef4444');
                $rateText  = $rate >= 90 ? '#059669' : ($rate >= 70 ? '#d97706' : '#dc2626');
            @endphp

            <div class="bc-card" style="border-left:4px solid {{ $s['border'] }};">

                {{-- Top row: date + status --}}
                <div class="bc-card-top">
                    <div class="bc-card-date">
                        <i class="fas fa-calendar-day" style="color:#94a3b8;font-size:.7rem;"></i>
                        {{ $campaign->created_at->format('d/m/Y H:i') }}
                        @if($campaign->scheduled_at && $campaign->status === 'scheduled')
                            <span class="bc-sched-badge">
                                <i class="fas fa-clock"></i> {{ $campaign->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m H:i') }}
                            </span>
                        @endif
                    </div>
                    <span class="bc-status-pill" style="background:{{ $s['bg'] }};color:{{ $s['color'] }};">
                        <i class="fas fa-{{ $s['icon'] }} {{ $campaign->status === 'processing' ? 'fa-spin' : '' }}"></i>
                        {{ $s['label'] }}
                    </span>
                </div>

                {{-- Message preview --}}
                <div class="bc-card-msg">
                    @if($campaign->has_image)
                        <span class="bc-badge-img"><i class="fas fa-image"></i> Imagem</span>
                    @endif
                    @if($campaign->message)
                        <p class="bc-msg-text">{{ $campaign->message }}</p>
                    @elseif(!$campaign->has_image)
                        <p class="bc-msg-empty">Sem mensagem</p>
                    @endif
                </div>

                {{-- Audience + Mode --}}
                <div class="bc-card-tags">
                    <span class="bc-aud-badge" style="background:{{ $aud['bg'] }};color:{{ $aud['color'] }};">
                        <i class="fas fa-{{ $audIcon }}"></i> {{ $audLabel }}
                    </span>
                    @if($isGroups)
                        @if($isMembers)
                            <span class="bc-mode-badge bc-mode--members">
                                <i class="fas fa-user-check"></i> Individual p/ membros
                            </span>
                        @else
                            <span class="bc-mode-badge bc-mode--group">
                                <i class="fas fa-people-group"></i> No grupo
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Metrics row --}}
                <div class="bc-card-metrics">

                    <div class="bc-metric">
                        <div class="bc-metric-val" style="color:#334155;">
                            {{ $campaign->actual_recipients !== null ? number_format($campaign->actual_recipients) : '—' }}
                        </div>
                        <div class="bc-metric-lbl">destinatários</div>
                    </div>

                    <div class="bc-metric">
                        <div class="bc-metric-val" style="color:#059669;">
                            {{ number_format($campaign->total_sent) }}
                        </div>
                        <div class="bc-metric-lbl">enviados</div>
                    </div>

                    <div class="bc-metric">
                        <div class="bc-metric-val" style="color:{{ $campaign->total_failed > 0 ? '#dc2626' : '#94a3b8' }};">
                            {{ number_format($campaign->total_failed) }}
                        </div>
                        <div class="bc-metric-lbl">falhas</div>
                    </div>

                    @if($campaign->duration)
                    <div class="bc-metric">
                        <div class="bc-metric-val" style="color:#475569;">{{ $campaign->duration }}</div>
                        <div class="bc-metric-lbl">duração</div>
                    </div>
                    @endif

                    @if($total > 0)
                    <div class="bc-metric bc-metric--rate">
                        <div class="bc-rate-ring" style="--p:{{ $rate }};--c:{{ $rateColor }};">
                            <span style="color:{{ $rateText }};font-weight:800;font-size:.75rem;">{{ $rate }}%</span>
                        </div>
                        <div class="bc-metric-lbl">taxa</div>
                    </div>
                    @endif

                </div>

                @if($total > 0)
                <div class="bc-progress-bar">
                    <div class="bc-progress-fill" style="width:{{ $rate }}%;background:{{ $rateColor }};"></div>
                </div>
                @endif

            </div>
            @endforeach
        </div>

        @if($campaigns->hasPages())
            <div class="bc-pagination">
                {{ $campaigns->links() }}
            </div>
        @endif
    @endif

</div>

<style>
/* ── Wrapper ──────────────────────────────────────────────────────────── */
.bc-wrap { max-width:1100px; margin:0 auto; padding:24px 16px 48px; }

/* ── Header ───────────────────────────────────────────────────────────── */
.bc-header {
    background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);
    border-radius:20px; padding:24px 28px; margin-bottom:24px;
    box-shadow:0 8px 30px rgba(79,70,229,.25);
}
.bc-header-inner { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.bc-header-left  { display:flex; align-items:center; gap:14px; }
.bc-back-btn {
    width:36px; height:36px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
    border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
    color:#fff; font-size:.85rem; flex-shrink:0; text-decoration:none; transition:.15s;
}
.bc-back-btn:hover { background:rgba(255,255,255,.25); color:#fff; }
.bc-breadcrumb { font-size:.65rem; color:rgba(255,255,255,.65); text-transform:uppercase; letter-spacing:1.4px; font-weight:600; margin-bottom:3px; }
.bc-title { font-size:1.45rem; font-weight:800; color:#fff; margin:0; line-height:1.2; }
.bc-new-btn {
    display:inline-flex; align-items:center; gap:8px;
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
    color:#fff; font-size:.83rem; font-weight:700; padding:9px 20px;
    border-radius:12px; text-decoration:none; transition:.15s; white-space:nowrap;
}
.bc-new-btn:hover { background:rgba(255,255,255,.25); color:#fff; }

/* ── KPIs ─────────────────────────────────────────────────────────────── */
.bc-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
@media(max-width:768px){ .bc-kpi-grid{ grid-template-columns:repeat(2,1fr); } }

.bc-kpi {
    background:#fff; border-radius:16px; padding:18px 16px;
    display:flex; align-items:center; gap:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.05); border:1px solid #f1f5f9;
    border-top-width:3px;
}
.bc-kpi--indigo { border-top-color:#6366f1; }
.bc-kpi--green  { border-top-color:#10b981; }
.bc-kpi--red    { border-top-color:#ef4444; }
.bc-kpi--amber  { border-top-color:#f59e0b; }

.bc-kpi-icon {
    width:44px; height:44px; border-radius:12px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.1rem;
}
.bc-kpi--indigo .bc-kpi-icon { background:rgba(99,102,241,.1);  color:#6366f1; }
.bc-kpi--green  .bc-kpi-icon { background:rgba(16,185,129,.1);  color:#10b981; }
.bc-kpi--red    .bc-kpi-icon { background:rgba(239,68,68,.1);   color:#ef4444; }
.bc-kpi--amber  .bc-kpi-icon { background:rgba(245,158,11,.1);  color:#f59e0b; }

.bc-kpi-val  { font-size:1.6rem; font-weight:800; color:#0f172a; line-height:1.1; }
.bc-kpi-lbl  { font-size:.67rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-top:2px; font-weight:600; }

/* ── Filters ──────────────────────────────────────────────────────────── */
.bc-filter-card {
    background:#fff; border-radius:14px; padding:16px 20px; margin-bottom:18px;
    box-shadow:0 2px 12px rgba(0,0,0,.05); border:1px solid #e2e8f0;
}
.bc-filter-form    { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.bc-filter-group   { display:flex; flex-direction:column; }
.bc-filter-lbl     { font-size:.68rem; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
.bc-select         { border:1.5px solid #e2e8f0; border-radius:9px; padding:6px 12px; font-size:.83rem; color:#334155; background:#f8fafc; outline:none; min-width:140px; }
.bc-select:focus   { border-color:#6366f1; background:#fff; }
.bc-filter-actions { display:flex; gap:8px; align-items:flex-end; }
.bc-btn-filter {
    background:#4f46e5; color:#fff; border:none; border-radius:9px;
    padding:7px 16px; font-size:.82rem; font-weight:700; cursor:pointer; transition:.15s;
    display:inline-flex; align-items:center; gap:6px;
}
.bc-btn-filter:hover { background:#4338ca; }
.bc-btn-clear {
    background:#f1f5f9; color:#64748b; border:1.5px solid #e2e8f0; border-radius:9px;
    padding:6px 14px; font-size:.82rem; font-weight:600; text-decoration:none; transition:.15s;
    display:inline-flex; align-items:center; gap:5px;
}
.bc-btn-clear:hover { background:#e2e8f0; color:#334155; }

/* ── List header ──────────────────────────────────────────────────────── */
.bc-list-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding:0 2px; }
.bc-list-count  { font-size:.75rem; font-weight:700; color:#64748b; }
.bc-list-per    { font-size:.7rem; color:#94a3b8; }

/* ── Campaign Cards ───────────────────────────────────────────────────── */
.bc-cards { display:flex; flex-direction:column; gap:12px; }

.bc-card {
    background:#fff; border-radius:16px;
    box-shadow:0 2px 12px rgba(0,0,0,.05); border:1px solid #e2e8f0;
    padding:18px 20px 14px; transition:.15s;
    overflow:hidden; position:relative;
}
.bc-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.09); transform:translateY(-1px); }

.bc-card-top {
    display:flex; justify-content:space-between; align-items:center;
    gap:12px; margin-bottom:10px; flex-wrap:wrap;
}
.bc-card-date { font-size:.75rem; color:#64748b; font-weight:600; display:flex; align-items:center; gap:6px; }
.bc-sched-badge {
    background:rgba(99,102,241,.1); color:#4f46e5;
    padding:2px 8px; border-radius:8px; font-size:.67rem; font-weight:700;
    display:inline-flex; align-items:center; gap:4px;
}

.bc-status-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:20px;
    font-size:.7rem; font-weight:800; letter-spacing:.02em; white-space:nowrap;
}

/* Message */
.bc-card-msg { margin-bottom:10px; }
.bc-badge-img {
    display:inline-flex; align-items:center; gap:4px;
    background:rgba(99,102,241,.1); color:#4f46e5;
    padding:2px 8px; border-radius:8px; font-size:.65rem; font-weight:700; margin-bottom:4px;
}
.bc-msg-text {
    font-size:.82rem; color:#334155; line-height:1.5; margin:0;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.bc-msg-empty { font-size:.75rem; color:#94a3b8; font-style:italic; margin:0; }

/* Tags */
.bc-card-tags { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
.bc-aud-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px;
    font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
}
.bc-mode-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px;
    font-size:.67rem; font-weight:700;
}
.bc-mode--members { background:rgba(16,185,129,.1);  color:#059669; }
.bc-mode--group   { background:rgba(245,158,11,.1);  color:#92400e; }

/* Metrics */
.bc-card-metrics { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.bc-metric       { text-align:center; min-width:48px; }
.bc-metric-val   { font-size:1rem; font-weight:800; line-height:1.1; }
.bc-metric-lbl   { font-size:.62rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-top:1px; font-weight:600; }

/* Rate ring (CSS-only) */
.bc-metric--rate { display:flex; flex-direction:column; align-items:center; gap:2px; }
.bc-rate-ring {
    width:44px; height:44px; border-radius:50%;
    background:conic-gradient(var(--c) calc(var(--p) * 1%),#f1f5f9 0);
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.bc-rate-ring::before {
    content:''; position:absolute;
    width:32px; height:32px; background:#fff; border-radius:50%;
}
.bc-rate-ring span { position:relative; z-index:1; }

/* Progress bar */
.bc-progress-bar {
    height:3px; background:#f1f5f9; border-radius:99px;
    margin-top:14px; overflow:hidden;
}
.bc-progress-fill { height:3px; border-radius:99px; transition:width .4s ease; }

/* Empty state */
.bc-empty { text-align:center; padding:60px 24px; background:#fff; border-radius:20px; border:1px solid #e2e8f0; }
.bc-empty-icon {
    width:64px; height:64px; margin:0 auto 16px;
    background:linear-gradient(135deg,rgba(79,70,229,.15),rgba(124,58,237,.15));
    border-radius:18px; display:flex; align-items:center; justify-content:center;
    font-size:1.5rem; color:#6366f1;
}
.bc-empty-title { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:6px; }
.bc-empty-sub   { font-size:.82rem; color:#94a3b8; max-width:280px; margin:0 auto 20px; }

/* Pagination */
.bc-pagination { margin-top:20px; display:flex; justify-content:center; }

@media(max-width:576px){
    .bc-card-metrics { gap:12px; }
    .bc-metric-val   { font-size:.88rem; }
    .bc-rate-ring    { width:38px; height:38px; }
    .bc-rate-ring::before { width:28px; height:28px; }
    .bc-rate-ring span { font-size:.65rem !important; }
    .bc-header { padding:18px; }
    .bc-title  { font-size:1.2rem; }
}
</style>
@endsection
