@extends('layouts.app')
@section('title', 'Plano Estratégico')

@php
    // Parse do markdown gerado pelo DeepSeek em blocos executivos.
    // Cada H2 vira um card na aba "Plano Executivo". H3s viram sub-tópicos.
    $md = $marketing->mindmap_data['markdown'] ?? '';
    $rootTitle = null;
    $sections  = []; // [{h2, h3s: [{title, bullets: []}]}]
    $currentH2 = null;
    $currentH3 = null;

    foreach (preg_split("/\r?\n/", $md) as $line) {
        $trimmed = rtrim($line);
        if ($trimmed === '') continue;

        if (preg_match('/^#\s+(.+)$/', $trimmed, $m) && !$rootTitle) {
            $rootTitle = trim($m[1]);
        } elseif (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
            if ($currentH2) $sections[] = $currentH2;
            $currentH2 = ['title' => trim($m[1]), 'h3s' => []];
            $currentH3 = null;
        } elseif (preg_match('/^###\s+(.+)$/', $trimmed, $m) && $currentH2) {
            $currentH3 = ['title' => trim($m[1]), 'bullets' => []];
            $currentH2['h3s'][] = &$currentH3;
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $line, $m) && $currentH2) {
            if ($currentH3 !== null) {
                $currentH3['bullets'][] = trim($m[1]);
            } elseif (!empty($currentH2['h3s'])) {
                // bullet direto no H2 sem H3 — vai pro último H3
                $lastKey = count($currentH2['h3s']) - 1;
                $currentH2['h3s'][$lastKey]['bullets'][] = trim($m[1]);
            } else {
                // bullet no H2 sem nenhum H3 ainda — cria pseudo-h3 "Pontos-chave"
                $currentH2['h3s'][] = ['title' => 'Pontos-chave', 'bullets' => [trim($m[1])]];
                $currentH3 = &$currentH2['h3s'][count($currentH2['h3s']) - 1];
            }
        }
    }
    if ($currentH2) $sections[] = $currentH2;
    unset($currentH3);

    $sectionIcons = [
        'posicion'  => 'fa-bullseye',
        'whatsapp'  => 'fa-brands fa-whatsapp',
        'redes'     => 'fa-hashtag',
        'social'    => 'fa-hashtag',
        'captac'    => 'fa-magnet',
        'lead'      => 'fa-magnet',
        'copy'      => 'fa-feather',
        'metric'    => 'fa-chart-line',
        'kpi'       => 'fa-chart-line',
        'plano'     => 'fa-flag-checkered',
        '72h'       => 'fa-flag-checkered',
        'persona'   => 'fa-users',
        'orcamento' => 'fa-coins',
        'agenda'    => 'fa-calendar-days',
        'default'   => 'fa-lightbulb',
    ];
    $iconFor = function (string $title) use ($sectionIcons) {
        $t = mb_strtolower($title);
        foreach ($sectionIcons as $key => $ico) {
            if ($key !== 'default' && str_contains($t, $key)) return $ico;
        }
        return $sectionIcons['default'];
    };
@endphp

