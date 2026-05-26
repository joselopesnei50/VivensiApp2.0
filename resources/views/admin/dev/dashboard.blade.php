<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Portal — Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root{
            --bg:#0d1117;--surface:#161b22;--border:#30363d;--text:#e6edf3;--muted:#8b949e;
            --accent:#7c3aed;--accent2:#4f46e5;--green:#3fb950;--red:#f85149;--yellow:#e3b341;
            --blue:#58a6ff;--mono:'JetBrains Mono',monospace;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--bg);color:var(--text);font-family:'Outfit',sans-serif;min-height:100vh}
        /* ── Top bar ── */
        .topbar{background:var(--surface);border-bottom:1px solid var(--border);
                padding:.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;
                position:sticky;top:0;z-index:100}
        .topbar-brand{display:flex;align-items:center;gap:.75rem;font-weight:700;font-size:1.1rem}
        .topbar-brand .badge{background:linear-gradient(135deg,var(--accent),var(--accent2));
                             border-radius:8px;padding:.25rem .6rem;font-size:.75rem;font-weight:600}
        .topbar-actions{display:flex;align-items:center;gap:1rem}
        .topbar-actions .session-info{color:var(--muted);font-size:.82rem}
        .btn-logout{background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.3);color:var(--red);
                    border-radius:8px;padding:.4rem .9rem;font-size:.83rem;cursor:pointer;
                    text-decoration:none;transition:background .2s}
        .btn-logout:hover{background:rgba(248,81,73,.22)}
        /* ── Layout ── */
        .shell{display:flex;min-height:calc(100vh - 53px)}
        .sidebar{width:220px;background:var(--surface);border-right:1px solid var(--border);
                 padding:1.2rem .75rem;flex-shrink:0;position:sticky;top:53px;
                 height:calc(100vh - 53px);overflow-y:auto}
        .sidebar a{display:flex;align-items:center;gap:.6rem;padding:.55rem .75rem;border-radius:8px;
                   color:var(--muted);text-decoration:none;font-size:.88rem;transition:all .2s}
        .sidebar a:hover,.sidebar a.active{background:rgba(124,58,237,.15);color:var(--text)}
        .sidebar a i{width:16px;text-align:center}
        .sidebar-section{color:var(--muted);font-size:.72rem;font-weight:600;letter-spacing:.08em;
                         text-transform:uppercase;padding:.9rem .75rem .3rem;margin-top:.5rem}
        .main{flex:1;padding:1.5rem 2rem;overflow:auto}
        /* ── Tabs (sections) ── */
        .tab-nav{display:flex;gap:.5rem;border-bottom:1px solid var(--border);margin-bottom:1.5rem;
                 overflow-x:auto;padding-bottom:1px}
        .tab-nav button{background:none;border:none;color:var(--muted);padding:.6rem 1rem;
                        font-size:.9rem;cursor:pointer;border-bottom:2px solid transparent;
                        white-space:nowrap;transition:all .2s;font-family:'Outfit',sans-serif}
        .tab-nav button.active{color:var(--text);border-bottom-color:var(--accent)}
        .tab-panel{display:none}.tab-panel.active{display:block}
        /* ── Search ── */
        .search-bar{position:relative;margin-bottom:1.2rem}
        .search-bar input{width:100%;background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:.6rem 1rem .6rem 2.4rem;color:var(--text);
                          font-size:.9rem;outline:none;transition:border-color .2s}
        .search-bar input:focus{border-color:var(--accent)}
        .search-bar i{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--muted)}
        /* ── Table/Cards ── */
        .card{background:var(--surface);border:1px solid var(--border);border-radius:10px;
              margin-bottom:1.2rem;overflow:hidden}
        .card-header{padding:.75rem 1rem;border-bottom:1px solid var(--border);
                     display:flex;align-items:center;justify-content:space-between;cursor:pointer;
                     user-select:none}
        .card-header h3{font-size:.95rem;font-weight:600;display:flex;align-items:center;gap:.6rem}
        .card-header .count{background:rgba(124,58,237,.2);color:var(--accent);border-radius:20px;
                            padding:.15rem .55rem;font-size:.75rem;font-weight:600}
        .card-body{padding:0}
        table{width:100%;border-collapse:collapse;font-size:.83rem}
        th{padding:.55rem 1rem;background:rgba(255,255,255,.02);color:var(--muted);
           font-weight:600;text-align:left;border-bottom:1px solid var(--border);
           font-size:.78rem;letter-spacing:.03em;white-space:nowrap}
        td{padding:.55rem 1rem;border-bottom:1px solid rgba(48,54,61,.5);vertical-align:middle}
        tr:last-child td{border-bottom:none}
        tr:hover td{background:rgba(255,255,255,.02)}
        /* ── Badges ── */
        .badge{display:inline-block;border-radius:4px;padding:.15rem .45rem;font-size:.72rem;
               font-weight:600;font-family:var(--mono)}
        .badge-pk{background:rgba(227,179,65,.2);color:var(--yellow)}
        .badge-uq{background:rgba(56,211,159,.15);color:#3fb950}
        .badge-idx{background:rgba(88,166,255,.15);color:var(--blue)}
        .badge-get{background:rgba(56,211,159,.15);color:#3fb950}
        .badge-post{background:rgba(88,166,255,.15);color:var(--blue)}
        .badge-put,.badge-patch{background:rgba(227,179,65,.15);color:var(--yellow)}
        .badge-delete{background:rgba(248,81,73,.15);color:var(--red)}
        .badge-options{background:rgba(124,58,237,.15);color:#a78bfa}
        .badge-info{background:rgba(88,166,255,.12);color:var(--blue)}
        .badge-ok{background:rgba(56,211,159,.12);color:var(--green)}
        /* ── Code ── */
        code{font-family:var(--mono);font-size:.82rem;background:rgba(255,255,255,.06);
             padding:.1rem .35rem;border-radius:4px;color:#a5d6ff}
        /* ── Arch grid ── */
        .arch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem}
        .arch-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1.2rem}
        .arch-card h4{font-size:.88rem;font-weight:700;color:var(--muted);
                      text-transform:uppercase;letter-spacing:.06em;margin-bottom:.9rem}
        .kv{display:flex;gap:.5rem;margin-bottom:.5rem;font-size:.85rem}
        .kv .k{color:var(--muted);min-width:130px;flex-shrink:0}
        .kv .v{color:var(--text)}
        /* ── Collapse ── */
        .card-body.collapsed{display:none}
        .chevron{transition:transform .2s;color:var(--muted)}
        .card-header.open .chevron{transform:rotate(180deg)}
        /* ── Stats row ── */
        .stats-row{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
        .stat-box{background:var(--surface);border:1px solid var(--border);border-radius:10px;
                  padding:1rem 1.3rem;flex:1;min-width:160px}
        .stat-box .label{color:var(--muted);font-size:.8rem;margin-bottom:.3rem}
        .stat-box .value{font-size:1.5rem;font-weight:700}
        /* ── Null/empty text ── */
        .null{color:var(--muted);font-style:italic}
    </style>
</head>
<body>

{{-- ── Top bar ── --}}
<div class="topbar">
    <div class="topbar-brand">
        <i class="fas fa-terminal" style="color:var(--accent)"></i>
        Dev Portal
        <span class="badge">SUPER ADMIN</span>
    </div>
    <div class="topbar-actions">
        <span class="session-info">
            <i class="fas fa-clock me-1"></i>Sessão expira em 30 min
        </span>
        <form method="POST" action="{{ route('admin.dev.logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="fas fa-sign-out-alt me-1"></i>Encerrar sessão
            </button>
        </form>
    </div>
</div>

<div class="shell">
    {{-- ── Sidebar ── --}}
    <nav class="sidebar">
        <span class="sidebar-section">Navegação</span>
        <a href="#" onclick="showTab('schema');return false" class="active" id="nav-schema">
            <i class="fas fa-database"></i> Banco de Dados
        </a>
        <a href="#" onclick="showTab('routes');return false" id="nav-routes">
            <i class="fas fa-route"></i> Rotas
        </a>
        <a href="#" onclick="showTab('queues');return false" id="nav-queues">
            <i class="fas fa-layer-group"></i> Filas & Jobs
        </a>
        <a href="#" onclick="showTab('arch');return false" id="nav-arch">
            <i class="fas fa-sitemap"></i> Arquitetura
        </a>

        <a href="#" onclick="showTab('whatsapp');return false" id="nav-whatsapp">
            <i class="fab fa-whatsapp"></i> WhatsApp & Anti-Ban
        </a>

        <span class="sidebar-section" style="margin-top:1.5rem">Links rápidos</span>
        <a href="{{ route('admin.dashboard') }}" target="_blank">
            <i class="fas fa-gauge"></i> Painel Admin
        </a>
        <a href="{{ route('admin.health') }}" target="_blank">
            <i class="fas fa-heartbeat"></i> Server Health
        </a>
        <a href="{{ route('admin.email_logs') }}" target="_blank">
            <i class="fas fa-envelope"></i> Email Logs
        </a>
    </nav>

    {{-- ── Main content ── --}}
    <div class="main">

        {{-- ── SCHEMA TAB ── --}}
        <div class="tab-panel active" id="tab-schema">
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:.3rem">
                <i class="fas fa-database me-2" style="color:var(--accent)"></i>Esquema do Banco de Dados
            </h2>
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.2rem">
                Database: <code>{{ DB::getDatabaseName() }}</code> — gerado dinamicamente em {{ now()->format('d/m/Y H:i:s') }}
            </p>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="label">Total de Tabelas</div>
                    <div class="value" style="color:var(--accent)">{{ count($schema) }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Total de Colunas</div>
                    <div class="value" style="color:var(--blue)">{{ collect($schema)->sum(fn($t) => count($t['columns'])) }}</div>
                </div>
            </div>

            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="schema-search" placeholder="Filtrar tabelas..." oninput="filterSchema(this.value)">
            </div>

            @foreach($schema as $tbl)
            <div class="card schema-table" data-name="{{ $tbl['table'] }}">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3>
                        <i class="fas fa-table" style="color:var(--muted);font-size:.85rem"></i>
                        {{ $tbl['table'] }}
                        <span class="count">{{ count($tbl['columns']) }} cols</span>
                    </h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed">
                    <table>
                        <thead>
                            <tr>
                                <th>Coluna</th><th>Tipo</th><th>Null</th><th>Default</th><th>Extra</th><th>Índices</th><th>Comentário</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tbl['columns'] as $col)
                            <tr>
                                <td><code>{{ $col['name'] }}</code></td>
                                <td><span style="color:var(--blue);font-family:var(--mono);font-size:.8rem">{{ $col['type'] }}</span></td>
                                <td>{{ $col['null'] === 'YES' ? 'YES' : '—' }}</td>
                                <td>
                                    @if($col['default'] !== null)
                                        <code>{{ $col['default'] }}</code>
                                    @else
                                        <span class="null">null</span>
                                    @endif
                                </td>
                                <td>
                                    @if($col['extra'])
                                        <code style="color:var(--yellow)">{{ $col['extra'] }}</code>
                                    @else —
                                    @endif
                                </td>
                                <td>
                                    @foreach($col['flags'] as $flag)
                                        <span class="badge {{ $flag === 'PK' ? 'badge-pk' : ($flag === 'UQ' ? 'badge-uq' : 'badge-idx') }}">{{ $flag }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($col['comment'])
                                        <span style="color:var(--muted);font-size:.8rem">{{ $col['comment'] }}</span>
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── ROUTES TAB ── --}}
        <div class="tab-panel" id="tab-routes">
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:.3rem">
                <i class="fas fa-route me-2" style="color:var(--accent)"></i>Mapa de Rotas
            </h2>
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.2rem">
                {{ count($routes) }} rotas registradas — gerado dinamicamente
            </p>

            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="route-search" placeholder="Filtrar por URI, nome ou controller..."
                       oninput="filterRoutes(this.value)">
            </div>

            <div class="card">
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Método</th><th>URI</th><th>Nome</th><th>Controller</th><th>Middleware</th>
                            </tr>
                        </thead>
                        <tbody id="routes-tbody">
                            @foreach($routes as $r)
                            <tr class="route-row" data-search="{{ strtolower($r['uri'].' '.$r['name'].' '.$r['controller']) }}">
                                <td>
                                    <span class="badge badge-{{ strtolower($r['method']) }}">{{ $r['method'] }}</span>
                                </td>
                                <td><code>{{ $r['uri'] }}</code></td>
                                <td style="color:var(--muted);font-size:.8rem">{{ $r['name'] }}</td>
                                <td style="font-family:var(--mono);font-size:.75rem;color:var(--muted)">
                                    {{ Str::after($r['controller'], 'App\Http\Controllers\\') }}
                                </td>
                                <td style="font-size:.75rem;color:var(--muted)">
                                    {{ Str::limit($r['middleware'], 60) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── QUEUES TAB ── --}}
        <div class="tab-panel" id="tab-queues">
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:.3rem">
                <i class="fas fa-layer-group me-2" style="color:var(--accent)"></i>Filas & Jobs
            </h2>
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.2rem">
                Configuração do sistema de filas e jobs registrados
            </p>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="label">Driver Ativo</div>
                    <div class="value" style="color:var(--accent);font-size:1.2rem">{{ strtoupper($queues['config']['driver']) }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">retry_after (seg)</div>
                    <div class="value" style="color:var(--blue);font-size:1.2rem">{{ $queues['config']['retry_after'] ?? '—' }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Total de Jobs</div>
                    <div class="value" style="color:var(--green)">{{ count($queues['jobs']) }}</div>
                </div>
            </div>

            {{-- Queue config card --}}
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-cog" style="color:var(--muted);font-size:.85rem"></i> Configuração queue.php</h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed">
                    <table>
                        <thead><tr><th>Conexão</th><th>Driver</th><th>retry_after</th><th>Queue padrão</th></tr></thead>
                        <tbody>
                            @foreach(config('queue.connections') as $name => $conn)
                            <tr>
                                <td><code>{{ $name }}</code>
                                    @if($name === config('queue.default'))
                                        <span class="badge badge-ok">ativo</span>
                                    @endif
                                </td>
                                <td><code>{{ $conn['driver'] }}</code></td>
                                <td><code>{{ $conn['retry_after'] ?? '—' }}</code></td>
                                <td><code>{{ $conn['queue'] ?? '—' }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Jobs --}}
            @foreach($queues['jobs'] as $job)
            <div class="card">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3>
                        <i class="fas fa-bolt" style="color:var(--yellow);font-size:.85rem"></i>
                        {{ $job['short'] }}
                        @if(in_array('ShouldBeUnique', $job['interfaces']))
                            <span class="badge badge-info">Unique</span>
                        @endif
                    </h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed" style="padding:1rem">
                    <div style="font-family:var(--mono);font-size:.78rem;color:var(--muted);margin-bottom:.75rem">
                        {{ $job['class'] }}
                    </div>
                    @if($job['interfaces'])
                    <div style="margin-bottom:.75rem">
                        <span style="color:var(--muted);font-size:.8rem">Interfaces: </span>
                        @foreach($job['interfaces'] as $iface)
                            <span class="badge badge-info">{{ $iface }}</span>
                        @endforeach
                    </div>
                    @endif
                    @if($job['props'])
                    <table>
                        <thead><tr><th>Propriedade</th><th>Valor</th></tr></thead>
                        <tbody>
                            @foreach($job['props'] as $k => $v)
                            <tr>
                                <td><code>${{ $k }}</code></td>
                                <td><code>{{ is_array($v) ? json_encode($v) : $v }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── ARCH TAB ── --}}
        <div class="tab-panel" id="tab-arch">
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:.3rem">
                <i class="fas fa-sitemap me-2" style="color:var(--accent)"></i>Arquitetura do Sistema
            </h2>
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.5rem">
                Visão geral técnica da plataforma Vivensi SaaS
            </p>

            <div class="arch-grid">

                {{-- Stack --}}
                <div class="arch-card">
                    <h4><i class="fas fa-layer-group me-1"></i> Stack Tecnológica</h4>
                    @foreach($arch['stack'] as $item)
                    <div class="kv">
                        <span class="k">{{ $item['label'] }}</span>
                        <span class="v">{{ $item['value'] }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Tenancy --}}
                <div class="arch-card">
                    <h4><i class="fas fa-building me-1"></i> Estratégia Multi-Tenant</h4>
                    @foreach($arch['tenancy'] as $k => $v)
                    <div class="kv">
                        <span class="k">{{ $k }}</span>
                        <span class="v">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Middleware --}}
                <div class="arch-card">
                    <h4><i class="fas fa-shield-alt me-1"></i> Grupos de Middleware</h4>
                    @foreach($arch['middleware_groups'] as $k => $v)
                    <div class="kv">
                        <span class="k"><code>{{ $k }}</code></span>
                        <span class="v" style="font-size:.83rem">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Key Models --}}
                <div class="arch-card">
                    <h4><i class="fas fa-table me-1"></i> Models Principais</h4>
                    @foreach($arch['key_models'] as $k => $v)
                    <div class="kv">
                        <span class="k"><code>{{ $k }}</code></span>
                        <span class="v" style="font-size:.82rem;color:var(--muted)">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Key Services --}}
                <div class="arch-card">
                    <h4><i class="fas fa-cogs me-1"></i> Services Principais</h4>
                    @foreach($arch['key_services'] as $k => $v)
                    <div class="kv">
                        <span class="k"><code style="font-size:.73rem">{{ $k }}</code></span>
                        <span class="v" style="font-size:.82rem;color:var(--muted)">{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Scheduled Commands --}}
                <div class="arch-card">
                    <h4><i class="fas fa-clock me-1"></i> Comandos Agendados</h4>
                    @foreach($arch['scheduled_commands'] as $k => $v)
                    <div class="kv">
                        <span class="k"><code style="font-size:.73rem">{{ $k }}</code></span>
                        <span class="v" style="font-size:.82rem;color:var(--muted)">{{ $v }}</span>
                    </div>
                    @endforeach
                    <div class="kv" style="margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--border)">
                        <span class="k">Scheduler</span>
                        <span class="v" style="font-size:.82rem"><code>* * * * * php artisan schedule:run</code></span>
                    </div>
                    <div class="kv">
                        <span class="k">Supervisor</span>
                        <span class="v" style="font-size:.82rem"><code>/etc/supervisor/conf.d/vivensi.conf</code></span>
                    </div>
                </div>

            </div>

            {{-- ENV summary --}}
            <div class="card" style="margin-top:1.5rem">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-gear" style="color:var(--muted);font-size:.85rem"></i> Ambiente Atual</h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed">
                    <table>
                        <thead><tr><th>Variável</th><th>Valor</th></tr></thead>
                        <tbody>
                            @foreach([
                                'APP_ENV'          => app()->environment(),
                                'APP_DEBUG'        => config('app.debug') ? 'true' : 'false',
                                'APP_URL'          => config('app.url'),
                                'DB_CONNECTION'    => config('database.default'),
                                'DB_DATABASE'      => config('database.connections.'.config('database.default').'.database'),
                                'QUEUE_CONNECTION' => config('queue.default'),
                                'CACHE_DRIVER'     => config('cache.default'),
                                'SESSION_DRIVER'   => config('session.driver'),
                                'MAIL_MAILER'      => config('mail.default'),
                                'FILESYSTEM_DISK'  => config('filesystems.default'),
                                'PHP_VERSION'      => PHP_VERSION,
                                'LARAVEL_VERSION'  => app()->version(),
                            ] as $k => $v)
                            <tr>
                                <td><code>{{ $k }}</code></td>
                                <td><code style="color:var(--green)">{{ $v }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── WHATSAPP & ANTI-BAN TAB ── --}}
        <div class="tab-panel" id="tab-whatsapp">
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:.3rem">
                <i class="fab fa-whatsapp me-2" style="color:#25D366"></i>Mensageria WhatsApp & Anti-Ban
            </h2>
            <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.5rem">
                Arquitetura completa de disparo em massa via Evolution API (Baileys) com camada de proteção anti-ban.
            </p>

            {{-- ── KPIs ── --}}
            <div class="stats-row">
                <div class="stat-box">
                    <div class="label">Máx mensagens/hora</div>
                    <div class="value" style="color:var(--yellow)">55</div>
                </div>
                <div class="stat-box">
                    <div class="label">Período warming</div>
                    <div class="value" style="color:var(--accent)">14 dias</div>
                </div>
                <div class="stat-box">
                    <div class="label">Restrição ban padrão</div>
                    <div class="value" style="color:var(--red)">24 h</div>
                </div>
                <div class="stat-box">
                    <div class="label">Pausa a cada 30 msgs</div>
                    <div class="value" style="color:var(--blue)">3–5 min</div>
                </div>
                <div class="stat-box">
                    <div class="label">Delay entre mensagens</div>
                    <div class="value" style="color:var(--green)">≥ 5 s</div>
                </div>
            </div>

            {{-- ── Fluxo de disparo em massa ── --}}
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header" onclick="toggleCard(this)" class="open">
                    <h3><i class="fas fa-paper-plane" style="color:#25D366;font-size:.85rem"></i> Fluxo de Disparo em Massa <span class="count">ProcessBroadcastCampaignJob</span></h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body" style="padding:1.2rem">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem">
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Fila & Controle</div>
                            <div class="kv"><span class="k">Queue</span><span class="v"><code>whatsapp</code></span></div>
                            <div class="kv"><span class="k">Timeout</span><span class="v">7200 s (2h) — campanhas grandes</span></div>
                            <div class="kv"><span class="k">Tries</span><span class="v">1 — sem retry; falha encerra campanha</span></div>
                            <div class="kv"><span class="k">ShouldBeUnique</span><span class="v">por <code>campaign_id</code> — evita disparo duplo</span></div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Tipos de Audiência</div>
                            <div class="kv"><span class="k"><code>all</code></span><span class="v">Cursor iterator — sem carregar tudo em memória</span></div>
                            <div class="kv"><span class="k"><code>groups</code> (group)</span><span class="v">Uma mensagem para o chat do grupo</span></div>
                            <div class="kv"><span class="k"><code>groups</code> (members)</span><span class="v">Expande membros via API — mensagem individual</span></div>
                            <div class="kv"><span class="k"><code>selected</code></span><span class="v">Lista de números manual ou CSV (máx 5 000)</span></div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Validação de Números</div>
                            <div class="kv"><span class="k">Normalização</span><span class="v"><code>normalizeBrazilianPhone()</code> — resolve 9º dígito BR</span></div>
                            <div class="kv"><span class="k">Validação</span><span class="v"><code>checkWhatsappNumbers()</code> — verifica JIDs na API antes do envio</span></div>
                            <div class="kv"><span class="k">Cache JIDs</span><span class="v"><code>wa_jid</code> + <code>whatsapp_validated_at</code> salvos no chat</span></div>
                            <div class="kv"><span class="k">Inválidos</span><span class="v">Pulados com <code>total_failed++</code> — não suspendem campanha</span></div>
                        </div>
                    </div>

                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Circuit Breakers</div>
                        <table>
                            <thead><tr><th>Gatilho</th><th>Ação</th><th>Resultado</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>Sinal de ban detectado (<code>isBanSignal()</code>)</td>
                                    <td><code>markAsRestricted($instance, 24)</code></td>
                                    <td><span class="badge badge-delete">campanha failed</span> instância restrita 24h</td>
                                </tr>
                                <tr>
                                    <td>5 erros consecutivos sem ban</td>
                                    <td>Encerra loop imediatamente</td>
                                    <td><span class="badge badge-delete">campanha failed</span></td>
                                </tr>
                                <tr>
                                    <td>Fora da janela horária</td>
                                    <td>Salva progresso atual</td>
                                    <td><span class="badge badge-put">campanha paused</span> operador reagenda</td>
                                </tr>
                                <tr>
                                    <td>Limite diário/horário atingido</td>
                                    <td>Salva progresso atual</td>
                                    <td><span class="badge badge-put">campanha paused</span></td>
                                </tr>
                                <tr>
                                    <td>URL encurtada na mensagem</td>
                                    <td>Rejeita antes de iniciar</td>
                                    <td><span class="badge badge-delete">campanha failed</span> nunca envia</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── AntiBanManager ── --}}
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-shield-halved" style="color:var(--red);font-size:.85rem"></i> AntiBanManager — Camada de Proteção <span class="count">App\Services\Messaging</span></h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed" style="padding:1.2rem">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;margin-bottom:1rem">

                        {{-- Verificação canSendMessage() --}}
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">canSendMessage() — Ordem de verificação</div>
                            <div style="display:flex;flex-direction:column;gap:6px">
                                @foreach([
                                    ['1', 'Janela horária', 'isWithinSafeWindow()', 'var(--blue)'],
                                    ['2', 'Restrição ban ativa', 'isInstanceRestricted()', 'var(--red)'],
                                    ['3', 'Limite diário', 'hasReachedDailyLimit()', 'var(--yellow)'],
                                    ['4', 'Limite warming', 'getWarmingDailyLimit()', 'var(--accent)'],
                                    ['5', 'Limite horário', 'hasReachedHourlyLimit() via RateLimiter', 'var(--green)'],
                                ] as [$n, $label, $fn, $color])
                                <div style="display:flex;align-items:center;gap:8px;font-size:.83rem">
                                    <span style="width:18px;height:18px;min-width:18px;border-radius:50%;background:{{ $color }};color:#0d1117;font-size:.65rem;font-weight:800;display:flex;align-items:center;justify-content:center;">{{ $n }}</span>
                                    <span style="color:var(--text)">{{ $label }}</span>
                                    <code style="color:var(--muted);font-size:.72rem">{{ $fn }}</code>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Simulação humana --}}
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">simulateHumanTyping()</div>
                            <div class="kv"><span class="k">Passo 1</span><span class="v">Envia presence <code>composing</code> (digitando)</span></div>
                            <div class="kv"><span class="k">Passo 2</span><span class="v">Sleep proporcional: <code>strlen / 12</code> chars/s (~digitação humana)</span></div>
                            <div class="kv"><span class="k">Passo 3</span><span class="v">Envia presence <code>paused</code> (pausou — mais orgânico)</span></div>
                            <div class="kv"><span class="k">Passo 4</span><span class="v">Sleep 1–3s antes do envio real</span></div>
                            <div class="kv"><span class="k">Delay payload</span><span class="v"><code>getRandomDelayMs()</code> → rand(2500, 6000) ms</span></div>
                            <div class="kv"><span class="k">Escopo</span><span class="v">Apenas mensagens individuais — não aplica em grupos</span></div>
                        </div>

                        {{-- Restrição --}}
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Gerenciamento de Restrição</div>
                            <div class="kv"><span class="k">Storage</span><span class="v">Campo <code>settings</code> JSON no model — sem migration</span></div>
                            <div class="kv"><span class="k">Chaves salvas</span><span class="v"><code>restricted_until</code>, <code>restricted_reason</code>, <code>restricted_at</code></span></div>
                            <div class="kv"><span class="k">Auto-remoção</span><span class="v"><code>isInstanceRestricted()</code> remove flag quando expirar</span></div>
                            <div class="kv"><span class="k">Log level</span><span class="v"><code>Log::critical</code> ao marcar, <code>Log::info</code> ao liberar</span></div>
                        </div>
                    </div>

                    {{-- Warming --}}
                    <div style="margin-top:.5rem;padding-top:1rem;border-top:1px solid var(--border)">
                        <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem">
                            Perfil de Warming Progressivo — 14 dias (~30% crescimento/dia)
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px">
                            @foreach([1=>20,2=>30,3=>40,4=>55,5=>70,6=>90,7=>115,8=>140,9=>170,10=>205,11=>245,12=>290,13=>340,14=>370] as $day => $limit)
                            <div style="text-align:center;background:rgba(124,58,237,.1);border:1px solid rgba(124,58,237,.2);border-radius:8px;padding:6px 10px;min-width:52px">
                                <div style="font-size:.65rem;color:var(--muted);font-weight:600">Dia {{ $day }}</div>
                                <div style="font-size:.92rem;font-weight:700;color:var(--accent)">{{ $limit }}</div>
                            </div>
                            @endforeach
                        </div>
                        <div class="kv" style="margin-top:.75rem"><span class="k">Após dia 14</span><span class="v">Modo warming desativado automaticamente — usa <code>daily_limit</code> normal</span></div>
                        <div class="kv"><span class="k">Ativação</span><span class="v"><code>startWarming($instance)</code> — chamar ao criar instância nova</span></div>
                        <div class="kv"><span class="k">Status</span><span class="v"><code>getInstanceStatus($instance)</code> retorna resumo completo para painel</span></div>
                    </div>

                    {{-- Detecção de riscos --}}
                    <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
                        <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem">Detecção de Riscos</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:var(--text);margin-bottom:.5rem">URLs Encurtadas Bloqueadas <code style="font-size:.72rem">(BLOCKED_SHORTENERS)</code></div>
                                <div style="display:flex;flex-wrap:wrap;gap:4px">
                                    @foreach(['bit.ly','cutt.ly','t.ly','tinyurl.com','is.gd','rebrand.ly','ow.ly','buff.ly','dlvr.it','soo.gd','clk.im','shorte.st','adf.ly','bc.vc','tiny.cc','mcaf.ee'] as $s)
                                    <code style="font-size:.72rem;background:rgba(248,81,73,.1);color:var(--red)">{{ $s }}</code>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:var(--text);margin-bottom:.5rem">Keywords Opt-Out <code style="font-size:.72rem">(PT-BR + EN)</code></div>
                                <div style="display:flex;flex-wrap:wrap;gap:4px">
                                    @foreach(['parar','pare','para','stop','sair','cancelar','remover','descadastrar','não quero','desinscrever','bloquear','sai','remove','unsubscribe','descadastro','não me mande','chega'] as $kw)
                                    <code style="font-size:.72rem;background:rgba(56,211,159,.08);color:var(--green)">{{ $kw }}</code>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:.75rem">
                            <div style="font-size:.8rem;font-weight:700;color:var(--text);margin-bottom:.5rem">Sinais de Ban detectados por <code>isBanSignal()</code></div>
                            <div style="display:flex;flex-wrap:wrap;gap:4px">
                                @foreach(['429','rate limit','rate_limit','banned','suspended','blocked','spam','unauthorized'] as $sig)
                                <code style="font-size:.72rem;background:rgba(248,81,73,.1);color:var(--red)">{{ $sig }}</code>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Outbound Policy ── --}}
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-gavel" style="color:var(--blue);font-size:.85rem"></i> WhatsappOutboundPolicy — Compliance de Envio</h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed" style="padding:1.2rem">
                    <table>
                        <thead><tr><th>Regra</th><th>Código de Retorno</th><th>Bypasses</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Envio global desativado (<code>outbound_enabled = false</code>)</td>
                                <td><code>OUTBOUND_DISABLED</code></td>
                                <td>IA (<code>isAi = true</code>) ignora esta regra</td>
                            </tr>
                            <tr>
                                <td>Contato com <code>blocked_at</code> preenchido</td>
                                <td><code>CONTACT_BLOCKED</code></td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td>Contato com <code>opt_out_at</code> preenchido</td>
                                <td><code>CONTACT_OPTOUT</code></td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td>Número na blacklist global SaaS</td>
                                <td><code>CONTACT_BLACKLISTED</code></td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td>Sem opt-in registrado (<code>require_opt_in = true</code>)</td>
                                <td><code>OPTIN_REQUIRED</code></td>
                                <td>Configurável por tenant</td>
                            </tr>
                            <tr>
                                <td>Fora da janela 24h (último inbound > 24h)</td>
                                <td><code>WINDOW_CLOSED</code></td>
                                <td>Templates (<code>isTemplate = true</code>) e IA ignoram</td>
                            </tr>
                            <tr>
                                <td>Rate limit por tenant (<code>wa:tenant:{id}</code>)</td>
                                <td><code>RATE_LIMITED</code></td>
                                <td>—</td>
                            </tr>
                            <tr>
                                <td>Cadência mínima por chat (<code>min_cadence_seconds</code>)</td>
                                <td><code>CADENCE_WAIT</code></td>
                                <td>—</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="kv" style="margin-top:.75rem">
                        <span class="k">Auditoria</span>
                        <span class="v">Mensagens bloqueadas pela policy são logadas via <code>AuditLog::create()</code> com <code>reason</code> e <code>code</code></span>
                    </div>
                </div>
            </div>

            {{-- ── Webhook ── --}}
            <div class="card" style="margin-bottom:1.2rem">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-webhook" style="color:var(--green);font-size:.85rem"></i> Webhooks Evolution API</h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed" style="padding:1.2rem">
                    <div class="kv"><span class="k">Endpoint</span><span class="v"><code>POST /api/evo/webhook/{token}</code> — token por instância, sem auth global</span></div>
                    <div class="kv"><span class="k">HMAC</span><span class="v">Validação opcional via header <code>x-webhook-hmac</code> — configurável por instância</span></div>
                    <div class="kv"><span class="k">Resposta</span><span class="v">Retorna <code>200</code> imediatamente — processa em fila <code>ProcessEvolutionWebhook</code></span></div>
                    <div class="kv"><span class="k">Multi-tenant</span><span class="v">Isolamento por <code>instance_token</code> — nunca vaza dados entre tenants</span></div>
                    <div style="margin-top:.75rem">
                        <table>
                            <thead><tr><th>Evento</th><th>Ação</th></tr></thead>
                            <tbody>
                                <tr><td><code>MESSAGES_UPSERT</code></td><td>Cria/atualiza chat e mensagem no banco; detecta opt-out; desativa bot se <code>fromMe</code></td></tr>
                                <tr><td><code>CONNECTION_UPDATE</code></td><td>Atualiza <code>status</code> da instância (<code>open</code>, <code>close</code>, <code>connecting</code>)</td></tr>
                                <tr><td><code>QRCODE_UPDATED</code></td><td>Armazena novo QR code para exibição no painel</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── Arquitetura de serviços ── --}}
            <div class="card">
                <div class="card-header" onclick="toggleCard(this)">
                    <h3><i class="fas fa-diagram-project" style="color:var(--accent);font-size:.85rem"></i> Mapa de Serviços & Jobs</h3>
                    <i class="fas fa-chevron-down chevron"></i>
                </div>
                <div class="card-body collapsed" style="padding:1.2rem">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem">
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Services</div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">EvolutionApiService</code></span><span class="v" style="font-size:.82rem">Integração direta Evolution API v2 (Baileys): instâncias, QR, sendMessage, sendMedia, presence, checkNumbers</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">AntiBanManager</code></span><span class="v" style="font-size:.82rem">Camada de proteção: janela horária, warming, limites, simulação humana, detecção ban</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">WhatsAppService</code></span><span class="v" style="font-size:.82rem">High-level: dual-channel (Meta Cloud API ou Evolution), auditoria, policy enforcement</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">WhatsappOutboundPolicy</code></span><span class="v" style="font-size:.82rem">Compliance: opt-out, blacklist, 24h window, rate limit, cadência</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">MetaCloudApiService</code></span><span class="v" style="font-size:.82rem">Mensagens oficiais via Meta (templates WABA) — fallback para Evolution</span></div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Jobs (queue: whatsapp)</div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">ProcessBroadcastCampaignJob</code></span><span class="v" style="font-size:.82rem">Orquestrador do disparo em massa — timeout 2h, ShouldBeUnique por campaign_id</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">SendWhatsAppCampaignMessage</code></span><span class="v" style="font-size:.82rem">Envio individual em campanha — 3 tries, backoff 1m/5m/15m</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">ProcessEvolutionWebhook</code></span><span class="v" style="font-size:.82rem">Processa eventos inbound da Evolution API de forma assíncrona</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">ProcessWhatsappAutomations</code></span><span class="v" style="font-size:.82rem">Dispara automações agendadas e fluxos de resposta automática</span></div>
                            <div class="kv"><span class="k"><code style="font-size:.72rem">SendProspectWhatsapp</code></span><span class="v" style="font-size:.82rem">Prospecção/outreach — respeita policy e anti-ban</span></div>
                        </div>
                        <div>
                            <div style="color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Limites por Instância (WhatsappInstance)</div>
                            <div class="kv"><span class="k">Limite tenant</span><span class="v">Máx 3 instâncias por tenant</span></div>
                            <div class="kv"><span class="k"><code>safe_window_start</code></span><span class="v">Horário início envio (configurável — ex: 08:00)</span></div>
                            <div class="kv"><span class="k"><code>safe_window_end</code></span><span class="v">Horário fim envio (configurável — ex: 22:00)</span></div>
                            <div class="kv"><span class="k"><code>daily_limit</code></span><span class="v">Limite diário pós-warming</span></div>
                            <div class="kv"><span class="k"><code>messages_sent_today</code></span><span class="v">Contador com reset automático à meia-noite</span></div>
                            <div class="kv"><span class="k">RateLimiter key</span><span class="v"><code>wa:hourly:{instance_id}</code> — janela 3600s, max 55 hits</span></div>
                            <div class="kv"><span class="k"><code>settings</code> JSON</span><span class="v">restricted_until · restricted_reason · warming_mode · warming_started_at</span></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /tab-whatsapp --}}

    </div>{{-- /main --}}
</div>{{-- /shell --}}

<script>
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidebar a[id^=nav-]').forEach(a => a.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    document.getElementById('nav-' + name).classList.add('active');
}

function toggleCard(header) {
    const body = header.nextElementSibling;
    const open  = !body.classList.contains('collapsed');
    if (open) {
        body.classList.add('collapsed');
        header.classList.remove('open');
    } else {
        body.classList.remove('collapsed');
        header.classList.add('open');
    }
}

function filterSchema(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.schema-table').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function filterRoutes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.route-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
