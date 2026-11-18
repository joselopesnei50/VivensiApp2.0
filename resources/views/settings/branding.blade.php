@extends('layouts.app')

@section('title', 'Identidade Visual e Pix — Vivensi')

@section('content')
@php
    $brandColor = old('brand_color', $tenant->brand_color ?? '#4F46E5');
    $typeOld    = old('pix_key_type', $pixType);
    $keyOld     = old('pix_key', $pixDisplay ?? '');
    $waOld      = old('whatsapp_support', $tenant->whatsapp_support);
    if ($errors->has('pix_key') || $errors->has('pix_key_type') || $errors->has('whatsapp_support')) { $tab = 'pix'; }
    elseif ($errors->has('report_email')) { $tab = 'relatorio'; }
    elseif ($errors->any()) { $tab = 'identidade'; }
@endphp

<style>
    .bx { max-width: 1080px; margin: 0 auto 40px; color: var(--ds-text); }
    .bx-head { margin-bottom: 18px; }
    .bx-kicker { font-size: .68rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--ds-text-faint); }
    .bx-head h1 { font-size: 1.6rem; font-weight: 800; margin: 4px 0 4px; color: var(--ds-text); letter-spacing: -.02em; }
    .bx-head p { margin: 0; color: var(--ds-text-muted); font-size: .93rem; }

    .bx-tabs { display: flex; gap: 6px; padding: 5px; background: var(--vv-surface-soft); border: 1px solid var(--ds-border); border-radius: 14px; margin-bottom: 18px; overflow-x: auto; scrollbar-width: none; }
    .bx-tabs::-webkit-scrollbar { display: none; }
    .bx-tab { flex: 1 0 auto; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border: 0; border-radius: 10px; background: transparent; color: var(--ds-text-muted); font-weight: 700; font-size: .9rem; white-space: nowrap; cursor: pointer; }
    .bx-tab:hover { color: var(--ds-text); }
    .bx-tab[aria-selected="true"] { background: var(--ds-surface); color: var(--ds-text); box-shadow: var(--ds-shadow-sm); }
    .bx-tab .short { display: none; }
    .bx-tab .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--ds-danger); }

    .bx-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
    .bx-card { background: var(--ds-surface); border: 1px solid var(--ds-border); border-radius: 18px; padding: 24px; box-shadow: var(--ds-shadow-xs); }
    .bx-card + .bx-card { margin-top: 16px; }
    .bx-card h2 { font-size: 1.02rem; font-weight: 800; margin: 0 0 4px; color: var(--ds-text); display: flex; align-items: center; gap: 10px; }
    .bx-card h2 i { width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
    .bx-card .lead { font-size: .86rem; color: var(--ds-text-muted); margin: 0 0 18px; line-height: 1.55; }

    .bx-label { display: block; font-size: .78rem; font-weight: 800; color: var(--ds-text-muted); margin-bottom: 7px; }
    .bx-input { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--ds-border); background: var(--ds-bg); color: var(--ds-text); font-size: .95rem; outline: none; transition: border-color .15s, box-shadow .15s; }
    .bx-input:focus { border-color: var(--ds-brand); box-shadow: 0 0 0 3px var(--ds-brand-shadow); }
    .bx-input.is-bad { border-color: var(--ds-danger); }
    .bx-help { font-size: .78rem; color: var(--ds-text-faint); margin: 6px 0 0; line-height: 1.5; }
    .bx-err { font-size: .8rem; color: var(--ds-danger); font-weight: 700; margin: 6px 0 0; }
    .bx-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .bx-logo { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
    .bx-logo-box { width: 150px; height: 76px; border: 1px dashed var(--ds-border); border-radius: 12px; background: var(--ds-bg); display: flex; align-items: center; justify-content: center; padding: 10px; flex-shrink: 0; }
    .bx-logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .bx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; border-radius: 12px; font-weight: 700; font-size: .9rem; cursor: pointer; border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text); text-decoration: none; }
    .bx-btn:hover { border-color: var(--ds-brand); }
    .bx-btn-primary { background: var(--ds-brand-dark); border-color: var(--ds-brand-dark); color: #fff; }
    .bx-btn-primary:hover { background: var(--ds-brand); color: #fff; }
    .bx-btn-ghost-danger { border: 0; background: none; color: var(--ds-danger); padding: 6px 0; font-size: .82rem; }

    .bx-color { display: flex; align-items: center; gap: 12px; padding: 8px 12px; border: 1px solid var(--ds-border); border-radius: 12px; background: var(--ds-bg); }
    .bx-color input[type=color] { width: 40px; height: 40px; border: 0; padding: 0; background: none; border-radius: 10px; cursor: pointer; }
    .bx-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-weight: 700; }

    .bx-types { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 16px; }
    .bx-type { position: relative; }
    .bx-type input { position: absolute; opacity: 0; inset: 0; }
    .bx-type label { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; border: 1px solid var(--ds-border); border-radius: 12px; background: var(--ds-bg); font-weight: 700; font-size: .82rem; text-align: center; cursor: pointer; color: var(--ds-text-muted); height: 100%; }
    .bx-type label i { font-size: 1rem; }
    .bx-type input:checked + label { border-color: var(--ds-success); background: var(--vv-tint-success); color: var(--ds-text); box-shadow: 0 0 0 2px rgba(16,185,129,.25); }
    .bx-type input:focus-visible + label { outline: 2px solid var(--ds-brand); outline-offset: 2px; }

    .bx-note { border-radius: 12px; padding: 12px 14px; font-size: .84rem; line-height: 1.5; display: flex; gap: 10px; }
    .bx-note i { margin-top: 2px; }
    .bx-note-ok { background: var(--vv-tint-success); border: 1px solid var(--vv-tint-success-border); }
    .bx-note-warn { background: var(--vv-tint-warning); border: 1px solid var(--vv-tint-warning-border); }
    .bx-note-bad { background: var(--vv-tint-danger); border: 1px solid var(--vv-tint-danger-border); }
    .bx-note-info { background: var(--vv-surface-soft); border: 1px solid var(--ds-border); }

    .bx-final { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: .84rem; color: var(--ds-text-muted); min-height: 22px; }
    .bx-final strong { color: var(--ds-text); }

    .bx-test { display: none; margin-top: 14px; gap: 18px; align-items: flex-start; flex-wrap: wrap; }
    .bx-test.on { display: flex; }
    .bx-test canvas { background: #fff; border-radius: 12px; padding: 8px; border: 1px solid var(--ds-border); width: 180px !important; height: 180px !important; }
    .bx-test textarea { width: 100%; font-size: .72rem; font-family: ui-monospace, monospace; border-radius: 10px; border: 1px solid var(--ds-border); background: var(--ds-bg); color: var(--ds-text); padding: 8px; resize: none; }

    .bx-switch { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid var(--ds-border); border-radius: 12px; background: var(--ds-bg); cursor: pointer; font-weight: 700; }
    .bx-switch input { width: 20px; height: 20px; accent-color: var(--ds-brand); }

    .bx-save { position: sticky; bottom: 0; z-index: 5; margin-top: 16px; display: flex; justify-content: flex-end; gap: 10px; padding: 12px 84px 12px 0; }
    .bx-save .bx-btn-primary { box-shadow: 0 8px 24px rgba(79,70,229,.35); padding: 12px 28px; }

    .bx-preview { position: sticky; top: 90px; }
    .bx-mini { background: #070d1a; border-radius: 14px; padding: 18px; border: 1px solid rgba(255,255,255,.06); }
    .bx-mini .item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px; font-size: .8rem; color: rgba(255,255,255,.45); font-weight: 600; }

    [data-tab-panel][hidden] { display: none !important; }

    @media (max-width: 991px) {
        .bx-grid { grid-template-columns: 1fr; }
        .bx-preview { position: static; }
    }
    @media (max-width: 575px) {
        .bx-head h1 { font-size: 1.3rem; }
        .bx-card { padding: 18px; border-radius: 16px; }
        .bx-row { grid-template-columns: 1fr; }
        .bx-types { grid-template-columns: repeat(2, 1fr); }
        .bx-tab { padding: 9px 10px; font-size: .85rem; gap: 6px; }
        .bx-tab .full { display: none; }
        .bx-tab .short { display: inline; }
        .bx-save { padding-right: 72px; }
        .bx-save .bx-btn-primary { flex: 1; }
        .bx-test canvas { margin: 0 auto; }
    }
</style>

<div class="bx">
    <div class="bx-head">
        <div class="bx-kicker">Configurações</div>
        <h1>Identidade Visual e Recebimento</h1>
        <p>Logo e cor do painel, chave Pix das rifas e o resumo semanal por e-mail.</p>
    </div>

    @if(session('success'))
        <div class="bx-note bx-note-ok mb-3" role="status"><i class="fas fa-check-circle" style="color: var(--ds-success);"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="bx-note bx-note-bad mb-3" role="alert"><i class="fas fa-circle-exclamation" style="color: var(--ds-danger);"></i><div>{{ session('error') }}</div></div>
    @endif
    @if($errors->any())
        <div class="bx-note bx-note-bad mb-3" role="alert">
            <i class="fas fa-circle-exclamation" style="color: var(--ds-danger);"></i>
            <div><strong>Não salvamos. Confira:</strong> {{ implode(' ', $errors->all()) }}</div>
        </div>
    @endif

    <div class="bx-tabs" role="tablist" aria-label="Seções">
        <button type="button" class="bx-tab" role="tab" data-tab="identidade" aria-selected="{{ $tab === 'identidade' ? 'true' : 'false' }}"><i class="fas fa-palette"></i> <span class="full">Identidade Visual</span><span class="short">Identidade</span></button>
        <button type="button" class="bx-tab" role="tab" data-tab="pix" aria-selected="{{ $tab === 'pix' ? 'true' : 'false' }}"><i class="fas fa-qrcode"></i> <span class="full">Recebimento Pix</span><span class="short">Pix</span> @if(!$pixKey)<span class="dot" title="Chave Pix não configurada"></span>@endif</button>
        <button type="button" class="bx-tab" role="tab" data-tab="relatorio" aria-selected="{{ $tab === 'relatorio' ? 'true' : 'false' }}"><i class="fas fa-envelope-open-text"></i> <span class="full">Relatório semanal</span><span class="short">Relatório</span></button>
    </div>

    <form action="{{ route('settings.branding.update') }}" method="POST" enctype="multipart/form-data" id="branding-form" novalidate>
        @csrf
        <input type="hidden" name="_tab" id="current-tab" value="{{ $tab }}">

        {{-- ─────────── Identidade Visual ─────────── --}}
        <div data-tab-panel="identidade" role="tabpanel" @if($tab !== 'identidade') hidden @endif>
            <div class="bx-grid">
                <div>
                    <div class="bx-card">
                        <h2><i class="fas fa-image" style="background: rgba(99,102,241,.12); color: var(--ds-brand);"></i> Logotipo</h2>
                        <p class="lead">PNG, JPG ou WEBP, até 2 MB. Fundo transparente fica melhor. Tamanho sugerido: 300 × 80 px.</p>
                        <div class="bx-logo">
                            <div class="bx-logo-box">
                                <img id="logo-preview" alt="Logo atual" src="{{ $tenant->brand_logo ? Storage::url($tenant->brand_logo) : asset('img/novalogo.png') }}" style="{{ $tenant->brand_logo ? '' : 'opacity:.35' }}">
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label for="brand_logo" class="bx-btn"><i class="fas fa-cloud-upload-alt"></i> Escolher imagem</label>
                                <input type="file" id="brand_logo" name="brand_logo" accept="image/png,image/jpeg,image/webp" class="d-none" onchange="previewLogo(this)">
                                <p class="bx-help" id="logo-filename">{{ $tenant->brand_logo ? 'Logo atual em uso.' : 'Usando o logo padrão da Vivensi.' }}</p>
                                @error('brand_logo')<p class="bx-err">{{ $message }}</p>@enderror
                                @if($tenant->brand_logo)
                                    <button type="button" class="bx-btn-ghost-danger" onclick="removeLogo()"><i class="fas fa-trash-alt"></i> Remover logo</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bx-card">
                        <h2><i class="fas fa-paint-roller" style="background: rgba(236,72,153,.12); color: #ec4899;"></i> Nome e cor</h2>
                        <p class="lead">Aparecem no menu lateral e nos botões do painel da sua equipe.</p>
                        <div class="bx-row">
                            <div>
                                <label class="bx-label" for="brand_name">Nome de exibição</label>
                                <input type="text" class="bx-input" id="brand_name" name="brand_name" maxlength="80" value="{{ old('brand_name', $tenant->brand_name) }}" placeholder="{{ $tenant->name }}" oninput="document.getElementById('mini-name').textContent = this.value || this.placeholder">
                                <p class="bx-help">Em branco, usamos o nome do cadastro.</p>
                            </div>
                            <div>
                                <label class="bx-label" for="brand_color">Cor de destaque</label>
                                <div class="bx-color">
                                    <input type="color" id="brand_color" name="brand_color" value="{{ $brandColor }}" oninput="updateColor(this.value)">
                                    <span class="bx-mono" id="color-hex">{{ strtoupper($brandColor) }}</span>
                                </div>
                                @error('brand_color')<p class="bx-err">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="bx-preview">
                    <div class="bx-card" style="padding: 18px;">
                        <div class="bx-kicker" style="margin-bottom: 12px;">Prévia do menu</div>
                        <div class="bx-mini">
                            <div style="text-align: center; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                <img id="mini-logo" alt="" src="{{ $tenant->brand_logo ? Storage::url($tenant->brand_logo) : asset('img/novalogo.png') }}" style="max-width: 110px; max-height: 46px; object-fit: contain; {{ $tenant->brand_logo ? '' : 'opacity:.4' }}">
                                <div id="mini-name" style="font-size: .72rem; color: rgba(255,255,255,.4); font-weight: 700; margin-top: 6px;">{{ $tenant->brand_name ?: $tenant->name }}</div>
                            </div>
                            <div class="item" id="mini-active" style="color: #fff;"><i class="fas fa-gauge"></i> Dashboard</div>
                            <div class="item"><i class="fas fa-folder"></i> Projetos</div>
                            <div class="item"><i class="fas fa-ticket"></i> Rifas</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        {{-- ─────────── Recebimento Pix ─────────── --}}
        <div data-tab-panel="pix" role="tabpanel" @if($tab !== 'pix') hidden @endif>
            <div class="bx-card">
                <h2><i class="fas fa-qrcode" style="background: rgba(16,185,129,.12); color: var(--ds-success);"></i> Chave Pix da organização</h2>
                <p class="lead">Usada no QR Code das rifas. O dinheiro cai direto na conta da organização; a Vivensi não passa pelo pagamento.</p>

                @if($pixLegacy)
                    <div class="bx-note bx-note-info mb-3"><i class="fas fa-wand-magic-sparkles" style="color: var(--ds-brand);"></i>
                        <div>Sua chave estava gravada num formato antigo. Já ajustamos o QR Code das rifas; confira o tipo abaixo e clique em <strong>Salvar</strong> para gravar no formato certo.</div>
                    </div>
                @endif

                <span class="bx-label">Tipo da chave</span>
                <div class="bx-types" role="radiogroup" aria-label="Tipo da chave Pix">
                    @foreach(['cpf_cnpj' => 'fa-building', 'phone' => 'fa-mobile-screen', 'email' => 'fa-at', 'random' => 'fa-shuffle'] as $type => $icon)
                        <div class="bx-type">
                            <input type="radio" name="pix_key_type" id="pt-{{ $type }}" value="{{ $type }}" {{ $typeOld === $type ? 'checked' : '' }}>
                            <label for="pt-{{ $type }}"><i class="fas {{ $icon }}"></i>{{ \App\Support\PixKey::LABELS[$type] }}</label>
                        </div>
                    @endforeach
                </div>

                <label class="bx-label" for="pix_key">Chave Pix</label>
                <input type="text" class="bx-input @error('pix_key') is-bad @enderror" id="pix_key" name="pix_key" maxlength="100" autocomplete="off" spellcheck="false" value="{{ $keyOld }}" placeholder="Escolha o tipo acima">
                @error('pix_key')<p class="bx-err">{{ $message }}</p>@enderror
                @error('pix_key_type')<p class="bx-err">{{ $message }}</p>@enderror
                <div class="bx-final" id="pix-final" aria-live="polite"></div>

                <div id="owner-warning" class="bx-note bx-note-warn mt-2" @if(!$ownerWarning) style="display:none" @endif>
                    <i class="fas fa-triangle-exclamation" style="color: var(--ds-warning);"></i><div id="owner-warning-text">{{ $ownerWarning }}</div>
                </div>

                <div class="bx-note bx-note-info mt-3">
                    <i class="fas fa-id-card" style="color: var(--ds-text-faint);"></i>
                    <div>No app do banco, quem paga vê <strong>{{ $merchantName }}</strong> · {{ $merchantCity }}. Esse nome vem da razão social do cadastro da organização.</div>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <button type="button" class="bx-btn" id="pix-test-btn" onclick="testPix()"><i class="fas fa-vial"></i> Gerar Pix de teste de R$ 1,00</button>
                    <span class="bx-help" style="margin: 0;">Pague pelo app do banco e confira se cai na conta certa antes de divulgar uma rifa.</span>
                </div>
                <div class="bx-test" id="pix-test">
                    <canvas id="pix-test-qr" width="180" height="180" aria-label="QR Code de teste"></canvas>
                    <div style="flex: 1; min-width: 220px;">
                        <div style="font-weight: 800; margin-bottom: 4px;">Pix de teste: R$ 1,00</div>
                        <div class="bx-help" style="margin: 0 0 8px;">Recebedor: <strong id="pix-test-name"></strong> · chave <strong id="pix-test-key"></strong></div>
                        <textarea id="pix-test-code" rows="4" readonly aria-label="Pix Copia e Cola"></textarea>
                        <button type="button" class="bx-btn mt-2" onclick="copyTest(this)"><i class="fas fa-copy"></i> Copiar Pix Copia e Cola</button>
                    </div>
                </div>
            </div>

            <div class="bx-card">
                <h2><i class="fab fa-whatsapp" style="background: rgba(37,211,102,.12); color: #16a34a;"></i> WhatsApp para comprovantes</h2>
                <p class="lead">Na página do pedido, o comprador tem um botão para mandar o comprovante para este número.</p>
                <label class="bx-label" for="whatsapp_support">Número com DDD</label>
                <input type="tel" inputmode="numeric" class="bx-input @error('whatsapp_support') is-bad @enderror" id="whatsapp_support" name="whatsapp_support" maxlength="25" value="{{ $waOld ? \App\Support\PixKey::display('+' . ltrim($waOld, '+'), 'phone') : '' }}" placeholder="(16) 98839-2853" style="max-width: 320px;">
                @error('whatsapp_support')<p class="bx-err">{{ $message }}</p>@enderror
            </div>

            <div class="bx-card">
                @include('raffles.partials.authorization-info', ['compact' => true])
                <p class="bx-help" style="margin-top: 10px;">O número do certificado é informado em cada rifa, em <a href="{{ route('raffles.index') }}" style="color: var(--vv-link);">Rifas</a>, e aparece na página pública.</p>
            </div>
        </div>

        {{-- ─────────── Relatório semanal ─────────── --}}
        <div data-tab-panel="relatorio" role="tabpanel" @if($tab !== 'relatorio') hidden @endif>
            <div class="bx-card">
                <h2><i class="fas fa-robot" style="background: rgba(56,189,248,.14); color: #0284c7;"></i> Resumo da semana por e-mail</h2>
                <p class="lead">Todo domingo de manhã, o Bruce IA junta receitas, despesas e tarefas da semana e manda um resumo em texto.</p>
                <input type="hidden" name="weekly_report_enabled" value="0">
                <label class="bx-switch">
                    <input type="checkbox" name="weekly_report_enabled" value="1" {{ old('weekly_report_enabled', $tenant->weekly_report_enabled) ? 'checked' : '' }}>
                    Enviar o resumo todo domingo
                </label>
                <div style="margin-top: 16px; max-width: 420px;">
                    <label class="bx-label" for="report_email">E-mail que recebe</label>
                    <input type="email" class="bx-input @error('report_email') is-bad @enderror" id="report_email" name="report_email" maxlength="100" value="{{ old('report_email', $tenant->report_email) }}" placeholder="diretoria@suaong.org.br">
                    @error('report_email')<p class="bx-err">{{ $message }}</p>@enderror
                    <p class="bx-help">Em branco, vai para o e-mail do administrador da conta. Este e-mail também recebe os avisos de novas reservas das rifas.</p>
                </div>
            </div>
        </div>

        <div class="bx-save">
            <button type="submit" class="bx-btn bx-btn-primary"><i class="fas fa-check"></i> Salvar</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
<script>
(function () {
    // ── Abas ──
    const tabs = document.querySelectorAll('.bx-tab');
    function openTab(name) {
        tabs.forEach(t => t.setAttribute('aria-selected', t.dataset.tab === name ? 'true' : 'false'));
        document.querySelectorAll('[data-tab-panel]').forEach(p => p.hidden = p.dataset.tabPanel !== name);
        document.getElementById('current-tab').value = name;
        try { history.replaceState(null, '', '?tab=' + name); } catch (e) {}
    }
    tabs.forEach(t => t.addEventListener('click', () => openTab(t.dataset.tab)));
    window.openBrandingTab = openTab;

    // ── Chave Pix: máscara e prévia do formato final ──
    const keyInput = document.getElementById('pix_key');
    const finalBox = document.getElementById('pix-final');
    const placeholders = {
        cpf_cnpj: '00.000.000/0001-00',
        phone: '(16) 98839-2853',
        email: 'financeiro@suaong.org.br',
        random: '123e4567-e89b-12d3-a456-426614174000'
    };
    const currentType = () => (document.querySelector('input[name="pix_key_type"]:checked') || {}).value;

    function onlyDigits(v) { return v.replace(/\D/g, ''); }
    function maskDoc(v) {
        const d = v.replace(/[^0-9A-Za-z]/g, '').toUpperCase().slice(0, 14);
        if (d.length <= 11 && /^\d*$/.test(d)) {
            return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        return d.replace(/^(\w{2})(\w)/, '$1.$2').replace(/^(\w{2})\.(\w{3})(\w)/, '$1.$2.$3')
                .replace(/\.(\w{3})(\w)/, '.$1/$2').replace(/(\w{4})(\d{1,2})$/, '$1-$2');
    }
    function maskPhone(v) {
        let d = onlyDigits(v);
        if (d.length > 11 && d.startsWith('55')) d = d.slice(2);
        d = d.slice(0, 11);
        if (d.length > 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
        if (d.length > 2) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
        return d;
    }
    function finalKey(type, v) {
        v = v.trim();
        if (!v) return null;
        if (type === 'cpf_cnpj') { const d = v.replace(/[^0-9A-Za-z]/g, '').toUpperCase(); return (d.length === 11 || d.length === 14) ? d : null; }
        if (type === 'phone') { let d = onlyDigits(v); if (d.length === 11) d = '55' + d; return /^55[1-9][1-9]9\d{8}$/.test(d) ? '+' + d : null; }
        if (type === 'email') { const e = v.toLowerCase(); return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e) ? e : null; }
        if (type === 'random') { const u = v.toLowerCase().replace(/\s/g, ''); return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/.test(u) ? u : null; }
        return null;
    }
    function refresh(applyMask) {
        const type = currentType();
        keyInput.placeholder = type ? 'Ex: ' + placeholders[type] : 'Escolha o tipo acima';
        keyInput.inputMode = (type === 'cpf_cnpj' || type === 'phone') ? 'numeric' : (type === 'email' ? 'email' : 'text');
        if (applyMask) {
            if (type === 'cpf_cnpj') keyInput.value = maskDoc(keyInput.value);
            if (type === 'phone') keyInput.value = maskPhone(keyInput.value);
        }
        const v = keyInput.value;
        if (!v.trim()) { finalBox.innerHTML = ''; return; }
        if (!type) { finalBox.innerHTML = '<i class="fas fa-circle-info"></i> Escolha o tipo da chave.'; return; }
        const k = finalKey(type, v);
        finalBox.innerHTML = k
            ? '<i class="fas fa-check-circle" style="color: var(--ds-success)"></i> Vai para o QR Code assim: <strong class="bx-mono"></strong>'
            : '<i class="fas fa-circle-exclamation" style="color: var(--ds-warning)"></i> Ainda não parece uma chave de ' + document.querySelector('label[for="pt-' + type + '"]').textContent.trim().toLowerCase() + ' completa.';
        if (k) finalBox.querySelector('strong').textContent = k;
    }
    const hideWarn = () => { document.getElementById('owner-warning').style.display = 'none'; };
    keyInput.addEventListener('input', () => { refresh(true); hideWarn(); });
    document.querySelectorAll('input[name="pix_key_type"]').forEach(r => r.addEventListener('change', () => { refresh(true); hideWarn(); document.getElementById('pix-test').classList.remove('on'); }));
    refresh(false);

    const wa = document.getElementById('whatsapp_support');
    wa.addEventListener('input', () => { wa.value = maskPhone(wa.value); });

    // ── Pix de teste ──
    window.testPix = function () {
        const btn = document.getElementById('pix-test-btn');
        const box = document.getElementById('pix-test');
        const type = currentType();
        if (!type || !keyInput.value.trim()) { refresh(false); finalBox.innerHTML = '<i class="fas fa-circle-info"></i> Escolha o tipo e digite a chave para testar.'; return; }
        btn.disabled = true;
        fetch('{{ route('settings.branding.pix-preview') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ pix_key: keyInput.value, pix_key_type: type })
        }).then(r => r.json().then(j => ({ ok: r.ok, j }))).then(({ ok, j }) => {
            btn.disabled = false;
            const warn = document.getElementById('owner-warning');
            if (!ok || !j.ok) {
                box.classList.remove('on');
                finalBox.innerHTML = '<i class="fas fa-circle-exclamation" style="color: var(--ds-danger)"></i> <span></span>';
                finalBox.querySelector('span').textContent = j.message || (j.errors ? Object.values(j.errors)[0][0] : 'Chave inválida.');
                return;
            }
            document.getElementById('pix-test-name').textContent = j.name + ' · ' + j.city;
            document.getElementById('pix-test-key').textContent = j.display;
            document.getElementById('pix-test-code').value = j.payload;
            warn.style.display = j.warning ? '' : 'none';
            document.getElementById('owner-warning-text').textContent = j.warning || '';
            if (window.QRCode) QRCode.toCanvas(document.getElementById('pix-test-qr'), j.payload, { width: 180, margin: 1 }, function () {});
            box.classList.add('on');
        }).catch(() => { btn.disabled = false; });
    };
    window.copyTest = function (btn) {
        const t = document.getElementById('pix-test-code');
        (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(() => { t.select(); document.execCommand('copy'); })
            .finally(() => { const o = btn.innerHTML; btn.innerHTML = '<i class="fas fa-check"></i> Copiado'; setTimeout(() => btn.innerHTML = o, 2000); });
    };
})();

function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        ['logo-preview', 'mini-logo'].forEach(id => { const el = document.getElementById(id); el.src = e.target.result; el.style.opacity = '1'; });
        document.getElementById('logo-filename').textContent = input.files[0].name + ' (clique em Salvar para aplicar)';
    };
    reader.readAsDataURL(input.files[0]);
}

function updateColor(hex) {
    document.getElementById('color-hex').textContent = hex.toUpperCase();
    const a = document.getElementById('mini-active');
    a.style.background = hex + '26';
    a.style.boxShadow = 'inset 3px 0 0 ' + hex;
    a.querySelector('i').style.color = hex;
}
updateColor(document.getElementById('brand_color').value);

function removeLogo() {
    if (!confirm('Remover o logo e voltar ao logo padrão da Vivensi?')) return;
    fetch('{{ route('settings.branding.remove-logo') }}', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => { if (d.success) window.location.reload(); });
}
</script>
@endsection
