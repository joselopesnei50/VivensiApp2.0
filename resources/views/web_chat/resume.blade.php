<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Abrindo sua conversa…</title>
    <style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#334155}</style>
</head>
<body>
    <p>Abrindo sua conversa…</p>
    <script>
        try { localStorage.setItem('vivchat', JSON.stringify({ id: @json($publicId), token: @json($token) })); } catch (e) {}
        location.replace('/?chat=1');
    </script>
</body>
</html>
