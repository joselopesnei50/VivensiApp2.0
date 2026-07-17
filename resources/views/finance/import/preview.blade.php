@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">
    <div class="mb-4">
        <p class="text-muted text-uppercase small mb-1" style="letter-spacing:.15em;">Financeiro · Import</p>
        <h1 class="h3 mb-1">👀 Preview antes de importar</h1>
        @if(!empty($projectOverride))
            <div class="alert alert-info mt-3 mb-0" style="border:none;background:#e0f2fe;color:#075985;">
                <i class="fas fa-diagram-project me-2"></i>
                Todas as transações serão vinculadas ao projeto <strong>{{ $projectOverride }}</strong>.
                A coluna "projeto" do CSV será ignorada.
                @if(!empty($stageOverride))
                    · Etapa: <strong>{{ $stageOverride }}</strong> (coluna "etapa" do CSV será ignorada).
                @endif
            </div>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center py-3">
                <div class="h2 fw-bold mb-0 text-primary">{{ $summary['total'] }}</div>
                <small class="text-muted">Total no arquivo</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center py-3">
                <div class="h2 fw-bold mb-0 text-success">{{ $summary['valid'] }}</div>
                <small class="text-muted">Válidas para importar</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-center py-3">
                <div class="h2 fw-bold mb-0 text-danger">{{ $summary['errors'] }}</div>
                <small class="text-muted">Com erro (ignoradas)</small>
            </div>
        </div>
    </div>

    @if(!empty($parseErrors) && count($parseErrors) > 0)
        <div class="alert alert-warning">
            <strong>{{ count($parseErrors) }} erros:</strong>
            <ul class="mb-0 small mt-2">
                @foreach(array_slice($parseErrors, 0, 20) as $e)<li>{{ $e }}</li>@endforeach
                @if(count($parseErrors) > 20)<li><em>… e mais {{ count($parseErrors) - 20 }}</em></li>@endif
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-3">#</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th>Projeto</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($rows, 0, 100) as $r)
                            <tr class="{{ $r['_error'] ? 'table-danger' : '' }}">
                                <td class="ps-3">{{ $r['_line'] }}</td>
                                <td>{{ $r['descricao'] ?? '—' }}</td>
                                <td class="text-end">
                                    @if(is_numeric($r['valor'] ?? null))
                                        R$ {{ number_format($r['valor'], 2, ',', '.') }}
                                    @else
                                        {{ $r['valor'] ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $r['data'] ?? '—' }}</td>
                                <td>
                                    @if(($r['tipo'] ?? '') === 'income')
                                        <span class="badge bg-success">Receita</span>
                                    @elseif(($r['tipo'] ?? '') === 'expense')
                                        <span class="badge bg-danger">Despesa</span>
                                    @else
                                        <small class="text-muted">{{ $r['tipo'] ?? '—' }}</small>
                                    @endif
                                </td>
                                <td><small>{{ $r['categoria'] ?? '—' }}</small></td>
                                <td><small>{{ $r['projeto'] ?? '—' }}</small></td>
                                <td class="text-center">
                                    @if($r['_error'])
                                        <span class="badge bg-danger" title="{{ $r['_error'] }}">Erro</span>
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(count($rows) > 100)
                <div class="p-3 text-center text-muted small">Mostrando 100 de {{ count($rows) }} linhas</div>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('finance.import.form') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Cancelar
        </a>
        @if($summary['valid'] > 0)
            <form method="POST" action="{{ route('finance.import.confirm') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check me-1"></i> Confirmar import de {{ $summary['valid'] }} transações
                </button>
            </form>
        @endif
    </div>

    <div class="alert alert-info small mt-3">
        ℹ️ Transações importadas entram com status <strong>pendente</strong> (aguardando aprovação).
        Duplicatas (mesma descrição + data + valor) são ignoradas automaticamente.
    </div>
</div>
@endsection
