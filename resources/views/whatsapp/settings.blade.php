@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Omnichannel & Chatbot</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Treinamento da IA & WhatsApp</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Configure seu robô de atendimento e sua instância Z-API.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <form action="{{ url('/whatsapp/settings') }}" method="POST">
            @csrf
            
            <!-- AI Training Section -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #a855f7;">
                <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-brain me-2" style="color: #a855f7;"></i> Instruções de Treinamento (Prompt)
                </h4>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">
                    Defina como o robô deve se comportar, o nome dele e o que ele deve responder aos clientes. 
                    <b>Dica:</b> Inclua informações sobre seus serviços, horários e tom de voz.
                </p>
                <textarea name="ai_training" rows="10" class="form-control-vivensi" placeholder="Ex: Você é o 'Vivi', assistente virtual da nossa ONG. Seja carinhosa, use emojis e explique que aceitamos doações via Pix..." style="font-family: inherit;">{{ $config->ai_training }}</textarea>
                
                <div style="margin-top: 20px; display: flex; align-items: center; gap: 20px; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabled" {{ $config->ai_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-700" for="aiEnabled">Ativar Respostas Automáticas (IA)</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; padding: 15px;">
                <i class="fas fa-save me-2"></i> Salvar e Treinar Cérebro da IA
            </button>
        </div>

        <div class="col-md-5">
            <!-- Z-API Config Section -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #25d366;">
                <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fab fa-whatsapp me-2" style="color: #25d366;"></i> Conexão Z-API
                </h4>
                
                <div class="form-group mb-4">
                    <label class="fw-700 mb-2 small">ID da Instância</label>
                    <input type="text" name="instance_id" value="{{ $config->instance_id }}" class="form-control-vivensi" placeholder="Ex: 3B..." required>
                </div>

                <div class="form-group mb-4">
                    <label class="fw-700 mb-2 small">Instance Token</label>
                    <input type="password" name="token" value="{{ $config->token }}" class="form-control-vivensi" placeholder="Token Secreto" required>
                </div>

                <div class="form-group mb-4">
                    <label class="fw-700 mb-2 small">Client Token (Z-API)</label>
                    <input type="text" name="client_token" value="{{ $config->client_token }}" class="form-control-vivensi" placeholder="Para segurança do Webhook">
                </div>

                <div class="alert alert-warning" style="font-size: 0.8rem; border-radius: 12px; border: none; background: #fffbeb; color: #92400e;">
                    <i class="fas fa-link me-1"></i> <b>URL de Webhook:</b><br>
                    <code style="background: rgba(0,0,0,0.05); padding: 2px 5px; border-radius: 4px; display: block; margin-top: 5px;">{{ url('/api/whatsapp/webhook') }}</code>
                    Copie este link e cole no painel da Z-API em "Configurações de Webhook".
                </div>
            </div>

            <!-- Connection Status & QR Code Scanner -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #3b82f6;">
                <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-wifi me-2" style="color: #3b82f6;"></i> Status da Conexão
                </h4>
                
                <div id="connectionStatus" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding: 15px; background: #f1f5f9; border-radius: 12px;">
                    <div id="statusIcon" style="width: 12px; height: 12px; border-radius: 50%; background: #cbd5e1;"></div>
                    <span id="statusText" style="font-weight: 600; color: #64748b; font-size: 0.9rem;">Aguardando verificação...</span>
                </div>

                <div id="qrCodeContainer" style="display: none; text-align: center; margin-bottom: 20px; padding: 20px; background: white; border: 1px dashed #cbd5e1; border-radius: 12px;">
                    <img id="qrImage" src="" style="max-width: 200px; height: auto; margin-bottom: 10px;">
                    <p class="small text-muted m-0"><i class="fas fa-camera me-1"></i> Escaneie o QR Code com seu WhatsApp</p>
                </div>

                <button type="button" onclick="checkConnection()" id="btnCheck" class="btn btn-light w-100 fw-bold" style="border: 1px solid #e2e8f0; color: #475569;">
                    <i class="fas fa-sync-alt me-2"></i> Atualizar Status
                </button>
            </div>

            <div class="vivensi-card" style="padding: 25px; background: #111827; color: white;">
                <h5 style="color: white; font-weight: 700; display: flex; align-items: center;"><img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #a855f7;"> Como funciona?</h5>
                <ul style="padding-left: 20px; font-size: 0.85rem; opacity: 0.8; margin-top: 15px;">
                    <li class="mb-2">A mensagem chega da Z-API via Webhook.</li>
                    <li class="mb-2">O Vivensi identifica se o cliente já existe.</li>
                    <li class="mb-2">A IA processa a mensagem usando seu <b>Treinamento</b>.</li>
                    <li class="mb-2">O robô responde em segundos, liberando sua equipe humana.</li>
                </ul>
            </div>
        </div>
    </form>
</div>

<style>
.form-control-vivensi {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 12px 16px; width: 100%; transition: all 0.2s;
}
.form-control-vivensi:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }
</style>

<script>
    async function checkConnection() {
        const btn = document.getElementById('btnCheck');
        const statusText = document.getElementById('statusText');
        const statusIcon = document.getElementById('statusIcon');
        const qrContainer = document.getElementById('qrCodeContainer');
        const qrImage = document.getElementById('qrImage');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verificando...';
        statusText.innerText = 'Verificando conexão...';
        statusIcon.style.background = '#cbd5e1'; 

        try {
            const response = await fetch('{{ url("/whatsapp/status") }}');
            const data = await response.json();

            // Reset UI
            statusText.style.color = '#64748b';
            qrContainer.style.display = 'none';

            if (data.status === 'not_configured') {
                statusText.innerText = 'Não configurado (Insira ID e Token)';
                statusIcon.style.background = '#f59e0b'; // Orange
            } else if (data.connected === true) {
                statusText.innerText = 'Conectado (Online)';
                statusText.style.color = '#166534';
                statusIcon.style.background = '#22c55e'; // Green
            } else {
                statusText.innerText = 'Desconectado';
                statusText.style.color = '#991b1b';
                statusIcon.style.background = '#ef4444'; // Red
                
                if (data.qr_code) {
                    statusText.innerText = 'Desconectado. Escaneie o QR Code abaixo:';
                    qrImage.src = data.qr_code;
                    qrContainer.style.display = 'block';
                }
            }

        } catch (e) {
            console.error(e);
            statusText.innerText = 'Erro ao verificar status (Verifique Console)';
            statusIcon.style.background = '#ef4444'; 
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Status';
        }
    }

    // Auto-check on load if configured
    document.addEventListener('DOMContentLoaded', () => {
        const hasConfig = {{ ($config->instance_id && $config->token) ? 'true' : 'false' }};
        if(hasConfig) {
            checkConnection();
        }
    });
</script>
@endsection
