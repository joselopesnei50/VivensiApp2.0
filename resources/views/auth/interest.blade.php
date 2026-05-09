<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi — Lançamento 25 de Maio</title>
    <meta name="description" content="O Vivensi chega dia 25 de maio. ERP completo com IA para ONGs e gestores do terceiro setor.">
    <meta property="og:title" content="Vivensi — Lançamento 25 de Maio de 2026">
    <meta property="og:description" content="ERP completo com IA para ONGs. Garanta sua vaga agora.">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple: #6366f1;
            --purple-light: #818cf8;
            --purple-dark: #4f46e5;
            --violet: #8b5cf6;
            --bg: #05071a;
            --bg2: #0d1030;
            --text: #f1f5f9;
            --muted: #64748b;
            --border: rgba(99,102,241,0.2);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
        }

        /* ───── ANIMATED BACKGROUND ───── */
        .bg-layer {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }
        .bg-layer::before {
            content: '';
            position: absolute;
            top: -30%;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 600px;
            background: radial-gradient(ellipse, rgba(99,102,241,0.22) 0%, transparent 65%);
            animation: float1 12s ease-in-out infinite alternate;
        }
        .bg-layer::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -15%;
            width: 700px;
            height: 500px;
            background: radial-gradient(ellipse, rgba(139,92,246,0.14) 0%, transparent 65%);
            animation: float2 15s ease-in-out infinite alternate;
        }
        @keyframes float1 { from { transform: translateX(-50%) translateY(0); } to { transform: translateX(-50%) translateY(40px); } }
        @keyframes float2 { from { transform: translateY(0) scale(1); } to { transform: translateY(-30px) scale(1.05); } }

        /* grid pattern overlay */
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ───── LAYOUT ───── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
        }

        /* ───── HEADER ───── */
        .logo-wrap {
            margin-bottom: 44px;
            text-align: center;
        }
        .logo-wrap img {
            height: 52px;
            filter: drop-shadow(0 0 24px rgba(99,102,241,0.4));
        }

        /* ───── BADGE ───── */
        .launch-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(99,102,241,0.45);
            background: rgba(99,102,241,0.1);
            color: #a5b4fc;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 7px 18px;
            border-radius: 100px;
            margin-bottom: 28px;
        }
        .pulse {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #a5b4fc;
            position: relative;
        }
        .pulse::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: rgba(165,180,252,0.3);
            animation: pulse 2s ease-out infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(2.5); opacity: 0; } }

        /* ───── HEADLINE ───── */
        .headline {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -1.5px;
            text-align: center;
            margin-bottom: 22px;
            max-width: 780px;
        }
        .headline .grad {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ───── SUBTITLE ───── */
        .subtitle {
            font-size: 1.05rem;
            color: #94a3b8;
            line-height: 1.75;
            text-align: center;
            max-width: 540px;
            margin-bottom: 52px;
        }
        .subtitle strong { color: #e2e8f0; }

        /* ───── DATE PILL ───── */
        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 100px;
            padding: 10px 24px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #c7d2fe;
            margin-bottom: 20px;
            letter-spacing: 0.01em;
        }
        .date-pill i { color: #818cf8; font-size: 0.85rem; }

        /* ───── COUNTDOWN ───── */
        .countdown {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 60px;
        }
        .cd-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 88px;
        }
        .cd-num {
            font-size: clamp(2.8rem, 6vw, 4.2rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(180deg, #f1f5f9 40%, rgba(241,245,249,0.5) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-variant-numeric: tabular-nums;
            background-color: var(--bg2);
            padding: 18px 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.07);
            min-width: 88px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .cd-num::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 50%;
            background: rgba(255,255,255,0.03);
        }
        .cd-lbl {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-top: 10px;
        }
        .cd-sep {
            font-size: 3rem;
            font-weight: 900;
            color: rgba(99,102,241,0.4);
            margin-top: 18px;
            line-height: 1;
            padding: 0 4px;
        }

        /* ───── FEATURES ───── */
        .features {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            max-width: 1020px;
            width: 100%;
            margin-bottom: 52px;
        }
        .feat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px;
            padding: 22px 18px;
            text-align: center;
            transition: border-color 0.2s, background 0.2s;
        }
        .feat:hover {
            border-color: rgba(99,102,241,0.35);
            background: rgba(99,102,241,0.06);
        }
        .feat-icon {
            font-size: 1.8rem;
            margin-bottom: 12px;
            display: block;
        }
        .feat-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 6px;
        }
        .feat-desc {
            font-size: 0.73rem;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ───── DIVIDER ───── */
        .divider {
            width: 100%;
            max-width: 860px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(99,102,241,0.3), transparent);
            margin-bottom: 40px;
        }

        /* ───── CTA SECTION ───── */
        .cta-wrap {
            text-align: center;
            max-width: 480px;
            width: 100%;
        }
        .cta-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .1em;
            margin-bottom: 16px;
        }
        .wa-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            padding: 16px 32px;
            border-radius: 14px;
            transition: all 0.25s;
            box-shadow: 0 8px 32px rgba(37,211,102,0.25);
            margin-bottom: 16px;
        }
        .wa-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 40px rgba(37,211,102,0.35);
        }
        .wa-btn i { font-size: 1.25rem; }

        .login-link {
            font-size: 0.82rem;
            color: var(--muted);
        }
        .login-link a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }

        /* ───── FOOTER ───── */
        .footer {
            margin-top: 56px;
            text-align: center;
            font-size: 0.73rem;
            color: #1e2a45;
        }

        /* ───── RESPONSIVE ───── */
        @media (max-width: 1020px) {
            .features { grid-template-columns: repeat(3, 1fr); max-width: 660px; }
        }
        @media (max-width: 680px) {
            .features { grid-template-columns: repeat(2, 1fr); max-width: 480px; }
        }
        @media (max-width: 540px) {
            .headline { font-size: 2rem; letter-spacing: -0.5px; }
            .features { grid-template-columns: 1fr 1fr; gap: 8px; }
            .cd-block { min-width: 68px; }
            .cd-num { font-size: 2.2rem; min-width: 68px; padding: 14px 8px 10px; }
            .cd-sep { font-size: 2rem; margin-top: 14px; }
            .feat { padding: 16px 12px; }
        }
        @media (max-width: 380px) {
            .features { grid-template-columns: 1fr; }
            .countdown { gap: 4px; }
        }
    </style>
