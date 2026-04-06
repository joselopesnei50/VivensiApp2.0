@extends('layouts.admin-executive')

@section('content')
<div class="dashboard-header" style="margin-bottom: 32px;">
    <div>
        <h1 style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 0 0 8px 0;">
            Overview Executivo
        </h1>
        <p style="font-size: 14px; color: var(--text-secondary); margin: 0;">
            Monitoramento em tempo real da performance do SaaS
        </p>
    </div>
</div>

<!-- KPIs Grid -->
<div class="row g-4 mb-4">
    <!-- MRR -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">MRR (Receita Recorrente)</div>
            <div class="kpi-value">
                R$ {{ number_format($mrr, 2, ',', '.') }}
            </div>
            <div class="kpi-change positive">
                <i class="fas fa-arrow-up"></i>
                +12.5% <span style="font-size: 11px; opacity: 0.7;">(simulado)</span>
            </div>
        </div>
    </div>

    <!-- Tenants Ativos -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">Tenants Ativos</div>
            <div class="kpi-value">{{ $totalActive ?? $totalTenants }}</div>
            <div class="kpi-change positive">
                <i class="fas fa-arrow-up"></i>
                +{{ $newClientsMonth }} este mês
            </div>
        </div>
    </div>

    <!-- Usuários Online -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">Usuários Online</div>
            <div class="kpi-value">{{ $onlineUsers }}</div>
            <div class="kpi-change neutral" style="color: var(--text-secondary);">
                <i class="fas fa-users"></i>
                Total: {{ $totalUsers }}
            </div>
        </div>
    </div>

    <!-- Churn Rate -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-label">Churn Rate</div>
            <div class="kpi-value">{{ number_format($churnRate, 1) }}%</div>
            <div class="kpi-change {{ $churnRate < 2 ? 'positive' : 'negative' }}">
                <i class="fas fa-{{ $churnRate < 2 ? 'check' : 'exclamation' }}-circle"></i>
                {{ $churnRate < 2 ? 'Saudável' : 'Atenção' }}
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Chart -->
    <div class="col-12 col-xl-8">
        <div class="executive-card">
            <div class="card-header">
                <div>
                    <div class="card-title">Crescimento de Receita (MRR)</div>
                    <div class="card-subtitle">Evolução do Faturamento (Últimos 6 meses)</div>
                </div>
            </div>
            <div style="position: relative; height: 300px;">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Plans Distribution -->
    <div class="col-12 col-xl-4">
        <div class="executive-card">
            <div class="card-header">
                <div>
                    <div class="card-title">Distribuição de Planos</div>
                    <div class="card-subtitle">Por volume de assinaturas</div>
                </div>
            </div>
            <div style="position: relative; height: 300px;">
                <canvas id="plansChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Risco de Churn -->
    <div class="col-lg-6">
        <div class="executive-card h-100">
            <div class="card-header">
                <div>
                    <div class="card-title">Risco de Churn</div>
                    <div class="card-subtitle">Gerentes inativos (>10 dias)</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-executive">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Gerente</th>
                            <th>Último Acesso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($churnRiskUsers as $user)
                        <tr>
                            <td>{{ $user->tenant->name ?? 'N/A' }}</td>
                            <td>{{ $user->name }}</td>
                            <td style="color: var(--danger);">
                                {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Nunca acessou' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-tertiary); padding: 20px;">
                                <i class="fas fa-check-circle" style="color: var(--success); margin-bottom: 8px; display: block; font-size: 24px;"></i>
                                Nenhum risco detectado
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-6">
        <div class="executive-card h-100">
            <div class="card-header">
                <div>
                    <div class="card-title">Novos Entrantes</div>
                    <div class="card-subtitle">Últimas 5 organizações cadastradas</div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table-executive">
                    <thead>
                        <tr>
                            <th>Organização</th>
                            <th>Plano</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTenants as $tenant)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--bg-secondary); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: var(--text-secondary);">
                                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary);">{{ $tenant->name }}</div>
                                        <div style="font-size: 11px; color: var(--text-tertiary);">{{ $tenant->created_at->format('d/m/Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-plan">{{ $tenant->plan_type ?? 'Free' }}</span>
                            </td>
                            <td>
                                <span class="badge-status {{ $tenant->subscription_status === 'active' ? 'active' : 'inactive' }}">
                                    {{ $tenant->subscription_status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.tenants.show', $tenant->id) }}" class="btn-action">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-tertiary);">
                                Nenhuma organização encontrada
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table-executive {
    width: 100%;
    border-collapse: collapse;
}

.table-executive thead tr {
    border-bottom: 1px solid var(--border);
}

.table-executive th {
    padding: 12px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-executive td {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    color: var(--text-secondary);
}

.table-executive tbody tr:last-child td {
    border-bottom: none;
}

.table-executive tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.badge-plan,
.badge-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-plan {
    background: rgba(99, 102, 241, 0.1);
    color: var(--accent);
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.badge-status.active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.badge-status.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.btn-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-action:hover {
    background: var(--bg-secondary);
    border-color: var(--text-secondary);
    color: var(--text-primary);
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurações Globais Chart.js
        Chart.defaults.color = '#737373';
        Chart.defaults.borderColor = '#262626';
        Chart.defaults.font.family = "'Inter', sans-serif";
        
        // Gráfico de Crescimento (Growth Chart)
        const ctxGrowth = document.getElementById('growthChart')?.getContext('2d');
        if (ctxGrowth) {
            new Chart(ctxGrowth, {
                type: 'line',
                data: {
                    labels: {!! json_encode($growthLabels) !!},
                    datasets: [{
                        label: 'MRR (R$)',
                        data: {!! json_encode($growthValues) !!},
                        borderColor: '#6366f1',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
                            gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
                            return gradient;
                        },
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#171717',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#171717',
                            titleColor: '#fff',
                            bodyColor: '#a3a3a3',
                            borderColor: '#333',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'MRR: R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#262626', borderDash: [5, 5] },
                            ticks: { 
                                callback: function(value) {
                                    return 'R$ ' + value / 1000 + 'k';
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // Gráfico de Planos (Plans Chart)
        const ctxPlans = document.getElementById('plansChart')?.getContext('2d');
        if (ctxPlans) {
            const planLabels = {!! json_encode($planDistribution->pluck('name')) !!};
            const planData = {!! json_encode($planDistribution->pluck('count')) !!};

            new Chart(ctxPlans, {
                type: 'doughnut',
                data: {
                    labels: planLabels,
                    datasets: [{
                        data: planData,
                        backgroundColor: [
                            '#6366f1', // Indigo
                            '#10b981', // Emerald
                            '#f59e0b', // Amber
                            '#ec4899', // Pink
                            '#8b5cf6'  // Violet
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { 
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
