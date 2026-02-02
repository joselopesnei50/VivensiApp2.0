@if(isset($onboarding) && !$onboarding['completed'])
<div class="vivensi-card animate__animated animate__fadeInDown" style="background: white; border-radius: 24px; padding: 40px; margin-bottom: 40px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.03);">
    <!-- Decorative Accents -->
    <div style="position: absolute; top: -50px; left: -50px; width: 200px; height: 200px; background: rgba(79, 70, 229, 0.05); border-radius: 50%; filter: blur(40px);"></div>
    <div style="position: absolute; bottom: -50px; right: -50px; width: 150px; height: 150px; background: rgba(16, 185, 129, 0.05); border-radius: 50%; filter: blur(30px);"></div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
        <div style="max-width: 800px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 45px; height: 45px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                    <i class="fas fa-sparkles"></i>
                </div>
                <h4 style="color: #1e293b; font-weight: 900; margin: 0; font-size: 1.8rem; letter-spacing: -1px;">
                    Bem-vindo à Nova Era do Vivensi, {{ explode(' ', auth()->user()->name)[0] }}!
                </h4>
            </div>
            <p style="color: #64748b; font-size: 1.1rem; line-height: 1.7; margin-bottom: 30px; font-weight: 500;">
                Preparamos um ambiente de alta performance para você gerenciar sua organização. Explore o painel, configure suas preferências e deixe o Bruce AI cuidar da análise pesada dos seus dados.
            </p>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                @foreach($onboarding['steps'] as $step)
                    @php
                        $targetUrl = $step['link'] === '#' ? url('/dashboard') : (str_starts_with($step['link'], 'http') ? $step['link'] : url($step['link']));
                    @endphp
                    <a href="{{ $targetUrl }}" class="btn-premium {{ $step['completed'] ? 'completed' : '' }}" 
                       style="padding: 12px 24px; font-size: 0.85rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 10px; 
                              background: {{ $step['completed'] ? '#f1f5f9' : 'var(--primary-color)' }}; 
                              color: {{ $step['completed'] ? '#94a3b8' : 'white' }}; 
                              border-radius: 14px; transition: all 0.3s;
                              {{ $step['completed'] ? 'pointer-events: none; opacity: 0.7;' : '' }}">
                        <i class="fas {{ $step['completed'] ? 'fa-check-circle' : 'fa-circle' }}"></i>
                        {{ $step['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="d-none d-lg-block" style="text-align: right;">
           <div style="position: relative;">
                <div class="ai-pulse-glow" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 120px; height: 120px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; z-index: -1;"></div>
                <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.1); object-fit: cover;">
           </div>
        </div>
    </div>
</div>
@endif
