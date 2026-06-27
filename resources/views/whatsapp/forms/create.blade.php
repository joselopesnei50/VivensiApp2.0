@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 24px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">WhatsApp / Formulários</h6>
    </div>
    <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.4rem; letter-spacing:-1px;">Novo formulário</h2>
    <p style="color:#64748b; margin-top:8px;">Comece pelo nome — as perguntas você adiciona na próxima tela.</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="m-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="vivensi-card" style="padding:30px; max-width:720px;">
    <form action="{{ route('whatsapp.forms.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Nome</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="120"
                   class="form-control form-control-lg rounded-3" placeholder="Ex: Cadastro de novo doador">
        </div>

        <div class="mb-3">
            <label class="form-label fw-700" style="font-size:.8rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Descrição <span style="color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:0;">(opcional)</span></label>
            <textarea name="description" rows="3" maxlength="1000"
                      class="form-control rounded-3" placeholder="Explique pra equipe quando esse form deve ser usado.">{{ old('description') }}</textarea>
        </div>

        <div class="mb-4 form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', '1') ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label fw-700">Ativo</label>
            <div class="form-text">Só formulários ativos aparecem na lista do chat.</div>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn-premium" style="padding:13px 26px;">
                <i class="fas fa-arrow-right me-2"></i> Continuar pra perguntas
            </button>
            <a href="{{ route('whatsapp.forms.index') }}" class="btn btn-outline-secondary rounded-3" style="padding:13px 22px;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
