<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi — Entrar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --black:   #080808;
            --dark:    #111111;
            --mid:     #1a1a1a;
            --border-d: rgba(255,255,255,.07);
            --white:   #ffffff;
            --off:     #f5f5f5;
            --muted:   #999999;
            --accent:  #4F6EF7;
            --accent2: #7B5CF0;
            --green:   #22c55e;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--black);
            color: var(--white);
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ─── LEFT PANEL ─────────────────────────────────────────────── */
        .panel-left {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            overflow: hidden;
            background: var(--black);
        }

        /* Animated gradient orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            animation: orbDrift 12s ease-in-out infinite alternate;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(79,110,247,.18) 0%, transparent 70%);
            top: -100px; left: -100px;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(123,92,240,.14) 0%, transparent 70%);
            bottom: -80px; right: -80px;
            animation-delay: -6s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(34,197,94,.08) 0%, transparent 70%);
            top: 40%; left: 30%;
            animation-delay: -3s;
        }
        @keyframes orbDrift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(30px, 20px) scale(1.08); }
        }

        /* Subtle grid overlay */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .left-top {
            position: relative;
            z-index: 2;
        }
        .left-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .left-logo img { height: 32px; }
        .left-logo-name {
            font-size: .85rem;
            font-weight: 700;
            letter-spacing: .04em;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
        }

        .left-center {
            position: relative;
            z-index: 2;
        }
        .left-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: 28px;
        }
        .eyebrow-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: blink 2s infinite;
        }
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.2;} }

        .left-headline {
            font-size: clamp(2.8rem, 4vw, 4.2rem);
            font-weight: 900;
            line-height: 1.06;
            letter-spacing: -.04em;
            margin-bottom: 24px;
        }
        .left-headline .word-light { color: var(--white); }
        .left-headline .word-dim   { color: rgba(255,255,255,.28); }
        .left-headline .word-accent {
            background: linear-gradient(135deg, #4F6EF7, #A78BFA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .left-sub {
            font-size: 1rem;
            color: rgba(255,255,255,.38);
            line-height: 1.7;
            max-width: 420px;
        }

        /* Stats row */
        .stats-row {
            display: flex;
            gap: 32px;
            margin-top: 52px;
        }
        .stat-item {}
        .stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--white);
            line-height: 1;
        }
        .stat-label {
            font-size: .75rem;
            color: rgba(255,255,255,.3);
            font-weight: 500;
            margin-top: 4px;
        }
        .stat-divider {
            width: 1px;
            background: var(--border-d);
            align-self: stretch;
        }

        /* Social proof avatars */
        .proof-chip {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border-d);
            border-radius: 100px;
            padding: 8px 16px 8px 8px;
            margin-top: 48px;
        }
        .proof-avatars { display: flex; }
        .proof-avatars span {
            width: 28px; height: 28px;
            border-radius: 50%;
            border: 2px solid var(--black);
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem;
            font-weight: 800;
            margin-left: -8px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }
        .proof-avatars span:first-child { margin-left: 0; }
        .proof-text {
            font-size: .78rem;
            color: rgba(255,255,255,.5);
        }
        .proof-text strong { color: var(--white); font-weight: 700; }

        .left-bottom {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .left-footer-links { display: flex; gap: 20px; }
        .left-footer-links a {
            font-size: .72rem;
            color: rgba(255,255,255,.2);
            text-decoration: none;
            font-weight: 500;
            transition: color .2s;
        }
        .left-footer-links a:hover { color: rgba(255,255,255,.5); }
        .left-footer-copy {
            font-size: .72rem;
            color: rgba(255,255,255,.15);
            font-weight: 500;
        }

        /* ─── RIGHT PANEL ────────────────────────────────────────────── */
        .panel-right {
            width: 460px;
            flex-shrink: 0;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px 48px;
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .right-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .right-logo img { height: 28px; }

        .form-eyebrow {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 6px;
        }
        .form-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f0f0f;
            letter-spacing: -.03em;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .form-subtitle {
            font-size: .85rem;
            color: var(--muted);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: .83rem;
            font-weight: 500;
            margin-bottom: 14px;
        }
        .alert-error  { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        /* Form */
        .field { margin-bottom: 14px; }
        .field label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .field-wrap { position: relative; }
        .field-wrap input {
            width: 100%;
            padding: 11px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: .95rem;
            font-family: inherit;
            color: #111827;
            background: #fafafa;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
            -webkit-appearance: none;
        }
        .field-wrap input:focus {
            border-color: var(--accent);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(79,110,247,.1);
        }
        .field-wrap input::placeholder { color: #9ca3af; }

        /* Password toggle */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color .2s;
        }
        .toggle-pw:hover { color: #374151; }
        .field-wrap input[type="password"],
        .field-wrap input[type="text"] { padding-right: 44px; }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 7px;
        }
        .field-row label { margin-bottom: 0; }
        .forgot-link {
            font-size: .78rem;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: opacity .2s;
        }
        .forgot-link:hover { opacity: .7; }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            border: 1.5px solid #d1d5db;
            border-radius: 4px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .remember-row label {
            font-size: .83rem;
            color: #6b7280;
            cursor: pointer;
            user-select: none;
        }

        /* CTA Button */
        .btn-login {
            width: 100%;
            padding: 13px 24px;
            background: #0f0f0f;
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: .95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .2s, transform .15s, box-shadow .2s;
            letter-spacing: -.01em;
        }
        .btn-login:hover {
            background: #1f1f1f;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login .arrow {
            width: 20px; height: 20px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
            font-size: .7rem;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 14px 0;
            color: #d1d5db;
            font-size: .78rem;
            font-weight: 600;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        /* Register row */
        .register-row {
            text-align: center;
            font-size: .85rem;
            color: #9ca3af;
            margin-top: 14px;
        }
        .register-row a {
            color: #0f0f0f;
            font-weight: 700;
            text-decoration: none;
            transition: opacity .2s;
        }
        .register-row a:hover { opacity: .6; }

        /* Lang bar */
        .lang-bar {
            display: flex;
            gap: 4px;
            margin-top: 20px;
        }
        .lang-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 600;
            color: #9ca3af;
            background: none;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }
        .lang-btn.active {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #374151;
        }
        .lang-btn:hover:not(.active) { background: #f9fafb; color: #6b7280; }
        .lang-btn img { width: 16px; height: 16px; border-radius: 50%; object-fit: cover; }

        /* ─── RESPONSIVE ─────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .panel-left { display: none; }
            .panel-right {
                width: 100%;
                height: 100vh;
                padding: 32px 28px;
                overflow-y: auto;
            }
        }
        @media (max-width: 400px) {
            .panel-right { padding: 28px 20px; }
        }
    </style>
</head>
<body>

    <!-- LEFT PANEL -->
    <div class="panel-left">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="grid-overlay"></div>

        <div class="left-top">
            <div class="left-logo">
                <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
            </div>
        </div>

        <div class="left-center">
            <div class="left-eyebrow">
                <span class="eyebrow-dot"></span>
                Plataforma de Impacto Social
            </div>

            <h1 class="left-headline">
                <span class="word-light">Gestão que</span><br>
                <span class="word-accent">transforma</span><br>
                <span class="word-dim">vidas em escala.</span>
            </h1>

            <p class="left-sub">
                O centro de comando da sua organização. Projetos, doações, parcerias e captação de recursos — tudo em um só lugar.
            </p>

            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value">2.4k+</div>
                    <div class="stat-label">Organizações ativas</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">R$ 18M</div>
                    <div class="stat-label">Captados em 2025</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">98%</div>
                    <div class="stat-label">Satisfação</div>
                </div>
            </div>

            <div class="proof-chip">
                <div class="proof-avatars">
                    <span>A</span>
                    <span>C</span>
                    <span>M</span>
                    <span>+</span>
                </div>
                <span class="proof-text"><strong>+2.400 gestores</strong> já usam a plataforma</span>
            </div>
        </div>

        <div class="left-bottom">
            <div class="left-footer-links">
                <a href="#">Privacidade</a>
                <a href="#">Termos</a>
                <a href="#">Suporte</a>
            </div>
            <span class="left-footer-copy">© 2026 Vivensi</span>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="panel-right">

        <div class="right-logo">
            <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
        </div>

        <div class="form-eyebrow">Bem-vindo de volta</div>
        <h2 class="form-title">Entre na sua conta</h2>
        <p class="form-subtitle">Use suas credenciais para acessar o painel.</p>

        @if(session('error'))
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST" id="loginForm">
            @csrf

            <div class="field">
                <label for="email">E-mail</label>
                <div class="field-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seu@email.com"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <div class="field">
                <div class="field-row">
                    <label for="password">Senha</label>
                    <a href="{{ route('password.request') }}" class="forgot-link">Esqueceu?</a>
                </div>
                <div class="field-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-pw" id="togglePw" aria-label="Mostrar senha">
                        <i class="far fa-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Manter-me conectado</label>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Entrar na plataforma
                <span class="arrow"><i class="fas fa-arrow-right"></i></span>
            </button>
        </form>

        <div class="divider">ou</div>

        <div class="register-row">
            Quer fazer parte? <a href="{{ route('register') }}">Entrar na lista de espera</a>
        </div>

        <div class="lang-bar">
            <a href="#" class="lang-btn active">
                <img src="https://flagcdn.com/w40/br.png" alt="PT"> Português
            </a>
            <a href="#" class="lang-btn">
                <img src="https://flagcdn.com/w40/es.png" alt="ES"> Español
            </a>
        </div>

    </div>

<script>
    // Password toggle
    document.getElementById('togglePw').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('pwIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'far fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'far fa-eye';
        }
    });

    // Loading state on submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
    });
</script>

</body>
</html>
