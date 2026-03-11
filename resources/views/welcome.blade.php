<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi | Gestão Inteligente para ONGs, Projetos e Pessoas</title>
    <meta name="description" content="A plataforma mais completa do Brasil para gestão de ONGs, projetos sociais e equipes. Donor portal, prestação de contas, CRM e muito mais.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/logovivensi.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logovivensi.png') }}">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --ink:#080E1A;--ink2:#111827;
    --blue:#3B6CF6;--blue2:#5B82FF;
    --violet:#7C3AED;--violet2:#9B59F7;
    --teal:#00D4AA;--rose:#E8455A;
    --gold:#F5A623;--white:#FFFFFF;
    --glass:rgba(255,255,255,.06);
    --border:rgba(255,255,255,.1);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden;line-height:1.6}

/* NAV */
.nav{position:fixed;top:0;left:0;right:0;z-index:999;display:flex;justify-content:space-between;align-items:center;padding:16px 6%;background:rgba(8,14,26,.9);backdrop-filter:blur(24px);border-bottom:1px solid var(--border);transition:all .3s}
.nav-logo img{height:34px}
.nav-links{display:flex;gap:28px;list-style:none}
.nav-links a{color:rgba(255,255,255,.6);text-decoration:none;font-size:.875rem;font-weight:500;transition:color .3s}
.nav-links a:hover{color:white}
.nav-ctas{display:flex;gap:12px}
.btn-ghost{color:rgba(255,255,255,.7);text-decoration:none;font-size:.875rem;font-weight:600;padding:9px 20px;border-radius:50px;border:1px solid var(--border);transition:all .3s}
.btn-ghost:hover{background:var(--glass);color:white;border-color:rgba(255,255,255,.2)}
.btn-nav{background:linear-gradient(135deg,var(--blue),var(--violet));color:white;text-decoration:none;font-size:.875rem;font-weight:700;padding:10px 22px;border-radius:50px;box-shadow:0 4px 20px rgba(59,108,246,.35);transition:all .3s}
.btn-nav:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(59,108,246,.5)}
/* Mobile */
.mobile-btn{display:none;background:none;border:none;color:white;font-size:1.4rem;cursor:pointer}
@media(max-width:768px){
    .nav-links,.nav-ctas{display:none}
    .mobile-btn{display:block}
    .mobile-menu{display:none;flex-direction:column;gap:16px;position:fixed;top:70px;left:0;right:0;background:rgba(8,14,26,.98);padding:24px 6%;border-bottom:1px solid var(--border)}
    .mobile-menu.open{display:flex}
    .mobile-menu a{color:rgba(255,255,255,.75);text-decoration:none;font-size:1rem;font-weight:600;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.06)}
}

/* ─── HERO ─── */
.hero{position:relative;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;padding:120px 6% 80px;
    background:radial-gradient(ellipse 100% 80% at 50% 0%, rgba(59,108,246,.2) 0%, transparent 55%),
               radial-gradient(ellipse 60% 50% at 80% 80%, rgba(124,58,237,.12) 0%, transparent 50%),
               var(--ink)}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(59,108,246,.12);border:1px solid rgba(59,108,246,.3);color:var(--blue2);font-size:.78rem;font-weight:700;padding:6px 16px;border-radius:100px;margin-bottom:24px;letter-spacing:.06em;text-transform:uppercase;animation:fadeDown .6s ease forwards}
