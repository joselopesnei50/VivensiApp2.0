<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status SIC — {{ $sic->protocol }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        .header { background: #1e293b; color: #fff; padding: 18px 24px; }
        .header a { color: #94a3b8; text-decoration: none; font-size: .9rem; }
        .header a:hover { color: #fff; }
        .container { max-width: 700px; margin: 40px auto; padding: 0 20px 60px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(15,23,42,.08); padding: 36px; margin-bottom: 20px; }
        h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; }
        .meta-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: .93rem; }
        .meta-row:last-child { border-bottom: none; }
        .meta-label { color: #64748b; font-weight: 700; }
        .meta-value { color: #0f172a; font-weight: 600; text-align: right; max-width: 60%; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: .8rem; font-weight: 800; text-transform: uppercase; }
        .message-box { background: #f8fafc; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; font-size: .93rem; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
        .response-box { background: #f0fdf4; border-radius: 10px; padding: 16px; border: 1px solid #bbf7d0; font-size: .93rem; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
        .overdue-warn { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; color: #dc2626; font-weight: 700; margin-bottom: 16px; font-size: .9rem; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #3b82f6; font-weight: 700; text-decoration: none; font-size: .95rem; }
    </style>
</head>
<body>

<div class="header">
    <div style="font-weight:800; font-size:1.05rem; margin-bottom:4px;">{{ $portal->title }}</div>
    <a href="{{ route('sic.public.form', $portal->slug) }}">← Nova solicitação</a>
    <span style="color:#64748b; margin: 0 8px;">·</span>
    <a href="{{ route('transparency.portal', $portal->slug) }}">Portal de Transparência</a>
</div>

<div class="container">

    <div class="card">
        <h1>Acompanhamento de Solicitação SIC</h1>

        @if($sic->isOverdue())
            <div class="overdue-warn">⚠️ O prazo de resposta (20 dias úteis) foi excedido. Você pode entrar em contato diretamente com a organização.</div>
        @endif

        <div class="meta-row">
            <span class="meta-label">Protocolo</span>
            <span class="meta-value" style="font-family: monospace; font-size:1rem;">{{ $sic->protocol }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Status</span>
            <span class="meta-value">
                <span class="status-badge" style="background: {{ $sic->status_color }}22; color: {{ $sic->status_color }};">
                    {{ $sic->status_label }}
                </span>
            </span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Assunto</span>
            <span class="meta-value">{{ $sic->subject }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Data da solicitação</span>
            <span class="meta-value">{{ $sic->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Prazo para resposta</span>
            <span class="meta-value" style="{{ $sic->isOverdue() ? 'color:#dc2626' : '' }}">
                {{ $sic->deadline_at->format('d/m/Y') }}
            </span>
        </div>
        @if($sic->responded_at)
        <div class="meta-row">
            <span class="meta-label">Respondida em</span>
            <span class="meta-value">{{ $sic->responded_at->format('d/m/Y H:i') }}</span>
        </div>
        @endif
    </div>

    <div class="card">
        <div style="font-weight:800; font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:12px;">Sua Solicitação</div>
        <div class="message-box">{{ $sic->message }}</div>
    </div>

    @if($sic->response)
    <div class="card">
        <div style="font-weight:800; font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#16a34a; margin-bottom:12px;">✅ Resposta da Organização</div>
        <div class="response-box">{{ $sic->response }}</div>
    </div>
    @else
    <div class="card" style="background: #fffbeb; border: 1px solid #fde68a;">
        <div style="color:#92400e; font-weight:700; font-size:.95rem;">⏳ Aguardando resposta</div>
        <div style="color:#92400e; font-size:.87rem; margin-top:6px;">A organização ainda não respondeu esta solicitação. Retorne em breve ou entre em contato diretamente.</div>
    </div>
    @endif

    <div class="back-link">
        <a href="{{ route('sic.public.form', $portal->slug) }}">Fazer nova solicitação</a>
    </div>
</div>

</body>
</html>
