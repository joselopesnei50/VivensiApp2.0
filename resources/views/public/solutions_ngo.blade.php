<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi para ONGs | Gestão do Terceiro Setor</title>
    <meta name="description" content="A plataforma mais completa para ONGs brasileiras. Gestão de doadores, prestação de contas, voluntários, editais e muito mais.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --ink:#0B1120;--ink2:#1E2D40;
    --rose:#E8455A;--rose-light:#FF6B7A;
    --blue:#3B6CF6;--blue-light:#5B82FF;
    --teal:#00D4AA;
    --gold:#F5A623;
    --surface:#F0F4FF;
    --white:#FFFFFF;
    --glass:rgba(255,255,255,0.06);
    --glass-border:rgba(255,255,255,0.12);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--ink);color:var(--white);overflow-x:hidden;line-height:1.6}

/* ─── NAVBAR ─── */
.nav{position:fixed;top:0;left:0;right:0;z-index:999;padding:18px 6%;display:flex;justify-content:space-between;align-items:center;background:rgba(11,17,32,0.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--glass-border);transition:all .3s}
.nav-logo img{height:36px}
.nav-links{display:flex;gap:32px;list-style:none}
.nav-links a{color:rgba(255,255,255,0.65);text-decoration:none;font-size:.9rem;font-weight:500;transition:color .3s}
.nav-links a:hover,.nav-links a.active{color:var(--white)}
.nav-actions{display:flex;gap:12px;align-items:center}
.btn-ghost{color:rgba(255,255,255,0.7);text-decoration:none;font-weight:600;font-size:.9rem;padding:9px 20px;border-radius:50px;border:1px solid var(--glass-border);transition:all .3s}
.btn-ghost:hover{background:var(--glass);color:var(--white)}
.btn-primary{background:linear-gradient(135deg,var(--rose),#C0392B);color:white;text-decoration:none;font-weight:700;font-size:.9rem;padding:10px 24px;border-radius:50px;box-shadow:0 4px 20px rgba(232,69,90,.35);transition:all .3s}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(232,69,90,.5)}

/* ─── HERO ─── */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;background:radial-gradient(ellipse 80% 70% at 60% 40%, rgba(59,108,246,.15) 0%, transparent 60%), radial-gradient(ellipse 50% 60% at 20% 80%, rgba(232,69,90,.1) 0%, transparent 50%), var(--ink)}
.hero-content{position:relative;z-index:2;padding:120px 6% 80px;max-width:1400px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(232,69,90,.12);border:1px solid rgba(232,69,90,.3);color:var(--rose-light);font-size:.8rem;font-weight:700;padding:6px 16px;border-radius:100px;margin-bottom:28px;letter-spacing:.05em;text-transform:uppercase}
.hero-badge span{width:6px;height:6px;background:var(--rose-light);border-radius:50%;animation:pulseRed 2s infinite}
.hero-title{font-size:clamp(2.5rem,5vw,4rem);font-weight:900;line-height:1.05;letter-spacing:-.03em;margin-bottom:24px}
.hero-title .accent{background:linear-gradient(135deg,var(--rose-light),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:1.15rem;color:rgba(255,255,255,0.6);line-height:1.7;margin-bottom:40px;max-width:520px}
.hero-actions{display:flex;gap:16px;flex-wrap:wrap}
.btn-hero-main{background:linear-gradient(135deg,var(--rose),#C0392B);color:white;text-decoration:none;font-weight:700;font-size:1rem;padding:16px 36px;border-radius:60px;box-shadow:0 6px 30px rgba(232,69,90,.4);transition:all .3s;display:inline-flex;align-items:center;gap:10px}
.btn-hero-main:hover{transform:translateY(-3px);box-shadow:0 12px 40px rgba(232,69,90,.55)}
.btn-hero-ghost{color:rgba(255,255,255,.8);text-decoration:none;font-weight:600;font-size:1rem;padding:16px 30px;border-radius:60px;border:1.5px solid rgba(255,255,255,.2);transition:all .3s;display:inline-flex;align-items:center;gap:10px}
.btn-hero-ghost:hover{background:rgba(255,255,255,.07);color:white}
.hero-stats{display:flex;gap:36px;margin-top:48px;padding-top:40px;border-top:1px solid rgba(255,255,255,.08)}
.stat-item{text-align:left}
.stat-num{font-size:1.9rem;font-weight:800;background:linear-gradient(135deg,var(--white),rgba(255,255,255,.7));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.stat-label{font-size:.78rem;color:rgba(255,255,255,.45);letter-spacing:.05em;text-transform:uppercase;margin-top:2px}

/* ─── MAP VISUAL ─── */
.hero-map-wrap{position:relative;display:flex;align-items:center;justify-content:center}
.map-container{position:relative;width:480px;height:540px}
#brazil-map{width:100%;height:100%;opacity:.9}
.map-dot{position:absolute;width:10px;height:10px;border-radius:50%;background:var(--rose-light);box-shadow:0 0 12px var(--rose);animation:mapPulse 2.5s infinite}
.map-dot::before{content:'';position:absolute;inset:-6px;border-radius:50%;background:var(--rose-light);opacity:.2;animation:mapRipple 2.5s infinite}
.map-dot.blue{background:var(--blue-light);box-shadow:0 0 12px var(--blue);}.map-dot.blue::before{background:var(--blue-light)}
.map-dot.teal{background:var(--teal);box-shadow:0 0 12px var(--teal);}.map-dot.teal::before{background:var(--teal)}
.d1{top:25%;left:35%;animation-delay:0s}.d2{top:30%;left:65%;animation-delay:.4s}.d3{top:45%;left:48%;animation-delay:.8s}
.d4{top:65%;left:55%;animation-delay:1.2s}.d5{top:50%;left:70%;animation-delay:1.6s}.d6{top:80%;left:45%;animation-delay:.6s}
.d7{top:40%;left:30%;animation-delay:1s}.d8{top:20%;left:50%;animation-delay:.2s}.d9{top:70%;left:65%;animation-delay:1.4s}
.floating-card{position:absolute;background:rgba(16,24,48,.85);backdrop-filter:blur(16px);border:1px solid var(--glass-border);border-radius:16px;padding:14px 18px;min-width:170px}
.fc-1{top:10%;right:-10%;animation:floatY 4s ease-in-out infinite}
.fc-2{bottom:15%;left:-15%;animation:floatY 5s ease-in-out infinite .8s}
.fc-3{top:50%;right:-12%;animation:floatY 3.5s ease-in-out infinite 1.4s}
.fc-label{font-size:.7rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.fc-value{font-size:1.2rem;font-weight:800;color:white}
.fc-delta{font-size:.75rem;color:var(--teal);margin-top:2px}

/* ─── TRUST BAR ─── */
.trust-bar{padding:24px 6%;background:rgba(255,255,255,.03);border-top:1px solid var(--glass-border);border-bottom:1px solid var(--glass-border)}
.trust-inner{display:flex;align-items:center;justify-content:center;gap:48px;flex-wrap:wrap}
.trust-text{font-size:.8rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.08em;white-space:nowrap}
.trust-item{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.5);font-size:.85rem;font-weight:600}
.trust-item i{color:var(--teal)}

/* ─── SECTION COMMONS ─── */
section{padding:100px 6%}
.section-tag{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:16px}
.tag-rose{color:var(--rose-light)}
.tag-blue{color:var(--blue-light)}
.tag-teal{color:var(--teal)}
.section-title{font-size:clamp(2rem,4vw,3rem);font-weight:900;line-height:1.1;letter-spacing:-.02em;margin-bottom:20px}
.section-sub{font-size:1.05rem;color:rgba(255,255,255,.55);line-height:1.7;max-width:580px}
.center{text-align:center;margin-left:auto;margin-right:auto}

/* ─── PROBLEM → SOLUTION ─── */
.problem-section{background:radial-gradient(ellipse 60% 80% at 20% 50%, rgba(232,69,90,.07) 0%, transparent 60%)}
.problem-grid{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1200px;margin:0 auto}
.pain-list{display:flex;flex-direction:column;gap:20px;margin-top:40px}
.pain-item{display:flex;align-items:flex-start;gap:16px;padding:22px;background:rgba(232,69,90,.06);border:1px solid rgba(232,69,90,.15);border-radius:16px}
.pain-icon{width:44px;height:44px;background:rgba(232,69,90,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--rose-light);font-size:1.1rem;flex-shrink:0}
.pain-text h4{font-size:.95rem;font-weight:700;margin-bottom:4px}
.pain-text p{font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.5}
.solution-visual{position:relative}
.dashboard-mock{background:rgba(30,45,64,.7);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:28px;backdrop-filter:blur(10px)}
.mock-header{display:flex;align-items:center;gap:10px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,.07)}
.mock-dot{width:10px;height:10px;border-radius:50%}
.mock-title{font-size:.9rem;font-weight:700;color:rgba(255,255,255,.7)}
.mock-kpi-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.mock-kpi{background:rgba(255,255,255,.05);border-radius:12px;padding:14px}
.mock-kpi-label{font-size:.7rem;color:rgba(255,255,255,.4);margin-bottom:4px}
.mock-kpi-value{font-size:1.4rem;font-weight:800}
.mock-bar-wrap{margin-top:14px}
.mock-bar-label{display:flex;justify-content:space-between;font-size:.72rem;color:rgba(255,255,255,.4);margin-bottom:6px}
.mock-bar{height:6px;background:rgba(255,255,255,.08);border-radius:10px;overflow:hidden;margin-bottom:10px}
.mock-bar-fill{height:100%;border-radius:10px;animation:barGrow 1.5s ease forwards}

/* ─── FEATURES ─── */
.features-section{background:linear-gradient(180deg, rgba(59,108,246,.05) 0%, transparent 100%)}
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1200px;margin:60px auto 0}
.feat-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:36px;transition:all .4s;position:relative;overflow:hidden;cursor:default}
.feat-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(59,108,246,.08),rgba(0,212,170,.05));opacity:0;transition:opacity .4s}
.feat-card:hover{border-color:rgba(255,255,255,.2);transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.4)}
.feat-card:hover::before{opacity:1}
.feat-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:22px}
.fi-rose{background:rgba(232,69,90,.15);color:var(--rose-light)}
.fi-blue{background:rgba(59,108,246,.15);color:var(--blue-light)}
.fi-teal{background:rgba(0,212,170,.15);color:var(--teal)}
.fi-gold{background:rgba(245,166,35,.15);color:var(--gold)}
.fi-purple{background:rgba(155,93,229,.15);color:#B27CFF}
.fi-sky{background:rgba(56,189,248,.15);color:#56C8F5}
.feat-card h3{font-size:1.05rem;font-weight:700;margin-bottom:10px}
.feat-card p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.7}
.feat-tag{display:inline-block;margin-top:14px;font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.ft-new{background:rgba(0,212,170,.15);color:var(--teal)}
.ft-pro{background:rgba(245,166,35,.15);color:var(--gold)}

/* ─── TIMELINE ─── */
.timeline-section{background:rgba(255,255,255,.02)}
.timeline{display:flex;flex-direction:column;gap:0;max-width:800px;margin:60px auto 0;position:relative}
.timeline::before{content:'';position:absolute;left:27px;top:0;bottom:0;width:2px;background:linear-gradient(180deg,var(--rose),var(--blue),var(--teal));opacity:.3}
.tl-item{display:flex;gap:28px;padding:28px 0}
.tl-icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;position:relative;z-index:1}
.tl-text h4{font-size:1rem;font-weight:700;margin-bottom:6px}
.tl-text p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.6}

