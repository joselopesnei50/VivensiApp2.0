@extends('layouts.app')
@section('title', 'Nova lista de contatos')

@push('styles')
<style>
    .brc-hero {
        background: linear-gradient(135deg, #0A0A0B 0%, #1a1a1c 100%);
        border-radius: 20px; padding: 28px 32px; margin-bottom: 22px;
        display: flex; align-items: center; gap: 18px;
        box-shadow: 0 10px 30px rgba(10,10,11,.12);
    }
    .brc-hero__title { color: #F4F4F5; font-weight: 900; font-size: 1.5rem; margin: 0; letter-spacing: -.4px; }
    .brc-hero__sub   { color: #9a9a9e; font-size: .9rem; margin: 4px 0 0; }

    .brc-card {
        background: #fff; padding: 32px 36px; border-radius: 18px;
        box-shadow: 0 4px 16px rgba(10,10,11,.06);
    }
    .brc-field { margin-bottom: 22px; }
    .brc-label { display: block; font-weight: 700; margin-bottom: 8px; color: #0A0A0B; font-size: .88rem; }
    .brc-required { color: #FF7A1A; margin-left: 3px; }
    .brc-input, .brc-textarea {
        width: 100%; padding: 12px 14px; border: 2px solid #d9d9dc; border-radius: 10px;
        font-size: .95rem; transition: border-color .15s, box-shadow .15s;
        font-family: inherit; background: #fff; color: #0A0A0B;
    }
    .brc-input:focus, .brc-textarea:focus {
        outline: 0; border-color: #FF7A1A; box-shadow: 0 0 0 3px rgba(255,122,26,.15);
    }
    .brc-hint { font-size: .78rem; color: #6b6b6f; margin-top: 6px; line-height: 1.5; }

    .brc-drop {
        border: 2px dashed #d9d9dc; border-radius: 12px; padding: 30px 20px;
        text-align: center; transition: all .18s; background: #F4F4F5;
    }
    .brc-drop:hover { border-color: #FF7A1A; background: rgba(255,122,26,.04); }
    .brc-drop__icon { font-size: 2rem; color: #6b6b6f; margin-bottom: 8px; }
    .brc-drop__label { display: block; cursor: pointer; }
    .brc-drop__title { color: #0A0A0B; font-weight: 700; font-size: .95rem; margin: 0 0 4px; }
    .brc-drop__sub { color: #6b6b6f; font-size: .8rem; margin: 0; }
    #brcCsvFilename {
        display: none; margin-top: 12px; padding: 10px 14px;
        background: rgba(255,122,26,.1); color: #0A0A0B; border-radius: 8px;
        font-size: .85rem; font-weight: 600;
    }

    .brc-lgpd {
        margin-bottom: 26px; padding: 18px 20px;
        background: rgba(255,122,26,.08); border-left: 4px solid #FF7A1A; border-radius: 10px;
    }
    .brc-lgpd label { display: flex; gap: 12px; align-items: flex-start; cursor: pointer; }
    .brc-lgpd input[type="checkbox"] {
        margin-top: 3px; width: 18px; height: 18px; accent-color: #FF7A1A; cursor: pointer;
    }
    .brc-lgpd__text { color: #0A0A0B; font-size: .88rem; line-height: 1.55; }
    .brc-lgpd__text strong { color: #0A0A0B; font-weight: 800; }

    .brc-btn-row { display: flex; justify-content: flex-end; gap: 10px; }
    .brc-btn-cancel {
        padding: 12px 22px; background: #F4F4F5; color: #3A3A3C;
        border-radius: 10px; text-decoration: none; font-weight: 700; font-size: .9rem;
    }
    .brc-btn-submit {
        padding: 12px 26px; background: #FF7A1A; color: #0A0A0B;
        border: 0; border-radius: 10px; font-weight: 800; font-size: .95rem;
        cursor: pointer; letter-spacing: .2px; transition: transform .15s;
        box-shadow: 0 4px 14px rgba(255,122,26,.35);
    }
    .brc-btn-submit:hover { transform: translateY(-1px); }

    .brc-error {
        background: rgba(220,38,38,.08); border-left: 4px solid #dc2626;
        color: #7f1d1d; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px;
        font-size: .88rem;
    }
</style>
@endpush

@section('content')
<div class="container" style="max-width:780px; margin:24px auto;">
    <div class="brc-hero">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="48" height="48" style="flex-shrink:0;">
        <div>
            <h1 class="brc-hero__title">Nova lista de contatos</h1>
            <p class="brc-hero__sub">Cadastre uma vez, dispare em quantas campanhas quiser.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="brc-error">
            @foreach ($errors->all() as $e)<div>✗ {{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.email_campaigns.lists.store') }}" enctype="multipart/form-data" class="brc-card">
        @csrf

        <div class="brc-field">
            <label class="brc-label">Nome<span class="brc-required">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                   placeholder="Ex: Doadores 2026 · Q4" class="brc-input">
        </div>

        <div class="brc-field">
            <label class="brc-label">Descrição</label>
            <textarea name="description" maxlength="1000" rows="2"
                      placeholder="Contexto da lista, origem, propósito" class="brc-textarea">{{ old('description') }}</textarea>
        </div>

        <div class="brc-field">
            <label class="brc-label">Tags (separadas por vírgula)</label>
            <input type="text" name="tags" value="{{ old('tags') }}"
                   placeholder="doadores, ex-alunos, empresa-parceira" class="brc-input">
            <div class="brc-hint">Ajudam a filtrar e agrupar listas depois.</div>
        </div>

        <div class="brc-field">
            <label class="brc-label">Upload CSV (opcional agora)</label>
            <div class="brc-drop">
                <label class="brc-drop__label" for="brcCsvInput">
                    <div class="brc-drop__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p class="brc-drop__title">Clique para escolher um arquivo CSV</p>
                    <p class="brc-drop__sub">Ou você adiciona contatos depois pela tela da lista</p>
                </label>
                <input type="file" name="csv" id="brcCsvInput" accept=".csv,text/csv" style="display:none;"
                       onchange="document.getElementById('brcCsvFilename').textContent='📎 ' + (this.files[0]?.name || ''); document.getElementById('brcCsvFilename').style.display = this.files[0] ? 'block' : 'none';">
                <div id="brcCsvFilename"></div>
            </div>
            <div class="brc-hint">
                UTF-8 ou Windows-1252 · Colunas <code>email</code> e <code>name</code> (opcional) · Delimiter automático · Até 20.000 contatos · Máx 10 MB
            </div>
        </div>

        <div class="brc-lgpd">
            <label>
                <input type="checkbox" name="opt_in_confirmed" value="1" required>
                <span class="brc-lgpd__text">
                    <strong>Confirmo (LGPD art. 7º, IX)</strong> que possuo o consentimento dos contatos desta lista para envio de e-mails. Assumo responsabilidade pela origem legítima dos dados.
                </span>
            </label>
        </div>

        <div class="brc-btn-row">
            <a href="{{ route('admin.email_campaigns.lists.index') }}" class="brc-btn-cancel">Cancelar</a>
            <button type="submit" class="brc-btn-submit">Criar lista</button>
        </div>
    </form>
</div>
@endsection
