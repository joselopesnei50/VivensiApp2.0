@extends('layouts.app', ['title' => 'Instâncias WhatsApp'])

@section('content')

<style>
.inst-header { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px; margin-bottom:32px; }
.inst-eyebrow { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#10b981; margin-bottom:4px; }
.inst-title { font-size:2rem; font-weight:900; color:#1e293b; letter-spacing:-1px; margin-bottom:4px; line-height:1.1; }
.inst-sub { font-size:.88rem; color:#64748b; font-weight:500; margin:0; }

.inst-btn-ghost {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:10px; font-size:.85rem; font-weight:700;
    border:1.5px solid #e2e8f0; background:white; color:#475569;
    text-decoration:none; transition:all .2s;
}
.inst-btn-ghost:hover { background:#f8fafc; border-color:#cbd5e1; color:#1e293b; }
.inst-btn-primary {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:10px; font-size:.85rem; font-weight:700;
    background:#10b981; color:white; border:none; cursor:pointer; transition:all .2s;
}
.inst-btn-primary:hover { background:#059669; transform:translateY(-1px); }

/* Cards de instância */
.inst-card {
    background:white; border-radius:20px; border:1px solid #f1f5f9;
    box-shadow:0 4px 20px rgba(0,0,0,.04); overflow:hidden;
    transition:transform .2s, box-shadow .2s;
}
.inst-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,.08); }

.inst-card-top {
    background:linear-gradient(135deg,#f0fdf4,#ecfdf5);
    padding:24px; border-bottom:1px solid #e8f5e9;
    display:flex; justify-content:space-between; align-items:flex-start;
}
.inst-avatar {
    width:48px; height:48px; border-radius:14px;
    background:linear-gradient(135deg,#10b981,#059669);
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; color:white; flex-shrink:0;
    box-shadow:0 8px 16px rgba(16,185,129,.3);
}
.inst-name { font-weight:800; color:#1e293b; font-size:.95rem; margin-bottom:3px; }
.inst-number { font-size:.78rem; color:#64748b; font-weight:500; font-family:monospace; }

.badge-connected   { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:.65rem; font-weight:800; padding:4px 10px; border-radius:20px; white-space:nowrap; }
.badge-connecting  { background:#fffbeb; color:#d97706; border:1px solid #fde68a; font-size:.65rem; font-weight:800; padding:4px 10px; border-radius:20px; white-space:nowrap; }
.badge-disconnected{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:.65rem; font-weight:800; padding:4px 10px; border-radius:20px; white-space:nowrap; }

.inst-card-body { padding:20px 24px; }

.inst-progress-label { display:flex; justify-content:space-between; margin-bottom:6px; font-size:.75rem; font-weight:700; }
.inst-progress-bar { height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; margin-bottom:16px; }
.inst-progress-fill { height:100%; border-radius:3px; transition:width .5s; }

.inst-meta { display:flex; gap:16px; font-size:.72rem; color:#94a3b8; font-weight:600; margin-bottom:20px; }

.inst-actions { display:flex; gap:8px; }
.inst-action-btn {
    flex:1; padding:9px; border-radius:10px; font-size:.78rem; font-weight:700;
    display:flex; align-items:center; justify-content:center; gap:6px;
    cursor:pointer; border:none; transition:all .15s; text-decoration:none;
}
.inst-action-btn.green { background:#f0fdf4; color:#059669; }
.inst-action-btn.green:hover { background:#dcfce7; }
.inst-action-btn.indigo { background:#eff6ff; color:#4f46e5; }
.inst-action-btn.indigo:hover { background:#dbeafe; }
.inst-action-btn.red { background:#fef2f2; color:#dc2626; padding:9px 14px; flex:0; }
.inst-action-btn.red:hover { background:#fee2e2; }

/* Empty state */
.inst-empty {
    text-align:center; padding:80px 20px;
    background:white; border-radius:20px; border:2px dashed #e2e8f0;
}
.inst-empty-icon {
    width:80px; height:80px; border-radius:50%;
    background:#f0fdf4; display:flex; align-items:center; justify-content:center;
    margin:0 auto 20px; font-size:2rem; color:#10b981;
}

/* Modal */
.inst-modal-body { padding:28px; }
.inst-field-label { font-size:.82rem; font-weight:700; color:#1e293b; margin-bottom:8px; display:block; }
.inst-field {
    width:100%; padding:12px 16px; border:2px solid #f1f5f9;
    border-radius:12px; font-size:.9rem; color:#1e293b; box-sizing:border-box;
    transition:border-color .2s; outline:none;
}
.inst-field:focus { border-color:#10b981; }
.inst-qr-wrap { background:#f8fafc; border-radius:16px; padding:20px; text-align:center; }
</style>

{{-- HEADER --}}
<div class="inst-header">
    <div>
        <p class="inst-eyebrow"><i class="fab fa-whatsapp me-1"></i> Omnichannel</p>
        <h1 class="inst-title">Instâncias WhatsApp</h1>
        <p class="inst-sub">Conecte números via Evolution API para automações, atendimento e disparos em massa.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('whatsapp.broadcast.index') }}" class="inst-btn-ghost">
            <i class="fas fa-paper-plane"></i> Disparos
        </a>
        <button onclick="openNewInstanceModal()" class="inst-btn-primary">
            <i class="fas fa-plus"></i> Nova Instância
        </button>
    </div>
</div>

{{-- LISTAGEM --}}
@if($instances->isEmpty())
    <div class="inst-empty">
        <div class="inst-empty-icon"><i class="fab fa-whatsapp"></i></div>
        <h3 style="font-weight:800; color:#1e293b; font-size:1.3rem; margin-bottom:8px;">Nenhuma instância conectada</h3>
        <p style="color:#64748b; font-size:.9rem; max-width:380px; margin:0 auto 28px; line-height:1.7;">
            Conecte um número WhatsApp via QR Code para usar automações, chatbot e disparos em massa.
        </p>
        <button onclick="openNewInstanceModal()" class="inst-btn-primary" style="font-size:.9rem; padding:12px 28px;">
            <i class="fas fa-plug me-1"></i> Conectar Agora
        </button>
    </div>
@else
    <div class="row g-4">
        @foreach($instances as $instance)
        @php
            $pct   = $instance->daily_limit > 0 ? min(100, intval(($instance->messages_sent_today / $instance->daily_limit) * 100)) : 0;
            $color = $pct > 80 ? '#ef4444' : ($pct > 50 ? '#f59e0b' : '#10b981');
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="inst-card">
                <div class="inst-card-top">
                    <div style="display:flex; gap:14px; align-items:center; flex:1; min-width:0;">
                        <div class="inst-avatar"><i class="fab fa-whatsapp"></i></div>
                        <div style="min-width:0;">
                            <div class="inst-name">{{ Str::limit($instance->instance_name, 22) }}</div>
                            <div class="inst-number">{{ $instance->phone_number ?: 'Aguardando número...' }}</div>
                        </div>
                    </div>
                    @if($instance->status === 'open')
                        <span class="badge-connected"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Online</span>
                    @elseif($instance->status === 'connecting')
                        <span class="badge-connecting"><i class="fas fa-spinner fa-spin me-1"></i>Conectando</span>
                    @else
                        <span class="badge-disconnected"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Offline</span>
                    @endif
                </div>

                <div class="inst-card-body">
                    <div class="inst-progress-label">
                        <span style="color:#475569;">Anti-Ban — uso diário</span>
                        <span style="color:#1e293b; font-weight:800;">{{ $instance->messages_sent_today }} / {{ $instance->daily_limit }}</span>
                    </div>
                    <div class="inst-progress-bar">
                        <div class="inst-progress-fill" style="width:{{ $pct }}%; background:{{ $color }};"></div>
                    </div>

                    <div class="inst-meta">
                        <span><i class="fas fa-clock me-1"></i>Delay 1.5–4s</span>
                        <span><i class="fas fa-calendar me-1"></i>{{ $instance->created_at->format('d/m/Y') }}</span>
                    </div>

                    <div class="inst-actions">
                        @if($instance->status !== 'open')
                            <button onclick="checkStatus('{{ $instance->id }}')" class="inst-action-btn indigo">
                                <i class="fas fa-qrcode"></i> Escanear QR
                            </button>
                        @else
                            <a href="{{ route('whatsapp.broadcast.index') }}" class="inst-action-btn green">
                                <i class="fas fa-paper-plane"></i> Usar Instância
                            </a>
                        @endif
                        <button onclick="confirmDelete('{{ $instance->id }}')" class="inst-action-btn red" title="Excluir instância">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- MODAL --}}
<div class="modal fade" id="newInstanceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:20px; border:1px solid #f1f5f9; box-shadow:0 25px 60px rgba(0,0,0,.15);">
            <div style="padding:24px 28px 0; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h5 style="font-weight:900; color:#1e293b; font-size:1.1rem; margin:0;">
                        <i class="fab fa-whatsapp me-2" style="color:#10b981;"></i>Conectar WhatsApp
                    </h5>
                    <p style="color:#64748b; font-size:.8rem; margin:4px 0 0;">Escaneie o QR Code com seu celular.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="inst-modal-body">
                {{-- Form --}}
                <div id="create-instance-form">
                    <label class="inst-field-label">Nome da instância</label>
                    <input type="text" id="instanceName" class="inst-field" placeholder="Ex: Vendas — Principal">
                    <p style="color:#94a3b8; font-size:.75rem; margin:8px 0 20px;">Apenas para identificação interna no sistema.</p>
                    <button onclick="createInstance()" class="inst-btn-primary" style="width:100%; justify-content:center; padding:14px; font-size:.9rem;">
                        <i class="fas fa-qrcode me-2"></i>Gerar QR Code
                    </button>
                </div>

                {{-- QR View --}}
                <div id="qr-code-view" style="display:none; text-align:center;">
                    <div class="inst-qr-wrap" style="margin-bottom:16px;">
                        <img id="qr-code-image" src="" alt="QR Code"
                             style="width:220px; height:220px; border-radius:12px; display:block; margin:0 auto;">
                    </div>
                    <p style="font-size:.82rem; color:#f59e0b; font-weight:700; margin:0;">
                        <i class="fas fa-spinner fa-spin me-1"></i>Aguardando conexão...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';
const webHeaders = { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' };
let pollInterval = null, currentInstanceId = null, pollAttempts = 0;
const MAX_POLL = 36;

function openNewInstanceModal() {
    document.getElementById('create-instance-form').style.display = 'block';
    document.getElementById('qr-code-view').style.display = 'none';
    document.getElementById('instanceName').value = '';
    new bootstrap.Modal(document.getElementById('newInstanceModal')).show();
}

async function createInstance() {
    const name = document.getElementById('instanceName').value.trim();
    if (!name) { alert('Informe um nome para a instância.'); return; }
    const btn = document.querySelector('#create-instance-form button');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Gerando...';
    btn.disabled = true;
    try {
        const r = await fetch('/whatsapp/instances', { method:'POST', headers:webHeaders, body:JSON.stringify({name}) });
        const d = await r.json();
        if (r.ok && d.instance) {
            currentInstanceId = d.instance.id; pollAttempts = 0;
            document.getElementById('create-instance-form').style.display = 'none';
            document.getElementById('qr-code-view').style.display = 'block';
            fetchQrCode();
            pollInterval = setInterval(fetchQrCode, 5000);
        } else {
            alert('Não foi possível criar a instância:\n' + (d.error || d.message || 'Erro desconhecido') + (d.details ? '\n\n' + d.details : ''));
            btn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar QR Code'; btn.disabled = false;
        }
    } catch(e) { alert('Falha de comunicação com o servidor.'); btn.innerHTML = '<i class="fas fa-qrcode me-2"></i>Gerar QR Code'; btn.disabled = false; }
}

function setQrMsg(msg, color='#f59e0b') {
    const el = document.querySelector('#qr-code-view p');
    if (el) { el.innerHTML = msg; el.style.color = color; }
}

async function fetchQrCode() {
    if (!currentInstanceId) return;
    if (++pollAttempts > MAX_POLL) { clearInterval(pollInterval); setQrMsg('<i class="fas fa-exclamation-triangle me-1"></i>Timeout — feche e tente novamente.','#ef4444'); return; }
    try {
        const r = await fetch(`/whatsapp/instances/${currentInstanceId}/connect`, { method:'POST', headers:webHeaders });
        const d = await r.json();
        if (d.qrcode || d.base64) {
            document.getElementById('qr-code-image').src = d.qrcode || d.base64;
            setQrMsg('<i class="fas fa-spinner fa-spin me-1"></i>Aguardando leitura...','#f59e0b');
        } else if (d.status === 'open' || d.state === 'open') {
            clearInterval(pollInterval);
            document.getElementById('qr-code-view').innerHTML = `
                <div style="color:#10b981;font-size:3rem;margin-bottom:12px;"><i class="fas fa-check-circle"></i></div>
                <h5 style="font-weight:800;color:#1e293b;">Conectado!</h5>
                <p style="color:#64748b;font-size:.85rem;">Recarregando...</p>`;
            setTimeout(() => window.location.reload(), 2000);
        } else if (d.error) {
            setQrMsg(`<i class="fas fa-clock me-1"></i>${d.error} (${pollAttempts}/${MAX_POLL})`,'#f59e0b');
        }
    } catch(e) { setQrMsg('<i class="fas fa-exclamation-circle me-1"></i>Erro de comunicação','#ef4444'); }
}

document.getElementById('newInstanceModal').addEventListener('hidden.bs.modal', () => { if(pollInterval) clearInterval(pollInterval); currentInstanceId = null; });

async function confirmDelete(id) {
    if (!confirm('Excluir esta instância? Ação irreversível.')) return;
    const r = await fetch(`/whatsapp/instances/${id}`, { method:'DELETE', headers:webHeaders });
    if (r.ok) window.location.reload();
    else { const d = await r.json().catch(()=>{}); alert('Erro: ' + (d?.error || d?.message || r.status)); }
}

function checkStatus(id) {
    currentInstanceId = id; pollAttempts = 0;
    document.getElementById('create-instance-form').style.display = 'none';
    document.getElementById('qr-code-view').style.display = 'block';
    document.getElementById('qr-code-image').src = '';
    new bootstrap.Modal(document.getElementById('newInstanceModal')).show();
    fetchQrCode(); pollInterval = setInterval(fetchQrCode, 5000);
}
</script>
@endsection
