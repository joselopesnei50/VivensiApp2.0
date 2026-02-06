@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 22px; display:flex; justify-content: space-between; align-items:center; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Indicadores Sociais</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Visão rápida de atendimentos, equipe e famílias (últimos 90 dias por padrão).</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a class="btn-premium" style="background:#111827;" href="{{ url('/ngo/beneficiaries') }}"><i class="fas fa-arrow-left"></i> Voltar</a>
        <a class="btn-premium" style="background:#4f46e5;" href="{{ url('/ngo/beneficiaries/attendances/export') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV Atendimentos (lote)</a>
        <a class="btn-premium" style="background:#16a34a;" href="{{ url('/ngo/beneficiaries/reports/annual') }}"><i class="fas fa-file-alt"></i> Relatório anual</a>
        <button class="btn-premium" style="background:#f1f5f9; color:#0f172a;" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/beneficiaries/insights') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
        <div class="form-group" style="margin:0;">
            <label>De</label>
            <input type="date" name="from" class="form-control-vivensi" value="{{ $from ?? '' }}">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Até</label>
            <input type="date" name="to" class="form-control-vivensi" value="{{ $to ?? '' }}">
        </div>
        <div style="display:flex; gap: 10px;">
            <button type="submit" class="btn-premium"><i class="fas fa-filter"></i> Aplicar</button>
            <a class="btn-premium" style="background:#f1f5f9; color:#0f172a;" href="{{ url('/ngo/beneficiaries/insights') }}">Reset</a>
        </div>
        <div style="color:#64748b; font-weight:800; margin-left:auto;">
            Período: {{ $fromDt->format('d/m/Y') }} → {{ $toDt->format('d/m/Y') }}
        </div>
    </form>
</div>

@php
    $k = $kpis ?? [];
    $maxMonth = 0;
    foreach(($monthly ?? []) as $v) { if ((int) $v > $maxMonth) $maxMonth = (int) $v; }
    $maxMonth = max($maxMonth, 1);
@endphp

<div class="grid-2" style="margin-bottom: 16px; grid-template-columns: 1fr 1fr; gap: 16px;">
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Famílias / Beneficiários</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format((int) ($k['totalBenef'] ?? 0)) }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">
            Ativos: <strong>{{ number_format((int) ($k['activeBenef'] ?? 0)) }}</strong> · Inativos: <strong>{{ number_format((int) ($k['inactiveBenef'] ?? 0)) }}</strong> · Graduados: <strong>{{ number_format((int) ($k['graduatedBenef'] ?? 0)) }}</strong>
        </p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Atendimentos (período)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format((int) ($k['totalAttendances'] ?? 0)) }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">
            Famílias atendidas (únicas): <strong>{{ number_format((int) ($k['uniqueBeneficiariesAttended'] ?? 0)) }}</strong>
        </p>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 10px 0; color:#0f172a;">Atendimentos por mês (últimos 12 meses)</h3>
    <div style="display:flex; gap: 10px; align-items:flex-end; height: 140px;">
        @foreach(($monthly ?? []) as $label => $val)
            @php $h = ((int) $val / $maxMonth) * 120; @endphp
            <div style="flex:1; min-width: 22px; text-align:center;">
                <div title="{{ $label }}: {{ (int) $val }}" style="height: {{ max(2, (int) $h) }}px; background:#4f46e5; border-radius:8px 8px 0 0;"></div>
                <div style="font-size: 10px; color:#64748b; margin-top:6px;">{{ substr($label, 5, 2) }}/{{ substr($label, 2, 2) }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid-2" style="gap: 16px;">
    <div class="vivensi-card">
        <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top tipos de atendimento</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Tipo</th>
                    <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Qtde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topTypes as $r)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><strong>{{ $r->type }}</strong></td>
                        <td style="padding:10px; text-align:right;">{{ number_format((int) $r->c) }}</td>
                    </tr>
                @endforeach
                @if($topTypes->count()===0)
                    <tr><td colspan="2" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados no período.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="vivensi-card">
        <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top equipe (por registros)</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Usuário</th>
                    <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Qtde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topUsers as $r)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><strong>{{ $r->name }}</strong></td>
                        <td style="padding:10px; text-align:right;">{{ number_format((int) $r->c) }}</td>
                    </tr>
                @endforeach
                @if($topUsers->count()===0)
                    <tr><td colspan="2" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados no período.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="vivensi-card" style="margin-top: 16px;">
    <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top famílias (por atendimentos)</h3>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align:left; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Beneficiário</th>
                <th style="text-align:center; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Status</th>
                <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Qtde</th>
                <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Abrir</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topFamilies as $r)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px;"><strong>{{ $r->name }}</strong></td>
                    <td style="padding:10px; text-align:center;">
                        <span style="background:#f1f5f9; padding:2px 8px; border-radius: 999px; font-weight: 800; font-size: 11px;">
                            {{ strtoupper($r->status ?? '—') }}
                        </span>
                    </td>
                    <td style="padding:10px; text-align:right;">{{ number_format((int) $r->c) }}</td>
                    <td style="padding:10px; text-align:right;">
                        <a class="btn-premium" style="font-size:.85rem; padding: 6px 10px; background:#f1f5f9; color:#0f172a;" href="{{ url('/ngo/beneficiaries/' . $r->id) }}">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            @if($topFamilies->count()===0)
                <tr><td colspan="4" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados no período.</td></tr>
            @endif
        </tbody>
    </table>
</div>
@endsection

