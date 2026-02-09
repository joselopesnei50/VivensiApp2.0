<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'VIVENSI App - Gestão Financeira Inteligente' }}</title>
    
    <!-- SEO & Social Sharing -->
    <meta name="description" content="Vivensi App - A plataforma de gestão definitiva para ONGs, empresas e gestores. Controle financeiro, projetos e transparência com auxílio de IA.">
    <meta name="keywords" content="gestão financeira, ongs, projetos, saas, vivensi, transparência pública, lgpd financeiro">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
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
        z-index: 1040; /* Above top-bar but BELOW sidebar when open */
        opacity: 0;
        transition: opacity 0.3s;
        cursor: pointer;
    }
    .sidebar-overlay.show {
        opacity: 1;
    }
    
    /* Ensure sidebar is ABOVE overlay on mobile */
    @media (max-width: 768px) {
        .sidebar.mobile-open {
            z-index: 1050 !important;
        }
    }
</style>

@auth
<aside class="sidebar">
    <div class="sidebar-header" style="justify-content: center;">
        <a href="{{ url('/dashboard') }}" class="logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="VIVENSI" style="max-width: 180px; height: auto;">
        </a>
    </div>
    <nav class="sidebar-menu">
        <ul>
            <li><a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}"><i class="fas fa-home"></i> Visão Geral</a></li>
            
            @if (in_array(auth()->user()->role, ['super_admin', 'manager', 'ngo']))
                {{-- Vivensi Academy - Disponível para super_admin, manager e ngo --}}
                <li><a href="{{ url('/academy') }}" class="{{ request()->is('academy*') ? 'active' : '' }}"><i class="fas fa-graduation-cap"></i> Vivensi Academy</a></li>
                <li><a href="{{ url('/whatsapp/chat') }}" class="{{ request()->is('whatsapp/chat*') ? 'active' : '' }}"><i class="fab fa-whatsapp"></i> Atendimento AI</a></li>
                <li><a href="{{ url('/whatsapp/settings') }}" class="{{ request()->is('whatsapp/settings*') ? 'active' : '' }}"><i class="fas fa-robot"></i> Configurar Robô</a></li>
            @endif
            
            @if (auth()->user()->role == 'super_admin')
                <!-- Menu Super Admin -->
                <li><a href="{{ url('/admin') }}"><i class="fas fa-credit-card"></i> Visão Geral (SaaS)</a></li>
                <li><a href="{{ url('/admin/tenants') }}"><i class="fas fa-building"></i> Organizações</a></li>
                <li><a href="{{ route('admin.team.index') }}"><i class="fas fa-users-cog"></i> Time Vivensi</a></li>
                <li><a href="{{ route('admin.chat') }}"><i class="fas fa-comments"></i> Chat Interno</a></li>
                <li><a href="{{ route('admin.plans.index') }}"><i class="fas fa-tags"></i> Gestão de Planos</a></li>
                <li><a href="{{ route('admin.email_logs') }}"><i class="fas fa-envelope-open-text"></i> Logs de E-mail</a></li>
                <li><a href="{{ route('admin.blog.index') }}"><i class="fas fa-blog"></i> Blog CMS</a></li>
                <li><a href="{{ route('admin.testimonials.index') }}"><i class="fas fa-quote-left"></i> Depoimentos</a></li>
                <li><a href="{{ route('admin.pages.index') }}"><i class="fas fa-file-alt"></i> Páginas (CMS)</a></li>
                <li><a href="{{ route('admin.academy.index') }}"><i class="fas fa-graduation-cap"></i> Vivensi Academy</a></li>
                <li><a href="{{ route('admin.health') }}"><i class="fas fa-server"></i> Saúde do Servidor</a></li>

                <li><a href="{{ url('/admin/settings') }}"><i class="fas fa-cogs"></i> Configurações Globais</a></li>
                <li><a href="{{ url('/admin/support') }}"><i class="fas fa-headset"></i> Gestão de Tickets</a></li>
            @elseif (auth()->user()->role == 'manager')
                <!-- Menu Gestor -->
                <li><a href="{{ url('/projects') }}" class="{{ request()->is('projects*') ? 'active' : '' }}"><i class="fas fa-project-diagram"></i> Projetos</a></li>
                <li><a href="{{ url('/manager/team') }}" class="{{ request()->is('manager/team*') ? 'active' : '' }}"><i class="fas fa-users"></i> Equipe & RH</a></li>
                <li><a href="{{ url('/manager/schedule') }}" class="{{ request()->is('manager/schedule*') ? 'active' : '' }}"><i class="fas fa-calendar-alt"></i> Agenda Corporativa</a></li>
                <li><a href="{{ url('/manager/approvals') }}" class="{{ request()->is('manager/approvals*') ? 'active' : '' }}"><i class="fas fa-check-double"></i> Central de Aprovações</a></li>
                <li><a href="{{ url('/manager/contracts') }}" class="{{ request()->is('manager/contracts*') ? 'active' : '' }}"><i class="fas fa-file-signature"></i> Contratos Digitais</a></li>
                <li><a href="{{ url('/manager/reconciliation') }}" class="{{ request()->is('manager/reconciliation*') ? 'active' : '' }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                <li><a href="{{ url('/manager/landing-pages') }}" class="{{ request()->is('manager/landing-pages*') ? 'active' : '' }}"><i class="fas fa-laptop-code"></i> Marketing (LPs)</a></li>
                <li><a href="{{ url('/smart-analysis') }}" class="{{ request()->is('smart-analysis*') ? 'active' : '' }}"><img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 5px;"> Smart Analysis AI</a></li>
            @elseif (auth()->user()->role == 'ngo' || (auth()->user()->tenant && auth()->user()->tenant->type == 'ngo'))
                <!-- Menu ONG -->
                <li><a href="{{ url('/ngo/donors') }}"><i class="fas fa-hand-holding-heart"></i> Doadores</a></li>
                <li><a href="{{ url('/ngo/receipts') }}"><i class="fas fa-receipt"></i> Recibos</a></li>
                <li><a href="{{ url('/ngo/contracts') }}"><i class="fas fa-file-contract"></i> Contratos Digitais</a></li>
                <li><a href="{{ url('/ngo/grants') }}"><i class="fas fa-file-signature"></i> Editais & Convênios</a></li>
                <li><a href="{{ url('/ngo/landing-pages') }}"><i class="fas fa-magic"></i> Construtor de LPs</a></li>
                <li><a href="{{ url('/ngo/budget') }}"><i class="fas fa-chart-pie"></i> Orçamento Anual</a></li>
                <li><a href="{{ url('/ngo/team') }}"><i class="fas fa-users"></i> Equipe da ONG</a></li>
                <li><a href="{{ url('/ngo/hr') }}"><i class="fas fa-id-badge"></i> RH & Voluntários</a></li>
                <li><a href="{{ url('/ngo/beneficiaries') }}" class="{{ request()->is('ngo/beneficiaries*') ? 'active' : '' }}"><i class="fas fa-hand-holding-heart"></i> Beneficiários</a></li>
                <li style="margin-left: 10px;"><a href="{{ url('/ngo/beneficiaries/insights') }}" class="{{ request()->is('ngo/beneficiaries/insights*') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Indicadores Sociais</a></li>
                <li style="margin-left: 10px;"><a href="{{ url('/ngo/beneficiaries/reports/annual') }}" class="{{ request()->is('ngo/beneficiaries/reports/annual*') ? 'active' : '' }}"><i class="fas fa-file-alt"></i> Relatório Anual</a></li>
                <li><a href="{{ url('/ngo/assets') }}"><i class="fas fa-boxes"></i> Patrimônio</a></li>
                <li><a href="{{ url('/ngo/reconciliation') }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                <li><a href="{{ url('/ngo/reports/dre') }}"><i class="fas fa-file-invoice-dollar"></i> Relatórios (DRE)</a></li>
                <li><a href="{{ url('/ngo/audit') }}"><i class="fas fa-eye"></i> Central de Auditoria</a></li>
                <li><a href="{{ url('/smart-analysis') }}"><img src="{{ asset('img/bruce-ai.png') }}" alt="AI" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 5px;"> Smart Analysis</a></li>
                <li><a href="{{ url('/ngo/transparencia') }}" class="{{ request()->is('ngo/transparencia*') ? 'active' : '' }}"><i class="fas fa-landmark"></i> Portal Transparência</a></li>
            @else
                <!-- Menu Comum -->
                <li><a href="{{ url('/personal/reconciliation') }}"><i class="fas fa-sync-alt"></i> Conciliação Bancária</a></li>
                <li><a href="{{ url('/personal/budget') }}"><i class="fas fa-chart-line"></i> Planejamento Anual</a></li>
                <li><a href="{{ url('/transactions/create') }}"><i class="fas fa-plus-circle"></i> Nova Transação</a></li>
            @endif
            
            <li><a href="{{ url('/support') }}"><i class="fas fa-life-ring"></i> Suporte</a></li>
            <li><a href="{{ url('/profile') }}"><i class="fas fa-cog"></i> Configurações</a></li>
        </ul>
    </nav>
    <div class="user-view">
        <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
        <div>
            <div style="font-weight: bold;">{{ auth()->user()->name ?? 'Usuário' }}</div>
            <div style="font-size: 0.8rem; color: #888;">
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: inherit;">Sair</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</aside>
@endauth

