@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Central de Auditoria</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Rastreabilidade completa de todas as ações sensíveis do sistema.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Data / Hora</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Usuário</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Evento</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Módulo / Item</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">IP</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px; color: #475569; font-size: 0.9rem;">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td style="padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">
                            {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                        </div>
                        <span style="color: #1e293b; font-weight: 500;">{{ $log->user->name ?? 'Sistema' }}</span>
                    </div>
                </td>
                <td style="padding: 15px;">
                    @php
                        $badgeColor = match($log->event) {
                            'created' => '#dcfce7; color: #16a34a',
                            'updated' => '#fef9c3; color: #ca8a04',
                            'deleted' => '#fecaca; color: #dc2626',
                            default => '#f1f5f9; color: #64748b'
                        };
                    @endphp
                    <span style="background: {{ explode(';', $badgeColor)[0] }}; {{ explode(';', $badgeColor)[1] }}; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                        {{ $log->event }}
                    </span>
                </td>
                <td style="padding: 15px; color: #475569; font-size: 0.9rem;">
                    <strong>{{ last(explode('\\', $log->auditable_type)) }}</strong>
                    <span style="color: #94a3b8;">(ID: {{ $log->auditable_id }})</span>
                </td>
                <td style="padding: 15px; text-align: center; color: #94a3b8; font-size: 0.8rem;">
                    {{ $log->ip_address }}
                </td>
                <td style="padding: 15px; text-align: center;">
                    <button onclick="viewDetails({{ $log->id }})" style="background: none; border: none; color: #4f46e5; cursor: pointer;">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $logs->links() }}
    </div>
</div>

<!-- Modal Detalhes (Placeholder) -->
<div id="logModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 600px;">
        <h3>Detalhes da Alteração</h3>
        <pre id="logContent" style="background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 8px; font-size: 0.8rem; overflow-x: auto;"></pre>
        <button onclick="document.getElementById('logModal').style.display='none'" class="btn-premium" style="width: 100%; margin-top: 15px; justify-content: center;">Fechar</button>
    </div>
</div>

<script>
    function viewDetails(id) {
        // Obter logs (idealmente via API, mas aqui simplificado)
        alert('Aqui exibiríamos o JSON das alterações antigas vs novas para auditoria detalhada.');
    }
</script>
@endsection
