<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi - Gestão de Projetos e Empresas | Performance SaaS</title>
    <meta name="description" content="Escale seus projetos sem perder o controle. Kanban, Gantt e Financeiro integrados para empresas de alta performance.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
<style>
/* CSS copied and adapted from solutions_ngo.blade.php */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --ink:#0B1120;--ink2:#1E2D40;
    --primary:#4f46e5;--primary-light:#818cf8;
    --accent:#16a34a;--accent-light:#4ade80;
    --rose:#E8455A;
    --blue:#3B6CF6;
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
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark,#4338ca));color:white;text-decoration:none;font-weight:700;font-size:.9rem;padding:10px 24px;border-radius:50px;box-shadow:0 4px 20px rgba(79,70,229,.35);transition:all .3s}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(79,70,229,.5)}

/* ─── HERO ─── */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;background:radial-gradient(ellipse 80% 70% at 60% 40%, rgba(59,108,246,.15) 0%, transparent 60%), radial-gradient(ellipse 50% 60% at 20% 80%, rgba(22,163,74,.1) 0%, transparent 50%), var(--ink)}
.hero-content{position:relative;z-index:2;padding:120px 6% 80px;max-width:1400px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--accent-light);font-size:.8rem;font-weight:700;padding:6px 16px;border-radius:100px;margin-bottom:28px;letter-spacing:.05em;text-transform:uppercase}
.hero-badge span{width:6px;height:6px;background:var(--accent-light);border-radius:50%;animation:pulseGreen 2s infinite}
.hero-title{font-size:clamp(2.5rem,5vw,4rem);font-weight:900;line-height:1.05;letter-spacing:-.03em;margin-bottom:24px}
.hero-title .accent{background:linear-gradient(135deg,var(--accent-light),var(--teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:1.15rem;color:rgba(255,255,255,0.6);line-height:1.7;margin-bottom:40px;max-width:520px}
.hero-actions{display:flex;gap:16px;flex-wrap:wrap}
.btn-hero-main{background:linear-gradient(135deg,var(--primary),#4338ca);color:white;text-decoration:none;font-weight:700;font-size:1rem;padding:16px 36px;border-radius:60px;box-shadow:0 6px 30px rgba(79,70,229,.4);transition:all .3s;display:inline-flex;align-items:center;gap:10px}
.btn-hero-main:hover{transform:translateY(-3px);box-shadow:0 12px 40px rgba(79,70,229,.55)}
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
.map-dot{position:absolute;width:10px;height:10px;border-radius:50%;background:var(--accent-light);box-shadow:0 0 12px var(--accent);animation:mapPulse 2.5s infinite}
.map-dot::before{content:'';position:absolute;inset:-6px;border-radius:50%;background:var(--accent-light);opacity:.2;animation:mapRipple 2.5s infinite}
.map-dot.blue{background:var(--blue-light,#5B82FF);box-shadow:0 0 12px var(--blue);}.map-dot.blue::before{background:var(--blue-light,#5B82FF)}
.map-dot.teal{background:var(--teal);box-shadow:0 0 12px var(--teal);}.map-dot.teal::before{background:var(--teal)}
.d1{top:22%;left:38%;animation-delay:0s}.d2{top:30%;left:52%;animation-delay:.4s}.d3{top:42%;left:44%;animation-delay:.8s}
.d4{top:55%;left:36%;animation-delay:1.2s}.d5{top:50%;left:60%;animation-delay:1.6s}.d6{top:68%;left:42%;animation-delay:.6s}
.d7{top:34%;left:28%;animation-delay:1s}.d8{top:18%;left:55%;animation-delay:.2s}.d9{top:62%;left:55%;animation-delay:1.4s}

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
.tag-accent{color:var(--accent-light)}
.tag-blue{color:var(--blue-light,#5B82FF)}
.tag-teal{color:var(--teal)}
.section-title{font-size:clamp(2rem,4vw,3rem);font-weight:900;line-height:1.1;letter-spacing:-.02em;margin-bottom:20px}
.section-sub{font-size:1.05rem;color:rgba(255,255,255,.55);line-height:1.7;max-width:580px}
.center{text-align:center;margin-left:auto;margin-right:auto}

/* ─── PROBLEM → SOLUTION ─── */
.problem-section{background:radial-gradient(ellipse 60% 80% at 20% 50%, rgba(22,163,74,.07) 0%, transparent 60%)}
.problem-grid{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1200px;margin:0 auto}
.pain-list{display:flex;flex-direction:column;gap:20px;margin-top:40px}
.pain-item{display:flex;align-items:flex-start;gap:16px;padding:22px;background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.15);border-radius:16px}
.pain-icon{width:44px;height:44px;background:rgba(22,163,74,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--accent-light);font-size:1.1rem;flex-shrink:0}
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
.fi-accent{background:rgba(22,163,74,.15);color:var(--accent-light)}
.fi-blue{background:rgba(59,108,246,.15);color:var(--blue-light,#5B82FF)}
.fi-teal{background:rgba(0,212,170,.15);color:var(--teal)}
.fi-gold{background:rgba(245,166,35,.15);color:var(--gold)}
.fi-purple{background:rgba(155,93,229,.15);color:#B27CFF}
.fi-sky{background:rgba(56,189,248,.15);color:#56C8F5}
.feat-card h3{font-size:1.05rem;font-weight:700;margin-bottom:10px}
.feat-card p{font-size:.875rem;color:rgba(255,255,255,.5);line-height:1.7}
.feat-tag{display:inline-block;margin-top:14px;font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.ft-new{background:rgba(0,212,170,.15);color:var(--teal)}

/* ─── PRICING ─── */
.pricing-section{background:radial-gradient(ellipse 70% 60% at 50% 20%, rgba(59,108,246,.1) 0%, transparent 70%)}
.pricing-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;max-width:1100px;margin:60px auto 0}
.price-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);border-radius:24px;padding:40px;position:relative;transition:all .4s;overflow:hidden}
.price-card.featured{background:linear-gradient(135deg,rgba(79,70,229,.15),rgba(22,163,74,.1));border-color:rgba(79,70,229,.4);transform:scale(1.03)}
.price-card:hover{border-color:rgba(255,255,255,.25);transform:translateY(-8px);box-shadow:0 30px 60px rgba(0,0,0,.4)}
.price-card.featured:hover{transform:scale(1.03) translateY(-8px)}
.featured-badge{position:absolute;top:20px;right:20px;background:linear-gradient(135deg,var(--primary),#4338ca);color:white;font-size:.7rem;font-weight:800;padding:4px 12px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em}
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
.btn-plan-main{background:linear-gradient(135deg,var(--primary),#4338ca);color:white;box-shadow:0 6px 24px rgba(79,70,229,.35)}
.btn-plan-main:hover{box-shadow:0 10px 35px rgba(79,70,229,.55);transform:translateY(-2px)}
.btn-plan-outline{border:1.5px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8)}
.btn-plan-outline:hover{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.4);color:white}
.billing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:32px}
.toggle-label{font-size:.9rem;font-weight:600;color:rgba(255,255,255,.5)}
.toggle-label.active{color:white}
.switch{position:relative;display:inline-block;width:52px;height:28px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,.15);border-radius:28px;transition:.3s}
.slider:before{content:'';position:absolute;height:20px;width:20px;left:4px;bottom:4px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--primary)}
input:checked+.slider:before{transform:translateX(24px)}
.discount-badge{background:rgba(0,212,170,.15);color:var(--teal);font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:100px}

/* ─── FINAL CTA ─── */
.final-cta{position:relative;overflow:hidden;text-align:center;background:linear-gradient(135deg,rgba(79,70,229,.2),rgba(59,108,246,.15));border-top:1px solid rgba(255,255,255,.07)}
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
@keyframes pulseGreen{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.5)}}
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
    <a href="{{ url('/') }}" class="nav-logo"><x-application-logo style="height: 36px; width: auto;" /></a>
    <ul class="nav-links">
        <li><a href="{{ route('solutions.ngo') }}">Terceiro Setor</a></li>
        <li><a href="{{ route('solutions.manager') }}" class="active">Gestores</a></li>
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
            <div class="hero-badge"><span></span> Performance & Gestão de Projetos</div>
            <h1 class="hero-title">Domine sua operação <br>com <span class="accent">inteligência real</span></h1>
            <p class="hero-sub">A primeira plataforma que une Kanban, Gantt e Financeiro em tempo real. Tome decisões baseadas em dados, não em palpites.</p>
            <div class="hero-actions">
                <a href="#pricing" class="btn-hero-main"><i class="fas fa-chart-line"></i> Ver Planos</a>
                <a href="#features" class="btn-hero-ghost"><i class="fas fa-play-circle"></i> Ver Demo</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item"><div class="stat-num">500+</div><div class="stat-label">Empresas</div></div>
                <div class="stat-item"><div class="stat-num">12k+</div><div class="stat-label">Tasks diárias</div></div>
                <div class="stat-item"><div class="stat-num">98%</div><div class="stat-label">Uptime</div></div>
            </div>
        </div>

        <div class="hero-map-wrap">
            <div class="map-container">
                <svg id="brazil-map" viewBox="0 0 400 500" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="mapGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#16a34a" stop-opacity="0.2"/>
                        </linearGradient>
                        <filter id="glow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                    </defs>
                    <path d="M180 30 L220 25 L260 40 L290 55 L310 80 L320 110 L325 140 L320 165 L310 180 L330 200 L345 225 L350 255 L345 285 L330 310 L310 330 L285 345 L260 355 L235 370 L215 390 L200 410 L185 430 L175 420 L165 400 L150 380 L135 360 L120 340 L105 315 L95 285 L90 260 L95 235 L105 215 L90 195 L75 170 L70 145 L75 120 L85 100 L100 80 L120 62 L145 45 Z"
                          fill="url(#mapGrad)" stroke="rgba(79,70,229,0.6)" stroke-width="1.5" filter="url(#glow)"/>
                </svg>

                <div class="map-dot d1"></div>
                <div class="map-dot blue d2"></div>
                <div class="map-dot d3"></div>
                <div class="map-dot teal d4"></div>
                <div class="map-dot blue d5"></div>
                <div class="map-dot d6"></div>
                <div class="map-dot d7"></div>
                <div class="map-dot d8"></div>
                <div class="map-dot blue d9"></div>

                <div class="floating-card fc-1">
                    <div class="fc-label">ROI Médio</div>
                    <div class="fc-value">+34%</div>
                    <div class="fc-delta"><i class="fas fa-arrow-up"></i> Eficiência operacional</div>
                </div>
                <div class="floating-card fc-2">
                    <div class="fc-label">Margem Real</div>
                    <div class="fc-value">R$ 152k</div>
                    <div class="fc-delta"><i class="fas fa-check"></i> Lucro validado</div>
                </div>
                <div class="floating-card fc-3">
                    <div class="fc-label">Status Projetos</div>
                    <div class="fc-value">84% No Prazo</div>
                    <div class="fc-delta" style="color:var(--teal)"><i class="fas fa-clock"></i> Gantt atualizado</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
    <div class="trust-inner">
        <span class="trust-text">Infraestrutura robusta para sua empresa</span>
        <div class="trust-item"><i class="fas fa-shield-halved"></i> Audit Trails</div>
        <div class="trust-item"><i class="fas fa-server"></i> AWS Enterprise</div>
        <div class="trust-item"><i class="fas fa-lock"></i> MFA + End-to-End</div>
        <div class="trust-item"><i class="fas fa-microchip"></i> Gestão via Dados</div>
    </div>
</div>

<!-- PROBLEM → SOLUTION -->
<section class="problem-section animate-on-scroll" id="features">
    <div class="problem-grid">
        <div>
            <div class="section-tag tag-accent"><i class="fas fa-triangle-exclamation"></i> O Gargalo da Gestão</div>
            <h2 class="section-title">Pare de gerir por planilhas e comece a <span style="color:var(--accent-light)">gerir por lucro.</span></h2>
            <p class="section-sub">Informação fragmentada. Projetos atrasados. Equipe sobrecarregada. Falta de visão financeira real. O Vivensi Manager resolve isso.</p>
            <div class="pain-list">
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="pain-text"><h4>Cronogramas que nunca batem</h4><p>Gerencie prazos reais com nosso gráfico de Gantt dinâmico e alertas de atraso.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-sack-dollar"></i></div>
                    <div class="pain-text"><h4>Furo de caixa por projeto</h4><p>Saiba exatamente onde cada centavo está sendo gasto por centro de custo.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-users-viewfinder"></i></div>
                    <div class="pain-text"><h4>Falta de clareza nas responsabilidades</h4><p>Kanban visual com responsáveis e prazos definidos para cada micro-task.</p></div>
                </div>
            </div>
        </div>
        <div class="solution-visual animate-on-scroll">
            <div class="dashboard-mock">
                <div class="mock-header">
                    <div class="mock-dot" style="background:#FF5F57"></div>
                    <div class="mock-dot" style="background:#FEBC2E"></div>
                    <div class="mock-dot" style="background:#28C840"></div>
                    <span class="mock-title" style="margin-left:8px">Vivensi — Dashboard Manager</span>
                </div>
                <div class="mock-kpi-row">
                    <div class="mock-kpi"><div class="mock-kpi-label">Projetos Ativos</div><div class="mock-kpi-value" style="color:var(--accent-light)">12</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Receita Estimada</div><div class="mock-kpi-value" style="color:var(--teal)">R$450k</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Tasks Concluídas</div><div class="mock-kpi-value" style="color:var(--primary-light)">84%</div></div>
                    <div class="mock-kpi"><div class="mock-kpi-label">Gargalo de Equipe</div><div class="mock-kpi-value" style="color:var(--gold)">Baixo</div></div>
                </div>
                <div class="mock-bar-wrap">
                    <div class="mock-bar-label"><span>Rentabilidade Média</span><span style="color:var(--teal)">42%</span></div>
                    <div class="mock-bar"><div class="mock-bar-fill" style="--target-width:42%;background:linear-gradient(90deg,var(--primary),var(--accent));width:0"></div></div>
                    <div class="mock-bar-label"><span>Saúde dos Projetos</span><span style="color:var(--blue)">92%</span></div>
                    <div class="mock-bar"><div class="mock-bar-fill" style="--target-width:92%;background:linear-gradient(90deg,var(--blue),var(--teal));width:0"></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES GRID -->
<section class="features-section">
    <div class="center animate-on-scroll">
        <div class="section-tag tag-blue"><i class="fas fa-layer-group"></i> Ecossistema Manager</div>
        <h2 class="section-title">Domine a operação de ponta a ponta</h2>
        <p class="section-sub center" style="max-width:560px">Uma suite de ferramentas integradas para quem não tem tempo a perder.</p>
    </div>
    <div class="features-grid">
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-accent"><i class="fas fa-table-list"></i></div>
            <h3>Gestão Híbrida</h3>
            <p>Visualize o mesmo projeto em Kanban, Lista ou Gantt. Adapte a ferramenta ao seu fluxo, não o contrário.</p>
            <span class="feat-tag ft-new">Novo</span>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-blue"><i class="fas fa-coins"></i></div>
            <h3>Margem Real</h3>
            <p>Vincule cada despesa e receita a um projeto. Saiba exatamente qual cliente é mais rentável em tempo real.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-teal"><i class="fas fa-fingerprint"></i></div>
            <h3>Segurança Enterprise</h3>
            <p>Logs de auditoria em todas as ações. Saiba quem fez o quê e quando. Controle de permissões granular.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-gold"><i class="fas fa-user-secret"></i></div>
            <h3>Permissões por Nível</h3>
            <p>Admin, Gerente e Colaborador. Cada um visualiza apenas o que é necessário para sua função.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-purple"><i class="fas fa-chart-pie"></i></div>
            <h3>DRE Automático</h3>
            <p>Relatórios financeiros estruturados por projeto ou consolidado da empresa a um clique de distância.</p>
        </div>
        <div class="feat-card animate-on-scroll">
            <div class="feat-icon fi-sky"><i class="fas fa-mobile-screen-button"></i></div>
            <h3>Acesso Web & Mobile</h3>
            <p>Gestão na nuvem. Acompanhe o status da sua empresa de qualquer lugar, em qualquer dispositivo.</p>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing-section" id="pricing">
    <div class="center animate-on-scroll">
        <div class="section-tag tag-accent"><i class="fas fa-bolt"></i> Escalabilidade</div>
        <h2 class="section-title">Potencialize sua empresa</h2>
        <p class="section-sub center">Escolha o plano que melhor se adapta ao tamanho do seu time.</p>
        <div class="billing-toggle">
            <span class="toggle-label active" id="lbl-monthly">Mensal</span>
            <label class="switch"><input type="checkbox" id="billing-toggle" onchange="toggleBilling()"><span class="slider"></span></label>
            <span class="toggle-label" id="lbl-yearly">Anual <span class="discount-badge">-10% OFF</span></span>
        </div>
    </div>
    <div class="pricing-grid">
        @forelse($plans as $plan)
        <div class="price-card {{ $loop->index === 1 ? 'featured' : '' }}">
            @if($loop->index === 1)<div class="featured-badge">Melhor Valor</div>@endif
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
               {{ $loop->index === 1 ? '🚀 Ativar Performance' : 'Escolher Plano' }}
            </a>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;color:rgba(255,255,255,.5)">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:16px;display:block"></i>
            Carregando planos...
        </div>
        @endforelse
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta animate-on-scroll">
    <div class="section-tag tag-accent center" style="justify-content:center"><i class="fas fa-rocket"></i> Futuro da Gestão</div>
    <h2>Decisões rápidas. Execução impecável.</h2>
    <p>Leve sua empresa para o próximo nível com a infraestrutura que ela merece.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
        <a href="#pricing" class="btn-hero-main" style="font-size:1.05rem;padding:18px 44px"><i class="fas fa-bolt"></i> Começar Agora</a>
        <a href="{{ route('login') }}" class="btn-hero-ghost" style="font-size:1.05rem;padding:18px 36px">Falar com Consultor</a>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <x-application-logo style="height:32px;margin-bottom:16px;filter:brightness(0) invert(1);opacity:.7" />
            <p style="font-size:.875rem;color:rgba(255,255,255,.35);line-height:1.7">Performance e Inteligência<br>para sua Gestão.</p>
        </div>
        <div class="footer-col">
            <h4>Soluções</h4>
            <a href="{{ route('solutions.ngo') }}">Terceiro Setor</a>
            <a href="{{ route('solutions.manager') }}">Projetos</a>
            <a href="{{ route('solutions.common') }}">Pessoal</a>
        </div>
        <div class="footer-col">
            <h4>Empresa</h4>
            <a href="{{ route('public.page', 'sobre') }}">Sobre</a>
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Registro</a>
        </div>
        <div class="footer-col">
            <h4>Legal</h4>
            <a href="{{ route('public.page', 'privacidade') }}">Privacidade</a>
            <a href="{{ route('public.page', 'termos') }}">Termos</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Vivensi. Tecnologia de Ponta.</p>
        <p>Vivensi Manager — Alta Performance</p>
    </div>
</footer>

<script>
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
const observer=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');
        e.target.querySelectorAll('.mock-bar-fill').forEach(b=>{
            const w=getComputedStyle(b).getPropertyValue('--target-width');
            b.style.width=w;b.style.transition='width 1.2s cubic-bezier(.22,1,.36,1)';
        });
    }});
},{threshold:0.15});
document.querySelectorAll('.animate-on-scroll').forEach(el=>observer.observe(el));
window.addEventListener('load',()=>{
    setTimeout(()=>{
        document.querySelectorAll('.mock-bar-fill').forEach(b=>{
            const w=getComputedStyle(b).getPropertyValue('--target-width');
            b.style.width=w;b.style.transition='width 1.2s cubic-bezier(.22,1,.36,1)';
        });
    },600);
});
</script>
</body>
</html>
