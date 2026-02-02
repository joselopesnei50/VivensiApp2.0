@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 600px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Recibo de Doação</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Registre a doação para gerar o comprovante.</p>
    </div>

    <form action="{{ url('/ngo/receipts') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Nome do Doador / Empresa</label>
            <input type="text" name="description" list="donorList" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Digite ou selecione o doador..." autocomplete="off">
            <datalist id="donorList">
                @foreach($donors as $donor)
                    <option value="{{ $donor->name }}">
                @endforeach
            </datalist>
            <small style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px; display: block;">* Selecione da lista ou digite um novo.</small>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Valor da Doação (R$)</label>
            <input type="text" name="amount" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.2rem; font-weight: 700; color: #16a34a;" placeholder="0,00">
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Data do Pagamento</label>
            <input type="date" name="date" class="form-control-vivensi" required value="{{ date('Y-m-d') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/receipts') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Gerar Recibo</button>
        </div>
    </form>
</div>
@endsection
