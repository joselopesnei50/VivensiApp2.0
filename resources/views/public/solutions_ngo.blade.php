<!DOCTYPE html>
<html lang="pt-BR">
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
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '493025661075925');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=493025661075925&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi para o Terceiro Setor | Gestão Completa para sua ONG</title>
    <meta name="description" content="A plataforma completa para ONGs brasileiras: prestação de contas, doadores, voluntários, editais com IA, transparência LGPD. Sem complicação.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        :root {
            --brand: #10b981;
            --brand-dark: #059669;
            --brand-bg: #ecfdf5;
            --ink: #0f172a;
            --ink-soft: #334155;
            --muted: #64748b;
            --faint: #94a3b8;
            --border: #e2e8f0;
            --border-soft: #f1f5f9;
            --bg: #ffffff;
            --bg-soft: #f8fafc;
            --dark: #0a0e1a;
            --dark-2: #131830;
            --dark-border: rgba(255, 255, 255, 0.08);
            --dark-text: #e2e8f0;
            --dark-muted: rgba(255, 255, 255, 0.55);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); background: var(--bg); line-height: 1.55; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        .nav { position: sticky; top: 0; background: rgba(255,255,255,0.94); backdrop-filter: saturate(180%) blur(12px); border-bottom: 1px solid var(--border-soft); z-index: 100; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 18px 0; }
        .nav-logo { display: flex; align-items: center; gap: 12px; }
        .nav-logo-mark { width: 38px; height: 38px; border-radius: 10px; background: var(--ink); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1rem; }
        .nav-logo-text { display: flex; flex-direction: column; line-height: 1; }
        .nav-logo-text strong { font-size: 0.95rem; font-weight: 800; color: var(--ink); }
        .nav-logo-text span { font-size: 0.62rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 2px; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links a { font-size: 0.88rem; font-weight: 600; color: var(--ink-soft); transition: color 0.15s; }
        .nav-links a:hover { color: var(--brand-dark); }
        .nav-cta { background: var(--ink); color: #fff; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; transition: background 0.15s; }
        .nav-cta:hover { background: #1f2937; }
        @media (max-width: 768px) { .nav-links a:not(.nav-cta) { display: none; } }

        .hero { padding: 80px 0 60px; }
        .hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: var(--brand-dark); background: var(--brand-bg); padding: 7px 14px; border-radius: 100px; margin-bottom: 22px; }
        .hero-title { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.12; letter-spacing: -1.5px; color: var(--ink); max-width: 760px; margin: 0 auto 18px; text-align: center; }
        .hero-sub { font-size: 1.05rem; color: var(--muted); max-width: 640px; margin: 0 auto 36px; text-align: center; line-height: 1.65; }
        .hero-ctas { display: flex; gap: 12px; justify-content: center; margin-bottom: 56px; flex-wrap: wrap; }
        .btn-primary { background: var(--ink); color: #fff; border: 1px solid var(--ink); padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 10px; transition: background 0.15s; }
        .btn-primary:hover { background: #1f2937; border-color: #1f2937; }
        .btn-ghost { background: #fff; color: var(--ink); border: 1px solid var(--border); padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 10px; transition: all 0.15s; }
        .btn-ghost:hover { border-color: var(--brand); color: var(--brand-dark); }
        .hero-mini-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; max-width: 900px; margin: 0 auto; }
        .hero-mini { background: var(--bg-soft); border: 1px solid var(--border-soft); border-radius: 14px; padding: 18px 20px; }
        .hero-mini strong { display: block; font-weight: 800; font-size: 0.95rem; color: var(--ink); margin-bottom: 4px; }
        .hero-mini span { font-size: 0.78rem; color: var(--muted); line-height: 1.5; }
        @media (max-width: 768px) { .hero-mini-grid { grid-template-columns: 1fr; } }

        /* ── Destaque Bruce IA (identidade #0A0A0B + #FF7A1A) ────────────── */
        .bruce-highlight { padding: 60px 0; background: #ffffff; }
        .bruce-card { display:flex; align-items:center; gap:32px; background:#0A0A0B; border:1px solid rgba(255,122,26,.22); border-radius:20px; padding:40px 44px; }
        .bruce-card-icon { flex-shrink:0; width:120px; height:120px; border-radius:24px; background:#0f0f1e; border:1px solid rgba(255,255,255,.08); display:flex; align-items:center; justify-content:center; }
        .bruce-card-icon img { width:90px; height:90px; display:block; }
        .bruce-card-body { flex:1; min-width:0; }
        .bruce-tag { display:inline-flex; align-items:center; background:rgba(255,122,26,.14); border:1px solid rgba(255,122,26,.35); color:#FF7A1A; font-size:.68rem; font-weight:800; padding:5px 12px; border-radius:100px; text-transform:uppercase; letter-spacing:1.2px; margin-bottom:14px; }
        .bruce-h { color:#fff; font-size:clamp(1.4rem,2.6vw,1.9rem); font-weight:800; line-height:1.2; letter-spacing:-.5px; margin:0 0 10px; }
        .bruce-h em { font-style:normal; color:#FF7A1A; }
        .bruce-p { color:rgba(255,255,255,.78); font-size:.95rem; line-height:1.65; margin:0 0 18px; max-width:640px; }
        .bruce-p strong { color:#fff; }
        .bruce-ulist { list-style:none; padding:0; margin:0 0 22px; display:grid; grid-template-columns:1fr 1fr; gap:8px 20px; }
        .bruce-ulist li { color:rgba(255,255,255,.82); font-size:.85rem; display:flex; align-items:flex-start; gap:8px; line-height:1.5; }
        .bruce-ulist li i { color:#FF7A1A; margin-top:3px; font-size:.78rem; }
        .bruce-ulist li strong { color:#fff; font-weight:700; }
        .bruce-ctas { display:flex; gap:12px; flex-wrap:wrap; }
        .bruce-cta-main { display:inline-flex; align-items:center; gap:8px; background:#FF7A1A; color:#fff; font-size:.9rem; font-weight:800; padding:12px 22px; border-radius:10px; transition:background .15s; }
        .bruce-cta-main:hover { background:#ea580c; color:#fff; }
        .bruce-cta-out { display:inline-flex; align-items:center; gap:8px; color:rgba(255,255,255,.75); font-size:.85rem; font-weight:600; padding:12px 18px; border-radius:10px; border:1px solid rgba(255,255,255,.14); transition:color .15s,background .15s; }
        .bruce-cta-out:hover { color:#fff; background:rgba(255,255,255,.06); }
        @media (max-width:860px) {
            .bruce-card { flex-direction:column; align-items:flex-start; padding:32px 24px; gap:20px; }
            .bruce-card-icon { width:80px; height:80px; border-radius:18px; }
            .bruce-card-icon img { width:64px; height:64px; }
            .bruce-ulist { grid-template-columns:1fr; }
        }

        /* ── Modulos NGO (6 blocos tematicos + extras) ─────────────────────── */
        .ngo-modules { padding:90px 0; background:linear-gradient(180deg,#0a0e1a 0%,#131830 100%); }
        .mkt-head { max-width:820px; margin:0 auto 44px; text-align:center; }
        .mkt-eyebrow { display:inline-block; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.4px; color:#FF7A1A; background:rgba(255,122,26,.12); border:1px solid rgba(255,122,26,.28); padding:6px 14px; border-radius:100px; margin-bottom:16px; }
        .mkt-title { font-size:clamp(1.7rem,3.2vw,2.4rem); font-weight:800; color:#fff; line-height:1.2; letter-spacing:-.8px; margin:0 0 12px; }
        .mkt-title em { font-style:normal; color:#FF7A1A; }
        .mkt-sub { font-size:1rem; color:rgba(255,255,255,.72); line-height:1.65; margin:0; }

        .ngo-blocks { display:grid; grid-template-columns:repeat(2,1fr); gap:20px; margin-bottom:32px; }
        @media (max-width:820px) { .ngo-blocks { grid-template-columns:1fr; } }

        .ngo-block { position:relative; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:26px 24px; transition:border-color .15s,transform .15s; }
        .ngo-block:hover { border-color:rgba(255,122,26,.35); transform:translateY(-2px); }
        .ngo-block-hot { border-color:rgba(255,122,26,.5); background:rgba(255,122,26,.06); }
        .ngo-hot-tag { position:absolute; top:-10px; right:16px; background:#FF7A1A; color:#fff; font-size:.6rem; font-weight:800; padding:3px 10px; border-radius:100px; text-transform:uppercase; letter-spacing:1px; }
        .ngo-block-hd { display:flex; align-items:flex-start; gap:14px; margin-bottom:14px; }
        .ngo-block-ico { flex-shrink:0; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
        .ngo-block-hd h3 { color:#fff; font-size:1.02rem; font-weight:800; line-height:1.3; margin:0 0 4px; }
        .ngo-block-hd p { color:rgba(255,255,255,.72); font-size:.82rem; line-height:1.55; margin:0; }
        .ngo-block-list { list-style:none; padding:0; margin:0; display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
        @media (max-width:520px) { .ngo-block-list { grid-template-columns:1fr; } }
        .ngo-block-list li { color:rgba(255,255,255,.82); font-size:.8rem; display:flex; align-items:flex-start; gap:6px; line-height:1.5; }
        .ngo-block-list li i { color:#22c55e; margin-top:3px; font-size:.7rem; }
        .ngo-block-list li strong { color:#fff; font-weight:700; }

        .ngo-extras { background:rgba(255,255,255,.02); border:1px dashed rgba(255,255,255,.12); border-radius:14px; padding:22px 24px; margin-bottom:24px; }
        .ngo-extras h3 { color:rgba(255,255,255,.82); font-size:.85rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; margin:0 0 14px; }
        .ngo-extras-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px 20px; }
        @media (max-width:820px) { .ngo-extras-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:520px) { .ngo-extras-grid { grid-template-columns:1fr; } }
        .ngo-extras-grid div { color:rgba(255,255,255,.75); font-size:.82rem; display:flex; align-items:center; gap:8px; }
        .ngo-extras-grid i { color:#FF7A1A; font-size:.85rem; width:16px; }

        .mkt-cta-box { display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:20px 24px; }
        .mkt-cta-box strong { display:block; color:#fff; font-size:1.02rem; font-weight:800; margin-bottom:4px; }
        .mkt-cta-box span { color:rgba(255,255,255,.7); font-size:.85rem; }
        .mkt-cta-box .btn-primary { background:#FF7A1A; border-color:#FF7A1A; color:#fff; }
        .mkt-cta-box .btn-primary:hover { background:#ea580c; }

        .section { padding: 90px 0; }
        .section-soft { background: var(--bg-soft); }
        .section-eyebrow { font-size: 0.72rem; font-weight: 800; color: var(--brand-dark); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 14px; }
        .section-title { font-size: clamp(1.7rem, 3.5vw, 2.4rem); font-weight: 800; color: var(--ink); letter-spacing: -1px; line-height: 1.18; margin-bottom: 18px; }
        .section-sub { font-size: 1rem; color: var(--muted); max-width: 640px; line-height: 1.65; margin-bottom: 50px; }
        .section-head-split { display: grid; grid-template-columns: 1fr 1.4fr; gap: 60px; margin-bottom: 50px; align-items: end; }
        @media (max-width: 768px) { .section-head-split { grid-template-columns: 1fr; gap: 18px; } }

        .step-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        @media (max-width: 768px) { .step-grid { grid-template-columns: 1fr; } }
        .step-card { background: var(--bg); border: 1px solid var(--border-soft); border-radius: 16px; padding: 26px 22px; transition: border-color 0.2s; }
        .step-card:hover { border-color: var(--brand); }
        .step-num { font-size: 0.72rem; font-weight: 800; color: var(--brand-dark); margin-bottom: 14px; letter-spacing: 1px; }
        .step-title { font-size: 1.02rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
        .step-text { font-size: 0.86rem; color: var(--muted); line-height: 1.6; }

        .dark { background: var(--dark); color: var(--dark-text); padding: 90px 0; }
        .dark .section-eyebrow { color: var(--brand); }
        .dark .section-title { color: #fff; }
        .dark .section-sub { color: var(--dark-muted); }

        .access-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 60px; align-items: center; }
        @media (max-width: 768px) { .access-grid { grid-template-columns: 1fr; gap: 36px; } }
        .access-list { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 30px; margin-top: 28px; }
        .access-list li { list-style: none; display: flex; align-items: center; gap: 10px; font-size: 0.92rem; }
        .access-list li i { color: var(--brand); font-size: 0.75rem; }
        .access-form-card { background: var(--dark-2); border: 1px solid var(--dark-border); border-radius: 18px; padding: 32px; }
        .access-form-card h3 { font-size: 0.78rem; font-weight: 700; color: var(--dark-muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .access-form-card h3 small { color: var(--brand); font-size: 0.7rem; }
        .access-form-card .price { font-size: 3.4rem; font-weight: 800; color: #fff; line-height: 1; letter-spacing: -2px; margin-bottom: 22px; }
        .access-form-card .price small { font-size: 0.85rem; color: var(--dark-muted); font-weight: 500; margin-left: 6px; }
        .access-form-card ul { list-style: none; margin-bottom: 24px; }
        .access-form-card ul li { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: var(--dark-text); padding: 7px 0; }
        .access-form-card ul li i { color: var(--brand); font-size: 0.7rem; }
        .access-form-card .btn-fill { background: var(--brand); color: #fff; border: none; width: 100%; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 0.92rem; transition: background 0.15s; }
        .access-form-card .btn-fill:hover { background: var(--brand-dark); }

        .mark { position: relative; white-space: nowrap; }
        .mark::after { content: ''; position: absolute; left: 0; right: 0; bottom: 4px; height: 10px; background: var(--brand); opacity: 0.45; z-index: -1; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
        .info-card { background: rgba(255,255,255,0.025); border: 1px solid var(--dark-border); border-radius: 16px; padding: 26px 22px; }
        .info-card-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(16,185,129,0.12); color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; }
        .info-card h4 { font-size: 0.98rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .info-card p { font-size: 0.85rem; color: var(--dark-muted); line-height: 1.6; }

        .free-block { background: var(--dark-2); color: #fff; padding: 36px 40px; border-radius: 18px; display: grid; grid-template-columns: auto 1fr; gap: 28px; align-items: center; }
        .free-pill { display: inline-flex; align-items: center; gap: 8px; background: var(--brand); color: #fff; font-size: 0.72rem; font-weight: 800; padding: 6px 12px; border-radius: 100px; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; }
        .free-block h3 { font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1.3; letter-spacing: -0.5px; }
        .free-block p { margin-top: 6px; font-size: 0.9rem; color: var(--dark-muted); }
        @media (max-width: 768px) { .free-block { grid-template-columns: 1fr; padding: 26px; } }

        .training-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        @media (max-width: 768px) { .training-grid { grid-template-columns: 1fr; } }
        .training-card { background: var(--dark-2); border: 1px solid var(--dark-border); border-radius: 14px; padding: 22px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .training-card-left { display: flex; align-items: center; gap: 14px; }
        .training-tag { background: rgba(16,185,129,0.15); color: var(--brand); font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; min-width: 50px; text-align: center; }
        .training-card h5 { font-size: 0.92rem; font-weight: 700; color: #fff; margin-bottom: 3px; }
        .training-card span { font-size: 0.78rem; color: var(--dark-muted); }
        .training-badge { font-size: 0.72rem; font-weight: 700; color: var(--brand); background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 100px; }

        .final-cta { padding: 100px 0; text-align: center; }
        .final-cta h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 800; color: var(--ink); letter-spacing: -1px; line-height: 1.18; margin-bottom: 18px; }
        .final-cta p { font-size: 1rem; color: var(--muted); max-width: 560px; margin: 0 auto 32px; }

        footer { background: var(--dark); color: var(--dark-text); padding: 60px 0 28px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; } }
        .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .footer-brand .nav-logo-mark { background: var(--brand); }
        .footer-brand strong { color: #fff; font-size: 1rem; }
        .footer-text { font-size: 0.86rem; color: var(--dark-muted); line-height: 1.6; max-width: 320px; }
        .footer-col h6 { font-size: 0.72rem; font-weight: 800; color: var(--dark-muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 16px; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { font-size: 0.88rem; color: var(--dark-text); transition: color 0.15s; }
        .footer-col ul li a:hover { color: var(--brand); }
        .footer-bottom { border-top: 1px solid var(--dark-border); padding-top: 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.78rem; color: var(--dark-muted); }
        .footer-bottom-tags { display: flex; gap: 14px; flex-wrap: wrap; }
        .footer-bottom-tags span { color: var(--dark-muted); font-weight: 600; }
    </style>
</head>
<body>

<nav class="nav">
    <div class="container nav-inner">
        <a href="{{ url('/') }}" class="nav-logo">
            <span class="nav-logo-mark">V</span>
            <span class="nav-logo-text">
                <strong>Vivensi</strong>
                <span>Terceiro Setor</span>
            </span>
        </a>
        <div class="nav-links">
            <a href="#fluxo">Como funciona</a>
            <a href="#gratuito">Gratuito</a>
            <a href="#bruce">Bruce IA</a>
            <a href="#conformidade">Conformidade</a>
            <a href="#footer">Base legal</a>
            <a href="{{ route('register') }}" class="nav-cta">Criar conta</a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container" style="text-align: center;">
        <div class="hero-eyebrow"><i class="fas fa-arrow-right"></i> Vivensi Terceiro Setor</div>
        <h1 class="hero-title">O sistema que sua ONG precisa — com <span class="mark">IA aplicada ao terceiro setor</span>.</h1>
        <p class="hero-sub">Doadores, editais, beneficiários, prestação de contas, WhatsApp, marketing e conformidade CEBAS/MROSC/SUAS em uma só plataforma. Com Bruce IA treinado pra falar a língua da sua causa — não jargão de startup.</p>
        <div class="hero-ctas">
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Criar minha conta</a>
            <a href="#modulos" class="btn-ghost"><i class="fas fa-th-large"></i> Ver todos os módulos</a>
        </div>
        <div class="hero-mini-grid">
            <div class="hero-mini">
                <strong>Radar de Editais</strong>
                <span>Bruce vasculha diários oficiais e chamamentos MROSC. Você recebe só o que combina com sua ONG.</span>
            </div>
            <div class="hero-mini">
                <strong>Conformidade Contínua</strong>
                <span>CEBAS · MROSC · SUAS calculados em tempo real. Alerta antes de vencer, não depois.</span>
            </div>
            <div class="hero-mini">
                <strong>Portal de Doadores</strong>
                <span>Recibo automático, informe IR e transparência LGPD em URL própria da ONG.</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-soft" id="fluxo">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Fluxo Integrado</div>
                <h2 class="section-title">Uma experiência guiada do cadastro à prestação de contas.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi foi pensada para ONGs brasileiras de qualquer porte. Cada etapa gera um registro auditável e conectado — nada fica perdido entre planilhas, e-mails e WhatsApp.</p>
        </div>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">01</div>
                <div class="step-title">Cadastra projetos</div>
                <div class="step-text">Nome, orçamento, equipe, metas, marcos planejados e Diário de Evolução com resumo por IA.</div>
            </div>
            <div class="step-card">
                <div class="step-num">02</div>
                <div class="step-title">Bruce IA analisa</div>
                <div class="step-text">Sugere ações, gera propostas para editais e responde sobre dados do projeto em tempo real.</div>
            </div>
            <div class="step-card">
                <div class="step-num">03</div>
                <div class="step-title">Captação e doadores</div>
                <div class="step-text">Portal de doadores com recibo automático, informe IR, campanhas e Pix integrado.</div>
            </div>
            <div class="step-card">
                <div class="step-num">04</div>
                <div class="step-title">Presta contas</div>
                <div class="step-text">DRE, conciliação bancária, relatórios em PDF e portal público de transparência.</div>
            </div>
        </div>
    </div>
</section>

{{-- Destaque Bruce IA para Terceiro Setor — identidade visual do Bruce contextualizada. --}}
<section class="bruce-highlight" id="ia">
    <div class="container">
        <div class="bruce-card">
            <div class="bruce-card-icon">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="88" height="88">
            </div>
            <div class="bruce-card-body">
                <span class="bruce-tag">Bruce IA · treinado para o terceiro setor</span>
                <h2 class="bruce-h">Uma IA que fala <em>doador, edital e beneficiário</em>. Não startup.</h2>
                <p class="bruce-p">Bruce lê seus projetos, transações, cadastros de doadores e chamamentos públicos — e devolve <strong>decisão prática</strong>, não relatório com 40 gráficos. Sem addon, sem mensalidade extra de IA. Dentro do plano.</p>
                <ul class="bruce-ulist">
                    <li><i class="fas fa-check"></i> <strong>Radar de Editais</strong> — Bruce vasculha diários oficiais e alerta o que combina com sua causa</li>
                    <li><i class="fas fa-check"></i> <strong>Gera propostas para editais</strong> — puxa dados reais dos seus projetos e escreve a submissão</li>
                    <li><i class="fas fa-check"></i> <strong>Smart Analysis</strong> — lê fluxo de caixa em linguagem de ONG (custeio, contrapartida, patrocínio)</li>
                    <li><i class="fas fa-check"></i> <strong>Sala de Estratégia</strong> — 5 agentes debatem e entregam UMA ação prioritária</li>
                    <li><i class="fas fa-check"></i> <strong>Marketing IA</strong> — post pra rede social, calendário editorial e e-mail pra doadores</li>
                    <li><i class="fas fa-check"></i> <strong>Qualifica lead no WhatsApp</strong> — IA lê a conversa e classifica se é doador em potencial</li>
                </ul>
                <div class="bruce-ctas">
                    <a href="{{ route('register') }}" class="bruce-cta-main"><i class="fas fa-arrow-right"></i> Ativar o Bruce na minha ONG</a>
                    <a href="#modulos" class="bruce-cta-out">Ver todos os módulos <i class="fas fa-chevron-down"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 6 blocos tematicos com modulos reais do menu NGO — foco em conversao. --}}
<section class="ngo-modules" id="modulos">
    <div class="container">
        <div class="mkt-head">
            <span class="mkt-eyebrow">Módulos exclusivos do terceiro setor</span>
            <h2 class="mkt-title">Tudo que sua ONG faz — em um sistema <em>desenhado pra ONG</em>.</h2>
            <p class="mkt-sub">Você não precisa juntar 6 ferramentas (planilha, MailChimp, plataforma de doação, ChatGPT, Trello, sistema de conformidade). Vivensi entrega o pacote inteiro, integrado.</p>
        </div>

        <div class="ngo-blocks">
            {{-- Bloco 1 · Captacao --}}
            <article class="ngo-block">
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(239,68,68,.14);color:#ef4444;"><i class="fas fa-heart"></i></div>
                    <div>
                        <h3>Captação, Doadores &amp; Recibos</h3>
                        <p>CRM de doadores, portal público, recibo automático, informe IR anual e rifas online. Sem intermediário engolindo taxa.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> CRM de Doadores</li>
                    <li><i class="fas fa-check"></i> Portal de Recibos com link privado</li>
                    <li><i class="fas fa-check"></i> Informe IR anual (PDF pronto)</li>
                    <li><i class="fas fa-check"></i> CRM de Patrocínios</li>
                    <li><i class="fas fa-check"></i> Rifas Online</li>
                    <li><i class="fas fa-check"></i> Landing Pages de captação (builder)</li>
                </ul>
            </article>

            {{-- Bloco 2 · Editais --}}
            <article class="ngo-block ngo-block-hot">
                <span class="ngo-hot-tag">Diferencial Vivensi</span>
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(255,122,26,.16);color:#FF7A1A;"><i class="fas fa-satellite-dish"></i></div>
                    <div>
                        <h3>Editais &amp; Convênios com IA</h3>
                        <p>Bruce vasculha Querido Diário e chamamentos MROSC. Analisa relevância. Gera a proposta. Sua ONG só decide se submete.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> <strong>Radar automático</strong> (Querido Diário integrado)</li>
                    <li><i class="fas fa-check"></i> Análise de relevância pelo Bruce IA</li>
                    <li><i class="fas fa-check"></i> Gerador de proposta com dados reais do projeto</li>
                    <li><i class="fas fa-check"></i> Workspace por edital (upload docs, prazos)</li>
                    <li><i class="fas fa-check"></i> Alertas de vencimento</li>
                    <li><i class="fas fa-check"></i> Histórico completo de submissões</li>
                </ul>
            </article>

            {{-- Bloco 3 · Conformidade --}}
            <article class="ngo-block ngo-block-hot">
                <span class="ngo-hot-tag">Único no mercado</span>
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(16,185,129,.14);color:#10b981;"><i class="fas fa-shield-halved"></i></div>
                    <div>
                        <h3>Conformidade Contínua CEBAS/MROSC/SUAS</h3>
                        <p>Motor que calcula seus índices em tempo real cruzando projetos, atendimentos e financeiro. Nunca mais chegar em auditoria despreparada.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> Dashboard de conformidade em tempo real</li>
                    <li><i class="fas fa-check"></i> Eixos CEBAS · SUAS · MROSC calculados</li>
                    <li><i class="fas fa-check"></i> Planos de ação rastreáveis</li>
                    <li><i class="fas fa-check"></i> Ciclos anuais + snapshot para auditoria</li>
                    <li><i class="fas fa-check"></i> Alertas de documento vencendo</li>
                    <li><i class="fas fa-check"></i> Relatório PDF pronto pro conselho</li>
                </ul>
            </article>

            {{-- Bloco 4 · Beneficiarios --}}
            <article class="ngo-block">
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(139,92,246,.14);color:#8b5cf6;"><i class="fas fa-hand-holding-heart"></i></div>
                    <div>
                        <h3>Beneficiários &amp; Impacto Social</h3>
                        <p>Cadastro com PII cifrado at-rest (LGPD nativo), lista de presença, indicadores sociais e relatório anual de impacto pronto pra apresentar.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> Cadastro LGPD-compliant (encryption at-rest)</li>
                    <li><i class="fas fa-check"></i> Importação massiva via CSV</li>
                    <li><i class="fas fa-check"></i> Lista de Presença por turma/atividade</li>
                    <li><i class="fas fa-check"></i> Indicadores Sociais automáticos</li>
                    <li><i class="fas fa-check"></i> Relatório Anual de Impacto</li>
                    <li><i class="fas fa-check"></i> Fluxo self-service /eu/dados (art. 15/18 LGPD)</li>
                </ul>
            </article>

            {{-- Bloco 5 · WhatsApp/Marketing --}}
            <article class="ngo-block">
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(34,197,94,.14);color:#22c55e;"><i class="fab fa-whatsapp"></i></div>
                    <div>
                        <h3>WhatsApp &amp; Marketing com IA</h3>
                        <p>API oficial Meta + Bruce respondendo doadores 24/7 + calendário editorial + e-mail marketing + disparo em massa com opt-in. Tudo integrado.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> API Oficial Meta WhatsApp Cloud</li>
                    <li><i class="fas fa-check"></i> Disparo em massa (texto, imagem, áudio) com anti-ban</li>
                    <li><i class="fas fa-check"></i> Qualificação de lead via IA</li>
                    <li><i class="fas fa-check"></i> Formulários conversacionais</li>
                    <li><i class="fas fa-check"></i> E-mail marketing com Brevo integrado</li>
                    <li><i class="fas fa-check"></i> Social AI Hub (posts + calendário editorial)</li>
                </ul>
            </article>

            {{-- Bloco 6 · Financeiro/Transparencia --}}
            <article class="ngo-block">
                <div class="ngo-block-hd">
                    <div class="ngo-block-ico" style="background:rgba(59,130,246,.14);color:#3b82f6;"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div>
                        <h3>Financeiro &amp; Prestação de Contas</h3>
                        <p>Fluxo de caixa por projeto, DRE, conciliação bancária, orçamento anual e Portal de Transparência público — sem depender de contador terceirizado pra tudo.</p>
                    </div>
                </div>
                <ul class="ngo-block-list">
                    <li><i class="fas fa-check"></i> Fluxo de Caixa por projeto</li>
                    <li><i class="fas fa-check"></i> DRE automático</li>
                    <li><i class="fas fa-check"></i> Conciliação Bancária</li>
                    <li><i class="fas fa-check"></i> Orçamento Anual</li>
                    <li><i class="fas fa-check"></i> Importação de planilhas CSV</li>
                    <li><i class="fas fa-check"></i> Portal Público de Transparência</li>
                </ul>
            </article>
        </div>

        {{-- Modulos complementares --}}
        <div class="ngo-extras">
            <h3>E ainda inclui:</h3>
            <div class="ngo-extras-grid">
                <div><i class="fas fa-project-diagram"></i> Gestão de Projetos + Kanban + Turmas</div>
                <div><i class="fas fa-id-badge"></i> RH &amp; Voluntariado com certificados</div>
                <div><i class="fas fa-building"></i> Patrimônio &amp; Almoxarifado</div>
                <div><i class="fas fa-file-contract"></i> Contratos Digitais</div>
                <div><i class="fas fa-eye"></i> Central de Auditoria</div>
                <div><i class="fas fa-landmark"></i> Portal de Transparência</div>
                <div><i class="fas fa-graduation-cap"></i> Vivensi Academy (LMS interno)</div>
                <div><i class="fas fa-map-location-dot"></i> Inteligência Territorial</div>
            </div>
        </div>

        <div class="mkt-cta-box">
            <div>
                <strong>Todos os módulos incluídos no seu plano.</strong>
                <span>Sem addon de IA. Sem cobrança por doador cadastrado. Sem tarifa escondida.</span>
            </div>
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-rocket"></i> Começar agora</a>
        </div>
    </div>
</section>

<section class="dark" id="escola" style="padding-top: 60px;">
    <div class="container">
        <div class="section-eyebrow">Escola Vivensi</div>
        <h2 class="section-title" style="max-width: 640px;">Formação continuada dentro do painel.</h2>
        <p class="section-sub" style="max-width: 640px;">Vídeos, e-books, módulos, conteúdos diários, fórum de aprendizagem e emissão de certificado para a equipe da ONG concluir a trilha.</p>
        <div class="training-grid">
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">VID</span>
                    <div>
                        <h5>Captação de recursos na prática</h5>
                        <span>Módulo básico · 24 min</span>
                    </div>
                </div>
                <span class="training-badge">Disponível</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">PDF</span>
                    <div>
                        <h5>Guia de prestação de contas para o conselho fiscal</h5>
                        <span>E-book · leitura orientada</span>
                    </div>
                </div>
                <span class="training-badge">Novo</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">LIVE</span>
                    <div>
                        <h5>Plantão de dúvidas com especialistas</h5>
                        <span>Fórum semanal · certificado</span>
                    </div>
                </div>
                <span class="training-badge">Ao vivo</span>
            </div>
        </div>
    </div>
</section>

<section class="section" id="conformidade">
    <div class="container">
        <div class="section-eyebrow">Base legal</div>
        <h2 class="section-title">Construído com referências legais do terceiro setor.</h2>
        <p class="section-sub">O sistema apoia a ONG com base em LGPD, MROSC, Marco Regulatório das OSCs e exigências da Receita Federal — sem deixar a operação travada.</p>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">LGPD</div>
                <div class="step-title">Proteção de dados</div>
                <div class="step-text">Criptografia AES-256, blind index para CPF e tokens, auditoria de acessos.</div>
            </div>
            <div class="step-card">
                <div class="step-num">MROSC</div>
                <div class="step-title">Marco Regulatório</div>
                <div class="step-text">Termo de fomento, colaboração e parceria — gestão centralizada por edital.</div>
            </div>
            <div class="step-card">
                <div class="step-num">RF</div>
                <div class="step-title">Receita Federal</div>
                <div class="step-text">Informe de Rendimentos para doadores e relatórios prontos para auditoria.</div>
            </div>
            <div class="step-card">
                <div class="step-num">CONANDA</div>
                <div class="step-title">Conselhos</div>
                <div class="step-text">Suporte a projetos de FIA/FUMCAD, parcerias com Conselhos Tutelares e CMDCAs.</div>
            </div>
        </div>
    </div>
</section>

<section class="dark">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Proteção de dados</div>
                <h2 class="section-title">Projetado para proteger informações sensíveis.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi trata dados de doadores, voluntários e beneficiários com foco em LGPD, segurança operacional e rastreabilidade. A experiência foi pensada para que a equipe da ONG nunca exponha informações além do necessário.</p>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-lock"></i></div>
                <h4>Criptografia</h4>
                <p>Dados sensíveis são protegidos em repouso com chave própria do ambiente. Tokens públicos com blind index pesquisável.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-eye"></i></div>
                <h4>Auditoria</h4>
                <p>Ações críticas são registradas para acompanhamento e responsabilização. Logs imutáveis disponíveis por API.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-network-wired"></i></div>
                <h4>Isolamento</h4>
                <p>Documentos, anexos e tokens vivem isolados por ONG. Sem cross-tenant em nenhuma superfície da plataforma.</p>
            </div>
        </div>
    </div>
</section>

@include('public._plans_section', [
    'accentColor'   => '#4f46e5',
    'bgColor'       => '#f8fafc',
    'wppMessage'    => 'Ol%C3%A1%21%20Quero%20conhecer%20os%20planos%20de%20Terceiro%20Setor%20do%20Vivensi.',
    'fallbackTitle' => 'Planos para ONGs de todos os portes',
])

<section class="final-cta">
    <div class="container">
        <h2>Centralize a rotina da sua ONG em uma plataforma feita pra terceiro setor.</h2>
        <p>Cadastre sua organização em minutos e comece a usar hoje mesmo — doadores, editais, WhatsApp, conformidade e Bruce IA já disponíveis desde a primeira sessão.</p>
        <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Criar conta da ONG</a>
    </div>
</section>

<footer id="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <span class="nav-logo-mark">V</span>
                    <strong>Vivensi · Terceiro Setor</strong>
                </div>
                <p class="footer-text">Sistema de gestão integrado para ONGs brasileiras. Segurança LGPD, IA contextual e isolamento por organização.</p>
            </div>
            <div class="footer-col">
                <h6>Sistema</h6>
                <ul>
                    <li><a href="#fluxo">Fluxo de gestão</a></li>
                    <li><a href="#bruce">Bruce IA</a></li>
                    <li><a href="#escola">Escola Vivensi</a></li>
                    <li><a href="#conformidade">Base legal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Acesso</h6>
                <ul>
                    <li><a href="{{ route('register') }}">Cadastro gratuito</a></li>
                    <li><a href="{{ route('login') }}">Entrar no sistema</a></li>
                    <li><a href="{{ url('/legal/privacidade') }}">Política de Privacidade</a></li>
                    <li><a href="{{ url('/legal/termos') }}">Termos de Uso</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Compromissos</h6>
                <ul>
                    <li><a href="#">Proteção de dados</a></li>
                    <li><a href="#">LGPD e auditoria</a></li>
                    <li><a href="#">Isolamento por ONG</a></li>
                    <li><a href="#">Uso responsável de IA</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} VIVENSIAPP. Todos os direitos reservados. Feito para o terceiro setor brasileiro.</span>
            <div class="footer-bottom-tags">
                <span>MROSC</span>
                <span>LGPD</span>
                <span>Bruce IA</span>
            </div>
        </div>
        <div class="footer-owner" style="text-align:center; padding:16px 0 0; margin-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-size:.82rem; color:rgba(255,255,255,0.55);">
            VivensiApp é um produto da <strong>NC5 HUB DIGITAL LTDA</strong> · CNPJ: 67.848.807/0001-50
        </div>
    </div>
</footer>

</body>
</html>
