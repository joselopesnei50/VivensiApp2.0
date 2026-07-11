{{-- Popup público destacando BruceIA — canto inferior direito, slide-in após 6s.
     Persistência via localStorage: uma vez fechado, não reaparece nesse browser
     durante 30 dias. Design baseado na identidade BruceIA v1.0 (preto+laranja). --}}

<div id="bruce-public-popup" class="bpp-wrapper" role="dialog" aria-labelledby="bpp-title" aria-hidden="true">

    <button type="button" class="bpp-close" onclick="closeBrucePopup()" aria-label="Fechar">&times;</button>

    <div class="bpp-inner">

        <div class="bpp-avatar">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="BruceIA" width="52" height="52">
            <span class="bpp-dot" aria-hidden="true"></span>
        </div>

        <div class="bpp-content">
            <div class="bpp-badge">
                <span class="bpp-badge-dot"></span>
                <span>Novo no Vivensi</span>
            </div>
            <h4 id="bpp-title" class="bpp-title">Conheça o <span>Bruce IA</span></h4>
            <p class="bpp-desc">
                Assistente inteligente que analisa seu negócio, antecipa decisões e monta seu plano estratégico automaticamente.
            </p>
            <div class="bpp-actions">
                <a href="{{ url('/solucoes/pessoa-comum') }}" class="bpp-cta" onclick="closeBrucePopup()">
                    Ver como funciona <i class="fas fa-arrow-right"></i>
                </a>
                <button type="button" class="bpp-later" onclick="closeBrucePopup()">Depois</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bpp-wrapper {
        position: fixed;
        bottom: 20px;
        right: 20px;
        max-width: 380px;
        width: calc(100% - 40px);
        background: #0A0A0B;
        border: 1px solid rgba(255, 122, 26, 0.2);
        border-radius: 20px;
        padding: 20px;
        color: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 122, 26, 0.05);
        z-index: 9998;
        opacity: 0;
        transform: translateY(30px) scale(0.95);
        pointer-events: none;
        transition: opacity .35s ease-out, transform .35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bpp-wrapper.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    .bpp-close {
        position: absolute;
        top: 10px;
        right: 12px;
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: 1.5rem;
        line-height: 1;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 8px;
        transition: color .15s, background .15s;
    }
    .bpp-close:hover { color: #FF7A1A; background: rgba(255, 122, 26, 0.08); }

    .bpp-inner {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }
    .bpp-avatar {
        position: relative;
        flex-shrink: 0;
    }
    .bpp-avatar img {
        display: block;
        border-radius: 12px;
    }
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
    .bpp-content { flex: 1; min-width: 0; }

    .bpp-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 122, 26, 0.1);
        border: 1px solid rgba(255, 122, 26, 0.3);
        border-radius: 999px;
        padding: 3px 10px;
        margin-bottom: 8px;
        font-size: .62rem;
        font-weight: 800;
        color: #FF7A1A;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }
    .bpp-badge-dot {
        width: 5px;
        height: 5px;
        background: #FF7A1A;
        border-radius: 50%;
        box-shadow: 0 0 6px #FF7A1A;
    }
    .bpp-title {
        font-size: 1.1rem;
        font-weight: 900;
        margin: 0 0 6px;
        letter-spacing: -0.5px;
        color: #fff;
    }
    .bpp-title span { color: #FF7A1A; }
    .bpp-desc {
        font-size: .85rem;
        color: rgba(255, 255, 255, 0.65);
        margin: 0 0 14px;
        line-height: 1.5;
    }
    .bpp-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .bpp-cta {
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
        transition: background .15s, transform .15s;
    }
    .bpp-cta:hover {
        background: #ff8f3a;
        transform: translateX(2px);
    }
    .bpp-later {
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        padding: 8px 8px;
        transition: color .15s;
    }
    .bpp-later:hover { color: #FF7A1A; }

    @media (max-width: 480px) {
        .bpp-wrapper {
            bottom: 12px;
            right: 12px;
            max-width: calc(100vw - 24px);
        }
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
                var ageMs = Date.now() - ts;
                return ageMs > (TTL_DAYS * 24 * 60 * 60 * 1000);
            } catch (e) {
                // localStorage bloqueado (ex: modo privado) — mostra por padrão
                return true;
            }
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
            try {
                localStorage.setItem(LS_KEY, Date.now().toString());
            } catch (e) { /* ignora */ }
        };

        // Aparece após 6s, apenas se ainda não foi dispensado
        if (shouldShow()) {
            setTimeout(openBrucePopup, 6000);
        }
    })();
</script>
