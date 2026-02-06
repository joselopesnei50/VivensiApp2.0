<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Recibo | Vivensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; font-family: 'Outfit', sans-serif; padding: 24px; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; max-width: 720px; width: 100%; padding: 32px; box-shadow: 0 30px 80px rgba(0,0,0,0.06); }
        h1 { margin: 0 0 10px 0; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
        p { margin: 0 0 18px 0; color: #475569; line-height: 1.6; }
        label { display: block; font-weight: 800; color: #334155; margin-bottom: 8px; }
        input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 1rem; }
        .row { display: flex; gap: 12px; margin-top: 12px; }
        button { background: #1e293b; color: white; border: none; padding: 14px 18px; border-radius: 12px; font-weight: 800; cursor: pointer; }
        a { color: #4f46e5; font-weight: 800; text-decoration: none; }
        .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase; }
        .ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .warn { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
        .bad { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .muted { color: #64748b; font-size: 0.9rem; }
        .result { margin-top: 18px; border-top: 1px solid #f1f5f9; padding-top: 18px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
        .kv { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; }
        .k { font-size: 0.7rem; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
        .v { font-weight: 900; color: #0f172a; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="card">
        <h1>Validar recibo</h1>
        <p>Cole o <strong>link</strong> do recibo (ex.: <span class="muted">/r/UUID</span>) ou o <strong>código de validação</strong> (16 caracteres) para confirmar autenticidade.</p>

        <form method="POST" action="{{ url('/validar-recibo') }}">
            @csrf
            <label for="query">Link ou código</label>
            <input id="query" name="query" value="{{ old('query', request('query')) }}" placeholder="Cole aqui o link /r/... ou o código (ex.: 1A2B3C4D5E6F7A8B)" required>
            <div class="row">
                <button type="submit">Validar</button>
                <a href="{{ url('/') }}" style="display:flex; align-items:center;">Voltar</a>
            </div>
        </form>

        @if(isset($result))
            <div class="result">
                @php
                    $status = $result['status'] ?? 'invalid';
                    $badgeClass = $status === 'valid' ? 'ok' : ($status === 'expired' ? 'warn' : 'bad');
                    $badgeText = $status === 'valid' ? 'Válido' : ($status === 'expired' ? 'Expirado' : ($status === 'error' ? 'Erro' : 'Não encontrado'));
                @endphp
                <div style="display:flex; align-items:center; justify-content: space-between; gap: 12px;">
                    <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                    @if(($result['receipt_url'] ?? null) && ($status === 'valid'))
                        <a href="{{ $result['receipt_url'] }}" target="_blank">Abrir recibo</a>
                    @endif
                </div>
                <p style="margin-top: 10px;">{{ $result['message'] ?? '' }}</p>

                @if(($result['transaction'] ?? null) && ($result['tenant'] ?? null))
                    <div class="grid">
                        <div class="kv">
                            <div class="k">Instituição</div>
                            <div class="v">{{ $result['tenant']->name ?? '—' }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">Data</div>
                            <div class="v">{{ \Carbon\Carbon::parse($result['transaction']->date)->format('d/m/Y') }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">Valor</div>
                            <div class="v">R$ {{ number_format((float) $result['transaction']->amount, 2, ',', '.') }}</div>
                        </div>
                        <div class="kv">
                            <div class="k">Validade do link</div>
                            <div class="v">
                                @if($result['transaction']->public_receipt_expires_at)
                                    {{ $result['transaction']->public_receipt_expires_at->format('d/m/Y') }}
                                @else
                                    Sem expiração
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</body>
</html>