@push('styles')
<style>
/* ══════════ HEADER ══════════ */
.mkt-head {
    background: #0A0A0B;
    border: 1px solid rgba(255,122,26,.15);
    border-radius: 24px;
    padding: 28px 32px;
    margin-bottom: 20px;
    color: #fff;
}
.mkt-head-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.mkt-back {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,.5); font-size: .78rem; font-weight: 700;
    text-decoration: none; padding: 6px 12px;
    border: 1px solid rgba(255,255,255,.08); border-radius: 8px;
    transition: color .15s, background .15s;
}
.mkt-back:hover { color: #fff; background: rgba(255,255,255,.05); }
.mkt-title-block { flex: 1; min-width: 260px; }
.mkt-title {
    font-size: 1.6rem; font-weight: 900; color: #fff;
    margin: 12px 0 6px; letter-spacing: -.4px; line-height: 1.2;
}
.mkt-subtitle {
    color: rgba(255,255,255,.55); font-size: .85rem;
    margin: 0 0 14px; line-height: 1.55; max-width: 720px;
}
.mkt-briefing {
    display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;
}
.mkt-pill {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    color: rgba(255,255,255,.75);
    font-size: .72rem; font-weight: 600;
    padding: 5px 12px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 6px;
}
.mkt-pill i { color: #FF7A1A; font-size: .68rem; }

/* ══════════ TABS ══════════ */
.mkt-tabs {
    display: flex; gap: 6px; padding: 6px;
    background: #f1f5f9; border-radius: 14px;
    margin-bottom: 20px;
}
.mkt-tab {
    flex: 1; padding: 12px 20px; text-align: center;
    background: transparent; border: none; border-radius: 10px;
    font-size: .88rem; font-weight: 700; color: #64748b;
    cursor: pointer; transition: background .18s, color .18s;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
}
.mkt-tab:hover { color: #0f172a; }
.mkt-tab.active {
    background: #fff; color: #0f172a;
    box-shadow: 0 1px 3px rgba(15,23,42,.08);
}
.mkt-tab .badge-count {
    background: #FF7A1A; color: #fff;
    font-size: .65rem; font-weight: 800;
    padding: 2px 8px; border-radius: 10px;
    letter-spacing: .05em;
}
.mkt-panel { display: none; }
.mkt-panel.active { display: block; }

/* ══════════ PLANO EXECUTIVO — CARDS + SETAS ══════════ */
.mkt-flow { display: flex; flex-direction: column; align-items: center; gap: 0; }
.mkt-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 20px; padding: 28px 32px;
    width: 100%; max-width: 820px;
    position: relative; transition: border-color .18s;
}
.mkt-card:hover { border-color: #FF7A1A; }
.mkt-card-head {
    display: flex; align-items: center; gap: 14px; margin-bottom: 18px;
    padding-bottom: 16px; border-bottom: 1px dashed #e2e8f0;
}
.mkt-card-num {
    width: 40px; height: 40px; border-radius: 12px;
    background: #FF7A1A; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: .95rem; flex-shrink: 0;
}
.mkt-card-ico {
    color: #FF7A1A; font-size: 1.05rem;
}
.mkt-card-title {
    font-size: 1.05rem; font-weight: 800; color: #0f172a;
    letter-spacing: -.2px; flex: 1; line-height: 1.3;
}
.mkt-card-body {
    display: flex; flex-direction: column; gap: 14px;
}
.mkt-h3 {
    padding: 14px 16px;
    background: #f8fafc; border: 1px solid #f1f5f9;
    border-radius: 12px;
}
.mkt-h3-title {
    font-size: .85rem; font-weight: 800; color: #334155;
    margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
}
.mkt-h3-title::before {
    content: ''; display: inline-block;
    width: 6px; height: 6px; border-radius: 50%;
    background: #FF7A1A;
}
.mkt-bullets {
    list-style: none; margin: 0; padding: 0;
    display: flex; flex-direction: column; gap: 6px;
}
.mkt-bullets li {
    font-size: .8rem; color: #475569; line-height: 1.55;
    padding-left: 18px; position: relative;
}
.mkt-bullets li::before {
    content: '›'; position: absolute; left: 4px; top: 0;
    color: #cbd5e1; font-weight: 800;
}
.mkt-bullets li strong { color: #0f172a; }
.mkt-card-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 18px; padding-top: 16px; border-top: 1px dashed #e2e8f0;
}
.mkt-h3-count {
    font-size: .7rem; color: #94a3b8; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
}
.mkt-card-expand {
    background: transparent; border: 1px solid #e2e8f0;
    color: #64748b; font-size: .72rem; font-weight: 700;
    padding: 6px 12px; border-radius: 8px; cursor: pointer;
    transition: background .15s;
}
.mkt-card-expand:hover { background: #f8fafc; color: #0f172a; }
.mkt-card.collapsed .mkt-card-body .mkt-h3:nth-child(n+3) { display: none; }

/* Seta conectora entre cards */
.mkt-arrow {
    width: 2px; height: 40px;
    background: #e2e8f0;
    position: relative;
    margin: 0 auto;
}
.mkt-arrow::after {
    content: ''; position: absolute;
    bottom: -2px; left: 50%; transform: translateX(-50%);
    width: 0; height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 8px solid #cbd5e1;
}

/* ══════════ GUIA DO BRUCE ══════════ */
.bruce-shell { max-width: 900px; margin: 0 auto; }
.bruce-intro {
    background: #0A0A0B;
    border: 1px solid rgba(255,122,26,.2);
    border-radius: 20px; padding: 28px 32px;
    color: #fff; margin-bottom: 24px;
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
}
.bruce-intro-icon {
    width: 84px; height: 84px; border-radius: 20px;
    background: #0f0f1e; flex-shrink: 0;
    border: 1px solid rgba(255,255,255,.08);
}
.bruce-intro-body { flex: 1; min-width: 260px; }
.bruce-intro-tag {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,122,26,.12); border: 1px solid rgba(255,122,26,.3);
    color: #FF7A1A; font-size: .62rem; font-weight: 800;
    padding: 3px 10px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px;
}
.bruce-intro-title { font-size: 1.05rem; font-weight: 800; margin: 0 0 6px; letter-spacing: -.2px; }
.bruce-intro-summary { color: rgba(255,255,255,.6); font-size: .85rem; margin: 0; line-height: 1.55; }
.bruce-phase {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 18px; padding: 24px 28px; margin-bottom: 16px;
}
.bruce-phase-head {
    display: flex; align-items: center; gap: 12px; margin-bottom: 8px;
}
.bruce-phase-num {
    width: 32px; height: 32px; border-radius: 10px;
    background: #FF7A1A; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: .82rem; flex-shrink: 0;
}
.bruce-phase-name { font-size: 1rem; font-weight: 800; color: #0f172a; letter-spacing: -.2px; }
.bruce-phase-goal { color: #64748b; font-size: .8rem; margin: 0 0 18px; padding-left: 44px; }
.bruce-steps { display: flex; flex-direction: column; gap: 10px; }
.bruce-step {
    display: grid; grid-template-columns: 40px 1fr auto; gap: 14px;
    align-items: center; padding: 14px 16px;
    background: #fafafa; border: 1px solid #f1f5f9; border-radius: 12px;
    transition: background .15s, border-color .15s;
}
.bruce-step:hover { background: #fff; border-color: #FF7A1A; }
.bruce-step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #fff; border: 1.5px solid #FF7A1A; color: #FF7A1A;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .78rem;
}
.bruce-step-body { min-width: 0; }
.bruce-step-title { font-size: .88rem; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
.bruce-step-desc { font-size: .76rem; color: #64748b; line-height: 1.5; }
.bruce-step-meta {
    display: flex; align-items: center; gap: 8px; margin-top: 8px;
    font-size: .68rem; color: #94a3b8; font-weight: 600;
}
.bruce-step-meta i { color: #94a3b8; }
.bruce-step-cta {
    display: inline-flex; align-items: center; gap: 6px;
    background: #FF7A1A; color: #fff !important;
    font-size: .74rem; font-weight: 800;
    padding: 8px 14px; border-radius: 8px;
    text-decoration: none; white-space: nowrap;
    transition: background .15s;
}
.bruce-step-cta:hover { background: #ea580c; color: #fff !important; }

.bruce-empty {
    background: #fff; border: 1px dashed #e2e8f0;
    border-radius: 18px; padding: 40px 32px;
    text-align: center; color: #64748b;
}
.bruce-empty .fa-hourglass-half { color: #FF7A1A; font-size: 2rem; margin-bottom: 16px; }
.bruce-empty h4 { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 6px; }
.bruce-empty p { font-size: .82rem; margin: 0 0 16px; }
.bruce-empty button {
    background: #FF7A1A; color: #fff; border: none;
    font-size: .82rem; font-weight: 700; padding: 10px 24px;
    border-radius: 10px; cursor: pointer; transition: background .15s;
}
.bruce-empty button:hover { background: #ea580c; }
.bruce-empty button:disabled { opacity: .6; cursor: wait; }

/* ══════════ Status / actions bar ══════════ */
.mkt-actions {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.mkt-btn {
    background: rgba(255,255,255,.08); color: #fff;
    border: 1px solid rgba(255,255,255,.12);
    font-size: .78rem; font-weight: 700; padding: 8px 16px;
    border-radius: 10px; cursor: pointer;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .15s;
}
.mkt-btn:hover { background: rgba(255,255,255,.15); color: #fff; }
.mkt-btn-danger { background: rgba(220,38,38,.15); border-color: rgba(220,38,38,.3); color: #fca5a5; }
.mkt-btn-danger:hover { background: rgba(220,38,38,.25); color: #fca5a5; }

@media (max-width: 720px) {
    .mkt-tab { font-size: .78rem; padding: 10px 12px; }
    .mkt-card { padding: 20px; }
    .bruce-step { grid-template-columns: 1fr; }
    .bruce-step-cta { justify-self: start; }
}
</style>
@endpush

@section('content')
<div style="max-width: 1200px; margin: 0 auto;">

    {{-- ══════════════ HEADER ══════════════ --}}
    <div class="mkt-head">
        <div class="mkt-head-top">
            <a href="{{ route('marketing.index') }}" class="mkt-back">
                <i class="fas fa-arrow-left" style="font-size:.65rem;"></i> Meus planos
            </a>
            <div class="mkt-actions">
                <button type="button" class="mkt-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <form action="{{ route('marketing.destroy', $marketing->id) }}" method="POST"
                      onsubmit="return confirm('Excluir este plano estratégico?')" style="margin:0;">
                    @csrf @method('DELETE')
                    <button type="submit" class="mkt-btn mkt-btn-danger">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        <h1 class="mkt-title">{{ $rootTitle ?: $marketing->title }}</h1>
        <p class="mkt-subtitle">{{ $marketing->objective }}</p>

        <div class="mkt-briefing">
            <span class="mkt-pill"><i class="fas fa-users"></i> {{ Str::limit($marketing->target_audience, 50) }}</span>
            <span class="mkt-pill"><i class="fas fa-globe"></i> {{ $marketing->scope === 'online_offline' ? 'Online + Presencial' : 'Apenas Online' }}</span>
            <span class="mkt-pill"><i class="fas fa-microphone"></i> Tom {{ ['professional'=>'Profissional','friendly'=>'Amigável','inspirational'=>'Inspirador','urgent'=>'Urgente'][$marketing->tone] ?? $marketing->tone }}</span>
            @if($marketing->budget_range)
                <span class="mkt-pill"><i class="fas fa-coins"></i> {{ $marketing->budget_range }}</span>
            @endif
            @if($marketing->project)
                <span class="mkt-pill"><i class="fas fa-diagram-project"></i> {{ $marketing->project->name }}</span>
            @endif
            <span class="mkt-pill"><i class="fas fa-calendar"></i> {{ $marketing->created_at->format('d/m/Y') }}</span>
        </div>
    </div>

    {{-- ══════════════ STATUS (processing / failed) ══════════════ --}}
    @if($marketing->status !== 'done')
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px;text-align:center;color:#475569;" id="statusBox">
            @if($marketing->status === 'failed')
                <i class="fas fa-triangle-exclamation" style="color:#dc2626;font-size:1.6rem;margin-bottom:12px;"></i>
                <h3 style="margin:0 0 6px;color:#0f172a;font-size:1rem;">Falha ao gerar o plano</h3>
                <p style="font-size:.85rem;margin:0;">A IA não conseguiu processar. Tente criar um novo plano.</p>
            @else
                <i class="fas fa-circle-notch fa-spin" style="color:#FF7A1A;font-size:1.6rem;margin-bottom:12px;"></i>
                <h3 style="margin:0 0 6px;color:#0f172a;font-size:1rem;">A IA está gerando seu plano...</h3>
                <p style="font-size:.85rem;margin:0;">Isso leva ~30-60 segundos. Esta página atualiza sozinha quando ficar pronto.</p>
            @endif
        </div>
    @else

    {{-- ══════════════ TABS ══════════════ --}}
    <div class="mkt-tabs">
        <button type="button" class="mkt-tab active" data-tab="executive" onclick="switchTab('executive')">
            <i class="fas fa-diagram-project"></i>
            Plano Executivo
            <span class="badge-count">{{ count($sections) }}</span>
        </button>
        <button type="button" class="mkt-tab" data-tab="bruce" onclick="switchTab('bruce')">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="" style="width:18px;height:18px;border-radius:5px;">
            Guia do Bruce
            @if($marketing->guide_status === 'ready' && $marketing->execution_guide)
                @php $stepCount = collect($marketing->execution_guide['phases'] ?? [])->sum(fn($p) => count($p['steps'] ?? [])); @endphp
                <span class="badge-count">{{ $stepCount }}</span>
            @endif
        </button>
    </div>

    {{-- ══════════════ TAB 1 — PLANO EXECUTIVO ══════════════ --}}
    <div class="mkt-panel active" id="panel-executive">
        <div class="mkt-flow">
            @forelse($sections as $idx => $section)
                <div class="mkt-card {{ count($section['h3s']) > 3 ? 'collapsed' : '' }}" id="card-{{ $idx }}">
                    <div class="mkt-card-head">
                        <div class="mkt-card-num">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <i class="fas {{ $iconFor($section['title']) }} mkt-card-ico"></i>
                        <div class="mkt-card-title">{{ $section['title'] }}</div>
                    </div>

                    <div class="mkt-card-body">
                        @foreach($section['h3s'] as $h3)
                            <div class="mkt-h3">
                                <div class="mkt-h3-title">{{ $h3['title'] }}</div>
                                @if(!empty($h3['bullets']))
                                    <ul class="mkt-bullets">
                                        @foreach($h3['bullets'] as $bullet)
                                            <li>{!! preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', e($bullet)) !!}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(count($section['h3s']) > 3)
                        <div class="mkt-card-toolbar">
                            <span class="mkt-h3-count">{{ count($section['h3s']) }} tópicos</span>
                            <button type="button" class="mkt-card-expand" onclick="toggleCard({{ $idx }})">
                                <span class="expand-text">Ver todos</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    @endif
                </div>

                @if(!$loop->last)
                    <div class="mkt-arrow"></div>
                @endif
            @empty
                <div class="bruce-empty">
                    <p>Nenhum conteúdo estruturado neste plano.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════ TAB 2 — GUIA DO BRUCE ══════════════ --}}
    <div class="mkt-panel" id="panel-bruce">
        <div class="bruce-shell">

            @if($marketing->guide_status === 'ready' && !empty($marketing->execution_guide['phases']))
                @php $guide = $marketing->execution_guide; @endphp

                <div class="bruce-intro">
                    <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" class="bruce-intro-icon">
                    <div class="bruce-intro-body">
                        <span class="bruce-intro-tag"><i class="fas fa-route"></i> Rota executável</span>
                        <div class="bruce-intro-title">Como colocar este plano em prática no Vivensi</div>
                        <p class="bruce-intro-summary">
                            {{ $guide['summary'] ?? 'Sequência de passos usando as ferramentas do Vivensi para executar o plano estratégico gerado acima.' }}
                        </p>
                    </div>
                </div>

                @foreach($guide['phases'] as $pIdx => $phase)
                    <div class="bruce-phase">
                        <div class="bruce-phase-head">
                            <div class="bruce-phase-num">{{ $pIdx + 1 }}</div>
                            <div class="bruce-phase-name">{{ $phase['name'] ?? 'Fase '.($pIdx+1) }}</div>
                        </div>
                        @if(!empty($phase['goal']))
                            <p class="bruce-phase-goal">🎯 {{ $phase['goal'] }}</p>
                        @endif

                        <div class="bruce-steps">
                            @foreach(($phase['steps'] ?? []) as $sIdx => $step)
                                <div class="bruce-step">
                                    <div class="bruce-step-num">{{ $sIdx + 1 }}</div>
                                    <div class="bruce-step-body">
                                        <div class="bruce-step-title">{{ $step['title'] ?? 'Passo' }}</div>
                                        <div class="bruce-step-desc">{{ $step['description'] ?? '' }}</div>
                                        <div class="bruce-step-meta">
                                            @if(!empty($step['tool_label']))
                                                <span><i class="fas fa-toolbox"></i> {{ $step['tool_label'] }}</span>
                                            @endif
                                            @if(!empty($step['estimated_time']))
                                                <span>·</span>
                                                <span><i class="far fa-clock"></i> {{ $step['estimated_time'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!empty($step['tool_url']))
                                        <a href="{{ $step['tool_url'] }}" class="bruce-step-cta" target="_blank" rel="noopener">
                                            <i class="fas fa-arrow-up-right-from-square"></i> Abrir agora
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            @elseif($marketing->guide_status === 'pending')
                <div class="bruce-empty">
                    <i class="fas fa-hourglass-half"></i>
                    <h4>Bruce está preparando as orientações</h4>
                    <p>Isso leva ~30-45 segundos após o plano ficar pronto. Esta página atualiza sozinha.</p>
                </div>

            @else
                {{-- guide_status === 'failed' ou null --}}
                <div class="bruce-empty">
                    <i class="fas fa-hourglass-half"></i>
                    <h4>Guia ainda não disponível</h4>
                    <p>Clique abaixo para o Bruce gerar orientações práticas de como executar este plano no Vivensi.</p>
                    <button type="button" id="btnRegenGuide" onclick="regenerateGuide()">
                        <i class="fas fa-wand-magic-sparkles"></i> Gerar Guia do Bruce
                    </button>
                </div>
            @endif
        </div>
    </div>

    @endif

</div>

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
    const PLAN_ID = {{ $marketing->id }};
    const IS_DONE = @json($marketing->status === 'done');
    const GUIDE_STATUS = @json($marketing->guide_status);

    function switchTab(name) {
        document.querySelectorAll('.mkt-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
        document.querySelectorAll('.mkt-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-' + name));
    }

    function toggleCard(idx) {
        const card = document.getElementById('card-' + idx);
        if (!card) return;
        card.classList.toggle('collapsed');
        const btn = card.querySelector('.expand-text');
        const ico = card.querySelector('.mkt-card-expand i');
        if (card.classList.contains('collapsed')) {
            btn.textContent = 'Ver todos';
            ico.className = 'fas fa-chevron-down';
        } else {
            btn.textContent = 'Recolher';
            ico.className = 'fas fa-chevron-up';
        }
    }

    async function regenerateGuide() {
        const btn = document.getElementById('btnRegenGuide');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Gerando (30-60s)...';
        try {
            const res = await fetch('/marketing/{{ $marketing->id }}/regenerate-guide', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Falha ao gerar o guia.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Tentar novamente';
            }
        } catch (e) {
            alert('Erro de comunicação. Tente de novo.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Tentar novamente';
        }
    }

    // Auto-refresh se plano ainda gerando ou se guide ainda pending
    if (!IS_DONE || GUIDE_STATUS === 'pending') {
        setTimeout(async () => {
            try {
                const res = await fetch('/marketing/{{ $marketing->id }}/status');
                const data = await res.json();
                if (data.status === 'done' && (data.guide_status === 'ready' || data.guide_status === 'failed')) {
                    location.reload();
                } else {
                    // continua aguardando — refresh full após alguns segundos
                    setTimeout(() => location.reload(), 8000);
                }
            } catch (e) {
                setTimeout(() => location.reload(), 15000);
            }
        }, 6000);
    }
</script>
@endpush

@endsection
