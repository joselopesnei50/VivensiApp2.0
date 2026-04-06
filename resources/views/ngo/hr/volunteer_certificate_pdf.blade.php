<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Certificado de Voluntariado</title>
    <style>
        @page { margin: 26px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; }
        .frame { border: 2px solid #0f172a; padding: 18px; position: relative; }
        .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 14px; }
        .brand { font-size: 12px; letter-spacing: .4px; color:#334155; text-transform: uppercase; font-weight: 800; }
        h1 { margin: 6px 0 0 0; font-size: 24px; letter-spacing: .8px; }
        .muted { color: #64748b; }
        .center { text-align: center; }
        .body { margin-top: 10px; font-size: 14px; line-height: 1.65; }
        .name { font-size: 20px; font-weight: 900; margin: 10px 0 2px 0; }
        .highlight { font-weight: 900; }
        .pill { display:inline-block; padding: 4px 10px; border-radius: 999px; background:#111827; color:#fff; font-size: 11px; font-weight: 900; letter-spacing: .6px; }
        .meta { margin-top: 14px; font-size: 11px; color: #334155; background:#f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; }
        .meta .code { font-family: DejaVu Sans Mono, ui-monospace, Menlo, monospace; letter-spacing: .6px; }
        .sign { margin-top: 26px; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; }
        .line { border-top: 1px solid #0f172a; margin-top: 34px; padding-top: 6px; font-size: 12px; }
        .water { position:absolute; top: 45%; left: 50%; transform: translate(-50%, -50%); font-size: 54px; color: rgba(15,23,42,.05); font-weight: 900; letter-spacing: 3px; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="water">VIVENSI</div>

        <div class="header">
            <table class="grid">
                <tr>
                    <td>
                        <div class="brand">{{ $orgName }}</div>
                        <h1>CERTIFICADO DE VOLUNTARIADO</h1>
                        <div class="muted" style="margin-top:4px;">Documento oficial de reconhecimento</div>
                    </td>
                    <td style="text-align:right;">
                        <span class="pill">CERTIFICADO</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="center">
            <div class="muted">Certificamos que</div>
            <div class="name">{{ $volunteer->name }}</div>
        </div>

        <div class="body">
            atuou como voluntário(a) em atividades relacionadas a <span class="highlight">{{ $cert->activity_description }}</span>,
            totalizando <span class="highlight">{{ number_format((int) $cert->hours) }}</span> hora(s) de dedicação.
            <br><br>
            Emitido em <span class="highlight">{{ optional($cert->issued_at)->format('d/m/Y') }}</span>.
        </div>

        <div class="meta">
            <div><strong>Nº do certificado:</strong> <span class="code">{{ $certificateNo }}</span></div>
            <div><strong>Código de autenticidade:</strong> <span class="code">{{ $authCode }}</span></div>
            <div>
                <strong>Validar:</strong>
                <span class="code">{{ url('/validar-certificado/' . (int) $cert->id) }}?code={{ $authCode }}</span>
            </div>
            <div><strong>Gerado em:</strong> {{ $generatedAt }} · <strong>Emitido por:</strong> {{ auth()->user()->name ?? '—' }}</div>
        </div>

        <div class="sign">
            <div class="line"><strong>Responsável</strong> (assinatura)</div>
            <div class="line"><strong>Diretoria</strong> (assinatura)</div>
        </div>
    </div>
</body>
</html>

