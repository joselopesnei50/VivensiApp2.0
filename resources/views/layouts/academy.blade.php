<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '493025661075925');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=493025661075925&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vivensi Academy &mdash; @yield('title', 'Plataforma de Ensino')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0f1e;
            color: #e2e8f0;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── NAVBAR ─────────────────────────────────── */
        .acad-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 68px;
            background: rgba(10, 15, 30, 0.92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.18);
            z-index: 1100;
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 0;
        }

        /* Logo */
        .acad-logo {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            flex-shrink: 0;
        }
        .acad-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,.45);
            flex-shrink: 0;
        }
        .acad-logo-text {
            display: flex; flex-direction: column; line-height: 1;
        }
        .acad-logo-text span:first-child {
            font-size: .65rem; font-weight: 700; letter-spacing: 2.5px;
            color: #818cf8; text-transform: uppercase;
        }
        .acad-logo-text span:last-child {
            font-size: 1.1rem; font-weight: 800; color: #fff;
            letter-spacing: -.5px;
        }

        /* Divider */
        .acad-nav-sep {
            width: 1px; height: 28px;
            background: rgba(255,255,255,.1);
            margin: 0 24px;
            flex-shrink: 0;
        }

        /* Nav links */
        .acad-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
        }
        .acad-nav-links a {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: .88rem;
            padding: 7px 13px;
            border-radius: 8px;
            transition: all .2s;
            display: flex; align-items: center; gap: 7px;
            white-space: nowrap;
        }
        .acad-nav-links a:hover,
        .acad-nav-links a.active {
            color: #fff;
            background: rgba(99,102,241,.14);
        }
        .acad-nav-links a.active {
            color: #a5b4fc;
        }
        .acad-nav-links a .nav-icon { font-size: .82rem; opacity: .8; }

        /* Right section */
        .acad-nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Back to dashboard pill */
        .acad-btn-dash {
            display: flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: #cbd5e1;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            white-space: nowrap;
        }
        .acad-btn-dash:hover {
            background: rgba(255,255,255,.11);
            color: #fff;
        }

        /* User avatar + dropdown */
        .acad-user-menu { position: relative; }
        .acad-user-trigger {
            display: flex; align-items: center; gap: 10px;
            background: rgba(99,102,241,.12);
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 10px;
            padding: 6px 12px 6px 6px;
            cursor: pointer;
            transition: all .2s;
            user-select: none;
        }
        .acad-user-trigger:hover { background: rgba(99,102,241,.22); border-color: rgba(99,102,241,.45); }
        .acad-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .85rem; color: #fff;
            flex-shrink: 0;
        }
        .acad-user-info { display: flex; flex-direction: column; line-height: 1.2; }
        .acad-user-name { font-size: .82rem; font-weight: 700; color: #e2e8f0; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .acad-user-role { font-size: .7rem; color: #818cf8; font-weight: 500; }
        .acad-user-trigger .acad-caret { color: #64748b; font-size: .7rem; transition: transform .2s; }
        .acad-user-trigger.open .acad-caret { transform: rotate(180deg); }

        /* Dropdown */
        .acad-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 210px;
            background: #131929;
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0,0,0,.5);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all .2s;
            z-index: 200;
        }
        .acad-user-menu.open .acad-dropdown {
            opacity: 1; visibility: visible; transform: translateY(0);
        }
        .acad-dropdown-header {
            padding: 14px 16px 10px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .acad-dropdown-header p { font-size: .7rem; color: #64748b; margin: 0 0 2px; }
        .acad-dropdown-header strong { font-size: .88rem; color: #e2e8f0; }
        .acad-dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-size: .85rem;
            font-weight: 500;
            transition: all .15s;
        }
        .acad-dropdown-item:hover { background: rgba(99,102,241,.1); color: #fff; }
        .acad-dropdown-item i { width: 16px; text-align: center; font-size: .82rem; }
        .acad-dropdown-sep { height: 1px; background: rgba(255,255,255,.06); margin: 4px 0; }
        .acad-dropdown-item.danger { color: #f87171; }
        .acad-dropdown-item.danger:hover { background: rgba(248,113,113,.1); color: #fca5a5; }

        /* Mobile hamburger */
        .acad-hamburger {
            display: none;
            background: none; border: none;
            color: #94a3b8; font-size: 1.2rem;
            cursor: pointer; padding: 5px;
            margin-left: 8px;
        }
        .acad-mobile-menu {
            display: none;
            position: fixed;
            top: 68px; left: 0; right: 0;
            background: rgba(10,15,30,.98);
            border-bottom: 1px solid rgba(99,102,241,.18);
            padding: 16px;
            z-index: 1090;
        }
        .acad-mobile-menu.open { display: block; }
        .acad-mobile-menu a {
            display: flex; align-items: center; gap: 10px;
            color: #94a3b8; text-decoration: none;
            font-weight: 500; font-size: .9rem;
            padding: 12px 10px; border-radius: 8px;
            transition: all .2s;
        }
        .acad-mobile-menu a:hover { background: rgba(99,102,241,.12); color: #fff; }

        /* ─── CONTENT ─────────────────────────────────── */
        .academy-content {
            margin-top: 68px;
            flex: 1;
        }

        /* ─── FOOTER ─────────────────────────────────── */
        .acad-footer {
            background: #080d1c;
            border-top: 1px solid rgba(99,102,241,.15);
            padding: 52px 0 28px;
            margin-top: auto;
        }
        .acad-footer-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
        }
        .acad-footer-brand {}
        .acad-footer-logo {
            display: flex; align-items: center; gap: 11px;
            text-decoration: none; margin-bottom: 16px;
        }
        .acad-footer-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
        }
        .acad-footer-logo-text { font-size: 1.15rem; font-weight: 800; color: #fff; letter-spacing: -.5px; }
        .acad-footer-desc {
            color: #64748b; font-size: .85rem; line-height: 1.7;
            max-width: 280px; margin-bottom: 20px;
        }
        .acad-footer-badges {
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .acad-footer-badge {
            background: rgba(99,102,241,.12);
            border: 1px solid rgba(99,102,241,.2);
            color: #818cf8;
            font-size: .72rem; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            letter-spacing: .5px;
        }

        .acad-footer-col h5 {
            color: #e2e8f0; font-size: .82rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.2px;
            margin-bottom: 16px;
        }
        .acad-footer-col ul { list-style: none; }
        .acad-footer-col ul li { margin-bottom: 10px; }
        .acad-footer-col ul li a {
            color: #64748b; text-decoration: none;
            font-size: .85rem; font-weight: 500;
            transition: color .2s;
            display: flex; align-items: center; gap: 7px;
        }
        .acad-footer-col ul li a:hover { color: #a5b4fc; }
        .acad-footer-col ul li a i { font-size: .75rem; opacity: .7; }

        .acad-footer-bottom {
            max-width: 1200px; margin: 36px auto 0;
            padding: 20px 28px 0;
            border-top: 1px solid rgba(255,255,255,.05);
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
        }
        .acad-footer-bottom p { color: #334155; font-size: .8rem; margin: 0; }
        .acad-footer-bottom a { color: #4f46e5; text-decoration: none; }
        .acad-footer-stats {
            display: flex; gap: 24px;
        }
        .acad-footer-stat { text-align: center; }
        .acad-footer-stat span { display: block; }
        .acad-footer-stat .stat-num { font-size: 1.1rem; font-weight: 800; color: #818cf8; }
        .acad-footer-stat .stat-lbl { font-size: .7rem; color: #475569; font-weight: 500; letter-spacing: .5px; }

        /* ─── RESPONSIVE ───────────────────────────────── */
        @media (max-width: 900px) {
            .acad-nav-links,
            .acad-nav-sep,
            .acad-btn-dash { display: none; }
            .acad-hamburger { display: block; }
            .acad-footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 576px) {
            .acad-navbar { padding: 0 16px; }
            .acad-user-info { display: none; }
            .acad-footer-grid { grid-template-columns: 1fr; }
            .acad-footer-stats { gap: 16px; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ═══ TOP NAVBAR ═══ --}}
<nav class="acad-navbar">

    {{-- Logo --}}
    <a href="{{ route('academy.index') }}" class="acad-logo">
        <div class="acad-logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="acad-logo-text">
            <span>Vivensi</span>
            <span>Academy</span>
        </div>
    </a>

    {{-- Desktop separator --}}
    <div class="acad-nav-sep"></div>

    {{-- Nav links --}}
    <div class="acad-nav-links">
        <a href="{{ route('academy.index') }}"
           class="{{ request()->routeIs('academy.index') ? 'active' : '' }}">
            <i class="fas fa-th-large nav-icon"></i> Meus Cursos
        </a>
        <a href="{{ route('academy.certificates') }}"
           class="{{ request()->routeIs('academy.certificates') ? 'active' : '' }}">
            <i class="fas fa-award nav-icon"></i> Certificados
        </a>
        <a href="#" onclick="return false;" style="cursor:default; opacity:.45;" title="Em breve">
            <i class="fas fa-chart-line nav-icon"></i> Progresso
        </a>
    </div>

    {{-- Right side --}}
    <div class="acad-nav-right">

        {{-- Back to Dashboard --}}
        <a href="{{ url('/dashboard') }}" class="acad-btn-dash">
            <i class="fas fa-arrow-left" style="font-size:.75rem;"></i>
            Dashboard
        </a>

        {{-- User menu --}}
        <div class="acad-user-menu" id="acadUserMenu">
            <div class="acad-user-trigger" onclick="toggleAcadMenu()" id="acadUserTrigger">
                <div class="acad-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                <div class="acad-user-info">
                    <span class="acad-user-name">{{ explode(' ', auth()->user()->name ?? 'Usuário')[0] }}</span>
                    <span class="acad-user-role">Aluno</span>
                </div>
                <i class="fas fa-chevron-down acad-caret"></i>
            </div>

            <div class="acad-dropdown" id="acadDropdown">
                <div class="acad-dropdown-header">
                    <p>Logado como</p>
                    <strong>{{ auth()->user()->name ?? 'Usuário' }}</strong>
                </div>
                <a href="{{ url('/profile') }}" class="acad-dropdown-item">
                    <i class="fas fa-user-circle"></i> Meu Perfil
                </a>
                <a href="{{ route('academy.index') }}" class="acad-dropdown-item">
                    <i class="fas fa-th-large"></i> Meus Cursos
                </a>
                <a href="{{ route('academy.certificates') }}" class="acad-dropdown-item">
                    <i class="fas fa-award"></i> Meus Certificados
                </a>
                <a href="{{ url('/dashboard') }}" class="acad-dropdown-item">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>
                <div class="acad-dropdown-sep"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="acad-dropdown-item danger w-100 text-start border-0 bg-transparent">
                        <i class="fas fa-right-from-bracket"></i> Sair
                    </button>
                </form>
            </div>
        </div>

        {{-- Mobile hamburger --}}
        <button class="acad-hamburger" onclick="toggleMobileMenu()" aria-label="Menu">
            <i class="fas fa-bars" id="hambIcon"></i>
        </button>
    </div>
</nav>

{{-- Mobile menu --}}
<div class="acad-mobile-menu" id="acadMobileMenu">
    <a href="{{ route('academy.index') }}"><i class="fas fa-th-large"></i> Meus Cursos</a>
    <a href="{{ route('academy.certificates') }}"><i class="fas fa-award"></i> Meus Certificados</a>
    <a href="{{ url('/dashboard') }}"><i class="fas fa-gauge-high"></i> Dashboard</a>
    <a href="{{ url('/profile') }}"><i class="fas fa-user-circle"></i> Perfil</a>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
        @csrf
        <button type="submit" style="width:100%; text-align:left; background:none; border:none; display:flex; align-items:center; gap:10px; color:#f87171; font-size:.9rem; font-weight:500; padding:12px 10px; border-radius:8px; cursor:pointer;">
            <i class="fas fa-right-from-bracket"></i> Sair
        </button>
    </form>
</div>

{{-- ═══ PAGE CONTENT ═══ --}}
<main class="academy-content">
    @yield('content')
</main>

{{-- ═══ FOOTER ═══ --}}
<footer class="acad-footer">
    <div class="acad-footer-grid">

        {{-- Brand col --}}
        <div class="acad-footer-brand">
            <a href="{{ route('academy.index') }}" class="acad-footer-logo">
                <div class="acad-footer-logo-icon"><i class="fas fa-graduation-cap" style="color:#fff;font-size:.95rem;"></i></div>
                <span class="acad-footer-logo-text">Vivensi Academy</span>
            </a>
            <p class="acad-footer-desc">
                Capacitação especializada para gestores, colaboradores e voluntários do Terceiro Setor.
                Aprenda no seu ritmo e conquiste certificados reconhecidos.
            </p>
            <div class="acad-footer-badges">
                <span class="acad-footer-badge">CERTIFICADO</span>
                <span class="acad-footer-badge">GRATUITO</span>
                <span class="acad-footer-badge">ONLINE</span>
            </div>
        </div>

        {{-- Plataforma --}}
        <div class="acad-footer-col">
            <h5>Plataforma</h5>
            <ul>
                <li><a href="{{ route('academy.index') }}"><i class="fas fa-th-large"></i> Meus Cursos</a></li>
                <li><a href="{{ url('/profile') }}"><i class="fas fa-user-circle"></i> Meu Perfil</a></li>
                <li><a href="{{ url('/dashboard') }}"><i class="fas fa-gauge-high"></i> Dashboard</a></li>
            </ul>
        </div>

        {{-- Recursos --}}
        <div class="acad-footer-col">
            <h5>Recursos</h5>
            <ul>
                <li><a href="{{ route('academy.certificates') }}"><i class="fas fa-award"></i> Certificados</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Progresso</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Suporte</a></li>
            </ul>
        </div>

        {{-- Legal --}}
        <div class="acad-footer-col">
            <h5>Legal</h5>
            <ul>
                <li><a href="{{ route('legal.terms') }}"><i class="fas fa-file-lines"></i> Termos de Uso</a></li>
                <li><a href="{{ route('legal.privacy') }}"><i class="fas fa-shield-halved"></i> Privacidade</a></li>
                <li><a href="#"><i class="fas fa-cookie-bite"></i> Cookies</a></li>
            </ul>
        </div>

    </div>

    <div class="acad-footer-bottom">
        <p>&copy; {{ date('Y') }} <a href="#">Vivensi</a>. Todos os direitos reservados.</p>

        <div class="acad-footer-stats">
            <div class="acad-footer-stat">
                <span class="stat-num"><i class="fas fa-book-open" style="font-size:.85rem;"></i></span>
                <span class="stat-lbl">Cursos</span>
            </div>
            <div class="acad-footer-stat">
                <span class="stat-num"><i class="fas fa-users" style="font-size:.85rem;"></i></span>
                <span class="stat-lbl">Alunos</span>
            </div>
            <div class="acad-footer-stat">
                <span class="stat-num"><i class="fas fa-certificate" style="font-size:.85rem;"></i></span>
                <span class="stat-lbl">Certificados</span>
            </div>
        </div>

        <p style="color:#1e3a5f;">
            Desenvolvido com <i class="fas fa-heart" style="color:#4f46e5;font-size:.75rem;"></i> pela <a href="#">Vivensi</a>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleAcadMenu() {
        const menu = document.getElementById('acadUserMenu');
        const trigger = document.getElementById('acadUserTrigger');
        menu.classList.toggle('open');
        trigger.classList.toggle('open');
    }

    function toggleMobileMenu() {
        const m = document.getElementById('acadMobileMenu');
        const icon = document.getElementById('hambIcon');
        m.classList.toggle('open');
        icon.className = m.classList.contains('open') ? 'fas fa-times' : 'fas fa-bars';
    }

    // Close user dropdown on outside click
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('acadUserMenu');
        if (menu && !menu.contains(e.target)) {
            menu.classList.remove('open');
            document.getElementById('acadUserTrigger')?.classList.remove('open');
        }
    });
</script>
@stack('scripts')
</body>
</html>
