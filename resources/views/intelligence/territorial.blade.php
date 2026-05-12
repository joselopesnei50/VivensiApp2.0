@extends('layouts.app', ['title' => 'Inteligência Territorial - IBGE'])

@section('content')
<div class="container-fluid p-4" style="background: #0f172a; min-height: 100vh; color: white;">
    
    <!-- HEADER -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 style="font-weight: 800; letter-spacing: -1px; margin: 0;">Inteligência <span style="color: #10b981;">Territorial</span></h1>
            <p style="color: rgba(255,255,255,0.5); margin-top: 5px;">Diagnóstico social automático via API do IBGE.</p>
        </div>
        <div class="col-md-4 text-end">
            <div style="background: rgba(16, 185, 129, 0.1); padding: 10px 20px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); display: inline-block;">
                <i class="fas fa-database me-2" style="color: #10b981;"></i> Fonte: IBGE SIDRA
            </div>
        </div>
    </div>

    <!-- BUSCA DE MUNICÍPIO -->
    <div class="row mb-5">
        <div class="col-12">
            <div style="background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 30px;">
                <h4 style="font-weight: 700; margin-bottom: 20px;">Pesquisar Município</h4>
                <div class="position-relative">
                    <div class="input-group" style="background: rgba(0,0,0,0.3); border-radius: 16px; padding: 8px; border: 1px solid rgba(255,255,255,0.1);">
                        <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="citySearch" class="form-control border-0 bg-transparent text-white shadow-none" placeholder="Digite o nome da cidade (ex: Araraquara)..." autocomplete="off">
                    </div>
                    <div id="searchResults" class="position-absolute w-100 mt-2" style="z-index: 1000; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; display: none; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                        <!-- Results will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DASHBOARD DE INDICADORES (HIDDEN BY DEFAULT) -->
    <div id="indicatorDashboard" style="display: none;">
        <div class="row mb-4">
            <!-- CARDS -->
            <div class="col-md-3">
                <div class="indicator-card">
                    <small>População Total</small>
                    <h2 id="val-populacao">-</h2>
                    <span id="year-populacao" class="year-badge">-</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="indicator-card">
                    <small>Renda Per Capita</small>
                    <h2 id="val-renda">-</h2>
                    <span id="year-renda" class="year-badge">-</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="indicator-card">
                    <small>Escolarização (6-14 anos)</small>
                    <h2 id="val-educacao">-</h2>
                    <span id="year-educacao" class="year-badge">-</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="indicator-card">
                    <small>Saneamento Adequado</small>
                    <h2 id="val-saneamento">-</h2>
                    <span id="year-saneamento" class="year-badge">-</span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ANÁLISE BRUCE AI -->
            <div class="col-md-6">
                <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(16, 185, 129, 0.1)); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 30px; height: 100%;">
                    <div class="d-flex align-items-center mb-4">
                        <div style="width: 45px; height: 45px; background: #4f46e5; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <h4 style="font-weight: 700; margin: 0;">Análise Bruce AI</h4>
                    </div>
                    <p id="aiAnalysis" style="font-size: 1.1rem; line-height: 1.8; color: rgba(255,255,255,0.8);">
                        Carregando análise estratégica...
                    </p>
                </div>
            </div>
            <!-- GRÁFICO (placeholder for now) -->
            <div class="col-md-6">
                <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 30px; height: 100%;">
                    <h4 style="font-weight: 700; margin-bottom: 25px;">Panorama Visual</h4>
                    <div id="chartContainer" style="height: 300px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 16px;">
                        <span class="text-muted">Gráfico de Comparação Territorial</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .indicator-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        padding: 25px;
        border-radius: 20px;
        transition: 0.3s;
    }
    .indicator-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: #10b981;
    }
    .indicator-card small {
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.7rem;
        letter-spacing: 1px;
    }
    .indicator-card h2 {
        font-weight: 800;
        margin: 10px 0;
        color: #10b981;
    }
    .year-badge {
        font-size: 0.7rem;
        background: rgba(255,255,255,0.1);
        padding: 2px 8px;
        border-radius: 5px;
    }
    .search-item {
        padding: 15px 20px;
        cursor: pointer;
        transition: 0.2s;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .search-item:hover {
        background: #4f46e5;
    }
</style>

<script>
    let searchTimeout;
    const cityInput = document.getElementById('citySearch');
    const resultsBox = document.getElementById('searchResults');

    cityInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value;

        if (query.length < 3) {
            resultsBox.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(async () => {
            const response = await fetch(`{{ route('ibge.cities.search') }}?q=${query}`);
            const cities = await response.json();

            resultsBox.innerHTML = '';
            if (cities.length > 0) {
                cities.forEach(city => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `<i class="fas fa-map-marker-alt me-2"></i> ${city.nome} - ${city.UF.sigla}`;
                    div.onclick = () => selectCity(city);
                    resultsBox.appendChild(div);
                });
                resultsBox.style.display = 'block';
            } else {
                resultsBox.style.display = 'none';
            }
        }, 300);
    });

    async function selectCity(city) {
        cityInput.value = `${city.nome} - ${city.UF.sigla}`;
        resultsBox.style.display = 'none';
        
        document.getElementById('indicatorDashboard').style.display = 'block';
        document.getElementById('aiAnalysis').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Bruce AI está analisando os dados do IBGE...';

        try {
            const response = await fetch(`/api/ibge/indicators/${city.id}`);
            const data = await response.json();

            // Update UI
            updateIndicator('populacao', data.indicators.populacao);
            updateIndicator('renda', data.indicators.renda, 'R$ ');
            updateIndicator('educacao', data.indicators.educacao, '', '%');
            updateIndicator('saneamento', data.indicators.saneamento, '', '%');

            document.getElementById('aiAnalysis').innerText = data.analysis;

        } catch (error) {
            console.error(error);
            alert('Erro ao buscar indicadores.');
        }
    }

    function updateIndicator(id, data, prefix = '', suffix = '') {
        const valEl = document.getElementById(`val-${id}`);
        const yearEl = document.getElementById(`year-${id}`);
        
        if (data.value && data.value !== 'N/A') {
            valEl.innerText = prefix + parseFloat(data.value).toLocaleString('pt-BR') + suffix;
            yearEl.innerText = data.year;
        } else {
            valEl.innerText = 'Indisponível';
            yearEl.innerText = '-';
        }
    }

    // Close results when clicking outside
    document.addEventListener('click', (e) => {
        if (!cityInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
</script>
@endsection
