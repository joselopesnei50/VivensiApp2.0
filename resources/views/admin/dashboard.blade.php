@extends('layouts.app')

@section('content')
<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.1) 0%, rgba(15, 23, 42, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #1e293b; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #1e293b; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">SaaS Executive Command</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Painel de Inteligência <span style="color: #6366f1;">Global</span></h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Métricas críticas de crescimento, retenção e saúde do ecossistema.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="{{ route('admin.health') }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 800;">
                <i class="fas fa-microchip me-2 text-primary"></i> Status Infra
            </a>
            <a href="{{ url('/admin/tenants') }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800;">
                <i class="fas fa-users-gear me-2"></i> Gerenciar Tenants
            </a>
        </div>
    </div>
</div>

<!-- 1. Executive monitors (KPIs) -->
<div class="row g-4 mb-5">
    <div class="col">
        <div class="vivensi-card" style="padding: 30px; border-left: 5px solid #6366f1; border-radius: 24px; background: white; border-top: none; border-right: none; border-bottom: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">Total MRR</span>
            <div style="font-size: 2rem; font-weight: 900; color: #1e293b; margin: 5px 0; letter-spacing: -1px;">R$ {{ number_format($mrr, 2, ',', '.') }}</div>
            <div style="font-size: 0.75rem; color: #10b981; font-weight: 800;"><i class="fas fa-chart-line me-1"></i> Receita Recorrente</div>
        </div>
    </div>

    <div class="col">
        <div class="vivensi-card" style="padding: 30px; border-left: 5px solid #10b981; border-radius: 24px; background: white; border-top: none; border-right: none; border-bottom: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">Clientes (Mês)</span>
            <div style="font-size: 2rem; font-weight: 900; color: #1e293b; margin: 5px 0; letter-spacing: -1px;">+{{ $newClientsMonth }}</div>
            <div style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Performance {{ now()->format('M') }}</div>
        </div>
    </div>

    <div class="col">
        <div class="vivensi-card" style="padding: 30px; border-left: 5px solid #f59e0b; border-radius: 24px; background: white; border-top: none; border-right: none; border-bottom: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">Churn Rate</span>
            <div style="font-size: 2rem; font-weight: 900; color: #1e293b; margin: 5px 0; letter-spacing: -1px;">{{ number_format($churnRate, 1) }}%</div>
            <div style="font-size: 0.75rem; color: #ef4444; font-weight: 800;">Retenção Crítica</div>
        </div>
    </div>

    <div class="col">
        <div class="vivensi-card" style="padding: 30px; border-left: 5px solid #ec4899; border-radius: 24px; background: white; border-top: none; border-right: none; border-bottom: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;">Live Users</span>
            <div style="font-size: 2rem; font-weight: 900; color: #1e293b; margin: 5px 0; letter-spacing: -1px;">{{ $onlineUsers }}</div>
            <div style="font-size: 0.75rem; color: #10b981; font-weight: 800;"><span class="ai-status-pulse" style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-right: 5px;"></span> Em Sessão</div>
        </div>
    </div>

</div>

<div class="row g-4 mb-5">
    <!-- 2. Main Growth Spline -->
    <div class="col-lg-8">
        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Revenue Growth Dynamics</h4>
                <div style="display: flex; gap: 10px;">
                    <span style="background: #eef2ff; color: #6366f1; padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800;">LTM</span>
                </div>
            </div>
            <div id="growthChart"></div>
        </div>
    </div>

    <!-- 5. Acquisition Sources -->
    <div class="col-lg-4">
        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); height: 100%;">
            <h4 style="margin: 0 0 30px 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Aquisição por Canal</h4>
            <div id="sourceChart"></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 3. Churn Radar -->
    <div class="col-lg-7">
        <div class="vivensi-card" style="padding: 0; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); overflow: hidden;">
            <div style="padding: 25px 30px; background: #fff1f2; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-weight: 900; color: #9f1239; font-size: 1.1rem;"><i class="fas fa-radar me-2 fa-beat"></i> Radar de Risco de Churn</h4>
                <div style="background: #9f1239; color: white; padding: 5px 12px; border-radius: 8px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase;">Protocolo de Recuperação</div>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #fdf2f2; border-bottom: 1px solid #fee2e2;">
                            <th style="padding: 15px 30px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 1px;">Cliente/Tenant</th>
                            <th style="padding: 15px 30px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 1px;">Último Acesso</th>
                            <th style="padding: 15px 30px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 1px;">Severidade</th>
                            <th style="padding: 15px 30px; text-align: center; font-size: 0.7rem; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 1px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($churnRiskUsers as $user)
                        <tr style="border-bottom: 1px solid #fff5f5; transition: background 0.2s;" onmouseover="this.style.background='#fff8f8';" onmouseout="this.style.background='white';">
                            <td style="padding: 20px 30px;">
                                <div style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">{{ $user->name }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">{{ $user->tenant->name }}</div>
                            </td>
                            <td style="padding: 20px 30px;">
                                <div style="color: #475569; font-size: 0.85rem; font-weight: 700;">
                                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Inativo Total' }}
                                </div>
                            </td>
                            <td style="padding: 20px 30px;">
                                @php
                                    $days = $user->last_login_at ? $user->last_login_at->diffInDays() : 99;
                                    $statusColor = $days > 15 ? '#ef4444' : '#f59e0b';
                                @endphp
                                <span style="background: {{ $statusColor }}20; color: {{ $statusColor }}; padding: 6px 12px; border-radius: 10px; font-size: 0.65rem; font-weight: 900;">
                                    {{ $days > 15 ? 'CRÍTICO' : 'ALTO RISCO' }}
                                </span>
                            </td>
                            <td style="padding: 20px 30px; text-align: center;">
                                <a href="mailto:{{ $user->email }}" 
                                   class="btn btn-sm" style="background: #6366f1; color: white; border-radius: 12px; font-weight: 800; padding: 10px 18px; font-size: 0.75rem;">
                                    <i class="fas fa-envelope me-2"></i> NOTIFICAR
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Adoption Analytics -->
    <div class="col-lg-5">
        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); height: 100%;">
            <h4 style="margin: 0 0 10px 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Adoção: Core Features</h4>
            <p style="font-size: 0.85rem; color: #94a3b8; font-weight: 600; margin-bottom: 30px;">Volume de LEGO Blocks vs Impacto Social Gerado</p>
            <div id="adoptionChart"></div>
        </div>
    </div>
