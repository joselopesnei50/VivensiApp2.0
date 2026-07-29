@extends('layouts.app')
@section('title', 'Analytics — Redes Sociais')

@push('styles')
<style>
    .an-page { max-width:1200px; margin:32px auto; padding:0 20px; }
    .an-hero { margin-bottom:24px; }
    .an-hero h1 { font-size:1.7rem; font-weight:800; color:#0f172a; margin:0 0 4px; }
    .an-hero p { color:#64748b; margin:0; font-size:.92rem; }

    .an-filter { display:inline-flex; background:#f1f5f9; border-radius:10px; padding:4px; gap:4px; margin-bottom:24px; }
    .an-filter a { padding:8px 18px; border-radius:8px; color:#475569; font-weight:600; font-size:.88rem; text-decoration:none; transition:all .15s; }
    .an-filter a.active { background:#10b981; color:#fff; }
    .an-filter a:hover:not(.active) { background:rgba(148,163,184,.15); }

    .an-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:14px; margin-bottom:28px; }
    .an-card { background:#fff; padding:18px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(15,23,42,.04); }
    .an-card .an-label { font-size:.7rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin:0 0 4px; }
    .an-card .an-value { font-size:1.8rem; font-weight:800; color:#0f172a; margin:0; line-height:1.1; }
    .an-card .an-icon { float:right; font-size:1.4rem; color:#e2e8f0; }

    .an-card.highlight { background:linear-gradient(135deg, #10b981 0%, #059669 100%); border:0; color:#fff; }
    .an-card.highlight .an-label { color:rgba(255,255,255,.75); }
    .an-card.highlight .an-value { color:#fff; }
    .an-card.highlight .an-icon { color:rgba(255,255,255,.4); }

    .an-networks { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px; }
    @media(max-width:800px) { .an-networks { grid-template-columns:1fr; } }
    .an-net-card { background:#fff; padding:20px; border-radius:14px; border:1px solid #e2e8f0; }
    .an-net-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
    .an-net-head i { font-size:1.4rem; }
    .an-net-head strong { color:#0f172a; font-size:1rem; font-weight:700; }
    .an-net-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; }
    .an-net-metric { text-align:center; padding:8px; background:#f8fafc; border-radius:8px; }
    .an-net-metric strong { display:block; color:#0f172a; font-size:1.05rem; font-weight:800; }
    .an-net-metric span { color:#94a3b8; font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; }

    .an-section-title { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 14px; padding-left:12px; border-left:3px solid #10b981; }

    .an-top-list { background:#fff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; }
    .an-top-item { display:grid; grid-template-columns:44px minmax(0, 1fr) auto; gap:12px; padding:14px 18px; align-items:center; border-bottom:1px solid #f1f5f9; }
    .an-top-item:last-child { border-bottom:0; }
    .an-top-rank { width:34px; height:34px; border-radius:8px; background:#f1f5f9; color:#64748b; font-weight:800; font-size:.95rem; display:flex; align-items:center; justify-content:center; }
    .an-top-rank.gold { background:#fef3c7; color:#a16207; }
    .an-top-caption { color:#0f172a; font-size:.9rem; line-height:1.4; margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .an-top-meta { color:#94a3b8; font-size:.75rem; margin-top:2px; }
    .an-top-metrics { text-align:right; }
    .an-top-metrics strong { color:#0f172a; font-weight:800; font-size:1rem; display:block; }
    .an-top-metrics span { color:#94a3b8; font-size:.72rem; }

    .an-empty { background:#fff; border-radius:14px; border:2px dashed #e2e8f0; padding:60px 24px; text-align:center; }
    .an-empty i { font-size:3rem; color:#cbd5e1; margin-bottom:16px; }
    .an-empty h3 { color:#0f172a; font-weight:700; margin:0 0 6px; }
    .an-empty p { color:#64748b; margin:0; }
</style>
@endpush

@php
    $fmt = fn ($n) => number_format($n ?? 0, 0, ',', '.');
@endphp

@section('content')
<div class="an-page">

    <div class="an-hero">
        <h1><i class="fas fa-chart-line" style="color:#10b981;"></i> Analytics — Redes Sociais</h1>
        <p>Métricas dos seus posts publicados no Facebook e Instagram. Atualizado a cada hora.</p>
    </div>

    <div class="an-filter">
        <a href="{{ route('social.analytics.index', ['days' => 7]) }}"  class="{{ $days === 7  ? 'active' : '' }}">7 dias</a>
        <a href="{{ route('social.analytics.index', ['days' => 30]) }}" class="{{ $days === 30 ? 'active' : '' }}">30 dias</a>
        <a href="{{ route('social.analytics.index', ['days' => 90]) }}" class="{{ $days === 90 ? 'active' : '' }}">90 dias</a>
    </div>

    {{-- Cards de resumo --}}
    <div class="an-summary">
        <div class="an-card highlight">
            <i class="fas fa-chart-bar an-icon"></i>
            <p class="an-label">Impressões</p>
            <p class="an-value">{{ $fmt($totals['impressions']) }}</p>
        </div>
        <div class="an-card">
            <i class="fas fa-users an-icon"></i>
            <p class="an-label">Alcance</p>
            <p class="an-value">{{ $fmt($totals['reach']) }}</p>
        </div>
        <div class="an-card">
            <i class="fas fa-heart an-icon" style="color:#ec4899;"></i>
            <p class="an-label">Curtidas</p>
            <p class="an-value">{{ $fmt($totals['likes']) }}</p>
        </div>
        <div class="an-card">
            <i class="fas fa-comment an-icon" style="color:#3b82f6;"></i>
            <p class="an-label">Comentários</p>
            <p class="an-value">{{ $fmt($totals['comments']) }}</p>
        </div>
        <div class="an-card">
            <i class="fas fa-share an-icon"></i>
            <p class="an-label">Compartilhamentos</p>
            <p class="an-value">{{ $fmt($totals['shares']) }}</p>
        </div>
        <div class="an-card">
            <i class="fas fa-newspaper an-icon"></i>
            <p class="an-label">Posts no período</p>
            <p class="an-value">{{ $fmt($totals['posts']) }}</p>
        </div>
    </div>

    @if($totals['posts'] === 0)
        <div class="an-empty">
            <i class="fas fa-chart-line"></i>
            <h3>Ainda sem posts publicados no período</h3>
            <p>As métricas aparecem aqui automaticamente a cada 1h depois que um post é publicado.</p>
        </div>
    @else
        {{-- Comparativo por rede --}}
        <h3 class="an-section-title">Por rede</h3>
        <div class="an-networks">
            @php
                $fb = $byNetwork->get('facebook');
                $ig = $byNetwork->get('instagram');
            @endphp

            <div class="an-net-card">
                <div class="an-net-head">
                    <i class="fab fa-facebook" style="color:#1877f2;"></i>
                    <strong>Facebook</strong>
                    <span style="margin-left:auto; color:#94a3b8; font-size:.82rem;">{{ $fmt($fb->posts ?? 0) }} posts</span>
                </div>
                <div class="an-net-grid">
                    <div class="an-net-metric"><strong>{{ $fmt($fb->impressions ?? 0) }}</strong><span>Impressões</span></div>
                    <div class="an-net-metric"><strong>{{ $fmt($fb->reach ?? 0) }}</strong><span>Alcance</span></div>
                    <div class="an-net-metric"><strong>{{ $fmt($fb->engagement ?? 0) }}</strong><span>Engaj.</span></div>
                </div>
            </div>

            <div class="an-net-card">
                <div class="an-net-head">
                    <i class="fab fa-instagram" style="color:#e4405f;"></i>
                    <strong>Instagram</strong>
                    <span style="margin-left:auto; color:#94a3b8; font-size:.82rem;">{{ $fmt($ig->posts ?? 0) }} posts</span>
                </div>
                <div class="an-net-grid">
                    <div class="an-net-metric"><strong>{{ $fmt($ig->impressions ?? 0) }}</strong><span>Impressões</span></div>
                    <div class="an-net-metric"><strong>{{ $fmt($ig->reach ?? 0) }}</strong><span>Alcance</span></div>
                    <div class="an-net-metric"><strong>{{ $fmt($ig->engagement ?? 0) }}</strong><span>Engaj.</span></div>
                </div>
            </div>
        </div>

        {{-- Top 5 posts --}}
        <h3 class="an-section-title">Top 5 posts por engajamento</h3>
        @if($topPosts->isEmpty())
            <div class="an-empty">
                <i class="fas fa-trophy"></i>
                <h3>Métricas ainda sendo coletadas</h3>
                <p>A Meta demora um pouco pra publicar as métricas. Volte em 1-2h.</p>
            </div>
        @else
            <div class="an-top-list">
                @foreach($topPosts as $idx => $row)
                    @php $post = $row['post']; @endphp
                    <div class="an-top-item">
                        <div class="an-top-rank {{ $idx === 0 ? 'gold' : '' }}">#{{ $idx + 1 }}</div>
                        <div>
                            <p class="an-top-caption">{{ $post->caption }}</p>
                            <div class="an-top-meta">
                                {{ $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                                &middot;
                                {{ $post->account?->page_name ?? 'Sem conta' }}
                                &middot;
                                <i class="fas fa-eye"></i> {{ $fmt($row['reach']) }} alcance
                                &middot;
                                <i class="fas fa-heart" style="color:#ec4899;"></i> {{ $fmt($row['likes']) }}
                                &middot;
                                <i class="fas fa-comment" style="color:#3b82f6;"></i> {{ $fmt($row['comments']) }}
                            </div>
                        </div>
                        <div class="an-top-metrics">
                            <strong>{{ $fmt($row['engagement']) }}</strong>
                            <span>engajamentos</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

</div>
@endsection
