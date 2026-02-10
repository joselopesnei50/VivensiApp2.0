<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VIVENSI - Executive Dashboard' }}</title>
    
    <!-- SEO -->
    <meta name="description" content="Vivensi Executive Dashboard - SaaS Management Platform">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/logovivensi.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logovivensi.png') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Executive CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-executive.css') }}">
    
</head>
<body class="executive-body">

<!-- Sidebar -->
<aside class="executive-sidebar" id="executiveSidebar">
    <div class="sidebar-header">
        <img src="{{ asset('img/logovivensi.png') }}" alt="VIVENSI" class="sidebar-logo">
        <span class="sidebar-title">VIVENSI</span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="{{ url('/admin') }}" class="nav-item {{ request()->is('admin') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Overview</span>
        </a>
        
        <a href="{{ url('/admin/tenants') }}" class="nav-item {{ request()->is('admin/tenants*') ? 'active' : '' }}">
            <i class="fas fa-building"></i>
            <span>Organizações</span>
        </a>
        
        <a href="{{ route('admin.plans.index') }}" class="nav-item {{ request()->is('admin/plans*') ? 'active' : '' }}">
            <i class="fas fa-tags"></i>
            <span>Planos</span>
        </a>
        
        <a href="{{ route('admin.academy.index') }}" class="nav-item {{ request()->is('admin/academy*') ? 'active' : '' }}">
            <i class="fas fa-graduation-cap"></i>
            <span>Academy</span>
        </a>
        
        <a href="{{ route('admin.blog.index') }}" class="nav-item {{ request()->is('admin/blog*') ? 'active' : '' }}">
            <i class="fas fa-blog"></i>
            <span>Blog</span>
        </a>
        
        <a href="{{ route('admin.team.index') }}" class="nav-item {{ request()->is('admin/team*') ? 'active' : '' }}">
            <i class="fas fa-users-cog"></i>
            <span>Time</span>
        </a>
        
        <a href="{{ route('admin.health') }}" class="nav-item {{ request()->is('admin/health*') ? 'active' : '' }}">
            <i class="fas fa-server"></i>
            <span>Sistema</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="{{ url('/admin/settings') }}" class="nav-item {{ request()->is('admin/settings*') ? 'active' : '' }}">
            <i class="fas fa-cogs"></i>
            <span>Configurações</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Super Admin</div>
            </div>
        </div>
        <a href="{{ route('logout') }}" class="logout-btn" 
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt"></i>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</aside>

<!-- Main Content -->
<main class="executive-main">
    <!-- Header -->
    <header class="executive-header">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar...">
        </div>
        
        <div class="header-actions">
            <button class="header-btn">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </button>
        </div>
    </header>
    
    <!-- Page Content -->
    <div class="executive-content">
        @if (session('success'))
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
        @endif
        
        @yield('content')
    </div>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('executiveSidebar').classList.toggle('collapsed');
}
</script>

@stack('scripts')

</body>
</html>
