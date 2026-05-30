@extends('layouts.app')
@section('title', 'E-mail Marketing')

@section('content')
<style>
    .ec-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
    }
    .ec-badge-draft    { background: #f1f5f9; color: #64748b; }
    .ec-badge-sending  { background: #fef9c3; color: #854d0e; }
    .ec-badge-sent     { background: #dcfce7; color: #166534; }
    .ec-badge-error    { background: #fef2f2; color: #991b1b; }
    .ec-badge-scheduled{ background: #eff6ff; color: #1d4ed8; }
    .ec-action { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center;
                 background:#f1f5f9; color:#64748b; border:none; cursor:pointer; transition:all 0.2s; text-decoration:none; }
    .ec-action:hover { background:#e2e8f0; color:#1e293b; }
    .ec-action-red:hover { background:#fef2f2; color:#ef4444; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div>
        <h2 class="fw-bold text-dark m-0" style="font-size:1.6rem; letter-spacing:-0.5px;">E-mail Marketing</h2>
        <p class="text-muted mt-1" style="font-size:0.9rem;">Comunique-se com doadores e leads da sua organização.</p>
    </div>
    <a href="{{ route('ngo.email_campaigns.create') }}"
       style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; background:#6366f1; color:white; border-radius:14px; font-weight:800; font-size:0.88rem; text-decoration:none;">
        <i class="fas fa-plus"></i> Nova Campanha
    </a>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:14px 20px; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fef2f2; color:#991b1b; padding:14px 20px; border-radius:12px; margin-bottom:20px; border:1px solid #fca5a5; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

<div class="vivensi-card" style="border-radius:20px; overflow:hidden;">
    @if($campaigns->isEmpty())
        <div style="padding:60px; text-align:center; color:#94a3b8;">
            <div style="font-size:3rem; margin-bottom:16px; opacity:0.4;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div style="font-weight:800; color:#1e293b; font-size:1.1rem; margin-bottom:8px;">Nenhuma campanha ainda</div>
            <p style="font-size:0.88rem; margin:0 0 24px;">Crie sua primeira campanha de e-mail para se comunicar com sua base de doadores.</p>
            <a href="{{ route('ngo.email_campaigns.create') }}"
               style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; background:#6366f1; color:white; border-radius:12px; font-weight:700; font-size:0.88rem; text-decoration:none;">
                <i class="fas fa-plus"></i> Criar Primeira Campanha
            </a>
        </div>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:14px 24px; text-align:left; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Campanha</th>
                    <th style="padding:14px 24px; text-align:left; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Público</th>
                    <th style="padding:14px 24px; text-align:center; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Status</th>
                    <th style="padding:14px 24px; text-align:center; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Enviados</th>
                    <th style="padding:14px 24px; text-align:center; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Data</th>
                    <th style="padding:14px 24px; text-align:right; font-size:0.72rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fcfdfe'" onmouseout="this.style.background=''">
                    <td style="padding:18px 24px; vertical-align:middle;">
                        <a href="{{ route('ngo.email_campaigns.show', $c) }}"
                           style="font-weight:800; color:#1e293b; text-decoration:none; font-size:0.9rem; display:block;">{{ $c->name }}</a>
                        <span style="color:#94a3b8; font-size:0.75rem;">{{ Str::limit($c->subject, 55) }}</span>
                    </td>
                    <td style="padding:18px 24px; vertical-align:middle;">
                        <span style="font-size:0.8rem; color:#475569; font-weight:600;">{{ $c->audienceLabel() }}</span>
                    </td>
                    <td style="padding:18px 24px; vertical-align:middle; text-align:center;">
                        @php
                            $badgeClass = match($c->status) {
                                'draft'     => 'ec-badge-draft',
                                'sending'   => 'ec-badge-sending',
                                'sent'      => 'ec-badge-sent',
                                'error'     => 'ec-badge-error',
                                'scheduled' => 'ec-badge-scheduled',
                                default     => 'ec-badge-draft',
                            };
                            $badgeIcon = match($c->status) {
                                'draft'     => 'fa-pencil',
                                'sending'   => 'fa-spinner fa-spin',
                                'sent'      => 'fa-check',
                                'error'     => 'fa-triangle-exclamation',
                                'scheduled' => 'fa-clock',
                                default     => 'fa-pencil',
                            };
                            $badgeLabel = match($c->status) {
                                'draft'     => 'Rascunho',
                                'sending'   => 'Enviando',
                                'sent'      => 'Enviada',
                                'error'     => 'Erro',
                                'scheduled' => 'Agendada',
                                default     => ucfirst($c->status),
                            };
                        @endphp
                        <span class="ec-badge {{ $badgeClass }}">
                            <i class="fas {{ $badgeIcon }}"></i> {{ $badgeLabel }}
                        </span>
                    </td>
                    <td style="padding:18px 24px; vertical-align:middle; text-align:center;">
                        <span style="font-weight:700; color:#1e293b; font-size:0.88rem;">
                            {{ $c->recipient_count ? number_format($c->recipient_count) : '—' }}
                        </span>
                    </td>
                    <td style="padding:18px 24px; vertical-align:middle; text-align:center;">
                        <span style="font-size:0.78rem; color:#64748b;">
                            {{ $c->sent_at ? $c->sent_at->format('d/m/Y H:i') : $c->created_at->format('d/m/Y') }}
                        </span>
                    </td>
                    <td style="padding:18px 24px; vertical-align:middle; text-align:right;">
                        <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center;">
                            @if(in_array($c->status, ['draft', 'error']))
                                <form id="_formDisparar{{ $c->id }}" action="{{ route('ngo.email_campaigns.send', $c) }}" method="POST" style="display:none;">@csrf</form>
                                <button type="button"
                                        onclick="abrirModalDisparar({{ $c->id }}, '{{ addslashes($c->name) }}', '{{ addslashes($c->audienceLabel()) }}', {{ $c->recipient_count ?: 'null' }})"
                                        class="ec-action" title="{{ $c->status === 'error' ? 'Tentar novamente' : 'Disparar campanha' }}"
                                        style="background:#eff6ff; color:#6366f1;">
                                    <i class="fas fa-paper-plane" style="font-size:0.78rem;"></i>
                                </button>
                            @endif
                            <a href="{{ route('ngo.email_campaigns.show', $c) }}" class="ec-action" title="Ver detalhes">
                                <i class="fas fa-eye" style="font-size:0.78rem;"></i>
                            </a>
                            @if($c->status !== 'sent')
                                <form id="_formExcluir{{ $c->id }}" action="{{ route('ngo.email_campaigns.destroy', $c) }}" method="POST" style="display:none;">@csrf @method('DELETE')</form>
                                <button type="button"
                                        onclick="abrirModalExcluir({{ $c->id }}, '{{ addslashes($c->name) }}')"
                                        class="ec-action ec-action-red" title="Excluir campanha">
                                    <i class="fas fa-trash" style="font-size:0.78rem;"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($campaigns->hasPages())
            <div style="padding:20px 24px; border-top:1px solid #f1f5f9;">
                {{ $campaigns->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Modal Disparar --}}
<div id="modalDisparar" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
    <div onclick="fecharModalDisparar()" style="position:absolute; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px);"></div>
    <div style="position:relative; background:#fff; border-radius:24px; padding:40px; max-width:460px; width:90%; box-shadow:0 25px 50px rgba(0,0,0,0.15); border:1px solid #e2e8f0;">
        <div style="width:64px; height:64px; background:#fef3c7; border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:1.6rem;">
            <i class="fas fa-paper-plane" style="color:#d97706;"></i>
        </div>
        <h3 style="text-align:center; margin:0 0 8px; font-size:1.25rem; font-weight:900; color:#0f172a;">Confirmar disparo</h3>
        <p style="text-align:center; color:#64748b; font-size:0.88rem; margin:0 0 28px; line-height:1.6;">
            Esta ação é <strong>irreversível</strong>. O e-mail será enviado imediatamente a todos os destinatários.
        </p>
        <div style="background:#f8fafc; border-radius:14px; padding:16px 20px; margin-bottom:28px; border:1px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Campanha</span>
                <span id="modalDNome" style="color:#1e293b; font-weight:800; text-align:right; max-width:60%;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Público</span>
                <span id="modalDPublico" style="color:#1e293b; font-weight:800;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Destinatários est.</span>
                <span id="modalDDestinatarios" style="color:#6366f1; font-weight:800;"></span>
            </div>
        </div>
        <div style="display:flex; gap:12px;">
            <button onclick="fecharModalDisparar()" style="flex:1; padding:14px; border:2px solid #e2e8f0; border-radius:12px; background:white; color:#64748b; font-weight:800; font-size:0.9rem; cursor:pointer;">Cancelar</button>
            <button onclick="confirmarDisparar()" id="btnConfirmarDisparar"
                    style="flex:1; padding:14px; border:none; border-radius:12px; background:#6366f1; color:white; font-weight:800; font-size:0.9rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="fas fa-paper-plane"></i> Disparar agora
            </button>
        </div>
    </div>
</div>

{{-- Modal Excluir --}}
<div id="modalExcluir" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
    <div onclick="fecharModalExcluir()" style="position:absolute; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px);"></div>
    <div style="position:relative; background:#fff; border-radius:24px; padding:40px; max-width:420px; width:90%; box-shadow:0 25px 50px rgba(0,0,0,0.15); border:1px solid #e2e8f0;">
        <div style="width:64px; height:64px; background:#fef2f2; border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:1.6rem;">
            <i class="fas fa-trash" style="color:#ef4444;"></i>
        </div>
        <h3 style="text-align:center; margin:0 0 8px; font-size:1.25rem; font-weight:900; color:#0f172a;">Excluir campanha?</h3>
        <p style="text-align:center; color:#64748b; font-size:0.88rem; margin:0 0 8px; line-height:1.6;">
            A campanha <strong id="modalENome"></strong> será excluída permanentemente.
        </p>
        <p style="text-align:center; color:#ef4444; font-size:0.8rem; margin:0 0 28px; font-weight:700;">Esta ação não pode ser desfeita.</p>
        <div style="display:flex; gap:12px;">
            <button onclick="fecharModalExcluir()" style="flex:1; padding:14px; border:2px solid #e2e8f0; border-radius:12px; background:white; color:#64748b; font-weight:800; font-size:0.9rem; cursor:pointer;">Cancelar</button>
            <button onclick="confirmarExcluir()" style="flex:1; padding:14px; border:none; border-radius:12px; background:#ef4444; color:white; font-weight:800; font-size:0.9rem; cursor:pointer;">
                <i class="fas fa-trash me-1"></i> Excluir
            </button>
        </div>
    </div>
</div>

<script>
let _formDisparar = null, _formExcluir = null;

function abrirModalDisparar(id, nome, publico, destinatarios) {
    _formDisparar = document.getElementById('_formDisparar' + id);
    document.getElementById('modalDNome').textContent = nome;
    document.getElementById('modalDPublico').textContent = publico;
    document.getElementById('modalDDestinatarios').textContent = destinatarios
        ? destinatarios.toLocaleString('pt-BR') + ' contatos (est.)'
        : 'a calcular no envio';
    const modal = document.getElementById('modalDisparar');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function fecharModalDisparar() {
    document.getElementById('modalDisparar').style.display = 'none';
    document.body.style.overflow = '';
}
function confirmarDisparar() {
    const btn = document.getElementById('btnConfirmarDisparar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    if (_formDisparar) _formDisparar.submit();
}

function abrirModalExcluir(id, nome) {
    _formExcluir = document.getElementById('_formExcluir' + id);
    document.getElementById('modalENome').textContent = nome;
    const modal = document.getElementById('modalExcluir');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function fecharModalExcluir() {
    document.getElementById('modalExcluir').style.display = 'none';
    document.body.style.overflow = '';
}
function confirmarExcluir() {
    if (_formExcluir) _formExcluir.submit();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { fecharModalDisparar(); fecharModalExcluir(); }
});
</script>
@endsection
