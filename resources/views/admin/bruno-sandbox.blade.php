@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · Sandbox</p>
            <h1 class="h3 mb-1">🤝 Sandbox do <span class="text-primary">Bruno</span></h1>
            <p class="text-muted mb-0">
                Teste o bot atendente/vendedor sem mexer em chats reais.
                Persona, KB e few-shot ficam em <code>config/bot-vendedor.php</code>.
            </p>
        </div>
        <button id="btn-clear" class="btn btn-outline-secondary btn-sm">
            🗑️ Limpar histórico
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="chat-log" class="p-3" style="height: 480px; overflow-y: auto; background: #f8f9fa;">
                <div class="text-center text-muted small py-5" id="empty-state">
                    Comece uma conversa. Sugestões:<br>
                    <button class="btn btn-sm btn-light mt-2 me-1 quick-msg">Oi, vi o anúncio. O que vocês fazem?</button>
                    <button class="btn btn-sm btn-light mt-2 me-1 quick-msg">Vi o Pro, achei caro</button>
                    <button class="btn btn-sm btn-light mt-2 me-1 quick-msg">Já uso RD CRM, por que mudaria?</button>
                    <button class="btn btn-sm btn-light mt-2 me-1 quick-msg">Quero falar com humano</button>
                </div>
            </div>

            <div class="border-top p-3 bg-white">
                <form id="form-msg" class="d-flex gap-2">
                    <input type="text" id="msg-input" class="form-control" placeholder="Digite como se fosse um lead..." autocomplete="off" required maxlength="2000">
                    <button type="submit" class="btn btn-primary" id="btn-send">Enviar</button>
                </form>
                <small class="text-muted">
                    💡 Histórico é mantido em Redis por 1h (chave isolada por seu user_id, tenant=0).
                </small>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    const log       = document.getElementById('chat-log');
    const empty     = document.getElementById('empty-state');
    const form      = document.getElementById('form-msg');
    const input     = document.getElementById('msg-input');
    const btnSend   = document.getElementById('btn-send');
    const btnClear  = document.getElementById('btn-clear');
    const csrfToken = '{{ csrf_token() }}';

    function bubble(role, text, meta) {
        if (empty) empty.remove();

        const wrap = document.createElement('div');
        wrap.className = 'mb-3 ' + (role === 'user' ? 'text-end' : 'text-start');

        const inner = document.createElement('div');
        inner.style.display      = 'inline-block';
        inner.style.maxWidth     = '75%';
        inner.style.padding      = '10px 14px';
        inner.style.borderRadius = '14px';
        inner.style.background   = role === 'user' ? '#0d6efd' : '#fff';
        inner.style.color        = role === 'user' ? '#fff'    : '#212529';
        inner.style.border       = role === 'user' ? 'none'    : '1px solid #dee2e6';
        inner.style.whiteSpace   = 'pre-wrap';
        inner.textContent        = text;
        wrap.appendChild(inner);

        if (meta) {
            const m = document.createElement('div');
            m.className = 'small text-muted mt-1';
            m.textContent = meta;
            wrap.appendChild(m);
        }

        log.appendChild(wrap);
        log.scrollTop = log.scrollHeight;
    }

    async function send(text) {
        bubble('user', text);
        input.value = '';
        btnSend.disabled = true;
        btnSend.textContent = '...';

        try {
            const r = await fetch('{{ route('admin.bruno.chat') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: text }),
            });
            const d = await r.json();
            if (r.ok && d.success) {
                bubble('bot', d.reply, `tokens: ${d.tokens}`);
            } else {
                bubble('bot', '⚠️ ' + (d.error || 'Falha ao responder.'), '');
            }
        } catch (e) {
            bubble('bot', '⚠️ Falha de comunicação: ' + e.message, '');
        } finally {
            btnSend.disabled = false;
            btnSend.textContent = 'Enviar';
            input.focus();
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (text) send(text);
    });

    document.querySelectorAll('.quick-msg').forEach((b) => {
        b.addEventListener('click', () => send(b.textContent));
    });

    btnClear.addEventListener('click', async () => {
        if (!confirm('Limpar histórico desta sessão?')) return;
        await fetch('{{ route('admin.bruno.clear') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        log.innerHTML = '<div class="text-center text-muted small py-5" id="empty-state">Histórico limpo. Recomece a conversa.</div>';
    });
})();
</script>
@endsection
