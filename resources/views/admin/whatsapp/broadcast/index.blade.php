@extends('layouts.app')

@section('title', 'WhatsApp — Disparo em Massa')

@push('styles')
<style>
    .broadcast-sidebar-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .broadcast-sidebar-card h6 {
        font-size: 0.8rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 14px;
    }
    .instance-status-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 12px 14px;
    }
    .instance-status-badge.disconnected {
        background: #fef9ec;
        border-color: #fde68a;
    }
    .instance-status-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        background: #10b981;
        flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }
        50% { box-shadow: 0 0 0 6px rgba(16,185,129,0.1); }
    }
    .stat-box {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        color: #fff;
        margin-bottom: 20px;
    }
    .stat-box .stat-number { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .stat-box .stat-label { font-size: 0.8rem; opacity: 0.7; margin-top: 4px; }
    .compose-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .compose-card-header {
        padding: 20px 24px 0;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 16px;
    }
    .compose-card-body { padding: 24px; }
    .section-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 10px;
    }
    .audience-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s;
        flex: 1;
    }
    .audience-option:has(input:checked) {
        border-color: #4f46e5;
        background: #f5f3ff;
    }
    .audience-option input { accent-color: #4f46e5; }
    .spintax-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #3b82f6;
        border: 1px solid #bfdbfe;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
    }
    .message-textarea {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        font-size: 0.9rem;
        resize: vertical;
        transition: border-color 0.15s;
        width: 100%;
        min-height: 140px;
    }
    .message-textarea:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
    }
    .char-counter { font-size: 0.75rem; color: #94a3b8; }
    .char-counter.warn { color: #f59e0b; }
    .char-counter.danger { color: #ef4444; }
    .cadence-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }
    .cadence-option:has(input:checked) {
        border-color: #4f46e5;
        background: #f5f3ff;
        color: #4f46e5;
        font-weight: 600;
    }
    .cadence-option .cadence-risk {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
    }
    .risk-high { background: #fee2e2; color: #dc2626; }
    .risk-med  { background: #fef3c7; color: #d97706; }
    .risk-low  { background: #dcfce7; color: #16a34a; }
    /* WhatsApp preview */
    .wa-preview-wrap {
        background: #e5ddd5;
        border-radius: 12px;
        padding: 16px;
        min-height: 120px;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9b99a' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .wa-bubble {
        background: #fff;
        border-radius: 0 10px 10px 10px;
        padding: 10px 14px;
        max-width: 85%;
        font-size: 0.9rem;
        color: #1a1a1a;
        line-height: 1.5;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        white-space: pre-wrap;
        word-break: break-word;
        position: relative;
    }
    .wa-bubble-time {
        font-size: 0.65rem;
        color: #94a3b8;
        text-align: right;
        margin-top: 4px;
    }
    .wa-placeholder {
        color: #94a3b8;
        font-size: 0.85rem;
        text-align: center;
        padding: 20px 0;
    }
    .spintax-helper {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 12px 14px;
        margin-top: 10px;
        font-size: 0.78rem;
        color: #64748b;
    }
    .spintax-helper code {
        background: #e0e7ff;
        color: #4338ca;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 0.78rem;
    }
    .spintax-tag {
        display: inline-block;
        background: #e0e7ff;
        color: #4338ca;
        border-radius: 4px;
        padding: 1px 7px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        margin: 2px;
        transition: background 0.1s;
    }
    .spintax-tag:hover { background: #c7d2fe; }
    .launch-btn {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 14px 32px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: opacity 0.15s, transform 0.1s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .launch-btn:hover { opacity: 0.92; transform: translateY(-1px); }
    .launch-btn:active { transform: translateY(0); }
    .launch-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .import-zone {
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .import-zone:hover { border-color: #4f46e5; background: #f5f3ff; }
    .csv-example {
        background: #f8fafc;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.75rem;
        color: #64748b;
        font-family: monospace;
        margin-top: 10px;
    }
</style>
@endpush

@section('content')

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-800" style="color:#1e293b;">
            <i class="fas fa-rocket me-2" style="color:#4f46e5;"></i> Disparo em Massa
        </h4>
        <p class="mb-0 text-muted small">Crie e envie campanhas de WhatsApp para sua base de contatos.</p>
    </div>
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}" class="text-muted">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('whatsapp.chat') }}" class="text-muted">WhatsApp</a></li>
        <li class="breadcrumb-item active">Disparo</li>
    </ol>
</div>

<div class="row g-4">

    {{-- ── SIDEBAR ── --}}
    <div class="col-lg-4 col-xl-3">

        {{-- Status da Instância --}}
        <div class="broadcast-sidebar-card">
            <h6><i class="fas fa-wifi me-1"></i> Instância WhatsApp</h6>
            @if(isset($activeInstance) && $activeInstance)
                <div class="instance-status-badge">
                    <div class="instance-status-dot"></div>
                    <div>
                        <div style="font-weight:700;font-size:0.85rem;color:#166534;">CONECTADO</div>
                        <div style="font-size:0.75rem;color:#4ade80;font-family:monospace;">{{ $activeInstance->phone_number ?: $activeInstance->instance_name }}</div>
                    </div>
                </div>
            @else
                <div class="instance-status-badge disconnected">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <div>
                        <div style="font-weight:700;font-size:0.85rem;color:#92400e;">DESCONECTADO</div>
                        <a href="{{ route('whatsapp.settings') }}" style="font-size:0.75rem;color:#d97706;">Conectar agora →</a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Contatos --}}
        <div class="stat-box">
            <div class="stat-number">{{ number_format($contactsCount, 0, ',', '.') }}</div>
            <div class="stat-label"><i class="fas fa-users me-1"></i> Contatos no CRM</div>
        </div>

        {{-- Importar CSV --}}
        <div class="broadcast-sidebar-card">
            <h6><i class="fas fa-file-csv me-1"></i> Importar Contatos</h6>
            <form action="{{ route('whatsapp.broadcast.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label class="import-zone w-100 mb-2" for="csvFileInput">
                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#94a3b8;"></i>
                    <div style="font-size:0.8rem;color:#64748b;">Clique para selecionar o CSV</div>
                    <div id="csvFileName" style="font-size:0.75rem;color:#4f46e5;margin-top:4px;"></div>
                    <input type="file" id="csvFileInput" name="csv_file" accept=".csv" required class="d-none" onchange="document.getElementById('csvFileName').textContent = this.files[0]?.name || ''">
                </label>
                <button type="submit" class="btn btn-sm w-100 fw-bold" style="background:#10b981;color:#fff;border-radius:8px;">
                    <i class="fas fa-upload me-1"></i> Importar
                </button>
                @error('csv_file')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </form>
            <div class="csv-example">
                <strong>Formato CSV:</strong><br>
                Nome,Telefone<br>
                João Silva,5511999999999<br>
                Maria Souza,5521988888888
            </div>
        </div>

        {{-- Preview WhatsApp --}}
        <div class="broadcast-sidebar-card">
            <h6><i class="fab fa-whatsapp me-1"></i> Preview da Mensagem</h6>
            <div class="wa-preview-wrap">
                <div id="waPreviewContent">
                    <div class="wa-placeholder">
                        <i class="fas fa-comment-dots fa-2x mb-2" style="opacity:0.3;"></i><br>
                        Digite a mensagem ao lado para ver o preview
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── COMPOSE ── --}}
    <div class="col-lg-8 col-xl-9">
        <div class="compose-card">
            <div class="compose-card-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-paper-plane text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-800" style="color:#1e293b;">Nova Campanha</h5>
                        <small class="text-muted">Configure e dispare sua mensagem em massa</small>
                    </div>
                </div>
            </div>

            <div class="compose-card-body">
                <form action="{{ route('whatsapp.broadcast.send') }}" method="POST" id="broadcastForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Público Alvo --}}
                    <div class="mb-4">
                        <div class="section-label">Público Alvo</div>
                        <div class="d-flex gap-3">
                            <label class="audience-option">
                                <input type="radio" name="audience" value="all" checked onchange="toggleManualPhones(false)">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-users me-1 text-primary"></i> Todos os Contatos
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">{{ $contactsCount }} contatos no CRM</div>
                                </div>
                            </label>
                            <label class="audience-option">
                                <input type="radio" name="audience" value="selected" onchange="toggleManualPhones(true)">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-user-check me-1 text-success"></i> Números Específicos
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">Digite manualmente</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Números manuais --}}
                    <div class="mb-4 d-none" id="manualPhonesWrapper">
                        <div class="section-label">Números (separados por vírgula)</div>
                        <textarea name="phones" class="message-textarea" rows="2" placeholder="5511999999999, 5521988888888, 5531977777777"></textarea>
                    </div>

                    {{-- Imagem (opcional) --}}
                    <div class="mb-4">
                        <div class="section-label">Imagem <span style="font-weight:400;color:#94a3b8;text-transform:none;letter-spacing:0;">(opcional)</span></div>
                        <div id="imageDropZone" class="import-zone" onclick="document.getElementById('broadcastImageInput').click()" ondragover="event.preventDefault();this.style.borderColor='#4f46e5'" ondragleave="this.style.borderColor=''" ondrop="handleImageDrop(event)">
                            <i class="fas fa-image fa-2x mb-2" style="color:#94a3b8;"></i>
                            <div style="font-size:0.8rem;color:#64748b;">Clique ou arraste uma imagem aqui</div>
                            <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;">JPG, PNG, GIF, WEBP — máx. 2 MB</div>
                        </div>
                        <input type="file" id="broadcastImageInput" name="broadcast_image" accept=".jpg,.jpeg,.png,.gif,.webp" class="d-none" onchange="handleImageSelect(this)">
                        <div id="imagePreviewWrap" class="d-none mt-2" style="position:relative;display:inline-block;">
                            <img id="imagePreviewThumb" src="" alt="preview" style="max-height:120px;max-width:100%;border-radius:10px;border:1px solid #e2e8f0;">
                            <button type="button" onclick="removeImage()" style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:22px;height:22px;font-size:0.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-times"></i></button>
                        </div>
                        @error('broadcast_image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mensagem --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-label mb-0" id="messageSectionLabel">Mensagem</div>
                            <span class="spintax-badge" data-bs-toggle="collapse" data-bs-target="#spintaxHelper">
                                <i class="fas fa-magic"></i> Spintax
                            </span>
                        </div>
                        <textarea name="message" id="messageInput" class="message-textarea" rows="6"
                            placeholder="Digite sua mensagem aqui..."
                            oninput="updatePreview(); updateCharCount(this)"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="char-counter" id="charCounter">0 caracteres</span>
                            <span style="font-size:0.72rem;color:#94a3b8;">Máx. 4000 caracteres</span>
                        </div>
                        @error('message')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Spintax Helper --}}
                    <div class="collapse mb-4" id="spintaxHelper">
                        <div class="spintax-helper">
                            <div class="fw-bold mb-2" style="color:#334155;">Como usar Spintax:</div>
                            <p class="mb-2">Use <code>{variação1|variação2|variação3}</code> para gerar mensagens únicas para cada contato.</p>
                            <div class="mb-2">
                                <strong>Exemplo:</strong><br>
                                <code>{Olá|Oi|Bom dia}! Temos uma {promoção|oferta} especial para você 🎉</code>
                            </div>
                            <div class="mb-2" style="color:#475569;"><strong>Clique para inserir:</strong></div>
                            <div>
                                <span class="spintax-tag" onclick="insertSpintax('{Olá|Oi|Bom dia}')">{Olá|Oi|Bom dia}</span>
                                <span class="spintax-tag" onclick="insertSpintax('{você|te}')">{você|te}</span>
                                <span class="spintax-tag" onclick="insertSpintax('{promoção|oferta especial}')">{promoção|oferta especial}</span>
                                <span class="spintax-tag" onclick="insertSpintax('{hoje|agora}')">{hoje|agora}</span>
                                <span class="spintax-tag" onclick="insertSpintax('{aproveite|não perca}')">{aproveite|não perca}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Cadência --}}
                    <div class="mb-4">
                        <div class="section-label">Cadência de Envio</div>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <label class="cadence-option">
                                    <div><input type="radio" name="cadence" value="1" class="me-2">1 segundo</div>
                                    <span class="cadence-risk risk-high">Alto risco</span>
                                </label>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="cadence-option">
                                    <div><input type="radio" name="cadence" value="3" checked class="me-2">3 segundos</div>
                                    <span class="cadence-risk risk-med">Moderado</span>
                                </label>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="cadence-option">
                                    <div><input type="radio" name="cadence" value="5" class="me-2">5 segundos</div>
                                    <span class="cadence-risk risk-low">Seguro</span>
                                </label>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="cadence-option">
                                    <div><input type="radio" name="cadence" value="10" class="me-2">10 segundos</div>
                                    <span class="cadence-risk risk-low">Muito seguro</span>
                                </label>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="cadence-option">
                                    <div><input type="radio" name="cadence" value="30" class="me-2">30 segundos</div>
                                    <span class="cadence-risk risk-low">Máxima segurança</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Aviso --}}
                    <div class="d-flex gap-3 p-3 mb-4 rounded-3" style="background:#fffbeb;border:1px solid #fde68a;">
                        <i class="fas fa-shield-alt mt-1" style="color:#d97706;flex-shrink:0;"></i>
                        <div style="font-size:0.82rem;color:#92400e;">
                            <strong>Aviso Anti-Spam:</strong> Disparos em massa podem resultar no bloqueio do número pelo WhatsApp.
                            Use Spintax para gerar variações e certifique-se que os contatos consentiram o recebimento.
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="d-flex align-items-center justify-content-between">
                        <div style="font-size:0.8rem;color:#94a3b8;">
                            <i class="fas fa-clock me-1"></i>
                            O disparo é processado em tempo real e pode levar alguns minutos.
                        </div>
                        <button type="button" class="launch-btn" onclick="confirmDisparo()">
                            <i class="fas fa-rocket"></i> Iniciar Disparo
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

