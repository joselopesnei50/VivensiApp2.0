document.addEventListener('DOMContentLoaded', () => {
    // Only run tour if specifically requested or first time
    const tourStatus = localStorage.getItem('vivensi-tour-completed');
    if (tourStatus === 'true') return;

    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'vivensi-tour-theme',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: {
                enabled: true
            }
        }
    });

    // Marca como completo em qualquer forma de encerramento (X, ESC, backdrop
    // click, finalizar). Antes so o botao "Finalizar Tour" gravava — quem
    // fechava pelo X reabria o tour a cada page load. Isso, combinado com o
    // useModalOverlay:true, prendia o user (especialmente credenciados) num
    // loop de overlay bloqueante.
    const markTourCompleted = () => {
        try { localStorage.setItem('vivensi-tour-completed', 'true'); } catch (e) {}
    };
    tour.on('cancel',   markTourCompleted);
    tour.on('complete', markTourCompleted);

    // Add Styles for the tour
    const style = document.createElement('style');
    style.innerHTML = `
        .shepherd-element.vivensi-tour-theme {
            background: #1e293b !important;
            color: white !important;
            border-radius: 20px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            max-width: 420px !important;
            outline: none !important;
        }
        .shepherd-content {
            background: transparent !important;
            border-radius: 20px !important;
        }
        .shepherd-header {
            background: rgba(255,255,255,0.05) !important;
            padding: 20px 24px 10px !important;
            border-top-left-radius: 20px !important;
            border-top-right-radius: 20px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }
        .shepherd-title {
            color: #818cf8 !important;
            font-size: 1rem !important;
            font-weight: 800 !important;
            line-height: 1.4 !important;
            margin: 0 !important;
            flex: 1 !important;
            padding-right: 10px !important;
        }
        .shepherd-text {
            color: #cbd5e1 !important;
            padding: 10px 24px 20px !important;
            font-size: 0.92rem !important;
            line-height: 1.6 !important;
        }
        .shepherd-footer {
            padding: 0 24px 24px !important;
            display: flex !important;
            justify-content: flex-end !important;
            gap: 12px !important;
        }
        .shepherd-button {
            background: #4f46e5 !important;
            color: white !important;
            border-radius: 12px !important;
            padding: 10px 20px !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
        }
        .shepherd-button:hover {
            background: #4338ca !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.4) !important;
        }
        .shepherd-button-secondary {
            background: rgba(255,255,255,0.05) !important;
            color: #94a3b8 !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            box-shadow: none !important;
        }
        .shepherd-cancel-icon {
            color: rgba(255,255,255,0.4) !important;
            font-size: 24px !important;
            font-weight: 300 !important;
            transition: color 0.2s !important;
        }
        .shepherd-cancel-icon:hover {
            color: #ef4444 !important;
        }
        .shepherd-arrow:before {
            background: #1e293b !important;
        }
    `;
    document.head.appendChild(style);

    tour.addStep({
        id: 'welcome',
        title: '🚀 Bem-vindo ao Vivensi Command Center!',
        text: 'Preparamos uma interface de elite para você gerir seus projetos e recursos com precisão cirúrgica.',
        buttons: [{
            text: 'Próximo',
            action: tour.next
        }]
    });

    tour.addStep({
        id: 'sidebar',
        attachTo: { element: '.sidebar', on: 'right' },
        title: '📂 Módulos de Gestão',
        text: 'Aqui você acessa todas as áreas: Financeiro, Marketing, RH e Auditoria. Tudo organizado por grupos expansíveis.',
        buttons: [{
            text: 'Voltar',
            action: tour.back,
            classes: 'shepherd-button-secondary'
        }, {
            text: 'Entendi',
            action: tour.next
        }]
    });

    tour.addStep({
        id: 'topbar',
        attachTo: { element: '#live-clock', on: 'bottom' },
        title: '⚡ Central de Comando',
        text: 'O Topbar é fixo e mostra seu status em tempo real. Veja o relógio e acesse as notificações instantâneas aqui.',
        buttons: [{
            text: 'Voltar',
            action: tour.back,
            classes: 'shepherd-button-secondary'
        }, {
            text: 'Legal!',
            action: tour.next
        }]
    });

    tour.addStep({
        id: 'search',
        attachTo: { element: '#global-search-trigger', on: 'bottom' },
        title: '🔍 Busca Ultrarrápida (Ctrl+K)',
        text: 'Precisa achar um projeto ou doador? Use a busca global. É o jeito mais rápido de navegar sem tirar as mãos do teclado.',
        buttons: [{
            text: 'Voltar',
            action: tour.back,
            classes: 'shepherd-button-secondary'
        }, {
            text: 'Ótimo',
            action: tour.next
        }]
    });

    tour.addStep({
        id: 'notifications',
        attachTo: { element: '#notification-bell', on: 'bottom' },
        title: '🛎️ Notificações em Tempo Real',
        text: 'Nunca perca um evento importante. Receba alertas de chat, novos leads e status de projetos conforme acontecem.',
        buttons: [{
            text: 'Voltar',
            action: tour.back,
            classes: 'shepherd-button-secondary'
        }, {
            text: 'Entendi',
            action: tour.next
        }]
    });

    tour.addStep({
        id: 'theme',
        attachTo: { element: '#theme-toggle', on: 'bottom' },
        title: '🌓 Modo Dark ou Light?',
        text: 'Você decide o estilo. Alterne entre o modo escuro premium e o modo claro clássico com um clique.',
        buttons: [{
            text: 'Voltar',
            action: tour.back,
            classes: 'shepherd-button-secondary'
        }, {
            text: 'Finalizar Tour',
            action: () => {
                localStorage.setItem('vivensi-tour-completed', 'true');
                tour.complete();
            }
        }]
    });

    // Start the tour
    setTimeout(() => {
        tour.start();
    }, 1500);
});

// Helper for resetting tour (for dev/user manual request)
window.resetVivensiTour = () => {
    localStorage.removeItem('vivensi-tour-completed');
    location.reload();
};

// Inicia o tour direto (usado pelo botão do onboarding)
window.startVivensiTour = () => {
    localStorage.removeItem('vivensi-tour-completed');
    location.reload();
};
