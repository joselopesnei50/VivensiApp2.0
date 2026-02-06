<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isDraftPreview = (($page->status ?? 'draft') !== 'published');
        $seoTitle = trim((string) (($page->settings['seo_title'] ?? '') ?: $page->title));
        $seoDesc = trim((string) ($page->settings['seo_description'] ?? ''));
        $canonical = url('/lp/' . $page->slug);
        $ogImage = \App\Support\LandingPageSanitizer::url($page->settings['og_image_url'] ?? null, '');
        $favicon = \App\Support\LandingPageSanitizer::url($page->settings['favicon_url'] ?? null, '');
        $primary = \App\Support\LandingPageSanitizer::cssColor($page->settings['theme_color'] ?? null, '#6366f1');
    @endphp

    <title>{{ $seoTitle }}</title>
    <link rel="canonical" href="{{ $canonical }}">

    @if($seoDesc !== '')
        <meta name="description" content="{{ $seoDesc }}">
    @endif

    <meta name="robots" content="{{ $isDraftPreview ? 'noindex,nofollow' : 'index,follow' }}">
    <meta name="theme-color" content="{{ $primary }}">

    <!-- Open Graph -->
    <meta property="og:locale" content="pt_BR">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    @if($seoDesc !== '')
        <meta property="og:description" content="{{ $seoDesc }}">
    @endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="Vivensi">
    @if($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="{{ $ogImage !== '' ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    @if($seoDesc !== '')
        <meta name="twitter:description" content="{{ $seoDesc }}">
    @endif
    @if($ogImage !== '')
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    @if($favicon !== '')
        <link rel="icon" href="{{ $favicon }}">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: {{ $primary }};
            --text-dark: #0f172a;
            --text-light: #64748b;
        }
        body { 
            font-family: 'Outfit', sans-serif; 
            margin: 0; padding: 0; 
            color: var(--text-dark); 
            line-height: 1.6; 
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 25px; }
        
        /* Animations */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        section { animation: fadeIn 0.8s ease-out forwards; }

        /* Hero */
        .section-hero { 
            padding: 120px 0; 
            text-align: center; 
        }
        .hero-title { 
            font-size: clamp(2.5rem, 8vw, 4.5rem); 
            font-weight: 800; 
            margin-bottom: 25px; 
            line-height: 1.1; 
            letter-spacing: -2px;
        }
        .hero-subtitle { 
            font-size: 1.25rem; 
            margin-bottom: 45px; 
            max-width: 750px; 
            margin-inline: auto; 
            font-weight: 400;
        }
        .btn-cta { 
            background: var(--primary); 
            color: white; 
            padding: 18px 50px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 700; 
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }
        .btn-cta:hover { transform: scale(1.05); filter: brightness(1.1); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }

        /* General Sections */
        h2 { font-size: 3rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 30px; }

        .feature-card { 
            padding: 40px; 
            background: white;
            border-radius: 24px; 
            transition: all 0.3s; 
            border: 1px solid #f1f5f9;
        }
        .feature-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0,0,0,0.05); }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.8rem; }
            section { padding: 60px 0 !important; }
        }
    </style>
