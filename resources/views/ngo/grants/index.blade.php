@extends('layouts.app')

@section('content')
<style>
    .grant-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    .grant-stat-card {
        background: white;
        padding: 24px;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }
    .grant-stat-card .label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
        display: block;
    }
    .grant-stat-card .value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #1e293b;
    }
    
    .grant-table-container {
        background: white;
        border-radius: 24px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .grant-table {
        width: 100%;
        border-collapse: collapse;
    }
    .grant-table th {
        background: #f8fafc;
        padding: 16px 24px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        border-bottom: 1px solid #f1f5f9;
    }
    .grant-table td {
        padding: 20px 24px;
        border-bottom: 1px solid #f8fafc;
    }
    
    .badge-grant-active { background: #dcfce7; color: #166534; }
    .badge-grant-reporting { background: #fef9c3; color: #854d0e; }
    .badge-grant-expired { background: #fee2e2; color: #991b1b; }
    
    .timeline-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #f1f5f9;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div>
        <h2 class="fw-bold text-dark m-0">Editais e Convênios</h2>
        <p class="text-muted mt-1">Gestão de recursos públicos, emendas e editais privados.</p>
    </div>
    <div class="d-flex gap-3">
        <a href="{{ url('/ngo/grants/create-ai') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background: #4f46e5; border: none;">
            <i class="fas fa-magic me-2"></i> Importar com IA
        </a>
        <a href="{{ url('/ngo/grants/create') }}" class="btn-premium">
            <i class="fas fa-plus me-2"></i> Novo Convênio
        </a>
    </div>
</div>

<div class="grant-stats-grid">
    <div class="grant-stat-card">
        <span class="label">Total sob Gestão</span>
        <div class="value text-primary">R$ {{ number_format($stats['total_funding'], 2, ',', '.') }}</div>
    </div>
    <div class="grant-stat-card">
        <span class="label">Projetos Ativos</span>
        <div class="value">{{ $stats['active_count'] }} <small class="text-muted fw-normal" style="font-size: 0.8rem;">em execução</small></div>
    </div>
    <div class="grant-stat-card">
        <span class="label">Prestações de Contas</span>
        <div class="value text-warning">{{ $stats['reporting_count'] }} <small class="text-muted fw-normal" style="font-size: 0.8rem;">pendentes</small></div>
    </div>
    <div class="grant-stat-card">
        <span class="label">Deadlines Próximos</span>
        <div class="value text-danger">{{ $stats['expiring_soon'] }} <small class="text-muted fw-normal" style="font-size: 0.8rem;">em 30 dias</small></div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="vivensi-card" style="padding: 18px 20px; margin-bottom: 16px;">
    <form method="GET" action="{{ url('/ngo/grants') }}" style="display:flex; gap: 12px; flex-wrap: wrap; align-items: end;">
        <div style="flex: 1; min-width: 220px;">
            <label class="form-label small text-muted" style="font-weight:800; margin-bottom: 6px;">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Título, concedente, nº processo..." style="border-radius: 14px;">
        </div>
        <div style="min-width: 200px;">
            <label class="form-label small text-muted" style="font-weight:800; margin-bottom: 6px;">Status</label>
            <select name="status" class="form-select" style="border-radius: 14px;">
                <option value="">Todos</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Ativo</option>
                <option value="reporting" {{ request('status') === 'reporting' ? 'selected' : '' }}>Prestação de Contas</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Encerrado</option>
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="border-radius: 14px; background:#4f46e5; border:none; font-weight:800;">
                Filtrar
            </button>
            <a class="btn btn-danger" style="border-radius: 14px; font-weight:800;"
               href="{{ url('/ngo/grants') . '?' . http_build_query(array_filter(array_merge(request()->except('page'), ['attention' => 'expired_pending']), fn($v) => $v !== null && $v !== '')) }}">
                <i class="fas fa-triangle-exclamation me-1"></i> Vencidos ({{ (int) ($stats['expired_pending'] ?? 0) }})
            </a>
            <a href="{{ url('/ngo/grants') }}" class="btn btn-outline-secondary" style="border-radius: 14px; font-weight:800;">
                Limpar
            </a>
        </div>
        <details style="margin-top: 12px; width: 100%;">
            <summary style="cursor:pointer; font-weight:800; color:#475569;">
                Filtros avançados
            </summary>
            <div style="margin-top: 12px; display:flex; gap: 12px; flex-wrap: wrap; align-items:end;">
                <div style="min-width: 220px;">
                    <label class="form-label small text-muted" style="font-weight:800; margin-bottom: 6px;">Prazo</label>
                    <select name="deadline" class="form-select" style="border-radius: 14px;">
                        <option value="">Todos</option>
                        <option value="soon" {{ request('deadline') === 'soon' ? 'selected' : '' }}>Próximos 30 dias</option>
                        <option value="expired" {{ request('deadline') === 'expired' ? 'selected' : '' }}>Vencidos</option>
                        <option value="none" {{ request('deadline') === 'none' ? 'selected' : '' }}>Sem deadline</option>
                    </select>
                </div>
                <div style="min-width: 180px;">
                    <label class="form-label small text-muted" style="font-weight:800; margin-bottom: 6px;">Documentos</label>
                    <select name="has_docs" class="form-select" style="border-radius: 14px;">
                        <option value="">Todos</option>
                        <option value="1" {{ request('has_docs') === '1' ? 'selected' : '' }}>Com documentos</option>
                    </select>
                </div>
                <div style="display:flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 14px; background:#4f46e5; border:none; font-weight:800;">
                        Aplicar avançados
                    </button>
                </div>
            </div>
        </details>
    </form>
</div>

<div class="grant-table-container">
    <table class="grant-table">
        <thead>
            <tr>
                <th>Título / Objeto do Convênio</th>
                <th>Concedente</th>
                <th class="text-end">Recurso Global</th>
                <th class="text-center">Vigência</th>
                <th class="text-center">Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grants as $grant)
            <tr>
                <td>
                    <div class="fw-bold text-dark" style="font-size: 1rem;">{{ $grant->title }}</div>
                    <div class="text-muted small">ID: #{{ $grant->id }}</div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-light rounded p-2 text-center" style="width: 32px; height: 32px;"><i class="fas fa-landmark text-muted small"></i></div>
                        <span class="text-dark small fw-bold">{{ $grant->agency }}</span>
                    </div>
                </td>
                <td class="text-end">
                    <div class="fw-bold text-dark" style="font-size: 1.1rem;">R$ {{ number_format($grant->value, 2, ',', '.') }}</div>
                    <div class="text-success small fw-bold" style="font-size: 0.6rem;">100% LIBERADO</div>
                </td>
                <td class="text-center">
                    <div class="fw-bold text-dark small">{{ $grant->deadline ? $grant->deadline->format('d/m/Y') : '-' }}</div>
                    @php $days = $grant->deadline ? now()->diffInDays($grant->deadline, false) : 0; @endphp
                    <div class="timeline-pill {{ $days < 0 ? 'text-danger bg-danger-subtle' : ($days < 30 ? 'text-danger bg-danger-subtle' : '') }}">
                        <i class="far fa-clock"></i>
                        @if($grant->deadline && $days < 0)
                            Vencido há {{ abs((int) $days) }} dias
                        @else
                            {{ (int) $days }} dias
                        @endif
                    </div>
                </td>
                <td class="text-center">
                    @php
                        $statuses = [
                            'open' => ['label' => 'Ativo', 'class' => 'badge-grant-active'],
                            'reporting' => ['label' => 'Prest. Contas', 'class' => 'badge-grant-reporting'],
                            'closed' => ['label' => 'Encerrado', 'class' => 'text-muted bg-light'],
                        ];
                        $curr = $statuses[$grant->status] ?? $statuses['open'];
                        $isExpired = $grant->deadline && $grant->deadline->isPast() && ($grant->status ?? 'open') !== 'closed';
                    @endphp
                    @if($isExpired)
                        <span class="badge badge-grant-expired px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                            Vencido
                        </span>
                    @else
                        <span class="badge {{ $curr['class'] }} px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                            {{ $curr['label'] }}
                        </span>
                    @endif
                    <div class="text-muted small" style="margin-top: 6px;">
                        <i class="fas fa-paperclip me-1"></i> {{ (int) ($grant->documents_count ?? 0) }} docs
                    </div>
                </td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-3">
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('ngo.grants.show', $grant->id) }}">
                                    <i class="fas fa-eye me-2 text-muted"></i> Ver Detalhes
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('ngo.grants.status', $grant->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="reporting">
                                    <button type="submit" class="dropdown-item py-2">
                                        <i class="fas fa-file-invoice me-2 text-muted"></i> Prestar Contas
                                    </button>
                                </form>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="{{ route('ngo.grants.show', $grant->id) }}#documents">
                                    <i class="fas fa-paperclip me-2 text-muted"></i> Anexar Documentos
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('ngo.grants.destroy', $grant->id) }}" method="POST" onsubmit="return confirm('Excluir este convênio/edital? Esta ação não pode ser desfeita.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item py-2 text-danger" style="background: none; border: none; width: 100%; text-align: left;">
                                        <i class="fas fa-trash-alt me-2"></i> Excluir
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="opacity-30 mb-3"><i class="fas fa-file-signature fa-3x"></i></div>
                    <p class="text-muted fw-bold">Nenhum convênio ou edital registrado.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 14px;">
    {{ $grants->links() }}
</div>
@endsection
