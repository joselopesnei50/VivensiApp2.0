<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Builder: {{ $page->title }} | Vivensi LEGO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-item: #1e293b;
            --primary: #6366f1;
            --accent: #10b981;
        }
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Outfit', sans-serif; overflow: hidden; background: #cbd5e1; }
        
        /* Layout Principal */
        .lego-builder { display: flex; height: 100vh; width: 100vw; }

        /* Sidebar */
        .lego-sidebar { width: 320px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; box-shadow: 10px 0 30px rgba(0,0,0,0.2); z-index: 100; position: relative; }
        .sidebar-header { padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
        .sidebar-header h3 { margin: 0; font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; }
        
        .active-list { flex: 1; overflow-y: auto; padding: 20px; }
        .block-item { 
            background: var(--sidebar-item); 
            margin-bottom: 10px; 
            padding: 15px; 
            border-radius: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .block-item:hover { transform: translateX(5px); border-color: var(--primary); background: #334155; }
        .block-item .info { display: flex; align-items: center; gap: 12px; }
        .block-item i.drag { color: #475569; cursor: grab; }
        .block-item span { font-size: 0.85rem; font-weight: 500; }
        
        .btn-add-main { 
            margin: 20px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 18px; 
            border-radius: 12px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            cursor: pointer; 
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            transition: all 0.2s;
        }
        .btn-add-main:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(99, 102, 241, 0.4); }

        /* Preview Area */
        .lego-canvas { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .canvas-header { padding: 15px 40px; background: white; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .canvas-body { flex: 1; padding: 40px; overflow-y: auto; display: flex; justify-content: center; }
        .iframe-container { width: 100%; max-width: 1200px; height: 100%; background: white; box-shadow: 0 30px 60px rgba(0,0,0,0.15); border-radius: 15px; overflow: hidden; position: relative; }
        iframe { width: 100%; height: 100%; border: none; }

        /* Modal Galeria (A Caixa Revolucionária) */
        .lego-modal { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(15, 23, 42, 0.9); display: none; 
            z-index: 1000; align-items: center; justify-content: center;
            backdrop-filter: blur(10px);
        }
        .modal-content { 
            background: white; width: 90%; max-width: 900px; 
            border-radius: 30px; overflow: hidden; 
            display: flex; flex-direction: column; height: 80vh;
            animation: modalIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        
        .modal-header { padding: 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 30px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        
        .gallery-card { 
            background: #f8fafc; border: 2px solid #f1f5f9; border-radius: 20px; padding: 25px; 
            text-align: center; cursor: pointer; transition: all 0.3s;
        }
        .gallery-card:hover { border-color: var(--primary); background: #eef2ff; transform: translateY(-5px); }
        .gallery-card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 15px; display: block; }
        .gallery-card span { font-weight: 700; color: #1e293b; display: block; }
        .gallery-card p { font-size: 0.75rem; color: #64748b; margin: 5px 0 0; }

        /* Editor Overlay */
        .editor-overlay { 
            position: absolute; top:0; left:0; width: 100%; height: 100%; 
            background: var(--sidebar-bg); display: none; flex-direction: column; z-index: 150; 
        }
        .editor-header { padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 15px; }
        .editor-body { flex: 1; overflow-y: auto; padding: 25px; }

        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px; letter-spacing: 1px; }
        .form-input { width: 100%; background: #1e293b; border: 1px solid #334155; color: white; padding: 12px; border-radius: 8px; font-family: inherit; font-size: 0.9rem; border: none; }
        .form-input:focus { outline: 2px solid var(--primary); background: #0f172a; }
        
        .btn-save { background: var(--accent); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 700; width: 100%; cursor: pointer; }
    </style>
</head>
<body>

    <div class="lego-builder">
        <!-- SIDEBAR -->
        <div class="lego-sidebar">
            <div class="sidebar-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="{{ rtrim(request()->getBaseUrl(), '/') . '/ngo/landing-pages' }}" style="color: white;"><i class="fas fa-chevron-left"></i></a>
                    <h3 id="page-title">{{ $page->title }}</h3>
                </div>
                <i class="fas fa-sliders-h" onclick="openSettings()" title="Configurações / SEO" style="color: #64748b; cursor: pointer;"></i>
            </div>

            <div class="active-list" id="active-blocks">
                @foreach($sections as $section)
                    <div class="block-item" onclick="openEditor({{ $section->id }}, '{{ $section->type }}', {{ json_encode($section->content) }})">
                        <div class="info">
                            <i class="fas fa-grip-lines drag"></i>
                            <span>
                                @php
                                    $names = [
                                        'hero' => 'Hero Impacto',
                                        'lead_capture' => 'Formulário Conversão',
                                        'stats' => 'Estatísticas',
                                        'testimonials' => 'Depoimentos',
                                        'features' => 'Recursos/Vantagens',
                                        'about' => 'Sobre Nós',
                                        'whatsapp' => 'Botão WhatsApp',
                                        'contact' => 'Contato/Endereço',
                                        'cta_banner' => 'Banner CTA',
                                        'faq' => 'FAQ (Perguntas)',
                                        'image_gallery' => 'Galeria de Fotos',
                                        'partners_logos' => 'Parceiros/Logos',
                                        'steps_timeline' => 'Timeline/Etapas',
                                        'impact_cards' => 'Cards de Impacto',
                                        'before_after' => 'Antes/Depois',
                                        'quick_donation' => 'Doação Rápida',
                                        'pix_donation' => 'Doação PIX (Copia e Cola)',
                                        'cta_cards' => 'Cards CTA (Ajudar)',
                                        'map_embed' => 'Mapa/Localização',
                                        'final_cta_form' => 'CTA Final + Formulário',
                                        'transparency_numbers' => 'Transparência em Números',
                                        'campaign_progress' => 'Meta/Progresso',
                                        'team_cards' => 'Time/Equipe',
                                    ];
                                @endphp
                                {{ $names[$section->type] ?? ucfirst($section->type) }}
                            </span>
                        </div>
                        <i class="fas fa-trash-alt" onclick="deleteBlock(event, {{ $section->id }})" style="color: #475569; font-size: 0.8rem;"></i>
                    </div>
                @endforeach
            </div>

            <button class="btn-add-main" onclick="openGallery()">+ Adicionar Bloco</button>

            <!-- EDITOR OVERLAY -->
            <div class="editor-overlay" id="editor-overlay">
                <div class="editor-header">
                    <i class="fas fa-arrow-left" onclick="closeEditor()" style="cursor: pointer; color: #94a3b8;"></i>
                    <h4 style="margin: 0; text-transform: uppercase; font-size: 0.8rem;" id="editor-type-title">Editar Bloco</h4>
                </div>
                <div class="editor-body">
                    <form id="editor-form">
                        <div id="editor-fields"></div>
                    </form>
                </div>
                <div style="padding: 25px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <button class="btn-save" onclick="saveBlock()">Salvar no LEGO</button>
                </div>
            </div>
        </div>

        <!-- CANVAS -->
        <div class="lego-canvas">
            <div class="canvas-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.8rem; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; color: #64748b; font-weight: 600;">Modo Edição Ativado</span>
                    @if(($page->status ?? 'draft') === 'published')
                        <span style="font-size: 0.75rem; background: rgba(16,185,129,.12); padding: 4px 10px; border-radius: 999px; color: #059669; font-weight: 900; letter-spacing:.06em; text-transform: uppercase;">Publicado</span>
                    @else
                        <span style="font-size: 0.75rem; background: rgba(100,116,139,.12); padding: 4px 10px; border-radius: 999px; color: #475569; font-weight: 900; letter-spacing:.06em; text-transform: uppercase;">Rascunho</span>
                    @endif
                </div>
                <div style="display: flex; gap: 15px;">
                    <button onclick="window.open('{{ rtrim(request()->getBaseUrl(), '/') . '/lp/' . $page->slug }}', '_blank')" style="background: white; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; color: #475569;">
                        <i class="fas fa-eye"></i> Visualizar Online
                    </button>
                    <button onclick="copyPublicLink()" style="background: white; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; color: #0f172a;">
                        <i class="fas fa-link"></i> Copiar link
                    </button>
                    <button onclick="duplicatePage()" style="background: #fff; border: 1px solid #e2e8f0; padding: 10px 18px; border-radius: 12px; font-weight: 800; cursor: pointer; color: #4f46e5;">
                        <i class="fas fa-clone"></i> Duplicar
                    </button>
                    <button onclick="deletePage()" style="background: #fff; border: 1px solid rgba(239,68,68,.25); padding: 10px 18px; border-radius: 12px; font-weight: 900; cursor: pointer; color: #ef4444;">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                    <button id="btn-publish" data-status="{{ ($page->status ?? 'draft') === 'published' ? 'published' : 'draft' }}" onclick="togglePublish()" style="background: var(--accent); color: white; border: none; padding: 10px 25px; border-radius: 12px; font-weight: 900; cursor: pointer;">
                        <span id="publish-text">{{ ($page->status ?? 'draft') === 'published' ? 'Despublicar' : 'Publicar' }}</span>
                    </button>
                </div>
            </div>
            <div class="canvas-body">
                <div class="iframe-container">
                    <iframe id="preview-iframe" src="{{ rtrim(request()->getBaseUrl(), '/') . '/lp/' . $page->slug }}"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GALERIA -->
    <div class="lego-modal" id="gallery-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="margin: 0; color: #1e293b;">Galeria de Blocos</h2>
                <i class="fas fa-times" onclick="closeGallery()" style="cursor: pointer; font-size: 1.5rem; color: #94a3b8;"></i>
            </div>
            <div class="modal-body">
                <div class="gallery-card" onclick="addBlock('header_nav')"><i class="fas fa-window-maximize"></i><span>Header / Menu</span><p>Menu de navegação superior.</p></div>
                <div class="gallery-card" onclick="addBlock('hero')"><i class="fas fa-id-card"></i><span>Hero / Início</span><p>Cabeçalho principal com CTA.</p></div>
                <div class="gallery-card" onclick="addBlock('who_we_are')"><i class="fas fa-users"></i><span>Quem Somos</span><p>Seção sobre a instituição.</p></div>
                <div class="gallery-card" onclick="addBlock('services_grid')"><i class="fas fa-th"></i><span>Serviços (3 col)</span><p>Grade de serviços com imagens.</p></div>
                <div class="gallery-card" onclick="addBlock('cta_banner')"><i class="fas fa-bullhorn"></i><span>Banner CTA</span><p>Chamada forte para ação.</p></div>
                <div class="gallery-card" onclick="addBlock('faq')"><i class="fas fa-circle-question"></i><span>FAQ</span><p>Perguntas e respostas.</p></div>
                <div class="gallery-card" onclick="addBlock('image_gallery')"><i class="fas fa-images"></i><span>Galeria</span><p>Grid de imagens com legenda.</p></div>
                <div class="gallery-card" onclick="addBlock('partners_logos')"><i class="fas fa-handshake"></i><span>Parceiros</span><p>Logos de apoiadores.</p></div>
                <div class="gallery-card" onclick="addBlock('steps_timeline')"><i class="fas fa-route"></i><span>Timeline/Etapas</span><p>Passo a passo em sequência.</p></div>
                <div class="gallery-card" onclick="addBlock('impact_cards')"><i class="fas fa-sparkles"></i><span>Cards de Impacto</span><p>Cards bonitos com ícones.</p></div>
                <div class="gallery-card" onclick="addBlock('before_after')"><i class="fas fa-clone"></i><span>Antes/Depois</span><p>Comparativo visual com texto.</p></div>
                <div class="gallery-card" onclick="addBlock('quick_donation')"><i class="fas fa-donate"></i><span>Doação Rápida</span><p>Opções de valor + CTA.</p></div>
                <div class="gallery-card" onclick="addBlock('pix_donation')"><i class="fas fa-qrcode"></i><span>Doação PIX</span><p>Copia e cola + QR Code.</p></div>
                <div class="gallery-card" onclick="addBlock('cta_cards')"><i class="fas fa-layer-group"></i><span>Cards CTA</span><p>3 opções para ação.</p></div>
                <div class="gallery-card" onclick="addBlock('map_embed')"><i class="fas fa-map-location-dot"></i><span>Mapa</span><p>Embed do Google Maps.</p></div>
                <div class="gallery-card" onclick="addBlock('final_cta_form')"><i class="fas fa-rocket"></i><span>CTA Final</span><p>Fechamento com formulário.</p></div>
                <div class="gallery-card" onclick="addBlock('transparency_numbers')"><i class="fas fa-shield-heart"></i><span>Transparência</span><p>Números para confiança.</p></div>
                <div class="gallery-card" onclick="addBlock('campaign_progress')"><i class="fas fa-chart-line"></i><span>Meta da Campanha</span><p>Barra de progresso/objetivo.</p></div>
                <div class="gallery-card" onclick="addBlock('team_cards')"><i class="fas fa-people-group"></i><span>Equipe</span><p>Cards do time com redes.</p></div>
                <div class="gallery-card" onclick="addBlock('link_bio')"><i class="fas fa-link"></i><span>Bio Instagram</span><p>Layout estilo Linktree.</p></div>
                <div class="gallery-card" onclick="addBlock('products')"><i class="fas fa-shopping-bag"></i><span>Produtos</span><p>Venda produtos ou serviços.</p></div>
                <div class="gallery-card" onclick="addBlock('video')"><i class="fas fa-play-circle"></i><span>Vídeo</span><p>Embed do YouTube ou Vimeo.</p></div>
                <div class="gallery-card" onclick="addBlock('lead_capture')"><i class="fas fa-user-plus"></i><span>Conversão</span><p>Capture contatos e leads.</p></div>
                <div class="gallery-card" onclick="addBlock('stats')"><i class="fas fa-chart-bar"></i><span>Números</span><p>Exiba seu impacto social.</p></div>
                <div class="gallery-card" onclick="addBlock('testimonials')"><i class="fas fa-quote-right"></i><span>Depoimentos</span><p>Mostre o que dizem de você.</p></div>
                <div class="gallery-card" onclick="addBlock('social_links')"><i class="fas fa-share-alt"></i><span>Redes Sociais</span><p>Links para seus perfis.</p></div>
                <div class="gallery-card" onclick="addBlock('footer_links')"><i class="fas fa-shoe-prints"></i><span>Rodapé</span><p>Finalização da página.</p></div>
            </div>
        </div>
    </div>

    <!-- MODAL CONFIG/SEO -->
    <div class="lego-modal" id="settings-modal">
        <div class="modal-content" style="max-width: 860px;">
            <div class="modal-header">
                <div>
                    <h2 style="margin: 0; color: #1e293b;">Configurações & SEO</h2>
                    <div style="margin-top: 6px; color:#64748b; font-weight:700; font-size:.9rem;">
                        Ajuste o título, cor do tema e os metadados de compartilhamento (Google/WhatsApp/Instagram).
                    </div>
                </div>
                <i class="fas fa-times" onclick="closeSettings()" style="cursor: pointer; font-size: 1.5rem; color: #94a3b8;"></i>
            </div>
            <div class="modal-body" style="display:block;">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">Título da Página (interno)</div>
                        <input id="st_title" type="text" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="Ex: Campanha de Natal">
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Aparece nas listas e no builder.</div>
                    </div>
                    <div>
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">Cor do Tema</div>
                        <div style="display:flex; gap: 10px; align-items:center;">
                            <input id="st_theme_color" type="color" style="width: 52px; height: 44px; border:none; background: transparent; padding:0;">
                            <input id="st_theme_color_hex" type="text" style="flex:1; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="#4f46e5">
                        </div>
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Define a cor principal (botões e destaques).</div>
                    </div>
                </div>

                <hr style="margin: 22px 0; border:none; border-top: 1px solid #f1f5f9;">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">SEO Title</div>
                        <input id="st_seo_title" type="text" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="Título para Google e compartilhamento">
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Recomendado: até ~60–70 caracteres.</div>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">SEO Description</div>
                        <textarea id="st_seo_desc" rows="3" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="Descrição curta para resultados do Google e redes."></textarea>
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Recomendado: ~140–160 caracteres.</div>
                    </div>

                    <div>
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">OG Image (URL)</div>
                        <input id="st_og_image" type="url" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="https://.../capa.jpg">
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Imagem para WhatsApp/Instagram/LinkedIn.</div>
                        <div style="margin-top: 10px; display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
                            <input id="st_og_file" type="file" accept="image/*" style="flex:1;">
                            <button type="button" id="btn-upload-og" onclick="uploadOgImage()" style="background:#4f46e5; color:#fff; border:none; padding: 10px 12px; border-radius: 12px; font-weight: 900; cursor:pointer;">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 8px;">Favicon (URL)</div>
                        <input id="st_favicon" type="url" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;" placeholder="https://.../favicon.png">
                        <div style="margin-top: 6px; color:#94a3b8; font-size:.85rem;">Ícone na aba do navegador.</div>
                        <div style="margin-top: 10px; display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
                            <input id="st_favicon_file" type="file" accept="image/*" style="flex:1;">
                            <button type="button" id="btn-upload-favicon" onclick="uploadFavicon()" style="background:#0ea5e9; color:#fff; border:none; padding: 10px 12px; border-radius: 12px; font-weight: 900; cursor:pointer;">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 18px; display:flex; gap: 10px; justify-content: space-between; align-items:center; flex-wrap: wrap;">
                    <div style="color:#64748b; font-weight:800; font-size:.9rem;">
                        Link público: <span style="color:#0f172a;">{{ request()->getSchemeAndHttpHost() . rtrim(request()->getBaseUrl(), '/') . '/lp/' . $page->slug }}</span>
                    </div>
                    <div style="display:flex; gap: 10px;">
                        <button type="button" onclick="closeSettings()" style="background:#f1f5f9; border:1px solid #e2e8f0; color:#0f172a; padding: 10px 14px; border-radius: 12px; font-weight:900; cursor:pointer;">Cancelar</button>
                        <button type="button" id="btn-save-settings" onclick="saveSettings()" style="background: #10b981; border:none; color:#fff; padding: 10px 14px; border-radius: 12px; font-weight:900; cursor:pointer;">Salvar SEO</button>
                    </div>
                </div>

                <hr style="margin: 22px 0; border:none; border-top: 1px solid #f1f5f9;">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items:start;">
                    <div style="grid-column: 1 / -1;">
                        <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px; flex-wrap: wrap;">
                            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">
                                Preview (WhatsApp/Instagram/Google)
                            </div>
                            <a href="{{ request()->getSchemeAndHttpHost() . rtrim(request()->getBaseUrl(), '/') . '/lp/' . $page->slug }}" target="_blank" rel="noopener noreferrer" style="color:#4f46e5; font-weight:900; text-decoration:none;">
                                Abrir página <i class="fas fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <div id="seoPreviewCard" style="border:1px solid #e2e8f0; border-radius: 18px; overflow:hidden; background:#ffffff; box-shadow: 0 18px 40px rgba(15,23,42,.06); display:flex; gap: 14px; align-items: stretch;">
                            <div id="seoPreviewImgWrap" style="width: 210px; min-height: 118px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; flex: 0 0 auto;">
                                <span style="font-weight:900;">OG Image</span>
                            </div>
                            <div style="padding: 14px 14px 14px 0; flex: 1;">
                                <div id="seoPreviewTitle" style="font-weight: 900; color:#0f172a; font-size: 1rem; line-height:1.35; margin-bottom: 6px;">
                                    Título do preview
                                </div>
                                <div id="seoPreviewDesc" style="color:#475569; font-size:.92rem; line-height:1.55; margin-bottom: 10px;">
                                    Descrição do preview (aparece no Google e em redes sociais).
                                </div>
                                <div style="display:flex; gap: 10px; align-items:center; flex-wrap: wrap;">
                                    <div style="display:inline-flex; gap:8px; align-items:center; padding:6px 10px; border-radius: 999px; background: rgba(99,102,241,.10); border:1px solid rgba(99,102,241,.18); color:#4f46e5; font-weight:900; font-size:.75rem;">
                                        <span style="width: 10px; height: 10px; border-radius: 999px; background: #4f46e5;" id="seoPreviewDot"></span>
                                        <span id="seoPreviewUrl">{{ request()->getSchemeAndHttpHost() . rtrim(request()->getBaseUrl(), '/') . '/lp/' . $page->slug }}</span>
                                    </div>
                                    <div style="color:#94a3b8; font-size:.85rem; font-weight:800;">
                                        Dica: para WhatsApp “pegar” a imagem nova, teste em janela anônima e aguarde cache.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const __lpPage = {
            id: {{ (int) $page->id }},
            title: @json($page->title),
            slug: @json($page->slug),
            status: @json($page->status ?? 'draft'),
            settings: @json($page->settings ?? []),
        };

        const __lpBaseUrl = @json(rtrim(request()->getBaseUrl(), '/'));
        function __lpUrl(path) { return (__lpBaseUrl || '') + path; }
        function __lpAbs(path) { return window.location.origin + (__lpBaseUrl || '') + path; }

        let currentId = null;

        function openGallery() { document.getElementById('gallery-modal').style.display = 'flex'; }
        function closeGallery() { document.getElementById('gallery-modal').style.display = 'none'; }

        function openSettings() {
            const modal = document.getElementById('settings-modal');
            if (!modal) return;

            const st = __lpPage.settings || {};
            const title = (__lpPage.title || '');
            const theme = (st.theme_color || '#4f46e5');

            document.getElementById('st_title').value = title;
            document.getElementById('st_theme_color').value = theme;
            document.getElementById('st_theme_color_hex').value = theme;
            document.getElementById('st_seo_title').value = (st.seo_title || title);
            document.getElementById('st_seo_desc').value = (st.seo_description || '');
            document.getElementById('st_og_image').value = (st.og_image_url || '');
            document.getElementById('st_favicon').value = (st.favicon_url || '');

            updateSeoPreview();
            modal.style.display = 'flex';
        }

        function closeSettings() {
            const modal = document.getElementById('settings-modal');
            if (modal) modal.style.display = 'none';
        }

        // keep hex <-> color synchronized
        document.addEventListener('input', function(ev) {
            const t = ev && ev.target ? ev.target.id : '';
            if (t === 'st_theme_color') {
                const v = document.getElementById('st_theme_color').value;
                document.getElementById('st_theme_color_hex').value = v;
            }
            if (t === 'st_theme_color_hex') {
                const v = document.getElementById('st_theme_color_hex').value;
                if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v)) {
                    document.getElementById('st_theme_color').value = v;
                }
            }

            // Live preview updates (SEO card)
            if (t && (t.startsWith('st_') || t === 'st_theme_color' || t === 'st_theme_color_hex')) {
                updateSeoPreview();
            }
        });

        function __safePreviewUrl(u) {
            u = (u || '').trim();
            if (!u) return '';
            if (u.startsWith('/') && !u.startsWith('//')) return u;
            if (/^https?:\/\//i.test(u)) return u;
            return '';
        }

        function updateSeoPreview() {
            const title = (document.getElementById('st_seo_title')?.value || document.getElementById('st_title')?.value || __lpPage.title || '').trim();
            const desc = (document.getElementById('st_seo_desc')?.value || '').trim();
            const og = __safePreviewUrl(document.getElementById('st_og_image')?.value || '');
            const theme = (document.getElementById('st_theme_color_hex')?.value || (__lpPage.settings || {}).theme_color || '#4f46e5').trim();

            const tEl = document.getElementById('seoPreviewTitle');
            const dEl = document.getElementById('seoPreviewDesc');
            const dot = document.getElementById('seoPreviewDot');
            const imgWrap = document.getElementById('seoPreviewImgWrap');

            if (tEl) tEl.textContent = title || 'Título do preview';
            if (dEl) dEl.textContent = desc || 'Descrição do preview (aparece no Google e em redes sociais).';
            if (dot && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(theme)) dot.style.background = theme;

            if (imgWrap) {
                if (og) {
                    imgWrap.innerHTML = '<img src="' + og.replace(/"/g, '') + '" alt="OG Image" style="width:100%; height:100%; object-fit: cover; display:block;">';
                } else {
                    imgWrap.innerHTML = '<span style="font-weight:900; color:#94a3b8;">OG Image</span>';
                }
            }
        }

        async function saveSettings() {
            const btn = document.getElementById('btn-save-settings');
            if (btn) btn.disabled = true;

            const payload = {
                title: document.getElementById('st_title').value || null,
                theme_color: document.getElementById('st_theme_color_hex').value || null,
                seo_title: document.getElementById('st_seo_title').value || null,
                seo_description: document.getElementById('st_seo_desc').value || null,
                og_image_url: document.getElementById('st_og_image').value || '',
                favicon_url: document.getElementById('st_favicon').value || '',
            };

            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/settings'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    let msg = 'Não foi possível salvar SEO.';
                    try {
                        const j = await res.json();
                        if (j && j.message) msg = j.message;
                        if (j && j.errors) {
                            const firstKey = Object.keys(j.errors)[0];
                            if (firstKey) msg = (j.errors[firstKey] || [msg])[0];
                        }
                    } catch (e) {}
                    alert(msg);
                    if (btn) btn.disabled = false;
                    return;
                }

                const j = await res.json();
                if (j && j.page) {
                    __lpPage.title = j.page.title;
                    __lpPage.settings = j.page.settings || {};
                    const pt = document.getElementById('page-title');
                    if (pt) pt.innerText = __lpPage.title;
                    document.title = 'Builder: ' + __lpPage.title + ' | Vivensi LEGO';
                }

                updateSeoPreview();

                // refresh preview so meta tags update
                const iframe = document.getElementById('preview-iframe');
                if (iframe && iframe.src) {
                    const base = iframe.src.split('?')[0];
                    iframe.src = base + '?t=' + Date.now();
                }

                closeSettings();
                alert('SEO salvo com sucesso!');
                if (btn) btn.disabled = false;
            } catch (e) {
                alert('Falha de conexão ao salvar SEO.');
                if (btn) btn.disabled = false;
            }
        }

        async function uploadOgImage() {
            const input = document.getElementById('st_og_file');
            const btn = document.getElementById('btn-upload-og');
            const file = input && input.files ? input.files[0] : null;
            if (!file) return alert('Selecione uma imagem para enviar.');

            if (btn) btn.disabled = true;
            const fd = new FormData();
            fd.append('file', file);

            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/upload-og-image'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                });

                if (!res.ok) {
                    let msg = 'Não foi possível enviar a imagem.';
                    try {
                        const j = await res.json();
                        if (j && j.errors && j.errors.file) msg = j.errors.file[0];
                    } catch (e) {}
                    alert(msg);
                    if (btn) btn.disabled = false;
                    return;
                }

                const j = await res.json();
                if (j && j.url) document.getElementById('st_og_image').value = j.url;
                if (j && j.page) __lpPage.settings = j.page.settings || __lpPage.settings;
                input.value = '';
                updateSeoPreview();

                const iframe = document.getElementById('preview-iframe');
                if (iframe && iframe.src) {
                    const base = iframe.src.split('?')[0];
                    iframe.src = base + '?t=' + Date.now();
                }

                alert('Imagem OG enviada!');
                if (btn) btn.disabled = false;
            } catch (e) {
                alert('Falha de conexão no upload.');
                if (btn) btn.disabled = false;
            }
        }

        async function uploadFavicon() {
            const input = document.getElementById('st_favicon_file');
            const btn = document.getElementById('btn-upload-favicon');
            const file = input && input.files ? input.files[0] : null;
            if (!file) return alert('Selecione um favicon para enviar.');

            if (btn) btn.disabled = true;
            const fd = new FormData();
            fd.append('file', file);

            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/upload-favicon'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd
                });

                if (!res.ok) {
                    let msg = 'Não foi possível enviar o favicon.';
                    try {
                        const j = await res.json();
                        if (j && j.errors && j.errors.file) msg = j.errors.file[0];
                    } catch (e) {}
                    alert(msg);
                    if (btn) btn.disabled = false;
                    return;
                }

                const j = await res.json();
                if (j && j.url) document.getElementById('st_favicon').value = j.url;
                if (j && j.page) __lpPage.settings = j.page.settings || __lpPage.settings;
                input.value = '';
                updateSeoPreview();

                const iframe = document.getElementById('preview-iframe');
                if (iframe && iframe.src) {
                    const base = iframe.src.split('?')[0];
                    iframe.src = base + '?t=' + Date.now();
                }

                alert('Favicon enviado!');
                if (btn) btn.disabled = false;
            } catch (e) {
                alert('Falha de conexão no upload.');
                if (btn) btn.disabled = false;
            }
        }

        async function addBlock(type) {
            try {
                const response = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/section'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: type })
                });

                if (response.ok) {
                    window.location.reload();
                    return;
                }

                const txt = await response.text();
                alert('Não foi possível inserir o bloco (' + response.status + ').\n\n' + txt);
            } catch (e) {
                alert('Falha de conexão ao inserir o bloco. Se você estiver acessando por uma URL diferente do APP_URL, ajuste o APP_URL ou acesse pela mesma URL.');
            }
        }

        function openEditor(id, type, content) {
            currentId = id;
            document.getElementById('editor-type-title').innerText = 'Editando: ' + type.toUpperCase();
            document.getElementById('editor-overlay').style.display = 'flex';
            
            const container = document.getElementById('editor-fields');
            container.innerHTML = '';
            
            // Loop recursivo básico para lidar com objetos simples e arrays (Depoimentos/Itens)
            function createFields(data, prefix = '') {
                for(const [key, value] of Object.entries(data)) {
                    const fieldName = prefix ? `${prefix}[${key}]` : key;
                    
                    if(Array.isArray(value)) {
                        const subTitle = document.createElement('h5');
                        subTitle.style.color = 'var(--primary)';
                        subTitle.style.margin = '20px 0 10px';
                        subTitle.innerText = key.toUpperCase();
                        container.appendChild(subTitle);
                        
                        value.forEach((item, index) => {
                            const hr = document.createElement('hr');
                            hr.style.opacity = '0.1';
                            container.appendChild(hr);
                            createFields(item, `${fieldName}[${index}]`);
                        });
                    } else if(typeof value === 'object' && value !== null) {
                        createFields(value, fieldName);
                    } else {
                        const group = document.createElement('div');
                        group.className = 'form-group';
                        const label = document.createElement('label');
                        label.className = 'form-label';
                        label.innerText = key.replace('_', ' ');
                        
                        const el = (typeof value === 'string' && value.length > 50) ? document.createElement('textarea') : document.createElement('input');
                        el.className = 'form-input';
                        el.name = fieldName;
                        el.value = value;
                        if(el.tagName === 'TEXTAREA') el.rows = 3;
                        
                        group.appendChild(label);
                        group.appendChild(el);
                        container.appendChild(group);
                    }
                }
            }
            
            createFields(content);
        }

        function closeEditor() { document.getElementById('editor-overlay').style.display = 'none'; }

        async function saveBlock() {
            const form = document.getElementById('editor-form');
            const data = new FormData(form);
            
            // Converter FormData para objeto aninhado (suporte a arrays)
            const content = {};
            for (let [key, value] of data.entries()) {
                const keys = key.split(/[\[\]]+/).filter(x => x !== '');
                let obj = content;
                for (let i = 0; i < keys.length; i++) {
                    const k = keys[i];
                    if (i === keys.length - 1) {
                        obj[k] = value;
                    } else {
                        obj[k] = obj[k] || (isNaN(keys[i+1]) ? {} : []);
                        obj = obj[k];
                    }
                }
            }

            const response = await fetch(__lpUrl('/ngo/landing-pages/section/' + currentId), {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: content })
            });

            if (response.ok) {
                closeEditor();
                const iframe = document.getElementById('preview-iframe');
                if (iframe && iframe.src) {
                    const base = iframe.src.split('?')[0];
                    iframe.src = base + '?t=' + Date.now();
                }
                alert('LEGO Atualizado!');
            }
        }

        async function deleteBlock(e, id) {
            e.stopPropagation();
            if(!confirm('Remover este bloco do seu LEGO?')) return;
            const response = await fetch(__lpUrl('/ngo/landing-pages/section/' + id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if(response.ok) window.location.reload();
        }

        function copyPublicLink() {
            const link = __lpAbs('/lp/' + __lpPage.slug);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link)
                    .then(() => alert('Link copiado!'))
                    .catch(() => prompt('Copie o link:', link));
            } else {
                prompt('Copie o link:', link);
            }
        }

        async function duplicatePage() {
            if (!confirm('Duplicar esta Landing Page (com os mesmos blocos)?')) return;

            const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/duplicate'), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });

            if (!res.ok) {
                let msg = 'Não foi possível duplicar.';
                try { const j = await res.json(); msg = j.message || msg; } catch (e) {}
                alert(msg);
                return;
            }

            const j = await res.json();
            if (j && j.builder_url) {
                window.location.href = j.builder_url;
            } else {
                window.location.reload();
            }
        }

        async function deletePage() {
            if (!confirm('Excluir esta Landing Page? Isso remove também os blocos e leads capturados.')) return;

            const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            if (!res.ok) {
                alert('Não foi possível excluir. Tente novamente.');
                return;
            }

            window.location.href = __lpUrl('/ngo/landing-pages');
        }

        async function togglePublish() {
            const btn = document.getElementById('btn-publish');
            const label = document.getElementById('publish-text');
            if (!btn || !label) return;

            btn.disabled = true;
            const prev = label.innerText;
            const status = btn.getAttribute('data-status') || 'draft';
            label.innerText = (status === 'published') ? 'Despublicando...' : 'Publicando...';

            try {
                const endpoint = (status === 'published')
                    ? __lpUrl('/ngo/landing-pages/' + __lpPage.id + '/unpublish')
                    : __lpUrl('/ngo/landing-pages/' + __lpPage.id + '/publish');

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });

                if (!res.ok) {
                    label.innerText = prev;
                    btn.disabled = false;
                    alert('Não foi possível atualizar o status. Tente novamente.');
                    return;
                }

                if (status === 'published') {
                    btn.setAttribute('data-status', 'draft');
                    label.innerText = 'Publicar';
                    alert('Despublicado com sucesso!');
                } else {
                    btn.setAttribute('data-status', 'published');
                    label.innerText = 'Despublicar';
                    alert('Publicado com sucesso!');
                }

                btn.disabled = false;
            } catch (e) {
                label.innerText = prev;
                btn.disabled = false;
                alert('Falha de conexão ao atualizar status.');
            }
        }
    </script>
</body>
</html>
