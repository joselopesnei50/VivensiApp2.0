@extends('layouts.canvas')
@section('title', $banner->title . ' — Editor')

@php
$font      = $banner->settings['font_family'] ?? 'Inter';
$bgColor   = $banner->settings['bg_color']    ?? '#ffffff';
$fonts     = ['Inter','Roboto','Poppins','Montserrat','Open Sans','Lato','Nunito','Raleway','Oswald','Playfair Display'];
$sectionCount = $banner->sections->count();
@endphp

@section('content')
<div id="editor">

{{-- ══════════ TOP BAR ══════════ --}}
<div id="topBar">
    <div class="tb-left">
        <a href="{{ route('banners.index') }}" class="tb-back" title="Meus Banners">
            <i class="fas fa-chevron-left"></i>
        </a>
        <div class="tb-logo">
            <i class="fas fa-image" style="color:#6366f1;font-size:14px;"></i>
        </div>
        <input id="titleInput" type="text" value="{{ $banner->title }}"
               class="tb-title" spellcheck="false"
               onblur="saveSetting('title',this.value)"
               onkeydown="if(event.key==='Enter')this.blur()">
    </div>
    <div class="tb-center">
        <span class="tb-badge">
            {{ $banner->width }}×{{ $banner->height }}px
        </span>
        <div class="tb-zoom">
            <button class="tb-btn" onclick="zoom(-0.1)" title="Zoom −"><i class="fas fa-minus"></i></button>
            <span id="zoomLabel" class="tb-zoom-label">100%</span>
            <button class="tb-btn" onclick="zoom(0.1)"  title="Zoom +"><i class="fas fa-plus"></i></button>
            <button class="tb-btn" onclick="fitZoom()"  title="Ajustar à tela" style="padding:0 10px;font-size:10px;letter-spacing:.5px;">FIT</button>
        </div>
    </div>
    <div class="tb-right">
        <span id="saveStatus" class="tb-save-status"></span>
        <a href="{{ route('banners.preview', $banner) }}" target="_blank" class="tb-btn tb-btn-ghost">
            <i class="fas fa-eye me-1"></i> Prévia
        </a>
        <a href="{{ route('banners.export', $banner) }}" class="tb-btn tb-btn-ghost">
            <i class="fas fa-download me-1"></i> HTML
        </a>
    </div>
</div>

