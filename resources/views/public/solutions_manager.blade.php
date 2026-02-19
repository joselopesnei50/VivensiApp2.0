<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi - Gestão de Projetos e Empresas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/logovivensi.png') }}">
    
    <style>
        /* Copied from welcome.blade.php with Manager tweaks */
        :root {
            --primary: #4f46e5;       
            --primary-dark: #4338ca;
            --secondary: #0f172a;
            --accent: #16a34a; /* Manager Accent Color (Green) */
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            
            --m3-surface: #FDFBFF;
            --m3-ease-out: cubic-bezier(0.2, 0.0, 0, 1.0);
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            background: white;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            position: fixed;
            width: 90%;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links { display: flex; gap: 30px; }
        .nav-links a { text-decoration: none; color: var(--text-light); font-weight: 600; transition: color 0.3s; }
        .nav-links a:hover { color: var(--primary); }

        .btn-cta {
            background: var(--primary);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }
        .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4); background: var(--primary-dark); }
        
        .btn-outline {
            border: 2px solid #e2e8f0;
            color: var(--text-main);
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        /* Hero M3 */
        .hero-m3 {
            position: relative;
            padding: 140px 5% 100px;
            overflow: hidden;
            background: #F8FAFC;
        }
        
        .hero-m3-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; opacity: 0.6; pointer-events: none; }
        .blob { position: absolute; filter: blur(80px); opacity: 0.8; border-radius: 50%; }
        .blob-1 { top: -10%; right: -5%; width: 600px; height: 600px; background: #dcfce7; /* Green Tint */ opacity: 0.5; } 
        .blob-2 { bottom: -10%; left: -10%; width: 500px; height: 500px; background: #E0E7FF; opacity: 0.4; }
        
        .hero-m3-grid { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; max-width: 1400px; margin: 0 auto; align-items: center; }

        .m3-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px;
            background: white; border: 1px solid #E2E8F0; border-radius: 100px;
            color: var(--accent); font-weight: 700; font-size: 0.85rem; margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .hero-display { font-size: 3.5rem; line-height: 1.1; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em; margin: 0 0 24px 0; }
        .hero-body { font-size: 1.2rem; color: var(--text-light); line-height: 1.6; margin-bottom: 40px; }

        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
        .feature-card { background: white; padding: 40px; border-radius: 20px; transition: all 0.3s; border: 1px solid transparent; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -5px rgba(0,0,0,0.05); border-color: #e2e8f0; }

        .feature-icon {
            width: 60px; height: 60px; background: #f0fdf4; color: var(--accent);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 25px; transition: all 0.5s;
        }

        /* Pricing */
        .pricing-section { padding: 100px 5%; background: white; }
        .price-card { background: white; padding: 40px; border-radius: 20px; border: 1px solid #e2e8f0; text-align: center; position: relative; }
        .price-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -5px rgba(0,0,0,0.1); border-color: var(--primary); }
        
        .price-display { font-size: 3rem; font-weight: 800; color: var(--secondary); margin: 20px 0; }
        .price-display .period { font-size: 1rem; color: var(--text-light); font-weight: 400; }

        /* Footer */
        footer { background: var(--secondary); color: #94a3b8; padding: 80px 5%; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        
        /* Mobile */
        @media (max-width: 768px) {
            .hero-m3 { padding-top: 120px; }
            .hero-m3-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-actions { justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
        }
        
        /* Toggle */
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="{{ url('/') }}" class="logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 40px;">
        </a>
        <div class="nav-links">
            <a href="{{ route('solutions.ngo') }}">ONGs</a>
            <a href="{{ route('solutions.manager') }}" style="color: var(--primary);">Gestores</a>
            <a href="{{ route('solutions.common') }}">Pessoal</a>
        </div>
        <div>
            <a href="{{ route('login') }}" class="btn-outline">Entrar</a>
            <a href="#pricing" class="btn-cta ms-3">Começar</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-m3">
        <div class="hero-m3-bg">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>
        <div class="hero-m3-grid">
            <div class="hero-content">
                <div class="m3-badge">
                    <i class="fas fa-chart-line"></i> 
                    <span>Para Gestores de Alta Performance</span>
                </div>
                <h1 class="hero-display">
                    Escale seus projetos <br>
                    <span style="color: var(--accent);">sem perder o controle.</span>
                </h1>
                <p class="hero-body">
                    A primeira plataforma que une Kanban, Gantt e Financeiro em tempo real. Tome decisões baseadas em dados, não em palpites.
                </p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="#pricing" class="btn-cta" style="padding: 15px 35px; font-size: 1.1rem;">Ver Planos</a>
                    <a href="#" class="btn-outline" style="padding: 15px 35px; background: white;">Agendar Demo</a>
                </div>
            </div>
            <div class="hero-visual" style="text-align: center;">
                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=600" style="border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 100%; transform: rotate(2deg); border: 4px solid white;">
            </div>
        </div>
    </section>

    <!-- Features -->
    <section style="padding: 100px 5%; background: white;">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--secondary);">Domine a operação</h2>
            <p style="color: var(--text-light);">Um sistema, múltiplas soluções integradas.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-tasks"></i></div>
                <h3>Gestão Híbrida</h3>
                <p style="color: var(--text-light);">Visualize o mesmo projeto em Kanban, Lista ou Gantt. Adapte a ferramenta ao seu fluxo, não o contrário.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-coins"></i></div>
                <h3>Margem Real</h3>
                <p style="color: var(--text-light);">Vincule cada despesa e receita a um centro de custo de projeto. Saiba exatamente qual cliente é mais rentável.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-fingerprint"></i></div>
                <h3>Segurança Total</h3>
                <p style="color: var(--text-light);">Logs de auditoria em todas as ações. Saiba quem fez o quê e quando. Controle de permissões granular.</p>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="pricing-section" id="pricing">
        <div style="text-align: center;">
            <span class="m3-badge" style="color: var(--primary);">Escalabilidade</span>
            <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--secondary); margin-top: 10px;">Potencialize sua empresa</h2>
            
            <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 30px;">
                <span style="font-weight: 600; color: #64748b;" id="label-monthly">Mensal</span>
                <label class="switch">
                    <input type="checkbox" id="billing-toggle" onchange="toggleBilling()">
                    <span class="slider round"></span>
                </label>
                <span style="font-weight: 600; color: #1e293b;" id="label-yearly">Anual <span style="font-size: 0.75rem; color: #0ea5e9; background: #e0f2fe; padding: 2px 8px; border-radius: 12px; margin-left: 5px;">-10% OFF</span></span>
            </div>
        </div>
        
        <div class="feature-grid" style="margin-top: 50px;">
            @forelse($plans as $plan)
                <div class="price-card">
                    <h3 style="margin: 0; font-size: 1.5rem; color: var(--secondary);">{{ $plan->name }}</h3>
                    <div class="price-display" 
                         data-price-monthly="{{ $plan->price }}" 
                         data-price-yearly="{{ $plan->price_yearly ?? ($plan->price * 12 * 0.9) }}">
                         R$ <span class="amount">{{ number_format($plan->price, 2, ',', '.') }}</span>
                         <span class="period">/mês</span>
                    </div>
                    <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 30px;">
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li style="margin-bottom: 12px; color: var(--text-light);"><i class="fas fa-check-circle" style="color: var(--accent); margin-right: 10px;"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <a href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']) }}" class="btn-cta w-100 btn-subscribe" data-plan-id="{{ $plan->id }}" style="display: block;">Assinar Agora</a>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center;">
                    <p class="text-muted">Planos sob consulta.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div>
                <a href="{{ url('/') }}" class="logo" style="margin-bottom: 20px;">
                    <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Logo" style="height: 35px; filter: brightness(0) invert(1);">
                </a>
                <p>Tecnologia para quem transforma o mundo.</p>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Produto</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="#features" style="color: #94a3b8; text-decoration: none;">Recursos</a>
                    <a href="#pricing" style="color: #94a3b8; text-decoration: none;">Planos</a>
                </div>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Empresa</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('public.page', 'sobre') }}" style="color: #94a3b8; text-decoration: none;">Sobre</a>
                    <a href="{{ route('login') }}" style="color: #94a3b8; text-decoration: none;">Login</a>
                </div>
            </div>
             <div>
                <h4 style="color: white; margin-bottom: 20px;">Legal</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('public.page', 'privacidade') }}" style="color: #94a3b8; text-decoration: none;">Privacidade</a>
                    <a href="{{ route('public.page', 'termos') }}" style="color: #94a3b8; text-decoration: none;">Termos</a>
                </div>
            </div>
        </div>
        <div style="text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 40px; margin-top: 40px;">
            <p>&copy; 2026 Vivensi. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        function toggleBilling() {
            const isYearly = document.getElementById('billing-toggle').checked;
            const prices = document.querySelectorAll('.price-display');
            const buttons = document.querySelectorAll('.btn-subscribe');

            prices.forEach(price => {
                const amountSpan = price.querySelector('.amount');
                const periodSpan = price.querySelector('.period');
                const monthly = parseFloat(price.dataset.priceMonthly);
                const yearly = parseFloat(price.dataset.priceYearly);

                if (isYearly) {
                    amountSpan.textContent = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(yearly);
                    periodSpan.textContent = '/ano';
                } else {
                    amountSpan.textContent = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(monthly);
                    periodSpan.textContent = '/mês';
                }
            });

            buttons.forEach(btn => {
                const planId = btn.dataset.planId;
                const cycle = isYearly ? 'yearly' : 'monthly';
                btn.href = `/register?plan_id=${planId}&billing_cycle=${cycle}`;
            });
        }
    </script>
</body>
</html>
