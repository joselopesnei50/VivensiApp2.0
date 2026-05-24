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
