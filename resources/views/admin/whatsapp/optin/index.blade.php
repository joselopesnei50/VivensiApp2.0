@extends('layouts.app')

@section('title', 'WhatsApp — Contatos Opt-in')

@push('styles')
<style>
    .optin-stat-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .optin-stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .optin-stat-icon.green  { background: #dcfce7; color: #16a34a; }
    .optin-stat-icon.blue   { background: #dbeafe; color: #2563eb; }
    .optin-stat-icon.red    { background: #fee2e2; color: #dc2626; }
    .optin-stat-number { font-size: 1.8rem; font-weight: 800; line-height: 1; color: #1e293b; }
    .optin-stat-label  { font-size: 0.78rem; color: #94a3b8; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.04em; }
    .filter-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 18px 24px;
        margin-bottom: 20px;
    }
    .badge-optin  { background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-optout { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-origem { background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Contatos WhatsApp — Opt-in</h4>
            <p class="text-muted small mb-0">Gerencie consentimentos LGPD dos contatos</p>
        </div>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
            + Registrar opt-in manual
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="optin-stat-card">
                <div class="optin-stat-icon blue">📋</div>
                <div><div class="optin-stat-number">{{ number_format($stats['total']) }}</div><div class="optin-stat-label">Total de contatos</div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="optin-stat-card">
                <div class="optin-stat-icon green">✅</div>
                <div><div class="optin-stat-number">{{ number_format($stats['ativos']) }}</div><div class="optin-stat-label">Com opt-in ativo</div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="optin-stat-card">
                <div class="optin-stat-icon red">🚫</div>
                <div><div class="optin-stat-number">{{ number_format($stats['opt_out']) }}</div><div class="optin-stat-label">Opt-out / descadastrados</div></div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="filter-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Buscar</label>
                <input type="text" name="busca" class="form-control form-control-sm" value="{{ request('busca') }}" placeholder="Nome ou telefone…">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status opt-in</label>
                <select name="opt_in" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" @selected(request('opt_in') === '1')>Com opt-in</option>
                    <option value="0" @selected(request('opt_in') === '0')>Sem opt-in</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Origem</label>
                <select name="origem" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="bot" @selected(request('origem') === 'bot')>Bot</option>
                    <option value="formulario" @selected(request('origem') === 'formulario')>Formulário</option>
                    <option value="evento" @selected(request('origem') === 'evento')>Evento</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
            </div>
        </form>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nome</th>
                            <th>Telefone</th>
                            <th>Opt-in</th>
                            <th>Origem</th>
                            <th>Data opt-in</th>
                            <th>Opt-out</th>
                            <th class="pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contatos as $contato)
                        <tr>
                            <td class="ps-4 fw-medium">{{ $contato->nome ?? '—' }}</td>
                            <td class="font-monospace small">{{ $contato->telefone }}</td>
                            <td>
                                @if($contato->opt_in)
                                    <span class="badge-optin">Ativo</span>
                                @else
                                    <span class="text-muted small">Pendente</span>
                                @endif
                            </td>
                            <td>
                                @if($contato->opt_in_origem)
                                    <span class="badge-origem">{{ $contato->opt_in_origem }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $contato->opt_in_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td>
                                @if($contato->opt_out)
                                    <span class="badge-optout">Saiu {{ $contato->opt_out_at?->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                @if($contato->opt_in && !$contato->opt_out)
                                <form method="POST" action="{{ route('whatsapp.optin.remover', $contato) }}" onsubmit="return confirm('Remover opt-in deste contato?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Remover opt-in</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Nenhum contato encontrado.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($contatos->hasPages())
        <div class="card-footer bg-transparent">
            {{ $contatos->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Modal registrar opt-in manual --}}
<div class="modal fade" id="modalRegistrar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar opt-in manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('whatsapp.optin.registrar') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Telefone <span class="text-danger">*</span></label>
                        <input type="text" name="telefone" class="form-control" placeholder="5511999999999" required>
                        <div class="form-text">Formato com DDI: 55 + DDD + número</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome</label>
                        <input type="text" name="nome" class="form-control" placeholder="Nome do contato (opcional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar opt-in</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
