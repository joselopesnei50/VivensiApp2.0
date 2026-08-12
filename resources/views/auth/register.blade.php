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

        /* ─────────── Layout split ─────────── */
        .split { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
        @media (max-width: 900px) { .split { grid-template-columns: 1fr; } }

        /* ─────────── Painel esquerdo (dark, brand) ─────────── */
        .brand-panel { background: var(--dark); color: #e2e8f0; padding: 56px 48px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }
        @media (max-width: 900px) { .brand-panel { padding: 36px 28px 44px; } }

        .brand-top { display: flex; align-items: center; gap: 12px; }
        .brand-mark { width: 40px; height: 40px; border-radius: 10px; background: #fff; color: var(--dark); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.05rem; letter-spacing: -0.5px; }
        .brand-name { font-weight: 800; font-size: 1.05rem; color: #fff; letter-spacing: -0.3px; }

        .brand-hero { margin: 48px 0; }
        @media (max-width: 900px) { .brand-hero { margin: 32px 0 24px; } }
        .brand-eyebrow { display: inline-block; font-size: 0.72rem; font-weight: 800; color: var(--brand); letter-spacing: 1.4px; text-transform: uppercase; background: rgba(16,185,129,0.10); border: 1px solid rgba(16,185,129,0.25); padding: 5px 12px; border-radius: 100px; margin-bottom: 20px; }
        .brand-title { font-size: 2rem; font-weight: 800; color: #fff; letter-spacing: -0.6px; margin: 0 0 16px; line-height: 1.15; }
        @media (max-width: 900px) { .brand-title { font-size: 1.6rem; } }
        .brand-sub { font-size: 1rem; color: #94a3b8; margin: 0; line-height: 1.6; max-width: 460px; }

        .brand-benefits { margin-top: 40px; display: flex; flex-direction: column; gap: 18px; }
        @media (max-width: 900px) { .brand-benefits { margin-top: 24px; gap: 14px; } }
        .benefit { display: flex; gap: 14px; align-items: flex-start; }
        .benefit-icon { flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); display: flex; align-items: center; justify-content: center; color: var(--brand); font-size: 1rem; }
        .benefit-text { color: #cbd5e1; font-size: 0.92rem; line-height: 1.5; }
        .benefit-text strong { color: #fff; font-weight: 700; }

        .brand-trust { display: flex; gap: 22px; flex-wrap: wrap; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.08); }
        .trust-item { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: #94a3b8; font-weight: 600; }
        .trust-item i { color: var(--brand); font-size: 0.85rem; }

        /* ─────────── Painel direito (form) ─────────── */
        .form-panel { background: var(--bg); padding: 56px 48px; display: flex; align-items: center; justify-content: center; }
        @media (max-width: 900px) { .form-panel { padding: 36px 24px; } }

        .form-wrap { width: 100%; max-width: 460px; }

        .form-head { margin-bottom: 28px; }
        .form-title { font-size: 1.5rem; font-weight: 800; color: var(--ink); margin: 0 0 6px; letter-spacing: -0.4px; }
        .form-sub { font-size: 0.92rem; color: var(--muted); margin: 0; }

        /* Plan summary enriquecido */
        .plan-summary { background: var(--bg-soft); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; margin-bottom: 28px; }
        .plan-summary-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .plan-summary-label { font-size: 0.68rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 4px; }
        .plan-summary-name { font-size: 1.05rem; font-weight: 800; color: var(--ink); letter-spacing: -0.3px; }
        .plan-summary-price { font-size: 1.15rem; font-weight: 900; color: var(--brand); white-space: nowrap; }
        .plan-summary-price small { font-size: 0.72rem; font-weight: 700; color: var(--muted); }
        .plan-summary-features { list-style: none; padding: 0; margin: 14px 0 0; display: flex; flex-direction: column; gap: 8px; border-top: 1px dashed var(--border); padding-top: 14px; }
        .plan-summary-features li { font-size: 0.85rem; color: var(--ink-soft); display: flex; gap: 10px; align-items: center; }
        .plan-summary-features i { color: var(--brand); font-size: 0.78rem; }

        /* Alertas */
        .alert { padding: 12px 14px; border-radius: 10px; font-size: 0.88rem; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; }
        .alert-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }
        .alert ul { margin: 0; padding-left: 18px; }

        /* Role selector */
        .field-label { display: block; font-size: 0.82rem; font-weight: 700; color: var(--ink); margin-bottom: 10px; }
        .field-required { color: var(--danger); }
        .role-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 22px; }
        @media (max-width: 480px) { .role-selector { grid-template-columns: 1fr; } }
        .role-option { padding: 16px 14px; border: 1.5px solid var(--border); border-radius: 12px; cursor: pointer; transition: border-color 0.15s, background 0.15s; background: var(--bg); }
        .role-option:hover { border-color: var(--ink-soft); }
        .role-option.selected { border-color: var(--ink); background: var(--bg-soft); }
        .role-option-top { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .role-option-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--border-soft); color: var(--ink-soft); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; transition: background 0.15s, color 0.15s; }
        .role-option.selected .role-option-icon { background: var(--ink); color: #fff; }
        .role-option-title { font-weight: 700; font-size: 0.92rem; color: var(--ink); }
        .role-option-desc { font-size: 0.76rem; color: var(--muted); line-height: 1.4; }

        /* Plano ja escolhido -> mostra tipo bloqueado */
        .role-locked { background: var(--bg-soft); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; color: var(--ink); margin-bottom: 22px; }
        .role-locked i { color: var(--brand); }

        /* Inputs */
        .field { margin-bottom: 18px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .field-row { grid-template-columns: 1fr; } }
        .input { width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: 10px; font-size: 0.94rem; font-family: inherit; color: var(--ink); background: var(--bg); transition: border-color 0.15s, background 0.15s; }
        .input:focus { outline: none; border-color: var(--ink); background: var(--bg-soft); }
        .input::placeholder { color: var(--faint); }

        .password-wrap { position: relative; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: var(--faint); font-size: 0.95rem; }
        .password-toggle:hover { color: var(--ink-soft); }

        /* Password rules feedback */
        .password-rules { margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px 14px; }
        .rule { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--faint); transition: color 0.15s; }
        .rule.ok { color: var(--brand-dark); }
        .rule i { font-size: 0.72rem; }

        /* Terms */
        .terms { margin: 22px 0 24px; display: flex; align-items: flex-start; gap: 10px; cursor: pointer; }
        .terms input { width: 16px; height: 16px; margin-top: 3px; accent-color: var(--ink); cursor: pointer; flex-shrink: 0; }
        .terms-text { font-size: 0.85rem; color: var(--muted); line-height: 1.5; }
        .terms-text a { color: var(--ink); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }

        /* Submit */
        .submit { width: 100%; padding: 14px; background: var(--ink); color: #fff; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: background 0.15s; display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-family: inherit; }
        .submit:hover:not(:disabled) { background: #1f2937; }
        .submit:disabled { background: var(--muted); cursor: not-allowed; opacity: 0.85; }
        .submit .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; display: none; }
        .submit.loading .spinner { display: inline-block; }
        .submit.loading .submit-label { opacity: 0.9; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .login-link { text-align: center; margin-top: 24px; font-size: 0.88rem; color: var(--muted); }
        .login-link a { color: var(--ink); font-weight: 700; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        .field-error { color: var(--danger); font-size: 0.8rem; margin-top: 6px; display: none; }
        .field-error.show { display: block; }
    </style>
</head>
<body>

<div class="split">

    {{-- ═════════════════ PAINEL ESQUERDO (brand + benefits) ═════════════════ --}}
    <aside class="brand-panel">
        <div class="brand-top">
            <div class="brand-mark">V</div>
            <div class="brand-name">Vivensi</div>
        </div>

        <div class="brand-hero">
            <span class="brand-eyebrow">Terceiro Setor · MEI · PME</span>
            <h1 class="brand-title">O ERP brasileiro pra quem gere organização, não planilha.</h1>
            <p class="brand-sub">WhatsApp Oficial, IA nativa e conformidade CEBAS/SUAS/MROSC no mesmo painel. Sem trial, sem burocracia — 20 minutos e você já está operando.</p>

            <div class="brand-benefits">
                <div class="benefit">
                    <div class="benefit-icon"><i class="fab fa-whatsapp"></i></div>
                    <div class="benefit-text"><strong>WhatsApp Oficial Meta + Evolution API</strong><br>Duas opções integradas, sem addon. Chatbot IA treinável pelo próprio cliente.</div>
                </div>
                <div class="benefit">
                    <div class="benefit-icon"><i class="fas fa-brain"></i></div>
                    <div class="benefit-text"><strong>Sala de Estratégia com 5 agentes de IA</strong><br>Conselho executivo virtual que estuda dados reais e entrega ação prioritária.</div>
                </div>
                <div class="benefit">
                    <div class="benefit-icon"><i class="fas fa-shield-halved"></i></div>
                    <div class="benefit-text"><strong>Conformidade Contínua CEBAS · SUAS · MROSC</strong><br>Checklist regulatório com alertas antes da fiscalização chegar.</div>
                </div>
            </div>
        </div>

        <div class="brand-trust">
            <div class="trust-item"><i class="fas fa-lock"></i> LGPD-first</div>
            <div class="trust-item"><i class="fas fa-shield-halved"></i> SSL · Dados cifrados</div>
            <div class="trust-item"><i class="fas fa-flag"></i> Preço em BRL</div>
        </div>
    </aside>

    {{-- ═════════════════ PAINEL DIREITO (form) ═════════════════ --}}
    <main class="form-panel">
        <div class="form-wrap">
            <div class="form-head">
                <h2 class="form-title">Criar sua conta</h2>
                <p class="form-sub">Preencha abaixo e comece em minutos.</p>
            </div>

            @if($plan)
            <div class="plan-summary">
                <div class="plan-summary-top">
                    <div>
                        <div class="plan-summary-label">Plano selecionado</div>
                        <div class="plan-summary-name">{{ $plan->name }}</div>
                    </div>
                    <div class="plan-summary-price">
                        R$ {{ number_format($plan->price, 2, ',', '.') }}<small>/mês</small>
                    </div>
                </div>
                @php
                    // Features do plano — tenta ler campo 'features' (json/array) ou 'description'.
                    // Fallback: 3 features padrao do painel do lead.
                    $planFeatures = [];
                    if (!empty($plan->features)) {
                        $raw = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?: []);
                        $planFeatures = array_slice(array_filter($raw), 0, 4);
                    }
                    if (empty($planFeatures)) {
                        $planFeatures = match($plan->target_audience ?? '') {
                            'ngo'     => ['WhatsApp Oficial + Chatbot IA', 'Conformidade CEBAS/SUAS/MROSC', 'Sala de Estratégia com 5 IAs', 'Portal do Doador + Radar de Editais'],
                            'manager' => ['WhatsApp + Kanban + Aprovações', 'IA nativa em todo o sistema', 'Contratos digitais + Assinatura', 'Central de aprovações'],
                            default   => ['WhatsApp Oficial + Chatbot IA', 'Termômetro Teto MEI + DAS', 'Recibos + NFS-e', 'Sala de Estratégia com 5 IAs'],
                        };
                    }
                @endphp
                <ul class="plan-summary-features">
                    @foreach($planFeatures as $feat)
                        <li><i class="fas fa-check"></i> {{ is_array($feat) ? ($feat['name'] ?? '') : $feat }}</li>
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
                    {{-- Plano ja definido — role fica travada + hidden --}}
                    @php
                        $lockedRole = match($plan->target_audience ?? '') {
                            'ngo'     => ['ngo_admin',      'Terceiro Setor / ONG',  'hands-helping'],
                            'manager' => ['project_manager','Gestor de Projetos',    'briefcase'],
                            default   => ['client',         'Pessoa Comum / MEI',    'user'],
                        };
                    @endphp
                    <label class="field-label">Tipo de conta</label>
                    <div class="role-locked">
                        <i class="fas fa-{{ $lockedRole[2] }}"></i>
                        <span>{{ $lockedRole[1] }}</span>
                    </div>
                    <input type="hidden" name="account_type" id="account_type" value="{{ $lockedRole[0] }}">
                @else
                    <label class="field-label">O que voce faz? <span class="field-required">*</span></label>
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
                    <label class="field-label" for="organization_name">Nome da organização / empresa</label>
                    <input type="text" id="organization_name" name="organization_name" class="input" placeholder="Ex: Minha ONG" required value="{{ old('organization_name') }}" autocomplete="organization">
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
