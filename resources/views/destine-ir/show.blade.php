@php
    $pageUrl = $partner
        ? route('destine-ir.partner', ['slug' => $partner->slug])
        : route('destine-ir.show');

    $ogTitle = $settings->og_title ?: $settings->hero_title;
    $ogDescription = $settings->og_description ?: $settings->hero_subtitle;
    $ogImage = $settings->og_image_url ?: $settings->video_poster_url;

    $deadlineDate = $settings->deadline_date;
    $daysLeft = null;
    if ($deadlineDate) {
        $diff = now()->startOfDay()->diffInDays($deadlineDate->copy()->startOfDay(), false);
        $daysLeft = (int) $diff;
    }

    $shareMsg = $partner
        ? "Olá! A entidade \"{$partner->name}\" participa desta ação em Araraquara. Parte do seu Imposto de Renda pode virar apoio pra crianças e adolescentes da nossa cidade, sem você pagar nada a mais. Veja como: {$pageUrl}"
        : "Parte do seu Imposto de Renda pode ficar em Araraquara e apoiar crianças e adolescentes da nossa cidade — sem você pagar nada a mais. Veja o passo a passo: {$pageUrl}";
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#F2B035">
    <title>{{ $ogTitle }}</title>
    <meta name="description" content="{{ $ogDescription }}">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Vivensi">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    <link rel="canonical" href="{{ $pageUrl }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --sun: #F2B035;
            --sun-ink: #3A2A05;
            --petrol: #0A2F3C;
            --petrol-50: #163f4f;
            --papel: #F5EFE3;
            --papel-shade: #ECE3D1;
            --clay: #C96F3C;
            --ink: #141414;
            --mute: #6B6355;
            --mute-on-dark: #B6A892;
            --line: #D8CDB8;
            --line-dark: #1E4556;
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.55;
            color: var(--ink);
            background: var(--papel);
            -webkit-font-smoothing: antialiased;
        }

        .d-wrap { max-width: 580px; margin: 0 auto; padding: 0 24px; }
        @media (min-width: 820px) {
            .d-wrap { max-width: 1040px; padding: 0 48px; }
        }

        .d-serif { font-family: 'Instrument Serif', Georgia, serif; font-weight: 400; letter-spacing: -0.01em; }
        .d-eyebrow {
            font-family: 'Instrument Sans', sans-serif;
            font-size: 11px; font-weight: 600; letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        /* ─── HERO ─────────────────────────────────────────────────────────── */
        .d-hero {
            background: var(--sun);
            color: var(--sun-ink);
            padding: 28px 0 56px;
            overflow: hidden;
        }
        .d-hero__top {
            display: flex; justify-content: space-between; align-items: center;
            gap: 16px; margin-bottom: 48px; font-size: 11px;
        }
        .d-hero__top .d-eyebrow { color: var(--sun-ink); opacity: .78; }
        .d-hero__partner-chip {
            padding: 6px 12px; background: var(--petrol); color: var(--sun);
            border-radius: 2px; font-size: 11px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
        }

        .d-hero__title {
            font-size: clamp(42px, 10vw, 88px);
            line-height: 0.98;
            letter-spacing: -0.025em;
            margin: 0 0 44px;
            max-width: 14ch;
        }
        .d-hero__title em { font-style: italic; color: var(--petrol); }

        .d-hero__figure {
            display: flex; align-items: baseline; gap: 20px;
            padding-top: 28px;
            border-top: 1px solid rgba(58,42,5,.22);
        }
        .d-hero__big {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(120px, 36vw, 260px);
            line-height: 0.78;
            letter-spacing: -0.05em;
            color: var(--petrol);
            flex-shrink: 0;
        }
        .d-hero__big sup { font-size: 0.5em; vertical-align: top; margin-left: 2px; letter-spacing: 0; }
        .d-hero__caption {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(18px, 4.4vw, 26px);
            line-height: 1.25;
            max-width: 18ch;
            color: var(--sun-ink);
        }
        .d-hero__caption strong { color: var(--petrol); font-weight: 400; font-style: italic; }

        /* ─── SECTIONS BASE ────────────────────────────────────────────────── */
        .d-section { padding: 72px 0; }
        .d-section--papel { background: var(--papel); color: var(--ink); }
        .d-section--petrol { background: var(--petrol); color: var(--papel); }
        .d-section--petrol .d-mute { color: var(--mute-on-dark); }

        .d-section__eyebrow {
            margin-bottom: 20px;
            color: var(--clay);
        }
        .d-section--petrol .d-section__eyebrow { color: var(--sun); }

        .d-section__title {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(32px, 6.5vw, 52px);
            line-height: 1.02;
            letter-spacing: -0.02em;
            margin: 0 0 24px;
            max-width: 20ch;
        }
        .d-section__lede {
            font-size: 18px;
            line-height: 1.55;
            max-width: 46ch;
            color: var(--ink);
            margin: 0 0 12px;
        }
        .d-section--petrol .d-section__lede { color: var(--papel); }

        .d-mute { color: var(--mute); font-size: 15px; }

        /* ─── SIGNATURE: SPLIT BAR 94/6 ────────────────────────────────────── */
        .d-split { margin-top: 48px; }
        .d-split__row { margin-bottom: 36px; }
        .d-split__row:last-child { margin-bottom: 0; }
        .d-split__label {
            display: flex; justify-content: space-between; align-items: baseline;
            margin-bottom: 10px; gap: 16px;
        }
        .d-split__cue {
            font-family: 'Instrument Serif', serif;
            font-size: 22px; line-height: 1.1; font-style: italic;
        }
        .d-split__count {
            font-family: 'Instrument Serif', serif;
            font-size: 18px; color: var(--mute);
        }
        .d-split__bar {
            display: flex; height: 48px; width: 100%;
            border: 1px solid var(--line);
            overflow: hidden;
        }
        .d-split__fill {
            display: flex; align-items: center; padding: 0 14px;
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            font-weight: 600;
            color: var(--papel); white-space: nowrap; overflow: hidden;
        }
        .d-split__fill--full { background: var(--petrol-50); flex: 1; }
        .d-split__fill--union { background: var(--petrol-50); flex: 94; }
        .d-split__fill--local { background: var(--sun); color: var(--sun-ink); flex: 6; justify-content: flex-end; }
        .d-split__footnote {
            margin-top: 18px; font-style: italic;
            font-family: 'Instrument Serif', serif;
            font-size: 20px; color: var(--mute);
        }

        /* ─── VIDEO ────────────────────────────────────────────────────────── */
        .d-video {
            margin-top: 48px;
            border: 1px solid var(--line);
            background: var(--petrol);
            aspect-ratio: 9 / 16;
            max-width: 360px;
            margin-left: auto; margin-right: auto;
            position: relative;
        }
        @media (min-width: 820px) {
            .d-video { aspect-ratio: 16 / 9; max-width: 100%; }
        }
        .d-video video { width: 100%; height: 100%; display: block; object-fit: cover; }

        /* ─── FUND CARD (sobre petrol) ─────────────────────────────────────── */
        .d-fund {
            border: 1px solid var(--line-dark);
            padding: 32px 28px;
            margin-top: 32px;
        }
        .d-fund__row { padding: 20px 0; border-bottom: 1px solid var(--line-dark); }
        .d-fund__row:last-child { border-bottom: 0; padding-bottom: 0; }
        .d-fund__row:first-child { padding-top: 0; }
        .d-fund__label {
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--sun); margin-bottom: 8px; font-weight: 600;
        }
        .d-fund__value {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(22px, 5vw, 30px);
            line-height: 1.1;
            color: var(--papel);
            word-break: break-all;
            margin-bottom: 10px;
        }
        .d-copy {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 16px;
            background: transparent;
            border: 1px solid var(--sun);
            color: var(--sun);
            font-family: 'Instrument Sans', sans-serif;
            font-size: 12px; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            cursor: pointer;
            transition: background .18s, color .18s;
        }
        .d-copy:hover { background: var(--sun); color: var(--sun-ink); }
        .d-copy.is-ok { background: var(--papel); border-color: var(--papel); color: var(--petrol); }

        /* ─── PATHS (passos tipográficos, nao cards) ───────────────────────── */
        .d-path { margin-top: 44px; }
        .d-path__head {
            display: flex; gap: 20px; align-items: baseline;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
        }
        .d-path__kicker {
            font-family: 'Instrument Serif', serif; font-style: italic;
            font-size: 24px; color: var(--clay);
            flex-shrink: 0;
        }
        .d-path__name {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(24px, 5vw, 34px);
            line-height: 1.05;
            margin: 0;
        }
        .d-steps { list-style: none; padding: 0; margin: 0; counter-reset: s; }
        .d-steps li {
            counter-increment: s;
            display: grid; grid-template-columns: 44px 1fr;
            gap: 14px; padding: 20px 0;
            border-bottom: 1px solid var(--line);
        }
        .d-steps li:last-child { border-bottom: 0; }
        .d-steps li::before {
            content: counter(s, decimal-leading-zero);
            font-family: 'Instrument Serif', serif;
            font-size: 28px;
            color: var(--clay);
            line-height: 1;
        }
        .d-steps__title { font-weight: 600; font-size: 17px; margin: 0 0 4px; }
        .d-steps__text { font-size: 16px; line-height: 1.55; color: var(--ink); margin: 0; }

        /* ─── SIMULADOR ────────────────────────────────────────────────────── */
        .d-sim {
            margin-top: 48px;
            padding: 44px 32px;
            background: var(--papel-shade);
            border: 1px solid var(--line);
        }
        .d-sim__label {
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--mute); margin-bottom: 10px; font-weight: 600;
        }
        .d-sim__input {
            width: 100%;
            padding: 12px 0;
            background: transparent;
            border: 0;
            border-bottom: 2px solid var(--petrol);
            font-family: 'Instrument Serif', serif;
            font-size: clamp(32px, 7vw, 48px);
            color: var(--ink);
            outline: none;
        }
        .d-sim__input::placeholder { color: var(--mute); font-style: italic; }
        .d-sim__results {
            display: grid; grid-template-columns: 1fr; gap: 24px;
            margin-top: 32px;
        }
        @media (min-width: 540px) {
            .d-sim__results { grid-template-columns: 1fr 1fr; }
        }
        .d-sim__box { }
        .d-sim__cue {
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            color: var(--clay); margin-bottom: 6px; font-weight: 600;
        }
        .d-sim__out {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(32px, 7vw, 44px);
            line-height: 1.05;
            color: var(--petrol);
        }
        .d-sim__hint { font-size: 13px; color: var(--mute); margin-top: 24px; font-style: italic; font-family: 'Instrument Serif', serif; }

        /* ─── FAQ (entrevista, nao accordion fofo) ─────────────────────────── */
        .d-faq__item {
            padding: 32px 0;
            border-bottom: 1px solid var(--line);
        }
        .d-faq__item:last-child { border-bottom: 0; }
        .d-faq__q {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(22px, 4.6vw, 30px);
            line-height: 1.15;
            margin: 0 0 10px;
        }
        .d-faq__a { font-size: 16px; color: var(--mute); margin: 0; max-width: 48ch; }

        /* ─── PARCEIRAS (logos monocromaticas em petrol) ───────────────────── */
        .d-partners {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px;
            margin-top: 48px; background: var(--line-dark);
            border: 1px solid var(--line-dark);
        }
        @media (min-width: 540px) { .d-partners { grid-template-columns: repeat(4, 1fr); } }
        @media (min-width: 820px) { .d-partners { grid-template-columns: repeat(5, 1fr); } }
        .d-partner {
            background: var(--petrol);
            aspect-ratio: 1;
            padding: 20px 14px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 10px; text-align: center;
            transition: background .2s;
        }
        .d-partner:hover { background: var(--petrol-50); }
        .d-partner img {
            max-width: 100%; max-height: 56px; object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: .76;
            transition: filter .2s, opacity .2s;
        }
        .d-partner:hover img { filter: none; opacity: 1; }
        .d-partner__name {
            font-size: 11px; color: var(--mute-on-dark);
            line-height: 1.25; font-weight: 500;
        }

        /* ─── FOOTER ───────────────────────────────────────────────────────── */
        .d-footer { padding: 56px 0 120px; background: var(--petrol); color: var(--papel); }
        .d-footer__sig {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(22px, 4.4vw, 28px);
            font-style: italic;
            line-height: 1.25;
            margin: 0 0 20px;
            max-width: 22ch;
        }
        .d-footer__brand {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 32px;
            font-family: 'Instrument Sans', sans-serif;
            font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase;
            font-weight: 600; color: var(--sun);
            text-decoration: none;
        }
        .d-footer__brand span { color: var(--mute-on-dark); }
        .d-footer__legal {
            font-size: 12px; line-height: 1.65; color: var(--mute-on-dark);
            max-width: 60ch;
            border-top: 1px solid var(--line-dark);
            padding-top: 20px;
        }

        /* ─── SHARE BAR (sticky, discreta) ─────────────────────────────────── */
        .d-share {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 50;
            background: var(--papel);
            border-top: 1px solid var(--line);
            padding: 12px 20px; padding-bottom: calc(12px + env(safe-area-inset-bottom));
            display: flex; gap: 10px; justify-content: center;
        }
        .d-share__btn {
            flex: 1; max-width: 240px;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 16px;
            font-family: 'Instrument Sans', sans-serif;
            font-size: 13px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
            text-decoration: none;
            border: 1px solid var(--petrol);
            cursor: pointer;
            transition: background .18s, color .18s;
        }
        .d-share__btn--primary { background: var(--petrol); color: var(--papel); }
        .d-share__btn--primary:hover { background: #000; }
        .d-share__btn--ghost { background: transparent; color: var(--petrol); }
        .d-share__btn--ghost:hover { background: var(--petrol); color: var(--papel); }
        .d-share__btn.is-ok { background: var(--clay); color: var(--papel); border-color: var(--clay); }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; animation: none !important; }
        }

        /* keyboard focus */
        :focus-visible { outline: 2px solid var(--clay); outline-offset: 3px; }
    </style>
