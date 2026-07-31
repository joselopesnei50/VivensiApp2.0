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

    /* ── Card de cota mensal ── */
    .wcs-quota {
        display:grid; grid-template-columns:1fr auto; gap:20px;
        padding:26px 28px; border-radius:18px; margin-bottom:28px;
        border:1px solid #e2e8f0; background:#fff;
    }
    .wcs-quota.state-ok      { background:linear-gradient(135deg,#065f46 0%,#047857 100%); border-color:#047857; color:#fff; }
    .wcs-quota.state-warn    { background:linear-gradient(135deg,#b45309 0%,#d97706 100%); border-color:#d97706; color:#fff; }
    .wcs-quota.state-danger  { background:linear-gradient(135deg,#991b1b 0%,#dc2626 100%); border-color:#dc2626; color:#fff; }
    .wcs-quota.state-inactive{ background:#f8fafc; color:#475569; }
    .wcs-quota.state-inactive .val { color:#0f172a; }
    .wcs-quota .lb   { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; opacity:.85; margin-bottom:6px; }
    .wcs-quota .val  { font-size:1.7rem; font-weight:900; letter-spacing:-.5px; margin:0 0 4px; }
    .wcs-quota .hint { font-size:.82rem; opacity:.9; margin:0 0 12px; }

    /* Barra de progresso */
    .wcs-bar { height:10px; background:rgba(255,255,255,.2); border-radius:99px; overflow:hidden; margin:8px 0 4px; }
    .wcs-quota.state-inactive .wcs-bar { background:#e2e8f0; }
    .wcs-bar-fill { height:100%; background:#fff; border-radius:99px; transition:width .4s; }
    .wcs-quota.state-inactive .wcs-bar-fill { background:#94a3b8; }
    .wcs-bar-legend { font-size:.72rem; opacity:.75; display:flex; justify-content:space-between; }

    .wcs-quota-cta { display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28); color:#fff; padding:11px 22px; border-radius:11px; font-weight:800; font-size:.82rem; text-decoration:none; align-self:center; white-space:nowrap; }
    .wcs-quota-cta:hover { background:rgba(255,255,255,.28); color:#fff; text-decoration:none; }

    /* ── Log de eventos ── */
    .wcs-tx-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:22px 24px; margin-bottom:28px; }
    .wcs-tx-title { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 14px; padding-left:12px; border-left:3px solid #128C7E; }
    .wcs-tx-list { display:flex; flex-direction:column; gap:0; }
    .wcs-tx-row { display:grid; grid-template-columns:32px 1fr auto auto; gap:12px; padding:12px 4px; border-bottom:1px solid #f1f5f9; align-items:center; }
    .wcs-tx-row:last-child { border-bottom:0; }
    .wcs-tx-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.85rem; }
    .wcs-tx-icon.consume    { background:#fee2e2; color:#991b1b; }
    .wcs-tx-icon.extra_pack { background:#dcfce7; color:#166534; }
    .wcs-tx-icon.reset      { background:#dbeafe; color:#1e40af; }
    .wcs-tx-icon.adjustment { background:#fef3c7; color:#a16207; }
    .wcs-tx-desc { color:#0f172a; font-size:.85rem; }
    .wcs-tx-when { color:#94a3b8; font-size:.72rem; margin-top:2px; }
    .wcs-tx-delta { font-weight:800; font-size:.9rem; text-align:right; white-space:nowrap; min-width:80px; }
    .wcs-tx-delta.pos { color:#16a34a; }
    .wcs-tx-delta.neg { color:#dc2626; }
    .wcs-tx-after { color:#94a3b8; font-size:.72rem; text-align:right; min-width:110px; white-space:nowrap; }
    .wcs-tx-empty { color:#94a3b8; font-size:.85rem; text-align:center; padding:24px; }
</style>
@endpush

@section('content')
<div class="wcs-page">

    <div class="wcs-header">
        <h1><i class="fab fa-whatsapp" style="color:#128C7E;"></i> Meu consumo WhatsApp</h1>
        <p>Acompanhe quantas conversas WhatsApp seu tenant iniciou e o custo estimado por período.</p>
    </div>

    {{-- ── Card de cota mensal (Modelo comercial C) ── --}}
    @php
        $used     = $usage->conversations_used_month;
        $planQt   = $usage->plan_included_snapshot;
        $extraQt  = $usage->extra_pack_conversations;
        $total    = $usage->totalAvailable();
        $usagePct = $usage->usagePct();
        $daysLeft = $usage->daysUntilReset();

        $moduleOff = ($planQt === 0 && $extraQt === 0);
        $state = $moduleOff ? 'inactive'
               : ($usage->isOverQuota() ? 'danger'
               : ($usage->isNearQuota() ? 'warn' : 'ok'));

        $hint = match($state) {
            'inactive' => 'O módulo WhatsApp não está incluído no seu plano atual. Fale com o suporte para conhecer os planos com WhatsApp.',
            'ok'       => "Você tem {$total} conversas inclusas este mês. Renova em {$daysLeft} dias.",
            'warn'     => "Você já usou " . $usagePct . "% da sua cota. Considere um pack extra para não interromper os envios.",
            'danger'   => 'Cota esgotada. Envios via WhatsApp Cloud estão bloqueados até renovação mensal ou compra de pack extra.',
        };

        $extraPackSize  = $plan?->whatsapp_extra_pack_size ?? 500;
        $extraPackPrice = $plan?->whatsapp_extra_pack_price_brl ?? 49.90;
    @endphp
    <div class="wcs-quota state-{{ $state }}">
        <div>
            <div class="lb">
                <i class="fas fa-chart-pie"></i> Cota mensal WhatsApp
            </div>
            @if(!$moduleOff)
                <p class="val">{{ number_format($used, 0, ',', '.') }} / {{ number_format($total, 0, ',', '.') }} conversas</p>
                <div class="wcs-bar">
                    <div class="wcs-bar-fill" style="width: {{ min(100, $usagePct) }}%"></div>
                </div>
                <div class="wcs-bar-legend">
                    <span>{{ $usagePct }}% usado</span>
                    <span>Renova em {{ $daysLeft }} {{ $daysLeft === 1 ? 'dia' : 'dias' }}</span>
                </div>
                <p class="hint" style="margin-top:12px;">{{ $hint }}</p>
                @if($extraQt > 0)
                    <p style="margin:8px 0 0; font-size:.75rem; opacity:.85;"><i class="fas fa-plus-circle"></i> Inclui {{ $extraQt }} de pack{{ $extraQt !== 1 ? 's' : '' }} extra{{ $extraQt !== 1 ? 's' : '' }} ativo{{ $extraQt !== 1 ? 's' : '' }} este mês.</p>
                @endif
            @else
                <p class="val">Módulo indisponível</p>
                <p class="hint">{{ $hint }}</p>
            @endif
        </div>
        @if(!$moduleOff && $extraPackSize > 0)
            <a href="https://wa.me/{{ config('app.support_whatsapp', '5511999999999') }}?text=Ola,%20quero%20comprar%20um%20pack%20extra%20de%20{{ $extraPackSize }}%20conversas%20WhatsApp%20por%20R$%20{{ number_format($extraPackPrice, 2, ',', '.') }}." target="_blank" rel="noopener" class="wcs-quota-cta">
                <i class="fas fa-plus-circle"></i> Pack extra +{{ $extraPackSize }} por {{ $moneyBrl($extraPackPrice) }}
            </a>
        @endif
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

    {{-- ── Log de eventos de cota (últimos 25) ── --}}
    @if(!$moduleOff)
    <div class="wcs-tx-wrap">
        <h3 class="wcs-tx-title">Histórico de consumo — últimos 25 eventos</h3>
        @if($quotaEvents->isEmpty())
            <div class="wcs-tx-empty">Nenhum consumo registrado neste ciclo.</div>
        @else
            <div class="wcs-tx-list">
                @foreach($quotaEvents as $ev)
                @php
                    $icoClass = $ev->type;
                    $ico = match($ev->type) {
                        'consume'    => 'fa-arrow-up',
                        'extra_pack' => 'fa-plus',
                        'reset'      => 'fa-rotate',
                        'adjustment' => 'fa-sliders',
                        default      => 'fa-circle',
                    };
                    $deltaClass = $ev->conversations_delta > 0 ? 'pos' : ($ev->conversations_delta < 0 ? 'neg' : '');
                @endphp
                <div class="wcs-tx-row">
                    <div class="wcs-tx-icon {{ $icoClass }}"><i class="fas {{ $ico }}"></i></div>
                    <div>
                        <div class="wcs-tx-desc">{{ $ev->description ?: ucfirst(str_replace('_', ' ', $ev->type)) }}</div>
                        <div class="wcs-tx-when">{{ $ev->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="wcs-tx-delta {{ $deltaClass }}">{{ $ev->signed_label }}</div>
                    <div class="wcs-tx-after">Usadas: {{ $ev->used_after }}</div>
                </div>
                @endforeach
            </div>
        @endif
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
