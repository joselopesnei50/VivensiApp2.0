@extends('layouts.app', ['title' => 'Social AI Hub'])

@push('styles')
<style>
/* ── Base & Background ──────────────────────────── */
.sai-wrapper {
    min-height: 100vh;
    background: #0a0f1a;
    padding: 32px;
}
.sai-content { position: relative; }

/* ── Header ─────────────────────────────────────── */
.sai-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}
.sai-title-block h1 {
    font-size: 2.6rem;
    font-weight: 900;
    letter-spacing: -1.5px;
    color: #fff;
    margin: 0;
    line-height: 1;
}
.sai-title-block h1 span {
    color: var(--primary-color, #4F46E5);
}
.sai-title-block p {
    color: rgba(255,255,255,0.4);
    margin: 8px 0 0;
    font-size: 0.95rem;
}

/* ── Quota Card ─────────────────────────────────── */
.sai-quota-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    padding: 20px 28px;
    min-width: 240px;
}
.sai-quota-card .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: rgba(255,255,255,0.35);
    font-weight: 700;
    margin-bottom: 6px;
}
.sai-quota-card .numbers {
    font-size: 1.8rem;
    font-weight: 900;
    color: #fff;
    line-height: 1;
}
.sai-quota-card .numbers small {
    font-size: 1rem;
    font-weight: 400;
    color: rgba(255,255,255,0.25);
}
.sai-quota-bar {
    width: 100%;
    height: 5px;
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
    margin-top: 12px;
    overflow: hidden;
}
.sai-quota-bar-fill {
    height: 100%;
    border-radius: 10px;
    background: var(--primary-color, #4F46E5);
    transition: width 1s ease;
}
@keyframes shimmer {
    0%   { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}

/* ── Generator Card ─────────────────────────────── */
.sai-generator {
    background: rgba(255,255,255,0.035);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 28px;
    padding: 40px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
}
.sai-generator::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(99,102,241,0.5), rgba(168,85,247,0.5), transparent);
}
.sai-generator-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: rgba(255,255,255,0.9);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sai-generator-title .icon {
    width: 36px;
    height: 36px;
    background: var(--primary-color, #4F46E5);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}
.sai-input-row {
    display: flex;
    gap: 12px;
    align-items: stretch;
}
.sai-input {
    flex: 1;
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 18px 24px;
    color: #fff;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.sai-input::placeholder { color: rgba(255,255,255,0.25); }
.sai-input:focus {
    border-color: rgba(79,70,229,0.6);
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}
.sai-btn-generate {
    background: var(--primary-color, #4F46E5);
    border: none;
    border-radius: 16px;
    padding: 18px 32px;
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(79,70,229,0.3);
}
.sai-btn-generate:hover:not(:disabled) {
    transform: translateY(-2px);
    filter: brightness(1.1);
    box-shadow: 0 12px 32px rgba(79,70,229,0.45);
}
.sai-btn-generate:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.sai-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}
.sai-tags .label-hint {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.3);
    align-self: center;
    margin-right: 4px;
}
.sai-tag {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.78rem;
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.sai-tag:hover {
    background: rgba(79,70,229,0.15);
    border-color: rgba(79,70,229,0.4);
    color: var(--primary-light, #818CF8);
}

/* ── Section Title ──────────────────────────────── */
.sai-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sai-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.06);
}

/* ── Post Card ──────────────────────────────────── */
.sai-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 24px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}
.sai-card:hover {
    transform: translateY(-6px);
    border-color: rgba(79,70,229,0.35);
    box-shadow: 0 24px 48px rgba(0,0,0,0.4);
}

/* ── Card Image Area ──────────────────────────────*/
.sai-card-img {
    position: relative;
    aspect-ratio: 1/1;
    overflow: hidden;
    background: #111;
}
.sai-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.sai-card:hover .sai-card-img img {
    transform: scale(1.04);
}
.sai-card-img .img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(7,7,15,0.8) 0%, transparent 50%);
}

