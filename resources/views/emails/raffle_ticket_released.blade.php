<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #ef4444 0%, #991b1b 100%); padding: 40px 20px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: -1px; }
        .content { padding: 30px; line-height: 1.6; color: #444444; }
        .ticket-info { background-color: #fef2f2; border: 1px dashed #fecaca; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .ticket-number { font-size: 32px; font-weight: bold; color: #ef4444; display: block; margin-top: 10px; }
        .raffle-title { font-weight: bold; color: #1e293b; }
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
        .button { display: inline-block; padding: 12px 30px; background-color: #1e293b; color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Aviso de Reserva de Bilhete</h1>
        </div>
        <div class="content">
            <p>Olá, {{ $ticket->buyer_name }}.</p>
            <p>Gostaríamos de informar que sua reserva do bilhete para a rifa <span class="raffle-title">{{ $raffle->title }}</span> foi expirada ou cancelada manualmente pela organização.</p>
            
            <div class="ticket-info">
                <span>O SEGUINTE NÚMERO FOI LIBERADO:</span>
                <span class="ticket-number">#{{ str_pad($ticket->number, 3, '0', STR_PAD_LEFT) }}</span>
            </div>

            <p>Isso acontece quando não identificamos a confirmação do pagamento no prazo estipulado. O número agora está disponível novamente para outros interessados na página pública da rifa.</p>
            
            <p>Se você já realizou o pagamento e acredita que houve um erro, por favor entre em contato com a <strong>{{ $raffle->tenant->name }}</strong> através do WhatsApp de suporte disponível na página da campanha.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('public.raffle.show', $raffle->slug) }}" class="button">Ver Página da Rifa</a>
            </div>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático enviado por Vivensi Solidário.<br>
            © {{ date('Y') }} {{ $raffle->tenant->name }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