{{-- ══════════ EDITOR BODY ══════════ --}}
<div id="editorBody">

    {{-- ── LEFT PANEL ── --}}
    <div id="leftPanel">
        <div class="lp-tabs">
            <button class="lp-tab active" onclick="switchTab('templates',this)">
                <i class="fas fa-th-large"></i> Templates
            </button>
            <button class="lp-tab" onclick="switchTab('blocks',this)">
                <i class="fas fa-plus-square"></i> Blocos
            </button>
            <button class="lp-tab" onclick="switchTab('settings',this)">
                <i class="fas fa-sliders-h"></i> Config
            </button>
        </div>

        {{-- TEMPLATES TAB --}}
        <div class="lp-content active" id="tab-templates">
            <p class="lp-hint">Clique para aplicar um template ao banner</p>
            <div class="template-grid" id="templateGrid">
                {{-- preenchido pelo JS --}}
            </div>
        </div>

        {{-- BLOCKS TAB --}}
        <div class="lp-content" id="tab-blocks">
            <p class="lp-hint">Clique para adicionar um bloco ao banner</p>
            @foreach($sectionTypes as $typeKey => $typeInfo)
            <form action="{{ route('banners.sections.add', $banner) }}" method="POST" class="block-form">
                @csrf
                <input type="hidden" name="type" value="{{ $typeKey }}">
                <button type="submit" class="block-btn">
                    <div class="block-btn-icon"><i class="{{ $typeInfo['icon'] }}"></i></div>
                    <div class="block-btn-text">
                        <strong>{{ $typeInfo['label'] }}</strong>
                        <span>{{ $typeInfo['desc'] }}</span>
                    </div>
                </button>
            </form>
            @endforeach
        </div>

        {{-- SETTINGS TAB --}}
        <div class="lp-content" id="tab-settings">
            <div class="cfg-section">
                <label class="cfg-label">Formato</label>
                <select class="cfg-select" onchange="saveSetting('format',this.value)">
                    @foreach($formats as $fk => $fv)
                    <option value="{{ $fk }}" {{ $banner->format===$fk?'selected':'' }}>
                        {{ $fv['label'] }} — {{ $fv['w'] }}×{{ $fv['h'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="cfg-section">
                <label class="cfg-label">Fonte Global</label>
                <select class="cfg-select" onchange="saveSetting('font_family',this.value)">
                    @foreach($fonts as $f)
                    <option value="{{ $f }}" {{ $font===$f?'selected':'' }}>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cfg-section">
                <label class="cfg-label">Cor de Fundo</label>
                <div class="cfg-color-row">
                    <input type="color" class="cfg-color-swatch"
                           value="{{ $bgColor }}"
                           oninput="document.getElementById('cfgBgText').value=this.value;saveSetting('bg_color',this.value)">
                    <input type="text" id="cfgBgText" class="cfg-input" value="{{ $bgColor }}"
                           oninput="this.previousElementSibling.value=this.value;saveSetting('bg_color',this.value)">
                </div>
            </div>
            @if($scheduledPosts->count() > 0)
            <div class="cfg-section">
                <label class="cfg-label">Post Agendado</label>
                <select class="cfg-select" onchange="saveSetting('scheduled_post_id',this.value)">
                    <option value="">— Nenhum —</option>
                    @foreach($scheduledPosts as $sp)
                    <option value="{{ $sp->id }}" {{ $banner->scheduled_post_id==$sp->id?'selected':'' }}>
                        {{ Str::limit($sp->caption,40) }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    {{-- ── CANVAS AREA ── --}}
    <div id="canvasArea">
        <div id="canvasViewport">
            <div id="bannerCanvas"
                 data-width="{{ $banner->width }}"
                 data-height="{{ $banner->height }}"
                 style="
                    width:{{ $banner->width }}px;
                    height:{{ $banner->height }}px;
                    background:{{ $bgColor }};
                    font-family:'{{ $font }}',sans-serif;
                    position:relative;
                    overflow:hidden;
                    display:flex;
                    flex-direction:column;
                 ">
                @if($banner->sections->isEmpty())
                <div id="canvasEmpty">
                    <i class="fas fa-layer-group"></i>
                    <p>Escolha um template<br>ou adicione um bloco</p>
                </div>
                @else
                @foreach($banner->sections as $section)
                    @include('banners.partials.section_render', ['section' => $section, 'banner' => $banner])
                @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div id="rightPanel">
        <div id="rp-empty">
            <i class="fas fa-hand-pointer"></i>
            <p>Selecione um bloco<br>no canvas para editar</p>
        </div>
        <div id="rp-props" style="display:none;">
            <div id="rp-header">
                <span id="rp-type-badge"></span>
                <button class="rp-del-btn" id="rpDeleteBtn" title="Remover bloco">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </div>
            <div id="rp-fields"></div>
        </div>
    </div>

</div>{{-- /editorBody --}}
</div>{{-- /editor --}}

{{-- ── Confirm modal para templates ── --}}
<div class="modal fade" id="tplModal" role="dialog" aria-modal="true" aria-labelledby="tplModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="background:#242731;border:1px solid rgba(255,255,255,.08);border-radius:14px;">
            <div class="modal-body p-4 text-center">
                <div style="font-size:2.5rem;margin-bottom:12px;">🎨</div>
                <h6 style="color:#f1f5f9;font-weight:700;margin-bottom:8px;" id="tplModalName">Aplicar Template</h6>
                <p style="color:#64748b;font-size:13px;margin-bottom:24px;">Os blocos atuais serão substituídos.</p>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary flex-fill btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary flex-fill btn-sm" id="tplModalConfirm" style="background:#6366f1;border-color:#6366f1;">Aplicar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ════════════════ STYLES ════════════════ --}}
@section('head')
<style>
/* ── Reset & root ── */
:root {
  --bg:      #18191d;
  --panel:   #242731;
  --border:  rgba(255,255,255,.07);
  --accent:  #6366f1;
  --text:    #e2e8f0;
  --muted:   #64748b;
  --topbar:  52px;
  --lp:      268px;
  --rp:      284px;
}
body { background: var(--bg); color: var(--text); }

/* ── Editor shell ── */
#editor { display:flex; flex-direction:column; height:100vh; overflow:hidden; }

/* ── Top Bar ── */
#topBar {
    height: var(--topbar);
    background: var(--panel);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    gap: 16px;
    flex-shrink: 0;
    z-index: 50;
}
.tb-left, .tb-right { display:flex; align-items:center; gap:8px; flex:1; }
.tb-right { justify-content:flex-end; }
.tb-center { display:flex; align-items:center; gap:10px; }
.tb-back {
    width:30px; height:30px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    color:var(--muted); text-decoration:none; font-size:13px;
    transition:.15s;
}
.tb-back:hover { background:rgba(255,255,255,.06); color:var(--text); }
.tb-logo {
    width:28px; height:28px; border-radius:8px;
    background:rgba(99,102,241,.15);
    display:flex; align-items:center; justify-content:center;
}
.tb-title {
    background:transparent; border:none; outline:none;
    color:var(--text); font-size:13px; font-weight:600;
    min-width:80px; max-width:280px;
    padding:4px 8px; border-radius:6px;
    transition:.15s;
}
.tb-title:hover, .tb-title:focus { background:rgba(255,255,255,.06); }
.tb-badge {
    font-size:11px; font-weight:600; letter-spacing:.5px;
    color:var(--muted); padding:3px 10px;
    border:1px solid var(--border); border-radius:99px;
}
.tb-zoom { display:flex; align-items:center; gap:4px; }
.tb-zoom-label {
    font-size:12px; font-weight:600; color:var(--muted);
    min-width:44px; text-align:center;
}
.tb-btn {
    background:rgba(255,255,255,.05); border:1px solid var(--border);
    color:var(--text); font-size:12px; font-weight:500;
    padding:5px 10px; border-radius:7px; cursor:pointer;
    transition:.15s; text-decoration:none; display:inline-flex;
    align-items:center; gap:5px;
    white-space:nowrap;
}
.tb-btn:hover { background:rgba(255,255,255,.1); color:var(--text); }
.tb-btn-ghost { color:#94a3b8; }
.tb-save-status {
    font-size:11px; color:var(--muted);
    transition:opacity .3s;
}

/* ── Editor Body ── */
#editorBody {
    flex:1; display:flex; overflow:hidden;
    height: calc(100vh - var(--topbar));
}

/* ── Left Panel ── */
#leftPanel {
    width: var(--lp); flex-shrink:0;
    background: var(--panel);
    border-right: 1px solid var(--border);
    display: flex; flex-direction:column;
    overflow: hidden;
}
.lp-tabs {
    display:flex; border-bottom: 1px solid var(--border);
    flex-shrink:0;
}
.lp-tab {
    flex:1; padding:11px 4px; background:transparent; border:none;
    color:var(--muted); font-size:10.5px; font-weight:600;
    cursor:pointer; transition:.15s; display:flex;
    align-items:center; justify-content:center; gap:5px;
    letter-spacing:.3px; text-transform:uppercase;
    border-bottom:2px solid transparent;
}
.lp-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.lp-tab:hover:not(.active) { color:var(--text); }
.lp-content { display:none; flex:1; overflow-y:auto; padding:14px; }
.lp-content.active { display:block; }
.lp-hint {
    font-size:11px; color:var(--muted); margin-bottom:14px;
    line-height:1.5;
}

/* ── Template Grid ── */
.template-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.tpl-card {
    border-radius:10px; overflow:hidden; cursor:pointer;
    border:2px solid transparent; transition:.2s;
    position:relative;
}
.tpl-card:hover { border-color:var(--accent); transform:translateY(-2px); }
.tpl-thumb {
    height:75px; position:relative;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:10px 8px; gap:5px;
}
.tpl-thumb-line {
    border-radius:3px; opacity:.9;
}
.tpl-thumb-btn {
    height:10px; border-radius:5px; opacity:.8;
    align-self:center;
}
.tpl-name {
    background:rgba(0,0,0,.55); color:#fff;
    font-size:9.5px; font-weight:700;
    padding:4px 6px; text-align:center;
    letter-spacing:.3px;
}
.tpl-apply-overlay {
    position:absolute; inset:0;
    background:rgba(99,102,241,.85);
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:.2s;
    font-size:11px; font-weight:700; color:#fff;
    letter-spacing:.5px;
}
.tpl-card:hover .tpl-apply-overlay { opacity:1; }

/* ── Block buttons ── */
.block-form { margin-bottom:6px; }
.block-btn {
    width:100%; background:rgba(255,255,255,.03);
    border:1px solid var(--border); border-radius:10px;
    padding:10px 12px; text-align:left; cursor:pointer;
    display:flex; align-items:center; gap:10px;
    transition:.15s; color:var(--text);
}
.block-btn:hover { background:rgba(99,102,241,.1); border-color:rgba(99,102,241,.4); }
.block-btn-icon {
    width:32px; height:32px; border-radius:8px;
    background:rgba(99,102,241,.15);
    display:flex; align-items:center; justify-content:center;
    color:var(--accent); font-size:13px; flex-shrink:0;
}
.block-btn-text strong { display:block; font-size:12px; font-weight:600; margin-bottom:2px; }
.block-btn-text span   { font-size:10.5px; color:var(--muted); line-height:1.4; }

/* ── Config Tab ── */
.cfg-section { margin-bottom:18px; }
.cfg-label {
    display:block; font-size:11px; font-weight:600;
    color:var(--muted); text-transform:uppercase; letter-spacing:.5px;
    margin-bottom:7px;
}
.cfg-select, .cfg-input {
    width:100%; background:rgba(255,255,255,.05);
    border:1px solid var(--border); border-radius:8px;
    color:var(--text); font-size:12px; padding:8px 10px;
    outline:none; transition:.15s;
}
.cfg-select:focus, .cfg-input:focus {
    border-color:var(--accent); background:rgba(99,102,241,.08);
}
.cfg-select option { background:#242731; }
.cfg-color-row { display:flex; gap:8px; align-items:center; }
.cfg-color-swatch {
    width:36px; height:36px; padding:2px; border:1px solid var(--border);
    border-radius:8px; background:transparent; cursor:pointer;
}

/* ── Canvas Area ── */
#canvasArea {
    flex:1; overflow:auto; background:var(--bg);
    display:flex; align-items:flex-start; justify-content:center;
    padding:40px 40px 80px;
    position:relative;
}
/* subtle dot grid */
#canvasArea::before {
    content:''; position:fixed; inset:0; pointer-events:none;
    background-image: radial-gradient(rgba(255,255,255,.04) 1px, transparent 1px);
    background-size: 24px 24px;
    z-index:0;
}
#canvasViewport {
    position:relative; z-index:1;
    transform-origin: top center;
    transition: transform .2s;
}
#bannerCanvas {
    box-shadow: 0 20px 80px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.06);
    border-radius:2px;
}
#canvasEmpty {
    position:absolute; inset:0;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:12px; color:#334155;
    pointer-events:none;
}
#canvasEmpty i { font-size:2.8rem; }
#canvasEmpty p { font-size:14px; line-height:1.6; text-align:center; }

