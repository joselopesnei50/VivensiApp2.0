@extends('layouts.app')

@section('title', 'Command Bot — Admin')

@section('content')

@php
    $isEnabled   = ($settings['bot_enabled'] ?? '0') === '1';
    $hasInstance = !empty($settings['bot_instance_name']);
    $connState   = $instanceStatus['instance']['state'] ?? '';
    $isConnected = $connState === 'open';

    $msgFields = [
        'bot_msg_welcome_ngo'      => ['Boas-vindas — NGO / Super Admin',      'Aparece quando um usuário NGO ou Super Admin envia "oi"'],
        'bot_msg_welcome_manager'  => ['Boas-vindas — Gestor de Projetos',      'Aparece para usuários com perfil Manager'],
        'bot_msg_welcome_employee' => ['Boas-vindas — Colaborador',             'Aparece para usuários com perfil Employee'],
        'bot_msg_welcome_common'   => ['Boas-vindas — Pessoa Comum',            'Aparece para usuários com perfil comum (finanças pessoais)'],
        'bot_msg_help'             => ['Mensagem de Ajuda (opção 5)',            'Enviada quando o usuário digita "5" ou "ajuda"'],
    ];

    $roleBadge = [
        'super_admin' => ['bg:#fef2f2;color:#dc2626;', 'Super Admin'],
        'ngo'         => ['bg:#f0fdf4;color:#16a34a;', 'NGO'],
        'manager'     => ['bg:#eff6ff;color:#3b82f6;', 'Manager'],
        'employee'    => ['bg:#f8fafc;color:#64748b;', 'Employee'],
    ];
@endphp

{{-- ── HEADER ── --}}
<div class="bot-header mb-4">
    <div>
        <p class="bot-eyebrow">Super Admin · WhatsApp</p>
        <h1 class="bot-title">Vivensi <span class="bot-title-accent">Command Bot</span></h1>
        <p class="bot-sub">Configure e gerencie o bot de gestão interna via WhatsApp.</p>
    </div>
    <div>
        @if($isEnabled && $hasInstance && $isConnected)
            <div class="conn-badge conn-ok"><span class="conn-dot conn-dot-ok"></span> Bot Ativo e Conectado</div>
        @elseif($isEnabled && $hasInstance)
            <div class="conn-badge conn-warn"><span class="conn-dot conn-dot-warn"></span> Ativo — Aguardando Conexão</div>
        @else
            <div class="conn-badge conn-off"><span class="conn-dot conn-dot-off"></span> Bot Desativado</div>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="bot-alert bot-alert-ok mb-4">
        <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
    </div>
@endif

{{-- ── TABS ── --}}
<div class="bot-tabs mb-4">
    <button class="bot-tab active" onclick="switchTab('config', this)">
        <i class="fas fa-sliders me-2"></i>Configurações
    </button>
    <button class="bot-tab" onclick="switchTab('users', this)">
        <i class="fas fa-users me-2"></i>Usuários
        <span class="tab-count">{{ $users->count() }}</span>
    </button>
    <button class="bot-tab" onclick="switchTab('messages', this)">
        <i class="fas fa-comment-dots me-2"></i>Mensagens
    </button>
</div>

