{{--
    Modal + botao "Gerar template com IA" (DeepSeek).
    Params opcionais:
      - $brandColor       cor primaria do botao (default: #6366f1)
      - $senderNameFallback nome do remetente pre-preenchido (default: nome do tenant)

    Requisitos:
      - O partial deve estar dentro da view onde existe <textarea id="htmlContent">
        e <input name="subject"> (o preenchimento e feito por seletor).
      - Rota nomeada: email_campaigns.ai.generate e email_campaigns.ai.quota
--}}
@php
    $brandColor = $brandColor ?? '#6366f1';
    $senderNameFallback = $senderNameFallback ?? (auth()->user()->tenant->name ?? 'Sua empresa');
@endphp

<button type="button" onclick="openAiModal()"
        style="padding:8px 16px; border-radius:10px; border:none; background:linear-gradient(135deg,{{ $brandColor }},#8b5cf6); color:white; font-weight:700; font-size:0.8rem; cursor:pointer; box-shadow:0 2px 6px rgba(99,102,241,0.25); display:inline-flex; align-items:center; gap:8px;">
    <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce" style="width:20px; height:20px; border-radius:50%; background:rgba(255,255,255,0.15); padding:1px;">
    Gerar com Bruce
</button>

<div id="aiModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto;">
    <div style="background:white; border-radius:20px; width:100%; max-width:720px; box-shadow:0 20px 60px rgba(0,0,0,0.25); overflow:hidden;">
        <div style="background:linear-gradient(135deg,#1e1b4b,#4f46e5); padding:22px 28px; color:white; display:flex; align-items:center; justify-content:space-between; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <div style="width:52px; height:52px; border-radius:50%; background:white; padding:4px; box-shadow:0 4px 12px rgba(0,0,0,0.25); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                    <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA" style="width:100%; height:100%; object-fit:contain;">
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:900; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                        Bruce IA <span style="font-size:1rem;">🐶</span>
                        <span style="font-size:0.62rem; font-weight:800; background:rgba(16,185,129,0.25); color:#a7f3d0; padding:2px 8px; border-radius:10px; letter-spacing:0.5px; text-transform:uppercase;">Online</span>
                    </div>
                    <div style="font-size:0.8rem; opacity:0.85; margin-top:2px;">Seu redator de e-mail marketing</div>
                </div>
            </div>
            <button type="button" onclick="closeAiModal()" style="background:rgba(255,255,255,0.18); border:none; color:white; width:32px; height:32px; border-radius:10px; cursor:pointer; font-size:1rem; flex-shrink:0;">&times;</button>
        </div>

        <div style="padding:24px 28px;">
            <div id="aiQuotaBadge" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; border-radius:10px; padding:10px 14px; font-size:0.82rem; font-weight:700; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-gauge-high"></i>
                <span id="aiQuotaText">Carregando cota mensal...</span>
            </div>

            <div id="aiFormPane">
                <div style="background:#f8fafc; border-left:3px solid #6366f1; padding:10px 14px; border-radius:6px; margin-bottom:16px; font-size:0.82rem; color:#475569; line-height:1.55;">
                    <strong style="color:#4f46e5;">Bruce:</strong> Me conta o que voce quer comunicar e para quem. Quanto mais detalhe, melhor o e-mail que eu escrevo pra voce.
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#1e293b; margin-bottom:6px;">O que voce quer comunicar? *</label>
                    <textarea id="aiBrief" rows="4" placeholder="Ex: Convidar doadores ativos para um evento beneficente presencial no dia 15/08. Destacar que sera uma noite com jantar, palestras de impacto social e homenagem aos maiores doadores do ano."
                              style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.88rem; box-sizing:border-box; resize:vertical; line-height:1.55;"></textarea>
                    <div style="font-size:0.72rem; color:#94a3b8; margin-top:4px;">Minimo 15 caracteres. Quanto mais especifico, melhor.</div>
                </div>

                <div class="row g-3" style="margin-bottom:14px;">
                    <div class="col-md-6">
                        <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Tom da comunicacao</label>
                        <select id="aiTone" style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.85rem; box-sizing:border-box; background:white;">
                            <option value="profissional e acolhedor">Profissional e acolhedor</option>
                            <option value="casual e amigavel">Casual e amigavel</option>
                            <option value="urgente e direto">Urgente e direto</option>
                            <option value="emotivo e inspirador">Emotivo e inspirador</option>
                            <option value="formal corporativo">Formal corporativo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Publico-alvo</label>
                        <input type="text" id="aiAudience" placeholder="Ex: doadores ativos, leads recentes"
                               style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.85rem; box-sizing:border-box;">
                    </div>
                    <div class="col-md-6">
                        <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">CTA principal</label>
                        <input type="text" id="aiCta" placeholder="Ex: Confirmar presenca"
                               style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.85rem; box-sizing:border-box;">
                    </div>
                    <div class="col-md-4">
                        <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Cor da marca</label>
                        <input type="color" id="aiBrandColor" value="{{ $brandColor }}"
                               style="width:100%; height:44px; padding:4px; border:2px solid #e2e8f0; border-radius:10px; box-sizing:border-box; cursor:pointer;">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <input type="hidden" id="aiSenderName" value="{{ $senderNameFallback }}">
                    </div>
                </div>

                <div id="aiError" style="display:none; background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; border-radius:10px; padding:10px 14px; font-size:0.82rem; margin-bottom:14px;"></div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeAiModal()"
                            style="padding:11px 20px; border-radius:10px; border:2px solid #e2e8f0; background:white; color:#64748b; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancelar</button>
                    <button type="button" id="aiGenerateBtn" onclick="submitAiGeneration()"
                            style="padding:11px 24px; border-radius:10px; border:none; background:linear-gradient(135deg,#4f46e5,#6366f1); color:white; font-weight:800; font-size:0.85rem; cursor:pointer; box-shadow:0 4px 12px rgba(79,70,229,0.4); display:inline-flex; align-items:center; gap:8px;">
                        <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="" style="width:18px; height:18px; border-radius:50%; background:rgba(255,255,255,0.15); padding:1px;">
                        Bruce, escreve pra mim
                    </button>
                </div>
            </div>

            <div id="aiLoadingPane" style="display:none; text-align:center; padding:40px 20px;">
                <div style="position:relative; display:inline-block; width:80px; height:80px;">
                    <div style="position:absolute; inset:0; border:4px solid #e2e8f0; border-top-color:#4f46e5; border-radius:50%; animation:aiSpin 0.9s linear infinite;"></div>
                    <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce" style="position:absolute; inset:12px; width:56px; height:56px; border-radius:50%;">
                </div>
                <div style="margin-top:18px; font-weight:800; color:#1e293b; font-size:0.95rem;">Bruce esta escrevendo seu e-mail<span id="aiLoadingDots">...</span></div>
                <div style="margin-top:4px; color:#94a3b8; font-size:0.78rem;">Costuma levar de 10 a 30 segundos.</div>
            </div>

            <div id="aiResultPane" style="display:none;">
                <div style="background:#f0fdf4; border:1px solid #86efac; color:#166534; border-radius:10px; padding:10px 14px; font-size:0.85rem; font-weight:700; margin-bottom:14px; display:flex; align-items:center; gap:10px;">
                    <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce" style="width:24px; height:24px; border-radius:50%; flex-shrink:0;">
                    <span><strong>Bruce:</strong> Prontinho! Da uma olhada no preview e me diz se posso usar.</span>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Assunto sugerido</label>
                    <input type="text" id="aiResultSubject" readonly
                           style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.88rem; box-sizing:border-box; background:#f8fafc;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Preheader</label>
                    <input type="text" id="aiResultPreheader" readonly
                           style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.82rem; box-sizing:border-box; background:#f8fafc;">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-weight:700; font-size:0.82rem; color:#1e293b; margin-bottom:6px;">Preview HTML</label>
                    <iframe id="aiResultPreview" style="width:100%; height:360px; border:2px solid #e2e8f0; border-radius:10px; background:white;"></iframe>
                </div>
                <div style="display:flex; gap:10px; justify-content:space-between;">
                    <button type="button" onclick="backToAiForm()"
                            style="padding:11px 18px; border-radius:10px; border:2px solid #e2e8f0; background:white; color:#64748b; font-weight:700; font-size:0.85rem; cursor:pointer;">
                        <i class="fas fa-arrow-left me-1"></i>Bruce, tenta outro
                    </button>
                    <button type="button" onclick="applyAiTemplate()"
                            style="padding:11px 24px; border-radius:10px; border:none; background:#059669; color:white; font-weight:800; font-size:0.85rem; cursor:pointer;">
                        <i class="fas fa-check me-1"></i>Usar este e-mail
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes aiSpin { to { transform: rotate(360deg); } }
</style>

<script>
(function() {
    const modal        = document.getElementById('aiModal');

    // IMPORTANTE: reparent para o <body> pra escapar de qualquer ancestor com
    // transform/filter/perspective (ex.: .vivensi-card:hover tem
    // transform:translateY(-2px), o que quebra position:fixed do modal e faz
    // ele "descer" pra dentro do card quando o mouse entra).
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const formPane     = document.getElementById('aiFormPane');
    const loadingPane  = document.getElementById('aiLoadingPane');
    const resultPane   = document.getElementById('aiResultPane');
    const errorBox     = document.getElementById('aiError');
    const quotaText    = document.getElementById('aiQuotaText');
    const generateBtn  = document.getElementById('aiGenerateBtn');
    const loadingDots  = document.getElementById('aiLoadingDots');
    // Paths hardcoded (nao usa route() helper) — se o cache de rotas em prod
    // ainda nao pegou a rota nova, route() lanca RouteNotFoundException no
    // render e quebra a pagina inteira com 500. Path fixo e imune a isso.
    const quotaUrl     = '/email-campaigns/ai/quota';
    const generateUrl  = '/email-campaigns/ai/generate';
    const csrf         = document.querySelector('meta[name="csrf-token"]')?.content
                       || document.querySelector('input[name="_token"]')?.value
                       || '';
    let lastResult     = null;
    let dotsTimer      = null;

    function showPane(pane) {
        formPane.style.display    = pane === 'form'    ? 'block' : 'none';
        loadingPane.style.display = pane === 'loading' ? 'block' : 'none';
        resultPane.style.display  = pane === 'result'  ? 'block' : 'none';

        if (dotsTimer) { clearInterval(dotsTimer); dotsTimer = null; }
        if (pane === 'loading' && loadingDots) {
            let n = 0;
            loadingDots.textContent = '';
            dotsTimer = setInterval(() => {
                n = (n + 1) % 4;
                loadingDots.textContent = '.'.repeat(n);
            }, 400);
        }
    }

    function renderQuota(used, limit, remaining) {
        if (remaining <= 0) {
            quotaText.textContent = `Cota mensal esgotada (${used}/${limit}). Reinicia dia 1 do proximo mes.`;
            quotaText.parentElement.style.background = '#fef2f2';
            quotaText.parentElement.style.borderColor = '#fca5a5';
            quotaText.parentElement.style.color = '#991b1b';
            generateBtn.disabled = true;
            generateBtn.style.opacity = '0.5';
            generateBtn.style.cursor = 'not-allowed';
        } else {
            quotaText.textContent = `${remaining} de ${limit} gerações restantes este mes.`;
            quotaText.parentElement.style.background = '#eff6ff';
            quotaText.parentElement.style.borderColor = '#bfdbfe';
            quotaText.parentElement.style.color = '#1d4ed8';
            generateBtn.disabled = false;
            generateBtn.style.opacity = '1';
            generateBtn.style.cursor = 'pointer';
        }
    }

    window.openAiModal = function() {
        errorBox.style.display = 'none';
        showPane('form');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        fetch(quotaUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => renderQuota(d.used || 0, d.limit || 10, d.remaining || 0))
            .catch(() => { quotaText.textContent = 'Nao foi possivel carregar a cota agora.'; });
    };

    window.closeAiModal = function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.backToAiForm = function() {
        errorBox.style.display = 'none';
        showPane('form');
    };

    window.submitAiGeneration = function() {
        errorBox.style.display = 'none';
        const brief = document.getElementById('aiBrief').value.trim();
        if (brief.length < 15) {
            errorBox.textContent = 'Descreva a campanha com pelo menos 15 caracteres.';
            errorBox.style.display = 'block';
            return;
        }

        const payload = {
            brief:       brief,
            tone:        document.getElementById('aiTone').value,
            audience:    document.getElementById('aiAudience').value.trim(),
            cta:         document.getElementById('aiCta').value.trim(),
            sender_name: document.getElementById('aiSenderName').value.trim(),
            brand_color: document.getElementById('aiBrandColor').value,
        };

        showPane('loading');

        fetch(generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        })
        .then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                const msg = data.error || `Erro ${r.status}. Tente novamente.`;
                if (typeof data.remaining === 'number') renderQuota(0, data.limit || 10, data.remaining);
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            lastResult = data;
            document.getElementById('aiResultSubject').value   = data.subject || '';
            document.getElementById('aiResultPreheader').value = data.preheader || '';
            document.getElementById('aiResultPreview').srcdoc  = data.html || '';
            renderQuota((data.limit || 10) - (data.remaining || 0), data.limit || 10, data.remaining || 0);
            showPane('result');
        })
        .catch(err => {
            errorBox.textContent = err.message || 'Falha inesperada. Tente novamente.';
            errorBox.style.display = 'block';
            showPane('form');
        });
    };

    window.applyAiTemplate = function() {
        if (!lastResult) return;
        const subjectInput = document.querySelector('input[name="subject"]');
        const htmlTextarea = document.getElementById('htmlContent');
        if (subjectInput && lastResult.subject) subjectInput.value = lastResult.subject;
        if (htmlTextarea && lastResult.html) htmlTextarea.value = lastResult.html;
        closeAiModal();

        // Feedback visual leve
        if (htmlTextarea) {
            htmlTextarea.style.transition = 'background-color 0.6s';
            htmlTextarea.style.backgroundColor = '#f0fdf4';
            setTimeout(() => { htmlTextarea.style.backgroundColor = ''; }, 1200);
        }
    };

    // Fecha modal ao clicar no backdrop
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeAiModal();
    });
})();
</script>
