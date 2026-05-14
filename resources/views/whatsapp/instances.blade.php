@extends('layouts.app', ['title' => 'Instâncias de Mensageria (WhatsApp)'])

@section('content')
<div class="whatsapp-dashboard-container" style="padding: 24px 36px;">
    
    <!-- HEADER DA PÁGINA -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);">
                    <i class="fab fa-whatsapp" style="font-size: 1.6rem; color: white;"></i>
                </div>
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 800; color: white; display: flex; align-items: center; gap: 12px; margin: 0; letter-spacing: -0.5px;">
                        Instâncias de WhatsApp
                        <span style="font-size: 0.6rem; background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); padding: 4px 10px; border-radius: 30px; vertical-align: middle; text-transform: uppercase; letter-spacing: 1px; font-weight: 900;">Dual-Channel</span>
                    </h1>
                    <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin: 4px 0 0 0; font-weight: 500;">Gerencie conexões da Evolution API para envio de mensagens em massa (Fallback) e automações.</p>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 12px;">
            <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-dark" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; font-size: 0.85rem; font-weight: 600; padding: 10px 18px; color: white;">
                <i class="fas fa-paper-plane" style="color: #60a5fa; margin-right: 8px;"></i> Disparos
            </a>
            <button onclick="openNewInstanceModal()" class="btn btn-primary" style="background: linear-gradient(135deg, #4f46e5, #4338ca); border: none; border-radius: 10px; font-size: 0.85rem; font-weight: 700; padding: 10px 20px; box-shadow: 0 10px 20px rgba(79,70,229,0.2);">
                <i class="fas fa-plus" style="margin-right: 8px;"></i> Nova Instância (QR Code)
            </button>
        </div>
    </div>

    <!-- LISTAGEM DE INSTÂNCIAS -->
    @if($instances->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 20px; margin-top: 20px;">
            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fab fa-whatsapp" style="font-size: 2rem; color: rgba(255,255,255,0.2);"></i>
            </div>
            <h3 style="color: white; font-weight: 700; font-size: 1.2rem;">Nenhuma Instância Conectada</h3>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.95rem; max-width: 400px; margin: 10px auto 25px;">Para usar os recursos de automação de texto e contingência ao Cloud API, conecte seu aparelho via QR Code.</p>
            <button onclick="openNewInstanceModal()" class="btn btn-success" style="background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; font-weight: 700; padding: 12px 24px;">
                Conectar Aparelho Agora
            </button>
        </div>
    @else
        <div class="row">
            @foreach($instances as $instance)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="instance-card" style="background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; position: relative; overflow: hidden;">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="width: 42px; height: 42px; background: rgba(16,185,129,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fab fa-whatsapp" style="color: #10b981; font-size: 1.3rem;"></i>
                                </div>
                                <div>
                                    <h4 style="color: white; font-weight: 800; font-size: 1.1rem; margin: 0;">{{ $instance->instance_name }}</h4>
                                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.4); font-family: monospace;">{{ $instance->phone_number ?: 'Aguardando Número...' }}</div>
                                </div>
                            </div>
                            
                            <!-- Badges de Status -->
                            @if($instance->status === 'open')
                                <span style="background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">CONECTADO</span>
                            @elseif($instance->status === 'connecting')
                                <span style="background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;"><i class="fas fa-spinner fa-spin"></i> CONECTANDO</span>
                            @else
                                <span style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">DESCONECTADO</span>
                            @endif
                        </div>

                        <!-- AntiBan Limits Visão Geral -->
                        <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: rgba(255,255,255,0.5); font-size: 0.8rem; font-weight: 600;">Aquecimento (Anti-Ban)</span>
                                <span style="color: white; font-size: 0.8rem; font-weight: 800;">Hoje: {{ $instance->messages_sent_today }} / {{ $instance->daily_limit }}</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                                @php 
                                    $percent = $instance->daily_limit > 0 ? min(100, intval(($instance->messages_sent_today / $instance->daily_limit) * 100)) : 0;
                                    $barColor = $percent > 80 ? '#ef4444' : ($percent > 50 ? '#f59e0b' : '#10b981');
                                @endphp
                                <div style="height: 100%; width: {{ $percent }}%; background: {{ $barColor }}; border-radius: 3px;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 0.75rem;">
                                <div style="color: rgba(255,255,255,0.4);"><i class="fas fa-history"></i> Delay: 1.5s a 4.0s</div>
                                <div style="color: rgba(255,255,255,0.4);"><i class="fas fa-calendar-alt"></i> Criado: {{ $instance->created_at->format('d/m/Y') }}</div>
                            </div>
                        </div>

                        <!-- Ações da Instância -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @if($instance->status !== 'open')
                                <button onclick="checkStatus('{{ $instance->id }}')" class="btn btn-sm" style="background: rgba(79,70,229,0.1); color: #818cf8; border: 1px solid rgba(79,70,229,0.3); border-radius: 8px; flex: 1; font-weight: 600;">
                                    <i class="fas fa-qrcode"></i> Scan QR
                                </button>
                            @else
                                <button onclick="window.location.href='{{ route('whatsapp.broadcast.index') }}'" class="btn btn-sm" style="background: rgba(16,185,129,0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; flex: 1; font-weight: 600;">
                                    <i class="fas fa-bullhorn"></i> Usar Instância
                                </button>
                            @endif
                            <button onclick="confirmDelete('{{ $instance->id }}')" class="btn btn-sm" style="background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 4px 12px;" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal Criar/Escanear Instância -->
