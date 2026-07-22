@extends('layouts.app')

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div>
        <h3 class="fw-bold mb-0">Perfil de Conformidade</h3>
        <small class="text-muted">Dados institucionais usados nos cálculos CEBAS / SUAS / MROSC</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Coluna principal --}}
    <div class="col-lg-8">
        <form action="{{ route('ngo.conformidade.configurar.save') }}" method="POST">
            @csrf

            {{-- CNPJ lookup --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-search me-2 text-primary"></i>Busca Automática por CNPJ</h6>
                    <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-semibold">CNPJ da organização</label>
                            <input type="text" id="cnpj-input" class="form-control"
                                   placeholder="00.000.000/0000-00"
                                   value="{{ preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $tenant->document ?? '') }}">
                        </div>
                        <button type="button" id="btn-cnpj-lookup" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-search me-1"></i>Buscar
                        </button>
                    </div>
                    <div id="cnpj-result" class="mt-3" style="display:none;"></div>
                </div>
            </div>

            {{-- CEBAS --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-2 text-success"></i>Dados CEBAS</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Área de Atuação CEBAS</label>
                            <select name="area_atuacao_cebas" class="form-select @error('area_atuacao_cebas') is-invalid @enderror">
                                <option value="">-- Selecione --</option>
                                <option value="assistencia_social" {{ $tenant->area_atuacao_cebas === 'assistencia_social' ? 'selected' : '' }}>Assistência Social</option>
                                <option value="saude"              {{ $tenant->area_atuacao_cebas === 'saude'              ? 'selected' : '' }}>Saúde</option>
                                <option value="educacao"           {{ $tenant->area_atuacao_cebas === 'educacao'           ? 'selected' : '' }}>Educação</option>
                            </select>
                            @error('area_atuacao_cebas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">CNAE Principal</label>
                            <input type="text" name="cnae_principal" id="cnae_principal"
                                   class="form-control @error('cnae_principal') is-invalid @enderror"
                                   value="{{ old('cnae_principal', $tenant->cnae_principal) }}"
                                   maxlength="20" placeholder="ex: 8720401">
                            @error('cnae_principal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nº Registro CNAS</label>
                            <input type="text" name="cnas_numero"
                                   class="form-control @error('cnas_numero') is-invalid @enderror"
                                   value="{{ old('cnas_numero', $tenant->cnas_numero) }}"
                                   maxlength="30" placeholder="ex: 12345/2023">
                            @error('cnas_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Validade CNAS</label>
                            <input type="date" name="cnas_validade"
                                   class="form-control @error('cnas_validade') is-invalid @enderror"
                                   value="{{ old('cnas_validade', $tenant->cnas_validade?->format('Y-m-d')) }}">
                            @error('cnas_validade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUAS --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-house-heart me-2" style="color:#10b981"></i>Dados SUAS</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nº Registro CMAS</label>
                            <input type="text" name="cmas_numero"
                                   class="form-control @error('cmas_numero') is-invalid @enderror"
                                   value="{{ old('cmas_numero', $tenant->cmas_numero) }}"
                                   maxlength="30" placeholder="ex: 78/2022">
                            @error('cmas_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Validade CMAS</label>
                            <input type="date" name="cmas_validade"
                                   class="form-control @error('cmas_validade') is-invalid @enderror"
                                   value="{{ old('cmas_validade', $tenant->cmas_validade?->format('Y-m-d')) }}">
                            @error('cmas_validade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Código CNEAS</label>
                            <input type="text" name="cneas_codigo"
                                   class="form-control @error('cneas_codigo') is-invalid @enderror"
                                   value="{{ old('cneas_codigo', $tenant->cneas_codigo) }}"
                                   maxlength="30" placeholder="ex: 23.001-8">
                            @error('cneas_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dados gerais --}}
            <div class="card border-0 shadow-sm mb-4" style="border-radius:20px">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-building me-2 text-secondary"></i>Dados Gerais</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Data de Fundação</label>
                            <input type="date" name="data_fundacao" id="data_fundacao"
                                   class="form-control @error('data_fundacao') is-invalid @enderror"
                                   value="{{ old('data_fundacao', $tenant->data_fundacao?->format('Y-m-d')) }}">
                            @error('data_fundacao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Receita Bruta Anual de Referência (R$)</label>
                            <input type="number" name="receita_bruta_anual_ref"
                                   class="form-control @error('receita_bruta_anual_ref') is-invalid @enderror"
                                   value="{{ old('receita_bruta_anual_ref', $tenant->receita_bruta_anual_ref) }}"
                                   min="0" step="0.01" placeholder="0.00">
                            @error('receita_bruta_anual_ref')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success fw-semibold px-5">
                    <i class="bi bi-check-lg me-1"></i>Salvar Perfil
                </button>
            </div>
        </form>
    </div>

    {{-- Coluna lateral: dicas --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f0fdf4;border:1px solid #bbf7d0!important;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:#065f46"><i class="bi bi-info-circle me-2"></i>Por que esses dados?</h6>
                <ul class="small text-muted mb-0" style="padding-left:1.2rem;line-height:1.7">
                    <li><strong>CNAS</strong>: prova de vínculo CEBAS ativo; usado no Dossiê CEBAS.</li>
                    <li><strong>CMAS</strong>: habilitação municipal SUAS; usado no RMA.</li>
                    <li><strong>Área de atuação</strong>: define quais requisitos CEBAS se aplicam.</li>
                    <li><strong>CNPJ / CNAE</strong>: preenchidos automaticamente via BrasilAPI.</li>
                    <li><strong>Receita anual</strong>: base de cálculo para cotas de gratuidade.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.getElementById('btn-cnpj-lookup').addEventListener('click', function () {
    const cnpj = document.getElementById('cnpj-input').value.replace(/\D/g, '');
    const resultEl = document.getElementById('cnpj-result');

    if (cnpj.length !== 14) {
        resultEl.style.display = 'block';
        resultEl.innerHTML = '<div class="alert alert-warning py-2 mb-0">Informe um CNPJ válido com 14 dígitos.</div>';
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Buscando...';
    resultEl.style.display = 'none';

    fetch('{{ route("ngo.conformidade.cnpj.lookup") }}?cnpj=' + cnpj, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            resultEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + data.error + '</div>';
        } else {
            // Preenche os campos
            if (data.cnae_principal) document.getElementById('cnae_principal').value = data.cnae_principal;
            if (data.data_fundacao) document.getElementById('data_fundacao').value = data.data_fundacao;

            resultEl.innerHTML = '<div class="alert alert-success py-2 mb-0">' +
                '<strong>' + (data.razao_social || '') + '</strong><br>' +
                '<small>CNAE: ' + (data.cnae_principal || '—') + ' · Situação: ' + (data.situacao_cadastral || '—') + '</small>' +
            '</div>';
        }
        resultEl.style.display = 'block';
    })
    .catch(() => {
        resultEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">Erro ao consultar a BrasilAPI. Tente novamente.</div>';
        resultEl.style.display = 'block';
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-search me-1"></i>Buscar';
    });
});
</script>
@endpush

@endsection
