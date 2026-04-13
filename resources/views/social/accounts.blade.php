@extends('layouts.app')
@section('title', 'Redes Sociais – Contas Conectadas')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Redes Sociais</h1>
            <p class="text-muted small mb-0">Conecte suas contas do Facebook e Instagram para agendar e publicar posts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('social.posts.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-alt me-1"></i> Calendário de Posts
            </a>
            @if($configured)
                <a href="{{ route('social.facebook.connect') }}" class="btn btn-primary btn-sm">
                    <i class="fab fa-facebook me-1"></i> Conectar Facebook
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif

    {{-- ══ Banner Promocional: Criador de Banners ══ --}}
    <div class="promo-banner-wrap mb-4" id="promoBanner">
        <div class="promo-banner-inner">

            {{-- Camadas de parallax --}}
            <div class="promo-layer promo-layer--orb1" data-depth="0.3"></div>
            <div class="promo-layer promo-layer--orb2" data-depth="0.5"></div>
            <div class="promo-layer promo-layer--grid"  data-depth="0.15"></div>
            <div class="promo-layer promo-layer--lines" data-depth="0.25"></div>

            {{-- Conteúdo --}}
            <div class="promo-content">
                <div class="promo-left">
                    <span class="promo-eyebrow">
                        <span class="promo-dot"></span> Novo no Vivensi
                    </span>
                    <h2 class="promo-title">
                        Crie banners<br>
                        <span class="promo-title-accent">que vendem.</span>
                    </h2>
                    <p class="promo-subtitle">
                        Editor visual de banners para redes sociais, e-mails e campanhas.<br>
                        Sem Photoshop. Sem curva de aprendizado.
                    </p>
                    <div class="promo-actions">
                        <a href="{{ route('banners.index') }}" class="promo-cta-primary">
                            Criar meu primeiro banner
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <div class="promo-formats">
                            <span>Facebook</span>
                            <span>Instagram</span>
                            <span>Stories</span>
                            <span>+4</span>
                        </div>
                    </div>
                </div>

                <div class="promo-right" aria-hidden="true">
                    {{-- Mockup visual de banners empilhados --}}
                    <div class="promo-mockup">
                        <div class="mock-card mock-card--back">
                            <div class="mock-bar" style="background:#7c3aed;width:60%;"></div>
                            <div class="mock-bar" style="background:#ffffff22;width:80%;margin-top:8px;"></div>
                            <div class="mock-btn"></div>
                        </div>
                        <div class="mock-card mock-card--mid">
                            <div style="position:absolute;inset:0;background:linear-gradient(135deg,#3b82f6 0%,#6366f1 100%);border-radius:inherit;"></div>
                            <div style="position:relative;padding:20px;">
                                <div class="mock-bar" style="background:#fff;width:50%;height:8px;"></div>
                                <div class="mock-bar" style="background:#ffffff88;width:75%;margin-top:8px;height:5px;"></div>
                                <div class="mock-bar" style="background:#ffffff88;width:65%;margin-top:5px;height:5px;"></div>
                                <div class="mock-btn" style="background:#fff;margin-top:14px;"></div>
                            </div>
                        </div>
                        <div class="mock-card mock-card--front">
                            <div style="position:absolute;inset:0;background:#0f172a;border-radius:inherit;overflow:hidden;">
                                <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;border-radius:50%;background:#6366f1;opacity:.25;"></div>
                            </div>
                            <div style="position:relative;padding:22px;">
                                <span style="font-size:9px;font-weight:700;letter-spacing:2px;color:#a5b4fc;text-transform:uppercase;">CAMPANHA</span>
                                <div class="mock-bar" style="background:#fff;width:85%;height:9px;margin-top:10px;"></div>
                                <div class="mock-bar" style="background:#ffffff66;width:65%;margin-top:7px;height:5px;"></div>
                                <div class="mock-btn" style="background:#6366f1;margin-top:16px;width:40%;"></div>
                            </div>
                        </div>
                        {{-- Badge floating --}}
                        <div class="mock-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#6366f1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            7 formatos prontos
                        </div>
                    </div>
                </div>
            </div>

            {{-- Métricas de rodapé --}}
            <div class="promo-metrics">
                <div class="promo-metric">
                    <span class="promo-metric-num">7</span>
                    <span class="promo-metric-label">Formatos de banner</span>
                </div>
                <div class="promo-metric-divider"></div>
                <div class="promo-metric">
                    <span class="promo-metric-num">7</span>
                    <span class="promo-metric-label">Tipos de blocos</span>
                </div>
                <div class="promo-metric-divider"></div>
                <div class="promo-metric">
                    <span class="promo-metric-num">∞</span>
                    <span class="promo-metric-label">Combinações visuais</span>
                </div>
                <div class="promo-metric-divider"></div>
                <div class="promo-metric">
                    <span class="promo-metric-num">HTML</span>
                    <span class="promo-metric-label">Exportação nativa</span>
                </div>
            </div>

        </div>
    </div>

    @if(!$configured)
    <div class="alert alert-warning border-0 rounded-3 d-flex align-items-center gap-3">
        <i class="fas fa-exclamation-triangle fs-4 text-warning"></i>
        <div>
            <strong>Módulo não configurado.</strong>
            O administrador ainda não inseriu as credenciais do App Meta.
            @if(auth()->user()->role === 'super_admin')
                <a href="{{ url('/admin/settings') }}" class="alert-link">Configurar agora →</a>
            @endif
        </div>
    </div>
    @endif

    @if($accounts->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fab fa-facebook text-muted" style="font-size:3rem;opacity:.3;"></i>
                <h5 class="mt-3 fw-bold">Nenhuma conta conectada</h5>
                <p class="text-muted small">Clique em "Conectar Facebook" para vincular suas páginas.</p>
            </div>
        </div>
    @else
    <div class="row g-3">
        @foreach($accounts as $account)
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-start gap-3 p-4">
                    @if($account->page_picture)
                        <img src="{{ $account->page_picture }}" alt="" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fab fa-facebook text-primary"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="fw-bold mb-0 text-truncate">{{ $account->page_name }}</h6>
                        <small class="text-muted">ID: {{ $account->page_id }}</small>

                        @if($account->instagram_username)
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border">
                                <i class="fab fa-instagram me-1" style="color:#e1306c;"></i>@{{ $account->instagram_username }}
                            </span>
                        </div>
                        @endif

                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            @if($account->is_active)
                                <span class="badge bg-success bg-opacity-15 text-success">Ativa</span>
                            @else
                                <span class="badge bg-danger bg-opacity-15 text-danger">Desconectada</span>
                            @endif

                            @if($account->token_expires_at)
                                @if($account->isTokenExpired())
                                    <span class="badge bg-danger bg-opacity-15 text-danger">Token expirado</span>
                                @elseif($account->isTokenExpiringSoon())
                                    <span class="badge bg-warning bg-opacity-15 text-warning">Expira em breve</span>
                                @else
                                    <span class="badge bg-light text-muted border" style="font-size:.65rem;">
                                        Expira {{ $account->token_expires_at->diffForHumans() }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top d-flex gap-2 px-4 py-2">
                    <a href="{{ route('social.posts.create') }}?account={{ $account->id }}" class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="fas fa-plus me-1"></i> Novo Post
                    </a>
                    <form action="{{ route('social.accounts.disconnect', $account) }}" method="POST" class="flex-fill">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-secondary w-100">Desconectar</button>
                    </form>
                    <form action="{{ route('social.accounts.destroy', $account) }}" method="POST"
                          onsubmit="return confirm('Remover esta conta e todos os posts agendados?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<style>
/* ════════════════════════════════════════════════
   PROMO BANNER — Criador de Banners
════════════════════════════════════════════════ */
.promo-banner-wrap {
    border-radius: 20px;
    overflow: hidden;
    position: relative;
}

.promo-banner-inner {
    background: #080c14;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    padding: 56px 52px 0;
    min-height: 340px;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(99, 102, 241, 0.18);
}

/* ── Parallax layers ── */
.promo-layer {
    position: absolute;
    pointer-events: none;
    will-change: transform;
    transition: transform 0.08s linear;
}

.promo-layer--orb1 {
    width: 480px; height: 480px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,0.32) 0%, transparent 70%);
    top: -160px; right: -60px;
}

.promo-layer--orb2 {
    width: 320px; height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124,58,237,0.22) 0%, transparent 70%);
    bottom: -80px; left: 120px;
}

