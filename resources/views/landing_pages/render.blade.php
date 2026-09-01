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

    {{-- Toast de confirmacao apos submissao (2026-08-06) --}}
    @if(session('lp_lead_success'))
        @php $lpSucc = session('lp_lead_success'); @endphp
        <div id="lp-success-toast" role="status" aria-live="polite" style="position:fixed; top:20px; right:20px; z-index:9999; max-width:380px; background:#ffffff; border:1px solid #86efac; border-left:4px solid #22c55e; border-radius:12px; padding:16px 18px 16px 16px; box-shadow:0 12px 30px rgba(15,23,42,.15); font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <div style="flex-shrink:0; width:32px; height:32px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; color:#15803d; font-weight:900;">✓</div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:800; color:#0f172a; font-size:.9rem; margin-bottom:2px;">
                        Inscrição enviada com sucesso!
                    </div>
                    <div style="color:#475569; font-size:.82rem; line-height:1.5;">
                        Enviamos um e-mail de confirmação para <strong>{{ $lpSucc['email'] ?? '' }}</strong>. Verifique também a caixa de spam.
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('lp-success-toast').remove()" aria-label="Fechar"
                        style="background:transparent; border:none; color:#94a3b8; cursor:pointer; font-size:1.2rem; line-height:1; padding:0 4px;">×</button>
            </div>
        </div>
        <script>setTimeout(function(){var t=document.getElementById('lp-success-toast');if(t)t.remove();},10000);</script>
    @endif

    @foreach($sections as $section)


        @if($section->type == 'impact_dynamic')
            @php
                $idBg     = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff');
                $idText   = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#0f172a');
                $idAccent = \App\Support\LandingPageSanitizer::cssColor($section->content['accent_color'] ?? null, '#4f46e5');
                $idMetrics = is_array($section->content['metrics'] ?? null) ? $section->content['metrics'] : [];

                // Cache 5min por tenant — impacto nao precisa ser real-time e
                // essa pagina pode receber tráfego alto. Chave inclui timestamp
                // do inicio do mes pra invalidar contador de doacoes mensais
                // automaticamente na virada.
                $cacheKey = 'lp_impact_' . $page->tenant_id . '_' . date('Ym');
                $counts   = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($page) {
                    $tid = $page->tenant_id;
                    $benefTable   = \Illuminate\Support\Facades\Schema::hasTable('beneficiaries') ? \Illuminate\Support\Facades\DB::table('beneficiaries')->where('tenant_id', $tid)->count() : 0;
                    $projTable    = \Illuminate\Support\Facades\Schema::hasTable('projects') ? \Illuminate\Support\Facades\DB::table('projects')->where('tenant_id', $tid)->whereIn('status', ['active','ativo','em_andamento','in_progress'])->count() : 0;
                    $donMonth     = \Illuminate\Support\Facades\Schema::hasTable('transactions') ? \Illuminate\Support\Facades\DB::table('transactions')->where('tenant_id', $tid)->where('type', 'income')->whereMonth('date', now()->month)->whereYear('date', now()->year)->count() : 0;
                    $tenantRow    = \Illuminate\Support\Facades\DB::table('tenants')->where('id', $tid)->first(['data_fundacao', 'created_at']);
                    $foundedAt    = $tenantRow?->data_fundacao ?? $tenantRow?->created_at;
                    $years        = 0;
                    if ($foundedAt) {
                        try { $years = max(1, (int) \Carbon\Carbon::parse($foundedAt)->diffInYears(now())); } catch (\Throwable $e) {}
                    }
                    return [
                        'beneficiaries'   => $benefTable,
                        'projects_active' => $projTable,
                        'donations_month' => $donMonth,
                        'years_active'    => $years,
                    ];
                });

                $iconWhitelist = ['fa-users','fa-project-diagram','fa-heart','fa-award','fa-hand-holding-heart','fa-globe','fa-tree','fa-graduation-cap','fa-house','fa-utensils','fa-child','fa-star'];
                $visibleMetrics = array_values(array_filter($idMetrics, fn($m) => ($m['enabled'] ?? 'yes') === 'yes'));
            @endphp
            @if(count($visibleMetrics) > 0)
                <section style="padding: 70px 0; background: {{ $idBg }}; color: {{ $idText }};">
                    <div class="container" style="max-width: 1080px;">
                        <div style="text-align:center; margin-bottom: 44px;">
                            <div style="display:inline-flex; align-items:center; gap:8px; background:{{ $idAccent }}15; color:{{ $idAccent }}; padding:6px 14px; border-radius:99px; font-weight:800; font-size:.78rem; letter-spacing:.05em; text-transform:uppercase; margin-bottom:12px;">
                                <i class="fas fa-chart-line"></i> Dados ao vivo
                            </div>
                            <h2 style="margin:0 0 10px; font-size:clamp(1.6rem,3.2vw,2.4rem); font-weight:900;">{{ $section->content['title'] ?? 'Nosso Impacto' }}</h2>
                            @if(!empty($section->content['subtitle']))
                                <p style="margin:0; color:#64748b; max-width: 640px; margin-inline:auto; font-size:1.02rem; line-height:1.6;">{{ $section->content['subtitle'] }}</p>
                            @endif
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                            @foreach($visibleMetrics as $metric)
                                @php
                                    $mType  = $metric['type']  ?? 'beneficiaries';
                                    $mLabel = $metric['label'] ?? 'Métrica';
                                    $mIcon  = in_array($metric['icon'] ?? '', $iconWhitelist, true) ? $metric['icon'] : 'fa-chart-simple';
                                    $mValue = (int) ($counts[$mType] ?? 0);
                                @endphp
                                <div style="background:#fff; border-radius:20px; padding:28px 22px; text-align:center; border:1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(15,23,42,.04); transition: transform .2s;"
                                     onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:{{ $idAccent }}15; color:{{ $idAccent }}; font-size:1.5rem; margin-bottom:16px;">
                                        <i class="fas {{ $mIcon }}"></i>
                                    </div>
                                    <div style="font-size:clamp(2rem,4vw,2.8rem); font-weight:900; color:{{ $idAccent }}; line-height:1; margin-bottom:8px;">
                                        {{ number_format($mValue, 0, ',', '.') }}{{ $mType === 'years_active' && $mValue > 0 ? '+' : '' }}
                                    </div>
                                    <div style="color:#475569; font-size:.92rem; font-weight:700;">
                                        {{ $mLabel }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endif

        @if($section->type == 'transparency_portal')
            @php
                // Puxa portal do proprio tenant da LP. Se nao existir ou nao
                // estiver publicado, o bloco simplesmente nao renderiza
                // (fail-safe: nao vira botao dead link 404).
                $tpPortal = \App\Models\TransparencyPortal::withoutGlobalScope('tenant')
                    ->where('tenant_id', $page->tenant_id)
                    ->where('is_published', true)
                    ->whereNotNull('slug')
                    ->first();
            @endphp
            @if($tpPortal)
                @php
                    $tpBg     = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f0f9ff');
                    $tpText   = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#0c4a6e');
                    $tpAccent = \App\Support\LandingPageSanitizer::cssColor($section->content['accent_color'] ?? null, '#0284c7');
                    $tpUrl    = url('/transparencia/' . $tpPortal->slug);
                @endphp
                <section style="padding: 70px 0; background: {{ $tpBg }}; color: {{ $tpText }};">
                    <div class="container" style="max-width: 900px;">
                        <div style="background:#fff; border-radius: 24px; padding: 40px; box-shadow: 0 20px 50px rgba(15,23,42,.08); text-align: center;">
                            <div style="display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; border-radius:50%; background:{{ $tpAccent }}22; color:{{ $tpAccent }}; font-size:1.8rem; margin-bottom:18px;">
                                <i class="fas fa-shield-heart"></i>
                            </div>
                            <h2 style="margin: 0 0 12px; color: {{ $tpText }}; font-size: clamp(1.4rem, 2.6vw, 2rem); font-weight: 900;">{{ $section->content['title'] ?? 'Transparência' }}</h2>
                            @if(!empty($section->content['subtitle']))
                                <p style="margin: 0 0 28px; color:#475569; max-width:640px; margin-inline:auto; line-height:1.6; font-size:1.02rem;">{{ $section->content['subtitle'] }}</p>
                            @endif

                            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-bottom:24px;">
                                <div style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:{{ $tpAccent }}11; color:{{ $tpAccent }}; border-radius:99px; font-size:.82rem; font-weight:700;">
                                    <i class="fas fa-file-invoice-dollar"></i> Prestação de contas
                                </div>
                                <div style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:{{ $tpAccent }}11; color:{{ $tpAccent }}; border-radius:99px; font-size:.82rem; font-weight:700;">
                                    <i class="fas fa-users"></i> Conselho e equipe
                                </div>
                                <div style="display:flex; align-items:center; gap:6px; padding:6px 12px; background:{{ $tpAccent }}11; color:{{ $tpAccent }}; border-radius:99px; font-size:.82rem; font-weight:700;">
                                    <i class="fas fa-handshake"></i> Parcerias
                                </div>
                            </div>

                            <a href="{{ $tpUrl }}" target="_blank" rel="noopener"
                               style="display:inline-flex; align-items:center; gap:10px; background: {{ $tpAccent }}; color:#fff; padding:14px 28px; border-radius:14px; text-decoration:none; font-weight:900; font-size:1.05rem; box-shadow: 0 8px 20px {{ $tpAccent }}44; transition: transform .15s;"
                               onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                {{ $section->content['button_text'] ?? 'Ver Portal da Transparência' }}
                                <i class="fas fa-arrow-right"></i>
                            </a>

                            <div style="margin-top:18px; color:#94a3b8; font-size:.82rem;">
                                Portal público em {{ parse_url($tpUrl, PHP_URL_HOST) }}/transparencia/{{ $tpPortal->slug }}
                            </div>
                        </div>
                    </div>
                </section>
            @endif
            {{-- Se portal nao publicado, bloco nao renderiza nada — evita link quebrado --}}
        @endif

        @if($section->type == 'whatsapp_float')
            @php
                $wfPhone   = preg_replace('/\D+/', '', (string) ($section->content['phone'] ?? ''));
                $wfMsg     = trim((string) ($section->content['message'] ?? ''));
                $wfTooltip = trim((string) ($section->content['tooltip'] ?? ''));
                $wfPos     = in_array($section->content['position'] ?? '', ['left','right'], true) ? $section->content['position'] : 'right';
                $wfColor   = \App\Support\LandingPageSanitizer::cssColor($section->content['button_color'] ?? null, '#25D366');
                $wfShow    = ($section->content['show_tooltip'] ?? 'yes') === 'yes';
                $wfHref    = 'https://wa.me/' . $wfPhone . ($wfMsg !== '' ? '?text=' . rawurlencode($wfMsg) : '');
                $wfId      = 'wf-' . $section->id;
            @endphp
            @if($wfPhone !== '')
                <a id="{{ $wfId }}" href="{{ $wfHref }}" target="_blank" rel="noopener"
                   aria-label="Falar no WhatsApp"
                   style="position: fixed; bottom: 24px; {{ $wfPos }}: 24px; z-index: 9998;
                          width: 60px; height: 60px; border-radius: 50%;
                          background: {{ $wfColor }}; color: #fff;
                          display: flex; align-items: center; justify-content: center;
                          font-size: 30px; text-decoration: none;
                          box-shadow: 0 8px 24px rgba(0,0,0,0.25);
                          transition: transform .2s ease, box-shadow .2s ease;">
                    <i class="fab fa-whatsapp"></i>
                    @if($wfShow && $wfTooltip !== '')
                        <span class="{{ $wfId }}-tip" style="
                            position: absolute; bottom: 50%; transform: translateY(50%);
                            {{ $wfPos === 'right' ? 'right: 74px;' : 'left: 74px;' }}
                            background: #0f172a; color: #fff; padding: 8px 14px;
                            border-radius: 8px; font-size: .85rem; font-weight: 700; white-space: nowrap;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                            opacity: 0; pointer-events: none; transition: opacity .2s;">{{ $wfTooltip }}</span>
                    @endif
                </a>
                <style>
                    #{{ $wfId }}:hover { transform: scale(1.08); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
                    @if($wfShow && $wfTooltip !== '')
                    #{{ $wfId }}:hover .{{ $wfId }}-tip { opacity: 1; }
                    @endif
                </style>
            @endif
        @endif

        @if($section->type == 'hero_image')
            @php
                $hiBg      = \App\Support\LandingPageSanitizer::url($section->content['background_url'] ?? null, '');
                $hiOvColor = \App\Support\LandingPageSanitizer::cssColor($section->content['overlay_color'] ?? null, '#0f172a');
                $hiOvOp    = is_numeric($section->content['overlay_opacity'] ?? null) ? max(0, min(1, (float) $section->content['overlay_opacity'])) : 0.55;
                $hiText    = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
                $hiAlign   = in_array($section->content['align'] ?? '', ['left','center','right'], true) ? $section->content['align'] : 'center';
                $hiHeight  = ['small' => '360px', 'medium' => '540px', 'large' => '720px', 'full' => '100vh'][$section->content['height'] ?? 'medium'] ?? '540px';
                $hiBtnText = $section->content['button_text'] ?? '';
                $hiBtnUrl  = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? null, '#');
            @endphp
            <section style="position: relative; min-height: {{ $hiHeight }}; display: flex; align-items: center; overflow: hidden;
                @if($hiBg)
                background-image: url('{{ $hiBg }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                @else
                background: {{ $hiOvColor }};
                @endif
            ">
                @if($hiBg)
                    <div style="position: absolute; inset: 0; background: {{ $hiOvColor }}; opacity: {{ $hiOvOp }}; z-index: 1;"></div>
                @endif
                <div class="container" style="position: relative; z-index: 2; text-align: {{ $hiAlign }}; padding: 60px 20px; width: 100%;">
                    <h1 style="color: {{ $hiText }}; font-size: clamp(1.8rem, 4vw, 3.4rem); font-weight: 900; margin: 0 0 18px; line-height: 1.15; text-shadow: 0 2px 12px rgba(0,0,0,.35);">{{ $section->content['title'] ?? 'Título' }}</h1>
                    <p style="color: {{ $hiText }}; opacity: 0.92; font-size: clamp(1rem, 1.6vw, 1.25rem); line-height: 1.6; max-width: 720px; margin: 0 {{ $hiAlign === 'center' ? 'auto' : '0' }} 28px; text-shadow: 0 1px 8px rgba(0,0,0,.3);">{{ $section->content['subtitle'] ?? '' }}</p>
                    @if($hiBtnText)
                        <a href="{{ $hiBtnUrl }}" class="btn-cta" style="display: inline-block; padding: 14px 32px; background: rgba(255,255,255,0.15); color: {{ $hiText }}; border: 2px solid {{ $hiText }}; border-radius: 12px; text-decoration: none; font-weight: 800; font-size: 1.05rem; backdrop-filter: blur(6px); transition: all .3s;">{{ $hiBtnText }}</a>
                    @endif
                </div>
            </section>
        @endif

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
            @php
                $hnBg    = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#ffffff');
                $hnColor = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#1e293b');
                $hnLogo  = \App\Support\LandingPageSanitizer::url($section->content['logo_url'] ?? null, '');
                $hnBrand = $section->content['brand_text'] ?? ($page->title ?? '');
                $hnId    = 'hn-' . $section->id;
            @endphp
            <nav style="background: {{ $hnBg }}; padding: 16px 0; border-bottom: 1px solid rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px);">
                <div class="container" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                    <a href="#" style="display:flex; align-items:center; gap:10px; text-decoration:none; color: {{ $hnColor }};">
                        @if($hnLogo)
                            <img loading="lazy" src="{{ $hnLogo }}" alt="Logo" style="height: 40px; max-width: 180px; object-fit: contain;">
                        @endif
                        @if($hnBrand && !$hnLogo)
                            <span style="font-weight: 900; font-size: 1.1rem;">{{ $hnBrand }}</span>
                        @endif
                    </a>

                    {{-- Botao hamburger — visivel so mobile via media query --}}
                    <button type="button" id="{{ $hnId }}-toggle" aria-label="Menu" aria-expanded="false"
                            style="display:none; background: transparent; border: 1px solid rgba(0,0,0,0.1); color: {{ $hnColor }}; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 1.2rem;"
                            class="{{ $hnId }}-mobile-only"
                            onclick="var m=document.getElementById('{{ $hnId }}-menu');var b=this;var open=m.getAttribute('data-open')==='1';m.setAttribute('data-open',open?'0':'1');m.style.display=open?'none':'flex';b.setAttribute('aria-expanded',open?'false':'true');">
                        &#9776;
                    </button>

                    <div id="{{ $hnId }}-menu" data-open="0" class="{{ $hnId }}-links"
                         style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                        @foreach($section->content['links'] ?? [] as $link)
                            <a href="{{ \App\Support\LandingPageSanitizer::url($link['url'] ?? null, '#') }}"
                               style="text-decoration: none; color: {{ $hnColor }}; font-weight: 600; font-size: 0.95rem; transition: color 0.3s; padding: 6px 0;">{{ $link['label'] ?? 'Link' }}</a>
                        @endforeach
                    </div>
                </div>
                <style>
                    @media (max-width: 768px) {
                        .{{ $hnId }}-mobile-only { display: inline-flex !important; }
                        .{{ $hnId }}-links {
                            display: none !important;
                            flex-direction: column;
                            align-items: flex-start !important;
                            gap: 8px !important;
                            width: 100%;
                            padding: 12px 20px !important;
                            border-top: 1px solid rgba(0,0,0,0.06);
                            margin-top: 12px;
                        }
                        .{{ $hnId }}-links[data-open="1"] { display: flex !important; }
                    }
                </style>
            </nav>
        @endif

        @if($section->type == 'who_we_are')
            <section style="padding: 100px 0; background: {{ \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc') }};">
                <div class="container" style="display: flex; align-items: center; gap: 60px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; border-radius: 30px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                        <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($section->content['image_url'] ?? null, '') }}" style="width: 100%; display: block;">
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
                                <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($service['image'] ?? null, '') }}" style="width: 100%; height: 220px; object-fit: cover;">
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
                                        <img loading="lazy" src="{{ $src }}" alt="{{ $img['caption'] ?? 'Foto' }}" style="width: 100%; height: 190px; object-fit: cover; display:block;">
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
                                    <img loading="lazy" src="{{ $plogo }}" alt="{{ $logo['name'] ?? 'Parceiro' }}" style="max-height: 34px; max-width: 180px;">
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
                            <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($section->content['left_image_url'] ?? null, '') }}" alt="{{ $section->content['left_title'] ?? 'Antes' }}" style="width: 100%; height: 240px; object-fit: cover; display:block;">
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
                            <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($section->content['right_image_url'] ?? null, '') }}" alt="{{ $section->content['right_title'] ?? 'Depois' }}" style="width: 100%; height: 240px; object-fit: cover; display:block;">
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
                $pixBg     = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_color'] ?? null, '#f8fafc');
                $pixText   = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#0f172a');
                $accent    = \App\Support\LandingPageSanitizer::cssColor($section->content['accent_color'] ?? null, '#25D366');
                $qrUser    = \App\Support\LandingPageSanitizer::url($section->content['qr_image_url'] ?? null, '');
                $payload   = (string) ($section->content['pix_key_or_payload'] ?? '');
                // Fallback: se user nao subiu QR, gera via api.qrserver.com com o proprio payload
                $qrSrc     = $qrUser !== ''
                    ? $qrUser
                    : ($payload !== '' ? 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=6&data=' . rawurlencode($payload) : '');
                $payloadId = 'pix-payload-' . $section->id;
                $toastId   = 'pix-toast-' . $section->id;
                $amounts   = array_filter(array_map('trim', explode(',', (string) ($section->content['suggested_amounts'] ?? ''))));
            @endphp
            <section style="padding: 80px 0; background: {{ $pixBg }}; color: {{ $pixText }};">
                <div class="container" style="max-width: 1080px;">
                    <div style="text-align:center; margin-bottom: 36px;">
                        <div style="display:inline-flex; align-items:center; gap:8px; background:{{ $accent }}22; color:{{ $accent }}; padding:6px 14px; border-radius:99px; font-weight:800; font-size:.78rem; letter-spacing:.05em; text-transform:uppercase; margin-bottom:14px;">
                            <i class="fas fa-bolt"></i> PIX Instantâneo
                        </div>
                        <h2 style="margin:0 0 12px; font-size:clamp(1.6rem,3.2vw,2.4rem); font-weight:900;">{{ $section->content['title'] ?? 'Doe via PIX' }}</h2>
                        @if(!empty($section->content['subtitle']))
                            <p style="margin:0; color:#64748b; max-width: 640px; margin-inline:auto; font-size:1.05rem; line-height:1.6;">{{ $section->content['subtitle'] }}</p>
                        @endif
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; align-items: stretch;">
                        {{-- QR Code — hero visual da doacao --}}
                        <div style="border-radius: 24px; padding: 28px; background:#fff; box-shadow: 0 20px 50px rgba(15,23,42,.08); text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                            <div style="font-weight: 800; color:#0f172a; margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
                                <i class="fas fa-qrcode" style="color:{{ $accent }};"></i>
                                Aponte a câmera do seu banco
                            </div>
                            @if($qrSrc !== '')
                                <div style="padding:16px; background:#fff; border-radius:20px; border:3px solid {{ $accent }}; box-shadow: 0 8px 24px rgba(37,211,102,.15);">
                                    <img loading="lazy" src="{{ $qrSrc }}" alt="QR Code PIX" style="width: 220px; height: 220px; object-fit: contain; display:block;">
                                </div>
                            @else
                                <div style="width: 240px; height: 240px; border-radius: 18px; border: 2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; color:#94a3b8; text-align:center; padding: 20px;">
                                    Configure a chave PIX ou o QR pra ativar
                                </div>
                            @endif
                            @if(!empty($section->content['recipient_name']))
                                <div style="margin-top:16px; color:#0f172a; font-weight:800; font-size:.95rem;">
                                    <i class="fas fa-heart" style="color:{{ $accent }};"></i>
                                    {{ $section->content['recipient_name'] }}
                                </div>
                            @endif
                        </div>

                        {{-- Copia e cola + valores sugeridos --}}
                        <div style="border-radius: 24px; padding: 28px; background:#fff; box-shadow: 0 20px 50px rgba(15,23,42,.08); display:flex; flex-direction:column;">
                            <div style="font-weight: 800; color:#0f172a; margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
                                <i class="fas fa-copy" style="color:{{ $accent }};"></i>
                                Ou copie e cole
                            </div>

                            @if(count($amounts) > 0)
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size:.78rem; color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">Valores sugeridos:</div>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        @foreach($amounts as $amt)
                                            <span style="background:{{ $accent }}11; color:{{ $accent }}; padding:8px 14px; border-radius:10px; font-weight:800; border:1px solid {{ $accent }}44;">R$ {{ $amt }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <textarea id="{{ $payloadId }}" readonly
                                      style="width:100%; min-height: 100px; padding: 14px; border-radius: 14px; border:1px solid #e2e8f0; background:#f8fafc; color:#0f172a; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .8rem; line-height:1.5; resize: vertical; margin-bottom:14px;">{{ $payload }}</textarea>

                            <button type="button"
                                    onclick="(function(){var el=document.getElementById('{{ $payloadId }}'); if(!el) return; var v=el.value; try{ if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(v);}else{el.select();document.execCommand('copy');} var t=document.getElementById('{{ $toastId }}'); if(t){t.style.opacity='1';t.style.transform='translateY(0)';clearTimeout(window.__pixT);window.__pixT=setTimeout(function(){t.style.opacity='0';t.style.transform='translateY(20px)';},2200);} }catch(e){} })();"
                                    style="background:{{ $accent }}; color:#fff; border:none; padding: 14px 20px; border-radius: 14px; font-weight: 900; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow: 0 6px 16px {{ $accent }}55; transition: transform .15s ease;"
                                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="fas fa-copy"></i> Copiar código PIX
                            </button>

                            @if(!empty($section->content['help_text']))
                                <div style="margin-top: 14px; color:#64748b; font-size:.85rem; line-height:1.5;">
                                    <i class="fas fa-info-circle" style="color:{{ $accent }};"></i> {{ $section->content['help_text'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Toast copiado --}}
                <div id="{{ $toastId }}" style="position:fixed; bottom:30px; left:50%; transform:translate(-50%, 20px); background:{{ $accent }}; color:#fff; padding:14px 24px; border-radius:14px; font-weight:800; box-shadow:0 12px 32px {{ $accent }}66; z-index:100000; opacity:0; pointer-events:none; transition:opacity .25s ease, transform .25s ease;">
                    <i class="fas fa-check-circle"></i> Código PIX copiado!
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

                        @php
                            // Toggles do content da seção — cada campo é opcional.
                            // Se target_project_id da landing está ligado e o gestor
                            // marcou "link_beneficiary", CPF é praticamente essencial.
                            $ctaInputStyle = 'width: 100%; padding: 14px; border: 1px solid rgba(255,255,255,0.25); border-radius: 14px; margin-bottom: 12px; background: rgba(15,23,42,0.25); color: #fff;';
                            $c = $section->content ?? [];
                            $enCpf       = (bool) ($c['enable_cpf']            ?? false);
                            $enBirth     = (bool) ($c['enable_birth_date']     ?? false);
                            $enAddress   = (bool) ($c['enable_address']        ?? false);
                            $enCity      = (bool) ($c['enable_city']           ?? false);
                            $enGuardian  = (bool) ($c['enable_guardian']       ?? false);
                            $reqName     = (bool) ($c['require_name']          ?? false);
                            $reqPhone    = (bool) ($c['require_phone']         ?? false);
                        @endphp
                        <div style="background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.22); border-radius: 26px; padding: 22px; backdrop-filter: blur(10px);">
                            <form action="{{ url('/lp/'.$page->slug.'/lead') }}" method="POST">
                                @csrf
                                <input type="text"  name="name"  {{ $reqName ? 'required' : '' }} style="{{ $ctaInputStyle }}" placeholder="Seu nome{{ $reqName ? '' : ' (opcional)' }}">
                                <input type="email" name="email" required style="{{ $ctaInputStyle }}" placeholder="Seu e-mail">
                                <input type="text"  name="phone" {{ $reqPhone ? 'required' : '' }} style="{{ $ctaInputStyle }}" placeholder="WhatsApp{{ $reqPhone ? '' : ' (opcional)' }}">

                                @if($enCpf)
                                    <input type="text" name="cpf" inputmode="numeric" maxlength="14" style="{{ $ctaInputStyle }}" placeholder="CPF (somente números)">
                                @endif
                                @if($enBirth)
                                    {{-- input[type=date] ignora placeholder — label acima resolve --}}
                                    <label style="display:block; font-size:.78rem; color:rgba(255,255,255,.85); margin:2px 4px 4px; font-weight:600;">Data de nascimento</label>
                                    <input type="date" name="birth_date" style="{{ $ctaInputStyle }}" aria-label="Data de nascimento">
                                @endif
                                @if($enAddress)
                                    <input type="text" name="address" maxlength="255" style="{{ $ctaInputStyle }}" placeholder="Endereço">
                                @endif
                                @if($enCity)
                                    <input type="text" name="city" maxlength="120" style="{{ $ctaInputStyle }}" placeholder="Cidade">
                                @endif
                                @if($enGuardian)
                                    <input type="text" name="guardian_name"  maxlength="255" style="{{ $ctaInputStyle }}" placeholder="Nome do responsável">
                                    <input type="text" name="guardian_phone" maxlength="30"  style="{{ $ctaInputStyle }}" placeholder="Telefone do responsável">
                                @endif

                                {{-- Custom fields (2026-08-06) — configurados no builder da landing --}}
                                @include('landing_pages._custom_fields', ['customFields' => $c['custom_fields'] ?? [], 'cfStyle' => $ctaInputStyle])

                                <label style="display:flex; gap:10px; align-items:flex-start; margin-bottom: 14px; font-size: .85rem; opacity:.9; line-height:1.45;">
                                    <input type="checkbox" name="consent_given" value="1" required style="margin-top: 4px; flex-shrink:0;">
                                    <span>Autorizo o contato e o tratamento dos meus dados conforme a <a href="/privacidade" target="_blank" rel="noopener" style="color:inherit; text-decoration: underline;">Política de Privacidade</a> (LGPD). Você pode cancelar a qualquer momento.</span>
                                </label>
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
                            <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($item['image'] ?? null, 'https://via.placeholder.com/300x200') }}" style="width: 100%; height: 200px; object-fit: cover;">
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
                        <img loading="lazy" src="{{ $bioImg }}" alt="Perfil" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); object-fit: cover;">
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
                            <img loading="lazy" src="{{ \App\Support\LandingPageSanitizer::url($section->content['image_url'] ?? null, 'https://via.placeholder.com/600x400') }}" alt="Sobre nós">
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'lead_capture')
            @php
                // Toggles do content — permitem estender o form de conversão pra
                // capturar CPF/endereço/responsável quando a landing está
                // vinculada a um Projeto (Opção C). Mantém o comportamento
                // original quando nenhum toggle está marcado.
                $c = $section->content ?? [];
                $lcInputStyle = 'width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px;';
                $lcEnCpf      = (bool) ($c['enable_cpf']        ?? false);
                $lcEnBirth    = (bool) ($c['enable_birth_date'] ?? false);
                $lcEnAddress  = (bool) ($c['enable_address']    ?? false);
                $lcEnCity     = (bool) ($c['enable_city']       ?? false);
                $lcEnGuardian = (bool) ($c['enable_guardian']   ?? false);
                $lcEnPhone    = (bool) ($c['enable_phone']      ?? false);
                $lcReqPhone   = (bool) ($c['require_phone']     ?? false);
            @endphp
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
                                <input type="text"  name="name"  required style="{{ $lcInputStyle }}" placeholder="Seu nome completo">
                                <input type="email" name="email" required style="{{ $lcInputStyle }}" placeholder="Seu melhor e-mail">
                                @if($lcEnPhone || $lcReqPhone)
                                    <input type="text" name="phone" {{ $lcReqPhone ? 'required' : '' }} style="{{ $lcInputStyle }}" placeholder="WhatsApp{{ $lcReqPhone ? '' : ' (opcional)' }}">
                                @endif
                                @if($lcEnCpf)
                                    <input type="text" name="cpf" inputmode="numeric" maxlength="14" style="{{ $lcInputStyle }}" placeholder="CPF (somente números)">
                                @endif
                                @if($lcEnBirth)
                                    {{-- input[type=date] ignora placeholder — label acima resolve --}}
                                    <label style="display:block; font-size:.78rem; color:#475569; margin:2px 4px 4px; font-weight:600;">Data de nascimento</label>
                                    <input type="date" name="birth_date" style="{{ $lcInputStyle }}" aria-label="Data de nascimento">
                                @endif
                                @if($lcEnAddress)
                                    <input type="text" name="address" maxlength="255" style="{{ $lcInputStyle }}" placeholder="Endereço">
                                @endif
                                @if($lcEnCity)
                                    <input type="text" name="city" maxlength="120" style="{{ $lcInputStyle }}" placeholder="Cidade">
                                @endif
                                @if($lcEnGuardian)
                                    <input type="text" name="guardian_name"  maxlength="255" style="{{ $lcInputStyle }}" placeholder="Nome do responsável">
                                    <input type="text" name="guardian_phone" maxlength="30"  style="{{ $lcInputStyle }}" placeholder="Telefone do responsável">
                                @endif

                                {{-- Custom fields (2026-08-06) — configurados no builder da landing --}}
                                @include('landing_pages._custom_fields', ['customFields' => $section->content['custom_fields'] ?? [], 'cfStyle' => $lcInputStyle])

                                <label style="display:flex; gap:10px; align-items:flex-start; margin-bottom: 18px; font-size: .9rem; color:#475569; line-height:1.45;">
                                    <input type="checkbox" name="consent_given" value="1" required style="margin-top: 4px; flex-shrink:0;">
                                    <span>Autorizo o contato e o tratamento dos meus dados conforme a <a href="/privacidade" target="_blank" rel="noopener">Política de Privacidade</a> (LGPD). Você pode cancelar a qualquer momento.</span>
                                </label>
                                <button type="submit" class="btn-cta" style="width: 100%; border: none; cursor: pointer;">{{ $section->content['button_text'] ?? 'Enviar' }}</button>
                                <input type="hidden" name="source" value="lead_capture">
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
                                    <img loading="lazy" src="{{ $photo }}" alt="{{ $it['name'] ?? 'Pessoa' }}" style="width: 92px; height: 92px; border-radius: 28px; object-fit: cover; border: 1px solid #e2e8f0;">
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

        {{-- ── NOVOS TEMPLATES ─────────────────────────────────────────── --}}

        @if($section->type == 'event_card')
            @php
                $evBg     = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#f0f9ff');
                $evAccent = \App\Support\LandingPageSanitizer::cssColor($section->content['accent_color'] ?? null, '#0284c7');
                $evBtn    = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? '#');
            @endphp
            <section style="background: {{ $evBg }}; padding: 72px 0;">
                <div class="container" style="max-width: 860px; margin: 0 auto; padding: 0 24px;">
                    <div style="background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,.08); display: flex; flex-wrap: wrap;">
                        <div style="width: 8px; background: {{ $evAccent }}; flex-shrink: 0; min-height: 100%;"></div>
                        <div style="flex: 1; padding: 48px 40px;">
                            <div style="display: inline-flex; align-items: center; gap: 8px; background: {{ $evAccent }}18; color: {{ $evAccent }}; border-radius: 99px; padding: 5px 14px; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">
                                <i class="fas fa-calendar-star"></i> Evento
                            </div>
                            <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; margin: 0 0 14px; letter-spacing: -1px;">{{ $section->content['title'] ?? 'Evento Especial' }}</h2>
                            <p style="color: #475569; font-size: 1.05rem; line-height: 1.7; margin: 0 0 28px;">{{ $section->content['description'] ?? '' }}</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 32px;">
                                <div style="display: flex; align-items: center; gap: 10px; color: #334155;">
                                    <span style="width: 38px; height: 38px; background: {{ $evAccent }}15; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: {{ $evAccent }};"><i class="fas fa-calendar"></i></span>
                                    <div><div style="font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px;">Data</div><strong>{{ $section->content['date'] ?? '—' }}</strong></div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; color: #334155;">
                                    <span style="width: 38px; height: 38px; background: {{ $evAccent }}15; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: {{ $evAccent }};"><i class="fas fa-clock"></i></span>
                                    <div><div style="font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px;">Horário</div><strong>{{ $section->content['time'] ?? '—' }}</strong></div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; color: #334155;">
                                    <span style="width: 38px; height: 38px; background: {{ $evAccent }}15; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: {{ $evAccent }};"><i class="fas fa-map-pin"></i></span>
                                    <div><div style="font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px;">Local</div><strong>{{ $section->content['location'] ?? '—' }}</strong></div>
                                </div>
                            </div>
                            @if(!empty($section->content['button_text']))
                            <a href="{{ $evBtn }}" style="display: inline-block; background: {{ $evAccent }}; color: #fff; font-weight: 800; padding: 15px 32px; border-radius: 14px; text-decoration: none; font-size: .95rem; box-shadow: 0 6px 20px {{ $evAccent }}40;">
                                {{ $section->content['button_text'] }} →
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'countdown')
            @php
                $cdBg    = \App\Support\LandingPageSanitizer::cssBg($section->content['bg_gradient'] ?? null, 'linear-gradient(135deg,#7c3aed,#4f46e5)');
                $cdColor = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#ffffff');
                $cdBtn   = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? '#');
                $cdDeadline = e($section->content['deadline'] ?? '');
            @endphp
            <section style="background: {{ $cdBg }}; color: {{ $cdColor }}; padding: 80px 0; text-align: center;" data-deadline="{{ $cdDeadline }}">
                <div class="container" style="max-width: 700px; margin: 0 auto; padding: 0 24px;">
                    <p style="font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; opacity: .7; margin-bottom: 12px;">⏳ Tempo restante</p>
                    <h2 style="font-size: 1.8rem; font-weight: 900; margin: 0 0 40px; letter-spacing: -.5px;">{{ $section->content['title'] ?? 'A campanha encerra em:' }}</h2>
                    <div class="lp-countdown" style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 36px;">
                        @foreach(['days' => 'Dias', 'hours' => 'Horas', 'minutes' => 'Min', 'seconds' => 'Seg'] as $key => $label)
                        <div style="background: rgba(255,255,255,.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.2); border-radius: 18px; padding: 20px 24px; min-width: 90px;">
                            <div class="lp-cd-{{ $key }}" style="font-size: 2.8rem; font-weight: 900; letter-spacing: -2px; line-height: 1;">00</div>
                            <div style="font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; opacity: .7; margin-top: 6px;">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                    @if(!empty($section->content['subtitle']))
                    <p style="opacity: .8; font-size: 1rem; margin-bottom: 28px;">{{ $section->content['subtitle'] }}</p>
                    @endif
                    @if(!empty($section->content['button_text']))
                    <a href="{{ $cdBtn }}" style="display: inline-block; background: #fff; color: #4f46e5; font-weight: 900; padding: 16px 36px; border-radius: 14px; text-decoration: none; font-size: 1rem; box-shadow: 0 8px 24px rgba(0,0,0,.15);">
                        {{ $section->content['button_text'] }} →
                    </a>
                    @endif
                </div>
            </section>
            <script>
            (function(){
                var sec = document.querySelector('section[data-deadline]');
                if (!sec) return;
                var deadline = new Date(sec.dataset.deadline);
                function tick() {
                    var diff = Math.max(0, deadline - new Date());
                    var d = Math.floor(diff/864e5), h = Math.floor((diff%864e5)/36e5),
                        m = Math.floor((diff%36e5)/6e4),  s = Math.floor((diff%6e4)/1e3);
                    function fmt(n){ return String(n).padStart(2,'0'); }
                    var el = sec.querySelector.bind(sec);
                    var days=el('.lp-cd-days'), hrs=el('.lp-cd-hours'), mins=el('.lp-cd-minutes'), secs=el('.lp-cd-seconds');
                    if(days) days.textContent=fmt(d);
                    if(hrs)  hrs.textContent=fmt(h);
                    if(mins) mins.textContent=fmt(m);
                    if(secs) secs.textContent=fmt(s);
                }
                tick(); setInterval(tick, 1000);
            })();
            </script>
        @endif

        @if($section->type == 'two_columns')
            @php
                $tcBg  = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#ffffff');
                $tcImg = \App\Support\LandingPageSanitizer::url($section->content['image_url'] ?? '');
                $tcBtn = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? '#');
                $imgRight = ($section->content['image_position'] ?? 'right') === 'right';
            @endphp
            <section style="background: {{ $tcBg }}; padding: 80px 0;">
                <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 24px;">
                    <div style="display: flex; align-items: center; gap: 60px; flex-wrap: wrap; flex-direction: {{ $imgRight ? 'row' : 'row-reverse' }};">
                        <div style="flex: 1; min-width: 280px;">
                            <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0 0 20px;">{{ $section->content['title'] ?? '' }}</h2>
                            <p style="color: #475569; font-size: 1.05rem; line-height: 1.8; margin: 0 0 28px;">{{ $section->content['text'] ?? '' }}</p>
                            @if(!empty($section->content['button_text']))
                            <a href="{{ $tcBtn }}" style="display: inline-flex; align-items: center; gap: 8px; background: var(--primary, #4f46e5); color: #fff; font-weight: 800; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-size: .9rem;">
                                {{ $section->content['button_text'] }} <i class="fas fa-arrow-right" style="font-size:.8rem;"></i>
                            </a>
                            @endif
                        </div>
                        @if($tcImg)
                        <div style="flex: 1; min-width: 280px;">
                            <img loading="lazy" src="{{ $tcImg }}" alt="{{ $section->content['title'] ?? '' }}" style="width: 100%; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,.1); display: block;">
                        </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'membership')
            @php $mbBg = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#f8fafc'); @endphp
            <section style="background: {{ $mbBg }}; padding: 80px 0; text-align: center;">
                <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 24px;">
                    <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0 0 12px;">{{ $section->content['title'] ?? 'Torne-se um Apoiador' }}</h2>
                    <p style="color: #64748b; font-size: 1.05rem; margin: 0 0 48px;">{{ $section->content['subtitle'] ?? '' }}</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; text-align: left;">
                        @foreach($section->content['items'] ?? [] as $mb)
                        @php
                            $mbColor = \App\Support\LandingPageSanitizer::cssColor($mb['color'] ?? null, '#4f46e5');
                            $isHL    = !empty($mb['highlight']);
                        @endphp
                        <div style="background: {{ $isHL ? '#fff' : '#fff' }}; border-radius: 20px; padding: 32px 28px; border: {{ $isHL ? "2px solid {$mbColor}" : '1px solid #e2e8f0' }}; position: relative; box-shadow: {{ $isHL ? '0 12px 40px rgba(0,0,0,.1)' : '0 2px 8px rgba(0,0,0,.04)' }};">
                            @if($isHL)
                            <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: {{ $mbColor }}; color: #fff; font-size: .65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 5px 16px; border-radius: 99px; white-space: nowrap;">Mais Popular</div>
                            @endif
                            <div style="width: 44px; height: 44px; background: {{ $mbColor }}20; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                <i class="fas fa-star" style="color: {{ $mbColor }};"></i>
                            </div>
                            <h3 style="font-size: 1.2rem; font-weight: 900; color: #0f172a; margin: 0 0 6px;">{{ $mb['name'] ?? '' }}</h3>
                            <div style="font-size: 1.6rem; font-weight: 900; color: {{ $mbColor }}; margin-bottom: 20px;">{{ $mb['price'] ?? '' }}</div>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                                @foreach(explode('|', $mb['benefits'] ?? '') as $benefit)
                                <li style="display: flex; align-items: center; gap: 10px; font-size: .88rem; color: #475569;">
                                    <i class="fas fa-check" style="color: {{ $mbColor }}; font-size: .75rem; flex-shrink: 0;"></i>
                                    {{ trim($benefit) }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'rich_text')
            @php
                $rtBg    = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#ffffff');
                $rtColor = \App\Support\LandingPageSanitizer::cssColor($section->content['text_color'] ?? null, '#1e293b');
                $rtAlign = in_array($section->content['text_align'] ?? 'center', ['left','center','right']) ? $section->content['text_align'] : 'center';
            @endphp
            <section style="background: {{ $rtBg }}; padding: 72px 0;">
                <div class="container" style="max-width: 760px; margin: 0 auto; padding: 0 24px; text-align: {{ $rtAlign }};">
                    @if(!empty($section->content['title']))
                    <h2 style="font-size: 1.9rem; font-weight: 900; color: {{ $rtColor }}; letter-spacing: -1px; margin: 0 0 24px;">{{ $section->content['title'] }}</h2>
                    @endif
                    <p style="color: {{ $rtColor }}; font-size: 1.1rem; line-height: 1.85; opacity: .85;">{{ $section->content['text'] ?? '' }}</p>
                </div>
            </section>
        @endif

        @if($section->type == 'awards')
            @php $awBg = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#fafafa'); @endphp
            <section style="background: {{ $awBg }}; padding: 80px 0; text-align: center;">
                <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 24px;">
                    <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0 0 12px;">{{ $section->content['title'] ?? 'Reconhecimentos' }}</h2>
                    @if(!empty($section->content['subtitle']))<p style="color: #64748b; font-size: 1rem; margin: 0 0 48px;">{{ $section->content['subtitle'] }}</p>@endif
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; text-align: left;">
                        @foreach($section->content['items'] ?? [] as $aw)
                        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px 24px;">
                            <div style="font-size: 2rem; margin-bottom: 12px;">{{ $aw['icon'] ?? '🏆' }}</div>
                            <h4 style="font-size: .95rem; font-weight: 800; color: #0f172a; margin: 0 0 8px;">{{ $aw['title'] ?? '' }}</h4>
                            <p style="color: #64748b; font-size: .83rem; line-height: 1.6; margin: 0;">{{ $aw['desc'] ?? '' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'cta_whatsapp')
            @php
                $waBg  = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#f0fdf4');
                $waPhone = preg_replace('/\D/', '', $section->content['phone'] ?? '5511999999999');
                $waMsg = rawurlencode($section->content['message'] ?? 'Olá! Quero saber mais.');
                $waBtn = \App\Support\LandingPageSanitizer::url($section->content['button_url'] ?? "https://wa.me/{$waPhone}?text={$waMsg}");
            @endphp
            <section style="background: {{ $waBg }}; padding: 80px 0; text-align: center;">
                <div class="container" style="max-width: 700px; margin: 0 auto; padding: 0 24px;">
                    <div style="width: 72px; height: 72px; background: #25D366; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 10px 30px rgba(37,211,102,.3);">
                        <i class="fab fa-whatsapp" style="font-size: 2rem; color: #fff;"></i>
                    </div>
                    <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0 0 16px;">{{ $section->content['title'] ?? 'Fale no WhatsApp' }}</h2>
                    <p style="color: #475569; font-size: 1.05rem; line-height: 1.7; margin: 0 0 32px;">{{ $section->content['subtitle'] ?? '' }}</p>
                    <a href="https://wa.me/{{ $waPhone }}?text={{ $waMsg }}" target="_blank" rel="noopener"
                       style="display: inline-flex; align-items: center; gap: 10px; background: #25D366; color: #fff; font-weight: 900; padding: 18px 40px; border-radius: 16px; text-decoration: none; font-size: 1rem; box-shadow: 0 8px 30px rgba(37,211,102,.3);">
                        <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i>
                        {{ $section->content['button_text'] ?? 'Iniciar Conversa' }}
                    </a>
                </div>
            </section>
        @endif

        @if($section->type == 'pricing')
            @php $prBg = \App\Support\LandingPageSanitizer::cssColor($section->content['bg_color'] ?? null, '#ffffff'); @endphp
            <section style="background: {{ $prBg }}; padding: 80px 0; text-align: center;">
                <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 0 24px;">
                    <h2 style="font-size: 2rem; font-weight: 900; color: #0f172a; letter-spacing: -1px; margin: 0 0 12px;">{{ $section->content['title'] ?? 'Planos e Valores' }}</h2>
                    @if(!empty($section->content['subtitle']))<p style="color: #64748b; font-size: 1rem; margin: 0 0 48px;">{{ $section->content['subtitle'] }}</p>@endif
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; text-align: left;">
                        @foreach($section->content['items'] ?? [] as $pr)
                        @php
                            $prColor = \App\Support\LandingPageSanitizer::cssColor($pr['color'] ?? null, '#4f46e5');
                            $prHL    = !empty($pr['highlight']);
                        @endphp
                        <div style="background: {{ $prHL ? $prColor : '#fff' }}; border-radius: 20px; padding: 36px 28px; border: {{ $prHL ? 'none' : '1px solid #e2e8f0' }}; position: relative; box-shadow: {{ $prHL ? '0 20px 50px rgba(0,0,0,.15)' : '0 2px 8px rgba(0,0,0,.04)' }}; color: {{ $prHL ? '#fff' : '#0f172a' }};">
                            @if($prHL)
                            <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #fff; color: {{ $prColor }}; font-size: .65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 5px 16px; border-radius: 99px; white-space: nowrap;">Recomendado</div>
                            @endif
                            <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0 0 8px; opacity: {{ $prHL ? '1' : '1' }};">{{ $pr['name'] ?? '' }}</h3>
                            <div style="font-size: 2.2rem; font-weight: 900; margin-bottom: 4px; letter-spacing: -1px;">{{ $pr['price'] ?? '' }}<span style="font-size: 1rem; font-weight: 600; opacity: .7;">{{ $pr['period'] ?? '' }}</span></div>
                            <hr style="border: none; border-top: 1px solid {{ $prHL ? 'rgba(255,255,255,.2)' : '#f1f5f9' }}; margin: 20px 0;">
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                                @foreach(explode('|', $pr['features'] ?? '') as $feat)
                                <li style="display: flex; align-items: center; gap: 10px; font-size: .88rem; opacity: .9;">
                                    <i class="fas fa-check-circle" style="color: {{ $prHL ? 'rgba(255,255,255,.8)' : $prColor }}; font-size: .8rem; flex-shrink: 0;"></i>
                                    {{ trim($feat) }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
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
                <img loading="lazy" id="lpLightboxImg" src="" alt="Imagem" style="max-width: 100%; max-height: 82vh; object-fit: contain; display:block;">
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
