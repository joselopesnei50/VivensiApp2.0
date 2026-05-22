<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação 2FA — Vivensi</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px;">

<div style="width:100%; max-width:420px;">
    <div style="text-align:center; margin-bottom:32px;">
        <div style="width:56px; height:56px; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <i class="fas fa-shield-halved" style="color:#818cf8; font-size:1.4rem;"></i>
        </div>
        <h1 style="color:white; font-weight:950; font-size:1.8rem; letter-spacing:-1px; margin:0 0 6px;">Verificação 2FA</h1>
        <p style="color:rgba(255,255,255,0.4); font-size:0.9rem;">Digite o código do seu app autenticador</p>
    </div>

    @if(session('error'))
    <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:12px 16px; margin-bottom:16px; color:#f87171; font-size:0.85rem; font-weight:700; text-align:center;">
        <i class="fas fa-circle-exclamation me-1"></i> {{ session('error') }}
    </div>
    @endif

    <div style="background:#1e293b; border-radius:24px; padding:32px; border:1px solid rgba(255,255,255,0.08);">
        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <div style="margin-bottom:20px;">
                <input type="text" name="code" autofocus required
                    placeholder="000 000"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    style="width:100%; padding:16px; background:rgba(255,255,255,0.05); border:2px solid rgba(255,255,255,0.1); border-radius:14px; color:white; font-size:1.6rem; font-weight:900; text-align:center; letter-spacing:8px; outline:none;"
                    onfocus="this.style.borderColor='rgba(99,102,241,0.6)'"
                    onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
                    value="{{ old('code') }}">
            </div>

            <button type="submit" style="width:100%; background:linear-gradient(135deg,#6366f1,#4f46e5); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:1rem; cursor:pointer; box-shadow:0 8px 24px rgba(99,102,241,0.25); margin-bottom:12px;">
                <i class="fas fa-check me-2"></i> Verificar
            </button>
        </form>

        <div style="text-align:center; border-top:1px solid rgba(255,255,255,0.06); padding-top:16px; margin-top:4px;">
            <p style="color:rgba(255,255,255,0.3); font-size:0.78rem; margin-bottom:8px;">Ou use um código de recuperação</p>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-2fa').submit();"
               style="color:rgba(255,255,255,0.3); font-size:0.78rem; text-decoration:none; font-weight:700;">
                <i class="fas fa-sign-out-alt me-1"></i> Sair da conta
            </a>
            <form id="logout-2fa" method="POST" action="{{ route('logout') }}" style="display:none;">@csrf</form>
        </div>
    </div>
</div>

</body>
</html>
