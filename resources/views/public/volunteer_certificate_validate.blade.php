<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validação de Certificado</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; color: #0f172a; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 28px 16px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 8px 20px rgba(15,23,42,.06); }
        h1 { margin: 0 0 6px 0; font-size: 20px; }
        .muted { color: #64748b; }
        .badge { display:inline-block; padding: 4px 10px; border-radius: 999px; font-weight: 800; font-size: 12px; }
        .ok { background: #dcfce7; color: #166534; }
        .bad { background: #fee2e2; color: #991b1b; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 12px; }
        @media (min-width: 720px) { .grid { grid-template-columns: 1fr 1fr; } }
        .kv { background:#f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
        .kv b { display:block; font-size: 12px; color:#64748b; text-transform: uppercase; margin-bottom: 4px; }
        .form { display:flex; gap: 10px; align-items:end; flex-wrap: wrap; margin-top: 12px; }
        input { padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; min-width: 260px; font-size: 14px; }
        button { padding: 10px 12px; border: none; border-radius: 10px; background: #111827; color: #fff; font-weight: 800; cursor: pointer; }
        .code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .5px; }
        .foot { margin-top: 12px; font-size: 12px; color:#64748b; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div style="display:flex; justify-content: space-between; align-items:center; gap: 12px; flex-wrap: wrap;">
                <div>
                    <h1>Validação de Certificado</h1>
                    <div class="muted">{{ $orgName }}</div>
                </div>
                @if($isValid)
                    <div class="badge ok">VÁLIDO</div>
                @else
                    <div class="badge bad">NÃO VALIDADO</div>
                @endif
            </div>

            <div class="grid">
                <div class="kv">
                    <b>Nº do certificado</b>
                    <div class="code">{{ $certificateNo }}</div>
                </div>
                <div class="kv">
                    <b>Código informado</b>
                    <div class="code">{{ $providedCode !== '' ? $providedCode : '—' }}</div>
                </div>
                <div class="kv">
                    <b>Voluntário(a)</b>
                    <div>{{ $cert->volunteer_name ?? '—' }}</div>
                </div>
                <div class="kv">
                    <b>Emissão</b>
                    <div>{{ optional($cert->issued_at)->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div class="kv" style="grid-column: 1 / -1;">
                    <b>Atividade</b>
                    <div>{{ $cert->activity_description }}</div>
                </div>
                <div class="kv">
                    <b>Horas</b>
                    <div><strong>{{ number_format((int) ($cert->hours ?? 0)) }}</strong></div>
                </div>
                <div class="kv">
                    <b>Status da validação</b>
                    @if($isValid)
                        <div><strong style="color:#166534;">Código confere com o certificado.</strong></div>
                    @else
                        <div><strong style="color:#991b1b;">Código não confere.</strong></div>
                    @endif
                </div>
            </div>

            <form class="form" method="GET" action="">
                <div>
                    <div class="muted" style="font-size: 12px; font-weight: 800; margin-bottom: 6px;">Informe o código de autenticidade</div>
                    <input name="code" value="{{ $providedCode }}" placeholder="Ex: 1A2B3C4D5E6F7G8H" maxlength="64">
                </div>
                <div>
                    <button type="submit">Validar</button>
                </div>
            </form>

            <div class="foot">
                Dica: se você recebeu um PDF, o código de autenticidade está impresso no documento.
            </div>
        </div>
    </div>
</body>
</html>

