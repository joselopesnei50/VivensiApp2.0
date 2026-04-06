@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Auditoria de Comunicação</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Logs de E-mail (Brevo)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Histórico completo de disparos e entregabilidade.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
        <h4 style="margin: 0; font-size: 1rem; color: #334155;">Monitoramento de Disparos</h4>
    </div>
    
    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead style="background: white; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Data/Hora</th>
                    <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Organização</th>
                    <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Destinatário</th>
                    <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Assunto</th>
                    <th style="padding: 15px 25px; text-align: center; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status</th>
                    <th style="padding: 15px 25px; text-align: right; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px 25px; color: #64748b; font-size: 0.85rem;">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td style="padding: 15px 25px;">
                        <span style="background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            {{ $log->tenant_name ?? 'Sistema' }}
                        </span>
                    </td>
                    <td style="padding: 15px 25px; color: #1e293b; font-weight: 600; font-size: 0.9rem;">
                        {{ $log->to_email }}
                    </td>
                    <td style="padding: 15px 25px; color: #64748b; font-size: 0.85rem;">
                        {{ $log->subject }}
                    </td>
                    <td style="padding: 15px 25px; text-align: center;">
                        @if($log->status === 'sent')
                            <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">
                                <i class="fas fa-check-circle me-1"></i> ENVIADO
                            </span>
                        @else
                            <span style="background: #fef2f2; color: #dc2626; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">
                                <i class="fas fa-times-circle me-1"></i> FALHOU
                            </span>
                        @endif
                    </td>
                    <td style="padding: 15px 25px; text-align: right;">
                        @if($log->response)
                        <button onclick="viewResponse({{ json_encode($log->response) }})" class="btn-outline" style="padding: 6px 12px; border-radius: 6px; font-size: 0.75rem;">
                            Ver Resposta
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">
                        Nenhum registro de e-mail encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="padding: 20px; border-top: 1px solid #f1f5f9;">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<!-- Modal com Design Premium -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header border-0 bg-light p-4">
                <h5 class="modal-title font-weight-bold" style="color: #1e293b;">Resposta da API Brevo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div style="background: #1e293b; color: #f8fafc; padding: 20px; border-radius: 12px; font-family: 'Courier New', monospace; font-size: 0.85rem; max-height: 400px; overflow-y: auto;">
                    <pre id="responseBody" style="margin: 0; white-space: pre-wrap; word-break: break-all; color: inherit;"></pre>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn-premium" data-bs-dismiss="modal" style="background: #6366f1;">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewResponse(response) {
        try {
            const formatted = JSON.stringify(JSON.parse(response), null, 4);
            document.getElementById('responseBody').innerText = formatted;
        } catch (e) {
            document.getElementById('responseBody').innerText = response;
        }
        
        const myModal = new bootstrap.Modal(document.getElementById('responseModal'));
        myModal.show();
    }
</script>
@endsection
