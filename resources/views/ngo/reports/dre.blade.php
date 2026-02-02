@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Demonstrativo do Resultado do Exercício (D.R.E.)</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Relatório contábil gerencial - Ano Base {{ $year }}</p>
    </div>
    <form action="{{ url('/ngo/reports/dre') }}" method="GET" style="display: flex; gap: 10px;">
        <select name="year" class="form-control-vivensi" style="padding: 8px;" onchange="this.form.submit()">
            @for($y = date('Y'); $y >= date('Y')-4; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        <button type="button" onclick="window.print()" class="btn-premium" style="background: #475569; padding: 8px 15px;">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </form>
</div>

<div class="vivensi-card" style="max-width: 900px; margin: 0 auto; padding: 40px; font-family: 'Courier New', Courier, monospace;">
    <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px;">
        <h3 style="margin: 0; text-transform: uppercase;">{{ auth()->user()->tenant_id == 1 ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL' }}</h3>
        <p style="margin: 5px 0 0; font-size: 0.9rem;">D.R.E. - Período: 01/01/{{ $year }} a 31/12/{{ $year }}</p>
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
