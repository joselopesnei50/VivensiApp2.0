<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Dossiê CEBAS</title>
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
.kpi-val { font-size: 20px; font-weight: 900; }
.eixo-header td { background: #0f172a; color: #fff; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; padding: 5px 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ strtoupper($tenant->name) }}</h1>
    <div class="sub">
        CNPJ: {{ $tenant->cnpj ?? '—' }}
        @if($tenant->cnas_numero) · CNAS nº {{ $tenant->cnas_numero }} @endif
        @if($tenant->cnas_validade) (val. {{ $tenant->cnas_validade->format('d/m/Y') }}) @endif
        @if($tenant->area_atuacao_cebas) · Área: {{ ucfirst(str_replace('_', ' ', $tenant->area_atuacao_cebas)) }} @endif
        &nbsp;·&nbsp; Dossiê CEBAS gerado em {{ $gerado_em }}
    </div>
</div>

{{-- Índice geral --}}
<div class="section-title">Índice de Conformidade CEBAS</div>
<table class="kpi-row">
<tr>
    <td>
        <div class="label">Índice Geral</div>
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
</tr>
</table>

{{-- Requisitos por eixo --}}
<div class="section-title">Requisitos CEBAS por Eixo</div>
@php
$eixoLabels = ['cebas_geral' => 'CEBAS Geral', 'cebas_as' => 'CEBAS Assistência Social', 'cebas_saude' => 'CEBAS Saúde', 'cebas_educacao' => 'CEBAS Educação'];
$porEixo = $requisitos->groupBy('eixo');
@endphp

<table>
<thead>
<tr>
    <th style="width:14%">Código</th>
    <th style="width:6%">Tipo</th>
    <th>Requisito</th>
    <th style="width:10%">Status</th>
    <th class="num" style="width:9%">Calculado</th>
    <th class="num" style="width:9%">Mínimo</th>
</tr>
</thead>
<tbody>
@foreach($porEixo as $eixo => $reqs)
<tr class="eixo-header"><td colspan="6">{{ $eixoLabels[$eixo] ?? $eixo }}</td></tr>
@foreach($reqs as $req)
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
    <td class="num">
        @if($aval && $aval['valor_calculado'] !== null)
            {{ number_format((float)$aval['valor_calculado'], 1, ',', '.') }}{{ $aval['unidade'] ?? '' }}
        @else —
        @endif
    </td>
    <td class="num">
        @if($aval && $aval['threshold'])
            {{ number_format((float)$aval['threshold'], 1, ',', '.') }}{{ $aval['unidade'] ?? '' }}
        @else —
        @endif
    </td>
</tr>
@if($aval && isset($aval['detalhe']))
<tr><td colspan="6" style="color:#64748b;font-size:9px;padding:1px 8px 5px;border-bottom:none;">↳ {{ $aval['detalhe'] }}</td></tr>
@endif
@endforeach
@endforeach
</tbody>
</table>

{{-- Documentos --}}
@if($documentos->isNotEmpty())
<div class="section-title">Documentos Comprobatórios (Tipo B)</div>
<table>
<thead>
<tr>
    <th>Tipo de Documento</th>
    <th>Arquivo</th>
    <th class="num">Válido até</th>
    <th class="num">Situação</th>
</tr>
</thead>
<tbody>
@foreach($documentos as $doc)
@php $diasDoc = $doc->valid_until ? (int) now()->diffInDays($doc->valid_until, false) : null; @endphp
<tr>
    <td>{{ $doc->tipo_documento }}</td>
    <td style="color:#64748b;font-size:9px;">{{ $doc->original_name }}</td>
    <td class="num">{{ $doc->valid_until?->format('d/m/Y') ?? '—' }}</td>
    <td class="num">
        @if($diasDoc === null) <span class="badge-verde">Permanente</span>
        @elseif($diasDoc < 0)  <span class="badge-vermelho">Vencido</span>
        @elseif($diasDoc <= 30)<span class="badge-amarelo">{{ $diasDoc }}d</span>
        @else                  <span class="badge-verde">Válido</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
@endif

<p style="font-size:9px;color:#94a3b8;margin-top:20px;text-align:center;">
    Dossiê gerado automaticamente pelo sistema Vivensi em {{ $gerado_em }}.
    As informações refletem o estado atual dos dados cadastrados na plataforma.
</p>

</body>
</html>
