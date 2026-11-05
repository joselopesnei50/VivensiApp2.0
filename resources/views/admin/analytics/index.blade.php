@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $chg = function ($c, $inverse = false) {
        if ($c === null) return '<span class="an-chg an-new">novo</span>';
        $good = $inverse ? $c < 0 : $c > 0;
        if ($c == 0) return '<span class="an-chg an-flat">0%</span>';
        return '<span class="an-chg ' . ($good ? 'an-up' : 'an-down') . '"><i class="fas fa-arrow-' . ($c > 0 ? 'up' : 'down') . '"></i> ' . number_format(abs($c), 1, ',', '.') . '%</span>';
    };
    $ago = fn ($d) => $d ? $d->locale('pt_BR')->diffForHumans() : 'nunca';
    $statusLabel = ['active' => 'Ativa', 'trialing' => 'Em teste', 'past_due' => 'Pagamento atrasado'];
    $app = $a['app']; $h = $a['health']; $pub = $a['public']; $wa = $a['whatsapp'];
@endphp

<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-grow-1" style="min-width:0;">
        <div class="an-eyebrow">Admin / SaaS &amp; Métricas</div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Analytics</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ $a['start']->format('d/m/Y') }} a {{ $a['end']->format('d/m/Y') }}, comparado com os {{ $a['days'] }} dias anteriores · só pessoas (sem robôs) · suas visitas como admin não entram
        </p>
    </div>
    <div class="an-periods flex-shrink-0" role="group" aria-label="Período">
        @foreach($periods as $d => $label)
            <a href="{{ route('admin.analytics', ['dias' => $d]) }}" class="{{ $a['days'] === $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

{{-- ═════ USO DO PRODUTO ═════ --}}
<h6 class="an-section"><i class="fas fa-chart-line"></i> Uso do produto pelos clientes</h6>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card an-card h-100">
            <div class="an-label">Organizações ativas</div>
            <div class="an-value">{{ $fmt($app['orgs']) }}</div>
            <div class="an-sub">{!! $chg($app['orgs_change']) !!} de {{ $fmt($app['total_orgs']) }} cadastradas · {{ $fmt($app['paying']) }} pagantes, {{ $fmt($app['trialing']) }} em teste</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card an-card h-100">
            <div class="an-label">Usuários ativos</div>
            <div class="an-value">{{ $fmt($app['users']) }}</div>
            <div class="an-sub">{!! $chg($app['users_change']) !!} no período</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card an-card h-100">
            <div class="an-label">Hoje · 7 dias · 30 dias</div>
            <div class="an-value">{{ $fmt($app['dau']) }} <span class="an-dim">· {{ $fmt($app['wau']) }} · {{ $fmt($app['mau']) }}</span></div>
            <div class="an-sub">usuários diferentes que entraram no sistema</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card an-card h-100">
            <div class="an-label">Retorno diário</div>
            <div class="an-value">{{ $app['stickiness'] !== null ? number_format($app['stickiness'], 0, ',', '.') . '%' : '—' }}</div>
            <div class="an-sub">dos usuários do mês entram num dia típico (acima de 20% é bom para SaaS de gestão)</div>
        </div>
    </div>
</div>

<div class="card an-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <h6 class="an-card-title mb-0">Atividade por dia</h6>
        <div class="an-legend"><span style="background:#6366f1"></span>Usuários <span style="background:#10b981"></span>Organizações</div>
    </div>
    <div style="position:relative;height:240px;"><canvas id="anApp" role="img" aria-label="Usuários e organizações ativos por dia"></canvas></div>
</div>

