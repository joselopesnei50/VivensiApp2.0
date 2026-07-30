@extends('layouts.app')
@section('title', 'WhatsApp — Créditos pré-pagos')

@php
    $moneyBrl = fn ($v) => 'R$ ' . number_format($v / 1_000_000, 2, ',', '.');
@endphp

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · WhatsApp Cloud</p>
            <h1 class="h3 mb-1"><i class="fas fa-wallet text-success"></i> Créditos pré-pagos</h1>
            <p class="text-muted mb-0">Ativar pré-pago por tenant e lançar recargas manuais (após confirmar PIX/pagamento).</p>
        </div>
        <a href="{{ route('admin.whatsapp.billing') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-chart-line"></i> Ver consumo consolidado
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success rounded-3 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 shadow-sm">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Tenants em pré-pago</p>
                    <h3 class="mb-0 fw-bold">{{ $stats['prepaid_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Saldo total no sistema</p>
                    <h3 class="mb-0 fw-bold text-success">{{ $moneyBrl($stats['total_balance']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Saldo &lt; R$ 10</p>
                    <h3 class="mb-0 fw-bold {{ $stats['low_balance'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $stats['low_balance'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabela de tenants --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tenant</th>
                            <th>Status pré-pago</th>
                            <th class="text-end">Saldo atual</th>
                            <th class="text-end">Última recarga</th>
                            <th style="width: 320px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $t)
                            @php
                                $bal = $t->credit_balance;
                                $enabled = $bal && $bal->isPrepaidEnabled();
                                $balMicros = $bal->balance_brl_micros ?? 0;
                                $lastTopupMicros = $bal->last_topup_brl_micros ?? 0;
                                $isLow = $enabled && $balMicros < 10_000_000;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $t->name }}</div>
                                    <div class="text-muted small">#{{ $t->id }}</div>
                                </td>
                                <td>
                                    @if($enabled)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fas fa-check-circle"></i> Ativo
                                        </span>
                                        <div class="text-muted small mt-1">desde {{ $bal->prepaid_enabled_at?->format('d/m/Y') }}</div>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            Inativo (modelo B)
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $isLow ? 'text-danger' : ($enabled ? 'text-success' : 'text-muted') }}" style="font-size: 1.05rem;">
                                        {{ $moneyBrl($balMicros) }}
                                    </span>
                                </td>
                                <td class="text-end text-muted small">
                                    {{ $lastTopupMicros > 0 ? $moneyBrl($lastTopupMicros) : '—' }}
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        {{-- Toggle --}}
                                        <form method="POST" action="{{ route('admin.whatsapp.credits.toggle', $t) }}" onsubmit="return confirm('{{ $enabled ? 'Desativar pré-pago (saldo é preservado)?' : 'Ativar pré-pago para este tenant?' }}');">
                                            @csrf
                                            <button class="btn btn-sm {{ $enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                <i class="fas {{ $enabled ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                                {{ $enabled ? 'Desativar' : 'Ativar' }}
                                            </button>
                                        </form>

                                        {{-- Recarga --}}
                                        <button type="button" class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal" data-bs-target="#topupModal-{{ $t->id }}">
                                            <i class="fas fa-plus-circle"></i> Recarregar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modais de recarga (1 por tenant) --}}
    @foreach($tenants as $t)
    <div class="modal fade" id="topupModal-{{ $t->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.whatsapp.credits.topup', $t) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle text-success"></i> Recarga manual — {{ $t->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Ativa pré-pago automaticamente se ainda não estava. Use apenas <strong>depois de confirmar o pagamento fora do sistema</strong> (PIX, transferência, etc).</p>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Valor da recarga (R$)</label>
                        <input type="number" name="amount_brl" step="0.01" min="1" required class="form-control form-control-lg" placeholder="Ex: 100.00">
                        <div class="mt-2 d-flex gap-1 flex-wrap">
                            @foreach($suggestedTopups as $val)
                                <button type="button" class="btn btn-sm btn-outline-secondary suggested-topup"
                                        data-target-modal="topupModal-{{ $t->id }}" data-value="{{ $val }}">R$ {{ $val }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Descrição / referência</label>
                        <input type="text" name="description" required maxlength="400" class="form-control" placeholder="Ex: PIX 30/07/2026 R$ 100 – comprovante WA-123">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success"><i class="fas fa-check"></i> Confirmar recarga</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

<script>
document.querySelectorAll('.suggested-topup').forEach(btn => {
    btn.addEventListener('click', function () {
        const modalId = this.getAttribute('data-target-modal');
        const input = document.querySelector('#' + modalId + ' input[name="amount_brl"]');
        if (input) input.value = this.getAttribute('data-value');
    });
});
</script>
@endsection
