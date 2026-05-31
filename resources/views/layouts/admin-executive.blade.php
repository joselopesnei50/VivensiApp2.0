<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-TTWCCXZ5');</script>
    <!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VIVENSI - Executive Dashboard' }}</title>
    
    <!-- SEO -->
    <meta name="description" content="Vivensi Executive Dashboard - SaaS Management Platform">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/favicon.png') }}">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Executive CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-executive.css') }}">
    
</head>
<body class="executive-body">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TTWCCXZ5"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<!-- Sidebar -->
<aside class="executive-sidebar" id="executiveSidebar">
    <div class="sidebar-header">
        <x-application-logo class="sidebar-logo" style="width: auto;" />
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

        <a href="{{ route('admin.team.index') }}" class="nav-item {{ request()->is('admin/team*') ? 'active' : '' }}">
            <i class="fas fa-users-cog"></i>
            <span>Time</span>
        </a>

        <a href="{{ route('admin.bot') }}" class="nav-item {{ request()->is('admin/bot*') ? 'active' : '' }}">
            <i class="fas fa-robot"></i>
            <span>Bot de Atendimento</span>
        </a>

        <a href="{{ route('admin.health') }}" class="nav-item {{ request()->is('admin/health*') ? 'active' : '' }}">
            <i class="fas fa-server"></i>
            <span>Sistema</span>
        </a>

        {{-- ── Marketing & Growth ── --}}
        <div class="nav-divider" style="font-size:.6rem;color:#94a3b8;letter-spacing:.08em;padding:6px 16px 2px;text-transform:uppercase;">Marketing &amp; Growth</div>

        <a href="{{ route('admin.sales.board') }}" class="nav-item {{ request()->is('admin/sales*') ? 'active' : '' }}">
            <i class="fas fa-funnel-dollar"></i>
            <span>Funil Comercial</span>
        </a>

        <a href="{{ route('prospecting.index') }}" class="nav-item {{ request()->is('prospecting*') ? 'active' : '' }}">
            <i class="fas fa-wand-magic-sparkles"></i>
            <span>Prospecção Global</span>
        </a>

        <a href="{{ route('admin.blog.index') }}" class="nav-item {{ request()->is('admin/blog*') ? 'active' : '' }}">
            <i class="fas fa-blog"></i>
            <span>Blog</span>
        </a>

        <a href="{{ route('admin.academy.index') }}" class="nav-item {{ request()->is('admin/academy*') ? 'active' : '' }}">
            <i class="fas fa-graduation-cap"></i>
            <span>Academy</span>
        </a>

        {{-- ── API & Integrações (dropdown) ── --}}
        @php
            $apiActive = request()->is('api-docs*') || request()->is('settings/api-tokens*') || request()->is('settings/webhooks*');
        @endphp
        <div class="nav-divider" style="font-size:.6rem;color:#94a3b8;letter-spacing:.08em;padding:6px 16px 2px;text-transform:uppercase;">Developers</div>

        <button class="nav-item w-100 border-0 bg-transparent text-start {{ $apiActive ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#navApiGroup" aria-expanded="{{ $apiActive ? 'true' : 'false' }}"
                style="cursor:pointer;">
            <i class="fas fa-code"></i>
            <span>API &amp; Integrações</span>
            <i class="fas fa-chevron-down ms-auto" style="font-size:.65rem;opacity:.5;transition:transform .2s;" id="navApiArrow"></i>
        </button>
        <div class="collapse {{ $apiActive ? 'show' : '' }}" id="navApiGroup">
            <a href="{{ route('api.docs') }}" class="nav-item ps-4 {{ request()->is('api-docs*') ? 'active' : '' }}" style="font-size:.82rem;">
                <i class="fas fa-book" style="font-size:.75rem;"></i>
                <span>Documentação API</span>
            </a>
            <a href="{{ route('settings.api-tokens') }}" class="nav-item ps-4 {{ request()->is('settings/api-tokens*') ? 'active' : '' }}" style="font-size:.82rem;">
                <i class="fas fa-key" style="font-size:.75rem;"></i>
                <span>API &amp; Tokens</span>
            </a>
            <a href="{{ route('settings.webhooks') }}" class="nav-item ps-4 {{ request()->is('settings/webhooks*') ? 'active' : '' }}" style="font-size:.82rem;">
                <i class="fas fa-webhook" style="font-size:.75rem;"></i>
                <span>Webhooks</span>
            </a>
        </div>

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
