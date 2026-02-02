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
</style>

<div class="header-page" style="margin-bottom: 20px; text-align: center;">
    <h2 style="color: #2c3e50;">Assinatura Digital</h2>
    <p>Por favor, revise o documento e assine no quadro abaixo.</p>
</div>

<div class="contract-paper">
    <h1 style="text-align: center; margin-bottom: 40px; font-size: 1.5rem;">{{ $contract->title }}</h1>
    
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
                Autenticação Digital Vivensi App
            </p>
        </div>
        <div style="background: #dcfce7; padding: 20px; text-align: center; border-radius: 8px; margin-top: 20px; color: #16a34a; font-weight: 700;">
            ESTE CONTRATO JÁ FOI ASSINADO.
        </div>
    @else
        <div style="margin-top: 50px;">
            <p style="font-weight: 600; margin-bottom: 10px;">Assine aqui, {{ $contract->signer_name }}:</p>
            <canvas id="signature-pad" width="600" height="200"></canvas>
            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                <button type="button" id="clear" class="btn-outline" style="font-size: 0.9rem; padding: 6px 12px;">Limpar</button>
            </div>
        </div>

        <form action="{{ url('/sign/'.$contract->token) }}" method="POST" id="signForm" style="margin-top: 30px;">
            @csrf
            <input type="hidden" name="signature" id="signatureInput">
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; padding: 15px; font-size: 1.1rem;">
                <i class="fas fa-file-contract"></i> Confirmar Assinatura
            </button>
        </form>
    @endif
</div>

<script>
    var canvas = document.getElementById('signature-pad');
    if (canvas) {
        var ctx = canvas.getContext('2d');
        var isDrawing = false;

        // Resize canvas to fit width
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
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
        });

        document.getElementById('signForm').addEventListener('submit', function(e) {
            var signature = canvas.toDataURL();
            // Simple check if empty (approximate)
            document.getElementById('signatureInput').value = signature;
        });
    }
</script>
@endsection
