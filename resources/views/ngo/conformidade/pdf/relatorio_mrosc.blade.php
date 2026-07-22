<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório MROSC</title>
<style>
@page { margin: 28px 30px; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; }
.muted { color: #64748b; }
.header { border-bottom: 3px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
.header h1 { margin: 0 0 3px; font-size: 15px; letter-spacing: .05em; text-transform: uppercase; font-weight: 900; }
.header .sub { font-size: 10px; color: #475569; }
.label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 2px; }
.section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin: 14px 0 8px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
th { background: #f1f5f9; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #475569; border-bottom: 1px solid #cbd5e1; text-align: left; }
td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; vertical-align: middle; }
.num { text-align: right; }
.badge-verde    { background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.badge-amarelo  { background: #fef9c3; color: #713f12; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.badge-vermelho { background: #fee2e2; color: #7f1d1d; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.badge-neutro   { background: #f1f5f9; color: #64748b; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: 700; }
.kpi-row td { padding: 8px 12px; border: 1px solid #e2e8f0; text-align: center; }
.kpi-val { font-size: 18px; font-weight: 900; }
.desvio-ok  { color: #059669; }
.desvio-bad { color: #dc2626; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ strtoupper($tenant->name) }}</h1>
    <div class="sub">
        CNPJ: {{ $tenant->cnpj ?? '—' }}
        &nbsp;·&nbsp; Relatório de Conformidade MROSC
        @if($ciclo) &nbsp;·&nbsp; Ciclo: {{ $ciclo->data_inicio->format('d/m/Y') }} a {{ $ciclo->data_fim->format('d/m/Y') }} @endif
        &nbsp;·&nbsp; Gerado em {{ $gerado_em }}
    </div>
</div>

{{-- Índice --}}
<div class="section-title">Índice de Conformidade MROSC</div>
<table class="kpi-row">
<tr>
    <td>
        <div class="label">Índice MROSC</div>
        <div class="kpi-val" style="color:{{ $indice >= 80 ? '#059669' : ($indice >= 60 ? '#d97706' : '#dc2626') }}">
            {{ number_format($indice, 1, ',', '.') }}%
        </div>
    </td>
    <td>
        <div class="label">Verde</div>
        <div class="kpi-val" style="color:#059669">{{ $verde }}</div>
    </td>
    <td>
        <div class="label">Atenção</div>
        <div class="kpi-val" style="color:#d97706">{{ $amarelo }}</div>
    </td>
    <td>
        <div class="label">Crítico</div>
        <div class="kpi-val" style="color:#dc2626">{{ $vermelho }}</div>
    </td>
    <td>
        <div class="label">Total elegível MROSC</div>
        <div class="kpi-val" style="font-size:14px;">R$ {{ number_format((float)$total_mrosc, 2, ',', '.') }}</div>
    </td>
</tr>
</table>

{{-- Parcerias --}}
@if($grants->isNotEmpty())
<div class="section-title">Parcerias e Instrumentos Jurídicos</div>
<table>
<thead>
<tr>
    <th>Título</th>
    <th>Órgão Concedente</th>
    <th>Instrumento</th>
    <th class="num">Valor (R$)</th>
    <th class="num">Vigência</th>
</tr>
</thead>
<tbody>
@foreach($grants as $grant)
<tr>
    <td>{{ $grant->title }}</td>
    <td style="font-size:9px;">{{ $grant->agency ?? $grant->orgao_concedente_codigo ?? '—' }}</td>
    <td style="font-size:9px;">{{ $grant->numero_instrumento ?? $grant->contract_number ?? '—' }}</td>
    <td class="num">{{ number_format((float)($grant->value ?? 0), 2, ',', '.') }}</td>
    <td class="num" style="font-size:9px;">
        {{ $grant->start_date?->format('d/m/Y') ?? '—' }} a {{ $grant->deadline?->format('d/m/Y') ?? '—' }}
    </td>
</tr>
@endforeach
</tbody>
</table>
@endif

{{-- Execução financeira --}}
@if($transacoes_mrosc->isNotEmpty())
<div class="section-title">Execução Financeira Elegível MROSC</div>
<table>
<thead>
<tr>
    <th>Tipo</th>
    <th>Fonte de Recurso</th>
    <th class="num">Qtd Lançamentos</th>
    <th class="num">Total (R$)</th>
</tr>
</thead>
<tbody>
@foreach($transacoes_mrosc as $linha)
<tr>
    <td>{{ ucfirst($linha->type) }}</td>
    <td>{{ $linha->fonte_recurso ?? '—' }}</td>
    <td class="num">{{ $linha->qtd }}</td>
    <td class="num">{{ number_format((float)$linha->total, 2, ',', '.') }}</td>
</tr>
@endforeach
<tr style="font-weight:700;background:#f8fafc;border-top:1px solid #cbd5e1;">
    <td colspan="3">TOTAL ELEGÍVEL MROSC</td>
    <td class="num">R$ {{ number_format((float)$total_mrosc, 2, ',', '.') }}</td>
</tr>
</tbody>
</table>
@endif

{{-- Metas / etapas --}}
@if($etapas->isNotEmpty())
<div class="section-title">Metas e Etapas de Projeto</div>
<table>
<thead>
<tr>
    <th>Etapa / Meta</th>
    <th class="num">Planejado (R$)</th>
    <th class="num">Executado (R$)</th>
    <th class="num">Desvio (%)</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
@foreach($etapas as $etapa)
@php
$desvio = $etapa->planned_value > 0
    ? abs((float)$etapa->executed_value - (float)$etapa->planned_value) / (float)$etapa->planned_value * 100
    : 0;
@endphp
<tr>
    <td>{{ $etapa->title }}</td>
    <td class="num">{{ number_format((float)$etapa->planned_value, 2, ',', '.') }}</td>
    <td class="num">{{ number_format((float)($etapa->executed_value ?? 0), 2, ',', '.') }}</td>
    <td class="num {{ $desvio > 25 ? 'desvio-bad' : 'desvio-ok' }}">
        {{ number_format($desvio, 1, ',', '.') }}%
    </td>
    <td>
        @if($etapa->status === 'completed')     <span class="badge-verde">Concluída</span>
        @elseif($etapa->status === 'in_progress')<span class="badge-amarelo">Em andamento</span>
        @else                                    <span class="badge-neutro">{{ ucfirst($etapa->status) }}</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
@endif

{{-- Requisitos MROSC --}}
<div class="section-title">Checklist de Requisitos MROSC</div>
<table>
<thead>
<tr>
    <th style="width:16%">Código</th>
    <th style="width:6%">Tipo</th>
    <th>Requisito</th>
    <th style="width:10%">Status</th>
    <th style="width:30%">Detalhe</th>
</tr>
</thead>
<tbody>
@foreach($requisitos as $req)
@php
$aval = $avaliacoes[$req->codigo] ?? null;
$res  = $aval['resultado'] ?? 'nao_aplicavel';
@endphp
<tr>
    <td style="font-family:monospace;font-size:9px;">{{ $req->codigo }}</td>
    <td style="text-align:center;">{{ $req->tipo }}</td>
    <td>{{ $req->titulo }}</td>
    <td>
        @if($res === 'verde')     <span class="badge-verde">Verde</span>
        @elseif($res === 'amarelo')  <span class="badge-amarelo">Atenção</span>
        @elseif($res === 'vermelho') <span class="badge-vermelho">Crítico</span>
        @else                        <span class="badge-neutro">N/A</span>
        @endif
    </td>
    <td style="font-size:9px;color:#64748b;">{{ $aval['detalhe'] ?? '—' }}</td>
</tr>
@endforeach
</tbody>
</table>

<p style="font-size:9px;color:#94a3b8;margin-top:20px;text-align:center;">
    Relatório gerado automaticamente pelo sistema Vivensi em {{ $gerado_em }}.
    Dados baseados nos registros cadastrados na plataforma até a data de geração.
</p>

</body>
</html>
