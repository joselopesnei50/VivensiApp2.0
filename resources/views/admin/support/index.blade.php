@extends('layouts.app')

@section('content')
<style>
    :root {
        --saas-dark: #0f172a;
        --saas-blue: #3b82f6;
        --saas-bg: #f3f4f6;
    }
    body { background-color: var(--saas-bg); }

    /* Header Section */
    .saas-header-bg {
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        padding: 40px 40px 100px 40px; /* Extra padding bottom for overlap */
        border-radius: 20px;
        color: white;
        position: relative;
        margin-bottom: -60px; /* Negative margin to pull content up */
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .header-controls {
        display: flex;
        gap: 15px;
        position: absolute;
        top: 30px;
        right: 30px;
        opacity: 0.7;
    }
    .header-controls i { cursor: pointer; transition: 0.2s; }
    .header-controls i:hover { opacity: 1; color: white; }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        position: relative;
        z-index: 2;
        margin-bottom: 30px;
        padding: 0 20px;
    }
    .stat-card-modern {
        background: white;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.5);
    }
    .icon-box {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #f0fdf4; color: #22c55e; }
    .icon-purple { background: #faf5ff; color: #a855f7; }

    /* Main Layout */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 25px;
        padding: 0 20px;
    }

    /* Filters Bar */
    .filters-bar {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
    }
    .search-input-modern {
        flex: 1;
        background: white;
        border: 1px solid #e5e7eb;
        padding: 10px 15px 10px 40px;
        border-radius: 10px;
        position: relative;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .search-icon {
        position: absolute;
        left: 35px; /* adjusting for container padding if needed */
        top: 50%; /* adjusting */
        transform: translateY(-50%);
        color: #9ca3af;
        z-index: 5;
    }
    .filter-select {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 15px;
        background: white;
        color: #4b5563;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .btn-gradient {
        background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 20px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }

    /* Ticket List */
    .ticket-card-item {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        border: 1px solid #f3f4f6;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }
    .ticket-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        border-color: #bfdbfe;
    }
    
    .status-badge-modern {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 8px;
    }
    .badge-yellow { background: #fef9c3; color: #b45309; }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-red { background: #fee2e2; color: #b91c1c; }

    /* Illustration Area */
    .illustration-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 1px solid #f3f4f6;
    }
    .illustration-img {
        max-width: 80%;
        margin-bottom: 20px;
    }
    
    .floating-tools {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        display: flex;
        gap: 15px;
        align-items: center;
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="saas-header-bg text-center" style="background: linear-gradient(180deg, #020617 0%, #1e293b 100%) !important;">
        <h2 class="fw-bold mb-1 text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Central de Ajuda (SaaS)</h2>
        <p class="text-white-50 mb-0">Gestão global de atendimento ao cliente</p>
        <div class="header-controls">
            <i class="far fa-user text-white"></i>
            <i class="far fa-bell text-white"></i>
            <i class="fas fa-cog text-white"></i>
        </div>
    </div>

    <!-- Stats Overlap -->
    <div class="stats-row">
        <div class="stat-card-modern">
            <div class="icon-box icon-blue"><i class="fas fa-ticket-alt"></i></div>
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $totalOpen }}</h3>
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Tickets Abertos</small>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="icon-box icon-green"><i class="fas fa-check-circle"></i></div>
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $totalClosed }}</h3>
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Resolvidos</small>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="icon-box icon-purple"><i class="fas fa-clock"></i></div>
            <div>
                <h3 class="fw-bold mb-0 text-dark">{{ $avgResponseTime }}</h3>
                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Tempo Médio</small>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-grid">
        <!-- Left: List -->
        <div>
            <!-- Filters -->
            <form action="{{ route('admin.support.index') }}" method="GET" class="filters-bar">
                <div style="position: relative; flex: 1;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="search-input-modern w-100" placeholder="Buscar tickets, artigos...">
                </div>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Aberto</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fechado</option>
                    <option value="answered_by_admin" {{ request('status') == 'answered_by_admin' ? 'selected' : '' }}>Respondido</option>
                </select>
                <select name="priority" class="filter-select" onchange="this.form.submit()">
                    <option value="">Prioridade</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Média</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baixa</option>
                </select>
                @if(request()->anyFilled(['search', 'status', 'priority']))
                    <a href="{{ route('admin.support.index') }}" class="btn btn-light btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Limpar Filtros"><i class="fas fa-times"></i></a>
                @endif
            </form>

            <!-- List Items -->
            <div class="ticket-cards-list">
                @foreach($tickets as $ticket)
                <div class="ticket-card-item" onclick="window.location='{{ route('support.show', $ticket->id) }}'">
                    @if($ticket->status == 'open')
                        <span class="status-badge-modern badge-yellow">Em Aberto</span>
                    @elseif($ticket->status == 'closed')
                        <span class="status-badge-modern badge-green">Resolvido</span>
                    @else
                        <span class="status-badge-modern badge-red">Atenção</span>
                    @endif
                    
                    <h6 class="fw-bold mb-2">{{ $ticket->subject }}</h6>
                    
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted"><i class="far fa-clock"></i> {{ $ticket->created_at->format('d M, H:i') }}</small>
                        
                        <div class="d-flex align-items-center gap-2">
                             <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->user->name) }}&background=random" class="rounded-circle" width="24" height="24">
                             <small class="fw-bold" style="font-size: 0.8rem;">{{ explode(' ', $ticket->user->name)[0] }}</small>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Pagination Placeholder -->
                <div class="text-center mt-3">
                    <button class="btn btn-sm btn-light w-100 py-2 fw-bold text-muted">Load More</button>
                </div>
            </div>
        </div>

        <!-- Right: Illustration / Detail Placeholder -->
        <div>
            <div class="illustration-card">
                 <!-- SVG Illustration matching the vibe -->
                 <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="illustration-img">
                    <circle cx="100" cy="100" r="90" fill="#f8fafc" />
                    <rect x="40" y="60" width="120" height="80" rx="10" fill="#e2e8f0" />
                    <rect x="50" y="70" width="100" height="60" rx="5" fill="white" />
                    <circle cx="80" cy="100" r="15" fill="#bfdbfe" />
                    <rect x="105" y="90" width="40" height="6" rx="3" fill="#cbd5e1" />
                    <rect x="105" y="105" width="30" height="6" rx="3" fill="#cbd5e1" />
                    <path d="M140,150 Q160,170 180,150" fill="none" stroke="#94a3b8" stroke-width="2" />
                </svg>
                
                <h4 class="fw-bold mb-2">Estamos aqui para ajudar</h4>
                <p class="text-muted small">Selecione um ticket ao lado para ver os detalhes ou inicie um novo atendimento.</p>
                
                <div class="mt-4 d-flex gap-2 justify-content-center">
                    <button class="btn btn-light rounded-circle p-2"><i class="far fa-comment-alt"></i></button>
                    <button class="btn btn-light rounded-circle p-2"><i class="fas fa-phone-alt"></i></button>
                    <button class="btn btn-light rounded-circle p-2"><i class="far fa-question-circle"></i></button>
                    <button class="btn btn-primary rounded-circle p-2"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
