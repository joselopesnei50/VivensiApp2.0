@extends('layouts.app')
@section('title', 'Chat do site · ' . $c->displayName())

@section('content')
@php $lastId = (int) $c->messages->max('id'); @endphp

<div class="d-flex align-items-start gap-3 mb-3 flex-wrap">
    <div class="flex-grow-1" style="min-width:0;">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            <a href="{{ route('admin.web_chat.index') }}" style="color:#94a3b8;text-decoration:none;">Admin / Chat do site</a>
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.45rem;color:#0f172a;line-height:1.2;">{{ $c->displayName() }}</h2>
        <p class="mb-0 mt-1" style="font-size:.85rem;color:#64748b;">Situação: <strong id="wc-status">{{ $c->statusLabel() }}</strong></p>
    </div>
    <div class="d-flex gap-2 flex-wrap flex-shrink-0">
        @if(in_array($c->status, ['bot', 'waiting_human']))
            <form method="POST" action="{{ route('admin.web_chat.assume', $c) }}">@csrf
                <button class="btn btn-primary fw-bold rounded-3"><i class="fas fa-user-check me-1"></i> Assumir conversa</button>
            </form>
        @endif
        @if(in_array($c->status, ['human', 'waiting_human']))
            <form method="POST" action="{{ route('admin.web_chat.release', $c) }}">@csrf
                <button class="btn btn-outline-secondary fw-bold rounded-3"><i class="fas fa-robot me-1"></i> Devolver ao Bruno</button>
            </form>
        @endif
        @if($c->status !== 'closed')
            <form method="POST" action="{{ route('admin.web_chat.close', $c) }}">@csrf
                <button class="btn btn-outline-danger fw-bold rounded-3">Encerrar</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:10px 14px;border-radius:12px;margin-bottom:14px;font-weight:700;font-size:.86rem;">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column" style="height:calc(100vh - 260px);min-height:460px;">
            <div id="wc-thread" style="flex:1;overflow-y:auto;padding:18px;background:#f8fafc;">
                <ol id="wc-list" class="wc-list">
                    @foreach($c->messages as $m)
                        @include('admin.web_chat._message', ['m' => $m])
                    @endforeach
                </ol>
            </div>
            <form id="wc-reply" method="POST" action="{{ route('admin.web_chat.reply', $c) }}" class="d-flex gap-2 p-3" style="border-top:1px solid #e2e8f0;background:#fff;">
                @csrf
                <textarea name="body" id="wc-body" rows="2" class="form-control" maxlength="2000" required
                          placeholder="{{ $c->status === 'bot' ? 'Responder como equipe (o Bruno para de responder)…' : 'Responder ao visitante…' }}"></textarea>
                <button class="btn btn-primary fw-bold rounded-3 px-4" id="wc-send">Enviar</button>
            </form>
        </div>
        <p style="font-size:.74rem;color:#94a3b8;margin-top:8px;">Enter envia · Shift+Enter quebra linha. A resposta aparece no chat do visitante em poucos segundos.</p>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
            <h6 class="fw-800 mb-3" style="color:#0f172a;">Visitante</h6>
            <dl class="wc-dl">
                <dt>Nome</dt><dd>{{ $c->visitor_name ?: '—' }}</dd>
                <dt>E-mail</dt><dd>@if($c->visitor_email)<a href="mailto:{{ $c->visitor_email }}">{{ $c->visitor_email }}</a>@else — @endif</dd>
                <dt>Telefone</dt><dd>{{ $c->visitor_phone ?: '—' }}</dd>
                <dt>Página</dt><dd>@if($c->page_url)<a href="{{ $c->page_url }}" target="_blank" rel="noopener">{{ $c->page_title ?: $c->page_url }}</a>@else — @endif</dd>
                <dt>Origem</dt><dd>{{ \App\Support\TrafficClassifier::sourceLabel($c->source) }}</dd>
                <dt>Dispositivo</dt><dd>{{ \App\Support\TrafficClassifier::deviceLabel($c->device) }}</dd>
                <dt>Início</dt><dd>{{ $c->created_at->format('d/m/Y H:i') }}</dd>
                <dt>Respostas do Bruno</dt><dd>{{ $c->ai_replies }}</dd>
                @if($c->assignedUser)<dt>Atendente</dt><dd>{{ $c->assignedUser->name }}</dd>@endif
            </dl>
        </div>
        @if($c->booking)
            <div class="card border-0 shadow-sm rounded-4 p-3" style="background:#ecfdf5;">
                <h6 class="fw-800 mb-1" style="color:#065f46;">📅 Reunião marcada</h6>
                <p class="mb-2" style="font-size:.88rem;color:#065f46;">
                    {{ \Carbon\Carbon::parse($c->booking->meeting_date)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM') }} às {{ $c->booking->meeting_time }}
                </p>
                <a href="{{ url('/admin/bookings') }}" style="font-size:.8rem;font-weight:700;">Ver na agenda</a>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.wc-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;}
.wc-msg{max-width:78%;display:flex;flex-direction:column;}
.wc-bubble{padding:10px 13px;border-radius:14px;font-size:.9rem;line-height:1.5;overflow-wrap:anywhere;}
.wc-meta{font-size:.7rem;color:#94a3b8;margin:3px 6px 0;}
.wc-visitor{align-self:flex-start;} .wc-visitor .wc-bubble{background:#fff;border:1px solid #e2e8f0;border-bottom-left-radius:4px;}
.wc-bot{align-self:flex-end;align-items:flex-end;} .wc-bot .wc-bubble{background:#eef2ff;color:#1e1b4b;border-bottom-right-radius:4px;}
.wc-agent{align-self:flex-end;align-items:flex-end;} .wc-agent .wc-bubble{background:#4f46e5;color:#fff;border-bottom-right-radius:4px;}
.wc-system{align-self:center;max-width:90%;text-align:center;font-size:.78rem;color:#475569;background:#f1f5f9;border-radius:10px;padding:6px 12px;}
.wc-system span{color:#94a3b8;margin-left:4px;}
li.wc-system-wrap{align-self:center;}
.wc-msg.wc-system{align-self:center;max-width:90%;}
.wc-dl{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;margin:0;font-size:.84rem;}
.wc-dl dt{color:#94a3b8;font-weight:600;} .wc-dl dd{margin:0;color:#0f172a;overflow-wrap:anywhere;}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var thread = document.getElementById('wc-thread'), list = document.getElementById('wc-list'),
        form = document.getElementById('wc-reply'), body = document.getElementById('wc-body'),
        statusEl = document.getElementById('wc-status'), lastId = {{ $lastId }}, busy = false;
    var pollUrl = @json(route('admin.web_chat.poll', $c));
    function down() { thread.scrollTop = thread.scrollHeight; }
    down();

    function poll() {
        fetch(pollUrl + '?depois=' + lastId, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (!j) return;
                var atBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
                j.messages.forEach(function (m) {
                    if (m.id <= lastId || list.querySelector('[data-id="' + m.id + '"]')) return;
                    list.insertAdjacentHTML('beforeend', m.html);
                    lastId = Math.max(lastId, m.id);
                });
                statusEl.textContent = j.status_label;
                if (atBottom) down();
            })
            .catch(function () {})
            .finally(function () { setTimeout(poll, document.hidden ? 15000 : 4000); });
    }
    setTimeout(poll, 4000);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = body.value.trim();
        if (!text || busy) return;
        busy = true; body.classList.remove('is-invalid');
        var fd = new FormData(form);
        fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(); body.value = ''; return fetch(pollUrl + '?depois=' + lastId, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' }); })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                j.messages.forEach(function (m) {
                    if (list.querySelector('[data-id="' + m.id + '"]')) return;
                    list.insertAdjacentHTML('beforeend', m.html); lastId = Math.max(lastId, m.id);
                });
                statusEl.textContent = j.status_label; down();
            })
            .catch(function () { body.classList.add('is-invalid'); body.placeholder = 'Não foi possível enviar. Tente de novo.'; })
            .finally(function () { busy = false; body.focus(); });
    });
    body.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit')); }
    });
})();
</script>
@endpush
@endsection
