@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 22px; display:flex; justify-content: space-between; align-items:center; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Relatório Anual (Prestação de Contas)</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Resumo anual do Social: atendimentos, tipos, equipe e famílias.</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a class="btn-ds btn-ds-ghost" href="{{ url('/ngo/beneficiaries') }}"><i class="fas fa-arrow-left"></i> Voltar</a>
        <button id="btn-pdf" class="btn-ds btn-ds-outline" onclick="generateAnnualPdf('annual')"><i class="fas fa-file-pdf"></i> Baixar PDF</button>
        <button id="btn-pdf-appendix" class="btn-ds btn-ds-outline" onclick="generateAnnualPdf('appendix')"><i class="fas fa-file-pdf"></i> PDF + anexos</button>
        <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual/export') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV detalhado</a>
        <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual/export-grouped') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV agrupado (técnico)</a>
        <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual/export-grouped-simple') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV agrupado</a>
        <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual/export-pivot-type') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV Pivot (tipo)</a>
        <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual/export-pivot-user') . '?' . http_build_query(request()->query()) }}"><i class="fas fa-file-csv"></i> CSV Pivot (equipe)</a>
        <button class="btn-ds btn-ds-outline" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/beneficiaries/reports/annual') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
        <div class="form-group" style="margin:0;">
            <label>Ano</label>
            <input type="number" name="year" class="form-control-vivensi" value="{{ (int) $year }}" min="2000" max="{{ date('Y') + 2 }}" style="max-width: 140px;">
        </div>
        <div class="form-group" style="margin:0; min-width: 220px;">
            <label>Status do Beneficiário</label>
            <select name="benef_status" class="form-control-vivensi">
                <option value="">Todos</option>
                @foreach($statuses as $k => $label)
                    <option value="{{ $k }}" @if(($benefStatus ?? '')===$k) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0; min-width: 240px;">
            <label>Tipo</label>
            <select name="type" class="form-control-vivensi">
                <option value="">Todos</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" @if(($type ?? '')===$t) selected @endif>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button class="btn-premium" type="submit"><i class="fas fa-filter"></i> Aplicar</button>
            <a class="btn-ds btn-ds-outline" href="{{ url('/ngo/beneficiaries/reports/annual') }}">Reset</a>
        </div>
        <div style="color:#64748b; font-weight:800; margin-left:auto;">
            Período: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </div>
    </form>
</div>

@php
    $maxMonth = max(1, max($monthly));
@endphp

