<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.07); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 32px 20px; text-align: center; }
        .header img { width: 130px; height: auto; }
        .content { padding: 32px 36px; line-height: 1.6; color: #334155; }
        h1 { font-size: 20px; color: #0f172a; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 10px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .muted { color: #64748b; font-size: 12px; }
        .cta-btn { display: inline-block; padding: 13px 32px; background: #0f172a; color: #ffffff !important; text-decoration: none; border-radius: 25px; font-weight: 700; font-size: 15px; }
        .footer { background-color: #f8fafc; padding: 20px 36px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{ config('app.url') }}/novalogo.png" alt="Vivensi">
    </div>
    <div class="content">
        <h1>Reservas de rifa perto de vencer</h1>
        <p>Olá, {{ $tenant->name }}. Estas reservas ainda não tiveram o pagamento confirmado. Se o PIX já caiu na conta, confirme em <strong>Rifas</strong>;
            caso contrário, os números voltam a ficar disponíveis no horário indicado.</p>

        <table>
            <thead><tr><th>Comprador</th><th>Números</th><th>Vence</th></tr></thead>
            <tbody>
            @foreach($orders as $o)
                <tr>
                    <td><strong>{{ $o['buyer'] }}</strong><br><span class="muted">{{ $o['phone'] }} · {{ $o['raffle'] }}</span></td>
                    <td>{{ $o['numbers'] }}<br><span class="muted">R$ {{ number_format($o['total'], 2, ',', '.') }}</span></td>
                    <td>{{ $o['expires'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div style="text-align:center;margin-top:24px;">
            <a href="{{ config('app.url') }}/raffles" class="cta-btn">Conferir reservas</a>
        </div>
    </div>
    <div class="footer">E-mail automático enviado pela Vivensi.</div>
</div>
</body>
</html>
