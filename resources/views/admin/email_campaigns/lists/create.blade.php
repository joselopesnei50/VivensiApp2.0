@extends('layouts.app')
@section('title', 'Nova lista de contatos')

@section('content')
<div class="container" style="max-width:780px; margin:24px auto;">
    <h1 style="margin:0 0 6px; color:#0f172a;">📇 Nova lista de contatos</h1>
    <p style="color:#64748b; margin:0 0 22px;">Crie uma lista pra reutilizar em várias campanhas.</p>

    @if ($errors->any())
        <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            @foreach ($errors->all() as $e)<div>❌ {{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.email_campaigns.lists.store') }}"
          enctype="multipart/form-data"
          style="background:#fff; padding:26px; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
        @csrf

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:700; margin-bottom:6px; color:#0f172a;">Nome <span style="color:#dc2626;">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                   placeholder="Ex: Doadores 2026 - Q4"
                   style="width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:8px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:700; margin-bottom:6px; color:#0f172a;">Descrição</label>
            <textarea name="description" maxlength="1000" rows="2"
                      placeholder="Contexto da lista, origem, propósito"
                      style="width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:8px;">{{ old('description') }}</textarea>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:700; margin-bottom:6px; color:#0f172a;">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" value="{{ old('tags') }}"
                   placeholder="doadores, ex-alunos, empresa-parceira"
                   style="width:100%; padding:11px 14px; border:1.5px solid #e2e8f0; border-radius:8px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:700; margin-bottom:6px; color:#0f172a;">Upload CSV (opcional agora)</label>
            <input type="file" name="csv" accept=".csv,text/csv"
                   style="width:100%; padding:10px; border:1.5px dashed #cbd5e1; border-radius:8px; background:#f8fafc;">
            <div style="font-size:.78rem; color:#64748b; margin-top:6px;">
                CSV UTF-8 ou Windows-1252. Colunas: <code>email</code> e <code>name</code> (opcional). Até 20.000 contatos por upload. Máx 10MB.
            </div>
        </div>

        <div style="margin-bottom:20px; padding:14px 16px; background:#fffbeb; border-left:3px solid #f59e0b; border-radius:6px;">
            <label style="display:flex; gap:10px; align-items:flex-start; cursor:pointer;">
                <input type="checkbox" name="opt_in_confirmed" value="1" required style="margin-top:3px;">
                <span style="color:#713f12; font-size:.88rem; line-height:1.55;">
                    <strong>Confirmo (LGPD art. 7º, IX)</strong> que possuo o consentimento dos contatos desta lista para envio de e-mails. Assumo responsabilidade pela origem legítima dos dados.
                </span>
            </label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('admin.email_campaigns.lists.index') }}"
               style="padding:11px 20px; background:#e5e7eb; color:#374151; border-radius:8px; text-decoration:none; font-weight:700;">Cancelar</a>
            <button type="submit"
                    style="padding:11px 22px; background:#10b981; color:#fff; border:0; border-radius:8px; font-weight:700; cursor:pointer;">
                Criar lista
            </button>
        </div>
    </form>
</div>
@endsection
