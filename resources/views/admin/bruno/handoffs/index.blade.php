@extends('layouts.app')
@section('title', 'Bruno — Handoffs')

@push('styles')
<style>
    .brc-hero {
        background: linear-gradient(135deg, #0A0A0B 0%, #1a1a1c 100%);
        border-radius: 20px; padding: 28px 32px; margin-bottom: 22px;
        display: flex; align-items: center; gap: 18px;
        box-shadow: 0 10px 30px rgba(10,10,11,.12);
    }
    .brc-hero__title { color: #F4F4F5; font-weight: 900; font-size: 1.6rem; margin: 0; letter-spacing: -.5px; }
    .brc-hero__sub   { color: #9a9a9e; font-size: .9rem; margin: 4px 0 0; }

    .brc-flash-success { background: rgba(255,122,26,.08); border-left: 4px solid #FF7A1A; color: #0A0A0B; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
    .brc-flash-error   { background: rgba(220,38,38,.08); border-left: 4px solid #dc2626; color: #7f1d1d; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }

    .brc-tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
    .brc-tab {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 10px; text-decoration: none;
        font-weight: 700; font-size: .88rem; background: #fff; color: #6b6b6f;
        border: 2px solid transparent; transition: all .15s;
    }
    .brc-tab:hover { color: #0A0A0B; }
    .brc-tab--active {
        background: #0A0A0B; color: #F4F4F5;
        border-color: #FF7A1A;
    }
    .brc-tab__badge {
        background: rgba(255,122,26,.2); color: #FF7A1A;
        padding: 2px 9px; border-radius: 10px; font-size: .72rem; font-weight: 800;
    }
    .brc-tab--active .brc-tab__badge { background: #FF7A1A; color: #0A0A0B; }

    .brc-card {
        background: #fff; border-radius: 16px; padding: 20px 24px; text-decoration: none;
        color: inherit; box-shadow: 0 2px 10px rgba(10,10,11,.06);
        border: 2px solid transparent; transition: all .18s;
        display: block; margin-bottom: 12px;
    }
    .brc-card:hover {
        border-color: #FF7A1A; transform: translateY(-2px);
        box-shadow: 0 8px 26px rgba(255,122,26,.15); color: inherit;
    }
    .brc-card__head { display: flex; justify-content: space-between; gap: 18px; align-items: start; margin-bottom: 10px; }
    .brc-card__contact { font-weight: 800; color: #0A0A0B; font-size: 1.05rem; margin: 0; }
    .brc-card__phone { color: #6b6b6f; font-size: .78rem; margin: 2px 0 0; }
    .brc-card__time  { color: #9a9a9e; font-size: .78rem; text-align: right; white-space: nowrap; }

    .brc-briefing {
        color: #3A3A3C; font-size: .88rem; line-height: 1.55;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; margin-bottom: 10px;
    }

    .brc-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .brc-chip {
        background: #F4F4F5; color: #3A3A3C; padding: 3px 10px;
        border-radius: 10px; font-size: .7rem; font-weight: 700; letter-spacing: .3px;
    }
    .brc-chip--urgencia-alta   { background: rgba(220,38,38,.1);  color: #dc2626; }
    .brc-chip--urgencia-media  { background: rgba(245,158,11,.12); color: #b45309; }
    .brc-chip--urgencia-baixa  { background: rgba(5,150,105,.12);  color: #059669; }
    .brc-chip--fase            { background: rgba(255,122,26,.1);  color: #B54A00; }

    .brc-empty {
        background: #fff; border-radius: 16px; padding: 60px 20px;
        text-align: center; color: #6b6b6f; border: 2px dashed #d9d9dc;
    }
    .brc-empty__icon { color: #9a9a9e; font-size: 2.5rem; margin-bottom: 14px; }
</style>
@endpush

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <div class="brc-hero">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="48" height="48" style="flex-shrink:0;">
        <div>
            <h1 class="brc-hero__title">Handoffs do Bruno</h1>
            <p class="brc-hero__sub">Leads que o Bruno escalou pro atendimento humano — com briefing pronto pra você assumir.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="brc-flash-success">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="brc-flash-error">✗ {{ session('error') }}</div>
    @endif

    <div class="brc-tabs">
        <a href="{{ route('admin.bruno.handoffs.index', ['status' => 'pendente']) }}"
           class="brc-tab {{ $status === 'pendente' ? 'brc-tab--active' : '' }}">
            <i class="fas fa-inbox"></i> Pendentes
            <span class="brc-tab__badge">{{ number_format($counts['pendente']) }}</span>
        </a>
        <a href="{{ route('admin.bruno.handoffs.index', ['status' => 'assumido']) }}"
           class="brc-tab {{ $status === 'assumido' ? 'brc-tab--active' : '' }}">
            <i class="fas fa-user-check"></i> Assumidos
            <span class="brc-tab__badge">{{ number_format($counts['assumido']) }}</span>
        </a>
        <a href="{{ route('admin.bruno.handoffs.index', ['status' => 'resolvido']) }}"
           class="brc-tab {{ $status === 'resolvido' ? 'brc-tab--active' : '' }}">
            <i class="fas fa-check-double"></i> Resolvidos
            <span class="brc-tab__badge">{{ number_format($counts['resolvido']) }}</span>
        </a>
    </div>

    @if($handoffs->isEmpty())
        <div class="brc-empty">
            <div class="brc-empty__icon"><i class="fas fa-inbox"></i></div>
            <div style="font-weight:700; color:#0A0A0B; font-size:1.05rem; margin-bottom:6px;">
                Nenhum handoff {{ $status }} agora
            </div>
            <p style="margin:0; font-size:.9rem;">
                @if($status === 'pendente')
                    Ótimo — o Bruno tá dando conta sozinho.
                @else
                    Nada por aqui ainda.
                @endif
            </p>
        </div>
    @else
        @foreach($handoffs as $h)
            @php
                $sd = $h->structured_data ?? [];
                $org = $sd['organizacao'] ?? null;
                $cidade = $sd['cidade'] ?? null;
                $urg = $sd['urgencia'] ?? null;
                $fase = $sd['fase_funil'] ?? null;
            @endphp
            <a href="{{ route('admin.bruno.handoffs.show', $h) }}" class="brc-card">
                <div class="brc-card__head">
                    <div>
                        <p class="brc-card__contact">{{ $h->chat->contact_name ?: 'Sem nome' }}</p>
                        <p class="brc-card__phone">
                            {{ $h->chat->contact_phone ?: $h->chat->wa_id }}
                            @if($org) · {{ $org }} @endif
                            @if($cidade) · {{ $cidade }} @endif
                        </p>
                    </div>
                    <div class="brc-card__time">
                        {{ $h->created_at->diffForHumans() }}
                        @if($h->assumedBy)
                            <br><small>por {{ $h->assumedBy->name }}</small>
                        @endif
                    </div>
                </div>

                <div class="brc-briefing">{{ $h->briefing }}</div>

                <div class="brc-meta">
                    @if($urg)
                        <span class="brc-chip brc-chip--urgencia-{{ $urg }}">urgência {{ $urg }}</span>
                    @endif
                    @if($fase)
                        <span class="brc-chip brc-chip--fase">{{ $fase }}</span>
                    @endif
                    @if(!empty($sd['area_atuacao']))
                        <span class="brc-chip">{{ $sd['area_atuacao'] }}</span>
                    @endif
                </div>
            </a>
        @endforeach

        <div style="margin-top:16px;">{{ $handoffs->links() }}</div>
    @endif
</div>
@endsection
