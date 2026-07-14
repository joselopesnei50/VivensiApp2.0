@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px;">

    <div class="mb-4">
        <p class="text-muted text-uppercase small mb-1" style="letter-spacing: .15em;">Privacidade · LGPD</p>
        <h1 class="h3 mb-1">🛡️ Meus dados pessoais</h1>
        <p class="text-muted mb-0">Exerça seus direitos previstos na Lei 13.709/2018 (LGPD).</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning rounded-3">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3">{{ session('error') }}</div>
    @endif

    {{-- ── Exportacao art. 18 IV ─────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start mb-3">
                <div class="me-3" style="font-size:2rem;">📦</div>
                <div>
                    <h5 class="fw-bold mb-1">Exportar meus dados</h5>
                    <p class="text-muted small mb-0">
                        <strong>Art. 18, IV — Portabilidade.</strong>
                        Receba por e-mail um arquivo ZIP contendo todos os seus dados pessoais
                        em formato JSON estruturado. O link expira em 48 horas.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('lgpd.self.export') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-download me-2"></i> Solicitar exportação
                </button>
                <small class="text-muted d-block mt-2">
                    Limite: 1 solicitação a cada 6 horas.
                </small>
            </form>

            @if($exports->isNotEmpty())
                <hr class="my-4">
                <h6 class="fw-bold mb-3">Suas exportações recentes</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Solicitada em</th>
                                <th>Status</th>
                                <th class="text-end">Downloads</th>
                                <th class="text-end">Expira em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exports as $r)
                                <tr>
                                    <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($r->status === 'completed')
                                            <span class="badge bg-success">Pronto</span>
                                        @elseif($r->status === 'processing')
                                            <span class="badge bg-primary">Processando</span>
                                        @elseif($r->status === 'pending')
                                            <span class="badge bg-secondary">Aguardando</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($r->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $r->export_download_count }}</td>
                                    <td class="text-end">
                                        @if($r->export_expires_at)
                                            @if($r->export_expires_at->isPast())
                                                <span class="text-muted small">Expirou</span>
                                            @else
                                                <span class="small">{{ $r->export_expires_at->format('d/m H:i') }}</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Delecao art. 15 ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start mb-3">
                <div class="me-3" style="font-size:2rem;">⚠️</div>
                <div>
                    <h5 class="fw-bold mb-1 text-danger">Excluir minha conta</h5>
                    <p class="text-muted small mb-0">
                        <strong>Art. 15 — Eliminação.</strong>
                        Ao solicitar, sua conta será agendada para exclusão em <strong>30 dias</strong>.
                        Dentro deste prazo você pode cancelar a qualquer momento. Após executada,
                        seus dados pessoais serão anonimizados (nome, e-mail, telefone removidos).
                    </p>
                </div>
            </div>

            @if($activeDeletion)
                <div class="alert alert-warning border-0 rounded-3 mb-3">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                        <div>
                            <strong>Exclusão agendada</strong><br>
                            <small>
                                Solicitada em {{ $activeDeletion->created_at->format('d/m/Y H:i') }}.<br>
                                Sua conta será anonimizada em
                                <strong>{{ $activeDeletion->scheduled_for->format('d/m/Y') }}</strong>
                                ({{ $activeDeletion->daysUntilPurge() }} dias).
                            </small>
                        </div>
                        <form method="POST" action="{{ route('lgpd.self.cancel_delete', $activeDeletion->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i class="fas fa-undo me-1"></i> Cancelar exclusão
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('lgpd.self.delete') }}"
                      onsubmit="return confirm('Confirma que quer agendar a exclusão da sua conta? Você terá 30 dias para cancelar.');">
                    @csrf
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            Entendo que após 30 dias meus dados pessoais serão anonimizados de forma irreversível.
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-user-times me-2"></i> Solicitar exclusão da minha conta
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ── Informacoes ────────────────────────────────────────────────────── --}}
    <div class="card border-0 rounded-4" style="background: #f1f5f9;">
        <div class="card-body p-4 small text-muted">
            <h6 class="fw-bold text-dark mb-2">📞 Outros direitos e contato</h6>
            <p class="mb-2">
                Para exercer outros direitos (correção, bloqueio, revisão de decisões automatizadas)
                ou tirar dúvidas sobre o tratamento dos seus dados, entre em contato com nosso DPO:
            </p>
            <p class="mb-0">
                <strong>Encarregado de Dados (DPO):</strong>
                @php $dpoEmail = config('legal.email_dpo', 'dpo@vivensi.app.br'); @endphp
                <a href="mailto:{{ $dpoEmail }}">{{ $dpoEmail }}</a><br>
                <strong>Controlador:</strong> NC5 HUB DIGITAL LTDA · CNPJ 67.848.807/0001-50
            </p>
        </div>
    </div>

</div>
@endsection
