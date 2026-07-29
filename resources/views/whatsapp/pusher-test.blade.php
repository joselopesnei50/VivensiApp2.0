@extends('layouts.app')
@section('title', 'Debug Pusher')

@section('content')
<div style="max-width: 900px; margin: 40px auto; padding: 0 20px;">

    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 1.6rem; font-weight: 800; color:#0f172a; margin:0 0 6px;">
            <i class="fas fa-bug"></i> Debug Pusher / Notificações em tempo real
        </h1>
        <p style="color:#64748b; margin:0; font-size:.92rem;">
            Diagnóstico ao vivo — não precisa abrir F12. Deixa essa tela aberta.
        </p>
    </div>

    {{-- Estado ao vivo --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-bottom:16px;">
        <h3 style="font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 14px;">Estado da conexão</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
            <div class="pt-stat">
                <div class="pt-label">Echo carregado</div>
                <div class="pt-value" id="pt-echo">…</div>
            </div>
            <div class="pt-stat">
                <div class="pt-label">Conexão Pusher</div>
                <div class="pt-value" id="pt-conn">…</div>
            </div>
            <div class="pt-stat">
                <div class="pt-label">Cluster</div>
                <div class="pt-value" id="pt-cluster">…</div>
            </div>
            <div class="pt-stat">
                <div class="pt-label">Tenant ID</div>
                <div class="pt-value" id="pt-tenant">{{ auth()->user()->tenant_id }}</div>
            </div>
        </div>
    </div>

    {{-- Canal + botão de teste --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-bottom:16px;">
        <h3 style="font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 14px;">Teste</h3>
        <div style="margin-bottom:12px; color:#475569; font-size:.9rem;">
            Canal escutado: <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#4f46e5;">private-tenant.{{ auth()->user()->tenant_id }}.whatsapp</code>
        </div>
        <div style="margin-bottom:14px; color:#475569; font-size:.9rem;">
            Evento escutado: <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#4f46e5;">.whatsapp.message.received</code>
        </div>
        <button id="pt-fire" onclick="ptFire()" style="background:#4f46e5; color:#fff; border:0; padding:12px 22px; border-radius:10px; font-weight:700; cursor:pointer;">
            <i class="fas fa-bolt"></i> Disparar evento de teste
        </button>
        <span style="margin-left:12px; color:#94a3b8; font-size:.85rem;">
            (Se estiver tudo OK, o log embaixo deve mostrar "EVENTO RECEBIDO" em segundos.)
        </span>
    </div>

    {{-- Log ao vivo --}}
    <div style="background:#0f172a; border-radius:14px; padding:16px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <h3 style="color:#e2e8f0; font-size:.95rem; font-weight:700; margin:0;">
                <i class="fas fa-terminal"></i> Log ao vivo
            </h3>
            <button onclick="document.getElementById('pt-log').innerHTML='';" style="background:#334155; color:#fff; border:0; padding:4px 10px; border-radius:6px; font-size:.75rem; cursor:pointer;">Limpar</button>
        </div>
        <pre id="pt-log" style="background:#020617; color:#94a3b8; padding:14px; border-radius:8px; margin:0; font-size:.82rem; line-height:1.55; max-height:340px; overflow-y:auto; white-space:pre-wrap;">Aguardando eventos...</pre>
    </div>

</div>

<style>
    .pt-stat { padding: 10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
    .pt-label { font-size:.68rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
    .pt-value { font-weight:700; color:#0f172a; font-size:.9rem; }
    .pt-value.ok { color:#10b981; }
    .pt-value.err { color:#ef4444; }
    .pt-value.warn { color:#f59e0b; }
</style>

<script>
    const ptLog = document.getElementById('pt-log');
    function ptWrite(label, obj) {
        const t = new Date().toISOString().substr(11, 12);
        const line = obj === undefined
            ? `[${t}] ${label}`
            : `[${t}] ${label}\n${JSON.stringify(obj, null, 2)}`;
        if (ptLog.textContent === 'Aguardando eventos...') ptLog.textContent = '';
        ptLog.textContent = ptLog.textContent + (ptLog.textContent ? '\n\n' : '') + line;
        ptLog.scrollTop = ptLog.scrollHeight;
    }

    // Estado do Echo
    function ptRefreshState() {
        const echoEl = document.getElementById('pt-echo');
        const connEl = document.getElementById('pt-conn');
        const clusterEl = document.getElementById('pt-cluster');

        if (typeof window.Echo === 'undefined') {
            echoEl.textContent = 'NÃO'; echoEl.className = 'pt-value err';
            connEl.textContent = '—';
            clusterEl.textContent = '—';
            return;
        }
        echoEl.textContent = 'SIM'; echoEl.className = 'pt-value ok';

        const pusher = window.Echo.connector.pusher;
        clusterEl.textContent = pusher.config.cluster || '(vazio)';

        const state = pusher.connection.state;
        connEl.textContent = state;
        connEl.className = 'pt-value ' + (state === 'connected' ? 'ok' : (state === 'failed' || state === 'unavailable' ? 'err' : 'warn'));
    }

    ptRefreshState();
    setInterval(ptRefreshState, 1500);

    // Log de mudanças de estado
    if (window.Echo) {
        const pusher = window.Echo.connector.pusher;
        pusher.connection.bind('state_change', (states) => {
            ptWrite('Estado da conexão: ' + states.previous + ' -> ' + states.current);
        });
        pusher.connection.bind('error', (err) => {
            ptWrite('!! Erro Pusher !!', err);
        });

        try {
            const channel = window.Echo.private(`tenant.{{ auth()->user()->tenant_id }}.whatsapp`);
            channel.listen('.whatsapp.message.received', (e) => {
                ptWrite('✅ EVENTO RECEBIDO — whatsapp.message.received', e);
            });
            channel.error((err) => {
                ptWrite('!! Erro no canal (auth falhou?) !!', err);
            });
            channel.subscribed(() => {
                ptWrite('✅ Inscrito no canal private-tenant.{{ auth()->user()->tenant_id }}.whatsapp');
            });
            ptWrite('Aguardando conexão + inscrição no canal...');
        } catch (err) {
            ptWrite('!! Falha ao subscribe !!', String(err));
        }
    } else {
        ptWrite('!! window.Echo não existe !!');
    }

    // Botão de teste — dispara evento do servidor
    async function ptFire() {
        const btn = document.getElementById('pt-fire');
        btn.disabled = true; btn.style.opacity = '.6';
        ptWrite('→ Chamando POST /whatsapp/pusher-test/fire ...');
        try {
            const res = await fetch('{{ route('whatsapp.pusher-test.fire') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });
            const j = await res.json();
            ptWrite('← resposta HTTP ' + res.status, j);
            ptWrite('Se cluster/canal OK, evento deve chegar em ~1s. Se não chegar, é auth do canal ou cluster errado.');
        } catch (e) {
            ptWrite('!! Falha ao chamar /fire !!', String(e));
        } finally {
            btn.disabled = false; btn.style.opacity = '1';
        }
    }
</script>
@endsection
