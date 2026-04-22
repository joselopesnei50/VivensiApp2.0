@extends('layouts.app')

@section('title', 'Bot de Gestão — Admin')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold">🤖 Vivensi Command Bot</h1>
            <p class="text-muted mb-0">Configure e gerencie o bot de gestão interna via WhatsApp</p>
        </div>
        <div>
            @php
                $isEnabled = $settings['bot_enabled'] === '1';
                $hasInstance = !empty($settings['bot_instance_name']);
                $isConnected = ($instanceStatus['instance']['state'] ?? '') === 'open';
            @endphp
            @if($isEnabled && $hasInstance && $isConnected)
                <span class="badge bg-success fs-6 px-3 py-2">🟢 Bot Ativo e Conectado</span>
            @elseif($isEnabled && $hasInstance)
                <span class="badge bg-warning text-dark fs-6 px-3 py-2">🟡 Ativo — Aguardando Conexão</span>
            @else
                <span class="badge bg-secondary fs-6 px-3 py-2">⚪ Bot Desativado</span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Abas --}}
    <ul class="nav nav-tabs mb-4" id="botTabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-config">⚙️ Configurações</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-users">👥 Usuários</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-messages">💬 Mensagens</a></li>
    </ul>

    <div class="tab-content">

        {{-- ── ABA 1: Configurações ──────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-config">
            <div class="row g-4">

                {{-- Configurações Gerais --}}
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header fw-bold">⚙️ Configurações Gerais</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.bot.save') }}">
                                @csrf

                                {{-- Toggle --}}
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="bot_enabled" id="bot_enabled" value="1" {{ ($settings['bot_enabled'] ?? '0') === '1' ? 'checked' : '' }} style="width:3rem;height:1.5rem;">
                                    <label class="form-check-label fw-bold ms-2 fs-5" for="bot_enabled">
                                        Habilitar o Bot de Gestão
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">📱 Número do Bot (formato E.164)</label>
                                    <input type="text" name="bot_phone" class="form-control" placeholder="5516997618695" value="{{ $settings['bot_phone'] ?? '' }}">
                                    <div class="form-text">Somente números, incluindo código do país (55) e DDD.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">🔌 Nome da Instância (Evolution API)</label>
                                    <input type="text" name="bot_instance_name" class="form-control" placeholder="vivensi-bot" value="{{ $settings['bot_instance_name'] ?? '' }}">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">🌐 URL do Webhook</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light" value="{{ $webhookUrl }}" id="webhookUrlInput" readonly>
                                        <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); this.textContent='✅ Copiado!'">📋 Copiar</button>
                                    </div>
                                    <div class="form-text">Configure este URL no webhook da instância do bot na Evolution API.</div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4">💾 Salvar Configurações</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Painel de Instância --}}
                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-header fw-bold">🔌 Instância na Evolution API</div>
                        <div class="card-body">

                            {{-- Status atual --}}
                            <div id="instanceStatusBadge" class="mb-3">
                                @if($instanceStatus && !isset($instanceStatus['error']))
                                    @php $state = $instanceStatus['instance']['state'] ?? 'unknown'; @endphp
                                    @if($state === 'open')
                                        <div class="alert alert-success py-2">✅ Conectado e funcionando</div>
                                    @elseif($state === 'connecting')
                                        <div class="alert alert-warning py-2">🔄 Conectando... aguarde</div>
                                    @else
                                        <div class="alert alert-secondary py-2">⚪ {{ ucfirst($state) }}</div>
                                    @endif
                                @elseif(empty($settings['bot_instance_name']))
                                    <div class="alert alert-info py-2">ℹ️ Defina o nome da instância acima primeiro.</div>
                                @else
                                    <div class="alert alert-danger py-2">🔴 Instância não encontrada ou sem conexão.</div>
                                @endif
                            </div>

                            {{-- Botão criar instância --}}
                            <div class="d-grid mb-3">
                                <button id="btnCreateInstance" class="btn btn-success" onclick="createInstance()">
                                    ➕ Criar Instância na Evolution API
                                </button>
                            </div>

                            {{-- QR Code --}}
                            <div id="qrSection" class="text-center d-none">
                                <p class="text-muted mb-2">Escaneie o QR Code com o número do bot:</p>
                                <div id="qrCodeContainer" class="d-flex justify-content-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshQr()">🔄 Atualizar QR</button>
                                    <button class="btn btn-sm btn-outline-success" onclick="checkStatus()">🔍 Verificar Conexão</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── ABA 2: Usuários ───────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-users">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">👥 Usuários com Acesso ao Bot</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Perfil</th>
                                    <th>Telefone cadastrado</th>
                                    <th>Status Bot</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                <tr>
                                    <td class="fw-semibold">{{ $u->name }}</td>
                                    <td class="text-muted small">{{ $u->email }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($u->role) { 'super_admin' => 'danger', 'ngo' => 'success', 'manager' => 'primary', 'employee' => 'secondary', default => 'light text-dark' } }}">
                                            {{ $u->role }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($u->phone)
                                            <code>{{ $u->phone }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($u->phone)
                                            <span class="badge bg-success">✅ Pode usar</span>
                                        @else
                                            <span class="badge bg-secondary">❌ Sem número</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                            onclick="openPhoneModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->phone }}')">
                                            ✏️ Editar
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ABA 3: Mensagens ──────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-messages">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">💬 Mensagens Personalizadas do Bot</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.bot.save') }}">
                        @csrf
                        <div class="row g-4">
                            @foreach([
                                'bot_msg_welcome_ngo'      => ['🌱 Boas-vindas — NGO / Super Admin', 'Aparece quando um usuário NGO ou Super Admin envia "oi"'],
                                'bot_msg_welcome_manager'  => ['📊 Boas-vindas — Gestor de Projetos', 'Aparece para usuários com perfil Manager'],
                                'bot_msg_welcome_employee' => ['👷 Boas-vindas — Colaborador', 'Aparece para usuários com perfil Employee'],
                                'bot_msg_welcome_common'   => ['🏠 Boas-vindas — Pessoa Comum', 'Aparece para usuários com perfil comum (finanças pessoais)'],
                                'bot_msg_help'             => ['❓ Mensagem de Ajuda (opção 5)', 'Enviada quando o usuário digita "5" ou "ajuda"'],
                            ] as $key => [$label, $help])
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ $label }}</label>
                                <textarea name="{{ $key }}" class="form-control font-monospace" rows="6" placeholder="Deixe em branco para usar o padrão do sistema.">{{ $settings[$key] ?? '' }}</textarea>
                                <div class="form-text">{{ $help }}</div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">💾 Salvar Mensagens</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal: Editar Telefone --}}
