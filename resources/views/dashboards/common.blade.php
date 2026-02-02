@extends('layouts.app')

@section('content')
@include('partials.onboarding')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Dashboard Pessoal</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.8rem; letter-spacing: -1.5px;">Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Sua saúde financeira resumida em um só lugar.</p>
        </div>
        <div style="display: flex; gap: 12px;">
             <a href="{{ url('/personal/reconciliation') }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-university me-2 text-primary"></i> Conciliar Banco
            </a>
             <a href="{{ url('/transactions/create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
                <i class="fas fa-plus me-2" style="color: #10b981;"></i> Nova Transação
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Saldo Total -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border: none; overflow: hidden; position: relative;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(255,255,255,0.03); transform: rotate(-15deg);"><i class="fas fa-wallet"></i></div>
            <span style="color: rgba(255,255,255,0.6); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Saldo Disponível</span>
            <div style="font-size: 2.6rem; font-weight: 900; color: white; margin-top: 10px; text-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                R$ {{ number_format($balance, 2, ',', '.') }}
            </div>
            <div style="margin-top: 20px; font-size: 0.8rem; background: rgba(255,255,255,0.1); display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 20px; backdrop-filter: blur(5px);">
                <i class="fas fa-shield-alt me-2 text-success"></i> Fundos Protegidos
            </div>
        </div>
    </div>
    
    <!-- Entradas -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: white; border: 1px solid rgba(16,185,129,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Entradas Totais</span>
                    <div style="font-size: 2.2rem; font-weight: 900; color: #10b981; margin-top: 10px;">
                        R$ {{ number_format($totalIncome, 2, ',', '.') }}
                    </div>
                </div>
                <div style="width: 50px; height: 50px; background: #ecfdf5; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.2rem;">
                    <i class="fas fa-arrow-down-long"></i>
                </div>
            </div>
            <div style="margin-top: 25px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; background: #10b981; width: 100%;"></div>
            </div>
        </div>
    </div>

    <!-- Saídas -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: white; border: 1px solid rgba(239,68,68,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Saídas Totais</span>
                    <div style="font-size: 2.2rem; font-weight: 900; color: #ef4444; margin-top: 10px;">
                        R$ {{ number_format($totalExpense, 2, ',', '.') }}
                    </div>
                </div>
                <div style="width: 50px; height: 50px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.2rem;">
                    <i class="fas fa-arrow-up-long"></i>
                </div>
            </div>
             <div style="margin-top: 25px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; background: #ef4444; width: {{ $totalIncome > 0 ? min(100, ($totalExpense / $totalIncome) * 100) : 0 }}%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Chart Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="vivensi-card p-5" style="background: white; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                <div>
                    <h4 style="margin: 0; font-size: 1.4rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Performance Semestral</h4>
                    <p style="margin: 5px 0 0 0; color: #64748b; font-size: 0.9rem;">Análise comparativa de fluxo de caixa</p>
                </div>
                <div style="display: flex; gap: 20px; background: #f8fafc; padding: 10px 20px; border-radius: 12px; border: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
                        <span style="font-size: 0.8rem; color: #1e293b; font-weight: 800;">Receitas</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%;"></span>
                        <span style="font-size: 0.8rem; color: #1e293b; font-weight: 800;">Despesas</span>
                    </div>
                </div>
            </div>
            <div style="height: 350px; width: 100%;">
                <canvas id="financialChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('financialChart').getContext('2d');
        
        // Gradient for Income
        const gradientIncome = ctx.createLinearGradient(0, 0, 0, 350);
        gradientIncome.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
        gradientIncome.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        // Gradient for Expense
        const gradientExpense = ctx.createLinearGradient(0, 0, 0, 350);
        gradientExpense.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
        gradientExpense.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Receitas',
                        data: {!! json_encode($chartIncome) !!},
                        borderColor: '#10b981',
                        backgroundColor: gradientIncome,
                        borderWidth: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Despesas',
                        data: {!! json_encode($chartExpense) !!},
                        borderColor: '#ef4444',
                        backgroundColor: gradientExpense,
                        borderWidth: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ef4444',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        titleFont: { family: "'Outfit', sans-serif", weight: '900', size: 14 },
                        bodyColor: '#f1f5f9',
                        bodyFont: { family: "'Inter', sans-serif", weight: '500' },
                        padding: 15,
                        displayColors: true,
                        boxPadding: 5,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 12, family: "'Outfit', sans-serif", weight: '600' } }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { 
                            color: '#94a3b8', 
                            font: { size: 11, weight: '700' },
                            padding: 10,
                            callback: function(value) {
                                return 'R$ ' + (value >= 1000 ? (value / 1000) + 'k' : value);
                            }
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    });
</script>

