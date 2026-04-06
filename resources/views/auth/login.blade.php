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

        /* Premium Hero Section with Map Visibility */
        .login-hero {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 80px 40px;
            background: radial-gradient(ellipse 100% 80% at 50% 0%, rgba(59,108,246,.2) 0%, transparent 55%),
                       radial-gradient(ellipse 60% 50% at 80% 80%, rgba(124,58,237,.12) 0%, transparent 50%),
                       var(--ink);
            overflow: hidden;
            border-right: 1px solid var(--border);
            display: none; /* Mobile */
        }
        
        .hero-content {
            position: relative;
            z-index: 5;
            text-align: center;
            max-width: 500px;
        }

        /* Brazil Map Styling from Welcome Page */
        .map-wrapper {
            position: absolute;
            top: 55%; left: 50%;
            transform: translate(-50%, -50%);
            width: 140%;
            opacity: 0.35;
            pointer-events: none;
            z-index: 1;
        }
        #brazil-svg { width: 100%; height: auto; display: block; filter: drop-shadow(0 0 40px rgba(59, 108, 246, 0.2)); }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59,108,246,.12);
            border: 1px solid rgba(59,108,246,.3);
            color: var(--blue2);
            font-size: .75rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 100px;
            margin-bottom: 24px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .hero-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--blue2); animation: pulseBlue 2s infinite; }

        .hero-title {
            font-size: clamp(2rem, 3.5vw, 3rem);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.03em;
            margin-bottom: 20px;
        }
        .hero-title .g1 { background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,.7) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-title .g2 { background: linear-gradient(135deg, var(--blue2), var(--violet2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .hero-text {
            font-size: 1.05rem;
            color: rgba(255,255,255,.5);
            max-width: 440px;
            margin: 0 auto;
        }

        /* Login Form Side (Centered more) */
        .login-form-side {
            flex: 1.1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            background: var(--ink2);
            position: relative;
        }

        /* Lang Switcher - Centralized above form */
        .lang-switcher {
            display: flex;
            gap: 20px;
            margin-bottom: 32px;
            background: rgba(255,255,255,0.03);
            padding: 8px 16px;
            border-radius: 50px;
            border: 1px solid var(--border);
        }
        .lang-link {
            text-decoration: none;
            color: rgba(255,255,255,0.3);
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .lang-link.active { color: white; }
        .lang-link:hover { color: rgba(255,255,255,0.6); }
        .lang-link img { width: 18px; height: 18px; object-fit: cover; border-radius: 50%; border: 1px solid var(--border); }

        .brand-section {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-logo img {
            height: 46px;
            margin-bottom: 16px;
        }
        .login-heading h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
        }
        .login-heading p { color: rgba(255,255,255,0.4); font-size: 0.9rem; }

        /* Login Card */
        .login-card {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 28px;
            border: 1px solid var(--border);
            box-shadow: 0 40px 100px rgba(0,0,0,0.5);
            animation: fadeUp 0.8s ease backwards;
        }

        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 16px 16px 52px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 16px;
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
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(59, 108, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(59, 108, 246, 0.6);
            filter: brightness(1.1);
        }

        .form-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.35);
        }
        .form-footer a {
            color: var(--blue2);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        .form-footer a:hover { color: white; text-shadow: 0 0 10px rgba(91,130,255,0.5); }

        .forgot-link {
            display: inline-block;
            margin-top: 12px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .forgot-link:hover { color: rgba(255,255,255,0.7); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulseBlue { 0%,100% { opacity: 1; } 50% { opacity: .4; } }

        @media(min-width: 900px) { .login-hero { display: flex; } }
        @media(max-width: 1024px) {
            .login-hero { padding: 40px; }
            .hero-title { font-size: 2.4rem; }
        }
        @media(max-width: 768px) {
            .login-card { padding: 30px; border-radius: 20px; }
            .login-form-side { padding: 40px 20px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Hero Banner with Brazil Map -->
    <div class="login-hero">
        <!-- SVG Map Container -->
        <div class="map-wrapper">
            <svg id="brazil-svg" viewBox="0 0 820 740" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="bgGrad" cx="50%" cy="40%" r="60%">
                        <stop offset="0%" stop-color="#3B6CF6" stop-opacity=".15"/>
                        <stop offset="100%" stop-color="#080E1A" stop-opacity="0"/>
                    </radialGradient>
                    <linearGradient id="mapFill" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#3B6CF6" stop-opacity=".3"/>
                        <stop offset="50%" stop-color="#7C3AED" stop-opacity=".2"/>
                        <stop offset="100%" stop-color="#E8455A" stop-opacity=".1"/>
                    </linearGradient>
                    <filter id="glow"><feGaussianBlur stdDeviation="4" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                </defs>
                <path d="M295.5,56.5c10.5-5.5,25.5-8.5,38.5-6.5s28,8.5,41.5,10c13.5,1.5,22.5-2.5,35.5,3.5s23,19,30,28.5s15.5,23,26,32s24,19.5,24,37.5s-4.5,30-10,43.5s-4.5,26.5,8,42s26,30,34.5,45s12.5,35.5,12.5,52.5s-4.5,33.5-12,48.5s-16.5,28.5-28.5,39.5s-20,19-33,28.5s-21.5,22-26.5,37s-10,32-20,46s-20,24-33.5,36.5s-24,20-35,32s-19.5,25-29,38s-17,21.5-28,21.5s-21.5-9.5-33.5-23s-22-29-32-45.5s-18-35.5-23-53.5s-7.5-35.5-9.5-54s-2.5-36,1-53s8-32.5,16.5-47s12.5-31,12.5-49.5s-6-35.5-15.5-50.5s-18-31-25.5-48s-11-34-11-50s5-29,15-44s21-27,34.5-38s22.5-19.5,34.5-22s22.5,5.5,22.5,15.5s-5.5,22.5-11.5,34.5s-10,25.5-10,38.5s7.5,25,20,38.5s25.5,25.5,36.5,17s12.5-24.5,12.5-40s-6-30-14.5-40.5s-17-15.5-17-27.5S285,62,295.5,56.5z"
                      fill="url(#mapFill)" stroke="rgba(59,108,246,.4)" stroke-width="1.2" filter="url(#glow)"/>
                <!-- Pulse points -->
                <circle cx="390" cy="480" r="5" fill="var(--blue2)"><animate attributeName="opacity" values="1;.2;1" dur="2s" repeatCount="indefinite"/></circle>
                <circle cx="530" cy="210" r="4" fill="#FF7080"><animate attributeName="opacity" values="1;.2;1" dur="2.5s" repeatCount="indefinite" begin="0.5s"/></circle>
                <circle cx="310" cy="200" r="4" fill="var(--teal)"><animate attributeName="opacity" values="1;.2;1" dur="3s" repeatCount="indefinite" begin="1s"/></circle>
            </svg>
        </div>

        <div class="hero-content">
            <div class="hero-badge"><span class="hero-badge-dot"></span> Ecossistema Vivensi</div>
            <h1 class="hero-title">
                <span class="g1">Gestão que</span><br>
                <span class="g2">Transforma Vidas</span><br>
                <span class="g1">em escala real.</span>
            </h1>
            <p class="hero-text">
                Acesse o centro de comando da sua missão social. Gerencie projetos, acompanhe doações e impulsione seu impacto em todo o Brasil.
            </p>
        </div>
    </div>

    <!-- Right Login Section -->
    <div class="login-form-side">
        <!-- Centralized Lang Switcher -->
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
                <p>Identifique-se para continuar na plataforma</p>
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
                    <label>E-mail Corporativo</label>
                    <div class="input-group">
                        <i class="far fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="usuario@vivensi.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Senha de Acesso</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="far fa-eye" id="togglePasswordIcon" onclick="togglePasswordVisibility()" style="left: auto; right: 18px; cursor: pointer; color: rgba(255,255,255,0.2);" title="Mostrar Senha"></i>
                    </div>
                    <div style="text-align: right;">
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            Esqueceu sua senha?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="loginBtn">
                    Acessar Plataforma <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="form-footer">
                Não possui conta? <a href="{{ route('register') }}">Criar agora</a>
            </div>
        </div>
        
        <div style="margin-top: 40px; color: rgba(255,255,255,0.1); font-size: 0.7rem; text-align: center; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 700;">
            Vivensi &copy; 2026 &bull; Tecnologia de Impacto
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
