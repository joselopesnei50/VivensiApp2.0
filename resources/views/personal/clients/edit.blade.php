@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('clients.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Mini CRM</h6>
    </div>
    <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px;">Editar Cliente</h2>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="vivensi-card p-5" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
            <form action="{{ route('clients.update', $client) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <label class="form-label" style="font-weight: 700; color: #475569;">Nome Completo / Razão Social</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $client->name) }}" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 700; color: #475569;">Tipo de Pessoa</label>
                        <select name="type" class="form-select" required style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                            <option value="individual" {{ $client->type == 'individual' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                            <option value="company" {{ $client->type == 'company' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 700; color: #475569;">Documento</label>
                        <input type="text" name="document" class="form-control" placeholder="CPF ou CNPJ" value="{{ old('document', $client->document) }}" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 700; color: #475569;">E-mail</label>
                        <input type="email" name="email" class="form-control" placeholder="contato@email.com" value="{{ old('email', $client->email) }}" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 700; color: #475569;">WhatsApp</label>
                        <input type="text" name="phone" class="form-control" placeholder="(00) 00000-0000" value="{{ old('phone', $client->phone) }}" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-weight: 700; color: #475569;">Histórico de Compras (Anotações)</label>
                    <textarea name="purchase_history" class="form-control" rows="3" placeholder="Registre aqui os produtos/serviços que este cliente já comprou..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('purchase_history', $client->purchase_history) }}</textarea>
                </div>

                <div class="mb-5">
                    <label class="form-label" style="font-weight: 700; color: #475569;">Anotações de Relacionamento (CRM)</label>
                    <textarea name="relationship_notes" class="form-control" rows="3" placeholder="Informações relevantes para o próximo contato, dores do cliente, etc..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('relationship_notes', $client->relationship_notes) }}</textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn-premium" style="background: #4f46e5; color: white; border: none; font-weight: 800; padding: 12px 30px; font-size: 1.1rem;">
                        <i class="fas fa-save me-2"></i> Atualizar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
