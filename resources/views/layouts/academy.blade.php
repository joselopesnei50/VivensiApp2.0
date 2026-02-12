<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vivensi Academy - @yield('title', 'Plataforma de Ensino')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logovivensi.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logovivensi.png') }}">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #fff;
            overflow-x: hidden;
        }
        
        /* Top Navigation Bar */
        .academy-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .academy-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .academy-logo img {
            height: 40px;
            filter: brightness(0) invert(1); /* Make logo white for dark theme */
        }
        
        .academy-nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .academy-nav-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .academy-nav-links a:hover {
            color: #fff;
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        /* Main Content */
        .academy-content {
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        @media (max-width: 768px) {
            .academy-navbar {
                padding: 10px 15px;
            }
            
            .academy-nav-links {
                gap: 15px;
                font-size: 0.9rem;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Top Navigation -->
    <nav class="academy-navbar">
        <a href="{{ route('academy.index') }}" class="academy-logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Academy">
        </a>
        
        <div class="academy-nav-links">
            <a href="{{ route('academy.index') }}">Meus Cursos</a>
            <a href="{{ url('/dashboard') }}">Dashboard</a>
            <a href="{{ url('/profile') }}">Perfil</a>
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="academy-content">
        @yield('content')
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
