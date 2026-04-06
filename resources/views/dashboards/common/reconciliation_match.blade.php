@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Conciliação</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Validar Transações</h2>
    </div>
</div>

<form action="{{ url('/personal/reconciliation/store') }}" method="POST">
    @csrf
    <div class="vivensi-card" style="padding: 25px;">
        <table class="table">
            <thead>
                <tr style="font-size: 0.8rem; text-transform: uppercase; color: #64748b;">
                    <th>Importar?</th>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matches as $index => $m)
                <tr style="{{ $m['system'] ? 'opacity: 0.5; background: #f1f5f9;' : '' }}">
                    <td>
                        <input type="checkbox" name="transactions[{{$index}}][checked]" value="1" {{ $m['system'] ? '' : 'checked' }}>
                        @if($m['system']) <span class="badge bg-warning text-dark">Já existe</span> @endif
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($m['ofx']['date'])->format('d/m/Y') }}
                        <input type="hidden" name="transactions[{{$index}}][date]" value="{{$m['ofx']['date']}}">
                    </td>
                    <td>
                        <input type="text" name="transactions[{{$index}}][description]" value="{{$m['ofx']['description']}}" class="form-control form-control-sm border-0 bg-transparent">
                    </td>
                    <td class="fw-bold">
                        R$ {{ number_format($m['ofx']['amount'], 2, ',', '.') }}
                        <input type="hidden" name="transactions[{{$index}}][amount]" value="{{$m['ofx']['amount']}}">
                    </td>
                    <td>
                        <span class="badge {{ $m['ofx']['type'] == 'income' ? 'bg-success' : 'bg-danger' }}">
                            {{ $m['ofx']['type'] == 'income' ? 'RECEITA' : 'DESPESA' }}
                        </span>
                        <input type="hidden" name="transactions[{{$index}}][type]" value="{{$m['ofx']['type']}}">
                    </td>
                    <td>
                        <select name="transactions[{{$index}}][category_id]" class="form-select form-select-sm">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="mt-4 text-end">
            <button type="submit" class="btn-premium">Confirmar Importação</button>
        </div>
    </div>
</form>
@endsection
