@extends('layouts.app')

@section('content')

<style>
.plano-card { border-radius:14px; border:1px solid #e2e8f0; padding:16px 18px; margin-bottom:10px; background:#fff; transition:box-shadow .15s; }
.plano-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.status-badge { font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.st-pendente     { background:#f1f5f9; color:#475569; }
.st-em_andamento { background:#e0e7ff; color:#3730a3; }
.st-concluido    { background:#d1fae5; color:#065f46; }
.st-cancelado    { background:#fee2e2; color:#7f1d1d; }
.atrasado-dot    { display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; margin-right:4px; }
</style>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div class="flex-grow-1">
        <h3 class="fw-bold mb-0">Planos de Ação</h3>
        <small class="text-muted">Ações corretivas para requisitos de conformidade em não-conformidade</small>
    </div>
    <button class="btn btn-success btn-sm fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#modalNovo">
        <i class="bi bi-plus-lg me-1"></i> Novo Plano
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$statusConfig = [
    'em_andamento' => ['label' => 'Em Andamento', 'icon' => 'bi-hourglass-split', 'color' => '#6366f1', 'badge' => 'st-em_andamento'],
    'pendente'     => ['label' => 'Pendente',     'icon' => 'bi-clock',           'color' => '#94a3b8', 'badge' => 'st-pendente'],
    'concluido'    => ['label' => 'Concluído',    'icon' => 'bi-check-circle',    'color' => '#10b981', 'badge' => 'st-concluido'],
    'cancelado'    => ['label' => 'Cancelado',    'icon' => 'bi-x-circle',        'color' => '#ef4444', 'badge' => 'st-cancelado'],
];
$totalAtivos = ($planos['em_andamento'] ?? collect())->count() + ($planos['pendente'] ?? collect())->count();
@endphp

{{-- KPI rápido --}}
<div class="row g-3 mb-4">
    @foreach($statusConfig as $st => $cfg)
    @php $grupo = $planos[$st] ?? collect(); @endphp
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center" style="border-radius:16px;border-left:4px solid {{ $cfg['color'] }}!important">
            <div class="card-body py-3">
                <div class="fw-bold" style="font-size:1.6rem;color:{{ $cfg['color'] }}">{{ $grupo->count() }}</div>
                <div class="text-muted" style="font-size:.78rem">{{ $cfg['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Planos por status --}}
@forelse($statusConfig as $st => $cfg)
@php $grupo = $planos[$st] ?? collect(); @endphp
@if($grupo->count() > 0)
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi {{ $cfg['icon'] }}" style="color:{{ $cfg['color'] }}"></i>
        <h6 class="fw-bold mb-0">{{ $cfg['label'] }}</h6>
        <span class="badge rounded-pill" style="background:{{ $cfg['color'] }};font-size:.7rem">{{ $grupo->count() }}</span>
    </div>

    @foreach($grupo as $p)
    <div class="plano-card" style="border-left:4px solid {{ $cfg['color'] }}">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="status-badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                    @if($p->estaAtrasado())
                        <span style="font-size:.72rem;color:#ef4444;font-weight:700">
                            <span class="atrasado-dot"></span>Atrasado
                        </span>
                    @endif
                    @if($p->requisito)
                        <span class="text-muted" style="font-size:.72rem">{{ $p->requisito->codigo }}</span>
                    @endif
                </div>
                <div class="fw-semibold mb-1">{{ $p->titulo }}</div>
                <div class="text-muted" style="font-size:.8rem">{{ Str::limit($p->descricao, 120) }}</div>
                <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:.78rem">
                    <span><i class="bi bi-person me-1 text-muted"></i>{{ $p->responsavel }}</span>
                    <span>
                        <i class="bi bi-calendar me-1 text-muted"></i>
                        <span style="color:{{ $p->estaAtrasado() ? '#ef4444' : '#475569' }}">
                            {{ $p->prazo->format('d/m/Y') }}
                        </span>
                    </span>
                    @if($p->resolvido_em)
                        <span class="text-success"><i class="bi bi-check me-1"></i>Resolvido em {{ $p->resolvido_em->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex-shrink-0 d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem"
                        data-bs-toggle="modal" data-bs-target="#modalEditar{{ $p->id }}">
                    <i class="bi bi-pencil"></i>
                </button>
                <form action="{{ route('ngo.conformidade.planos.destroy', $p->id) }}" method="POST"
                      onsubmit="return confirm('Remover este plano de ação?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        @if($p->observacoes_resolucao)
        <div class="mt-2 pt-2" style="border-top:1px solid #f1f5f9;font-size:.78rem;color:#475569">
            <i class="bi bi-chat-text me-1 text-muted"></i>{{ $p->observacoes_resolucao }}
        </div>
        @endif
    </div>

    {{-- Modal editar --}}
    <div class="modal fade" id="modalEditar{{ $p->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius:20px">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Atualizar Plano</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('ngo.conformidade.planos.update', $p->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body pt-2">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm" required>
                                @foreach($statusConfig as $sv => $sl)
                                    <option value="{{ $sv }}" {{ $p->status === $sv ? 'selected' : '' }}>{{ $sl['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Responsável</label>
                            <input type="text" name="responsavel" class="form-control form-control-sm"
                                   value="{{ $p->responsavel }}" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Prazo</label>
                            <input type="date" name="prazo" class="form-control form-control-sm"
                                   value="{{ $p->prazo->format('Y-m-d') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Observações / Resolução</label>
                            <textarea name="observacoes_resolucao" class="form-control form-control-sm" rows="3"
                                      maxlength="2000">{{ $p->observacoes_resolucao }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@empty
<div class="text-center py-5 text-muted">
    <i class="bi bi-clipboard-check" style="font-size:3rem;opacity:.3"></i>
    <p class="mt-3 fw-semibold">Nenhum plano de ação cadastrado.</p>
    <p class="small">Crie um plano para cada requisito em não-conformidade.</p>
    <button class="btn btn-success btn-sm fw-semibold px-4" data-bs-toggle="modal" data-bs-target="#modalNovo">
        <i class="bi bi-plus-lg me-1"></i> Criar primeiro plano
    </button>
</div>
@endforelse

{{-- Modal novo plano --}}
<div class="modal fade" id="modalNovo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Novo Plano de Ação</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ngo.conformidade.planos.store') }}" method="POST">
                @csrf
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Requisito <span class="text-danger">*</span></label>
                            <select name="requisito_legal_id" class="form-select form-select-sm" required>
                                <option value="">-- Selecione o requisito --</option>
                                @foreach($requisitos->groupBy('eixo') as $eixo => $reqs)
                                    <optgroup label="{{ strtoupper(str_replace('_', ' ', $eixo)) }}">
                                        @foreach($reqs as $r)
                                            <option value="{{ $r->id }}">{{ $r->codigo }} — {{ $r->titulo }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Título do Plano <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control form-control-sm"
                                   placeholder="ex: Regularizar documentação CNAS 2026" maxlength="200" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descrição / Ações necessárias <span class="text-danger">*</span></label>
                            <textarea name="descricao" class="form-control form-control-sm" rows="4"
                                      placeholder="Descreva as ações concretas que serão tomadas..." required minlength="10" maxlength="2000"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Responsável <span class="text-danger">*</span></label>
                            <input type="text" name="responsavel" class="form-control form-control-sm"
                                   placeholder="Nome do responsável" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Prazo <span class="text-danger">*</span></label>
                            <input type="date" name="prazo" class="form-control form-control-sm"
                                   min="{{ now()->addDay()->toDateString() }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-5">
                        <i class="bi bi-check-lg me-1"></i>Criar Plano
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
