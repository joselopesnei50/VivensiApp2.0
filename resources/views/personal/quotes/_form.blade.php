@php
    $isEdit = isset($quote);
    $formUrl = $isEdit ? route('quotes.update', $quote) : route('quotes.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';
    $existingItems = $isEdit ? $quote->items->map(fn($i) => [
        'catalog_product_id' => $i->catalog_product_id,
        'name'               => $i->name,
        'description'        => $i->description,
        'quantity'           => (float) $i->quantity,
        'unit'               => $i->unit,
        'unit_price'         => (float) $i->unit_price,
    ])->all() : [];
    $preClient = $preselectedClient ?? null;
    $selectedClientId = old('client_id', $isEdit ? $quote->client_id : ($preClient?->id));
@endphp

<form action="{{ $formUrl }}" method="POST" id="quote-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if($errors->any())
    <div class="ds-alert ds-alert-danger" style="margin-bottom:20px;">
        <i class="fas fa-triangle-exclamation"></i>
        <ul style="margin:8px 0 0 0; padding-left:20px;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <label class="form-label" style="font-weight: 700; color: #475569;">Título do Orçamento *</label>
            <input type="text" name="title" required value="{{ old('title', $quote->title ?? '') }}" class="form-control" placeholder="Ex: Sprint de desenvolvimento — Setembro/2026" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Cliente</label>
            <select name="client_id" class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                <option value="">— Sem cliente (avulso) —</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected((int)$selectedClientId === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Data de Emissão *</label>
            <input type="date" name="issue_date" required value="{{ old('issue_date', $isEdit ? $quote->issue_date->toDateString() : now()->toDateString()) }}" class="form-control" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Válido até</label>
            <input type="date" name="valid_until" value="{{ old('valid_until', $isEdit && $quote->valid_until ? $quote->valid_until->toDateString() : now()->addDays(15)->toDateString()) }}" class="form-control" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Desconto (R$)</label>
            <input type="number" name="discount" min="0" step="0.01" value="{{ old('discount', $isEdit ? number_format((float)$quote->discount, 2, '.', '') : '0.00') }}" class="form-control" id="quote-discount" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" oninput="recalcTotal()">
        </div>
    </div>

    <div style="border-top: 1px solid #f1f5f9; padding-top: 24px; margin-bottom: 24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <h5 style="margin:0; color:#1e293b; font-weight:900; font-size:1.15rem;">Itens do Orçamento</h5>
            <div style="display:flex; gap:8px;">
                <select id="catalog-picker" class="form-select" style="border-radius:10px; padding:8px 12px; border-color:#cbd5e1; font-size:.85rem; max-width:280px;">
                    <option value="">Adicionar do catálogo…</option>
                    @foreach($catalog as $p)
                        <option value="{{ $p->id }}"
                                data-name="{{ $p->name }}"
                                data-price="{{ $p->unit_price }}"
                                data-unit="{{ $p->unit }}">
                            {{ $p->name }} — R$ {{ number_format((float)$p->unit_price, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <button type="button" onclick="addAdhocRow()" class="btn-premium" style="background:#10b981; color:white; border:none; padding:8px 14px; font-size:.8rem; font-weight:800;">
                    <i class="fas fa-plus"></i> Item avulso
                </button>
            </div>
        </div>

        <div id="items-container" style="display:flex; flex-direction:column; gap:12px;"></div>

        <div id="empty-msg" style="text-align:center; padding:30px; border:2px dashed #f1f5f9; border-radius:14px; color:#94a3b8; font-weight:600;">
            <i class="fas fa-inbox" style="font-size:1.5rem; color:#cbd5e1; display:block; margin-bottom:8px;"></i>
            Nenhum item ainda. Selecione um do catálogo ou adicione um avulso.
        </div>
    </div>

    <div style="background:#f8fafc; border-radius:14px; padding:20px 24px; margin-bottom:24px; display:flex; flex-direction:column; gap:8px;">
        <div style="display:flex; justify-content:space-between; font-size:.9rem; color:#64748b; font-weight:600;">
            <span>Subtotal (itens)</span>
            <span id="display-subtotal">R$ 0,00</span>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:.9rem; color:#64748b; font-weight:600;">
            <span>Desconto</span>
            <span id="display-discount">- R$ 0,00</span>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:1.4rem; color:#065f46; font-weight:900; padding-top:8px; border-top:1px solid #e2e8f0; margin-top:4px;">
            <span>TOTAL</span>
            <span id="display-total">R$ 0,00</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" style="font-weight: 700; color: #475569;">Observações (visíveis no PDF)</label>
            <textarea name="notes" rows="3" class="form-control" placeholder="Notas para o cliente..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('notes', $quote->notes ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label" style="font-weight: 700; color: #475569;">Termos e Condições</label>
            <textarea name="terms" rows="3" class="form-control" placeholder="Ex: Pagamento em 15 dias, entrega em 30 dias..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('terms', $quote->terms ?? '') }}</textarea>
        </div>
    </div>

    <div class="text-end" style="border-top:1px solid #f1f5f9; padding-top:20px;">
        <a href="{{ $isEdit ? route('quotes.show', $quote) : route('quotes.index') }}" class="btn-premium" style="background:#f1f5f9; color:#475569; border:none; margin-right:10px;">Cancelar</a>
        <button type="submit" class="btn-premium" style="background:{{ $isEdit ? '#4f46e5' : '#10b981' }}; color: white; border: none; font-weight: 800; padding: 12px 30px; font-size: 1.05rem;">
            <i class="fas fa-{{ $isEdit ? 'save' : 'check-circle' }} me-2"></i> {{ $isEdit ? 'Salvar Alterações' : 'Criar Orçamento' }}
        </button>
    </div>
</form>

<template id="row-template">
    <div class="quote-row" style="background:white; border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px;">
        <div style="display:flex; gap:10px; align-items:start;">
            <div style="flex:2;">
                <input type="hidden" data-field="catalog_product_id" name="items[__i__][catalog_product_id]">
                <input type="text" data-field="name" name="items[__i__][name]" required placeholder="Nome do item" style="width:100%; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; font-weight:700; color:#1e293b;">
                <textarea data-field="description" name="items[__i__][description]" rows="1" placeholder="Descrição opcional…" style="width:100%; margin-top:6px; padding:6px 10px; border:1px solid #f1f5f9; border-radius:8px; font-size:.82rem; color:#64748b;"></textarea>
            </div>
            <div style="flex:0 0 90px;">
                <label style="font-size:.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase;">Qtd</label>
                <input type="number" data-field="quantity" name="items[__i__][quantity]" required min="0.001" step="0.001" value="1" style="width:100%; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; text-align:right;" oninput="recalcRow(this)">
            </div>
            <div style="flex:0 0 80px;">
                <label style="font-size:.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase;">Unid</label>
                <select data-field="unit" name="items[__i__][unit]" style="width:100%; padding:8px 6px; border:1px solid #e2e8f0; border-radius:8px; font-size:.82rem;">
                    @foreach(\App\Models\CatalogProduct::COMMON_UNITS as $key => $label)
                        <option value="{{ $key }}">{{ $key }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:0 0 130px;">
                <label style="font-size:.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase;">Valor (R$)</label>
                <input type="number" data-field="unit_price" name="items[__i__][unit_price]" required min="0" step="0.01" value="0.00" style="width:100%; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; text-align:right;" oninput="recalcRow(this)">
            </div>
            <div style="flex:0 0 130px; text-align:right;">
                <label style="font-size:.65rem; color:#94a3b8; font-weight:800; text-transform:uppercase;">Subtotal</label>
                <div data-field="subtotal-display" style="padding:9px 0; font-weight:900; color:#059669;">R$ 0,00</div>
            </div>
            <div style="flex:0 0 auto; padding-top:20px;">
                <button type="button" onclick="removeRow(this)" style="background:#fee2e2; color:#991b1b; border:none; border-radius:8px; padding:8px 10px; cursor:pointer;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    const existingItems = @json($existingItems);
    let rowIndex = 0;

    function fmt(n) {
        return 'R$ ' + Number(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function addRow(payload) {
        const tpl   = document.getElementById('row-template').innerHTML.replace(/__i__/g, rowIndex);
        const wrap  = document.createElement('div');
        wrap.innerHTML = tpl.trim();
        const row = wrap.firstChild;
        document.getElementById('items-container').appendChild(row);
        if (payload) {
            row.querySelector('[data-field=catalog_product_id]').value = payload.catalog_product_id || '';
            row.querySelector('[data-field=name]').value                = payload.name || '';
            row.querySelector('[data-field=description]').value         = payload.description || '';
            row.querySelector('[data-field=quantity]').value            = payload.quantity ?? 1;
            row.querySelector('[data-field=unit_price]').value          = Number(payload.unit_price ?? 0).toFixed(2);
            const unitSel = row.querySelector('[data-field=unit]');
            if (payload.unit) {
                const opt = Array.from(unitSel.options).find(o => o.value === payload.unit);
                if (opt) opt.selected = true;
            }
        }
        rowIndex++;
        recalcRow(row.querySelector('[data-field=quantity]'));
        document.getElementById('empty-msg').style.display = 'none';
    }

    function addAdhocRow() {
        addRow({ name: '', quantity: 1, unit_price: 0, unit: 'un' });
    }

    function removeRow(btn) {
        const row = btn.closest('.quote-row');
        row.remove();
        if (!document.querySelectorAll('.quote-row').length) {
            document.getElementById('empty-msg').style.display = '';
        }
        recalcTotal();
    }

    function recalcRow(input) {
        const row = input.closest('.quote-row');
        const q = parseFloat(row.querySelector('[data-field=quantity]').value) || 0;
        const p = parseFloat(row.querySelector('[data-field=unit_price]').value) || 0;
        row.querySelector('[data-field=subtotal-display]').textContent = fmt(q * p);
        recalcTotal();
    }

    function recalcTotal() {
        let subtotal = 0;
        document.querySelectorAll('.quote-row').forEach(r => {
            const q = parseFloat(r.querySelector('[data-field=quantity]').value) || 0;
            const p = parseFloat(r.querySelector('[data-field=unit_price]').value) || 0;
            subtotal += q * p;
        });
        const discount = parseFloat(document.getElementById('quote-discount').value) || 0;
        const total    = Math.max(0, subtotal - discount);
        document.getElementById('display-subtotal').textContent = fmt(subtotal);
        document.getElementById('display-discount').textContent = '- ' + fmt(discount);
        document.getElementById('display-total').textContent    = fmt(total);
    }

    document.getElementById('catalog-picker').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) return;
        addRow({
            catalog_product_id: opt.value,
            name:               opt.dataset.name,
            unit_price:         opt.dataset.price,
            unit:               opt.dataset.unit,
            quantity:           1,
        });
        this.value = '';
    });

    // Bootstrap: itens existentes (edit) ou nada
    (function init() {
        if (existingItems.length) {
            existingItems.forEach(addRow);
        }
        recalcTotal();
    })();
</script>
