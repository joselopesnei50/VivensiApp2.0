@extends('layouts.app')

@section('content')
<style>
    .sr-hero {
        position: relative;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
        border-radius: 24px;
        padding: 40px;
        color: white;
        overflow: hidden;
        margin-bottom: 32px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
    }
    .sr-hero::before, .sr-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.5;
        z-index: 1;
    }
    .sr-hero::before { top: -80px; right: -60px; width: 320px; height: 320px; background: rgba(99,102,241,0.35); }
    .sr-hero::after  { bottom: -100px; left: -60px; width: 280px; height: 280px; background: rgba(16,185,129,0.22); }
    .sr-hero-inner { position: relative; z-index: 2; }

    .sr-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: rgba(255,255,255,0.85);
    }
    .sr-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: white;
        color: #0f172a;
        padding: 14px 26px;
        border-radius: 14px;
        font-weight: 900;
        font-size: 0.95rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        box-shadow: 0 12px 28px rgba(0,0,0,0.15);
    }
    .sr-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 40px rgba(0,0,0,0.22); color: #0f172a; }

    .sr-hero-agent {
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 16px;
        padding: 12px 14px;
        height: 100%;
    }

    .sr-avatar {
        width: 64px; height: 64px;
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 900; font-size: 1.4rem;
        letter-spacing: -1px;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 12px 28px rgba(0,0,0,0.18);
    }
    .sr-avatar .sr-avatar-badge {
        position: absolute; right: -6px; bottom: -6px;
        width: 26px; height: 26px; border-radius: 9px;
        background: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.68rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    }

    .sr-card {
        background: white;
        border-radius: 20px;
        padding: 26px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 40px rgba(15,23,42,0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }
    .sr-card:hover { transform: translateY(-4px); box-shadow: 0 24px 60px rgba(15,23,42,0.08); }

    .sr-step-num {
        width: 38px; height: 38px;
        border-radius: 12px;
        font-weight: 900; font-size: 1rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .sr-section-title {
        margin: 0;
        color: #1e293b;
        font-weight: 900;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
    }
    .sr-section-sub {
        color: #64748b;
        font-size: 0.9rem;
        margin: 4px 0 0 0;
    }

    .sr-session-card {
        background: white;
        border-radius: 16px;
        padding: 18px 22px;
        border: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: 18px;
        transition: transform 0.15s ease, border-color 0.15s ease;
        text-decoration: none;
        color: inherit;
    }
    .sr-session-card:hover { transform: translateX(4px); border-color: #cbd5e1; color: inherit; text-decoration: none; }

    .sr-status-chip { padding: 5px 12px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .sr-status-em_andamento { background: #fffbeb; color: #92400e; }
    .sr-status-concluida    { background: #ecfdf5; color: #065f46; }
</style>

{{-- ═══ Hero ═══════════════════════════════════════════════ --}}
<div class="sr-hero">
    <div class="sr-hero-inner">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <span class="sr-badge">
                    <i class="fas fa-chess-queen" style="color: #a5b4fc;"></i> Diretoria Executiva Virtual
                </span>
                <h1 style="margin: 18px 0 12px 0; font-size: 2.4rem; font-weight: 950; letter-spacing: -1.5px; line-height: 1.1; color: white;">
                    Sala de Estratégia
                </h1>
                <p style="color: rgba(255,255,255,0.72); font-size: 1rem; font-weight: 500; line-height: 1.55; margin: 0 0 26px 0;">
                    Quatro agentes de IA — <strong style="color: #fff;">Bruce</strong>, <strong style="color: #34d399;">Olga</strong>, <strong style="color: #60a5fa;">Maria</strong> e <strong style="color: #fbbf24;">Time Vibra</strong> — cruzam dado real do seu painel, debatem entre si e propõem <em style="color: white;">uma</em> ação prioritária. Cada afirmação vem rastreada.
                </p>
                <form action="{{ route('strategy-room.store') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="sr-btn-primary">
                        <i class="fas fa-play-circle"></i> Convocar reunião estratégica
                    </button>
                </form>
            </div>

            {{-- Preview da diretoria — grade 2x2 --}}
            <div class="col-lg-5">
                <div class="row g-3">
                    @foreach($agents as $a)
                        <div class="col-sm-6">
                            <div class="sr-hero-agent">
                                <div class="sr-avatar" style="width: 44px; height: 44px; font-size: 1rem; border-radius: 13px; background: linear-gradient(135deg, {{ $a['from'] }}, {{ $a['to'] }});">
                                    {{ $a['initials'] }}
                                    <span class="sr-avatar-badge" style="width: 19px; height: 19px; border-radius: 6px; color: {{ $a['color'] }}; font-size: 0.55rem;">
                                        <i class="fas {{ $a['icon'] }}"></i>
                                    </span>
                                </div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 900; color: white; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $a['name'] }}</div>
                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.55); font-weight: 600; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $a['sub_role'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Conheça a diretoria ═══════════════════════════════════════════════ --}}
<div class="mb-3">
    <h2 class="sr-section-title">Conheça a diretoria</h2>
    <p class="sr-section-sub">Cada um consulta uma fatia diferente do painel — sem sobreposição.</p>
</div>

<div class="row g-4 mb-5">
    @foreach($agents as $a)
        <div class="col-sm-6 col-xl-3">
            <div class="sr-card" style="border-top: 4px solid {{ $a['color'] }};">
                <div class="sr-avatar" style="background: linear-gradient(135deg, {{ $a['from'] }}, {{ $a['to'] }}); margin-bottom: 18px;">
                    {{ $a['initials'] }}
                    <span class="sr-avatar-badge" style="color: {{ $a['color'] }};"><i class="fas {{ $a['icon'] }}"></i></span>
                </div>
                <h3 style="margin: 0 0 4px 0; color: #1e293b; font-weight: 900; font-size: 1.2rem; letter-spacing: -0.5px;">{{ $a['name'] }}</h3>
                <div style="color: {{ $a['color'] }}; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;">
                    {{ $a['role'] }} · {{ $a['sub_role'] }}
                </div>
                <p style="color: #64748b; font-size: 0.86rem; line-height: 1.55; margin: 0;">{{ $a['bio'] }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- ═══ Como funciona ═══════════════════════════════════════════════ --}}
<div class="mb-3">
    <h2 class="sr-section-title">Como funciona uma reunião</h2>
    <p class="sr-section-sub">4 chamadas sequenciais em cerca de 40-90 segundos. Cada agente responde só do que consulta.</p>
</div>

<div class="sr-card mb-5" style="padding: 32px;">
    <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
            <div style="display: flex; gap: 14px;">
                <div class="sr-step-num" style="background: #ecfdf5; color: #065f46;">1</div>
                <div>
                    <strong style="color: #1e293b; display: block; margin-bottom: 6px; font-size: 0.92rem;">Olga fala primeiro</strong>
                    <span style="color: #64748b; font-size: 0.84rem; line-height: 1.5;">Consulta saldo, receita, despesa, score dos projetos ativos.</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div style="display: flex; gap: 14px;">
                <div class="sr-step-num" style="background: #eff6ff; color: #1d4ed8;">2</div>
                <div>
                    <strong style="color: #1e293b; display: block; margin-bottom: 6px; font-size: 0.92rem;">Maria cruza dado</strong>
                    <span style="color: #64748b; font-size: 0.84rem; line-height: 1.5;">Puxa editais cadastrados e vincula com projetos do pipeline.</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div style="display: flex; gap: 14px;">
                <div class="sr-step-num" style="background: #fffbeb; color: #92400e;">3</div>
                <div>
                    <strong style="color: #1e293b; display: block; margin-bottom: 6px; font-size: 0.92rem;">Time Vibra mede canal</strong>
                    <span style="color: #64748b; font-size: 0.84rem; line-height: 1.5;">WhatsApp, e-mail, opt-in da base. Sempre agregado, nunca PII.</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div style="display: flex; gap: 14px;">
                <div class="sr-step-num" style="background: #eef2ff; color: #4338ca;">4</div>
                <div>
                    <strong style="color: #1e293b; display: block; margin-bottom: 6px; font-size: 0.92rem;">Bruce fecha</strong>
                    <span style="color: #64748b; font-size: 0.84rem; line-height: 1.5;">Sintetiza as três vozes e prioriza uma ação. Sem dado bruto.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Reuniões anteriores ═══════════════════════════════════════════════ --}}
