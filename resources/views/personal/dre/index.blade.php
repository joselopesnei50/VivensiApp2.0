@extends('layouts.app')

@section('content')
@php
    $fmtBr = fn ($n) => 'R$ ' . number_format((float) $n, 2, ',', '.');
    $fmtPct = function ($v) {
        if ($v === null) return '—';
        $sign = $v > 0 ? '+' : '';
        return $sign . number_format($v, 1, ',', '.') . '%';
    };
    $trendColor = fn ($v, $goodUp = true) => $v === null ? '#94a3b8' : (($v > 0) === $goodUp ? '#059669' : '#dc2626');
@endphp

<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Financeiro</h6>
    </div>
    <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.4rem; letter-spacing: -1px;">DRE &amp; Análise</h2>
    <p style="color: #64748b; margin: 6px 0 0 0; font-size: 1rem; font-weight: 500;">Demonstrativo de resultados, comparativo com período anterior, top clientes e distribuição por categoria.</p>
</div>

<div class="vivensi-card" style="padding:20px 24px; margin-bottom:20px;">
    <form method="GET" action="{{ route('personal.dre.index') }}" style="display:flex; align-items:end; gap:14px; flex-wrap:wrap;">
        <div style="flex:1; min-width:220px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Período</label>
            <select name="period" class="form-select" onchange="toggleCustom(this.value); this.form.submit()" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
                @foreach($presets as $key => $label)
                    <option value="{{ $key }}" @selected($preset === $key)>{{ $label }}</option>
                @endforeach
                <option value="custom" @selected($preset === 'custom')>Personalizado…</option>
            </select>
        </div>
        <div id="custom-from" style="min-width:170px; display: {{ $preset === 'custom' ? 'block' : 'none' }};">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase;">De</label>
            <input type="date" name="from" value="{{ request('from', $period['from']->toDateString()) }}" class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <div id="custom-to" style="min-width:170px; display: {{ $preset === 'custom' ? 'block' : 'none' }};">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase;">Até</label>
            <input type="date" name="to" value="{{ request('to', $period['to']->toDateString()) }}" class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <button type="submit" class="btn-premium" style="background:#4f46e5; color:white; border:none; padding:10px 20px; font-weight:800;">
            <i class="fas fa-chart-simple"></i> Atualizar
        </button>
        <div style="font-size:.78rem; color:#64748b; font-weight:600; text-align:right; margin-left:auto;">
            {{ $period['from']->translatedFormat('d \d\e M \d\e Y') }} até {{ $period['to']->translatedFormat('d \d\e M \d\e Y') }}
        </div>
    </form>
</div>

