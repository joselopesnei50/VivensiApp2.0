@extends('layouts.app')

@section('content')
@include('partials.onboarding')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #4f46e5; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #4f46e5; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Dashboard de Projetos</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.8rem; letter-spacing: -1.5px;">Missão & Visão</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie seu portfólio de impacto com precisão cirúrgica.</p>
        </div>
        <div style="display: flex; gap: 12px;">
             <a href="{{ url('/projects/reports') }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-chart-pie me-2 text-indigo-600"></i> Relatórios Analíticos
            </a>
             <a href="{{ url('/projects/create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
                <i class="fas fa-plus-circle me-2" style="color: #4f46e5;"></i> Iniciar Projeto
            </a>
        </div>
    </div>
</div>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
    <!-- Stat 1: Active Projects -->
    <div class="stat-card" style="background: white; padding: 35px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; font-size: 6rem; color: #f1f5f9; opacity: 0.5;"><i class="fas fa-rocket"></i></div>
        <div style="color: #94a3b8; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">PROJETOS ATIVOS</div>
        <div style="font-size: 3rem; font-weight: 900; color: #1e293b; line-height: 1;">{{ $activeProjects }}</div>
        <div style="font-size: 0.85rem; color: #10b981; margin-top: 20px; font-weight: 700;">
            <i class="fas fa-check-circle me-1"></i> Em plena execução
        </div>
    </div>

    <!-- Stat 2: Pending Tasks -->
    <div class="stat-card" style="background: white; padding: 35px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; position: relative; overflow: hidden;">
         <div style="position: absolute; top: -10px; right: -10px; font-size: 6rem; color: #fffbeb; opacity: 1;"><i class="fas fa-tasks text-amber-100"></i></div>
        <div style="color: #94a3b8; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">MARCOS PENDENTES</div>
        <div style="font-size: 3rem; font-weight: 900; color: #1e293b; line-height: 1;">{{ $stats['pending_tasks'] }}</div>
        <div style="font-size: 0.85rem; color: #f59e0b; margin-top: 20px; font-weight: 700;">
            <i class="fas fa-exclamation-triangle me-1"></i> Atenção requerida
        </div>
    </div>

    <!-- Stat 3: Team Health -->
    <div class="stat-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 35px; border-radius: 24px; color: white; border: none; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; font-size: 6rem; color: rgba(255,255,255,0.03);"><i class="fas fa-users"></i></div>
        <div style="color: rgba(255,255,255,0.5); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">EQUIPE & COLABORAÇÃO</div>
        <div style="font-size: 3rem; font-weight: 900; color: white; line-height: 1;">{{ $stats['team_size'] }}</div>
        <div style="font-size: 0.85rem; color: rgba(255,255,255,0.7); margin-top: 20px; font-weight: 700;">
            Membros ativos no ecossistema
        </div>
    </div>
</div>

<div class="recent-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="color: #1e293b; font-weight: 900; font-size: 1.4rem; letter-spacing: -0.5px;">Linha do Tempo de Impacto</h3>
        <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Acompanhamento em Tempo Real</span>
    </div>
    
    <div style="background: white; border-radius: 24px; padding: 30px; border: 1px solid #f1f5f9;">
        @forelse($impactFeed as $item)
            <div style="display: flex; align-items: flex-start; gap: 20px; padding: 20px; border-bottom: 1px solid #f8fafc; position: relative;">
                <div style="width: 48px; height: 48px; min-width: 48px; border-radius: 12px; background: {{ $item['color'] }}15; border: 1px solid {{ $item['color'] }}30; display: flex; align-items: center; justify-content: center; color: {{ $item['color'] }}; font-size: 1.1rem;">
                    <i class="fas {{ $item['icon'] }}"></i>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">{{ $item['title'] }}</div>
                     <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">{{ $item['time'] }}</div>
                </div>
                <div style="font-size: 0.7rem; background: #f1f5f9; padding: 4px 10px; border-radius: 20px; color: #64748b; font-weight: 800;">AUTO-SINC</div>
            </div>
        @empty
            <div style="text-align: center; padding: 60px; color: #94a3b8;">
                <i class="fas fa-satellite-dish d-block mb-3" style="font-size: 2.5rem; opacity: 0.2;"></i>
                <p style="font-weight: 600;">Monitorando novas atividades nos projetos...</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