{{-- ── TAB: CONFIG ── --}}
<div id="tab-config" class="tab-pane-bot">
    <div class="row g-3">

        {{-- General Settings --}}
        <div class="col-lg-7">
            <div class="exec-card">
                <div class="exec-card-head">
                    <div>
                        <div class="exec-card-title">Configurações Gerais</div>
                        <div class="exec-card-sub">Número, instância e ativação do bot</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.bot.save') }}">
                    @csrf

                    {{-- Toggle --}}
                    <div class="toggle-row mb-4">
                        <div>
                            <div class="toggle-label">Habilitar o Bot de Gestão</div>
                            <div class="toggle-sub">Quando desativado, o bot ignora todas as mensagens recebidas</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="bot_enabled" value="1" {{ $isEnabled ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="field mb-3">
                        <label class="field-label">
                            <i class="fas fa-mobile-screen me-1 text-primary"></i> Número do Bot (E.164)
                        </label>
                        <input type="text" name="bot_phone" class="field-input"
                               placeholder="5516997618695"
                               value="{{ $settings['bot_phone'] ?? '' }}">
                        <div class="field-hint">Somente números, incluindo código do país (55) e DDD.</div>
                    </div>

                    <div class="field mb-3">
                        <label class="field-label">
                            <i class="fas fa-plug me-1 text-primary"></i> Nome da Instância (Evolution API)
                        </label>
                        <input type="text" name="bot_instance_name" class="field-input"
                               placeholder="vivensi-bot"
                               value="{{ $settings['bot_instance_name'] ?? '' }}">
                    </div>

                    <div class="field mb-4">
                        <label class="field-label">
                            <i class="fas fa-link me-1 text-primary"></i> URL do Webhook
                        </label>
                        <div class="copy-row">
                            <input type="text" class="field-input" value="{{ $webhookUrl }}" id="webhookUrlInput" readonly>
                            <button type="button" class="copy-btn" id="copyBtn"
                                    onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); document.getElementById('copyBtn').innerHTML='<i class=\'fas fa-check\'></i> Copiado'; setTimeout(() => document.getElementById('copyBtn').innerHTML='<i class=\'fas fa-copy\'></i> Copiar', 2000);">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                        <div class="field-hint">Configure este URL no webhook da instância na Evolution API.</div>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-floppy-disk me-2"></i>Salvar Configurações
                    </button>
                </form>
            </div>
        </div>

        {{-- Instance Panel --}}
        <div class="col-lg-5">
            <div class="exec-card h-100">
                <div class="exec-card-head">
                    <div>
                        <div class="exec-card-title">Instância Evolution API</div>
                        <div class="exec-card-sub">Status e conexão via QR Code</div>
                    </div>
                </div>

                {{-- Status --}}
                <div id="instanceStatusBadge" class="mb-4">
                    @if($instanceStatus && !isset($instanceStatus['error']))
                        @if($connState === 'open')
                            <div class="status-block status-ok">
                                <i class="fas fa-circle-check me-2"></i>Conectado e funcionando
                            </div>
                        @elseif($connState === 'connecting')
                            <div class="status-block status-warn">
                                <i class="fas fa-spinner fa-spin me-2"></i>Conectando... aguarde
                            </div>
                        @else
                            <div class="status-block status-off">
                                <i class="fas fa-circle me-2"></i>{{ ucfirst($connState ?: 'Desconhecido') }}
                            </div>
                        @endif
                    @elseif(empty($settings['bot_instance_name']))
                        <div class="status-block status-info">
                            <i class="fas fa-circle-info me-2"></i>Defina o nome da instância primeiro.
                        </div>
                    @else
                        <div class="status-block status-error">
                            <i class="fas fa-triangle-exclamation me-2"></i>Instância não encontrada ou sem conexão.
                        </div>
                    @endif
                </div>

                <button id="btnCreateInstance" class="btn-create w-100 mb-4" onclick="createInstance()">
                    <i class="fas fa-plus me-2"></i>Criar Instância na Evolution API
                </button>

                {{-- QR Code --}}
                <div id="qrSection" class="d-none text-center">
                    <p class="field-hint mb-3">Escaneie com o número do bot no WhatsApp:</p>
                    <div id="qrCodeContainer" class="qr-box">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <button class="btn-ghost-sm" onclick="refreshQr()">
                            <i class="fas fa-arrows-rotate me-1"></i>Atualizar QR
                        </button>
                        <button class="btn-ghost-sm btn-ghost-green" onclick="checkStatus()">
                            <i class="fas fa-magnifying-glass me-1"></i>Verificar Conexão
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- ── TAB: USERS ── --}}
<div id="tab-users" class="tab-pane-bot d-none">
    <div class="exec-card p-0">
        <div class="exec-card-head px-4 py-3 border-bottom">
            <div>
                <div class="exec-card-title">Usuários com Acesso ao Bot</div>
                <div class="exec-card-sub">Usuários com telefone cadastrado podem interagir via WhatsApp</div>
            </div>
            <span class="tab-count-lg">{{ $users->where('phone', '!=', null)->count() }} com acesso</span>
        </div>
        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Perfil</th>
                        <th>Telefone</th>
                        <th>Status Bot</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    @php
                        $rb = $roleBadge[$u->role] ?? ['bg:#f8fafc;color:#64748b;', $u->role];
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="t-avatar t-av-blue">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                <div>
                                    <div class="cell-bold">{{ $u->name }}</div>
                                    <div class="cell-muted" style="font-size:.72rem;">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge" style="{{ $rb[0] }}">{{ $rb[1] }}</span>
                        </td>
                        <td>
                            @if($u->phone)
                                <code class="phone-code">{{ $u->phone }}</code>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($u->phone)
                                <span class="status-pill s-ok"><i class="fas fa-circle" style="font-size:.45rem;"></i> Ativo</span>
                            @else
                                <span class="status-pill s-off"><i class="fas fa-circle" style="font-size:.45rem;"></i> Sem número</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="action-edit"
                                onclick="openPhoneModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->phone }}')">
                                <i class="fas fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── TAB: MESSAGES ── --}}
