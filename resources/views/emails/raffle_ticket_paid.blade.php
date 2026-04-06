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
        .ticket-number { font-size: 32px; font-weight: bold; color: #4361ee; display: block; margin-top: 10px; }
        .raffle-title { font-weight: bold; color: #1e293b; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4361ee; color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Boa sorte, {{ $ticket->buyer_name }}!</h1>
        </div>
        <div class="content">
            <p>Olá, tudo bem?</p>
            <p>É com muita alegria que confirmamos o seu pagamento para a rifa <span class="raffle-title">{{ $raffle->title }}</span>.</p>
            <p>Sua participação é fundamental para apoiar as causas da <strong>{{ $raffle->tenant->name }}</strong>. Muito obrigado pela sua generosidade!</p>
            
            <div class="ticket-info">
                <span>ESTE É O SEU NÚMERO DA SORTE:</span>
                <span class="ticket-number">#{{ str_pad($ticket->number, 3, '0', STR_PAD_LEFT) }}</span>
            </div>

            <p>Fique de olho na data do sorteio: <strong>{{ \Carbon\Carbon::parse($raffle->draw_date)->format('d/m/Y \à\s H:i') }}</strong>.</p>
            
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
