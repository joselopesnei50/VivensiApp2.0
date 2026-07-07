@extends('layouts.app')

@section('title', 'Conectar WhatsApp Business — Meta Cloud API')

@push('styles')
<style>
    .cloud-hero {
        background: linear-gradient(135deg, #075E54 0%, #128C7E 100%);
        color: #fff; padding: 32px; border-radius: 16px; margin-bottom: 24px;
    }
    .cloud-hero h1 { margin: 0 0 8px; font-size: 1.6rem; }
    .cloud-hero p { margin: 0; opacity: .95; }
    .cloud-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.06); margin-bottom: 20px; }
    .cloud-step { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 18px; }
    .cloud-step-num { background: #128C7E; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
    .cloud-step p { margin: 4px 0 0; color: #444; }
    .cloud-btn { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; background: #1877F2; color: #fff !important; border: 0; border-radius: 10px; cursor: pointer; font-size: 1rem; font-weight: 600; text-decoration: none; }
    .cloud-btn:hover { background: #145FBB; }
    .cloud-btn:disabled { background: #94a3b8; cursor: not-allowed; }
    .cloud-alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 16px; }
    .cloud-alert.warn { background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B; }
    .cloud-alert.err  { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }
    .cloud-alert.ok   { background: #D1FAE5; color: #065F46; border-left: 4px solid #10B981; }
    .cloud-pin-input { width: 140px; text-align: center; font-size: 1.4rem; padding: 10px; border: 2px solid #d1d5db; border-radius: 8px; letter-spacing: 4px; }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 900px; margin: 24px auto;">
    <div class="cloud-hero">
        <h1><i class="fab fa-whatsapp"></i> Conectar WhatsApp Business — Meta Cloud API</h1>
        <p>Integração oficial da Meta. Sem risco de bloqueio, escala ilimitada, custo por conversa. Você conecta seu número Business em minutos.</p>
    </div>

    @if (!$configured)
        <div class="cloud-alert warn">
            <strong>Configuração pendente.</strong> As credenciais do Meta App (App ID e Config ID) ainda não foram cadastradas pelo administrador. Contate o suporte antes de prosseguir.
        </div>
    @endif

    <div class="cloud-card">
        <h3 style="margin: 0 0 20px;">Como funciona</h3>

        <div class="cloud-step">
            <div class="cloud-step-num">1</div>
            <div>
                <strong>Escolha um PIN de 6 dígitos</strong>
                <p>Você vai precisar dele pra recuperar a conta caso mude de aparelho. Guarde em local seguro.</p>
            </div>
        </div>

        <div class="cloud-step">
            <div class="cloud-step-num">2</div>
            <div>
                <strong>Clique em "Conectar com Facebook"</strong>
                <p>Você será redirecionado ao login da Meta pra autorizar o Vivensi como Provedor de Tecnologia.</p>
            </div>
        </div>

        <div class="cloud-step">
            <div class="cloud-step-num">3</div>
            <div>
                <strong>Escolha ou crie sua Conta Business + Número WhatsApp</strong>
                <p>A Meta guia você por todo o processo, incluindo verificação de propriedade do número.</p>
            </div>
        </div>

        <div class="cloud-step">
            <div class="cloud-step-num">4</div>
            <div>
                <strong>Pronto — o Vivensi ativa sua conta e sincroniza contatos automaticamente</strong>
                <p>Você pode começar a enviar mensagens imediatamente.</p>
            </div>
        </div>
    </div>

    <div class="cloud-card">
        <h3 style="margin: 0 0 16px;">Iniciar conexão</h3>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 6px;">PIN de segurança (6 dígitos)</label>
            <input id="pin-input" type="text" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000" class="cloud-pin-input" autocomplete="off">
            <div style="color: #6b7280; font-size: .9rem; margin-top: 6px;">Só números. Ex: <code>142857</code></div>
        </div>

        <button id="fb-signup-btn" class="cloud-btn" @if(!$configured) disabled @endif>
            <i class="fab fa-facebook"></i> Conectar com Facebook
        </button>

        <div id="signup-status" style="margin-top: 20px;"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Config injetado do backend ────────────────────────────────────────────
    const CLOUD_APP_ID    = @json($appId);
    const CLOUD_CONFIG_ID = @json($configId);
    const CLOUD_CSRF      = document.querySelector('meta[name="csrf-token"]').content;

    // ── Facebook JS SDK ──────────────────────────────────────────────────────
    window.fbAsyncInit = function () {
        FB.init({
            appId:   CLOUD_APP_ID,
            cookie:  true,
            xfbml:   false,
            version: 'v20.0',
        });
    };

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/pt_BR/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    // ── Escuta mensagens do Embedded Signup ──────────────────────────────────
    // A Meta manda uma window.postMessage com waba_id + phone_number_id
    let __wabaData = null;

    window.addEventListener('message', (event) => {
        if (!event.origin.endsWith('facebook.com')) return;
        try {
            const parsed = JSON.parse(event.data);
            if (parsed.type === 'WA_EMBEDDED_SIGNUP') {
                if (parsed.event === 'FINISH' && parsed.data) {
                    __wabaData = {
                        waba_id: parsed.data.waba_id,
                        phone_number_id: parsed.data.phone_number_id,
                    };
                }
            }
        } catch (e) { /* ignora payloads não-JSON */ }
    });

    // ── UI ───────────────────────────────────────────────────────────────────
    const btn    = document.getElementById('fb-signup-btn');
    const pinInp = document.getElementById('pin-input');
    const status = document.getElementById('signup-status');

    function alert(kind, msg) {
        status.innerHTML = `<div class="cloud-alert ${kind}">${msg}</div>`;
    }

    btn.addEventListener('click', function () {
        const pin = (pinInp.value || '').trim();
        if (!/^\d{6}$/.test(pin)) {
            alert('err', 'Informe um PIN de exatamente 6 dígitos numéricos.');
            pinInp.focus();
            return;
        }

        if (typeof FB === 'undefined') {
            alert('err', 'O SDK do Facebook ainda não carregou. Aguarde 3 segundos e tente de novo.');
            return;
        }

        __wabaData = null;
        alert('ok', '<i class="fas fa-spinner fa-spin"></i> Aguardando autorização no popup do Facebook...');

        FB.login(function (response) {
            if (!response.authResponse || !response.authResponse.code) {
                alert('warn', 'Autorização cancelada ou incompleta. Você pode tentar novamente.');
                return;
            }

            if (!__wabaData) {
                alert('err', 'Não recebemos os dados da sua conta WhatsApp Business. Tente novamente e conclua todos os passos do popup.');
                return;
            }

            const code = response.authResponse.code;
            alert('ok', '<i class="fas fa-spinner fa-spin"></i> Registrando seu número na Meta e ativando webhook...');

            fetch('{{ route("whatsapp.cloud.callback") }}', {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     CLOUD_CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                },
                body: JSON.stringify({
                    code:            code,
                    waba_id:         __wabaData.waba_id,
                    phone_number_id: __wabaData.phone_number_id,
                    pin:             pin,
                }),
            })
            .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
            .then(({ ok, body }) => {
                if (!ok) {
                    alert('err', 'Falha no cadastro: ' + (body.error || 'erro desconhecido'));
                    return;
                }
                alert('ok', '<i class="fas fa-check-circle"></i> Conta conectada com sucesso! Redirecionando...');
                setTimeout(() => window.location.href = body.redirect, 1500);
            })
            .catch(err => {
                alert('err', 'Erro de comunicação com o servidor: ' + err.message);
            });
        }, {
            config_id:                       CLOUD_CONFIG_ID,
            response_type:                   'code',
            override_default_response_type:  true,
            extras: { setup: {} },
        });
    });
</script>
@endpush
