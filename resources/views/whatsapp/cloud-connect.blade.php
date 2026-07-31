@extends('layouts.app')

@section('title', 'Conectar WhatsApp')

@php
    // Debug console só pra super admin — cliente comum não precisa ver
    // stacktrace de JS. Se algo der errado, admin acessa a mesma URL e
    // consegue diagnosticar.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
@endphp

@push('styles')
<style>
    .wac-page { max-width: 720px; margin: 32px auto; padding: 0 20px; }

    /* Hero simples, sem jargão */
    .wac-hero {
        text-align: center;
        padding: 40px 24px 32px;
    }
    .wac-hero .wac-icon {
        width: 84px; height: 84px; margin: 0 auto 18px;
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        border-radius: 24px; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.6rem;
        box-shadow: 0 12px 32px rgba(37, 211, 102, .35);
    }
    .wac-hero h1 {
        font-size: 1.9rem; font-weight: 800; color: #0f172a;
        margin: 0 0 10px; line-height: 1.2;
    }
    .wac-hero p {
        font-size: 1.05rem; color: #475569; line-height: 1.55;
        margin: 0 auto; max-width: 480px;
    }

    /* Card central com o CTA */
    .wac-card {
        background: #fff; border-radius: 20px;
        padding: 32px 28px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 24px rgba(15,23,42,.05);
        margin-bottom: 20px;
    }

    /* Passos visuais horizontais (números discretos) */
    .wac-steps {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 24px;
    }
    @media (max-width: 620px) {
        .wac-steps { grid-template-columns: 1fr; }
    }
    .wac-step {
        text-align: center;
        padding: 14px 8px;
    }
    .wac-step-icon {
        width: 44px; height: 44px; margin: 0 auto 8px;
        background: #f1f5f9;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #128C7E; font-size: 1.15rem;
    }
    .wac-step-txt {
        font-size: .88rem; color: #334155; line-height: 1.4; font-weight: 500;
    }
    .wac-step-txt strong { display: block; color: #0f172a; margin-bottom: 2px; }

    /* Botão principal — grande, verde WhatsApp */
    .wac-btn-primary {
        display: flex; align-items: center; justify-content: center; gap: 12px;
        width: 100%;
        padding: 18px 24px;
        background: #25D366; color: #fff !important;
        border: 0; border-radius: 14px;
        font-size: 1.1rem; font-weight: 800;
        cursor: pointer;
        text-decoration: none;
        transition: all .15s;
        box-shadow: 0 6px 20px rgba(37, 211, 102, .35);
    }
    .wac-btn-primary:hover { background: #1EBE5B; transform: translateY(-1px); }
    .wac-btn-primary:disabled {
        background: #94a3b8; cursor: not-allowed; transform: none;
        box-shadow: none;
    }

    .wac-secure {
        text-align: center; margin-top: 14px;
        color: #64748b; font-size: .82rem;
    }
    .wac-secure i { color: #10b981; margin-right: 4px; }

    /* PIN — só mostra opcional, sob "avançado" */
    .wac-advanced {
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px dashed #e2e8f0;
    }
    .wac-advanced-toggle {
        color: #475569; font-size: .85rem; font-weight: 600;
        background: none; border: 0; padding: 0; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .wac-advanced-body { display: none; margin-top: 14px; }
    .wac-advanced-body.open { display: block; }
    .wac-pin-input {
        width: 140px; padding: 10px 12px;
        border: 1.5px solid #cbd5e1; border-radius: 10px;
        text-align: center; font-size: 1.1rem; letter-spacing: 4px;
    }

    /* Alerts */
    .wac-alert {
        padding: 14px 18px; border-radius: 12px; margin-bottom: 16px;
        font-size: .93rem; line-height: 1.5;
    }
    .wac-alert.warn { background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B; }
    .wac-alert.err  { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }
    .wac-alert.ok   { background: #D1FAE5; color: #065F46; border-left: 4px solid #10B981; }

    /* Rodapé "precisa de ajuda?" — discreto */
    .wac-help {
        text-align: center; margin-top: 22px;
        color: #64748b; font-size: .86rem;
    }
    .wac-help a {
        color: #128C7E; font-weight: 600; text-decoration: none;
    }
    .wac-help a:hover { text-decoration: underline; }

    /* Debug — SÓ pra super admin */
    .wac-debug {
        background:#0f172a; color:#cbd5e1;
        border-radius: 12px; padding: 18px;
        font-family: 'Courier New', monospace;
        font-size: .8rem; margin-top: 24px;
    }
    .wac-debug pre {
        background:#020617; color:#94a3b8; padding:12px;
        border-radius:8px; max-height:260px; overflow-y:auto;
        margin:8px 0 0; white-space:pre-wrap;
    }
</style>
@endpush

@section('content')
<div class="wac-page">

    {{-- ─── Hero ─────────────────────────────────────────────────────── --}}
    <div class="wac-hero">
        <div class="wac-icon"><i class="fab fa-whatsapp"></i></div>
        <h1>Conecte seu WhatsApp em segundos</h1>
        <p>
            Use a linha oficial da Meta pra atender seus contatos direto pelo Vivensi.
            Sem app extra, sem risco de bloqueio.
        </p>
    </div>

    @if (!$configured)
        <div class="wac-alert warn">
            <strong>Ainda não está pronto.</strong>
            O suporte Vivensi está finalizando a configuração da integração.
            Volte em breve ou fale com a gente.
        </div>
    @endif

    {{-- ─── Card principal com o CTA ────────────────────────────────── --}}
    <div class="wac-card">

        <div class="wac-steps">
            <div class="wac-step">
                <div class="wac-step-icon"><i class="fas fa-mouse-pointer"></i></div>
                <div class="wac-step-txt"><strong>1. Clique</strong>no botão abaixo</div>
            </div>
            <div class="wac-step">
                <div class="wac-step-icon"><i class="fab fa-facebook"></i></div>
                <div class="wac-step-txt"><strong>2. Entre</strong>com seu Facebook</div>
            </div>
            <div class="wac-step">
                <div class="wac-step-icon"><i class="fas fa-check-circle"></i></div>
                <div class="wac-step-txt"><strong>3. Pronto!</strong>Já pode enviar mensagens</div>
            </div>
        </div>

        <button id="fb-signup-btn" class="wac-btn-primary" @if(!$configured) disabled @endif>
            <i class="fab fa-facebook" style="font-size:1.4rem;"></i>
            Conectar com o Facebook
        </button>

        <div class="wac-secure">
            <i class="fas fa-shield-alt"></i>
            Conexão segura e criptografada. Você pode desconectar quando quiser.
        </div>

        <div id="signup-status" style="margin-top: 18px;"></div>

        {{-- Configuração avançada — colapsada, opcional --}}
        <div class="wac-advanced">
            <button type="button" class="wac-advanced-toggle" onclick="wacToggleAdvanced()">
                <i class="fas fa-chevron-right" id="wac-adv-chev"></i>
                <span>Configuração avançada</span>
            </button>
            <div class="wac-advanced-body" id="wac-adv-body">
                <label style="display:block; font-weight:600; margin-bottom:6px; color:#475569;">
                    PIN de recuperação de 6 dígitos
                </label>
                <input id="pin-input" type="text" maxlength="6" pattern="\d{6}"
                       inputmode="numeric" placeholder="000000"
                       class="wac-pin-input" autocomplete="off"
                       value="{{ substr(str_replace(['-', '.'], '', md5(auth()->id() . '-vivensi-pin')), 0, 6) }}">
                <div style="color: #64748b; font-size: .82rem; margin-top: 6px;">
                    Necessário só se você ainda não verificou o número.
                    Preenchemos com um valor aleatório — <strong>anote se precisar recuperar depois</strong>.
                </div>
            </div>
        </div>
    </div>

    {{-- Rodapé ajuda + link fallback discreto --}}
    <div class="wac-help">
        <div>Deu problema? <a href="https://wa.me/5516997618695?text=Olá, preciso de ajuda para conectar meu WhatsApp na Vivensi." target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> Falar com o suporte</a></div>
        <div style="margin-top: 6px;">
            <a href="{{ route('whatsapp.cloud.manual.show') }}" style="color: #94a3b8; font-size: .78rem;">
                Sou avançado, quero colar credenciais manualmente
            </a>
        </div>
    </div>

    {{-- Debug console — SÓ super admin --}}
    @if($isSuperAdmin)
        <div class="wac-debug">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:#e2e8f0;"><i class="fas fa-bug"></i> Console de debug (super admin)</strong>
                <button id="clear-debug" style="background:#334155; color:#fff; border:0; padding:4px 10px; border-radius:6px; cursor:pointer; font-size:.75rem;">Limpar</button>
            </div>
            <pre id="debug-log">[aguardando eventos...]</pre>
        </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
    // ── Config injetado do backend ────────────────────────────────────────────
    const CLOUD_APP_ID    = @json($appId);
    const CLOUD_CONFIG_ID = @json($configId);
    const CLOUD_CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const IS_SUPER_ADMIN  = @json($isSuperAdmin);

    // ── Debug logger — só faz console.log se não for super admin (evita
    // erro quando debugBox não existe no DOM pro user comum) ─────────────
    const debugBox = document.getElementById('debug-log'); // pode ser null
    function dbg(label, payload) {
        if (IS_SUPER_ADMIN && debugBox) {
            const t = new Date().toISOString().substr(11, 12);
            const line = payload === undefined
                ? `[${t}] ${label}`
                : `[${t}] ${label}\n${JSON.stringify(payload, null, 2)}`;
            debugBox.textContent = debugBox.textContent === '[aguardando eventos...]'
                ? line
                : debugBox.textContent + '\n\n' + line;
            debugBox.scrollTop = debugBox.scrollHeight;
        }
        // Em qualquer caso, também loga no console do browser (F12 disponível pra dev)
        console.log('[VivensiCloud]', label, payload);
    }
    const clearBtn = document.getElementById('clear-debug');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            debugBox.textContent = '[aguardando eventos...]';
        });
    }

    dbg('Config injetado do backend:', {
        appId: CLOUD_APP_ID,
        configId: CLOUD_CONFIG_ID,
        appIdLen: (CLOUD_APP_ID || '').length,
        configIdLen: (CLOUD_CONFIG_ID || '').length,
    });

    // Toggle da seção "Configuração avançada"
    function wacToggleAdvanced() {
        const body = document.getElementById('wac-adv-body');
        const chev = document.getElementById('wac-adv-chev');
        const isOpen = body.classList.toggle('open');
        chev.className = isOpen ? 'fas fa-chevron-down' : 'fas fa-chevron-right';
    }

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
                    dbg('WA_EMBEDDED_SIGNUP CANCEL — usuário abandonou', {
                        current_step: parsed.data && parsed.data.current_step,
                    });
                } else if (parsed.event === 'ERROR') {
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
        status.innerHTML = `<div class="wac-alert ${kind}">${msg}</div>`;
    }

    btn.addEventListener('click', function () {
        const pin = (pinInp.value || '').trim();
        if (!/^\d{6}$/.test(pin)) {
            alert('err', 'O PIN de recuperação precisa ter 6 dígitos numéricos. Verifique em Configuração avançada.');
            document.getElementById('wac-adv-body').classList.add('open');
            document.getElementById('wac-adv-chev').className = 'fas fa-chevron-down';
            pinInp.focus();
            return;
        }

        if (typeof FB === 'undefined') {
            alert('err', 'Ainda estamos carregando. Aguarde 3 segundos e tente novamente.');
            dbg('ERRO: FB é undefined no click');
            return;
        }

        __wabaData = null;
        alert('ok', '<i class="fas fa-spinner fa-spin"></i> Aguardando você autorizar no Facebook...');

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
                alert('err', 'A janela do Facebook foi bloqueada pelo navegador. Libere pop-ups pra vivensi.app.br e tente de novo.');
                return;
            }

            if (!response.authResponse) {
                alert('err', 'Você fechou a janela sem concluir. Tente de novo — leva menos de 1 minuto.');
                return;
            }

            if (!response.authResponse.code) {
                alert('err', 'Faltou um passo na autorização. Tente novamente e conclua até o final.');
                return;
            }

            if (!__wabaData) {
                alert('err', 'Não conseguimos identificar sua conta WhatsApp. Fale com o suporte pra ajudar você.');
                return;
            }

            const code = response.authResponse.code;
            alert('ok', '<i class="fas fa-spinner fa-spin"></i> Ativando sua conta WhatsApp...');
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
                    alert('err', 'Não foi possível conectar: ' + (body.error || 'erro desconhecido. Fale com o suporte.'));
                    return;
                }
                alert('ok', '<i class="fas fa-check-circle"></i> Pronto! Já pode começar a enviar mensagens.');
                setTimeout(() => window.location.href = body.redirect, 1500);
            })
            .catch(err => {
                dbg('ERRO fetch backend', String(err));
                alert('err', 'Erro de conexão. Verifique sua internet e tente de novo.');
            });
        }, fbLoginOptions);
    });
</script>
@endpush