.hero-badge-dot{width:6px;height:6px;border-radius:50%;background:var(--blue2);animation:pulseBlue 2s infinite}
.hero-title{font-size:clamp(2.8rem,6vw,5.2rem);font-weight:900;line-height:1.0;letter-spacing:-.04em;text-align:center;margin-bottom:24px;animation:fadeUp .8s ease .15s both}
.hero-title .g1{background:linear-gradient(135deg,#fff 0%,rgba(255,255,255,.7) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-title .g2{background:linear-gradient(135deg,var(--blue2),var(--violet2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:1.15rem;color:rgba(255,255,255,.55);text-align:center;max-width:580px;margin:0 auto 40px;line-height:1.7;animation:fadeUp .8s ease .3s both}
.hero-ctas{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:72px;animation:fadeUp .8s ease .45s both}
.btn-hero{background:linear-gradient(135deg,var(--blue),var(--violet));color:white;text-decoration:none;font-weight:700;font-size:1.05rem;padding:16px 40px;border-radius:60px;box-shadow:0 8px 32px rgba(59,108,246,.4);transition:all .3s;display:inline-flex;align-items:center;gap:10px}
.btn-hero:hover{transform:translateY(-3px);box-shadow:0 14px 42px rgba(59,108,246,.6)}
.btn-hero-outline{color:rgba(255,255,255,.8);text-decoration:none;font-weight:600;font-size:1.05rem;padding:16px 32px;border-radius:60px;border:1.5px solid rgba(255,255,255,.18);transition:all .3s;display:inline-flex;align-items:center;gap:10px}
.btn-hero-outline:hover{background:rgba(255,255,255,.07);color:white}

/* MAP SECTION */
.map-section{position:relative;width:100%;max-width:1100px;margin:0 auto;animation:fadeUp .8s ease .6s both}
.map-wrap{position:relative}
#brazil-svg{width:100%;max-width:780px;display:block;margin:0 auto}
/* Dot grid representing data coverage */
.map-dot{position:absolute;border-radius:50%;animation:mapPulse 2.5s ease-in-out infinite}
.map-dot::after{content:'';position:absolute;inset:-5px;border-radius:50%;animation:mapRipple 2.5s ease-out infinite}
/* Floating info cards */
.fcard{position:absolute;background:rgba(13,20,40,.85);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:14px 18px;min-width:160px;white-space:nowrap}
.fcard-label{font-size:.68rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px}
.fcard-value{font-size:1.15rem;font-weight:800;color:white}
.fcard-sub{font-size:.72rem;color:var(--teal);margin-top:3px}
.fc-a{top:8%;left:2%;animation:floatY 4s ease-in-out infinite}
.fc-b{top:12%;right:2%;animation:floatY 5s ease-in-out infinite .6s}
.fc-c{bottom:15%;left:5%;animation:floatY 3.8s ease-in-out infinite 1.2s}
.fc-d{bottom:8%;right:3%;animation:floatY 4.5s ease-in-out infinite .3s}
.fc-e{top:45%;right:-2%;animation:floatY 4.2s ease-in-out infinite .9s}

/* ─── TRUST ─── */
.trust{padding:20px 6%;background:rgba(255,255,255,.025);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.trust-row{display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap}
.trust-lbl{font-size:.75rem;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.09em}
.trust-item{display:flex;align-items:center;gap:7px;color:rgba(255,255,255,.5);font-size:.82rem;font-weight:600}
.trust-item i{color:var(--teal)}

/* ─── SEGMENTS ─── */
.segments{padding:100px 6%}
.seg-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1100px;margin:56px auto 0}
.seg-card{border-radius:22px;padding:40px;position:relative;overflow:hidden;text-decoration:none;color:white;transition:all .4s;display:block}
.seg-card::before{content:'';position:absolute;inset:0;opacity:0;transition:opacity .4s}
.seg-card:hover{transform:translateY(-8px)}
.seg-card:hover::before{opacity:1}
.seg-ngo{background:linear-gradient(135deg,rgba(232,69,90,.18),rgba(245,166,35,.08));border:1px solid rgba(232,69,90,.25)}
.seg-ngo::before{background:linear-gradient(135deg,rgba(232,69,90,.08),transparent)}
.seg-mgr{background:linear-gradient(135deg,rgba(59,108,246,.18),rgba(0,212,170,.08));border:1px solid rgba(59,108,246,.25)}
.seg-mgr::before{background:linear-gradient(135deg,rgba(59,108,246,.08),transparent)}
.seg-ppl{background:linear-gradient(135deg,rgba(124,58,237,.18),rgba(91,130,255,.08));border:1px solid rgba(124,58,237,.25)}
.seg-ppl::before{background:linear-gradient(135deg,rgba(124,58,237,.08),transparent)}
.seg-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.35rem;margin-bottom:22px}
.si-rose{background:rgba(232,69,90,.2);color:#FF6B7A}
.si-blue{background:rgba(59,108,246,.2);color:var(--blue2)}
.si-purple{background:rgba(124,58,237,.2);color:#B27CFF}
.seg-card h3{font-size:1.15rem;font-weight:800;margin-bottom:10px}
.seg-card p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.65;margin-bottom:20px}
.seg-link{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;letter-spacing:.03em}
.sl-rose{color:#FF6B7A}.sl-blue{color:var(--blue2)}.sl-purple{color:#B27CFF}

/* ─── FEATURES ─── */
.features{padding:80px 6% 100px;background:rgba(255,255,255,.015)}
.feat-inner{max-width:1200px;margin:0 auto}
.feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:56px}
.feat-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:18px;padding:28px;transition:all .35s}
.feat-card:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);transform:translateY(-5px)}
.feat-ic{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:16px}
.fi-blue{background:rgba(59,108,246,.15);color:var(--blue2)}
.fi-teal{background:rgba(0,212,170,.15);color:var(--teal)}
.fi-rose{background:rgba(232,69,90,.15);color:#FF7080}
.fi-gold{background:rgba(245,166,35,.15);color:var(--gold)}
.fi-purple{background:rgba(124,58,237,.15);color:#B27CFF}
.fi-sky{background:rgba(56,189,248,.15);color:#50C8F5}
.fi-green{background:rgba(34,197,94,.15);color:#4ADE80}
.fi-violet{background:rgba(167,139,250,.15);color:#A78BFA}
.feat-card h4{font-size:.95rem;font-weight:700;margin-bottom:6px}
.feat-card p{font-size:.8rem;color:rgba(255,255,255,.45);line-height:1.6}

/* ─── IMPACT ─── */
.impact{padding:100px 6%;background:radial-gradient(ellipse 80% 60% at 50% 50%,rgba(59,108,246,.08) 0%,transparent 70%)}
.impact-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center}
.counter-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.counter-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:28px}
.counter-num{font-size:2.4rem;font-weight:900;letter-spacing:-.03em}
.cn-blue{background:linear-gradient(135deg,var(--blue2),var(--violet2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.cn-teal{color:var(--teal)}
.cn-rose{color:#FF7080}
.cn-gold{color:var(--gold)}
.counter-label{font-size:.78rem;color:rgba(255,255,255,.4);margin-top:4px;text-transform:uppercase;letter-spacing:.05em}

/* ─── PRICING ─── */
.pricing{padding:100px 6%;background:rgba(255,255,255,.015)}
.pricing-inner{max-width:1100px;margin:0 auto}
.billing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin:32px 0 56px}
.tgl-label{font-size:.9rem;font-weight:600;color:rgba(255,255,255,.45);transition:color .3s}
.tgl-label.on{color:white}
.switch{position:relative;display:inline-block;width:50px;height:26px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:rgba(255,255,255,.15);border-radius:26px;transition:.3s}
.slider:before{content:'';position:absolute;height:18px;width:18px;left:4px;bottom:4px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--blue)}
input:checked+.slider:before{transform:translateX(24px)}
.disc-badge{background:rgba(0,212,170,.15);color:var(--teal);font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:100px}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px}
.price-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:36px;position:relative;transition:all .4s}
.price-card.hot{background:linear-gradient(145deg,rgba(59,108,246,.18),rgba(124,58,237,.12));border-color:rgba(59,108,246,.4);transform:scale(1.03)}
.price-card:hover{border-color:rgba(255,255,255,.2);box-shadow:0 24px 60px rgba(0,0,0,.4)}
.price-card.hot:hover{transform:scale(1.03) translateY(-6px)}
.hot-label{position:absolute;top:18px;right:18px;background:linear-gradient(135deg,var(--blue),var(--violet));color:white;font-size:.68rem;font-weight:800;padding:4px 12px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em}
.price-name{font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.45);margin-bottom:14px}
.price-val{font-size:2.8rem;font-weight:900;line-height:1;letter-spacing:-.03em}
.price-val .cur{font-size:1.1rem;font-weight:700;vertical-align:top;margin-top:6px;display:inline-block;color:rgba(255,255,255,.55)}
.price-val .per{font-size:.95rem;font-weight:400;color:rgba(255,255,255,.35)}
.price-note{font-size:.75rem;color:rgba(255,255,255,.3);margin-top:4px;margin-bottom:22px}
.price-divider{height:1px;background:rgba(255,255,255,.07);margin:18px 0}
.price-feats{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:28px}
.price-feats li{display:flex;align-items:center;gap:9px;font-size:.84rem;color:rgba(255,255,255,.65)}
.price-feats li i{color:var(--teal);font-size:.8rem;flex-shrink:0}
.btn-price{display:block;text-align:center;padding:14px;border-radius:60px;font-weight:700;font-size:.9rem;text-decoration:none;transition:all .3s;border:none;cursor:pointer;width:100%}
.btp-main{background:linear-gradient(135deg,var(--blue),var(--violet));color:white;box-shadow:0 6px 24px rgba(59,108,246,.35)}
.btp-main:hover{box-shadow:0 10px 32px rgba(59,108,246,.55);transform:translateY(-2px)}
.btp-out{border:1.5px solid rgba(255,255,255,.18);color:rgba(255,255,255,.75);background:none}
.btp-out:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.35);color:white}

