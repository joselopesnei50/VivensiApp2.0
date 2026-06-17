<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $__gtmId  = \App\Models\SystemSetting::getValue('gtm_container_id');
        $__ga4Id  = \App\Models\SystemSetting::getValue('ga4_measurement_id');
    @endphp
    @if($__gtmId)
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $__gtmId }}');</script>
    <!-- End Google Tag Manager -->
    @endif
    @if($__ga4Id)
    <!-- Google Analytics GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $__ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $__ga4Id }}');
    </script>
    <!-- End Google Analytics -->
    @endif
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>try{var _adm={{ (auth()->check()&&auth()->user()->role==='super_admin')?'true':'false' }};var _k=_adm?'vivensi-admin-theme':'vivensi-theme';var _df=_adm?'light':'dark';document.documentElement.setAttribute('data-theme',localStorage.getItem(_k)||_df);}catch(e){document.documentElement.setAttribute('data-theme','dark')}</script>
    <title>{{ $title ?? config('app.name', 'Vivensi') }} — {{ __('ui.app_tagline') }}</title>
    
    <!-- SEO & Social Sharing -->
    <meta name="description" content="Vivensi App - A plataforma de gestão definitiva para ONGs, empresas e gestores. Controle financeiro, projetos e transparência com auxílio de IA.">
    <meta name="keywords" content="gestão financeira, ongs, projetos, saas, vivensi, transparência pública, lgpd financeiro">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/favicon.png') }}">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">

    {{-- F7: Cor de marca dinâmica por tenant --}}
    @auth
    @php $tenantBrand = auth()->user()->tenant; @endphp
    @if($tenantBrand?->brand_color)
    <style>
        :root {
            --primary-color: {{ $tenantBrand->brand_color }};
            --primary-color-rgb: {{ implode(',', sscanf($tenantBrand->brand_color, '#%02x%02x%02x') ?? [79,70,229]) }};
        }
    </style>
    @endif
    @endauth

    {{-- F2: Soketi / Laravel Echo Real-time dependencies --}}
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.0.1/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script>
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ config('broadcasting.connections.pusher.key') }}',
            cluster: 'mt1',
            wsHost: '{{ config('broadcasting.connections.pusher.options.host') }}',
            wsPort: {{ config('broadcasting.connections.pusher.options.port') }},
            forceTLS: {{ config('broadcasting.connections.pusher.options.useTLS') ? 'true' : 'false' }},
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            @auth authEndpoint: '{{ url("/broadcasting/auth") }}', @endauth
        });
    </script>
    {{-- F1: Onboarding Tour (Shepherd.js) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/css/shepherd.css"/>
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/js/shepherd.min.js"></script>

    @stack('styles')
</head>
@php
    $panelRole = auth()->check() ? auth()->user()->role : 'guest';
    $panelAttr = match($panelRole) {
        'ngo'         => 'ngo',
        'manager'     => 'manager',
        'common'      => 'mei',
        'super_admin' => 'admin',
        default       => 'guest',
    };
@endphp
<body data-panel="{{ $panelAttr }}">
@if($__gtmId)
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $__gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
@endif

<a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay" style="display: none;" onclick="toggleSidebar()"></div>

<style>
    body { pointer-events: auto; }
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        z-index: 1040;
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }
    .sidebar-overlay.show { opacity: 1; }
    @media (max-width: 768px) {
        .sidebar.mobile-open { z-index: 1050 !important; }
    }

    /* ── Accordion Menu Groups ─────────────────────────── */
    .menu-group { margin-bottom: 2px; }
    .menu-group-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #7e95ac;
        cursor: pointer;
        user-select: none;
        border-radius: 8px;
        transition: background 0.2s, color 0.2s;
        margin: 2px 6px 0 6px;
    }
    .menu-group-header:hover { background: rgba(255,255,255,0.05); color: #cbd5e1; }
    .menu-group-header.group-active { color: var(--primary-color, #4f46e5); }
    .menu-group-header .group-icon { width: 16px; text-align: center; font-size: 0.75rem; }
    .menu-group-header .group-arrow {
        margin-left: auto;
        font-size: 0.6rem;
        transition: transform 0.25s;
        opacity: 0.6;
    }
    .menu-group-header.collapsed .group-arrow { transform: rotate(-90deg); }
    .menu-group-items {
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .menu-group-items.collapsed { max-height: 0 !important; }
    .menu-divider {
        height: 1px;
        background: rgba(255,255,255,0.06);
        margin: 6px 14px;
    }
    /* ── Language Switcher ──────────────────────────────── */
    .lang-switcher { display: flex; justify-content: center; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.07); }
    .lang-btn {
        display: flex; align-items: center; gap: 6px;
        text-decoration: none; padding: 5px 10px;
        border-radius: 20px; background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    }
    .lang-btn { cursor: pointer; }
    .lang-btn:hover { background: rgba(255,255,255,0.12); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.25); }
    .lang-btn img { border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.3); display: block; }
    .lang-btn span { font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 0.5px; }
    .lang-btn-active { background: rgba(255,255,255,0.15) !important; border-color: rgba(255,255,255,0.3) !important; }
    .lang-btn-active span { color: white !important; }
    /* ── Sub-menu indent items ──────────────────────────── */
    .menu-sub-item { margin-left: 10px; }
    /* ── Sidebar user-view ──────────────────────────────── */
    .user-view {
        padding: 12px 16px;
        border-top: 1px solid rgba(255,255,255,0.07);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-view .user-avatar {
        width: 34px; height: 34px; flex-shrink: 0;
        background: var(--primary-color, #4f46e5);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; font-size: 0.8rem; color: white;
    }
    .user-view .user-info { flex: 1; min-width: 0; }
    .user-view .user-name { font-size: 0.78rem; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-view .user-logout { font-size: 0.65rem; color: var(--text-secondary); text-decoration: none; font-weight: 600; }
    .user-view .user-logout:hover { color: #ef4444; }
    .user-view .user-settings { width: 28px; height: 28px; background: var(--border-color); border: 1px solid var(--border-color); border-radius: 7px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: background 0.2s; flex-shrink: 0; }
    .user-view .user-settings:hover { opacity: 0.7; }
    .user-view .user-settings i { font-size: 0.7rem; color: var(--text-secondary); }

    /* ═══════════════════════════════════════════════════════════════════════
       Executive Sidebar — Super Admin Only  (.sidebar-sa)
       Scoped 100% — zero impacto em menus de clientes
       Padrão: Linear / Vercel / Stripe — contraste WCAG AA
    ══════════════════════════════════════════════════════════════════════ */

    /* ── Cabeçalhos de seção ──────────────────────────────────────────── */
    .sidebar-sa .menu-group-header {
        padding: 14px 16px 4px;
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: #64748b !important;    /* slate-500 — cinza médio sobre fundo branco */
        border-radius: 0;
        margin: 0;
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 7px;
        transition: color 0.15s;
        background: transparent !important;
    }
    .sidebar-sa .menu-group-header:hover { color: #334155 !important; }
    .sidebar-sa .menu-group-header.group-active { color: #4f46e5 !important; }
    .sidebar-sa .menu-group-header .group-icon { font-size: 0.65rem; width: 14px; text-align: center; }
    .sidebar-sa .menu-group-header .group-arrow {
        margin-left: auto; font-size: 0.55rem; color: #94a3b8;
        transition: transform 0.2s;
    }
    .sidebar-sa .menu-group-header.collapsed .group-arrow { transform: rotate(-90deg); }

    /* ── Itens do menu ────────────────────────────────────────────────── */
    .sidebar-sa .sidebar-menu ul { margin: 0; padding: 0; list-style: none; }
    .sidebar-sa .sidebar-menu ul li a {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 8px 12px 8px 16px !important;
        margin: 1px 8px !important;
        border-radius: 8px !important;
        font-size: 0.82rem !important;
        font-weight: 500 !important;
        color: #334155 !important;       /* slate-700 — cinza escuro sobre fundo branco */
        text-decoration: none !important;
        transition: background 0.12s, color 0.12s, border-color 0.12s !important;
        border-left: 2px solid transparent !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Força ícones com cor consistente — remove inline colors */
    .sidebar-sa .sidebar-menu ul li a i,
    .sidebar-sa .sidebar-menu ul li a .fab,
    .sidebar-sa .sidebar-menu ul li a .fas,
    .sidebar-sa .sidebar-menu ul li a .far {
        width: 16px !important;
        text-align: center !important;
        font-size: 0.78rem !important;
        flex-shrink: 0 !important;
        color: #64748b !important;       /* slate-500 — ícone cinza sobre fundo branco */
        transition: color 0.12s !important;
    }
    .sidebar-sa .sidebar-menu ul li a:hover {
        background: rgba(99,102,241,0.07) !important;
        color: #1e293b !important;
        border-left-color: rgba(99,102,241,0.5) !important;
    }
    .sidebar-sa .sidebar-menu ul li a:hover i,
    .sidebar-sa .sidebar-menu ul li a:hover .fab,
    .sidebar-sa .sidebar-menu ul li a:hover .fas { color: #4f46e5 !important; }
    .sidebar-sa .sidebar-menu ul li a.active {
        background: rgba(99,102,241,0.16) !important;
        color: #e0e7ff !important;
        border-left-color: #6366f1 !important;
        font-weight: 600 !important;
    }
    .sidebar-sa .sidebar-menu ul li a.active i,
    .sidebar-sa .sidebar-menu ul li a.active .fab,
    .sidebar-sa .sidebar-menu ul li a.active .fas { color: #818cf8 !important; }

    /* ── Divisores ────────────────────────────────────────────────────── */
    .sidebar-sa .menu-divider {
        height: 1px;
        background: rgba(255,255,255,0.06);
        margin: 2px 0;
    }

    /* ── Badges de notificação ────────────────────────────────────────── */
    .sa-badge {
        margin-left: auto;
        font-size: 0.6rem;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 20px;
        line-height: 1.5;
        flex-shrink: 0;
        background: rgba(99,102,241,0.25);
        color: #c7d2fe;
    }
    .sa-badge.sa-red   { background: rgba(239,68,68,0.25);  color: #fecaca; }
    .sa-badge.sa-amber { background: rgba(251,191,36,0.2);  color: #fde68a; }
    .sa-badge.sa-green { background: rgba(34,197,94,0.2);   color: #bbf7d0; }

    /* ── Widget Ao Vivo ───────────────────────────────────────────────── */
    .sa-live-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 8px 12px 4px;
        padding: 8px 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
    }
    .sa-pulse {
        width: 7px; height: 7px; border-radius: 50%;
        background: #22c55e; flex-shrink: 0;
        animation: sa-pulse 2s infinite;
    }
    @keyframes sa-pulse {
        0%,100% { opacity: 1; }
        50%      { opacity: 0.4; }
    }
    .sa-live-txt { font-size: 0.72rem; font-weight: 600; color: #64748b; flex: 1; }
    .sa-live-num { font-size: 0.78rem; font-weight: 800; color: #4ade80; min-width: 16px; text-align: right; }
</style>

@auth
<aside class="sidebar{{ auth()->user()->role === 'super_admin' ? ' sidebar-sa' : '' }}" id="mainSidebar">
    {{-- Collapse toggle --}}
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Recolher menu" aria-label="Recolher menu">
        <i class="fas fa-chevron-left collapse-icon" id="sidebarCollapseIcon"></i>
    </button>
    <div class="sidebar-header" style="justify-content: center; flex-direction: column; height: auto; padding: 18px 24px 14px;">
        <a href="{{ url('/dashboard') }}" class="logo" style="display: block; text-align: center;">
            <x-application-logo style="max-width: 108px; height: auto;" />
        </a>
        {{-- Badge de Painel --}}
        @auth
        @php
            $panelMeta = match(auth()->user()->role) {
                'ngo'         => ['label' => 'Terceiro Setor', 'icon' => 'fa-heart',          'cls' => 'panel-badge-ngo'],
                'manager'     => ['label' => 'Gestão',         'icon' => 'fa-briefcase',      'cls' => 'panel-badge-manager'],
                'common'      => ['label' => 'MEI / Pessoal',  'icon' => 'fa-store',          'cls' => 'panel-badge-mei'],
                'super_admin' => ['label' => 'Admin',          'icon' => 'fa-shield-halved',  'cls' => 'panel-badge-admin'],
                default       => ['label' => 'Vivensi',        'icon' => 'fa-circle',         'cls' => 'panel-badge-admin'],
            };
        @endphp
        <div class="panel-badge {{ $panelMeta['cls'] }}">
            <i class="fas {{ $panelMeta['icon'] }}"></i>
            <span>{{ $panelMeta['label'] }}</span>
        </div>
        @endauth

        {{-- Seletor de Idioma --}}
        @php $currentLocale = app()->getLocale(); @endphp
        <div class="lang-switcher">
            <form method="POST" action="{{ route('locale.set', 'pt_BR') }}" style="display:inline">@csrf
                <button type="submit" title="Português (Brasil)" class="lang-btn {{ $currentLocale === 'pt_BR' ? 'lang-btn-active' : '' }}">
                    <img loading="lazy" src="https://flagcdn.com/w40/br.png" width="22" height="15" alt="Brasil">
                    <span>PT</span>
                </button>
            </form>
            <form method="POST" action="{{ route('locale.set', 'en') }}" style="display:inline">@csrf
                <button type="submit" title="English" class="lang-btn {{ $currentLocale === 'en' ? 'lang-btn-active' : '' }}">
                    <img loading="lazy" src="https://flagcdn.com/w40/us.png" width="22" height="15" alt="English">
                    <span>EN</span>
                </button>
            </form>
            <form method="POST" action="{{ route('locale.set', 'es') }}" style="display:inline">@csrf
                <button type="submit" title="Español" class="lang-btn {{ $currentLocale === 'es' ? 'lang-btn-active' : '' }}">
                    <img loading="lazy" src="https://flagcdn.com/w40/es.png" width="22" height="15" alt="España">
                    <span>ES</span>
                </button>
            </form>
        </div>
    </div>
    <nav class="sidebar-menu">
        <ul>
            <li><a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fas fa-home"></i> Visão Geral</a></li>
            
            
            {{-- Academy Access removed from global and moved to specific roles below --}}            
            @if (auth()->user()->role == 'super_admin')
                {{-- ═══ MENU SUPER ADMIN — Executive Edition ═══ --}}
                @php
                    $sa_saas_active  = request()->is('admin') || request()->is('admin/tenants') || request()->routeIs('admin.plans.index');
                    $sa_team_active  = request()->routeIs('admin.team.index') || request()->routeIs('admin.chat') || request()->is('admin/support') || request()->routeIs('admin.bookings.*') || request()->routeIs('admin.executive.*');
                    $sa_cms_active   = request()->routeIs('admin.blog.index') || request()->routeIs('admin.testimonials.index') || request()->routeIs('admin.pages.index') || request()->routeIs('admin.academy.index') || request()->is('academy*') || request()->is('social-ai*');
                    $sa_wa_active     = request()->is('whatsapp/chat*') || request()->routeIs('whatsapp.broadcast.*') || request()->routeIs('whatsapp.optin.*') || request()->routeIs('whatsapp.instances') || request()->routeIs('whatsapp.templates') || request()->routeIs('whatsapp.automations.*') || request()->routeIs('whatsapp.settings') || request()->routeIs('whatsapp.labels.*');
                    $sa_growth_active = request()->routeIs('admin.email_logs') || request()->is('prospecting*') || request()->routeIs('admin.email_campaigns.*') || request()->is('admin/sales*');
                    $sa_infra_active  = request()->routeIs('admin.health') || request()->routeIs('admin.analytics') || request()->is('admin/settings') || request()->routeIs('admin.bot') || request()->routeIs('admin.lgpd.*');
                    $sa_api_active    = request()->is('api-docs*') || request()->is('settings/api-tokens*') || request()->is('settings/webhooks*');
                    // Badges de notificação
                    try {
                        $sa_badge_bookings = \App\Models\MeetingBooking::where('status','confirmed')->where('meeting_date','>=',today())->count();
                        $sa_badge_lgpd     = \App\Models\LgpdDataRequest::where('status','pending')->count();
                        $sa_badge_tasks    = \App\Models\Task::where('tenant_id',1)->whereNotIn('status',['done','completed'])->where(function($q){ $q->whereNotNull('due_date')->where('due_date','<',now()); })->count();
                    } catch(\Throwable $e) {
                        $sa_badge_bookings = 0; $sa_badge_lgpd = 0; $sa_badge_tasks = 0;
                    }
                @endphp

                {{-- ── SaaS & Métricas ──────────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_saas_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-chart-line group-icon"></i> SaaS &amp; Métricas
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_saas_active ? '300px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/admin') }}" class="{{ request()->is('admin') ? 'active' : '' }}"><i class="fas fa-gauge-high"></i> Visão Geral</a></li>
                            <li><a href="{{ url('/admin/tenants') }}" class="{{ request()->is('admin/tenants*') ? 'active' : '' }}"><i class="fas fa-building"></i> Organizações</a></li>
                            <li><a href="{{ route('admin.plans.index') }}" class="{{ request()->routeIs('admin.plans.index') ? 'active' : '' }}"><i class="fas fa-tags"></i> Planos</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── Equipe & Operações ───────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_team_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-users-cog group-icon"></i> Equipe &amp; Operações
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_team_active ? '280px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('admin.executive.index') }}" class="{{ request()->routeIs('admin.executive.*') ? 'active' : '' }}">
                                <i class="fas fa-briefcase"></i> Agenda Executiva
                                @if($sa_badge_tasks > 0)<span class="sa-badge sa-red">{{ $sa_badge_tasks }}</span>@endif
                            </a></li>
                            <li><a href="{{ route('admin.team.index') }}" class="{{ request()->routeIs('admin.team.index') ? 'active' : '' }}"><i class="fas fa-id-card"></i> Time Vivensi</a></li>
                            <li><a href="{{ route('admin.chat') }}" class="{{ request()->routeIs('admin.chat') ? 'active' : '' }}"><i class="fas fa-comments"></i> Chat Interno</a></li>
                            <li><a href="{{ url('/admin/support') }}" class="{{ request()->is('admin/support*') ? 'active' : '' }}"><i class="fas fa-headset"></i> Suporte &amp; Tickets</a></li>
                            <li><a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check"></i> Agenda de Reuniões
                                @if($sa_badge_bookings > 0)<span class="sa-badge sa-green">{{ $sa_badge_bookings }}</span>@endif
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── Conteúdo & CMS ───────────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_cms_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-layer-group group-icon"></i> Conteúdo &amp; CMS
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_cms_active ? '450px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('admin.blog.index') }}" class="{{ request()->routeIs('admin.blog.index') ? 'active' : '' }}"><i class="fas fa-blog"></i> Blog CMS</a></li>
                            <li><a href="{{ route('intelligence.territorial') }}" class="{{ request()->routeIs('intelligence.territorial') ? 'active' : '' }}"><i class="fas fa-map-location-dot"></i> Inteligência Territorial</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('admin.testimonials.index') }}" class="{{ request()->routeIs('admin.testimonials.index') ? 'active' : '' }}"><i class="fas fa-quote-left"></i> Depoimentos</a></li>
                            <li><a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.index') ? 'active' : '' }}"><i class="fas fa-file-alt"></i> Páginas (CMS)</a></li>
                            <li><a href="{{ route('admin.academy.index') }}" class="{{ request()->routeIs('admin.academy.index') ? 'active' : '' }}"><i class="fas fa-graduation-cap"></i> Academy</a></li>
                            <li><a href="{{ url('/academy') }}" class="{{ request()->is('academy*') ? 'active' : '' }}"><i class="fas fa-play-circle"></i> Ver como Aluno</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── WhatsApp ──────────────────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_wa_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fab fa-whatsapp group-icon" style="color:#25d366;"></i> WhatsApp
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_wa_active ? '380px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Chat & Atendimento</a></li>
                            <li><a href="{{ route('whatsapp.labels.index') }}" class="{{ request()->routeIs('whatsapp.labels.*') ? 'active' : '' }}"><i class="fas fa-tags"></i> Etiquetas</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->routeIs('whatsapp.broadcast.*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.optin.index') }}" class="{{ request()->routeIs('whatsapp.optin.*') ? 'active' : '' }}"><i class="fas fa-check-circle"></i> Opt-in & Campanhas</a></li>
                            <li><a href="{{ route('whatsapp.instances') }}" class="{{ request()->routeIs('whatsapp.instances') ? 'active' : '' }}"><i class="fas fa-plug"></i> Instâncias WA</a></li>
                            <li><a href="{{ route('whatsapp.settings') }}" class="{{ request()->routeIs('whatsapp.settings') ? 'active' : '' }}"><i class="fas fa-robot"></i> Chatbot & Config</a></li>
                            <li><a href="{{ route('whatsapp.templates') }}" class="{{ request()->routeIs('whatsapp.templates') ? 'active' : '' }}"><i class="fas fa-layer-group"></i> Templates</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->routeIs('whatsapp.automations.*') ? 'active' : '' }}"><i class="fas fa-bolt"></i> Automações</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── Marketing & Growth ───────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_growth_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-rocket group-icon"></i> Marketing &amp; Growth
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_growth_active ? '260px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('admin.sales.board') }}" class="{{ request()->is('admin/sales*') ? 'active' : '' }}"><i class="fas fa-funnel-dollar"></i> Funil Comercial</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção Global</a></li>
                            <li><a href="{{ route('admin.email_campaigns.index') }}" class="{{ request()->routeIs('admin.email_campaigns.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i> Campanhas de E-mail</a></li>
                            <li><a href="{{ route('admin.email_logs') }}" class="{{ request()->routeIs('admin.email_logs') ? 'active' : '' }}"><i class="fas fa-envelope-open-text"></i> Logs de E-mail</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── Infraestrutura & Compliance ──────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_infra_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-shield-halved group-icon"></i> Infra &amp; Compliance
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_infra_active ? '350px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                            <li><a href="{{ route('admin.health') }}" class="{{ request()->routeIs('admin.health') ? 'active' : '' }}"><i class="fas fa-heart-pulse"></i> Saúde do Servidor</a></li>
                            <li><a href="{{ url('/admin/settings') }}" class="{{ request()->is('admin/settings*') ? 'active' : '' }}"><i class="fas fa-sliders"></i> Configurações Globais</a></li>
                            <li><a href="{{ route('admin.bot') }}" class="{{ request()->routeIs('admin.bot') ? 'active' : '' }}"><i class="fas fa-robot"></i> Bot de Atendimento</a></li>
                            <li><a href="{{ route('admin.lgpd.index') }}" class="{{ request()->routeIs('admin.lgpd.*') ? 'active' : '' }}">
                                <i class="fas fa-scale-balanced"></i> Painel LGPD / DPO
                                @if($sa_badge_lgpd > 0)<span class="sa-badge sa-amber">{{ $sa_badge_lgpd }}</span>@endif
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- ── API & Dev ────────────────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_api_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-code group-icon"></i> API &amp; Dev
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_api_active ? '220px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('api.docs') }}" class="{{ request()->is('api-docs*') ? 'active' : '' }}"><i class="fas fa-book"></i> Documentação API</a></li>
                            <li><a href="{{ route('settings.api-tokens') }}" class="{{ request()->is('settings/api-tokens*') ? 'active' : '' }}"><i class="fas fa-key"></i> API &amp; Integrações</a></li>
                            <li><a href="{{ route('settings.webhooks') }}" class="{{ request()->is('settings/webhooks*') ? 'active' : '' }}"><i class="fas fa-bolt"></i> Webhooks</a></li>
                        </ul>
                    </div>
                </div>

            @elseif (auth()->user()->role == 'manager')
                {{-- ═══ MENU GESTOR — Agrupado ═══ --}}
                @php
                    $mgr_ops_active  = request()->is('projects*','manager/team*','manager/schedule*','manager/approvals*','manager/perfil-operacional*','manager/kanban*');
                    $mgr_fin_active  = request()->is('manager/contracts*','manager/reconciliation*');
                    $mgr_wa_active   = request()->is('whatsapp*');
                    $mgr_mkt_active  = request()->is('manager/landing-pages*','manager/email-campaigns*','marketing*','prospecting*','raffles*','social/accounts*','social-ai*','banners*');
                    $mgr_ai_active   = request()->is('smart-analysis*');
                    $mgr_acad_active = request()->is('academy*');
                @endphp

                {{-- Grupo: Projetos & Operações --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_ops_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-briefcase group-icon"></i> Projetos &amp; Operações
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_ops_active ? '300px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/projects') }}" class="{{ request()->is('projects*') ? 'active' : '' }}"><i class="fas fa-project-diagram"></i> Projetos</a></li>
                            <li><a href="{{ url('/manager/team') }}" class="{{ request()->is('manager/team*') ? 'active' : '' }}"><i class="fas fa-users"></i> Equipe &amp; RH</a></li>
                            <li><a href="{{ url('/manager/schedule') }}" class="{{ request()->is('manager/schedule*') ? 'active' : '' }}"><i class="fas fa-calendar-alt"></i> Agenda Corporativa</a></li>
                            <li><a href="{{ url('/manager/approvals') }}" class="{{ request()->is('manager/approvals*') ? 'active' : '' }}"><i class="fas fa-check-double"></i> Central de Aprovações</a></li>
                            <li><a href="{{ url('/manager/perfil-operacional') }}" class="{{ request()->is('manager/perfil-operacional*') ? 'active' : '' }}"><i class="fas fa-compass"></i> Perfil Operacional</a></li>
                            <li><a href="{{ url('/manager/kanban') }}" class="{{ request()->is('manager/kanban*') ? 'active' : '' }}"><i class="fas fa-columns"></i> Kanban Geral</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Contratos & Financeiro --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_fin_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-file-invoice-dollar group-icon"></i> Contratos &amp; Financeiro
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_fin_active ? '200px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/manager/contracts') }}" class="{{ request()->is('manager/contracts*') ? 'active' : '' }}"><i class="fas fa-file-signature"></i> Contratos Digitais</a></li>
                            <li><a href="{{ url('/manager/reconciliation') }}" class="{{ request()->is('manager/reconciliation*') ? 'active' : '' }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: WhatsApp --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_wa_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fab fa-whatsapp group-icon" style="color:#25d366;"></i> WhatsApp
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_wa_active ? '320px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Chat & Atendimento</a></li>
                            <li><a href="{{ route('whatsapp.labels.index') }}" class="{{ request()->routeIs('whatsapp.labels.*') ? 'active' : '' }}"><i class="fas fa-tags"></i> Etiquetas</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.optin.index') }}" class="{{ request()->routeIs('whatsapp.optin.*') ? 'active' : '' }}"><i class="fas fa-check-circle"></i> Opt-in & Campanhas</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-robot"></i> Chatbot & Config</a></li>
                            <li><a href="{{ url('/whatsapp/templates') }}" class="{{ request()->is('whatsapp/templates*') ? 'active' : '' }}"><i class="fas fa-layer-group"></i> Templates</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-bolt"></i> Automações</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_mkt_active ? '440px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('manager.email_campaigns.index') }}" class="{{ request()->is('manager/email-campaigns*') ? 'active' : '' }}"><i class="fas fa-envelope" style="color:#6366f1;"></i> E-mail Marketing</a></li>
                            <li><a href="{{ url('/manager/landing-pages') }}" class="{{ request()->is('manager/landing-pages*') ? 'active' : '' }}"><i class="fas fa-laptop-code"></i> Landing Pages</a></li>
                            <li><a href="{{ route('intelligence.territorial') }}" class="{{ request()->routeIs('intelligence.territorial') ? 'active' : '' }}"><i class="fas fa-map-location-dot" style="color: #10b981;"></i> Inteligência Territorial</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ route('raffles.index') }}" class="{{ request()->is('raffles*') ? 'active' : '' }}"><i class="fas fa-ticket-alt" style="color: #6366f1;"></i> Rifas Online</a></li>
                            <li><a href="{{ route('social.accounts') }}" class="{{ request()->is('social/accounts*') ? 'active' : '' }}"><i class="fas fa-share-nodes" style="color:#3b82f6;"></i> Redes Sociais</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Inteligência Artificial --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_ai_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-robot group-icon"></i> Inteligência Artificial
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_ai_active ? '150px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/smart-analysis') }}" class="{{ request()->is('smart-analysis*') ? 'active' : '' }}"><img loading="lazy" src="{{ asset('img/nova-ideintidade-bruce/icon-bruceIA.png') }}" alt="AI" style="width:20px;height:20px;border-radius:50%;object-fit:cover;margin-right:5px;"> Smart Analysis AI</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Academy --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_acad_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-graduation-cap group-icon"></i> Vivensi Academy
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_acad_active ? '150px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/academy') }}" class="{{ request()->is('academy*') ? 'active' : '' }}"><i class="fas fa-play-circle"></i> Acessar Academy</a></li>
                        </ul>
                    </div>
                </div>
            @elseif (auth()->user()->role == 'ngo' || (auth()->user()->tenant && auth()->user()->tenant->type == 'ngo'))
                {{-- ═══ MENU TERCEIRO SETOR (ONG) — Agrupado ═══ --}}
                @php
                    $ngo_capt_active   = request()->is('ngo/donors*','ngo/receipts*','ngo/grants*','ngo/sponsorship*','projects*');
                    $ngo_wa_active     = request()->is('whatsapp*');
                    $ngo_mkt_active    = request()->is('ngo/landing-pages*','ngo/email-campaigns*','marketing*','prospecting*','raffles*','social/accounts*','social-ai*','banners*');
                    $ngo_fin_active    = request()->is('transactions*','ngo/budget*','ngo/reconciliation*');
                    $ngo_people_active = request()->is('ngo/team*','ngo/hr*','ngo/beneficiaries*');
                    $ngo_pat_active    = request()->is('ngo/inventory*','ngo/assets*');
                    $ngo_jur_active    = request()->is('ngo/contracts*');
                    $ngo_rep_active    = request()->is('ngo/reports*','ngo/audit*','ngo/transparencia*');
                    $ngo_ai_active     = request()->is('smart-analysis*');
                @endphp

                {{-- Grupo: Projetos & Captação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_capt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-hand-holding-heart group-icon"></i> Projetos &amp; Captação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_capt_active ? '350px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/projects') }}" class="{{ request()->is('projects*') ? 'active' : '' }}"><i class="fas fa-project-diagram"></i> Projetos Ativos</a></li>
                            <li><a href="{{ url('/ngo/donors') }}" class="{{ request()->is('ngo/donors*') ? 'active' : '' }}"><i class="fas fa-heart"></i> Doadores</a></li>
                            <li><a href="{{ url('/ngo/receipts') }}" class="{{ request()->is('ngo/receipts*') ? 'active' : '' }}"><i class="fas fa-receipt"></i> Recibos</a></li>
                            <li><a href="{{ url('/ngo/grants') }}" class="{{ request()->is('ngo/grants*') ? 'active' : '' }}"><i class="fas fa-file-signature"></i> Editais &amp; Convênios</a></li>
                            <li><a href="{{ url('/ngo/sponsorships') }}" class="{{ request()->is('ngo/sponsorships*') ? 'active' : '' }}"><i class="fas fa-handshake"></i> CRM Patrocínios</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: WhatsApp --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_wa_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fab fa-whatsapp group-icon" style="color:#25d366;"></i> WhatsApp
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_wa_active ? '320px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Chat & Atendimento</a></li>
                            <li><a href="{{ route('whatsapp.labels.index') }}" class="{{ request()->routeIs('whatsapp.labels.*') ? 'active' : '' }}"><i class="fas fa-tags"></i> Etiquetas</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.optin.index') }}" class="{{ request()->routeIs('whatsapp.optin.*') ? 'active' : '' }}"><i class="fas fa-check-circle"></i> Opt-in & Campanhas</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-robot"></i> Chatbot & Config</a></li>
                            <li><a href="{{ url('/whatsapp/templates') }}" class="{{ request()->is('whatsapp/templates*') ? 'active' : '' }}"><i class="fas fa-layer-group"></i> Templates</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-bolt"></i> Automações</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_mkt_active ? '440px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('ngo.email_campaigns.index') }}" class="{{ request()->is('ngo/email-campaigns*') ? 'active' : '' }}"><i class="fas fa-envelope" style="color:#6366f1;"></i> E-mail Marketing</a></li>
                            <li><a href="{{ url('/ngo/landing-pages') }}" class="{{ request()->is('ngo/landing-pages*') ? 'active' : '' }}"><i class="fas fa-magic"></i> Construtor de LPs</a></li>
                            <li><a href="{{ route('intelligence.territorial') }}" class="{{ request()->routeIs('intelligence.territorial') ? 'active' : '' }}"><i class="fas fa-map-location-dot" style="color: #10b981;"></i> Inteligência Territorial</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ route('raffles.index') }}" class="{{ request()->is('raffles*') ? 'active' : '' }}"><i class="fas fa-ticket-alt" style="color: #6366f1;"></i> Rifas Online</a></li>
                            <li><a href="{{ route('social.accounts') }}" class="{{ request()->is('social/accounts*') ? 'active' : '' }}"><i class="fas fa-share-nodes" style="color:#3b82f6;"></i> Redes Sociais</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Financeiro --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_fin_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-coins group-icon"></i> Financeiro
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_fin_active ? '300px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/transactions') }}" class="{{ request()->is('transactions') ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i> Fluxo de Caixa</a></li>
                            <li><a href="{{ url('/transactions/create') }}" class="{{ request()->is('transactions/create') ? 'active' : '' }}"><i class="fas fa-plus-circle" style="color:#10b981;"></i> Nova Transação</a></li>
                            <li><a href="{{ url('/ngo/budget') }}" class="{{ request()->is('ngo/budget*') ? 'active' : '' }}"><i class="fas fa-chart-pie"></i> Orçamento Anual</a></li>
                            <li><a href="{{ url('/ngo/reconciliation') }}" class="{{ request()->is('ngo/reconciliation*') ? 'active' : '' }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Pessoas & Beneficiários --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_people_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-users group-icon"></i> Pessoas &amp; Beneficiários
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_people_active ? '400px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/ngo/team') }}" class="{{ request()->is('ngo/team*') ? 'active' : '' }}"><i class="fas fa-id-card"></i> Equipe da ONG</a></li>
                            <li><a href="{{ url('/ngo/hr') }}" class="{{ request()->is('ngo/hr*') ? 'active' : '' }}"><i class="fas fa-id-badge"></i> RH &amp; Voluntários</a></li>
                            <li><a href="{{ url('/ngo/beneficiaries') }}" class="{{ request()->is('ngo/beneficiaries') ? 'active' : '' }}"><i class="fas fa-hand-holding-heart"></i> Beneficiários</a></li>
                            <li class="menu-sub-item"><a href="{{ url('/ngo/beneficiaries/insights') }}" class="{{ request()->is('ngo/beneficiaries/insights*') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Indicadores Sociais</a></li>
                            <li class="menu-sub-item"><a href="{{ url('/ngo/beneficiaries/reports/annual') }}" class="{{ request()->is('ngo/beneficiaries/reports/annual*') ? 'active' : '' }}"><i class="fas fa-file-alt"></i> Relatório Anual</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Patrimônio & Estoque --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_pat_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-boxes group-icon"></i> Patrimônio &amp; Estoque
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_pat_active ? '200px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/ngo/inventory') }}" class="{{ request()->is('ngo/inventory*') ? 'active' : '' }}"><i class="fas fa-box-open"></i> Almoxarifado e Estoque</a></li>
                            <li><a href="{{ url('/ngo/assets') }}" class="{{ request()->is('ngo/assets*') ? 'active' : '' }}"><i class="fas fa-building"></i> Patrimônio</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Contratos & Jurídico --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_jur_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-file-contract group-icon"></i> Contratos &amp; Jurídico
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_jur_active ? '150px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/ngo/contracts') }}" class="{{ request()->is('ngo/contracts*') ? 'active' : '' }}"><i class="fas fa-file-contract"></i> Contratos Digitais</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Relatórios & Auditoria --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_rep_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-chart-bar group-icon"></i> Relatórios &amp; Auditoria
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_rep_active ? '250px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/ngo/reports/dre') }}" class="{{ request()->is('ngo/reports*') ? 'active' : '' }}"><i class="fas fa-file-invoice-dollar"></i> Relatórios (DRE)</a></li>
                            <li><a href="{{ url('/ngo/audit') }}" class="{{ request()->is('ngo/audit*') ? 'active' : '' }}"><i class="fas fa-eye"></i> Central de Auditoria</a></li>
                            <li><a href="{{ url('/ngo/transparencia') }}" class="{{ request()->is('ngo/transparencia*') ? 'active' : '' }}"><i class="fas fa-landmark"></i> Portal Transparência</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Inteligência Artificial --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_ai_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-robot group-icon"></i> Inteligência Artificial
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_ai_active ? '150px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/smart-analysis') }}" class="{{ request()->is('smart-analysis*') ? 'active' : '' }}"><img loading="lazy" src="{{ asset('img/nova-ideintidade-bruce/icon-bruceIA.png') }}" alt="AI" style="width:20px;height:20px;border-radius:50%;object-fit:cover;margin-right:5px;"> Smart Analysis AI</a></li>
                        </ul>
                    </div>
                </div>
            @else
                <!-- Menu Comum / MEI / Empresa -->
                @php
                    $mei_fin_active  = request()->is('personal/reconciliation*','personal/budget*','transactions*');
                    $mei_wa_active   = request()->is('whatsapp*');
                    $mei_mkt_active  = request()->is('marketing*','prospecting*','social/accounts*','social-ai*','manager/landing-pages*');
                    $mei_crm_active  = request()->is('personal/clients*');
                @endphp

                {{-- Grupo: CRM & Clientes --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mei_crm_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-users group-icon"></i> CRM &amp; Clientes
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mei_crm_active ? '150px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/personal/clients') }}" class="{{ request()->is('personal/clients*') ? 'active' : '' }}"><i class="fas fa-address-book"></i> Meus Clientes</a></li>
                            <li><a href="{{ url('/personal/clients/create') }}" class="{{ request()->is('personal/clients/create') ? 'active' : '' }}"><i class="fas fa-user-plus" style="color:#10b981;"></i> Novo Cliente</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: WhatsApp --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mei_wa_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fab fa-whatsapp group-icon" style="color:#25d366;"></i> WhatsApp
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mei_wa_active ? '300px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Chat & Atendimento</a></li>
                            <li><a href="{{ route('whatsapp.labels.index') }}" class="{{ request()->routeIs('whatsapp.labels.*') ? 'active' : '' }}"><i class="fas fa-tags"></i> Etiquetas</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.optin.index') }}" class="{{ request()->routeIs('whatsapp.optin.*') ? 'active' : '' }}"><i class="fas fa-check-circle"></i> Opt-in & Campanhas</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-robot"></i> Chatbot & Config</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-bolt"></i> Automações</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mei_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mei_mkt_active ? '300px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/manager/landing-pages') }}" class="{{ request()->is('manager/landing-pages*') ? 'active' : '' }}"><i class="fas fa-laptop-code"></i> Landing Pages</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ route('social.accounts') }}" class="{{ request()->is('social/accounts*') ? 'active' : '' }}"><i class="fas fa-share-nodes" style="color:#3b82f6;"></i> Redes Sociais</a></li>
                        </ul>
                    </div>
                </div>
                <div class="menu-divider"></div>

                {{-- Grupo: Gestão Financeira --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mei_fin_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-coins group-icon"></i> Gestão Financeira
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mei_fin_active ? '200px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/transactions') }}" class="{{ request()->is('transactions*') ? 'active' : '' }}"><i class="fas fa-exchange-alt"></i> Fluxo de Caixa</a></li>
                            <li><a href="{{ url('/personal/reconciliation') }}" class="{{ request()->is('personal/reconciliation*') ? 'active' : '' }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                            <li><a href="{{ url('/personal/budget') }}" class="{{ request()->is('personal/budget*') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Planejamento Anual</a></li>
                        </ul>
                    </div>
                </div>
            @endif
            


            <li><a href="{{ url('/support') }}" class="{{ request()->is('support') ? 'active' : '' }}"><i class="fas fa-life-ring"></i> Suporte</a></li>
            <li><a href="{{ url('/profile') }}" class="{{ request()->is('profile') ? 'active' : '' }}"><i class="fas fa-cog"></i> Configurações</a></li>
            @if(in_array(auth()->user()->role, ['manager', 'ngo', 'super_admin']))
            <li><a href="{{ route('settings.branding') }}" class="{{ request()->routeIs('settings.branding') ? 'active' : '' }}"><i class="fas fa-palette"></i> Identidade Visual</a></li>
            <li><a href="{{ route('api.docs') }}" class="{{ request()->routeIs('api.docs') ? 'active' : '' }}"><i class="fas fa-book-open"></i> Documentação API</a></li>
            <li><a href="{{ route('settings.api-tokens') }}" class="{{ request()->routeIs('settings.api-tokens*') ? 'active' : '' }}"><i class="fas fa-plug"></i> API &amp; Integrações</a></li>
            <li><a href="{{ route('settings.webhooks') }}" class="{{ request()->routeIs('settings.webhooks*') ? 'active' : '' }}"><i class="fas fa-webhook"></i> Webhooks</a></li>
            @endif
        </ul>
    </nav>
    {{-- User Card (Linear-style) --}}
    @php
        $ucName    = auth()->user()->name ?? 'Usuário';
        $ucInitials = strtoupper(implode('', array_map(fn($w) => substr($w,0,1), array_slice(explode(' ', $ucName), 0, 2))));
        $ucRoleLabel = match(auth()->user()->role) {
            'super_admin' => 'Admin',
            'manager'     => 'Gestor',
            'ngo'         => 'ONG',
            'common'      => 'MEI',
            default       => 'Usuário',
        };
    @endphp
    <div class="sidebar-user-card" id="sucTrigger" onclick="toggleSucDropdown()">
        <div class="suc-avatar user-avatar-wrap">{{ $ucInitials }}</div>
        <div class="suc-info">
            <div class="suc-name">{{ $ucName }}</div>
            <div class="suc-role">{{ $ucRoleLabel }}</div>
        </div>
        <i class="fas fa-chevron-up suc-arrow"></i>
        <div class="suc-dropdown" id="sucDropdown">
            <a href="{{ url('/profile') }}"><i class="fas fa-user-circle"></i> {{ __('ui.profile') }}</a>
            <a href="{{ url('/profile') }}#settings"><i class="fas fa-cog"></i> {{ __('ui.settings') }}</a>
            <div class="suc-divider"></div>
            <a href="#" class="danger" onclick="event.preventDefault(); document.getElementById('global-logout-form').submit();">
                <i class="fas fa-sign-out-alt"></i> {{ __('ui.logout') }}
            </a>
        </div>
    </div>
</aside>
@endauth

<main id="main-content" class="main-content" style="{{ !auth()->check() ? 'margin-left: 0; width: 100%;' : '' }}">
    
    <!-- ══ COMMAND TOPBAR ══════════════════════════════════════════════ -->
    <div id="topbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0 36px; height: 68px; background: var(--topbar-bg, #0f172a); border-bottom: 1px solid var(--topbar-border, rgba(255,255,255,0.06)); position: sticky; top: 0; z-index: 900; margin: -32px -32px 32px -32px;">

        <!-- Esquerda: Menu mobile + Identidade do painel -->
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="mobile-menu-btn" onclick="toggleSidebar()" style="color: rgba(255,255,255,0.5); font-size: 1.2rem; cursor: pointer; display: none;">
                <i class="fas fa-bars"></i>
            </div>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('global-logout-form').submit();" class="mobile-logout-btn" style="display: none; color: #ef4444; font-size: 1.2rem;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
            <style>
                @media (max-width: 768px) {
                    .mobile-logout-btn { display: block !important; }
                    .mobile-menu-btn { display: block !important; }
                    /* Topbar: padding e margin compensam o padding do main-content mobile (20px) */
                    #topbar {
                        padding: 0 14px !important;
                        margin: -20px -20px 20px -20px !important;
                        height: 60px !important;
                    }
                    /* Esconde elementos que ocupam espaço desnecessário na topbar mobile */
                    #topbar-title { display: none !important; }
                    #live-clock { display: none !important; }
                    #topbar-search-cmd { display: none !important; }
                    #global-search-trigger { display: none !important; }
                    #topbar-trial { display: none !important; }
                    #export-container { display: none !important; }
                    #user-profile-trigger { display: none !important; }
                    /* Dropdown de notificações: posição fixa em mobile para não sair da tela */
                    #notif-dropdown {
                        position: fixed !important;
                        top: 62px !important;
                        left: 8px !important;
                        right: 8px !important;
                        width: auto !important;
                    }
                }
                @media (max-width: 480px) {
                    #topbar { padding: 0 10px !important; }
                }
            </style>

            <!-- Linha vertical accent + texto do painel -->
            <div id="topbar-title" style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 3px; height: 32px; background: var(--primary-color, #4f46e5); border-radius: 2px;"></div>
                <div>
                    @php $role = auth()->user()?->role; @endphp
                    <div style="font-size: 0.6rem; font-weight: 800; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2px;">
                        @if($role == 'manager') Gestão de Projetos
                        @elseif($role == 'ngo') Terceiro Setor
                        @elseif($role == 'super_admin') Super Administrador
                        @else Vivensi App
                        @endif
                    </div>
                    <div style="font-size: 1rem; font-weight: 800; color: white; letter-spacing: -0.3px; line-height: 1;">
                        @if($role == 'manager') Central de Comando — Projetos
                        @elseif($role == 'ngo') Central de Comando — ONG
                        @elseif($role == 'super_admin') Central de Comando — Admin
                        @else Vivensi Platform
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Centro: Trial warning -->
        @auth
            @php $tenant = auth()->user()->tenant; @endphp
            @if($tenant && $tenant->subscription_status === 'trialing' && $tenant->trial_ends_at)
            <div id="topbar-trial">
                @php $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($tenant->trial_ends_at), false); @endphp
                <div style="display: flex; align-items: center; gap: 12px; background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.2); padding: 8px 16px; border-radius: 10px;">
                    <i class="fas fa-clock" style="color: #fbbf24; font-size: 0.85rem;"></i>
                    <span style="color: #fbbf24; font-size: 0.8rem; font-weight: 700;">
                        {{ $daysLeft > 0 ? "$daysLeft dias de teste grátis" : "Teste encerrado hoje!" }}
                    </span>
                    @if($tenant->plan_id)
                        <a href="{{ route('checkout.index', ['plan_id' => $tenant->plan_id]) }}" style="background: #fbbf24; color: #0f172a; font-size: 0.7rem; font-weight: 900; padding: 4px 12px; border-radius: 8px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.5px;">Ativar</a>
                    @endif
                </div>
            </div>
            @endif
        @endauth

        <!-- Direita: Ações -->
        <div style="display: flex; align-items: center; gap: 8px;">

            <!-- Relógio ao vivo -->
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); padding: 8px 14px; border-radius: 10px; font-size: 0.78rem; color: rgba(255,255,255,0.4); font-weight: 700; letter-spacing: 0.5px; font-family: 'JetBrains Mono', monospace;" id="live-clock">
                <i class="fas fa-circle" style="font-size: 0.4rem; color: #10b981; animation: pulse-green 2s infinite;"></i>
                <span id="clock-time">--:--</span>
            </div>

            <!-- Ctrl+K Command Palette trigger -->
            <button id="topbar-search-cmd" onclick="openCmdPalette()"
                    style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 7px 14px; cursor: pointer; transition: background 0.2s; color: rgba(255,255,255,0.4); font-size: 0.75rem; font-weight: 600;"
                    onmouseover="this.style.background='rgba(255,255,255,0.09)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.04)'"
                    title="Busca rápida (Ctrl+K)">
                <i class="fas fa-search" style="font-size: 0.7rem;"></i>
                <span class="d-none d-md-inline">Buscar</span>
                <kbd style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; padding: 1px 5px; font-size: 0.6rem; font-family: inherit; color: rgba(255,255,255,0.3);">⌘K</kbd>
            </button>

            <!-- F5: Dark Mode Toggle -->
            <button id="theme-toggle"
                    style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'"
                    title="Alternar Tema (Dark/Light)">
                <i id="theme-icon" class="fas fa-sun" style="font-size: 1rem; color: #fbbf24;"></i>
            </button>

            <!-- Busca Global Ctrl+K -->
            @auth
            <button id="global-search-trigger"
                    onclick="openGlobalSearch()"
                    style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); padding: 8px 14px; border-radius: 10px; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.04)'"
                    title="Busca Global (Ctrl+K)">
                <i class="fas fa-magnifying-glass" style="font-size: 0.8rem; color: rgba(255,255,255,0.4);"></i>
                <span style="font-size: 0.72rem; color: rgba(255,255,255,0.3); font-weight: 700;">Buscar</span>
                <kbd style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 5px; padding: 1px 6px; font-size: 0.6rem; color: rgba(255,255,255,0.3); font-family: monospace;">⌘K</kbd>
            </button>
            @endauth

            <!-- Notificações -->
            <div style="position: relative; cursor: pointer;" id="notification-bell" onclick="toggleNotifications()">
                <div id="notification-bell-btn" style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer;"
                     onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                     onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    @php $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->unread()->count() : 0; @endphp
                    <i class="fas fa-bell" style="font-size: 1rem; color: rgba(255,255,255,0.5);"></i>
                    <span id="notif-badge" style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 9px; display: {{ $unreadCount > 0 ? 'flex' : 'none' }}; align-items: center; justify-content: center; border: 2px solid #0f172a; font-weight: 900;">{{ $unreadCount }}</span>
                </div>

                <!-- Dropdown Notificações -->
                <div id="notif-dropdown" style="display: none; position: absolute; top: 50px; right: 0; width: 340px; background: #1e293b; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); z-index: 1060; border: 1px solid rgba(255,255,255,0.08); overflow: hidden;">
                    <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: white; font-size: 0.9rem; font-weight: 800;">Notificações</strong>
                        <button onclick="markAllRead()" style="background: none; border: none; color: #818cf8; font-size: 0.72rem; cursor: pointer; font-weight: 700;">Marcar todas como lidas</button>
                    </div>
                    <div id="notif-list" style="max-height: 340px; overflow-y: auto;">
                        <div style="padding: 20px; text-align: center; color: rgba(255,255,255,0.3); font-size: 0.9rem;">Carregando...</div>
                    </div>
                    <div style="padding: 10px; text-align: center; background: rgba(255,255,255,0.03); border-top: 1px solid rgba(255,255,255,0.06);">
                        <a href="{{ route('notifications.index') }}" style="font-size: 0.8rem; color: #818cf8; text-decoration: none; font-weight: 700;">Ver todas</a>
                    </div>
                </div>
            </div>

            <!-- F4: Exportar Relatórios -->
            <div style="position: relative;" id="export-container">
                <button id="export-dropdown-trigger"
                        style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.05)'"
                        title="Exportar Dados"
                        onclick="toggleExportMenu()">
                    <i class="fas fa-file-export" style="color: rgba(255,255,255,0.5); font-size: 1rem;"></i>
                </button>
                
                <div id="export-menu" style="display: none; position: absolute; top: 50px; right: 0; width: 220px; background: #1e293b; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); z-index: 1000; border: 1px solid rgba(255,255,255,0.08); overflow: hidden;">
                    <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.75rem; color: #818cf8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Exportar para:</div>
                    <a href="javascript:window.print()" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: white; text-decoration: none; font-size: 0.85rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-file-pdf" style="color: #ef4444; width: 16px;"></i> PDF Profissional
                    </a>
                    <a href="{{ url('/export/csv') }}" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: white; text-decoration: none; font-size: 0.85rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-file-csv" style="color: #10b981; width: 16px;"></i> Planilha (CSV)
                    </a>
                </div>
            </div>

            <!-- Configurações -->
            @auth
            <a id="topbar-settings" href="{{ url('/profile') }}" style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;"
               onmouseover="this.style.background='rgba(255,255,255,0.1)'"
               onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                <i class="fas fa-cog" style="color: rgba(255,255,255,0.5); font-size: 1rem;"></i>
            </a>

            <!-- Avatar do usuário -->
            <div id="user-profile-trigger"
                 style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 6px 12px 6px 6px; cursor: pointer;"
                 onmouseover="this.style.background='rgba(255,255,255,0.08)'"
                 onmouseout="this.style.background='rgba(255,255,255,0.04)'">
                <div style="width: 30px; height: 30px; background: var(--primary-color, #4f46e5); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.75rem; color: white;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; color: white; line-height: 1;">{{ explode(' ', auth()->user()->name ?? 'Usuário')[0] }}</div>
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('global-logout-form').submit();" style="font-size: 0.65rem; color: rgba(255,255,255,0.35); text-decoration: none; font-weight: 600; display: block; line-height: 1; margin-top: 2px;">Sair</a>
                </div>
            </div>
            @endauth
        </div>
    </div>

    <style>
        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
    <script>
        (function() {
            function updateClock() {
                const el = document.getElementById('clock-time');
                if (!el) return;
                const now = new Date();
                el.textContent = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            updateClock();
            setInterval(updateClock, 1000);
        })();
    </script>


    <!-- Flash Messages -->
    <div style="padding: 20px 30px 0 30px;">
        @if(session('success'))
            <div class="alert alert-success" style="background-color: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" style="background-color: #fee2e2; border: 1px solid #f87171; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning" style="background-color: #fef9c3; border: 1px solid #fde047; color: #854d0e; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fas fa-triangle-exclamation me-2"></i> {{ session('warning') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info" style="background-color: #e0f2fe; border: 1px solid #7dd3fc; color: #0c4a6e; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fas fa-circle-info me-2"></i> {{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="background-color: #fee2e2; border: 1px solid #f87171; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Injection -->
    @yield('content')

    <footer style="margin-top: 50px; text-align: center; color: #999; font-size: 0.8rem; padding-bottom: 20px;">
        &copy; {{ date('Y') }} <strong>Vivensi app</strong>.
    </footer>
</main>

@auth
{{-- F6: Modal de Busca Global --}}
@include('partials.global-search')
@endauth

<style>
    /* btn-premium agora definido em design-system.css */
    .btn-premium-icon {
        background: rgba(255,255,255,0.2);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .notif-bell {
        font-size: 1.2rem;
        color: #555;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
    }

    /* Toast notifications (lightweight, no dependencies) */
    .vivensi-toast-wrap {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 2000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }
    .vivensi-toast {
        pointer-events: auto;
        width: 340px;
        max-width: calc(100vw - 36px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        transform: translateY(10px);
        opacity: 0;
        transition: all .18s ease;
    }
    .vivensi-toast.show {
        transform: translateY(0);
        opacity: 1;
    }
    .vivensi-toast__bar {
        height: 4px;
        background: linear-gradient(90deg, #4f46e5, #22c55e);
    }
    .vivensi-toast__body {
        padding: 12px 14px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .vivensi-toast__icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #f0f4ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }
    .vivensi-toast__title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.9rem;
        margin: 0 0 2px 0;
    }
    .vivensi-toast__msg {
        color: #64748b;
        font-size: 0.85rem;
        line-height: 1.35;
        margin: 0;
    }
    .vivensi-toast__actions {
        margin-top: 8px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .vivensi-toast__btn {
        font-size: 0.8rem;
        padding: 6px 10px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #334155;
        text-decoration: none;
        cursor: pointer;
    }
    .vivensi-toast__btn.primary {
        border-color: #c7d2fe;
        background: #eef2ff;
        color: #3730a3;
        font-weight: 700;
    }
</style>

<script>
    const __vivensiIsAuth  = {{ auth()->check() ? 'true' : 'false' }};
    const __vivensiUserId  = {{ auth()->id() ?? 'null' }};
    let __vivensiLastUnread = null;
    let __vivensiToastCooldownUntil = 0;
    let __vivensiEchoConnected = false;

    // Safety check on load to prevent stuck overlays
    // Safety check on load to prevent stuck overlays
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('sidebarOverlay');
        if(overlay) {
            // Forcefully hide overlay
            overlay.classList.remove('show');
            overlay.style.display = 'none';
        }
        
        // Remove any stuck bootstrap modal backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(b => b.remove());

        // Restore body state
        document.body.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
        document.body.style.pointerEvents = 'auto'; // Critical for "freeze" issue
    });

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('mobile-open');
        const isOpen = sidebar.classList.contains('mobile-open');

        if (isOpen) {
            overlay.style.display = 'block';
            // Small delay to allow display:block to apply before adding opacity class
            setTimeout(() => overlay.classList.add('show'), 10);
            document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
        } else {
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
            setTimeout(() => {
                if (!sidebar.classList.contains('mobile-open')) {
                    overlay.style.display = 'none';
                }
            }, 300);
        }
    }
    
    // Close sidebar when clicking overlay
    if(document.getElementById('sidebarOverlay')) {
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if(sidebar.classList.contains('mobile-open')) {
                toggleSidebar();
            }
        });
    }

    function toggleNotifications() {
        const dropdown = document.getElementById('notif-dropdown');
        const isVisible = dropdown.style.display === 'block';

        // Fechar outros dropdowns se houver
        dropdown.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            fetchNotifications();
        }
    }

    async function fetchNotifications() {
        if (!__vivensiIsAuth) return;
        const list = document.getElementById('notif-list');
        try {
            const response = await fetch('{{ url("/api/notifications") }}');
            const data = await response.json();

            list.innerHTML = '';
            const notifications = Array.isArray(data) ? data : (data.notifications || []);
            const unreadCount = Array.isArray(data) ? null : (data.unread_count ?? null);
            if (typeof unreadCount === 'number') {
                renderBadge(unreadCount);
            }

            if (notifications.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 0.9rem;">Nenhuma notificação nova.</div>';
                return;
            }

            notifications.forEach(n => {
                const item = document.createElement('div');
                item.style.padding = '12px 15px';
                item.style.borderBottom = '1px solid #f1f5f9';
                item.style.cursor = 'pointer';
                item.style.background = n.read_at ? 'transparent' : '#f0f4ff';
                item.innerHTML = `
                    <div style="font-weight: 600; color: #1e293b; font-size: 0.85rem; margin-bottom: 3px;">${n.title}</div>
                    <div style="color: #64748b; font-size: 0.8rem; line-height: 1.4;">${n.message}</div>
                    <div style="color: #94a3b8; font-size: 0.7rem; margin-top: 5px;">${new Date(n.created_at).toLocaleString('pt-BR')}</div>
                `;
                item.onclick = (e) => {
                    e.stopPropagation();
                    markAsRead(n.id, n.link);
                };
                list.appendChild(item);
            });
        } catch (e) {
            list.innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444; font-size: 0.8rem;">Erro ao carregar notificações.</div>';
        }
    }

    async function markAsRead(id, link) {
        if (!__vivensiIsAuth) return;
        await fetch(`{{ url("/api/notifications") }}/${id}/read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        await updateBadge();
        if (link) {
            window.location.href = link;
        } else {
            fetchNotifications();
        }
    }

    async function markAllRead() {
        if (!__vivensiIsAuth) return;
        await fetch(`{{ url("/api/notifications/read-all") }}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        fetchNotifications();
        await updateBadge();
    }

    function renderBadge(count) {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        const n = Math.max(0, parseInt(count || 0, 10));
        badge.textContent = n > 99 ? '99+' : String(n);
        badge.style.display = n > 0 ? 'flex' : 'none';
    }

    async function updateBadge() {
        if (!__vivensiIsAuth) return;
        try {
            const res = await fetch('{{ url("/api/notifications/unread-count") }}');
            const data = await res.json();
            const nextUnread = (data.unread_count ?? 0);
            const prevUnread = (__vivensiLastUnread === null) ? nextUnread : __vivensiLastUnread;
            __vivensiLastUnread = nextUnread;

            renderBadge(nextUnread);

            // If unread count increased, show a toast with the latest notification.
            if (nextUnread > prevUnread) {
                const now = Date.now();
                if (now >= __vivensiToastCooldownUntil && !document.hidden) {
                    __vivensiToastCooldownUntil = now + 8000; // avoid spam
                    showLatestNotificationToast();
                }
            }
        } catch (e) {
            // Silent: badge refresh shouldn't break the UI
        }
    }

    async function showLatestNotificationToast() {
        try {
            const res = await fetch('{{ url("/api/notifications") }}?limit=1');
            const data = await res.json();
            const notifications = Array.isArray(data) ? data : (data.notifications || []);
            const n = notifications[0];
            if (!n) return;

            showToast({
                title: n.title || 'Nova notificação',
                message: n.message || '',
                link: n.link || null
            });
        } catch (e) {
            // Silent
        }
    }

    function showToast({ title, message, link }) {
        let wrap = document.getElementById('vivensi-toast-wrap');
        if (!wrap) return;

        const toast = document.createElement('div');
        toast.className = 'vivensi-toast';
        toast.innerHTML = `
            <div class="vivensi-toast__bar"></div>
            <div class="vivensi-toast__body">
                <div class="vivensi-toast__icon"><i class="fas fa-bell"></i></div>
                <div style="flex:1; min-width:0;">
                    <div class="vivensi-toast__title"></div>
                    <p class="vivensi-toast__msg"></p>
                    <div class="vivensi-toast__actions">
                        ${link ? `<a class="vivensi-toast__btn primary" href="${link}">Abrir</a>` : ``}
                        <a class="vivensi-toast__btn" href="{{ route('notifications.index') }}">Ver todas</a>
                        <button type="button" class="vivensi-toast__btn" data-close="1">Fechar</button>
                    </div>
                </div>
            </div>
        `;
        toast.querySelector('.vivensi-toast__title').textContent = String(title || '');
        toast.querySelector('.vivensi-toast__msg').textContent = String(message || '');

        toast.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-close]');
            if (btn) {
                e.preventDefault();
                removeToast(toast);
            }
        });

        wrap.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));

        // Auto-remove.
        setTimeout(() => removeToast(toast), 7000);
    }

    function removeToast(toast) {
        if (!toast) return;
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 200);
    }

    // F2: Real-time listener via Laravel Echo
    document.addEventListener('DOMContentLoaded', function() {
        if (!__vivensiIsAuth) return;

        // Badge inicial + polling de fallback (30s quando WebSocket cai, 2min quando conectado)
        updateBadge();
        let __pollInterval = setInterval(() => {
            if (!document.hidden) updateBadge();
        }, __vivensiEchoConnected ? 120000 : 30000);

        // WebSocket em tempo real via Laravel Echo
        if (__vivensiUserId && window.Echo) {
            try {
                window.Echo.private(`notifications.${__vivensiUserId}`)
                    .listen('NotificationCreated', (e) => {
                        __vivensiEchoConnected = true;
                        clearInterval(__pollInterval);
                        __pollInterval = setInterval(() => {
                            if (!document.hidden) updateBadge();
                        }, 120000);

                        updateBadge();

                        if (!document.hidden) {
                            showToast({
                                title:   e.title   || 'Nova notificação',
                                message: e.message || '',
                                link:    e.link    || null
                            });
                        }

                        const dropdown = document.getElementById('notif-dropdown');
                        if (dropdown && dropdown.style.display === 'block') {
                            fetchNotifications();
                        }
                    })
                    .error((err) => {
                        console.warn('[Notificações] WebSocket falhou, mantendo polling de 30s.', err);
                    });
            } catch (err) {
                console.warn('[Notificações] Echo indisponível, mantendo polling de 30s.', err);
            }
        }
    });

    // F4: Export Menu toggle
    function toggleExportMenu() {
        const menu = document.getElementById('export-menu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    // Close menus when clicking outside
    window.addEventListener('click', function(e) {
        const exportMenu = document.getElementById('export-menu');
        const exportTrigger = document.getElementById('export-dropdown-trigger');
        if (exportMenu && exportTrigger && !exportTrigger.contains(e.target) && !exportMenu.contains(e.target)) {
            exportMenu.style.display = 'none';
        }
        
        const dropdown = document.getElementById('notif-dropdown');
        const bell = document.getElementById('notification-bell');
        if (dropdown && bell && !bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });


    // ── Accordion Sidebar Groups ────────────────────────────────
    function toggleGroup(header) {
        const items = header.nextElementSibling;
        const isCollapsed = header.classList.contains('collapsed');
        if (isCollapsed) {
            header.classList.remove('collapsed');
            items.style.maxHeight = items.scrollHeight + 'px';
            // Scroll sidebar so the header + its items are visible after expansion
            setTimeout(() => {
                const menu = document.querySelector('.sidebar-menu');
                const headerRect = header.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                // If header is below the visible area of the menu, scroll to it
                if (headerRect.top < menuRect.top || headerRect.top > menuRect.bottom - 60) {
                    menu.scrollTo({ top: menu.scrollTop + (headerRect.top - menuRect.top) - 16, behavior: 'smooth' });
                }
            }, 60);
        } else {
            header.classList.add('collapsed');
            items.style.maxHeight = '0';
        }
    }

    // Fix server-rendered max-height for expanded groups (prevents items being clipped)
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.menu-group-header:not(.collapsed)').forEach(function(header) {
            const items = header.nextElementSibling;
            if (items && items.classList.contains('menu-group-items')) {
                items.style.maxHeight = items.scrollHeight + 'px';
            }
        });
    });

    // Scroll sidebar to show the active menu item on page load
    document.addEventListener('DOMContentLoaded', function () {
        const activeLink = document.querySelector('.sidebar-menu a.active');
        if (activeLink) {
            const menu = document.querySelector('.sidebar-menu');
            setTimeout(() => {
                const linkRect = activeLink.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                if (linkRect.bottom > menuRect.bottom || linkRect.top < menuRect.top) {
                    menu.scrollTo({ top: menu.scrollTop + (linkRect.top - menuRect.top) - (menu.clientHeight / 2) + (linkRect.height / 2), behavior: 'smooth' });
                }
            }, 200);
        }
    });
