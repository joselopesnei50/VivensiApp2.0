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
    <title>Vivensi para MEI e Pequenas Empresas | Clientes, WhatsApp, Finanças e IA</title>
    <meta name="description" content="Vivensi para MEI, autônomos e pequenas empresas: CRM de clientes, WhatsApp comercial, fluxo de caixa, NFS-e, teto MEI e a Sala de Estratégia com 5 agentes de IA.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        :root {
            --brand: #f59e0b;
            --brand-dark: #d97706;
            --brand-bg: #fffbeb;
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
        .nav-logo-img { width: 38px; height: 38px; object-fit: contain; }
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
        .hero-title { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.12; letter-spacing: -1.5px; color: var(--ink); max-width: 800px; margin: 0 auto 18px; text-align: center; }
        .hero-sub { font-size: 1.05rem; color: var(--muted); max-width: 680px; margin: 0 auto 36px; text-align: center; line-height: 1.65; }
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
        .bruce-card {
            display: flex; align-items: center; gap: 32px;
            background: #0A0A0B;
            border: 1px solid rgba(255,122,26,.22);
            border-radius: 20px;
            padding: 40px 44px;
        }
        .bruce-card-icon {
            flex-shrink: 0;
            width: 120px; height: 120px;
            border-radius: 24px;
            background: #0f0f1e;
            border: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; justify-content: center;
        }
        .bruce-card-icon img { width: 90px; height: 90px; display: block; }
        .bruce-card-body { flex: 1; min-width: 0; }
        .bruce-tag {
            display: inline-flex; align-items: center;
            background: rgba(255,122,26,.14);
            border: 1px solid rgba(255,122,26,.35);
            color: #FF7A1A;
            font-size: .68rem; font-weight: 800;
            padding: 5px 12px; border-radius: 100px;
            text-transform: uppercase; letter-spacing: 1.2px;
            margin-bottom: 14px;
        }
        .bruce-h {
            color: #fff; font-size: clamp(1.4rem, 2.6vw, 1.9rem);
            font-weight: 800; line-height: 1.2; letter-spacing: -.5px;
            margin: 0 0 10px;
        }
        .bruce-h em { font-style: normal; color: #FF7A1A; }
        .bruce-p {
            color: rgba(255,255,255,.78); font-size: .95rem;
            line-height: 1.65; margin: 0 0 18px; max-width: 640px;
        }
        .bruce-ulist { list-style: none; padding: 0; margin: 0 0 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
        .bruce-ulist li { color: rgba(255,255,255,.82); font-size: .85rem; display: flex; align-items: flex-start; gap: 8px; line-height: 1.5; }
        .bruce-ulist li i { color: #FF7A1A; margin-top: 3px; font-size: .78rem; }
        .bruce-ulist li strong { color: #fff; font-weight: 700; }
        .bruce-ctas { display: flex; gap: 12px; flex-wrap: wrap; }
        .bruce-cta-main {
            display: inline-flex; align-items: center; gap: 8px;
            background: #FF7A1A; color: #fff;
            font-size: .9rem; font-weight: 800;
            padding: 12px 22px; border-radius: 10px;
            transition: background .15s;
        }
        .bruce-cta-main:hover { background: #ea580c; color: #fff; }
        .bruce-cta-out {
            display: inline-flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,.75); font-size: .85rem; font-weight: 600;
            padding: 12px 18px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,.14);
            transition: color .15s, background .15s;
        }
        .bruce-cta-out:hover { color: #fff; background: rgba(255,255,255,.06); }
        @media (max-width: 860px) {
            .bruce-card { flex-direction: column; align-items: flex-start; padding: 32px 24px; gap: 20px; }
            .bruce-card-icon { width: 80px; height: 80px; border-radius: 18px; }
            .bruce-card-icon img { width: 64px; height: 64px; }
            .bruce-ulist { grid-template-columns: 1fr; }
        }

        /* ── Destaque Marketing (grid de 8 servicos, fundo escuro) ────────── */
        .mkt-highlight {
            padding: 90px 0;
            background: linear-gradient(180deg, #0a0e1a 0%, #131830 100%);
        }
        .mkt-head { max-width: 780px; margin: 0 auto 44px; text-align: center; }
        .mkt-eyebrow {
            display: inline-block; font-size: .68rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.4px;
            color: #FF7A1A;
            background: rgba(255,122,26,.12);
            border: 1px solid rgba(255,122,26,.28);
            padding: 6px 14px; border-radius: 100px; margin-bottom: 16px;
        }
        .mkt-title { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; color: #fff; line-height: 1.2; letter-spacing: -.8px; margin: 0 0 12px; }
        .mkt-title em { font-style: normal; color: #FF7A1A; }
        .mkt-sub { font-size: 1rem; color: rgba(255,255,255,.72); line-height: 1.65; margin: 0; }

        .mkt-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
        @media (max-width: 1024px) { .mkt-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 540px)  { .mkt-grid { grid-template-columns: 1fr; } }

        .mkt-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px; padding: 22px 20px;
            transition: border-color .15s, transform .15s;
            position: relative;
        }
        .mkt-card:hover { border-color: rgba(255,122,26,.4); transform: translateY(-2px); }
        .mkt-card-hot { border-color: rgba(255,122,26,.5); background: rgba(255,122,26,.06); }
        .mkt-hot-tag {
            position: absolute; top: -10px; right: 14px;
            background: #FF7A1A; color: #fff;
            font-size: .58rem; font-weight: 800;
            padding: 3px 10px; border-radius: 100px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .mkt-ico {
            width: 40px; height: 40px; border-radius: 10px;
            background: rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.82); font-size: 1.05rem;
            margin-bottom: 14px;
        }
        .mkt-ico-brand { background: rgba(255,122,26,.14); color: #FF7A1A; }
        .mkt-ico-wa    { background: rgba(34,197,94,.14);  color: #22c55e; }
        .mkt-card h4 {
            color: #fff; font-size: .92rem; font-weight: 800;
            line-height: 1.3; margin: 0 0 6px;
        }
        .mkt-card p {
            color: rgba(255,255,255,.7); font-size: .8rem;
            line-height: 1.55; margin: 0;
        }

        .mkt-cta-box {
            display: flex; align-items: center; justify-content: space-between;
            gap: 20px; flex-wrap: wrap;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px; padding: 20px 24px;
        }
        .mkt-cta-box strong { display: block; color: #fff; font-size: 1.02rem; font-weight: 800; margin-bottom: 4px; }
        .mkt-cta-box span   { color: rgba(255,255,255,.7); font-size: .85rem; }
        .mkt-cta-box .btn-primary { background: #FF7A1A; border-color: #FF7A1A; color: #fff; }
        .mkt-cta-box .btn-primary:hover { background: #ea580c; }

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

        /* ── SERVIÇOS ── */
        .svc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 992px) { .svc-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .svc-grid { grid-template-columns: 1fr; } }
        .svc-card { background: var(--bg); border: 1px solid var(--border-soft); border-radius: 16px; padding: 26px 24px; transition: border-color 0.2s, transform 0.2s; }
        .svc-card:hover { border-color: var(--brand); transform: translateY(-2px); }
        .svc-icon { width: 40px; height: 40px; border-radius: 11px; background: var(--brand-bg); color: var(--brand-dark); display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 16px; }
        .svc-card h4 { font-size: 1rem; font-weight: 800; color: var(--ink); margin-bottom: 12px; }
        .svc-card ul { list-style: none; }
        .svc-card ul li { display: flex; align-items: flex-start; gap: 9px; font-size: 0.85rem; color: var(--ink-soft); padding: 4px 0; line-height: 1.5; }
        .svc-card ul li i { color: var(--brand); font-size: 0.68rem; margin-top: 6px; flex-shrink: 0; }

        .dark { background: var(--dark); color: var(--dark-text); padding: 90px 0; }
        .dark .section-eyebrow { color: var(--brand); }
        .dark .section-title { color: #fff; }
        .dark .section-sub { color: var(--dark-muted); }

        /* ── SALA DE ESTRATÉGIA — PREMIUM ── */
        #sala { position: relative; overflow: hidden; }
        .sala-kv { position: relative; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; padding: 54px 48px; margin-bottom: 52px; }
        @media (max-width: 768px) { .sala-kv { padding: 34px 24px; } }
        .sala-kv-title { font-size: clamp(1.9rem, 4.5vw, 3rem); font-weight: 900; line-height: 1.1; letter-spacing: -1.8px; margin-bottom: 18px; color: #fff; }
        .sala-kv-sub { font-size: 1rem; color: rgba(255,255,255,0.5); max-width: 680px; line-height: 1.78; }
        /* pipeline */
        .sala-pipeline { display: flex; align-items: center; justify-content: center; gap: 0; margin-bottom: 52px; flex-wrap: wrap; row-gap: 20px; }
        .pl-agent { display: flex; flex-direction: column; align-items: center; gap: 10px; min-width: 90px; }
        .pl-avatar { width: 58px; height: 58px; border-radius: 50%; background: rgba(255,255,255,0.04); border: 1.5px solid rgba(245,158,11,0.28); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: var(--brand); transition: all 0.2s; backdrop-filter: blur(6px); }
        .pl-agent:hover .pl-avatar { border-color: var(--brand); }
        .pl-name { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.65); text-align: center; line-height: 1.35; max-width: 80px; }
        .pl-arrow { color: rgba(245,158,11,0.35); font-size: 0.85rem; padding: 0 8px; flex-shrink: 0; }
        .pl-result { display: flex; flex-direction: column; align-items: center; gap: 10px; min-width: 100px; }
        .pl-result .pl-avatar { width: 66px; height: 66px; background: rgba(245,158,11,0.1); border-color: var(--brand); font-size: 1.3rem; }
        .pl-result .pl-name { color: var(--brand); font-weight: 800; font-size: 0.73rem; }
        /* outcome cards premium */
        .sala-outcomes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 768px) { .sala-outcomes { grid-template-columns: 1fr; } }
        .oc { background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.07); border-radius: 18px; padding: 30px 26px; position: relative; overflow: hidden; transition: border-color 0.2s, transform 0.2s; }
        .oc::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: linear-gradient(180deg, #f59e0b, #8b5cf6); border-radius: 3px 0 0 3px; }
        .oc:hover { border-color: rgba(245,158,11,0.22); transform: translateY(-3px); }
        .oc-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(245,158,11,0.1); color: var(--brand); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; margin-bottom: 16px; }
        .oc h6 { font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .oc p { font-size: 0.85rem; color: rgba(255,255,255,0.48); line-height: 1.68; }

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
        .access-form-card .btn-fill { background: var(--brand); color: #fff; border: none; width: 100%; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 0.92rem; transition: background 0.15s; text-align: center; display: block; }
        .access-form-card .btn-fill:hover { background: var(--brand-dark); }

        .mark { position: relative; white-space: nowrap; }
        .mark::after { content: ''; position: absolute; left: 0; right: 0; bottom: 4px; height: 10px; background: var(--brand); opacity: 0.45; z-index: -1; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
        .info-card { background: rgba(255,255,255,0.025); border: 1px solid var(--dark-border); border-radius: 16px; padding: 26px 22px; }
        .info-card-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(245,158,11,0.18); color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; }
        .info-card h4 { font-size: 0.98rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .info-card p { font-size: 0.85rem; color: var(--dark-muted); line-height: 1.6; }

        .training-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        @media (max-width: 768px) { .training-grid { grid-template-columns: 1fr; } }
        .training-card { background: var(--dark-2); border: 1px solid var(--dark-border); border-radius: 14px; padding: 22px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .training-card-left { display: flex; align-items: center; gap: 14px; }
        .training-tag { background: rgba(245,158,11,0.2); color: var(--brand); font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; min-width: 50px; text-align: center; }
        .training-card h5 { font-size: 0.92rem; font-weight: 700; color: #fff; margin-bottom: 3px; }
        .training-card span { font-size: 0.78rem; color: var(--dark-muted); }
        .training-badge { font-size: 0.72rem; font-weight: 700; color: var(--brand); background: rgba(245,158,11,0.15); padding: 4px 10px; border-radius: 100px; }

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
            <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi" class="nav-logo-img">
            <span class="nav-logo-text">
                <strong>Vivensi</strong>
                <span>MEI &amp; Empresas</span>
            </span>
        </a>
        <div class="nav-links">
            <a href="#fluxo">Como funciona</a>
            <a href="#servicos">Serviços</a>
            <a href="#sala">Sala de Estratégia</a>
            <a href="#plano">Plano</a>
            <a href="#conformidade">Base legal</a>
            <a href="{{ route('register') }}" class="nav-cta">Criar minha conta</a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container" style="text-align: center;">
        <h1 class="hero-title">Seu negócio inteiro em <span class="mark">um só painel</span>: clientes, WhatsApp, dinheiro e IA.</h1>
        <p class="hero-sub">Para MEI, autônomos e pequenas empresas: CRM de clientes, atendimento comercial pelo WhatsApp, fluxo de caixa com NFS-e, termômetro do teto MEI — e uma Sala de Estratégia onde 5 agentes de IA analisam seus números e dizem qual é a próxima jogada.</p>
        <div class="hero-ctas">
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Criar minha conta</a>
            <a href="{{ url('/agendar') }}" class="btn-ghost"><i class="fas fa-calendar-check"></i> Agendar demonstração</a>
        </div>
        <div class="hero-mini-grid">
            <div class="hero-mini">
                <strong>Termômetro do Teto MEI</strong>
                <span>Avisa quando você chega a 70% e 90% do limite anual de R$ 81 mil — e lembra o DAS todo dia 20.</span>
            </div>
            <div class="hero-mini">
                <strong>WhatsApp comercial</strong>
                <span>Atendimento, chatbot com IA treinável, disparo em massa e automações — no número do seu negócio.</span>
            </div>
            <div class="hero-mini">
                <strong>Sala de Estratégia</strong>
                <span>5 agentes de IA debatem seus dados reais e entregam UMA ação prioritária com plano de execução.</span>
            </div>
        </div>
    </div>
</section>

{{-- Destaque Bruce IA — chamada com identidade visual do Bruce (fundo escuro + accent laranja). --}}
<section class="bruce-highlight" id="ia">
    <div class="container">
        <div class="bruce-card">
            <div class="bruce-card-icon">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="88" height="88">
            </div>
            <div class="bruce-card-body">
                <span class="bruce-tag">Bruce IA · aplicada ao seu negócio</span>
                <h2 class="bruce-h">Não é chatbot. É IA que <em>entende de negócio</em>.</h2>
                <p class="bruce-p">O Bruce IA é o copiloto do seu painel: lê seus dados reais (clientes, vendas, WhatsApp, caixa), sugere a próxima ação e ainda escreve pra você. Sem custo extra, sem addon, dentro do plano.</p>
                <ul class="bruce-ulist">
                    <li><i class="fas fa-check"></i> <strong>Copywriting comercial</strong> — responde cliente no WhatsApp com o tom do seu negócio</li>
                    <li><i class="fas fa-check"></i> <strong>Análise de dados</strong> — lê seu fluxo de caixa e diz onde tem dinheiro parado</li>
                    <li><i class="fas fa-check"></i> <strong>Sala de Estratégia</strong> — 5 agentes debatem e entregam UMA decisão</li>
                    <li><i class="fas fa-check"></i> <strong>Marketing pronto</strong> — post, calendário editorial e plano em minutos</li>
                </ul>
                <div class="bruce-ctas">
                    <a href="{{ route('register') }}" class="bruce-cta-main"><i class="fas fa-arrow-right"></i> Ativar o Bruce no meu negócio</a>
                    <a href="#marketing" class="bruce-cta-out">Ver o que o Bruce faz por você <i class="fas fa-chevron-down"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-soft" id="fluxo">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Como funciona</div>
                <h2 class="section-title">Da venda do dia à decisão da semana.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi foi pensada para quem toca o negócio sozinho ou com equipe pequena — sem tempo pra planilha, mas com necessidade real de saber quem é cliente, quanto entrou e o que fazer a seguir.</p>
        </div>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">01</div>
                <div class="step-title">Cadastra o cliente</div>
                <div class="step-text">CRM simples: cadastro rápido, histórico de conversa e venda — tudo ligado ao WhatsApp.</div>
            </div>
            <div class="step-card">
                <div class="step-num">02</div>
                <div class="step-title">Vende e registra</div>
                <div class="step-text">Recibo ou NFS-e na hora, receita lançada no fluxo de caixa e teto MEI atualizado automaticamente.</div>
            </div>
            <div class="step-card">
                <div class="step-num">03</div>
                <div class="step-title">Atende no WhatsApp</div>
                <div class="step-text">Chatbot com a Bruce IA responde, etiqueta e qualifica — você entra só quando precisa fechar.</div>
            </div>
            <div class="step-card">
                <div class="step-num">04</div>
                <div class="step-title">Decide com a IA</div>
                <div class="step-text">A Sala de Estratégia analisa clientes, notas e caixa e devolve a próxima ação com plano no Kanban.</div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="servicos">
    <div class="container">
        <div class="section-eyebrow">Todos os serviços</div>
        <h2 class="section-title">Tudo que o painel MEI &amp; Empresas entrega.</h2>
        <p class="section-sub">Sem módulo escondido, sem addon pago por fora. Isso aqui é o painel completo que você recebe ao assinar.</p>
        <div class="svc-grid">
            <div class="svc-card">
                <div class="svc-icon"><i class="fas fa-address-book"></i></div>
                <h4>CRM &amp; Clientes</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Meus Clientes com histórico completo</li>
                    <li><i class="fas fa-check"></i>Cadastro rápido de cliente</li>
                    <li><i class="fas fa-check"></i>Prospecção de clientes com IA</li>
                </ul>
            </div>
            <div class="svc-card">
                <div class="svc-icon"><i class="fab fa-whatsapp"></i></div>
                <h4>WhatsApp Comercial</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Chat e atendimento omnichannel</li>
                    <li><i class="fas fa-check"></i>Chatbot com IA Bruce — você treina do seu jeito</li>
                    <li><i class="fas fa-check"></i>Etiquetas, disparo em massa e automações</li>
                    <li><i class="fas fa-check"></i>Formulários conversacionais</li>
                    <li><i class="fas fa-check"></i>Opt-in e campanhas dentro da LGPD</li>
                </ul>
            </div>
            <div class="svc-card">
                <div class="svc-icon"><i class="fas fa-bullhorn"></i></div>
                <h4>Marketing &amp; Vendas</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Landing pages prontas pra divulgar</li>
                    <li><i class="fas fa-check"></i>Social AI Hub — gera posts pras redes</li>
                    <li><i class="fas fa-check"></i>Hub de Marketing IA (estratégia)</li>
                    <li><i class="fas fa-check"></i>Gestão de redes sociais</li>
                </ul>
            </div>
            <div class="svc-card">
                <div class="svc-icon"><i class="fas fa-wallet"></i></div>
                <h4>Gestão Financeira</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Fluxo de caixa em tempo real</li>
                    <li><i class="fas fa-check"></i>Recibos e NFS-e — emissão rápida</li>
                    <li><i class="fas fa-check"></i>Conciliação bancária</li>
                    <li><i class="fas fa-check"></i>Planejamento anual</li>
                </ul>
            </div>
            <div class="svc-card">
                <div class="svc-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <h4>Rotina Fiscal MEI</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Termômetro do teto MEI (alerta em 70% e 90%)</li>
                    <li><i class="fas fa-check"></i>Lembrete do DAS todo dia 20</li>
                    <li><i class="fas fa-check"></i>Dossiê fiscal auditável — NFS-e anexada em cada receita</li>
                </ul>
            </div>
            <div class="svc-card" style="border-color: var(--brand);">
                <div class="svc-icon"><i class="fas fa-brain"></i></div>
                <h4>Inteligência Artificial</h4>
                <ul>
                    <li><i class="fas fa-check"></i>Bruce IA — assistente integrado em todo o sistema</li>
                    <li><i class="fas fa-check"></i><strong>Sala de Estratégia — seu conselho de 5 agentes de IA</strong></li>
                    <li><i class="fas fa-check"></i>Sem addon, sem mensalidade extra de IA</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Destaque Marketing — 8 serviços em grid escuro, foco em conversão. --}}
<section class="mkt-highlight" id="marketing">
    <div class="container">
        <div class="mkt-head">
            <span class="mkt-eyebrow">Marketing &amp; Atendimento com IA</span>
            <h2 class="mkt-title">Do post que sai hoje ao WhatsApp que responde <em>sozinho</em>.</h2>
            <p class="mkt-sub">Você não precisa contratar agência, social media, redator, plataforma de e-mail e disparo de WhatsApp separados. O Vivensi entrega tudo integrado — e o Bruce IA faz o trabalho pesado por você.</p>
        </div>

        <div class="mkt-grid">
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-image"></i></div>
                <h4>Criação de post para rede social</h4>
                <p>Texto + imagem prontos em minutos. Você dá o tema, o Bruce escreve com o tom do seu negócio e sugere criativo.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-calendar-alt"></i></div>
                <h4>Calendário editorial</h4>
                <p>Pauta mensal automática por segmento, datas comemorativas e sazonalidade. Vê o mês inteiro em um clique.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-clock"></i></div>
                <h4>Agendamento de post</h4>
                <p>Programa Instagram, Facebook e outras redes direto do painel. Publica no horário que engaja mais.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-bullseye"></i></div>
                <h4>Plano de marketing</h4>
                <p>Objetivos, canais, metas e cronograma organizados pelo Bruce. Sai do "e-mail pontual" pra estratégia.</p>
            </div>
            <div class="mkt-card mkt-card-hot">
                <span class="mkt-hot-tag">Destaque</span>
                <div class="mkt-ico mkt-ico-brand"><i class="fas fa-brain"></i></div>
                <h4>Sala de Estratégia</h4>
                <p>5 agentes de IA analisam seus números e entregam UMA ação prioritária. Direção, não relatório.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-envelope-open-text"></i></div>
                <h4>E-mail marketing</h4>
                <p>Campanhas, templates, listas segmentadas e disparo via Brevo API — sem SMTP pra configurar.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico mkt-ico-wa"><i class="fab fa-whatsapp"></i></div>
                <h4>API Oficial Meta WhatsApp</h4>
                <p>Cloud API homologada com Meta. Número Business verificado, templates aprovados, zero risco de ban.</p>
            </div>
            <div class="mkt-card">
                <div class="mkt-ico"><i class="fas fa-plug"></i></div>
                <h4>API nativa Vivensi</h4>
                <p>Integre seu site, e-commerce ou app com o painel. Webhooks, tokens per-tenant, tudo documentado.</p>
            </div>
        </div>

        <div class="mkt-cta-box">
            <div>
                <strong>Tudo isso incluído no seu plano.</strong>
                <span>Sem addon, sem contrato de agência, sem tarifa por mensagem escondida.</span>
            </div>
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-rocket"></i> Começar agora</a>
        </div>
    </div>
</section>

<section class="dark" id="sala" style="padding: 90px 0;">
    <div class="container">
        <h2 class="section-title" style="color:#fff; max-width: 760px;">Sala de Estratégia: um conselho de IA trabalhando pro seu negócio.</h2>
        <p class="section-sub" style="max-width: 680px; color: rgba(255,255,255,0.5);">Grandes empresas têm diretoria pra decidir o próximo passo. Agora você também. Cinco agentes de IA debatem seus números reais e devolvem UMA decisão — não um relatório com 40 gráficos.</p>

        <div class="sala-kv">
            <div class="sala-kv-title">Você aperta um botão.<br>Eles debatem. Você recebe<br>UMA ação prioritária.</div>
            <p class="sala-kv-sub">Nada de dashboard com 40 gráficos pra você interpretar. A Sala discute seus dados como uma diretoria de verdade e entrega a conclusão — o que fazer, por que fazer e em que ordem.</p>
        </div>

        <div class="sala-pipeline">
            <div class="pl-agent">
                <div class="pl-avatar"><i class="fas fa-chart-line"></i></div>
                <span class="pl-name">Dados &amp; Mercado</span>
            </div>
            <div class="pl-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="pl-agent">
                <div class="pl-avatar"><i class="fas fa-coins"></i></div>
                <span class="pl-name">Financeiro</span>
            </div>
            <div class="pl-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="pl-agent">
                <div class="pl-avatar"><i class="fas fa-gears"></i></div>
                <span class="pl-name">Operações</span>
            </div>
            <div class="pl-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="pl-agent">
                <div class="pl-avatar"><i class="fas fa-bullseye"></i></div>
                <span class="pl-name">Mobilização</span>
            </div>
            <div class="pl-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="pl-agent">
                <div class="pl-avatar"><i class="fas fa-chess-king"></i></div>
                <span class="pl-name">Estrategista-chefe</span>
            </div>
            <div class="pl-arrow" style="font-size:1.2rem; color: var(--brand);"><i class="fas fa-arrow-right"></i></div>
            <div class="pl-result">
                <div class="pl-avatar"><i class="fas fa-clipboard-check"></i></div>
                <span class="pl-name">UMA Ação Prioritária</span>
            </div>
        </div>

        <div class="sala-outcomes">
            <div class="oc">
                <div class="oc-icon"><i class="fas fa-clipboard-check"></i></div>
                <h6>Vira cartão no Kanban</h6>
                <p>A ação recomendada já entra no seu quadro de tarefas com o plano passo a passo. É executar, não interpretar.</p>
            </div>
            <div class="oc">
                <div class="oc-icon"><i class="fas fa-bell"></i></div>
                <h6>Dispara sozinha em risco</h6>
                <p>Detectou sinal de perigo nos dados — queda de vendas, teto MEI estourando? A Sala se reúne sem você pedir.</p>
            </div>
            <div class="oc">
                <div class="oc-icon"><i class="fas fa-comments-dollar"></i></div>
                <h6>Fala a sua língua</h6>
                <p>Pra MEI e PJ, a conversa é sobre clientes, notas fiscais e faturamento — não jargão corporativo.</p>
            </div>
        </div>
    </div>
</section>

@include('public._plans_section', [
    'accentColor'   => '#f59e0b',
    'bgColor'       => '#f8fafc',
    'wppMessage'    => 'Ol%C3%A1%21%20Quero%20conhecer%20os%20planos%20TopEmpresas%20do%20Vivensi.',
    'fallbackTitle' => 'Planos TopEmpresas — MEI e Pequenas Empresas',
])

<section class="dark" id="escola" style="padding-top: 60px;">
    <div class="container">
        <div class="section-eyebrow">Escola Vivensi</div>
        <h2 class="section-title" style="max-width: 640px;">Aprende junto, sem ter que sair do app.</h2>
        <p class="section-sub" style="max-width: 640px;">Vídeos curtos, e-books simples e plantão ao vivo — sobre MEI na prática, vendas pelo WhatsApp e finanças do negócio, pra quem não tem 2h pra estudar.</p>
        <div class="training-grid">
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">VID</span>
                    <div>
                        <h5>Organize seu MEI em 30 minutos</h5>
                        <span>Vídeo direto · 30 min</span>
                    </div>
                </div>
                <span class="training-badge">Disponível</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">PDF</span>
                    <div>
                        <h5>5 gastos que destroem o caixa (e como cortar)</h5>
                        <span>E-book · leitura de 15 min</span>
                    </div>
                </div>
                <span class="training-badge">Novo</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">LIVE</span>
                    <div>
                        <h5>Plantão de vendas e finanças</h5>
                        <span>Semanal · ao vivo no painel</span>
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
        <h2 class="section-title">Construído com referências legais para quem é MEI ou pequena empresa.</h2>
        <p class="section-sub">O sistema apoia sua organização com base em LGPD, regras do MEI (LC 123/2006) e exigências da Receita — sem complicar a sua vida.</p>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">LGPD</div>
                <div class="step-title">Proteção de dados</div>
                <div class="step-text">Os dados do seu negócio e dos seus clientes são criptografados em repouso. Acesso só você.</div>
            </div>
            <div class="step-card">
                <div class="step-num">MEI</div>
                <div class="step-title">LC 123/2006</div>
                <div class="step-text">Categorias de receita compatíveis com o limite anual e DAS-MEI mensal.</div>
            </div>
            <div class="step-card">
                <div class="step-num">RF</div>
                <div class="step-title">Receita Federal</div>
                <div class="step-text">Relatórios prontos para sua declaração anual (DASN-SIMEI) e IRPF.</div>
            </div>
            <div class="step-card">
                <div class="step-num">PIX</div>
                <div class="step-title">Bancos brasileiros</div>
                <div class="step-text">Importação de extrato OFX dos principais bancos e categorização automática.</div>
            </div>
        </div>
    </div>
</section>

<section class="dark">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Proteção de dados</div>
                <h2 class="section-title">Os dados do seu negócio ficam só com você.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi trata os dados do seu negócio e dos seus clientes com cuidado de banco — criptografia em repouso, isolamento por conta e auditoria de cada acesso. Não vendemos para terceiros, não treinamos modelos com seu histórico.</p>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-lock"></i></div>
                <h4>Criptografia</h4>
                <p>CPF/CNPJ, comprovantes e contas bancárias vivem cifrados em repouso. Acesso só com sua senha + 2FA opcional.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-eye"></i></div>
                <h4>Privacidade</h4>
                <p>Seus dados não treinam IA, não viram produto pra terceiro, não vão pra rede de anunciantes. Ponto.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-user-shield"></i></div>
                <h4>LGPD na prática</h4>
                <p>Exporta tudo em PDF/CSV quando quiser. Deleta tudo quando quiser. Sem retenção fora do prazo legal.</p>
            </div>
        </div>
    </div>
</section>

<section class="final-cta">
    <div class="container">
        <h2>Toque o negócio. A Vivensi cuida do resto.</h2>
        <p>Crie sua conta e comece com o painel completo — ou agende uma demonstração de 20 minutos e veja a Sala de Estratégia funcionando com dados de verdade.</p>
        <div class="hero-ctas" style="margin-bottom: 0;">
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Criar minha conta</a>
            <a href="{{ url('/agendar') }}" class="btn-ghost"><i class="fas fa-calendar-check"></i> Agendar demonstração</a>
        </div>
    </div>
</section>

<footer id="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi" style="width:34px;height:34px;object-fit:contain;">
                    <strong>Vivensi · MEI &amp; Empresas</strong>
                </div>
                <p class="footer-text">CRM, WhatsApp comercial, finanças com NFS-e, rotina fiscal MEI e Sala de Estratégia com IA — em uma só plataforma, conforme a LGPD.</p>
            </div>
            <div class="footer-col">
                <h6>Sistema</h6>
                <ul>
                    <li><a href="#fluxo">Como funciona</a></li>
                    <li><a href="#servicos">Serviços</a></li>
                    <li><a href="#sala">Sala de Estratégia</a></li>
                    <li><a href="#conformidade">Base legal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Acesso</h6>
                <ul>
                    <li><a href="{{ route('register') }}">Criar conta</a></li>
                    <li><a href="{{ route('login') }}">Entrar no sistema</a></li>
                    <li><a href="{{ url('/agendar') }}">Agendar demonstração</a></li>
                    <li><a href="{{ url('/legal/privacidade') }}">Política de Privacidade</a></li>
                    <li><a href="{{ url('/legal/termos') }}">Termos de Uso</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Compromissos</h6>
                <ul>
                    <li><a href="#">Proteção de dados</a></li>
                    <li><a href="#">LGPD na prática</a></li>
                    <li><a href="#">Sem venda de dados</a></li>
                    <li><a href="#">Uso responsável de IA</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} VIVENSIAPP. Todos os direitos reservados. Feito para quem toca o próprio negócio.</span>
            <div class="footer-bottom-tags">
                <span>MEI</span>
                <span>Pequenas Empresas</span>
                <span>LGPD</span>
                <span>Sala de Estratégia</span>
            </div>
        </div>
        <div class="footer-owner" style="text-align:center; padding:16px 0 0; margin-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-size:.82rem; color:rgba(255,255,255,0.55);">
            VivensiApp é um produto da <strong>NC5 HUB DIGITAL LTDA</strong> · CNPJ: 67.848.807/0001-50
        </div>
    </div>
</footer>

</body>
</html>
