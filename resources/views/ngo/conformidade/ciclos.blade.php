@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div>
        <h3 class="fw-bold mb-0">Ciclos de Conformidade</h3>
        <small class="text-muted">Gerencie os períodos de avaliação por eixo regulatório</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$eixoMeta = [
    'cebas_geral'    => ['label' => 'CEBAS Geral',           'icon' => 'bi-shield-check',    'color' => '#6366f1', 'enquadramentos' => ['3_anos', '5_anos']],
    'cebas_as'       => ['label' => 'CEBAS Assist. Social',  'icon' => 'bi-people',          'color' => '#8b5cf6', 'enquadramentos' => ['3_anos', '5_anos']],
    'cebas_saude'    => ['label' => 'CEBAS Saúde',           'icon' => 'bi-heart-pulse',     'color' => '#ec4899', 'enquadramentos' => ['3_anos', '5_anos']],
    'cebas_educacao' => ['label' => 'CEBAS Educação',        'icon' => 'bi-book',            'color' => '#f59e0b', 'enquadramentos' => ['3_anos', '5_anos']],
    'mrosc'          => ['label' => 'MROSC',                 'icon' => 'bi-file-earmark-text','color' => '#3b82f6', 'enquadramentos' => ['anual', 'por_parceria']],
    'suas'           => ['label' => 'SUAS',                  'icon' => 'bi-house-heart',     'color' => '#10b981', 'enquadramentos' => ['anual']],
];
$enquadramentoLabel = [
    '3_anos'      => '3 anos',
    '5_anos'      => '5 anos',
    'anual'       => 'Anual',
    'por_parceria' => 'Por parceria',
];
@endphp

<div class="row g-4">
    @foreach($eixos as $eixo)
    @php
        $meta         = $eixoMeta[$eixo];
        $historico    = $ciclosPorEixo[$eixo] ?? collect();
        $cicloAtivo   = $historico->firstWhere('status', 'em_andamento');
    @endphp

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:20px">
            <div class="card-body p-4">

                {{-- Cabeçalho --}}
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi {{ $meta['icon'] }}" style="color:{{ $meta['color'] }};font-size:1.2rem"></i>
                    <h6 class="fw-bold mb-0">{{ $meta['label'] }}</h6>
                    @if($cicloAtivo)
                        <span class="badge ms-auto" style="background:#d1fae5;color:#065f46;font-size:.7rem">Em andamento</span>
                    @else
                        <span class="badge ms-auto bg-secondary" style="font-size:.7rem">Sem ciclo ativo</span>
                    @endif
                </div>

                {{-- Ciclo ativo --}}
                @if($cicloAtivo)
                <div class="p-3 mb-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-muted">Início</span><br>
                            <strong>{{ $cicloAtivo->data_inicio->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Fim</span><br>
                            <strong>{{ $cicloAtivo->data_fim->format('d/m/Y') }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Enquadramento</span><br>
                            <strong>{{ $enquadramentoLabel[$cicloAtivo->enquadramento ?? ''] ?? '—' }}</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Dias restantes</span><br>
                            @php $dias = $cicloAtivo->dias_restantes; @endphp
                            <strong style="color:{{ $dias < 30 ? '#ef4444' : ($dias < 90 ? '#f59e0b' : '#10b981') }}">
                                {{ $dias }} dia{{ $dias !== 1 ? 's' : '' }}
                            </strong>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Formulário: criar / renovar ciclo --}}
                <details class="mt-1" {{ $cicloAtivo ? '' : 'open' }}>
                    <summary class="small fw-semibold text-primary" style="cursor:pointer;list-style:none;">
                        <i class="bi bi-plus-circle me-1"></i>
                        {{ $cicloAtivo ? 'Encerrar e renovar ciclo' : 'Criar ciclo' }}
                    </summary>
                    <form action="{{ route('ngo.conformidade.ciclo') }}" method="POST" class="mt-3">
                        @csrf
                        <input type="hidden" name="eixo" value="{{ $eixo }}">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Início</label>
                                <input type="date" name="data_inicio" class="form-control form-control-sm"
                                       value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Fim</label>
                                <input type="date" name="data_fim" class="form-control form-control-sm"
                                       value="{{ now()->addYears($eixo === 'mrosc' || $eixo === 'suas' ? 1 : 3)->toDateString() }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Enquadramento</label>
                                <select name="enquadramento" class="form-select form-select-sm">
                                    <option value="">-- Selecione --</option>
                                    @foreach($meta['enquadramentos'] as $enq)
                                        <option value="{{ $enq }}">{{ $enquadramentoLabel[$enq] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mt-1">
                                @if($cicloAtivo)
                                    <div class="alert alert-warning py-2 mb-2" style="font-size:.75rem">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        O ciclo atual será encerrado automaticamente.
                                    </div>
                                @endif
                                <button type="submit" class="btn btn-sm fw-semibold px-4"
                                        style="background:{{ $meta['color'] }};color:white;border:none;">
                                    <i class="bi bi-check-lg me-1"></i>
                                    {{ $cicloAtivo ? 'Renovar' : 'Criar' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </details>

                {{-- Histórico --}}
                @if($historico->where('status', 'encerrado')->count() > 0)
                <div class="mt-3 pt-3" style="border-top:1px solid #f1f5f9">
                    <p class="small fw-semibold text-muted mb-2">Histórico</p>
                    @foreach($historico->where('status', 'encerrado')->take(3) as $c)
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ $c->data_inicio->format('d/m/Y') }} – {{ $c->data_fim->format('d/m/Y') }}</span>
                        <span class="badge bg-light text-secondary" style="font-size:.65rem">encerrado</span>
                    </div>
                    @endforeach
                </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
