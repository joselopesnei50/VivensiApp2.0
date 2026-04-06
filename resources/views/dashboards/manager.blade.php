@extends('layouts.app')

@section('content')
@include('partials.onboarding')

<style>
    .command-center-hero {
        background: #0f172a;
        border-radius: 40px;
        padding: 55px 60px 40px;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 36px;
        border: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 40px 100px rgba(0,0,0,0.2);
    }
    .command-center-hero::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                    radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
        z-index: 1;
    }
    .hero-glass-pill {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        padding: 8px 20px;
        border-radius: 100px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 25px;
    }
    .cmd-stat-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 28px;
        padding: 30px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 170px;
    }
    .cmd-stat-card:hover {
        background: rgba(255, 255, 255, 0.06);
        transform: translateY(-6px);
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    }
    .btn-action-pro {
        padding: 14px 28px;
        border-radius: 18px;
        font-weight: 800;
        font-size: 0.9rem;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }
    .btn-action-pro:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }
    .timeline-item-pro {
        padding: 20px 24px;
        border-radius: 20px;
        background: white;
        border: 1px solid #f1f5f9;
        margin-bottom: 14px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .timeline-item-pro:hover {
        border-color: #e2e8f0;
        transform: translateX(4px);
    }
    .progress-mini {
        height: 6px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-mini-bar {
        height: 100%;
        border-radius: 99px;
        transition: width 0.6s ease;
    }
</style>

{{-- ===== HERO / COMMAND CENTER ===== --}}
<div class="command-center-hero">
    <div style="position: relative; z-index: 10;">
        <div class="hero-glass-pill">
            <span style="width: 8px; height: 8px; background: #6366f1; border-radius: 50%; box-shadow: 0 0 10px #6366f1;"></span>
             Project Manager Intelligence
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="font-size: 3.4rem; font-weight: 950; margin: 0; letter-spacing: -2.5px; line-height: 0.95; color: white;">Centro de Comando</h1>
                <p style="margin: 18px 0 0 0; color: rgba(255,255,255,0.5); font-size: 1.1rem; font-weight: 500;">
                    Visão estratégica consolidada — {{ now()->translatedFormat('l, d \d\e F') }}
                </p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ url('/manager/approvals') }}" class="btn-action-pro" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                    <i class="fas fa-clock-rotate-left"></i> Aprovações
                    @if($stats['pending_approvals'] > 0)
                        <span style="background: #ef4444; color: white; border-radius: 99px; padding: 2px 8px; font-size: .7rem;">{{ $stats['pending_approvals'] }}</span>
                    @endif
                </a>
                <a href="{{ url('/smart-analysis') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                    <i class="fas fa-chart-line" style="color: #94a3b8;"></i> Relatórios
                </a>
                <a href="{{ url('/projects/create') }}" class="btn-action-pro" style="background: white; color: #0f172a; box-shadow: 0 10px 30px rgba(255,255,255,0.1);">
                    <i class="fas fa-plus" style="color: #6366f1;"></i> Novo Projeto
                </a>
            </div>
        </div>

        {{-- KPIs no hero --}}
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Missões Ativas</span>
                        <div style="font-size: 2.8rem; font-weight: 950; margin-top: 8px; letter-spacing: -2px;">{{ $activeProjects }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: #10b981; font-weight: 800; font-size: 0.8rem;">
                        <i class="fas fa-satellite-dish"></i> Sincronizado
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Radar de Alertas</span>
                        <div style="font-size: 2.8rem; font-weight: 950; margin-top: 8px; color: #f59e0b; letter-spacing: -2px;">{{ $stats['pending_tasks'] }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: #f59e0b; font-weight: 800; font-size: 0.8rem;">
                        <i class="fas fa-wave-square"></i> Ação Requerida
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Entrada / Mês</span>
                        <div style="font-size: 1.6rem; font-weight: 950; margin-top: 8px; color: #34d399; letter-spacing: -1px;">
                            R$ {{ number_format($stats['monthly_income'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: #34d399; font-weight: 800; font-size: 0.8rem;">
                        <i class="fas fa-arrow-trend-up"></i>
                        Saldo: R$ {{ number_format($stats['monthly_balance'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Célula Operacional</span>
                        <div style="font-size: 2.8rem; font-weight: 950; margin-top: 8px; letter-spacing: -2px;">{{ $stats['team_size'] }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.4); font-weight: 800; font-size: 0.8rem;">
                        <i class="fas fa-network-wired"></i> Membros Ativos
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== LINHA PRINCIPAL ===== --}}
<div class="row g-4 mb-4">

    {{-- Portfólio de Projetos --}}
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Portfólio de Projetos</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Progresso em tempo real</p>
                </div>
                <a href="{{ url('/projects') }}" style="font-size: 0.75rem; font-weight: 900; color: #6366f1; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Ver Todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            @forelse($projects as $proj)
            @php
                $statusColors = [
                    'active'    => ['#10b981', 'rgba(16,185,129,0.1)', 'Ativo'],
                    'paused'    => ['#f59e0b', 'rgba(245,158,11,0.1)', 'Pausado'],
                    'completed' => ['#6366f1', 'rgba(99,102,241,0.1)', 'Concluído'],
                    'canceled'  => ['#94a3b8', 'rgba(255,255,255,0.05)', 'Cancelado'],
                ];
                $sc = $statusColors[$proj->status] ?? ['white','rgba(255,255,255,0.05)','—'];
                $barColor = $proj->progress >= 100 ? '#10b981' : ($proj->progress >= 60 ? '#6366f1' : '#f59e0b');
            @endphp
            <div style="padding: 18px 20px; border: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); border-radius: 18px; margin-bottom: 12px; transition:.2s;" onmouseover="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.transform='translateX(4px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'; this.style.transform=''">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div>
                        <a href="{{ url('/projects/'.$proj->id) }}" style="font-weight:800; color:white; font-size:.95rem; text-decoration:none;">{{ $proj->name }}</a>
                        @if($proj->end_date)
                            @php $daysLeft = now()->diffInDays($proj->end_date, false); @endphp
                            <span style="margin-left:10px; font-size:.7rem; color:{{ $daysLeft < 7 ? '#dc2626' : '#94a3b8' }}; font-weight:700;">
                                <i class="far fa-clock me-1"></i>
                                {{ $daysLeft < 0 ? 'Vencido há '.abs((int)$daysLeft).'d' : (int)$daysLeft.'d restantes' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="progress-mini" style="background: rgba(255,255,255,0.05);">
                    <div class="progress-mini-bar" style="width:{{ $proj->progress }}%; background:{{ $barColor }}; box-shadow: 0 0 10px {{ $barColor }};"></div>
                </div>
            </div>
            @empty
            <x-empty-state 
                icon="fa-rocket" 
                title="Nenhum Projeto Ativo" 
                description="Você ainda não possui projetos nesta organização. Crie seu primeiro projeto para começar a gerenciar tarefas e orçamentos." 
                action_label="Criar Novo Projeto" 
                action_url="{{ url('/projects/create') }}" 
            />
            @endforelse
        </div>
    </div>

    {{-- Radar de Saúde AI --}}
    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%; display: flex; flex-direction: column; justify-content: center;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: white; font-weight: 950; font-size: 1.3rem; letter-spacing: -1px; margin: 0;">Radar de Saúde</h3>
                <p style="color: #6366f1; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Análise Operacional IA</p>
            </div>
            <div style="position: relative; width: 100%; max-width: 300px; margin: 0 auto;">
                <canvas id="healthRadarChart"></canvas>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-around;">
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Média Geral</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: #10b981;">{{ round(collect($radarData['scores'])->avg()) }}%</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Status</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: #6366f1;">Estável</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Cadência Operacional</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Projetos criados e tarefas concluídas (6 meses)</p>
                </div>
                <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ url('/projects') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); padding: 10px 16px;">
                        <i class="fas fa-folder-open" style="color:#6366f1;"></i> Projetos
                    </a>
                    <a href="{{ url('/tasks') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); padding: 10px 16px;">
                        <i class="fas fa-list-check" style="color:#10b981;"></i> Tarefas
                    </a>
                </div>
            </div>
            <div style="height: 300px;">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Feed de Impacto</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size:.85rem; margin:4px 0 0 0;">Marcos concluídos recentemente pela equipe</p>
                </div>
                <a href="{{ url('/tasks') }}" style="font-size: 0.75rem; font-weight: 900; color: #6366f1; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Ver Atividades <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row g-3">
                @forelse($impactFeed as $item)
                    <div class="col-md-6">
                        <div class="timeline-item-pro" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 0;">
                            <div style="width: 50px; height: 50px; min-width: 50px; border-radius: 14px; background: {{ $item['color'] }}20; border: 1px solid {{ $item['color'] }}30; display: flex; align-items: center; justify-content: center; color: {{ $item['color'] }}; font-size: 1.1rem;">
                                <i class="fas {{ $item['icon'] }}"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 800; color: white; font-size: .9rem; margin-bottom: 4px;">{{ $item['title'] }}</div>
                                <div style="display: flex; align-items: center; gap: 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 700;">
                                    <span>{{ $item['time'] }}</span>
                                    <span style="color: #6366f1;">· Registro Automático</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <x-empty-state
                            icon="fa-satellite-dish"
                            title="Nenhuma Atividade Recente"
                            description="O radar ainda não captou atualizações. Conclua tarefas ou projetos para gerar impacto."
                        />
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                <h4 style="color: white; font-weight: 950; font-size: 1.1rem; margin: 0;">⚠️ Tarefas Urgentes</h4>
                <a href="{{ url('/tasks') }}" style="font-size:.7rem; font-weight:900; color:#6366f1; text-decoration:none; text-transform:uppercase;">Ver Todas</a>
            </div>
            @forelse($urgentTasks as $task)
            @php
                $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast();
                $prioColors = ['high'=>'#ef4444','critical'=>'#dc2626','medium'=>'#f59e0b','low'=>'#10b981'];
                $pc = $prioColors[$task->priority] ?? '#94a3b8';
            @endphp
            <div style="padding:14px 16px; border-radius:14px; border-left:4px solid {{ $isOverdue ? '#dc2626' : $pc }}; background:rgba(255,255,255,0.03); margin-bottom:10px; border-top:1px solid rgba(255,255,255,0.02); border-right:1px solid rgba(255,255,255,0.02); border-bottom:1px solid rgba(255,255,255,0.02);">
                <div style="font-weight:800; color:white; font-size:.875rem; margin-bottom:5px;">{{ \Illuminate\Support\Str::limit($task->title, 50) }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:.7rem; color:{{ $isOverdue ? '#dc2626' : '#94a3b8' }}; font-weight:700;">
                        <i class="far fa-calendar-xmark me-1"></i>
                        {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}
                        @if($isOverdue) <strong>(VENCIDA)</strong>@endif
                    </span>
                    <span style="font-size:.65rem; background:{{ $pc }}20; color:{{ $pc }}; padding:3px 8px; border-radius:99px; font-weight:900; text-transform:uppercase;">{{ $task->priority }}</span>
                </div>
            </div>
            @empty
            <x-empty-state
                icon="fa-glass-cheers"
                title="Tudo em dia!"
                description="Nenhuma tarefa atrasada ou urgente no momento. Ótimo trabalho da equipe!"
            />
            @endforelse
        </div>

        @if($pendingApprovals->count() > 0)
        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(239,68,68,0.2); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                <h4 style="color: white; font-weight: 950; font-size: 1.1rem; margin: 0;">🔔 Aprovações Pendentes</h4>
                <span style="background:rgba(239,68,68,0.2); color:#ef4444; font-size:.7rem; font-weight:900; padding:4px 10px; border-radius:99px;">{{ $pendingApprovals->count() }}</span>
            </div>
            @foreach($pendingApprovals as $ap)
            <div style="padding:12px 14px; background:rgba(239,68,68,0.05); border-radius:12px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:800; color:white; font-size:.8rem;">{{ \Illuminate\Support\Str::limit($ap->description, 40) }}</div>
                    <div style="font-size:.7rem; color:rgba(255,255,255,0.5); font-weight:700;">{{ \Carbon\Carbon::parse($ap->date)->format('d/m/Y') }}</div>
                </div>
                <span style="font-weight:900; color:#dc2626; font-size:.875rem;">-R$ {{ number_format($ap->amount,2,',','.') }}</span>
            </div>
            @endforeach
            <a href="{{ url('/manager/approvals') }}" style="display:block; text-align:center; padding:10px; background:#ef4444; color:white; border-radius:12px; font-weight:800; font-size:.8rem; text-decoration:none; margin-top:12px;">
                Revisar Aprovações
            </a>
        </div>
        @endif

        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h4 style="font-weight: 950; color: white; margin-bottom: 22px; font-size:1.1rem;">⚡ Comando Rápido</h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ url('/manager/schedule') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-calendar-alt me-2" style="color: #818cf8;"></i> Agenda Integrada</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/transactions') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-wallet me-2" style="color: #34d399;"></i> Fluxo de Caixa</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/manager/team') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-user-friends me-2" style="color: #fbbf24;"></i> Gestão de Equipe</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/smart-analysis') }}" class="btn-action-pro" style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.3); color: white; justify-content: space-between; padding:12px 18px; margin-top:4px;">
                    <span><i class="fas fa-brain me-2" style="color: #818cf8;"></i> Smart AI Analysis</span>
                    <i class="fas fa-bolt" style="color: #f59e0b; font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxRadar = document.getElementById('healthRadarChart').getContext('2d');
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: @json($radarData['labels']),
                datasets: [{
                    label: 'Score Operacional',
                    data: @json($radarData['scores']),
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: '#6366f1',
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#6366f1',
                    borderWidth: 3
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        pointLabels: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: { size: 10, weight: '700', family: 'Inter' }
                        },
                        ticks: { display: false, stepSize: 20 },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                },
                plugins: { legend: { display: false } },
                maintainAspectRatio: true
            }
        });

        const ctxUsage = document.getElementById('usageChart').getContext('2d');
        new Chart(ctxUsage, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Projetos',
                        data: @json($chartProjects),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.15)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Tarefas Concluídas',
                        data: @json($chartTasks),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, labels: { color: 'rgba(255,255,255,0.7)', font: { weight: '700' } } },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12
                    }
                },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11, family: "'Outfit', sans-serif" } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)' }
                    }
                }
            }
        });
    });
</script>
@endpush

@endsection
