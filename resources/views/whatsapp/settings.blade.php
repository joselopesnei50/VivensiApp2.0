@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Omnichannel & Chatbot</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Treinamento da IA & WhatsApp</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Configure seu robô de atendimento e sua instância Evolution API.</p>
    </div>
</div>

<form action="{{ url('/whatsapp/settings') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-7">
            
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
        </div>

        <div class="col-md-5">
            <!-- Evolution API Config Section -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #25d366;">
                <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fab fa-whatsapp me-2" style="color: #25d366;"></i> Conexão Evolution API
                </h4>
                
                <div class="form-group mb-4">
                    <label class="fw-700 mb-2 small">Nome Único da Instância</label>
                    <input type="text" name="evolution_instance_name" value="{{ $contextModel->evolution_instance_name ?? '' }}" class="form-control-vivensi" placeholder="Ex: OngAcessoBrasil" required>
                    <div class="small text-muted mt-1">Nós criaremos esta nova instância automaticamente no servidor ao Salvar.</div>
                </div>

                <div class="form-group mb-4" style="display: none;">
                    <label class="fw-700 mb-2 small">Client Token Webhook (Segurança)</label>
                    <input type="text" name="client_token" value="{{ $config->client_token }}" class="form-control-vivensi" placeholder="Para segurança do Webhook">
                </div>

                <div class="alert alert-warning" style="font-size: 0.8rem; border-radius: 12px; border: none; background: #fffbeb; color: #92400e;">
                    <i class="fas fa-link me-1"></i> <b>URL de Webhook (Configurada Automaticamente):</b><br>
                    <code style="background: rgba(0,0,0,0.05); padding: 2px 5px; border-radius: 4px; display: block; margin-top: 5px;">{{ url('/api/whatsapp/webhook') }}</code>
                </div>
            </div>

            <!-- Anti-ban & Compliance Controls -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #f59e0b;">
                <h4 style="margin: 0 0 14px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-shield-halved me-2" style="color: #f59e0b;"></i> Segurança, LGPD e Anti‑Ban
                </h4>
                <div class="small text-muted mb-3">
                    Regras para reduzir risco de bloqueio e garantir consentimento (opt‑in) e STOP.
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="outbound_enabled" value="1" id="outboundEnabled" {{ ($config->outbound_enabled ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="outboundEnabled">Permitir envios (outbound)</label>
                    <div class="small text-muted">Desative para impedir qualquer envio pelo sistema (manual e IA).</div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="require_opt_in" value="1" id="requireOptIn" {{ ($config->require_opt_in ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="requireOptIn">Exigir opt‑in para enviar</label>
                    <div class="small text-muted">O opt‑in é registrado automaticamente quando o cliente manda a primeira mensagem. Para contatos “iniciados”, exija consentimento explícito.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="enforce_24h_window" value="1" id="enforce24h" {{ ($config->enforce_24h_window ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="enforce24h">Aplicar janela de 24h</label>
                    <div class="small text-muted">Fora da janela, o sistema bloqueia envios “livres” para reduzir risco. Ideal para atendimento.</div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="allow_templates_outside_window" value="1" id="allowTemplatesOutside" {{ ($config->allow_templates_outside_window ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="allowTemplatesOutside">Permitir templates fora da janela</label>
                    <div class="small text-muted">Mensagens “aprovadas” (ex.: respostas rápidas) podem ser enviadas mesmo fora da janela.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-700 mb-2 small">Limite de envios por minuto</label>
                        <input type="number" min="1" max="120" name="max_outbound_per_minute" value="{{ (int) ($config->max_outbound_per_minute ?? 12) }}" class="form-control-vivensi">
                        <div class="small text-muted mt-1">Aplicado por tenant e por contato.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-700 mb-2 small">Cadência mínima (segundos)</label>
                        <input type="number" min="0" max="60" name="min_outbound_delay_seconds" value="{{ (int) ($config->min_outbound_delay_seconds ?? 2) }}" class="form-control-vivensi">
                        <div class="small text-muted mt-1">Evita rajadas e comportamento “robótico”.</div>
                    </div>
                </div>

                <div class="alert alert-info mt-4" style="font-size: 0.8rem; border-radius: 12px; border: none; background: #eff6ff; color: #1d4ed8;">
                    <b>STOP:</b> se o cliente enviar “stop / sair / cancelar / parar”, o sistema registra opt‑out e bloqueia novos envios.
                </div>
            </div>

            <!-- Connection Status & Pairing Code Scanner -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #3b82f6;">
                <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-wifi me-2" style="color: #3b82f6;"></i> Status da Conexão
                </h4>
                
                <div id="connectionStatus" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding: 15px; background: #f1f5f9; border-radius: 12px;">
                    <div id="statusIcon" style="width: 12px; height: 12px; border-radius: 50%; background: #cbd5e1;"></div>
                    <span id="statusText" style="font-weight: 600; color: #64748b; font-size: 0.9rem;">Aguardando verificação...</span>
                </div>

                <!-- Pairing Code Section -->
                <div id="pairingCodeSection" style="display: none; background: white; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <h5 style="font-size: 1rem; color: #334155; margin-bottom: 15px; font-weight: 700;">Conectar WhatsApp (Aparelho Adicional)</h5>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;">Para evitar bloqueios de IP, utilizamos o método de <b>Vincular com número de telefone</b> em vez do QR Code.</p>
                    
                    <div class="form-group mb-3">
                        <label class="fw-700 mb-2 small">Número do WhatsApp (com DDD)</label>
                        <input type="text" id="pairingPhoneInput" class="form-control-vivensi" placeholder="Ex: 11999999999" value="{{ $contextModel->contact_phone ?? '' }}">
                    </div>
                    
                    <button type="button" id="btnRequestPairingCode" onclick="requestPairingCode()" class="btn btn-dark w-100 fw-bold" style="padding: 10px; border-radius: 8px;">
                        Gerar Código de Pareamento
                    </button>

                    <div id="pairingCodeDisplay" style="display: none; margin-top: 20px; text-align: center; border-radius: 8px; background: #f8fafc; padding: 20px; border: 2px dashed #3b82f6;">
                         <p style="font-size: 0.85rem; color: #3b82f6; margin-bottom: 10px; font-weight: bold;">Digite este código no seu WhatsApp:</p>
                         <div id="thePairingCode" style="font-size: 2rem; letter-spacing: 5px; font-weight: 800; color: #1e293b; user-select: all;">---- ----</div>
                         <p style="font-size: 0.75rem; color: #64748b; margin-top: 15px; margin-bottom: 0;">Abra o WhatsApp > Aparelhos Conectados > Vincular um Aparelho > Conectar com número de telefone</p>
                    </div>
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
                    <li class="mb-2">A mensagem chega da Evolution API via Webhook.</li>
                    <li class="mb-2">O Vivensi identifica se o cliente já existe.</li>
                    <li class="mb-2">A IA processa a mensagem usando seu <b>Treinamento</b>.</li>
                    <li class="mb-2">O robô responde em segundos, liberando sua equipe humana.</li>
                </ul>
            </div>
            
            <button type="submit" class="btn-premium" style="width: 100%; padding: 15px; margin-top: 20px;">
                <i class="fas fa-save me-2"></i> Salvar Configurações e Instância
            </button>
        </div>
    </div>
</form>

<style>
.form-control-vivensi {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 12px 16px; width: 100%; transition: all 0.2s;
}
.form-control-vivensi:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }
</style>

<script>
    let statusPollInterval = null;

    async function checkConnection(poll = false) {
        const btn = document.getElementById('btnCheck');
        const statusText = document.getElementById('statusText');
        const statusIcon = document.getElementById('statusIcon');
        const qrContainer = document.getElementById('qrCodeContainer');
        const pairingSection = document.getElementById('pairingCodeSection');
        const pairingDisplay = document.getElementById('pairingCodeDisplay');
        const qrImage = document.getElementById('qrImage');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verificando...';
        statusText.innerText = 'Verificando conexão...';
        statusIcon.style.background = '#cbd5e1';

        // Clear any previous polling
        if (statusPollInterval) { clearInterval(statusPollInterval); statusPollInterval = null; }

        let attempts = 0;
        const maxAttempts = poll ? 5 : 1; // Reduces polling attempts for Pairing Code as it doesn't need constant refreshing like QR

        async function doCheck() {
            attempts++;
            try {
                const response = await fetch('{{ url("/whatsapp/status") }}');
                const data = await response.json();

                statusText.style.color = '#64748b';
                qrContainer.style.display = 'none';
                pairingSection.style.display = 'none'; // Assume connected initially

                if (data.status === 'not_configured') {
                    statusText.innerText = 'Não configurado (Salve o Nome da Instância primeiro).';
                    statusIcon.style.background = '#f59e0b';
                    clearInterval(statusPollInterval); statusPollInterval = null;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Status';
                } else if (data.connected === true) {
                    statusText.innerText = 'Conectado ✅ Online!';
                    statusText.style.color = '#166534';
                    statusIcon.style.background = '#22c55e';
                    clearInterval(statusPollInterval); statusPollInterval = null;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Status';
                } else {
                    // Not connected, show pairing UI
                    statusText.innerText = 'Aguardando Pareamento...';
                    statusIcon.style.background = '#f59e0b';
                    pairingSection.style.display = 'block';
                    
                    if (data.qr_code) {
                         // Fallback for QR code just in case
                         qrImage.src = data.qr_code;
                         qrContainer.style.display = 'none'; // Ensure QR is hidden, preference for Pairing
                    }
                    
                    if (attempts >= maxAttempts) {
                        clearInterval(statusPollInterval); statusPollInterval = null;
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Status';
                    }
                }
            } catch (e) {
                console.error(e);
                statusText.innerText = 'Erro ao verificar status.';
                statusIcon.style.background = '#ef4444';
                clearInterval(statusPollInterval); statusPollInterval = null;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i> Atualizar Status';
            }
        }

        await doCheck();
        if (poll && attempts < maxAttempts) {
            statusPollInterval = setInterval(doCheck, 5000); // Poll less frequently for Pairing
        }
    }

    async function requestPairingCode() {
        const btn = document.getElementById('btnRequestPairingCode');
        const phone = document.getElementById('pairingPhoneInput').value;
        const display = document.getElementById('pairingCodeDisplay');
        const codeElement = document.getElementById('thePairingCode');
        
        if (!phone || phone.length < 10) {
            alert("Por favor, informe um número de WhatsApp válido com DDD.");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
        display.style.display = 'none';

        try {
            const response = await fetch('{{ url("/whatsapp/pairing-code") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ phone: phone })
            });
            
            const data = await response.json();
            
            if (response.ok && data.pairing_code) {
                // Format code: ABCD-WXYZ
                const code = data.pairing_code;
                const formattedCode = code.match(/.{1,4}/g).join('-');
                codeElement.innerText = formattedCode;
                display.style.display = 'block';
                
                // Start polling to detect successful connection
                checkConnection(true);
            } else {
                alert(data.error || "Erro ao gerar código de pareamento.");
            }
        } catch (e) {
            console.error(e);
            alert("Erro de comunicação com o servidor.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Gerar Código de Pareamento';
        }
    }

    // Auto-check with polling on load if configured
    document.addEventListener('DOMContentLoaded', () => {
        const hasConfig = {{ !empty($contextModel->evolution_instance_name) ? 'true' : 'false' }};
        if (hasConfig) {
            checkConnection(false); // Don't poll initially until they request code or check status
        }
    });
</script>
@endsection