/* ─── PRICING ─── */
.pricing-section{background:radial-gradient(ellipse 70% 60% at 50% 20%, rgba(59,108,246,.1) 0%, transparent 70%)}
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;max-width:1100px;margin:60px auto 0}
.price-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:24px;padding:40px;position:relative;transition:all .4s;overflow:hidden}
.price-card.featured{background:linear-gradient(135deg,rgba(232,69,90,.15),rgba(59,108,246,.1));border-color:rgba(232,69,90,.4);transform:scale(1.03)}
.price-card:hover{border-color:rgba(255,255,255,.25);transform:translateY(-8px);box-shadow:0 30px 60px rgba(0,0,0,.4)}
.price-card.featured:hover{transform:scale(1.03) translateY(-8px)}
.featured-badge{position:absolute;top:20px;right:20px;background:linear-gradient(135deg,var(--rose),#C0392B);color:white;font-size:.7rem;font-weight:800;padding:4px 12px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em}
.price-name{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.5);margin-bottom:16px}
.price-amount{font-size:3rem;font-weight:900;line-height:1;letter-spacing:-.03em}
.price-amount .cur{font-size:1.2rem;font-weight:700;vertical-align:top;margin-top:8px;display:inline-block;color:rgba(255,255,255,.6)}
.price-amount .period{font-size:1rem;font-weight:400;color:rgba(255,255,255,.4)}
.price-note{font-size:.78rem;color:rgba(255,255,255,.35);margin-top:4px}
.price-divider{height:1px;background:rgba(255,255,255,.08);margin:24px 0}
.price-features{list-style:none;display:flex;flex-direction:column;gap:12px;margin-bottom:32px}
.price-features li{display:flex;align-items:center;gap:10px;font-size:.875rem;color:rgba(255,255,255,.7)}
.price-features li i{color:var(--teal);font-size:.85rem;flex-shrink:0}
.btn-plan{display:block;text-align:center;padding:15px;border-radius:60px;font-weight:700;font-size:.95rem;text-decoration:none;transition:all .3s}
.btn-plan-main{background:linear-gradient(135deg,var(--rose),#C0392B);color:white;box-shadow:0 6px 24px rgba(232,69,90,.35)}
.btn-plan-main:hover{box-shadow:0 10px 35px rgba(232,69,90,.55);transform:translateY(-2px)}
.btn-plan-outline{border:1.5px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8)}
.btn-plan-outline:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.4);color:white}
.billing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:32px}
.toggle-label{font-size:.9rem;font-weight:600;color:rgba(255,255,255,.5)}
.toggle-label.active{color:white}
.switch{position:relative;display:inline-block;width:52px;height:28px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.15);border-radius:28px;transition:.3s}
.slider:before{content:'';position:absolute;height:20px;width:20px;left:4px;bottom:4px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--rose)}
input:checked+.slider:before{transform:translateX(24px)}
.discount-badge{background:rgba(0,212,170,.15);color:var(--teal);font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:100px}

