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
                            <span class="text-success">R$ {{ number_format($plan->price, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Formulário de Pagamento --}}
                <div class="col-md-7 p-5 bg-white">
                    <h3 class="fw-bold mb-1" style="color: #1e293b;">Finalizar Assinatura</h3>
                    <p class="text-muted small mb-4">O PIX abre em uma nova aba, no checkout seguro da AbacatePay.</p>

                    @if(session('error'))
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            {{ session('error') }}
                        </div>
                    @elseif($errors->any())
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    @if($pendingPayment ?? false)
                        <div class="alert alert-info border-0 rounded-4 mb-4" id="pixPending">
                            <strong>Já pagou o PIX?</strong> Estamos conferindo automaticamente.
                            <span id="pixPendingMsg">Assim que a AbacatePay confirmar, você será levado ao painel.</span>
                            <button type="button" class="btn btn-sm btn-primary ms-2 mt-2 mt-md-0" id="pixCheckBtn">Verificar agora</button>
                        </div>
                        <script>
                        (function () {
                            var busy = false, tries = 0;
                            function check(manual) {
                                if (busy) return; busy = true; tries++;
                                fetch(@json(route('checkout.status')), {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
                                    .then(function (r) { return r.ok ? r.json() : null; })
                                    .then(function (d) {
                                        busy = false;
                                        if (d && d.active) { window.location.href = d.redirect || '/dashboard'; return; }
                                        if (manual) document.getElementById('pixPendingMsg').textContent = 'Ainda não confirmado. Se pagou agora, aguarde alguns segundos.';
                                        if (tries < 36) setTimeout(function () { check(false); }, 5000);
                                    })
                                    .catch(function () { busy = false; });
                            }
                            document.getElementById('pixCheckBtn').addEventListener('click', function () { check(true); });
                            setTimeout(function () { check(false); }, 1500);
                        })();
                        </script>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Mostrado quando o PIX abre em outra aba: esta aba confere o pagamento e entra no painel sozinha. --}}
                    <div id="payWaiting" class="text-center py-4" style="display:none" aria-live="polite">
                        <div class="spinner-border text-success mb-3" role="status" aria-hidden="true"></div>
                        <h5 class="fw-bold mb-2" id="payWaitingTitle">Pague o PIX na aba que abriu</h5>
                        <p class="text-muted mb-4" id="payWaitingText">Pode deixar esta página aberta. Assim que o pagamento for confirmado, ela entra no painel sozinha.</p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <button type="button" class="btn btn-dark rounded-pill px-4" id="payCheckNow">Já paguei, verificar</button>
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="payReopen">Abrir o PIX de novo</button>
                        </div>
                    </div>

                    <form action="{{ route('checkout.process') }}" method="POST" id="checkoutForm">
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
                                   value="{{ old('document', $tenant->document) }}" placeholder="000.000.000-00" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Forma de Pagamento</label>
                            <div class="d-flex gap-3">
                                <label class="payment-method-card flex-fill text-center p-3 border rounded-3 cursor-pointer active" id="label-pix" style="border-color:#10b981; background:#ecfdf5;">
                                    <input type="radio" name="payment_method" value="PIX" class="d-none" checked>
                                    <i class="fas fa-qrcode fs-4 text-success d-block mb-1"></i>
                                    <span class="fw-bold small">PIX</span>
                                    <div class="text-muted" style="font-size:.7rem;">Instantâneo</div>
                                </label>
                            </div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-info-circle"></i> Cartão de crédito em breve.
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark w-100 py-3 rounded-pill fw-bold fs-5 shadow-lg d-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-lock"></i>
                                Assinar agora
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
<script>
(function () {
    var form = document.getElementById('checkoutForm');
    if (!form) return;
    var waiting = document.getElementById('payWaiting');
    var statusUrl = @json(route('checkout.status'));
    var payTab = null, timer = null, busy = false, started = 0;

    function check(manual) {
        if (busy) return; busy = true;
        fetch(statusUrl, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                busy = false;
                if (d && d.active) {
                    document.getElementById('payWaitingTitle').textContent = 'Pagamento confirmado!';
                    document.getElementById('payWaitingText').textContent = 'Acesso liberado. Abrindo o painel…';
                    try { if (payTab && !payTab.closed) payTab.close(); } catch (e) {}
                    window.location.href = d.redirect || '/dashboard';
                    return;
                }
                if (manual) document.getElementById('payWaitingText').textContent = 'Ainda não confirmado. Se você acabou de pagar, aguarde alguns segundos — a conferência continua sozinha.';
            })
            .catch(function () { busy = false; });
    }

    function startWaiting() {
        form.style.display = 'none';
        var pend = document.getElementById('pixPending'); if (pend) pend.style.display = 'none';
        waiting.style.display = 'block';
        started = Date.now();
        clearInterval(timer);
        timer = setInterval(function () {
            if (Date.now() - started > 30 * 60 * 1000) { clearInterval(timer); return; } // 30 min
            check(false);
        }, 4000);
    }

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) return; // deixa o navegador mostrar o erro
        var doc = (form.querySelector('[name=document]').value || '').replace(/\D/g, '');
        if (doc.length !== 11 && doc.length !== 14) return; // o servidor devolve a mensagem certa na mesma aba
        payTab = window.open('', 'vivensi_pix');
        if (!payTab) return; // pop-up bloqueado: segue na mesma aba, como antes
        e.preventDefault();
        form.target = 'vivensi_pix';
        form.submit();
        form.removeAttribute('target');
        startWaiting();
    });

    document.getElementById('payCheckNow').addEventListener('click', function () { check(true); });
    document.getElementById('payReopen').addEventListener('click', function () {
        if (payTab && !payTab.closed) { payTab.focus(); return; }
        waiting.style.display = 'none'; form.style.display = 'block'; clearInterval(timer);
    });
    // Voltou para esta aba depois de pagar: confere na hora.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && waiting.style.display === 'block') check(false);
    });
})();
</script>
@endsection
