@extends('layouts.app', ['title' => 'Inteligência Territorial'])

@push('styles')
<style>
/* ═══════════════════════════════════════════════════
   INTELIGÊNCIA TERRITORIAL — Premium UX
   ═══════════════════════════════════════════════════ */
.it-wrapper { background: #f8fafc; min-height: 100vh; padding: 0; }

/* ── Page header ──────────────────────────────────── */
.it-page-header {
    background: #fff;
    border-bottom: 1px solid #EAECF0;
    padding: 22px 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.it-page-header h1 { font-size: 1.35rem; font-weight: 800; color: #101828; margin: 0; letter-spacing: -0.3px; }
.it-page-header h1 span { color: var(--primary-color, #4F46E5); }
.it-page-header p  { color: #667085; margin: 3px 0 0; font-size: 0.82rem; }
.it-header-badges  { display: flex; gap: 8px; flex-wrap: wrap; }
.it-badge {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 12px;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px;
}
.it-badge.green  { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
.it-badge.indigo { background: #F4F3FF; color: #4338CA; border: 1px solid #C7D2FE; }

/* ── Content ──────────────────────────────────────── */
.it-content { padding: 28px 36px; }

/* ── Search card ──────────────────────────────────── */
.it-search-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-top: 4px solid var(--primary-color, #4F46E5);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 8px 24px rgba(16,24,40,.05);
}
.it-search-wrap { position: relative; display: flex; gap: 10px; }
.it-search-icon-left {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: #98A2B3; font-size: 0.95rem; pointer-events: none; z-index: 1;
}
.it-search-input {
    flex: 1; background: #F9FAFB; border: 1.5px solid #D0D5DD;
    border-radius: 12px; padding: 14px 20px 14px 44px;
    color: #101828; font-size: 0.95rem; outline: none; font-family: inherit;
    transition: all .2s;
}
.it-search-input::placeholder { color: #98A2B3; }
.it-search-input:focus {
    background: #fff; border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 0 0 4px rgba(79,70,229,.08);
}
.it-btn-search {
    background: var(--primary-color, #4F46E5); color: #fff; border: none;
    border-radius: 12px; padding: 14px 26px; font-weight: 700; font-size: 0.88rem;
    cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 7px;
    transition: all .2s; box-shadow: 0 4px 14px rgba(79,70,229,.3);
}
.it-btn-search:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.it-btn-search:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* ── Autocomplete ──────────────────────────────────── */
.it-autocomplete {
    position: absolute; top: calc(100% + 6px); left: 0; right: 0;
    background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(16,24,40,.1); z-index: 1000; display: none; overflow: hidden;
}
.it-ac-item {
    display: flex; align-items: center; gap: 12px; padding: 11px 16px;
    cursor: pointer; border-bottom: 1px solid #F9FAFB; transition: background .12s;
}
.it-ac-item:last-child { border-bottom: none; }
.it-ac-item:hover, .it-ac-item.active { background: #F4F3FF; }
.it-ac-item:hover .ac-i, .it-ac-item.active .ac-i { color: var(--primary-color, #4F46E5); }
.ac-i   { color: #98A2B3; font-size: 0.85rem; flex-shrink: 0; }
.ac-city{ font-weight: 700; font-size: 0.88rem; color: #101828; }
.ac-uf  { font-size: 0.75rem; color: #667085; }
.ac-tag {
    margin-left: auto; background: #F2F4F7; color: #475467;
    padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 700;
}

/* ── City header bar ──────────────────────────────── */
.it-city-bar {
    display: flex; align-items: flex-end; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 12px; padding: 0 2px;
}
.it-city-name { font-size: 1.5rem; font-weight: 800; color: #101828; margin: 0; letter-spacing: -.4px; }
.it-city-meta { font-size: .75rem; color: #667085; margin-top: 3px; }
.it-cache-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: #F9FAFB; border: 1px solid #EAECF0; border-radius: 8px;
    padding: 5px 12px; font-size: .72rem; color: #667085; font-weight: 500;
    white-space: nowrap;
}

/* ── KPI cards grid ───────────────────────────────── */
.it-kpi {
    background: #fff; border: 1px solid #EAECF0; border-radius: 14px;
    padding: 20px 22px; height: 100%;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.04);
    transition: transform .2s, box-shadow .2s, border-color .2s;
}
.it-kpi:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(16,24,40,.08), 0 12px 28px rgba(16,24,40,.06);
}
.it-kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; margin-bottom: 14px;
}
.it-kpi-label { font-size: .68rem; font-weight: 700; color: #98A2B3; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.it-kpi-value { font-size: 1.5rem; font-weight: 900; color: #101828; letter-spacing: -.4px; line-height: 1.1; margin-bottom: 6px; }
.it-kpi-value.nd { font-size: 1rem; color: #C0C7D2; font-weight: 600; }
.it-kpi-foot  { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.it-year-tag  {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .65rem; font-weight: 700; border-radius: 5px; padding: 2px 7px;
}
.it-kpi-bar   { height: 3px; background: #F2F4F7; border-radius: 10px; overflow: hidden; margin-top: 10px; }
.it-kpi-bar-fill { height: 100%; border-radius: 10px; transition: width 1.2s cubic-bezier(.4,0,.2,1); width: 0; }

/* IDHM badge */
.idhm-badge {
    font-size: .65rem; font-weight: 700; border-radius: 20px;
    padding: 2px 8px; white-space: nowrap;
}

/* ── Skeleton ─────────────────────────────────────── */
.sk { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size: 400px 100%; animation: sk 1.4s infinite linear; border-radius: 6px; }
@keyframes sk { 0%{background-position:-400px 0} 100%{background-position:400px 0} }

/* ── AI card ──────────────────────────────────────── */
.it-ai-card {
    background: #fff; border: 1px solid #EAECF0; border-radius: 14px;
    padding: 24px; height: 100%;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.04);
}
.it-ai-head  { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.it-ai-ava   {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--primary-color,#4F46E5); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(79,70,229,.3);
}
.it-ai-name  { font-weight: 800; font-size: .9rem; color: #101828; margin: 0; }
.it-ai-sub   { font-size: .68rem; color: #12B76A; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
.it-ai-body  { font-size: .875rem; line-height: 1.8; color: #344054; min-height: 100px; }
.it-ai-body.empty { color: #98A2B3; font-style: italic; }
.it-ai-disc  {
    margin-top: 14px; padding-top: 12px; border-top: 1px solid #F2F4F7;
    font-size: .7rem; color: #98A2B3; display: flex; align-items: flex-start; gap: 6px;
}

/* ── Bars card ────────────────────────────────────── */
.it-bars-card {
    background: #fff; border: 1px solid #EAECF0; border-radius: 14px;
    padding: 24px; height: 100%;
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.04);
}
.it-bars-title { font-size: .9rem; font-weight: 700; color: #101828; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.it-bars-title i { color: var(--primary-color,#4F46E5); }
.it-bar-row   { margin-bottom: 16px; }
.it-bar-meta  { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
.it-bar-name  { font-size: .78rem; font-weight: 600; color: #344054; display: flex; align-items: center; gap: 7px; }
.it-bar-val   { font-size: .8rem; font-weight: 700; color: #101828; }
.it-bar-track { height: 8px; background: #F2F4F7; border-radius: 10px; overflow: hidden; }
.it-bar-fill  { height: 100%; border-radius: 10px; transition: width 1.3s cubic-bezier(.4,0,.2,1); width: 0; }
.it-bars-note { margin-top: 18px; padding-top: 14px; border-top: 1px solid #F2F4F7; font-size: .7rem; color: #98A2B3; }

/* ── Empty state cards ────────────────────────────── */
.it-info-card {
    background: #fff; border: 1px solid #EAECF0; border-radius: 14px;
    padding: 24px; box-shadow: 0 1px 3px rgba(16,24,40,.06);
}
.it-info-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: .95rem; }

/* ── Toast ────────────────────────────────────────── */
#it-toasts { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.it-toast {
    background: #fff; border: 1px solid #EAECF0; border-radius: 12px;
    padding: 12px 18px; font-size: .84rem; font-weight: 500; color: #101828;
    display: flex; align-items: center; gap: 10px; min-width: 260px; max-width: 360px;
    box-shadow: 0 4px 12px rgba(16,24,40,.1);
    animation: toastIn .3s cubic-bezier(.34,1.56,.64,1);
}
.it-toast.error   { border-left: 3px solid #F04438; }
.it-toast.success { border-left: 3px solid #12B76A; }
@keyframes toastIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
</style>
@endpush

@section('content')
<div class="it-wrapper">

    {{-- Page header --}}
    <div class="it-page-header">
        <div>
            <h1><i class="fas fa-map-location-dot" style="color:var(--primary-color,#4F46E5);margin-right:10px"></i>Inteligência <span>Territorial</span></h1>
            <p>Diagnóstico socioeconômico por município com dados oficiais do IBGE</p>
        </div>
        <div class="it-header-badges">
            <span class="it-badge green"><i class="fas fa-database"></i> IBGE Cidades</span>
            <span class="it-badge indigo"><i class="fas fa-robot"></i> Bruce AI</span>
        </div>
    </div>

    <div class="it-content">

        {{-- Search card --}}
        <div class="it-search-card">
            <p style="font-size:.82rem;font-weight:700;color:#344054;margin-bottom:10px;display:flex;align-items:center;gap:8px">
                <i class="fas fa-magnifying-glass-location" style="color:var(--primary-color,#4F46E5)"></i>
                Qual município você quer analisar?
            </p>
            <div class="it-search-wrap">
                <i class="it-search-icon-left fas fa-search"></i>
                <input type="text" id="citySearch" class="it-search-input"
                    placeholder="Digite o nome da cidade... ex: Araraquara"
                    autocomplete="off" aria-label="Buscar município">
                <div id="it-autocomplete" class="it-autocomplete"></div>
                <button id="btnAnalyze" class="it-btn-search" onclick="triggerSearch()" disabled>
                    <i class="fas fa-chart-bar" id="btnIcon"></i>
                    <span id="btnText">Analisar</span>
                </button>
            </div>
            <p style="font-size:.72rem;color:#98A2B3;margin:10px 0 0">
                <i class="fas fa-circle-info me-1"></i>
                Dados de: <strong>Censo 2022</strong>, <strong>PNAD</strong>, <strong>IDH 2010</strong> e outros — Fonte oficial IBGE
            </p>
        </div>

        {{-- Results --}}
        <div id="resultsArea" style="display:none">

            {{-- City bar --}}
            <div class="it-city-bar">
                <div>
                    <h2 class="it-city-name" id="cityNameDisplay">—</h2>
                    <p class="it-city-meta">Fonte: IBGE Cidades · Censo Demográfico e Pesquisas Nacionais</p>
                </div>
                <div class="it-cache-pill">
                    <i class="fas fa-clock-rotate-left"></i>
                    Consultado em <strong id="cacheDate">—</strong>
                </div>
            </div>

            {{-- 8 KPI cards: 4 cols desktop, 2 cols mobile --}}
            <div class="row g-3 mb-4" id="kpiGrid">

                {{-- 1 - Population --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#EFF6FF;color:#1D4ED8"><i class="fas fa-users"></i></div>
                        <div class="it-kpi-label">População</div>
                        <div class="it-kpi-value" id="kv-populacao">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-populacao" style="background:#EFF6FF;color:#1D4ED8"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-populacao" style="background:#3B82F6"></div></div>
                    </div>
                </div>

                {{-- 2 - Area --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#F0FDF4;color:#166534"><i class="fas fa-map"></i></div>
                        <div class="it-kpi-label">Área Territorial</div>
                        <div class="it-kpi-value" id="kv-area">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-area" style="background:#F0FDF4;color:#166534"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-area" style="background:#16A34A"></div></div>
                    </div>
                </div>

                {{-- 3 - Density --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#FFF7ED;color:#92400E"><i class="fas fa-city"></i></div>
                        <div class="it-kpi-label">Densidade Demogr.</div>
                        <div class="it-kpi-value" id="kv-densidade">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-densidade" style="background:#FFF7ED;color:#92400E"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-densidade" style="background:#D97706"></div></div>
                    </div>
                </div>

                {{-- 4 - PIB per capita --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#ECFDF5;color:#065F46"><i class="fas fa-hand-holding-dollar"></i></div>
                        <div class="it-kpi-label">PIB per capita</div>
                        <div class="it-kpi-value" id="kv-pib">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-pib" style="background:#ECFDF5;color:#065F46"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-pib" style="background:#059669"></div></div>
                    </div>
                </div>

                {{-- 5 - IDHM --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#F5F3FF;color:#5B21B6"><i class="fas fa-star-half-stroke"></i></div>
                        <div class="it-kpi-label">IDHM</div>
                        <div class="it-kpi-value" id="kv-idhm">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-idhm" style="background:#F5F3FF;color:#5B21B6"></span>
                            <span class="idhm-badge" id="idhm-cat"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-idhm" style="background:#7C3AED"></div></div>
                    </div>
                </div>

                {{-- 6 - Infant mortality --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#FFF1F2;color:#9F1239"><i class="fas fa-heart-pulse"></i></div>
                        <div class="it-kpi-label">Mortalidade Infantil</div>
                        <div class="it-kpi-value" id="kv-mortalidade">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-mortalidade" style="background:#FFF1F2;color:#9F1239"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-mortalidade" style="background:#E11D48"></div></div>
                    </div>
                </div>

                {{-- 7 - Education --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#FFFBEB;color:#78350F"><i class="fas fa-graduation-cap"></i></div>
                        <div class="it-kpi-label">Escolarização 6–14</div>
                        <div class="it-kpi-value" id="kv-educacao">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-educacao" style="background:#FFFBEB;color:#78350F"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-educacao" style="background:#B45309"></div></div>
                    </div>
                </div>

                {{-- 8 - Sanitation --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi">
                        <div class="it-kpi-icon" style="background:#ECFEFF;color:#155E75"><i class="fas fa-faucet-drip"></i></div>
                        <div class="it-kpi-label">Saneamento Adequado</div>
                        <div class="it-kpi-value" id="kv-saneamento">—</div>
                        <div class="it-kpi-foot">
                            <span class="it-year-tag" id="ky-saneamento" style="background:#ECFEFF;color:#155E75"></span>
                        </div>
                        <div class="it-kpi-bar"><div class="it-kpi-bar-fill" id="kb-saneamento" style="background:#0E7490"></div></div>
                    </div>
                </div>

            </div>{{-- /kpiGrid --}}

            {{-- Bottom row: AI + chart bars --}}
            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="it-ai-card">
                        <div class="it-ai-head">
                            <div class="it-ai-ava"><i class="fas fa-robot"></i></div>
                            <div>
                                <p class="it-ai-name">Análise Bruce AI</p>
                                <span class="it-ai-sub">Inteligência Estratégica</span>
                            </div>
                        </div>
                        <div class="it-ai-body empty" id="aiAnalysis">
                            Selecione um município para receber o diagnóstico estratégico baseado nos dados do IBGE.
                        </div>
                        <div class="it-ai-disc">
                            <i class="fas fa-shield-halved" style="color:#12B76A;margin-top:1px;flex-shrink:0"></i>
                            Gerado por IA com dados públicos do IBGE. Não substitui estudo técnico especializado.
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="it-bars-card">
                        <div class="it-bars-title">
                            <i class="fas fa-chart-bar"></i> Indicadores em Escala
                        </div>
                        <div id="barsPlaceholder" style="text-align:center;padding:40px 0;color:#C0C7D2">
                            <i class="fas fa-chart-bar" style="font-size:2.5rem;opacity:.25;display:block;margin-bottom:12px"></i>
                            <span style="font-size:.82rem">Os gráficos aparecerão após a análise.</span>
                        </div>
                        <div id="barsContent" style="display:none">
                            <div class="it-bar-row">
                                <div class="it-bar-meta">
                                    <span class="it-bar-name"><i class="fas fa-star-half-stroke" style="color:#7C3AED"></i> IDHM</span>
                                    <span class="it-bar-val" id="bv-idhm">—</span>
                                </div>
                                <div class="it-bar-track"><div class="it-bar-fill" id="bf-idhm" style="background:#7C3AED"></div></div>
                            </div>
                            <div class="it-bar-row">
                                <div class="it-bar-meta">
                                    <span class="it-bar-name"><i class="fas fa-graduation-cap" style="color:#B45309"></i> Escolarização</span>
                                    <span class="it-bar-val" id="bv-educacao">—</span>
                                </div>
                                <div class="it-bar-track"><div class="it-bar-fill" id="bf-educacao" style="background:#B45309"></div></div>
                            </div>
                            <div class="it-bar-row">
                                <div class="it-bar-meta">
                                    <span class="it-bar-name"><i class="fas fa-faucet-drip" style="color:#0E7490"></i> Saneamento</span>
                                    <span class="it-bar-val" id="bv-saneamento">—</span>
                                </div>
                                <div class="it-bar-track"><div class="it-bar-fill" id="bf-saneamento" style="background:#0E7490"></div></div>
                            </div>
                            <div class="it-bar-row">
                                <div class="it-bar-meta">
                                    <span class="it-bar-name"><i class="fas fa-hand-holding-dollar" style="color:#059669"></i> PIB per capita (ref. R$100k)</span>
                                    <span class="it-bar-val" id="bv-pib">—</span>
                                </div>
                                <div class="it-bar-track"><div class="it-bar-fill" id="bf-pib" style="background:#059669"></div></div>
                            </div>
                            <p class="it-bars-note">
                                <i class="fas fa-info-circle me-1"></i>
                                IDHM e indicadores % mostrados em escala 0–100. PIB per capita normalizado (máx. referência: R$ 100.000).
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>{{-- /resultsArea --}}

        {{-- Empty state --}}
        <div id="emptyState">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="it-info-card">
                        <div class="it-info-icon" style="background:#EFF6FF;color:#1D4ED8"><i class="fas fa-users"></i></div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Dados Populacionais</h6>
                        <p style="font-size:.8rem;color:#667085;margin:0">População, área territorial e densidade demográfica — Censo IBGE 2022.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="it-info-card">
                        <div class="it-info-icon" style="background:#F5F3FF;color:#5B21B6"><i class="fas fa-star-half-stroke"></i></div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">IDHM & PIB per capita</h6>
                        <p style="font-size:.8rem;color:#667085;margin:0">Índice de Desenvolvimento Humano e riqueza econômica do município.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="it-info-card">
                        <div class="it-info-icon" style="background:#F0FDF4;color:#166534"><i class="fas fa-robot"></i></div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Diagnóstico Bruce AI</h6>
                        <p style="font-size:.8rem;color:#667085;margin:0">Análise estratégica automática dos desafios sociais e oportunidades para o terceiro setor.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /it-content --}}
</div>

<div id="it-toasts"></div>

@push('scripts')
<script>
const ROUTE_CITIES     = '{{ route("ibge.cities.search") }}';
const ROUTE_INDICATORS = (code, name) => `/api/ibge/indicators/${code}?city_name=${encodeURIComponent(name)}`;

let selectedCity = null, searchTimer = null, focusIdx = -1;

const cityInput = document.getElementById('citySearch');
const acBox     = document.getElementById('it-autocomplete');
const btnAna    = document.getElementById('btnAnalyze');

// ── Autocomplete ────────────────────────────────────
cityInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    selectedCity = null; btnAna.disabled = true; focusIdx = -1;
    const q = this.value.trim();
    if (q.length < 3) { acBox.style.display = 'none'; return; }
    searchTimer = setTimeout(async () => {
        try {
            const data = await (await fetch(`${ROUTE_CITIES}?q=${encodeURIComponent(q)}`)).json();
            renderAC(data);
        } catch { acBox.style.display = 'none'; }
    }, 280);
});

function getUF(city) {
    return city['UF']?.sigla || city.microrregiao?.mesorregiao?.UF?.sigla || '';
}
function getState(city) {
    return city['UF']?.nome || city.microrregiao?.mesorregiao?.UF?.nome || '';
}

function renderAC(cities) {
    acBox.innerHTML = '';
    if (!cities.length) { acBox.style.display = 'none'; return; }
    cities.forEach(c => {
        const el = document.createElement('div');
        el.className = 'it-ac-item';
        el.innerHTML = `<i class="fas fa-location-dot ac-i"></i>
            <div><div class="ac-city">${c.nome}</div><div class="ac-uf">${getState(c)}</div></div>
            <span class="ac-tag">${getUF(c)}</span>`;
        el.addEventListener('click', () => pickCity(c));
        acBox.appendChild(el);
    });
    acBox.style.display = 'block';
}

function pickCity(c) {
    selectedCity = c;
    cityInput.value = `${c.nome} — ${getUF(c)}`;
    acBox.style.display = 'none';
    btnAna.disabled = false;
    btnAna.focus();
}

cityInput.addEventListener('keydown', e => {
    const items = [...acBox.querySelectorAll('.it-ac-item')];
    if (!items.length) return;
    if (e.key === 'ArrowDown')  { e.preventDefault(); focusIdx = Math.min(focusIdx+1, items.length-1); }
    else if (e.key === 'ArrowUp')   { e.preventDefault(); focusIdx = Math.max(focusIdx-1, 0); }
    else if (e.key === 'Enter')     { if (focusIdx >= 0) { items[focusIdx].click(); return; } if (selectedCity) triggerSearch(); return; }
    else if (e.key === 'Escape')    { acBox.style.display = 'none'; return; }
    items.forEach((el, i) => el.classList.toggle('active', i === focusIdx));
});

document.addEventListener('click', e => {
    if (!cityInput.contains(e.target) && !acBox.contains(e.target)) acBox.style.display = 'none';
});

// ── Trigger ─────────────────────────────────────────
async function triggerSearch() {
    if (!selectedCity) { toast('Selecione uma cidade da lista.', 'error'); return; }

    const name = selectedCity.nome;
    const uf   = getUF(selectedCity);
    const code = selectedCity.id;

    document.getElementById('emptyState').style.display  = 'none';
    document.getElementById('resultsArea').style.display = 'block';
    document.getElementById('cityNameDisplay').textContent = `${name}${uf ? ', '+uf : ''}`;
    document.getElementById('cacheDate').textContent = '—';

    resetKpis();
    const ai = document.getElementById('aiAnalysis');
    ai.className = 'it-ai-body';
    ai.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="color:var(--primary-color,#4F46E5);margin-right:8px"></i> Bruce AI está processando os dados do IBGE…';

    const btn  = btnAna;
    const icon = document.getElementById('btnIcon');
    const txt  = document.getElementById('btnText');
    btn.disabled = true; icon.className = 'fas fa-circle-notch fa-spin'; txt.textContent = 'Analisando…';

    try {
        const resp = await fetch(ROUTE_INDICATORS(code, name));
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const data = await resp.json();

        renderKpis(data.indicators);
        renderBars(data.indicators);

        ai.className   = 'it-ai-body';
        ai.textContent = data.analysis;
        document.getElementById('cacheDate').textContent = new Date().toLocaleDateString('pt-BR');
        toast(`Análise de ${name} concluída!`, 'success');

    } catch (err) {
        console.error(err);
        ai.className   = 'it-ai-body';
        ai.textContent = 'Erro ao buscar dados. Verifique sua conexão e tente novamente.';
        toast('Falha ao buscar indicadores.', 'error');
    } finally {
        btn.disabled = false; icon.className = 'fas fa-chart-bar'; txt.textContent = 'Analisar';
    }
}

// ── KPI rendering ────────────────────────────────────
const KPI_CFG = {
    populacao:   { fmt: v => Math.round(v).toLocaleString('pt-BR') + ' hab.',   maxRef: 15000000 },
    area:        { fmt: v => v.toLocaleString('pt-BR', {maximumFractionDigits:0}) + ' km²',     maxRef: 1500000 },
    densidade:   { fmt: v => v.toLocaleString('pt-BR', {maximumFractionDigits:2}) + ' hab/km²', maxRef: 20000 },
    pib:         { fmt: v => 'R$ ' + Math.round(v).toLocaleString('pt-BR'),     maxRef: 100000 },
    idhm:        { fmt: v => parseFloat(v).toFixed(3),                          maxRef: 1 },
    mortalidade: { fmt: v => parseFloat(v).toFixed(1) + ' / mil',               maxRef: 50, inverse: true },
    educacao:    { fmt: v => parseFloat(v).toFixed(1) + '%',                    maxRef: 100 },
    saneamento:  { fmt: v => parseFloat(v).toFixed(1) + '%',                    maxRef: 100 },
};

function numBR(str) {
    if (str == null) return NaN;
    // Handle Brazilian number format: "1.234,56" → 1234.56 or "1234.56"
    const s = String(str).replace(/\s/g,'');
    if (/^\d{1,3}(\.\d{3})*(,\d+)?$/.test(s)) return parseFloat(s.replace(/\./g,'').replace(',','.'));
    return parseFloat(s);
}

function resetKpis() {
    Object.keys(KPI_CFG).forEach(k => {
        const ve = document.getElementById(`kv-${k}`);
        const ye = document.getElementById(`ky-${k}`);
        const be = document.getElementById(`kb-${k}`);
        if (ve) { ve.className = 'it-kpi-value'; ve.textContent = '—'; }
        if (ye) ye.textContent = '';
        if (be) be.style.width = '0%';
    });
    const ic = document.getElementById('idhm-cat');
    if (ic) { ic.textContent = ''; ic.style.background = ''; ic.style.color = ''; }
}

function renderKpis(indicators) {
    Object.entries(KPI_CFG).forEach(([key, cfg]) => {
        const ind  = indicators[key];
        const ve   = document.getElementById(`kv-${key}`);
        const ye   = document.getElementById(`ky-${key}`);
        const be   = document.getElementById(`kb-${key}`);
        if (!ve) return;

        if (ind && ind.value != null) {
            const num = numBR(ind.value);
            if (isNaN(num)) { ve.className = 'it-kpi-value nd'; ve.textContent = 'N/D'; return; }

            ve.className   = 'it-kpi-value';
            ve.textContent = cfg.fmt(num);
            if (ye) ye.textContent = ind.year ? `${key === 'populacao' ? 'Censo' : ''} ${ind.year}`.trim() : '';

            // Progress bar
            if (be) {
                let pct = Math.min((num / cfg.maxRef) * 100, 100);
                if (cfg.inverse) pct = Math.max(100 - pct, 0); // lower is better
                setTimeout(() => { be.style.width = pct + '%'; }, 120);
            }

            // IDHM category badge
            if (key === 'idhm') {
                const cat  = num >= 0.8 ? ['Muito Alto','#4338CA','#EDE9FE'] : num >= 0.7 ? ['Alto','#166534','#F0FDF4'] : num >= 0.55 ? ['Médio','#92400E','#FFFBEB'] : ['Baixo','#9F1239','#FFF1F2'];
                const ic   = document.getElementById('idhm-cat');
                if (ic) { ic.textContent = cat[0]; ic.style.color = cat[1]; ic.style.background = cat[2]; }
            }
        } else {
            ve.className   = 'it-kpi-value nd';
            ve.textContent = 'N/D';
            if (ye) ye.textContent = 'Sem dados';
        }
    });
}

// ── Bar chart rendering ──────────────────────────────
function renderBars(indicators) {
    document.getElementById('barsPlaceholder').style.display = 'none';
    document.getElementById('barsContent').style.display     = 'block';

    const BAR_CFG = {
        idhm:      { maxRef: 1,   fmt: v => parseFloat(v).toFixed(3) },
        educacao:  { maxRef: 100, fmt: v => parseFloat(v).toFixed(1) + '%' },
        saneamento:{ maxRef: 100, fmt: v => parseFloat(v).toFixed(1) + '%' },
        pib:       { maxRef: 100000, fmt: v => 'R$ ' + Math.round(v).toLocaleString('pt-BR') },
    };

    Object.entries(BAR_CFG).forEach(([key, cfg]) => {
        const ind = indicators[key];
        const vEl = document.getElementById(`bv-${key}`);
        const bEl = document.getElementById(`bf-${key}`);
        if (!vEl || !bEl) return;

        if (ind && ind.value != null) {
            const num = numBR(ind.value);
            if (!isNaN(num)) {
                vEl.textContent = cfg.fmt(num);
                const pct = Math.min((num / cfg.maxRef) * 100, 100);
                setTimeout(() => { bEl.style.width = pct + '%'; }, 200);
                return;
            }
        }
        vEl.textContent = 'N/D';
    });
}

// ── Toast ────────────────────────────────────────────
function toast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `it-toast ${type}`;
    el.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span><span>${msg}</span>`;
    document.getElementById('it-toasts').appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
</script>
@endpush

@endsection
