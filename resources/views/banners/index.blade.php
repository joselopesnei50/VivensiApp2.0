@extends('layouts.app')
@section('title', 'Criador de Banners')
@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<style>
/* ── BANNER STUDIO ────────────────────── */
.bs-page { padding: 24px 0 40px; }

/* Header */
.bs-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
}
.bs-header-left h1 { font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; }
.bs-header-left p  { font-size: .83rem; color: #6b7280; margin: 4px 0 0; }
.btn-new {
    background: #6366f1; color: #fff; border: none; border-radius: 10px;
    padding: 11px 22px; font-weight: 700; font-size: .88rem; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(99,102,241,.35); transition: all .15s;
    text-decoration: none; white-space: nowrap;
}
.btn-new:hover { background: #4f46e5; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,.45); }

/* Template row */
.tpl-row-label { font-size: .78rem; font-weight: 700; color: #374151; margin-bottom: 10px; letter-spacing: .3px; }
.tpl-row {
    display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 28px;
    scrollbar-width: thin; scrollbar-color: #e5e7eb transparent;
}
.tpl-row::-webkit-scrollbar { height: 4px; }
.tpl-row::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 2px; }
.tpl-starter {
    flex: 0 0 100px; height: 140px; border-radius: 12px; cursor: pointer;
    position: relative; overflow: hidden; border: 2px solid transparent;
    transition: all .2s; box-shadow: 0 2px 8px rgba(0,0,0,.1);
    display: flex; flex-direction: column; justify-content: flex-end;
}
.tpl-starter:hover { transform: translateY(-3px); border-color: #6366f1; box-shadow: 0 8px 24px rgba(99,102,241,.3); }
.tpl-starter-label {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(to top, rgba(0,0,0,.85) 0%, transparent 100%);
    padding: 18px 6px 6px; font-size: .66rem; font-weight: 700; color: #fff;
    text-align: center; line-height: 1.3;
}
.tpl-starter-inner { /* Visual content inside card */
    position: absolute; inset: 6px; display: flex; flex-direction: column;
    justify-content: center; align-items: center; gap: 3px;
}
.tpl-line { height: 6px; border-radius: 3px; background: rgba(255,255,255,.8); }
.tpl-line-sm { height: 4px; border-radius: 2px; background: rgba(255,255,255,.5); }
.tpl-circle { width: 26px; height: 26px; border-radius: 50%; background: rgba(255,255,255,.25); }
.tpl-badge {
    background: rgba(255,255,255,.9); border-radius: 4px; padding: 2px 6px;
    font-size: 7px; font-weight: 800; color: #111; letter-spacing: .5px;
}

/* Section header */
.sec-hdr { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.sec-hdr-title { font-size: .95rem; font-weight: 700; color: #111827; }
.sec-hdr-count {
    background: #e0e7ff; color: #4f46e5; border-radius: 20px;
    padding: 2px 10px; font-size: .72rem; font-weight: 700; margin-left: 8px;
}
.bs-search {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1.5px solid #e5e7eb; border-radius: 8px;
    padding: 7px 14px; font-size: .83rem; color: #374151;
}
.bs-search input { border: none; outline: none; background: transparent; color: #374151; font-size: .83rem; width: 180px; }

/* Banner grid */
.bn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }

.bn-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    border: 1.5px solid #f1f5f9; box-shadow: 0 1px 8px rgba(0,0,0,.05);
    transition: all .2s; display: flex; flex-direction: column;
}
.bn-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.1); border-color: #c7d2fe; }

/* Thumbnail — no iframe, purely CSS + PNG */
.bn-thumb {
    height: 150px; position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.bn-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.bn-thumb-placeholder {
    width: 100%; height: 100%; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
}
.bn-thumb-placeholder .ph-line { height: 8px; border-radius: 4px; background: rgba(255,255,255,.3); margin: 0 20px; }
.bn-thumb-placeholder .ph-line-sm { height: 5px; border-radius: 3px; background: rgba(255,255,255,.2); margin: 0 28px; }
.bn-thumb-placeholder .ph-btn {
    margin-top: 6px; background: rgba(255,255,255,.2); border-radius: 12px;
    padding: 5px 16px; font-size: 9px; font-weight: 700; color: rgba(255,255,255,.8); letter-spacing: .5px;
}
.bn-thumb-edit {
    position: absolute; inset: 0; background: rgba(79,70,229,.0);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: all .2s;
}
.bn-card:hover .bn-thumb-edit { background: rgba(79,70,229,.65); opacity: 1; }
.bn-thumb-edit-btn {
    background: #fff; color: #4f46e5; border: none; border-radius: 8px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; gap: 6px; text-decoration: none;
    transform: scale(.9); transition: transform .15s;
}
.bn-card:hover .bn-thumb-edit-btn { transform: scale(1); }
.bn-fmt-badge {
    position: absolute; top: 7px; left: 7px;
    background: rgba(0,0,0,.5); backdrop-filter: blur(6px);
    border-radius: 5px; padding: 2px 7px; font-size: .62rem; font-weight: 700; color: #fff;
}
.bn-body { padding: 11px 13px 4px; flex: 1; }
.bn-name { font-weight: 700; font-size: .85rem; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bn-meta { font-size: .7rem; color: #94a3b8; margin-top: 2px; }
.bn-footer { padding: 8px 13px 12px; display: flex; gap: 7px; align-items: center; }
.bn-btn-edit {
    flex: 1; background: #6366f1; color: #fff; border: none; border-radius: 8px;
    padding: 7px 12px; font-size: .78rem; font-weight: 700; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;
    transition: background .15s;
}
.bn-btn-edit:hover { background: #4f46e5; color: #fff; }
.bn-btn-menu {
    background: #f8fafc; border: 1.5px solid #e5e7eb; border-radius: 8px;
    padding: 7px 10px; cursor: pointer; color: #64748b; font-size: .75rem;
    transition: all .15s;
}
.bn-btn-menu:hover { background: #f1f5f9; }

/* Empty state */
.bs-empty {
    text-align: center; padding: 60px 24px;
    border-radius: 16px; border: 2px dashed #e5e7eb; background: #fafafa;
}
.bs-empty-icon { font-size: 3rem; color: #d1d5db; margin-bottom: 14px; }

/* Modal */
.format-card {
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:12px 8px;border:2px solid #e5e7eb;border-radius:12px;cursor:pointer;
    text-align:center;transition:all .15s;color:#64748b;min-height:84px;background:#fff;
}
.format-card:hover { border-color:#6366f1;color:#6366f1;background:#f5f3ff; }
.format-card.active { border-color:#6366f1;background:#eef2ff;color:#4f46e5;box-shadow:0 0 0 3px rgba(99,102,241,.1); }

@media(max-width:640px) {
    .bn-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .tpl-starter { flex: 0 0 82px; height: 115px; }
}
</style>

<div class="bs-page">

    {{-- Header --}}
    <div class="bs-header">
        <div class="bs-header-left">
            <h1><i class="fas fa-wand-magic-sparkles" style="color:#6366f1;margin-right:8px"></i>Criador de Banners</h1>
            <p>Crie posts, stories e banners profissionais para redes sociais.</p>
        </div>
        <button class="btn-new" data-bs-toggle="modal" data-bs-target="#newBannerModal">
            <i class="fas fa-plus"></i> Novo Banner
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4 rounded-3" style="border:none;background:#f0fdf4;color:#15803d">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Quick-start templates --}}
    <div class="tpl-row-label"><i class="fas fa-bolt" style="color:#f59e0b;margin-right:5px"></i>Começar de um template</div>
    <div class="tpl-row">
        @php
        $starters = [
            ['label'=>'Black Friday Azul',  'bg'=>'linear-gradient(135deg,#1e3a8a,#0f172a)',  'fmt'=>'instagram_story', 'accent'=>'#fde047', 'icon'=>'fa-bolt'],
            ['label'=>'Black Friday Dark',  'bg'=>'linear-gradient(135deg,#111,#dc2626)',      'fmt'=>'instagram_story', 'accent'=>'#fff', 'icon'=>'fa-fire'],
            ['label'=>'Agência Digital',    'bg'=>'linear-gradient(135deg,#162d40,#0d9488)',   'fmt'=>'instagram_story', 'accent'=>'#99f6e4', 'icon'=>'fa-chart-line'],
            ['label'=>'BF Câmera Dourada',  'bg'=>'linear-gradient(135deg,#1a0a00,#d97706)',   'fmt'=>'instagram_story', 'accent'=>'#fbbf24', 'icon'=>'fa-camera'],
            ['label'=>'BF Relógio Laranja', 'bg'=>'linear-gradient(135deg,#1c1000,#ea580c)',   'fmt'=>'instagram_story', 'accent'=>'#fed7aa', 'icon'=>'fa-clock'],
            ['label'=>'BF Fone Preto',      'bg'=>'linear-gradient(135deg,#111,#eab308)',       'fmt'=>'instagram_story', 'accent'=>'#fde047', 'icon'=>'fa-headphones'],
            ['label'=>'Business Orange',    'bg'=>'linear-gradient(135deg,#ea580c,#fff7ed)',    'fmt'=>'facebook_post',   'accent'=>'#fff', 'icon'=>'fa-briefcase'],
            ['label'=>'Travel Explore',     'bg'=>'linear-gradient(135deg,#0284c7,#eab308)',    'fmt'=>'instagram_story', 'accent'=>'#fde047', 'icon'=>'fa-plane'],
            ['label'=>'Startup Talkshow',   'bg'=>'linear-gradient(135deg,#0d4a3e,#0f7560)',   'fmt'=>'instagram_story', 'accent'=>'#d4af37', 'icon'=>'fa-microphone'],
            ['label'=>'NGO Neon',           'bg'=>'linear-gradient(135deg,#1e1b4b,#4f46e5)',   'fmt'=>'instagram_story', 'accent'=>'#a5b4fc', 'icon'=>'fa-heart'],
            ['label'=>'Social Impact',      'bg'=>'linear-gradient(135deg,#064e3b,#10b981)',    'fmt'=>'facebook_post',   'accent'=>'#6ee7b7', 'icon'=>'fa-leaf'],
            ['label'=>'Fashion Pop',        'bg'=>'linear-gradient(135deg,#ec4899,#f97316)',    'fmt'=>'instagram_story', 'accent'=>'#fff', 'icon'=>'fa-star'],
        ];
        @endphp
        @foreach($starters as $s)
        <div class="tpl-starter" style="background:{{ $s['bg'] }}"
             onclick="openNewBannerWithFormat('{{ $s['fmt'] }}')">
            {{-- Mini visual content --}}
            <div style="position:absolute;inset:0;padding:8px;display:flex;flex-direction:column;gap:4px;justify-content:center;align-items:flex-start">
                <div style="width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;margin-bottom:2px">
                    <i class="fas {{ $s['icon'] }}" style="font-size:9px;color:{{ $s['accent'] }}"></i>
                </div>
                <div style="height:7px;border-radius:4px;background:rgba(255,255,255,.8);width:70%"></div>
                <div style="height:5px;border-radius:3px;background:rgba(255,255,255,.45);width:55%"></div>
                <div style="height:5px;border-radius:3px;background:rgba(255,255,255,.3);width:40%"></div>
                <div style="margin-top:5px;background:{{ $s['accent'] }};border-radius:8px;padding:3px 8px;font-size:7px;font-weight:800;color:#111;letter-spacing:.3px;white-space:nowrap">
                    {{ strtoupper(explode(' ', $s['label'])[0]) }}
                </div>
            </div>
            <div class="tpl-starter-label">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Banners list --}}
    <div class="sec-hdr">
        <div>
            <span class="sec-hdr-title">Meus Banners</span>
            <span class="sec-hdr-count">{{ $banners->count() }}</span>
        </div>
        @if($banners->count() > 0)
        <div class="bs-search">
            <i class="fas fa-magnifying-glass" style="color:#9ca3af;font-size:.8rem"></i>
            <input type="text" id="banner-search" placeholder="Buscar..." oninput="filterBanners(this.value)">
        </div>
        @endif
    </div>

    @if($banners->isEmpty())
    <div class="bs-empty">
        <div class="bs-empty-icon"><i class="fas fa-image"></i></div>
        <h5 class="fw-700" style="color:#374151">Nenhum banner ainda</h5>
        <p style="color:#6b7280;font-size:.88rem;margin-bottom:20px">Crie seu primeiro banner usando um template acima ou do zero.</p>
        <button class="btn-new" data-bs-toggle="modal" data-bs-target="#newBannerModal">
            <i class="fas fa-plus"></i> Criar Primeiro Banner
        </button>
    </div>
    @else
    <div class="bn-grid" id="banners-grid">
        @foreach($banners as $banner)
        @php
        /* Generate a deterministic gradient from the banner ID for visual variety */
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
        <div class="bn-card" data-name="{{ strtolower($banner->title) }}">
            <div class="bn-thumb" style="{{ $banner->png_path ? '' : 'background:'.$grad }}">
                @if($banner->png_path)
                    <img src="{{ Storage::disk('public')->url($banner->png_path) }}"
                         alt="{{ $banner->title }}" loading="lazy">
                @else
                    {{-- Visual placeholder — no iframe --}}
                    <div class="bn-thumb-placeholder">
                        <div class="ph-line" style="width:65%"></div>
                        <div class="ph-line-sm" style="width:50%"></div>
                        <div class="ph-line-sm" style="width:38%"></div>
                        <div class="ph-btn">EDITAR</div>
                    </div>
                @endif

                <div class="bn-fmt-badge">{{ $formats[$banner->format]['label'] ?? $banner->format }}</div>

                <div class="bn-thumb-edit">
                    <a href="{{ route('banners.canvas', $banner) }}" class="bn-thumb-edit-btn">
                        <i class="fas fa-pen"></i> Editar
                    </a>
                </div>
            </div>

            <div class="bn-body">
                <div class="bn-name" title="{{ $banner->title }}">{{ $banner->title }}</div>
                <div class="bn-meta">{{ $banner->width }}×{{ $banner->height }}px · {{ $banner->updated_at->diffForHumans() }}</div>
            </div>

            <div class="bn-footer">
                <a href="{{ route('banners.canvas', $banner) }}" class="bn-btn-edit">
                    <i class="fas fa-wand-magic-sparkles"></i> Abrir Editor
                </a>
                <div class="dropdown">
                    <button class="bn-btn-menu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow" style="font-size:.82rem;border-radius:12px;padding:6px;min-width:190px">
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('banners.canvas', $banner) }}">
                            <i class="fas fa-pen me-2 text-primary"></i> Editor Canvas</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('banners.preview', $banner) }}" target="_blank">
                            <i class="fas fa-eye me-2 text-success"></i> Pré-visualizar</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="{{ route('banners.export', $banner) }}">
                            <i class="fas fa-code me-2 text-secondary"></i> Exportar HTML</a></li>
                        <li>
                            <form action="{{ route('banners.duplicate', $banner) }}" method="POST">
                                @csrf <button class="dropdown-item rounded-2 py-2">
                                <i class="fas fa-copy me-2 text-warning"></i> Duplicar</button>
                            </form>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('banners.destroy', $banner) }}" method="POST"
                                  onsubmit="return confirm('Remover este banner?')">
                                @csrf @method('DELETE')
                                <button class="dropdown-item rounded-2 py-2 text-danger">
                                <i class="fas fa-trash me-2"></i> Excluir</button>
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

{{-- Modal Novo Banner --}}
<div class="modal fade" id="newBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <h5 class="modal-title fw-800" style="font-size:1.1rem">
                    <i class="fas fa-image me-2" style="color:#6366f1"></i> Novo Banner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('banners.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4">
                    <div class="mb-4">
                        <label class="form-label fw-600 small">Nome do Banner <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-lg"
                               placeholder="Ex: Campanha Black Friday — Novembro" required
                               style="border-radius:10px">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-600 small">Formato <span class="text-danger">*</span></label>
                        <div class="row g-2" id="formatPicker">
                            @foreach($formats as $key => $fmt)
                            <div class="col-6 col-sm-4 col-md-3">
                                <label class="format-card {{ $key === 'instagram_story' ? 'active' : '' }}"
                                       id="fcard-{{ $key }}">
                                    <input type="radio" name="format" value="{{ $key }}"
                                           {{ $key === 'instagram_story' ? 'checked' : '' }}
                                           class="d-none" onchange="selectFormat('{{ $key }}')">
                                    <i class="{{ $fmt['icon'] }}" style="font-size:1.2rem;margin-bottom:4px"></i>
                                    <span class="d-block fw-bold" style="font-size:.76rem">{{ $fmt['label'] }}</span>
                                    <span class="d-block text-muted" style="font-size:.63rem">{{ $fmt['w'] }}×{{ $fmt['h'] }}px</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div id="customDims" style="display:none" class="row g-2 mt-2">
                            <div class="col-6">
                                <label class="form-label small fw-600">Largura (px)</label>
                                <input type="number" name="custom_width" class="form-control" value="1200" min="100" max="4000" style="border-radius:8px">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-600">Altura (px)</label>
                                <input type="number" name="custom_height" class="form-control" value="628" min="100" max="4000" style="border-radius:8px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-new">
                        <i class="fas fa-arrow-right"></i> Criar e Abrir Editor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectFormat(key) {
    document.querySelectorAll('.format-card').forEach(c => c.classList.remove('active'));
    const lbl = document.getElementById('fcard-' + key);
    if (lbl) lbl.classList.add('active');
    document.getElementById('customDims').style.display = key === 'custom' ? '' : 'none';
}
function openNewBannerWithFormat(fmtKey) {
    selectFormat(fmtKey);
    const inp = document.querySelector(`#fcard-${fmtKey} input`);
    if (inp) inp.checked = true;
    new bootstrap.Modal(document.getElementById('newBannerModal')).show();
}
function filterBanners(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.bn-card').forEach(card => {
        card.style.display = (card.dataset.name || '').includes(q) ? '' : 'none';
    });
}
</script>
@endsection
