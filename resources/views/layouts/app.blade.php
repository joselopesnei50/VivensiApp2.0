<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'VIVENSI App - Gestão Financeira Inteligente' }}</title>
    
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
<body>

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
        color: #475569;
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
    .lang-btn:hover { background: rgba(255,255,255,0.12); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.25); }
    .lang-btn img { border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.3); display: block; }
    .lang-btn span { font-size: 0.65rem; font-weight: 700; color: rgba(255,255,255,0.55); text-transform: uppercase; letter-spacing: 0.5px; }
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

    /* ═══ Executive Sidebar — Super Admin Only (.sidebar-sa) ═══════════════
       Completely scoped — zero impact on manager/ngo/common menus          */

    /* Group headers → flat section labels (Linear/Stripe style) */
    .sidebar-sa .menu-group-header {
        padding: 10px 16px 3px;
        font-size: 0.57rem;
        font-weight: 900;
        letter-spacing: 1.5px;
        color: rgba(100,116,139,0.55);
        border-radius: 0;
        margin: 4px 0 2px;
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: color 0.15s;
    }
    .sidebar-sa .menu-group-header:hover { color: rgba(148,163,184,0.75); background: transparent; }
    .sidebar-sa .menu-group-header.group-active { color: rgba(165,180,252,0.65); }
    .sidebar-sa .menu-group-header .group-icon { width: 13px; text-align: center; font-size: 0.62rem; }
    .sidebar-sa .menu-group-header .group-arrow { margin-left: auto; font-size: 0.5rem; opacity: 0.4; transition: transform 0.25s; }
    .sidebar-sa .menu-group-header.collapsed .group-arrow { transform: rotate(-90deg); }
    .sidebar-sa .menu-group-header.group-active .group-arrow { opacity: 0.6; }

    /* Menu items → executive precision */
    .sidebar-sa .sidebar-menu ul { margin: 0; padding: 0; }
    .sidebar-sa .sidebar-menu ul li a {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 6px 10px 6px 14px;
        margin: 1px 8px;
        border-radius: 7px;
        font-size: 0.79rem;
        font-weight: 600;
        color: rgba(148,163,184,0.8);
        text-decoration: none;
        transition: all 0.12s;
        border-left: 2px solid transparent;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-sa .sidebar-menu ul li a i,
    .sidebar-sa .sidebar-menu ul li a .fab {
        width: 15px;
        text-align: center;
        font-size: 0.76rem;
        flex-shrink: 0;
        opacity: 0.65;
        transition: opacity 0.12s;
    }
    .sidebar-sa .sidebar-menu ul li a:hover {
        background: rgba(255,255,255,0.05);
        color: rgba(226,232,240,0.95);
        border-left-color: rgba(99,102,241,0.35);
    }
    .sidebar-sa .sidebar-menu ul li a:hover i,
    .sidebar-sa .sidebar-menu ul li a:hover .fab { opacity: 1; }
    .sidebar-sa .sidebar-menu ul li a.active {
        background: rgba(99,102,241,0.15);
        color: #a5b4fc;
        border-left-color: #6366f1;
        font-weight: 700;
    }
    .sidebar-sa .sidebar-menu ul li a.active i,
    .sidebar-sa .sidebar-menu ul li a.active .fab { opacity: 1; color: #818cf8; }

    /* Dividers → ultra subtle */
    .sidebar-sa .menu-divider { margin: 1px 0; background: rgba(255,255,255,0.04); height: 1px; }

    /* Notification badges */
    .sa-badge {
        margin-left: auto;
        font-size: 0.58rem;
        font-weight: 900;
        padding: 2px 6px;
        border-radius: 10px;
        background: rgba(99,102,241,0.25);
        color: #a5b4fc;
        line-height: 1.5;
        flex-shrink: 0;
    }
    .sa-badge.sa-red   { background: rgba(239,68,68,0.2);  color: #fca5a5; }
    .sa-badge.sa-amber { background: rgba(245,158,11,0.2); color: #fcd34d; }
    .sa-badge.sa-green { background: rgba(34,197,94,0.15); color: #86efac; }

    /* ── Live Users Card ──────────────────────────────────────────────── */
    .sa-live-card {
        margin: 6px 10px 10px;
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 11px 13px;
        transition: border-color 0.2s;
    }
    .sa-live-card:hover { border-color: rgba(99,102,241,0.25); }
    .sa-live-hdr { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .sa-pulse {
        width: 7px; height: 7px; border-radius: 50%; background: #22c55e; flex-shrink: 0;
        box-shadow: 0 0 0 0 rgba(34,197,94,0.5);
        animation: sa-heartbeat 2.2s infinite;
    }
    @keyframes sa-heartbeat {
        0%   { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
        70%  { box-shadow: 0 0 0 7px rgba(34,197,94,0); }
        100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .sa-live-lbl { font-size: 0.6rem; font-weight: 800; color: rgba(148,163,184,0.6); text-transform: uppercase; letter-spacing: 1.2px; flex: 1; }
    .sa-live-cnt { font-size: 0.78rem; font-weight: 900; color: #4ade80; }
    .sa-live-row { display: flex; align-items: center; gap: 7px; padding: 4px 0; border-top: 1px solid rgba(255,255,255,0.04); }
    .sa-live-av {
        width: 22px; height: 22px; border-radius: 6px;
        background: rgba(99,102,241,0.28); color: #a5b4fc;
        font-size: 0.6rem; font-weight: 900;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .sa-live-nfo { flex: 1; min-width: 0; }
    .sa-live-nm  { font-size: 0.7rem; font-weight: 700; color: rgba(226,232,240,0.88); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sa-live-tn  { font-size: 0.6rem; color: rgba(100,116,139,0.55); display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sa-live-ago { font-size: 0.58rem; color: rgba(100,116,139,0.5); white-space: nowrap; flex-shrink: 0; }
    .sa-live-empty { font-size: 0.68rem; color: rgba(100,116,139,0.45); text-align: center; padding: 6px 0 2px; }
</style>

@auth
<aside class="sidebar{{ auth()->user()->role === 'super_admin' ? ' sidebar-sa' : '' }}">
    <div class="sidebar-header" style="justify-content: center; flex-direction: column; height: auto; padding: 18px 24px 14px;">
        <a href="{{ url('/dashboard') }}" class="logo" style="display: block; text-align: center;">
            <x-application-logo style="max-width: 108px; height: auto;" />
        </a>
        {{-- Seletor de Idioma --}}
        <div class="lang-switcher">
            <a href="#" title="Português (Brasil)" class="lang-btn">
                <img src="https://flagcdn.com/w40/br.png" srcset="https://flagcdn.com/w80/br.png 2x" width="22" height="15" alt="Brasil">
                <span>PT</span>
            </a>
            <a href="#" title="Español" class="lang-btn">
                <img src="https://flagcdn.com/w40/es.png" srcset="https://flagcdn.com/w80/es.png 2x" width="22" height="15" alt="España">
                <span>ES</span>
            </a>
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
                    $sa_mkt_active   = request()->routeIs('admin.email_logs') || request()->routeIs('whatsapp.broadcast.index') || request()->is('prospecting*') || request()->routeIs('admin.email_campaigns.*');
                    $sa_infra_active = request()->routeIs('admin.health') || request()->is('admin/settings') || request()->routeIs('admin.bot') || request()->routeIs('admin.lgpd.*');
                    // Badges de notificação
                    try {
                        $sa_badge_bookings = \App\Models\MeetingBooking::where('status','confirmed')->where('meeting_date','>=',today())->count();
                        $sa_badge_lgpd     = \App\Models\LgpdDataRequest::where('status','pending')->count();
                        $sa_badge_tasks    = \App\Models\Task::where('tenant_id',1)->whereNotIn('status',['done','completed'])->where(function($q){ $q->whereNotNull('due_date')->where('due_date','<',now()); })->count();
                    } catch(\Throwable $e) {
                        $sa_badge_bookings = 0; $sa_badge_lgpd = 0; $sa_badge_tasks = 0;
                    }
                @endphp

                {{-- ── Live Users Card ─────────────────────────────────── --}}
                <div class="sa-live-card">
                    <div class="sa-live-hdr">
                        <span class="sa-pulse"></span>
                        <span class="sa-live-lbl">Ao Vivo</span>
                        <span class="sa-live-cnt" id="saLiveCnt">—</span>
                    </div>
                    <div id="saLiveList"><div class="sa-live-empty">Carregando...</div></div>
                </div>

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

                {{-- ── Comunicação & Growth ─────────────────────────────── --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $sa_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-rocket group-icon"></i> Comunicação &amp; Growth
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $sa_mkt_active ? '250px' : '0' }};">
                        <ul>
                            <li><a href="{{ route('admin.email_campaigns.index') }}" class="{{ request()->routeIs('admin.email_campaigns.*') ? 'active' : '' }}"><i class="fas fa-bullhorn"></i> Campanhas de E-mail</a></li>
                            <li><a href="{{ route('admin.email_logs') }}" class="{{ request()->routeIs('admin.email_logs') ? 'active' : '' }}"><i class="fas fa-envelope-open-text"></i> Logs de E-mail</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção Global</a></li>
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
                            <li><a href="{{ route('admin.health') }}" class="{{ request()->routeIs('admin.health') ? 'active' : '' }}"><i class="fas fa-heart-pulse"></i> Saúde do Servidor</a></li>
                            <li><a href="{{ url('/admin/settings') }}" class="{{ request()->is('admin/settings*') ? 'active' : '' }}"><i class="fas fa-sliders"></i> Configurações Globais</a></li>
                            <li><a href="{{ route('admin.bot') }}" class="{{ request()->routeIs('admin.bot') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Command Bot (WA)</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-headset"></i> Bot de Atendimento</a></li>
                            <li><a href="{{ route('admin.lgpd.index') }}" class="{{ request()->routeIs('admin.lgpd.*') ? 'active' : '' }}">
                                <i class="fas fa-scale-balanced"></i> Painel LGPD / DPO
                                @if($sa_badge_lgpd > 0)<span class="sa-badge sa-amber">{{ $sa_badge_lgpd }}</span>@endif
                            </a></li>
                        </ul>
                    </div>
                </div>
            @elseif (auth()->user()->role == 'manager')
                {{-- ═══ MENU GESTOR — Agrupado ═══ --}}
                @php
                    $mgr_ops_active  = request()->is('projects*','manager/team*','manager/schedule*','manager/approvals*');
                    $mgr_fin_active  = request()->is('manager/contracts*','manager/reconciliation*');
                    $mgr_mkt_active  = request()->is('manager/landing-pages*','marketing*','prospecting*','whatsapp*','raffles*','social/accounts*','social-ai*','banners*');
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

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mgr_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mgr_mkt_active ? '400px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/manager/landing-pages') }}" class="{{ request()->is('manager/landing-pages*') ? 'active' : '' }}"><i class="fas fa-laptop-code"></i> Landing Pages</a></li>
                            <li><a href="{{ route('intelligence.territorial') }}" class="{{ request()->routeIs('intelligence.territorial') ? 'active' : '' }}"><i class="fas fa-map-location-dot" style="color: #10b981;"></i> Inteligência Territorial</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Mensageria WhatsApp</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-cogs"></i> Configuração Omnichannel</a></li>
                            <li><a href="{{ url('/whatsapp/templates') }}" class="{{ request()->is('whatsapp/templates*') ? 'active' : '' }}"><i class="fas fa-layer-group"></i> Modelos (Templates)</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-robot" style="color:#a78bfa;"></i> Automações</a></li>
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
                            <li><a href="{{ url('/smart-analysis') }}" class="{{ request()->is('smart-analysis*') ? 'active' : '' }}"><img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width:20px;height:20px;border-radius:50%;object-fit:cover;margin-right:5px;"> Smart Analysis AI</a></li>
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
                    $ngo_mkt_active    = request()->is('ngo/landing-pages*','marketing*','prospecting*','whatsapp*','raffles*','social/accounts*','social-ai*','banners*');
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

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $ngo_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $ngo_mkt_active ? '400px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/ngo/landing-pages') }}" class="{{ request()->is('ngo/landing-pages*') ? 'active' : '' }}"><i class="fas fa-magic"></i> Construtor de LPs</a></li>
                            <li><a href="{{ route('intelligence.territorial') }}" class="{{ request()->routeIs('intelligence.territorial') ? 'active' : '' }}"><i class="fas fa-map-location-dot" style="color: #10b981;"></i> Inteligência Territorial</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Mensageria WhatsApp</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-cogs"></i> Configuração Omnichannel</a></li>
                            <li><a href="{{ url('/whatsapp/templates') }}" class="{{ request()->is('whatsapp/templates*') ? 'active' : '' }}"><i class="fas fa-layer-group"></i> Modelos (Templates)</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-robot" style="color:#a78bfa;"></i> Automações</a></li>
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
                            <li><a href="{{ url('/smart-analysis') }}" class="{{ request()->is('smart-analysis*') ? 'active' : '' }}"><img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width:20px;height:20px;border-radius:50%;object-fit:cover;margin-right:5px;"> Smart Analysis AI</a></li>
                        </ul>
                    </div>
                </div>
            @else
                <!-- Menu Comum / MEI / Empresa -->
                @php
                    $mei_fin_active  = request()->is('personal/reconciliation*','personal/budget*','transactions*');
                    $mei_mkt_active  = request()->is('marketing*','prospecting*','whatsapp*','social/accounts*','social-ai*','manager/landing-pages*');
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

                {{-- Grupo: Marketing & Comunicação --}}
                <div class="menu-group">
                    <div class="menu-group-header {{ $mei_mkt_active ? 'group-active' : 'collapsed' }}" onclick="toggleGroup(this)">
                        <i class="fas fa-bullhorn group-icon"></i> Marketing &amp; Comunicação
                        <i class="fas fa-chevron-down group-arrow"></i>
                    </div>
                    <div class="menu-group-items" style="max-height: {{ $mei_mkt_active ? '400px' : '0' }};">
                        <ul>
                            <li><a href="{{ url('/manager/landing-pages') }}" class="{{ request()->is('manager/landing-pages*') ? 'active' : '' }}"><i class="fas fa-laptop-code"></i> Landing Pages</a></li>
                            <li><a href="{{ route('social-ai.index') }}" class="{{ request()->is('social-ai*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles" style="color: #6366f1;"></i> Social AI Hub</a></li>
                            <li><a href="{{ route('marketing.index') }}" class="{{ request()->is('marketing*') ? 'active' : '' }}"><i class="fas fa-brain" style="color:#4f46e5;"></i> Hub de Marketing IA</a></li>
                            <li><a href="{{ route('prospecting.index') }}" class="{{ request()->is('prospecting*') ? 'active' : '' }}"><i class="fas fa-wand-magic-sparkles"></i> Prospecção IA</a></li>
                            <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Mensageria WhatsApp</a></li>
                            <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-cogs"></i> Configuração Omnichannel</a></li>
                            <li><a href="{{ route('whatsapp.broadcast.index') }}" class="{{ request()->is('whatsapp/broadcast*') ? 'active' : '' }}"><i class="fas fa-paper-plane"></i> Disparo em Massa</a></li>
                            <li><a href="{{ route('whatsapp.automations.index') }}" class="{{ request()->is('whatsapp/automations*') ? 'active' : '' }}"><i class="fas fa-robot" style="color:#a78bfa;"></i> Automações</a></li>
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
            @endif
        </ul>
    </nav>
    <div class="user-view">
        <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
        <div class="user-info">
            <div class="user-name">{{ auth()->user()->name ?? 'Usuário' }}</div>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('global-logout-form').submit();" class="user-logout">Sair</a>
        </div>
        <a href="{{ url('/profile') }}" class="user-settings" title="Configurações">
            <i class="fas fa-cog"></i>
        </a>
    </div>
</aside>
@endauth

<main class="main-content" style="{{ !auth()->check() ? 'margin-left: 0; width: 100%;' : '' }}">
    
    <!-- ══ COMMAND TOPBAR ══════════════════════════════════════════════ -->
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0 36px; height: 68px; background: #0f172a; border-bottom: 1px solid rgba(255,255,255,0.06); position: sticky; top: 0; z-index: 900; margin: -32px -32px 32px -32px;">

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
                }
            </style>

            <!-- Linha vertical accent + texto do painel -->
            <div style="display: flex; align-items: center; gap: 16px;">
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
            @endif
        @endauth

        <!-- Direita: Ações -->
        <div style="display: flex; align-items: center; gap: 8px;">

            <!-- Relógio ao vivo -->
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); padding: 8px 14px; border-radius: 10px; font-size: 0.78rem; color: rgba(255,255,255,0.4); font-weight: 700; letter-spacing: 0.5px; font-family: 'JetBrains Mono', monospace;" id="live-clock">
                <i class="fas fa-circle" style="font-size: 0.4rem; color: #10b981; animation: pulse-green 2s infinite;"></i>
                <span id="clock-time">--:--</span>
            </div>

            <!-- F5: Dark Mode Toggle -->
            <button id="theme-toggle" 
                    style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.05)'"
                    title="Alternar Tema (Dark/Light)">
                <i id="theme-icon" class="fas fa-moon" style="font-size: 1rem; color: rgba(255,255,255,0.5);"></i>
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
                <div style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer;"
                     onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                     onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    @php $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->unread()->count() : 0; @endphp
                    <i class="fas fa-bell" style="font-size: 1rem; color: rgba(255,255,255,0.5);"></i>
                    <span id="notif-badge" style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 9px; display: {{ $unreadCount > 0 ? 'flex' : 'none' }}; align-items: center; justify-content: center; border: 2px solid #0f172a; font-weight: 900;">{{ $unreadCount }}</span>
                </div>

                <!-- Dropdown Notificações -->
                <div id="notif-dropdown" style="display: none; position: absolute; top: 50px; right: 0; width: 340px; background: #1e293b; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); z-index: 1000; border: 1px solid rgba(255,255,255,0.08); overflow: hidden;">
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
            <a href="{{ url('/profile') }}" style="width: 38px; height: 38px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;"
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
    .btn-premium {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: white;
        padding: 10px 24px;
        border-radius: 30px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        font-size: 0.9rem;
    }
    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        filter: brightness(1.2);
        color: white;
    }
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

    // ── Live Users Widget (Super Admin Only) ─────────────────────
    @if(auth()->check() && auth()->user()->role === 'super_admin')
    (function saLive() {
        async function fetchLive() {
            try {
                const r = await fetch('/admin/live-users', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) return;
                const d = await r.json();
                document.getElementById('saLiveCnt').textContent = d.count;
                const el = document.getElementById('saLiveList');
                if (!d.count) {
                    el.innerHTML = '<div class="sa-live-empty">Nenhum usuário ativo agora</div>';
                } else {
                    el.innerHTML = d.users.slice(0, 5).map(u =>
                        `<div class="sa-live-row">
                            <div class="sa-live-av">${u.initial}</div>
                            <div class="sa-live-nfo">
                                <span class="sa-live-nm">${u.name}</span>
                                <span class="sa-live-tn">${u.tenant}</span>
                            </div>
                            <span class="sa-live-ago">${u.seen}</span>
                        </div>`
                    ).join('') + (d.count > 5 ? `<div class="sa-live-empty">+${d.count-5} outros</div>` : '');
                }
            } catch(e) {
                const el = document.getElementById('saLiveList');
                if (el) el.innerHTML = '<div class="sa-live-empty">—</div>';
            }
        }
        fetchLive();
        setInterval(fetchLive, 30000);
    })();
    @endif

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
            
            function setTheme(theme) {
                htmlModel.setAttribute('data-theme', theme);
                localStorage.setItem('vivensi-theme', theme);
                if (theme === 'dark') {
                    themeIcon.className = 'fas fa-sun';
                    themeIcon.style.color = '#fbbf24';
                } else {
                    themeIcon.className = 'fas fa-moon';
                    themeIcon.style.color = 'rgba(255,255,255,0.5)';
                }
            }
            
            // Init theme
            const savedTheme = localStorage.getItem('vivensi-theme') || 'dark';
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
</body>
</html>

