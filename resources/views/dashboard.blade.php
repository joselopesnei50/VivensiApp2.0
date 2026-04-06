@extends('layouts.app')

@section('content')

@if($role == 'ngo')
    <!-- PREMIUM NGO DASHBOARD -->
    <style>
        :root {
            --ngo-dark: #0f172a;
            --ngo-primary: #6366f1;
            --ngo-success: #10b981;
            --ngo-bg: #f8fafc;
        }
        body { background-color: var(--ngo-bg); }

        .ngo-grid {
            display: grid;
            gap: 24px;
        }

        /* Top Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card-premium {
            background: white;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 140px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card-premium:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1);
        }

        /* Dark Card (Runway) */
        .stat-card-dark {
            background: var(--ngo-dark);
            color: white;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .stat-card-dark .label { color: rgba(255,255,255,0.6); }
        .stat-card-dark .value { color: white; }
        .stat-card-dark .icon-corner {
            position: absolute;
            top: 15px; right: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px;
        }

        .label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .value {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }
        .indicator {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: auto;
        }
        .text-success { color: var(--ngo-success) !important; }
        .text-purple { color: #8b5cf6 !important; }

        /* Middle Row: Chart + Sidebar */
        .middle-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #f1f5f9;
            height: 400px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .sidebar-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .campaign-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .ai-tip-box {
            background: #eff6ff;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            border: 1px solid #dbeafe;
        }

        /* Bottom Row: Table + Actions */
        .bottom-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 40px;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 1200px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 992px) {
            .middle-row { grid-template-columns: 1fr; }
            .chart-section { height: 350px; }
            .bottom-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr; }
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }
        .custom-table th {
            text-align: left;
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            padding-bottom: 8px;
        }
        .custom-table td {
            vertical-align: middle;
        }
        
        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .pill-green { background: #dcfce7; color: #166534; }
        .pill-yellow { background: #fef9c3; color: #854d0e; }

        .quick-actions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .action-card-btn {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .action-card-btn:hover {
            border-color: var(--ngo-primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
            transform: translateX(4px);
        }
        .action-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .bg-icon-purple { background: #f3e8ff; color: #7e22ce; }
        .bg-icon-green { background: #dcfce7; color: #15803d; }
        .bg-icon-pink { background: #fce7f3; color: #be185d; }

        /* Progress Bar for Runway */
        .progress-slim {
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            margin-top: 10px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: #ef4444; /* Danger color for low runway */
            width: 20%; /* 0.6 months is low */
        }
    </style>

    <div class="container-fluid pt-2">
        <!-- Top Stats -->
        <div class="stats-row mb-4">
            <!-- Runway -->
            <div class="stat-card-premium stat-card-dark">
                <div class="icon-corner"><i class="fas fa-microchip"></i></div>
                <div class="label">Runway (Caixa)</div>
                <div>
                    <div class="value">{{ $stats['runway'] ?? 'N/A' }} <span style="font-size: 1rem; font-weight: 500;">meses</span></div>
                    <div class="progress-slim">
                        <div class="progress-bar-fill" style="width: {{ min(($stats['runway'] == '> 12' ? 12 : (float)$stats['runway']) * 10, 100) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Arrecadação -->
            <div class="stat-card-premium" style="position: relative;">
                <div style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: #e0e7ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #4338ca;">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="label">Arrecadação (Mês)</div>
                <div class="value">R$ {{ number_format($stats['monthly_income'] ?? 0, 2, ',', '.') }}</div>
                <div class="indicator text-success">
                    <i class="fas fa-arrow-up"></i> Captação ativa
                </div>
            </div>

            <!-- Voluntários -->
            <div class="stat-card-premium" style="position: relative;">
                 <div style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: #dcfce7; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #15803d;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="label">Força Voluntária</div>
                <div class="value">{{ $stats['volunteers_count'] ?? 0 }}</div>
                <div class="indicator text-muted">
                    <small>Pessoas engajadas</small>
                </div>
            </div>

            <!-- Alcance -->
            <div class="stat-card-premium" style="position: relative;">
                <div style="position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: #f3e8ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #7e22ce;">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="label">Base de Doadores</div>
                <div class="value">{{ $stats['total_donors'] ?? 0 }}</div>
                <div class="indicator text-purple">
                    <i class="fas fa-database"></i> Registros totais
                </div>
            </div>
        </div>

        <!-- Middle Row -->
        <div class="middle-row mb-4">
            <!-- Chart -->
            <div class="chart-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-line me-2"></i> Fluxo Financeiro ({{ date('Y') }})</h5>
                    <div class="d-flex gap-3">
                        <small class="fw-bold text-muted"><span style="display:inline-block;width:10px;height:10px;background:#6366f1;border-radius:50%;margin-right:5px;"></span> Doações</small>
                        <small class="fw-bold text-muted"><span style="display:inline-block;width:10px;height:10px;background:#ef4444;border-radius:50%;margin-right:5px;"></span> Despesas</small>
                    </div>
                </div>
                <canvas id="financialFlowChart" style="width: 100%; height: 300px;"></canvas>
            </div>

            <!-- Campaigns Sidebar -->
            <div class="sidebar-section">
                <div class="campaign-card h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-bullhorn me-2"></i> Campanhas Ativas</h6>
                    
                    @if(isset($stats['active_campaigns']) && $stats['active_campaigns']->count() > 0)
                        @foreach($stats['active_campaigns'] as $campaign)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark text-truncate" style="max-width: 150px;">{{ $campaign->title }}</span>
                                <a href="{{ url('/c/'.$campaign->slug) }}" target="_blank" class="text-primary"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="far fa-calendar"></i> Até {{ \Carbon\Carbon::parse($campaign->end_date)->format('d/m') }}</span>
                                <span class="text-success fw-bold">R$ {{ number_format($campaign->raised_amount ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4 text-muted small">
                            Nenhuma campanha ativa no momento.
                        </div>
                    @endif

                    <a href="{{ url('/ngo/campaigns') }}" class="text-decoration-none fw-bold text-primary small">Ver Todas <i class="fas fa-arrow-right ms-1"></i></a>

                    <!-- AI Tip -->
                    <div class="ai-tip-box">
                        <h6 class="fw-bold text-primary mb-2" style="font-size: 0.8rem;">DICA DA IA <i class="fas fa-magic ms-1"></i></h6>
                        <p class="text-dark mb-0 small" style="line-height: 1.5;">Com base no seu fluxo, lançar uma campanha de "Recorrência" agora aumentaria seu Runway em 20%.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="bottom-row">
            <!-- Grants List -->
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-file-signature me-2"></i> Editais & Convênios</h5>
                    <a href="{{ url('/ngo/grants') }}" class="text-primary fw-bold small text-decoration-none">Gerenciar</a>
                </div>
                
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Convênio/Edital</th>
                            <th>Prazo Final</th>
                            <th>Status Prestação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($stats['recent_grants']) && $stats['recent_grants']->count() > 0)
                            @foreach($stats['recent_grants'] as $grant)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $grant->title }}</div>
                                    <small class="text-muted">{{ $grant->agency ?? 'Orgão Externo' }} • R$ {{ number_format($grant->value, 0, ',', '.') }}</small>
                                </td>
                                <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($grant->deadline)->format('M/Y') }}</td>
                                <td>
                                    @if($grant->status == 'open')
                                        <span class="status-pill pill-yellow">Pendente</span>
                                    @elseif($grant->status == 'won')
                                        <span class="status-pill pill-green">Aprovado</span>
                                    @else
                                        <span class="status-pill" style="background:#f1f5f9; color:#64748b;">{{ ucfirst($grant->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Nenhum edital ou convênio registrado.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-list">
                <h5 class="fw-bold text-dark mb-0"><i class="fas fa-bolt me-2"></i> Ações Rápidas</h5>
                
                <a href="{{ url('/ngo/receipts') }}" class="action-card-btn">
                    <div class="action-icon bg-icon-purple"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Emitir Recibo</div>
                        <small class="text-muted">Gerar PDF/Link para doador</small>
                    </div>
                </a>

                <a href="{{ url('/ngo/transparencia') }}" class="action-card-btn">
                    <div class="action-icon bg-icon-green"><i class="fas fa-landmark"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Portal da Transparência</div>
                        <small class="text-muted">Visualizar como o público vê</small>
                    </div>
                </a>

                <a href="{{ url('/ngo/hr') }}" class="action-card-btn">
                    <div class="action-icon bg-icon-pink"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="fw-bold text-dark">Equipe Voluntária</div>
                        <small class="text-muted">Gerir escalas e presenças</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('financialFlowChart').getContext('2d');
            
            // Gradient for Income
            const gradientIncome = ctx.createLinearGradient(0, 0, 0, 300);
            gradientIncome.addColorStop(0, 'rgba(99, 102, 241, 0.5)');
            gradientIncome.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

            // Gradient for Expenses
            const gradientExpense = ctx.createLinearGradient(0, 0, 0, 300);
            gradientExpense.addColorStop(0, 'rgba(239, 68, 68, 0.5)');
            gradientExpense.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                    datasets: [
                        {
                            label: 'Doações',
                            data: [12000, 15000, 14000, 18000, 16000, 22000, 20000, 25000, 23000, 28000, 30000, 35000],
                            borderColor: '#6366f1',
                            backgroundColor: gradientIncome,
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Despesas',
                            data: [10000, 11000, 10500, 12000, 11500, 13000, 12500, 14000, 13500, 15000, 16000, 18000],
                            borderColor: '#ef4444',
                            backgroundColor: gradientExpense,
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 0,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', borderDash: [5, 5] },
                            ticks: { callback: function(value) { return 'R$ ' + value/1000 + 'k'; } }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>

@elseif($role == 'manager')
    <!-- M3 MANAGER DASHBOARD - ISOLATED -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" />
    
    <style>
        /* Scoped Reset for Dashboard Area */
        .m3-dashboard-wrapper {
            all: initial;
            font-family: 'Roboto', sans-serif;
            box-sizing: border-box;
            background-color: #F7F2FA;
            display: block;
            width: 100%;
            height: 100%;
        }
        
        /* Universal reset inside wrapper, BUT EXCLUDING ICONS */
        .m3-dashboard-wrapper *:not(.material-symbols-rounded) {
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        /* FORCE Icon Font */
        .m3-dashboard-wrapper .material-symbols-rounded {
            font-family: 'Material Symbols Rounded' !important;
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
        }

        :root {
            --m3-primary: #6750A4;
            --m3-on-primary: #ffffff;
            --m3-primary-container: #EADDFF;
            --m3-on-primary-container: #21005D;
            --m3-surface: #F7F2FA;
            --m3-surface-container: #F3EDF7;
            --m3-on-surface: #1C1B1F;
            --m3-on-surface-variant: #49454F;
            --m3-outline: #79747E;
        }

        .m3-container {
            padding: 24px;
            max-width: 1600px;
            margin: 0 auto;
            color: var(--m3-on-surface);
        }

        /* FAB */
        .m3-fab {
            background-color: var(--m3-primary-container);
            color: var(--m3-on-primary-container);
            border: none;
            border-radius: 16px; 
            padding: 0 24px;
            height: 56px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0px 4px 8px 3px rgba(0,0,0,0.15);
            transition: all 0.2s;
            text-decoration: none;
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 999;
        }
        .m3-fab:hover {
            background-color: #E2D9F5;
            transform: translateY(-2px);
        }

        /* Create Button (Top) */
        .m3-btn-create {
            background-color: #EADDFF;
            color: #21005D;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .m3-btn-create:hover {
            background-color: #D0BCFF;
        }

        /* Grid System */
        .m3-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Cards */
        .m3-card {
            background-color: #fff;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 160px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Subtle shadow */
            position: relative;
            overflow: hidden;
        }

        /* Side Accents requested by user */
        .m3-card.accent-grey { border-left: 6px solid #9CA3AF; } /* Cool Grey */
        .m3-card.accent-blue { border-left: 6px solid #3B82F6; } /* Blue 500 */
        
        .m3-icon-box {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: auto;
        }
        
        .m3-value {
            font-size: 32px;
            color: #1F2937;
            line-height: 1;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .m3-label {
            font-size: 14px;
            font-weight: 500;
            color: #6B7280;
        }

        /* Split View */
        .m3-split {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .m3-pane {
            background-color: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Task List */
        .m3-task-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid #F3F4F6;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }
        .m3-task-item:hover {
            background: #F9FAFB;
            border-color: #E5E7EB;
        }

        /* Chart Wrapper */
        .chart-wrapper-fix {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Filter Group (Custom Segmented Control) */
        .m3-filter-group {
            display: inline-flex;
            background: #F3F4F6;
            border-radius: 8px;
            padding: 4px;
            gap: 4px;
        }
        .m3-filter-btn {
            background: transparent;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.2s;
        }
        .m3-filter-btn.active {
            background: #fff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .m3-filter-btn:hover:not(.active) {
            background: rgba(0,0,0,0.05);
        }

        h1, h2, h3 { font-family: 'Roboto', sans-serif !important; margin: 0; }
    </style>

    <!-- Wrapper to isolate styles -->
    <div class="m3-dashboard-wrapper">
        <div class="m3-container">
            
            <!-- Welcome Section -->
            <div style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <h1 style="font-size: 28px; color: #111827; font-weight: 700;">Painel do Gestor</h1>
                    <p style="font-size: 14px; color: #6B7280; margin-top: 4px;">Visão geral estratégica operacional.</p>
                </div>
                <!-- Inline Create Action -->
                <a href="{{ url('/projects/create') }}" class="m3-btn-create">
                    <span class="material-symbols-rounded">add</span>
                    Novo Projeto
                </a>
            </div>

            <!-- Metrics -->
            <div class="m3-grid">
                <!-- Fluxo de Caixa: Azul/Cinza -->
                <div class="m3-card accent-grey">
                    <div style="display: flex; justify-content: space-between;">
                        <div class="m3-icon-box" style="background: #EFF6FF; color: #3B82F6;">
                            <span class="material-symbols-rounded">account_balance</span>
                        </div>
                    </div>
                    <div>
                         <div class="m3-value" style="color: #3B82F6;">R$ {{ number_format($balance, 2, ',', '.') }}</div>
                         <div class="m3-label">Fluxo de Caixa</div>
                    </div>
                </div>

                <!-- Projetos Ativos: Lateral Azul -->
                <div class="m3-card accent-blue">
                    <div class="m3-icon-box" style="background: #F0FDF4; color: #16A34A;">
                        <span class="material-symbols-rounded">rocket_launch</span>
                    </div>
                    <div>
                        <div class="m3-value">{{ $stats['active_projects'] ?? 1 }}</div>
                        <div class="m3-label">Projetos Ativos</div>
                    </div>
                </div>

                <!-- Backlog: Lateral Azul -->
                <div class="m3-card accent-blue">
                    <div class="m3-icon-box" style="background: #FDF2F8; color: #DB2777;">
                        <span class="material-symbols-rounded">check_circle</span>
                    </div>
                    <div>
                        <div class="m3-value">{{ $stats['backlog_count'] ?? 3 }}</div>
                        <div class="m3-label">Backlog</div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="m3-split">
                <!-- Chart -->
                <div class="m3-pane">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 24px; align-items: center;">
                        <h2 style="font-size: 18px; color: #374151; font-weight: 600;">Performance Financeira</h2>
                        <!-- Functional Date Filter -->
                        <div class="m3-filter-group" id="chartFilter">
                            <button class="m3-filter-btn" onclick="setActiveFilter(this, 'dia')">Dia</button>
                            <button class="m3-filter-btn" onclick="setActiveFilter(this, 'semana')">Semana</button>
                            <button class="m3-filter-btn" onclick="setActiveFilter(this, 'mes')">Mês</button>
                            <button class="m3-filter-btn active" onclick="setActiveFilter(this, 'ano')">Ano</button>
                        </div>
                    </div>
                    <div class="chart-wrapper-fix">
                        <canvas id="m3Chart"></canvas>
                    </div>
                </div>

                <!-- Priority List -->
                <div class="m3-pane">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 24px; align-items: center;">
                        <h2 style="font-size: 18px; color: #374151; font-weight: 600;">Prioridades</h2>
                        <a href="{{ url('/manager/schedule') }}" style="color: #3B82F6; font-weight: 600; font-size: 12px; text-decoration: none;">VER AGENDA</a>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @php
                            $tasks = [
                                ['title' => 'Revisão Financeira', 'tag' => 'Hoje', 'bg' => '#DBEAFE', 'text' => '#1E40AF'],
                                ['title' => 'Aprovar Contrato MKT', 'tag' => 'Urgente', 'bg' => '#FEE2E2', 'text' => '#991B1B'],
                                ['title' => 'Kickoff Novo Projeto', 'tag' => 'Amanhã', 'bg' => '#F3F4F6', 'text' => '#374151'],
                            ];
                        @endphp
                        @foreach($tasks as $t)
                        <div class="m3-task-item">
                            <span class="material-symbols-rounded" style="color: #9CA3AF;">radio_button_unchecked</span>
                            <span style="flex: 1; font-weight: 500; color: #374151; font-size: 14px;">{{ $t['title'] }}</span>
                            <span style="background: {{ $t['bg'] }}; color: {{ $t['text'] }}; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                {{ $t['tag'] }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- FAB (Apenas para mobile ou se o usuário quiser) -->
    <a href="{{ url('/projects/create') }}" class="m3-fab">
        <span class="material-symbols-rounded">add</span>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Simple JS for Interactivity
        function setActiveFilter(btn, period) {
            // Remove active from all siblings
            const parent = btn.parentElement;
            const buttons = parent.getElementsByClassName('m3-filter-btn');
            for(let i=0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            // Add active to clicked
            btn.classList.add('active');
            
            // Here you would trigger AJAX update for chart...
            console.log("Filter switched to: " + period);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('m3Chart');
            if(ctx) {
                // Gradient for Chart
                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, '#6366f1'); // Indigo 500
                gradient.addColorStop(1, '#818cf8'); // Indigo 400

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                        datasets: [{
                            label: 'Resultado',
                            data: [12, 19, 10, 15, 8, 12],
                            backgroundColor: gradient,
                            borderRadius: 8,
                            barThickness: 28,
                            hoverBackgroundColor: '#4f46e5'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { 
                                display: true,
                                grid: { color: '#f3f4f6', borderDash: [4, 4] },
                                border: { display: false }
                            },
                            x: { 
                                grid: { display: false }, 
                                border: { display: false },
                                ticks: { color: '#6b7280' }
                            }
                        },
                        layout: { padding: 20 }
                    }
                });
            }
        });
    </script>
@else
    <div class="header-welcome">
        <div>
            <h1>Olá, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p>Aqui está o resumo da sua operação hoje.</p>
        </div>
        <div class="actions">
            <a href="{{ url('/transactions/create') }}" class="btn-action-admin primary">
                <i class="fas fa-plus"></i> Novo Lançamento
            </a>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <!-- Balance Card -->
        <div class="metric-card">
            <div class="icon-box {{ $balance >= 0 ? 'info' : 'danger' }}">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="meta">Saldo Atual</div>
            <div class="value" style="color: {{ $balance >= 0 ? '#2c3e50' : '#ef4444' }}">
                R$ {{ number_format($balance, 2, ',', '.') }}
            </div>
            <div class="trend {{ $balance >= 0 ? 'positive' : 'negative' }}">
                <i class="fas {{ $balance >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i> Fluxo de Caixa
            </div>
        </div>

        @if($role == 'manager')
        <!-- Manager: Active Projects -->
        <div class="metric-card">
            <div class="icon-box warning">
                <i class="fas fa-project-diagram"></i>
            </div>
            <div class="meta">Projetos Ativos</div>
            <div class="value">{{ $stats['active_projects'] ?? 0 }}</div>
            <div class="trend positive">
                <i class="fas fa-check"></i> {{ $stats['completed_projects'] ?? 0 }} Concluídos
            </div>
        </div>
        @endif

        <!-- AI Health Score (Mockup for now, could be dynamic later) -->
        <div class="metric-card">
            <div class="icon-box success" style="padding: 0; overflow: hidden;">
                <img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="meta">Saúde Financeira</div>
            <div class="value">A</div>
            <div class="trend positive">
                <a href="{{ url('/smart-analysis') }}" style="text-decoration: none; color: inherit;">
                    Ver Análise <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="bottom-grid">
        <!-- Recent Transactions -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Últimas Movimentações</h3>
                <a href="{{ url('/transactions') }}" style="font-size: 0.85rem; color: #4f46e5; text-decoration: none;">Ver tudo</a>
            </div>
            
            <div style="overflow-y: auto; flex: 1;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        @foreach($recentTransactions as $t)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 0;">
                                <div style="font-weight: 600; color: #334155;">{{ $t->description }}</div>
                                <div style="font-size: 0.8rem; color: #94a3b8;">{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</div>
                            </td>
                            <td style="padding: 12px 0; text-align: right; font-weight: 700; color: {{ $t->type == 'income' ? '#16a34a' : '#dc2626' }};">
                                {{ $t->type == 'income' ? '+' : '-' }} R$ {{ number_format($t->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(count($recentTransactions) == 0)
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 20px; color: #94a3b8;">Nenhuma movimentação recente.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions / Promo -->
        <div class="chart-card" style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); color: white; justify-content: center; align-items: center; text-align: center;">
            <i class="fas fa-rocket" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.8;"></i>
            <h3 style="color: white; margin-bottom: 10px;">Acelere sua Gestão</h3>
            <p style="opacity: 0.8; margin-bottom: 20px;">Use o Smart Analysis para prever seu fluxo de caixa futuro.</p>
            <a href="{{ url('/smart-analysis') }}" style="background: white; color: #4f46e5; padding: 10px 20px; border-radius: 20px; text-decoration: none; font-weight: 700;">
                Explorar IA
            </a>
        </div>
    </div>
@endif

@endsection
