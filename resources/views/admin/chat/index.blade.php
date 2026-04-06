@extends('layouts.app')

@section('content')
<style>
    :root {
        --chat-bg: #fdfdfd;
        --sidebar-bg: #ffffff;
        --active-chat-bg: #f3f4f6;
        --my-message: #6366f1;
        --their-message: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
    }

    .chat-wrapper {
        display: flex;
        height: calc(100vh - 160px);
        background: var(--sidebar-bg);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
        margin-top: 10px;
    }

    /* Sidebar */
    .chat-sidebar {
        width: 320px;
        border-right: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        background: var(--sidebar-bg);
    }

    .sidebar-header {
        padding: 24px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sidebar-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 15px;
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        background: #f8fafc;
        border: none;
        border-radius: 12px;
        font-size: 0.85rem;
    }

    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
    }

    .contacts-list {
        flex: 1;
        overflow-y: auto;
    }

    .contact-card {
        padding: 16px 24px;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s ease;
        border-bottom: 1px solid #f9fafb;
    }

    .contact-card:hover {
        background: #f9fafb;
    }

    .contact-card.active {
        background: var(--active-chat-bg);
        border-left: 4px solid var(--my-message);
    }

    .avatar {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        background: #eef2ff;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .contact-info {
        flex: 1;
        min-width: 0;
    }

    .contact-name {
        font-weight: 700;
        color: var(--text-main);
        font-size: 0.95rem;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .contact-status {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* Main Chat Area */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--chat-bg);
    }

    .chat-header {
        padding: 16px 30px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .messages-area {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
        background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px);
        background-size: 20px 20px;
    }

    .message-row {
        display: flex;
        flex-direction: column;
        max-width: 70%;
    }

    .message-row.me {
        align-self: flex-end;
    }

    .message-row.them {
        align-self: flex-start;
    }

    .bubble {
        padding: 12px 20px;
        font-size: 0.95rem;
        line-height: 1.5;
        position: relative;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }

    .message-row.me .bubble {
        background: var(--my-message);
        color: white;
        border-radius: 20px 20px 4px 20px;
    }

    .message-row.them .bubble {
        background: var(--their-message);
        color: var(--text-main);
        border: 1px solid #f1f5f9;
        border-radius: 20px 20px 20px 4px;
    }

    .time-stamp {
        font-size: 0.65rem;
        margin-top: 5px;
        color: var(--text-muted);
        font-weight: 600;
    }

    .message-row.me .time-stamp {
        text-align: right;
    }

    /* Input Area */
    .input-area {
        padding: 24px 30px;
        background: white;
        border-top: 1px solid #f1f5f9;
    }

    .input-container {
        display: flex;
        gap: 15px;
        background: #f8fafc;
        padding: 8px 8px 8px 20px;
        border-radius: 16px;
        align-items: center;
        border: 1px solid #f1f5f9;
    }

    .input-container input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        color: var(--text-main);
        outline: none;
    }

    .btn-send {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        background: var(--my-message);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-send:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #ccd6e0;
        text-align: center;
        padding: 40px;
    }

    .empty-state i {
        font-size: 5rem;
        margin-bottom: 20px;
        opacity: 0.1;
    }
</style>

<div class="chat-wrapper">
    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Conversas</div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Pesquisar contato...">
            </div>
        </div>
        <div class="contacts-list">
            @foreach($contacts as $contact)
            <div onclick="openChat({{ $contact->id }}, '{{ $contact->name }}')" class="contact-card" id="contact-{{ $contact->id }}">
                <div class="avatar">{{ substr($contact->name, 0, 1) }}</div>
                <div class="contact-info">
                    <div class="contact-name">{{ $contact->name }}</div>
                    <div class="contact-status">{{ $contact->role ?? $contact->department }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Main Chat -->
    <div class="chat-main">
        <!-- Chat Area Head -->
        <div id="chatHead" class="chat-header" style="display: none;">
            <div style="display: flex; align-items: center;">
                <div id="activeAvatar" class="avatar" style="width: 35px; height: 35px; font-size: 0.9rem; margin-right: 12px; border-radius: 10px;"></div>
                <div style="font-weight: 800; color: var(--text-main);" id="activeName"></div>
            </div>
            <div style="color: var(--text-muted);">
                <i class="fas fa-ellipsis-v cursor-pointer"></i>
            </div>
        </div>

        <!-- Messages Content -->
        <div id="messagesList" class="messages-area" style="display: none;">
        </div>

        <!-- Empty State -->
        <div id="chatPlaceholder" class="empty-state">
            <i class="fas fa-comment-dots"></i>
            <h4 style="color: #94a3b8; font-weight: 800;">Vivensi Messenger</h4>
            <p style="font-size: 0.9rem;">Selecione um membro do time para iniciar uma conversa auditável.</p>
        </div>

        <!-- Float Input -->
        <div id="inputBox" class="input-area" style="display: none;">
            <form id="chatForm" onsubmit="handleSend(event)">
                <input type="hidden" id="currentReceiverId">
                <div class="input-container">
                    <input type="text" id="msgContent" placeholder="Digite uma mensagem segura..." autocomplete="off">
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let activeReceiver = null;
    let refreshInterval = null;

    function openChat(id, name) {
        activeReceiver = id;
        document.getElementById('currentReceiverId').value = id;
        
        // UI Updates
        document.getElementById('chatPlaceholder').style.display = 'none';
        document.getElementById('chatHead').style.display = 'flex';
        document.getElementById('messagesList').style.display = 'flex';
        document.getElementById('inputBox').style.display = 'block';
        
        document.getElementById('activeName').innerText = name;
        document.getElementById('activeAvatar').innerText = name.charAt(0);

        // Active state
        document.querySelectorAll('.contact-card').forEach(c => c.classList.remove('active'));
        document.getElementById('contact-' + id).classList.add('active');

        loadMessages();
        
        // Polling
        if(refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(loadMessages, 5000);
    }

    async function loadMessages() {
        if(!activeReceiver) return;
        
        try {
            const response = await fetch(`{{ url('admin/api/chat/messages') }}/${activeReceiver}`);
            const messages = await response.json();
            
            const list = document.getElementById('messagesList');
            const shouldScroll = list.scrollTop + list.offsetHeight >= list.scrollHeight - 50;
            
            list.innerHTML = '';
            
            messages.forEach(m => {
                const isMe = m.sender_id == {{ auth()->id() }};
                const time = new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const div = document.createElement('div');
                div.className = `message-row ${isMe ? 'me' : 'them'}`;
                div.innerHTML = `
                    <div class="bubble">${m.message}</div>
                    <div class="time-stamp">${time}</div>
                `;
                list.appendChild(div);
            });
            
            if(shouldScroll) list.scrollTop = list.scrollHeight;
        } catch(e) { console.error("Erro ao carregar chat:", e); }
    }

    async function handleSend(e) {
        e.preventDefault();
        const msgInput = document.getElementById('msgContent');
        const content = msgInput.value.trim();
        const receiverId = document.getElementById('currentReceiverId').value;

        if(!content || !receiverId) return;

        // Limpa logo o input para feedback de velocidade
        msgInput.value = '';

        try {
            const response = await fetch('{{ url('admin/api/chat/send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    receiver_id: receiverId,
                    message: content
                })
            });

            if(response.ok) {
                loadMessages();
            } else {
                const err = await response.json();
                alert("Erro ao enviar: " + (err.error || "Tente novamente"));
                msgInput.value = content; // Devolve o texto em caso de erro
            }
        } catch(e) { 
            console.error(e);
            alert("Falha na conexão.");
            msgInput.value = content;
        }
    }
</script>
@endsection