/* ── Section Wrappers ── */
.section-wrapper {
    flex:1; position:relative; min-height:0;
    cursor:pointer; transition:outline .1s;
    outline:2px solid transparent;
    outline-offset:-2px;
}
.section-wrapper:hover { outline-color:rgba(99,102,241,.5); }
.section-wrapper.active { outline-color:#6366f1; outline-width:3px; }
.section-controls {
    position:absolute; top:8px; right:8px; z-index:20;
    display:none; gap:4px;
}
.section-wrapper.active .section-controls { display:flex; }
.section-wrapper:hover .section-controls  { display:flex; }
.sc-btn {
    width:26px; height:26px; border-radius:6px;
    display:flex; align-items:center; justify-content:center;
    border:none; cursor:pointer; font-size:11px;
    transition:.15s;
}
.sc-btn-del { background:rgba(239,68,68,.15); color:#f87171; }
.sc-btn-del:hover { background:rgba(239,68,68,.4); }

/* ── Right Panel ── */
#rightPanel {
    width: var(--rp); flex-shrink:0;
    background: var(--panel);
    border-left: 1px solid var(--border);
    overflow-y:auto; overflow-x:hidden;
}
#rp-empty {
    height:100%; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    gap:12px; color:#334155; padding:24px; text-align:center;
}
#rp-empty i { font-size:2rem; }
#rp-empty p { font-size:13px; line-height:1.7; }
#rp-header {
    padding:14px 16px 10px;
    border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
}
#rp-type-badge {
    font-size:10px; font-weight:700; letter-spacing:1px;
    text-transform:uppercase; color:var(--accent);
    background:rgba(99,102,241,.12); padding:3px 10px; border-radius:99px;
}
.rp-del-btn {
    background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2);
    color:#f87171; font-size:11px; font-weight:600;
    padding:4px 10px; border-radius:7px; cursor:pointer;
    display:flex; align-items:center; gap:5px; transition:.15s;
}
.rp-del-btn:hover { background:rgba(239,68,68,.25); }
#rp-fields { padding:14px 16px; }

