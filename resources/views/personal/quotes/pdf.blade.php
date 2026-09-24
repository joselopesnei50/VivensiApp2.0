<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $quote->quote_number }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11pt; line-height: 1.4; }
        h1, h2, h3, h4 { margin: 0; padding: 0; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 20px; }
        .header-flex { display: table; width: 100%; }
        .header-cell { display: table-cell; vertical-align: top; }
        .brand-name { font-size: 20pt; font-weight: 900; color: #1e293b; letter-spacing: -0.5px; }
        .brand-sub { color: #64748b; font-size: 9pt; }
        .quote-badge { text-align: right; }
        .quote-number { display: inline-block; background: #eef2ff; color: #4338ca; padding: 6px 14px; border-radius: 6px; font-weight: 900; font-family: monospace; font-size: 12pt; }
        .quote-title { font-size: 16pt; font-weight: 900; color: #1e293b; margin: 18px 0 4px 0; }
        .quote-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 8pt; font-weight: 900; text-transform: uppercase; background: #f1f5f9; color: #475569; }
        .meta-grid { display: table; width: 100%; margin: 18px 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .meta-cell { display: table-cell; padding: 10px 14px; border-right: 1px solid #e2e8f0; width: 25%; }
        .meta-cell:last-child { border-right: 0; }
        .meta-label { font-size: 7.5pt; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .meta-value { font-size: 10pt; color: #1e293b; font-weight: 700; margin-top: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items thead th { background: #4f46e5; color: white; padding: 10px 12px; font-size: 9pt; text-align: left; }
        table.items tbody td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 10pt; vertical-align: top; }
        table.items tbody tr:last-child td { border-bottom: 0; }
        .item-name { font-weight: 700; color: #1e293b; }
        .item-desc { color: #64748b; font-size: 9pt; margin-top: 3px; }
        .text-right { text-align: right; }
        .totals { width: 300px; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 6px 12px; font-size: 10pt; }
        .totals .row-total td { border-top: 2px solid #065f46; padding-top: 10px; color: #065f46; font-size: 13pt; font-weight: 900; }
        .footer-block { margin-top: 20px; padding: 12px 14px; background: #f8fafc; border-left: 3px solid #4f46e5; border-radius: 4px; }
        .footer-block h4 { font-size: 8pt; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 6px; }
        .footer-block p { font-size: 9.5pt; color: #475569; white-space: pre-wrap; margin: 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-flex">
            <div class="header-cell">
                <div class="brand-name">{{ $quote->tenant->brand_name ?: $quote->tenant->name }}</div>
                <div class="brand-sub">{{ $quote->tenant->razao_social ?: $quote->tenant->name }}</div>
                @if($quote->tenant->document)<div class="brand-sub">CNPJ/CPF: {{ $quote->tenant->document }}</div>@endif
            </div>
            <div class="header-cell quote-badge">
                <div class="quote-number">{{ $quote->quote_number }}</div>
                <div style="margin-top: 8px;">
                    <span class="quote-status">{{ $quote->status_label }}</span>
                </div>
            </div>
        </div>
    </div>

    <h2 class="quote-title">{{ $quote->title }}</h2>

    <div class="meta-grid">
        <div class="meta-cell">
            <div class="meta-label">Cliente</div>
            <div class="meta-value">{{ $quote->client?->name ?: '—' }}</div>
        </div>
        <div class="meta-cell">
            <div class="meta-label">Emitido em</div>
            <div class="meta-value">{{ $quote->issue_date->format('d/m/Y') }}</div>
        </div>
        <div class="meta-cell">
            <div class="meta-label">Válido até</div>
            <div class="meta-value">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : 'Sem validade' }}</div>
        </div>
        <div class="meta-cell">
            <div class="meta-label">Total</div>
            <div class="meta-value" style="color:#065f46; font-weight:900;">{{ $quote->formatted_total }}</div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item / Descrição</th>
                <th class="text-right" style="width: 70px;">Qtd</th>
                <th class="text-right" style="width: 110px;">Valor un.</th>
                <th class="text-right" style="width: 120px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $it)
            <tr>
                <td>
                    <div class="item-name">{{ $it->name }}</div>
                    @if($it->description)<div class="item-desc">{{ $it->description }}</div>@endif
                </td>
                <td class="text-right">{{ rtrim(rtrim(number_format((float)$it->quantity, 3, ',', ''), '0'), ',') }} {{ $it->unit }}</td>
                <td class="text-right">R$ {{ number_format((float)$it->unit_price, 2, ',', '.') }}</td>
                <td class="text-right"><strong>R$ {{ number_format((float)$it->subtotal, 2, ',', '.') }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">R$ {{ number_format($quote->subtotal_items, 2, ',', '.') }}</td>
        </tr>
        @if((float) $quote->discount > 0)
        <tr>
            <td>Desconto</td>
            <td class="text-right" style="color:#dc2626;">- R$ {{ number_format((float)$quote->discount, 2, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="row-total">
            <td>TOTAL</td>
            <td class="text-right">{{ $quote->formatted_total }}</td>
        </tr>
    </table>

    @if($quote->notes)
    <div class="footer-block">
        <h4>Observações</h4>
        <p>{{ $quote->notes }}</p>
    </div>
    @endif

    @if($quote->terms)
    <div class="footer-block" style="border-left-color:#f59e0b;">
        <h4>Termos e Condições</h4>
        <p>{{ $quote->terms }}</p>
    </div>
    @endif
</body>
</html>
