@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1" style="font-family:'Outfit',sans-serif;">Analytics Executivo</h1>
            <p class="text-muted mb-0">Acessos ao sistema e volume de mensagens WhatsApp — últimos 30 dias.</p>
        </div>
        <span class="text-muted small"><i class="fas fa-database me-1"></i> {{ number_format($totalRecords) }} registros de acesso</span>
    </div>

    {{-- ── Seção: Acessos ao Sistema ─────────────────────────────────────── --}}
    <h6 class="text-uppercase text-muted fw-bold small letter-spacing-1 mb-3">
        <i class="fas fa-door-open me-2"></i>Acessos ao Sistema
    </h6>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Visitas hoje</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsToday) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Visitas semana</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsWeek) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Visitas mês</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsMonth) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Usuários únicos hoje</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($usersToday) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Usuários únicos semana</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($usersWeek) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Orgs ativas / total</div>
                <div class="fs-3 fw-bold text-info">{{ $activeTenantsCount }}<span class="fs-6 text-muted fw-normal"> / {{ $totalTenants }}</span></div>
            </div>
        </div>
    </div>

    {{-- Gráfico de acessos --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h6 class="fw-bold mb-0">Acessos por dia — últimos 30 dias</h6>
        </div>
        <div class="card-body p-4">
            <canvas id="visitsChart" height="80"></canvas>
        </div>
    </div>

    {{-- ── Seção: WhatsApp ─────────────────────────────────────────────────── --}}
    <h6 class="text-uppercase text-muted fw-bold small letter-spacing-1 mb-3">
        <i class="fab fa-whatsapp me-2 text-success"></i>Mensagens WhatsApp
    </h6>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Total hoje</div>
                <div class="fs-3 fw-bold" style="color:#25d366;">{{ number_format($waMsgToday) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Total semana</div>
                <div class="fs-3 fw-bold" style="color:#25d366;">{{ number_format($waMsgWeek) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Total mês</div>
                <div class="fs-3 fw-bold" style="color:#25d366;">{{ number_format($waMsgMonth) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Recebidas hoje</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($waInboundToday) }}</div>
                <div class="text-muted" style="font-size:.65rem;">inbound</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Enviadas hoje</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($waOutboundToday) }}</div>
                <div class="text-muted" style="font-size:.65rem;">outbound</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3 h-100">
                <div class="text-muted small mb-1">Taxa resposta hoje</div>
                @php $taxa = $waInboundToday > 0 ? round(($waOutboundToday / $waInboundToday) * 100) : 0; @endphp
                <div class="fs-3 fw-bold text-info">{{ $taxa }}%</div>
            </div>
        </div>
    </div>

    {{-- Gráfico WhatsApp --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex align-items-center gap-3">
            <h6 class="fw-bold mb-0">Mensagens por dia — últimos 30 dias</h6>
            <div class="d-flex gap-3 ms-3 small text-muted">
                <span><span class="badge rounded-pill" style="background:#4f46e5;">&nbsp;</span> Recebidas</span>
                <span><span class="badge rounded-pill bg-warning">&nbsp;</span> Enviadas</span>
            </div>
        </div>
        <div class="card-body p-4">
            <canvas id="waChart" height="80"></canvas>
        </div>
    </div>

    {{-- ── Tabelas ──────────────────────────────────────────────────────────── --}}
    <div class="row g-4">

        {{-- Top páginas --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold mb-0">Páginas mais acessadas — 30 dias</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Caminho</th>
                                    <th class="text-end pe-4">Visitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topPages as $i => $page)
                                <tr>
                                    <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                                    <td><code class="text-dark bg-light px-2 py-1 rounded">/{{ $page->path }}</code></td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($page->total) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Nenhum dado ainda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tenants mais ativos --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold mb-0">Organizações mais ativas — 30 dias</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Organização</th>
                                    <th class="text-end pe-4">Acessos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topTenants as $row)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $row->name }}</td>
                                    <td class="text-end pe-4 fw-bold">{{ number_format($row->total) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">Nenhum dado ainda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <p class="text-muted small mt-4">
        <i class="fas fa-info-circle me-1"></i>
        Acessos retidos por 90 dias. Execute <code>PageVisit::prune(90)</code> periodicamente para limpar registros antigos.
    </p>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de acessos
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: @json($dailyLabels),
        datasets: [{
            label: 'Acessos',
            data: @json($dailyData),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79,70,229,0.08)',
            borderWidth: 2,
            pointRadius: 3,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
        }
    }
});

// Gráfico WhatsApp
new Chart(document.getElementById('waChart'), {
    type: 'bar',
    data: {
        labels: @json($dailyLabels),
        datasets: [
            {
                label: 'Recebidas',
                data: @json($waInboundData),
                backgroundColor: 'rgba(79,70,229,0.75)',
                borderRadius: 4,
            },
            {
                label: 'Enviadas',
                data: @json($waOutboundData),
                backgroundColor: 'rgba(234,179,8,0.75)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, stacked: false },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
