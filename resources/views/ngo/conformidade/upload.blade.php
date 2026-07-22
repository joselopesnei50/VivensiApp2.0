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
@endphp

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ngo.conformidade.eixo', $requisito->eixo) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div>
        <h3 class="fw-bold mb-0">Enviar Documento</h3>
        <small class="text-muted">{{ $eixoLabels[$requisito->eixo] ?? $requisito->eixo }} · {{ $requisito->codigo }}</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    {{-- Upload form --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-1">{{ $requisito->titulo }}</h5>
                <p class="text-muted small mb-4">{{ $requisito->enunciado }}</p>

                @if($requisito->regra?->tipo_documento_obrigatorio)
                <div class="mb-4 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <div class="small fw-semibold text-success mb-1"><i class="bi bi-shield-check me-1"></i>Documento exigido</div>
                    <div class="fw-bold">{{ $requisito->regra->tipo_documento_obrigatorio }}</div>
                    @if($requisito->regra->alerta_dias_antes)
                        <div class="small text-muted mt-1">Alerta com {{ $requisito->regra->alerta_dias_antes }} dias de antecedência do vencimento</div>
                    @endif
                </div>
                @endif

                <form action="{{ route('ngo.conformidade.upload', $requisito->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Arquivo <span class="text-danger">*</span></label>
                        <input type="file" name="arquivo" class="form-control @error('arquivo') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        @error('arquivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">PDF, JPG ou PNG · máximo 10 MB</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Válido até</label>
                        <input type="date" name="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                               value="{{ old('valid_until') }}" min="{{ now()->addDay()->format('Y-m-d') }}">
                        @error('valid_until')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Deixe em branco para documentos sem vencimento (ex.: estatuto social)</div>
                    </div>

                    <button type="submit" class="btn btn-success fw-semibold px-4">
                        <i class="bi bi-upload me-2"></i>Enviar Documento
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Histórico de versões --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Histórico de versões</h6>
                @forelse($historico as $doc)
                <div class="d-flex align-items-start gap-3 mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="flex-shrink-0 mt-1">
                        @if($doc->mime_type === 'application/pdf')
                            <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                        @else
                            <i class="bi bi-file-earmark-image text-primary fs-5"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold small text-truncate">{{ $doc->original_name }}</div>
                        <div class="small text-muted">
                            v{{ $doc->versao ?? 1 }} ·
                            {{ $doc->created_at->format('d/m/Y') }}
                            @if($doc->valid_until)
                                · Válido até {{ $doc->valid_until->format('d/m/Y') }}
                                @php $dias = (int) now()->diffInDays($doc->valid_until, false); @endphp
                                @if($dias < 0)
                                    <span class="badge bg-danger ms-1" style="font-size:.65rem;">Vencido</span>
                                @elseif($dias <= 30)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">{{ $dias }}d</span>
                                @endif
                            @endif
                        </div>
                        @if($doc->substituido_por_id)
                            <div class="small text-muted mt-1" style="font-size:.7rem;"><i class="bi bi-arrow-up-right me-1"></i>Substituído</div>
                        @else
                            <span class="badge bg-success ms-0 mt-1" style="font-size:.65rem;">Atual</span>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <a href="{{ route('ngo.conformidade.download', $doc->id) }}"
                           class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;" title="Baixar">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-muted small text-center py-3">
                    <i class="bi bi-inbox fs-4 d-block mb-2 opacity-50"></i>
                    Nenhum documento enviado ainda
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
