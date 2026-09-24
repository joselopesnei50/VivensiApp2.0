@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Vendas</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.4rem; letter-spacing: -1px;">Orçamentos</h2>
            <p style="color: #64748b; margin: 6px 0 0 0; font-size: 1rem; font-weight: 500;">Monte propostas com itens do catálogo, envie ao cliente e converta em receita quando aceitas.</p>
        </div>
        <a href="{{ route('quotes.create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
            <i class="fas fa-plus me-2" style="color: #10b981;"></i> Novo Orçamento
        </a>
    </div>
</div>

@if(session('success'))
    <div class="ds-alert ds-alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-3" style="margin-bottom: 20px;">
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#eef2ff,#ffffff); border:1px solid #c7d2fe;">
            <div style="font-size:.7rem; color:#4338ca; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Pipeline em Aberto</div>
            <div style="font-size:1.6rem; color:#3730a3; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                R$ {{ number_format($stats['pipeline_value'], 2, ',', '.') }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Enviados + Aceitos</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:white; border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#64748b; font-weight:900; text-transform:uppercase;">Rascunho</div>
            <div style="font-size:1.5rem; color:#1e293b; font-weight:900; margin-top:4px;">{{ $stats['draft'] }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:white; border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#4f46e5; font-weight:900; text-transform:uppercase;">Enviados</div>
            <div style="font-size:1.5rem; color:#3730a3; font-weight:900; margin-top:4px;">{{ $stats['sent'] }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:white; border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#059669; font-weight:900; text-transform:uppercase;">Aceitos / Convertidos</div>
            <div style="font-size:1.5rem; color:#065f46; font-weight:900; margin-top:4px;">{{ $stats['accepted'] + $stats['converted'] }}</div>
        </div>
    </div>
</div>

<div class="vivensi-card" style="padding: 24px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('quotes.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
        <div style="flex:1; min-width:220px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase;">Buscar por título ou número</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="PROP-2026-0001, Consultoria..." class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <div style="min-width:180px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase;">Status</label>
            <select name="status" class="form-select" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
                <option value="">Todos</option>
                @foreach(\App\Models\Quote::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-premium" style="background:#4f46e5; color:white; border:none; font-weight:800; padding:10px 20px; border-radius:10px;">
            <i class="fas fa-search"></i> Filtrar
        </button>
    </form>
</div>

<div class="vivensi-card p-4" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                    <th style="border: none;">Número</th>
                    <th style="border: none;">Título / Cliente</th>
                    <th style="border: none;">Status</th>
                    <th style="border: none;">Emitido</th>
                    <th style="border: none;">Validade</th>
                    <th style="border: none; text-align: right;">Total</th>
                    <th style="border: none; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusStyle = [
                        'draft'     => ['bg' => '#f1f5f9', 'fg' => '#475569'],
                        'sent'      => ['bg' => '#eef2ff', 'fg' => '#4338ca'],
                        'accepted'  => ['bg' => '#dcfce7', 'fg' => '#166534'],
                        'rejected'  => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
                        'expired'   => ['bg' => '#fef3c7', 'fg' => '#92400e'],
                        'converted' => ['bg' => '#ecfdf5', 'fg' => '#065f46'],
                    ];
                @endphp
                @forelse($quotes as $q)
                @php $ss = $statusStyle[$q->status] ?? $statusStyle['draft']; @endphp
                <tr style="background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <td style="border: none; border-radius: 12px 0 0 12px; padding: 15px 20px; font-weight: 800; color: #4338ca; font-family: monospace;">
                        {{ $q->quote_number }}
                    </td>
                    <td style="border: none;">
                        <div style="font-weight: 700; color: #1e293b;">{{ $q->title }}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">{{ $q->client?->name ?: 'Sem cliente vinculado' }}</div>
                    </td>
                    <td style="border: none;">
                        <span class="badge" style="background:{{ $ss['bg'] }}; color:{{ $ss['fg'] }}; padding: 5px 10px; border-radius: 8px; font-weight:800;">{{ $q->status_label }}</span>
                    </td>
                    <td style="border: none; font-size: 0.85rem; color: #475569;">{{ $q->issue_date->format('d/m/Y') }}</td>
                    <td style="border: none; font-size: 0.85rem; color: {{ $q->isExpired() ? '#dc2626' : '#475569' }};">
                        {{ $q->valid_until ? $q->valid_until->format('d/m/Y') : '—' }}
                        @if($q->isExpired())<div style="font-size:.65rem; color:#dc2626; font-weight:800;">EXPIRADO</div>@endif
                    </td>
                    <td style="border: none; text-align: right; font-weight: 900; color: #059669; font-size: 1rem;">
                        {{ $q->formatted_total }}
                    </td>
                    <td style="border: none; border-radius: 0 12px 12px 0; text-align: right; padding: 15px 20px;">
                        <a href="{{ route('quotes.show', $q) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #475569; font-weight: 700;" title="Detalhes"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('quotes.pdf', $q) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #dc2626; font-weight: 700;" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 0; border: none;">
                        <x-empty-state
                            icon="fa-file-invoice-dollar"
                            title="Nenhum orçamento ainda"
                            description="Monte propostas com itens do catálogo, gere PDF e converta em receita quando o cliente aceitar."
                            action_label="Criar Primeiro Orçamento"
                            action_url="{{ route('quotes.create') }}"
                        />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($quotes->hasPages())
    <div class="mt-4">{{ $quotes->links() }}</div>
    @endif
</div>
@endsection
