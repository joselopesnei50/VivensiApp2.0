@extends('layouts.app')

@section('content')
<style>
    .adesao-card { background:#fff; padding: 32px; max-width: 780px; margin: 24px auto; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
    .adesao-card h1 { margin: 0 0 4px 0; color:#0f172a; font-size:1.5rem; }
    .adesao-card p.lead { margin: 0 0 24px 0; color:#64748b; }
    .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .grid-3 { display:grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; }
    .field label { display:block; font-size:.85rem; color:#334155; margin-bottom:4px; }
    .field input { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:.95rem; }
    .step-bar { display:flex; gap:8px; margin-bottom:20px; }
    .step-bar span { flex:1; padding:8px 10px; border-radius:8px; text-align:center; font-size:.85rem; background:#f1f5f9; color:#64748b; }
    .step-bar span.active { background:#0f172a; color:#fff; font-weight:600; }
    .btn-cta { display:inline-flex; align-items:center; gap:8px; padding:12px 20px; background:#0f172a; color:#fff; border:0; border-radius:10px; font-size:1rem; font-weight:600; cursor:pointer; }
    .plan-badge { display:inline-block; padding:4px 10px; background:#f1f5f9; border-radius:999px; font-size:.8rem; color:#334155; margin-bottom:12px; }
</style>

<div class="adesao-card">
    <div class="step-bar">
        <span class="active">1. Dados da entidade</span>
        <span>2. Revisar e assinar</span>
        <span>3. {{ ($plan && $plan->is_courtesy) ? 'Acesso liberado' : 'Pagamento' }}</span>
    </div>

    @if($plan)
        <div class="plan-badge">Plano: {{ $plan->name }} @if($plan->is_courtesy) — CORTESIA @else — R$ {{ number_format((float)$plan->price, 2, ',', '.') }}/mes @endif</div>
    @endif

    <h1>Contrato de Adesao Vivensi</h1>
    <p class="lead">Antes de acessar a plataforma, precisamos dos dados da entidade e da sua assinatura. Sem esses dados nao emitimos o contrato.</p>

    @if(session('error'))
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    <form action="{{ route('adesao.store-data') }}" method="POST">
        @csrf

        <h3 style="margin: 20px 0 10px 0;">Entidade / Empresa</h3>
        <div class="field" style="margin-bottom:12px;">
            <label>Razao social *</label>
            <input type="text" name="razao_social" value="{{ old('razao_social', $tenant->razao_social ?? $tenant->name) }}" required>
            @error('razao_social')<small style="color:#dc2626">{{ $message }}</small>@enderror
        </div>

        <div class="grid-2" style="margin-bottom:12px;">
            <div class="field">
                <label>CNPJ / CPF *</label>
                <input type="text" name="document" value="{{ old('document', $tenant->document) }}" required>
                @error('document')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>CEP *</label>
                <input type="text" name="cep" value="{{ old('cep', $tenant->cep) }}" required>
                @error('cep')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
        </div>

        <div class="grid-3" style="margin-bottom:12px;">
            <div class="field">
                <label>Endereco *</label>
                <input type="text" name="endereco" value="{{ old('endereco', $tenant->endereco) }}" required>
                @error('endereco')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>Numero *</label>
                <input type="text" name="numero_endereco" value="{{ old('numero_endereco', $tenant->numero_endereco) }}" required>
                @error('numero_endereco')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>Complemento</label>
                <input type="text" name="complemento" value="{{ old('complemento', $tenant->complemento) }}">
            </div>
        </div>

        <div class="grid-3" style="margin-bottom:24px;">
            <div class="field">
                <label>Bairro *</label>
                <input type="text" name="bairro" value="{{ old('bairro', $tenant->bairro) }}" required>
                @error('bairro')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>Cidade *</label>
                <input type="text" name="cidade" value="{{ old('cidade', $tenant->cidade) }}" required>
                @error('cidade')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>UF *</label>
                <input type="text" name="estado" maxlength="2" value="{{ old('estado', $tenant->estado) }}" required>
                @error('estado')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
        </div>

        <h3 style="margin: 8px 0 10px 0;">Signatario (quem assina o contrato)</h3>
        <div class="grid-2" style="margin-bottom:12px;">
            <div class="field">
                <label>Nome completo *</label>
                <input type="text" name="signer_name" value="{{ old('signer_name', auth()->user()->name) }}" required>
                @error('signer_name')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
            <div class="field">
                <label>E-mail *</label>
                <input type="email" name="signer_email" value="{{ old('signer_email', auth()->user()->email) }}" required>
                @error('signer_email')<small style="color:#dc2626">{{ $message }}</small>@enderror
            </div>
        </div>
        <div class="field" style="margin-bottom:24px;">
            <label>CPF do signatario *</label>
            <input type="text" name="signer_cpf" value="{{ old('signer_cpf') }}" required>
            @error('signer_cpf')<small style="color:#dc2626">{{ $message }}</small>@enderror
        </div>

        <button type="submit" class="btn-cta">Continuar para revisar e assinar</button>
    </form>
</div>
@endsection
