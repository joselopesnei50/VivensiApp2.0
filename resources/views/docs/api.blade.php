@extends('layouts.app')

@section('content')
<div style="max-width: 900px; margin: 0 auto;">

{{-- Header --}}
<div style="margin-bottom: 36px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
        <div style="background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); border-radius:10px; padding:8px 14px; font-size:0.7rem; font-weight:900; color:#818cf8; text-transform:uppercase; letter-spacing:2px;">v1.0</div>
        <div style="font-size:0.75rem; color:rgba(255,255,255,0.3); font-weight:700;">REST API</div>
    </div>
    <h1 style="color:white; font-weight:950; font-size:2.4rem; letter-spacing:-1.5px; margin:0;">Vivensi API</h1>
    <p style="color:rgba(255,255,255,0.5); margin:8px 0 0; font-size:0.95rem;">Integre qualquer sistema com o Vivensi usando nossa API REST autenticada.</p>
</div>

{{-- Auth info --}}
<div style="background:#0f172a; border-radius:20px; padding:28px; border:1px solid rgba(99,102,241,0.2); margin-bottom:24px;">
    <h3 style="color:white; font-weight:900; font-size:1rem; margin:0 0 16px;">Autenticação</h3>
    <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:12px;">Inclua o token no header de todas as requisições:</p>
    <div style="background:#020617; border-radius:12px; padding:14px 18px; font-family:monospace; font-size:0.82rem; color:#a5b4fc; border:1px solid rgba(99,102,241,0.15);">
        Authorization: Bearer &lt;seu-token&gt;
    </div>
    <div style="display:flex; gap:12px; margin-top:14px; flex-wrap:wrap;">
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2); border-radius:10px; padding:10px 16px; font-size:0.75rem; color:#fbbf24; font-weight:700;">
            <i class="fas fa-gauge-high me-1"></i> Rate Limit: 60 req/min por token
        </div>
        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:10px; padding:10px 16px; font-size:0.75rem; color:#34d399; font-weight:700;">
            <i class="fas fa-shield-halved me-1"></i> Isolamento total por tenant
        </div>
        <a href="{{ route('settings.api-tokens') }}" style="background:rgba(99,102,241,0.12); border:1px solid rgba(99,102,241,0.25); border-radius:10px; padding:10px 16px; font-size:0.75rem; color:#818cf8; font-weight:700; text-decoration:none;">
            <i class="fas fa-key me-1"></i> Gerenciar tokens
        </a>
    </div>
</div>

{{-- Base URL --}}
<div style="background:#020617; border-radius:14px; padding:14px 18px; font-family:monospace; font-size:0.85rem; color:#6ee7b7; border:1px solid rgba(16,185,129,0.2); margin-bottom:28px; display:flex; align-items:center; gap:10px;">
    <span style="color:rgba(255,255,255,0.3); font-size:0.7rem; font-weight:800; text-transform:uppercase;">BASE URL</span>
    <span>{{ $baseUrl }}</span>
</div>

