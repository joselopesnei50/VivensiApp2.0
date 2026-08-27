@extends('layouts.app')
@section('title', 'Nova lista de contatos')

@section('content')
<div style="max-width:820px; margin:0 auto;">
    <a href="{{ route('ngo.email_campaigns.lists.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; color:var(--ds-brand); font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
        <i class="fas fa-arrow-left"></i> Voltar às listas
    </a>
    <h2 style="margin:0 0 6px; color:#1e293b; font-weight:900; font-size:1.7rem; letter-spacing:-0.5px;">Nova lista de contatos</h2>
    <p style="color:#64748b; margin:0 0 24px; font-size:0.9rem;">Cadastre uma vez, dispare em quantas campanhas quiser.</p>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#991b1b;">
            @foreach ($errors->all() as $e)<div>✗ {{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('ngo.email_campaigns.lists.store') }}" enctype="multipart/form-data"
          class="vivensi-card" style="padding:32px 36px; border-radius:18px;">
        @csrf

        <div style="margin-bottom:22px;">
            <label style="display:block; font-weight:700; margin-bottom:8px; color:#1e293b; font-size:.88rem;">Nome<span style="color:var(--ds-brand); margin-left:3px;">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                   placeholder="Ex: Doadores 2026 · Q4"
                   style="width:100%; padding:12px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:.95rem; box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--ds-brand)'" onblur="this.style.borderColor='#f1f5f9'">
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block; font-weight:700; margin-bottom:8px; color:#1e293b; font-size:.88rem;">Descrição</label>
            <textarea name="description" maxlength="1000" rows="2"
                      placeholder="Contexto da lista, origem, propósito"
                      style="width:100%; padding:12px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:.95rem; font-family:inherit; box-sizing:border-box;">{{ old('description') }}</textarea>
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block; font-weight:700; margin-bottom:8px; color:#1e293b; font-size:.88rem;">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" value="{{ old('tags') }}"
                   placeholder="doadores, ex-alunos, empresa-parceira"
                   style="width:100%; padding:12px 14px; border:2px solid #f1f5f9; border-radius:10px; font-size:.95rem; box-sizing:border-box;">
            <div style="font-size:.78rem; color:#64748b; margin-top:6px;">Ajudam a filtrar e agrupar listas depois.</div>
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block; font-weight:700; margin-bottom:8px; color:#1e293b; font-size:.88rem;">Upload CSV (opcional agora)</label>
            <div style="border:2px dashed #e2e8f0; border-radius:12px; padding:26px 20px; text-align:center; background:#f8fafc; transition:.18s;"
                 onmouseover="this.style.borderColor='var(--ds-brand)'; this.style.background='#ecfdf5';"
                 onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc';">
                <label style="cursor:pointer; display:block;" for="ngoCsvInput">
                    <i class="fas fa-cloud-upload-alt" style="font-size:1.8rem; color:#64748b; display:block; margin-bottom:8px;"></i>
                    <div style="color:#1e293b; font-weight:700; font-size:.95rem; margin-bottom:2px;">Clique para escolher um arquivo CSV</div>
                    <div style="color:#64748b; font-size:.8rem;">Ou você adiciona contatos depois pela tela da lista</div>
                </label>
                <input type="file" name="csv" id="ngoCsvInput" accept=".csv,text/csv" style="display:none;"
                       onchange="var el=document.getElementById('ngoCsvFilename'); if(this.files[0]){el.textContent='📎 '+this.files[0].name; el.style.display='block';}else{el.style.display='none';}">
                <div id="ngoCsvFilename" style="display:none; margin-top:10px; padding:8px 12px; background:#ecfdf5; color:#065f46; border-radius:8px; font-size:.85rem; font-weight:600;"></div>
            </div>
            <div style="font-size:.78rem; color:#64748b; margin-top:6px; line-height:1.5;">
                UTF-8 ou Windows-1252 · Colunas <code>email</code> e <code>name</code> (opcional) · Delimiter automático · Até 20.000 contatos · Máx 10 MB
            </div>
        </div>

        <div style="margin-bottom:26px; padding:16px 20px; background:#ecfdf5; border-left:4px solid var(--ds-brand); border-radius:10px;">
            <label style="display:flex; gap:12px; align-items:flex-start; cursor:pointer; margin:0;">
                <input type="checkbox" name="opt_in_confirmed" value="1" required
                       style="margin-top:3px; width:18px; height:18px; accent-color:var(--ds-brand); cursor:pointer;">
                <span style="color:#065f46; font-size:.88rem; line-height:1.55;">
                    <strong>Confirmo (LGPD art. 7º, IX)</strong> que possuo o consentimento dos contatos desta lista para envio de e-mails. Assumo responsabilidade pela origem legítima dos dados.
                </span>
            </label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('ngo.email_campaigns.lists.index') }}"
               style="padding:12px 22px; background:#f1f5f9; color:#475569; border-radius:10px; text-decoration:none; font-weight:700; font-size:.9rem;">Cancelar</a>
            <button type="submit"
                    style="padding:12px 26px; background:var(--ds-brand); color:white; border:0; border-radius:10px; font-weight:800; font-size:.95rem; cursor:pointer; box-shadow:0 4px 14px rgba(5,150,105,.3);">Criar lista</button>
        </div>
    </form>
</div>
@endsection
