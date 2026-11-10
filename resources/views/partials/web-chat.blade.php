{{-- Chat do site com o Bruno. Incluir só quando WebChatService::isEnabled().
     $launcher (bool, default true): mostra o botão flutuante próprio.
     Abre também por qualquer elemento [data-vivchat] (valor "agendar" abre a agenda)
     e por ?chat=1 na URL. API: window.VivChat.open(mode?) --}}
@php
    $launcher = $launcher ?? true;
    $waNumber = \App\Models\SystemSetting::getValue('support_whatsapp', '16997618695');
@endphp
<div id="vc-root" class="vc-root"
     data-csrf="{{ csrf_token() }}"
     data-base="/chat"
     data-greeting="{{ config('web_chat.greeting') }}"
     data-wa="https://wa.me/55{{ $waNumber }}?text={{ rawurlencode('Olá! Vim pelo site do Vivensi.') }}"
     data-privacy="{{ route('legal.privacy') }}">

    @if($launcher)
    <div class="vc-teaser" id="vc-teaser" role="status" hidden>
        <button type="button" class="vc-teaser-x" id="vc-teaser-x" aria-label="Fechar aviso">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="vc-teaser-head">
            <span class="vc-mini-avatar" aria-hidden="true">B</span>
            <span><strong>Bruno</strong> · Vivensi</span>
        </div>
        <p>Oi! 👋 Quer ver como o Vivensi organiza a sua ONG? Tiro suas dúvidas agora ou já marco uma conversa de 20 min com a equipe.</p>
        <div class="vc-teaser-actions">
            <button type="button" class="vc-teaser-btn primary" data-vivchat>Conversar agora</button>
            <button type="button" class="vc-teaser-btn" data-vivchat="agendar">📅 Agendar</button>
        </div>
    </div>
    <button type="button" class="vc-launcher" id="vc-launcher" aria-label="Abrir chat com o Bruno" aria-expanded="false">
        <span class="vc-launcher-avatar" aria-hidden="true">
            <span class="vc-launcher-ring"></span>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><circle cx="8.5" cy="11.5" r=".6" fill="currentColor"/><circle cx="12" cy="11.5" r=".6" fill="currentColor"/><circle cx="15.5" cy="11.5" r=".6" fill="currentColor"/></svg>
            <span class="vc-launcher-dot"></span>
        </span>
        <span class="vc-launcher-text">
            <span class="vc-launcher-title">Fale com o Bruno</span>
            <span class="vc-launcher-sub">Online · responde na hora</span>
        </span>
        <span class="vc-badge" id="vc-badge" hidden>1</span>
    </button>
    @endif

    <section class="vc-panel" id="vc-panel" role="dialog" aria-modal="false" aria-labelledby="vc-title" hidden>
        <header class="vc-head">
            <div class="vc-avatar" aria-hidden="true">B<span class="vc-online"></span></div>
            <div class="vc-head-text">
                <h2 id="vc-title">Bruno · Vivensi</h2>
                <p id="vc-sub">Responde na hora</p>
            </div>
            <button type="button" class="vc-icon-btn" id="vc-menu-btn" aria-label="Mais opções" aria-haspopup="true" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
            </button>
            <button type="button" class="vc-icon-btn" id="vc-close" aria-label="Fechar chat">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="vc-menu" id="vc-menu" role="menu" hidden>
                <button type="button" role="menuitem" data-act="book">📅 Agendar uma conversa</button>
                <button type="button" role="menuitem" data-act="email">📩 Receber esta conversa por e-mail</button>
                <button type="button" role="menuitem" data-act="human">🙋 Falar com uma pessoa</button>
                <a role="menuitem" id="vc-wa" target="_blank" rel="noopener">💬 Prefere WhatsApp?</a>
                <button type="button" role="menuitem" data-act="reset">↺ Começar nova conversa</button>
            </div>
        </header>

        <div class="vc-body" id="vc-body">
            <ol class="vc-list" id="vc-list" aria-live="polite"></ol>
            <div class="vc-typing" id="vc-typing" hidden aria-label="Bruno está digitando"><span></span><span></span><span></span></div>
            <div class="vc-chips" id="vc-chips"></div>
        </div>

        {{-- Agenda --}}
        <div class="vc-sheet" id="vc-book" hidden>
            <div class="vc-sheet-head">
                <strong>Agendar conversa de 20 min</strong>
                <button type="button" class="vc-link" data-close-sheet>Voltar</button>
            </div>
            <div id="vc-book-step-slots">
                <p class="vc-muted" id="vc-book-loading">Carregando horários…</p>
                <div class="vc-days" id="vc-days" role="tablist" aria-label="Dia"></div>
                <div class="vc-times" id="vc-times" aria-label="Horário"></div>
            </div>
            <form id="vc-book-form" hidden novalidate>
                <p class="vc-picked" id="vc-picked"></p>
                <label>Nome completo<input name="name" autocomplete="name" required maxlength="120"></label>
                <label>E-mail<input name="email" type="email" autocomplete="email" required maxlength="190"></label>
                <label>WhatsApp ou telefone (com DDD)<input name="phone" type="tel" autocomplete="tel" required maxlength="30" placeholder="(16) 99999-9999"></label>
                <p class="vc-error" id="vc-book-error" hidden></p>
                <div class="vc-form-actions">
                    <button type="button" class="vc-link" id="vc-book-back">Trocar horário</button>
                    <button type="submit" class="vc-primary">Confirmar</button>
                </div>
            </form>
        </div>

        {{-- E-mail --}}
        <div class="vc-sheet" id="vc-email" hidden>
            <div class="vc-sheet-head">
                <strong>Receber a conversa por e-mail</strong>
                <button type="button" class="vc-link" data-close-sheet>Voltar</button>
            </div>
            <form id="vc-email-form" novalidate>
                <p class="vc-muted">Mandamos um link para você continuar daqui a pouco ou em outro aparelho. Se a equipe responder, você vê por lá.</p>
                <label>Seu nome (opcional)<input name="name" autocomplete="name" maxlength="120"></label>
                <label>E-mail<input name="email" type="email" autocomplete="email" required maxlength="190"></label>
                <p class="vc-error" id="vc-email-error" hidden></p>
                <div class="vc-form-actions"><span></span><button type="submit" class="vc-primary">Enviar link</button></div>
            </form>
        </div>

        <form class="vc-compose" id="vc-compose" autocomplete="off">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="vc-hp" aria-hidden="true">
            <label class="vc-sr" for="vc-input">Mensagem</label>
            <textarea id="vc-input" rows="1" maxlength="1000" placeholder="Escreva sua mensagem…"></textarea>
            <button type="submit" class="vc-send" id="vc-send" aria-label="Enviar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
        <p class="vc-foot">Assistente com IA · ao conversar você concorda com a <a id="vc-privacy" target="_blank" rel="noopener">Política de Privacidade</a></p>
    </section>
