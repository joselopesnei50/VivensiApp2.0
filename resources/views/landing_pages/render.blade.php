<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: {{ $page->settings['theme_color'] ?? '#6366f1' }};
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
            <section class="section-hero" style="background: {{ $section->content['bg_gradient'] ?? $section->content['bg_color'] ?? '#f8fafc' }}; color: {{ $section->content['text_color'] ?? '#ffffff' }}; position: relative; overflow: hidden;">
                <!-- Efeito de Fundo -->
                <div style="position: absolute; top: -10%; right: -5%; width: 40%; height: 60%; background: rgba(255,255,255,0.05); border-radius: 50%; blur: 100px;"></div>
                
                <div class="container" style="position: relative; z-index: 2;">
                    <h1 class="hero-title">{{ $section->content['title'] ?? 'Título Impactante' }}</h1>
                    <p class="hero-subtitle" style="color: {{ ($section->content['text_color'] ?? '') == '#ffffff' ? 'rgba(255,255,255,0.8)' : '#64748b' }}">{{ $section->content['subtitle'] ?? 'Uma descrição sobre sua causa.' }}</p>
                    <a href="#contato" class="btn-cta" style="box-shadow: 0 10px 20px rgba(0,0,0,0.15);">{{ $section->content['button_text'] ?? 'Saiba Mais' }}</a>
                </div>
            </section>
        @endif

        @if($section->type == 'stats')
            <section style="padding: 60px 0; background: {{ $section->content['bg_color'] ?? '#0f172a' }}; color: {{ $section->content['text_color'] ?? '#ffffff' }};">
                <div class="container">
                    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 30px; text-align: center;">
                        @foreach($section->content['items'] ?? [] as $stat)
                        <div style="flex: 1; min-width: 200px;">
                            <h2 style="font-size: 3rem; margin: 0; color: {{ $section->content['text_color'] ?? '#ffffff' }};">{{ $stat['value'] ?? '0' }}</h2>
                            <p style="text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; opacity: 0.8;">{{ $stat['label'] ?? 'Impacto' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'header_nav')
            <nav style="background: {{ $section->content['bg_color'] ?? '#ffffff' }}; padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px);">
                <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
                    <img src="{{ $section->content['logo_url'] ?? '' }}" alt="Logo" style="height: 40px;">
                    <div style="display: flex; gap: 30px;">
                        @foreach($section->content['links'] ?? [] as $link)
                            <a href="{{ $link['url'] ?? '#' }}" style="text-decoration: none; color: {{ $section->content['text_color'] ?? '#1e293b' }}; font-weight: 600; font-size: 0.9rem; transition: color 0.3s;">{{ $link['label'] ?? 'Link' }}</a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif

        @if($section->type == 'who_we_are')
            <section style="padding: 100px 0; background: {{ $section->content['bg_color'] ?? '#f8fafc' }};">
                <div class="container" style="display: flex; align-items: center; gap: 60px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; border-radius: 30px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.1);">
                        <img src="{{ $section->content['image_url'] ?? '' }}" style="width: 100%; display: block;">
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
                                <img src="{{ $service['image'] ?? '' }}" style="width: 100%; height: 220px; object-fit: cover;">
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

        @if($section->type == 'footer_links')
            <footer style="padding: 80px 0; background: {{ $section->content['bg_color'] ?? '#0f172a' }}; color: {{ $section->content['text_color'] ?? '#ffffff' }};">
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
                            <a href="{{ $section->content['facebook'] ?? '#' }}" style="color: inherit; opacity: 0.7;"><i class="fab fa-facebook"></i></a>
                            <a href="{{ $section->content['instagram'] ?? '#' }}" style="color: inherit; opacity: 0.7;"><i class="fab fa-instagram"></i></a>
                            <a href="{{ $section->content['linkedin'] ?? '#' }}" style="color: inherit; opacity: 0.7;"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                <div class="container" style="margin-top: 60px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 0.8rem; opacity: 0.5;">
                    &copy; {{ date('Y') }} {{ $section->content['company_name'] ?? 'Vivensi' }}. Todos os direitos reservados.
                </div>
            </footer>
        @endif

        @if($section->type == 'products')
            <section style="padding: 100px 0; background: {{ $section->content['bg_color'] ?? '#ffffff' }};">
                <div class="container">
                    <h2 style="text-align: center; margin-bottom: 60px;">{{ $section->content['title'] ?? 'Nossos Produtos' }}</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
                        @foreach($section->content['items'] ?? [] as $item)
                        <div class="feature-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                            <img src="{{ $item['image'] ?? 'https://via.placeholder.com/300x200' }}" style="width: 100%; height: 200px; object-fit: crop;">
                            <div style="padding: 25px;">
                                <h3 style="margin-top: 0; font-size: 1.25rem;">{{ $item['name'] ?? 'Produto' }}</h3>
                                <p style="color: var(--primary); font-weight: 800; font-size: 1.1rem; margin: 10px 0;">{{ $item['price'] ?? 'Sob consulta' }}</p>
                                <a href="{{ $item['link'] ?? '#' }}" class="btn-cta" style="width: 100%; text-align: center; padding: 12px; font-size: 0.8rem;">Comprar Agora</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'video')
            <section style="padding: 100px 0; background: {{ $section->content['bg_color'] ?? '#f8fafc' }};">
                <div class="container" style="max-width: 900px;">
                    <h2 style="text-align: center; margin-bottom: 40px;">{{ $section->content['title'] ?? 'Assista ao Vídeo' }}</h2>
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; border-radius: 24px; overflow: hidden; box-shadow: 0 40px 80px rgba(0,0,0,0.1);">
                        <iframe src="{{ $section->content['video_url'] ?? '' }}" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'social_links')
            <section style="padding: 60px 0; background: {{ $section->content['bg_color'] ?? '#ffffff' }}; text-align: center;">
                <div class="container">
                    <h2 style="margin-bottom: 40px;">{{ $section->content['title'] ?? 'Nossas RedesSociais' }}</h2>
                    <div style="display: flex; justify-content: center; gap: 25px; flex-wrap: wrap;">
                        @if($section->content['instagram'] ?? '')
                            <a href="{{ $section->content['instagram'] }}" target="_blank" style="width: 60px; height: 60px; background: #e1306c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if($section->content['facebook'] ?? '')
                            <a href="{{ $section->content['facebook'] }}" target="_blank" style="width: 60px; height: 60px; background: #1877f2; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if($section->content['linkedin'] ?? '')
                            <a href="{{ $section->content['linkedin'] }}" target="_blank" style="width: 60px; height: 60px; background: #0077b5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-linkedin-in"></i></a>
                        @endif
                        @if($section->content['youtube'] ?? '')
                            <a href="{{ $section->content['youtube'] }}" target="_blank" style="width: 60px; height: 60px; background: #ff0000; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: transform 0.3s;"><i class="fab fa-youtube"></i></a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'link_bio')
            <section style="padding: 80px 0; background: {{ $section->content['bg_gradient'] ?? '#0f172a' }}; min-height: 100vh; display: flex; align-items: center;">
                <div class="container" style="max-width: 500px; text-align: center; color: white;">
                    <img src="{{ $section->content['profile_image'] ?? '' }}" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <h1 style="font-size: 1.8rem; margin-bottom: 10px;">{{ $section->content['name'] ?? 'Nome do Perfil' }}</h1>
                    <p style="opacity: 0.8; margin-bottom: 40px;">{{ $section->content['bio'] ?? 'Sua biografia aqui.' }}</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        @foreach($section->content['links'] ?? [] as $link)
                            <a href="{{ $link['url'] ?? '#' }}" target="_blank" 
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
                            <img src="{{ $section->content['image_url'] ?? 'https://via.placeholder.com/600x400' }}" alt="Sobre nós">
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($section->type == 'lead_capture')
            <section class="section-lead" style="padding: 100px 0; background: {{ $section->content['bg_color'] ?? '#ffffff' }};">
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
            <section class="section-newsletter" style="padding: 60px 0; background: {{ $section->content['bg_color'] ?? '#eff6ff' }}; text-align: center;">
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
            <a href="https://wa.me/{{ $section->content['phone'] ?? '5511000000000' }}?text={{ urlencode($section->content['message'] ?? 'Olá!') }}" 
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

    <footer style="padding: 40px 0; text-align: center; border-top: 1px solid #e2e8f0; font-size: 0.9rem; color: #64748b; background: white;">
        &copy; {{ date('Y') }} Vivensi - Mantido por {{ $page->tenant_id == 1 ? 'Instituto Vivensi' : 'Organização Social' }}
        <div style="margin-top: 15px; font-weight: 600; color: var(--primary);">
            Desenvolvido pelo sistema Vivensi App com carinho! <i class="fas fa-heart" style="color: #ef4444;"></i>
        </div>
    </footer>

</body>
</html>
