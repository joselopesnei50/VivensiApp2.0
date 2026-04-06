<div style="position: relative; border-radius: 24px; overflow: hidden; background: #0f172a; margin-bottom: 32px; border: 1px solid rgba(99,102,241,0.15); box-shadow: 0 20px 50px rgba(0,0,0,0.2);">

    {{-- Glow bg --}}
    <div style="position: absolute; top: -80px; right: -80px; width: 300px; height: 300px; background: rgba(99,102,241,0.1); border-radius: 50%; filter: blur(80px); pointer-events: none;"></div>
    <div style="position: absolute; bottom: -60px; left: -60px; width: 200px; height: 200px; background: rgba(16,185,129,0.06); border-radius: 50%; filter: blur(60px); pointer-events: none;"></div>

    {{-- Grid dot pattern --}}
    <div style="position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 28px 28px; pointer-events: none;"></div>

    <div style="position: relative; z-index: 1; padding: 36px 44px; display: flex; align-items: center; justify-content: space-between; gap: 32px; flex-wrap: wrap;">

        {{-- Conteúdo --}}
        <div style="flex: 1; min-width: 280px;">
            {{-- Badge de status --}}
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 20px; padding: 5px 14px; margin-bottom: 18px;">
                <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #10b981; animation: pulse-dot 2s infinite;"></span>
                <span style="font-size: 0.62rem; font-weight: 900; color: #34d399; text-transform: uppercase; letter-spacing: 1.8px;">Sistemas Operacionais</span>
            </div>

            <h3 style="margin: 0 0 10px; font-size: 2rem; font-weight: 950; color: white; letter-spacing: -1.5px; line-height: 1.05;">
                Bem-vindo, <span style="background: linear-gradient(90deg, #818cf8, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">{{ explode(' ', auth()->user()->name)[0] }}</span>!
            </h3>

            <p style="margin: 0; color: rgba(255,255,255,0.45); font-size: 0.95rem; line-height: 1.7; max-width: 560px;">
                Sua Central de Comando está ativa. Monitore indicadores em tempo real, gerencie projetos e conte com o Bruce AI para antecipar decisões estratégicas.
            </p>

            {{-- Métricas inline --}}
            <div style="display: flex; gap: 24px; margin-top: 22px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px;">
                    <i class="fas fa-shield-alt" style="color: #818cf8; font-size: 0.8rem;"></i>
                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700;">Acesso Seguro</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px;">
                    <i class="fas fa-robot" style="color: #34d399; font-size: 0.8rem;"></i>
                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700;">Bruce AI Ativo</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px;">
                    <i class="fas fa-clock" style="color: #fbbf24; font-size: 0.8rem;"></i>
                    <span style="font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 700;">{{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Avatar Bruce --}}
        <div class="d-none d-lg-flex" style="align-items: center; justify-content: center;">
            <div style="position: relative;">
                <div style="position: absolute; inset: -12px; background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%); border-radius: 50%; animation: glow-ring 3s ease-in-out infinite;"></div>
                <div style="position: absolute; inset: -4px; border: 1px solid rgba(99,102,241,0.3); border-radius: 50%; animation: spin-slow 8s linear infinite;"></div>
                <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce AI"
                     style="width: 90px; height: 90px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.1); object-fit: cover; position: relative; z-index: 1; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                <div style="position: absolute; bottom: 2px; right: 2px; width: 16px; height: 16px; background: #10b981; border: 2px solid #0f172a; border-radius: 50%; z-index: 2; box-shadow: 0 0 8px #10b981;"></div>
            </div>
        </div>
    </div>

    <style>
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.3); }
    }
    @keyframes glow-ring {
        0%, 100% { opacity: 0.6; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.05); }
    }
    @keyframes spin-slow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
</div>
