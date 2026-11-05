@extends('layouts.app')

@section('content')
@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $routeFor = fn ($d) => $post
        ? route('admin.blog.post_metrics', ['post' => $post->id, 'dias' => $d])
        : route('admin.blog.metrics', ['dias' => $d]);
    $changeBadge = function ($c) {
        if ($c === null) return '<span class="bm-chg bm-new">novo</span>';
        if ($c > 0)  return '<span class="bm-chg bm-up"><i class="fas fa-arrow-up"></i> ' . number_format($c, 1, ',', '.') . '%</span>';
        if ($c < 0)  return '<span class="bm-chg bm-down"><i class="fas fa-arrow-down"></i> ' . number_format(abs($c), 1, ',', '.') . '%</span>';
        return '<span class="bm-chg bm-flat">0%</span>';
    };
    $sourceColors = [
        'google' => '#4285f4', 'search' => '#0ea5e9', 'instagram' => '#e1306c', 'facebook' => '#1877f2',
        'linkedin' => '#0a66c2', 'whatsapp' => '#25d366', 'x' => '#0f172a', 'youtube' => '#ff0000',
        'internal' => '#6366f1', 'email' => '#f59e0b', 'ai' => '#8b5cf6', 'direct' => '#64748b', 'other' => '#94a3b8',
    ];
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1" style="min-width:0;">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            <a href="{{ route('admin.blog.index') }}" style="color:#94a3b8;text-decoration:none;">Admin / Blog CMS</a>
            @if($post) / <a href="{{ route('admin.blog.metrics', ['dias' => $m['days']]) }}" style="color:#94a3b8;text-decoration:none;">Métricas</a> @endif
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">
            {{ $post ? $post->title : 'Métricas do Blog' }}
        </h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">
            {{ $m['start']->format('d/m/Y') }} a {{ $m['end']->format('d/m/Y') }} · só visitas de pessoas (robôs e pré-visualizações de links ficam de fora)
            @if($post && $post->is_published)
                · <a href="{{ url('/blog/' . $post->slug) }}" target="_blank" rel="noopener">ver post <i class="fas fa-arrow-up-right-from-square" style="font-size:.65rem;"></i></a>
            @endif
        </p>
    </div>
    <div class="bm-periods flex-shrink-0" role="group" aria-label="Período">
        @foreach($periods as $d => $label)
            <a href="{{ $routeFor($d) }}" class="{{ $m['days'] === $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Visitas</div>
            <div class="bm-kpi-value">{{ $fmt($m['views']) }}</div>
            <div class="bm-kpi-sub">{!! $changeBadge($m['change']) !!} vs. {{ $fmt($m['prev_views']) }} no período anterior</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="bm-kpi-label">Leitores únicos</div>
            <div class="bm-kpi-value">{{ $fmt($m['readers']) }}</div>
            <div class="bm-kpi-sub">
                @if($m['readers'] > 0)
                    {{ number_format($m['views'] / $m['readers'], 1, ',', '.') }} visitas por leitor
                @else
                    nenhum leitor no período
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            @if($post)
                <div class="bm-kpi-label">Desde a publicação</div>
                <div class="bm-kpi-value">{{ $fmt($m['all_time']) }}</div>
                <div class="bm-kpi-sub">visitas de pessoas no total</div>
            @else
                <div class="bm-kpi-label">Posts publicados</div>
                <div class="bm-kpi-value">{{ $fmt($m['published']) }}</div>
                <div class="bm-kpi-sub">{{ $fmt($m['all_time']) }} visitas no total do blog</div>
            @endif
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100" style="background:#f8fafc;">
            <div class="bm-kpi-label">Robôs filtrados</div>
            <div class="bm-kpi-value" style="color:#94a3b8;">{{ $fmt($m['bots']) }}</div>
            <div class="bm-kpi-sub">Google, pré-visualização do WhatsApp etc. Não entram nas contas.</div>
        </div>
    </div>
</div>

