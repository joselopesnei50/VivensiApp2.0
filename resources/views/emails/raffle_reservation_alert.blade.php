<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 36px 20px; text-align: center; }
        .header img { width: 130px; height: auto; }
        .alert-banner { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 28px 40px; text-align: center; }
        .alert-banner h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .alert-banner p { margin: 0; font-size: 14px; color: rgba(255,255,255,0.75); }
        .content { padding: 36px 40px; line-height: 1.6; color: #334155; }
        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin: 24px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .info-row:last-child { border-bottom: none; padding-bottom: 0; }
        .info-label { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; }
        .info-value { color: #1e293b; font-weight: 700; text-align: right; }
        .ticket-numbers { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 16px 20px; text-align: center; margin: 20px 0; }
        .ticket-numbers span { font-size: 20px; font-weight: 800; color: #4f46e5; letter-spacing: 1px; }
        .total-box { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #86efac; border-radius: 10px; padding: 16px 20px; text-align: center; margin: 20px 0; }
        .total-box .total-label { font-size: 12px; font-weight: 700; color: #16a34a; text-transform: uppercase; letter-spacing: .05em; }
        .total-box .total-value { font-size: 28px; font-weight: 900; color: #15803d; margin-top: 4px; }
        .cta-btn { display: inline-block; padding: 14px 36px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #ffffff !important; text-decoration: none; border-radius: 25px; font-weight: 700; font-size: 15px; margin-top: 8px; }
        .notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-top: 24px; }
        .footer { background-color: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container">

        <!-- Logo -->
        <div class="header">
            <img loading="lazy" src="{{ config('app.url') }}/novalogo.png" alt="Vivensi">
        </div>

        <!-- Alert Banner -->
        <div class="alert-banner">
            <h1>🎟️ Nova Reserva Recebida!</h1>
            <p>Alguém acabou de reservar bilhetes na sua rifa</p>
        </div>

        <div class="content">
            <p>Olá! Você tem uma nova reserva na rifa <strong>{{ $raffle->title }}</strong>. Confira os detalhes abaixo:</p>

            <!-- Buyer Info -->
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Comprador</span>
                    <span class="info-value">{{ $buyerName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">WhatsApp</span>
                    <span class="info-value">
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $buyerPhone) }}" style="color:#4f46e5;text-decoration:none;">
                            {{ $buyerPhone }}
                        </a>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rifa</span>
                    <span class="info-value">{{ $raffle->title }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Qtd. de Bilhetes</span>
                    <span class="info-value">{{ count($tickets) }}</span>
                </div>
            </div>

            <!-- Ticket Numbers -->
            <div class="ticket-numbers">
                <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Números Reservados</div>
                <span>
                    @foreach($tickets as $ticket)
                        #{{ str_pad($ticket->number, 3, '0', STR_PAD_LEFT) }}{{ !$loop->last ? ' · ' : '' }}
                    @endforeach
                </span>
            </div>

            <!-- Total -->
            <div class="total-box">
                <div class="total-label">Valor Total a Receber</div>
                <div class="total-value">R$ {{ number_format($totalAmount, 2, ',', '.') }}</div>
            </div>

            <!-- Notice -->
            <div class="notice">
                <strong>⏱ Atenção:</strong> Esta reserva expira em <strong>30 minutos</strong> se o pagamento não for confirmado.
                O comprador recebeu o código PIX por e-mail.
            </div>

            <!-- CTA -->
            <div style="text-align:center;margin-top:32px;">
                <a href="{{ config('app.url') }}/raffles" class="cta-btn">
                    Ver Painel da Rifa
                </a>
            </div>
        </div>

        <div class="footer">
            <p style="margin-bottom:6px;">© {{ date('Y') }} {{ $raffle->tenant->name ?? 'Vivensi' }}. Todos os direitos reservados.</p>
            <p style="margin:0;">Este é um e-mail automático gerado pelo sistema Vivensi.</p>
        </div>
    </div>
</body>
</html>
