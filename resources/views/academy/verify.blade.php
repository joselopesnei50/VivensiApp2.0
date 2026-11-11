{{--
    Verificação pública de certificado da Vivensi Academy (sem login).
    Mostra só nome, curso, carga horária e data — nada de e-mail ou outros dados.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Verificar certificado — Vivensi Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #080808; color: #fff; min-height: 100vh; position: relative; }
        .grid { position: fixed; inset: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px; }
        .orb { position: fixed; width: 520px; height: 520px; border-radius: 50%; filter: blur(110px); pointer-events: none;
            background: radial-gradient(circle, rgba(79,110,247,.2) 0%, transparent 70%); top: -160px; left: -120px; }
        .wrap { position: relative; z-index: 1; max-width: 620px; margin: 0 auto; padding: 48px 20px 64px; }
        .top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 48px; }
        .top img { height: 30px; display: block; }
        .top a.back { font-size: .8rem; font-weight: 600; color: rgba(255,255,255,.55); text-decoration: none; border: 1px solid rgba(255,255,255,.12); padding: 8px 14px; border-radius: 100px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; color: #7d93ff; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 14px; }
        .eyebrow i { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; display: block; }
        h1 { font-size: clamp(2rem, 6vw, 2.8rem); font-weight: 900; letter-spacing: -.04em; line-height: 1.05; margin-bottom: 12px; }
        .sub { color: rgba(255,255,255,.55); line-height: 1.6; margin-bottom: 28px; font-size: .95rem; }
        form { display: flex; gap: 10px; margin-bottom: 28px; }
        input { flex: 1; min-width: 0; padding: 14px 16px; border-radius: 12px; border: 1.5px solid rgba(255,255,255,.14); background: rgba(255,255,255,.04);
            color: #fff; font-family: inherit; font-size: 1rem; letter-spacing: .08em; text-transform: uppercase; outline: none; }
        input:focus { border-color: #4F6EF7; box-shadow: 0 0 0 4px rgba(79,110,247,.18); }
        input::placeholder { color: rgba(255,255,255,.3); letter-spacing: .04em; text-transform: none; }
        button { padding: 14px 22px; border-radius: 12px; border: none; background: #fff; color: #0a0a0a; font-weight: 800; font-family: inherit; font-size: .92rem; cursor: pointer; white-space: nowrap; }
        button:hover { background: #ececec; }
        .card { border-radius: 18px; overflow: hidden; border: 1px solid rgba(255,255,255,.1); background: #0e0f15; }
        .card-head { padding: 18px 24px; display: flex; align-items: center; gap: 12px; font-weight: 800; }
        .ok .card-head { background: rgba(34,197,94,.12); color: #4ade80; border-bottom: 1px solid rgba(34,197,94,.2); }
        .bad .card-head { background: rgba(239,68,68,.1); color: #f87171; border-bottom: 1px solid rgba(239,68,68,.2); }
        .card-body { padding: 22px 24px; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: .92rem; }
        .row:last-child { border-bottom: none; }
        .row span { color: rgba(255,255,255,.5); flex: none; }
        .row strong { text-align: right; font-weight: 700; }
        .bad p { color: rgba(255,255,255,.65); line-height: 1.6; font-size: .92rem; }
        .foot { margin-top: 28px; font-size: .78rem; color: rgba(255,255,255,.35); line-height: 1.6; }
        @media (max-width: 480px) { form { flex-direction: column; } .row { flex-direction: column; gap: 4px; } .row strong { text-align: left; } }
    </style>
</head>
<body>
<div class="grid"></div>
<div class="orb"></div>

<main class="wrap">
    <div class="top">
        <a href="{{ url('/') }}"><img src="{{ asset('img/novalogo.png') }}" alt="Vivensi"></a>
        <a href="{{ url('/') }}" class="back">vivensi.app.br</a>
    </div>

    <div class="eyebrow"><i></i> Vivensi Academy</div>
    <h1>Verificar certificado</h1>
    <p class="sub">Digite o código de validação que aparece no rodapé do certificado para confirmar se ele é autêntico.</p>

    <form action="{{ route('academy.verify') }}" method="GET">
        <input type="text" name="codigo" value="{{ $searched ? trim(chunk_split($code, 4, '-'), '-') : '' }}"
               placeholder="Ex.: ABCD-EFGH-IJKL-MNOP" autocomplete="off" maxlength="40" required aria-label="Código do certificado">
        <button type="submit">Verificar</button>
    </form>

    @if($searched && $certificate)
        <section class="card ok" aria-live="polite">
            <div class="card-head"><i class="fas fa-circle-check"></i> Certificado válido</div>
            <div class="card-body">
                <div class="row"><span>Aluno(a)</span><strong>{{ $certificate->studentName() }}</strong></div>
                <div class="row"><span>Curso</span><strong>{{ $certificate->courseTitle() }}</strong></div>
                @if($certificate->workloadLabel())
                    <div class="row"><span>Carga horária</span><strong>{{ $certificate->workloadLabel() }}</strong></div>
                @endif
                <div class="row"><span>Emitido em</span><strong>{{ $certificate->issued_at->format('d/m/Y') }}</strong></div>
                <div class="row"><span>Código</span><strong>{{ $certificate->formattedCode() }}</strong></div>
            </div>
        </section>
    @elseif($searched)
        <section class="card bad" aria-live="polite">
            <div class="card-head"><i class="fas fa-circle-xmark"></i> Certificado não encontrado</div>
            <div class="card-body">
                <p>Não existe certificado da Vivensi Academy com este código. Confira se digitou todas as letras e números — o código tem 16 caracteres.</p>
            </div>
        </section>
    @endif

    <p class="foot">Os certificados da Vivensi Academy são emitidos quando o aluno conclui todas as aulas do curso. Esta página mostra apenas os dados necessários para a conferência.</p>
</main>
</body>
</html>
