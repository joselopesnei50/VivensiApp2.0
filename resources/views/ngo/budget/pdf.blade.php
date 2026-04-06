<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamento {{ $year }}</title>
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
        .section { font-weight: bold; background: #f8fafc; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
        .kpi { width: 100%; margin: 12px 0 18px; }
        .kpi td { padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .sig { margin-top: 26px; width: 100%; }
        .sig td { padding-top: 30px; text-align: center; }
        .sig .line { border-top: 1px solid #0f172a; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $orgName }}</h1>
        <div class="sub muted">Orçamento Anual — {{ $year }} · Gerado em {{ $generatedAt }}</div>
    </div>

    <table class="kpi">
        <tr>
            <td>
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Planejado (Receitas)</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$plannedIncome, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Planejado (Despesas)</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$plannedExpense, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Realizado (Receitas)</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$realIncome, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Realizado (Despesas)</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$realExpense, 2, ',', '.') }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Resultado Planejado</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$plannedResult, 2, ',', '.') }}</div>
            </td>
            <td colspan="2">
                <div class="muted" style="font-weight:bold; text-transform:uppercase; font-size: 10px; letter-spacing:.06em;">Resultado Realizado</div>
                <div style="font-weight:900; font-size: 14px;">R$ {{ number_format((float)$realResult, 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th class="num">Planejado</th>
                <th class="num">Realizado</th>
                <th class="num">Variação</th>
                <th class="num">% Execução</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section">
                <td colspan="5">(+) RECEITAS</td>
            </tr>
            @foreach($incomeCategories as $category)
                @php
                    $target = $targets->where('category_id', $category->id)->where('type', 'income')->first();
                    $real = $realized->where('category_id', $category->id)->where('type', 'income')->first();
                    $planned = (float) ($target->amount ?? 0);
                    $done = (float) ($real->total ?? 0);
                    $var = $done - $planned;
                    $pct = $planned > 0 ? ($done / $planned) * 100 : 0;
                @endphp
                @if($planned != 0.0 || $done != 0.0)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td class="num">R$ {{ number_format($planned, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($done, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($var, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format($pct, 2, ',', '.') }}%</td>
                    </tr>
                @endif
            @endforeach

            <tr class="section">
                <td colspan="5">(-) DESPESAS</td>
            </tr>
            @foreach($expenseCategories as $category)
                @php
                    $target = $targets->where('category_id', $category->id)->where('type', 'expense')->first();
                    $real = $realized->where('category_id', $category->id)->where('type', 'expense')->first();
                    $planned = (float) ($target->amount ?? 0);
                    $done = (float) ($real->total ?? 0);
                    $var = $done - $planned;
                    $pct = $planned > 0 ? ($done / $planned) * 100 : 0;
                @endphp
                @if($planned != 0.0 || $done != 0.0)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td class="num">R$ {{ number_format($planned, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($done, 2, ',', '.') }}</td>
                        <td class="num">R$ {{ number_format($var, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format($pct, 2, ',', '.') }}%</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td style="width: 48%;">
                <div class="line">Responsável Financeiro(a)</div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <div class="line">Direção / Conselho</div>
            </td>
        </tr>
    </table>
</body>
</html>

