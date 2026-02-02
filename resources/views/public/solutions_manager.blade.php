<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Projetos - Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #0ea5e9; --secondary: #0f172a; --bg-light: #f8fafc; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; color: #1e293b; background: white; }
        .hero-solution { padding: 120px 5% 60px; background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%); text-align: center; }
        .hero-solution h1 { font-size: 3rem; font-weight: 800; color: var(--secondary); margin-bottom: 20px; }
        .hero-solution p { font-size: 1.2rem; color: #64748b; max-width: 800px; margin: 0 auto 40px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 60px 5%; }
        .feature-item { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 40px; }
        .feature-icon { width: 60px; height: 60px; background: #e0f2fe; color: var(--primary); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .pricing-section { background: var(--bg-light); padding: 80px 5%; }
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px; }
        .price-card { background: white; padding: 40px; border-radius: 24px; border: 1px solid #e2e8f0; text-align: center; }
        .btn-cta { background: var(--primary); color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: all 0.3s; }
        .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(14, 165, 233, 0.2); }
        .navbar { padding: 20px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--secondary); text-decoration: none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="{{ url('/') }}" class="logo">VIVENSI</a>
        <a href="{{ route('login') }}" style="text-decoration: none; font-weight: 700; color: var(--primary);">Entrar</a>
    </nav>

    <header class="hero-solution">
        <h1>Gestão de Projetos de Alta Eficiência</h1>
        <p>Potencialize sua equipe com quadros Kanban, controle financeiro integrado e monitoramento de rentabilidade em tempo real.</p>
        <a href="#pricing" class="btn-cta">Explorar Planos Corporativos</a>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-columns"></i></div>
                    <div>
                        <h3>Kanban & Fluxo</h3>
                        <p>Visualize o progresso das tarefas de forma intuitiva. Arraste e solte para atualizar o status e manter todos alinhados.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <h3>Financeiro Integrado</h3>
                        <p>Vincule todas as despesas e receitas diretamente aos projetos para ter uma visão clara da margem de lucro.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-users-cog"></i></div>
                    <div>
                        <h3>Gestão de Equipe</h3>
                        <p>Atribua tarefas, defina níveis de acesso e monitore a carga de trabalho de cada colaborador com facilidade.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-sync-alt"></i></div>
                    <div>
                        <h3>Conciliação Bancária</h3>
                        <p>Sincronize seus extratos e importe dados para uma conciliação rápida, evitando erros manuais no projeto.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="pricing-section" id="pricing">
        <div style="text-align: center;">
            <h2>Planos para Gestores de Projetos</h2>
            <p>Escolha a escala ideal para os projetos da sua empresa.</p>
        </div>
        
        <div class="pricing-grid">
            @forelse($plans as $plan)
                <div class="price-card">
                    <h3 style="margin: 0;">{{ $plan->name }}</h3>
                    <div style="font-size: 2.5rem; font-weight: 800; margin: 20px 0;">R$ {{ number_format($plan->price, 0, ',', '.') }}<span style="font-size: 1rem; color: #64748b; font-weight: 400;">/mês</span></div>
                    <ul style="list-style: none; padding: 0; text-align: left; margin-bottom: 30px;">
                        @if($plan->features)
                            @foreach($plan->features as $feature)
                                <li style="margin-bottom: 10px;"><i class="fas fa-check text-info me-2"></i> {{ $feature }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <a href="{{ route('register', ['plan_id' => $plan->id]) }}" class="btn-cta w-100" style="background: var(--secondary);">Contratar Plano</a>
                </div>
            @empty
                <div style="text-align: center; width: 100%;">
                    <p class="text-muted">Aguardando definição de planos pelo administrador.</p>
                </div>
            @endforelse
        </div>
    </section>

    <footer style="padding: 60px 5%; background: var(--secondary); color: white; text-align: center;">
        <p>&copy; 2026 Vivensi. Performance e Resultado.</p>
    </footer>
</body>
</html>
