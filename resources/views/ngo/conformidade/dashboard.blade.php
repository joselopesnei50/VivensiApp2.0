@extends('layouts.app')

@section('content')

<style>
.conf-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); border-radius: 24px; padding: 32px 36px; margin-bottom: 28px; color: #fff; }
.conf-semaforo-card { border-radius: 20px; padding: 24px; border: 1px solid; transition: box-shadow .2s; }
.conf-semaforo-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.12); }
.conf-badge-verde    { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
.conf-badge-amarelo  { background: #fef9c3; color: #713f12; border-color: #fde047; }
.conf-badge-vermelho { background: #fee2e2; color: #7f1d1d; border-color: #fca5a5; }
.conf-badge-neutro   { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.conf-indice-circle { width: 110px; height: 110px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 700; border: 6px solid; }
.conf-circle-verde    { border-color: #10b981; color: #065f46; background: #ecfdf5; }
.conf-circle-amarelo  { border-color: #f59e0b; color: #713f12; background: #fffbeb; }
.conf-circle-vermelho { border-color: #ef4444; color: #7f1d1d; background: #fef2f2; }
.conf-pendencia-item { border-left: 4px solid; padding: 12px 16px; border-radius: 0 12px 12px 0; margin-bottom: 10px; background: #fff; }
.conf-pendencia-vermelho { border-color: #ef4444; }
.conf-pendencia-amarelo  { border-color: #f59e0b; }
.conf-risco-badge { font-size: .68rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
.conf-risco-alto   { background: #fee2e2; color: #b91c1c; }
.conf-risco-medio  { background: #fef3c7; color: #92400e; }
.conf-risco-baixo  { background: #f0fdf4; color: #166534; }
@media(max-width:768px){ .conf-header { padding: 22px 18px; } }
</style>

{{-- Header --}}
<div class="conf-header d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="font-size:1.9rem;letter-spacing:-1px">Conformidade Contínua</h2>
        <p class="mb-0 opacity-75" style="font-size:.9rem">CEBAS · MROSC · SUAS — índice calculado automaticamente</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        {{-- RMA: precisa de mês/ano --}}
        <button class="btn btn-outline-success btn-sm fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalRma">
            <i class="bi bi-file-earmark-pdf me-1"></i> RMA
        </button>
        <a href="{{ route('ngo.conformidade.pdf.cebas') }}" class="btn btn-outline-primary btn-sm fw-semibold px-3" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i> Dossiê CEBAS
        </a>
        <a href="{{ route('ngo.conformidade.pdf.mrosc') }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i> MROSC
        </a>
        <button class="btn btn-outline-warning btn-sm fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalExportCsv">
            <i class="bi bi-download me-1"></i> Exportar CSV
        </button>
        <form action="{{ route('ngo.conformidade.snapshot') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-outline-info btn-sm fw-semibold px-3">
                <i class="bi bi-camera me-1"></i> Snapshot
            </button>
        </form>
        <form action="{{ route('ngo.conformidade.recalcular') }}" method="POST">
            @csrf
            <button class="btn btn-light btn-sm fw-semibold px-3">
                <i class="bi bi-arrow-clockwise me-1"></i> Recalcular
            </button>
        </form>
    </div>
</div>

{{-- Modal Export CSV --}}
<div class="modal fade" id="modalExportCsv" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-download me-2"></i>Exportar CSV</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ngo.conformidade.export') }}" method="GET">
                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Eixo (opcional)</label>
                        <select name="eixo" class="form-select form-select-sm">
                            <option value="">Todos os eixos</option>
                            <option value="cebas_geral">CEBAS Geral</option>
                            <option value="cebas_as">CEBAS Assist. Social</option>
                            <option value="cebas_saude">CEBAS Saúde</option>
                            <option value="cebas_educacao">CEBAS Educação</option>
                            <option value="mrosc">MROSC</option>
                            <option value="suas">SUAS</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Data início</label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Data fim</label>
                        <input type="date" name="data_fim" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold px-4">
                        <i class="bi bi-download me-1"></i>Baixar CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal RMA --}}
<div class="modal fade" id="modalRma" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Gerar RMA</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ngo.conformidade.pdf.rma') }}" method="GET" target="_blank">
                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mês <span class="text-danger">*</span></label>
                        <select name="mes" class="form-select form-select-sm" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ now()->setMonth($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Ano <span class="text-danger">*</span></label>
                        <select name="ano" class="form-select form-select-sm" required>
                            @for($a = now()->year; $a >= now()->year - 3; $a--)
                                <option value="{{ $a }}" {{ $a == now()->year ? 'selected' : '' }}>{{ $a }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-4">
                        <i class="bi bi-download me-1"></i>Gerar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Banner Guia --}}
<div class="d-flex align-items-center gap-3 mb-4 p-3 alert-dismissible fade show"
     style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border-radius:16px;border:1px solid #bfdbfe;border-left:4px solid #3b82f6"
     role="alert">
    <div style="font-size:2rem;line-height:1;flex-shrink:0">📋</div>
    <div class="flex-grow-1">
        <div class="fw-bold" style="color:#1e40af;font-size:.9rem">Como preencher o módulo de Conformidade?</div>
        <div style="color:#2563eb;font-size:.8rem">Veja o guia passo a passo para configurar perfil, ciclos, documentos, declarações e planos de ação.</div>
    </div>
    <a href="{{ route('ngo.conformidade.guia') }}" class="btn btn-sm fw-semibold px-3 flex-shrink-0"
       style="background:#3b82f6;color:#fff;border-radius:10px">
        <i class="bi bi-map me-1"></i> Ver Guia
    </a>
    <button type="button" class="btn-close" data-bs-dismiss="alert" style="flex-shrink:0"></button>
</div>

{{-- Índice Geral + Semáforos por eixo-família --}}
<div class="row g-4 mb-4">

    {{-- Índice Geral --}}
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm" style="border-radius:20px">
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-4 text-center">
                <p class="text-muted small fw-semibold mb-3 text-uppercase" style="letter-spacing:.8px">Índice Geral</p>
                @php
                    $ig = (float)$dashboard['indice_geral'];
                    $circleClass = $ig >= 70 ? 'conf-circle-verde' : ($ig >= 50 ? 'conf-circle-amarelo' : 'conf-circle-vermelho');
                @endphp
                <div class="conf-indice-circle {{ $circleClass }}">{{ $ig }}%</div>
                <p class="mt-3 mb-0 text-muted" style="font-size:.82rem">Calculado em {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- Semáforos --}}
    @php
        $eixoLabels = [
            'cebas_geral'     => ['label'=>'CEBAS Geral',     'icon'=>'bi-shield-check'],
            'cebas_as'        => ['label'=>'CEBAS Assist. Social','icon'=>'bi-people'],
            'cebas_saude'     => ['label'=>'CEBAS Saúde',     'icon'=>'bi-heart-pulse'],
            'cebas_educacao'  => ['label'=>'CEBAS Educação',  'icon'=>'bi-book'],
            'mrosc'           => ['label'=>'MROSC',           'icon'=>'bi-file-earmark-text'],
            'suas'            => ['label'=>'SUAS',            'icon'=>'bi-house-heart'],
        ];
    @endphp

    <div class="col-md-9">
        <div class="row g-3">
            @foreach($eixoLabels as $eixo => $meta)
                @php
                    $idx = $dashboard['indices_por_eixo'][$eixo] ?? null;
                    $pct = $idx ? $idx['percentual'] : 0;
                    $sem = $pct >= 70 ? 'verde' : ($pct >= 50 ? 'amarelo' : 'vermelho');
                    $semIcon = $pct >= 70 ? '🟢' : ($pct >= 50 ? '🟡' : '🔴');
                    $cardClass = "conf-badge-{$sem}";
                    $ciclo = $dashboard['ciclos'][$eixo] ?? null;
                @endphp
                <div class="col-md-4 col-6">
                    <a href="{{ route('ngo.conformidade.eixo', $eixo) }}" class="text-decoration-none">
                        <div class="conf-semaforo-card {{ $cardClass }} h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span style="font-size:1.1rem">{{ $semIcon }}</span>
                                <span class="fw-semibold" style="font-size:.82rem">{{ $meta['label'] }}</span>
                            </div>
                            <div class="fw-bold" style="font-size:1.6rem">{{ $pct }}%</div>
                            @if($idx)
                                <div style="font-size:.72rem;opacity:.8">
                                    {{ $idx['verde'] }}✓ {{ $idx['amarelo'] }}⚠ {{ $idx['vermelho'] }}✗
                                </div>
                            @endif
                            @if($ciclo)
                                <div class="mt-2" style="font-size:.7rem;opacity:.7">
                                    Ciclo: até {{ $ciclo->data_fim->format('d/m/Y') }}
                                </div>
                            @endif
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Pendências --}}
    <div class="col-md-7">
        <div class="card border-0 shadow-sm" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Pendências Acionáveis
                    @if(count($dashboard['pendencias']) > 0)
                        <span class="badge bg-danger ms-2">{{ count($dashboard['pendencias']) }}</span>
                    @endif
                </h6>

                @forelse($dashboard['pendencias'] as $p)
                    @php
                        $pClass = $p['resultado'] === 'vermelho' ? 'conf-pendencia-vermelho' : 'conf-pendencia-amarelo';
                        $riscoClass = match($p['risco']) { 'alto' => 'conf-risco-alto', 'medio' => 'conf-risco-medio', default => 'conf-risco-baixo' };
                    @endphp
                    <div class="conf-pendencia-item {{ $pClass }}">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="conf-risco-badge {{ $riscoClass }}">{{ strtoupper($p['risco']) }}</span>
                                    <span style="font-size:.7rem;color:#64748b">{{ $p['codigo'] }}</span>
                                </div>
                                <div class="fw-semibold" style="font-size:.85rem">{{ $p['titulo'] }}</div>
                                @if(!empty($p['detalhe']))
                                    <div class="text-muted" style="font-size:.76rem">{{ $p['detalhe'] }}</div>
                                @elseif(isset($p['valor_calculado']) && $p['valor_calculado'] !== null)
                                    <div class="text-muted" style="font-size:.76rem">
                                        {{ $p['valor_calculado'] }}{{ $p['unidade'] ?? '' }}
                                        @if($p['threshold']) · mínimo {{ $p['threshold'] }}{{ $p['unidade'] ?? '' }} @endif
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('ngo.conformidade.eixo', $p['eixo']) }}"
                               class="btn btn-sm btn-outline-secondary flex-shrink-0" style="font-size:.72rem">
                                Ver
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:2rem"></i>
                        <p class="mt-2 mb-0 fw-semibold">Nenhuma pendência crítica!</p>
                        <p class="small">Todos os requisitos avaliados estão em dia.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Histórico + Links rápidos --}}
    <div class="col-md-5">

        {{-- Histórico de índice --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-graph-up me-2"></i>Evolução do Índice
                </h6>
                @if($historico->count() > 0)
                    @php
                        // Auditoria 2026-08-29 P3: JSON_HEX_* uniformiza com outras views
                        // (dashboards/{ngo,common,manager}, admin/dashboard, booking) —
                        // blindagem XSS caso label passe a vir de campo user-controlled.
                        $jsonFlags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
                        $chartSnaps  = $historico->take(12)->reverse()->values();
                        $chartLabels = $chartSnaps->map(fn($s) => \Carbon\Carbon::parse($s->snapshotado_em)->format('d/m'))->toJson($jsonFlags);
                        $chartData   = $chartSnaps->map(fn($s) => (float)$s->indice_geral)->toJson($jsonFlags);
                    @endphp
                    <canvas id="chartIndice" height="130"></canvas>
                @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                <script>
                (function(){
                    const labels = {!! $chartLabels !!};
                    const data   = {!! $chartData !!};
                    const colors = data.map(v => v >= 70 ? '#10b981' : (v >= 50 ? '#f59e0b' : '#ef4444'));
                    new Chart(document.getElementById('chartIndice'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Índice Geral (%)',
                                data,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99,102,241,0.08)',
                                pointBackgroundColor: colors,
                                pointRadius: 5,
                                tension: 0.3,
                                fill: true,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { min: 0, max: 100, ticks: { callback: v => v + '%', font: { size: 10 } } },
                                x: { ticks: { font: { size: 10 } } }
                            }
                        }
                    });
                })();
                </script>
                @endpush
                @else
                    <p class="text-muted small text-center py-3">
                        Snapshots aparecerão aqui após o primeiro uso do botão <strong>Snapshot</strong>.
                    </p>
                @endif
            </div>
        </div>

        {{-- Links rápidos por eixo --}}
        <div class="card border-0 shadow-sm" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Detalhar por Eixo
                </h6>
                <div class="d-flex flex-column gap-2">
                    @foreach($eixoLabels as $eixo => $meta)
                        @php
                            $idx = $dashboard['indices_por_eixo'][$eixo] ?? null;
                            $pct = $idx ? $idx['percentual'] : 0;
                            $cor = $pct >= 70 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <a href="{{ route('ngo.conformidade.eixo', $eixo) }}"
                           class="d-flex align-items-center justify-content-between text-decoration-none p-2 rounded-3"
                           style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $meta['icon'] }}" style="color:{{ $cor }}"></i>
                                <span style="font-size:.82rem;font-weight:600;color:#1e293b">{{ $meta['label'] }}</span>
                            </div>
                            <span class="fw-bold" style="font-size:.85rem;color:{{ $cor }}">{{ $pct }}%</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
