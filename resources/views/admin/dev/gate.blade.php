<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito — Dev Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;
             background:#0d1117;font-family:'Outfit',sans-serif;color:#e6edf3}
        .card{background:#161b22;border:1px solid #30363d;border-radius:12px;
              padding:2.5rem;width:100%;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.4)}
        .icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#7c3aed,#4f46e5);
              display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 1.5rem}
        h1{font-size:1.4rem;font-weight:700;text-align:center;margin-bottom:.4rem}
        p.sub{text-align:center;color:#8b949e;font-size:.9rem;margin-bottom:2rem}
        label{display:block;font-size:.85rem;color:#8b949e;margin-bottom:.4rem}
        input[type=password]{width:100%;background:#0d1117;border:1px solid #30363d;border-radius:8px;
            padding:.7rem 1rem;color:#e6edf3;font-size:1rem;outline:none;transition:border-color .2s}
        input[type=password]:focus{border-color:#7c3aed}
        button{width:100%;margin-top:1.2rem;padding:.8rem;border:none;border-radius:8px;
               background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;font-weight:600;
               font-size:1rem;cursor:pointer;transition:opacity .2s}
        button:hover{opacity:.88}
        .alert{padding:.75rem 1rem;border-radius:8px;font-size:.9rem;margin-bottom:1.2rem}
        .alert-danger{background:rgba(248,81,73,.12);border:1px solid rgba(248,81,73,.3);color:#f85149}
        .alert-success{background:rgba(56,211,159,.12);border:1px solid rgba(56,211,159,.3);color:#38d39f}
        .back{display:block;text-align:center;margin-top:1.5rem;color:#8b949e;font-size:.85rem;
              text-decoration:none;transition:color .2s}
        .back:hover{color:#e6edf3}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-terminal"></i></div>
        <h1>Dev Portal</h1>
        <p class="sub">Acesso restrito a desenvolvedores do sistema.</p>

        @if(session('error'))
            <div class="alert alert-danger"><i class="fas fa-lock me-1"></i> {{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check me-1"></i> {{ session('success') }}</div>
        @endif
        @if($errors->has('dev_password'))
            <div class="alert alert-danger">{{ $errors->first('dev_password') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.dev.authenticate') }}">
            @csrf
            <label for="dev_password">Senha de acesso</label>
            <input type="password" id="dev_password" name="dev_password"
                   placeholder="••••••••••••" autofocus autocomplete="current-password">
            <button type="submit"><i class="fas fa-unlock-alt me-2"></i>Entrar</button>
        </form>

        <a href="{{ route('admin.dashboard') }}" class="back">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao painel
        </a>
    </div>
</body>
</html>