/* ─── CTA ─── */
.cta-section{padding:100px 6%;text-align:center;background:linear-gradient(135deg,rgba(59,108,246,.15),rgba(124,58,237,.1));border-top:1px solid var(--border)}
.cta-section h2{font-size:clamp(2rem,4vw,3rem);font-weight:900;letter-spacing:-.03em;margin-bottom:18px}
.cta-section p{font-size:1.05rem;color:rgba(255,255,255,.55);margin-bottom:40px}

/* ─── FOOTER ─── */
footer{background:rgba(255,255,255,.02);border-top:1px solid var(--border);padding:60px 6% 30px}
.footer-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
.footer-col h5{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.3);margin-bottom:14px}
.footer-col a{display:block;color:rgba(255,255,255,.5);text-decoration:none;font-size:.85rem;margin-bottom:8px;transition:color .3s}
.footer-col a:hover{color:white}
.footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding-top:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.footer-bottom span{font-size:.78rem;color:rgba(255,255,255,.25)}

/* ─── SECTION HEADER ─── */
.section-tag{display:inline-flex;align-items:center;gap:7px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:14px}
.st-blue{color:var(--blue2)}.st-teal{color:var(--teal)}.st-rose{color:#FF7080}
.section-title{font-size:clamp(2rem,4vw,2.8rem);font-weight:900;line-height:1.1;letter-spacing:-.025em;margin-bottom:16px}
.section-sub{font-size:1rem;color:rgba(255,255,255,.5);line-height:1.7;max-width:540px}
.center{text-align:center;margin-left:auto;margin-right:auto}

/* ─── ANIMATIONS ─── */
@keyframes fadeDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes mapPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:.6}}
@keyframes mapRipple{0%{transform:scale(1);opacity:.3}100%{transform:scale(3.5);opacity:0}}
@keyframes pulseBlue{0%,100%{opacity:1}50%{opacity:.4}}
.aos{opacity:0;transform:translateY(28px);transition:all .75s cubic-bezier(.22,1,.36,1)}
.aos.in{opacity:1;transform:translateY(0)}

