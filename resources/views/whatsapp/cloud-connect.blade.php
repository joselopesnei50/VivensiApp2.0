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

    <div class="cloud-card" style="background:#0f172a; color:#cbd5e1; font-family: monospace; font-size: .85rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <strong style="color:#e2e8f0;"><i class="fas fa-bug"></i> Console de debug ao vivo</strong>
            <button id="clear-debug" style="background:#334155; color:#fff; border:0; padding:4px 10px; border-radius:6px; cursor:pointer; font-size:.75rem;">Limpar</button>
        </div>
        <pre id="debug-log" style="background:#020617; color:#94a3b8; padding:12px; border-radius:8px; max-height:300px; overflow-y:auto; margin:0; white-space:pre-wrap;">[aguardando eventos...]</pre>
        <div style="color:#64748b; font-size:.75rem; margin-top:8px;">Se algo der errado, tire print dessa caixa e me mande.</div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Config injetado do backend ────────────────────────────────────────────
    const CLOUD_APP_ID    = @json($appId);
    const CLOUD_CONFIG_ID = @json($configId);
    const CLOUD_CSRF      = document.querySelector('meta[name="csrf-token"]').content;

    // ── Debug logger inline (não depende de F12) ─────────────────────────────
    const debugBox = document.getElementById('debug-log');
    function dbg(label, payload) {
        const t = new Date().toISOString().substr(11, 12);
        const line = payload === undefined
            ? `[${t}] ${label}`
            : `[${t}] ${label}\n${JSON.stringify(payload, null, 2)}`;
        debugBox.textContent = debugBox.textContent === '[aguardando eventos...]'
            ? line
            : debugBox.textContent + '\n\n' + line;
        debugBox.scrollTop = debugBox.scrollHeight;
        console.log('[VivensiCloud]', label, payload);
    }
    document.getElementById('clear-debug').addEventListener('click', () => {
        debugBox.textContent = '[aguardando eventos...]';
    });

    dbg('Config injetado do backend:', {
        appId: CLOUD_APP_ID,
        configId: CLOUD_CONFIG_ID,
        appIdLen: (CLOUD_APP_ID || '').length,
        configIdLen: (CLOUD_CONFIG_ID || '').length,
    });

    // ── Facebook JS SDK ──────────────────────────────────────────────────────
    window.fbAsyncInit = function () {
        dbg('fbAsyncInit: chamando FB.init');
        FB.init({
            appId:   CLOUD_APP_ID,
            cookie:  true,
            xfbml:   false,
            version: 'v22.0',
        });
        dbg('FB.init concluído. FB.getVersion?', typeof FB.getVersion === 'function' ? FB.getVersion() : 'n/a');
    };

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        js.async = true; js.defer = true; js.crossOrigin = "anonymous";
        js.onload  = () => dbg('SDK script carregado');
        js.onerror = (e) => dbg('SDK script FALHOU ao carregar', String(e));
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    // ── Escuta mensagens do Embedded Signup ──────────────────────────────────
    let __wabaData = null;

    window.addEventListener('message', (event) => {
        // Origem estrita — antes usava endsWith('facebook.com'), o que aceitaria
        // subdomínios não confiáveis. O Embedded Signup sempre vem de www.facebook.com.
        if (event.origin !== 'https://www.facebook.com') return;
        dbg('postMessage recebido de facebook.com', event.data);
        try {
            const parsed = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            if (parsed && parsed.type === 'WA_EMBEDDED_SIGNUP') {
                dbg('WA_EMBEDDED_SIGNUP event', parsed);
                if (parsed.event === 'FINISH' && parsed.data) {
                    __wabaData = {
                        waba_id: parsed.data.waba_id,
                        phone_number_id: parsed.data.phone_number_id,
                    };
                    dbg('WABA data capturada', __wabaData);
                } else if (parsed.event === 'CANCEL') {
                    // Usuário fechou o modal — mostrar em qual etapa desistiu ajuda no suporte.
                    dbg('WA_EMBEDDED_SIGNUP CANCEL — usuário abandonou', {
                        current_step: parsed.data && parsed.data.current_step,
                    });
                } else if (parsed.event === 'ERROR') {
                    // Meta reportou erro interno do fluxo (número inválido, WABA já cadastrada, etc).
                    dbg('WA_EMBEDDED_SIGNUP ERROR', {
                        error_message: parsed.data && parsed.data.error_message,
                    });
                }
            }
        } catch (e) {
            dbg('postMessage não parseou como JSON — ignorando');
        }
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
            dbg('ERRO: FB é undefined no click');
            return;
        }

        __wabaData = null;
        alert('ok', '<i class="fas fa-spinner fa-spin"></i> Aguardando autorização no popup do Facebook...');

        const fbLoginOptions = {
            config_id:                       CLOUD_CONFIG_ID,
            response_type:                   'code',
            override_default_response_type:  true,
            // sessionInfoVersion:'3' é obrigatório pra Meta enviar waba_id e
            // phone_number_id via postMessage no formato esperado. Sem isso,
            // FINISH chega vazio e __wabaData permanece null.
            extras: { setup: {}, sessionInfoVersion: '3' },
        };
        dbg('Chamando FB.login com options', fbLoginOptions);

        FB.login(function (response) {
            dbg('FB.login CALLBACK — resposta COMPLETA', response);

            if (!response) {
                alert('err', 'FB.login retornou undefined. Provavelmente o popup foi bloqueado. Libera pop-ups pra vivensi.app.br e tenta de novo.');
                return;
            }

            if (!response.authResponse) {
                alert('err', 'Sem authResponse. Status: ' + (response.status || 'desconhecido') + '. Cola o log de debug abaixo pra suporte.');
                return;
            }

            if (!response.authResponse.code) {
                alert('err', 'authResponse sem code. Cola o log de debug.');
                return;
            }

            if (!__wabaData) {
                alert('err', 'code obtido mas não recebemos WABA data do postMessage. Cola o log.');
                return;
            }

            const code = response.authResponse.code;
            alert('ok', '<i class="fas fa-spinner fa-spin"></i> Registrando seu número na Meta e ativando webhook...');
            dbg('Enviando pro backend', { code_preview: code.substring(0, 20) + '...', waba: __wabaData });

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
            .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, body: j })))
            .then(({ ok, status: httpStatus, body }) => {
                dbg('Resposta backend', { httpStatus, body });
                if (!ok) {
                    alert('err', 'Falha no cadastro: ' + (body.error || 'erro desconhecido'));
                    return;
                }
                alert('ok', '<i class="fas fa-check-circle"></i> Conta conectada com sucesso! Redirecionando...');
                setTimeout(() => window.location.href = body.redirect, 1500);
            })
            .catch(err => {
                dbg('ERRO fetch backend', String(err));
                alert('err', 'Erro de comunicação com o servidor: ' + err.message);
            });
        }, fbLoginOptions);
    });
</script>
@endpush
