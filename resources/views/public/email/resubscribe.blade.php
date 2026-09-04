<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Cadastro reativado — {{ $tenant->name }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0; padding: 32px 16px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #0f172a; min-height: 100vh;
        display: flex; align-items: center; justify-content: center;
    }
    .card {
        max-width: 520px; width: 100%;
        background: #fff; border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 53, 121, 0.15);
        padding: 44px 40px; text-align: center;
    }
    .icon {
        width: 72px; height: 72px; margin: 0 auto 24px;
        background: #003579; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 36px;
    }
    h1 {
        font-size: 1.5rem; font-weight: 800; margin: 0 0 12px;
        color: #003579; letter-spacing: -0.4px;
    }
    p { font-size: 0.95rem; color: #475569; line-height: 1.6; margin: 0 0 14px; }
    .email-badge {
        display: inline-block; margin: 12px 0 8px;
        padding: 8px 16px; background: #eff6ff; color: #003579;
        border-radius: 999px; font-weight: 600; font-size: 0.88rem;
    }
    .tenant {
        font-size: 0.78rem; color: #94a3b8; text-transform: uppercase;
        letter-spacing: 1.5px; margin-bottom: 20px;
    }
</style>
</head>
<body>
    <div class="card">
        <div class="tenant">{{ $tenant->name }}</div>
        <div class="icon">&#8635;</div>
        <h1>Cadastro reativado</h1>
        <p>Você voltou a receber comunicações de <strong>{{ $tenant->name }}</strong>.</p>
        <div class="email-badge">{{ $email }}</div>
    </div>
</body>
</html>
