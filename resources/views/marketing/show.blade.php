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
    background: #0f172a;
    border-radius: 24px;
    position: relative;
    overflow: hidden;
    width: 100%;
    height: calc(100vh - 220px);
    min-height: 540px;
    box-shadow: 0 25px 60px rgba(0,0,0,.25);
    border: 1px solid rgba(255,255,255,.06);
}
.mindmap-shell::before {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse at 20% 30%, rgba(99,102,241,.12) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 70%, rgba(16,185,129,.08) 0%, transparent 55%);
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
/* nodes legíveis sobre fundo escuro */
.mindmap-shell .markmap-node text { fill: #f1f5f9 !important; }
.mindmap-shell .markmap-link { stroke: rgba(255,255,255,.15) !important; }

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
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(10px);
    color: #fff;
    font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all .15s;
}
.map-btn:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); }

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
    color: rgba(255,255,255,.35);
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
                <span class="fw-bold" style="color:#93c5fd;font-size:1.05rem;">A IA está gerando seu plano estratégico...</span>
            </div>
            <div style="width:280px;">
                <div class="progress" style="height:5px;border-radius:99px;background:rgba(255,255,255,.1);">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated w-100"></div>
                </div>
                <p style="color:rgba(255,255,255,.4);font-size:.8rem;text-align:center;margin-top:14px;">
                    Analisando briefing e gerando estratégias com Bruce AI.<br>Isso leva entre 10 e 30 segundos.
                </p>
            </div>
        </div>

        {{-- Estado: Falhou --}}
        <div id="state-failed"
             class="{{ $marketing->status === 'failed' ? '' : 'd-none' }}"
             style="position:absolute;inset:0;z-index:5;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">
            <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color:#fbbf24;"></i>
            <h6 class="fw-bold" style="color:#f1f5f9;">Não foi possível gerar o plano</h6>
            <p style="color:rgba(255,255,255,.4);font-size:.85rem;max-width:320px;">
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

{{-- Markmap --}}
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
<script src="https://cdn.jsdelivr.net/npm/markmap-view@0.15.4/dist/browser/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/markmap-lib@0.15.4/dist/browser/index.js"></script>

<script>
const PLAN_STATUS = '{{ $marketing->status }}';
const PLAN_ID     = {{ $marketing->id }};
const STATUS_URL  = '{{ route("marketing.status", $marketing->id) }}';
@if($marketing->status === 'done' && $marketing->mindmap_data)
const MINDMAP_MD  = @json($marketing->mindmap_data['markdown'] ?? '');
@else
const MINDMAP_MD  = null;
@endif

let mmInstance = null;

// ── Render ──────────────────────────────────────────────────────────────────
function renderMarkmap(markdown) {
    const { Markmap, loadCSS, loadJS } = window.markmap;
    const t = new window.markmap.Transformer();
    const { root, features } = t.transform(markdown);
    const { styles, scripts } = t.getUsedAssets(features);
    if (styles)  loadCSS(styles);
    if (scripts) loadJS(scripts, { getMarkmap: () => window.markmap });

    const svg = document.getElementById('mindmap-svg');
    svg.style.visibility = 'visible';

    // Injetar fonte premium antes de renderizar
    if (!document.getElementById('markmap-font')) {
        const link = document.createElement('link');
        link.id   = 'markmap-font';
        link.rel  = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap';
        document.head.appendChild(link);
    }

    // Força cor clara em todos os textos SVG (atributos inline têm prioridade sobre CSS)
    function fixTextColor() {
        svg.querySelectorAll('text').forEach(el => {
            el.setAttribute('fill', '#f1f5f9');
            el.style.fill = '#f1f5f9';
        });
    }

    // Observer para manter a cor quando o usuário expande/colapsa nós
    const colorObserver = new MutationObserver(fixTextColor);
    colorObserver.observe(svg, { childList: true, subtree: true, attributes: false });

    mmInstance = Markmap.create(svg, {
        autoFit: true,

        color: (node) => {
            const palette = [
                '#a78bfa', // raiz — violeta
                '#34d399', // nível 1 — verde esmeralda
                '#60a5fa', // nível 2 — azul
                '#f472b6', // nível 3 — rosa
                '#fbbf24', // nível 4 — âmbar
                '#fb923c', // nível 5 — laranja
                '#818cf8', // nível 6 — índigo
            ];
            return palette[node.depth % palette.length];
        },
        duration: 350,
        maxWidth: 380,
        paddingX: 20,
        spacingHorizontal: 80,
        spacingVertical: 8,
        initialExpandLevel: 2,
        style: (id) => `
            #${id} .markmap-node text {
                font-family: 'Inter', 'Outfit', sans-serif !important;
                font-size: 13px;
                font-weight: 600;
                fill: #f1f5f9 !important;
            }
            #${id} .markmap-node > circle {
                stroke-width: 1.5;
            }
            #${id} .markmap-link {
                stroke: rgba(255,255,255,.18) !important;
            }
        `,
    }, root);

    // Aplica cor após render inicial e após animação
    setTimeout(fixTextColor, 500);
    setTimeout(fixTextColor, 1200);
}

// ── Controles ───────────────────────────────────────────────────────────────
function mmZoom(factor) {
    if (!mmInstance) return;
    const { x, y, k } = mmInstance.state.transform ?? { x: 0, y: 0, k: 1 };
    mmInstance.transition(mmInstance.svg)
        .call(mmInstance.zoom.scaleBy, factor);
}

function mmFit() {
    if (mmInstance) mmInstance.fit();
}

function toggleFullscreen() {
    const el = document.querySelector('.mindmap-shell');
    const icon = document.getElementById('fs-icon');
    if (!document.fullscreenElement) {
        el.requestFullscreen().then(() => {
            icon.className = 'fas fa-compress';
            setTimeout(() => mmInstance?.fit(), 300);
        });
    } else {
        document.exitFullscreen().then(() => {
            icon.className = 'fas fa-expand';
            setTimeout(() => mmInstance?.fit(), 300);
        });
    }
}

// ── Poll ─────────────────────────────────────────────────────────────────────
function pollStatus() {
    fetch(STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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

document.addEventListener('DOMContentLoaded', function () {
    if (PLAN_STATUS === 'done' && MINDMAP_MD) {
        setTimeout(() => renderMarkmap(MINDMAP_MD), 200);
    } else if (PLAN_STATUS === 'pending' || PLAN_STATUS === 'processing') {
        setTimeout(pollStatus, 3000);
    }
});

// ── Export ───────────────────────────────────────────────────────────────────
function exportMarkdown() {
    if (!MINDMAP_MD) return;
    const blob = new Blob([MINDMAP_MD], { type: 'text/markdown' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'plano-estrategico.md';
    a.click();
}
</script>
@endsection