<div class="modal fade" id="newInstanceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px 24px;">
                <h5 class="modal-title" style="color: white; font-weight: 800; font-size: 1.1rem;"><i class="fab fa-whatsapp" style="color: #10b981; margin-right: 8px;"></i> Conectar Aparelho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px; text-align: center;">
                
                <!-- Criar Form -->
                <div id="create-instance-form">
                    <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 20px; text-align: left;">Dê um nome para identificar este aparelho no sistema (ex: "Celular Recepção").</p>
                    <div class="form-group text-start mb-4">
                        <label style="color: rgba(255,255,255,0.7); font-weight: 600; font-size: 0.8rem; margin-bottom: 6px;">Nome da Instância</label>
                        <input type="text" id="instanceName" class="form-control" placeholder="Financeiro - Número 1" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 10px; padding: 12px;">
                    </div>
                    <button onclick="createInstance()" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 10px; font-weight: 700; padding: 14px;">
                        Gerar QR Code de Conexão
                    </button>
                </div>

                <!-- QR Code View (Hidden initially) -->
                <div id="qr-code-view" style="display: none;">
                    <p style="color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 15px; font-weight: 600;">Abra o WhatsApp no celular e escaneie o código abaixo:</p>
                    
                    <div style="background: white; padding: 15px; border-radius: 16px; display: inline-block; margin-bottom: 20px;">
                        <img id="qr-code-image" src="" alt="QR Code" style="width: 220px; height: 220px;">
                    </div>

                    <p style="color: #f59e0b; font-size: 0.8rem; font-weight: 600; margin-bottom: 0;">
                        <i class="fas fa-spinner fa-spin"></i> Aguardando conexão...
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    const CSRF = '{{ csrf_token() }}';
    const webHeaders = {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
    };

    let pollInterval = null;
    let currentInstanceId = null;
    let pollAttempts = 0;
    const MAX_POLL_ATTEMPTS = 36; // 36 × 5s = 3 minutos máximo

    function openNewInstanceModal() {
        document.getElementById('create-instance-form').style.display = 'block';
        document.getElementById('qr-code-view').style.display = 'none';
        document.getElementById('instanceName').value = '';
        var modal = new bootstrap.Modal(document.getElementById('newInstanceModal'));
        modal.show();
    }

    async function createInstance() {
        const name = document.getElementById('instanceName').value.trim();
        if (!name) {
            alert('Por favor, informe um nome para a instância.');
            return;
        }

        const btn = document.querySelector('#create-instance-form button');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
        btn.disabled = true;

        try {
            const response = await fetch(`/whatsapp/instances`, {
                method: 'POST',
                headers: webHeaders,
                body: JSON.stringify({ name: name })
            });

            const data = await response.json();

            if (response.ok && data.instance) {
                currentInstanceId = data.instance.id;
                pollAttempts = 0;
                document.getElementById('create-instance-form').style.display = 'none';
                document.getElementById('qr-code-view').style.display = 'block';
                fetchQrCode();
                pollInterval = setInterval(fetchQrCode, 5000);
            } else {
                const errMsg = data.error || data.message || 'Sem resposta do servidor';
                const errDet = data.details ? '\n\nDetalhes: ' + data.details : '';
                alert('Não foi possível criar a instância:\n' + errMsg + errDet);
                btn.innerHTML = oldText;
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Falha na comunicação com o servidor.');
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    function setQrStatus(msg, color = '#f59e0b') {
        const el = document.querySelector('#qr-code-view > p');
        if (el) { el.innerHTML = msg; el.style.color = color; }
    }

    async function fetchQrCode() {
        if (!currentInstanceId) return;

        pollAttempts++;
        if (pollAttempts > MAX_POLL_ATTEMPTS) {
            clearInterval(pollInterval);
            setQrStatus('<i class="fas fa-exclamation-triangle"></i> Timeout: QR não gerado após 3 minutos. Feche e tente novamente.', '#ef4444');
            return;
        }

        try {
            const response = await fetch(`/whatsapp/instances/${currentInstanceId}/connect`, {
                method: 'POST',
                headers: webHeaders,
            });
            const data = await response.json();

            if (data.qrcode || data.base64) {
                document.getElementById('qr-code-image').src = data.qrcode || data.base64;
                setQrStatus('<i class="fas fa-spinner fa-spin"></i> Aguardando conexão...', '#f59e0b');
            } else if (data.status === 'open' || data.state === 'open') {
                clearInterval(pollInterval);
                document.getElementById('qr-code-view').innerHTML = `
                    <div style="color: #10b981; font-size: 3rem; margin-bottom: 10px;"><i class="fas fa-check-circle"></i></div>
                    <h5 style="color: white; font-weight: 800;">Conectado com Sucesso!</h5>
                    <p style="color: rgba(255,255,255,0.6);">A página será recarregada.</p>
                `;
                setTimeout(() => window.location.reload(), 2000);
            } else if (data.error) {
                setQrStatus(`<i class="fas fa-clock"></i> ${data.error} (tentativa ${pollAttempts})`, '#f59e0b');
            }
        } catch (err) {
            console.error('Erro buscando QR Code', err);
            setQrStatus('<i class="fas fa-exclamation-circle"></i> Erro de comunicação com servidor', '#ef4444');
        }
    }

    document.getElementById('newInstanceModal').addEventListener('hidden.bs.modal', function () {
        if (pollInterval) clearInterval(pollInterval);
        currentInstanceId = null;
    });

    async function confirmDelete(id) {
        if (!confirm('Tem certeza que deseja excluir esta instância? Esta ação é irreversível.')) return;

        try {
            const response = await fetch(`/whatsapp/instances/${id}`, {
                method: 'DELETE',
                headers: webHeaders,
            });

            if (response.ok) {
                window.location.reload();
            } else {
                const data = await response.json().catch(() => ({}));
                alert('Erro ao excluir: ' + (data.message || data.error || 'HTTP ' + response.status));
            }
        } catch (err) {
            alert('Falha na comunicação: ' + err.message);
        }
    }

    function checkStatus(id) {
        currentInstanceId = id;
        pollAttempts = 0;
        document.getElementById('create-instance-form').style.display = 'none';
        document.getElementById('qr-code-view').style.display = 'block';
        document.getElementById('qr-code-image').src = '';
        var modal = new bootstrap.Modal(document.getElementById('newInstanceModal'));
        modal.show();
        fetchQrCode();
        pollInterval = setInterval(fetchQrCode, 5000);
    }
</script>
@endsection