<div class="modal fade" id="phoneModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="phoneForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Telefone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Usuário: <strong id="modalUserName"></strong></p>
                    <label class="form-label mt-3">Número de WhatsApp</label>
                    <input type="text" name="phone" id="modalPhone" class="form-control" placeholder="5516997618695">
                    <div class="form-text">Somente números, incluindo código do país (55) e DDD. Deixe vazio para remover o acesso.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">💾 Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const instanceName = document.querySelector('[name="bot_instance_name"]')?.value || '';

    function openPhoneModal(userId, userName, currentPhone) {
        document.getElementById('modalUserName').textContent = userName;
        document.getElementById('modalPhone').value = currentPhone || '';
        document.getElementById('phoneForm').action = `/admin/bot/users/${userId}/phone`;
        new bootstrap.Modal(document.getElementById('phoneModal')).show();
    }

    async function createInstance() {
        const name = document.querySelector('[name="bot_instance_name"]').value;
        if (!name) { alert('Preencha e salve o nome da instância primeiro.'); return; }

        document.getElementById('btnCreateInstance').disabled = true;
        document.getElementById('btnCreateInstance').textContent = '⏳ Criando...';

        try {
            const res = await fetch('{{ route("admin.bot.instance.create") }}', {
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

        document.getElementById('btnCreateInstance').disabled = false;
        document.getElementById('btnCreateInstance').textContent = '➕ Criar Instância na Evolution API';
    }

    async function refreshQr() {
        const name = document.querySelector('[name="bot_instance_name"]').value;
        document.getElementById('qrCodeContainer').innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        document.getElementById('qrSection').classList.remove('d-none');

        try {
            const res = await fetch(`{{ route("admin.bot.instance.qr") }}?instance=${encodeURIComponent(name)}`);
            const data = await res.json();
            if (data.qrcode) {
                document.getElementById('qrCodeContainer').innerHTML = `<img src="${data.qrcode}" style="max-width:220px;border-radius:8px;" alt="QR Code">`;
            } else {
                document.getElementById('qrCodeContainer').innerHTML = '<p class="text-muted">QR ainda não disponível. Aguarde e tente novamente.</p>';
            }
        } catch(e) {
            document.getElementById('qrCodeContainer').innerHTML = '<p class="text-danger">Erro ao buscar QR Code.</p>';
        }
    }

    async function checkStatus() {
        const name = document.querySelector('[name="bot_instance_name"]').value;
        try {
            const res = await fetch(`{{ route("admin.bot.instance.status") }}?instance=${encodeURIComponent(name)}`);
            const data = await res.json();
            const state = data?.instance?.state || data?.state || 'unknown';
            const badge = document.getElementById('instanceStatusBadge');
            if (state === 'open') {
                badge.innerHTML = '<div class="alert alert-success py-2">✅ Conectado e funcionando!</div>';
            } else {
                badge.innerHTML = `<div class="alert alert-warning py-2">🔄 Estado: ${state}. Continue aguardando.</div>`;
            }
        } catch(e) {
            alert('Erro ao verificar status.');
        }
    }
</script>
@endpush
@endsection
