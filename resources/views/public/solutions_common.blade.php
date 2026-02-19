<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finanças Pessoais - Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #10b981; --secondary: #1e293b; --bg-light: #f9fafb; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #1e293b; background: white; }
        .hero-solution { padding: 120px 5% 60px; background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%); text-align: center; }
        .hero-solution h1 { font-size: 3rem; font-weight: 800; color: var(--secondary); margin-bottom: 20px; }
        .hero-solution p { font-size: 1.2rem; color: #64748b; max-width: 800px; margin: 0 auto 40px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 60px 5%; }
        .feature-item { text-align: center; padding: 30px; }
        .feature-icon { width: 70px; height: 70px; background: #d1fae5; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 20px; }
        .pricing-section { background: var(--bg-light); padding: 80px 5%; }
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 900px; margin: 40px auto 0; }
        .price-card { background: white; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn-cta { background: var(--primary); color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; }
        .btn-cta:hover { background: #059669; transform: scale(1.05); }
        .navbar { padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--secondary); text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="logo">VIVENSI</a>
        <a href="{{ route('login') }}" style="text-decoration: none; font-weight: 700; color: #64748b;">Entrar</a>
    </nav>

    <header class="hero-solution">
        <h1>Sua Vida Financeira no Controle</h1>
        <p>A simplicidade que você precisa para dominar seu orçamento, planejar o futuro e alcançar a liberdade financeira.</p>
        <a href="#pricing" class="btn-cta">Comece a Organizar Agora</a>
    </header>

    <div class="container">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Fluxo Diário</h3>
                    <p>Registre seus gastos e ganhos de forma rápida e intuitiva. Chega de planilhas complexas.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-bullseye"></i></div>
                    <h3>Metas de Economia</h3>
                    <p>Defina objetivos (viagens, reserva, sonhos) e acompanhe seu progresso mês a mês.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                    <h3>Gráficos Visuais</h3>
                    <p>Entenda exatamente para onde seu dinheiro está indo com gráficos que fazem sentido.</p>
                </div>
            </div>
        </div>
    </div>

    <section class="pricing-section" id="pricing">
        <div style="text-align: center;">
            <h2>Planos Para Você</h2>
            <p>Escolha a simplicidade.</p>

            <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 20px;">
                <span style="font-weight: 600; color: #64748b;" id="label-monthly">Mensal</span>
                <label class="switch" style="position: relative; display: inline-block; width: 60px; height: 34px;">
                    <input type="checkbox" id="billing-toggle" onchange="toggleBilling()">
                    <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                    <span class="slider-icon" style="position: absolute; content: ''; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                </label>
                <span style="font-weight: 600; color: #1e293b;" id="label-yearly">Anual <span style="font-size: 0.75rem; color: #10b981; background: #d1fae5; padding: 2px 8px; border-radius: 12px; margin-left: 5px;">-10% OFF</span></span>
            </div>
        </div>
        
        <div class="pricing-grid">
            @forelse($plans as $plan)
                <div class="price-card">
                    <h3 style="margin: 0; color: var(--secondary);">{{ $plan->name }}</h3>
                    <div class="price-display" 
                         data-price-monthly="{{ $plan->price }}" 
                         data-price-yearly="{{ $plan->price_yearly ?? ($plan->price * 12 * 0.9) }}" 
                         style="font-size: 2.5rem; font-weight: 800; margin: 20px 0;">
                         R$ <span class="amount">{{ number_format($plan->price, 2, ',', '.') }}</span>
                         <span class="period" style="font-size: 1rem; color: #64748b; font-weight: 400;">/mês</span>
                    </div>
                    <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 30px;">
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li style="margin-bottom: 10px;"><i class="fas fa-check text-primary me-2"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <a href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']) }}" class="btn-cta w-100 btn-subscribe" data-plan-id="{{ $plan->id }}">Criar Minha Conta</a>
                </div>
            @empty
                <div style="text-align: center; width: 100%;">
                    <p class="text-muted">Aproveite nossa versão gratuita por tempo limitado.</p>
                    <a href="{{ route('login') }}" class="btn-cta">Criar Conta Grátis</a>
                </div>
            @endforelse
        </div>
        </div>
    </section>

    <style>
        .switch input { opacity: 0; width: 0; height: 0; }
        .switch input:checked + .slider { background-color: var(--primary); }
        .switch input:focus + .slider { box-shadow: 0 0 1px var(--primary); }
        .switch input:checked + .slider .slider-icon { transform: translateX(26px); }
    </style>

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
                    // Show yearly price
                    amountSpan.textContent = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(yearly);
                    periodSpan.textContent = '/ano';
                } else {
                    // Show monthly price
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

    <footer style="padding: 60px 5%; background: var(--secondary); color: #94a3b8; text-align: center;">
        <p>&copy; 2026 Vivensi. Sua jornada financeira começa aqui.</p>
    </footer>
</body>
</html>
