@extends('layouts.app')
@section('title', 'Analytics')

@section('content')
@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $url = fn (array $q) => route('admin.analytics', array_merge(['dias' => $m['days'], 'area' => $m['area']], $q));
    $chg = function ($c) {
        if ($c === null) return '<span class="bm-chg bm-new">novo</span>';
        if ($c > 0) return '<span class="bm-chg bm-up"><i class="fas fa-arrow-up"></i> ' . number_format($c, 1, ',', '.') . '%</span>';
        if ($c < 0) return '<span class="bm-chg bm-down"><i class="fas fa-arrow-down"></i> ' . number_format(abs($c), 1, ',', '.') . '%</span>';
        return '<span class="bm-chg bm-flat">0%</span>';
    };
    $sourceColors = [
        'google' => '#4285f4', 'search' => '#0ea5e9', 'instagram' => '#e1306c', 'facebook' => '#1877f2',
        'linkedin' => '#0a66c2', 'whatsapp' => '#25d366', 'x' => '#0f172a', 'youtube' => '#ff0000',
        'internal' => '#6366f1', 'email' => '#f59e0b', 'ai' => '#8b5cf6', 'direct' => '#64748b', 'other' => '#94a3b8',
    ];
    $hourMax = max(1, max($m['hours']));
    $peakHour = array_search(max($m['hours']), $m['hours']);
@endphp

{{-- Cabeçalho --}}
<div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
    <div class="flex-grow-1" style="min-width:0;">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">Admin / SaaS &amp; Métricas</div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Acessos ao sistema</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ $m['start']->format('d/m/Y') }} a {{ $m['end']->format('d/m/Y') }} · só pessoas (robôs, atualizações automáticas e erros ficam de fora) · seus acessos como admin não entram
        </p>
    </div>
    <div class="bm-periods flex-shrink-0" role="group" aria-label="Período">
        @foreach($periods as $d => $label)
            <a href="{{ $url(['dias' => $d]) }}" class="{{ $m['days'] === $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>
