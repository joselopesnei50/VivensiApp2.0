<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary: #4F46E5; --secondary: #1E293B; --bg: #FAFAFA; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: #1E293B; }
        .checkout-header { background: white; padding: 20px 0; box-shadow: 0 2px 15px rgba(0,0,0,0.05); margin-bottom: 40px; }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--secondary); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo i { color: var(--primary); }
    </style>
</head>
<body>

<header class="checkout-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="#" class="logo"><i class="fas fa-cube"></i> Vivensi</a>
            <div class="user-info small text-muted">
                <i class="fas fa-user-circle me-1"></i> {{ auth()->user()->email }} | 
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 small text-muted text-decoration-none">Sair</button>
                </form>
            </div>
        </div>
    </div>
</header>

<main class="container">
    @yield('content')
</main>

<footer class="text-center py-5 text-muted small">
    &copy; 2026 Vivensi App. Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
