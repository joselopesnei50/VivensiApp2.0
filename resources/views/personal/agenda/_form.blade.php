@php
    $isEdit     = isset($item);
    $data       = $isEdit ? $item : null;
    $formUrl    = $isEdit ? route('agenda.update', $item) : route('agenda.store');
    $startsOn   = old('starts_on', $data?->starts_on?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $selectedKind = old('kind', $data->kind ?? 'meeting');
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
            <label class="form-label" style="font-weight: 700; color: #475569;">Título do compromisso *</label>
            <input type="text" name="title" required maxlength="200" value="{{ old('title', $data->title ?? '') }}" class="form-control" placeholder="Ex: Reunião com ACME sobre proposta" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Tipo *</label>
            <select name="kind" required class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                @foreach(\App\Models\AgendaEvent::KINDS as $key => $label)
                    <option value="{{ $key }}" @selected($key === $selectedKind)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label" style="font-weight: 700; color: #475569;">Data *</label>
            <input type="date" name="starts_on" required value="{{ $startsOn }}" class="form-control" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-3">
            <label class="form-label" style="font-weight: 700; color: #475569;">Início</label>
            <input type="time" name="starts_at" value="{{ old('starts_at', $data->starts_at ?? '') }}" class="form-control" id="starts_at_input" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-3">
            <label class="form-label" style="font-weight: 700; color: #475569;">Término</label>
            <input type="time" name="ends_at" value="{{ old('ends_at', $data->ends_at ?? '') }}" class="form-control" id="ends_at_input" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; color:#475569;">
                <input type="checkbox" name="all_day" value="1" id="all_day_input" @checked(old('all_day', $data->all_day ?? false)) onchange="toggleTimes()">
                Dia inteiro
            </label>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" style="font-weight: 700; color: #475569;">Cliente vinculado (opcional)</label>
            <select name="client_id" class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
                <option value="">— Sem cliente —</option>
                @foreach($clients ?? [] as $client)
                    <option value="{{ $client->id }}" @selected((string) old('client_id', $data->client_id ?? '') === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" style="font-weight: 700; color: #475569;">Local (opcional)</label>
            <input type="text" name="location" maxlength="200" value="{{ old('location', $data->location ?? '') }}" class="form-control" placeholder="Ex: Escritório do cliente, Google Meet, WhatsApp..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" style="font-weight: 700; color: #475569;">Descrição / notas</label>
        <textarea name="description" rows="4" class="form-control" placeholder="Pauta da reunião, o que precisa levar, decisões pendentes..." style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1;">{{ old('description', $data->description ?? '') }}</textarea>
    </div>

    @if($isEdit)
    <div class="mb-4">
        <label class="form-label" style="font-weight: 700; color: #475569;">Status</label>
        <select name="status" class="form-select" style="border-radius: 12px; padding: 12px 15px; border-color: #cbd5e1; max-width:280px;">
            @foreach(\App\Models\AgendaEvent::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($key === old('status', $data->status))>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="text-end" style="border-top:1px solid #f1f5f9; padding-top:20px;">
        <a href="{{ route('agenda.index') }}" class="btn-premium" style="background:#f1f5f9; color:#475569; border:none; margin-right:10px;">Cancelar</a>
        <button type="submit" class="btn-premium" style="background:{{ $isEdit ? '#4f46e5' : '#10b981' }}; color: white; border: none; font-weight: 800; padding: 12px 30px; font-size: 1.05rem;">
            <i class="fas fa-{{ $isEdit ? 'save' : 'check-circle' }} me-2"></i> {{ $isEdit ? 'Salvar Alterações' : 'Cadastrar Compromisso' }}
        </button>
    </div>
</form>

<script>
    function toggleTimes() {
        var checked = document.getElementById('all_day_input').checked;
        document.getElementById('starts_at_input').disabled = checked;
        document.getElementById('ends_at_input').disabled = checked;
        if (checked) {
            document.getElementById('starts_at_input').value = '';
            document.getElementById('ends_at_input').value = '';
        }
    }
    toggleTimes();
</script>
