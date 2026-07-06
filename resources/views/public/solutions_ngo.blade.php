<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @php
        $__gtmId  = \App\Models\SystemSetting::getValue('gtm_container_id');
        $__ga4Id  = \App\Models\SystemSetting::getValue('ga4_measurement_id');
    @endphp
    @if($__gtmId)
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $__gtmId }}');</script>
    <!-- End Google Tag Manager -->
    @endif
    @if($__ga4Id)
    <!-- Google Analytics GA4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $__ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $__ga4Id }}');
    </script>
    <!-- End Google Analytics -->
    @endif
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '493025661075925');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=493025661075925&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vivensi para o Terceiro Setor | Gestão Completa para sua ONG</title>
    <meta name="description" content="A plataforma completa para ONGs brasileiras: prestação de contas, doadores, voluntários, editais com IA, transparência LGPD. Sem complicação.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/novalogo.png') }}">
    <style>
        :root {
            --brand: #10b981;
            --brand-dark: #059669;
            --brand-bg: #ecfdf5;
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
            --dark-border: rgba(255, 255, 255, 0.08);
            --dark-text: #e2e8f0;
            --dark-muted: rgba(255, 255, 255, 0.55);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); background: var(--bg); line-height: 1.55; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 24px; }

        .nav { position: sticky; top: 0; background: rgba(255,255,255,0.94); backdrop-filter: saturate(180%) blur(12px); border-bottom: 1px solid var(--border-soft); z-index: 100; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 18px 0; }
        .nav-logo { display: flex; align-items: center; gap: 12px; }
        .nav-logo-mark { width: 38px; height: 38px; border-radius: 10px; background: var(--ink); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1rem; }
        .nav-logo-text { display: flex; flex-direction: column; line-height: 1; }
        .nav-logo-text strong { font-size: 0.95rem; font-weight: 800; color: var(--ink); }
        .nav-logo-text span { font-size: 0.62rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 2px; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links a { font-size: 0.88rem; font-weight: 600; color: var(--ink-soft); transition: color 0.15s; }
        .nav-links a:hover { color: var(--brand-dark); }
        .nav-cta { background: var(--ink); color: #fff; border: none; padding: 11px 20px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; transition: background 0.15s; }
        .nav-cta:hover { background: #1f2937; }
        @media (max-width: 768px) { .nav-links a:not(.nav-cta) { display: none; } }

        .hero { padding: 80px 0 60px; }
        .hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: var(--brand-dark); background: var(--brand-bg); padding: 7px 14px; border-radius: 100px; margin-bottom: 22px; }
        .hero-title { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; line-height: 1.12; letter-spacing: -1.5px; color: var(--ink); max-width: 760px; margin: 0 auto 18px; text-align: center; }
        .hero-sub { font-size: 1.05rem; color: var(--muted); max-width: 640px; margin: 0 auto 36px; text-align: center; line-height: 1.65; }
        .hero-ctas { display: flex; gap: 12px; justify-content: center; margin-bottom: 56px; flex-wrap: wrap; }
        .btn-primary { background: var(--ink); color: #fff; border: 1px solid var(--ink); padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 10px; transition: background 0.15s; }
        .btn-primary:hover { background: #1f2937; border-color: #1f2937; }
        .btn-ghost { background: #fff; color: var(--ink); border: 1px solid var(--border); padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 10px; transition: all 0.15s; }
        .btn-ghost:hover { border-color: var(--brand); color: var(--brand-dark); }
        .hero-mini-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; max-width: 900px; margin: 0 auto; }
        .hero-mini { background: var(--bg-soft); border: 1px solid var(--border-soft); border-radius: 14px; padding: 18px 20px; }
        .hero-mini strong { display: block; font-weight: 800; font-size: 0.95rem; color: var(--ink); margin-bottom: 4px; }
        .hero-mini span { font-size: 0.78rem; color: var(--muted); line-height: 1.5; }
        @media (max-width: 768px) { .hero-mini-grid { grid-template-columns: 1fr; } }

        .section { padding: 90px 0; }
        .section-soft { background: var(--bg-soft); }
        .section-eyebrow { font-size: 0.72rem; font-weight: 800; color: var(--brand-dark); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 14px; }
        .section-title { font-size: clamp(1.7rem, 3.5vw, 2.4rem); font-weight: 800; color: var(--ink); letter-spacing: -1px; line-height: 1.18; margin-bottom: 18px; }
        .section-sub { font-size: 1rem; color: var(--muted); max-width: 640px; line-height: 1.65; margin-bottom: 50px; }
        .section-head-split { display: grid; grid-template-columns: 1fr 1.4fr; gap: 60px; margin-bottom: 50px; align-items: end; }
        @media (max-width: 768px) { .section-head-split { grid-template-columns: 1fr; gap: 18px; } }

        .step-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        @media (max-width: 768px) { .step-grid { grid-template-columns: 1fr; } }
        .step-card { background: var(--bg); border: 1px solid var(--border-soft); border-radius: 16px; padding: 26px 22px; transition: border-color 0.2s; }
        .step-card:hover { border-color: var(--brand); }
        .step-num { font-size: 0.72rem; font-weight: 800; color: var(--brand-dark); margin-bottom: 14px; letter-spacing: 1px; }
        .step-title { font-size: 1.02rem; font-weight: 800; color: var(--ink); margin-bottom: 8px; }
        .step-text { font-size: 0.86rem; color: var(--muted); line-height: 1.6; }

        .dark { background: var(--dark); color: var(--dark-text); padding: 90px 0; }
        .dark .section-eyebrow { color: var(--brand); }
        .dark .section-title { color: #fff; }
        .dark .section-sub { color: var(--dark-muted); }

        .access-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 60px; align-items: center; }
        @media (max-width: 768px) { .access-grid { grid-template-columns: 1fr; gap: 36px; } }
        .access-list { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 30px; margin-top: 28px; }
        .access-list li { list-style: none; display: flex; align-items: center; gap: 10px; font-size: 0.92rem; }
        .access-list li i { color: var(--brand); font-size: 0.75rem; }
        .access-form-card { background: var(--dark-2); border: 1px solid var(--dark-border); border-radius: 18px; padding: 32px; }
        .access-form-card h3 { font-size: 0.78rem; font-weight: 700; color: var(--dark-muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .access-form-card h3 small { color: var(--brand); font-size: 0.7rem; }
        .access-form-card .price { font-size: 3.4rem; font-weight: 800; color: #fff; line-height: 1; letter-spacing: -2px; margin-bottom: 22px; }
        .access-form-card .price small { font-size: 0.85rem; color: var(--dark-muted); font-weight: 500; margin-left: 6px; }
        .access-form-card ul { list-style: none; margin-bottom: 24px; }
        .access-form-card ul li { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: var(--dark-text); padding: 7px 0; }
        .access-form-card ul li i { color: var(--brand); font-size: 0.7rem; }
        .access-form-card .btn-fill { background: var(--brand); color: #fff; border: none; width: 100%; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 0.92rem; transition: background 0.15s; }
        .access-form-card .btn-fill:hover { background: var(--brand-dark); }

        .mark { position: relative; white-space: nowrap; }
        .mark::after { content: ''; position: absolute; left: 0; right: 0; bottom: 4px; height: 10px; background: var(--brand); opacity: 0.45; z-index: -1; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px; }
        @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
        .info-card { background: rgba(255,255,255,0.025); border: 1px solid var(--dark-border); border-radius: 16px; padding: 26px 22px; }
        .info-card-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(16,185,129,0.12); color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 18px; }
        .info-card h4 { font-size: 0.98rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .info-card p { font-size: 0.85rem; color: var(--dark-muted); line-height: 1.6; }

        .free-block { background: var(--dark-2); color: #fff; padding: 36px 40px; border-radius: 18px; display: grid; grid-template-columns: auto 1fr; gap: 28px; align-items: center; }
        .free-pill { display: inline-flex; align-items: center; gap: 8px; background: var(--brand); color: #fff; font-size: 0.72rem; font-weight: 800; padding: 6px 12px; border-radius: 100px; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; }
        .free-block h3 { font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1.3; letter-spacing: -0.5px; }
        .free-block p { margin-top: 6px; font-size: 0.9rem; color: var(--dark-muted); }
        @media (max-width: 768px) { .free-block { grid-template-columns: 1fr; padding: 26px; } }

        .training-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        @media (max-width: 768px) { .training-grid { grid-template-columns: 1fr; } }
        .training-card { background: var(--dark-2); border: 1px solid var(--dark-border); border-radius: 14px; padding: 22px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .training-card-left { display: flex; align-items: center; gap: 14px; }
        .training-tag { background: rgba(16,185,129,0.15); color: var(--brand); font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; min-width: 50px; text-align: center; }
        .training-card h5 { font-size: 0.92rem; font-weight: 700; color: #fff; margin-bottom: 3px; }
        .training-card span { font-size: 0.78rem; color: var(--dark-muted); }
        .training-badge { font-size: 0.72rem; font-weight: 700; color: var(--brand); background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 100px; }

        .final-cta { padding: 100px 0; text-align: center; }
        .final-cta h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 800; color: var(--ink); letter-spacing: -1px; line-height: 1.18; margin-bottom: 18px; }
        .final-cta p { font-size: 1rem; color: var(--muted); max-width: 560px; margin: 0 auto 32px; }

        footer { background: var(--dark); color: var(--dark-text); padding: 60px 0 28px; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        @media (max-width: 768px) { .footer-grid { grid-template-columns: 1fr 1fr; gap: 28px; } }
        .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .footer-brand .nav-logo-mark { background: var(--brand); }
        .footer-brand strong { color: #fff; font-size: 1rem; }
        .footer-text { font-size: 0.86rem; color: var(--dark-muted); line-height: 1.6; max-width: 320px; }
        .footer-col h6 { font-size: 0.72rem; font-weight: 800; color: var(--dark-muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 16px; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { font-size: 0.88rem; color: var(--dark-text); transition: color 0.15s; }
        .footer-col ul li a:hover { color: var(--brand); }
        .footer-bottom { border-top: 1px solid var(--dark-border); padding-top: 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.78rem; color: var(--dark-muted); }
        .footer-bottom-tags { display: flex; gap: 14px; flex-wrap: wrap; }
        .footer-bottom-tags span { color: var(--dark-muted); font-weight: 600; }
    </style>
</head>
<body>

<nav class="nav">
    <div class="container nav-inner">
        <a href="{{ url('/') }}" class="nav-logo">
            <span class="nav-logo-mark">V</span>
            <span class="nav-logo-text">
                <strong>Vivensi</strong>
                <span>Terceiro Setor</span>
            </span>
        </a>
        <div class="nav-links">
            <a href="#fluxo">Como funciona</a>
            <a href="#gratuito">Gratuito</a>
            <a href="#bruce">Bruce IA</a>
            <a href="#conformidade">Conformidade</a>
            <a href="#footer">Base legal</a>
            <a href="{{ route('register') }}" class="nav-cta">Entrar na lista de espera</a>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container" style="text-align: center;">
        <div class="hero-eyebrow"><i class="fas fa-arrow-right"></i> Vivensi Terceiro Setor</div>
        <h1 class="hero-title">Gestão completa para sua ONG, em <span class="mark">um só lugar</span>.</h1>
        <p class="hero-sub">A plataforma integrada de gestão para o terceiro setor: doadores, voluntários, projetos, prestação de contas e captação de editais — com transparência LGPD e Bruce IA do seu lado.</p>
        <div class="hero-ctas">
            <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Entrar na lista de espera</a>
            <a href="#fluxo" class="btn-ghost">Explorar fluxo de trabalho</a>
        </div>
        <div class="hero-mini-grid">
            <div class="hero-mini">
                <strong>Portal de Doadores</strong>
                <span>Recibo, histórico, informe IR e transparência em URL própria da ONG.</span>
            </div>
            <div class="hero-mini">
                <strong>Bruce IA</strong>
                <span>Análise contextual de projetos, propostas para editais e estratégia de marketing.</span>
            </div>
            <div class="hero-mini">
                <strong>Prestação de Contas</strong>
                <span>DRE, conciliação bancária e portal público de transparência LGPD.</span>
            </div>
        </div>
    </div>
</section>

<section class="section section-soft" id="fluxo">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Fluxo Integrado</div>
                <h2 class="section-title">Uma experiência guiada do cadastro à prestação de contas.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi foi pensada para ONGs brasileiras de qualquer porte. Cada etapa gera um registro auditável e conectado — nada fica perdido entre planilhas, e-mails e WhatsApp.</p>
        </div>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">01</div>
                <div class="step-title">Cadastra projetos</div>
                <div class="step-text">Nome, orçamento, equipe, metas, marcos planejados e Diário de Evolução com resumo por IA.</div>
            </div>
            <div class="step-card">
                <div class="step-num">02</div>
                <div class="step-title">Bruce IA analisa</div>
                <div class="step-text">Sugere ações, gera propostas para editais e responde sobre dados do projeto em tempo real.</div>
            </div>
            <div class="step-card">
                <div class="step-num">03</div>
                <div class="step-title">Captação e doadores</div>
                <div class="step-text">Portal de doadores com recibo automático, informe IR, campanhas e Pix integrado.</div>
            </div>
            <div class="step-card">
                <div class="step-num">04</div>
                <div class="step-title">Presta contas</div>
                <div class="step-text">DRE, conciliação bancária, relatórios em PDF e portal público de transparência.</div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="gratuito">
    <div class="container">
        <div class="access-grid">
            <div>
                <div class="section-eyebrow">Acesso gratuito</div>
                <h2 class="section-title">Feito para a rotina da sua ONG. Sem custar nada.</h2>
                <p style="font-size: 1rem; color: var(--muted); margin-bottom: 18px;">A Vivensi foi desenhada para as ONGs brasileiras reais — com foco em fluxo, documentos e desafios do dia a dia. Acesso 100% gratuito durante o programa de lançamento.</p>
                <ul class="access-list" style="color: var(--ink);">
                    <li><i class="fas fa-check"></i><span>Bruce IA integrado ao painel</span></li>
                    <li><i class="fas fa-check"></i><span>Atendimentos ilimitados</span></li>
                    <li><i class="fas fa-check"></i><span>Rede de serviços integrada</span></li>
                    <li><i class="fas fa-check"></i><span>Escola Vivensi com certificado</span></li>
                    <li><i class="fas fa-check"></i><span>Documentos automáticos</span></li>
                    <li><i class="fas fa-check"></i><span>Sem cartão de crédito</span></li>
                </ul>
                <div style="margin-top: 32px;">
                    <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Começar agora</a>
                </div>
            </div>
            @php
                $featuredPlan = ($plans ?? collect())->where('interval', 'monthly')->first() ?? ($plans ?? collect())->first();
            @endphp
            @if($featuredPlan)
                <div class="access-form-card">
                    <h3>{{ $featuredPlan->name }} <small>{{ $featuredPlan->interval === 'yearly' ? 'ANUAL' : 'MENSAL' }}</small></h3>
                    <div class="price">R$ <strong>{{ rtrim(rtrim(number_format((float) $featuredPlan->price, 2, ',', '.'), '0'), ',') }}</strong><small>/{{ $featuredPlan->interval === 'yearly' ? 'ano' : 'mês' }}</small></div>
                    <p style="color: var(--dark-muted); font-size: 0.85rem; margin-bottom: 22px;">{{ $featuredPlan->description ?? 'Acesso completo à plataforma — Bruce IA, gestão de doadores, prestação de contas.' }}</p>
                    <ul>
                        <li><i class="fas fa-check"></i> Bruce IA incluso</li>
                        <li><i class="fas fa-check"></i> Doadores e relatórios</li>
                        <li><i class="fas fa-check"></i> Conforme LGPD e MROSC</li>
                        <li><i class="fas fa-check"></i> Atualizações incluídas</li>
                    </ul>
                    <button type="button" class="btn-fill" onclick="window.location='{{ route('register', ['plan_id' => $featuredPlan->id]) }}'">Assinar agora</button>
                </div>
            @else
                {{-- Fallback: nenhum plano ativo cadastrado — captura interesse via lista de espera --}}
                <div class="access-form-card">
                    <h3>Plano Lançamento <small>GRATUITO</small></h3>
                    <div class="price">R$ <strong>0</strong><small>/mês</small></div>
                    <p style="color: var(--dark-muted); font-size: 0.85rem; margin-bottom: 22px;">Para sua ONG aderir ao programa de lançamento. Sem letras miúdas.</p>
                    <ul>
                        <li><i class="fas fa-check"></i> Bruce IA incluso</li>
                        <li><i class="fas fa-check"></i> Doadores e relatórios</li>
                        <li><i class="fas fa-check"></i> Conforme LGPD e MROSC</li>
                        <li><i class="fas fa-check"></i> Atualizações incluídas</li>
                    </ul>
                    <button type="button" class="btn-fill" onclick="window.location='{{ route('register') }}'">Entrar na lista</button>
                </div>
            @endif
        </div>
    </div>
</section>

<section class="dark" id="bruce">
    <div class="container">
        <div class="section-eyebrow">Bruce IA</div>
        <h2 class="section-title" style="max-width: 760px;">Bruce IA <span class="mark">não substitui</span> a sua equipe. Apoia decisões com mais organização e contexto.</h2>
        <p class="section-sub" style="max-width: 720px;">Treinado com fluxos do terceiro setor, Bruce auxilia no cadastro de projetos, organização das informações dos doadores e geração de propostas para editais — mantendo a sua ONG no centro da decisão.</p>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-life-ring"></i></div>
                <h4>Apoio técnico</h4>
                <p>Ajuda a estruturar relatos e organizar informações de projetos com base em fluxos do terceiro setor.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-clock"></i></div>
                <h4>Menos retrabalho</h4>
                <p>Auxilia na geração de documentos, propostas para editais e resumos de projeto — reduz tempo gasto em formatação manual.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-shield-halved"></i></div>
                <h4>IA responsável</h4>
                <p>O gestor mantém controle total sobre decisões. A IA sugere, a equipe decide e assina.</p>
            </div>
        </div>
    </div>
</section>

<section class="dark" style="padding-top: 0;">
    <div class="container">
        <div class="free-block">
            <span class="free-pill"><i class="fas fa-gift"></i> Acesso gratuito</span>
            <div>
                <h3>Tecnologia acessível para gestão social.</h3>
                <p>A Vivensi oferece acesso gratuito porque acredita que ONGs brasileiras devem ter ferramentas modernas de gestão, documentação e apoio técnico — independente do porte do orçamento.</p>
            </div>
        </div>
    </div>
</section>

<section class="dark" id="escola" style="padding-top: 60px;">
    <div class="container">
        <div class="section-eyebrow">Escola Vivensi</div>
        <h2 class="section-title" style="max-width: 640px;">Formação continuada dentro do painel.</h2>
        <p class="section-sub" style="max-width: 640px;">Vídeos, e-books, módulos, conteúdos diários, fórum de aprendizagem e emissão de certificado para a equipe da ONG concluir a trilha.</p>
        <div class="training-grid">
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">VID</span>
                    <div>
                        <h5>Captação de recursos na prática</h5>
                        <span>Módulo básico · 24 min</span>
                    </div>
                </div>
                <span class="training-badge">Disponível</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">PDF</span>
                    <div>
                        <h5>Guia de prestação de contas para o conselho fiscal</h5>
                        <span>E-book · leitura orientada</span>
                    </div>
                </div>
                <span class="training-badge">Novo</span>
            </div>
            <div class="training-card">
                <div class="training-card-left">
                    <span class="training-tag">LIVE</span>
                    <div>
                        <h5>Plantão de dúvidas com especialistas</h5>
                        <span>Fórum semanal · certificado</span>
                    </div>
                </div>
                <span class="training-badge">Ao vivo</span>
            </div>
        </div>
    </div>
</section>

<section class="section" id="conformidade">
    <div class="container">
        <div class="section-eyebrow">Base legal</div>
        <h2 class="section-title">Construído com referências legais do terceiro setor.</h2>
        <p class="section-sub">O sistema apoia a ONG com base em LGPD, MROSC, Marco Regulatório das OSCs e exigências da Receita Federal — sem deixar a operação travada.</p>
        <div class="step-grid">
            <div class="step-card">
                <div class="step-num">LGPD</div>
                <div class="step-title">Proteção de dados</div>
                <div class="step-text">Criptografia AES-256, blind index para CPF e tokens, auditoria de acessos.</div>
            </div>
            <div class="step-card">
                <div class="step-num">MROSC</div>
                <div class="step-title">Marco Regulatório</div>
                <div class="step-text">Termo de fomento, colaboração e parceria — gestão centralizada por edital.</div>
            </div>
            <div class="step-card">
                <div class="step-num">RF</div>
                <div class="step-title">Receita Federal</div>
                <div class="step-text">Informe de Rendimentos para doadores e relatórios prontos para auditoria.</div>
            </div>
            <div class="step-card">
                <div class="step-num">CONANDA</div>
                <div class="step-title">Conselhos</div>
                <div class="step-text">Suporte a projetos de FIA/FUMCAD, parcerias com Conselhos Tutelares e CMDCAs.</div>
            </div>
        </div>
    </div>
</section>

<section class="dark">
    <div class="container">
        <div class="section-head-split">
            <div>
                <div class="section-eyebrow">Proteção de dados</div>
                <h2 class="section-title">Projetado para proteger informações sensíveis.</h2>
            </div>
            <p class="section-sub" style="margin: 0;">A Vivensi trata dados de doadores, voluntários e beneficiários com foco em LGPD, segurança operacional e rastreabilidade. A experiência foi pensada para que a equipe da ONG nunca exponha informações além do necessário.</p>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-lock"></i></div>
                <h4>Criptografia</h4>
                <p>Dados sensíveis são protegidos em repouso com chave própria do ambiente. Tokens públicos com blind index pesquisável.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-eye"></i></div>
                <h4>Auditoria</h4>
                <p>Ações críticas são registradas para acompanhamento e responsabilização. Logs imutáveis disponíveis por API.</p>
            </div>
            <div class="info-card">
                <div class="info-card-icon"><i class="fas fa-network-wired"></i></div>
                <h4>Isolamento</h4>
                <p>Documentos, anexos e tokens vivem isolados por ONG. Sem cross-tenant em nenhuma superfície da plataforma.</p>
            </div>
        </div>
    </div>
</section>

<section class="final-cta">
    <div class="container">
        <h2>Centralize a rotina de gestão da sua ONG em uma experiência simples.</h2>
        <p>Cadastre sua organização, entre na lista de espera gratuita e receba acesso ao painel completo assim que sua conta for liberada.</p>
        <a href="{{ route('register') }}" class="btn-primary"><i class="fas fa-arrow-right"></i> Entrar na lista de espera</a>
    </div>
</section>

<footer id="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <span class="nav-logo-mark">V</span>
                    <strong>Vivensi · Terceiro Setor</strong>
                </div>
                <p class="footer-text">Sistema de gestão integrado para ONGs brasileiras. Segurança LGPD, IA contextual e isolamento por organização.</p>
            </div>
            <div class="footer-col">
                <h6>Sistema</h6>
                <ul>
                    <li><a href="#fluxo">Fluxo de gestão</a></li>
                    <li><a href="#bruce">Bruce IA</a></li>
                    <li><a href="#escola">Escola Vivensi</a></li>
                    <li><a href="#conformidade">Base legal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Acesso</h6>
                <ul>
                    <li><a href="{{ route('register') }}">Cadastro gratuito</a></li>
                    <li><a href="{{ route('login') }}">Entrar no sistema</a></li>
                    <li><a href="{{ url('/legal/privacidade') }}">Política de Privacidade</a></li>
                    <li><a href="{{ url('/legal/termos') }}">Termos de Uso</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h6>Compromissos</h6>
                <ul>
                    <li><a href="#">Proteção de dados</a></li>
                    <li><a href="#">LGPD e auditoria</a></li>
                    <li><a href="#">Isolamento por ONG</a></li>
                    <li><a href="#">Uso responsável de IA</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} VIVENSIAPP. Todos os direitos reservados. Feito para o terceiro setor brasileiro.</span>
            <div class="footer-bottom-tags">
                <span>MROSC</span>
                <span>LGPD</span>
                <span>Bruce IA</span>
            </div>
        </div>
        <div class="footer-owner" style="text-align:center; padding:16px 0 0; margin-top:16px; border-top:1px solid rgba(255,255,255,0.08); font-size:.82rem; color:rgba(255,255,255,0.55);">
            VivensiApp é um produto da <strong>NC5 HUB DIGITAL LTDA</strong> · CNPJ: 67.848.807/0001-50
        </div>
    </div>
</footer>

</body>
</html>
