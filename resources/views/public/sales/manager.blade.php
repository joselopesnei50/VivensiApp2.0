@extends('layouts.landing_sales')

@section('title', 'Alta Performance em Gestão de Projetos e Empresas')
@section('meta_description', 'Domine seus projetos, equipe e fluxo financeiro com o Vivensi 2.0. A central de comando inteligente para empresas de alta performance.')

@section('styles')
<style>
    .hero-manager {
        padding: 180px 5% 100px;
        background: radial-gradient(circle at bottom left, #ecfdf5 0%, #ffffff 50%);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }
    .hero-manager h1 { font-size: 3.8rem; font-weight: 900; line-height: 1.1; margin-bottom: 25px; color: #0f172a; letter-spacing: -0.04em; }
    .hero-manager h1 span { color: #16a34a; }
    
    .gantt-preview {
        background: white;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
    }
    .gantt-bar { height: 12px; border-radius: 6px; margin-bottom: 12px; }
</style>
@endsection

@section('content')
    <!-- Hero -->
    <section class="hero-manager">
        <div class="animate">
            <span class="section-badge" style="background: #dcfce7; color: #166534;">PLATAFORMA DE ALTA PERFORMANCE</span>
            <h1>Domine seus <span>Projetos</span> e o seu <span>Fluxo Financeiro</span>.</h1>
            <p>O Vivensi 2.0 é o centro de comando definitivo para empresas e gestores que não aceitam menos que o controle total. Cronogramas, custos e equipe em uma única interface inteligente.</p>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="{{ route('register', ['plan_id' => 2]) }}" class="btn-cta" style="background: #16a34a; box-shadow: 0 10px 25px -5px rgba(22, 163, 74, 0.4); font-size: 1.2rem; padding: 18px 40px;">Iniciar Teste Grátis</a>
                <a href="#workflow" class="btn-outline" style="padding: 18px 40px; border-radius: 50px; text-decoration: none; font-weight: 700;">Ver Ecossistema</a>
            </div>

            <div style="margin-top: 50px; display: flex; gap: 30px;">
                <div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">360º</div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Controle Operacional</div>
                </div>
                <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">100%</div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Mobile Ready</div>
                </div>
                <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">IA</div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Análise Preditiva</div>
                </div>
            </div>
        </div>
        
        <div class="animate" style="animation-delay: 0.2s;">
            <div class="gantt-preview">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                    <i class="fas fa-chart-project" style="color: #16a34a; font-size: 1.5rem;"></i>
                    <h4 style="margin: 0; font-weight: 800;">Visualização de Cronograma</h4>
                </div>
                <div style="display: grid; grid-template-columns: 80px 1fr; gap: 15px; align-items: center; margin-bottom: 20px;">
                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 700;">Módulo A</div>
                    <div class="gantt-bar" style="width: 60%; background: #16a34a;"></div>
                    
                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 700;">Módulo B</div>
                    <div class="gantt-bar" style="width: 85%; background: #16a34a; opacity: 0.6; margin-left: 10%;"></div>
                    
                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 700;">Financeiro</div>
                    <div class="gantt-bar" style="width: 40%; background: #2563eb; margin-left: 30%;"></div>
                </div>
                <div style="background: #f8fafc; border-radius: 12px; padding: 15px; border-left: 4px solid #16a34a;">
                    <div style="font-size: 0.75rem; font-weight: 800; color: #166534;">INSIGHT DA IA:</div>
                    <div style="font-size: 0.8rem; color: #334155;">A margem do projeto aumentou 12% após a otimização de custos desta semana.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Showcase -->
    <section style="padding: 80px 5%; background: #f1f5f9; text-align: center;">
        <div class="animate" style="max-width: 1000px; margin: 0 auto;">
            <span class="section-badge" style="background: #dcfce7; color: #166534;">TOUR VIRTUAL</span>
            <h2>Potencialize seus Resultados Corporativos</h2>
            <p>Veja como o nosso ecossistema de gestão pode transformar a produtividade e a lucratividade do seu negócio.</p>
            
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.2); background: #000; margin-top: 40px;">
                <iframe 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                    src="https://www.youtube.com/embed/placeholder" 
                    title="Vivensi para Empresas" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </section>

    <!-- Operational Power -->
    <section id="workflow" style="padding: 100px 5%; background: #0f172a; color: white;">
        <div style="text-align: center; max-width: 800px; margin: 0 auto 80px;">
            <span class="section-badge" style="background: rgba(22, 163, 74, 0.1); color: #4ade80;">GESTÃO EMPRESARIAL 2.0</span>
            <h2 style="color: white;">Sua empresa operando no piloto automático</h2>
            <p style="color: #94a3b8;">Tecnologia robusta para empresas que escalam rápido. Diminua a carga operacional e foque no crescimento estratégico.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
            <div style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05);">
                <i class="fas fa-microchip" style="font-size: 2.5rem; color: #16a34a; margin-bottom: 25px;"></i>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Workflow Inteligente</h4>
                <p style="color: #94a3b8;">Gerencie tarefas, responsáveis e prazos com triagem automática de prioridades. Mantenha seu time alinhado sem reuniões intermináveis.</p>
            </div>

            <div style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05);">
                <i class="fas fa-sack-dollar" style="font-size: 2.5rem; color: #16a34a; margin-bottom: 25px;"></i>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Financeiro Integrado</h4>
                <p style="color: #94a3b8;">DRE, Fluxo de Caixa e Relatórios Gerenciais emitidos em segundos. Tenha clareza absoluta sobre a lucratividade de cada contrato.</p>
            </div>

            <div style="background: rgba(255,255,255,0.03); padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05);">
                <i class="fas fa-users-gear" style="font-size: 2.5rem; color: #16a34a; margin-bottom: 25px;"></i>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Colaboração Centralizada</h4>
                <p style="color: #94a3b8;">Chat interno, compartilhamento de documentos e logs de atividade integrados. Sua empresa inteira conectada em um único canal seguro.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <div id="pricing" style="padding: 100px 5%; background: white; text-align: center;">
        <span class="section-badge">INVESTIMENTO CORPORATIVO</span>
        <h2>Acelere sua Empresa Hoje</h2>
        
        <div style="max-width: 500px; margin: 0 auto; padding: 60px; background: white; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.1); border: 2px solid #16a34a;">
            <div style="text-transform: uppercase; font-weight: 800; color: #166534; letter-spacing: 2px; margin-bottom: 10px;">Plano Gestor Business</div>
            <div style="font-size: 4rem; font-weight: 900; color: #0f172a; margin-bottom: 10px;">R$ 297<span style="font-size: 1.2rem; color: #64748b; font-weight: 400;">/mês</span></div>
            <p>Controle operacional e financeiro para negócios que buscam escala.</p>
            <hr style="opacity: 0.1; margin: 30px 0;">
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom: 40px;">
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #16a34a; margin-right: 10px;"></i> Projetos e Tasks Ilimitadas</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #16a34a; margin-right: 10px;"></i> Gráfico de Gantt Interativo</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #16a34a; margin-right: 10px;"></i> Gestão de Contratos e Clientes</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #16a34a; margin-right: 10px;"></i> Fluxo Financeiro Real-Time</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #16a34a; margin-right: 10px;"></i> Suporte Prioritário</li>
            </ul>
            <a href="{{ route('register', ['plan_id' => 2]) }}" class="btn-cta" style="width: 100%; border-radius: 12px; font-size: 1.2rem; background: #16a34a;">Ativar 7 Dias de Teste Grátis</a>
        </div>
    </div>
@endsection
