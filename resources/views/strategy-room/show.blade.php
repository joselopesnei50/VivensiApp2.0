@extends('layouts.app')

@section('content')
@php
    $inProgress = $session->status === 'em_andamento';

    // Metadata dos agentes vem do controller. Adiciona fallback pra chaves
    // desconhecidas (ex: se algum agente novo aparecer no futuro).
    $agentFallback = [
        'name' => 'Agente', 'role' => 'Diretoria', 'sub_role' => '',
        'bio' => '', 'icon' => 'fa-comment', 'color' => '#64748b',
        'from' => '#94a3b8', 'to' => '#475569', 'initials' => '?',
    ];

    $confMeta = [
        'alta'  => ['label' => 'Confiança alta',  'bg' => '#ecfdf5', 'fg' => '#065f46', 'border' => '#a7f3d0', 'icon' => 'fa-circle-check'],
        'media' => ['label' => 'Confiança média', 'bg' => '#fffbeb', 'fg' => '#92400e', 'border' => '#fde68a', 'icon' => 'fa-circle-exclamation'],
        'baixa' => ['label' => 'Confiança baixa', 'bg' => '#fef2f2', 'fg' => '#991b1b', 'border' => '#fecaca', 'icon' => 'fa-triangle-exclamation'],
    ];

    // Ordem esperada de aparecimento — pra render em fila mesmo se as mensagens
    // chegarem fora de ordem por qualquer motivo.
    $agentOrder = ['financeiro' => 1, 'inteligencia' => 2, 'mobilizacao' => 3, 'estrategista_chefe' => 4];
@endphp

<style>
    .sr-show-hero {
        position: relative;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: 24px;
        padding: 32px 36px;
        color: white;
        overflow: hidden;
        margin-bottom: 32px;
        box-shadow: 0 24px 60px rgba(15,23,42,0.25);
    }
    .sr-show-hero::before {
        content: ""; position: absolute; top: -80px; right: -60px;
        width: 260px; height: 260px; border-radius: 50%;
        background: rgba(99,102,241,0.28); filter: blur(70px); z-index: 1;
    }
    .sr-show-hero-inner { position: relative; z-index: 2; }

    .sr-back-link {
        color: rgba(255,255,255,0.65);
        font-size: 0.82rem;
        text-decoration: none;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.15s ease;
    }
    .sr-back-link:hover { color: white; }

    .sr-progress-bar {
        display: flex; gap: 6px; margin-top: 24px;
    }
    .sr-progress-dot {
        flex: 1;
        height: 6px;
        border-radius: 99px;
        background: rgba(255,255,255,0.12);
        overflow: hidden;
        position: relative;
        transition: background 0.4s ease;
    }
    .sr-progress-dot.done  { background: rgba(255,255,255,0.9); }
    .sr-progress-dot.active {
        background: rgba(255,255,255,0.28);
    }
    .sr-progress-dot.active::after {
        content: ""; position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.9), transparent);
        animation: sr-shimmer 1.6s infinite;
    }
    @keyframes sr-shimmer {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .sr-message-card {
        background: white;
        border-radius: 22px;
        padding: 26px 28px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 40px rgba(15,23,42,0.04);
        display: flex;
        gap: 22px;
        align-items: flex-start;
        opacity: 0;
        transform: translateY(8px);
        animation: sr-fade-in 0.35s ease-out forwards;
    }
    @keyframes sr-fade-in {
        to { opacity: 1; transform: translateY(0); }
    }

    .sr-avatar-big {
        width: 60px; height: 60px;
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 900; font-size: 1.3rem;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 10px 24px rgba(0,0,0,0.14);
    }
    .sr-avatar-big .sr-avatar-icon {
        position: absolute; right: -6px; bottom: -6px;
        width: 26px; height: 26px; border-radius: 9px;
        background: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.72rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.14);
    }

    .sr-conf-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .sr-fact-pill {
        padding: 5px 12px;
        border-radius: 99px;
        font-size: 0.74rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid;
        white-space: nowrap;
    }
    .sr-fact-pill a { text-decoration: none; color: inherit; }
    .sr-fact-pill:hover { transform: translateY(-1px); }

    .sr-skeleton-card {
        background: white;
        border-radius: 22px;
        padding: 26px 28px;
        border: 1px dashed #e2e8f0;
        display: flex;
        gap: 22px;
        align-items: center;
        opacity: 0.7;
    }
    .sr-skeleton-avatar {
        width: 60px; height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        display: flex; align-items: center; justify-content: center;
        color: #94a3b8;
        flex-shrink: 0;
    }
    .sr-pulse { animation: sr-pulse 1.4s ease-in-out infinite; }
    @keyframes sr-pulse {
        0%, 100% { opacity: 0.5; }
        50%      { opacity: 1; }
    }

    @media (max-width: 576px) {
        .sr-show-hero { padding: 24px 20px; }
        .sr-message-card, .sr-skeleton-card { flex-direction: column; padding: 20px; }
        .sr-avatar-big { width: 48px; height: 48px; font-size: 1.05rem; }
    }
</style>

