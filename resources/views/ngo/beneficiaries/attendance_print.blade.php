<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico de Atendimentos</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; }
        .muted { color: #64748b; }
        .header { display:flex; justify-content: space-between; align-items: end; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px; gap: 12px; flex-wrap: wrap; }
        h1 { margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 11px; color: #475569; }
        .no-print { margin-bottom: 10px; }
        @media print { .no-print { display:none; } }
        .sign { margin-top: 24px; display:flex; gap: 30px; }
        .line { flex: 1; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:10px; background:#111827; color:#fff; font-weight:800; cursor:pointer;">Imprimir</button>
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" style="margin-left:10px; color:#4f46e5; font-weight:800; text-decoration:none;">Voltar</a>
    </div>

    <div class="header">
        <div>
            <h1>Histórico de Atendimentos</h1>
            <div class="muted">{{ $orgName }} · Gerado em {{ $generatedAt }}</div>
            <div class="muted" style="margin-top:4px;">
                Beneficiário: <strong>{{ $beneficiary->name }}</strong> · NIS: {{ $beneficiary->nis ?? '—' }}
            </div>
            <div class="muted" style="margin-top:4px;">
                Filtros: de={{ $from ?: '—' }} · até={{ $to ?: '—' }} · tipo={{ $type ?: 'todos' }} · busca={{ $q ?: '—' }}
            </div>
        </div>
        <div class="muted">Total: <strong>{{ number_format((int) $attendances->count()) }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 90px;">Data</th>
                <th style="width: 160px;">Tipo</th>
                <th>Descrição</th>
                <th style="width: 160px;">Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $a)
                <tr>
                    <td>{{ optional($a->date)->format('d/m/Y') ?? '—' }}</td>
                    <td><strong>{{ $a->type }}</strong></td>
                    <td>{{ $a->description }}</td>
                    <td>{{ $a->user->name ?? 'Sistema' }}</td>
                </tr>
            @endforeach
            @if($attendances->count() === 0)
                <tr><td colspan="4" class="muted" style="padding:14px; text-align:center;">Nenhum atendimento encontrado.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="sign">
        <div class="line"><strong>Responsável</strong> (assinatura)</div>
        <div class="line"><strong>Coordenação</strong> (assinatura)</div>
    </div>
</body>
</html>

