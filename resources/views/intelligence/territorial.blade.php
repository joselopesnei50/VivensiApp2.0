@extends('layouts.app', ['title' => 'Inteligência Territorial - IBGE'])

@section('content')
<div class="container-fluid p-0" style="background: #0f172a; min-height: 100vh; color: white; font-family: 'Inter', sans-serif;">
    
    <!-- HERO SECTION COM PESQUISA -->
    <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 60px 40px; border-bottom: 1px solid rgba(255,255,255,0.05); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background: rgba(16, 185, 129, 0.05); border-radius: 50%; filter: blur(80px);"></div>
        <div style="position: absolute; bottom: -50px; left: -50px; width: 300px; height: 300px; background: rgba(79, 70, 229, 0.05); border-radius: 50%; filter: blur(80px);"></div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 6px 16px; border-radius: 100px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: inline-block; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <i class="fas fa-satellite me-2"></i> Módulo de Big Data Social
                    </span>
                    <h1 style="font-weight: 900; font-size: 3.5rem; letter-spacing: -2px; line-height: 1; margin-bottom: 20px;">
                        Inteligência <span style="background: linear-gradient(90deg, #10b981, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Territorial</span>
                    </h1>
                    <p style="font-size: 1.2rem; color: rgba(255,255,255,0.5); max-width: 600px; line-height: 1.6;">
                        Cruze os dados oficiais do IBGE com seu projeto para gerar diagnósticos sociais automáticos e provar seu impacto.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div style="background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 35px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                        <h5 style="font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-search-location" style="color: #10b981;"></i> Começar Pesquisa
                        </h5>
                        <div class="position-relative">
                            <div class="input-group mb-3" style="background: rgba(0,0,0,0.3); border-radius: 18px; padding: 10px; border: 1px solid rgba(255,255,255,0.1);">
                                <input type="text" id="citySearch" class="form-control border-0 bg-transparent text-white shadow-none" placeholder="Qual cidade deseja analisar?" style="font-size: 1.1rem; padding-left: 15px;">
                                <button onclick="triggerSearch()" class="btn btn-success" style="border-radius: 12px; font-weight: 800; padding: 12px 25px; background: #10b981; border: none; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);">
                                    <i class="fas fa-search me-2"></i> ANALISAR
                                </button>
                            </div>
                            <!-- RESULTADOS AUTOCOMPLETE -->
                            <div id="searchResults" class="position-absolute w-100" style="z-index: 1000; top: 100%; left: 0; background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; display: none; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.6);">
                            </div>
                        </div>
                        <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3); margin-top: 15px; text-align: center;">
                            <i class="fas fa-info-circle me-1"></i> Digite ao menos 3 letras para listar as cidades.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ÁREA DE RESULTADOS -->
    <div class="container py-5" id="dashboardArea" style="display: none; opacity: 0; transition: 0.5s ease;">
        
        <!-- HEADER DA CIDADE SELECIONADA -->
        <div class="row mb-5">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <div>
                    <h2 id="selectedCityName" style="font-weight: 800; margin: 0; font-size: 2.2rem;">-</h2>
                    <p style="color: #10b981; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem;">Município Detectado via IBGE SIDRA</p>
                </div>
                <div class="text-end">
                    <span id="updateBadge" style="background: rgba(255,255,255,0.05); padding: 8px 16px; border-radius: 10px; font-size: 0.75rem; color: rgba(255,255,255,0.5);">
                        Cache atualizado em: <strong id="cacheDate">-</strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- INDICADORES PRINCIPAIS -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background: rgba(79, 70, 229, 0.1); color: #818cf8;"><i class="fas fa-users"></i></div>
                    <h3>População</h3>
                    <div class="value" id="val-populacao">-</div>
                    <div class="year" id="year-populacao">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-hand-holding-dollar"></i></div>
                    <h3>Renda Média</h3>
                    <div class="value" id="val-renda">-</div>
                    <div class="year" id="year-renda">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background: rgba(245, 158, 11, 0.1); color: #fbbf24;"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Educação</h3>
                    <div class="value" id="val-educacao">-</div>
                    <div class="year" id="year-educacao">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon-box" style="background: rgba(6, 182, 212, 0.1); color: #22d3ee;"><i class="fas fa-faucet-drip"></i></div>
                    <h3>Saneamento</h3>
                    <div class="value" id="val-saneamento">-</div>
                    <div class="year" id="year-saneamento">-</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- ANÁLISE BRUCE AI -->
            <div class="col-lg-6">
                <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 30px; padding: 40px; height: 100%; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; right: 0; padding: 15px;">
                        <i class="fas fa-quote-right" style="font-size: 4rem; color: rgba(255,255,255,0.03);"></i>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="bruce-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 800; margin: 0;">Análise Bruce AI</h4>
                            <span style="font-size: 0.7rem; color: #10b981; font-weight: 700; text-transform: uppercase;">Inteligência Estratégica</span>
                        </div>
                    </div>
                    <div id="aiAnalysis" class="typing-container" style="font-size: 1.15rem; line-height: 1.7; color: rgba(255,255,255,0.9); font-weight: 400;">
                        Selecione uma cidade para iniciar o diagnóstico...
                    </div>
                </div>
            </div>

            <!-- GRÁFICO / VISUALIZAÇÃO -->
            <div class="col-lg-6">
                <div style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; height: 100%;">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 style="font-weight: 800; margin: 0;">Comparativo Territorial</h4>
                        <i class="fas fa-chart-pie" style="color: #6366f1;"></i>
                    </div>
                    <div id="chartContainer" style="height: 300px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.1);">
                        <div class="text-center">
                            <i class="fas fa-chart-line mb-3" style="font-size: 2rem; color: rgba(255,255,255,0.1);"></i><br>
                            <span class="text-muted small">Os dados visuais aparecerão em breve.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap');

    .stat-card {
        background: #1e293b;
        padding: 30px;
        border-radius: 25px;
        border: 1px solid rgba(255,255,255,0.05);
        height: 100%;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .stat-card:hover {
        transform: translateY(-10px);
        border-color: #10b981;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);
    }
    .stat-card .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    .stat-card h3 {
        font-size: 0.85rem;
        font-weight: 700;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 1px;
    }
    .stat-card .value {
        font-size: 1.8rem;
        font-weight: 900;
        color: white;
    }
    .stat-card .year {
        font-size: 0.7rem;
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
        padding: 2px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 10px;
        font-weight: 700;
    }
    .bruce-avatar {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, #4f46e5, #3730a3);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 20px;
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
    }
    .search-item {
        padding: 15px 25px;
        cursor: pointer;
        transition: 0.2s;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .search-item:hover {
        background: #10b981;
        color: white !important;
    }
    .search-item i { color: #10b981; }
    .search-item:hover i { color: white; }

    .typing-container::after {
        content: '|';
        animation: blink 1s infinite;
    }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
</style>

<script>
    let searchTimeout;
    const cityInput = document.getElementById('citySearch');
    const resultsBox = document.getElementById('searchResults');
    let selectedCity = null;

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
                    div.innerHTML = `<i class="fas fa-location-dot"></i> <div><strong>${city.nome}</strong> <br> <small>${city.UF.nome}</small></div>`;
                    div.onclick = () => {
                        cityInput.value = `${city.nome} - ${city.UF.sigla}`;
                        selectedCity = city;
                        resultsBox.style.display = 'none';
                    };
                    resultsBox.appendChild(div);
                });
                resultsBox.style.display = 'block';
            } else {
                resultsBox.style.display = 'none';
            }
        }, 300);
    });

    async function triggerSearch() {
        if (!selectedCity) {
            alert('Por favor, selecione uma cidade da lista antes de analisar.');
            return;
        }

        const dashboard = document.getElementById('dashboardArea');
        dashboard.style.display = 'block';
        setTimeout(() => dashboard.style.opacity = '1', 50);

        document.getElementById('selectedCityName').innerText = `${selectedCity.nome}, ${selectedCity.UF.sigla}`;
        document.getElementById('aiAnalysis').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Bruce AI está acessando o banco de dados do IBGE e processando a análise...';

        try {
            const response = await fetch(`/api/ibge/indicators/${selectedCity.id}`);
            const data = await response.json();

            // Update UI with animation
            updateIndicator('populacao', data.indicators.populacao);
            updateIndicator('renda', data.indicators.renda, 'R$ ');
            updateIndicator('educacao', data.indicators.educacao, '', '%');
            updateIndicator('saneamento', data.indicators.saneamento, '', '%');

            document.getElementById('cacheDate').innerText = new Date().toLocaleDateString('pt-BR');
            document.getElementById('aiAnalysis').innerText = data.analysis;

        } catch (error) {
            console.error(error);
            alert('Erro ao buscar indicadores. Tente novamente.');
        }
    }

    function updateIndicator(id, data, prefix = '', suffix = '') {
        const valEl = document.getElementById(`val-${id}`);
        const yearEl = document.getElementById(`year-${id}`);
        
        if (data.value && data.value !== 'N/A') {
            let numValue = parseFloat(data.value);
            valEl.innerText = prefix + numValue.toLocaleString('pt-BR') + suffix;
            yearEl.innerText = 'Censo ' + data.year;
        } else {
            valEl.innerText = 'N/A';
            yearEl.innerText = '-';
        }
    }

    document.addEventListener('click', (e) => {
        if (!cityInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
</script>
@endsection
