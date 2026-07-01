<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada — {{ $session->title }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:linear-gradient(135deg, #eef2ff 0%, #fef3c7 100%); min-height:100vh; padding:20px; }
        .wrap { max-width:520px; margin:40px auto; background:white; border-radius:24px; padding:36px 32px; box-shadow: 0 25px 60px rgba(0,0,0,0.08); }
        h1 { margin:0 0 6px; color:#1e293b; font-size:1.7rem; letter-spacing:-0.5px; }
        .subtitle { color:#64748b; margin:0 0 24px; font-size:0.95rem; }
        label { display:block; font-weight:700; color:#1e293b; margin:16px 0 8px; font-size:0.9rem; }
        input[type=text], input[type=tel] { width:100%; padding:14px 16px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-size:1rem; font-weight:600; }
        input[type=text]:focus, input[type=tel]:focus { outline:none; border-color:#4f46e5; background:white; }
        .consent { margin:24px 0 20px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; display:flex; gap:12px; align-items:flex-start; }
        .consent input { margin-top:4px; }
        .consent label { margin:0; font-weight:500; font-size:0.85rem; color:#334155; }
        button { width:100%; padding:16px; background:linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color:white; border:none; border-radius:14px; font-size:1rem; font-weight:800; letter-spacing:0.3px; cursor:pointer; }
        button:disabled { background:#cbd5e1; cursor:not-allowed; }
        .err { background:#fef2f2; color:#b91c1c; padding:14px; border-radius:12px; margin-bottom:16px; font-size:0.9rem; font-weight:600; }
        .badge { display:inline-block; padding:4px 12px; background:#eef2ff; color:#4338ca; border-radius:20px; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom:14px; }
    </style>
</head>
<body>
<div class="wrap">
    <span class="badge">Lista de Presença</span>
    <h1>{{ $session->title }}</h1>
    <p class="subtitle">{{ $session->date->format('d/m/Y') }}
        @if($session->start_time) — {{ substr($session->start_time,0,5) }}@endif
    </p>

    @if ($errors->any())
        <div class="err">
            @foreach ($errors->all() as $e) {{ $e }}<br>@endforeach
        </div>
    @endif

    <form method="POST" action="/chamada/{{ $token }}/checkin" id="ck-form">
        @csrf

        <label for="name">Seu nome completo</label>
        <input type="text" id="name" name="name" required maxlength="120"
               autocomplete="off"
               @if($session->mode === 'fechada') list="students-list" @endif
               placeholder="Digite seu nome" value="{{ old('name') }}">

        @if($session->mode === 'fechada')
            <datalist id="students-list">
                @foreach($students as $n)<option value="{{ $n }}">@endforeach
            </datalist>
        @endif

        @if($session->mode === 'aberta')
            <label for="phone">Telefone (com DDD)</label>
            <input type="tel" id="phone" name="phone" maxlength="30"
                   placeholder="(11) 91234-5678" value="{{ old('phone') }}">
        @endif

        <div class="consent">
            <input type="checkbox" id="consent" name="consent" value="1" required onchange="document.getElementById('btn').disabled = !this.checked;">
            <label for="consent">Autorizo o registro do meu nome e presença nesta atividade, conforme LGPD. Os dados serão usados apenas para controle acadêmico da instituição.</label>
        </div>

        <button type="submit" id="btn" disabled>
            <i class="fas fa-check-circle"></i>&nbsp; Marcar Presença
        </button>
    </form>
</div>
</body>
</html>
