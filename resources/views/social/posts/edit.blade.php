@extends('layouts.app')
@section('title', 'Editar Post Agendado')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('social.posts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="h4 fw-bold mb-0">Editar Post</h1>
            <p class="text-muted small mb-0">Altere a legenda, plataforma ou horário de publicação.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('social.posts.update', $post) }}" method="POST" id="editForm">
                        @csrf @method('PUT')

                        <div class="mb-4">
                            <label class="form-label fw-600">Conta</label>
                            <div class="form-control bg-light text-muted">
                                {{ $post->account?->page_name ?? '—' }}
                                <small class="ms-2 text-muted">(não pode ser alterada)</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600">Publicar em <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2" id="platformOptions">
                                <label class="platform-chip {{ $post->platform === 'facebook' ? 'active' : '' }}" data-val="facebook">
                                    <input type="radio" name="platform" value="facebook" {{ $post->platform === 'facebook' ? 'checked' : '' }} class="d-none">
                                    <i class="fab fa-facebook me-1"></i> Facebook
                                </label>
                                @if($post->account?->instagram_business_id)
                                <label class="platform-chip {{ $post->platform === 'instagram' ? 'active' : '' }}" data-val="instagram">
                                    <input type="radio" name="platform" value="instagram" {{ $post->platform === 'instagram' ? 'checked' : '' }} class="d-none">
                                    <i class="fab fa-instagram me-1"></i> Instagram
                                </label>
                                <label class="platform-chip {{ $post->platform === 'both' ? 'active' : '' }}" data-val="both">
                                    <input type="radio" name="platform" value="both" {{ $post->platform === 'both' ? 'checked' : '' }} class="d-none">
                                    <i class="fas fa-share-nodes me-1"></i> Ambos
                                </label>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-600 mb-0">Legenda <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-xs btn-outline-primary" id="aiBtn" style="font-size:.75rem;padding:3px 10px;">
                                    <i class="fas fa-wand-magic-sparkles me-1"></i> Gerar com IA
                                </button>
                            </div>
                            <textarea name="caption" id="caption" class="form-control" rows="6"
                                      required maxlength="2200">{{ old('caption', $post->caption) }}</textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <span class="text-muted" style="font-size:.7rem;" id="charCount">{{ strlen($post->caption) }} / 2200</span>
                            </div>
                        </div>

                        <div id="aiPanel" class="mb-4 p-3 rounded-3 bg-light border" style="display:none;">
                            <p class="small fw-600 mb-2"><i class="fas fa-robot me-1 text-primary"></i> Gerar legenda com IA</p>
                            <div class="input-group">
                                <input type="text" id="aiTopic" class="form-control form-control-sm" placeholder="Descreva o tema do post...">
                                <button type="button" class="btn btn-sm btn-primary" id="aiGenerate">Gerar</button>
                            </div>
                            <div id="aiLoading" class="text-muted small mt-2" style="display:none;">
                                <i class="fas fa-spinner fa-spin me-1"></i> Gerando legenda...
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600">Data e hora de publicação <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" class="form-control form-control-lg"
                                   value="{{ old('scheduled_at', $post->scheduled_at->format('Y-m-d\TH:i')) }}" required
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        @if($post->media_url)
                        <div class="mb-4">
                            <label class="form-label fw-600">Mídia atual</label>
                            @if($post->media_type === 'video')
                                <video controls class="rounded d-block" style="max-height:160px;max-width:100%;"></video>
                            @else
                                <img loading="lazy" src="{{ $post->media_url }}" alt="" class="rounded" style="max-height:160px;max-width:100%;object-fit:cover;">
                            @endif
                            <div class="form-text text-muted">A mídia não pode ser alterada na edição.</div>
                        </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold">
                                <i class="fas fa-save me-2"></i> Salvar Alterações
                            </button>
                            <a href="{{ route('social.posts.index') }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm position-sticky" style="top:90px;">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4">
                    <h6 class="fw-bold text-muted mb-0" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Preview</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                            <i class="fab fa-facebook text-primary"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:.85rem;">{{ $post->account?->page_name ?? 'Sua Página' }}</div>
                            <div class="text-muted" style="font-size:.7rem;">Agendado para {{ $post->scheduled_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    <p class="mb-2" style="font-size:.88rem;white-space:pre-wrap;" id="previewCaption">{{ $post->caption }}</p>
                    @if($post->media_url && $post->media_type === 'image')
                    <img loading="lazy" src="{{ $post->media_url }}" alt="" class="rounded w-100" style="max-height:200px;object-fit:cover;">
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.platform-chip {
    display:inline-flex;align-items:center;padding:7px 14px;
    border:1.5px solid #e2e8f0;border-radius:20px;
    font-size:.82rem;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;
}
.platform-chip.active { background:#4f6ef7;border-color:#4f6ef7;color:#fff; }
.platform-chip:hover:not(.active) { border-color:#4f6ef7;color:#4f6ef7; }
.fw-600 { font-weight:600; }
</style>

<script>
document.querySelectorAll('.platform-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.platform-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        chip.querySelector('input').checked = true;
    });
});

const captionEl = document.getElementById('caption');
const charCount  = document.getElementById('charCount');
captionEl.addEventListener('input', () => {
    charCount.textContent = captionEl.value.length + ' / 2200';
    document.getElementById('previewCaption').textContent = captionEl.value || '';
});

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
