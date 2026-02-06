<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo expirado | Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 24px; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; max-width: 520px; width: 100%; padding: 32px; box-shadow: 0 30px 80px rgba(0,0,0,0.06); }
        h1 { margin: 0 0 10px 0; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        p { margin: 0 0 18px 0; color: #475569; line-height: 1.6; }
        .hint { font-size: 0.9rem; color: #64748b; }
        a { color: #4f46e5; font-weight: 800; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Este link de recibo expirou</h1>
        <p>Por segurança, o recibo público possui um prazo de validade. Solicite um novo link à organização que emitiu o recibo.</p>
        <p class="hint">Se você é o gestor, gere um novo recibo ou reenvie o link atualizado pelo painel.</p>
        <p class="hint">Se você possui o <strong>código de validação</strong>, ainda pode consultar o status em <a href="{{ url('/validar-recibo') }}">Validar recibo</a>.</p>
        <p class="hint"><a href="{{ url('/') }}">Voltar para a página inicial</a></p>
    </div>
</body>
</html>