</div>

<!-- Niche Funnel Analytics -->
<div class="row mt-5">
    <div class="col-md-12">
        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f8fafc;">
                <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Máquina de Vendas: Funil por Vertical</h4>
                <div style="background: #f1f5f9; color: #475569; padding: 6px 15px; border-radius: 10px; font-size: 0.7rem; font-weight: 800;">TOTAL DE TRIAL STARTS: {{ $lpMetrics->sum('total_registrations') }}</div>
            </div>
            
            <div class="row g-4">
                @foreach(['ngo' => 'Terceiro Setor', 'manager' => 'Gestão Social', 'personal' => 'Uso Individual'] as $key => $label)
                    @php
                        $metric = $lpMetrics->where('page_key', $key)->first();
                        $views = $metric->total_views ?? 0;
                        $regs = $metric->total_registrations ?? 0;
                        $rate = $views > 0 ? ($regs / $views) * 100 : 0;
                    @endphp
                    <div class="col-md-4">
                        <div style="background: #fbfcfe; border-radius: 24px; padding: 25px; border: 1px solid #f1f5f9;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                <h6 style="margin: 0; font-weight: 900; color: #6366f1; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">{{ $label }}</h6>
                                <span style="background: #6366f1; color: white; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 800;">{{ number_format($rate, 1) }}% Conv.</span>
                            </div>
                            <div style="display: flex; gap: 30px;">
                                <div>
                                    <span style="display: block; font-size: 0.6rem; color: #94a3b8; font-weight: 800; text-transform: uppercase;">Reach</span>
                                    <span style="font-size: 1.5rem; font-weight: 900; color: #1e293b;">{{ $views }}</span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.6rem; color: #94a3b8; font-weight: 800; text-transform: uppercase;">Trials</span>
                                    <span style="font-size: 1.5rem; font-weight: 900; color: #1e293b;">{{ $regs }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 2. Main Growth Curve
    new ApexCharts(document.querySelector("#growthChart"), {
        series: [{ name: 'MRR Dynamics (R$)', data: {!! json_encode($growthValues) !!} }],
        chart: { type: 'area', height: 320, fontFamily: 'Outfit, sans-serif', toolbar: { show: false }, zoom: { enabled: false } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 4, colors: ['#6366f1'] },
        xaxis: { categories: {!! json_encode($growthLabels) !!}, labels: { style: { colors: '#94a3b8', fontWeight: 700, fontSize: '11px' } } },
        yaxis: { labels: { style: { colors: '#94a3b8', fontWeight: 700, fontSize: '11px' }, formatter: val => "R$ " + val.toLocaleString('pt-BR') } },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.6, opacityTo: 0.1, stops: [0, 95, 100] } },
        colors: ['#6366f1'],
        grid: { borderColor: '#f8fafc', strokeDashArray: 4 }
    }).render();

    // 4. Bar Adoption
    new ApexCharts(document.querySelector("#adoptionChart"), {
        series: [{ name: 'Volume', data: [{{ $totalBlocks }}, {{ $totalDonations }}] }],
        chart: { type: 'bar', height: 300, fontFamily: 'Outfit, sans-serif', toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 12, columnWidth: '45%', distributed: true } },
        xaxis: { categories: ['LEGO Blocks', 'Repasses Sociais'], labels: { style: { colors: '#94a3b8', fontWeight: 800, fontSize: '12px' } } },
        colors: ['#6366f1', '#10b981'],
        grid: { borderColor: '#f8fafc', strokeDashArray: 6 },
        legend: { show: false }
    }).render();

    // 5. Donut Source
    new ApexCharts(document.querySelector("#sourceChart"), {
        series: {!! json_encode(array_map('intval', $leadSourceData->pluck('count')->toArray())) !!},
        chart: { type: 'donut', height: 320, fontFamily: 'Outfit, sans-serif' },
        labels: {!! json_encode($leadSourceData->pluck('page_key')) !!},
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ec4899'],
        legend: { position: 'bottom', fontSize: '12px', fontWeight: 800, labels: { colors: '#64748b' } },
        stroke: { show: false },
        plotOptions: { pie: { donut: { size: '80%', labels: { show: true, total: { show: true, label: 'TOTAL LEADS', fontSize: '10px', fontWeight: 900, color: '#94a3b8' } } } } }
    }).render();
});
</script>

<style>
.ai-status-pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>
@endsection

