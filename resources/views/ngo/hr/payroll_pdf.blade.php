<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Folha de Pagamento - {{ $referenceLabel }}</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .muted { color: #64748b; }
        .row { width: 100%; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .kpis { width: 100%; border-collapse: collapse; }
        .kpis td { padding: 6px 8px; vertical-align: top; }
        .kpi { font-size: 13px; font-weight: 700; }
        .small { font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
        th { background: #f8fafc; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; }
        .right { text-align: right; }
        .center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 10px; background: #eef2ff; color: #3730a3; }
        .sign { margin-top: 24px; }
        .line { border-top: 1px solid #94a3b8; margin-top: 28px; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Folha de Pagamento (Referência {{ $referenceLabel }})</h1>
        <div class="muted small">
            Organização: <strong>{{ $orgName }}</strong> · Gerado em: <strong>{{ $generatedAt }}</strong> · Emitido por: <strong>{{ auth()->user()->name ?? '—' }}</strong>
        </div>
        <div class="muted small" style="margin-top:6px;">
            Observação: este documento reflete os valores cadastrados no módulo de RH para referência mensal (salário e, opcionalmente, bônus).
        </div>
    </div>

    <div class="box">
        <table class="kpis">
            <tr>
                <td>
                    <div class="muted small">Colaboradores listados</div>
                    <div class="kpi">{{ number_format((int) $employees->count()) }}</div>
                    <div class="muted small">Status filtrado: <span class="badge">{{ $status === 'all' ? 'todos' : $status }}</span></div>
                </td>
                <td>
                    <div class="muted small">Total Salários</div>
                    <div class="kpi">R$ {{ number_format((float) $sumSalary, 2, ',', '.') }}</div>
                </td>
                <td>
                    <div class="muted small">Total Bônus</div>
                    <div class="kpi">
                        @if($includeBonus)
                            R$ {{ number_format((float) $sumBonus, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </div>
                </td>
                <td>
                    <div class="muted small">Total Geral</div>
                    <div class="kpi">R$ {{ number_format((float) $sumTotal, 2, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div style="font-weight:700; margin-bottom:8px;">Resumo por Projeto</div>
        <table>
            <thead>
                <tr>
                    <th>Projeto</th>
                    <th class="center">Qtde.</th>
                    <th class="right">Salários</th>
                    <th class="right">Bônus</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byProject as $projectId => $row)
                    <tr>
                        <td>
                            @if((int) $projectId === 0)
                                Sem Projeto
                            @else
                                {{ $projects[$projectId]->name ?? ('Projeto #' . $projectId) }}
                            @endif
                        </td>
                        <td class="center">{{ number_format((int) ($row['count'] ?? 0)) }}</td>
                        <td class="right">R$ {{ number_format((float) ($row['salary'] ?? 0), 2, ',', '.') }}</td>
                        <td class="right">
                            @if($includeBonus)
                                R$ {{ number_format((float) ($row['bonus'] ?? 0), 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="right">R$ {{ number_format((float) ($row['total'] ?? 0), 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                @if($employees->count() === 0)
                    <tr>
                        <td colspan="5" class="muted center" style="padding:12px;">Nenhum funcionário para o filtro selecionado.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="box">
        <div style="font-weight:700; margin-bottom:8px;">Detalhamento</div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cargo</th>
                    <th>Contrato</th>
                    <th>Projeto</th>
                    <th class="center">Status</th>
                    <th class="right">Salário</th>
                    <th class="right">Bônus</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $e)
                    @php
                        $salary = (float) ($e->salary ?? 0);
                        $bonus = $includeBonus ? (float) ($e->bonus ?? 0) : 0.0;
                        $total = $salary + $bonus;
                    @endphp
                    <tr>
                        <td>{{ $e->name }}</td>
                        <td>{{ $e->position }}</td>
                        <td>{{ $e->contract_type }}</td>
                        <td>
                            @if(!empty($e->project_id))
                                {{ $projects[$e->project_id]->name ?? ('Projeto #' . $e->project_id) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="center">{{ $e->status }}</td>
                        <td class="right">R$ {{ number_format($salary, 2, ',', '.') }}</td>
                        <td class="right">
                            @if($includeBonus)
                                R$ {{ number_format((float) ($e->bonus ?? 0), 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                @if($employees->count() === 0)
                    <tr>
                        <td colspan="8" class="muted center" style="padding:12px;">Nenhum funcionário encontrado.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="sign">
        <div class="line"><strong>Responsável</strong> (assinatura)</div>
        <div class="line"><strong>Diretoria</strong> (assinatura)</div>
    </div>
</body>
</html>

