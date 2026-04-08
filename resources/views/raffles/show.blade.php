@extends('layouts.app')

@section('content')
<div class="header-page mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('raffles.index') }}" class="btn btn-outline-secondary rounded-circle p-2 border-1 shadow-sm">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <h6 class="text-primary fw-700 text-uppercase mb-1 ls-1" style="font-size: 0.75rem;">Gestão de Campanha</h6>
            <h2 class="fw-800 mb-0">{{ $raffle->title }}</h2>
            <p class="text-muted small mb-0">Controle de bilhetes, pagamentos e realização do sorteio.</p>
        </div>
    </div>
</div>

<div class="row g-4 pb-5">
    <!-- Sidebar Info -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <span class="badge bg-primary-soft text-primary rounded-pill p-2"><i class="bi bi-info-circle"></i></span>
                    Resumo da Rifa
                </h5>
                
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted small text-uppercase fw-bold ls-1 d-block mb-1">Preço do Bilhete</span>
                        <span class="fs-5 fw-800 text-primary">R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}</span>
                    </div>
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted small text-uppercase fw-bold ls-1 d-block mb-1">Data do Sorteio</span>
                        <span class="fw-bold">{{ $raffle->draw_date->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="p-3 bg-light rounded-3">
                        <span class="text-muted small text-uppercase fw-bold ls-1 d-block mb-1">Status Atual</span>
                        <span class="badge rounded-pill {{ $raffle->status == 'active' ? 'bg-success' : ($raffle->status == 'finished' ? 'bg-primary' : 'bg-secondary') }} px-3 py-2 shadow-sm">
                            {{ $raffle->status == 'active' ? 'ATIVA (EM VENDA)' : ($raffle->status == 'finished' ? 'FINALIZADA' : 'INATIVA') }}
                        </span>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top">
                        <label class="small fw-700 text-muted text-uppercase mb-2">Link de Divulgação</label>
                        <div class="input-group shadow-sm">
                            <input type="text" class="form-control bg-light border-0 small" value="{{ route('public.raffle.show', $raffle->slug) }}" id="publicLink" readonly>
                            <button class="btn btn-primary px-3" onclick="copyLink()">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Performance -->
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <span class="badge bg-success-soft text-success rounded-pill p-2"><i class="bi bi-graph-up-arrow"></i></span>
                    Desempenho
                </h5>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Arrecadado</span>
                        <span class="fw-800 text-success">R$ {{ number_format($raffle->tickets->where('status', 'paid')->count() * $raffle->ticket_price, 2, ',', '.') }}</span>
                    </div>
                    <div class="progress rounded-pill shadow-inner" style="height: 12px; background: #f1f5f9;">
                        @php 
                            $percent = ($raffle->tickets->where('status', 'paid')->count() / $raffle->total_tickets) * 100;
                        @endphp
                        <div class="progress-bar rounded-pill bg-success shadow-sm" role="progressbar" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-success-soft">
                            <span class="d-block small text-muted fw-700 ls-1 mb-1">PAGOS</span>
                            <span class="fw-800 text-success">{{ $raffle->tickets->where('status', 'paid')->count() }}</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-warning-soft">
                            <span class="d-block small text-muted fw-700 ls-1 mb-1">PEND.</span>
                            <span class="fw-800 text-warning">{{ $raffle->tickets->where('status', 'pending')->count() }}</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded-3 bg-light">
                            <span class="d-block small text-muted fw-700 ls-1 mb-1">LIVRES</span>
                            <span class="fw-800">{{ $raffle->tickets->where('status', 'available')->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content (Ticket List) -->
    <div class="col-xl-8">
        @if($raffle->status === 'finished' && $raffle->winner)
        <div class="card border-0 shadow-lg mb-4 text-center overflow-hidden" style="border-radius: 20px; background: linear-gradient(135deg, #fff 0%, #fef3c7 100%); border: 2px solid #f59e0b !important;">
            <div class="card-body p-5">
                <div class="mb-3">
                    <i class="bi bi-trophy-fill text-warning" style="font-size: 3.5rem;"></i>
                </div>
                <h2 class="fw-900 text-warning mb-1">TEMOS UM VENCEDOR!</h2>
                <p class="text-muted mb-4">O sorteio foi finalizado e a sorte sorriu para:</p>
                <div class="d-inline-flex align-items-center gap-4 bg-white p-3 px-5 rounded-pill shadow-sm border border-warning-soft">
                    <span class="fs-2 fw-900 text-warning">#{{ str_pad($raffle->winner->number, 3, '0', STR_PAD_LEFT) }}</span>
                    <div class="text-start border-start ps-4">
                        <h4 class="mb-0 fw-800">{{ $raffle->winner->buyer_name }}</h4>
                        <span class="text-muted small"><i class="bi bi-whatsapp me-1"></i>{{ $raffle->winner->buyer_phone }}</span>
                    </div>
                </div>
            </div>
        </div>
        @elseif($raffle->status === 'active' && $raffle->tickets->where('status', 'paid')->count() > 0)
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white" style="border-radius: 20px; background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%) !important;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-magic me-2"></i>Realizar Sorteio</h5>
                    <p class="mb-0 opacity-75 small">Já existem bilhetes pagos. Você pode encerrar as vendas e sortear o ganhador.</p>
                </div>
                <form action="{{ route('raffles.draw', $raffle) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light fw-bold px-4 rounded-pill shadow-sm" onclick="return confirm('Tem certeza que deseja realizar o sorteio agora? Esta ação é irreversível.')">
                        SORTEAR AGORA
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Details Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <h6 class="small fw-800 text-muted text-uppercase mb-3 ls-1">Descrição e Prêmios</h6>
                        <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $raffle->description }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="small fw-800 text-muted text-uppercase mb-3 ls-1">Regras e Termos</h6>
                        <p class="mb-0 text-dark" style="white-space: pre-line;">{{ $raffle->rules ?: 'Nenhuma regra definida.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
            <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Controle de Bilhetes</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-soft text-success px-3 py-2 rounded-pill small fw-bold">PAGOS: {{ $raffle->tickets->where('status', 'paid')->count() }}</span>
                    <span class="badge bg-warning-soft text-warning px-3 py-2 rounded-pill small fw-bold">RESERVADOS: {{ $raffle->tickets->where('status', 'pending')->count() }}</span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small text-uppercase ls-1">
                            <th class="ps-4 py-3">Nº</th>
                            <th class="py-3">Comprador</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Data</th>
                            <th class="py-3 text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($raffle->tickets->whereIn('status', ['pending', 'paid'])->sortByDesc('updated_at') as $ticket)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-800 text-primary">#{{ str_pad($ticket->number, 3, '0', STR_PAD_LEFT) }}</span>
                            </td>
                            <td>
                                @if($ticket->buyer_name)
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold">{{ $ticket->buyer_name }}</span>
                                        <span class="small text-muted">{{ $ticket->buyer_phone }}</span>
                                    </div>
                                @else
                                    <span class="text-muted small italic">--</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $ticket->status == 'paid' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-1 shadow-sm">
                                    {{ $ticket->status == 'paid' ? 'PAGO' : 'PENDENTE' }}
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted">{{ $ticket->updated_at->diffForHumans() }}</span>
                            </td>
                            <td class="text-end pe-4">
                                @if($ticket->payment_receipt_path)
                                <a href="{{ Storage::url($ticket->payment_receipt_path) }}" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3 me-2 border-0 bg-light shadow-none">
                                    <i class="bi bi-paperclip me-1"></i> VER RECIBO
                                </a>
                                @endif
                                
                                @if($ticket->status == 'pending')
                                <div class="d-flex gap-2">
                                    <form action="{{ route('raffles.confirm-payment', $ticket->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm border-0" onclick="return confirm('Confirmar recebimento deste pagamento?')">
                                            <i class="bi bi-check-lg me-1"></i> CONFIRMAR
                                        </button>
                                    </form>

                                    <form action="{{ route('raffles.release-ticket', $ticket->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-none" onclick="return confirm('Deseja liberar este número? A reserva será cancelada e o comprador será notificado por e-mail.')">
                                            <i class="bi bi-x-lg me-1"></i> LIBERAR
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted opacity-50">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Nenhuma movimentação para esta rifa.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function copyLink() {
        const copyText = document.getElementById("publicLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        if (typeof Toastify === 'function') {
            Toastify({
                text: "Link copiado com sucesso!",
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: "linear-gradient(to right, #00b09b, #96c93d)",
                }
            }).showToast();
        } else {
            alert("Link copiado com sucesso!");
        }
    }
</script>

<style>
    .bg-primary-soft { background: rgba(67, 97, 238, 0.1); }
    .bg-success-soft { background: rgba(16, 185, 129, 0.1); }
    .bg-warning-soft { background: rgba(245, 158, 11, 0.1); }
    .ls-1 { letter-spacing: 1px; }
    .fw-800 { font-weight: 800; }
    .fw-900 { font-weight: 900; }
    .fw-700 { font-weight: 700; }
    .shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06); }
</style>
@endsection
