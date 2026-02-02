@extends('layouts.landing_sales')

@section('title', 'Domine suas Finanças com Inteligência Artificial')
@section('meta_description', 'Controle bancário automático, categorização por IA e metas financeiras. Sobra dinheiro no final do mês com o Vivensi Pessoal.')

@section('styles')
<style>
    .hero-personal {
        padding: 180px 5% 100px;
        background: radial-gradient(circle at bottom right, #eff6ff 0%, #ffffff 50%);
        text-align: center;
    }
    .hero-personal h1 { font-size: 4.2rem; font-weight: 800; line-height: 1; margin-bottom: 25px; color: #1e293b; letter-spacing: -0.05em; }
    .hero-personal h1 span { color: #3b82f6; }
    
    .mobile-mockup {
        width: 280px;
        height: 580px;
        background: #000;
        border-radius: 40px;
        margin: 60px auto 0;
        border: 8px solid #1e293b;
        position: relative;
        overflow: hidden;
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.3);
    }
    .mobile-screen {
        background: #f8fafc;
        height: 100%;
        padding: 40px 20px;
    }
</style>
@endsection

@section('content')
    <!-- Hero -->
    <section class="hero-personal">
        <div class="animate" style="max-width: 900px; margin: 0 auto;">
            <span class="section-badge" style="background: #e0f2fe; color: #0369a1;">LIBERDADE FINANCEIRA INTELIGENTE</span>
            <h1>A sua conta bancária sob o <span>controle da IA</span>.</h1>
            <p style="font-size: 1.4rem; max-width: 700px; margin: 0 auto 40px;">Esqueça as anotações manuais. Importe seu extrato, deixe o Vivensi categorizar tudo e receba dicas estratégicas para sobrar dinheiro no final do mês.</p>
            
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('register', ['plan_id' => 3]) }}" class="btn-cta" style="background: #2563eb; font-size: 1.2rem; padding: 20px 50px;">Começar Grátis - 7 Dias</a>
                <a href="#features" class="btn-outline" style="padding: 20px 50px; border-radius: 50px; text-decoration: none; font-weight: 700;">Ver Como Funciona</a>
            </div>

            <div class="mobile-mockup">
                <div class="mobile-screen">
                    <div style="font-weight: 800; font-size: 0.9rem; color: #1e293b; margin-bottom: 20px; text-align: left;">Minhas Finanças</div>
                    <div style="background: white; border-radius: 12px; padding: 15px; margin-bottom: 15px; text-align: left; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <div style="font-size: 0.6rem; color: #64748b;">SALDO TOTAL</div>
                        <div style="font-size: 1.1rem; font-weight: 800;">R$ 12.450,00</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="background: #ecfdf5; border-radius: 10px; padding: 10px; text-align: left;">
                            <div style="font-size: 0.5rem; color: #065f46;">ENTRADAS</div>
                            <div style="font-size: 0.75rem; font-weight: 800; color: #059669;">+ R$ 5.200</div>
                        </div>
                        <div style="background: #fef2f2; border-radius: 10px; padding: 10px; text-align: left;">
                            <div style="font-size: 0.5rem; color: #991b1b;">SAÍDAS</div>
                            <div style="font-size: 0.75rem; font-weight: 800; color: #dc2626;">- R$ 3.800</div>
                        </div>
                    </div>
                    <div style="margin-top: 30px; text-align: left;">
                        <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; margin-bottom: 10px;">DICA DA IA: 🤖</div>
                        <div style="background: #eff6ff; border-radius: 12px; padding: 12px; font-size: 0.65rem; color: #1d4ed8; line-height: 1.4;">
                            Você gastou 15% a menos com Restaurantes este mês. Excelente! Que tal investir esses R$ 400,00 no Tesouro Direto?
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Showcase -->
    <section style="padding: 80px 5%; background: #f8fafc; text-align: center;">
        <div class="animate" style="max-width: 1000px; margin: 0 auto;">
            <span class="section-badge" style="background: #e0f2fe; color: #0369a1;">SISTEMA NA PRÁTICA</span>
            <h2>Gestão na Palma da sua Mão</h2>
            <p>Assista e veja como é fácil organizar sua vida financeira e decolar com a ajuda da nossa Inteligência Artificial.</p>
            
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.2); background: #000; margin-top: 40px;">
                <iframe 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                    src="https://www.youtube.com/embed/placeholder" 
                    title="Vivensi Pessoal" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" style="padding: 100px 5%; background: white;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto;">
            <div class="feature-box" style="text-align: center;">
                <i class="fas fa-file-invoice-dollar" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 25px;"></i>
                <h4 style="font-weight: 800; margin-bottom: 15px;">Conciliação OFX</h4>
                <p>Abra o app do seu banco, exporte o OFX e pronto. O Vivensi faz a leitura e organiza seus gastos sem você digitar um único valor.</p>
            </div>
            <div class="feature-box" style="text-align: center;">
                <i class="fas fa-brain" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 25px;"></i>
                <h4 style="font-weight: 800; margin-bottom: 15px;">Planejamento IA</h4>
                <p>Crie orçamentos anuais e deixe nossa inteligência cuidar do seu futuro financeiro com avisos e projeções automáticas.</p>
            </div>
            <div class="feature-box" style="text-align: center;">
                <i class="fas fa-lock" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 25px;"></i>
                <h4 style="font-weight: 800; margin-bottom: 15px;">Privacidade Total</h4>
                <p>Seus dados financeiros são criptografados e inacessíveis para terceiros. O Vivensi não compartilha nem vende suas informações.</p>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <div id="pricing" style="padding: 100px 5%; background: var(--bg-light); text-align: center;">
        <span class="section-badge">VALOR JUSTO</span>
        <h2>O preço de um café por mês</h2>
        
        <div style="max-width: 450px; margin: 0 auto; padding: 60px; background: white; border-radius: 30px; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.1); border: 1px solid #2563eb;">
            <div style="text-transform: uppercase; font-weight: 800; color: #1e40af; letter-spacing: 2px; margin-bottom: 10px;">Plano Pessoal Premium</div>
            <div style="font-size: 4rem; font-weight: 900; color: #1e293b; margin-bottom: 10px;">R$ 27<span style="font-size: 1.2rem; color: #64748b; font-weight: 400;">/mês</span></div>
            <p>Tudo o que você precisa para dominar seu dinheiro e planejar seu futuro.</p>
            <hr style="opacity: 0.1; margin: 30px 0;">
            <ul style="text-align: left; list-style: none; padding: 0; margin-bottom: 40px;">
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #3b82f6; margin-right: 10px;"></i> Importação de Extratos OFX</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #3b82f6; margin-right: 10px;"></i> Categorização com IA</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #3b82f6; margin-right: 10px;"></i> Orçamento e Metas Anuais</li>
                <li style="margin-bottom: 15px;"><i class="fas fa-check-circle" style="color: #3b82f6; margin-right: 10px;"></i> Dashboard Mobile Full</li>
            </ul>
            <a href="{{ route('register', ['plan_id' => 3]) }}" class="btn-cta" style="width: 100%; border-radius: 12px; font-size: 1.2rem; background: #2563eb;">Experimentar Grátis Agora</a>
        </div>
    </div>
@endsection
