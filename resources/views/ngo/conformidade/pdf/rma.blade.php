<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>RMA {{ $periodo }}</title>
<style>
@page { margin: 28px 30px; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; }
.muted { color: #64748b; }
.header { border-bottom: 3px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
.header h1 { margin: 0 0 3px; font-size: 15px; letter-spacing: .05em; text-transform: uppercase; font-weight: 900; }
.header .sub { font-size: 10px; color: #475569; }
.label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 2px; }
.section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin: 16px 0 8px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
th { background: #f1f5f9; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #475569; border-bottom: 1px solid #cbd5e1; text-align: left; }
td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; vertical-align: middle; }
tr.total-row td { font-weight: 700; background: #f8fafc; border-top: 1px solid #cbd5e1; }
.num { text-align: right; }
.badge-verde    { background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.badge-amarelo  { background: #fef9c3; color: #713f12; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.badge-vermelho { background: #fee2e2; color: #7f1d1d; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.kpi-row td { padding: 8px 12px; border: 1px solid #e2e8f0; }
.kpi-val { font-size: 18px; font-weight: 900; }
.sig-table { margin-top: 30px; width: 100%; }
.sig-table td { padding-top: 35px; text-align: center; width: 33%; }
.sig-line { border-top: 1px solid #0f172a; padding-top: 6px; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ strtoupper($tenant->name) }}</h1>
    <div class="sub">
        CNPJ: {{ $tenant->cnpj ?? '—' }}
        @if($tenant->cmas_numero) · CMAS nº {{ $tenant->cmas_numero }} @endif
        &nbsp;·&nbsp; Relatório Mensal de Atendimentos — {{ strtoupper($periodo) }}
        &nbsp;·&nbsp; Gerado em {{ $gerado_em }}
    </div>
</div>

{{-- KPIs --}}
<div class="section-title">Resumo do Período</div>
<table class="kpi-row">
<tr>
    <td>
        <div class="label">Total de Atendimentos</div>
        <div class="kpi-val">{{ number_format($total, 0, ',', '.') }}</div>
    </td>
    <td>
        <div class="label">Beneficiários Únicos</div>
        <div class="kpi-val">{{ number_format($beneficiarios, 0, ',', '.') }}</div>
    </td>
    <td>
        <div class="label">Atendimentos Gratuitos</div>
        <div class="kpi-val">{{ number_format($total_gratuito, 0, ',', '.') }}</div>
    </td>
    <td>
        <div class="label">% Gratuidade</div>
        <div class="kpi-val" style="color:{{ $pct_gratuito >= 20 ? '#059669' : ($pct_gratuito >= 16 ? '#d97706' : '#dc2626') }}">
            {{ number_format($pct_gratuito, 1, ',', '.') }}%
        </div>
        <div class="muted" style="font-size:9px;">mínimo CEBAS: 20%</div>
    </td>
</tr>
</table>

{{-- Tipificação SUAS --}}
<div class="section-title">Atendimentos por Tipificação SUAS</div>
@if($por_tipificacao->isEmpty())
<p class="muted">Nenhum atendimento registrado no período.</p>
@else
<table>
<thead>
<tr>
    <th>Tipificação SUAS</th>
    <th class="num">Atendimentos</th>
    <th class="num">Gratuitos</th>
    <th class="num">% do Total</th>
</tr>
</thead>
<tbody>
@foreach($por_tipificacao as $tipo => $dados)
<tr>
    <td>{{ $tipo }}</td>
    <td class="num">{{ number_format($dados['total'], 0, ',', '.') }}</td>
    <td class="num">{{ number_format($dados['gratuito'], 0, ',', '.') }}</td>
    <td class="num">{{ number_format($dados['pct'], 1, ',', '.') }}%</td>
</tr>
@endforeach
<tr class="total-row">
    <td>TOTAL</td>
    <td class="num">{{ number_format($total, 0, ',', '.') }}</td>
    <td class="num">{{ number_format($total_gratuito, 0, ',', '.') }}</td>
    <td class="num">100%</td>
</tr>
</tbody>
</table>
@endif

{{-- Modalidade --}}
@if($por_tipo->isNotEmpty())
<div class="section-title">Atendimentos por Modalidade</div>
<table>
<thead>
<tr><th>Modalidade</th><th class="num">Qtd</th></tr>
</thead>
<tbody>
@foreach($por_tipo as $tipo => $qtd)
<tr>
    <td>{{ ucfirst($tipo) }}</td>
    <td class="num">{{ number_format($qtd, 0, ',', '.') }}</td>
</tr>
@endforeach
</tbody>
</table>
@endif

{{-- Conformidade --}}
<div class="section-title">Nota de Conformidade</div>
<p style="font-size:10.5px; line-height:1.6; color:#374151;">
    O percentual de gratuidade de <strong>{{ number_format($pct_gratuito, 1, ',', '.') }}%</strong>
    @if($pct_gratuito >= 20)
        <span class="badge-verde">está ACIMA</span>
    @elseif($pct_gratuito >= 16)
        <span class="badge-amarelo">está EM ATENÇÃO</span>
    @else
        <span class="badge-vermelho">está ABAIXO</span>
    @endif
    do mínimo legal exigido para manutenção do CEBAS (20%).
    Relatório gerado automaticamente pelo sistema Vivensi em {{ $gerado_em }}.
</p>

{{-- Assinaturas --}}
<table class="sig-table">
<tr>
    <td><div class="sig-line">Coordenador(a) Técnico(a)</div></td>
    <td><div class="sig-line">Responsável Financeiro</div></td>
    <td><div class="sig-line">Data &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div></td>
</tr>
</table>

</body>
</html>
