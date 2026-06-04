@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $count    = $pendingApprovals->count();
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Gestão / Aprovações
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Central de Aprovações</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Valide recibos e despesas lançadas pela equipe.</p>
    </div>
    @if($count > 0)
    <span class="flex-shrink-0 d-flex align-items-center gap-2 px-3 py-2 rounded-3 fw-bold mt-1"
          style="background:#fef3c7;color:#92400e;font-size:.82rem;border:1px solid #fde68a;">
        <i class="fas fa-clock"></i> {{ $count }} pendente{{ $count > 1 ? 's' : '' }}
    </span>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-hourglass-half" style="color:#f59e0b;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:#d97706;line-height:1.1;">{{ $count }}</div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">pendentes</div>
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-circle-dollar-to-slot" style="color:#ef4444;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.3rem;color:#dc2626;line-height:1.1;">
                R$ {{ number_format($totalAmount, 2, ',', '.') }}
            </div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">valor total</div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                <i class="fas fa-shield-halved" style="color:#10b981;font-size:.85rem;"></i>
            </div>
            <div class="fw-800" style="font-size:1.6rem;color:#059669;line-height:1.1;">
                {{ $count > 0 ? 'Requer Ação' : 'Em dia' }}
            </div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">status geral</div>
        </div>
    </div>
</div>

{{-- Table card --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">

        @if($pendingApprovals->isEmpty())
            <div class="text-center py-5 px-4">
                <div style="width:60px;height:60px;background:rgba(16,185,129,.1);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i class="fas fa-check-double" style="font-size:1.4rem;color:#10b981;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Tudo em dia!</h6>
                <p class="text-muted small mb-0">Nenhuma aprovação pendente no momento.</p>
            </div>
        @else
            {{-- Meta bar --}}
            <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                 style="background:#f8fafc;border-radius:1rem 1rem 0 0;">
                <span style="font-size:.78rem;font-weight:700;color:#64748b;">
                    {{ $count }} solicitação{{ $count > 1 ? 'ões' : '' }} aguardando revisão
                </span>
                <span style="font-size:.72rem;color:#94a3b8;">ordenado por data mais recente</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 approval-tbl">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th class="px-4 py-3 th-lbl">Projeto</th>
                            <th class="py-3 th-lbl">Descrição</th>
                            <th class="py-3 th-lbl">Categoria</th>
                            <th class="py-3 th-lbl">Data</th>
                            <th class="py-3 th-lbl">Valor</th>
                            <th class="py-3 th-lbl text-center">Anexo</th>
                            <th class="pe-4 py-3 th-lbl text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingApprovals as $t)
                        @php $receiptPath = $t->receipt_path ?: $t->attachment_path; @endphp
                        <tr>
                            {{-- Projeto --}}
                            <td class="px-4 py-3" style="white-space:nowrap;">
                                <div class="fw-bold" style="font-size:.85rem;color:#0f172a;">
                                    {{ $t->project->name ?? '—' }}
                                </div>
                                <div style="font-size:.7rem;color:#94a3b8;">#{{ $t->project_id }}</div>
                            </td>

                            {{-- Descrição --}}
                            <td class="py-3" style="max-width:220px;">
                                <div style="font-size:.83rem;color:#334155;font-weight:600;
                                     display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ $t->description }}
                                </div>
                            </td>

                            {{-- Categoria --}}
                            <td class="py-3">
                                @if($t->category)
                                    <span class="cat-pill">{{ $t->category->name }}</span>
                                @else
                                    <span style="font-size:.75rem;color:#94a3b8;">—</span>
                                @endif
                            </td>

                            {{-- Data --}}
                            <td class="py-3" style="white-space:nowrap;">
                                <div style="font-size:.83rem;font-weight:600;color:#334155;">
                                    {{ $t->date ? $t->date->format('d/m/Y') : '—' }}
                                </div>
                            </td>

                            {{-- Valor --}}
                            <td class="py-3" style="white-space:nowrap;">
                                <span class="fw-800" style="font-size:.95rem;color:#dc2626;">
                                    R$ {{ number_format($t->amount, 2, ',', '.') }}
                                </span>
                            </td>

                            {{-- Anexo --}}
                            <td class="py-3 text-center">
                                @if($receiptPath)
                                    <a href="{{ route('transactions.attachment', $t->id) }}" target="_blank" rel="noopener"
                                       class="receipt-btn" title="Ver anexo">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </a>
                                @else
                                    <span style="font-size:.72rem;color:#cbd5e1;">Sem anexo</span>
                                @endif
                            </td>

                            {{-- Ações --}}
                            <td class="pe-4 py-3">
                                <div class="d-flex justify-content-end gap-2">
                                    <form action="{{ $basePath . '/transactions/' . $t->id . '/approve' }}" method="POST">
                                        @csrf
                                        <button type="submit" class="action-btn btn-ok" title="Aprovar">
                                            <i class="fas fa-check me-1"></i> Aprovar
                                        </button>
                                    </form>
                                    <form action="{{ $basePath . '/transactions/' . $t->id . '/reject' }}" method="POST">
                                        @csrf
                                        <button type="submit" class="action-btn btn-no" title="Recusar">
                                            <i class="fas fa-xmark me-1"></i> Recusar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</div>

@push('styles')
<style>
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

.th-lbl {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 700;
    color: #94a3b8;
    border-bottom: none;
}

.approval-tbl tbody tr { border-color: #f1f5f9; }
.approval-tbl tbody tr:hover td { background: #f8faff; }

.cat-pill {
    display: inline-flex; align-items: center;
    padding: 2px 9px; border-radius: 20px;
    font-size: .68rem; font-weight: 700;
    background: #f1f5f9; color: #475569;
    white-space: nowrap;
}

.receipt-btn {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: #f1f5f9;
    display: inline-flex; align-items: center; justify-content: center;
    color: #64748b;
    text-decoration: none;
    transition: background .15s, color .15s;
    font-size: .9rem;
}
.receipt-btn:hover { background: #e0e7ff; color: #4f46e5; }

.action-btn {
    display: inline-flex; align-items: center;
    padding: 5px 12px; border-radius: 8px;
    font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.btn-ok { background: #dcfce7; color: #166534; }
.btn-ok:hover { background: #10b981; color: #fff; }
.btn-no { background: #fee2e2; color: #991b1b; }
.btn-no:hover { background: #ef4444; color: #fff; }
</style>
@endpush
@endsection
