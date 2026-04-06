@extends('layouts.app')

@section('content')
<style>
    :root {
        --c-text-primary: #0f172a;
        --c-text-secondary: #64748b;
        --c-text-tertiary: #94a3b8;
        --c-bg-page: #f8fafc;
        --c-bg-card: #ffffff;
        --c-border: #e2e8f0;
        --c-primary: #3b82f6; /* Modern Blue */
        --c-danger: #ef4444;
        --c-success: #10b981;
        --c-warning: #f59e0b;
    }

    body {
        background-color: var(--c-bg-page);
        font-family: 'Outfit', sans-serif; /* Preserving user's font preference */
    }

    .page-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Header Section */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--c-text-primary);
        letter-spacing: -0.025em;
        margin-bottom: 4px;
    }
    .page-subtitle {
        color: var(--c-text-secondary);
        font-size: 0.95rem;
    }

    .btn-create {
        background-color: var(--c-text-primary);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        border: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .btn-create:hover {
        background-color: #1e293b;
        transform: translateY(-1px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        color: white;
    }

    /* Stats Grid - Minimalist */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }
    .stat-card {
        background: var(--c-bg-card);
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }
    .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--c-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--c-text-primary);
        line-height: 1;
    }

    /* Content Card */
    .content-card {
        background: var(--c-bg-card);
        border: 1px solid var(--c-border);
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    /* Toolbar */
    .toolbar {
        padding: 16px 24px;
        border-bottom: 1px solid var(--c-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fdfdfd;
    }
    .search-wrapper {
        position: relative;
        width: 300px;
    }
    .search-input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border-radius: 6px;
        border: 1px solid var(--c-border);
        background: white;
        font-size: 0.9rem;
        color: var(--c-text-primary);
        transition: border-color 0.2s;
    }
    .search-input:focus {
        outline: none;
        border-color: var(--c-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--c-text-tertiary);
        font-size: 0.9rem;
    }

    /* List Styles */
    .ticket-list {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .ticket-list th {
        text-align: left;
        padding: 12px 24px;
        background: #fafafa;
        color: var(--c-text-secondary);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--c-border);
    }
    .ticket-list td {
        padding: 16px 24px;
        border-bottom: 1px solid var(--c-border);
        vertical-align: middle;
        color: var(--c-text-secondary);
        font-size: 0.9rem;
    }
    .ticket-list tr:last-child td {
        border-bottom: none;
    }
    .ticket-list tr {
        transition: background 0.1s;
        cursor: pointer;
    }
    .ticket-list tr:hover {
        background: #f8fafc;
    }

    .ticket-subject {
        color: var(--c-text-primary);
        font-weight: 600;
        font-size: 0.95rem;
        display: block;
        margin-bottom: 2px;
    }
    .ticket-id {
        font-family: 'Courier New', monospace;
        color: var(--c-text-tertiary);
        font-size: 0.8rem;
    }

    /* Status Badges - Minimal */
    .status-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 6px;
    }
    .status-text {
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-open .status-dot { background: var(--c-danger); }
    .status-open .status-text { color: var(--c-danger); }

    .status-answered .status-dot { background: var(--c-success); }
    .status-answered .status-text { color: var(--c-success); }

    .status-closed .status-dot { background: var(--c-text-tertiary); }
    .status-closed .status-text { color: var(--c-text-secondary); }

    .category-badge {
        display: inline-block;
        padding: 4px 8px;
        background: #f1f5f9;
        color: var(--c-text-secondary);
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Modal Tweaks */
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .form-control, .form-select {
        border-radius: 8px;
        border-color: var(--c-border);
        padding: 0.75rem 1rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--c-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

</style>

<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Suporte</h1>
            <div class="page-subtitle">Central de atendimento e helpdesk</div>
        </div>
        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#newTicketModal">
            <i class="fas fa-plus"></i> Novo Chamado
        </button>
    </div>

    @if($tickets->count() > 0)
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Chamados Abertos</div>
            <div class="stat-value">{{ $tickets->where('status', 'open')->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Em Andamento</div>
            <div class="stat-value">{{ $tickets->whereIn('status', ['answered_by_admin', 'answered_by_user'])->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Resolvidos (Total)</div>
            <div class="stat-value text-muted">{{ $tickets->where('status', 'closed')->count() }}</div>
        </div>
    </div>

    <div class="content-card">
        <form action="{{ route('support.index') }}" method="GET" class="toolbar">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="search-input" placeholder="Buscar chamados...">
            </div>
            <div class="d-flex align-items-center gap-2">
                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">Todos os Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Abertos</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fechados</option>
                </select>
                @if(request()->anyFilled(['search', 'status']))
                    <a href="{{ route('support.index') }}" class="btn btn-sm btn-light border" title="Limpar"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </form>

        <table class="ticket-list">
            <thead>
                <tr>
                    <th width="45%">Assunto</th>
                    <th width="20%">Status</th>
                    <th width="15%">Categoria</th>
                    <th width="20%">Última Atualização</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                <tr onclick="window.location='{{ route('support.show', $ticket->id) }}'">
                    <td>
                        <span class="ticket-subject">{{ $ticket->subject }}</span>
                        <span class="ticket-id">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td>
                        @if($ticket->status == 'open')
                            <span class="status-open"><span class="status-dot"></span><span class="status-text">Aberto</span></span>
                        @elseif($ticket->status == 'answered_by_admin')
                            <span class="status-answered"><span class="status-dot"></span><span class="status-text">Respondido</span></span>
                        @elseif($ticket->status == 'answered_by_user')
                            <span class="status-answered"><span class="status-dot" style="background:var(--c-warning);"></span><span class="status-text" style="color:var(--c-warning);">Aguardando</span></span>
                        @else
                            <span class="status-closed"><span class="status-dot"></span><span class="status-text">Fechado</span></span>
                        @endif
                    </td>
                    <td>
                        <span class="category-badge">{{ ucfirst($ticket->category) }}</span>
                    </td>
                    <td>
                        {{ $ticket->updated_at->diffForHumans() }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <!-- Elegant Empty State -->
    <div class="text-center py-5 border rounded-3 bg-white">
        <div class="mb-4 text-muted" style="opacity: 0.2">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <h3 class="h5 fw-bold text-dark mb-2">Sem chamados recentes</h3>
        <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">Você não tem solicitações de suporte em aberto no momento. Se precisar de ajuda, estamos à disposição.</p>
        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#newTicketModal">
            Criar Primeiro Chamado
        </button>
    </div>
    @endif
</div>

<!-- Modal Create Ticket -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('support.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold">Novo Chamado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Assunto</label>
                    <input type="text" name="subject" class="form-control" required placeholder="Resumo do problema">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Categoria</label>
                        <select name="category" class="form-select">
                            <option value="tech">Problema Técnico</option>
                            <option value="billing">Financeiro</option>
                            <option value="feature">Sugestão</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Prioridade</label>
                        <select name="priority" class="form-select">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Mensagem</label>
                    <textarea name="message" class="form-control" rows="6" required placeholder="Descreva detalhadamente sua solicitação..."></textarea>
                </div>
            </div>
            <div class="modal-footer px-4 py-3 border-top bg-light">
                <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Enviar</button>
            </div>
        </form>
    </div>
</div>

@if(session('open_modal'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('newTicketModal'));
        myModal.show();
    });
</script>
@endif
@endsection
