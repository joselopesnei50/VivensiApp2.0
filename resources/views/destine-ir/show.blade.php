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

    // mailto pré-preenchido quando vier via partner — doador só anexa comprovante
    $mailtoHref = null;
    if ($partner) {
        $subject = 'Destinação ao FMDCA — Indicação: ' . $partner->name;
        $body    = "Olá,\n\n"
                 . "Segue minha destinação ao Fundo Municipal (FMDCA de Araraquara).\n\n"
                 . "Nome: \n"
                 . "CPF ou CNPJ: \n"
                 . "Valor destinado: R$ \n"
                 . "Entidade que gostaria de apoiar: {$partner->name}\n\n"
                 . "Comprovante do PIX em anexo.\n\n"
                 . "Obrigado.";
        $mailtoHref = 'mailto:' . $settings->fund_email
                    . '?subject=' . rawurlencode($subject)
                    . '&body=' . rawurlencode($body);
    }

    $youtubeId = $settings->youtube_id;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#050B14">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #050B14;
            --bg-navy: #0A192F;
            --bg-navy-light: #112240;
            --primary-blue: #2563EB;
            --primary-blue-hover: #1D4ED8;
            --accent-gold: #F59E0B;
            --accent-gold-glow: rgba(245, 158, 11, 0.4);
            --text-white: #F8FAFC;
            --text-gray: #94A3B8;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4 { font-family: var(--font-heading); line-height: 1.1; color: var(--text-white); }
        a { text-decoration: none; color: inherit; }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* ─── NAVBAR ─────────────────────────────────────────────────────── */
        .navbar {
            position: fixed; top: 0; width: 100%;
            padding: 1rem 0;
            background: rgba(5, 11, 20, 0.8);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 100;
        }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .logo {
            font-family: var(--font-heading); font-weight: 800;
            font-size: 1.5rem; letter-spacing: -0.5px;
        }
        .logo span { color: var(--accent-gold); }

        /* ─── CTA ────────────────────────────────────────────────────────── */
        .btn-cta {
            background: linear-gradient(135deg, var(--accent-gold), #D97706);
            color: #000;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.05rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 0 20px var(--accent-gold-glow), inset 0 0 10px rgba(255,255,255,0.5);
            transition: transform .3s ease, box-shadow .3s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-cta:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 0 30px var(--accent-gold), inset 0 0 20px rgba(255,255,255,0.8);
        }
        .btn-nav { padding: 0.6rem 1.3rem; font-size: 0.82rem; }

        /* ─── HERO ───────────────────────────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex; align-items: center;
            padding: 120px 0 80px;
            overflow: hidden;
            background: radial-gradient(circle at top right, var(--bg-navy-light) 0%, var(--bg-dark) 60%);
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.5;
        }
        .blob-1 {
            width: 400px; height: 400px;
            background: var(--primary-blue);
            top: -100px; right: -100px;
            animation: float 10s infinite alternate ease-in-out;
        }
        .blob-2 {
            width: 300px; height: 300px;
            background: var(--accent-gold);
            bottom: -50px; left: -100px;
            opacity: 0.2;
            animation: float 8s infinite alternate-reverse ease-in-out;
        }
        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-30px, 30px) scale(1.1); }
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 2rem;
            align-items: center;
            width: 100%;
        }

        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUpAnim 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.3s; }
        .delay-3 { animation-delay: 0.5s; }
        .delay-4 { animation-delay: 0.7s; }
        @keyframes fadeUpAnim { to { opacity: 1; transform: translateY(0); } }

        .hero-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            font-size: 0.82rem;
            color: var(--accent-gold);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
        }
        .hero-tag--partner {
            margin-left: 10px;
            color: var(--text-white);
            background: rgba(37, 99, 235, 0.14);
            border-color: rgba(37, 99, 235, 0.4);
        }

        .hero h1 {
            font-size: clamp(2.4rem, 6vw, 4.6rem);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1.5rem;
        }
        .hero h1 em {
            font-style: normal;
            background: linear-gradient(to right, #60A5FA, #3B82F6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-sub {
            font-size: 1.15rem;
            color: var(--text-gray);
            max-width: 520px;
            margin-bottom: 2.5rem;
        }

        .percentage-display {
            position: relative;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center;
        }
        .percentage-number {
            font-family: var(--font-heading);
            font-size: clamp(9rem, 20vw, 18rem);
            font-weight: 800;
            line-height: 0.8;
            color: transparent;
            -webkit-text-stroke: 2px var(--accent-gold);
            position: relative;
            animation: pulseGlow 3s infinite alternate;
        }
        .percentage-number::after {
            content: '6%';
            position: absolute; left: 0; top: 0;
            color: var(--accent-gold);
            z-index: -1;
            filter: blur(20px);
            opacity: 0.6;
        }
        @keyframes pulseGlow {
            0% { -webkit-text-stroke: 2px rgba(245, 158, 11, 0.5); transform: scale(1); }
            100% { -webkit-text-stroke: 4px rgba(245, 158, 11, 1); transform: scale(1.02); }
        }
        .percentage-sub {
            font-weight: 500;
            font-size: 1.1rem;
            color: var(--text-gray);
            margin-top: 1rem;
            max-width: 280px;
        }

        /* ─── IMPACT (comparativo) ───────────────────────────────────────── */
        .impact { padding: 6rem 0; background: var(--bg-navy); }

        .section-head { text-align: center; max-width: 720px; margin: 0 auto 3rem; }
        .section-head h2 { font-size: clamp(1.9rem, 4vw, 2.6rem); margin-bottom: 1rem; }
        .section-head p { color: var(--text-gray); font-size: 1.05rem; }

        .impact-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
            margin-top: 3rem;
        }
        .impact-card {
            background: var(--bg-navy-light);
            border: 1px solid var(--glass-border);
            padding: 2.4rem;
            border-radius: 24px;
        }
        .impact-card.destructive { border-top: 4px solid #EF4444; }
        .impact-card.constructive { border-top: 4px solid var(--accent-gold); }
        .impact-title { font-size: 1.3rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: baseline; }
        .impact-value { font-size: 2.4rem; font-family: var(--font-heading); font-weight: 800; }
        .impact-card p { color: var(--text-gray); font-size: 0.95rem; }

        .modern-bar-bg {
            width: 100%; height: 30px;
            background: rgba(255,255,255,0.05);
            border-radius: 50px;
            overflow: hidden;
            margin-top: 2rem;
            position: relative;
        }
        .modern-bar-fill { height: 100%; border-radius: 50px; }
        .fill-red { background: linear-gradient(90deg, #7F1D1D, #EF4444); width: 100%; }
        .bar-split { display: flex; height: 100%; width: 100%; }
        .fill-gray { background: rgba(255,255,255,0.2); width: 94%; }
        .fill-gold {
            background: linear-gradient(90deg, #D97706, #F59E0B);
            width: 6%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: bold; color: #000;
        }

        /* ─── VIDEO ──────────────────────────────────────────────────────── */
        .video-section { padding: 6rem 0; background: var(--bg-dark); }
        .video-wrap {
            max-width: 820px; margin: 0 auto;
            aspect-ratio: 16/9;
            border-radius: 24px; overflow: hidden;
            border: 1px solid var(--glass-border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            background: #000;
        }
        .video-wrap--vertical {
            aspect-ratio: 9/16;
            max-width: 360px;
        }
        .video-wrap video { width: 100%; height: 100%; display: block; object-fit: cover; }

        /* ─── ACTION (CNPJ glass panel) ──────────────────────────────────── */
        .action-section {
            padding: 6rem 0;
            background: linear-gradient(180deg, var(--bg-dark), var(--bg-navy));
        }
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .data-group { display: flex; flex-direction: column; gap: 0.6rem; align-items: flex-start; }
        .data-label {
            font-size: 0.78rem; color: var(--text-gray);
            text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
        }
        .data-value {
            font-family: monospace;
            font-size: clamp(1.1rem, 2.4vw, 1.5rem);
            font-weight: 600; color: var(--text-white);
            word-break: break-all;
        }
        .btn-copy {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.75rem 1.4rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: background 0.2s;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-copy:hover { background: var(--primary-blue-hover); }
        .btn-copy.success { background: #10B981; }

        /* ─── CALLOUT INDICAÇÃO DA ENTIDADE ──────────────────────────────── */
        .partner-callout {
            position: relative;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(245, 158, 11, 0.08));
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 20px;
            padding: 2rem 2.2rem;
            margin: 0 0 2rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
            align-items: center;
            overflow: hidden;
        }
        .partner-callout::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.15), transparent 60%);
            pointer-events: none;
        }
        .partner-callout__content { position: relative; z-index: 1; }
        .partner-callout__eyebrow {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: rgba(245, 158, 11, 0.18);
            color: var(--accent-gold);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 0.8rem;
        }
        .partner-callout__title {
            font-family: var(--font-heading);
            font-size: clamp(1.2rem, 2.8vw, 1.6rem);
            font-weight: 600;
            color: var(--text-white);
            line-height: 1.25;
            margin: 0;
        }
        .partner-callout__title strong {
            color: var(--accent-gold);
            font-weight: 800;
        }
        .partner-callout__hint {
            margin: 0.5rem 0 0;
            color: var(--text-gray);
            font-size: 0.92rem;
        }
        .partner-callout__cta {
            position: relative; z-index: 1;
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.9rem 1.6rem;
            background: linear-gradient(135deg, var(--accent-gold), #D97706);
            color: #000;
            border: none;
            border-radius: 50px;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(245, 158, 11, 0.25);
            transition: transform .2s, box-shadow .2s;
            white-space: nowrap;
        }
        .partner-callout__cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(245, 158, 11, 0.35);
        }

        /* ─── PATHS (Caminho 1 e 2) ──────────────────────────────────────── */
        .paths { padding: 6rem 0; background: var(--bg-dark); }
        .paths-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.2rem; margin-top: 2rem; }
        .path-card {
            background: var(--bg-navy);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.4rem;
            position: relative;
        }
        .path-tag {
            display: inline-block;
            padding: 0.35rem 0.9rem;
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-gold);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 50px;
            font-size: 0.74rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        .path-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        .steps { list-style: none; padding: 0; counter-reset: step; }
        .steps li {
            counter-increment: step;
            display: grid; grid-template-columns: 36px 1fr;
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px solid var(--glass-border);
        }
        .steps li:first-child { border-top: 0; padding-top: 0; }
        .steps li::before {
            content: counter(step, decimal-leading-zero);
            font-family: var(--font-heading);
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--accent-gold);
            line-height: 1.3;
        }
        .steps strong {
            display: block;
            color: var(--text-white);
            font-size: 1rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .steps span { color: var(--text-gray); font-size: 0.94rem; }

        .cost-strip {
            margin-top: 3rem;
            padding: 1.5rem 2rem;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), transparent);
            border-left: 3px solid #10B981;
            border-radius: 10px;
            color: var(--text-white);
            font-size: 1.05rem;
        }
        .cost-strip strong { color: #10B981; }

        /* ─── CALCULATOR ─────────────────────────────────────────────────── */
        .calculator { padding: 6rem 0; background: var(--bg-navy); }
        .calc-container {
            max-width: 800px; margin: 0 auto;
            background: var(--bg-dark);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3.5rem;
            text-align: center;
        }
        .calc-container h2 { font-size: clamp(1.6rem, 3vw, 2rem); margin-bottom: 0.5rem; }
        .calc-container > p { color: var(--text-gray); font-size: 1rem; }
        .calc-input-wrap {
            position: relative;
            max-width: 420px;
            margin: 2rem auto;
        }
        .calc-input-wrap span {
            position: absolute; left: 20px; top: 50%;
            transform: translateY(-50%);
            font-size: 1.4rem; color: var(--text-gray); font-weight: 300;
        }
        .calc-input {
            width: 100%;
            padding: 1.4rem 1.5rem 1.4rem 3.5rem;
            font-size: 1.8rem;
            background: var(--bg-navy-light);
            border: 2px solid var(--glass-border);
            border-radius: 16px;
            color: var(--accent-gold);
            font-family: var(--font-heading);
            font-weight: 800;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .calc-input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
        }
        .calc-results-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 1.3rem; margin-top: 2rem;
        }
        .calc-res-card {
            background: var(--bg-navy-light);
            padding: 1.8rem;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
        }
        .calc-res-title {
            font-size: 0.82rem; color: var(--text-gray);
            text-transform: uppercase; letter-spacing: 1px;
            margin-bottom: 0.5rem; font-weight: 600;
        }
        .calc-res-value {
            font-size: clamp(1.6rem, 4vw, 2.3rem);
            font-family: var(--font-heading); font-weight: 800;
            color: var(--primary-blue);
        }
        .calc-hint { font-size: 0.85rem; color: var(--text-gray); margin-top: 1.5rem; }

        /* ─── PARTNERS ───────────────────────────────────────────────────── */
        .partners { padding: 6rem 0; background: var(--bg-dark); }
        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .partner-cell {
            background: var(--bg-navy);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1.4rem 0.8rem;
            aspect-ratio: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 0.6rem; text-align: center;
            transition: border-color .2s, transform .2s;
        }
        .partner-cell:hover { border-color: rgba(245, 158, 11, 0.4); transform: translateY(-3px); }
        .partner-cell img {
            max-width: 100%; max-height: 60px; object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.72;
            transition: filter .2s, opacity .2s;
        }
        .partner-cell:hover img { filter: none; opacity: 1; }
        .partner-cell__name {
            font-size: 0.78rem; color: var(--text-gray);
            line-height: 1.25; font-weight: 500;
        }

        /* ─── FAQ ────────────────────────────────────────────────────────── */
        .faq { padding: 6rem 0; background: var(--bg-navy); }
        .faq-wrap { max-width: 800px; margin: 0 auto; }
        .faq-item {
            background: var(--bg-dark);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            margin-bottom: 1rem;
            overflow: hidden;
            transition: border-color .3s;
        }
        .faq-item:hover { border-color: rgba(255,255,255,0.2); }
        .faq-item summary {
            padding: 1.5rem 2rem;
            font-family: var(--font-heading);
            font-size: 1.1rem; font-weight: 600;
            cursor: pointer;
            list-style: none;
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '+';
            font-size: 1.8rem; color: var(--primary-blue);
            font-weight: 300; line-height: 1;
            transition: transform .3s, color .3s;
            flex-shrink: 0;
        }
        .faq-item[open] summary::after { transform: rotate(45deg); color: var(--accent-gold); }
        .faq-item p {
            padding: 0 2rem 1.5rem;
            color: var(--text-gray);
            font-size: 1rem;
        }

        /* ─── FOOTER ─────────────────────────────────────────────────────── */
        footer {
            padding: 3rem 0 7rem;
            text-align: center;
            border-top: 1px solid var(--glass-border);
            color: var(--text-gray);
            font-size: 0.92rem;
            background: var(--bg-dark);
        }
        footer p + p { margin-top: 1rem; font-size: 0.78rem; opacity: 0.78; max-width: 60ch; margin-left: auto; margin-right: auto; }
        footer a { color: var(--accent-gold); }

        /* ─── SHARE BAR (sticky) ─────────────────────────────────────────── */
        .share-bar {
            position: fixed; left: 0; right: 0; bottom: 0;
            z-index: 90;
            background: rgba(5, 11, 20, 0.9);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid var(--glass-border);
            padding: 0.9rem 1.2rem;
            padding-bottom: calc(0.9rem + env(safe-area-inset-bottom));
            display: flex; gap: 0.7rem; justify-content: center;
        }
        .share-btn {
            flex: 1; max-width: 260px;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.1rem;
            font-family: var(--font-heading);
            font-size: 0.84rem; font-weight: 600;
            letter-spacing: 0.5px;
            text-decoration: none;
            border-radius: 10px;
            cursor: pointer;
            border: none;
            transition: transform .2s, box-shadow .2s;
        }
        .share-btn--primary {
            background: linear-gradient(135deg, #25d366, #128C7E);
            color: #fff;
        }
        .share-btn--primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3); }
        .share-btn--ghost {
            background: var(--glass-bg);
            color: var(--text-white);
            border: 1px solid var(--glass-border);
        }
        .share-btn--ghost:hover { background: rgba(255,255,255,0.08); }
        .share-btn.success { background: var(--primary-blue); }

        /* ─── RESPONSIVO ─────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .hero { padding-top: 100px; min-height: auto; padding-bottom: 60px; }
            .hero-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-sub { margin-left: auto; margin-right: auto; }
            .percentage-display { margin-top: 2rem; }
            .impact-grid, .paths-grid, .glass-panel { grid-template-columns: 1fr; }
            .partner-callout { grid-template-columns: 1fr; text-align: center; padding: 1.8rem; }
            .partner-callout__cta { justify-content: center; }
            .glass-panel { padding: 2rem; gap: 1.5rem; }
            .calc-container { padding: 2rem; }
            .calc-results-grid { grid-template-columns: 1fr; }
            footer { padding-bottom: 8rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .fade-up { opacity: 1; transform: none; }
        }

        :focus-visible { outline: 2px solid var(--accent-gold); outline-offset: 3px; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container nav-content">
            <div class="logo">Deixe<span>IR</span></div>
            <a href="#destinar" class="btn-cta btn-nav">Destinar Agora</a>
        </div>
    </nav>

    <!-- HERO -->
    <header class="hero">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        <div class="container hero-grid">
            <div class="hero-content">
                <div class="fade-up delay-1" style="display:flex; flex-wrap:wrap; align-items:center; gap:0;">
                    <span class="hero-tag">
                        @if($settings->deadline_label)
                            {{ $settings->deadline_label }}@if($daysLeft !== null && $daysLeft > 0 && $daysLeft <= 90) · faltam {{ $daysLeft }} {{ $daysLeft === 1 ? 'dia' : 'dias' }}@endif
                        @else
                            Ação pública · Araraquara
                        @endif
                    </span>
                    @if($partner)
                        <span class="hero-tag hero-tag--partner">via {{ $partner->name }}</span>
                    @endif
                </div>

                <h1 class="fade-up delay-2">{{ $settings->hero_title }}</h1>
                <p class="hero-sub fade-up delay-3">{{ $settings->hero_subtitle }}</p>

                <a href="#destinar-dados" class="btn-cta fade-up delay-4">
                    Como fazer a destinação
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            </div>

            <div class="percentage-display fade-up delay-3">
                <div class="percentage-number">6%</div>
                <p class="percentage-sub">do seu imposto pode ficar na nossa cidade.</p>
            </div>
        </div>
    </header>

    <!-- IMPACT (comparativo) -->
    <section class="impact">
        <div class="container">
            <div class="section-head">
                <h2>O caminho do seu dinheiro.</h2>
                <p>Hoje o seu imposto vai inteiro pra União. Com a destinação, uma parte fica em Araraquara, financiando projetos aprovados e fiscalizados pelo conselho.</p>
            </div>

            <div class="impact-grid">
                <div class="impact-card destructive">
                    <div class="impact-title">
                        <span>Hoje</span>
                        <span class="impact-value">100%</span>
                    </div>
                    <p>Vai inteiro para o Governo Federal.</p>
                    <div class="modern-bar-bg"><div class="modern-bar-fill fill-red"></div></div>
                </div>

                <div class="impact-card constructive">
                    <div class="impact-title">
                        <span>Com a destinação</span>
                        <span class="impact-value" style="color: var(--accent-gold);">6%</span>
                    </div>
                    <p>Fica em Araraquara, em projetos para crianças e adolescentes.</p>
                    <div class="modern-bar-bg">
                        <div class="bar-split">
                            <div class="fill-gray"></div>
                            <div class="fill-gold">6%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VIDEO -->
    @if($youtubeId || $settings->video_vertical_url || $settings->video_horizontal_url)
        <section class="video-section">
            <div class="container">
                <div class="section-head">
                    <h2>Entenda em 1 minuto</h2>
                </div>
                @if($youtubeId)
                    <div class="video-wrap">
                        <iframe
                            src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0&modestbranding=1"
                            title="Vídeo da campanha"
                            frameborder="0"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                            style="width:100%;height:100%;display:block;border:0;"></iframe>
                    </div>
                @else
                    <div class="video-wrap @unless($settings->video_horizontal_url)video-wrap--vertical @endunless">
                        <video controls playsinline preload="none"
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
    @endif

    <!-- DADOS DO FUNDO (CNPJ) -->
    <section class="action-section" id="destinar-dados">
        <div class="container">
            <div class="section-head" style="margin-bottom:2rem; text-align:left;">
                <h2>Destinação segura e transparente</h2>
                <p>O dinheiro vai direto pro {{ $settings->council_name }}. A Vivensi apenas divulga.</p>
            </div>

            @if($partner && $mailtoHref)
                <div class="partner-callout">
                    <div class="partner-callout__content">
                        <span class="partner-callout__eyebrow">Indicação de entidade</span>
                        <p class="partner-callout__title">Você veio pelo link da <strong>{{ $partner->name }}</strong>. No e-mail ao conselho, indique essa entidade pra receber o apoio.</p>
                        <p class="partner-callout__hint">Preparamos um rascunho de e-mail — você só anexa o comprovante do PIX.</p>
                    </div>
                    <a href="{{ $mailtoHref }}" class="partner-callout__cta">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Preparar e-mail
                    </a>
                </div>
            @endif

            <div class="glass-panel">
                <div class="data-group">
                    <span class="data-label">CNPJ · Chave PIX</span>
                    <span class="data-value">{{ $settings->fund_cnpj }}</span>
                    <button type="button" class="btn-copy" data-copy="{{ $settings->fund_cnpj }}" onclick="copyField(this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        Copiar CNPJ
                    </button>
                </div>

                <div class="data-group">
                    <span class="data-label">E-mail para comprovante</span>
                    <span class="data-value">{{ $settings->fund_email }}</span>
                    <button type="button" class="btn-copy" data-copy="{{ $settings->fund_email }}" onclick="copyField(this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        Copiar e-mail
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- CAMINHOS -->
    @if(!empty($settings->path1_steps) || !empty($settings->path2_steps))
        <section class="paths">
            <div class="container">
                <div class="section-head">
                    <h2>Dois caminhos. Escolha um.</h2>
                    <p>Você pode destinar via PIX até 31/12 (até 6%) ou direto na sua declaração do IR (até 3%).</p>
                </div>

                <div class="paths-grid">
                    @if(!empty($settings->path1_steps))
                        <div class="path-card">
                            <span class="path-tag">Caminho 1 · PIX até 31/12</span>
                            <h3>Doe agora, abata 6% depois na declaração.</h3>
                            <ol class="steps">
                                @foreach($settings->path1_steps as $step)
                                    <li>
                                        <div>
                                            <strong>{{ $step['title'] ?? '' }}</strong>
                                            <span>{{ $step['text'] ?? '' }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    @if(!empty($settings->path2_steps))
                        <div class="path-card">
                            <span class="path-tag">Caminho 2 · Na declaração</span>
                            <h3>Destine 3% direto pelo programa do IR.</h3>
                            <ol class="steps">
                                @foreach($settings->path2_steps as $step)
                                    <li>
                                        <div>
                                            <strong>{{ $step['title'] ?? '' }}</strong>
                                            <span>{{ $step['text'] ?? '' }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>

                @if($settings->cost_text)
                    <div class="cost-strip">
                        <strong>Quanto custa:</strong> {{ $settings->cost_text }}
                    </div>
                @endif
            </div>
        </section>
    @endif

    <!-- CALCULADORA -->
    @if($settings->show_simulator)
        <section class="calculator">
            <div class="container">
                <div class="calc-container">
                    <h2>Simule sua destinação</h2>
                    <p>Digite o valor do seu Imposto de Renda devido abaixo.</p>

                    <div class="calc-input-wrap">
                        <span>R$</span>
                        <input type="text" id="input-imposto" class="calc-input" inputmode="decimal" placeholder="0,00" autocomplete="off">
                    </div>

                    <div class="calc-results-grid">
                        <div class="calc-res-card">
                            <div class="calc-res-title">Via PIX · até 6%</div>
                            <div class="calc-res-value" id="res-pix">R$ 0,00</div>
                        </div>
                        <div class="calc-res-card">
                            <div class="calc-res-title">Via declaração · até 3%</div>
                            <div class="calc-res-value" id="res-ir">R$ 0,00</div>
                        </div>
                    </div>
                    <p class="calc-hint">Valores de referência. Confirme no programa oficial do IR.</p>
                </div>
            </div>
        </section>
    @endif

    <!-- PARCEIRAS -->
    @if($settings->show_partners_wall && $partners->count())
        <section class="partners">
            <div class="container">
                <div class="section-head">
                    <h2>{{ $partners->count() }} entidades de Araraquara participam.</h2>
                    <p>Você pode indicar o nome de uma delas no e-mail ao conselho pra que a verba apoie o trabalho que ela faz.</p>
                </div>
                <div class="partners-grid">
                    @foreach($partners as $p)
                        <div class="partner-cell">
                            @if($p->logo_url)
                                <img src="{{ $p->logo_url }}" alt="{{ $p->name }}" loading="lazy">
                            @endif
                            <div class="partner-cell__name">{{ $p->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- FAQ -->
    @if(!empty($settings->faq_items))
        <section class="faq">
            <div class="container">
                <div class="section-head">
                    <h2>Dúvidas frequentes</h2>
                </div>
                <div class="faq-wrap">
                    @foreach($settings->faq_items as $item)
                        <details class="faq-item">
                            <summary>{{ $item['q'] ?? '' }}</summary>
                            <p>{{ $item['a'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>{{ $settings->footer_tagline }}</p>
            <p><a href="{{ url('/') }}">vivensi.app.br</a></p>
            @if($settings->footer_legal_text)
                <p>{{ $settings->footer_legal_text }}</p>
            @endif
        </div>
    </footer>

    @if($settings->show_share_bar)
        <div class="share-bar">
            <a href="https://wa.me/?text={{ urlencode($shareMsg) }}" target="_blank" rel="noopener" class="share-btn share-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.4-.1-.6.1-.2.3-.7.9-.8 1-.1.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.4.1-.6.1-.1.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.7.3-.3.3-.9.9-.9 2.2 0 1.3 1 2.6 1.1 2.7.1.2 1.9 2.9 4.6 4 .6.3 1.1.4 1.5.5.6.2 1.2.2 1.7.1.5-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.4zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 4.9L2 22l5.3-1.4c1.4.7 3 1.1 4.7 1.1 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.3.9.9-3.2-.2-.3C3.8 14.6 3.3 13.3 3.3 12c0-4.8 3.9-8.7 8.7-8.7s8.7 3.9 8.7 8.7-3.9 8-8.7 8z"/></svg>
                Compartilhar no WhatsApp
            </a>
            <button type="button" class="share-btn share-btn--ghost" data-link="{{ $pageUrl }}" onclick="copyShareLink(this)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.72-1.71"></path></svg>
                <span class="share-link-label">Copiar link</span>
            </button>
        </div>
    @endif

    <script>
        function copyField(btn) {
            const txt = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(txt).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML = '✓ Copiado!';
                btn.classList.add('success');
                setTimeout(() => { btn.innerHTML = original; btn.classList.remove('success'); }, 1800);
            });
        }
        function copyShareLink(btn) {
            const link = btn.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                btn.classList.add('success');
                const label = btn.querySelector('.share-link-label');
                const old = label.textContent;
                label.textContent = 'Link copiado';
                setTimeout(() => { label.textContent = old; btn.classList.remove('success'); }, 1800);
            });
        }

        // Calculadora
        const inputImp = document.getElementById('input-imposto');
        if (inputImp) {
            const parseBr = v => {
                if (!v) return 0;
                const clean = String(v).replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.');
                const n = parseFloat(clean);
                return isNaN(n) ? 0 : n;
            };
            const fmt = n => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(n);
            const update = () => {
                const base = parseBr(inputImp.value);
                document.getElementById('res-pix').textContent = fmt(base * 0.06);
                document.getElementById('res-ir').textContent = fmt(base * 0.03);
            };
            inputImp.addEventListener('input', update);
        }
    </script>
</body>
</html>
