@extends('layouts.checkout')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-lg-10">
        <div class="vivensi-card p-0 overflow-hidden" style="border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.08);">
            <div class="row g-0">
                <!-- Coluna Resumo do Plano -->
                <div class="col-md-5 bg-dark text-white p-5 d-flex flex-column">
                    <div class="mb-auto">
                        <span class="badge bg-primary mb-3 px-3 py-2 rounded-pill fw-bold" style="background: rgba(255,255,255,0.1) !important;">
                            Plano Selecionado
                        </span>
                        <h2 class="display-5 fw-800 mb-4">{{ $plan->name }}</h2>
                        
                        <div class="features-list mb-5">
                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                    <div class="d-flex align-items-center mb-3 opacity-75">
                                        <i class="fas fa-check-circle me-3 text-success"></i>
                                        <span>{{ $feature }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="border-top border-secondary pt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="opacity-75">Subtotal</span>
                            <span>R$ {{ number_format($plan->price, 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center fs-4 fw-800">
                            <span>Total</span>
                            <span class="text-primary">R$ {{ number_format($plan->price, 2, ',', '.') }}</span>
                        </div>
                        <p class="small opacity-50 mt-2">Próximo vencimento em 3 dias após a confirmação.</p>
                    </div>
                </div>

                <!-- Formulário de Pagamento -->
                <div class="col-md-7 p-5 bg-white">
                    <h3 class="fw-bold mb-4" style="color: #1e293b;">Finalizar Assinatura</h3>
                    
                    @if(session('error'))
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('checkout.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Organização / Nome Completo</label>
                                <input type="text" class="form-control border-0 bg-light py-3 rounded-4" value="{{ $tenant->name }}" readonly>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">CPF ou CNPJ para Faturamento</label>
                                <input type="text" name="document" class="form-control border-0 bg-light py-3 rounded-4" value="{{ $tenant->document }}" placeholder="00.000.000/0000-00" required>
                                <small class="text-muted">Necessário para emitir a fatura no Asaas.</small>
                            </div>

                            <div class="col-md-12 mb-4">
                                <div class="alert alert-info border-0 rounded-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Você será redirecionado para o ambiente seguro do <strong>PagSeguro</strong> para escolher a forma de pagamento (Pix, Cartão ou Boleto).
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold fs-5 shadow-lg">
                                Confirmar e Gerar Fatura
                            </button>
                            <p class="text-center text-muted small mt-3">
                                <i class="fas fa-shield-alt me-1"></i> Pagamento processado com segurança via Asaas API V3.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-800 { font-weight: 800; }
    .btn-check:checked + .btn {
        border-color: #6366f1 !important;
        background: #f5f3ff !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
</style>
@endsection