/* ── Field Groups ── */
.rp-group { margin-bottom:16px; }
.rp-label {
    display:block; font-size:10.5px; font-weight:600;
    color:var(--muted); text-transform:uppercase; letter-spacing:.5px;
    margin-bottom:6px;
}
.rp-input, .rp-select, .rp-textarea {
    width:100%; background:rgba(255,255,255,.05);
    border:1px solid var(--border); border-radius:8px;
    color:var(--text); font-size:12px; padding:7px 10px;
    outline:none; transition:.15s;
}
.rp-input:focus, .rp-select:focus, .rp-textarea:focus {
    border-color:var(--accent); background:rgba(99,102,241,.08);
}
.rp-select option { background:#242731; }
.rp-textarea { resize:vertical; min-height:60px; }
.rp-color-row { display:flex; gap:8px; }
.rp-color-swatch {
    width:34px; height:34px; padding:2px;
    border:1px solid var(--border); border-radius:8px;
    background:transparent; cursor:pointer; flex-shrink:0;
}
.rp-row2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.rp-section-divider {
    height:1px; background:var(--border); margin:16px -16px;
}
.rp-section-title {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.8px; color:#334155; margin-bottom:12px;
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:99px; }
::-webkit-scrollbar-thumb:hover { background:rgba(255,255,255,.18); }
</style>
@endsection

{{-- ════════════════ SCRIPTS ════════════════ --}}
@section('scripts')
<script>
/* ═══════════ CONSTANTS ═══════════ */
const BANNER_W    = {{ $banner->width }};
const BANNER_H    = {{ $banner->height }};
const SAVE_URL    = "{{ route('banners.settings', $banner) }}";
const SECTION_BASE= "{{ url('/banners/sections') }}/";
const TPL_URL     = "{{ route('banners.apply-template', $banner) }}";
const CSRF        = "{{ csrf_token() }}";

/* ═══════════ ZOOM ═══════════ */
let currentZoom = 1;

function fitZoom() {
    const area  = document.getElementById('canvasArea');
    const W     = area.clientWidth  - 80;
    const H     = area.clientHeight - 80;
    const scale = Math.min(W / BANNER_W, H / BANNER_H, 1);
    setZoom(Math.round(scale * 20) / 20); // snap to 5%
}

function zoom(delta) {
    setZoom(Math.min(Math.max(currentZoom + delta, 0.1), 3));
}

function setZoom(z) {
    currentZoom = z;
    document.getElementById('zoomLabel').textContent = Math.round(z * 100) + '%';
    const vp = document.getElementById('canvasViewport');
    vp.style.transform = `scale(${z})`;
    vp.style.transformOrigin = 'top center';
    // adjust viewport height so scrollable area reflects scaled canvas
    vp.style.marginBottom = ((BANNER_H * z) - BANNER_H) + 'px';
}

window.addEventListener('load', () => {
    fitZoom();
    // ctrl/cmd + scroll to zoom
    document.getElementById('canvasArea').addEventListener('wheel', e => {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            zoom(e.deltaY < 0 ? 0.05 : -0.05);
        }
    }, { passive:false });
});

/* ═══════════ TABS ═══════════ */
function switchTab(name, btn) {
    document.querySelectorAll('.lp-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.lp-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
}

/* ═══════════ SAVE SETTINGS ═══════════ */
let saveTimer = null;
function saveSetting(key, value) {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        setSaveStatus('Salvando…');
        fetch(SAVE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify({[key]: value})
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                setSaveStatus('✓ Salvo');
                if (key === 'bg_color') document.getElementById('bannerCanvas').style.background = value;
                if (key === 'font_family') document.getElementById('bannerCanvas').style.fontFamily = value + ',sans-serif';
                if (key === 'title') document.getElementById('titleInput').value = d.title;
                setTimeout(() => setSaveStatus(''), 2000);
            }
        });
    }, 400);
}

function setSaveStatus(msg) {
    document.getElementById('saveStatus').textContent = msg;
}

/* ═══════════ SECTION SELECTION ═══════════ */
let activeSectionId = null;

