<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reunião Cancelada – Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Inter',sans-serif; background:#f0f4f8; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { background:#fff; border-radius:16px; box-shadow:0 8px 40px rgba(0,0,0,.10); padding:48px 40px; text-align:center; max-width:400px; width:100%; }
        .icon { font-size:2.5rem; margin-bottom:12px; }
        h1 { font-size:1.2rem; font-weight:800; color:#0f172a; margin-bottom:10px; }
        p { font-size:.88rem; color:#64748b; line-height:1.65; }
        a { display:inline-block; margin-top:24px; padding:11px 24px; background:#4f6ef7; color:#fff; border-radius:8px; font-size:.85rem; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">❌</div>
        <h1>Reunião cancelada</h1>
        <p>Sua reunião com a equipe Vivensi foi cancelada com sucesso.<br>Se quiser reagendar, clique abaixo.</p>
        <a href="{{ route('booking.index') }}">Reagendar uma conversa</a>
    </div>
</body>
</html>
