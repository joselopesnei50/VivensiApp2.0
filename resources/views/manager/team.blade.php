@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --accent-indigo: #6366f1;
        --bg-glass: rgba(255, 255, 255, 0.9);
        --text-main: #1e293b;
    }
    body { font-family: 'Outfit', sans-serif; background: #f8fafc; }

    .team-header {
        background: white;
        padding: 30px;
        border-radius: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        margin-bottom: 30px;
        border: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .employee-card {
        background: white;
        border-radius: 24px;
        padding: 24px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .employee-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        border-color: var(--accent-indigo);
    }

    .employee-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: var(--accent-indigo);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .employee-card:hover::before { opacity: 1; }

    .avatar-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: var(--accent-indigo);
        margin-bottom: 20px;
        border: 2px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .role-badge {
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        background: #eef2ff;
        color: #4f46e5;
    }

    .btn-add-team {
        background: var(--text-main);
        color: white;
        padding: 12px 24px;
        border-radius: 16px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
    }

    .btn-add-team:hover {
        background: #000;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .stats-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 15px;
    }
</style>

<div class="container-fluid py-4 px-5">
    
    <div class="team-header shadow-sm" style="border-left: 8px solid var(--accent-indigo);">
        <div>
            <h1 class="fw-800 m-0 text-dark" style="letter-spacing: -1.5px; font-size: 2.5rem;">Gestão de Capital Humano</h1>
            <p class="text-slate-500 m-0 mt-1 fw-500">Administre sua equipe, atribua papéis e monitore a performance em tempo real.</p>
        </div>
        <button class="btn-add-team shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-user-plus me-2"></i> Adicionar Colaborador
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="row g-4">
        @foreach($employees as $e)
        <div class="col-xl-3 col-lg-4 col-md-6">
            <a href="{{ url('/manager/team/'.$e->id) }}" class="employee-card">
                <div class="avatar-wrapper">
                    {{ substr($e->name, 0, 1) }}
                </div>
                <div>
                    <span class="role-badge">{{ $e->role == 'manager' ? 'Gestor' : 'Funcionário' }}</span>
                    <h5 class="fw-bold mt-2 mb-1">{{ $e->name }}</h5>
                    <p class="text-muted small mb-0">{{ $e->email }}</p>
                </div>
                
                <div class="stats-pill">
                    <i class="fas fa-briefcase"></i>
                    <span>{{ $e->project_members_count ?? 0 }} Projetos Alocados</span>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    @if($e->status == 'active')
                        <span class="text-success small fw-bold"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Ativo</span>
                    @else
                        <span class="text-muted small fw-bold"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> Inativo</span>
                    @endif
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                </div>

            </a>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Cadastrar Equipe -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold">Novo Colaborador</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ url('/manager/team/store-quick') }}" method="POST">
                @csrf
                <!-- Using dummy project_id for generic team registration if needed, or making it optional in controller -->
                <input type="hidden" name="project_id" value="{{ \App\Models\Project::where('tenant_id', auth()->user()->tenant_id)->first()->id ?? 0 }}">
                <input type="hidden" name="access_level" value="viewer">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nome Completo</label>
                        <input type="text" name="name" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="Ex: Maria Souza">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">E-mail Profissional</label>
                        <input type="email" name="email" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="maria@empresa.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Senha Temporária</label>
                        <input type="password" name="password" class="form-control rounded-3 py-2 bg-light border-0" required placeholder="Defina uma senha">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Função / Hierarquia</label>
                        <select name="role" class="form-select rounded-3 py-2 bg-light border-0" required>
                            <option value="employee">Funcionário (Operacional)</option>
                            <option value="manager">Gestor de Equipe</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold">Finalizar Cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