.promo-layer--grid {
    inset: 0;
    background-image:
        linear-gradient(rgba(99,102,241,0.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99,102,241,0.07) 1px, transparent 1px);
    background-size: 40px 40px;
}

.promo-layer--lines {
    right: 260px; top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, rgba(99,102,241,0.25) 30%, rgba(99,102,241,0.25) 70%, transparent);
}

/* ── Content layout ── */
.promo-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    flex: 1;
}

/* ── Left column ── */
.promo-left { max-width: 520px; }

.promo-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #a5b4fc;
    margin-bottom: 18px;
}

.promo-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #6366f1;
    box-shadow: 0 0 10px #6366f1;
    animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 8px #6366f1; }
    50%       { box-shadow: 0 0 20px #6366f1, 0 0 40px rgba(99,102,241,.3); }
}

.promo-title {
    font-size: clamp(34px, 3.8vw, 54px);
    font-weight: 900;
    line-height: 1.05;
    color: #f8fafc;
    margin: 0 0 16px;
    letter-spacing: -1.5px;
}

.promo-title-accent {
    background: linear-gradient(90deg, #818cf8, #c084fc, #38bdf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    background-size: 200% auto;
    animation: shimmer-text 3s linear infinite;
}

@keyframes shimmer-text {
    from { background-position: 0% center; }
    to   { background-position: 200% center; }
}

.promo-subtitle {
    font-size: 15px;
    line-height: 1.7;
    color: #94a3b8;
    margin: 0 0 28px;
}

.promo-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.promo-cta-primary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: #6366f1;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    border-radius: 100px;
    text-decoration: none;
    transition: all .2s;
    box-shadow: 0 4px 24px rgba(99,102,241,.4);
    white-space: nowrap;
}

