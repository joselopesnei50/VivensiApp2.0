@extends('layouts.app')

@section('content')

<div class="ds-page-header">
    <div>
        <h2 class="ds-page-title">Novo Beneficiário</h2>
        <p class="ds-page-subtitle">Cadastre um novo beneficiário no sistema de acompanhamento social.</p>
    </div>
    <a href="{{ url('/ngo/beneficiaries') }}" class="btn-ds btn-ds-outline">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

@if($errors->any())
    <div class="ds-alert ds-alert-danger mb-4">
        <i class="fas fa-exclamation-circle"></i>
        <ul style="margin:0; padding-left: 16px;">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="ds-card" style="max-width: 860px;">
    <div class="ds-card-header">
        <span style="font-weight: 700; color: var(--ds-text); font-size: 0.95rem;">
            <i class="fas fa-user-plus me-2" style="color: var(--ds-brand);"></i> Dados do Beneficiário
        </span>
    </div>
    <div class="ds-card-body">
        <form action="{{ url('/ngo/beneficiaries') }}" method="POST">
            @csrf

            {{-- Identificação --}}
            <div class="form-group">
                <label class="form-label" for="name">Nome Completo (Titular) <span style="color:var(--ds-danger)" aria-hidden="true">*</span></label>
                <input type="text" id="name" name="name" class="form-control-vivensi" value="{{ old('name') }}" required aria-required="true">
            </div>

            <div class="grid-2" style="gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="nis">NIS (Número de Identificação Social)</label>
                    <input type="text" id="nis" name="nis" class="form-control-vivensi" value="{{ old('nis') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" class="form-control-vivensi" value="{{ old('cpf') }}">
                </div>
            </div>

            {{-- Dados pessoais com novos campos --}}
            <div class="grid-2" style="gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="birth_date">Data de Nascimento</label>
                    <input type="date" id="birth_date" name="birth_date" class="form-control-vivensi" value="{{ old('birth_date') }}">
                    <small style="color:var(--ds-text-muted); font-size:0.78rem;">Idade calculada automaticamente.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Telefone</label>
                    <input type="text" id="phone" name="phone" class="form-control-vivensi" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="grid-2" style="gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="gender">Sexo / Gênero</label>
                    <select id="gender" name="gender" class="form-control-vivensi">
                        <option value="">Selecione...</option>
                        <option value="masculino"           {{ old('gender') === 'masculino'              ? 'selected' : '' }}>Masculino</option>
                        <option value="feminino"            {{ old('gender') === 'feminino'               ? 'selected' : '' }}>Feminino</option>
                        <option value="nao_binario"         {{ old('gender') === 'nao_binario'            ? 'selected' : '' }}>Não-binário</option>
                        <option value="outro"               {{ old('gender') === 'outro'                  ? 'selected' : '' }}>Outro</option>
                        <option value="prefiro_nao_informar" {{ old('gender') === 'prefiro_nao_informar'  ? 'selected' : '' }}>Prefiro não informar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="race_color">Cor / Raça <span style="font-size:0.75rem; color:var(--ds-text-muted)">(IBGE)</span></label>
                    <select id="race_color" name="race_color" class="form-control-vivensi">
                        <option value="">Selecione...</option>
                        <option value="branca"              {{ old('race_color') === 'branca'             ? 'selected' : '' }}>Branca</option>
                        <option value="preta"               {{ old('race_color') === 'preta'              ? 'selected' : '' }}>Preta</option>
                        <option value="parda"               {{ old('race_color') === 'parda'              ? 'selected' : '' }}>Parda</option>
                        <option value="amarela"             {{ old('race_color') === 'amarela'            ? 'selected' : '' }}>Amarela</option>
                        <option value="indigena"            {{ old('race_color') === 'indigena'           ? 'selected' : '' }}>Indígena</option>
                        <option value="prefiro_nao_informar" {{ old('race_color') === 'prefiro_nao_informar' ? 'selected' : '' }}>Prefiro não informar</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="education">Escolaridade</label>
                    <select id="education" name="education" class="form-control-vivensi">
                        <option value="">Selecione...</option>
                        <option value="sem_escolaridade"        {{ old('education') === 'sem_escolaridade'        ? 'selected' : '' }}>Sem escolaridade</option>
                        <option value="fundamental_incompleto"  {{ old('education') === 'fundamental_incompleto'  ? 'selected' : '' }}>Fundamental incompleto</option>
                        <option value="fundamental_completo"    {{ old('education') === 'fundamental_completo'    ? 'selected' : '' }}>Fundamental completo</option>
                        <option value="medio_incompleto"        {{ old('education') === 'medio_incompleto'        ? 'selected' : '' }}>Médio incompleto</option>
                        <option value="medio_completo"          {{ old('education') === 'medio_completo'          ? 'selected' : '' }}>Médio completo</option>
                        <option value="superior_incompleto"     {{ old('education') === 'superior_incompleto'     ? 'selected' : '' }}>Superior incompleto</option>
                        <option value="superior_completo"       {{ old('education') === 'superior_completo'       ? 'selected' : '' }}>Superior completo</option>
                        <option value="pos_graduacao"           {{ old('education') === 'pos_graduacao'           ? 'selected' : '' }}>Pós-graduação</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control-vivensi">
                        <option value="active"    {{ old('status', 'active') === 'active'    ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive"  {{ old('status') === 'inactive'  ? 'selected' : '' }}>Inativo</option>
                        <option value="graduated" {{ old('status') === 'graduated' ? 'selected' : '' }}>Graduado</option>
                    </select>
                    <small style="color:var(--ds-text-muted); font-size:0.78rem;">"Graduado" = saiu da vulnerabilidade.</small>
                </div>
            </div>

            <x-address-fields />

            <div class="ds-divider"></div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ url('/ngo/beneficiaries') }}" class="btn-ds btn-ds-outline">Cancelar</a>
                <button type="submit" class="btn-premium">
                    <i class="fas fa-save"></i> Salvar Cadastro
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Import CSV --}}
<div class="ds-card" style="max-width: 860px; margin-top: 24px; border-style: dashed;">
    <div class="ds-card-header">
        <span style="font-weight: 700; color: var(--ds-text); font-size: 0.95rem;">
            <i class="fas fa-file-import me-2" style="color: var(--ds-brand);"></i> Importar via Planilha (CSV)
        </span>
    </div>
    <div class="ds-card-body">
        <p style="color:var(--ds-text-muted); font-size: 0.9rem; margin-bottom: 20px;">
            Se você tem muitos beneficiários, suba um CSV. Se o CPF ou NIS já existir, os dados serão atualizados.
        </p>

        @if(session('import_errors'))
            <div class="ds-alert ds-alert-danger">
                <div>
                    <strong>Erros na importação:</strong>
                    <ul style="margin: 6px 0 0 0; padding-left: 16px; font-size: 0.85rem;">
                        @foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ url('/ngo/beneficiaries/import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Selecione o arquivo CSV</label>
                <input type="file" name="file" class="form-control-vivensi" accept=".csv" required>
                <div style="margin-top: 10px;">
                    <a href="{{ url('/ngo/beneficiaries/import/template') }}" style="font-size: 0.85rem; color: var(--ds-brand); font-weight: 600;">
                        <i class="fas fa-download me-1"></i> Baixar Modelo de Planilha
                    </a>
                </div>
            </div>
            <button type="submit" class="btn-ds btn-ds-outline">
                <i class="fas fa-upload"></i> Processar Planilha
            </button>
        </form>
    </div>
</div>

@endsection
