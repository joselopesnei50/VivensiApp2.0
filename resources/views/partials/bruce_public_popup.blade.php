{{-- Popup público destacando BruceIA — modal centralizado compacto.
     Aparece 6s após load. Persistência via localStorage (TTL 30 dias). --}}

<div id="bruce-public-popup" class="bpp-backdrop" role="dialog" aria-labelledby="bpp-title" aria-hidden="true">
    <div class="bpp-card">

        <button type="button" class="bpp-close" onclick="closeBrucePopup()" aria-label="Fechar">&times;</button>

        <div class="bpp-hero">
            <div class="bpp-avatar">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="BruceIA" width="60" height="60">
                <span class="bpp-dot" aria-hidden="true"></span>
            </div>
            <h3 id="bpp-title" class="bpp-title">Conheça o <span>Bruce IA</span></h3>
            <p class="bpp-tagline">O assistente estratégico da sua organização social.</p>
        </div>

        <ul class="bpp-features">
            <li>
                <i class="fas fa-chart-line"></i>
                <span><strong>Analisa</strong> indicadores em tempo real</span>
            </li>
            <li>
                <i class="fas fa-lightbulb"></i>
                <span><strong>Antecipa</strong> decisões e alertas críticos</span>
            </li>
            <li>
                <i class="fas fa-chess"></i>
                <span><strong>Monta</strong> seu plano estratégico na Sala de Estratégia</span>
            </li>
        </ul>

        <button type="button" class="bpp-close-btn" onclick="closeBrucePopup()">Entendi</button>
    </div>
</div>

<style>
    .bpp-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 10, 11, 0.72);
        backdrop-filter: blur(6px);
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        opacity: 0;
        transition: opacity .3s ease-out;
    }
    .bpp-backdrop.open { display: flex; opacity: 1; }

    .bpp-card {
        position: relative;
        background: #0A0A0B;
        border: 1px solid rgba(255, 122, 26, 0.18);
        border-radius: 20px;
        max-width: 420px;
        width: 100%;
        padding: 28px 26px 22px;
        color: #fff;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
        transform: translateY(16px) scale(0.97);
        transition: transform .3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bpp-backdrop.open .bpp-card { transform: translateY(0) scale(1); }

    .bpp-close {
        position: absolute;
        top: 10px;
        right: 14px;
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: 1.6rem;
        line-height: 1;
        cursor: pointer;
        padding: 4px 8px;
        transition: color .15s;
    }
    .bpp-close:hover { color: #FF7A1A; }

    .bpp-hero { text-align: center; margin-bottom: 18px; }
    .bpp-avatar {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
    }
    .bpp-avatar img { display: block; border-radius: 14px; }
    .bpp-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 14px;
        height: 14px;
        background: #10b981;
        border: 2px solid #0A0A0B;
        border-radius: 50%;
        box-shadow: 0 0 8px #10b981;
    }
    .bpp-title {
        font-size: 1.35rem;
        font-weight: 900;
        margin: 0 0 4px;
        letter-spacing: -0.5px;
        color: #fff;
    }
    .bpp-title span { color: #FF7A1A; }
    .bpp-tagline {
        color: rgba(255, 255, 255, 0.6);
        font-size: .85rem;
        margin: 0;
        line-height: 1.4;
    }

    .bpp-features {
        list-style: none;
        margin: 0 0 18px;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .bpp-features li {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        font-size: .85rem;
        color: rgba(255, 255, 255, 0.75);
        line-height: 1.35;
    }
    .bpp-features li i {
        width: 30px;
        height: 30px;
        flex-shrink: 0;
        background: rgba(255, 122, 26, 0.12);
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #FF7A1A;
        font-size: .9rem;
    }
    .bpp-features li strong { color: #fff; font-weight: 800; }

    .bpp-close-btn {
        display: block;
        width: 100%;
        background: #FF7A1A;
        color: #0A0A0B;
        border: 0;
        font-size: .9rem;
        font-weight: 800;
        padding: 11px 20px;
        border-radius: 12px;
        cursor: pointer;
        transition: background .15s, transform .15s;
    }
    .bpp-close-btn:hover {
        background: #ff8f3a;
        transform: translateY(-1px);
    }

    @media (max-width: 480px) {
        .bpp-card { padding: 24px 20px 18px; border-radius: 18px; }
        .bpp-title { font-size: 1.2rem; }
        .bpp-features li { font-size: .82rem; padding: 9px 10px; }
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
        }

        window.closeBrucePopup = function () {
            var el = document.getElementById('bruce-public-popup');
            if (!el) return;
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
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
