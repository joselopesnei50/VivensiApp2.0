@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Planejamento</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Metas para {{ $year }}</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Defina seus limites e objetivos para o ano.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="vivensi-card" style="padding: 30px;">
            <form action="{{ url('/personal/budget/store') }}" method="POST">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Meta de Receita Anual</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">R$</span>
                            <input type="number" step="0.01" name="target_income" class="form-control" value="{{ $budget->target_income ?? 0 }}">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Teto de Gastos Anual</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">R$</span>
                            <input type="number" step="0.01" name="max_expense" class="form-control" value="{{ $budget->max_expense ?? 0 }}">
                        </div>
                    </div>

                    <div class="col-12">
                        <hr>
                        <h5 class="mb-3 fw-bold"><i class="fas fa-th-list me-2"></i> Orçamento por Categoria</h5>
                        <div class="row g-3">
                            @foreach($defaultCategories as $cat)
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">{{ $cat }}</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" name="categories[{{ $cat }}]" class="form-control" value="{{ $items[$cat] ?? 0 }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold">Notas e Observações</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Ex: Economizar para viagem de férias...">{{ $budget->notes ?? '' }}</textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn-premium w-100">Salvar Planejamento</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-5">
        <div class="vivensi-card" style="padding: 30px; background: #0f172a; color: white; border: none; overflow: hidden; position: relative;">
            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: #6366f1; filter: blur(60px); opacity: 0.4;"></div>
            
            <div style="display: flex; align-items: center; margin-bottom: 25px; position: relative;">
                <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce AI" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid #3b82f6; padding: 2px; object-fit: cover; margin-right: 12px;" title="Atendimento via Bruce AI 🐶">
                <h4 style="margin: 0; font-weight: 800; font-size: 1rem; letter-spacing: 0.5px; color: #ffffff;">PULSO FINANCEIRO IA</h4>
                <div class="ai-pulse" style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-left: 10px; box-shadow: 0 0 10px #10b981;"></div>
            </div>
            
            <div id="ai-tips-container" style="display: flex; flex-direction: column; gap: 15px; position: relative;">
                <p style="font-size: 0.85rem; opacity: 0.6; color: #cbd5e1;">Conectando ao núcleo de análise...</p>
            </div>
            
            <button onclick="refreshAiTips()" class="btn-premium w-100" style="margin-top: 25px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); font-size: 0.8rem; font-weight: 700;">
                <i class="fas fa-bolt me-2" style="color: #fbbf24;"></i> GERAR NOVOS INSIGHTS
            </button>
        </div>
    </div>
</div>

<style>
    .ai-card-pill {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        padding: 15px;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    .ai-card-pill:hover {
        background: rgba(255,255,255,0.06);
        transform: translateX(5px);
    }
    .ai-pulse {
        animation: pulse-animation 2s infinite;
    }
    @keyframes pulse-animation {
        0% { box-shadow: 0 0 0 0px rgba(16, 185, 129, 0.7); }
        100% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    }
</style>

<script>
    async function refreshAiTips() {
        const container = document.getElementById('ai-tips-container');
        container.innerHTML = '<div style="padding: 20px; text-align: center;"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2" style="font-size: 0.8rem; opacity: 0.7;">Analisando tendências...</span></div>';
        
        try {
            const response = await fetch('{{ url("/personal/budget/ai-tips") }}');
            const data = await response.json();
            
            if (data.error) {
                container.innerHTML = `<p style="color: #ef4444; font-size: 0.8rem;">${data.error}</p>`;
            } else {
                const tips = data.tips.split('\n').filter(t => t.trim() !== '');
                container.innerHTML = '';
                
                tips.forEach(tip => {
                    const parts = tip.split('|');
                    const icon = parts[0] || '💡';
                    const content = parts[1] || tip;
                    
                    const div = document.createElement('div');
                    div.className = 'ai-card-pill';
                    div.innerHTML = `
                        <div style="display: flex; gap: 12px; align-items: start;">
                            <span style="font-size: 1.2rem;">${icon}</span>
                            <div style="font-size: 0.85rem; line-height: 1.4; font-weight: 500;">${content.replace(':', ':<br><span style="font-weight: 400; opacity: 0.7; font-size: 0.75rem;">').replace(',', '</span>') + '</span>'}</div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            }
        } catch (e) {
            console.error("AI Error:", e);
            container.innerHTML = `<p style='font-size: 0.8rem; opacity: 0.5;'>Erro técnico: ${e.message}</p>`;
        }
    }

    // Load tips on start
    document.addEventListener('DOMContentLoaded', refreshAiTips);
</script>
@endsection
