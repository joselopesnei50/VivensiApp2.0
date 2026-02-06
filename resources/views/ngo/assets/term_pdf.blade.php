<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventário Patrimonial</title>
    <style>
        @page { margin: 26px 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 16px; letter-spacing: .06em; text-transform: uppercase; }
        .header .sub { margin-top: 6px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 10px; vertical-align: top; }
        thead th { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .badge { display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .ok { background: #dcfce7; color: #16a34a; }
        .warn { background: #fef9c3; color: #ca8a04; }
        .bad { background: #fee2e2; color: #dc2626; }
        .kpi { width: 100%; margin: 12px 0 18px; }
        .kpi td { padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .sig { margin-top: 22px; width: 100%; }
        .sig td { padding-top: 28px; text-align: center; }
        .sig .line { border-top: 1px solid #0f172a; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $orgName }}</h1>
        <div class="sub muted">Termo de Inventário Patrimonial — Gerado em {{ $generatedAt }} · Emitente: {{ $emitter }}</div>
    </div>

    <table class="kpi">
        <tr>
            <td><div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Total itens</div><div style="font-weight:900; font-size: 14px;">{{ number_format((int)($totals['count'] ?? 0)) }}</div></td>
            <td><div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Ativos</div><div style="font-weight:900; font-size: 14px;">{{ number_format((int)($totals['active'] ?? 0)) }}</div></td>
            <td><div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Manutenção</div><div style="font-weight:900; font-size: 14px;">{{ number_format((int)($totals['maintenance'] ?? 0)) }}</div></td>
            <td><div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Baixados</div><div style="font-weight:900; font-size: 14px;">{{ number_format((int)($totals['disposed'] ?? 0)) }}</div></td>
        </tr>
        <tr>
            <td colspan="4"><div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Valor total</div><div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)($totals['value'] ?? 0), 2, ',', '.') }}</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th>Bem</th>
                <th style="width: 18%;">Localização</th>
                <th style="width: 18%;">Responsável</th>
                <th style="width: 12%;" class="center">Status</th>
                <th style="width: 12%;" class="num">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $a)
                @php
                    $map = [
                        'active' => ['ATIVO', 'ok'],
                        'maintenance' => ['MANUT.', 'warn'],
                        'disposed' => ['BAIX.', 'bad'],
                        'lost' => ['BAIX.', 'bad'],
                    ];
                    [$lbl, $cls] = $map[$a->status] ?? ['—', ''];
                @endphp
                <tr>
                    <td style="font-weight:800; color:#475569;">{{ $a->code ?? '-' }}</td>
                    <td>
                        <div style="font-weight:900;">{{ $a->name }}</div>
                        <div class="muted" style="font-size: 10px;">
                            Aquisição: {{ $a->acquisition_date ? \Carbon\Carbon::parse($a->acquisition_date)->format('d/m/Y') : '—' }}
                        </div>
                    </td>
                    <td>{{ $a->location ?? '-' }}</td>
                    <td>{{ $a->responsible ?? '-' }}</td>
                    <td class="center">
                        <span class="badge {{ $cls }}">{{ $lbl }}</span>
                    </td>
                    <td class="num" style="font-weight:900;">R$ {{ number_format((float)$a->value, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @if($assets->isEmpty())
                <tr>
                    <td colspan="6" class="center muted" style="padding: 30px;">Nenhum item encontrado.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td style="width: 48%;"><div class="line">Responsável pela conferência</div></td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;"><div class="line">Direção / Conselho</div></td>
        </tr>
    </table>
</body>
</html>

