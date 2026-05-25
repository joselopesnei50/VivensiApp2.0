@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="margin: 0; font-weight: 950; font-size: 2rem; letter-spacing: -1px;">Webhooks</h2>
            <p style="color: #64748b; margin: 6px 0 0; font-size: 0.95rem;">Receba notificações automáticas em tempo real quando eventos ocorrerem no Vivensi.</p>
        </div>
        <a href="{{ route('settings.api-tokens') }}" style="font-size: 0.8rem; color: #6366f1; font-weight: 700; text-decoration: none;">
            <i class="fas fa-key me-1"></i> API Tokens
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Criar webhook --}}
    <div class="col-lg-5">
        <div style="background: #0f172a; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.06);">
            <h4 style="color: white; font-weight: 900; margin-bottom: 24px; font-size: 1.1rem;">Novo Webhook</h4>
            <form method="POST" action="{{ route('settings.webhooks.store') }}">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label for="name" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Nome</label>
                    <input type="text" name="name" required placeholder="Ex: Integração ERP" maxlength="100"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;" id="name">
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="url" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">URL de Destino</label>
                    <input type="url" name="url" required placeholder="https://meusite.com/webhook"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none; font-family:monospace;" id="url">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 12px;">Eventos</label>
                    @foreach($events as $event)
                    @php
                        [$domain, $action] = explode('.', $event);
                        $colors = ['transaction'=>'#6366f1','project'=>'#f59e0b','task'=>'#10b981'];
                        $color  = $colors[$domain] ?? '#94a3b8';
                    @endphp
                    <label style="display:flex; align-items:center; gap:10px; padding:8px 0; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.03);">
                        <input type="checkbox" name="events[]" value="{{ $event }}" style="accent-color:{{ $color }}; width:15px; height:15px;">
                        <span style="font-size:0.78rem; color:{{ $color }}; font-weight:800; font-family:monospace;">{{ $event }}</span>
                    </label>
                    @endforeach
                </div>

                <button type="submit" style="width:100%; background:linear-gradient(135deg,#6366f1,#4f46e5); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.9rem; cursor:pointer; box-shadow:0 8px 24px rgba(99,102,241,0.25);">
                    <i class="fas fa-plug me-2"></i> Criar Webhook
                </button>
            </form>
        </div>

        {{-- Como funciona --}}
        <div style="background: #0f172a; border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.06); margin-top: 16px;">
            <h5 style="color: white; font-weight: 800; font-size: 0.9rem; margin-bottom: 14px;"><i class="fas fa-shield-halved me-2" style="color:#10b981;"></i>Verificação HMAC</h5>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.78rem; line-height: 1.6;">Cada requisição inclui o header:</p>
            <code style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px 14px; color:#94a3b8; font-size:0.72rem; display:block; font-family:monospace; margin:8px 0;">
                X-Vivensi-Signature: sha256=&lt;hmac&gt;
            </code>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.78rem; margin-top: 8px;">Valide com o <strong style="color:white;">secret</strong> do webhook usando <code style="font-size:0.7rem;">hash_hmac('sha256', $body, $secret)</code>.</p>
        </div>
    </div>

    {{-- Lista de webhooks --}}
    <div class="col-lg-7">
        <div style="background: #0f172a; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.06);">
            <h4 style="color: white; font-weight: 900; margin-bottom: 24px; font-size: 1.1rem;">
                Webhooks Registrados
                <span style="font-size:0.75rem; background:rgba(99,102,241,0.2); color:#818cf8; padding:3px 10px; border-radius:99px; margin-left:8px; font-weight:800;">{{ $webhooks->count() }}</span>
            </h4>

            @forelse($webhooks as $wh)
            <div style="padding:18px 20px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,{{ $wh->active ? '0.08' : '0.03' }}); border-radius:16px; margin-bottom:10px; {{ !$wh->active ? 'opacity:0.5;' : '' }}">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                            <div style="width:8px; height:8px; border-radius:50%; background:{{ $wh->active ? '#10b981' : '#ef4444' }}; box-shadow:0 0 6px {{ $wh->active ? '#10b981' : '#ef4444' }};"></div>
                            <span style="font-weight:800; color:white; font-size:0.9rem;">{{ $wh->name }}</span>
                        </div>
                        <code style="font-size:0.72rem; color:#94a3b8; font-family:monospace; word-break:break-all;">{{ $wh->url }}</code>
                        <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:4px;">
                            @foreach($wh->events as $ev)
                            @php [$dom] = explode('.', $ev); $c = ['transaction'=>'#6366f1','project'=>'#f59e0b','task'=>'#10b981'][$dom] ?? '#94a3b8'; @endphp
                            <span style="font-size:0.6rem; background:{{ $c }}18; color:{{ $c }}; padding:2px 8px; border-radius:99px; font-weight:800; font-family:monospace;">{{ $ev }}</span>
                            @endforeach
                        </div>
                        @if($wh->last_triggered_at)
                        <div style="margin-top:6px; font-size:0.68rem; color:rgba(255,255,255,0.3);">
                            <i class="fas fa-clock me-1"></i>Último disparo: {{ $wh->last_triggered_at->diffForHumans() }}
                        </div>
                        @endif
                    </div>
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                        <button onclick="showLogs({{ $wh->id }})" title="Ver logs" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:8px; padding:6px 10px; cursor:pointer; font-size:0.8rem;">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <form method="POST" action="{{ route('settings.webhooks.toggle', $wh->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" title="{{ $wh->active ? 'Desativar' : 'Ativar' }}" style="background:rgba({{ $wh->active ? '245,158,11' : '16,185,129' }},0.1); border:1px solid rgba({{ $wh->active ? '245,158,11' : '16,185,129' }},0.3); color:{{ $wh->active ? '#fbbf24' : '#34d399' }}; border-radius:8px; padding:6px 10px; cursor:pointer; font-size:0.8rem;">
                                <i class="fas fa-{{ $wh->active ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.webhooks.destroy', $wh->id) }}" onsubmit="return confirm('Remover este webhook?');" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#f87171; border-radius:8px; padding:6px 10px; cursor:pointer; font-size:0.8rem;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
                {{-- Secret --}}
                <div style="margin-top:10px; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:0.65rem; color:rgba(255,255,255,0.3); font-weight:700; text-transform:uppercase; letter-spacing:1px;">Secret:</span>
                    <code id="secret-{{ $wh->id }}" style="font-size:0.72rem; color:#64748b; font-family:monospace; filter:blur(4px); cursor:pointer; transition:.2s;" onclick="this.style.filter='none'; setTimeout(()=>this.style.filter='blur(4px)',8000);" title="Clique para revelar">{{ $wh->secret }}</code>
                </div>
                {{-- Logs panel --}}
                <div id="logs-{{ $wh->id }}" style="display:none; margin-top:12px; background:rgba(0,0,0,0.3); border-radius:10px; padding:12px; font-size:0.72rem; color:#94a3b8; font-family:monospace; max-height:160px; overflow-y:auto;"></div>
            </div>
            @empty
            <div style="text-align:center; padding:40px 20px; color:rgba(255,255,255,0.3);">
                <i class="fas fa-plug" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
                <p style="font-weight:700;">Nenhum webhook registrado.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
async function showLogs(id) {
    const panel = document.getElementById('logs-' + id);
    if (panel.style.display !== 'none') { panel.style.display = 'none'; return; }
    panel.style.display = 'block';
    panel.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Carregando...';
    try {
        const res  = await fetch('/settings/webhooks/' + id + '/logs', { headers: {'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'} });
        const logs = await res.json();
        if (!logs.length) { panel.innerHTML = 'Nenhum disparo registrado ainda.'; return; }
        panel.innerHTML = logs.map(l =>
            `<div style="padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.04); display:flex; align-items:center; gap:8px;">
                <span style="color:${l.success?'#34d399':'#f87171'}; min-width:14px;">${l.success?'✓':'✗'}</span>
                <span style="color:#818cf8; font-size:0.7rem;">${l.event}</span>
                <span style="color:#64748b; font-size:0.7rem;">→ ${l.http_status ?? 'ERR'}</span>
                <span style="color:#475569; font-size:0.65rem; flex:1; text-align:right;">${l.fired_at}</span>
                ${!l.success ? `<form method="POST" action="/settings/webhooks/${id}/retry/${l.id}" style="display:inline;"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" style="background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);color:#fbbf24;border-radius:6px;padding:2px 8px;font-size:0.65rem;cursor:pointer;font-weight:800;">retry</button></form>` : ''}
             </div>`
        ).join('');
    } catch(e) { panel.innerHTML = 'Erro ao carregar logs.'; }
}
</script>
@endpush
@endsection
