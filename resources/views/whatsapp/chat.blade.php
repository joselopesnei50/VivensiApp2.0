<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi OmniChannel | WhatsApp CRM</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4F46E5; /* Indigo 600 */
            --primary-light: #EEF2FF;
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --danger-color: #EF4444;
            --text-heading: #1e293b;
            --text-body: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --sidebar-width: 380px;
            --intelligence-panel-width: 350px;
        }

        /* Reset & Layout Fullscreen */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden; /* No scroll on body, specific areas scroll */
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .crm-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* --- Left Sidebar (Inbox) --- */
        .crm-sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            z-index: 10;
            box-shadow: 1px 0 10px rgba(0,0,0,0.02);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            background: white;
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .back-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .back-link:hover { color: var(--primary-color); }

        .app-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .search-area {
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            color: var(--text-body);
            font-size: 0.9rem;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .filter-tabs {
            padding: 10px 20px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            border-bottom: 1px solid var(--border-color);
        }
        .filter-tab {
            background: transparent;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
            cursor: pointer;
            transition: 0.2s;
        }
        .filter-tab:hover { background: #f1f5f9; color: var(--text-body); }
        .filter-tab.active { background: var(--primary-light); color: var(--primary-color); }

        .contact-list {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .contact-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            gap: 12px;
            position: relative;
            border-left: 3px solid transparent;
        }
        .contact-item:hover { background: #f8fafc; }
        .contact-item.active { background: #f8fafc; border-left-color: var(--primary-color); }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            position: relative;
        }
        .online-dot {
            width: 10px;
            height: 10px;
            background: var(--success-color);
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: 0; right: 0;
        }

        .contact-info { flex: 1; min-width: 0; }
        .contact-top { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .contact-name { font-weight: 600; color: var(--text-heading); font-size: 0.95rem; }
        .contact-time { font-size: 0.75rem; color: var(--text-muted); }
        
        .contact-bottom { display: flex; justify-content: space-between; align-items: center; }
        .last-message { font-size: 0.85rem; color: var(--text-body); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 85%; }
        .badge-unread { background: var(--danger-color); color: white; font-size: 0.7rem; padding: 1px 6px; border-radius: 10px; font-weight: 700; }

        /* --- Center Chat Area --- */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #e5ddd5;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-blend-mode: overlay;
            position: relative;
        }
        /* White overlay to make it look cleaner/more SaaS */
        .chat-main::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.92);
            z-index: 0;
        }

        .chat-header {
            height: 70px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: relative;
            z-index: 2;
        }

        .chat-user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-avatar {
            width: 40px; height: 40px;
            background: var(--primary-color);
            color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600;
        }
        .header-info h4 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-heading); }
        .header-info span { font-size: 0.8rem; color: var(--text-muted); display: block; }

        .chat-actions button {
            background: transparent; border: none;
            color: var(--text-body);
            font-size: 1.1rem;
            padding: 8px;
            cursor: pointer;
            transition: 0.2s;
            border-radius: 8px;
        }
        .chat-actions button:hover { background: rgba(0,0,0,0.05); color: var(--primary-color); }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .message-row { display: flex; width: 100%; }
        .message-in { justify-content: flex-start; }
        .message-out { justify-content: flex-end; }

        .bubble {
            max-width: 65%;
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            position: relative;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .bubble.in { background: white; color: var(--text-heading); border-top-left-radius: 0; }
        .bubble.out { background: linear-gradient(135deg, #4F46E5 0%, #4338ca 100%); color: white; border-top-right-radius: 0; }

        .bubble .meta {
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            justify-content: flex-end;
            opacity: 0.8;
        }

        .input-area {
            background: white;
            padding: 20px;
            position: relative;
            z-index: 2;
        }
        .input-container {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: #f8fafc;
            padding: 5px 15px;
            display: flex;
            flex-direction: column;
            transition: 0.2s;
        }
        .input-container:focus-within {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .input-toolbar {
            display: flex;
            gap: 10px;
            padding: 5px 0 0;
            margin-bottom: 5px;
            border-bottom: 1px solid transparent; /* Placeholder */
        }
        .tool-btn {
            background: transparent; border: none;
            color: var(--text-muted);
            font-size: 1rem;
            cursor: pointer;
            padding: 5px;
            transition: 0.2s;
        }
        .tool-btn:hover { color: var(--primary-color); }

        .message-input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
            font-size: 0.95rem;
            min-height: 24px;
            max-height: 100px;
            resize: none;
            color: var(--text-heading);
        }

        .input-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
            padding-top: 5px;
        }

        .send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.2s;
        }
        .send-btn:hover { background: #4338ca; transform: translateY(-1px); }

        /* --- Right Intelligence Panel --- */
        .intelligence-panel {
            width: var(--intelligence-panel-width);
            background: white;
            border-left: 1px solid var(--border-color);
            z-index: 10;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .panel-hero {
            padding: 30px 20px;
            text-align: center;
            background: linear-gradient(to bottom, #f8fafc, white);
            border-bottom: 1px solid #f1f5f9;
        }
        .hero-avatar {
            width: 80px; height: 80px;
            background: #cbd5e1;
            border-radius: 20px;
            margin: 0 auto 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        .tag-badge {
            background: #f1f5f9; color: var(--text-body);
            padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
        }
        .tag-badge.hot { background: #fef2f2; color: var(--danger-color); }
        
        .crm-section { border-bottom: 1px solid #f1f5f9; }
        .crm-header {
            padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            font-weight: 600; font-size: 0.9rem; color: var(--text-heading);
            cursor: pointer; text-decoration: none;
        }
        .crm-header:hover { background: #f8fafc; }

        .crm-body { padding: 0 20px 20px; }
        
        .info-row { margin-bottom: 12px; }
        .label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
        .value { color: var(--text-heading); font-size: 0.9rem; font-weight: 500; }

        .timeline-item {
            position: relative;
            padding-left: 15px;
            margin-bottom: 15px;
            border-left: 2px solid #e2e8f0;
        }
        .timeline-item::before {
            content: ''; position: absolute; left: -5px; top: 5px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
        }
        .timeline-content { font-size: 0.85rem; color: var(--text-body); background: #f8fafc; padding: 10px; border-radius: 8px; }
        .timeline-date { font-size: 0.7rem; color: var(--text-muted); display: block; margin-top: 5px; text-align: right; }

        /* Empty State */
        .empty-state {
            height: 100%;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            background: #f8fafc;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="crm-layout">
        <!-- 1. LEFT SIDEBAR -->
        <div class="crm-sidebar">
            <div class="sidebar-header">
                <div class="header-top-row">
                    <h1 class="app-title"><i class="fab fa-whatsapp" style="color: #25D366;"></i> OmniChannel</h1>
                    <a href="{{ url('/dashboard') }}" class="back-link"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>
                <div class="search-area">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Buscar conversas...">
                </div>
            </div>
            
             <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Todas</button>
                <button class="filter-tab" data-filter="unread">Não Lidas (Mock)</button>
                <button class="filter-tab" data-filter="waiting">Aguardando</button>
            </div>

            <div class="contact-list" id="chatList">
                @foreach($chats as $chat)
                @php
                    // Logic to simulate unread/waiting for demonstration if real data isn't perfect yet
                    $isUnread = $loop->index == 1; 
                    $isWaiting = $chat->status === 'waiting' || $loop->index == 2;
                @endphp
                <div class="contact-item {{ $loop->first ? 'active' : '' }}" 
                     onclick="selectChat(this, {{ $chat->id }})" 
                     data-id="{{ $chat->id }}"
                     data-unread="{{ $isUnread ? 'true' : 'false' }}"
                     data-waiting="{{ $isWaiting ? 'true' : 'false' }}">
                    
                    <div class="avatar" style="background: {{ ['#4F46E5', '#10B981', '#F59E0B', '#EF4444'][$chat->id % 4] }};">
                        {{ substr($chat->contact_name, 0, 1) }}
                        @if($loop->index < 3) <div class="online-dot"></div> @endif
                    </div>
                    <div class="contact-info">
                        <div class="contact-top">
                            <span class="contact-name">{{ $chat->contact_name }}</span>
                            <span class="contact-time text-muted">{{ \Carbon\Carbon::parse($chat->last_message_at)->format('H:i') }}</span>
                        </div>
                        <div class="contact-bottom">
                            <span class="last-message">
                                @if($chat->messages()->latest()->first()?->direction == 'outbound')
                                    <i class="fas fa-check-double" style="color: var(--primary-color);"></i>
                                @endif
                                {{ $chat->messages()->latest()->first()->content ?? 'Iniciar conversa' }}
                            </span>
                            @if($isUnread) <span class="badge-unread">1</span> @endif
                        </div>
                    </div>
                </div>
                @endforeach
                
                @if(count($chats) == 0)
                    <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="far fa-comments fa-2x mb-2"></i><br>Nenhuma conversa.
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. MAIN CHAT AREA -->
        @if(count($chats) > 0)
        <div class="chat-main" id="chat-main-area">
            <!-- Header -->
            <div class="chat-header">
                <div class="chat-user-profile">
                    <div class="header-avatar" id="header-avatar">{{ substr($chats[0]->contact_name, 0, 1) }}</div>
                    <div class="header-info">
                        <h4 id="header-name">{{ $chats[0]->contact_name }}</h4>
                        <span id="header-status"><i class="fas fa-circle text-success" style="font-size: 8px;"></i> Online agora</span>
                    </div>
                </div>
                <div class="chat-actions">
                    <button title="Treinar IA / Configurações" onclick="location.href='{{ url('/whatsapp/settings') }}'"><i class="fas fa-cog"></i></button>
                    <button title="Simular Mensagem (Teste AI)" onclick="simulateMessage()" class="text-danger"><i class="fas fa-dog"></i></button>
                    <button title="Mais opções"><i class="fas fa-ellipsis-v"></i></button>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages-container" id="chat-messages-area">
                <!-- Loaded via JS -->
            </div>

            <!-- Warning Toast (Example) -->
            <div id="window-warning" style="display: none; background: #fffbeb; border: 1px solid #fbbf24; color: #92400e; padding: 10px 20px; border-radius: 8px; margin: 0 20px 10px; font-size: 0.9rem; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Janela de 24h fechada. Use um <strong>Template</strong>.
            </div>

            <!-- Input -->
            <div class="input-area">
                <div class="input-container">
                    <div class="input-toolbar">
                        <button class="tool-btn" title="Emoji"><i class="far fa-smile"></i></button>
                        <button class="tool-btn" title="Anexar"><i class="fas fa-paperclip"></i></button>
                        <div style="width: 1px; height: 15px; background: #ccc; margin: 5px;"></div>
                        <button class="tool-btn text-warning" title="Respostas Rápidas" onclick="openCannedModal()"><i class="fas fa-bolt"></i></button>
                        <button class="tool-btn text-primary" title="Melhorar com IA"><i class="fas fa-magic"></i></button>
                    </div>
                    <textarea class="message-input" id="msgInput" rows="1" placeholder="Escreva uma mensagem..."></textarea>
                    <div class="input-footer">
                        <span style="font-size: 0.75rem; color: #94a3b8;">Enter para enviar, Shift+Enter para pular linha</span>
                        <div style="display: flex; gap: 10px;">
                             <button class="tool-btn"><i class="fas fa-microphone"></i></button>
                             <button class="send-btn" onclick="sendMessage()">Enviar <i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 3. RIGHT PANEL (CRM) -->
        <div class="intelligence-panel" id="crm-panel">
            <div class="panel-hero">
                <div class="hero-avatar" id="crm-avatar">{{ substr($chats[0]->contact_name, 0, 1) }}</div>
                <h3 style="margin: 0; font-size: 1.2rem; margin-bottom: 5px;" id="crm-name">{{ $chats[0]->contact_name }}</h3>
                <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 10px;" id="crm-phone">{{ $chats[0]->contact_phone }}</span>
                <div style="display: flex; justify-content: center; gap: 5px;">
                    <span class="tag-badge hot">Quente 🔥</span>
                    <span class="tag-badge">Novo Lead</span>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-3 w-100" style="border-radius: 20px;">Ver Perfil Completo</button>
            </div>

            <div class="crm-content">
                <!-- Accordion 1 -->
                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-contact-info">
                        <span><i class="far fa-id-card me-2 text-muted"></i> Dados de Contato</span>
                        <i class="fas fa-chevron-down text-muted small"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-contact-info">
                        <div class="info-row">
                            <span class="label">E-mail</span>
                            <span class="value">contato@empresa.com</span>
                        </div>
                         <div class="info-row">
                            <span class="label">Empresa</span>
                            <span class="value">Acme Solutions Ltda</span>
                        </div>
                         <div class="info-row mb-0">
                            <span class="label">Cargo</span>
                            <span class="value">Gerente de Compras</span>
                        </div>
                    </div>
                </div>
                
                <!-- Accordion 2 -->
                <div class="crm-section">
                    <div class="crm-header" data-bs-toggle="collapse" data-bs-target="#crm-notes">
                        <span><i class="far fa-sticky-note me-2 text-warning"></i> Notas & IA</span>
                        <i class="fas fa-chevron-down text-muted small"></i>
                    </div>
                    <div class="crm-body collapse show" id="crm-notes">
                        <div class="mb-3 text-end"><button class="btn btn-sm btn-outline-secondary py-0" style="font-size: 0.75rem;" onclick="openNoteModal()">+ Criar Nota</button></div>
                        <div id="crm-notes-container">
                            <div class="text-center text-muted small py-3">Nenhuma nota.</div>
                        </div>
                    </div>
                </div>

                 <!-- Accordion 3 -->
                <div class="crm-section">
                    <div class="crm-header collapsed" data-bs-toggle="collapse" data-bs-target="#crm-history">
                        <span><i class="fas fa-history me-2 text-info"></i> Histórico</span>
                        <i class="fas fa-chevron-right text-muted small"></i>
                    </div>
                </div>
            </div>
        </div>

        @else
        <!-- EMPTY STATE (NO CHATS) -->
        <div class="empty-state" style="flex: 1;">
            <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05);">
                <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                <h2>Bem-vindo ao OmniChannel</h2>
                <p>Nenhuma conversa ativa no momento.<br>Aguarde novas mensagens ou inicie um atendimento ativo.</p>
                <button class="send-btn" style="margin: 20px auto;">Nova Transmissão</button>
            </div>
        </div>
        @endif
    </div>

    <!-- Modals -->
    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-6">Nova Nota Interna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                   <textarea id="newNoteContent" class="form-control" rows="3" placeholder="Ex: Cliente prefere contato pela manhã..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveNote()">Salvar Nota</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Canned Responses Modal -->
    <div class="modal fade" id="cannedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-6">Respostas Rápidas (Macros)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                   <div class="list-group list-group-flush" id="cannedList">
                       <!-- Populated by JS -->
                   </div>
                   <div class="p-3 border-top bg-light">
                       <input type="text" id="newCannedTitle" class="form-control form-control-sm mb-2" placeholder="Título (ex: /pix)">
                       <textarea id="newCannedContent" class="form-control form-control-sm mb-2" placeholder="Conteúdo da mensagem..." rows="2"></textarea>
                       <button class="btn btn-outline-primary btn-sm w-100" onclick="createCanned()">Criar Nova Macro</button>
                   </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentChatId = {{ count($chats) > 0 ? $chats[0]->id : 'null' }};
        const csrfToken = '{{ csrf_token() }}';
        let cannedResponses = [];

        $(document).ready(function() {
            if(currentChatId) {
                // Initialize with first chat
                loadChatData(currentChatId);
            }

            // Filtering Logic
            $('.filter-tab').click(function() {
                $('.filter-tab').removeClass('active');
                $(this).addClass('active');
                
                const filter = $(this).data('filter'); // 'all', 'unread', 'waiting'
                
                $('.contact-item').each(function() {
                    const isUnread = $(this).data('unread') == true;
                    const isWaiting = $(this).data('waiting') == true;
                    
                    if(filter === 'all') $(this).show();
                    else if(filter === 'unread') {
                        isUnread ? $(this).show() : $(this).hide();
                    }
                    else if(filter === 'waiting') {
                        isWaiting ? $(this).show() : $(this).hide();
                    }
                });
            });
        });

        function selectChat(el, id) {
            currentChatId = id;
            $('.contact-item').removeClass('active');
            $(el).addClass('active');
            loadChatData(id);
        }

        function loadChatData(id) {
            // Show Loading
            $('#chat-messages-area').html('<div style="display:flex; justify-content:center; align-items:center; height:100%; color:#999;"><div class="spinner-border text-primary" role="status"></div></div>');
            
            $.get('{{ url("/whatsapp/chat") }}/' + id + '/messages', function(data) {
                updateUI(data.chat);
                renderMessages(data.messages);
                renderNotes(data.notes);
                
                // Store canned responses globally or update list
                if(data.canned_responses) {
                    cannedResponses = data.canned_responses;
                    renderCannedList();
                }
            });
        }

        function updateUI(chat) {
            $('#header-name, #crm-name').text(chat.contact_name);
            $('#crm-avatar, #header-avatar').text(chat.contact_name.charAt(0));
            $('#crm-phone').text(chat.contact_phone || '--');
        }

        function renderMessages(messages) {
             let html = '';
             messages.forEach(msg => {
                let isOut = msg.direction === 'outbound';
                html += `
                    <div class="message-row ${isOut ? 'message-out' : 'message-in'}">
                        <div class="bubble ${isOut ? 'out' : 'in'}">
                            ${msg.content}
                            <div class="meta">
                                ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                ${isOut ? '<i class="fas fa-check-double text-light"></i>' : ''}
                            </div>
                        </div>
                    </div>
                `;
             });
             $('#chat-messages-area').html(html);
             scrollToBottom();
        }

        function renderNotes(notes) {
            const container = $('#crm-notes-container');
            let html = '';
            
            if(!notes || notes.length === 0) {
                html = '<div class="text-center text-muted small py-3">Nenhuma nota.</div>';
            } else {
                notes.forEach(note => {
                    let icon = note.type === 'ai_insight' ? '<i class="fas fa-dog text-warning"></i>' : '<i class="fas fa-user text-muted"></i>';
                    let bg = note.type === 'ai_insight' ? '#fffbeb' : '#f8fafc';
                    html += `
                        <div class="timeline-item">
                            <div class="timeline-content" style="background: ${bg}; border: 1px solid #e2e8f0;">
                                ${icon} ${note.content}
                                <span class="timeline-date">${new Date(note.created_at).toLocaleDateString()} • ${note.user ? note.user.name : 'Sistema'}</span>
                            </div>
                        </div>
                    `;
                });
            }
            container.html(html);
        }

        function renderCannedList() {
            let html = '';
            cannedResponses.forEach(c => {
                html += `
                    <button class="list-group-item list-group-item-action" onclick="useCanned('${c.content.replace(/'/g, "\\'")}')">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold">${c.title}</h6>
                        </div>
                        <p class="mb-1 small text-muted text-truncate">${c.content}</p>
                    </button>
                `;
            });
            $('#cannedList').html(html);
        }

        // --- Actions ---

        function scrollToBottom() {
            const el = document.getElementById('chat-messages-area');
            if(el) el.scrollTop = el.scrollHeight;
        }

        function sendMessage() {
            const txt = $('#msgInput').val();
            if(!txt.trim() || !currentChatId) return;
            
            $('#msgInput').val('');
            
            // Optimistic
            $('#chat-messages-area').append(`
                <div class="message-row message-out">
                    <div class="bubble out">
                        ${txt}
                        <div class="meta">Agora <i class="far fa-clock"></i></div>
                    </div>
                </div>
            `);
            scrollToBottom();

            $.post('{{ url("/whatsapp/chat/send") }}', {
                _token: csrfToken,
                chat_id: currentChatId,
                message: txt
            });
        }

        $('#msgInput').on('keypress', function(e) {
            if(e.which == 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Notes Logic
        function openNoteModal() {
            $('#addNoteModal').modal('show');
        }

        function saveNote() {
            const content = $('#newNoteContent').val();
            if(!content) return;

            $.post('{{ url("/whatsapp/notes") }}', {
                _token: csrfToken,
                chat_id: currentChatId,
                content: content
            }, function(newNote) {
                $('#addNoteModal').modal('hide');
                $('#newNoteContent').val('');
                // Refresh data
                loadChatData(currentChatId);
            });
        }

        // Canned Responses Logic
        function openCannedModal() {
            $('#cannedModal').modal('show');
        }

        function createCanned() {
            const title = $('#newCannedTitle').val();
            const content = $('#newCannedContent').val();
            if(!title || !content) return;

            $.post('{{ url("/whatsapp/canned") }}', {
                _token: csrfToken,
                title: title,
                content: content
            }, function(resp) {
                cannedResponses.push(resp);
                renderCannedList();
                $('#newCannedTitle').val('');
                $('#newCannedContent').val('');
            });
        }

        function useCanned(text) {
            $('#msgInput').val(text);
            $('#cannedModal').modal('hide');
            $('#msgInput').focus();
        }

        // --- Simulation ---
        function simulateMessage() {
            const msg = prompt("Digite a mensagem para simular um cliente:");
            if(!msg) return;

            // Get current phone
            const currentPhone = $('#crm-phone').text();
            
            // Feedback visual
            $('#chat-messages-area').append(`
                <div class="text-center text-muted small my-2">
                    <i class="fas fa-satellite-dish"></i> Simulando mensagem de ${currentPhone}...
                </div>
            `);
            scrollToBottom();

            $.post('{{ url("/whatsapp/test/receive") }}', {
                _token: csrfToken,
                phone: currentPhone,
                message: msg
            }, function() {
                // Refresh chat after a short delay to see the AI reply
                setTimeout(() => loadChatData(currentChatId), 1500); 
                // Second check to ensure AI reply is caught if slow
                setTimeout(() => loadChatData(currentChatId), 5000);
            }).fail(function() {
                 alert("Erro ao simular. Verifique o console.");
            });
        }
    </script>
</body>
</html>
