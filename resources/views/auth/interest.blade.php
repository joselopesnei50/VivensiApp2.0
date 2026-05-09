<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Em Breve — Vivensi</title>
    <meta name="description" content="O Vivensi está chegando. Seja um dos primeiros a transformar sua ONG com IA.">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        /* ---- BACKGROUND GLOW ---- */
        .bg-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .bg-glow::before {
            content: '';
            position: absolute;
            top: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 500px;
            background: radial-gradient(ellipse, rgba(79,70,229,0.18) 0%, transparent 70%);
        }
        .bg-glow::after {
            content: '';
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 500px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(124,58,237,0.12) 0%, transparent 70%);
        }

        /* ---- CARD ---- */
        .card {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 28px;
            padding: 52px 48px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(16px);
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
        }

        /* ---- LOGO ---- */
        .logo { margin-bottom: 36px; }
        .logo img { height: 38px; filter: brightness(1.1); }

        /* ---- BADGE ---- */
        .launch-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg, rgba(79,70,229,0.3), rgba(124,58,237,0.3));
            border: 1px solid rgba(124,58,237,0.4);
            color: #a78bfa;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 20px;
            margin-bottom: 28px;
        }
        .badge-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #a78bfa;
            box-shadow: 0 0 8px #7c3aed;
            animation: blink 1.8s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* ---- HEADLINE ---- */
        h1 {
            font-size: 2.1rem;
            font-weight: 900;
            color: #f8fafc;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 18px;
        }
        h1 .accent {
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ---- SUBTEXT ---- */
        .subtitle {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 36px;
        }
        .subtitle strong { color: #e2e8f0; }

        /* ---- COUNTDOWN ---- */
        .countdown-wrap {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            padding: 24px 16px;
            margin-bottom: 36px;
            display: flex;
            justify-content: center;
            gap: 8px;
            align-items: center;
        }
        .countdown-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 64px;
        }
        .countdown-num {
            font-size: 2rem;
            font-weight: 900;
            color: #f8fafc;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .countdown-lbl {
            font-size: 0.62rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-top: 5px;
        }
        .countdown-sep {
            font-size: 1.8rem;
            font-weight: 900;
            color: #334155;
            padding-bottom: 14px;
            align-self: flex-start;
            margin-top: 2px;
        }

        /* ---- FEATURES ---- */
        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 36px;
            text-align: left;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 12px 14px;
        }
        .feature-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .feature-text {
            font-size: 0.78rem;
            color: #94a3b8;
            line-height: 1.4;
        }
        .feature-text strong { color: #e2e8f0; display: block; font-size: 0.8rem; }

        /* ---- DIVIDER ---- */
        .divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin: 32px 0;
        }

        /* ---- CTA ---- */
        .cta-label {
            font-size: 0.73rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .07em;
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
            padding: 15px 32px;
            border-radius: 14px;
            transition: all 0.2s;
            box-shadow: 0 6px 24px rgba(37,211,102,0.3);
            width: 100%;
            justify-content: center;
        }
        .wa-btn:hover {
            background: #1fba58;
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(37,211,102,0.4);
        }
        .wa-btn i { font-size: 1.3rem; }

        /* ---- FOOTER ---- */
        .footer-note {
            margin-top: 28px;
            font-size: 0.74rem;
            color: #334155;
        }
        .footer-note a { color: #4f46e5; text-decoration: none; }
        .footer-note a:hover { text-decoration: underline; }

        /* ---- RESPONSIVE ---- */
        @media (max-width: 540px) {
            .card { padding: 36px 24px; }
            h1 { font-size: 1.6rem; }
            .features { grid-template-columns: 1fr; }
            .countdown-block { min-width: 52px; }
            .countdown-num { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
<div class="bg-glow"></div>

<div class="card">
    <div class="logo">
        <img src="{{ asset('novalogo.png') }}" alt="Vivensi">
    </div>

    <div class="launch-badge">
        <span class="badge-dot"></span>
        Lançamento em breve
    </div>

    <h1>O futuro das ONGs<br>está chegando <span class="accent">semana que vem</span></h1>

    <p class="subtitle">
        O Vivensi — ERP completo com <strong>IA para o terceiro setor</strong> — abre as portas
        em poucos dias. Gestão financeira, projetos, editais, WhatsApp e muito mais.
    </p>

    {{-- Countdown --}}
    <div class="countdown-wrap">
        <div class="countdown-block">
            <div class="countdown-num" id="cd-days">--</div>
            <div class="countdown-lbl">Dias</div>
        </div>
        <div class="countdown-sep">:</div>
        <div class="countdown-block">
            <div class="countdown-num" id="cd-hours">--</div>
            <div class="countdown-lbl">Horas</div>
        </div>
        <div class="countdown-sep">:</div>
        <div class="countdown-block">
            <div class="countdown-num" id="cd-mins">--</div>
            <div class="countdown-lbl">Min</div>
        </div>
        <div class="countdown-sep">:</div>
        <div class="countdown-block">
            <div class="countdown-num" id="cd-secs">--</div>
            <div class="countdown-lbl">Seg</div>
        </div>
    </div>

    {{-- Features --}}
    <div class="features">
        <div class="feature-item">
            <span class="feature-icon">🤖</span>
            <div class="feature-text"><strong>IA Integrada</strong>Análise financeira e automação com Bruce AI</div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">💬</span>
            <div class="feature-text"><strong>WhatsApp Nativo</strong>Comunicação e campanhas em massa</div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">📋</span>
            <div class="feature-text"><strong>Editais & Projetos</strong>Gestão de grants e captação de recursos</div>
        </div>
        <div class="feature-item">
            <span class="feature-icon">💰</span>
            <div class="feature-text"><strong>Financeiro Completo</strong>Transações, orçamento e conciliação OFX</div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="cta-label">Entre na lista — seja um dos primeiros</div>

    @php
        $msg = urlencode('Olá! Vi que o Vivensi vai lançar em breve e quero ser um dos primeiros a ter acesso. Pode me avisar quando abrir?');
        $wn  = $whatsapp ?? '16997618695';
    @endphp
    <a href="https://wa.me/55{{ $wn }}?text={{ $msg }}" class="wa-btn" target="_blank" rel="noopener">
        <i class="fab fa-whatsapp"></i>
        Garantir minha vaga pelo WhatsApp
    </a>

    <p class="footer-note">
        Já tem uma conta? <a href="{{ route('login') }}">Faça login</a>
        &nbsp;·&nbsp;
        © {{ date('Y') }} Vivensi · Impulsionando o Terceiro Setor
    </p>
</div>

<script>
    // Countdown para próxima segunda-feira às 09:00
    (function () {
        function nextMonday9am() {
            var now  = new Date();
            var next = new Date(now);
            var day  = now.getDay(); // 0=Dom, 1=Seg…
            var daysUntilMonday = day === 1 ? 7 : (8 - day) % 7 || 7;
            next.setDate(now.getDate() + daysUntilMonday);
            next.setHours(9, 0, 0, 0);
            return next;
        }

        var target = nextMonday9am();

        function pad(n) { return String(n).padStart(2, '0'); }

        function tick() {
            var now  = new Date();
            var diff = Math.max(0, target - now);
            var s    = Math.floor(diff / 1000);
            var m    = Math.floor(s / 60);
            var h    = Math.floor(m / 60);
            var d    = Math.floor(h / 24);

            document.getElementById('cd-days').textContent  = pad(d);
            document.getElementById('cd-hours').textContent = pad(h % 24);
            document.getElementById('cd-mins').textContent  = pad(m % 60);
            document.getElementById('cd-secs').textContent  = pad(s % 60);
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>
</body>
</html>
