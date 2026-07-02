@extends('layouts.app')

@section('content')
@php
    // Auto-refresh a cada 5s enquanto em_andamento (polling suave).
    $inProgress = $session->status === 'em_andamento';

    // Metadata por agente pra estilo consistente
    $agentMeta = [
        'financeiro' => ['label' => 'Financeiro',           'icon' => 'fa-coins',        'accent' => '#10b981', 'accentBg' => 'rgba(16,185,129,0.08)', 'role' => 'CFO'],
        'inteligencia' => ['label' => 'Inteligência',        'icon' => 'fa-magnifying-glass-chart', 'accent' => '#3b82f6', 'accentBg' => 'rgba(59,130,246,0.08)', 'role' => 'Pesquisador'],
        'mobilizacao'  => ['label' => 'Mobilização',        'icon' => 'fa-bullhorn',     'accent' => '#f59e0b', 'accentBg' => 'rgba(245,158,11,0.08)', 'role' => 'CMO'],
        'estrategista_chefe' => ['label' => 'Estrategista-Chefe', 'icon' => 'fa-chess-king', 'accent' => '#4f46e5', 'accentBg' => 'rgba(79,70,229,0.08)', 'role' => 'CEO / Moderador'],
    ];

    $confMeta = [
        'alta'  => ['label' => 'Confiança alta',  'bg' => '#ecfdf5', 'fg' => '#065f46', 'icon' => 'fa-circle-check'],
        'media' => ['label' => 'Confiança média', 'bg' => '#fffbeb', 'fg' => '#92400e', 'icon' => 'fa-circle-exclamation'],
        'baixa' => ['label' => 'Confiança baixa', 'bg' => '#fef2f2', 'fg' => '#991b1b', 'icon' => 'fa-triangle-exclamation'],
    ];
@endphp

@if($inProgress)
    <meta http-equiv="refresh" content="5">
@endif

<div style="margin-bottom: 24px;">
    <a href="{{ route('strategy-room.index') }}" style="color: #64748b; font-weight: 700; text-decoration: none; font-size: 0.82rem;">
        <i class="fas fa-arrow-left me-1"></i> Sala de Estratégia
    </a>
</div>

<div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
    <div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px;">
            Sessão #{{ $session->id }}
        </h2>
        <p style="color: #64748b; margin: 8px 0 0 0; font-size: 0.95rem;">
            Iniciada {{ $session->created_at->diffForHumans() }} · Gatilho: {{ $session->trigger_type }}
        </p>
    </div>
    @if($inProgress)
        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
            <div class="spinner-border spinner-border-sm" style="color: #d97706;" role="status"></div>
            <div>
                <div style="font-weight: 800; color: #92400e; font-size: 0.88rem;">Debate em andamento</div>
                <div style="color: #a16207; font-size: 0.78rem;">A página atualiza sozinha a cada 5s</div>
            </div>
        </div>
    @else
        <span style="background: #ecfdf5; color: #065f46; padding: 8px 16px; border-radius: 99px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase;">
            <i class="fas fa-check-circle me-1"></i> Concluída
        </span>
    @endif
</div>

@if($messages->isEmpty() && !$inProgress)
    <div class="vivensi-card" style="padding: 40px; border-radius: 20px; text-align: center; color: #64748b;">
        Nenhuma fala foi gerada. Verifique os logs.
    </div>
