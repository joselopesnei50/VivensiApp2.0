<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interesse Registrado — Vivensi</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 56px 48px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,0.3);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            box-shadow: 0 8px 24px rgba(79,70,229,0.35);
        }
        .icon-wrap i { font-size: 2rem; color: #fff; }
        .badge {
            display: inline-block;
            background: #ede9fe;
            color: #6d28d9;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        h1 span { color: #4f46e5; }
        p {
            font-size: 1rem;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 36px;
        }
        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 36px 0;
        }
        .whatsapp-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 14px;
        }
        .wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #25d366;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            padding: 14px 28px;
            border-radius: 14px;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(37,211,102,0.3);
        }
        .wa-btn:hover {
            background: #1fba58;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37,211,102,0.4);
        }
        .wa-btn i { font-size: 1.3rem; }
        .footer-note {
            margin-top: 32px;
            font-size: 0.78rem;
            color: #cbd5e1;
        }
        .logo {
            margin-bottom: 36px;
        }
        .logo img { height: 36px; }
        @media (max-width: 540px) {
            .card { padding: 40px 28px; }
            h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <img src="{{ asset('novalogo.png') }}" alt="Vivensi">
        </div>

        <div class="icon-wrap">
            <i class="fas fa-clock"></i>
        </div>

        <div class="badge">Acesso em breve</div>

        <h1>Recebemos seu <span>interesse!</span></h1>

        <p>
            O Vivensi está em fase de lançamento e disponível para
            <strong>um número limitado de entidades</strong> neste momento.<br><br>
            Nossa equipe analisará seu cadastro e entrará em contato
            para liberar seu acesso. Fique de olho no seu e-mail!
        </p>

        <div class="divider"></div>

        <div class="whatsapp-label">Fale com a gente agora</div>

        @php
            $msg = urlencode('Olá! Acabei de me cadastrar no Vivensi e gostaria de saber mais sobre o acesso à plataforma.');
        @endphp

        <a href="https://wa.me/55{{ $whatsapp }}?text={{ $msg }}" class="wa-btn" target="_blank">
            <i class="fab fa-whatsapp"></i>
            Conversar pelo WhatsApp
        </a>

        <p class="footer-note">
            © {{ date('Y') }} Vivensi · Impulsionando o Terceiro Setor com Inteligência
        </p>
    </div>
</body>
</html>
