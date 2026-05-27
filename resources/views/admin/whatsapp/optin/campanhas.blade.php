@extends('layouts.app')

@section('title', 'WhatsApp — Campanhas Opt-in')

@push('styles')
<style>
    .status-badge { padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
    .status-rascunho    { background: #f1f5f9; color: #475569; }
    .status-agendada    { background: #fef9ec; color: #d97706; }
    .status-processando { background: #dbeafe; color: #2563eb; }
    .status-concluida   { background: #dcfce7; color: #16a34a; }
    .status-cancelada   { background: #fee2e2; color: #dc2626; }
    .progress-bar-wrap  { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; min-width: 80px; }
    .progress-bar-fill  { height: 100%; background: #10b981; border-radius: 4px; transition: width .3s; }
    .compose-card       { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .compose-card.editing { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
    .msg-preview        { font-size: 0.8rem; color: #64748b; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Campanhas WhatsApp — Opt-in</h4>
            <p class="text-muted small mb-0">Disparos apenas para contatos com consentimento ativo</p>
        </div>
        <a href="{{ route('whatsapp.optin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-users me-1"></i> Ver contatos
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-1"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Formulário: criar ou editar rascunho --}}
    @php $editing = $campanha ?? null; @endphp
    <div class="compose-card {{ $editing ? 'editing' : '' }}" id="compose-form">
        <h6 class="fw-bold mb-3">
            @if($editing)
                <i class="fas fa-pen text-indigo me-1" style="color:#6366f1;"></i> Editando rascunho — <em>{{ $editing->titulo }}</em>
            @else
                <i class="fas fa-plus-circle me-1 text-success"></i> Nova campanha
            @endif
        </h6>

        @if($editing)
            <form method="POST" action="{{ route('whatsapp.optin.campanhas.update', $editing) }}">
                @csrf @method('PATCH')
        @else
            <form method="POST" action="{{ route('whatsapp.optin.campanhas.store') }}">
                @csrf
        @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror"
                           value="{{ old('titulo', $editing?->titulo) }}" required maxlength="255">
                    @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Intervalo entre envios</label>
                    <select name="intervalo_segundos" class="form-select">
                        @foreach([1 => '1 segundo', 2 => '2 segundos', 3 => '3 segundos', 5 => '5 segundos', 10 => '10 segundos'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('intervalo_segundos', $editing?->intervalo_segundos ?? 3) == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Agendar para <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="datetime-local" name="agendada_para" class="form-control @error('agendada_para') is-invalid @enderror"
                           value="{{ old('agendada_para', $editing?->agendada_para?->format('Y-m-d\TH:i')) }}">
                    @error('agendada_para')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Mensagem <span class="text-danger">*</span></label>
                    <textarea name="mensagem" class="form-control @error('mensagem') is-invalid @enderror"
                              rows="4" required maxlength="4000"
                              placeholder="Use {nome} para personalizar com o nome do contato.">{{ old('mensagem', $editing?->mensagem) }}</textarea>
                    @error('mensagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Variáveis disponíveis: <code>{nome}</code>, <code>{telefone}</code></div>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    @if($editing)
                        <a href="{{ route('whatsapp.optin.campanhas') }}" class="btn btn-outline-secondary btn-sm">Cancelar edição</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar alterações
                        </button>
                    @else
                        <span></span>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Criar campanha
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Lista de campanhas --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Título</th>
                            <th>Mensagem</th>
                            <th>Status</th>
                            <th>Progresso</th>
                            <th class="text-center">Enviados</th>
                            <th class="text-center">Falhas</th>
                            <th>Agendada para</th>
                            <th>Criada em</th>
                            <th class="pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campanhas as $campanha)
                        <tr>
                            <td class="ps-4 fw-medium">{{ $campanha->titulo }}</td>
                            <td>
                                <span class="msg-preview" title="{{ $campanha->mensagem }}">
                                    {{ $campanha->mensagem }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $labels = [
                                        'rascunho'    => 'Rascunho',
                                        'agendada'    => 'Agendada',
                                        'processando' => 'Enviando…',
                                        'concluida'   => 'Concluída',
                                        'cancelada'   => 'Cancelada',
                                    ];
                                @endphp
                                <span class="status-badge status-{{ $campanha->status }}">
                                    {{ $labels[$campanha->status] ?? ucfirst($campanha->status) }}
                                </span>
                            </td>
                            <td>
                                @if($campanha->total_contatos > 0)
                                    @php $pct = $campanha->progresso(); @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress-bar-wrap flex-grow-1">
                                            <div class="progress-bar-fill" id="bar-{{ $campanha->id }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="small text-muted" id="pct-{{ $campanha->id }}">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center text-success fw-semibold" id="sent-{{ $campanha->id }}">{{ $campanha->total_enviados }}</td>
                            <td class="text-center text-danger fw-semibold" id="fail-{{ $campanha->id }}">{{ $campanha->total_falhas }}</td>
                            <td class="small text-muted">
                                {{ $campanha->agendada_para ? $campanha->agendada_para->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="small text-muted">{{ $campanha->created_at->format('d/m/Y H:i') }}</td>
                            <td class="pe-4">
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($campanha->status === 'rascunho')
                                        {{-- Disparar --}}
                                        <form method="POST" action="{{ route('whatsapp.optin.campanhas.disparar', $campanha) }}"
                                              onsubmit="return confirm('Disparar para todos os contatos com opt-in ativo?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm" title="Disparar agora">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        {{-- Editar --}}
                                        <a href="{{ route('whatsapp.optin.campanhas.edit', $campanha) }}#compose-form"
                                           class="btn btn-outline-primary btn-sm" title="Editar rascunho">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                    @endif

                                    @if(in_array($campanha->status, ['rascunho', 'agendada']))
                                        {{-- Cancelar --}}
                                        <form method="POST" action="{{ route('whatsapp.optin.campanhas.cancelar', $campanha) }}"
                                              onsubmit="return confirm('Cancelar esta campanha?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-outline-warning btn-sm" title="Cancelar">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($campanha->status === 'processando')
                                        <span class="small text-primary d-flex align-items-center gap-1" id="status-{{ $campanha->id }}">
                                            <span class="spinner-border spinner-border-sm"></span> Enviando…
                                        </span>
                                    @endif

                                    {{-- Duplicar (sempre disponível) --}}
                                    <form method="POST" action="{{ route('whatsapp.optin.campanhas.duplicate', $campanha) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm" title="Duplicar como rascunho">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>

                                    {{-- Excluir (só rascunho, cancelada, concluída) --}}
                                    @if(in_array($campanha->status, ['rascunho', 'cancelada', 'concluida']))
                                        <form method="POST" action="{{ route('whatsapp.optin.campanhas.destroy', $campanha) }}"
                                              onsubmit="return confirm('Excluir permanentemente esta campanha?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Nenhuma campanha criada ainda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($campanhas->hasPages())
        <div class="card-footer bg-transparent">
            {{ $campanhas->links() }}
        </div>
        @endif
    </div>

</div>
@push('scripts')
<script>
// Polling de progresso para campanhas em processamento
(function () {
    const processando = document.querySelectorAll('[id^="status-"]');
    if (!processando.length) return;

    processando.forEach(function (el) {
        const id = el.id.replace('status-', '');

        const interval = setInterval(function () {
            fetch('/whatsapp/optin/campanhas/' + id + '/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(function (data) {
                const bar = document.getElementById('bar-' + id);
                const pct = document.getElementById('pct-' + id);
                if (bar && data.progresso !== undefined) {
                    bar.style.width = data.progresso + '%';
                    if (pct) pct.textContent = data.progresso + '%';
                }
                const sent = document.getElementById('sent-' + id);
                const fail = document.getElementById('fail-' + id);
                if (sent) sent.textContent = data.total_enviados;
                if (fail) fail.textContent = data.total_falhas;

                if (data.status !== 'processando') {
                    clearInterval(interval);
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(function () {});
        }, 3000);
    });
})();

// Scroll para formulário ao clicar em Editar
@if(isset($campanha))
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('compose-form');
    if (form) form.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
@endif
</script>
@endpush

@endsection
