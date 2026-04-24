<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Editor â€” {{ $banner->title }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@700;800&family=Montserrat:wght@700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&family=Oswald:wght@600;700&family=Raleway:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden;background:#080a10;font-family:'Inter',sans-serif;color:#e2e8f0}

/* ── TOP BAR ─────────────────────────────────────────────── */
#topbar{
    position:fixed;top:0;left:0;right:0;height:54px;
    background:linear-gradient(180deg,#14161f 0%,#111318 100%);
    border-bottom:1px solid rgba(99,102,241,.15);
    display:flex;align-items:center;gap:6px;padding:0 14px;z-index:100;
}
#topbar::after{
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent 0%,rgba(99,102,241,.4) 30%,rgba(139,92,246,.4) 70%,transparent 100%);
}
.tb-logo{
    display:flex;align-items:center;gap:7px;margin-right:4px;
    font-size:12px;font-weight:800;color:#818cf8;letter-spacing:.5px;text-decoration:none;
}
.tb-logo-dot{width:20px;height:20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:900;flex-shrink:0}
.tb-back{
    color:#64748b;font-size:12px;text-decoration:none;
    display:flex;align-items:center;gap:5px;padding:5px 9px;border-radius:7px;transition:all .15s;
    border:1px solid transparent;
}
.tb-back:hover{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.08);color:#94a3b8}
.tb-divider{width:1px;height:22px;background:rgba(255,255,255,.08);margin:0 2px}
#title-input{
    background:transparent;border:1px solid transparent;color:#e2e8f0;
    font-size:13px;font-weight:700;padding:5px 9px;border-radius:7px;
    min-width:150px;max-width:240px;transition:all .15s;
}
#title-input:hover{border-color:rgba(255,255,255,.1);background:rgba(255,255,255,.04)}
#title-input:focus{border-color:#6366f1;outline:none;background:#1a1d26;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
#format-badge{
    font-size:10px;font-weight:700;color:#6366f1;
    background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.2);
    padding:3px 10px;border-radius:100px;white-space:nowrap;letter-spacing:.3px;
}
.tb-spacer{flex:1}
#save-status{
    font-size:11px;color:#475569;display:flex;align-items:center;gap:5px;
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
    border-radius:100px;padding:3px 10px;
}
#save-status.saving{color:#f59e0b}
#save-status.saved{color:#10b981}
.tb-btn{
    display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:7px;
    border:none;font-size:12px;font-weight:700;cursor:pointer;transition:all .18s;white-space:nowrap;
}
.tb-btn-outline{
    background:rgba(255,255,255,.05);color:#94a3b8;
    border:1px solid rgba(255,255,255,.1);
}
.tb-btn-outline:hover{background:rgba(255,255,255,.09);color:#e2e8f0;border-color:rgba(255,255,255,.18)}
.tb-btn-primary{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 2px 12px rgba(99,102,241,.35)}
.tb-btn-primary:hover{background:linear-gradient(135deg,#818cf8,#6366f1);box-shadow:0 4px 18px rgba(99,102,241,.45);transform:translateY(-1px)}
.tb-btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 2px 10px rgba(16,185,129,.3)}
.tb-btn-success:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(16,185,129,.4)}
.tb-btn-ai{background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;box-shadow:0 2px 12px rgba(124,58,237,.35)}
.tb-btn-ai:hover{transform:translateY(-1px);box-shadow:0 4px 18px rgba(124,58,237,.45)}
.undo-redo{display:flex;gap:1px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:7px;padding:2px}
.undo-redo button{
    background:transparent;border:none;color:#64748b;font-size:13px;
    cursor:pointer;padding:5px 8px;border-radius:5px;transition:all .15s;
}
.undo-redo button:hover{background:rgba(255,255,255,.08);color:#e2e8f0}
.undo-redo button:disabled{opacity:.25;cursor:not-allowed}
.zoom-controls{display:flex;align-items:center;gap:1px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:7px;padding:2px}
.zoom-controls button{background:transparent;border:none;color:#64748b;font-size:11px;cursor:pointer;padding:5px 7px;border-radius:5px;transition:all .15s}
.zoom-controls button:hover{background:rgba(255,255,255,.08);color:#e2e8f0}
#zoom-label{font-size:11px;color:#64748b;min-width:36px;text-align:center;font-weight:700}

/* ── LAYOUT ──────────────────────────────────────────────── */
#editor-layout{position:fixed;top:54px;left:0;right:0;bottom:0;display:flex}

/* ── LEFT PANEL ──────────────────────────────────────────── */
#left-panel{
    width:276px;min-width:276px;
    background:#0e1018;border-right:1px solid rgba(255,255,255,.07);
    display:flex;flex-direction:column;overflow:hidden;
}
.lp-tabs{
    display:flex;border-bottom:1px solid rgba(255,255,255,.06);
    background:rgba(0,0,0,.2);padding:0 4px;gap:1px;
}
.lp-tab{
    flex:1;padding:11px 2px;text-align:center;font-size:9.5px;font-weight:700;color:#475569;
    cursor:pointer;border-bottom:2px solid transparent;transition:all .15s;
    letter-spacing:.6px;text-transform:uppercase;
}
.lp-tab.active{color:#818cf8;border-bottom-color:#6366f1}
.lp-tab:hover:not(.active){color:#94a3b8}
.lp-content{flex:1;overflow-y:auto;padding:10px;display:none}
.lp-content.active{display:block}
.lp-content::-webkit-scrollbar{width:3px}
.lp-content::-webkit-scrollbar-thumb{background:rgba(99,102,241,.3);border-radius:2px}
.sec-title{
    font-size:9.5px;font-weight:800;color:#334155;letter-spacing:1.5px;text-transform:uppercase;
    margin:14px 0 7px;display:flex;align-items:center;gap:5px;
}
.sec-title:first-child{margin-top:4px}
.sec-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.05);margin-left:4px}

/* Template cards — portrait aspect + hover overlay */
.tpl-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.tpl-card{
    border-radius:9px;overflow:hidden;cursor:pointer;
    border:1.5px solid rgba(255,255,255,.07);
    transition:all .22s;aspect-ratio:.75;
    position:relative;background-size:cover;background-position:center;
}
.tpl-card:hover{
    border-color:#6366f1;
    transform:translateY(-3px) scale(1.02);
    box-shadow:0 10px 30px rgba(99,102,241,.35);
}
.tpl-label{
    position:absolute;bottom:0;left:0;right:0;
    background:linear-gradient(to top,rgba(0,0,0,.9) 0%,transparent 100%);
    color:#fff;font-size:8.5px;font-weight:700;
    padding:18px 6px 6px;text-align:center;letter-spacing:.4px;line-height:1.2;
}
.tpl-card-apply{
    position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    background:rgba(0,0,0,0);opacity:0;transition:all .18s;
}
.tpl-card:hover .tpl-card-apply{background:rgba(0,0,0,.55);opacity:1}
.tpl-apply-btn{
    background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;
    border-radius:7px;padding:6px 13px;font-size:10px;font-weight:800;cursor:pointer;
    transform:scale(.88);transition:transform .15s;letter-spacing:.3px;
}
.tpl-card:hover .tpl-apply-btn{transform:scale(1)}

/* Elem buttons */
.elem-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.elem-btn{
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;
    color:#64748b;font-size:11px;font-weight:600;padding:11px 8px;cursor:pointer;
    transition:all .15s;display:flex;flex-direction:column;align-items:center;gap:5px;
}
.elem-btn i{font-size:18px;transition:color .15s}
.elem-btn:hover{background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.35);color:#818cf8;transform:translateY(-1px)}
.elem-btn:hover i{color:#6366f1}

/* Icon grid */
.ico-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:5px}
.ico-btn{
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
    border-radius:8px;cursor:pointer;transition:all .15s;
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;
    padding:7px 3px;aspect-ratio:1;
}
.ico-btn svg{width:24px;height:24px;fill:none;stroke:#475569;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;transition:stroke .15s}
.ico-btn span{font-size:7.5px;color:#334155;text-align:center;line-height:1.2;font-weight:700}
.ico-btn:hover{background:rgba(99,102,241,.12);border-color:rgba(99,102,241,.3);transform:translateY(-1px)}
.ico-btn:hover svg{stroke:#818cf8}

/* Image tabs */
.sub-tabs{display:flex;gap:3px;margin-bottom:10px;background:rgba(0,0,0,.3);padding:3px;border-radius:8px;border:1px solid rgba(255,255,255,.06)}
.sub-tab{flex:1;text-align:center;font-size:11px;font-weight:700;padding:5px;cursor:pointer;color:#475569;border-radius:5px;transition:all .15s;letter-spacing:.3px}
.sub-tab.active{background:#1a1d26;color:#e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.4)}
.upload-area{border:2px dashed rgba(255,255,255,.1);border-radius:10px;padding:20px 10px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:10px}
.upload-area:hover{border-color:#6366f1;background:rgba(99,102,241,.06)}
.upload-area i{font-size:22px;color:#334155;margin-bottom:7px;display:block}
.upload-area p{font-size:10.5px;color:#475569;line-height:1.5}
.img-gal{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.img-thumb{aspect-ratio:1;object-fit:cover;border-radius:7px;cursor:pointer;width:100%;border:1.5px solid transparent;transition:all .15s;background:#1e2330;display:block}
.img-thumb:hover{border-color:#6366f1;transform:scale(1.04);box-shadow:0 4px 12px rgba(99,102,241,.3)}

/* Layers */
.layer-item{display:flex;align-items:center;gap:7px;padding:6px 8px;border-radius:7px;cursor:pointer;transition:all .15s;border:1px solid transparent;margin-bottom:2px}
.layer-item:hover{background:rgba(255,255,255,.04)}
.layer-item.active{background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.25)}
.li-icon{font-size:11px;color:#334155;width:14px;text-align:center}
.li-name{flex:1;font-size:11.5px;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.li-act{font-size:10px;color:#334155;cursor:pointer;padding:2px 5px;border-radius:4px;transition:all .15s}
.li-act:hover{color:#ef4444;background:rgba(239,68,68,.1)}

/* ── CANVAS AREA ─────────────────────────────────────────── */
#canvas-area{
    flex:1;display:flex;align-items:center;justify-content:center;
    background:#080a10;overflow:hidden;position:relative;
    background-image:
        radial-gradient(circle,rgba(99,102,241,.08) 1px,transparent 1px);
    background-size:28px 28px;
}
#canvas-area::before{
    content:'';position:absolute;inset:0;pointer-events:none;
    background:
        radial-gradient(ellipse 60% 50% at 50% 0%,rgba(99,102,241,.04) 0%,transparent 60%),
        radial-gradient(ellipse 40% 40% at 50% 100%,rgba(139,92,246,.03) 0%,transparent 60%);
}
#canvas-wrap{
    position:relative;
    box-shadow:
        0 0 0 1px rgba(99,102,241,.35),
        0 0 0 6px rgba(0,0,0,.5),
        0 40px 100px rgba(0,0,0,.8),
        0 0 80px rgba(99,102,241,.1);
    border-radius:3px;transform-origin:center center;
}
#fabric-canvas{display:block}
#snap-canvas{position:absolute;top:0;left:0;pointer-events:none;display:block}

/* ── RIGHT PANEL ─────────────────────────────────────────── */
#right-panel{
    width:276px;min-width:276px;background:#0e1018;
    border-left:1px solid rgba(255,255,255,.07);
    display:flex;flex-direction:column;overflow:hidden;
}
#rp-header{
    padding:12px 14px 10px;border-bottom:1px solid rgba(255,255,255,.06);
    font-size:10px;font-weight:800;color:#334155;letter-spacing:1px;text-transform:uppercase;
    display:flex;align-items:center;gap:7px;background:rgba(0,0,0,.15);
}
#rp-header-icon{
    width:22px;height:22px;background:linear-gradient(135deg,#6366f1,#8b5cf6);
    border-radius:5px;display:flex;align-items:center;justify-content:center;
    font-size:9px;color:#fff;flex-shrink:0;
}
#props-content{flex:1;overflow-y:auto;padding:12px}
#props-content::-webkit-scrollbar{width:3px}
#props-content::-webkit-scrollbar-thumb{background:rgba(99,102,241,.3);border-radius:2px}
.prop-group{margin-bottom:14px}
.prop-group-title{
    font-size:9.5px;font-weight:800;color:#334155;letter-spacing:1.2px;
    text-transform:uppercase;margin-bottom:7px;display:flex;align-items:center;gap:5px;
}
.prop-group-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.05);margin-left:3px}
.prop-row{display:flex;align-items:center;gap:7px;margin-bottom:6px}
.prop-label{font-size:10.5px;color:#475569;min-width:64px;flex-shrink:0;font-weight:600}
.prop-input{
    flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);
    border-radius:6px;color:#e2e8f0;font-size:12px;padding:5px 8px;outline:none;transition:all .15s;width:100%;
}
.prop-input:focus{border-color:#6366f1;background:rgba(99,102,241,.08);box-shadow:0 0 0 2px rgba(99,102,241,.15)}
.prop-sm{width:52px!important;flex:none}
.prop-select{
    flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);
    border-radius:6px;color:#e2e8f0;font-size:12px;padding:5px 8px;outline:none;cursor:pointer;
}
.prop-color{width:30px;height:26px;border-radius:6px;border:1px solid rgba(255,255,255,.1);cursor:pointer;padding:2px;background:rgba(255,255,255,.05)}
.prop-range{flex:1;accent-color:#6366f1}
.prop-btn{
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:6px;
    color:#64748b;font-size:11.5px;padding:5px 8px;cursor:pointer;transition:all .15s;
    display:flex;align-items:center;justify-content:center;gap:4px;flex:1;
}
.prop-btn:hover{background:rgba(255,255,255,.09);color:#e2e8f0;border-color:rgba(255,255,255,.18)}
.prop-btn.on{background:rgba(99,102,241,.2);border-color:rgba(99,102,241,.5);color:#818cf8}
.prop-btn.danger{color:#ef4444}.prop-btn.danger:hover{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3)}
.align-row{display:flex;gap:3px;flex:1}
.align-btn{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:6px;color:#475569;padding:5px;cursor:pointer;font-size:10px;text-align:center;transition:all .15s}
.align-btn:hover{border-color:rgba(99,102,241,.4);color:#818cf8;background:rgba(99,102,241,.1)}
.align-btn.on{background:rgba(99,102,241,.2);border-color:rgba(99,102,241,.5);color:#818cf8}
.no-sel{text-align:center;padding:36px 14px;color:#334155}
.no-sel i{font-size:28px;margin-bottom:10px;display:block;opacity:.4}
.no-sel p{font-size:11.5px;line-height:1.7;color:#334155}
.ai-btn{
    width:100%;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);
    border-radius:7px;color:#818cf8;font-size:11.5px;font-weight:700;
    padding:8px 12px;cursor:pointer;display:flex;align-items:center;gap:6px;
    justify-content:center;transition:all .15s;margin-top:4px;
}
.ai-btn:hover{background:rgba(99,102,241,.18);color:#a5b4fc;border-color:rgba(99,102,241,.5)}

/* Canvas Settings */
#canvas-settings{padding:10px 12px;border-top:1px solid rgba(255,255,255,.06)}
.cs-row{display:flex;gap:7px;margin-bottom:7px;align-items:center}
.cs-label{font-size:10.5px;color:#475569;min-width:62px;font-weight:600}

/* ── MODALS ──────────────────────────────────────────────── */
.modal-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.82);
    display:flex;align-items:center;justify-content:center;
    z-index:1000;backdrop-filter:blur(8px);
    opacity:0;pointer-events:none;transition:opacity .2s;
}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal-box{
    background:#0f1117;border:1px solid rgba(255,255,255,.1);
    border-radius:16px;padding:28px;width:480px;max-width:95vw;max-height:90vh;overflow-y:auto;
    transform:translateY(16px) scale(.97);transition:transform .22s,opacity .22s;
    box-shadow:0 40px 100px rgba(0,0,0,.7),0 0 0 1px rgba(99,102,241,.15);
}
.modal-overlay.open .modal-box{transform:translateY(0) scale(1)}
.modal-title{
    font-size:15px;font-weight:800;margin-bottom:18px;color:#e2e8f0;
    display:flex;align-items:center;gap:8px;
}
.modal-title-icon{
    width:28px;height:28px;background:linear-gradient(135deg,#6366f1,#8b5cf6);
    border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;color:#fff;
}
.modal-row{margin-bottom:13px}
.modal-label{font-size:11.5px;font-weight:700;color:#64748b;margin-bottom:5px;display:block;letter-spacing:.3px}
.modal-input{
    width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
    border-radius:8px;color:#e2e8f0;font-size:13px;padding:9px 12px;outline:none;transition:all .15s;
}
.modal-input:focus{border-color:#6366f1;background:rgba(99,102,241,.08);box-shadow:0 0 0 3px rgba(99,102,241,.15)}
textarea.modal-input{resize:vertical;min-height:88px}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:20px}
.modal-btn{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;transition:all .15s}
.modal-btn-cancel{background:rgba(255,255,255,.06);color:#64748b;border:1px solid rgba(255,255,255,.09)}.modal-btn-cancel:hover{color:#e2e8f0}
.modal-btn-primary{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 2px 12px rgba(99,102,241,.35)}.modal-btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 18px rgba(99,102,241,.45)}
.modal-btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 2px 10px rgba(16,185,129,.3)}.modal-btn-success:hover{transform:translateY(-1px)}

/* ── NOTIF ───────────────────────────────────────────────── */
#notif{
    position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(8px);
    background:#0f1117;color:#e2e8f0;padding:10px 22px;border-radius:100px;
    font-size:12.5px;font-weight:600;z-index:2000;pointer-events:none;opacity:0;
    transition:opacity .25s,transform .25s;
    border:1px solid rgba(255,255,255,.1);
    display:flex;align-items:center;gap:8px;
    box-shadow:0 12px 40px rgba(0,0,0,.6);backdrop-filter:blur(10px);
}
#notif.show{opacity:1;transform:translateX(-50%) translateY(0)}
#notif.success{border-color:rgba(16,185,129,.4);color:#6ee7b7}
#notif.error{border-color:rgba(239,68,68,.4);color:#fca5a5}

::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:rgba(99,102,241,.3);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:rgba(99,102,241,.5)}
</style>
</head>
<body>

<!-- TOP BAR -->
<div id="topbar">
    <a href="{{ route('banners.index') }}" class="tb-back"><i class="fas fa-arrow-left"></i> Studio</a>
    <div class="tb-divider"></div>
    <input id="title-input" type="text" value="{{ $banner->title }}" maxlength="120" autocomplete="off">
    <span id="format-badge">{{ strtoupper(str_replace('_',' ',$banner->format)) }} · {{ $banner->width }}×{{ $banner->height }}px</span>
    <div class="tb-spacer"></div>
    <span id="save-status"><i class="fas fa-circle-check" style="font-size:9px"></i> Salvo</span>
    <div class="tb-divider"></div>
    <div class="undo-redo">
        <button id="btn-undo" onclick="doUndo()" title="Desfazer (Ctrl+Z)"><i class="fas fa-rotate-left"></i></button>
        <button id="btn-redo" onclick="doRedo()" title="Refazer (Ctrl+Y)"><i class="fas fa-rotate-right"></i></button>
    </div>
    <div class="zoom-controls">
        <button onclick="zoomOut()" title="Diminuir"><i class="fas fa-minus"></i></button>
        <span id="zoom-label">100%</span>
        <button onclick="zoomIn()" title="Aumentar"><i class="fas fa-plus"></i></button>
        <button onclick="zoomFit()" title="Ajustar"><i class="fas fa-expand"></i></button>
    </div>
    <div class="tb-divider"></div>
    <button class="tb-btn tb-btn-ai" onclick="openAiModal()"><i class="fas fa-wand-magic-sparkles"></i> IA</button>
    <button class="tb-btn tb-btn-outline" onclick="openScheduleModal()"><i class="fas fa-calendar-plus"></i> Agendar</button>
    <button class="tb-btn tb-btn-primary" onclick="exportPng()"><i class="fas fa-download"></i> PNG</button>
    <button class="tb-btn tb-btn-success" onclick="saveFabric(true)"><i class="fas fa-floppy-disk"></i> Salvar</button>
</div>

<!-- EDITOR -->
<div id="editor-layout">

    <!-- LEFT PANEL -->
    <div id="left-panel">
        <div class="lp-tabs">
            <div class="lp-tab active" data-tab="templates" onclick="switchTab(this)">Templates</div>
            <div class="lp-tab" data-tab="icones" onclick="switchTab(this)">Ícones</div>
            <div class="lp-tab" data-tab="elementos" onclick="switchTab(this)">Formas</div>
            <div class="lp-tab" data-tab="imagens" onclick="switchTab(this)">Imagens</div>
            <div class="lp-tab" data-tab="camadas" onclick="switchTab(this)">Camadas</div>
        </div>

        <!-- TEMPLATES -->
        <div id=”tab-templates” class=”lp-content active”>
            <div style=”position:relative;padding:8px 0 6px”>
                <i class=”fas fa-magnifying-glass” style=”position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#334155;font-size:11px;pointer-events:none”></i>
                <input type=”text” id=”tpl-search” placeholder=”Buscar template...” oninput=”filterTemplates(this.value)”
                    style=”width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:8px;color:#e2e8f0;font-size:11px;padding:6px 10px 6px 30px;outline:none;transition:border-color .15s”
                    onfocus=”this.style.borderColor=’#6366f1’” onblur=”this.style.borderColor=’rgba(255,255,255,.09)’”>
            </div>
            <div class=”sec-title”>🛒 Black Friday</div>
            <div class=”tpl-grid” id=”tpl-bf”></div>
            <div class=”sec-title”>📱 Marketing & Agência</div>
            <div class=”tpl-grid” id=”tpl-agency”></div>
            <div class=”sec-title”>📷 Com Foto</div>
            <div class=”tpl-grid” id=”tpl-photo”></div>
            <div class=”sec-title”>🎨 Design Gráfico</div>
            <div class=”tpl-grid” id=”tpl-graphic”></div>
            <div class=”sec-title”>✈️ Viagem & Eventos</div>
            <div class=”tpl-grid” id=”tpl-travel”></div>
            <div class=”sec-title”>🏢 Corporativo</div>
            <div class=”tpl-grid” id=”tpl-corp”></div>
            <div class=”sec-title”>💚 Terceiro Setor (ONGs)</div>
            <div class=”tpl-grid” id=”tpl-ngo”></div>
        </div>

        <!-- ÍCONES -->
        <div id=”tab-icones” class=”lp-content”>
            <div style=”position:relative;padding:8px 0 6px”>
                <i class=”fas fa-magnifying-glass” style=”position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#334155;font-size:11px;pointer-events:none”></i>
                <input type=”text” id=”ico-search” placeholder=”Buscar ícone...” oninput=”filterIcons(this.value)”
                    style=”width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:8px;color:#e2e8f0;font-size:11px;padding:6px 10px 6px 30px;outline:none;transition:border-color .15s”
                    onfocus=”this.style.borderColor=’#6366f1’” onblur=”this.style.borderColor=’rgba(255,255,255,.09)’”>
            </div>
            <div class="sec-title">Negócios</div>
            <div class="ico-grid" id="ico-negocios"></div>
            <div class="sec-title" style="margin-top:14px">Social & Comunicação</div>
            <div class="ico-grid" id="ico-social"></div>
            <div class="sec-title" style="margin-top:14px">E-commerce & Vendas</div>
            <div class="ico-grid" id="ico-ecomm"></div>
            <div class="sec-title" style="margin-top:14px">Símbolos & Decorativos</div>
            <div class="ico-grid" id="ico-deco"></div>
        </div>

        <!-- ELEMENTOS -->
        <div id="tab-elementos" class="lp-content">
            <div class="sec-title">Texto</div>
            <div class="elem-grid">
                <button class="elem-btn" onclick="addText('heading')"><i class="fas fa-heading"></i>TÃ­tulo</button>
                <button class="elem-btn" onclick="addText('sub')"><i class="fas fa-paragraph"></i>SubtÃ­tulo</button>
                <button class="elem-btn" onclick="addText('body')"><i class="fas fa-font"></i>ParÃ¡grafo</button>
                <button class="elem-btn" onclick="addText('badge')"><i class="fas fa-tag"></i>Etiqueta</button>
            </div>
            <div class="sec-title" style="margin-top:18px">Formas</div>
            <div class="elem-grid">
                <button class="elem-btn" onclick="addShape('rect')"><i class="fas fa-square"></i>RetÃ¢ngulo</button>
                <button class="elem-btn" onclick="addShape('circle')"><i class="fas fa-circle"></i>CÃ­rculo</button>
                <button class="elem-btn" onclick="addShape('line')"><i class="fas fa-minus"></i>Linha</button>
                <button class="elem-btn" onclick="addShape('triangle')"><i class="fas fa-play" style="transform:rotate(-90deg)"></i>TriÃ¢ngulo</button>
                <button class="elem-btn" onclick="addShape('star')"><i class="fas fa-star"></i>Estrela</button>
                <button class="elem-btn" onclick="addShape('badge_pill')"><i class="fas fa-certificate"></i>Badge</button>
            </div>
            <div class="sec-title" style="margin-top:18px">Linhas Decorativas</div>
            <div class="elem-grid">
                <button class="elem-btn" onclick="addDeco('hr_thick')"><i class="fas fa-minus" style="font-size:14px;font-weight:900"></i>Linha Grossa</button>
                <button class="elem-btn" onclick="addDeco('divider_dots')"><i class="fas fa-ellipsis"></i>Pontilhado</button>
                <button class="elem-btn" onclick="addDeco('accent_bar')"><i class="fas fa-grip-lines-vertical"></i>Acento Vert.</button>
                <button class="elem-btn" onclick="addDeco('corner_tag')"><i class="fas fa-bookmark"></i>Corner Tag</button>
            </div>
            <div class="sec-title" style="margin-top:18px">Fundo</div>
            <div class="elem-grid">
                <button class="elem-btn" onclick="setBgSolid()"><i class="fas fa-fill-drip"></i>Cor SÃ³lida</button>
                <button class="elem-btn" onclick="setBgGradient()"><i class="fas fa-layer-group"></i>Gradiente</button>
                <button class="elem-btn" onclick="setBgGradient('sunset')"><i class="fas fa-sun"></i>Sunset</button>
                <button class="elem-btn" onclick="setBgGradient('ocean')"><i class="fas fa-water"></i>Ocean</button>
                <button class="elem-btn" onclick="setBgGradient('forest')"><i class="fas fa-leaf"></i>Forest</button>
                <button class="elem-btn" onclick="setBgGradient('dark')"><i class="fas fa-moon"></i>Dark</button>
            </div>
        </div>

        <!-- IMAGENS -->
        <div id="tab-imagens" class="lp-content">
            <div class="sub-tabs">
                <div class="sub-tab active" onclick="switchImgTab(this,'banco')">Banco Premium</div>
                <div class="sub-tab" onclick="switchImgTab(this,'upload')">Meu Upload</div>
            </div>
            <div id="img-banco">
                <div class="sec-title">Imagens Profissionais</div>
                <div class="img-gal" id="stock-gal"></div>
            </div>
            <div id="img-upload" style="display:none">
                <div class="upload-area" onclick="document.getElementById('img-file').click()">
                    <i class="fas fa-cloud-arrow-up"></i>
                    <p>Clique para enviar<br><span style="font-size:10px;color:#475569">JPG, PNG, WEBP atÃ© 10MB</span></p>
                </div>
                <input type="file" id="img-file" accept="image/*" style="display:none" onchange="handleUpload(this)">
                <div class="sec-title">Enviadas</div>
                <div class="img-gal" id="upload-gal"></div>
            </div>
        </div>

        <!-- CAMADAS -->
        <div id="tab-camadas" class="lp-content">
            <div class="sec-title" style="display:flex;justify-content:space-between">
                Camadas
                <span style="cursor:pointer;color:#6366f1" onclick="refreshLayers()"><i class="fas fa-sync fa-xs"></i></span>
            </div>
            <div id="layers-list"></div>
        </div>
    </div>

    <!-- CANVAS AREA -->
    <div id="canvas-area">
        <div id="canvas-wrap">
            <canvas id="fabric-canvas"></canvas>
            <canvas id="snap-canvas"></canvas>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div id="right-panel">
        <div id="rp-header">
            <div id="rp-header-icon"><i class="fas fa-sliders"></i></div>
            <span id="rp-header-text">Propriedades</span>
        </div>
        <div id="props-content">
            <div class="no-sel">
                <i class="fas fa-hand-pointer"></i>
                <p>Clique em qualquer elemento do canvas para editar suas propriedades.</p>
            </div>
        </div>
        <div id="canvas-settings">
            <div class="prop-group-title" style="margin-bottom:8px">Canvas</div>
            <div class="cs-row">
                <span class="cs-label">Cor de fundo</span>
                <input type="color" class="prop-color" id="bg-color-input" value="#ffffff" onchange="updateBg(this.value)">
                <span style="font-size:11px;color:#475569;margin-left:auto">{{ $banner->width }}Ã—{{ $banner->height }}</span>
            </div>
            <div class="cs-row">
                <span class="cs-label">Fonte matriz</span>
                <select class="prop-select" id="global-font" onchange="updateGlobalFont(this.value)" style="font-size:11px">
                    <option>Inter</option><option>Poppins</option><option>Montserrat</option>
                    <option>Playfair Display</option><option>Oswald</option><option>Raleway</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="ai-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-wand-magic-sparkles" style="color:#818cf8;margin-right:8px"></i>Gerador de Copy com IA</div>
        <div class="modal-row">
            <label class="modal-label">Descreva o que a IA deve escrever</label>
            <textarea id="ai-prompt" class="modal-input" rows="3" placeholder="Ex: TÃ­tulo impactante para campanha de doaÃ§Ã£o de agasalhos..."></textarea>
        </div>
        <div class="modal-row">
            <label class="modal-label">Tipo de texto</label>
            <select id="ai-field" class="modal-input">
                <option value="title">TÃ­tulo (Curto)</option>
                <option value="subtitle">SubtÃ­tulo (MÃ©dio)</option>
                <option value="message">Corpo do texto (Longo)</option>
                <option value="button">Call to Action</option>
            </select>
        </div>
        <div id="ai-result" style="display:none;background:#1a1d26;border:1px solid #2d3748;border-radius:8px;padding:14px;margin-top:12px">
            <div style="font-size:11px;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.8px">IA gerou:</div>
            <div id="ai-result-text" style="font-size:14px;color:#e2e8f0;line-height:1.6;font-weight:500"></div>
            <button onclick="applyAiText()" style="margin-top:12px;width:100%" class="tb-btn tb-btn-primary"><i class="fas fa-check"></i> Aplicar no Canvas</button>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeModal('ai-modal')">Cancelar</button>
            <button class="modal-btn modal-btn-primary" id="ai-run-btn" onclick="runAi()"><i class="fas fa-wand-magic-sparkles"></i> Gerar</button>
        </div>
    </div>
</div>

<div id="schedule-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-calendar-plus" style="color:#34d399;margin-right:8px"></i>Agendar PublicaÃ§Ã£o</div>
        @if($socialAccounts->isEmpty())
        <div style="text-align:center;padding:24px;color:#64748b">
            <i class="fas fa-link-slash" style="font-size:32px;margin-bottom:12px;display:block"></i>
            <p>Nenhuma conta social conectada.</p>
            <a href="{{ route('social.accounts') }}" style="color:#6366f1;margin-top:10px;display:inline-block">Conectar conta</a>
        </div>
        @else
        <div class="modal-row">
            <label class="modal-label">Conta</label>
            <select id="sched-account" class="modal-input">
                @foreach($socialAccounts as $acc)
                <option value="{{ $acc->id }}" data-platform="{{ $acc->platform }}">{{ $acc->page_name }} ({{ ucfirst($acc->platform) }})</option>
                @endforeach
            </select>
        </div>
        <div class="modal-row">
            <label class="modal-label">Legenda</label>
            <textarea id="sched-caption" class="modal-input" rows="4" placeholder="Texto do post..."></textarea>
        </div>
        <div class="modal-row">
            <label class="modal-label">Data e hora</label>
            <input type="datetime-local" id="sched-datetime" class="modal-input">
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="closeModal('schedule-modal')">Cancelar</button>
            <button class="modal-btn modal-btn-success" onclick="submitSchedule()"><i class="fas fa-paper-plane"></i> Agendar</button>
        </div>
        @endif
    </div>
</div>

<!-- Template Confirm Modal -->
<div id="tpl-confirm-modal" class="modal-overlay">
    <div class="modal-box" style="width:380px">
        <div class="modal-title" style="font-size:15px"><i class="fas fa-paint-brush" style="color:#818cf8;margin-right:8px"></i>Aplicar Template</div>
        <p style="color:#94a3b8;font-size:13px;line-height:1.6">Deseja aplicar o template <strong id="tpl-confirm-name" style="color:#e2e8f0"></strong>? O canvas atual será substituído.</p>
        <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('tpl-confirm-modal')">Cancelar</button>
                <button class="modal-btn modal-btn-primary" onclick="applyPendingTpl()"><i class="fas fa-check"></i> Aplicar Template</button>
            </div>
        </div>
    </div>
</div>

<div id="notif"></div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// CONSTANTS
// ─────────────────────────────────────────────────────────────────────────────
const BANNER_ID  = {{ $banner->id }};
const BANNER_W   = {{ $banner->width }};
const BANNER_H   = {{ $banner->height }};
const SAVE_URL   = '{{ route('banners.save-fabric', $banner) }}';
const UPLOAD_URL = '{{ route('banners.upload-image', $banner) }}';
const SCHED_URL  = '{{ route('banners.schedule-from-canvas', $banner) }}';
const AI_URL     = '{{ route('banners.generate-ai-text', $banner) }}';
const CSRF       = document.querySelector('meta[name=csrf-token]').content;

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// FABRIC INIT
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const canvas = new fabric.Canvas('fabric-canvas', {
    width: BANNER_W,
    height: BANNER_H,
    backgroundColor: '#ffffff',
    preserveObjectStacking: true,
    selection: true,
    renderOnAddRemove: true,
    stateful: false,
});

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// SNAPPING â€” uses a separate overlay canvas (not fabric contextTop)
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const snapEl = document.getElementById('snap-canvas');
const snapCtx = snapEl.getContext('2d');
let snapLines = { v: [], h: [] };
const SNAP_DIST = 6;
const SNAP_COLOR = '#f0abfc';

function syncSnapSize() {
    snapEl.width  = BANNER_W;
    snapEl.height = BANNER_H;
    snapEl.style.width  = BANNER_W + 'px';
    snapEl.style.height = BANNER_H + 'px';
}
syncSnapSize();

function clearSnap() {
    snapCtx.clearRect(0, 0, snapEl.width, snapEl.height);
    snapLines = { v: [], h: [] };
}

function drawSnapLines() {
    snapCtx.clearRect(0, 0, snapEl.width, snapEl.height);
    snapCtx.save();
    snapCtx.strokeStyle = SNAP_COLOR;
    snapCtx.lineWidth = 1;
    snapCtx.setLineDash([4, 4]);
    snapLines.v.forEach(x => { snapCtx.beginPath(); snapCtx.moveTo(x, 0); snapCtx.lineTo(x, snapEl.height); snapCtx.stroke(); });
    snapLines.h.forEach(y => { snapCtx.beginPath(); snapCtx.moveTo(0, y); snapCtx.lineTo(snapEl.width, y); snapCtx.stroke(); });
    snapCtx.restore();
}

canvas.on('object:moving', function(e) {
    const obj = e.target;
    if (!obj) return;
    snapLines = { v: [], h: [] };
    const oc = obj.getCenterPoint();
    const cw = BANNER_W, ch = BANNER_H;

    // Snap to canvas center
    if (Math.abs(oc.x - cw/2) < SNAP_DIST) { obj.set({ left: cw/2 - obj.getScaledWidth()/2 }); snapLines.v.push(cw/2); }
    if (Math.abs(oc.y - ch/2) < SNAP_DIST) { obj.set({ top: ch/2 - obj.getScaledHeight()/2 }); snapLines.h.push(ch/2); }

    // Snap to other objects
    canvas.getObjects().forEach(o => {
        if (o === obj) return;
        const oc2 = o.getCenterPoint();
        if (Math.abs(oc.x - oc2.x) < SNAP_DIST) { obj.set({ left: oc2.x - obj.getScaledWidth()/2 }); snapLines.v.push(oc2.x); }
        if (Math.abs(oc.y - oc2.y) < SNAP_DIST) { obj.set({ top: oc2.y - obj.getScaledHeight()/2 }); snapLines.h.push(oc2.y); }
    });

    // Snap to edges
    const ol = obj.left, ot = obj.top;
    const ow = obj.getScaledWidth(), oh = obj.getScaledHeight();
    if (Math.abs(ol) < SNAP_DIST)          { obj.set({ left: 0 }); snapLines.v.push(0); }
    if (Math.abs(ol + ow - cw) < SNAP_DIST){ obj.set({ left: cw - ow }); snapLines.v.push(cw); }
    if (Math.abs(ot) < SNAP_DIST)          { obj.set({ top: 0 }); snapLines.h.push(0); }
    if (Math.abs(ot + oh - ch) < SNAP_DIST){ obj.set({ top: ch - oh }); snapLines.h.push(ch); }

    obj.setCoords();
    drawSnapLines();
});

canvas.on('mouse:up', clearSnap);

// ─────────────────────────────────────────────────────────────────────────────
// HISTORY
// ─────────────────────────────────────────────────────────────────────────────
let hist = [], hIdx = -1, ignHist = false;

function pushHistory() {
    if (ignHist) return;
    const j = JSON.stringify(canvas.toJSON(['id','name']));
    hist = hist.slice(0, hIdx + 1);
    hist.push(j);
    if (hist.length > 50) hist.shift();
    hIdx = hist.length - 1;
    setSaveStatus('pending');
    refreshLayers();
    syncUndoRedo();
}

function restoreHistory(idx) {
    ignHist = true;
    canvas.loadFromJSON(hist[idx], () => {
        canvas.renderAll();
        ignHist = false;
        refreshLayers();
        syncUndoRedo();
        showNoSel();
    });
}

function doUndo() { if (hIdx > 0) { hIdx--; restoreHistory(hIdx); } }
function doRedo() { if (hIdx < hist.length - 1) { hIdx++; restoreHistory(hIdx); } }
function syncUndoRedo() {
    document.getElementById('btn-undo').disabled = hIdx <= 0;
    document.getElementById('btn-redo').disabled = hIdx >= hist.length - 1;
}

canvas.on('object:added',    pushHistory);
canvas.on('object:removed',  pushHistory);
canvas.on('object:modified', pushHistory);

// ─────────────────────────────────────────────────────────────────────────────
// ZOOM
// ─────────────────────────────────────────────────────────────────────────────
let zoomLvl = 1;

function applyZoom(z) {
    zoomLvl = Math.max(0.1, Math.min(4, z));
    const wrap = document.getElementById('canvas-wrap');
    if (wrap) wrap.style.transform = `scale(${zoomLvl})`;
    const label = document.getElementById('zoom-label');
    if (label) label.textContent = Math.round(zoomLvl * 100) + '%';
}

function zoomIn()  { applyZoom(zoomLvl + 0.1); }
function zoomOut() { applyZoom(zoomLvl - 0.1); }

function zoomFit() {
    const a = document.getElementById('canvas-area');
    if (!a) return;
    const z = Math.min((a.clientWidth - 80) / BANNER_W, (a.clientHeight - 80) / BANNER_H);
    applyZoom(Math.min(z, 1.5));
}
window.addEventListener('resize', zoomFit);

// ─────────────────────────────────────────────────────────────────────────────
// TABS
// ─────────────────────────────────────────────────────────────────────────────
function switchTab(el) {
    if (!el) return;
    document.querySelectorAll('.lp-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.lp-content').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    const tab = el.dataset.tab;
    const content = document.getElementById('tab-' + tab);
    if (content) content.classList.add('active');
    if (tab === 'camadas') refreshLayers();
}

function switchImgTab(el, tab) {
    document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
    const banco = document.getElementById('img-banco');
    const upload = document.getElementById('img-upload');
    if (banco) banco.style.display = tab === 'banco' ? 'block' : 'none';
    if (upload) upload.style.display = tab === 'upload' ? 'block' : 'none';
}

// ─────────────────────────────────────────────────────────────────────────────
// TEMPLATES
// ─────────────────────────────────────────────────────────────────────────────
const W = BANNER_W, H = BANNER_H;


const TEMPLATES = {
    bf: [
        { label: 'Mega Sale Blue',     previewCss: 'linear-gradient(135deg,#1e3a8a,#0f172a)',       build: tplMegaSaleBlue },
        { label: 'BF Camera Gold',     previewCss: 'linear-gradient(135deg,#1a0a00,#d97706)',       build: tplBfCamera },
        { label: 'BF Fone Black',      previewCss: 'linear-gradient(135deg,#0a0a0a,#eab308)',       build: tplBfFone },
        { label: 'BF Watch Orange',    previewCss: 'linear-gradient(135deg,#1c1000,#f97316)',       build: tplBfWatchOrange },
        { label: 'BF Headphone Dark',  previewCss: 'linear-gradient(135deg,#111,#eab308)',          build: tplBfHeadphone },
        { label: 'BF Weekend Guitar',  previewCss: 'linear-gradient(135deg,#f8fafc,#0ea5e9)',       build: tplBfWeekend },
    ],
    agency: [
        { label: 'Business Orange',    previewCss: 'linear-gradient(135deg,#ea580c,#d97706)',       build: tplBusinessOrange },
        { label: 'Agência Square',     previewCss: 'linear-gradient(135deg,#0d1117,#00d4ff)',       build: tplAgenciaSquare },
        { label: 'Marketing Teal',     previewCss: 'linear-gradient(135deg,#162d40,#0d9488)',       build: tplGraphicAgency },
        { label: 'Creative Red',       previewCss: 'linear-gradient(135deg,#7f1d1d,#e5e7eb)',       build: tplAgencyCreativeRed },
        { label: 'Marketing Blue',     previewCss: 'linear-gradient(135deg,#1e3a8a,#f8fafc)',       build: tplAgencyBlue },
        { label: 'Marketing Expert',   previewCss: 'linear-gradient(135deg,#ea580c,#fff7ed)',       build: tplMarketingExpert },
    ],
    photo: [
        { label: 'Viagem Explore',     preview: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=280&q=70', build: tplTravelExplorer },
        { label: 'Arrecadação (Foto)', preview: 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=280&q=70', build: tplPhotoNgo },
        { label: 'Tech Dark (Foto)',   preview: 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=280&q=70',    build: tplPhotoTech },
    ],
    graphic: [
        { label: 'Neon Dark Studio',    previewCss: 'linear-gradient(135deg,#030307,#7c5cff)',      build: tplNeonDark },
        { label: 'Split Geometria',    previewCss: 'linear-gradient(90deg,#1e1b4b 50%,#4f46e5 50%)',build: tplSplitGeo },
        { label: 'Fashion Pop Premium',previewCss: 'linear-gradient(135deg,#1a0014,#ec4899)',       build: tplFashionPop },
        { label: 'Headline Dark',      previewCss: 'linear-gradient(135deg,#000,#374151)',          build: tplHeadlineDark },
        { label: 'Minimal Branco',     previewCss: 'linear-gradient(135deg,#f8fafc,#e2e8f0)',       build: tplGraphicMinimal },
        { label: 'Duotone Sunset',     previewCss: 'linear-gradient(135deg,#7f1d1d,#d97706)',       build: tplGraphicDuotone },
    ],
    travel: [
        { label: 'Startup Talkshow',   previewCss: 'linear-gradient(135deg,#0a1f1a,#d4af37)',       build: tplStartupTalkshow },
        { label: 'Travel World Gold',  previewCss: 'linear-gradient(135deg,#78716c,#d4a574)',       build: tplTravelWorld },
        { label: 'Swipe Up Story',     previewCss: 'linear-gradient(135deg,#4f46e5,#7c3aed)',       build: tplSwipeUpStory },
    ],
    corp: [
        { label: 'KPI Report Dark',    previewCss: 'linear-gradient(135deg,#0f172a,#1e293b)',       build: tplKpiReport },
        { label: 'Lançamento SaaS',    previewCss: 'linear-gradient(135deg,#1e1b4b,#312e81)',       build: tplLancamentoSaas },
        { label: 'Webinar Executivo',  previewCss: 'linear-gradient(135deg,#0c1428,#0ea5e9)',       build: tplWebinarCard },
        { label: 'Digital Mktg Dark',  previewCss: 'linear-gradient(135deg,#0d1d2b,#1a3a52)',       build: tplDigitalMktg },
    ],
    ngo: [
        { label: 'Solidariedade Neon', previewCss: 'linear-gradient(135deg,#030312,#6366f1)',       build: tplNgoNeon },
        { label: 'Impacto Social',     previewCss: 'linear-gradient(135deg,#052e16,#10b981)',       build: tplSocialImpact },
        { label: 'Campanha Doação',    previewCss: 'linear-gradient(180deg,#ffffff,#ef4444)',       build: tplCampanhaDoacao },
        { label: 'Voluntariado Pop',   previewCss: 'linear-gradient(135deg,#1a0a2e,#a855f7)',       build: tplVoluntariado },
    ],
};

let _pendingTplBuild = null;
let _allTplItems = [];

function renderTemplateGrids() {
    _allTplItems = [];
    Object.entries(TEMPLATES).forEach(([group, list]) => {
        const container = document.getElementById('tpl-' + group);
        if (!container) return;
        container.innerHTML = '';
        list.forEach(tpl => {
            const d = document.createElement('div');
            d.className = 'tpl-card';
            d.dataset.label = tpl.label.toLowerCase();
            if (tpl.preview) d.style.backgroundImage = `url(${tpl.preview})`;
            else d.style.background = tpl.previewCss || '#1a1d26';
            d.innerHTML = `
                <div class="tpl-label">${tpl.label}</div>
                <div class="tpl-card-apply"><button class="tpl-apply-btn"><i class="fas fa-wand-magic-sparkles"></i> Aplicar</button></div>
            `;
            d.onclick = () => {
                _pendingTplBuild = tpl.build;
                document.getElementById('tpl-confirm-name').textContent = tpl.label;
                openModal('tpl-confirm-modal');
            };
            container.appendChild(d);
            _allTplItems.push({ el: d, group, label: tpl.label.toLowerCase() });
        });
    });
}

function filterTemplates(q) {
    q = q.toLowerCase();
    _allTplItems.forEach(item => {
        item.el.style.display = (!q || item.label.includes(q)) ? '' : 'none';
    });
    // Hide/show section titles
    document.querySelectorAll('#tab-templates .sec-title').forEach(t => t.style.display = q ? 'none' : '');
}
function applyPendingTpl() {
    closeModal('tpl-confirm-modal');
    if (_pendingTplBuild) { _pendingTplBuild(); _pendingTplBuild = null; }
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// ICONS
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const ICONS = {
    negocios: [
        { label:'Sucesso', svg:'<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>' },
        { label:'Dinheiro', svg:'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M6 9h.01M18 15h.01"/>' },
        { label:'Raio/Power', svg:'<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>' },
        { label:'CalendÃ¡rio', svg:'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="9 16 11 18 15 14"/>' },
        { label:'FÃ¡brica', svg:'<path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v16z"/>' },
        { label:'SemÃ¡foro', svg:'<rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="13" r="2"/><circle cx="12" cy="19" r="2"/><line x1="5" y1="5" x2="2" y2="5"/><line x1="5" y1="11" x2="2" y2="11"/><line x1="19" y1="5" x2="22" y2="5"/><line x1="19" y1="11" x2="22" y2="11"/>' },
        { label:'Escala', svg:'<line x1="12" y1="3" x2="12" y2="21"/><polyline points="17 8 12 3 7 8"/><line x1="3" y1="21" x2="21" y2="21"/>' },
        { label:'RÃ©gua', svg:'<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/><rect x="2" y="4" width="4" height="16" rx="1"/>' },
        { label:'GrÃ¡fico Up', svg:'<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>' },
        { label:'TrofÃ©u', svg:'<polyline points="6 9 2 9 2 2 22 2 22 9 18 9"/><path d="M6 2v6c0 3.3 2.7 6 6 6s6-2.7 6-6V2"/><path d="M12 18v4"/><line x1="8" y1="22" x2="16" y2="22"/>' },
        { label:'Martelo', svg:'<path d="m15 12-8.5 8.5c-.83.83-2.17.83-3 0 0 0 0 0 0 0a2.12 2.12 0 0 1 0-3L12 9"/><path d="M17.64 15 22 10.64"/><path d="m20.91 11.7-1.25-1.25c-.6-.6-.93-1.4-.93-2.25v-.86L16.01 4.6a5.56 5.56 0 0 0-3.94-1.64H9l.92.82A6.18 6.18 0 0 1 12 8.4v1.56l2 2h2.47l2.26 1.91"/>' },
        { label:'Maleta', svg:'<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>' },
    ],
    social: [
        { label:'Fones', svg:'<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>' },
        { label:'Xadrez', svg:'<path d="M3 3h4l2.68 7.6L5 17h14l-4.68-6.4L17 3h4"/><line x1="12" y1="3" x2="12" y2="21"/>' },
        { label:'Msg Minus', svg:'<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/>' },
        { label:'NotificaÃ§Ã£o', svg:'<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>' },
        { label:'UsuÃ¡rios', svg:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
        { label:'Compartilhar', svg:'<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>' },
        { label:'Globo', svg:'<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>' },
        { label:'Wifi', svg:'<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>' },
    ],
    ecomm: [
        { label:'Carrinho', svg:'<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>' },
        { label:'Etiqueta', svg:'<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>' },
        { label:'Presente', svg:'<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>' },
        { label:'Desconto', svg:'<circle cx="9" cy="9" r="2"/><circle cx="15" cy="15" r="2"/><line x1="5" y1="19" x2="19" y2="5"/>' },
        { label:'Loja', svg:'<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>' },
        { label:'Entrega', svg:'<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>' },
        { label:'Empilhadeira', svg:'<path d="M4 17V5a2 2 0 0 1 2-2h12l4 4v10a2 2 0 0 1-2 2"/><rect x="2" y="13" width="8" height="8" rx="1"/>' },
        { label:'Caixa', svg:'<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>' },
    ],
    deco: [
        { label:'Estrela', svg:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>' },
        { label:'CoraÃ§Ã£o', svg:'<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>' },
        { label:'CoraÃ§Ã£o Seta', svg:'<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>' },
        { label:'Raio', svg:'<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>' },
        { label:'Flor', svg:'<circle cx="12" cy="12" r="4"/><path d="M12 2a10 10 0 0 1 10 10A10 10 0 0 1 12 22 10 10 0 0 1 2 12 10 10 0 0 1 12 2"/><path d="M12 8a4 4 0 0 1 4 4"/>' },
        { label:'Pata', svg:'<circle cx="11" cy="4" r="2"/><circle cx="18" cy="8" r="2"/><circle cx="20" cy="16" r="2"/><path d="M9 10a5 5 0 0 1 5 5v3.5a3.5 3.5 0 0 1-6.84 1.045Q6.52 17.48 4.46 16.84A3.5 3.5 0 0 1 5.5 10Z"/>' },
        { label:'PoÃ§Ã£o', svg:'<path d="M10 2v2.343a7.5 7.5 0 1 0 4 0V2"/><line x1="8.5" y1="2" x2="15.5" y2="2"/>' },
        { label:'Tarot', svg:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>' },
        { label:'Seta Up', svg:'<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>' },
        { label:'Seta Right', svg:'<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>' },
        { label:'Check', svg:'<polyline points="20 6 9 17 4 12"/>' },
        { label:'Brilho', svg:'<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>' },
    ],
};

let _allIcoItems = [];
function renderIconGrids() {
    _allIcoItems = [];
    Object.entries(ICONS).forEach(([group, list]) => {
        const container = document.getElementById('ico-' + group);
        if (!container) return;
        container.innerHTML = '';
        list.forEach(ico => {
            const btn = document.createElement('button');
            btn.className = 'ico-btn';
            btn.title = ico.label;
            btn.dataset.label = ico.label.toLowerCase();
            btn.innerHTML = `<svg viewBox="0 0 24 24">${ico.svg}</svg><span>${ico.label}</span>`;
            btn.onclick = () => addIconToCanvas(ico.svg, ico.label);
            container.appendChild(btn);
            _allIcoItems.push({ el: btn, group, label: ico.label.toLowerCase() });
        });
    });
}

function filterIcons(q) {
    q = q.toLowerCase();
    _allIcoItems.forEach(item => {
        item.el.style.display = (!q || item.label.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('#tab-icones .sec-title').forEach(t => t.style.display = q ? 'none' : '');
}

function addIconToCanvas(svgContent, label) {
    // Build a complete SVG string with white stroke style
    const svgStr = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="120" height="120" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">${svgContent}</svg>`;
    const url = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgStr)));
    fabric.Image.fromURL(url, img => {
        if (!img) return;
        img.set({ left: W/2 - 60, top: H/2 - 60, scaleX: 1, scaleY: 1, selectable: true, name: 'icon_' + label.toLowerCase().replace(/\s/g, '_') });
        canvas.add(img);
        canvas.setActiveObject(img);
        canvas.requestRenderAll();
        showNotif('Ãcone adicionado!', 'success');
    });
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Helpers
function gf() { return document.getElementById('global-font').value || 'Inter'; }
function mkTxt(text, opts = {}) {
    return new fabric.Textbox(text, {
        fontFamily:   opts.font || gf(),
        fontSize:     opts.sz   || 40,
        fill:         opts.fill || '#ffffff',
        fontWeight:   opts.fw   || 'normal',
        left:         opts.x    || 60,
        top:          opts.y    || 60,
        width:        opts.w    || W - 120,
        textAlign:    opts.align|| 'left',
        lineHeight:   opts.lh   || 1.2,
        shadow:       opts.shadow|| null,
        opacity:      opts.op   || 1,
        charSpacing:  opts.letterSpacing ? opts.letterSpacing * 100 : 0,
        angle:        opts.angle|| 0,
        selectable:   true,
        name:         opts.name || 'text',
    });
}
function mkRect(opts = {}) {
    return new fabric.Rect({
        left: opts.x || 0, top: opts.y || 0,
        width: opts.w || 100, height: opts.h || 100,
        fill: opts.fill || '#6366f1',
        rx: opts.rx || 0, ry: opts.ry || 0,
        opacity: opts.op || 1,
        shadow: opts.shadow || null,
        selectable: true, name: opts.name || 'rect',
    });
}
function mkCircle(opts = {}) {
    return new fabric.Circle({
        left: opts.x || 0, top: opts.y || 0,
        radius: opts.r || 80,
        fill: opts.fill || '#6366f1',
        opacity: opts.op || 0.15,
        selectable: true, name: opts.name || 'circle',
    });
}

function startTpl(bg) {
    ignHist = true;
    canvas.clear();
    canvas.setBackgroundColor(bg, () => { canvas.renderAll(); });
}
function finishTpl() {
    canvas.renderAll();
    ignHist = false;
    pushHistory();
}

// â”€â”€â”€ PHOTO TEMPLATES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Helper: load image without blocking â€” template always renders even if image fails
function asyncImg(url, cb) {
    fabric.Image.fromURL(url, function(img) {
        if (img && img.width > 0) { cb(img); pushHistory(); }
    }, { crossOrigin: 'anonymous' });
}

function tplPhotoNgo() {
    startTpl('#1c1917');
    // Render all content immediately (no image dependency)
    canvas.add(mkCircle({ x:W-100, y:-80, r:260, fill:'#1e3a5f', op:.6 }));
    canvas.add(mkTxt('ARRECADAÃ‡ÃƒO\nSOLIDÃRIA', { sz: Math.round(H*.13), fw:'900', y:90, lh:1.05, shadow: new fabric.Shadow({color:'rgba(0,0,0,.6)',blur:20}) }));
    canvas.add(mkTxt('Cada gesto vale uma vida. Doe e transforme.', { sz: Math.round(H*.036), y: Math.round(H*.65), lh: 1.6, fill:'#e2e8f0' }));
    canvas.add(mkRect({ x:60, y: Math.round(H*.8), w:190, h:48, fill:'#e11d48', rx:8, shadow: new fabric.Shadow({color:'rgba(225,29,72,.4)',blur:14,offsetY:6}) }));
    canvas.add(mkTxt('Contribuir Agora', { sz:14, fw:'700', x:60, y: Math.round(H*.8)+14, w:190, fill:'#fff', align:'center' }));
    finishTpl();
    // Enhance with photo async â€” if fails, template still looks good
    asyncImg('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=1080&q=80', img => {
        img.set({ scaleX: W/img.width, scaleY: H/img.height, left:0, top:0, opacity:0.35, name:'bg-img' });
        img.filters.push(new fabric.Image.filters.Brightness({ brightness: -0.2 })); img.applyFilters();
        canvas.add(img); canvas.sendToBack(img); canvas.requestRenderAll();
    });
}
function tplPhotoTech() {
    startTpl('#0f172a');
    // Gradient overlay as placeholder for right side
    const grad = new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W,y2:0},
        colorStops:[{offset:0,color:'#0f172a'},{offset:.55,color:'rgba(15,23,42,.9)'},{offset:1,color:'rgba(15,23,42,0)'}]});
    canvas.add(mkRect({ x:W*.35, y:0, w:W*.65, h:H, fill:'#1e293b', name:'right-bg' }));
    canvas.add(mkRect({ x:0, y:0, w:W, h:H, fill:grad, name:'overlay' }));
    canvas.add(mkTxt('NOVO PRODUTO', { sz:12, fw:'800', y:75, fill:'#38bdf8', name:'tag' }));
    canvas.add(mkTxt('Tecnologia\nque Escala.', { sz: Math.round(H*.13), fw:'900', y:110, lh:1.05, w: W*.5, shadow: new fabric.Shadow({color:'rgba(0,0,0,.5)',blur:16}) }));
    canvas.add(mkTxt('AutomaÃ§Ã£o inteligente para times de alta performance.', { sz: Math.round(H*.033), y: Math.round(H*.6), lh:1.6, w: W*.48, fill:'#cbd5e1' }));
    canvas.add(mkRect({ x:60, y: Math.round(H*.78), w:210, h:48, fill:'#6366f1', rx:24, shadow: new fabric.Shadow({color:'rgba(99,102,241,.5)',blur:16,offsetY:6}) }));
    canvas.add(mkTxt('Ver DemonstraÃ§Ã£o', { sz:14, fw:'700', x:60, y: Math.round(H*.78)+14, w:210, fill:'#fff', align:'center' }));
    finishTpl();
    asyncImg('https://images.unsplash.com/photo-1551434678-e076c223a692?w=1080&q=80', img => {
        img.set({ scaleX: W*.65/img.width, scaleY: H/img.height, left: W*.35, top:0, opacity:0.85, name:'img' });
        canvas.add(img); canvas.sendToBack(img); canvas.sendToBack(img); canvas.requestRenderAll();
    });
}

// â”€â”€â”€ GRAPHICAL TEMPLATES (sem fotos) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function tplGraphicNeonNgo() {
    startTpl('#1e1b4b');
    // BG shapes
    canvas.add(mkCircle({ x: W-180, y:-120, r:280, fill:'#818cf8', op:.1, name:'c1' }));
    canvas.add(mkCircle({ x:-80, y: H*.6, r:200, fill:'#6366f1', op:.07, name:'c2' }));
    // Accent line
    canvas.add(mkRect({ x:60, y:70, w:5, h:60, fill:'#818cf8', rx:3, name:'accLine' }));
    // Badge strip
    canvas.add(mkRect({ x:72, y:72, w:200, h:34, fill:'rgba(129,140,248,.15)', rx:17, name:'badge-bg' }));
    canvas.add(mkTxt('CAMPANHA DE IMPACTO', { sz:11, fw:'800', x:72, y:80, w:200, fill:'#a5b4fc', align:'center', name:'badge' }));
    // Main title
    canvas.add(mkTxt('Juntos\nSomos\nMais.', { sz: Math.round(H*.135), fw:'900', y:140, lh:1.05, shadow: new fabric.Shadow({color:'rgba(99,102,241,.3)',blur:20}) }));
    // Subtitle
    canvas.add(mkTxt('Cada contribuiÃ§Ã£o molda o futuro que queremos para todos.', { sz: Math.round(H*.034), y: Math.round(H*.66), lh:1.6, fill:'rgba(255,255,255,.85)', name:'sub' }));
    // CTA
    canvas.add(mkRect({ x:60, y: Math.round(H*.8), w:185, h:48, fill:'#6366f1', rx:24, shadow: new fabric.Shadow({color:'rgba(99,102,241,.5)',blur:16,offsetY:6}), name:'btn-bg' }));
    canvas.add(mkTxt('Quero Ajudar', { sz:15, fw:'700', x:60, y: Math.round(H*.8)+14, w:185, fill:'#fff', align:'center', name:'btn-txt' }));
    finishTpl();
}

function tplGraphicImpact() {
    startTpl('#064e3b');
    canvas.add(mkCircle({ x: W*.75, y:-60, r:240, fill:'#10b981', op:.15, name:'c1' }));
    canvas.add(mkCircle({ x:-60, y: H*.7, r:180, fill:'#34d399', op:.08, name:'c2' }));
    // Stats pills
    const stats = [{n:'5.200', l:'FamÃ­lias'},{n:'38', l:'Cidades'},{n:'R$1M', l:'Arrecadado'}];
    stats.forEach((s, i) => {
        const bx = 60 + i * Math.round((W-120)/3);
        canvas.add(mkRect({ x: bx, y: Math.round(H*.54), w: Math.round((W-120)/3)-8, h:64, fill:'rgba(16,185,129,.15)', rx:12, name:'pill'+i }));
        canvas.add(mkTxt(s.n, { sz: Math.round(H*.05), fw:'900', x: bx, y: Math.round(H*.55)+2, w: Math.round((W-120)/3)-8, align:'center', fill:'#6ee7b7', name:'pn'+i }));
        canvas.add(mkTxt(s.l, { sz:11, fw:'600', x: bx, y: Math.round(H*.55)+Math.round(H*.05)+6, w: Math.round((W-120)/3)-8, align:'center', fill:'rgba(255,255,255,.7)', name:'pl'+i }));
    });
    canvas.add(mkTxt('â™» RELATÃ“RIO DE IMPACTO 2025', { sz:11, fw:'800', y:58, fill:'#6ee7b7', name:'tag' }));
    canvas.add(mkTxt('Nosso Impacto\nFala por Si.', { sz: Math.round(H*.12), fw:'900', y:95, lh:1.1, name:'title' }));
    canvas.add(mkTxt('Obrigado a cada voluntÃ¡rio, doador e parceiro.', { sz: Math.round(H*.033), y: Math.round(H*.73), lh:1.5, fill:'rgba(255,255,255,.8)', name:'sub' }));
    finishTpl();
}

function tplGraphicAwareness() {
    startTpl('#4a044e');
    canvas.add(mkCircle({ x: W*.5, y:-80, r:300, fill:'#a855f7', op:.12, name:'c1' }));
    canvas.add(mkCircle({ x:-60, y: H, r:220, fill:'#ec4899', op:.08, name:'c2' }));
    // Horizontal accent
    canvas.add(mkRect({ x:0, y: Math.round(H*.48), w: W, h:2, fill:'rgba(236,72,153,.25)', name:'hr' }));
    canvas.add(mkTxt('#CONSCIENTIZAÃ‡ÃƒO', { sz:11, fw:'800', y:58, fill:'#e879f9', name:'tag' }));
    canvas.add(mkTxt('A MudanÃ§a\nComeÃ§a\nem VocÃª.', { sz: Math.round(H*.13), fw:'900', y:95, lh:1.05, shadow: new fabric.Shadow({color:'rgba(236,72,153,.3)',blur:20}), name:'title' }));
    canvas.add(mkTxt('Sua voz tem o poder de transformar realidades. Use-a.', { sz: Math.round(H*.034), y: Math.round(H*.7), lh:1.6, fill:'rgba(255,255,255,.85)', name:'sub' }));
    finishTpl();
}

function tplGraphicMinimal() {
    startTpl('#f8fafc');
    // Decorative bar
    canvas.add(mkRect({ x:0, y:0, w:8, h:H, fill:'#6366f1', name:'bar' }));
    canvas.add(mkRect({ x:8, y:0, w: Math.round(W*.35), h:H, fill:'#f1f5f9', name:'sidebar' }));
    // Geometric accent
    canvas.add(mkCircle({ x: Math.round(W*.32), y: Math.round(H*.12), r: Math.round(H*.22), fill:'#e2e8f0', op:1, name:'circle-deco' }));
    canvas.add(mkTxt('OrganizaÃ§Ã£o\nNome', { sz: Math.round(H*.05), fw:'800', x:30, y:60, w:200, fill:'#1e293b', align:'center', name:'orgname' }));
    // Main area
    canvas.add(mkTxt('Proposta\nde Valor\nElegante.', { sz: Math.round(H*.1), fw:'900', x: Math.round(W*.42), y:60, w: W*.52, fill:'#0f172a', lh:1.1, name:'title' }));
    canvas.add(mkTxt('Layout clean e profissional para apresentaÃ§Ãµes corporativas e materiais institucionais.', { sz: Math.round(H*.031), x: Math.round(W*.42), y: Math.round(H*.6), w: W*.52, fill:'#475569', lh:1.6, name:'sub' }));
    canvas.add(mkRect({ x: Math.round(W*.42), y: Math.round(H*.8), w:180, h:44, fill:'#6366f1', rx:8, shadow: new fabric.Shadow({color:'rgba(99,102,241,.3)',blur:12,offsetY:4}), name:'btn-bg' }));
    canvas.add(mkTxt('Saiba Mais', { sz:14, fw:'700', x: Math.round(W*.42), y: Math.round(H*.8)+13, w:180, fill:'#fff', align:'center', name:'btn-txt' }));
    finishTpl();
}

function tplGraphicSplit() {
    startTpl('#0f172a');
    // Split geometry
    canvas.add(mkRect({ x: Math.round(W*.5), y:0, w: Math.round(W*.5), h:H, fill:'#6366f1', name:'right-bg' }));
    // Diagonal divider
    const tri = new fabric.Triangle({ left: Math.round(W*.44), top:0, width: Math.round(W*.15), height:H, fill:'#6366f1', angle:0, selectable:true, name:'divider' });
    canvas.add(tri);
    // Left side content
    canvas.add(mkTxt('NOVO\nLANÃ‡AMENTO', { sz: Math.round(H*.1), fw:'900', x:40, y:80, w: Math.round(W*.42), fill:'#f8fafc', lh:1.1, name:'title' }));
    canvas.add(mkTxt('O produto que sua equipe esperava chegou.', { sz: Math.round(H*.033), x:40, y: Math.round(H*.62), w: Math.round(W*.42), fill:'#94a3b8', lh:1.6, name:'sub' }));
    canvas.add(mkRect({ x:40, y: Math.round(H*.8), w:160, h:44, fill:'#fff', rx:8, name:'btn-bg' }));
    canvas.add(mkTxt('Acessar', { sz:14, fw:'700', x:40, y: Math.round(H*.8)+13, w:160, fill:'#6366f1', align:'center', name:'btn-txt' }));
    // Right side large number
    canvas.add(mkTxt('2025', { sz: Math.round(H*.22), fw:'900', x: Math.round(W*.54), y: Math.round(H*.3), w: Math.round(W*.42), fill:'rgba(255,255,255,.9)', align:'center', name:'year' }));
    finishTpl();
}

function tplGraphicDuotone() {
    startTpl('#7f1d1d');
    canvas.add(mkCircle({ x: W*.5, y:-60, r:280, fill:'#f97316', op:.14, name:'c1' }));
    canvas.add(mkCircle({ x: W-100, y: H, r:220, fill:'#ef4444', op:.12, name:'c2' }));
    // Horizontal line accent
    canvas.add(mkRect({ x:60, y: Math.round(H*.5), w: Math.round(W*.35), h:3, fill:'#fb923c', rx:2, name:'accent' }));
    canvas.add(mkTxt('ðŸ† CONQUISTA 2025', { sz:13, fw:'800', y:55, fill:'#fb923c', name:'tag' }));
    canvas.add(mkTxt('PrÃªmio\nde ExcelÃªncia\nEmpresarial.', { sz: Math.round(H*.12), fw:'900', y:95, lh:1.05, shadow: new fabric.Shadow({color:'rgba(251,146,60,.25)',blur:18}), name:'title' }));
    canvas.add(mkTxt('Reconhecimento pela inovaÃ§Ã£o e impacto social gerado em 2025.', { sz: Math.round(H*.033), y: Math.round(H*.72), lh:1.6, fill:'rgba(255,255,255,.85)', name:'sub' }));
    finishTpl();
}

function tplGraphicAgency() {
    startTpl('#162d40');
    // Glow behind rings
    canvas.add(mkCircle({ x: W*.5-200, y: H*.4-200, r:200, fill:'#38bdf8', op:.1, name:'glow' }));
    
    // Simulated Orbits using Ellipses
    const o1 = new fabric.Ellipse({ left:W*.05, top:H*.45, rx: W*.45, ry: H*.1, stroke:'rgba(255,255,255,0.8)', strokeWidth:2, fill:'transparent', angle:-10, selectable:true, name:'orbit1' });
    const o2 = new fabric.Ellipse({ left:W*.25, top:H*.25, rx: W*.3, ry: H*.06, stroke:'rgba(255,255,255,0.6)', strokeWidth:2, fill:'transparent', angle:5, selectable:true, name:'orbit2' });
    canvas.add(o1, o2);

    // Floating 3D-like red spheres
    canvas.add(mkCircle({ x:W*.15, y:H*.3, r:20, fill:'#ef4444', op:1, shadow: new fabric.Shadow({color:'rgba(239,68,68,0.5)',blur:15}) }));
    canvas.add(mkCircle({ x:W*.8, y:H*.35, r:15, fill:'#ef4444', op:0.8 }));
    canvas.add(mkCircle({ x:W*.05, y:H*.7, r:60, fill:'#ef4444', op:1, shadow: new fabric.Shadow({color:'rgba(239,68,68,0.6)',blur:20}) }));
    canvas.add(mkCircle({ x:W*.85, y:H*.65, r:45, fill:'#ef4444', op:0.9 }));

    canvas.add(mkTxt('âž¤ VIVENSI LOGO', { sz:20, fw:'900', x:40, y:40, fill:'#fef08a' }));
    canvas.add(mkTxt('SUA TAGLINE AQUI', { sz:10, fw:'700', x:42, y:65, fill:'#94a3b8' }));

    // Dark bar background for the main text
    canvas.add(mkRect({ x:0, y: Math.round(H*.6), w: W, h: Math.round(H*.22), fill:'#0d1d2b', shadow: new fabric.Shadow({color:'rgba(0,0,0,0.5)',blur:20,offsetY:-10}) }));
    
    // Top and bottom yellow accent lines on the dark bar
    canvas.add(mkRect({ x:0, y: Math.round(H*.6), w: W, h: 2, fill:'#fef08a' }));
    canvas.add(mkRect({ x:0, y: Math.round(H*.82), w: W, h: 2, fill:'#fef08a' }));

    // Main text
    canvas.add(mkTxt('DIGITAL', { sz: Math.round(H*.06), fw:'800', y: Math.round(H*.63), fill:'#ffffff', align:'center', name:'title1' }));
    canvas.add(mkTxt('MARKETING', { sz: Math.round(H*.065), fw:'900', y: Math.round(H*.69), fill:'#fef08a', align:'center', name:'title2' }));
    canvas.add(mkTxt('AGENCY', { sz: Math.round(H*.055), fw:'800', y: Math.round(H*.755), fill:'#ffffff', align:'center', name:'title3' }));

    // Footer info
    canvas.add(mkTxt('VISIT OUR WEB', { sz:12, fw:'700', x:40, y: Math.round(H*.88), w:200, fill:'#fef08a' }));
    canvas.add(mkTxt('WEBSITE HERE', { sz:18, fw:'900', x:40, y: Math.round(H*.9)+4, w:200, fill:'#ffffff' }));

    canvas.add(mkTxt('FOR MORE INFO', { sz:12, fw:'700', x: W-240, y: Math.round(H*.88), w:200, fill:'#fef08a', align:'right' }));
    canvas.add(mkTxt('123-456-7890', { sz:18, fw:'900', x: W-240, y: Math.round(H*.9)+4, w:200, fill:'#ffffff', align:'right' }));
    
    finishTpl();
}

function tplGraphicBlackFriday() {
    startTpl('#111111');
    canvas.add(mkCircle({ x: -100, y: -100, r: 250, fill:'#e11d48', op: .15 }));
    canvas.add(mkCircle({ x: W-150, y: H*.7, r: 200, fill:'#e11d48', op: .1 }));
    
    const grad = new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W*.6,y2:H*.6}, colorStops:[{offset:0,color:'#be123c'},{offset:1,color:'#e11d48'}]});
    const badge = mkRect({ x:W*.5-120, y:80, w:240, h:46, fill:grad, rx:23, shadow: new fabric.Shadow({color:'rgba(225,29,72,.6)',blur:20,offsetY:6}) });
    canvas.add(badge);
    canvas.add(mkTxt('BLACK FRIDAY 2025', { sz:15, fw:'900', y:94, fill:'#fff', align:'center', w:W }));

    canvas.add(mkTxt('ATÃ‰', { sz: Math.round(H*.04), fw:'800', x: W*.2, y: Math.round(H*.35), fill:'#f43f5e' }));
    canvas.add(mkTxt('70%', { sz: Math.round(H*.22), fw:'900', x: W*.2, y: Math.round(H*.35)+Math.round(H*.01), fill:'#ffffff', shadow: new fabric.Shadow({color:'rgba(0,0,0,.8)',blur:20}) }));
    canvas.add(mkTxt('DE DESCONTO', { sz: Math.round(H*.05), fw:'800', x: W*.2, y: Math.round(H*.35)+Math.round(H*.24), fill:'#f43f5e' }));
    
    canvas.add(mkTxt('NÃƒO PERCA A MAIOR QUEIMA DE ESTOQUE DO ANO', { sz: Math.round(H*.025), fw:'600', x: W*.2, y: Math.round(H*.68), fill:'#a1a1aa' }));
    
    canvas.add(mkRect({ x:W*.2, y: Math.round(H*.78), w:220, h:54, fill:'#ffffff', rx:8, shadow: new fabric.Shadow({color:'rgba(255,255,255,.3)',blur:15,offsetY:5}) }));
    canvas.add(mkTxt('COMPRAR AGORA', { sz:16, fw:'900', x:W*.2, y: Math.round(H*.78)+17, w:220, fill:'#000000', align:'center' }));
    finishTpl();
}

function tplAgencySquare() {
    startTpl('#fafaf9');
    canvas.add(mkRect({x:0, y:0, w:W*.65, h:H*.6, fill:'#78716c', rx:40}));
    canvas.add(mkTxt('We Are\nDigital\nMarketing\nAgency', {sz:Math.round(H*.09), fw:'900', x:40, y:80, lh:1.1, fill:'#ffffff'}));
    canvas.add(mkTxt('â€¢ Business Analysis\nâ€¢ Market Research', {sz:16, fw:'600', x:40, y:H*.65, fill:'#57534e', lh:1.8}));
    canvas.add(mkTxt('â€¢ SEO Service\nâ€¢ Brand Build', {sz:16, fw:'600', x:W*.32, y:H*.65, fill:'#57534e', lh:1.8}));
    // Floating accent squares
    canvas.add(mkRect({x:W*.48, y:180, w:50, h:50, fill:'#f43f5e', rx:12, shadow:new fabric.Shadow({color:'rgba(0,0,0,0.2)',blur:10})}));
    canvas.add(mkRect({x:W*.45, y:260, w:55, h:55, fill:'#3b82f6', rx:12, shadow:new fabric.Shadow({color:'rgba(0,0,0,0.2)',blur:10})}));
    // Photo placeholder
    canvas.add(mkRect({x:W*.55, y:80, w:W*.4, h:H*.5, fill:'#d6d3d1', rx:30, name:'img-placeholder'}));
    canvas.add(mkRect({x:W*.5-80, y:H*.85, w:160, h:45, fill:'#78716c', rx:22}));
    canvas.add(mkTxt('JOIN NOW', {sz:16, fw:'800', x:W*.5-80, y:H*.85+14, w:160, fill:'#fff', align:'center'}));
    finishTpl();
    // Photo enhances async
    asyncImg('https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&q=80', img => {
        img.set({ scaleX:(W*.4)/img.width, scaleY:(H*.5)/img.height, left:W*.55, top:80, rx:30, ry:30, shadow:new fabric.Shadow({color:'rgba(0,0,0,0.15)',blur:20}) });
        const ph = canvas.getObjects().find(o => o.name === 'img-placeholder');
        if (ph) canvas.remove(ph);
        canvas.add(img); canvas.requestRenderAll();
    });
}

// â”€â”€â”€ NEW TEMPLATES (Black Friday, Agency, Fashion, Travel) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function tplBfCameraDark() {
    startTpl('#1a0a00');
    const grad = new fabric.Gradient({ type:'radial', coords:{x1:W*.5,y1:H*.5,x2:W*.5,y2:H*.5,r1:0,r2:W*.6},
        colorStops:[{offset:0,color:'rgba(217,119,6,.3)'},{offset:1,color:'transparent'}]});
    canvas.add(mkRect({ x:0,y:0,w:W,h:H, fill:grad, name:'glow-bg' }));
    canvas.add(mkTxt('Mega Offer', { sz:Math.round(H*.045), fw:'300', y:60, fill:'rgba(255,255,255,.7)', align:'center', w:W, font:'Inter' }));
    canvas.add(mkTxt('Black', { sz:Math.round(H*.13), fw:'900', y:100, fill:'#ffffff', align:'center', w:W, shadow:new fabric.Shadow({color:'rgba(0,0,0,.6)',blur:20}) }));
    canvas.add(mkTxt('Friday', { sz:Math.round(H*.13), fw:'900', y:100+Math.round(H*.12), fill:'#f97316', align:'center', w:W, shadow:new fabric.Shadow({color:'rgba(249,115,22,.4)',blur:20}) }));
    // Discount badge
    canvas.add(mkRect({ x:W*.6, y:Math.round(H*.52), w:160, h:50, fill:'#d97706', rx:8, shadow:new fabric.Shadow({color:'rgba(217,119,6,.5)',blur:12}), name:'disc-bg' }));
    canvas.add(mkTxt('Get upto', { sz:11, fw:'700', x:W*.6, y:Math.round(H*.527), w:160, fill:'#fff', align:'center' }));
    canvas.add(mkTxt('80% OFF', { sz:24, fw:'900', x:W*.6, y:Math.round(H*.545), w:160, fill:'#fff', align:'center' }));
    canvas.add(mkTxt('Best Digital CAMERA\nSuper offer', { sz:15, fw:'800', x:40, y:Math.round(H*.5), w:200, fill:'#f97316', lh:1.4 }));
    canvas.add(mkRect({ x:W*.5-80, y:Math.round(H*.78), w:160, h:44, fill:'#d97706', rx:22, shadow:new fabric.Shadow({color:'rgba(217,119,6,.5)',blur:14,offsetY:5}) }));
    canvas.add(mkTxt('ORDER NOW', { sz:14, fw:'800', x:W*.5-80, y:Math.round(H*.78)+14, w:160, fill:'#fff', align:'center' }));
    canvas.add(mkTxt('919-726-3108\nwww.yoursite.com', { sz:12, fw:'600', y:Math.round(H*.9), fill:'rgba(255,255,255,.6)', align:'center', w:W, lh:1.6 }));
    finishTpl();
}

function tplBfWatchOrange() {
    startTpl('#1c1000');
    // Orange glow at bottom
    canvas.add(mkRect({ x:0, y:H*.65, w:W, h:H*.35, fill:'#f97316', name:'orange-base' }));
    canvas.add(mkCircle({ x:W*.5-150, y:H*.5, r:180, fill:'#fb923c', op:.2, name:'glow' }));
    canvas.add(mkTxt('Super Sale', { sz:Math.round(H*.04), fw:'300', y:55, fill:'rgba(255,255,255,.7)', align:'center', w:W }));
    canvas.add(mkTxt('Black', { sz:Math.round(H*.13), fw:'900', y:90, fill:'rgba(255,255,255,.95)', align:'center', w:W, font:'Playfair Display' }));
    canvas.add(mkTxt('Friday', { sz:Math.round(H*.14), fw:'900', y:90+Math.round(H*.12), fill:'#f97316', align:'center', w:W, shadow:new fabric.Shadow({color:'rgba(249,115,22,.6)',blur:18}) }));
    canvas.add(mkRect({ x:W*.62, y:Math.round(H*.48), w:150, h:54, fill:'rgba(0,0,0,.35)', rx:4, name:'disc-bg' }));
    canvas.add(mkTxt('Get Upto\n45% Discount', { sz:16, fw:'800', x:W*.62, y:Math.round(H*.49), w:150, fill:'#fff', align:'center', lh:1.3 }));
    canvas.add(mkRect({ x:W*.5-80, y:Math.round(H*.8), w:160, h:42, fill:'#000', rx:21, name:'btn' }));
    canvas.add(mkTxt('ORDER NOW â†’', { sz:13, fw:'800', x:W*.5-80, y:Math.round(H*.8)+13, w:160, fill:'#fff', align:'center' }));
    canvas.add(mkTxt('323-543-4145\nINCLUDEHEREYOURWEBSITE', { sz:11, fw:'600', y:Math.round(H*.9), fill:'rgba(255,255,255,.7)', align:'center', w:W, lh:1.6 }));
    finishTpl();
}

function tplBfHeadphone() {
    startTpl('#111111');
    canvas.add(mkTxt('Black', { sz:Math.round(H*.16), fw:'900', y:60, fill:'#ffffff', align:'center', w:W, shadow:new fabric.Shadow({color:'rgba(0,0,0,.7)',blur:12}) }));
    canvas.add(mkTxt('Friday', { sz:Math.round(H*.16), fw:'900', y:60+Math.round(H*.15), fill:'#eab308', align:'center', w:W, shadow:new fabric.Shadow({color:'rgba(234,179,8,.4)',blur:16}) }));
    canvas.add(mkTxt('Limited Offer', { sz:Math.round(H*.035), fw:'700', y:60+Math.round(H*.31), fill:'rgba(255,255,255,.75)', align:'center', w:W }));
    canvas.add(mkRect({ x:W*.35, y:Math.round(H*.55), w:W*.3, h:2, fill:'rgba(234,179,8,.4)', name:'hr' }));
    canvas.add(mkTxt('Get up To\n50% OFF\nDiscount â–º', { sz:Math.round(H*.05), fw:'900', x:W*.65, y:Math.round(H*.57), w:W*.3, fill:'#eab308', lh:1.2, align:'right' }));
    canvas.add(mkTxt('205-741-8365\nwww.yourwebsite.com', { sz:12, fw:'600', x:40, y:Math.round(H*.88), fill:'#eab308', lh:1.5 }));
    canvas.add(mkRect({ x:W*.62, y:Math.round(H*.86), w:150, h:42, fill:'#eab308', rx:21, name:'btn' }));
    canvas.add(mkTxt('â†’ SHOP NOW', { sz:13, fw:'800', x:W*.62, y:Math.round(H*.86)+13, w:150, fill:'#111', align:'center' }));
    finishTpl();
}

function tplAgencyCreativeRed() {
    startTpl('#f5f5f4');
    canvas.add(mkRect({ x:0, y:H*.85, w:W, h:H*.15, fill:'#ef4444', name:'footer-bar' }));
    canvas.add(mkRect({ x:0, y:H*.85, w:W, h:3, fill:'#b91c1c', name:'footer-line' }));
    // Photo placeholder (replaced when image loads)
    canvas.add(mkRect({ x:W*.45, y:0, w:W*.55, h:H*.85, fill:'#e5e7eb', name:'img-placeholder' }));
    // Red accent shapes â€” always visible
    canvas.add(mkRect({ x:W*.2, y:0, w:W*.28, h:H*.5, fill:'#dc2626', rx:0, name:'red-block' }));
    canvas.add(mkTxt('WE ARE\nCREATIVE\nBUSINESS\nSOLUTIONS\nAGENCY', { sz:Math.round(H*.07), fw:'900', x:20, y:50, w:W*.35, fill:'#111827', lh:1.1, name:'title' }));
    canvas.add(mkTxt('WE ARE\nCREATIVE', { sz:Math.round(H*.08), fw:'900', x:W*.21, y:H*.08, w:W*.27, fill:'#fff', lh:1.1, align:'center', name:'title-red' }));
    canvas.add(mkTxt('WHAT WE DO\nOUR SERVICES', { sz:12, fw:'800', x:20, y:H*.56, w:W*.38, fill:'#374151', lh:1.4 }));
    const svcs = ['STRATEGY AND PLANNING','CORPORATE FINANCE','MARKET RESEARCH','BUSINESS ANALYSIS'];
    svcs.forEach((s,i) => canvas.add(mkTxt(`${i+1}  ${s}`, { sz:11, fw:'600', x:20, y:H*.62+i*22, w:W*.42, fill:'#dc2626' })));
    canvas.add(mkTxt('609-791-3583\nWWW.YOURWEBSITE.COM', { sz:12, fw:'700', x:40, y:H*.885, fill:'#fff', lh:1.4 }));
    finishTpl();
    // Person photo enhances async
    asyncImg('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80', img => {
        img.set({ scaleX:(W*.55)/img.width, scaleY:(H*.85)/img.height, left:W*.45, top:0, name:'person-img' });
        const ph = canvas.getObjects().find(o => o.name === 'img-placeholder');
        if (ph) canvas.remove(ph);
        canvas.add(img);
        // Send photo behind text layers
        const redBlock = canvas.getObjects().find(o => o.name === 'red-block');
        if (redBlock) canvas.sendToBack(img);
        canvas.requestRenderAll();
    });
}

function tplAgencyBlue() {
    startTpl('#f0f9ff');
    canvas.add(mkRect({ x:0, y:0, w:W*.08, h:H, fill:'#1d4ed8', name:'bar-left' }));
    // Teal diagonal accent
    canvas.add(mkRect({ x:W*.55, y:-50, w:W*.5, h:H*.5, fill:'#0ea5e9', angle:15, rx:20, name:'accent' }));
    canvas.add(mkTxt('we are', { sz:Math.round(H*.04), fw:'400', x:W*.12, y:60, fill:'#64748b', font:'Playfair Display' }));
    canvas.add(mkTxt('creative', { sz:Math.round(H*.055), fw:'700', x:W*.12, y:95, fill:'#0c4a6e', font:'Playfair Display' }));
    canvas.add(mkTxt('MARKETING\nAGENCY', { sz:Math.round(H*.1), fw:'900', x:W*.12, y:145, w:W*.6, fill:'#0f172a', lh:1.05 }));
    canvas.add(mkTxt('join online\nTRAINING SESSION', { sz:Math.round(H*.04), fw:'700', x:W*.12, y:H*.52, fill:'#0ea5e9', lh:1.3 }));
    const pts = ['Digital Marketing','Brand Build Strategies','SEO Campaign Strategies'];
    pts.forEach((p,i) => canvas.add(mkTxt(`â€¢ ${p}`, { sz:13, fw:'600', x:W*.12, y:H*.65+i*22, fill:'#374151' })));
    canvas.add(mkRect({ x:W*.12, y:H*.84, w:180, h:42, fill:'#1d4ed8', rx:21, name:'btn' }));
    canvas.add(mkTxt('CONTATO', { sz:14, fw:'800', x:W*.12, y:H*.84+13, w:180, fill:'#fff', align:'center' }));
    canvas.add(mkTxt('000 123 456 789', { sz:13, fw:'600', x:W*.58, y:H*.87, fill:'#0f172a' }));
    finishTpl();
}

function tplCleaningRed() {
    startTpl('#ffffff');
    canvas.add(mkRect({ x:0, y:0, w:W*.45, h:H, fill:'#dc2626', name:'red-bg' }));
    // White rounded shape top
    canvas.add(mkCircle({ x:W*.35, y:-80, r:180, fill:'#ffffff', op:1, name:'circle-deco' }));
    canvas.add(mkTxt('Cleaning\nService', { sz:Math.round(H*.07), fw:'900', x:30, y:60, w:W*.4, fill:'#ffffff', lh:1.2, name:'title' }));
    const pts2 = ['Roof & Window cleaning','Furniture cleaning','Floor Carpet cleaning','Hotel & Office cleaning'];
    pts2.forEach((p,i) => canvas.add(mkTxt(`â— ${p}`, { sz:12, fw:'600', x:30, y:H*.55+i*26, fill:'#fff', name:'item'+i })));
    canvas.add(mkRect({ x:30, y:H*.84, w:140, h:40, fill:'#fff', rx:20, name:'btn-bg' }));
    canvas.add(mkTxt('â†’ CALL NOW', { sz:12, fw:'800', x:30, y:H*.84+13, w:140, fill:'#dc2626', align:'center' }));
    // Right side with images placeholder
    canvas.add(mkRect({ x:W*.48, y:H*.04, w:W*.48, h:H*.45, fill:'#fee2e2', rx:16, name:'img1-bg' }));
    canvas.add(mkRect({ x:W*.48, y:H*.52, w:W*.48, h:H*.42, fill:'#fecaca', rx:16, name:'img2-bg' }));
    // Discount badge
    canvas.add(mkCircle({ x:W*.7, y:H*.65, r:48, fill:'#dc2626', op:1, name:'disc-circle' }));
    canvas.add(mkTxt('20%\nDISCOUNT', { sz:14, fw:'900', x:W*.7-48, y:H*.65-22, w:96, fill:'#fff', align:'center', lh:1.2 }));
    canvas.add(mkTxt('804-790-3688\nWWW.YOURWEBSITE.COM', { sz:11, fw:'700', x:30, y:H*.9, fill:'rgba(255,255,255,.8)', lh:1.4 }));
    finishTpl();
}

function tplMarketingExpert() {
    startTpl('#fff7ed');
    // Orange diagonal shapes
    canvas.add(mkRect({ x:-30, y:0, w:W*.55, h:H*.55, fill:'#ea580c', angle:0, name:'orange-main' }));
    canvas.add(mkRect({ x:W*.5, y:-20, w:W*.6, h:80, fill:'#c2410c', angle:0, name:'top-bar' }));
    canvas.add(mkRect({ x:W*.5, y:H-80, w:W*.6, h:80, fill:'#ea580c', angle:0, name:'bottom-bar' }));
    canvas.add(mkTxt('We Are', { sz:Math.round(H*.045), fw:'400', x:30, y:60, fill:'#fff', font:'Playfair Display' }));
    canvas.add(mkTxt('Digital\nMarketing\nExpert', { sz:Math.round(H*.1), fw:'900', x:30, y:100, w:W*.48, fill:'#ffffff', lh:1.05 }));
    canvas.add(mkTxt('JOIN US\nwww.websitename.com', { sz:13, fw:'700', x:30, y:H*.65, fill:'#fff', lh:1.5 }));
    canvas.add(mkTxt('More Info Call Us:\n+000 123 456 7890', { sz:12, fw:'600', x:30, y:H*.8, fill:'#fff', lh:1.5 }));
    finishTpl();
}

function tplSwipeUpStory() {
    startTpl('#4f46e5');
    canvas.add(mkCircle({ x:W*.7, y:H*.1, r:100, fill:'#7c3aed', op:.7, name:'c1' }));
    canvas.add(mkCircle({ x:-30, y:H*.9, r:80, fill:'#312e81', op:.8, name:'c2' }));
    // Arrow decorations
    for(let i=0;i<3;i++) { canvas.add(new fabric.Triangle({ left:W*.78, top:H*.12+i*30, width:22, height:16, fill:'rgba(255,255,255,.4)', angle:180 })); }
    for(let i=0;i<3;i++) { canvas.add(new fabric.Triangle({ left:W*.08, top:H*.78+i*30, width:22, height:16, fill:'rgba(255,255,255,.4)', angle:180 })); }
    canvas.add(mkTxt('YOUR', { sz:Math.round(H*.17), fw:'900', y:H*.08, fill:'#fbbf24', align:'center', w:W }));
    canvas.add(mkTxt('HEADLINE\nHERE', { sz:Math.round(H*.15), fw:'900', y:H*.08+Math.round(H*.16), fill:'#ffffff', align:'center', w:W, lh:1.0 }));
    canvas.add(mkTxt('Lorem Ipsum is simply dummy text of the printing industry.', { sz:13, fw:'500', x:20, y:H*.72, w:W*.5, fill:'rgba(255,255,255,.85)', lh:1.5 }));
    // Dots pattern
    for(let i=0;i<5;i++) for(let j=0;j<4;j++) canvas.add(mkCircle({ x:W*.65+i*16, y:H*.05+j*16, r:2, fill:'#fff', op:.3 }));
    for(let i=0;i<5;i++) for(let j=0;j<4;j++) canvas.add(mkCircle({ x:W*.65+i*16, y:H*.78+j*16, r:2, fill:'#fff', op:.3 }));
    canvas.add(mkRect({ x:W*.5-80, y:H*.89, w:160, h:42, fill:'#fbbf24', rx:21, name:'btn' }));
    canvas.add(mkTxt('â–² Swipe Up', { sz:14, fw:'800', x:W*.5-80, y:H*.89+13, w:160, fill:'#1e1b4b', align:'center' }));
    finishTpl();
}

function tplDigitalMktgDark() {
    startTpl('#0d1d2b');
    canvas.add(mkRect({ x:W*.45, y:0, w:W*.55, h:H, fill:'#0a1520', name:'right-bg' }));
    canvas.add(mkCircle({ x:W*.5, y:H*.2, r:120, fill:'#0ea5e9', op:.06, name:'glow' }));
    canvas.add(mkTxt('LOGO\nTAGLINE HERE', { sz:12, fw:'800', x:20, y:30, fill:'rgba(255,255,255,.6)', lh:1.2 }));
    canvas.add(mkTxt('f  â—‹  Ã—  â–¶', { sz:14, fw:'700', x:W*.7, y:34, w:W*.25, fill:'rgba(255,255,255,.5)', align:'right' }));
    canvas.add(mkTxt('Digital', { sz:Math.round(H*.075), fw:'300', x:W*.5, y:80, w:W*.48, fill:'#ffffff', align:'center', font:'Inter' }));
    canvas.add(mkTxt('MARKETING\nAGENCY', { sz:Math.round(H*.09), fw:'900', x:W*.5, y:80+Math.round(H*.07), w:W*.48, fill:'#fbbf24', align:'center', lh:1.0 }));
    canvas.add(mkRect({ x:W*.5, y:H*.38, w:W*.48, h:1, fill:'rgba(255,255,255,.1)', name:'hr' }));
    canvas.add(mkTxt('Lorem ipsum dolor sit amet, consectetuer\nadipiscing elit, tincidunt ut laoreet dolore', { sz:11, fw:'400', x:W*.5, y:H*.4, w:W*.48, fill:'rgba(255,255,255,.5)', lh:1.6, align:'center' }));
    canvas.add(mkRect({ x:W*.55, y:H*.62, w:120, h:36, fill:'transparent', rx:4, stroke:'#fbbf24', strokeWidth:1.5, name:'disc-box' }));
    canvas.add(mkTxt('UP TO 50%\nDISCOUNT', { sz:16, fw:'900', x:W*.55, y:H*.63, w:120, fill:'#fbbf24', align:'center', lh:1.2 }));
    canvas.add(mkRect({ x:W*.5, y:H*.8, w:W*.48, h:42, fill:'transparent', rx:4, stroke:'#ffffff', strokeWidth:1.5, name:'btn' }));
    canvas.add(mkTxt('LEARN MORE', { sz:14, fw:'700', x:W*.5, y:H*.8+13, w:W*.48, fill:'#ffffff', align:'center' }));
    canvas.add(mkTxt('000 123 456 789', { sz:11, fw:'600', x:W*.5, y:H*.9, w:W*.48, fill:'rgba(255,255,255,.4)', align:'center' }));
    finishTpl();
}

// â”€â”€â”€ PROMO TEMPLATES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function tplPromoMegaSale() {
    // â”€â”€ MEGA SALE BLACK FRIDAY (Blue Lightning) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // Fundo + radial glow no centro superior
    startTpl('#0a1a3d');
    // Deep radial glow
    const radGrad = new fabric.Gradient({ type:'radial',
        coords:{ x1:W*.5,y1:H*.3,x2:W*.5,y2:H*.3,r1:0,r2:W*.75 },
        colorStops:[{offset:0,color:'rgba(59,130,246,.45)'},{offset:1,color:'transparent'}]});
    canvas.add(mkRect({x:0,y:0,w:W,h:H,fill:radGrad,name:'glow-bg'}));

    // Raio esquerdo
    canvas.add(new fabric.Polygon(
        [{x:0,y:0},{x:28,y:0},{x:14,y:44},{x:38,y:44},{x:10,y:100},{x:22,y:100},{x:0,y:150}],
        {left:Math.round(W*.1),top:50,fill:'#38bdf8',opacity:.9,name:'bolt-l',selectable:true}));
    // Raio direito (espelhado)
    canvas.add(new fabric.Polygon(
        [{x:38,y:0},{x:10,y:0},{x:24,y:44},{x:0,y:44},{x:28,y:100},{x:16,y:100},{x:38,y:150}],
        {left:Math.round(W*.77),top:50,fill:'#38bdf8',opacity:.9,name:'bolt-r',selectable:true}));

    // Logo / Marca topo-esq
    canvas.add(mkTxt('LOGO HERE', {sz:11,fw:'800',x:20,y:20,fill:'rgba(255,255,255,.7)'}));
    // Redes topo-dir
    canvas.add(mkTxt('f  âœ¦  @', {sz:14,fw:'700',x:W-110,y:20,w:100,fill:'rgba(255,255,255,.55)',align:'right'}));

    // Tag
    canvas.add(mkTxt('MEGA SALE', {sz:Math.round(H*.04),fw:'900',y:Math.round(H*.08),fill:'#ffffff',align:'center',w:W}));

    // Textos principais â€” BLACK + FRIDAY centralizados
    const tBLACK = Math.round(H*.19);
    const szBig  = Math.round(H*.195);
    canvas.add(mkTxt('BLACK', {
        sz:szBig, fw:'900', y:Math.round(H*.11), fill:'#fcdc00', align:'center', w:W,
        shadow:new fabric.Shadow({color:'rgba(0,0,0,.7)',blur:20,offsetY:10}),
        font:'Inter'
    }));
    canvas.add(mkTxt('FRIDAY', {
        sz:szBig, fw:'900', y:Math.round(H*.11)+Math.round(szBig*.85), fill:'#e8f0ff', align:'center', w:W,
        shadow:new fabric.Shadow({color:'rgba(0,0,80,.6)',blur:18,offsetY:8}),
        font:'Inter'
    }));
    // Subtag
    canvas.add(mkTxt('LIMITED TIME OFFER', {
        sz:Math.round(H*.03),fw:'700',
        y:Math.round(H*.11)+Math.round(szBig*.85)*2+8,
        fill:'rgba(255,255,255,.75)',align:'center',w:W,
        letterSpacing:4
    }));

    // Placa dark inferior com wave
    const waveY = Math.round(H*.66);
    canvas.add(new fabric.Ellipse({left:-W*.4,top:waveY-Math.round(H*.18),rx:W*.9,ry:Math.round(H*.18),fill:'#020d26',name:'wave'}));
    canvas.add(mkRect({x:0,y:waveY,w:W,h:H-waveY,fill:'#020d26',name:'dark-bottom'}));

    // Info side-by-side na placa dark
    canvas.add(mkRect({x:20,y:waveY+12,w:W*.42,h:72,fill:'rgba(255,255,255,.06)',rx:8}));
    canvas.add(mkTxt('FREE HOME', {sz:13,fw:'700',x:30,y:waveY+18,fill:'#fcdc00'}));
    canvas.add(mkTxt('DELIVERY 24/7', {sz:22,fw:'900',x:30,y:waveY+34,fill:'#ffffff'}));

    canvas.add(mkRect({x:W*.55,y:waveY+12,w:W*.42,h:72,fill:'rgba(255,255,255,.06)',rx:8}));
    canvas.add(mkTxt('PRICE', {sz:13,fw:'700',x:W*.57,y:waveY+18,fill:'#fcdc00'}));
    canvas.add(mkTxt('START AT', {sz:13,fw:'700',x:W*.57,y:waveY+32,fill:'rgba(255,255,255,.7)'}));
    canvas.add(mkTxt('$15.99', {sz:24,fw:'900',x:W*.57,y:waveY+44,fill:'#ffffff'}));

    // Linha separadora
    canvas.add(mkRect({x:20,y:waveY+94,w:W-40,h:1,fill:'rgba(255,255,255,.1)'}));

    // UPTO 50% OFF
    const offY = waveY+104;
    canvas.add(mkTxt('UPTO', {sz:Math.round(H*.04),fw:'700',x:20,y:offY+8,fill:'rgba(255,255,255,.9)'}));
    canvas.add(mkTxt('50%', {sz:Math.round(H*.1),fw:'900',x:20,y:offY+2,fill:'#fcdc00',shadow:new fabric.Shadow({color:'rgba(252,220,0,.3)',blur:14})}));
    canvas.add(mkTxt('OFF', {sz:Math.round(H*.055),fw:'900',x:20+Math.round(H*.1)*.55,y:offY+Math.round(H*.055),fill:'#ffffff'}));
    canvas.add(mkTxt('PROMO:BLACKFRIDAY50', {sz:12,fw:'700',x:20,y:offY+Math.round(H*.1)+6,fill:'rgba(255,255,255,.5)',letterSpacing:2}));

    // BotÃ£o CTA
    canvas.add(mkRect({x:W*.5-110,y:H-62,w:220,h:44,fill:'#fcdc00',rx:22,shadow:new fabric.Shadow({color:'rgba(252,220,0,.4)',blur:14,offsetY:4})}));
    canvas.add(mkTxt('SHOP NOW CLICK HERE â†˜', {sz:13,fw:'900',x:W*.5-110,y:H-48,w:220,fill:'#0a1a3d',align:'center'}));
    canvas.add(mkTxt('your website goes here', {sz:11,fw:'500',y:H-14,fill:'rgba(255,255,255,.4)',align:'center',w:W}));

    finishTpl();
    // Produto de fundo â€” vai para trÃ¡s de tudo
    asyncImg('https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1080&q=80', img => {
        const s = Math.max(W/img.width, H*.55/img.height);
        img.set({ scaleX:s, scaleY:s, left:W*.12, top:Math.round(H*.28),
            shadow:new fabric.Shadow({color:'rgba(0,0,0,.9)',blur:30,offsetY:20}),
            name:'product-img'
        });
        canvas.add(img);
        // Garante que imagem fica entre glow e textos
        const glow = canvas.getObjects().find(o=>o.name==='glow-bg');
        if(glow) canvas.moveObjectTo(img, canvas.getObjects().indexOf(glow)+1);
        canvas.requestRenderAll();
    });
}

function tplPromoWeekend() {
    // â”€â”€ WEEKEND OFFER (White + Blue Organic curves) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    startTpl('#f1f5f9');

    // Grande mancha azul Ã  direita (curva orgÃ¢nica superior)
    canvas.add(new fabric.Ellipse({left:W*.2,top:-H*.35,rx:W*.65,ry:H*.7,fill:'#0ea5e9',name:'blue-blob-top'}));

    // Mancha azul inferior esquerda pequena
    canvas.add(new fabric.Ellipse({left:-W*.2,top:H*.78,rx:W*.45,ry:H*.3,fill:'#0284c7',name:'blue-blob-bottom'}));

    // Logo top-left
    canvas.add(mkTxt('LOGO\nTACGLINE', {sz:11,fw:'800',x:22,y:22,fill:'#1e3a8a',lh:1.3}));
    // Social icons top-right
    canvas.add(mkTxt('Follow Us Now\nf  âœ¦  â—‰  âœ‰', {sz:11,fw:'600',x:W-130,y:22,w:120,fill:'rgba(255,255,255,.85)',align:'right',lh:1.5}));

    // Data â€” left side
    canvas.add(mkTxt('29', {sz:Math.round(H*.12),fw:'900',x:22,y:Math.round(H*.32),fill:'#0284c7',shadow:new fabric.Shadow({color:'rgba(0,0,0,.1)',blur:6})}));
    canvas.add(mkTxt('NOVEMBER', {sz:Math.round(H*.038),fw:'900',x:22,y:Math.round(H*.43),fill:'#1e3a8a'}));
    canvas.add(mkTxt('2 0 2 5', {sz:Math.round(H*.025),fw:'600',x:22,y:Math.round(H*.46)+4,fill:'#64748b',letterSpacing:6}));

    // TÃ­tulos sobre a mancha azul â€” lado direito
    const txR = Math.round(W*.38);
    canvas.add(mkTxt('Limited TIME Offer', {sz:Math.round(H*.038),fw:'500',x:txR,y:Math.round(H*.07),w:W*.6,fill:'rgba(255,255,255,.9)',font:'Playfair Display'}));
    canvas.add(mkTxt('BLACK', {sz:Math.round(H*.155),fw:'900',x:txR,y:Math.round(H*.11),w:W*.6,fill:'#ffffff',shadow:new fabric.Shadow({color:'rgba(0,0,0,.3)',blur:14,offsetY:6})}));
    // Faixa "Sale" diagonal por cima do FRIDAY
    canvas.add(mkRect({x:txR,y:Math.round(H*.265),w:W*.55,h:Math.round(H*.07),fill:'#1e3a8a',rx:4,angle:-3,name:'sale-badge'}));
    canvas.add(mkTxt('Sale', {sz:Math.round(H*.055),fw:'900',x:txR+Math.round(W*.06),y:Math.round(H*.27),w:W*.4,fill:'#ffffff',angle:-3}));
    canvas.add(mkTxt('FRIDAY', {sz:Math.round(H*.155),fw:'900',x:txR,y:Math.round(H*.31),w:W*.6,fill:'#0f172a',shadow:new fabric.Shadow({color:'rgba(0,0,0,.15)',blur:10,offsetY:5})}));
    canvas.add(mkTxt('THIS WEEKEND ONLY', {sz:Math.round(H*.03),fw:'700',x:txR,y:Math.round(H*.47),w:W*.6,fill:'rgba(15,23,42,.65)',letterSpacing:3}));

    // Desconto badge â€” canto inferior direito
    const discX = Math.round(W*.65), discY = Math.round(H*.6);
    canvas.add(mkRect({x:discX,y:discY,w:190,h:70,fill:'rgba(255,255,255,.9)',rx:8,shadow:new fabric.Shadow({color:'rgba(0,0,0,.12)',blur:12})}));
    canvas.add(mkTxt('Save UP To', {sz:12,fw:'600',x:discX+8,y:discY+8,fill:'#64748b'}));
    canvas.add(mkTxt('45%', {sz:Math.round(H*.08),fw:'900',x:discX+8,y:discY+6,fill:'#0284c7'}));
    canvas.add(mkTxt('Discount', {sz:16,fw:'700',x:discX+Math.round(H*.08)*.55+8,y:discY+Math.round(H*.065),fill:'#1e3a8a'}));

    // RodapÃ©
    canvas.add(mkRect({x:0,y:H-70,w:W,h:70,fill:'#0284c7',name:'footer',rx:0}));
    canvas.add(mkRect({x:28,y:H-56,w:140,h:38,fill:'rgba(255,255,255,.15)',rx:19,name:'btn-bg'}));
    canvas.add(mkTxt('âŠ™  ORDER NOW', {sz:13,fw:'800',x:28,y:H-43,w:140,fill:'#ffffff',align:'center'}));
    canvas.add(mkTxt('323-517-4946', {sz:16,fw:'700',x:W*.45,y:H-50,fill:'#ffffff'}));
    canvas.add(mkTxt('www.yourwebsite.com', {sz:11,fw:'500',x:W*.45,y:H-30,fill:'rgba(255,255,255,.7)'}));

    finishTpl();
    // Produto/instrumento async â€” entra na camada correta
    asyncImg('https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=1080&q=80', img => {
        const scale = (H*.52)/img.height;
        img.set({ scaleX:scale, scaleY:scale,
            left:Math.round(W*.22), top:Math.round(H*.28),
            angle:-14,
            shadow:new fabric.Shadow({color:'rgba(0,0,0,.45)',blur:24,offsetY:14}),
            name:'product-img'
        });
        canvas.add(img);
        // Manda o produto para baixo dos textos â€” logo acima das manchas azuis
        const blobBottom = canvas.getObjects().find(o=>o.name==='blue-blob-bottom');
        if(blobBottom) canvas.moveObjectTo(img, canvas.getObjects().indexOf(blobBottom)+1);
        canvas.requestRenderAll();
    });
}

function tplPromoTravel() {
    // â”€â”€ TRAVEL EXPLORER (Blue + Yellow, diagonal slash) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    startTpl('#1d6fa8');

    // Slash branca diagonal dominante (elemento de design)
    canvas.add(mkRect({x:-W*.25,y:-H*.1,w:W*.38,h:H*1.3,fill:'rgba(255,255,255,.18)',angle:22,name:'slash1'}));
    canvas.add(mkRect({x:-W*.13,y:-H*.1,w:W*.35,h:H*1.3,fill:'rgba(255,255,255,.12)',angle:22,name:'slash2'}));

    // Barra amarela pequena
    canvas.add(mkRect({x:W*.08,y:-H*.12,w:18,h:H*1.3,fill:'#eab308',angle:22,name:'yellow-line'}));

    // Logo top-left
    canvas.add(mkTxt('âœˆ LOGO', {sz:14,fw:'900',x:22,y:20,fill:'#fde047'}));
    canvas.add(mkTxt('HERE', {sz:10,fw:'600',x:22,y:37,fill:'rgba(255,255,255,.7)'}));
    // Social top-right
    canvas.add(mkTxt('FOLLOW US NOW', {sz:9,fw:'700',x:W-112,y:22,w:100,fill:'rgba(255,255,255,.6)',align:'right'}));
    canvas.add(mkTxt('f  âœ¦  â—‰  âœ‰', {sz:13,fw:'600',x:W-112,y:33,w:100,fill:'rgba(255,255,255,.85)',align:'right'}));

    // Tagline + TÃ­tulo hierarquizado
    const txY = Math.round(H*.14);
    canvas.add(mkTxt("It's", {sz:Math.round(H*.042),fw:'400',x:Math.round(W*.28),y:txY,fill:'#fde047',font:'Playfair Display'}));
    canvas.add(mkTxt('TIME TO', {sz:Math.round(H*.04),fw:'300',x:Math.round(W*.28)+Math.round(H*.042)*.42,y:txY,fill:'rgba(255,255,255,.9)'}));

    canvas.add(mkTxt('Travel', {sz:Math.round(H*.14),fw:'700',x:Math.round(W*.25),y:Math.round(H*.18),fill:'#ffffff',shadow:new fabric.Shadow({color:'rgba(0,0,0,.35)',blur:16}),font:'Playfair Display'}));
    canvas.add(mkTxt('EXPLORE', {sz:Math.round(H*.12),fw:'900',x:Math.round(W*.25),y:Math.round(H*.31),fill:'#ffffff',shadow:new fabric.Shadow({color:'rgba(0,0,0,.35)',blur:14})}));
    canvas.add(mkTxt('THE WORLD WITH US!', {sz:Math.round(H*.028),fw:'600',x:Math.round(W*.28),y:Math.round(H*.44),fill:'rgba(255,255,255,.8)',letterSpacing:3}));

    // RetÃ¢ngulos de enquadramento decorativos (estilo porta-foto)
    const frX1 = Math.round(W*.22), frY1 = Math.round(H*.5);
    const frW1 = Math.round(W*.52), frH1 = Math.round(H*.22);
    canvas.add(mkRect({x:frX1,y:frY1,w:frW1,h:frH1,fill:'#0f3a5c',rx:6,name:'photo-frame-1'}));
    canvas.add(mkTxt('[ Foto de Destino 1 ]', {sz:13,fw:'700',x:frX1,y:frY1+frH1*.38,w:frW1,fill:'rgba(255,255,255,.4)',align:'center'}));

    const frX2 = Math.round(W*.35), frY2 = Math.round(H*.72);
    const frW2 = Math.round(W*.58), frH2 = Math.round(H*.2);
    canvas.add(mkRect({x:frX2,y:frY2,w:frW2,h:frH2,fill:'#0f3a5c',rx:6,name:'photo-frame-2'}));
    canvas.add(mkTxt('[ Foto de Destino 2 ]', {sz:13,fw:'700',x:frX2,y:frY2+frH2*.38,w:frW2,fill:'rgba(255,255,255,.4)',align:'center'}));

    // Badge desconto
    canvas.add(mkRect({x:Math.round(W*.6),y:Math.round(H*.7),w:160,h:72,fill:'#eab308',rx:8,shadow:new fabric.Shadow({color:'rgba(0,0,0,.2)',blur:12}),name:'disc-badge'}));
    canvas.add(mkTxt('Get Up', {sz:14,fw:'600',x:Math.round(W*.6)+8,y:Math.round(H*.71),w:144,fill:'#fff',align:'center'}));
    canvas.add(mkTxt('50%', {sz:Math.round(H*.09),fw:'900',x:Math.round(W*.6)+8,y:Math.round(H*.72),w:144,fill:'#ffffff',align:'center',shadow:new fabric.Shadow({color:'rgba(0,0,0,.15)',blur:6})}));
    canvas.add(mkTxt('Discount', {sz:14,fw:'700',x:Math.round(W*.6)+8,y:Math.round(H*.7)+56,w:144,fill:'rgba(255,255,255,.9)',align:'center'}));

    // BotÃ£o CTA
    canvas.add(mkRect({x:W*.5-110,y:H-72,w:220,h:44,fill:'rgba(255,255,255,.2)',rx:22,stroke:'#ffffff',strokeWidth:1.5,name:'btn'}));
    canvas.add(mkTxt('â†’  BOOK NOW', {sz:16,fw:'800',x:W*.5-110,y:H-56,w:220,fill:'#ffffff',align:'center'}));

    // RodapÃ©
    canvas.add(mkTxt('609-791-3583', {sz:20,fw:'900',y:H-24,fill:'#ffffff',align:'center',w:W}));
    canvas.add(mkTxt('WWW.YOURWEBSITE.COM', {sz:11,fw:'500',y:H-10,fill:'rgba(255,255,255,.5)',align:'center',w:W}));

    finishTpl();
    // Foto1 (aviÃ£o)
    asyncImg('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=1080&q=80', img => {
        img.set({ scaleX:frW1/img.width, scaleY:frH1/img.height,
            left:frX1, top:frY1, rx:6, ry:6,
            shadow:new fabric.Shadow({color:'rgba(0,0,0,.3)',blur:12}),
            name:'travel-img1'
        });
        const frame1 = canvas.getObjects().find(o=>o.name==='photo-frame-1');
        if(frame1) { const idx = canvas.getObjects().indexOf(frame1); canvas.remove(frame1); canvas.moveObjectTo(img, idx); }
        else canvas.add(img);
        canvas.requestRenderAll();
    });
    // Foto2 (praia/dunas)
    asyncImg('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1080&q=80', img => {
        img.set({ scaleX:frW2/img.width, scaleY:frH2/img.height,
            left:frX2, top:frY2, rx:6, ry:6,
            shadow:new fabric.Shadow({color:'rgba(0,0,0,.3)',blur:12}),
            name:'travel-img2'
        });
        const frame2 = canvas.getObjects().find(o=>o.name==='photo-frame-2');
        if(frame2) { const idx = canvas.getObjects().indexOf(frame2); canvas.remove(frame2); canvas.moveObjectTo(img, idx); }
        else canvas.add(img);
        canvas.requestRenderAll();
    });
}

// â”€â”€â”€ CORPORATE TEMPLATES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function tplCorpKpi() {
    startTpl('#0f172a');
    // Grid decoration
    for (let i=1; i<4; i++) canvas.add(new fabric.Line([W*i/4,0,W*i/4,H],{stroke:'rgba(255,255,255,.025)',selectable:false,name:'grid'+i}));
    canvas.add(mkTxt('RELATÃ“RIO DE DESEMPENHO Q1 2025', { sz:11, fw:'800', y:50, fill:'#475569', name:'tag' }));
    canvas.add(mkTxt('Resultados\ndo Trimestre.', { sz: Math.round(H*.12), fw:'900', y:85, lh:1.1, fill:'#e2e8f0', name:'title' }));
    // Divider
    canvas.add(mkRect({ x:60, y: Math.round(H*.53), w: W-120, h:1, fill:'rgba(255,255,255,.1)', name:'div' }));
    // KPI row
    const kpis = [{v:'â†‘ 34%', l:'Crescimento'},{v:'98%', l:'SatisfaÃ§Ã£o'},{v:'120', l:'Projetos'}];
    kpis.forEach((k, i) => {
        const bx = 60 + i * Math.round((W-120)/3);
        const bw = Math.round((W-120)/3) - 8;
        canvas.add(mkTxt(k.v, { sz: Math.round(H*.06), fw:'900', x: bx, y: Math.round(H*.57), w: bw, align:'center', fill:'#6366f1', name:'kv'+i }));
        canvas.add(mkTxt(k.l, { sz:11, fw:'500', x: bx, y: Math.round(H*.63), w: bw, align:'center', fill:'#64748b', name:'kl'+i }));
    });
    canvas.add(mkTxt('Meta superada em todos os indicadores. ParabÃ©ns ao time.', { sz: Math.round(H*.03), y: Math.round(H*.78), fill:'rgba(255,255,255,.5)', lh:1.5, name:'sub' }));
    finishTpl();
}

function tplCorpLaunch() {
    startTpl('#1e1b4b');
    canvas.add(mkCircle({ x: W*.7, y:-80, r:280, fill:'#818cf8', op:.1 }));
    canvas.add(mkCircle({ x:-60, y: H*.65, r:190, fill:'#6366f1', op:.08 }));
    canvas.add(mkTxt('NOVO PRODUTO', { sz:11, fw:'800', y:55, fill:'#a5b4fc', name:'tag' }));
    canvas.add(mkTxt('Chegou o\nMomento\nde Inovar.', { sz: Math.round(H*.12), fw:'900', y:90, lh:1.05, shadow: new fabric.Shadow({color:'rgba(99,102,241,.3)',blur:18}), name:'title' }));
    canvas.add(mkTxt('Nossa nova soluÃ§Ã£o transforma a gestÃ£o de projetos para o prÃ³ximo nÃ­vel.', { sz: Math.round(H*.034), y: Math.round(H*.7), lh:1.6, fill:'rgba(255,255,255,.8)', name:'sub' }));
    canvas.add(mkRect({ x:60, y: Math.round(H*.83), w:175, h:48, fill:'#6366f1', rx:8, shadow: new fabric.Shadow({color:'rgba(99,102,241,.45)',blur:16,offsetY:6}), name:'btn-bg' }));
    canvas.add(mkTxt('Conhecer Agora', { sz:15, fw:'700', x:60, y: Math.round(H*.83)+14, w:175, fill:'#fff', align:'center', name:'btn-txt' }));
    finishTpl();
}

function tplCorpWebinar() {
    startTpl('#0c1428');
    canvas.add(mkCircle({ x: W-80, y: H*.2, r:230, fill:'#0ea5e9', op:.08 }));
    canvas.add(mkCircle({ x:80, y: H*.85, r:170, fill:'#6366f1', op:.07 }));
    // Live badge
    canvas.add(mkRect({ x:60, y:60, w:80, h:28, fill:'#ef4444', rx:14, name:'live-bg' }));
    canvas.add(mkTxt('â— AO VIVO', { sz:10, fw:'800', x:60, y:67, w:80, fill:'#fff', align:'center', name:'live' }));
    canvas.add(mkTxt('NOSSA EQUIPE', { sz:11, fw:'800', x:60, y:58, fill:'transparent', name:'hidden-space' }));
    canvas.add(mkTxt('Webinar\nExecutivo:', { sz: Math.round(H*.11), fw:'900', y:110, lh:1.1, fill:'#f8fafc', name:'title' }));
    canvas.add(mkTxt('GestÃ£o de Alto Impacto em 2025', { sz: Math.round(H*.048), fw:'700', y: Math.round(H*.48), fill:'#38bdf8', name:'sub1' }));
    canvas.add(mkTxt('ðŸ“…  15 de Agosto  â€¢  19h00  â€¢  Online e Gratuito', { sz:14, fw:'500', y: Math.round(H*.62), fill:'#94a3b8', name:'info' }));
    canvas.add(mkRect({ x:60, y: Math.round(H*.78), w:200, h:48, fill:'#0ea5e9', rx:24, shadow: new fabric.Shadow({color:'rgba(14,165,233,.45)',blur:16,offsetY:6}), name:'btn-bg' }));
    canvas.add(mkTxt('Garantir Minha Vaga', { sz:14, fw:'700', x:60, y: Math.round(H*.78)+14, w:200, fill:'#fff', align:'center', name:'btn-txt' }));
    finishTpl();
}

/* â”€â”€ NEW PREMIUM TEMPLATES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

function tplMegaSaleBlue() { tplPromoMegaSale(); }
function tplBfWeekend() { tplPromoWeekend(); }
function tplTravelExplorer() { tplPromoTravel(); }
function tplKpiReport() { tplCorpKpi(); }
function tplLancamentoSaas() { tplCorpLaunch(); }
function tplWebinarCard() { tplCorpWebinar(); }
function tplDigitalMktg() { tplDigitalMktgDark(); }

function tplBfCamera() {
    startTpl('linear-gradient(160deg,#1a0a00 0%,#4a1c00 60%,#1a0a00 100%)');
    canvas.add(mkCircle({ x:W*.6, y:H*.4, r:W*.35, fill: new fabric.Gradient({ type:'radial', coords:{x1:W*.5,y1:H*.5,x2:W*.5,y2:H*.5,r1:0,r2:W*.35}, colorStops:[{offset:0,color:'rgba(217,119,6,.3)'},{offset:1,color:'transparent'}]}) }));
    canvas.add(mkTxt('BLACK FRIDAY', { sz:12, fw:'700', x:25, y:40, fill:'#d97706', charSpacing:400 }));
    canvas.add(mkTxt('CÃ‚MERAS &\nFOTOGRAFIA', { sz:Math.round(H*.08), fw:'800', x:25, y:65, fill:'rgba(255,255,255,.5)', lh:1.1 }));
    canvas.add(mkTxt('-60%', { sz:Math.round(H*.22), fw:'900', x:25, y:120, fill:'#fbbf24', shadow:'0 10px 20px rgba(0,0,0,.5)' }));
    canvas.add(mkTxt('DE DESCONTO', { sz:14, fw:'700', x:25, y:120+Math.round(H*.18), fill:'rgba(255,255,255,.4)' }));
    
    // Icon decoration
    canvas.add(mkCircle({ x:60, y:H-140, r:40, fill:'rgba(255,255,255,.05)', stroke:'#d97706', strokeWidth:2 }));
    canvas.add(mkTxt('ðŸ“·', { sz:32, x:38, y:H-144 }));
    
    canvas.add(mkRect({ x:25, y:H-80, w:W-50, h:50, fill:'transparent', rx:8, stroke:'#d97706', strokeWidth:2 }));
    canvas.add(mkTxt('APROVEITE A OFERTA AGORA', { sz:14, fw:'800', x:25, y:H-64, w:W-50, align:'center', fill:'#d97706' }));
    finishTpl();
}

function tplBusinessOrange() {
    startTpl('linear-gradient(135deg,#ea580c 0%,#d97706 100%)');
    canvas.add(mkTxt('OFERTA ESPECIAL', { sz:12, fw:'800', x:30, y:H*.3, fill:'rgba(255,255,255,.6)', charSpacing:300 }));
    canvas.add(mkTxt('Transforme\nseu Negócio', { sz:Math.round(H*.15), fw:'900', x:30, y:H*.34, fill:'#fff', lh:1.0 }));
    canvas.add(mkRect({ x:30, y:H*.58, w:100, h:4, fill:'rgba(255,255,255,.4)' }));
    canvas.add(mkTxt('Consultoria completa com especialistas\npara alavancar seus resultados em 2025.', { sz:16, fw:'400', x:30, y:H*.62, fill:'rgba(255,255,255,.8)', lh:1.5 }));
    canvas.add(mkRect({ x:30, y:H*.78, w:220, h:54, fill:'#fff', rx:10 }));
    canvas.add(mkTxt('SAIBA MAIS →', { sz:18, fw:'900', x:30, y:H*.78+16, w:220, align:'center', fill:'#ea580c' }));
    finishTpl();
}

function tplBfFone() {
    startTpl('#0a0a0a');
    canvas.add(mkRect({ x:0, y:0, w:W, h:5, fill:'#eab308' }));
    canvas.add(mkTxt('BLACK FRIDAY', { sz:12, fw:'700', x:25, y:40, fill:'#eab308', charSpacing:400 }));
    canvas.add(mkTxt('FONES & ÃUDIO', { sz:16, fw:'800', x:25, y:65, fill:'rgba(255,255,255,.4)', charSpacing:100 }));
    canvas.add(mkTxt('80%', { sz:Math.round(H*.24), fw:'900', x:25, y:100, fill:'#fde047' }));
    canvas.add(mkTxt('OFF', { sz:28, fw:'900', x:Math.round(W*.65), y:210, fill:'rgba(255,255,255,.5)' }));
    
    canvas.add(mkCircle({ x:W/2, y:H/2+40, r:80, fill:'rgba(255,255,255,.03)', stroke:'rgba(234,179,8,.3)', strokeWidth:1 }));
    canvas.add(mkTxt('ðŸŽ§', { sz:70, x:W/2-35, y:H/2 }));

    canvas.add(mkRect({ x:25, y:H-90, w:W-50, h:54, fill:'#eab308', rx:10 }));
    canvas.add(mkTxt('COMPRAR AGORA', { sz:16, fw:'900', x:25, y:H-72, w:W-50, align:'center', fill:'#000' }));
    finishTpl();
}

function tplStartupTalkshow() {
    startTpl('linear-gradient(160deg,#0a1f1a 0%,#0d4a3e 60%,#0a2010 100%)');
    canvas.add(mkRect({ x:20, y:20, w:W-40, h:H-40, fill:'transparent', stroke:'rgba(212,175,55,.4)', strokeWidth:2, rx:20 }));
    canvas.add(mkTxt('STARTUP TALK', { sz:12, fw:'700', y:60, fill:'#d4af37', align:'center', w:W, charSpacing:400 }));
    canvas.add(mkTxt('InovaÃ§Ã£o &\nNegÃ³cios', { sz:Math.round(H*.14), fw:'900', y:90, fill:'#fff', align:'center', w:W, lh:1.1 }));
    canvas.add(mkCircle({ x:W/2, y:H/2+20, r:45, fill:'rgba(212,175,55,.1)', stroke:'rgba(212,175,55,.3)', strokeWidth:1.5 }));
    canvas.add(mkTxt('ðŸŽ™ï¸', { sz:40, x:W/2-20, y:H/2-5 }));
    canvas.add(mkRect({ x:60, y:H-100, w:W-120, h:45, fill:'rgba(212,175,55,.15)', stroke:'rgba(212,175,55,.3)', rx:10 }));
    canvas.add(mkTxt('EPISÃ“DIO AO VIVO', { sz:14, fw:'700', y:H-85, fill:'#d4af37', align:'center', w:W }));
    finishTpl();
}

function tplHeadlineDark() {
    startTpl('#0a0a0a');
    // Cinematic top bar accent
    canvas.add(mkRect({ x:0, y:0, w:W, h:5, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W,y2:0}, colorStops:[{offset:0,color:'#374151'},{offset:.5,color:'#9ca3af'},{offset:1,color:'#374151'}]}) }));
    // Vertical accent line left
    canvas.add(mkRect({ x:50, y:60, w:4, h:H*.45, fill:'rgba(255,255,255,.15)', rx:2 }));
    // Large headline text stacked
    canvas.add(mkTxt('THE', { sz:Math.round(H*.08), fw:'300', x:70, y:65, fill:'rgba(255,255,255,.35)', charSpacing:800 }));
    canvas.add(mkTxt('HEADLINE', { sz:Math.round(H*.16), fw:'900', x:50, y:115, fill:'#ffffff', shadow: new fabric.Shadow({color:'rgba(255,255,255,.1)',blur:30}), charSpacing:100 }));
    canvas.add(mkTxt('GOES HERE', { sz:Math.round(H*.1), fw:'800', x:50, y:115+Math.round(H*.15), fill:'rgba(255,255,255,.55)', charSpacing:200 }));
    // Horizontal rule
    canvas.add(mkRect({ x:50, y:Math.round(H*.52), w:W-100, h:1, fill:'rgba(255,255,255,.12)' }));
    // Body text
    canvas.add(mkTxt('Subtítulo descritivo ou chamada de ação. Personalize este texto para o seu banner.', { sz:Math.round(H*.033), x:50, y:Math.round(H*.55), w:W*.7, fill:'rgba(255,255,255,.5)', lh:1.7 }));
    // CTA button — ghost style
    canvas.add(mkRect({ x:50, y:Math.round(H*.8), w:180, h:48, fill:'transparent', rx:4, stroke:'rgba(255,255,255,.4)', strokeWidth:1.5 }));
    canvas.add(mkTxt('SAIBA MAIS', { sz:13, fw:'700', x:50, y:Math.round(H*.8)+16, w:180, fill:'rgba(255,255,255,.8)', align:'center', charSpacing:300 }));
    // Bottom right corner accent
    canvas.add(mkCircle({ x:W+60, y:H+60, r:200, fill:'rgba(55,65,81,.25)', op:1 }));
    finishTpl();
}

function tplTravelWorld() {
    startTpl('#2c1a0e');
    // Warm earthy radial glow
    const warmGlow = new fabric.Gradient({ type:'radial', coords:{x1:W*.5,y1:H*.4,x2:W*.5,y2:H*.4,r1:0,r2:W*.6},
        colorStops:[{offset:0,color:'rgba(212,165,116,.25)'},{offset:1,color:'transparent'}]});
    canvas.add(mkRect({ x:0,y:0,w:W,h:H, fill:warmGlow, name:'glow-bg' }));
    // Decorative circle elements — compass-like
    canvas.add(mkCircle({ x:W*.75, y:H*.3, r:130, fill:'transparent', stroke:'rgba(212,165,116,.18)', strokeWidth:2, op:1 }));
    canvas.add(mkCircle({ x:W*.75, y:H*.3, r:90,  fill:'transparent', stroke:'rgba(212,165,116,.12)', strokeWidth:1, op:1 }));
    canvas.add(mkCircle({ x:W*.75, y:H*.3, r:50,  fill:'rgba(212,165,116,.08)', stroke:'rgba(212,165,116,.2)', strokeWidth:1.5, op:1 }));
    // Logo / brand top
    canvas.add(mkTxt('✈ TRAVEL WORLD', { sz:11, fw:'700', x:40, y:32, fill:'#d4a574', charSpacing:400 }));
    // Main copy
    canvas.add(mkTxt('Explore', { sz:Math.round(H*.06), fw:'300', x:40, y:72, fill:'rgba(255,255,255,.7)', font:'Playfair Display' }));
    canvas.add(mkTxt('O Mundo', { sz:Math.round(H*.155), fw:'900', x:40, y:108, fill:'#ffffff', lh:1.0, shadow: new fabric.Shadow({color:'rgba(0,0,0,.5)',blur:18}) }));
    canvas.add(mkTxt('com a gente', { sz:Math.round(H*.065), fw:'400', x:40, y:108+Math.round(H*.145), fill:'#d4a574', font:'Playfair Display' }));
    // Horizontal separator
    canvas.add(mkRect({ x:40, y:Math.round(H*.52), w:120, h:2, fill:'#d4a574', rx:1 }));
    // Destinations row
    const dests = ['Paris', 'Tóquio', 'Cairo', 'Lisboa'];
    dests.forEach((d, i) => {
        canvas.add(mkTxt(d, { sz:11, fw:'600', x:40+i*Math.round((W-80)/4), y:Math.round(H*.55), fill:'rgba(255,255,255,.55)' }));
    });
    // Description
    canvas.add(mkTxt('Pacotes exclusivos para os destinos mais\nsonhados do mundo. Reserve agora.', { sz:Math.round(H*.032), x:40, y:Math.round(H*.62), w:W*.55, fill:'rgba(255,255,255,.45)', lh:1.6 }));
    // CTA
    canvas.add(mkRect({ x:40, y:Math.round(H*.8), w:185, h:48, fill:'#d4a574', rx:24, shadow: new fabric.Shadow({color:'rgba(212,165,116,.4)',blur:14,offsetY:6}) }));
    canvas.add(mkTxt('RESERVAR VIAGEM', { sz:13, fw:'800', x:40, y:Math.round(H*.8)+15, w:185, fill:'#2c1a0e', align:'center' }));
    // Footer
    canvas.add(mkTxt('www.seusite.com.br  •  contato@viagem.com', { sz:10, fw:'500', y:H-18, fill:'rgba(255,255,255,.3)', align:'center', w:W }));
    finishTpl();
}

function tplFashionPop() {
    startTpl('linear-gradient(160deg,#1a0014 0%,#6b0057 50%,#1a000a 100%)');
    canvas.add(mkCircle({ x:W/2, y:H/2, r:W*.4, fill: new fabric.Gradient({ type:'radial', coords:{x1:W*.5,y1:H*.5,x2:W*.5,y2:H*.5,r1:0,r2:W*.4}, colorStops:[{offset:0,color:'rgba(236,72,153,.2)'},{offset:1,color:'transparent'}]}) }));
    canvas.add(mkRect({ x:W/2-1, y:0, w:2, h:H, fill:'rgba(236,72,153,.4)' }));
    canvas.add(mkTxt('NEW COLLECTION', { sz:11, fw:'700', y:50, fill:'rgba(255,255,255,.4)', align:'center', w:W, charSpacing:500 }));
    canvas.add(mkTxt('FASHION', { sz:Math.round(H*.18), fw:'900', y:80, fill:'#fff', align:'center', w:W }));
    canvas.add(mkTxt('POP', { sz:Math.round(H*.18), fw:'900', y:80+Math.round(H*.15), fill:'#f9a8d4', align:'center', w:W }));
    canvas.add(mkTxt('SUMMER • SPRING', { sz:12, fw:'500', y:280, fill:'rgba(255,255,255,.4)', align:'center', w:W, charSpacing:300 }));
    canvas.add(mkRect({ x:W/2-100, y:H-90, w:200, h:50, fill:'#ec4899', rx:10 }));
    canvas.add(mkTxt('EXPLORAR COLEÇÃO', { sz:14, fw:'800', y:H-72, fill:'#fff', align:'center', w:W }));
    finishTpl();
}

function tplSplitGeo() {
    startTpl('#fff');
    canvas.add(mkRect({ x:0, y:0, w:W*.5, h:H, fill:'#1e1b4b' }));
    canvas.add(new fabric.Polygon([{x:W*.45,y:0},{x:W*.7,y:0},{x:W*.6,y:H},{x:W*.35,y:H}], { fill:'#4f46e5', selectable:false }));
    canvas.add(mkTxt('SERVIÇOS', { sz:10, fw:'700', x:30, y:H*.3, fill:'#a5b4fc', charSpacing:400 }));
    canvas.add(mkTxt('Design\nPremium', { sz:Math.round(H*.14), fw:'900', x:30, y:H*.34, fill:'#fff', lh:1.0 }));
    canvas.add(mkRect({ x:30, y:H*.55, w:50, h:4, fill:'#7c5cff' }));
    canvas.add(mkTxt('Sua marca com\nexcelência visual.', { sz:14, color:'rgba(255,255,255,.6)', x:30, y:H*.58, lh:1.5 }));
    
    const services = ['Social Media', 'Branding', 'Web Design', 'Ads Graphics'];
    services.forEach((s,i) => {
        canvas.add(mkRect({ x:W*.58, y:H*.3 + i*45, w:W*.35, h:36, fill:'#f1f5f9', rx:6 }));
        canvas.add(mkTxt(s, { sz:14, fw:'800', x:W*.62, y:H*.31 + i*45, fill:'#1e293b' }));
    });
    finishTpl();
}

function tplNeonDark() {
    startTpl('#030307');
    canvas.add(mkCircle({ x:W/2, y:H*.3, r:100, fill:'rgba(124,92,255,.4)', op:.5, name:'glow' }));
    canvas.getObjects().find(o=>o.name==='glow').set('shadow', new fabric.Shadow({color:'#7c5cff',blur:60}));
    
    canvas.add(mkTxt('◆ STUDIO ◆', { sz:12, fw:'700', y:60, fill:'#7c5cff', align:'center', w:W, charSpacing:500 }));
    canvas.add(mkTxt('NEON\nDARK', { sz:Math.round(H*.16), fw:'900', y:90, align:'center', w:W, lh:0.95, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W,y2:0}, colorStops:[{offset:0,color:'#7c5cff'},{offset:1,color:'#00d4ff'}]}) }));
    canvas.add(mkRect({ x:W*.15, y:230, w:W*.7, h:1, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W*.7,y2:0}, colorStops:[{offset:0,color:'transparent'},{offset:.5,color:'#7c5cff'},{offset:1,color:'transparent'}]}) }));
    canvas.add(mkTxt('creative • studio • design', { sz:11, fw:'500', y:245, fill:'rgba(255,255,255,.3)', align:'center', w:W, charSpacing:300 }));
    
    canvas.add(mkRect({ x:60, y:H-100, w:W-120, h:48, fill:'transparent', stroke:'#7c5cff', strokeWidth:1.5, rx:10 }));
    canvas.add(mkTxt('ENTRAR EM CONTATO', { sz:14, fw:'800', y:H-84, fill:'#7c5cff', align:'center', w:W, charSpacing:100 }));
    finishTpl();
}

function tplAgenciaSquare() {
    startTpl('linear-gradient(160deg,#0d1117 0%,#162d40 50%,#0d2234 100%)');
    canvas.add(mkRect({ x:0, y:0, w:5, h:H, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:0,y2:H}, colorStops:[{offset:0,color:'#00d4ff'},{offset:1,color:'#7c5cff'}]}) }));
    canvas.add(mkTxt('MARKETING DIGITAL', { sz:10, fw:'700', x:30, y:40, fill:'#00d4ff', charSpacing:400 }));
    canvas.add(mkTxt('Sua marca\nno ', { sz:Math.round(H*.12), fw:'900', x:30, y:70, fill:'#fff', lh:1.1 }));
    canvas.add(mkTxt('próximo\nnível', { sz:Math.round(H*.12), fw:'900', x:30, y:70+Math.round(H*.12), fill:'#00d4ff', lh:1.1 }));
    canvas.add(mkRect({ x:30, y:280, w:100, h:2, fill:'rgba(0,212,255,.3)' }));
    canvas.add(mkTxt('Strategy • Creative • Performance', { sz:11, fw:'600', x:30, y:295, fill:'rgba(255,255,255,.4)', charSpacing:100 }));
    
    canvas.add(mkRect({ x:30, y:H-80, w:120, h:40, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:120,y2:0}, colorStops:[{offset:0,color:'#00d4ff'},{offset:1,color:'#7c5cff'}]}), rx:6 }));
    canvas.add(mkTxt('CONTATO', { sz:12, fw:'900', x:30, y:H-68, w:120, align:'center', fill:'#fff' }));
    finishTpl();
}

function tplNgoNeon() {
    startTpl('linear-gradient(160deg,#030312 0%,#1e1b4b 60%,#030312 100%)');
    canvas.add(mkCircle({ x:W/2, y:H*.35, r:120, fill:'rgba(99,102,241,.15)', name:'glow' }));
    canvas.getObjects().find(o=>o.name==='glow').set('shadow', new fabric.Shadow({color:'#6366f1',blur:60}));
    
    canvas.add(mkCircle({ x:W/2, y:60, r:30, fill:'rgba(99,102,241,.1)', stroke:'rgba(99,102,241,.4)', strokeWidth:1.5 }));
    canvas.add(mkTxt('â¤ï¸', { sz:28, x:W/2-21, y:45 }));
    
    canvas.add(mkTxt('SOLIDARIEDADE', { sz:12, fw:'700', y:110, fill:'#818cf8', align:'center', w:W, charSpacing:400 }));
    canvas.add(mkTxt('Juntos\nPodemos\nMais', { sz:Math.round(H*.14), fw:'900', y:140, align:'center', w:W, lh:1.0, fill:'#fff' }));
    canvas.add(mkRect({ x:W*.2, y:H*.55, w:W*.6, h:1, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W*.6,y2:0}, colorStops:[{offset:0,color:'transparent'},{offset:.5,color:'#6366f1'},{offset:1,color:'transparent'}]}) }));
    canvas.add(mkTxt('Transformando vidas atravÃ©s da aÃ§Ã£o coletiva', { sz:13, fw:'400', y:H*.58, fill:'rgba(255,255,255,.4)', align:'center', w:W }));
    
    canvas.add(mkRect({ x:60, y:H-90, w:W-120, h:50, fill:'#4f46e5', rx:10 }));
    canvas.add(mkTxt('SEJA VOLUNTÃRIO', { sz:16, fw:'800', y:H-72, fill:'#fff', align:'center', w:W }));
    finishTpl();
}

function tplSocialImpact() {
    startTpl('linear-gradient(135deg,#052e16 0%,#064e3b 100%)');
    canvas.add(mkCircle({ x:50, y:50, r:22, fill:'rgba(16,185,129,.1)', stroke:'rgba(16,185,129,.4)', strokeWidth:1.5 }));
    canvas.add(mkTxt('ðŸŒ¿', { sz:20, x:40, y:40 }));
    canvas.add(mkTxt('IMPACTO SOCIAL', { sz:11, fw:'700', x:85, y:45, fill:'#34d399', charSpacing:300 }));
    
    canvas.add(mkTxt('Cada ação\ntransforma\nvidas', { sz:Math.round(H*.14), fw:'900', x:40, y:120, fill:'#fff', lh:1.1 }));
    canvas.add(mkTxt('vidas', { sz:Math.round(H*.14), fw:'900', x:40, y:120+Math.round(H*.22), fill:'#6ee7b7', lh:1.1 }));
    
    canvas.add(mkTxt('Sua participação faz a diferença real\nna vida de quem mais precisa.', { sz:16, fw:'400', x:40, y:H*.6, fill:'rgba(255,255,255,.4)', lh:1.5 }));
    canvas.add(mkRect({ x:40, y:H-100, w:180, h:50, fill:'#10b981', rx:8 }));
    canvas.add(mkTxt('APOIE AGORA', { sz:16, fw:'800', x:40, y:H-82, w:180, align:'center', fill:'#fff' }));
    finishTpl();
}

function tplCampanhaDoacao() {
    startTpl('#fff');
    canvas.add(mkRect({ x:0, y:0, w:W, h:6, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:W,y2:0}, colorStops:[{offset:0,color:'#f59e0b'},{offset:1,color:'#ef4444'}]}) }));
    canvas.add(mkRect({ x:25, y:40, w:160, h:28, fill:'#fef3c7', rx:6 }));
    canvas.add(mkTxt('CAMPANHA URGENTE', { sz:11, fw:'800', x:25, y:48, w:160, align:'center', fill:'#d97706', charSpacing:100 }));
    
    canvas.add(mkTxt('Sua doaÃ§Ã£o\nsalva vidas', { sz:Math.round(H*.12), fw:'900', x:25, y:85, fill:'#1e293b', lh:1.2 }));
    canvas.add(mkRect({ x:25, y:210, w:W-50, h:1, fill:'#f1f5f9' }));
    
    canvas.add(mkTxt('Meta: R$ 50.000', { sz:14, fw:'600', x:25, y:230, fill:'#64748b' }));
    canvas.add(mkTxt('Arrecadado: R$ 32.400', { sz:14, fw:'700', x:25, y:250, fill:'#10b981' }));
    
    canvas.add(mkRect({ x:25, y:280, w:W-50, h:10, fill:'#f1f5f9', rx:5 }));
    canvas.add(mkRect({ x:25, y:280, w:(W-50)*.64, h:10, fill: new fabric.Gradient({ type:'linear', coords:{x1:0,y1:0,x2:(W-50)*.64,y2:0}, colorStops:[{offset:0,color:'#10b981'},{offset:1,color:'#34d399'}]}), rx:5 }));
    canvas.add(mkTxt('64% da meta alcanÃ§ada', { sz:11, fw:'500', x:25, y:295, fill:'#94a3b8' }));
    
    canvas.add(mkRect({ x:25, y:H-80, w:W-50, h:50, fill:'#ef4444', rx:10 }));
    canvas.add(mkTxt('QUERO CONTRIBUIR â¤ï¸', { sz:16, fw:'800', x:25, y:H-62, w:W-50, align:'center', fill:'#fff' }));
    finishTpl();
}

function tplVoluntariado() {
    startTpl('linear-gradient(160deg,#1a0a2e 0%,#3b1f5e 50%,#1a0a2e 100%)');
    canvas.add(mkTxt('SEJA VOLUNTÃRIO', { sz:12, fw:'700', y:60, fill:'#a855f7', align:'center', w:W, charSpacing:400 }));
    
    const users = ['ðŸ‘¤','ðŸ‘¤','ðŸ‘¤'];
    users.forEach((u,i) => {
        canvas.add(mkCircle({ x:W/2 - 40 + i*40, y:120, r:25, fill:'rgba(168,85,247,.1)', stroke:'rgba(168,85,247,.4)', strokeWidth:1 }));
        canvas.add(mkTxt(u, { sz:18, x:W/2 - 40 + i*40 - 9, y:110 }));
    });
    
    canvas.add(mkTxt('FaÃ§a parte\nda mudanÃ§a', { sz:Math.round(H*.14), fw:'900', y:170, align:'center', w:W, lh:1.1, fill:'#fff' }));
    canvas.add(mkTxt('Doe seu tempo, multiplique o impacto', { sz:14, fw:'400', y:260, fill:'rgba(255,255,255,.4)', align:'center', w:W }));
    
    canvas.add(mkRect({ x:60, y:H-90, w:W-120, h:50, fill:'rgba(168,85,247,.8)', rx:10 }));
    canvas.add(mkTxt('QUERO PARTICIPAR', { sz:16, fw:'800', y:H-72, fill:'#fff', align:'center', w:W }));
    finishTpl();
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// ADD ELEMENTS
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function addText(type) {
    const cfgs = {
        heading: { text:'TÃ­tulo Principal', sz: Math.round(H*.09), fw:'800', fill:'#111827' },
        sub:     { text:'SubtÃ­tulo do Banner', sz: Math.round(H*.04), fw:'600', fill:'#374151' },
        body:    { text:'Adicione aqui o texto do banner. Clique para editar e personalizar.', sz: Math.round(H*.03), fw:'400', fill:'#6b7280' },
        badge:   { text:'NOVO', sz: Math.round(H*.025), fw:'800', fill:'#ffffff' },
    };
    const c = cfgs[type];
    const t = new fabric.Textbox(c.text, {
        fontFamily: gf(), fontSize: c.sz, fill: c.fill, fontWeight: c.fw,
        left: 80, top: 100, width: W - 160, textAlign: 'left', lineHeight: 1.3,
        selectable: true, name: type + '_text',
    });
    canvas.add(t);
    canvas.setActiveObject(t);
    canvas.requestRenderAll();
}

function addShape(type) {
    const cx = W/2, cy = H/2;
    let obj;
    if (type === 'rect')         obj = new fabric.Rect({ left:cx-120, top:cy-80, width:240, height:160, fill:'#6366f1', rx:10, selectable:true, name:'rect' });
    else if (type === 'circle')  obj = new fabric.Circle({ left:cx-100, top:cy-100, radius:100, fill:'#10b981', selectable:true, name:'circle' });
    else if (type === 'line')    obj = new fabric.Line([cx-160,cy,cx+160,cy], { stroke:'#e2e8f0', strokeWidth:4, selectable:true, name:'line', strokeLineCap:'round' });
    else if (type === 'triangle') obj = new fabric.Triangle({ left:cx-90, top:cy-100, width:180, height:200, fill:'#f59e0b', selectable:true, name:'triangle' });
    else if (type === 'star') {
        // Create star using polygon points
        const pts = [];
        for (let i=0; i<10; i++) {
            const r = i%2===0 ? 90 : 38;
            const a = Math.PI*2*(i/10) - Math.PI/2;
            pts.push({ x: cx + r*Math.cos(a), y: cy + r*Math.sin(a) });
        }
        obj = new fabric.Polygon(pts, { fill:'#f59e0b', selectable:true, name:'star' });
    }
    else if (type === 'badge_pill') {
        const group = new fabric.Group([
            new fabric.Rect({ width:200, height:46, fill:'#ef4444', rx:23, left:-100, top:-23 }),
            new fabric.Text('PROMOÃ‡ÃƒO', { fontSize:16, fontWeight:'800', fill:'#fff', left:-100, top:-10, width:200, textAlign:'center', fontFamily:'Inter' }),
        ], { left:cx-100, top:cy-23, selectable:true, name:'badge_pill' });
        canvas.add(group); canvas.setActiveObject(group); canvas.requestRenderAll(); return;
    }
    if (obj) { canvas.add(obj); canvas.setActiveObject(obj); canvas.requestRenderAll(); }
}

function addDeco(type) {
    const cx = W/2, cy = H/2;
    let obj;
    if (type === 'hr_thick') {
        obj = new fabric.Rect({ left:60, top:cy, width:W-120, height:6, fill:'#6366f1', rx:3, selectable:true, name:'hr_thick' });
    } else if (type === 'divider_dots') {
        const grp = [];
        for(let i=0; i<9; i++) grp.push(new fabric.Circle({ left:i*24, top:0, radius:4, fill:'#94a3b8' }));
        obj = new fabric.Group(grp, { left:cx-108, top:cy, selectable:true, name:'dots' });
    } else if (type === 'accent_bar') {
        obj = new fabric.Rect({ left:60, top:60, width:6, height:80, fill:'#6366f1', rx:3, selectable:true, name:'accent_bar' });
    } else if (type === 'corner_tag') {
        obj = new fabric.Group([
            new fabric.Triangle({ width:80, height:80, fill:'#ef4444', left:0, top:0 }),
            new fabric.Text('NEW', { fontSize:11, fontWeight:'800', fill:'#fff', left:4, top:8, angle:0, fontFamily:'Inter' }),
        ], { left:0, top:0, selectable:true, name:'corner_tag' });
    }
    if (obj) { canvas.add(obj); canvas.setActiveObject(obj); canvas.requestRenderAll(); pushHistory(); }
}

function setBgSolid() {
    const c = document.getElementById('bg-color-input').value;
    canvas.setBackgroundColor(c, () => { canvas.requestRenderAll(); pushHistory(); });
}
function setBgGradient(variant) {
    const gradients = {
        default: [{offset:0,color:'#1e1b4b'},{offset:1,color:'#6366f1'}],
        sunset:  [{offset:0,color:'#7f1d1d'},{offset:.5,color:'#d97706'},{offset:1,color:'#fde047'}],
        ocean:   [{offset:0,color:'#0c4a6e'},{offset:1,color:'#0ea5e9'}],
        forest:  [{offset:0,color:'#052e16'},{offset:1,color:'#16a34a'}],
        dark:    [{offset:0,color:'#0f172a'},{offset:1,color:'#1e293b'}],
    };
    const stops = gradients[variant] || gradients.default;
    const grad = new fabric.Gradient({ type:'linear', gradientUnits:'pixels', coords:{x1:0,y1:0,x2:W,y2:H}, colorStops: stops });
    canvas.setBackgroundColor(grad, () => { canvas.requestRenderAll(); pushHistory(); });
}
function updateBg(c) { canvas.setBackgroundColor(c, () => { canvas.requestRenderAll(); pushHistory(); }); }
function updateGlobalFont(f) {
    canvas.getObjects().forEach(o => {
        if (o.type === 'textbox' || o.type === 'text') o.set('fontFamily', f);
    });
    canvas.requestRenderAll(); pushHistory();
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// IMAGES
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const STOCK = [
    'https://images.unsplash.com/photo-1542838132-92c53300491e?w=280&q=70',
    'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=280&q=70',
    'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?w=280&q=70',
    'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=280&q=70',
    'https://images.unsplash.com/photo-1551434678-e076c223a692?w=280&q=70',
    'https://images.unsplash.com/photo-1557683316-973673baf926?w=280&q=70',
    'https://images.unsplash.com/photo-1497366216548-37526070297c?w=280&q=70',
    'https://images.unsplash.com/photo-1531206715517-5c0ba140b4b8?w=280&q=70',
    'https://images.unsplash.com/photo-1593113589914-07599019ddda?w=280&q=70',
    'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=280&q=70',
];
const STOCK_HD = STOCK.map(u => u.replace('w=280','w=1080').replace('q=70','q=80'));

function renderStockGallery() {
    const g = document.getElementById('stock-gal');
    g.innerHTML = '';
    STOCK.forEach((u, i) => {
        const img = document.createElement('img');
        img.src = u; img.className = 'img-thumb';
        img.loading = 'lazy';
        img.onclick = () => addImageToCanvas(STOCK_HD[i]);
        g.appendChild(img);
    });
}

function addImageToCanvas(url) {
    showNotif('Carregando imagem...', '');
    fabric.Image.fromURL(url, img => {
        if (!img) { showNotif('Falha ao carregar imagem.','error'); return; }
        const scale = Math.min((W * .6) / img.width, (H * .6) / img.height);
        img.set({ left: W/2 - img.width*scale/2, top: H/2 - img.height*scale/2, scaleX:scale, scaleY:scale, selectable:true, name:'image' });
        canvas.add(img); canvas.setActiveObject(img); canvas.requestRenderAll();
        showNotif('Imagem adicionada!','success');
    }, { crossOrigin: 'anonymous' });
}

function handleUpload(input) {
    const file = input.files[0]; if (!file) return;
    const fd = new FormData(); fd.append('image', file); fd.append('_token', CSRF);
    setSaveStatus('saving');
    fetch(UPLOAD_URL, { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                addImageToCanvas(data.url);
                const g = document.getElementById('upload-gal');
                const img = document.createElement('img'); img.src = data.url; img.className = 'img-thumb';
                img.onclick = () => addImageToCanvas(data.url);
                g.prepend(img);
                showNotif('Imagem enviada!','success');
            } else showNotif('Erro no upload.','error');
            setSaveStatus('saved');
        })
        .catch(() => { showNotif('Falha no upload.','error'); setSaveStatus('saved'); });
    input.value = '';
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// LAYERS
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function refreshLayers() {
    const list = document.getElementById('layers-list');
    if (!list) return;
    const objs = canvas.getObjects().slice().reverse();
    list.innerHTML = '';
    
    if (!objs.length) { 
        list.innerHTML = '<div style="color:#475569;font-size:12px;text-align:center;padding:20px;opacity:.6">Nenhuma camada no momento</div>'; 
        return; 
    }
    
    const icons = { textbox:'fas fa-font', text:'fas fa-font', rect:'fas fa-square', circle:'fas fa-circle', image:'fas fa-image', line:'fas fa-minus', triangle:'fas fa-play', group:'fas fa-object-group', polygon:'fas fa-draw-polygon' };
    
    objs.forEach((obj, i) => {
        const realIdx = objs.length - 1 - i;
        const isActive = canvas.getActiveObjects().includes(obj);
        const div = document.createElement('div');
        div.className = 'layer-item' + (isActive ? ' active' : '');
        
        const isLocked = !obj.selectable;
        
        div.innerHTML = `
            <i class="li-icon ${icons[obj.type]||'fas fa-shapes'}"></i>
            <span class="li-name">${obj.name || obj.type}</span>
            <i class="li-act fas ${isLocked ? 'fa-lock' : 'fa-unlock'}" title="Bloquear/Desbloquear" onclick="event.stopPropagation();toggleLock(${realIdx})"></i>
            <i class="li-act fas ${obj.visible===false?'fa-eye-slash':'fa-eye'}" title="Visibilidade" onclick="event.stopPropagation();toggleVis(${realIdx})"></i>
            <i class="li-act fas fa-trash" title="Excluir" onclick="event.stopPropagation();delLayer(${realIdx})"></i>
        `;
        
        // Drag and drop for reordering
        div.draggable = true;
        div.ondragstart = (e) => { e.dataTransfer.setData('text/plain', realIdx.toString()); div.style.opacity = '0.4'; };
        div.ondragend = (e) => { div.style.opacity = '1'; };
        div.ondragover = (e) => { e.preventDefault(); div.style.borderTop = '2px solid #6366f1'; };
        div.ondragleave = (e) => { div.style.borderTop = '1px solid transparent'; };
        div.ondrop = (e) => {
            e.preventDefault();
            div.style.borderTop = '1px solid transparent';
            const fromIdx = parseInt(e.dataTransfer.getData('text/plain'));
            if(isNaN(fromIdx) || fromIdx === realIdx) return;
            const targetObj = canvas.item(fromIdx);
            if(targetObj) {
                canvas.moveTo(targetObj, realIdx);
                canvas.requestRenderAll();
                refreshLayers();
                pushHistory();
            }
        };

        div.onclick = () => { 
            if(obj.selectable) {
                canvas.setActiveObject(obj); 
                canvas.requestRenderAll(); 
            }
            refreshLayers(); 
            updateProps(); 
        };
        list.appendChild(div);
    });
}

function toggleLock(idx) { 
    const o = canvas.item(idx); 
    if(o) { 
        const willLock = o.selectable;
        o.set({
            selectable: !willLock,
            evented: !willLock,
            lockMovementX: willLock,
            lockMovementY: willLock,
            lockRotation: willLock,
            lockScalingX: willLock,
            lockScalingY: willLock
        });
        if (willLock) canvas.discardActiveObject();
        canvas.requestRenderAll(); 
        refreshLayers(); 
    } 
}
function toggleVis(idx) { const o = canvas.item(idx); if(o){ o.set('visible', !o.visible); canvas.requestRenderAll(); refreshLayers(); } }
function delLayer(idx) { const o = canvas.item(idx); if(o){ canvas.remove(o); canvas.requestRenderAll(); refreshLayers(); } }

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// PROPERTIES PANEL
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
canvas.on('selection:created', () => { updateProps(); refreshLayers(); });
canvas.on('selection:updated', () => { updateProps(); refreshLayers(); });
canvas.on('selection:cleared', () => { showNoSel(); refreshLayers(); });

function setRpHeader(icon, text) {
    const iconEl = document.getElementById('rp-header-icon');
    const textEl = document.getElementById('rp-header-text');
    if (iconEl) iconEl.innerHTML = `<i class="${icon}"></i>`;
    if (textEl) textEl.textContent = text;
}

function showNoSel() {
    setRpHeader('fas fa-sliders', 'Propriedades');
    document.getElementById('props-content').innerHTML = '<div class="no-sel"><i class="fas fa-hand-pointer"></i><p>Selecione um elemento para editar.</p></div>';
}

function updateProps() {
    const objs = canvas.getActiveObjects();
    if (!objs.length) { showNoSel(); return; }

    if (objs.length > 1) {
        setRpHeader('fas fa-object-group', objs.length + ' Elementos');
        document.getElementById('props-content').innerHTML = `<div class="prop-group"><div class="prop-group-title">SeleÃ§Ã£o MÃºltipla</div>
            <div class="prop-row">
                <button class="prop-btn" onclick="groupSel()"><i class="fas fa-object-group"></i> Agrupar</button>
                <button class="prop-btn" onclick="dupActive()"><i class="fas fa-copy"></i> Duplicar</button>
            </div>
            <div class="prop-row"><button class="prop-btn danger" onclick="delActive()"><i class="fas fa-trash"></i> Remover Tudo</button></div>
        </div>`;
        return;
    }

    const obj = objs[0];
    const typeIconMap = { textbox:'fas fa-font', text:'fas fa-font', rect:'fas fa-square', circle:'fas fa-circle', image:'fas fa-image', group:'fas fa-object-group', triangle:'fas fa-play', line:'fas fa-minus' };
    setRpHeader(typeIconMap[obj.type] || 'fas fa-shapes', (obj.name || obj.type || 'Objeto').toUpperCase());
    const isText  = obj.type === 'textbox' || obj.type === 'text';
    const isShape = ['rect','circle','triangle','polygon'].includes(obj.type);
    const isImg   = obj.type === 'image';
    const isGroup = obj.type === 'group';
    const sh = obj.shadow;

    let html = '';

    // General actions
    html += `<div class="prop-group"><div class="prop-group-title">AÃ§Ãµes</div>
        <div class="prop-row">
            <button class="prop-btn" onclick="dupActive()"><i class="fas fa-copy"></i> Duplicar</button>
            ${isGroup ? `<button class="prop-btn" onclick="ungroupSel()"><i class="fas fa-object-ungroup"></i> Desagrupar</button>` : ''}
        </div>
        <div class="prop-row">
            <button class="prop-btn" onclick="alignObj('hcenter')" title="Centralizar Horiz."><i class="fas fa-arrows-left-right-to-line"></i></button>
            <button class="prop-btn" onclick="alignObj('vcenter')" title="Centralizar Vert."><i class="fas fa-arrows-up-down-to-line"></i></button>
            <button class="prop-btn" onclick="sendLayer('front')" title="Para Frente"><i class="fas fa-chevron-double-up"></i></button>
            <button class="prop-btn" onclick="sendLayer('back')" title="Para TrÃ¡s"><i class="fas fa-chevron-double-down"></i></button>
        </div>
        <div class="prop-row">
            <span class="prop-label">Opacidade</span>
            <input class="prop-range" type="range" min="0" max="1" step="0.01" value="${obj.opacity||1}" oninput="setProp('opacity',+this.value)">
        </div>
        <div class="prop-row"><button class="prop-btn danger" onclick="delActive()"><i class="fas fa-trash"></i> Remover</button></div>
    </div>`;

    if (isText) {
        const fonts = ['Inter','Poppins','Montserrat','Playfair Display','Oswald','Raleway'];
        html += `<div class="prop-group"><div class="prop-group-title">Tipografia</div>
            <div class="prop-row"><span class="prop-label">Fonte</span>
                <select class="prop-select" onchange="setProp('fontFamily',this.value)">
                ${fonts.map(f => `<option ${obj.fontFamily===f?'selected':''}>${f}</option>`).join('')}</select>
            </div>
            <div class="prop-row">
                <span class="prop-label">Tamanho</span>
                <input class="prop-input prop-sm" type="number" min="6" max="600" value="${Math.round(obj.fontSize||40)}" onchange="setProp('fontSize',+this.value)">
                <span class="prop-label">Cor</span>
                <input type="color" class="prop-color" value="${c2hex(obj.fill||'#000')}" onchange="setProp('fill',this.value)">
            </div>
            <div class="prop-row">
                <div class="align-row">
                    <button class="align-btn ${obj.textAlign==='left'?'on':''}" onclick="setProp('textAlign','left')"><i class="fas fa-align-left"></i></button>
                    <button class="align-btn ${obj.textAlign==='center'?'on':''}" onclick="setProp('textAlign','center')"><i class="fas fa-align-center"></i></button>
                    <button class="align-btn ${obj.textAlign==='right'?'on':''}" onclick="setProp('textAlign','right')"><i class="fas fa-align-right"></i></button>
                </div>
            </div>
            <div class="prop-row">
                <button class="prop-btn ${isBold(obj)?'on':''}" onclick="toggleBold()"><i class="fas fa-bold"></i></button>
                <button class="prop-btn ${obj.fontStyle==='italic'?'on':''}" onclick="toggleItalic()"><i class="fas fa-italic"></i></button>
                <span class="prop-label" style="margin-left:4px">EspaÃ§.</span>
                <input class="prop-input prop-sm" type="number" step="0.1" value="${obj.lineHeight||1.2}" onchange="setProp('lineHeight',+this.value)">
            </div>
            <button class="ai-btn" onclick="openAiModal()"><i class="fas fa-wand-magic-sparkles"></i> Escrever com IA</button>
        </div>`;
    }

    if (isShape) {
        html += `<div class="prop-group"><div class="prop-group-title">Forma</div>
            <div class="prop-row"><span class="prop-label">Preench.</span><input type="color" class="prop-color" value="${c2hex(obj.fill||'#6366f1')}" onchange="setProp('fill',this.value)">
                <span class="prop-label">Borda</span><input type="color" class="prop-color" value="${c2hex(obj.stroke||'#000000')}" onchange="setProp('stroke',this.value)">
            </div>
            <div class="prop-row"><span class="prop-label">Esp.Borda</span><input class="prop-input prop-sm" type="number" min="0" max="50" value="${obj.strokeWidth||0}" onchange="setProp('strokeWidth',+this.value)">
                ${obj.type==='rect'?`<span class="prop-label">Arred.</span><input class="prop-input prop-sm" type="number" min="0" max="300" value="${obj.rx||0}" onchange="setPropRxy(+this.value)">`:'' }
            </div>
        </div>`;
    }

    if (isImg) {
        html += `<div class="prop-group"><div class="prop-group-title">Imagem</div>
            <div class="prop-row"><span class="prop-label">Arred.</span><input class="prop-input prop-sm" type="number" min="0" value="${obj.rx||0}" onchange="setPropRxy(+this.value)"></div>
            <div class="prop-row"><span class="prop-label">Filtros</span>
                <select class="prop-select" onchange="applyFilter(this.value)">
                    <option value="">Original</option>
                    <option value="grayscale">Preto e Branco</option>
                    <option value="sepia">Vintage</option>
                    <option value="blur">Desfocado</option>
                    <option value="bright">Clarear</option>
                    <option value="dark">Escurecer</option>
                </select>
            </div>
        </div>`;
    }

    // Shadow
    html += `<div class="prop-group"><div class="prop-group-title">Sombra</div>
        <div class="prop-row"><button class="prop-btn ${sh?'on':''}" onclick="toggleShadow()">${sh?'<i class="fas fa-toggle-on"></i> Ativada':'<i class="fas fa-toggle-off"></i> Desativada'}</button></div>
        ${sh ? `
        <div class="prop-row"><span class="prop-label">Cor</span><input type="color" class="prop-color" value="${c2hex(sh.color||'#000000')}" onchange="setShadow('color',this.value)"></div>
        <div class="prop-row"><span class="prop-label">Blur</span><input class="prop-range" type="range" min="0" max="60" value="${sh.blur||0}" oninput="setShadow('blur',+this.value)"></div>
        <div class="prop-row"><span class="prop-label">Dist X</span><input class="prop-range" type="range" min="-40" max="40" value="${sh.offsetX||0}" oninput="setShadow('offsetX',+this.value)"></div>
        <div class="prop-row"><span class="prop-label">Dist Y</span><input class="prop-range" type="range" min="-40" max="40" value="${sh.offsetY||0}" oninput="setShadow('offsetY',+this.value)"></div>
        ` : ''}
    </div>`;

    document.getElementById('props-content').innerHTML = html;
}

function isBold(obj) { const w = obj.fontWeight+''; return w==='bold'||w==='700'||w==='800'||w==='900'; }
function setProp(p, v) { const o = canvas.getActiveObject(); if(o){ o.set(p,v); canvas.requestRenderAll(); } }
function setPropRxy(v) { const o = canvas.getActiveObject(); if(o){ o.set({rx:v,ry:v}); canvas.requestRenderAll(); } }
function toggleBold() { const o=canvas.getActiveObject(); if(o){ o.set('fontWeight', isBold(o)?'normal':'bold'); canvas.requestRenderAll(); updateProps(); } }
function toggleItalic() { const o=canvas.getActiveObject(); if(o){ o.set('fontStyle', o.fontStyle==='italic'?'normal':'italic'); canvas.requestRenderAll(); updateProps(); } }
function sendLayer(d) { const o=canvas.getActiveObject(); if(o){ if(d==='front')canvas.bringToFront(o); else if(d==='back')canvas.sendToBack(o); else if(d==='forward')canvas.bringForward(o); else canvas.sendBackwards(o); canvas.requestRenderAll(); refreshLayers(); } }
function alignObj(d) { const o=canvas.getActiveObject(); if(!o)return; if(d==='hcenter')o.set({left:(W-o.getScaledWidth())/2}); if(d==='vcenter')o.set({top:(H-o.getScaledHeight())/2}); canvas.requestRenderAll(); }
function delActive() { const ao=canvas.getActiveObjects(); if(ao.length){ ao.forEach(o=>canvas.remove(o)); canvas.discardActiveObject(); canvas.requestRenderAll(); refreshLayers(); } }
function dupActive() { const o=canvas.getActiveObject(); if(!o)return; o.clone(c2=>{ canvas.discardActiveObject(); c2.set({left:c2.left+20,top:c2.top+20}); if(c2.type==='activeSelection'){ c2.canvas=canvas; c2.forEachObject(x=>canvas.add(x)); c2.setCoords(); } else canvas.add(c2); canvas.setActiveObject(c2); canvas.requestRenderAll(); pushHistory(); }); }
function groupSel() { const ao=canvas.getActiveObject(); if(ao&&ao.type==='activeSelection'){ ao.toGroup(); canvas.requestRenderAll(); updateProps(); refreshLayers(); } }
function ungroupSel() { const ao=canvas.getActiveObject(); if(ao&&ao.type==='group'){ ao.toActiveSelection(); canvas.requestRenderAll(); updateProps(); refreshLayers(); } }
function toggleShadow() { const o=canvas.getActiveObject(); if(!o)return; o.set('shadow', o.shadow ? null : new fabric.Shadow({color:'rgba(0,0,0,.4)',blur:12,offsetX:4,offsetY:6})); canvas.requestRenderAll(); updateProps(); }
function setShadow(p, v) { const o=canvas.getActiveObject(); if(!o||!o.shadow)return; o.shadow[p]=v; canvas.requestRenderAll(); }
function applyFilter(t) { const o=canvas.getActiveObject(); if(!o||o.type!=='image')return; o.filters=[]; if(t==='grayscale')o.filters.push(new fabric.Image.filters.Grayscale()); else if(t==='sepia')o.filters.push(new fabric.Image.filters.Sepia()); else if(t==='blur')o.filters.push(new fabric.Image.filters.Blur({blur:.3})); else if(t==='bright')o.filters.push(new fabric.Image.filters.Brightness({brightness:.2})); else if(t==='dark')o.filters.push(new fabric.Image.filters.Brightness({brightness:-.3})); o.applyFilters(); canvas.requestRenderAll(); saveHistory(); }
function c2hex(c) { if(!c||typeof c!=='string')return '#000000'; if(c.startsWith('#')){ let h=c.replace('#',''); if(h.length===3)h=h[0]+h[0]+h[1]+h[1]+h[2]+h[2]; if(h.length>6)h=h.slice(0,6); return '#'+h; } if(c.startsWith('rgb')){ const m=c.match(/\d+/g); if(m&&m.length>=3)return '#'+[m[0],m[1],m[2]].map(n=>parseInt(n).toString(16).padStart(2,'0')).join(''); } return '#000000'; }

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// KEYBOARD
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('keydown', e => {
    if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
    if (e.key==='Delete'||e.key==='Backspace') { e.preventDefault(); delActive(); }
    if (e.key==='Escape') { canvas.discardActiveObject(); canvas.requestRenderAll(); }
    if (e.ctrlKey && e.key.toLowerCase()==='d') { e.preventDefault(); dupActive(); }
    if (e.ctrlKey && e.key.toLowerCase()==='z') { e.preventDefault(); doUndo(); }
    if (e.ctrlKey && e.key.toLowerCase()==='y') { e.preventDefault(); doRedo(); }
    if (e.ctrlKey && e.key==='=') { e.preventDefault(); zoomIn(); }
    if (e.ctrlKey && e.key==='-') { e.preventDefault(); zoomOut(); }
    if (e.ctrlKey && e.key==='0') { e.preventDefault(); zoomFit(); }
    if (e.ctrlKey && e.key.toLowerCase()==='s') { e.preventDefault(); saveFabric(false); }
    if (['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) {
        e.preventDefault(); const o=canvas.getActiveObject(); if(!o)return;
        const s=e.shiftKey?10:1;
        if(e.key==='ArrowUp')o.set('top',o.top-s);
        if(e.key==='ArrowDown')o.set('top',o.top+s);
        if(e.key==='ArrowLeft')o.set('left',o.left-s);
        if(e.key==='ArrowRight')o.set('left',o.left+s);
        o.setCoords(); canvas.requestRenderAll();
    }
});

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// SAVE / AUTO-SAVE
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let saveTimer = null;
function setSaveStatus(s) {
    const el = document.getElementById('save-status');
    if (s==='pending') { el.innerHTML='<i class="fas fa-circle" style="color:#f59e0b;font-size:10px"></i> AlteraÃ§Ãµes'; clearTimeout(saveTimer); saveTimer=setTimeout(()=>saveFabric(false),2500); }
    else if (s==='saving') { el.innerHTML='<i class="fas fa-spinner fa-spin" style="color:#6366f1;font-size:10px"></i> Salvando...'; }
    else { el.innerHTML='<i class="fas fa-circle-check" style="color:#10b981;font-size:10px"></i> Salvo'; }
}

function saveFabric(showMsg) {
    setSaveStatus('saving');
    try {
        const jsonData = JSON.stringify(canvas.toJSON(['id','name']));
        const pngData  = canvas.toDataURL({ format:'png', multiplier: Math.min(2, 2000/Math.max(W,H)) });
        const title    = document.getElementById('title-input').value;
        fetch(SAVE_URL, {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify({ fabric_json:jsonData, png_data:pngData, title }),
        }).then(r=>r.json()).then(()=>{ setSaveStatus('saved'); if(showMsg)showNotif('Banner salvo!','success'); })
          .catch(()=>{ setSaveStatus('pending'); if(showMsg)showNotif('Erro ao salvar.','error'); });
    } catch(e) { setSaveStatus('pending'); if(showMsg)showNotif('NÃ£o salvo: imagens externas bloqueiam exportaÃ§Ã£o.','error'); }
}

function exportPng() {
    canvas.discardActiveObject(); 
    canvas.renderAll(); // SÃ­ncrono para garantir que nÃ£o haja camada invisÃ­vel
    
    // Um leve atraso pode evitar o erro de toDataURL limpar canvas no meio de um render pendente
    setTimeout(() => {
        try {
            const dataUrl = canvas.toDataURL({ format:'png', multiplier:1 });
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'BANNER-' + BANNER_ID + '.png';
            document.body.appendChild(a); // NecessÃ¡rio para Firefox/alguns browsers
            a.click();
            document.body.removeChild(a);
            showNotif('PNG exportado com sucesso!','success');
            
            // Restaura o render regular
            canvas.requestRenderAll();
        } catch(e) { 
            showNotif('Imagens externas impediram exportar (CORS).','error'); 
        }
    }, 50);
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// AI + SCHEDULE MODALS
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openAiModal() { openModal('ai-modal'); document.getElementById('ai-result').style.display='none'; }
function runAi() {
    const prompt = document.getElementById('ai-prompt').value.trim();
    if (!prompt) { showNotif('Digite um prompt.','error'); return; }
    const btn = document.getElementById('ai-run-btn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Gerando...';
    fetch(AI_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}, body:JSON.stringify({prompt, field:document.getElementById('ai-field').value}) })
        .then(r=>r.json()).then(d=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-wand-magic-sparkles"></i> Gerar'; if(d.ok){document.getElementById('ai-result').style.display='block';document.getElementById('ai-result-text').textContent=d.text;}else showNotif(d.error||'Erro na IA.','error'); })
        .catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-wand-magic-sparkles"></i> Gerar'; showNotif('Falha de conexÃ£o.','error'); });
}
function applyAiText() {
    const text = document.getElementById('ai-result-text').textContent.trim();
    const active = canvas.getActiveObject();
    if (active&&(active.type==='textbox'||active.type==='text')) { active.set('text', text); canvas.requestRenderAll(); closeModal('ai-modal'); showNotif('Texto aplicado!','success'); }
    else { addText('heading'); const objs=canvas.getObjects(); const n=objs[objs.length-1]; if(n)n.set('text',text); canvas.requestRenderAll(); closeModal('ai-modal'); }
}
function openScheduleModal() {
    const dt=document.getElementById('sched-datetime'); if(dt){ const now=new Date(Date.now()+5*60000); dt.min=now.toISOString().slice(0,16); if(!dt.value)dt.value=new Date(Date.now()+3600000).toISOString().slice(0,16); }
    openModal('schedule-modal');
}
function submitSchedule() {
    const acct=document.getElementById('sched-account'); const cap=document.getElementById('sched-caption').value.trim(); const dt=document.getElementById('sched-datetime').value;
    if(!acct||!cap||!dt){showNotif('Preencha todos os campos.','error');return;}
    try {
        const pngData=canvas.toDataURL({format:'png',multiplier:1});
        const platform=acct.options[acct.selectedIndex].dataset.platform||'instagram';
        const btn=document.querySelector('#schedule-modal .modal-btn-success');
        btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Agendando...';
        fetch(SCHED_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({social_account_id:+acct.value,platform,caption:cap,scheduled_at:dt,png_data:pngData})})
            .then(r=>r.json()).then(d=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Agendar'; if(d.ok){closeModal('schedule-modal');showNotif('Post agendado!','success');}else showNotif('Erro no agendamento.','error'); })
            .catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Agendar'; showNotif('Erro.','error'); });
    } catch(e){showNotif('Imagens externas bloqueiam o agendamento.','error');}
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m)closeModal(m.id); }));

let nTimer=null;
function showNotif(msg, type='') {
    const el=document.getElementById('notif');
    el.className='show '+(type||'');
    el.innerHTML=`<i class="fas ${type==='error'?'fa-triangle-exclamation':type==='success'?'fa-check-circle':'fa-info-circle'}"></i> ${msg}`;
    clearTimeout(nTimer); nTimer=setTimeout(()=>el.classList.remove('show'),3500);
}

// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// INIT
// â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('DOMContentLoaded', () => {
    renderTemplateGrids();
    renderIconGrids();
    renderStockGallery();
    zoomFit();

    const existing = @json($banner->fabric_json);
    if (existing) {
        ignHist = true;
        canvas.loadFromJSON(existing, () => {
            canvas.renderAll();
            ignHist = false;
            pushHistory();
            refreshLayers();
        });
    } else {
        // Autoload template from URL query param (reliable — set by BannerController::store)
        const autoload = @json($autoloadTemplate ?? null);
        if (autoload) {
            const fnName = 'tpl' + autoload.charAt(0).toUpperCase() + autoload.slice(1);
            if (typeof window[fnName] === 'function') {
                window[fnName]();
            } else {
                canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));
                pushHistory();
            }
        } else {
            canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));
            pushHistory();
        }
    }

    document.getElementById('title-input').addEventListener('blur', () => saveFabric(false));
    syncUndoRedo();
});
</script>
</body>
</html>
