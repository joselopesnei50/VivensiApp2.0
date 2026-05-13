@extends('layouts.app', ['title' => 'Inteligência Territorial'])

@push('styles')
<style>
/* ══════════════════════════════════════════════════════
   INTELIGÊNCIA TERRITORIAL — UX para Projetos Sociais
   ══════════════════════════════════════════════════════ */
.it-wrap  { background: #f8fafc; min-height: 100vh; padding: 0; }

/* ── Page header ──────────────────────────────────────── */
.it-hdr {
    background: #fff; border-bottom: 1px solid #EAECF0;
    padding: 20px 36px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.it-hdr h1 { font-size: 1.3rem; font-weight: 800; color: #101828; margin: 0; letter-spacing: -.3px; }
.it-hdr h1 span { color: var(--primary-color, #4F46E5); }
.it-hdr p  { color: #667085; margin: 3px 0 0; font-size: .8rem; }
.it-pill {
    display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 4px 12px;
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
}
.it-pill.green  { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
.it-pill.indigo { background: #F4F3FF; color: #4338CA; border: 1px solid #C7D2FE; }

/* ── Content area ─────────────────────────────────────── */
.it-body { padding: 28px 36px; }

/* ── Search card ──────────────────────────────────────── */
.it-search {
    background: #fff; border: 1px solid #EAECF0;
    border-top: 4px solid var(--primary-color, #4F46E5);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 28px;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 8px 24px rgba(16,24,40,.05);
}
.it-search-row { position: relative; display: flex; gap: 10px; }
.it-si { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #98A2B3; pointer-events: none; }
.it-sinput {
    flex: 1; background: #F9FAFB; border: 1.5px solid #D0D5DD;
    border-radius: 12px; padding: 13px 20px 13px 44px;
    color: #101828; font-size: .95rem; outline: none; font-family: inherit; transition: all .2s;
}
.it-sinput::placeholder { color: #98A2B3; }
.it-sinput:focus { background: #fff; border-color: var(--primary-color,#4F46E5); box-shadow: 0 0 0 4px rgba(79,70,229,.08); }
.it-sbtn {
    background: var(--primary-color,#4F46E5); color: #fff; border: none;
    border-radius: 12px; padding: 13px 24px; font-weight: 700; font-size: .88rem;
    cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 7px;
    box-shadow: 0 4px 14px rgba(79,70,229,.3); transition: all .2s;
}
.it-sbtn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.it-sbtn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* ── Autocomplete ─────────────────────────────────────── */
.it-ac {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0;
    background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(16,24,40,.1); z-index: 1000; display: none; overflow: hidden;
}
.it-ac-item { display: flex; align-items: center; gap: 12px; padding: 11px 16px; cursor: pointer; border-bottom: 1px solid #F9FAFB; transition: background .12s; }
.it-ac-item:last-child { border-bottom: none; }
.it-ac-item:hover, .it-ac-item.active { background: #F4F3FF; }
.ac-i   { color: #98A2B3; font-size: .85rem; flex-shrink: 0; }
.ac-city{ font-weight: 700; font-size: .88rem; color: #101828; }
.ac-uf  { font-size: .75rem; color: #667085; }
.ac-tag { margin-left: auto; background: #F2F4F7; color: #475467; padding: 2px 8px; border-radius: 6px; font-size: .65rem; font-weight: 700; }

/* ── City bar ─────────────────────────────────────────── */
.it-city-bar { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
.it-city-name { font-size: 1.5rem; font-weight: 800; color: #101828; margin: 0; letter-spacing: -.4px; }
.it-city-sub  { font-size: .72rem; color: #667085; margin-top: 3px; }
.it-cache-pill { display: inline-flex; align-items: center; gap: 6px; background: #F9FAFB; border: 1px solid #EAECF0; border-radius: 8px; padding: 5px 12px; font-size: .7rem; color: #667085; }

/* ── Overview strip ───────────────────────────────────── */
.it-strip {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 28px;
}
@media(max-width:768px){ .it-strip { grid-template-columns: repeat(2,1fr); } }
.it-strip-item {
    background: #fff; border: 1px solid #EAECF0; border-radius: 12px; padding: 16px 18px;
    box-shadow: 0 1px 3px rgba(16,24,40,.05);
}
.it-strip-label { font-size: .65rem; font-weight: 700; color: #98A2B3; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.it-strip-val   { font-size: 1.2rem; font-weight: 800; color: #101828; letter-spacing: -.3px; }
.it-strip-unit  { font-size: .65rem; color: #667085; margin-top: 2px; }

/* ── Thematic section ─────────────────────────────────── */
.it-section {
    background: #fff; border: 1px solid #EAECF0; border-radius: 16px;
    overflow: hidden; margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.04);
}
.it-section-hdr {
    padding: 18px 24px; border-bottom: 1px solid #F2F4F7;
    display: flex; align-items: center; gap: 12px;
}
.it-section-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.it-section-title { font-size: .95rem; font-weight: 800; color: #101828; margin: 0; }
.it-section-sub   { font-size: .72rem; color: #667085; margin-top: 2px; }
.it-section-body  { padding: 20px 24px; }

/* ── KPI cards inside section ─────────────────────────── */
.it-kpis { display: grid; gap: 12px; margin-bottom: 18px; }
.it-kpis.cols2 { grid-template-columns: repeat(2,1fr); }
.it-kpis.cols3 { grid-template-columns: repeat(3,1fr); }
.it-kpis.cols4 { grid-template-columns: repeat(4,1fr); }
@media(max-width:900px){ .it-kpis.cols4 { grid-template-columns: repeat(2,1fr); } }
@media(max-width:768px){ .it-kpis.cols3, .it-kpis.cols2 { grid-template-columns: repeat(2,1fr); } }

.it-kpi {
    background: #F9FAFB; border: 1px solid #F2F4F7; border-radius: 12px; padding: 16px 18px;
    transition: border-color .2s, box-shadow .2s;
}
.it-kpi:hover { border-color: #D0D5DD; box-shadow: 0 2px 8px rgba(16,24,40,.06); }
.it-kpi.highlight {
    background: #FFF1F2; border-color: #FECACA;
}
.it-kpi.highlight .kpi-val { color: #9F1239; }
.it-kpi-lbl { font-size: .65rem; font-weight: 700; color: #98A2B3; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 5px; }
.kpi-val    { font-size: 1.4rem; font-weight: 900; color: #101828; letter-spacing: -.3px; line-height: 1.1; margin-bottom: 4px; }
.kpi-val.nd { font-size: 1rem; color: #C0C7D2; font-weight: 600; }
.kpi-year   { display: inline-flex; align-items: center; gap: 4px; font-size: .62rem; font-weight: 700; border-radius: 5px; padding: 2px 7px; }
.kpi-bar    { height: 3px; background: #EAECF0; border-radius: 10px; overflow: hidden; margin-top: 10px; }
.kpi-bar-fill { height: 100%; border-radius: 10px; transition: width 1.2s cubic-bezier(.4,0,.2,1); width: 0; }

/* ── Context callout ──────────────────────────────────── */
.it-callout {
    border-radius: 10px; padding: 14px 16px; margin-top: 4px;
    display: flex; gap: 12px; align-items: flex-start;
}
.it-callout.insight  { background: #F4F3FF; border-left: 3px solid #7C3AED; }
.it-callout.action   { background: #F0FDF4; border-left: 3px solid #16A34A; }
.it-callout.warning  { background: #FFF7ED; border-left: 3px solid #D97706; }
.it-callout-icon { font-size: .85rem; flex-shrink: 0; margin-top: 2px; }
.it-callout p { font-size: .8rem; color: #344054; line-height: 1.6; margin: 0; }
.it-callout p strong { font-weight: 700; }

/* ── IDHM visual breakdown ────────────────────────────── */
.idhm-badge { font-size: .65rem; font-weight: 700; border-radius: 20px; padding: 2px 9px; white-space: nowrap; }

/* ── AI card ──────────────────────────────────────────── */
.it-ai-card {
    background: #fff; border: 1px solid #EAECF0; border-radius: 16px; padding: 24px; margin-top: 20px;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.04);
}
.it-ai-head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.it-ai-ava  { width: 44px; height: 44px; border-radius: 12px; background: var(--primary-color,#4F46E5); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(79,70,229,.3); }
.it-ai-name { font-weight: 800; font-size: .9rem; color: #101828; margin: 0; }
.it-ai-sub  { font-size: .68rem; color: #12B76A; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
.it-ai-body { font-size: .875rem; line-height: 1.8; color: #344054; min-height: 80px; }
.it-ai-body.empty { color: #98A2B3; font-style: italic; }
.it-ai-disc { margin-top: 14px; padding-top: 12px; border-top: 1px solid #F2F4F7; font-size: .7rem; color: #98A2B3; display: flex; gap: 6px; }

/* ── Skeleton ─────────────────────────────────────────── */
.sk { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size: 400px 100%; animation: sk 1.4s infinite linear; border-radius: 6px; display: inline-block; }
@keyframes sk { 0%{background-position:-400px 0} 100%{background-position:400px 0} }

/* ── Empty state ──────────────────────────────────────── */
.it-empty-cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
@media(max-width:768px){ .it-empty-cards { grid-template-columns: 1fr; } }
.it-empty-card { background: #fff; border: 1px solid #EAECF0; border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(16,24,40,.06); }
.it-empty-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }

/* ── Toast ────────────────────────────────────────────── */
#it-toasts { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.it-toast { background: #fff; border: 1px solid #EAECF0; border-radius: 12px; padding: 12px 18px; font-size: .84rem; font-weight: 500; color: #101828; display: flex; align-items: center; gap: 10px; min-width: 260px; max-width: 360px; box-shadow: 0 4px 12px rgba(16,24,40,.1); animation: toastIn .3s cubic-bezier(.34,1.56,.64,1); }
.it-toast.error   { border-left: 3px solid #F04438; }
.it-toast.success { border-left: 3px solid #12B76A; }
@keyframes toastIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
</style>
@endpush

@section('content')
<div class="it-wrap">

    {{-- Header --}}
    <div class="it-hdr">
        <div>
            <h1><i class="fas fa-map-location-dot" style="color:var(--primary-color,#4F46E5);margin-right:10px"></i>Inteligência <span>Territorial</span></h1>
            <p>Dados IBGE organizados para elaboração de projetos sociais e captação de recursos</p>
        </div>
        <div style="display:flex;gap:8px">
            <span class="it-pill green"><i class="fas fa-database"></i> IBGE Oficial</span>
            <span class="it-pill indigo"><i class="fas fa-robot"></i> Bruce AI</span>
        </div>
    </div>

    <div class="it-body">

        {{-- Search --}}
        <div class="it-search">
            <p style="font-size:.8rem;font-weight:700;color:#344054;margin:0 0 10px;display:flex;align-items:center;gap:8px">
                <i class="fas fa-magnifying-glass-location" style="color:var(--primary-color,#4F46E5)"></i>
                Pesquise um município para gerar o diagnóstico social completo
            </p>
            <div class="it-search-row">
                <i class="it-si fas fa-search"></i>
                <input type="text" id="citySearch" class="it-sinput" placeholder="Ex: Araraquara, Campinas, Fortaleza..." autocomplete="off">
                <div id="it-ac" class="it-ac"></div>
                <button id="btnAna" class="it-sbtn" onclick="triggerSearch()" disabled>
                    <i class="fas fa-chart-bar" id="btnIcon"></i>
                    <span id="btnTxt">Analisar</span>
                </button>
            </div>
            <p style="font-size:.7rem;color:#98A2B3;margin:10px 0 0">
                <i class="fas fa-circle-info me-1"></i>
                Dados: <strong>IBGE Cidades</strong> (Censo 2022, estimativas populacionais, IDHM 2010) + <strong>IBGE SIDRA</strong> (educação, saneamento) · Atualização automática a cada 7 dias
            </p>
        </div>

        {{-- Results --}}
        <div id="resultsArea" style="display:none">

            {{-- City bar --}}
            <div class="it-city-bar">
                <div>
                    <h2 class="it-city-name" id="cityNameDisplay">—</h2>
                    <p class="it-city-sub">Dados oficiais do IBGE · Fonte: IBGE Cidades e IBGE SIDRA</p>
                </div>
                <span class="it-cache-pill"><i class="fas fa-clock-rotate-left"></i> Consultado em <strong id="cacheDate" style="margin-left:4px">—</strong></span>
            </div>

            {{-- Overview strip --}}
            <div class="it-strip">
                <div class="it-strip-item">
                    <div class="it-strip-label">População</div>
                    <div class="it-strip-val" id="sv-populacao">—</div>
                    <div class="it-strip-unit" id="su-populacao"></div>
                </div>
                <div class="it-strip-item">
                    <div class="it-strip-label">Área</div>
                    <div class="it-strip-val" id="sv-area">—</div>
                    <div class="it-strip-unit" id="su-area"></div>
                </div>
                <div class="it-strip-item">
                    <div class="it-strip-label">Mortalidade Infantil</div>
                    <div class="it-strip-val" id="sv-mortalidade">—</div>
                    <div class="it-strip-unit" id="su-mortalidade">por 1.000 nasc.</div>
                </div>
                <div class="it-strip-item">
                    <div class="it-strip-label">PIB per capita</div>
                    <div class="it-strip-val" id="sv-pib">—</div>
                    <div class="it-strip-unit" id="su-pib"></div>
                </div>
            </div>

            {{-- SECTION 1: Infância & Educação --}}
            <div class="it-section">
                <div class="it-section-hdr">
                    <div class="it-section-icon" style="background:#EFF6FF;color:#1D4ED8"><i class="fas fa-child"></i></div>
                    <div>
                        <p class="it-section-title">Infância & Educação</p>
                        <p class="it-section-sub">Escolarização, crianças fora da escola e acesso à educação básica</p>
                    </div>
                </div>
                <div class="it-section-body">
                    <div class="it-kpis cols4" id="kpis-infancia">

                        {{-- Taxa de escolarização --}}
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">Escolarização 6–14 anos</div>
                            <div class="kpi-val" id="kv-educacao">—</div>
                            <div><span class="kpi-year" id="ky-educacao" style="background:#EFF6FF;color:#1D4ED8"></span></div>
                            <div class="kpi-bar"><div class="kpi-bar-fill" id="kb-educacao" style="background:#1D4ED8"></div></div>
                        </div>

                        {{-- % Fora da escola --}}
                        <div class="it-kpi highlight" id="card-fora-pct">
                            <div class="it-kpi-lbl">Fora da escola (6–14)</div>
                            <div class="kpi-val" id="kv-fora-pct">—</div>
                            <div><span class="kpi-year" id="ky-fora-pct" style="background:#FFF1F2;color:#9F1239"></span></div>
                            <div class="kpi-bar"><div class="kpi-bar-fill" id="kb-fora-pct" style="background:#E11D48"></div></div>
                        </div>

                        {{-- Estimativa absoluta --}}
                        <div class="it-kpi highlight" id="card-fora-est">
                            <div class="it-kpi-lbl">Crianças fora da escola</div>
                            <div class="kpi-val" id="kv-fora-est">—</div>
                            <div style="font-size:.6rem;color:#9F1239;margin-top:4px" id="ky-fora-est"></div>
                        </div>

                        {{-- Saneamento --}}
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">Saneamento Adequado</div>
                            <div class="kpi-val" id="kv-saneamento">—</div>
                            <div><span class="kpi-year" id="ky-saneamento" style="background:#ECFEFF;color:#155E75"></span></div>
                            <div class="kpi-bar"><div class="kpi-bar-fill" id="kb-saneamento" style="background:#0E7490"></div></div>
                        </div>
                    </div>

                    {{-- Callouts --}}
                    <div id="callout-educacao" style="display:none">
                        <div class="it-callout warning">
                            <i class="fas fa-triangle-exclamation it-callout-icon" style="color:#D97706"></i>
                            <p id="callout-edu-text"></p>
                        </div>
                        <div class="it-callout action" style="margin-top:8px">
                            <i class="fas fa-lightbulb it-callout-icon" style="color:#166534"></i>
                            <p><strong>Oportunidade para Projetos:</strong> Reforço escolar, programas de esporte e cultura no contraturno, busca ativa de crianças fora da escola, defensoria dos direitos à educação, formação de professores em territórios vulneráveis.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Saúde & Bem-estar --}}
            <div class="it-section">
                <div class="it-section-hdr">
                    <div class="it-section-icon" style="background:#F0FDF4;color:#166534"><i class="fas fa-heart-pulse"></i></div>
                    <div>
                        <p class="it-section-title">Saúde & Bem-estar</p>
                        <p class="it-section-sub">Mortalidade infantil, IDHM e indicadores de qualidade de vida</p>
                    </div>
                </div>
                <div class="it-section-body">
                    <div class="it-kpis cols2">
                        <div class="it-kpi" id="card-mortalidade">
                            <div class="it-kpi-lbl">Mortalidade Infantil</div>
                            <div class="kpi-val" id="kv-mortalidade">—</div>
                            <div><span class="kpi-year" id="ky-mortalidade" style="background:#FFF1F2;color:#9F1239"></span></div>
                            <div class="kpi-bar"><div class="kpi-bar-fill" id="kb-mortalidade" style="background:#E11D48"></div></div>
                        </div>
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">Óbitos Registrados</div>
                            <div class="kpi-val" id="kv-obitos">—</div>
                            <div><span class="kpi-year" id="ky-obitos" style="background:#F9FAFB;color:#475467"></span></div>
                        </div>
                    </div>
                    <div class="it-callout insight" id="callout-saude" style="display:none">
                        <i class="fas fa-circle-info it-callout-icon" style="color:#7C3AED"></i>
                        <p id="callout-saude-text"></p>
                    </div>
                    <div class="it-callout action" style="margin-top:8px">
                        <i class="fas fa-lightbulb it-callout-icon" style="color:#166534"></i>
                        <p><strong>Oportunidade para Projetos:</strong> Saúde preventiva comunitária, pré-natal humanizado, cuidado de idosos, saúde mental, apoio a famílias em situação de luto, vigilância alimentar e nutricional.</p>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: Economia & Desenvolvimento --}}
            <div class="it-section">
                <div class="it-section-hdr">
                    <div class="it-section-icon" style="background:#FFFBEB;color:#92400E"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <p class="it-section-title">Economia & Desenvolvimento</p>
                        <p class="it-section-sub">PIB per capita, densidade demográfica e potencial produtivo do município</p>
                    </div>
                </div>
                <div class="it-section-body">
                    <div class="it-kpis cols3">
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">PIB per capita</div>
                            <div class="kpi-val" id="kv-pib">—</div>
                            <div><span class="kpi-year" id="ky-pib" style="background:#FFFBEB;color:#92400E"></span></div>
                            <div class="kpi-bar"><div class="kpi-bar-fill" id="kb-pib" style="background:#D97706"></div></div>
                        </div>
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">Densidade Demográfica</div>
                            <div class="kpi-val" id="kv-densidade">—</div>
                            <div><span class="kpi-year" id="ky-densidade" style="background:#F9FAFB;color:#475467"></span></div>
                        </div>
                        <div class="it-kpi">
                            <div class="it-kpi-lbl">Área Territorial</div>
                            <div class="kpi-val" id="kv-area">—</div>
                            <div><span class="kpi-year" id="ky-area" style="background:#F0FDF4;color:#166534"></span></div>
                        </div>
                    </div>
                    <div class="it-callout action" style="margin-top:8px">
                        <i class="fas fa-lightbulb it-callout-icon" style="color:#166534"></i>
                        <p><strong>Oportunidade para Projetos:</strong> Qualificação profissional e geração de renda, economia solidária e cooperativismo, microcrédito social, empreendedorismo periférico, acesso a programas de transferência de renda.</p>
                    </div>
                </div>
            </div>

            {{-- AI Analysis --}}
            <div class="it-ai-card">
                <div class="it-ai-head">
                    <div class="it-ai-ava"><i class="fas fa-robot"></i></div>
                    <div>
                        <p class="it-ai-name">Diagnóstico Bruce AI</p>
                        <span class="it-ai-sub">Análise Estratégica para o Terceiro Setor</span>
                    </div>
                </div>
                <div class="it-ai-body empty" id="aiAnalysis">
                    Selecione um município para receber o diagnóstico estratégico completo — vulnerabilidades, públicos prioritários e tipos de projeto com maior impacto social.
                </div>
                <div class="it-ai-disc">
                    <i class="fas fa-shield-halved" style="color:#12B76A;flex-shrink:0;margin-top:1px"></i>
                    Gerado por IA com base em dados públicos do IBGE. Não substitui estudo técnico especializado. Use como ponto de partida para diagnósticos mais aprofundados.
                </div>
            </div>

        </div>{{-- /resultsArea --}}

        {{-- Empty state --}}
        <div id="emptyState">
            <div class="it-empty-cards">
                <div class="it-empty-card">
                    <div class="it-empty-icon" style="background:#EFF6FF;color:#1D4ED8"><i class="fas fa-child"></i></div>
                    <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Infância & Educação</h6>
                    <p style="font-size:.8rem;color:#667085;margin:0">Taxa de escolarização, estimativa de crianças fora da escola e cobertura de saneamento básico — dados fundamentais para projetos sociais.</p>
                </div>
                <div class="it-empty-card">
                    <div class="it-empty-icon" style="background:#FFF1F2;color:#9F1239"><i class="fas fa-heart-pulse"></i></div>
                    <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Saúde & Bem-estar</h6>
                    <p style="font-size:.8rem;color:#667085;margin:0">Mortalidade infantil, IDHM e indicadores de qualidade de vida para identificar vulnerabilidades e priorizar ações de saúde pública.</p>
                </div>
                <div class="it-empty-card">
                    <div class="it-empty-icon" style="background:#F0FDF4;color:#166534"><i class="fas fa-robot"></i></div>
                    <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Diagnóstico Bruce AI</h6>
                    <p style="font-size:.8rem;color:#667085;margin:0">Análise interpretativa automática que transforma dados do IBGE em insights acionáveis para elaboração de projetos e captação de recursos.</p>
                </div>
            </div>
        </div>

    </div>{{-- /it-body --}}
</div>

<div id="it-toasts"></div>

@push('scripts')
<script>
const ROUTE_CITIES = '{{ route("ibge.cities.search") }}';
const ROUTE_IND    = (code, name) => `/api/ibge/indicators/${code}?city_name=${encodeURIComponent(name)}`;

let sel = null, timer = null, focusIdx = -1;
const inp  = document.getElementById('citySearch');
const acBox = document.getElementById('it-ac');
const btn   = document.getElementById('btnAna');

// ── Autocomplete ──────────────────────────────────────────
inp.addEventListener('input', function () {
    clearTimeout(timer); sel = null; btn.disabled = true; focusIdx = -1;
    const q = this.value.trim();
    if (q.length < 3) { acBox.style.display = 'none'; return; }
    timer = setTimeout(async () => {
        try {
            const data = await (await fetch(`${ROUTE_CITIES}?q=${encodeURIComponent(q)}`)).json();
            renderAC(data);
        } catch { acBox.style.display = 'none'; }
    }, 280);
});

function uf(c)    { return c['UF']?.sigla || c.microrregiao?.mesorregiao?.UF?.sigla || ''; }
function state(c) { return c['UF']?.nome  || c.microrregiao?.mesorregiao?.UF?.nome  || ''; }

function renderAC(cities) {
    acBox.innerHTML = '';
    if (!cities.length) { acBox.style.display = 'none'; return; }
    cities.forEach(c => {
        const el = document.createElement('div');
        el.className = 'it-ac-item';
        el.innerHTML = `<i class="fas fa-location-dot ac-i"></i><div><div class="ac-city">${c.nome}</div><div class="ac-uf">${state(c)}</div></div><span class="ac-tag">${uf(c)}</span>`;
        el.addEventListener('click', () => pick(c));
        acBox.appendChild(el);
    });
    acBox.style.display = 'block';
}
function pick(c) { sel = c; inp.value = `${c.nome} — ${uf(c)}`; acBox.style.display = 'none'; btn.disabled = false; btn.focus(); }

inp.addEventListener('keydown', e => {
    const items = [...acBox.querySelectorAll('.it-ac-item')];
    if (!items.length) return;
    if (e.key === 'ArrowDown')  { e.preventDefault(); focusIdx = Math.min(focusIdx+1, items.length-1); }
    else if (e.key === 'ArrowUp')   { e.preventDefault(); focusIdx = Math.max(focusIdx-1, 0); }
    else if (e.key === 'Enter')     { e.preventDefault(); if (focusIdx >= 0) items[focusIdx].click(); else if (sel) triggerSearch(); return; }
    else if (e.key === 'Escape')    { acBox.style.display = 'none'; return; }
    items.forEach((el, i) => el.classList.toggle('active', i === focusIdx));
});
document.addEventListener('click', e => { if (!inp.contains(e.target) && !acBox.contains(e.target)) acBox.style.display = 'none'; });

// ── Main search ───────────────────────────────────────────
async function triggerSearch() {
    if (!sel) { toast('Selecione uma cidade da lista.', 'error'); return; }

    const name = sel.nome, cityUF = uf(sel), code = sel.id;

    document.getElementById('emptyState').style.display   = 'none';
    document.getElementById('resultsArea').style.display  = 'block';
    document.getElementById('cityNameDisplay').textContent = `${name}${cityUF ? ', '+cityUF : ''}`;
    document.getElementById('cacheDate').textContent       = '—';

    resetAll();
    const ai = document.getElementById('aiAnalysis');
    ai.className = 'it-ai-body';
    ai.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="color:var(--primary-color,#4F46E5);margin-right:8px"></i> Bruce AI está analisando os dados do IBGE para este município…';

    const icon = document.getElementById('btnIcon');
    const txt  = document.getElementById('btnTxt');
    btn.disabled = true; icon.className = 'fas fa-circle-notch fa-spin'; txt.textContent = 'Analisando…';

    try {
        const resp = await fetch(ROUTE_IND(code, name));
        if (!resp.ok) throw new Error(`Servidor retornou HTTP ${resp.status}`);

        let data;
        try { data = await resp.json(); } catch { throw new Error('Resposta inválida do servidor.'); }
        if (!data || !data.raw) throw new Error('Estrutura de dados inesperada.');

        try { renderStrip(data.raw); }                  catch(e) { console.error('strip:', e); }
        try { renderInfancia(data.raw, data.derived); } catch(e) { console.error('infancia:', e); }
        try { renderSaude(data.raw); }                  catch(e) { console.error('saude:', e); }
        try { renderEconomia(data.raw); }               catch(e) { console.error('economia:', e); }

        ai.className   = 'it-ai-body';
        ai.textContent = data.analysis || '—';
        document.getElementById('cacheDate').textContent = new Date().toLocaleDateString('pt-BR');
        toast(`Diagnóstico de ${name} concluído!`, 'success');

    } catch (err) {
        console.error('[Territorial]', err);
        ai.className   = 'it-ai-body';
        ai.textContent = 'Erro ao buscar dados: ' + err.message;
        toast('Falha ao buscar indicadores IBGE.', 'error');
    } finally {
        btn.disabled = false; icon.className = 'fas fa-chart-bar'; txt.textContent = 'Analisar';
    }
}

// ── Number helpers ────────────────────────────────────────
function numBR(v) {
    if (v == null) return NaN;
    const s = String(v).replace(/\s/g,'');
    // "1.234,56" → 1234.56
    if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(s)) return parseFloat(s.replace(/\./g,'').replace(',','.'));
    // "1.234" with only 3 decimals after period could be period-as-decimal: keep as is
    return parseFloat(s.replace(',','.'));
}
function fmtNum(v, decimals = 0) { return v.toLocaleString('pt-BR', {minimumFractionDigits:decimals, maximumFractionDigits:decimals}); }
function setKpi(id, val, year, yearStyle, barPct, barColor) {
    const ve = document.getElementById(`kv-${id}`);
    const ye = document.getElementById(`ky-${id}`);
    const be = document.getElementById(`kb-${id}`);
    if (ve) { ve.className = 'kpi-val'; ve.textContent = val; }
    if (ye && year) { ye.textContent = year; if (yearStyle) Object.assign(ye.style, yearStyle); }
    if (be && barPct != null) setTimeout(() => { be.style.width = Math.min(barPct, 100) + '%'; if (barColor) be.style.background = barColor; }, 120);
}
function setKpiND(id) {
    const ve = document.getElementById(`kv-${id}`);
    const ye = document.getElementById(`ky-${id}`);
    if (ve) { ve.className = 'kpi-val nd'; ve.textContent = 'N/D'; }
    if (ye) ye.textContent = 'Sem dados';
}

// ── Strip (overview) ──────────────────────────────────────
function renderStrip(raw) {
    // NOTA: unit deve ser função (não expressão) para evitar ReferenceError ao criar o objeto
    const items = [
        { key: 'populacao',   id: 'populacao',  fmt: v => fmtNum(v) + ' hab.',      unit: () => '' },
        { key: 'area',        id: 'area',        fmt: v => fmtNum(v, 2) + ' km²',    unit: () => '' },
        { key: 'mortalidade', id: 'mortalidade', fmt: v => fmtNum(v, 1),             unit: () => '/1.000 nasc.' },
        { key: 'pib',         id: 'pib',         fmt: v => 'R$ ' + fmtNum(v, 0),    unit: () => '/hab.' },
    ];
    items.forEach(cfg => {
        const ind = raw[cfg.key];
        const sv  = document.getElementById(`sv-${cfg.id}`);
        const su  = document.getElementById(`su-${cfg.id}`);
        if (!sv) return;
        if (!ind || ind.value == null) { sv.textContent = '—'; return; }
        const num = numBR(ind.value);
        if (isNaN(num)) { sv.textContent = '—'; return; }
        sv.textContent = cfg.fmt(num);
        if (su) su.textContent = cfg.unit() || (ind.year ? ind.year : '');
    });
}

// ── Infância & Educação ───────────────────────────────────
function renderInfancia(raw, derived) {
    // Escolarização
    const edu = raw['educacao'];
    if (edu && edu.value != null) {
        const num = numBR(edu.value);
        setKpi('educacao', fmtNum(num,1) + '%', `Censo ${edu.year || ''}`, null, num, '#1D4ED8');
    } else { setKpiND('educacao'); }

    // Saneamento
    const san = raw['saneamento'];
    if (san && san.value != null) {
        const num = numBR(san.value);
        setKpi('saneamento', fmtNum(num,1) + '%', `Censo ${san.year || ''}`, null, num, '#0E7490');
    } else { setKpiND('saneamento'); }

    // Crianças fora da escola (%)
    const pct = derived?.fora_escola_pct;
    if (pct && pct.value != null) {
        const num = parseFloat(pct.value);
        const ve  = document.getElementById('kv-fora-pct');
        const ye  = document.getElementById('ky-fora-pct');
        const be  = document.getElementById('kb-fora-pct');
        if (ve) { ve.className = 'kpi-val'; ve.textContent = fmtNum(num,1) + '%'; }
        if (ye) ye.textContent = `Ref. ${pct.year || ''}`;
        if (be) setTimeout(() => { be.style.width = Math.min(num * 5, 100) + '%'; }, 120); // amplify for visibility

        // Callout
        const callout = document.getElementById('callout-educacao');
        const ct      = document.getElementById('callout-edu-text');
        if (callout && ct) {
            callout.style.display = 'block';
            const taxaEscol = edu ? fmtNum(numBR(edu.value),1) : '—';
            ct.innerHTML = `<strong>${fmtNum(num,1)}% das crianças de 6 a 14 anos estão fora da escola</strong> neste município (taxa de escolarização de ${taxaEscol}%). ${num > 5 ? 'Este índice está acima da média nacional e representa uma vulnerabilidade significativa que exige intervenção imediata.' : 'Este índice está dentro da média nacional, mas cada criança fora da escola representa uma oportunidade de atuação preventiva.'}`;
        }
    } else {
        const ve = document.getElementById('kv-fora-pct');
        if (ve) { ve.className = 'kpi-val nd'; ve.textContent = '—'; }
        const ye = document.getElementById('ky-fora-pct');
        if (ye) ye.textContent = edu ? 'Dado não disponível' : 'Aguardando dado base';
    }

    // Estimativa absoluta
    const est = derived?.fora_escola_est;
    if (est && est.value != null) {
        const ve = document.getElementById('kv-fora-est');
        const ye = document.getElementById('ky-fora-est');
        if (ve) { ve.className = 'kpi-val'; ve.textContent = fmtNum(parseInt(est.value)); }
        if (ye) ye.textContent = `crianças (estimativa baseada em ${est.year || '—'})`;
    } else {
        const ve = document.getElementById('kv-fora-est');
        if (ve) { ve.className = 'kpi-val nd'; ve.textContent = '—'; }
    }
}

// ── Saúde ─────────────────────────────────────────────────
function renderSaude(raw) {
    // Mortalidade infantil
    const mort = raw['mortalidade'];
    if (mort && mort.value != null) {
        const num = numBR(mort.value);
        // Barra: meta OMS é <10; escala até 30 (acima disso é emergência)
        setKpi('mortalidade', fmtNum(num, 1), mort.year || '', null, Math.min((num / 30) * 100, 100), '#E11D48');

        const callout = document.getElementById('callout-saude');
        const ctText  = document.getElementById('callout-saude-text');
        if (callout && ctText) {
            callout.style.display = 'block';
            const oms = num > 10;
            ctText.innerHTML = `Mortalidade infantil de <strong>${fmtNum(num,1)} por 1.000 nascidos vivos</strong> (${mort.year || '—'}). `
                + (oms
                    ? '<strong>Acima da meta OMS (< 10/mil).</strong> Projetos de saúde materno-infantil, pré-natal, nutrição e vigilância sanitária têm alto impacto neste contexto.'
                    : 'Dentro da meta OMS (< 10/mil). Foco em manutenção e prevenção — especialmente em territórios periféricos do município.');
        }
    } else { setKpiND('mortalidade'); }

    // Óbitos registrados (SIDRA Registro Civil)
    const ob = raw['obitos'];
    if (ob && ob.value != null) {
        const num = numBR(ob.value);
        setKpi('obitos', fmtNum(num, 0), ob.year || '', null, null, null);
    } else { setKpiND('obitos'); }
}

// ── Economia ──────────────────────────────────────────────
function renderEconomia(raw) {
    // PIB per capita — indicador 47001, retorna em R$/ano
    const pib = raw['pib'];
    if (pib && pib.value != null) {
        const num = numBR(pib.value);
        // Barra proporcional: referência R$ 100k = 100%
        setKpi('pib', 'R$ ' + fmtNum(num, 0), pib.year || '', null, Math.min((num / 100000) * 100, 100), '#D97706');
    } else { setKpiND('pib'); }

    const den = raw['densidade'];
    if (den && den.value != null) {
        const num = numBR(den.value);
        setKpi('densidade', fmtNum(num, 1) + ' hab/km²', den.year || '', null, null, null);
    } else { setKpiND('densidade'); }

    const ar = raw['area'];
    if (ar && ar.value != null) {
        const num = numBR(ar.value);
        setKpi('area', fmtNum(num, 2) + ' km²', ar.year || '', null, null, null);
    } else { setKpiND('area'); }
}

// ── Reset ─────────────────────────────────────────────────
function resetAll() {
    ['educacao','saneamento','fora-pct','fora-est','mortalidade','obitos','pib','densidade','area']
        .forEach(k => {
            const ve = document.getElementById(`kv-${k}`);
            const ye = document.getElementById(`ky-${k}`);
            const be = document.getElementById(`kb-${k}`);
            if (ve) { ve.className = 'kpi-val'; ve.textContent = '—'; }
            if (ye) ye.textContent = '';
            if (be) be.style.width = '0%';
        });
    ['sv-populacao','sv-area','sv-mortalidade','sv-pib'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '—'; });
    ['callout-educacao','callout-saude'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
}

// ── Toast ─────────────────────────────────────────────────
function toast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `it-toast ${type}`;
    el.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span><span>${msg}</span>`;
    document.getElementById('it-toasts').appendChild(el);
    setTimeout(() => el.remove(), 4500);
}
</script>
@endpush
@endsection
