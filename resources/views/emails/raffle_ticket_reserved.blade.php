<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); padding: 40px 20px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: -1px; }
        .content { padding: 30px; line-height: 1.6; color: #444444; }
        .ticket-info { background-color: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .ticket-number { font-size: 24px; font-weight: bold; color: #4361ee; display: block; margin-top: 10px; }
        .pix-box { background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; word-break: break-all; font-family: monospace; font-size: 13px; margin: 20px 0; }
        .raffle-title { font-weight: bold; color: #1e293b; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4361ee; color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sua reserva foi realizada! 🚀</h1>
        </div>
        <div class="content">
            <p>Olá, tudo bem?</p>
            <p>Suas cotas para a rifa <span class="raffle-title">{{ $raffle->title }}</span> foram reservadas com sucesso.</p>
            
            <div class="ticket-info">
                <span>NÚMEROS RESERVADOS:</span>
                <span class="ticket-number">
                    @foreach($tickets as $ticket)
                        #{{ str_pad($ticket->number, 3, '0', STR_PAD_LEFT) }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </span>
                <p><strong>Total: R$ {{ number_format($totalAmount, 2, ',', '.') }}</strong></p>
            </div>

            <p>Agora falta pouco! Para garantir sua participação, realize o pagamento via PIX utilizando o código abaixo:</p>

            <div class="pix-box">
                {{ $pixPayload }}
            </div>

            <p><strong>Importante:</strong> Esta reserva expira em 30 minutos. Após realizar o pagamento, nosso sistema identificará automaticamente seu pedido.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('public.raffle.show', $raffle->slug) }}" class="button">Ver Campanha</a>
            </div>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático enviado por Vivensi Solidário.<br>
            © {{ date('Y') }} {{ $raffle->tenant->name }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
