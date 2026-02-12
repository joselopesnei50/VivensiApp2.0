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
                                <img src="{{ $img['url_regular'] }}" class="d-block w-100" style="height: 250px; object-fit: cover;" alt="Unsplash Image">
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
                <div class="bg-light p-3 rounded mb-3" style="font-size: 0.9rem; white-space: pre-line;">
                    {{ $post['caption'] }}
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard(this)">
                    <i class="fas fa-copy"></i> Copiar Legenda
                </button>
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

<!-- Modal para Magic Fill -->
<form id="magic-form" action="{{ route('ngo.landing-pages.create_magic') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="strategy_json" value="{{ json_encode($strategy['landing_page']) }}">
</form>

<script>
    function copyToClipboard(btn) {
        const text = btn.previousElementSibling.innerText;
        navigator.clipboard.writeText(text);
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        setTimeout(() => btn.innerHTML = original, 2000);
    }

    function copyInput(id) {
        const copyText = document.getElementById(id);
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        alert("Texto copiado!");
    }

    function sendToBuilder() {
        if(confirm('Você será redirecionado para o Construtor e esta estratégia será aplicada automaticamente. Continuar?')) {
            document.getElementById('magic-form').submit();
        }
    }
</script>
@endsection
