@extends('layouts.app')

@section('content')

@php
$eixoLabels = [
    'cebas_geral'    => 'CEBAS Geral',
    'cebas_as'       => 'CEBAS Assistência Social',
    'cebas_saude'    => 'CEBAS Saúde',
    'cebas_educacao' => 'CEBAS Educação',
    'mrosc'          => 'MROSC',
    'suas'           => 'SUAS',
];
$label = $eixoLabels[$eixo] ?? $eixo;
@endphp

<style>
.req-card { border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px 20px; margin-bottom: 12px; background:#fff; }
.req-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.res-badge { font-size:.72rem; font-weight:700; padding: 3px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.5px; }
.res-verde    { background:#d1fae5; color:#065f46; }
.res-amarelo  { background:#fef9c3; color:#713f12; }
.res-vermelho { background:#fee2e2; color:#7f1d1d; }
.res-neutro   { background:#f1f5f9; color:#64748b; }
.tipo-badge   { font-size:.65rem; font-weight:700; padding:2px 7px; border-radius:6px; background:#e0e7ff; color:#3730a3; }
</style>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div>
        <h3 class="fw-bold mb-0">{{ $label }}</h3>
        @if($ciclo)
            <small class="text-muted">Ciclo: {{ $ciclo->data_inicio->format('d/m/Y') }} a {{ $ciclo->data_fim->format('d/m/Y') }}
                · {{ $ciclo->dias_restantes }} dias restantes</small>
        @endif
    </div>
    @if($indices)
        <div class="ms-auto d-flex gap-3 text-center">
            <div><div class="fw-bold fs-5 text-success">{{ $indices['verde'] }}</div><div class="small text-muted">Verde</div></div>
            <div><div class="fw-bold fs-5 text-warning">{{ $indices['amarelo'] }}</div><div class="small text-muted">Amarelo</div></div>
            <div><div class="fw-bold fs-5 text-danger">{{ $indices['vermelho'] }}</div><div class="small text-muted">Vermelho</div></div>
            <div><div class="fw-bold fs-5" style="color:#10b981">{{ $indices['percentual'] }}%</div><div class="small text-muted">Índice</div></div>
        </div>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@foreach($requisitos as $req)
    @php
        $aval = $avaliacoesPorCodigo[$req->codigo] ?? null;
        $resultado = $aval['resultado'] ?? 'nao_aplicavel';
        $resClass = match($resultado) {
            'verde'         => 'res-verde',
            'amarelo'       => 'res-amarelo',
            'vermelho'      => 'res-vermelho',
            default         => 'res-neutro',
        };
        $resLabel = match($resultado) {
            'verde'         => 'Verde',
            'amarelo'       => 'Atenção',
            'vermelho'      => 'Crítico',
            default         => 'N/A',
        };
        $leftColor = match($resultado) {
            'verde'   => '#10b981',
            'amarelo' => '#f59e0b',
            'vermelho'=> '#ef4444',
            default   => '#cbd5e1',
        };
    @endphp
    <div class="req-card" style="border-left:4px solid {{ $leftColor }}">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="res-badge {{ $resClass }}">{{ $resLabel }}</span>
                    <span class="tipo-badge">Tipo {{ $req->tipo }}</span>
                    <span class="text-muted" style="font-size:.72rem">{{ $req->codigo }}</span>
                    <span class="text-muted" style="font-size:.7rem">·</span>
                    <span class="text-muted" style="font-size:.7rem">Risco {{ ucfirst($req->risco) }}</span>
                </div>
                <div class="fw-semibold mb-1">{{ $req->titulo }}</div>
                <div class="text-muted" style="font-size:.8rem">{{ $req->enunciado }}</div>
                @if($req->base_legal)
                    <div class="text-muted mt-1" style="font-size:.72rem"><i class="bi bi-journal-text me-1"></i>{{ $req->base_legal }}</div>
                @endif

                @if($aval && isset($aval['detalhe']))
                    <div class="mt-2 p-2 rounded-2" style="background:{{ $leftColor }}15;font-size:.78rem;color:#374151">
                        <i class="bi bi-info-circle me-1"></i>{{ $aval['detalhe'] }}
                    </div>
                @endif

                @if($aval && isset($aval['valor_calculado']) && $aval['valor_calculado'] !== null)
                    <div class="mt-2" style="font-size:.78rem">
                        <strong>Calculado:</strong> {{ $aval['valor_calculado'] }}{{ $aval['unidade'] ?? '' }}
                        @if($aval['threshold'])
                            · <strong>Mínimo:</strong> {{ $aval['threshold'] }}{{ $aval['unidade'] ?? '' }}
                        @endif
                    </div>
                @endif
            </div>

            {{-- Ação por tipo --}}
            <div class="flex-shrink-0">
                @if($req->tipo === 'B')
                    <a href="/ngo/patrimonio" class="btn btn-sm btn-outline-primary" style="font-size:.75rem">
                        <i class="bi bi-upload me-1"></i>Fazer Upload
                    </a>
                @elseif($req->tipo === 'C' && $resultado !== 'verde')
                    <button class="btn btn-sm btn-outline-success" style="font-size:.75rem"
                        data-bs-toggle="modal" data-bs-target="#modalDeclarar{{ $req->id }}">
                        <i class="bi bi-pen me-1"></i>Declarar
                    </button>
                @elseif($req->tipo === 'A')
                    <span class="text-muted" style="font-size:.72rem">Automático</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal de declaração (Tipo C) --}}
    @if($req->tipo === 'C')
    <div class="modal fade" id="modalDeclarar{{ $req->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:20px">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Declaração — {{ $req->codigo }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('ngo.conformidade.declarar', $req->id) }}" method="POST">
                    @csrf
                    <div class="modal-body pt-2">
                        @if($req->regra?->pergunta_declaracao)
                            <p class="text-muted" style="font-size:.85rem">{{ $req->regra->pergunta_declaracao }}</p>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Confirmação e observações <span class="text-danger">*</span></label>
                            <textarea name="observacoes" class="form-control" rows="4" placeholder="Descreva como este requisito está sendo cumprido..." required minlength="20"></textarea>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="ciencia{{ $req->id }}" required>
                            <label class="form-check-label small" for="ciencia{{ $req->id }}">
                                Declaro, sob minha responsabilidade, que as informações acima são verdadeiras.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-success fw-semibold px-4">Confirmar Declaração</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endforeach

@endsection
