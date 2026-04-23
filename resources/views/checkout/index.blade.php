@extends('layouts.checkout')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-lg-10">
        <div class="vivensi-card p-0 overflow-hidden" style="border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.08);">
            <div class="row g-0">
                {{-- Coluna Resumo do Plano --}}
                <div class="col-md-5 text-white p-5 d-flex flex-column" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
                    <div class="mb-auto">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="badge rounded-pill px-3 py-2 fw-bold" style="background: rgba(255,255,255,0.15);">
                                Plano Selecionado
                            </span>
                            <span class="badge bg-success rounded-pill px-3 py-2 fw-bold">
                                <i class="fas fa-gift me-1"></i> 7 dias grátis
                            </span>
                        </div>

                        <h2 class="display-5 fw-800 mb-4">{{ $plan->name }}</h2>

                        <div class="features-list mb-5">
                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                    <div class="d-flex align-items-center mb-3" style="opacity:.85;">
                                        <i class="fas fa-check-circle me-3 text-green-400"></i>
                                        <span>{{ $feature }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="border-top pt-4" style="border-color:rgba(255,255,255,0.2)!important;">
                        <div class="d-flex justify-content-between align-items-center mb-1 opacity-75">
                            <span>Valor mensal</span>
                            <span>R$ {{ number_format($plan->price, 2, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center fs-4 fw-800 mt-2">
                            <span>Hoje você paga</span>
                            <span class="text-success">R$ 0,00</span>
                        </div>
                        <p class="small mt-2 mb-0" style="opacity:.6;">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Primeira cobrança em 7 dias (R$ {{ number_format($plan->price, 2, ',', '.') }}/mês).
                        </p>
                    </div>
                </div>

                {{-- Formulário de Pagamento --}}
                <div class="col-md-7 p-5 bg-white">
                    <h3 class="fw-bold mb-1" style="color: #1e293b;">Finalizar Assinatura</h3>
                    <p class="text-muted small mb-4">Você será redirecionado para o checkout seguro da AbacatePay.</p>

                    @if(session('error'))
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('checkout.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="gateway" value="abacatepay">

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Organização / Nome</label>
                            <input type="text" class="form-control border-0 bg-light py-3 rounded-4"
                                   value="{{ $tenant->name }}" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">CPF ou CNPJ para Faturamento</label>
                            <input type="text" name="document" class="form-control border-0 bg-light py-3 rounded-4"
                                   value="{{ $tenant->document }}" placeholder="000.000.000-00" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Forma de Pagamento</label>
                            <div class="d-flex gap-3">
                                <label class="payment-method-card flex-fill text-center p-3 border rounded-3 cursor-pointer" id="label-pix">
                                    <input type="radio" name="payment_method" value="PIX" class="d-none" checked>
                                    <i class="fas fa-qrcode fs-4 text-success d-block mb-1"></i>
                                    <span class="fw-bold small">PIX</span>
                                    <div class="text-muted" style="font-size:.7rem;">Instantâneo</div>
                                </label>
                                <label class="payment-method-card flex-fill text-center p-3 border rounded-3 cursor-pointer" id="label-card">
                                    <input type="radio" name="payment_method" value="CARD" class="d-none">
                                    <i class="fas fa-credit-card fs-4 text-primary d-block mb-1"></i>
                                    <span class="fw-bold small">Cartão</span>
                                    <div class="text-muted" style="font-size:.7rem;">Crédito/Débito</div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold fs-5 shadow-lg d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-lock"></i>
                                Iniciar período grátis
                            </button>
                            <p class="text-center text-muted small mt-3 mb-0">
                                <i class="fas fa-shield-alt me-1 text-success"></i>
                                Pagamento seguro via <strong>AbacatePay</strong> · Cancele quando quiser
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
    .cursor-pointer { cursor: pointer; }
    .payment-method-card {
        cursor: pointer;
        transition: all .2s;
        border-color: #e2e8f0 !important;
    }
    .payment-method-card:has(input:checked) {
        border-color: #4f46e5 !important;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(79,70,229,.12);
    }
    .payment-method-card:hover {
        border-color: #c7d2fe !important;
        background: #f8faff;
    }
</style>
@endsection
