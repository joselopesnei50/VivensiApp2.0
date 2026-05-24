<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erro interno | Vivensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Inter, system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { text-align: center; padding: 2rem; max-width: 480px; }
        .code { font-size: 6rem; font-weight: 800; background: linear-gradient(135deg, #dc2626, #f87171); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        h1 { font-size: 1.5rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #f1f5f9; }
        p { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; }
        a { display: inline-block; margin-top: 2rem; padding: 0.75rem 1.5rem; background: #4f46e5; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background .2s; }
        a:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">500</div>
        <h1>Erro interno do servidor</h1>
        <p>Algo deu errado do nosso lado. Já fomos notificados e vamos resolver em breve.</p>
        <a href="{{ url('/') }}">Voltar ao início</a>
    </div>
</body>
</html>
