<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DRE {{ $year }}</title>
    <style>
        @page { margin: 26px 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 16px; letter-spacing: .06em; text-transform: uppercase; }
        .header .sub { margin-top: 6px; font-size: 12px; }
        .meta { margin-bottom: 14px; }
        .meta td { padding: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 10px; vertical-align: top; }
        thead th { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #475569; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        .section-row td { font-weight: bold; background: #f8fafc; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; }
        .num { text-align: right; white-space: nowrap; }
        .total { font-weight: bold; }
        .result { font-weight: bold; font-size: 13px; border-top: 2px solid #0f172a; }
        .sig { margin-top: 26px; width: 100%; }
        .sig td { padding-top: 30px; text-align: center; }
        .sig .line { border-top: 1px solid #0f172a; padding-top: 8px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .ok { background: #dcfce7; color: #16a34a; }
        .bad { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $orgName }}</h1>
        <div class="sub muted">
            Demonstrativo do Resultado do Exercício (D.R.E.) — Período: {{ $periodLabel }}
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Ano base:</strong> {{ $year }}</td>
            <td class="num"><strong>Gerado em:</strong> {{ $generatedAt }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="num">Valor (R$)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row">
                <td>(+) RECEITAS OPERACIONAIS BRUTAS</td>
                <td class="num total">{{ number_format((float) $totalIncome, 2, ',', '.') }}</td>
            </tr>
            @foreach($incomes as $inc)
                <tr>
                    <td style="padding-left: 22px;">{{ $inc['name'] }}</td>
                    <td class="num">{{ number_format((float) $inc['value'], 2, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr><td colspan="2" style="padding: 8px;"></td></tr>

            <tr class="section-row">
                <td>(-) CUSTOS E DESPESAS OPERACIONAIS</td>
                <td class="num total">({{ number_format((float) $totalExpense, 2, ',', '.') }})</td>
            </tr>
            @foreach($expenses as $exp)
                <tr>
                    <td style="padding-left: 22px;">{{ $exp['name'] }}</td>
                    <td class="num">({{ number_format((float) $exp['value'], 2, ',', '.') }})</td>
                </tr>
            @endforeach

            <tr class="result">
                <td>(=) RESULTADO DO EXERCÍCIO (SUPERÁVIT/DÉFICIT)</td>
                <td class="num">
                    <span class="badge {{ $result >= 0 ? 'ok' : 'bad' }}">
                        {{ $result >= 0 ? 'SUPERÁVIT' : 'DÉFICIT' }}
                    </span>
                    &nbsp; R$ {{ number_format((float) $result, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="sig">
        <tr>
            <td style="width: 48%;">
                <div class="line">Presidente / Diretor(a)</div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <div class="line">Contador(a) Responsável</div>
            </td>
        </tr>
    </table>
</body>
</html>

