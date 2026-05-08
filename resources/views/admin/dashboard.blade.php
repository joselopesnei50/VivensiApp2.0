@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@php
    try {
        $totalActive = \App\Models\Tenant::where('subscription_status', 'active')->count();
    } catch (\Throwable $e) {
        $totalActive = $totalTenants ?? 0;
    }
@endphp

{{-- ── HEADER ── --}}
<div class="dash-header mb-4">
    <div>
        <p class="dash-eyebrow">Super Admin · Command Center</p>
        <h1 class="dash-title">Painel de <span class="dash-title-accent">Inteligência</span></h1>
        <p class="dash-sub">Métricas críticas de crescimento, retenção e saúde do ecossistema.</p>
    </div>
    <div class="dash-actions">
        <a href="{{ route('admin.health') }}" class="dash-btn-ghost">
            <i class="fas fa-microchip"></i> Status Infra
        </a>
        <a href="{{ url('/admin/tenants') }}" class="dash-btn-primary">
            <i class="fas fa-users-gear"></i> Gerenciar Tenants
        </a>
    </div>
</div>

{{-- ── KPI ROW ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="kpi-card kpi-indigo">
            <div class="kpi-icon"><i class="fas fa-circle-dollar-to-slot"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">MRR</div>
                <div class="kpi-value">R$ {{ number_format($mrr, 0, ',', '.') }}</div>
                <div class="kpi-sub"><i class="fas fa-chart-line me-1"></i>Receita recorrente</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="fas fa-building"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Tenants Totais</div>
                <div class="kpi-value">{{ $totalTenants }}</div>
                <div class="kpi-sub">{{ $totalActive }} ativos agora</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-card kpi-green">
            <div class="kpi-icon"><i class="fas fa-user-plus"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Novos ({{ now()->format('M') }})</div>
                <div class="kpi-value">+{{ $newClientsMonth }}</div>
                <div class="kpi-sub"><i class="fas fa-arrow-up me-1"></i>Este mês</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-card kpi-amber">
            <div class="kpi-icon"><i class="fas fa-door-open"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Churn Rate</div>
                <div class="kpi-value">{{ number_format($churnRate, 1) }}%</div>
                <div class="kpi-sub kpi-sub-warn"><i class="fas fa-triangle-exclamation me-1"></i>Retenção crítica</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-card kpi-emerald">
            <div class="kpi-icon"><i class="fas fa-signal"></i></div>
            <div class="kpi-body">
                <div class="kpi-label">Online Agora</div>
                <div class="kpi-value">{{ $onlineUsers }}</div>
                <div class="kpi-sub"><span class="pulse-dot"></span>Em sessão · {{ $totalUsers }} total</div>
            </div>
        </div>
    </div>
</div>

{{-- ── CHARTS ROW 1 ── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="exec-card h-100">
            <div class="exec-card-head">
                <div>
                    <div class="exec-card-title">Crescimento de Receita (MRR)</div>
                    <div class="exec-card-sub">Faturamento acumulado · últimos 6 meses</div>
                </div>
                <span class="badge-pill badge-indigo">LTM</span>
            </div>
            <div id="growthChart"></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="exec-card h-100">
            <div class="exec-card-head">
                <div>
                    <div class="exec-card-title">Distribuição de Planos</div>
                    <div class="exec-card-sub">Por volume de assinaturas</div>
                </div>
            </div>
            <div id="plansChart"></div>
        </div>
    </div>
</div>

{{-- ── CHARTS ROW 2 ── --}}
<div class="row g-3 mb-4">
    {{-- Churn Risk --}}
    <div class="col-lg-7">
        <div class="exec-card p-0 h-100">
            <div class="exec-card-head exec-head-danger px-4 py-3">
                <div class="exec-card-title" style="color:#9f1239;">
                    <i class="fas fa-triangle-exclamation me-2"></i>Radar de Risco de Churn
                </div>
                <span class="badge-pill badge-danger">Protocolo Ativo</span>
            </div>
            <div class="table-responsive">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Organização</th>
                            <th>Último Acesso</th>
                            <th>Risco</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($churnRiskUsers as $user)
                        @php
                            $days = $user->last_login_at ? $user->last_login_at->diffInDays() : 99;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="t-avatar t-av-red">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    <span class="cell-bold">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="cell-muted">{{ $user->tenant->name ?? '—' }}</td>
                            <td class="cell-muted">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Nunca acessou' }}
                            </td>
                            <td>
                                <span class="risk-badge {{ $days > 15 ? 'risk-critical' : 'risk-high' }}">
                                    {{ $days > 15 ? 'CRÍTICO' : 'ALTO' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="mailto:{{ $user->email }}" class="action-notify" title="Notificar por e-mail">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4" style="color:#94a3b8;">
                                <i class="fas fa-check-circle text-success d-block mb-2" style="font-size:1.4rem;"></i>
                                Nenhum risco detectado
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Acquisition Channel --}}
    <div class="col-lg-5">
        <div class="exec-card h-100">
            <div class="exec-card-head">
                <div>
                    <div class="exec-card-title">Aquisição por Canal</div>
                    <div class="exec-card-sub">Leads registrados por segmento</div>
                </div>
            </div>
            <div id="sourceChart"></div>
        </div>
    </div>
</div>

{{-- ── RECENT TENANTS + FUNNEL ── --}}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="exec-card p-0">
            <div class="exec-card-head px-4 py-3 border-bottom">
                <div>
                    <div class="exec-card-title">Novos Entrantes</div>
                    <div class="exec-card-sub">Últimas 5 organizações cadastradas</div>
                </div>
                <a href="{{ url('/admin/tenants') }}" class="dash-btn-ghost" style="font-size:.75rem;padding:7px 14px;">
                    Ver todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Organização</th>
                            <th>Plano</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th class="text-center">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTenants as $tenant)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="t-avatar t-av-blue">{{ strtoupper(substr($tenant->name, 0, 1)) }}</div>
                                    <span class="cell-bold">{{ $tenant->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge-pill badge-indigo">{{ $tenant->plan_type ?? 'Free' }}</span>
                            </td>
                            <td>
                                @if($tenant->subscription_status === 'active')
                                    <span class="badge-pill badge-green">Ativo</span>
                                @elseif($tenant->subscription_status === 'trialing')
                                    <span class="badge-pill badge-amber">Trial</span>
                                @else
                                    <span class="badge-pill badge-gray">{{ $tenant->subscription_status }}</span>
                                @endif
                            </td>
                            <td class="cell-muted">{{ $tenant->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="action-view">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4" style="color:#94a3b8;">Nenhuma organização encontrada</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Funnel by Vertical --}}
    <div class="col-lg-4">
        <div class="exec-card h-100">
            <div class="exec-card-head">
                <div>
                    <div class="exec-card-title">Funil por Vertical</div>
                    <div class="exec-card-sub">Trials iniciados por segmento</div>
                </div>
                <span class="badge-pill badge-gray">{{ $lpMetrics->sum('total_registrations') }} total</span>
            </div>
            @php
                $verticals = ['ngo' => 'Terceiro Setor', 'manager' => 'Gestão Social', 'personal' => 'Individual'];
            @endphp
            @foreach($verticals as $key => $label)
            @php
                $metric = $lpMetrics->where('page_key', $key)->first();
                $views  = (int)($metric->total_views ?? 0);
                $regs   = (int)($metric->total_registrations ?? 0);
                $rate   = $views > 0 ? ($regs / $views) * 100 : 0;
                $pct    = min($rate * 4, 100);
            @endphp
            <div class="funnel-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="funnel-label">{{ $label }}</span>
                    <span class="badge-pill badge-indigo">{{ number_format($rate, 1) }}% conv.</span>
                </div>
                <div class="funnel-bar-bg">
                    <div class="funnel-bar-fill" style="width: {{ $pct }}%"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="funnel-meta">{{ $views }} visitas</span>
                    <span class="funnel-meta">{{ $regs }} trials</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('styles')
<style>
/* ── HEADER ── */
.dash-header { display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap; }
.dash-eyebrow { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:4px; }
.dash-title { font-size:2.1rem;font-weight:900;color:#0f172a;letter-spacing:-1.5px;margin-bottom:4px;line-height:1.1; }
.dash-title-accent { color:#6366f1; }
.dash-sub { font-size:.88rem;color:#64748b;font-weight:500;margin:0; }
.dash-actions { display:flex;gap:10px;align-items:center;flex-shrink:0; }

.dash-btn-ghost {
    display:inline-flex;align-items:center;gap:7px;
    padding:9px 18px;border-radius:10px;font-size:.82rem;font-weight:700;
    border:1.5px solid #e2e8f0;background:white;color:#475569;
    text-decoration:none;transition:all .2s;
}
.dash-btn-ghost:hover { background:#f8fafc;border-color:#cbd5e1;color:#1e293b; }

.dash-btn-primary {
    display:inline-flex;align-items:center;gap:7px;
    padding:9px 18px;border-radius:10px;font-size:.82rem;font-weight:700;
    background:#6366f1;color:white;text-decoration:none;transition:all .2s;
}
.dash-btn-primary:hover { background:#4f46e5;color:white;transform:translateY(-1px); }

/* ── KPI CARDS ── */
.kpi-card {
    border-radius:16px;padding:18px 20px;
    display:flex;align-items:center;gap:14px;
    border:1px solid transparent;
    transition:transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,0,0,.07); }
.kpi-icon {
    width:42px;height:42px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;flex-shrink:0;
}
.kpi-label { font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;margin-bottom:2px; }
.kpi-value { font-size:1.5rem;font-weight:900;letter-spacing:-.03em;line-height:1.1; }
.kpi-sub   { font-size:.7rem;font-weight:600;margin-top:3px; }
.kpi-sub-warn { color:#ef4444 !important; }

.kpi-indigo { background:#eef2ff;border-color:#c7d2fe; }
.kpi-indigo .kpi-icon { background:#6366f1;color:white; }
.kpi-indigo .kpi-label { color:#6366f1; }
.kpi-indigo .kpi-value { color:#3730a3; }
.kpi-indigo .kpi-sub { color:#818cf8; }

.kpi-blue { background:#eff6ff;border-color:#bfdbfe; }
.kpi-blue .kpi-icon { background:#3b82f6;color:white; }
.kpi-blue .kpi-label { color:#3b82f6; }
.kpi-blue .kpi-value { color:#1d4ed8; }
.kpi-blue .kpi-sub { color:#60a5fa; }

.kpi-green { background:#f0fdf4;border-color:#bbf7d0; }
.kpi-green .kpi-icon { background:#22c55e;color:white; }
.kpi-green .kpi-label { color:#16a34a; }
.kpi-green .kpi-value { color:#15803d; }
.kpi-green .kpi-sub { color:#4ade80; }

.kpi-amber { background:#fffbeb;border-color:#fde68a; }
.kpi-amber .kpi-icon { background:#f59e0b;color:white; }
.kpi-amber .kpi-label { color:#d97706; }
.kpi-amber .kpi-value { color:#b45309; }
.kpi-amber .kpi-sub { color:#f59e0b; }

.kpi-emerald { background:#ecfdf5;border-color:#a7f3d0; }
.kpi-emerald .kpi-icon { background:#10b981;color:white; }
.kpi-emerald .kpi-label { color:#059669; }
.kpi-emerald .kpi-value { color:#047857; }
.kpi-emerald .kpi-sub { color:#34d399; }

.pulse-dot {
    display:inline-block;width:7px;height:7px;
    background:#10b981;border-radius:50%;margin-right:4px;
    animation:kpiPulse 2s infinite;
}
@keyframes kpiPulse {
    0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4);}
    50%{box-shadow:0 0 0 5px rgba(16,185,129,0);}
}

/* ── EXEC CARD ── */
.exec-card {
    background:white;border:1px solid #f1f5f9;
    border-radius:20px;padding:26px;
    box-shadow:0 4px 18px rgba(0,0,0,.03);
}
.exec-card-head { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px; }
.exec-card-title { font-size:.92rem;font-weight:800;color:#0f172a;letter-spacing:-.02em; }
.exec-card-sub { font-size:.73rem;color:#94a3b8;font-weight:500;margin-top:2px; }
.exec-head-danger { background:#fff1f2;border-bottom:1px solid #fee2e2;border-radius:20px 20px 0 0;margin-bottom:0; }

/* ── BADGES ── */
.badge-pill {
    display:inline-flex;align-items:center;
    padding:3px 10px;border-radius:20px;
    font-size:.67rem;font-weight:700;white-space:nowrap;
}
.badge-indigo { background:#eef2ff;color:#4f46e5; }
.badge-green  { background:#f0fdf4;color:#16a34a; }
.badge-amber  { background:#fffbeb;color:#d97706; }
.badge-gray   { background:#f8fafc;color:#64748b; }
.badge-danger { background:#9f1239;color:white; }

/* ── TABLES ── */
.dash-table { width:100%;border-collapse:collapse; }
.dash-table thead th {
    padding:11px 16px;text-align:left;
    font-size:.67rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.08em;color:#94a3b8;
    border-bottom:1px solid #f1f5f9;
}
.dash-table tbody td { padding:13px 16px;font-size:.82rem;color:#475569;border-bottom:1px solid #f8fafc; }
.dash-table tbody tr:last-child td { border-bottom:none; }
.dash-table tbody tr:hover td { background:#fafbff; }
.cell-bold { font-weight:700;color:#0f172a; }
.cell-muted { color:#94a3b8; }

.t-avatar {
    width:30px;height:30px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:.7rem;font-weight:800;flex-shrink:0;
}
.t-av-red  { background:#fee2e2;color:#dc2626; }
.t-av-blue { background:#eff6ff;color:#3b82f6; }

.risk-badge {
    display:inline-flex;align-items:center;
    padding:3px 9px;border-radius:8px;
    font-size:.65rem;font-weight:900;letter-spacing:.06em;
}
.risk-critical { background:#fee2e2;color:#dc2626; }
.risk-high     { background:#fff7ed;color:#d97706; }

.action-notify, .action-view {
    width:30px;height:30px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.78rem;text-decoration:none;transition:all .15s;
}
.action-notify { background:#eef2ff;color:#6366f1; }
.action-notify:hover { background:#6366f1;color:white; }
.action-view { background:#f1f5f9;color:#475569; }
.action-view:hover { background:#e0e7ff;color:#4f46e5; }

/* ── FUNNEL ── */
.funnel-item { margin-bottom:22px; }
.funnel-item:last-child { margin-bottom:0; }
.funnel-label { font-size:.82rem;font-weight:700;color:#1e293b; }
.funnel-bar-bg { height:6px;background:#f1f5f9;border-radius:6px;overflow:hidden; }
.funnel-bar-fill { height:100%;background:linear-gradient(90deg,#6366f1,#818cf8);border-radius:6px;transition:width .6s ease; }
.funnel-meta { font-size:.7rem;color:#94a3b8;font-weight:500; }
</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Growth Area Chart ──────────────────────────────────────
    new ApexCharts(document.querySelector('#growthChart'), {
        series: [{ name: 'MRR (R$)', data: {!! json_encode($growthValues) !!} }],
        chart: {
            type: 'area', height: 300,
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: false }, zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeOut', speed: 700 }
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3, colors: ['#6366f1'] },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1, opacityFrom: .45, opacityTo: .02,
                stops: [0, 90, 100],
                colorStops: [
                    { offset: 0, color: '#6366f1', opacity: .4 },
                    { offset: 100, color: '#6366f1', opacity: 0 }
                ]
            }
        },
        xaxis: {
            categories: {!! json_encode($growthLabels) !!},
            labels: { style: { colors: '#94a3b8', fontWeight: 600, fontSize: '11px' } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#94a3b8', fontWeight: 600, fontSize: '11px' },
                formatter: function (val) { return 'R$ ' + Number(val).toLocaleString('pt-BR'); }
            }
        },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 5, padding: { left: 10, right: 10 } },
        colors: ['#6366f1'],
        markers: { size: 5, colors: ['#fff'], strokeColors: '#6366f1', strokeWidth: 2 },
        tooltip: {
            theme: 'light',
            y: { formatter: function (val) { return 'R$ ' + Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 }); } }
        }
    }).render();

    // ── Plan Distribution Donut ────────────────────────────────
    new ApexCharts(document.querySelector('#plansChart'), {
        series: {!! json_encode(array_map('intval', $planDistribution->pluck('count')->toArray())) !!},
        chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
        labels: {!! json_encode($planDistribution->pluck('name')->toArray()) !!},
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
        stroke: { show: false },
        plotOptions: { pie: { donut: { size: '74%', labels: {
            show: true,
            total: {
                show: true, label: 'TOTAL',
                fontSize: '11px', fontWeight: 700, color: '#94a3b8',
                formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); }
            }
        } } } },
        legend: {
            position: 'bottom', fontSize: '12px', fontWeight: 700,
            labels: { colors: '#64748b' },
            markers: { radius: 4, width: 10, height: 10 }
        },
        tooltip: { theme: 'light', y: { formatter: function (val) { return val + ' tenant(s)'; } } }
    }).render();

    // ── Acquisition Source Donut ───────────────────────────────
    @if($leadSourceData->count())
    new ApexCharts(document.querySelector('#sourceChart'), {
        series: {!! json_encode(array_map('intval', $leadSourceData->pluck('count')->toArray())) !!},
        chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
        labels: {!! json_encode($leadSourceData->pluck('page_key')->toArray()) !!},
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899'],
        stroke: { show: false },
        plotOptions: { pie: { donut: { size: '74%', labels: {
            show: true,
            total: { show: true, label: 'LEADS', fontSize: '11px', fontWeight: 700, color: '#94a3b8' }
        } } } },
        legend: {
            position: 'bottom', fontSize: '12px', fontWeight: 700,
            labels: { colors: '#64748b' },
            markers: { radius: 4, width: 10, height: 10 }
        },
        tooltip: { theme: 'light' }
    }).render();
    @else
    document.querySelector('#sourceChart').innerHTML = '<div style="text-align:center;padding:60px 20px;color:#94a3b8;font-size:.85rem;"><i class="fas fa-chart-pie" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.3;"></i>Sem dados de leads ainda</div>';
    @endif
});
</script>

@endsection
