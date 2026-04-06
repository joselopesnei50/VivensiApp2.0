@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #6366f1; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #6366f1; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Agenda & Colaboração</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Agendar Nova Tarefa</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Organize o tempo e as responsabilidades da sua equipe.</p>
        </div>
        <a href="{{ $basePath . '/tasks' }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
            <i class="fas fa-calendar-alt me-2 text-primary"></i> Ver Agenda Full
        </a>
    </div>
</div>

<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; border: 1px solid #f1f5f9; background: white; padding: 45px; border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.02);">
    
    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #fecaca; display: flex; gap: 15px; align-items: center;">
            <i class="fas fa-calendar-xmark" style="font-size: 1.5rem;"></i>
            <ul style="margin: 0; padding-left: 0; list-style: none; font-weight: 600; font-size: 0.9rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $basePath . '/tasks' }}" method="POST">
        @csrf
        <input type="hidden" name="redirect_to_schedule" value="1">

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #eef2ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6366f1; font-size: 0.9rem;"><i class="fas fa-bullseye"></i></span>
                Definição da Atividade
            </h5>

            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">O que deve ser feito?</label>
                <div style="position: relative;">
                    <i class="fas fa-check-to-slot" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 1.1rem;"></i>
                    <input type="text" name="title" required 
                           style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 600; font-size: 1rem; color: #1e293b; transition: all 0.3s;"
                           onfocus="this.style.borderColor='#6366f1'; this.style.background='white';"
                           onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc';"
                           placeholder="Ex: Revisão de Relatório Trimestral" value="{{ old('title') }}">
                </div>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Instruções ou Contexto</label>
                <textarea name="description" rows="3" 
                          style="width: 100%; padding: 18px 20px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 500; font-size: 0.95rem; color: #1e293b; resize: none; transition: all 0.3s;" 
                          onfocus="this.style.borderColor='#6366f1'; this.style.background='white';"
                          onblur="this.style.borderColor='#f1f5f9'; this.style.background='#f8fafc';"
                          placeholder="Detalhe o que precisa ser entregue...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div style="margin-bottom: 35px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #f0fdf4; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 0.9rem;"><i class="fas fa-users-gear"></i></span>
                Atribuição & Vínculo
            </h5>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Projeto Relacionado</label>
                        <div style="position: relative;">
                            <select name="project_id" style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                                <option value="">Atividade Geral / Sem projeto</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-folder-tree" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 1.1rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Responsável pela Execução</label>
                        <div style="position: relative;">
                            <select name="assigned_to" style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                                <option value="">Mantenha comigo</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-user-tag" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 1.1rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 45px;">
            <h5 style="color: #1e293b; font-weight: 900; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="width: 32px; height: 32px; background: #fff7ed; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f59e0b; font-size: 0.9rem;"><i class="fas fa-calendar-day"></i></span>
                Prazo & Urgência
            </h5>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Data Limite</label>
                        <input type="date" name="due_date" required 
                               style="width: 100%; padding: 18px 20px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; font-size: 1rem;" 
                               value="{{ old('due_date', request('date', date('Y-m-d'))) }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Nível de Prioridade</label>
                        <select name="priority" style="width: 100%; padding: 18px 20px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 800; color: #1e293b; appearance: none; cursor: pointer;">
                            <option value="low">🟡 Prioridade Normal</option>
                            <option value="medium" selected>🟠 Importante</option>
                            <option value="high">🔴 Crítico / Urgente</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="status" value="todo">

        <div style="display: flex; gap: 20px; align-items: center; padding-top: 25px; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 20px; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 12px; background: #1e293b;">
                 Agendar Agora <i class="fas fa-clock"></i>
            </button>
            <a href="{{ $basePath . '/tasks' }}" style="flex: 1; text-align: center; color: #94a3b8; font-weight: 800; font-size: 0.9rem; text-decoration: none; text-transform: uppercase;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
