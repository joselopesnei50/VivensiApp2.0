@extends('layouts.checkout')

@push('scripts')
    {{-- Meta Pixel: evento Purchase. Value real do plano quando disponivel; --}}
    {{-- content_ids/content_name pra otimizacao de campanha por catalogo Meta. --}}
    <x-meta-pixel-event
        event="Purchase"
        :value="$plan?->price"
        currency="BRL"
        :params="$plan ? ['content_ids' => [(string) $plan->id], 'content_name' => $plan->name, 'content_type' => 'product'] : []"
    />
@endpush

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-lg-5">
        <div class="vivensi-card text-center py-5 px-4 shadow-lg border-0 rounded-5">

            <div class="mb-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                    <i class="fas fa-check fa-2x"></i>
                </div>
            </div>

            <h2 class="fw-bold mb-2" style="color:#1e293b;">Pagamento Confirmado!</h2>
            <p class="text-muted mb-4">
                Seu pagamento foi recebido com sucesso. Seu acesso será ativado automaticamente em instantes.
            </p>

            <div class="bg-light rounded-4 p-4 mb-4 text-start border">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i> O que acontece agora?</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-2">Pagamentos via <strong>PIX</strong> são confirmados em segundos.</li>
                    <li class="mb-2">Após confirmação, seu painel Vivensi será <strong>liberado automaticamente</strong>.</li>
                    <li>Você receberá um e-mail de confirmação em breve.</li>
                </ul>
            </div>

            <div class="d-grid gap-3">
                <a href="{{ url('/dashboard') }}" class="btn btn-dark py-3 rounded-pill fw-bold">
                    <i class="fas fa-rocket me-2"></i> Ir para o Painel
                </a>
                <a href="{{ url('/') }}" class="btn btn-link text-muted text-decoration-none small">
                    Precisa de ajuda? Fale com o suporte
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
