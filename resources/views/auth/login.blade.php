@php
    try {
        $statOrgs  = \App\Models\Tenant::count();
        $statUsers = \App\Models\User::whereIn('role', ['manager', 'employee'])->count();
    } catch (\Throwable $e) {
        $statOrgs  = 0;
        $statUsers = 0;
    }

    function fmtStat(int $n): string {
        if ($n >= 1000) return number_format($n / 1000, 1, '.', '') . 'k+';
        if ($n > 0)     return $n . '+';
        return '—';
    }
@endphp
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
            --black:    #080808;
            --dark:     #111111;
            --border-d: rgba(255,255,255,.07);
            --white:    #ffffff;
            --muted:    #999999;
            --accent:   #4F6EF7;
            --accent2:  #7B5CF0;
            --green:    #22c55e;
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

        /* ── LEFT PANEL ───────────────────────────────────────────────── */
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

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            animation: orbDrift 12s ease-in-out infinite alternate;
        }
        .orb-1 { width:500px;height:500px;background:radial-gradient(circle,rgba(79,110,247,.18) 0%,transparent 70%);top:-100px;left:-100px; }
        .orb-2 { width:400px;height:400px;background:radial-gradient(circle,rgba(123,92,240,.14) 0%,transparent 70%);bottom:-80px;right:-80px;animation-delay:-6s; }
        .orb-3 { width:300px;height:300px;background:radial-gradient(circle,rgba(34,197,94,.08) 0%,transparent 70%);top:40%;left:30%;animation-delay:-3s; }
        @keyframes orbDrift { from{transform:translate(0,0) scale(1);} to{transform:translate(30px,20px) scale(1.08);} }

        .grid-overlay {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .left-top { position: relative; z-index: 2; }
        .left-logo { display: flex; align-items: center; gap: 10px; }
        .left-logo img { height: 32px; }

        .left-center { position: relative; z-index: 2; }

        .left-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--accent); font-size: .75rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase; margin-bottom: 28px;
        }
        .eyebrow-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--green); animation: blink 2s infinite;
        }
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.2;} }

        .left-headline {
            font-size: clamp(2.8rem, 4vw, 4.2rem);
            font-weight: 900; line-height: 1.06;
            letter-spacing: -.04em; margin-bottom: 24px;
        }
        .word-light  { color: var(--white); }
        .word-dim    { color: rgba(255,255,255,.28); }
        .word-accent {
            background: linear-gradient(135deg, #4F6EF7, #A78BFA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .left-sub {
            font-size: 1rem; color: rgba(255,255,255,.38);
            line-height: 1.7; max-width: 420px; margin-bottom: 32px;
        }

        /* Feature pills */
        .feature-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 44px; }
        .feature-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 14px; border-radius: 100px;
            border: 1px solid rgba(255,255,255,.1);
            background: rgba(255,255,255,.04);
            font-size: .75rem; font-weight: 600;
            color: rgba(255,255,255,.55);
            white-space: nowrap;
        }
        .feature-pill i { font-size: .65rem; }
        .pill-blue  i { color: #4F6EF7; }
        .pill-green i { color: #22c55e; }
        .pill-purple i { color: #a78bfa; }
        .pill-amber i { color: #fbbf24; }

        /* Stats row */
        .stats-row { display: flex; gap: 32px; margin-bottom: 40px; }
        .stat-value {
            font-size: 1.6rem; font-weight: 800;
            letter-spacing: -.02em; color: var(--white); line-height: 1;
        }
        .stat-label { font-size: .75rem; color: rgba(255,255,255,.3); font-weight: 500; margin-top: 4px; }
        .stat-divider { width: 1px; background: var(--border-d); align-self: stretch; }

        /* Social proof */
        .proof-chip {
            display: inline-flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border-d);
            border-radius: 100px;
            padding: 8px 16px 8px 8px;
        }
        .proof-avatars { display: flex; }
        .proof-avatars span {
            width: 28px; height: 28px; border-radius: 50%;
            border: 2px solid var(--black);
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; font-weight: 800;
            margin-left: -8px;
        }
        .proof-avatars span:first-child { margin-left: 0; }
        .av-1 { background: linear-gradient(135deg, #4F6EF7, #6366f1); }
        .av-2 { background: linear-gradient(135deg, #10b981, #059669); }
        .av-3 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .av-4 { background: linear-gradient(135deg, #ec4899, #db2777); }
        .proof-text { font-size: .78rem; color: rgba(255,255,255,.5); }
        .proof-text strong { color: var(--white); font-weight: 700; }

        .left-bottom {
            position: relative; z-index: 2;
            display: flex; align-items: center; justify-content: space-between;
        }
        .left-footer-links { display: flex; gap: 20px; }
        .left-footer-links a {
            font-size: .72rem; color: rgba(255,255,255,.2);
            text-decoration: none; font-weight: 500; transition: color .2s;
        }
        .left-footer-links a:hover { color: rgba(255,255,255,.5); }
        .left-footer-copy { font-size: .72rem; color: rgba(255,255,255,.15); font-weight: 500; }

        /* ── RIGHT PANEL ──────────────────────────────────────────────── */
        .panel-right {
            width: 460px; flex-shrink: 0;
            background: var(--white);
            display: flex; flex-direction: column; justify-content: center;
            padding: 32px 48px;
            position: relative; height: 100vh; overflow: hidden;
        }

        .right-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .right-logo img { height: 28px; }

        .form-eyebrow {
            font-size: .72rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: var(--accent); margin-bottom: 6px;
        }
        .form-title {
            font-size: 1.55rem; font-weight: 800;
            color: #0f0f0f; letter-spacing: -.03em;
            line-height: 1.2; margin-bottom: 4px;
        }
        .form-subtitle { font-size: .85rem; color: var(--muted); margin-bottom: 20px; line-height: 1.5; }

        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 10px;
            font-size: .83rem; font-weight: 500; margin-bottom: 14px;
        }
        .alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        .field { margin-bottom: 14px; }
        .field label {
            display: block; font-size: .8rem;
            font-weight: 600; color: #374151; margin-bottom: 6px;
        }
        .field-wrap { position: relative; }
        .field-wrap input {
            width: 100%; padding: 11px 16px;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            font-size: .95rem; font-family: inherit;
            color: #111827; background: #fafafa;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none; -webkit-appearance: none;
        }
        .field-wrap input:focus {
            border-color: var(--accent); background: var(--white);
            box-shadow: 0 0 0 4px rgba(79,110,247,.1);
        }
        .field-wrap input::placeholder { color: #9ca3af; }

        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #9ca3af; padding: 4px;
            display: flex; align-items: center; transition: color .2s;
        }
        .toggle-pw:hover { color: #374151; }
        .field-wrap input[type="password"],
        .field-wrap input[type="text"] { padding-right: 44px; }

        .field-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; }
        .field-row label { margin-bottom: 0; }
        .forgot-link {
            font-size: .78rem; color: var(--accent);
            text-decoration: none; font-weight: 600; transition: opacity .2s;
        }
        .forgot-link:hover { opacity: .7; }

        .remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            border: 1.5px solid #d1d5db; border-radius: 4px;
            accent-color: var(--accent); cursor: pointer;
        }
        .remember-row label { font-size: .83rem; color: #6b7280; cursor: pointer; user-select: none; }

        .btn-login {
            width: 100%; padding: 13px 24px;
            background: #0f0f0f; color: var(--white);
            border: none; border-radius: 12px;
            font-size: .95rem; font-weight: 700; font-family: inherit;
            cursor: pointer; display: flex; align-items: center;
            justify-content: center; gap: 10px;
            transition: background .2s, transform .15s, box-shadow .2s;
            letter-spacing: -.01em;
        }
        .btn-login:hover { background: #1f1f1f; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,.18); }
        .btn-login:active { transform: translateY(0); }
        .btn-login .arrow {
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center; font-size: .7rem;
        }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 14px 0; color: #d1d5db; font-size: .78rem; font-weight: 600;
        }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }

        .register-row { text-align: center; font-size: .85rem; color: #9ca3af; margin-top: 14px; }
        .register-row a { color: #0f0f0f; font-weight: 700; text-decoration: none; transition: opacity .2s; }
        .register-row a:hover { opacity: .6; }

        /* ── RESPONSIVE ───────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; height: 100vh; padding: 32px 28px; overflow-y: auto; }
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

        <div class="feature-pills">
            <span class="feature-pill pill-blue"><i class="fas fa-chart-line"></i> Gestão financeira</span>
            <span class="feature-pill pill-green"><i class="fas fa-hands-holding-heart"></i> Captação de doações</span>
            <span class="feature-pill pill-purple"><i class="fas fa-robot"></i> IA integrada</span>
            <span class="feature-pill pill-amber"><i class="fas fa-file-contract"></i> Contratos digitais</span>
            <span class="feature-pill pill-blue"><i class="fab fa-whatsapp"></i> WhatsApp CRM</span>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value">{{ fmtStat($statOrgs) }}</div>
                <div class="stat-label">Organizações</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-value">{{ fmtStat($statUsers) }}</div>
                <div class="stat-label">Gestores ativos</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Nuvem segura</div>
            </div>
        </div>

        <div class="proof-chip">
            <div class="proof-avatars">
                <span class="av-1">JL</span>
                <span class="av-2">MA</span>
                <span class="av-3">RS</span>
                <span class="av-4">CF</span>
            </div>
            <span class="proof-text">
                Organizações reais usando a plataforma <strong>agora</strong>
            </span>
        </div>
    </div>

    <div class="left-bottom">
        <div class="left-footer-links">
            <a href="{{ route('legal.privacy') }}">Privacidade</a>
            <a href="{{ route('legal.terms') }}">Termos</a>
            <a href="mailto:suporte@vivensi.app.br">Suporte</a>
        </div>
        <span class="left-footer-copy">© {{ date('Y') }} Vivensi</span>
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
    @if($errors->any())
        <div class="alert alert-error">
            <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ url('/login') }}" method="POST" id="loginForm">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <div class="field-wrap">
                <input type="email" id="email" name="email"
                       placeholder="seu@email.com"
                       value="{{ old('email') }}"
                       autocomplete="email" required>
            </div>
        </div>

        <div class="field">
            <div class="field-row">
                <label for="password">Senha</label>
                <a href="{{ route('password.request') }}" class="forgot-link">Esqueceu?</a>
            </div>
            <div class="field-wrap">
                <input type="password" id="password" name="password"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
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

</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    var input = document.getElementById('password');
    var icon  = document.getElementById('pwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'far fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'far fa-eye';
    }
});

document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
});
</script>

</body>
</html>