<div id="tab-messages" class="tab-pane-bot d-none">
    <div class="exec-card">
        <div class="exec-card-head">
            <div>
                <div class="exec-card-title">Mensagens Personalizadas do Bot</div>
                <div class="exec-card-sub">Deixe em branco para usar o texto padrão do sistema</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.bot.save') }}">
            @csrf
            <div class="row g-3">
                @foreach($msgFields as $key => $info)
                @php
                    $mLabel = $info[0];
                    $mHelp  = $info[1];
                @endphp
                <div class="col-md-6">
                    <div class="field">
                        <label class="field-label">{{ $mLabel }}</label>
                        <textarea name="{{ $key }}" class="field-input font-monospace"
                                  rows="6"
                                  placeholder="Deixe em branco para usar o padrão.">{{ $settings[$key] ?? '' }}</textarea>
                        <div class="field-hint">{{ $mHelp }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="submit" class="btn-save">
                    <i class="fas fa-floppy-disk me-2"></i>Salvar Mensagens
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: Phone ── --}}
<div class="modal fade" id="phoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="phoneForm">
            @csrf
            <div class="modal-content" style="border-radius:20px;border:1px solid #f1f5f9;">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding:20px 24px;">
                    <h5 class="modal-title" style="font-weight:800;font-size:1rem;color:#0f172a;">
                        <i class="fas fa-mobile-screen me-2 text-primary"></i>Editar Telefone
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <p class="field-hint mb-3">Usuário: <strong id="modalUserName" style="color:#0f172a;"></strong></p>
                    <div class="field">
                        <label class="field-label">Número de WhatsApp</label>
                        <input type="text" name="phone" id="modalPhone" class="field-input" placeholder="5516997618695">
                        <div class="field-hint">Somente números (55 + DDD + número). Deixe vazio para remover o acesso.</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:16px 24px;gap:8px;">
                    <button type="button" class="btn-ghost-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-save" style="padding:9px 20px;">
                        <i class="fas fa-floppy-disk me-2"></i>Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
