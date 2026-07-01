@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $fmtPct = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.') . '%';
@endphp

<div style="margin-bottom:30px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width:12px; height:3px; border-radius:2px;"></span>
                <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">{{ $project->name }}</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">Relatório de Frequência</h2>
            <p style="color:#64748b; margin:6px 0 0 0;">Período: {{ $from->format('d/m/Y') }} até {{ $to->format('d/m/Y') }}</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ $basePath . '/projects/' . $project->id }}" class="btn-ds btn-ds-ghost" style="text-decoration:none; font-weight:700;">
                <i class="fas fa-arrow-left me-2"></i> Projeto
            </a>
            <a href="{{ $basePath . '/projects/' . $project->id . '/attendance/report.csv?from=' . $from->toDateString() . '&to=' . $to->toDateString() }}" class="btn-ds btn-ds-primary" style="text-decoration:none; font-weight:800;">
                <i class="fas fa-file-csv me-2"></i> Exportar CSV
            </a>
        </div>
    </div>
</div>

{{-- Filtro de periodo --}}
<div class="vivensi-card" style="background:white; padding:20px 24px; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,0.03); margin-bottom:24px;">
    <form method="GET" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
        <div>
            <label style="display:block; font-weight:700; color:#1e293b; font-size:0.8rem; margin-bottom:6px;">De</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" style="padding:10px 14px; border:2px solid #f1f5f9; border-radius:10px; background:#f8fafc; font-weight:600;">
        </div>
        <div>
            <label style="display:block; font-weight:700; color:#1e293b; font-size:0.8rem; margin-bottom:6px;">Até</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" style="padding:10px 14px; border:2px solid #f1f5f9; border-radius:10px; background:#f8fafc; font-weight:600;">
        </div>
        <button type="submit" class="btn-ds btn-ds-primary" style="font-weight:700;">
            <i class="fas fa-filter"></i>&nbsp; Aplicar
        </button>
    </form>
</div>

{{-- KPIs --}}
<div class="row g-3" style="margin-bottom:24px;">
    <div class="col-md-3">
        <div style="background:white; padding:24px; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03);">
            <div style="font-size:0.7rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1.5px;">Sessões no período</div>
            <div style="font-size:2rem; font-weight:900; color:#1e293b; margin-top:6px;">{{ $totalSessions }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:white; padding:24px; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03);">
            <div style="font-size:0.7rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1.5px;">Presença média</div>
            <div style="font-size:2rem; font-weight:900; color:#10b981; margin-top:6px;">{{ $fmtPct($avgPct) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:white; padding:24px; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03);">
            <div style="font-size:0.7rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1.5px;">Alunos em risco</div>
            <div style="font-size:2rem; font-weight:900; color:{{ $atRiskCount > 0 ? '#dc2626' : '#1e293b' }}; margin-top:6px;">{{ $atRiskCount }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div style="background:white; padding:24px; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03);">
            <div style="font-size:0.7rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1.5px;">Check-ins via link</div>
            <div style="font-size:2rem; font-weight:900; color:#4f46e5; margin-top:6px;">{{ $fmtPct($publicPct) }}</div>
        </div>
    </div>
</div>

{{-- Tabela por aluno --}}
<div class="vivensi-card" style="background:white; padding:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.03); overflow:hidden;">
    @if(empty($rows))
        <div style="padding:60px 30px; text-align:center; color:#64748b;">
            <i class="fas fa-chart-bar" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px;"></i>
            <p style="margin:0; font-weight:600;">Nenhuma presença registrada no período selecionado.</p>
        </div>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="text-align:left; padding:14px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Aluno</th>
                    <th style="text-align:center; padding:14px 12px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Sessões</th>
                    <th style="text-align:center; padding:14px 12px; font-size:0.72rem; font-weight:800; color:#10b981; text-transform:uppercase; letter-spacing:1px;">Pres.</th>
                    <th style="text-align:center; padding:14px 12px; font-size:0.72rem; font-weight:800; color:#dc2626; text-transform:uppercase; letter-spacing:1px;">Faltas</th>
                    <th style="text-align:center; padding:14px 12px; font-size:0.72rem; font-weight:800; color:#f59e0b; text-transform:uppercase; letter-spacing:1px;">Just.</th>
                    <th style="text-align:right; padding:14px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">% Presença</th>
                    <th style="text-align:left; padding:14px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Sinal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr style="border-top:1px solid #f1f5f9; {{ $r['at_risk'] ? 'background:#fef2f2;' : '' }}">
                        <td style="padding:12px 20px; font-weight:700; color:#1e293b;">
                            {{ $r['name'] }}
                            @if($r['inactive'])
                                <span style="margin-left:8px; padding:2px 8px; border-radius:6px; font-size:0.65rem; background:#fef2f2; color:#b91c1c; font-weight:700;">inativo</span>
                            @endif
                        </td>
                        <td style="padding:12px; text-align:center; color:#64748b;">{{ $r['eligible'] }}</td>
                        <td style="padding:12px; text-align:center; font-weight:800; color:#10b981;">{{ $r['presences'] }}</td>
                        <td style="padding:12px; text-align:center; font-weight:800; color:#dc2626;">{{ $r['absences'] }}</td>
                        <td style="padding:12px; text-align:center; font-weight:800; color:#f59e0b;">{{ $r['justified'] }}</td>
                        <td style="padding:12px 20px; text-align:right; font-weight:900; color:#1e293b;">{{ $fmtPct($r['pct']) }}</td>
                        <td style="padding:12px 20px; text-align:left;">
                            @if($r['risk_consec'])
                                <span title="{{ $r['consecutive'] }} faltas consecutivas" style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:0.7rem; background:#fee2e2; color:#991b1b; font-weight:800; margin-right:4px;">
                                    <i class="fas fa-fire"></i>&nbsp; {{ $r['consecutive'] }} seguidas
                                </span>
                            @endif
                            @if($r['risk_percent'])
                                <span title="% de faltas ≥ 30%" style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:0.7rem; background:#fef3c7; color:#92400e; font-weight:800;">
                                    <i class="fas fa-percentage"></i>&nbsp; faltas altas
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Sessoes do periodo com botao de export CSV por sessao --}}
@if($sessions->isNotEmpty())
    <div style="margin-top:32px;">
        <h3 style="color:#1e293b; font-weight:900; font-size:1.1rem; margin-bottom:14px;">Sessões do período — exportar chamada individual</h3>
        <div class="vivensi-card" style="background:white; padding:0; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03); overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="text-align:left; padding:12px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Data</th>
                        <th style="text-align:left; padding:12px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Sessão</th>
                        <th style="text-align:right; padding:12px 20px; font-size:0.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Exportar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $s)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:10px 20px; font-weight:700; color:#1e293b;">{{ $s->date->format('d/m/Y') }}</td>
                            <td style="padding:10px 20px; color:#334155;">{{ $s->title }}</td>
                            <td style="padding:10px 20px; text-align:right;">
                                <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $s->id . '/export.csv' }}" style="color:#4f46e5; text-decoration:none; font-weight:800; font-size:0.85rem;">
                                    <i class="fas fa-download"></i>&nbsp; CSV
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
