@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Super Admin · Métricas</p>
            <h1 class="h3 mb-1">📊 Bruno em números</h1>
            <p class="text-muted mb-0">Atividade do Bot Vendedor no tenant configurado. Cache de 5 min.</p>
        </div>
        <div class="btn-group" role="group">
            @foreach([1 => 'Hoje', 7 => '7 dias', 30 => '30 dias', 90 => '90 dias'] as $d => $label)
                <a href="?days={{ $d }}" class="btn btn-sm {{ $days === $d ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if(!($metrics['enabled'] ?? false))
        <div class="alert alert-warning d-flex align-items-start">
            <i class="fas fa-exclamation-triangle me-3 mt-1 fs-5"></i>
            <div>
                <strong>Bruno está desligado.</strong>
                Vá em <a href="{{ url('/admin/settings') }}">Configurações Globais</a> → seção "Bot Vendedor — Bruno" e selecione o tenant ativo.
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Conversas iniciadas</p>
                        <h2 class="fw-bold mb-0">{{ $metrics['chats'] }}</h2>
                        <small class="text-muted">últimos {{ $days }} dia(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Leads cadastrados</p>
                        <h2 class="fw-bold mb-0">{{ $metrics['leads_total'] }}</h2>
                        <small class="text-muted">via Bruno</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Demos agendadas</p>
                        <h2 class="fw-bold mb-0">{{ $metrics['bookings_total'] }}</h2>
                        <small class="text-muted">inline pelo chat</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Cards no Kanban</p>
                        <h2 class="fw-bold mb-0">{{ $metrics['kanban_cards'] }}</h2>
                        <small class="text-muted">leads quentes promovidos</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Qualificação dos leads</h5>
                @php
                    $byQ = $metrics['by_qualification'];
                    $total = array_sum($byQ) ?: 1;
                    $palette = [
                        'frio'   => ['lbl' => '🥶 Frio',   'bg' => '#6c757d'],
                        'morno'  => ['lbl' => '☕ Morno',  'bg' => '#f59e0b'],
                        'quente' => ['lbl' => '🔥 Quente', 'bg' => '#dc2626'],
                        'sem'    => ['lbl' => '— Sem',    'bg' => '#cbd5e1'],
                    ];
                @endphp
                <div class="d-flex w-100 rounded-pill overflow-hidden mb-3" style="height: 28px;">
                    @foreach($byQ as $key => $count)
                        @if($count > 0)
                            @php $pct = round(($count / $total) * 100, 1); @endphp
                            <div style="background: {{ $palette[$key]['bg'] }}; width: {{ $pct }}%;" title="{{ $palette[$key]['lbl'] }}: {{ $count }} ({{ $pct }}%)"></div>
                        @endif
                    @endforeach
                </div>
                <div class="row g-2">
                    @foreach(['frio', 'morno', 'quente', 'sem'] as $key)
                        <div class="col">
                            <div class="d-flex align-items-center">
                                <span class="me-2" style="display:inline-block;width:14px;height:14px;border-radius:50%;background: {{ $palette[$key]['bg'] }};"></span>
                                <span class="small">{{ $palette[$key]['lbl'] }}: <strong>{{ $byQ[$key] }}</strong></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Últimos agendamentos via Bruno</h5>
                    <a href="{{ url('/admin/bookings') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
                @if($metrics['recent_bookings']->isEmpty())
                    <p class="text-muted mb-0 small">Nenhum agendamento via Bruno ainda na janela selecionada.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase">
                                    <th>Lead</th>
                                    <th>Contato</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($metrics['recent_bookings'] as $b)
                                    <tr>
                                        <td><strong>{{ $b->name }}</strong></td>
                                        <td><small class="text-muted">{{ $b->email }}<br>{{ $b->phone }}</small></td>
                                        <td>{{ \Carbon\Carbon::parse($b->meeting_date)->format('d/m/Y') }}</td>
                                        <td>{{ \Illuminate\Support\Str::substr($b->meeting_time, 0, 5) }}</td>
                                        <td>
                                            <span class="badge {{ $b->status === 'confirmed' ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $b->status }}
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

        <p class="text-muted small mt-3 text-end">
            Gerado às {{ $metrics['generated_at'] ?? '—' }} · Cache: 5 min · Tenant #{{ $metrics['tenant_id'] }}
        </p>
    @endif

</div>
@endsection
