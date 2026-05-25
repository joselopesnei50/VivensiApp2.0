<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi para MEI e PME | Gestão Financeira e Operacional Completa</title>
    <meta name="description" content="O sistema de gestão que MEIs e PMEs precisavam: fluxo de caixa, notas fiscais, cobranças, CRM de clientes e IA — tudo em um único lugar.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#090909;
    --bg2:#111111;
    --bg3:#161616;
    --border:rgba(255,255,255,.07);
    --border-hover:rgba(255,255,255,.15);
    --accent:#10b981;
    --accent-dark:#059669;
    --accent-dim:rgba(16,185,129,.12);
    --accent-border:rgba(16,185,129,.25);
    --blue:#4F6EF7;
    --teal:#00D4AA;
    --gold:#F5A623;
    --purple:#8B5CF6;
    --white:#FFFFFF;
    --text-dim:rgba(255,255,255,.45);
    --text-muted:rgba(255,255,255,.25);
    --glass:rgba(255,255,255,.04);
    --glass-border:rgba(255,255,255,.09);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--white);overflow-x:hidden;line-height:1.6}

/* ══ NAV ════════════════════════════════════════════════════════ */
.nav{
    position:fixed;top:0;left:0;right:0;z-index:999;
    display:flex;justify-content:space-between;align-items:center;
    padding:0 5%;height:64px;
    background:rgba(9,9,9,.9);
    backdrop-filter:blur(20px) saturate(180%);
    border-bottom:1px solid var(--border);
    transition:all .3s;
}
.nav-logo img{height:30px;display:block}
.nav-links{display:flex;gap:4px;list-style:none;align-items:center}
.nav-links a{color:var(--text-dim);text-decoration:none;font-size:.83rem;font-weight:500;padding:7px 13px;border-radius:8px;transition:all .2s;white-space:nowrap}
.nav-links a:hover{color:var(--white);background:rgba(255,255,255,.06)}
.nav-links a.active{color:var(--white)}
.nav-ctas{display:flex;gap:8px;align-items:center}
.btn-nav-ghost{color:var(--text-dim);text-decoration:none;font-size:.83rem;font-weight:600;padding:8px 16px;border-radius:8px;border:1px solid var(--border);transition:all .2s;background:transparent}
.btn-nav-ghost:hover{color:var(--white);border-color:rgba(255,255,255,.18);background:rgba(255,255,255,.06)}
.btn-nav-solid{background:var(--accent);color:#fff;text-decoration:none;font-size:.83rem;font-weight:700;padding:9px 20px;border-radius:8px;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.btn-nav-solid:hover{background:var(--accent-dark);transform:translateY(-1px);box-shadow:0 4px 16px rgba(16,185,129,.35)}
@media(max-width:860px){.nav-links{display:none}}

/* ══ HERO ════════════════════════════════════════════════════════ */
.hero{
    position:relative;min-height:100vh;display:flex;align-items:center;
    padding:120px 5% 80px;overflow:hidden;background:var(--bg);
}
.hero::before{
    content:'';position:absolute;inset:0;
    background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);
    background-size:72px 72px;pointer-events:none;
}
.hero::after{
    content:'';position:absolute;width:700px;height:700px;
    top:-100px;right:-100px;border-radius:50%;
    background:radial-gradient(circle,rgba(16,185,129,.09) 0%,rgba(0,212,170,.05) 40%,transparent 70%);
    filter:blur(70px);pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:1240px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--accent-dim);border:1px solid var(--accent-border);color:#6ee7b7;font-size:.72rem;font-weight:800;padding:5px 14px;border-radius:100px;margin-bottom:24px;text-transform:uppercase;letter-spacing:.07em}
.badge-dot{width:6px;height:6px;border-radius:50%;background:#6ee7b7;animation:pDot 2s infinite}
@keyframes pDot{0%,100%{opacity:1}50%{opacity:.3}}

.hero-title{font-size:clamp(2.4rem,5vw,4rem);font-weight:900;line-height:1.04;letter-spacing:-.04em;margin-bottom:20px;color:var(--white)}
.hero-title em{font-style:normal;color:var(--accent)}
.hero-sub{font-size:1.05rem;color:var(--text-dim);line-height:1.75;max-width:480px;margin-bottom:36px}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:52px}

.btn-hero{display:inline-flex;align-items:center;gap:9px;background:var(--accent);color:#fff;text-decoration:none;font-weight:700;font-size:.95rem;padding:14px 30px;border-radius:10px;transition:all .2s}
.btn-hero:hover{background:var(--accent-dark);transform:translateY(-2px);box-shadow:0 8px 28px rgba(16,185,129,.4)}
.btn-hero-outline{display:inline-flex;align-items:center;gap:9px;color:var(--text-dim);text-decoration:none;font-weight:600;font-size:.95rem;padding:14px 26px;border-radius:10px;border:1px solid var(--border);transition:all .2s;background:transparent}
.btn-hero-outline:hover{color:var(--white);background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.18)}

.hero-stats{display:flex;gap:32px;flex-wrap:wrap}
.stat-item .snum{font-size:1.8rem;font-weight:900;letter-spacing:-.04em;color:var(--white)}
.stat-item .slbl{font-size:.68rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-top:2px}

