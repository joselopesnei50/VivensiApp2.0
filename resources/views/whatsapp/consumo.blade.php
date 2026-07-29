@extends('layouts.app')

@section('title', 'Consumo WhatsApp')

@php
    $categoryLabels = [
        'marketing'           => ['label' => 'Promocional', 'desc' => 'Mensagens de marketing e promoção.', 'color' => '#EC4899'],
        'utility'             => ['label' => 'Transacional', 'desc' => 'Confirmações, atualizações, alertas.', 'color' => '#3B82F6'],
        'authentication'      => ['label' => 'Autenticação', 'desc' => 'Códigos de verificação (OTP).', 'color' => '#8B5CF6'],
        'service'             => ['label' => 'Atendimento',  'desc' => 'Iniciada pelo cliente na janela de 24h — sempre grátis.', 'color' => '#10B981'],
        'referral_conversion' => ['label' => 'Referral',     'desc' => 'Conversão de anúncio click-to-WhatsApp — grátis.', 'color' => '#F59E0B'],
    ];
    $moneyBrl = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
@endphp

@push('styles')
<style>
    .wcs-page { max-width: 1100px; margin: 32px auto; padding: 0 20px; }

    .wcs-header { margin-bottom: 24px; }
    .wcs-header h1 {
        font-size: 1.7rem; font-weight: 800; color: #0f172a;
        margin: 0 0 6px;
    }
    .wcs-header p { color: #64748b; margin: 0; font-size: .95rem; }

    /* Filtro de período */
    .wcs-filter {
        display: inline-flex;
        background: #f1f5f9; border-radius: 10px; padding: 4px;
        gap: 4px; margin-bottom: 24px;
    }
    .wcs-filter a {
        padding: 8px 18px; border-radius: 8px;
        color: #475569; font-weight: 600; font-size: .88rem;
        text-decoration: none; transition: all .15s;
    }
    .wcs-filter a.active {
        background: #128C7E; color: #fff;
    }
    .wcs-filter a:hover:not(.active) { background: rgba(148,163,184,.15); }

    /* Cards de resumo */
    .wcs-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px; margin-bottom: 28px;
    }
    .wcs-summary-card {
        background: #fff; padding: 22px; border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 12px rgba(15,23,42,.04);
    }
    .wcs-summary-card .wcs-label {
        font-size: .75rem; font-weight: 700; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .04em; margin: 0 0 6px;
    }
    .wcs-summary-card .wcs-value {
        font-size: 2rem; font-weight: 800; color: #0f172a; margin: 0;
        line-height: 1.2;
    }
    .wcs-summary-card .wcs-sub {
        color: #64748b; font-size: .85rem; margin-top: 4px;
    }
    .wcs-summary-card.highlight {
        background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
        color: #fff; border: 0;
    }
    .wcs-summary-card.highlight .wcs-label { color: rgba(255,255,255,.7); }
    .wcs-summary-card.highlight .wcs-value { color: #fff; }
    .wcs-summary-card.highlight .wcs-sub   { color: rgba(255,255,255,.85); }

    /* Aviso Modelo B */
    .wcs-notice {
        background: #EFF6FF; border-left: 4px solid #3B82F6;
        padding: 16px 20px; border-radius: 10px;
        color: #1E3A8A; margin-bottom: 28px;
        font-size: .92rem; line-height: 1.55;
    }
    .wcs-notice strong { display: block; margin-bottom: 4px; font-weight: 700; }

    /* Card genérico */
    .wcs-card {
        background: #fff; border-radius: 14px; padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 12px rgba(15,23,42,.04);
        margin-bottom: 20px;
    }
    .wcs-card h3 {
        font-size: 1.02rem; font-weight: 800; color: #0f172a;
        margin: 0 0 4px;
    }
    .wcs-card .wcs-card-sub {
        color: #64748b; font-size: .85rem; margin: 0 0 20px;
    }

    /* Tabela de categorias */
    .wcs-cat-list { list-style: none; padding: 0; margin: 0; }
    .wcs-cat-item {
        display: grid;
        grid-template-columns: 40px 1fr auto auto;
        gap: 14px; align-items: center;
        padding: 14px 0; border-bottom: 1px solid #f1f5f9;
    }
    .wcs-cat-item:last-child { border-bottom: 0; }
    .wcs-cat-dot {
        width: 12px; height: 12px; border-radius: 50%;
        margin-left: 14px;
    }
    .wcs-cat-info strong {
        display: block; color: #0f172a; font-weight: 700; font-size: .95rem;
    }
    .wcs-cat-info span {
        color: #64748b; font-size: .82rem;
    }
    .wcs-cat-count {
        color: #475569; font-size: .88rem; font-weight: 600;
        white-space: nowrap;
    }
    .wcs-cat-cost {
        color: #0f172a; font-size: 1rem; font-weight: 800;
        text-align: right; min-width: 100px; white-space: nowrap;
    }

    /* Estado vazio */
    .wcs-empty {
        text-align: center; padding: 48px 24px;
    }
    .wcs-empty i {
        font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;
    }
    .wcs-empty h3 {
        color: #0f172a; font-size: 1.1rem; font-weight: 700; margin: 0 0 6px;
    }
    .wcs-empty p { color: #64748b; margin: 0; }

    /* Timeline (mini bar chart) */
    .wcs-timeline {
        display: flex; align-items: flex-end; gap: 4px;
        height: 120px; margin-top: 16px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 4px;
    }
    .wcs-tl-bar {
        flex: 1; min-width: 6px;
        background: #E2E8F0; border-radius: 4px 4px 0 0;
        position: relative;
        transition: background .15s;
    }
    .wcs-tl-bar:hover { background: #128C7E; }
    .wcs-tl-bar .wcs-tl-tip {
        display: none; position: absolute; bottom: 100%; left: 50%;
        transform: translateX(-50%);
        background: #0f172a; color: #fff;
        padding: 6px 10px; border-radius: 6px;
        font-size: .75rem; white-space: nowrap;
        margin-bottom: 4px; z-index: 10;
    }
    .wcs-tl-bar:hover .wcs-tl-tip { display: block; }
    .wcs-tl-x {
        display: flex; justify-content: space-between;
        color: #94a3b8; font-size: .75rem; margin-top: 6px;
    }
</style>
@endpush

@section('content')
<div class="wcs-page">

    <div class="wcs-header">
        <h1><i class="fab fa-whatsapp" style="color:#128C7E;"></i> Meu consumo WhatsApp</h1>
        <p>Acompanhe quantas conversas WhatsApp seu tenant iniciou e o custo estimado por período.</p>
    </div>

    {{-- Filtro de período --}}
    <div class="wcs-filter" role="tablist">
        <a href="{{ route('whatsapp.consumo', ['days' => 7]) }}"  class="{{ $days === 7  ? 'active' : '' }}">7 dias</a>
        <a href="{{ route('whatsapp.consumo', ['days' => 30]) }}" class="{{ $days === 30 ? 'active' : '' }}">30 dias</a>
        <a href="{{ route('whatsapp.consumo', ['days' => 90]) }}" class="{{ $days === 90 ? 'active' : '' }}">90 dias</a>
    </div>

    {{-- Aviso Modelo B (transparência) --}}
    <div class="wcs-notice">
        <strong><i class="fas fa-info-circle"></i> Como funciona a cobrança</strong>
        A Meta cobra <strong>diretamente</strong> pelo cartão vinculado à sua conta WhatsApp Business.
        Os valores abaixo são estimativas em Reais (câmbio USD {{ number_format($usdBrlRate, 2, ',', '.') }})
        pra você acompanhar. O Vivensi <strong>não</strong> cobra por mensagens — a assinatura mensal do Vivensi
        já inclui o uso ilimitado da plataforma.
    </div>

    {{-- Cards de resumo --}}
    <div class="wcs-summary">
        <div class="wcs-summary-card highlight">
            <p class="wcs-label">Estimativa Meta ({{ $days }} dias)</p>
            <p class="wcs-value">{{ $moneyBrl($summary['total_cost_brl']) }}</p>
            <p class="wcs-sub">
                US$ {{ number_format($summary['total_cost_usd'], 2, ',', '.') }} @ R$ {{ number_format($usdBrlRate, 2, ',', '.') }}
            </p>
        </div>

        <div class="wcs-summary-card">
            <p class="wcs-label">Conversas iniciadas</p>
            <p class="wcs-value">{{ number_format($summary['total_conversations'], 0, ',', '.') }}</p>
            <p class="wcs-sub">Cada conversa vale por 24h após a 1ª mensagem</p>
        </div>

        <div class="wcs-summary-card">
            <p class="wcs-label">Período analisado</p>
            <p class="wcs-value" style="font-size: 1.15rem; line-height: 1.4;">
                {{ $from->format('d/m') }} — {{ $to->format('d/m/Y') }}
            </p>
            <p class="wcs-sub">Últimos {{ $days }} dias</p>
        </div>
    </div>

    {{-- Breakdown por categoria --}}
    <div class="wcs-card">
        <h3>Distribuição por categoria</h3>
        <p class="wcs-card-sub">
            A Meta cobra diferente por tipo de conversa. Atendimento (iniciado pelo cliente na janela de 24h) é grátis.
        </p>

        @if($byCategory->isEmpty())
            <div class="wcs-empty">
                <i class="fas fa-comments"></i>
                <h3>Nenhuma conversa faturável ainda</h3>
                <p>Quando você começar a enviar mensagens, o consumo aparece aqui.</p>
            </div>
        @else
            <ul class="wcs-cat-list">
                @foreach($byCategory as $cat)
                    @php
                        $info = $categoryLabels[$cat->category] ?? ['label' => ucfirst($cat->category), 'desc' => '', 'color' => '#64748b'];
                    @endphp
                    <li class="wcs-cat-item">
                        <span class="wcs-cat-dot" style="background: {{ $info['color'] }};"></span>
                        <div class="wcs-cat-info">
                            <strong>{{ $info['label'] }}</strong>
                            <span>{{ $info['desc'] }}</span>
                        </div>
                        <div class="wcs-cat-count">
                            {{ number_format($cat->conversations, 0, ',', '.') }}
                            {{ $cat->conversations === 1 ? 'conversa' : 'conversas' }}
                        </div>
                        <div class="wcs-cat-cost">{{ $moneyBrl($cat->cost_brl) }}</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Timeline (só se houver dados) --}}
    @if($timeline->isNotEmpty())
        <div class="wcs-card">
            <h3>Últimos {{ $days }} dias</h3>
            <p class="wcs-card-sub">Passe o mouse pra ver detalhes de cada dia.</p>

            @php
                $maxCost = $timeline->max('cost_brl') ?: 1;
            @endphp

            <div class="wcs-timeline">
                @foreach($timeline as $row)
                    @php
                        $pct = max(4, ($row->cost_brl / $maxCost) * 100);
                    @endphp
                    <div class="wcs-tl-bar" style="height: {{ $pct }}%;">
                        <div class="wcs-tl-tip">
                            {{ \Carbon\Carbon::parse($row->day)->format('d/m') }}:
                            {{ number_format($row->conversations, 0, ',', '.') }}
                            {{ $row->conversations === 1 ? 'conversa' : 'conversas' }}
                            ({{ $moneyBrl($row->cost_brl) }})
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="wcs-tl-x">
                <span>{{ \Carbon\Carbon::parse($timeline->first()->day)->format('d/m') }}</span>
                <span>{{ \Carbon\Carbon::parse($timeline->last()->day)->format('d/m') }}</span>
            </div>
        </div>
    @endif

    {{-- Rodapé educativo --}}
    <div class="wcs-card" style="background: #F8FAFC;">
        <h3>Como reduzir seus custos WhatsApp</h3>
        <ul style="color: #475569; line-height: 1.7; margin: 8px 0 0; padding-left: 20px;">
            <li><strong>Respondam dentro de 24h</strong> — se o cliente puxa a conversa e você responde na janela, o Meta considera "atendimento" e não cobra.</li>
            <li><strong>Use templates de utilidade</strong> pra confirmações e alertas (mais barato que promocional).</li>
            <li><strong>Guarde o promocional pra momentos-chave</strong> — campanhas custam mais mas convertem melhor quando bem segmentadas.</li>
            <li>Precisa de mais orientação? <a href="https://wa.me/5516988392853?text=Olá,%20quero%20ajuda%20com%20consumo%20WhatsApp." target="_blank" rel="noopener" style="color:#128C7E;font-weight:600;">Fale com o suporte Vivensi</a>.</li>
        </ul>
    </div>

</div>
@endsection
