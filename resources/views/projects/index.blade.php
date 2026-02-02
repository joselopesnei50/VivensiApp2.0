@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Ecossistema Vivensi</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Portfólio de Projetos</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie suas iniciativas com inteligência e controle total.</p>
        </div>
        <a href="{{ url('/projects/create') }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-plus-circle"></i> Iniciar Projeto
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
@endif

<div class="projects-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px;">
    @forelse($projects as $project)
        <div class="project-card" style="background: white; border-radius: 28px; box-shadow: 0 15px 45px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; padding: 35px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;"
             onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 30px 60px rgba(0,0,0,0.05)'; this.style.borderColor='var(--primary-color)';"
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 45px rgba(0,0,0,0.02)'; this.style.borderColor='#f1f5f9';">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                <div class="project-icon" style="width: 56px; height: 56px; background: #eef2ff; color: var(--primary-color); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid #e0e7ff;">
                    <i class="fas fa-rocket"></i>
                </div>
                <div>
                     @php
                        $statusColors = [
                            'active' => ['bg' => '#ecfdf5', 'text' => '#10b981', 'label' => '🚀 Em Execução'],
                            'planning' => ['bg' => '#eff6ff', 'text' => '#3b82f6', 'label' => '📋 Planejamento'],
                            'on_hold' => ['bg' => '#fff7ed', 'text' => '#f59e0b', 'label' => '⏸️ Pausado']
                        ];
                        $st = $statusColors[$project->status] ?? ['bg' => '#f1f5f9', 'text' => '#64748b', 'label' => $project->status];
                    @endphp
                    <span class="status-badge" style="background: {{ $st['bg'] }}; color: {{ $st['text'] }}; padding: 8px 16px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                        {{ $st['label'] }}
                    </span>
                </div>
            </div>
            
            <h4 style="margin: 0 0 12px 0; font-size: 1.4rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">{{ $project->name }}</h4>
            <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.6; font-weight: 500;">
                {{ Str::limit($project->description, 100) }}
            </p>
            
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Progresso da Missão</span>
                    <span style="font-size: 0.75rem; color: #1e293b; font-weight: 900;">65%</span>
                </div>
                <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%); width: 65%;"></div>
                </div>
            </div>

            <div class="project-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px 0; border-top: 1px solid #f8fafc; border-bottom: 1px solid #f8fafc; margin-bottom: 25px;">
                <div>
                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Investimento</span>
                    <span style="font-weight: 800; color: #1e293b; font-size: 1.1rem;">R$ {{ number_format($project->budget, 0, ',', '.') }}</span>
                </div>
                <div style="text-align: right;">
                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Deadline</span>
                    <span style="font-weight: 800; color: #475569; font-size: 1.1rem;">{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M, Y') : 'Fluxo Contínuo' }}</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <a href="{{ url('/projects/details/'.$project->id) }}" style="text-align: center; background: white; padding: 15px; border-radius: 14px; color: #1e293b; font-weight: 800; text-decoration: none; border: 2px solid #f1f5f9; transition: all 0.2s; font-size: 0.85rem;">
                    <i class="fas fa-eye me-2 text-primary"></i> Visão Geral
                </a>
                <a href="{{ url('/projects/'.$project->id.'/kanban') }}" style="text-align: center; background: #1e293b; padding: 15px; border-radius: 14px; color: white; font-weight: 800; text-decoration: none; border: none; transition: all 0.2s; font-size: 0.85rem;">
                    <i class="fas fa-columns me-2" style="color: var(--primary-light);"></i> Kanban
                </a>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 100px 20px; background: white; border-radius: 28px; border: 2px dashed #f1f5f9;">
            <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #e2e8f0;"></i>
            </div>
            <h3 style="color: #1e293b; font-weight: 900; font-size: 1.8rem; margin-bottom: 10px;">Silêncio no Radar</h3>
            <p style="color: #94a3b8; font-size: 1.1rem; max-width: 500px; margin: 0 auto 30px; font-weight: 500;">Parece que você ainda não iniciou nenhuma missão estratégica. Vamos começar agora?</p>
            <a href="{{ url('/projects/create') }}" class="btn-premium" style="display: inline-block; text-decoration: none; font-weight: 800; padding: 15px 40px;">CRIA PRIMEIRO PROJETO</a>
        </div>
    @endforelse
</div>
@endsection
