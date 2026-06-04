@extends('layouts.app')
@section('title', 'Logs — ' . $automation->name)

@section('content')
@php
    $totalLogs = $automation->logs()->count();
    $sentCount = $automation->logs()->where('status', 'sent')->count();
    $failedCount = $automation->logs()->where('status', 'failed')->count();
    $successRate = $totalLogs > 0 ? round(($sentCount / $totalLogs) * 100) : 0;

    $triggerMeta = [
        'donor_inactive_days'    => ['label' => 'Doador sem doação',             'icon' => 'heart',        'color' => '#10b981'],
        'sponsorship_stale_days' => ['label' => 'Patrocínio parado em proposta', 'icon' => 'handshake',    'color' => '#6366f1'],
        'no_contact_days'        => ['label' => 'Contato sem interação',         'icon' => 'user-clock',   'color' => '#f59e0b'],
        'open_conversation_days' => ['label' => 'Conversa sem resposta',         'icon' => 'comment-dots', 'color' => '#3b82f6'],
    ];
    $audienceLabels = ['all' => 'Todos', 'donors' => 'Doadores', 'sponsors' => 'Patrocinadores', 'contacts' => 'Contatos'];
    $tm = $triggerMeta[$automation->trigger] ?? ['label' => $automation->trigger, 'icon' => 'bolt', 'color' => '#6366f1'];
