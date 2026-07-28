@extends('layouts.app')

@section('title', 'Conectar WhatsApp Business — Onboarding assistido')

@push('styles')
<style>
    .mconn-hero {
        background: linear-gradient(135deg, #075E54 0%, #128C7E 100%);
        color: #fff; padding: 28px 32px; border-radius: 16px; margin-bottom: 24px;
    }
    .mconn-hero h1 { margin: 0 0 8px; font-size: 1.5rem; font-weight: 800; }
    .mconn-hero p { margin: 0; opacity: .95; line-height: 1.5; }

    .mconn-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 24px;
        align-items: flex-start;
    }
    @media (max-width: 900px) {
        .mconn-grid { grid-template-columns: 1fr; }
    }

    .mconn-card {
        background: #fff; border-radius: 14px; padding: 24px;
        box-shadow: 0 2px 12px rgba(15,23,42,.06);
        border: 1px solid #e2e8f0;
    }
    .mconn-card h3 {
        margin: 0 0 4px;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .mconn-card .sub {
        color: #64748b; font-size: .88rem; margin-bottom: 20px;
    }

    .mconn-field { margin-bottom: 18px; }
    .mconn-field label {
        display: block; font-weight: 700; color: #1e293b;
        font-size: .9rem; margin-bottom: 6px;
    }
    .mconn-field input, .mconn-field textarea {
        width: 100%; padding: 12px 14px;
        border: 1.5px solid #cbd5e1; border-radius: 10px;
        font-size: .95rem; font-family: inherit;
        background: #fff; color: #0f172a;
    }
    .mconn-field textarea { min-height: 96px; resize: vertical; font-family: 'Courier New', monospace; font-size: .8rem; }
    .mconn-field input:focus, .mconn-field textarea:focus {
        outline: none; border-color: #128C7E;
        box-shadow: 0 0 0 3px rgba(18,140,126,.15);
    }
    .mconn-field .hint {
        display: block; font-size: .78rem; color: #64748b; margin-top: 5px;
    }
    .mconn-field .hint code {
        background: #f1f5f9; padding: 1px 6px; border-radius: 4px;
        font-size: .85em; color: #0f172a;
    }
    .mconn-field.optional label::after {
        content: ' (opcional)';
        color: #94a3b8; font-weight: 500; font-size: .82em;
    }

    .mconn-btn {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 13px 26px; background: #128C7E; color: #fff !important;
        border: 0; border-radius: 10px; cursor: pointer;
        font-size: .98rem; font-weight: 700; text-decoration: none;
        transition: background .15s;
    }
    .mconn-btn:hover { background: #0f6f65; }
    .mconn-btn:disabled { background: #94a3b8; cursor: not-allowed; }

    .mconn-alert {
        padding: 14px 18px; border-radius: 10px; margin-bottom: 18px;
        font-size: .92rem; line-height: 1.5;
    }
    .mconn-alert.err   { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }
    .mconn-alert.warn  { background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B; }
    .mconn-alert.info  { background: #DBEAFE; color: #1E3A8A; border-left: 4px solid #3B82F6; }

    /* ── Tutorial (coluna direita) ─────────────────────────────────── */
    .mconn-step {
        display: flex; gap: 14px; align-items: flex-start;
        padding: 16px; border-radius: 12px;
        background: #f8fafc; border: 1px solid #e2e8f0;
        margin-bottom: 12px;
    }
    .mconn-step-num {
        flex-shrink: 0;
        width: 32px; height: 32px; border-radius: 50%;
        background: #128C7E; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .95rem;
    }
    .mconn-step-body strong {
        display: block; color: #0f172a; font-size: .95rem; margin-bottom: 4px;
    }
    .mconn-step-body p {
        margin: 0 0 6px; color: #475569; font-size: .87rem; line-height: 1.5;
    }
    .mconn-step-body a {
        display: inline-flex; align-items: center; gap: 5px;
        color: #128C7E; text-decoration: none; font-weight: 600;
        font-size: .82rem;
    }
    .mconn-step-body a:hover { text-decoration: underline; }
    .mconn-step-body code {
        background: #fff; border: 1px solid #e2e8f0; padding: 1px 6px;
        border-radius: 4px; font-size: .85em; color: #128C7E;
    }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 1100px; margin: 24px auto;">

    <div class="mconn-hero">
        <h1><i class="fab fa-whatsapp"></i> Conectar seu WhatsApp Business à Vivensi</h1>
        <p>
            Onboarding assistido: você cria e verifica o número no
            <strong>Business Manager da Meta</strong>, cola três credenciais aqui,
            e o Vivensi ativa sua conta em segundos. Nenhum dado sensível fica
            visível — o token é armazenado criptografado (AES-256).
        </p>
    </div>

    @if(!$configured)
        <div class="mconn-alert warn">
            <strong>Configuração incompleta:</strong> as credenciais globais Meta do Vivensi
            (app_id / app_secret) ainda não foram cadastradas. Contate o suporte antes de prosseguir.
        </div>
    @endif

    @if(session('error'))
        <div class="mconn-alert err">
            <strong>Não foi possível conectar:</strong> {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mconn-alert info">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mconn-alert err">
            <strong>Verifique os campos:</strong>
            <ul style="margin: 8px 0 0 22px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mconn-grid">

        {{-- ─── COLUNA 1: FORMULÁRIO ─────────────────────────────────── --}}
        <div class="mconn-card">
            <h3>Cole suas 3 credenciais</h3>
            <div class="sub">Copie do Business Manager e cole abaixo. Não compartilhe com ninguém fora do Vivensi.</div>

            <form method="POST" action="{{ route('whatsapp.cloud.manual.store') }}" autocomplete="off">
                @csrf

                <div class="mconn-field">
                    <label for="waba_id">1. WABA ID</label>
                    <input type="text" id="waba_id" name="waba_id"
                           value="{{ old('waba_id') }}"
                           inputmode="numeric" pattern="\d+" required
                           placeholder="Ex: 1331534925713250"
                           autocomplete="off" spellcheck="false">
                    <span class="hint">Também chamado de <code>WhatsApp Business Account ID</code>.</span>
                </div>

                <div class="mconn-field">
                    <label for="phone_number_id">2. Phone Number ID</label>
                    <input type="text" id="phone_number_id" name="phone_number_id"
                           value="{{ old('phone_number_id') }}"
                           inputmode="numeric" pattern="\d+" required
                           placeholder="Ex: 1168655716337509"
                           autocomplete="off" spellcheck="false">
                    <span class="hint">Encontrado ao lado do número na tela de números do WABA.</span>
                </div>

                <div class="mconn-field">
                    <label for="access_token">3. System User Access Token</label>
                    <textarea id="access_token" name="access_token" required
                              placeholder="EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
                              autocomplete="off" spellcheck="false"></textarea>
                    <span class="hint">
                        Token gerado pra um <strong>System User</strong> com permissões
                        <code>whatsapp_business_messaging</code> e <code>whatsapp_business_management</code>.
                        Não use tokens temporários (expiram em 1h).
                    </span>
                </div>

                <div class="mconn-field optional">
                    <label for="pin">4. PIN de 6 dígitos</label>
                    <input type="text" id="pin" name="pin"
                           value="{{ old('pin') }}"
                           inputmode="numeric" pattern="\d{6}" maxlength="6"
                           placeholder="Ex: 142857"
                           autocomplete="off">
                    <span class="hint">
                        Preencha <strong>apenas se ainda não registrou o número</strong> no Business
                        Manager. Se você já conseguiu enviar uma mensagem de teste na Meta, deixe em branco.
                    </span>
                </div>

                <button type="submit" class="mconn-btn" @if(!$configured) disabled @endif>
                    <i class="fas fa-plug"></i> Conectar meu WhatsApp Business
                </button>
            </form>
        </div>

        {{-- ─── COLUNA 2: TUTORIAL PASSO-A-PASSO ─────────────────────── --}}
        <div class="mconn-card">
            <h3>Como achar cada credencial</h3>
            <div class="sub">5 minutos, direto do Business Manager da Meta.</div>

            <div class="mconn-step">
                <div class="mconn-step-num">1</div>
                <div class="mconn-step-body">
                    <strong>Acesse o Business Manager</strong>
                    <p>Entre com a conta Facebook que criou sua conta empresarial.</p>
                    <a href="https://business.facebook.com" target="_blank" rel="noopener">
                        business.facebook.com <i class="fas fa-arrow-up-right-from-square" style="font-size:.7em;"></i>
                    </a>
                </div>
            </div>

            <div class="mconn-step">
                <div class="mconn-step-num">2</div>
                <div class="mconn-step-body">
                    <strong>Copie o WABA ID</strong>
                    <p>
                        Menu lateral → <em>Contas</em> → <em>Contas do WhatsApp</em>.
                        Clique na sua conta. O <code>WABA ID</code> fica no topo, ao lado do nome (número comprido).
                    </p>
                </div>
            </div>

            <div class="mconn-step">
                <div class="mconn-step-num">3</div>
                <div class="mconn-step-body">
                    <strong>Copie o Phone Number ID</strong>
                    <p>
                        Ainda dentro da WABA, clique na aba <em>Números de telefone</em>.
                        Ao lado do número (formato <code>+55 …</code>) aparece o <code>Phone Number ID</code>.
                    </p>
                </div>
            </div>

            <div class="mconn-step">
                <div class="mconn-step-num">4</div>
                <div class="mconn-step-body">
                    <strong>Crie um System User + Token</strong>
                    <p>
                        Menu <em>Configurações da empresa</em> → <em>Usuários do sistema</em> → <em>Adicionar</em>.
                        Nomeie como &quot;Vivensi Integration&quot; e defina como Admin.
                    </p>
                    <p>
                        Clique em <em>Adicionar ativos</em> → escolha sua WABA → marque
                        <code>Gerenciar conta do WhatsApp</code>.
                    </p>
                    <p>
                        Volte pro System User e clique em <em>Gerar novo token</em>. Escolha o app
                        <strong>NC5HUBDIGITAL-EMP</strong> e marque as permissões
                        <code>whatsapp_business_messaging</code> e <code>whatsapp_business_management</code>.
                        Copie o token e cole no formulário aqui do lado.
                    </p>
                </div>
            </div>

            <div class="mconn-step">
                <div class="mconn-step-num">5</div>
                <div class="mconn-step-body">
                    <strong>Pronto — conectar</strong>
                    <p>
                        Cole as 3 credenciais no formulário ao lado e clique em <em>Conectar</em>.
                        O Vivensi valida com a Meta, inscreve o app pra receber mensagens
                        (<code>subscribed_apps</code>) e ativa sua conta.
                    </p>
                </div>
            </div>

            <div class="mconn-alert info" style="margin-top: 16px; margin-bottom: 0;">
                <strong>Segurança:</strong> o token é armazenado criptografado (AES-256) e nunca
                aparece em logs, telas ou e-mails. Se quiser revogá-lo depois, é só apagar o
                System User no Business Manager — o Vivensi perde acesso imediatamente.
            </div>
        </div>

    </div>

</div>
@endsection
