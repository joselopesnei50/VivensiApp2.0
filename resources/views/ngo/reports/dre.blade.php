@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Demonstrativo do Resultado do Exercício (D.R.E.)</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">
            Relatório contábil gerencial —
            {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
        </p>
    </div>
    <form action="{{ url('/ngo/reports/dre') }}" method="GET" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <select name="year" id="dre-year-select" class="form-control-vivensi" style="padding: 8px; width: 90px;" onchange="dreYearChanged(this.value)">
            @for($y = date('Y'); $y >= date('Y')-4; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <input type="date" name="from" id="dre-from" value="{{ $from }}" class="form-control-vivensi" style="padding: 8px;">
        <span style="color:#64748b; font-weight:700; font-size:.85rem;">até</span>
        <input type="date" name="to" id="dre-to" value="{{ $to }}" class="form-control-vivensi" style="padding: 8px;">
        <select name="category_id" id="dre-category" class="form-control-vivensi" style="padding: 8px; min-width: 160px;">
            <option value="">Todas as categorias</option>
            <optgroup label="Receitas">
                @foreach($categories->where('type', 'income') as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Despesas">
                @foreach($categories->where('type', 'expense') as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </optgroup>
        </select>
        <button type="submit" class="btn-ds btn-ds-outline" style="padding: 8px 14px;">Filtrar</button>
        <button id="btn-dre-pdf" class="btn-ds btn-ds-outline" style="padding: 8px 15px;" onclick="generateDrePdf(this); return false;">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <a id="btn-dre-csv" href="{{ url('/ngo/reports/dre/export') }}?from={{ $from }}&to={{ $to }}{{ $categoryId ? '&category_id='.$categoryId : '' }}" class="btn-ds btn-ds-outline" style="padding: 8px 15px;">
            <i class="fas fa-file-csv"></i> CSV
        </a>
        <button type="button" onclick="window.print()" class="btn-premium" style="padding: 8px 15px;">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </form>
</div>

<div class="grid-2" style="margin-bottom: 18px;">
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Receitas (Ano)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">R$ {{ number_format($totalIncome, 2, ',', '.') }}</h3>
        <p style="margin:0; font-size: .9rem; color:#16a34a; font-weight:800;">Base: pagamentos com status “paid”.</p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #ef4444;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Despesas (Ano)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">R$ {{ number_format($totalExpense, 2, ',', '.') }}</h3>
        <p style="margin:0; font-size: .9rem; color:#ef4444; font-weight:800;">Total de saídas pagas no período.</p>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 18px;">
    <div style="display:flex; justify-content: space-between; align-items: end; gap: 10px; flex-wrap: wrap;">
        <div>
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Resultado do Exercício</div>
            <div style="margin-top: 6px; font-size: 2rem; font-weight: 900; color: {{ $result >= 0 ? '#16a34a' : '#ef4444' }};">
                R$ {{ number_format($result, 2, ',', '.') }}
            </div>
        </div>
        <div style="color:#64748b; font-weight:800;">
            Gráfico mensal (jan–dez)
        </div>
    </div>

    @php
        $vals = collect($chartData ?? [])->map(fn($v) => (float) $v);
        $maxAbs = max(1, (float) $vals->map(fn($v) => abs($v))->max());
    @endphp
    <div style="margin-top: 14px; display:grid; grid-template-columns: repeat(12, 1fr); gap: 6px; align-items:end;">
        @for($m=1; $m<=12; $m++)
            @php
                $v = (float) (($chartData[$m] ?? 0) ?: 0);
                $h = (int) round((abs($v) / $maxAbs) * 90) + 6; // px
                $color = $v >= 0 ? 'linear-gradient(180deg, rgba(34,197,94,.95) 0%, rgba(16,185,129,.85) 100%)'
                                 : 'linear-gradient(180deg, rgba(239,68,68,.95) 0%, rgba(220,38,38,.85) 100%)';
                $labels = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                $label = $labels[$m-1] ?? (string) $m;
            @endphp
            <div title="{{ $m }}: R$ {{ number_format($v, 2, ',', '.') }}" style="display:flex; flex-direction: column; align-items:center; gap: 6px;">
                <div style="width: 100%; height: {{ $h }}px; border-radius: 10px; background: {{ $color }}; box-shadow: 0 10px 25px rgba(15,23,42,.08);"></div>
                <div style="font-size:.72rem; font-weight:900; color:#94a3b8; text-transform:uppercase;">{{ $label }}</div>
            </div>
        @endfor
    </div>
    <div style="margin-top: 10px; color:#94a3b8; font-size:.85rem;">
        Verde = superávit · Vermelho = déficit
    </div>
</div>

<div class="vivensi-card" style="max-width: 900px; margin: 0 auto; padding: 40px; font-family: 'Courier New', Courier, monospace;">
    <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px;">
        <h3 style="margin: 0; text-transform: uppercase;">{{ auth()->user()->tenant->brand_name ?? 'ORGANIZAÇÃO SOCIAL' }}</h3>
        <p style="margin: 5px 0 0; font-size: 0.9rem;">D.R.E. - Período: {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</p>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <!-- Receitas -->
        <tr style="font-weight: bold; background: #f1f5f9;">
            <td style="padding: 10px;">(+) RECEITAS OPERACIONAIS BRUTAS</td>
            <td style="text-align: right; padding: 10px;">R$ {{ number_format($totalIncome, 2, ',', '.') }}</td>
        </tr>
        @foreach($incomes as $inc)
        <tr>
            <td style="padding: 5px 10px 5px 30px; color: #334155;">{{ $inc['name'] }}</td>
            <td style="text-align: right; padding: 5px 10px; color: #334155;">{{ number_format($inc['value'], 2, ',', '.') }}</td>
        </tr>
        @endforeach

        <!-- Separador -->
        <tr><td colspan="2" style="padding: 10px;"></td></tr>

        <!-- Despesas -->
        <tr style="font-weight: bold; background: #fff1f2;">
            <td style="padding: 10px;">(-) CUSTOS E DESPESAS OPERACIONAIS</td>
            <td style="text-align: right; padding: 10px;">(R$ {{ number_format($totalExpense, 2, ',', '.') }})</td>
        </tr>
        @foreach($expenses as $exp)
        <tr>
            <td style="padding: 5px 10px 5px 30px; color: #334155;">{{ $exp['name'] }}</td>
            <td style="text-align: right; padding: 5px 10px; color: #334155;">(R$ {{ number_format($exp['value'], 2, ',', '.') }})</td>
        </tr>
        @endforeach

        <!-- Resultado -->
        <tr style="border-top: 2px solid #000; font-size: 1.1rem; font-weight: bold;">
            <td style="padding: 20px 10px;">(=) RESULTADO DO EXERCÍCIO (SUPERÁVIT/DÉFICIT)</td>
            <td style="text-align: right; padding: 20px 10px; color: {{ $result >= 0 ? '#16a34a' : '#ef4444' }};">
                R$ {{ number_format($result, 2, ',', '.') }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 50px; display: flex; justify-content: space-between; padding-top: 50px;">
        <div style="text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 10px;">
            Presidente / Diretor
        </div>
        <div style="text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 10px;">
            Contador Resp.
        </div>
    </div>
</div>

<style>
@media print {
    .btn-premium, .header-page form, #sidebar, .header-main { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; border: none; }
    .vivensi-card { box-shadow: none; border: none; }
}
</style>
@endsection

@push('scripts')
<script>
let _drePdfPollBtn = null;
let _drePdfPollOrig = null;
let _drePdfStatusUrl = null;

function dreYearChanged(year) {
    document.getElementById('dre-from').value = year + '-01-01';
    document.getElementById('dre-to').value   = year + '-12-31';
    document.getElementById('dre-from').closest('form').submit();
}

function pollDrePdf(attempts) {
    attempts = attempts || 0;
    if (attempts > 36) {
        alert('A geração do PDF demorou demais. Tente novamente.');
        if (_drePdfPollBtn) { _drePdfPollBtn.innerHTML = _drePdfPollOrig; _drePdfPollBtn.disabled = false; }
        return;
    }
    fetch(_drePdfStatusUrl).then(r => r.json()).then(function(data) {
        if (data.status === 'done' && data.download_url) {
            window.location.href = data.download_url;
            if (_drePdfPollBtn) { _drePdfPollBtn.innerHTML = _drePdfPollOrig; _drePdfPollBtn.disabled = false; }
        } else if (data.status === 'failed') {
            alert('Falha ao gerar o PDF. Tente novamente.');
            if (_drePdfPollBtn) { _drePdfPollBtn.innerHTML = _drePdfPollOrig; _drePdfPollBtn.disabled = false; }
        } else {
            setTimeout(function() { pollDrePdf(attempts + 1); }, 5000);
        }
    }).catch(function() {
        setTimeout(function() { pollDrePdf(attempts + 1); }, 8000);
    });
}

async function generateDrePdf(btn) {
    _drePdfPollOrig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
    btn.disabled = true;
    _drePdfPollBtn = btn;
    const from     = document.getElementById('dre-from').value;
    const to       = document.getElementById('dre-to').value;
    const catId    = document.getElementById('dre-category').value;
    const year     = document.getElementById('dre-year-select').value;
    const params   = new URLSearchParams({ year, from, to, ...(catId ? { category_id: catId } : {}) });
    try {
        const resp = await fetch('{{ url("/ngo/reports/dre/pdf") }}?' + params.toString(), {
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
        });
        const data = await resp.json();
        if (data.status_url) {
            _drePdfStatusUrl = data.status_url;
            pollDrePdf(0);
        } else {
            alert('Erro ao iniciar geração do PDF.');
            btn.innerHTML = _drePdfPollOrig; btn.disabled = false;
        }
    } catch(e) {
        alert('Falha na comunicação com o servidor.');
        btn.innerHTML = _drePdfPollOrig; btn.disabled = false;
    }
}
</script>
@endpush
