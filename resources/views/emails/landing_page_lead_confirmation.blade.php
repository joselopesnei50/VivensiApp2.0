<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recebemos sua inscrição</title>
</head>
<body style="margin:0; padding:0; background:#f4f6fb; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 20px rgba(15,23,42,.06);">
                    <tr>
                        <td style="background:#0f172a; padding:28px 32px;">
                            <div style="color:#22c55e; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1.4px; margin-bottom:6px;">Inscrição recebida</div>
                            <h1 style="color:#ffffff; font-size:1.4rem; font-weight:800; margin:0; line-height:1.3;">
                                Recebemos sua inscrição, {{ $leadName }}!
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px 32px; color:#334155; font-size:.95rem; line-height:1.65;">
                            <p style="margin:0 0 14px 0;">
                                Confirmamos o recebimento da sua inscrição em <strong>{{ $page->title }}</strong> — organizada por <strong>{{ $tenantName }}</strong>.
                            </p>
                            <p style="margin:0 0 14px 0;">
                                Você não precisa fazer mais nada agora. Nossa equipe vai analisar seus dados e entrar em contato pelo e-mail informado ({{ $leadEmail }}) assim que houver novidade.
                            </p>
                            <p style="margin:0 0 14px 0; color:#64748b; font-size:.88rem;">
                                Se você não fez esta inscrição, pode ignorar este e-mail com segurança — nenhum dado será compartilhado sem confirmação.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <a href="{{ $pageUrl }}"
                               style="display:inline-block; background:#4f46e5; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:10px; font-weight:700; font-size:.9rem;">
                                Rever informações da inscrição
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 28px 32px; border-top:1px solid #e2e8f0; color:#94a3b8; font-size:.75rem; line-height:1.5;">
                            E-mail enviado automaticamente por {{ $tenantName }} via Vivensi.
                            Em dúvidas sobre seus dados pessoais (LGPD), responda este e-mail.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
