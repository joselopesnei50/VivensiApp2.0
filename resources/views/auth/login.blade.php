<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563EB;
            --primary-hover: #1D4ED8;
        }
        body { margin: 0; font-family: 'Outfit', sans-serif; background: #fff; }
        
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Modern Hero Section */
        .login-hero {
            flex: 1.2;
            background: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 60px;
            color: white;
            display: none; /* Mobile */
        }
        
        .login-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, rgba(37, 99, 235, 0.3) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .hero-features {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.3);
        }

        /* Login Form Section */
        .login-form-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background: #FAFAFA;
            position: relative;
        }

        .lang-switcher {
            width: 100%;
            max-width: 420px;
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .lang-link {
            text-decoration: none;
            color: #64748B;
            font-weight: 600;
            margin-left: 10px;
            opacity: 0.7;
            transition: 0.2s;
        }
        .lang-link:hover, .lang-link.active {
            opacity: 1;
            color: var(--primary-color);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            animation: slideUp 0.6s ease-out;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-logo i { color: var(--primary-color); }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #64748B;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #F8FAFC;
        }
        .form-control:focus {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        @media(min-width: 900px) {
            .login-hero { display: flex; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Hero Image -->
    <div class="login-hero">
        <div class="hero-content">
            <div class="hero-title">Domine as Finanças da sua Organização.</div>
            <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 30px;">
                Gestão completa para Empresas, Terceiro Setor e Profissionais.
                Automatize processos, visualize dados em tempo real e tome decisões inteligentes.
            </p>
            <div class="hero-features">
                <span class="hero-badge"><i class="fas fa-check-circle"></i> Dashboard IA</span>
                <span class="hero-badge"><i class="fas fa-shield-alt"></i> Seguro</span>
                <span class="hero-badge"><i class="fas fa-bolt"></i> Ambiente Único</span>
            </div>
        </div>
    </div>

    <!-- Right Login Form -->
    <div class="login-form-side">
        <!-- Lang Switcher -->
        <div class="lang-switcher">
            <a href="#" class="lang-link active" title="Português">
                <img src="https://flagcdn.com/w40/br.png" alt="Brasil" style="width: 24px; vertical-align: middle; margin-right: 5px; border-radius: 2px;"> PT
            </a>
            <a href="#" class="lang-link" title="Español">
                <img src="https://flagcdn.com/w40/es.png" alt="España" style="width: 24px; vertical-align: middle; margin-right: 5px; border-radius: 2px;"> ES
            </a>
        </div>

        <div class="login-card">
            <div class="brand-logo" style="justify-content: center;">
                <img src="{{ asset('img/logovivensi.png') }}" alt="VIVENSI" style="max-height: 60px;">
            </div>
            <p style="color: #64748B; margin-bottom: 30px;">Bem-vindo de volta! Acesse sua conta.</p>

            @if(session('error'))
                <div style="background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 8px; border: 1px solid #FECACA; margin-bottom: 20px; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div style="background: #ecfdf5; color: #166534; padding: 12px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px; font-size: 0.9rem;">
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
                        <i class="far fa-eye" id="togglePasswordIcon" onclick="togglePasswordVisibility()" style="left: auto; right: 15px; cursor: pointer;" title="Mostrar Senha"></i>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; margin-top: -8px; margin-bottom: 18px;">
                    <a href="{{ route('password.request') }}" style="color:#64748B; font-size:0.9rem; text-decoration:none; font-weight:600;">
                        Esqueci minha senha
                    </a>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <span class="btn-text">Entrar <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: #64748B;">
                Ainda não tem conta? <a href="{{ route('register') }}" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Criar conta</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; color: #94A3B8; font-size: 0.85rem; text-align: center;">
            &copy; 2026 <strong>Vivensi app</strong>. Todos os direitos reservados.
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
