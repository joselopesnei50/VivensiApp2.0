@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Gestão de Portfólio</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Iniciar Novo Projeto</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Transforme sua visão em execução organizada.</p>
        </div>
        <a href="{{ $basePath . '/projects' }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
            <i class="fas fa-arrow-left me-2"></i> Voltar à Lista
        </a>
    </div>
</div>

<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; border: 1px solid #f1f5f9; background: white; padding: 45px; border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.02);">
    
    <!-- Exibição de Erros do Laravel -->
    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #fecaca; display: flex; gap: 15px; align-items: center;">
            <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
            <ul style="margin: 0; padding-left: 0; list-style: none; font-weight: 600; font-size: 0.9rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $basePath . '/projects' }}" method="POST">
        @csrf

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #eef2ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 0.9rem;"><i class="fas fa-info-circle"></i></span>
                Informações Principais
            </h5>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Escolha um Nome Impactante</label>
                <div style="position: relative;">
                    <i class="fas fa-rocket" style="position: absolute; left: 20px; top: 18px; color: #94a3b8; font-size: 1rem;"></i>
                    <input type="text" name="name" class="form-control-vivensi" required 
                           style="width: 100%; padding: 16px 20px 16px 50px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; font-size: 1rem; transition: all 0.3s;" 
                           onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white'; this.style.boxShadow='0 10px 20px rgba(79, 70, 229, 0.05)';"
                           onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc'; this.style.boxShadow='none';"
                           placeholder="Ex: Expansão Nacional 2026" value="{{ old('name') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Missão & Objetivos</label>
                <textarea name="description" class="form-control-vivensi" rows="4" 
                          style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 500; font-size: 0.95rem; resize: none; transition: all 0.3s;" 
                          onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';"
                          onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc';"
                          placeholder="Quais serão os entregáveis e o impacto deste projeto?">{{ old('description') }}</textarea>
            </div>
        </div>

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #fffbeb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 0.9rem;"><i class="fas fa-wallet"></i></span>
                Recursos & Status
            </h5>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Investimento Planejado</label>
                        <div style="position: relative;">
                             <span style="position: absolute; left: 20px; top: 16px; color: #10b981; font-weight: 800; font-size: 1rem;">R$</span>
                             <input type="text" name="budget" class="form-control-vivensi" placeholder="0,00" required 
                                    style="width: 100%; padding: 16px 20px 16px 55px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; font-size: 1.1rem;" 
                                    value="{{ old('budget') }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Status da Missão</label>
                        <select name="status" class="form-control-vivensi" 
                                style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                            <option value="active" selected>🚀 Em Execução</option>
                            <option value="paused">⏸️ Pausado</option>
                            <option value="completed">✅ Concluído</option>
                            <option value="canceled">⛔ Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 45px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #f0fdf4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 0.9rem;"><i class="fas fa-calendar-alt"></i></span>
                Cronograma
            </h5>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Data de Decolagem</label>
                        <input type="date" name="start_date" class="form-control-vivensi" required 
                               style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; color: #1e293b;" 
                               value="{{ old('start_date', date('Y-m-d')) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Pouso Previsto (Opcional)</label>
                        <input type="date" name="end_date" class="form-control-vivensi" 
                               style="width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 16px; background: #f8fafc; font-weight: 600; color: #1e293b;" 
                               value="{{ old('end_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 18px; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 10px;">
                 Lançar Projeto Ativo <i class="fas fa-paper-plane"></i>
            </button>
            <a href="{{ $basePath . '/projects' }}" style="flex: 1; text-align: center; color: #94a3b8; font-weight: 800; font-size: 0.9rem; text-decoration: none; text-transform: uppercase;">Descartar</a>
        </div>
    </form>
</div>
@endsection
