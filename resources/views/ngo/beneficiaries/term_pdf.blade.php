<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Ficha do Beneficiário</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { margin: 0; font-size: 16px; }
        h2 { margin: 0; font-size: 13px; }
        .muted { color: #64748b; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; padding: 4px 6px; }
        .k { color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 800; }
        .v { font-weight: 800; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 7px; vertical-align: top; }
        th { background: #f8fafc; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; }
        .right { text-align: right; }
        .center { text-align: center; }
        .pill { display:inline-block; padding: 2px 8px; border-radius: 999px; font-weight: 900; font-size: 10px; letter-spacing: .4px; }
        .active { background:#dcfce7; color:#166534; }
        .inactive { background:#f1f5f9; color:#64748b; }
        .graduated { background:#dbeafe; color:#1d4ed8; }
        .sign { margin-top: 18px; }
        .line { border-top: 1px solid #94a3b8; margin-top: 22px; padding-top: 6px; font-size: 12px; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Ficha do Beneficiário / Família Atendida</h1>
        <div class="muted small">{{ $orgName }} · Gerado em {{ $generatedAt }} · Emitido por {{ $emitter }}</div>
        <div class="muted small" style="margin-top:4px;">
            Filtros atendimentos: de={{ $from ?: '—' }} · até={{ $to ?: '—' }} · tipo={{ $type ?: 'todos' }} · busca={{ $q ?: '—' }}
        </div>
    </div>

    <div class="box">
        <h2>Dados do Titular</h2>
        <table class="grid">
            <tr>
                <td>
                    <div class="k">Nome</div>
                    <div class="v">{{ $beneficiary->name }}</div>
                </td>
                <td class="right">
                    @php $st = $beneficiary->status ?: 'inactive'; @endphp
                    <div class="k">Status</div>
                    <div><span class="pill {{ $st }}">{{ strtoupper($st) }}</span></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="k">NIS</div>
                    <div class="v">{{ $beneficiary->nis ?: '—' }}</div>
                </td>
                <td>
                    <div class="k">CPF</div>
                    <div class="v">{{ $beneficiary->cpf ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="k">Nascimento</div>
                    <div class="v">{{ optional($beneficiary->birth_date)->format('d/m/Y') ?: '—' }}</div>
                </td>
                <td>
                    <div class="k">Telefone</div>
                    <div class="v">{{ $beneficiary->phone ?: '—' }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="k">Endereço</div>
                    <div class="v">{{ $beneficiary->address ?: '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Composição Familiar</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Parentesco</th>
                    <th class="center" style="width:120px;">Nascimento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($beneficiary->familyMembers as $m)
                    <tr>
                        <td><strong>{{ $m->name }}</strong></td>
                        <td>{{ $m->kinship }}</td>
                        <td class="center">{{ optional($m->birth_date)->format('d/m/Y') ?: '—' }}</td>
                    </tr>
                @endforeach
                @if($beneficiary->familyMembers->count() === 0)
                    <tr><td colspan="3" class="center muted" style="padding:10px;">Nenhum familiar cadastrado.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="box">
        <h2>Atendimentos (últimos {{ number_format((int) $attendances->count()) }} de {{ number_format((int) ($stats['attendances_total'] ?? 0)) }})</h2>
        <div class="muted small" style="margin-top:4px;">
            Último atendimento: <strong>
                {{ !empty($stats['last_attendance_at']) ? \Carbon\Carbon::parse($stats['last_attendance_at'])->format('d/m/Y') : '—' }}
            </strong>
        </div>

        <table style="margin-top: 8px;">
            <thead>
                <tr>
                    <th style="width:90px;">Data</th>
                    <th style="width:160px;">Tipo</th>
                    <th>Descrição</th>
                    <th style="width:150px;">Registrado por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $a)
                    <tr>
                        <td>{{ optional($a->date)->format('d/m/Y') ?: '—' }}</td>
                        <td><strong>{{ $a->type }}</strong></td>
                        <td>{{ $a->description }}</td>
                        <td>{{ $a->user->name ?? 'Sistema' }}</td>
                    </tr>
                @endforeach
                @if($attendances->count() === 0)
                    <tr><td colspan="4" class="center muted" style="padding:10px;">Nenhum atendimento encontrado para os filtros.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="sign">
        <div class="line"><strong>Responsável</strong> (assinatura)</div>
        <div class="line"><strong>Coordenação</strong> (assinatura)</div>
    </div>
</body>
</html>

