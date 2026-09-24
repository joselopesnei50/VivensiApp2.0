@php
    $isEdit    = isset($item);
    $data      = $isEdit ? $item : null;
    $formUrl   = $isEdit ? route('catalog.update', $item) : route('catalog.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';
@endphp

<form action="{{ $formUrl }}" method="POST">
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
            <label class="form-label" style="font-weight: 700; color: #475569;">Nome do Item *</label>
            <input type="text" name="name" required value="{{ old('name', $data->name ?? '') }}" class="form-control" placeholder="Ex: Consultoria Estratégica" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Tipo *</label>
            <select name="type" required class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;" onchange="toggleStock(this.value)">
                @foreach(\App\Models\CatalogProduct::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected($key === old('type', $data->type ?? 'service'))>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label" style="font-weight: 700; color: #475569;">SKU (opcional)</label>
            <input type="text" name="sku" value="{{ old('sku', $data->sku ?? '') }}" class="form-control" placeholder="Ex: SVC-001" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Categoria</label>
            <input list="categories-list" type="text" name="category" value="{{ old('category', $data->category ?? '') }}" class="form-control" placeholder="Ex: Consultoria, Design..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
            <datalist id="categories-list">
                @foreach($categories ?? [] as $cat)<option value="{{ $cat }}">@endforeach
            </datalist>
        </div>
        <div class="col-md-3">
            <label class="form-label" style="font-weight: 700; color: #475569;">Preço unitário (R$) *</label>
            <input type="number" name="unit_price" required min="0" step="0.01" value="{{ old('unit_price', $data ? number_format((float) $data->unit_price, 2, '.', '') : '0.00') }}" class="form-control" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-2">
            <label class="form-label" style="font-weight: 700; color: #475569;">Unidade</label>
            <select name="unit" class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                @foreach(\App\Models\CatalogProduct::COMMON_UNITS as $key => $label)
                    <option value="{{ $key }}" @selected($key === old('unit', $data->unit ?? 'un'))>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" style="font-weight: 700; color: #475569;">Descrição</label>
        <textarea name="description" rows="3" class="form-control" placeholder="Detalhes que ajudam você a lembrar do escopo, entrega, condições..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('description', $data->description ?? '') }}</textarea>
    </div>

    <div id="stock-block" style="background:#fef3c7; border:1px solid #fde68a; border-radius:14px; padding:16px 20px; margin-bottom:24px; display: {{ old('type', $data->type ?? 'service') === 'product' ? 'block' : 'none' }};">
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:800; color:#92400e; margin-bottom:12px;">
            <input type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $data->track_stock ?? false))>
            Controlar estoque deste produto
        </label>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" style="font-weight: 700; color: #78350f;">Quantidade em estoque</label>
                <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $data->stock_quantity ?? '') }}" class="form-control" placeholder="0" style="border-radius: 12px; padding: 10px 12px; border-color: #fde68a;">
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:700; color:#475569;">
            <input type="checkbox" name="active" value="1" @checked(old('active', $data->active ?? true))>
            Item ativo (aparece pra selecionar em orçamentos e recibos)
        </label>
    </div>

    <div class="text-end" style="border-top:1px solid #f1f5f9; padding-top:20px;">
        <a href="{{ route('catalog.index') }}" class="btn-premium" style="background:#f1f5f9; color:#475569; border:none; margin-right:10px;">Cancelar</a>
        <button type="submit" class="btn-premium" style="background:{{ $isEdit ? '#4f46e5' : '#10b981' }}; color: white; border: none; font-weight: 800; padding: 12px 30px; font-size: 1.05rem;">
            <i class="fas fa-{{ $isEdit ? 'save' : 'check-circle' }} me-2"></i> {{ $isEdit ? 'Salvar Alterações' : 'Cadastrar Item' }}
        </button>
    </div>
</form>

<script>
    function toggleStock(v) {
        document.getElementById('stock-block').style.display = v === 'product' ? 'block' : 'none';
    }
</script>
