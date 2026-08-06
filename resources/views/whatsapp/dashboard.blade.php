@extends('layouts.app')

@section('title', 'Dashboard de Atendimento WhatsApp')

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:20px;">

    {{-- Header + filtro de periodo --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h1 style="margin:0;font-size:1.4rem;font-weight:800;color:#1e293b;">Atendimento WhatsApp</h1>
            <p style="margin:2px 0 0;font-size:.82rem;color:#64748b;">Produtividade dos agentes e distribuição de chats</p>
        </div>
        <div style="display:flex;gap:6px;">
            @foreach(['today' => 'Hoje', '7d' => '7 dias', '30d' => '30 dias'] as $k => $label)
                <a href="{{ route('whatsapp.dashboard', ['period' => $k]) }}"
                   style="padding:8px 14px;font-size:.78rem;font-weight:700;border-radius:10px;text-decoration:none;
                          {{ $period === $k ? 'background:#4f46e5;color:#fff;' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPIs (4 cards) --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:22px;">
        <div class="wa-dash-card">
            <div class="wa-dash-label"><i class="fas fa-headset" style="color:#4f46e5;"></i> Atendimentos</div>
            <div class="wa-dash-value">{{ number_format($kpis['completed'], 0, ',', '.') }}</div>
            <div class="wa-dash-hint">{{ $kpis['total'] }} iniciados no período</div>
        </div>
        <div class="wa-dash-card">
            <div class="wa-dash-label"><i class="fas fa-stopwatch" style="color:#0ea5e9;"></i> Tempo médio</div>
            <div class="wa-dash-value">{{ $kpis['avg_duration'] }}</div>
            <div class="wa-dash-hint">por atendimento concluído</div>
        </div>
        <div class="wa-dash-card">
            <div class="wa-dash-label"><i class="fas fa-comment-dots" style="color:#059669;"></i> Chats abertos</div>
            <div class="wa-dash-value">{{ $kpis['open_now'] }}</div>
            <div class="wa-dash-hint">agora</div>
        </div>
        <div class="wa-dash-card">
            <div class="wa-dash-label"><i class="fas fa-robot" style="color:#f59e0b;"></i> Auto-assign</div>
            <div class="wa-dash-value">{{ $kpis['auto_pct'] }}%</div>
            <div class="wa-dash-hint">{{ $kpis['auto_count'] }} auto · {{ $kpis['manual_count'] }} manual</div>
        </div>
    </div>

    {{-- Grafico volume diario --}}
    @if(count($series) > 1)
    <div class="wa-dash-card" style="margin-bottom:22px;">
        <div class="wa-dash-label" style="margin-bottom:10px;"><i class="fas fa-chart-bar" style="color:#8b5cf6;"></i> Volume diário</div>
        <canvas id="waDailyChart" height="80"></canvas>
    </div>
    @endif

    {{-- Ranking agentes --}}
    <div class="wa-dash-card">
        <div class="wa-dash-label" style="margin-bottom:12px;"><i class="fas fa-trophy" style="color:#f59e0b;"></i> Ranking de agentes</div>
        @if(empty($ranking))
            <div style="padding:20px;color:#94a3b8;text-align:center;font-size:.85rem;">
                Nenhum atendimento registrado no período.
            </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">
                        <th style="text-align:left;padding:10px 8px;font-weight:700;">Agente</th>
                        <th style="text-align:right;padding:10px 8px;font-weight:700;">Atendimentos</th>
                        <th style="text-align:right;padding:10px 8px;font-weight:700;">Tempo médio</th>
                        <th style="text-align:right;padding:10px 8px;font-weight:700;">Transf. recebidas</th>
                        <th style="text-align:right;padding:10px 8px;font-weight:700;">Transf./release feitos</th>
                        <th style="text-align:center;padding:10px 8px;font-weight:700;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranking as $row)
                    @php
                        $availColor = ['available' => '#22c55e', 'away' => '#f59e0b', 'offline' => '#94a3b8'][$row['availability']] ?? '#22c55e';
                        $availLabel = ['available' => 'Disponível', 'away' => 'Ausente', 'offline' => 'Offline'][$row['availability']] ?? 'Disponível';
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 8px;font-weight:600;color:#1e293b;">{{ $row['name'] }}</td>
                        <td style="padding:12px 8px;text-align:right;font-weight:700;color:#4f46e5;">{{ $row['total'] }}</td>
                        <td style="padding:12px 8px;text-align:right;color:#475569;">{{ $row['avg_duration'] }}</td>
                        <td style="padding:12px 8px;text-align:right;color:#475569;">{{ $row['transfers_in'] }}</td>
                        <td style="padding:12px 8px;text-align:right;color:#475569;">{{ $row['transfers_out'] }}</td>
                        <td style="padding:12px 8px;text-align:center;">
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:.72rem;color:#475569;">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $availColor }};"></span>
                                {{ $availLabel }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<style>
    .wa-dash-card {
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:16px 18px;
    }
    .wa-dash-label {
        font-size:.68rem;font-weight:800;color:#64748b;
        text-transform:uppercase;letter-spacing:.06em;
        display:flex;align-items:center;gap:6px;
    }
    .wa-dash-value {
        font-size:1.7rem;font-weight:900;color:#0f172a;
        margin:6px 0 2px;letter-spacing:-.5px;
    }
    .wa-dash-hint {
        font-size:.72rem;color:#94a3b8;
    }
</style>

@if(count($series) > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const data = @json($series);
        const ctx = document.getElementById('waDailyChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    label: 'Atendimentos iniciados',
                    data: data.map(d => d.total),
                    backgroundColor: '#4f46e5',
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } },
                },
            },
        });
    })();
</script>
@endif
@endsection