<main class="main-content" style="{{ !auth()->check() ? 'margin-left: 0; width: 100%;' : '' }}">
    
    <!-- Top Bar -->
    <div class="top-bar">
        <div style="display: flex; align-items: center;">
            <div class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            
            <!-- Mobile Logout Button -->
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="mobile-logout-btn" style="display: none; margin-left: 15px; color: #ef4444; font-size: 1.2rem;">
                <i class="fas fa-sign-out-alt"></i>
            </a>

            <style>
                @media (max-width: 768px) {
                    .mobile-logout-btn { display: block !important; }
                }
            </style>

            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; display: block; margin-bottom: 5px;">Ambiente de Gestão</span>
                <h2 style="margin: 0; font-weight: 700; color: #2c3e50; font-size: 1.5rem;">
                    @php $role = auth()->user()?->role; @endphp
                    @if($role == 'manager') <i class="fas fa-user-shield" style="color: var(--primary-color);"></i> Painel do Gestor
                    @elseif($role == 'ngo') <i class="fas fa-landmark" style="color: var(--primary-color);"></i> Painel Terceiro Setor
                    @elseif($role) <i class="fas fa-wallet" style="color: var(--primary-color);"></i> Minhas Finanças
                    @else <i class="fas fa-file-contract" style="color: var(--primary-color);"></i> Documento Público
                    @endif
                </h2>
            </div>
        </div>

        @auth
            @php
                $tenant = auth()->user()->tenant;
            @endphp
            @if($tenant && $tenant->subscription_status === 'trialing' && $tenant->trial_ends_at)
                @php
                    $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($tenant->trial_ends_at), false);
                @endphp
                <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 8px 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; margin-left: 30px;">
                    <div style="color: #d97706; font-size: 0.9rem; font-weight: 700;">
                        <i class="fas fa-clock me-1"></i> 
                        {{ $daysLeft > 0 ? "Você tem $daysLeft dias de teste grátis" : "Seu período de teste venceu hoje!" }}
                    </div>
                    @if($tenant->plan_id)
                        <a href="{{ route('checkout.index', ['plan_id' => $tenant->plan_id]) }}" class="btn btn-sm btn-primary" style="font-size: 0.75rem; padding: 5px 12px; border-radius: 8px;">ATIVAR AGORA</a>
                    @endif
                </div>
            @endif
        @endauth

        <div class="actions">
             <div class="top-bar-right">
                <div class="notifications" style="position: relative; cursor: pointer;" id="notification-bell" onclick="toggleNotifications()">
                    @php
                        $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->unread()->count() : 0;
                    @endphp
                    <i class="fas fa-bell" style="font-size: 1.2rem; color: #64748b;"></i>
                    <span id="notif-badge" class="notification-badge" style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: {{ $unreadCount > 0 ? 'flex' : 'none' }}; align-items: center; justify-content: center; border: 2px solid white;">
                        {{ $unreadCount }}
                    </span>

                    <!-- Dropdown de Notificações -->
                    <div id="notif-dropdown" style="display: none; position: absolute; top: 40px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; border: 1px solid #e2e8f0; overflow: hidden;">
                        <div style="padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #1e293b;">Notificações</strong>
                            <button onclick="markAllRead()" style="background: none; border: none; color: #4f46e5; font-size: 0.75rem; cursor: pointer; font-weight: 600;">Marcar todas como lidas</button>
                        </div>
                        <div id="notif-list" style="max-height: 350px; overflow-y: auto;">
                            <!-- Preenchido via JS -->
                            <div style="padding: 20px; text-align: center; color: #94a3b8; font-size: 0.9rem;">Carregando...</div>
                        </div>
                        <div style="padding: 10px; text-align: center; background: #f8fafc; border-top: 1px solid #f1f5f9;">
                            <a href="{{ route('notifications.index') }}" style="font-size: 0.8rem; color: #64748b; text-decoration: none; font-weight: 600;">Ver todas</a>
                        </div>
                    </div>
                </div>
             </div>

             @auth
             <a href="{{ url('/profile') }}" class="nav-link" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; transition: all 0.2s;">
                <i class="fas fa-cog"></i> Configurações
             </a>
             @endauth
        </div>
    </div>

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
    const __vivensiIsAuth = {{ auth()->check() ? 'true' : 'false' }};
    let __vivensiLastUnread = null;
    let __vivensiToastCooldownUntil = 0;

    // Safety check on load to prevent stuck overlays
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('sidebarOverlay');
        if(overlay) {
            // Ensure overlay is hidden on load unless explicitly triggered
            overlay.classList.remove('show');
            overlay.style.display = 'none';
        }
        
        // Remove any stuck bootstrap modal backdrops from previous sessions/history
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if(backdrops.length > 0) {
            backdrops.forEach(b => b.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.pointerEvents = 'auto';
        }
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

    // Light polling to show new notifications without refresh.
    document.addEventListener('DOMContentLoaded', function() {
        if (!__vivensiIsAuth) return;
        updateBadge();
        setInterval(() => {
            if (document.hidden) return;
            updateBadge();
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown && dropdown.style.display === 'block') {
                fetchNotifications();
            }
        }, 30000);
    });

    // Fechar dropdown ao clicar fora
    window.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notif-dropdown');
        const bell = document.getElementById('notification-bell');
        if (dropdown && bell && !bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>

    @auth
        @include('partials.chat_widget')
    @endauth

    <div id="vivensi-toast-wrap" class="vivensi-toast-wrap" aria-live="polite" aria-atomic="true"></div>
    
    <!-- Bootstrap 5 JS Bundle (Required for Modals, Dropdowns, Tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