/* ─── FINAL CTA ─── */
.final-cta{position:relative;overflow:hidden;text-align:center;background:linear-gradient(135deg,rgba(232,69,90,.2),rgba(59,108,246,.15));border-top:1px solid rgba(255,255,255,.07)}
.final-cta h2{font-size:clamp(2rem,4vw,3.2rem);font-weight:900;letter-spacing:-.02em;margin-bottom:20px}
.final-cta p{font-size:1.1rem;color:rgba(255,255,255,.6);margin-bottom:40px}

/* ─── FOOTER ─── */
footer{background:rgba(255,255,255,.02);border-top:1px solid var(--glass-border);padding:60px 6% 32px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
.footer-col h4{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.4);margin-bottom:16px}
.footer-col a{display:block;color:rgba(255,255,255,.55);text-decoration:none;font-size:.875rem;margin-bottom:10px;transition:color .3s}
.footer-col a:hover{color:white}
.footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding-top:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.footer-bottom p{font-size:.8rem;color:rgba(255,255,255,.3)}

/* ─── ANIMATIONS ─── */
@keyframes pulseRed{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
@keyframes mapPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:.7}}
@keyframes mapRipple{0%{transform:scale(1);opacity:.2}100%{transform:scale(3.5);opacity:0}}
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
@keyframes barGrow{from{width:0}to{width:var(--target-width)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.animate-on-scroll{opacity:0;transform:translateY(30px);transition:all .7s cubic-bezier(.22,1,.36,1)}
.animate-on-scroll.visible{opacity:1;transform:translateY(0)}

/* ─── RESPONSIVE ─── */
@media(max-width:900px){
    .hero-content{grid-template-columns:1fr;text-align:center;gap:40px}
    .hero-sub{margin:0 auto 40px;text-align:center}
    .hero-actions{justify-content:center}
    .hero-stats{justify-content:center}
    .hero-map-wrap{display:none}
    .problem-grid{grid-template-columns:1fr}
    .features-grid{grid-template-columns:1fr 1fr}
    .footer-grid{grid-template-columns:1fr 1fr}
    .nav-links{display:none}
    .pricing-grid{grid-template-columns:1fr}
    .price-card.featured{transform:none}
}
@media(max-width:600px){
    .features-grid{grid-template-columns:1fr}
    .footer-grid{grid-template-columns:1fr}
    section{padding:70px 5%}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
    <a href="{{ url('/') }}" class="nav-logo"><img src="{{ asset('img/novalogo.png') }}" alt="Vivensi"></a>
    <ul class="nav-links">
        <li><a href="{{ route('solutions.ngo') }}" class="active">Terceiro Setor</a></li>
        <li><a href="{{ route('solutions.manager') }}">Gestores</a></li>
        <li><a href="{{ route('solutions.common') }}">Pessoal</a></li>
        <li><a href="#features">Recursos</a></li>
        <li><a href="#pricing">Planos</a></li>
    </ul>
    <div class="nav-actions">
        <a href="{{ route('login') }}" class="btn-ghost">Entrar</a>
        <a href="#pricing" class="btn-primary"><i class="fas fa-rocket"></i> Começar Agora</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-left">
            <div class="hero-badge"><span></span> Plataforma #1 para ONGs Brasileiras</div>
            <h1 class="hero-title">Gestão que liberta<br>sua <span class="accent">missão social</span></h1>
            <p class="hero-sub">De prestação de contas a portal de doadores — tudo em um sistema criado para quem transforma o Brasil com propósito.</p>
            <div class="hero-actions">
                <a href="#pricing" class="btn-hero-main"><i class="fas fa-heart"></i> Assinar Agora</a>
                <a href="#features" class="btn-hero-ghost"><i class="fas fa-play-circle"></i> Ver Recursos</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item"><div class="stat-num" data-count="2400">2.400+</div><div class="stat-label">ONGs Ativas</div></div>
                <div class="stat-item"><div class="stat-num">98k+</div><div class="stat-label">Beneficiários</div></div>
                <div class="stat-item"><div class="stat-num">R$ 12M+</div><div class="stat-label">Prestados em Contas</div></div>
            </div>
        </div>

        <div class="hero-map-wrap">
            <div class="map-container">
                <!-- Brazil SVG Map simplified -->
                <svg id="brazil-map" viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="mapGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#3B6CF6" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#E8455A" stop-opacity="0.2"/>
                        </linearGradient>
                        <filter id="glow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                    </defs>
                    <!-- Refined Brazil outline -->
                    <path d="M144,38c5.4-3.7,13.2-5.7,19.9-4.4s14.4,5.7,21.4,6.7c7,1,11.6-1.7,18.3,2.4s11.9,12.8,15.5,19.2s8,15.5,13.4,21.6s12.4,13.2,12.4,25.3s-2.3,20.3-5.2,29.4s-2.3,17.9,4.1,28.4s13.4,20.3,17.8,30.4s6.4,24,6.4,35.5s-2.3,22.6-6.2,32.8s-8.5,19.2-14.7,26.7s-10.3,12.8-17,19.2s-11.1,14.8-13.7,25s-5.2,21.6-10.3,31.1s-10.3,16.2-17.3,24.6s-12.4,13.5-18,21.6s-10.1,16.9-14.9,25.7s-8.8,14.5-14.4,14.5s-11.1-6.4-17.3-15.5s-11.3-19.6-16.5-30.7s-9.3-24-11.9-36.1s-3.9-24-4.9-36.5s-1.3-24.3,0.5-35.8s4.1-22,8.5-31.7s6.4-20.9,6.4-33.4s-3.1-24-8-34.1s-9.3-20.9-13.1-32.4s-5.7-23-5.7-33.8s2.6-19.6,7.7-29.7s10.8-18.2,17.8-25.7s11.6-13.2,17.8-14.9s11.6,3.7,11.6,10.5s-2.8,15.2-5.9,23.3s-5.2,17.2-5.2,26s3.9,16.9,10.3,26c13.1,17.2,13.1,17.2,18.8,11.5c6.4-10.5,6.4-16.5,6.4-27s-3.1-20.3-7.5-27.4s-8.8-10.5-8.8-18.6S138.6,41.7,144,38z"
                          fill="url(#mapGrad)" stroke="rgba(59,108,246,0.6)" stroke-width="1.5" filter="url(#glow)"/>
                    <!-- Northern region detail -->
                    <path d="M95 120 L110 105 L130 98 L150 100 L165 110 L180 120 L170 135 L155 140 L135 135 L115 130 Z"
                          fill="rgba(59,108,246,0.15)" stroke="rgba(59,108,246,0.3)" stroke-width="1"/>
                    <!-- Grid lines suggesting data -->
                    <line x1="80" y1="200" x2="350" y2="200" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="80" y1="250" x2="350" y2="250" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="80" y1="300" x2="350" y2="300" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="150" y1="50" x2="150" y2="430" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="220" y1="50" x2="220" y2="430" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                    <line x1="290" y1="50" x2="290" y2="430" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
                </svg>

                <!-- Pulsing dots for NGO locations -->
                <div class="map-dot d1"></div>
                <div class="map-dot blue d2"></div>
                <div class="map-dot d3"></div>
                <div class="map-dot teal d4"></div>
                <div class="map-dot blue d5"></div>
                <div class="map-dot d6"></div>
                <div class="map-dot teal d7"></div>
                <div class="map-dot d8"></div>
                <div class="map-dot blue d9"></div>

                <!-- Floating dashboard cards -->
                <div class="floating-card fc-1">
                    <div class="fc-label">Doações do mês</div>
                    <div class="fc-value">R$ 24.780</div>
                    <div class="fc-delta"><i class="fas fa-arrow-up"></i> +18% vs anterior</div>
                </div>
                <div class="floating-card fc-2">
                    <div class="fc-label">Voluntários ativos</div>
                    <div class="fc-value">143 <span style="font-size:.7rem;color:var(--teal)">🏅</span></div>
                    <div class="fc-delta"><i class="fas fa-star"></i> 3 novos Diamante</div>
                </div>
                <div class="floating-card fc-3">
                    <div class="fc-label">Editais abertos</div>
                    <div class="fc-value">7 ativos</div>
                    <div class="fc-delta" style="color:var(--gold)"><i class="fas fa-clock"></i> 2 com prazo em breve</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
    <div class="trust-inner">
        <span class="trust-text">Confiado por organizações em todo o Brasil</span>
        <div class="trust-item"><i class="fas fa-shield-check"></i> LGPD Compliant</div>
        <div class="trust-item"><i class="fas fa-server"></i> AWS Brasil</div>
        <div class="trust-item"><i class="fas fa-lock"></i> SSL + 2FA</div>
        <div class="trust-item"><i class="fas fa-star"></i> 4.9/5 pelos usuários</div>
        <div class="trust-item"><i class="fas fa-file-invoice"></i> Pronta para auditoria fiscal</div>
    </div>
</div>

<!-- PROBLEM → SOLUTION -->
<section class="problem-section animate-on-scroll" id="features">
    <div class="problem-grid">
        <div>
            <div class="section-tag tag-rose"><i class="fas fa-triangle-exclamation"></i> O Problema Real</div>
            <h2 class="section-title">ONGs lindas por fora,<br>caóticas por dentro.</h2>
            <p class="section-sub">Planilhas perdidas. Prestação de contas na última hora. Doadores sem acompanhamento. Voluntários desmotivados. Isso trava o impacto.</p>
            <div class="pain-list">
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="pain-text"><h4>Relatórios manuais que levam dias</h4><p>Horas gastas organizando planilhas em vez de ampliar o impacto social.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-users-slash"></i></div>
                    <div class="pain-text"><h4>Doadores que somem sem retorno</h4><p>Falta de CRM para engajar quem apoia sua causa com transparência.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="pain-text"><h4>Editais perdidos por falta de controle</h4><p>Oportunidades de captação passando sem gestão centralizada.</p></div>
                </div>
            </div>
        </div>
        <div class="solution-visual animate-on-scroll">
            <div class="dashboard-mock">
                <div class="mock-header">
                    <div class="mock-dot" style="background:#FF5F57"></div>
                    <div class="mock-dot" style="background:#FEBC2E"></div>
                    <div class="mock-dot" style="background:#28C840"></div>
                    <span class="mock-title" style="margin-left:8px">Vivensi — Dashboard ONG</span>
                </div>
                <div class="mock-kpi-row">
                    <div class="mock-kpi"><div class="mock-kpi-label">Doadores Ativos</div><div class="mock-kpi-value" style="color:var(--rose-light)">247</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Arrecadado/mês</div><div class="mock-kpi-value" style="color:var(--teal)">R$28k</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Beneficiários</div><div class="mock-kpi-value" style="color:var(--blue-light)">1.840</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Voluntários</div><div class="mock-kpi-value" style="color:var(--gold)">143</div></div>
                </div>
                <div class="mock-bar-wrap">
                    <div class="mock-bar-label"><span>Meta Anual</span><span style="color:var(--teal)">78%</span></div>
                    <div class="mock-bar"><div class="mock-bar-fill" style="--target-width:78%;background:linear-gradient(90deg,var(--rose),var(--gold));width:0"></div></div>
                    <div class="mock-bar-label"><span>Editais Captados</span><span style="color:var(--blue-light)">5/7</span></div>
                    <div class="mock-bar"><div class="mock-bar-fill" style="--target-width:71%;background:linear-gradient(90deg,var(--blue),var(--teal));width:0"></div></div>
                    <div class="mock-bar-label"><span>Prestação de Contas</span><span style="color:var(--teal)">100%</span></div>
                    <div class="mock-bar"><div class="mock-bar-fill" style="--target-width:100%;background:var(--teal);width:0"></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES GRID -->
<section class="features-section">
    <div class="center animate-on-scroll">
        <div class="section-tag tag-blue"><i class="fas fa-sparkles"></i> Funcionalidades</div>
        <h2 class="section-title">Tudo que sua ONG precisa,<br>em um só lugar</h2>
        <p class="section-sub center" style="max-width:560px">Desenvolvido com e para gestores do terceiro setor. Cada funcionalidade resolve um problema real.</p>
    </div>
    <div class="features-grid">
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-rose"><i class="fas fa-hand-holding-heart"></i></div>
            <h3>Portal VIP do Doador</h3>
            <p>Link mágico personalizado por doador. Histórico completo de doações e download do Informe de Rendimentos para o IRPF.</p>
            <span class="feat-tag ft-new">Novo</span>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-blue"><i class="fas fa-file-invoice-dollar"></i></div>
            <h3>Prestação de Contas</h3>
            <p>DRE, Balancetes e relatórios com um clique. Prontos para auditoria, conselho fiscal e entidades financiadoras.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-teal"><i class="fas fa-boxes-stacked"></i></div>
            <h3>Almoxarifado & Estoque</h3>
            <p>Controle de cestas básicas, roupas e doações físicas. Registro de entrada e saída com histórico completo.</p>
            <span class="feat-tag ft-new">Novo</span>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-gold"><i class="fas fa-trophy"></i></div>
            <h3>Gamificação de Voluntários</h3>
            <p>Sistema de pontos por horas doadas. Crachás Bronze, Prata, Ouro e Diamante para motivar seu time.</p>
            <span class="feat-tag ft-new">Novo</span>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-purple"><i class="fas fa-kanban"></i></div>
            <h3>CRM de Patrocínios (Kanban)</h3>
            <p>Funil visual de empresas parceiras. Arraste deals entre Prospecção, Negociação e Ganho com drag & drop.</p>
            <span class="feat-tag ft-new">Novo</span>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-sky"><i class="fas fa-scroll"></i></div>
            <h3>Gestão de Editais</h3>
            <p>Controle de prazos, documentos e status de cada edital. Nunca mais perca uma oportunidade de captação.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-rose"><i class="fas fa-users"></i></div>
            <h3>RH & Voluntários</h3>
            <p>Ficha completa, certificados de voluntariado, controle de horas e folha de pagamento de colaboradores.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-blue"><i class="fas fa-globe"></i></div>
            <h3>Portal de Transparência</h3>
            <p>Página pública automática mostrando o impacto real de cada real arrecadado. Construa confiança institucional.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-teal"><i class="fas fa-brain"></i></div>
            <h3>IA para Editais</h3>
            <p>Geração automática de projetos via DeepSeek ou Gemini. Análise de viabilidade e sugestão de melhorias.</p>
            <span class="feat-tag ft-pro">Pro</span>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="timeline-section animate-on-scroll">
    <div class="center">
        <div class="section-tag tag-teal"><i class="fas fa-route"></i> Como Funciona</div>
        <h2 class="section-title">Da inscrição ao impacto<br>em 4 etapas</h2>
    </div>
    <div class="timeline">
        <div class="tl-item">
            <div class="tl-icon" style="background:rgba(232,69,90,.15);color:var(--rose-light)"><i class="fas fa-user-plus"></i></div>
            <div class="tl-text"><h4>1. Crie sua conta em 2 minutos</h4><p>Assine um plano, configure o perfil da ONG e importe dados existentes. Suporte incluso no onboarding.</p></div>
        </div>
        <div class="tl-item">
            <div class="tl-icon" style="background:rgba(59,108,246,.15);color:var(--blue-light)"><i class="fas fa-sliders"></i></div>
            <div class="tl-text"><h4>2. Configure sua estrutura</h4><p>Cadastre projetos, beneficiários, doadores e voluntários. O sistema aprende com seu fluxo de trabalho.</p></div>
        </div>
        <div class="tl-item">
            <div class="tl-icon" style="background:rgba(0,212,170,.15);color:var(--teal)"><i class="fas fa-chart-line"></i></div>
            <div class="tl-text"><h4>3. Gerencie com clareza</h4><p>Dashboard em tempo real, alertas automáticos, prestação de contas gerada em segundos e relatórios prontos.</p></div>
        </div>
        <div class="tl-item">
            <div class="tl-icon" style="background:rgba(245,166,35,.15);color:var(--gold)"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="tl-text"><h4>4. Amplie seu impacto</h4><p>Com tudo organizado, concentre 100% da energia na missão. Capte mais editais, engaje mais doadores.</p></div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing-section" id="pricing">
    <div class="center animate-on-scroll">
        <div class="section-tag tag-rose"><i class="fas fa-tag"></i> Planos</div>
        <h2 class="section-title">Invista na sua missão</h2>
        <p class="section-sub center">Sem fidelidade, sem taxa de setup. Cancele quando quiser.</p>
        <div class="billing-toggle">
            <span class="toggle-label active" id="lbl-monthly">Mensal</span>
            <label class="switch"><input type="checkbox" id="billing-toggle" onchange="toggleBilling()"><span class="slider"></span></label>
            <span class="toggle-label" id="lbl-yearly">Anual <span class="discount-badge">-10% OFF</span></span>
        </div>
    </div>
    <div class="pricing-grid">
        @forelse($plans as $plan)
        <div class="price-card {{ $loop->index === 1 ? 'featured' : '' }}">
            @if($loop->index === 1)<div class="featured-badge">Mais Popular</div>@endif
            <div class="price-name">{{ $plan->name }}</div>
            <div class="price-amount">
                <span class="cur">R$</span>
                <span class="amount" data-monthly="{{ $plan->price }}" data-yearly="{{ $plan->price_yearly ?? ($plan->price * 12 * 0.9) }}">{{ number_format($plan->price, 2, ',', '.') }}</span>
                <span class="period">/mês</span>
            </div>
            <div class="price-note" id="price-note-{{ $loop->index }}">cobrado mensalmente</div>
            <div class="price-divider"></div>
            <ul class="price-features">
                @if($plan->features)
                    @foreach($plan->features as $feat)
                    <li><i class="fas fa-circle-check"></i> {{ $feat }}</li>
                    @endforeach
                @endif
            </ul>
            <a href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']) }}"
               class="btn-plan {{ $loop->index === 1 ? 'btn-plan-main' : 'btn-plan-outline' }} btn-subscribe"
               data-plan-id="{{ $plan->id }}">
               {{ $loop->index === 1 ? '🚀 Assinar Agora' : 'Escolher Plano' }}
            </a>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;color:rgba(255,255,255,.5)">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:16px;display:block"></i>
            Planos sob consulta — <a href="{{ route('login') }}" style="color:var(--rose-light)">entre em contato</a>
        </div>
        @endforelse
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta animate-on-scroll">
    <div class="section-tag tag-rose center" style="justify-content:center"><i class="fas fa-rocket"></i> Começe hoje</div>
    <h2>Sua ONG merece gestão de alto nível.</h2>
    <p>Junte-se a +2.400 organizações que já transformam mais com menos esforço.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
        <a href="#pricing" class="btn-hero-main" style="font-size:1.05rem;padding:18px 44px"><i class="fas fa-heart"></i> Assinar Plano</a>
        <a href="{{ route('login') }}" class="btn-hero-ghost" style="font-size:1.05rem;padding:18px 36px"><i class="fas fa-sign-in-alt"></i> Já tenho conta</a>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi" style="height:32px;margin-bottom:16px;filter:brightness(0) invert(1);opacity:.7">
            <p style="font-size:.875rem;color:rgba(255,255,255,.35);line-height:1.7">Tecnologia para quem<br>transforma o mundo.</p>
        </div>
        <div class="footer-col">
            <h4>Produto</h4>
            <a href="#features">Recursos</a>
            <a href="#pricing">Planos</a>
            <a href="{{ route('solutions.ngo') }}">Para ONGs</a>
        </div>
        <div class="footer-col">
            <h4>Empresa</h4>
            <a href="{{ route('public.page', 'sobre') }}">Sobre</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Criar conta</a>
        </div>
        <div class="footer-col">
            <h4>Legal</h4>
            <a href="{{ route('public.page', 'privacidade') }}">Privacidade</a>
            <a href="{{ route('public.page', 'termos') }}">Termos de Uso</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Vivensi. Todos os direitos reservados.</p>
        <p>Feito com <span style="color:var(--rose)">♥</span> para o terceiro setor brasileiro</p>
    </div>
</footer>

<script>
// Billing toggle
function toggleBilling(){
    const yearly = document.getElementById('billing-toggle').checked;
    document.getElementById('lbl-monthly').classList.toggle('active',!yearly);
    document.getElementById('lbl-yearly').classList.toggle('active',yearly);
    document.querySelectorAll('.amount').forEach(el=>{
        const m=parseFloat(el.dataset.monthly),y=parseFloat(el.dataset.yearly);
        el.textContent=new Intl.NumberFormat('pt-BR',{minimumFractionDigits:2}).format(yearly?y/12:m);
    });
    document.querySelectorAll('[id^="price-note-"]').forEach(el=>{
        el.textContent=yearly?'cobrado anualmente (economize 10%)':'cobrado mensalmente';
    });
    document.querySelectorAll('.btn-subscribe').forEach(btn=>{
        const id=btn.dataset.planId,c=yearly?'yearly':'monthly';
        btn.href=`/register?plan_id=${id}&billing_cycle=${c}`;
    });
}

// Scroll animations
const observer=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');
        // Animate bars
        e.target.querySelectorAll('.mock-bar-fill').forEach(b=>{
            const w=getComputedStyle(b).getPropertyValue('--target-width');
            b.style.width=w;b.style.transition='width 1.2s cubic-bezier(.22,1,.36,1)';
        });
    }});
},{threshold:0.15});
document.querySelectorAll('.animate-on-scroll').forEach(el=>observer.observe(el));

// Animate hero bars on load
window.addEventListener('load',()=>{
    setTimeout(()=>{
        document.querySelectorAll('.mock-bar-fill').forEach(b=>{
            const w=getComputedStyle(b).getPropertyValue('--target-width');
            b.style.width=w;b.style.transition='width 1.2s cubic-bezier(.22,1,.36,1)';
        });
    },600);
});

// Navbar scroll effect
window.addEventListener('scroll',()=>{
    document.querySelector('.nav').style.background=
        window.scrollY>50?'rgba(11,17,32,0.97)':'rgba(11,17,32,0.85)';
});
</script>
@include('partials.whatsapp-button')
</body>
</html>
