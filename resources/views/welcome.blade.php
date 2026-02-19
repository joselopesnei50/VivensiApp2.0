<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi - Gestão Inteligente para ONGs e Projetos</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logovivensi.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logovivensi.png') }}">
    
    <style>
        :root {
            --primary: #4f46e5;       
            --primary-dark: #4338ca;
            --secondary: #0f172a;
            --accent: #38bdf8;
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            
            /* Material 3 Palette */
            --m3-surface: #FDFBFF;
            --m3-surface-container: #F0F4F9;
            --m3-primary-container: #E0E7FF;
            --m3-on-primary-container: #001D35;
            --m3-ease-out: cubic-bezier(0.2, 0.0, 0, 1.0);
            --m3-ease-in-out: cubic-bezier(0.2, 0.0, 0.2, 1.0);
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            background: white;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            position: fixed;
            width: 90%;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i { color: var(--primary); }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-light);
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover { color: var(--primary); }

        .btn-cta {
            background: var(--primary);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            background: var(--primary-dark);
        }

        .btn-outline {
            border: 2px solid #e2e8f0;
            color: var(--text-main);
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Hero Old Removed */

        /* Features */
        .features {
            padding: 100px 5%;
            background: var(--bg-light);
            text-align: center;
        }

        .section-header {
            margin-bottom: 60px;
        }

        .section-badge {
            background: #dbeafe;
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: left;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.05);
            border-color: #e2e8f0;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e0e7ff 0%, #cbd5e1 100%);
            color: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 25px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover .feature-icon {
            background: var(--primary);
            color: white;
            transform: rotateY(180deg);
        }

        .feature-tag {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 15px;
            display: inline-block;
        }

        .tag-ngo { background: #fee2e2; color: #991b1b; }
        .tag-manager { background: #dcfce7; color: #166534; }
        .tag-common { background: #e0e7ff; color: #3730a3; }

        /* Pricing */
        .pricing {
            padding: 100px 5%;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .price-card {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .price-card.featured {
            border-color: var(--primary);
            background: #fafafa;
            transform: scale(1.05);
            box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.15);
        }

        .price { font-size: 3rem; font-weight: 800; color: var(--secondary); margin: 20px 0; }
        .period { font-size: 1rem; color: var(--text-light); font-weight: 400; }

        .check-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
            text-align: left;
        }

        .check-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
        }

        .check-list i { color: var(--success); }

        /* Footer */
        footer {
            background: var(--secondary);
            color: #94a3b8;
            padding: 80px 5%;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .footer-brand { color: white; font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; display: block; }

        @media (max-width: 768px) {
            .hero { grid-template-columns: 1fr; text-align: center; padding-top: 120px; }
            .hero-content p { margin: 0 auto 40px; }
            .nav-links { display: none; }
            .price-card.featured { transform: scale(1); }
            .footer-grid { grid-template-columns: 1fr; }
            
            /* FIX: Video Container Responsiveness */
            .col-video { min-width: 100% !important; }
            .col-text { min-width: 100% !important; }
            .ecosistema-section .row { gap: 40px !important; }
        }
    </style>
</head>
<body>

    <!-- Top Trial Banner -->
    <div style="background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%); color: white; padding: 10px 5%; text-align: center; font-size: 0.9rem; font-weight: 700; position: relative; z-index: 1001; margin-top: 80px;">
        🚀 OFERTA ESPECIAL: Experimente qualquer plano por 7 dias grátis. <a href="{{ route('register') }}" style="color: #60a5fa; text-decoration: underline; margin-left: 10px;">Começar Teste Agora</a>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="{{ url('/') }}" class="logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 40px;">
        </a>

        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links" id="navLinks">
            <!-- Mobile Header inside Menu -->
            <div class="mobile-menu-header">
                <span style="font-weight: 800; font-size: 1.2rem; color: var(--secondary);">Menu</span>
                <button onclick="toggleMobileMenu()" style="background: none; border: none; font-size: 1.5rem; color: var(--text-light); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <a href="{{ route('solutions.ngo') }}">ONGs</a>
            <a href="{{ route('solutions.manager') }}">Gestores</a>
            <a href="{{ route('solutions.common') }}">Pessoal</a>
            <a href="#pricing">Preços</a>
            
            <!-- Mobile Only Actions -->
            <div class="mobile-actions">
                <a href="{{ route('login') }}" class="btn-outline" style="text-align: center;">Entrar</a>
                <a href="#pricing" class="btn-cta" style="text-align: center;">Começar Agora</a>
            </div>
        </div>

        <div class="desktop-actions" style="display: flex; gap: 15px;">
            <a href="{{ route('login') }}" class="btn-outline">Entrar</a>
            <a href="#pricing" class="btn-cta">Começar Agora</a>
        </div>
    </nav>

    <!-- Overlay for Mobile Menu -->
    <div class="menu-overlay" id="menuOverlay" onclick="toggleMobileMenu()"></div>

    <style>
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; color: var(--secondary); cursor: pointer; }
        .mobile-menu-header { display: none; }
        .mobile-actions { display: none; }
        .menu-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px); opacity: 0; transition: opacity 0.3s; }
        .menu-overlay.active { display: block; opacity: 1; }

        @media (max-width: 768px) {
            .mobile-menu-btn { display: block; }
            .desktop-actions { display: none !important; }
            
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                background: white;
                flex-direction: column;
                padding: 20px;
                box-shadow: -10px 0 30px rgba(0,0,0,0.1);
                transition: right 0.3s ease;
                z-index: 1001;
                display: flex !important;
                gap: 20px;
            }

            .nav-links.active { right: 0; }

            .mobile-menu-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 15px;
            }

            .nav-links a {
                font-size: 1.1rem;
                padding: 10px 0;
                border-bottom: 1px solid #f8fafc;
                display: block;
            }

            .mobile-actions {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: 20px;
            }
        }
    </style>

    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('navLinks');
            const overlay = document.getElementById('menuOverlay');
            nav.classList.toggle('active');
            
            if (nav.classList.contains('active')) {
                overlay.style.display = 'block';
                setTimeout(() => overlay.style.opacity = '1', 10);
                document.body.style.overflow = 'hidden';
            } else {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.style.display = 'none', 300);
                document.body.style.overflow = 'auto';
            }
        }
    </script>

    <!-- Hero Material 3 -->
    <section class="hero-m3" id="hero">
        <div class="hero-m3-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
            <div class="blob blob-accent"></div>
        </div>

        <div class="hero-m3-grid">
            <!-- Content Layer -->
            <div class="hero-m3-content">
                <div class="m3-badge fade-up" style="animation-delay: 0.1s">
                    <i class="fas fa-sparkles"></i> 
                    <span>Gestão Inteligente v2.0</span>
                </div>
                
                <h1 class="hero-display fade-up" style="animation-delay: 0.2s">
                    Transforme sua <br>
                    <span class="gradient-text">visão de mundo</span> <br>
                    em realidade.
                </h1>
                
                <p class="hero-body fade-up" style="animation-delay: 0.3s">
                    A plataforma definitiva que une ONGs, Gestores e Finanças Pessoais. 
                    Simples, transparente e impulsionada por IA.
                </p>

                <div class="hero-actions fade-up" style="animation-delay: 0.4s">
                    <a href="{{ route('register') }}" class="btn-m3-primary">
                        Começar Agora
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#demo" class="btn-m3-tonal">
                        <i class="fas fa-play"></i>
                        Ver Demo (1 min)
                    </a>
                </div>

                <div class="hero-trust fade-up" style="animation-delay: 0.5s">
                    <div class="avatars">
                        <img src="https://i.pravatar.cc/100?img=1" alt="">
                        <img src="https://i.pravatar.cc/100?img=5" alt="">
                        <img src="https://i.pravatar.cc/100?img=8" alt="">
                    </div>
                    <span>Junte-se a <strong>500+ inovadores</strong></span>
                </div>
            </div>

            <!-- Visual Layer (Parallax) -->
            <div class="hero-m3-visual" id="parallaxScene">
                <!-- Base Card -->
                <div class="visual-card main-card" data-speed="20">
                    <div class="card-header">
                        <div class="card-dot red"></div>
                        <div class="card-dot yellow"></div>
                        <div class="card-dot green"></div>
                    </div>
                    <div class="card-body-mock">
                        <div class="mock-row shimer" style="width: 70%"></div>
                        <div class="mock-row shimer" style="width: 90%; height: 120px; margin-top: 20px"></div>
                        <div class="mock-row" style="display: flex; gap: 10px; margin-top: 20px">
                            <div class="shimer" style="flex: 1; height: 80px"></div>
                            <div class="shimer" style="flex: 1; height: 80px"></div>
                        </div>
                    </div>
                    
                    <!-- Floating Badge 1 -->
                    <div class="float-badge success" data-speed="40" style="top: -20px; right: -20px;">
                        <div class="icon-box"><i class="fas fa-arrow-up"></i></div>
                        <div>
                            <div class="label">Receita</div>
                            <div class="value">+24%</div>
                        </div>
                    </div>

                    <!-- Floating Badge 2 -->
                    <div class="float-badge info" data-speed="60" style="bottom: 40px; left: -40px;">
                        <div class="user-pill">
                            <img src="https://i.pravatar.cc/100?img=12" alt="">
                            <span>Novo Doador</span>
                        </div>
                    </div>

                    <!-- Floating Icons (New Premium Touch) -->
                    <div class="float-icon icon-1" data-speed="-30" style="top: -60px; left: 20px;">
                        <i class="fas fa-robot" style="color: #6366f1;"></i>
                    </div>
                    <div class="float-icon icon-2" data-speed="45" style="top: 100px; right: -50px;">
                        <i class="fab fa-whatsapp" style="color: #22c55e;"></i>
                    </div>
                    <div class="float-icon icon-3" data-speed="-20" style="bottom: -30px; right: 40px;">
                        <i class="fas fa-comment-dots" style="color: #ec4899;"></i>
                    </div>
                </div>

                <!-- Abstract Decor -->
                <div class="abstract-shape shape-1" data-speed="-15"></div>
            </div>
        </div>
    </section>

    <style>
        /* Hero M3 Styles */
        .hero-m3 {
            position: relative;
            padding: 80px 5% 100px; /* Reduced top padding significantly */
            overflow: hidden;
            background: #F8FAFC;
        }

        .hero-m3-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.6;
            pointer-events: none;
        }

        .blob {
            position: absolute;
            filter: blur(80px);
            opacity: 0.8;
            border-radius: 50%;
            animation: floatBlob 10s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95);
        }

        .blob-1 { top: -10%; right: -5%; width: 600px; height: 600px; background: #C7D2FE; animation-duration: 25s; opacity: 0.4; }
        .blob-2 { bottom: -10%; left: -10%; width: 500px; height: 500px; background: #E9D5FF; animation-duration: 30s; opacity: 0.4; }
        .blob-3 { top: 40%; right: 30%; width: 300px; height: 300px; background: #BAE6FD; animation-duration: 20s; opacity: 0.4; }
        .blob-accent { top: 10%; left: 15%; width: 500px; height: 500px; background: radial-gradient(circle, var(--primary) 0%, transparent 70%); opacity: 0.15; animation-duration: 18s; z-index: 1; }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -50px) scale(1.1); }
        }

        .hero-m3-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            max-width: 1400px;
            margin: 0 auto;
            align-items: center;
        }

        /* Typography */
        .m3-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 100px;
            color: var(--primary);
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .hero-display {
            font-size: 4rem;
            line-height: 1.1;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.03em;
            margin: 0 0 24px 0;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, #818CF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-body {
            font-size: 1.25rem;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 48px;
            max-width: 520px;
        }

        /* Actions */
        .hero-actions {
            display: flex;
            gap: 16px;
            margin-bottom: 48px;
        }

        .btn-m3-primary {
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            border-radius: 100px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s var(--m3-ease-out);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .btn-m3-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 35px -5px rgba(79, 70, 229, 0.5);
            background: var(--primary-dark);
        }

        .btn-m3-tonal {
            background: white;
            color: var(--text-main);
            padding: 16px 32px;
            border-radius: 100px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-m3-tonal:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }

        /* Trust */
        .hero-trust {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatars { display: flex; }
        .avatars img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid white;
            margin-left: -12px;
        }
        .avatars img:first-child { margin-left: 0; }

        .hero-trust span {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Visual & Parallax */
        .hero-m3-visual {
            position: relative;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 1000px;
        }

        .visual-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid white;
            border-radius: 28px;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255,255,255, 0.5) inset;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.1s cubic-bezier(0,0,0,1); /* Smooth lerp controlled by JS */
        }

        .main-card {
            width: 100%;
            max-width: 500px;
            padding: 24px;
            z-index: 10;
        }

        .card-header { display: flex; gap: 8px; margin-bottom: 24px; }
        .card-dot { width: 12px; height: 12px; border-radius: 50%; opacity: 0.5; }
        .red { background: #EF4444; } .yellow { background: #F59E0B; } .green { background: #10B981; }

        .shimer {
            background: linear-gradient(90deg, #F1F5F9 0%, #E2E8F0 50%, #F1F5F9 100%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
            border-radius: 12px;
        }
        
        .mock-row { height: 16px; margin-bottom: 12px; border-radius: 8px; }

        @keyframes shimmer { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }

        .float-badge {
            position: absolute;
            background: white;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            display: flex;
            gap: 12px;
            align-items: center;
            min-width: 160px;
            animation: float 6s ease-in-out infinite;
        }

        .float-badge.success .icon-box { background: #DCFCE7; color: #16A34A; }
        .float-badge.info .icon-box { background: #E0E7FF; color: #4F46E5; }
        
        .float-icon {
            position: absolute;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            z-index: 12;
            animation: float 5s ease-in-out infinite alternate;
        }

        .icon-1 { animation-delay: 0s; }
        .icon-2 { animation-delay: 1.5s; font-size: 1.8rem; }
        .icon-3 { animation-delay: 3s; }
        
        .icon-box {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }

        .label { font-size: 0.75rem; color: var(--text-light); font-weight: 600; }
        .value { font-size: 1.1rem; font-weight: 800; color: var(--text-main); }
        
        .user-pill { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--text-main); }
        .user-pill img { width: 32px; height: 32px; border-radius: 50%; }

        .abstract-shape {
            position: absolute;
            width: 300px;
            height: 300px;
            border: 2px solid rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            top: -50px;
            right: -50px;
            z-index: 0;
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s var(--m3-ease-out) forwards;
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1024px) {
            .hero-display { font-size: 3rem; }
            .hero-m3-visual { height: 400px; }
        }

        @media (max-width: 768px) {
            .hero-m3-grid { grid-template-columns: 1fr; text-align: center; gap: 40px; }
            .hero-display { font-size: 2.5rem; }
            .hero-body { margin: 0 auto 30px; }
            .hero-actions { justify-content: center; flexDirection: column; }
            .hero-trust { justify-content: center; }
            .hero-m3-visual { display: none; } /* Hide complex visual on mobile for performance */
            .hero-m3 { padding-top: 120px; }
        }
    </style>

    <script>
        document.addEventListener('mousemove', (e) => {
            const parallaxScene = document.getElementById('parallaxScene');
            if (window.innerWidth <= 768 || !parallaxScene) return;

            const x = (window.innerWidth - e.pageX * 2) / 100;
            const y = (window.innerHeight - e.pageY * 2) / 100;

            const elements = document.querySelectorAll('[data-speed]');
            
            elements.forEach(el => {
                const speed = el.getAttribute('data-speed');
                const xPos = x * speed / 10;
                const yPos = y * speed / 10;
                
                el.style.transform = `translate(${xPos}px, ${yPos}px)`;
            });
        });
    </script>


    <!-- Ecossistema & Vídeo Section -->
    <section id="ecosistema" class="ecosistema-section" style="padding: 100px 5%; background: white;">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div class="row align-items-center" style="display: flex; flex-wrap: wrap; gap: 60px; justify-content: center;">
                <!-- Texto Column -->
                <div class="col-text" style="flex: 1; min-width: 350px;">
                    <span class="section-badge" style="background: rgba(79, 70, 229, 0.1); color: var(--primary); padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">Conheça a Revolução</span>
                    <h2 style="font-size: 3rem; color: var(--secondary); margin: 25px 0; font-weight: 900; line-height: 1.1; letter-spacing: -1px;">O Ecossistema Vivensi</h2>
                    <p style="color: var(--text-light); font-size: 1.15rem; line-height: 1.8; margin-bottom: 30px;">
                        Mais do que uma ferramenta, o Vivensi é um <strong>organismo digital integrado</strong>. Unificamos o Terceiro Setor, o Gerenciamento de Projetos e as Finanças Pessoais em um único fluxo de inteligência.
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="background: #eef2ff; color: var(--primary); padding: 10px; border-radius: 12px;"><i class="fas fa-link"></i></div>
                            <div>
                                <h4 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 1rem;">Conectividade</h4>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 5px 0 0 0;">Tudo sincronizado em tempo real.</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="background: #f0fdf4; color: #16a34a; padding: 10px; border-radius: 12px;"><i class="fas fa-shield-alt"></i></div>
                            <div>
                                <h4 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 1rem;">Segurança</h4>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 5px 0 0 0;">Auditável e 100% transparente.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Column -->
                <div class="col-video" style="flex: 1.2; min-width: 400px; position: relative;">
                    <div style="background: #0f172a; border-radius: 24px; padding: 10px; box-shadow: 0 40px 80px -20px rgba(0,0,0,0.3); position: relative; overflow: hidden; aspect-ratio: 16/10;">
                        @if($videoUrl)
                            <iframe width="100%" height="100%" src="{{ $videoUrl }}" title="Vivensi Introduction" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="border-radius: 15px;"></iframe>
                        @else
                            <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 15px;">
                                <i class="fas fa-play-circle" style="font-size: 4rem; color: white; opacity: 0.2; margin-bottom: 20px;"></i>
                                <p style="color: white; opacity: 0.5; font-weight: 600;">Vídeo Institucional em Breve</p>
                            </div>
                        @endif
                    </div>
                    <!-- Decoracao -->
                    <div style="position: absolute; z-index: -1; top: -20px; right: -20px; width: 100px; height: 100px; background: radial-gradient(circle, var(--primary) 0%, transparent 70%); opacity: 0.3; filter: blur(20px);"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features" style="padding: 120px 5%;">
        <div class="section-header">
            <span class="section-badge" style="background: rgba(79, 70, 229, 0.1); border: 1px solid rgba(79, 70, 229, 0.2);">Ecossistema Vivensi</span>
            <h2 style="font-size: 3rem; color: var(--secondary); margin-top: 15px; font-weight: 800; letter-spacing: -1px;">Uma plataforma para três mundos</h2>
            <p style="color: var(--text-light); max-width: 600px; margin: 20px auto; font-size: 1.1rem;">Soluções sob medida para quem busca transparência, eficiência e inteligência financeira.</p>
        </div>

        <div class="feature-grid" style="max-width: 1200px; margin: 0 auto; gap: 40px;">
            <!-- NGO Card -->
            <div class="feature-card" style="border: 1px solid #f1f5f9; position: relative; padding: 60px 40px; border-top: 6px solid #e11d48;">
                <div style="text-transform: uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 2px; color: #e11d48; margin-bottom: 10px;">Solução Especializada</div>
                <h2 style="font-weight: 900; font-size: 1.8rem; margin-bottom: 25px; color: #0f172a; line-height: 1.2;">ONGs & Projetos Sociais</h2>
                
                <div class="feature-icon" style="background: #fff1f2; color: #e11d48; margin-bottom: 30px;"><i class="fas fa-landmark"></i></div>
                
                <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; color: #1e293b;">Portal da Transparência</h3>
                <p style="color: var(--text-light); font-size: 1rem; line-height: 1.7; margin-bottom: 30px;">Gere portais públicos auditáveis automaticamente. Transforme a prestação de contas em uma ferramenta de captação e confiança para seus doadores.</p>
                
                <hr style="opacity: 0.1; margin: 25px 0;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #e11d48; display: flex; align-items: center;">
                        <i class="fas fa-shield-heart me-2" style="font-size: 1.1rem;"></i> Credibilidade para sua Causa
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-comments me-2" style="color: #e11d48;"></i> Chat Interno de Equipe
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-bolt me-2" style="color: #e11d48;"></i> Potencializado por IA Vivensi
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-file-invoice me-2" style="color: #e11d48;"></i> Relatórios DRE Automáticos
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-users-viewfinder me-2" style="color: #e11d48;"></i> Gestão de Doadores Recorrentes
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-globe me-2" style="color: #e11d48;"></i> Domínio Próprio para Landing Pages
                    </div>
                </div>
            </div>

            <!-- Manager Card -->
            <div class="feature-card" style="border: 1px solid #f1f5f9; position: relative; padding: 60px 40px; border-top: 6px solid #16a34a;">
                <div style="text-transform: uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 2px; color: #16a34a; margin-bottom: 10px;">Gestão Business</div>
                <h2 style="font-weight: 900; font-size: 1.8rem; margin-bottom: 25px; color: #0f172a; line-height: 1.2;">Gestores de Projetos e Empresas</h2>
                
                <div class="feature-icon" style="background: #f0fdf4; color: #16a34a; margin-bottom: 30px;"><i class="fas fa-chart-pie"></i></div>
                
                <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; color: #1e293b;">Controle Operacional 360º</h3>
                <p style="color: var(--text-light); font-size: 1rem; line-height: 1.7; margin-bottom: 30px;">Domine seus cronogramas e fluxo financeiro em uma única tela. Trilha de auditoria completa e segurança total para cada decisão do seu negócio.</p>
                
                <hr style="opacity: 0.1; margin: 25px 0;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #16a34a; display: flex; align-items: center;">
                        <i class="fas fa-rocket me-2" style="font-size: 1.1rem;"></i> Performance & Escalabilidade
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-comments me-2" style="color: #16a34a;"></i> Chat Interno & Colaboração
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-brain me-2" style="color: #16a34a;"></i> Inteligência Preditiva IA
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-calendar-alt me-2" style="color: #16a34a;"></i> Gráfico de Gantt Interativo
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-fingerprint me-2" style="color: #16a34a;"></i> Auditoria de Ações (Logs)
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-file-invoice-dollar me-2" style="color: #16a34a;"></i> Conciliação Bancária Avançada
                    </div>
                </div>
            </div>

            <!-- Common Card -->
            <div class="feature-card" style="border: 1px solid #f1f5f9; position: relative; padding: 60px 40px; border-top: 6px solid #2563eb;">
                <div style="text-transform: uppercase; font-size: 0.75rem; font-weight: 800; letter-spacing: 2px; color: #2563eb; margin-bottom: 10px;">Sucesso Financeiro</div>
                <h2 style="font-weight: 900; font-size: 1.8rem; margin-bottom: 25px; color: #0f172a; line-height: 1.2;">Uso Pessoal</h2>
                
                <div class="feature-icon" style="background: #eff6ff; color: #2563eb; margin-bottom: 30px;"><i class="fas fa-robot"></i></div>
                
                <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; color: #1e293b;">Vivensi Insight IA</h3>
                <p style="color: var(--text-light); font-size: 1rem; line-height: 1.7; margin-bottom: 30px;">O poder da IA para suas finanças. Analise padrões de gastos reais e receba insights estratégicos para economizar e investir com sabedoria.</p>
                
                <hr style="opacity: 0.1; margin: 25px 0;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: #2563eb; display: flex; align-items: center;">
                        <i class="fas fa-brain me-2" style="font-size: 1.1rem;"></i> Inteligência nas Finanças
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-university me-2" style="color: #2563eb;"></i> Conciliação Bancária OFX
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-bullseye me-2" style="color: #2563eb;"></i> Metas e Orçamento Anual
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-mobile-alt me-2" style="color: #2563eb;"></i> Acesso Full Mobile
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-layer-group me-2" style="color: #2563eb;"></i> Categorização Automática
                    </div>
                    <div style="font-size: 0.85rem; color: #64748b; display: flex; align-items: center;">
                        <i class="fas fa-bell me-2" style="color: #2563eb;"></i> Alertas de Gastos por IA
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" style="padding: 100px 5%; background: white;">
        <div class="section-header" style="text-align: center; margin-bottom: 60px;">
            <span class="section-badge">Gente que transforma vidas usa Vivensi</span>
            <h2 style="font-size: 2.5rem; color: var(--secondary); margin-top: 10px; font-weight: 800;">Histórias de Impacto</h2>
            <p style="color: var(--text-light); max-width: 600px; margin: 20px auto;">Confira como o Vivensi ajuda organizações e pessoas a alcançarem novos patamares.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">
            @forelse($testimonials as $testimonial)
            <div class="testimonial-card" style="background: #f8fafc; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; position: relative; transition: all 0.3s ease;">
                <div style="color: var(--primary); font-size: 2rem; margin-bottom: 20px; opacity: 0.3;"><i class="fas fa-quote-left"></i></div>
                <p style="color: #334155; font-size: 1.1rem; font-style: italic; line-height: 1.7; margin-bottom: 30px;">
                    "{{ $testimonial->content }}"
                </p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #e2e8f0; overflow: hidden;">
                        <img src="{{ $testimonial->photo ?: 'https://i.pravatar.cc/100?u=' . Str::slug($testimonial->name) }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 1rem;">{{ $testimonial->name }}</h4>
                        <p style="margin: 0; color: #64748b; font-size: 0.85rem;">{{ $testimonial->role }}</p>
                    </div>
                </div>
            </div>
            @empty
                <div style="text-align: center; grid-column: 1 / -1;">
                    <p class="text-muted">Nenhum depoimento encontrado.</p>
                </div>
            @endforelse
        </div>
    </section>

    <style>
        .testimonial-card:hover { transform: translateY(-10px); background: white !important; box-shadow: 0 40px 80px -20px rgba(0,0,0,0.1); border-color: var(--primary); }
    </style>

    <!-- Academy Hero Section -->
    <section id="academy" style="padding: 100px 5%; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); position: relative; overflow: hidden;">
        <!-- Decorative Elements -->
        <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background: rgba(99, 102, 241, 0.2); border-radius: 50%; filter: blur(100px);"></div>
        <div style="position: absolute; bottom: -150px; left: -150px; width: 500px; height: 500px; background: rgba(139, 92, 246, 0.2); border-radius: 50%; filter: blur(120px);"></div>
        
        <style>
            .academy-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
            @media (max-width: 768px) {
                .academy-grid { grid-template-columns: 1fr !important; gap: 40px !important; }
            }
        </style>
        <div style="max-width: 1200px; margin: 0 auto; position: relative; z-index: 2;">
            <div class="academy-grid">
                <!-- Left: Text Content -->
                <div>
                    <div style="display: inline-block; background: rgba(251, 191, 36, 0.2); backdrop-filter: blur(10px); padding: 8px 20px; border-radius: 30px; margin-bottom: 25px; border: 1px solid rgba(251, 191, 36, 0.3);">
                        <span style="color: #fbbf24; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px;">✨ NOVO: VIVENSI ACADEMY</span>
                    </div>
                    
                    <h2 style="color: #fff; font-weight: 900; font-size: 3rem; letter-spacing: -2px; margin-bottom: 25px; line-height: 1.1;">
                        Aprenda a Dominar sua
                        <span style="background: linear-gradient(90deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Gestão</span>
                    </h2>
                    
                    <p style="color: #e0e7ff; font-size: 1.2rem; line-height: 1.7; margin-bottom: 35px; max-width: 500px;">
                        Cursos exclusivos, certificados reconhecidos e conteúdo prático para transformar sua organização.
                    </p>
                    
                    <!-- Features List -->
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 40px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-graduation-cap" style="color: #818cf8; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="color: #fff; font-weight: 700;">Cursos Práticos</div>
                                <div style="color: #cbd5e1; font-size: 0.9rem;">Aprenda fazendo, não apenas assistindo</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; background: rgba(251, 191, 36, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-crown" style="color: #fbbf24; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="color: #fff; font-weight: 700;">Conteúdos Exclusivos para Assinantes</div>
                                <div style="color: #cbd5e1; font-size: 0.9rem;">Acesso VIP a materiais premium e ebooks</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-certificate" style="color: #818cf8; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="color: #fff; font-weight: 700;">Certificados Oficiais</div>
                                <div style="color: #cbd5e1; font-size: 0.9rem;">Reconhecidos e validados digitalmente</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-infinity" style="color: #818cf8; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <div style="color: #fff; font-weight: 700;">Acesso Ilimitado</div>
                                <div style="color: #cbd5e1; font-size: 0.9rem;">Estude no seu ritmo, quando quiser</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="{{ route('register') }}" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #1e293b; padding: 16px 35px; border-radius: 12px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4); transition: transform 0.2s;">
                            <i class="fas fa-rocket"></i> Começar Agora
                        </a>
                        <a href="#pricing" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); color: white; padding: 16px 35px; border-radius: 12px; text-decoration: none; font-weight: 700; border: 1px solid rgba(255, 255, 255, 0.2); transition: all 0.2s;">
                            <i class="fas fa-tag"></i> Ver Planos
                        </a>
                    </div>
                </div>
                
                <!-- Right: Course Preview Cards -->
                <div style="position: relative;">
                    <!-- Main Card -->
                    <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-radius: 24px; padding: 30px; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); height: 180px; border-radius: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-play-circle" style="color: white; font-size: 4rem; opacity: 0.9;"></i>
                        </div>
                        <h3 style="color: #fff; font-weight: 700; font-size: 1.3rem; margin-bottom: 10px;">Gestão de Projetos Sociais</h3>
                        <p style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 20px;">Domine as ferramentas essenciais para gerenciar projetos de impacto social.</p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 15px; color: #94a3b8; font-size: 0.85rem;">
                                <span><i class="far fa-clock"></i> 12 aulas</span>
                                <span><i class="fas fa-signal"></i> Iniciante</span>
                            </div>
                            <div style="background: rgba(251, 191, 36, 0.2); color: #fbbf24; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 0.8rem;">
                                <i class="fas fa-star"></i> POPULAR
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 16px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <div style="color: #fbbf24; font-size: 2rem; font-weight: 900; margin-bottom: 5px;">500+</div>
                            <div style="color: #cbd5e1; font-size: 0.9rem;">Alunos Ativos</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 16px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <div style="color: #818cf8; font-size: 2rem; font-weight: 900; margin-bottom: 5px;">15+</div>
                            <div style="color: #cbd5e1; font-size: 0.9rem;">Cursos Disponíveis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="pricing">
        <div class="section-header" style="text-align: center;">
            <span class="section-badge">Planos Flexíveis</span>
            <h2 style="font-size: 2.5rem; color: var(--secondary); margin-top: 10px;">Escolha o ideal para sua missão</h2>
        </div>

        <div class="pricing-grid">
            @forelse($plans as $plan)
                <div class="price-card {{ $plan->target_audience == 'ngo' ? 'featured' : '' }}">
                    @if($plan->target_audience == 'ngo')
                        <div style="background: var(--primary); color: white; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">MAIS POPULAR</div>
                    @endif
                    @php
                        $audienceLabel = match($plan->target_audience) {
                            'ngo' => 'Terceiro Setor',
                            'manager' => 'Gestor de Projetos',
                            'common' => 'Pessoal',
                            default => 'Plano'
                        };
                    @endphp

                    <h3>{{ $plan->name }}</h3>
                    <div class="price">
                         R$ <span class="amount">{{ number_format($plan->price, 2, ',', '.') }}</span>
                         <span class="period" style="font-size: 1rem; color: #64748b; font-weight: 400;">/mês</span></div>
                    <p style="color: var(--text-light);">{{ $audienceLabel }}</p>
                    
                    <a href="{{ route('register', ['plan_id' => $plan->id]) }}" class="{{ $plan->target_audience == 'ngo' ? 'btn-cta' : 'btn-outline' }}" style="display: block; margin-top: 20px;">
                        {{ $plan->price > 0 ? 'Assinar Agora' : 'Começar Grátis' }}
                    </a>
                    
                    <ul class="check-list">
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li><i class="fas fa-check text-success"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Novos planos em breve!</p>
                </div>
            @endforelse
        </div>

    </section>

    <!-- FAQ Section -->
    <section id="faq" style="padding: 100px 5%; background: var(--bg-light);">
        <div class="section-header" style="text-align: center; margin-bottom: 60px;">
            <span class="section-badge">Suporte & FAQ</span>
            <h2 style="font-size: 2.5rem; color: var(--secondary); margin-top: 10px; font-weight: 800;">Dúvidas Frequentes</h2>
            <p style="color: var(--text-light); max-width: 600px; margin: 20px auto;">Aqui estão as respostas para as perguntas mais comuns sobre o Vivensi.</p>
        </div>

        <div style="max-width: 800px; margin: 0 auto;">
            <div class="faq-item" style="margin-bottom: 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;">
                <button class="faq-question" style="width: 100%; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: none; background: transparent; cursor: pointer; text-align: left;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">Posso cancelar minha assinatura a qualquer momento?</span>
                    <i class="fas fa-chevron-down" style="color: var(--primary); transition: transform 0.3s;"></i>
                </button>
                <div class="faq-answer" style="padding: 0 25px 25px; color: #64748b; font-size: 1rem; line-height: 1.7; display: none;">
                    Sim! O Vivensi não possui contratos de fidelidade. Você pode cancelar sua assinatura diretamente pelo painel a qualquer momento, sem taxas ocultas ou multas.
                </div>
            </div>

            <div class="faq-item" style="margin-bottom: 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;">
                <button class="faq-question" style="width: 100%; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: none; background: transparent; cursor: pointer; text-align: left;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">Como funciona o período de teste grátis?</span>
                    <i class="fas fa-chevron-down" style="color: var(--primary); transition: transform 0.3s;"></i>
                </button>
                <div class="faq-answer" style="padding: 0 25px 25px; color: #64748b; font-size: 1rem; line-height: 1.7; display: none;">
                    Oferecemos acesso gratuito aos nossos planos para que você sinta o poder da plataforma. Não solicitamos cartão de crédito para o cadastro inicial nos planos gratuitos e você pode migrar para um plano pago quando desejar.
                </div>
            </div>

            <div class="faq-item" style="margin-bottom: 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;">
                <button class="faq-question" style="width: 100%; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: none; background: transparent; cursor: pointer; text-align: left;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">Meus dados estão seguros na plataforma?</span>
                    <i class="fas fa-chevron-down" style="color: var(--primary); transition: transform 0.3s;"></i>
                </button>
                <div class="faq-answer" style="padding: 0 25px 25px; color: #64748b; font-size: 1rem; line-height: 1.7; display: none;">
                    Totalmente. Utilizamos infraestrutura de nuvem segura da AWS, criptografia SSL em todas as conexões e backups diários automáticos. Seguimos rigorosamente as diretrizes da LGPD para garantir que seus dados permaneçam privados.
                </div>
            </div>
            
            <div class="faq-item" style="margin-bottom: 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all 0.3s;">
                <button class="faq-question" style="width: 100%; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: none; background: transparent; cursor: pointer; text-align: left;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">Posso migrar meus dados de outra planilha ou sistema?</span>
                    <i class="fas fa-chevron-down" style="color: var(--primary); transition: transform 0.3s;"></i>
                </button>
                <div class="faq-answer" style="padding: 0 25px 25px; color: #64748b; font-size: 1rem; line-height: 1.7; display: none;">
                    Sim, oferecemos ferramentas de importação via CSV e suporte especializado para grandes migrações de dados de ONGs e empresas. Nossa equipe técnica está pronta para ajudar você na transição.
                </div>
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const answer = button.nextElementSibling;
                const icon = button.querySelector('i');
                const isOpen = answer.style.display === 'block';
                
                // Fecha outros
                document.querySelectorAll('.faq-answer').forEach(a => a.style.display = 'none');
                document.querySelectorAll('.faq-question i').forEach(i => i.style.transform = 'rotate(0deg)');
                
                if (!isOpen) {
                    answer.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    </script>

    <!-- Blog Section -->
    <!-- Updated via Agent -->
    <section id="blog" class="blog" style="padding: 100px 5%; background: #f8fafc;">
        <div class="section-header" style="text-align: center;">
            <span class="section-badge">Blog & Insights</span>
            <h2 style="font-size: 2.5rem; color: var(--secondary); margin-top: 10px; font-weight: 800;">Fique por dentro das novidades</h2>
            <p style="color: var(--text-light); max-width: 600px; margin: 20px auto;">Dicas de gestão, finanças e tecnologia para potencializar sua missão.</p>
        </div>

        <div class="blog-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">
            @forelse($posts as $post)
            <div class="blog-card" style="background: white; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s ease;">
                <div style="height: 200px; background: #e2e8f0; position: relative; overflow: hidden;">
                    <img src="{{ $post->image ?: 'https://images.unsplash.com/photo-1499750310107-5fef28a66643' }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                </div>
                <div style="padding: 25px;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); margin-bottom: 10px; text-transform: uppercase;">{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d M, Y') : 'Recente' }}</div>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; line-height: 1.4;">{{ $post->title }}</h3>
                    <p style="color: #64748b; font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px;">
                        {{ Str::limit(strip_tags($post->content), 120) }}
                    </p>
                    <a href="{{ route('public.blog.show', $post->slug) }}" style="color: var(--primary); font-weight: 800; text-decoration: none; font-size: 0.85rem; display: flex; align-items: center;">
                        LER ARTIGO COMPLETO <i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 20px; border: 1px dashed #cbd5e1;">
                <i class="far fa-newspaper" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                <h3 style="color: #64748b; font-size: 1.2rem;">Nenhum artigo publicado ainda.</h3>
                <p style="color: #94a3b8;">Em breve novidades por aqui!</p>
            </div>
            @endforelse
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <a href="{{ route('public.blog.index') }}" class="btn-outline" style="padding: 12px 40px;">Ver Todos os Artigos</a>
        </div>
    </section>

    <style>
        .blog-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); border-color: var(--primary); }
        .blog-card:hover img { transform: scale(1.1); }
    </style>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div>
                <a href="{{ url('/') }}" class="footer-brand">
                    <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 35px; filter: brightness(0) invert(1);">
                </a>
                <p>Transformando a gestão financeira e operacional com tecnologia e propósito.</p>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <a href="#" style="color: white;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: white;"><i class="fab fa-linkedin"></i></a>
                    <a href="#" style="color: white;"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Produto</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="#" style="color: #94a3b8; text-decoration: none;">Recursos</a>
                    <a href="#" style="color: #94a3b8; text-decoration: none;">Integrações</a>
                    <a href="#pricing" style="color: #94a3b8; text-decoration: none;">Preços</a>
                </div>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Empresa</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('public.page', 'sobre') }}" style="color: #94a3b8; text-decoration: none;">Sobre Nós</a>
                    <a href="{{ route('admin.dashboard') }}" style="color: #94a3b8; text-decoration: none;">Painel Admin</a>
                    <a href="#" style="color: #94a3b8; text-decoration: none;">Contato</a>
                </div>
            </div>
             <div>
                <h4 style="color: white; margin-bottom: 20px;">Legal</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('public.page', 'privacidade') }}" style="color: #94a3b8; text-decoration: none;">Privacidade</a>
                    <a href="{{ route('public.page', 'termos') }}" style="color: #94a3b8; text-decoration: none;">Termos de Uso</a>
                </div>
            </div>
        </div>
        <div style="text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 40px;">
            <p>&copy; 2026 Vivensi Inc. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/5516988392853?text=Ol%C3%A1!%20Gostaria%20de%20saber%20mais%20sobre%20o%20Vivensi%20SaaS." class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp my-float"></i>
        <span class="whatsapp-text">Fale Conosco</span>
    </a>

    <style>
        .whatsapp-float {
            position: fixed;
            width: 60px;
            height: 60px;
            bottom: 40px;
            right: 40px;
            background-color: #25d366;
            color: #FFF;
            border-radius: 50px;
            text-align: center;
            font-size: 30px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-float:hover {
            width: 180px;
            background-color: #20ba5a;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.6);
        }

        .whatsapp-text {
            display: none;
            font-size: 16px;
            font-weight: 700;
            margin-left: 10px;
            white-space: nowrap;
        }

        .whatsapp-float:hover .whatsapp-text {
            display: inline;
        }

        .my-float {
            font-size: 32px;
        }

        /* Pulsing Shadow Animation */
        @keyframes pulse-whatsapp {
            0% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0); }
        }

        .whatsapp-float {
            animation: pulse-whatsapp 2s infinite;
        }

        @media (max-width: 768px) {
            .whatsapp-float {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }
            .whatsapp-float:hover {
                width: 50px; /* Keep round on mobile */
            }
            .whatsapp-text { display: none !important; }
        }
    </style>

    {{-- Cookie Consent Banner (LGPD) --}}
    @include('partials.cookie-banner')

</body>
</html>
