@extends('layouts.app')

@section('content')
@php
    $statusStyle = [
        'draft'     => ['bg' => '#f1f5f9', 'fg' => '#475569', 'icon' => 'fa-file'],
        'sent'      => ['bg' => '#eef2ff', 'fg' => '#4338ca', 'icon' => 'fa-paper-plane'],
        'accepted'  => ['bg' => '#dcfce7', 'fg' => '#166534', 'icon' => 'fa-circle-check'],
        'rejected'  => ['bg' => '#fee2e2', 'fg' => '#991b1b', 'icon' => 'fa-circle-xmark'],
        'expired'   => ['bg' => '#fef3c7', 'fg' => '#92400e', 'icon' => 'fa-clock'],
        'converted' => ['bg' => '#ecfdf5', 'fg' => '#065f46', 'icon' => 'fa-arrow-right-arrow-left'],
    ];
    $ss = $statusStyle[$quote->status] ?? $statusStyle['draft'];
@endphp

<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('quotes.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar aos orçamentos</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Vendas · Orçamento</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="font-family: monospace; background:#e0e7ff; color:#4338ca; padding:8px 14px; border-radius:10px; font-weight:900; font-size:.95rem;">
                    {{ $quote->quote_number }}
                </div>
                <span style="background:{{ $ss['bg'] }}; color:{{ $ss['fg'] }}; padding:5px 12px; border-radius:99px; font-weight:800; font-size:.75rem; text-transform:uppercase;">
                    <i class="fas {{ $ss['icon'] }}"></i> {{ $quote->status_label }}
                </span>
            </div>
            <h2 style="margin: 8px 0 0 0; color: #1e293b; font-weight: 900; font-size: 1.9rem; letter-spacing: -0.5px;">{{ $quote->title }}</h2>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                @if($quote->client)
                    <i class="fas fa-user"></i> <a href="{{ route('clients.show', $quote->client_id) }}" style="color:#4338ca; text-decoration:none;">{{ $quote->client->name }}</a> &nbsp;·&nbsp;
                @endif
                Emitido {{ $quote->issue_date->format('d/m/Y') }}
                @if($quote->valid_until) &nbsp;·&nbsp; Válido até {{ $quote->valid_until->format('d/m/Y') }} @endif
            </div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap:wrap;">
            <a href="{{ route('quotes.pdf', $quote) }}" class="btn-premium" style="background:#fee2e2; color:#991b1b; border:none;">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @if($quote->status !== 'converted')
                <a href="{{ route('quotes.edit', $quote) }}" class="btn-premium" style="background:#fef3c7; color:#92400e; border:none;">
                    <i class="fas fa-pen"></i> Editar
                </a>
            @endif
            <form action="{{ route('quotes.duplicate', $quote) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn-premium" style="background:#e0e7ff; color:#4338ca; border:none;">
                    <i class="fas fa-copy"></i> Duplicar
                </button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="ds-alert ds-alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

{{-- Botões de ação por status --}}
<div class="vivensi-card" style="padding: 18px 24px; margin-bottom: 20px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
    <span style="font-size:.8rem; color:#475569; font-weight:800; text-transform:uppercase; letter-spacing:.5px;">Próxima ação:</span>
    @if($quote->status === 'draft')
        <form action="{{ route('quotes.send', $quote) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-premium" style="background:#4f46e5; color:white; border:none;">
                <i class="fas fa-paper-plane"></i> Marcar como Enviado
            </button>
        </form>
    @endif
    @if(in_array($quote->status, ['draft', 'sent']))
        <form action="{{ route('quotes.accept', $quote) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-premium" style="background:#10b981; color:white; border:none;">
                <i class="fas fa-thumbs-up"></i> Cliente Aceitou
            </button>
        </form>
        <form action="{{ route('quotes.reject', $quote) }}" method="POST" style="display:inline;" onsubmit="return confirm('Marcar como rejeitado?');">
            @csrf
            <button type="submit" class="btn-premium" style="background:#fee2e2; color:#991b1b; border:none;">
                <i class="fas fa-thumbs-down"></i> Rejeitado
            </button>
        </form>
    @endif
    @if($quote->status === 'accepted')
        <form action="{{ route('quotes.convert', $quote) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn-premium" style="background:#059669; color:white; border:none;">
                <i class="fas fa-arrow-right-arrow-left"></i> Converter em Receita Pendente
            </button>
        </form>
    @endif
    @if($quote->status === 'converted' && $quote->convertedTransaction)
        <span style="font-size:.85rem; color:#065f46; font-weight:700;">
            Virou <a href="{{ url('/transactions/' . $quote->converted_transaction_id) }}" style="color:#065f46; font-weight:900;">receita #{{ $quote->converted_transaction_id }}</a>
            ({{ $quote->convertedTransaction->status === 'paid' ? 'PAGA' : 'PENDENTE' }} vencendo {{ \Carbon\Carbon::parse($quote->convertedTransaction->date)->format('d/m/Y') }})
        </span>
    @endif
    @if(in_array($quote->status, ['rejected', 'expired']))
        <span style="font-size:.85rem; color:#64748b; font-weight:600;">
            Sem próxima ação. Duplique se quiser reenviar.
        </span>
    @endif
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
            <h5 style="margin: 0 0 18px 0; font-weight: 800; color: #1e293b; font-size: 1rem;">Itens</h5>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="color:#94a3b8; font-size:.7rem; text-transform:uppercase; letter-spacing:1px; border-bottom:2px solid #f1f5f9;">
                        <th style="text-align:left; padding:10px 0;">Item</th>
                        <th style="text-align:right; padding:10px 0; width:80px;">Qtd</th>
                        <th style="text-align:right; padding:10px 0; width:140px;">Valor un.</th>
                        <th style="text-align:right; padding:10px 0; width:140px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $it)
                    <tr style="border-bottom:1px solid #f8fafc;">
                        <td style="padding:14px 0;">
                            <div style="font-weight:700; color:#1e293b;">{{ $it->name }}</div>
                            @if($it->description)
                                <div style="font-size:.78rem; color:#64748b; margin-top:2px;">{{ $it->description }}</div>
                            @endif
                        </td>
                        <td style="text-align:right; color:#475569; font-weight:600;">{{ rtrim(rtrim(number_format((float)$it->quantity, 3, ',', ''), '0'), ',') }} {{ $it->unit }}</td>
                        <td style="text-align:right; color:#475569;">R$ {{ number_format((float)$it->unit_price, 2, ',', '.') }}</td>
                        <td style="text-align:right; color:#1e293b; font-weight:800;">R$ {{ number_format((float)$it->subtotal, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right; padding:14px 0; color:#64748b; font-weight:600;">Subtotal</td>
                        <td style="text-align:right; padding:14px 0; color:#475569; font-weight:700;">R$ {{ number_format($quote->subtotal_items, 2, ',', '.') }}</td>
                    </tr>
                    @if((float)$quote->discount > 0)
                    <tr>
                        <td colspan="3" style="text-align:right; color:#64748b; font-weight:600;">Desconto</td>
                        <td style="text-align:right; color:#dc2626; font-weight:700;">- R$ {{ number_format((float)$quote->discount, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" style="text-align:right; padding:16px 0; color:#065f46; font-weight:900; font-size:1.15rem; border-top:2px solid #dcfce7;">TOTAL</td>
                        <td style="text-align:right; padding:16px 0; color:#065f46; font-weight:900; font-size:1.35rem; border-top:2px solid #dcfce7;">{{ $quote->formatted_total }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        @if($quote->notes)
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 14px;">
            <h6 style="margin:0 0 10px 0; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:1px; font-size:.8rem;"><i class="fas fa-sticky-note me-2" style="color:#f59e0b;"></i> Observações</h6>
            <p style="margin:0; color:#475569; font-size:.88rem; line-height:1.6; white-space:pre-wrap;">{{ $quote->notes }}</p>
        </div>
        @endif
        @if($quote->terms)
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
            <h6 style="margin:0 0 10px 0; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:1px; font-size:.8rem;"><i class="fas fa-file-contract me-2" style="color:#4f46e5;"></i> Termos</h6>
            <p style="margin:0; color:#475569; font-size:.88rem; line-height:1.6; white-space:pre-wrap;">{{ $quote->terms }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
