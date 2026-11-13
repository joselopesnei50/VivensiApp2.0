{{-- Base das páginas de erro: independente do banco e da sessão (vale até em manutenção). --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') | Vivensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
        .box { text-align: center; max-width: 480px; }
        .brand { font-size: 1.6rem; font-weight: 900; letter-spacing: -.03em; color: #fff; margin-bottom: 28px; }
        .brand span { color: #818cf8; }
        .icon { width: 64px; height: 64px; border-radius: 18px; background: rgba(79,70,229,.15); color: #a5b4fc; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 18px; }
        h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 10px; color: #f8fafc; }
        p { color: #94a3b8; font-size: .95rem; line-height: 1.65; }
        .actions { margin-top: 28px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.btn { display: inline-block; padding: .75rem 1.4rem; background: #4f46e5; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .9rem; }
        a.btn:hover { background: #4338ca; }
        a.btn.ghost { background: transparent; border: 1px solid rgba(255,255,255,.18); }
        .note { margin-top: 26px; font-size: .8rem; color: #64748b; }
    </style>
    @yield('head')
</head>
<body>
    <div class="box">
        <div class="brand">Vivensi<span>!</span></div>
        <div class="icon">@yield('icon')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="actions">@yield('actions')</div>
        <div class="note">Precisa de ajuda? contato@vivensi.app.br</div>
    </div>
</body>
</html>
