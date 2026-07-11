{{-- Modal de boas-vindas destacando Bruce IA + Sala de Estratégia.
     Só renderiza quando welcome_dismissed_at é null. Auto-abre no page load.
     Baseado na identidade visual BruceIA v1.0 (preto #0A0A0B + laranja #FF7A1A). --}}
@auth
@if (auth()->user()->welcome_dismissed_at === null)
<div id="welcome-modal-backdrop" class="welcome-modal-backdrop">
    <div class="welcome-modal" role="dialog" aria-modal="true" aria-labelledby="welcome-modal-title">

        {{-- Close button --}}
        <button type="button" class="welcome-modal-close" onclick="dismissWelcomeModal()" aria-label="Fechar">&times;</button>

        {{-- Hero: logo + título --}}
        <div class="welcome-modal-hero">
            <img src="{{ asset('img/bruce/bruceia-logo-fundo-escuro.svg') }}" alt="BruceIA" class="welcome-modal-logo">
            <div class="welcome-modal-badge">
                <span class="welcome-modal-badge-dot"></span>
                <span>Novidades no Vivensi</span>
            </div>
            <h2 id="welcome-modal-title" class="welcome-modal-title">Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p class="welcome-modal-desc">
                Sua Central de Comando agora conta com dois recursos que aceleram decisões: o Bruce IA e a Sala de Estratégia.
            </p>
        </div>

        {{-- Cards de features --}}
        <div class="welcome-modal-cards">

            <div class="welcome-modal-card">
                <div class="welcome-modal-card-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="welcome-modal-card-title">Bruce IA</h3>
                <p class="welcome-modal-card-desc">
                    Assistente inteligente que analisa seus dados em tempo real e sugere ações. Peça insights, dashboards ou explicações.
                </p>
                <a href="{{ url('/smart-analysis') }}" class="welcome-modal-cta" onclick="dismissWelcomeModal(true)">
                    <i class="fas fa-arrow-right"></i> Conhecer Bruce IA
                </a>
            </div>

            <div class="welcome-modal-card">
                <div class="welcome-modal-card-icon">
                    <i class="fas fa-chess"></i>
                </div>
                <h3 class="welcome-modal-card-title">Sala de Estratégia</h3>
                <p class="welcome-modal-card-desc">
                    Sessão guiada por 5 personas especialistas que analisam seu negócio e entregam um plano de ação em Kanban.
                </p>
                <a href="{{ url('/strategy-room') }}" class="welcome-modal-cta" onclick="dismissWelcomeModal(true)">
                    <i class="fas fa-arrow-right"></i> Entrar na Sala
                </a>
            </div>

        </div>

        {{-- Footer com "não mostrar mais" --}}
        <div class="welcome-modal-footer">
            <button type="button" class="welcome-modal-later" onclick="dismissWelcomeModal()">
                Depois. Não mostrar de novo.
            </button>
        </div>
    </div>
</div>

<style>
    .welcome-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 10, 11, 0.85);
        backdrop-filter: blur(8px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: welcome-fade-in .35s ease-out;
    }
    @keyframes welcome-fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .welcome-modal {
        position: relative;
        background: #0A0A0B;
        border: 1px solid rgba(255, 122, 26, 0.15);
        border-radius: 24px;
        max-width: 560px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 34px 30px 26px;
        color: #fff;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        animation: welcome-slide-up .4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes welcome-slide-up {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .welcome-modal-close {
        position: absolute;
        top: 14px;
        right: 18px;
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.5);
        font-size: 1.8rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        transition: color .15s;
    }
    .welcome-modal-close:hover { color: #FF7A1A; }

    .welcome-modal-hero {
        text-align: center;
        margin-bottom: 24px;
    }
    .welcome-modal-logo {
        width: 100px;
        height: auto;
        margin-bottom: 14px;
    }
    .welcome-modal-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 122, 26, 0.1);
        border: 1px solid rgba(255, 122, 26, 0.3);
        border-radius: 999px;
        padding: 5px 14px;
        margin-bottom: 14px;
        font-size: .7rem;
        font-weight: 800;
        color: #FF7A1A;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .welcome-modal-badge-dot {
        width: 6px;
        height: 6px;
        background: #FF7A1A;
        border-radius: 50%;
        box-shadow: 0 0 8px #FF7A1A;
    }
    .welcome-modal-title {
        font-size: 1.9rem;
        font-weight: 900;
        letter-spacing: -1px;
        margin: 0 0 8px;
        color: #fff;
    }
    .welcome-modal-desc {
        color: rgba(255, 255, 255, 0.65);
        font-size: .95rem;
        line-height: 1.55;
        margin: 0;
    }

    .welcome-modal-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 20px;
    }
    @media (max-width: 520px) {
        .welcome-modal-cards { grid-template-columns: 1fr; }
    }

    .welcome-modal-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 20px 18px;
        display: flex;
        flex-direction: column;
        transition: border-color .2s, transform .2s;
    }
    .welcome-modal-card:hover {
        border-color: rgba(255, 122, 26, 0.4);
        transform: translateY(-2px);
    }
    .welcome-modal-card-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 122, 26, 0.12);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF7A1A;
        font-size: 1.1rem;
        margin-bottom: 12px;
    }
    .welcome-modal-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        margin: 0 0 6px;
        color: #fff;
    }
    .welcome-modal-card-desc {
        font-size: .82rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.5;
        margin: 0 0 14px;
        flex: 1;
    }
    .welcome-modal-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #FF7A1A;
        color: #0A0A0B !important;
        text-decoration: none;
        font-size: .78rem;
        font-weight: 800;
        padding: 8px 14px;
        border-radius: 10px;
        justify-content: center;
        transition: background .15s, transform .15s;
    }
    .welcome-modal-cta:hover {
        background: #ff8f3a;
        transform: translateX(2px);
    }

    .welcome-modal-footer {
        text-align: center;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        padding-top: 18px;
        margin-top: 4px;
    }
    .welcome-modal-later {
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        padding: 4px 10px;
        transition: color .15s;
    }
    .welcome-modal-later:hover { color: #FF7A1A; }
</style>

<script>
    (function () {
        var backdrop = document.getElementById('welcome-modal-backdrop');
        if (!backdrop) return;

        // Fecha ao clicar fora do card
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) dismissWelcomeModal();
        });

        // Fecha com ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && backdrop.style.display !== 'none') dismissWelcomeModal();
        });
    })();

    function dismissWelcomeModal(followingLink) {
        var backdrop = document.getElementById('welcome-modal-backdrop');
        if (backdrop) backdrop.style.display = 'none';

        fetch(@json(route('welcome.dismiss')), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        }).catch(function () { /* silencioso — se falhar, tentaremos de novo no próximo carregamento */ });
    }
</script>
@endif
@endauth