.promo-cta-primary:hover {
    background: #4f46e5;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 36px rgba(99,102,241,.55);
}

.promo-cta-primary svg { transition: transform .2s; }
.promo-cta-primary:hover svg { transform: translateX(4px); }

.promo-formats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.promo-formats span {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    padding: 4px 10px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 100px;
    transition: border-color .2s, color .2s;
}

.promo-formats span:hover {
    border-color: rgba(99,102,241,0.5);
    color: #a5b4fc;
}

/* ── Right column — Mockup ── */
.promo-right {
    flex-shrink: 0;
    padding-bottom: 0;
}

.promo-mockup {
    position: relative;
    width: 280px;
    height: 220px;
}

.mock-card {
    position: absolute;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
    transition: transform .4s cubic-bezier(.22,1,.36,1);
}

.mock-card--back {
    width: 200px; height: 130px;
    bottom: 10px; right: 0;
    background: #1e1b4b;
    padding: 18px;
    transform: rotate(6deg) translateY(10px);
    opacity: .75;
}

.mock-card--mid {
    width: 210px; height: 140px;
    bottom: 20px; right: 20px;
    transform: rotate(-3deg) translateY(5px);
    opacity: .85;
}

.mock-card--front {
    width: 230px; height: 155px;
    bottom: 30px; left: 0;
    transform: rotate(0deg);
    box-shadow: 0 20px 60px rgba(0,0,0,.6);
}

.promo-banner-wrap:hover .mock-card--back  { transform: rotate(8deg)  translateY(15px); }
.promo-banner-wrap:hover .mock-card--mid   { transform: rotate(-5deg) translateY(8px); }
.promo-banner-wrap:hover .mock-card--front { transform: rotate(0deg)  translateY(-6px); box-shadow: 0 30px 80px rgba(0,0,0,.7); }

.mock-bar {
    height: 6px;
    border-radius: 99px;
}

.mock-btn {
    width: 55px; height: 20px;
    border-radius: 99px;
    background: #6366f1;
    margin-top: 12px;
}

.mock-badge {
    position: absolute;
    bottom: 0; left: 50%;
    transform: translateX(-50%);
    background: rgba(99,102,241,.15);
    border: 1px solid rgba(99,102,241,.3);
    backdrop-filter: blur(12px);
    border-radius: 100px;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 700;
    color: #a5b4fc;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 6px;
    animation: float-badge 3s ease-in-out infinite;
}

@keyframes float-badge {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50%       { transform: translateX(-50%) translateY(-6px); }
}

/* ── Metrics footer ── */
.promo-metrics {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 0;
    border-top: 1px solid rgba(255,255,255,0.06);
    margin-top: 40px;
    padding: 20px 0;
}

.promo-metric {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 0 20px;
}

.promo-metric-num {
    font-size: 24px;
    font-weight: 900;
    color: #f1f5f9;
    letter-spacing: -0.5px;
}

.promo-metric-label {
    font-size: 11px;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: center;
}

.promo-metric-divider {
    width: 1px;
    height: 40px;
    background: rgba(255,255,255,0.07);
    flex-shrink: 0;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .promo-banner-inner { padding: 40px 32px 0; }
    .promo-right { display: none; }
    .promo-title { font-size: 36px; }
}

@media (max-width: 640px) {
    .promo-banner-inner { padding: 32px 24px 0; }
    .promo-metrics { gap: 0; }
    .promo-metric-num { font-size: 18px; }
    .promo-metric-label { font-size: 9px; }
    .promo-metric { padding: 0 10px; }
}
</style>

<script>
(function () {
    const banner = document.getElementById('promoBanner');
    if (!banner) return;

    const layers = banner.querySelectorAll('.promo-layer[data-depth]');

    let rafId;
    let mouseX = 0, mouseY = 0;
    let curX = 0, curY = 0;

    function onMove(e) {
        const rect = banner.getBoundingClientRect();
        mouseX = (e.clientX - rect.left - rect.width  / 2) / rect.width;
        mouseY = (e.clientY - rect.top  - rect.height / 2) / rect.height;
    }

    function animate() {
        curX += (mouseX - curX) * 0.08;
        curY += (mouseY - curY) * 0.08;

        layers.forEach(layer => {
            const depth = parseFloat(layer.dataset.depth);
            const tx = curX * depth * 40;
            const ty = curY * depth * 24;
            layer.style.transform = `translate(${tx}px, ${ty}px)`;
        });

        rafId = requestAnimationFrame(animate);
    }

    banner.addEventListener('mousemove', onMove);
    banner.addEventListener('mouseleave', () => { mouseX = 0; mouseY = 0; });

    animate();
})();
</script>
@endsection
