<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Vivensi 2.0</title>
    
    <!-- Meta Tags Dinâmicas -->
    <meta name="description" content="@yield('meta_description', 'A plataforma definitiva para ONGs, Gestores e Finanças Pessoais.')">
    <meta name="keywords" content="gestão ongs, gestão de projetos, finanças pessoais, ia financeira, vivensi">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title') - Vivensi 2.0">
    <meta property="og:description" content="@yield('meta_description', 'A plataforma definitiva para ONGs, Gestores e Finanças Pessoais.')">
    <meta property="og:image" content="{{ asset('img/social-preview.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('title') - Vivensi 2.0">
    <meta property="twitter:description" content="@yield('meta_description', 'A plataforma definitiva para ONGs, Gestores e Finanças Pessoais.')">
    <meta property="twitter:image" content="{{ asset('img/social-preview.png') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #0f172a;
            --accent: #38bdf8;
            --success: #10b981;
            --bg-light: #f8fafc;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background: white;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            position: fixed;
            width: 90%;
            top: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .btn-cta {
            background: var(--primary);
            color: white;
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            display: inline-block;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.5);
        }

        .whatsapp-float {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: #25d366;
            color: white;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4);
            z-index: 999;
            transition: all 0.3s;
            text-decoration: none;
        }

        .whatsapp-float:hover {
            transform: scale(1.1) rotate(10deg);
        }

        .section-badge {
            background: #e0e7ff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 20px;
        }

        h2 { font-size: 2.5rem; font-weight: 800; color: var(--secondary); margin-bottom: 25px; line-height: 1.2; }
        p { font-size: 1.1rem; color: #64748b; margin-bottom: 30px; }

        .feature-box {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            transition: all 0.3s;
        }

        .feature-box:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
        }

        footer {
            background: var(--secondary);
            color: white;
            padding: 60px 5%;
            text-align: center;
        }

        @media (max-width: 768px) {
            h2 { font-size: 2rem; }
            .navbar { width: 100%; padding: 15px; }
            .whatsapp-float { bottom: 20px; right: 20px; width: 55px; height: 55px; font-size: 25px; }
        }

        /* Glass Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate { animation: fadeIn 0.8s ease-out forwards; }
    </style>
    @yield('styles')
</head>
<body>

    <nav class="navbar">
        <a href="{{ url('/') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 35px;">
        </a>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="display: flex; gap: 10px; margin-right: 15px; font-size: 0.9rem;">
                <a href="{{ route('lang.switch', 'pt_BR') }}" title="Português" style="text-decoration: none; opacity: {{ app()->getLocale() == 'pt_BR' ? '1' : '0.5' }}">🇧🇷</a>
                <a href="{{ route('lang.switch', 'es') }}" title="Español" style="text-decoration: none; opacity: {{ app()->getLocale() == 'es' ? '1' : '0.5' }}">🇪🇸</a>
                <a href="{{ route('lang.switch', 'en') }}" title="English" style="text-decoration: none; opacity: {{ app()->getLocale() == 'en' ? '1' : '0.5' }}">🇺🇸</a>
            </div>
            <a href="{{ route('login') }}" style="color: #64748b; font-weight: 700; text-decoration: none;">Login</a>
            <a href="#pricing" class="btn-cta">Testar Grátis 7 Dias</a>
        </div>
    </nav>

    @yield('content')

    <footer>
        <div style="margin-bottom: 30px;">
            <img src="{{ asset('img/logovivensi.png') }}" style="height: 35px; filter: brightness(0) invert(1);">
        </div>
        <p style="color: #94a3b8; font-size: 0.9rem;">&copy; {{ date('Y') }} Vivensi App. Todos os direitos reservados. Orgulhosamente Brasileiro 🇧🇷</p>
    </footer>

    <a href="https://wa.me/5581999999999?text=Olá! Vim da página de vendas e quero saber mais sobre o Vivensi." class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    @yield('scripts')
</body>
</html>
