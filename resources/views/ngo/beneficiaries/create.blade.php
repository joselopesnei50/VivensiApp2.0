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
<div class="vivensi-card" style="max-width: 800px; margin-top: 30px; border: 1px dashed #cbd5e1; background: #f8fafc;">
    <h3 style="color: #334155; margin-bottom: 15px;"><i class="fas fa-file-import"></i> Importar via Planilha (CSV)</h3>
    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px;">
        Se você tem muitos dados, pode subir um arquivo CSV. Se o CPF ou NIS já existir, os dados serão atualizados.
    </p>

    @if(session('import_errors'))
        <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p><strong>Erros na importação:</strong></p>
            <ul style="margin-bottom: 0; font-size: 0.85rem;">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/ngo/beneficiaries/import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label class="form-label">Selecione o arquivo CSV</label>
            <input type="file" name="file" class="form-control-vivensi" accept=".csv" required>
            <div style="margin-top: 10px;">
                <a href="{{ url('/ngo/beneficiaries/import/template') }}" style="font-size: 0.85rem; color: #3b82f6;"><i class="fas fa-download"></i> Baixar Modelo de Planilha</a>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: left;">
            <button type="submit" class="btn-premium" style="background: #64748b;">
                <i class="fas fa-upload"></i> Processar Planilha
            </button>
        </div>
    </form>
</div>
@endsection
