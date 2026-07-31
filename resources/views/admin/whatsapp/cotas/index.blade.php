@extends('layouts.app')
@section('title', 'WhatsApp — Cotas mensais')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · WhatsApp Cloud</p>
            <h1 class="h3 mb-1"><i class="fas fa-chart-pie text-primary"></i> Cotas mensais</h1>
            <p class="text-muted mb-0">Modelo C: cota inclusa no plano + packs extras avulsos. Cliente paga Meta direto.</p>
        </div>
        <a href="{{ route('admin.whatsapp.billing') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-chart-line"></i> Ver consumo consolidado
        </a>
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
                    <p class="text-muted small mb-1">Tenants com módulo ativo</p>
                    <h3 class="mb-0 fw-bold">{{ $stats['active_tenants'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Uso total no mês</p>
                    <h3 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_used_month'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Perto do limite (≥80%)</p>
                    <h3 class="mb-0 fw-bold {{ $stats['near_quota'] > 0 ? 'text-warning' : 'text-muted' }}">{{ $stats['near_quota'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <p class="text-muted small mb-1">Estouraram cota</p>
                    <h3 class="mb-0 fw-bold {{ $stats['over_quota'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $stats['over_quota'] }}</h3>
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
                            <th>Tenant / Plano</th>
                            <th style="min-width: 220px;">Uso este mês</th>
                            <th class="text-end">Pack extra</th>
                            <th class="text-end">Renova em</th>
                            <th style="width: 240px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $t)
                            @php
                                $usage    = $t->quota_usage;
                                $planQt   = $t->plan?->whatsapp_conversations_included ?? 0;
                                $used     = $usage->conversations_used_month ?? 0;
                                $extraQt  = $usage?->extra_pack_conversations ?? 0;
                                $snapshot = $usage?->plan_included_snapshot ?? $planQt;
                                $total    = $snapshot + $extraQt;
                                $pct      = $usage ? $usage->usagePct() : 0;
                                $barColor = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
                                $moduleOff = $planQt === 0 && $extraQt === 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $t->name }}</div>
                                    <div class="text-muted small">
                                        #{{ $t->id }} &middot;
                                        {{ $t->plan?->name ?? 'sem plano' }}
                                        @if($planQt > 0)
                                            (inclui {{ $planQt }}/mês)
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($moduleOff)
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Módulo inativo</span>
                                    @else
                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                            <span class="fw-bold">{{ number_format($used, 0, ',', '.') }} / {{ number_format($total, 0, ',', '.') }}</span>
                                            <span class="text-muted">{{ $pct }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $barColor }}" style="width: {{ min(100, $pct) }}%"></div>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($extraQt > 0)
                                        <span class="fw-bold text-success">+{{ $extraQt }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end text-muted small">
                                    @if($usage)
                                        {{ $usage->daysUntilReset() }} dias
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <button type="button" class="btn btn-sm btn-success"
                                                data-bs-toggle="modal" data-bs-target="#packModal-{{ $t->id }}">
                                            <i class="fas fa-plus"></i> Pack extra
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#adjustModal-{{ $t->id }}">
                                            <i class="fas fa-sliders"></i> Ajuste
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

    {{-- Modais (1 par de modais por tenant) --}}
    @foreach($tenants as $t)
        @php
            $packSize  = $t->plan?->whatsapp_extra_pack_size ?? 500;
            $packPrice = $t->plan?->whatsapp_extra_pack_price_brl ?? 49.90;
        @endphp

        {{-- Modal pack extra --}}
        <div class="modal fade" id="packModal-{{ $t->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.whatsapp.quotas.pack', $t) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus text-success"></i> Pack extra — {{ $t->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Use após confirmar pagamento fora do sistema. Pack padrão do plano: <strong>{{ $packSize }} conversas por R$ {{ number_format($packPrice, 2, ',', '.') }}</strong>.</p>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Quantidade de conversas</label>
                            <input type="number" name="conversations" min="1" required class="form-control form-control-lg" value="{{ $packSize }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Descrição / referência</label>
                            <input type="text" name="description" required maxlength="400" class="form-control" placeholder="Ex: PIX 30/07 R$ 49,90 — comprovante WA-123">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-success"><i class="fas fa-check"></i> Aplicar pack</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal ajuste manual --}}
        <div class="modal fade" id="adjustModal-{{ $t->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.whatsapp.quotas.adjustment', $t) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-sliders text-warning"></i> Ajuste — {{ $t->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Ajuste manual do saldo — positivo credita como pack extra, negativo desconta do usado (não fica abaixo de 0).</p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Delta de conversas (positivo ou negativo)</label>
                            <input type="number" name="delta" required class="form-control form-control-lg" placeholder="Ex: 100 ou -50">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Motivo</label>
                            <input type="text" name="description" required maxlength="400" class="form-control" placeholder="Ex: Cortesia por instabilidade 28/07">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-warning"><i class="fas fa-check"></i> Aplicar ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>
@endsection