<div class="row g-4">
    <!-- Últimas Transações -->
    <div class="col-md-7">
        <div class="vivensi-card" style="padding: 35px; min-height: 520px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Atividade Recente</h4>
                <a href="{{ url('/transactions') }}" class="btn-premium" style="font-size: 0.75rem; padding: 8px 16px; background: #f8fafc; color: var(--primary-color); border: 1px solid #e2e8f0; text-decoration: none; font-weight: 800;">VER HISTÓRICO</a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                @forelse($recentTransactions as $tx)
                <div style="display: flex; align-items: center; padding: 20px; background: white; border: 1px solid #f1f5f9; border-radius: 20px; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.borderColor='var(--primary-color)'; this.style.transform='translateX(5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateX(0)';">
                    <div style="width: 52px; height: 52px; border-radius: 16px; background: {{ $tx->type === 'income' ? '#ecfdf5' : '#fef2f2' }}; display: flex; align-items: center; justify-content: center; margin-right: 20px; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-size: 1.1rem; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <i class="fas {{ $tx->type === 'income' ? 'fa-square-plus' : 'fa-square-minus' }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">{{ $tx->description }}</div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">{{ \Carbon\Carbon::parse($tx->date)->translatedFormat('d \d\e F') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 900; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-size: 1.1rem; letter-spacing: -0.5px;">
                            {{ $tx->type === 'income' ? '+' : '-' }} R$ {{ number_format($tx->amount, 2, ',', '.') }}
                        </div>
                        <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase;">{{ $tx->type === 'income' ? 'Crédito' : 'Débito' }}</span>
                    </div>
                </div>
                @empty
                <div style="text-align: center; padding: 60px; border: 2px dashed #f1f5f9; border-radius: 24px;">
                    <i class="fas fa-receipt d-block mb-3" style="font-size: 3rem; color: #e2e8f0;"></i>
                    <p style="color: #94a3b8; font-weight: 600;">Aguardando seu primeiro lançamento.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Agenda e AI -->
    <div class="col-md-5">
        <div class="vivensi-card" style="padding: 35px; min-height: 250px; background: white; margin-bottom: 24px;">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Pendências</h4>
                <a href="{{ url('/tasks') }}" style="font-size: 0.75rem; font-weight: 800; color: var(--primary-color); text-decoration: none;">AGENDA <i class="fas fa-arrow-right ms-1"></i></a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                @forelse($pendingTasks as $task)
                <div style="padding: 15px 20px; background: #f8fafc; border-radius: 16px; border-left: 6px solid {{ $task->priority === 'high' ? '#ef4444' : '#6366f1' }}; border-top: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">{{ $task->title }}</span>
                        <span style="font-size: 0.65rem; background: {{ $task->priority === 'high' ? '#fef2f2' : '#e0e7ff' }}; padding: 4px 10px; border-radius: 20px; font-weight: 900; color: {{ $task->priority === 'high' ? '#ef4444' : '#4f46e5' }}; text-transform: uppercase;">
                            {{ $task->priority }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">
                            <i class="far fa-calendar-check me-1"></i> {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Rotina' }}
                        </span>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 0.8rem;"></i>
                    </div>
                </div>
                @empty
                 <div style="text-align: center; padding: 40px; border: 1px dashed #f1f5f9; border-radius: 16px;">
                    <p style="color: #94a3b8; margin: 0; font-weight: 600; font-size: 0.9rem;">Nada urgente por hoje! ✨</p>
                </div>
                @endforelse
            </div>
            
            <a href="{{ url('/tasks/create') }}" class="btn-premium" style="width: 100%; margin-top: 25px; background: var(--primary-color); border: none; font-size: 0.9rem; font-weight: 700; text-align: center; display: block; text-decoration: none; padding: 14px;">
                <i class="fas fa-plus-circle me-2"></i> Adicionar Tarefa
            </a>
        </div>

        <!-- Bruce AI Insight Box (Glassmorphism Dark) -->
        <div class="vivensi-card" style="padding: 35px; background: #0f172a; color: white; border: none; position: relative; overflow: hidden; border-radius: 24px; min-height: 240px;">
            <!-- Decorative Glows -->
            <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--primary-color); filter: blur(60px); opacity: 0.4;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: #10b981; filter: blur(50px); opacity: 0.2;"></div>
            
            <div style="display: flex; align-items: center; margin-bottom: 25px; position: relative; z-index: 1;">
                <div style="position: relative; margin-right: 15px;">
                    <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); padding: 2px; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid #0f172a; border-radius: 50%;"></div>
                </div>
                <div>
                    <h5 style="margin: 0; font-weight: 900; font-size: 1.1rem; letter-spacing: -0.5px;">Bruce AI Advisor</h5>
                    <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Sincronizado via Gemini Pro</span>
                </div>
            </div>
            
            <div id="dashboard-ai-tips" style="position: relative; z-index: 1; padding: 15px; border-radius: 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                <div style="display: flex; gap: 12px; align-items: center; color: #f1f5f9; font-size: 0.95rem; line-height: 1.5; font-weight: 500;">
                    <i class="fas fa-circle-notch fa-spin text-primary"></i> <span>Digerindo dados financeiros...</span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; position: relative; z-index: 1;">
                <a href="{{ url('/personal/budget') }}" style="color: var(--primary-light); font-size: 0.75rem; font-weight: 800; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Análise Profunda <i class="fas fa-chevron-right ms-1"></i>
                </a>
                <span style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 600;">FEVEREIRO 2026</span>
            </div>
        </div>

        <!-- Linha do Tempo de Impacto (Pessoa Comum) -->
        <div class="vivensi-card" style="padding: 35px; border-radius: 24px; background: white; margin-top: 24px; border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Linha da Prosperidade</h4>
                <div style="width: 32px; height: 32px; background: #eef2ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0;">
                @forelse($impactFeed as $item)
                    <div style="display: flex; align-items: center; gap: 15px; padding: 18px 0; border-bottom: 1px solid #f8fafc;">
                        <div style="width: 42px; height: 42px; min-width: 42px; border-radius: 12px; background: {{ $item['color'] }}10; color: {{ $item['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid {{ $item['color'] }}20;">
                            <i class="fas {{ $item['icon'] }}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 3px;">{{ $item['title'] }}</div>
                             <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">{{ $item['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 40px 10px;">
                        <p style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Lance novos gastos ou metas para ver sua evolução!</p>
                    </div>
                @endforelse
            </div>
        </div>

        <script>
            async function loadDashboardAi() {
                const container = document.getElementById('dashboard-ai-tips');
                try {
                    const response = await fetch('{{ url("/personal/budget/ai-tips") }}');
                    const data = await response.json();
                    if(!data.error) {
                        const tips = data.tips.split('\n').filter(t => t.trim() !== '').slice(0, 2); 
                        container.innerHTML = '';
                        tips.forEach(tip => {
                            const parts = tip.split('|');
                            const icon = parts[0]?.trim() || '🐶';
                            const text = parts[1]?.trim() || tip;
                            container.innerHTML += `
                                <div style="display: flex; gap: 12px; margin-bottom: 15px; align-items: flex-start;">
                                    <span style="font-size: 1.2rem; filter: drop-shadow(0 0 5px rgba(255,255,255,0.2));">${icon}</span>
                                    <span style="color: #e2e8f0; font-size: 0.85rem; line-height: 1.4; font-weight: 500;">${text.split(':')[0]}</span>
                                </div>`;
                        });
                        if(container.innerHTML === '') {
                             container.innerHTML = '<p style="font-size: 0.8rem; color: #94a3b8;">Tudo limpo! Continue registrando para novos insights.</p>';
                        }
                    }
                } catch(e) {
                    container.innerHTML = `<p style="font-size: 0.7rem; color: #ef4444;">Offline temporariamente.</p>`;
                }
            }
            document.addEventListener('DOMContentLoaded', loadDashboardAi);
        </script>
    </div>
</div>
@endsection