/* ── HEADER ── */
.bot-header { display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap; }
.bot-eyebrow { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#94a3b8;margin-bottom:4px; }
.bot-title { font-size:2rem;font-weight:900;color:#0f172a;letter-spacing:-1.5px;margin-bottom:4px;line-height:1.1; }
.bot-title-accent { color:#6366f1; }
.bot-sub { font-size:.88rem;color:#64748b;font-weight:500;margin:0; }

.conn-badge {
    display:inline-flex;align-items:center;gap:8px;
    padding:8px 16px;border-radius:100px;font-size:.8rem;font-weight:700;
}
.conn-ok   { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
.conn-warn { background:#fffbeb;color:#d97706;border:1px solid #fde68a; }
.conn-off  { background:#f8fafc;color:#64748b;border:1px solid #e2e8f0; }
.conn-dot  { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
.conn-dot-ok   { background:#22c55e;animation:connPulse 2s infinite; }
.conn-dot-warn { background:#f59e0b; }
.conn-dot-off  { background:#cbd5e1; }
@keyframes connPulse {
    0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.4);}
    50%{box-shadow:0 0 0 5px rgba(34,197,94,0);}
}

.bot-alert { display:flex;align-items:center;padding:12px 16px;border-radius:12px;font-size:.85rem;font-weight:600; }
.bot-alert-ok { background:#f0fdf4;color:#166534;border:1px solid #bbf7d0; }

/* ── TABS ── */
.bot-tabs { display:flex;gap:4px;background:#f8fafc;padding:4px;border-radius:14px;width:fit-content; }
.bot-tab {
    display:inline-flex;align-items:center;gap:6px;
    padding:9px 18px;border-radius:10px;border:none;
    font-size:.82rem;font-weight:700;color:#64748b;
    background:transparent;cursor:pointer;transition:all .2s;
}
.bot-tab.active { background:white;color:#0f172a;box-shadow:0 1px 4px rgba(0,0,0,.08); }
.bot-tab:hover:not(.active) { color:#475569; }
.tab-count {
    background:#e0e7ff;color:#4f46e5;
    padding:1px 7px;border-radius:20px;font-size:.68rem;font-weight:800;
}
.tab-count-lg {
    background:#f0fdf4;color:#16a34a;
    padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;
}

/* ── EXEC CARD ── */
.exec-card {
    background:white;border:1px solid #f1f5f9;
    border-radius:20px;padding:26px;
    box-shadow:0 4px 18px rgba(0,0,0,.03);
}
.exec-card-head { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px; }
.exec-card-title { font-size:.92rem;font-weight:800;color:#0f172a;letter-spacing:-.02em; }
.exec-card-sub { font-size:.73rem;color:#94a3b8;font-weight:500;margin-top:2px; }

/* ── FORM ── */
.toggle-row { display:flex;justify-content:space-between;align-items:center;gap:20px;padding:16px 20px;background:#f8fafc;border-radius:14px;border:1px solid #f1f5f9; }
.toggle-label { font-size:.88rem;font-weight:700;color:#0f172a; }
.toggle-sub { font-size:.72rem;color:#94a3b8;margin-top:2px; }

.switch { position:relative;display:inline-block;width:46px;height:26px;flex-shrink:0; }
.switch input { opacity:0;width:0;height:0; }
.slider { position:absolute;inset:0;background:#e2e8f0;border-radius:26px;cursor:pointer;transition:.3s; }
.slider:before { position:absolute;content:'';height:20px;width:20px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
.switch input:checked + .slider { background:#6366f1; }
.switch input:checked + .slider:before { transform:translateX(20px); }

.field { margin-bottom:0; }
.field-label { display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:6px; }
.field-input {
    width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;
    font-size:.88rem;font-family:inherit;color:#111827;background:#fafafa;
    transition:border-color .2s,box-shadow .2s;outline:none;
}
.field-input:focus { border-color:#6366f1;background:white;box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.field-input[readonly] { background:#f8fafc;color:#64748b;cursor:default; }
.field-hint { font-size:.72rem;color:#94a3b8;margin-top:5px; }

.copy-row { display:flex;gap:8px; }
.copy-btn {
    flex-shrink:0;padding:10px 14px;border-radius:10px;border:1.5px solid #e5e7eb;
    background:white;font-size:.8rem;font-weight:700;color:#475569;cursor:pointer;
    white-space:nowrap;transition:all .2s;
}
.copy-btn:hover { background:#f1f5f9;border-color:#cbd5e1; }

.btn-save {
    display:inline-flex;align-items:center;padding:10px 22px;
    background:#6366f1;color:white;border:none;border-radius:10px;
    font-size:.85rem;font-weight:700;cursor:pointer;transition:all .2s;
}
.btn-save:hover { background:#4f46e5;transform:translateY(-1px); }

.btn-create {
    display:flex;align-items:center;justify-content:center;padding:11px 20px;
    background:#f0fdf4;color:#16a34a;border:1.5px solid #bbf7d0;border-radius:10px;
    font-size:.85rem;font-weight:700;cursor:pointer;transition:all .2s;
}
.btn-create:hover { background:#dcfce7; }
.btn-create:disabled { opacity:.5;cursor:not-allowed; }

.btn-ghost-sm {
    display:inline-flex;align-items:center;padding:7px 14px;
    border:1.5px solid #e2e8f0;background:white;border-radius:8px;
    font-size:.78rem;font-weight:700;color:#475569;cursor:pointer;transition:all .2s;
}
.btn-ghost-sm:hover { background:#f8fafc; }
.btn-ghost-green { border-color:#bbf7d0;color:#16a34a; }
.btn-ghost-green:hover { background:#f0fdf4; }

/* ── STATUS BLOCKS ── */
.status-block {
    display:flex;align-items:center;padding:12px 16px;
    border-radius:12px;font-size:.83rem;font-weight:600;
}
.status-ok    { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
.status-warn  { background:#fffbeb;color:#d97706;border:1px solid #fde68a; }
.status-off   { background:#f8fafc;color:#64748b;border:1px solid #e2e8f0; }
.status-info  { background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe; }
.status-error { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }

/* ── QR ── */
.qr-box {
    width:220px;height:220px;margin:0 auto;border-radius:16px;
    border:2px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;
    background:#fafafa;overflow:hidden;
}
.qr-box img { border-radius:14px;max-width:200px; }

/* ── TABLE ── */
.dash-table { width:100%;border-collapse:collapse; }
.dash-table thead th {
    padding:11px 16px;text-align:left;
    font-size:.67rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.08em;color:#94a3b8;border-bottom:1px solid #f1f5f9;
}
.dash-table tbody td { padding:13px 16px;font-size:.82rem;color:#475569;border-bottom:1px solid #f8fafc; }
.dash-table tbody tr:last-child td { border-bottom:none; }
.dash-table tbody tr:hover td { background:#fafbff; }
.cell-bold { font-weight:700;color:#0f172a; }
.cell-muted { color:#94a3b8; }

.t-avatar {
    width:32px;height:32px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:.72rem;font-weight:800;flex-shrink:0;
}
.t-av-blue { background:#eff6ff;color:#3b82f6; }

.role-badge {
    display:inline-flex;align-items:center;
    padding:3px 9px;border-radius:8px;font-size:.68rem;font-weight:700;
}

.phone-code {
    background:#f1f5f9;color:#1e293b;
    padding:3px 8px;border-radius:6px;font-size:.78rem;
}

.status-pill {
    display:inline-flex;align-items:center;gap:5px;
    padding:3px 9px;border-radius:20px;font-size:.68rem;font-weight:700;
}
.s-ok  { background:#f0fdf4;color:#16a34a; }
.s-off { background:#f8fafc;color:#94a3b8; }

.action-edit {
    width:30px;height:30px;border-radius:8px;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:.75rem;border:none;cursor:pointer;
    background:#f1f5f9;color:#475569;transition:all .15s;
}
.action-edit:hover { background:#e0e7ff;color:#4f46e5; }

textarea.field-input { resize:vertical;min-height:120px; }
</style>
@endpush

@push('scripts')
<script>
function switchTab(name, el) {
    document.querySelectorAll('.tab-pane-bot').forEach(p => p.classList.add('d-none'));
    document.querySelectorAll('.bot-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.remove('d-none');
    el.classList.add('active');
}

function openPhoneModal(userId, userName, currentPhone) {
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalPhone').value = currentPhone || '';
    document.getElementById('phoneForm').action = '/admin/bot/users/' + userId + '/phone';
    new bootstrap.Modal(document.getElementById('phoneModal')).show();
}

async function createInstance() {
    const name = document.querySelector('[name="bot_instance_name"]').value;
    if (!name) { alert('Preencha e salve o nome da instância primeiro.'); return; }

    const btn = document.getElementById('btnCreateInstance');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando...';

    try {
        const res  = await fetch('{{ route("admin.bot.instance.create") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ instance_name: name })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('qrSection').classList.remove('d-none');
            refreshQr();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch(e) { alert('Falha na requisição: ' + e.message); }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-plus me-2"></i>Criar Instância na Evolution API';
}

async function refreshQr() {
    const name = document.querySelector('[name="bot_instance_name"]').value;
    document.getElementById('qrCodeContainer').innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    document.getElementById('qrSection').classList.remove('d-none');

    try {
        const res  = await fetch('{{ route("admin.bot.instance.qr") }}?instance=' + encodeURIComponent(name));
        const data = await res.json();
        if (data.qrcode) {
            document.getElementById('qrCodeContainer').innerHTML = '<img src="' + data.qrcode + '" alt="QR Code">';
        } else {
            document.getElementById('qrCodeContainer').innerHTML = '<p class="text-muted small p-3">QR ainda não disponível. Aguarde e tente novamente.</p>';
        }
    } catch(e) {
        document.getElementById('qrCodeContainer').innerHTML = '<p class="text-danger small p-3">Erro ao buscar QR Code.</p>';
    }
}

async function checkStatus() {
    const name = document.querySelector('[name="bot_instance_name"]').value;
    try {
        const res   = await fetch('{{ route("admin.bot.instance.status") }}?instance=' + encodeURIComponent(name));
        const data  = await res.json();
        const state = data?.instance?.state || data?.state || 'unknown';
        const badge = document.getElementById('instanceStatusBadge');
        if (state === 'open') {
            badge.innerHTML = '<div class="status-block status-ok"><i class="fas fa-circle-check me-2"></i>Conectado e funcionando!</div>';
        } else {
            badge.innerHTML = '<div class="status-block status-warn"><i class="fas fa-spinner fa-spin me-2"></i>Estado: ' + state + '. Continue aguardando.</div>';
        }
    } catch(e) { alert('Erro ao verificar status.'); }
}
</script>
@endpush

@endsection
