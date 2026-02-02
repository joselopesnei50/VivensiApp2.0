@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #2c3e50;">Revisão da Importação</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">Verifique as transações antes de confirmar.</p>
</div>

<form action="{{ url('/ngo/reconciliation/store') }}" method="POST">
    @csrf
    <div class="vivensi-card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 15px; width: 40px;"></th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Data</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Descrição (Banco)</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Categoria (Sugerida)</th>
                    <th style="padding: 15px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Valor</th>
                    <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matches as $index => $match)
                <tr style="border-bottom: 1px solid #f1f5f9; background: {{ $match['system'] ? '#f0fdf4' : 'white' }};">
                    <td style="padding: 15px; text-align: center;">
                        @if(!$match['system'])
                            <input type="checkbox" name="transactions[{{$index}}][checked]" value="1" checked>
                            <input type="hidden" name="transactions[{{$index}}][date]" value="{{ $match['ofx']['date'] }}">
                            <input type="hidden" name="transactions[{{$index}}][description]" value="{{ $match['ofx']['description'] }}">
                            <input type="hidden" name="transactions[{{$index}}][amount]" value="{{ $match['ofx']['amount'] }}">
                            <input type="hidden" name="transactions[{{$index}}][type]" value="{{ $match['ofx']['type'] }}">
                        @else
                            <i class="fas fa-check-circle" style="color: #16a34a;" title="Já existe no sistema"></i>
                        @endif
                    </td>
                    <td style="padding: 15px; color: #475569;">
                        {{ \Carbon\Carbon::parse($match['ofx']['date'])->format('d/m/Y') }}
                    </td>
                    <td style="padding: 15px; color: #1e293b; max-width: 250px;">
                        {{ $match['ofx']['description'] }}
                    </td>
                    <td style="padding: 15px;">
                        @if(!$match['system'])
                            <select name="transactions[{{$index}}][category_id]" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; background-color: #f8fafc;">
                                <option value="1">Sem Categoria</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (isset($match['suggested_category_id']) && $match['suggested_category_id'] == $cat->id) ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <span class="badge" style="background: #e2e8f0; color: #475569; padding: 5px 10px; border-radius: 12px; font-size: 0.75rem;">Já classificado</span>
                        @endif
                    </td>
                    <td style="padding: 15px; text-align: right; font-weight: 600; color: {{ $match['ofx']['type'] == 'expense' ? '#ef4444' : '#16a34a' }};">
                        R$ {{ number_format($match['ofx']['amount'], 2, ',', '.') }}
                    </td>
                    <td style="padding: 15px; text-align: center; font-size: 0.8rem;">
                        @if($match['system'])
                            <span style="color: #15803d; font-weight: 700;">CONCILIADO</span>
                            <br><span style="font-size: 0.7rem; color: #64748b;">Encontrado ID #{{ $match['system']->id }}</span>
                        @else
                            <span style="color: #0369a1; font-weight: 700;">NOVO REGISTRO</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="padding: 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <a href="{{ url('/ngo/reconciliation') }}" style="margin-right: 20px; color: #64748b; text-decoration: none;">Cancelar</a>
            <button type="submit" class="btn-premium">
                <i class="fas fa-check me-2"></i> Importar Selecionados
            </button>
        </div>
    </div>
</form>
@endsection