</head>
<body>

    @foreach($sections as $section)


        @if($section->type == 'hero')
            <section class="section-hero" style="background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? $section->content['bg_color'] ?? null, '#f8fafc') }}; color: {{ \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff') }}; position: relative; overflow: hidden;">
                <!-- Efeito de Fundo -->
                <div style="position: absolute; top: -10%; right: -5%; width: 40%; height: 60%; background: rgba(255,255,255,0.05); border-radius: 50%; blur: 100px;"></div>
                
                <div class="container" style="position: relative; z-index: 2;">
                    <h1 class="hero-title">{{ $section->content['title'] ?? 'Título Impactante' }}</h1>
                    <p class="hero-subtitle" style="color: {{ (($section->content['text_color'] ?? '') == '#ffffff') ? 'rgba(255,255,255,0.8)' : '#64748b' }}">{{ $section->content['subtitle'] ?? 'Uma descrição sobre sua causa.' }}</p>
                    <a href="#contato" class="btn-cta" style="box-shadow: 0 10px 20px rgba(0,0,0,0.15);">{{ $section->content['button_text'] ?? 'Saiba Mais' }}</a>
                </div>
            </section>
        @endif

        @if($section->type == 'stats')
            <section style="padding: 60px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#0f172a') }}; color: {{ \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff') }};">
                <div class="container">
                    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 30px; text-align: center;">
                        @foreach($section->content['items'] ?? [] as $stat)
                        <div style="flex: 1; min-width: 200px;">
                            <h2 style="font-size: 3rem; margin: 0; color: {{ \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff') }};">{{ $stat['value'] ?? '0' }}</h2>
                            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; opacity: 0.8;">{{ $stat['label'] ?? 'Impacto' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'header_nav')
            <nav style="background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }}; padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px);">
                <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
                    <img src="{{ \App\Support\LandingPageSanitizer::url($section->content['logo_url'] ?? null, '') }}" alt="Logo" style="height: 40px;">
                    <div style="display: flex; gap: 30px;">
                        @foreach($section->content['links'] ?? [] as $link)
                            <a href="{{ \App\Support\LandingPageSanitizer::url($link['url'] ?? null, '#') }}" style="text-decoration: none; color: {{ \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#1e293b') }}; font-weight: 600; font-size: 0.9rem; transition: color 0.3s;">{{ $link['label'] ?? 'Link' }}</a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif

        @if($section->type == 'who_we_are')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc') }};">
                <div class="container" style="display: flex; align-items: center; gap: 60px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; border-radius: 30px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                        <img src="{{ \App\Support\LandingPageSanitizer::url($section->content['image_url'] ?? null, '') }}" style="width: 100%; display: block;">
                    </div>
                    <div style="flex: 1; min-width: 300px;">
                        <span style="color: var(--primary); text-transform: uppercase; letter-spacing: 2px; font-weight: 800; font-size: 0.8rem;">Saiba Mais</span>
                        <h2 style="margin-top: 15px;">{{ $section->content['title'] ?? 'Quem Somos' }}</h2>
                        <h4 style="color: #64748b; margin-bottom: 25px;">{{ $section->content['subtitle'] ?? '' }}</h4>
                        <p style="font-size: 1.1rem; color: #475569; line-height: 1.8;">{{ $section->content['text'] ?? '' }}</p>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'services_grid')
            <section style="padding: 100px 0; background: #ffffff;">
                <div class="container">
                    <h2 style="text-align: center; margin-bottom: 60px;">{{ $section->content['title'] ?? 'Nossos Serviços' }}</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
                        @foreach($section->content['items'] ?? [] as $service)
                            <div class="feature-card" style="padding: 0; text-align: left; overflow: hidden;">
                                <img src="{{ \App\Support\LandingPageSanitizer::url($service['image'] ?? null, '') }}" style="width: 100%; height: 220px; object-fit: cover;">
                                <div style="padding: 30px;">
                                    <h3 style="margin-top: 0; margin-bottom: 15px;">{{ $service['title'] ?? 'Serviço' }}</h3>
                                    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">{{ $service['desc'] ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'cta_banner')
            @php
                $ctaBannerBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? $section->content['bg_color'] ?? null, '#4f46e5');
                $ctaBannerText = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
                $ctaBannerUrl = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? null, '#contato');
            @endphp
            <section style="padding: 70px 0; background: {{ $ctaBannerBg }}; color: {{ $ctaBannerText }};">
                <div class="container" style="display:flex; align-items:center; justify-content: space-between; gap: 25px; flex-wrap: wrap;">
                    <div style="flex:1; min-width: 280px;">
                        <h2 style="margin:0 0 10px 0; font-size: 2.2rem;">{{ $section->content['title'] ?? 'Chamada para ação' }}</h2>
                        <p style="margin:0; opacity: .9; font-size: 1.05rem;">{{ $section->content['subtitle'] ?? '' }}</p>
                    </div>
                    <div>
                        <a class="btn-cta" href="{{ $ctaBannerUrl }}" style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.65);">
                            {{ $section->content['button_text'] ?? 'Saiba mais' }}
                        </a>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'faq')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container" style="max-width: 950px;">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Perguntas Frequentes' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 40px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    <div style="display:grid; gap: 14px;">
                        @foreach($section->content['items'] ?? [] as $it)
                            <details style="background:#fff; border:1px solid #e2e8f0; border-radius: 16px; padding: 16px 18px; box-shadow: 0 10px 25px rgba(15,23,42,.03);">
                                <summary style="cursor:pointer; font-weight: 800; color:#0f172a; list-style:none;">
                                    {{ $it['q'] ?? 'Pergunta' }}
                                </summary>
                                <div style="margin-top: 10px; color:#475569; line-height: 1.7;">
                                    {{ $it['a'] ?? '' }}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'image_gallery')
            @php
                $galleryBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc');
            @endphp
            <section style="padding: 100px 0; background: {{ $galleryBg }};">
                <div class="container">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Galeria' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 45px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        @foreach($section->content['images'] ?? [] as $img)
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius: 20px; overflow:hidden; box-shadow: 0 18px 40px rgba(15,23,42,.06);">
                                @php
                                    $src = \App\Support\LandingPageSanitizer::url($img['url'] ?? null, '');
                                @endphp
                                @if($src !== '')
                                    <a href="{{ $src }}"
                                       data-lp-lightbox="1"
                                       data-src="{{ $src }}"
                                       data-cap="{{ $img['caption'] ?? '' }}"
                                       onclick="return window.vivensiLpOpenLightbox ? window.vivensiLpOpenLightbox(event, this) : true;"
                                       style="display:block; cursor: zoom-in;">
                                        <img src="{{ $src }}" alt="{{ $img['caption'] ?? 'Foto' }}" style="width: 100%; height: 190px; object-fit: cover; display:block;">
                                    </a>
                                @else
                                    <div style="width: 100%; height: 190px; display:flex; align-items:center; justify-content:center; background:#f8fafc; color:#94a3b8;">
                                        Sem imagem
                                    </div>
                                @endif
                                @if(!empty($img['caption']))
                                    <div style="padding: 12px 14px; color:#475569; font-size:.9rem; font-weight:600;">
                                        {{ $img['caption'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'partners_logos')
            <section style="padding: 70px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Parceiros' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 35px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    <div style="display:flex; gap: 22px; flex-wrap: wrap; justify-content: center; align-items:center;">
                        @foreach($section->content['logos'] ?? [] as $logo)
                            @php
                                $plink = \App\Support\LandingPageSanitizer::url($logo['link'] ?? null, '#');
                                $plogo = \App\Support\LandingPageSanitizer::url($logo['logo_url'] ?? null, '');
                            @endphp
                            <a href="{{ $plink }}" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; justify-content:center; padding: 14px 18px; border:1px solid #e2e8f0; border-radius: 18px; background:#fff; text-decoration:none; color:inherit;">
                                @if($plogo !== '')
                                    <img src="{{ $plogo }}" alt="{{ $logo['name'] ?? 'Parceiro' }}" style="max-height: 34px; max-width: 180px;">
                                @else
                                    <span style="color:#94a3b8; font-weight:900;">{{ $logo['name'] ?? 'Parceiro' }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'steps_timeline')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container" style="max-width: 1000px;">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Como funciona' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 45px;">{{ $section->content['subtitle'] }}</p>
                    @endif

                    <div style="display:grid; gap: 16px;">
                        @foreach($section->content['items'] ?? [] as $idx => $it)
                            <div style="display:flex; gap: 16px; align-items:flex-start; padding: 18px 18px; border: 1px solid #e2e8f0; border-radius: 18px; background: #fff; box-shadow: 0 18px 40px rgba(15,23,42,.04);">
                                <div style="min-width: 44px; height: 44px; border-radius: 14px; background: rgba(99,102,241,.12); display:flex; align-items:center; justify-content:center; font-weight: 900; color: var(--primary);">
                                    {{ (int) $idx + 1 }}
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight: 900; color:#0f172a; font-size: 1.1rem;">{{ $it['title'] ?? 'Etapa' }}</div>
                                    <div style="color:#475569; margin-top: 6px; line-height: 1.7;">{{ $it['desc'] ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'impact_cards')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc') }};">
                <div class="container">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Impacto' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 55px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
                        @foreach($section->content['items'] ?? [] as $it)
                            <div style="background:#fff; border: 1px solid #e2e8f0; border-radius: 22px; padding: 26px; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                                <div style="display:flex; align-items:center; gap: 12px; margin-bottom: 10px;">
                                    <div style="width: 44px; height: 44px; border-radius: 16px; background: rgba(99,102,241,.12); display:flex; align-items:center; justify-content:center;">
                                        <i class="fas {{ $it['icon'] ?? 'fa-star' }}" style="color: var(--primary);"></i>
                                    </div>
                                    <div style="font-weight: 900; color:#0f172a; font-size: 1.05rem;">{{ $it['title'] ?? 'Card' }}</div>
                                </div>
                                <div style="color:#475569; line-height: 1.7;">{{ $it['desc'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'before_after')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Antes e Depois' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 55px;">{{ $section->content['subtitle'] }}</p>
                    @endif

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
                        <div style="border: 1px solid #e2e8f0; border-radius: 24px; overflow:hidden; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                            <img src="{{ \App\Support\LandingPageSanitizer::url($section->content['left_image_url'] ?? null, '') }}" alt="{{ $section->content['left_title'] ?? 'Antes' }}" style="width: 100%; height: 240px; object-fit: cover; display:block;">
                            <div style="padding: 22px;">
                                <div style="display:inline-flex; gap:8px; align-items:center; padding:6px 12px; border-radius:999px; background:#f1f5f9; color:#0f172a; font-weight:900; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase;">
                                    {{ $section->content['left_title'] ?? 'Antes' }}
                                </div>
                                <div style="margin-top: 12px; color:#475569; line-height:1.7;">
                                    {{ $section->content['left_text'] ?? '' }}
                                </div>
                            </div>
                        </div>
                        <div style="border: 1px solid #e2e8f0; border-radius: 24px; overflow:hidden; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                            <img src="{{ \App\Support\LandingPageSanitizer::url($section->content['right_image_url'] ?? null, '') }}" alt="{{ $section->content['right_title'] ?? 'Depois' }}" style="width: 100%; height: 240px; object-fit: cover; display:block;">
                            <div style="padding: 22px;">
                                <div style="display:inline-flex; gap:8px; align-items:center; padding:6px 12px; border-radius:999px; background:rgba(99,102,241,.12); color: var(--primary); font-weight:900; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase;">
                                    {{ $section->content['right_title'] ?? 'Depois' }}
                                </div>
                                <div style="margin-top: 12px; color:#475569; line-height:1.7;">
                                    {{ $section->content['right_text'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'quick_donation')
            @php
                $qdBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? $section->content['bg_color'] ?? null, '#6366f1');
                $qdText = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
                $qdUrl = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? null, '#contato');
            @endphp
            <section style="padding: 90px 0; background: {{ $qdBg }}; color: {{ $qdText }};">
                <div class="container" style="max-width: 1050px;">
                    <div style="display:flex; align-items:flex-start; justify-content: space-between; gap: 25px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 280px;">
                            <h2 style="margin:0 0 10px 0; color: {{ $qdText }};">{{ $section->content['title'] ?? 'Doe em 1 minuto' }}</h2>
                            <p style="margin:0; opacity:.9; font-size: 1.05rem;">{{ $section->content['subtitle'] ?? '' }}</p>
                        </div>
                        <div style="flex: 1; min-width: 320px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); border-radius: 26px; padding: 22px; backdrop-filter: blur(10px);">
                            <div style="display:flex; gap: 12px; flex-wrap: wrap; justify-content:center; margin-bottom: 16px;">
                                @foreach($section->content['options'] ?? [] as $opt)
                                    @php $hi = (bool)($opt['highlight'] ?? false); @endphp
                                    <a href="{{ $qdUrl }}"
                                       style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; min-width: 120px; padding: 14px 16px; border-radius: 18px; font-weight: 900; letter-spacing:.02em; color: {{ $qdText }}; background: {{ $hi ? 'rgba(255,255,255,0.22)' : 'rgba(255,255,255,0.12)' }}; border: 1px solid rgba(255,255,255,0.35);">
                                        {{ $opt['label'] ?? 'R$ 0' }}
                                    </a>
                                @endforeach
                            </div>
                            <a class="btn-cta" href="{{ $qdUrl }}" style="width:100%; text-align:center; background: rgba(255,255,255,0.18); border: 2px solid rgba(255,255,255,0.65);">
                                {{ $section->content['button_text'] ?? 'Quero doar' }}
                            </a>
                            <div style="margin-top: 10px; font-size:.85rem; opacity:.85; text-align:center;">
                                Você confirma o valor na próxima etapa.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'pix_donation')
            @php
                $pixBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff');
                $pixText = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#0f172a');
                $qr = \App\Support\LandingPageSanitizer::url($section->content['qr_image_url'] ?? null, '');
                $payload = (string) ($section->content['pix_key_or_payload'] ?? '');
                $payloadId = 'pix-payload-' . $section->id;
            @endphp
            <section style="padding: 100px 0; background: {{ $pixBg }}; color: {{ $pixText }};">
                <div class="container" style="max-width: 1050px;">
                    <div style="text-align:center; margin-bottom: 40px;">
                        <h2 style="margin-bottom: 10px;">{{ $section->content['title'] ?? 'Doe via PIX' }}</h2>
                        @if(!empty($section->content['subtitle']))
                            <p style="margin:0; color:#64748b; max-width: 820px; margin-inline:auto;">{{ $section->content['subtitle'] }}</p>
                        @endif
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; align-items: start;">
                        <div style="border:1px solid #e2e8f0; border-radius: 24px; padding: 22px; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                            <div style="font-weight: 900; color:#0f172a; margin-bottom: 10px;">
                                <i class="fas fa-copy me-1"></i> PIX Copia e Cola
                            </div>
                            @if(!empty($section->content['recipient_name']))
                                <div style="color:#475569; font-weight:700; margin-bottom: 10px;">
                                    Destinatário: {{ $section->content['recipient_name'] }}
                                </div>
                            @endif

                            <textarea id="{{ $payloadId }}" readonly
                                      style="width:100%; min-height: 110px; padding: 14px; border-radius: 16px; border:1px solid #e2e8f0; background:#f8fafc; color:#0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: .85rem; line-height:1.4; resize: vertical;">{{ $payload }}</textarea>

                            <div style="display:flex; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                                <button type="button"
                                        onclick="(function(){var el=document.getElementById('{{ $payloadId }}'); if(!el) return; el.select(); try{document.execCommand('copy');}catch(e){} if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(el.value).catch(()=>{});} })();"
                                        style="background:#4f46e5; color:#fff; border:none; padding: 12px 16px; border-radius: 14px; font-weight: 900; cursor:pointer;">
                                    Copiar código PIX
                                </button>
                                @if(!empty($section->content['help_text']))
                                    <div style="color:#64748b; font-size:.9rem; align-self:center;">
                                        {{ $section->content['help_text'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div style="border:1px solid #e2e8f0; border-radius: 24px; padding: 22px; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06); text-align:center;">
                            <div style="font-weight: 900; color:#0f172a; margin-bottom: 12px;">
                                <i class="fas fa-qrcode me-1"></i> QR Code
                            </div>
                            @if($qr !== '')
                                <img src="{{ $qr }}" alt="QR Code PIX" style="width: 220px; height: 220px; object-fit: cover; border-radius: 18px; border: 1px solid #e2e8f0;">
                            @else
                                <div style="width: 220px; height: 220px; margin: 0 auto; border-radius: 18px; border: 1px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                                    Sem QR
                                </div>
                            @endif
                            <div style="margin-top: 12px; color:#64748b; font-size:.9rem;">
                                Aponte a câmera do app do seu banco para doar.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'cta_cards')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Como você pode ajudar' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 55px;">{{ $section->content['subtitle'] }}</p>
                    @endif

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px;">
                        @foreach($section->content['items'] ?? [] as $it)
                            <div style="background:#fff; border: 1px solid #e2e8f0; border-radius: 22px; padding: 26px; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                                <div style="display:flex; align-items:center; gap: 12px; margin-bottom: 10px;">
                                    <div style="width: 44px; height: 44px; border-radius: 16px; background: rgba(99,102,241,.12); display:flex; align-items:center; justify-content:center;">
                                        <i class="fas {{ $it['icon'] ?? 'fa-star' }}" style="color: var(--primary);"></i>
                                    </div>
                                    <div style="font-weight: 900; color:#0f172a; font-size: 1.05rem;">{{ $it['title'] ?? 'Ação' }}</div>
                                </div>
                                <div style="color:#475569; line-height: 1.7; margin-bottom: 16px;">{{ $it['desc'] ?? '' }}</div>
                                <a class="btn-cta" href="{{ \App\Support\LandingPageSanitizer::url($it['button_url'] ?? null, '#contato') }}" style="width:100%; text-align:center; padding: 12px; font-size: .85rem;">
                                    {{ $it['button_text'] ?? 'Saiba mais' }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'map_embed')
            @php
                $mapBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc');
                $embed = \App\Support\LandingPageSanitizer::googleMapsEmbedUrl($section->content['embed_url'] ?? null, '');
            @endphp
            <section style="padding: 100px 0; background: {{ $mapBg }};" id="localizacao">
                <div class="container" style="max-width: 1100px;">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Localização' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 35px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    @if(!empty($section->content['address']))
                        <p style="text-align:center; color:#475569; margin-top: 0; margin-bottom: 25px; font-weight: 700;">
                            <i class="fas fa-location-dot me-1" style="color: var(--primary);"></i> {{ $section->content['address'] }}
                        </p>
                    @endif

                    <div style="border-radius: 24px; overflow:hidden; border: 1px solid #e2e8f0; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                        @if($embed !== '')
                            <div style="position: relative; padding-bottom: 56.25%; height: 0;">
                                <iframe src="{{ $embed }}" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            </div>
                        @else
                            <div style="padding: 30px; text-align:center; color:#94a3b8;">
                                Mapa não configurado.
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'transparency_numbers')
            <section style="padding: 90px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container" style="max-width: 1100px;">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Transparência' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 45px;">{{ $section->content['subtitle'] }}</p>
                    @endif

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        @foreach($section->content['items'] ?? [] as $it)
                            <div style="background:#fff; border: 1px solid #e2e8f0; border-radius: 22px; padding: 26px; box-shadow: 0 24px 60px rgba(15,23,42,.06); text-align:center;">
                                <div style="font-weight: 900; font-size: 2.2rem; letter-spacing:-1px; color:#0f172a;">
                                    {{ $it['value'] ?? '0' }}
                                </div>
                                <div style="margin-top: 8px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.08em; font-size:.75rem;">
                                    {{ $it['label'] ?? 'Indicador' }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($section->content['note']))
                        <div style="margin-top: 22px; text-align:center; color:#64748b; font-size:.95rem;">
                            {{ $section->content['note'] }}
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($section->type == 'final_cta_form')
            @php
                $ctaBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? null, '#0f172a');
                $ctaText = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
            @endphp
            <section style="padding: 100px 0; background: {{ $ctaBg }}; color: {{ $ctaText }};" id="cta-final">
                <div class="container" style="max-width: 1100px;">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; align-items: start;">
                        <div>
                            @if(!empty($section->content['badge']))
                                <div style="display:inline-flex; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); font-weight: 900; letter-spacing:.08em; text-transform: uppercase; font-size:.75rem;">
                                    {{ $section->content['badge'] }}
                                </div>
                            @endif
                            <h2 style="margin: 14px 0 12px 0; color: {{ $ctaText }};">{{ $section->content['title'] ?? 'Vamos juntos' }}</h2>
                            @if(!empty($section->content['subtitle']))
                                <p style="margin:0; opacity:.9; font-size: 1.05rem; line-height:1.7;">
                                    {{ $section->content['subtitle'] }}
                                </p>
                            @endif
                            @if(!empty($section->content['form_note']))
                                <p style="margin-top: 18px; opacity:.8; font-size: .95rem;">
                                    {{ $section->content['form_note'] }}
                                </p>
                            @endif
                        </div>

                        <div style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.22); border-radius: 26px; padding: 22px; backdrop-filter: blur(10px);">
                            <form action="{{ url('/lp/'.$page->slug.'/lead') }}" method="POST">
                                @csrf
                                <input type="text" name="name" style="width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.25); border-radius: 14px; margin-bottom: 12px; background: rgba(15,23,42,0.25); color: #fff;" placeholder="Seu nome (opcional)">
                                <input type="email" name="email" required style="width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.25); border-radius: 14px; margin-bottom: 12px; background: rgba(15,23,42,0.25); color: #fff;" placeholder="Seu e-mail">
                                <input type="text" name="phone" style="width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.25); border-radius: 14px; margin-bottom: 14px; background: rgba(15,23,42,0.25); color: #fff;" placeholder="WhatsApp (opcional)">
                                <button type="submit" class="btn-cta" style="width: 100%; border:none; cursor:pointer;">
                                    {{ $section->content['button_text'] ?? 'Enviar' }}
                                </button>
                                <input type="hidden" name="source" value="final_cta_form">
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'footer_links')
            @php
                $fBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#0f172a');
                $fText = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
                $fFb = \App\Support\LandingPageSanitizer::url($section->content['facebook'] ?? null, '#');
                $fIg = \App\Support\LandingPageSanitizer::url($section->content['instagram'] ?? null, '#');
                $fLn = \App\Support\LandingPageSanitizer::url($section->content['linkedin'] ?? null, '#');
            @endphp
            <footer style="padding: 80px 0; background: {{ $fBg }}; color: {{ $fText }};">
                <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 50px;">
                    <div>
                        <h3 style="margin-top: 0;">{{ $section->content['company_name'] ?? 'Empresa' }}</h3>
                        <p style="opacity: 0.7; font-size: 0.9rem; line-height: 1.6;">{{ $section->content['description'] ?? '' }}</p>
                    </div>
                    <div>
                        <h4 style="margin-top: 0;">Links Rápidos</h4>
                        <ul style="list-style: none; padding: 0; opacity: 0.7; font-size: 0.9rem;">
                            <li style="margin-bottom: 10px;"><a href="#" style="color: inherit; text-decoration: none;">Privacidade</a></li>
                            <li style="margin-bottom: 10px;"><a href="#" style="color: inherit; text-decoration: none;">Termos de Uso</a></li>
                            <li style="margin-bottom: 10px;"><a href="#" style="color: inherit; text-decoration: none;">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 style="margin-top: 0;">Siga-nos</h4>
                        <div style="display: flex; gap: 15px; font-size: 1.2rem;">
                            <a href="{{ $fFb }}" target="_blank" rel="noopener noreferrer" style="color: inherit; opacity: 0.7;"><i class="fab fa-facebook"></i></a>
                            <a href="{{ $fIg }}" target="_blank" rel="noopener noreferrer" style="color: inherit; opacity: 0.7;"><i class="fab fa-instagram"></i></a>
                            <a href="{{ $fLn }}" target="_blank" rel="noopener noreferrer" style="color: inherit; opacity: 0.7;"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                <div class="container" style="margin-top: 60px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 0.8rem; opacity: 0.5;">
                    &copy; {{ date('Y') }} {{ $section->content['company_name'] ?? 'Vivensi' }}. Todos os direitos reservados.
                </div>
            </footer>
        @endif

        @if($section->type == 'products')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container">
                    <h2 style="text-align: center; margin-bottom: 60px;">{{ $section->content['title'] ?? 'Nossos Produtos' }}</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
                        @foreach($section->content['items'] ?? [] as $item)
                        <div class="feature-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                            <img src="{{ \App\Support\LandingPageSanitizer::url($item['image'] ?? null, 'https://via.placeholder.com/300x200') }}" style="width: 100%; height: 200px; object-fit: cover;">
                            <div style="padding: 25px;">
                                <h3 style="margin-top: 0; font-size: 1.25rem;">{{ $item['name'] ?? 'Produto' }}</h3>
                                <p style="color: var(--primary); font-weight: 800; font-size: 1.1rem; margin: 10px 0;">{{ $item['price'] ?? 'Sob consulta' }}</p>
                                <a href="{{ \App\Support\LandingPageSanitizer::url($item['link'] ?? null, '#') }}" class="btn-cta" style="width: 100%; text-align: center; padding: 12px; font-size: 0.8rem;">Comprar Agora</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'video')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc') }};">
                <div class="container" style="max-width: 900px;">
                    <h2 style="text-align: center; margin-bottom: 40px;">{{ $section->content['title'] ?? 'Assista ao Vídeo' }}</h2>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; border-radius: 24px; overflow: hidden; box-shadow: 0 40px 80px rgba(0,0,0,0.1);">
                        <iframe src="{{ \App\Support\LandingPageSanitizer::url($section->content['video_url'] ?? null, '') }}" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'social_links')
            <section style="padding: 60px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }}; text-align: center;">
                <div class="container">
                    <h2 style="margin-bottom: 40px;">{{ $section->content['title'] ?? 'Nossas RedesSociais' }}</h2>
                    <div style="display: flex; justify-content: center; gap: 25px; flex-wrap: wrap;">
                        @if($section->content['instagram'] ?? '')
                            <a href="{{ \App\Support\LandingPageSanitizer::url($section->content['instagram'] ?? null, '#') }}" target="_blank" style="width: 60px; height: 60px; background: #e1306c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if($section->content['facebook'] ?? '')
                            <a href="{{ \App\Support\LandingPageSanitizer::url($section->content['facebook'] ?? null, '#') }}" target="_blank" style="width: 60px; height: 60px; background: #1877f2; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if($section->content['linkedin'] ?? '')
                            <a href="{{ \App\Support\LandingPageSanitizer::url($section->content['linkedin'] ?? null, '#') }}" target="_blank" style="width: 60px; height: 60px; background: #0077b5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-linkedin-in"></i></a>
                        @endif
                        @if($section->content['youtube'] ?? '')
                            <a href="{{ \App\Support\LandingPageSanitizer::url($section->content['youtube'] ?? null, '#') }}" target="_blank" style="width: 60px; height: 60px; background: #ff0000; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-youtube"></i></a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'link_bio')
            @php
                $bioBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? null, '#0f172a');
                $bioImg = \App\Support\LandingPageSanitizer::url($section->content['profile_image'] ?? null, '');
            @endphp
            <section style="padding: 80px 0; background: {{ $bioBg }}; min-height: 100vh; display: flex; align-items: center;">
                <div class="container" style="max-width: 500px; text-align: center; color: white;">
                    @if($bioImg !== '')
                        <img src="{{ $bioImg }}" alt="Perfil" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); object-fit: cover;">
                    @endif
                    <h1 style="font-size: 1.8rem; margin-bottom: 10px;">{{ $section->content['name'] ?? 'Nome do Perfil' }}</h1>
                    <p style="opacity: 0.8; margin-bottom: 40px;">{{ $section->content['bio'] ?? 'Sua biografia aqui.' }}</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        @foreach($section->content['links'] ?? [] as $link)
                            <a href="{{ \App\Support\LandingPageSanitizer::url($link['url'] ?? null, '#') }}" target="_blank" rel="noopener noreferrer"
                               style="background: rgba(255,255,255,0.1); border: 2px solid white; color: white; padding: 18px; border-radius: 50px; text-decoration: none; font-weight: 700; transition: all 0.3s; backdrop-filter: blur(5px);">
                                {{ $link['label'] ?? 'Link' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'about')
            <section class="section-about" style="padding: 100px 0;">
                <div class="container">
                    <div class="about-flex">
                        <div class="about-content">
                            <h2 style="font-size: 2.8rem; margin-bottom: 25px; font-weight: 800; line-height: 1.2;">{{ $section->content['title'] ?? 'Nossa História' }}</h2>
                            <p style="font-size: 1.15rem; color: #475569; line-height: 1.8;">{{ $section->content['text'] ?? 'Escreva aqui sobre sua jornada.' }}</p>
                            <div style="margin-top: 30px; display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 2px; background: var(--primary);"></div>
                                <span style="font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;">Conheça a nossa causa</span>
                            </div>
                        </div>
                        <div class="about-image" style="border-radius: 30px;">
                            <img src="{{ \App\Support\LandingPageSanitizer::url($section->content['image_url'] ?? null, 'https://via.placeholder.com/600x400') }}" alt="Sobre nós">
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'lead_capture')
            <section class="section-lead" style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff') }};">
                <div class="container" style="max-width: 1000px;">
                    <div style="display: flex; align-items: center; gap: 60px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <h2 style="font-size: 2.5rem; margin-bottom: 15px;">{{ $section->content['title'] ?? 'Faça Parte' }}</h2>
                            <p style="color: #64748b; font-size: 1.1rem;">{{ $section->content['subtitle'] ?? 'Cadastre seu contato para novidades.' }}</p>
                        </div>
                        <div style="flex: 1; min-width: 300px;">
                            <form action="{{ url('/lp/'.$page->slug.'/lead') }}" method="POST" style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.06);">
                                @csrf
                                <input type="text" name="name" required style="width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px;" placeholder="Seu nome completo">
                                <input type="email" name="email" required style="width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 20px;" placeholder="Seu melhor e-mail">
                                <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer;">{{ $section->content['button_text'] ?? 'Enviar' }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'testimonials')
            <section style="padding: 100px 0; background: #f1f5f9;">
                <div class="container">
                    <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.5rem;">{{ $section->content['title'] ?? 'O que dizem' }}</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                        @foreach($section->content['items'] ?? [] as $t)
                        <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); position: relative;">
                            <i class="fas fa-quote-left" style="position: absolute; top: 30px; right: 30px; font-size: 2rem; color: #e2e8f0;"></i>
                            <p style="font-style: italic; color: #475569; margin-bottom: 25px; line-height: 1.7;">"{{ $t['text'] ?? '...' }}"</p>
                            <h4 style="margin: 0;">{{ $t['name'] ?? 'Anônimo' }}</h4>
                            <span style="font-size: 0.8rem; color: #94a3b8;">{{ $t['role'] ?? 'Beneficiário' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'newsletter')
            <section class="section-newsletter" style="padding: 60px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#eff6ff') }}; text-align: center;">
                <div class="container" style="max-width: 800px;">
                    <h2 style="margin-bottom: 10px;">{{ $section->content['title'] ?? 'Newsletter' }}</h2>
                    <p style="color: #64748b; margin-bottom: 30px;">{{ $section->content['subtitle'] ?? 'Receba atualizações.' }}</p>
                    <form action="{{ url('/lp/'.$page->slug.'/lead') }}" method="POST" style="display: flex; gap: 10px; max-width: 500px; margin: 0 auto;">
                        @csrf
                        <input type="email" name="email" required style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px;" placeholder="Seu melhor e-mail...">
                        <button type="submit" class="btn-cta" style="padding: 12px 30px; border: none; cursor: pointer;">{{ $section->content['button_text'] ?? 'Inscrever' }}</button>
                    </form>
                </div>
            </section>
        @endif

        @if($section->type == 'whatsapp')
            <a href="https://wa.me/{{ \App\Support\LandingPageSanitizer::phoneDigits($section->content['phone'] ?? null, '5511000000000') }}?text={{ urlencode($section->content['message'] ?? 'Olá!') }}" 
               target="_blank" 
               style="position: fixed; bottom: 30px; right: 30px; background: #25d366; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; box-shadow: 0 10px 25px rgba(37, 211, 102, 0.4); z-index: 9999; transition: transform 0.2s;">
                <i class="fab fa-whatsapp"></i>
            </a>
        @endif

        @if($section->type == 'features')
            <section class="section-features" style="padding: 80px 0;">
                <div class="container">
                    <h2 style="text-align: center; font-size: 2.5rem;">{{ $section->content['title'] ?? 'Destaques' }}</h2>
                    <div class="features-grid">
                        @foreach($section->content['items'] ?? [] as $item)
                        <div class="feature-card">
                            <h3>{{ $item['title'] ?? 'Recurso' }}</h3>
                            <p>{{ $item['desc'] ?? 'Descrição curta.' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'team_cards')
            @php
                $teamBg = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff');
            @endphp
            <section style="padding: 100px 0; background: {{ $teamBg }};">
                <div class="container" style="max-width: 1100px;">
                    <h2 style="text-align:center; margin-bottom: 10px;">{{ $section->content['title'] ?? 'Nosso Time' }}</h2>
                    @if(!empty($section->content['subtitle']))
                        <p style="text-align:center; color:#64748b; margin-top: 0; margin-bottom: 45px;">{{ $section->content['subtitle'] }}</p>
                    @endif

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        @foreach($section->content['items'] ?? [] as $it)
                            @php
                                $photo = \App\Support\LandingPageSanitizer::url($it['photo_url'] ?? null, '');
                                $linkedin = \App\Support\LandingPageSanitizer::url($it['linkedin'] ?? null, '#');
                                $instagram = \App\Support\LandingPageSanitizer::url($it['instagram'] ?? null, '#');
                            @endphp
                            <div style="background:#fff; border: 1px solid #e2e8f0; border-radius: 22px; padding: 20px; box-shadow: 0 24px 60px rgba(15,23,42,.06); text-align:center;">
                                @if($photo !== '')
                                    <img src="{{ $photo }}" alt="{{ $it['name'] ?? 'Pessoa' }}" style="width: 92px; height: 92px; border-radius: 28px; object-fit: cover; border: 1px solid #e2e8f0;">
                                @else
                                    <div style="width: 92px; height: 92px; border-radius: 28px; margin: 0 auto; background:#f1f5f9; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                                <div style="margin-top: 14px; font-weight: 900; color:#0f172a; font-size: 1.05rem;">
                                    {{ $it['name'] ?? 'Nome' }}
                                </div>
                                @if(!empty($it['role']))
                                    <div style="margin-top: 6px; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.08em; font-size:.72rem;">
                                        {{ $it['role'] }}
                                    </div>
                                @endif
                                @if(!empty($it['bio']))
                                    <div style="margin-top: 10px; color:#475569; line-height:1.65; font-size:.95rem;">
                                        {{ $it['bio'] }}
                                    </div>
                                @endif
                                <div style="margin-top: 14px; display:flex; justify-content:center; gap: 10px;">
                                    <a href="{{ $linkedin }}" target="_blank" rel="noopener noreferrer" style="width: 40px; height: 40px; border-radius: 14px; border:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; justify-content:center; color:#0f172a; text-decoration:none;">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" style="width: 40px; height: 40px; border-radius: 14px; border:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; justify-content:center; color:#0f172a; text-decoration:none;">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'campaign_progress')
            @php
                $pb = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff');
                $goalRaw = (string) ($section->content['goal_amount'] ?? '0');
                $currentRaw = (string) ($section->content['current_amount'] ?? '0');
                $goal = (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $goalRaw));
                $current = (float) str_replace(',', '.', preg_replace('/[^\d,\.]/', '', $currentRaw));
                if ($goal <= 0) { $goal = 0.0; }
                if ($current < 0) { $current = 0.0; }
                $pct = $goal > 0 ? min(100, max(0, ($current / $goal) * 100)) : 0;
                $unit = (string) ($section->content['unit'] ?? 'R$');
                $badge = (string) ($section->content['badge'] ?? '');
            @endphp
            <section style="padding: 90px 0; background: {{ $pb }};">
                <div class="container" style="max-width: 1000px;">
                    <div style="border:1px solid #e2e8f0; border-radius: 28px; padding: 26px; background:#fff; box-shadow: 0 24px 60px rgba(15,23,42,.06);">
                        @if($badge !== '')
                            <div style="display:inline-flex; padding: 6px 12px; border-radius: 999px; background: rgba(99,102,241,.12); border: 1px solid rgba(99,102,241,.22); font-weight: 900; letter-spacing:.08em; text-transform: uppercase; font-size:.75rem; color: var(--primary);">
                                {{ $badge }}
                            </div>
                        @endif
                        <h2 style="margin: 12px 0 10px 0;">{{ $section->content['title'] ?? 'Meta da Campanha' }}</h2>
                        @if(!empty($section->content['subtitle']))
                            <p style="margin:0 0 18px 0; color:#64748b; font-size: 1.05rem; line-height:1.7;">
                                {{ $section->content['subtitle'] }}
                            </p>
                        @endif

                        <div style="display:flex; gap: 14px; flex-wrap: wrap; align-items: baseline; justify-content: space-between; margin-top: 16px;">
                            <div style="font-weight: 900; color:#0f172a; font-size: 1.25rem;">
                                {{ $unit }} {{ $section->content['current_amount'] ?? '0' }}
                                <span style="font-weight:700; color:#64748b; font-size: 1rem;">/ {{ $unit }} {{ $section->content['goal_amount'] ?? '0' }}</span>
                            </div>
                            <div style="color:#64748b; font-weight:900;">
                                {{ number_format($pct, 0) }}%
                            </div>
                        </div>

                        <div style="margin-top: 12px; height: 14px; background:#f1f5f9; border-radius: 999px; overflow:hidden; border:1px solid #e2e8f0;">
                            <div style="height: 100%; width: {{ $pct }}%; background: linear-gradient(90deg, #6366f1 0%, #22c55e 100%);"></div>
                        </div>

                        @if(!empty($section->content['note']))
                            <div style="margin-top: 14px; color:#64748b; font-size:.95rem;">
                                {{ $section->content['note'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'contact')
            <section class="section-contact" id="contato" style="background: #0f172a; color: white; padding: 80px 0; text-align: center;">
                <div class="container">
                    <h2>{{ $section->content['title'] ?? 'Contato' }}</h2>
                    <div class="contact-info" style="margin-top: 30px; display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary); font-size: 1.5rem; margin-bottom: 15px; display: block;"></i>
                            <strong>Endereço</strong><br>{{ $section->content['address'] ?? 'Cidade - Estado' }}
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope" style="color: var(--primary); font-size: 1.5rem; margin-bottom: 15px; display: block;"></i>
                            <strong>E-mail</strong><br>{{ $section->content['email'] ?? 'contato@ong.org' }}
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone" style="color: var(--primary); font-size: 1.5rem; margin-bottom: 15px; display: block;"></i>
                            <strong>Telefone</strong><br>{{ $section->content['phone'] ?? '(00) 0000-0000' }}
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    <!-- Lightbox (galeria) -->
    <div id="lpLightbox" style="position: fixed; inset: 0; background: rgba(15,23,42,0.85); display:none; align-items:center; justify-content:center; z-index: 99999; padding: 24px;">
        <div style="max-width: 1100px; width: 100%; max-height: 90vh; display:flex; flex-direction: column; gap: 10px;">
            <div style="display:flex; justify-content: space-between; align-items:center; gap: 12px; color:#fff;">
                <div id="lpLightboxCaption" style="font-weight: 800; opacity:.95; overflow:hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
                <button type="button" onclick="window.vivensiLpCloseLightbox && window.vivensiLpCloseLightbox()" style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25); color:#fff; border-radius: 12px; padding: 10px 12px; cursor:pointer; font-weight:900;">
                    Fechar
                </button>
            </div>
            <div style="flex:1; display:flex; align-items:center; justify-content:center; border-radius: 22px; overflow:hidden; border: 1px solid rgba(255,255,255,0.18); background: rgba(255,255,255,0.06);">
                <img id="lpLightboxImg" src="" alt="Imagem" style="max-width: 100%; max-height: 82vh; object-fit: contain; display:block;">
            </div>
        </div>
    </div>

    <script>
        (function() {
            function el(id) { return document.getElementById(id); }
            function show(v) { var box = el('lpLightbox'); if (!box) return; box.style.display = v ? 'flex' : 'none'; }

            window.vivensiLpOpenLightbox = function(e, a) {
                try { if (e && e.preventDefault) e.preventDefault(); } catch (err) {}
                if (!a) return false;
                var src = a.getAttribute('data-src') || a.getAttribute('href') || '';
                var cap = a.getAttribute('data-cap') || '';
                var img = el('lpLightboxImg');
                var caption = el('lpLightboxCaption');
                if (img) img.src = src;
                if (caption) caption.textContent = cap || '';
                show(true);
                return false;
            };

            window.vivensiLpCloseLightbox = function() {
                var img = el('lpLightboxImg');
                if (img) img.src = '';
                show(false);
            };

            var box = el('lpLightbox');
            if (box) {
                box.addEventListener('click', function(ev) {
                    if (ev && ev.target === box) window.vivensiLpCloseLightbox();
                });
            }
            document.addEventListener('keydown', function(ev) {
                if (ev && ev.key === 'Escape') window.vivensiLpCloseLightbox();
            });
        })();
    </script>

    <footer style="padding: 40px 0; text-align: center; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: #64748b; background: white;">
        &copy; {{ date('Y') }} Vivensi - Mantido por {{ $page->tenant_id == 1 ? 'Instituto Vivensi' : 'Organização Social' }}
        <div style="margin-top: 15px; font-weight: 600; color: var(--primary);">
            Desenvolvido pelo sistema Vivensi App com carinho! <i class="fas fa-heart" style="color: #ef4444;"></i>
        </div>
    </footer>

</body>
</html>
