<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi - Redefinir Senha</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #2563EB; --primary-hover: #1D4ED8; }
        body { margin: 0; font-family: 'Outfit', sans-serif; background: #FAFAFA; }
        .wrap { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 40px; }
        .card { width: 100%; max-width: 520px; background:#fff; border-radius: 24px; padding: 44px; border: 1px solid rgba(0,0,0,0.03); box-shadow: 0 10px 40px rgba(0,0,0,0.06); }
        .logo { display:flex; justify-content:center; margin-bottom: 10px; }
        .title { text-align:center; margin: 10px 0 6px 0; font-size: 1.4rem; color: #1e293b; font-weight: 800; }
        .subtitle { text-align:center; margin: 0 0 20px 0; color:#64748B; font-size: 0.95rem; line-height:1.4; }
        .form-group { margin-bottom: 18px; }
        label { display:block; margin-bottom: 8px; color:#64748B; font-size:0.9rem; font-weight:600; }
        .input-group { position: relative; }
        .input-group i { position:absolute; left: 15px; top: 50%; transform: translateY(-50%); color:#94A3B8; }
        .form-control { width: 100%; padding: 14px 14px 14px 45px; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 1rem; background:#F8FAFC; }
        .form-control:focus { background:#fff; border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); outline:none; }
        .btn-primary { width: 100%; padding: 14px; background: var(--primary-color); color:#fff; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor:pointer; }
        .btn-primary:hover { background: var(--primary-hover); }
        .msg { border-radius: 10px; padding: 12px; font-size: 0.9rem; margin-bottom: 16px; }
        .msg.err { background:#FEF2F2; border:1px solid #FECACA; color:#DC2626; }
        .links { text-align:center; margin-top: 18px; color:#64748B; font-size: 0.9rem; }
        .links a { color: var(--primary-color); font-weight: 700; text-decoration:none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="logo">
            <img src="{{ asset('img/logovivensi.png') }}" alt="VIVENSI" style="max-height: 60px;">
        </div>

        <div class="title">Redefinir senha</div>
        <div class="subtitle">
            Crie uma nova senha para sua conta.
        </div>

        @if($errors->any())
            <div class="msg err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label>E-mail</label>
                <div class="input-group">
                    <i class="far fa-envelope"></i>
                    <input type="email" name="email" class="form-control" required value="{{ old('email', $email) }}" placeholder="voce@empresa.com">
                </div>
            </div>

            <div class="form-group">
                <label>Nova senha</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <div class="form-group">
                <label>Confirmar nova senha</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password_confirmation" class="form-control" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-primary">Salvar nova senha</button>
        </form>

        <div class="links">
            <a href="{{ route('login') }}">Voltar para o login</a>
        </div>
    </div>
</div>
</body>
</html>

