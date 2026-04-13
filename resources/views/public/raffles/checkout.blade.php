<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX | {{ $raffle->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    {{-- QR Code nativo: geração 100% client-side sem dependência de API externa --}}
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --bg-body: #f1f5f9;
            --text-main: #1e293b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .checkout-container {
            max-width: 600px;
            margin: 40px auto;
            width: 100%;
            padding: 0 20px;
        }

        .checkout-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            text-align: center;
        }

        .qr-wrapper {
            display: flex;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .qr-wrapper img,
        .qr-wrapper canvas {
            max-width: 200px;
            width: 200px;
            height: 200px;
            padding: 12px;
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .pix-code-box {
            background: #f8fafc;
            border: 1px dashed var(--border-color);
            border-radius: 15px;
            padding: 20px;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
            color: var(--primary);
            margin: 20px 0;
            position: relative;
            text-align: left;
        }

        .btn-copy {
            position: absolute;
            top: -15px;
            right: 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .step-badge {
            width: 32px;
            height: 32px;
            min-width: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin-right: 12px;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 15px;
            padding: 18px;
            font-weight: 800;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.4);
            color: white;
        }

        .info-alert {
            background: rgba(67, 97, 238, 0.05);
            border: 1px solid rgba(67, 97, 238, 0.1);
            border-radius: 15px;
            padding: 15px;
            text-align: left;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="checkout-container">
    <div class="text-center mb-4">
        <h2 class="fw-800 mb-2">Quase lá! 🚀</h2>
        <p class="text-muted">Sua reserva para {{ $tickets->count() }} {{ $tickets->count() > 1 ? 'números' : 'número' }} foi realizada.</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            @foreach($tickets as $ticket)
                <span class="badge bg-primary rounded-pill px-3 py-2">#{{ str_pad($ticket->number, 2, '0', STR_PAD_LEFT) }}</span>
            @endforeach
        </div>
    </div>

    <div class="checkout-card">
        <h4 class="fw-800 mb-4">Pagamento PIX</h4>

        {{-- QR Code gerado client-side a partir do payload PIX --}}
        <div class="qr-wrapper">
            <canvas id="qr-native"></canvas>
        </div>

        <h3 class="fw-800 text-primary mb-4">Total: R$ {{ number_format($totalAmount, 2, ',', '.') }}</h3>

        <div class="mb-5 text-start">
            <div class="d-flex align-items-center mb-3">
                <span class="step-badge">1</span>
                <span class="fw-bold">Copie o código PIX abaixo</span>
            </div>

            <div class="pix-code-box">
                <button class="btn-copy" onclick="copyPix()">COPIAR CÓDIGO</button>
                <span id="pixPayload" style="font-size: 11px; opacity: 0.8;">{{ $pixPayload }}</span>
            </div>
        </div>

        @if(!empty($whatsappSupport))
            <div class="mb-5 text-start">
                <div class="d-flex align-items-center mb-3">
                    <span class="step-badge">2</span>
                    <span class="fw-bold">Envie o comprovante pelo WhatsApp</span>
                </div>

                @php
                    $numbersStr = $tickets->map(fn($t) => '#'.str_pad($t->number, 2, '0', STR_PAD_LEFT))->implode(', ');
                    $waMessage  = urlencode("Olá! Acabei de pagar a rifa: {$raffle->title}\nBilhetes: {$numbersStr}\nTotal: R$ " . number_format($totalAmount, 2, ',', '.') . "\nSegue o comprovante em anexo.");
                    $waLink     = "https://wa.me/" . preg_replace('/\D/', '', $whatsappSupport) . "?text=" . $waMessage;
                @endphp

                <a href="{{ $waLink }}" target="_blank" class="btn btn-success w-100 py-3 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center">
                    <i class="fab fa-whatsapp me-2 fs-4"></i> ENVIAR COMPROVANTE AGORA
                </a>
                <div class="form-text mt-2 text-center text-muted">
                    Clique acima para falar com <strong>{{ $tenant->name }}</strong>
                </div>
            </div>
        @endif

        <a href="{{ route('public.raffle.show', $raffle->slug) }}" class="btn btn-primary-gradient w-100 shadow-lg">
            VOLTAR PARA A RIFA
        </a>

        <p class="mt-4 text-muted small">
            Problemas com o pagamento?<br>
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsappSupport) }}" class="text-primary text-decoration-none fw-bold">Suporte {{ $tenant->name }}</a>
        </p>
    </div>
</div>

<script>
    // ── QR Code Nativo (PIX Estático) ─────────────────────────────────────────
    // Só executa quando não há imagem do OpenPix (modo estático)
    (function () {
        const canvas = document.getElementById('qr-native');
        if (!canvas) return; // OpenPix dinâmico: não precisa gerar

        const pixPayload = document.getElementById('pixPayload').innerText.trim();
        if (!pixPayload) return;

        QRCode.toCanvas(canvas, pixPayload, {
            width: 200,
            margin: 1,
            color: { dark: '#1e293b', light: '#ffffff' },
            errorCorrectionLevel: 'M',
        }, function (err) {
            if (err) console.error('Erro ao gerar QR Code:', err);
        });
    })();

    // ── Copiar código PIX ─────────────────────────────────────────────────────
    function copyPix() {
        const pixText = document.getElementById('pixPayload').innerText.trim();
        navigator.clipboard.writeText(pixText).then(() => {
            Toastify({
                text: '✅ Código PIX copiado!',
                duration: 3000,
                gravity: 'top',
                position: 'center',
                style: { background: 'linear-gradient(to right, #4361ee, #4cc9f0)' },
            }).showToast();
        }).catch(() => {
            // Fallback para browsers sem clipboard API (ex: iOS antigo)
            const el = document.createElement('textarea');
            el.value = pixText;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            alert('Código PIX copiado!');
        });
    }
</script>
</body>
</html>
