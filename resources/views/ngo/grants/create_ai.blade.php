@extends('layouts.app')

@section('content')
<style>
    .ai-import-card {
        background: white;
        border-radius: 32px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        max-width: 700px;
        margin: 40px auto;
        overflow: hidden;
        position: relative;
    }
    .ai-import-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 6px;
        background: linear-gradient(90deg, #4f46e5, #818cf8, #4f46e5);
    }
    
    .drop-zone-premium {
        border: 2px dashed #e2e8f0;
        border-radius: 24px;
        padding: 60px 40px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        background: #f8fafc;
        margin-bottom: 30px;
    }
    .drop-zone-premium:hover {
        border-color: #6366f1;
        background: #f5f7ff;
    }
    
    .ai-badge {
        background: #4f46e5;
        color: white;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    
    .loading-overlay {
        display: none;
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.9);
        z-index: 10;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container">
    <div class="text-center mb-5">
        <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
            <span class="ai-badge d-inline-block">Bruce AI Inspector</span>
        </div>
        <h2 class="fw-bold text-dark">Smart Grant Import</h2>
        <p class="text-muted mx-auto" style="max-width: 500px;">Economize horas. Deixe o Bruce ler o edital (PDF) e estruturar os dados para você.</p>
    </div>

    <div class="ai-import-card">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="mb-4">
                <img src="{{ asset('img/bruce-ai.png') }}" class="rounded-circle shadow" style="width: 80px; height: 80px; animation: pulse 2s infinite;">
            </div>
            <h5 class="fw-bold text-dark">Farejando Dados...</h5>
            <p class="text-muted small">O Bruce está analisando o edital e extraindo as informações financeiras.</p>
        </div>

        <div class="p-5">
            <form action="{{ url('/ngo/grants/analyze') }}" method="POST" enctype="multipart/form-data" onsubmit="showLoading()">
                @csrf
                
                <div class="drop-zone-premium" onclick="document.getElementById('fileInput').click()">
                    <div style="width: 80px; height: 80px; background: white; border-radius: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem; color: #ef4444;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Clique para selecionar o PDF</h4>
                    <p class="text-muted small m-0">Arraste o arquivo ou procure no seu computador.<br>Tamanho máximo: 10MB</p>
                    <input type="file" name="edital_file" id="fileInput" accept=".pdf" style="display: none;" required onchange="updateFileName(this)">
                    <div id="fileNameDisplay" class="mt-3 fw-bold text-primary small"></div>
                </div>

                <div class="alert alert-info border-0 rounded-4 d-flex align-items-start gap-3 p-4 mb-4">
                    <i class="fas fa-shield-alt mt-1"></i>
                    <div class="small">
                        <strong>Privacidade de Dados:</strong> O arquivo é processado temporariamente para análise e os dados extraídos são sugeridos a você. Nada é armazenado permanentemente antes da sua confirmação.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm" style="background: #4f46e5; border: none; font-size: 1.1rem;">
                    <i class="fas fa-sparkles me-2"></i> Iniciar Análise Inteligente
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function updateFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        if (input.files.length > 0) {
            display.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${input.files[0].name}`;
        }
    }
    
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
</script>
@endsection
