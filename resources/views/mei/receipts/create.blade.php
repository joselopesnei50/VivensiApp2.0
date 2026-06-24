@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 24px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Recibos / MEI</h6>
    </div>
    <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.4rem; letter-spacing:-1px;">Novo recibo</h2>
    <p style="color:#64748b; margin-top:8px;">Após gerar, você recebe um link público assinado pra enviar ao cliente (WhatsApp, e-mail). O valor entra como receita do mês e conta no Termômetro do Teto MEI.</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0; padding-left:18px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="vivensi-card" style="padding:30px; max-width: 720px;">
    <form action="{{ route('personal.receipts.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Cliente cadastrado <span style="color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:0;">(opcional)</span></label>
            <select name="client_id" class="form-select form-select-lg rounded-3" id="client_select">
                <option value="">— Sem cliente (recibo avulso) —</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}@if($c->document) — {{ $c->document }}@endif
                    </option>
                @endforeach
            </select>
            <small style="color:#94a3b8;">Não cadastrado? Use os campos abaixo pra emitir recibo avulso.</small>
        </div>

        <div id="block_avulso">
            <div class="row g-3 mb-3">
                <div class="col-md-7">
                    <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Nome do destinatário <span style="color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:0;">(avulso)</span></label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-control form-control-lg rounded-3" placeholder="Ex: Maria Silva">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">CPF/CNPJ <span style="color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:0;">(opcional)</span></label>
                    <input type="text" name="recipient_document" value="{{ old('recipient_document') }}" class="form-control form-control-lg rounded-3" placeholder="000.000.000-00">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Descrição</label>
            <input type="text" name="description" value="{{ old('description') }}" class="form-control form-control-lg rounded-3" placeholder="Ex: Serviço de design gráfico — junho/2026" maxlength="255">
            <small style="color:#94a3b8;">Se em branco, usamos "Recibo — [nome do cliente]" ou "Recibo de prestação de serviço/venda".</small>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Valor</label>
                <input type="text" name="amount" value="{{ old('amount') }}" required class="form-control form-control-lg rounded-3" placeholder="0,00">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Data</label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="form-control form-control-lg rounded-3">
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-premium" style="padding:14px 28px;">
                <i class="fas fa-file-invoice me-2"></i> Gerar recibo
            </button>
            <a href="{{ route('personal.receipts.index') }}" class="btn btn-outline-secondary rounded-3" style="padding:14px 22px;">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Esconde bloco avulso quando seleciona cliente cadastrado
(function () {
    const sel = document.getElementById('client_select');
    const blk = document.getElementById('block_avulso');
    const sync = () => blk.style.display = sel.value ? 'none' : 'block';
    sel.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
