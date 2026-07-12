@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1200px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · WhatsApp Cloud</p>
            <h1 class="h3 mb-1">💰 Consumo WhatsApp Cloud API</h1>
            <p class="text-muted mb-0">Faturamento Meta por conversa 24h. Preços em USD micros.</p>
        </div>
        <div class="btn-group" role="group">
            @foreach([7 => '7 dias', 30 => '30 dias', 90 => '90 dias'] as $d => $label)
                <a href="?days={{ $d }}{{ $tenantId ? '&tenant_id=' . $tenantId : '' }}{{ $category ? '&category=' . $category : '' }}"
                   class="btn btn-sm {{ $days === $d ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Tenant</label>
                    <select name="tenant_id" class="form-select">
                        <option value="">Todos os tenants</option>
                        @foreach($tenants as $id => $name)
                            <option value="{{ $id }}" {{ (int) $tenantId === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Categoria</label>
                    <select name="category" class="form-select">
                        <option value="">Todas as categorias</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="days" value="{{ $days }}">
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Aplicar filtros</button>
                    @if($tenantId || $category)
                        <a href="?days={{ $days }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- KPIs gerais --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Conversas faturáveis</p>
                    <h2 class="fw-bold mb-0">{{ number_format($summary['total_conversations'], 0, ',', '.') }}</h2>
                    <small class="text-muted">últimos {{ $days }} dias</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Custo total (USD)</p>
                    <h2 class="fw-bold mb-0">${{ number_format($summary['total_cost_usd'], 4, '.', ',') }}</h2>
                    <small class="text-muted">preços Meta oficiais</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Custo estimado (BRL)</p>
                    <h2 class="fw-bold mb-0">R$ {{ number_format($summary['total_cost_brl'], 2, ',', '.') }}</h2>
                    <small class="text-muted">USD × {{ number_format(config('whatsapp_pricing.usd_brl_rate'), 2, ',', '.') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown por categoria --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">📊 Por categoria</h5>
                    @if($byCategory->isEmpty())
                        <p class="text-muted mb-0 small">Sem conversas no período selecionado.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small text-uppercase">
                                        <th>Categoria</th>
                                        <th class="text-end">Conversas</th>
                                        <th class="text-end">Custo USD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byCategory as $cat)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary text-uppercase">{{ $cat->category }}</span>
                                            </td>
                                            <td class="text-end">{{ number_format($cat->conversations, 0, ',', '.') }}</td>
                                            <td class="text-end">${{ number_format($cat->cost_usd, 4, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top 10 tenants --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">🏆 Top 10 tenants por consumo</h5>
                    @if($topTenants->isEmpty())
                        <p class="text-muted mb-0 small">Nenhum tenant com consumo no período.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small text-uppercase">
                                        <th>Tenant</th>
                                        <th class="text-end">Conversas</th>
                                        <th class="text-end">Custo USD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topTenants as $t)
                                        <tr>
                                            <td>
                                                <a href="?days={{ $days }}&tenant_id={{ $t->tenant_id }}" class="text-decoration-none">
                                                    {{ $t->tenant_name }}
                                                </a>
                                            </td>
                                            <td class="text-end">{{ number_format($t->conversations, 0, ',', '.') }}</td>
                                            <td class="text-end">${{ number_format($t->cost_usd, 4, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">📈 Evolução diária</h5>
            @if($timeline->isEmpty())
                <p class="text-muted mb-0 small">Sem dados para o período.</p>
            @else
                <canvas id="timelineChart" height="80"></canvas>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        if (typeof Chart === 'undefined') return;

                        const ctx = document.getElementById('timelineChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: @json($timeline->pluck('day')),
                                datasets: [
                                    {
                                        label: 'Conversas',
                                        data: @json($timeline->pluck('conversations')),
                                        borderColor: '#4f46e5',
                                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                        tension: 0.3,
                                        yAxisID: 'y'
                                    },
                                    {
                                        label: 'Custo (USD micros)',
                                        data: @json($timeline->pluck('cost_micros')),
                                        borderColor: '#FF7A1A',
                                        backgroundColor: 'rgba(255, 122, 26, 0.1)',
                                        tension: 0.3,
                                        yAxisID: 'y1'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                interaction: { mode: 'index', intersect: false },
                                scales: {
                                    y:  { type: 'linear', position: 'left',  title: { display: true, text: 'Conversas' } },
                                    y1: { type: 'linear', position: 'right', title: { display: true, text: 'Custo (micros)' }, grid: { drawOnChartArea: false } }
                                }
                            }
                        });
                    });
                </script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
            @endif
        </div>
    </div>

    <div class="alert alert-info small">
        <strong>Nota:</strong> Fase 5.1 apenas rastreia consumo. Fatura mensal, markup e repasse ao tenant serão implementados na próxima fase.
        Preços fallback em <code>config/whatsapp_pricing.php</code>; Meta pode sobrescrever via <code>pricing</code> no webhook.
    </div>

</div>
@endsection