<div class="sr-show-hero">
    <div class="sr-show-hero-inner">
        <a href="{{ route('strategy-room.index') }}" class="sr-back-link">
            <i class="fas fa-arrow-left"></i> Voltar à Sala de Estratégia
        </a>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px; gap: 20px; flex-wrap: wrap;">
            <div>
                <h1 style="margin: 0 0 6px 0; color: white; font-weight: 950; font-size: 2rem; letter-spacing: -1px;">
                    Reunião #{{ $session->id }}
                </h1>
                <div style="color: rgba(255,255,255,0.6); font-size: 0.9rem; font-weight: 500;">
                    Convocada {{ $session->created_at->diffForHumans() }} · Gatilho: <span style="color: white;">{{ $session->trigger_type }}</span>
                </div>
            </div>

            <div id="sr-status-badge" style="background: {{ $inProgress ? 'rgba(251,191,36,0.15)' : 'rgba(16,185,129,0.15)' }}; color: {{ $inProgress ? '#fbbf24' : '#34d399' }}; border: 1px solid {{ $inProgress ? 'rgba(251,191,36,0.35)' : 'rgba(16,185,129,0.35)' }}; padding: 10px 18px; border-radius: 99px; font-size: 0.82rem; font-weight: 800; display: inline-flex; align-items: center; gap: 10px;">
                @if($inProgress)
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #fbbf24; animation: sr-pulse 1.4s infinite;"></span>
                    <span>Diretoria em reunião</span>
                @else
                    <i class="fas fa-check-circle"></i> <span>Reunião concluída</span>
                @endif
            </div>
        </div>

        {{-- Progress dots — 4 slots, um por agente na ordem esperada --}}
        <div class="sr-progress-bar" id="sr-progress-bar">
            @foreach($agentOrder as $agentKey => $ordem)
                @php
                    $spoke = $messages->contains(fn ($m) => $m->agent === $agentKey);
                    $cls   = $spoke ? 'done' : ($inProgress ? 'active' : '');
                @endphp
                <div class="sr-progress-dot {{ $cls }}" data-agent="{{ $agentKey }}"></div>
            @endforeach
        </div>
    </div>
</div>

