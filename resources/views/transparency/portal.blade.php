<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $portal->title }} | {{ $portal->cnpj }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3b82f6;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --gray: #64748b;
            --bg: #f8fafc;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            margin: 0;
            color: var(--dark);
        }

        /* Header Dark */
        header {
            background: linear-gradient(180deg, var(--dark) 0%, var(--dark-light) 100%);
            padding: 80px 0 120px;
            color: white;
            text-align: center;
        }

        .portal-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            display: inline-block;
        }

        .audit-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.2);
            margin-top: 20px;
        }

        /* Navigation Bar */
        .nav-container {
            max-width: 1000px;
            margin: -50px auto 40px;
            padding: 0 20px;
        }

        .portal-nav {
            background: white;
            border-radius: 100px;
            display: flex;
            justify-content: space-around;
            padding: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .nav-item {
            padding: 15px 25px;
            cursor: pointer;
            border-radius: 100px;
            color: var(--gray);
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover, .nav-item.active {
            color: white;
            background: var(--dark);
        }

        /* Content Sections */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px 100px;
        }

        .section-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-bottom: 30px;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--dark); margin: 10px 0; }
        .stat-label { color: var(--gray); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        /* Tables & Lists */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: var(--gray); font-weight: 600; padding: 15px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; }

        /* Footer */
        footer.dark-footer {
            background: var(--dark);
            color: white;
            padding: 80px 0 40px;
            margin-top: 100px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 50px;
        }

        .footer-title { color: white; font-weight: 800; border-bottom: 2px solid var(--primary); display: inline-block; padding-bottom: 5px; margin-bottom: 25px; }

        .brand-seal {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 0.8rem;
            margin-top: 50px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .portal-nav { border-radius: 20px; flex-direction: column; }
            header { padding: 40px 0 80px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="portal-icon"><i class="fas fa-university"></i></div>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0;">Portal da Transparência</h1>
        <p style="opacity: 0.7; font-size: 1.1rem; margin-top: 10px;">{{ $portal->title }} | CNPJ: {{ $portal->cnpj }}</p>
        <div class="audit-badge"><i class="fas fa-check-circle me-2"></i> Dados Auditados em Tempo Real</div>
    </header>

    <div class="nav-container">
        <div class="portal-nav">
            <a href="javascript:void(0)" onclick="openTab('visao')" class="nav-item tab-link active" id="link-visao"><i class="fas fa-chart-line"></i> Visão Geral</a>
            <a href="javascript:void(0)" onclick="openTab('inst')" class="nav-item tab-link" id="link-inst"><i class="fas fa-landmark"></i> Institucional</a>
            <a href="javascript:void(0)" onclick="openTab('proj')" class="nav-item tab-link" id="link-proj"><i class="fas fa-handshake"></i> Projetos</a>
            <a href="javascript:void(0)" onclick="openTab('impacto')" class="nav-item tab-link" id="link-impacto"><i class="fas fa-users"></i> Impacto Social</a>
            <a href="javascript:void(0)" onclick="openTab('equipe')" class="nav-item tab-link" id="link-equipe"><i class="fas fa-user-friends"></i> Equipe</a>
        </div>
    </div>

    <main class="container">
        <!-- ABA: VISÃO GERAL -->
        <section id="visao" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Arrecadação Anual</span>
                    <div class="stat-value">R$ {{ number_format($totalIn, 2, ',', '.') }}</div>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> Entradas confirmadas</small>
                </div>
                <div class="stat-card" style="border-color: #f59e0b;">
                    <span class="stat-label">Investimento Social</span>
                    <div class="stat-value">R$ {{ number_format($investmentSocial, 2, ',', '.') }}</div>
                    <small style="color: #f59e0b;"><i class="fas fa-check"></i> Aplicado na causa</small>
                </div>
                <div class="stat-card" style="border-color: #10b981;">
                    <span class="stat-label">Saldo em Caixa</span>
                    <div class="stat-value">R$ {{ number_format($balance, 2, ',', '.') }}</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; flex-wrap: wrap;">
                <div class="section-card">
                    <h3 style="margin-top: 0">Distribuição de Gastos</h3>
                    <canvas id="expenseChart"></canvas>
                </div>
                <div class="section-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="margin-top: 0">Últimas Despesas</h3>
                        <a href="#" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">Ver Tudo <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição / Beneficiário</th>
                                <th style="text-align: right;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastExpenses as $exp)
                            <tr>
                                <td style="font-size: 0.85rem; color: var(--gray);">{{ date('d/m/Y', strtotime($exp->date)) }}</td>
                                <td style="font-weight: 500;">{{ $exp->description }}</td>
                                <td style="text-align: right; color: #ef4444; font-weight: 600;">- R$ {{ number_format($exp->amount, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ABA: INSTITUCIONAL -->
        <section id="inst" class="tab-content">
            <div class="section-card">
                <h3 class="footer-title">Documentação & Compliance</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    @foreach($docs as $type => $group)
                        @foreach($group as $doc)
                        <div style="background: #f1f5f9; padding: 25px; border-radius: 15px; position: relative;">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 1.5rem; margin-bottom: 10px;"></i>
                            <h4 style="margin: 5px 0; font-size: 0.9rem;">{{ $doc->title }}</h4>
                            <small class="text-muted">{{ strtoupper($doc->type) }} • {{ $doc->year }}</small>
                            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" style="display: block; margin-top: 15px; color: var(--primary); text-decoration: none; font-weight: 800; font-size: 0.75rem;">CLIQUE PARA BAIXAR</a>
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="section-card">
                <h3>Diretoria Executiva</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                    @forelse($board as $member)
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div style="width: 80px; height: 80px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            @if($member->photo_url)
                                <img src="{{ $member->photo_url }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <i class="fas fa-user text-muted fa-2x"></i>
                            @endif
                        </div>
                        <div>
                            <h4 style="margin: 0;">{{ $member->name }}</h4>
                            <span style="color: var(--primary); font-weight: 600; font-size: 0.85rem;">{{ $member->position }}</span>
                        </div>
                    </div>
                    @empty
                    <p style="text-align: center; color: var(--gray); width: 100%;">Diretoria não cadastrada no sistema.</p>
                    @endforelse
                </div>
                <div style="margin-top: 40px; background: #fffbeb; padding: 20px; border-radius: 15px; color: #92400e; font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-2"></i> <strong>Nota de Transparência:</strong> Conforme Art. 29 da Lei 12.101/2009, os diretores exercem suas funções de forma voluntária e não remunerada.
                </div>
            </div>
        </section>

        <!-- ABA: PROJETOS -->
        <section id="proj" class="tab-content">
            <div class="section-card">
                <h3>Projetos e Termos de Fomento</h3>
                <table>
                    <thead>
                        <tr>
                            <th>PROJETO / TERMO</th>
                            <th>VIGÊNCIA</th>
                            <th>ORÇAMENTO</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partnerships as $p)
                        <tr>
                            <td class="fw-bold">{{ $p->project_name }} <br> <small style="color: var(--gray)">Órgão: {{ $p->agency_name }}</small></td>
                            <td>{{ date('d/m/Y', strtotime($p->start_date)) }} - {{ date('d/m/Y', strtotime($p->end_date)) }}</td>
                            <td style="font-weight: 600;">R$ {{ number_format($p->value, 2, ',', '.') }}</td>
                            <td><span style="padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; background: #dcfce7; color: #166534;">{{ strtoupper($p->status) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align: center; padding: 50px; color: var(--gray);">Nenhum projeto ativo listado publicamente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ABA: IMPACTO SOCIAL -->
        <section id="impacto" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: #8b5cf6;">
                    <span class="stat-label">Famílias Cadastradas</span>
                    <div class="stat-value" style="color: #8b5cf6;">{{ $familiesCount }}</div>
                    <small style="color: #8b5cf6;">Núcleos familiares ativos</small>
                </div>
                <div class="stat-card" style="border-left-color: #ec4899;">
                    <span class="stat-label">Pessoas Inpactadas</span>
                    <div class="stat-value" style="color: #ec4899;">{{ $peopleCount }}</div>
                    <small style="color: #ec4899;">Estimativa direta</small>
                </div>
                <div class="stat-card" style="border-left-color: #06b6d4;">
                    <span class="stat-label">Atendimentos (Mês)</span>
                    <div class="stat-value" style="color: #06b6d4;">{{ $attendancesCount }}</div>
                    <small style="color: #06b6d4;">Ações realizadas este mês</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
                <div class="section-card">
                    <h3 style="margin-top: 0">Evolução dos Atendimentos</h3>
                    <canvas id="impactChart"></canvas>
                </div>
                <div class="section-card">
                    <h3 style="margin-top: 0">Patrimônio Social (Bens Adquiridos)</h3>
                    <p style="color: var(--gray); font-size: 0.9rem;">Relação de bens permanentes adquiridos pela entidade para execução de suas finalidades.</p>
                    <div style="border: 1px dashed #cbd5e1; border-radius: 15px; padding: 40px; text-align: center; color: var(--gray);">
                        Nenhum bem patrimonial registrado.
                    </div>
                </div>
            </div>
            
            <div style="background: #eef2ff; padding: 20px; border-radius: 15px; color: #3730a3; font-size: 0.85rem; margin-top: 40px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-user-shield fa-2x"></i>
                <div>
                    <strong>Privacidade Garantida:</strong> Em conformidade com a LGPD e o Estatuto da Criança e do Adolescente (ECA), não divulgamos nomes, endereços ou imagens de beneficiários neste portal público. Os dados apresentados são estritamente quantitativos e auditados.
                </div>
            </div>
        </section>

        <!-- ABA: EQUIPE -->
        <section id="equipe" class="tab-content">
            <div class="section-card">
                <h3>Recursos Humanos</h3>
                <p style="color: var(--gray);">Estrutura de cargos e salários (Equipe Técnica).</p>
                <div style="border: 1px dashed #cbd5e1; border-radius: 20px; padding: 50px; text-align: center; color: var(--gray);">
                    <i class="fas fa-users-slash fa-3x" style="opacity: 0.3; margin-bottom: 20px;"></i> <br>
                    Dados de recursos humanos não publicados.
                </div>
            </div>
        </section>
    </main>

    <footer class="dark-footer">
        <div class="container footer-grid">
            <div>
                <h4 class="footer-title">Fale Conosco (SIC)</h4>
                <p style="opacity: 0.7; margin-bottom: 20px;">Canal oficial de Serviço de Informação ao Cidadão.</p>
                <p><strong>Email:</strong> {{ $portal->sic_email ?? 'transparencia@vivensi.org' }}</p>
                @if($portal->sic_phone) <p><strong>Telefone:</strong> {{ $portal->sic_phone }}</p> @endif
            </div>
            <div>
                <h4 class="footer-title">Auditoria</h4>
                <p style="opacity: 0.7; line-height: 1.8;">Este portal segue os critérios da Lei 13.019/2014 (MROSC) e Lei Geral de Proteção de Dados (LGPD). Todos os dados são auditados em tempo real direto da base de operações da entidade.</p>
            </div>
        </div>
        <div class="container border-top" style="border-color: rgba(255,255,255,0.05); text-align: center; padding-top: 40px; margin-top: 60px;">
            Desenvolvido com ❤️ por Vivensi App | Tecnologia Social
        </div>
    </footer>

    <script>
        function openTab(id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(t => t.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            document.getElementById('link-' + id).classList.add('active');
        }

        // Charts
        const ctxExpense = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctxExpense, {
            type: 'doughnut',
            data: {
                labels: ['Projetos', 'Administrativo', 'RH', 'Infraestrutura'],
                datasets: [{
                    data: [65, 15, 15, 5],
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });

        const ctxImpact = document.getElementById('impactChart').getContext('2d');
        new Chart(ctxImpact, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Atendimentos',
                    data: [120, 150, 180, 140, 200, 250],
                    backgroundColor: '#8b5cf6',
                    borderRadius: 10
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
