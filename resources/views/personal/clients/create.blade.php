@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('clients.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Mini CRM</h6>
    </div>
    <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px;">Cadastrar Novo Cliente</h2>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="vivensi-card p-5" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="name" class="form-label" style="font-weight: 700; color: #475569;">Nome Completo / Razão Social</label>
                        <input type="text" name="name" class="form-control" required style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="name">
                    </div>
                    <div class="col-md-3">
                        <label for="type" class="form-label" style="font-weight: 700; color: #475569;">Tipo</label>
                        <select name="type" class="form-select" required style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="type">
                            <option value="individual">Pessoa Física</option>
                            <option value="company">Pessoa Jurídica</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="stage" class="form-label" style="font-weight: 700; color: #475569;">Estágio</label>
                        <select name="stage" class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="stage">
                            @foreach(\App\Models\Client::STAGES as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'active')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="document" class="form-label" style="font-weight: 700; color: #475569;">Documento</label>
                        <input type="text" name="document" class="form-control" placeholder="CPF ou CNPJ" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="document">
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label" style="font-weight: 700; color: #475569;">E-mail</label>
                        <input type="email" name="email" class="form-control" placeholder="contato@email.com" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="email">
                    </div>
                    <div class="col-md-4">
                        <label for="phone" class="form-label" style="font-weight: 700; color: #475569;">WhatsApp</label>
                        <input type="text" name="phone" class="form-control" placeholder="(00) 00000-0000" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="phone">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="purchase_history" class="form-label" style="font-weight: 700; color: #475569;">Histórico de Compras (Anotações)</label>
                    <textarea name="purchase_history" class="form-control" rows="3" placeholder="Registre aqui os produtos/serviços que este cliente já comprou..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="purchase_history"></textarea>
                </div>

                <div class="mb-5">
                    <label for="relationship_notes" class="form-label" style="font-weight: 700; color: #475569;">Anotações de Relacionamento (CRM)</label>
                    <textarea name="relationship_notes" class="form-control" rows="3" placeholder="Informações relevantes para o próximo contato, dores do cliente, etc..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" id="relationship_notes"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn-premium" style="background: #10b981; color: white; border: none; font-weight: 800; padding: 12px 30px; font-size: 1.1rem;">
                        <i class="fas fa-check-circle me-2"></i> Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
