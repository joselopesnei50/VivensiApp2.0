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
            
            @if(isset($paymentData))
                <div class="alert alert-info border-0 rounded-4 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file-invoice-dollar fa-2x me-3"></i>
                        <div>
                            <h5 class="fw-bold mb-1">Fatura #{{ $paymentData['invoiceNumber'] ?? 'N/A' }}</h5>
                            <p class="mb-0 small">Vencimento: {{ isset($paymentData['dueDate']) ? \Carbon\Carbon::parse($paymentData['dueDate'])->format('d/m/Y') : 'N/A' }}</p>
                        </div>
                        <div class="ms-auto fs-4 fw-800">
                            R$ {{ number_format($paymentData['value'] ?? 0, 2, ',', '.') }}
                        </div>
                    </div>
                </div>

                @if($paymentData['billingType'] === 'PIX' && isset($paymentData['pix']))
                    <div class="bg-white p-4 rounded-4 border mb-4 text-center">
                        <h5 class="fw-bold mb-3" style="color: #1e293b;"><i class="fas fa-qrcode me-2 text-primary"></i> Pagamento via Pix</h5>
                        <p class="text-muted small mb-4">Abra o app do seu banco e escaneie o QR Code abaixo ou use o "Pix Copia e Cola".</p>
                        
                        @if(isset($paymentData['pix']['encodedImage']))
                            <img src="data:image/png;base64,{{ $paymentData['pix']['encodedImage'] }}" alt="QR Code Pix" class="img-fluid mb-4 rounded border p-2" style="max-width: 200px;">
                        @endif

                        <div class="form-group text-start">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Pix Copia e Cola</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-light border-0 py-2" id="pixCopyPaste" value="{{ $paymentData['pix']['payload'] ?? '' }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyPix()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif($paymentData['billingType'] === 'BOLETO' && isset($paymentData['boleto']))
                    <div class="bg-white p-4 rounded-4 border mb-4 text-center">
                        <h5 class="fw-bold mb-3" style="color: #1e293b;"><i class="fas fa-barcode me-2 text-secondary"></i> Pagamento via Boleto</h5>
                        <p class="text-muted small mb-4">Utilize a linha digitável abaixo para pagar no seu banco.</p>
                        
                        <div class="form-group text-start">
                            <label class="small fw-bold text-muted text-uppercase mb-1">Linha Digitável</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control bg-light border-0 py-2 font-monospace" id="boletoCode" value="{{ $paymentData['boleto']['identificationField'] ?? '' }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyBoleto()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <a href="{{ $paymentData['bankSlipUrl'] ?? '#' }}" target="_blank" class="btn btn-outline-dark w-100 py-2 rounded-pill">
                            <i class="fas fa-print me-2"></i> Visualizar/Imprimir Boleto
                        </a>
                    </div>
                @elseif($paymentData['billingType'] === 'CREDIT_CARD')
                     <div class="bg-white p-4 rounded-4 border mb-4 text-center">
                        <h5 class="fw-bold mb-3" style="color: #1e293b;"><i class="fas fa-credit-card me-2 text-success"></i> Cartão de Crédito</h5>
                        <p class="text-muted small mb-4">Para sua segurança, finalize o pagamento digitando os dados do cartão no ambiente seguro do Asaas.</p>
                        
                        <a href="{{ $paymentData['invoiceUrl'] ?? '#' }}" target="_blank" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-lock me-2"></i> Pagar Fatura Agora
                        </a>
                    </div>
                @endif
            @else
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
            @endif

            <div class="d-grid gap-3">
                <a href="{{ url('/dashboard') }}" class="btn btn-dark py-3 rounded-pill fw-bold">Ir para Meus Projetos</a>
                <a href="{{ url('/') }}" class="btn btn-link text-muted text-decoration-none small">Precisa de ajuda? Fale com o suporte</a>
            </div>
        </div>
    </div>
</div>

<script>
    function copyPix() {
        var copyText = document.getElementById("pixCopyPaste");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("Código Pix copiado!");
    }
    
    function copyBoleto() {
        var copyText = document.getElementById("boletoCode");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("Linha digitável copiada!");
    }
</script>


<style>
    .fw-800 { font-weight: 800; }
</style>
@endsection
