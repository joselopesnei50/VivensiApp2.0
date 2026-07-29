@extends('layouts.app')
@section('title', 'Novo Post Agendado')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('social.posts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="h4 fw-bold mb-0">Novo Post</h1>
            <p class="text-muted small mb-0">Crie e agende um post para suas redes sociais.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <!-- Formulário -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('social.posts.store') }}" method="POST" enctype="multipart/form-data" id="postForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-600">Conta</label>
                            @if($accounts->isEmpty())
                                <div class="alert alert-warning border-0 rounded-3 py-2 px-3 mb-2" style="font-size:.82rem;">
                                    <i class="fas fa-circle-info me-1"></i>
                                    Nenhuma conta conectada ainda. O post será salvo como <strong>rascunho</strong> e poderá ser publicado quando você conectar uma conta em <a href="{{ route('social.accounts') }}">Redes Sociais</a>.
                                </div>
                                <input type="hidden" name="social_account_id" value="">
                            @else
                                <select name="social_account_id" class="form-select form-select-lg" id="accountSelect">
                                    <option value="">— Sem conta (salvar como rascunho) —</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}"
                                            data-has-ig="{{ $account->instagram_business_id ? '1' : '0' }}"
                                            {{ request('account') == $account->id ? 'selected' : '' }}>
                                            {{ $account->page_name }}
                                            @if($account->instagram_username) · @{{ $account->instagram_username }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div id="draftNotice" class="text-warning small mt-1" style="display:none;">
                                    <i class="fas fa-circle-info me-1"></i> Sem conta selecionada — post salvo como rascunho.
                                </div>
                            @endif
                            @error('social_account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Formato — Feed padrão OR Story 24h --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Formato</label>
                            <div class="d-flex gap-2" id="formatOptions">
                                <label class="platform-chip active" data-val="feed" onclick="wacSetFormat('feed')">
                                    <input type="radio" name="format" value="feed" checked class="d-none">
                                    <i class="fas fa-newspaper me-1"></i> Feed
                                </label>
                                <label class="platform-chip" data-val="story" onclick="wacSetFormat('story')">
                                    <input type="radio" name="format" value="story" class="d-none">
                                    <i class="fas fa-clock-rotate-left me-1"></i> Story (24h)
                                </label>
                            </div>
                            <div class="form-text" id="wacFormatHint">
                                Feed é o post normal. <strong>Story</strong> dura 24h no Instagram e some
                                — só publica no Instagram (Facebook Stories não é suportado pela API oficial da Meta).
                                Story usa <strong>apenas a primeira mídia</strong> (não faz carrossel).
                            </div>
                        </div>

                        <div class="mb-4" id="platformSection">
                            <label for="platform" class="form-label fw-600">Publicar em <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2" id="platformOptions">
                                <label class="platform-chip active" data-val="facebook" id="fbOption">
                                    <input type="radio" name="platform" value="facebook" checked class="d-none" id="platform">
                                    <i class="fab fa-facebook me-1"></i> Facebook
                                </label>
                                <label for="platform" class="platform-chip" data-val="instagram" id="igOption" style="display:none;">
                                    <input type="radio" name="platform" value="instagram" class="d-none" id="platform">
                                    <i class="fab fa-instagram me-1"></i> Instagram
                                </label>
                                <label class="platform-chip" data-val="both" id="bothOption" style="display:none;">
                                    <input type="radio" name="platform" value="both" class="d-none" id="platform">
                                    <i class="fas fa-share-nodes me-1"></i> Ambos
                                </label>
                            </div>
                        </div>

                        <script>
                            // Alterna Feed / Story: quando escolhe Story, força
                            // Instagram (Facebook Story via API não é suportado).
                            function wacSetFormat(f) {
                                document.querySelectorAll('#formatOptions .platform-chip').forEach(el =>
                                    el.classList.toggle('active', el.dataset.val === f));
                                document.querySelector(`input[name="format"][value="${f}"]`).checked = true;

                                const fbChip   = document.getElementById('fbOption');
                                const igChip   = document.getElementById('igOption');
                                const bothChip = document.getElementById('bothOption');

                                if (f === 'story') {
                                    // Story só no Instagram. Esconde FB/Ambos e força IG selecionado.
                                    fbChip.style.display   = 'none';
                                    bothChip.style.display = 'none';
                                    igChip.style.display   = 'inline-flex';
                                    document.querySelectorAll('#platformOptions .platform-chip').forEach(el =>
                                        el.classList.toggle('active', el.dataset.val === 'instagram'));
                                    igChip.querySelector('input').checked = true;
                                } else {
                                    // Feed: mostra tudo de novo (o script legado já lida com IG/Ambos
                                    // baseado no account.instagram_business_id).
                                    fbChip.style.display = 'inline-flex';
                                    // igChip e bothChip são reexibidos pelo script legado quando
                                    // account tem instagram_business_id.
                                }
                            }
                        </script>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-600 mb-0">Legenda <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-xs btn-outline-primary" id="aiBtn" style="font-size:.75rem;padding:3px 10px;">
                                    <i class="fas fa-wand-magic-sparkles me-1"></i> Gerar com IA
                                </button>
                            </div>
                            <textarea name="caption" id="caption" class="form-control" rows="6"
                                      placeholder="Escreva a legenda do post..." required maxlength="2200">{{ old('caption') }}</textarea>
                            <div class="d-flex justify-content-between mt-1">
                                @error('caption') <div class="text-danger small">{{ $message }}</div> @else <span></span> @enderror
                                <span class="text-muted" style="font-size:.7rem;" id="charCount">0 / 2200</span>
                            </div>
                        </div>

                        <!-- AI panel -->
                        <div id="aiPanel" class="mb-4 p-3 rounded-3 bg-light border" style="display:none;">
                            <p class="small fw-600 mb-2"><i class="fas fa-robot me-1 text-primary"></i> Gerar legenda com IA</p>
                            <div class="input-group">
                                <input type="text" id="aiTopic" class="form-control form-control-sm"
                                       placeholder="Descreva o tema do post...">
                                <button type="button" class="btn btn-sm btn-primary" id="aiGenerate">Gerar</button>
                            </div>
                            <div id="aiLoading" class="text-muted small mt-2" style="display:none;">
                                <i class="fas fa-spinner fa-spin me-1"></i> Gerando legenda...
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="mediaFilesInput" class="form-label fw-600">
                                Mídia (opcional — múltiplas = carrossel)
                            </label>
                            <input type="file" name="media_files[]" class="form-control"
                                   accept="image/*,video/mp4" multiple
                                   id="mediaFilesInput" onchange="wacHandleMediaFiles(this)">
                            <div class="form-text">
                                JPG, PNG, GIF ou MP4. Máx 50 MB por arquivo. Até 10 mídias.
                                <strong>Selecione 2 ou mais imagens</strong> para publicar como
                                <span class="text-primary fw-bold"><i class="fab fa-instagram"></i> carrossel no Instagram</span>.
                                No Facebook aparece a primeira imagem.
                            </div>
                            <div id="mediaPreview" class="mt-3 d-flex flex-wrap gap-2" style="display:none;"></div>
                            <div id="mediaCounter" class="mt-2 small text-muted" style="display:none;">
                                <span id="mediaCount">0</span>/10 mídias selecionadas
                                <span id="mediaCarouselHint" style="display:none;" class="badge bg-primary bg-opacity-10 text-primary ms-2">
                                    <i class="fas fa-images"></i> Carrossel Instagram
                                </span>
                            </div>
                        </div>

                        <script>
                            // Guardamos os arquivos selecionados manualmente pra permitir
                            // remoção individual (input[type=file] não permite remover
                            // arquivos individualmente após a seleção).
                            const __wacMediaFiles = [];

                            function wacHandleMediaFiles(input) {
                                for (const f of input.files) {
                                    if (__wacMediaFiles.length >= 10) break;
                                    __wacMediaFiles.push(f);
                                }
                                input.value = ''; // reseta o input pra permitir reselecionar o mesmo arquivo
                                wacRenderPreviews();
                                wacRebuildFileInput();
                            }

                            function wacRebuildFileInput() {
                                // Reconstroi o FileList do input a partir do array
                                // interno (pra o form submitir os arquivos corretos)
                                const dt = new DataTransfer();
                                __wacMediaFiles.forEach(f => dt.items.add(f));
                                document.getElementById('mediaFilesInput').files = dt.files;
                            }

                            function wacRemoveMedia(idx) {
                                __wacMediaFiles.splice(idx, 1);
                                wacRenderPreviews();
                                wacRebuildFileInput();
                            }

                            function wacRenderPreviews() {
                                const wrap = document.getElementById('mediaPreview');
                                const counter = document.getElementById('mediaCounter');
                                const countEl = document.getElementById('mediaCount');
                                const hint = document.getElementById('mediaCarouselHint');

                                wrap.innerHTML = '';
                                if (__wacMediaFiles.length === 0) {
                                    wrap.style.display = 'none';
                                    counter.style.display = 'none';
                                    return;
                                }
                                wrap.style.display = 'flex';
                                counter.style.display = 'block';
                                countEl.innerText = __wacMediaFiles.length;
                                hint.style.display = __wacMediaFiles.length >= 2 ? 'inline-block' : 'none';

                                if (typeof wacSyncSidePreview === 'function') wacSyncSidePreview();

                                __wacMediaFiles.forEach((file, idx) => {
                                    const url = URL.createObjectURL(file);
                                    const isVideo = file.type.startsWith('video/');

                                    const box = document.createElement('div');
                                    box.style.cssText = 'position:relative; width:100px; height:100px; border-radius:10px; overflow:hidden; background:#f1f5f9; border:1px solid #e2e8f0;';
                                    box.innerHTML = `
                                        ${isVideo
                                            ? `<video src="${url}" muted style="width:100%; height:100%; object-fit:cover;"></video>
                                               <div style="position:absolute; top:4px; left:4px; background:rgba(15,23,42,.7); color:#fff; padding:2px 6px; border-radius:4px; font-size:.65rem;"><i class="fas fa-video"></i></div>`
                                            : `<img src="${url}" style="width:100%; height:100%; object-fit:cover;">`
                                        }
                                        <div style="position:absolute; top:4px; right:4px;">
                                            <button type="button" onclick="wacRemoveMedia(${idx})"
                                                    style="background:rgba(220,38,38,.9); color:#fff; border:0; border-radius:50%; width:22px; height:22px; font-size:.7rem; line-height:1; cursor:pointer;"
                                                    title="Remover">×</button>
                                        </div>
                                        <div style="position:absolute; bottom:4px; left:4px; background:rgba(15,23,42,.7); color:#fff; padding:2px 6px; border-radius:4px; font-size:.65rem; font-weight:700;">
                                            ${idx + 1}
                                        </div>
                                    `;
                                    wrap.appendChild(box);
                                });
                            }
                        </script>

                        {{-- Toggle "Publicar agora" — se marcado, esconde o campo de data e publica imediatamente --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="publish_now" name="publish_now" value="1"
                                       {{ old('publish_now') ? 'checked' : '' }}
                                       onchange="wacTogglePublishNow(this.checked)">
                                <label class="form-check-label fw-600" for="publish_now">
                                    <i class="fas fa-bolt text-warning"></i> Publicar agora
                                </label>
                                <div class="text-muted small">Ao marcar, o post vai pro Facebook/Instagram imediatamente após salvar.</div>
                            </div>
                        </div>

                        <div class="mb-4" id="wac-schedule-field">
                            <label class="form-label fw-600">Data e hora de publicação <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" class="form-control form-control-lg"
                                   value="{{ old('scheduled_at') }}"
                                   min="{{ now('America/Sao_Paulo')->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            <div class="form-text">Horário de Brasília (GMT-3).</div>
                            @error('scheduled_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold" id="wac-submit-btn">
                                <i class="fas fa-calendar-check me-2"></i>
                                <span id="wac-submit-label">Agendar Post</span>
                            </button>
                            <a href="{{ route('social.posts.index') }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                        </div>

                        <script>
                            function wacTogglePublishNow(checked) {
                                const field = document.getElementById('wac-schedule-field');
                                const input = field.querySelector('input[name="scheduled_at"]');
                                const label = document.getElementById('wac-submit-label');
                                const btn   = document.getElementById('wac-submit-btn');
                                if (checked) {
                                    field.style.display = 'none';
                                    input.required = false;
                                    label.innerText = 'Publicar Agora';
                                    btn.classList.remove('btn-primary');
                                    btn.classList.add('btn-success');
                                } else {
                                    field.style.display = '';
                                    input.required = true;
                                    label.innerText = 'Agendar Post';
                                    btn.classList.add('btn-primary');
                                    btn.classList.remove('btn-success');
                                }
                            }
                            // Inicializa no load caso venha do old()
                            (function () {
                                const cb = document.getElementById('publish_now');
                                if (cb && cb.checked) wacTogglePublishNow(true);
                            })();
                        </script>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm position-sticky" style="top:90px;">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4">
                    <h6 class="fw-bold text-muted mb-0" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">
                        Preview do Post
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                            <i class="fab fa-facebook text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:.85rem;" id="previewPageName">Sua Página</div>
                            <div class="text-muted" style="font-size:.7rem;">Agora</div>
                        </div>
                    </div>
                    <p class="mb-2" style="font-size:.88rem;white-space:pre-wrap;" id="previewCaption">A legenda aparecerá aqui...</p>
                    <div id="previewImg" style="display:none;">
                        <img loading="lazy" src="" alt="" class="rounded w-100" style="max-height:200px;object-fit:cover;" id="previewImgEl">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.platform-chip {
    display:inline-flex;align-items:center;padding:7px 14px;
    border:1.5px solid #e2e8f0;border-radius:20px;
    font-size:.82rem;font-weight:600;color:#64748b;cursor:pointer;
    transition:all .15s;
}
.platform-chip.active { background:#4f6ef7;border-color:#4f6ef7;color:#fff; }
.platform-chip:hover:not(.active) { border-color:#4f6ef7;color:#4f6ef7; }
.fw-600 { font-weight:600; }
</style>

<script>
// Platform chips
document.querySelectorAll('.platform-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.platform-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        chip.querySelector('input').checked = true;
    });
});

// Mostrar opções de Instagram se conta tiver Instagram + aviso de rascunho
const accountSel = document.getElementById('accountSelect');
if (accountSel) {
    accountSel.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const hasIg = opt.dataset.hasIg === '1';
        const isDraft = !this.value;
        document.getElementById('igOption') && (document.getElementById('igOption').style.display  = hasIg ? '' : 'none');
        document.getElementById('bothOption') && (document.getElementById('bothOption').style.display = hasIg ? '' : 'none');
        document.getElementById('previewPageName').textContent = isDraft ? 'Rascunho' : (opt.text || 'Sua Página');
        const notice = document.getElementById('draftNotice');
        if (notice) notice.style.display = isDraft ? '' : 'none';
        // Alterar texto do botão de submit
        const btn = document.querySelector('button[type=submit]');
        btn.innerHTML = isDraft
            ? '<i class="fas fa-floppy-disk me-2"></i> Salvar Rascunho'
            : '<i class="fas fa-calendar-check me-2"></i> Agendar Post';
    });
}

// Char counter
const captionEl = document.getElementById('caption');
const charCount  = document.getElementById('charCount');
captionEl.addEventListener('input', () => {
    charCount.textContent = captionEl.value.length + ' / 2200';
    document.getElementById('previewCaption').textContent = captionEl.value || 'A legenda aparecerá aqui...';
});

// Preview lateral (card estilo Facebook/Instagram) — usa a PRIMEIRA mídia
// selecionada. wacRenderPreviews (definido junto ao input múltiplo) chama
// esta função pra manter o preview lateral sincronizado.
function wacSyncSidePreview() {
    const box  = document.getElementById('previewImg');
    const img  = document.getElementById('previewImgEl');
    const first = (typeof __wacMediaFiles !== 'undefined' && __wacMediaFiles[0]) || null;
    if (!first || first.type.startsWith('video')) {
        // Sem imagem OU primeira é vídeo → esconde imagem do preview
        box.style.display = 'none';
        return;
    }
    img.src = URL.createObjectURL(first);
    box.style.display = '';
}

// AI caption
document.getElementById('aiBtn').addEventListener('click', () => {
    const panel = document.getElementById('aiPanel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
});

document.getElementById('aiGenerate').addEventListener('click', async () => {
    const topic    = document.getElementById('aiTopic').value.trim();
    const platform = document.querySelector('input[name="platform"]:checked')?.value || 'facebook';
    if (!topic) { alert('Descreva o tema do post primeiro.'); return; }

    document.getElementById('aiLoading').style.display = '';
    document.getElementById('aiGenerate').disabled = true;

    try {
        const res  = await fetch('{{ route('social.posts.generate-caption') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ topic, platform }),
        });
        const data = await res.json();
        if (data.caption) {
            captionEl.value = data.caption;
            captionEl.dispatchEvent(new Event('input'));
            document.getElementById('aiPanel').style.display = 'none';
        } else {
            alert(data.error || 'Erro ao gerar legenda.');
        }
    } catch(e) {
        alert('Erro de conexão.');
    } finally {
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('aiGenerate').disabled = false;
    }
});
</script>
@endsection
