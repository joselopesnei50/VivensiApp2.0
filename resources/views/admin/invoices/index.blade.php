@extends('layouts.app')
@section('title', 'Faturas — Super Admin')

@section('content')
<div class="container py-4" style="max-width: 1300px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · Billing</p>
            <h1 class="h3 mb-1"><i class="fas fa-file-invoice-dollar text-primary"></i> Faturas</h1>
            <p class="text-muted mb-0">Gestão consolidada de todas as cobranças de assinatura Vivensi.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 shadow-sm">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Em aberto</p>
                    <h3 class="mb-0 fw-bold text-primary">{{ $stats['total_open'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Vencidas</p>
                    <h3 class="mb-0 fw-bold {{ $stats['total_overdue'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $stats['total_overdue'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">A receber (open+overdue)</p>
                    <h3 class="mb-0 fw-bold">R$ {{ number_format($stats['sum_open_brl'], 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Recebido no mês</p>
                    <h3 class="mb-0 fw-bold text-success">R$ {{ number_format($stats['sum_paid_month_brl'], 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['open' => 'Em aberto', 'overdue' => 'Vencidas', 'paid' => 'Pagas', 'canceled' => 'Canceladas'] as $key => $label)
                            <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tenant</label>
                    <select name="tenant_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach($tenants as $id => $name)
                            <option value="{{ $id }}" {{ (int) $tenantId === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">De</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Até</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tenant / Plano</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Pago em</th>
                            <th style="width: 220px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="text-muted small">#{{ $inv->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $inv->tenant?->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $inv->plan?->name ?? 'Sem plano' }}</div>
                                </td>
                                <td class="small">{{ $inv->description }}</td>
                                <td class="text-end fw-bold">{{ $inv->formatted_amount }}</td>
                                <td class="small">{{ $inv->due_date->format('d/m/Y') }}</td>
                                <td>
                                    @php $badge = ['open' => 'bg-primary-subtle text-primary', 'overdue' => 'bg-danger-subtle text-danger', 'paid' => 'bg-success-subtle text-success', 'canceled' => 'bg-secondary-subtle text-secondary'][$inv->status] ?? 'bg-light'; @endphp
                                    <span class="badge {{ $badge }} border">{{ $inv->statusLabel() }}</span>
                                </td>
                                <td class="small text-muted">
                                    @if($inv->paid_at)
                                        {{ $inv->paid_at->format('d/m/Y') }}<br>
                                        <span class="small">{{ $inv->paidViaLabel() }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($inv->isPayable())
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal-{{ $inv->id }}">
                                                <i class="fas fa-check"></i> Marcar paga
                                            </button>
                                            <form method="POST" action="{{ route('admin.invoices.cancel', $inv) }}" onsubmit="return confirm('Cancelar fatura #{{ $inv->id }}?');" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary" title="Cancelar"><i class="fas fa-ban"></i></button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted p-4">Nenhuma fatura encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $invoices->links() }}</div>

    {{-- Modais de marcar paga (1 por invoice payable) --}}
    @foreach($invoices as $inv)
        @if($inv->isPayable())
            <div class="modal fade" id="payModal-{{ $inv->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.invoices.mark-paid', $inv) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Marcar como paga — Fatura #{{ $inv->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">Tenant: <strong>{{ $inv->tenant?->name }}</strong> · Valor: <strong>{{ $inv->formatted_amount }}</strong></p>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Como foi pago?</label>
                                <select name="paid_via" class="form-select" required>
                                    <option value="pix_manual">PIX manual (recebi comprovante)</option>
                                    <option value="manual_admin">Ajuste manual (outro canal)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-success"><i class="fas fa-check"></i> Confirmar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

</div>
@endsection