{{-- Modal de Confirmação de Disparo --}}
<div class="modal fade" id="modalConfirmarDisparo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:20px;border:none;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 28px 20px;text-align:center;">
                <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                    <i class="fas fa-rocket" style="font-size:1.5rem;color:#fff;"></i>
                </div>
                <h5 style="color:#fff;font-weight:800;margin:0;">Confirmar Disparo</h5>
                <p style="color:rgba(255,255,255,0.75);font-size:0.85rem;margin:6px 0 0;">Esta ação não pode ser desfeita</p>
            </div>
            <div class="modal-body" style="padding:24px 28px;">
                <div id="modalDisparoInfo" style="background:#f8fafc;border-radius:12px;padding:14px 16px;font-size:0.85rem;color:#475569;margin-bottom:16px;">
                </div>
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;font-size:0.8rem;color:#92400e;">
                    <i class="fas fa-shield-alt me-1" style="color:#d97706;"></i>
                    Certifique-se que os contatos consentiram o recebimento para evitar bloqueios.
                </div>
            </div>
            <div class="modal-footer" style="padding:0 28px 24px;border:none;gap:10px;">
                <button type="button" class="btn btn-light fw-600 flex-fill" style="border-radius:10px;padding:10px;" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn fw-700 flex-fill" id="btnConfirmarDisparo"
                    style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:10px;padding:10px;">
                    <i class="fas fa-rocket me-1"></i> Disparar Agora
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function toggleManualPhones(show) {
        document.getElementById('manualPhonesWrapper').classList.toggle('d-none', !show);
    }

    function updateCharCount(el) {
        const len = el.value.length;
        const counter = document.getElementById('charCounter');
        counter.textContent = len + ' caracteres';
        counter.className = 'char-counter' + (len > 3500 ? ' danger' : len > 2500 ? ' warn' : '');
    }

    let previewImageSrc = null;

    function updatePreview() {
        const msg = document.getElementById('messageInput').value;
        const preview = document.getElementById('waPreviewContent');
        const hasImage = !!previewImageSrc;
        const hasText  = !!msg.trim();

        if (!hasImage && !hasText) {
            preview.innerHTML = `<div class="wa-placeholder"><i class="fas fa-comment-dots fa-2x mb-2" style="opacity:0.3;"></i><br>Digite a mensagem ao lado para ver o preview</div>`;
            return;
        }
        const now = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        const rendered = hasText ? applySpintaxPreview(escapeHtml(msg)) : '';
        const imgHtml  = hasImage
            ? `<img src="${previewImageSrc}" style="width:100%;border-radius:6px;margin-bottom:${hasText?'6px':'0'};">`
            : '';
        preview.innerHTML = `
            <div class="wa-bubble">
                ${imgHtml}${rendered}
                <div class="wa-bubble-time">${now} <i class="fas fa-check-double" style="color:#53bdeb;font-size:0.6rem;"></i></div>
            </div>`;
    }

    function handleImageSelect(input) {
        if (!input.files || !input.files[0]) return;
        setImagePreview(input.files[0]);
    }

    function handleImageDrop(e) {
        e.preventDefault();
        document.getElementById('imageDropZone').style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('broadcastImageInput').files = dt.files;
            setImagePreview(file);
        }
    }

    function setImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImageSrc = e.target.result;
            const wrap  = document.getElementById('imagePreviewWrap');
            const thumb = document.getElementById('imagePreviewThumb');
            thumb.src = previewImageSrc;
            wrap.classList.remove('d-none');
            wrap.style.display = 'inline-block';
            document.getElementById('imageDropZone').style.display = 'none';
            document.getElementById('messageSectionLabel').textContent = 'Legenda (opcional)';
            document.getElementById('messageInput').placeholder = 'Adicione uma legenda para a imagem...';
            updatePreview();
        };
        reader.readAsDataURL(file);
    }

    function removeImage() {
        previewImageSrc = null;
        document.getElementById('broadcastImageInput').value = '';
        document.getElementById('imagePreviewWrap').classList.add('d-none');
        document.getElementById('imageDropZone').style.display = '';
        document.getElementById('messageSectionLabel').textContent = 'Mensagem';
        document.getElementById('messageInput').placeholder = 'Digite sua mensagem aqui...';
        updatePreview();
    }

    function applySpintaxPreview(text) {
        return text.replace(/\{([^}]+)\}/g, function(match, inner) {
            const options = inner.split('|');
            return '<span style="background:#e0e7ff;color:#4338ca;padding:0 3px;border-radius:3px;">' + options[0] + '</span>';
        });
    }

    function escapeHtml(text) {
        return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    function insertSpintax(tag) {
        const ta = document.getElementById('messageInput');
        const start = ta.selectionStart;
        const end = ta.selectionEnd;
        ta.value = ta.value.substring(0, start) + tag + ta.value.substring(end);
        ta.selectionStart = ta.selectionEnd = start + tag.length;
        ta.focus();
        updatePreview();
        updateCharCount(ta);
    }

    function confirmDisparo() {
        const msg       = document.getElementById('messageInput').value.trim();
        const hasImage  = !!document.getElementById('broadcastImageInput').files.length;
        if (!msg && !hasImage) {
            // Small inline toast instead of alert
            const btn = document.querySelector('.launch-btn');
            btn.style.background = '#ef4444';
            btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Mensagem ou imagem obrigatória';
            setTimeout(() => {
                btn.style.background = '';
                btn.innerHTML = '<i class="fas fa-rocket"></i> Iniciar Disparo';
            }, 2500);
            return;
        }

        // Build info summary for modal
        const audience   = document.querySelector('input[name=audience]:checked')?.value;
        const cadence    = document.querySelector('input[name=cadence]:checked')?.value || 3;
        const audienceTxt = audience === 'all' ? 'Todos os contatos do CRM' : 'Números específicos';
        const imageTxt   = hasImage ? '<span style="color:#4f46e5;font-weight:600;"><i class="fas fa-image me-1"></i>Com imagem</span> + ' : '';
        const msgPreview = msg ? `"${msg.substring(0, 60)}${msg.length > 60 ? '…' : ''}"` : '<em>sem texto</em>';

        document.getElementById('modalDisparoInfo').innerHTML = `
            <div class="d-flex flex-column gap-2">
                <div><i class="fas fa-users me-2" style="color:#4f46e5;width:16px;"></i><strong>Público:</strong> ${audienceTxt}</div>
                <div><i class="fas fa-comment me-2" style="color:#4f46e5;width:16px;"></i><strong>Mensagem:</strong> ${imageTxt}${msgPreview}</div>
                <div><i class="fas fa-clock me-2" style="color:#4f46e5;width:16px;"></i><strong>Cadência:</strong> ${cadence}s entre envios</div>
            </div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmarDisparo'));
        modal.show();
    }

    document.getElementById('btnConfirmarDisparo').addEventListener('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmarDisparo')).hide();
        const btn = document.querySelector('.launch-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        document.getElementById('broadcastForm').submit();
    });
</script>
@endpush
