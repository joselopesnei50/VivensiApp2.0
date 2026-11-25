@extends('layouts.app')
@section('title', 'Adicionais')

@push('styles')
<style>
    .ad-page { max-width: 1000px; margin: 32px auto; padding: 0 20px; }
    .ad-hero h1 { font-size: 1.7rem; font-weight: 800; color: var(--text-primary, #0f172a); margin: 0 0 6px; }
    .ad-hero p { color: #64748b; margin: 0 0 24px; font-size: .95rem; }
    .ad-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; margin-bottom: 28px; }
    .ad-card { background: var(--card-bg, #fff); border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; display: flex; flex-direction: column; gap: 12px; }
    .ad-card h3 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--text-primary, #0f172a); }
    .ad-card .desc { margin: 0; color: #64748b; font-size: .88rem; line-height: 1.5; }
    .ad-price { font-size: 1.3rem; font-weight: 800; color: #4338ca; }
    .ad-price small { font-size: .8rem; color: #64748b; font-weight: 600; }
    .ad-now { font-size: .85rem; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
    .ad-form { display: flex; gap: 10px; align-items: center; margin-top: auto; flex-wrap: wrap; }
    .ad-form input { width: 90px; padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 9px; font-weight: 700; }
    .ad-btn { padding: 9px 16px; border-radius: 9px; border: none; background: #4f46e5; color: #fff; font-weight: 700; cursor: pointer; font-size: .85rem; }
    .ad-btn.light { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .ad-btn.danger { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
    .ad-note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; color: #475569; font-size: .88rem; line-height: 1.6; margin-bottom: 24px; }
    .ad-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .ad-team { background: var(--card-bg, #fff); border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
    .ad-team-head { padding: 18px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .ad-team-head h2 { margin: 0; font-size: 1.1rem; font-weight: 800; }
    .ad-seats { font-weight: 800; color: #4338ca; }
    .ad-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 22px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .ad-row:last-child { border-bottom: none; }
    .ad-row .who strong { display: block; color: var(--text-primary, #0f172a); }
    .ad-row .who span { font-size: .8rem; color: #64748b; }
    .ad-tag { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 800; }
    .ad-tag.on { background: #dcfce7; color: #15803d; }
    .ad-tag.off { background: #f1f5f9; color: #64748b; }
</style>
@endpush

@section('content')
@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $botFull = $botUsed >= $botLimit;
@endphp
<div class="ad-page">
    <div class="ad-hero">
        <h1><i class="fas fa-puzzle-piece" style="color:#4f46e5;"></i> Adicionais</h1>
        <p>Contrate mais números no bot de gestão e mais e-mails por dia. Tudo vem na mesma fatura do plano.</p>
    </div>

    @if(!$canContract)
        <div class="ad-note ad-warn">
            <i class="fas fa-info-circle me-1"></i>
            Adicionais podem ser contratados por contas com plano pago ativo. Se precisar, fale com o nosso suporte.
        </div>
    @else
        <div class="ad-note">
            <i class="fas fa-bolt me-1" style="color:#4f46e5;"></i>
            O adicional é <strong>liberado na hora</strong> e a cobrança entra na <strong>próxima fatura</strong>, com o valor do mês cheio.
            @if($isYearly) No plano anual, o adicional entra na fatura anual (valor mensal × 12). @endif
            Para cancelar, é só colocar 0.
        </div>
    @endif

    <div class="ad-grid">
        @foreach($catalog as $addon)
            @php $qty = (int) ($quantities[$addon->code] ?? 0); @endphp
            <div class="ad-card">
                <h3>
                    @if($addon->code === \App\Models\BillingAddon::BOT_NUMBER)<i class="fab fa-whatsapp" style="color:#16a34a;"></i>@else<i class="fas fa-envelope-open-text" style="color:#6366f1;"></i>@endif
                    {{ $addon->name }}
                </h3>
                @if($addon->description)<p class="desc">{{ $addon->description }}</p>@endif
                <div class="ad-price">{{ $brl($addon->unit_price) }} <small>por mês, cada</small></div>

                <div class="ad-now">
                    @if($addon->code === \App\Models\BillingAddon::BOT_NUMBER)
                        Você tem <strong>{{ $botLimit }}</strong> {{ $botLimit === 1 ? 'número' : 'números' }} no bot
                        ({{ $included }} incluso no plano{{ $qty > 0 ? ' + ' . $qty . ' extra' . ($qty > 1 ? 's' : '') : '' }}).
                    @else
                        Sua cota hoje: <strong>{{ $emailBase + $emailExtra }}</strong> e-mails por dia
                        ({{ $emailBase }} do plano{{ $emailExtra > 0 ? ' + ' . $emailExtra . ' dos pacotes' : '' }}).
                    @endif
                    @if($qty > 0)
                        <br>Contratado: <strong>{{ $qty }}</strong> · {{ $brl($qty * (float) $addon->unit_price) }} por mês
                    @endif
                </div>

                <form class="ad-form" method="POST" action="{{ route('client.addons.update', $addon->code) }}"
                      onsubmit="return confirm('Confirmar? A cobrança entra na próxima fatura.');">
                    @csrf
                    <label for="qty-{{ $addon->code }}" style="font-size:.85rem;font-weight:700;color:#475569;">Quantidade</label>
                    <input type="number" id="qty-{{ $addon->code }}" name="quantity" min="0" max="{{ $addon->max_quantity }}" value="{{ $qty > 0 ? $qty : 1 }}" @disabled(!$canContract && $qty === 0)>
                    <button type="submit" class="ad-btn" @disabled(!$canContract && $qty === 0)>{{ $qty > 0 ? 'Alterar' : 'Contratar' }}</button>
                </form>
                @if($qty > 0)
                    <form method="POST" action="{{ route('client.addons.update', $addon->code) }}" onsubmit="return confirm('Cancelar este adicional?');">
                        @csrf
                        <input type="hidden" name="quantity" value="0">
                        <button type="submit" class="ad-btn danger"><i class="fas fa-times me-1"></i> Cancelar adicional</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Quem usa o bot --}}
    <div class="ad-team" id="bot">
        <div class="ad-team-head">
            <div>
                <h2><i class="fab fa-whatsapp" style="color:#16a34a;"></i> Quem usa o bot de gestão</h2>
                <span style="font-size:.85rem;color:#64748b;">A pessoa precisa ter o WhatsApp cadastrado no perfil.</span>
            </div>
            <span class="ad-seats">{{ $botUsed }} de {{ $botLimit }} em uso</span>
        </div>
        @forelse($members as $m)
            @php $hasPhone = !empty($m->phone); $suspended = ($m->status ?? 'active') === 'suspended'; @endphp
            <div class="ad-row">
                <div class="who">
                    <strong>{{ $m->name }} @if($m->id === auth()->id())<small style="color:#94a3b8;">(você)</small>@endif</strong>
                    <span>
                        {{ \App\Support\TeamAccess::label($m->role) }}
                        · {{ $hasPhone ? 'WhatsApp cadastrado' : 'sem WhatsApp no perfil' }}
                        @if($suspended) · acesso suspenso @endif
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="ad-tag {{ $m->bot_access ? 'on' : 'off' }}">{{ $m->bot_access ? 'Usa o bot' : 'Sem bot' }}</span>
                    <form method="POST" action="{{ route('client.addons.bot', $m->id) }}">
                        @csrf
                        <input type="hidden" name="on" value="{{ $m->bot_access ? 0 : 1 }}">
                        @if($m->bot_access)
                            <button type="submit" class="ad-btn danger">Tirar acesso</button>
                        @else
                            <button type="submit" class="ad-btn light" @disabled($botFull || $suspended)
                                    title="{{ $botFull ? 'Todos os números estão em uso. Contrate um número extra.' : '' }}">Liberar bot</button>
                        @endif
                    </form>
                </div>
            </div>
        @empty
            <div class="ad-row"><span style="color:#64748b;">Nenhuma pessoa na equipe.</span></div>
        @endforelse
    </div>

    <p style="margin-top:18px;font-size:.85rem;color:#64748b;">
        <a href="{{ route('client.invoices.index') }}"><i class="fas fa-file-invoice-dollar me-1"></i> Ver minhas faturas</a>
    </p>
</div>
@endsection