<div class="row g-3" style="margin-bottom: 20px;">
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:22px; background:linear-gradient(135deg,#ecfdf5,#ffffff); border:1px solid #d1fae5;">
            <div style="font-size:.7rem; color:#065f46; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Receita (pago)</div>
            <div style="font-size:1.7rem; color:#065f46; font-weight:900; letter-spacing:-.5px; margin-top:6px;">{{ $fmtBr($kpis['income']) }}</div>
            <div style="font-size:.75rem; color:{{ $trendColor($kpis['income_delta']) }}; font-weight:800; margin-top:4px;">
                <i class="fas fa-arrow-{{ ($kpis['income_delta'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                {{ $fmtPct($kpis['income_delta']) }} vs período anterior
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:22px; background:linear-gradient(135deg,#fef2f2,#ffffff); border:1px solid #fecaca;">
            <div style="font-size:.7rem; color:#991b1b; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Despesas (pago)</div>
            <div style="font-size:1.7rem; color:#991b1b; font-weight:900; letter-spacing:-.5px; margin-top:6px;">{{ $fmtBr($kpis['expense']) }}</div>
            <div style="font-size:.75rem; color:{{ $trendColor($kpis['expense_delta'], false) }}; font-weight:800; margin-top:4px;">
                <i class="fas fa-arrow-{{ ($kpis['expense_delta'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                {{ $fmtPct($kpis['expense_delta']) }} vs período anterior
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:22px; background:linear-gradient(135deg, {{ $kpis['result'] >= 0 ? '#eef2ff' : '#fef3c7' }}, #ffffff); border:1px solid {{ $kpis['result'] >= 0 ? '#c7d2fe' : '#fde68a' }};">
            <div style="font-size:.7rem; color:{{ $kpis['result'] >= 0 ? '#3730a3' : '#92400e' }}; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Resultado</div>
            <div style="font-size:1.7rem; color:{{ $kpis['result'] >= 0 ? '#3730a3' : '#92400e' }}; font-weight:900; letter-spacing:-.5px; margin-top:6px;">{{ $fmtBr($kpis['result']) }}</div>
            <div style="font-size:.75rem; color:{{ $trendColor($kpis['result_delta']) }}; font-weight:800; margin-top:4px;">
                {{ $fmtPct($kpis['result_delta']) }} vs período anterior
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:22px; background:white; border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#475569; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Margem líquida</div>
            <div style="font-size:1.7rem; color:{{ ($kpis['margin'] ?? 0) >= 0 ? '#1e293b' : '#dc2626' }}; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $kpis['margin'] === null ? '—' : number_format($kpis['margin'], 1, ',', '.') . '%' }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Resultado / Receita</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 20px;">
            <h5 style="margin: 0 0 18px 0; font-weight: 900; color: #1e293b; font-size: 1.05rem; text-transform: uppercase; letter-spacing: 1px;">Demonstrativo Estruturado</h5>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <tr style="background:#ecfdf5;">
                        <td style="padding:12px 14px; font-weight:900; color:#065f46; border-radius:8px 0 0 8px;">(+) Receita Bruta</td>
                        <td style="padding:12px 14px; text-align:right; font-weight:900; color:#065f46; border-radius:0 8px 8px 0;">{{ $fmtBr($kpis['income']) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px; color:#94a3b8; font-style:italic;">(−) Deduções / Tributos</td>
                        <td style="padding:12px 14px; text-align:right; color:#94a3b8;">R$ 0,00 <span style="font-size:.7rem;">(integração contábil na Onda 3)</span></td>
                    </tr>
                    <tr style="background:#f8fafc;">
                        <td style="padding:12px 14px; font-weight:800; color:#1e293b; border-radius:8px 0 0 8px;">(=) Receita Líquida</td>
                        <td style="padding:12px 14px; text-align:right; font-weight:800; color:#1e293b; border-radius:0 8px 8px 0;">{{ $fmtBr($kpis['income']) }}</td>
                    </tr>
                    <tr style="background:#fef2f2;">
                        <td style="padding:12px 14px; font-weight:900; color:#991b1b; border-radius:8px 0 0 8px;">(−) Custos e Despesas</td>
                        <td style="padding:12px 14px; text-align:right; font-weight:900; color:#991b1b; border-radius:0 8px 8px 0;">{{ $fmtBr($kpis['expense']) }}</td>
                    </tr>
                    <tr style="background: {{ $kpis['result'] >= 0 ? '#dcfce7' : '#fee2e2' }}; border-top:3px solid {{ $kpis['result'] >= 0 ? '#059669' : '#dc2626' }};">
                        <td style="padding:16px 14px; font-weight:900; color:{{ $kpis['result'] >= 0 ? '#065f46' : '#7f1d1d' }}; font-size:1.1rem; border-radius:8px 0 0 8px;">(=) Resultado do Período</td>
                        <td style="padding:16px 14px; text-align:right; font-weight:900; color:{{ $kpis['result'] >= 0 ? '#065f46' : '#7f1d1d' }}; font-size:1.25rem; border-radius:0 8px 8px 0;">{{ $fmtBr($kpis['result']) }}</td>
                    </tr>
                </tbody>
            </table>
            <div style="margin-top:16px; font-size:.78rem; color:#64748b; background:#f8fafc; padding:10px 14px; border-radius:8px;">
                <i class="fas fa-circle-info me-1" style="color:#4f46e5;"></i>
                Considera apenas lançamentos com status <strong>pago</strong> no período. Pendentes ficam listadas abaixo.
            </div>
        </div>

        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 20px;">
            <h5 style="margin: 0 0 18px 0; font-weight: 900; color: #1e293b; font-size: 1.05rem; text-transform: uppercase; letter-spacing: 1px;">Evolução no período</h5>
            <canvas id="dreTrendChart" height="140"></canvas>
        </div>
    </div>

    <div class="col-md-5">
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 20px;">
            <h5 style="margin: 0 0 14px 0; font-weight: 900; color: #1e293b; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Top Clientes por Receita</h5>
            @forelse($top_clients as $c)
            <a href="{{ route('clients.show', $c->id) }}" style="display:flex; align-items:center; padding:12px 0; border-bottom:1px solid #f8fafc; text-decoration:none; color:inherit;">
                <div style="width:38px; height:38px; background:#e0e7ff; color:#4338ca; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:900; margin-right:12px;">
                    {{ strtoupper(substr($c->name, 0, 1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800; color:#1e293b; font-size:.9rem;">{{ $c->name }}</div>
                    <div style="font-size:.7rem; color:#94a3b8; font-weight:600;">{{ $c->tx_count }} {{ $c->tx_count == 1 ? 'transação' : 'transações' }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:900; color:#059669; font-size:.95rem;">{{ $fmtBr($c->total_paid) }}</div>
                </div>
            </a>
            @empty
            <div style="text-align:center; padding:24px; color:#94a3b8; font-weight:600; font-size:.85rem;">
                <i class="fas fa-user-slash" style="color:#cbd5e1; font-size:1.5rem; display:block; margin-bottom:8px;"></i>
                Nenhuma receita vinculada a cliente no período.
            </div>
            @endforelse
        </div>

        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 20px;">
            <h5 style="margin: 0 0 14px 0; font-weight: 900; color: #1e293b; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Despesas por Categoria</h5>
            @forelse($expenses_by_category as $cat)
            @php $pct = $kpis['expense'] > 0 ? ($cat->total / $kpis['expense']) * 100 : 0; @endphp
            <div style="padding:10px 0; border-bottom:1px solid #f8fafc;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-weight:700; color:#1e293b; font-size:.88rem;">
                        <span style="display:inline-block; width:10px; height:10px; background:{{ $cat->color }}; border-radius:50%; margin-right:6px;"></span>
                        {{ $cat->category }}
                    </span>
                    <span style="font-weight:900; color:#991b1b; font-size:.88rem;">{{ $fmtBr($cat->total) }}</span>
                </div>
                <div style="background:#f1f5f9; border-radius:99px; height:6px; overflow:hidden;">
                    <div style="height:6px; width:{{ $pct }}%; background:{{ $cat->color }}; border-radius:99px;"></div>
                </div>
                <div style="font-size:.68rem; color:#94a3b8; font-weight:600; margin-top:2px;">{{ number_format($pct, 1, ',', '.') }}% · {{ $cat->tx_count }} lanç.</div>
            </div>
            @empty
            <div style="text-align:center; padding:24px; color:#94a3b8; font-weight:600; font-size:.85rem;">
                <i class="fas fa-inbox" style="color:#cbd5e1; font-size:1.5rem; display:block; margin-bottom:8px;"></i>
                Sem despesas no período.
            </div>
            @endforelse
        </div>

        <div class="vivensi-card p-4" style="background:linear-gradient(135deg,#fef3c7,#ffffff); border:1px solid #fde68a; border-radius:20px;">
            <h5 style="margin: 0 0 14px 0; font-weight: 900; color: #92400e; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Snapshot de Pendências</h5>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight:800; color:#065f46; font-size:.85rem;"><i class="fas fa-arrow-down" style="font-size:.7rem;"></i> A receber</div>
                        <div style="font-size:.7rem; color:#64748b; font-weight:600;">{{ $pending['receivables_count'] }} lançamentos</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:900; color:#059669;">{{ $fmtBr($pending['receivables_total']) }}</div>
                        @if($pending['receivables_overdue_total'] > 0)
                            <div style="font-size:.7rem; color:#dc2626; font-weight:800;">{{ $fmtBr($pending['receivables_overdue_total']) }} atrasado</div>
                        @endif
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight:800; color:#991b1b; font-size:.85rem;"><i class="fas fa-arrow-up" style="font-size:.7rem;"></i> A pagar</div>
                        <div style="font-size:.7rem; color:#64748b; font-weight:600;">{{ $pending['payables_count'] }} lançamentos</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:900; color:#dc2626;">{{ $fmtBr($pending['payables_total']) }}</div>
                        @if($pending['payables_overdue_total'] > 0)
                            <div style="font-size:.7rem; color:#dc2626; font-weight:800;">{{ $fmtBr($pending['payables_overdue_total']) }} vencido</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function toggleCustom(v) {
        const show = v === 'custom' ? 'block' : 'none';
        document.getElementById('custom-from').style.display = show;
        document.getElementById('custom-to').style.display   = show;
    }

    (function () {
        const ctx = document.getElementById('dreTrendChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($monthly_trend['labels']),
                datasets: [
                    { label: 'Receita',   backgroundColor: '#10b981', borderRadius: 6, data: @json($monthly_trend['income']) },
                    { label: 'Despesa',   backgroundColor: '#ef4444', borderRadius: 6, data: @json($monthly_trend['expense']) },
                    { label: 'Resultado', type: 'line', borderColor: '#4f46e5', backgroundColor: '#4f46e5', tension: 0.3, data: @json($monthly_trend['result']) },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11, weight: '700' }, color: '#475569' } },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'R$ ' + Number(v).toLocaleString('pt-BR'),
                            font: { size: 10 }, color: '#94a3b8'
                        },
                        grid: { color: '#f1f5f9' },
                    },
                    x: { ticks: { font: { size: 10, weight: '700' }, color: '#64748b' }, grid: { display: false } },
                },
            },
        });
    })();
</script>
@endsection
