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
            <div class="exec-card-head px-4 py-3" style="border-bottom:1px solid rgba(148,163,184,.14);">
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

{{-- ── ÚLTIMAS CAMPANHAS DE E-MAIL ── --}}
<div class="row g-3 mb-4" style="margin-top: 32px !important;">
    <div class="col-12">
        <div class="exec-card p-0">
            <div class="exec-card-head px-4 py-3" style="border-bottom:1px solid rgba(148,163,184,.14);">
                <div>
                    <div class="exec-card-title"><i class="fas fa-bullhorn me-2" style="color:#6366f1;"></i>Últimas Campanhas de E-mail</div>
                    <div class="exec-card-sub">Performance de entregabilidade · Brevo Campaign API</div>
                </div>
                <a href="{{ route('admin.email_campaigns.create') }}" class="dash-btn-primary" style="font-size:.78rem;padding:8px 16px;">
                    <i class="fas fa-plus me-1"></i> Nova Campanha
                </a>
            </div>

            @if($latestCampaigns->isEmpty())
                <div style="text-align:center;padding:48px 20px;color:#94a3b8;">
                    <i class="fas fa-envelope-open" style="font-size:2.5rem;display:block;margin-bottom:14px;opacity:0.3;"></i>
                    <p style="font-weight:700;margin:0 0 16px;">Nenhuma campanha criada ainda.</p>
                    <a href="{{ route('admin.email_campaigns.create') }}" class="dash-btn-primary" style="font-size:.82rem;">
                        Criar primeira campanha
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Campanha</th>
                                <th>Público</th>
                                <th class="text-center">Destinatários</th>
                                <th class="text-center">Entregues</th>
                                <th class="text-center">Abertura</th>
                                <th class="text-center">Cliques</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestCampaigns as $c)
                            @php
                                $openRate  = ($c->stat_delivered && $c->stat_opens)  ? round($c->stat_opens  / $c->stat_delivered * 100, 1) : null;
                                $clickRate = ($c->stat_delivered && $c->stat_clicks) ? round($c->stat_clicks / $c->stat_delivered * 100, 1) : null;
                                $stMap = [
                                    'draft'    => ['badge-gray',   'Rascunho'],
                                    'sending'  => ['badge-amber',  'Enviando'],
                                    'sent'     => ['badge-green',  'Enviada'],
                                    'error'    => ['badge-red',    'Erro'],
                                    'scheduled'=> ['badge-indigo', 'Agendada'],
                                ];
                                [$stClass, $stLabel] = $stMap[$c->status] ?? ['badge-gray', $c->status];
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-bold" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $c->name }}</div>
                                    <div class="cell-muted" style="font-size:.7rem;margin-top:2px;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $c->subject }}</div>
                                </td>
                                <td><span class="cell-muted" style="font-size:.78rem;">{{ $c->audienceLabel() }}</span></td>
                                <td class="text-center">
                                    <span class="cell-bold">{{ $c->recipient_count ?: '—' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="cell-bold">{{ $c->stat_delivered ? number_format($c->stat_delivered) : '—' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($openRate !== null)
                                        <span style="font-weight:800;color:{{ $openRate >= 20 ? '#059669' : ($openRate >= 10 ? '#d97706' : '#ef4444') }};font-size:.88rem;">
                                            {{ $openRate }}%
                                        </span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($clickRate !== null)
                                        <span style="font-weight:800;color:#3b82f6;font-size:.88rem;">{{ $clickRate }}%</span>
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge-pill {{ $stClass }}">{{ $stLabel }}</span>
                                </td>
                                <td class="text-center">
                                    <div style="display:flex;gap:6px;justify-content:center;align-items:center;">
                                        <a href="{{ route('admin.email_campaigns.show', $c) }}"
                                           style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:.78rem;transition:background .15s;"
                                           onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"
                                           title="Ver campanha">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($c->status === 'draft')
                                        <form action="{{ route('admin.email_campaigns.send', $c) }}" method="POST"
                                              onsubmit="return confirm('Disparar campanha?')">
                                            @csrf
                                            <button type="submit"
                                                    style="width:30px;height:30px;border-radius:8px;background:#eff6ff;color:#3b82f6;border:none;cursor:pointer;font-size:.78rem;transition:background .15s;"
                                                    onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'"
                                                    title="Disparar agora">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @if($c->status === 'sent' && $c->brevo_campaign_id)
                                        <form action="{{ route('admin.email_campaigns.stats', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    style="width:30px;height:30px;border-radius:8px;background:#f0fdf4;color:#059669;border:none;cursor:pointer;font-size:.78rem;"
                                                    title="Atualizar métricas">
                                                <i class="fas fa-arrow-rotate-right"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding:14px 20px;border-top:1px solid #f8fafc;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:.75rem;color:#94a3b8;">Exibindo as {{ $latestCampaigns->count() }} campanhas mais recentes</span>
                    <a href="{{ route('admin.email_campaigns.index') }}" class="dash-btn-ghost" style="font-size:.75rem;padding:6px 14px;">
                        Ver todas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif
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
.badge-red    { background:#fef2f2;color:#dc2626; }
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
<style>
body {
    background:
        radial-gradient(circle at top left, rgba(79, 70, 229, .12), transparent 28%),
        radial-gradient(circle at top right, rgba(16, 185, 129, .08), transparent 24%),
        #0b1220;
}

.dash-header {
    padding: 28px 30px;
    margin-bottom: 24px;
    border: 1px solid rgba(148,163,184,.16);
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.84));
    box-shadow: 0 24px 60px rgba(2,6,23,.28);
}
.dash-eyebrow {
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#93c5fd;
    letter-spacing:1.7px;
}
.dash-eyebrow::before {
    content:'';
    width:7px;height:7px;border-radius:50%;
    background:#60a5fa;
    box-shadow:0 0 0 4px rgba(96,165,250,.12);
}
.dash-title { color:#f8fafc; font-size: clamp(2rem, 2.8vw, 2.65rem); letter-spacing:-1.8px; }
.dash-title-accent { color:#7c3aed; }
.dash-sub { color:#cbd5e1; max-width:820px; }

.dash-btn-ghost,
.dash-btn-primary {
    border-radius:12px;
    font-weight:800;
    padding:10px 18px;
}
.dash-btn-ghost {
    border:1px solid rgba(148,163,184,.22);
    background: rgba(15,23,42,.68);
    color:#e2e8f0;
}
.dash-btn-ghost:hover {
    background: rgba(30,41,59,.95);
    border-color: rgba(148,163,184,.36);
    color:#fff;
}
.dash-btn-primary {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    box-shadow: 0 14px 30px rgba(79,70,229,.25);
}
.dash-btn-primary:hover {
    transform:translateY(-1px);
    box-shadow: 0 18px 36px rgba(79,70,229,.3);
}

.kpi-card,
.exec-card {
    background: linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.88));
    border:1px solid rgba(148,163,184,.16);
    box-shadow: 0 18px 42px rgba(2,6,23,.18);
}
.kpi-card:hover,
.exec-card:hover {
    border-color: rgba(99,102,241,.32);
}
.kpi-value,
.cell-bold,
.exec-card-title {
    color:#f8fafc;
}
.kpi-sub,
.exec-card-sub,
.cell-muted,
.funnel-meta {
    color:#94a3b8 !important;
}

.kpi-indigo .kpi-icon,
.kpi-blue .kpi-icon,
.kpi-green .kpi-icon,
.kpi-amber .kpi-icon,
.kpi-emerald .kpi-icon {
    color:#fff;
}

.badge-indigo { background: rgba(99,102,241,.14); color:#c7d2fe; border:1px solid rgba(99,102,241,.22); }
.badge-green  { background: rgba(34,197,94,.14); color:#bbf7d0; border:1px solid rgba(34,197,94,.22); }
.badge-amber  { background: rgba(245,158,11,.14); color:#fde68a; border:1px solid rgba(245,158,11,.22); }
.badge-gray   { background: rgba(148,163,184,.14); color:#cbd5e1; border:1px solid rgba(148,163,184,.18); }
.badge-red    { background: rgba(239,68,68,.14); color:#fecaca; border:1px solid rgba(239,68,68,.22); }
.badge-danger { background:#dc2626;color:white; }

.dash-table thead th {
    color:#94a3b8;
    border-bottom:1px solid rgba(148,163,184,.14);
    background: rgba(15,23,42,.3);
}
.dash-table tbody td {
    color:#cbd5e1;
    border-bottom:1px solid rgba(148,163,184,.1);
}
.dash-table tbody tr:hover td { background: rgba(30,41,59,.5); }

.t-av-red  { background: linear-gradient(135deg,#7f1d1d,#ef4444); color:#fff; }
.t-av-blue { background: linear-gradient(135deg,#1d4ed8,#60a5fa); color:#fff; }

.risk-critical { background: rgba(239,68,68,.14); color:#fecaca; border:1px solid rgba(239,68,68,.2); }
.risk-high     { background: rgba(245,158,11,.14); color:#fde68a; border:1px solid rgba(245,158,11,.2); }

.action-notify {
    background: rgba(99,102,241,.14);
    color:#c7d2fe;
    border: 1px solid rgba(99,102,241,.18);
}
.action-notify:hover { background:#6366f1;color:white; }
.action-view {
    background: rgba(148,163,184,.12);
    color:#e2e8f0;
}
.action-view:hover { background:#1e293b;color:#fff; }

.exec-head-danger {
    background: rgba(220,38,38,.12);
    border-bottom: 1px solid rgba(220,38,38,.2);
}
.exec-head-danger .exec-card-title { color:#fca5a5 !important; }

.funnel-bar-bg {
    height:8px;
    background: rgba(148,163,184,.14);
    border-radius:999px;
}
.funnel-bar-fill {
    background: linear-gradient(90deg,#6366f1,#8b5cf6);
    box-shadow: 0 0 18px rgba(99,102,241,.25);
}

@media (max-width: 991.98px) {
    .dash-header { padding: 22px; }
    .exec-card { padding: 20px; }
}

@media (max-width: 767.98px) {
    .dash-actions { width:100%; flex-wrap:wrap; }
    .dash-btn-ghost, .dash-btn-primary { width:100%; justify-content:center; }
    .exec-card-head { flex-direction:column; }
}
</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Growth Area Chart ──────────────────────────────────────
    new ApexCharts(document.querySelector('#growthChart'), {
        series: [{ name: 'MRR (R$)', data: {!! json_encode($growthValues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!} }],
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
            categories: {!! json_encode($growthLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
            labels: { style: { colors: '#94a3b8', fontWeight: 600, fontSize: '11px' } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: '#94a3b8', fontWeight: 600, fontSize: '11px' },
                formatter: function (val) { return 'R$ ' + Number(val).toLocaleString('pt-BR'); }
            }
        },
        grid: { borderColor: 'rgba(148,163,184,.12)', strokeDashArray: 5, padding: { left: 10, right: 10 } },
        colors: ['#6366f1'],
        markers: { size: 5, colors: ['#1e293b'], strokeColors: '#6366f1', strokeWidth: 2 },
        tooltip: {
            theme: 'dark',
            y: { formatter: function (val) { return 'R$ ' + Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 }); } }
        }
    }).render();

    // ── Plan Distribution Donut ────────────────────────────────
    new ApexCharts(document.querySelector('#plansChart'), {
        series: {!! json_encode(array_map('intval', $planDistribution->pluck('count')->toArray()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
        chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
        labels: {!! json_encode($planDistribution->pluck('name')->toArray(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
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
            labels: { colors: '#94a3b8' },
            markers: { radius: 4, width: 10, height: 10 }
        },
        tooltip: { theme: 'dark', y: { formatter: function (val) { return val + ' tenant(s)'; } } }
    }).render();

    // ── Acquisition Source Donut ───────────────────────────────
    @if($leadSourceData->count())
    new ApexCharts(document.querySelector('#sourceChart'), {
        series: {!! json_encode(array_map('intval', $leadSourceData->pluck('count')->toArray()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
        chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
        labels: {!! json_encode($leadSourceData->pluck('page_key')->toArray(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899'],
        stroke: { show: false },
        plotOptions: { pie: { donut: { size: '74%', labels: {
            show: true,
            total: { show: true, label: 'LEADS', fontSize: '11px', fontWeight: 700, color: '#94a3b8' }
        } } } },
        legend: {
            position: 'bottom', fontSize: '12px', fontWeight: 700,
            labels: { colors: '#94a3b8' },
            markers: { radius: 4, width: 10, height: 10 }
        },
        tooltip: { theme: 'dark' }
    }).render();
    @else
    document.querySelector('#sourceChart').innerHTML = '<div style="text-align:center;padding:60px 20px;color:#94a3b8;font-size:.85rem;"><i class="fas fa-chart-pie" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.3;"></i>Sem dados de leads ainda</div>';
    @endif
});
</script>

@endsection
