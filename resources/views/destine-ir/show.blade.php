@php
    $pageUrl = $partner
        ? route('destine-ir.partner', ['slug' => $partner->slug])
        : route('destine-ir.show');

    $ogTitle = $settings->og_title ?: $settings->hero_title;
    $ogDescription = $settings->og_description ?: $settings->hero_subtitle;
    $ogImage = $settings->og_image_url ?: ($settings->video_poster_url ?: asset('vivensi-og.jpg'));

    $deadlineDate = $settings->deadline_date;
    $daysLeft = null;
    if ($deadlineDate) {
        $diff = now()->startOfDay()->diffInDays($deadlineDate->copy()->startOfDay(), false);
        $daysLeft = (int) $diff;
    }

    // Mensagem pré-escrita de compartilhamento
    $shareMsg = $partner
        ? "Olá! A entidade \"{$partner->name}\" está participando desta ação em Araraquara. Parte do seu Imposto de Renda pode virar apoio pra crianças e adolescentes da nossa cidade, sem você pagar nada a mais. Veja como: {$pageUrl}"
        : "Parte do seu Imposto de Renda pode ficar em Araraquara e apoiar crianças e adolescentes da nossa cidade — sem você pagar nada a mais. Veja o passo a passo: {$pageUrl}";
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ogTitle }}</title>
    <meta name="description" content="{{ $ogDescription }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph (WhatsApp / Facebook / Insta) --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Vivensi">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    <link rel="canonical" href="{{ $pageUrl }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --petrol: #0b4f6c;
            --petrol-dark: #073a52;
            --sun: #f5b027;
            --green: #2a9d4a;
            --cream: #fff8ee;
            --ink: #0a1f2e;
            --mute: #556b7b;
            --line: #e3e9ee;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--ink); background: var(--cream);
            line-height: 1.6; -webkit-font-smoothing: antialiased;
        }
        a { color: var(--petrol); }
        .container { max-width: 720px; margin: 0 auto; padding: 0 20px; }

        /* Deadline banner */
        .deadline-bar {
            background: var(--sun); color: var(--ink);
            text-align: center; padding: 10px 16px; font-weight: 700; font-size: .92rem;
        }
        .deadline-bar strong { font-weight: 800; }

        /* Hero */
        .hero { background: var(--petrol); color: #fff; padding: 36px 0 28px; }
        .hero h1 {
            font-size: 1.75rem; line-height: 1.2; font-weight: 800;
            margin: 0 0 14px; letter-spacing: -.5px;
        }
        .hero p { font-size: 1.05rem; opacity: .92; margin: 0 0 22px; }
        .hero .partner-hint {
            display: inline-block; margin-bottom: 14px;
            padding: 6px 14px; background: rgba(255,255,255,.14);
            border-radius: 999px; font-size: .82rem; font-weight: 600;
        }

        /* Vídeo */
        .video-wrap {
            margin: 20px 0 10px; border-radius: 16px; overflow: hidden;
            background: rgba(0,0,0,.3); aspect-ratio: 9 / 16;
            max-width: 360px; margin-left: auto; margin-right: auto;
        }
        @media (min-width: 740px) {
            .video-wrap { aspect-ratio: 16 / 9; max-width: 100%; }
        }
        .video-wrap video { width: 100%; height: 100%; display: block; object-fit: cover; background: #000; }

        /* Section genérica */
        section { padding: 36px 0; border-top: 1px solid var(--line); background: #fff; }
        section:nth-of-type(odd) { background: var(--cream); }
        h2 { font-size: 1.4rem; font-weight: 800; margin: 0 0 18px; color: var(--petrol); letter-spacing: -.3px; }
        h3 { font-size: 1.1rem; font-weight: 700; margin: 0 0 12px; }
        p { margin: 0 0 14px; }

        /* Caminhos */
        .path {
            background: #fff; border: 1px solid var(--line); border-radius: 16px;
            padding: 20px 22px; margin-bottom: 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,.03);
        }
        .path .path-head {
            display: flex; align-items: center; gap: 12px; margin-bottom: 14px;
        }
        .path .path-tag {
            background: var(--sun); color: var(--ink); font-weight: 800;
            padding: 4px 10px; border-radius: 999px; font-size: .78rem;
        }
        .path h3 { color: var(--petrol-dark); margin: 0; }
        .path ol { padding-left: 22px; margin: 0; }
        .path ol li { margin-bottom: 10px; }
        .path ol li strong { color: var(--petrol-dark); }

        /* Fundo info card */
        .fund-card {
            background: #fff; border: 2px solid var(--petrol);
            border-radius: 16px; padding: 20px; margin-top: 10px;
        }
        .fund-card .fund-label { font-size: .78rem; color: var(--mute); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .fund-card .fund-value { font-size: 1.1rem; font-weight: 700; color: var(--petrol-dark); font-family: ui-monospace, 'SF Mono', monospace; word-break: break-all; }
        .fund-row { margin-bottom: 14px; }
        .copy-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--petrol); color: #fff; border: none;
            padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: .85rem;
            cursor: pointer; margin-top: 6px;
            font-family: inherit;
        }
        .copy-btn.ok { background: var(--green); }

        /* Simulador */
        .sim {
            background: linear-gradient(135deg, var(--petrol) 0%, var(--petrol-dark) 100%);
            color: #fff; border-radius: 16px; padding: 26px 22px; margin-top: 16px;
        }
        .sim label { display: block; font-weight: 700; margin-bottom: 8px; font-size: .95rem; }
        .sim input {
            width: 100%; padding: 14px 16px; border-radius: 10px; border: none;
            font-size: 1.05rem; font-weight: 700; color: var(--ink); font-family: inherit;
        }
        .sim-result {
            margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .sim-result__box {
            background: rgba(255,255,255,.1); backdrop-filter: blur(4px);
            padding: 14px; border-radius: 10px;
        }
        .sim-result__label { font-size: .78rem; opacity: .85; font-weight: 600; }
        .sim-result__value { font-size: 1.4rem; font-weight: 800; color: var(--sun); margin-top: 4px; font-family: ui-monospace, monospace; }
        .sim-hint { font-size: .82rem; opacity: .8; margin-top: 14px; }

        /* FAQ */
        .faq details {
            background: #fff; border: 1px solid var(--line);
            border-radius: 12px; padding: 14px 18px; margin-bottom: 10px;
        }
        .faq summary {
            cursor: pointer; font-weight: 700; color: var(--petrol-dark);
            list-style: none; display: flex; justify-content: space-between; align-items: center;
        }
        .faq summary::after { content: '+'; font-size: 1.4rem; color: var(--sun); line-height: 1; }
        .faq details[open] summary::after { content: '−'; }
        .faq details[open] { background: var(--cream); }
        .faq summary::-webkit-details-marker { display: none; }

        /* Parceiras */
        .partners-wall { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        @media (min-width: 500px) { .partners-wall { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 740px) { .partners-wall { grid-template-columns: repeat(4, 1fr); } }
        .partners-wall .partner {
            background: #fff; border: 1px solid var(--line); border-radius: 12px;
            padding: 14px 10px; text-align: center; aspect-ratio: 1;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
        }
        .partners-wall .partner img { max-width: 100%; max-height: 60px; object-fit: contain; }
        .partners-wall .partner__name { font-size: .78rem; font-weight: 700; color: var(--petrol-dark); line-height: 1.3; }
        .partner-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--sun); color: var(--ink); padding: 10px 16px;
            border-radius: 12px; font-weight: 700; margin-bottom: 20px;
        }

        /* Share bar */
        .share-bar {
            position: sticky; bottom: 0; background: #fff;
            border-top: 1px solid var(--line); padding: 14px 20px;
            display: flex; gap: 10px; justify-content: center; z-index: 10;
            box-shadow: 0 -4px 20px rgba(0,0,0,.06);
        }
        .share-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 16px; border-radius: 10px; font-weight: 700; font-size: .9rem;
            text-decoration: none; border: none; cursor: pointer;
            font-family: inherit;
        }
        .share-wpp { background: #25d366; color: #fff; }
        .share-link { background: var(--petrol); color: #fff; }
        .share-link.ok { background: var(--green); }

        /* Footer */
        footer { background: var(--petrol-dark); color: #cde3ec; padding: 30px 0; text-align: center; font-size: .88rem; }
        footer a { color: var(--sun); text-decoration: none; }
        footer .legal { font-size: .78rem; opacity: .7; margin-top: 10px; max-width: 540px; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>

    @if($settings->show_deadline_banner && $settings->deadline_label)
        <div class="deadline-bar">
            <i aria-hidden="true">⏱</i>
            @if($daysLeft !== null && $daysLeft > 0 && $daysLeft <= 90)
                <strong>Faltam {{ $daysLeft }} {{ $daysLeft === 1 ? 'dia' : 'dias' }}:</strong>
            @endif
            {{ $settings->deadline_label }}
        </div>
    @endif

    <header class="hero">
        <div class="container">
            @if($partner)
                <span class="partner-hint">Você veio pelo link da entidade <strong>{{ $partner->name }}</strong></span>
            @endif
            <h1>{{ $settings->hero_title }}</h1>
            <p>{{ $settings->hero_subtitle }}</p>

            @if($settings->video_vertical_url || $settings->video_horizontal_url)
                <div class="video-wrap">
                    <video
                        controls
                        playsinline
                        preload="none"
                        @if($settings->video_poster_url) poster="{{ $settings->video_poster_url }}" @endif>
                        @if($settings->video_horizontal_url)
                            <source src="{{ $settings->video_horizontal_url }}" type="video/mp4" media="(min-width: 740px)">
                        @endif
                        @if($settings->video_vertical_url)
                            <source src="{{ $settings->video_vertical_url }}" type="video/mp4">
                        @elseif($settings->video_horizontal_url)
                            <source src="{{ $settings->video_horizontal_url }}" type="video/mp4">
                        @endif
                        Seu navegador não suporta vídeo HTML5.
                    </video>
                </div>
            @endif
        </div>
    </header>

    @if($settings->what_is_text)
        <section>
            <div class="container">
                <h2>O que é a destinação</h2>
                <p>{{ $settings->what_is_text }}</p>
            </div>
        </section>
    @endif

    @if($settings->who_can_text)
        <section>
            <div class="container">
                <h2>Quem pode destinar</h2>
                <p>{{ $settings->who_can_text }}</p>
            </div>
        </section>
    @endif

    @if($settings->show_simulator)
        <section>
            <div class="container">
                <h2>Simulador rápido</h2>
                <p style="color:var(--mute);">Digite seu imposto devido pra ver quanto você pode destinar:</p>
                <div class="sim">
                    <label for="sim-input">Imposto devido (R$)</label>
                    <input type="text" id="sim-input" inputmode="decimal" placeholder="Ex: 3.500,00">
                    <div class="sim-result">
                        <div class="sim-result__box">
                            <div class="sim-result__label">Até 6% (Caminho 1)</div>
                            <div class="sim-result__value" id="sim-6">R$ 0,00</div>
                        </div>
                        <div class="sim-result__box">
                            <div class="sim-result__label">Até 3% (Caminho 2)</div>
                            <div class="sim-result__value" id="sim-3">R$ 0,00</div>
                        </div>
                    </div>
                    <div class="sim-hint">Valores de referência. Confirme sempre no programa oficial do IR.</div>
                </div>
            </div>
        </section>
    @endif

    @if(!empty($settings->path1_steps))
        <section>
            <div class="container">
                <div class="path">
                    <div class="path-head">
                        <span class="path-tag">Caminho 1</span>
                        <h3>Até 31 de dezembro (até 6%)</h3>
                    </div>
                    <ol>
                        @foreach($settings->path1_steps as $step)
                            <li><strong>{{ $step['title'] ?? '' }}.</strong> {{ $step['text'] ?? '' }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>
    @endif

    @if(!empty($settings->path2_steps))
        <section>
            <div class="container">
                <div class="path">
                    <div class="path-head">
                        <span class="path-tag">Caminho 2</span>
                        <h3>Na declaração do IR (até 3%)</h3>
                    </div>
                    <ol>
                        @foreach($settings->path2_steps as $step)
                            <li><strong>{{ $step['title'] ?? '' }}.</strong> {{ $step['text'] ?? '' }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>
    @endif

    <section>
        <div class="container">
            <h2>Dados do fundo</h2>
            <p style="color:var(--mute);">A chave PIX é o CNPJ abaixo. Tudo vai direto pro {{ $settings->council_name }}.</p>
            <div class="fund-card">
                <div class="fund-row">
                    <div class="fund-label">Nome</div>
                    <div class="fund-value" style="font-family:inherit; font-size:1rem;">{{ $settings->fund_name }}</div>
                </div>
                <div class="fund-row">
                    <div class="fund-label">CNPJ / Chave PIX</div>
                    <div class="fund-value" id="fund-cnpj">{{ $settings->fund_cnpj }}</div>
                    <button type="button" class="copy-btn" data-copy="{{ $settings->fund_cnpj }}" onclick="copyField(this)">📋 Copiar CNPJ</button>
                </div>
                <div class="fund-row">
                    <div class="fund-label">E-mail do conselho</div>
                    <div class="fund-value" style="font-family:inherit; font-size:1rem;">{{ $settings->fund_email }}</div>
                    <button type="button" class="copy-btn" data-copy="{{ $settings->fund_email }}" onclick="copyField(this)">📋 Copiar e-mail</button>
                </div>
            </div>
        </div>
    </section>

    @if($settings->cost_text)
        <section>
            <div class="container">
                <h2>Quanto custa</h2>
                <p>{{ $settings->cost_text }}</p>
            </div>
        </section>
    @endif

    @if(!empty($settings->faq_items))
        <section>
            <div class="container faq">
                <h2>Perguntas frequentes</h2>
                @foreach($settings->faq_items as $item)
                    <details>
                        <summary>{{ $item['q'] ?? '' }}</summary>
                        <p style="margin:12px 0 0; color:var(--mute);">{{ $item['a'] ?? '' }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    @if($settings->show_partners_wall && $partners->count())
        <section>
            <div class="container">
                <h2>Entidades que participam desta ação</h2>
                <p style="color:var(--mute);">São as organizações de Araraquara que você pode indicar no e-mail ao conselho:</p>
                <div class="partners-wall">
                    @foreach($partners as $p)
                        <div class="partner">
                            @if($p->logo_url)
                                <img src="{{ $p->logo_url }}" alt="{{ $p->name }}" loading="lazy">
                            @endif
                            <div class="partner__name">{{ $p->name }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <footer>
        <div class="container">
            <p style="margin:0; font-weight:600;">{{ $settings->footer_tagline }}</p>
            <p style="margin:6px 0 0;">
                <a href="{{ url('/') }}">vivensi.app.br</a>
            </p>
            @if($settings->footer_legal_text)
                <p class="legal">{{ $settings->footer_legal_text }}</p>
            @endif
        </div>
    </footer>

    @if($settings->show_share_bar)
        <div class="share-bar">
            <a href="https://wa.me/?text={{ urlencode($shareMsg) }}" target="_blank" rel="noopener" class="share-btn share-wpp">
                <span>📱</span> Compartilhar no WhatsApp
            </a>
            <button type="button" class="share-btn share-link" data-link="{{ $pageUrl }}" onclick="copyShareLink(this)">
                <span>🔗</span> <span class="share-link__label">Copiar link</span>
            </button>
        </div>
    @endif

    <script>
        // Copiar campos (CNPJ, e-mail)
        function copyField(btn) {
            const txt = btn.getAttribute('data-copy');
            navigator.clipboard.writeText(txt).then(() => {
                const old = btn.textContent;
                btn.textContent = '✓ Copiado!';
                btn.classList.add('ok');
                setTimeout(() => { btn.textContent = old; btn.classList.remove('ok'); }, 1800);
            });
        }

        // Copiar link de compartilhamento
        function copyShareLink(btn) {
            const link = btn.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                btn.classList.add('ok');
                const label = btn.querySelector('.share-link__label');
                const old = label.textContent;
                label.textContent = 'Copiado!';
                setTimeout(() => { label.textContent = old; btn.classList.remove('ok'); }, 1800);
            });
        }

        // Simulador: parse pt-BR → number
        const simInput = document.getElementById('sim-input');
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
                document.getElementById('sim-6').textContent = fmt(base * 0.06);
                document.getElementById('sim-3').textContent = fmt(base * 0.03);
            };
            simInput.addEventListener('input', update);
        }
    </script>
</body>
</html>
