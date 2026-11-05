<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sua conversa com o Vivensi</title>
</head>
<body style="margin:0; padding:0; background:#f4f6fb; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 20px rgba(15,23,42,.06);">
                    <tr>
                        <td style="background:#0f172a; padding:28px 32px;">
                            <div style="color:#818cf8; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1.4px; margin-bottom:6px;">Chat do Vivensi</div>
                            <h1 style="color:#ffffff; font-size:1.35rem; font-weight:800; margin:0; line-height:1.3;">
                                {{ $name ? "Oi, {$name}!" : 'Oi!' }} Sua conversa está guardada.
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px 32px; color:#334155; font-size:.95rem; line-height:1.65;">
                            <p style="margin:0 0 14px 0;">
                                Você pediu para receber o link da sua conversa no site do Vivensi. Clique no botão abaixo para continuar de onde parou, em qualquer celular ou computador.
                            </p>
                            <p style="margin:0 0 14px 0;">
                                Se a nossa equipe responder enquanto você estiver fora, a resposta vai estar lá esperando por você.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px 32px;">
                            <a href="{{ $resumeUrl }}"
                               style="display:inline-block; background:#4f46e5; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:10px; font-weight:700; font-size:.9rem;">
                                Continuar a conversa
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px 32px; color:#94a3b8; font-size:.8rem; line-height:1.55;">
                            O link vale por {{ $days }} dias e é só seu, não encaminhe este e-mail. Se não foi você que pediu, pode ignorar.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
