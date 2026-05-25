@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Resultado da IA</h6>
        <h2 style="margin: 0; color: #111827;">Sua Estratégia Pronta</h2>
    </div>
    <a href="{{ route('marketing.index') }}" class="btn btn-outline-secondary">Nova Estratégia</a>
</div>

<div class="row">
    <!-- COLUNA 1: REDES SOCIAIS -->
    <div class="col-lg-6 mb-4">
        <h4 class="mb-3 fw-bold text-primary"><i class="fab fa-instagram me-2"></i> Redes Sociais (Atração)</h4>
        
        @foreach($strategy['social'] as $post)
        <div class="vivensi-card mb-4 p-0 overflow-hidden">
            @if(isset($post['images']) && count($post['images']) > 0)
                <div id="carousel-{{ $loop->index }}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($post['images'] as $key => $img)
                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                <img loading="lazy" src="{{ $img['url_regular'] }}" class="d-block w-100" style="height: 250px; object-fit: cover;" alt="Unsplash Image">
                                <div class="carousel-caption d-none d-md-block p-1" style="background: rgba(0,0,0,0.5); bottom: 0;">
                                    <small>Foto por <a href="{{ $img['photographer_url'] }}" target="_blank" class="text-white">{{ $img['photographer'] }}</a> no Unsplash</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-{{ $loop->index }}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-{{ $loop->index }}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            @else
                <div style="height: 200px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b;">
                    <i class="fas fa-image fa-2x"></i>
                </div>
            @endif

            <div class="p-4">
                <h5 class="fw-bold mb-3">{{ $post['title'] }}</h5>
                <div class="bg-light p-3 rounded mb-3" style="font-size: 0.9rem; white-space: pre-line;" data-caption>
                    {{ $post['caption'] }}
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(this)">
                        <i class="fas fa-copy"></i> Copiar Legenda
                    </button>
                    @if($socialAccounts->isNotEmpty())
                    <button class="btn btn-sm btn-primary" onclick="openScheduleModal(this)"
                        data-caption="{{ $post['caption'] }}"
                        data-img="{{ $post['images'][0]['url_regular'] ?? '' }}"
                        data-imgs="{{ json_encode(array_column($post['images'] ?? [], 'url_regular')) }}">
                        <i class="fas fa-calendar-plus me-1"></i> Agendar Post
                    </button>
                    @else
                    <a href="{{ route('social.accounts') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-plug me-1"></i> Conectar conta para agendar
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- COLUNA 2: LANDING PAGE -->
    <div class="col-lg-6 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-success"><i class="fas fa-globe me-2"></i> Landing Page (Conversão)</h4>
            <!-- MAGIC BUTTON -->
            <button class="btn btn-success fw-bold shadow-sm" onclick="sendToBuilder()">
                <i class="fas fa-magic me-2"></i> Criar Página com IA
            </button>
        </div>

        <div class="vivensi-card">
            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">HERO HEADLINE (H1)</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="{{ $strategy['landing_page']['hero_headline'] }}" id="lp_headline" readonly>
                    <button class="btn btn-outline-secondary" onclick="copyInput('lp_headline')"><i class="fas fa-copy"></i></button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">HERO SUBHEADLINE (H2)</label>
                <textarea class="form-control" rows="2" id="lp_subheadline" readonly>{{ $strategy['landing_page']['hero_subheadline'] }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">CTA BUTTON</label>
                <input type="text" class="form-control" value="{{ $strategy['landing_page']['cta_button'] }}" id="lp_cta" readonly>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">BENEFÍCIOS / VANTAGENS</label>
                <ul class="list-group list-group-flush">
                    @foreach($strategy['landing_page']['benefits_list'] as $key => $benefit)
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-check text-success me-2"></i> <span id="lp_benefit_{{ $key }}">{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">SOBRE / CAUSA</label>
                <h6 class="fw-bold">{{ $strategy['landing_page']['about_title'] }}</h6>
                <p class="text-muted" id="lp_about_text">{{ $strategy['landing_page']['about_text'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Magic Fill (Landing Page) -->
<form id="magic-form" action="{{ route('ngo.landing-pages.create_magic') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="strategy_json" value="{{ json_encode($strategy['landing_page']) }}">
</form>

<!-- Modal: Agendar Post Social -->
<div class="modal fade" id="scheduleModal" role="dialog" aria-modal="true" aria-labelledby="scheduleModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="scheduleModalLabel"><i class="fas fa-calendar-plus me-2 text-primary"></i>Agendar Post nas Redes Sociais</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('social.posts.store') }}" method="POST" id="scheduleForm">
                @csrf
                <input type="hidden" name="media_url_external" id="sm_media_url">
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <!-- Preview da imagem -->
                        <div class="col-md-4">
                            <label class="form-label fw-600 small text-muted">IMAGEM SELECIONADA</label>
                            <div id="sm_img_preview_wrap" class="rounded overflow-hidden mb-2" style="height:160px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                                <img loading="lazy" id="sm_img_preview" src="" alt="" class="w-100 h-100" style="object-fit:cover;display:none;">
                                <i id="sm_img_placeholder" class="fas fa-image fa-2x text-muted"></i>
                            </div>
                            <div id="sm_img_selector" class="d-flex gap-1 flex-wrap"></div>
                        </div>

                        <!-- Campos do formulário -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-600 small text-muted">CONTA</label>
                                <select name="social_account_id" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    @foreach($socialAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->page_name }}
                                            @if($account->instagram_username) · @{{ $account->instagram_username }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600 small text-muted">PUBLICAR EM</label>
                                <div class="d-flex gap-2">
                                    <label class="platform-chip active" data-val="facebook">
                                        <input type="radio" name="platform" value="facebook" checked class="d-none">
                                        <i class="fab fa-facebook me-1"></i> Facebook
                                    </label>
                                    <label class="platform-chip" data-val="instagram">
                                        <input type="radio" name="platform" value="instagram" class="d-none">
                                        <i class="fab fa-instagram me-1"></i> Instagram
                                    </label>
                                    <label class="platform-chip" data-val="both">
                                        <input type="radio" name="platform" value="both" class="d-none">
                                        <i class="fas fa-share-nodes me-1"></i> Ambos
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600 small text-muted">DATA E HORA</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" required
                                       min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <!-- Legenda -->
                        <div class="col-12">
                            <label class="form-label fw-600 small text-muted d-flex justify-content-between">
                                <span>LEGENDA</span>
                                <span id="sm_char_count" class="text-muted" style="font-size:.7rem;font-weight:400;">0 / 2200</span>
                            </label>
                            <textarea name="caption" id="sm_caption" class="form-control" rows="5" required maxlength="2200"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-calendar-check me-2"></i> Agendar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.platform-chip {
    display:inline-flex;align-items:center;padding:6px 12px;
    border:1.5px solid #e2e8f0;border-radius:20px;
    font-size:.82rem;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;
}
.platform-chip.active { background:#4f6ef7;border-color:#4f6ef7;color:#fff; }
.platform-chip:hover:not(.active) { border-color:#4f6ef7;color:#4f6ef7; }
.fw-600 { font-weight:600; }
.sm-thumb {
    width:44px;height:44px;object-fit:cover;border-radius:6px;cursor:pointer;
    border:2px solid transparent;transition:all .15s;opacity:.7;
}
.sm-thumb.selected { border-color:#4f6ef7;opacity:1; }
</style>

<script>
    function copyToClipboard(btn) {
        const text = btn.closest('.p-4').querySelector('[data-caption]').innerText;
        navigator.clipboard.writeText(text);
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        setTimeout(() => btn.innerHTML = original, 2000);
    }

    function copyInput(id) {
        const el = document.getElementById(id);
        el.select();
        el.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(el.value);
    }

    function sendToBuilder() {
        if(confirm('Você será redirecionado para o Construtor e esta estratégia será aplicada automaticamente. Continuar?')) {
            document.getElementById('magic-form').submit();
        }
    }

    function openScheduleModal(btn) {
        const caption  = btn.dataset.caption;
        const imgs     = JSON.parse(btn.dataset.imgs || '[]');
        const firstImg = imgs[0] || '';

        // Preenche caption
        const captionEl = document.getElementById('sm_caption');
        captionEl.value = caption;
        document.getElementById('sm_char_count').textContent = caption.length + ' / 2200';

        // Preenche imagem
        setModalImage(firstImg);

        // Monta thumbnails para seleção
        const selectorEl = document.getElementById('sm_img_selector');
        selectorEl.innerHTML = '';
        imgs.forEach((url, i) => {
            if (!url) return;
            const img = document.createElement('img');
            img.src = url;
            img.className = 'sm-thumb' + (i === 0 ? ' selected' : '');
            img.title = 'Selecionar imagem ' + (i + 1);
            img.onclick = function() {
                document.querySelectorAll('.sm-thumb').forEach(t => t.classList.remove('selected'));
                this.classList.add('selected');
                setModalImage(url);
            };
            selectorEl.appendChild(img);
        });

        new bootstrap.Modal(document.getElementById('scheduleModal')).show();
    }

    function setModalImage(url) {
        const preview = document.getElementById('sm_img_preview');
        const placeholder = document.getElementById('sm_img_placeholder');
        document.getElementById('sm_media_url').value = url || '';
        if (url) {
            preview.src = url;
            preview.style.display = '';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = '';
        }
    }

    // Platform chips no modal
    document.querySelectorAll('#scheduleModal .platform-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            chip.closest('.d-flex').querySelectorAll('.platform-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            chip.querySelector('input').checked = true;
        });
    });

    // Char counter
    document.getElementById('sm_caption').addEventListener('input', function() {
        document.getElementById('sm_char_count').textContent = this.value.length + ' / 2200';
    });
</script>
@endsection
