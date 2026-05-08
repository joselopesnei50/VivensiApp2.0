<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi para Gestores | Projetos, CRM e Performance em um só lugar</title>
    <meta name="description" content="Kanban, CRM, financeiro e WhatsApp IA integrados para gestores e equipes de projeto. Tome decisões baseadas em dados, não em palpites.">
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
    --accent:#4F6EF7;
    --accent-dark:#3a57d4;
    --accent-dim:rgba(79,110,247,.12);
    --accent-border:rgba(79,110,247,.28);
    --blue:#4F6EF7;
    --teal:#00D4AA;
    --gold:#F5A623;
    --purple:#8B5CF6;
    --rose:#E8455A;
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
.nav-links a{color:var(--text-dim);text-decoration:none;font-size:.83rem;font-weight:500;padding:7px 13px;border-radius:8px;transition:all .2s}
.nav-links a:hover{color:var(--white);background:rgba(255,255,255,.06)}
.nav-links a.active{color:var(--white)}
.nav-ctas{display:flex;gap:8px;align-items:center}
.btn-nav-ghost{color:var(--text-dim);text-decoration:none;font-size:.83rem;font-weight:600;padding:8px 16px;border-radius:8px;border:1px solid var(--border);transition:all .2s;background:transparent}
.btn-nav-ghost:hover{color:var(--white);border-color:rgba(255,255,255,.18);background:rgba(255,255,255,.06)}
.btn-nav-solid{background:var(--accent);color:#fff;text-decoration:none;font-size:.83rem;font-weight:700;padding:9px 20px;border-radius:8px;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.btn-nav-solid:hover{background:var(--accent-dark);transform:translateY(-1px);box-shadow:0 4px 16px rgba(79,110,247,.35)}
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
    background:radial-gradient(circle,rgba(79,110,247,.12) 0%,rgba(0,212,170,.05) 40%,transparent 70%);
    filter:blur(70px);pointer-events:none;
}
.hero-inner{position:relative;z-index:2;max-width:1240px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center}

.hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--accent-dim);border:1px solid var(--accent-border);color:#93a8ff;font-size:.72rem;font-weight:800;padding:5px 14px;border-radius:100px;margin-bottom:24px;text-transform:uppercase;letter-spacing:.07em}
.badge-dot{width:6px;height:6px;border-radius:50%;background:#93a8ff;animation:pDot 2s infinite}
@keyframes pDot{0%,100%{opacity:1}50%{opacity:.3}}

.hero-title{font-size:clamp(2.4rem,5vw,4rem);font-weight:900;line-height:1.04;letter-spacing:-.04em;margin-bottom:20px;color:var(--white)}
.hero-title em{font-style:normal;color:var(--accent)}
.hero-sub{font-size:1.05rem;color:var(--text-dim);line-height:1.75;max-width:480px;margin-bottom:36px}
.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:52px}

.btn-hero{display:inline-flex;align-items:center;gap:9px;background:var(--accent);color:#fff;text-decoration:none;font-weight:700;font-size:.95rem;padding:14px 30px;border-radius:10px;transition:all .2s}
.btn-hero:hover{background:var(--accent-dark);transform:translateY(-2px);box-shadow:0 8px 28px rgba(79,110,247,.4)}
.btn-hero-outline{display:inline-flex;align-items:center;gap:9px;color:var(--text-dim);text-decoration:none;font-weight:600;font-size:.95rem;padding:14px 26px;border-radius:10px;border:1px solid var(--border);transition:all .2s;background:transparent}
.btn-hero-outline:hover{color:var(--white);background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.18)}

.hero-stats{display:flex;gap:32px;flex-wrap:wrap}
.stat-item .snum{font-size:1.8rem;font-weight:900;letter-spacing:-.04em;color:var(--white)}
.stat-item .slbl{font-size:.68rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.07em;margin-top:2px}