<div class="bm-periods mb-4" role="group" aria-label="Área">
    @foreach($areas as $key => $label)
        <a href="{{ $url(['area' => $key]) }}" class="{{ $m['area'] === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Visitas</div>
            <div class="bm-kpi-value">{{ $fmt($m['views']) }}</div>
            <div class="bm-kpi-sub">{!! $chg($m['views_change']) !!} vs. {{ $fmt($m['prev_views']) }} no período anterior · hoje: {{ $fmt($m['today']) }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Visitantes únicos</div>
            <div class="bm-kpi-value">{{ $fmt($m['visitors']) }}</div>
            <div class="bm-kpi-sub">{!! $chg($m['visitors_change']) !!} pessoas diferentes</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Páginas por visitante</div>
            <div class="bm-kpi-value">{{ $m['per_visitor'] !== null ? number_format($m['per_visitor'], 1, ',', '.') : '—' }}</div>
            <div class="bm-kpi-sub">quanto cada pessoa navega</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Usuários logados</div>
            <div class="bm-kpi-value">{{ $fmt($m['logged']) }}</div>
            <div class="bm-kpi-sub">{!! $chg($m['logged_change']) !!} clientes que entraram no sistema</div>
        </div>
    </div>
</div>

@if(! $m['tracking_since'] || $m['tracking_since']->gt($m['start']))
    <p class="bm-note"><i class="fas fa-circle-info"></i>
        Visitantes anônimos, origem e dispositivo passaram a ser registrados {{ $m['tracking_since'] ? 'em ' . $m['tracking_since']->format('d/m/Y') : 'a partir de agora' }}.
        Antes disso, só as visitas (e os usuários logados).
    </p>
@endif

{{-- Gráfico diário --}}
<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h6 class="fw-800 mb-0" style="color:#0f172a;">Visitas por dia</h6>
        <div class="bm-legend"><span style="background:#6366f1"></span>Visitas <span style="background:#10b981"></span>Visitantes</div>
    </div>
    <div style="position:relative;height:260px;"><canvas id="anDaily" role="img" aria-label="Visitas por dia"></canvas></div>
</div>

{{-- Origem, dispositivo, área --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">De onde vêm os acessos</h6>
            @forelse($m['sources'] as $s)
                <div class="bm-bar-row">
                    <div class="bm-bar-label">{{ $s['label'] }}</div>
                    <div class="bm-bar-track"><div class="bm-bar-fill" style="width:{{ max(1, $s['pct']) }}%;background:{{ $sourceColors[$s['key']] ?? '#94a3b8' }};"></div></div>
                    <div class="bm-bar-num">{{ $fmt($s['count']) }} <span>{{ number_format($s['pct'], 1, ',', '.') }}%</span></div>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size:.85rem;">Ainda sem dados de origem no período.</p>
            @endforelse
            @if(count($m['referrers']))
                <h6 class="fw-800 mb-2 mt-4" style="color:#0f172a;font-size:.85rem;">Outros sites que mandaram visitantes</h6>
                <ul class="list-unstyled mb-0" style="font-size:.82rem;">
                    @foreach($m['referrers'] as $host => $count)
                        <li class="d-flex justify-content-between py-1" style="border-bottom:1px solid #f1f5f9;">
                            <span class="text-truncate me-2" style="color:#334155;">{{ $host }}</span>
                            <span class="fw-bold" style="color:#64748b;">{{ $fmt($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">Dispositivo</h6>
            @forelse($m['devices'] as $d)
                <div class="bm-bar-row">
                    <div class="bm-bar-label"><i class="fas {{ ['mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button'][$d['key']] ?? 'fa-desktop' }} me-1" style="color:#94a3b8;width:14px;"></i>{{ $d['label'] }}</div>
                    <div class="bm-bar-track"><div class="bm-bar-fill" style="width:{{ max(1, $d['pct']) }}%;background:#6366f1;"></div></div>
                    <div class="bm-bar-num">{{ number_format($d['pct'], 0, ',', '.') }}%</div>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size:.85rem;">Ainda sem dados de dispositivo no período.</p>
            @endforelse
        </div>
        @if($m['area'] === 'tudo')
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">Onde acontecem os acessos</h6>
            @foreach($m['areas'] as $ar)
                <div class="bm-bar-row">
                    <div class="bm-bar-label">{{ $ar['label'] }}</div>
                    <div class="bm-bar-track"><div class="bm-bar-fill" style="width:{{ max(1, $ar['pct']) }}%;background:{{ ['app' => '#10b981', 'clients' => '#f59e0b'][$ar['key']] ?? '#0ea5e9' }};"></div></div>
                    <div class="bm-bar-num">{{ $fmt($ar['count']) }} <span>{{ number_format($ar['pct'], 0, ',', '.') }}%</span></div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Horários --}}
<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h6 class="fw-800 mb-0" style="color:#0f172a;">Horários de acesso</h6>
        @if(max($m['hours']) > 0)<span style="font-size:.75rem;color:#94a3b8;">pico às {{ str_pad($peakHour, 2, '0', STR_PAD_LEFT) }}h</span>@endif
    </div>
    <div class="an-hours" role="img" aria-label="Visitas por hora do dia">
        @foreach($m['hours'] as $h => $c)
            <div class="an-hour" title="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}h: {{ $fmt($c) }} visitas">
                <div class="an-hour-bar" style="height:{{ max(2, round($c / $hourMax * 100)) }}%;{{ $h === $peakHour && $c > 0 ? 'background:#4f46e5;' : '' }}"></div>
                <div class="an-hour-label">{{ $h % 3 === 0 ? str_pad($h, 2, '0', STR_PAD_LEFT) : '' }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Páginas --}}
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="p-3 p-md-4 pb-0">
        <h6 class="fw-800 mb-1" style="color:#0f172a;">Páginas mais acessadas</h6>
        <p class="mb-3" style="font-size:.78rem;color:#94a3b8;">Endereços com número viram {id} para somar as páginas do mesmo tipo.</p>
    </div>
    @if(count($m['pages']))
        <div class="table-responsive">
            <table class="table align-middle mb-0 bm-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:40px;">#</th>
                        <th>Página</th>
                        <th class="text-end">Visitas</th>
                        <th class="text-end d-none d-md-table-cell">Visitantes</th>
                        <th class="text-end">Variação</th>
                        <th class="pe-4 d-none d-md-table-cell" style="width:160px;">Participação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($m['pages'] as $i => $p)
                        <tr>
                            <td class="ps-4" style="color:#94a3b8;font-weight:700;">{{ $i + 1 }}</td>
                            <td style="max-width:420px;">
                                <code class="d-block text-truncate" style="font-size:.8rem;color:#0f172a;background:none;padding:0;">{{ $p['path'] }}</code>
                                @if($m['area'] === 'tudo')<span class="bm-tag {{ $p['area'] }}">{{ ['app' => 'área logada', 'clients' => 'página de cliente'][$p['area']] ?? 'site Vivensi' }}</span>@endif
                            </td>
                            <td class="text-end fw-800" style="color:#4f46e5;">{{ $fmt($p['views']) }}</td>
                            <td class="text-end d-none d-md-table-cell" style="color:#475569;">{{ $p['visitors'] ? $fmt($p['visitors']) : '—' }}</td>
                            <td class="text-end">{!! $chg($p['change']) !!}</td>
                            <td class="pe-4 d-none d-md-table-cell">
                                <div class="bm-bar-track" title="{{ number_format($p['share'], 1, ',', '.') }}%"><div class="bm-bar-fill" style="width:{{ max(1, $p['share']) }}%;background:#6366f1;"></div></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted px-4 pb-4 mb-0" style="font-size:.85rem;">Nenhuma visita no período.</p>
    @endif
</div>

<div class="row g-3 mb-4">
    {{-- Organizações --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="p-3 p-md-4 pb-2"><h6 class="fw-800 mb-0" style="color:#0f172a;">Organizações que mais acessam</h6></div>
            @forelse($m['orgs'] as $o)
                <div class="d-flex align-items-center gap-2 px-3 px-md-4 py-2" style="border-top:1px solid #f1f5f9;">
                    <a href="{{ route('admin.tenants.show', $o['id']) }}" class="fw-bold text-truncate flex-grow-1" style="color:#0f172a;text-decoration:none;font-size:.86rem;">{{ $o['name'] }}</a>
                    <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">{{ $fmt($o['users']) }} usuário(s)</span>
                    <span class="fw-800" style="color:#4f46e5;font-size:.86rem;min-width:48px;text-align:right;">{{ $fmt($o['views']) }}</span>
                    <span style="min-width:62px;text-align:right;">{!! $chg($o['change']) !!}</span>
                </div>
            @empty
                <p class="text-muted px-4 pb-4 mb-0" style="font-size:.85rem;">Nenhum acesso de clientes no período.</p>
            @endforelse
        </div>
    </div>
    {{-- WhatsApp --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100">
            <h6 class="fw-800 mb-3" style="color:#0f172a;"><i class="fab fa-whatsapp me-1" style="color:#25d366;"></i> WhatsApp no período</h6>
            <div class="d-flex gap-4">
                <div>
                    <div class="bm-kpi-label">Recebidas</div>
                    <div class="bm-kpi-value">{{ $fmt($m['whatsapp']['inbound']) }}</div>
                    <div class="bm-kpi-sub">{!! $chg($m['whatsapp']['inbound_change']) !!}</div>
                </div>
                <div>
                    <div class="bm-kpi-label">Enviadas</div>
                    <div class="bm-kpi-value">{{ $fmt($m['whatsapp']['outbound']) }}</div>
                    <div class="bm-kpi-sub">{!! $chg($m['whatsapp']['outbound_change']) !!}</div>
                </div>
            </div>
            <a href="{{ route('admin.whatsapp.billing') }}" class="d-inline-block mt-3" style="font-size:.8rem;font-weight:700;">Ver custo da API oficial →</a>
        </div>
    </div>
</div>

<p style="font-size:.72rem;color:#94a3b8;">
    Visita = uma página aberta. Visitante único = um usuário logado ou um navegador anônimo. Os registros ficam guardados por cerca de 13 meses.
</p>

@push('styles')
<style>
.bm-periods { display:inline-flex;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:3px;gap:2px;flex-wrap:wrap; }
.bm-periods a { padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:700;color:#64748b;text-decoration:none;white-space:nowrap; }
.bm-periods a.active { background:#eef2ff;color:#4338ca; }
.bm-kpi-label { font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700; }
.bm-kpi-value { font-size:1.75rem;font-weight:800;color:#0f172a;line-height:1.15;margin:4px 0;font-variant-numeric:tabular-nums; }
.bm-kpi-sub { font-size:.74rem;color:#64748b; }
.bm-chg { display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:20px;font-size:.7rem;font-weight:800;white-space:nowrap; }
.bm-chg i { font-size:.6rem; }
.bm-up { background:#dcfce7;color:#166534; } .bm-down { background:#fee2e2;color:#991b1b; }
.bm-flat { background:#f1f5f9;color:#64748b; } .bm-new { background:#e0e7ff;color:#3730a3; }
.bm-note { font-size:.76rem;color:#64748b;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:8px 12px;margin:-8px 0 16px; }
.bm-note i { color:#6366f1;margin-right:4px; }
.bm-legend { font-size:.74rem;color:#64748b;display:flex;align-items:center;gap:6px; }
.bm-legend span { display:inline-block;width:10px;height:10px;border-radius:3px;margin-left:8px; }
.bm-bar-row { display:flex;align-items:center;gap:10px;margin-bottom:9px; }
.bm-bar-label { width:150px;flex-shrink:0;font-size:.82rem;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.bm-bar-track { flex:1;height:8px;background:#f1f5f9;border-radius:6px;overflow:hidden; }
.bm-bar-fill { height:100%;border-radius:6px; }
.bm-bar-num { width:92px;flex-shrink:0;text-align:right;font-size:.8rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums; }
.bm-bar-num span { font-weight:600;color:#94a3b8;margin-left:4px; }
.bm-table thead th { font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;background:#f8fafc;border-bottom:1px solid #e2e8f0; }
.bm-table td { font-size:.86rem;font-variant-numeric:tabular-nums; }
.bm-tag { display:inline-block;margin-top:2px;padding:0 7px;border-radius:10px;font-size:.64rem;font-weight:800;background:#e0f2fe;color:#0369a1; }
.bm-tag.app { background:#dcfce7;color:#166534; }
.bm-tag.clients { background:#fef3c7;color:#92400e; }
.an-hours { display:flex;align-items:flex-end;gap:4px;height:130px;padding-bottom:18px; }
.an-hour { flex:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;position:relative; }
.an-hour-bar { background:#c7d2fe;border-radius:4px 4px 0 0;min-height:2px; }
.an-hour-label { position:absolute;bottom:-18px;left:0;right:0;text-align:center;font-size:.66rem;color:#94a3b8; }
@media (max-width:575px) { .bm-bar-label { width:105px; } .an-hours { gap:2px; } }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var el = document.getElementById('anDaily');
    if (!el || typeof Chart === 'undefined') return;
    var labels = @json($m['daily']['labels']), views = @json($m['daily']['views']), visitors = @json($m['daily']['visitors']);
    var ctx = el.getContext('2d'), grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(99,102,241,.25)'); grad.addColorStop(1, 'rgba(99,102,241,0)');
    var long = labels.length > 45;
    new Chart(el, {
        type: 'line',
        data: { labels: labels, datasets: [
            { label: 'Visitas', data: views, borderColor: '#6366f1', backgroundColor: grad, fill: true, tension: long ? .15 : .3, borderWidth: 2, pointRadius: long ? 0 : 3, pointBackgroundColor: '#6366f1' },
            { label: 'Visitantes', data: visitors, borderColor: '#10b981', backgroundColor: '#10b981', fill: false, tension: long ? .15 : .3, borderWidth: 2, pointRadius: long ? 0 : 2.5, pointBackgroundColor: '#10b981' }
        ]},
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + c.parsed.y.toLocaleString('pt-BR'); } } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 10, autoSkip: true } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', precision: 0 } }
            }
        }
    });
})();
</script>
@endpush
@endsection
