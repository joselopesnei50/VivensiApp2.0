@extends('layouts.app')
@section('title', 'Logs — ' . $automation->name)

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('whatsapp.automations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <div>
            <p class="mb-0" style="color:#6366f1;font-weight:700;text-transform:uppercase;font-size:.7rem;letter-spacing:1.5px;">
                <i class="fab fa-whatsapp me-1"></i> Automações / Logs
            </p>
            <h2 class="fw-bold mb-0" style="font-size:1.5rem;color:#111827;">{{ $automation->name }}</h2>
        </div>
    </div>

    {{-- Stats bar --}}
    <div class="row g-3 mb-4">
        @php
            $sent   = $logs->total() > 0 ? $automation->logs()->where('status','sent')->count()   : 0;
            $failed = $logs->total() > 0 ? $automation->logs()->where('status','failed')->count() : 0;
        @endphp
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <div class="fw-bold" style="font-size:1.6rem;color:#4f46e5;">{{ $automation->logs()->count() }}</div>
                <div class="text-muted small">Total enviados</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <div class="fw-bold" style="font-size:1.6rem;color:#10b981;">{{ $sent }}</div>
                <div class="text-muted small">Sucesso</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
                <div class="fw-bold" style="font-size:1.6rem;color:#ef4444;">{{ $failed }}</div>
                <div class="text-muted small">Falhas</div>
            </div>
        </div>
    </div>

    {{-- Tabela de logs --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5">
                    <div style="width:56px;height:56px;background:#ede9fe;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
                        <i class="fas fa-inbox" style="font-size:1.4rem;color:#7c3aed;"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Nenhum envio registrado ainda</h6>
                    <p class="text-muted small mb-0">Os logs aparecerão aqui após a automação processar contatos elegíveis.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                            <tr>
                                <th class="px-4 py-3 fw-600 text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Contato</th>
                                <th class="py-3 fw-600 text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Telefone</th>
                                <th class="py-3 fw-600 text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Mensagem</th>
                                <th class="py-3 fw-600 text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Status</th>
                                <th class="pe-4 py-3 fw-600 text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Enviado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-600 text-dark">{{ $log->contact_name ?: '—' }}</div>
                                </td>
                                <td class="py-3">
                                    <span class="font-monospace small">{{ $log->contact_phone }}</span>
                                </td>
                                <td class="py-3" style="max-width:320px;">
                                    <div class="text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px;" title="{{ $log->message_sent }}">
                                        {{ $log->message_sent }}
                                    </div>
                                    @if($log->error_message)
                                        <div class="text-danger small mt-1">
                                            <i class="fas fa-circle-exclamation me-1"></i>{{ $log->error_message }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($log->status === 'sent')
                                        <span class="badge rounded-pill px-3 py-2" style="font-size:.7rem;background:rgba(16,185,129,.1);color:#059669;">
                                            <i class="fas fa-check me-1"></i> Enviado
                                        </span>
                                    @else
                                        <span class="badge rounded-pill px-3 py-2" style="font-size:.7rem;background:rgba(239,68,68,.1);color:#dc2626;">
                                            <i class="fas fa-xmark me-1"></i> Falhou
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-muted small">
                                    {{ $log->sent_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-4 py-3 border-top">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<style>.fw-600{font-weight:600;}</style>
@endsection