<div class="grid-2" style="margin-bottom: 16px; grid-template-columns: 1fr 1fr; gap: 16px;">
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Atendimentos (ano)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format((int) $totalAttendances) }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">
            Famílias únicas atendidas: <strong>{{ number_format((int) $uniqueFamilies) }}</strong>
        </p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Contexto</p>
        <h3 style="margin: 10px 0; font-size: 1.1rem;">{{ $orgName }}</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin:0;">Gerado em {{ $generatedAt }}</p>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 16px;">
    <h3 style="margin: 0 0 10px 0; color:#0f172a;">Atendimentos por mês ({{ (int) $year }})</h3>
    <div style="display:flex; gap: 10px; align-items:flex-end; height: 140px;">
        @for($m=1; $m<=12; $m++)
            @php $val = (int) ($monthly[$m] ?? 0); $h = ($val / $maxMonth) * 120; @endphp
            <div style="flex:1; min-width: 22px; text-align:center;">
                <div title="{{ $m }}/{{ $year }}: {{ $val }}" style="height: {{ max(2, (int) $h) }}px; background:#4f46e5; border-radius:8px 8px 0 0;"></div>
                <div style="font-size: 10px; color:#64748b; margin-top:6px;">{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</div>
            </div>
        @endfor
    </div>
</div>

<div class="grid-2" style="gap: 16px;">
    <div class="vivensi-card">
        <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top tipos</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Tipo</th>
                    <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Qtde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byType as $r)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><strong>{{ $r->type }}</strong></td>
                        <td style="padding:10px; text-align:right;">{{ number_format((int) $r->c) }}</td>
                    </tr>
                @endforeach
                @if($byType->count()===0)
                    <tr><td colspan="2" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="vivensi-card">
        <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top equipe</h3>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Usuário</th>
                    <th style="text-align:right; padding:10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#64748b; text-transform:uppercase; font-size:11px;">Qtde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byUser as $r)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px;"><strong>{{ $r->name }}</strong></td>
                        <td style="padding:10px; text-align:right;">{{ number_format((int) $r->c) }}</td>
                    </tr>
                @endforeach
                @if($byUser->count()===0)
                    <tr><td colspan="2" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="vivensi-card" style="margin-top: 16px;">
    <h3 style="margin: 0 0 10px 0; color:#0f172a;">Top famílias</h3>
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
                        <a class="btn-ds btn-ds-outline" style="font-size:.85rem; padding: 6px 10px;" href="{{ url('/ngo/beneficiaries/' . $r->id) }}">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            @if($topFamilies->count()===0)
                <tr><td colspan="4" style="padding:12px; text-align:center; color:#94a3b8;">Sem dados.</td></tr>
            @endif
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
const _annualPdfQuery = '{{ http_build_query(request()->query()) }}';
const _annualPdfBaseUrl  = '{{ url("/ngo/beneficiaries/reports/annual/pdf") }}';
const _annualPdfAppendixUrl = '{{ url("/ngo/beneficiaries/reports/annual/pdf-appendix") }}';
let _annualPdfPollBtn = null;
let _annualPdfPollOrig = null;
let _annualPdfStatusUrl = null;

function pollAnnualPdf(attempts) {
    attempts = attempts || 0;
    if (attempts > 36) {
        alert('A geração do PDF demorou demais. Tente novamente.');
        if (_annualPdfPollBtn) { _annualPdfPollBtn.innerHTML = _annualPdfPollOrig; _annualPdfPollBtn.disabled = false; }
        return;
    }
    fetch(_annualPdfStatusUrl).then(r => r.json()).then(function(data) {
        if (data.status === 'done' && data.download_url) {
            window.location.href = data.download_url;
            if (_annualPdfPollBtn) { _annualPdfPollBtn.innerHTML = _annualPdfPollOrig; _annualPdfPollBtn.disabled = false; }
        } else if (data.status === 'failed') {
            alert('Falha ao gerar o PDF. Tente novamente.');
            if (_annualPdfPollBtn) { _annualPdfPollBtn.innerHTML = _annualPdfPollOrig; _annualPdfPollBtn.disabled = false; }
        } else {
            setTimeout(function() { pollAnnualPdf(attempts + 1); }, 5000);
        }
    }).catch(function() {
        setTimeout(function() { pollAnnualPdf(attempts + 1); }, 8000);
    });
}

async function generateAnnualPdf(variant) {
    const btnId = variant === 'appendix' ? 'btn-pdf-appendix' : 'btn-pdf';
    const btn = document.getElementById(btnId);
    _annualPdfPollOrig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
    btn.disabled = true;
    _annualPdfPollBtn = btn;
    const baseUrl = variant === 'appendix' ? _annualPdfAppendixUrl : _annualPdfBaseUrl;
    const url = baseUrl + (_annualPdfQuery ? '?' + _annualPdfQuery : '');
    try {
        const resp = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
        const data = await resp.json();
        if (data.status_url) {
            _annualPdfStatusUrl = data.status_url;
            pollAnnualPdf(0);
        } else {
            alert('Erro ao iniciar geração do PDF.');
            btn.innerHTML = _annualPdfPollOrig; btn.disabled = false;
        }
    } catch(e) {
        alert('Falha na comunicação com o servidor.');
        btn.innerHTML = _annualPdfPollOrig; btn.disabled = false;
    }
}
</script>
@endpush