/* ── Skeleton Loading ───────────────────────────── */
.sai-skeleton {
    aspect-ratio: 1/1;
    background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.04) 75%);
    background-size: 400px 100%;
    animation: shimmer 1.5s infinite linear;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.sai-skeleton .sk-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid rgba(99,102,241,0.4);
    border-top-color: #6366f1;
    animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.sai-skeleton .sk-text {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.3);
    font-weight: 500;
}

/* ── Status Badge ───────────────────────────────── */
.sai-badge {
    position: absolute;
    top: 14px;
    left: 14px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.sai-badge.draft      { background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.sai-badge.processing { background: rgba(79,70,229,0.2); color: var(--primary-light, #818CF8); border: 1px solid rgba(79,70,229,0.4); animation: pulseBadge 2s infinite; }
.sai-badge.failed     { background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.sai-badge.published  { background: rgba(14,165,233,0.2); color: #38bdf8; border: 1px solid rgba(14,165,233,0.3); }
.sai-badge.scheduled  { background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
@keyframes pulseBadge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ── Download Button (on hover) ─────────────────── */
.sai-img-actions {
    position: absolute;
    top: 14px;
    right: 14px;
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.3s;
}
.sai-card:hover .sai-img-actions { opacity: 1; }
.sai-img-btn {
    width: 34px;
    height: 34px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75rem;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.1);
    transition: background 0.2s;
    text-decoration: none;
}
.sai-img-btn:hover { background: rgba(79,70,229,0.6); color: #fff; }

/* ── Card Body ──────────────────────────────────── */
.sai-card-body {
    padding: 22px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.sai-card-theme {
    font-size: 0.9rem;
    font-weight: 700;
    color: rgba(255,255,255,0.9);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sai-card-caption {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.4);
    line-height: 1.6;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sai-card-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}
.sai-card-row { display: flex; gap: 8px; }
.sai-action-btn {
    flex: 1;
    padding: 10px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.sai-action-btn.copy {
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.7);
    border: 1px solid rgba(255,255,255,0.1);
}
.sai-action-btn.copy:hover { background: rgba(255,255,255,0.12); color: #fff; }
.sai-action-btn.expand {
    background: rgba(79,70,229,0.12);
    color: var(--primary-light, #818CF8);
    border: 1px solid rgba(79,70,229,0.2);
}
.sai-action-btn.expand:hover { background: rgba(79,70,229,0.22); }
.sai-action-btn.whatsapp {
    background: rgba(37,211,102,0.12);
    color: #4ade80;
    border: 1px solid rgba(37,211,102,0.2);
    width: 100%;
}
.sai-action-btn.whatsapp:hover { background: rgba(37,211,102,0.22); }
.sai-action-btn.delete {
    background: rgba(239,68,68,0.07);
    color: rgba(248,113,113,0.7);
    border: 1px solid rgba(239,68,68,0.15);
    width: 100%;
    font-size: 0.73rem;
}
.sai-action-btn.delete:hover { background: rgba(239,68,68,0.15); color: #f87171; }

/* ── Empty State ─────────────────────────────────── */
.sai-empty {
    text-align: center;
    padding: 80px 20px;
    color: rgba(255,255,255,0.2);
}
.sai-empty .empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}
.sai-empty p { font-size: 1rem; margin: 0; }

/* ── Toast Notification ─────────────────────────── */
#sai-toast-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.sai-toast {
    background: rgba(20,20,35,0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 16px 22px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 500;
    min-width: 300px;
    max-width: 380px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInToast 0.4s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
.sai-toast.success { border-left: 3px solid #34d399; }
.sai-toast.error   { border-left: 3px solid #f87171; }
.sai-toast.info    { border-left: 3px solid #a5b4fc; }
.sai-toast .t-icon { font-size: 1.1rem; flex-shrink: 0; }
@keyframes slideInToast {
    from { transform: translateX(120%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}

/* ── Modal ──────────────────────────────────────── */
.sai-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    z-index: 9000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.sai-modal-backdrop.open { display: flex; }
.sai-modal {
    background: #0f1120;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 28px;
    width: 100%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 36px;
    position: relative;
    animation: modalIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes modalIn {
    from { transform: scale(0.85) translateY(20px); opacity: 0; }
    to   { transform: scale(1) translateY(0);        opacity: 1; }
}
.sai-modal-close {
    position: absolute;
    top: 18px; right: 18px;
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.06);
    border: none;
    border-radius: 10px;
    color: rgba(255,255,255,0.5);
    cursor: pointer;
    font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.sai-modal-close:hover { background: rgba(255,255,255,0.12); color: #fff; }
.sai-modal img { width: 100%; border-radius: 16px; margin-bottom: 24px; }
.sai-modal h4 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 12px; }
.sai-modal-caption {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 16px;
    color: rgba(255,255,255,0.7);
    font-size: 0.88rem;
    line-height: 1.7;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
}
.sai-modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.sai-modal-btn {
    flex: 1;
    padding: 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.sai-modal-btn.primary {
    background: var(--primary-color, #4F46E5);
    color: #fff;
}
.sai-modal-btn.primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
.sai-modal-btn.secondary {
    background: rgba(37,211,102,0.12);
    color: #4ade80;
    border: 1px solid rgba(37,211,102,0.2);
}
.sai-modal-btn.secondary:hover { background: rgba(37,211,102,0.22); }

/* ── Failed State ───────────────────────────────── */
.sai-failed-img {
    aspect-ratio: 1/1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(239,68,68,0.05);
    color: rgba(248,113,113,0.6);
    font-size: 0.8rem;
    text-align: center;
    padding: 20px;
}
.sai-failed-img i { font-size: 2.5rem; opacity: 0.5; }

/* ── Pagination override ─────────────────────────── */
.sai-pagination .page-link {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.5);
    border-radius: 10px !important;
    margin: 0 3px;
    transition: all 0.2s;
}
.sai-pagination .page-item.active .page-link {
    background: var(--primary-color, #4F46E5);
    border-color: transparent;
    color: #fff;
}
.sai-pagination .page-link:hover { background: rgba(79,70,229,0.2); color: var(--primary-light, #818CF8); }
</style>
@endpush

@section('content')
<div class="sai-wrapper">
<div class="sai-content">

    {{-- ── HEADER ──────────────────────────────── --}}
    <div class="sai-header">
        <div class="sai-title-block">
            <h1>Social <span>AI Hub</span></h1>
            <p>Gere posts estratégicos com texto e imagem em segundos, direto pela IA.</p>
        </div>
        <div class="sai-quota-card">
            <div class="label">Imagens geradas este mês</div>
            <div class="numbers">
                {{ $quotaUsed }} <small>/ 60</small>
            </div>
            <div class="sai-quota-bar">
                <div class="sai-quota-bar-fill" style="width: {{ min(($quotaUsed / 60) * 100, 100) }}%"></div>
            </div>
        </div>
    </div>

    {{-- ── GENERATOR ────────────────────────────── --}}
    <div class="sai-generator">
        <div class="sai-generator-title">
            <div class="icon"><i class="fas fa-wand-magic-sparkles"></i></div>
            Sobre o que vamos postar hoje?
        </div>
        <div class="sai-input-row">
            <input type="text" id="postTheme" class="sai-input"
                placeholder="Ex: Captação de recursos para ONGs em 2026, dicas práticas..."
                maxlength="500"
                onkeydown="if(event.key==='Enter') generatePost()">
            <button id="btnGenerate" class="sai-btn-generate" onclick="generatePost()">
                <i class="fas fa-magic" id="btnIcon"></i>
                <span id="btnText">Gerar Post</span>
            </button>
        </div>
        <div class="sai-tags">
            <span class="label-hint">Sugestões:</span>
            @php $role = auth()->user()->role ?? 'common'; @endphp

            @if($role === 'ngo')
                {{-- ONG / Terceiro Setor --}}
                <span class="sai-tag" onclick="setTheme('Impacto real do nosso projeto na comunidade')">Impacto do projeto</span>
                <span class="sai-tag" onclick="setTheme('Como fazer uma doação e transformar vidas')">Captação de doadores</span>
                <span class="sai-tag" onclick="setTheme('Transparência e prestação de contas da nossa ONG')">Transparência</span>
                <span class="sai-tag" onclick="setTheme('Chamada para novos voluntários — venha fazer parte!')">Chamada de voluntários</span>
                <span class="sai-tag" onclick="setTheme('Resultado da nossa última campanha social')">Resultado de campanha</span>
                <span class="sai-tag" onclick="setTheme('Edital aprovado: o que isso significa para a comunidade')">Edital aprovado</span>

            @elseif(in_array($role, ['manager', 'super_admin']))
                {{-- Gestor de Projetos --}}
                <span class="sai-tag" onclick="setTheme('Resultado alcançado no trimestre — nossa equipe entregou!')">Resultado do trimestre</span>
                <span class="sai-tag" onclick="setTheme('Lançamento de novo projeto estratégico')">Lançamento de projeto</span>
                <span class="sai-tag" onclick="setTheme('Como a tecnologia está transformando nossa gestão')">Gestão e tecnologia</span>
                <span class="sai-tag" onclick="setTheme('Destaque do profissional da semana na nossa equipe')">Destaque da equipe</span>
                <span class="sai-tag" onclick="setTheme('Meta batida! Celebrando a conquista da nossa equipe')">Meta alcançada</span>
                <span class="sai-tag" onclick="setTheme('Convite para parceria estratégica com nossa empresa')">Parceria estratégica</span>

            @else
                {{-- MEI / Empreendedor --}}
                <span class="sai-tag" onclick="setTheme('Promoção especial do meu produto ou serviço')">Promoção especial</span>
                <span class="sai-tag" onclick="setTheme('Novidade na minha loja — confira o que chegou!')">Novidade na loja</span>
                <span class="sai-tag" onclick="setTheme('Dica rápida para clientes do meu negócio')">Dica para clientes</span>
                <span class="sai-tag" onclick="setTheme('Depoimento real de cliente satisfeito com meu serviço')">Depoimento de cliente</span>
                <span class="sai-tag" onclick="setTheme('Lançamento de novo produto ou serviço da minha empresa')">Lançamento de serviço</span>
                <span class="sai-tag" onclick="setTheme('Bastidores do meu negócio — como tudo acontece por aqui')">Bastidores do negócio</span>
            @endif
        </div>
    </div>

    {{-- ── POSTS GRID ───────────────────────────── --}}
    @if($posts->total() > 0)
    <div class="sai-section-title">
        <i class="fas fa-layer-group" style="color:var(--primary-color,#4F46E5)"></i>
        Seus Rascunhos ({{ $posts->total() }})
    </div>
    @endif

    <div class="row g-4" id="postsGrid">
        @forelse($posts as $post)
        <div class="col-md-6 col-xl-4" id="postCard-{{ $post->id }}">
            <div class="sai-card">

                {{-- Image / Skeleton / Failed --}}
                @if($post->status === 'processing')
                    <div class="sai-skeleton">
                        <div class="sk-icon"></div>
                        <div class="sk-text">Gerando com IA...</div>
                    </div>
                @elseif($post->status === 'failed')
                    <div class="sai-failed-img">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Falha na geração</span>
                        @if($post->error_message)
                        <small style="opacity:.6; font-size:.7rem;">{{ Str::limit($post->error_message, 80) }}</small>
                        @endif
                    </div>
                @else
                    <div class="sai-card-img">
                        @if($post->image_path)
                            <img src="{{ Storage::disk('public')->url($post->image_path) }}" alt="{{ $post->title_theme }}" loading="lazy">
                        @else
                            <img src="https://placehold.co/600x600/111827/374151?text=Sem+Imagem" alt="Sem imagem">
                        @endif
                        <div class="img-overlay"></div>
                        <div class="sai-img-actions">
                            @if($post->image_path)
                            <a href="{{ Storage::disk('public')->url($post->image_path) }}" download class="sai-img-btn" title="Baixar imagem">
                                <i class="fas fa-download"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Status Badge --}}
                <div class="sai-badge {{ $post->status }}" style="top:14px;left:14px">
                    @if($post->status === 'processing')
                        <i class="fas fa-circle-notch fa-spin" style="margin-right:5px"></i>
                    @elseif($post->status === 'draft')
                        <i class="fas fa-circle-check" style="margin-right:5px"></i>
                    @elseif($post->status === 'failed')
                        <i class="fas fa-circle-xmark" style="margin-right:5px"></i>
                    @elseif($post->status === 'published')
                        <i class="fas fa-paper-plane" style="margin-right:5px"></i>
                    @endif
                    {{ ucfirst($post->status === 'draft' ? 'Pronto' : $post->status) }}
                </div>

                {{-- Body --}}
                <div class="sai-card-body">
                    <div class="sai-card-theme">{{ $post->title_theme }}</div>

                    @if($post->body_text)
                    <div class="sai-card-caption">{{ $post->body_text }}</div>
                    @elseif($post->status === 'processing')
                    <div class="sai-card-caption" style="font-style:italic; opacity:.4">Gerando legenda...</div>
                    @endif

                    <div class="sai-card-actions">
                        @if($post->status !== 'processing')
                        <div class="sai-card-row">
                            <button class="sai-action-btn copy" onclick="copyCaption({{ $post->id }}, `{{ addslashes($post->body_text) }}`)" {{ !$post->body_text ? 'disabled' : '' }}>
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <button class="sai-action-btn expand" onclick="openModal({{ $post->id }}, `{{ addslashes($post->title_theme) }}`, `{{ addslashes($post->body_text) }}`, `{{ $post->image_path ? Storage::disk('public')->url($post->image_path) : '' }}`)">
                                <i class="fas fa-expand"></i> Ver
                            </button>
                        </div>
                        <button class="sai-action-btn whatsapp" onclick="sendWhatsApp(`{{ addslashes($post->body_text) }}`)">
                            <i class="fab fa-whatsapp"></i> Enviar via WhatsApp
                        </button>
                        @endif
                        <button class="sai-action-btn delete" onclick="deletePost({{ $post->id }})">
                            <i class="fas fa-trash-alt"></i> Excluir Rascunho
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 sai-empty">
            <div class="empty-icon"><i class="fas fa-robot"></i></div>
            <p>Nenhum post gerado ainda.<br>
            <span style="font-size:.85rem; opacity:.6">Digite um tema acima e deixe a IA trabalhar por você.</span></p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
    <div class="d-flex justify-content-center mt-5 sai-pagination">
        {{ $posts->links() }}
    </div>
    @endif

</div>
</div>

{{-- ── MODAL ────────────────────────────────────── --}}
<div class="sai-modal-backdrop" id="postModal" onclick="if(event.target===this) closeModal()">
    <div class="sai-modal">
        <button class="sai-modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <img id="modalImg" src="" alt="" style="display:none">
        <h4 id="modalTheme"></h4>
        <div class="sai-modal-caption" id="modalCaption"></div>
        <div class="sai-modal-actions">
            <button class="sai-modal-btn primary" onclick="copyModalCaption()">
                <i class="fas fa-copy"></i> Copiar Legenda
            </button>
            <button class="sai-modal-btn secondary" onclick="sendWhatsAppModal()">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
        </div>
    </div>
</div>

{{-- ── TOAST CONTAINER ─────────────────────────── --}}
<div id="sai-toast-container"></div>

@push('scripts')
<script>
const CSRF  = '{{ csrf_token() }}';
const ROUTE_GENERATE = '{{ route("social-ai.generate") }}';
const ROUTE_STATUS   = (id) => `/social-ai/${id}/status`;
const ROUTE_DESTROY  = (id) => `/social-ai/${id}`;

// ── Toast ──────────────────────────────────────────
function toast(msg, type = 'info') {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const el = document.createElement('div');
    el.className = `sai-toast ${type}`;
    el.innerHTML = `<span class="t-icon">${icons[type]}</span><span>${msg}</span>`;
    document.getElementById('sai-toast-container').appendChild(el);
    setTimeout(() => el.remove(), 4500);
}

// ── Theme suggestions ──────────────────────────────
function setTheme(text) {
    document.getElementById('postTheme').value = text;
    document.getElementById('postTheme').focus();
}

// ── Generate ───────────────────────────────────────
async function generatePost() {
    const theme = document.getElementById('postTheme').value.trim();
    if (!theme) { toast('Digite um tema para gerar o post.', 'error'); return; }

    const btn  = document.getElementById('btnGenerate');
    const icon = document.getElementById('btnIcon');
    const text = document.getElementById('btnText');

    btn.disabled  = true;
    icon.className = 'fas fa-circle-notch fa-spin';
    text.textContent = 'Iniciando...';

    try {
        const res  = await fetch(ROUTE_GENERATE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ theme }),
        });
        const data = await res.json();

        if (data.success) {
            toast(data.message, 'success');
            document.getElementById('postTheme').value = '';
            setTimeout(() => location.reload(), 1200);
        } else {
            toast(data.message || 'Erro ao iniciar geração.', 'error');
        }
    } catch (e) {
        toast('Falha na comunicação com o servidor.', 'error');
    } finally {
        btn.disabled  = false;
        icon.className = 'fas fa-magic';
        text.textContent = 'Gerar Post';
    }
}

// ── Copy caption ───────────────────────────────────
function copyCaption(id, text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => toast('Legenda copiada!', 'success'));
}

// ── WhatsApp share ─────────────────────────────────
function sendWhatsApp(text) {
    if (!text) return;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

// ── Modal ──────────────────────────────────────────
let _modalCaption = '', _modalImg = '';
function openModal(id, theme, caption, imgUrl) {
    _modalCaption = caption;
    _modalImg     = imgUrl;
    document.getElementById('modalTheme').textContent   = theme;
    document.getElementById('modalCaption').textContent = caption || '—';
    const img = document.getElementById('modalImg');
    if (imgUrl) { img.src = imgUrl; img.style.display = 'block'; }
    else         { img.style.display = 'none'; }
    document.getElementById('postModal').classList.add('open');
}
function closeModal() {
    document.getElementById('postModal').classList.remove('open');
}
function copyModalCaption() {
    navigator.clipboard.writeText(_modalCaption).then(() => { toast('Legenda copiada!', 'success'); closeModal(); });
}
function sendWhatsAppModal() {
    sendWhatsApp(_modalCaption);
}

// ── Delete ─────────────────────────────────────────
async function deletePost(id) {
    if (!confirm('Excluir este rascunho permanentemente?')) return;
    try {
        const res  = await fetch(ROUTE_DESTROY(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            const card = document.getElementById(`postCard-${id}`);
            if (card) { card.style.transition = 'opacity .4s'; card.style.opacity = '0'; setTimeout(() => card.remove(), 400); }
            toast('Rascunho excluído.', 'info');
        } else {
            toast('Erro ao excluir.', 'error');
        }
    } catch (e) {
        toast('Falha na comunicação.', 'error');
    }
}

// ── Polling for processing posts ───────────────────
@php $processingIds = $posts->where('status', 'processing')->pluck('id')->toArray(); @endphp
@if(count($processingIds) > 0)
const processingIds = @json($processingIds);
let pollAttempts = {};

async function pollPost(id) {
    try {
        const res  = await fetch(ROUTE_STATUS(id), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (data.status !== 'processing') {
            // Reload the page to show the new card with proper content
            toast('Novo post gerado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            pollAttempts[id] = (pollAttempts[id] || 0) + 1;
            if (pollAttempts[id] < 24) { // max ~4 min polling
                setTimeout(() => pollPost(id), 10000);
            }
        }
    } catch (e) {
        setTimeout(() => pollPost(id), 15000);
    }
}

processingIds.forEach(id => { pollAttempts[id] = 0; setTimeout(() => pollPost(id), 10000); });
@endif
</script>
@endpush

@endsection
