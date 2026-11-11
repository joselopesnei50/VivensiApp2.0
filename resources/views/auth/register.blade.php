{{--
    Cadastro público — exclusivo do Terceiro Setor (ONG, associação, instituto, fundação).
    Mesmo layout da tela de login: painel escuro à esquerda, formulário branco à direita.
    $plan  = plano escolhido (link ?plan_id= ou o único plano disponível)
    $plans = planos pagos de ONG ativos (para escolher quando não veio no link)
--}}
@php
    $plans = $plans ?? collect();
    $whatsapp = 'https://wa.me/5516997618695?text=' . rawurlencode('Olá! Quero cadastrar a minha organização na Vivensi.');
    $oldPlan = old('plan_id', $plan->id ?? null);
    $semPlano = !$plan && $plans->isEmpty();
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi — Criar conta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --black:    #080808;
            --border-d: rgba(255,255,255,.07);
            --white:    #ffffff;
            --muted:    #6b7280;
            --accent:   #4F6EF7;
        }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--black);
            color: var(--white);
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ── PAINEL ESQUERDO (igual ao login) ─────────────────────────── */
        .panel-left {
            flex: 1; position: relative;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 48px 56px; overflow: hidden; background: var(--black);
        }
        .orb { position: absolute; border-radius: 50%; filter: blur(100px); pointer-events: none; }
        .orb-1 { width:500px;height:500px;background:radial-gradient(circle,rgba(79,110,247,.18) 0%,transparent 70%);top:-100px;left:-100px; }
        .orb-2 { width:400px;height:400px;background:radial-gradient(circle,rgba(123,92,240,.14) 0%,transparent 70%);bottom:-80px;right:-80px; }
        .grid-overlay {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px; pointer-events: none;
        }
        .left-top, .left-center, .left-bottom { position: relative; z-index: 2; }
        .left-top { display: flex; align-items: center; justify-content: space-between; }
        .left-logo img { height: 32px; display: block; }
        .left-back {
            font-size: .8rem; font-weight: 600; color: rgba(255,255,255,.55); text-decoration: none;
            padding: 8px 14px; border: 1px solid rgba(255,255,255,.12); border-radius: 100px; transition: color .2s, border-color .2s;
        }
        .left-back:hover { color: #fff; border-color: rgba(255,255,255,.3); }
        .left-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--accent); font-size: .75rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase; margin-bottom: 28px;
        }
        .eyebrow-dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }
        .left-headline {
            font-size: clamp(2.6rem, 3.8vw, 4rem);
            font-weight: 900; line-height: 1.06; letter-spacing: -.04em; margin-bottom: 24px;
        }
        .word-light  { color: var(--white); }
        .word-dim    { color: rgba(255,255,255,.42); }
        .word-accent {
            background: linear-gradient(135deg, #4F6EF7, #A78BFA);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .left-sub { font-size: 1rem; color: rgba(255,255,255,.55); line-height: 1.7; max-width: 440px; margin-bottom: 36px; }

        /* passos do cadastro (sequência real) */
        .steps { list-style: none; counter-reset: st; max-width: 440px; }
        .steps li {
            counter-increment: st; position: relative;
            padding: 0 0 22px 46px; font-size: .9rem; line-height: 1.55; color: rgba(255,255,255,.55);
        }
        .steps li::before {
            content: counter(st); position: absolute; left: 0; top: -3px;
            width: 30px; height: 30px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.04);
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; color: #fff;
        }
        .steps li:first-child::before { background: var(--accent); border-color: var(--accent); }
        .steps li strong { display: block; color: #fff; font-weight: 700; font-size: .95rem; }

        .left-bottom { display: flex; align-items: center; justify-content: space-between; }
        .left-footer-links { display: flex; gap: 20px; }
        .left-footer-links a { font-size: .72rem; color: rgba(255,255,255,.3); text-decoration: none; font-weight: 500; transition: color .2s; }
        .left-footer-links a:hover { color: rgba(255,255,255,.6); }
        .left-footer-copy { font-size: .72rem; color: rgba(255,255,255,.2); font-weight: 500; }

        /* ── PAINEL DIREITO (formulário, igual ao login) ──────────────── */
        .panel-right {
            width: 520px; flex-shrink: 0; background: var(--white); color: #111827;
            height: 100vh; overflow-y: auto; display: flex;
        }
        .form-wrap { width: 100%; margin: auto 0; padding: 40px 52px; }
        .right-logo { margin-bottom: 22px; }
        .right-logo img { height: 28px; display: block; }
        .form-eyebrow { font-size: .72rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--accent); margin-bottom: 6px; }
        .form-title { font-size: 1.55rem; font-weight: 800; color: #0f0f0f; letter-spacing: -.03em; line-height: 1.2; margin-bottom: 4px; }
        .form-subtitle { font-size: .86rem; color: var(--muted); margin-bottom: 22px; line-height: 1.5; }

        .alert { display: flex; gap: 10px; padding: 11px 14px; border-radius: 10px; font-size: .83rem; font-weight: 500; margin-bottom: 16px; line-height: 1.45; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert ul { margin: 0; padding-left: 16px; }

        .field { margin-bottom: 14px; }
        .field label, .field-label { display: block; font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .field-wrap { position: relative; }
        .field-wrap input {
            width: 100%; padding: 11px 16px;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            font-size: .95rem; font-family: inherit; color: #111827; background: #fafafa;
            transition: border-color .2s, box-shadow .2s, background .2s; outline: none; -webkit-appearance: none;
        }
        .field-wrap input:focus { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(79,110,247,.1); }
        .field-wrap input::placeholder { color: #9ca3af; }
        .field-wrap input[type="password"], .field-wrap input.pw { padding-right: 44px; }
        .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px; display: flex;
        }
        .toggle-pw:hover { color: #374151; }
        .toggle-pw:focus-visible, .btn-login:focus-visible, .plan-opt input:focus-visible + .plan-box { outline: 2px solid var(--accent); outline-offset: 2px; }
        .field-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* plano */
        .plan-current {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; background: #fafafa;
        }
        .plan-current-name { font-weight: 700; font-size: .95rem; color: #0f0f0f; }
        .plan-current-sub { font-size: .78rem; color: var(--muted); margin-top: 2px; }
        .plan-current-price { font-weight: 800; font-size: 1.05rem; color: #0f0f0f; white-space: nowrap; text-align: right; }
        .plan-current-price small { display: block; font-size: .72rem; font-weight: 500; color: var(--muted); }
        .plan-change { font-size: .78rem; color: var(--accent); font-weight: 600; text-decoration: none; }
        .plan-list { display: grid; gap: 8px; margin-bottom: 18px; }
        .plan-opt { display: block; cursor: pointer; }
        .plan-opt input { position: absolute; opacity: 0; pointer-events: none; }
        .plan-box {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; background: #fafafa;
            transition: border-color .15s, background .15s;
        }
        .plan-opt:hover .plan-box { border-color: #c7d2fe; }
        .plan-opt input:checked + .plan-box { border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(79,110,247,.08); }
        .plan-radio { width: 16px; height: 16px; border-radius: 50%; border: 1.5px solid #d1d5db; flex: none; margin-right: 10px; position: relative; }
        .plan-opt input:checked + .plan-box .plan-radio { border-color: var(--accent); }
        .plan-opt input:checked + .plan-box .plan-radio::after { content: ''; position: absolute; inset: 3px; border-radius: 50%; background: var(--accent); }
        .plan-left { display: flex; align-items: center; min-width: 0; }
        .plan-name { font-weight: 700; font-size: .92rem; color: #0f0f0f; }
        .plan-price { font-weight: 800; font-size: .95rem; color: #0f0f0f; white-space: nowrap; }
        .plan-price small { font-weight: 500; font-size: .72rem; color: var(--muted); }
        .no-plan { border: 1.5px dashed #d1d5db; border-radius: 12px; padding: 14px 16px; font-size: .86rem; color: #374151; line-height: 1.55; margin-bottom: 18px; }
        .no-plan a { color: var(--accent); font-weight: 700; }

        /* regras da senha */
        .pw-rules { display: flex; flex-wrap: wrap; gap: 6px 14px; margin: -4px 0 16px; }
        .rule { font-size: .74rem; color: #9ca3af; display: inline-flex; align-items: center; gap: 5px; }
        .rule i { font-size: .65rem; }
        .rule.ok { color: #15803d; }

        .terms { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 18px; cursor: pointer; }
        .terms input { width: 16px; height: 16px; margin-top: 2px; accent-color: var(--accent); flex: none; cursor: pointer; }
        .terms span { font-size: .82rem; color: #4b5563; line-height: 1.55; }
        .terms a { color: #0f0f0f; font-weight: 700; text-underline-offset: 2px; }

        .btn-login {
            width: 100%; padding: 13px 24px; background: #0f0f0f; color: #fff;
            border: none; border-radius: 12px; font-size: .95rem; font-weight: 700; font-family: inherit;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background .2s, transform .15s, box-shadow .2s; letter-spacing: -.01em;
        }
        .btn-login:hover { background: #1f1f1f; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,.18); }
        .btn-login:disabled { background: #9ca3af; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-login .arrow { width: 20px; height: 20px; border-radius: 50%; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; font-size: .7rem; }
        .after-note { font-size: .78rem; color: var(--muted); text-align: center; margin-top: 12px; line-height: 1.5; }

        .divider { display: flex; align-items: center; gap: 12px; margin: 18px 0 12px; color: #d1d5db; font-size: .78rem; font-weight: 600; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:#e5e7eb; }
        .login-row { text-align: center; font-size: .85rem; color: #9ca3af; }
        .login-row a { color: #0f0f0f; font-weight: 700; text-decoration: none; }
        .login-row a:hover { opacity: .6; }

        /* ── RESPONSIVO ───────────────────────────────────────────────── */
        @media (max-width: 1100px) { .panel-left { padding: 40px 40px; } .panel-right { width: 480px; } .form-wrap { padding: 36px 40px; } }
        @media (max-width: 900px) {
            html, body { height: auto; }
            body { overflow: visible; display: block; }
            .panel-left { display: none; }
            .panel-right { width: 100%; height: auto; min-height: 100vh; }
            .form-wrap { padding: 32px 22px 40px; max-width: 520px; margin: 0 auto; }
        }
        @media (max-width: 420px) { .field-pair { grid-template-columns: 1fr; gap: 0; } }
        @media (prefers-reduced-motion: reduce) { .btn-login, .btn-login:hover { transition: none; transform: none; } }
    </style>
</head>
<body>

<!-- PAINEL ESQUERDO -->
<div class="panel-left">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="grid-overlay"></div>

    <div class="left-top">
        <a href="{{ url('/') }}" class="left-logo"><img src="{{ asset('img/novalogo.png') }}" alt="Vivensi"></a>
        <a href="{{ url('/') }}" class="left-back"><i class="fas fa-arrow-left" style="font-size:.7rem"></i> Voltar ao site</a>
    </div>

    <div class="left-center">
        <div class="left-eyebrow"><span class="eyebrow-dot"></span> Cadastro para o Terceiro Setor</div>
        <h1 class="left-headline">
            <span class="word-light">Sua ONG</span><br>
            <span class="word-accent">organizada</span><br>
            <span class="word-dim">a partir de hoje.</span>
        </h1>
        <p class="left-sub">
            Beneficiários, turmas, prestação de contas e transparência no mesmo lugar — feito para ONGs, associações, institutos e fundações.
        </p>

        <ol class="steps">
            <li><strong>Crie a conta da organização</strong>Leva poucos minutos.</li>
            <li><strong>Assine o contrato de adesão</strong>Tudo online, com os dados da entidade.</li>
            <li><strong>Pague por PIX e comece a usar</strong>O acesso é liberado assim que o pagamento é confirmado.</li>
        </ol>
    </div>

    <div class="left-bottom">
        <div class="left-footer-links">
            @if(\Illuminate\Support\Facades\Route::has('legal.privacy'))<a href="{{ route('legal.privacy') }}">Privacidade</a>@endif
            @if(\Illuminate\Support\Facades\Route::has('legal.terms'))<a href="{{ route('legal.terms') }}">Termos</a>@endif
            <a href="{{ $whatsapp }}" target="_blank" rel="noopener">Suporte</a>
        </div>
        <span class="left-footer-copy">© {{ date('Y') }} Vivensi</span>
    </div>
</div>

<!-- PAINEL DIREITO -->
<main class="panel-right">
    <div class="form-wrap">
        <div class="right-logo"><img src="{{ asset('img/novalogo.png') }}" alt="Vivensi"></div>

        <div class="form-eyebrow">Criar conta</div>
        <h2 class="form-title">Cadastre sua organização</h2>
        <p class="form-subtitle">Para ONGs, associações, institutos e fundações.</p>

        @if(session('error'))
            <div class="alert alert-error"><i class="fas fa-circle-exclamation" style="margin-top:2px"></i><div>{{ session('error') }}</div></div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation" style="margin-top:2px"></i>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ url('/register') }}" method="POST" id="registerForm" novalidate>
            @csrf

            {{-- Plano --}}
            @if($plan)
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <span class="field-label">Plano</span>
                <div class="plan-current">
                    <div>
                        <div class="plan-current-name">{{ $plan->name }}</div>
                        @if($plans->count() > 1 && !$plan->is_courtesy)
                            <a href="{{ route('register') }}" class="plan-change">Trocar plano</a>
                        @else
                            <div class="plan-current-sub">Terceiro Setor</div>
                        @endif
                    </div>
                    <div class="plan-current-price">
                        @if($plan->is_courtesy)
                            Cortesia
                        @else
                            R$ {{ number_format((float) $plan->price, 2, ',', '.') }}<small>por mês</small>
                        @endif
                    </div>
                </div>
            @elseif($plans->isNotEmpty())
                <span class="field-label" id="plan-label">Escolha o plano</span>
                <div class="plan-list" role="radiogroup" aria-labelledby="plan-label">
                    @foreach($plans as $p)
                        <label class="plan-opt">
                            <input type="radio" name="plan_id" value="{{ $p->id }}" {{ (string) $oldPlan === (string) $p->id ? 'checked' : '' }} required>
                            <span class="plan-box">
                                <span class="plan-left"><span class="plan-radio" aria-hidden="true"></span><span class="plan-name">{{ $p->name }}</span></span>
                                <span class="plan-price">R$ {{ number_format((float) $p->price, 2, ',', '.') }} <small>/mês</small></span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="no-plan">
                    No momento o cadastro de novas organizações é feito com a nossa equipe.
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener">Fale com a gente pelo WhatsApp</a>.
                </div>
            @endif

            <div class="field">
                <label for="organization_name">Nome da organização</label>
                <div class="field-wrap"><input type="text" id="organization_name" name="organization_name" placeholder="Ex.: Instituto Semente" required value="{{ old('organization_name') }}" autocomplete="organization"></div>
            </div>

            <div class="field">
                <label for="name">Seu nome</label>
                <div class="field-wrap"><input type="text" id="name" name="name" placeholder="Nome e sobrenome" required value="{{ old('name') }}" autocomplete="name"></div>
            </div>

            <div class="field">
                <label for="email">E-mail de acesso</label>
                <div class="field-wrap"><input type="email" id="email" name="email" placeholder="voce@suaong.org.br" required value="{{ old('email') }}" autocomplete="email"></div>
            </div>

            <div class="field-pair">
                <div class="field">
                    <label for="password">Senha</label>
                    <div class="field-wrap">
                        <input type="password" class="pw" id="password" name="password" placeholder="Crie uma senha" required autocomplete="new-password">
                        <button type="button" class="toggle-pw" data-target="password" aria-label="Mostrar senha"><i class="far fa-eye"></i></button>
                    </div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar senha</label>
                    <div class="field-wrap">
                        <input type="password" class="pw" id="password_confirmation" name="password_confirmation" placeholder="Repita a senha" required autocomplete="new-password">
                        <button type="button" class="toggle-pw" data-target="password_confirmation" aria-label="Mostrar senha"><i class="far fa-eye"></i></button>
                    </div>
                </div>
            </div>
            <div class="pw-rules" aria-live="polite">
                <span class="rule" data-rule="length"><i class="fas fa-circle"></i> 12 caracteres</span>
                <span class="rule" data-rule="upper"><i class="fas fa-circle"></i> letra maiúscula</span>
                <span class="rule" data-rule="lower"><i class="fas fa-circle"></i> letra minúscula</span>
                <span class="rule" data-rule="number"><i class="fas fa-circle"></i> número</span>
            </div>

            <label class="terms">
                <input type="checkbox" name="terms" required {{ old('terms') ? 'checked' : '' }}>
                <span>
                    Li e concordo com os <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Termos de Uso</a>
                    e a <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
                </span>
            </label>

            <button type="submit" class="btn-login" id="submitBtn" {{ $semPlano ? 'disabled' : '' }}>
                <span class="submit-label">Criar conta</span>
                <span class="arrow"><i class="fas fa-arrow-right"></i></span>
            </button>
            <p class="after-note">Em seguida você assina o contrato de adesão e paga por PIX. Sem fidelidade.</p>
        </form>

        <div class="divider">ou</div>
        <div class="login-row">Já tem conta? <a href="{{ route('login') }}">Entrar</a></div>
    </div>
</main>

<script>
document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(this.dataset.target);
        var icon = this.querySelector('i');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
        this.setAttribute('aria-label', show ? 'Esconder senha' : 'Mostrar senha');
    });
});

var pw = document.getElementById('password');
pw.addEventListener('input', function () {
    var v = this.value, ok = { length: v.length >= 12, upper: /[A-Z]/.test(v), lower: /[a-z]/.test(v), number: /[0-9]/.test(v) };
    document.querySelectorAll('.rule').forEach(function (r) {
        var good = ok[r.dataset.rule];
        r.classList.toggle('ok', good);
        r.querySelector('i').className = good ? 'fas fa-check-circle' : 'fas fa-circle';
    });
});

document.getElementById('registerForm').addEventListener('submit', function (e) {
    var radios = this.querySelectorAll('input[name="plan_id"][type="radio"]');
    if (radios.length && !this.querySelector('input[name="plan_id"]:checked')) {
        e.preventDefault();
        var lbl = document.getElementById('plan-label');
        lbl.textContent = 'Escolha o plano para continuar';
        lbl.style.color = '#b91c1c';
        lbl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    if (!this.checkValidity()) { e.preventDefault(); this.reportValidity(); return; }
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando a conta...';
});
</script>

</body>
</html>