</script>

    @auth
        @include('partials.chat_widget')
    @endauth

    <div id="vivensi-toast-wrap" class="vivensi-toast-wrap" aria-live="polite" aria-atomic="true"></div>

{{-- ── Bruce AI Floating Chat (Phase 5) ──────────────────── --}}
@auth
<style>
.bruce-fab{position:fixed;bottom:28px;right:28px;z-index:8000;display:flex;flex-direction:column;align-items:flex-end;gap:12px}
.bruce-fab-btn{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;box-shadow:0 8px 24px rgba(99,102,241,0.4);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s;color:#fff;font-size:1.3rem}
.bruce-fab-btn:hover{transform:scale(1.08);box-shadow:0 12px 30px rgba(99,102,241,0.5)}
.bruce-fab-btn .bruce-notif{position:absolute;top:-4px;right:-4px;width:14px;height:14px;background:#10b981;border-radius:50%;border:2px solid #fff;animation:bruce-pulse 2s infinite}
@keyframes bruce-pulse{0%,100%{opacity:1}50%{opacity:0.5}}
.bruce-panel{width:360px;max-height:520px;background:#0f172a;border:1px solid rgba(255,255,255,0.08);border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,0.35);display:none;flex-direction:column;overflow:hidden}
.bruce-panel.open{display:flex}
.bruce-panel-head{padding:16px 20px;background:linear-gradient(135deg,#1e1b4b,#1e293b);display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,0.06)}
.bruce-panel-head img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.15)}
.bruce-panel-head .bruce-info{flex:1}
.bruce-panel-head .bruce-name{font-size:.9rem;font-weight:800;color:#fff}
.bruce-panel-head .bruce-status{font-size:.65rem;color:#10b981;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.bruce-panel-head .bruce-close{background:rgba(255,255,255,0.08);border:none;border-radius:8px;width:28px;height:28px;color:rgba(255,255,255,0.5);cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center}
.bruce-panel-head .bruce-close:hover{background:rgba(239,68,68,0.2);color:#ef4444}
.bruce-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;max-height:340px}
.bruce-messages::-webkit-scrollbar{width:4px}
.bruce-messages::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:4px}
.bruce-msg{max-width:85%;font-size:.82rem;line-height:1.5;padding:10px 14px;border-radius:14px;animation:bruce-fadein .2s}
@keyframes bruce-fadein{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.bruce-msg.user{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:14px 14px 4px 14px;align-self:flex-end}
.bruce-msg.bot{background:rgba(255,255,255,0.07);color:#e2e8f0;border-radius:14px 14px 14px 4px;align-self:flex-start}
.bruce-msg.typing{display:flex;gap:4px;align-items:center;padding:12px 16px}
.bruce-msg.typing span{width:6px;height:6px;background:rgba(255,255,255,0.4);border-radius:50%;animation:bruce-bounce 1.2s infinite}
.bruce-msg.typing span:nth-child(2){animation-delay:.2s}
.bruce-msg.typing span:nth-child(3){animation-delay:.4s}
@keyframes bruce-bounce{0%,80%,100%{transform:scale(0)}40%{transform:scale(1)}}
.bruce-input-row{padding:12px 16px;border-top:1px solid rgba(255,255,255,0.06);display:flex;gap:8px}
.bruce-input{flex:1;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:9px 14px;color:#fff;font-size:.82rem;font-family:inherit;outline:none;resize:none}
.bruce-input::placeholder{color:rgba(255,255,255,0.3)}
.bruce-input:focus{border-color:rgba(99,102,241,0.5)}
.bruce-send{background:#6366f1;border:none;border-radius:10px;width:36px;height:36px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;transition:background .2s}
.bruce-send:hover{background:#4f46e5}
.bruce-send:disabled{background:rgba(255,255,255,0.1);cursor:not-allowed}
.bruce-clear{background:none;border:none;color:rgba(255,255,255,0.25);font-size:.65rem;cursor:pointer;padding:4px 8px;border-radius:6px}
.bruce-clear:hover{color:rgba(239,68,68,0.7)}
</style>

<div class="bruce-fab" id="bruceFab">
    <div class="bruce-panel" id="brucePanel">
        <div class="bruce-panel-head">
            <img loading="lazy" src="{{ asset('img/nova-ideintidade-bruce/icon-bruceIA.png') }}" alt="Bruce" onerror="this.style.display='none'">
            <div class="bruce-info">
                <div class="bruce-name">Bruce AI</div>
                <div class="bruce-status">● Online — DeepSeek</div>
            </div>
            <button class="bruce-clear" onclick="bruceClear()" title="Limpar conversa"><i class="fas fa-trash-alt"></i></button>
            <button class="bruce-close" onclick="bruceToggle()" title="Fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="bruce-messages" id="bruceMessages">
            <div class="bruce-msg bot">Olá! Sou o Bruce, assistente do Vivensi. Tenho acesso aos dados da sua conta em tempo real — finanças, projetos, tarefas e muito mais. Como posso ajudar?</div>
        </div>
        <div class="bruce-input-row">
            <textarea class="bruce-input" id="bruceInput" placeholder="Pergunte sobre suas finanças, projetos..." rows="1" onkeydown="bruceKeydown(event)"></textarea>
            <button class="bruce-send" id="bruceSend" onclick="bruceSend()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <button class="bruce-fab-btn" onclick="bruceToggle()" title="Bruce AI — Assistente Inteligente" style="position:relative">
        <i class="fas fa-robot"></i>
        <span class="bruce-notif"></span>
    </button>
</div>

<script>
function bruceToggle(){
    const p=document.getElementById('brucePanel');
    p.classList.toggle('open');
    if(p.classList.contains('open')) document.getElementById('bruceInput').focus();
}
function bruceKeydown(e){
    if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();bruceSend();}
}
async function bruceSend(){
    const input=document.getElementById('bruceInput');
    const msg=input.value.trim();
    if(!msg)return;

    bruceAddMsg(msg,'user');
    input.value='';
    input.style.height='auto';

    const sendBtn=document.getElementById('bruceSend');
    sendBtn.disabled=true;

    const typing=document.createElement('div');
    typing.className='bruce-msg typing bot';
    typing.id='bruceTyping';
    typing.innerHTML='<span></span><span></span><span></span>';
    document.getElementById('bruceMessages').appendChild(typing);
    bruceScroll();

    try{
        const res=await fetch('/api/bruce/chat',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body:JSON.stringify({message:msg})
        });
        const data=await res.json();
        document.getElementById('bruceTyping')?.remove();
        if(data.error){bruceAddMsg('⚠️ '+data.error,'bot');}
        else{bruceAddMsg(data.reply,'bot');}
    }catch(e){
        document.getElementById('bruceTyping')?.remove();
        bruceAddMsg('Erro de conexão. Tente novamente.','bot');
    }finally{
        sendBtn.disabled=false;
        input.focus();
    }
}
function bruceAddMsg(text,role){
    const div=document.createElement('div');
    div.className='bruce-msg '+role;
    // Markdown básico: **bold** e \n → <br>
    div.innerHTML=text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
    document.getElementById('bruceMessages').appendChild(div);
    bruceScroll();
}
function bruceScroll(){
    const m=document.getElementById('bruceMessages');
    m.scrollTop=m.scrollHeight;
}
async function bruceClear(){
    await fetch('/api/bruce/chat/history',{method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
    document.getElementById('bruceMessages').innerHTML='<div class="bruce-msg bot">Conversa reiniciada. Como posso ajudar?</div>';
}
// Auto-resize textarea
document.getElementById('bruceInput')?.addEventListener('input',function(){
    this.style.height='auto';
    this.style.height=Math.min(this.scrollHeight,100)+'px';
});
</script>
@endauth

{{-- ── Command Palette (Ctrl+K) ──────────────────────────── --}}
<div class="cmd-overlay" id="cmdOverlay" onclick="closeCmdPalette(event)">
    <div class="cmd-palette" onclick="event.stopPropagation()">
        <div class="cmd-input-wrap">
            <i class="fas fa-search"></i>
            <input class="cmd-input" id="cmdInput" type="text" placeholder="Buscar página, ação..." autocomplete="off">
        </div>
        <div class="cmd-results" id="cmdResults"></div>
        <div class="cmd-footer">
            <span class="cmd-key"><kbd>↑</kbd><kbd>↓</kbd> navegar</span>
            <span class="cmd-key"><kbd>Enter</kbd> abrir</span>
            <span class="cmd-key"><kbd>Esc</kbd> fechar</span>
        </div>
    </div>
</div>
    
    
    <!-- Bootstrap 5 JS Bundle (Required for Modals, Dropdowns, Tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- F1: Tour Guiado Script -->
    <script src="{{ asset('js/vivensi-tour.js') }}"></script>

    <script>
        // F5: Theme Toggle Logic
        (function() {
            const toggleBtn = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const htmlModel = document.documentElement;
            const isAdmin = document.body.getAttribute('data-panel') === 'admin';
            const storageKey = isAdmin ? 'vivensi-admin-theme' : 'vivensi-theme';
            const defaultTheme = isAdmin ? 'light' : 'dark';

            function setTheme(theme) {
                htmlModel.setAttribute('data-theme', theme);
                localStorage.setItem(storageKey, theme);
                if (theme === 'dark') {
                    themeIcon.className = 'fas fa-sun';
                    themeIcon.style.color = '#fbbf24';
                } else {
                    themeIcon.className = 'fas fa-moon';
                    themeIcon.style.color = 'rgba(255,255,255,0.5)';
                }
            }

            const savedTheme = localStorage.getItem(storageKey) || defaultTheme;
            setTheme(savedTheme);

            toggleBtn.addEventListener('click', () => {
                const currentTheme = htmlModel.getAttribute('data-theme');
                setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>

    <!-- Global Logout Form -->
    <form id="global-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

    <!-- Cookie Consent Banner (LGPD) -->
    @include('partials.cookie-banner')

    @stack('scripts')

<script>
// ── Sidebar collapse ───────────────────────────────────────────────────
(function() {
    const sidebar = document.getElementById('mainSidebar');
    const main    = document.querySelector('.main-content');
    const btn     = document.getElementById('sidebarCollapseBtn');
    if (!sidebar || !btn) return;

    const COLLAPSED_KEY = 'vivensi_sb_collapsed';
    function applyState(collapsed) {
        if (collapsed) {
            sidebar.classList.add('sb-collapsed');
            if (main) { main.classList.remove('sb-expanded'); main.classList.add('sb-collapsed'); }
        } else {
            sidebar.classList.remove('sb-collapsed');
            if (main) { main.classList.add('sb-expanded'); main.classList.remove('sb-collapsed'); }
        }
    }
    applyState(localStorage.getItem(COLLAPSED_KEY) === '1');
    btn.addEventListener('click', function() {
        const now = sidebar.classList.contains('sb-collapsed');
        localStorage.setItem(COLLAPSED_KEY, now ? '0' : '1');
        applyState(!now);
    });

    // Add data-tooltip to sidebar links for collapsed mode
    document.querySelectorAll('.sidebar-menu a').forEach(function(a) {
        const txt = a.textContent.trim().split('\n')[0].trim();
        if (txt) a.setAttribute('data-tooltip', txt);
    });
})();

// ── User card dropdown ─────────────────────────────────────────────────
function toggleSucDropdown() {
    const dropdown = document.getElementById('sucDropdown');
    const trigger  = document.getElementById('sucTrigger');
    if (!dropdown) return;
    const open = dropdown.classList.toggle('open');
    trigger.classList.toggle('open', open);
}
document.addEventListener('click', function(e) {
    const trigger = document.getElementById('sucTrigger');
    if (trigger && !trigger.contains(e.target)) {
        document.getElementById('sucDropdown')?.classList.remove('open');
        trigger.classList.remove('open');
    }
});

// ── Command Palette ────────────────────────────────────────────────────
const CMD_ITEMS = [
    @auth
    { label: 'Dashboard',        url: '{{ url("/dashboard") }}',               icon: 'fa-home' },
    { label: 'Transações',       url: '{{ url("/transactions") }}',             icon: 'fa-wallet' },
    { label: 'Nova Transação',   url: '{{ url("/transactions/create") }}',      icon: 'fa-plus-circle' },
    { label: 'Projetos',         url: '{{ url("/projects") }}',                 icon: 'fa-folder-open' },
    { label: 'Tarefas',          url: '{{ url("/tasks") }}',                    icon: 'fa-check-square' },
    { label: 'WhatsApp CRM',     url: '{{ url("/whatsapp/chat") }}',            icon: 'fa-comment-dots' },
    { label: 'Etiquetas WhatsApp', url: '{{ route("whatsapp.labels.index") }}', icon: 'fa-tags' },
    { label: 'Clientes',         url: '{{ url("/clients") }}',                  icon: 'fa-users' },
    { label: 'Meu Perfil',       url: '{{ url("/profile") }}',                  icon: 'fa-user-circle' },
    @if(auth()->user()->role === 'ngo')
    { label: 'Beneficiários',    url: '{{ url("/ngo/beneficiaries") }}',        icon: 'fa-heart' },
    { label: 'Doadores',         url: '{{ url("/ngo/donors") }}',               icon: 'fa-hand-holding-heart' },
    { label: 'Editais',          url: '{{ url("/ngo/grants") }}',               icon: 'fa-file-contract' },
    @endif
    @if(in_array(auth()->user()->role, ['manager','ngo','common']))
    { label: 'Marketing IA',     url: '{{ route("marketing.index") }}',         icon: 'fa-brain' },
    { label: 'Disparo em Massa', url: '{{ route("whatsapp.broadcast.index") }}',icon: 'fa-paper-plane' },
    @endif
    @if(auth()->user()->role === 'super_admin')
    { label: 'Painel Admin',     url: '{{ url("/admin") }}',                    icon: 'fa-shield-halved' },
    { label: 'Organizações',     url: '{{ url("/admin/tenants") }}',            icon: 'fa-building' },
    @endif
    @endauth
];

let cmdActiveIdx = -1;

function openCmdPalette() {
    document.getElementById('cmdOverlay').classList.add('open');
    const input = document.getElementById('cmdInput');
    input.value = '';
    cmdActiveIdx = -1;
    renderCmdResults('');
    setTimeout(() => input.focus(), 50);
}
function closeCmdPalette(e) {
    if (!e || e.target === document.getElementById('cmdOverlay')) {
        document.getElementById('cmdOverlay').classList.remove('open');
    }
}
function renderCmdResults(q) {
    const results = document.getElementById('cmdResults');
    const filtered = q.trim()
        ? CMD_ITEMS.filter(i => i.label.toLowerCase().includes(q.toLowerCase()))
        : CMD_ITEMS;

    if (!filtered.length) {
        results.innerHTML = '<div class="cmd-empty">Nenhum resultado para "' + q + '"</div>';
        return;
    }
    results.innerHTML = filtered.map((item, idx) =>
        `<a href="${item.url}" class="cmd-item${idx === cmdActiveIdx ? ' active' : ''}">
            <i class="fas ${item.icon}"></i> ${item.label}
        </a>`
    ).join('');
}
document.getElementById('cmdInput')?.addEventListener('input', function() {
    cmdActiveIdx = -1;
    renderCmdResults(this.value);
});
document.getElementById('cmdInput')?.addEventListener('keydown', function(e) {
    const items = document.querySelectorAll('#cmdResults .cmd-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        cmdActiveIdx = Math.min(cmdActiveIdx + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        cmdActiveIdx = Math.max(cmdActiveIdx - 1, 0);
    } else if (e.key === 'Enter' && cmdActiveIdx >= 0) {
        e.preventDefault();
        items[cmdActiveIdx]?.click();
        return;
    } else if (e.key === 'Escape') {
        closeCmdPalette();
        return;
    }
    renderCmdResults(this.value);
    items.forEach((el, i) => el.classList.toggle('active', i === cmdActiveIdx));
    items[cmdActiveIdx]?.scrollIntoView({ block: 'nearest' });
});
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const overlay = document.getElementById('cmdOverlay');
        overlay.classList.contains('open') ? closeCmdPalette() : openCmdPalette();
    }
    if (e.key === 'Escape') closeCmdPalette();
});

// Focus trap for custom modal-overlay elements (canvas, kanban)
// Bootstrap modals already handle their own focus trap natively.
(function () {
    const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

    let trapped = null, keyHandler = null;

    function trap(modal) {
        if (trapped === modal) return;
        release();
        trapped = modal;
        const nodes = Array.from(modal.querySelectorAll(FOCUSABLE)).filter(
            n => getComputedStyle(n).display !== 'none' && !n.closest('[hidden]')
        );
        if (!nodes.length) return;
        const first = nodes[0], last = nodes[nodes.length - 1];
        first.focus();
        keyHandler = function (e) {
            if (e.key !== 'Tab') return;
            if (e.shiftKey) { if (document.activeElement === first) { e.preventDefault(); last.focus(); } }
            else            { if (document.activeElement === last)  { e.preventDefault(); first.focus(); } }
        };
        modal.addEventListener('keydown', keyHandler);
    }

    function release() {
        if (trapped && keyHandler) trapped.removeEventListener('keydown', keyHandler);
        trapped = null; keyHandler = null;
    }

    function visible(el) {
        return el.classList.contains('open') || (el.style.display !== '' && el.style.display !== 'none');
    }

    new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            const el = m.target;
            if (!el.classList || !el.classList.contains('modal-overlay')) return;
            visible(el) ? trap(el) : (trapped === el && release());
        });
    }).observe(document.body, { attributes: true, attributeFilter: ['style', 'class'], subtree: true });
})();

// Prevent double-submit: disable submit buttons on form submission
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.dataset.noDoubleSubmit === 'false') return;

    const btns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    btns.forEach(function(btn) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-spinner fa-spin';
        }
        if (btn.dataset.loadingText) {
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + btn.dataset.loadingText;
        }
    });

    // Re-enable after 15s as fallback (e.g. server-side validation error reloads page)
    setTimeout(function() {
        btns.forEach(function(btn) { btn.disabled = false; });
    }, 15000);
}, true);
</script>
</body>
</html>

