@extends('layouts.landing_sales')

@section('title', 'Gestão Revolucionária para ONGs e Terceiro Setor')
@section('meta_description', 'Transforme a transparência e a captação da sua ONG com o Vivensi 2.0. Construtor de páginas, gestão de verbas e portal da transparência automático.')

@section('styles')
<style>
    .hero-ngo {
        padding: 180px 5% 100px;
        background: radial-gradient(circle at top right, #e0e7ff 0%, #ffffff 50%);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }
    .hero-ngo h1 { font-size: 3.8rem; font-weight: 800; line-height: 1.1; margin-bottom: 25px; background: linear-gradient(135deg, #1e293b 0%, #4f46e5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    
    .builder-preview {
        background: #0f172a;
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 40px 80px -20px rgba(0,0,0,0.5);
    }
    .check-item { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; font-weight: 600; color: #334155; }
    .check-item i { color: #10b981; }

    @media (max-width: 992px) {
        .hero-ngo { grid-template-columns: 1fr; text-align: center; }
        .hero-ngo h1 { font-size: 2.8rem; }
    }
</style>
@endsection

@section('content')
    <!-- Hero -->
    <section class="hero-ngo">
        <div class="animate">
            <span class="section-badge">SOLUÇÃO EXCLUSIVA PARA O TERCEIRO SETOR</span>
            <h1>Sua ONG mais Profissional, Transparente e Digital.</h1>
            <p>A única plataforma brasileira que une Gestão Financeira, Portal da Transparência e Construtor de Páginas de Doação em um só lugar. Revolucione sua captação de recursos hoje.</p>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="{{ route('register', ['plan_id' => 1]) }}" class="btn-cta" style="font-size: 1.2rem; padding: 18px 40px;">Testar Grátis por 7 Dias</a>
                <a href="#features" class="btn-outline" style="padding: 18px 40px; border-radius: 50px; text-decoration: none; font-weight: 700; border: 2px solid #e2e8f0; color: #1e293b;">Ver Recursos</a>
            </div>

            <div style="margin-top: 40px;">
                <div class="check-item"><i class="fas fa-check-circle"></i> Portal da Transparência Automático</div>
                <div class="check-item"><i class="fas fa-check-circle"></i> Páginas de Captação Prontas em 5 min</div>
                <div class="check-item"><i class="fas fa-check-circle"></i> Prestação de Contas sem Planilhas</div>
            </div>
        </div>
        
        <div class="animate" style="animation-delay: 0.2s;">
            <div class="builder-preview">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div style="display: flex; gap: 8px;">
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: #ff5f56;"></div>
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: #ffbd2e;"></div>
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: #27c93f;"></div>
                    </div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Landing Page Builder v2.0</div>
                </div>
                <h3 style="color: white; margin-bottom: 20px;">Arraste & Solte sua Landing Page de Doação</h3>
                <div style="background: rgba(255,255,255,0.05); border: 1px dashed rgba(255,255,255,0.2); border-radius: 12px; height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <i class="fas fa-magic" style="font-size: 2rem; color: #818cf8; margin-bottom: 15px;"></i>
                    <span style="color: #94a3b8;">Clique para Personalizar cada Seção</span>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <div style="flex: 1; height: 8px; background: #4f46e5; border-radius: 4px;"></div>
                    <div style="flex: 1; height: 8px; background: #4f46e5; border-radius: 4px; opacity: 0.5;"></div>
                    <div style="flex: 1; height: 8px; background: #4f46e5; border-radius: 4px; opacity: 0.2;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Showcase -->
    <section style="padding: 80px 5%; background: #f8fafc; text-align: center;">
        <div class="animate" style="max-width: 1000px; margin: 0 auto;">
            <span class="section-badge" style="background: #e0e7ff; color: #4338ca;">VEJA EM AÇÃO</span>
            <h2>Conheça a Revolução do Vivensi 2.0 para ONGs</h2>
            <p>Assista ao vídeo abaixo e descubra como simplificamos a transparência e a captação de recursos.</p>
            
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.2); background: #000; margin-top: 40px;">
                <iframe 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                    src="https://www.youtube.com/embed/placeholder" 
                    title="Vivensi para ONGs" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </section>

    <!-- Revolução em Gestão -->
    <section id="features" style="padding: 100px 5%; background: white;">
        <div style="text-align: center; max-width: 800px; margin: 0 auto 80px;">
            <span class="section-badge">TECNOLOGIA DE PONTA</span>
            <h2>O fim da era das planilhas confusas</h2>
            <p>O Vivensi foi desenhado para ONGs que buscam o próximo nível de governança. Entregamos o que há de mais moderno no mercado SAAS global para o Terceiro Setor brasileiro.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
            <div class="feature-box">
                <div style="width: 60px; height: 60px; background: #f0fdf4; color: #16a34a; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 25px;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Transparência 360º</h4>
                <p>Gere um link público da sua transparência com um clique. Demonstre para seus doadores onde cada centavo está sendo investido de forma visual e auditável.</p>
            </div>

            <div class="feature-box">
                <div style="width: 60px; height: 60px; background: #eff6ff; color: #3b82f6; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 25px;">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Construtor de Páginas</h4>
                <p>Crie Landing Pages de campanhas, doações e eventos sem precisar de programador. Modelos otimizados para conversão de novos doadores.</p>
            </div>

            <div class="feature-box">
                <div style="width: 60px; height: 60px; background: #fff7ed; color: #ea580c; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 25px;">
                    <i class="fas fa-university"></i>
                </div>
                <h4 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 15px;">Gestão de Verbas & Editais</h4>
                <p>Controle saldos por convênios, editais e doadores específicos. Nunca mais erre na prestação de contas de uma verba carimbada.</p>
            </div>
        </div>
    </section>

    <!-- Call to Acton -->
    <section style="padding: 100px 5%; background: #4f46e5; text-align: center; color: white;">
        <h2 style="color: white; margin-bottom: 20px;">Pronto para transformar sua ONG?</h2>
        <p style="color: #e0e7ff; max-width: 700px; margin: 0 auto 40px;">Junte-se a centenas de organizações que já digitalizaram seu impacto socioambiental.</p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('register') }}" class="btn-cta" style="background: white; color: #4f46e5; padding: 20px 50px; font-size: 1.3rem;">Começar Agora - 7 Dias Grátis</a>
            <a href="https://wa.me/5581999999999" class="btn-outline" style="border-color: white; color: white; padding: 20px 50px; font-size: 1.3rem; border-radius: 50px; text-decoration: none; font-weight: 700;">Falar com Especialista</a>
        </div>
    </section>

    <div id="pricing" style="padding: 100px 5%; background: var(--bg-light); text-align: center;">
        <span class="section-badge">INVESTIMENTO</span>
        <h2>O Valor de uma Gestão Profissional</h2>
        <p>Preços transparentes, sem letras miúdas. Comece hoje seu teste gratuito.</p>
        
        <div class="vivensi-card" style="max-width: 500px; margin: 0 auto; padding: 60px; background: white; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.1); border: 2px solid #4f46e5;">
            <div style="text-transform: uppercase; font-weight: 800; color: #4f46e5; letter-spacing: 2px; margin-bottom: 10px;">Plano Terceiro Setor</div>
            <div style="font-size: 4rem; font-weight: 900; color: #0f172a; margin-bottom: 10px;">R$ 147<span style="font-size: 1.2rem; color: #64748b; font-weight: 400;">/mês</span></div>
            <p>Acesso completo a todas as ferramentas de transparência e captação.</p>
            <hr style="opacity: 0.1; margin: 30px 0;">
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom: 40px;">
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Landing Pages Ilimitadas</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Portal da Transparência Premium</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Gestão de Doadores & CRM</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Prestação de Contas Automatizada</li>
            </ul>
            <a href="{{ route('register', ['plan_id' => 1]) }}" class="btn-cta" style="width: 100%; border-radius: 12px; font-size: 1.2rem;">Garantir Meus 7 Dias Grátis</a>
        </div>
    </div>
@endsection
