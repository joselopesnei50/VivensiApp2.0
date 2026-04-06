<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<div id="vivensi-floating-dock" class="vivensi-dock">
    <style>
        .vivensi-dock {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column-reverse;
            align-items: flex-end;
            gap: 15px;
        }
        @media (max-width: 768px) {
            .vivensi-dock {
                bottom: 20px !important;
                right: 15px !important;
            }
        }
    </style>
    
    <!-- Main Toggle Button -->
    <button onclick="toggleDock()" id="main-fab" style="width: 56px; height: 56px; border-radius: 50%; background: #1e293b; color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; transition: transform 0.2s ease;">
        <i class="fas fa-plus" id="fab-icon"></i>
    </button>
    <style>
        #main-fab:hover { transform: rotate(90deg); background: #334155; }
    </style>

    <!-- Menu Items (Hidden by default) -->
    <div id="dock-menu" style="display: none; flex-direction: column; gap: 10px; align-items: flex-end;">
        
        <!-- AI Assistant -->
        <div class="dock-item" style="display: flex; align-items: center; gap: 10px;">
            <span class="dock-label" style="background: #1e293b; color: white; padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; opacity: 0; transition: opacity 0.2s;">Bruce AI</span>
            <img onclick="toggleChat()" src="{{ asset('img/bruce-ai.png') }}" alt="Bruce AI" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid #6366f1; padding: 2px; object-fit: cover; cursor: pointer; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);" title="Atendimento via Bruce AI 🐶">
        </div>

        <!-- Reconciliation Shortcut -->
        <div class="dock-item" style="display: flex; align-items: center; gap: 10px;">
            <span class="dock-label" style="background: #1e293b; color: white; padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; opacity: 0; transition: opacity 0.2s;">Conciliação</span>
            <a href="{{ url('/ngo/reconciliation') }}" style="width: 50px; height: 50px; border-radius: 50%; background: #0ea5e9; color: white; border: none; box-shadow: 0 4px 10px rgba(14, 165, 233, 0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                <i class="fas fa-university"></i>
            </a>
        </div>


    </div>

    <!-- Chat Window (Remains the same but position adjusted relative to dock if needed, or fixed logic) -->
    <div id="chat-window" style="display: none; position: fixed; bottom: 100px; right: 30px; width: 350px; height: 500px; background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; z-index: 10000;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 15px 20px; color: white; display: flex; align-items: center; gap: 10px;">
            <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce AI" style="width: 40px; height: 40px; border-radius: 50%; border: 2px solid white; padding: 1px; object-fit: cover;" title="Atendimento via Bruce AI 🐶">
            <div>
                <h4 style="margin: 0; font-size: 1rem;">Bruce AI 🐶</h4>
                <span style="font-size: 0.75rem; opacity: 0.9;">Cão-sultor Financeiro</span>
            </div>
            <button onclick="toggleChat()" style="margin-left: auto; background: none; border: none; color: white; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>

        <!-- Messages Area -->
        <div id="chat-messages" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 15px;">
            <div class="message system" style="align-self: flex-start; background: white; padding: 10px 15px; border-radius: 12px 12px 12px 0; max-width: 85%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); font-size: 0.9rem; color: #334155;">
                Au! 🐶 Sou o Bruce, seu assistente financeiro. Posso ajudar a analisar seus gastos, explicar relatórios ou encontrar lançamentos. O que vamos farejar hoje?
            </div>
        </div>

        <!-- Input Area -->
        <div style="padding: 15px; border-top: 1px solid #e2e8f0; background: white; display: flex; gap: 10px;">
            <input type="text" id="chat-input" placeholder="Pergunte ao Bruce..." style="flex: 1; border: 1px solid #cbd5e1; border-radius: 20px; padding: 10px 15px; font-size: 0.9rem; outline: none;">
            <button onclick="sendMessage()" id="send-btn" style="background: #4f46e5; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .dock-item:hover .dock-label {
        opacity: 1 !important;
    }
    /* Estilo para markdown renderizado */
    #chat-messages p { margin: 0 0 10px 0; }
    #chat-messages p:last-child { margin: 0; }
    #chat-messages ul, #chat-messages ol { margin: 0 0 10px 0; padding-left: 20px; }
    #chat-messages strong { font-weight: 700; color: #1e293b; }
</style>

<script>
    let chatHistory = [];
    let isDockOpen = false;

    function toggleDock() {
        const menu = document.getElementById('dock-menu');
        const icon = document.getElementById('fab-icon');
        
        if (isDockOpen) {
            menu.style.display = 'none';
            icon.classList.remove('fa-times');
            icon.classList.add('fa-plus');
            icon.style.transform = 'rotate(0deg)';
        } else {
            menu.style.display = 'flex';
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-times');
            icon.style.transform = 'rotate(90deg)';
        }
        isDockOpen = !isDockOpen;
    }

    function toggleChat() {
        const chatWindow = document.getElementById('chat-window');
        if (chatWindow.style.display === 'none') {
            chatWindow.style.display = 'flex';
            // Hide dock menu on mobile to save space if needed
            setTimeout(() => document.getElementById('chat-input').focus(), 100);
        } else {
            chatWindow.style.display = 'none';
        }
    }

    async function sendMessage() {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        if (!message) return;

        // Add user message to UI
        appendMessage(message, 'user');
        input.value = '';

        // Add loading indicator
        const loadingId = appendLoading();

        try {
            const response = await fetch('{{ url("/api/chat/send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ 
                    message: message,
                    history: chatHistory 
                })
            });

            const data = await response.json();
            
            // Remove loading
            document.getElementById(loadingId).remove();

            if (data.error) {
                appendMessage('Erro: ' + data.error, 'system');
            } else {
                appendMessage(data.reply, 'system');
                // Update history
                chatHistory.push({ role: 'user', content: message });
                chatHistory.push({ role: 'assistant', content: data.reply });
            }

        } catch (e) {
            document.getElementById(loadingId).remove();
            appendMessage('Erro de conexão. Tente novamente.', 'system');
        }
    }

    function appendMessage(text, sender) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        
        div.style.padding = '10px 15px';
        div.style.maxWidth = '85%';
        div.style.fontSize = '0.9rem';
        div.style.lineHeight = '1.4';
        
        if (sender === 'user') {
            div.style.alignSelf = 'flex-end';
            div.style.background = '#4f46e5';
            div.style.color = 'white';
            div.style.borderRadius = '12px 12px 0 12px';
            div.style.boxShadow = '0 4px 6px rgba(79, 70, 229, 0.2)';
            div.textContent = text; // User input is plain text to avoid XSS
        } else {
            div.style.alignSelf = 'flex-start';
            div.style.background = 'white';
            div.style.color = '#334155';
            div.style.borderRadius = '12px 12px 12px 0';
            div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
            // Parse Markdown for system messages
            div.innerHTML = marked.parse(text);
        }
        
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function appendLoading() {
        const container = document.getElementById('chat-messages');
        const id = 'loading-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.style.alignSelf = 'flex-start';
        div.style.color = '#94a3b8';
        div.style.fontSize = '0.8rem';
        div.style.fontStyle = 'italic';
        div.style.marginLeft = '10px';
        div.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Digitando...';
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    // Enter key support
    document.getElementById('chat-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
</script>
