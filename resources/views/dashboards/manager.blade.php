@extends('layouts.app')

@section('content')
@include('partials.onboarding')

<style>
    .command-center-hero {
        background: #0f172a;
        border-radius: 40px;
        padding: 60px;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
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
        border-radius: 30px;
        padding: 35px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 200px;
    }
    .cmd-stat-card:hover {
        background: rgba(255, 255, 255, 0.06);
        transform: translateY(-8px);
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
        padding: 24px;
        border-radius: 22px;
        background: white;
        border: 1px solid #f1f5f9;
        margin-bottom: 16px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .timeline-item-pro:hover {
        border-color: #e2e8f0;
        transform: translateX(4px);
    }
</style>

<div class="command-center-hero">
    <div style="position: relative; z-index: 10;">
        <div class="hero-glass-pill">
            <span style="width: 8px; height: 8px; background: #6366f1; border-radius: 50%; box-shadow: 0 0 10px #6366f1;"></span>
             Project Manager Intelligence
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 50px;">
            <div>
                <h1 style="font-size: 4rem; font-weight: 950; margin: 0; letter-spacing: -3px; line-height: 0.9; color: white;">Centro de Comando</h1>
                <p style="margin: 20px 0 0 0; color: rgba(255,255,255,0.5); font-size: 1.2rem; font-weight: 500; max-width: 600px;">
                    Visão estratégica consolidada do seu portfólio de impacto e execução de alta performance.
                </p>
            </div>
            <div style="display: flex; gap: 15px;">
                <a href="{{ url('/projects/reports') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                    <i class="fas fa-chart-line" style="color: #94a3b8;"></i> Relatórios
                </a>
                <a href="{{ url('/projects/create') }}" class="btn-action-pro" style="background: white; color: #0f172a; box-shadow: 0 10px 30px rgba(255,255,255,0.1);">
                    <i class="fas fa-plus" style="color: #6366f1;"></i> Novo Projeto
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Active Mission -->
            <div class="col-md-4">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.7rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Missões Ativas</span>
                        <div style="font-size: 3.2rem; font-weight: 950; margin-top: 10px; letter-spacing: -2px;">{{ $activeProjects }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: #10b981; font-weight: 800; font-size: 0.85rem;">
                        <i class="fas fa-satellite-dish"></i> Sincronizado
                    </div>
                </div>
            </div>
            
            <!-- Pending Milestones -->
            <div class="col-md-4">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.7rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Alertas do Radar</span>
                        <div style="font-size: 3.2rem; font-weight: 950; margin-top: 10px; color: #f59e0b; letter-spacing: -2px;">{{ $stats['pending_tasks'] }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: #f59e0b; font-weight: 800; font-size: 0.85rem;">
                        <i class="fas fa-wave-square"></i> Ação Requerida
                    </div>
                </div>
            </div>

            <!-- Team Reach -->
            <div class="col-md-4">
                <div class="cmd-stat-card">
                    <div>
                        <span style="font-size: 0.7rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px;">Célula Operacional</span>
                        <div style="font-size: 3.2rem; font-weight: 950; margin-top: 10px; letter-spacing: -2px;">{{ $stats['team_size'] }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.4); font-weight: 800; font-size: 0.85rem;">
                        <i class="fas fa-network-wired"></i> Membros Ativos
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5">
    <!-- Timeline Section -->
    <div class="col-lg-8">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h3 style="color: #1e293b; font-weight: 950; font-size: 1.8rem; letter-spacing: -1.5px; margin: 0;">Feed de Impacto</h3>
            <span style="font-size: 0.7rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Sincronismo Digital</span>
        </div>
        
        <div>
            @forelse($impactFeed as $item)
                <div class="timeline-item-pro">
                    <div style="width: 54px; height: 54px; min-width: 54px; border-radius: 16px; background: {{ $item['color'] }}10; border: 1px solid {{ $item['color'] }}20; display: flex; align-items: center; justify-content: center; color: {{ $item['color'] }}; font-size: 1.2rem;">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 1.05rem; margin-bottom: 4px;">{{ $item['title'] }}</div>
                        <div style="display: flex; align-items: center; gap: 10px; color: #94a3b8; font-size: 0.8rem; font-weight: 700;">
                            <span>{{ $item['time'] }}</span>
                            <span style="width: 4px; height: 4px; background: #cbd5e1; border-radius: 50%;"></span>
                            <span style="color: #6366f1;">Registro Automático</span>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 100px 40px; background: #f8fafc; border-radius: 32px; border: 2px dashed #e2e8f0;">
                    <i class="fas fa-satellite-dish d-block mb-4" style="font-size: 3rem; color: #cbd5e1;"></i>
                    <p style="font-weight: 800; color: #94a3b8; font-size: 1.1rem;">Aguardando transmissões de impacto...</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions Sidebar -->
    <div class="col-lg-4">
        <div style="background: white; border-radius: 32px; padding: 40px; border: 1px solid #f1f5f9; box-shadow: 0 20px 50px rgba(0,0,0,0.03);">
            <h4 style="font-weight: 950; color: #1e293b; margin-bottom: 30px; letter-spacing: -1px;">Comando Rápido</h4>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="{{ url('/manager/schedule') }}" class="btn-action-pro" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; justify-content: space-between;">
                    <span><i class="fas fa-calendar-alt me-2" style="color: #6366f1;"></i> Agenda Integrada</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/manager/approvals') }}" class="btn-action-pro" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; justify-content: space-between;">
                    <span><i class="fas fa-check-double me-2" style="color: #10b981;"></i> Aprovações Pendentes</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/manager/team') }}" class="btn-action-pro" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; justify-content: space-between;">
                    <span><i class="fas fa-user-friends me-2" style="color: #f59e0b;"></i> Gestão de Stakeholders</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/smart-analysis') }}" class="btn-action-pro" style="background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; justify-content: space-between; margin-top: 10px;">
                    <span><i class="fas fa-brain me-2" style="color: #4f46e5;"></i> Smart AI Analysis</span>
                    <i class="fas fa-bolt" style="color: #f59e0b; font-size: 0.7rem;"></i>
                </a>
            </div>

            <div style="margin-top: 40px; padding: 25px; background: #fffbeb; border-radius: 20px; border: 1px solid #fef3c7;">
                <div style="font-size: 0.7rem; font-weight: 900; color: #b45309; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Dica do Dia</div>
                <p style="margin: 0; font-size: 0.85rem; color: #92400e; font-weight: 600; line-height: 1.5;">Projetos com atualizações diárias têm 45% mais chance de sucesso.</p>
            </div>
        </div>
    </div>
</div>
@endsection