/* Kanban mini visual */
.kanban-wrap{background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:20px;overflow:hidden}
.kb-header{display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)}
.kbh-dot{width:8px;height:8px;border-radius:50%}
.kbh-title{font-size:.78rem;font-weight:700;color:rgba(255,255,255,.5);margin-left:4px}
.kb-board{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.kb-col-title{font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:8px;display:flex;align-items:center;gap:5px}
.kb-dot{width:5px;height:5px;border-radius:50%}
.kd-orange{background:#fb923c}.kd-blue{background:var(--accent)}.kd-green{background:var(--teal)}
.kb-card{background:var(--glass);border:1px solid var(--border);border-radius:8px;padding:9px 10px;margin-bottom:7px;font-size:.66rem;color:rgba(255,255,255,.55);font-weight:600;line-height:1.3}
.kb-card-val{font-size:.72rem;font-weight:800;color:var(--white);margin-top:3px}
.kb-card-hot{border-color:rgba(79,110,247,.45);background:rgba(79,110,247,.09);animation:hotPulse 3s ease-in-out infinite}
@keyframes hotPulse{0%,100%{border-color:rgba(79,110,247,.3)}50%{border-color:rgba(79,110,247,.7)}}
.kb-metric-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px}
.kb-metric{background:var(--glass);border:1px solid var(--border);border-radius:8px;padding:10px}
.kb-metric-lbl{font-size:.6rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px}
.kb-metric-val{font-size:1.2rem;font-weight:900;letter-spacing:-.03em}

/* Floating notifs */
.hero-notif{position:absolute;display:flex;align-items:center;gap:9px;background:rgba(12,12,18,.92);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:12px;padding:10px 14px;white-space:nowrap;box-shadow:0 8px 32px rgba(0,0,0,.5);z-index:3}
.hn-1{bottom:-24px;left:-30px;animation:floatBadge 4s ease-in-out infinite}
.hn-2{top:-20px;right:-20px;animation:floatBadge 4.5s ease-in-out infinite .8s}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.hn-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.hni-blue{background:rgba(79,110,247,.15);color:var(--accent)}
.hni-green{background:rgba(74,222,128,.15);color:#4ade80}
.hni-teal{background:rgba(0,212,170,.15);color:var(--teal)}
.hn-text .hn-lbl{font-size:.58rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.hn-text .hn-val{font-size:.8rem;font-weight:800;color:var(--white)}

@media(max-width:860px){.hero-inner{grid-template-columns:1fr}.kanban-wrap{display:none}}

/* ══ TRUST BAR ════════════════════════════════════════════════ */
.trust{padding:18px 5%;background:var(--glass);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.trust-row{display:flex;align-items:center;justify-content:center;gap:36px;flex-wrap:wrap}
.trust-lbl{font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.09em}
.trust-item{display:flex;align-items:center;gap:7px;color:var(--text-dim);font-size:.8rem;font-weight:600}
.trust-item i{color:var(--teal)}

/* ══ SECTION COMMONS ══════════════════════════════════════════ */
.section-tag{display:inline-flex;align-items:center;gap:7px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px}
.st-blue{color:#93a8ff}.st-teal{color:var(--teal)}.st-gold{color:var(--gold)}.st-purple{color:#c4b5fd}.st-rose{color:#ff8a97}.st-white{color:rgba(255,255,255,.5)}
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

/* ══ PROBLEM SECTION ══════════════════════════════════════════ */
.prob-section{padding:100px 5%;background:rgba(79,110,247,.03);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.prob-grid{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;max-width:1240px;margin:0 auto}
.pain-list{display:flex;flex-direction:column;gap:14px;margin-top:36px}
.pain-item{display:flex;align-items:flex-start;gap:14px;padding:18px;background:rgba(79,110,247,.06);border:1px solid rgba(79,110,247,.15);border-radius:14px}
.pain-icon{width:40px;height:40px;background:rgba(79,110,247,.14);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#93a8ff;font-size:1rem;flex-shrink:0}
.pain-text h4{font-size:.9rem;font-weight:700;margin-bottom:3px}
.pain-text p{font-size:.8rem;color:var(--text-dim);line-height:1.5}
@media(max-width:860px){.prob-grid{grid-template-columns:1fr;gap:40px}}

/* ══ FEATURES GRID ════════════════════════════════════════════ */
.features-wrap{padding:100px 5%;background:var(--bg)}
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;max-width:1240px;margin:52px auto 0}

.fc{
    background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:28px;
    transition:border-color .3s,transform .3s,box-shadow .3s;cursor:default;position:relative;overflow:hidden;
}
.fc::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--fc-accent,var(--accent));opacity:0;transition:opacity .3s}
.fc:hover{border-color:var(--border-hover);transform:translateY(-5px);box-shadow:0 20px 48px rgba(0,0,0,.45)}
.fc:hover::before{opacity:1}
.fc-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:18px;transition:transform .3s}
.fc:hover .fc-icon{transform:scale(1.1)}
.fi-blue{background:rgba(79,110,247,.12);color:#93a8ff;--fc-accent:#4F6EF7}
.fi-teal{background:rgba(0,212,170,.12);color:var(--teal);--fc-accent:#00D4AA}
.fi-gold{background:rgba(245,166,35,.12);color:var(--gold);--fc-accent:#F5A623}
.fi-purple{background:rgba(139,92,246,.12);color:#c4b5fd;--fc-accent:#8B5CF6}
.fi-rose{background:rgba(232,69,90,.12);color:#ff8a97;--fc-accent:#E8455A}
.fi-sky{background:rgba(56,189,248,.12);color:#7dd3fc;--fc-accent:#38bdf8}
.fi-green{background:rgba(74,222,128,.12);color:#4ade80;--fc-accent:#4ade80}
.fi-orange{background:rgba(251,146,60,.12);color:#fb923c;--fc-accent:#fb923c}
.fi-pink{background:rgba(244,114,182,.12);color:#f472b6;--fc-accent:#f472b6}

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
.spot-card-blue{background:#060812}
.spot-card-purple{background:#080610}
.spot-accent-bar{position:absolute;top:0;left:0;right:0;height:3px}
.sab-blue{background:var(--blue)}.sab-purple{background:var(--purple)}
.spot-badge{display:inline-flex;align-items:center;gap:6px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:4px 10px;border-radius:100px;margin-bottom:16px}
.sbg-blue{background:rgba(79,110,247,.12);color:#93a8ff;border:1px solid rgba(79,110,247,.2)}
.sbg-purple{background:rgba(139,92,246,.12);color:#c4b5fd;border:1px solid rgba(139,92,246,.2)}
.spot-title{font-size:1.4rem;font-weight:900;letter-spacing:-.03em;margin-bottom:10px;color:var(--white)}
.spot-desc{font-size:.85rem;color:var(--text-dim);line-height:1.7;margin-bottom:24px}
.spot-feats{list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:28px}
.spot-feats li{display:flex;align-items:center;gap:9px;font-size:.82rem;color:rgba(255,255,255,.6)}
.spot-feats li i{font-size:.75rem;flex-shrink:0}
.sfi-blue{color:#93a8ff}.sfi-purple{color:#c4b5fd}
.spot-demo{background:var(--glass);border:1px solid var(--border);border-radius:12px;padding:14px}
@media(max-width:860px){.spotlight-grid{grid-template-columns:1fr}}

/* Campaign/KPI demo */
.kpi-demo{display:flex;flex-direction:column;gap:10px}
.kpi-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.72rem}
.kpi-row:last-child{border-bottom:none}
.kpi-lbl{color:var(--text-dim)}
.kpi-val{font-weight:700;color:var(--white)}
.kpi-pos{color:#4ade80}.kpi-neg{color:#f87171}
.kpi-total{margin-top:8px;padding:10px;border-radius:8px;background:rgba(79,110,247,.1);border:1px solid rgba(79,110,247,.2);display:flex;justify-content:space-between;align-items:center}
.kpi-total-lbl{font-size:.64rem;font-weight:700;color:var(--text-dim)}
.kpi-total-val{font-size:.95rem;font-weight:900;color:var(--white)}

/* AI pitch demo */
.ai-demo{display:flex;flex-direction:column;gap:9px}
.ai-header{display:flex;align-items:center;gap:7px;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)}
.ai-live{width:5px;height:5px;border-radius:50%;background:#c4b5fd;animation:pDot 1.5s infinite}
.ai-prospect{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid var(--border)}
.ai-logo{width:28px;height:28px;border-radius:7px;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:900;color:#fff;flex-shrink:0}
.ai-name{font-size:.74rem;font-weight:700;color:var(--white)}
.ai-cat{font-size:.6rem;color:var(--text-muted);margin-top:1px}
.ai-score-row{display:flex;justify-content:space-between;font-size:.6rem;color:var(--text-muted);margin-top:4px;margin-bottom:3px}
.ai-score-pct{color:#c4b5fd;font-weight:800}
.ai-bar{height:4px;background:rgba(255,255,255,.07);border-radius:100px;overflow:hidden}
.ai-bar-fill{height:100%;border-radius:100px;background:var(--purple);animation:aiBar 3s ease-in-out infinite alternate}
@keyframes aiBar{from{width:20%}to{width:92%}}
.ai-pitch{margin-top:4px;padding:8px 10px;background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.15);border-radius:8px}
.ai-pitch-lbl{font-size:.56rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:rgba(139,92,246,.55);margin-bottom:3px}
.ai-pitch-txt{font-size:.68rem;color:rgba(255,255,255,.55);line-height:1.5}
.ai-cursor{display:inline-block;width:1.5px;height:9px;background:#c4b5fd;margin-left:1px;animation:cursorBlink .7s step-end infinite;vertical-align:text-bottom}
@keyframes cursorBlink{0%,100%{opacity:1}50%{opacity:0}}

/* ══ PARALLAX BOOKING CTA ════════════════════════════════════ */
.parallax-cta{
    position:relative;padding:120px 5%;overflow:hidden;
    background:#06060e;border-top:1px solid var(--border);border-bottom:1px solid var(--border);
}
.parallax-bg{
    position:absolute;inset:0;z-index:0;
    background-image:
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(79,110,247,.09) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 20% 30%, rgba(0,212,170,.06) 0%, transparent 60%);
    will-change:transform;
}
.parallax-grid{position:absolute;inset:0;z-index:0;background-image:linear-gradient(rgba(255,255,255,.018) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.018) 1px,transparent 1px);background-size:72px 72px}
.parallax-inner{position:relative;z-index:2;max-width:760px;margin:0 auto;text-align:center}
.parallax-eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--accent-dim);border:1px solid var(--accent-border);
    color:#93a8ff;font-size:.7rem;font-weight:800;padding:5px 14px;border-radius:100px;
    margin-bottom:28px;text-transform:uppercase;letter-spacing:.08em;
}
.plx-dot{width:6px;height:6px;border-radius:50%;background:#93a8ff;animation:pDot 1.8s infinite}
.parallax-title{font-size:clamp(2rem,5vw,3.4rem);font-weight:900;line-height:1.06;letter-spacing:-.04em;color:var(--white);margin-bottom:20px}
.parallax-sub{font-size:1rem;color:var(--text-dim);line-height:1.75;max-width:520px;margin:0 auto 40px}
.btn-plx{display:inline-flex;align-items:center;gap:10px;background:var(--accent);color:#fff;text-decoration:none;font-weight:700;font-size:1rem;padding:16px 36px;border-radius:12px;transition:all .25s}
.btn-plx:hover{background:var(--accent-dark);transform:translateY(-3px);box-shadow:0 12px 36px rgba(79,110,247,.4)}
.btn-plx-ghost{display:inline-flex;align-items:center;gap:10px;background:transparent;color:var(--text-dim);text-decoration:none;font-weight:600;font-size:1rem;padding:16px 28px;border-radius:12px;border:1px solid var(--border);transition:all .25s}
.btn-plx-ghost:hover{color:var(--white);border-color:var(--border-hover);background:rgba(255,255,255,.05)}
.plx-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}
.plx-trust{display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap}
.plxt{display:flex;align-items:center;gap:7px;font-size:.76rem;color:var(--text-muted);font-weight:600}
.plxt i{color:var(--teal);font-size:.7rem}

/* ══ PRICING ═════════════════════════════════════════════════ */
.pricing-wrap{padding:100px 5%;background:var(--bg)}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:2px;max-width:980px;margin:48px auto 0;border:1px solid var(--border);border-radius:20px;overflow:hidden}
.price-card{background:var(--bg2);padding:36px;position:relative;transition:background .25s;border-right:1px solid var(--border)}
.price-card:last-child{border-right:none}
.price-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:transparent}
.price-card.hot{background:rgb(10,12,22)}
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
.price-feats li i{color:var(--teal);font-size:.74rem;flex-shrink:0}
.btn-price{display:block;text-align:center;padding:13px;border-radius:10px;font-weight:700;font-size:.85rem;text-decoration:none;transition:all .2s;cursor:pointer;width:100%;border:none;font-family:'Inter',sans-serif}
.bp-solid{background:var(--accent);color:#fff}
.bp-solid:hover{background:var(--accent-dark);transform:translateY(-1px)}
.bp-outline{border:1px solid var(--border);color:rgba(255,255,255,.6);background:transparent}
.bp-outline:hover{border-color:var(--border-hover);background:rgba(255,255,255,.05);color:var(--white)}
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
.fb-heart{color:rgba(79,110,247,.7)}
@media(max-width:768px){.footer-row{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.footer-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
    <a href="{{ url('/') }}" class="nav-logo">
        <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
    </a>
    <ul class="nav-links">
        <li><a href="{{ route('solutions.ngo') }}">Terceiro Setor</a></li>
        <li><a href="{{ route('solutions.manager') }}" class="active">Gestores</a></li>
        <li><a href="{{ route('solutions.common') }}">MEI/PME</a></li>
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
                Performance & Gestão de Projetos
            </div>

            <h1 class="hero-title">
                Domine seus projetos<br>com <em>inteligência real</em>
            </h1>

            <p class="hero-sub">
                A primeira plataforma que une Kanban, CRM, Financeiro e WhatsApp IA em tempo real. Tome decisões baseadas em dados, não em palpites.
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
                    <div class="snum">500+</div>
                    <div class="slbl">Empresas</div>
                </div>
                <div class="stat-item">
                    <div class="snum">12k+</div>
                    <div class="slbl">Tasks diárias</div>
                </div>
                <div class="stat-item">
                    <div class="snum">99.9%</div>
                    <div class="slbl">Uptime SLA</div>
                </div>
            </div>
        </div>

        <!-- RIGHT — Kanban Visual -->
        <div class="hero-visual">
            <div style="position:relative">
                <div class="hero-notif hn-2">
                    <div class="hn-icon hni-green"><i class="fas fa-circle-check"></i></div>
                    <div class="hn-text">
                        <div class="hn-lbl">Task concluída</div>
                        <div class="hn-val">Em dia · Sprint 3</div>
                    </div>
                </div>

                <div class="kanban-wrap">
                    <div class="kb-header">
                        <div class="kbh-dot" style="background:#FF5F57"></div>
                        <div class="kbh-dot" style="background:#FEBC2E"></div>
                        <div class="kbh-dot" style="background:#28C840"></div>
                        <span class="kbh-title">Vivensi — Gestão de Projetos</span>
                    </div>
                    <div class="kb-board">
                        <div>
                            <div class="kb-col-title"><span class="kb-dot kd-orange"></span> A Fazer</div>
                            <div class="kb-card">Landing Página Cliente B<div class="kb-card-val">Alta</div></div>
                            <div class="kb-card">Relatório Q2 Financeiro<div class="kb-card-val">Média</div></div>
                        </div>
                        <div>
                            <div class="kb-col-title"><span class="kb-dot kd-blue"></span> Em Progresso</div>
                            <div class="kb-card kb-card-hot">Proposta Banco Sul<div class="kb-card-val">R$ 45k</div></div>
                            <div class="kb-card">API WhatsApp Integration<div class="kb-card-val">Dev</div></div>
                        </div>
                        <div>
                            <div class="kb-col-title"><span class="kb-dot kd-green"></span> Concluído</div>
                            <div class="kb-card">Setup CRM Leads<div class="kb-card-val">Entregue ✓</div></div>
                            <div class="kb-card">Site Institucional<div class="kb-card-val">Deploy ✓</div></div>
                        </div>
                    </div>
                    <div class="kb-metric-row">
                        <div class="kb-metric">
                            <div class="kb-metric-lbl">Margem Mês</div>
                            <div class="kb-metric-val" style="color:var(--teal)">R$ 42k</div>
                        </div>
                        <div class="kb-metric">
                            <div class="kb-metric-lbl">Sprint Health</div>
                            <div class="kb-metric-val" style="color:#4ade80">84%</div>
                        </div>
                    </div>
                </div>

                <div class="hero-notif hn-1">
                    <div class="hn-icon hni-teal"><i class="fas fa-comment-dots"></i></div>
                    <div class="hn-text">
                        <div class="hn-lbl">Bruce AI</div>
                        <div class="hn-val">Pitch gerado em 8s</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST BAR -->
<div class="trust">
    <div class="trust-row">
        <span class="trust-lbl">Infraestrutura robusta para sua empresa</span>
        <div class="trust-item"><i class="fas fa-shield-halved"></i> Audit Trails</div>
        <div class="trust-item"><i class="fas fa-server"></i> AWS Enterprise</div>
        <div class="trust-item"><i class="fas fa-lock"></i> MFA + SSL</div>
        <div class="trust-item"><i class="fas fa-microchip"></i> IA Nativa</div>
        <div class="trust-item"><i class="fas fa-clock"></i> 99.9% Uptime</div>
    </div>
</div>

<!-- PROBLEM → SOLUTION -->
<section class="prob-section" id="problema">
    <div class="prob-grid">
        <div class="reveal">
            <div class="section-tag st-blue"><i class="fas fa-triangle-exclamation"></i> O Gargalo Real</div>
            <h2 class="section-title">Pare de gerir por planilhas.<br>Comece a gerir por resultado.</h2>
            <p class="section-sub">Informação fragmentada. Projetos atrasados. Equipe sobrecarregada. Falta de visão financeira real. O Vivensi Manager resolve tudo isso.</p>
            <div class="pain-list">
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-clock-rotate-left"></i></div>
                    <div class="pain-text"><h4>Cronogramas que nunca batem</h4><p>Gerencie prazos reais com Kanban visual e alertas automáticos de atraso.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-sack-dollar"></i></div>
                    <div class="pain-text"><h4>Furo de caixa por projeto</h4><p>Saiba exatamente onde cada centavo está sendo gasto por centro de custo.</p></div>
                </div>
                <div class="pain-item">
                    <div class="pain-icon"><i class="fas fa-users-viewfinder"></i></div>
                    <div class="pain-text"><h4>Falta de clareza nas responsabilidades</h4><p>Kanban com responsáveis, prazos definidos e histórico para cada task.</p></div>
                </div>
            </div>
        </div>
        <div class="reveal reveal-d2">
            <div class="kanban-wrap" style="background:#0d0f18;border-color:rgba(79,110,247,.15)">
                <div class="kb-header">
                    <div class="kbh-dot" style="background:#FF5F57"></div>
                    <div class="kbh-dot" style="background:#FEBC2E"></div>
                    <div class="kbh-dot" style="background:#28C840"></div>
                    <span class="kbh-title">Vivensi — Dashboard Manager</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:9px">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(79,110,247,.14);display:flex;align-items:center;justify-content:center;color:#93a8ff;font-size:.85rem;flex-shrink:0"><i class="fas fa-chart-line"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Projeto Alpha — No prazo</div><div style="font-size:.65rem;color:var(--text-muted)">Sprint 3 · 84% concluído</div></div>
                        <div style="font-size:.65rem;color:#4ade80;font-weight:700">✓ OK</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(0,212,170,.12);display:flex;align-items:center;justify-content:center;color:var(--teal);font-size:.85rem;flex-shrink:0"><i class="fas fa-coins"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Margem acumulada Q2</div><div style="font-size:.65rem;color:var(--text-muted)">3 projetos · R$ 142.800</div></div>
                        <div style="font-size:.65rem;color:var(--teal);font-weight:700">+18%</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(139,92,246,.14);display:flex;align-items:center;justify-content:center;color:#c4b5fd;font-size:.85rem;flex-shrink:0"><i class="fas fa-robot"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Bruce AI — Pitch gerado</div><div style="font-size:.65rem;color:var(--text-muted)">Empresa: Banco Sul · 92% fit</div></div>
                        <div style="font-size:.65rem;color:#c4b5fd;font-weight:700">IA</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--glass);border:1px solid var(--border);border-radius:10px">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(245,166,35,.12);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:.85rem;flex-shrink:0"><i class="fas fa-calendar-check"></i></div>
                        <div style="flex:1"><div style="font-size:.75rem;font-weight:700;color:var(--white)">Reunião agendada</div><div style="font-size:.65rem;color:var(--text-muted)">Cliente ABC · Amanhã 14h</div></div>
                        <div style="font-size:.65rem;color:var(--gold);font-weight:700">📅</div>
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
            <div class="section-tag st-blue"><i class="fas fa-layer-group"></i> Ecossistema Manager</div>
            <h2 class="section-title">Domine a operação<br>de ponta a ponta</h2>
            <p class="section-sub center" style="max-width:540px">Uma suite de ferramentas integradas para quem não tem tempo a perder. Cada módulo fala com o outro.</p>
        </div>

        <div class="feat-grid">
            <div class="fc fi-blue reveal reveal-d1">
                <div class="fc-icon fi-blue"><i class="fas fa-columns"></i></div>
                <h3>Kanban de Projetos</h3>
                <p>Visualize o mesmo projeto em Kanban, Lista ou Gantt. Drag & drop entre estágios com histórico completo de movimentações.</p>
                <span class="fc-tag ft-new"><i class="fas fa-sparkles"></i> Novo</span>
            </div>

            <div class="fc fi-teal reveal reveal-d2">
                <div class="fc-icon fi-teal"><i class="fas fa-coins"></i></div>
                <h3>Controle Financeiro</h3>
                <p>Vincule receitas e despesas a cada projeto. Saiba exatamente qual cliente é mais rentável — em tempo real.</p>
            </div>

            <div class="fc fi-purple reveal reveal-d3">
                <div class="fc-icon fi-purple"><i class="fas fa-robot"></i></div>
                <h3>Bruce AI — Prospecção</h3>
                <p>Encontra, analisa e gera pitch personalizado para cada lead em segundos. Integração com Google Maps e WhatsApp.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>

            <div class="fc fi-gold reveal reveal-d4">
                <div class="fc-icon fi-gold"><i class="fas fa-calendar-check"></i></div>
                <h3>Reuniões & Agendamentos</h3>
                <p>Sistema de booking público com slots configuráveis, confirmação automática por e-mail e cancelamento com token seguro.</p>
                <span class="fc-tag ft-new"><i class="fas fa-sparkles"></i> Novo</span>
            </div>

            <div class="fc fi-rose reveal reveal-d5">
                <div class="fc-icon fi-rose"><i class="fas fa-chart-pie"></i></div>
                <h3>DRE & Relatórios</h3>
                <p>Relatórios financeiros estruturados por projeto ou consolidado da empresa — gerados em 1 clique, prontos para diretoria.</p>
            </div>

            <div class="fc fi-sky reveal reveal-d6">
                <div class="fc-icon fi-sky"><i class="fas fa-comment-dots"></i></div>
                <h3>WhatsApp & IA Chatbot</h3>
                <p>Atenda clientes, responda leads e automatize follow-ups pelo WhatsApp com IA treinada na sua base de conhecimento.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>

            <div class="fc fi-blue reveal reveal-d7">
                <div class="fc-icon fi-blue"><i class="fas fa-megaphone"></i></div>
                <h3>Broadcast WhatsApp</h3>
                <p>Envie mensagens em massa para listas segmentadas. Importe contatos por CSV e acompanhe taxas de entrega em tempo real.</p>
            </div>

            <div class="fc fi-green reveal reveal-d8">
                <div class="fc-icon fi-green"><i class="fas fa-file-signature"></i></div>
                <h3>Contratos Digitais</h3>
                <p>Crie, envie e assine contratos digitalmente com validade jurídica. Histórico completo de assinaturas com IP e timestamp.</p>
            </div>

            <div class="fc fi-pink reveal reveal-d9">
                <div class="fc-icon fi-pink"><i class="fas fa-calendar-days"></i></div>
                <h3>Redes Sociais & Conteúdo</h3>
                <p>Calendário editorial, agendamento multi-rede e legenda gerada por IA. Marketing de forma organizada e consistente.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>

            <div class="fc fi-teal reveal reveal-d10">
                <div class="fc-icon fi-teal"><i class="fas fa-globe"></i></div>
                <h3>Landing Pages</h3>
                <p>Builder visual para criar páginas de captura de leads com formulários, pixels e integração direta ao CRM da plataforma.</p>
            </div>

            <div class="fc fi-gold reveal reveal-d11">
                <div class="fc-icon fi-gold"><i class="fas fa-fingerprint"></i></div>
                <h3>Segurança Enterprise</h3>
                <p>Logs de auditoria em todas as ações. Saiba quem fez o quê e quando. Controle de permissões granular por nível.</p>
            </div>

            <div class="fc fi-purple reveal reveal-d12">
                <div class="fc-icon fi-purple"><i class="fas fa-brain"></i></div>
                <h3>Marketing Intelligence</h3>
                <p>Análise de mercado, posicionamento competitivo e estratégias de marketing geradas por IA para o seu nicho de atuação.</p>
                <span class="fc-tag ft-ai"><i class="fas fa-wand-magic-sparkles"></i> IA</span>
            </div>
        </div>
    </div>
</section>

<!-- MODULE SPOTLIGHT -->
<section class="spotlight" id="modulos">
    <div class="spotlight-inner">
        <div class="reveal center">
            <div class="section-tag st-blue"><i class="fas fa-star"></i> Destaques</div>
            <h2 class="section-title">Módulos que impulsionam<br>resultados</h2>
            <p class="section-sub center" style="max-width:500px">Dois dos recursos mais poderosos para gestores que precisam captar clientes e fechar projetos com qualidade.</p>
        </div>

        <div class="spotlight-grid">
            <!-- Card 1: CRM/Financeiro -->
            <div class="spot-card spot-card-blue reveal reveal-d1">
                <div class="spot-accent-bar sab-blue"></div>
                <div class="spot-badge sbg-blue"><i class="fas fa-chart-line"></i> Financeiro</div>
                <div class="spot-title">Controle financeiro por projeto</div>
                <p class="spot-desc">Vincule cada transação a um projeto ou cliente. Saiba exatamente a margem real de cada entrega, com DRE automático.</p>
                <ul class="spot-feats">
                    <li><i class="fas fa-circle-check sfi-blue"></i> Margem por projeto em tempo real</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> DRE gerado em 1 clique</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> Reconciliação bancária automática</li>
                    <li><i class="fas fa-circle-check sfi-blue"></i> Relatório executivo em PDF</li>
                </ul>
                <div class="spot-demo">
                    <div class="kpi-demo">
                        <div class="kpi-row">
                            <span class="kpi-lbl">Receita do mês</span>
                            <span class="kpi-val kpi-pos">R$ 98.200</span>
                        </div>
                        <div class="kpi-row">
                            <span class="kpi-lbl">Despesas operacionais</span>
                            <span class="kpi-val kpi-neg">− R$ 31.400</span>
                        </div>
                        <div class="kpi-row">
                            <span class="kpi-lbl">Custo de equipe</span>
                            <span class="kpi-val kpi-neg">− R$ 24.800</span>
                        </div>
                        <div class="kpi-total">
                            <span class="kpi-total-lbl">Lucro líquido</span>
                            <span class="kpi-total-val">R$ 42.000</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Bruce AI Prospecção -->
            <div class="spot-card spot-card-purple reveal reveal-d2">
                <div class="spot-accent-bar sab-purple"></div>
                <div class="spot-badge sbg-purple"><i class="fas fa-robot"></i> IA Nativa</div>
                <div class="spot-title">Bruce AI — Prospecção inteligente</div>
                <p class="spot-desc">A IA encontra empresas no Google Maps, analisa o fit com seu perfil e gera pitches personalizados para cada lead em segundos.</p>
                <ul class="spot-feats">
                    <li><i class="fas fa-circle-check sfi-purple"></i> Busca por cidade e segmento</li>
                    <li><i class="fas fa-circle-check sfi-purple"></i> Score de fit automático (0-100%)</li>
                    <li><i class="fas fa-circle-check sfi-purple"></i> Pitch gerado para WhatsApp ou e-mail</li>
                    <li><i class="fas fa-circle-check sfi-purple"></i> CRM integrado para conversão</li>
                </ul>
                <div class="spot-demo">
                    <div class="ai-demo">
                        <div class="ai-header">
                            <span class="ai-live"></span> Analisando em tempo real
                        </div>
                        <div class="ai-prospect">
                            <div class="ai-logo">SF</div>
                            <div>
                                <div class="ai-name">Supermercado Família</div>
                                <div class="ai-cat">Varejo · Google Maps</div>
                            </div>
                        </div>
                        <div class="ai-score-row">
                            <span>Fit Score</span>
                            <span class="ai-score-pct">94%</span>
                        </div>
                        <div class="ai-bar"><div class="ai-bar-fill"></div></div>
                        <div class="ai-pitch">
                            <div class="ai-pitch-lbl">Pitch — WhatsApp</div>
                            <div class="ai-pitch-txt">"Olá! Vi que o Supermercado Família apoia a comunidade local. Temos uma proposta de visibilidade…<span class="ai-cursor"></span>"</div>
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
            Pronto para escalar<br>seus projetos?
        </h2>

        <p class="parallax-sub">
            Agende uma demonstração de 30 minutos com nosso time. Mostre sua operação atual e veja exatamente como o Vivensi pode resolver seus gargalos.
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
            <div class="section-tag st-blue"><i class="fas fa-bolt"></i> Escalabilidade</div>
            <h2 class="section-title">Potencialize sua empresa</h2>
            <p class="section-sub center">Escolha o plano que melhor se adapta ao seu time. Sem fidelização, sem taxa de setup.</p>
            <div class="billing-toggle">
                <span class="tgl-lbl on" id="lbl-m">Mensal</span>
                <label class="switch"><input type="checkbox" id="billing-toggle" onchange="toggleBilling()"><span class="slider"></span></label>
                <span class="tgl-lbl" id="lbl-y">Anual <span class="disc-badge">-10% OFF</span></span>
            </div>
        </div>
        <div class="price-grid">
            @forelse($plans as $plan)
            <div class="price-card {{ $loop->index === 1 ? 'hot' : '' }}">
                @if($loop->index === 1)<div class="hot-badge">Melhor Valor</div>@endif
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
                   {{ $loop->index === 1 ? '🚀 Ativar Performance' : 'Escolher Plano' }}
                </a>
            </div>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-dim)">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:16px"></i>
                Planos sob consulta — <a href="{{ route('login') }}" style="color:var(--accent)">fale conosco</a>
            </div>
            @endforelse
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-row">
        <div class="footer-brand">
            <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi" style="height:28px;filter:brightness(0) invert(1);opacity:.6">
            <p>Performance e inteligência para gestores e equipes de projeto.</p>
        </div>
        <div class="footer-col">
            <h5>Soluções</h5>
            <a href="{{ route('solutions.ngo') }}">Para ONGs</a>
            <a href="{{ route('solutions.manager') }}">Para Gestores</a>
            <a href="{{ route('solutions.common') }}">Uso Pessoal</a>
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
        <span>Vivensi Manager — Alta Performance <span class="fb-heart">♥</span></span>
    </div>
</footer>

<script>
// ── Billing toggle ──────────────────────────────────────────────
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

// ── Intersection Observer — fade-up reveal ──────────────────────
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); revealObs.unobserve(e.target); } });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── Navbar scroll ───────────────────────────────────────────────
window.addEventListener('scroll', () => {
    document.getElementById('mainNav').style.borderBottomColor =
        window.scrollY > 60 ? 'rgba(255,255,255,.1)' : 'rgba(255,255,255,.07)';
}, { passive: true });

// ── Parallax background ─────────────────────────────────────────
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
