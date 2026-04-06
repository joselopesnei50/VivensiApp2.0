<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista de Beneficiários</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; }
        .muted { color: #64748b; }
        .header { display:flex; justify-content: space-between; align-items: end; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px; }
        h1 { margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 11px; color: #475569; }
        .right { text-align:right; }
        .center { text-align:center; }
        .pill { display:inline-block; padding: 2px 8px; border-radius: 999px; font-weight: 800; font-size: 11px; }
        .active { background:#dcfce7; color:#166534; }
        .inactive { background:#f1f5f9; color:#64748b; }
        .graduated { background:#dbeafe; color:#1d4ed8; }
        .sign { margin-top: 28px; display:flex; gap: 30px; }
        .line { flex: 1; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 12px; }
        @media print {
            .no-print { display:none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:10px; background:#111827; color:#fff; font-weight:800; cursor:pointer;">Imprimir</button>
        <a href="{{ url('/ngo/beneficiaries') }}" style="margin-left:10px; color:#4f46e5; font-weight:800; text-decoration:none;">Voltar</a>
    </div>

    <div class="header">
        <div>
            <h1>Lista de Beneficiários / Famílias Atendidas</h1>
            <div class="muted">{{ $orgName }} · Gerado em {{ $generatedAt }}</div>
            <div class="muted" style="margin-top:4px;">
                Filtros: busca={{ $q !== '' ? $q : '—' }} · status={{ $status !== '' ? $status : 'todos' }}
            </div>
        </div>
        <div class="muted">Total: <strong>{{ number_format((int) $beneficiaries->count()) }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th class="center">NIS/CPF</th>
                <th class="center">Nascimento</th>
                <th class="center">Atendimentos</th>
                <th class="center">Status</th>
                <th>Telefone</th>
                <th>Endereço</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $b)
                <tr>
                    <td><strong>{{ $b->name }}</strong></td>
                    <td class="center">{{ $b->nis ?? $b->cpf ?? '-' }}</td>
                    <td class="center">{{ optional($b->birth_date)->format('d/m/Y') ?? '-' }}</td>
                    <td class="center">{{ (int) $b->attendances_count }}</td>
                    <td class="center">
                        @php $st = $b->status ?: 'inactive'; @endphp
                        <span class="pill {{ $st }}">{{ strtoupper($st) }}</span>
                    </td>
                    <td>{{ $b->phone ?? '-' }}</td>
                    <td>{{ $b->address ?? '-' }}</td>
                </tr>
            @endforeach
            @if($beneficiaries->count() === 0)
                <tr><td colspan="7" class="center muted" style="padding:16px;">Nenhum beneficiário encontrado.</td></tr>
            @endif
        </tbody>
    </table>

    <div class="sign">
        <div class="line"><strong>Responsável</strong> (assinatura)</div>
        <div class="line"><strong>Coordenação</strong> (assinatura)</div>
    </div>
</body>
</html>