/* ─── RESPONSIVE ─── */
@media(max-width:1024px){.feat-grid{grid-template-columns:repeat(2,1fr)}.impact-inner{grid-template-columns:1fr}}
@media(max-width:768px){
    .seg-grid{grid-template-columns:1fr}
    .feat-grid{grid-template-columns:1fr 1fr}
    .counter-grid{grid-template-columns:1fr 1fr}
    .footer-row{grid-template-columns:1fr 1fr}
    .fc-c,.fc-d,.fc-e{display:none}
    .price-card.hot{transform:none}
    .price-card.hot:hover{transform:translateY(-4px)}
}
@media(max-width:480px){
    .feat-grid{grid-template-columns:1fr}
    .footer-row{grid-template-columns:1fr}
    .hero-title{font-size:2.4rem}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
    <a href="{{ url('/') }}" class="nav-logo"><img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi"></a>
    <ul class="nav-links">
        <li><a href="{{ route('solutions.ngo') }}">Para ONGs</a></li>
        <li><a href="{{ route('solutions.manager') }}">Para Gestores</a></li>
        <li><a href="{{ route('solutions.common') }}">Uso Pessoal</a></li>
        <li><a href="#pricing">Preços</a></li>
        <li><a href="{{ url('/blog') }}" style="color:rgba(255,255,255,.45)">Blog</a></li>
    </ul>
    <div class="nav-ctas">
        <a href="{{ route('login') }}" class="btn-ghost">Entrar</a>
        <a href="{{ route('register') }}" class="btn-nav"><i class="fas fa-rocket"></i> Começar Grátis</a>
    </div>
    <button class="mobile-btn" onclick="document.getElementById('mobileMenu').classList.toggle('open')"><i class="fas fa-bars"></i></button>
</nav>
<div class="mobile-menu" id="mobileMenu">
    <a href="{{ route('solutions.ngo') }}">Para ONGs</a>
    <a href="{{ route('solutions.manager') }}">Para Gestores</a>
    <a href="{{ route('solutions.common') }}">Uso Pessoal</a>
    <a href="#pricing">Preços</a>
    <a href="{{ route('login') }}">Entrar</a>
    <a href="{{ route('register') }}" style="color:var(--blue2);font-weight:800">🚀 Começar Grátis</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge"><span class="hero-badge-dot"></span> Plataforma #1 de Impacto Social no Brasil</div>
    <h1 class="hero-title">
        <span class="g1">Gestão que</span><br>
        <span class="g2">Transforma Vidas</span><br>
        <span class="g1">em escala real.</span>
    </h1>
    <p class="hero-sub">Da ONG de bairro à rede nacional — o Vivensi conecta pessoas, dados e propósito em um único ecossistema digital.</p>
    <div class="hero-ctas">
        <a href="{{ route('register') }}" class="btn-hero"><i class="fas fa-rocket"></i> Comece Gratuitamente</a>
        <a href="#segments" class="btn-hero-outline"><i class="fas fa-th-large"></i> Ver Soluções</a>
    </div>

    <!-- MAP VISUALIZATION -->
    <div class="map-section">
        <div class="map-wrap">
            <svg id="brazil-svg" viewBox="0 0 820 740" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="bgGrad" cx="50%" cy="40%" r="60%">
                        <stop offset="0%" stop-color="#3B6CF6" stop-opacity=".12"/>
                        <stop offset="100%" stop-color="#080E1A" stop-opacity="0"/>
                    </radialGradient>
                    <linearGradient id="mapFill" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#3B6CF6" stop-opacity=".25"/>
                        <stop offset="50%" stop-color="#7C3AED" stop-opacity=".15"/>
                        <stop offset="100%" stop-color="#E8455A" stop-opacity=".1"/>
                    </linearGradient>
                    <filter id="glow"><feGaussianBlur stdDeviation="4" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                    <filter id="softglow"><feGaussianBlur stdDeviation="8" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                </defs>
                <rect width="820" height="740" fill="url(#bgGrad)"/>
                <!-- Grid lines -->
                <g stroke="rgba(255,255,255,.04)" stroke-width="1">
                    <line x1="0" y1="185" x2="820" y2="185"/><line x1="0" y1="370" x2="820" y2="370"/>
                    <line x1="0" y1="555" x2="820" y2="555"/><line x1="205" y1="0" x2="205" y2="740"/>
                    <line x1="410" y1="0" x2="410" y2="740"/><line x1="615" y1="0" x2="615" y2="740"/>
                </g>
                <!-- Brazil mainland path (simplified) -->
                <path d="M310 55 L360 48 L410 58 L455 72 L490 88 L520 110 L540 138 L548 165 L542 188 L556 208 L575 232 L590 260 L598 292 L594 322 L580 348 L560 368 L538 382 L513 394 L488 408 L465 428 L444 452 L428 476 L415 502 L403 528 L392 550 L380 568 L365 582 L348 595 L330 604 L312 608 L294 606 L277 598 L260 584 L244 565 L230 542 L218 516 L207 486 L198 454 L192 420 L190 386 L192 353 L198 320 L207 290 L218 262 L232 238 L248 216 L266 196 L284 180 L300 164 L312 148 L318 130 L316 112 L308 96 Z"
                      fill="url(#mapFill)" stroke="rgba(59,108,246,.5)" stroke-width="1.5" filter="url(#glow)"/>
                <!-- Northern state separation lines -->
                <path d="M310 140 L390 135 L430 142 L440 158 L410 165 L380 160 L350 155 Z" fill="rgba(59,108,246,.08)" stroke="rgba(59,108,246,.2)" stroke-width="1"/>
                <!-- Northeast region tint -->
                <path d="M500 180 L540 195 L568 220 L578 250 L562 268 L538 262 L516 245 L498 225 L490 205 Z" fill="rgba(232,69,90,.07)" stroke="rgba(232,69,90,.2)" stroke-width="1"/>
                <!-- South region tint -->
                <path d="M260 520 L290 514 L320 518 L340 530 L330 548 L310 558 L288 552 L268 540 Z" fill="rgba(0,212,170,.07)" stroke="rgba(0,212,170,.2)" stroke-width="1"/>
                <!-- Connection lines between major dots -->
                <g stroke="rgba(59,108,246,.25)" stroke-width="1" stroke-dasharray="4 4">
                    <line x1="390" y1="180" x2="530" y2="240"/>
                    <line x1="390" y1="180" x2="310" y2="490"/>
                    <line x1="530" y1="240" x2="430" y2="390"/>
                    <line x1="310" y1="490" x2="430" y2="390"/>
                    <line x1="430" y1="390" x2="500" y2="320"/>
                </g>
                <!-- State dots: São Paulo -->
                <circle cx="390" cy="480" r="16" fill="rgba(59,108,246,.2)" filter="url(#softglow)"/>
                <circle cx="390" cy="480" r="8" fill="var(--blue2)" filter="url(#glow)">
                    <animate attributeName="r" values="7;10;7" dur="3s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="1;.6;1" dur="3s" repeatCount="indefinite"/>
                </circle>
                <!-- Rio de Janeiro -->
                <circle cx="430" cy="450" r="12" fill="rgba(59,108,246,.15)" filter="url(#softglow)"/>
                <circle cx="430" cy="450" r="6" fill="var(--blue2)" filter="url(#glow)">
                    <animate attributeName="r" values="5;8;5" dur="2.8s" repeatCount="indefinite" begin=".5s"/>
                </circle>
                <!-- Brasília -->
                <circle cx="420" cy="340" r="14" fill="rgba(245,166,35,.2)" filter="url(#softglow)"/>
                <circle cx="420" cy="340" r="7" fill="var(--gold)" filter="url(#glow)">
                    <animate attributeName="r" values="6;9;6" dur="3.2s" repeatCount="indefinite" begin="1s"/>
                </circle>
                <!-- Fortaleza -->
                <circle cx="530" cy="210" r="13" fill="rgba(232,69,90,.2)" filter="url(#softglow)"/>
                <circle cx="530" cy="210" r="6" fill="#FF7080" filter="url(#glow)">
                    <animate attributeName="r" values="5;8;5" dur="2.6s" repeatCount="indefinite" begin=".3s"/>
                </circle>
                <!-- Manaus -->
                <circle cx="310" cy="200" r="12" fill="rgba(0,212,170,.15)" filter="url(#softglow)"/>
                <circle cx="310" cy="200" r="6" fill="var(--teal)" filter="url(#glow)">
                    <animate attributeName="r" values="5;8;5" dur="3.5s" repeatCount="indefinite" begin="1.5s"/>
                </circle>
                <!-- Porto Alegre -->
                <circle cx="370" cy="570" r="11" fill="rgba(124,58,237,.2)" filter="url(#softglow)"/>
                <circle cx="370" cy="570" r="5" fill="#B27CFF" filter="url(#glow)">
                    <animate attributeName="r" values="4;7;4" dur="3s" repeatCount="indefinite" begin=".8s"/>
                </circle>
                <!-- Belém -->
                <circle cx="460" cy="165" r="10" fill="rgba(59,108,246,.15)" filter="url(#softglow)"/>
                <circle cx="460" cy="165" r="5" fill="var(--blue2)" filter="url(#glow)">
                    <animate attributeName="r" values="4;7;4" dur="2.9s" repeatCount="indefinite" begin=".2s"/>
                </circle>
                <!-- Salvador -->
                <circle cx="500" cy="340" r="11" fill="rgba(245,166,35,.15)" filter="url(#softglow)"/>
                <circle cx="500" cy="340" r="5" fill="var(--gold)" filter="url(#glow)">
                    <animate attributeName="r" values="4;7;4" dur="3.1s" repeatCount="indefinite" begin="1.2s"/>
                </circle>
                <!-- Particle ambiance -->
                <g opacity=".5">
                    <circle cx="350" cy="110" r="1.5" fill="#5B82FF"><animate attributeName="opacity" values="0;1;0" dur="4s" repeatCount="indefinite"/></circle>
                    <circle cx="570" cy="290" r="1.5" fill="#B27CFF"><animate attributeName="opacity" values="0;1;0" dur="5s" repeatCount="indefinite" begin="1s"/></circle>
                    <circle cx="250" cy="380" r="1.5" fill="#00D4AA"><animate attributeName="opacity" values="0;1;0" dur="3.5s" repeatCount="indefinite" begin="2s"/></circle>
                    <circle cx="480" cy="500" r="1.5" fill="#FF7080"><animate attributeName="opacity" values="0;1;0" dur="4.5s" repeatCount="indefinite" begin=".5s"/></circle>
                </g>
            </svg>

            <!-- Floating cards -->
            <div class="fcard fc-a">
                <div class="fcard-label">ONGs ativas</div>
                <div class="fcard-value">2.847</div>
                <div class="fcard-sub"><i class="fas fa-arrow-up"></i> +124 este mês</div>
            </div>
            <div class="fcard fc-b">
                <div class="fcard-label">Doações captadas</div>
                <div class="fcard-value">R$ 12,4M</div>
                <div class="fcard-sub" style="color:var(--gold)"><i class="fas fa-chart-line"></i> +31% vs 2025</div>
            </div>
            <div class="fcard fc-c">
                <div class="fcard-label">Beneficiários</div>
                <div class="fcard-value">98.200</div>
                <div class="fcard-sub"><i class="fas fa-heart"></i> em 27 estados</div>
            </div>
            <div class="fcard fc-d">
                <div class="fcard-label">Voluntários gamificados</div>
                <div class="fcard-value">34.500</div>
                <div class="fcard-sub" style="color:#B27CFF"><i class="fas fa-trophy"></i> 1.240 Diamante</div>
            </div>
            <div class="fcard fc-e">
                <div class="fcard-label">Relatórios gerados</div>
                <div class="fcard-value">18.930</div>
                <div class="fcard-sub" style="color:rgba(255,255,255,.4)">Automáticos por IA</div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
    <div class="trust-row">
        <span class="trust-lbl">Infraestrutura</span>
        <div class="trust-item"><i class="fas fa-shield-halved"></i> LGPD Compliant</div>
        <div class="trust-item"><i class="fas fa-server"></i> AWS Brasil (SA-East)</div>
        <div class="trust-item"><i class="fas fa-lock"></i> SSL 256-bit + 2FA</div>
        <div class="trust-item"><i class="fas fa-star"></i> 4.9/5 ★★★★★</div>
        <div class="trust-item"><i class="fas fa-clock"></i> 99.9% Uptime SLA</div>
    </div>
</div>

<!-- SEGMENTS -->
<section class="segments" id="segments">
    <div class="center aos">
        <div class="section-tag st-blue"><i class="fas fa-th-large"></i> Soluções</div>
        <h2 class="section-title">Uma plataforma,<br>três ecossistemas</h2>
        <p class="section-sub center">Cada vertical é especializada para as necessidades do seu setor, mas todas compartilham a mesma base sólida.</p>
    </div>
    <div class="seg-grid">
        <a href="{{ route('solutions.ngo') }}" class="seg-card seg-ngo aos">
            <div class="seg-icon si-rose"><i class="fas fa-hand-holding-heart"></i></div>
            <h3>Para ONGs & Terceiro Setor</h3>
            <p>Gestão de doadores, prestação de contas, editais, almoxarifado, voluntários gamificados e portal de transparência.</p>
            <div class="seg-link sl-rose">Explorar solução <i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="{{ route('solutions.manager') }}" class="seg-card seg-mgr aos">
            <div class="seg-icon si-blue"><i class="fas fa-chart-bar"></i></div>
            <h3>Para Gestores & Projetos</h3>
            <p>Kanban de projetos, agenda de reuniões, controle financeiro, CRM de clientes e relatórios executivos em tempo real.</p>
            <div class="seg-link sl-blue">Explorar solução <i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="{{ route('solutions.common') }}" class="seg-card seg-ppl aos">
            <div class="seg-icon si-purple"><i class="fas fa-user-circle"></i></div>
            <h3>Uso Pessoal & Profissional</h3>
            <p>Finanças pessoais, contratos, whatsapp integrado, landing pages e muito mais para profissionais liberais.</p>
            <div class="seg-link sl-purple">Explorar solução <i class="fas fa-arrow-right"></i></div>
        </a>
    </div>
</section>

<!-- FEATURES -->
<section class="features aos">
    <div class="feat-inner">
        <div class="center">
            <div class="section-tag st-teal"><i class="fas fa-sparkles"></i> Funcionalidades</div>
            <h2 class="section-title">Tudo incluso, sem surpresas</h2>
            <p class="section-sub center">Cada recurso foi pensado pela equipe Vivensi com base no feedback de gestores reais do terceiro setor.</p>
        </div>
        <div class="feat-grid">
            <div class="feat-card"><div class="feat-ic fi-rose"><i class="fas fa-heart"></i></div><h4>Portal do Doador VIP</h4><p>Link mágico exclusivo por doador com histórico e informe de rendimentos.</p></div>
            <div class="feat-card"><div class="feat-ic fi-blue"><i class="fas fa-file-invoice"></i></div><h4>Prestação de Contas</h4><p>DRE, Balancetes e relatórios em 1 clique, prontos para auditoria.</p></div>
            <div class="feat-card"><div class="feat-ic fi-teal"><i class="fas fa-box-open"></i></div><h4>Almoxarifado Digital</h4><p>Controle de estoque físico de doações com movimentações e histórico.</p></div>
            <div class="feat-card"><div class="feat-ic fi-gold"><i class="fas fa-trophy"></i></div><h4>Voluntários Gamificados</h4><p>Rankings, pontos por horas e crachás para motivar seu time social.</p></div>
            <div class="feat-card"><div class="feat-ic fi-purple"><i class="fas fa-brain"></i></div><h4>IA para Editais</h4><p>Geração automática de projetos via IA com análise de viabilidade.</p></div>
            <div class="feat-card"><div class="feat-ic fi-sky"><i class="fas fa-kanban"></i></div><h4>CRM Kanban</h4><p>Funil visual de patrocínios e deals com drag & drop profissional.</p></div>
            <div class="feat-card"><div class="feat-ic fi-green"><i class="fab fa-whatsapp"></i></div><h4>WhatsApp Integrado</h4><p>Campanhas, atendimento e bot com IA via Evolution API.</p></div>
            <div class="feat-card"><div class="feat-ic fi-violet"><i class="fas fa-globe"></i></div><h4>Portal de Transparência</h4><p>Página pública automática mostrando impacto em tempo real.</p></div>
        </div>
    </div>
</section>

<!-- IMPACT COUNTERS -->
<section class="impact">
    <div class="impact-inner">
        <div class="aos">
            <div class="section-tag st-rose"><i class="fas fa-chart-area"></i> Impacto Real</div>
            <h2 class="section-title">Números que<br>provam o propósito</h2>
            <p class="section-sub">O Vivensi não é só software. É o motor de gestão que permite que organizações focadas em missão operem com excelência.</p>
        </div>
        <div class="counter-grid aos">
            <div class="counter-card"><div class="counter-num cn-blue">2.847</div><div class="counter-label">ONGs cadastradas</div></div>
            <div class="counter-card"><div class="counter-num cn-teal">98k+</div><div class="counter-label">Beneficiários impactados</div></div>
            <div class="counter-card"><div class="counter-num cn-rose">R$12M</div><div class="counter-label">Doações rastreadas</div></div>
            <div class="counter-card"><div class="counter-num cn-gold">34.5k</div><div class="counter-label">Voluntários ativos</div></div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing" id="pricing">
    <div class="pricing-inner">
        <div class="center aos">
            <div class="section-tag st-blue"><i class="fas fa-tag"></i> Planos</div>
            <h2 class="section-title">Investimento no impacto</h2>
            <p class="section-sub center">Sem fidelização. Cancele quando quiser. Suporte incluso em todos os planos.</p>
            <div class="billing-toggle">
                <span class="tgl-label on" id="lbl-m">Mensal</span>
                <label class="switch"><input type="checkbox" id="billing-toggle" onchange="toggleBilling()"><span class="slider"></span></label>
                <span class="tgl-label" id="lbl-y">Anual <span class="disc-badge">-10% OFF</span></span>
            </div>
        </div>
        <div class="price-grid">
            @forelse($plans as $plan)
            <div class="price-card {{ $loop->index === 1 ? 'hot' : '' }}">
                @if($loop->index === 1)<div class="hot-label">Mais Popular</div>@endif
                <div class="price-name">{{ $plan->name }}</div>
                <div class="price-val">
                    <span class="cur">R$</span>
                    <span class="amount" data-m="{{ $plan->price }}" data-y="{{ $plan->price_yearly ?? ($plan->price * 12 * 0.9) }}">{{ number_format($plan->price, 2, ',', '.') }}</span>
                    <span class="per">/mês</span>
                </div>
                <div class="price-note" id="pnote-{{ $loop->index }}">cobrado mensalmente</div>
                <div class="price-divider"></div>
                <ul class="price-feats">
                    @if($plan->features)
                        @foreach($plan->features as $f)
                        <li><i class="fas fa-circle-check"></i> {{ $f }}</li>
                        @endforeach
                    @endif
                </ul>
                <a href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']) }}"
                   class="btn-price {{ $loop->index === 1 ? 'btp-main' : 'btp-out' }} btn-subscribe"
                   data-plan-id="{{ $plan->id }}">
                   {{ $loop->index === 1 ? '🚀 Assinar Agora' : 'Escolher Plano' }}
                </a>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:rgba(255,255,255,.4)">
                <i class="fas fa-package-open" style="font-size:2rem;display:block;margin-bottom:16px"></i>
                Planos sob consulta — <a href="{{ route('login') }}" style="color:var(--blue2)">fale conosco</a>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="cta-section aos">
    <div class="section-tag st-blue center" style="justify-content:center"><i class="fas fa-rocket"></i> Comece hoje</div>
    <h2>Pronto para escalar seu impacto?</h2>
    <p>Junte-se a mais de 2.800 organizações que já confiam no Vivensi.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('register') }}" class="btn-hero" style="font-size:1.05rem;padding:18px 44px"><i class="fas fa-rocket"></i> Criar Conta Grátis</a>
        <a href="{{ route('login') }}" class="btn-hero-outline" style="font-size:1.05rem;padding:18px 36px"><i class="fas fa-sign-in-alt"></i> Já tenho conta</a>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-row">
        <div class="footer-col">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi" style="height:30px;margin-bottom:14px;filter:brightness(0) invert(1);opacity:.6">
            <p style="font-size:.82rem;color:rgba(255,255,255,.3);line-height:1.7">Tecnologia para quem<br>transforma o Brasil.</p>
        </div>
        <div class="footer-col">
            <h5>Soluções</h5>
            <a href="{{ route('solutions.ngo') }}">Para ONGs</a>
            <a href="{{ route('solutions.manager') }}">Para Gestores</a>
            <a href="{{ route('solutions.common') }}">Uso Pessoal</a>
        </div>
        <div class="footer-col">
            <h5>Produto</h5>
            <a href="#pricing">Planos</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Criar conta</a>
        </div>
        <div class="footer-col">
            <h5>Legal</h5>
            <a href="{{ route('public.page', 'privacidade') }}">Privacidade</a>
            <a href="{{ route('public.page', 'termos') }}">Termos de Uso</a>
            <a href="{{ route('public.page', 'sobre') }}">Sobre</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 Vivensi. Todos os direitos reservados.</span>
        <span>Feito com <span style="color:var(--rose)">♥</span> para o terceiro setor brasileiro</span>
    </div>
