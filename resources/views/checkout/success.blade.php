@extends('layouts.checkout')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-lg-6">
        <div class="vivensi-card text-center py-5 px-4 shadow-lg border-0 rounded-5">
            <div class="mb-4">
                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fas fa-check fa-2x"></i>
                </div>
            </div>
            
            <h2 class="fw-800 mb-3" style="color: #1e293b;">Assinatura Solicitada!</h2>
            <p class="text-muted fs-5 mb-5">Quase lá! Sua fatura foi gerada e enviada para o seu e-mail. Para liberar seu acesso instantaneamente, realize o pagamento agora.</p>

            <div class="bg-light p-4 rounded-4 mb-5 text-start border">
                <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i> Instruções de Ativação</h5>
                <ul class="small text-muted ps-3">
                    <li class="mb-2">Acesse seu e-mail para visualizar o link de pagamento do Asaas.</li>
                    <li class="mb-2">Pagamentos via <strong>Pix</strong> são confirmados em poucos segundos.</li>
                    <li class="mb-2">Boletos podem levar até 2 dias úteis para compensação.</li>
                    <li>Assim que confirmado, seu Painel Vivensi será liberado automaticamente.</li>
                </ul>
            </div>

            <div class="d-grid gap-3">
                <a href="{{ url('/dashboard') }}" class="btn btn-dark py-3 rounded-pill fw-bold">Ir para Meus Projetos</a>
                <a href="{{ url('/') }}" class="btn btn-link text-muted text-decoration-none small">Precisa de ajuda? Fale com o suporte</a>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-800 { font-weight: 800; }
</style>
@endsection
