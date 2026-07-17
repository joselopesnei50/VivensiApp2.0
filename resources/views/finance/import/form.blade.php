@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">
    <div class="mb-4">
        <p class="text-muted text-uppercase small mb-1" style="letter-spacing:.15em;">Financeiro</p>
        <h1 class="h3 mb-1">📊 Importar planilha de gastos</h1>
        <p class="text-muted mb-0">Traga transações que você já tem em Excel/CSV para o Vivensi.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))  <div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">1. Baixe o template</h5>
            <p class="text-muted small">Use as colunas exatamente com esses nomes:</p>
            <div class="p-3 rounded-3 mb-3" style="background:#f1f5f9;">
                <code>descricao,valor,data,tipo,categoria,projeto</code>
            </div>
            <ul class="small text-muted">
                <li><strong>descricao, valor, data, tipo</strong> — obrigatórios</li>
                <li><strong>categoria, projeto</strong> — opcionais</li>
                <li>Valor aceita <code>R$ 1.234,56</code> ou <code>1234.56</code></li>
                <li>Data aceita <code>dd/mm/aaaa</code> ou <code>aaaa-mm-dd</code></li>
                <li>Tipo: <code>receita</code> ou <code>despesa</code></li>
                <li>Máx <strong>5000 linhas</strong> · Arquivo até <strong>5 MB</strong></li>
            </ul>
            <a href="{{ route('finance.import.template') }}" class="btn btn-outline-primary">
                <i class="fas fa-download me-2"></i> Baixar template CSV
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">2. Envie sua planilha</h5>
            <form method="POST" action="{{ route('finance.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <input type="file" name="file" accept=".csv,.txt" class="form-control form-control-lg" required>
                    @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="project_id" class="form-label small text-muted mb-1">
                        <i class="fas fa-diagram-project me-1"></i>Vincular a um projeto (opcional)
                    </label>
                    <select name="project_id" id="project_id" class="form-control form-control-lg">
                        <option value="">— Nenhum · usar coluna "projeto" do CSV —</option>
                        @foreach(($projects ?? []) as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">Se escolher aqui, TODAS as linhas viram desse projeto (ignora coluna "projeto" do CSV).</small>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-eye me-1"></i> Pré-visualizar
                </button>
                <small class="text-muted d-block mt-2">Você verá as linhas ANTES de confirmar o import. Nada é salvo ainda.</small>
            </form>
        </div>
    </div>
</div>
@endsection