{{-- Endpoints --}}
@foreach($endpoints as $group)
<div style="margin-bottom:32px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
        <div style="width:10px; height:10px; border-radius:50%; background:{{ $group['color'] }};"></div>
        <h2 style="color:white; font-weight:950; font-size:1.1rem; margin:0;">{{ $group['group'] }}</h2>
    </div>

    @foreach($group['routes'] as [$method, $path, $desc, $body, $example])
    @php
        $methodColors = [
            'GET'    => ['bg'=>'rgba(16,185,129,0.12)', 'text'=>'#34d399', 'border'=>'rgba(16,185,129,0.25)'],
            'POST'   => ['bg'=>'rgba(99,102,241,0.12)',  'text'=>'#818cf8', 'border'=>'rgba(99,102,241,0.25)'],
            'PATCH'  => ['bg'=>'rgba(245,158,11,0.12)',  'text'=>'#fbbf24', 'border'=>'rgba(245,158,11,0.25)'],
            'DELETE' => ['bg'=>'rgba(239,68,68,0.1)',    'text'=>'#f87171', 'border'=>'rgba(239,68,68,0.2)'],
        ];
        $mc = $methodColors[$method] ?? $methodColors['GET'];
        $uid = 'ep-'.md5($method.$path);
    @endphp
    <div style="background:#0f172a; border:1px solid rgba(255,255,255,0.06); border-radius:16px; margin-bottom:8px; overflow:hidden;">
        <button onclick="document.getElementById('{{ $uid }}').classList.toggle('hidden')" style="width:100%; background:none; border:none; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer; text-align:left;">
            <span style="font-size:0.65rem; font-weight:900; padding:4px 10px; border-radius:6px; min-width:52px; text-align:center; background:{{ $mc['bg'] }}; color:{{ $mc['text'] }}; border:1px solid {{ $mc['border'] }}; font-family:monospace;">{{ $method }}</span>
            <code style="font-size:0.85rem; color:#e2e8f0; font-family:monospace; font-weight:700;">{{ $path }}</code>
            <span style="font-size:0.78rem; color:rgba(255,255,255,0.4); flex:1;">{{ $desc }}</span>
            <i class="fas fa-chevron-down" style="color:rgba(255,255,255,0.2); font-size:0.7rem;"></i>
        </button>

        <div id="{{ $uid }}" class="hidden" style="border-top:1px solid rgba(255,255,255,0.05); padding:20px;">
            @if(!empty($body))
            <div style="margin-bottom:14px;">
                <div style="font-size:0.65rem; font-weight:800; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Request Body</div>
                <pre style="background:#020617; border-radius:10px; padding:14px; font-size:0.78rem; color:#a5b4fc; overflow-x:auto; border:1px solid rgba(99,102,241,0.12); margin:0;">{{ json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
            @if($example)
            <div>
                <div style="font-size:0.65rem; font-weight:800; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Exemplo de Resposta</div>
                <pre style="background:#020617; border-radius:10px; padding:14px; font-size:0.78rem; color:#6ee7b7; overflow-x:auto; border:1px solid rgba(16,185,129,0.12); margin:0;">{{ json_encode(['data' => $example], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif
            <div style="margin-top:12px; padding:10px 14px; background:rgba(255,255,255,0.02); border-radius:8px; font-size:0.75rem; color:rgba(255,255,255,0.3);">
                <i class="fas fa-terminal me-1"></i>
                <code>curl -s -H "Authorization: Bearer &lt;token&gt;" {{ $baseUrl }}{{ $path }}</code>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endforeach

{{-- Webhooks section --}}
<div style="background:#0f172a; border-radius:20px; padding:28px; border:1px solid rgba(255,255,255,0.06); margin-bottom:24px;">
    <h3 style="color:white; font-weight:900; font-size:1rem; margin:0 0 16px;"><i class="fas fa-plug me-2" style="color:#818cf8;"></i>Webhooks</h3>
    <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:16px;">Registre uma URL para receber notificações automáticas quando eventos ocorrerem.</p>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
        @foreach($webhookEvents as $ev)
        @php [$dom] = explode('.', $ev); $c = ['transaction'=>'#34d399','project'=>'#fbbf24','task'=>'#818cf8'][$dom] ?? '#94a3b8'; @endphp
        <span style="font-size:0.65rem; background:rgba(255,255,255,0.04); color:{{ $c }}; padding:4px 12px; border-radius:99px; font-weight:800; font-family:monospace; border:1px solid {{ $c }}30;">{{ $ev }}</span>
        @endforeach
    </div>
    <a href="{{ route('settings.webhooks') }}" style="display:inline-flex; align-items:center; gap:8px; background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.25); color:#818cf8; border-radius:10px; padding:10px 16px; font-size:0.8rem; font-weight:800; text-decoration:none;">
        <i class="fas fa-cog"></i> Configurar Webhooks
    </a>
</div>

{{-- Error codes --}}
<div style="background:#0f172a; border-radius:20px; padding:28px; border:1px solid rgba(255,255,255,0.06);">
    <h3 style="color:white; font-weight:900; font-size:1rem; margin:0 0 16px;">Códigos de Resposta</h3>
    @foreach([
        ['200', 'OK', 'Requisição processada com sucesso.', '#34d399'],
        ['201', 'Created', 'Recurso criado com sucesso.', '#34d399'],
        ['401', 'Unauthorized', 'Token ausente ou inválido.', '#f87171'],
        ['403', 'Forbidden', 'Sem permissão para este recurso.', '#f87171'],
        ['404', 'Not Found', 'Recurso não encontrado ou pertence a outro tenant.', '#f87171'],
        ['422', 'Unprocessable Entity', 'Erro de validação. Verifique os campos.', '#fbbf24'],
        ['429', 'Too Many Requests', 'Rate limit excedido (60 req/min).', '#fbbf24'],
        ['503', 'Service Unavailable', 'Dependência temporariamente indisponível.', '#f87171'],
    ] as [$code, $name, $desc, $color])
    <div style="display:flex; align-items:center; gap:14px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.04);">
        <span style="font-size:0.75rem; font-weight:900; color:{{ $color }}; font-family:monospace; min-width:36px;">{{ $code }}</span>
        <span style="font-size:0.8rem; font-weight:800; color:white; min-width:160px;">{{ $name }}</span>
        <span style="font-size:0.78rem; color:rgba(255,255,255,0.4);">{{ $desc }}</span>
    </div>
    @endforeach
</div>

</div>

@push('scripts')
<script>
document.querySelectorAll('.hidden').forEach(el => el.style.display = 'none');
document.querySelectorAll('[id^="ep-"]').forEach(el => {
    Object.defineProperty(el, 'hidden', {
        get() { return this.style.display === 'none'; },
        set(v) { this.style.display = v ? 'none' : 'block'; }
    });
});
// Override toggle to work with style.display
document.querySelectorAll('button[onclick*="classList.toggle"]').forEach(btn => {
    const id = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
    btn.setAttribute('onclick', `(function(){var e=document.getElementById('${id}');e.style.display=e.style.display==='none'?'block':'none';})()`);
});
</script>
@endpush
@endsection