@endphp

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
        <a href="{{ route('whatsapp.automations.index') }}"
           class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center gap-1 flex-shrink-0"
           style="padding:6px 14px;font-size:.82rem;margin-top:6px;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <div class="flex-1">
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
                WhatsApp / Automações / Histórico
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">{{ $automation->name }}</h2>
                @if($automation->is_active)
                    <span class="badge rounded-pill" style="font-size:.62rem;font-weight:700;background:rgba(16,185,129,.12);color:#059669;">
                        <i class="fas fa-circle" style="font-size:.35rem;vertical-align:middle;margin-right:2px;"></i> Ativa
                    </span>
                @else
                    <span class="badge rounded-pill" style="font-size:.62rem;font-weight:700;background:rgba(100,116,139,.1);color:#64748b;">
                        <i class="fas fa-pause" style="font-size:.5rem;vertical-align:middle;margin-right:2px;"></i> Pausada
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Automation context card --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden"
         style="border-left: 4px solid {{ $tm['color'] }} !important;">
        <div class="card-body p-3 px-4">
            <div class="d-flex align-items-center gap-4 flex-wrap" style="font-size:.8rem;color:#64748b;">
                <span>
                    <i class="fas fa-bolt me-1" style="color:{{ $tm['color'] }};"></i>
                    <strong class="text-dark">{{ $tm['label'] }}</strong> há {{ $automation->trigger_days }} dias
                </span>
                <span>
                    <i class="fas fa-users me-1 text-muted"></i>
                    {{ $audienceLabels[$automation->audience] ?? $automation->audience }}
                </span>
                <span>
                    <i class="fas fa-clock me-1 text-muted"></i>
                    Envia entre {{ substr($automation->send_window_start, 0, 5) }} – {{ substr($automation->send_window_end, 0, 5) }}
                </span>
                <a href="{{ route('whatsapp.automations.edit', $automation) }}" class="ms-auto text-muted text-decoration-none" style="font-size:.75rem;">
                    <i class="fas fa-pen me-1"></i> Editar automação
                </a>
            </div>
        </div>
    </div>

    {{-- Stats row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-paper-plane" style="color:var(--ds-brand);font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#4f46e5;line-height:1.1;">{{ number_format($totalLogs) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">total enviados</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-circle-check" style="color:#10b981;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#059669;line-height:1.1;">{{ number_format($sentCount) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">sucesso</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(239,68,68,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-circle-xmark" style="color:#ef4444;font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;color:#dc2626;line-height:1.1;">{{ number_format($failedCount) }}</div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">falhas</div>
            </div>
        </div>
        <div class="col-6 col-sm-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.1);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                    <i class="fas fa-chart-pie" style="color:var(--ds-brand);font-size:.85rem;"></i>
                </div>
                <div class="fw-800" style="font-size:1.6rem;line-height:1.1;color:{{ $successRate >= 80 ? '#059669' : ($successRate >= 50 ? '#d97706' : '#dc2626') }};">
                    {{ $successRate }}%
                </div>
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-top:2px;">taxa de sucesso</div>
            </div>
        </div>
    </div>

    {{-- Logs table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 px-4">
                    <div style="width:60px;height:60px;background:#ede9fe;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="fas fa-inbox" style="font-size:1.4rem;color:#7c3aed;"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Nenhum envio registrado ainda</h6>
                    <p class="text-muted small mb-0" style="max-width:340px;margin-inline:auto;">
                        Os logs aparecerão aqui após a automação processar contatos elegíveis (processamento diário às 10h).
                    </p>
                </div>
            @else
                {{-- Table header --}}
                <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
                     style="background:#f8fafc;border-radius:1rem 1rem 0 0;">
                    <span style="font-size:.78rem;font-weight:700;color:#64748b;">
                        {{ number_format($logs->total()) }} registro{{ $logs->total() !== 1 ? 's' : '' }}
                        @if($logs->hasPages())
                            — página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}
                        @endif
                    </span>
                    @if($logs->hasPages())
                        <span style="font-size:.72rem;color:#94a3b8;">50 por página</span>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 log-table">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th class="px-4 py-3 th-label">Contato</th>
                                <th class="py-3 th-label">Mensagem enviada</th>
                                <th class="py-3 th-label">Status</th>
                                <th class="pe-4 py-3 th-label">Data e hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                {{-- Contact --}}
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="contact-avatar">
                                            {{ strtoupper(substr($log->contact_name ?: $log->contact_phone, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-600 text-dark" style="font-size:.85rem;line-height:1.2;">
                                                {{ $log->contact_name ?: '—' }}
                                            </div>
                                            <div class="font-monospace" style="font-size:.72rem;color:#94a3b8;letter-spacing:.01em;">
                                                {{ $log->contact_phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Message --}}
                                <td class="py-3" style="max-width:340px;">
                                    <div class="text-muted log-message" title="{{ $log->message_sent }}" style="font-size:.8rem;">
                                        {{ $log->message_sent }}
                                    </div>
                                    @if($log->error_message)
                                        <div class="d-flex align-items-center gap-1 mt-1" style="font-size:.72rem;color:#dc2626;">
                                            <i class="fas fa-triangle-exclamation"></i>
                                            <span>{{ $log->error_message }}</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3">
                                    @if($log->status === 'sent')
                                        <span class="status-badge status-sent">
                                            <i class="fas fa-check"></i> Enviado
                                        </span>
                                    @else
                                        <span class="status-badge status-failed">
                                            <i class="fas fa-xmark"></i> Falhou
                                        </span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="pe-4 py-3">
                                    @if($log->sent_at)
                                        <div style="font-size:.82rem;color:#374151;font-weight:500;">
                                            {{ $log->sent_at->format('d/m/Y') }}
                                        </div>
                                        <div style="font-size:.72rem;color:#94a3b8;">
                                            {{ $log->sent_at->format('H:i') }}
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-4 py-3 border-top" style="background:#fafafa;border-radius:0 0 1rem 1rem;">
                        {{ $logs->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<style>
.fw-600 { font-weight: 600; }
.fw-700 { font-weight: 700; }
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

.th-label {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 700;
    color: #94a3b8;
    border-bottom: none;
}

.log-table tbody tr { border-color: #f1f5f9; }
.log-table tbody tr:hover td { background: #f8faff; }

.log-message {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}

.contact-avatar {
    width: 34px; height: 34px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ds-brand), #8b5cf6);
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    letter-spacing: .02em;
}

.status-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .02em;
    white-space: nowrap;
}
.status-sent   { background: rgba(16,185,129,.1); color: #059669; }
.status-failed { background: rgba(239,68,68,.1);  color: #dc2626; }
</style>
@endsection
