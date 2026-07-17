@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Importar Planilha de Patrimônio</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Suba um CSV com N bens de uma vez. Preview antes de confirmar.</p>
    </div>
    <div>
        <a href="{{ url('/ngo/assets') }}" class="btn-premium" style="background:#64748b;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <ul style="margin: 0;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="vivensi-card" style="margin-bottom: 25px;">
    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">
        <i class="fas fa-file-csv" style="color: #4f46e5;"></i> Modelo do CSV
    </h3>
    <p style="color: #475569; margin: 0 0 15px 0;">
        Baixe o template e preencha antes de subir. Colunas obrigatórias: <strong>nome</strong>, <strong>data_aquisicao</strong>, <strong>valor</strong>. Opcionais: codigo, descricao, vida_util_anos, valor_residual, status, local, responsavel.
    </p>
    <a href="{{ route('assets.import.template') }}" class="btn-premium" style="background:#0284c7;">
        <i class="fas fa-download"></i> Baixar Template
    </a>
</div>

<div class="vivensi-card">
    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">
        <i class="fas fa-upload" style="color: #4f46e5;"></i> Enviar CSV
    </h3>
    <form action="{{ route('assets.import.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="file" name="file" accept=".csv,.txt" required class="form-control-vivensi" style="flex: 1; min-width: 260px;">
            <button type="submit" class="btn-premium">
                <i class="fas fa-eye"></i> Ver Preview
            </button>
        </div>
        <p style="color: #64748b; font-size: 0.85rem; margin: 8px 0 0 0;">
            Máx 5000 linhas · 5 MB · Dedup por código do patrimônio · Status default: ativo
        </p>
    </form>
</div>
@endsection
