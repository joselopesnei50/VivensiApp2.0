@extends('layouts.app')
@section('title', 'Chat do site')

@section('content')
@php
    $tabs = ['abertas' => 'Abertas', 'equipe' => 'Com a equipe', 'agendadas' => 'Com reunião', 'encerradas' => 'Encerradas', 'todas' => 'Todas'];
    $pill = [
        'waiting_human' => ['#fef3c7', '#92400e'],
        'human'         => ['#dcfce7', '#166534'],
        'bot'           => ['#e0e7ff', '#3730a3'],
        'closed'        => ['#f1f5f9', '#64748b'],
    ];
@endphp

<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-grow-1" style="min-width:0;">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">Admin / Atendimento</div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Chat do site</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Conversas do Bruno com visitantes do site. Quando ele passa para a equipe, a conversa sobe para o topo.</p>
    </div>
    <form method="POST" action="{{ route('admin.web_chat.settings') }}" class="d-flex align-items-center gap-2 flex-shrink-0">
        @csrf
        <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
        <span class="wc-state {{ $enabled ? 'on' : '' }}"><span></span>{{ $enabled ? 'Ligado no site' : 'Desligado' }}</span>
        <button class="btn {{ $enabled ? 'btn-outline-secondary' : 'btn-primary' }} fw-bold rounded-3">
            {{ $enabled ? 'Desligar' : 'Ligar o chat no site' }}
        </button>
    </form>
</div>

@foreach(['success' => ['#ecfdf5', '#065f46', '#a7f3d0'], 'error' => ['#fef2f2', '#991b1b', '#fecaca']] as $k => $c)
    @if(session($k))
        <div style="background:{{ $c[0] }};color:{{ $c[1] }};border:1px solid {{ $c[2] }};padding:12px 16px;border-radius:12px;margin-bottom:16px;font-weight:700;font-size:.88rem;">{{ session($k) }}</div>
    @endif
@endforeach

@unless($brunoReady)
    <div style="background:#fffbeb;color:#92400e;border:1px solid #fde68a;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:.86rem;">
        O tenant do Bruno (bot vendedor) não está configurado. Sem ele o chat não liga.
    </div>
@endunless

<div class="row g-3 mb-4">
    @foreach([
        ['Conversas (30 dias)', $stats['conversations'], '#4f46e5'],
        ['Reuniões marcadas pelo chat', $stats['bookings'], '#059669'],
        ['Passadas para a equipe', $stats['handoffs'], '#d97706'],
        ['Aguardando agora', $stats['waiting'], $stats['waiting'] ? '#dc2626' : '#94a3b8'],
    ] as [$label, $value, $color])
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;">{{ $label }}</div>
                <div style="font-size:1.75rem;font-weight:800;color:{{ $color }};line-height:1.15;margin-top:4px;">{{ number_format($value, 0, ',', '.') }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3" style="border-bottom:1px solid #f1f5f9;">
        <div class="wc-tabs">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.web_chat.index', ['status' => $key, 'q' => $q ?: null]) }}" class="{{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Nome, e-mail ou telefone" style="min-width:220px;">
        </form>
    </div>

    @forelse($conversations as $c)
        @php $last = $lastMessages[$c->id] ?? null; $p = $pill[$c->status] ?? $pill['closed']; @endphp
        <a href="{{ route('admin.web_chat.show', $c) }}" class="wc-row">
            <div class="wc-avatar">{{ mb_strtoupper(mb_substr($c->visitor_name ?: 'V', 0, 1)) }}</div>
            <div style="flex:1;min-width:0;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong style="color:#0f172a;">{{ $c->displayName() }}</strong>
                    <span class="wc-pill" style="background:{{ $p[0] }};color:{{ $p[1] }};">{{ $c->statusLabel() }}</span>
                    @if($c->meeting_booking_id)<span class="wc-pill" style="background:#ecfdf5;color:#047857;">📅 reunião marcada</span>@endif
                    @if($c->unread_for_team > 0 && $c->status !== 'bot')<span class="wc-pill" style="background:#ef4444;color:#fff;">{{ $c->unread_for_team }} nova(s)</span>@endif
                </div>
                <div class="text-truncate" style="font-size:.84rem;color:#64748b;margin-top:3px;">
                    @if($last)<span style="color:#94a3b8;">{{ ['visitor' => 'Visitante', 'bot' => 'Bruno', 'agent' => 'Equipe'][$last->sender] ?? '' }}:</span> {{ \Illuminate\Support\Str::limit($last->body, 140) }}@endif
                </div>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:3px;">
                    {{ $c->page_title ?: 'Página não identificada' }} · {{ \App\Support\TrafficClassifier::sourceLabel($c->source) }} · {{ \App\Support\TrafficClassifier::deviceLabel($c->device) }}
                    @if($c->assignedUser) · com {{ strtok($c->assignedUser->name, ' ') }} @endif
                </div>
            </div>
            <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">{{ $c->last_message_at?->diffForHumans() }}</div>
        </a>
    @empty
        <p class="text-muted p-4 mb-0" style="font-size:.88rem;">Nenhuma conversa {{ $filter === 'todas' ? '' : 'neste filtro' }} ainda.</p>
    @endforelse

    @if($conversations->hasPages())
        <div class="p-3">{{ $conversations->links() }}</div>
    @endif
</div>

@push('styles')
<style>
.wc-state{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#64748b;}
.wc-state span{width:8px;height:8px;border-radius:50%;background:#cbd5e1;}
.wc-state.on{color:#047857;} .wc-state.on span{background:#22c55e;}
.wc-tabs{display:inline-flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:2px;flex-wrap:wrap;}
.wc-tabs a{padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:700;color:#64748b;text-decoration:none;}
.wc-tabs a.active{background:#fff;color:#4f46e5;box-shadow:0 1px 2px rgba(15,23,42,.08);}
.wc-row{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;}
.wc-row:hover{background:#f8fafc;}
.wc-avatar{width:38px;height:38px;flex-shrink:0;border-radius:50%;background:#eef2ff;color:#4f46e5;font-weight:800;display:flex;align-items:center;justify-content:center;}
.wc-pill{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.66rem;font-weight:800;}
</style>
@endpush
@endsection
