@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    #audit-wrap {
        background: #0a0e1a;
        color: #e2e8f0;
        font-family: 'IBM Plex Mono', 'Fira Code', 'Courier New', monospace;
        font-size: 13px;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 120px);
        min-height: 600px;
    }
    #audit-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 1px solid #1e3a5f;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    #progress-track { height: 3px; background: #1e293b; flex-shrink: 0; }
    #progress-bar   { height: 100%; width: 0%; background: linear-gradient(90deg, #2563eb, #60a5fa); transition: width .5s ease; }
    #summary-bar    { display: none; background: #0f172a; border-bottom: 1px solid #1e293b; padding: 8px 24px; flex-shrink: 0; flex-wrap: wrap; gap: 20px; align-items: center; }
    #audit-body     { display: flex; flex: 1; overflow: hidden; }
    #audit-sidebar  { width: 204px; background: #0d1626; border-right: 1px solid #1e293b; overflow-y: auto; flex-shrink: 0; }
    #audit-main     { flex: 1; overflow-y: auto; padding: 20px; }

    .mod-item {
        padding: 10px 16px;
        cursor: pointer;
        border-left: 3px solid transparent;
        transition: all .15s;
    }
    .mod-item.active { background: #0f172a; }
    .mod-item:hover  { background: #0f1f38; }

    .mod-audit-btn {
        margin-top: 6px; width: 100%; padding: 3px 0;
        border-radius: 3px; font-size: 9px; cursor: pointer;
        font-family: inherit; background: transparent;
        transition: opacity .15s;
    }
    .mod-audit-btn:disabled { opacity: .4; cursor: not-allowed; }

    #full-audit-btn {
        width: 100%; padding: 8px 0;
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        border: none; border-radius: 4px; color: #fff;
        font-size: 10px; font-weight: 700; cursor: pointer;
        font-family: inherit; letter-spacing: .5px;
    }
    #stop-btn {
        width: 100%; padding: 8px 0;
        background: #1e293b; border: 1px solid rgba(239,68,68,.27);
        border-radius: 4px; color: #ef4444;
        font-size: 10px; font-weight: 700; cursor: pointer;
        font-family: inherit;
    }

    .check-card { background: #0d1626; border: 1px solid #1e293b; border-radius: 6px; margin-bottom: 8px; overflow: hidden; transition: border-color .15s; }
    .check-card.expanded { border-color: rgba(255,255,255,.12); }
    .check-header { padding: 10px 14px; display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .check-body   { border-top: 1px solid #1e293b; padding: 14px; }

    .run-btn {
        padding: 3px 8px; background: #1e3a5f;
        border: 1px solid #2563eb; border-radius: 3px;
        color: #60a5fa; font-size: 9px; cursor: pointer;
        font-family: inherit;
    }
    .run-btn:disabled { background: #1e293b; border-color: #1e293b; color: #475569; cursor: not-allowed; }

    .issue-row { display: flex; gap: 8px; margin-bottom: 4px; padding: 4px 8px; border-radius: 0 3px 3px 0; }
    .analysis-pre { color: #64748b; font-size: 10px; line-height: 1.6; white-space: pre-wrap; max-height: 300px; overflow-y: auto; background: #0a0e1a; padding: 10px; border-radius: 4px; border: 1px solid #1e293b; font-family: inherit; }

    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: #0a0e1a; }
    ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 2px; }

    @keyframes pulse { 0%,100%{ opacity:1; } 50%{ opacity:.3; } }
    @keyframes spin  { from{ transform:rotate(0deg); } to{ transform:rotate(360deg); } }
    .anim-pulse { animation: pulse .8s ease-in-out infinite; }
    .anim-spin  { animation: spin 1s linear infinite; }
</style>

<div id="audit-wrap">

    {{-- Header --}}
    <div id="audit-header">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <span style="font-size:22px;">🔍</span>
                <span style="font-size:18px;font-weight:700;color:#60a5fa;letter-spacing:1px;">VIVENSI AUDIT AGENT</span>
                <span style="background:#1e3a5f;color:#93c5fd;font-size:10px;padding:2px 8px;border-radius:4px;border:1px solid #2563eb;">v1.0 PRE-LAUNCH</span>
            </div>
            <div style="color:#64748b;font-size:11px;">Super checagem antes das vendas de assinatura · 16 verificações · 5 módulos</div>
        </div>
        <div style="text-align:right;">
            <div id="global-pct" style="font-size:28px;font-weight:700;color:#60a5fa;">0%</div>
            <div id="global-count" style="color:#64748b;font-size:10px;">0/16 concluídas</div>
        </div>
    </div>

    {{-- Progress bar --}}
    <div id="progress-track"><div id="progress-bar"></div></div>

    {{-- Summary bar --}}
    <div id="summary-bar">
        <span style="color:#64748b;font-size:11px;">PROBLEMAS ENCONTRADOS:</span>
        <span id="sev-alta"  style="display:none;color:#ef4444;font-size:11px;font-weight:700;"></span>
        <span id="sev-media" style="display:none;color:#f59e0b;font-size:11px;font-weight:700;"></span>
        <span id="sev-baixa" style="display:none;color:#22c55e;font-size:11px;font-weight:700;"></span>
        <span id="sev-info"  style="display:none;color:#6b7280;font-size:11px;font-weight:700;"></span>
        <span id="sev-ok"    style="color:#22c55e;font-size:11px;">✓ Nenhum problema detectado ainda</span>
    </div>

    {{-- Body --}}
    <div id="audit-body">
        <div id="audit-sidebar"></div>
        <div id="audit-main"></div>
    </div>
</div>

<script>
// ── Data ──────────────────────────────────────────────────────────────────────
const AUDIT_MODULES = [
  { id:'security',    label:'Segurança',      icon:'🔒', color:'#ef4444', checks:[
    { id:'s1', label:'Variáveis de ambiente (.env) não expostas no repositório',
      prompt:'Audite o uso de variáveis de ambiente no projeto Laravel. Verifique se .env está no .gitignore, se há chaves hardcoded no código, se APP_KEY está configurado corretamente, se há secrets expostos em logs ou responses. Liste todos os problemas encontrados com severidade Alta/Média/Baixa.' },
    { id:'s2', label:'Proteção contra SQL Injection e XSS',
      prompt:'Audite o código Laravel buscando vulnerabilidades de SQL Injection e XSS. Verifique uso de Eloquent ORM vs raw queries, sanitização de inputs, uso de {{ }} vs {!! !!} no Blade, validação de dados nos FormRequests. Liste problemas com exemplos de código.' },
    { id:'s3', label:'Autenticação e autorização (Gates/Policies)',
      prompt:'Audite o sistema de autenticação e autorização Laravel. Verifique se todas as rotas sensíveis têm middleware auth, se Policies estão definidas para todos os models críticos, se há Mass Assignment desprotegido nos models, se CSRF está ativo nos formulários. Liste falhas encontradas.' },
    { id:'s4', label:'Segurança dos pagamentos PIX / OpenPix',
      prompt:'Audite a integração de pagamentos PIX/OpenPix no Vivensi. Verifique: validação de webhooks com assinatura/token, idempotência nas cobranças, proteção contra double-spending, logs de transações, tratamento de falhas. Este é componente financeiro crítico.' },
    { id:'s5', label:'Proteção de dados sensíveis (LGPD)',
      prompt:'Audite a conformidade com LGPD no sistema Vivensi (ONGs brasileiras). Verifique: dados pessoais criptografados em repouso, política de retenção de dados, logs de acesso a dados sensíveis, consentimento de usuários, possibilidade de exclusão de dados (direito ao esquecimento).' },
  ]},
  { id:'performance', label:'Performance',    icon:'⚡', color:'#f59e0b', checks:[
    { id:'p1', label:'Queries N+1 e otimização Eloquent',
      prompt:'Audite o código Laravel buscando problemas de N+1 queries. Analise relacionamentos Eloquent sem eager loading, loops com queries dentro, uso de lazy loading desnecessário. Sugira correções com with(), load() e query optimization. Foque nos módulos de relatórios e listagens.' },
    { id:'p2', label:'Cache Redis (ElastiCache) e otimização',
      prompt:'Audite o uso de cache Redis no projeto Laravel. Verifique: quais dados estão sendo cacheados, TTL adequado por tipo de dado, cache invalidation correta, uso de cache tags, filas de processamento. Identifique oportunidades de cache que não estão sendo aproveitadas.' },
    { id:'p3', label:'Assets, S3 e CloudFront',
      prompt:'Audite a configuração de assets estáticos no Vivensi. Verifique: uso correto do S3 com CloudFront CDN, versionamento de assets com Laravel Mix/Vite, imagens otimizadas, lazy loading de imagens, configuração de cache headers no CloudFront.' },
    { id:'p4', label:'Livewire 3 e performance de componentes',
      prompt:'Audite o uso de Livewire 3 no projeto. Verifique: componentes com muitos re-renders desnecessários, uso correto de wire:model.lazy vs wire:model, paginação server-side, uso de lazy loading de componentes, Alpine.js para interações leves que não precisam de round-trip.' },
  ]},
  { id:'stability',   label:'Estabilidade',   icon:'🏗️', color:'#3b82f6', checks:[
    { id:'st1', label:'Tratamento de erros e logs',
      prompt:'Audite o tratamento de erros no projeto Laravel. Verifique: try/catch nos pontos críticos, uso correto do Handler.php, configuração do Sentry/Telescope para monitoramento, logs estruturados, alertas para erros críticos em produção. Liste pontos sem tratamento adequado.' },
    { id:'st2', label:'Módulo de Rifas (RifaService e lockForUpdate)',
      prompt:'Audite o módulo de rifas do Vivensi com atenção máxima — componente financeiro crítico. Verifique: uso correto de lockForUpdate() para race conditions, transações DB, expiração agendada de rifas, concorrência em compras simultâneas, rollback em falhas de pagamento, testes do fluxo completo.' },
    { id:'st3', label:'Filas Laravel (Queue/Supervisor)',
      prompt:'Audite a configuração de filas Laravel com Supervisor. Verifique: jobs sem timeout configurado, falta de retry logic, jobs que podem causar loop infinito, dead letter queue, monitoramento de jobs falhados, configuração do Supervisor (numprocs, autostart, autorestart).' },
    { id:'st4', label:'Migrations e integridade do banco de dados',
      prompt:'Audite as migrations e estrutura do banco MySQL/RDS. Verifique: migrations sem rollback definido, falta de foreign keys e indexes críticos, campos sem constraints adequadas, seeders que podem corromper dados em produção, backup automatizado do RDS.' },
  ]},
  { id:'ux',          label:'UX / Funcional', icon:'🎯', color:'#10b981', checks:[
    { id:'u1', label:'Fluxo completo de cadastro de ONG',
      prompt:'Descreva e audite o fluxo completo de cadastro de uma ONG no Vivensi: desde o registro até a ativação da conta. Identifique: pontos de abandono potenciais, validações ausentes, emails de confirmação, onboarding, primeiro acesso ao painel. Liste o que pode causar fricção ou falha.' },
    { id:'u2', label:'Fluxo de assinatura e cobrança',
      prompt:'Audite o fluxo de assinatura do Vivensi para venda B2B para ONGs. Verifique: planos disponíveis, trial/período gratuito, upgrade/downgrade, cobrança recorrente via PIX ou cartão, cancelamento, bloqueio por inadimplência, reativação. Mapeie gaps críticos antes do go-to-market.' },
    { id:'u3', label:'Módulo de Mensagens e Bot WhatsApp',
      prompt:'Audite o módulo de mensagens do Vivensi (Evolution API + Bot IA Gemini/DeepSeek). Verifique: reconexão automática após queda do WhatsApp, fila de mensagens em caso de instabilidade, logs de conversas, treinamento do bot, fallback quando IA não responde, integração com calendário executivo.' },
    { id:'u4', label:'Painel FilamentPHP — usabilidade e dados',
      prompt:'Audite o painel administrativo FilamentPHP do Vivensi. Verifique: KPIs exibidos corretamente, filtros e buscas funcionais, exportação de relatórios, permissões por perfil de usuário, recursos que podem confundir ONGs menos técnicas, performance das tabelas com muitos registros.' },
  ]},
  { id:'infra',       label:'Infraestrutura', icon:'☁️', color:'#8b5cf6', checks:[
    { id:'i1', label:'AWS Lightsail / EC2 e configurações',
      prompt:'Audite a infraestrutura AWS do Vivensi (Lightsail/EC2, Ubuntu 22.04). Verifique: Nginx/Apache configurado para produção, SSL/HTTPS ativo, firewall (portas abertas necessárias), OPcache PHP habilitado, swap configurado, monitoramento de recursos, alarmes de CPU/memória.' },
    { id:'i2', label:'Deployment e CI/CD (GitHub)',
      prompt:'Audite o processo de deployment do Vivensi a partir do repositório GitHub. Verifique: processo de deploy sem downtime, rollback em caso de falha, variáveis de ambiente em produção, artisan commands pós-deploy (migrate, cache:clear, queue:restart), automação com GitHub Actions.' },
    { id:'i3', label:'AppServiceProvider e boot crítico',
      prompt:'Audite o AppServiceProvider.php do Vivensi — histórico de falha crítica onde queries na tabela system_settings crashavam a instância antes das migrations. Verifique: se o problema foi resolvido com Schema::hasTable(), se há outras queries no boot sem guard, outros ServiceProviders com mesmo problema.' },
  ]},
];

const ALL_CHECKS = AUDIT_MODULES.flatMap(m => m.checks.map(c => ({ ...c, moduleId: m.id, moduleLabel: m.label, moduleColor: m.color, moduleIcon: m.icon })));
const TOTAL = ALL_CHECKS.length;

const SEV_COLORS = { alta: '#ef4444', média: '#f59e0b', baixa: '#22c55e', info: '#6b7280' };

// ── State ─────────────────────────────────────────────────────────────────────
let S = {
    results:       {},      // { checkId: { status, text, issues } }
    running:       null,
    queue:         [],
    activeModule:  'security',
    expandedCheck: null,
    contextInfo:   '',
    showContext:   true,
    stopped:       false,
};

// ── Helpers ───────────────────────────────────────────────────────────────────
function detectSeverity(text) {
    const t = text.toLowerCase();
    if (t.includes('alta') || t.includes('crítico') || t.includes('critical') || t.includes('grave')) return 'alta';
    if (t.includes('média') || t.includes('moderado') || t.includes('medium')) return 'média';
    if (t.includes('baixa') || t.includes('low') || t.includes('minor')) return 'baixa';
    return 'info';
}

function parseIssues(text) {
    return text.split('\n')
        .filter(l => l.trim())
        .map(l => l.replace(/^[\s\-\*\•\d\.]+/, '').trim())
        .filter(l => l.length > 20)
        .slice(0, 12)
        .map(text => ({ text, severity: detectSeverity(text) }));
}

function completedCount() { return Object.values(S.results).filter(r => r.status === 'done' || r.status === 'error').length; }
function isRunning()      { return S.running !== null || S.queue.length > 0; }

// ── API call ──────────────────────────────────────────────────────────────────
async function apiRunCheck(prompt) {
    const resp = await fetch('{{ route("admin.audit.run") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ prompt, context: S.contextInfo }),
    });
    if (!resp.ok) {
        const err = await resp.json().catch(() => ({}));
        throw new Error(err.error || 'Erro HTTP ' + resp.status);
    }
    return resp.json();
}

// ── Core run logic ────────────────────────────────────────────────────────────
async function runCheck(checkId) {
    const check = ALL_CHECKS.find(c => c.id === checkId);
    if (!check) return;

    S.running = checkId;
    S.results[checkId] = { status: 'running', text: '', issues: [] };
    render();

    try {
        const data = await apiRunCheck(check.prompt);
        if (S.stopped) { S.running = null; render(); return; }
        const text   = data.text || 'Sem resposta.';
        const issues = parseIssues(text);
        S.results[checkId] = { status: 'done', text, issues };
    } catch (e) {
        if (!S.stopped) {
            S.results[checkId] = { status: 'error', text: 'Erro: ' + e.message, issues: [] };
        }
    }

    S.running = null;
    render();
    processQueue();
}

function processQueue() {
    if (S.stopped || S.running !== null || S.queue.length === 0) return;
    const [next, ...rest] = S.queue;
    S.queue = rest;
    runCheck(next);
}

function startFullAudit() {
    S.stopped = false;
    S.showContext = false;
    const ids = ALL_CHECKS.map(c => c.id);
    const [first, ...rest] = ids;
    S.queue = rest;
    runCheck(first);
}

function startModuleAudit(moduleId) {
    if (isRunning()) return;
    S.stopped = false;
    S.showContext = false;
    const ids = AUDIT_MODULES.find(m => m.id === moduleId).checks.map(c => c.id);
    const [first, ...rest] = ids;
    S.queue = rest;
    runCheck(first);
}

function runSingleCheck(checkId) {
    if (isRunning()) return;
    S.stopped = false;
    S.showContext = false;
    runCheck(checkId);
}

function stopAudit() {
    S.stopped = true;
    S.queue   = [];
    S.running = null;
    render();
}

function setActiveModule(id) {
    S.activeModule = id;
    render();
}

function toggleExpanded(checkId) {
    S.expandedCheck = S.expandedCheck === checkId ? null : checkId;
    renderMain();
}

// ── Render ─────────────────────────────────────────────────────────────────────
function render() {
    renderProgress();
    renderSummary();
    renderSidebar();
    renderMain();
}

function renderProgress() {
    const done = completedCount();
    const pct  = TOTAL > 0 ? Math.round((done / TOTAL) * 100) : 0;
    document.getElementById('progress-bar').style.width   = pct + '%';
    document.getElementById('global-pct').textContent     = pct + '%';
    document.getElementById('global-pct').style.color     = pct === 100 ? '#22c55e' : '#60a5fa';
    document.getElementById('global-count').textContent   = done + '/' + TOTAL + ' concluídas';
}

function renderSummary() {
    const issues = Object.values(S.results).flatMap(r => r.issues || []);
    const counts = { alta:0, média:0, baixa:0, info:0 };
    issues.forEach(i => { counts[i.severity] = (counts[i.severity] || 0) + 1; });

    if (completedCount() > 0) {
        document.getElementById('summary-bar').style.display = 'flex';
    }

    const total = counts.alta + counts.média + counts.baixa + counts.info;
    document.getElementById('sev-ok').style.display = total === 0 ? '' : 'none';

    ['alta','media','baixa','info'].forEach(k => {
        const key = k === 'media' ? 'média' : k;
        const el  = document.getElementById('sev-' + k);
        if (counts[key] > 0) {
            el.style.display = '';
            el.textContent   = '● ' + counts[key] + ' ' + key.toUpperCase();
        } else {
            el.style.display = 'none';
        }
    });
}

function renderSidebar() {
    const sb = document.getElementById('audit-sidebar');
    let html  = '';

    AUDIT_MODULES.forEach(mod => {
        const modCheckIds = mod.checks.map(c => c.id);
        const done        = modCheckIds.filter(id => S.results[id]?.status === 'done').length;
        const active      = S.activeModule === mod.id;
        const hasRunning  = modCheckIds.includes(S.running) || modCheckIds.some(id => S.queue.includes(id));
        const pct         = Math.round((done / mod.checks.length) * 100);

        html += `
        <div class="mod-item${active ? ' active' : ''}"
             style="border-left-color:${active ? mod.color : 'transparent'};"
             onclick="setActiveModule('${mod.id}')">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                <span style="font-size:14px;">${mod.icon}</span>
                <span style="color:${active ? '#e2e8f0' : '#64748b'};font-size:11px;font-weight:600;">${mod.label}</span>
                ${hasRunning ? '<span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;animation:pulse 1s infinite;display:inline-block;"></span>' : ''}
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <div style="flex:1;height:2px;background:#1e293b;border-radius:1px;">
                    <div style="height:100%;width:${pct}%;background:${mod.color};border-radius:1px;transition:width .3s;"></div>
                </div>
                <span style="color:#475569;font-size:10px;">${done}/${mod.checks.length}</span>
            </div>
            <button class="mod-audit-btn"
                    ${isRunning() ? 'disabled' : ''}
                    style="background:${isRunning() ? '#1e293b' : mod.color+'22'};border:1px solid ${isRunning() ? '#1e293b' : mod.color+'44'};color:${isRunning() ? '#475569' : mod.color};"
                    onclick="event.stopPropagation(); startModuleAudit('${mod.id}')">
                ${hasRunning ? 'RODANDO...' : 'AUDITAR MÓDULO'}
            </button>
        </div>`;
    });

    html += `
    <div style="padding:12px 16px;margin-top:8px;border-top:1px solid #1e293b;">
        ${!isRunning()
            ? `<button id="full-audit-btn" onclick="startFullAudit()">🚀 AUDITORIA COMPLETA</button>`
            : `<button id="stop-btn" onclick="stopAudit()">⏹ PARAR</button>`
        }
    </div>`;

    sb.innerHTML = html;
}

function renderMain() {
    const main = document.getElementById('audit-main');
    const mod  = AUDIT_MODULES.find(m => m.id === S.activeModule);
    if (!mod) return;

    let html = '';

    // Context input
    if (S.showContext) {
        html += `
        <div style="background:#0f172a;border:1px solid #1e3a5f;border-radius:8px;padding:16px;margin-bottom:16px;">
            <div style="color:#60a5fa;font-size:11px;font-weight:700;margin-bottom:8px;">📋 CONTEXTO ADICIONAL (opcional)</div>
            <textarea id="ctx-input" oninput="S.contextInfo=this.value" placeholder="Ex: Módulo de rifas recentemente refatorado, usando PIX via OpenPix, deploy no Lightsail t2.medium, 50 ONGs em homologação..."
                style="width:100%;min-height:60px;background:#0a0e1a;border:1px solid #1e293b;border-radius:4px;color:#94a3b8;padding:8px;font-size:11px;font-family:inherit;resize:vertical;box-sizing:border-box;">${escHtml(S.contextInfo)}</textarea>
            <div style="color:#475569;font-size:10px;margin-top:4px;">Quanto mais contexto você fornecer, mais precisa será a auditoria.</div>
        </div>`;
    }

    // Module header
    html += `
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
        <span style="font-size:18px;">${mod.icon}</span>
        <span style="color:${mod.color};font-size:14px;font-weight:700;">${mod.label.toUpperCase()}</span>
    </div>`;

    // Checks
    mod.checks.forEach(check => {
        const result     = S.results[check.id];
        const inQueue    = S.queue.includes(check.id);
        const thisRun    = S.running === check.id;
        const expanded   = S.expandedCheck === check.id;
        const rawStatus  = thisRun ? 'running' : inQueue ? 'queued' : (result?.status || 'pending');

        const SC = {
            pending: { color:'#475569', icon:'○', label:'PENDENTE' },
            queued:  { color:'#f59e0b', icon:'◌', label:'NA FILA' },
            running: { color:'#60a5fa', icon:'◉', label:'ANALISANDO...' },
            done:    { color:'#22c55e', icon:'●', label:'CONCLUÍDO' },
            error:   { color:'#ef4444', icon:'✕', label:'ERRO' },
        };
        const sc = SC[rawStatus] || SC.pending;

        const highIssues = (result?.issues || []).filter(i => i.severity === 'alta').length;
        const medIssues  = (result?.issues || []).filter(i => i.severity === 'média').length;

        html += `
        <div class="check-card${expanded ? ' expanded' : ''}" style="border-color:${expanded ? mod.color+'44' : '#1e293b'};">
            <div class="check-header" onclick="toggleExpanded('${check.id}')">
                <span style="color:${sc.color};font-size:12px;${rawStatus==='running'?'animation:spin 1s linear infinite;display:inline-block;':''}">${sc.icon}</span>
                <div style="flex:1;">
                    <div style="color:#cbd5e1;font-size:11px;font-weight:600;">${escHtml(check.label)}</div>
                    <div style="color:${sc.color};font-size:9px;margin-top:1px;">${sc.label}</div>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    ${highIssues > 0 ? `<span style="background:#ef444422;color:#ef4444;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;">${highIssues} ALTA</span>` : ''}
                    ${medIssues  > 0 ? `<span style="background:#f59e0b22;color:#f59e0b;padding:1px 6px;border-radius:3px;font-size:9px;font-weight:700;">${medIssues} MED</span>` : ''}
                    ${rawStatus === 'pending' ? `<button class="run-btn" ${isRunning()?'disabled':''} onclick="event.stopPropagation(); runSingleCheck('${check.id}')">RODAR</button>` : ''}
                    <span style="color:#475569;font-size:10px;">${expanded ? '▲' : '▼'}</span>
                </div>
            </div>
            ${expanded && result ? renderCheckBody(result, rawStatus) : ''}
        </div>`;
    });

    main.innerHTML = html;
}

function renderCheckBody(result, status) {
    let html = '<div class="check-body">';

    if (status === 'running') {
        html += `<div style="color:#60a5fa;font-size:11px;display:flex;align-items:center;gap:8px;">
            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#60a5fa;" class="anim-pulse"></span>
            Analisando código e configurações...
        </div>`;
    }

    if (result.issues && result.issues.length > 0) {
        html += `<div style="margin-bottom:12px;">
            <div style="color:#475569;font-size:9px;margin-bottom:6px;letter-spacing:1px;">ITENS IDENTIFICADOS</div>`;
        result.issues.forEach(issue => {
            html += `<div class="issue-row" style="background:${SEV_COLORS[issue.severity]}11;border-left:2px solid ${SEV_COLORS[issue.severity]};">
                <span style="color:${SEV_COLORS[issue.severity]};font-size:9px;font-weight:700;flex-shrink:0;padding-top:1px;">${issue.severity.toUpperCase()}</span>
                <span style="color:#94a3b8;font-size:10px;line-height:1.4;">${escHtml(issue.text)}</span>
            </div>`;
        });
        html += '</div>';
    }

    if (result.text) {
        html += `<div>
            <div style="color:#475569;font-size:9px;margin-bottom:6px;letter-spacing:1px;">ANÁLISE COMPLETA</div>
            <pre class="analysis-pre">${escHtml(result.text)}</pre>
        </div>`;
    }

    html += '</div>';
    return html;
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Init ──────────────────────────────────────────────────────────────────────
render();
</script>

@endsection
