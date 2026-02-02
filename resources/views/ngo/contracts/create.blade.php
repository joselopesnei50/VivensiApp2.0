@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Contrato Digital</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Redija o contrato e envie para assinatura.</p>
    </div>

    <form action="{{ url('/ngo/contracts') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Título do Documento</label>
            <input type="text" name="title" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Contrato de Prestação de Serviços - Consultoria">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Nome do Signatário</label>
                <input type="text" name="signer_name" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Nome Completo">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Email (Opcional)</label>
                <input type="email" name="signer_email" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="email@exemplo.com">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Endereço de quem irá assinar</label>
            <input type="text" name="signer_address" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Rua, Número, Bairro, Cidade - UF">
        </div>

        <div class="grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">WhatsApp / Telefone</label>
                <input type="text" name="signer_phone" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">CPF</label>
                <input type="text" name="signer_cpf" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">RG</label>
                <input type="text" name="signer_rg" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="0.000.000">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Termos do Contrato</label>
            <textarea name="content" class="form-control-vivensi" required style="width: 100%; height: 300px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: sans-serif; resize: vertical;" placeholder="Cole aqui o texto do contrato..."></textarea>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/contracts') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Gerar e Enviar</button>
        </div>
    </form>
</div>
@endsection
