<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIC — {{ $portal->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        .header { background: #1e293b; color: #fff; padding: 18px 24px; display: flex; align-items: center; gap: 16px; }
        .header a { color: #94a3b8; text-decoration: none; font-size: .9rem; }
        .header a:hover { color: #fff; }
        .container { max-width: 700px; margin: 40px auto; padding: 0 20px 60px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(15,23,42,.08); padding: 36px; }
        h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .subtitle { color: #64748b; font-size: .95rem; margin-bottom: 28px; line-height: 1.5; }
        label { display: block; font-weight: 700; font-size: .85rem; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
        input, textarea { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: .95rem; color: #0f172a; background: #f8fafc; transition: border-color .15s; margin-bottom: 20px; }
        input:focus, textarea:focus { outline: none; border-color: #3b82f6; background: #fff; }
        textarea { resize: vertical; min-height: 140px; }
        .btn { display: block; width: 100%; background: #3b82f6; color: #fff; border: none; border-radius: 10px; padding: 14px; font-family: inherit; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .15s; }
        .btn:hover { background: #2563eb; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px 16px; margin-bottom: 24px; font-size: .9rem; color: #1d4ed8; line-height: 1.5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
        .check-link { text-align: center; margin-top: 20px; font-size: .9rem; color: #64748b; }
        .check-link a { color: #3b82f6; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>

<div class="header">
    <div style="flex:1;">
        <div style="font-weight:800; font-size:1.05rem;">{{ $portal->title }}</div>
        <a href="{{ route('transparency.portal', $portal->slug) }}">← Voltar ao Portal</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h1>📋 Serviço de Informação ao Cidadão (SIC)</h1>
        <p class="subtitle">
            Solicite informações sobre as atividades, finanças e gestão desta organização.
            Respondemos em até <strong>20 dias úteis</strong>, conforme a Lei de Acesso à Informação (LAI).
        </p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <div class="info-box">
            ℹ️ Sua solicitação receberá um número de protocolo único. Guarde-o para acompanhar o andamento da resposta.
        </div>

        <form action="{{ route('sic.public.store', $portal->slug) }}" method="POST">
            @csrf
            <div class="grid-2">
                <div>
                    <label for="requester_name">Seu nome *</label>
                    <input type="text" name="requester_name" id="requester_name" value="{{ old('requester_name') }}" required maxlength="255">
                </div>
                <div>
                    <label for="requester_email">Seu e-mail *</label>
                    <input type="email" name="requester_email" id="requester_email" value="{{ old('requester_email') }}" required maxlength="255">
                </div>
            </div>
            <div>
                <label for="subject">Assunto *</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required maxlength="500" placeholder="Descreva brevemente o que você está solicitando">
            </div>
            <div>
                <label for="message">Descrição da solicitação *</label>
                <textarea name="message" id="message" required maxlength="5000" placeholder="Detalhe sua solicitação. Seja específico sobre quais informações você precisa.">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn">Enviar Solicitação</button>
        </form>

        <div class="check-link">
            Já tem protocolo? <a href="#" onclick="document.getElementById('check-form').style.display='block'; return false;">Consultar situação</a>
        </div>

        <form id="check-form" style="display:none; margin-top:20px;" action="#" onsubmit="consultarProtocolo(event)">
            <label for="check-protocol">Número do protocolo</label>
            <div style="display:flex; gap:10px;">
                <input type="text" id="check-protocol" placeholder="Ex: SIC-2026-ABC123" style="margin-bottom:0; text-transform:uppercase;">
                <button type="submit" class="btn" style="width:auto; padding: 12px 24px;">Consultar</button>
            </div>
        </form>
    </div>
</div>

<script>
function consultarProtocolo(e) {
    e.preventDefault();
    const protocol = document.getElementById('check-protocol').value.trim().toUpperCase();
    if (!protocol) return;
    window.location.href = '/transparencia/{{ $portal->slug }}/sic/' + encodeURIComponent(protocol);
}
</script>
</body>
</html>
