@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 700px; margin: 0 auto; border: 1px solid rgba(0,0,0,0.05); background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.04);">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Doador</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Cadastre um novo parceiro da organização.</p>
    </div>
    
    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/ngo/donors') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Nome Completo / Razão Social</label>
            <input type="text" name="name" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Instituto Ayrton Senna" value="{{ old('name') }}">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Tipo de Doador</label>
                <select name="type" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <option value="individual">Pessoa Física</option>
                    <option value="company">Empresa</option>
                    <option value="government">Governo / Edital</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">CPF / CNPJ</label>
                <input type="text" name="document" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" value="{{ old('document') }}">
            </div>
        </div>
        
        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Email</label>
                <input type="email" name="email" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Telefone</label>
                <input type="text" name="phone" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="(99) 99999-9999" value="{{ old('phone') }}">
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/donors') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Salvar Doador</button>
        </div>
    </form>
</div>
@endsection
