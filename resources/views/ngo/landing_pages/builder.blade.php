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
        .block-item i.drag { color: #475569; cursor: grab; padding: 4px; }
        .block-item i.drag:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background: #e0e7ff; border: 2px dashed #6366f1 !important; }
        .sortable-chosen { cursor: grabbing !important; }
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
                    <div class="block-item"
                         data-editor-id="{{ $section->id }}"
                         data-editor-type="{{ $section->type }}"
                         data-editor-content="{{ json_encode($section->content) }}"
                         onclick="openEditor(this.dataset.editorId, this.dataset.editorType, JSON.parse(this.getAttribute('data-editor-content')))">
                        <div class="info">
                            <i class="fas fa-grip-lines drag"></i>
                            <span>
                                @php
                                    $names = [
                                        'hero' => 'Hero Impacto',
                                        'hero_image' => 'Hero com Imagem',
                                        'whatsapp_float' => 'Botão WhatsApp Flutuante',
                                        'transparency_portal' => 'Portal Transparência (link dinâmico)',
                                        'impact_dynamic' => 'Impacto Dinâmico (dados do sistema)',
                                        'lead_capture' => 'Formulário de Inscrição',
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
                <div class="gallery-card" onclick="addBlock('hero_image')"><i class="fas fa-image"></i><span>Hero com Imagem</span><p>Cabeçalho com imagem de fundo + overlay.</p></div>
                <div class="gallery-card" onclick="addBlock('whatsapp_float')"><i class="fab fa-whatsapp"></i><span>WhatsApp Flutuante</span><p>Botão fixo no canto que abre conversa.</p></div>
                <div class="gallery-card" onclick="addBlock('transparency_portal')"><i class="fas fa-shield-heart"></i><span>Portal Transparência</span><p>Link dinâmico pro Portal da Transparência da sua OSC.</p></div>
                <div class="gallery-card" onclick="addBlock('impact_dynamic')"><i class="fas fa-chart-line"></i><span>Impacto Dinâmico</span><p>Números reais do sistema (beneficiários, projetos, doações).</p></div>
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
                <div class="gallery-card" onclick="addBlock('final_cta_form')"><i class="fas fa-rocket"></i><span>CTA Final + Formulário</span><p>Fechamento com formulário de inscrição.</p></div>
                <div class="gallery-card" onclick="addBlock('transparency_numbers')"><i class="fas fa-shield-heart"></i><span>Transparência</span><p>Números para confiança.</p></div>
                <div class="gallery-card" onclick="addBlock('campaign_progress')"><i class="fas fa-chart-line"></i><span>Meta da Campanha</span><p>Barra de progresso/objetivo.</p></div>
                <div class="gallery-card" onclick="addBlock('team_cards')"><i class="fas fa-people-group"></i><span>Equipe</span><p>Cards do time com redes.</p></div>
                <div class="gallery-card" onclick="addBlock('link_bio')"><i class="fas fa-link"></i><span>Bio Instagram</span><p>Layout estilo Linktree.</p></div>
                <div class="gallery-card" onclick="addBlock('products')"><i class="fas fa-shopping-bag"></i><span>Produtos</span><p>Venda produtos ou serviços.</p></div>
                <div class="gallery-card" onclick="addBlock('video')"><i class="fas fa-play-circle"></i><span>Vídeo</span><p>Embed do YouTube ou Vimeo.</p></div>
                <div class="gallery-card" onclick="addBlock('lead_capture')"><i class="fas fa-user-plus"></i><span>Formulário de Inscrição</span><p>Nome + e-mail e mais campos opcionais (CPF, endereço, responsável).</p></div>
                <div class="gallery-card" onclick="addBlock('stats')"><i class="fas fa-chart-bar"></i><span>Números</span><p>Exiba seu impacto social.</p></div>
                <div class="gallery-card" onclick="addBlock('testimonials')"><i class="fas fa-quote-right"></i><span>Depoimentos</span><p>Mostre o que dizem de você.</p></div>
                <div class="gallery-card" onclick="addBlock('social_links')"><i class="fas fa-share-alt"></i><span>Redes Sociais</span><p>Links para seus perfis.</p></div>
                <div class="gallery-card" onclick="addBlock('footer_links')"><i class="fas fa-shoe-prints"></i><span>Rodapé</span><p>Finalização da página.</p></div>
                <div class="gallery-card" onclick="addBlock('event_card')"><i class="fas fa-calendar-star"></i><span>Evento</span><p>Data, local, horário e inscrição.</p></div>
                <div class="gallery-card" onclick="addBlock('countdown')"><i class="fas fa-hourglass-half"></i><span>Contagem Regressiva</span><p>Urgência para campanhas com prazo.</p></div>
                <div class="gallery-card" onclick="addBlock('two_columns')"><i class="fas fa-table-columns"></i><span>Duas Colunas</span><p>Imagem + texto lado a lado.</p></div>
                <div class="gallery-card" onclick="addBlock('membership')"><i class="fas fa-id-badge"></i><span>Planos de Apoio</span><p>Tiers de associação e doação.</p></div>
                <div class="gallery-card" onclick="addBlock('rich_text')"><i class="fas fa-align-left"></i><span>Texto Livre</span><p>Manifesto, carta aberta, editorial.</p></div>
                <div class="gallery-card" onclick="addBlock('awards')"><i class="fas fa-trophy"></i><span>Prêmios & Selos</span><p>Reconhecimentos e certificações.</p></div>
                <div class="gallery-card" onclick="addBlock('cta_whatsapp')"><i class="fab fa-whatsapp"></i><span>WhatsApp CTA</span><p>Seção dedicada de contato via WA.</p></div>
                <div class="gallery-card" onclick="addBlock('pricing')"><i class="fas fa-tags"></i><span>Preços / Planos</span><p>Tabela de planos com features.</p></div>
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

                <hr style="margin: 22px 0; border:none; border-top: 1px solid #f1f5f9;">

                {{-- ─── Opção C — Destino do Cadastro (vínculo com Projeto) ─── --}}
                <div style="display:grid; grid-template-columns: 1fr; gap: 12px;">
                    <div>
                        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#4f46e5; margin-bottom: 8px;">
                            <i class="fas fa-bullseye"></i> Destino do Cadastro
                        </div>
                        <div style="color:#64748b; font-size:.85rem; margin-bottom: 12px;">
                            Vincule esta página a um <strong>Projeto</strong> pra que cada inscrição pública crie um cadastro dentro dele. Ideal pra matrículas, inscrições em oficinas ou coleta de beneficiários.
                        </div>
                        <select id="st_target_project" style="width:100%; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff;">
                            <option value="">— Sem vínculo (apenas lead no CRM) —</option>
                            @foreach($projects ?? [] as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="target-flags" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                        <label style="display:flex; gap: 10px; align-items:flex-start; margin-bottom: 10px; cursor:pointer;">
                            <input type="checkbox" id="st_target_creates_person" checked style="margin-top: 4px;">
                            <span style="font-size:.9rem; color:#0f172a;">
                                <strong>Criar pessoa dentro do projeto</strong>
                                <div style="color:#64748b; font-size:.82rem; margin-top: 2px;">Cada envio do formulário público vira uma matrícula (ProjectPerson).</div>
                            </span>
                        </label>

                        <label style="display:flex; gap: 10px; align-items:flex-start; cursor:pointer;">
                            <input type="checkbox" id="st_target_link_beneficiary" style="margin-top: 4px;">
                            <span style="font-size:.9rem; color:#0f172a;">
                                <strong>Também cadastrar/vincular como Beneficiário</strong>
                                <div style="color:#64748b; font-size:.82rem; margin-top: 2px;">Precisa que o CPF esteja marcado como campo do formulário. CPF já existente é reutilizado (não duplica).</div>
                            </span>
                        </label>
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

                {{-- ─── Domínio Próprio (add-on) ─────────────────────────────── --}}
                @php
                    $addonActive = (bool) (auth()->user()->tenant?->custom_domain_addon_active ?? false);
                @endphp
                <div>
                    <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#4f46e5; margin-bottom: 8px;">
                        <i class="fas fa-globe"></i> Domínio Próprio (add-on)
                    </div>

                    @if(!$addonActive)
                        <div style="background:#fef3c7; border:1px solid #fde68a; border-radius: 12px; padding: 14px 16px; color:#78350f; font-size:.9rem; line-height:1.55;">
                            <strong>Add-on não contratado.</strong> Use seu próprio domínio (ex: <code>www.suaong.org.br</code>) em vez de <code>{{ parse_url(config('app.url'), PHP_URL_HOST) }}/lp/{{ $page->slug }}</code>.
                            Fala com nosso comercial pra ativar em sua conta.
                        </div>
                    @else
                        <div id="cd-panel" data-status="{{ $page->custom_domain_status ?? '' }}" data-domain="{{ $page->custom_domain ?? '' }}">

                            {{-- Estado: nenhum dominio configurado --}}
                            <div id="cd-form" style="display: {{ $page->custom_domain ? 'none' : 'block' }};">
                                <div style="color:#64748b; font-size:.85rem; margin-bottom: 10px;">
                                    Digite o domínio (raiz ou <code>www.</code>) e siga as instruções de DNS.
                                </div>
                                <div style="display:flex; gap: 10px;">
                                    <input id="cd_input" type="text" placeholder="www.suaong.org.br"
                                           style="flex:1; padding: 12px 14px; border-radius: 12px; border:1px solid #e2e8f0;">
                                    <button type="button" onclick="cdSaveDomain()"
                                            style="background:#4f46e5; color:#fff; border:none; padding: 10px 16px; border-radius: 12px; font-weight:900; cursor:pointer;">
                                        Salvar domínio
                                    </button>
                                </div>
                            </div>

                            {{-- Estado: dominio configurado --}}
                            <div id="cd-info" style="display: {{ $page->custom_domain ? 'block' : 'none' }};">
                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap:wrap;">
                                        <div>
                                            <div style="font-weight:900; color:#0f172a; font-size: 1.05rem;" id="cd-domain-display">{{ $page->custom_domain }}</div>
                                            <div id="cd-status-line" style="margin-top:4px; font-size:.85rem;">
                                                {{-- Preenchido via JS --}}
                                            </div>
                                        </div>
                                        <div style="display:flex; gap: 8px;">
                                            <button type="button" id="cd-btn-provision" onclick="cdProvision()"
                                                    style="background:#10b981; color:#fff; border:none; padding: 10px 16px; border-radius: 10px; font-weight:900; cursor:pointer;">
                                                Verificar e ativar
                                            </button>
                                            <button type="button" onclick="cdRemove()"
                                                    style="background:#fff; color:#dc2626; border:1px solid #fecaca; padding: 10px 16px; border-radius: 10px; font-weight:900; cursor:pointer;">
                                                Remover
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Passo-a-passo com card visual (substitui o alert feio) --}}
                                    <div style="margin-top: 18px; padding: 18px; background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%); border-radius: 14px; border:1px solid #c7d2fe;">
                                        <div style="font-weight:900; color:#3730a3; font-size:1rem; margin-bottom: 14px; display:flex; align-items:center; gap: 8px;">
                                            <span style="background:#4f46e5; color:#fff; width: 28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.85rem;">1</span>
                                            Configure o DNS no seu provedor
                                        </div>

                                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 14px;">
                                            <div style="font-size:.78rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; margin-bottom: 10px;">
                                                Registro DNS a criar:
                                            </div>
                                            <div style="display:grid; grid-template-columns: 100px 1fr auto; gap: 8px 12px; align-items:center; font-size:.92rem;">
                                                <div style="color:#64748b; font-weight:700;">Tipo</div>
                                                <div style="font-family:'Courier New', monospace; font-weight:900; color:#0f172a;">A</div>
                                                <div></div>

                                                <div style="color:#64748b; font-weight:700;">Nome / Host</div>
                                                <div style="font-family:'Courier New', monospace; font-weight:900; color:#0f172a; word-break:break-all;" id="cd-dns-name">(seu domínio)</div>
                                                <button type="button" onclick="cdCopy(document.getElementById('cd-dns-name').textContent)" title="Copiar" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding: 4px 10px; border-radius: 6px; font-size:.75rem; cursor:pointer; font-weight:700;">📋 Copiar</button>

                                                <div style="color:#64748b; font-weight:700;">Valor / IP</div>
                                                <div style="font-family:'Courier New', monospace; font-weight:900; color:#0f172a;">{{ \App\Services\LandingDomainProvisioner::VPS_IP }}</div>
                                                <button type="button" onclick="cdCopy('{{ \App\Services\LandingDomainProvisioner::VPS_IP }}')" title="Copiar" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding: 4px 10px; border-radius: 6px; font-size:.75rem; cursor:pointer; font-weight:700;">📋 Copiar</button>

                                                <div style="color:#64748b; font-weight:700;">TTL</div>
                                                <div style="font-family:'Courier New', monospace; font-weight:900; color:#0f172a;">3600 <span style="color:#94a3b8; font-weight:600;">(ou o padrão do seu provedor)</span></div>
                                                <div></div>
                                            </div>
                                        </div>

                                        <div style="font-weight:800; color:#334155; font-size:.9rem; margin-bottom: 8px;">
                                            Como fazer no seu provedor:
                                        </div>
                                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px; margin-bottom: 12px;">
                                            <a href="https://registro.br/painel/" target="_blank" rel="noopener" style="background:#fff; border:1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; text-decoration:none; color:#0f172a; font-size:.85rem; font-weight:700; display:flex; align-items:center; gap: 6px;">
                                                🇧🇷 Registro.br <span style="color:#64748b; font-size:.8rem;">→</span>
                                            </a>
                                            <a href="https://dcc.godaddy.com/manage/dns" target="_blank" rel="noopener" style="background:#fff; border:1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; text-decoration:none; color:#0f172a; font-size:.85rem; font-weight:700; display:flex; align-items:center; gap: 6px;">
                                                GoDaddy <span style="color:#64748b; font-size:.8rem;">→</span>
                                            </a>
                                            <a href="https://cp.hostgator.com.br/" target="_blank" rel="noopener" style="background:#fff; border:1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; text-decoration:none; color:#0f172a; font-size:.85rem; font-weight:700; display:flex; align-items:center; gap: 6px;">
                                                HostGator <span style="color:#64748b; font-size:.8rem;">→</span>
                                            </a>
                                            <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener" style="background:#fff; border:1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; text-decoration:none; color:#0f172a; font-size:.85rem; font-weight:700; display:flex; align-items:center; gap: 6px;">
                                                Cloudflare <span style="color:#64748b; font-size:.8rem;">→</span>
                                            </a>
                                        </div>

                                        <div style="background:#fef3c7; border:1px solid #fde68a; border-radius: 10px; padding: 10px 12px; color:#78350f; font-size:.85rem; line-height:1.5;">
                                            <strong>⏱ Propagação:</strong> após criar o registro, o DNS leva de <strong>5 a 30 minutos</strong> pra propagar (raramente até 24h). Depois clique em <strong>"Verificar e ativar"</strong> no topo desta caixa.
                                        </div>

                                        <div id="cd-verify-instr" style="display:none; margin-top:12px; padding: 10px 12px; background:#dbeafe; border:1px solid #93c5fd; border-radius: 10px; color:#1e3a8a; font-size:.85rem;">
                                            <strong>💡 Como testar se o DNS já propagou:</strong> abra <a href="https://dnschecker.org/" target="_blank" rel="noopener" style="color:#1e40af;">dnschecker.org</a> e coloque seu domínio + tipo A. Se aparecer <strong>{{ \App\Services\LandingDomainProvisioner::VPS_IP }}</strong> na maioria dos servidores, pode clicar "Verificar e ativar".
                                        </div>
                                        <button type="button" onclick="document.getElementById('cd-verify-instr').style.display = document.getElementById('cd-verify-instr').style.display === 'none' ? 'block' : 'none'"
                                                style="background:transparent; border:none; color:#4f46e5; font-size:.82rem; font-weight:700; cursor:pointer; margin-top: 8px; padding: 0; text-decoration:underline;">
                                            Como testar se o DNS já propagou?
                                        </button>
                                    </div>

                                    {{-- Toast de copiado --}}
                                    <div id="cd-toast" style="display:none; position: fixed; bottom: 30px; right: 30px; background:#10b981; color:#fff; padding: 12px 20px; border-radius: 10px; font-weight:800; box-shadow: 0 10px 30px rgba(16,185,129,.3); z-index: 100000;">
                                        ✓ Copiado!
                                    </div>

                                    {{-- Erro (se houver) --}}
                                    <div id="cd-error" style="display:none; margin-top:14px; padding:12px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; color:#991b1b; font-size:.9rem; line-height:1.5;"></div>
                                </div>
                            </div>
                        </div>
                    @endif
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
            target_project_id:       @json($page->target_project_id),
            target_creates_person:   @json((bool) $page->target_creates_person),
            target_link_beneficiary: @json((bool) $page->target_link_beneficiary),
        };

        const __lpBaseUrl = @json(rtrim(request()->getBaseUrl(), '/'));
        function __lpUrl(path) { return (__lpBaseUrl || '') + path; }
        function __lpAbs(path) { return window.location.origin + (__lpBaseUrl || '') + path; }

        // ── Custom Domain (add-on) ────────────────────────────────────────────
        // Blade interpola o token direto — mesmo padrao das outras fetches do
        // builder (saveSettings/uploadOgImage/etc). Meta tag foi caindo em
        // CSRF mismatch em alguns clients; interpolacao direta e o padrao
        // que funciona no resto do arquivo.
        const __cdCsrf = '{{ csrf_token() }}';
        let __cdPollTimer = null;

        function cdStatusLabel(status) {
            const map = {
                'pending':   {t: 'Aguardando ativação', bg: '#fef3c7', fg: '#78350f'},
                'verifying': {t: 'Verificando/provisionando...', bg: '#dbeafe', fg: '#1e3a8a'},
                'active':    {t: 'Ativo ✓ (SSL válido)',  bg: '#dcfce7', fg: '#166534'},
                'failed':    {t: 'Falhou — corrija DNS e tente de novo', bg: '#fee2e2', fg: '#991b1b'},
                '':          {t: 'Não configurado', bg: '#f1f5f9', fg: '#64748b'},
            };
            return map[status] || map[''];
        }

        function cdRefreshUI(page) {
            const panel = document.getElementById('cd-panel');
            if (!panel) return;
            const status = page.custom_domain_status || '';
            const domain = page.custom_domain || '';
            panel.dataset.status = status;
            panel.dataset.domain = domain;

            document.getElementById('cd-form').style.display   = domain ? 'none' : 'block';
            document.getElementById('cd-info').style.display   = domain ? 'block' : 'none';

            if (domain) {
                document.getElementById('cd-domain-display').textContent = domain;
                document.getElementById('cd-dns-name').textContent = domain;

                const l = cdStatusLabel(status);
                const line = document.getElementById('cd-status-line');
                line.innerHTML = `<span style="display:inline-block; padding:3px 10px; border-radius:20px; background:${l.bg}; color:${l.fg}; font-weight:800; font-size:.8rem;">${l.t}</span>`;

                const btn = document.getElementById('cd-btn-provision');
                if (status === 'verifying') {
                    btn.disabled = true;
                    btn.textContent = 'Provisionando...';
                    btn.style.opacity = '0.6';
                } else if (status === 'active') {
                    btn.textContent = 'Renovar cert';
                    btn.disabled = false;
                    btn.style.opacity = '1';
                } else {
                    btn.textContent = 'Verificar e ativar';
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }

                const errBox = document.getElementById('cd-error');
                if (page.custom_domain_error) {
                    errBox.style.display = 'block';
                    errBox.textContent = page.custom_domain_error;
                } else {
                    errBox.style.display = 'none';
                }
            }
        }

        // Helper: extrai a msg real de erro do response Laravel (validation, custom, generic)
        function cdErrMsg(response, json) {
            if (json?.error)   return json.error;                                    // custom da API
            if (json?.errors)  return Object.values(json.errors).flat().join('\n'); // validation 422
            if (json?.message) return json.message;                                  // exception Laravel
            return `Falhou (HTTP ${response.status}). Se persistir, verifique se o dominio esta no formato correto (ex: www.suaong.org.br) e recarregue a pagina.`;
        }

        async function cdSaveDomain() {
            const val = (document.getElementById('cd_input').value || '').trim().toLowerCase();
            if (!val) { alert('Digite o dominio (ex: www.suaong.org.br)'); return; }
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/custom-domain'), {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':__cdCsrf,'Accept':'application/json'},
                    body: JSON.stringify({ custom_domain: val }),
                });
                let j = {}; try { j = await res.json(); } catch (_) {}
                if (!res.ok || !j.success) { alert(cdErrMsg(res, j)); return; }
                cdRefreshUI(j.page);
                // Nao mostra alert do dns_hint — o card visual ja explica tudo.
                // Scroll suave pro card DNS pra chamar atencao.
                setTimeout(() => {
                    document.getElementById('cd-info')?.scrollIntoView({behavior: 'smooth', block: 'start'});
                }, 200);
            } catch (e) { alert('Erro de rede: ' + e.message); }
        }

        // Copia texto pro clipboard + toast visual (nada de alert)
        async function cdCopy(text) {
            try {
                await navigator.clipboard.writeText(text);
            } catch (_) {
                // Fallback pra browsers antigos
                const ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta);
                ta.select(); document.execCommand('copy'); ta.remove();
            }
            const toast = document.getElementById('cd-toast');
            if (toast) {
                toast.style.display = 'block';
                clearTimeout(window.__cdToastTimer);
                window.__cdToastTimer = setTimeout(() => { toast.style.display = 'none'; }, 1600);
            }
        }

        async function cdProvision() {
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/custom-domain/provision'), {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN':__cdCsrf,'Accept':'application/json'},
                });
                let j = {}; try { j = await res.json(); } catch (_) {}
                if (!res.ok || !j.success) { alert(cdErrMsg(res, j)); return; }
                cdRefreshUI(j.page);
                cdStartPolling();
            } catch (e) { alert('Erro de rede: ' + e.message); }
        }

        async function cdRemove() {
            if (!confirm('Remover dominio proprio? A landing volta a responder so pela URL do Vivensi.')) return;
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/custom-domain'), {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN':__cdCsrf,'Accept':'application/json'},
                });
                let j = {}; try { j = await res.json(); } catch (_) {}
                if (!res.ok || !j.success) { alert(cdErrMsg(res, j)); return; }
                cdRefreshUI({custom_domain: null, custom_domain_status: null, custom_domain_error: null});
                cdStopPolling();
            } catch (e) { alert('Erro de rede: ' + e.message); }
        }

        async function cdPollStatus() {
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/custom-domain/status'), {
                    headers: {'Accept':'application/json'},
                });
                const j = await res.json();
                if (j.success) {
                    cdRefreshUI(j.page);
                    if (j.page.custom_domain_status !== 'verifying') cdStopPolling();
                }
            } catch (e) { /* silent */ }
        }

        function cdStartPolling() {
            cdStopPolling();
            __cdPollTimer = setInterval(cdPollStatus, 4000);
        }
        function cdStopPolling() {
            if (__cdPollTimer) { clearInterval(__cdPollTimer); __cdPollTimer = null; }
        }

        // Init: preenche UI com dados do server-side (via data-* no panel)
        document.addEventListener('DOMContentLoaded', function() {
            const p = document.getElementById('cd-panel');
            if (!p) return;
            const status = p.dataset.status || '';
            const domain = p.dataset.domain || '';
            cdRefreshUI({
                custom_domain: domain,
                custom_domain_status: status,
                custom_domain_error: @json($page->custom_domain_error ?? null),
            });
            if (status === 'verifying') cdStartPolling();
        });

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

            // Opção C — destino do cadastro
            const sel = document.getElementById('st_target_project');
            if (sel) sel.value = (__lpPage.target_project_id ? String(__lpPage.target_project_id) : '');
            const chkP = document.getElementById('st_target_creates_person');
            if (chkP) chkP.checked = !!__lpPage.target_creates_person;
            const chkB = document.getElementById('st_target_link_beneficiary');
            if (chkB) chkB.checked = !!__lpPage.target_link_beneficiary;
            __lpToggleTargetFlags();

            updateSeoPreview();
            modal.style.display = 'flex';
        }

        function __lpToggleTargetFlags() {
            const sel = document.getElementById('st_target_project');
            const box = document.getElementById('target-flags');
            if (!sel || !box) return;
            box.style.display = sel.value ? 'block' : 'none';
            // Vincular beneficiário só faz sentido se também criar pessoa.
            const chkP = document.getElementById('st_target_creates_person');
            const chkB = document.getElementById('st_target_link_beneficiary');
            if (chkP && chkB) {
                chkB.disabled = !chkP.checked;
                if (!chkP.checked) chkB.checked = false;
            }
        }
        document.addEventListener('change', function (ev) {
            const t = ev && ev.target ? ev.target.id : '';
            if (t === 'st_target_project' || t === 'st_target_creates_person') {
                __lpToggleTargetFlags();
            }
        });

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
                    imgWrap.innerHTML = '<img loading="lazy" src="' + og.replace(/"/g, '') + '" alt="OG Image" style="width:100%; height:100%; object-fit: cover; display:block;">';
                } else {
                    imgWrap.innerHTML = '<span style="font-weight:900; color:#94a3b8;">OG Image</span>';
                }
            }
        }

        async function saveSettings() {
            const btn = document.getElementById('btn-save-settings');
            if (btn) btn.disabled = true;

            const _tp = document.getElementById('st_target_project')?.value || '';
            const payload = {
                title: document.getElementById('st_title').value || null,
                theme_color: document.getElementById('st_theme_color_hex').value || null,
                seo_title: document.getElementById('st_seo_title').value || null,
                seo_description: document.getElementById('st_seo_desc').value || null,
                og_image_url: document.getElementById('st_og_image').value || '',
                favicon_url: document.getElementById('st_favicon').value || '',
                target_project_id: _tp ? parseInt(_tp, 10) : null,
                target_creates_person: !!document.getElementById('st_target_creates_person')?.checked,
                target_link_beneficiary: !!document.getElementById('st_target_link_beneficiary')?.checked,
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
                    __lpPage.target_project_id       = j.page.target_project_id ?? null;
                    __lpPage.target_creates_person   = !!j.page.target_creates_person;
                    __lpPage.target_link_beneficiary = !!j.page.target_link_beneficiary;
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

        // ── Upload de background do hero_image (P3 melhorias builder) ───────
        async function hiUploadBg(sectionId) {
            const input = document.getElementById('hi-bg-file');
            const btn   = document.getElementById('hi-bg-upload-btn');
            const file  = input && input.files ? input.files[0] : null;
            if (!file) return alert('Selecione uma imagem primeiro.');
            if (btn) btn.disabled = true;

            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/sections/' + sectionId + '/asset/background'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: fd,
                });
                let j = {}; try { j = await res.json(); } catch (_) {}
                if (!res.ok || !j.success) {
                    alert((j && j.errors && j.errors.file) ? j.errors.file[0] : (j.error || 'Falha no upload'));
                    if (btn) btn.disabled = false;
                    return;
                }
                const wrap = document.getElementById('hi-bg-preview-wrap');
                const img  = document.getElementById('hi-bg-preview');
                if (img) img.src = j.url + '?t=' + Date.now();
                if (wrap) wrap.style.display = 'block';
                const hidden = document.querySelector('input[name="background_url"]');
                if (hidden) hidden.value = j.url;
                if (btn) btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                setTimeout(() => { btn.innerHTML = '<i class="fas fa-upload"></i> Enviar'; }, 1500);
            } catch (e) {
                alert('Erro de rede: ' + e.message);
                if (btn) btn.disabled = false;
            }
        }

        function hiClearBg() {
            if (!confirm('Remover imagem de fundo? O hero volta a mostrar so a cor overlay.')) return;
            const hidden = document.querySelector('input[name="background_url"]');
            const wrap   = document.getElementById('hi-bg-preview-wrap');
            if (hidden) hidden.value = '';
            if (wrap)   wrap.style.display = 'none';
            alert('Imagem marcada pra remover. Clique em Salvar Bloco pra confirmar.');
        }

        // ── Upload de logo do header_nav (P2 melhorias builder) ──────────────
        async function hnUploadLogo(sectionId) {
            const input = document.getElementById('hn-logo-file');
            const btn = document.getElementById('hn-logo-upload-btn');
            const file = input && input.files ? input.files[0] : null;
            if (!file) return alert('Selecione uma imagem primeiro.');
            if (btn) btn.disabled = true;

            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/sections/' + sectionId + '/logo'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: fd,
                });
                let j = {}; try { j = await res.json(); } catch (_) {}
                if (!res.ok || !j.success) {
                    alert((j && j.errors && j.errors.file) ? j.errors.file[0] : (j.error || 'Falha no upload'));
                    if (btn) btn.disabled = false;
                    return;
                }
                // Atualiza preview + hidden field
                const wrap = document.getElementById('hn-logo-preview-wrap');
                const img  = document.getElementById('hn-logo-preview');
                if (img) img.src = j.url + '?t=' + Date.now(); // cache-bust
                if (wrap) wrap.style.display = 'block';
                const hidden = document.querySelector('input[name="logo_url"]');
                if (hidden) hidden.value = j.url;
                if (btn) btn.disabled = false;
                // Feedback rapido
                btn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                setTimeout(() => { btn.innerHTML = '<i class="fas fa-upload"></i> Enviar'; }, 1500);
            } catch (e) {
                alert('Erro de rede: ' + e.message);
                if (btn) btn.disabled = false;
            }
        }

        function hnClearLogo() {
            if (!confirm('Remover o logo? A landing volta a mostrar o titulo em texto.')) return;
            const hidden = document.querySelector('input[name="logo_url"]');
            const wrap   = document.getElementById('hn-logo-preview-wrap');
            if (hidden) hidden.value = '';
            if (wrap)   wrap.style.display = 'none';
            // Precisa salvar section pra propagar; user clica Salvar depois
            alert('Logo marcado pra remover. Clique em Salvar Bloco pra confirmar.');
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

            // Blocos com formulário — renderiza checkboxes p/ os campos
            // opcionais (CPF, endereço, responsável…) antes do editor genérico
            // e remove essas chaves de `content` p/ não duplicar como input
            // de texto embaixo. Cobre BOTH final_cta_form E lead_capture.
            if (type === 'final_cta_form' || type === 'lead_capture') {
                // lead_capture já tem name e phone opcional; final_cta_form também.
                // Toggle "require_name" só faz sentido no final_cta_form (no
                // lead_capture o nome já é required por default).
                const isFinal = type === 'final_cta_form';
                const box = document.createElement('div');
                box.style.cssText = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px;';
                box.innerHTML = `
                    <div style="font-size:.72rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#4f46e5; margin-bottom:10px;">
                        <i class="fas fa-list-check"></i> Campos do formulário
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 8px 14px;">
                        ${isFinal ? `<label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="require_name" value="1" ${content.require_name ? 'checked' : ''}> Nome obrigatório</label>` : ''}
                        ${!isFinal ? `<label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="enable_phone" value="1" ${content.enable_phone ? 'checked' : ''}> Mostrar WhatsApp</label>` : ''}
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="require_phone" value="1" ${content.require_phone ? 'checked' : ''}> WhatsApp obrigatório</label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="enable_cpf" value="1" ${content.enable_cpf ? 'checked' : ''}> Pedir CPF</label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="enable_birth_date" value="1" ${content.enable_birth_date ? 'checked' : ''}> Data de nascimento</label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="enable_address" value="1" ${content.enable_address ? 'checked' : ''}> Endereço</label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer;"><input type="checkbox" name="enable_city" value="1" ${content.enable_city ? 'checked' : ''}> Cidade</label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:.88rem; color:#0f172a; cursor:pointer; grid-column: 1 / -1;"><input type="checkbox" name="enable_guardian" value="1" ${content.enable_guardian ? 'checked' : ''}> Responsável (nome + telefone)</label>
                    </div>
                    <div style="margin-top:10px; color:#64748b; font-size:.78rem;">
                        Se a landing está vinculada a um <strong>Projeto</strong> (em Configurações), estes campos alimentam o cadastro dentro do projeto. Pra vincular como Beneficiário, o CPF é essencial.
                    </div>
                `;
                container.appendChild(box);

                // ── Custom fields (2026-08-06) — repeater pra campos personalizados
                //    além dos pré-prontos. Salvos em content.custom_fields[] e vão
                //    pra landing_page_leads.extra_data.custom.<key> na submissao.
                const cfBox = document.createElement('div');
                cfBox.style.cssText = 'background:#fefce8; border:1px solid #fde68a; border-radius:12px; padding:14px 16px; margin-bottom:16px;';
                cfBox.innerHTML = `
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <div style="font-size:.72rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#b45309;">
                            <i class="fas fa-sliders"></i> Campos personalizados
                        </div>
                        <button type="button" onclick="cfAddRow(this)" style="background:#f59e0b; color:#fff; border:none; border-radius:8px; padding:6px 12px; font-size:.75rem; font-weight:700; cursor:pointer;">
                            <i class="fas fa-plus"></i> Adicionar campo
                        </button>
                    </div>
                    <div class="cf-list" data-cf-list></div>
                    <div style="margin-top:8px; color:#78350f; font-size:.72rem;">
                        Tipos aceitos: texto, email, telefone, número, data, área de texto, seleção (options separadas por vírgula). As respostas vão pro CRM da landing em <code>extra_data.custom</code>.
                    </div>
                `;
                container.appendChild(cfBox);

                const cfList = cfBox.querySelector('[data-cf-list]');
                const existing = Array.isArray(content.custom_fields) ? content.custom_fields : [];
                if (existing.length === 0) {
                    cfEmpty(cfList);
                } else {
                    existing.forEach((f, i) => cfRenderRow(cfList, i, f));
                }

                // Evita duplicar toggles + custom_fields no editor genérico abaixo.
                content = Object.assign({}, content);
                ['require_name','require_phone','enable_phone','enable_cpf','enable_birth_date','enable_address','enable_city','enable_guardian','custom_fields'].forEach(k => { delete content[k]; });
            }

            // ── Hero com Imagem — upload de background (P3 melhorias builder)
            if (type === 'hero_image') {
                const bgBox = document.createElement('div');
                bgBox.style.cssText = 'background:#f0fdf4; border:1px solid #86efac; border-radius:12px; padding:14px 16px; margin-bottom:16px;';
                bgBox.innerHTML = `
                    <div style="font-size:.72rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#15803d; margin-bottom:10px;">
                        <i class="fas fa-image"></i> Imagem de fundo
                    </div>
                    <div id="hi-bg-preview-wrap" style="margin-bottom:12px; ${content.background_url ? '' : 'display:none;'}">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px; display:inline-block; max-width:100%;">
                            <img id="hi-bg-preview" src="${content.background_url || ''}" alt="Fundo atual" style="max-height:140px; max-width:400px; display:block; border-radius:6px;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <input type="file" id="hi-bg-file" accept="image/*" style="flex:1; min-width:200px;">
                        <button type="button" id="hi-bg-upload-btn" onclick="hiUploadBg(${id})"
                                style="background:#10b981; color:#fff; border:none; padding:10px 16px; border-radius:10px; font-weight:900; cursor:pointer;">
                            <i class="fas fa-upload"></i> Enviar
                        </button>
                        ${content.background_url ? `<button type="button" onclick="hiClearBg()"
                                style="background:#fff; color:#dc2626; border:1px solid #fecaca; padding:10px 14px; border-radius:10px; font-weight:800; cursor:pointer;">
                            Remover
                        </button>` : ''}
                    </div>
                    <div style="margin-top:8px; color:#64748b; font-size:.78rem;">
                        JPG/PNG/WebP, max 4MB. Recomendado 1920x1080. Use overlay (abaixo) pra escurecer e melhorar leitura do texto.
                    </div>
                    <input type="hidden" name="background_url" value="${content.background_url || ''}">
                </div>`;
                container.appendChild(bgBox);

                content = Object.assign({}, content);
                delete content.background_url;
                delete content.background_storage_path;
            }

            // ── Header/Menu — upload de logo dedicado (P2 melhorias builder)
            if (type === 'header_nav') {
                const logoBox = document.createElement('div');
                logoBox.style.cssText = 'background:#eef2ff; border:1px solid #c7d2fe; border-radius:12px; padding:14px 16px; margin-bottom:16px;';
                logoBox.innerHTML = `
                    <div style="font-size:.72rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#4f46e5; margin-bottom:10px;">
                        <i class="fas fa-image"></i> Logo do menu
                    </div>
                    <div id="hn-logo-preview-wrap" style="margin-bottom:12px; ${content.logo_url ? '' : 'display:none;'}">
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:10px; display:inline-block;">
                            <img id="hn-logo-preview" src="${content.logo_url || ''}" alt="Logo atual" style="max-height:60px; max-width:220px; display:block;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <input type="file" id="hn-logo-file" accept="image/*" style="flex:1; min-width:200px;">
                        <button type="button" id="hn-logo-upload-btn" onclick="hnUploadLogo(${id})"
                                style="background:#4f46e5; color:#fff; border:none; padding:10px 16px; border-radius:10px; font-weight:900; cursor:pointer;">
                            <i class="fas fa-upload"></i> Enviar
                        </button>
                        ${content.logo_url ? `<button type="button" onclick="hnClearLogo()"
                                style="background:#fff; color:#dc2626; border:1px solid #fecaca; padding:10px 14px; border-radius:10px; font-weight:800; cursor:pointer;">
                            Remover
                        </button>` : ''}
                    </div>
                    <div style="margin-top:8px; color:#64748b; font-size:.78rem;">
                        PNG/JPG/SVG, max 2MB. Recomendado 200x60px transparente. Se não enviar, mostra o título da landing no lugar.
                    </div>
                    <input type="hidden" name="logo_url" value="${content.logo_url || ''}">
                </div>`;
                container.appendChild(logoBox);

                // Remove logo_url do content pra não duplicar como input texto embaixo
                content = Object.assign({}, content);
                delete content.logo_url;
                delete content.logo_storage_path;
            }

            // Nos blocos de formulário, os campos genéricos (título, subtítulo,
            // botão, cor, etc) vão para um container irmão estilizado — assim
            // não ficam "soltos" abaixo do box de toggles.
            let dest = container;
            if (type === 'final_cta_form' || type === 'lead_capture') {
                const textBox = document.createElement('div');
                textBox.style.cssText = 'background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px;';
                const textHead = document.createElement('div');
                textHead.style.cssText = 'font-size:.72rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#0f172a; margin-bottom:10px;';
                textHead.innerHTML = '<i class="fas fa-pen"></i> Textos & visual';
                textBox.appendChild(textHead);
                container.appendChild(textBox);
                dest = textBox;
            }

            // Loop recursivo básico para lidar com objetos simples e arrays (Depoimentos/Itens)
            function createFields(data, prefix = '') {
                for(const [key, value] of Object.entries(data)) {
                    const fieldName = prefix ? `${prefix}[${key}]` : key;

                    if(Array.isArray(value)) {
                        const subTitle = document.createElement('h5');
                        subTitle.style.color = 'var(--primary)';
                        subTitle.style.margin = '20px 0 10px';
                        subTitle.innerText = key.toUpperCase();
                        dest.appendChild(subTitle);

                        value.forEach((item, index) => {
                            const hr = document.createElement('hr');
                            hr.style.opacity = '0.1';
                            dest.appendChild(hr);
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
                        dest.appendChild(group);
                    }
                }
            }

            createFields(content);
        }

        // ── Custom fields repeater (2026-08-06) ──────────────────────────────
        function cfEmpty(list) {
            list.innerHTML = '<div style="padding:12px 4px; color:#78350f; font-size:.78rem; opacity:.7;">Nenhum campo personalizado. Clique em "Adicionar campo" pra criar.</div>';
        }
        function cfNextIndex(list) {
            return list.querySelectorAll('[data-cf-row]').length;
        }
        function cfAddRow(btn) {
            const list = btn.closest('div').parentElement.querySelector('[data-cf-list]');
            if (list.querySelector('[data-cf-empty]') || !list.querySelector('[data-cf-row]')) {
                list.innerHTML = '';
            }
            cfRenderRow(list, cfNextIndex(list), { key: '', label: '', type: 'text', required: false, placeholder: '', options: '' });
        }
        function cfRenderRow(list, index, f) {
            const optsStr = Array.isArray(f.options) ? f.options.join(', ') : (f.options || '');
            const row = document.createElement('div');
            row.setAttribute('data-cf-row', '');
            row.style.cssText = 'background:#fff; border:1px solid #fde68a; border-radius:10px; padding:12px; margin-bottom:8px; display:grid; grid-template-columns: 2fr 2fr 1.2fr 1fr auto; gap:8px; align-items:end;';
            row.innerHTML = `
                <div>
                    <label style="font-size:.68rem; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em;">Rótulo *</label>
                    <input type="text" name="custom_fields[${index}][label]" value="${cfEsc(f.label)}" placeholder="Ex: Instituição" required style="width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:.85rem;">
                </div>
                <div>
                    <label style="font-size:.68rem; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em;">Chave (slug)</label>
                    <input type="text" name="custom_fields[${index}][key]" value="${cfEsc(f.key)}" placeholder="ex: instituicao" pattern="[a-z0-9_]{2,40}" title="Só letras minúsculas, números e _" style="width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:.85rem;">
                </div>
                <div>
                    <label style="font-size:.68rem; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em;">Tipo</label>
                    <select name="custom_fields[${index}][type]" onchange="cfToggleOptions(this)" style="width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:.85rem;">
                        <option value="text"     ${f.type==='text'?'selected':''}>Texto</option>
                        <option value="email"    ${f.type==='email'?'selected':''}>Email</option>
                        <option value="tel"      ${f.type==='tel'?'selected':''}>Telefone</option>
                        <option value="number"   ${f.type==='number'?'selected':''}>Número</option>
                        <option value="date"     ${f.type==='date'?'selected':''}>Data</option>
                        <option value="textarea" ${f.type==='textarea'?'selected':''}>Área de texto</option>
                        <option value="select"   ${f.type==='select'?'selected':''}>Seleção</option>
                    </select>
                </div>
                <div>
                    <label style="display:flex; align-items:center; gap:6px; font-size:.78rem; color:#0f172a; cursor:pointer; margin-top:18px;">
                        <input type="checkbox" name="custom_fields[${index}][required]" value="1" ${f.required?'checked':''}> Obrig.
                    </label>
                </div>
                <button type="button" onclick="cfRemoveRow(this)" title="Remover" style="background:#ef4444; color:#fff; border:none; border-radius:6px; width:32px; height:32px; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
                <div style="grid-column: 1 / 3;">
                    <label style="font-size:.68rem; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em;">Placeholder</label>
                    <input type="text" name="custom_fields[${index}][placeholder]" value="${cfEsc(f.placeholder)}" style="width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:.85rem;">
                </div>
                <div data-cf-opts style="grid-column: 3 / 6; display:${f.type==='select'?'block':'none'};">
                    <label style="font-size:.68rem; font-weight:700; color:#78350f; text-transform:uppercase; letter-spacing:.05em;">Opções (vírgula)</label>
                    <input type="text" name="custom_fields[${index}][options]" value="${cfEsc(optsStr)}" placeholder="Opção A, Opção B, Opção C" style="width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; font-size:.85rem;">
                </div>
            `;
            list.appendChild(row);
        }
        function cfRemoveRow(btn) {
            const row = btn.closest('[data-cf-row]');
            const list = row.parentElement;
            row.remove();
            if (list.querySelectorAll('[data-cf-row]').length === 0) cfEmpty(list);
        }
        function cfToggleOptions(sel) {
            const row = sel.closest('[data-cf-row]');
            const opts = row.querySelector('[data-cf-opts]');
            if (opts) opts.style.display = sel.value === 'select' ? 'block' : 'none';
        }
        function cfEsc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
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

    {{-- SortableJS pra drag-drop dos blocos --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        // Re-inicializa Sortable apos o script CDN carregar (fim do body)
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.getElementById('active-blocks');
            if (!list || !window.Sortable) return;
            if (list.__vivensiSortable) return; // ja inicializou

            list.__vivensiSortable = new Sortable(list, {
                handle: '.drag',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: async function () {
                    const ids = Array.from(list.querySelectorAll('.block-item'))
                        .map(el => parseInt(el.dataset.editorId, 10))
                        .filter(Boolean);
                    if (ids.length === 0) return;
                    try {
                        const res = await fetch(__lpUrl('/ngo/landing-pages/' + __lpPage.id + '/reorder'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order: ids }),
                        });
                        const j = await res.json();
                        if (!res.ok || !j.success) {
                            alert('Falha ao salvar nova ordem: ' + (j.error || res.status));
                        }
                    } catch (e) {
                        alert('Erro de rede ao salvar ordem: ' + e.message);
                    }
                },
            });

            list.querySelectorAll('.drag').forEach(el => {
                el.addEventListener('click', e => e.stopPropagation());
                el.addEventListener('mousedown', e => e.stopPropagation());
            });
        });
    </script>
</body>
</html>
