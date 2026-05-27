<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Portal | {{ $donor->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #020617;
            --accent: #4f46e5;
            --accent-light: #0ea5e9;
            --glass: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(79, 70, 229, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 40%);
        }

        .navbar {
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(2, 6, 23, 0.8);
        }

        .brand {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-hero {
            text-align: center;
            margin-bottom: 60px;
        }

        .welcome-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #fff 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-hero p {
            color: var(--text-dim);
            font-size: 1.2rem;
            margin-top: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-10px);
            border-color: rgba(79, 70, 229, 0.3);
        }

        .stat-label {
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--text-dim);
            margin-bottom: 10px;
            display: block;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-icon {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.05;
            color: var(--accent);
            transform: rotate(-15deg);
        }

        .btn-action {
            background: linear-gradient(135deg, var(--accent) 0%, #4338ca 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }

        .btn-action:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
        }

        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .history-table tr {
            background: rgba(255,255,255,0.02);
            transition: background 0.3s;
        }

        .history-table tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .history-table td {
            padding: 20px;
        }

        .history-table td:first-child {
            border-radius: 12px 0 0 12px;
        }

        .history-table td:last-child {
            border-radius: 0 12px 12px 0;
            text-align: right;
            font-weight: 700;
        }

        .badge-income {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .welcome-hero h1 { font-size: 2.5rem; }
            .stat-value { font-size: 2.2rem; }
            .navbar { padding: 20px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">VIVENSI <span style="font-weight: 300; opacity: 0.5;">| Donor Portal</span></div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-shield-halved" style="color: #10b981;"></i>
            <span style="font-size: 0.85rem; color: var(--text-dim);">Conexão Criptografada</span>
        </div>
    </nav>

    <div class="container">
        
        <header class="welcome-hero animate__animated animate__fadeIn">
            <h1>Olá, {{ explode(' ', $donor->name)[0] }}! 💙</h1>
            <p>Seja bem-vindo ao seu painel de transparência e impacto social.</p>
            
            @if(session('success'))
                <div class="animate__animated animate__headShake" style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 15px; border-radius: 12px; margin-top: 20px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif
        </header>

        <div class="stats-grid">
            <!-- Perfil / Cadastro -->
            <div class="glass-card animate__animated animate__fadeInUp" style="animation-delay: 0.05s;">
                <span class="stat-label">Seu Cadastro</span>
                <form action="{{ url('/portal-doador/'.$donor->portal_token.'/update') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 15px;">
                        <input type="text" name="name" value="{{ $donor->name }}" placeholder="Seu Nome" style="width:100%; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; margin-bottom: 10px;">
                        <input type="text" name="phone" value="{{ $donor->phone }}" placeholder="Seu Telefone" style="width:100%; padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: white; margin-bottom: 10px;">
                        @if($donor->email)
                        <div style="padding: 10px; border-radius: 8px; background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.06); color: rgba(255,255,255,0.45); font-size: 0.85rem; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-lock" style="font-size:.75rem;"></i>
                            {{ $donor->email }}
                            <span style="font-size:.7rem; opacity:.7;">— Para alterar o e-mail, contate a organização.</span>
                        </div>
                        @endif
                    </div>
                    <button type="submit" class="btn-action" style="width: 100%; justify-content: center; padding: 10px; font-size: 0.9rem;">
                        <i class="fas fa-save"></i> Atualizar Dados
                    </button>
                </form>
            </div>
            <!-- Investimento Social -->
            <div class="glass-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <span class="stat-label">Investimento Total</span>
                <div class="stat-value">R$ {{ number_format($totalDonated, 2, ',', '.') }}</div>
                <p style="margin-top: 15px; color: var(--text-dim); line-height: 1.5;">
                    Obrigado por investir no futuro. Suas doações são transformadas em resultados reais.
                </p>
                <i class="fas fa-hand-holding-heart stat-icon"></i>
            </div>

            <!-- Vidas Impactadas -->
            <div class="glass-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <span class="stat-label">Impacto Estimado</span>
                @php
                    $impact = floor($totalDonated / 50); // Média fictícia: R$ 50 impacta 1 pessoa
                @endphp
                <div class="stat-value" style="color: var(--accent-light);">+ {{ $impact }} Vidas</div>
                <p style="margin-top: 15px; color: var(--text-dim); line-height: 1.5;">
                    Pessoas que tiveram suas trajetórias mudadas diretamente através do seu apoio.
                </p>
                <i class="fas fa-users stat-icon" style="color: var(--accent-light);"></i>
            </div>
        </div>

        <!-- IR & Documentos -->
        <section class="glass-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s; margin-bottom: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 300px;">
                    <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-file-invoice-dollar" style="color: var(--accent);"></i>
                        Informe de Rendimentos
                    </h2>
                    <p style="color: var(--text-dim); margin-top: 8px;">Documento oficial para sua declaração anual de IR.</p>
                </div>
                
                <form action="{{ url('/portal-doador/'.$donor->portal_token.'/ir-pdf') }}" method="GET" style="display: flex; gap: 12px;">
                    <select name="year" style="padding: 14px; border-radius: 12px; background: rgba(0,0,0,0.3); color: white; border: 1px solid rgba(255,255,255,0.1); outline: none; font-family: inherit;">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                        @if(count($years) === 0)
                            <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                        @endif
                    </select>
                    <button type="submit" class="btn-action">
                        <i class="fas fa-download"></i> Gerar PDF
                    </button>
                </form>
            </div>
        </section>

        <!-- Histórico -->
        <section class="glass-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            <h3 style="margin-bottom: 25px; font-size: 1.5rem;">Histórico de Transparência</h3>
            
            @if(count($donations) > 0)
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <tbody>
                        @foreach($donations as $d)
                        <tr>
                            <td style="width: 120px; color: var(--text-dim);">{{ $d->date->format('d M, Y') }}</td>
                            <td>
                                <div style="font-weight: 500;">{{ $d->description ?? 'Doação Recebida' }}</div>
                                <div class="badge-income">Confirmada</div>
                            </td>
                            <td style="color: #10b981;">R$ {{ number_format($d->amount, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: rgba(255,255,255,0.05); margin-bottom: 20px;"></i>
                <p style="color: var(--text-dim);">Ainda não encontramos registros de doações para este perfil.</p>
            </div>
            @endif
        </section>

        <footer style="text-align: center; margin-top: 60px; color: var(--text-dim); font-size: 0.9rem;">
            <p>&copy; {{ date('Y') }} Vivensi App. Todos os dados são processados com segurança de ponta-a-ponta.</p>
            <div style="margin-top: 15px; display: flex; justify-content: center; gap: 20px;">
                <a href="#" style="color: var(--text-dim); text-decoration: none;">Privacidade</a>
                <a href="#" style="color: var(--text-dim); text-decoration: none;">Termos de Uso</a>
                <a href="#" style="color: var(--text-dim); text-decoration: none;">Contato</a>
            </div>
        </footer>
    </div>

</body>
</html>
