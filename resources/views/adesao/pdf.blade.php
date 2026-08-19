<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Adesao Vivensi</title>
    <style>
        @page { margin: 24mm 20mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color:#222; line-height: 1.6; }
        h1, h2, h3 { color:#0f172a; margin: 12px 0 6px 0; }
        h1 { font-size: 15pt; text-align:center; }
        h2 { font-size: 13pt; }
        h3 { font-size: 11.5pt; }
        p { margin: 6px 0; }
        ul { margin: 4px 0 8px 0; padding-left: 18px; }
        .badge { display:inline-block; padding: 3px 8px; background:#f1f5f9; border-radius: 999px; font-size: 9pt; color:#334155; }
        .signature-block { margin-top: 40px; text-align:center; }
        .signature-block img { max-width: 280px; border-bottom: 1px solid #000; }
        .meta { font-size: 9pt; color:#64748b; margin-top: 8px; }
        .auth-code { font-family: monospace; font-size: 9pt; color:#334155; }
        .divider { border-top: 1px solid #e2e8f0; margin: 24px 0; }
    </style>
</head>
<body>
    <div class="badge">Codigo: {{ strtoupper(substr($contract->document_hash ?? '', 0, 16)) }}</div>

    {!! $contract->content !!}

    <div class="divider"></div>

    @if($contract->status === 'signed' && $contract->signature_image)
        <div class="signature-block">
            <img src="{{ $contract->signature_image }}" alt="Assinatura">
            <p><strong>{{ $contract->signer_name }}</strong></p>
            <p class="meta">
                Data/Hora: {{ optional($contract->signed_at)->format('d/m/Y H:i:s') }}<br>
                IP: {{ $contract->signer_ip ?? '-' }}<br>
                Hash da assinatura: <span class="auth-code">{{ strtoupper(substr($contract->signature_hash ?? '', 0, 32)) }}</span><br>
                Autenticacao Digital Vivensi
            </p>
        </div>
    @else
        <p class="meta">Contrato ainda nao assinado.</p>
    @endif
</body>
</html>