</div>

<style>
.vc-root{--vc-brand:#4f46e5;--vc-brand-d:#4338ca;--vc-ink:#0f172a;--vc-muted:#64748b;--vc-line:#e2e8f0;--vc-soft:#f1f5f9;--vc-bg:#fff;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
.vc-root *{box-sizing:border-box;}
.vc-root [hidden]{display:none !important;}
.vc-sr,.vc-hp{position:absolute !important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;}
.vc-launcher{position:fixed;right:24px;bottom:calc(24px + var(--vc-lift,0px));z-index:10001;display:flex;align-items:center;gap:12px;height:64px;padding:0 24px 0 8px;border:1px solid rgba(255,255,255,.18);border-radius:32px;background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);color:#fff;font-family:inherit;text-align:left;cursor:pointer;box-shadow:0 14px 34px rgba(79,70,229,.45),0 3px 10px rgba(15,23,42,.18),inset 0 1px 0 rgba(255,255,255,.25);transition:transform .2s cubic-bezier(.2,.8,.2,1),box-shadow .2s;animation:vcIn .5s cubic-bezier(.2,.8,.2,1) both;}
.vc-launcher:hover{transform:translateY(-3px);box-shadow:0 20px 44px rgba(79,70,229,.55),0 4px 12px rgba(15,23,42,.2),inset 0 1px 0 rgba(255,255,255,.25);}
.vc-launcher:active{transform:translateY(-1px);}
.vc-launcher-avatar{position:relative;width:48px;height:48px;flex-shrink:0;border-radius:50%;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;box-shadow:inset 0 0 0 1px rgba(255,255,255,.25);}
.vc-launcher-ring{position:absolute;inset:0;border-radius:50%;border:2px solid rgba(255,255,255,.55);animation:vcRing 2.4s ease-out infinite;}
.vc-launcher-dot{position:absolute;right:1px;bottom:1px;width:13px;height:13px;border-radius:50%;background:#22c55e;border:2.5px solid #6d43ea;}
.vc-launcher-text{display:flex;flex-direction:column;gap:3px;line-height:1.1;}
.vc-launcher-title{font-size:1rem;font-weight:800;letter-spacing:-.01em;}
.vc-launcher-sub{font-size:.74rem;font-weight:600;color:#ddd6fe;}
@keyframes vcIn{from{opacity:0;transform:translateY(16px) scale(.96)}to{opacity:1;transform:none}}
@keyframes vcRing{0%{transform:scale(1);opacity:.9}70%{transform:scale(1.55);opacity:0}100%{opacity:0}}
.vc-teaser{position:fixed;right:24px;bottom:calc(104px + var(--vc-lift,0px));z-index:10001;width:300px;padding:16px 16px 14px;background:#fff;border-radius:18px 18px 4px 18px;box-shadow:0 18px 48px rgba(15,23,42,.22),0 2px 8px rgba(15,23,42,.08);color:var(--vc-ink);animation:vcIn .45s cubic-bezier(.2,.8,.2,1) both;}
.vc-teaser::after{content:"";position:absolute;right:34px;bottom:-7px;width:14px;height:14px;background:#fff;transform:rotate(45deg);border-radius:2px;box-shadow:3px 3px 6px rgba(15,23,42,.06);}
.vc-teaser-x{position:absolute;top:8px;right:8px;width:26px;height:26px;border:0;border-radius:8px;background:transparent;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.vc-teaser-x:hover{background:var(--vc-soft);color:#475569;}
.vc-teaser-head{display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--vc-muted);margin-bottom:8px;}
.vc-teaser-head strong{color:var(--vc-ink);}
.vc-mini-avatar{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:800;font-size:.8rem;display:flex;align-items:center;justify-content:center;}
.vc-teaser p{margin:0 0 12px;font-size:.88rem;line-height:1.5;color:#334155;}
.vc-teaser-actions{display:flex;gap:8px;}
.vc-teaser-btn{flex:1;padding:9px 10px;border:1px solid #c7d2fe;border-radius:10px;background:#fff;color:var(--vc-brand-d);font-weight:800;font-size:.8rem;line-height:1;font-family:inherit;cursor:pointer;}
.vc-teaser-btn.primary{background:var(--vc-brand);border-color:var(--vc-brand);color:#fff;}
.vc-teaser-btn:hover{filter:brightness(.97);}
.vc-launcher:focus-visible,.vc-root button:focus-visible,.vc-root a:focus-visible,.vc-root textarea:focus-visible,.vc-root input:focus-visible{outline:3px solid #a5b4fc;outline-offset:2px;}
.vc-badge{position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;padding:0 5px;border-radius:10px;background:#ef4444;color:#fff;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #fff;}
.vc-panel{position:fixed;right:24px;bottom:24px;z-index:10010;width:380px;height:min(620px,calc(100vh - 48px));display:flex;flex-direction:column;background:var(--vc-bg);border-radius:18px;box-shadow:0 24px 64px rgba(15,23,42,.28),0 2px 8px rgba(15,23,42,.08);overflow:hidden;color:var(--vc-ink);}
.vc-head{position:relative;display:flex;align-items:center;gap:10px;padding:14px 12px 14px 16px;background:var(--vc-ink);color:#fff;}
.vc-avatar{position:relative;width:38px;height:38px;flex-shrink:0;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;}
.vc-online{position:absolute;right:0;bottom:0;width:10px;height:10px;border-radius:50%;background:#22c55e;border:2px solid var(--vc-ink);}
.vc-head-text{flex:1;min-width:0;}
.vc-head h2{margin:0;font-size:.95rem;font-weight:800;color:#fff;line-height:1.2;}
.vc-head p{margin:2px 0 0;font-size:.74rem;color:#a5b4fc;}
.vc-icon-btn{width:34px;height:34px;flex-shrink:0;border:0;border-radius:9px;background:transparent;color:#cbd5e1;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.vc-icon-btn:hover{background:rgba(255,255,255,.1);color:#fff;}
.vc-menu{position:absolute;top:56px;right:12px;z-index:3;min-width:250px;padding:6px;background:#fff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.2);display:flex;flex-direction:column;}
.vc-menu button,.vc-menu a{display:block;width:100%;text-align:left;padding:10px 12px;border:0;border-radius:8px;background:none;color:var(--vc-ink);font-weight:600;font-size:.84rem;line-height:1.3;font-family:inherit;cursor:pointer;text-decoration:none;}
.vc-menu button:hover,.vc-menu a:hover{background:var(--vc-soft);}
.vc-body{flex:1;overflow-y:auto;padding:16px 14px 8px;background:#f8fafc;overscroll-behavior:contain;}
.vc-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;}
.vc-msg{max-width:86%;display:flex;flex-direction:column;}
.vc-msg .vc-bubble{padding:9px 12px;border-radius:14px;font-size:.88rem;line-height:1.5;white-space:normal;overflow-wrap:anywhere;}
.vc-msg .vc-meta{font-size:.66rem;color:#94a3b8;margin:3px 6px 0;}
.vc-msg.bot,.vc-msg.agent{align-self:flex-start;}
.vc-msg.bot .vc-bubble{background:#fff;border:1px solid var(--vc-line);border-bottom-left-radius:4px;}
.vc-msg.agent .vc-bubble{background:#ecfdf5;border:1px solid #a7f3d0;border-bottom-left-radius:4px;}
.vc-msg.visitor{align-self:flex-end;align-items:flex-end;}
.vc-msg.visitor .vc-bubble{background:var(--vc-brand);color:#fff;border-bottom-right-radius:4px;}
.vc-msg.visitor .vc-bubble a{color:#fff;}
.vc-msg.system{align-self:center;max-width:94%;text-align:center;}
.vc-msg.system .vc-bubble{background:#eef2ff;color:#3730a3;font-size:.8rem;border-radius:10px;}
.vc-msg.failed .vc-bubble{opacity:.6;}
.vc-bubble a{color:var(--vc-brand);font-weight:600;}
.vc-typing{display:inline-flex;gap:4px;padding:11px 14px;margin-top:8px;background:#fff;border:1px solid var(--vc-line);border-radius:14px;border-bottom-left-radius:4px;}
.vc-typing span{width:7px;height:7px;border-radius:50%;background:#94a3b8;animation:vcDot 1.2s infinite;}
.vc-typing span:nth-child(2){animation-delay:.15s}.vc-typing span:nth-child(3){animation-delay:.3s}
@keyframes vcDot{0%,60%,100%{opacity:.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}
.vc-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;}
.vc-chip{padding:8px 12px;border:1px solid #c7d2fe;border-radius:18px;background:#fff;color:var(--vc-brand-d);font-weight:700;font-size:.8rem;line-height:1.2;font-family:inherit;cursor:pointer;}
.vc-chip:hover{background:#eef2ff;}
.vc-compose{display:flex;align-items:flex-end;gap:8px;padding:10px 12px;border-top:1px solid var(--vc-line);background:#fff;}
.vc-compose textarea{flex:1;resize:none;max-height:120px;min-height:40px;padding:10px 12px;border:1px solid var(--vc-line);border-radius:12px;font-weight:400;font-size:.9rem;line-height:1.4;font-family:inherit;color:var(--vc-ink);background:#fff;}
.vc-send{width:40px;height:40px;flex-shrink:0;border:0;border-radius:12px;background:var(--vc-brand);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.vc-send:disabled{background:#c7d2fe;cursor:default;}
.vc-foot{margin:0;padding:0 12px 10px;background:#fff;font-size:.66rem;color:#94a3b8;text-align:center;}
.vc-foot a{color:#64748b;}
.vc-sheet{position:absolute;left:0;right:0;bottom:0;top:66px;z-index:2;background:#fff;padding:14px 16px;overflow-y:auto;}
.vc-sheet-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-size:.92rem;}
.vc-link{border:0;background:none;color:var(--vc-brand);font-weight:700;font-size:.82rem;line-height:1;font-family:inherit;cursor:pointer;padding:6px 0;}
.vc-muted{font-size:.82rem;color:var(--vc-muted);margin:0 0 12px;line-height:1.5;}
.vc-days{display:flex;gap:6px;overflow-x:auto;padding-bottom:6px;margin-bottom:10px;}
.vc-day{flex-shrink:0;padding:8px 12px;border:1px solid var(--vc-line);border-radius:10px;background:#fff;font-weight:700;font-size:.8rem;line-height:1.2;font-family:inherit;color:#334155;cursor:pointer;}
.vc-day[aria-selected="true"]{background:var(--vc-brand);border-color:var(--vc-brand);color:#fff;}
.vc-times{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;}
.vc-time{padding:9px 0;border:1px solid #c7d2fe;border-radius:9px;background:#fff;color:var(--vc-brand-d);font-weight:700;font-size:.84rem;line-height:1;font-family:inherit;cursor:pointer;font-variant-numeric:tabular-nums;}
.vc-time:hover{background:#eef2ff;}
.vc-sheet form label{display:block;font-size:.76rem;font-weight:700;color:#334155;margin-bottom:10px;}
.vc-sheet form input{display:block;width:100%;margin-top:4px;padding:10px 12px;border:1px solid var(--vc-line);border-radius:10px;font-weight:400;font-size:.9rem;line-height:1.3;font-family:inherit;color:var(--vc-ink);}
.vc-picked{background:#eef2ff;color:#3730a3;font-size:.84rem;font-weight:700;padding:10px 12px;border-radius:10px;margin:0 0 12px;}
.vc-form-actions{display:flex;align-items:center;justify-content:space-between;margin-top:6px;}
.vc-primary{padding:11px 18px;border:0;border-radius:10px;background:var(--vc-brand);color:#fff;font-weight:800;font-size:.86rem;line-height:1;font-family:inherit;cursor:pointer;}
.vc-primary:disabled{opacity:.6;cursor:default;}
.vc-error{color:#b91c1c;font-size:.8rem;margin:0 0 8px;}
@media (max-width:768px){
  .vc-launcher{right:16px;bottom:calc(16px + var(--vc-lift,0px));height:58px;padding:0 18px 0 6px;gap:10px;}
  .vc-launcher-avatar{width:46px;height:46px;}
  .vc-launcher-title{font-size:.94rem;}
  .vc-launcher-sub{font-size:.7rem;}
  .vc-teaser{right:16px;bottom:calc(88px + var(--vc-lift,0px));width:min(300px,calc(100vw - 32px));}
  /* celular: só o ícone redondo, para não cobrir botões e textos */
  .vc-launcher{width:58px;padding:0 6px;justify-content:center;border-radius:50%;}
  .vc-launcher-text{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;}
}
@media (max-width:480px){
  .vc-panel{right:0;bottom:0;width:100%;height:100%;max-height:100dvh;border-radius:0;}
  .vc-compose textarea{font-size:16px;} /* evita zoom no iOS */
}
@media (prefers-reduced-motion:reduce){.vc-typing span,.vc-launcher-ring,.vc-launcher,.vc-teaser{animation:none}.vc-launcher{transition:none}}
</style>

<script>
(function () {
    var root = document.getElementById('vc-root');
    if (!root || window.VivChat) return;

    var $ = function (id) { return document.getElementById(id); };
    var BASE = root.dataset.base, CSRF = root.dataset.csrf;
    var panel = $('vc-panel'), list = $('vc-list'), body = $('vc-body'), typing = $('vc-typing'),
        chips = $('vc-chips'), input = $('vc-input'), sendBtn = $('vc-send'), launcher = $('vc-launcher'),
        badge = $('vc-badge'), sub = $('vc-sub'), menu = $('vc-menu'), menuBtn = $('vc-menu-btn');
    $('vc-wa').href = root.dataset.wa;
    $('vc-privacy').href = root.dataset.privacy;

    // ── Estado ──────────────────────────────────────────────────────────
    var mem = null;
    function load() { try { return JSON.parse(localStorage.getItem('vivchat') || 'null'); } catch (e) { return mem; } }
    function save(v) { mem = v; try { v ? localStorage.setItem('vivchat', JSON.stringify(v)) : localStorage.removeItem('vivchat'); } catch (e) {} }
    var conv = load(), lastId = 0, status = 'bot', botTyping = false, isOpen = false, pollTimer = null,
        unread = 0, seen = {}, hasBooking = false, hasEmail = false, starting = null, booked = null;

    // ── HTTP ────────────────────────────────────────────────────────────
    function api(method, path, data) {
        var headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' };
        if (conv) headers['X-Chat-Token'] = conv.token;
        if (data) headers['Content-Type'] = 'application/json';
        return fetch(BASE + path, { method: method, headers: headers, body: data ? JSON.stringify(data) : undefined, credentials: 'same-origin' })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (j) {
                    if (r.status === 404 && conv && path.indexOf('/' + conv.id) === 0) { save(null); conv = null; }
                    if (!r.ok) {
                        var msg = j.error || (j.errors && Object.values(j.errors)[0][0]) ||
                            (r.status === 429 ? 'Muitas mensagens seguidas. Espere um pouquinho.' :
                             r.status === 419 ? 'A página ficou aberta por muito tempo. Recarregue a página para continuar a conversa.' :
                             'Não foi possível concluir. Tente de novo.');
                        var err = new Error(msg); err.status = r.status; throw err;
                    }
                    return j;
                });
            });
    }
    function ensureStarted() {
        if (conv) return Promise.resolve();
        if (starting) return starting;
        var hp = panel.querySelector('.vc-hp');
        starting = api('POST', '/iniciar', {
            page_url: location.href.slice(0, 1000), page_title: document.title.slice(0, 300),
            referrer: document.referrer.slice(0, 1000), website: hp ? hp.value : ''
        }).then(function (j) {
            conv = j.conversation; save(conv);
            list.innerHTML = ''; seen = {}; lastId = 0;
            apply(j);
        }).finally(function () { starting = null; });
        return starting;
    }

    // ── Render ──────────────────────────────────────────────────────────
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function fmt(text) {
        var h = esc(text);
        h = h.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
        h = h.replace(/(https?:\/\/[^\s<]+[^\s<.,;:!?)\]])/g, function (u) { return '<a href="' + u + '" target="_blank" rel="noopener nofollow">' + u.replace(/^https?:\/\//, '').slice(0, 48) + (u.length > 56 ? '…' : '') + '</a>'; });
        return h.replace(/\n/g, '<br>');
    }
    function addMsg(m) {
        if (m.id && seen[m.id]) return;
        if (m.id) { seen[m.id] = 1; lastId = Math.max(lastId, m.id); }
        var li = document.createElement('li');
        li.className = 'vc-msg ' + m.sender;
        li.innerHTML = '<div class="vc-bubble">' + fmt(m.body) + '</div>' +
            (m.sender !== 'system' && (m.name || m.at) ? '<div class="vc-meta">' + esc([m.sender === 'visitor' ? '' : (m.name || ''), m.at || ''].filter(Boolean).join(' · ')) + '</div>' : '');
        list.appendChild(li);
        if (!isOpen && (m.sender === 'bot' || m.sender === 'agent') && m.kind !== 'greeting') { unread++; showBadge(); }
        return li;
    }
    function scrollDown() { body.scrollTop = body.scrollHeight; }
    function showBadge() { if (badge) { badge.textContent = unread; badge.hidden = unread === 0; } }
    function renderChips() {
        chips.innerHTML = '';
        var visitorSpoke = list.querySelector('.vc-msg.visitor');
        var opts = [];
        if (!visitorSpoke) {
            opts = [['Quais são os planos?', 'text'], ['Agendar uma conversa', 'book'], ['Como funciona para ONGs?', 'text'], ['Falar com uma pessoa', 'human']];
        } else if (status === 'bot' && !hasBooking) {
            opts = [['Agendar uma conversa', 'book']];
        }
        opts.forEach(function (o) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'vc-chip'; b.textContent = o[0];
            b.onclick = function () { o[1] === 'book' ? openSheet('book') : o[1] === 'human' ? askHuman() : sendText(o[0]); };
            chips.appendChild(b);
        });
    }
    function apply(j) {
        (j.messages || []).forEach(addMsg);
        status = j.status || status; botTyping = !!j.bot_typing;
        hasBooking = !!j.has_booking; hasEmail = !!j.has_email;
        typing.hidden = !botTyping;
        sub.textContent = status === 'human' ? 'Equipe Vivensi na conversa' : status === 'waiting_human' ? 'Chamando a equipe…' : 'Responde na hora';
        renderChips();
        if (isOpen) scrollDown();
        schedulePoll();
    }
    function localGreeting() {
        if (list.children.length) return;
        addMsg({ sender: 'bot', name: 'Bruno', body: root.dataset.greeting, kind: 'greeting' });
        renderChips();
    }

    // ── Polling ─────────────────────────────────────────────────────────
    function schedulePoll() {
        clearTimeout(pollTimer);
        if (!conv) return;
        var waitingTeam = status === 'human' || status === 'waiting_human';
        var delay = isOpen ? (botTyping ? 2000 : waitingTeam ? 3000 : 10000) : (waitingTeam || botTyping ? 15000 : 0);
        if (!delay || document.hidden && !botTyping) return;
        pollTimer = setTimeout(poll, delay);
    }
    function poll() {
        if (!conv) return;
        api('GET', '/' + conv.id + '/mensagens?depois=' + lastId).then(apply).catch(function (e) { if (e.status !== 404) schedulePoll(); });
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden && conv) poll(); });

    // ── Ações ───────────────────────────────────────────────────────────
    function notice(text) { addMsg({ sender: 'system', body: text }); scrollDown(); }
    function sendText(text) {
        text = (text || '').trim();
        if (!text) return;
        var temp = addMsg({ sender: 'visitor', body: text });
        typing.hidden = false; chips.innerHTML = ''; scrollDown();
        sendBtn.disabled = true;
        ensureStarted().then(function () {
            return api('POST', '/' + conv.id + '/mensagens', { body: text, depois: lastId });
        }).then(function (j) {
            if (temp) temp.remove();
            apply(j);
        }).catch(function (e) {
            if (temp) temp.classList.add('failed');
            typing.hidden = true; notice(e.message);
        }).finally(function () { sendBtn.disabled = false; });
    }
    function askHuman() {
        closeMenu();
        ensureStarted().then(function () { return api('POST', '/' + conv.id + '/humano', { depois: lastId }); })
            .then(apply).catch(function (e) { notice(e.message); });
    }

    // Agenda
    var sheets = { book: $('vc-book'), email: $('vc-email') };
    function openSheet(name) {
        closeMenu();
        if (name === 'book' && hasBooking) { notice('Você já tem uma reunião marcada. Para remarcar, use o link do e-mail de confirmação.'); return; }
        Object.keys(sheets).forEach(function (k) { sheets[k].hidden = k !== name; });
        if (name === 'book') loadSlots();
        if (name === 'email') { setTimeout(function () { sheets.email.querySelector('input[name=email]').focus(); }, 30); }
    }
    function closeSheets() { Object.keys(sheets).forEach(function (k) { sheets[k].hidden = true; }); input.focus(); }
    panel.querySelectorAll('[data-close-sheet]').forEach(function (b) { b.onclick = closeSheets; });

    function loadSlots() {
        var daysEl = $('vc-days'), timesEl = $('vc-times'), loading = $('vc-book-loading');
        $('vc-book-step-slots').hidden = false; $('vc-book-form').hidden = true;
        daysEl.innerHTML = ''; timesEl.innerHTML = ''; loading.hidden = false; loading.textContent = 'Carregando horários…';
        ensureStarted().then(function () { return api('GET', '/' + conv.id + '/horarios'); }).then(function (j) {
            var days = j.days || [];
            if (!days.length) { loading.textContent = 'Sem horários livres nos próximos dias. Fale com a equipe pelo menu ⋮.'; return; }
            loading.hidden = true;
            days.forEach(function (d, i) {
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'vc-day'; b.setAttribute('role', 'tab'); b.textContent = d.label;
                b.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
                b.onclick = function () { daysEl.querySelectorAll('.vc-day').forEach(function (x) { x.setAttribute('aria-selected', 'false'); }); b.setAttribute('aria-selected', 'true'); showTimes(d); };
                daysEl.appendChild(b);
            });
            showTimes(days[0]);
        }).catch(function (e) { loading.textContent = e.message; });
    }
    function showTimes(d) {
        var timesEl = $('vc-times'); timesEl.innerHTML = '';
        d.slots.forEach(function (t) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'vc-time'; b.textContent = t;
            b.onclick = function () { pick(d, t); };
            timesEl.appendChild(b);
        });
    }
    function pick(d, t) {
        booked = { date: d.date, time: t };
        $('vc-picked').textContent = '📅 ' + d.label + ' às ' + t + ' · 20 minutos';
        $('vc-book-step-slots').hidden = true; $('vc-book-form').hidden = false; $('vc-book-error').hidden = true;
        $('vc-book-form').querySelector('input[name=name]').focus();
    }
    $('vc-book-back').onclick = function () { $('vc-book-step-slots').hidden = false; $('vc-book-form').hidden = true; };
    $('vc-book-form').addEventListener('submit', function (ev) {
        ev.preventDefault();
        var f = ev.target, err = $('vc-book-error'), btn = f.querySelector('.vc-primary');
        var data = { name: f.name.value.trim(), email: f.email.value.trim(), phone: f.phone.value.trim(), date: booked.date, time: booked.time, depois: lastId };
        if (data.name.length < 3 || !/^\S+@\S+\.\S+$/.test(data.email) || data.phone.replace(/\D/g, '').length < 10) {
            err.textContent = 'Preencha nome, e-mail e telefone com DDD.'; err.hidden = false; return;
        }
        btn.disabled = true; err.hidden = true;
        api('POST', '/' + conv.id + '/agendar', data).then(function (j) { closeSheets(); apply(j); })
            .catch(function (e) { err.textContent = e.message; err.hidden = false; if (e.status === 422 && /dispon/i.test(e.message)) loadSlots(); })
            .finally(function () { btn.disabled = false; });
    });

    // E-mail
    $('vc-email-form').addEventListener('submit', function (ev) {
        ev.preventDefault();
        var f = ev.target, err = $('vc-email-error'), btn = f.querySelector('.vc-primary');
        var email = f.email.value.trim();
        if (!/^\S+@\S+\.\S+$/.test(email)) { err.textContent = 'Informe um e-mail válido.'; err.hidden = false; return; }
        btn.disabled = true; err.hidden = true;
        ensureStarted().then(function () { return api('POST', '/' + conv.id + '/link-email', { email: email, name: f.name.value.trim(), depois: lastId }); })
            .then(function (j) { closeSheets(); apply(j); })
            .catch(function (e) { err.textContent = e.message; err.hidden = false; })
            .finally(function () { btn.disabled = false; });
    });

    // Menu
    function closeMenu() { menu.hidden = true; menuBtn.setAttribute('aria-expanded', 'false'); }
    menuBtn.onclick = function (e) { e.stopPropagation(); menu.hidden = !menu.hidden; menuBtn.setAttribute('aria-expanded', String(!menu.hidden)); };
    document.addEventListener('click', function (e) { if (!menu.hidden && !menu.contains(e.target)) closeMenu(); });
    menu.querySelectorAll('[data-act]').forEach(function (b) {
        b.onclick = function () {
            var a = b.dataset.act;
            if (a === 'book') openSheet('book');
            else if (a === 'email') openSheet('email');
            else if (a === 'human') askHuman();
            else if (a === 'reset') { closeMenu(); save(null); conv = null; list.innerHTML = ''; seen = {}; lastId = 0; status = 'bot'; hasBooking = false; typing.hidden = true; localGreeting(); }
        };
    });

    // Composer
    $('vc-compose').addEventListener('submit', function (e) { e.preventDefault(); var t = input.value; input.value = ''; autosize(); sendText(t); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('vc-compose').requestSubmit ? $('vc-compose').requestSubmit() : $('vc-compose').dispatchEvent(new Event('submit')); } });
    function autosize() { input.style.height = 'auto'; input.style.height = Math.min(120, input.scrollHeight) + 'px'; }
    input.addEventListener('input', autosize);

    // Abrir / fechar
    function open(mode) {
        isOpen = true; panel.hidden = false;
        if (launcher) { launcher.hidden = true; launcher.setAttribute('aria-expanded', 'true'); }
        hideTeaser(true);
        unread = 0; showBadge();
        if (conv && !lastId) {
            api('GET', '/' + conv.id + '/mensagens?depois=0').then(apply).catch(function () { localGreeting(); });
        } else { localGreeting(); schedulePoll(); }
        if (mode === 'agendar') openSheet('book'); else setTimeout(function () { input.focus(); }, 50);
        scrollDown();
    }
    function close() {
        isOpen = false; panel.hidden = true; closeMenu();
        if (launcher) { launcher.hidden = false; launcher.setAttribute('aria-expanded', 'false'); launcher.focus(); }
        schedulePoll();
    }
    if (launcher) launcher.onclick = function () { open(); };

    // Aviso de cookies na tela: o botão e o balão sobem para não cobrir o "Aceitar"
    function liftAboveCookies() {
        var cb = document.getElementById('cookieBanner'), lift = 0;
        if (cb && cb.getClientRects().length && getComputedStyle(cb).display !== 'none' && getComputedStyle(cb).visibility !== 'hidden') {
            lift = Math.ceil(cb.getBoundingClientRect().height) + 12;
        }
        root.style.setProperty('--vc-lift', lift + 'px');
    }
    liftAboveCookies();
    window.addEventListener('resize', liftAboveCookies);
    if (window.MutationObserver) {
        new MutationObserver(liftAboveCookies).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class', 'hidden'] });
    }

    // Balão de boas-vindas: 5 s após entrar, no máximo 1x por dia, nunca com conversa em andamento
    var teaser = $('vc-teaser'), teaserTimer = null, TEASER_KEY = 'vivchat_teaser_until';
    function hideTeaser(remember) {
        clearTimeout(teaserTimer);
        if (teaser) teaser.hidden = true;
        if (remember) { try { localStorage.setItem(TEASER_KEY, String(Date.now() + 86400000)); } catch (e) {} }
    }
    if (teaser && !conv) {
        var until = 0;
        try { until = parseInt(localStorage.getItem(TEASER_KEY) || '0', 10); } catch (e) {}
        if (Date.now() > until) {
            // 25s: a pessoa lê a página antes do convite (antes: 5s, cobria o topo)
            teaserTimer = setTimeout(function () { if (!isOpen) teaser.hidden = false; }, 25000);
        }
        $('vc-teaser-x').onclick = function () { hideTeaser(true); };
    }
    $('vc-close').onclick = close;
    panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { menu.hidden ? close() : closeMenu(); } });
    document.addEventListener('click', function (e) {
        var t = e.target.closest && e.target.closest('[data-vivchat]');
        if (t) { e.preventDefault(); open(t.dataset.vivchat || undefined); }
    });

    window.VivChat = { open: open, close: close };

    // Conversa em andamento com a equipe: segue avisando mesmo com o chat fechado
    if (conv) {
        api('GET', '/' + conv.id + '/mensagens?depois=0').then(function (j) { unread = 0; apply(j); unread = 0; showBadge(); }).catch(function () {});
    }
    if (/[?&]chat=1\b/.test(location.search)) setTimeout(function () { open(); }, 300);
})();
</script>
