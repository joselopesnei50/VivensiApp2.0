<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        :root {
            --ink: #080E1A; --ink2: #111827;
            --blue: #3B6CF6; --blue2: #5B82FF;
            --violet: #7C3AED; --violet2: #9B59F7;
            --teal: #00D4AA; --rose: #E8455A;
            --gold: #F5A623; --white: #FFFFFF;
            --glass: rgba(255, 255, 255, .06);
            --border: rgba(255, 255, 255, .1);
            --primary-color: var(--blue);
            --primary-hover: var(--blue2);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--ink); 
            color: var(--white);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Premium Hero Section from Welcome Page */
        .login-hero {
            flex: 1.25;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            background: radial-gradient(ellipse 100% 80% at 50% 0%, rgba(59,108,246,.2) 0%, transparent 55%),
                       radial-gradient(ellipse 60% 50% at 80% 80%, rgba(124,58,237,.12) 0%, transparent 50%),
                       var(--ink);
            overflow: hidden;
            display: none; /* Mobile */
        }
        
        /* Decorative Background Elements */
        .hero-glow {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(59,108,246,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 580px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59,108,246,.12);
            border: 1px solid rgba(59,108,246,.3);
            color: var(--blue2);
            font-size: .78rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 100px;
            margin-bottom: 24px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .hero-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--blue2); animation: pulseBlue 2s infinite; }

        .hero-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.03em;
            margin-bottom: 24px;
        }
        .hero-title .g1 { background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,.7) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-title .g2 { background: linear-gradient(135deg, var(--blue2), var(--violet2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .hero-text {
            font-size: 1.1rem;
            color: rgba(255,255,255,.6);
            margin-bottom: 40px;
            max-width: 480px;
        }

        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .feat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            color: rgba(255,255,255,.8);
        }
        .feat-item i { color: var(--teal); font-size: 1.1rem; }

        /* Login Form Section */
        .login-form-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background: var(--ink2); /* Ligeiramente mais claro que o fundo principal */
            position: relative;
            border-left: 1px solid var(--border);
        }

        /* Logo and Heading */
        .brand-section {
            text-align: center;
            margin-bottom: 40px;
        }
        .brand-logo img {
            height: 50px;
            margin-bottom: 20px;
        }
        .login-heading h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
        }
        .login-heading p {
            color: rgba(255,255,255,0.45);
            font-size: 0.95rem;
        }

        /* Login Card */
        .login-card {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            animation: fadeUp 0.8s ease;
        }

        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            transition: color 0.3s;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--blue2);
            box-shadow: 0 0 0 4px rgba(59, 108, 246, 0.15);
            outline: none;
        }
        .form-control:focus + i { color: var(--blue2); }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--blue), var(--violet));
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(59, 108, 246, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(59, 108, 246, 0.5);
            filter: brightness(1.1);
        }

        .form-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.4);
        }
        .form-footer a {
            color: var(--blue2);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .form-footer a:hover { color: white; }

        .forgot-link {
            display: inline-block;
            margin-top: 12px;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-link:hover { color: rgba(255,255,255,0.8); }

        /* Lang Switcher */
        .lang-switcher {
            position: absolute;
            top: 40px; right: 40px;
            display: flex;
            gap: 16px;
        }
        .lang-link {
            text-decoration: none;
            color: rgba(255,255,255,0.3);
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s;
        }
        .lang-link.active { color: white; }
        .lang-link img { width: 20px; border-radius: 2px; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulseBlue { 0%,100% { opacity: 1; } 50% { opacity: .4; } }

        @media(min-width: 900px) { .login-hero { display: flex; } }
        @media(max-width: 768px) {
            .login-card { padding: 30px; }
            .lang-switcher { top: 20px; right: 20px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Hero Image (Premium) -->
    <div class="login-hero">
        <div class="hero-glow"></div>
        <div class="hero-content">
            <div class="hero-badge"><span class="hero-badge-dot"></span> Área Restrita Vivensi</div>
            <h1 class="hero-title">
                <span class="g1">Gestão que</span><br>
                <span class="g2">Transforma Vidas</span><br>
                <span class="g1">em escala real.</span>
            </h1>
            <p class="hero-text">
                Bem-vindo de volta ao seu centro de comando social. Acesse sua conta para gerenciar projetos, doações e impacto em tempo real.
            </p>
            <div class="hero-features">
                <div class="feat-item"><i class="fas fa-shield-check"></i> <span>Acesso seguro com criptografia de ponta</span></div>
                <div class="feat-item"><i class="fas fa-chart-network"></i> <span>Dados integrados em um único ecossistema</span></div>
                <div class="feat-item"><i class="fas fa-sparkles"></i> <span>Interface otimizada para produtividade</span></div>
            </div>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="login-form-side">
        <!-- Lang Switcher -->
        <div class="lang-switcher">
            <a href="#" class="lang-link active" title="Português">
                <img src="https://flagcdn.com/w40/br.png" alt="Brasil"> PT
            </a>
            <a href="#" class="lang-link" title="Español">
                <img src="https://flagcdn.com/w40/es.png" alt="España"> ES
            </a>
        </div>

        <div class="brand-section">
            <a href="{{ url('/') }}" class="brand-logo">
                <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
            </a>
            <div class="login-heading">
                <h2>Seja bem-vindo</h2>
                <p>Identifique-se para continuar</p>
            </div>
        </div>

        <div class="login-card">
            @if(session('error'))
                <div style="background: rgba(232, 69, 90, 0.1); color: #FF7080; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(232, 69, 90, 0.2); margin-bottom: 24px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div style="background: rgba(0, 212, 170, 0.1); color: var(--teal); padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(0, 212, 170, 0.2); margin-bottom: 24px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label>E-mail</label>
                    <div class="input-group">
                        <i class="far fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="voce@empresa.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Senha</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="far fa-eye" id="togglePasswordIcon" onclick="togglePasswordVisibility()" style="left: auto; right: 16px; cursor: pointer; color: rgba(255,255,255,0.2);" title="Mostrar Senha"></i>
                    </div>
                    <div style="text-align: right;">
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            Esqueceu a senha?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="loginBtn">
                    Entrar na Plataforma <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="form-footer">
                Não possui uma conta? <a href="{{ route('register') }}">Criar conta grátis</a>
            </div>
        </div>
        
        <div style="margin-top: 40px; color: rgba(255,255,255,0.15); font-size: 0.75rem; text-align: center; letter-spacing: 0.05em; text-transform: uppercase; font-weight: 600;">
            &copy; 2026 <strong>Vivensi</strong> &bull; Gestão de Impacto Social
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>
