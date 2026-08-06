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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi | Gestão Inteligente para ONGs e Organizações do Terceiro Setor</title>
    <meta name="description" content="A plataforma mais completa do Brasil para ONGs e organizações do terceiro setor. Prestação de contas, portal do doador, CRM, captação de editais e Bruce IA — o assistente estratégico da sua organização social.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/novalogo.png') }}">
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

/* ══ ANNOUNCE BAR ══════════════════════════════════════════════════ */
.announce-bar{
    position:fixed;top:0;left:0;right:0;z-index:1000;
    background:#1a1a1a;
    border-bottom:1px solid rgba(255,255,255,.06);
    display:flex;align-items:center;justify-content:center;
    padding:9px 24px;gap:10px;
    font-size:.75rem;font-weight:600;color:rgba(255,255,255,.55);
    letter-spacing:.01em;
    transition:transform .3s ease;
}
.announce-bar a{color:rgba(255,255,255,.85);text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s}
.announce-bar a:hover{color:#fff}
.announce-pill{
    background:linear-gradient(135deg,rgba(79,110,247,.25),rgba(123,92,240,.2));
    border:1px solid rgba(79,110,247,.35);
    color:#93a8ff;
    font-size:.68rem;font-weight:800;
    padding:2px 9px;border-radius:100px;
    letter-spacing:.06em;text-transform:uppercase;
    white-space:nowrap;
}

/* ══ NAV ══════════════════════════════════════════════════════════ */
.nav{
    position:fixed;top:37px;left:0;right:0;z-index:999;
    display:flex;justify-content:space-between;align-items:center;
    padding:0 5%;height:64px;
    background:rgba(9,9,9,.82);
    backdrop-filter:blur(20px) saturate(180%);
    border-bottom:1px solid rgba(255,255,255,.06);
    transition:all .3s;
}
.nav.scrolled{top:0;background:rgba(9,9,9,.95);}
.nav-logo img{height:32px;display:block}
.nav-links{display:flex;gap:4px;list-style:none;align-items:center}
.nav-links a{
    color:rgba(255,255,255,.82);text-decoration:none;
    font-size:.83rem;font-weight:500;
    padding:7px 13px;border-radius:8px;
    transition:all .2s;white-space:nowrap;
}
.nav-links a:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.06)}
.nav-links .nav-active{color:rgba(255,255,255,.9)}
.nav-sep{width:1px;height:18px;background:rgba(255,255,255,.08);margin:0 4px}
.nav-ctas{display:flex;gap:8px;align-items:center}
.btn-ghost{
    color:rgba(255,255,255,.6);text-decoration:none;
    font-size:.83rem;font-weight:600;
    padding:8px 18px;border-radius:8px;
    border:1px solid rgba(255,255,255,.1);
    transition:all .2s;background:transparent;
}
.btn-ghost:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.18)}
.btn-nav{
    background:#ffffff;color:#0a0a0a;
    text-decoration:none;font-size:.83rem;font-weight:700;
    padding:9px 20px;border-radius:8px;
    transition:all .2s;white-space:nowrap;
    display:inline-flex;align-items:center;gap:7px;
}
.btn-nav:hover{background:#e8e8e8;transform:translateY(-1px);box-shadow:0 4px 16px rgba(255,255,255,.15)}
/* Nav dropdown (Soluções) */
.nav-dropdown{position:relative}
.nav-dropdown > a::after{
    content:'';display:inline-block;margin-left:5px;
    width:0;height:0;
    border-left:3.5px solid transparent;border-right:3.5px solid transparent;
    border-top:4px solid currentColor;
    opacity:.55;transition:transform .2s;vertical-align:middle;
}
.nav-dropdown:hover > a::after{transform:rotate(180deg)}
.nav-dropdown-menu{
    position:absolute;top:calc(100% + 6px);left:-6px;
    min-width:260px;
    background:#111111;border:1px solid rgba(255,255,255,.08);
    border-radius:14px;padding:8px;
    box-shadow:0 12px 40px rgba(0,0,0,.5);
    opacity:0;pointer-events:none;transform:translateY(-4px);
    transition:opacity .18s,transform .18s;
    display:flex;flex-direction:column;gap:2px;list-style:none;
}
.nav-dropdown:hover .nav-dropdown-menu,
.nav-dropdown:focus-within .nav-dropdown-menu{
    opacity:1;pointer-events:auto;transform:translateY(0);
}
.nav-dropdown-menu li{list-style:none}
.nav-dropdown-menu a{
    display:flex;flex-direction:column;gap:2px;
    padding:11px 13px;border-radius:10px;
    color:rgba(255,255,255,.78);font-size:.83rem;font-weight:600;
    text-decoration:none;transition:background .15s;
    white-space:normal;
}
.nav-dropdown-menu a:hover{background:rgba(255,255,255,.05);color:#fff}
.nav-dropdown-menu .ndm-title{display:flex;align-items:center;gap:8px}
.nav-dropdown-menu .ndm-badge{
    background:linear-gradient(135deg,#FF7A1A,#ea580c);color:#fff;
    font-size:.58rem;font-weight:800;
    padding:2px 7px;border-radius:6px;letter-spacing:.06em;
    text-transform:uppercase;
}
.nav-dropdown-menu .ndm-desc{
    font-size:.71rem;color:rgba(255,255,255,.42);
    font-weight:500;
}
/* Mobile */
.mobile-btn{display:none;background:none;border:none;color:rgba(255,255,255,.7);font-size:1.2rem;cursor:pointer;padding:6px}
@media(max-width:860px){
    .nav-links,.nav-ctas,.nav-sep{display:none}
    .mobile-btn{display:block}
    .announce-bar span:not(.announce-pill){display:none}
    .mobile-menu{
        display:none;flex-direction:column;gap:2px;
        position:fixed;top:100px;left:0;right:0;
        background:#111111;
        padding:16px 20px 24px;
        border-bottom:1px solid rgba(255,255,255,.07);
        border-top:1px solid rgba(255,255,255,.07);
        z-index:998;
    }
    .mobile-menu.open{display:flex}
    .mobile-menu a{
        color:rgba(255,255,255,.65);text-decoration:none;
        font-size:.95rem;font-weight:500;
        padding:12px 14px;border-radius:10px;
        transition:all .2s;
    }
    .mobile-menu a:hover{background:rgba(255,255,255,.06);color:white}
    .mobile-menu .m-cta{
        margin-top:8px;background:#ffffff;color:#0a0a0a;
        font-weight:700;border-radius:10px;text-align:center;
    }
    .mobile-menu .m-cta:hover{background:#e8e8e8}
}

/* ══ HERO ══════════════════════════════════════════════════════════ */
.hero{
    position:relative;
    min-height:100vh;
    display:flex;
    align-items:center;
    overflow:hidden;
    padding:140px 5% 80px;
    background:#090909;
}
/* Grid texture */
.hero::before{
    content:'';position:absolute;inset:0;
    background-image:
        linear-gradient(rgba(255,255,255,.028) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.028) 1px,transparent 1px);
    background-size:72px 72px;
    pointer-events:none;
}
/* Radial glow */
.hero::after{
    content:'';position:absolute;
    width:900px;height:900px;
    top:-200px;right:-100px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(79,110,247,.11) 0%,rgba(123,92,240,.06) 40%,transparent 70%);
    filter:blur(60px);
    pointer-events:none;
}

/* Layout */
.hero-inner{
    position:relative;z-index:2;
    max-width:1280px;margin:0 auto;width:100%;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:64px;
    align-items:center;
}

/* LEFT */
.hero-left{}
.hero-announce{
    display:inline-flex;align-items:center;gap:10px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.09);
    border-radius:100px;
    padding:6px 14px 6px 6px;
    margin-bottom:32px;
    font-size:.75rem;font-weight:600;color:rgba(255,255,255,.55);
    text-decoration:none;
    transition:border-color .2s;
    width:fit-content;
}
.hero-announce:hover{border-color:rgba(255,255,255,.2);color:rgba(255,255,255,.75)}
.ha-tag{
    background:linear-gradient(135deg,#4F6EF7,#7B5CF0);
    color:#fff;font-size:.65rem;font-weight:800;
    padding:3px 10px;border-radius:100px;
    letter-spacing:.06em;text-transform:uppercase;
    white-space:nowrap;
}
.ha-dot{width:5px;height:5px;border-radius:50%;background:#4ade80;animation:pulseDot 2s infinite}
@keyframes pulseDot{0%,100%{opacity:1}50%{opacity:.3}}

.hero-title{
    font-size:clamp(3rem,5.5vw,5rem);
    font-weight:900;
    line-height:1.02;
    letter-spacing:-.04em;
    margin-bottom:22px;
    animation:fadeUp .7s ease .1s both;
}
.ht-white{color:#ffffff}
.ht-dim{color:rgba(255,255,255,.25)}
.ht-grad{color:#6B8BFF}

.hero-sub{
    font-size:1.05rem;color:rgba(255,255,255,.8);
    max-width:480px;line-height:1.75;
    margin-bottom:36px;
    animation:fadeUp .7s ease .2s both;
}

.hero-ctas{
    display:flex;gap:12px;flex-wrap:wrap;
    margin-bottom:16px;
    animation:fadeUp .7s ease .3s both;
}
.hero-price-anchor{
    margin-bottom:36px;
    animation:fadeUp .7s ease .35s both;
}
.hero-price-anchor a{
    display:inline-flex;align-items:center;gap:8px;
    color:rgba(255,255,255,.82);text-decoration:none;
    font-size:.85rem;font-weight:500;
    padding:6px 14px;border-radius:999px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.1);
    transition:color .2s,background .2s;
}
.hero-price-anchor a:hover{color:#fff;background:rgba(255,255,255,.08)}
.hero-price-anchor strong{color:#fff;font-weight:800}
.hero-price-anchor .hpa-sep{color:rgba(255,255,255,.35)}
.hero-price-anchor .hpa-note{color:rgba(255,255,255,.7)}
.btn-hero{
    display:inline-flex;align-items:center;gap:8px;
    background:#ffffff;color:#0a0a0a;
    text-decoration:none;font-weight:700;font-size:.92rem;
    padding:13px 28px;border-radius:10px;
    transition:all .2s;
}
.btn-hero:hover{background:#e8e8e8;transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,255,255,.12)}
.btn-hero-outline{
    display:inline-flex;align-items:center;gap:8px;
    color:rgba(255,255,255,.6);text-decoration:none;
    font-weight:600;font-size:.92rem;
    padding:13px 24px;border-radius:10px;
    border:1px solid rgba(255,255,255,.12);
    transition:all .2s;background:transparent;
}
.btn-hero-outline:hover{color:rgba(255,255,255,.9);background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.22)}

/* Social proof row */
.hero-proof{
    display:flex;align-items:center;gap:16px;
    animation:fadeUp .7s ease .4s both;
}
.hp-avatars{display:flex}
.hp-av{
    width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,#4F6EF7,#7B5CF0);
    border:2px solid #090909;
    display:flex;align-items:center;justify-content:center;
    font-size:.62rem;font-weight:800;color:#fff;
    margin-left:-10px;flex-shrink:0;
}
.hp-av:first-child{margin-left:0}
.hp-av.av2{background:linear-gradient(135deg,#7B5CF0,#EC4899)}
.hp-av.av3{background:linear-gradient(135deg,#06b6d4,#3B82F6)}
.hp-av.av4{background:linear-gradient(135deg,#F59E0B,#EF4444)}
.hp-text{font-size:.8rem;color:rgba(255,255,255,.4);line-height:1.4}
.hp-text strong{color:rgba(255,255,255,.8);font-weight:700}
.hp-sep{width:1px;height:28px;background:rgba(255,255,255,.08)}
.hp-rating{display:flex;flex-direction:column;gap:2px}
.hp-stars{display:flex;gap:2px;color:#F59E0B;font-size:.65rem}
.hp-rlabel{font-size:.72rem;color:rgba(255,255,255,.72)}

/* RIGHT — Brazil Map */
.hero-right{position:relative;animation:fadeUp .7s ease .25s both;display:flex;align-items:center;justify-content:center}
.hero-map-wrap{position:relative;width:100%;max-width:460px}
.brazil-svg{width:100%;display:block;filter:drop-shadow(0 0 60px rgba(79,110,247,.18));animation:mapFadeIn .9s ease .5s both}
@keyframes mapFadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
/* Brazil shape */
.br-fill{fill:#0d1525;stroke:rgba(79,110,247,.48);stroke-width:1.5;stroke-linejoin:round;stroke-linecap:round}
.br-state-line{fill:none;stroke:rgba(79,110,247,.11);stroke-width:.75;stroke-linecap:round}
/* City dots */
.city-dot{animation:cdPop 2.5s ease-in-out infinite}
@keyframes cdPop{0%,100%{opacity:1}50%{opacity:.4}}
.city-ring{fill:none;stroke-width:1.4;animation:crExpand 2.5s ease-out infinite}
@keyframes crExpand{0%{r:7;opacity:.75}100%{r:24;opacity:0}}
/* Connection lines */
.conn-line{fill:none;stroke-width:.9;stroke-dasharray:3 4;animation:clFlow 2.8s linear infinite}
@keyframes clFlow{from{stroke-dashoffset:0}to{stroke-dashoffset:-14}}
/* Floating map badges */
.map-badge{
    position:absolute;background:rgba(10,10,15,.94);
    border:1px solid rgba(255,255,255,.1);backdrop-filter:blur(14px);
    border-radius:12px;padding:10px 16px;white-space:nowrap;
    z-index:3;box-shadow:0 8px 32px rgba(0,0,0,.45)
}
.mb-1{top:5%;right:-4%;animation:floatBadge 4s ease-in-out infinite}
.mb-2{top:40%;left:-10%;animation:floatBadge 4.5s ease-in-out infinite .7s}
.mb-3{bottom:8%;right:-2%;animation:floatBadge 3.8s ease-in-out infinite 1.3s}
.mb-val{font-size:1rem;font-weight:900;color:#fff;letter-spacing:-.02em;line-height:1}
.mb-lbl{font-size:.6rem;color:rgba(255,255,255,.72);font-weight:500;margin-top:3px}
.mb-live{display:flex;align-items:center;gap:5px;margin-bottom:4px}
.mb-ldot{width:6px;height:6px;border-radius:50%;background:#4ade80;animation:pulseDot 1.5s infinite;flex-shrink:0}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}

/* Hero responsive */
@media(max-width:1100px){
    .hero-inner{gap:40px}
    .hero-title{font-size:clamp(2.6rem,5vw,4rem)}
}
@media(max-width:860px){
    .hero-inner{grid-template-columns:1fr;gap:56px}
    .hero-title{font-size:clamp(2.6rem,8vw,3.8rem)}
    .hero-sub{max-width:100%}
    .map-badge{display:none}
    .hero-right{max-width:440px;margin:0 auto;width:100%}
}
@media(max-width:480px){
    .hero-title{font-size:2.4rem}
    .hero-map-wrap{max-width:320px}
}

/* MAP SECTION (legacy — hidden) */
.map-section{display:none}
.fcard,.fc-a,.fc-b,.fc-c,.fc-d,.fc-e{display:none}

/* ─── TRUST ─── */
.trust{padding:20px 6%;background:rgba(255,255,255,.025);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.trust-row{display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap}
.trust-lbl{font-size:.75rem;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.09em}
.trust-item{display:flex;align-items:center;gap:7px;color:rgba(255,255,255,.82);font-size:.82rem;font-weight:600}
.trust-item i{color:var(--teal)}

/* ══ SEGMENTS ═════════════════════════════════════════════════════ */
.segments{padding:120px 6%;background:#090909;position:relative}
.segments::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:72px 72px;pointer-events:none}

/* Section header */
.seg-header{max-width:1240px;margin:0 auto 56px;display:flex;align-items:flex-end;justify-content:space-between;gap:40px;flex-wrap:wrap;position:relative;z-index:1}
.seg-hdr-left{}
.seg-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.65);margin-bottom:14px}
.seg-title{font-size:clamp(2rem,3.5vw,2.8rem);font-weight:900;letter-spacing:-.035em;line-height:1.06;color:#fff}
.seg-title em{font-style:normal;color:rgba(255,255,255,.65)}
.seg-hdr-right{max-width:260px;font-size:.85rem;color:rgba(255,255,255,.72);line-height:1.65}

/* Grid — NGO hero à esquerda, Gestão+Profissional empilhadas à direita */
.seg-grid{display:grid;grid-template-columns:1.65fr 1fr;gap:0;max-width:1240px;margin:0 auto;position:relative;z-index:1;border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden}

/* Card base */
.seg-card{background:#111;padding:40px 36px 36px;text-decoration:none;color:#fff;display:flex;flex-direction:column;gap:0;position:relative;transition:background .25s}
.seg-card:hover{background:#161616}

/* Accent top bar */
.seg-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;transition:opacity .25s}
.seg-ngo::before{background:linear-gradient(90deg,#ef4444,#f97316);height:3px}
.seg-mgr::before{background:#4F6EF7}
.seg-ppl::before{background:#8B5CF6}

/* NGO hero card — ocupa as 2 linhas da coluna esquerda */
.seg-ngo{grid-row:1/3;border-right:1px solid rgba(255,255,255,.09);padding:52px 48px 48px;background:linear-gradient(160deg,#141010 0%,#111 55%)}
.seg-ngo:hover{background:linear-gradient(160deg,#1a1010 0%,#161616 55%)}

/* Cartão principal badge */
.seg-principal-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.28);color:#ef4444;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;padding:5px 12px;border-radius:100px;margin-bottom:28px}
.seg-principal-badge i{font-size:.6rem}

/* Number watermark */
.seg-num{font-size:4.5rem;font-weight:900;letter-spacing:-.05em;color:rgba(255,255,255,.05);line-height:1;margin-bottom:20px;font-variant-numeric:tabular-nums}
.seg-ngo .seg-num{font-size:7rem;color:rgba(239,68,68,.06)}

/* Icon row */
.seg-icon-row{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.seg-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.seg-ngo .seg-icon{width:56px;height:56px;border-radius:16px;font-size:1.3rem}
.si-rose{background:rgba(239,68,68,.12);color:#ef4444}
.si-blue{background:rgba(79,110,247,.14);color:#6B8BFF}
.si-purple{background:rgba(139,92,246,.14);color:#a78bfa}
.seg-badge{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:3px 8px;border-radius:100px}
.sb-rose{background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2)}
.sb-blue{background:rgba(79,110,247,.12);color:#6B8BFF;border:1px solid rgba(79,110,247,.22)}
.sb-purple{background:rgba(139,92,246,.12);color:#a78bfa;border:1px solid rgba(139,92,246,.22)}

/* Títulos */
.seg-card h3{font-size:1.2rem;font-weight:800;letter-spacing:-.02em;color:#fff;margin-bottom:10px;line-height:1.2}
.seg-ngo h3{font-size:1.75rem;letter-spacing:-.03em;margin-bottom:14px}
.seg-card p{font-size:.82rem;color:rgba(255,255,255,.78);line-height:1.7;margin-bottom:22px}
.seg-ngo p{font-size:.9rem;color:rgba(255,255,255,.82);line-height:1.75;margin-bottom:26px}

/* Feature chips */
.seg-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:28px}
.seg-chip{font-size:.65rem;font-weight:600;color:rgba(255,255,255,.4);background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:6px;padding:3px 9px}
.seg-ngo .seg-chip{color:rgba(239,68,68,.8);background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.15);font-size:.68rem;padding:4px 11px}

/* Lista de funcionalidades no hero */
.seg-feat-list{display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;margin-bottom:28px}
.seg-feat-item{display:flex;align-items:center;gap:8px;font-size:.78rem;color:rgba(255,255,255,.82)}
.seg-feat-item i{color:#ef4444;font-size:.6rem;flex-shrink:0}

/* Stat */
.seg-stat{margin-bottom:28px;padding-top:20px;border-top:1px solid rgba(255,255,255,.06)}
.seg-stat-val{font-size:1.5rem;font-weight:900;letter-spacing:-.03em;color:#fff;line-height:1}
.seg-stat-lbl{font-size:.7rem;color:rgba(255,255,255,.65);font-weight:500;margin-top:3px}
.seg-ngo .seg-stat{display:flex;gap:32px;align-items:flex-start}
.seg-ngo .seg-stat-val{font-size:2rem}

/* Divisor entre Gestão e Profissional */
.seg-mgr{border-bottom:1px solid rgba(255,255,255,.07)}
.seg-ppl{}
.seg-compact{padding:32px 30px 28px}
.seg-compact .seg-num{font-size:3rem;margin-bottom:14px}
.seg-compact h3{font-size:1.05rem}
.seg-compact p{font-size:.79rem;margin-bottom:16px}
.seg-compact .seg-chips{gap:4px;margin-bottom:18px}
.seg-compact .seg-chip{font-size:.6rem;padding:2px 7px}
.seg-compact .seg-stat{padding-top:14px;margin-bottom:18px}
.seg-compact .seg-stat-val{font-size:1.25rem}

/* CTA link */
.seg-link{display:inline-flex;align-items:center;gap:8px;font-size:.8rem;font-weight:700;text-decoration:none;margin-top:auto;transition:gap .2s}
.seg-link:hover{gap:12px}
.sl-rose{color:#ef4444}.sl-blue{color:#6B8BFF}.sl-purple{color:#a78bfa}
.seg-link-arrow{display:flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;font-size:.6rem;transition:transform .2s}
.sla-rose{background:rgba(239,68,68,.12)}.sla-blue{background:rgba(79,110,247,.14)}.sla-purple{background:rgba(139,92,246,.14)}
.seg-card:hover .seg-link-arrow{transform:translateX(3px)}

/* Responsive */
@media(max-width:900px){
    .seg-grid{grid-template-columns:1fr;border-radius:16px}
    .seg-ngo{grid-row:auto;border-right:none;border-bottom:1px solid rgba(255,255,255,.07);padding:40px 28px 36px}
    .seg-mgr,.seg-ppl{border-right:none}
    .seg-mgr{border-bottom:1px solid rgba(255,255,255,.07)}
    .seg-feat-list{grid-template-columns:1fr}
    .seg-ngo .seg-stat{gap:20px}
}
@media(max-width:600px){.seg-header{flex-direction:column;align-items:flex-start}.seg-hdr-right{max-width:100%}.seg-compact{padding:28px 22px 24px}}

/* ══ FEATURES BENTO ══════════════════════════════════════════════ */
.features{padding:120px 6%;background:#090909;position:relative;overflow:hidden}
.features::before{content:'';position:absolute;width:700px;height:700px;bottom:-200px;right:-150px;border-radius:50%;background:radial-gradient(circle,rgba(79,110,247,.07) 0%,transparent 70%);filter:blur(60px);pointer-events:none}
.feat-inner{max-width:1240px;margin:0 auto}

/* Header — left aligned, horizontal layout */
.feat-header{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:56px;gap:40px;flex-wrap:wrap}
.feat-header-left{}
.feat-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#4ade80;margin-bottom:14px}
.feat-ey-dot{width:5px;height:5px;border-radius:50%;background:#4ade80;animation:pulseDot 2s infinite}
.feat-title{font-size:clamp(2rem,3.5vw,2.8rem);font-weight:900;letter-spacing:-.035em;line-height:1.08;color:#fff}
.feat-title .ft-dim{color:rgba(255,255,255,.22)}
.feat-title .ft-grad{color:#6B8BFF}
.feat-header-right{flex-shrink:0;text-align:right}
.fhr-val{font-size:2.2rem;font-weight:900;letter-spacing:-.04em;color:#fff;line-height:1}
.fhr-label{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:4px;font-weight:500}

/* ─ BENTO GRID ─ */
.bento-grid{display:grid;grid-template-columns:repeat(3,1fr);grid-auto-rows:auto;gap:10px}

/* Base card */
.bc{background:#111;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;position:relative;transition:border-color .3s,transform .3s}
.bc:hover{border-color:rgba(255,255,255,.15)}
.bc-2w{grid-column:span 2}
.bc-2h{grid-row:span 2}

/* Card body top */
.bc-body{padding:18px 18px 10px;position:relative;z-index:2}
.bc-tag{display:inline-flex;align-items:center;gap:5px;font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:2px 8px;border-radius:100px;margin-bottom:8px}
.bct-ai{background:rgba(167,139,250,.15);color:#a78bfa}
.bct-fin{background:rgba(79,110,247,.15);color:#93a8ff}
.bct-social{background:rgba(74,222,128,.14);color:#4ade80}
.bct-ops{background:rgba(251,191,36,.12);color:#fbbf24}
.bct-crm{background:rgba(249,115,22,.14);color:#fb923c}
.bct-transp{background:rgba(34,211,238,.12);color:#22d3ee}
.bc-title{font-size:.92rem;font-weight:800;color:#fff;letter-spacing:-.02em;margin-bottom:4px}
.bc-desc{font-size:.72rem;color:rgba(255,255,255,.78);line-height:1.5;max-width:320px}

/* ── BC-3W (Full Width) ────────────────────────────────────────── */
.bc-3w{grid-column:span 3}

/* ── BC-MASTER-BOT: Mensageria (3x) ──────────────────────────── */
.bc-master-bot{background:linear-gradient(135deg, #051A14 0%, #03080A 100%); border-color:rgba(37,211,102,.3); padding:0; display:flex; align-items:center; overflow:hidden;}
.master-glow{position:absolute; top:-50%; left:-20%; width:600px; height:600px; background:radial-gradient(circle, rgba(37,211,102,.12) 0%, transparent 65%); filter:blur(60px); pointer-events:none; animation:slowPulse 8s ease-in-out infinite alternate;}
@keyframes slowPulse{from{opacity:0.5; transform:scale(0.9);} to{opacity:1; transform:scale(1.1);}}
.master-text{flex:1; padding:40px; position:relative; z-index:2;}
.master-text .bc-title{font-size:2rem; font-weight:900; margin-bottom:12px; letter-spacing:-0.03em;}
.master-text .bc-desc{font-size:0.85rem; color:rgba(255,255,255,.6); line-height:1.6; max-width:400px; margin-bottom:20px;}
.master-features{display:flex; gap:15px; margin-top:20px;}
.mf-item{display:flex; align-items:center; gap:8px; font-size:0.7rem; font-weight:700; color:#fff; background:rgba(255,255,255,0.05); padding:6px 12px; border-radius:100px; border:1px solid rgba(255,255,255,0.1);}

.master-visual{flex:1.2; position:relative; height:100%; min-height:300px; display:flex; align-items:center; justify-content:center;}
.glass-phone{width:280px; height:260px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.08); box-shadow:0 30px 60px -15px rgba(0,0,0,0.8), inset 0 1px 0 rgba(255,255,255,0.15); border-radius:24px; backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); padding:16px; display:flex; flex-direction:column; gap:14px; position:relative; overflow:hidden; z-index:2;}
.glass-phone::before{content:''; position:absolute; top:0; left:0; width:100%; height:50px; background:linear-gradient(180deg, rgba(37,211,102,0.15), transparent); pointer-events:none;}
.gp-hdr{display:flex; align-items:center; gap:10px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:10px;}
.gp-av{width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg, #25D366, #128C7E); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; box-shadow:0 0 15px rgba(37,211,102,0.4);}
.gp-info{display:flex; flex-direction:column;}
.gp-name{font-size:0.8rem; font-weight:800; color:#fff;}
.gp-status{font-size:0.6rem; color:#4ade80; font-weight:600;}
.gp-msg{font-size:0.7rem; padding:10px 14px; border-radius:12px; max-width:85%; line-height:1.4; position:relative; z-index:2;}
.gp-in{background:rgba(255,255,255,0.08); color:#e9edef; align-self:flex-start; border-top-left-radius:2px;}
.gp-out{background:linear-gradient(135deg, #005c4b, #007a64); color:#e9edef; align-self:flex-end; border-top-right-radius:2px; box-shadow:0 4px 15px rgba(0,92,75,0.3);}

.float-icon{position:absolute; background:rgba(20,30,25,0.8); border:1px solid rgba(255,255,255,0.1); border-radius:12px; backdrop-filter:blur(10px); padding:8px 12px; display:flex; align-items:center; gap:8px; font-size:0.65rem; font-weight:700; color:#fff; box-shadow:0 10px 20px rgba(0,0,0,0.4); z-index:3;}
.fi-1{top:40px; left:40px; animation:floatAnim 4s ease-in-out infinite;}
.fi-2{bottom:50px; right:40px; animation:floatAnim 5s ease-in-out infinite 1s;}
@keyframes floatAnim{0%,100%{transform:translateY(0);} 50%{transform:translateY(-12px);}}

/* ── BC-AI-RADAR: Bruce AI (2w) ──────────────────────────────── */
.bc-ai-radar{background:linear-gradient(145deg, #0f0b20, #080512); border-color:rgba(139,92,246,0.2);}
.radar-inner{display:flex; padding:18px 24px; gap:20px; align-items:center; height:100%;}
.radar-text{flex:1;}
.radar-visual{flex:1; position:relative; height:140px; background:radial-gradient(circle, rgba(139,92,246,0.1) 0%, transparent 60%); border-radius:50%; display:flex; align-items:center; justify-content:center; border:1px dashed rgba(139,92,246,0.3); overflow:hidden;}
.radar-sweep{position:absolute; top:50%; left:50%; width:50%; height:50%; background:linear-gradient(45deg, rgba(139,92,246,0.6), transparent); transform-origin:0 0; animation:radarSpin 3s linear infinite;}
@keyframes radarSpin{100%{transform:rotate(360deg);}}
.radar-target{position:absolute; width:45px; height:45px; border-radius:50%; background:#1a1a2e; border:2px solid #8b5cf6; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.85rem; font-weight:900; box-shadow:0 0 20px rgba(139,92,246,0.6); z-index:2;}

/* ── BC-CRM: Kanban ──────────────────────────────────────────── */
.bc-crm{background:linear-gradient(145deg, #120905, #1a0f0a); border-color:rgba(249,115,22,0.2);}
.kanban-demo{margin:2px 14px 14px;display:flex;gap:6px}
.kd-col{flex:1}
.kd-hdr{font-size:.54rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.65);margin-bottom:5px;display:flex;align-items:center;gap:4px}
.kdd{width:4px;height:4px;border-radius:50%}
.kdd-o{background:#fb923c}.kdd-b{background:#93a8ff}.kdd-g{background:#4ade80}
.kd-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:6px;padding:5px 7px;margin-bottom:4px;font-size:.58rem;color:rgba(255,255,255,.7);font-weight:600;line-height:1.3; box-shadow:0 2px 5px rgba(0,0,0,0.2);}
.kd-card-val{font-size:.65rem;font-weight:800;color:#fff;margin-top:2px}
.kd-card-hot{border-color:rgba(249,115,22,.4);background:rgba(249,115,22,.1);animation:hotPulse 3s ease-in-out infinite}
@keyframes hotPulse{0%,100%{border-color:rgba(249,115,22,.3); box-shadow:0 0 10px rgba(249,115,22,0.2);}50%{border-color:rgba(249,115,22,.8); box-shadow:0 0 20px rgba(249,115,22,0.5);}}

/* ── BC-CANVA: LPs & Artes ────────────────────────────────────── */
.bc-canva{background:linear-gradient(145deg, #181016, #20131b); border-color:rgba(236,72,153,0.2);}
.canva-demo{margin:2px 14px 14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:8px; padding:10px; display:flex; flex-direction:column; gap:6px; box-shadow:inset 0 0 20px rgba(0,0,0,0.5);}
.cv-toolbar{display:flex; gap:5px; margin-bottom:5px;}
.cvt{width:12px; height:12px; border-radius:3px; background:rgba(255,255,255,0.15);}
.cv-canvas{height:65px; border-radius:6px; background:linear-gradient(135deg, #a855f7, #ec4899); position:relative; overflow:hidden; box-shadow:0 5px 15px rgba(236,72,153,0.3);}
.cv-elem{position:absolute; background:rgba(255,255,255,0.9); border-radius:4px; width:40%; height:12px; top:15px; left:15px; box-shadow:0 2px 10px rgba(0,0,0,0.2);}
.cv-elem2{position:absolute; background:rgba(255,255,255,0.4); border-radius:3px; width:30%; height:6px; top:35px; left:15px;}

/* ── BC-TRANSP: Transparência (2w) ──────────────────────────── */
.bc-transp{background:linear-gradient(145deg, #08111a, #0a1724); border-color:rgba(34,211,238,0.2);}
.transp-inner{margin:2px 16px 16px;display:flex;gap:20px;align-items:flex-start}
.transp-counts{flex-shrink:0;display:flex;flex-direction:column;gap:10px}
.tc-block .tc-lbl{font-size:.56rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.65);margin-bottom:3px}
.tc-block .tc-num{font-size:1.4rem;font-weight:900;letter-spacing:-.04em;color:#fff;line-height:1; text-shadow:0 0 15px rgba(34,211,238,0.3);}
.tc-block .tc-delta{font-size:.6rem;color:#4ade80;font-weight:700;margin-top:1px}
.transp-feed{flex:1}
.tf-hdr{font-size:.56rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.65);margin-bottom:6px}
.tf-item{display:flex;align-items:center;gap:6px;padding:5px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.62rem;color:rgba(255,255,255,.6)}
.tf-item:last-child{border:none}
.tfd{width:5px;height:5px;border-radius:50%;flex-shrink:0; box-shadow:0 0 8px currentColor;}
.tfd-g{background:#4ade80; color:#4ade80;} .tfd-b{background:#22d3ee; color:#22d3ee;} .tfd-o{background:#fb923c; color:#fb923c;}

/* ── BC-VOLUNT: Voluntários ──────────────────────────────────── */
.bc-volunt{background:linear-gradient(145deg, #0e0c10, #141117); border-color:rgba(251,191,36,0.2);}
.podium-list{margin:2px 14px 14px;display:flex;flex-direction:column;gap:4px}
.pl-item{display:flex;align-items:center;gap:6px;padding:5px 8px;border-radius:7px;background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,0.03);}
.pl-pos{font-size:.68rem;font-weight:900;width:14px;text-align:center;flex-shrink:0}
.pl-p1{color:#f59e0b; text-shadow:0 0 10px rgba(245,158,11,0.5);} .pl-p2{color:rgba(255,255,255,.82);} .pl-p3{color:#fb923c;}
.pl-av{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.52rem;font-weight:900;color:#fff;flex-shrink:0; box-shadow:0 2px 5px rgba(0,0,0,0.5);}
.plav1{background:linear-gradient(135deg,#f59e0b,#ef4444)}
.plav2{background:linear-gradient(135deg,#6b7280,#9ca3af)}
.plav3{background:linear-gradient(135deg,#fb923c,#f59e0b)}
.pl-name{flex:1;font-size:.66rem;color:rgba(255,255,255,.8);font-weight:600}
.pl-pts{font-size:.66rem;font-weight:800;color:rgba(255,255,255,.4)}
.pl-pts-1{color:#f59e0b}
.pl-bar{flex:1;max-width:50px;height:4px;background:rgba(255,255,255,.06);border-radius:100px;overflow:hidden;}
.plb-fill{height:100%;border-radius:100px;background:linear-gradient(90deg,#f59e0b,#fb923c); box-shadow:0 0 8px rgba(245,158,11,0.5);}

/* ── BC-CONTRACTS: Contratos ─────────────────────────────────── */
.bc-contracts{background:linear-gradient(145deg, #111411, #161a16); border-color:rgba(74,222,128,0.2);}
.ctr-demo{margin:2px 14px 14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:8px; padding:12px; display:flex; flex-direction:column; gap:7px; position:relative; overflow:hidden;}
.ctr-line{height:4px; background:rgba(255,255,255,.15); border-radius:2px; width:100%;}
.ctr-line.w-70{width:70%;}
.ctr-line.w-40{width:40%;}
.ctr-sign{margin-top:6px; display:flex; justify-content:space-between; align-items:flex-end;}
.ctr-badge{font-size:.5rem; font-weight:800; background:rgba(74,222,128,.2); color:#4ade80; padding:4px 10px; border-radius:100px; border:1px solid rgba(74,222,128,.4); box-shadow:0 0 15px rgba(74,222,128,0.2);}

/* ── BC-FIN: Prestação de Contas ─────────────────────────────── */
.bc-fin{background:linear-gradient(145deg, #0a0f1c, #0e1526); border-color:rgba(79,110,247,0.2);}
.fin-demo{margin:2px 14px 14px}
.fin-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.66rem}
.fin-row:last-of-type{border-bottom:none}
.fin-lbl{color:rgba(255,255,255,.82)}
.fin-val{font-weight:700;color:#fff}
.fin-pos{color:#4ade80; text-shadow:0 0 8px rgba(74,222,128,0.3);}.fin-neg{color:#f87171}
.fin-total{margin-top:8px;padding:8px 11px;border-radius:8px;background:rgba(79,110,247,.15);border:1px solid rgba(79,110,247,.3);display:flex;justify-content:space-between;align-items:center; box-shadow:0 5px 15px rgba(0,0,0,0.2);}
.fin-total-lbl{font-size:.64rem;font-weight:700;color:rgba(255,255,255,.6)}
.fin-total-val{font-size:.95rem;font-weight:900;color:#fff; text-shadow:0 0 10px rgba(79,110,247,0.4);}
.fin-badge{font-size:.54rem;background:rgba(74,222,128,.2);color:#4ade80;padding:2px 6px;border-radius:100px;font-weight:800;margin-top:2px}

/* ── BC-ALMOX: Almoxarifado ──────────────────────────────────── */
.bc-almox{background:linear-gradient(145deg, #0d1010, #131818); border-color:rgba(34,211,238,0.2);}
.almox-bars{margin:2px 14px 14px;display:flex;flex-direction:column;gap:8px}
.ab-row{}
.ab-hdr{display:flex;justify-content:space-between;margin-bottom:4px}
.ab-name{font-size:.66rem;color:rgba(255,255,255,.6);font-weight:600}
.ab-qty{font-size:.62rem;color:rgba(255,255,255,.65);font-weight:600}
.ab-track{height:5px;background:rgba(255,255,255,.08);border-radius:100px;overflow:hidden}
.ab-fill{height:100%;border-radius:100px}
.abf1{background:linear-gradient(90deg,#4F6EF7,#7B5CF0);animation:abAnim1 2.8s ease-in-out infinite alternate; box-shadow:0 0 10px rgba(79,110,247,0.4);}
.abf2{background:linear-gradient(90deg,#4ade80,#22d3ee);animation:abAnim2 3.1s ease-in-out infinite alternate; box-shadow:0 0 10px rgba(34,211,238,0.4);}
.abf3{background:linear-gradient(90deg,#fb923c,#f59e0b);animation:abAnim3 3.4s ease-in-out infinite alternate; box-shadow:0 0 10px rgba(245,158,11,0.4);}
@keyframes abAnim1{from{width:42%}to{width:78%}}
@keyframes abAnim2{from{width:65%}to{width:93%}}
@keyframes abAnim3{from{width:28%}to{width:52%}}

/* ── FEATURE HUB ─────────────────────────────────────────────── */
.hub-grid { display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; margin-top: 40px; }
.hub-col { display:flex; flex-direction:column; gap:16px; }
.hub-col-title { font-size: 0.8rem; font-weight: 800; color:#fff; text-transform: uppercase; letter-spacing: 0.08em; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.06); margin-bottom:10px; display:flex; align-items:center; gap:8px;}
.hc-blue { color: #6B8BFF; border-bottom-color: rgba(107,139,255,0.2); }
.hc-green { color: #4ade80; border-bottom-color: rgba(74,222,128,0.2); }
.hc-purple { color: #a78bfa; border-bottom-color: rgba(167,139,250,0.2); }
.hc-pink { color: #ec4899; border-bottom-color: rgba(236,72,153,0.2); }
.hub-card { background: rgba(20,20,20,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; display:flex; flex-direction:column; gap:4px; }
.hub-card:hover { transform: translateY(-3px); background: rgba(30,30,30,0.8); border-color: rgba(255,255,255,0.15); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.hub-card-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 12px; }
.hub-card-title { font-size: 0.82rem; font-weight: 800; color: #fff; line-height: 1.3; }
.hub-card-desc { font-size: 0.72rem; color: rgba(255,255,255,0.45); line-height: 1.5; }

/* Variations */
.hi-blue { background: linear-gradient(135deg, rgba(79,110,247,.15), rgba(79,110,247,.05)); color: #6B8BFF; border: 1px solid rgba(79,110,247,.2); box-shadow: inset 0 0 10px rgba(79,110,247,.1); }
.hi-green { background: linear-gradient(135deg, rgba(74,222,128,.15), rgba(74,222,128,.05)); color: #4ade80; border: 1px solid rgba(74,222,128,.2); box-shadow: inset 0 0 10px rgba(74,222,128,.1); }
.hi-purple { background: linear-gradient(135deg, rgba(167,139,250,.15), rgba(167,139,250,.05)); color: #a78bfa; border: 1px solid rgba(167,139,250,.2); box-shadow: inset 0 0 10px rgba(167,139,250,.1); }
.hi-pink { background: linear-gradient(135deg, rgba(236,72,153,.15), rgba(236,72,153,.05)); color: #ec4899; border: 1px solid rgba(236,72,153,.2); box-shadow: inset 0 0 10px rgba(236,72,153,.1); }
.hi-orange { background: linear-gradient(135deg, rgba(249,115,22,.15), rgba(249,115,22,.05)); color: #fb923c; border: 1px solid rgba(249,115,22,.2); box-shadow: inset 0 0 10px rgba(249,115,22,.1); }
.hi-teal { background: linear-gradient(135deg, rgba(34,211,238,.15), rgba(34,211,238,.05)); color: #22d3ee; border: 1px solid rgba(34,211,238,.2); box-shadow: inset 0 0 10px rgba(34,211,238,.1); }
.hi-red { background: linear-gradient(135deg, rgba(239,68,68,.15), rgba(239,68,68,.05)); color: #ef4444; border: 1px solid rgba(239,68,68,.2); box-shadow: inset 0 0 10px rgba(239,68,68,.1); }

@media(max-width: 1024px) { .hub-grid { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 640px) { .hub-grid { grid-template-columns: 1fr; } }

/* ─── IMPACT ─── */
.impact{padding:88px 6%;background:#090909;position:relative;border-top:1px solid rgba(255,255,255,.05)}
.impact::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:72px 72px;pointer-events:none}
.impact-inner{max-width:1240px;margin:0 auto;position:relative;z-index:1}
.impact-head{display:flex;align-items:flex-end;justify-content:space-between;gap:40px;flex-wrap:wrap;margin-bottom:48px}
.impact-eyebrow{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:rgba(255,255,255,.65);margin-bottom:12px}
.impact-title{font-size:clamp(2.2rem,4vw,3rem);font-weight:900;letter-spacing:-.04em;line-height:1.04;color:#fff}
.impact-title em{font-style:normal;color:rgba(255,255,255,.22)}
.impact-right-txt{max-width:340px;font-size:.84rem;color:rgba(255,255,255,.32);line-height:1.7}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden}
.stat-block{padding:32px 28px;border-right:1px solid rgba(255,255,255,.07);position:relative;background:#111}
.stat-block:last-child{border-right:none}
.stat-block::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.sb-1::before{background:#4F6EF7}
.sb-2::before{background:#4ade80}
.sb-3::before{background:#ef4444}
.sb-4::before{background:#f59e0b}
.stat-num{font-size:clamp(1.8rem,3vw,2.6rem);font-weight:900;letter-spacing:-.04em;color:#fff;line-height:1;margin-bottom:6px}
.stat-lbl{font-size:.64rem;color:rgba(255,255,255,.65);font-weight:600;text-transform:uppercase;letter-spacing:.06em}
.stat-delta{margin-top:8px;font-size:.62rem;font-weight:700;color:#4ade80;display:flex;align-items:center;gap:4px}
@media(max-width:860px){.stats-row{grid-template-columns:1fr 1fr}.stat-block:nth-child(2){border-right:none}.stat-block:nth-child(3){border-right:1px solid rgba(255,255,255,.07)}.stat-block:nth-child(3),.stat-block:nth-child(4){border-top:1px solid rgba(255,255,255,.07)}}
@media(max-width:480px){.stats-row{grid-template-columns:1fr}.stat-block{border-right:none!important;border-top:1px solid rgba(255,255,255,.07)}.stat-block:first-child{border-top:none}}

/* ─── PRICING ─── */
.pricing{padding:100px 6%;background:#090909;position:relative;border-top:1px solid rgba(255,255,255,.05)}
.pricing::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:72px 72px;pointer-events:none}
.pricing-inner{max-width:1240px;margin:0 auto;position:relative;z-index:1}
.billing-toggle{display:flex;align-items:center;justify-content:center;gap:14px;margin:32px 0 52px}
.tgl-label{font-size:.86rem;font-weight:600;color:rgba(255,255,255,.72);transition:color .3s}
.tgl-label.on{color:white}
.switch{position:relative;display:inline-block;width:46px;height:24px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:rgba(255,255,255,.12);border-radius:24px;transition:.3s}
.slider:before{content:'';position:absolute;height:16px;width:16px;left:4px;bottom:4px;background:white;border-radius:50%;transition:.3s}
input:checked+.slider{background:#4F6EF7}
input:checked+.slider:before{transform:translateX(22px)}
.disc-badge{background:rgba(74,222,128,.12);color:#4ade80;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:100px}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2px;max-width:1000px;margin:0 auto;border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden}
.price-card{background:#111;padding:36px;position:relative;transition:background .25s;border-right:1px solid rgba(255,255,255,.07)}
.price-card:last-child{border-right:none}
.price-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:transparent}
.price-card.hot{background:#131820}
.price-card.hot::before{background:#4F6EF7}
.price-card:hover{background:#141414}
.hot-label{position:absolute;top:18px;right:18px;background:#4F6EF7;color:white;font-size:.62rem;font-weight:800;padding:3px 10px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em}
.price-name{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.65);margin-bottom:16px}
.price-val{font-size:2.6rem;font-weight:900;line-height:1;letter-spacing:-.04em;color:#fff}
.price-val .cur{font-size:1rem;font-weight:700;vertical-align:top;margin-top:6px;display:inline-block;color:rgba(255,255,255,.4)}
.price-val .per{font-size:.86rem;font-weight:400;color:rgba(255,255,255,.65)}
.price-note{font-size:.7rem;color:rgba(255,255,255,.22);margin-top:4px;margin-bottom:24px}
.price-divider{height:1px;background:rgba(255,255,255,.07);margin:18px 0}
.price-feats{list-style:none;display:flex;flex-direction:column;gap:9px;margin-bottom:28px}
.price-feats li{display:flex;align-items:center;gap:9px;font-size:.8rem;color:rgba(255,255,255,.58)}
.price-feats li i{color:#4ade80;font-size:.76rem;flex-shrink:0}
.btn-price{display:block;text-align:center;padding:13px;border-radius:10px;font-weight:700;font-size:.86rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer;width:100%}
.btp-main{background:#ffffff;color:#0a0a0a}
.btp-main:hover{background:#e8e8e8;transform:translateY(-1px)}
.btp-out{border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.6);background:none}
.btp-out:hover{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.22);color:white}

/* ─── CTA ─── */
.cta-section{padding:120px 6%;background:#090909;position:relative;border-top:1px solid rgba(255,255,255,.06)}
.cta-section::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:72px 72px;pointer-events:none}
.cta-inner{max-width:1240px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;position:relative;z-index:1}
.cta-big{font-size:clamp(4.5rem,9vw,8rem);font-weight:900;letter-spacing:-.055em;line-height:.9;color:#fff;margin-bottom:28px}
.cta-big em{font-style:normal;color:rgba(255,255,255,.15)}
.cta-tagline{font-size:.95rem;color:rgba(255,255,255,.4);line-height:1.75;max-width:420px;margin-bottom:36px}
.cta-divider{width:100%;height:1px;background:rgba(255,255,255,.07);margin:32px 0}
.cta-benefits{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.cb-icon{width:30px;height:30px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:.74rem;color:rgba(255,255,255,.8);margin-bottom:9px}
.cb-title{font-size:.8rem;font-weight:700;color:rgba(255,255,255,.8);margin-bottom:3px}
.cb-desc{font-size:.7rem;color:rgba(255,255,255,.65);line-height:1.55}
.cta-right{background:#ffffff;border-radius:20px;padding:44px}
.cta-form-title{font-size:1.5rem;font-weight:900;letter-spacing:-.03em;margin-bottom:5px;color:#0a0a0a;line-height:1.1}
.cta-form-title span{color:#4F6EF7}
.cta-form-sub{font-size:.76rem;color:rgba(0,0,0,.38);margin-bottom:24px;line-height:1.5}
.cta-proof{display:flex;align-items:center;gap:10px;padding:11px 14px;background:#f5f5f5;border-radius:10px;margin-bottom:22px}
.cta-proof-av{display:flex}
.cp-av{width:24px;height:24px;border-radius:50%;border:2px solid #fff;margin-left:-7px;background:#4F6EF7;display:flex;align-items:center;justify-content:center;font-size:.48rem;font-weight:900;color:#fff;flex-shrink:0}
.cp-av:first-child{margin-left:0}
.cp-av.cp2{background:#7B5CF0}.cp-av.cp3{background:#4ade80;color:#0a0a0a}.cp-av.cp4{background:#f59e0b;color:#0a0a0a}
.cta-proof-text{font-size:.68rem;color:rgba(0,0,0,.45);line-height:1.4}
.cta-proof-text strong{color:rgba(0,0,0,.78);font-weight:700}
.btn-cta-main{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;background:#0a0a0a;color:#fff;border:none;border-radius:10px;font-size:.9rem;font-weight:700;font-family:'Inter',sans-serif;text-decoration:none;cursor:pointer;transition:all .2s;margin-bottom:10px}
.btn-cta-main:hover{background:#222;transform:translateY(-1px);color:#fff}
.btn-cta-sec{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;background:#f0f0f0;color:#0a0a0a;border:none;border-radius:10px;font-size:.86rem;font-weight:600;font-family:'Inter',sans-serif;text-decoration:none;cursor:pointer;transition:all .2s}
.btn-cta-sec:hover{background:#e6e6e6;color:#0a0a0a}
.cta-form-note{margin-top:14px;font-size:.62rem;color:rgba(0,0,0,.28);text-align:center;line-height:1.6}
.cta-form-note a{color:#4F6EF7;text-decoration:none}
@media(max-width:900px){.cta-inner{grid-template-columns:1fr;gap:56px}.cta-big{font-size:clamp(3.8rem,12vw,5.5rem)}.cta-right{padding:32px}}

/* ─── FOOTER ─── */
footer{background:#060606;border-top:1px solid rgba(255,255,255,.06);padding:64px 6% 28px}
.footer-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:48px}
.footer-brand-desc{font-size:.78rem;color:rgba(255,255,255,.22);line-height:1.75;margin-top:12px;max-width:220px}
.footer-col h5{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.2);margin-bottom:16px}
.footer-col a{display:block;color:rgba(255,255,255,.78);text-decoration:none;font-size:.8rem;margin-bottom:9px;transition:color .2s}
.footer-col a:hover{color:rgba(255,255,255,.8)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.05);padding-top:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.footer-bottom span{font-size:.7rem;color:rgba(255,255,255,.18)}
.footer-bottom .fb-heart{color:rgba(239,68,68,.6)}

/* ─── SECTION HEADER ─── */
.section-tag{display:inline-flex;align-items:center;gap:7px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:14px}
.st-blue{color:var(--blue2)}.st-teal{color:var(--teal)}.st-rose{color:#FF7080}
.section-title{font-size:clamp(2rem,4vw,2.8rem);font-weight:900;line-height:1.1;letter-spacing:-.025em;margin-bottom:16px}
.section-sub{font-size:1rem;color:rgba(255,255,255,.82);line-height:1.7;max-width:540px}
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

/* ─── VIDEO PLAYER ─── */
.video-player-wrap{display:flex;align-items:center;gap:16px;justify-content:center;margin-bottom:60px}
.video-play-btn{position:relative;width:62px;height:62px;border:none;background:none;cursor:pointer;flex-shrink:0}
.play-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(91,130,255,.5);animation:videoRing 2.2s ease-out infinite}
.play-ring-2{animation-delay:.9s}
.play-icon-circle{position:absolute;inset:6px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--violet));display:flex;align-items:center;justify-content:center;color:white;box-shadow:0 6px 24px rgba(59,108,246,.5);transition:transform .2s}
.video-play-btn:hover .play-icon-circle{transform:scale(1.08)}
.video-hint{color:rgba(255,255,255,.55);font-size:.9rem;line-height:1.4}
.video-hint strong{color:white}
@keyframes videoRing{0%{transform:scale(1);opacity:.7}80%{transform:scale(2);opacity:0}100%{opacity:0}}
/* Modal */
.video-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.88);backdrop-filter:blur(12px);align-items:center;justify-content:center}
.video-modal.open{display:flex}
.video-modal-inner{position:relative;width:90%;max-width:900px;aspect-ratio:16/9;border-radius:16px;overflow:hidden;box-shadow:0 40px 120px rgba(0,0,0,.8)}
.video-modal-close{position:absolute;top:-40px;right:0;background:none;border:none;color:rgba(255,255,255,.7);font-size:1.6rem;cursor:pointer;z-index:2;transition:color .2s}
.video-modal-close:hover{color:white}

/* ─── ACADEMY UNIVERSE ─── */
.academy-universe{position:relative;padding:120px 0;overflow:hidden;background:linear-gradient(165deg,#06040F 0%,#0D0824 40%,#0A1628 100%);border-top:1px solid rgba(124,58,237,.2);border-bottom:1px solid rgba(59,108,246,.15)}
/* Cosmos bg */
.au-cosmos{position:absolute;inset:0;pointer-events:none}
.au-star{position:absolute;width:3px;height:3px;border-radius:50%;background:white;animation:auStar 3s ease-in-out infinite alternate}
.au-star-lg{width:5px;height:5px;opacity:.5}
@keyframes auStar{0%{opacity:.1;transform:scale(1)}100%{opacity:.8;transform:scale(1.5)}}
.au-orbit{position:absolute;border-radius:50%;border:1px solid rgba(124,58,237,.12)}
.au-orbit-1{width:600px;height:600px;top:50%;left:60%;transform:translate(-50%,-50%);animation:auOrbitSpin 30s linear infinite}
.au-orbit-2{width:900px;height:900px;top:50%;left:60%;transform:translate(-50%,-50%);animation:auOrbitSpin 50s linear infinite reverse}
@keyframes auOrbitSpin{from{transform:translate(-50%,-50%) rotate(0deg)}to{transform:translate(-50%,-50%) rotate(360deg)}}
.au-nebula{position:absolute;top:20%;left:40%;width:700px;height:700px;border-radius:50%;background:radial-gradient(ellipse at center,rgba(124,58,237,.15) 0%,rgba(59,108,246,.08) 40%,transparent 70%);filter:blur(60px);animation:auNebulaPulse 6s ease-in-out infinite alternate}
@keyframes auNebulaPulse{0%{opacity:.6;transform:scale(1)}100%{opacity:1;transform:scale(1.1)}}
/* Layout */
.au-container{position:relative;max-width:1200px;margin:0 auto;padding:0 6%;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center}
/* Left */
.au-badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,rgba(124,58,237,.25),rgba(59,108,246,.15));border:1px solid rgba(124,58,237,.4);color:#C4B5FD;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:7px 16px;border-radius:100px;margin-bottom:24px}
.au-badge-dot{width:7px;height:7px;border-radius:50%;background:#A78BFA;animation:auBadgePulse 1.8s ease-in-out infinite}
@keyframes auBadgePulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(167,139,250,.6)}50%{opacity:.7;box-shadow:0 0 0 6px rgba(167,139,250,0)}}
.au-title{font-size:clamp(3rem,6vw,5rem);font-weight:900;line-height:.95;letter-spacing:-.04em;margin-bottom:20px}
.au-title-white{color:white}
.au-title-grad{color:#a78bfa}
.au-sub{font-size:1.05rem;color:rgba(255,255,255,.55);line-height:1.75;max-width:440px;margin-bottom:32px}
.au-benefits{list-style:none;display:flex;flex-direction:column;gap:13px;margin-bottom:40px}
.au-benefits li{display:flex;align-items:center;gap:12px;font-size:.92rem;color:rgba(255,255,255,.75)}
.au-check{width:22px;height:22px;border-radius:50%;background:rgba(124,58,237,.25);border:1px solid rgba(124,58,237,.45);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.6rem;color:#A78BFA}
.au-ctas{display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.au-btn-primary{display:inline-flex;align-items:center;gap:10px;background:#7C3AED;color:white;text-decoration:none;font-weight:700;font-size:1rem;padding:15px 35px;border-radius:60px;box-shadow:0 8px 28px rgba(124,58,237,.35);transition:all .3s;white-space:nowrap}
.au-btn-primary:hover{transform:translateY(-2px);box-shadow:0 12px 36px rgba(124,58,237,.55);color:white;background:#6d2ed6}
.au-vagas{display:flex;flex-direction:column;gap:5px}
.au-vagas-dots{display:flex;gap:5px}
.au-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.15)}
.au-dot.active{background:#7C3AED}
.au-vagas span{font-size:.75rem;color:rgba(255,255,255,.4)}
/* Right — open editorial layout (sem wrapper box) */
.au-right{display:flex;flex-direction:column;gap:0}
/* Numbers row */
.au-nums{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-bottom:28px}
.au-num-item{padding:22px 20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px}
.au-num-item:nth-child(2){background:rgba(124,58,237,.12);border-color:rgba(124,58,237,.25)}
.au-num-val{font-size:2.2rem;font-weight:900;letter-spacing:-.05em;color:#fff;line-height:1;margin-bottom:5px}
.au-num-lbl{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:rgba(255,255,255,.65)}
/* Featured module card */
.au-module-card{border:1px solid rgba(255,255,255,.08);border-radius:16px;overflow:hidden;margin-bottom:20px}
.au-module-thumb{height:96px;background:rgba(124,58,237,.18);display:flex;align-items:center;justify-content:center;position:relative;border-bottom:1px solid rgba(255,255,255,.06)}
.au-module-thumb-icon{width:38px;height:38px;background:#7C3AED;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff}
.au-module-live{position:absolute;top:10px;left:12px;display:flex;align-items:center;gap:5px;background:rgba(0,0,0,.5);border:1px solid rgba(74,222,128,.3);border-radius:20px;padding:3px 9px}
.au-module-live-dot{width:5px;height:5px;border-radius:50%;background:#4ade80;animation:pulseDot 1.5s infinite}
.au-module-live-txt{font-size:.55rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#4ade80}
.au-module-body{padding:16px 18px}
.au-module-tag{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:rgba(167,139,250,.7);margin-bottom:5px}
.au-module-name{font-size:.9rem;font-weight:800;color:#fff;margin-bottom:12px;line-height:1.3}
.au-module-bar-wrap{display:flex;align-items:center;gap:10px}
.au-module-bar{flex:1;height:3px;background:rgba(255,255,255,.08);border-radius:100px;overflow:hidden}
.au-module-bar-fill{height:100%;width:62%;background:#a78bfa;border-radius:100px}
.au-module-pct{font-size:.62rem;font-weight:800;color:rgba(255,255,255,.72)}
/* Testimonial — open quote style */
.au-test-open{padding:20px 0 0}
.au-test-mark{font-size:3.5rem;font-weight:900;color:rgba(124,58,237,.45);line-height:.6;margin-bottom:10px}
.au-test-quote{font-size:.82rem;color:rgba(255,255,255,.82);line-height:1.7;margin-bottom:14px;font-style:italic}
.au-test-author{display:flex;align-items:center;gap:10px}
.au-test-av{width:30px;height:30px;border-radius:50%;background:rgba(124,58,237,.35);border:1px solid rgba(124,58,237,.45);display:flex;align-items:center;justify-content:center;font-size:.58rem;font-weight:900;color:#C4B5FD;flex-shrink:0}
.au-test-name{font-size:.74rem;font-weight:700;color:rgba(255,255,255,.8)}
.au-test-role{font-size:.62rem;color:rgba(255,255,255,.65);margin-top:1px}

/* ─── RESPONSIVE ─── */
@media(max-width:1024px){.bento-grid{grid-template-columns:repeat(2,1fr)}.bc-2w,.bc-2h{grid-column:span 2;grid-row:span 1}.impact-inner{grid-template-columns:1fr}.au-container{grid-template-columns:1fr;gap:60px}}
@media(max-width:768px){
    .seg-grid{grid-template-columns:1fr}
    .seg-ngo{grid-row:auto;border-right:none}
    .bento-grid{grid-template-columns:1fr}
    .bc-2w{grid-column:span 1}
    .counter-grid{grid-template-columns:1fr 1fr}
    .footer-row{grid-template-columns:1fr 1fr}
    .fc-c,.fc-d,.fc-e{display:none}
    .price-card.hot{transform:none}
    .price-card.hot:hover{transform:translateY(-4px)}
    .au-fb-1,.au-fb-2{display:none}
    .au-orbit-1,.au-orbit-2{display:none}
    .au-card-wrap{max-width:420px;margin:0 auto}
    .au-title{font-size:3rem}
}
@media(max-width:480px){
    .footer-row{grid-template-columns:1fr}
    .hero-title{font-size:2.4rem}
    .au-title{font-size:2.5rem}
    .au-ctas{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>

<!-- ANNOUNCE BAR -->
<div class="announce-bar" id="announceBar">
    <span class="announce-pill">Novo</span>
    <span>Prospecção Inteligente com Bruce AI chegou &mdash;</span>
    <a href="#features">Conheça o recurso <i class="fas fa-arrow-right" style="font-size:.65rem"></i></a>
</div>

<!-- NAV -->
<nav class="nav" id="mainNav">
    <a href="{{ url('/') }}" class="nav-logo">
        <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
    </a>

    <ul class="nav-links">
        <li class="nav-dropdown">
            <a href="#" tabindex="0">Soluções</a>
            <ul class="nav-dropdown-menu">
                <li>
                    <a href="{{ route('solutions.ngo') }}">
                        <span class="ndm-title">Terceiro Setor <span class="ndm-badge">Principal</span></span>
                        <span class="ndm-desc">ONGs, associações e fundações</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('solutions.manager') }}">
                        <span class="ndm-title">Gestor de Projetos</span>
                        <span class="ndm-desc">Consultores e coordenadores sociais</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('solutions.common') }}">
                        <span class="ndm-title">MEI &amp; Empresas</span>
                        <span class="ndm-desc">Autônomos, MEIs e pequenas empresas</span>
                    </a>
                </li>
            </ul>
        </li>
        <li><a href="#bruce-ia">Bruce IA</a></li>
        <li><a href="#features">Recursos</a></li>
        <li><a href="#academy">Academy</a></li>
        <div class="nav-sep"></div>
        <li><a href="#pricing">Preços</a></li>
    </ul>

    <div class="nav-ctas">
        <a href="{{ route('login') }}" class="btn-ghost">Entrar</a>
        <a href="{{ route('register') }}" class="btn-nav">
            Começar agora <i class="fas fa-arrow-right" style="font-size:.7rem"></i>
        </a>
    </div>

    <button class="mobile-btn" id="mobileToggle" aria-label="Menu">
        <i class="fas fa-bars" id="menuIcon"></i>
    </button>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <a href="{{ route('solutions.ngo') }}">Para ONGs <span style="font-size:.6rem;background:#FF7A1A;color:#0a0a0a;padding:2px 6px;border-radius:5px;margin-left:6px;font-weight:800;letter-spacing:.05em">PRINCIPAL</span></a>
    <a href="{{ route('solutions.manager') }}">Para Gestores</a>
    <a href="{{ route('solutions.common') }}">MEI &amp; Empresas</a>
    <a href="#bruce-ia">Bruce IA</a>
    <a href="#features">Recursos</a>
    <a href="#academy">Academy</a>
    <a href="#pricing">Preços</a>
    <a href="{{ route('login') }}">Entrar</a>
    <a href="{{ route('register') }}" class="m-cta">Começar agora →</a>
</div>

<!-- HERO -->
<section class="hero">
<div class="hero-inner">

    <!-- LEFT -->
    <div class="hero-left">
        <a href="#features" class="hero-announce">
            <span class="ha-tag">Novo</span>
            <span class="ha-dot"></span>
            Bruce AI agora prospecta parceiros automaticamente
            <i class="fas fa-arrow-right" style="font-size:.65rem;opacity:.5;margin-left:2px"></i>
        </a>

        <h1 class="hero-title">
            <span class="ht-white">A gestão que</span><br>
            <span class="ht-grad">transforma</span><br>
            <span class="ht-dim">o terceiro setor.</span>
        </h1>

        <p class="hero-sub">
            Da ONG de bairro à rede nacional — Vivensi unifica projetos, doações, voluntários, prestação de contas e captação de editais em uma única plataforma pensada para organizações sociais.
        </p>

        <div class="hero-ctas">
            <a href="{{ route('register') }}" class="btn-hero">
                Começar agora
                <i class="fas fa-arrow-right" style="font-size:.75rem"></i>
            </a>
            <a href="#segments" class="btn-hero-outline">
                <i class="fas fa-play" style="font-size:.7rem"></i>
                Ver como funciona
            </a>
        </div>

        {{-- Ancoragem de preco: remove friccao "quanto custa" sem prometer teste gratis. --}}
        @if(!empty($cheapestPlan))
            <div class="hero-price-anchor">
                <a href="#pricing">
                    A partir de <strong>R$ {{ number_format((float) $cheapestPlan->price, 2, ',', '.') }}</strong>
                    <span>/mês</span>
                    <span class="hpa-sep">·</span>
                    <span class="hpa-note">Cancele quando quiser</span>
                </a>
            </div>
        @endif

        <div class="hero-proof">
            <div class="hp-avatars">
                <div class="hp-av">J</div>
                <div class="hp-av av2">M</div>
                <div class="hp-av av3">A</div>
                <div class="hp-av av4">C</div>
            </div>
            <div class="hp-text">
                <strong>{{ $siteStats['hero_badge'] }}</strong><br>{{ $siteStats['orgs_label'] }}
            </div>
            <div class="hp-sep"></div>
            <div class="hp-rating">
                <div class="hp-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <span class="hp-rlabel">{{ $siteStats['rating_score'] }} / 5 &nbsp;·&nbsp; {{ $siteStats['rating_label'] }}</span>
            </div>
        </div>
    </div>

    <!-- RIGHT — Brazil Map -->
    <div class="hero-right">
        <div class="hero-map-wrap">

            <!-- Badge: organizações ativas -->
            <div class="map-badge mb-1">
                <div class="mb-val">{{ $siteStats['orgs_count'] }}+</div>
                <div class="mb-lbl">{{ $siteStats['orgs_label'] }}</div>
            </div>

            <!-- Badge: cobertura ao vivo -->
            <div class="map-badge mb-2">
                <div class="mb-live">
                    <span class="mb-ldot"></span>
                    <span style="font-size:.58rem;color:#4ade80;font-weight:800;letter-spacing:.07em">AO VIVO</span>
                </div>
                <div class="mb-val" style="font-size:.88rem">26 estados + DF</div>
                <div class="mb-lbl">cobertos pelo sistema</div>
            </div>

            <!-- Badge: captações -->
            <div class="map-badge mb-3">
                <div class="mb-val" style="color:#93a8ff">{{ $siteStats['projects_count'] }}+</div>
                <div class="mb-lbl">{{ $siteStats['projects_label'] }}</div>
            </div>

            <!-- Animated Brazil SVG Map -->
            <svg class="brazil-svg" viewBox="0 0 480 575" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="brGlow" cx="54%" cy="44%" r="50%">
                        <stop offset="0%" stop-color="#4F6EF7" stop-opacity="1"/>
                        <stop offset="100%" stop-color="#4F6EF7" stop-opacity="0"/>
                    </radialGradient>
                </defs>

                <!-- Brazil outline — simplified clockwise from NW -->
                <path class="br-fill" d="
                    M 30,148 L 12,82 L 42,50 L 165,38 L 258,18
                    L 296,70 L 342,118 L 396,140 L 450,188
                    L 448,220 L 415,280 L 385,340 L 358,390
                    L 348,424 L 318,450 L 285,474 L 255,496
                    L 228,518 L 196,535 L 168,545
                    L 152,528 L 140,490 L 130,455 L 130,415
                    L 148,378 L 155,348 L 138,315 L 120,280
                    L 105,248 L 82,212 L 55,178 Z
                "/>

                <!-- Glow overlay -->
                <path fill="url(#brGlow)" opacity=".13" d="
                    M 30,148 L 12,82 L 42,50 L 165,38 L 258,18
                    L 296,70 L 342,118 L 396,140 L 450,188
                    L 448,220 L 415,280 L 385,340 L 358,390
                    L 348,424 L 318,450 L 285,474 L 255,496
                    L 228,518 L 196,535 L 168,545
                    L 152,528 L 140,490 L 130,455 L 130,415
                    L 148,378 L 155,348 L 138,315 L 120,280
                    L 105,248 L 82,212 L 55,178 Z
                "/>

                <!-- State boundary lines -->
                <line class="br-state-line" x1="160" y1="46" x2="295" y2="115"/>
                <line class="br-state-line" x1="295" y1="115" x2="342" y2="118"/>
                <line class="br-state-line" x1="160" y1="46" x2="108" y2="178"/>
                <line class="br-state-line" x1="295" y1="115" x2="282" y2="262"/>
                <line class="br-state-line" x1="282" y1="262" x2="346" y2="278"/>
                <line class="br-state-line" x1="154" y1="282" x2="282" y2="262"/>
                <line class="br-state-line" x1="130" y1="415" x2="284" y2="396"/>
                <line class="br-state-line" x1="215" y1="448" x2="318" y2="444"/>
                <line class="br-state-line" x1="238" y1="474" x2="308" y2="468"/>

                <!-- Animated connection lines between cities -->
                <line class="conn-line" stroke="rgba(74,222,128,.22)" x1="282" y1="396" x2="318" y2="444"/>
                <line class="conn-line" stroke="rgba(79,110,247,.18)" x1="318" y1="444" x2="348" y2="422"/>
                <line class="conn-line" stroke="rgba(79,110,247,.18)" x1="348" y1="422" x2="338" y2="384"/>
                <line class="conn-line" stroke="rgba(79,110,247,.13)" x1="282" y1="396" x2="338" y2="384"/>
                <line class="conn-line" stroke="rgba(79,110,247,.11)" x1="338" y1="384" x2="392" y2="292"/>
                <line class="conn-line" stroke="rgba(147,168,255,.12)" x1="318" y1="444" x2="258" y2="470"/>
                <line class="conn-line" stroke="rgba(147,168,255,.10)" x1="258" y1="470" x2="230" y2="495"/>

                <!-- São Paulo — principal hub -->
                <circle class="city-ring" stroke="#4ade80" cx="318" cy="444" r="7" style="animation-delay:0s"/>
                <circle class="city-dot" fill="#4ade80" cx="318" cy="444" r="5" style="animation-delay:0s"/>

                <!-- Rio de Janeiro -->
                <circle class="city-ring" stroke="#4F6EF7" cx="348" cy="422" r="6" style="animation-delay:.5s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="348" cy="422" r="4" style="animation-delay:.5s"/>

                <!-- Brasília -->
                <circle class="city-ring" stroke="#93a8ff" cx="282" cy="396" r="6" style="animation-delay:1s"/>
                <circle class="city-dot" fill="#93a8ff" cx="282" cy="396" r="4" style="animation-delay:1s"/>

                <!-- Belo Horizonte -->
                <circle class="city-ring" stroke="#4F6EF7" cx="338" cy="384" r="5" style="animation-delay:1.5s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="338" cy="384" r="3.5" style="animation-delay:1.5s"/>

                <!-- Salvador -->
                <circle class="city-ring" stroke="#4F6EF7" cx="392" cy="292" r="5" style="animation-delay:.8s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="392" cy="292" r="3.5" style="animation-delay:.8s"/>

                <!-- Fortaleza -->
                <circle class="city-ring" stroke="#4F6EF7" cx="393" cy="148" r="5" style="animation-delay:1.3s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="393" cy="148" r="3.5" style="animation-delay:1.3s"/>

                <!-- Recife -->
                <circle class="city-ring" stroke="#93a8ff" cx="440" cy="206" r="5" style="animation-delay:.3s"/>
                <circle class="city-dot" fill="#93a8ff" cx="440" cy="206" r="3" style="animation-delay:.3s"/>

                <!-- Manaus -->
                <circle class="city-ring" stroke="#93a8ff" cx="118" cy="118" r="5" style="animation-delay:1.8s"/>
                <circle class="city-dot" fill="#93a8ff" cx="118" cy="118" r="3" style="animation-delay:1.8s"/>

                <!-- Curitiba -->
                <circle class="city-ring" stroke="#4F6EF7" cx="258" cy="470" r="4.5" style="animation-delay:2s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="258" cy="470" r="3" style="animation-delay:2s"/>

                <!-- Porto Alegre -->
                <circle class="city-ring" stroke="#4F6EF7" cx="230" cy="495" r="4.5" style="animation-delay:.6s"/>
                <circle class="city-dot" fill="#4F6EF7" cx="230" cy="495" r="3" style="animation-delay:.6s"/>

                <!-- Belém -->
                <circle class="city-ring" stroke="#93a8ff" cx="292" cy="78" r="4" style="animation-delay:2.3s"/>
                <circle class="city-dot" fill="#93a8ff" cx="292" cy="78" r="2.5" style="animation-delay:2.3s"/>
            </svg>

        </div>
    </div><!-- /hero-right -->

</div><!-- /hero-inner -->

    @if($videoUrl)
    <!-- VIDEO MODAL -->
    <div class="video-modal" id="videoModal" onclick="closeVideoModal(event)">
        <div class="video-modal-inner">
            <button class="video-modal-close" onclick="closeVideoModal()">&times;</button>
            <iframe id="videoIframe" src="" frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen style="width:100%;height:100%;border-radius:16px;"></iframe>
        </div>
    </div>
    @endif

    <!-- MAP VISUALIZATION (hidden) -->
    <div class="map-section" style="display:none">
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
                <!-- Brazil mainland path (Detailed) -->
                <path d="M295.5,56.5c10.5-5.5,25.5-8.5,38.5-6.5s28,8.5,41.5,10c13.5,1.5,22.5-2.5,35.5,3.5s23,19,30,28.5s15.5,23,26,32s24,19.5,24,37.5s-4.5,30-10,43.5s-4.5,26.5,8,42s26,30,34.5,45s12.5,35.5,12.5,52.5s-4.5,33.5-12,48.5s-16.5,28.5-28.5,39.5s-20,19-33,28.5s-21.5,22-26.5,37s-10,32-20,46s-20,24-33.5,36.5s-24,20-35,32s-19.5,25-29,38s-17,21.5-28,21.5s-21.5-9.5-33.5-23s-22-29-32-45.5s-18-35.5-23-53.5s-7.5-35.5-9.5-54s-2.5-36,1-53s8-32.5,16.5-47s12.5-31,12.5-49.5s-6-35.5-15.5-50.5s-18-31-25.5-48s-11-34-11-50s5-29,15-44s21-27,34.5-38s22.5-19.5,34.5-22s22.5,5.5,22.5,15.5s-5.5,22.5-11.5,34.5s-10,25.5-10,38.5s7.5,25,20,38.5s25.5,25.5,36.5,17s12.5-24.5,12.5-40s-6-30-14.5-40.5s-17-15.5-17-27.5S285,62,295.5,56.5z"
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
                <div class="fcard-label">Organizações ativas</div>
                <div class="fcard-value">{{ $siteStats['orgs_count'] }}+</div>
                <div class="fcard-sub"><i class="fas fa-arrow-up"></i> Crescendo</div>
            </div>
            <div class="fcard fc-b">
                <div class="fcard-label">Projetos gerenciados</div>
                <div class="fcard-value">{{ $siteStats['projects_count'] }}+</div>
                <div class="fcard-sub" style="color:var(--gold)"><i class="fas fa-chart-line"></i> Em expansão</div>
            </div>
            <div class="fcard fc-c">
                <div class="fcard-label">Usuários ativos</div>
                <div class="fcard-value">{{ $siteStats['users_count'] }}+</div>
                <div class="fcard-sub"><i class="fas fa-heart"></i> {{ $siteStats['impact_label'] }}</div>
            </div>
            <div class="fcard fc-d">
                <div class="fcard-label">Avaliação média</div>
                <div class="fcard-value">{{ $siteStats['rating_score'] }}/5</div>
                <div class="fcard-sub" style="color:#B27CFF"><i class="fas fa-star"></i> {{ $siteStats['rating_label'] }}</div>
            </div>
            <div class="fcard fc-e">
                <div class="fcard-label">Relatórios gerados por IA</div>
                <div class="fcard-value">Bruce AI</div>
                <div class="fcard-sub" style="color:rgba(255,255,255,.4)">Automáticos e precisos</div>
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

    <div class="seg-header aos">
        <div class="seg-hdr-left">
            <div class="seg-eyebrow">
                <i class="fas fa-layer-group" style="font-size:.65rem"></i> Soluções
            </div>
            <h2 class="seg-title">
                Feito para o<br>
                <em>terceiro setor.</em>
            </h2>
        </div>
        <div class="seg-hdr-right">
            <p style="margin-bottom: 15px;">
                Três pilares para sustentar sua organização: gestão unificada, transparência à prova de auditoria e captação inteligente de recursos.
            </p>
            <div style="background: rgba(37, 211, 102, 0.1); border: 1px solid rgba(37, 211, 102, 0.3); padding: 12px 20px; border-radius: 12px; display: inline-flex; align-items: center; gap: 12px;">
                <i class="fab fa-whatsapp" style="color: #25D366; font-size: 1.5rem;"></i>
                <div>
                    <strong style="color: white; display: block; font-size: 0.9rem;">WhatsApp integrado à sua ONG</strong>
                    <span style="color: rgba(255,255,255,0.7); font-size: 0.8rem;">Disparo em massa para doadores, Chatbot 24/7 e atendimento humanizado.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="seg-grid">

        {{-- ══ HERO: ONGs & Terceiro Setor ════════════════════════════════ --}}
        <a href="{{ route('solutions.ngo') }}" class="seg-card seg-ngo aos">

            <div class="seg-principal-badge">
                <i class="fas fa-star"></i> Solução Principal · Terceiro Setor
            </div>

            <div class="seg-num">01</div>

            <div class="seg-icon-row">
                <div class="seg-icon si-rose"><i class="fas fa-hand-holding-heart"></i></div>
                <span class="seg-badge sb-rose">Terceiro Setor / ONGs</span>
            </div>

            <h3>ONGs &amp; Entidades Sociais</h3>
            <p>O Vivensi foi construído do zero para o terceiro setor. Do controle de beneficiários ao portal de transparência pública — tudo que sua organização precisa para operar com profissionalismo, captar recursos e prestar contas com clareza.</p>

            <div class="seg-chips">
                <span class="seg-chip">Portal do Doador</span>
                <span class="seg-chip">Prestação de Contas</span>
                <span class="seg-chip">Beneficiários & Atendimentos</span>
                <span class="seg-chip">Captação de Editais via IA</span>
                <span class="seg-chip">Transparência Pública</span>
                <span class="seg-chip">Gestão de Voluntários</span>
                <span class="seg-chip">Almoxarifado</span>
                <span class="seg-chip">Patrocínios & Doações</span>
            </div>

            <div class="seg-feat-list">
                <div class="seg-feat-item"><i class="fas fa-check"></i> Mapa Territorial de Impacto</div>
                <div class="seg-feat-item"><i class="fas fa-check"></i> Boletim de Acompanhamento</div>
                <div class="seg-feat-item"><i class="fas fa-check"></i> Relatórios para Editais</div>
                <div class="seg-feat-item"><i class="fas fa-check"></i> Controle de Projetos Sociais</div>
                <div class="seg-feat-item"><i class="fas fa-check"></i> CRM de Doadores</div>
                <div class="seg-feat-item"><i class="fas fa-check"></i> WhatsApp para Captação</div>
            </div>

            <div class="seg-stat">
                <div>
                    <div class="seg-stat-val">{{ $siteStats['orgs_count'] }}+</div>
                    <div class="seg-stat-lbl">{{ $siteStats['orgs_label'] }}</div>
                </div>
                <div>
                    <div class="seg-stat-val" style="color:#ef4444">100%</div>
                    <div class="seg-stat-lbl">focado no terceiro setor</div>
                </div>
            </div>

            <span class="seg-link sl-rose">
                Ver solução completa para ONGs
                <span class="seg-link-arrow sla-rose"><i class="fas fa-arrow-right"></i></span>
            </span>
        </a>

        {{-- ── Pilar: Transparência & Compliance ──────────────────────── --}}
        <a href="{{ route('solutions.ngo') }}#transparencia" class="seg-card seg-mgr seg-compact aos">
            <div class="seg-num">02</div>

            <div class="seg-icon-row">
                <div class="seg-icon si-blue"><i class="fas fa-shield-halved"></i></div>
                <span class="seg-badge sb-blue">Transparência</span>
            </div>

            <h3>Transparência &amp; Compliance</h3>
            <p>Portal público, prestação de contas automatizada e conformidade com LGPD, CEBAS e MROSC — sem planilha.</p>

            <div class="seg-chips">
                <span class="seg-chip">Portal Público</span>
                <span class="seg-chip">Prestação de Contas</span>
                <span class="seg-chip">CEBAS / MROSC</span>
                <span class="seg-chip">LGPD</span>
                <span class="seg-chip">Auditoria</span>
                <span class="seg-chip">Recibo IR</span>
            </div>

            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#3B6CF6">100%</div>
                <div class="seg-stat-lbl">rastreabilidade nos processos sensíveis</div>
            </div>

            <span class="seg-link sl-blue">
                Ver como funciona
                <span class="seg-link-arrow sla-blue"><i class="fas fa-arrow-right"></i></span>
            </span>
        </a>

        {{-- ── Pilar: Captação & Sustentabilidade ─────────────────────── --}}
        <a href="{{ route('solutions.ngo') }}#captacao" class="seg-card seg-ppl seg-compact aos">
            <div class="seg-num">03</div>

            <div class="seg-icon-row">
                <div class="seg-icon si-purple"><i class="fas fa-hand-holding-dollar"></i></div>
                <span class="seg-badge sb-purple">Captação</span>
            </div>

            <h3>Captação &amp; Sustentabilidade</h3>
            <p>Radar de editais com IA, portal do doador, campanhas, rifas e patrocínios — para diversificar sua receita.</p>

            <div class="seg-chips">
                <span class="seg-chip">Radar de Editais IA</span>
                <span class="seg-chip">Portal do Doador</span>
                <span class="seg-chip">Campanhas</span>
                <span class="seg-chip">Rifas</span>
                <span class="seg-chip">Patrocínios</span>
                <span class="seg-chip">CRM de Doadores</span>
            </div>

            <div class="seg-stat">
                <div class="seg-stat-val" style="color:#7C3AED">IA</div>
                <div class="seg-stat-lbl">Bruce IA prospecta parceiros pra você</div>
            </div>

            <span class="seg-link sl-purple">
                Ver como funciona
                <span class="seg-link-arrow sla-purple"><i class="fas fa-arrow-right"></i></span>
            </span>
        </a>

    </div>
</section>

<!-- FEATURES / PLATFORM ACCORDION -->
<section class="pt-section aos" id="features">
<style>
/* ─── PLATFORM ACCORDION ─── */
.pt-section{padding:90px 0;background:#080808}
.pt-inner{max-width:1100px;margin:0 auto;padding:0 24px}
.pt-head{text-align:center;margin-bottom:56px}
.pt-head .feat-eyebrow{justify-content:center;margin-bottom:16px}
.pt-h2{font-size:clamp(1.9rem,3.8vw,2.7rem);font-weight:900;color:#fff;letter-spacing:-0.03em;line-height:1.2;margin:0}
.pt-h2 em{font-style:normal;color:rgba(255,255,255,.65)}
.pt-h2-cta{display:block;font-size:.85rem;font-weight:500;color:rgba(255,255,255,.72);margin-top:14px;letter-spacing:0}

/* Accordion container */
.pt-accord{border:1px solid rgba(255,255,255,.08);border-radius:20px;overflow:hidden;margin-bottom:10px;background:#0c0c0c;transition:border-color .3s,background .3s}
.pt-accord.pt-open{border-color:rgba(255,255,255,.16);background:#0f0f0f}

/* Accordion header */
.pta-hdr{width:100%;background:transparent;border:none;padding:22px 26px;display:flex;align-items:center;gap:16px;cursor:pointer;text-align:left;color:#fff;transition:background .2s}
.pta-hdr:hover{background:rgba(255,255,255,.025)}
.pta-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0;transition:transform .2s}
.pt-accord.pt-open .pta-icon{transform:scale(1.08)}
.pta-ic-rose{background:rgba(251,113,133,.15);color:#fb7185}
.pta-ic-blue{background:rgba(96,165,250,.15);color:#60a5fa}
.pta-ic-purple{background:rgba(167,139,250,.15);color:#a78bfa}
.pta-meta{flex:1;min-width:0}
.pta-name{font-size:1.05rem;font-weight:800;color:#fff;line-height:1.2}
.pta-sub{font-size:.75rem;color:rgba(255,255,255,.32);margin-top:3px}
.pta-badge{font-size:.65rem;font-weight:700;padding:4px 12px;border-radius:100px;background:rgba(255,255,255,.07);color:rgba(255,255,255,.4);white-space:nowrap;flex-shrink:0}
.pta-chevron{width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.25);transition:transform .3s;flex-shrink:0;font-size:.8rem}
.pt-accord.pt-open .pta-chevron{transform:rotate(180deg);color:rgba(255,255,255,.6)}

/* Accordion body */
.pta-body{max-height:0;overflow:hidden;transition:max-height .42s ease}
.pt-accord.pt-open .pta-body{max-height:1400px}
.pta-content{display:grid;grid-template-columns:1fr 380px;gap:36px;padding:4px 26px 30px;align-items:start}

/* Feature cards grid */
.pta-feats{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pta-feat{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:16px;display:flex;gap:12px;align-items:flex-start;transition:border-color .2s,background .2s}
.pta-feat:hover{border-color:rgba(255,255,255,.12);background:rgba(255,255,255,.04)}
.pta-fi{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.afi-green{background:rgba(37,211,102,.15);color:#25d366}
.afi-blue{background:rgba(96,165,250,.15);color:#60a5fa}
.afi-purple{background:rgba(167,139,250,.15);color:#a78bfa}
.afi-pink{background:rgba(236,72,153,.15);color:#ec4899}
.afi-orange{background:rgba(251,146,60,.15);color:#fb923c}
.afi-teal{background:rgba(45,212,191,.15);color:#2dd4bf}
.afi-yellow{background:rgba(251,191,36,.15);color:#fbbf24}
.afi-red{background:rgba(248,113,113,.15);color:#f87171}
.pta-ft{font-size:.8rem;font-weight:700;color:#fff;margin-bottom:3px;line-height:1.3}
.pta-fd{font-size:.7rem;color:rgba(255,255,255,.36);line-height:1.5}

/* Demo visual */
.pta-demo{position:sticky;top:88px}
.pta-demo-box{background:#141414;border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:18px;overflow:hidden}
.ptd-label{font-size:.62rem;font-weight:700;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px;display:flex;align-items:center;gap:7px}

/* Demo — NGO chat + stats */
.ptd-chat-bubble{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:12px;margin-bottom:12px}
.ptd-ch-hdr{display:flex;align-items:center;gap:10px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid rgba(255,255,255,.05)}
.ptd-ch-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#25D366,#128C7E);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;flex-shrink:0}
.ptd-ch-name{font-size:.75rem;font-weight:700;color:#fff}
.ptd-ch-status{font-size:.62rem;color:#25d366}
.ptd-msg{font-size:.68rem;line-height:1.5;padding:7px 10px;border-radius:10px;margin-bottom:6px;max-width:90%}
.ptd-msg-in{background:rgba(255,255,255,.06);color:rgba(255,255,255,.65);border-bottom-left-radius:2px;align-self:flex-start}
.ptd-msg-out{background:rgba(37,211,102,.12);color:rgba(255,255,255,.85);border-bottom-right-radius:2px;margin-left:auto}
.ptd-msgs{display:flex;flex-direction:column}
.ptd-stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:4px}
.ptd-stat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:10px;text-align:center}
.ptd-stat-v{font-size:.95rem;font-weight:800;color:#fff;display:block}
.ptd-stat-l{font-size:.6rem;color:rgba(255,255,255,.65);margin-top:2px;display:block}

/* Demo — Gestores kanban */
.ptd-kanban{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-bottom:12px}
.ptd-kcol-hdr{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;display:flex;align-items:center;gap:5px}
.ptd-kcol-hdr::before{content:'';width:7px;height:7px;border-radius:50%;flex-shrink:0}
.ptd-kh-o::before{background:#fb923c}.ptd-kh-b::before{background:#60a5fa}.ptd-kh-g::before{background:#4ade80}
.ptd-kcard{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:7px 8px;margin-bottom:5px;font-size:.62rem;color:rgba(255,255,255,.7)}
.ptd-kcard span{display:block;font-size:.6rem;color:rgba(255,255,255,.72);margin-top:2px}
.ptd-kcard.hot{border-color:rgba(251,191,36,.3);background:rgba(251,191,36,.06)}
.ptd-kpi-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ptd-kpi{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:10px;text-align:center}
.ptd-kpi-v{font-size:.95rem;font-weight:800;color:#fff}
.ptd-kpi-l{font-size:.6rem;color:rgba(255,255,255,.65);margin-top:2px}

/* Demo — Pessoal agendamento */
.ptd-slots{display:flex;flex-direction:column;gap:7px;margin-bottom:12px}
.ptd-slot{font-size:.72rem;padding:9px 12px;border-radius:10px;display:flex;align-items:center;gap:8px;font-weight:600}
.ptd-slot-ok{background:rgba(37,211,102,.1);color:#4ade80;border:1px solid rgba(37,211,102,.2)}
.ptd-slot-ok::before{content:'✓';font-weight:900}
.ptd-slot-free{background:rgba(255,255,255,.04);color:rgba(255,255,255,.65);border:1px solid rgba(255,255,255,.07)}
.ptd-slot-free::before{content:'○'}
.ptd-tags{display:flex;flex-wrap:wrap;gap:6px}
.ptd-tag{font-size:.62rem;font-weight:700;padding:4px 10px;border-radius:100px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:5px}

/* Responsive */
@media(max-width:860px){.pta-content{grid-template-columns:1fr}.pta-demo{display:none}}
@media(max-width:640px){.pta-feats{grid-template-columns:1fr}.pta-hdr{padding:18px 20px;gap:12px}.pta-icon{width:42px;height:42px}.pta-name{font-size:.95rem}}
</style>
<div class="pt-inner">

    <!-- Header -->
    <div class="pt-head">
        <div class="feat-eyebrow"><span class="feat-ey-dot"></span> Plataforma Inteligente</div>
        <h2 class="pt-h2">
            18 módulos prontos para a sua ONG.<br>
            <em>Explore por área de atuação.</em>
        </h2>
        <span class="pt-h2-cta">Clique em cada tema e veja o que o Vivensi faz pela sua organização.</span>
    </div>

    <!-- ACCORDION SEGMENTS -->

    {{-- ─── 1. ONGs & Terceiro Setor ─────────────────────────────── --}}
    <div class="pt-accord pt-open" id="pt-ngo">
        <button class="pta-hdr" onclick="ptToggle('ngo')" type="button">
            <div class="pta-icon pta-ic-rose"><i class="fas fa-hand-holding-heart"></i></div>
            <div class="pta-meta">
                <div class="pta-name">ONGs &amp; Terceiro Setor</div>
                <div class="pta-sub">Captação, transparência, convênios e voluntários</div>
            </div>
            <span class="pta-badge">6 módulos</span>
            <div class="pta-chevron"><i class="fas fa-chevron-down"></i></div>
        </button>
        <div class="pta-body">
            <div class="pta-content">
                <div class="pta-feats">
                    <div class="pta-feat">
                        <div class="pta-fi afi-green"><i class="fab fa-whatsapp"></i></div>
                        <div><div class="pta-ft">Bot WhatsApp IA 24/7</div><div class="pta-fd">Responde doadores, envia link de doação e recibo em PDF automaticamente — sem você estar online.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-pink"><i class="fas fa-heart"></i></div>
                        <div><div class="pta-ft">CRM de Doadores</div><div class="pta-fd">Histórico completo de cada doador: quanto deu, quando e por qual canal. Régua de reativação automática.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-teal"><i class="fas fa-earth-americas"></i></div>
                        <div><div class="pta-ft">Portal de Transparência</div><div class="pta-fd">Página pública com métricas ao vivo: doações, projetos e beneficiários. Perfeito para editais e fiscais.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-yellow"><i class="fas fa-file-contract"></i></div>
                        <div><div class="pta-ft">Convênios &amp; Editais</div><div class="pta-fd">Prazos, entregáveis e alertas automáticos antes do vencimento da prestação de contas.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-orange"><i class="fas fa-boxes-stacked"></i></div>
                        <div><div class="pta-ft">Almoxarifado</div><div class="pta-fd">Entradas de doações físicas, saídas por projeto e relatório pronto para auditoria externa.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-purple"><i class="fas fa-trophy"></i></div>
                        <div><div class="pta-ft">Gamificação de Voluntários</div><div class="pta-fd">Ranking público, pontos por horas trabalhadas e medalhas. Engajamento real, comprovado em campo.</div></div>
                    </div>
                </div>
                <div class="pta-demo">
                    <div class="pta-demo-box">
                        <div class="ptd-label"><i class="fab fa-whatsapp"></i> WhatsApp Bot Ativo</div>
                        <div class="ptd-chat-bubble">
                            <div class="ptd-ch-hdr">
                                <div class="ptd-ch-av"><i class="fas fa-heart"></i></div>
                                <div><div class="ptd-ch-name">Projeto Esperança</div><div class="ptd-ch-status">Bruce AI · respondendo agora</div></div>
                            </div>
                            <div class="ptd-msgs">
                                <div class="ptd-msg ptd-msg-in">Quero fazer uma doação mensal. Como funciona?</div>
                                <div class="ptd-msg ptd-msg-out">💙 Que incrível! Você pode doar via PIX agora: vivensi.app/doar — O recibo chega automático!</div>
                            </div>
                        </div>
                        <div class="ptd-label" style="margin-top:14px;"><i class="fas fa-earth-americas"></i> Portal de Transparência</div>
                        <div class="ptd-stats-row">
                            <div class="ptd-stat"><span class="ptd-stat-v">R$312k</span><span class="ptd-stat-l">Captado</span></div>
                            <div class="ptd-stat"><span class="ptd-stat-v">1.240</span><span class="ptd-stat-l">Doadores</span></div>
                            <div class="ptd-stat"><span class="ptd-stat-v">+23%</span><span class="ptd-stat-l">vs 2025</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── 2. Gestão Financeira & Prestação de Contas ──────────────── --}}
    <div class="pt-accord" id="pt-mgr">
        <button class="pta-hdr" onclick="ptToggle('mgr')" type="button">
            <div class="pta-icon pta-ic-blue"><i class="fas fa-scale-balanced"></i></div>
            <div class="pta-meta">
                <div class="pta-name">Financeiro &amp; Prestação de Contas</div>
                <div class="pta-sub">DRE, conciliação, CEBAS, MROSC e auditoria pronta pra Ministério Público</div>
            </div>
            <span class="pta-badge">6 módulos</span>
            <div class="pta-chevron"><i class="fas fa-chevron-down"></i></div>
        </button>
        <div class="pta-body">
            <div class="pta-content">
                <div class="pta-feats">
                    <div class="pta-feat">
                        <div class="pta-fi afi-blue"><i class="fas fa-chart-line"></i></div>
                        <div><div class="pta-ft">Financeiro Completo</div><div class="pta-fd">DRE, conciliação bancária, balancetes e exportação para contabilidade em um clique.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-orange"><i class="fas fa-shield-halved"></i></div>
                        <div><div class="pta-ft">CEBAS &amp; MROSC</div><div class="pta-fd">Motor de conformidade contínua com checklists automáticos, alertas de vencimento e relatórios prontos para renovação.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-teal"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div><div class="pta-ft">Recibo de IR Automático</div><div class="pta-fd">Cada doação vira PDF com QR de validação. O doador recebe direto no WhatsApp ou e-mail.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-red"><i class="fas fa-clipboard-check"></i></div>
                        <div><div class="pta-ft">Auditoria &amp; LGPD</div><div class="pta-fd">Log rastreável de cada ação sensível (quem, quando, de onde). Prova prática para MP, fiscais e ANPD.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-yellow"><i class="fas fa-boxes-stacked"></i></div>
                        <div><div class="pta-ft">Almoxarifado &amp; Patrimônio</div><div class="pta-fd">Doações físicas, saídas por projeto e inventário — relatório pronto para auditoria externa.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-purple"><i class="fas fa-earth-americas"></i></div>
                        <div><div class="pta-ft">Portal de Transparência</div><div class="pta-fd">Página pública obrigatória em CEBAS, atualizada em tempo real. Sem planilha, sem risco de erro.</div></div>
                    </div>
                </div>
                <div class="pta-demo">
                    <div class="pta-demo-box">
                        <div class="ptd-label"><i class="fas fa-shield-halved"></i> Motor de Conformidade</div>
                        <div class="ptd-slots">
                            <div class="ptd-slot ptd-slot-ok">CEBAS · Certidão Federal · <strong>Ok</strong></div>
                            <div class="ptd-slot ptd-slot-ok">MROSC · Plano de Trabalho 2026 · <strong>Enviado</strong></div>
                            <div class="ptd-slot ptd-slot-ok">LGPD · Registro de Operações · <strong>Em dia</strong></div>
                            <div class="ptd-slot ptd-slot-free">CNAS · Renovação em 47 dias</div>
                        </div>
                        <div class="ptd-label" style="margin-top:14px"><i class="fas fa-file-invoice-dollar"></i> Prestação de Contas 2026</div>
                        <div class="ptd-stats-row">
                            <div class="ptd-stat"><span class="ptd-stat-v">R$ 1.2M</span><span class="ptd-stat-l">Executado</span></div>
                            <div class="ptd-stat"><span class="ptd-stat-v">96%</span><span class="ptd-stat-l">Comprovado</span></div>
                            <div class="ptd-stat"><span class="ptd-stat-v">4/4</span><span class="ptd-stat-l">Convênios ok</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── 3. Captação Inteligente & Editais ──────────────────────── --}}
    <div class="pt-accord" id="pt-personal">
        <button class="pta-hdr" onclick="ptToggle('personal')" type="button">
            <div class="pta-icon pta-ic-purple"><i class="fas fa-brain"></i></div>
            <div class="pta-meta">
                <div class="pta-name">Captação Inteligente &amp; Editais</div>
                <div class="pta-sub">Radar de editais IA, portal do doador, campanhas, rifas e patrocínios</div>
            </div>
            <span class="pta-badge">6 módulos</span>
            <div class="pta-chevron"><i class="fas fa-chevron-down"></i></div>
        </button>
        <div class="pta-body">
            <div class="pta-content">
                <div class="pta-feats">
                    <div class="pta-feat">
                        <div class="pta-fi afi-purple"><i class="fas fa-satellite-dish"></i></div>
                        <div><div class="pta-ft">Radar de Editais com IA</div><div class="pta-fd">Bruce IA varre Transferegov e QueroDoação, filtra por tema/UF e sugere só editais alinhados à sua missão.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-pink"><i class="fas fa-heart"></i></div>
                        <div><div class="pta-ft">Portal do Doador</div><div class="pta-fd">Cada doador tem área logada com histórico, próximos vencimentos e recibo de IR baixável a qualquer hora.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-orange"><i class="fas fa-bullhorn"></i></div>
                        <div><div class="pta-ft">Campanhas Públicas</div><div class="pta-fd">Landing pages com meta em tempo real, integração PIX/cartão e checkout otimizado — feitas em minutos.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-yellow"><i class="fas fa-ticket"></i></div>
                        <div><div class="pta-ft">Rifas &amp; Sorteios</div><div class="pta-fd">Rifas beneficentes com pagamento PIX, sorteio auditável e envio automático dos bilhetes pelo WhatsApp.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-blue"><i class="fas fa-handshake"></i></div>
                        <div><div class="pta-ft">CRM de Patrocínios</div><div class="pta-fd">Pipeline visual: Prospecção → Proposta → Fechado. Bruce IA sugere fit com empresas e ajuda no pitch.</div></div>
                    </div>
                    <div class="pta-feat">
                        <div class="pta-fi afi-green"><i class="fab fa-whatsapp"></i></div>
                        <div><div class="pta-ft">Régua de Reativação</div><div class="pta-fd">Doadores que ficaram inativos recebem sequência automatizada por WhatsApp — sem esforço da equipe.</div></div>
                    </div>
                </div>
                <div class="pta-demo">
                    <div class="pta-demo-box">
                        <div class="ptd-label"><i class="fas fa-satellite-dish"></i> Radar de Editais — Últimas 24h</div>
                        <div class="ptd-slots">
                            <div class="ptd-slot ptd-slot-ok"><strong>Petrobras Socioambiental</strong> · R$ 800k · Fit 94%</div>
                            <div class="ptd-slot ptd-slot-ok"><strong>Itaú Social</strong> · R$ 250k · Fit 87%</div>
                            <div class="ptd-slot ptd-slot-free">Transferegov Cultura · R$ 120k · Fit 71%</div>
                        </div>
                        <div class="ptd-tags">
                            <span class="ptd-tag"><i class="fas fa-brain" style="color:#a78bfa"></i> Bruce IA aprovou 2</span>
                            <span class="ptd-tag"><i class="fas fa-envelope-open-text" style="color:#4ade80"></i> Digest enviado</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /pt-inner -->
</section>

<!-- ─── BRUCE IA — SEÇÃO DEDICADA ─── -->
<section class="bruce-section" id="bruce-ia">
<style>
.bruce-section{
    padding:110px 6% 100px;
    background:#0A0A0B;
    position:relative;
}
.bruce-inner{max-width:1200px;margin:0 auto;position:relative}
.bruce-header{
    display:grid;grid-template-columns:auto 1fr;gap:48px;align-items:center;
    margin-bottom:56px;
}
.bruce-icon-wrap{
    display:flex;flex-direction:column;align-items:center;gap:12px;flex-shrink:0;
}
.bruce-icon-wrap img{
    width:140px;height:140px;border-radius:32px;
    background:#0f0f1e;
    border:1px solid rgba(255,255,255,.08);
}
.bruce-tag{
    background:rgba(255,122,26,.15);color:#FF7A1A;
    font-size:.68rem;font-weight:800;
    padding:5px 14px;border-radius:20px;letter-spacing:.1em;
    text-transform:uppercase;border:1px solid rgba(255,122,26,.3);
}
.bruce-title-block .bt-eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    font-size:.72rem;font-weight:800;letter-spacing:.14em;
    color:#FF7A1A;text-transform:uppercase;margin-bottom:14px;
}
.bruce-title-block h2{
    font-size:clamp(2rem,4vw,3rem);font-weight:900;
    color:#fff;letter-spacing:-.03em;line-height:1.1;
    margin:0 0 18px;
}
.bruce-title-block h2 em{font-style:normal;color:#FF7A1A;}
.bruce-title-block p{
    font-size:1.03rem;line-height:1.65;
    color:rgba(255,255,255,.62);max-width:560px;margin:0;
}
.bruce-pillars{
    display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:44px;
}
.bp-card{
    background:#111114;
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;padding:26px 22px;
    transition:border-color .25s,transform .25s;
}
.bp-card:hover{border-color:rgba(255,122,26,.3);transform:translateY(-3px)}
.bp-ico{
    width:44px;height:44px;border-radius:12px;
    background:#FF7A1A;
    display:flex;align-items:center;justify-content:center;
    font-size:1.05rem;color:#fff;margin-bottom:16px;
}
.bp-title{
    font-size:.95rem;font-weight:800;color:#fff;
    letter-spacing:-.01em;margin-bottom:8px;
}
.bp-desc{
    font-size:.82rem;line-height:1.55;color:rgba(255,255,255,.55);margin:0;
}
.bruce-cta-row{
    display:flex;gap:14px;align-items:center;justify-content:center;flex-wrap:wrap;
    padding-top:8px;
}
.bruce-cta-primary{
    display:inline-flex;align-items:center;gap:10px;
    background:#FF7A1A;
    color:#fff;font-size:.9rem;font-weight:800;
    padding:14px 28px;border-radius:12px;text-decoration:none;
    transition:background .2s,transform .2s;letter-spacing:-.01em;
}
.bruce-cta-primary:hover{background:#ea580c;transform:translateY(-2px)}
.bruce-cta-secondary{
    display:inline-flex;align-items:center;gap:10px;
    background:rgba(255,255,255,.05);color:rgba(255,255,255,.85);
    border:1px solid rgba(255,255,255,.12);
    font-size:.9rem;font-weight:700;
    padding:13px 26px;border-radius:12px;text-decoration:none;
    transition:background .2s,border-color .2s;letter-spacing:-.01em;
}
.bruce-cta-secondary:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2)}
@media(max-width:900px){
    .bruce-header{grid-template-columns:1fr;gap:24px;text-align:center}
    .bruce-icon-wrap{align-items:center}
    .bruce-title-block .bt-eyebrow{justify-content:center;display:inline-flex}
    .bruce-pillars{grid-template-columns:repeat(2,1fr)}
    .bruce-title-block p{margin:0 auto}
}
@media(max-width:520px){
    .bruce-section{padding:80px 5% 70px}
    .bruce-pillars{grid-template-columns:1fr}
    .bruce-icon-wrap img{width:110px;height:110px;border-radius:26px}
}
</style>

<div class="bruce-inner">

    <div class="bruce-header">
        <div class="bruce-icon-wrap">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA">
            <span class="bruce-tag">Exclusivo Vivensi</span>
        </div>
        <div class="bruce-title-block">
            <div class="bt-eyebrow">
                <i class="fas fa-sparkles"></i> A inteligência da sua ONG
            </div>
            <h2>Bruce IA — o<br><em>assistente estratégico</em><br>da sua organização social.</h2>
            <p>
                Enquanto sua equipe cuida das pessoas, o Bruce lê seus dados, prospecta editais e patrocínios, monta relatórios, alerta sobre riscos e sugere o próximo passo estratégico. Feito pelo Vivensi, treinado para o terceiro setor brasileiro.
            </p>
        </div>
    </div>

    <div class="bruce-pillars">

        <div class="bp-card">
            <div class="bp-ico"><i class="fas fa-satellite-dish"></i></div>
            <div class="bp-title">Radar de Editais &amp; Patrocínios</div>
            <p class="bp-desc">Varre Transferegov, QueroDoação e empresas privadas 24/7. Sugere apenas oportunidades alinhadas à missão da sua ONG, com fit score e pitch pronto.</p>
        </div>

        <div class="bp-card">
            <div class="bp-ico"><i class="fas fa-chart-line"></i></div>
            <div class="bp-title">Análise de Indicadores</div>
            <p class="bp-desc">Cruza doações, projetos, beneficiários e financeiro. Detecta tendências, gargalos e desvios antes que virem problema.</p>
        </div>

        <div class="bp-card">
            <div class="bp-ico"><i class="fas fa-file-lines"></i></div>
            <div class="bp-title">Relatórios Automatizados</div>
            <p class="bp-desc">Prestação de contas, relatórios de impacto e narrativas para editais — gerados em segundos a partir dos dados da sua organização.</p>
        </div>

        <div class="bp-card">
            <div class="bp-ico"><i class="fas fa-brain"></i></div>
            <div class="bp-title">Assistente Estratégico</div>
            <p class="bp-desc">Sala de estratégia com 5 personas (financeira, captação, jurídico, comunicação, projetos). Você pergunta, Bruce responde com base nos seus dados reais.</p>
        </div>

    </div>

    <div class="bruce-cta-row">
        <a href="{{ route('register') }}" class="bruce-cta-primary">
            <i class="fas fa-rocket"></i> Ativar o Bruce na minha ONG
        </a>
        <a href="{{ route('solutions.ngo') }}" class="bruce-cta-secondary">
            <i class="fas fa-book-open"></i> Como o Bruce trabalha
        </a>
    </div>

</div>
</section>

<!-- ─── VIVENSI ACADEMY ─── -->
<section class="academy-universe" id="academy">
    <!-- Cosmic particles background (CSS-only) -->
    <div class="au-cosmos" aria-hidden="true">
        <span class="au-star" style="top:8%;left:12%;animation-delay:0s"></span>
        <span class="au-star" style="top:15%;left:72%;animation-delay:.8s"></span>
        <span class="au-star" style="top:35%;left:5%;animation-delay:1.4s"></span>
        <span class="au-star" style="top:60%;left:88%;animation-delay:.3s"></span>
        <span class="au-star" style="top:78%;left:22%;animation-delay:1.1s"></span>
        <span class="au-star" style="top:22%;left:45%;animation-delay:2s"></span>
        <span class="au-star au-star-lg" style="top:50%;left:50%;animation-delay:.6s"></span>
        <span class="au-star au-star-lg" style="top:80%;left:65%;animation-delay:1.8s"></span>
        <div class="au-orbit au-orbit-1"></div>
        <div class="au-orbit au-orbit-2"></div>
        <div class="au-nebula"></div>
    </div>

    <div class="au-container">
        <!-- LEFT COLUMN -->
        <div class="au-left aos">
            <div class="au-badge">
                <span class="au-badge-dot"></span>
                <i class="fas fa-graduation-cap"></i>
                Exclusivo para Gestores &amp; ONGs
            </div>
            <h2 class="au-title">
                <span class="au-title-white">Vivensi</span><br>
                <span class="au-title-grad">Academy</span>
            </h2>
            <p class="au-sub">Trilhas de aprendizado criadas para transformar gestores do terceiro setor em líderes de impacto. Conteúdo prático, especializado e incluído no seu plano.</p>

            <ul class="au-benefits">
                <li><span class="au-check"><i class="fas fa-check"></i></span> Captação de Recursos &amp; Editais</li>
                <li><span class="au-check"><i class="fas fa-check"></i></span> Gestão por Impacto e OKRs Sociais</li>
                <li><span class="au-check"><i class="fas fa-check"></i></span> Prestação de Contas para Auditores</li>
                <li><span class="au-check"><i class="fas fa-check"></i></span> Liderança de Equipes Voluntárias</li>
                <li><span class="au-check"><i class="fas fa-check"></i></span> Marketing Digital para o Terceiro Setor</li>
            </ul>

            <div class="au-ctas">
                <a href="{{ route('register') }}" class="au-btn-primary">
                    <i class="fas fa-rocket"></i> Quero Ter Acesso
                </a>
                <div class="au-vagas">
                    <div class="au-vagas-dots">
                        <span class="au-dot active"></span><span class="au-dot active"></span><span class="au-dot active"></span><span class="au-dot"></span><span class="au-dot"></span>
                    </div>
                    <span>Apenas 3 vagas restantes neste ciclo</span>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN — open editorial -->
        <div class="au-right aos">

            <!-- Numbers — sem wrapper externo -->
            <div class="au-nums">
                <div class="au-num-item">
                    <div class="au-num-val">{{ $siteStats['users_count'] }}+</div>
                    <div class="au-num-lbl">Usuários ativos</div>
                </div>
                <div class="au-num-item">
                    <div class="au-num-val">5</div>
                    <div class="au-num-lbl">Módulos</div>
                </div>
                <div class="au-num-item">
                    <div class="au-num-val">Bruce AI</div>
                    <div class="au-num-lbl">IA integrada</div>
                </div>
            </div>

            <!-- Featured module -->
            <div class="au-module-card">
                <div class="au-module-thumb">
                    <div class="au-module-thumb-icon"><i class="fas fa-play"></i></div>
                    <div class="au-module-live">
                        <span class="au-module-live-dot"></span>
                        <span class="au-module-live-txt">Ao vivo</span>
                    </div>
                </div>
                <div class="au-module-body">
                    <div class="au-module-tag">Trilha em andamento</div>
                    <div class="au-module-name">Gestão de Impacto<br>&amp; OKRs Sociais</div>
                    <div class="au-module-bar-wrap">
                        <div class="au-module-bar"><div class="au-module-bar-fill"></div></div>
                        <span class="au-module-pct">62% concluído</span>
                    </div>
                </div>
            </div>

            <!-- Testimonial — open quote -->
            <div class="au-test-open">
                <div class="au-test-mark">"</div>
                <p class="au-test-quote">A Vivensi Academy transformou completamente como gerencio minha ONG. Conteúdo prático, aplicado ao dia a dia do terceiro setor.</p>
                <div class="au-test-author">
                    <div class="au-test-av">MR</div>
                    <div>
                        <div class="au-test-name">Maria Rodrigues</div>
                        <div class="au-test-role">Diretora Executiva · ONG Vida Nova</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- IMPACT COUNTERS -->
<section class="impact" id="impacto">
    <div class="impact-inner">
        <div class="impact-head aos">
            <div>
                <div class="impact-eyebrow">Crescimento Real</div>
                <h2 class="impact-title">Plataforma em<br><em>expansão acelerada</em></h2>
            </div>
            <p class="impact-right-txt">O Vivensi não é só software — é o motor que permite organizações do terceiro setor e gestores de projetos operarem com excelência real, todos os dias.</p>
        </div>
        <div class="stats-row aos">
            <div class="stat-block sb-1">
                <div class="stat-num">{{ $siteStats['orgs_count'] }}+</div>
                <div class="stat-lbl">{{ $siteStats['orgs_label'] }}</div>
                <div class="stat-delta"><i class="fas fa-arrow-up" style="font-size:.56rem"></i> Em crescimento</div>
            </div>
            <div class="stat-block sb-2">
                <div class="stat-num">{{ $siteStats['projects_count'] }}+</div>
                <div class="stat-lbl">{{ $siteStats['projects_label'] }}</div>
                <div class="stat-delta"><i class="fas fa-arrow-up" style="font-size:.56rem"></i> Ativos na plataforma</div>
            </div>
            <div class="stat-block sb-3">
                <div class="stat-num">{{ $siteStats['users_count'] }}+</div>
                <div class="stat-lbl">{{ $siteStats['users_label'] }}</div>
                <div class="stat-delta"><i class="fas fa-arrow-up" style="font-size:.56rem"></i> {{ $siteStats['impact_label'] }}</div>
            </div>
            <div class="stat-block sb-4">
                <div class="stat-num">{{ $siteStats['rating_score'] }}/5</div>
                <div class="stat-lbl">{{ $siteStats['rating_label'] }}</div>
                <div class="stat-delta"><i class="fas fa-star" style="font-size:.56rem"></i> Pelos primeiros usuários</div>
            </div>
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
            {{-- Fallback quando SubscriptionPlan esta vazio — evita a home ficar "orfa" de preco. --}}
            <div style="grid-column:1/-1;text-align:center;padding:48px 24px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.08);border-radius:16px;color:rgba(255,255,255,.82)">
                <i class="fas fa-comments" style="font-size:2rem;display:block;margin-bottom:14px;color:#22c55e"></i>
                <h4 style="color:#fff;font-weight:800;font-size:1.15rem;margin:0 0 8px">Planos personalizados por porte</h4>
                <p style="color:rgba(255,255,255,.72);font-size:.9rem;max-width:520px;margin:0 auto 22px;line-height:1.6">
                    Cada organização tem realidades diferentes. Fale com a gente pra montar o plano do tamanho da sua operação.
                </p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
                    <a href="https://wa.me/551697618695?text=Ol%C3%A1%21%20Quero%20conhecer%20os%20planos%20do%20Vivensi."
                       target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:8px;background:#22c55e;color:#fff;padding:12px 22px;border-radius:10px;font-weight:700;font-size:.9rem;text-decoration:none">
                        <i class="fab fa-whatsapp"></i> Falar no WhatsApp
                    </a>
                    <a href="{{ route('register') }}"
                       style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:#fff;border:1px solid rgba(255,255,255,.22);padding:12px 22px;border-radius:10px;font-weight:600;font-size:.9rem;text-decoration:none">
                        Criar conta grátis
                    </a>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="cta-section" id="contato">
    <div class="cta-inner">

        <!-- LEFT: editorial big text + benefits -->
        <div class="aos">
            <h2 class="cta-big">Vamos<br><em>começar?</em></h2>
            <p class="cta-tagline">Sua organização merece tecnologia de ponta. Ative sua conta agora e transforme sua gestão.</p>
            <div class="cta-divider"></div>
            <div class="cta-benefits">
                <div>
                    <div class="cb-icon"><i class="fas fa-bolt"></i></div>
                    <div class="cb-title">Ativação imediata.</div>
                    <div class="cb-desc">Sua conta fica pronta em menos de 5 minutos.</div>
                </div>
                <div>
                    <div class="cb-icon"><i class="fas fa-shield-halved"></i></div>
                    <div class="cb-title">Cancele quando quiser!</div>
                </div>
                <div>
                    <div class="cb-icon"><i class="fas fa-headset"></i></div>
                    <div class="cb-title">Suporte humano.</div>
                    <div class="cb-desc">Time disponível por WhatsApp e e-mail.</div>
                </div>
                <div>
                    <div class="cb-icon"><i class="fas fa-rotate-left"></i></div>
                    <div class="cb-title">Garantia total.</div>
                    <div class="cb-desc">Devolvemos 100% se não gostar em 30 dias.</div>
                </div>
            </div>
        </div>

        <!-- RIGHT: white card -->
        <div class="cta-right aos">
            <div class="cta-form-title">Criar conta <span>agora</span></div>
            <p class="cta-form-sub">Pagamento Seguro · Cancele quando quiser</p>

            <!-- Social proof -->
            <div class="cta-proof">
                <div class="cta-proof-av">
                    <div class="cp-av">MR</div>
                    <div class="cp-av cp2">CS</div>
                    <div class="cp-av cp3">JA</div>
                    <div class="cp-av cp4">LF</div>
                </div>
                <div class="cta-proof-text">
                    <strong>+{{ $siteStats['orgs_count'] }} organizações</strong> já usam o Vivensi.<br>
                    Junte-se a quem já transformou sua gestão.
                </div>
            </div>

            <a href="{{ route('register') }}" class="btn-cta-main">
                <i class="fas fa-rocket"></i> Criar minha conta
            </a>
            <a href="{{ route('login') }}" class="btn-cta-sec">
                <i class="fas fa-arrow-right-to-bracket"></i> Já tenho uma conta
            </a>
            <p class="cta-form-note">
                Ao criar sua conta, você concorda com nossos
                <a href="{{ route('public.page', 'termos') }}">Termos de Uso</a> e
                <a href="{{ route('public.page', 'privacidade') }}">Política de Privacidade</a>.
            </p>
        </div>

    </div>
</section>

@include('components.blog-section')

<!-- FOOTER -->
<footer>
    <div class="footer-row">
        <div class="footer-col">
            <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi" style="height:32px;display:block;filter:brightness(0) invert(1);opacity:.6">
            <p class="footer-brand-desc">Tecnologia para quem transforma o Brasil. Gestão inteligente feita para o terceiro setor.</p>
        </div>
        <div class="footer-col">
            <h5>Soluções</h5>
            <a href="{{ route('solutions.ngo') }}">Terceiro Setor · ONGs</a>
            <a href="{{ route('solutions.manager') }}">Gestores de Projetos</a>
            <a href="{{ route('solutions.common') }}">MEI &amp; Empresas</a>
            <a href="#bruce-ia">Bruce IA</a>
            <a href="#features">Recursos</a>
        </div>
        <div class="footer-col">
            <h5>Produto</h5>
            <a href="#pricing">Planos &amp; Preços</a>
            <a href="#academy">Vivensi Academy</a>
            <a href="{{ route('login') }}">Acessar conta</a>
            <a href="{{ route('register') }}">Criar conta</a>
        </div>
        <div class="footer-col">
            <h5>Legal</h5>
            <a href="{{ route('public.page', 'privacidade') }}">Privacidade</a>
            <a href="{{ route('public.page', 'termos') }}">Termos de Uso</a>
            <a href="{{ route('public.page', 'sobre') }}">Sobre o Vivensi</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 VIVENSIAPP. Todos os direitos reservados.</span>
        <span class="fb-tagline">Feito com <span class="fb-heart">♥</span> para o terceiro setor brasileiro</span>
    </div>
    <div class="footer-owner" style="text-align:center; padding:16px 0 0; margin-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-size:.82rem; color:rgba(255,255,255,0.6);">
        VivensiApp é um produto da <strong>NC5 HUB DIGITAL LTDA</strong> · CNPJ: 67.848.807/0001-50
    </div>
</footer>

<script>
// Video Modal
const _videoUrl = @json($videoUrl ?? null);
function openVideoModal() {
    if (!_videoUrl) return;
    const modal = document.getElementById('videoModal');
    let src = _videoUrl;
    // Convert watch?v= to embed format
    src = src.replace('watch?v=', 'embed/').replace('youtu.be/', 'youtube.com/embed/');
    // Add autoplay
    src += (src.includes('?') ? '&' : '?') + 'autoplay=1&rel=0';
    document.getElementById('videoIframe').src = src;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeVideoModal(e) {
    if (e && e.target !== e.currentTarget && !e.target.classList.contains('video-modal-close')) return;
    const modal = document.getElementById('videoModal');
    modal.classList.remove('open');
    document.getElementById('videoIframe').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVideoModal(); });

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

// Navbar scroll — add .scrolled class + hide announce bar
const mainNav = document.getElementById('mainNav');
const announceBar = document.getElementById('announceBar');
window.addEventListener('scroll', () => {
    if (window.scrollY > 80) {
        mainNav.classList.add('scrolled');
        if (announceBar) announceBar.style.transform = 'translateY(-100%)';
    } else {
        mainNav.classList.remove('scrolled');
        if (announceBar) announceBar.style.transform = '';
    }
}, { passive: true });

// Mobile menu toggle
const mobileToggle = document.getElementById('mobileToggle');
const mobileMenu   = document.getElementById('mobileMenu');
const menuIcon     = document.getElementById('menuIcon');
if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        const open = mobileMenu.classList.toggle('open');
        menuIcon.className = open ? 'fas fa-xmark' : 'fas fa-bars';
    });
}

// Mobile menu close on outside click
document.addEventListener('click',e=>{
    const menu=document.getElementById('mobileMenu');
    if(menu.classList.contains('open')&&!e.target.closest('#mobileMenu')&&!e.target.closest('.mobile-btn'))
        menu.classList.remove('open');
});

// Platform accordion
function ptToggle(id) {
    var accord = document.getElementById('pt-' + id);
    var isOpen = accord.classList.contains('pt-open');
    document.querySelectorAll('.pt-accord').forEach(function(a) { a.classList.remove('pt-open'); });
    if (!isOpen) { accord.classList.add('pt-open'); }
}
</script>
@include('partials.whatsapp-button')

{{-- ═══ Widget de Agendamento Flutuante ═══ --}}
<style>
    .booking-widget {
        position: fixed;
        bottom: 90px; /* acima do botão WhatsApp */
        right: 24px;
        z-index: 9990;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 12px;
    }
    .bw-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
        padding: 18px 20px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        width: 236px;
        cursor: pointer;
        transition: transform .2s, box-shadow .2s;
        animation: bwSlideIn .4s cubic-bezier(.34,1.56,.64,1) both;
        text-decoration: none;
    }
    .bw-card:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(0,0,0,.22); }
    @keyframes bwSlideIn {
        from { opacity:0; transform:translateY(20px) scale(.95); }
        to   { opacity:1; transform:translateY(0) scale(1); }
    }
    .bw-avatar {
        width: 44px; height: 44px; flex-shrink: 0;
        background: linear-gradient(135deg, #4f6ef7, #7c3aed);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; font-weight: 900; color: #fff;
    }
    .bw-text { flex: 1; min-width: 0; }
    .bw-label {
        font-size: .62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #94a3b8;
        margin-bottom: 2px;
        font-family: 'Inter', -apple-system, sans-serif;
    }
    .bw-title {
        font-size: .85rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        font-family: 'Inter', -apple-system, sans-serif;
    }
    .bw-arrow {
        width: 28px; height: 28px; flex-shrink: 0;
        background: #4f6ef7;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .bw-arrow svg { color: #fff; }

    .bw-close {
        width: 28px; height: 28px;
        background: rgba(0,0,0,.35);
        border: none;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        align-self: flex-end;
        transition: background .2s;
        flex-shrink: 0;
    }
    .bw-close:hover { background: rgba(0,0,0,.55); }
    .bw-close svg { color: #fff; }

    .bw-pill {
        background: #4f6ef7;
        color: #fff;
        border: none;
        border-radius: 24px;
        padding: 10px 18px;
        font-size: .82rem;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 16px rgba(79,110,247,.4);
        transition: background .2s, transform .15s;
        font-family: 'Inter', -apple-system, sans-serif;
    }
    .bw-pill:hover { background: #3b5bd9; transform: scale(1.04); }
    .bw-pill .bw-dot {
        width: 7px; height: 7px;
        background: #4ade80;
        border-radius: 50%;
        animation: bwPulse 2s infinite;
    }
    @keyframes bwPulse {
        0%,100% { opacity:1; transform:scale(1); }
        50% { opacity:.5; transform:scale(1.4); }
    }

    .booking-widget.collapsed .bw-card { display: none; }
</style>

<div class="booking-widget" id="bookingWidget">
    <a href="{{ route('booking.index') }}" class="bw-card" id="bwCard">
        <div class="bw-avatar">V</div>
        <div class="bw-text">
            <p class="bw-label">Equipe Vivensi</p>
            <p class="bw-title">Agendar uma conversa</p>
        </div>
        <div class="bw-arrow">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
    </a>
    <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;">
        <button class="bw-close" id="bwClose" title="Fechar">
            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <button class="bw-pill" onclick="window.location='{{ route('booking.index') }}'">
            <span class="bw-dot"></span>
            Falar com a equipe
        </button>
    </div>
</div>

<script>
(function() {
    const widget = document.getElementById('bookingWidget');
    const closeBtn = document.getElementById('bwClose');
    const HIDDEN_KEY = 'bw_hidden_until';

    // Hide for 1 hour after close
    const hiddenUntil = localStorage.getItem(HIDDEN_KEY);
    if (hiddenUntil && Date.now() < parseInt(hiddenUntil)) {
        widget.style.display = 'none';
        return;
    }

    // Show after 3s scroll or 8s timer
    let shown = false;
    function showWidget() {
        if (shown) return;
        shown = true;
        widget.style.display = 'flex';
    }

    widget.style.display = 'none';
    setTimeout(showWidget, 8000);
    window.addEventListener('scroll', function onScroll() {
        if (window.scrollY > 400) { showWidget(); window.removeEventListener('scroll', onScroll); }
    }, { passive: true });

    closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        widget.style.display = 'none';
        localStorage.setItem(HIDDEN_KEY, Date.now() + 3600000); // 1 hora
    });
})();
</script>
@include('partials.bruce_public_popup')
</body>
</html>