function selectSection(el, sectionId) {
    document.querySelectorAll('.section-wrapper').forEach(s => s.classList.remove('active'));
    el.classList.add('active');
    activeSectionId = sectionId;
    const type    = el.dataset.sectionType;
    const content = JSON.parse(el.dataset.content || '{}');
    renderRightPanel(sectionId, type, content);
}

function deselectAll() {
    document.querySelectorAll('.section-wrapper').forEach(s => s.classList.remove('active'));
    activeSectionId = null;
    document.getElementById('rp-empty').style.display = '';
    document.getElementById('rp-props').style.display = 'none';
}

document.addEventListener('click', e => {
    if (!e.target.closest('.section-wrapper') && !e.target.closest('#rightPanel')) {
        deselectAll();
    }
});

/* ═══════════ SECTION DELETE ═══════════ */
function deleteSection(sectionId) {
    if (!confirm('Remover este bloco?')) return;
    fetch(SECTION_BASE + sectionId + '/delete', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({})
    }).then(r => r.json()).then(d => {
        if (d.ok) location.reload();
    });
}

/* ═══════════ SECTION SAVE (AJAX) ═══════════ */
let sectionTimers = {};

function autoSaveSection(sectionId) {
    clearTimeout(sectionTimers[sectionId]);
    sectionTimers[sectionId] = setTimeout(() => {
        const data = collectFields();
        setSaveStatus('Salvando…');
        fetch(SECTION_BASE + sectionId + '/update', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok && d.html) {
                const inner = document.getElementById('si' + sectionId);
                if (inner) inner.innerHTML = d.html;
                // Update data-content on wrapper
                const wrapper = document.querySelector('[data-section-id="'+sectionId+'"]');
                if (wrapper) wrapper.dataset.content = JSON.stringify(data);
                setSaveStatus('✓ Salvo');
                setTimeout(() => setSaveStatus(''), 1800);
            }
        });
    }, 350);
}

function collectFields() {
    const data = {};
    document.querySelectorAll('#rp-fields [data-field]').forEach(el => {
        const k = el.dataset.field;
        const v = el.value;
        data[k] = (!isNaN(v) && v !== '') ? (v * 1) : v;
    });
    return data;
}

/* ═══════════ RIGHT PANEL RENDERER ═══════════ */
function renderRightPanel(sectionId, type, c) {
    document.getElementById('rp-empty').style.display = 'none';
    document.getElementById('rp-props').style.display = '';

    const typeLabels = {
        hero_text:'Hero Texto', image_overlay:'Imagem+Overlay',
        gradient_text:'Degradê', split_banner:'Split',
        announcement:'Anúncio', event_banner:'Evento', simple_text:'Texto Bold'
    };
    document.getElementById('rp-type-badge').textContent = typeLabels[type] || type;
    document.getElementById('rpDeleteBtn').onclick = () => deleteSection(sectionId);

    const fields = document.getElementById('rp-fields');
    fields.innerHTML = buildFields(type, c);

    fields.querySelectorAll('[data-field]').forEach(inp => {
        const ev = (inp.type === 'color') ? 'input' : 'input';
        inp.addEventListener(ev, () => autoSaveSection(sectionId));
    });
}

