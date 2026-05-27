@extends('layouts.app')

@section('title', 'WhatsApp — Campanhas Opt-in')

@push('styles')
<style>
    .status-badge {
        padding: 3px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .status-rascunho    { background: #f1f5f9; color: #475569; }
    .status-agendada    { background: #fef9ec; color: #d97706; }
    .status-processando { background: #dbeafe; color: #2563eb; }
    .status-concluida   { background: #dcfce7; color: #16a34a; }
    .status-cancelada   { background: #fee2e2; color: #dc2626; }
    .progress-bar-wrap {
        height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; min-width: 80px;
    }
    .progress-bar-fill {
        height: 100%; background: #10b981; border-radius: 4px; transition: width .3s;
    }
    .compose-card {
        background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;
        padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;
    }
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
            Ver contatos
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Formulário nova campanha --}}
    <div class="compose-card">
        <h6 class="fw-bold mb-3">Nova campanha</h6>
        <form method="POST" action="{{ route('whatsapp.optin.campanhas.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" value="{{ old('titulo') }}" required maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Intervalo entre envios</label>
                    <select name="intervalo_segundos" class="form-select">
                        @foreach([1 => '1 segundo', 2 => '2 segundos', 3 => '3 segundos', 5 => '5 segundos', 10 => '10 segundos'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('intervalo_segundos', 3) == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Agendar para (opcional)</label>
                    <input type="datetime-local" name="agendada_para" class="form-control" value="{{ old('agendada_para') }}">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Mensagem <span class="text-danger">*</span></label>
                    <textarea name="mensagem" class="form-control" rows="4" required maxlength="4000" placeholder="Use {nome} para personalizar com o nome do contato.">{{ old('mensagem') }}</textarea>
                    <div class="form-text">Variáveis disponíveis: <code>{nome}</code>, <code>{telefone}</code></div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Criar campanha</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Lista de campanhas --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Título</th>
                            <th>Status</th>
                            <th>Progresso</th>
                            <th>Enviados</th>
                            <th>Falhas</th>
                            <th>Criada em</th>
                            <th class="pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campanhas as $campanha)
                        <tr>
                            <td class="ps-4 fw-medium">{{ $campanha->titulo }}</td>
                            <td>
                                <span class="status-badge status-{{ $campanha->status }}">
                                    {{ ucfirst($campanha->status) }}
                                </span>
                            </td>
                            <td>
                                @if($campanha->total_contatos > 0)
                                    @php $pct = $campanha->progresso(); @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress-bar-wrap flex-grow-1">
                                            <div class="progress-bar-fill" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-success fw-semibold">{{ $campanha->total_enviados }}</td>
                            <td class="text-danger fw-semibold">{{ $campanha->total_falhas }}</td>
                            <td class="small text-muted">{{ $campanha->created_at->format('d/m/Y H:i') }}</td>
                            <td class="pe-4">
                                <div class="d-flex gap-1">
                                    @if($campanha->status === 'rascunho')
                                        <form method="POST" action="{{ route('whatsapp.optin.campanhas.disparar', $campanha) }}"
                                              onsubmit="return confirm('Disparar campanha para todos os contatos com opt-in?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">▶ Disparar</button>
                                        </form>
                                    @endif
                                    @if(in_array($campanha->status, ['rascunho', 'agendada']))
                                        <form method="POST" action="{{ route('whatsapp.optin.campanhas.cancelar', $campanha) }}"
                                              onsubmit="return confirm('Cancelar esta campanha?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Cancelar</button>
                                        </form>
                                    @endif
                                    @if($campanha->status === 'processando')
                                        <span class="small text-primary" id="status-{{ $campanha->id }}">Processando…</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Nenhuma campanha criada ainda.</td>
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
        const row = el.closest('tr');

        const interval = setInterval(function () {
            fetch('/whatsapp/optin/campanhas/' + id + '/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(function (data) {
                // Atualiza barra de progresso
                const pctCell = row.cells[2];
                if (data.total_contatos > 0) {
                    pctCell.innerHTML = '<div class="d-flex align-items-center gap-2">'
                        + '<div class="progress-bar-wrap flex-grow-1">'
                        + '<div class="progress-bar-fill" style="width:' + data.progresso + '%"></div>'
                        + '</div><span class="small text-muted">' + data.progresso + '%</span></div>';
                }
                row.cells[3].textContent = data.total_enviados;
                row.cells[4].textContent = data.total_falhas;

                if (data.status !== 'processando') {
                    clearInterval(interval);
                    // Recarregar página para refletir status final
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(function () { /* ignora erros de rede */ });
        }, 3000);
    });
})();
</script>
@endpush

@endsection