{{-- ═════ SAÚDE DAS ORGANIZAÇÕES ═════ --}}
<h6 class="an-section"><i class="fas fa-heart-pulse"></i> Saúde das organizações</h6>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card an-card h-100 an-tone-red">
        <div class="an-label">Em risco</div><div class="an-value">{{ $fmt($h['counts']['at_risk']) }}</div>
        <div class="an-sub">pagantes sem entrar há {{ \App\Services\ProductAnalyticsService::RISK_DAYS }}+ dias</div>
    </div></div>
    <div class="col-md-4"><div class="card an-card h-100 an-tone-amber">
        <div class="an-label">Não ativaram</div><div class="an-value">{{ $fmt($h['counts']['not_activated']) }}</div>
        <div class="an-sub">cadastro nos últimos 30 dias e usaram no máximo 1 dia</div>
    </div></div>
    <div class="col-md-4"><div class="card an-card h-100 an-tone-green">
        <div class="an-label">Saudáveis</div><div class="an-value">{{ $fmt($h['counts']['healthy']) }}</div>
        <div class="an-sub">ativas, em teste ou atrasadas que estão usando</div>
    </div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card an-card h-100 p-0 overflow-hidden">
            <div class="p-3 pb-2"><h6 class="an-card-title mb-0">🔴 Em risco: vale uma ligação</h6></div>
            @forelse($h['at_risk'] as $t)
                <div class="an-row">
                    <div style="min-width:0;flex:1;">
                        <a href="{{ url('/admin/tenants/' . $t['id']) }}" class="an-row-title">{{ $t['name'] }}</a>
                        <div class="an-row-sub">{{ $statusLabel[$t['status']] ?? $t['status'] }}@if($t['plan']) · {{ $t['plan'] }}@endif</div>
                    </div>
                    <div class="text-end"><div class="an-row-strong" style="color:#b91c1c;">{{ $t['last_seen'] ? 'última vez ' . $ago($t['last_seen']) : 'nunca usou' }}</div></div>
                </div>
            @empty
                <p class="an-empty">Nenhuma organização pagante parada. 🎉</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card an-card h-100 p-0 overflow-hidden">
            <div class="p-3 pb-2"><h6 class="an-card-title mb-0">🟡 Não ativaram: ajudar a começar</h6></div>
            @forelse($h['not_activated'] as $t)
                <div class="an-row">
                    <div style="min-width:0;flex:1;">
                        <a href="{{ url('/admin/tenants/' . $t['id']) }}" class="an-row-title">{{ $t['name'] }}</a>
                        <div class="an-row-sub">cadastro {{ $ago($t['created_at']) }} · {{ $statusLabel[$t['status']] ?? $t['status'] }}</div>
                    </div>
                    <div class="text-end"><div class="an-row-strong" style="color:#b45309;">{{ $t['active_days'] ? 'usou 1 dia' : 'nunca usou' }}</div></div>
                </div>
            @empty
                <p class="an-empty">Todos os cadastros recentes já estão usando.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card an-card p-0 overflow-hidden mb-4">
    <div class="p-3 pb-2"><h6 class="an-card-title mb-0">Organizações que mais usam no período</h6></div>
    @if(count($h['top']))
    <div class="table-responsive">
        <table class="table align-middle mb-0 an-table">
            <thead><tr><th class="ps-3">Organização</th><th class="d-none d-md-table-cell">Situação</th><th class="text-end">Páginas</th><th class="text-end">Usuários</th><th class="text-end">Variação</th><th class="text-end pe-3 d-none d-md-table-cell">Última vez</th></tr></thead>
            <tbody>
            @foreach($h['top'] as $t)
                <tr>
                    <td class="ps-3"><a href="{{ url('/admin/tenants/' . $t['id']) }}" class="an-row-title">{{ $t['name'] }}</a></td>
                    <td class="d-none d-md-table-cell" style="font-size:.8rem;color:#64748b;">{{ $statusLabel[$t['status']] ?? $t['status'] }}@if($t['plan']) · {{ $t['plan'] }}@endif</td>
                    <td class="text-end fw-bold">{{ $fmt($t['views']) }}</td>
                    <td class="text-end">{{ $fmt($t['users']) }}</td>
                    <td class="text-end">{!! $chg($t['change']) !!}</td>
                    <td class="text-end pe-3 d-none d-md-table-cell" style="font-size:.8rem;color:#64748b;">{{ $ago($t['last_seen']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
        <p class="an-empty">Sem uso registrado no período.</p>
    @endif
</div>

{{-- ═════ MÓDULOS ═════ --}}
<h6 class="an-section"><i class="fas fa-puzzle-piece"></i> Adoção por módulo</h6>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card an-card h-100">
            <h6 class="an-card-title">Quantas organizações ativas usam cada módulo</h6>
            @forelse($a['modules'] as $mo)
                <div class="an-bar-row">
                    <div class="an-bar-label" title="{{ $mo['label'] }}">{{ $mo['label'] }}</div>
                    <div class="an-bar-track"><div class="an-bar-fill" style="width:{{ max(1, $mo['adoption']) }}%;"></div></div>
                    <div class="an-bar-num">{{ number_format($mo['adoption'], 0, ',', '.') }}% <span>{{ $fmt($mo['orgs']) }} org.</span></div>
                </div>
            @empty
                <p class="an-empty px-0">Sem uso registrado no período.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card an-card h-100 p-0 overflow-hidden">
            <div class="p-3 pb-2"><h6 class="an-card-title mb-0">Páginas mais usadas no app</h6></div>
            @forelse($a['pages'] as $p)
                <div class="an-row">
                    <code class="an-path text-truncate">{{ $p['path'] }}</code>
                    <div class="text-end" style="white-space:nowrap;"><span class="an-row-strong">{{ $fmt($p['views']) }}</span> <span class="an-row-sub">· {{ $fmt($p['orgs']) }} org.</span></div>
                </div>
            @empty
                <p class="an-empty">Sem dados.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ═════ SITE PÚBLICO ═════ --}}
<h6 class="an-section"><i class="fas fa-globe"></i> Site público</h6>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card an-card h-100">
        <div class="an-label">Visitantes</div><div class="an-value">{{ $fmt($pub['visitors']) }}</div>
        <div class="an-sub">{!! $chg($pub['visitors_change']) !!} pessoas diferentes</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card an-card h-100">
        <div class="an-label">Páginas vistas</div><div class="an-value">{{ $fmt($pub['visits']) }}</div>
        <div class="an-sub">{!! $chg($pub['visits_change']) !!} no período</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card an-card h-100">
        <div class="an-label">Novos cadastros</div><div class="an-value">{{ $fmt($pub['signups']) }}</div>
        <div class="an-sub">{!! $chg($pub['signups_change']) !!} organizações criadas</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card an-card h-100">
        <div class="an-label">Conversão</div>
        <div class="an-value">{{ $pub['conversion'] !== null ? number_format($pub['conversion'], 1, ',', '.') . '%' : '—' }}</div>
        <div class="an-sub">dos visitantes viraram cadastro</div>
    </div></div>
</div>
@if(! $pub['tracking_since'] || $pub['tracking_since']->gt($a['start']))
    <p class="an-note"><i class="fas fa-circle-info"></i>
        Visitantes únicos, origem e dispositivo passaram a ser registrados {{ $pub['tracking_since'] ? 'em ' . $pub['tracking_since']->format('d/m/Y') : 'a partir de agora' }}. Antes disso, só as páginas vistas.
    </p>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card an-card h-100">
            <h6 class="an-card-title">Páginas vistas por dia</h6>
            <div style="position:relative;height:220px;"><canvas id="anPublic" role="img" aria-label="Páginas vistas no site por dia"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card an-card h-100">
            <h6 class="an-card-title">De onde vêm</h6>
            @forelse(array_slice($pub['sources'], 0, 7) as $s)
                <div class="an-bar-row">
                    <div class="an-bar-label">{{ $s['label'] }}</div>
                    <div class="an-bar-track"><div class="an-bar-fill" style="width:{{ max(1, $s['pct']) }}%;background:#0ea5e9;"></div></div>
                    <div class="an-bar-num">{{ number_format($s['pct'], 0, ',', '.') }}%</div>
                </div>
            @empty
                <p class="an-empty px-0">Ainda sem dados de origem.</p>
            @endforelse
            @if(count($pub['devices']))
                <div class="d-flex gap-3 flex-wrap mt-3 pt-3" style="border-top:1px solid #f1f5f9;">
                    @foreach($pub['devices'] as $d)
                        <div class="an-device"><i class="fas {{ ['mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button'][$d['key']] ?? 'fa-desktop' }}"></i> {{ $d['label'] }} <strong>{{ number_format($d['pct'], 0, ',', '.') }}%</strong></div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card an-card p-0 overflow-hidden mb-4">
    <div class="p-3 pb-2"><h6 class="an-card-title mb-0">Páginas mais vistas do site</h6></div>
    @forelse($pub['pages'] as $p)
        <div class="an-row">
            <code class="an-path text-truncate">{{ $p['path'] }}</code>
            <div class="text-end" style="white-space:nowrap;"><span class="an-row-strong">{{ $fmt($p['views']) }}</span>@if($p['visitors'])<span class="an-row-sub"> · {{ $fmt($p['visitors']) }} visitantes</span>@endif</div>
        </div>
    @empty
        <p class="an-empty">Sem dados.</p>
    @endforelse
</div>

{{-- ═════ WHATSAPP ═════ --}}
<h6 class="an-section"><i class="fab fa-whatsapp"></i> WhatsApp (todas as organizações)</h6>
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card an-card mb-3">
            <div class="an-label">Recebidas</div><div class="an-value">{{ $fmt($wa['inbound']) }}</div>
            <div class="an-sub">{!! $chg($wa['inbound_change']) !!} mensagens de contatos</div>
        </div>
        <div class="card an-card">
            <div class="an-label">Enviadas</div><div class="an-value">{{ $fmt($wa['outbound']) }}</div>
            <div class="an-sub">{!! $chg($wa['outbound_change']) !!} · custo da API oficial em <a href="{{ route('admin.whatsapp.billing') }}">Consumo Cloud</a></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card an-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <h6 class="an-card-title mb-0">Mensagens por dia</h6>
                <div class="an-legend"><span style="background:#25d366"></span>Recebidas <span style="background:#6366f1"></span>Enviadas</div>
            </div>
            <div style="position:relative;height:220px;"><canvas id="anWa" role="img" aria-label="Mensagens de WhatsApp por dia"></canvas></div>
        </div>
    </div>
</div>

@push('styles')
<style>
.an-eyebrow{font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;}
.an-periods{display:inline-flex;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:3px;gap:2px;}
.an-periods a{padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:700;color:#64748b;text-decoration:none;white-space:nowrap;}
.an-periods a.active{background:#eef2ff;color:#4338ca;}
.an-section{font-size:.74rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:800;margin:8px 0 12px;display:flex;align-items:center;gap:8px;}
.an-section i{color:#6366f1;}
.an-card{border:0;border-radius:16px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 1px 2px rgba(15,23,42,.04);padding:16px 18px;background:#fff;}
.an-card-title{font-size:.9rem;font-weight:800;color:#0f172a;margin-bottom:12px;}
.an-label{font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;}
.an-value{font-size:1.75rem;font-weight:800;color:#0f172a;line-height:1.15;margin:4px 0;font-variant-numeric:tabular-nums;}
.an-dim{font-size:1.05rem;color:#94a3b8;font-weight:700;}
.an-sub{font-size:.74rem;color:#64748b;line-height:1.45;}
.an-sub a{font-weight:700;}
.an-tone-red{border-left:4px solid #ef4444;} .an-tone-red .an-value{color:#b91c1c;}
.an-tone-amber{border-left:4px solid #f59e0b;} .an-tone-amber .an-value{color:#b45309;}
.an-tone-green{border-left:4px solid #10b981;} .an-tone-green .an-value{color:#047857;}
.an-chg{display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:20px;font-size:.7rem;font-weight:800;white-space:nowrap;}
.an-chg i{font-size:.6rem;}
.an-up{background:#dcfce7;color:#166534;} .an-down{background:#fee2e2;color:#991b1b;} .an-flat{background:#f1f5f9;color:#64748b;} .an-new{background:#e0e7ff;color:#3730a3;}
.an-legend{font-size:.74rem;color:#64748b;display:flex;align-items:center;gap:6px;}
.an-legend span{display:inline-block;width:10px;height:10px;border-radius:3px;margin-left:8px;}
.an-row{display:flex;align-items:center;gap:12px;padding:10px 16px;border-top:1px solid #f1f5f9;}
.an-row-title{font-weight:700;color:#0f172a;text-decoration:none;font-size:.88rem;}
.an-row-title:hover{color:#4f46e5;}
.an-row-sub{font-size:.75rem;color:#94a3b8;}
.an-row-strong{font-size:.82rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums;}
.an-path{flex:1;min-width:0;font-size:.78rem;color:#334155;background:none;padding:0;}
.an-empty{font-size:.85rem;color:#94a3b8;padding:12px 16px;margin:0;}
.an-note{font-size:.76rem;color:#64748b;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:8px 12px;margin:-4px 0 14px;}
.an-note i{color:#6366f1;margin-right:4px;}
.an-table thead th{font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;background:#f8fafc;border-bottom:1px solid #e2e8f0;}
.an-table td{font-size:.86rem;font-variant-numeric:tabular-nums;}
.an-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:9px;}
.an-bar-label{width:170px;flex-shrink:0;font-size:.82rem;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.an-bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:6px;overflow:hidden;}
.an-bar-fill{height:100%;border-radius:6px;background:#6366f1;}
.an-bar-num{width:96px;flex-shrink:0;text-align:right;font-size:.8rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums;}
.an-bar-num span{font-weight:600;color:#94a3b8;margin-left:4px;}
.an-device{font-size:.8rem;color:#475569;} .an-device i{color:#94a3b8;margin-right:4px;}
@media (max-width:575px){.an-bar-label{width:110px;} .an-bar-num{width:78px;} .an-bar-num span{display:none;}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    var base = {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 10, autoSkip: true } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', precision: 0 } }
        }
    };
    function line(label, data, color, fill) {
        return { label: label, data: data, borderColor: color, backgroundColor: fill ? color + '22' : color, fill: !!fill,
                 tension: data.length > 45 ? .15 : .3, borderWidth: 2, pointRadius: data.length > 45 ? 0 : 2.5, pointBackgroundColor: color };
    }
    var appLabels = @json($app['daily_users']['labels']);
    new Chart(document.getElementById('anApp'), { type: 'line', options: base, data: { labels: appLabels, datasets: [
        line('Usuários', @json($app['daily_users']['values']), '#6366f1', true),
        line('Organizações', @json($app['daily_orgs']['values']), '#10b981', false)
    ]}});
    new Chart(document.getElementById('anPublic'), { type: 'bar', options: base, data: { labels: @json($pub['daily']['labels']), datasets: [
        { label: 'Páginas vistas', data: @json($pub['daily']['values']), backgroundColor: '#0ea5e9', borderRadius: 4, maxBarThickness: 18 }
    ]}});
    new Chart(document.getElementById('anWa'), { type: 'line', options: base, data: { labels: @json($wa['daily_in']['labels']), datasets: [
        line('Recebidas', @json($wa['daily_in']['values']), '#25d366', false),
        line('Enviadas', @json($wa['daily_out']['values']), '#6366f1', false)
    ]}});
})();
</script>
@endpush
@endsection
