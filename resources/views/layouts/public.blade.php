<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vivensi - Gestão Inteligente')</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;       /* Indigo 600 */
            --primary-dark: #4338ca;  /* Indigo 700 */
            --secondary: #0f172a;     /* Slate 900 */
            --accent: #38bdf8;        /* Sky 400 */
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
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
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

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

        /* Footer */
        footer {
            background: var(--secondary);
            color: #94a3b8;
            padding: 80px 10%;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand { color: white; font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; display: block; }

        @media (max-width: 768px) {
            .navbar { width: 100%; padding: 15px 20px; box-sizing: border-box; }
            .nav-links { display: none; }
        }

        /* Floating WhatsApp Button */
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

        @keyframes pulse-whatsapp {
            0% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0); }
        }

        .whatsapp-float {
            animation: pulse-whatsapp 2s infinite;
        }

        .section-badge {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="{{ url('/') }}" class="logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 40px;">
        </a>
        <div class="nav-links">
            <a href="{{ route('solutions.ngo') }}">ONGs</a>
            <a href="{{ route('solutions.manager') }}">Gestores</a>
            <a href="{{ route('solutions.common') }}">Pessoal</a>
            <a href="{{ url('/#pricing') }}">Preços</a>
            <a href="{{ route('public.blog.index') }}">Blog</a>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ route('login') }}" class="btn-outline">Entrar</a>
            <a href="{{ url('/#pricing') }}" class="btn-cta">Começar Agora</a>
        </div>
    </nav>

    <div style="margin-top: 100px;">
        @yield('content')
    </div>

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
                    <a href="{{ url('/#pricing') }}" style="color: #94a3b8; text-decoration: none;">Preços</a>
                </div>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Empresa</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('public.page', 'sobre') }}" style="color: #94a3b8; text-decoration: none;">Sobre Nós</a>
                    <a href="{{ route('public.blog.index') }}" style="color: #94a3b8; text-decoration: none;">Blog</a>
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
    <a href="https://wa.me/5516988392853?text=Olá! Gostaria de saber mais sobre o Vivensi SaaS." class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-text">Fale Conosco</span>
    </a>

    <!-- Cookie Consent Banner (LGPD) -->
    @include('partials.cookie-banner')

</body>
</html>

