@props(['model' => null, 'inputClass' => 'form-control-vivensi'])

@php
    $zip          = old('address_zip',          $model->address_zip          ?? '');
    $street       = old('address_street',       $model->address_street       ?? '');
    $number       = old('address_number',       $model->address_number       ?? '');
    $complement   = old('address_complement',   $model->address_complement   ?? '');
    $neighborhood = old('address_neighborhood', $model->address_neighborhood ?? '');
    $city         = old('address_city',         $model->address_city         ?? '');
    $state        = old('address_state',        $model->address_state        ?? '');
@endphp

<div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; background: #f8fafc;">
    <p style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: .08em; margin: 0 0 16px 0;">
        <i class="fas fa-map-marker-alt me-1" style="color: #6366f1;"></i> Endereço
    </p>

    {{-- CEP --}}
    <div style="display: grid; grid-template-columns: 180px 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">CEP</label>
            <input type="text" name="address_zip" id="address_zip"
                   class="{{ $inputClass }}"
                   value="{{ $zip }}"
                   placeholder="00000-000"
                   maxlength="9"
                   oninput="this.value=this.value.replace(/\D/g,'').replace(/^(\d{5})(\d)/,'$1-$2')"
                   onblur="fetchCEP(this.value)">
        </div>
        <div id="cep-status" style="display:flex; align-items:flex-end; padding-bottom:2px; font-size:0.8rem; color:#64748b;"></div>
    </div>

    {{-- Rua + Número --}}
    <div style="display: grid; grid-template-columns: 1fr 120px; gap: 12px; margin-bottom: 12px;">
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">Logradouro</label>
            <input type="text" name="address_street" id="address_street"
                   class="{{ $inputClass }}" value="{{ $street }}" placeholder="Ex: Rua das Flores">
        </div>
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">Número</label>
            <input type="text" name="address_number" id="address_number"
                   class="{{ $inputClass }}" value="{{ $number }}" placeholder="123">
        </div>
    </div>

    {{-- Complemento + Bairro --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">Complemento</label>
            <input type="text" name="address_complement" id="address_complement"
                   class="{{ $inputClass }}" value="{{ $complement }}" placeholder="Apto, Sala, Bloco...">
        </div>
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">Bairro</label>
            <input type="text" name="address_neighborhood" id="address_neighborhood"
                   class="{{ $inputClass }}" value="{{ $neighborhood }}" placeholder="Ex: Centro">
        </div>
    </div>

    {{-- Cidade + Estado --}}
    <div style="display: grid; grid-template-columns: 1fr 80px; gap: 12px;">
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">Cidade</label>
            <input type="text" name="address_city" id="address_city"
                   class="{{ $inputClass }}" value="{{ $city }}" placeholder="Ex: São Paulo">
        </div>
        <div>
            <label style="display:block; font-size:.8rem; font-weight:700; color:#475569; margin-bottom:5px;">UF</label>
            <input type="text" name="address_state" id="address_state"
                   class="{{ $inputClass }}" value="{{ $state }}" placeholder="SP" maxlength="2"
                   style="text-transform:uppercase">
        </div>
    </div>
</div>

<script>
async function fetchCEP(rawCep) {
    const cep = rawCep.replace(/\D/g, '');
    const status = document.getElementById('cep-status');
    if (cep.length !== 8) return;

    status.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Buscando...';
    try {
        const r = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
        const d = await r.json();
        if (d.erro) {
            status.innerHTML = '<span style="color:#ef4444"><i class="fas fa-times-circle me-1"></i>CEP não encontrado</span>';
            return;
        }
        document.getElementById('address_street').value       = d.logradouro || '';
        document.getElementById('address_neighborhood').value = d.bairro     || '';
        document.getElementById('address_city').value         = d.localidade || '';
        document.getElementById('address_state').value        = d.uf         || '';
        status.innerHTML = '<span style="color:#16a34a"><i class="fas fa-check-circle me-1"></i>Endereço preenchido</span>';
    } catch(e) {
        status.innerHTML = '<span style="color:#ef4444"><i class="fas fa-exclamation-circle me-1"></i>Erro ao buscar CEP</span>';
    }
}
</script>
