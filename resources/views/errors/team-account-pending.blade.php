<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi — Conta com pendência</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #080808; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px); background-size: 60px 60px; }
        .box { max-width: 480px; width: 100%; }
        img { height: 30px; margin-bottom: 36px; }
        .eyebrow { color: #f59e0b; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 12px; }
        h1 { font-size: 2rem; font-weight: 900; letter-spacing: -.03em; line-height: 1.1; margin-bottom: 14px; }
        p { color: rgba(255,255,255,.6); line-height: 1.65; margin-bottom: 26px; }
        form button { padding: 13px 22px; border-radius: 12px; border: 1px solid rgba(255,255,255,.18); background: transparent; color: #fff; font-weight: 700; font-family: inherit; cursor: pointer; }
        form button:hover { background: rgba(255,255,255,.06); }
    </style>
</head>
<body>
    <div class="box">
        <img src="{{ asset('img/novalogo.png') }}" alt="Vivensi">
        <div class="eyebrow">Acesso temporariamente indisponível</div>
        <h1>A conta da {{ $tenant->name }} precisa de uma ação do administrador</h1>
        <p>Há uma pendência na assinatura da organização (contrato ou pagamento). Assim que o administrador regularizar, o seu acesso volta normalmente — você não precisa fazer nada.</p>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Sair</button>
        </form>
    </div>
</body>
</html>