</head>
<body>

    {{-- ─── HERO ────────────────────────────────────────────────────────── --}}
    <header class="d-hero">
        <div class="d-wrap">
            <div class="d-hero__top">
                <span class="d-eyebrow">
                    @if($settings->deadline_label)
                        {{ $settings->deadline_label }}@if($daysLeft !== null && $daysLeft > 0 && $daysLeft <= 90) · faltam {{ $daysLeft }} {{ $daysLeft === 1 ? 'dia' : 'dias' }}@endif
                    @else
                        Ação pública · Araraquara
                    @endif
                </span>
                @if($partner)
                    <span class="d-hero__partner-chip">via {{ $partner->name }}</span>
                @endif
            </div>

            <h1 class="d-hero__title d-serif">{{ $settings->hero_title }}</h1>

            <div class="d-hero__figure">
                <div class="d-hero__big">6<sup>%</sup></div>
                <p class="d-hero__caption">
                    {{ $settings->hero_subtitle }}
                </p>
            </div>
        </div>
    </header>

    {{-- ─── SIGNATURE: SPLIT BAR 94/6 ───────────────────────────────────── --}}
    <section class="d-section d-section--papel">
        <div class="d-wrap">
            <div class="d-eyebrow d-section__eyebrow">O que muda com a destinação</div>
            <h2 class="d-section__title">Hoje o seu imposto vai inteiro pra União. Com a destinação, uma parte fica aqui.</h2>

            <div class="d-split">
                <div class="d-split__row">
                    <div class="d-split__label">
                        <span class="d-split__cue">Hoje</span>
                        <span class="d-split__count">100% União</span>
                    </div>
                    <div class="d-split__bar">
                        <div class="d-split__fill d-split__fill--full">Brasília</div>
                    </div>
                </div>

                <div class="d-split__row">
                    <div class="d-split__label">
                        <span class="d-split__cue">Com destinação</span>
                        <span class="d-split__count">94% União · 6% Araraquara</span>
                    </div>
                    <div class="d-split__bar">
                        <div class="d-split__fill d-split__fill--union">Brasília</div>
                        <div class="d-split__fill d-split__fill--local">Araraquara</div>
                    </div>
                </div>

                <p class="d-split__footnote">Sem pagar nada a mais.</p>
            </div>

            @if($settings->video_vertical_url || $settings->video_horizontal_url)
                <div class="d-video">
                    <video
                        controls playsinline preload="none"
                        @if($settings->video_poster_url) poster="{{ $settings->video_poster_url }}" @endif>
                        @if($settings->video_horizontal_url)
                            <source src="{{ $settings->video_horizontal_url }}" type="video/mp4" media="(min-width: 820px)">
                        @endif
                        @if($settings->video_vertical_url)
                            <source src="{{ $settings->video_vertical_url }}" type="video/mp4">
                        @elseif($settings->video_horizontal_url)
                            <source src="{{ $settings->video_horizontal_url }}" type="video/mp4">
                        @endif
                    </video>
                </div>
            @endif
        </div>
    </section>

    {{-- ─── O QUE É / QUEM PODE ─────────────────────────────────────────── --}}
    @if($settings->what_is_text || $settings->who_can_text)
        <section class="d-section d-section--papel" style="padding-top:0;">
            <div class="d-wrap">
                @if($settings->what_is_text)
                    <div style="padding-top:48px; border-top:1px solid var(--line);">
                        <div class="d-eyebrow d-section__eyebrow">O conceito</div>
                        <h2 class="d-section__title">O que é a destinação</h2>
                        <p class="d-section__lede">{{ $settings->what_is_text }}</p>
                    </div>
                @endif
                @if($settings->who_can_text)
                    <div style="padding-top:48px; margin-top:48px; border-top:1px solid var(--line);">
                        <div class="d-eyebrow d-section__eyebrow">Elegibilidade</div>
                        <h2 class="d-section__title">Quem pode destinar</h2>
                        <p class="d-section__lede">{{ $settings->who_can_text }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ─── DADOS DO FUNDO (petrol) ─────────────────────────────────────── --}}
    <section class="d-section d-section--petrol">
        <div class="d-wrap">
            <div class="d-eyebrow d-section__eyebrow">Dados oficiais</div>
            <h2 class="d-section__title">Pra onde o dinheiro vai</h2>
            <p class="d-section__lede">A chave PIX é o CNPJ abaixo. Tudo é recebido pelo {{ $settings->council_name }} — a Vivensi apenas divulga.</p>

            <div class="d-fund">
                <div class="d-fund__row">
                    <div class="d-fund__label">Fundo</div>
                    <div class="d-fund__value" style="font-size:clamp(18px,4vw,24px);">{{ $settings->fund_name }}</div>
                </div>
                <div class="d-fund__row">
                    <div class="d-fund__label">CNPJ · Chave PIX</div>
                    <div class="d-fund__value">{{ $settings->fund_cnpj }}</div>
                    <button type="button" class="d-copy" data-copy="{{ $settings->fund_cnpj }}" onclick="dCopy(this)">Copiar CNPJ</button>
                </div>
                <div class="d-fund__row">
                    <div class="d-fund__label">E-mail do conselho</div>
                    <div class="d-fund__value" style="font-size:clamp(18px,4vw,24px);">{{ $settings->fund_email }}</div>
                    <button type="button" class="d-copy" data-copy="{{ $settings->fund_email }}" onclick="dCopy(this)">Copiar e-mail</button>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── CAMINHOS ────────────────────────────────────────────────────── --}}
    @if(!empty($settings->path1_steps) || !empty($settings->path2_steps))
        <section class="d-section d-section--papel">
            <div class="d-wrap">
                <div class="d-eyebrow d-section__eyebrow">Como fazer</div>
                <h2 class="d-section__title">Dois caminhos. Escolha um.</h2>

                @if(!empty($settings->path1_steps))
                    <div class="d-path">
                        <div class="d-path__head">
                            <span class="d-path__kicker">Caminho 1 — até 31/12</span>
                        </div>
                        <h3 class="d-path__name d-serif">Fazer um PIX até o fim do ano (até 6%).</h3>
                        <ol class="d-steps" style="margin-top:20px;">
                            @foreach($settings->path1_steps as $step)
                                <li>
                                    <div>
                                        <p class="d-steps__title">{{ $step['title'] ?? '' }}</p>
                                        <p class="d-steps__text">{{ $step['text'] ?? '' }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if(!empty($settings->path2_steps))
                    <div class="d-path" style="margin-top:72px;">
                        <div class="d-path__head">
                            <span class="d-path__kicker">Caminho 2 — na declaração</span>
                        </div>
                        <h3 class="d-path__name d-serif">Destinar direto no programa do IR (até 3%).</h3>
                        <ol class="d-steps" style="margin-top:20px;">
                            @foreach($settings->path2_steps as $step)
                                <li>
                                    <div>
                                        <p class="d-steps__title">{{ $step['title'] ?? '' }}</p>
                                        <p class="d-steps__text">{{ $step['text'] ?? '' }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if($settings->cost_text)
                    <div style="margin-top:72px; padding-top:48px; border-top:1px solid var(--line);">
                        <div class="d-eyebrow d-section__eyebrow">Custo pra você</div>
                        <p class="d-section__title d-serif" style="font-style:italic;">{{ $settings->cost_text }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ─── SIMULADOR ───────────────────────────────────────────────────── --}}
    @if($settings->show_simulator)
        <section class="d-section d-section--papel" style="padding-top:0;">
            <div class="d-wrap">
                <div class="d-sim">
                    <div class="d-eyebrow d-section__eyebrow">Simulador</div>
                    <h2 class="d-section__title" style="margin-bottom:14px;">Quanto você pode destinar?</h2>
                    <p class="d-mute" style="margin:0 0 24px;">Digite seu imposto devido pra ver o teto dos dois caminhos.</p>

                    <label for="d-sim-input" class="d-sim__label">Imposto devido (R$)</label>
                    <input type="text" id="d-sim-input" class="d-sim__input" inputmode="decimal" placeholder="3.500,00" autocomplete="off">

                    <div class="d-sim__results">
                        <div class="d-sim__box">
                            <div class="d-sim__cue">Caminho 1 · até 6%</div>
                            <div class="d-sim__out" id="d-sim-6">R$ 0,00</div>
                        </div>
                        <div class="d-sim__box">
                            <div class="d-sim__cue">Caminho 2 · até 3%</div>
                            <div class="d-sim__out" id="d-sim-3">R$ 0,00</div>
                        </div>
                    </div>

                    <p class="d-sim__hint">Valores de referência. Confirme no programa oficial do IR.</p>
                </div>
            </div>
        </section>
    @endif

    {{-- ─── MURAL DE PARCEIRAS ──────────────────────────────────────────── --}}
    @if($settings->show_partners_wall && $partners->count())
        <section class="d-section d-section--petrol">
            <div class="d-wrap">
                <div class="d-eyebrow d-section__eyebrow">Entidades participantes</div>
                <h2 class="d-section__title">{{ $partners->count() }} organizações de Araraquara distribuem esta campanha.</h2>
                <p class="d-section__lede">Você pode indicar o nome de uma delas no e-mail ao conselho.</p>

                <div class="d-partners">
                    @foreach($partners as $p)
                        <div class="d-partner">
                            @if($p->logo_url)
                                <img src="{{ $p->logo_url }}" alt="{{ $p->name }}" loading="lazy">
                            @endif
                            <div class="d-partner__name">{{ $p->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── FAQ ─────────────────────────────────────────────────────────── --}}
    @if(!empty($settings->faq_items))
        <section class="d-section d-section--papel">
            <div class="d-wrap">
                <div class="d-eyebrow d-section__eyebrow">Dúvidas comuns</div>
                <h2 class="d-section__title">Perguntas frequentes.</h2>

                <div style="margin-top:40px;">
                    @foreach($settings->faq_items as $item)
                        <div class="d-faq__item">
                            <p class="d-faq__q">{{ $item['q'] ?? '' }}</p>
                            <p class="d-faq__a">{{ $item['a'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ─── FOOTER ──────────────────────────────────────────────────────── --}}
    <footer class="d-footer">
        <div class="d-wrap">
            <p class="d-footer__sig d-serif">{{ $settings->footer_tagline }}</p>
            <a href="{{ url('/') }}" class="d-footer__brand">
                Vivensi <span>· vivensi.app.br</span>
            </a>
            @if($settings->footer_legal_text)
                <p class="d-footer__legal">{{ $settings->footer_legal_text }}</p>
            @endif
        </div>
    </footer>

    @if($settings->show_share_bar)
        <div class="d-share">
            <a href="https://wa.me/?text={{ urlencode($shareMsg) }}" target="_blank" rel="noopener" class="d-share__btn d-share__btn--primary">
                Compartilhar
            </a>
            <button type="button" class="d-share__btn d-share__btn--ghost" data-link="{{ $pageUrl }}" onclick="dCopyLink(this)">
                <span class="d-share__btn__label">Copiar link</span>
            </button>
        </div>
    @endif

    <script>
        function dCopy(btn) {
            const txt = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(txt).then(() => {
                const old = btn.textContent;
                btn.textContent = 'Copiado';
                btn.classList.add('is-ok');
                setTimeout(() => { btn.textContent = old; btn.classList.remove('is-ok'); }, 1800);
            });
        }
        function dCopyLink(btn) {
            const link = btn.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                btn.classList.add('is-ok');
                const label = btn.querySelector('.d-share__btn__label');
                const old = label.textContent;
                label.textContent = 'Link copiado';
                setTimeout(() => { label.textContent = old; btn.classList.remove('is-ok'); }, 1800);
            });
        }

        const simInput = document.getElementById('d-sim-input');
        if (simInput) {
            const parseBr = (v) => {
                if (!v) return 0;
                const clean = String(v).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
                const n = parseFloat(clean);
                return isNaN(n) ? 0 : n;
            };
            const fmt = (n) => 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const update = () => {
                const base = parseBr(simInput.value);
                document.getElementById('d-sim-6').textContent = fmt(base * 0.06);
                document.getElementById('d-sim-3').textContent = fmt(base * 0.03);
            };
            simInput.addEventListener('input', update);
        }
    </script>
</body>
</html>
