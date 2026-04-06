<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Relatório Social - {{ (int) $year }}</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { margin: 0; font-size: 16px; }
        h2 { margin: 0; font-size: 13px; }
        .muted { color: #64748b; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 7px; vertical-align: top; }
        th { background: #f8fafc; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bar { height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .bar > div { height: 10px; background: #4f46e5; }
        .small { font-size: 11px; }
        .sign { margin-top: 16px; }
        .line { border-top: 1px solid #94a3b8; margin-top: 22px; padding-top: 6px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Relatório Social (Prestação de Contas) – {{ (int) $year }}</h1>
        <div class="muted small">{{ $orgName }} · Período {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
        <div class="muted small">Gerado em {{ $generatedAt }} · Emitido por {{ $emitter }}</div>
        <div class="muted small">Filtros: status={{ $benefStatus ?: 'todos' }} · tipo={{ $type ?: 'todos' }}</div>
    </div>

    <div class="box">
        <h2>Resumo</h2>
        <table>
            <tr>
                <td>
                    <div class="muted small">Atendimentos (ano)</div>
                    <div style="font-size:18px; font-weight:900;">{{ number_format((int) $totalAttendances) }}</div>
                </td>
                <td>
                    <div class="muted small">Famílias únicas atendidas</div>
                    <div style="font-size:18px; font-weight:900;">{{ number_format((int) $uniqueFamilies) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php $maxMonth = max(1, max($monthly)); @endphp
    <div class="box">
        <h2>Atendimentos por mês</h2>
        <table>
            <thead>
                <tr>
                    <th style="width:70px;">Mês</th>
                    <th>Volume</th>
                    <th class="right" style="width:80px;">Qtde</th>
                </tr>
            </thead>
            <tbody>
                @for($m=1;$m<=12;$m++)
                    @php $val=(int)($monthly[$m]??0); $pct=($val/$maxMonth)*100; @endphp
                    <tr>
                        <td class="center">{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</td>
                        <td>
                            <div class="bar"><div style="width: {{ (int) $pct }}%;"></div></div>
                        </td>
                        <td class="right"><strong>{{ number_format($val) }}</strong></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="box">
        <h2>Top tipos</h2>
        <table>
            <thead><tr><th>Tipo</th><th class="right" style="width:80px;">Qtde</th></tr></thead>
            <tbody>
                @foreach($byType as $r)
                    <tr><td><strong>{{ $r->type }}</strong></td><td class="right">{{ number_format((int) $r->c) }}</td></tr>
                @endforeach
                @if($byType->count()===0)<tr><td colspan="2" class="center muted">Sem dados.</td></tr>@endif
            </tbody>
        </table>
    </div>

    <div class="box">
        <h2>Top equipe</h2>
        <table>
            <thead><tr><th>Usuário</th><th class="right" style="width:80px;">Qtde</th></tr></thead>
            <tbody>
                @foreach($byUser as $r)
                    <tr><td><strong>{{ $r->name }}</strong></td><td class="right">{{ number_format((int) $r->c) }}</td></tr>
                @endforeach
                @if($byUser->count()===0)<tr><td colspan="2" class="center muted">Sem dados.</td></tr>@endif
            </tbody>
        </table>
    </div>

    <div class="box">
        <h2>Top famílias</h2>
        <table>
            <thead><tr><th>Beneficiário</th><th class="center" style="width:90px;">Status</th><th class="right" style="width:80px;">Qtde</th></tr></thead>
            <tbody>
                @foreach($topFamilies as $r)
                    <tr>
                        <td><strong>{{ $r->name }}</strong></td>
                        <td class="center">{{ strtoupper($r->status ?? '—') }}</td>
                        <td class="right">{{ number_format((int) $r->c) }}</td>
                    </tr>
                @endforeach
                @if($topFamilies->count()===0)<tr><td colspan="3" class="center muted">Sem dados.</td></tr>@endif
            </tbody>
        </table>
    </div>

    <div class="sign">
        <div class="line"><strong>Responsável</strong> (assinatura)</div>
        <div class="line"><strong>Coordenação</strong> (assinatura)</div>
    </div>
</body>
</html>

