<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presença registrada</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:linear-gradient(135deg, #ecfdf5 0%, #eef2ff 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .wrap { max-width:460px; background:white; border-radius:24px; padding:44px 32px; box-shadow:0 25px 60px rgba(0,0,0,0.08); text-align:center; }
        i.check { font-size:4rem; color:#10b981; margin-bottom:18px; }
        h1 { margin:0 0 10px; color:#1e293b; font-size:1.6rem; letter-spacing:-0.5px; }
        p { color:#64748b; font-size:0.95rem; margin:0 0 24px; }
        .actions { display:flex; gap:10px; }
        .btn { flex:1; padding:14px; border-radius:12px; text-decoration:none; font-weight:800; font-size:0.9rem; }
        .btn-primary { background:linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color:white; }
        .btn-ghost { background:#f1f5f9; color:#334155; }
    </style>
</head>
<body>
<div class="wrap">
    @if(!empty($jaRegistrado))
        <i class="fas fa-circle-info check" style="color:#f59e0b;"></i>
        <h1>Presença já registrada</h1>
        <p>Você já marcou presença nesta chamada. <br><strong>{{ $session->title }} — {{ $session->date->format('d/m/Y') }}</strong></p>
    @else
        <i class="fas fa-circle-check check"></i>
        <h1>Presença registrada!</h1>
        <p>{{ $session->title }} — {{ $session->date->format('d/m/Y') }}</p>
    @endif
    <div class="actions">
        <a class="btn btn-primary" href="/chamada/{{ $token }}"><i class="fas fa-plus"></i>&nbsp; Nova Presença</a>
        <a class="btn btn-ghost" href="#" onclick="window.close(); return false;">Fechar</a>
    </div>
</div>
</body>
</html>
