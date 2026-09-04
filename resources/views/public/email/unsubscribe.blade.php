<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Descadastro efetuado — {{ $tenant->name }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0; padding: 32px 16px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        color: #0f172a; min-height: 100vh;
        display: flex; align-items: center; justify-content: center;
    }
    .card {
        max-width: 520px; width: 100%;
        background: #fff; border-radius: 20px;
        box-shadow: 0 20px 60px rgba(5, 150, 105, 0.15);
        padding: 44px 40px; text-align: center;
    }
    .check {
        width: 72px; height: 72px; margin: 0 auto 24px;
        background: #059669; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 36px; font-weight: 800;
        box-shadow: 0 8px 24px rgba(5, 150, 105, 0.35);
    }
    h1 {
        font-size: 1.6rem; font-weight: 800; margin: 0 0 12px;
        letter-spacing: -0.4px; color: #065f46;
    }
    p { font-size: 0.98rem; color: #475569; line-height: 1.6; margin: 0 0 14px; }
    .email-badge {
        display: inline-block; margin: 12px 0 24px;
        padding: 8px 16px; background: #ecfdf5; color: #065f46;
        border-radius: 999px; font-weight: 600; font-size: 0.88rem;
        border: 1px solid #a7f3d0;
    }
    .divider {
        height: 1px; background: #f1f5f9; margin: 28px 0 24px;
    }
    .footer-note {
        font-size: 0.8rem; color: #94a3b8; line-height: 1.55;
    }
    .footer-note a { color: #059669; text-decoration: none; font-weight: 600; }
    form.reactivate {
        margin-top: 8px;
    }
    button.link-btn {
        background: none; border: 0; padding: 0;
        color: #64748b; font: inherit; font-size: 0.85rem;
        text-decoration: underline; cursor: pointer;
    }
    button.link-btn:hover { color: #059669; }
    .tenant {
        font-size: 0.78rem; color: #94a3b8; text-transform: uppercase;
        letter-spacing: 1.5px; margin-bottom: 20px;
    }
</style>
</head>
<body>
    <div class="card">
        <div class="tenant">{{ $tenant->name }}</div>
        <div class="check">&#10003;</div>
        <h1>Descadastro efetuado</h1>
        <p>Você não vai mais receber e-mails de campanhas desta organização.</p>
        <div class="email-badge">{{ $email }}</div>
        <p style="font-size:0.9rem;">
            A remoção vale para todas as listas de contatos de <strong>{{ $tenant->name }}</strong>.
            Comunicações transacionais (recibo, confirmação de doação) podem continuar chegando quando aplicável.
        </p>

        <div class="divider"></div>

        <form class="reactivate" method="POST" action="{{ route('public.email.unsubscribe.reactivate', ['token' => $token]) }}">
            @csrf
            <p class="footer-note">
                Foi por engano?
                <button type="submit" class="link-btn">Reativar meu cadastro</button>
            </p>
        </form>

        <p class="footer-note" style="margin-top: 18px;">
            Dúvidas sobre seus dados? Entre em contato com a organização.<br>
            Ambiente <a href="https://vivensi.app.br">Vivensi</a> — LGPD art. 18, IX.
        </p>
    </div>
</body>
</html>