{{-- Gráfico diário --}}
<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-800 mb-0" style="color:#0f172a;">Visitas por dia</h6>
        <span style="font-size:.75rem;color:#94a3b8;">pico: {{ $fmt(max($m['daily']['values'] ?: [0])) }} em um dia</span>
    </div>
    <div style="position:relative;height:260px;">
        <canvas id="bmDaily" aria-label="Visitas por dia" role="img"></canvas>
    </div>
</div>

{{-- Origem e dispositivo --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">De onde vêm os leitores</h6>
            @forelse($m['sources'] as $s)
                <div class="bm-bar-row">
                    <div class="bm-bar-label">{{ $s['label'] }}</div>
                    <div class="bm-bar-track">
                        <div class="bm-bar-fill" style="width:{{ max(1, $s['pct']) }}%;background:{{ $sourceColors[$s['key']] ?? '#94a3b8' }};"></div>
                    </div>
                    <div class="bm-bar-num">{{ $fmt($s['count']) }} <span>{{ number_format($s['pct'], 1, ',', '.') }}%</span></div>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size:.85rem;">Sem visitas no período.</p>
            @endforelse
            @if(collect($m['sources'])->firstWhere('key', 'direct'))
                <p class="mb-0 mt-2" style="font-size:.72rem;color:#94a3b8;">
                    "Direto" inclui links abertos de dentro de apps (WhatsApp, Instagram), que nem sempre informam a origem.
                </p>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 h-100">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">Dispositivo</h6>
            @forelse($m['devices'] as $d)
                <div class="bm-bar-row">
                    <div class="bm-bar-label">
                        <i class="fas {{ ['mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button', 'desktop' => 'fa-desktop'][$d['key']] ?? 'fa-desktop' }} me-1" style="color:#94a3b8;width:14px;"></i>{{ $d['label'] }}
                    </div>
                    <div class="bm-bar-track">
                        <div class="bm-bar-fill" style="width:{{ max(1, $d['pct']) }}%;background:#6366f1;"></div>
                    </div>
                    <div class="bm-bar-num">{{ number_format($d['pct'], 0, ',', '.') }}%</div>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size:.85rem;">Sem visitas no período.</p>
            @endforelse

            @if(count($m['top_referers']))
                <h6 class="fw-800 mb-2 mt-4" style="color:#0f172a;font-size:.85rem;">Outros sites que mandaram leitores</h6>
                <ul class="list-unstyled mb-0" style="font-size:.82rem;">
                    @foreach($m['top_referers'] as $host => $count)
                        <li class="d-flex justify-content-between py-1" style="border-bottom:1px solid #f1f5f9;">
                            <span class="text-truncate me-2" style="color:#334155;">{{ $host }}</span>
                            <span class="fw-bold" style="color:#64748b;">{{ $fmt($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

{{-- Ranking de posts --}}
@unless($post)
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="p-3 p-md-4 pb-0">
        <h6 class="fw-800 mb-1" style="color:#0f172a;">Posts mais lidos no período</h6>
        <p class="mb-3" style="font-size:.78rem;color:#94a3b8;">Clique num post para ver as métricas dele.</p>
    </div>
    @if(count($m['top_posts']))
        <div class="table-responsive">
            <table class="table align-middle mb-0 bm-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:40px;">#</th>
                        <th>Post</th>
                        <th class="text-end">Visitas</th>
                        <th class="text-end d-none d-md-table-cell">Leitores</th>
                        <th class="text-end">Variação</th>
                        <th class="d-none d-lg-table-cell">Principal origem</th>
                        <th class="pe-4 d-none d-md-table-cell" style="width:160px;">Participação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($m['top_posts'] as $i => $row)
                        <tr>
                            <td class="ps-4" style="color:#94a3b8;font-weight:700;">{{ $i + 1 }}</td>
                            <td class="bm-title-cell">
                                <a href="{{ route('admin.blog.post_metrics', ['post' => $row['post']->id, 'dias' => $m['days']]) }}"
                                   class="fw-bold d-block" style="color:#0f172a;text-decoration:none;">{{ $row['post']->title }}</a>
                                @unless($row['post']->is_published)
                                    <span style="font-size:.68rem;color:#94a3b8;">rascunho</span>
                                @endunless
                            </td>
                            <td class="text-end fw-800" style="color:#4f46e5;">{{ $fmt($row['views']) }}</td>
                            <td class="text-end d-none d-md-table-cell" style="color:#475569;">{{ $fmt($row['readers']) }}</td>
                            <td class="text-end">{!! $changeBadge($row['change']) !!}</td>
                            <td class="d-none d-lg-table-cell" style="font-size:.82rem;color:#475569;">{{ $row['top_source'] }}</td>
                            <td class="pe-4 d-none d-md-table-cell">
                                <div class="bm-bar-track" title="{{ number_format($row['share'], 1, ',', '.') }}%">
                                    <div class="bm-bar-fill" style="width:{{ max(1, $row['share']) }}%;background:#6366f1;"></div>
                                </div>
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
@endunless

<p style="font-size:.72rem;color:#94a3b8;">
    Uma visita = uma pessoa (endereço de internet) abrindo o post num dia. Quem lê o mesmo post várias vezes no mesmo dia conta uma vez.
</p>

@push('styles')
<style>
.bm-periods { display:inline-flex;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:3px;gap:2px; }
.bm-periods a { padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:700;color:#64748b;text-decoration:none;white-space:nowrap; }
.bm-periods a.active { background:#eef2ff;color:#4338ca; }
.bm-kpi-label { font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700; }
.bm-kpi-value { font-size:1.75rem;font-weight:800;color:#0f172a;line-height:1.15;margin:4px 0; font-variant-numeric:tabular-nums; }
.bm-kpi-sub { font-size:.74rem;color:#64748b; }
.bm-chg { display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:20px;font-size:.7rem;font-weight:800;white-space:nowrap; }
.bm-chg i { font-size:.6rem; }
.bm-up   { background:#dcfce7;color:#166534; }
.bm-down { background:#fee2e2;color:#991b1b; }
.bm-flat { background:#f1f5f9;color:#64748b; }
.bm-new  { background:#e0e7ff;color:#3730a3; }
.bm-bar-row { display:flex;align-items:center;gap:10px;margin-bottom:9px; }
.bm-bar-label { width:150px;flex-shrink:0;font-size:.82rem;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.bm-bar-track { flex:1;height:8px;background:#f1f5f9;border-radius:6px;overflow:hidden; }
.bm-bar-fill { height:100%;border-radius:6px; }
.bm-bar-num { width:92px;flex-shrink:0;text-align:right;font-size:.8rem;font-weight:800;color:#0f172a;font-variant-numeric:tabular-nums; }
.bm-bar-num span { font-weight:600;color:#94a3b8;margin-left:4px; }
.bm-table thead th { font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;background:#f8fafc;border-bottom:1px solid #e2e8f0; }
.bm-table td { font-size:.86rem; font-variant-numeric:tabular-nums; }
.bm-title-cell { min-width:220px; }
@media (max-width: 575px) {
    .bm-bar-label { width:118px; } .bm-bar-num { width:80px; } .bm-bar-row { gap:8px; }
    .bm-periods a { padding:6px 9px; }
    .bm-title-cell { min-width:0; } .bm-table td, .bm-table th { padding-left:.5rem; padding-right:.5rem; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var el = document.getElementById('bmDaily');
    if (!el || typeof Chart === 'undefined') return;
    var labels = @json($m['daily']['labels']);
    var values = @json($m['daily']['values']);
    var ctx = el.getContext('2d');
    var grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(99,102,241,.25)');
    grad.addColorStop(1, 'rgba(99,102,241,0)');
    new Chart(el, {
        type: 'line',
        data: { labels: labels, datasets: [{
            label: 'Visitas', data: values, borderColor: '#6366f1', backgroundColor: grad,
            fill: true, tension: values.length > 45 ? .15 : .3, borderWidth: 2,
            pointRadius: values.length > 45 ? 0 : 3, pointHoverRadius: 5, pointBackgroundColor: '#6366f1'
        }]},
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return ' ' + c.parsed.y.toLocaleString('pt-BR') + ' visita(s)'; } } } },
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
