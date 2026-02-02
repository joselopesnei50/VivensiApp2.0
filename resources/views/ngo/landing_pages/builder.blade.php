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
                    <a href="{{ url('/ngo/landing-pages') }}" style="color: white;"><i class="fas fa-chevron-left"></i></a>
                    <h3 id="page-title">{{ $page->title }}</h3>
                </div>
                <i class="fas fa-sliders-h" style="color: #64748b; cursor: pointer;"></i>
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
                                        'contact' => 'Contato/Endereço'
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
                </div>
                <div style="display: flex; gap: 15px;">
                    <button onclick="window.open('{{ url('/lp/'.$page->slug) }}', '_blank')" style="background: white; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; color: #475569;">
                        <i class="fas fa-eye"></i> Visualizar Online
                    </button>
                    <button onclick="alert('Publicado com sucesso!')" style="background: var(--accent); color: white; border: none; padding: 10px 25px; border-radius: 12px; font-weight: 700; cursor: pointer;">
                        Publicar Agora
                    </button>
                </div>
            </div>
            <div class="canvas-body">
                <div class="iframe-container">
                    <iframe id="preview-iframe" src="{{ url('/lp/'.$page->slug) }}"></iframe>
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

    <script>
        let currentId = null;

        function openGallery() { document.getElementById('gallery-modal').style.display = 'flex'; }
        function closeGallery() { document.getElementById('gallery-modal').style.display = 'none'; }

        async function addBlock(type) {
            const response = await fetch('{{ url("/ngo/landing-pages/".$page->id."/section") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type })
            });
            if (response.ok) { window.location.reload(); }
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

            const response = await fetch(`{{ url("/ngo/landing-pages/section") }}/${currentId}`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: content })
            });

            if (response.ok) {
                closeEditor();
                document.getElementById('preview-iframe').src += '';
                alert('LEGO Atualizado!');
            }
        }

        async function deleteBlock(e, id) {
            e.stopPropagation();
            if(!confirm('Remover este bloco do seu LEGO?')) return;
            const response = await fetch(`{{ url("/ngo/landing-pages/section") }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if(response.ok) window.location.reload();
        }
    </script>
</body>
</html>
