@extends('layouts.app')

@section('content')
<div class="header-page mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('raffles.index') }}" class="btn btn-outline-secondary rounded-circle p-2 border-1 shadow-sm">
            <i class="bi bi-arrow-left fs-5"></i>
        </a>
        <div>
            <h6 class="text-primary fw-700 text-uppercase mb-1 ls-1" style="font-size: 0.75rem;">Marketing Digital</h6>
            <h2 class="fw-800 mb-0">Nova Campanha de Rifa</h2>
            <p class="text-muted small mb-0">Configure os prêmios e as regras do sorteio.</p>
        </div>
    </div>
</div>

<div class="row justify-content-center pb-5">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('raffles.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row g-4">
                        <!-- Left Column: Info -->
                        <div class="col-lg-7">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                <span class="badge bg-primary-soft text-primary rounded-pill p-2"><i class="bi bi-info-circle"></i></span>
                                Detalhes da Campanha
                            </h5>

                            <div class="mb-3">
                                <label class="form-label small fw-700 text-muted text-uppercase">Título da Campanha</label>
                                <input type="text" name="title" class="form-control form-control-vivensi" placeholder="Ex: Rifa de Natal - iPhone 15 Pro" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-700 text-muted text-uppercase">Descrição e Prêmios</label>
                                <textarea name="description" rows="3" class="form-control form-control-vivensi" placeholder="O que será sorteado? Detalhe os prêmios aqui..." required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-700 text-muted text-uppercase">Regras e Termos</label>
                                <textarea name="rules" rows="3" class="form-control form-control-vivensi" placeholder="Quais as regras do sorteio? Data da entrega, frete por conta de quem, etc..."></textarea>
                            </div>
                        </div>

                        <!-- Right Column: Settings & Image -->
                        <div class="col-lg-5">
                            <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                                <span class="badge bg-success-soft text-success rounded-pill p-2"><i class="bi bi-gear-fill"></i></span>
                                Regras e Mídia
                            </h5>

                            <div class="mb-4">
                                <label class="form-label small fw-700 text-muted text-uppercase">Imagem da Capa</label>
                                <div class="image-upload-box" onclick="document.getElementById('raffleImage').click()">
                                    <i class="bi bi-image fs-1 opacity-25"></i>
                                    <span class="small mt-2 text-muted">Clique para enviar imagem</span>
                                    <input type="file" name="image" id="raffleImage" class="d-none" accept="image/*" onchange="previewImage(this)">
                                    <img loading="lazy" id="imagePreview" class="preview-img d-none">
                                </div>
                                <small class="text-muted mt-2 d-block">Recomendado: 800x400px (Máx 2MB)</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-700 text-muted text-uppercase">Preço (R$)</label>
                                    <input type="number" name="ticket_price" step="0.01" class="form-control form-control-vivensi" placeholder="0,00" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-700 text-muted text-uppercase">Qtd. Bilhetes</label>
                                    <input type="number" name="total_tickets" class="form-control form-control-vivensi" placeholder="100" required>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small fw-700 text-muted text-uppercase">Data do Sorteio</label>
                                <input type="datetime-local" name="draw_date" class="form-control form-control-vivensi" required>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="col-12 mt-5 text-end">
                            <hr class="opacity-10 mb-4">
                            <a href="{{ route('raffles.index') }}" class="btn btn-link text-muted fw-bold text-decoration-none me-3">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm py-2">
                                <i class="bi bi-rocket-takeoff me-2"></i> PUBLICAR RIFA
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control-vivensi {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 15px;
        transition: all 0.2s;
    }
    .form-control-vivensi:focus {
        background: #fff;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
    }
    .image-upload-box {
        border: 2px dashed #e2e8f0;
        border-radius: 15px;
        height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
    }
    .image-upload-box:hover {
        border-color: var(--bs-primary);
        background: #fff;
    }
    .preview-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .ls-1 { letter-spacing: 1px; }
    .bg-primary-soft { background: rgba(67, 97, 238, 0.1); }
    .bg-success-soft { background: rgba(16, 185, 129, 0.1); }
</style>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').classList.remove('d-none');
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