</head>
<body>
<div class="bg-layer"></div>
<div class="bg-grid"></div>

<div class="page">

    {{-- Logo --}}
    <div class="logo-wrap">
        <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
    </div>

    {{-- Badge --}}
    <div class="launch-badge">
        <span class="pulse"></span>
        Lançamento oficial confirmado
    </div>

    {{-- Headline --}}
    <h1 class="headline">
        O ERP que o <span class="grad">Terceiro Setor</span><br>estava esperando
    </h1>

    <p class="subtitle">
        Gestão financeira, projetos, editais, WhatsApp e <strong>IA integrada</strong>
        numa plataforma feita para ONGs e gestores sociais.
        Acesso liberado em:
    </p>

    {{-- Date pill --}}
    <div class="date-pill">
        <i class="fas fa-calendar-star"></i>
        25 de Maio de 2026 &nbsp;·&nbsp; Segunda-feira
    </div>

    {{-- Countdown --}}
    <div class="countdown">
        <div class="cd-block">
            <div class="cd-num" id="cd-d">--</div>
            <div class="cd-lbl">Dias</div>
        </div>
        <div class="cd-sep">:</div>
        <div class="cd-block">
            <div class="cd-num" id="cd-h">--</div>
            <div class="cd-lbl">Horas</div>
        </div>
        <div class="cd-sep">:</div>
        <div class="cd-block">
            <div class="cd-num" id="cd-m">--</div>
            <div class="cd-lbl">Min</div>
        </div>
        <div class="cd-sep">:</div>
        <div class="cd-block">
            <div class="cd-num" id="cd-s">--</div>
            <div class="cd-lbl">Seg</div>
        </div>
    </div>

    {{-- Features --}}
    <div class="features">
        <div class="feat">
            <span class="feat-icon">🤖</span>
            <div class="feat-title">Bruce AI</div>
            <div class="feat-desc">Relatórios, análises e automações com IA integrada</div>
        </div>
        <div class="feat">
            <span class="feat-icon">💬</span>
            <div class="feat-title">WhatsApp</div>
            <div class="feat-desc">Disparo em massa, bot inteligente e omnichannel</div>
        </div>
        <div class="feat">
            <span class="feat-icon">📋</span>
            <div class="feat-title">Editais & Grants</div>
            <div class="feat-desc">Captação de recursos com análise de PDF por IA</div>
        </div>
        <div class="feat">
            <span class="feat-icon">💰</span>
            <div class="feat-title">Financeiro</div>
            <div class="feat-desc">Orçamento, conciliação OFX e relatórios completos</div>
        </div>
        <div class="feat">
            <span class="feat-icon">📁</span>
            <div class="feat-title">Projetos</div>
            <div class="feat-desc">Kanban, tarefas, prazos e gestão de equipes</div>
        </div>
    </div>

    <div class="divider"></div>

    {{-- CTA --}}
    <div class="cta-wrap">
        <p class="cta-label">Garanta acesso antecipado</p>

        @php
            $msg = urlencode('Olá! Quero garantir meu acesso ao Vivensi no lançamento do dia 25 de maio. Pode me incluir na lista?');
            $wn  = $whatsapp ?? '16997618695';
        @endphp

        <a href="https://wa.me/55{{ $wn }}?text={{ $msg }}" class="wa-btn" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i>
            Entrar na lista pelo WhatsApp
        </a>

        <p class="login-link">
            Já tem uma conta? <a href="{{ route('login') }}">Fazer login</a>
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        © {{ date('Y') }} Vivensi · Todos os direitos reservados
    </div>

</div>

<script>
(function () {
    // 25 de Maio de 2026 às 00:00 horário de Brasília (UTC-3)
    var launch = new Date('2026-05-25T03:00:00Z'); // 00:00 BRT = 03:00 UTC

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var now  = new Date();
        var diff = Math.max(0, launch - now);

        if (diff === 0) {
            ['cd-d','cd-h','cd-m','cd-s'].forEach(function(id) {
                document.getElementById(id).textContent = '00';
            });
            return;
        }

        var s = Math.floor(diff / 1000);
        var m = Math.floor(s / 60);
        var h = Math.floor(m / 60);
        var d = Math.floor(h / 24);

        document.getElementById('cd-d').textContent = pad(d);
        document.getElementById('cd-h').textContent = pad(h % 24);
        document.getElementById('cd-m').textContent = pad(m % 60);
        document.getElementById('cd-s').textContent = pad(s % 60);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>
