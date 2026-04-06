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
        <div class="audit-badge"><i class="fas fa-check-circle me-2"></i> Dados agregados e auditáveis</div>
        <div style="opacity: 0.85; font-size: 0.95rem; margin-top: 10px;">
            Período: <strong>{{ $year ?? now()->year }}</strong>
            @if(!empty($publicDataUpdatedAt))
                • Atualizado em <strong>{{ \Carbon\Carbon::parse($publicDataUpdatedAt)->format('d/m/Y H:i') }}</strong>
            @endif
        </div>
    </header>

    <div class="nav-container">
        <div class="portal-nav">
            <a href="javascript:void(0)" onclick="openTab('visao')" class="nav-item tab-link active" id="link-visao"><i class="fas fa-chart-line"></i> Visão Geral</a>
            <a href="javascript:void(0)" onclick="openTab('inst')" class="nav-item tab-link" id="link-inst"><i class="fas fa-landmark"></i> Institucional</a>
            <a href="javascript:void(0)" onclick="openTab('proj')" class="nav-item tab-link" id="link-proj"><i class="fas fa-handshake"></i> Projetos</a>
            <a href="javascript:void(0)" onclick="openTab('impacto')" class="nav-item tab-link" id="link-impacto"><i class="fas fa-users"></i> Impacto Social</a>
            <a href="javascript:void(0)" onclick="openTab('equipe')" class="nav-item tab-link" id="link-equipe"><i class="fas fa-user-friends"></i> Equipe</a>
            <a href="javascript:void(0)" onclick="openTab('dados')" class="nav-item tab-link" id="link-dados"><i class="fas fa-database"></i> Dados abertos</a>
        </div>
    </div>

    <main class="container">
        <!-- ABA: VISÃO GERAL -->
        <section id="visao" class="tab-content active">
            <div style="display:flex; justify-content:flex-end; margin-bottom: 15px;">
                <form method="get" style="display:flex; gap:10px; align-items:center;">
                    <label for="year" style="font-weight: 800; font-size: 0.85rem; color: var(--gray); letter-spacing: 1px; text-transform: uppercase;">Ano</label>
                    <select id="year" name="year" onchange="this.form.submit()" style="padding: 10px 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: white; font-weight: 800;">
                        @for($y = (int) now()->year; $y >= (int) now()->year - 5; $y--)
                            <option value="{{ $y }}" @if((int) ($year ?? now()->year) === $y) selected @endif>{{ $y }}</option>
                        @endfor
                    </select>
                    <noscript><button type="submit" style="padding: 10px 14px; border-radius: 12px; border: 0; background: var(--primary); color: white; font-weight: 800;">Filtrar</button></noscript>
                </form>
                <a href="{{ route('transparency.opendata', ['slug' => $portal->slug, 'year' => ($year ?? now()->year)]) }}"
                   style="margin-left: 10px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e2e8f0; background: white; font-weight: 800; text-decoration:none; color: var(--dark); display:inline-flex; align-items:center; gap:10px;"
                   title="Exportar dados agregados (sem dados pessoais)">
                    <i class="fas fa-file-csv"></i> Dados abertos (CSV)
                </a>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Arrecadação ({{ $year ?? now()->year }})</span>
                    <div class="stat-value">R$ {{ number_format($totalIn, 2, ',', '.') }}</div>
                    <small class="text-success"><i class="fas fa-arrow-up"></i> Entradas confirmadas</small>
                </div>
                <div class="stat-card" style="border-color: #f59e0b;">
                    <span class="stat-label">Investimento Social</span>
                    <div class="stat-value">R$ {{ number_format($investmentSocial, 2, ',', '.') }}</div>
                    <small style="color: #f59e0b;"><i class="fas fa-check"></i> {{ $investmentNote ?? 'Aplicado na causa' }}</small>
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
                        <a href="javascript:void(0)" onclick="try{document.getElementById('last-expenses').scrollIntoView({behavior:'smooth'})}catch(e){}" style="color: var(--primary); text-decoration: none; font-size: 0.9rem;">Ver Tudo <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <table id="last-expenses">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Categoria</th>
                                <th style="text-align: right;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastExpenses as $exp)
                            <tr>
                                <td style="font-size: 0.85rem; color: var(--gray);">{{ date('d/m/Y', strtotime($exp->date)) }}</td>
                                <td style="font-weight: 500;">{{ optional($exp->category)->name ?? 'Sem categoria' }}</td>
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
                            <a href="{{ route('transparency.doc', ['slug' => $portal->slug, 'id' => $doc->id]) }}" target="_blank" rel="noopener" style="display: block; margin-top: 15px; color: var(--primary); text-decoration: none; font-weight: 800; font-size: 0.75rem;">BAIXAR DOCUMENTO</a>
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            @php
                $pp = $portal->settings['privacy_policy'] ?? null;
                $dpoName = $portal->settings['dpo_name'] ?? null;
                $dpoEmail = $portal->settings['dpo_email'] ?? null;
                $dpoPhone = $portal->settings['dpo_phone'] ?? null;
                $legalBasis = $portal->settings['legal_basis_note'] ?? null;
                $retention = $portal->settings['data_retention_note'] ?? null;
                $hasLgpd = !empty($pp) || !empty($dpoName) || !empty($dpoEmail) || !empty($dpoPhone) || !empty($legalBasis) || !empty($retention);
            @endphp

            @if($hasLgpd)
                <div class="section-card">
                    <h3 class="footer-title">LGPD & Privacidade</h3>
                    @if(!empty($legalBasis))
                        <p style="color: var(--gray); line-height: 1.8; margin-top: 0;">{!! nl2br(e($legalBasis)) !!}</p>
                    @else
                        <p style="color: var(--gray); line-height: 1.8; margin-top: 0;">
                            Este portal publica informações em formato <strong>agregado</strong>. Para proteger dados pessoais (LGPD), não exibimos nomes, endereços ou descrições individuais.
                        </p>
                    @endif

                    @if(!empty($pp))
                        <div style="background:#f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; color: var(--gray); line-height: 1.8;">
                            <strong style="color: var(--dark);">Política de privacidade do portal</strong><br>
                            {!! nl2br(e($pp)) !!}
                        </div>
                    @endif

                    @if(!empty($retention))
                        <p style="color: var(--gray); line-height: 1.8; margin: 14px 0 0 0;">
                            <strong style="color: var(--dark);">Retenção / logs</strong>: {!! nl2br(e($retention)) !!}
                        </p>
                    @endif

                    @if(!empty($dpoName) || !empty($dpoEmail) || !empty($dpoPhone))
                        <div style="margin-top: 18px; display:flex; gap: 14px; flex-wrap: wrap; align-items:flex-start;">
                            <div style="background:#eef2ff; border-radius: 16px; padding: 16px 18px; color:#3730a3; min-width: 260px;">
                                <div style="font-weight: 900; margin-bottom: 6px;"><i class="fas fa-user-shield"></i> Encarregado (DPO)</div>
                                @if(!empty($dpoName)) <div><strong>Nome:</strong> {{ $dpoName }}</div> @endif
                                @if(!empty($dpoEmail)) <div><strong>Email:</strong> {{ $dpoEmail }}</div> @endif
                                @if(!empty($dpoPhone)) <div><strong>Telefone:</strong> {{ $dpoPhone }}</div> @endif
                            </div>
                            <div style="color: var(--gray); line-height: 1.8; flex: 1; min-width: 260px;">
                                Solicitações de titulares (LGPD) e dúvidas sobre privacidade devem ser direcionadas ao Encarregado.
                                Solicitações de acesso à informação (LAI) seguem o canal SIC.
                            </div>
                        </div>
                    @endif
                </div>
            @endif

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
                            <td class="fw-bold">
                                {{ $p->project_name }}
                                <br>
                                <small style="color: var(--gray)">Órgão: {{ $p->agency_name }}</small>
                                @if(!empty($p->gazette_link))
                                    <br>
                                    <a href="{{ $p->gazette_link }}" target="_blank" rel="noopener"
                                       style="color: var(--primary); text-decoration:none; font-weight: 800; font-size: 0.8rem;">
                                        <i class="fas fa-link"></i> Diário Oficial / Publicação
                                    </a>
                                @endif
                            </td>
                            <td>
                                {{ $p->start_date ? date('d/m/Y', strtotime($p->start_date)) : '—' }}
                                -
                                {{ $p->end_date ? date('d/m/Y', strtotime($p->end_date)) : '—' }}
                            </td>
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
                    <span class="stat-label">Pessoas Impactadas</span>
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
                    @if(($assets ?? collect())->count() > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Bem</th>
                                    <th>Aquisição</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($assets ?? collect())->take(10) as $a)
                                    <tr>
                                        <td style="font-size: 0.85rem; color: var(--gray);">{{ $a->code ?? '-' }}</td>
                                        <td style="font-weight: 600;">{{ $a->name }}</td>
                                        <td style="font-size: 0.9rem; color: var(--gray);">
                                            {{ $a->acquisition_date ? \Carbon\Carbon::parse($a->acquisition_date)->format('d/m/Y') : '—' }}
                                        </td>
                                        <td style="font-size: 0.9rem; color: var(--gray);">{{ strtoupper($a->status ?? '—') }}</td>
                                        <td style="text-align:right; font-weight: 700;">R$ {{ number_format((float) ($a->value ?? 0), 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if(($assets ?? collect())->count() > 10)
                            <div style="margin-top: 12px; font-size: 0.85rem; color: var(--gray);">
                                Exibindo 10 de {{ ($assets ?? collect())->count() }} bens.
                            </div>
                        @endif
                    @else
                        <div style="border: 1px dashed #cbd5e1; border-radius: 15px; padding: 40px; text-align: center; color: var(--gray);">
                            Nenhum bem patrimonial registrado.
                        </div>
                    @endif
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
                @if($employeesCount > 0)
                    <div class="stats-grid" style="margin-top: 20px;">
                        <div class="stat-card" style="border-left-color:#06b6d4;">
                            <span class="stat-label">Colaboradores (ativos)</span>
                            <div class="stat-value" style="color:#06b6d4;">{{ $employeesCount }}</div>
                            <small style="color: var(--gray);">Sem identificação nominal (LGPD)</small>
                        </div>
                        <div class="stat-card" style="border-left-color:#f59e0b;">
                            <span class="stat-label">Folha (salários base)</span>
                            <div class="stat-value" style="color:#f59e0b;">R$ {{ number_format((float) ($payrollTotal ?? 0), 2, ',', '.') }}</div>
                            <small style="color: var(--gray);">Valores agregados</small>
                        </div>
                        <div class="stat-card" style="border-left-color:#10b981;">
                            <span class="stat-label">Bônus (total)</span>
                            <div class="stat-value" style="color:#10b981;">R$ {{ number_format((float) ($bonusTotal ?? 0), 2, ',', '.') }}</div>
                            <small style="color: var(--gray);">Valores agregados</small>
                        </div>
                    </div>
                @else
                    <div style="border: 1px dashed #cbd5e1; border-radius: 20px; padding: 50px; text-align: center; color: var(--gray);">
                        <i class="fas fa-users-slash fa-3x" style="opacity: 0.3; margin-bottom: 20px;"></i> <br>
                        Sem dados de RH disponíveis.
                    </div>
                @endif
            </div>
        </section>

        <!-- ABA: DADOS ABERTOS -->
        <section id="dados" class="tab-content">
            <div class="section-card">
                <h3 style="margin-top: 0">Dados abertos (formato agregado)</h3>
                <p style="color: var(--gray); line-height: 1.8;">
                    Este conjunto atende boas práticas de transparência/LAI com <strong>dados agregados</strong>, sem publicação de informações pessoais (LGPD).
                    Use o arquivo CSV para auditoria, relatórios e reutilização.
                </p>

                <div style="display:flex; gap: 12px; flex-wrap: wrap; margin: 16px 0 24px 0;">
                    <a href="{{ route('transparency.opendata', ['slug' => $portal->slug, 'year' => ($year ?? now()->year)]) }}"
                       style="padding: 12px 16px; border-radius: 12px; border: 0; background: var(--dark); color: white; font-weight: 800; text-decoration:none; display:inline-flex; align-items:center; gap:10px;">
                        <i class="fas fa-download"></i> Baixar CSV ({{ $year ?? now()->year }})
                    </a>
                    <a href="{{ route('transparency.report_pdf', ['slug' => $portal->slug, 'year' => ($year ?? now()->year)]) }}"
                       style="padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: white; color: var(--dark); font-weight: 800; text-decoration:none; display:inline-flex; align-items:center; gap:10px;">
                        <i class="fas fa-file-pdf"></i> Relatório anual (PDF)
                    </a>
                    <div style="padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: var(--gray); font-weight: 700;">
                        Cobertura: <strong style="color: var(--dark);">{{ $year ?? now()->year }}</strong> • Delimitador: <strong style="color: var(--dark);">;</strong> • Codificação: <strong style="color: var(--dark);">UTF-8</strong>
                    </div>
                </div>

                <div style="display:flex; gap: 12px; flex-wrap: wrap; margin: -8px 0 24px 0;">
                    <span style="color: var(--gray); font-weight: 700;">
                        Dica: use o CSV/PDF para auditoria e relatórios (dados agregados).
                    </span>
                </div>

                <h4 style="margin: 0 0 12px 0;">Prévia 1: Totais mensais (pagos)</h4>
                <div style="overflow:auto; border: 1px solid #eef2f7; border-radius: 16px; margin-bottom: 26px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th style="text-align:right;">Entradas</th>
                                <th style="text-align:right;">Saídas</th>
                                <th style="text-align:right;">Saldo do mês</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($openDataMonthly ?? collect()) as $row)
                                <tr>
                                    <td style="color: var(--gray); font-weight: 700;">{{ strtoupper($row['label'] ?? '') }}</td>
                                    <td style="text-align:right; font-weight: 700;">R$ {{ number_format((float) ($row['income'] ?? 0), 2, ',', '.') }}</td>
                                    <td style="text-align:right; font-weight: 700; color: #ef4444;">R$ {{ number_format((float) ($row['expense'] ?? 0), 2, ',', '.') }}</td>
                                    <td style="text-align:right; font-weight: 800;">R$ {{ number_format((float) ($row['balance'] ?? 0), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 style="margin: 0 0 12px 0;">Prévia 2: Despesas por categoria (pagas)</h4>
                <div style="overflow:auto; border: 1px solid #eef2f7; border-radius: 16px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th style="text-align:right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($openDataExpenseByCategory ?? collect()) as $row)
                                <tr>
                                    <td style="font-weight: 700;">{{ $row['category'] ?? 'Sem categoria' }}</td>
                                    <td style="text-align:right; font-weight: 800;">R$ {{ number_format((float) ($row['total'] ?? 0), 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="color: var(--gray); text-align:center; padding: 22px;">
                                        Sem despesas pagas no período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 26px; background: #f1f5f9; border-radius: 16px; padding: 18px 18px; color: var(--gray); line-height: 1.8;">
                    <strong style="color: var(--dark);">Dicionário de dados (CSV)</strong><br>
                    - <strong style="color: var(--dark);">dataset</strong>: `monthly_totals` ou `expense_by_category`<br>
                    - <strong style="color: var(--dark);">type</strong>: `income` / `expense` (quando aplicável)<br>
                    - <strong style="color: var(--dark);">month</strong>: 1-12 (somente em `monthly_totals`)<br>
                    - <strong style="color: var(--dark);">category</strong>: nome da categoria (somente em `expense_by_category`)<br>
                    - <strong style="color: var(--dark);">total</strong>: soma dos valores pagos no período (sem itens/descrições individuais)
                </div>

                <h4 style="margin: 26px 0 12px 0;">Auditoria pública (downloads)</h4>
                <p style="color: var(--gray); line-height: 1.8; margin: 0 0 12px 0;">
                    Contadores agregados para rastreabilidade (sem exibir IP, identificação ou logs individuais).
                </p>
                <div style="overflow:auto; border: 1px solid #eef2f7; border-radius: 16px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th style="text-align:right;">Downloads de documentos</th>
                                <th style="text-align:right;">Downloads de dados abertos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($publicAuditDownloads ?? []) as $r)
                                <tr>
                                    <td style="color: var(--gray); font-weight: 800;">{{ strtoupper($r['label'] ?? '') }}</td>
                                    <td style="text-align:right; font-weight: 800;">{{ (int) ($r['docs'] ?? 0) }}</td>
                                    <td style="text-align:right; font-weight: 800;">{{ (int) ($r['opendata'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                <p style="opacity: 0.7; line-height: 1.8; margin-top: 18px;">
                    Solicitações de acesso à informação (LAI) devem ser feitas por este canal, informando o máximo de detalhes possível (tema, período e formato desejado).
                </p>
            </div>
            <div>
                <h4 class="footer-title">Privacidade e LAI</h4>
                <p style="opacity: 0.7; line-height: 1.8;">
                    Este portal publica informações de interesse público em formato <strong>agregado</strong>, alinhado ao MROSC e às boas práticas de transparência.
                    Por LGPD, não divulgamos dados pessoais de beneficiários, nem dados que permitam identificação direta/indireta.
                    Quando necessário, fornecemos informações adicionais via SIC, com análise de sigilo e anonimização.
                </p>
                @if(!empty($portal->settings['dpo_email'] ?? null))
                    <p style="opacity: 0.7; line-height: 1.8; margin-top: 12px;">
                        <strong>Encarregado (DPO):</strong> {{ $portal->settings['dpo_email'] }}
                    </p>
                @endif
                <p style="opacity: 0.7; line-height: 1.8; margin-top: 16px;">
                    <strong>Dados abertos:</strong>
                    <a href="{{ route('transparency.opendata', ['slug' => $portal->slug, 'year' => ($year ?? now()->year)]) }}"
                       style="color: #93c5fd; text-decoration: none; font-weight: 800;"
                       rel="noopener">
                        baixar CSV agregado do período
                    </a>.
                </p>
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
        const expenseLabels = @json($expenseChart['labels'] ?? []);
        const expenseData = @json($expenseChart['data'] ?? []);
        const expenseColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b'];
        new Chart(ctxExpense, {
            type: 'doughnut',
            data: {
                labels: (expenseLabels && expenseLabels.length ? expenseLabels : ['Sem dados']),
                datasets: [{
                    data: (expenseData && expenseData.length ? expenseData : [1]),
                    backgroundColor: (expenseLabels && expenseLabels.length ? expenseLabels.map((_, i) => expenseColors[i % expenseColors.length]) : ['#cbd5e1']),
                    borderWidth: 0
                }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });

        const ctxImpact = document.getElementById('impactChart').getContext('2d');
        const impactLabels = @json($impactChart['labels'] ?? []);
        const impactData = @json($impactChart['data'] ?? []);
        new Chart(ctxImpact, {
            type: 'bar',
            data: {
                labels: (impactLabels && impactLabels.length ? impactLabels : ['Sem dados']),
                datasets: [{
                    label: 'Atendimentos',
                    data: (impactData && impactData.length ? impactData : [0]),
                    backgroundColor: '#8b5cf6',
                    borderRadius: 10
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    </script>
</body>
</html>