/* ═══════════ FIELD BUILDERS ═══════════ */
function f_text(label, key, val, placeholder='') {
    return `<div class="rp-group">
        <label class="rp-label">${label}</label>
        <input type="text" class="rp-input" data-field="${key}" value="${esc(val ?? '')}" placeholder="${placeholder}">
    </div>`;
}
function f_textarea(label, key, val) {
    return `<div class="rp-group">
        <label class="rp-label">${label}</label>
        <textarea class="rp-textarea" data-field="${key}">${esc(val ?? '')}</textarea>
    </div>`;
}
function f_number(label, key, val, min='', max='', step=1) {
    return `<div class="rp-group">
        <label class="rp-label">${label}</label>
        <input type="number" class="rp-input" data-field="${key}" value="${val ?? ''}" min="${min}" max="${max}" step="${step}">
    </div>`;
}
function f_color(label, key, val) {
    const v = val || '#000000';
    return `<div class="rp-group">
        <label class="rp-label">${label}</label>
        <div class="rp-color-row">
            <input type="color" class="rp-color-swatch" data-field="${key}" value="${v}"
                   oninput="this.nextElementSibling.value=this.value">
            <input type="text" class="rp-input" data-field="${key}" value="${v}"
                   oninput="this.previousElementSibling.value=this.value" style="flex:1;">
        </div>
    </div>`;
}
function f_select(label, key, val, opts) {
    const options = opts.map(o => `<option value="${o.v}" ${val===o.v?'selected':''}>${o.l}</option>`).join('');
    return `<div class="rp-group">
        <label class="rp-label">${label}</label>
        <select class="rp-select" data-field="${key}">${options}</select>
    </div>`;
}
function f_divider(title='') {
    return `<div class="rp-section-divider"></div>${title?`<p class="rp-section-title">${title}</p>`:''}`;
}
function f_row2(a, b) { return `<div class="rp-row2">${a}${b}</div>`; }
function esc(s) { return String(s).replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
const ALIGN = [{v:'left',l:'Esquerda'},{v:'center',l:'Centro'},{v:'right',l:'Direita'}];

function buildFields(type, c) {
    let h = '';
    if (type === 'hero_text') {
        h += f_divider('Conteúdo');
        h += f_text('Título', 'title', c.title);
        h += f_text('Subtítulo', 'subtitle', c.subtitle);
        h += f_select('Alinhamento', 'alignment', c.alignment, ALIGN);
        h += f_divider('Tipografia');
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_number('Tam. Subtítulo','subtitle_size',c.subtitle_size,10,100)}</div>`;
        h += `<div class="rp-row2">${f_color('Cor Título','title_color',c.title_color)}${f_color('Cor Subtítulo','subtitle_color',c.subtitle_color)}</div>`;
        h += f_divider('Fundo');
        h += f_color('Cor de Fundo', 'bg_color', c.bg_color);
        h += f_divider('Botão CTA');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo Botão','button_bg',c.button_bg)}${f_color('Cor Botão','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'image_overlay') {
        h += f_divider('Imagem');
        h += f_text('URL da Imagem', 'image_url', c.image_url, 'https://...');
        h += `<div class="rp-row2">${f_color('Cor Overlay','overlay_color',c.overlay_color)}${f_number('Opacidade (%)','overlay_opacity',c.overlay_opacity,0,100)}</div>`;
        h += f_divider('Texto');
        h += f_text('Título', 'title', c.title);
        h += f_text('Subtítulo', 'subtitle', c.subtitle);
        h += f_select('Alinhamento', 'alignment', c.alignment, ALIGN);
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_number('Tam. Subtítulo','subtitle_size',c.subtitle_size,10,100)}</div>`;
        h += `<div class="rp-row2">${f_color('Cor Título','title_color',c.title_color)}${f_color('Cor Subtítulo','subtitle_color',c.subtitle_color)}</div>`;
        h += f_divider('Botão');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo','button_bg',c.button_bg)}${f_color('Cor','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'gradient_text') {
        h += f_divider('Degradê');
        h += `<div class="rp-row2">${f_color('Cor Início','gradient_from',c.gradient_from)}${f_color('Cor Fim','gradient_to',c.gradient_to)}</div>`;
        h += f_number('Ângulo (°)', 'gradient_angle', c.gradient_angle, 0, 360);
        h += f_divider('Texto');
        h += f_text('Título', 'title', c.title);
        h += f_text('Subtítulo', 'subtitle', c.subtitle);
        h += f_select('Alinhamento', 'alignment', c.alignment, ALIGN);
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_number('Tam. Subtítulo','subtitle_size',c.subtitle_size,10,100)}</div>`;
        h += `<div class="rp-row2">${f_color('Cor Título','title_color',c.title_color)}${f_color('Cor Subtítulo','subtitle_color',c.subtitle_color)}</div>`;
        h += f_divider('Botão');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo','button_bg',c.button_bg)}${f_color('Cor','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'split_banner') {
        h += f_divider('Imagem');
        h += f_text('URL Imagem', 'image_url', c.image_url);
        h += f_select('Lado', 'image_side', c.image_side, [{v:'left',l:'Esquerda'},{v:'right',l:'Direita'}]);
        h += f_divider('Texto');
        h += f_color('Fundo do Texto', 'bg_color', c.bg_color);
        h += f_text('Tag', 'tag', c.tag);
        h += `<div class="rp-row2">${f_color('Fundo Tag','tag_bg',c.tag_bg)}${f_color('Cor Tag','tag_color',c.tag_color)}</div>`;
        h += f_text('Título', 'title', c.title);
        h += f_textarea('Subtítulo', 'subtitle', c.subtitle);
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_color('Cor Título','title_color',c.title_color)}</div>`;
        h += f_divider('Botão');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo','button_bg',c.button_bg)}${f_color('Cor','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'announcement') {
        h += f_divider('Identidade');
        h += f_color('Cor de Fundo', 'bg_color', c.bg_color);
        h += `<div class="rp-row2">${f_text('Ícone FA','icon',c.icon,'fas fa-bullhorn')}${f_color('Cor Ícone','icon_color',c.icon_color)}</div>`;
        h += f_text('Tag', 'tag', c.tag);
        h += `<div class="rp-row2">${f_color('Fundo Tag','tag_bg',c.tag_bg)}${f_color('Cor Tag','tag_color',c.tag_color)}</div>`;
        h += f_divider('Conteúdo');
        h += f_text('Título', 'title', c.title);
        h += f_textarea('Mensagem', 'message', c.message);
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_number('Tam. Msg','message_size',c.message_size,10,100)}</div>`;
        h += `<div class="rp-row2">${f_color('Cor Título','title_color',c.title_color)}${f_color('Cor Msg','message_color',c.message_color)}</div>`;
        h += f_divider('Botão');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo','button_bg',c.button_bg)}${f_color('Cor','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'event_banner') {
        h += f_divider('Identidade');
        h += `<div class="rp-row2">${f_color('Fundo','bg_color',c.bg_color)}${f_color('Destaque','accent_color',c.accent_color)}</div>`;
        h += f_text('Tag', 'tag', c.tag);
        h += f_text('URL Logo', 'logo_url', c.logo_url, 'https://...');
        h += f_divider('Evento');
        h += f_text('Título', 'title', c.title);
        h += `<div class="rp-row2">${f_text('Data','date',c.date,'15 de Julho')}${f_text('Horário','time',c.time,'19h00')}</div>`;
        h += f_text('Local', 'location', c.location);
        h += `<div class="rp-row2">${f_number('Tam. Título','title_size',c.title_size,16,200)}${f_color('Cor Título','title_color',c.title_color)}</div>`;
        h += f_color('Cor Data/Info', 'date_color', c.date_color);
        h += f_divider('Botão');
        h += `<div class="rp-row2">${f_text('Texto','button_text',c.button_text)}${f_text('URL','button_url',c.button_url,'#')}</div>`;
        h += `<div class="rp-row2">${f_color('Fundo','button_bg',c.button_bg)}${f_color('Cor','button_color',c.button_color)}</div>`;
        h += f_number('Raio (px)', 'button_radius', c.button_radius, 0, 100);
    } else if (type === 'simple_text') {
        h += f_color('Cor de Fundo', 'bg_color', c.bg_color);
        h += f_textarea('Texto', 'text', c.text);
        h += `<div class="rp-row2">${f_number('Tamanho (px)','text_size',c.text_size,16,300)}${f_number('Peso','text_weight',c.text_weight,100,900,100)}</div>`;
        h += f_color('Cor do Texto', 'text_color', c.text_color);
        h += f_select('Alinhamento', 'alignment', c.alignment, ALIGN);
    }
    return h;
}

/* ═══════════ TEMPLATES ═══════════ */
const TEMPLATES = [
    {
        id:'gradient_indigo', name:'Índigo', category:'gradient',
        thumb:'background:linear-gradient(135deg,#6366f1,#8b5cf6)',
        text_color:'#fff',
        sections:[{type:'gradient_text',content:{gradient_from:'#6366f1',gradient_to:'#8b5cf6',gradient_angle:135,title:'Título Principal',subtitle:'Mensagem de apoio clara e objetiva.',title_size:54,subtitle_size:20,title_color:'#ffffff',subtitle_color:'#c7d2fe',alignment:'center',button_text:'Saiba Mais',button_url:'#',button_bg:'rgba(255,255,255,0.18)',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'gradient_ocean', name:'Oceano', category:'gradient',
        thumb:'background:linear-gradient(135deg,#0ea5e9,#6366f1)',
        text_color:'#fff',
        sections:[{type:'gradient_text',content:{gradient_from:'#0ea5e9',gradient_to:'#6366f1',gradient_angle:135,title:'Transforme o Futuro',subtitle:'Juntos construímos uma comunidade melhor.',title_size:54,subtitle_size:20,title_color:'#ffffff',subtitle_color:'#bae6fd',alignment:'center',button_text:'Participar',button_url:'#',button_bg:'rgba(255,255,255,0.2)',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'gradient_sunset', name:'Pôr do Sol', category:'gradient',
        thumb:'background:linear-gradient(135deg,#f97316,#ec4899)',
        text_color:'#fff',
        sections:[{type:'gradient_text',content:{gradient_from:'#f97316',gradient_to:'#ec4899',gradient_angle:135,title:'Faça a Diferença',subtitle:'Sua contribuição transforma vidas.',title_size:54,subtitle_size:20,title_color:'#ffffff',subtitle_color:'#fde8d8',alignment:'center',button_text:'Contribuir',button_url:'#',button_bg:'rgba(255,255,255,0.2)',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'gradient_forest', name:'Floresta', category:'gradient',
        thumb:'background:linear-gradient(135deg,#059669,#0d9488)',
        text_color:'#fff',
        sections:[{type:'gradient_text',content:{gradient_from:'#059669',gradient_to:'#0d9488',gradient_angle:135,title:'Impacto Positivo',subtitle:'Cada ação conta na construção de um mundo melhor.',title_size:54,subtitle_size:20,title_color:'#ffffff',subtitle_color:'#a7f3d0',alignment:'center',button_text:'Agir Agora',button_url:'#',button_bg:'rgba(255,255,255,0.2)',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'dark_hero', name:'Hero Dark', category:'dark',
        thumb:'background:linear-gradient(135deg,#0f172a,#1e1b4b)',
        text_color:'#a5b4fc',
        sections:[{type:'hero_text',content:{title:'Título Impactante',subtitle:'Sua mensagem de apoio vai aqui com clareza.',title_size:52,title_color:'#ffffff',subtitle_size:20,subtitle_color:'#94a3b8',bg_color:'#0f172a',alignment:'center',button_text:'Ação Principal',button_url:'#',button_bg:'#6366f1',button_color:'#ffffff',button_radius:8}}]
    },
    {
        id:'light_clean', name:'Minimalista', category:'light',
        thumb:'background:linear-gradient(135deg,#f8fafc,#e2e8f0)',
        text_color:'#334155',
        sections:[{type:'hero_text',content:{title:'Design Clean',subtitle:'Layout profissional para sua comunicação.',title_size:52,title_color:'#0f172a',subtitle_size:20,subtitle_color:'#64748b',bg_color:'#f8fafc',alignment:'center',button_text:'Saiba Mais',button_url:'#',button_bg:'#6366f1',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'split_modern', name:'Split', category:'split',
        thumb:'background:linear-gradient(90deg,#4f46e5 50%,#f8fafc 50%)',
        text_color:'#fff',
        sections:[{type:'split_banner',content:{image_url:'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800&q=80',image_side:'left',bg_color:'#ffffff',tag:'CAMPANHA',tag_bg:'#4f46e5',tag_color:'#ffffff',title:'Unidos por uma Causa',subtitle:'Acreditamos que cada ação conta. Junte-se a nós.',title_size:38,title_color:'#111827',subtitle_size:17,subtitle_color:'#6b7280',button_text:'Quero Participar',button_url:'#',button_bg:'#4f46e5',button_color:'#ffffff',button_radius:8}}]
    },
    {
        id:'event_night', name:'Evento', category:'event',
        thumb:'background:linear-gradient(160deg,#0f172a,#312e81)',
        text_color:'#a5b4fc',
        sections:[{type:'event_banner',content:{bg_color:'#0f172a',accent_color:'#818cf8',tag:'EVENTO',title:'Nome do Evento',date:'15 de Maio de 2025',time:'19h00',location:'Presencial / Online',title_size:46,title_color:'#ffffff',date_color:'#a5b4fc',button_text:'Inscrever-se',button_url:'#',button_bg:'#6366f1',button_color:'#ffffff',button_radius:8,logo_url:''}}]
    },
    {
        id:'announcement_warm', name:'Anúncio', category:'announcement',
        thumb:'background:linear-gradient(135deg,#fffbeb,#fef3c7)',
        text_color:'#92400e',
        sections:[{type:'announcement',content:{icon:'fas fa-bullhorn',icon_color:'#f59e0b',bg_color:'#fffbeb',tag:'NOVIDADE',tag_bg:'#f59e0b',tag_color:'#ffffff',title:'Importante Anúncio',message:'Temos uma novidade incrível para compartilhar com você.',title_size:36,title_color:'#111827',message_size:18,message_color:'#6b7280',button_text:'Ver Detalhes',button_url:'#',button_bg:'#f59e0b',button_color:'#ffffff',button_radius:8}}]
    },
    {
        id:'bold_text', name:'Texto Bold', category:'text',
        thumb:'background:#111827',
        text_color:'#f9fafb',
        sections:[{type:'simple_text',content:{bg_color:'#111827',text:'Sua\nmensagem\naqui.',text_size:80,text_color:'#ffffff',text_weight:900,alignment:'center'}}]
    },
    {
        id:'red_power', name:'Vermelho', category:'gradient',
        thumb:'background:linear-gradient(135deg,#dc2626,#9f1239)',
        text_color:'#fff',
        sections:[{type:'gradient_text',content:{gradient_from:'#dc2626',gradient_to:'#9f1239',gradient_angle:135,title:'Título Impactante',subtitle:'Mensagem forte para sua audiência.',title_size:54,subtitle_size:20,title_color:'#ffffff',subtitle_color:'#fecaca',alignment:'center',button_text:'Ação Agora',button_url:'#',button_bg:'rgba(255,255,255,0.2)',button_color:'#ffffff',button_radius:50}}]
    },
    {
        id:'photo_overlay', name:'Foto+Texto', category:'photo',
        thumb:'background:linear-gradient(to bottom,#374151,#111827)',
        text_color:'#9ca3af',
        sections:[{type:'image_overlay',content:{image_url:'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=1200&q=80',overlay_color:'#000000',overlay_opacity:55,title:'Faça a Diferença',subtitle:'Sua participação transforma vidas e comunidades.',title_size:56,title_color:'#ffffff',subtitle_size:22,subtitle_color:'#f1f5f9',alignment:'center',button_text:'Participar',button_url:'#',button_bg:'#ffffff',button_color:'#1e293b',button_radius:50}}]
    },
];

function buildTemplateGrid() {
    const grid = document.getElementById('templateGrid');
    grid.innerHTML = TEMPLATES.map(t => `
        <div class="tpl-card" onclick="confirmTemplate(${JSON.stringify(t).replace(/"/g,'&quot;')})">
            <div class="tpl-thumb" style="${t.thumb}">
                <div class="tpl-thumb-line" style="background:${t.text_color};width:65%;height:8px;"></div>
                <div class="tpl-thumb-line" style="background:${t.text_color};width:80%;height:5px;opacity:.6;"></div>
                <div class="tpl-thumb-line" style="background:${t.text_color};width:50%;height:5px;opacity:.4;"></div>
                <div class="tpl-thumb-btn"  style="background:${t.text_color};width:35%;opacity:.85;"></div>
            </div>
            <div class="tpl-name">${t.name}</div>
            <div class="tpl-apply-overlay">APLICAR</div>
        </div>
    `).join('');
}

let pendingTemplate = null;
function confirmTemplate(tplData) {
    pendingTemplate = tplData;
    document.getElementById('tplModalName').textContent = tplData.name;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('tplModal')).show();
}

document.getElementById('tplModalConfirm').addEventListener('click', () => {
    if (!pendingTemplate) return;
    bootstrap.Modal.getInstance(document.getElementById('tplModal')).hide();
    applyTemplate(pendingTemplate.sections);
});

function applyTemplate(sections) {
    setSaveStatus('Aplicando…');
    fetch(TPL_URL, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({sections})
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const canvas = document.getElementById('bannerCanvas');
            // Remove empty state
            const empty = document.getElementById('canvasEmpty');
            if (empty) empty.remove();
            // Replace section wrappers
            canvas.querySelectorAll('.section-wrapper').forEach(s => s.remove());
            canvas.insertAdjacentHTML('beforeend', d.html);
            deselectAll();
            setSaveStatus('✓ Template aplicado');
            setTimeout(() => setSaveStatus(''), 2000);
        }
    });
}

buildTemplateGrid();

/* ─── keyboard: Escape deselects ─── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') deselectAll();
    if ((e.ctrlKey || e.metaKey) && e.key === '=') { e.preventDefault(); zoom(0.1); }
    if ((e.ctrlKey || e.metaKey) && e.key === '-') { e.preventDefault(); zoom(-0.1); }
    if ((e.ctrlKey || e.metaKey) && e.key === '0') { e.preventDefault(); fitZoom(); }
});
</script>
@endsection