/* RIGHT — dashcard */
.hero-visual{position:relative}
.dashcard{background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:24px;overflow:hidden}
.dc-header{display:flex;align-items:center;gap:8px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)}
.dcd{width:9px;height:9px;border-radius:50%}
.dc-title{font-size:.82rem;font-weight:700;color:rgba(255,255,255,.55);margin-left:6px}
.dc-kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}
.dc-kpi{background:var(--glass);border:1px solid var(--border);border-radius:10px;padding:12px}
.dc-kpi-lbl{font-size:.62rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
.dc-kpi-val{font-size:1.35rem;font-weight:900;letter-spacing:-.03em}
.kv-green{color:var(--accent)}.kv-teal{color:var(--teal)}.kv-blue{color:var(--blue)}.kv-gold{color:var(--gold)}
.dc-bars{display:flex;flex-direction:column;gap:10px}
.dc-bar-top{display:flex;justify-content:space-between;font-size:.65rem;color:var(--text-muted);font-weight:600;margin-bottom:5px}
.dc-bar-track{height:5px;background:rgba(255,255,255,.07);border-radius:100px;overflow:hidden}
.dc-bar-fill{height:100%;border-radius:100px}

/* Floating notifications */
.hero-notif{position:absolute;display:flex;align-items:center;gap:9px;background:rgba(12,12,18,.92);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:12px;padding:10px 14px;white-space:nowrap;box-shadow:0 8px 32px rgba(0,0,0,.5);z-index:3}
.hn-1{bottom:-24px;left:-30px;animation:floatBadge 4s ease-in-out infinite}
.hn-2{top:-20px;right:-20px;animation:floatBadge 4.5s ease-in-out infinite .8s}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.hn-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.hni-green{background:rgba(16,185,129,.15);color:#6ee7b7}
.hni-blue{background:rgba(79,110,247,.15);color:var(--blue)}
.hn-text .hn-lbl{font-size:.58rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.hn-text .hn-val{font-size:.8rem;font-weight:800;color:var(--white)}

@media(max-width:860px){.hero-inner{grid-template-columns:1fr}.hero-visual{display:none}}

/* ══ TRUST BAR ════════════════════════════════════════════════ */
.trust{padding:18px 5%;background:var(--glass);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.trust-row{display:flex;align-items:center;justify-content:center;gap:36px;flex-wrap:wrap}
.trust-lbl{font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.09em}
.trust-item{display:flex;align-items:center;gap:7px;color:var(--text-dim);font-size:.8rem;font-weight:600}
.trust-item i{color:var(--accent)}

/* ══ SECTION COMMONS ══════════════════════════════════════════ */
.section{padding:100px 5%;max-width:1240px;margin:0 auto}
.section-tag{display:inline-flex;align-items:center;gap:7px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px}
.st-green{color:#6ee7b7}.st-blue{color:#93a8ff}.st-teal{color:var(--teal)}.st-gold{color:var(--gold)}.st-purple{color:#c4b5fd}
.section-title{font-size:clamp(1.9rem,3.5vw,2.8rem);font-weight:900;line-height:1.08;letter-spacing:-.035em;margin-bottom:16px;color:var(--white)}
.section-sub{font-size:.95rem;color:var(--text-dim);line-height:1.75;max-width:560px}
.center{text-align:center;margin-left:auto;margin-right:auto}

/* fade-up reveal */
.reveal{opacity:0;transform:translateY(28px);transition:opacity .7s cubic-bezier(.22,1,.36,1),transform .7s cubic-bezier(.22,1,.36,1)}
.reveal.in{opacity:1;transform:translateY(0)}
.reveal-d1{transition-delay:.08s}.reveal-d2{transition-delay:.16s}.reveal-d3{transition-delay:.24s}
.reveal-d4{transition-delay:.32s}.reveal-d5{transition-delay:.40s}.reveal-d6{transition-delay:.48s}
.reveal-d7{transition-delay:.56s}.reveal-d8{transition-delay:.64s}.reveal-d9{transition-delay:.72s}
.reveal-d10{transition-delay:.80s}.reveal-d11{transition-delay:.88s}.reveal-d12{transition-delay:.96s}

/* ══ PROBLEM / SOLUTION ══════════════════════════════════════ */
.prob-section{padding:100px 5%;background:rgba(16,185,129,.03);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.prob-grid{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1240px;margin:0 auto}
.pain-list{display:flex;flex-direction:column;gap:14px;margin-top:36px}
.pain-item{display:flex;align-items:flex-start;gap:14px;padding:18px;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.14);border-radius:14px}
.pain-icon{width:40px;height:40px;background:rgba(16,185,129,.14);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#6ee7b7;font-size:1rem;flex-shrink:0}
.pain-text h4{font-size:.9rem;font-weight:700;margin-bottom:3px}
.pain-text p{font-size:.8rem;color:var(--text-dim);line-height:1.5}
@media(max-width:860px){.prob-grid{grid-template-columns:1fr;gap:40px}}

/* ══ FEATURES BOXES GRID ══════════════════════════════════════ */
.features-wrap{padding:100px 5%;background:var(--bg)}
.feat-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
    max-width:1240px;margin:52px auto 0;
}

/* Feature Card */
.fc{
    background:var(--bg2);
    border:1px solid var(--border);
    border-radius:16px;
    padding:28px;
    transition:border-color .3s,transform .3s,box-shadow .3s;
    cursor:default;
    position:relative;overflow:hidden;
}
.fc::before{
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:var(--fc-accent,var(--accent));
    opacity:0;transition:opacity .3s;
}
.fc:hover{
    border-color:var(--border-hover);
    transform:translateY(-5px);
    box-shadow:0 20px 48px rgba(0,0,0,.45);
}
.fc:hover::before{opacity:1}

.fc-icon{
    width:46px;height:46px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;margin-bottom:18px;
    transition:transform .3s;
}
.fc:hover .fc-icon{transform:scale(1.1)}
.fi-green{background:rgba(16,185,129,.12);color:#6ee7b7;--fc-accent:#10b981}
.fi-blue{background:rgba(79,110,247,.12);color:#93a8ff;--fc-accent:#4F6EF7}
.fi-teal{background:rgba(0,212,170,.12);color:var(--teal);--fc-accent:#00D4AA}
.fi-gold{background:rgba(245,166,35,.12);color:var(--gold);--fc-accent:#F5A623}
.fi-purple{background:rgba(139,92,246,.12);color:#c4b5fd;--fc-accent:#8B5CF6}
.fi-sky{background:rgba(56,189,248,.12);color:#7dd3fc;--fc-accent:#38bdf8}
.fi-orange{background:rgba(251,146,60,.12);color:#fb923c;--fc-accent:#fb923c}
.fi-pink{background:rgba(244,114,182,.12);color:#f472b6;--fc-accent:#f472b6}
.fi-emerald{background:rgba(52,211,153,.12);color:#34d399;--fc-accent:#34d399}

.fc h3{font-size:.95rem;font-weight:800;margin-bottom:8px;color:var(--white)}
.fc p{font-size:.8rem;color:var(--text-dim);line-height:1.65}
.fc-tag{display:inline-flex;align-items:center;gap:4px;margin-top:14px;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:3px 9px;border-radius:100px}
.ft-new{background:rgba(0,212,170,.12);color:var(--teal);border:1px solid rgba(0,212,170,.2)}
.ft-pro{background:rgba(245,166,35,.12);color:var(--gold);border:1px solid rgba(245,166,35,.2)}
.ft-ai{background:rgba(139,92,246,.12);color:#c4b5fd;border:1px solid rgba(139,92,246,.2)}

@media(max-width:1024px){.feat-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.feat-grid{grid-template-columns:1fr}}

/* ══ MODULE SPOTLIGHT ════════════════════════════════════════ */
.spotlight{padding:80px 5%;background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.spotlight-inner{max-width:1240px;margin:0 auto}
.spotlight-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:48px}
.spot-card{border-radius:20px;padding:36px;position:relative;overflow:hidden;border:1px solid var(--border)}
.spot-card-green{background:#060e09}
.spot-card-blue{background:#060812}
.spot-accent-bar{position:absolute;top:0;left:0;right:0;height:3px}
.sab-green{background:var(--accent)}.sab-blue{background:var(--blue)}
.spot-badge{display:inline-flex;align-items:center;gap:6px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 10px;border-radius:100px;margin-bottom:16px}
.sbg-green{background:rgba(16,185,129,.12);color:#6ee7b7;border:1px solid rgba(16,185,129,.2)}
.sbg-blue{background:rgba(79,110,247,.12);color:#93a8ff;border:1px solid rgba(79,110,247,.2)}
.spot-title{font-size:1.4rem;font-weight:900;letter-spacing:-.03em;margin-bottom:10px;color:var(--white)}
.spot-desc{font-size:.85rem;color:var(--text-dim);line-height:1.7;margin-bottom:24px}
.spot-feats{list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:28px}
.spot-feats li{display:flex;align-items:center;gap:9px;font-size:.82rem;color:rgba(255,255,255,.6)}
.spot-feats li i{font-size:.75rem;flex-shrink:0}
.sfi-green{color:#6ee7b7}.sfi-blue{color:#93a8ff}
.spot-demo{margin-top:auto;background:var(--glass);border:1px solid var(--border);border-radius:12px;padding:14px}

/* Cash flow demo */
.cf-demo{display:flex;flex-direction:column;gap:8px}
.cf-row{display:flex;justify-content:space-between;align-items:center;padding:8px 10px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)}
.cf-label{font-size:.7rem;font-weight:600;color:rgba(255,255,255,.55)}
.cf-value-pos{font-size:.78rem;font-weight:800;color:#6ee7b7}
.cf-value-neg{font-size:.78rem;font-weight:800;color:#f87171}
.cf-balance{display:flex;justify-content:space-between;align-items:baseline;margin-top:6px;padding-top:8px;border-top:1px solid var(--border)}
.cf-balance-lbl{font-size:.65rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em}
.cf-balance-val{font-size:1.2rem;font-weight:900;color:var(--accent)}

/* Invoice demo */
.inv-demo{display:flex;flex-direction:column;gap:8px}
.inv-row{display:flex;align-items:center;gap:8px;padding:8px 10px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)}
.inv-status{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.s-paid{background:#4ade80}.s-pending{background:var(--gold)}.s-overdue{background:#f87171}
.inv-info{flex:1}
.inv-name{font-size:.7rem;font-weight:700;color:rgba(255,255,255,.7)}
.inv-date{font-size:.6rem;color:var(--text-muted)}
.inv-amount{font-size:.75rem;font-weight:800;color:var(--white)}

@media(max-width:860px){.spotlight-grid{grid-template-columns:1fr}}

/* ══ PARALLAX BOOKING SECTION ════════════════════════════════ */
.parallax-cta{
    position:relative;
    padding:120px 5%;
    overflow:hidden;
    background:#06060e;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
}
.parallax-bg{
    position:absolute;inset:0;z-index:0;
    background-image:
        radial-gradient(ellipse 60% 80% at 20% 50%, rgba(16,185,129,.08) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 80% 30%, rgba(79,110,247,.07) 0%, transparent 60%);
    will-change:transform;
}
.parallax-grid{
    position:absolute;inset:0;z-index:0;
    background-image:linear-gradient(rgba(255,255,255,.018) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.018) 1px,transparent 1px);
    background-size:72px 72px;
}
.parallax-inner{
    position:relative;z-index:2;
    max-width:760px;margin:0 auto;text-align:center;
}
.parallax-eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--accent-dim);border:1px solid var(--accent-border);
    color:#6ee7b7;font-size:.7rem;font-weight:800;padding:5px 14px;border-radius:100px;
    margin-bottom:28px;text-transform:uppercase;letter-spacing:.08em;
}
.plx-dot{width:6px;height:6px;border-radius:50%;background:#6ee7b7;animation:pDot 1.8s infinite}

.parallax-title{
    font-size:clamp(2rem,5vw,3.4rem);
    font-weight:900;line-height:1.06;letter-spacing:-.04em;
    color:var(--white);margin-bottom:20px;
}
.parallax-sub{
    font-size:1rem;color:var(--text-dim);line-height:1.75;max-width:520px;margin:0 auto 40px;
}
.btn-plx{
    display:inline-flex;align-items:center;gap:10px;
    background:var(--accent);color:#fff;
    text-decoration:none;font-weight:700;font-size:1rem;
    padding:16px 36px;border-radius:12px;
    transition:all .25s;
}
.btn-plx:hover{background:var(--accent-dark);transform:translateY(-3px);box-shadow:0 12px 36px rgba(16,185,129,.4)}
.btn-plx-ghost{
    display:inline-flex;align-items:center;gap:10px;
    background:transparent;color:var(--text-dim);
    text-decoration:none;font-weight:600;font-size:1rem;
    padding:16px 28px;border-radius:12px;
    border:1px solid var(--border);transition:all .25s;
}
.btn-plx-ghost:hover{color:var(--white);border-color:var(--border-hover);background:rgba(255,255,255,.05)}
.plx-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}

.plx-trust{display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap}
.plxt{display:flex;align-items:center;gap:7px;font-size:.76rem;color:var(--text-muted);font-weight:600}
.plxt i{color:var(--accent);font-size:.7rem}

/* ══ PRICING ═════════════════════════════════════════════════ */
.pricing-wrap{padding:100px 5%;background:var(--bg)}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:2px;max-width:980px;margin:48px auto 0;border:1px solid var(--border);border-radius:20px;overflow:hidden}
.price-card{background:var(--bg2);padding:36px;position:relative;transition:background .25s;border-right:1px solid var(--border)}
.price-card:last-child{border-right:none}
.price-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:transparent;transition:background .3s}
.price-card.hot{background:rgb(7,14,10)}
.price-card.hot::before{background:var(--accent)}
.price-card:hover{background:var(--bg3)}
.hot-badge{position:absolute;top:18px;right:18px;background:var(--accent);color:#fff;font-size:.6rem;font-weight:800;padding:3px 10px;border-radius:100px;text-transform:uppercase;letter-spacing:.06em}
.price-name{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:16px}
.price-val{font-size:2.6rem;font-weight:900;line-height:1;letter-spacing:-.04em;color:var(--white);margin-bottom:4px}
.price-val .cur{font-size:.95rem;font-weight:700;vertical-align:top;margin-top:5px;display:inline-block;color:rgba(255,255,255,.4)}
.price-val .per{font-size:.82rem;font-weight:400;color:var(--text-muted)}
.price-note{font-size:.68rem;color:var(--text-muted);margin-bottom:22px}
.price-divider{height:1px;background:var(--border);margin:18px 0}
.price-feats{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:28px}
.price-feats li{display:flex;align-items:center;gap:9px;font-size:.8rem;color:rgba(255,255,255,.6)}
.price-feats li i{color:var(--accent);font-size:.74rem;flex-shrink:0}
.btn-price{display:block;text-align:center;padding:13px;border-radius:10px;font-weight:700;font-size:.85rem;text-decoration:none;transition:all .2s;cursor:pointer;width:100%;border:none;font-family:'Inter',sans-serif}
.bp-solid{background:var(--accent);color:#fff}
.bp-solid:hover{background:var(--accent-dark);transform:translateY(-1px)}
.bp-outline{border:1px solid var(--border);color:rgba(255,255,255,.6);background:transparent}
.bp-outline:hover{border-color:var(--border-hover);background:rgba(255,255,255,.05);color:var(--white)}

/* billing toggle */
.billing-toggle{display:flex;align-items:center;justify-content:center;gap:12px;margin:28px 0 0}
.tgl-lbl{font-size:.85rem;font-weight:600;color:var(--text-muted);transition:color .3s}
.tgl-lbl.on{color:var(--white)}
.switch{position:relative;display:inline-block;width:46px;height:24px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:rgba(255,255,255,.12);border-radius:24px;transition:.3s}
.slider::before{content:'';position:absolute;height:16px;width:16px;left:4px;bottom:4px;background:#fff;border-radius:50%;transition:.3s}
input:checked+.slider{background:var(--accent)}
input:checked+.slider::before{transform:translateX(22px)}
.disc-badge{background:rgba(74,222,128,.12);color:var(--teal);font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:100px}

@media(max-width:860px){.price-grid{grid-template-columns:1fr;border-radius:16px}.price-card{border-right:none;border-bottom:1px solid var(--border)}.price-card:last-child{border-bottom:none}}

/* ══ FOOTER ══════════════════════════════════════════════════ */
footer{background:#060606;border-top:1px solid var(--border);padding:60px 5% 28px}
.footer-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
.footer-brand p{font-size:.78rem;color:var(--text-muted);line-height:1.75;margin-top:12px;max-width:200px}
.footer-col h5{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:14px}
.footer-col a{display:block;color:rgba(255,255,255,.38);text-decoration:none;font-size:.8rem;margin-bottom:9px;transition:color .2s}
.footer-col a:hover{color:var(--white)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.05);padding-top:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.footer-bottom span{font-size:.7rem;color:rgba(255,255,255,.18)}
.fb-heart{color:rgba(16,185,129,.7)}
@media(max-width:768px){.footer-row{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.footer-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
    <a href="{{ url('/') }}" class="nav-logo">
        <img loading="lazy" src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
    </a>
    <ul class="nav-links">
        <li><a href="{{ route('solutions.ngo') }}">Terceiro Setor</a></li>
        <li><a href="{{ route('solutions.manager') }}">Gestores</a></li>
        <li><a href="{{ route('solutions.common') }}" class="active">MEI/PME</a></li>
        <li><a href="#features">Funcionalidades</a></li>
        <li><a href="#pricing">Planos</a></li>
    </ul>
    <div class="nav-ctas">
        <a href="{{ route('login') }}" class="btn-nav-ghost">Entrar</a>
        <a href="#pricing" class="btn-nav-solid">
            <i class="fas fa-rocket"></i> Começar Agora
        </a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <!-- LEFT -->
        <div class="hero-left">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                Gestão Inteligente para MEI e PME
            </div>

            <h1 class="hero-title">
                Seu negócio organizado,<br>seu <em>lucro visível</em>
            </h1>

            <p class="hero-sub">
                Fluxo de caixa, notas fiscais, cobranças, CRM de clientes e IA de atendimento — tudo em um sistema feito para quem empreende com determinação.
            </p>

            <div class="hero-actions">
                <a href="#pricing" class="btn-hero">
                    <i class="fas fa-chart-line"></i> Ver Planos
                </a>
                <a href="#features" class="btn-hero-outline">
                    <i class="fas fa-play" style="font-size:.75rem"></i> Ver Recursos
                </a>
            </div>

            <div class="hero-stats">
                <div class="stat-item">
                    <div class="snum">5.800+</div>
                    <div class="slbl">Empresas Ativas</div>
                </div>
                <div class="stat-item">
                    <div class="snum">R$ 98M+</div>
                    <div class="slbl">Gerenciados</div>
                </div>
                <div class="stat-item">
                    <div class="snum">4.8/5</div>
                    <div class="slbl">Avaliação</div>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="hero-visual">
            <div style="position:relative">
                <!-- Floating top notification -->
                <div class="hero-notif hn-2">
                    <div class="hn-icon hni-blue"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="hn-text">
                        <div class="hn-lbl">Cobrança paga</div>
                        <div class="hn-val">Empresa XYZ · R$ 3.200</div>
                    </div>
                </div>

                <div class="dashcard">
                    <div class="dc-header">
                        <div class="dcd" style="background:#FF5F57"></div>
                        <div class="dcd" style="background:#FEBC2E"></div>
                        <div class="dcd" style="background:#28C840"></div>
                        <span class="dc-title">Vivensi — Painel MEI/PME</span>
                    </div>
                    <div class="dc-kpis">
                        <div class="dc-kpi">
                            <div class="dc-kpi-lbl">Faturamento/mês</div>
                            <div class="dc-kpi-val kv-green">R$ 42k</div>
                        </div>
                        <div class="dc-kpi">
                            <div class="dc-kpi-lbl">Lucro Líquido</div>
                            <div class="dc-kpi-val kv-teal">R$ 18k</div>
                        </div>
                        <div class="dc-kpi">
                            <div class="dc-kpi-lbl">A Receber</div>
                            <div class="dc-kpi-val kv-gold">R$ 9.400</div>
                        </div>
                        <div class="dc-kpi">
                            <div class="dc-kpi-lbl">Clientes Ativos</div>
                            <div class="dc-kpi-val kv-blue">87</div>
                        </div>
                    </div>
                    <div class="dc-bars">
                        <div class="dc-bar-row">
                            <div class="dc-bar-top"><span>Meta de Faturamento</span><span style="color:var(--accent)">84%</span></div>
                            <div class="dc-bar-track"><div class="dc-bar-fill" style="background:var(--accent);width:84%"></div></div>
                        </div>
                        <div class="dc-bar-row">
                            <div class="dc-bar-top"><span>Notas Emitidas</span><span style="color:var(--blue)">31/40</span></div>
                            <div class="dc-bar-track"><div class="dc-bar-fill" style="background:var(--blue);width:78%"></div></div>
                        </div>
                        <div class="dc-bar-row">
                            <div class="dc-bar-top"><span>Inadimplência</span><span style="color:var(--teal)">2%</span></div>
                            <div class="dc-bar-track"><div class="dc-bar-fill" style="background:var(--teal);width:2%"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Floating bottom notification -->
                <div class="hero-notif hn-1">
                    <div class="hn-icon hni-green"><i class="fas fa-robot"></i></div>
                    <div class="hn-text">
                        <div class="hn-lbl">Bruce AI</div>
                        <div class="hn-val">Cliente respondido via WhatsApp</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
    <div class="trust-row">
        <span class="trust-lbl">Confiado por MEIs e PMEs em todo o Brasil</span>
        <div class="trust-item"><i class="fas fa-shield-check"></i> LGPD Compliant</div>
        <div class="trust-item"><i class="fas fa-server"></i> AWS Brasil</div>
        <div class="trust-item"><i class="fas fa-lock"></i> SSL + 2FA</div>
        <div class="trust-item"><i class="fas fa-star"></i> 4.8/5 pelos usuários</div>
        <div class="trust-item"><i class="fas fa-file-invoice"></i> Integrado com NF-e</div>
    </div>
</div>

<!-- PROBLEM → SOLUTION -->
<section class="prob-section" id="problema">
    <div class="prob-grid">
        <div class="reveal">
            <div class="section-tag st-green"><i class="fas fa-triangle-exclamation"></i> O Problema Real</div>
            <h2 class="section-title">MEI e PME perdem dinheiro<br>por falta de <em style="font-style:normal;color:var(--accent)">controle</em>.</h2>
            <p class="section-sub">Planilhas que não fecham. Clientes que somem sem pagar. Notas fiscais no caos. Fluxo de caixa só na cabeça. Isso trava o crescimento do seu negócio.</p>
            <div class="pain-list">
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="pain-text"><h4>Fluxo de caixa no escuro</h4><p>Sem visibilidade do que entra e sai, fica impossível planejar crescimento com segurança.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="pain-text"><h4>Cobranças esquecidas</h4><p>Clientes inadimplentes acumulando enquanto você não tem sistema para cobrar automaticamente.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-users-slash"></i></div>
                    <div class="pain-text"><h4>Clientes sem acompanhamento</h4><p>Nenhum CRM para registrar histórico, proposta, contrato e status de cada cliente.</p></div>
                </div>
            </div>
        </div>
        <div class="reveal reveal-d2">
            <div class="dashcard" style="background:#0a0f0b;border-color:rgba(16,185,129,.15)">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)">
                    <div style="width:9px;height:9px;border-radius:50%;background:#FF5F57"></div>
                    <div style="width:9px;height:9px;border-radius:50%;background:#FEBC2E"></div>
                    <div style="width:9px;height:9px;border-radius:50%;background:#28C840"></div>
                    <span style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.45);margin-left:6px">Vivensi — Central do Negócio</span>
                </div>
                <!-- Live activity feed -->
                <div style="display:flex;flex-direction:column;gap:9px">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(16,185,129,.14);display:flex;align-items:center;justify-content:center;color:#6ee7b7;font-size:.85rem;flex-shrink:0"><i class="fas fa-money-bill-wave"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Cobrança paga via Pix</div><div style="font-size:.65rem;color:var(--text-muted)">Cliente: Tech Solutions · R$ 5.800</div></div>
                        <div style="font-size:.65rem;color:var(--accent);font-weight:700">PAGO</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(79,110,247,.14);display:flex;align-items:center;justify-content:center;color:#93a8ff;font-size:.85rem;flex-shrink:0"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">NF-e emitida automaticamente</div><div style="font-size:.65rem;color:var(--text-muted)">NF 0094 · R$ 3.200 · SEFAZ OK</div></div>
                        <div style="font-size:.65rem;color:#4ade80;font-weight:700">✓</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(139,92,246,.14);display:flex;align-items:center;justify-content:center;color:#c4b5fd;font-size:.85rem;flex-shrink:0"><i class="fas fa-robot"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Bruce AI respondeu cliente</div><div style="font-size:.65rem;color:var(--text-muted)">WhatsApp · 2 seg · sem intervenção</div></div>
                        <div style="font-size:.65rem;color:#c4b5fd;font-weight:700">IA</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(245,166,35,.12);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.85rem;flex-shrink:0"><i class="fas fa-chart-line"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">DRE do mês gerada</div><div style="font-size:.65rem;color:var(--text-muted)">Margem líquida: 42% · acima da meta</div></div>
                        <div style="font-size:.65rem;color:var(--gold);font-weight:700">📊</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES GRID -->
<section class="features-wrap" id="features">
    <div style="max-width:1240px;margin:0 auto">
        <div class="reveal center">
            <div class="section-tag st-blue"><i class="fas fa-layer-group"></i> Funcionalidades</div>
            <h2 class="section-title">Tudo que seu negócio precisa,<br>em um só lugar</h2>
            <p class="section-sub center" style="max-width:540px">Desenvolvido para quem empreende sem equipe de TI. Cada módulo resolve um problema real da operação diária.</p>
        </div>

        <div class="feat-grid">
            <div class="fc fi-green reveal reveal-d1">
                <div class="fc-icon fi-green"><i class="fas fa-chart-line"></i></div>
                <h3>Fluxo de Caixa</h3>
                <p>Visão diária, semanal e mensal de entradas e saídas. Projeção futura automática e alertas de saldo crítico em tempo real.</p>
                <span class="fc-tag ft-new"><i class="fas fa-sparkles"></i> Novo</span>
            </div>

            <div class="fc fi-blue reveal reveal-d2">
                <div class="fc-icon fi-blue"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3>Emissão de NF-e / NFS-e</h3>
                <p>Emita notas fiscais de produtos e serviços diretamente pelo sistema. Integração com SEFAZ e XML automático para o contador.</p>
            </div>

            <div class="fc fi-teal reveal reveal-d3">
                <div class="fc-icon fi-teal"><i class="fas fa-money-bill-trend-up"></i></div>
                <h3>Cobranças Automáticas</h3>
                <p>Boletos, Pix e cartão de crédito. Régua de cobrança automática por WhatsApp e e-mail para reduzir inadimplência sem esforço.</p>
                <span class="fc-tag ft-new"><i class="fas fa-sparkles"></i> Novo</span>
            </div>

            <div class="fc fi-gold reveal reveal-d4">
                <div class="fc-icon fi-gold"><i class="fas fa-users"></i></div>
                <h3>CRM de Clientes</h3>
                <p>Cadastro completo, histórico de compras, contratos, propostas e anotações. Saiba exatamente onde cada cliente está no funil.</p>
            </div>

            <div class="fc fi-purple reveal reveal-d5">
                <div class="fc-icon fi-purple"><i class="fas fa-robot"></i></div>
                <h3>Bruce AI no WhatsApp</h3>
                <p>Atendimento 24/7 via WhatsApp com inteligência artificial. Responde dúvidas, agenda reuniões e qualifica leads automaticamente.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>

            <div class="fc fi-sky reveal reveal-d6">
                <div class="fc-icon fi-sky"><i class="fas fa-file-contract"></i></div>
                <h3>Contratos Digitais</h3>
                <p>Crie, envie e colete assinatura eletrônica com validade jurídica. Modelos prontos editáveis para agilizar seu processo comercial.</p>
            </div>

            <div class="fc fi-orange reveal reveal-d7">
                <div class="fc-icon fi-orange"><i class="fas fa-receipt"></i></div>
                <h3>Controle de Despesas</h3>
                <p>Lance despesas por categoria, anexe comprovantes e gere relatórios para o contador com um clique. Fim das pilhas de papel.</p>
            </div>

            <div class="fc fi-emerald reveal reveal-d8">
                <div class="fc-icon fi-emerald"><i class="fas fa-chart-pie"></i></div>
                <h3>DRE Simplificada</h3>
                <p>Demonstrativo de Resultado gerado automaticamente com as suas movimentações. Entenda sua margem de lucro sem ser contador.</p>
            </div>

            <div class="fc fi-purple reveal reveal-d9">
                <div class="fc-icon fi-purple"><i class="fas fa-brain"></i></div>
                <h3>Insights com IA</h3>
                <p>Bruce AI analisa seu histórico e sugere onde cortar custos, quais clientes têm maior LTV e qual período é mais lucrativo.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>

            <div class="fc fi-green reveal reveal-d10">
                <div class="fc-icon fi-green"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Controle de Estoque</h3>
                <p>Cadastro de produtos, entradas, saídas e alertas de estoque mínimo. Integrado com emissão de NF-e e histórico de fornecedores.</p>
            </div>

            <div class="fc fi-blue reveal reveal-d11">
                <div class="fc-icon fi-blue"><i class="fas fa-calendar-check"></i></div>
                <h3>Agenda de Compromissos</h3>
                <p>Calendário de reuniões, vencimentos e tarefas integrado com notificações por WhatsApp para você e sua equipe.</p>
            </div>

            <div class="fc fi-pink reveal reveal-d12">
                <div class="fc-icon fi-pink"><i class="fas fa-handshake"></i></div>
                <h3>Gestão de Fornecedores</h3>
                <p>Cadastro, histórico de compras, controle de contratos e contas a pagar com previsão de vencimento e conciliação bancária.</p>
                <span class="fc-tag ft-new"><i class="fas fa-sparkles"></i> Novo</span>
            </div>
        </div>
    </div>
</section>

<!-- MODULE SPOTLIGHT -->
<section class="spotlight" id="modulos">
    <div class="spotlight-inner">
        <div class="reveal center">
            <div class="section-tag st-green"><i class="fas fa-star"></i> Destaques</div>
            <h2 class="section-title">Módulos que aceleram<br>seu faturamento</h2>
            <p class="section-sub center" style="max-width:500px">Dois dos recursos mais poderosos do Vivensi para MEIs e PMEs que querem crescer com controle e previsibilidade.</p>
        </div>

        <div class="spotlight-grid">
            <!-- Card 1: Fluxo de Caixa -->
            <div class="spot-card spot-card-green reveal reveal-d1">
                <div class="spot-accent-bar sab-green"></div>
                <div class="spot-badge sbg-green"><i class="fas fa-chart-line"></i> Financeiro</div>
                <div class="spot-title">Fluxo de caixa com previsão inteligente</div>
                <p class="spot-desc">Visualize entradas e saídas em tempo real. O sistema projeta seu saldo futuro automaticamente com base nos recebimentos cadastrados.</p>
                <ul class="spot-feats">
                    <li><i class="fas fa-circle-check sfi-green"></i> Projeção automática de 30/60/90 dias</li>
                    <li><i class="fas fa-circle-check sfi-green"></i> Alertas de saldo crítico por WhatsApp</li>
                    <li><i class="fas fa-circle-check sfi-green"></i> Conciliação bancária em 1 clique</li>
                    <li><i class="fas fa-circle-check sfi-green"></i> Relatório exportável para contador</li>
                </ul>
                <div class="spot-demo">
                    <div class="cf-demo">
                        <div class="cf-row">
                            <span class="cf-label"><i class="fas fa-arrow-up" style="color:#6ee7b7;margin-right:4px"></i>Receitas do mês</span>
                            <span class="cf-value-pos">+ R$ 42.300</span>
                        </div>
                        <div class="cf-row">
                            <span class="cf-label"><i class="fas fa-arrow-down" style="color:#f87171;margin-right:4px"></i>Despesas do mês</span>
                            <span class="cf-value-neg">- R$ 24.100</span>
                        </div>
                        <div class="cf-balance">
                            <span class="cf-balance-lbl">Saldo líquido</span>
                            <span class="cf-balance-val">R$ 18.200</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Cobranças Automáticas -->
            <div class="spot-card spot-card-blue reveal reveal-d2">
                <div class="spot-accent-bar sab-blue"></div>
                <div class="spot-badge sbg-blue"><i class="fas fa-money-bill-trend-up"></i> Cobranças</div>
                <div class="spot-title">Régua de cobrança automática</div>
                <p class="spot-desc">Configure uma régua de cobrança e o sistema dispara lembretes automáticos por WhatsApp e e-mail antes e após o vencimento.</p>
                <ul class="spot-feats">
                    <li><i class="fas fa-circle-check sfi-blue"></i> Aviso 3 dias antes do vencimento</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> Cobrança no dia + 2 dias após</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> Pix automático com QR Code</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> Painel de inadimplência em tempo real</li>
                </ul>
                <div class="spot-demo">
                    <div class="inv-demo">
                        <div class="inv-row">
                            <div class="inv-status s-paid"></div>
                            <div class="inv-info">
                                <div class="inv-name">Tech Solutions Ltda</div>
                                <div class="inv-date">Venc. 01/05 · Pago via Pix</div>
                            </div>
                            <span class="inv-amount">R$ 5.800</span>
                        </div>
                        <div class="inv-row">
                            <div class="inv-status s-pending"></div>
                            <div class="inv-info">
                                <div class="inv-name">Comércio do João ME</div>
                                <div class="inv-date">Venc. 15/05 · Lembrete enviado</div>
                            </div>
                            <span class="inv-amount">R$ 2.400</span>
                        </div>
                        <div class="inv-row">
                            <div class="inv-status s-overdue"></div>
                            <div class="inv-info">
                                <div class="inv-name">Distribuidora Rápida</div>
                                <div class="inv-date">Venc. 28/04 · Em cobrança</div>
                            </div>
                            <span class="inv-amount">R$ 1.200</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PARALLAX BOOKING CTA -->
<section class="parallax-cta" id="agendar">
    <div class="parallax-bg" id="parallaxBg"></div>
    <div class="parallax-grid"></div>

    <div class="parallax-inner reveal">
        <div class="parallax-eyebrow">
            <span class="plx-dot"></span>
            <i class="fas fa-calendar-check"></i>
            Demonstração Gratuita
        </div>

        <h2 class="parallax-title">
            Pronto para organizar<br>e escalar seu negócio?
        </h2>

        <p class="parallax-sub">
            Agende uma demonstração de 30 minutos com nosso time. Veja na prática como o Vivensi pode transformar a gestão do seu MEI ou PME.
        </p>

        <div class="plx-btns">
            <a href="{{ route('booking.index') }}" class="btn-plx">
                <i class="fas fa-calendar-check"></i> Agendar Demonstração
            </a>
            <a href="#pricing" class="btn-plx-ghost">
                Ver planos <i class="fas fa-arrow-right" style="font-size:.75rem"></i>
            </a>
        </div>

        <div class="plx-trust">
            <div class="plxt"><i class="fas fa-check"></i> Sem compromisso</div>
            <div class="plxt"><i class="fas fa-check"></i> 30 minutos</div>
            <div class="plxt"><i class="fas fa-check"></i> Especialista dedicado</div>
            <div class="plxt"><i class="fas fa-check"></i> Material incluso</div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing-wrap" id="pricing">
    <div style="max-width:1240px;margin:0 auto">
        <div class="reveal center">
            <div class="section-tag st-green"><i class="fas fa-tag"></i> Planos</div>
            <h2 class="section-title">Invista no crescimento do seu negócio</h2>
            <p class="section-sub center">Sem fidelidade, sem taxa de setup. Cancele quando quiser. Suporte incluso em todos os planos.</p>
            <div class="billing-toggle">
                <span class="tgl-lbl on" id="lbl-m">Mensal</span>
                <label class="switch"><input type="checkbox" id="billing-toggle" onchange="toggleBilling()"><span class="slider"></span></label>
                <span class="tgl-lbl" id="lbl-y">Anual <span class="disc-badge">-10% OFF</span></span>
            </div>
        </div>
        <div class="price-grid">
            @forelse($plans as $plan)
            <div class="price-card {{ $loop->index === 1 ? 'hot' : '' }}">
                @if($loop->index === 1)<div class="hot-badge">Mais Popular</div>@endif
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
                   class="btn-price {{ $loop->index === 1 ? 'bp-solid' : 'bp-outline' }} btn-subscribe"
                   data-plan-id="{{ $plan->id }}">
                   {{ $loop->index === 1 ? '🚀 Assinar Agora' : 'Escolher Plano' }}
                </a>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-dim)">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:16px"></i>
                Planos sob consulta — <a href="{{ route('login') }}" style="color:var(--accent)">entre em contato</a>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-row">
        <div class="footer-brand">
            <img loading="lazy" src="{{ asset('img/novalogo.png') }}" alt="Vivensi" style="height:28px;filter:brightness(0) invert(1);opacity:.6">
            <p>Tecnologia para quem empreende com determinação. Gestão inteligente para MEI, PME e negócios em crescimento.</p>
        </div>
        <div class="footer-col">
            <h5>Soluções</h5>
            <a href="{{ route('solutions.ngo') }}">Para ONGs</a>
            <a href="{{ route('solutions.manager') }}">Para Gestores</a>
            <a href="{{ route('solutions.common') }}">Para MEI/PME</a>
            <a href="#features">Recursos</a>
        </div>
        <div class="footer-col">
            <h5>Produto</h5>
            <a href="#pricing">Planos & Preços</a>
            <a href="{{ route('login') }}">Acessar conta</a>
            <a href="{{ route('register') }}">Criar conta</a>
            <a href="{{ route('booking.index') }}">Agendar Demo</a>
        </div>
        <div class="footer-col">
            <h5>Legal</h5>
            <a href="{{ route('public.page', 'privacidade') }}">Privacidade</a>
            <a href="{{ route('public.page', 'termos') }}">Termos de Uso</a>
            <a href="{{ route('public.page', 'sobre') }}">Sobre o Vivensi</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 Vivensi. Todos os direitos reservados.</span>
        <span>Feito com <span class="fb-heart">♥</span> para quem empreende no Brasil</span>
    </div>
</footer>

<script>
function toggleBilling(){
    const yearly = document.getElementById('billing-toggle').checked;
    document.getElementById('lbl-m').classList.toggle('on', !yearly);
    document.getElementById('lbl-y').classList.toggle('on', yearly);
    document.querySelectorAll('.amount').forEach(el => {
        const m = parseFloat(el.dataset.m), y = parseFloat(el.dataset.y);
        el.textContent = new Intl.NumberFormat('pt-BR',{minimumFractionDigits:2}).format(yearly ? y/12 : m);
    });
    document.querySelectorAll('[id^="pnote-"]').forEach(el => {
        el.textContent = yearly ? 'cobrado anualmente (economize 10%)' : 'cobrado mensalmente';
    });
    document.querySelectorAll('.btn-subscribe').forEach(btn => {
        btn.href = `/register?plan_id=${btn.dataset.planId}&billing_cycle=${yearly ? 'yearly' : 'monthly'}`;
    });
}

const revealObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); revealObs.unobserve(e.target); } });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

window.addEventListener('scroll', () => {
    document.getElementById('mainNav').style.borderBottomColor =
        window.scrollY > 60 ? 'rgba(255,255,255,.1)' : 'rgba(255,255,255,.07)';
}, { passive: true });

const parallaxBg = document.getElementById('parallaxBg');
const isMobile = window.matchMedia('(max-width:768px)').matches;
if (!isMobile && parallaxBg) {
    window.addEventListener('scroll', () => {
        const section = document.getElementById('agendar');
        if (!section) return;
        const rect = section.getBoundingClientRect();
        const scrolled = -rect.top * 0.25;
        parallaxBg.style.transform = `translateY(${scrolled}px)`;
    }, { passive: true });
}
</script>
@include('partials.whatsapp-button')
</body>
</html>
