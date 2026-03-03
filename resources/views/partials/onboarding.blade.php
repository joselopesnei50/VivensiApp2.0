<div class="vivensi-card animate__animated animate__fadeInDown" style="background: white; border-radius: 24px; padding: 40px; margin-bottom: 40px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.03);">
    <!-- Decorative Accents -->
    <div style="position: absolute; top: -50px; left: -50px; width: 200px; height: 200px; background: rgba(79, 70, 229, 0.05); border-radius: 50%; filter: blur(40px);"></div>
    <div style="position: absolute; bottom: -50px; right: -50px; width: 150px; height: 150px; background: rgba(16, 185, 129, 0.05); border-radius: 50%; filter: blur(30px);"></div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
        <div style="max-width: 800px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 45px; height: 45px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                    <i class="fas fa-hand-sparkles"></i>
                </div>
                <h4 style="color: #1e293b; font-weight: 800; margin: 0; font-size: 1.8rem; letter-spacing: -1px;">
                    Que bom ter você aqui, {{ explode(' ', auth()->user()->name)[0] }}!
                </h4>
            </div>
            <p style="color: #64748b; font-size: 1.1rem; line-height: 1.6; margin-bottom: 0; font-weight: 500;">
                Seu ambiente de gestão está pronto. Explore as ferramentas, acompanhe seus indicadores e conte com a inteligência do Bruce AI para otimizar seus resultados todos os dias.
            </p>
        </div>
        <div class="d-none d-lg-block" style="text-align: right;">
           <div style="position: relative;">
                <div class="ai-pulse-glow" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 120px; height: 120px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; z-index: -1;"></div>
                <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.08); object-fit: cover;">
           </div>
        </div>
    </div>
</div>
