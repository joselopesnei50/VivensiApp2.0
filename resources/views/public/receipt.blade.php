<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo Oficial #{{ $transaction->id }} | {{ $tenant->name }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --text-dark: #1e293b;
        }
        body {
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Outfit', sans-serif;
        }
        .receipt-card {
            background: #fff;
            width: 100%;
            max-width: 450px;
            border-radius: 32px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .receipt-top-stripe {
            height: 12px;
            background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%);
        }
        .receipt-content {
            padding: 45px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .tenant-logo {
            width: 64px;
            height: 64px;
            background: #f8fafc;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: var(--primary-color);
            border: 1px solid #e2e8f0;
        }
        .tenant-name {
            font-weight: 900;
            font-size: 1.4rem;
            color: var(--text-dark);
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }
        .receipt-type {
            font-size: 0.75rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .receipt-body {
            font-family: 'Courier Prime', monospace;
            background: #fcfdfe;
            border: 1px dashed #e2e8f0;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 35px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #475569;
        }
        .info-label { font-weight: 400; color: #94a3b8; }
        .info-value { font-weight: 700; color: #1e293b; }

        .amount-focus {
            text-align: center;
            padding: 25px 0;
            border-top: 2px solid #f1f5f9;
            border-bottom: 2px solid #f1f5f9;
            margin: 25px 0;
        }
        .amount-currency { font-size: 1.2rem; font-weight: 800; vertical-align: middle; color: #94a3b8; }
        .amount-value { font-size: 2.8rem; font-weight: 900; vertical-align: middle; color: var(--text-dark); letter-spacing: -2px; }

        .receipt-text {
            font-family: 'Outfit', sans-serif;
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .receipt-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #cbd5e1;
            padding-top: 30px;
            border-top: 1px solid #f1f5f9;
        }
        .auth-code {
            font-family: 'Courier Prime', monospace;
            background: #f8fafc;
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-top: 10px;
            display: inline-block;
            color: #94a3b8;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px;
        }
        .btn-print {
            background: var(--text-dark);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 18px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .btn-print:hover { transform: translateY(-5px); box-shadow: 0 30px 60px rgba(0,0,0,0.15); }

        @media print {
            body { background: white; padding: 0; }
            .receipt-card { box-shadow: none; border: none; max-width: 100%; border-radius: 0; }
            .actions { display: none; }
            .receipt-top-stripe { display: none; }
        }
    </style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center;">
    <div class="receipt-card">
        <div class="receipt-top-stripe"></div>
        <div class="receipt-content">
            <div class="receipt-header">
                <div class="tenant-logo">
                    <i class="fas fa-landmark"></i>
                </div>
                <div class="tenant-name">{{ $tenant->name }}</div>
                <div class="receipt-type">Recibo Oficial de Repasse</div>
            </div>

            <div class="receipt-body">
                <div class="info-row">
                    <span class="info-label">REGISTRO</span>
                    <span class="info-value">#{{ str_pad($transaction->id, 8, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">DATA</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($transaction->date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">HORÁRIO</span>
                    <span class="info-value">{{ $transaction->created_at->format('H:i:s') }}</span>
                </div>
                <div class="info-row" style="margin-top: 15px; border-top: 1px dotted #e2e8f0; padding-top: 15px;">
                    <span class="info-label">DOC/CNPJ</span>
                    <span class="info-value">{{ $tenant->document ?? 'RESTRITO' }}</span>
                </div>
            </div>

            <div class="amount-focus">
                <span class="amount-currency">R$</span>
                <span class="amount-value">{{ number_format($transaction->amount, 2, ',', '.') }}</span>
            </div>

            <p class="receipt-text">
                Confirmamos o recebimento da importância acima de <strong>{{ $transaction->description }}</strong>, referente ao aporte/pagamento processado pelo ecossistema Vivensi.
            </p>

            <div class="receipt-footer">
                <div style="font-weight: 800; color: #94a3b8; margin-bottom: 5px;">CÓDIGO DE VALIDAÇÃO</div>
                <div class="auth-code">{{ $transaction->receipt_auth_code }}</div>
                <div style="margin-top: 12px; font-weight: 800; font-size: 0.75rem;">
                    <a href="{{ url('/validar-recibo') . '?query=' . urlencode($transaction->receipt_auth_code) }}" style="color: var(--primary-color); text-decoration: none;">
                        Validar este recibo
                    </a>
                </div>
                @if($transaction->public_receipt_expires_at)
                    <div style="margin-top: 14px; font-weight: 900; color: #64748b; letter-spacing: 1px; font-size: 0.65rem; text-transform: uppercase;">
                        Link válido até {{ $transaction->public_receipt_expires_at->format('d/m/Y') }}
                    </div>
                @else
                    <div style="margin-top: 14px; font-weight: 900; color: #64748b; letter-spacing: 1px; font-size: 0.65rem; text-transform: uppercase;">
                        Link sem expiração
                    </div>
                @endif
                <p style="margin-top: 20px; font-weight: 700; color: var(--primary-color);">Tecnologia Vivensi 2.0</p>
            </div>
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Emitir Documento</button>
        <a href="javascript:window.history.back()" style="color: #94a3b8; font-weight: 700; text-decoration: none; font-size: 0.9rem;">Voltar ao Sistema</a>
    </div>
</div>

</body>
</html>
