@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 18px; display:flex; justify-content: space-between; align-items:center; gap: 14px; flex-wrap: wrap;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Detalhe de Auditoria</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Registro completo da alteração (antigo vs novo).</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ url('/ngo/audit') }}" class="btn-premium" style="background:#64748b;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button type="button" onclick="window.print()" class="btn-premium" style="background:#475569;">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>
</div>

@php
    $type = $log->auditable_type ? last(explode('\\', $log->auditable_type)) : 'Sistema';
    $userName = $log->user->name ?? 'Sistema';
@endphp

<div class="grid-2" style="margin-bottom: 14px;">
    <div class="vivensi-card">
        <div style="display:flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <div>
                <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Evento</div>
                <div style="margin-top: 6px; font-weight: 900; color:#0f172a; font-size: 1.1rem;">
                    {{ strtoupper($log->event) }}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Data/Hora</div>
                <div style="margin-top: 6px; font-weight: 900; color:#0f172a;">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </div>
            </div>
        </div>
        <div style="margin-top: 12px; color:#475569;">
            <strong>Módulo:</strong> {{ $type }}
            @if(!empty($log->auditable_id))
                <span style="color:#94a3b8;">(ID: {{ $log->auditable_id }})</span>
            @endif
        </div>
        @if(!empty($log->url))
            <div style="margin-top: 8px; color:#64748b; font-size:.9rem;">
                <strong>URL:</strong> <span style="word-break: break-all;">{{ $log->url }}</span>
            </div>
        @endif
    </div>
    <div class="vivensi-card">
        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Usuário</div>
        <div style="margin-top: 6px; font-weight: 900; color:#0f172a; font-size: 1.1rem;">
            {{ $userName }}
        </div>
        <div style="margin-top: 10px; display:flex; gap: 14px; flex-wrap: wrap;">
            <div style="color:#475569;">
                <strong>IP:</strong> {{ $log->ip_address ?? '—' }}
            </div>
            <div style="color:#475569;">
                <strong>User-Agent:</strong>
                <span style="color:#64748b;">{{ \Illuminate\Support\Str::limit($log->user_agent ?? '—', 90) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="align-items:start;">
    <div class="vivensi-card" style="padding: 0; overflow:hidden;">
        <div style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; background:#f8fafc;">
            <strong style="color:#0f172a;">Valores Antigos</strong>
        </div>
        <pre style="margin:0; padding: 16px; background:#0f172a; color:#e2e8f0; overflow:auto; font-size:.85rem; line-height:1.45;">{{ json_encode($log->old_values ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    <div class="vivensi-card" style="padding: 0; overflow:hidden;">
        <div style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; background:#f8fafc;">
            <strong style="color:#0f172a;">Valores Novos</strong>
        </div>
        <pre style="margin:0; padding: 16px; background:#0f172a; color:#e2e8f0; overflow:auto; font-size:.85rem; line-height:1.45;">{{ json_encode($log->new_values ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>

<style>
@media print {
    .btn-premium, #sidebar, .header-main { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; border: none; }
    .vivensi-card { box-shadow: none; border: none; }
    pre { page-break-inside: avoid; }
}
</style>
@endsection

