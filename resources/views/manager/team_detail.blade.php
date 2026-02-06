@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp

<style>
    :root {
        --accent-indigo: #6366f1;
        --text-dark: #0f172a;
    }
    body { font-family: 'Outfit', sans-serif; background: #f1f5f9; }

    .profile-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 30px;
        padding: 60px 40px;
        color: white;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    .profile-header h1, .profile-header h2, .profile-header h3 {
        color: white !important;
    }

    .profile-hero-content {
        display: flex;
        align-items: center;
        gap: 40px;
        position: relative;
        z-index: 2;
    }

    .persona-icon {
        width: 150px;
        height: 150px;
        border-radius: 40px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: 800;
        border: 2px solid rgba(255,255,255,0.2);
    }

    .card-glass {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 30px;
        border: 1px solid white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        height: 100%;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-dark);
    }

    .reminder-item {
        background: #f8fafc;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 12px;
        border-left: 4px solid var(--accent-indigo);
    }

    .project-pill {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .project-pill:hover { border-color: var(--accent-indigo); transform: translateX(5px); }

    .btn-chief {
        background: var(--accent-indigo);
        color: white;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 700;
        border: none;
    }
</style>

<div class="container-fluid py-4 px-5">
    <a href="{{ $basePath . '/manager/team' }}" class="btn btn-link text-muted mb-3 text-decoration-none">
        <i class="fas fa-arrow-left me-2"></i> Voltar para Equipe
    </a>

    <div class="profile-header text-white">
        <div class="profile-hero-content">
            <div class="persona-icon">
                {{ substr($employee->name, 0, 1) }}
            </div>
            <div>
                <span class="badge mb-3 px-3 py-2 rounded-pill fw-bold text-white border border-white border-opacity-25" style="background: rgba(255,255,255,0.1) !important; letter-spacing: 1px; font-size: 0.7rem;">
                    <i class="fas fa-id-badge me-1"></i> {{ strtoupper($employee->role) }}
                </span>
                <h1 class="display-3 fw-900 m-0" style="letter-spacing: -2px; text-shadow: 0 4px 12px rgba(0,0,0,0.3);">{{ $employee->name }}</h1>
                <p class="fs-5 mt-2 fw-500 text-white-50"><i class="far fa-envelope me-2 text-white"></i> {{ $employee->email }}</p>
                
                <div class="d-flex gap-4 mt-4">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 border border-white border-opacity-10">
                        <div class="fw-bold fs-3 text-white">{{ $projects->count() }}</div>
                        <div class="small text-white-50 text-uppercase fw-600" style="font-size: 0.65rem; letter-spacing: 1px;">Projetos Ativos</div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 border border-white border-opacity-10">
                        <div class="fw-bold fs-3 text-white">{{ $reminders->count() }}</div>
                        <div class="small text-white-50 text-uppercase fw-600" style="font-size: 0.65rem; letter-spacing: 1px;">Tarefas Pendentes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Projects Column -->
        <div class="col-lg-5">
            <div class="card-glass">
                <div class="section-title"><i class="fas fa-layer-group"></i> Projetos em que atua</div>
                @forelse($projects as $p)
                <a href="{{ $basePath . '/projects/'.$p->project->id }}" class="project-pill text-decoration-none text-dark">
                    <div class="bg-light rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-folder text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">{{ $p->project->name }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Nível: {{ strtoupper($p->access_level) }}</div>
                    </div>
                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                </a>
                @empty
                <p class="text-muted">Este colaborador ainda não foi alocado em nenhum projeto.</p>
                @endforelse
            </div>
        </div>

        <!-- Reminders Column -->
        <div class="col-lg-7">
            <div class="card-glass">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="section-title m-0"><i class="fas fa-bell"></i> Lembretes & Tarefas Diárias</div>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addReminderModal">
                        <i class="fas fa-plus me-1"></i> Novo Lembrete
                    </button>
                </div>

                @forelse($reminders as $r)
                <div class="reminder-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold text-dark">{{ $r->title }}</div>
                            <p class="text-muted small m-0 mt-1">{{ $r->description }}</p>
                        </div>
                        <span class="badge {{ $r->priority == 'high' ? 'bg-danger' : 'bg-primary' }} rounded-pill" style="font-size: 0.6rem;">
                            {{ strtoupper($r->priority) }}
                        </span>
                    </div>
                    @if($r->project)
                        <div class="mt-1">
                            <span class="badge bg-white text-dark border small fw-600" style="font-size: 0.7rem; color: #475569 !important;">
                                <i class="fas fa-project-diagram me-1 text-indigo-500"></i> {{ $r->project->name }}
                            </span>
                        </div>
                    @endif
                    <div class="mt-3 d-flex align-items-center justify-content-between">
                        <div class="text-muted" style="font-size: 0.7rem;"><i class="far fa-clock me-1"></i> Enviado em {{ $r->created_at->format('d/m H:i') }}</div>
                        <div class="status-indicator d-flex align-items-center gap-2">
                             <div class="small fw-bold {{ $r->status == 'completed' ? 'text-success' : 'text-warning' }}">
                                 {{ $r->status == 'completed' ? 'Concluído' : 'Em Aberto' }}
                             </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-calendar-check fa-3x opacity-10 mb-3"></i>
                    <p class="text-muted">Nenhum lembrete enviado recentemente.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Lembrete -->
<div class="modal fade" id="addReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0 py-4 px-4" style="background: var(--accent-indigo) !important;">
                <h5 class="modal-title fw-bold">Enviar Lembrete ao Colaborador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $basePath . '/tasks' }}" method="POST">
                @csrf
                <input type="hidden" name="assigned_to" value="{{ $employee->id }}">
                <input type="hidden" name="priority" value="medium">
                <input type="hidden" name="status" value="todo">
                <input type="hidden" name="redirect_to_schedule" value="1">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Assunto / Título</label>
                        <input type="text" name="title" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="Ex: Revisar planilha de custos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Instrução Detalhada</label>
                        <textarea name="description" class="form-control rounded-3 bg-light border-0" rows="4" placeholder="Descreva o que o colaborador precisa fazer hoje..."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Projeto Relacionado (Opcional)</label>
                        <select name="project_id" class="form-select rounded-3 py-2 bg-light border-0">
                            <option value="">Nenhum específico</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->project->id }}">{{ $p->project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold">Enviar Mensagem Direta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
