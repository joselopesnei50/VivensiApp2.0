@extends('layouts.app')

@section('title', 'Templates WhatsApp Cloud API')

@push('styles')
<style>
    .tpl-hero { background: linear-gradient(135deg, #075E54 0%, #128C7E 100%); color: #fff; padding: 28px; border-radius: 14px; margin-bottom: 20px; }
    .tpl-hero h1 { margin: 0 0 6px; font-size: 1.5rem; }
    .tpl-hero p { margin: 0; opacity: .95; }
    .tpl-card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 12px; border-left: 4px solid #94a3b8; }
    .tpl-card.approved { border-left-color: #10b981; }
    .tpl-card.pending  { border-left-color: #f59e0b; }
    .tpl-card.rejected { border-left-color: #ef4444; }
    .tpl-card.draft    { border-left-color: #6366f1; }
    .tpl-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 10px; }
    .tpl-title { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a; }
    .tpl-meta { color: #6b7280; font-size: .8rem; margin-top: 4px; }
    .tpl-body { background: #f8fafc; padding: 10px 12px; border-radius: 6px; color: #374151; font-size: .9rem; margin: 8px 0; white-space: pre-wrap; }
    .tpl-status { padding: 3px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
    .tpl-status.approved { background: #d1fae5; color: #065f46; }
    .tpl-status.pending  { background: #fef3c7; color: #92400e; }
    .tpl-status.rejected { background: #fee2e2; color: #991b1b; }
    .tpl-status.draft    { background: #e0e7ff; color: #3730a3; }
    .tpl-rejection { color: #991b1b; font-size: .8rem; margin-top: 6px; }
    .tpl-actions { display: flex; gap: 6px; }
    .tpl-btn { padding: 6px 12px; border-radius: 6px; font-size: .8rem; border: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .tpl-btn-primary { background: #10b981; color: #fff; }
    .tpl-btn-secondary { background: #e5e7eb; color: #374151; }
    .tpl-btn-danger { background: #fee2e2; color: #991b1b; }
    .tpl-btn-test { background: #dbeafe; color: #1e40af; }
    .tpl-empty { background: #fff; padding: 40px; text-align: center; border-radius: 12px; color: #6b7280; }
    .tpl-modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }
    .tpl-modal-backdrop.open { display: flex; align-items: center; justify-content: center; }
    .tpl-modal { background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 92%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
    .tpl-modal h3 { margin: 0 0 6px; color: #0f172a; }
    .tpl-modal p.subtitle { margin: 0 0 16px; color: #6b7280; font-size: .9rem; }
    .tpl-modal input { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: .95rem; margin-bottom: 12px; }
    .tpl-modal label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: 4px; color: #374151; }
    .tpl-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
    .tpl-modal-result { padding: 12px; border-radius: 8px; margin-top: 10px; font-size: .9rem; display: none; }
    .tpl-modal-result.ok { background: #d1fae5; color: #065f46; display: block; }
    .tpl-modal-result.err { background: #fee2e2; color: #991b1b; display: block; }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 900px; margin: 24px auto;">
    <div class="tpl-hero">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fab fa-whatsapp"></i> Templates de Mensagem</h1>
                <p>WhatsApp Cloud API — mensagens pré-aprovadas para iniciar conversas fora da janela de 24h</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('whatsapp.templates.cloud.create') }}" class="tpl-btn tpl-btn-primary">
                    <i class="fas fa-plus"></i> Novo template
                </a>
                @if ($instances->isNotEmpty())
                    <form method="POST" action="{{ route('whatsapp.templates.cloud.sync') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="whatsapp_instance_id" value="{{ $instances->first()->id }}">
                        <button type="submit" class="tpl-btn tpl-btn-secondary">
                            <i class="fas fa-sync"></i> Sincronizar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    @if ($instances->isEmpty())
        <div class="tpl-empty">
            <i class="fas fa-plug" style="font-size: 2rem; color: #94a3b8; margin-bottom: 12px;"></i>
            <h3 style="margin: 0 0 8px;">Nenhuma instância Cloud API conectada</h3>
            <p style="margin: 0 0 16px;">Conecte uma conta WhatsApp Business Cloud API primeiro pra criar templates.</p>
            <a href="{{ route('whatsapp.cloud.connect') }}" class="tpl-btn tpl-btn-primary">
                <i class="fas fa-link"></i> Conectar WhatsApp Business
            </a>
        </div>
    @elseif ($templates->isEmpty())
        <div class="tpl-empty">
            <i class="fas fa-envelope-open-text" style="font-size: 2rem; color: #94a3b8; margin-bottom: 12px;"></i>
            <h3 style="margin: 0 0 8px;">Nenhum template ainda</h3>
            <p style="margin: 0 0 16px;">Crie seu primeiro template ou sincronize os que já existem na Meta.</p>
            <a href="{{ route('whatsapp.templates.cloud.create') }}" class="tpl-btn tpl-btn-primary">
                <i class="fas fa-plus"></i> Criar template
            </a>
        </div>
    @else
        @foreach ($templates as $tpl)
            @php
                $statusClass = strtolower($tpl->status);
                $statusLabel = match($tpl->status) {
                    'APPROVED' => 'Aprovado',
                    'PENDING'  => 'Em análise',
                    'REJECTED' => 'Rejeitado',
                    'PAUSED'   => 'Pausado',
                    'DISABLED' => 'Desativado',
                    default    => 'Rascunho local',
                };
                $cardStatusClass = match($tpl->status) {
                    'APPROVED' => 'approved',
                    'PENDING'  => 'pending',
                    'REJECTED' => 'rejected',
                    default    => 'draft',
                };
            @endphp
            <div class="tpl-card {{ $cardStatusClass }}">
                <div class="tpl-header">
                    <div style="flex: 1;">
                        <h3 class="tpl-title">{{ $tpl->name }}</h3>
                        <div class="tpl-meta">
                            {{ $tpl->language }} · {{ $tpl->category }}
                            @if ($tpl->synced_at)
                                · Sincronizado {{ $tpl->synced_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                    <span class="tpl-status {{ $cardStatusClass }}">{{ $statusLabel }}</span>
                </div>

                @if ($tpl->bodyText())
                    <div class="tpl-body">{{ $tpl->bodyText() }}</div>
                @endif

                @if ($tpl->status === 'REJECTED' && $tpl->rejection_reason)
                    <div class="tpl-rejection">
                        <strong>Motivo da rejeição:</strong> {{ $tpl->rejection_reason }}
                    </div>
                @endif

                <div class="tpl-actions" style="margin-top: 10px;">
                    @if ($tpl->isSendable())
                        @php
                            $varCount = 0;
                            preg_match_all('/\{\{(\d+)\}\}/', $tpl->bodyText() ?? '', $mm);
                            $varCount = count(array_unique($mm[1] ?? []));
                        @endphp
                        <button type="button"
                                class="tpl-btn tpl-btn-test"
                                data-tpl-id="{{ $tpl->id }}"
                                data-tpl-name="{{ $tpl->name }}"
                                data-tpl-lang="{{ $tpl->language }}"
                                data-tpl-vars="{{ $varCount }}"
                                onclick="openSendTestModal(this)">
                            <i class="fas fa-paper-plane"></i> Enviar teste
                        </button>
                    @endif
                    <form method="POST" action="{{ route('whatsapp.templates.cloud.destroy', $tpl->id) }}" onsubmit="return confirm('Deletar template {{ $tpl->name }}? Isso também remove da Meta.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tpl-btn tpl-btn-danger">
                            <i class="fas fa-trash"></i> Deletar
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
</div>

{{-- Modal Enviar Teste --}}
<div id="send-test-modal" class="tpl-modal-backdrop" onclick="closeSendTestModal(event)">
    <div class="tpl-modal" onclick="event.stopPropagation()">
        <h3><i class="fas fa-paper-plane" style="color:#1e40af"></i> Enviar template de teste</h3>
        <p class="subtitle">Template: <strong id="stm-name"></strong> · idioma <strong id="stm-lang"></strong></p>

        <label for="stm-to">Número destinatário (E.164)</label>
        <input id="stm-to" type="text" placeholder="+5511987654321" autocomplete="off">
        <div style="font-size:.75rem; color:#6b7280; margin: -6px 0 12px;">
            Cadastrado como testador na Meta. Ex: <code>+5516997618695</code>
        </div>

        <div id="stm-vars-container"></div>

        <div id="stm-result" class="tpl-modal-result"></div>

        <div class="tpl-modal-actions">
            <button type="button" class="tpl-btn tpl-btn-secondary" onclick="closeSendTestModal()">Fechar</button>
            <button type="button" id="stm-send-btn" class="tpl-btn tpl-btn-primary" onclick="doSendTest()">
                <i class="fas fa-paper-plane"></i> Enviar agora
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const STM_CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let __currentTplId = null;

    function openSendTestModal(btn) {
        __currentTplId = btn.dataset.tplId;
        document.getElementById('stm-name').textContent = btn.dataset.tplName;
        document.getElementById('stm-lang').textContent = btn.dataset.tplLang;
        document.getElementById('stm-to').value = '';
        document.getElementById('stm-result').className = 'tpl-modal-result';
        document.getElementById('stm-result').textContent = '';

        const varCount = parseInt(btn.dataset.tplVars, 10) || 0;
        const cont = document.getElementById('stm-vars-container');
        cont.innerHTML = '';
        for (let i = 1; i <= varCount; i++) {
            const wrap = document.createElement('div');
            wrap.innerHTML =
                '<label for="stm-var-' + i + '">Variável &#123;&#123;' + i + '&#125;&#125;</label>' +
                '<input id="stm-var-' + i + '" type="text" placeholder="valor da variavel ' + i + '" data-var-index="' + i + '">';
            cont.appendChild(wrap);
        }

        document.getElementById('send-test-modal').classList.add('open');
    }

    function closeSendTestModal(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('send-test-modal').classList.remove('open');
    }

    async function doSendTest() {
        const to = document.getElementById('stm-to').value.trim();
        const resultBox = document.getElementById('stm-result');
        const btn = document.getElementById('stm-send-btn');

        if (!/^\+?\d{10,15}$/.test(to)) {
            resultBox.className = 'tpl-modal-result err';
            resultBox.textContent = 'Número inválido. Use formato E.164 (ex: +5511987654321).';
            return;
        }

        const variables = Array.from(document.querySelectorAll('#stm-vars-container input')).map(i => i.value.trim());

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        resultBox.className = 'tpl-modal-result';
        resultBox.textContent = '';

        try {
            const res = await fetch(`/whatsapp/cloud/templates/${__currentTplId}/send-test`, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     STM_CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                },
                body: JSON.stringify({ to, variables }),
            });
            const body = await res.json();

            if (res.ok && body.ok) {
                resultBox.className = 'tpl-modal-result ok';
                resultBox.innerHTML = '<i class="fas fa-check-circle"></i> Enviado! Message ID: <code>' + (body.provider_message_id || 'n/a') + '</code>. Deve chegar em segundos.';
            } else {
                resultBox.className = 'tpl-modal-result err';
                resultBox.innerHTML = '<i class="fas fa-times-circle"></i> Falha: ' + (body.error || 'erro desconhecido');
            }
        } catch (err) {
            resultBox.className = 'tpl-modal-result err';
            resultBox.textContent = 'Erro de comunicação: ' + err.message;
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar agora';
    }
</script>
@endpush

@endsection
