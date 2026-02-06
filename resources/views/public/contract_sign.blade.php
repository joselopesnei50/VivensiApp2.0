@extends('layouts.app')

@section('content')
<style>
    .contract-paper { 
        background: white; 
        padding: 60px; 
        max-width: 800px; 
        margin: 0 auto; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        min-height: 800px; 
    }
    canvas {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        width: 100%;
        cursor: crosshair;
        background: #f8fafc;
    }
    .meta-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.8rem;
        color: #0f172a;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    @media print {
        .no-print { display: none !important; }
        .contract-paper { box-shadow: none; padding: 0; margin: 0; max-width: none; }
        body { background: white !important; }
    }
</style>

<div class="header-page no-print" style="margin-bottom: 20px; text-align: center;">
    <h2 style="color: #2c3e50; margin-bottom: 8px;">Assinatura Digital</h2>
    <p style="margin: 0; color:#64748b;">Por favor, revise o documento e assine no quadro abaixo.</p>
</div>

<div class="contract-paper">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 20px; flex-wrap:wrap; margin-bottom: 18px;">
        <div>
            <div style="font-size:0.9rem; color:#64748b;">{{ $tenant->name ?? 'Organização' }}</div>
            <h1 style="margin: 6px 0 0 0; font-size: 1.5rem; color:#0f172a;">{{ $contract->title }}</h1>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
            <span class="meta-badge">
                <strong>Código de autenticidade:</strong>
                <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                    {{ strtoupper(substr($contract->document_hash ?? '', 0, 16)) }}
                </span>
            </span>
            @if($contract->public_sign_expires_at)
                <span class="meta-badge">
                    <strong>Válido até:</strong> {{ $contract->public_sign_expires_at->format('d/m/Y') }}
                </span>
            @endif
            @if($contract->status == 'signed')
                <button type="button" class="btn-outline no-print" style="padding: 8px 12px; border-radius: 10px; cursor:pointer;" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            @endif
        </div>
    </div>
    
    <div style="font-size: 1rem; line-height: 1.8; color: #334155; margin-bottom: 40px;">
        {!! nl2br(e($contract->content)) !!}
    </div>

    <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; margin-bottom: 40px; font-size: 0.9rem; color: #64748b;">
        <p><strong>Dados do Signatário:</strong></p>
        <p>Nome: {{ $contract->signer_name }}</p>
        @if($contract->signer_cpf) <p>CPF: {{ $contract->signer_cpf }}</p> @endif
        @if($contract->signer_rg) <p>RG: {{ $contract->signer_rg }}</p> @endif
        @if($contract->signer_address) <p>Endereço: {{ $contract->signer_address }}</p> @endif
        @if($contract->signer_phone) <p>Telefone: {{ $contract->signer_phone }}</p> @endif
    </div>

    @if($contract->status == 'signed')
        <div style="margin-top: 50px; text-align: center;">
            <p style="margin-bottom: 20px;">Assinado digitalmente por <strong>{{ $contract->signer_name }}</strong></p>
            <div style="margin-bottom: 10px;">
                <img src="{{ $contract->signature_image }}" style="max-width: 300px; border-bottom: 1px solid #000;">
            </div>
            <p style="font-size: 0.8rem; color: #94a3b8; line-height: 1.5;">
                Data/Hora: {{ $contract->signed_at->format('d/m/Y H:i:s') }}<br>
                Endereço IP: {{ $contract->signer_ip ?? 'N/A' }}<br>
                @if($contract->signer_user_agent) Dispositivo: {{ $contract->signer_user_agent }}<br> @endif
                Hash da assinatura: <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">{{ strtoupper(substr($contract->signature_hash ?? '', 0, 16)) }}</span><br>
                Autenticação Digital Vivensi
            </p>
        </div>
        <div style="background: #dcfce7; padding: 20px; text-align: center; border-radius: 8px; margin-top: 20px; color: #16a34a; font-weight: 700;">
            ESTE CONTRATO JÁ FOI ASSINADO.
        </div>
    @else
        @if($errors->has('signature'))
            <div class="no-print" style="background: #fee2e2; border: 1px solid #fecaca; color:#991b1b; padding: 12px 14px; border-radius: 10px; margin-bottom: 15px;">
                {{ $errors->first('signature') }}
            </div>
        @endif

        <div style="margin-top: 50px;" class="no-print">
            <p style="font-weight: 600; margin-bottom: 10px;">Assine aqui, {{ $contract->signer_name }}:</p>
            <canvas id="signature-pad" width="600" height="200"></canvas>
            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                <button type="button" id="clear" class="btn-outline" style="font-size: 0.9rem; padding: 6px 12px;">Limpar</button>
            </div>
        </div>

        <form action="{{ url('/sign/'.$contract->token) }}" method="POST" id="signForm" style="margin-top: 30px;" class="no-print">
            @csrf
            <input type="hidden" name="signature" id="signatureInput">
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; padding: 15px; font-size: 1.1rem;">
                <i class="fas fa-file-contract"></i> Confirmar Assinatura
            </button>
            <p style="margin: 10px 0 0 0; color:#94a3b8; font-size:0.85rem; line-height:1.4; text-align:center;">
                Ao confirmar, você concorda com os termos acima e registra uma assinatura eletrônica.
            </p>
        </form>
    @endif
</div>

<script>
    var canvas = document.getElementById('signature-pad');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var isDrawing = false;
        var hasDrawn = false;

        // Resize canvas to fit width
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            // Reset transforms to avoid cumulative scaling.
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            ctx.scale(ratio, ratio);
        }
        window.addEventListener("resize", resizeCanvas);
        // Call once to set initial size
        setTimeout(resizeCanvas, 100);

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            // Touch or Mouse
            var clientX = e.touches ? e.touches[0].clientX : e.clientX;
            var clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDraw(e) {
            e.preventDefault();
            isDrawing = true;
            var pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            var pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasDrawn = true;
        }

        function endDraw() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', endDraw);
        canvas.addEventListener('mouseleave', endDraw);

        canvas.addEventListener('touchstart', startDraw);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', endDraw);

        document.getElementById('clear').addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
        });

        document.getElementById('signForm').addEventListener('submit', function(e) {
            if (!hasDrawn) {
                e.preventDefault();
                alert('Por favor, desenhe sua assinatura antes de confirmar.');
                return;
            }
            var signature = canvas.toDataURL();
            // Simple check if empty (approximate)
            document.getElementById('signatureInput').value = signature;
        });
    }
</script>
@endsection
