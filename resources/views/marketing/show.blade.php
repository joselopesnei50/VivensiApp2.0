@extends('layouts.app')
@section('title', 'Mapa Estratégico')

@push('styles')
<style>
/* ── Layout ─────────────────────────────────────────────────────────────── */
.mkt-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
/* ── Mapa ───────────────────────────────────────────────────────────────── */
.mindmap-shell {
    background: #f8fafc;
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    width: 100%;
    height: calc(100vh - 220px);
    min-height: 540px;
    box-shadow: 0 25px 60px rgba(0,0,0,.08);
    border: 1px solid #e2e8f0;
}
.mindmap-shell::before {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse at 20% 30%, rgba(99,102,241,.06) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 70%, rgba(16,185,129,.05) 0%, transparent 55%);
    pointer-events: none;
    z-index: 0;
}
#mindmap-svg {
    width: 100%;
    height: 100%;
    display: block;
    position: relative;
    z-index: 1;
}

/* ── Controles flutuantes ───────────────────────────────────────────────── */
.map-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.map-btn {
    width: 40px; height: 40px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all .15s;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.map-btn:hover { background: #6366f1; color: #fff; border-color: #6366f1; }

/* Fullscreen button top-right */
.map-controls-top {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 10;
    display: flex;
    gap: 8px;
}

/* ── Badge de hint ──────────────────────────────────────────────────────── */
.map-hint {
    position: absolute;
    bottom: 20px;
    left: 20px;
    z-index: 10;
    font-size: .72rem;
    color: #94a3b8;
    font-weight: 600;
    pointer-events: none;
}

/* ── Pulse loading ──────────────────────────────────────────────────────── */
.status-pulse {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #3b82f6;
    animation: pulse-blue 1.5s infinite;
    margin-right: 8px;
}
@keyframes pulse-blue {
    0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,.5); }
    50%      { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
}

/* ── Info strip ─────────────────────────────────────────────────────────── */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-top: 20px;
}
.info-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
}
.info-pill .label { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
.info-pill .value { font-size: .88rem; font-weight: 700; color: #1e293b; }

/* ── Quick actions ──────────────────────────────────────────────────────── */
.action-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    background: #eef2ff;
    color: #4f46e5;
    text-decoration: none;
    border: 1px solid #c7d2fe;
    transition: all .15s;
    white-space: nowrap;
}
.action-chip:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }

/* ── Fullscreen override ────────────────────────────────────────────────── */
.mindmap-shell:-webkit-full-screen { height: 100vh; border-radius: 0; }
.mindmap-shell:-moz-full-screen    { height: 100vh; border-radius: 0; }
.mindmap-shell:fullscreen          { height: 100vh; border-radius: 0; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    @if(session('success'))
        <div class="alert alert-success rounded-3 border-0 mb-4">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="mkt-header">
        <div>
            <a href="{{ route('marketing.index') }}" class="text-muted small text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Meus Planos
            </a>
            <h4 class="fw-bold mt-1 mb-0" style="color:#1e293b;">
                <i class="fas fa-brain me-2" style="color:#4f46e5;"></i>
                {{ mb_substr($marketing->title ?? $marketing->objective, 0, 80) }}
            </h4>
            @if($marketing->ai_provider)
                <span class="badge bg-light text-dark border mt-1" style="font-size:.7rem;">
                    <i class="fas fa-robot me-1"></i> Bruce AI
                </span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if($marketing->status === 'done')
                <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
                <button onclick="exportMarkdown()" class="btn btn-outline-primary rounded-pill btn-sm px-3">
                    <i class="fas fa-download me-1"></i> Exportar MD
                </button>
            @endif
            <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill btn-sm px-4 fw-bold">
                <i class="fas fa-plus me-1"></i> Novo Plano
            </a>
            <form action="{{ route('marketing.destroy', $marketing->id) }}" method="POST"
                  onsubmit="return confirm('Remover este plano?')" class="mb-0">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger rounded-pill btn-sm px-3">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- ── Mapa Mental ── --}}
    <div class="mindmap-shell mb-4">

        {{-- Controles topo-direita --}}
        <div class="map-controls-top">
            @if($marketing->ai_provider)
            <span class="map-btn" style="width:auto;padding:0 14px;font-size:.72rem;font-weight:700;gap:6px;pointer-events:none;">
                <i class="fas fa-robot"></i> Bruce AI
            </span>
            @endif
            <button class="map-btn" onclick="toggleFullscreen()" title="Tela cheia">
                <i class="fas fa-expand" id="fs-icon"></i>
            </button>
        </div>

        {{-- Controles laterais direita-baixo --}}
        <div class="map-controls">
            <button class="map-btn" onclick="mmZoom(1.25)" title="Zoom in"><i class="fas fa-plus"></i></button>
            <button class="map-btn" onclick="mmZoom(0.8)"  title="Zoom out"><i class="fas fa-minus"></i></button>
            <button class="map-btn" onclick="mmFit()"      title="Encaixar"><i class="fas fa-compress-arrows-alt"></i></button>
        </div>

        {{-- Hint --}}
        <div class="map-hint">
            <i class="fas fa-mouse me-1"></i> Scroll para zoom &nbsp;·&nbsp; Arraste para mover &nbsp;·&nbsp; Clique nos nós para expandir
        </div>

        {{-- Estado: Processando --}}
        <div id="state-processing"
             class="{{ in_array($marketing->status, ['pending','processing']) ? '' : 'd-none' }}"
             style="position:absolute;inset:0;z-index:5;display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <div class="mb-4">
                <span class="status-pulse"></span>
                <span class="fw-bold" style="color:#4f46e5;font-size:1.05rem;">A IA está gerando seu plano estratégico...</span>
            </div>
            <div style="width:280px;">
                <div class="progress" style="height:5px;border-radius:99px;background:#e2e8f0;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated w-100"></div>
                </div>
                <p style="color:#64748b;font-size:.8rem;text-align:center;margin-top:14px;">
                    Analisando briefing e gerando estratégias com Bruce AI.<br>Isso leva entre 10 e 30 segundos.
                </p>
            </div>
        </div>

        {{-- Estado: Falhou --}}
        <div id="state-failed"
             class="{{ $marketing->status === 'failed' ? '' : 'd-none' }}"
             style="position:absolute;inset:0;z-index:5;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">
            <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color:#fbbf24;"></i>
            <h6 class="fw-bold" style="color:#1e293b;">Não foi possível gerar o plano</h6>
            <p style="color:#64748b;font-size:.85rem;max-width:320px;">
                As APIs de IA podem estar indisponíveis. Tente novamente em alguns minutos.
            </p>
            <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold mt-2">
                Tentar Novamente
            </a>
        </div>

        {{-- SVG do mapa --}}
        <svg id="mindmap-svg" style="{{ $marketing->status === 'done' ? '' : 'visibility:hidden;' }}"></svg>
    </div>

    {{-- ── Briefing + Ações ── --}}
    <div class="row g-4">

        {{-- Briefing --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#1e293b;">
                        <i class="fas fa-clipboard-list me-2 text-warning"></i> Briefing do Plano
                    </h6>
                    <div class="info-grid">
                        <div class="info-pill">
                            <div class="label">Objetivo</div>
                            <div class="value">{{ mb_substr($marketing->objective, 0, 140) }}</div>
                        </div>
                        <div class="info-pill">
                            <div class="label">Público-alvo</div>
                            <div class="value">{{ mb_substr($marketing->target_audience, 0, 100) }}</div>
                        </div>
                        <div class="info-pill">
                            <div class="label">Abrangência</div>
                            <div class="value">{{ $marketing->scope === 'online_offline' ? 'Online + Presencial' : 'Apenas Online' }}</div>
                        </div>
                        <div class="info-pill">
                            <div class="label">Tom de Voz</div>
                            <div class="value">{{ ucfirst($marketing->tone) }}</div>
                        </div>
                        @if($marketing->budget_range)
                        <div class="info-pill">
                            <div class="label">Orçamento</div>
                            <div class="value">{{ $marketing->budget_range }}</div>
                        </div>
                        @endif
                        @if($marketing->project)
                        <div class="info-pill">
                            <div class="label">Projeto</div>
                            <div class="value">
                                <a href="{{ url('/projects/details/' . $marketing->project_id) }}"
                                   style="color:#4f46e5;text-decoration:none;font-weight:700;">
                                    <i class="fas fa-sitemap me-1" style="font-size:.8rem;"></i>
                                    {{ $marketing->project->name }}
                                </a>
                            </div>
                        </div>
                        @endif
                        <div class="info-pill">
                            <div class="label">Criado em</div>
                            <div class="value">{{ $marketing->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ações Rápidas --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1" style="color:#1e293b;">
                        <i class="fas fa-bolt me-2 text-primary"></i> Executar no Vivensi
                    </h6>
                    <p class="text-muted small mb-3">Módulos disponíveis para colocar o plano em prática:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ url('/whatsapp/broadcast') }}" class="action-chip">
                            <i class="fab fa-whatsapp"></i> Disparo WhatsApp
                        </a>
                        <a href="{{ url('/whatsapp/settings') }}" class="action-chip">
                            <i class="fas fa-robot"></i> Configurar Bot
                        </a>
                        @php $isNgo = in_array(auth()->user()->role, ['ngo','super_admin']) || (auth()->user()->tenant?->type === 'ngo'); @endphp
                        <a href="{{ url($isNgo ? '/ngo/landing_pages' : '/manager/landing_pages') }}" class="action-chip">
                            <i class="fas fa-file-alt"></i> Landing Page
                        </a>
                        <a href="{{ url('/social/posts/create') }}" class="action-chip">
                            <i class="fas fa-image"></i> Criar Post
                        </a>
                        <a href="{{ url('/prospecting') }}" class="action-chip">
                            <i class="fas fa-crosshairs"></i> Prospectar Parceiros
                        </a>
                        @if($isNgo)
                        <a href="{{ url('/raffles') }}" class="action-chip">
                            <i class="fas fa-ticket-alt"></i> Criar Rifa
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- D3 + Markmap (apenas o transformer para parse) --}}
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/markmap-lib@0.15.4/dist/browser/index.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

<style>
/* ── Card styles (injetados aqui para funcionar no foreignObject) ── */
.pm-card {
    box-sizing: border-box;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 8px rgba(15,23,42,.07);
    transition: box-shadow .18s, transform .18s;
    overflow: hidden;
    user-select: none;
    padding: 10px 14px;
}
.pm-card:hover { box-shadow: 0 6px 20px rgba(15,23,42,.13); transform: translateY(-1px); }
.pm-inner  { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.pm-label  { line-height: 1.45; flex: 1; word-break: break-word; }
.pm-chevron{ flex-shrink: 0; opacity: .4; font-size: 10px; margin-top: 2px; transition: transform .2s; }
.pm-chevron.open { transform: rotate(90deg); opacity: .6; }
.pm-actions{ margin-top: 8px; display: flex; flex-wrap: wrap; gap: 5px; }
.pm-btn    { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px;
             background: #6366f1; color: #fff !important; border-radius: 7px;
             font-size: 10px; font-weight: 700; text-decoration: none !important;
             line-height: 1.5; transition: background .15s; }
.pm-btn:hover { background: #4f46e5; }
/* depth styles */
.pm-d0 { background: linear-gradient(135deg,#6366f1,#8b5cf6); border:none;
          padding: 16px 22px; border-radius: 18px;
          box-shadow: 0 8px 28px rgba(99,102,241,.35); }
.pm-d0 .pm-label  { color:#fff; font-size:15px; font-weight:800; }
.pm-d0 .pm-chevron{ color:#fff; }

.pm-d1 { border-left: 3px solid #6366f1; }
.pm-d1 .pm-label  { color:#1e293b; font-size:13px; font-weight:700; }

.pm-d2 { background:#f5f3ff; border-color:#ddd6fe; border-left:3px solid #8b5cf6; }
.pm-d2 .pm-label  { color:#3730a3; font-size:12px; font-weight:600; }

.pm-d3 { background:#f0fdf4; border-color:#bbf7d0; border-left:3px solid #10b981; }
.pm-d3 .pm-label  { color:#065f46; font-size:11.5px; font-weight:600; }

.pm-d4 { background:#eff6ff; border-color:#bfdbfe; border-left:3px solid #3b82f6; }
.pm-d4 .pm-label  { color:#1e40af; font-size:11px; font-weight:500; }

.pm-d5 { background:#fff7ed; border-color:#fed7aa; border-left:3px solid #f97316; }
.pm-d5 .pm-label  { color:#9a3412; font-size:10.5px; font-weight:500; }
</style>

<script>
const PLAN_STATUS = '{{ $marketing->status }}';
const STATUS_URL  = '{{ route("marketing.status", $marketing->id) }}';
@if($marketing->status === 'done' && $marketing->mindmap_data)
const MINDMAP_MD = @json($marketing->mindmap_data['markdown'] ?? '');
@else
const MINDMAP_MD = null;
@endif

// ── Configuração de cards por profundidade ────────────────────────────────────
const DEPTH_CFG = [
    { w: 290, padV: 16 }, // 0
    { w: 240, padV: 10 }, // 1
    { w: 220, padV: 10 }, // 2
    { w: 210, padV: 10 }, // 3
    { w: 200, padV:  9 }, // 4
    { w: 195, padV:  8 }, // 5+
];
function dcfg(d) { return DEPTH_CFG[Math.min(d, 5)]; }

// Estimativa de altura do card com base no texto + botões
function cardH(node) {
    const cfg = dcfg(node.depth);
    const chars = (node.data.text || '').length;
    const charsPerLine = Math.floor(cfg.w / 7.2);
    const lines = Math.max(1, Math.ceil(chars / charsPerLine));
    const textH = lines * 20;
    const btnH  = (node.data.links?.length || 0) * 30;
    return cfg.padV * 2 + textH + btnH + (btnH > 0 ? 8 : 0);
}

// ── State ─────────────────────────────────────────────────────────────────────
let pmSvg, pmG, pmZoom, pmRoot;

// ── Parser: Markdown → árvore simples ────────────────────────────────────────
function parseMd(markdown) {
    const transformer = new window.markmap.Transformer();
    const { root: mmRoot } = transformer.transform(markdown);

    function convert(n) {
        const div = document.createElement('div');
        div.innerHTML = n.content || '';
        const links = [...div.querySelectorAll('a')].map(a => ({
            label: a.textContent.trim(),
            href:  a.getAttribute('href') || '#',
        }));
        div.querySelectorAll('a').forEach(a => a.replaceWith(document.createTextNode('')));
        return {
            text:     div.textContent.replace(/\s+/g, ' ').trim(),
            links,
            depth:    n.depth,
            children: (n.children || []).map(convert),
        };
    }
    return convert(mmRoot);
}

// ── Renderer principal ────────────────────────────────────────────────────────
function renderMarkmap(markdown) {
    const svgEl = document.getElementById('mindmap-svg');
    svgEl.innerHTML = '';
    svgEl.style.visibility = 'visible';

    // SVG setup
    pmSvg = d3.select(svgEl);
    pmSvg.append('defs').html(`
        <filter id="pm-sh" x="-20%" y="-35%" width="140%" height="170%">
            <feDropShadow dx="0" dy="2" stdDeviation="5" flood-color="rgba(15,23,42,.09)"/>
        </filter>`);

    pmG = pmSvg.append('g');

    pmZoom = d3.zoom().scaleExtent([.1, 4])
        .on('zoom', e => pmG.attr('transform', e.transform));
    pmSvg.call(pmZoom).on('dblclick.zoom', null);

    // Hierarquia D3
    const data = parseMd(markdown);
    pmRoot = d3.hierarchy(data);

    // Colapsa nós a partir do nível 2
    pmRoot.descendants().forEach(d => {
        if (d.depth >= 2 && d.children) {
            d._children = d.children;
            d.children  = null;
        }
    });

    pmDraw();
    setTimeout(mmFit, 350);
}

// ── Desenho ───────────────────────────────────────────────────────────────────
function pmDraw() {
    // Layout
    const layout = d3.tree()
        .nodeSize([70, 310])
        .separation((a, b) => {
            const ah = cardH(a) / 2 + 12;
            const bh = cardH(b) / 2 + 12;
            return (ah + bh) / 70;
        });
    layout(pmRoot);

    const nodes = pmRoot.descendants();
    const links = pmRoot.links();

    // Cores das linhas por profundidade da origem
    const lineColors = ['#c7d2fe','#a7f3d0','#bfdbfe','#fde68a','#fbcfe8','#ddd6fe'];

    // ── Links (curvas Bezier) ─────────────────────────────────────────────────
    const linkPath = d3.linkHorizontal()
        .x(d => d.y + dcfg(d.depth).w / 2)
        .y(d => d.x);

    pmG.selectAll('.pm-link')
        .data(links, d => d.target.data.text + d.target.depth)
        .join(
            e => e.append('path').attr('class','pm-link')
                    .attr('fill','none').attr('stroke-linecap','round'),
            u => u,
            x => x.remove()
        )
        .attr('stroke', d => lineColors[Math.min(d.source.depth, lineColors.length-1)])
        .attr('stroke-width', d => Math.max(1.5, 3 - d.source.depth * .5))
        .attr('stroke-opacity', .75)
        .attr('d', linkPath);

    // ── Nós (foreignObject com cards HTML) ────────────────────────────────────
    const nodeSel = pmG.selectAll('.pm-fo')
        .data(nodes, d => d.data.text + d.depth);

    nodeSel.join(
        enter => {
            const fo = enter.append('foreignObject')
                .attr('class', 'pm-fo')
                .attr('overflow', 'visible');

            fo.append('xhtml:div')
                .attr('xmlns', 'http://www.w3.org/1999/xhtml')
                .on('click', (evt, d) => {
                    if (evt.target.closest('a')) return;
                    if (d.children)  { d._children = d.children;  d.children  = null; }
                    else if (d._children) { d.children = d._children; d._children = null; }
                    pmDraw();
                });
            return fo;
        },
        u => u,
        x => x.remove()
    )
    .attr('x', d => d.y)
    .attr('y', d => d.x - cardH(d) / 2)
    .attr('width',  d => dcfg(d.depth).w)
    .attr('height', d => cardH(d) + 4)
    .select('div')
    .attr('class', d => `pm-card pm-d${Math.min(d.depth, 5)}`)
    .style('width',  d => dcfg(d.depth).w + 'px')
    .style('min-height', d => cardH(d) + 'px')
    .html(d => buildCard(d));
}

// ── HTML do card ─────────────────────────────────────────────────────────────
function buildCard(d) {
    const { text, links } = d.data;
    const hasKids = d.children || d._children;
    const open    = !!d.children;

    const chevron = hasKids
        ? `<span class="pm-chevron ${open ? 'open' : ''}">&#9654;</span>`
        : '';

    const btns = links?.length
        ? `<div class="pm-actions">${links.map(l =>
            `<a href="${l.href}" class="pm-btn" onclick="event.stopPropagation()">
                <i class="fas fa-arrow-right" style="font-size:8px;"></i> ${l.label}
             </a>`).join('')}
           </div>`
        : '';

    return `<div class="pm-inner">
                <span class="pm-label">${text}</span>
                ${chevron}
            </div>${btns}`;
}

// ── Controles ─────────────────────────────────────────────────────────────────
function mmZoom(factor) {
    pmSvg?.transition().duration(250).call(pmZoom.scaleBy, factor);
}

function mmFit() {
    if (!pmG) return;
    const svgEl = document.getElementById('mindmap-svg');
    const W = svgEl.clientWidth, H = svgEl.clientHeight;
    try {
        const b = pmG.node().getBBox();
        if (!b.width || !b.height) return;
        const scale = Math.min(.95, .82 * Math.min(W / b.width, H / b.height));
        const tx = (W - b.width * scale) / 2 - b.x * scale;
        const ty = (H - b.height * scale) / 2 - b.y * scale;
        pmSvg.transition().duration(420)
            .call(pmZoom.transform, d3.zoomIdentity.translate(tx, ty).scale(scale));
    } catch(e) {}
}

function toggleFullscreen() {
    const el  = document.querySelector('.mindmap-shell');
    const ico = document.getElementById('fs-icon');
    if (!document.fullscreenElement) {
        el.requestFullscreen().then(() => { ico.className = 'fas fa-compress'; setTimeout(mmFit, 300); });
    } else {
        document.exitFullscreen().then(() => { ico.className = 'fas fa-expand'; setTimeout(mmFit, 300); });
    }
}

// ── Poll ──────────────────────────────────────────────────────────────────────
function pollStatus() {
    fetch(STATUS_URL, { headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done' && data.mindmap_data?.markdown) {
                document.getElementById('state-processing').classList.add('d-none');
                setTimeout(() => renderMarkmap(data.mindmap_data.markdown), 100);
            } else if (data.status === 'failed') {
                document.getElementById('state-processing').classList.add('d-none');
                document.getElementById('state-failed').classList.remove('d-none');
            } else {
                setTimeout(pollStatus, 4000);
            }
        })
        .catch(() => setTimeout(pollStatus, 6000));
}

document.addEventListener('DOMContentLoaded', () => {
    if (PLAN_STATUS === 'done' && MINDMAP_MD) {
        setTimeout(() => renderMarkmap(MINDMAP_MD), 200);
    } else if (PLAN_STATUS === 'pending' || PLAN_STATUS === 'processing') {
        setTimeout(pollStatus, 3000);
    }
});

// ── Export ────────────────────────────────────────────────────────────────────
function exportMarkdown() {
    if (!MINDMAP_MD) return;
    const blob = new Blob([MINDMAP_MD], {type:'text/markdown'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'plano-estrategico.md';
    a.click();
}
</script>
@endsection
