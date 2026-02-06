@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp

<style>
    .ai-hero-card {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border-radius: 32px;
        padding: 40px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(79, 70, 229, 0.2);
    }
    .ai-hero-card::before {
        content: "AI";
        position: absolute;
        top: -30px;
        right: -20px;
        font-size: 15rem;
        font-weight: 900;
        opacity: 0.1;
        font-family: 'Outfit', sans-serif;
    }
    .metric-premium-label {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #94a3b8;
        display: block;
        margin-bottom: 5px;
    }
    .metric-premium-value {
        font-size: 2.2rem;
        font-weight: 900;
        color: #1e293b;
        letter-spacing: -1.5px;
        line-height: 1;
    }
    .bruce-glass-premium {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 24px;
        padding: 25px;
        position: relative;
    }
    #aiAnalysisBox {
        border-radius: 28px;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
    }
    .insight-card-premium {
        background: white;
        border-radius: 20px;
        padding: 20px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s;
    }
    .insight-card-premium:hover {
        transform: translateX(10px);
        border-color: #6366f1;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.05);
    }
</style>

<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #6366f1; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #6366f1; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Laboratório Cognitivo</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Smart Analysis <span class="vivensi-gradient-text" style="background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI</span></h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Previsão de fluxo e inteligência de CFO Virtual para sua entidade.</p>
        </div>
        <button id="btnDeepAnalysis" class="btn-premium btn-premium-shine ai-pulse-glow" style="border: none; padding: 16px 32px; font-weight: 800; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-brain"></i> Iniciar Análise Profunda
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Main Forecasting Section -->
    <div class="col-lg-8">
        <div class="vivensi-card" style="padding: 40px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Projeção de Sobrevivência (Runway)</h4>
                    <p style="margin: 5px 0 0 0; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">Algoritmo Beta-Predict v.2.0 • Histórico de 365 dias</p>
                </div>
                <div style="display: flex; background: #f8fafc; padding: 5px; border-radius: 12px; border: 1px solid #f1f5f9;">
                    <div style="padding: 6px 15px; background: white; border-radius: 8px; font-size: 0.75rem; font-weight: 800; color: #6366f1; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">PRÓXIMOS 6 MESES</div>
                </div>
            </div>
            
            <div id="projectionChart" style="height: 400px;"></div>
        </div>
    </div>

    <!-- Quick Insights Panel -->
    <div class="col-lg-4">
        <div style="display: flex; flex-direction: column; gap: 25px; height: 100%;">
            
            <!-- Runway Hero -->
            <div class="ai-hero-card">
                <span style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.7); display: block; margin-bottom: 10px;">Autonomia Financeira</span>
                <div style="display: flex; align-items: baseline; gap: 10px;">
                    <h1 style="font-size: 4rem; font-weight: 900; margin: 0; line-height: 1;">
                        {{ $metrics['months_left'] >= 99 ? '∞' : number_format($metrics['months_left'], 1) }}
                    </h1>
                    <span style="font-size: 1.25rem; font-weight: 700; opacity: 0.8;">meses</span>
                </div>
                <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between;">
                    <div>
                        <span style="display: block; font-size: 0.6rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase;">Taxa de Queima</span>
                        <span style="font-weight: 800; font-size: 1.1rem;">R$ {{ number_format($metrics['avg_monthly_burn'], 0, ',', '.') }}</span>
                    </div>
                    <div style="text-align: right;">
                         <span style="display: block; font-size: 0.6rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase;">Confiança</span>
                         <span style="font-weight: 800; font-size: 1.1rem;">94.2%</span>
                    </div>
                </div>
            </div>

            <!-- Bruce Advisor Premium -->
            <div class="vivensi-card" style="background: #1e293b; border-radius: 28px; padding: 35px; flex-grow: 1; position: relative; overflow: hidden;">
                <div class="bruce-glass-premium">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                        <div>
                            <h6 style="margin: 0; color: white; font-weight: 900; font-size: 1rem; letter-spacing: 0.5px;">Bruce AI Advisor</h6>
                            <span style="font-size: 0.65rem; color: #10b981; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Online & Analisando</span>
                        </div>
                    </div>
                    <p style="color: rgba(255,255,255,0.9); font-size: 0.95rem; font-weight: 500; line-height: 1.6; margin: 0; font-style: italic;">
                        "{{ $metrics['months_left'] < 4 
                            ? 'Woof! 🚨 Detecto turbulência no horizonte. Nosso fôlego está abaixo de 4 meses. Hora de priorizar captação ou otimizar custos operacionais imediatamente.' 
                            : 'Au au! 🐾 O radar está limpo e o oceano calmo. Temos uma reserva sólida que nos permite planejar novos investimentos sociais com segurança.' }}"
                    </p>
                </div>
                <div style="position: absolute; bottom: -20px; right: -20px; font-size: 8rem; color: rgba(255,255,255,0.03); transform: rotate(-15deg);">
                    <i class="fas fa-microchip"></i>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <!-- Detailed Insights -->
    <div class="col-lg-6">
        <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
            <h4 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 1.35rem; letter-spacing: -0.5px;">Sinais Vitais</h4>
            <span style="height: 1px; flex-grow: 1; background: #f1f5f9;"></span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            @forelse($insights as $insight)
            <div class="insight-card-premium">
                <div style="display: flex; gap: 20px;">
                    <div style="width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
                        {{ $insight['type'] == 'critical' ? 'background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2;' : ($insight['type'] == 'success' ? 'background: #f0fdf4; color: #10b981; border: 1px solid #dcfce7;' : 'background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe;') }}">
                        <i class="fas {{ $insight['icon'] }}"></i>
                    </div>
                    <div>
                        <h6 style="margin: 0 0 5px 0; font-weight: 900; color: #1e293b; font-size: 1rem;">{{ $insight['title'] }}</h6>
                        <p style="margin: 0; color: #64748b; font-size: 0.9rem; line-height: 1.5; font-weight: 500;">{{ $insight['message'] }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="vivensi-card" style="padding: 50px; text-align: center; border: 2px dashed #f1f5f9; background: #fafafa;">
                <i class="fas fa-shield-heart" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 20px;"></i>
                <h5 style="font-weight: 900; color: #1e293b;">Nenhum Alerta Pendente</h5>
                <p style="color: #94a3b8; font-weight: 600;">Seus fluxos estão operando dentro da normalidade esperada.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- AI Generated Report Container -->
    <div class="col-lg-6">
        <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
            <h4 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 1.35rem; letter-spacing: -0.5px;">Estratégia Deep-View</h4>
            <span style="height: 1px; flex-grow: 1; background: #f1f5f9;"></span>
        </div>

        <div id="aiAnalysisBox" style="min-height: 480px; background: white; border: 2px dashed #e2e8f0; position: relative;">
            
            <!-- Default / Empty State -->
            <div id="aiPlaceholder" style="padding: 60px 40px; text-align: center; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; border: 1px solid #f1f5f9;">
                    <i class="fas fa-microchip" style="font-size: 2.5rem; color: #e2e8f0;"></i>
                </div>
                <h5 style="font-weight: 900; color: #cbd5e1; font-size: 1.3rem;">Relatório Não Iniciado</h5>
                <p style="color: #cbd5e1; max-width: 320px; font-weight: 600; line-height: 1.6; margin-top: 10px;">A inteligência profunda requer uma requisição manual para garantir o uso eficiente de recursos.</p>
                <button onclick="document.getElementById('btnDeepAnalysis').click()" class="btn-premium" style="margin-top: 25px; background: #94a3b8; box-shadow: none; border: none; padding: 12px 30px; font-weight: 800;">ATIVAR MOTOR IA</button>
            </div>

            <!-- Loading State -->
            <div id="aiLoading" class="d-none" style="padding: 60px 40px; text-align: center; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div class="ai-pulse-glow" style="width: 80px; height: 80px; background: #6366f1; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; color: white; font-size: 2rem;">
                    <i class="fas fa-atom fa-spin"></i>
                </div>
                <h5 style="font-weight: 900; color: #1e293b; font-size: 1.5rem;">Processando Neurônios...</h5>
                <p style="color: #64748b; max-width: 300px; font-weight: 500; line-height: 1.6; margin-top: 10px;">Bruce está cruzando dados de tesouraria com variáveis de mercado para gerar o melhor insight.</p>
            </div>

            <!-- Content State -->
            <div id="aiContent" class="d-none" style="padding: 45px;">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 35px; padding-bottom: 25px; border-bottom: 1px solid #f1f5f9;">
                    <div style="padding: 10px; background: #eef2ff; border-radius: 12px; color: #6366f1; font-size: 1.5rem;"><i class="fas fa-file-contract"></i></div>
                    <div>
                        <h5 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 1.2rem;">Relatório de Alta Performance</h5>
                        <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Sincronizado via Webhook • {{ date('d/m/Y H:i') }}</span>
                    </div>
                </div>
                <div id="aiTextBody" style="color: #475569; font-size: 0.95rem; line-height: 1.8; font-weight: 500;"></div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePath = @json($basePath);
    const deepEndpoint = (basePath || '') + '/smart-analysis/deep';

    // ApexCharts Configuration
    const options = {
        series: [{ name: 'Patrimônio Projetado', data: {!! json_encode($prediction['values'] ?? []) !!} }],
        chart: { 
            type: 'area', 
            height: 400, 
            fontFamily: 'Outfit, sans-serif',
            toolbar: { show: false }, 
            zoom: { enabled: false } 
        },
        colors: ['#6366f1'],
        fill: { 
            type: 'gradient', 
            gradient: { 
                shadeIntensity: 1, 
                opacityFrom: 0.6, 
                opacityTo: 0.05, 
                stops: [0, 95, 100],
                colorStops: [
                    { offset: 0, color: "#6366f1", opacity: 0.6 },
                    { offset: 100, color: "#a855f7", opacity: 0.1 }
                ]
            } 
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 4, lineCap: 'round' },
        markers: { size: 0, hover: { size: 6, strokeWidth: 3 } },
        xaxis: { 
            categories: {!! json_encode($prediction['labels'] ?? []) !!}, 
            axisBorder: { show: false }, 
            axisTicks: { show: false },
            labels: { style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 700 } }
        },
        yaxis: { 
            labels: { 
                style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 700 },
                formatter: val => "R$ " + val.toLocaleString('pt-BR', { notation: "compact", compactDisplay: "short" }) 
            } 
        },
        grid: { borderColor: '#f8fafc', strokeDashArray: 6, padding: { top: 0, right: 0, bottom: 0, left: 15 } },
        tooltip: {
            theme: 'dark',
            x: { show: true },
            y: { formatter: val => "R$ " + val.toLocaleString('pt-BR') }
        }
    };
    new ApexCharts(document.querySelector("#projectionChart"), options).render();

    // AI Analysis Logic
    const btn = document.getElementById('btnDeepAnalysis');
    const box = document.getElementById('aiAnalysisBox');
    const placeholder = document.getElementById('aiPlaceholder');
    const loading = document.getElementById('aiLoading');
    const content = document.getElementById('aiContent');
    const textBody = document.getElementById('aiTextBody');

    btn.addEventListener('click', function() {
        placeholder.classList.add('d-none');
        loading.classList.remove('d-none');
        content.classList.add('d-none');
        
        box.style.border = 'none';
        box.style.boxShadow = '0 30px 60px rgba(0,0,0,0.05)';
        box.style.borderRadius = '28px';

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-sync fa-spin me-2"></i> Bruce pensando...';

        fetch(deepEndpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(async (response) => {
            if (!response.ok) {
                const txt = await response.text();
                throw new Error('HTTP ' + response.status + ': ' + txt);
            }
            return response.json();
        })
        .then(data => {
            loading.classList.add('d-none');
            content.classList.remove('d-none');
            textBody.innerHTML = marked.parse(data.analysis);
            btn.innerHTML = '<i class="fas fa-brain me-2"></i> Recalcular Análise';
            btn.disabled = false;
        })
        .catch(err => {
            textBody.innerHTML = '<div class="alert alert-danger border-0 rounded-4">Falha ao gerar a análise. Tente novamente. Se persistir, verifique sessão/CSRF e permissões.<br><small class="text-muted">' + String(err).replace(/</g,'&lt;') + '</small></div>';
            loading.classList.add('d-none');
            content.classList.remove('d-none');
            btn.disabled = false;
        });
    });
});
</script>

<style>
/* Markdown Content Overrides */
#aiTextBody h1, #aiTextBody h2, #aiTextBody h3 {
    color: #1e293b;
    font-weight: 900;
    margin-top: 1.5rem;
    margin-bottom: 0.8rem;
    letter-spacing: -0.5px;
}
#aiTextBody p { margin-bottom: 1.2rem; }
#aiTextBody ul { padding-left: 1.5rem; margin-bottom: 1.5rem; }
#aiTextBody li { margin-bottom: 0.5rem; }
#aiTextBody strong { font-weight: 800; color: #1e293b; }
</style>
@endsection

