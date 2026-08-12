<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta — Vivensi</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <x-meta-pixel />
    <style>
        :root {
            --ink: #0f172a;
            --ink-soft: #334155;
            --muted: #64748b;
            --faint: #94a3b8;
            --border: #e2e8f0;
            --border-soft: #f1f5f9;
            --bg: #ffffff;
            --bg-soft: #f8fafc;
            --dark: #0a0e1a;
            --dark-2: #131830;
            --brand: #10b981;
            --brand-dark: #059669;
            --brand-bg: #ecfdf5;
            --danger: #dc2626;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; color: var(--ink); background: var(--bg); line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }

        /* ═══════════ Layout split ═══════════ */
        .split { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }
        @media (max-width: 980px) { .split { grid-template-columns: 1fr; } }

        /* ═══════════ Painel esquerdo (dark hero) ═══════════ */
        .brand-panel {
            background: var(--dark);
            color: #e2e8f0;
            padding: 44px 56px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }
        @media (max-width: 980px) { .brand-panel { padding: 32px 28px 40px; min-height: auto; } }

        /* Grade sutil de fundo — sem glow, so pattern silencioso */
        .brand-panel::before {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
        .brand-panel > * { position: relative; z-index: 1; }

        .brand-top { display: flex; justify-content: space-between; align-items: center; }
        .brand-top .logo-wrap { display: flex; align-items: center; gap: 12px; }
        .brand-top .logo-wrap img { height: 34px; width: auto; filter: brightness(0) invert(1); opacity: 0.95; }
        .brand-back { font-size: 0.82rem; color: #94a3b8; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 100px; border: 1px solid rgba(255,255,255,0.10); transition: all 0.15s; }
        .brand-back:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

        .brand-hero { margin: 56px 0 40px; max-width: 480px; }
        @media (max-width: 980px) { .brand-hero { margin: 36px 0 28px; } }

        .brand-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.72rem; font-weight: 800; color: var(--brand);
            letter-spacing: 1.4px; text-transform: uppercase;
            background: rgba(16,185,129,0.10);
            border: 1px solid rgba(16,185,129,0.22);
            padding: 6px 14px; border-radius: 100px;
            margin-bottom: 24px;
        }
        .brand-eyebrow::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--brand); display: inline-block; }

        .brand-title {
            font-size: 2.4rem; font-weight: 800;
            color: #fff; letter-spacing: -0.7px;
            margin: 0 0 18px; line-height: 1.1;
        }
        @media (max-width: 980px) { .brand-title { font-size: 1.85rem; } }
        .brand-title em { font-style: normal; color: var(--brand); }

        .brand-sub {
            font-size: 1.02rem; color: #cbd5e1;
            margin: 0; line-height: 1.65; font-weight: 400;
        }

        .brand-benefits {
            margin-top: 44px;
            display: flex; flex-direction: column; gap: 22px;
        }
        @media (max-width: 980px) { .brand-benefits { margin-top: 28px; gap: 16px; } }

        .benefit { display: flex; gap: 16px; align-items: flex-start; }
        .benefit-icon {
            flex-shrink: 0;
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            display: flex; align-items: center; justify-content: center;
            color: var(--brand); font-size: 1.05rem;
        }
        .benefit-body strong {
            display: block;
            color: #fff; font-weight: 700; font-size: 0.98rem;
            margin-bottom: 4px; letter-spacing: -0.2px;
        }
        .benefit-body span {
            display: block;
            color: #94a3b8; font-size: 0.87rem; line-height: 1.55;
        }

        .brand-trust {
            display: flex; gap: 24px; flex-wrap: wrap;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .trust-item { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: #94a3b8; font-weight: 600; }
        .trust-item i { color: var(--brand); font-size: 0.82rem; }

        /* ═══════════ Painel direito (form) ═══════════ */
        .form-panel {
            background: var(--bg);
            padding: 48px 56px;
            display: flex; align-items: center; justify-content: center;
        }
        @media (max-width: 980px) { .form-panel { padding: 36px 24px 48px; } }

        .form-wrap { width: 100%; max-width: 460px; }

        .form-mobile-logo { display: none; text-align: center; margin-bottom: 24px; }
        .form-mobile-logo img { height: 32px; width: auto; }
        @media (max-width: 980px) { .form-mobile-logo { display: block; } }

        .form-head { margin-bottom: 32px; }
        .form-title {
            font-size: 1.65rem; font-weight: 800;
            color: var(--ink); margin: 0 0 8px;
            letter-spacing: -0.5px; line-height: 1.15;
        }
        .form-sub { font-size: 0.93rem; color: var(--muted); margin: 0; line-height: 1.55; }

        /* Plan summary enriquecido */
        .plan-summary {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 22px;
            margin-bottom: 28px;
        }
        .plan-summary-top {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        }
        .plan-summary-label {
            font-size: 0.66rem; font-weight: 800;
            color: var(--brand-dark); text-transform: uppercase;
            letter-spacing: 1.3px; margin-bottom: 6px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .plan-summary-label i { font-size: 0.65rem; }
        .plan-summary-name { font-size: 1.1rem; font-weight: 800; color: var(--ink); letter-spacing: -0.3px; }
        .plan-summary-price { text-align: right; }
        .plan-summary-price strong {
            font-size: 1.3rem; font-weight: 900; color: var(--ink);
            letter-spacing: -0.6px; display: block;
        }
        .plan-summary-price small { font-size: 0.72rem; font-weight: 600; color: var(--muted); }
        .plan-summary-features {
            list-style: none; padding: 0; margin: 16px 0 0;
            display: flex; flex-direction: column; gap: 9px;
            border-top: 1px dashed var(--border); padding-top: 16px;
        }
        .plan-summary-features li {
            font-size: 0.87rem; color: var(--ink-soft);
            display: flex; gap: 10px; align-items: flex-start;
        }
        .plan-summary-features i {
            color: var(--brand); font-size: 0.78rem;
            margin-top: 4px; flex-shrink: 0;
        }

        /* Alertas */
        .alert {
            padding: 12px 14px; border-radius: 10px;
            font-size: 0.88rem; margin-bottom: 20px;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .alert-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }
        .alert ul { margin: 0; padding-left: 18px; }

        /* Role selector */
        .field-label {
            display: block; font-size: 0.82rem; font-weight: 700;
            color: var(--ink); margin-bottom: 10px;
        }
        .field-required { color: var(--danger); }
        .role-selector {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
            margin-bottom: 24px;
        }
        @media (max-width: 480px) { .role-selector { grid-template-columns: 1fr; } }
        .role-option {
            padding: 14px 14px; border: 1.5px solid var(--border);
            border-radius: 12px; cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: var(--bg);
        }
        .role-option:hover { border-color: var(--ink-soft); }
        .role-option.selected { border-color: var(--ink); background: var(--bg-soft); }
        .role-option-top { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .role-option-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--border-soft); color: var(--ink-soft);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; transition: background 0.15s, color 0.15s;
        }
        .role-option.selected .role-option-icon { background: var(--ink); color: #fff; }
        .role-option-title { font-weight: 700; font-size: 0.92rem; color: var(--ink); }
        .role-option-desc { font-size: 0.76rem; color: var(--muted); line-height: 1.4; }

        .role-locked {
            background: var(--brand-bg); border: 1px solid rgba(16,185,129,0.28);
            border-radius: 12px; padding: 12px 14px;
            display: flex; align-items: center; gap: 10px;
            font-size: 0.9rem; font-weight: 700; color: var(--brand-dark);
            margin-bottom: 24px;
        }
        .role-locked i { color: var(--brand); }

        /* Inputs */
        .field { margin-bottom: 18px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .field-row { grid-template-columns: 1fr; } }
        .input {
            width: 100%; padding: 12px 14px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-size: 0.94rem; font-family: inherit;
            color: var(--ink); background: var(--bg);
            transition: border-color 0.15s, background 0.15s;
        }
        .input:focus { outline: none; border-color: var(--ink); background: var(--bg-soft); }
        .input::placeholder { color: var(--faint); }

        .password-wrap { position: relative; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%); background: none; border: none;
            padding: 4px; cursor: pointer;
            color: var(--faint); font-size: 0.95rem;
        }
        .password-toggle:hover { color: var(--ink-soft); }

        /* Password rules feedback */
        .password-rules {
            margin-top: 10px;
            display: grid; grid-template-columns: 1fr 1fr; gap: 6px 14px;
        }
        .rule {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.75rem; color: var(--faint);
            transition: color 0.15s;
        }
        .rule.ok { color: var(--brand-dark); }
        .rule i { font-size: 0.72rem; }

        /* Terms */
        .terms {
            margin: 24px 0 24px;
            display: flex; align-items: flex-start; gap: 10px; cursor: pointer;
        }
        .terms input {
            width: 16px; height: 16px; margin-top: 3px;
            accent-color: var(--ink); cursor: pointer; flex-shrink: 0;
        }
        .terms-text { font-size: 0.85rem; color: var(--muted); line-height: 1.55; }
        .terms-text a { color: var(--ink); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }

        /* Submit */
        .submit {
            width: 100%; padding: 15px;
            background: var(--ink); color: #fff; border: none;
            border-radius: 12px; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; transition: background 0.15s;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            font-family: inherit; letter-spacing: -0.1px;
        }
        .submit:hover:not(:disabled) { background: #1f2937; }
        .submit:disabled { background: var(--muted); cursor: not-allowed; opacity: 0.85; }
        .submit .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%;
            animation: spin 0.7s linear infinite; display: none;
        }
        .submit.loading .spinner { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .form-footer-note {
            text-align: center; margin-top: 16px;
            font-size: 0.78rem; color: var(--muted); line-height: 1.5;
        }
        .form-footer-note i { color: var(--brand); margin-right: 4px; }

        .login-link {
            text-align: center; margin-top: 28px; padding-top: 24px;
            border-top: 1px solid var(--border-soft);
            font-size: 0.9rem; color: var(--muted);
        }
        .login-link a { color: var(--ink); font-weight: 700; }
        .login-link a:hover { text-decoration: underline; }

        .field-error {
            color: var(--danger); font-size: 0.8rem;
            margin-top: 6px; display: none;
        }
        .field-error.show { display: block; }
    </style>
</head>
<body>

<div class="split">

    {{-- ═════════════════ PAINEL ESQUERDO (brand + benefits) ═════════════════ --}}
    <aside class="brand-panel">
        <div class="brand-top">
            <a href="{{ url('/') }}" class="logo-wrap" aria-label="Voltar para Vivensi">
                <x-application-logo />
            </a>
            <a href="{{ url('/') }}" class="brand-back">
                <i class="fas fa-arrow-left"></i> Voltar ao site
            </a>
        </div>

        <div class="brand-hero">
            <span class="brand-eyebrow">Ecossistema pra Terceiro Setor</span>
            <h1 class="brand-title">A gestão feita <em>por dentro</em> do trabalho social.</h1>
            <p class="brand-sub">Doadores, projetos, WhatsApp Oficial, conformidade e IA — em um só painel, com preço em reais e suporte que responde em português.</p>

            <div class="brand-benefits">
                <div class="benefit">
                    <div class="benefit-icon"><i class="fab fa-whatsapp"></i></div>
                    <div class="benefit-body">
                        <strong>WhatsApp Oficial da Meta</strong>
                        <span>Chatbot com IA treinado pela sua organização, respondendo em segundos e sem risco de bloqueio.</span>
                    </div>
                </div>
                <div class="benefit">
                    <div class="benefit-icon"><i class="fas fa-brain"></i></div>
                    <div class="benefit-body">
                        <strong>Sala de Estratégia com 5 agentes de IA</strong>
                        <span>Um conselho executivo virtual analisa seus números reais e entrega a próxima ação com plano pronto no Kanban.</span>
                    </div>
                </div>
                <div class="benefit">
                    <div class="benefit-icon"><i class="fas fa-shield-halved"></i></div>
                    <div class="benefit-body">
                        <strong>Conformidade Contínua — CEBAS · SUAS · MROSC</strong>
                        <span>Checklist regulatório com alertas antes da fiscalização chegar e PDF pronto para o auditor.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="brand-trust">
            <div class="trust-item"><i class="fas fa-lock"></i> LGPD-first</div>
            <div class="trust-item"><i class="fas fa-shield-halved"></i> Dados cifrados</div>
            <div class="trust-item"><i class="fas fa-flag"></i> Preço em BRL</div>
            <div class="trust-item"><i class="fas fa-headset"></i> Suporte em português</div>
        </div>
    </aside>

    {{-- ═════════════════ PAINEL DIREITO (form) ═════════════════ --}}
    <main class="form-panel">
        <div class="form-wrap">
            <div class="form-mobile-logo">
                <a href="{{ url('/') }}"><x-application-logo /></a>
            </div>

            <div class="form-head">
                <h2 class="form-title">Criar sua conta</h2>
                <p class="form-sub">Preencha os dados abaixo. Você vai direto para o pagamento seguro em seguida.</p>
            </div>

            @if($plan)
            <div class="plan-summary">
                <div class="plan-summary-top">
                    <div>
                        <div class="plan-summary-label"><i class="fas fa-check-circle"></i> Plano selecionado</div>
                        <div class="plan-summary-name">{{ $plan->name }}</div>
                    </div>
                    <div class="plan-summary-price">
                        <strong>R$ {{ number_format($plan->price, 2, ',', '.') }}</strong>
                        <small>por mês · sem fidelidade</small>
                    </div>
                </div>
                @php
                    $planFeatures = [];
                    if (!empty($plan->features)) {
                        $raw = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?: []);
                        $planFeatures = array_slice(array_filter($raw), 0, 4);
                    }
                    if (empty($planFeatures)) {
                        $planFeatures = match($plan->target_audience ?? '') {
                            'ngo'     => ['WhatsApp Oficial da Meta + Chatbot IA', 'Conformidade CEBAS · SUAS · MROSC', 'Sala de Estratégia com 5 agentes de IA', 'Portal do Doador + Radar de Editais'],
                            'manager' => ['WhatsApp Oficial + Kanban integrado', 'Central de Aprovações e Contratos Digitais', 'IA nativa em todo o sistema', 'Prospecção IA e Landing Pages'],
                            default   => ['WhatsApp Oficial da Meta + Chatbot IA', 'Termômetro do Teto MEI + DAS mensal', 'Recibos digitais e NFS-e', 'Sala de Estratégia com 5 agentes de IA'],
                        };
                    }
                @endphp
                <ul class="plan-summary-features">
                    @foreach($planFeatures as $feat)
                        <li><i class="fas fa-check"></i> <span>{{ is_array($feat) ? ($feat['name'] ?? '') : $feat }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ url('/register') }}" method="POST" id="registerForm">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan ? $plan->id : '' }}">

                @if($plan)
                    @php
                        $lockedRole = match($plan->target_audience ?? '') {
                            'ngo'     => ['ngo_admin',      'Terceiro Setor · ONG / Instituto / Fundação', 'hands-helping'],
                            'manager' => ['project_manager','Gestor de Projetos / PME',                    'briefcase'],
                            default   => ['client',         'MEI / Autônomo / Pessoa Comum',               'user'],
                        };
                    @endphp
                    <label class="field-label">Tipo de conta</label>
                    <div class="role-locked">
                        <i class="fas fa-{{ $lockedRole[2] }}"></i>
                        <span>{{ $lockedRole[1] }}</span>
                    </div>
                    <input type="hidden" name="account_type" id="account_type" value="{{ $lockedRole[0] }}">
                @else
                    <label class="field-label">O que você faz? <span class="field-required">*</span></label>
                    <div class="role-selector">
                        <div class="role-option" data-role="ngo_admin">
                            <div class="role-option-top">
                                <div class="role-option-icon"><i class="fas fa-hands-helping"></i></div>
                                <div class="role-option-title">Terceiro Setor</div>
                            </div>
                            <div class="role-option-desc">ONG, associação, instituto, fundação</div>
                        </div>
                        <div class="role-option" data-role="project_manager">
                            <div class="role-option-top">
                                <div class="role-option-icon"><i class="fas fa-briefcase"></i></div>
                                <div class="role-option-title">Gestor / PME</div>
                            </div>
                            <div class="role-option-desc">Empresa, projeto, equipe</div>
                        </div>
                        <div class="role-option" data-role="client" style="grid-column: 1 / -1;">
                            <div class="role-option-top">
                                <div class="role-option-icon"><i class="fas fa-user"></i></div>
                                <div class="role-option-title">MEI / Autônomo / Pessoa Comum</div>
                            </div>
                            <div class="role-option-desc">Uso individual, freelancer, pequeno negócio</div>
                        </div>
                    </div>
                    <input type="hidden" name="account_type" id="account_type" value="" required>
                    <div class="field-error" id="role-error">Selecione um tipo de conta.</div>
                @endif

                <div class="field">
                    <label class="field-label" for="organization_name">Nome da organização</label>
                    <input type="text" id="organization_name" name="organization_name" class="input" placeholder="Ex: Instituto Semente" required value="{{ old('organization_name') }}" autocomplete="organization">
                </div>

                <div class="field">
                    <label class="field-label" for="name">Seu nome completo</label>
                    <input type="text" id="name" name="name" class="input" placeholder="Como você quer ser chamado" required value="{{ old('name') }}" autocomplete="name">
                </div>

                <div class="field">
                    <label class="field-label" for="email">E-mail de acesso</label>
                    <input type="email" id="email" name="email" class="input" placeholder="voce@email.com" required value="{{ old('email') }}" autocomplete="email">
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="field-label" for="password">Senha</label>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password" class="input" placeholder="Min. 12 caracteres" required autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field-label" for="password_confirmation">Confirmar senha</label>
                        <div class="password-wrap">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="input" placeholder="Repita a senha" required autocomplete="new-password">
                            <button type="button" class="password-toggle" data-target="password_confirmation" aria-label="Mostrar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="password-rules" aria-live="polite">
                    <div class="rule" data-rule="length"><i class="fas fa-circle"></i> Mínimo 12 caracteres</div>
                    <div class="rule" data-rule="upper"><i class="fas fa-circle"></i> 1 letra maiúscula</div>
                    <div class="rule" data-rule="lower"><i class="fas fa-circle"></i> 1 letra minúscula</div>
                    <div class="rule" data-rule="number"><i class="fas fa-circle"></i> 1 número</div>
                </div>

                <label class="terms">
                    <input type="checkbox" name="terms" required>
                    <span class="terms-text">
                        Declaro que li e concordo com os
                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Termos de Uso</a>
                        e a
                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>
                        do Vivensi.
                    </span>
                </label>

                <button type="submit" class="submit" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="submit-label">Criar conta e ir para o pagamento</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <div class="form-footer-note">
                    <i class="fas fa-lock"></i> Pagamento seguro via Pix. Sem fidelidade. Cancele quando quiser.
                </div>
            </form>

            <div class="login-link">
                Já tem conta? <a href="{{ route('login') }}">Fazer login</a>
            </div>
        </div>
    </main>

</div>

<script>
// ─── Role selection ──────────────────────────────────────────
document.querySelectorAll('.role-option').forEach(opt => {
    opt.addEventListener('click', function () {
        document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('account_type').value = this.dataset.role;
        const err = document.getElementById('role-error');
        if (err) err.classList.remove('show');
    });
});

// ─── Password visibility toggle ──────────────────────────────
document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.target);
        const icon = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
        }
    });
});

// ─── Password rules live feedback ────────────────────────────
const pwInput = document.getElementById('password');
const rules = document.querySelectorAll('.rule');
pwInput.addEventListener('input', function () {
    const v = this.value;
    const checks = {
        length: v.length >= 12,
        upper:  /[A-Z]/.test(v),
        lower:  /[a-z]/.test(v),
        number: /[0-9]/.test(v),
    };
    rules.forEach(r => {
        const key = r.dataset.rule;
        const icon = r.querySelector('i');
        if (checks[key]) {
            r.classList.add('ok');
            icon.classList.remove('fa-circle'); icon.classList.add('fa-check-circle');
        } else {
            r.classList.remove('ok');
            icon.classList.remove('fa-check-circle'); icon.classList.add('fa-circle');
        }
    });
});

// ─── Submit: validação + loading state ───────────────────────
document.getElementById('registerForm').addEventListener('submit', function (e) {
    const accountType = document.getElementById('account_type').value;
    if (!accountType) {
        e.preventDefault();
        const err = document.getElementById('role-error');
        if (err) { err.classList.add('show'); err.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        return false;
    }
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.disabled = true;
    btn.querySelector('.submit-label').textContent = 'Criando sua conta...';
});
</script>

</body>
</html>
