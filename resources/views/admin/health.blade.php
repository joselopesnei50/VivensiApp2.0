@extends('layouts.app')

@push('styles')
{{-- Auto-refresh silencioso a cada 60s. Cache no controller garante que
     recarregar nao gera carga (bater 2x na mesma janela reusa snapshot). --}}
<meta http-equiv="refresh" content="60">
<style>
/* ── Health Dashboard ─────────────────────────────────────────────── */
.health-page { background: #f1f5f9; min-height: 100vh; }

/* Header */
.health-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 16px; margin-bottom: 32px;
}
.health-header-title { display: flex; flex-direction: column; gap: 4px; }
.health-kicker {
    font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 2px; color: #6366f1;
    display: flex; align-items: center; gap: 8px;
}
.live-pulse {
    width: 8px; height: 8px; border-radius: 50%; background: #10b981;
    animation: livePulse 2s ease-in-out infinite;
}
@keyframes livePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.6); }
    50%       { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
}
.health-main-title {
    font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; line-height: 1.1;
}
.health-subtitle { font-size: 0.82rem; color: #64748b; margin-top: 2px; }
.health-actions { display: flex; align-items: center; gap: 10px; }
.health-timestamp {
    font-size: 0.72rem; color: #94a3b8; font-weight: 600;
    background: white; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 6px 12px; display: flex; align-items: center; gap: 6px;
}
.btn-refresh {
    display: flex; align-items: center; gap: 8px;
    background: #6366f1; color: white; border: none;
    border-radius: 10px; padding: 8px 18px;
    font-size: 0.8rem; font-weight: 700; cursor: pointer;
    transition: all .2s; text-decoration: none;
}
.btn-refresh:hover { background: #4f46e5; color: white; transform: translateY(-1px); }

/* Section label */
.section-label {
    font-size: 0.62rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 2px; color: #94a3b8; margin-bottom: 14px;
    display: flex; align-items: center; gap: 10px;
}
.section-label::after {
    content: ''; flex: 1; height: 1px; background: #e2e8f0;
}

/* ── Security Block ──────────────────────────────────────────────── */
.security-block {
    display: grid; grid-template-columns: 260px 1fr; gap: 20px;
    background: white; border-radius: 20px;
    border: 1px solid #e2e8f0;
    overflow: hidden; margin-bottom: 20px;
}
.security-gauge-panel {
    background: linear-gradient(145deg, #f8fafc, #eef2ff);
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 36px 24px; gap: 16px;
    border-right: 1px solid #e2e8f0;
}
.gauge-wrap { position: relative; width: 150px; height: 150px; }
.gauge-wrap svg { transform: rotate(-90deg); }
.gauge-center {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    text-align: center;
}
.gauge-pct { font-size: 2.2rem; font-weight: 900; color: #0f172a; line-height: 1; }
.gauge-label { font-size: 0.62rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
.gauge-title { font-size: 0.95rem; font-weight: 800; color: #1e293b; }
.gauge-desc { font-size: 0.75rem; color: #64748b; text-align: center; line-height: 1.5; max-width: 180px; }

.security-controls-panel { padding: 28px; }
.security-controls-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px;
}
.security-controls-title {
    font-size: 0.88rem; font-weight: 800; color: #1e293b;
    display: flex; align-items: center; gap: 8px;
}
.badge-score {
    font-size: 0.7rem; font-weight: 800; padding: 3px 10px;
    border-radius: 20px; background: #dcfce7; color: #166534;
}
.badge-score.warn { background: #fef9c3; color: #854d0e; }
.badge-score.danger { background: #fee2e2; color: #991b1b; }
.controls-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
}
.control-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 14px; border-radius: 12px;
    border: 1px solid #e2e8f0; background: #f8fafc;
    transition: border-color .15s;
}
.control-item:hover { border-color: #c7d2fe; }
.control-item.ok   { border-left: 3px solid #10b981; }
.control-item.fail { border-left: 3px solid #ef4444; background: #fff5f5; }
.control-icon {
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; flex-shrink: 0; margin-top: 1px;
}
.control-icon.ok   { background: #dcfce7; color: #16a34a; }
.control-icon.fail { background: #fee2e2; color: #dc2626; }
.control-text-label { font-size: 0.8rem; font-weight: 700; color: #1e293b; }
.control-text-desc  { font-size: 0.7rem; color: #64748b; margin-top: 1px; }

/* ── Server & Resources Block ────────────────────────────────────── */
.server-block {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
    margin-bottom: 20px;
}
.server-card {
    background: white; border-radius: 20px;
    border: 1px solid #e2e8f0; padding: 28px;
}
.server-card-title {
    font-size: 0.82rem; font-weight: 800; color: #334155;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 20px; padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.server-card-title i { color: #6366f1; }

/* Stack rows */
.stack-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0; border-bottom: 1px solid #f1f5f9;
}
.stack-row:last-child { border-bottom: none; padding-bottom: 0; }
.stack-key { font-size: 0.8rem; color: #475569; font-weight: 500; }
.stack-val {
    font-size: 0.75rem; font-weight: 800; padding: 3px 10px;
    border-radius: 20px; background: #f1f5f9; color: #334155;
    font-family: 'JetBrains Mono', monospace; letter-spacing: .3px;
}
.stack-val.green { background: #dcfce7; color: #15803d; }
.stack-val.red   { background: #fee2e2; color: #dc2626; }
.stack-val.blue  { background: #dbeafe; color: #1d4ed8; }

/* Resource bars */
.resource-item { margin-bottom: 22px; }
.resource-item:last-child { margin-bottom: 0; }
.resource-meta {
    display: flex; justify-content: space-between;
    align-items: baseline; margin-bottom: 6px;
}
.resource-meta-label { font-size: 0.8rem; color: #475569; font-weight: 600; }
.resource-meta-value { font-size: 0.75rem; font-weight: 800; color: #1e293b; }
.resource-bar-track {
    height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden;
}
.resource-bar-fill {
    height: 100%; border-radius: 99px;
    transition: width 1s cubic-bezier(.4,0,.2,1);
}
.resource-bar-fill.green  { background: linear-gradient(90deg, #10b981, #34d399); }
.resource-bar-fill.yellow { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.resource-bar-fill.red    { background: linear-gradient(90deg, #ef4444, #f87171); }
.resource-note {
    display: flex; align-items: flex-start; gap: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 12px; padding: 14px; margin-top: 18px;
}
.resource-note i { color: #6366f1; margin-top: 2px; flex-shrink: 0; font-size: 0.85rem; }
.resource-note p { font-size: 0.75rem; color: #475569; margin: 0; line-height: 1.6; }

/* ── Stats Block ─────────────────────────────────────────────────── */
.stats-block {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    margin-bottom: 20px;
}
.stat-card {
    background: white; border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 22px; position: relative; overflow: hidden;
}
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 4px; height: 100%; border-radius: 16px 0 0 16px;
}
.stat-card.blue::before   { background: #6366f1; }
.stat-card.green::before  { background: #10b981; }
.stat-card.yellow::before { background: #f59e0b; }
.stat-card.red::before    { background: #ef4444; }
.stat-icon-wrap {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; margin-bottom: 14px;
}
.stat-card.blue   .stat-icon-wrap { background: #eef2ff; color: #6366f1; }
.stat-card.green  .stat-icon-wrap { background: #dcfce7; color: #16a34a; }
.stat-card.yellow .stat-icon-wrap { background: #fef9c3; color: #ca8a04; }
.stat-card.red    .stat-icon-wrap { background: #fee2e2; color: #dc2626; }
.stat-kicker {
    font-size: 0.6rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 1.5px; color: #94a3b8;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.stat-desc {
    font-size: 0.72rem; color: #64748b; margin: 2px 0 10px;
    line-height: 1.4; word-break: break-word;
}
.stat-number {
    font-size: 2rem; font-weight: 900; color: #0f172a;
    line-height: 1; margin-bottom: 10px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.stat-badge  {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.62rem; font-weight: 800; padding: 3px 8px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: .5px;
    white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis;
}
.stat-badge .dot { width: 6px; height: 6px; border-radius: 50%; }
.stat-badge.base   { background: #f1f5f9; color: #475569; }
.stat-badge.base   .dot { background: #94a3b8; }
.stat-badge.live   { background: #dcfce7; color: #166534; }
.stat-badge.live   .dot { background: #16a34a; animation: livePulse 2s infinite; }
.stat-badge.warn   { background: #fef9c3; color: #854d0e; }
.stat-badge.warn   .dot { background: #ca8a04; }
.stat-badge.danger { background: #fee2e2; color: #991b1b; }
.stat-badge.danger .dot { background: #dc2626; }

/* ── Load Card ───────────────────────────────────────────────────── */
.load-card {
    background: white; border-radius: 20px;
    border: 1px solid #e2e8f0; padding: 28px;
}
.load-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px; }
.load-item {
    background: #f8fafc; border-radius: 12px; padding: 16px;
    text-align: center; border: 1px solid #e2e8f0;
}
.load-val { font-size: 1.8rem; font-weight: 900; color: #0f172a; line-height: 1; font-family: 'JetBrains Mono', monospace; }
.load-period { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }

/* Responsive */
@media (max-width: 900px) {
    .security-block { grid-template-columns: 1fr; }
    .security-gauge-panel { border-right: none; border-bottom: 1px solid #e2e8f0; }
    .server-block { grid-template-columns: 1fr; }
    .stats-block { grid-template-columns: 1fr 1fr; }
    .controls-grid { grid-template-columns: 1fr; }
}
@media (max-width: 540px) {
    .stats-block { grid-template-columns: 1fr 1fr; }
    .load-grid { grid-template-columns: 1fr; }
}
@media (max-width: 400px) {
    .stats-block { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $gaugeCircumference = 2 * M_PI * 45; // r=45
    $gaugeDash = ($securityPct / 100) * $gaugeCircumference;
    $gaugeGap  = $gaugeCircumference - $gaugeDash;
    $gaugeColor = $securityPct >= 85 ? '#10b981' : ($securityPct >= 60 ? '#f59e0b' : '#ef4444');

    function barColor(int $pct): string {
        if ($pct < 60) return 'green';
        if ($pct < 80) return 'yellow';
        return 'red';
    }
@endphp

<div class="health-page">

    {{-- ── HEADER ────────────────────────────────────────────────────────── --}}
    <div class="health-header">
        <div class="health-header-title">
            <div class="health-kicker">
                <span class="live-pulse"></span>
                Infraestrutura · AWS Lightsail
            </div>
            <div class="health-main-title">Central de Saúde do Servidor</div>
            <div class="health-subtitle">Monitoramento executivo de segurança, recursos e serviços.</div>
        </div>
        <div class="health-actions">
            <div class="health-timestamp">
                <i class="fas fa-clock" style="color:#6366f1;font-size:.7rem;"></i>
                Snapshot: {{ $cachedAt ? \Carbon\Carbon::parse($cachedAt)->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}
                <span style="color:#94a3b8;">· cache 30s · auto 60s</span>
            </div>
            <a href="{{ route('admin.health', ['refresh' => 1]) }}" class="btn-refresh">
                <i class="fas fa-rotate-right"></i> Forçar Atualização
            </a>
        </div>
    </div>

    {{-- ── SECURITY ───────────────────────────────────────────────────────── --}}
    <div class="section-label">Segurança</div>
    <div class="security-block">

        {{-- Gauge --}}
        <div class="security-gauge-panel">
            <div class="gauge-wrap">
                <svg viewBox="0 0 100 100" width="150" height="150">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                    <circle cx="50" cy="50" r="45" fill="none"
                        stroke="{{ $gaugeColor }}" stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="{{ number_format($gaugeDash, 2) }} {{ number_format($gaugeGap, 2) }}"
                        stroke-dashoffset="0"/>
                </svg>
                <div class="gauge-center">
                    <div class="gauge-pct" style="color:{{ $gaugeColor }}">{{ $securityPct }}%</div>
                    <div class="gauge-label">score</div>
                </div>
            </div>
            <div class="gauge-title">Saúde de segurança</div>
            <div class="gauge-desc">Leitura executiva dos controles essenciais para reduzir risco de vazamento e falhas operacionais.</div>
        </div>

        {{-- Controls --}}
        <div class="security-controls-panel">
            <div class="security-controls-header">
                <div class="security-controls-title">
                    <i class="fas fa-shield-halved" style="color:#6366f1;"></i>
                    Controles críticos
                </div>
                <span class="badge-score {{ $securityScore === $securityTotal ? '' : ($securityScore >= $securityTotal * .7 ? 'warn' : 'danger') }}">
                    {{ $securityScore }}/{{ $securityTotal }} OK
                </span>
            </div>
            <div class="controls-grid">
                @foreach($checks as $check)
                <div class="control-item {{ $check['ok'] ? 'ok' : 'fail' }}">
                    <div class="control-icon {{ $check['ok'] ? 'ok' : 'fail' }}">
                        <i class="fas {{ $check['ok'] ? 'fa-check' : 'fa-xmark' }}"></i>
                    </div>
                    <div>
                        <div class="control-text-label">{{ $check['label'] }}</div>
                        <div class="control-text-desc">{{ $check['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── SERVER STACK + RESOURCES ───────────────────────────────────────── --}}
    <div class="section-label">Servidor &amp; Recursos</div>
    <div class="server-block">

        {{-- Stack --}}
        <div class="server-card">
            <div class="server-card-title">
                <i class="fas fa-layer-group"></i> Stack do Servidor
            </div>
            <div class="stack-row">
                <span class="stack-key">PHP</span>
                <span class="stack-val green">{{ PHP_VERSION }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">MySQL</span>
                <span class="stack-val green">{{ $dbVersion }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Laravel</span>
                <span class="stack-val blue">{{ app()->version() }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Sistema Operacional</span>
                <span class="stack-val">{{ strtoupper(PHP_OS_FAMILY) }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Limite de Memória PHP</span>
                <span class="stack-val">{{ ini_get('memory_limit') }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Redis</span>
                <span class="stack-val {{ $redisOk ? 'green' : 'red' }}">{{ $redisOk ? 'conectado' : 'offline' }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Hora do Servidor</span>
                <span class="stack-val">{{ now()->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="stack-row">
                <span class="stack-key">Uptime</span>
                <span class="stack-val {{ $isLinux ? 'green' : '' }}">{{ $uptime }}</span>
            </div>
        </div>

        {{-- Resources --}}
        <div class="server-card">
            <div class="server-card-title">
                <i class="fas fa-chart-line"></i> Recursos
            </div>

            @if($isLinux && $diskTotal > 0)
            @php $diskColor = barColor($diskPct); @endphp
            <div class="resource-item">
                <div class="resource-meta">
                    <span class="resource-meta-label">Disco utilizado</span>
                    <span class="resource-meta-value">{{ $diskPct }}% · {{ number_format($diskUsed / 1024, 1) }} GB / {{ number_format($diskTotal / 1024, 1) }} GB</span>
                </div>
                <div class="resource-bar-track">
                    <div class="resource-bar-fill {{ $diskColor }}" style="width:{{ $diskPct }}%;"></div>
                </div>
            </div>
            @endif

            @if($isLinux && $memTotal > 0)
            @php $memColor = barColor($memPct); @endphp
            <div class="resource-item">
                <div class="resource-meta">
                    <span class="resource-meta-label">Memória RAM</span>
                    <span class="resource-meta-value">{{ $memPct }}% · {{ $memUsed }} MB / {{ $memTotal }} MB</span>
                </div>
                <div class="resource-bar-track">
                    <div class="resource-bar-fill {{ $memColor }}" style="width:{{ $memPct }}%;"></div>
                </div>
            </div>
            @endif

            <div class="resource-item">
                <div class="resource-meta">
                    <span class="resource-meta-label">Memória PHP (requisição atual)</span>
                    <span class="resource-meta-value">{{ round(memory_get_usage(true) / 1024 / 1024, 1) }} MB / {{ ini_get('memory_limit') }}</span>
                </div>
                @php $phpMem = round(memory_get_usage(true) / 1024 / 1024); $phpLimit = (int) ini_get('memory_limit'); $phpPct = $phpLimit > 0 ? min(100, round($phpMem / $phpLimit * 100)) : 0; @endphp
                <div class="resource-bar-track">
                    <div class="resource-bar-fill {{ barColor($phpPct) }}" style="width:{{ $phpPct }}%;"></div>
                </div>
            </div>

            @if(!$isLinux)
            <div class="resource-note">
                <i class="fas fa-location-dot"></i>
                <p>Configure o servidor em <strong>AWS Lightsail</strong> para ambiente de produção. As métricas detalhadas (CPU, rede) ficam disponíveis no console Lightsail.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ── LOAD AVERAGE ────────────────────────────────────────────────────── --}}
    <div class="load-card" style="margin-bottom:20px;">
        <div class="server-card-title" style="margin-bottom:0;">
            <i class="fas fa-microchip" style="color:#6366f1;"></i> Load Average do Servidor
            <span style="font-size:.72rem;color:#94a3b8;font-weight:500;margin-left:4px;">— média de uso de CPU</span>
        </div>
        <div class="load-grid">
            <div class="load-item">
                <div class="load-val" style="color:{{ (float)$load['1m'] > 2 ? '#ef4444' : ((float)$load['1m'] > 1 ? '#f59e0b' : '#10b981') }};">
                    {{ $load['1m'] }}
                </div>
                <div class="load-period">Último 1 min</div>
            </div>
            <div class="load-item">
                <div class="load-val" style="color:{{ (float)$load['5m'] > 2 ? '#ef4444' : ((float)$load['5m'] > 1 ? '#f59e0b' : '#10b981') }};">
                    {{ $load['5m'] }}
                </div>
                <div class="load-period">Últimos 5 min</div>
            </div>
            <div class="load-item">
                <div class="load-val" style="color:{{ (float)$load['15m'] > 2 ? '#ef4444' : ((float)$load['15m'] > 1 ? '#f59e0b' : '#10b981') }};">
                    {{ $load['15m'] }}
                </div>
                <div class="load-period">Últimos 15 min</div>
            </div>
        </div>
    </div>

    {{-- ── FILAS & JOBS ────────────────────────────────────────────────────── --}}
    <div class="section-label">Filas &amp; Jobs</div>
    <div class="stats-block" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 32px;">

        <div class="stat-card {{ $jobsPending > 500 ? 'yellow' : 'green' }}">
            <div class="stat-icon-wrap"><i class="fas fa-list-check"></i></div>
            <div class="stat-kicker">Jobs pendentes</div>
            <div class="stat-desc">Aguardando processamento na fila</div>
            <div class="stat-number">{{ number_format($jobsPending) }}</div>
            <span class="stat-badge {{ $jobsPending > 500 ? 'warn' : 'live' }}">
                <span class="dot"></span> {{ $jobsPending > 500 ? 'ATENÇÃO' : 'OK' }}
            </span>
        </div>

        <div class="stat-card {{ $jobsFailed > 0 ? 'red' : 'green' }}">
            <div class="stat-icon-wrap"><i class="fas fa-circle-exclamation"></i></div>
            <div class="stat-kicker">Jobs falhados</div>
            <div class="stat-desc">
                @if($jobsLastActivity) Último há {{ $jobsLastActivity }} @else Nenhum registrado @endif
            </div>
            <div class="stat-number">{{ number_format($jobsFailed) }}</div>
            @if($jobsFailed > 0)
                <a href="{{ route('admin.failed-jobs.index') }}" class="stat-badge danger" style="text-decoration:none;">
                    <span class="dot"></span> VER DETALHES
                </a>
            @else
                <span class="stat-badge live"><span class="dot"></span> ZERADO</span>
            @endif
        </div>

        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-database"></i></div>
            <div class="stat-kicker">Redis (cache & filas)</div>
            <div class="stat-desc">Backend de cache e sessão</div>
            <div class="stat-number" style="font-size:1.4rem;">{{ $redisOk ? 'CONECTADO' : 'OFFLINE' }}</div>
            <span class="stat-badge {{ $redisOk ? 'live' : 'danger' }}">
                <span class="dot"></span> {{ $redisOk ? 'HEALTHY' : 'ALERTA' }}
            </span>
        </div>

    </div>

    {{-- ── STATS REAIS DA PLATAFORMA ─────────────────────────────────────── --}}
    <div class="section-label">Dados da Plataforma</div>
    <div class="stats-block">

        <div class="stat-card blue">
            <div class="stat-icon-wrap"><i class="fas fa-hands-holding-heart"></i></div>
            <div class="stat-kicker">ONGs</div>
            <div class="stat-desc">Organizações cadastradas no SaaS</div>
            <div class="stat-number">{{ number_format($statTenants) }}</div>
            <span class="stat-badge base"><span class="dot"></span> TOTAL</span>
        </div>

        <div class="stat-card green">
            <div class="stat-icon-wrap"><i class="fas fa-users"></i></div>
            <div class="stat-kicker">Usuários</div>
            <div class="stat-desc">Contas criadas na plataforma</div>
            <div class="stat-number">{{ number_format($statUsers) }}</div>
            <span class="stat-badge live"><span class="dot"></span> TOTAL</span>
        </div>

        <div class="stat-card blue" style="--accent:#0ea5e9;">
            <div class="stat-icon-wrap" style="background:#e0f2fe;color:#0284c7;"><i class="fas fa-arrow-right-arrow-left"></i></div>
            <div class="stat-kicker">Transações</div>
            <div class="stat-desc">Registros financeiros no sistema</div>
            <div class="stat-number">{{ number_format($statTransactions) }}</div>
            <span class="stat-badge base"><span class="dot"></span> ACUMULADO</span>
        </div>

        <div class="stat-card green" style="--accent:#25d366;">
            <div class="stat-icon-wrap" style="background:#dcfce7;color:#16a34a;"><i class="fab fa-whatsapp"></i></div>
            <div class="stat-kicker">Mensagens WhatsApp</div>
            <div class="stat-desc">Total de mensagens processadas</div>
            <div class="stat-number">{{ number_format($statWppMsgs) }}</div>
            <span class="stat-badge live"><span class="dot"></span> ACUMULADO</span>
        </div>

        <div class="stat-card yellow">
            <div class="stat-icon-wrap"><i class="fas fa-heart-pulse"></i></div>
            <div class="stat-kicker">Beneficiários</div>
            <div class="stat-desc">Pessoas assistidas pelas ONGs</div>
            <div class="stat-number">{{ number_format($statBeneficiaries) }}</div>
            <span class="stat-badge warn"><span class="dot"></span> CADASTRADOS</span>
        </div>

        <div class="stat-card red">
            <div class="stat-icon-wrap"><i class="fas fa-headset"></i></div>
            <div class="stat-kicker">Suporte</div>
            <div class="stat-desc">Chamados abertos pelos usuários</div>
            <div class="stat-number">{{ $statOpenTickets }}</div>
            <span class="stat-badge {{ $statOpenTickets > 0 ? 'danger' : 'base' }}">
                <span class="dot"></span> {{ $statOpenTickets > 0 ? 'PENDENTE' : 'SLA OK' }}
            </span>
        </div>

    </div>

</div>
@endsection
