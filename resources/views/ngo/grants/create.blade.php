@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Convênio / Edital</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Cadastre os dados oficiais do termo financiador.</p>
    </div>

    <form action="{{ url('/ngo/grants') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Título / Objeto do Convênio</label>
            <input type="text" name="title" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Termo de Fomento 001/2026 - Cultura Viva">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Concedente (Orgão/Empresa)</label>
                <input type="text" name="grantor_name" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Secretaria de Cultura">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Número do Processo/Contrato</label>
                <input type="text" name="contract_number" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: 231.422.11/2026">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Valor Global (R$)</label>
            <input type="text" name="total_amount" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.2rem; font-weight: 700; color: #4f46e5;" placeholder="0,00">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Início da Vigência</label>
                <input type="date" name="start_date" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Fim da Vigência</label>
                <input type="date" name="end_date" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/grants') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Registrar Convênio</button>
        </div>
    </form>
</div>
@endsection
