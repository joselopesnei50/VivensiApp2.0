<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Seus dados estão prontos — Vivensi</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Segoe UI,Roboto,Arial,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;padding:28px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(15,23,42,.06);">
            <div style="padding:22px;background:linear-gradient(135deg,#4f46e5,#3730a3);color:#fff;">
                <div style="font-weight:800;font-size:20px;">Vivensi — LGPD</div>
                <div style="font-size:13px;opacity:.85;margin-top:4px;">Portabilidade de dados · Art. 18 IV</div>
            </div>
            <div style="padding:24px;">
                <p style="margin:0 0 12px;font-size:15px;">Olá, <strong>{{ $userName }}</strong>.</p>

                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#334155;">
                    Seus dados pessoais foram compilados em um arquivo ZIP conforme solicitado.
                    Ele contém um <code>data.json</code> com todas as categorias de informação que
                    guardamos sobre você.
                </p>

                <p style="margin:24px 0 8px;text-align:center;">
                    <a href="{{ $downloadUrl }}"
                       style="background:#4f46e5;color:#fff;padding:12px 22px;border-radius:10px;
                              text-decoration:none;font-weight:700;display:inline-block;">
                        Baixar meus dados (ZIP)
                    </a>
                </p>

                <p style="margin:16px 0;font-size:13px;color:#64748b;text-align:center;">
                    O link expira em <strong>{{ $expiresAt }}</strong>.<br>
                    Depois disso, será preciso fazer nova solicitação em <em>/eu/dados</em>.
                </p>

                <hr style="border:0;border-top:1px solid #e2e8f0;margin:20px 0;">

                <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                    Se você não solicitou este download, ignore este email — o link expira
                    sozinho. Em caso de dúvidas, contate nosso DPO em
                    @php $dpoEmail = config('legal.email_dpo', 'dpo@vivensi.app.br'); @endphp
                    <a href="mailto:{{ $dpoEmail }}" style="color:#4f46e5;">{{ $dpoEmail }}</a>.
                </p>
            </div>
            <div style="padding:14px 22px;background:#f1f5f9;color:#64748b;font-size:11px;border-top:1px solid #e2e8f0;">
                Vivensi é um produto da NC5 HUB DIGITAL LTDA · CNPJ 67.848.807/0001-50
            </div>
        </div>
    </div>
</body>
</html>
