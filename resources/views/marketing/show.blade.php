@extends('layouts.app')
@section('title', 'Mapa Estratégico')

@push('styles')
<style>
    .mindmap-wrap {
        background: #f8fafc;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        position: relative;
        min-height: 520px;
    }
    #mindmap-svg {
        width: 100%;
        height: 600px;
    }
    .markmap-node-circle { fill: #4f46e5; stroke: #4f46e5; }
    .markmap-link { stroke: #c7d2fe; }
    .markmap-node text { font-family: 'Inter', sans-serif; }
    .status-pulse {
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        background: #3b82f6;
        animation: pulse-blue 1.5s infinite;
        margin-right: 8px;
    }
    @keyframes pulse-blue {
        0%,100% { box-shadow: 0 0 0 0 rgba(59,130,246,.4); }
        50%      { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
    }
    .action-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px;
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
    .briefing-card { background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; }
    .briefing-row { display: flex; gap: 12px; margin-bottom: 12px; font-size: .88rem; }
    .briefing-label { font-weight: 700; color: #64748b; min-width: 110px; }
    .briefing-val { color: #1e293b; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    @if(session('success'))
        <div class="alert alert-success rounded-3 border-0 mb-4">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <a href="{{ route('marketing.index') }}" class="text-muted small text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Meus Planos
            </a>
            <h4 class="fw-bold mt-1 mb-0" style="color:#1e293b;">
                <i class="fas fa-sitemap me-2" style="color:#4f46e5;"></i>
                {{ mb_substr($marketing->title ?? $marketing->objective, 0, 70) }}
            </h4>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($marketing->status === 'done')
                <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
                <button onclick="exportMarkdown()" class="btn btn-outline-primary rounded-pill btn-sm px-3">
                    <i class="fas fa-download me-1"></i> Exportar MD
                </button>
            @endif
            <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill btn-sm px-3 fw-bold">
                <i class="fas fa-plus me-1"></i> Novo Plano
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Mapa Mental --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 px-4 pt-4 pb-0 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0" style="color:#1e293b;">
                        <i class="fas fa-project-diagram me-2 text-primary"></i> Mapa Mental Estratégico
                    </h6>
                    @if($marketing->ai_provider)
                        <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                            IA: {{ ucfirst($marketing->ai_provider) }}
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">

                    {{-- Estado: Processando --}}
                    <div id="state-processing" style="{{ in_array($marketing->status, ['pending','processing']) ? '' : 'display:none;' }}">
                        <div class="mindmap-wrap d-flex flex-column align-items-center justify-content-center" style="min-height:400px;">
                            <div class="mb-4">
                                <div class="status-pulse"></div>
                                <span class="fw-bold" style="color:#3b82f6;">A IA está gerando seu plano estratégico...</span>
                            </div>
                            <div style="width:280px;">
                                <div class="progress" style="height:6px;border-radius:99px;">
                                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated w-100"></div>
                                </div>
                                <p class="text-muted small text-center mt-3">Analisando briefing e gerando estratégias com Gemini AI. Isso leva entre 10 e 30 segundos.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Estado: Falhou --}}
                    <div id="state-failed" style="{{ $marketing->status === 'failed' ? '' : 'display:none;' }}">
                        <div class="mindmap-wrap d-flex flex-column align-items-center justify-content-center text-center" style="min-height:300px;">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h6 class="fw-bold">Não foi possível gerar o plano</h6>
                            <p class="text-muted small">As APIs de IA podem estar indisponíveis. Tente novamente em alguns minutos.</p>
                            <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold mt-2">Tentar Novamente</a>
                        </div>
                    </div>

                    {{-- Estado: Concluído --}}
                    <div id="state-done" style="{{ $marketing->status === 'done' ? '' : 'display:none;' }}">
                        <div class="mindmap-wrap mb-3">
                            <svg id="mindmap-svg"></svg>
                        </div>
                        <p class="text-muted small text-center mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Use o scroll para zoom · Arraste para mover · Clique nos nós para expandir/colapsar
                        </p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-xl-4">

            {{-- Briefing --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#1e293b;">
                        <i class="fas fa-clipboard-list me-2 text-warning"></i> Briefing
                    </h6>
                    <div class="briefing-card">
                        <div class="briefing-row">
                            <span class="briefing-label">Objetivo</span>
                            <span class="briefing-val">{{ mb_substr($marketing->objective, 0, 120) }}</span>
                        </div>
                        <div class="briefing-row">
                            <span class="briefing-label">Público</span>
                            <span class="briefing-val">{{ mb_substr($marketing->target_audience, 0, 80) }}</span>
                        </div>
                        <div class="briefing-row">
                            <span class="briefing-label">Abrangência</span>
                            <span class="briefing-val">{{ $marketing->scope === 'online_offline' ? 'Online + Presencial' : 'Apenas Online' }}</span>
                        </div>
                        <div class="briefing-row mb-0">
                            <span class="briefing-label">Tom de Voz</span>
                            <span class="briefing-val">{{ ucfirst($marketing->tone) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ações Rápidas --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#1e293b;">
                        <i class="fas fa-bolt me-2 text-primary"></i> Ações Rápidas no Vivensi
                    </h6>
                    <p class="text-muted small mb-3">Módulos disponíveis para executar seu plano agora:</p>
                    <div class="d-flex flex-column gap-2">
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

// ── Render Markmap ──────────────────────────────────────────────────────────
function renderMarkmap(markdown) {
    const { Markmap, loadCSS, loadJS } = window.markmap;
    const { transformer } = window.markmap;

    const t = new window.markmap.Transformer();
    const { root, features } = t.transform(markdown);

    const { styles, scripts } = t.getUsedAssets(features);
    if (styles) loadCSS(styles);
    if (scripts) loadJS(scripts, { getMarkmap: () => window.markmap });

    const svg = document.getElementById('mindmap-svg');
    Markmap.create(svg, {
        autoFit: true,
        color: (node) => {
            const colors = ['#4f46e5','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'];
            return colors[node.depth % colors.length];
        },
        duration: 400,
        maxWidth: 280,
    }, root);
}

// ── Poll while processing ───────────────────────────────────────────────────
function pollStatus() {
    fetch(STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done' && data.mindmap_data?.markdown) {
                document.getElementById('state-processing').style.display = 'none';
                document.getElementById('state-done').style.display = '';
                setTimeout(() => renderMarkmap(data.mindmap_data.markdown), 100);
            } else if (data.status === 'failed') {
                document.getElementById('state-processing').style.display = 'none';
                document.getElementById('state-failed').style.display = '';
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

// ── Export ──────────────────────────────────────────────────────────────────
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
