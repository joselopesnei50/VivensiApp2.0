@extends('layouts.app')

@section('content')

@php
$resultado  = $avaliacaoAtual['resultado'] ?? 'nao_aplicavel';
$leftColor  = match($resultado) { 'verde' => '#10b981', 'amarelo' => '#f59e0b', 'vermelho' => '#ef4444', default => '#cbd5e1' };
$badgeClass = match($resultado) { 'verde' => 'res-verde', 'amarelo' => 'res-amarelo', 'vermelho' => 'res-vermelho', default => 'res-neutro' };
$badgeLabel = match($resultado) { 'verde' => 'Verde ✓', 'amarelo' => 'Atenção ⚠', 'vermelho' => 'Crítico ✗', default => 'N/A' };
@endphp

<style>
.res-badge   { font-size:.72rem; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.res-verde   { background:#d1fae5; color:#065f46; }
.res-amarelo { background:#fef9c3; color:#713f12; }
.res-vermelho{ background:#fee2e2; color:#7f1d1d; }
.res-neutro  { background:#f1f5f9; color:#64748b; }
.tipo-badge  { font-size:.65rem; font-weight:700; padding:2px 7px; border-radius:6px; background:#e0e7ff; color:#3730a3; }
.ev-card     { border-radius:12px; border:1px solid #e2e8f0; padding:14px 16px; margin-bottom:8px; background:#fff; }
</style>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('ngo.conformidade.eixo', $req->eixo) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="res-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            <span class="tipo-badge">Tipo {{ $req->tipo }}</span>
            <span class="text-muted" style="font-size:.75rem">{{ $req->codigo }}</span>
            <span class="badge bg-light text-secondary" style="font-size:.7rem">Risco {{ ucfirst($req->risco) }}</span>
        </div>
        <h4 class="fw-bold mb-0">{{ $req->titulo }}</h4>
        <p class="text-muted mb-0" style="font-size:.85rem">{{ $req->enunciado }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Coluna principal --}}
    <div class="col-lg-8">

        {{-- Tipo A: explicação do cálculo --}}
        @if($req->tipo === 'A' && $req->regra)
        <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-2 text-primary"></i>Cálculo Automático</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <span class="small text-muted">Modelo</span>
                        <div class="fw-semibold">{{ $req->regra->modelo ?? '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <span class="small text-muted">Threshold mínimo</span>
                        <div class="fw-semibold">
                            {{ $req->regra->threshold !== null ? (float)$req->regra->threshold : '—' }}
                            {{ $req->regra->unidade ?? '' }}
                        </div>
                    </div>
                    @if($avaliacaoAtual && $avaliacaoAtual['valor_calculado'] !== null)
                    <div class="col-sm-6">
                        <span class="small text-muted">Último valor calculado</span>
                        <div class="fw-bold" style="color:{{ $leftColor }}">
                            {{ $avaliacaoAtual['valor_calculado'] }}{{ $req->regra->unidade ?? '' }}
                        </div>
                    </div>
                    @endif
                    @if($req->regra->formula)
                    <div class="col-12">
                        <span class="small text-muted">Fórmula</span>
                        <code style="font-size:.78rem">{{ $req->regra->formula }}</code>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Tipo B: documentos --}}
        @if($req->tipo === 'B')
        <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-earmark-check me-2 text-warning"></i>Documentos</h6>
                    <a href="{{ route('ngo.conformidade.upload.form', $req->id) }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:.78rem">
                        <i class="bi bi-upload me-1"></i>Enviar novo
                    </a>
                </div>
                @if($documentos && $documentos->count() > 0)
                    @foreach($documentos as $doc)
                    <div class="ev-card d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold" style="font-size:.85rem">{{ $doc->original_name }}</div>
                            <div class="text-muted" style="font-size:.75rem">
                                Versão {{ $doc->versao ?? 1 }}
                                @if($doc->valid_until)
                                    · Validade:
                                    <span style="color:{{ $doc->valid_until->isPast() ? '#ef4444' : ($doc->valid_until->diffInDays() < 30 ? '#f59e0b' : '#10b981') }}">
                                        {{ $doc->valid_until->format('d/m/Y') }}
                                    </span>
                                @endif
                                · {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y') }}
                            </div>
                        </div>
                        <a href="{{ route('ngo.conformidade.download', $doc->id) }}"
                           class="btn btn-sm btn-outline-secondary flex-shrink-0" style="font-size:.75rem">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted small text-center py-3">Nenhum documento enviado ainda.</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Tipo C: declarações com evidências + form para nova evidência --}}
        @if($req->tipo === 'C')
        <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-check2-square me-2 text-success"></i>Declarações</h6>
                    <button class="btn btn-sm btn-outline-success" style="font-size:.78rem"
                            data-bs-toggle="modal" data-bs-target="#modalDeclarar">
                        <i class="bi bi-pen me-1"></i>Nova declaração
                    </button>
                </div>

                @forelse($historico->where('avaliado_por', '!=', null) as $dec)
                <div class="ev-card mb-2">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <span class="res-badge {{ $dec->resultado === 'verde' ? 'res-verde' : 'res-vermelho' }}" style="font-size:.65rem">
                                {{ ucfirst($dec->resultado) }}
                            </span>
                            <span class="text-muted ms-2" style="font-size:.75rem">
                                {{ $dec->avaliado_em?->format('d/m/Y H:i') }}
                                @if($dec->avaliador) · {{ $dec->avaliador->name }} @endif
                            </span>
                        </div>
                    </div>
                    <p class="mb-2" style="font-size:.82rem">{{ $dec->observacoes }}</p>

                    {{-- Evidências desta declaração --}}
                    @if($dec->evidencias->count() > 0)
                    <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9">
                        <p class="text-muted mb-1" style="font-size:.72rem">Evidências:</p>
                        @foreach($dec->evidencias as $ev)
                        <div class="d-flex align-items-center gap-2 mb-1" style="font-size:.78rem">
                            <i class="bi bi-paperclip text-muted"></i>
                            <span>{{ $ev->descricao }}</span>
                            @if($ev->evidenciavel_type === \App\Models\Attachment::class && $ev->evidenciavel_id)
                                <a href="{{ route('ngo.conformidade.download', $ev->evidenciavel_id) }}"
                                   class="btn btn-sm btn-outline-secondary py-0" style="font-size:.7rem">
                                    <i class="bi bi-download"></i>
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Botão para adicionar evidência à declaração mais recente --}}
                    @if($loop->first)
                    <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9">
                        <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem"
                                data-bs-toggle="collapse" data-bs-target="#collapseEvidencia{{ $dec->id }}">
                            <i class="bi bi-paperclip me-1"></i>Anexar evidência
                        </button>
                        <div class="collapse mt-2" id="collapseEvidencia{{ $dec->id }}">
                            <form action="{{ route('ngo.conformidade.evidencia.upload', $dec->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="descricao" class="form-control form-control-sm"
                                           placeholder="Descrição da evidência *" required minlength="5" maxlength="500">
                                </div>
                                <div class="mb-2">
                                    <input type="file" name="arquivo" class="form-control form-control-sm"
                                           accept=".pdf,.jpg,.jpeg,.png" required>
                                    <div class="form-text" style="font-size:.7rem">PDF, JPG ou PNG · máx 10 MB</div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-success fw-semibold px-3" style="font-size:.75rem">
                                    <i class="bi bi-upload me-1"></i>Enviar
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                    <p class="text-muted small text-center py-3">Nenhuma declaração registrada. Clique em "Nova declaração" para começar.</p>
                @endforelse
            </div>
        </div>

        {{-- Modal nova declaração --}}
        <div class="modal fade" id="modalDeclarar" tabindex="-1">
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
                            <label class="form-label fw-semibold small">Confirmação e observações <span class="text-danger">*</span></label>
                            <textarea name="observacoes" class="form-control" rows="4"
                                      placeholder="Descreva como este requisito está sendo cumprido..." required minlength="20"></textarea>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-success fw-semibold px-4">
                                <i class="bi bi-check-lg me-1"></i>Confirmar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Histórico completo de avaliações (Tipo A/B) --}}
        @if($req->tipo !== 'C' && $historico->count() > 0)
        <div class="card border-0 shadow-sm" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-secondary"></i>Histórico de Avaliações</h6>
                <div style="font-size:.82rem">
                    @foreach($historico->take(10) as $h)
                    <div class="d-flex align-items-center gap-3 mb-2 pb-2" style="border-bottom:1px solid #f1f5f9">
                        <span class="res-badge {{ $h->resultado === 'verde' ? 'res-verde' : ($h->resultado === 'amarelo' ? 'res-amarelo' : 'res-vermelho') }}" style="font-size:.65rem;white-space:nowrap">
                            {{ ucfirst($h->resultado) }}
                        </span>
                        <span class="text-muted">{{ $h->avaliado_em?->format('d/m/Y H:i') }}</span>
                        @if($h->valor_calculado !== null)
                            <span class="fw-semibold">{{ (float)$h->valor_calculado }}{{ $req->regra?->unidade }}</span>
                        @endif
                        @if($h->observacoes)
                            <span class="text-muted flex-grow-1 text-truncate" style="max-width:200px" title="{{ $h->observacoes }}">{{ $h->observacoes }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Info do requisito --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-secondary"></i>Detalhes</h6>
                <dl class="mb-0" style="font-size:.82rem">
                    <dt class="text-muted">Eixo</dt>
                    <dd class="fw-semibold mb-2">{{ strtoupper(str_replace('_', ' ', $req->eixo)) }}</dd>
                    <dt class="text-muted">Periodicidade</dt>
                    <dd class="fw-semibold mb-2">{{ ucfirst($req->periodicidade ?? '—') }}</dd>
                    @if($req->base_legal)
                    <dt class="text-muted">Base Legal</dt>
                    <dd class="fw-semibold mb-2">{{ $req->base_legal }}</dd>
                    @endif
                    @if($req->vigente_de || $req->vigente_ate)
                    <dt class="text-muted">Vigência</dt>
                    <dd class="fw-semibold mb-2">
                        {{ $req->vigente_de?->format('d/m/Y') ?? '—' }} a {{ $req->vigente_ate?->format('d/m/Y') ?? 'indefinido' }}
                    </dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Status --}}
        <div class="card border-0 shadow-sm" style="border-radius:20px;border:2px solid {{ $leftColor }}!important">
            <div class="card-body p-4 text-center">
                <p class="text-muted small mb-2">Status atual</p>
                <div class="fw-bold" style="font-size:2rem;color:{{ $leftColor }}">{{ $badgeLabel }}</div>
                @if($avaliacaoAtual && isset($avaliacaoAtual['detalhe']))
                    <p class="text-muted mt-2 mb-0" style="font-size:.78rem">{{ $avaliacaoAtual['detalhe'] }}</p>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
