@extends('layouts.app')

@section('content')
<style>
    .crm-card {
        background: white;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        overflow: hidden;
    }
    .crm-header {
        padding: 24px;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .donor-table {
        width: 100%;
        border-collapse: collapse;
    }
    .donor-table th {
        padding: 16px 24px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .donor-table td {
        padding: 20px 24px;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }
    .donor-table tr:hover { background-color: #fcfdfe; }
    
    .avatar-ngo {
        width: 45px;
        height: 45px;
        border-radius: 14px;
        background: #eef2ff;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
    }
    
    .badge-donor-pf { background: #dcfce7; color: #166534; }
    .badge-donor-pj { background: #dbeafe; color: #1e40af; }
    .badge-donor-gov { background: #fef9c3; color: #854d0e; }
    
    .action-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #64748b;
        transition: all 0.2s;
    }
    .action-circle:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div>
        <h2 class="fw-bold text-dark m-0">Base de Doadores</h2>
        <p class="text-muted mt-1">Gestão inteligente de parceiros e investidores sociais.</p>
    </div>
    <div class="d-flex gap-3">
        <a href="#" class="btn btn-light rounded-pill px-4 fw-bold border">
            <i class="fas fa-filter me-2 text-muted"></i> Filtrar
        </a>
        <a href="{{ url('/ngo/donors/create') }}" class="btn-premium">
            <i class="fas fa-plus me-2"></i> Novo Doador
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="crm-card">
    <div class="crm-header">
        <div style="position: relative; width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 15px; top: 12px; color: #94a3b8;"></i>
            <input type="text" placeholder="Buscar doador..." style="width:100%; padding: 10px 15px 10px 40px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.9rem;">
        </div>
        <div class="text-muted small fw-bold">
            Mostrando {{ $donors->count() }} de {{ $donors->total() }} registros
        </div>
    </div>
    
    <table class="donor-table">
        <thead>
            <tr>
                <th>Doador / Parceiro</th>
                <th>Informação de Contato</th>
                <th class="text-center">Tipo</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($donors as $d)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-ngo">
                            {{ substr($d->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $d->name }}</div>
                            <div class="text-muted small">Doc: {{ $d->document ?? 'Não informado' }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($d->email)
                        <div class="text-dark small fw-bold mb-1"><i class="far fa-envelope me-2 text-muted"></i> {{ $d->email }}</div>
                    @endif
                    @if($d->phone)
                        <div class="text-muted small"><i class="fas fa-phone-alt me-2 text-muted"></i> {{ $d->phone }}</div>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $types = [
                            'individual' => ['label' => 'Pessoa Física', 'class' => 'badge-donor-pf'],
                            'company' => ['label' => 'Empresa (PJ)', 'class' => 'badge-donor-pj'],
                            'government' => ['label' => 'Governo', 'class' => 'badge-donor-gov'],
                        ];
                        $curr = $types[$d->type] ?? ['label' => 'Outro', 'class' => 'bg-secondary'];
                    @endphp
                    <span class="badge {{ $curr['class'] }} px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                        {{ $curr['label'] }}
                    </span>
                </td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-2">
                        @if($d->phone)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $d->phone) }}" target="_blank" class="action-circle text-success" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        @endif
                        <a href="{{ url('/ngo/donors/'.$d->id.'/edit') }}" class="action-circle" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ url('/ngo/donors/'.$d->id) }}" method="POST" onsubmit="return confirm('Excluir este doador?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-circle border-0 text-danger" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="opacity-30 mb-3"><i class="fas fa-users fa-3x"></i></div>
                    <p class="text-muted fw-bold">Nenhum doador encontrado.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="p-4 border-top">
        {{ $donors->links() }}
    </div>
</div>
@endsection
