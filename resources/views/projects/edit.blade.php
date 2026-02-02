@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Configurações Estratégicas</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Ajustar Portfólio</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Refinando a visão e o planejamento do projeto <strong>{{ $project->name }}</strong>.</p>
        </div>
        <a href="{{ url('/projects/details/'.$project->id) }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
            <i class="fas fa-arrow-left me-2"></i> Voltar ao Painel
        </a>
    </div>
</div>

<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; border: 1px solid #f1f5f9; background: white; padding: 45px; border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.02);">
    
    <form action="{{ url('/projects/'.$project->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #eef2ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 0.9rem;"><i class="fas fa-edit"></i></span>
                Identidade do Projeto
            </h5>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Nome Principal</label>
                <div style="position: relative;">
                    <i class="fas fa-rocket" style="position: absolute; left: 20px; top: 18px; color: #94a3b8; font-size: 1rem;"></i>
                    <input type="text" name="name" class="form-control-vivensi" required 
                           style="width: 100%; padding: 16px 20px 16px 50px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; font-size: 1rem; transition: all 0.3s;" 
                           placeholder="Ex: Expansão Nacional 2026" value="{{ $project->name }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Memorando de Escopo</label>
                <textarea name="description" class="form-control-vivensi" rows="4" 
                          style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 500; font-size: 0.95rem; resize: none; transition: all 0.3s;" 
                          placeholder="Quais serão os entregáveis e o impacto deste projeto?">{{ $project->description }}</textarea>
            </div>
        </div>

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #fffbeb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 0.9rem;"><i class="fas fa-chart-line"></i></span>
                Alocação & Status
            </h5>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Investimento Planejado (R$)</label>
                        <div style="position: relative;">
                             <span style="position: absolute; left: 20px; top: 16px; color: #10b981; font-weight: 800; font-size: 1rem;">R$</span>
                             <input type="number" step="0.01" name="budget" class="form-control-vivensi" required 
                                    style="width: 100%; padding: 16px 20px 16px 55px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; font-size: 1.1rem;" 
                                    value="{{ $project->budget }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Fase Atual</label>
                        <select name="status" class="form-control-vivensi" 
                                style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                            <option value="planning" {{ $project->status == 'planning' ? 'selected' : '' }}>📋 Planejamento Estratégico</option>
                            <option value="active" {{ $project->status == 'active' ? 'selected' : '' }}>🚀 Em Execução Ativa</option>
                            <option value="on_hold" {{ $project->status == 'on_hold' ? 'selected' : '' }}>⏸️ Pausado Temporariamente</option>
                            <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>✅ Missão Concluída</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 45px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #f0fdf4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 0.9rem;"><i class="fas fa-calendar-check"></i></span>
                Cronograma de Entrega
            </h5>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Data de Início</label>
                        <input type="date" name="start_date" class="form-control-vivensi" required 
                               style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; color: #1e293b;" 
                               value="{{ $project->start_date ? $project->start_date->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Pouso Previsto (Opcional)</label>
                        <input type="date" name="end_date" class="form-control-vivensi" 
                               style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; color: #1e293b;" 
                               value="{{ $project->end_date ? $project->end_date->format('Y-m-d') : '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 18px; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 10px;">
                 Salvar Protocolo <i class="fas fa-check-circle"></i>
            </button>
            <a href="{{ url('/projects/details/'.$project->id) }}" style="flex: 1; text-align: center; color: #94a3b8; font-weight: 800; font-size: 0.9rem; text-decoration: none; text-transform: uppercase;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
