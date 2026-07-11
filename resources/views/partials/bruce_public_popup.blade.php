{{-- Popup público destacando BruceIA — modal centralizado com backdrop escuro.
     Aparece 6s após load. Persistência via localStorage (TTL 30 dias).
     Propaganda pura do Bruce IA, sem redirect. --}}

<div id="bruce-public-popup" class="bpp-backdrop" role="dialog" aria-labelledby="bpp-title" aria-hidden="true">
    <div class="bpp-card">

        <button type="button" class="bpp-close" onclick="closeBrucePopup()" aria-label="Fechar">&times;</button>

        {{-- Hero --}}
        <div class="bpp-hero">
            <div class="bpp-avatar">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="BruceIA" width="70" height="70">
                <span class="bpp-dot" aria-hidden="true"></span>
            </div>
            <div class="bpp-badge">
                <span class="bpp-badge-dot"></span>
                <span>Inteligência Artificial no Vivensi</span>
            </div>
            <h3 id="bpp-title" class="bpp-title">Conheça o <span>Bruce IA</span></h3>
            <p class="bpp-tagline">O assistente estratégico da sua organização.</p>
        </div>

        {{-- Features --}}
        <div class="bpp-features">
            <div class="bpp-feature">
                <div class="bpp-feature-icon"><i class="fas fa-chart-line"></i></div>
                <div class="bpp-feature-text">
                    <strong>Analisa em tempo real</strong>
                    <span>Cruza indicadores financeiros, operacionais e sociais da sua ONG.</span>
                </div>
            </div>
            <div class="bpp-feature">
                <div class="bpp-feature-icon"><i class="fas fa-lightbulb"></i></div>
                <div class="bpp-feature-text">
                    <strong>Antecipa decisões</strong>
                    <span>Sugere ações e alertas antes dos problemas se tornarem críticos.</span>
                </div>
            </div>
            <div class="bpp-feature">
                <div class="bpp-feature-icon"><i class="fas fa-chess"></i></div>
                <div class="bpp-feature-text">
                    <strong>Monta seu plano estratégico</strong>
                    <span>Sessões guiadas por personas especialistas na Sala de Estratégia.</span>
                </div>
            </div>
        </div>

        {{-- Rodapé --}}
        <div class="bpp-footer">
            <button type="button" class="bpp-close-btn" onclick="closeBrucePopup()">Entendi</button>
        </div>
    </div>
</div>

<style>
    .bpp-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 10, 11, 0.75);
        backdrop-filter: blur(6px);
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity .3s ease-out;
    }
    .bpp-backdrop.open {
        display: flex;
        opacity: 1;
    }
    .bpp-card {
        position: relative;
        background: #0A0A0B;
        border: 1px solid rgba(255, 122, 26, 0.2);
        border-radius: 24px;
        max-width: 520px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 34px 28px 22px;
        color: #fff;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        transform: translateY(20px) scale(0.96);
        transition: transform .35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bpp-backdrop.open .bpp-card {
        transform: translateY(0) scale(1);
    }
    .bpp-close {
        position: absolute;
        top: 12px;
        right: 16px;
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.5);
        font-size: 1.8rem;
        line-height: 1;
        cursor: pointer;
        padding: 4px 10px;
        border-radius: 8px;
        transition: color .15s, background .15s;
    }
    .bpp-close:hover { color: #FF7A1A; background: rgba(255, 122, 26, 0.08); }

    .bpp-hero { text-align: center; margin-bottom: 22px; }
    .bpp-avatar {
        position: relative;
        display: inline-block;
        margin-bottom: 12px;
    }
    .bpp-avatar img {
        display: block;
        border-radius: 16px;
    }
    .bpp-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        background: #10b981;
        border: 2px solid #0A0A0B;
        border-radius: 50%;
        box-shadow: 0 0 8px #10b981;
    }
    .bpp-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 122, 26, 0.1);
        border: 1px solid rgba(255, 122, 26, 0.3);
        border-radius: 999px;
        padding: 4px 12px;
        margin-bottom: 12px;
        font-size: .65rem;
        font-weight: 800;
        color: #FF7A1A;
        text-transform: uppercase;
        letter-spacing: 1.4px;
    }
    .bpp-badge-dot {
        width: 5px;
        height: 5px;
        background: #FF7A1A;
        border-radius: 50%;
        box-shadow: 0 0 6px #FF7A1A;
    }
    .bpp-title {
        font-size: 1.7rem;
        font-weight: 900;
        margin: 0 0 4px;
        letter-spacing: -1px;
        color: #fff;
    }
    .bpp-title span { color: #FF7A1A; }
    .bpp-tagline {
        color: rgba(255, 255, 255, 0.6);
        font-size: .95rem;
        margin: 0;
    }

    .bpp-features {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 6px 0 20px;
    }
    .bpp-feature {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 14px;
        padding: 14px 16px;
        transition: border-color .15s;
    }
    .bpp-feature:hover { border-color: rgba(255, 122, 26, 0.25); }
    .bpp-feature-icon {
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        background: rgba(255, 122, 26, 0.12);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FF7A1A;
        font-size: 1rem;
    }
    .bpp-feature-text { flex: 1; }
    .bpp-feature-text strong {
        display: block;
        font-size: .95rem;
        font-weight: 800;
        color: #fff;
        margin-bottom: 2px;
    }
    .bpp-feature-text span {
        font-size: .82rem;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.45;
    }

    .bpp-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        padding-top: 16px;
        text-align: center;
    }
    .bpp-close-btn {
        background: #FF7A1A;
        color: #0A0A0B;
        border: 0;
        font-size: .85rem;
        font-weight: 800;
        padding: 10px 28px;
        border-radius: 12px;
        cursor: pointer;
        transition: background .15s, transform .15s;
    }
    .bpp-close-btn:hover {
        background: #ff8f3a;
        transform: translateY(-1px);
    }

    @media (max-width: 480px) {
        .bpp-card { padding: 26px 20px 18px; border-radius: 20px; }
        .bpp-title { font-size: 1.35rem; }
    }
</style>

<script>
    (function () {
        var LS_KEY = 'bruce_public_popup_dismissed_at';
        var TTL_DAYS = 30;

        function shouldShow() {
            try {
                var val = localStorage.getItem(LS_KEY);
                if (!val) return true;
                var ts = parseInt(val, 10);
                if (isNaN(ts)) return true;
                return (Date.now() - ts) > (TTL_DAYS * 24 * 60 * 60 * 1000);
            } catch (e) { return true; }
        }

        function openBrucePopup() {
            var el = document.getElementById('bruce-public-popup');
            if (!el) return;
            el.classList.add('open');
            el.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        window.closeBrucePopup = function () {
            var el = document.getElementById('bruce-public-popup');
            if (!el) return;
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            try { localStorage.setItem(LS_KEY, Date.now().toString()); } catch (e) {}
        };

        // Fecha ao clicar fora do card
        document.addEventListener('click', function (e) {
            var backdrop = document.getElementById('bruce-public-popup');
            if (backdrop && e.target === backdrop) closeBrucePopup();
        });

        // Fecha com ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var el = document.getElementById('bruce-public-popup');
                if (el && el.classList.contains('open')) closeBrucePopup();
            }
        });

        if (shouldShow()) {
            setTimeout(openBrucePopup, 6000);
        }
    })();
</script>