@elseif($messages->isEmpty())
    <div class="vivensi-card" style="padding: 40px; border-radius: 20px; text-align: center; color: #64748b;">
        Aguardando primeira fala…
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 20px;">
        @foreach($messages as $m)
            @php
                $meta = $agentMeta[$m->agent] ?? ['label' => $m->agent, 'icon' => 'fa-comment', 'accent' => '#64748b', 'accentBg' => '#f1f5f9', 'role' => ''];
                $conf = $confMeta[$m->confidence] ?? $confMeta['media'];
                $facts = is_array($m->facts_used) ? $m->facts_used : [];
            @endphp
            <div class="vivensi-card" style="padding: 24px 28px; border-radius: 18px; background: white; border: 1px solid #f1f5f9; border-left: 4px solid {{ $meta['accent'] }};">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px; flex-wrap: wrap;">
                    <div style="width: 42px; height: 42px; background: {{ $meta['accentBg'] }}; color: {{ $meta['accent'] }}; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fas {{ $meta['icon'] }}"></i>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <div style="font-weight: 900; color: #1e293b; font-size: 1rem;">{{ $meta['label'] }}</div>
                        @if($meta['role'])
                            <div style="color: #94a3b8; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.3px;">{{ $meta['role'] }}</div>
                        @endif
                    </div>
                    <span style="background: {{ $conf['bg'] }}; color: {{ $conf['fg'] }}; padding: 6px 14px; border-radius: 99px; font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas {{ $conf['icon'] }}"></i> {{ $conf['label'] }}
                    </span>
                </div>

                <div style="color: #1e293b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 18px;">
                    {{ $m->content }}
                </div>

                @if(!empty($facts))
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                        <span style="color: #94a3b8; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 4px; align-self: center;">
                            <i class="fas fa-link me-1"></i> Fatos usados:
                        </span>
                        @foreach($facts as $handle)
                            @php
                                // Resolve handle pra URL clicavel quando possivel
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
                                    $tip = 'Consulta via ferramenta: ' . $id;
                                    $kind = 'tool';
                                } elseif (in_array($handle, ['financeiro', 'inteligencia', 'mobilizacao'], true)) {
                                    $tip = 'Referência ao agente ' . ucfirst($handle);
                                    $kind = 'agent';
                                } else {
                                    $tip = 'Métrica do painel: ' . $handle;
                                    $kind = 'catalog';
                                }

                                $styles = [
                                    'entity'  => ['bg' => 'rgba(59,130,246,0.08)', 'fg' => '#1d4ed8', 'border' => 'rgba(59,130,246,0.25)'],
                                    'tool'    => ['bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#e2e8f0'],
                                    'agent'   => ['bg' => 'rgba(79,70,229,0.08)', 'fg' => '#3730a3', 'border' => 'rgba(79,70,229,0.25)'],
                                    'catalog' => ['bg' => 'rgba(16,185,129,0.08)', 'fg' => '#065f46', 'border' => 'rgba(16,185,129,0.25)'],
                                ][$kind];
                            @endphp
                            @if($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ $tip }}"
                                   style="background: {{ $styles['bg'] }}; color: {{ $styles['fg'] }}; border: 1px solid {{ $styles['border'] }}; padding: 5px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-external-link-alt" style="font-size: 0.65rem;"></i> {{ $handle }}
                                </a>
                            @else
                                <span title="{{ $tip }}"
                                      style="background: {{ $styles['bg'] }}; color: {{ $styles['fg'] }}; border: 1px solid {{ $styles['border'] }}; padding: 5px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: help;">
                                    {{ $handle }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div style="margin-top: 14px; color: #94a3b8; font-size: 0.72rem;">
                    Mensagem #{{ $m->id }} · {{ $m->created_at->format('H:i:s') }}
                </div>
            </div>
        @endforeach
    </div>
@endif

<div style="margin-top: 32px; padding: 20px 24px; background: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; display: flex; align-items: flex-start; gap: 12px;">
    <i class="fas fa-shield-halved" style="color: #64748b; margin-top: 2px;"></i>
    <div style="color: #64748b; font-size: 0.82rem; line-height: 1.6;">
        <strong style="color: #475569;">Como ler as pílulas de fatos usados:</strong>
        <span style="background: rgba(16,185,129,0.08); color: #065f46; padding: 2px 8px; border-radius: 99px; font-size: 0.72rem; font-weight: 700;">verde</span>
        = métrica do painel;
        <span style="background: rgba(59,130,246,0.08); color: #1d4ed8; padding: 2px 8px; border-radius: 99px; font-size: 0.72rem; font-weight: 700;">azul</span>
        = registro clicável (projeto/edital);
        <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 99px; font-size: 0.72rem; font-weight: 700;">cinza</span>
        = consulta feita via ferramenta;
        <span style="background: rgba(79,70,229,0.08); color: #3730a3; padding: 2px 8px; border-radius: 99px; font-size: 0.72rem; font-weight: 700;">roxo</span>
        = referência a outro agente. A Sala apoia a decisão — não substitui aconselhamento profissional.
    </div>
</div>
@endsection
