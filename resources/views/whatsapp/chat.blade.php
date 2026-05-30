<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi OmniChannel | WhatsApp CRM</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --wa-green: #25d366;
            --wa-green-dark: #128c7e;
            --wa-green-light: #dcf8c6;
            --primary-color: #075e54;
            --primary-hover: #054c44;
            --primary-light: rgba(7, 94, 84, 0.08);
            --accent: #25d366;
            --success-color: #25d366;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-heading: #111b21;
            --text-body: #3b4a54;
            --text-muted: #8696a0;
            --border-color: #e9edef;
            --sidebar-bg: #ffffff;
            --chat-bg: #efeae2;
            --sidebar-width: 360px;
            --intelligence-panel-width: 340px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body, html {
            margin: 0; padding: 0;
            height: 100%; overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--chat-bg);
        }

        .crm-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* ── Sidebar ── */
        .crm-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 10;
        }

        .sidebar-header {
            padding: 0;
            background: #f0f2f5;
            border-bottom: 1px solid var(--border-color);
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
        }

        .app-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .back-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.15s;
        }
        .back-link:hover { color: var(--primary-color); }

        .search-area {
            position: relative;
            padding: 8px 14px 14px;
        }
        .search-input {
            width: 100%;
            padding: 9px 14px 9px 38px;
            border-radius: 8px;
            border: none;
            background: #ffffff;
            color: var(--text-body);
            font-size: 0.88rem;
            outline: none;
            box-shadow: var(--shadow-sm);
        }
        .search-input:focus { box-shadow: 0 0 0 2px var(--accent); }
        .search-icon {
            position: absolute;
            left: 26px; top: 50%;
            transform: translateY(-30%);
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .filter-tabs {
            padding: 8px 14px;
            display: flex;
            gap: 6px;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
            overflow-x: auto;
        }
        .filter-tabs::-webkit-scrollbar { height: 3px; }
        .filter-tabs::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        .filter-tab {
            background: #f0f2f5;
            border: none;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s;
        }
        .filter-tab:hover { background: #e9edef; color: var(--text-body); }
        .filter-tab.active {
            background: var(--primary-color);
            color: #fff;
        }

        .contact-list {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .contact-list::-webkit-scrollbar { width: 5px; }
        .contact-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        .contact-item {
            padding: 13px 20px;
            border-bottom: 1px solid #f0f2f5;
            cursor: pointer;
            display: flex;
            gap: 13px;
            align-items: center;
            transition: background 0.12s;
            border-left: 3px solid transparent;
        }
        .contact-item:hover { background: #f5f6f6; }
        .contact-item.active {
            background: #f0f2f5;
            border-left-color: var(--accent);
        }

        .avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
            position: relative;
        }
        .online-dot {
            width: 11px; height: 11px;
            background: var(--accent);
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: 1px; right: 1px;
        }

        .contact-info { flex: 1; min-width: 0; overflow: hidden; }
        .contact-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; gap: 6px; }
        .contact-name {
            font-weight: 600; color: var(--text-heading); font-size: 0.92rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            min-width: 0; flex: 1;
        }
        .contact-time { font-size: 0.72rem; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }
        .contact-bottom { display: flex; justify-content: space-between; align-items: center; }
        .last-message {
            font-size: 0.82rem; color: var(--text-muted);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 200px;
        }
        .badge-unread {
            background: var(--accent); color: white;
            font-size: 0.68rem; padding: 2px 6px;
            border-radius: 10px; font-weight: 700; min-width: 18px; text-align: center;
        }

        .compliance-badges { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 5px; }
        .c-badge { font-size: .67rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; border: 1px solid transparent; }
        .c-ok  { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .c-warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .c-bad  { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

        /* ── Chat Area ── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--chat-bg);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='400' height='400' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            position: relative;
        }

        .chat-header {
            height: 80px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            z-index: 10;
        }

        .chat-user-profile { display: flex; align-items: center; gap: 16px; min-width: 0; overflow: hidden; flex: 1; }
        .header-avatar {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .header-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; overflow: hidden; }
        .header-info h4 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .header-info #header-status { font-size: 0.8rem; color: #64748b; display: flex; align-items: center; gap: 5px; }
        
        .compliance-badges { display: flex; gap: 6px; margin-top: 4px; }
        .c-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .c-ok   { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .c-warn { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .c-bad  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .chat-actions button {
            background: transparent; border: none;
            color: var(--text-muted);
            font-size: 1.1rem;
            padding: 8px 10px;
            cursor: pointer;
            transition: all 0.15s;
            border-radius: 8px;
        }
        .chat-actions button:hover { background: #e9edef; color: var(--text-heading); }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .messages-container::-webkit-scrollbar { width: 5px; }
        .messages-container::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        .message-row { display: flex; width: 100%; margin-bottom: 2px; }
        .message-in  { justify-content: flex-start; }
        .message-out { justify-content: flex-end; }

        .bubble {
            max-width: 62%;
            padding: 8px 12px 6px;
            border-radius: 8px;
            position: relative;
            font-size: 0.92rem;
            line-height: 1.5;
            word-break: break-word;
            box-shadow: 0 1px 2px rgba(0,0,0,0.12);
        }
        .bubble.in {
            background: #ffffff;
            color: var(--text-heading);
            border-top-left-radius: 2px;
        }
        .bubble.out {
            background: var(--wa-green-light);
            color: #1a1a1a;
            border-top-right-radius: 2px;
        }
        .bubble .meta {
            font-size: 0.68rem;
            display: flex; align-items: center; gap: 4px;
            margin-top: 3px; justify-content: flex-end;
            color: var(--text-muted);
        }
        .bubble.out .meta { color: #6b8f71; }

        /* ── Input Area ── */
        .input-area {
            background: #f0f2f5;
            padding: 12px 20px;
            border-top: 1px solid var(--border-color);
            flex-shrink: 0;
        }
        .input-container {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .input-box {
            flex: 1;
            background: white;
            border-radius: 10px;
            border: none;
            padding: 10px 16px;
            box-shadow: var(--shadow-sm);
        }
        .input-toolbar {
            display: flex;
            gap: 4px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f2f5;
            margin-bottom: 6px;
        }
        .tool-btn {
            background: transparent; border: none;
            color: var(--text-muted);
            font-size: 1rem;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 6px;
            transition: all 0.15s;
            display: flex; align-items: center; gap: 5px;
            font-size: 0.82rem; font-weight: 600;
        }
        .tool-btn:hover { color: var(--primary-color); background: var(--primary-light); }
        .tool-btn i { font-size: 0.95rem; }

        .message-input {
            border: none; background: transparent;
            width: 100%; outline: none;
            font-size: 0.92rem;
            min-height: 24px; max-height: 120px;
            resize: none;
            color: var(--text-heading);
            line-height: 1.5;
            font-family: inherit;
        }

        .input-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
        }

        .send-btn {
            background: var(--primary-color);
            color: white; border: none;
            border-radius: 50%;
            width: 46px; height: 46px;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            flex-shrink: 0;
            box-shadow: var(--shadow-md);
        }
        .send-btn:hover { background: var(--primary-hover); transform: scale(1.05); }

        /* ── Right Panel ── */
        .intelligence-panel {
            width: var(--intelligence-panel-width);
            background: #ffffff;
            border-left: 1px solid var(--border-color);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .intelligence-panel::-webkit-scrollbar { width: 4px; }
        .intelligence-panel::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        .panel-hero {
            padding: 28px 20px 20px;
            text-align: center;
            background: #f0f2f5;
            border-bottom: 1px solid var(--border-color);
        }
        .hero-avatar {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border-radius: 50%;
            margin: 0 auto 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(37,211,102,0.3);
        }
        .panel-hero h3 { color: #111b21; font-size: 1rem; font-weight: 700; margin-bottom: 3px; }
        .panel-hero span { color: #667781; font-size: 0.8rem; }

        .tag-badge { background: #e9edef; color: #3b4a54; padding: 3px 10px; border-radius: 6px; font-size: 0.73rem; font-weight: 600; }
        .tag-badge.hot { background: #fef2f2; color: #991b1b; }

        .crm-section { border-bottom: 1px solid #f0f2f5; }
        .crm-header {
            padding: 14px 18px;
            display: flex; justify-content: space-between; align-items: center;
            font-weight: 600; font-size: 0.88rem; color: var(--text-heading);
            cursor: pointer; text-decoration: none;
            transition: background 0.12s;
            background: transparent;
        }
        .crm-header:hover { background: #f9fafb; }

        .crm-body { padding: 0 18px 16px; }

        .info-row { margin-bottom: 12px; }
        .label { font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; letter-spacing: 0.5px; }
        .value { color: var(--text-heading); font-size: 0.88rem; font-weight: 500; }

        .timeline-item {
            position: relative; padding-left: 18px; margin-bottom: 14px;
            border-left: 2px solid #e9edef;
        }
        .timeline-item::before {
            content: ''; position: absolute; left: -5px; top: 5px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--accent); border: 2px solid white;
        }
        .timeline-content {
            font-size: 0.82rem; color: var(--text-body);
            background: #f9fafb; padding: 10px 12px;
            border-radius: 8px; border: 1px solid #e9edef;
        }
        .timeline-date { font-size: 0.67rem; color: var(--text-muted); display: block; margin-top: 4px; text-align: right; }

        .empty-state {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; background: var(--chat-bg); color: var(--text-muted);
        }

        @media (max-width: 1200px) {
            :root { --sidebar-width: 300px; --intelligence-panel-width: 300px; }
        }
        @media (max-width: 992px) {
            .intelligence-panel { display: none; }
        }

        /* ── Sidebar accent bar ── */
        .sidebar-accent {
            height: 3px;
            background: linear-gradient(90deg, #25d366, #128c7e, #075e54);
        }

        /* ── Status dots on avatars ── */
        .status-dot {
            width: 11px; height: 11px;
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: 1px; right: 1px;
        }
        .status-dot.open    { background: #25d366; box-shadow: 0 0 0 2px rgba(37,211,102,.25); }
        .status-dot.waiting { background: #f59e0b; }
        .status-dot.closed  { background: #94a3b8; }

        /* ── Contact item status border ── */
        .contact-item.status-waiting { border-left-color: #f59e0b; }
        .contact-item.status-closed  { border-left-color: #e2e8f0; }
        .contact-item.active         { border-left-color: var(--accent); }

        /* ── Contact status pill ── */
        .contact-status-pill {
            font-size: .6rem; font-weight: 700;
            padding: 1px 6px; border-radius: 10px;
            text-transform: uppercase; letter-spacing: .04em;
            flex-shrink: 0;
        }
        .pill-open    { background: #dcfce7; color: #16a34a; }
        .pill-waiting { background: #fef3c7; color: #d97706; }
        .pill-closed  { background: #f1f5f9; color: #94a3b8; }

        /* ── Contact labels ── */
        .contact-labels { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; }
        .clabel {
            font-size: .58rem; font-weight: 700; padding: 1px 6px;
            border-radius: 8px; text-transform: uppercase; letter-spacing: .04em;
        }
        .clabel-novo-lead    { background: #dbeafe; color: #1d4ed8; }
        .clabel-suporte      { background: #ffedd5; color: #c2410c; }
        .clabel-venda        { background: #dcfce7; color: #15803d; }
        .clabel-urgente      { background: #fee2e2; color: #b91c1c; }
        .clabel-vip          { background: #f3e8ff; color: #7e22ce; }
        .clabel-concluido    { background: #f1f5f9; color: #64748b; }
        .clabel-agendado     { background: #cffafe; color: #0e7490; }
        /* fallback */
        .clabel              { background: #f0f2f5; color: #475569; }

        /* ── Label picker panel ── */
        .label-picker { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .label-opt {
            font-size: .72rem; font-weight: 600; padding: 4px 10px;
            border-radius: 12px; cursor: pointer; border: 1.5px solid transparent;
            transition: all .15s; user-select: none;
        }
        .label-opt.selected { border-color: currentColor; opacity: 1; }
        .label-opt:not(.selected) { opacity: .55; }

        /* ── Improved input toolbar ── */
        .tool-btn {
            background: transparent; border: none;
            font-size: 0.78rem; font-weight: 600;
            cursor: pointer; padding: 4px 10px;
            border-radius: 20px;
            transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 5px;
            white-space: nowrap;
        }
        .tool-btn:hover { opacity: .85; }
        .tool-btn.tb-rapid    { color: #d97706; background: rgba(245,158,11,.1); }
        .tool-btn.tb-rapid:hover { background: rgba(245,158,11,.18); }
        .tool-btn.tb-template { color: #16a34a; background: rgba(16,185,129,.1); }
        .tool-btn.tb-template:hover { background: rgba(16,185,129,.18); }
        .tool-btn.tb-image    { color: #6366f1; background: rgba(99,102,241,.1); }
        .tool-btn.tb-image:hover { background: rgba(99,102,241,.18); }
        .tool-btn.tb-audio    { color: #ef4444; background: rgba(239,68,68,.1); }
        .tool-btn.tb-audio:hover { background: rgba(239,68,68,.18); }
        .tool-btn.tb-schedule { color: #0ea5e9; background: rgba(14,165,233,.1); }
        .tool-btn.tb-schedule:hover { background: rgba(14,165,233,.18); }

        /* ── Compact right panel hero ── */
        .panel-hero-compact {
            padding: 16px 18px 14px;
            background: #f0f2f5;
            border-bottom: 1px solid var(--border-color);
        }
        .panel-hero-top {
            display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
        }
        .hero-avatar-sm {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: white; font-weight: 700;
            box-shadow: 0 3px 8px rgba(37,211,102,.3);
        }
        .hero-info { flex: 1; min-width: 0; }
        .hero-info h3 { color: #111b21; font-size: .95rem; font-weight: 700; margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hero-info span { color: #667781; font-size: .75rem; font-family: monospace; }

        /* ── Section header with chevron animation ── */
        .crm-header[aria-expanded="true"] .crm-chevron { transform: rotate(180deg); }
        .crm-chevron { transition: transform .2s; }

        /* ── Better chat header avatar ── */
        .header-avatar-dynamic {
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem; color: white;
            flex-shrink: 0;
        }

        /* ── Window warning banner ── */
        #window-warning {
            margin: 0 16px 8px;
            border-radius: 10px;
            font-size: .85rem;
        }

        /* ── Audio record area ── */
        #audioRecordArea {
            border-radius: 10px;
        }

        /* ── Image preview area ── */
        #imagePreviewArea {
            border-radius: 10px;
        }

        /* ── Empty state improvement ── */
        .empty-state-card {
            background: white;
            padding: 48px 40px;
            border-radius: 24px;
            box-shadow: 0 12px 40px rgba(0,0,0,.06);
            text-align: center;
            max-width: 380px;
        }
        .empty-state-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, rgba(37,211,102,.15), rgba(18,140,126,.15));
            border-radius: 20px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 20px;
        }
        .new-chat-btn {
            background: var(--primary-color);
            color: white; border: none;
            border-radius: 12px;
            padding: 12px 28px;
            font-size: .9rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 8px;
            cursor: pointer; transition: all .15s;
            margin-top: 20px;
        }
        .new-chat-btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
    </style>
</head>
<body>
@php
    $isManager = in_array(auth()->user()->role, ['manager', 'super_admin'], true);
    $isNgo     = auth()->user()->role === 'ngo';
@endphp

    <div class="crm-layout">
        <!-- 1. LEFT SIDEBAR -->
        <div class="crm-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-accent"></div>
                <div class="header-top-row">
                    <h1 class="app-title">
                        <span style="width:30px;height:30px;background:linear-gradient(135deg,#25d366,#128c7e);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fab fa-whatsapp" style="color:#fff;font-size:.9rem;"></i>
                        </span>
                        OmniChannel
                    </h1>
                    <a href="{{ url('/dashboard') }}" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
                <div class="search-area">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Buscar por nome ou número...">
                </div>
            </div>

            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">
                    Todas <span style="background:rgba(0,0,0,.1);border-radius:10px;padding:0 5px;font-size:.65rem;margin-left:2px;">{{ count($chats) }}</span>
                </button>
                <button class="filter-tab" data-filter="unread">Não Lidas</button>
                <button class="filter-tab" data-filter="waiting">Aguardando</button>
            </div>

            <div class="contact-list" id="chatList">
                @foreach($chats as $chat)
                @php
                    $isUnread  = $chat->status !== 'closed'
                        && $chat->last_inbound_at
                        && (!$chat->last_read_at || $chat->last_inbound_at > $chat->last_read_at);
                    $isWaiting = $chat->status === 'waiting';
                    $statusClass = $chat->status === 'waiting' ? 'waiting' : ($chat->status === 'closed' ? 'closed' : 'open');
                    $avatarColors = ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899'];
                    $avatarColor  = $avatarColors[$chat->id % 6];
                    $lastMsgContent   = $chat->last_msg_content ?? null;
                    $lastMsgDirection = $chat->last_msg_direction ?? null;
                    $lastTime = $chat->last_message_at
                        ? (\Carbon\Carbon::parse($chat->last_message_at)->isToday()
                            ? \Carbon\Carbon::parse($chat->last_message_at)->format('H:i')
                            : \Carbon\Carbon::parse($chat->last_message_at)->format('d/m'))
                        : '';
                @endphp
                <div class="contact-item {{ $loop->first ? 'active' : '' }} status-{{ $statusClass }}"
                     onclick="selectChat(this, {{ $chat->id }})"
                     data-id="{{ $chat->id }}"
                     data-unread="{{ $isUnread ? 'true' : 'false' }}"
                     data-waiting="{{ $isWaiting ? 'true' : 'false' }}">

                    <div class="avatar" style="background: {{ $avatarColor }};">
                        {{ strtoupper(substr($chat->contact_name ?? '?', 0, 1)) }}
                        <span class="status-dot {{ $statusClass }}"></span>
                    </div>
                    <div class="contact-info">
                        <div class="contact-top">
                            <span class="contact-name">{{ $chat->contact_name ?? 'Sem Nome' }}</span>
                            <span class="contact-time">{{ $lastTime }}</span>
                        </div>
                        <div class="contact-bottom">
                            <span class="last-message">
                                @if($lastMsgDirection === 'outbound')
                                    <i class="fas fa-check-double" style="color:var(--primary-color);font-size:.7rem;"></i>
                                @endif
                                {{ $lastMsgContent ?? 'Iniciar conversa' }}
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                @if($isUnread) <span class="badge-unread">1</span> @endif
                                @if($statusClass === 'waiting')
                                    <span class="contact-status-pill pill-waiting">Aguard.</span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($chat->labels))
                        <div class="contact-labels">
                            @foreach(array_slice($chat->labels, 0, 3) as $lbl)
                                <span class="clabel clabel-{{ Str::slug($lbl) }}">{{ $lbl }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                
                @if(count($chats) == 0)
                    <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="far fa-comments fa-2x mb-2"></i><br>Nenhuma conversa.
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. MAIN CHAT AREA -->
        @if(count($chats) > 0)
        <div class="chat-main" id="chat-main-area">
            <!-- Header -->
            <div class="chat-header">
                <div class="chat-user-profile">
                    <div class="header-avatar-dynamic" id="header-avatar"
                         style="background: linear-gradient(135deg, {{ ['#4F46E5','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899'][$chats[0]->id % 6] }}, {{ ['#7c3aed','#059669','#d97706','#dc2626','#7c3aed','#db2777'][$chats[0]->id % 6] }});">
                        {{ count($chats) > 0 ? strtoupper(substr($chats[0]->contact_name, 0, 1)) : '?' }}
                    </div>
                    <div class="header-info">
                        <h4 id="header-name">{{ count($chats) > 0 ? $chats[0]->contact_name : 'Nenhum chat' }}</h4>
                        <span id="header-status">
                            <i class="fas fa-circle" style="font-size:6px;color:#25d366;"></i>
                            Online agora
                        </span>
                        <div class="compliance-badges" id="waComplianceBadges" style="margin-top:2px;"></div>
                    </div>
                </div>
                <div class="chat-actions d-flex align-items-center gap-2">
                    <div id="bot-status-container" style="display:none;">
                        <button class="tool-btn" id="btn-toggle-bot" onclick="toggleBotStatus()" style="font-size: 0.75rem; border-radius: 12px; padding: 6px 12px;">
                            <i class="fas fa-robot me-1"></i> <span id="bot-status-text">Bot: Ativo</span>
                        </button>
                    </div>
                    <button class="btn btn-primary" id="btn-assign-chat" onclick="assignChatToMe()" style="display:none; font-size: 0.75rem; border-radius: 12px; font-weight: 700; padding: 6px 12px; background: var(--wa-green-dark); border: none;">
                        <i class="fas fa-handshake me-1"></i> Assumir
                    </button>
                    <button class="tool-btn" title="Nova Conversa" onclick="startNewChat()" style="color:#25d366;background:rgba(37,211,102,.1); width: 36px; height: 36px; padding: 0; justify-content: center;">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button class="tool-btn" title="Configurações" onclick="location.href='{{ url('/whatsapp/settings') }}'" style="color:#64748b;background:#f1f5f9; width: 36px; height: 36px; padding: 0; justify-content: center;">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-container" id="chat-messages-area">
                <!-- Loaded via JS -->
            </div>

            <!-- Warning Toast (Example) -->
            <div id="window-warning" style="display: none; background: #fffbeb; border: 1px solid #fbbf24; color: #92400e; padding: 10px 20px; border-radius: 8px; margin: 0 20px 10px; font-size: 0.9rem; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Janela de 24h fechada. Use um <strong>Template</strong>.
            </div>

            <div class="input-area">
                <div class="input-container">
                    <!-- Hidden file input for image attachment -->
                    <input type="file" id="imageFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;" onchange="previewImage(event)">

                    <div class="input-box">
                        <!-- Image Preview Area (hidden by default) -->
                        <div id="imagePreviewArea" style="display:none; margin-bottom: 8px; padding: 10px; background: #f8fafc; border-radius: 10px; border: 1px dashed #cbd5e1; position: relative;">
                            <img loading="lazy" id="imagePreviewEl" src="" style="max-height:120px; max-width:100%; border-radius:8px; display:block; margin-bottom:6px;">
                            <input type="text" id="imageCaptionInput" placeholder="Legenda (opcional)..." style="width:100%; border:none; background:transparent; font-size:0.85rem; outline:none; color:#334155;">
                            <button onclick="cancelImage()" style="position:absolute; top:6px; right:6px; background:#ef4444; color:white; border:none; border-radius:50%; width:24px; height:24px; font-size:0.8rem; cursor:pointer; line-height:1;">✕</button>
                        </div>

                        <!-- Audio Recording Area (hidden by default) -->
                        <div id="audioRecordArea" style="display:none; margin-bottom: 8px; padding: 10px; background: #fef2f2; border-radius: 10px; border: 1px solid #fecaca; align-items:center; gap:10px;">
                            <span id="audioRecordStatus" style="font-size:0.82rem; color:#ef4444; font-weight:700;">● Gravando... <span id="audioTimer">0:00</span></span>
                            <audio id="audioPlayback" controls style="display:none; height:32px; flex:1;"></audio>
                            <div style="display:flex; gap:6px; margin-top:6px;">
                                <button id="stopRecordBtn" onclick="stopRecording()" style="background:#ef4444; color:white; border:none; border-radius:8px; padding:5px 14px; font-size:0.8rem; font-weight:700; cursor:pointer;">⏹ Parar</button>
                                <button id="sendAudioBtn" onclick="sendAudio()" style="display:none; background:#25d366; color:white; border:none; border-radius:8px; padding:5px 14px; font-size:0.8rem; font-weight:700; cursor:pointer;"><i class="fas fa-paper-plane"></i> Enviar Áudio</button>
                                <button onclick="cancelAudio()" style="background:#f1f5f9; color:#64748b; border:none; border-radius:8px; padding:5px 14px; font-size:0.8rem; font-weight:600; cursor:pointer;">✕ Descartar</button>
                            </div>
                        </div>

                        <div class="input-toolbar" style="flex-wrap:wrap;">
                            <button class="tool-btn tb-rapid" title="Respostas Rápidas" onclick="openCannedModal()">
                                <i class="fas fa-bolt"></i> Rápidas
                            </button>
                            <button class="tool-btn tb-template" id="tplToggleBtn" title="Template Oficial Meta" onclick="openTemplateModal()">
                                <i class="fas fa-shield-halved"></i> Template
                            </button>
                            <button class="tool-btn tb-image" title="Anexar Imagem" onclick="document.getElementById('imageFileInput').click()">
                                <i class="fas fa-image"></i> Imagem
                            </button>
                            <button class="tool-btn tb-audio" title="Gravar Áudio" onclick="startRecording()" id="recordBtn">
                                <i class="fas fa-microphone"></i> Áudio
                            </button>
                            <button class="tool-btn tb-schedule" title="Agendar Mensagem" onclick="openScheduleModal()">
                                <i class="fas fa-clock"></i> Agendar
                            </button>
                        </div>
                        <textarea class="message-input" id="msgInput" rows="1" placeholder="Escreva uma mensagem..."></textarea>
                        <div class="input-footer">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:0.72rem; color:#94a3b8;">Enter para enviar · Shift+Enter nova linha</span>
                                <span id="templateModeBadge" style="display:none; font-size:.72rem; font-weight:700; padding:2px 10px; border-radius:999px; background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;">
                                    TEMPLATE
                                </span>
                            </div>
                        </div>
                    </div>
                    <button class="send-btn" onclick="sendMessage()" title="Enviar">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 3. RIGHT PANEL (CRM) -->
        <div class="intelligence-panel" id="crm-panel">
            <div class="panel-hero-compact">
                <div class="panel-hero-top">
                    <div class="hero-avatar-sm" id="crm-avatar">{{ strtoupper(substr($chats[0]->contact_name, 0, 1)) }}</div>
                    <div class="hero-info">
                        <h3 id="crm-name">{{ $chats[0]->contact_name }}</h3>
                        <span id="crm-phone">{{ $chats[0]->contact_phone }}</span>
                    </div>
                    @if($isManager)
                    <div class="dropdown ms-auto">
                        <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;color:#475569;padding:5px 8px;">
                            <i class="fas fa-shield-alt text-muted" style="font-size:.8rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1" style="border-radius:12px;font-size:.84rem;padding:6px;min-width:200px;">
                            <li><a class="dropdown-item fw-bold text-success rounded py-1" href="#" onclick="complianceAction('opt_in')"><i class="fas fa-check me-2"></i>Opt-in (Permitir)</a></li>
                            <li><a class="dropdown-item fw-bold text-danger rounded py-1" href="#" onclick="complianceAction('opt_out')"><i class="fas fa-ban me-2"></i>Opt-out (Remover)</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item fw-bold text-dark rounded py-1" href="#" onclick="complianceAction('block')"><i class="fas fa-lock me-2"></i>Bloquear</a></li>
                            <li><a class="dropdown-item fw-bold text-secondary rounded py-1" href="#" onclick="complianceAction('unblock')"><i class="fas fa-unlock me-2"></i>Desbloquear</a></li>
                        </ul>
                    </div>
                    @endif
                </div>
                <div class="compliance-badges" id="crmComplianceBadges" style="flex-wrap:wrap;gap:4px;"></div>
            </div>

            <div class="crm-content">
                <!-- Accordion 1 -->
                <!-- Etiquetas -->
                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-labels" aria-expanded="true">
                        <span><i class="fas fa-tags me-2 text-muted"></i> Etiquetas</span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-labels">
                        <div class="label-picker" id="label-picker">
                            @php
                            $allLabels = [
                                ['name'=>'Novo Lead',  'slug'=>'novo-lead',  'bg'=>'#dbeafe','color'=>'#1d4ed8'],
                                ['name'=>'Suporte',    'slug'=>'suporte',    'bg'=>'#ffedd5','color'=>'#c2410c'],
                                ['name'=>'Venda',      'slug'=>'venda',      'bg'=>'#dcfce7','color'=>'#15803d'],
                                ['name'=>'Urgente',    'slug'=>'urgente',    'bg'=>'#fee2e2','color'=>'#b91c1c'],
                                ['name'=>'VIP',        'slug'=>'vip',        'bg'=>'#f3e8ff','color'=>'#7e22ce'],
                                ['name'=>'Agendado',   'slug'=>'agendado',   'bg'=>'#cffafe','color'=>'#0e7490'],
                                ['name'=>'Concluído',  'slug'=>'concluido',  'bg'=>'#f1f5f9','color'=>'#64748b'],
                            ];
                            @endphp
                            @foreach($allLabels as $lbl)
                            <span class="label-opt clabel-{{ $lbl['slug'] }}"
                                  data-label="{{ $lbl['name'] }}"
                                  style="background:{{ $lbl['bg'] }};color:{{ $lbl['color'] }};"
                                  onclick="toggleLabel(this)">
                                {{ $lbl['name'] }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-contact-info" aria-expanded="true">
                        <span><i class="far fa-id-card me-2 text-muted"></i> Dados de Contato</span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-contact-info">
                        <div class="info-row">
                            <span class="label">Nome</span>
                            <span class="value" id="crm-info-name">—</span>
                        </div>
                        <div class="info-row mb-0">
                            <span class="label">Telefone</span>
                            <span class="value" id="crm-info-phone">—</span>
                        </div>
                    </div>
                </div>

                <!-- Accordion 2 -->
                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-notes" aria-expanded="true">
                        <span><i class="far fa-sticky-note me-2 text-warning"></i> Notas & IA</span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-notes">
                        <div class="mb-3 text-end"><button class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.75rem;" onclick="openNoteModal()">+ Criar Nota</button></div>
                        <div id="crm-notes-container">
                            <div class="text-center text-muted small py-3">Nenhuma nota.</div>
                        </div>
                    </div>
                </div>

                <!-- Accordion 3: AI Training -->
                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-ai-brain" aria-expanded="true">
                        <span><i class="fas fa-brain me-2 text-info"></i> Cérebro do Bruce (IA)</span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-ai-brain">
                        <div class="mb-2">
                            <label for="aiTrainingArea" class="label">Instruções de Personalidade</label>
                            <textarea id="aiTrainingArea" class="form-control form-control-sm" rows="4" style="font-size: 0.8rem; background: #fffbeb;" placeholder="Ex: Você é um vendedor focado em..."></textarea>
                        </div>
                        <button type="button" class="btn btn-sm btn-info text-white w-100 py-1" onclick="updateAiTraining()" style="font-weight: 700; font-size: 0.75rem;">
                            <i class="fas fa-save me-1"></i> Atualizar Conhecimento
                        </button>
                    </div>
                </div>

                {{-- Accordion Kanban (manager = projetos | ngo = patrocínios) --}}
                @if($isManager || $isNgo)
                <div class="crm-section">
                    <div class="crm-header collapsed" data-bs-toggle="collapse" data-bs-target="#crm-kanban" aria-expanded="false">
                        <span>
                            <i class="fas fa-columns me-2" style="color:#6366f1;"></i>
                            @if($isNgo) Enviar p/ Kanban de Patrocínios @else Enviar p/ Kanban de Projeto @endif
                        </span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse" id="crm-kanban">
                        @if($isManager)
                            <div class="mb-2">
                                <label for="kanbanProjectSelect" class="label mb-1">Selecionar Projeto</label>
                                <select id="kanbanProjectSelect" class="form-select form-select-sm" style="border-radius:8px;font-size:0.82rem;">
                                    <option value="">— escolha um projeto —</option>
                                    @foreach($projects as $proj)
                                        <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="small text-muted mb-2" style="font-size:0.75rem;">
                                <i class="fas fa-sticky-note me-1" style="color:#f59e0b;"></i>
                                As notas do atendimento serão incluídas no card automaticamente.
                            </div>
                            <button class="btn btn-sm w-100 fw-700" onclick="sendChatToProjectKanban()"
                                style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;font-size:0.82rem;padding:7px;">
                                <i class="fas fa-columns me-1"></i> Criar Card no Kanban
                            </button>
                        @elseif($isNgo)
                            <div class="mb-2">
                                <label for="sponsorCompanyName" class="label mb-1">Nome da Empresa / Patrocinador</label>
                                <input type="text" id="sponsorCompanyName" class="form-control form-control-sm" placeholder="Ex: Empresa ABC Ltda" style="border-radius:8px;font-size:0.82rem;">
                                <div class="small text-muted mt-1" style="font-size:0.75rem;">
                                    <i class="fas fa-sticky-note me-1" style="color:#f59e0b;"></i>
                                    Notas do chat serão incluídas no deal de patrocínio.
                                </div>
                            </div>
                            <button class="btn btn-sm w-100 fw-700" onclick="sendChatToSponsorshipKanban()"
                                style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:8px;font-size:0.82rem;padding:7px;">
                                <i class="fas fa-handshake me-1"></i> Enviar p/ Patrocínios
                            </button>
                        @endif
                        <div id="kanbanResult" class="mt-2 d-none small fw-600 text-success text-center"></div>
                    </div>
                </div>
                @endif

                <!-- Accordion 4 -->
                <div class="crm-section">
                    <div class="crm-header collapsed" data-bs-toggle="collapse" data-bs-target="#crm-history" aria-expanded="false">
                        <span><i class="fas fa-history me-2 text-muted"></i> Histórico</span>
                        <i class="fas fa-chevron-down text-muted small crm-chevron"></i>
                    </div>
                    <div class="crm-body collapse" id="crm-history">
                        <div id="historyList" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                            <div class="text-center text-muted small py-3">Selecione uma conversa para ver o histórico.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @else
        <!-- EMPTY STATE (NO CHATS) -->
        <div class="empty-state" style="flex: 1;">
            <div class="empty-state-card">
                <div class="empty-state-icon">
                    <i class="fab fa-whatsapp" style="font-size:2rem;color:#25d366;"></i>
                </div>
                <h5 style="font-weight:700;color:#111b21;margin-bottom:8px;">Bem-vindo ao OmniChannel</h5>
                <p style="color:#667781;font-size:.88rem;margin:0;line-height:1.6;">
                    Nenhuma conversa ativa no momento.<br>
                    Aguarde novas mensagens ou inicie um atendimento ativo.
                </p>
                <button class="new-chat-btn" onclick="startNewChat()">
                    <i class="fas fa-plus"></i> Nova Conversa
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Modals -->
    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" role="dialog" aria-modal="true" aria-labelledby="addNoteModalLabel" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-6" id="addNoteModalLabel">Nova Nota Interna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                   <textarea id="newNoteContent" class="form-control" rows="3" placeholder="Ex: Cliente prefere contato pela manhã..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveNote()">Salvar Nota</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Canned Responses Modal -->
    <div class="modal fade" id="cannedModal" role="dialog" aria-modal="true" aria-labelledby="cannedModalLabel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-6">Respostas Rápidas (Macros)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                   <div class="list-group list-group-flush" id="cannedList">
                       <!-- Populated by JS -->
                   </div>
                   <div class="p-3 border-top bg-light">
                       <input type="text" id="newCannedTitle" class="form-control form-control-sm mb-2" placeholder="Título (ex: /pix)">
                       <textarea id="newCannedContent" class="form-control form-control-sm mb-2" placeholder="Conteúdo da mensagem..." rows="2"></textarea>
                       <button class="btn btn-outline-primary btn-sm w-100" onclick="createCanned()">Criar Nova Macro</button>
                   </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meta Templates Modal -->
    <div class="modal fade" id="templateModal" role="dialog" aria-modal="true" aria-labelledby="templateModalLabel" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fas fa-shield-halved text-success me-2"></i> Enviar Template Oficial (Meta)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Templates oficiais permitem iniciar conversas ativas e contornar a janela de 24h. Escolha um modelo aprovado abaixo:</p>
                    
                    <div class="row g-3" id="templateSelectorContainer">
                        <!-- Carregado via JS -->
                        <div class="col-12 text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Buscando templates na Meta...</p>
                        </div>
                    </div>

                    <div id="templateVariablesForm" class="mt-4 p-3 d-none" style="background: #f1f5f9; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h6 class="fw-bold mb-3 small text-uppercase" style="letter-spacing: 0.5px; color: #475569;">Preencher Variáveis do Modelo</h6>
                        <div id="variableInputsContainer" class="row g-2">
                            <!-- Inputs dinâmicos aqui -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success fw-bold px-4" id="confirmSendTemplateBtn" disabled onclick="sendTemplate()">
                        <i class="fas fa-paper-plane me-1"></i> Disparar Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Message Modal -->
    <div class="modal fade" id="scheduleModal" role="dialog" aria-modal="true" aria-labelledby="scheduleModalLabel" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fas fa-clock text-warning me-2"></i> Agendar Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">A mensagem será enviada automaticamente na data e hora selecionadas (com delay de ±5 min para segurança anti-ban).</p>
                    <div class="mb-3">
                        <label for="scheduleMsgContent" class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.5px;">Mensagem</label>
                        <textarea id="scheduleMsgContent" class="form-control" rows="4" placeholder="Digite a mensagem que será enviada..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleDatetime" class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.5px;">Data e Hora do Envio</label>
                        <input type="datetime-local" id="scheduleDatetime" class="form-control" style="border-radius:10px;">
                    </div>
                    <div id="scheduleResult" style="display:none;" class="alert alert-success py-2 small fw-semibold"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning fw-bold px-4 text-white" onclick="confirmSchedule()">
                        <i class="fas fa-clock me-1"></i> Confirmar Agendamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function escapeHtml(input) {
            const s = String(input ?? '');
            return s.replace(/[&<>"'`]/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;'
            }[c]));
        }
        let currentChatId = {{ count($chats) > 0 ? $chats[0]->id : 'null' }};
        const csrfToken = '{{ csrf_token() }}';
        let cannedResponses = [];
        let nextSendIsTemplate = false;
        let selectedTemplate = null;
        let lastMessageId = null; // Track last seen message for incremental polling

        $(document).ready(function() {
            if(currentChatId) {
                // Initialize with first chat
                loadChatData(currentChatId);
            }

            // Auto-polling every 3 seconds for new messages
            setInterval(function() {
                if (!currentChatId) return;
                pollNewMessages(currentChatId);
            }, 3000);

            // Refresh sidebar chat list every 10 seconds
            setInterval(function() {
                $.ajax({
                    url: '{{ url("/whatsapp/chat/list") }}',
                    method: 'GET',
                    timeout: 5000,
                    success: function(data) {
                        if (!data.chats) return;
                        data.chats.forEach(function(chat) {
                            const item = $('.contact-item[data-id="' + chat.id + '"]');
                            if (item.length) {
                                item.find('.contact-name').text(chat.contact_name || 'Sem Nome');
                                item.find('.contact-time').text(chat.last_message_at_formatted || '');
                                item.find('.last-message').text(chat.last_message_preview || '');
                            } else {
                                location.reload();
                            }
                        });
                    }
                });
            }, 10000);

            // Filtering Logic
            $('.filter-tab').click(function() {
                $('.filter-tab').removeClass('active');
                $(this).addClass('active');

                const filter = $(this).data('filter');

                $('.contact-item').each(function() {
                    const isUnread = $(this).data('unread') == true;
                    const isWaiting = $(this).data('waiting') == true;

                    if(filter === 'all') $(this).show();
                    else if(filter === 'unread') {
                        isUnread ? $(this).show() : $(this).hide();
                    }
                    else if(filter === 'waiting') {
                        isWaiting ? $(this).show() : $(this).hide();
                    }
                });
            });

            // Search filter
            $('.search-input').on('input', function() {
                const q = $(this).val().toLowerCase().trim();
                $('.contact-item').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(q === '' || text.includes(q));
                });
            });
        });

        function toggleLabel(el) {
            if (!currentChatId) return;
            $(el).toggleClass('selected');
            const labels = [];
            $('#label-picker .label-opt.selected').each(function() {
                labels.push($(this).data('label'));
            });
            $.ajax({
                url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/labels',
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                contentType: 'application/json',
                data: JSON.stringify({ labels }),
                success: function() {
                    // Update sidebar label pills for this chat
                    const item = $('.contact-item[data-id="' + currentChatId + '"]');
                    let html = '';
                    labels.slice(0, 3).forEach(function(l) {
                        const slug = l.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/\s+/g,'-').replace(/[^a-z0-9-]/g,'');
                        html += '<span class="clabel clabel-' + slug + '">' + l + '</span>';
                    });
                    item.find('.contact-labels').html(html);
                    if (!item.find('.contact-labels').length && html) {
                        item.find('.contact-info').append('<div class="contact-labels">' + html + '</div>');
                    }
                }
            });
        }

        function selectChat(el, id) {
            currentChatId = id;
            $('.contact-item').removeClass('active');
            $(el).addClass('active');
            // Mark as read visually
            $(el).attr('data-unread', 'false');
            $(el).find('.badge-unread').hide();
            // Persist to server (fire-and-forget)
            $.post('{{ url("/whatsapp/chat") }}/' + id + '/read', { _token: csrfToken });
            loadChatData(id);
        }

        function loadChatData(id) {
            // Show Loading
            $('#chat-messages-area').html('<div style="display:flex; justify-content:center; align-items:center; height:100%; color:#999;"><div class="spinner-border text-primary" role="status"></div></div>');
            lastMessageId = null; // reset on full reload
            
            $.get('{{ url("/whatsapp/chat") }}/' + id + '/messages', function(data) {
                updateUI(data.chat);
                $('#window-warning').hide();
                renderMessages(data.messages);
                renderNotes(data.notes);
                renderHistory(data.messages);
                if (data.messages && data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                }
                if(data.canned_responses) {
                    cannedResponses = data.canned_responses;
                    renderCannedList();
                }
                if(data.ai_training !== undefined) {
                    $('#aiTrainingArea').val(data.ai_training);
                }
            }).fail(function(xhr) {
                $('#chat-messages-area').html('<div style="display:flex;flex-direction:column;justify-content:center;align-items:center;height:100%;color:#94a3b8;gap:8px;"><i class="fas fa-exclamation-circle fa-2x"></i><span style="font-size:.9rem;">Erro ao carregar mensagens. Tente novamente.</span></div>');
            });
        }

        function sendChatToProjectKanban() {
            const projectId = document.getElementById('kanbanProjectSelect')?.value;
            if (!projectId) { alert('Selecione um projeto antes de continuar.'); return; }
            if (!currentChatId) { alert('Selecione uma conversa primeiro.'); return; }

            const btn = document.querySelector('#crm-kanban .btn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';

            $.ajax({
                url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/kanban',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                contentType: 'application/json',
                data: JSON.stringify({ project_id: projectId }),
                success: function(res) {
                    const el = document.getElementById('kanbanResult');
                    el.innerHTML = '<i class="fas fa-check-circle me-1"></i> Card criado no Kanban!';
                    el.classList.remove('d-none');
                    setTimeout(() => el.classList.add('d-none'), 4000);
                },
                error: function(xhr) {
                    alert('Erro: ' + (xhr.responseJSON?.message || 'Falha ao criar card.'));
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            });
        }

        function sendChatToSponsorshipKanban() {
            if (!currentChatId) { alert('Selecione uma conversa primeiro.'); return; }
            const companyName = document.getElementById('sponsorCompanyName')?.value.trim() || '';

            const btn = document.querySelector('#crm-kanban .btn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';

            $.ajax({
                url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/kanban-sponsorship',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                contentType: 'application/json',
                data: JSON.stringify({ company_name: companyName }),
                success: function(res) {
                    const el = document.getElementById('kanbanResult');
                    el.innerHTML = '<i class="fas fa-check-circle me-1"></i> Deal criado em Patrocínios!';
                    el.classList.remove('d-none');
                    setTimeout(() => el.classList.add('d-none'), 4000);
                },
                error: function(xhr) {
                    alert('Erro: ' + (xhr.responseJSON?.message || 'Falha ao criar deal.'));
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            });
        }

        function updateAiTraining() {
            const training = $('#aiTrainingArea').val();
            const btn = $('#crm-ai-brain button');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');

            $.post('{{ url("/whatsapp/update-training") }}', {
                _token: csrfToken,
                training: training
            }, function(res) {
                if(res.success) {
                    btn.html('<i class="fas fa-check"></i> Atualizado!').addClass('btn-success').removeClass('btn-info');
                    setTimeout(() => {
                        btn.prop('disabled', false).html(originalHtml).addClass('btn-info').removeClass('btn-success');
                    }, 2000);
                }
            }).fail(function() {
                alert('Erro ao atualizar treinamento.');
                btn.prop('disabled', false).html(originalHtml);
            });
        }

        // Silent poll — appends only NEW messages without full reload
        function pollNewMessages(id) {
            const url = '{{ url("/whatsapp/chat") }}/' + id + '/messages' + (lastMessageId ? '?after=' + lastMessageId : '');
            $.ajax({
                url: url,
                method: 'GET',
                timeout: 5000,
                success: function(data) {
                    if (!data.messages) return;
                    const msgs = data.messages;
                    if (lastMessageId && msgs.length > 0) {
                        let html = '';
                        const isAtBottom = isScrolledToBottom();
                        msgs.forEach(msg => {
                            let isOut = msg.direction === 'outbound';
                            html += `<div class="message-row ${isOut ? 'message-out' : 'message-in'}">
                                <div class="bubble ${isOut ? 'out' : 'in'}">
                                    ${escapeHtml(msg.content)}
                                    <div class="meta">${new Date(msg.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}${isOut ? ' <i class="fas fa-check-double text-light"></i>' : ''}</div>
                                </div></div>`;
                        });
                        $('#chat-messages-area').append(html);
                        lastMessageId = msgs[msgs.length - 1].id;
                        if (isAtBottom) scrollToBottom();
                    } else if (!lastMessageId) {
                        if (msgs.length > 0) lastMessageId = msgs[msgs.length - 1].id;
                    }
                }
            });
        }

        function isScrolledToBottom() {
            const el = document.getElementById('chat-messages-area');
            if (!el) return true;
            return el.scrollHeight - el.scrollTop - el.clientHeight < 60;
        }

        function updateUI(chat) {
            $('#header-name, #crm-name').text(chat.contact_name);
            $('#crm-avatar, #header-avatar').text(chat.contact_name.charAt(0));
            $('#crm-phone').text(chat.contact_phone || '--');
            $('#crm-info-name').text(chat.contact_name || '—');
            $('#crm-info-phone').text(chat.contact_phone || '—');
            
            // Bot/Assignment UI
            const botContainer = document.getElementById('bot-status-container');
            const botBtn = document.getElementById('btn-toggle-bot');
            const botText = document.getElementById('bot-status-text');
            const assignBtn = document.getElementById('btn-assign-chat');

            if (chat) {
                botContainer.style.display = 'block';
                if (chat.is_bot_active) {
                    botBtn.className = 'tool-btn bg-success text-white';
                    botText.innerText = 'Bot: Ativo';
                    assignBtn.style.display = 'block';
                } else {
                    botBtn.className = 'tool-btn bg-secondary text-white';
                    botText.innerText = 'Bot: Pausado';
                    assignBtn.style.display = chat.assigned_to ? 'none' : 'block';
                }

                if (chat.assigned_to) {
                    assignBtn.style.display = 'none';
                    botText.innerText = 'Atendimento Humano';
                    botBtn.className = 'tool-btn bg-primary text-white';
                }
            }

            renderCompliance(chat);

            // Sync label picker with chat's current labels
            const chatLabels = chat.labels || [];
            $('#label-picker .label-opt').each(function() {
                const isActive = chatLabels.includes($(this).data('label'));
                $(this).toggleClass('selected', isActive);
            });
        }

        function renderCompliance(chat) {
            const badges = [];
            if (chat.blocked_at) {
                badges.push('<span class="c-badge c-bad"><i class="fas fa-lock me-1"></i> BLOQUEADO</span>');
            } else if (chat.opt_out_at) {
                badges.push('<span class="c-badge c-bad"><i class="fas fa-ban me-1"></i> OPT‑OUT</span>');
            } else if (chat.opt_in_at) {
                badges.push('<span class="c-badge c-ok"><i class="fas fa-check me-1"></i> OPT‑IN</span>');
            } else {
                badges.push('<span class="c-badge c-warn"><i class="fas fa-triangle-exclamation me-1"></i> SEM OPT‑IN</span>');
            }

            if (chat.last_outbound_at) {
                const d = new Date(chat.last_outbound_at);
                badges.push('<span class="c-badge c-warn"><i class="fas fa-clock me-1"></i> Último envio ' + d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], {hour:"2-digit", minute:"2-digit"}) + '</span>');
            }

            $('#waComplianceBadges').html(badges.join(''));
            $('#crmComplianceBadges').html(badges.join(''));
        }

        function renderMessages(messages) {
             let html = '';
             messages.forEach(msg => {
                let isOut = msg.direction === 'outbound';
                html += `
                    <div class="message-row ${isOut ? 'message-out' : 'message-in'}">
                        <div class="bubble ${isOut ? 'out' : 'in'}">
                            ${escapeHtml(msg.content)}
                            <div class="meta">
                                ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                ${isOut ? '<i class="fas fa-check-double text-light"></i>' : ''}
                            </div>
                        </div>
                    </div>
                `;
             });
             $('#chat-messages-area').html(html);
             scrollToBottom();
        }

        function renderHistory(messages) {
            const container = document.getElementById('historyList');
            if (!container) return;
            if (!messages || messages.length === 0) {
                container.innerHTML = '<div class="text-center text-muted small py-3">Nenhuma mensagem registrada.</div>';
                return;
            }
            const last = messages.slice(-20).reverse();
            let html = '';
            last.forEach(msg => {
                const isOut = msg.direction === 'outbound';
                const time = new Date(msg.created_at).toLocaleString('pt-BR', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
                html += `<div style="display:flex;gap:6px;align-items:flex-start;padding:5px 0;border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:0.65rem;background:${isOut?'#eef2ff':'#f0fdf4'};color:${isOut?'#4f46e5':'#16a34a'};padding:2px 6px;border-radius:6px;white-space:nowrap;flex-shrink:0;">${isOut?'Enviado':'Recebido'}</span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.75rem;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(msg.content)}</div>
                        <div style="font-size:0.65rem;color:#94a3b8;">${time}</div>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }

        function renderNotes(notes) {
            const container = $('#crm-notes-container');
            let html = '';
            
            if(!notes || notes.length === 0) {
                html = '<div class="text-center text-muted small py-3">Nenhuma nota.</div>';
            } else {
                notes.forEach(note => {
                    let icon = note.type === 'ai_insight' ? '<i class="fas fa-dog text-warning"></i>' : '<i class="fas fa-user text-muted"></i>';
                    let bg = note.type === 'ai_insight' ? '#fffbeb' : '#f8fafc';
                    html += `
                        <div class="timeline-item">
                            <div class="timeline-content" style="background: ${bg}; border: 1px solid #e2e8f0;">
                                ${icon} ${escapeHtml(note.content)}
                                <span class="timeline-date">${new Date(note.created_at).toLocaleDateString()} • ${escapeHtml(note.user ? note.user.name : 'Sistema')}</span>
                            </div>
                        </div>
                    `;
                });
            }
            container.html(html);
        }

        function renderCannedList() {
            const $list = $('#cannedList');
            $list.empty();
            cannedResponses.forEach(c => {
                const $btn = $('<button type="button" class="list-group-item list-group-item-action"></button>');
                $btn.on('click', () => useCanned(String(c.content ?? '')));

                const $header = $('<div class="d-flex w-100 justify-content-between"></div>');
                const $title = $('<h6 class="mb-1 fw-bold"></h6>').text(String(c.title ?? ''));
                $header.append($title);

                const $p = $('<p class="mb-1 small text-muted text-truncate"></p>').text(String(c.content ?? ''));
                $btn.append($header).append($p);
                $list.append($btn);
            });
        }

        // --- Actions ---

        function scrollToBottom() {
            const el = document.getElementById('chat-messages-area');
            if(el) el.scrollTop = el.scrollHeight;
        }

        function sendMessage() {
            const txt = $('#msgInput').val();
            if(!txt.trim() || !currentChatId) return;
            
            $('#msgInput').val('');
            
            // Optimistic
            const optimisticId = 'opt_' + Date.now();
            $('#chat-messages-area').append(`
                <div class="message-row message-out">
                    <div class="bubble out" data-optimistic="${optimisticId}">
                        ${escapeHtml(txt)}
                        <div class="meta">Agora <i class="far fa-clock"></i></div>
                    </div>
                </div>
            `);
            scrollToBottom();

            const extraData = nextSendIsTemplate ? {
                is_template: 1,
                template_name: selectedTemplate.name,
                template_vars: getTemplateVars(),
                language_code: selectedTemplate.language
            } : {
                is_template: 0
            };

            $.post('{{ url("/whatsapp/chat/send") }}', {
                _token: csrfToken,
                chat_id: currentChatId,
                message: txt,
                ...extraData
            }).fail(function(xhr) {
                const code = (xhr && xhr.responseJSON && xhr.responseJSON.code) ? xhr.responseJSON.code : null;
                const msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Envio bloqueado.';
                const b = document.querySelector('[data-optimistic="' + optimisticId + '"]');
                if (b) {
                    b.style.opacity = '0.85';
                    b.style.background = '#991b1b';
                    const meta = b.querySelector('.meta');
                    if (meta) meta.innerHTML = 'Falhou <i class="fas fa-triangle-exclamation"></i>';
                }
                if (code === 'OUTSIDE_24H_WINDOW') {
                    $('#window-warning').css('display', 'flex');
                }
                alert(msg);
                setTimeout(() => loadChatData(currentChatId), 800);
            });

            // reset template mode after attempting to send
            nextSendIsTemplate = false;
            $('#templateModeBadge').hide();
        }

        $('#msgInput').on('keypress', function(e) {
            if(e.which == 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function complianceAction(action) {
            if (!currentChatId) return;

            let reason = '';
            if (action === 'block' || action === 'opt_out') {
                reason = prompt('Motivo (opcional):') || '';
            }

            $.post('{{ url("/whatsapp/chat") }}/' + currentChatId + '/compliance', {
                _token: csrfToken,
                action: action,
                reason: reason
            }, function(resp) {
                if (resp && resp.chat) {
                    renderCompliance(resp.chat);
                }
                setTimeout(() => loadChatData(currentChatId), 250);
            }).fail(function(xhr) {
                const msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Não foi possível atualizar compliance.';
                alert(msg);
            });
        }

        // Notes Logic
        function openNoteModal() {
            const modalEl = document.getElementById('addNoteModal');
            const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bsModal.show();
        }

        function saveNote() {
            const content = $('#newNoteContent').val().trim();
            if (!content) { alert('Escreva o conteúdo da nota antes de salvar.'); return; }
            if (!currentChatId) { alert('Nenhuma conversa selecionada.'); return; }

            const $btn = $('#addNoteModal .btn-primary');
            $btn.prop('disabled', true).text('Salvando…');

            $.ajax({
                url:  '{{ url("/whatsapp/notes") }}',
                type: 'POST',
                data: { _token: csrfToken, chat_id: currentChatId, content: content },
                dataType: 'json',
                success: function() {
                    // Bootstrap 5: fecha via instância nativa para garantir compatibilidade
                    const modalEl = document.getElementById('addNoteModal');
                    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    bsModal.hide();
                    $('#newNoteContent').val('');
                    loadChatData(currentChatId);
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.message || ('Erro ' + xhr.status + ': não foi possível salvar a nota.');
                    alert(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Salvar Nota');
                }
            });
        }

        // Canned Responses Logic
        function openCannedModal() {
            $('#cannedModal').modal('show');
        }

        function createCanned() {
            const title = $('#newCannedTitle').val();
            const content = $('#newCannedContent').val();
            if(!title || !content) return;

            $.post('{{ url("/whatsapp/canned") }}', {
                _token: csrfToken,
                title: title,
                content: content
            }, function(resp) {
                cannedResponses.push(resp);
                renderCannedList();
                $('#newCannedTitle').val('');
                $('#newCannedContent').val('');
            });
        }

        function useCanned(text) {
            $('#msgInput').val(text);
            $('#cannedModal').modal('hide');
            $('#msgInput').focus();
        }

        function openTemplateModal() {
            $('#templateModal').modal('show');
            $('#templateSelectorContainer').html('<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Buscando templates na Meta...</p></div>');
            $('#templateVariablesForm').addClass('d-none');
            $('#confirmSendTemplateBtn').prop('disabled', true);
            selectedTemplate = null;

            $.get('{{ url("/whatsapp/templates") }}', function(html) {
                // Como o endpoint retorna uma view, vamos parsear os dados ou criar um endpoint JSON. 
                // Para agilizar, vou buscar os dados do endpoint /whatsapp/templates que já retorna os templates
                // mas vou sugerir que o usuário use um endpoint JSON no futuro.
                // Aqui vou simular a busca se falhar ou se for HTML
                fetchTemplatesJson();
            });
        }

        function fetchTemplatesJson() {
            // Nota: No mundo real, criaríamos um endpoint JSON. Aqui vou emular o parse do que temos.
            $.get('{{ url("/api/whatsapp/templates") }}', function(data) {
                renderTemplateSelector(data.templates || []);
            }).fail(function() {
                $('#templateSelectorContainer').html('<div class="col-12 text-center py-4 text-danger"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>Erro ao carregar templates. Verifique a integração da Meta API.</div>');
            });
        }

        function renderTemplateSelector(templates) {
            const container = $('#templateSelectorContainer');
            container.empty();

            if (templates.length === 0) {
                container.html('<div class="col-12 text-center py-4 text-muted">Nenhum template aprovado encontrado.</div>');
                return;
            }

            templates.forEach(tpl => {
                if (tpl.status !== 'APPROVED') return;

                const card = $(`
                    <div class="col-md-6">
                        <div class="template-card p-3 border rounded-3 position-relative" style="cursor:pointer; transition: 0.2s; background: white;">
                            <div class="fw-bold small mb-1 text-truncate" title="${escapeHtml(tpl.name)}">${escapeHtml(tpl.name)}</div>
                            <div class="text-muted" style="font-size: 0.7rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 32px;">
                                ${escapeHtml(tpl.components.find(c => c.type === 'BODY')?.text || '')}
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark border p-1 px-2" style="font-size: 0.65rem;">${tpl.language}</span>
                                <i class="fas fa-check-circle text-success check-icon d-none"></i>
                            </div>
                        </div>
                    </div>
                `);

                card.find('.template-card').click(function() {
                    $('.template-card').removeClass('border-success shadow-sm').css('background', 'white');
                    $('.check-icon').addClass('d-none');
                    $(this).addClass('border-success shadow-sm').css('background', '#f0fdf4');
                    $(this).find('.check-icon').removeClass('d-none');
                    selectTemplate(tpl);
                });

                container.append(card);
            });
        }

        function selectTemplate(tpl) {
            selectedTemplate = tpl;
            const bodyComp = tpl.components.find(c => c.type === 'BODY');
            const text = bodyComp?.text || '';
            
            // Localizar variaveis numericas
            const matches = text.match(/@{{[0-9]+}}/g) || [];
            const varCount = matches.length;

            const varContainer = $('#variableInputsContainer');
            varContainer.empty();

            if (varCount > 0) {
                $('#templateVariablesForm').removeClass('d-none');
                for (let i = 1; i <= varCount; i++) {
                    varContainer.append(`
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-1">Variável @{{${i}}}</label>
                            <input type="text" class="form-control form-control-sm tpl-var-input" data-index="${i}" placeholder="Valor para @{{${i}}}">
                        </div>
                    `);
                }
            } else {
                $('#templateVariablesForm').addClass('d-none');
            }

            $('#confirmSendTemplateBtn').prop('disabled', false);
        }

        function getTemplateVars() {
            const vars = [];
            $('.tpl-var-input').each(function() {
                vars.push($(this).val());
            });
            return vars;
        }

        function sendTemplate() {
            if (!selectedTemplate) return;

            const bodyComp = selectedTemplate.components.find(c => c.type === 'BODY');
            let text = bodyComp?.text || '';
            const vars = getTemplateVars();
            
            // Preview do texto (opcional)
            vars.forEach((v, i) => {
                const varTag = "@{{"+(i+1)+"}}";
                text = text.replace(varTag, v || "["+(i+1)+"]");
            });

            // Set inputs
            $('#msgInput').val(text);
            nextSendIsTemplate = true;
            $('#templateModeBadge').show().text('TEMPLATE: ' + selectedTemplate.name);
            
            $('#templateModal').modal('hide');
            sendMessage();
        }

        function toggleTemplateMode() {
            if (nextSendIsTemplate) {
                nextSendIsTemplate = false;
                $('#templateModeBadge').hide();
                selectedTemplate = null;
            } else {
                openTemplateModal();
            }
        }

        // --- Simulation ---
        function simulateMessage() {
            const msg = prompt("Digite a mensagem para simular um cliente:");
            if(!msg) return;

            // Get current phone
            const currentPhone = $('#crm-phone').text();
            
            // Feedback visual
            $('#chat-messages-area').append(`
                <div class="text-center text-muted small my-2">
                    <i class="fas fa-satellite-dish"></i> Simulando mensagem de ${currentPhone}...
                </div>
            `);
            scrollToBottom();

            $.post('{{ url("/whatsapp/test/receive") }}', {
                _token: csrfToken,
                phone: currentPhone,
                message: msg
            }, function() {
                // Refresh chat after a short delay to see the AI reply
                setTimeout(() => loadChatData(currentChatId), 1500); 
                // Second check to ensure AI reply is caught if slow
                setTimeout(() => loadChatData(currentChatId), 5000);
            }).fail(function() {
                 alert("Erro ao simular. Verifique o console.");
            });
        }

        function startNewChat() {
            const phone = prompt("Digite o WhatsApp do contato (somente números, ex: 558199999999):");
            if (!phone) return;
            const name = prompt("Nome do contato (opcional):") || '';
            const message = prompt("Primeira mensagem (opcional). Se deixar vazio, só cria a conversa:") || '';
            const consent = confirm("Você tem opt-in/consentimento para enviar mensagem a este contato?\n\nOK = Sim (tenho consentimento)\nCancelar = Não (apenas criar conversa)");

            $.post('{{ route("whatsapp.chat.start") }}', {
                _token: csrfToken,
                phone: phone,
                name: name,
                message: message,
                consent: consent ? 1 : 0
            }, function(resp) {
                // Reload to refresh chat list and open chat
                window.location.href = '{{ url("/whatsapp/chat") }}';
            }).fail(function(xhr) {
                const msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Não foi possível criar a conversa. Verifique o telefone e configurações.";
                alert(msg);
            });
        }

        function quickSendToKanban(btn) {
            if (!currentChatId) return alert('Selecione uma conversa primeiro.');
            const projectId = document.getElementById('quickKanbanProject').value;
            if (!projectId) return alert('Selecione um projeto na lista.');

            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            $.post('{{ url("/whatsapp/chat") }}/' + currentChatId + '/kanban', {
                _token: csrfToken,
                project_id: projectId
            }, function(res) {
                if(res.success) {
                    alert('Contato e Card criados com sucesso no Kanban do projeto!');
                }
            }).fail(function(xhr) {
                alert('Erro ao enviar contato para o Kanban: ' + (xhr.responseJSON?.message || 'Desconhecido'));
            }).always(function() {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }

        // ════════════════════════════════════════════════════
        // FEATURE: ENVIO DE IMAGEM
        // ════════════════════════════════════════════════════
        let _imageBase64 = null;
        let _imageMimetype = null;

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Limitar a 5MB
            if (file.size > 5 * 1024 * 1024) {
                alert('Imagem muito grande! Máximo permitido: 5MB.');
                event.target.value = '';
                return;
            }

            _imageMimetype = file.type;
            const reader = new FileReader();
            reader.onload = function(e) {
                _imageBase64 = e.target.result.split(',')[1]; // apenas a parte base64
                document.getElementById('imagePreviewEl').src = e.target.result;
                document.getElementById('imageCaptionInput').value = '';
                document.getElementById('imagePreviewArea').style.display = 'block';
            };
            reader.readAsDataURL(file);
            // Limpa o input para permitir selecionar o mesmo arquivo novamente
            event.target.value = '';
        }

        function cancelImage() {
            _imageBase64 = null;
            _imageMimetype = null;
            document.getElementById('imagePreviewArea').style.display = 'none';
            document.getElementById('imagePreviewEl').src = '';
        }

        let _isSendingImage = false;
        function sendImage() {
            if (!_imageBase64 || !currentChatId || _isSendingImage) return;
            _isSendingImage = true;
            const caption = document.getElementById('imageCaptionInput').value.trim();
            const sendBtn = document.querySelector('.send-image-btn');
            if (sendBtn) { sendBtn.disabled = true; sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...'; }

            $.ajax({
                url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/send-media',
                method: 'POST',
                timeout: 15000,
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: JSON.stringify({ base64: _imageBase64, mimetype: _imageMimetype, caption: caption }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.success) {
                        const preview = document.getElementById('imagePreviewEl').src;
                        const captionHtml = caption ? `<div style="font-size:0.82rem;margin-top:4px;">${escapeHtml(caption)}</div>` : '';
                        $('#chat-messages-area').append(`
                            <div class="message-row message-out">
                                <div class="bubble out">
                                    <img loading="lazy" src="${preview}" style="max-width:200px; max-height:160px; border-radius:8px; display:block;">
                                    ${captionHtml}
                                    <div class="meta">Agora <i class="fas fa-check-double text-light"></i></div>
                                </div>
                            </div>
                        `);
                        scrollToBottom();
                        cancelImage();
                    } else {
                        alert('Erro ao enviar imagem: ' + (res.error || 'Falha no envio'));
                        if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar'; }
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Erro desconhecido (status ' + xhr.status + ')';
                    alert('Erro ao enviar imagem: ' + msg);
                    if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar'; }
                },
                complete: function() { _isSendingImage = false; }
            });
        }

        // Sobrescrever sendMessage para enviar imagem se tiver uma pendente
        const _originalSendMessage = sendMessage;
        // Overriding sendMessage to also handle image
        $(document).off('click', '.send-btn').on('click', '.send-btn', function() {
            if (_imageBase64) {
                sendImage();
            } else {
                sendMessage();
            }
        });

        // ════════════════════════════════════════════════════
        // FEATURE: GRAVAÇÃO E ENVIO DE ÁUDIO
        // ════════════════════════════════════════════════════
        let _mediaRecorder = null;
        let _audioChunks   = [];
        let _audioBlob     = null;
        let _audioTimerInt = null;
        let _audioSeconds  = 0;

        function startRecording() {
            if (!currentChatId) { alert('Selecione uma conversa primeiro.'); return; }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Seu navegador não suporta gravação de áudio. Use Chrome ou Firefox.');
                return;
            }

            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function(stream) {
                    _audioChunks = [];
                    _audioBlob   = null;
                    _audioSeconds = 0;

                    // Tenta OGG/Opus primeiro (nativo do WhatsApp), fallback para webm
                    const mimeType = MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')
                        ? 'audio/ogg;codecs=opus'
                        : 'audio/webm;codecs=opus';

                    _mediaRecorder = new MediaRecorder(stream, { mimeType });
                    _mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) _audioChunks.push(e.data); };
                    _mediaRecorder.onstop = function() {
                        stream.getTracks().forEach(t => t.stop());
                        _audioBlob = new Blob(_audioChunks, { type: mimeType });
                        const url = URL.createObjectURL(_audioBlob);
                        const player = document.getElementById('audioPlayback');
                        player.src = url;
                        player.style.display = 'block';

                        document.getElementById('audioRecordStatus').style.display = 'none';
                        document.getElementById('stopRecordBtn').style.display = 'none';
                        document.getElementById('sendAudioBtn').style.display = 'inline-block';
                        clearInterval(_audioTimerInt);
                    };

                    _mediaRecorder.start(100);

                    // Anti-ban: limitar a 2 minutos
                    setTimeout(() => {
                        if (_mediaRecorder && _mediaRecorder.state === 'recording') {
                            stopRecording();
                            alert('Duração máxima de 2 minutos atingida. Envie o áudio.');
                        }
                    }, 120000);

                    // Mostrar UI de gravação
                    const area = document.getElementById('audioRecordArea');
                    area.style.display = 'block';
                    document.getElementById('audioRecordStatus').style.display = 'inline';
                    document.getElementById('stopRecordBtn').style.display = 'inline-block';
                    document.getElementById('sendAudioBtn').style.display = 'none';
                    document.getElementById('audioPlayback').style.display = 'none';

                    // Timer
                    _audioTimerInt = setInterval(() => {
                        _audioSeconds++;
                        const m = Math.floor(_audioSeconds / 60);
                        const s = _audioSeconds % 60;
                        document.getElementById('audioTimer').textContent = m + ':' + (s < 10 ? '0' : '') + s;
                    }, 1000);
                })
                .catch(function(err) {
                    alert('Não foi possível acessar o microfone: ' + err.message);
                });
        }

        function stopRecording() {
            if (_mediaRecorder && _mediaRecorder.state === 'recording') {
                _mediaRecorder.stop();
            }
            clearInterval(_audioTimerInt);
        }

        function cancelAudio() {
            if (_mediaRecorder && _mediaRecorder.state === 'recording') {
                _mediaRecorder.stop();
            }
            clearInterval(_audioTimerInt);
            _audioBlob   = null;
            _audioChunks = [];
            document.getElementById('audioRecordArea').style.display = 'none';
            document.getElementById('audioPlayback').style.display   = 'none';
            document.getElementById('audioRecordStatus').style.display = 'inline';
            document.getElementById('stopRecordBtn').style.display = 'inline-block';
            document.getElementById('sendAudioBtn').style.display = 'none';
            document.getElementById('audioTimer').textContent = '0:00';
        }

        function sendAudio() {
            if (!_audioBlob || !currentChatId) return;

            const btn = document.getElementById('sendAudioBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            const reader = new FileReader();
            reader.onloadend = function() {
                const base64 = reader.result.split(',')[1];

                $.ajax({
                    url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/send-audio',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: JSON.stringify({ base64: base64, mimetype: _audioBlob.type }),
                    contentType: 'application/json',
                    success: function(res) {
                        if (res.success) {
                            $('#chat-messages-area').append(`
                                <div class="message-row message-out">
                                    <div class="bubble out">
                                        🎙️ <em style="font-size:0.85rem;">Áudio enviado</em>
                                        <div class="meta">Agora <i class="fas fa-check-double text-light"></i></div>
                                    </div>
                                </div>
                            `);
                            scrollToBottom();
                            cancelAudio();
                        } else {
                            alert('Erro ao enviar áudio: ' + (res.error || 'Falha no envio'));
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Áudio';
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Erro desconhecido (status ' + xhr.status + ')';
                        alert('Erro ao enviar áudio: ' + msg);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Áudio';
                    }
                });
            };
            reader.readAsDataURL(_audioBlob);
        }

        // ════════════════════════════════════════════════════
        // FEATURE: AGENDAMENTO DE MENSAGENS
        // ════════════════════════════════════════════════════
        function openScheduleModal() {
            if (!currentChatId) { alert('Selecione uma conversa primeiro.'); return; }

            // Pre-fill com o conteúdo do textarea se houver
            const currentMsg = $('#msgInput').val().trim();
            if (currentMsg) {
                document.getElementById('scheduleMsgContent').value = currentMsg;
            }

            // Define data mínima (agora + 5 min)
            const minDate = new Date(Date.now() + 5 * 60000);
            const pad = n => String(n).padStart(2, '0');
            const minStr = `${minDate.getFullYear()}-${pad(minDate.getMonth()+1)}-${pad(minDate.getDate())}T${pad(minDate.getHours())}:${pad(minDate.getMinutes())}`;
            document.getElementById('scheduleDatetime').min = minStr;
            document.getElementById('scheduleDatetime').value = '';
            document.getElementById('scheduleResult').style.display = 'none';

            new bootstrap.Modal(document.getElementById('scheduleModal')).show();
        }

        function confirmSchedule() {
            const content     = document.getElementById('scheduleMsgContent').value.trim();
            const scheduledAt = document.getElementById('scheduleDatetime').value;

            if (!content) { alert('Digite a mensagem a ser agendada.'); return; }
            if (!scheduledAt) { alert('Selecione a data e hora do envio.'); return; }

            const btn = document.querySelector('#scheduleModal .btn-warning');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';

            $.ajax({
                url: '{{ url("/whatsapp/chat") }}/' + currentChatId + '/schedule',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: JSON.stringify({ content: content, scheduled_at: scheduledAt }),
                contentType: 'application/json',
                success: function(res) {
                    if (res.success) {
                        const resultEl = document.getElementById('scheduleResult');
                        resultEl.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${escapeHtml(res.message ?? '')}`;
                        resultEl.style.display = 'block';
                        document.getElementById('scheduleMsgContent').value = '';
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('scheduleModal'))?.hide();
                        }, 2500);
                    } else {
                        alert('Erro ao agendar: ' + (res.error || 'Falha no agendamento'));
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Erro desconhecido (status ' + xhr.status + ')';
                    alert('Erro ao agendar: ' + msg);
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            });
        }

        function assignChatToMe() {
            if (!currentChatId) return;
            $.post('{{ url("/whatsapp/chat") }}/' + currentChatId + '/assign', { _token: csrfToken }, function(res) {
                if(res.success) {
                    loadChatData(currentChatId);
                }
            });
        }

        function toggleBotStatus() {
            if (!currentChatId) return;
            const btn = document.getElementById('btn-toggle-bot');
            const isActive = !btn.classList.contains('bg-success');
            $.post('{{ url("/whatsapp/chat") }}/' + currentChatId + '/toggle-bot', { 
                _token: csrfToken,
                is_bot_active: isActive
            }, function(res) {
                if(res.success) {
                    loadChatData(currentChatId);
                }
            });
        }
    </script>
</body>
</html>
