@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1" style="font-family:'Outfit',sans-serif;">Analytics</h1>
            <p class="text-muted mb-0">Acessos e atividade dos usuários na plataforma.</p>
        </div>
        <span class="text-muted small"><i class="fas fa-database me-1"></i> {{ number_format($totalRecords) }} registros no banco</span>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Visitas hoje</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsToday) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Visitas semana</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsWeek) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Visitas mês</div>
                <div class="fs-3 fw-bold text-primary">{{ number_format($visitsMonth) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Usuários hoje</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($usersToday) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Usuários semana</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($usersWeek) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small mb-1">Usuários mês</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($usersMonth) }}</div>
            </div>
        </div>
    </div>

    {{-- Gráfico de visitas --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
            <h6 class="fw-bold mb-0">Visitas por dia — últimos 30 dias</h6>
        </div>
        <div class="card-body p-4">
            <canvas id="visitsChart" height="90"></canvas>
        </div>
    </div>

    <div class="row g-4">

        {{-- Top páginas --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h6 class="fw-bold mb-0">Páginas mais acessadas — últimos 30 dias</h6>
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
                                    <td>
                                        <code class="text-dark bg-light px-2 py-1 rounded">/{{ $page->path }}</code>
                                    </td>
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
                    <h6 class="fw-bold mb-0">Organizações mais ativas — últimos 30 dias</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Organização</th>
                                    <th class="text-end pe-4">Visitas</th>
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
        Dados retidos por 90 dias. Execute <code>PageVisit::prune(90)</code> periodicamente para limpar registros antigos.
    </p>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: @json($dailyLabels),
        datasets: [{
            label: 'Visitas',
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
</script>
@endpush
