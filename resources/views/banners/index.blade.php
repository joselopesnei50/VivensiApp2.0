@extends('layouts.app')
@section('title', 'Design Studio — Criador de Banners')
@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<style>
/* ═══════════════════════════════════════════════
   DESIGN STUDIO — CANVA-LIKE BANNER HOME
   ═══════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --ds-bg:       #0f0f13;
    --ds-surface:  #18181f;
    --ds-surface2: #222230;
    --ds-border:   rgba(255,255,255,.07);
    --ds-purple:   #7c5cff;
    --ds-purple2:  #5b3fd8;
    --ds-pink:     #e040fb;
    --ds-cyan:     #00d4ff;
    --ds-text:     #f1f1f5;
    --ds-muted:    #8888a8;
    --ds-radius:   16px;
}

.ds-wrap { font-family:'Inter',sans-serif; background: var(--ds-bg); min-height: 100vh;
    padding: 0 0 80px; color: var(--ds-text); }

/* ── HERO ────────────────────────────────────── */
.ds-hero {
    background: linear-gradient(135deg, #0f0f1a 0%, #16102a 50%, #0f151a 100%);
    padding: 52px 40px 56px;
    position: relative; overflow: hidden;
    border-bottom: 1px solid var(--ds-border);
}
.ds-hero::before {
    content:''; position:absolute; inset:0; pointer-events:none;
    background:
        radial-gradient(ellipse 60% 80% at 10% 50%, rgba(124,92,255,.12) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 85% 20%, rgba(0,212,255,.08) 0%, transparent 55%),
        radial-gradient(ellipse 40% 40% at 70% 80%, rgba(224,64,251,.07) 0%, transparent 50%);
}
.ds-hero-inner { position:relative; max-width:860px; margin:0 auto; text-align:center; }
.ds-hero-tag {
    display:inline-flex; align-items:center; gap:6px;
    background: rgba(124,92,255,.15); border: 1px solid rgba(124,92,255,.3);
    border-radius:100px; padding:5px 14px; font-size:.72rem; font-weight:700;
    color: #a78bfa; letter-spacing:.6px; text-transform:uppercase; margin-bottom:20px;
}
.ds-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem); font-weight:900; line-height: 1.1;
    color: #fff; margin: 0 0 16px;
    background: linear-gradient(135deg, #ffffff 30%, #a78bfa 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}
.ds-hero p { font-size:1rem; color: var(--ds-muted); margin: 0 0 36px; max-width:520px; margin-left:auto; margin-right:auto; line-height:1.6; }

/* Search bar */
.ds-search-wrap { position:relative; max-width:560px; margin: 0 auto; }
.ds-search-bar {
    width:100%; background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.12);
    border-radius: 14px; padding: 16px 60px 16px 52px;
    font-size:.95rem; color: var(--ds-text); outline:none;
    transition: border-color .2s, box-shadow .2s;
    backdrop-filter: blur(10px);
}
.ds-search-bar::placeholder { color: var(--ds-muted); }
.ds-search-bar:focus { border-color: var(--ds-purple); box-shadow: 0 0 0 3px rgba(124,92,255,.2); }
.ds-search-icon { position:absolute; left:18px; top:50%; transform:translateY(-50%); color:var(--ds-muted); font-size:.9rem; }
.ds-search-kbd {
    position:absolute; right:16px; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12);
    border-radius:6px; padding:2px 8px; font-size:.65rem; color:var(--ds-muted); font-weight:600;
}

/* ── CONTENT AREA ─────────────────────────────── */
.ds-content { padding: 40px; }

/* ── SECTION TITLE ───────────────────────────── */
.ds-sec-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.ds-sec-title { font-size:.88rem; font-weight:700; color:var(--ds-muted); letter-spacing:.5px; text-transform:uppercase; }
.ds-sec-action {
    font-size:.75rem; font-weight:600; color: var(--ds-purple); text-decoration:none;
    display:flex; align-items:center; gap:5px; transition:opacity .15s;
}
.ds-sec-action:hover { opacity:.7; color:var(--ds-purple); }

/* ── FORMAT CHIPS ─────────────────────────────── */
.ds-formats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:0; }
.ds-format-chip {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:5px; padding:14px 18px; border-radius:14px; cursor:pointer; min-width:96px;
    background: var(--ds-surface); border: 1.5px solid var(--ds-border);
    transition: all .2s; text-decoration:none; color: var(--ds-text);
    position:relative; overflow:hidden;
}
.ds-format-chip:hover, .ds-format-chip:focus {
    border-color: var(--ds-purple); background: rgba(124,92,255,.1);
    color: var(--ds-text); transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(124,92,255,.2);
}
.ds-format-chip .chip-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display:flex; align-items:center; justify-content:center; font-size:1.2rem;
    margin-bottom:2px;
}
.ds-format-chip .chip-label { font-size:.72rem; font-weight:700; text-align:center; line-height:1.2; }
.ds-format-chip .chip-dim { font-size:.6rem; color:var(--ds-muted); text-align:center; margin-top:1px; }

/* ── TEMPLATE CAROUSEL ────────────────────────── */
.ds-carousel-wrap { position:relative; }
.ds-carousel {
    display:flex; gap:12px; overflow-x:auto; padding-bottom:6px;
    scroll-behavior:smooth; scrollbar-width:none;
}
.ds-carousel::-webkit-scrollbar { display:none; }
.ds-carousel-nav {
    position:absolute; top:50%; transform:translateY(-50%);
    width:36px; height:36px; border-radius:50%; border:none; cursor:pointer;
    background: rgba(255,255,255,.1); backdrop-filter:blur(8px);
    color:#fff; font-size:.8rem; display:flex; align-items:center; justify-content:center;
    transition: all .2s; z-index:2; box-shadow:0 4px 12px rgba(0,0,0,.3);
}
.ds-carousel-nav:hover { background:rgba(124,92,255,.6); transform:translateY(-50%) scale(1.05); }
.ds-carousel-nav.prev { left:-14px; }
.ds-carousel-nav.next { right:-14px; }