</footer>

<script>
// Billing toggle
function toggleBilling(){
    const yearly=document.getElementById('billing-toggle').checked;
    document.getElementById('lbl-m').classList.toggle('on',!yearly);
    document.getElementById('lbl-y').classList.toggle('on',yearly);
    document.querySelectorAll('.amount').forEach(el=>{
        const m=parseFloat(el.dataset.m),y=parseFloat(el.dataset.y);
        el.textContent=new Intl.NumberFormat('pt-BR',{minimumFractionDigits:2}).format(yearly?y/12:m);
    });
    document.querySelectorAll('[id^="pnote-"]').forEach(el=>{
        el.textContent=yearly?'cobrado anualmente (economize 10%)':'cobrado mensalmente';
    });
    document.querySelectorAll('.btn-subscribe').forEach(btn=>{
        btn.href=`/register?plan_id=${btn.dataset.planId}&billing_cycle=${yearly?'yearly':'monthly'}`;
    });
}

// Scroll animations
const observer=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('in')});
},{threshold:0.12});
document.querySelectorAll('.aos').forEach(el=>observer.observe(el));

// Navbar scroll
window.addEventListener('scroll',()=>{
    const n=document.getElementById('mainNav');
    n.style.background=window.scrollY>60?'rgba(8,14,26,.98)':'rgba(8,14,26,.9)';
});

// Mobile menu close on outside click
document.addEventListener('click',e=>{
    const menu=document.getElementById('mobileMenu');
    if(menu.classList.contains('open')&&!e.target.closest('#mobileMenu')&&!e.target.closest('.mobile-btn'))
        menu.classList.remove('open');
});
</script>
</body>
</html>