{{-- Timeline das falas --}}
<div id="sr-messages" style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 32px;">
    @foreach($messages as $m)
        @php
            $a = $agents[$m->agent] ?? $agentFallback;
            $conf = $confMeta[$m->confidence] ?? $confMeta['media'];
            $facts = is_array($m->facts_used) ? $m->facts_used : [];
        @endphp
        <div class="sr-message-card" style="border-left: 5px solid {{ $a['color'] }};">
            <div class="sr-avatar-big" style="background: linear-gradient(135deg, {{ $a['from'] }}, {{ $a['to'] }});">
                {{ $a['initials'] }}
                <span class="sr-avatar-icon" style="color: {{ $a['color'] }};">
                    <i class="fas {{ $a['icon'] }}"></i>
                </span>
            </div>

            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px; flex-wrap: wrap;">
                    <strong style="color: #1e293b; font-size: 1.1rem; font-weight: 900;">{{ $a['name'] }}</strong>
                    <span class="sr-conf-badge" style="background: {{ $conf['bg'] }}; color: {{ $conf['fg'] }}; border: 1px solid {{ $conf['border'] }};">
                        <i class="fas {{ $conf['icon'] }}"></i> {{ $conf['label'] }}
                    </span>
                </div>
                <div style="color: #64748b; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.3px; margin-bottom: 16px;">
                    {{ $a['role'] }} · {{ $a['sub_role'] }}
                </div>

                <div style="color: #1e293b; font-size: 0.96rem; line-height: 1.65; margin-bottom: 20px;">
                    {{ $m->content }}
                </div>

                @if(!empty($facts))
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; padding-top: 16px; border-top: 1px dashed #e2e8f0;">
                        <span style="color: #94a3b8; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; align-self: center; margin-right: 4px;">
                            <i class="fas fa-link me-1"></i> Fatos:
                        </span>
                        @foreach($facts as $handle)
                            @php
                                [$type, $id] = array_pad(explode(':', $handle, 2), 2, null);
                                $url = null; $tip = null; $kind = 'catalog';
                                if ($type === 'projeto' && is_numeric($id)) {
                                    $url = url('/projects/' . (int) $id);
                                    $tip = 'Abrir projeto #' . $id;
                                    $kind = 'entity';
                                } elseif ($type === 'edital' && is_numeric($id)) {
                                    $url = url('/ngo/grants/' . (int) $id);
                                    $tip = 'Abrir edital #' . $id;
                                    $kind = 'entity';
                                } elseif ($type === 'tool') {
                                    $tip = 'Consulta feita via ferramenta: ' . $id;
                                    $kind = 'tool';
                                } elseif (in_array($handle, ['financeiro', 'inteligencia', 'mobilizacao'], true)) {
                                    $refAgent = $agents[$handle] ?? null;
                                    $tip = 'Referência ao ' . ($refAgent['name'] ?? ucfirst($handle));
                                    $kind = 'agent';
                                } else {
                                    $tip = 'Métrica do painel: ' . $handle;
                                    $kind = 'catalog';
                                }
                                $styles = [
                                    'entity'  => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'border' => '#bfdbfe'],
                                    'tool'    => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#e2e8f0'],
                                    'agent'   => ['bg' => '#eef2ff', 'fg' => '#3730a3', 'border' => '#c7d2fe'],
                                    'catalog' => ['bg' => '#ecfdf5', 'fg' => '#065f46', 'border' => '#a7f3d0'],
                                ][$kind];
                            @endphp
                            @if($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ $tip }}"
                                   class="sr-fact-pill"
                                   style="background: {{ $styles['bg'] }}; color: {{ $styles['fg'] }}; border-color: {{ $styles['border'] }};">
                                    <i class="fas fa-external-link-alt" style="font-size: 0.6rem;"></i> {{ $handle }}
                                </a>
                            @else
                                <span title="{{ $tip }}"
                                      class="sr-fact-pill"
                                      style="background: {{ $styles['bg'] }}; color: {{ $styles['fg'] }}; border-color: {{ $styles['border'] }}; cursor: help;">
                                    {{ $handle }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div style="margin-top: 14px; color: #94a3b8; font-size: 0.72rem; letter-spacing: 0.2px;">
                    Fala #{{ $m->id }} · {{ $m->created_at->format('H:i:s') }}
                </div>
            </div>
        </div>
    @endforeach

    {{-- Skeletons pros agentes que ainda não falaram (apenas em em_andamento) --}}
    @if($inProgress)
        @foreach($agentOrder as $agentKey => $ordem)
            @php
                $a = $agents[$agentKey] ?? $agentFallback;
                $spoke = $messages->contains(fn ($m) => $m->agent === $agentKey);
            @endphp
            @if(!$spoke)
                <div class="sr-skeleton-card" data-skeleton-for="{{ $agentKey }}">
                    <div class="sr-skeleton-avatar sr-pulse">
                        <i class="fas {{ $a['icon'] }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; color: #64748b; margin-bottom: 4px;">{{ $a['name'] }} está pensando…</div>
                        <div style="color: #94a3b8; font-size: 0.82rem;">{{ $a['role'] }} · {{ $a['sub_role'] }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

@if($messages->isEmpty() && !$inProgress)
    <div class="sr-message-card" style="justify-content: center; text-align: center; color: #64748b; border-left: 5px solid #e2e8f0;">
        <div style="width: 100%;">
            <i class="fas fa-comment-slash" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 12px;"></i>
            <div>Nenhuma fala foi gerada nesta sessão. Verifique os logs de queue.</div>
        </div>
    </div>
@endif

{{-- Legenda das pílulas --}}
<div style="padding: 22px 26px; background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 14px; margin-top: 8px;">
    <i class="fas fa-shield-halved" style="color: #64748b; margin-top: 2px; font-size: 0.95rem;"></i>
    <div style="color: #64748b; font-size: 0.82rem; line-height: 1.7;">
        <strong style="color: #475569;">Como ler as pílulas de fatos:</strong>
        <span class="sr-fact-pill" style="background: #ecfdf5; color: #065f46; border-color: #a7f3d0; margin: 0 4px;">verde</span> métrica do painel;
        <span class="sr-fact-pill" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; margin: 0 4px;">azul</span> registro clicável (projeto/edital);
        <span class="sr-fact-pill" style="background: #f1f5f9; color: #475569; border-color: #e2e8f0; margin: 0 4px;">cinza</span> consulta via ferramenta;
        <span class="sr-fact-pill" style="background: #eef2ff; color: #3730a3; border-color: #c7d2fe; margin: 0 4px;">roxo</span> referência a colega.
        A Sala apoia decisão — não substitui aconselhamento profissional.
    </div>
</div>

@if($inProgress)
<script>
    // Polling suave — checa status a cada 3s. Quando concluida, reload UMA vez.
    // Substitui o antigo <meta http-equiv=refresh> que reloadava a pagina inteira
    // a cada 5s e fazia o menu piscar/pulsar.
    (function () {
        const statusUrl = "{{ route('strategy-room.status', $session->id) }}";
        const POLL_MS   = 3000;
        const MAX_MS    = 5 * 60 * 1000; // trava em 5min pra nao ficar pra sempre
        const startedAt = Date.now();
        const seen      = new Set(@json($messages->pluck('agent')->all()));

        function tick() {
            if (Date.now() - startedAt > MAX_MS) return;

            fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return setTimeout(tick, POLL_MS);

                    // Quando concluir, reload UMA vez pra pegar tudo renderizado
                    if (data.status !== 'em_andamento') {
                        window.location.reload();
                        return;
                    }

                    // Se aumentou o count, provavelmente novo agente falou —
                    // reload pra mostrar (evita render parcial complexo em JS).
                    if (data.messages_count > seen.size) {
                        window.location.reload();
                        return;
                    }

                    setTimeout(tick, POLL_MS);
                })
                .catch(() => setTimeout(tick, POLL_MS));
        }

        setTimeout(tick, POLL_MS);
    })();
</script>
@endif
@endsection
