@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --approval-green: #10b981;
        --approval-red: #ef4444;
        --accent-indigo: #6366f1;
    }
    body { font-family: 'Outfit', sans-serif; background: #f8fafc; }

    .approval-table {
        width: 100%;
        background: white;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }

    .approval-table th {
        background: #f8fafc;
        padding: 20px 25px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.05em;
    }

    .approval-table td {
        padding: 24px 25px;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }

    .approval-table tr:hover { background: #fcfdfe; }

    .receipt-preview {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 1.2rem;
        transition: all 0.2s;
        cursor: pointer;
    }
    .receipt-preview:hover { background: #e2e8f0; color: var(--accent-indigo); }

    .btn-approve {
        background: #dcfce7;
        color: #166534;
        border: none;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .btn-approve:hover { background: #10b981; color: white; }

    .btn-reject {
        background: #fee2e2;
        color: #991b1b;
        border: none;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .btn-reject:hover { background: #ef4444; color: white; }
</style>

<div class="container-fluid py-4 px-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-800 m-0" style="letter-spacing: -1px;">Central de Aprovações</h2>
            <p class="text-muted m-0">Confira e valide recibos e despesas lançadas pela equipe.</p>
        </div>
        <div class="badge bg-white text-dark shadow-sm border px-3 py-2 rounded-pill fw-bold">
            <i class="fas fa-clock text-warning me-2"></i> {{ $pendingApprovals->count() }} Solicitações Pendentes
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="approval-table">
        <table class="w-100">
            <thead>
                <tr>
                    <th>Projeto</th>
                    <th>Descrição & Data</th>
                    <th>Valor</th>
                    <th class="text-center">Doc/Recibo</th>
                    <th class="text-end">Ação Decisória</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingApprovals as $t)
                <tr>
                    <td>
                        <div class="fw-bold text-dark">{{ $t->project->name ?? 'N/A' }}</div>
                        <div class="text-muted small">Cód: #PROJ-{{ $t->project_id }}</div>
                    </td>
                    <td>
                        <div class="fw-bold">{{ $t->description }}</div>
                        <div class="text-muted small">{{ $t->date->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <span class="fw-800 text-danger" style="font-size: 1.1rem;">R$ {{ number_format($t->amount, 2, ',', '.') }}</span>
                    </td>
                    <td class="text-center">
                        @if($t->receipt_path)
                            <a href="{{ Storage::url($t->receipt_path) }}" target="_blank" class="receipt-preview mx-auto">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </a>
                        @else
                            <div class="text-muted small">Sem Anexo</div>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <form action="{{ url('/transactions/'.$t->id.'/approve') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-approve">Aprovar Lanc.</button>
                            </form>
                            <form action="{{ url('/transactions/'.$t->id.'/reject') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-reject">Recusar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="opacity-10 mb-3"><i class="fas fa-check-double fa-4x"></i></div>
                        <p class="text-muted fw-bold">Tudo em dia! Nenhuma aprovação pendente.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
