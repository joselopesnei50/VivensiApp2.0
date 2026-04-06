<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX | {{ $raffle->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #121212;
            --glass: rgba(255, 255, 255, 0.05);
        }

        body {
            background-color: var(--dark);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
        }

        .checkout-container {
            max-width: 600px;
            margin: 60px auto;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
        }

        .pix-code-box {
            background: rgba(255,255,255,0.03);
            border: 1px dashed rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 20px;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
            color: var(--accent);
            margin: 20px 0;
            position: relative;
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
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .step-badge {
            width: 30px;
            height: 30px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .form-control-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            padding: 12px;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
    </style>
</head>
<body>

    <div class="container checkout-container text-white">
        <div class="text-center mb-5 text-white">
            <h2 class="fw-bold mb-2 text-white">Quase lá! 🚀</h2>
            <p class="text-muted-light">Sua reserva para {{ $tickets->count() }} {{ $tickets->count() > 1 ? 'números' : 'número' }} foi realizada.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap text-white">
                @foreach($tickets as $ticket)
                    <span class="badge bg-primary rounded-pill px-3 py-2">#{{ str_pad($ticket->number, 2, '0', STR_PAD_LEFT) }}</span>
                @endforeach
            </div>
        </div>

        <div class="glass-card text-white">
            <h4 class="fw-bold mb-4 text-white">Pagamento PIX</h4>
            <h3 class="fw-bold text-accent mb-4 text-white">Total: R$ {{ number_format($totalAmount, 2, ',', '.') }}</h3>
            
            <div class="mb-4 text-start text-white">
                <div class="d-flex align-items-center mb-3 text-white">
                    <span class="step-badge text-white">1</span>
                    <span class="text-white">Copie o código PIX abaixo</span>
                </div>
                
                <div class="pix-code-box text-white">
                    <button class="btn-copy text-white" onclick="copyPix()">COPIAR CÓDIGO</button>
                    <span id="pixPayload">{{ $pixPayload }}</span>
                </div>
            </div>

            <div class="mb-5 text-start text-white">
                <div class="d-flex align-items-center mb-3 text-white">
                    <span class="step-badge text-white">2</span>
                    <span class="text-white">Anexe o comprovante de pagamento</span>
                </div>
                
                <form action="{{ route('public.raffle.receipt', $tickets->first()->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4 text-white">
                        <input type="file" name="receipt" class="form-control form-control-glass text-white" required accept="image/*,.pdf">
                        <small class="text-muted-light d-block mt-2 text-white">Formatos aceitos: JPG, PNG ou PDF (Máx 2MB)</small>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 fs-5 shadow-lg text-white">
                        ENVIAR COMPROVANTE
                    </button>
                </form>
            </div>
            
            <div class="alert alert-info border-0 text-start small mb-0 text-white" style="background: rgba(76, 201, 240, 0.1); color: var(--accent); border-radius: 15px;">
                <i class="bi bi-info-circle-fill me-2 text-white"></i>
                <strong>Importante:</strong> Sua reserva é válida por 30 minutos. Após o envio do comprovante, nossa equipe validará o pagamento e você receberá a confirmação por e-mail.
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('public.raffle.show', $raffle->slug) }}" class="text-muted text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i> Voltar para a rifa
            </a>
        </div>
    </div>

    <script>
        function copyPix() {
            const pixText = document.getElementById("pixPayload").innerText;
            navigator.clipboard.writeText(pixText);
            
            Toastify({
                text: "Código PIX copiado!",
                duration: 3000,
                gravity: "top",
                position: "center",
                style: {
                    background: "linear-gradient(to right, #4361ee, #4cc9f0)",
                }
            }).showToast();
        }
    </script>
</body>
</html>