<div class="mb-3">
    <h2 class="sr-section-title">Reuniões anteriores</h2>
    <p class="sr-section-sub">Últimas {{ $sessions->count() }} sessões desta organização.</p>
</div>

@if($sessions->isEmpty())
    <div class="sr-card" style="padding: 56px 40px; text-align: center; border-style: dashed; border-color: #e2e8f0; background: #f8fafc; height: auto;">
        <div style="width: 84px; height: 84px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px; color: #94a3b8; font-size: 1.8rem; box-shadow: 0 6px 20px rgba(15,23,42,0.06);">
            <i class="fas fa-comments"></i>
        </div>
        <h3 style="color: #1e293b; font-weight: 900; font-size: 1.3rem; margin-bottom: 10px;">Nenhuma reunião ainda</h3>
        <p style="color: #64748b; max-width: 480px; margin: 0 auto; font-size: 0.92rem;">
            Clique em <strong>Convocar reunião</strong> acima. Em 40-90 segundos a diretoria devolve o diagnóstico e propõe uma ação.
        </p>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 12px;">
        @foreach($sessions as $s)
            @php
                $triggerLabel = match($s->trigger_type){
                    'manual_ui'      => ['icon' => 'fa-mouse-pointer', 'label' => 'Painel'],
                    'manual_debate'  => ['icon' => 'fa-terminal',      'label' => 'CLI (debate)'],
                    'manual_test'    => ['icon' => 'fa-flask',         'label' => 'CLI (teste)'],
                    'auto_health_drop' => ['icon' => 'fa-heart-pulse', 'label' => 'Automático · queda de score'],
                    default          => ['icon' => 'fa-bolt',          'label' => $s->trigger_type],
                };
            @endphp
            <a href="{{ route('strategy-room.show', $s->id) }}" class="sr-session-card">
                <div style="width: 50px; height: 50px; background: #f1f5f9; color: #475569; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.9rem; flex-shrink: 0;">
                    #{{ $s->id }}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px; flex-wrap: wrap;">
                        <span style="font-weight: 900; color: #1e293b; font-size: 0.98rem;">Reunião #{{ $s->id }}</span>
                        <span class="sr-status-chip sr-status-{{ $s->status }}">{{ $s->status }}</span>
                    </div>
                    <div style="color: #64748b; font-size: 0.8rem;">
                        <i class="fas {{ $triggerLabel['icon'] }}" style="margin-right: 4px;"></i> {{ $triggerLabel['label'] }}
                        · <i class="fas fa-comment-dots" style="margin-right: 4px;"></i> {{ $s->messages_count }} {{ $s->messages_count === 1 ? 'fala' : 'falas' }}
                        · <i class="fas fa-clock" style="margin-right: 4px;"></i> {{ $s->created_at->diffForHumans() }}
                    </div>
                </div>
                <div style="color: #94a3b8; font-size: 1.05rem; flex-shrink: 0;">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
