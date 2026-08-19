@extends('layouts.app')

@section('content')
<style>
    .contract-paper { background:#fff; padding: 40px 48px; max-width: 820px; margin: 24px auto; border-radius:12px; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
    .step-bar { display:flex; gap:8px; margin-bottom:20px; }
    .step-bar span { flex:1; padding:8px 10px; border-radius:8px; text-align:center; font-size:.85rem; background:#f1f5f9; color:#64748b; }
    .step-bar span.active { background:#0f172a; color:#fff; font-weight:600; }
    canvas { border:2px dashed #cbd5e1; border-radius:8px; width:100%; cursor:crosshair; background:#f8fafc; }
    .btn-cta { display:inline-flex; align-items:center; gap:8px; padding:14px 24px; background:#0f172a; color:#fff; border:0; border-radius:10px; font-size:1.05rem; font-weight:600; cursor:pointer; width:100%; justify-content:center; }
    .btn-outline { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#fff; color:#334155; border:1px solid #cbd5e1; border-radius:8px; font-size:.9rem; cursor:pointer; }
    .contract-body { font-size:.95rem; line-height:1.7; color:#334155; margin: 24px 0; padding: 20px; background:#f8fafc; border-radius:8px; max-height: 380px; overflow-y:auto; }
    .contract-body h2, .contract-body h3 { color:#0f172a; }
</style>

<div class="contract-paper">
    <div class="step-bar">
        <span>1. Dados da entidade</span>
        <span class="active">2. Revisar e assinar</span>
        <span>3. {{ ($plan && $plan->is_courtesy) ? 'Acesso liberado' : 'Pagamento' }}</span>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <h1 style="margin:0;font-size:1.4rem;color:#0f172a;">{{ $contract->title }}</h1>
        <a href="{{ route('adesao.show') }}" class="btn-outline">Editar dados</a>
    </div>
    <p style="margin:0 0 8px 0; color:#64748b; font-size:.9rem;">Revise o conteudo. Ao assinar, sua adesao e registrada com IP, hash HMAC e carimbo de tempo.</p>

    <div class="contract-body">
        {!! $contract->content !!}
    </div>

    @if($errors->has('signature'))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ $errors->first('signature') }}</div>
    @endif

    <p style="font-weight:600; margin: 20px 0 8px 0;">Assine no quadro abaixo, {{ $contract->signer_name }}:</p>
    <canvas id="signature-pad" width="700" height="220"></canvas>
    <div style="margin: 10px 0 24px 0; text-align:right;">
        <button type="button" id="clear" class="btn-outline">Limpar</button>
    </div>

    <form action="{{ route('adesao.sign', $contract->id) }}" method="POST" id="signForm">
        @csrf
        <input type="hidden" name="signature" id="signatureInput">
        <button type="submit" class="btn-cta">Confirmar assinatura e continuar</button>
        <p style="margin:12px 0 0 0; color:#94a3b8; font-size:.85rem; text-align:center;">
            @if($plan && $plan->is_courtesy)
                Ao confirmar, sua conta cortesia sera ativada e voce sera redirecionado ao painel.
            @else
                Ao confirmar, voce sera redirecionado ao pagamento AbacatePay para ativar a assinatura.
            @endif
        </p>
    </form>
</div>

<script>
(function () {
    var canvas = document.getElementById('signature-pad');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var drawing = false, drew = false;

    function resize() {
        var r = Math.max(window.devicePixelRatio || 1, 1);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        canvas.width = canvas.offsetWidth * r;
        canvas.height = canvas.offsetHeight * r;
        ctx.scale(r, r);
    }
    window.addEventListener('resize', resize);
    setTimeout(resize, 80);

    function pos(e) {
        var rect = canvas.getBoundingClientRect();
        var cx = e.touches ? e.touches[0].clientX : e.clientX;
        var cy = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: cx - rect.left, y: cy - rect.top };
    }
    function start(e) { e.preventDefault(); drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#000'; }
    function move(e) { if (!drawing) return; e.preventDefault(); var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); drew = true; }
    function end() { drawing = false; }

    ['mousedown','touchstart'].forEach(function(ev){ canvas.addEventListener(ev, start); });
    ['mousemove','touchmove'].forEach(function(ev){ canvas.addEventListener(ev, move); });
    ['mouseup','mouseleave','touchend'].forEach(function(ev){ canvas.addEventListener(ev, end); });

    document.getElementById('clear').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drew = false;
    });

    document.getElementById('signForm').addEventListener('submit', function (e) {
        if (!drew) {
            e.preventDefault();
            alert('Por favor, desenhe sua assinatura antes de confirmar.');
            return;
        }
        document.getElementById('signatureInput').value = canvas.toDataURL();
    });
})();
</script>
@endsection
