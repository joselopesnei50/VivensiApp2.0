@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 20px;">
    <h2 style="margin: 0; color: #2c3e50;">Novo Beneficiário</h2>
    <a href="{{ url('/ngo/beneficiaries') }}" style="color: #64748b; font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<div class="vivensi-card" style="max-width: 800px;">
    <form action="{{ url('/ngo/beneficiaries') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Nome Completo (Titular)</label>
            <input type="text" name="name" class="form-control-vivensi" required>
        </div>

        <div class="grid-2" style="gap: 20px;">
            <div class="form-group">
                <label class="form-label">NIS (Número de Identificação Social)</label>
                <input type="text" name="nis" class="form-control-vivensi">
            </div>
            <div class="form-group">
                <label class="form-label">CPF</label>
                <input type="text" name="cpf" class="form-control-vivensi">
            </div>
        </div>

        <div class="grid-2" style="gap: 20px;">
            <div class="form-group">
                <label class="form-label">Data de Nascimento</label>
                <input type="date" name="birth_date" class="form-control-vivensi">
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="text" name="phone" class="form-control-vivensi">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control-vivensi">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
                <option value="graduated">Graduado</option>
            </select>
            <small style="color:#94a3b8;">Dica: “Graduado” significa que saiu da vulnerabilidade (concluiu o acompanhamento).</small>
        </div>

        <div class="form-group">
            <label class="form-label">Endereço Completo</label>
            <input type="text" name="address" class="form-control-vivensi">
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" class="btn-premium">
                <i class="fas fa-save"></i> Salvar Cadastro
            </button>
        </div>
    </form>
</div>
@endsection