/* Template card */
.ds-tpl-card {
    flex: 0 0 155px; height: 200px; border-radius: 14px; cursor:pointer;
    position:relative; overflow:hidden; border: 2px solid transparent;
    transition: all .22s; box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
.ds-tpl-card:hover { transform: translateY(-5px) scale(1.02); border-color: var(--ds-purple); box-shadow: 0 12px 36px rgba(124,92,255,.35); }
.ds-tpl-card-label {
    position:absolute; bottom:0; left:0; right:0;
    background: linear-gradient(to top, rgba(0,0,0,.9) 0%, transparent 100%);
    padding: 28px 10px 10px; font-size:.67rem; font-weight:700; color:#fff;
    text-align:center; line-height:1.3;
    transform: translateY(4px); transition: transform .2s;
}
.ds-tpl-card:hover .ds-tpl-card-label { transform:translateY(0); }
.ds-tpl-card-badge {
    position:absolute; top:8px; right:8px;
    background:rgba(255,255,255,.15); backdrop-filter:blur(6px);
    border-radius:6px; padding:2px 7px; font-size:.58rem; font-weight:800; color:#fff; letter-spacing:.3px;
}
.ds-tpl-card-use {
    position:absolute; inset:0; background:rgba(124,92,255,.0);
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:all .2s;
}
.ds-tpl-card:hover .ds-tpl-card-use { background:rgba(0,0,0,.5); opacity:1; }
.ds-tpl-use-btn {
    background: var(--ds-purple); color:#fff; border:none; border-radius:8px;
    padding:8px 16px; font-size:.75rem; font-weight:700; cursor:pointer;
    transform:scale(.85); transition:transform .15s; white-space:nowrap;
}
.ds-tpl-card:hover .ds-tpl-use-btn { transform:scale(1); }

/* Template card inner art layers */
.tpl-art { position:absolute; inset:0; overflow:hidden; }

/* ── MY BANNERS GRID ──────────────────────────── */
.ds-bn-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px; }
.ds-bn-card {
    background: var(--ds-surface); border-radius:14px; overflow:hidden;
    border: 1.5px solid var(--ds-border); transition:all .2s;
    display:flex; flex-direction:column;
}
.ds-bn-card:hover { transform:translateY(-4px); border-color:rgba(124,92,255,.4); box-shadow:0 12px 32px rgba(0,0,0,.4); }
.ds-bn-thumb { height:145px; position:relative; overflow:hidden; flex-shrink:0; }
.ds-bn-thumb img { width:100%; height:100%; object-fit:cover; }
.ds-bn-fmt { position:absolute; top:7px; left:7px; background:rgba(0,0,0,.6); backdrop-filter:blur(6px); border-radius:5px; padding:2px 7px; font-size:.58rem; font-weight:700; color:#fff; }
.ds-bn-overlay { position:absolute; inset:0; background:rgba(0,0,0,0); display:flex; align-items:center; justify-content:center; transition:.2s; }
.ds-bn-card:hover .ds-bn-overlay { background:rgba(0,0,0,.55); }
.ds-bn-edit-btn { background:#fff; color:#1a1a2e; border:none; border-radius:8px; padding:8px 16px; font-size:.75rem; font-weight:700; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:5px; transform:scale(.85); transition:.15s; }
.ds-bn-card:hover .ds-bn-edit-btn { transform:scale(1); }
.ds-bn-body { padding:10px 12px 4px; flex:1; }
.ds-bn-name { font-weight:700; font-size:.82rem; color:var(--ds-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ds-bn-meta { font-size:.66rem; color:var(--ds-muted); margin-top:2px; }
.ds-bn-footer { padding:8px 12px 12px; display:flex; gap:6px; }
.ds-bn-btn { flex:1; background:var(--ds-purple); color:#fff; border:none; border-radius:8px; padding:7px; font-size:.75rem; font-weight:700; cursor:pointer; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px; transition:.15s; }
.ds-bn-btn:hover { background:var(--ds-purple2); color:#fff; }
.ds-bn-menu-btn { background:var(--ds-surface2); border:1px solid var(--ds-border); border-radius:8px; padding:7px 10px; cursor:pointer; color:var(--ds-muted); font-size:.75rem; transition:.15s; }
.ds-bn-menu-btn:hover { border-color:rgba(124,92,255,.4); color:var(--ds-text); }

/* ── NEW BANNER btn ──────────────────────────── */
.ds-btn-new {
    background: var(--ds-purple); color:#fff; border:none; border-radius:12px;
    padding:12px 24px; font-weight:700; font-size:.88rem; cursor:pointer;
    display:inline-flex; align-items:center; gap:8px;
    box-shadow: 0 4px 18px rgba(124,92,255,.4); transition:all .18s;
    text-decoration:none; white-space:nowrap;
}
.ds-btn-new:hover { background:var(--ds-purple2); color:#fff; transform:translateY(-1px); box-shadow:0 8px 28px rgba(124,92,255,.5); }

/* ── EMPTY STATE ──────────────────────────────── */
.ds-empty {
    text-align:center; padding:60px 24px;
    border-radius:16px; border:2px dashed rgba(255,255,255,.08);
    background:var(--ds-surface);
}
.ds-empty-icon { font-size:2.8rem; color:var(--ds-muted); margin-bottom:16px; }

/* ── DIVIDER ──────────────────────────────────── */
.ds-divider { height:1px; background:var(--ds-border); margin:36px 0; }

/* ── FORMAT MODAL (dark) ──────────────────────── */
.ds-modal .modal-content { background:var(--ds-surface); color:var(--ds-text); border:1px solid var(--ds-border); border-radius:20px; }
.ds-modal .modal-header { border-bottom:1px solid var(--ds-border); }
.ds-modal .modal-footer { border-top:1px solid var(--ds-border); }
.ds-modal .form-control { background:var(--ds-surface2); border:1.5px solid var(--ds-border); color:var(--ds-text); border-radius:10px; }
.ds-modal .form-control:focus { background:var(--ds-surface2); border-color:var(--ds-purple); color:var(--ds-text); box-shadow:0 0 0 3px rgba(124,92,255,.2); }
.ds-fmt-card {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:12px 8px; border:1.5px solid var(--ds-border); border-radius:12px; cursor:pointer;
    text-align:center; transition:.15s; color:var(--ds-muted); min-height:84px; background:var(--ds-surface2);
}
.ds-fmt-card:hover { border-color:var(--ds-purple); color:var(--ds-purple); background:rgba(124,92,255,.1); }
.ds-fmt-card.active { border-color:var(--ds-purple); background:rgba(124,92,255,.15); color:#a78bfa; box-shadow:0 0 0 3px rgba(124,92,255,.15); }
.ds-modal .form-label { color:var(--ds-muted); font-size:.8rem; font-weight:600; letter-spacing:.3px; text-transform:uppercase; }

/* ── SEARCH FILTER (live) ─────────────────────── */
.ds-filter-chips { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
.ds-filter-chip {
    padding:5px 14px; border-radius:100px; font-size:.72rem; font-weight:700;
    border:1.5px solid var(--ds-border); background:transparent; color:var(--ds-muted);
    cursor:pointer; transition:.15s;
}
.ds-filter-chip:hover, .ds-filter-chip.active { border-color:var(--ds-purple); color:var(--ds-purple); background:rgba(124,92,255,.1); }

/* Sections padding */
.ds-section { margin-bottom:44px; }

@media(max-width:768px) {
    .ds-hero { padding:36px 20px 42px; }
    .ds-content { padding:24px 16px; }
    .ds-tpl-card { flex:0 0 130px; height:170px; }
    .ds-bn-grid { grid-template-columns:repeat(2,1fr); gap:10px; }
    .ds-formats { gap:8px; }
    .ds-format-chip { min-width:78px; padding:10px 12px; }
}
</style>

{{-- ═══════════ MAIN WRAPPER ═══════════ --}}
<div class="ds-wrap" id="ds-app">

    {{-- ══ HERO ══════════════════════════════════ --}}
    <div class="ds-hero">
        <div class="ds-hero-inner">
            <div class="ds-hero-tag">
                <i class="fas fa-wand-magic-sparkles"></i> Design Studio
            </div>
            <h1>O que você quer criar hoje?</h1>
            <p>Posts, stories, banners e muito mais — tudo com templates profissionais prontos para usar.</p>

            {{-- Search --}}
            <div class="ds-search-wrap">
                <i class="fas fa-magnifying-glass ds-search-icon"></i>
                <input type="text" class="ds-search-bar" id="ds-global-search"
                       placeholder="Buscar templates ou formatos..."
                       oninput="dsLiveSearch(this.value)" autocomplete="off">
                <span class="ds-search-kbd">⌘K</span>
            </div>

            {{-- Filter chips --}}
            <div class="ds-filter-chips" id="filter-chips">
                <button class="ds-filter-chip active" onclick="dsFilter(this,'all')">Todos</button>
                <button class="ds-filter-chip" onclick="dsFilter(this,'promo')">Promoções</button>
                <button class="ds-filter-chip" onclick="dsFilter(this,'design')">Design Gráfico</button>
                <button class="ds-filter-chip" onclick="dsFilter(this,'corporate')">Corporativo</button>
                <button class="ds-filter-chip" onclick="dsFilter(this,'ngo')">Terceiro Setor</button>
            </div>
        </div>
    </div>

    {{-- ══ CONTENT ════════════════════════════════ --}}
    <div class="ds-content">

        @if(session('success'))
        <div class="alert alert-dismissible mb-4 rounded-3" style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#6ee7b7">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ── FORMATO RÁPIDO ─────────────────────────── --}}
        <div class="ds-section">
            <div class="ds-sec-header">
                <span class="ds-sec-title"><i class="fas fa-grid-2 me-2"></i>Escolha um formato</span>
            </div>
            <div class="ds-formats">
                @php
                $quickFormats = [
                    ['key'=>'instagram_story','icon'=>'fa-instagram','bg'=>'linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)','label'=>'Instagram Story','dim'=>'1080×1920'],
                    ['key'=>'instagram_post','icon'=>'fa-instagram','bg'=>'linear-gradient(135deg,#405de6,#833ab4)','label'=>'Instagram Post','dim'=>'1080×1080'],
                    ['key'=>'facebook_post','icon'=>'fa-facebook-f','bg'=>'linear-gradient(135deg,#1877f2,#0a58ca)','label'=>'Facebook Post','dim'=>'1200×628'],
                    ['key'=>'linkedin_post','icon'=>'fa-linkedin-in','bg'=>'linear-gradient(135deg,#0077b5,#00a0dc)','label'=>'LinkedIn Post','dim'=>'1200×627'],
                    ['key'=>'youtube_thumb','icon'=>'fa-youtube','bg'=>'linear-gradient(135deg,#c4302b,#ff0000)','label'=>'YouTube Thumb','dim'=>'1280×720'],
                    ['key'=>'email_header','icon'=>'fa-envelope','bg'=>'linear-gradient(135deg,#4f46e5,#7c3aed)','label'=>'E-mail Header','dim'=>'600×200'],
                    ['key'=>'custom','icon'=>'fa-sliders','bg'=>'linear-gradient(135deg,#374151,#1f2937)','label'=>'Personalizado','dim'=>'Livre'],
                ];
                @endphp
                @foreach($quickFormats as $qf)
                <button class="ds-format-chip" onclick="openModalWithFormat('{{ $qf['key'] }}')" title="{{ $qf['dim'] }}">
                    <div class="chip-icon" style="background:{{ $qf['bg'] }}">
                        <i class="fab {{ $qf['icon'] }}" style="color:#fff"></i>
                    </div>
                    <span class="chip-label">{{ $qf['label'] }}</span>
                    <span class="chip-dim">{{ $qf['dim'] }}</span>
                </button>
                @endforeach
            </div>
        </div>

        <div class="ds-divider"></div>

        {{-- ── CARROSSEL: PROMOÇÕES & VENDAS ─────────── --}}
        <div class="ds-section" data-category="promo">
            <div class="ds-sec-header">
                <span class="ds-sec-title">🛒 Promoções & Vendas</span>
                <button class="ds-btn-new" onclick="openModalWithFormat('instagram_story')">
                    <i class="fas fa-plus"></i> Novo Design
                </button>
            </div>
            <div class="ds-carousel-wrap">
                <button class="ds-carousel-nav prev" onclick="scrollCarousel('carousel-promo',-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="ds-carousel" id="carousel-promo">

                    {{-- Mega Sale Blue --}}
                    <div class="ds-tpl-card" data-tpl="megaSaleBlue" data-label="Mega Sale Blue" data-tags="promo" onclick="createAndOpen('Mega Sale Blue','instagram_story','megaSaleBlue')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#0a0e2a 0%,#1a237e 50%,#090d25 100%)">
                            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#7c5cff,#00d4ff)"></div>
                            <div style="position:absolute;top:18px;left:12px;right:12px">
                                <div style="font-size:7px;font-weight:900;color:#7c5cff;letter-spacing:3px;text-transform:uppercase">MEGA SALE</div>
                                <div style="font-size:28px;font-weight:900;color:#fde047;line-height:1;margin:2px 0">BLACK</div>
                                <div style="font-size:28px;font-weight:900;color:#fff;line-height:1">FRIDAY</div>
                                <div style="display:flex;align-items:center;gap:4px;margin-top:6px">
                                    <div style="background:#dc2626;border-radius:4px;padding:2px 6px;font-size:7px;font-weight:800;color:#fff">ATÉ</div>
                                    <div style="font-size:18px;font-weight:900;color:#fde047">70%</div>
                                </div>
                                <div style="font-size:6px;font-weight:700;color:rgba(255,255,255,.5);margin-top:4px;letter-spacing:1px">DE DESCONTO</div>
                            </div>
                            <div style="position:absolute;bottom:0;left:0;right:0;height:55px;background:rgba(0,0,0,.6);display:flex;align-items:center;padding:0 12px;gap:6px">
                                <div style="flex:1">
                                    <div style="height:4px;background:rgba(255,255,255,.3);border-radius:2px;width:80%;margin-bottom:3px"></div>
                                    <div style="height:3px;background:rgba(255,255,255,.2);border-radius:2px;width:55%"></div>
                                </div>
                                <div style="background:#7c5cff;border-radius:6px;padding:5px 10px;font-size:6px;font-weight:800;color:#fff">COMPRAR</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Mega Sale Blue</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- BF Weekend Black --}}
                    <div class="ds-tpl-card" data-tpl="bfWeekend" data-label="BF Weekend Dark" data-tags="promo" onclick="createAndOpen('BF Weekend Dark','instagram_story','bfWeekend')">
                        <div class="tpl-art" style="background:#0a0a0a">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 80% 50% at 50% 80%,rgba(220,38,38,.25) 0%,transparent 70%)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="font-size:6px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:4px;margin-bottom:4px">OFERTA ESPECIAL</div>
                                <div style="font-size:30px;font-weight:900;color:#fff;line-height:1">BLACK</div>
                                <div style="font-size:30px;font-weight:900;color:#dc2626;line-height:1">FRIDAY</div>
                                <div style="margin:8px auto;width:60px;height:1px;background:rgba(255,255,255,.2)"></div>
                                <div style="font-size:9px;font-weight:800;color:rgba(255,255,255,.6)">ATÉ <span style="color:#fff;font-size:16px">50%</span> OFF</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:rgba(220,38,38,.9);border-radius:8px;padding:7px;text-align:center;font-size:7px;font-weight:800;color:#fff;letter-spacing:1px">VER OFERTAS AGORA →</div>
                        </div>
                        <div class="ds-tpl-card-label">BF Weekend Dark</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Travel Explorer --}}
                    <div class="ds-tpl-card" data-tpl="travelExplorer" data-label="Travel Explorer" data-tags="promo" onclick="createAndOpen('Travel Explorer','instagram_story','travelExplorer')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#001a2c 0%,#0284c7 70%,#0d9488 100%)">
                            <div style="position:absolute;top:0;right:0;width:60%;height:55%;background:rgba(255,255,255,.04);border-bottom-left-radius:60px"></div>
                            <div style="position:absolute;top:14px;left:12px">
                                <div style="font-size:5px;font-weight:700;color:rgba(255,255,255,.5);letter-spacing:3px">TRAVEL & EXPLORE</div>
                                <div style="font-size:20px;font-weight:900;color:#fde047;line-height:1.1;margin-top:3px">Descubra<br>o Mundo</div>
                                <div style="margin-top:8px;background:rgba(253,224,71,.9);border-radius:6px;padding:3px 8px;display:inline-block;font-size:6px;font-weight:800;color:#1a1a00">PACOTES ATÉ 40% OFF</div>
                            </div>
                            <div style="position:absolute;bottom:0;left:0;right:0;height:65px;background:rgba(0,0,0,.55);display:flex;flex-direction:column;justify-content:center;padding:0 12px">
                                <div style="height:4px;background:rgba(255,255,255,.3);border-radius:2px;width:75%;margin-bottom:3px"></div>
                                <div style="height:3px;background:rgba(255,255,255,.15);border-radius:2px;width:50%"></div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Travel Explorer</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Camera Dourada --}}
                    <div class="ds-tpl-card" data-tpl="bfCamera" data-label="BF Câmera Dourada" data-tags="promo" onclick="createAndOpen('BF Câmera Dourada','instagram_story','bfCamera')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#1a0a00 0%,#4a1c00 60%,#1a0a00 100%)">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 60% 40%,rgba(217,119,6,.3) 0%,transparent 70%)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px">
                                <div style="font-size:6px;font-weight:700;color:#d97706;letter-spacing:3px">BLACK FRIDAY</div>
                                <div style="font-size:9px;font-weight:800;color:rgba(255,255,255,.5);margin:3px 0">CÂMERAS & FOTOGRAFIA</div>
                                <div style="font-size:24px;font-weight:900;color:#fbbf24;line-height:1">-60%</div>
                                <div style="font-size:7px;color:rgba(255,255,255,.4)">DE DESCONTO</div>
                                <div style="margin-top:6px;width:36px;height:36px;background:rgba(255,255,255,.08);border-radius:50%;border:1.5px solid rgba(217,119,6,.5);display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-camera" style="font-size:14px;color:#fbbf24"></i>
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;border:1px solid rgba(217,119,6,.6);border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:700;color:#d97706">APROVEITE A OFERTA</div>
                        </div>
                        <div class="ds-tpl-card-label">BF Câmera Dourada</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Business Orange --}}
                    <div class="ds-tpl-card" data-tpl="businessOrange" data-label="Business Orange" data-tags="promo" onclick="createAndOpen('Business Orange','facebook_post','businessOrange')">
                        <div class="tpl-art" style="background:linear-gradient(135deg,#ea580c 0%,#d97706 100%)">
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:14px">
                                <div style="font-size:6px;font-weight:800;color:rgba(255,255,255,.6);letter-spacing:3px;margin-bottom:5px">OFERTA ESPECIAL</div>
                                <div style="font-size:22px;font-weight:900;color:#fff;line-height:1.1">Transforme<br>seu negócio</div>
                                <div style="margin:8px 0;height:2px;background:rgba(255,255,255,.3);width:50%"></div>
                                <div style="font-size:8px;color:rgba(255,255,255,.8);line-height:1.5">Consultoria completa<br>com 30% de desconto</div>
                                <div style="margin-top:10px;background:#fff;border-radius:6px;padding:5px 10px;font-size:7px;font-weight:800;color:#ea580c;display:inline-block">SAIBA MAIS →</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Business Orange</div>
                        <div class="ds-tpl-card-badge">Post</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- BF Fone --}}
                    <div class="ds-tpl-card" data-tpl="bfFone" data-label="BF Fone Preto" data-tags="promo" onclick="createAndOpen('BF Fone Preto','instagram_story','bfFone')">
                        <div class="tpl-art" style="background:#0a0a0a">
                            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#eab308"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px">
                                <div style="font-size:6px;font-weight:700;color:#eab308;letter-spacing:3px">BLACK FRIDAY</div>
                                <div style="font-size:8px;font-weight:800;color:rgba(255,255,255,.4);margin-bottom:4px">FONES & ÁUDIO</div>
                                <div style="display:flex;align-items:flex-end;gap:4px">
                                    <div style="font-size:26px;font-weight:900;color:#fde047;line-height:1">80%</div>
                                    <div style="font-size:7px;color:rgba(255,255,255,.5);padding-bottom:4px">OFF</div>
                                </div>
                                <div style="margin-top:8px;width:32px;height:32px;background:rgba(255,255,255,.05);border-radius:50%;border:1px solid rgba(234,179,8,.4);display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-headphones" style="font-size:13px;color:#fde047"></i>
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:#eab308;border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#000">COMPRAR AGORA</div>
                        </div>
                        <div class="ds-tpl-card-label">BF Fone Preto</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                </div>
                <button class="ds-carousel-nav next" onclick="scrollCarousel('carousel-promo',1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        {{-- ── CARROSSEL: DESIGN GRÁFICO ──────────────── --}}
        <div class="ds-section" data-category="design">
            <div class="ds-sec-header">
                <span class="ds-sec-title">🎨 Design Gráfico & Agência</span>
            </div>
            <div class="ds-carousel-wrap">
                <button class="ds-carousel-nav prev" onclick="scrollCarousel('carousel-design',-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="ds-carousel" id="carousel-design">

                    {{-- Agência Square --}}
                    <div class="ds-tpl-card" data-tpl="agenciaSquare" data-label="Agência Digital" data-tags="design" onclick="createAndOpen('Agência Digital','instagram_story','agenciaSquare')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#0d1117 0%,#162d40 50%,#0d2234 100%)">
                            <div style="position:absolute;top:0;left:0;width:3px;height:100%;background:linear-gradient(180deg,#00d4ff,#7c5cff)"></div>
                            <div style="position:absolute;top:14px;left:16px;right:12px">
                                <div style="font-size:5px;font-weight:700;color:#00d4ff;letter-spacing:3px">MARKETING DIGITAL</div>
                                <div style="font-size:18px;font-weight:900;color:#fff;line-height:1.1;margin-top:4px">Sua marca<br>no <span style="color:#00d4ff">próximo<br>nível</span></div>
                                <div style="margin-top:10px;height:1px;background:rgba(0,212,255,.2);width:70%"></div>
                                <div style="margin-top:8px;font-size:6px;color:rgba(255,255,255,.4);line-height:1.6">
                                    Strategy · Creative · Performance
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:16px;right:12px;display:flex;align-items:center;gap:6px">
                                <div style="flex:1;height:4px;background:rgba(255,255,255,.1);border-radius:2px"></div>
                                <div style="background:linear-gradient(135deg,#00d4ff,#7c5cff);border-radius:6px;padding:4px 8px;font-size:6px;font-weight:800;color:#fff">CONTATO</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Agência Digital</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Startup Talkshow --}}
                    <div class="ds-tpl-card" data-tpl="startupTalkshow" data-label="Startup Talkshow" data-tags="design" onclick="createAndOpen('Startup Talkshow','instagram_story','startupTalkshow')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#0a1f1a 0%,#0d4a3e 60%,#0a2010 100%)">
                            <div style="position:absolute;top:10px;left:10px;right:10px;border:1px solid rgba(212,175,55,.4);border-radius:12px;padding:12px">
                                <div style="font-size:6px;font-weight:700;color:#d4af37;letter-spacing:2px;text-align:center">STARTUP TALK</div>
                                <div style="font-size:14px;font-weight:900;color:#fff;text-align:center;line-height:1.2;margin:4px 0">Inovação<br>& Negócios</div>
                                <div style="display:flex;justify-content:center;gap:4px;margin-top:6px">
                                    <div style="width:20px;height:20px;border-radius:50%;background:rgba(212,175,55,.2);border:1px solid rgba(212,175,55,.4);display:flex;align-items:center;justify-content:center">
                                        <i class="fas fa-microphone" style="font-size:8px;color:#d4af37"></i>
                                    </div>
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:10px;right:10px;background:rgba(212,175,55,.1);border:1px solid rgba(212,175,55,.25);border-radius:8px;padding:6px;text-align:center;font-size:6px;font-weight:700;color:#d4af37">EPISÓDIO AO VIVO</div>
                        </div>
                        <div class="ds-tpl-card-label">Startup Talkshow</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Fashion Pop --}}
                    <div class="ds-tpl-card" data-tpl="fashionPop" data-label="Fashion Pop" data-tags="design" onclick="createAndOpen('Fashion Pop','instagram_story','fashionPop')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#1a0014 0%,#6b0057 50%,#1a000a 100%)">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 50%,rgba(236,72,153,.3) 0%,transparent 70%)"></div>
                            <div style="position:absolute;top:0;left:50%;transform:translateX(-50%);width:2px;height:100%;background:linear-gradient(180deg,transparent,rgba(236,72,153,.6),transparent)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="font-size:6px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:4px">NEW COLLECTION</div>
                                <div style="font-size:22px;font-weight:900;color:#fff;line-height:1;margin:4px 0">FASHION<br><span style="color:#f9a8d4">POP</span></div>
                                <div style="font-size:6px;color:rgba(255,255,255,.4)">SUMMER · SPRING</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:rgba(236,72,153,.8);border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#fff">EXPLORAR COLEÇÃO</div>
                        </div>
                        <div class="ds-tpl-card-label">Fashion Pop</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Split Geo --}}
                    <div class="ds-tpl-card" data-tpl="splitGeo" data-label="Split Geo" data-tags="design" onclick="createAndOpen('Split Geo','facebook_post','splitGeo')">
                        <div class="tpl-art" style="background:#fff">
                            <div style="position:absolute;top:0;left:0;width:50%;height:100%;background:#1e1b4b"></div>
                            <div style="position:absolute;top:0;left:45%;width:25%;height:100%;background:#4f46e5;transform:skewX(-5deg)"></div>
                            <div style="position:absolute;top:0;left:0;width:50%;height:100%;display:flex;flex-direction:column;justify-content:center;padding:14px">
                                <div style="font-size:5px;font-weight:700;color:#a5b4fc;letter-spacing:3px">SERVIÇOS</div>
                                <div style="font-size:15px;font-weight:900;color:#fff;line-height:1.1;margin:3px 0">Design<br>Premium</div>
                                <div style="height:2px;background:#7c5cff;width:30px;margin:5px 0"></div>
                                <div style="font-size:5px;color:rgba(255,255,255,.5)">Sua marca com<br>excelência visual</div>
                            </div>
                            <div style="position:absolute;top:0;right:0;width:42%;height:100%;display:flex;flex-direction:column;justify-content:center;padding:10px;gap:5px">
                                @for($i=0;$i<4;$i++)
                                <div style="background:#f1f5f9;border-radius:4px;padding:4px 6px;font-size:5px;font-weight:700;color:#374151">Serviço {{ $i+1 }}</div>
                                @endfor
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Split Geo</div>
                        <div class="ds-tpl-card-badge">Post</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Neon Dark --}}
                    <div class="ds-tpl-card" data-tpl="neonDark" data-label="Neon Dark" data-tags="design" onclick="createAndOpen('Neon Dark','instagram_story','neonDark')">
                        <div class="tpl-art" style="background:#030307">
                            <div style="position:absolute;inset:0">
                                <div style="position:absolute;top:30%;left:50%;transform:translate(-50%,-50%);width:80px;height:80px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.4) 0%,transparent 70%);filter:blur(12px)"></div>
                            </div>
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="font-size:5px;font-weight:700;color:#7c5cff;letter-spacing:4px;text-shadow:0 0 8px rgba(124,92,255,.8)">◈ STUDIO ◈</div>
                                <div style="font-size:22px;font-weight:900;line-height:1;margin:5px 0;
                                    background:linear-gradient(135deg,#7c5cff,#00d4ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">NEON<br>DARK</div>
                                <div style="height:1px;background:linear-gradient(90deg,transparent,#7c5cff,transparent);width:80%;margin:6px auto"></div>
                                <div style="font-size:6px;color:rgba(255,255,255,.3)">creative · studio · design</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;border:1px solid rgba(124,92,255,.5);border-radius:8px;padding:6px;text-align:center;font-size:6px;font-weight:700;color:#7c5cff;box-shadow:0 0 10px rgba(124,92,255,.2)">ENTRAR EM CONTATO</div>
                        </div>
                        <div class="ds-tpl-card-label">Neon Dark</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                </div>
                <button class="ds-carousel-nav next" onclick="scrollCarousel('carousel-design',1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        {{-- ── CARROSSEL: CORPORATIVO & SAAS ──────────── --}}
        <div class="ds-section" data-category="corporate">
            <div class="ds-sec-header">
                <span class="ds-sec-title">🏢 Corporativo & SaaS</span>
            </div>
            <div class="ds-carousel-wrap">
                <button class="ds-carousel-nav prev" onclick="scrollCarousel('carousel-corp',-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="ds-carousel" id="carousel-corp">

                    {{-- KPI Report --}}
                    <div class="ds-tpl-card" data-tpl="kpiReport" data-label="KPI Report" data-tags="corporate" onclick="createAndOpen('KPI Report','linkedin_post','kpiReport')">
                        <div class="tpl-art" style="background:#0f172a">
                            <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#6366f1,#06b6d4)"></div>
                            <div style="position:absolute;top:12px;left:12px;right:12px">
                                <div style="font-size:5px;font-weight:700;color:#64748b;letter-spacing:3px;text-transform:uppercase">RELATÓRIO MENSAL</div>
                                <div style="font-size:14px;font-weight:800;color:#fff;margin:4px 0">Performance<br>Q1 2025</div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;margin-top:8px">
                                    <div style="background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:6px;padding:5px;text-align:center">
                                        <div style="font-size:12px;font-weight:800;color:#818cf8">+42%</div>
                                        <div style="font-size:5px;color:#64748b;margin-top:1px">Crescimento</div>
                                    </div>
                                    <div style="background:rgba(6,182,212,.15);border:1px solid rgba(6,182,212,.3);border-radius:6px;padding:5px;text-align:center">
                                        <div style="font-size:12px;font-weight:800;color:#67e8f9">R$2M</div>
                                        <div style="font-size:5px;color:#64748b;margin-top:1px">Receita</div>
                                    </div>
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px">
                                <div style="display:flex;gap:2px;align-items:flex-end;height:24px">
                                    @foreach([40,60,45,80,55,90,70] as $h)
                                    <div style="flex:1;background:rgba(99,102,241,.{{ intval($h/10) }});border-radius:2px 2px 0 0;height:{{ $h }}%"></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">KPI Report</div>
                        <div class="ds-tpl-card-badge">LinkedIn</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Lançamento SaaS --}}
                    <div class="ds-tpl-card" data-tpl="lancamentoSaas" data-label="Lançamento SaaS" data-tags="corporate" onclick="createAndOpen('Lançamento SaaS','linkedin_post','lancamentoSaas')">
                        <div class="tpl-art" style="background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 100%)">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 50% at 50% 30%,rgba(99,102,241,.2) 0%,transparent 70%)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="display:inline-flex;align-items:center;gap:4px;background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);border-radius:100px;padding:3px 10px;margin-bottom:6px">
                                    <div style="width:5px;height:5px;background:#6366f1;border-radius:50%"></div>
                                    <div style="font-size:5px;font-weight:700;color:#a5b4fc;letter-spacing:2px">NOVO PRODUTO</div>
                                </div>
                                <div style="font-size:18px;font-weight:900;color:#fff;line-height:1.1">Apresentando<br><span style="color:#818cf8">v2.0</span></div>
                                <div style="margin:8px auto;font-size:6px;color:rgba(255,255,255,.4)">A plataforma reimaginada<br>pra sua equipe crescer</div>
                                <div style="background:#6366f1;border-radius:6px;padding:5px 14px;font-size:6px;font-weight:800;color:#fff;display:inline-block">ACESSAR AGORA →</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Lançamento SaaS</div>
                        <div class="ds-tpl-card-badge">LinkedIn</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Webinar --}}
                    <div class="ds-tpl-card" data-tpl="webinarCard" data-label="Webinar Card" data-tags="corporate" onclick="createAndOpen('Webinar Card','facebook_post','webinarCard')">
                        <div class="tpl-art" style="background:#fff">
                            <div style="position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,#6366f1,#8b5cf6)"></div>
                            <div style="position:absolute;top:18px;left:12px;right:12px">
                                <div style="background:#eef2ff;border-radius:6px;padding:2px 8px;font-size:5px;font-weight:800;color:#4f46e5;display:inline-block;letter-spacing:1px">WEBINAR GRATUITO</div>
                                <div style="font-size:14px;font-weight:800;color:#1e293b;line-height:1.2;margin:5px 0">Como Acelerar<br>Resultados em 2025</div>
                                <div style="height:1px;background:#e2e8f0;margin:8px 0"></div>
                                <div style="display:flex;align-items:center;gap:5px;margin-bottom:4px">
                                    <i class="fas fa-calendar" style="font-size:7px;color:#6366f1"></i>
                                    <span style="font-size:6px;color:#64748b;font-weight:600">15 de Julho · 19h00</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:5px">
                                    <i class="fas fa-video" style="font-size:7px;color:#6366f1"></i>
                                    <span style="font-size:6px;color:#64748b;font-weight:600">Online · Gratuito</span>
                                </div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:#6366f1;border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#fff">INSCREVER-SE GRÁTIS</div>
                        </div>
                        <div class="ds-tpl-card-label">Webinar Card</div>
                        <div class="ds-tpl-card-badge">Post</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Digital Mktg --}}
                    <div class="ds-tpl-card" data-tpl="digitalMktg" data-label="Digital Marketing" data-tags="corporate" onclick="createAndOpen('Digital Marketing','instagram_post','digitalMktg')">
                        <div class="tpl-art" style="background:linear-gradient(135deg,#022c22 0%,#064e3b 100%)">
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:6px;padding:14px">
                                <div style="width:36px;height:36px;background:rgba(16,185,129,.2);border:1.5px solid rgba(16,185,129,.5);border-radius:10px;display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-chart-line" style="font-size:14px;color:#34d399"></i>
                                </div>
                                <div style="font-size:14px;font-weight:900;color:#fff;text-align:center;line-height:1.1">Marketing<br>Digital</div>
                                <div style="font-size:6px;color:rgba(255,255,255,.4);text-align:center">Atraia, converta<br>e fidelize clientes</div>
                                <div style="background:#10b981;border-radius:6px;padding:4px 10px;font-size:6px;font-weight:800;color:#fff">COMEÇAR</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Digital Marketing</div>
                        <div class="ds-tpl-card-badge">Post</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                </div>
                <button class="ds-carousel-nav next" onclick="scrollCarousel('carousel-corp',1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        {{-- ── CARROSSEL: TERCEIRO SETOR ───────────────── --}}
        <div class="ds-section" data-category="ngo">
            <div class="ds-sec-header">
                <span class="ds-sec-title">💚 Terceiro Setor & ONG</span>
            </div>
            <div class="ds-carousel-wrap">
                <button class="ds-carousel-nav prev" onclick="scrollCarousel('carousel-ngo',-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="ds-carousel" id="carousel-ngo">

                    {{-- NGO Neon --}}
                    <div class="ds-tpl-card" data-tpl="ngoNeon" data-label="NGO Neon" data-tags="ngo" onclick="createAndOpen('NGO Neon','instagram_story','ngoNeon')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#030312 0%,#1e1b4b 60%,#030312 100%)">
                            <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 50% at 50% 40%,rgba(99,102,241,.3) 0%,transparent 70%)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="width:30px;height:30px;margin:0 auto 6px;background:rgba(99,102,241,.2);border:1.5px solid rgba(99,102,241,.5);border-radius:50%;display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-heart" style="font-size:11px;color:#818cf8"></i>
                                </div>
                                <div style="font-size:7px;font-weight:700;color:#818cf8;letter-spacing:2px">SOLIDARIEDADE</div>
                                <div style="font-size:18px;font-weight:900;color:#fff;line-height:1.1;margin:4px 0">Juntos<br>Podemos<br>Mais</div>
                                <div style="height:1px;background:linear-gradient(90deg,transparent,#6366f1,transparent);width:70%;margin:6px auto"></div>
                                <div style="font-size:6px;color:rgba(255,255,255,.4)">Transformando vidas<br>através da ação coletiva</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:rgba(99,102,241,.8);border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#fff">SEJA VOLUNTÁRIO</div>
                        </div>
                        <div class="ds-tpl-card-label">NGO Neon</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Social Impact --}}
                    <div class="ds-tpl-card" data-tpl="socialImpact" data-label="Social Impact" data-tags="ngo" onclick="createAndOpen('Social Impact','facebook_post','socialImpact')">
                        <div class="tpl-art" style="background:linear-gradient(135deg,#052e16 0%,#064e3b 100%)">
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;justify-content:center;padding:14px">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                                    <div style="width:26px;height:26px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.4);border-radius:50%;display:flex;align-items:center;justify-content:center">
                                        <i class="fas fa-leaf" style="font-size:10px;color:#34d399"></i>
                                    </div>
                                    <div style="font-size:5px;font-weight:700;color:#34d399;letter-spacing:2px">IMPACTO SOCIAL</div>
                                </div>
                                <div style="font-size:16px;font-weight:900;color:#fff;line-height:1.1">Cada ação<br>transforma<br><span style="color:#6ee7b7">vidas</span></div>
                                <div style="margin-top:8px;font-size:6px;color:rgba(255,255,255,.4);line-height:1.5">Sua participação faz<br>a diferença real</div>
                                <div style="margin-top:8px;background:#10b981;border-radius:6px;padding:5px 10px;font-size:6px;font-weight:800;color:#fff;display:inline-block">APOIE AGORA</div>
                            </div>
                        </div>
                        <div class="ds-tpl-card-label">Social Impact</div>
                        <div class="ds-tpl-card-badge">Post</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Campanha Doação --}}
                    <div class="ds-tpl-card" data-tpl="campanhaDoacao" data-label="Campanha de Doação" data-tags="ngo" onclick="createAndOpen('Campanha de Doação','instagram_story','campanhaDoacao')">
                        <div class="tpl-art" style="background:#fff">
                            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#f59e0b,#ef4444)"></div>
                            <div style="position:absolute;top:14px;left:12px;right:12px">
                                <div style="background:#fef3c7;border-radius:6px;padding:2px 8px;font-size:5px;font-weight:800;color:#d97706;display:inline-block">CAMPANHA URGENTE</div>
                                <div style="font-size:15px;font-weight:800;color:#1e293b;line-height:1.2;margin:5px 0">Sua doação<br>salva vidas</div>
                                <div style="height:1px;background:#f1f5f9;margin:6px 0"></div>
                                <div style="font-size:6px;color:#64748b;line-height:1.5">Meta: <strong style="color:#1e293b">R$ 50.000</strong><br>Arrecadado: <strong style="color:#10b981">R$ 32.400</strong></div>
                                <div style="margin-top:5px;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden">
                                    <div style="width:64%;height:100%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:4px"></div>
                                </div>
                                <div style="font-size:5px;color:#94a3b8;margin-top:2px">64% da meta alcançada</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:#ef4444;border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#fff">QUERO CONTRIBUIR ❤️</div>
                        </div>
                        <div class="ds-tpl-card-label">Campanha de Doação</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                    {{-- Voluntariado --}}
                    <div class="ds-tpl-card" data-tpl="voluntariado" data-label="Call Voluntários" data-tags="ngo" onclick="createAndOpen('Call Voluntários','instagram_story','voluntariado')">
                        <div class="tpl-art" style="background:linear-gradient(160deg,#1a0a2e 0%,#3b1f5e 50%,#1a0a2e 100%)">
                            <div style="position:absolute;top:14px;left:12px;right:12px;text-align:center">
                                <div style="font-size:6px;font-weight:700;color:#a855f7;letter-spacing:3px;margin-bottom:6px">SEJA VOLUNTÁRIO</div>
                                <div style="display:flex;justify-content:center;gap:3px;margin-bottom:6px">
                                    @for($i=0;$i<3;$i++)
                                    <div style="width:20px;height:20px;background:rgba(168,85,247,.2);border:1px solid rgba(168,85,247,.4);border-radius:50%;display:flex;align-items:center;justify-content:center">
                                        <i class="fas fa-user" style="font-size:8px;color:#a855f7"></i>
                                    </div>
                                    @endfor
                                </div>
                                <div style="font-size:16px;font-weight:900;color:#fff;line-height:1.2">Faça parte<br>da mudança</div>
                                <div style="margin:6px auto;font-size:6px;color:rgba(255,255,255,.4)">Doe seu tempo,<br>multiplique o impacto</div>
                            </div>
                            <div style="position:absolute;bottom:10px;left:12px;right:12px;background:rgba(168,85,247,.8);border-radius:8px;padding:6px;text-align:center;font-size:7px;font-weight:800;color:#fff">QUIERO PARTICIPAR</div>
                        </div>
                        <div class="ds-tpl-card-label">Call Voluntários</div>
                        <div class="ds-tpl-card-badge">Story</div>
                        <div class="ds-tpl-card-use"><button class="ds-tpl-use-btn"><i class="fas fa-wand-magic-sparkles me-1"></i> Usar</button></div>
                    </div>

                </div>
                <button class="ds-carousel-nav next" onclick="scrollCarousel('carousel-ngo',1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        <div class="ds-divider"></div>

        {{-- ── MEUS BANNERS ──────────────────────────── --}}
        <div class="ds-section">
            <div class="ds-sec-header">
                <div>
                    <span class="ds-sec-title">📁 Meus Designs</span>
                    @if($banners->count() > 0)
                    <span style="background:rgba(124,92,255,.2);color:#a78bfa;border-radius:100px;padding:2px 10px;font-size:.7rem;font-weight:700;margin-left:8px">{{ $banners->count() }}</span>
                    @endif
                </div>
                <div style="display:flex;gap:10px;align-items:center">
                    @if($banners->count() > 0)
                    <div style="position:relative">
                        <i class="fas fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ds-muted);font-size:.75rem"></i>
                        <input type="text" id="bn-search" placeholder="Buscar designs..."
                               oninput="filterBanners(this.value)"
                               style="background:var(--ds-surface2);border:1.5px solid var(--ds-border);border-radius:10px;padding:8px 14px 8px 34px;font-size:.78rem;color:var(--ds-text);outline:none;width:200px;transition:.2s"
                               onfocus="this.style.borderColor='var(--ds-purple)'" onblur="this.style.borderColor='var(--ds-border)'">
                    </div>
                    @endif
                    <button class="ds-btn-new" data-bs-toggle="modal" data-bs-target="#newBannerModal">
                        <i class="fas fa-plus"></i> Novo Design
                    </button>
                </div>
            </div>

            @if($banners->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><i class="fas fa-image"></i></div>
                <h5 style="color:var(--ds-text);font-weight:800;margin-bottom:8px">Nenhum design ainda</h5>
                <p style="color:var(--ds-muted);font-size:.88rem;margin-bottom:24px">Clique em um template acima para começar, ou crie um design em branco.</p>
                <button class="ds-btn-new" data-bs-toggle="modal" data-bs-target="#newBannerModal">
                    <i class="fas fa-plus"></i> Criar Primeiro Design
                </button>
            </div>
            @else
            <div class="ds-bn-grid" id="banners-grid">
                @foreach($banners as $banner)
                @php
                $gradients = [
                    'linear-gradient(135deg,#1e3a8a,#312e81)',
                    'linear-gradient(135deg,#111,#dc2626)',
                    'linear-gradient(135deg,#162d40,#0d9488)',
                    'linear-gradient(135deg,#064e3b,#10b981)',
                    'linear-gradient(135deg,#4a044e,#db2777)',
                    'linear-gradient(135deg,#1a0a00,#d97706)',
                    'linear-gradient(135deg,#0c1428,#0ea5e9)',
                    'linear-gradient(135deg,#ea580c,#fef3c7)',
                ];
                $grad = $gradients[$banner->id % count($gradients)];
                @endphp
                <div class="ds-bn-card" data-name="{{ strtolower($banner->title) }}">
                    <div class="ds-bn-thumb" style="{{ $banner->png_path ? '' : 'background:'.$grad }}">
                        @if($banner->png_path)
                            <img src="{{ Storage::disk('public')->url($banner->png_path) }}" alt="{{ $banner->title }}" loading="lazy">
                        @else
                            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:14px;gap:5px">
                                <div style="height:7px;border-radius:4px;background:rgba(255,255,255,.7);width:65%"></div>
                                <div style="height:5px;border-radius:3px;background:rgba(255,255,255,.4);width:48%"></div>
                                <div style="height:5px;border-radius:3px;background:rgba(255,255,255,.25);width:34%"></div>
                                <div style="margin-top:6px;background:rgba(255,255,255,.15);border-radius:6px;padding:4px 10px;font-size:7px;font-weight:700;color:rgba(255,255,255,.7)">EDITAR</div>
                            </div>
                        @endif
                        <div class="ds-bn-fmt">{{ $formats[$banner->format]['label'] ?? $banner->format }}</div>
                        <div class="ds-bn-overlay">
                            <a href="{{ route('banners.canvas', $banner) }}" class="ds-bn-edit-btn">
                                <i class="fas fa-pen"></i> Editar
                            </a>
                        </div>
                    </div>
                    <div class="ds-bn-body">
                        <div class="ds-bn-name" title="{{ $banner->title }}">{{ $banner->title }}</div>
                        <div class="ds-bn-meta">{{ $banner->width }}×{{ $banner->height }}px · {{ $banner->updated_at->diffForHumans() }}</div>
                    </div>
                    <div class="ds-bn-footer">
                        <a href="{{ route('banners.canvas', $banner) }}" class="ds-bn-btn">
                            <i class="fas fa-wand-magic-sparkles"></i> Abrir Editor
                        </a>
                        <div class="dropdown">
                            <button class="ds-bn-menu-btn" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="background:#1e1e2e;border:1px solid rgba(255,255,255,.08)!important;border-radius:12px;padding:6px;min-width:180px;font-size:.8rem">
                                <li><a class="dropdown-item rounded-2 py-2" href="{{ route('banners.canvas', $banner) }}" style="color:#e2e8f0"><i class="fas fa-pen me-2" style="color:#818cf8"></i>Abrir Editor</a></li>
                                <li><a class="dropdown-item rounded-2 py-2" href="{{ route('banners.preview', $banner) }}" target="_blank" style="color:#e2e8f0"><i class="fas fa-eye me-2" style="color:#34d399"></i>Pré-visualizar</a></li>
                                <li>
                                    <form action="{{ route('banners.duplicate', $banner) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item rounded-2 py-2" style="color:#e2e8f0"><i class="fas fa-copy me-2" style="color:#fbbf24"></i>Duplicar</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider my-1" style="border-color:rgba(255,255,255,.08)"></li>
                                <li>
                                    <form action="{{ route('banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Remover este design?')">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item rounded-2 py-2 text-danger"><i class="fas fa-trash me-2"></i>Excluir</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>{{-- /ds-content --}}
</div>{{-- /ds-wrap --}}

{{-- ═══════════ MODAL CRIAR BANNER ═══════════ --}}
<div class="modal fade ds-modal" id="newBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-4 pt-4 pb-3">
                <h5 class="modal-title fw-800" style="font-size:1.05rem">
                    <i class="fas fa-image me-2" style="color:var(--ds-purple)"></i>Criar Novo Design
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('banners.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4">
                    <div class="mb-4">
                        <label class="form-label">Nome do Design *</label>
                        <input type="text" name="title" class="form-control form-control-lg"
                               placeholder="Ex: Campanha Black Friday — Novembro" required id="modal-title-input">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Formato *</label>
                        <div class="row g-2" id="formatPicker">
                            @foreach($formats as $key => $fmt)
                            <div class="col-6 col-sm-4 col-md-3">
                                <label class="ds-fmt-card {{ $key === 'instagram_story' ? 'active' : '' }}" id="fcard-{{ $key }}">
                                    <input type="radio" name="format" value="{{ $key }}"
                                           {{ $key === 'instagram_story' ? 'checked' : '' }}
                                           class="d-none" onchange="selectFormat('{{ $key }}')">
                                    <i class="{{ $fmt['icon'] }}" style="font-size:1.2rem;margin-bottom:4px"></i>
                                    <span class="d-block fw-bold" style="font-size:.74rem">{{ $fmt['label'] }}</span>
                                    <span class="d-block" style="font-size:.62rem;color:var(--ds-muted)">{{ $fmt['w'] }}×{{ $fmt['h'] }}px</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div id="customDims" style="display:none" class="row g-2 mt-2">
                            <div class="col-6">
                                <label class="form-label">Largura (px)</label>
                                <input type="number" name="custom_width" class="form-control" value="1200" min="100" max="4000">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Altura (px)</label>
                                <input type="number" name="custom_height" class="form-control" value="628" min="100" max="4000">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="ds-btn-new">
                        <i class="fas fa-arrow-right"></i> Criar e Abrir Editor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ── CAROUSEL ──────────────────────────────── */
function scrollCarousel(id, dir) {
    const el = document.getElementById(id);
    if (el) el.scrollBy({ left: dir * 520, behavior: 'smooth' });
}

/* ── FORMAT MODAL ──────────────────────────── */
function selectFormat(key) {
    document.querySelectorAll('.ds-fmt-card').forEach(c => c.classList.remove('active'));
    const lbl = document.getElementById('fcard-' + key);
    if (lbl) lbl.classList.add('active');
    document.getElementById('customDims').style.display = key === 'custom' ? '' : 'none';
}
function openModalWithFormat(fmtKey) {
    selectFormat(fmtKey);
    const inp = document.querySelector(`#fcard-${fmtKey} input`);
    if (inp) inp.checked = true;
    new bootstrap.Modal(document.getElementById('newBannerModal')).show();
    setTimeout(() => document.getElementById('modal-title-input').focus(), 350);
}

/* ── TEMPLATE → CREATE + OPEN ──────────────── */
function createAndOpen(title, format, template) {
    // Create banner via form submit with prefilled data, then canvas will autoload template
    const modal = document.getElementById('newBannerModal');
    document.getElementById('modal-title-input').value = title;
    selectFormat(format);
    const inp = document.querySelector(`#fcard-${format} input`);
    if (inp) inp.checked = true;
    // Store template key to autoload after redirect
    sessionStorage.setItem('ds_autoload_tpl', template);
    new bootstrap.Modal(modal).show();
    setTimeout(() => document.getElementById('modal-title-input').focus(), 350);
}

/* ── SEARCH (global) ───────────────────────── */
function dsLiveSearch(q) {
    q = q.toLowerCase().trim();
    const sections = document.querySelectorAll('.ds-section[data-category]');
    sections.forEach(sec => {
        const cards = sec.querySelectorAll('.ds-tpl-card');
        let visible = 0;
        cards.forEach(card => {
            const label = (card.dataset.label || '').toLowerCase();
            const tags  = (card.dataset.tags  || '').toLowerCase();
            const match = !q || label.includes(q) || tags.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        sec.style.display = (q && visible === 0) ? 'none' : '';
    });
    // Also filter banner grid
    filterBanners(q);
}

/* ── FILTER CHIPS ──────────────────────────── */
function dsFilter(btn, category) {
    document.querySelectorAll('.ds-filter-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    const sections = document.querySelectorAll('.ds-section[data-category]');
    sections.forEach(sec => {
        sec.style.display = (category === 'all' || sec.dataset.category === category) ? '' : 'none';
    });
    document.getElementById('ds-global-search').value = '';
}

/* ── BANNER SEARCH ─────────────────────────── */
function filterBanners(q) {
    q = q.toLowerCase();
    const grid = document.getElementById('banners-grid');
    if (!grid) return;
    grid.querySelectorAll('.ds-bn-card').forEach(card => {
        card.style.display = (card.dataset.name || '').includes(q) ? '' : 'none';
    });
}

/* ── KEYBOARD SHORTCUT ─────────────────────── */
document.addEventListener('keydown', e => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('ds-global-search').focus();
    }
});
</script>
@endsection
