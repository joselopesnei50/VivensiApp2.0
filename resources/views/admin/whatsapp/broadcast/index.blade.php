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

    /* Seletor de etiquetas no broadcast (Fase 2) */
    .label-picker-broadcast { display: flex; flex-wrap: wrap; gap: 8px; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; }
    .lpb-item { cursor: pointer; margin: 0; }
    .lpb-item input { display: none; }
    .lpb-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 99px; font-size: .8rem; font-weight: 700;
        opacity: .55; border: 2px solid transparent; transition: opacity .15s, border-color .15s;
    }
    .lpb-item:hover .lpb-badge { opacity: .85; }
    .lpb-item input:checked + .lpb-badge { opacity: 1; border-color: currentColor; }
    #labelCountInfo.warn { color: #b45309; font-weight: 600; }
    #labelCountInfo.ok   { color: #15803d; font-weight: 600; }
    #labelCountInfo.over { color: #b91c1c; font-weight: 700; }
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
    /* Groups */
    .group-checkbox-list {
        max-height: 220px;
        overflow-y: auto;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px;
    }
    .group-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.1s;
        font-size: 0.85rem;
    }
    .group-item:hover { background: #f5f3ff; }
    .group-item input[type=checkbox] { accent-color: #4f46e5; width:16px; height:16px; flex-shrink:0; }
    /* History */
    .history-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-top: 28px;
    }
    .history-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .badge-audience {
        display:inline-block;
        padding:2px 10px;
        border-radius:20px;
        font-size:0.72rem;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .badge-all      { background:#e0e7ff;color:#4338ca; }
    .badge-selected { background:#dcfce7;color:#166534; }
    .badge-groups   { background:#fef3c7;color:#92400e; }
</style>
@endpush

@section('content')

{{-- Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        @if(session('import_label_id'))
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm fw-bold"
                        style="background:#7c3aed;color:#fff;border-radius:8px;"
                        onclick="selectImportLabel({{ session('import_label_id') }})">
                    <i class="fas fa-bullseye me-1"></i>
                    Disparar só para "{{ session('import_label_name') }}"
                </button>
            </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('warning_antiban'))
    <div class="alert alert-warning alert-dismissible fade show rounded-3 mb-4" role="alert">
        <i class="fas fa-shield-alt me-2"></i> {{ session('warning_antiban') }}
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

                {{-- Nome opcional pra facilitar identificar a lista depois --}}
                <input type="text" name="import_name" maxlength="60"
                       placeholder="Nome da lista (opcional, ex: Doadores 2025)"
                       style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.78rem;margin-bottom:8px;">

                {{-- Declaração LGPD obrigatória --}}
                <label style="display:flex;align-items:flex-start;gap:8px;padding:10px;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;margin-bottom:10px;cursor:pointer;">
                    <input type="checkbox" name="lgpd_consent" value="1" required style="margin-top:2px;flex-shrink:0;">
                    <span style="font-size:.72rem;color:#78350f;line-height:1.4;">
                        <strong>Declaro (LGPD art. 7º)</strong> que possuo consentimento
                        dos contatos desta planilha para enviar mensagens pelo WhatsApp.
                    </span>
                </label>

                <button type="submit" class="btn btn-sm w-100 fw-bold" style="background:#10b981;color:#fff;border-radius:8px;">
                    <i class="fas fa-upload me-1"></i> Importar + criar etiqueta
                </button>
                @error('csv_file')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('lgpd_consent')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </form>
            <div class="csv-example">
                <strong>Formato CSV:</strong><br>
                Nome,Telefone<br>
                João Silva,5511999999999<br>
                Maria Souza,5521988888888
                <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #e2e8f0;font-size:.7rem;color:#64748b;">
                    <i class="fas fa-info-circle" style="color:#4f46e5;"></i>
                    Ao importar, criamos uma <strong>etiqueta</strong> com essa lista.
                    Para disparar só pra ela: <em>Público Alvo → Etiquetas</em>.
                </div>
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

                    {{-- Banner de segurança pra broadcast de áudio (2026-08-05 rev 2).
                         Regras diferentes por tipo de audiência — grupos aceitos com cap. --}}
                    <div id="audioSafetyBanner" style="background:#fffbeb;border:1px solid #fcd34d;border-left:4px solid #f59e0b;border-radius:10px;padding:14px 16px;margin-bottom:18px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <i class="fas fa-shield-alt" style="color:#b45309;"></i>
                            <strong style="color:#92400e;font-size:.9rem;">Regras de segurança do áudio em massa</strong>
                        </div>
                        <ul style="margin:0 0 0 20px;padding:0;font-size:.78rem;color:#78350f;line-height:1.6;">
                            <li><strong>Termo Anti-Ban obrigatório:</strong> sem aceite vigente, o disparo é rejeitado.</li>
                            <li><strong>Cadência mínima 20s</strong> entre envios (imita gravação humana e reduz ban).</li>
                            <li><strong>Máx. 3 disparos do mesmo áudio por dia</strong> por instância (fingerprint sha256).</li>
                            <li><strong>Audiência individual</strong> (Todos, Selecionados, Etiquetas): só contatos com inbound nas últimas 24h + máx. 100 destinatários.</li>
                            <li><strong>Audiência de grupos</strong>: máx. 10 grupos por campanha (membros já opted-in — sem janela 24h).</li>
                        </ul>
                        <div style="margin-top:8px;font-size:.72rem;color:#92400e;">
                            Áudio 1:1 pra muitos contatos frios é o vetor mais punido pela Meta/Evolution. Áudio em grupo próprio é comportamento normal.
                        </div>
                    </div>

                    {{-- Importações recentes: atalho de 1 clique pra disparar só pra planilha subida --}}
                    @if(isset($recentImports) && $recentImports->isNotEmpty())
                    <div class="mb-4 p-3 rounded-3" style="background:#f5f3ff;border:1px solid #e9d5ff;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-label mb-0" style="color:#6d28d9;">
                                <i class="fas fa-file-import me-1"></i> Importações Recentes
                            </div>
                            <span style="font-size:.72rem;color:#7c3aed;font-weight:700;">Últimas planilhas subidas</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($recentImports as $imp)
                                <button type="button"
                                        onclick="selectImportLabel({{ $imp->id }})"
                                        style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:#fff;border:1px solid #d8b4fe;border-radius:10px;cursor:pointer;font-size:.78rem;color:#5b21b6;font-weight:600;transition:background .15s;"
                                        onmouseover="this.style.background='#ede9fe'"
                                        onmouseout="this.style.background='#fff'">
                                    <i class="fas fa-tag" style="color:#7c3aed;font-size:.7rem;"></i>
                                    <span>{{ $imp->name }}</span>
                                    <span style="background:#7c3aed;color:#fff;padding:2px 7px;border-radius:20px;font-size:.65rem;font-weight:800;">{{ $imp->chats_count }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div style="font-size:.72rem;color:#6d28d9;margin-top:8px;">
                            <i class="fas fa-info-circle"></i>
                            Clique em uma importação para disparar <strong>apenas para os contatos daquela planilha</strong>.
                        </div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <div class="section-label">Público Alvo</div>
                        <div class="d-flex gap-3 flex-wrap">
                            <label class="audience-option">
                                <input type="radio" name="audience" value="all" {{ !session('prefilled_phones') ? 'checked' : '' }} onchange="onAudienceChange('all')">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-users me-1 text-primary"></i> Todos os Contatos
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">{{ $contactsCount }} contatos no CRM</div>
                                </div>
                            </label>
                            <label class="audience-option">
                                <input type="radio" name="audience" value="selected" {{ session('prefilled_phones') ? 'checked' : '' }} onchange="onAudienceChange('selected')">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-user-check me-1 text-success"></i> Números Específicos
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">Digite manualmente</div>
                                </div>
                            </label>
                            <label class="audience-option">
                                <input type="radio" name="audience" value="groups" onchange="onAudienceChange('groups')">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-people-group me-1" style="color:#d97706;"></i> Grupos
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">Selecionar grupos do WhatsApp</div>
                                </div>
                            </label>
                            <label class="audience-option">
                                <input type="radio" name="audience" value="labels" onchange="onAudienceChange('labels')">
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                        <i class="fas fa-tags me-1" style="color:#7e22ce;"></i> Etiquetas
                                    </div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">Disparar por categoria de contato</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Etiquetas (Fase 2) --}}
                    <div class="mb-4 d-none" id="labelsWrapper">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-label mb-0">Etiquetas alvo</div>
                            <a href="{{ route('whatsapp.labels.index') }}" target="_blank" style="font-size:.78rem;color:#6366f1;text-decoration:none;font-weight:700;">
                                <i class="fas fa-gear"></i> gerenciar etiquetas
                            </a>
                        </div>
                        @if($labels->isEmpty())
                            <div style="padding:16px;background:#fef3c7;border:1px solid #fde68a;border-radius:12px;color:#78350f;font-size:.85rem;">
                                <i class="fas fa-info-circle"></i>
                                Você ainda não tem etiquetas cadastradas. Crie em <a href="{{ route('whatsapp.labels.index') }}" style="color:#b45309;font-weight:700;">Etiquetas</a> antes de disparar por categoria.
                            </div>
                        @else
                            <div class="label-picker-broadcast" id="labelsPicker">
                                @foreach($labels as $label)
                                    <label class="lpb-item">
                                        <input type="checkbox" name="label_ids[]" value="{{ $label->id }}" onchange="updateLabelCount()">
                                        <span class="lpb-badge" style="background:{{ $label->background }};color:{{ $label->color }};">
                                            <i class="fas fa-tag" style="font-size:.6rem;"></i>
                                            {{ $label->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div id="labelCountInfo" class="mt-3" style="font-size:.85rem;color:#64748b;">
                                Selecione uma ou mais etiquetas para ver a contagem estimada de destinatários.
                            </div>
                        @endif
                    </div>

                    {{-- Números manuais --}}
                    <div class="mb-4 {{ session('prefilled_phones') ? '' : 'd-none' }}" id="manualPhonesWrapper">
                        <div class="section-label">Números (separados por vírgula)</div>
                        <textarea name="phones" class="message-textarea" rows="2" placeholder="5511999999999, 5521988888888, 5531977777777">{{ session('prefilled_phones') }}</textarea>
                    </div>

                    {{-- Grupos --}}
                    <div class="mb-4 d-none" id="groupsWrapper">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="section-label mb-0">Grupos do WhatsApp</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadGroups()" style="font-size:0.78rem;border-radius:8px;">
                                <i class="fas fa-sync-alt me-1"></i> Carregar grupos
                            </button>
                        </div>

                        {{-- Modo de envio para grupos --}}
                        <div class="mb-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="section-label mb-2">Modo de Envio</div>
                            <div class="d-flex gap-3 flex-wrap">
                                <label class="audience-option" style="flex:1;min-width:180px;">
                                    <input type="radio" name="group_send_mode" value="group" checked onchange="updateGroupModeInfo()">
                                    <div>
                                        <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                            <i class="fas fa-people-group me-1" style="color:#d97706;"></i> Mensagem no Grupo
                                        </div>
                                        <div style="font-size:0.73rem;color:#94a3b8;">1 msg enviada para o chat do grupo</div>
                                    </div>
                                </label>
                                <label class="audience-option" style="flex:1;min-width:180px;">
                                    <input type="radio" name="group_send_mode" value="members" onchange="updateGroupModeInfo()">
                                    <div>
                                        <div style="font-weight:700;font-size:0.85rem;color:#334155;">
                                            <i class="fas fa-user-check me-1" style="color:#10b981;"></i> Individual p/ Cada Membro
                                        </div>
                                        <div style="font-size:0.73rem;color:#94a3b8;">Mensagem privada para cada pessoa</div>
                                    </div>
                                </label>
                            </div>
                            <div id="groupModeInfo" class="mt-2 small" style="color:#64748b;font-size:0.78rem;">
                                <i class="fas fa-info-circle me-1" style="color:#4f46e5;"></i>
                                <span id="groupModeInfoText">A mensagem será enviada uma vez para o chat do grupo.</span>
                            </div>
                        </div>

                        <div id="groupsLoadingMsg" class="text-muted small d-none">
                            <i class="fas fa-spinner fa-spin me-1"></i> Buscando grupos...
                        </div>
                        <div id="groupsErrorMsg" class="text-danger small d-none"></div>
                        <div id="groupsListWrapper" class="d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <input type="text" id="groupsSearch" class="form-control form-control-sm" placeholder="Buscar grupo..." oninput="filterGroups(this.value)" style="max-width:220px;border-radius:8px;">
                                <span id="groupsCount" class="text-muted small"></span>
                            </div>
                            <div class="group-checkbox-list" id="groupsCheckboxList"></div>
                            <div id="membersModeWarning" class="d-none mt-2 p-2 rounded-3" style="background:#fffbeb;border:1px solid #fde68a;font-size:0.78rem;color:#92400e;">
                                <i class="fas fa-exclamation-triangle me-1" style="color:#d97706;"></i>
                                <strong>Modo individual:</strong> Será enviada uma mensagem privada para cada membro dos grupos selecionados.
                                O total de mensagens enviadas pode ser muito maior que o número de grupos.
                            </div>
                        </div>
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
                            <img loading="lazy" id="imagePreviewThumb" src="" alt="preview" style="max-height:120px;max-width:100%;border-radius:10px;border:1px solid #e2e8f0;">
                            <button type="button" onclick="removeImage()" style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:22px;height:22px;font-size:0.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-times"></i></button>
                        </div>
                        @error('broadcast_image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Áudio (opcional, com travas anti-ban) --}}
                    <div class="mb-4">
                        <div class="section-label">Áudio <span style="font-weight:400;color:#94a3b8;text-transform:none;letter-spacing:0;">(opcional — leia as regras acima)</span></div>
                        <div id="audioDropZone" class="import-zone" onclick="document.getElementById('broadcastAudioInput').click()"
                             ondragover="event.preventDefault();this.style.borderColor='#f59e0b'"
                             ondragleave="this.style.borderColor=''"
                             ondrop="handleAudioDrop(event)">
                            <i class="fas fa-microphone fa-2x mb-2" style="color:#f59e0b;"></i>
                            <div style="font-size:0.8rem;color:#64748b;">Clique ou arraste um áudio aqui</div>
                            <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;">OGG, MP3, M4A, WEBM — máx. 16 MB</div>
                        </div>
                        <input type="file" id="broadcastAudioInput" name="broadcast_audio" accept="audio/*,.ogg,.oga,.opus,.mp3,.m4a,.mp4,.webm,.aac" class="d-none" onchange="handleAudioSelect(this)">
                        <div id="audioPreviewWrap" class="d-none mt-2" style="position:relative;display:flex;align-items:center;gap:12px;background:#fff7ed;padding:10px 14px;border-radius:10px;border:1px solid #fed7aa;">
                            <i class="fas fa-file-audio" style="color:#c2410c;font-size:1.4rem;"></i>
                            <div style="flex:1;">
                                <div id="audioFileName" style="font-size:.85rem;font-weight:600;color:#7c2d12;"></div>
                                <audio id="audioPreviewPlayer" controls style="width:100%;margin-top:4px;height:32px;"></audio>
                            </div>
                            <button type="button" onclick="removeAudio()" style="background:#ef4444;color:#fff;border:none;border-radius:50%;width:26px;height:26px;font-size:0.75rem;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i class="fas fa-times"></i></button>
                        </div>
                        @error('broadcast_audio')
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
                            oninput="updatePreview(); updateCharCount(this)">{{ old('message') }}</textarea>
                        @if(!empty($preMessage))
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const el = document.getElementById('messageInput');
                                if (el) {
                                    el.value = @json($preMessage);
                                    el.dispatchEvent(new Event('input'));
                                }
                            });
                        </script>
                        @endif
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
                                    <div><input type="radio" name="cadence" value="20" class="me-2">20 segundos</div>
                                    <span class="cadence-risk risk-low">Mínimo p/ áudio</span>
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

                    {{-- Agendamento --}}
                    <div class="mb-4">
                        <div class="section-label">Agendamento</div>
                        <div class="p-3 rounded-3" style="background:#f8fafc; border: 1px solid #e2e8f0;">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-switch" type="checkbox" id="scheduleToggle" onchange="toggleSchedule(this.checked)">
                                <label class="form-check-label fw-600 small ms-2" for="scheduleToggle">Agendar para mais tarde</label>
                            </div>
                            <div id="scheduleInputWrapper" class="d-none">
                                <label class="form-label small text-muted mb-1">Selecione data e hora:</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduledAtInput"
                                       class="form-control form-control-sm" style="border-radius:8px; max-width:240px;"
                                       disabled>
                                <div class="text-muted mt-1" style="font-size:0.7rem;">
                                    <i class="fas fa-info-circle me-1"></i> Deixe pelo menos 5 minutos de margem.
                                </div>
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

{{-- ── CAMPANHAS AGENDADAS ── --}}
<div class="history-card" style="border-left:4px solid #4f46e5; margin-bottom:20px;">
    <div class="history-card-header">
        <div style="width:36px;height:36px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-clock text-white" style="font-size:0.9rem;"></i>
        </div>
        <div class="flex-1">
            <h6 class="mb-0 fw-800" style="color:#1e293b;">Disparos Agendados</h6>
            <small class="text-muted">{{ $scheduled->count() }} campanha(s) aguardando envio</small>
        </div>
    </div>

    @if($scheduled->isEmpty())
        <div style="padding:32px;text-align:center;color:#94a3b8;">
            <i class="fas fa-clock fa-2x mb-3" style="opacity:0.25;"></i>
            <p class="mb-0 small">Nenhum disparo agendado.<br>Use "Agendar para mais tarde" ao criar uma campanha.</p>
        </div>
    @else
    <div class="table-responsive">
        <table class="table mb-0" style="font-size:0.85rem;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:12px 20px;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;border:none;">Data Agendada</th>
                    <th style="padding:12px 20px;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;border:none;">Mensagem</th>
                    <th style="padding:12px 20px;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;border:none;">Público</th>
                    <th style="padding:12px 20px;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;border:none;">Ação</th>
                </tr>
            </thead>
            <tbody>
                @php $audienceLabels2 = ['all'=>'Todos','selected'=>'Específicos','groups'=>'Grupos']; @endphp
                @foreach($scheduled as $sc)
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px 20px;white-space:nowrap;">
                        <span style="font-weight:700;color:#4f46e5;">
                            <i class="fas fa-clock me-1"></i>{{ $sc->scheduled_at->format('d/m/Y H:i') }}
                        </span>
                        <div style="font-size:0.7rem;color:#94a3b8;">{{ $sc->scheduled_at->diffForHumans() }}</div>
                    </td>
                    <td style="padding:12px 20px;max-width:260px;">
                        @if($sc->has_image)<span style="color:#4f46e5;"><i class="fas fa-image me-1"></i></span>@endif
                        {{ $sc->message ? mb_substr($sc->message, 0, 60).(mb_strlen($sc->message) > 60 ? '…' : '') : '(apenas imagem)' }}
                    </td>
                    <td style="padding:12px 20px;">
                        <span class="badge-audience badge-{{ $sc->audience_type === 'groups' ? 'groups' : ($sc->audience_type === 'selected' ? 'selected' : 'all') }}">
                            {{ $audienceLabels2[$sc->audience_type] ?? $sc->audience_type }}
                        </span>
                    </td>
                    <td style="padding:12px 20px;">
                        <form method="POST" action="{{ route('whatsapp.broadcast.cancel', $sc->id) }}"
                              onsubmit="return confirm('Cancelar este agendamento?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;border-radius:8px;padding:3px 10px;">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── HISTÓRICO DE CAMPANHAS ── --}}
<div class="history-card" id="historicoDisparos">

    {{-- Header --}}
    <div class="history-card-header" style="background:linear-gradient(135deg,#0ea5e9 0%,#38bdf8 100%);border-radius:14px 14px 0 0;padding:18px 24px;">
        <div style="width:38px;height:38px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-chart-bar" style="color:#fff;font-size:0.95rem;"></i>
        </div>
        <div class="flex-1">
            <h6 class="mb-0 fw-800" style="color:#fff;font-size:.95rem;">Relatório de Disparos</h6>
            <small style="color:rgba(255,255,255,.75);font-size:.72rem;">Últimas 10 campanhas · <a href="{{ route('whatsapp.broadcast.campaigns') }}" style="color:rgba(255,255,255,.9);font-weight:700;">ver todas</a></small>
        </div>
        <a href="{{ route('whatsapp.broadcast.campaigns') }}"
           style="background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);color:#fff;font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:.15s;"
           onmouseover="this.style.background='rgba(255,255,255,.3)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
            <i class="fas fa-arrow-up-right-from-square"></i> Ver tudo
        </a>
    </div>

    @if($campaigns->isEmpty())
        <div style="padding:48px 24px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(14,165,233,.08);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <i class="fas fa-paper-plane" style="font-size:1.3rem;color:#38bdf8;"></i>
            </div>
            <p class="mb-0" style="color:#94a3b8;font-size:.83rem;">Nenhuma campanha disparada ainda.</p>
        </div>
    @else
    <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;">
        @foreach($campaigns as $campaign)
        @php
            $total = $campaign->total_sent + $campaign->total_failed;
            $rate  = $total > 0 ? round($campaign->total_sent / $total * 100) : 0;
            $rateColor = $rate >= 90 ? '#10b981' : ($rate >= 70 ? '#f59e0b' : '#ef4444');
            $audLabels  = ['all'=>'Todos','selected'=>'Específicos','groups'=>'Grupos'];
            $audColors  = ['all'=>['bg'=>'rgba(99,102,241,.1)','c'=>'#4338ca'],'selected'=>['bg'=>'rgba(16,185,129,.1)','c'=>'#166534'],'groups'=>['bg'=>'rgba(245,158,11,.1)','c'=>'#92400e']];
            $aud = $audColors[$campaign->audience_type] ?? $audColors['all'];
            $sMap = ['scheduled'=>['bg'=>'rgba(99,102,241,.1)','c'=>'#4f46e5','icon'=>'clock','lbl'=>'Agendado'],
                     'queued'   =>['bg'=>'rgba(99,102,241,.1)', 'c'=>'#4f46e5','icon'=>'hourglass-half','lbl'=>'Na Fila'],
                     'processing'=>['bg'=>'rgba(245,158,11,.1)','c'=>'#d97706','icon'=>'spinner','lbl'=>'Processando'],
                     'completed' =>['bg'=>'rgba(16,185,129,.1)', 'c'=>'#059669','icon'=>'circle-check','lbl'=>'Concluído'],
                     'paused'   =>['bg'=>'rgba(148,163,184,.1)','c'=>'#64748b','icon'=>'pause','lbl'=>'Pausado'],
                     'cancelled'=>['bg'=>'rgba(148,163,184,.15)','c'=>'#64748b','icon'=>'ban','lbl'=>'Cancelado'],
                     'failed'    =>['bg'=>'rgba(239,68,68,.1)',  'c'=>'#dc2626','icon'=>'circle-xmark','lbl'=>'Falhou']];
            $s = $sMap[$campaign->status] ?? ['bg'=>'rgba(100,116,139,.1)','c'=>'#475569','icon'=>'circle','lbl'=>$campaign->status];
            $isMembers = $campaign->audience_type === 'groups' && $campaign->group_send_mode === 'members';
        @endphp
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;border-left:3px solid {{ $rateColor }};transition:.15s;"
             onmouseover="this.style.background='#f0f9ff';this.style.borderColor='#bae6fd'"
             onmouseout="this.style.background='#f8fafc';this.style.borderColor='#e2e8f0';this.style.borderLeftColor='{{ $rateColor }}'">

            {{-- Row 1: date + status + audience --}}
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
                <span style="font-size:.72rem;color:#64748b;font-weight:600;">
                    <i class="fas fa-calendar-day" style="font-size:.65rem;color:#94a3b8;"></i>
                    {{ $campaign->created_at->format('d/m H:i') }}
                </span>
                <span style="background:{{ $s['bg'] }};color:{{ $s['c'] }};padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:800;display:inline-flex;align-items:center;gap:4px;">
                    <i class="fas fa-{{ $s['icon'] }} {{ $campaign->status === 'processing' ? 'fa-spin' : '' }}" style="font-size:.6rem;"></i>
                    {{ $s['lbl'] }}
                </span>
                <span style="background:{{ $aud['bg'] }};color:{{ $aud['c'] }};padding:2px 8px;border-radius:20px;font-size:.63rem;font-weight:700;margin-left:auto;">
                    {{ $audLabels[$campaign->audience_type] ?? $campaign->audience_type }}
                    @if($isMembers) · membros @endif
                </span>
            </div>

            {{-- Row 2: message preview --}}
            <div style="font-size:.8rem;color:#334155;line-height:1.4;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;">
                @if($campaign->has_image)<i class="fas fa-image" style="color:#6366f1;font-size:.7rem;margin-right:4px;"></i>@endif
                {{ $campaign->message ?: '(apenas imagem)' }}
            </div>

            {{-- Row 3: metrics + progress --}}
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="display:flex;align-items:center;gap:4px;">
                    <span style="font-size:.82rem;font-weight:800;color:#059669;">{{ number_format($campaign->total_sent) }}</span>
                    <span style="font-size:.7rem;color:#94a3b8;">env</span>
                    <span style="color:#e2e8f0;margin:0 2px;">/</span>
                    <span style="font-size:.82rem;font-weight:800;color:{{ $campaign->total_failed > 0 ? '#dc2626' : '#94a3b8' }};">{{ number_format($campaign->total_failed) }}</span>
                    <span style="font-size:.7rem;color:#94a3b8;">falha</span>
                </div>
                @if($total > 0)
                <div style="flex:1;display:flex;align-items:center;gap:6px;">
                    <div style="flex:1;background:#e2e8f0;border-radius:99px;height:4px;overflow:hidden;">
                        <div style="width:{{ $rate }}%;height:4px;background:{{ $rateColor }};border-radius:99px;"></div>
                    </div>
                    <span style="font-size:.73rem;font-weight:800;color:{{ $rateColor }};min-width:30px;">{{ $rate }}%</span>
                </div>
                @endif
                @if($campaign->duration)
                <span style="font-size:.68rem;color:#94a3b8;white-space:nowrap;"><i class="fas fa-stopwatch" style="font-size:.6rem;"></i> {{ $campaign->duration }}</span>
                @endif
            </div>

            {{-- Acoes contextuais por status (Retomar / Cancelar) --}}
            @if(in_array($campaign->status, ['scheduled', 'queued', 'paused', 'processing']))
                <div style="display:flex;align-items:center;gap:10px;margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;flex-wrap:wrap;">
                    @if($campaign->status === 'paused')
                        <span style="font-size:.65rem;color:#92400e;background:#fef3c7;padding:3px 8px;border-radius:6px;">
                            <i class="fas fa-circle-info"></i> Fora da janela horária OU limite diário/horário atingido. Retomar dentro da janela 08h–21h.
                        </span>
                        <form method="POST" action="{{ route('whatsapp.broadcast.resume', $campaign->id) }}" style="display:inline;margin-left:auto;">
                            @csrf
                            <button type="submit" style="background:transparent;border:1px solid #10b981;color:#059669;font-size:.7rem;font-weight:700;cursor:pointer;padding:4px 10px;border-radius:8px;">
                                <i class="fas fa-play me-1" style="font-size:.6rem;"></i> Retomar
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('whatsapp.broadcast.cancel', $campaign->id) }}" style="display:inline;{{ $campaign->status === 'paused' ? '' : 'margin-left:auto;' }}"
                          onsubmit="return confirm('Cancelar esta campanha? As mensagens já enviadas não serão revertidas.');">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:transparent;border:1px solid #ef4444;color:#dc2626;font-size:.7rem;font-weight:700;cursor:pointer;padding:4px 10px;border-radius:8px;">
                            <i class="fas fa-times me-1" style="font-size:.6rem;"></i> Cancelar
                        </button>
                    </form>
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <div style="padding:12px 20px;border-top:1px solid #f1f5f9;text-align:center;">
        <a href="{{ route('whatsapp.broadcast.campaigns') }}" style="font-size:.78rem;color:#0ea5e9;font-weight:700;text-decoration:none;">
            Ver relatório completo <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    @endif
</div>

{{-- Modal de Confirmação de Disparo --}}
<div class="modal fade" id="modalConfirmarDisparo" role="dialog" aria-modal="true" aria-labelledby="modalConfirmarDisparoLabel" tabindex="-1" aria-hidden="true">
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
    let allGroups = [];

    function onAudienceChange(val) {
        document.getElementById('manualPhonesWrapper').classList.toggle('d-none', val !== 'selected');
        document.getElementById('groupsWrapper').classList.toggle('d-none', val !== 'groups');
        const labelsWrap = document.getElementById('labelsWrapper');
        if (labelsWrap) labelsWrap.classList.toggle('d-none', val !== 'labels');
        if (val === 'labels') updateLabelCount();
    }

    // Chamado pelos botões "Disparar só para esta importação" (alert pós-upload e
    // dropdown de importações recentes). Seleciona a audiência "labels", marca só
    // a etiqueta dessa importação e rola até a seção.
    function selectImportLabel(labelId) {
        const radioLabels = document.querySelector('input[name="audience"][value="labels"]');
        if (radioLabels) {
            radioLabels.checked = true;
            onAudienceChange('labels');
        }
        // Desmarca todos os checkboxes de etiqueta e marca só o desta importação
        document.querySelectorAll('input[name="label_ids[]"]').forEach(cb => {
            cb.checked = (String(cb.value) === String(labelId));
        });
        updateLabelCount();
        const composeCard = document.querySelector('.compose-card');
        if (composeCard) composeCard.scrollIntoView({behavior: 'smooth', block: 'start'});
    }

    // Atualiza a contagem estimada de destinatários quando o usuário marca
    // ou desmarca etiquetas. Aplica os mesmos filtros de compliance que o job.
    let labelCountTimer = null;
    function updateLabelCount() {
        const info = document.getElementById('labelCountInfo');
        if (!info) return;

        const checked = Array.from(document.querySelectorAll('input[name="label_ids[]"]:checked'))
            .map(el => el.value);

        if (checked.length === 0) {
            info.className = 'mt-3';
            info.textContent = 'Selecione uma ou mais etiquetas para ver a contagem estimada de destinatários.';
            return;
        }

        clearTimeout(labelCountTimer);
        info.className = 'mt-3';
        info.textContent = 'Calculando...';

        labelCountTimer = setTimeout(() => {
            const qs = checked.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
            fetch('{{ route("whatsapp.broadcast.label-count") }}?' + qs, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                const n = data.count ?? 0;
                const max = data.max ?? 500;
                if (n === 0) {
                    info.className = 'mt-3 warn';
                    info.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Nenhum contato elegível encontrado com essas etiquetas (precisa ter opt-in e não estar bloqueado).';
                } else if (n > max) {
                    info.className = 'mt-3 over';
                    info.innerHTML = '<i class="fas fa-circle-exclamation"></i> <strong>' + n + '</strong> contatos elegíveis — apenas os primeiros <strong>' + max + '</strong> serão disparados (limite anti-ban).';
                } else {
                    info.className = 'mt-3 ok';
                    info.innerHTML = '<i class="fas fa-check-circle"></i> <strong>' + n + '</strong> contatos elegíveis serão disparados.';
                }
            })
            .catch(() => {
                info.className = 'mt-3 warn';
                info.textContent = 'Não foi possível calcular agora. A contagem será aplicada no disparo.';
            });
        }, 250);
    }

    function toggleSchedule(checked) {
        document.getElementById('scheduleInputWrapper').classList.toggle('d-none', !checked);
        const input = document.getElementById('scheduledAtInput');
        input.disabled = !checked;
        if (!checked) input.value = '';
    }

    function loadGroups() {
        const loading = document.getElementById('groupsLoadingMsg');
        const errEl   = document.getElementById('groupsErrorMsg');
        const listEl  = document.getElementById('groupsListWrapper');

        loading.classList.remove('d-none');
        errEl.classList.add('d-none');
        listEl.classList.add('d-none');

        fetch('{{ route("whatsapp.broadcast.groups") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            loading.classList.add('d-none');
            
            // Handle Laravel exceptions or our custom errors
            const errMsg = data.error || data.message || null;
            if (errMsg) { 
                errEl.textContent = errMsg; 
                errEl.classList.remove('d-none'); 
                return; 
            }
            
            // Extra safety check: must be an array
            if (!Array.isArray(data)) {
                errEl.textContent = 'Erro de comunicação: A API não retornou uma lista válida.';
                errEl.classList.remove('d-none');
                return;
            }

            allGroups = data;
            renderGroups(data);
            listEl.classList.remove('d-none');
        })
        .catch(() => {
            loading.classList.add('d-none');
            errEl.textContent = 'Erro ao carregar grupos. Tente novamente.';
            errEl.classList.remove('d-none');
        });
    }

    function renderGroups(groups) {
        const container = document.getElementById('groupsCheckboxList');
        document.getElementById('groupsCount').textContent = groups.length + ' grupos encontrados';
        if (!groups.length) {
            container.innerHTML = '<p class="text-muted small p-2 mb-0">Nenhum grupo encontrado.</p>';
            return;
        }
        container.innerHTML = groups.map(g => `
            <label class="group-item">
                <input type="checkbox" name="group_ids[]" value="${escapeAttr(g.id)}">
                <div style="flex:1;">
                    <div style="font-weight:600;color:#334155;">${escapeHtml(g.name)}</div>
                    ${g.size ? `<div style="font-size:0.72rem;color:#94a3b8;">${g.size} participantes</div>` : ''}
                </div>
            </label>
        `).join('');
    }

    function filterGroups(q) {
        const filtered = allGroups.filter(g => g.name.toLowerCase().includes(q.toLowerCase()));
        renderGroups(filtered);
    }

    function updateGroupModeInfo() {
        const mode = document.querySelector('input[name="group_send_mode"]:checked')?.value || 'group';
        const infoText = document.getElementById('groupModeInfoText');
        const warning  = document.getElementById('membersModeWarning');
        if (mode === 'members') {
            infoText.textContent = 'Cada membro receberá uma mensagem privada (no inbox pessoal dele).';
            warning?.classList.remove('d-none');
        } else {
            infoText.textContent = 'A mensagem será enviada uma vez para o chat do grupo.';
            warning?.classList.add('d-none');
        }
    }

    function escapeAttr(s) { return String(s).replace(/"/g, '&quot;'); }

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
            ? `<img loading="lazy" src="${previewImageSrc}" style="width:100%;border-radius:6px;margin-bottom:${hasText?'6px':'0'};">`
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

    // ── Broadcast de audio (2026-08-05) ──────────────────────────────────────
    // Ao anexar audio, forca cadencia >=20s e bloqueia audience=groups no client
    // (server tambem valida — dupla trava).
    const AUDIO_MIN_CADENCE = 20;
    let previewAudioBlobUrl = null;

    function handleAudioSelect(input) {
        if (!input.files || !input.files[0]) return;
        setAudioPreview(input.files[0]);
    }

    function handleAudioDrop(e) {
        e.preventDefault();
        document.getElementById('audioDropZone').style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (file && (file.type.startsWith('audio/') || file.type === 'video/webm' || file.type === 'video/mp4')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('broadcastAudioInput').files = dt.files;
            setAudioPreview(file);
        }
    }

    function setAudioPreview(file) {
        if (previewAudioBlobUrl) URL.revokeObjectURL(previewAudioBlobUrl);
        previewAudioBlobUrl = URL.createObjectURL(file);
        document.getElementById('audioFileName').textContent = `${file.name} — ${(file.size/1024/1024).toFixed(2)} MB`;
        document.getElementById('audioPreviewPlayer').src = previewAudioBlobUrl;
        document.getElementById('audioPreviewWrap').classList.remove('d-none');
        document.getElementById('audioDropZone').style.display = 'none';

        enforceAudioRestrictions(true);
        const banner = document.getElementById('audioSafetyBanner');
        if (banner) banner.style.boxShadow = '0 0 0 3px rgba(245,158,11,.25)';
    }

    function removeAudio() {
        if (previewAudioBlobUrl) URL.revokeObjectURL(previewAudioBlobUrl);
        previewAudioBlobUrl = null;
        document.getElementById('broadcastAudioInput').value = '';
        document.getElementById('audioPreviewPlayer').src = '';
        document.getElementById('audioPreviewWrap').classList.add('d-none');
        document.getElementById('audioDropZone').style.display = '';
        enforceAudioRestrictions(false);
        const banner = document.getElementById('audioSafetyBanner');
        if (banner) banner.style.boxShadow = '';
    }

    function enforceAudioRestrictions(active) {
        // Cadencia: desabilita 1/3/5/10 quando ha audio; salta pra 20 se estava menor.
        document.querySelectorAll('input[name="cadence"]').forEach(r => {
            const v = parseInt(r.value, 10);
            if (active && v < AUDIO_MIN_CADENCE) {
                r.disabled = true;
                if (r.checked) r.checked = false;
                r.closest('.cadence-option')?.style && (r.closest('.cadence-option').style.opacity = '.4');
            } else {
                r.disabled = false;
                r.closest('.cadence-option')?.style && (r.closest('.cadence-option').style.opacity = '');
            }
        });
        if (active) {
            const c20 = document.querySelector('input[name="cadence"][value="20"]')
                     || document.querySelector('input[name="cadence"][value="30"]');
            if (c20 && !document.querySelector('input[name="cadence"]:checked')) c20.checked = true;
        }

        // Audience=groups agora ACEITA audio (cap 10 grupos, validado no server).
        // Sem manipulacao do radio "groups".
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
        const groupsChecked = document.querySelectorAll('input[name="group_ids[]"]:checked').length;
        
        const isScheduled = document.getElementById('scheduleToggle').checked;
        const scheduledTime = document.getElementById('scheduledAtInput').value;

        const groupMode = document.querySelector('input[name="group_send_mode"]:checked')?.value || 'group';
        const groupModeTxt = groupMode === 'members' ? ' — individual p/ cada membro' : ' — mensagem no grupo';
        const audienceTxt = audience === 'all'
            ? 'Todos os contatos do CRM'
            : audience === 'groups'
                ? `${groupsChecked} grupo(s) selecionado(s)${groupModeTxt}`
                : 'Números específicos';
        const imageTxt   = hasImage ? '<span style="color:#4f46e5;font-weight:600;"><i class="fas fa-image me-1"></i>Com imagem</span> + ' : '';
        const msgPreview = msg ? `"${msg.substring(0, 60)}${msg.length > 60 ? '…' : ''}"` : '<em>sem texto</em>';
        
        let scheduleInfo = '';
        if (isScheduled && scheduledTime) {
            const date = new Date(scheduledTime);
            scheduleInfo = `<div class="mt-2 text-warning fw-bold"><i class="fas fa-clock me-2"></i>Agendado para: ${date.toLocaleString()}</div>`;
        } else {
            scheduleInfo = `<div class="mt-2 text-primary fw-bold"><i class="fas fa-bolt me-2"></i>Início IMEDIATO</div>`;
        }

        document.getElementById('modalDisparoInfo').innerHTML = `
            <div class="d-flex flex-column gap-2">
                <div><i class="fas fa-users me-2" style="color:#4f46e5;width:16px;"></i><strong>Público:</strong> ${audienceTxt}</div>
                <div><i class="fas fa-comment me-2" style="color:#4f46e5;width:16px;"></i><strong>Mensagem:</strong> ${imageTxt}${msgPreview}</div>
                <div><i class="fas fa-stopwatch me-2" style="color:#4f46e5;width:16px;"></i><strong>Cadência:</strong> ${cadence}s entre envios</div>
                ${scheduleInfo}
            </div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmarDisparo'));
        modal.show();
    }

    document.getElementById('btnConfirmarDisparo').addEventListener('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmarDisparo')).hide();
        const btn = document.querySelector('.launch-btn');
        btn.disabled = true;
        
        const isScheduled = document.getElementById('scheduleToggle').checked;
        if (isScheduled) {
            btn.innerHTML = '<i class="fas fa-clock fa-spin"></i> Agendando...';
        } else {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        }
        
        document.getElementById('broadcastForm').submit();
    });

    // Auto-refresh da tabela a cada 8 segundos se houver campanha em andamento
    setInterval(() => {
        const tableHtml = document.querySelector('.table-responsive')?.innerHTML || '';
        if (tableHtml.includes('Processando')) {
            fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.table-responsive');
                const currentTable = document.querySelector('.table-responsive');
                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }
            })
            .catch(err => console.error('Erro no auto-refresh:', err));
        }
    }, 8000);
</script>
@endpush
