<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Transparência - {{ $portal->title }} ({{ $year }})</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .muted { color: #475569; }
        .h1 { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; }
        .h2 { font-size: 14px; font-weight: 800; margin: 18px 0 8px 0; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; }
        .kpi { width: 100%; border-collapse: collapse; }
        .kpi td { padding: 8px 10px; border: 1px solid #e2e8f0; }
        .kpi .label { color: #475569; font-weight: 700; }
        .kpi .value { font-size: 14px; font-weight: 800; }
        table.tbl { width: 100%; border-collapse: collapse; }
        table.tbl th, table.tbl td { border: 1px solid #e2e8f0; padding: 6px 8px; }
        table.tbl th { background: #f1f5f9; text-align: left; }
        .right { text-align: right; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="h1">Relatório público de transparência (agregado)</div>
        <div class="muted">
            {{ $portal->title }} • CNPJ: {{ $portal->cnpj ?? '—' }} • Ano: {{ $year }}
            @if(!empty($publicDataUpdatedAt))
                • Atualizado em: {{ \Carbon\Carbon::parse($publicDataUpdatedAt)->format('d/m/Y H:i') }}
            @endif
        </div>
        <div class="small muted" style="margin-top: 8px; line-height: 1.6;">
            Este relatório publica informações de interesse público em formato agregado. Em conformidade com a LGPD, não contém dados pessoais de beneficiários
            e não inclui descrições individuais de lançamentos.
        </div>
    </div>

    <div class="h2">1) Resumo financeiro (pagos)</div>
    <table class="kpi">
        <tr>
            <td>
                <div class="label">Entradas confirmadas</div>
                <div class="value">R$ {{ number_format((float) $totalIn, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="label">Saídas confirmadas</div>
                <div class="value">R$ {{ number_format((float) $totalOut, 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="label">Investimento social</div>
                <div class="value">R$ {{ number_format((float) $investmentSocial, 2, ',', '.') }}</div>
                <div class="small muted">{{ $investmentNote ?? '' }}</div>
            </td>
            <td>
                <div class="label">Saldo do período</div>
                <div class="value">R$ {{ number_format((float) $balance, 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="h2">2) Totais mensais (pagos)</div>
    <table class="tbl">
        <thead>
            <tr>
                <th>Mês</th>
                <th class="right">Entradas</th>
                <th class="right">Saídas</th>
                <th class="right">Saldo do mês</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($monthlyTotals ?? []) as $row)
                <tr>
                    <td style="font-weight: 800;">{{ strtoupper($row['label'] ?? '') }}</td>
                    <td class="right">R$ {{ number_format((float) ($row['income'] ?? 0), 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format((float) ($row['expense'] ?? 0), 2, ',', '.') }}</td>
                    <td class="right" style="font-weight: 800;">R$ {{ number_format((float) ($row['balance'] ?? 0), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="h2">3) Despesas por categoria (pagas)</div>
    <table class="tbl">
        <thead>
            <tr>
                <th>Categoria</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($expenseByCategory ?? []) as $row)
                <tr>
                    <td style="font-weight: 700;">{{ $row->category ?? 'Sem categoria' }}</td>
                    <td class="right" style="font-weight: 800;">R$ {{ number_format((float) ($row->total ?? 0), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="h2">4) Impacto social (quantitativo)</div>
    <table class="kpi">
        <tr>
            <td>
                <div class="label">Famílias cadastradas</div>
                <div class="value">{{ (int) ($familiesCount ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Pessoas impactadas (estimativa)</div>
                <div class="value">{{ (int) ($peopleCount ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Atendimentos no ano</div>
                <div class="value">{{ (int) ($attendancesYearTotal ?? 0) }}</div>
            </td>
        </tr>
    </table>

    <div class="h2">5) Patrimônio (resumo)</div>
    <table class="kpi">
        <tr>
            <td>
                <div class="label">Bens cadastrados</div>
                <div class="value">{{ (int) ($assetsCount ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Valor histórico total</div>
                <div class="value">R$ {{ number_format((float) ($assetsTotalValue ?? 0), 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="h2">6) Recursos humanos (agregado)</div>
    <table class="kpi">
        <tr>
            <td>
                <div class="label">Colaboradores ativos</div>
                <div class="value">{{ (int) ($employeesCount ?? 0) }}</div>
            </td>
            <td>
                <div class="label">Folha (salários base)</div>
                <div class="value">R$ {{ number_format((float) ($payrollTotal ?? 0), 2, ',', '.') }}</div>
            </td>
            <td>
                <div class="label">Bônus (total)</div>
                <div class="value">R$ {{ number_format((float) ($bonusTotal ?? 0), 2, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="h2">7) Governança e parcerias</div>
    <table class="grid">
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="box">
                    <div style="font-weight: 800; margin-bottom: 6px;">Diretoria</div>
                    @if(($board ?? collect())->count() > 0)
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($board as $m)
                                    <tr>
                                        <td>{{ $m->name }}</td>
                                        <td>{{ $m->position }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="muted">Sem diretoria cadastrada.</div>
                    @endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div style="font-weight: 800; margin-bottom: 6px;">Parcerias públicas</div>
                    @if(($partnerships ?? collect())->count() > 0)
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>Projeto</th>
                                    <th class="right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partnerships as $p)
                                    <tr>
                                        <td>{{ $p->project_name }}<br><span class="small muted">{{ $p->agency_name }}</span></td>
                                        <td class="right" style="font-weight: 800;">R$ {{ number_format((float) ($p->value ?? 0), 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="muted">Sem parcerias cadastradas.</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="h2">8) SIC (LAI)</div>
    <div class="box">
        <div class="muted" style="line-height: 1.8;">
            Canal oficial: <strong>{{ $portal->sic_email ?? 'transparencia@vivensi.org' }}</strong>
            @if(!empty($portal->sic_phone))
                • Telefone: <strong>{{ $portal->sic_phone }}</strong>
            @endif
            <br>
            Solicitações de acesso à informação devem indicar tema, período e formato desejado. Quando aplicável, informações adicionais são fornecidas com análise de sigilo e anonimização.
        </div>
    </div>

    @php
        $pp = $portal->settings['privacy_policy'] ?? null;
        $dpoName = $portal->settings['dpo_name'] ?? null;
        $dpoEmail = $portal->settings['dpo_email'] ?? null;
        $dpoPhone = $portal->settings['dpo_phone'] ?? null;
        $legalBasis = $portal->settings['legal_basis_note'] ?? null;
        $retention = $portal->settings['data_retention_note'] ?? null;
        $hasLgpd = !empty($pp) || !empty($dpoName) || !empty($dpoEmail) || !empty($dpoPhone) || !empty($legalBasis) || !empty($retention);
    @endphp

    @if($hasLgpd)
        <div class="h2">9) LGPD (Privacidade e Encarregado)</div>
        <div class="box">
            <div class="muted" style="line-height: 1.8;">
                @if(!empty($legalBasis))
                    <div><strong>Nota:</strong> {{ $legalBasis }}</div>
                @endif
                @if(!empty($retention))
                    <div style="margin-top: 6px;"><strong>Retenção / logs:</strong> {{ $retention }}</div>
                @endif
                @if(!empty($dpoName) || !empty($dpoEmail) || !empty($dpoPhone))
                    <div style="margin-top: 10px;">
                        <strong>Encarregado (DPO):</strong>
                        @if(!empty($dpoName)) {{ $dpoName }} @endif
                        @if(!empty($dpoEmail)) • {{ $dpoEmail }} @endif
                        @if(!empty($dpoPhone)) • {{ $dpoPhone }} @endif
                    </div>
                @endif
                @if(!empty($pp))
                    <div style="margin-top: 10px;">
                        <strong>Política de privacidade (portal):</strong><br>
                        <span class="small">{{ $pp }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</body>
</html>
