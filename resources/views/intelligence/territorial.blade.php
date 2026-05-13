@extends('layouts.app', ['title' => 'Inteligência Territorial'])

@push('styles')
<style>
/* ════════════════════════════════════════════════
   INTELIGÊNCIA TERRITORIAL — Premium UX Design
   ════════════════════════════════════════════════ */

/* ── Page shell ─────────────────────────────────── */
.it-wrapper { background: #f8fafc; min-height: 100vh; padding: 0; }

/* ── Page header ─────────────────────────────────── */
.it-page-header {
    background: #fff;
    border-bottom: 1px solid #EAECF0;
    padding: 24px 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.it-page-header h1 {
    font-size: 1.4rem;
    font-weight: 800;
    color: #101828;
    margin: 0;
    letter-spacing: -0.3px;
}
.it-page-header h1 span { color: var(--primary-color, #4F46E5); }
.it-page-header p { color: #667085; margin: 2px 0 0; font-size: 0.85rem; }
.it-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F0FDF4;
    color: #166534;
    border: 1px solid #BBF7D0;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* ── Content area ────────────────────────────────── */
.it-content { padding: 32px 36px; }

/* ── Search card ─────────────────────────────────── */
.it-search-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-top: 4px solid var(--primary-color, #4F46E5);
    border-radius: 16px;
    padding: 32px 36px;
    margin-bottom: 32px;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 8px 24px rgba(16,24,40,0.05);
}
.it-search-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #344054;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.it-search-label i { color: var(--primary-color, #4F46E5); }
.it-search-row {
    display: flex;
    gap: 10px;
    position: relative;
}
.it-search-input {
    flex: 1;
    background: #F9FAFB;
    border: 1.5px solid #D0D5DD;
    border-radius: 12px;
    padding: 14px 20px 14px 44px;
    color: #101828;
    font-size: 1rem;
    outline: none;
    transition: all 0.2s;
    font-family: inherit;
}
.it-search-input::placeholder { color: #98A2B3; }
.it-search-input:focus {
    background: #fff;
    border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
}
.it-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #98A2B3;
    font-size: 0.95rem;
    pointer-events: none;
}
.it-btn-search {
    background: var(--primary-color, #4F46E5);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 14px 28px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(79,70,229,0.3);
}
.it-btn-search:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.it-btn-search:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

/* ── Autocomplete dropdown ───────────────────────── */
.it-autocomplete {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 80px;
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(16,24,40,0.08), 0 16px 40px rgba(16,24,40,0.06);
    z-index: 1000;
    display: none;
    overflow: hidden;
}
.it-autocomplete-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    cursor: pointer;
    border-bottom: 1px solid #F9FAFB;
    transition: background 0.12s;
}
.it-autocomplete-item:last-child { border-bottom: none; }
.it-autocomplete-item:hover, .it-autocomplete-item.active {
    background: #F4F3FF;
}
.it-autocomplete-item:hover .ac-icon, .it-autocomplete-item.active .ac-icon {
    color: var(--primary-color, #4F46E5);
}
.ac-icon { color: #98A2B3; font-size: 0.9rem; flex-shrink: 0; }
.ac-city { font-weight: 700; font-size: 0.9rem; color: #101828; }
.ac-state { font-size: 0.78rem; color: #667085; margin-top: 1px; }
.ac-badge {
    margin-left: auto;
    background: #F2F4F7;
    color: #475467;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 700;
    flex-shrink: 0;
}

/* ── City info bar ───────────────────────────────── */
.it-city-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
    padding: 0 2px;
}
.it-city-name {
    font-size: 1.6rem;
    font-weight: 800;
    color: #101828;
    letter-spacing: -0.5px;
    margin: 0;
}
.it-city-sub {
    font-size: 0.78rem;
    color: #667085;
    margin: 2px 0 0;
}
.it-cache-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F9FAFB;
    border: 1px solid #EAECF0;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.75rem;
    color: #667085;
    font-weight: 500;
}

/* ── KPI Cards ───────────────────────────────────── */
.it-kpi-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    padding: 24px;
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 4px 12px rgba(16,24,40,0.04);
}
.it-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(16,24,40,0.08), 0 12px 32px rgba(16,24,40,0.06);
}
.it-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 16px;
}
.it-kpi-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #98A2B3;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}
.it-kpi-value {
    font-size: 1.7rem;
    font-weight: 900;
    color: #101828;
    letter-spacing: -0.5px;
    line-height: 1;
    margin-bottom: 8px;
    min-height: 2rem;
}
.it-kpi-year {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 6px;
    padding: 3px 8px;
}
.it-kpi-progress {
    margin-top: 14px;
    height: 4px;
    background: #F2F4F7;
    border-radius: 10px;
    overflow: hidden;
}
.it-kpi-progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 1s ease;
    width: 0%;
}

/* Skeleton ─────────────────────────────────────────*/
.it-skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400px 100%;
    animation: skShimmer 1.4s infinite linear;
    border-radius: 6px;
}
@keyframes skShimmer {
    0%   { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}
.it-kpi-value.loading { height: 2rem; border-radius: 6px; }
.it-kpi-year.loading  { height: 20px; width: 70px; }

/* ── AI Analysis card ────────────────────────────── */
.it-ai-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    padding: 28px;
    height: 100%;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 4px 12px rgba(16,24,40,0.04);
}
.it-ai-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.it-ai-avatar {
    width: 46px;
    height: 46px;
    background: var(--primary-color, #4F46E5);
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(79,70,229,0.3);
}
.it-ai-title { font-weight: 800; font-size: 0.95rem; color: #101828; margin: 0; }
.it-ai-sub { font-size: 0.7rem; color: #12B76A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
.it-ai-text {
    font-size: 0.9rem;
    line-height: 1.75;
    color: #344054;
    min-height: 120px;
}
.it-ai-text.placeholder { color: #98A2B3; font-style: italic; }

/* ── Chart card ──────────────────────────────────── */
.it-chart-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    padding: 28px;
    height: 100%;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 4px 12px rgba(16,24,40,0.04);
}
.it-chart-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #101828;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.it-chart-title i { color: var(--primary-color, #4F46E5); }
.it-bar-row {
    margin-bottom: 18px;
}
.it-bar-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}
.it-bar-name { font-size: 0.8rem; font-weight: 600; color: #344054; display: flex; align-items: center; gap: 7px; }
.it-bar-val  { font-size: 0.8rem; font-weight: 700; color: #101828; }
.it-bar-track {
    height: 8px;
    background: #F2F4F7;
    border-radius: 10px;
    overflow: hidden;
}
.it-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 1.2s cubic-bezier(0.4,0,0.2,1);
    width: 0%;
}

/* ── Empty / Error state ─────────────────────────── */
.it-placeholder-state {
    text-align: center;
    padding: 60px 20px;
    color: #98A2B3;
}
.it-placeholder-state .big-icon {
    font-size: 3.5rem;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* ── Toast ───────────────────────────────────────── */
#it-toast-wrap {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.it-toast {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 12px;
    padding: 13px 18px;
    color: #101828;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 280px;
    max-width: 360px;
    box-shadow: 0 4px 12px rgba(16,24,40,0.1);
    animation: toastIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
.it-toast.error   { border-left: 3px solid #F04438; }
.it-toast.success { border-left: 3px solid #12B76A; }
@keyframes toastIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
@endpush

@section('content')
<div class="it-wrapper">

    {{-- ── PAGE HEADER ─────────────────────────────── --}}
    <div class="it-page-header">
        <div>
            <h1><i class="fas fa-map-location-dot" style="color:var(--primary-color,#4F46E5);margin-right:10px"></i>Inteligência <span>Territorial</span></h1>
            <p>Diagnóstico socioeconômico por município via dados oficiais do IBGE</p>
        </div>
        <span class="it-badge">
            <i class="fas fa-database"></i> IBGE SIDRA
        </span>
    </div>

    <div class="it-content">

        {{-- ── SEARCH CARD ──────────────────────────── --}}
        <div class="it-search-card">
            <div class="it-search-label">
                <i class="fas fa-magnifying-glass-location"></i>
                Qual município você quer analisar?
            </div>
            <div class="it-search-row" style="position:relative">
                <i class="it-search-icon fas fa-search"></i>
                <input type="text" id="citySearch" class="it-search-input"
                    placeholder="Digite o nome da cidade... ex: Araraquara"
                    autocomplete="off"
                    aria-label="Buscar município">
                <div id="it-autocomplete" class="it-autocomplete"></div>
                <button id="btnAnalyze" class="it-btn-search" onclick="triggerSearch()" disabled>
                    <i class="fas fa-chart-bar" id="btnIcon"></i>
                    <span id="btnText">Analisar</span>
                </button>
            </div>
            <p style="font-size:0.75rem;color:#98A2B3;margin-top:12px;margin-bottom:0">
                <i class="fas fa-circle-info me-1"></i>
                Digite ao menos 3 letras para ver sugestões. Os dados vêm diretamente do IBGE SIDRA.
            </p>
        </div>

        {{-- ── RESULTS AREA ─────────────────────────── --}}
        <div id="resultsArea" style="display:none">

            {{-- City bar --}}
            <div class="it-city-bar">
                <div>
                    <h2 class="it-city-name" id="cityNameDisplay">—</h2>
                    <p class="it-city-sub">Fonte: IBGE SIDRA · Dados Censitários e PNAD</p>
                </div>
                <div class="it-cache-badge">
                    <i class="fas fa-clock-rotate-left"></i>
                    Atualizado em <strong id="cacheDate">—</strong>
                </div>
            </div>

            {{-- ── KPI CARDS ──────────────────────────── --}}
            <div class="row g-3 mb-4">
                {{-- Population --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi-card">
                        <div class="it-kpi-icon" style="background:#EFF6FF;color:#1D4ED8">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="it-kpi-label">População</div>
                        <div class="it-kpi-value" id="val-populacao">—</div>
                        <div class="it-kpi-year" id="year-populacao" style="background:#EFF6FF;color:#1D4ED8"></div>
                        <div class="it-kpi-progress">
                            <div class="it-kpi-progress-fill" id="bar-populacao" style="background:#1D4ED8"></div>
                        </div>
                    </div>
                </div>
                {{-- Income --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi-card">
                        <div class="it-kpi-icon" style="background:#F0FDF4;color:#166534">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                        <div class="it-kpi-label">Renda per capita</div>
                        <div class="it-kpi-value" id="val-renda">—</div>
                        <div class="it-kpi-year" id="year-renda" style="background:#F0FDF4;color:#166534"></div>
                        <div class="it-kpi-progress">
                            <div class="it-kpi-progress-fill" id="bar-renda" style="background:#166534"></div>
                        </div>
                    </div>
                </div>
                {{-- Education --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi-card">
                        <div class="it-kpi-icon" style="background:#FFFBEB;color:#92400E">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="it-kpi-label">Escolarização (6–14 anos)</div>
                        <div class="it-kpi-value" id="val-educacao">—</div>
                        <div class="it-kpi-year" id="year-educacao" style="background:#FFFBEB;color:#92400E"></div>
                        <div class="it-kpi-progress">
                            <div class="it-kpi-progress-fill" id="bar-educacao" style="background:#D97706"></div>
                        </div>
                    </div>
                </div>
                {{-- Sanitation --}}
                <div class="col-6 col-xl-3">
                    <div class="it-kpi-card">
                        <div class="it-kpi-icon" style="background:#ECFEFF;color:#155E75">
                            <i class="fas fa-faucet-drip"></i>
                        </div>
                        <div class="it-kpi-label">Saneamento Adequado</div>
                        <div class="it-kpi-value" id="val-saneamento">—</div>
                        <div class="it-kpi-year" id="year-saneamento" style="background:#ECFEFF;color:#155E75"></div>
                        <div class="it-kpi-progress">
                            <div class="it-kpi-progress-fill" id="bar-saneamento" style="background:#0E7490"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── BOTTOM ROW: AI + Chart ──────────────── --}}
            <div class="row g-4">

                {{-- AI Analysis --}}
                <div class="col-lg-6">
                    <div class="it-ai-card">
                        <div class="it-ai-header">
                            <div class="it-ai-avatar"><i class="fas fa-robot"></i></div>
                            <div>
                                <p class="it-ai-title">Análise Bruce AI</p>
                                <span class="it-ai-sub">Inteligência Estratégica</span>
                            </div>
                        </div>
                        <div class="it-ai-text placeholder" id="aiAnalysis">
                            Selecione um município para receber o diagnóstico automático baseado nos dados do IBGE.
                        </div>
                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #F2F4F7;display:flex;align-items:center;gap:6px">
                            <i class="fas fa-shield-halved" style="color:#12B76A;font-size:.8rem"></i>
                            <span style="font-size:.72rem;color:#98A2B3">Análise gerada por IA com base em dados públicos do IBGE. Não substitui estudo técnico aprofundado.</span>
                        </div>
                    </div>
                </div>

                {{-- Bar Chart --}}
                <div class="col-lg-6">
                    <div class="it-chart-card">
                        <div class="it-chart-title">
                            <i class="fas fa-chart-bar"></i>
                            Indicadores Sociais (% e escala)
                        </div>

                        {{-- Placeholder before data --}}
                        <div id="chartPlaceholder" class="it-placeholder-state" style="padding:40px 20px">
                            <div class="big-icon"><i class="fas fa-chart-bar"></i></div>
                            <p style="font-size:.85rem;margin:0">Os indicadores aparecerão aqui após a análise.</p>
                        </div>

                        {{-- Bars --}}
                        <div id="chartBars" style="display:none">
                            <div class="it-bar-row">
                                <div class="it-bar-label">
                                    <span class="it-bar-name"><i class="fas fa-graduation-cap" style="color:#D97706"></i> Escolarização</span>
                                    <span class="it-bar-val" id="chart-val-educacao">—</span>
                                </div>
                                <div class="it-bar-track">
                                    <div class="it-bar-fill" id="chart-bar-educacao" style="background:#D97706"></div>
                                </div>
                            </div>
                            <div class="it-bar-row">
                                <div class="it-bar-label">
                                    <span class="it-bar-name"><i class="fas fa-faucet-drip" style="color:#0E7490"></i> Saneamento Adequado</span>
                                    <span class="it-bar-val" id="chart-val-saneamento">—</span>
                                </div>
                                <div class="it-bar-track">
                                    <div class="it-bar-fill" id="chart-bar-saneamento" style="background:#0E7490"></div>
                                </div>
                            </div>
                            <div class="it-bar-row">
                                <div class="it-bar-label">
                                    <span class="it-bar-name"><i class="fas fa-hand-holding-dollar" style="color:#166534"></i> Renda (índice relativo)</span>
                                    <span class="it-bar-val" id="chart-val-renda">—</span>
                                </div>
                                <div class="it-bar-track">
                                    <div class="it-bar-fill" id="chart-bar-renda" style="background:#166534"></div>
                                </div>
                            </div>
                            <div style="margin-top:20px;padding-top:16px;border-top:1px solid #F2F4F7">
                                <p style="font-size:.72rem;color:#98A2B3;margin:0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Educação e Saneamento em % real. Renda normalizada relativa ao maior valor registrado (R$5.000).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── INITIAL EMPTY STATE ────────────────────── --}}
        <div id="emptyState">
            <div class="row g-4">
                <div class="col-md-4">
                    <div style="background:#fff;border:1px solid #EAECF0;border-radius:16px;padding:28px;box-shadow:0 1px 3px rgba(16,24,40,0.06)">
                        <div style="width:44px;height:44px;background:#EFF6FF;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px">
                            <i class="fas fa-users" style="color:#1D4ED8"></i>
                        </div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Dados Populacionais</h6>
                        <p style="font-size:.82rem;color:#667085;margin:0">População residente por município — Censo IBGE 2022.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#fff;border:1px solid #EAECF0;border-radius:16px;padding:28px;box-shadow:0 1px 3px rgba(16,24,40,0.06)">
                        <div style="width:44px;height:44px;background:#F0FDF4;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px">
                            <i class="fas fa-graduation-cap" style="color:#166534"></i>
                        </div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Educação & Renda</h6>
                        <p style="font-size:.82rem;color:#667085;margin:0">Taxa de escolarização e rendimento mensal per capita — PNAD/IBGE.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#fff;border:1px solid #EAECF0;border-radius:16px;padding:28px;box-shadow:0 1px 3px rgba(16,24,40,0.06)">
                        <div style="width:44px;height:44px;background:#ECFEFF;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px">
                            <i class="fas fa-robot" style="color:#0E7490"></i>
                        </div>
                        <h6 style="font-weight:700;color:#101828;margin-bottom:6px">Diagnóstico Bruce AI</h6>
                        <p style="font-size:.82rem;color:#667085;margin:0">Análise estratégica automática sobre os desafios sociais do município.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /it-content --}}
</div>{{-- /it-wrapper --}}

{{-- Toast container --}}
<div id="it-toast-wrap"></div>

@push('scripts')
<script>
const CSRF            = '{{ csrf_token() }}';
const ROUTE_CITIES    = '{{ route("ibge.cities.search") }}';
const ROUTE_INDICATORS = (code, name) => `/api/ibge/indicators/${code}?city_name=${encodeURIComponent(name)}`;

let selectedCity  = null;
let searchTimeout = null;
let acFocusIndex  = -1;

// ── Autocomplete ────────────────────────────────────
const input  = document.getElementById('citySearch');
const acBox  = document.getElementById('it-autocomplete');
const btnAna = document.getElementById('btnAnalyze');

input.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    selectedCity = null;
    btnAna.disabled = true;
    acFocusIndex = -1;

    if (q.length < 3) { acBox.style.display = 'none'; return; }

    searchTimeout = setTimeout(async () => {
        try {
            const res    = await fetch(`${ROUTE_CITIES}?q=${encodeURIComponent(q)}`);
            const cities = await res.json();
            renderAutocomplete(cities);
        } catch { acBox.style.display = 'none'; }
    }, 280);
});

function renderAutocomplete(cities) {
    acBox.innerHTML = '';
    if (!cities.length) { acBox.style.display = 'none'; return; }

    cities.forEach((city, i) => {
        const div = document.createElement('div');
        div.className = 'it-autocomplete-item';
        div.dataset.idx = i;
        div.innerHTML  = `
            <i class="fas fa-location-dot ac-icon"></i>
            <div>
                <div class="ac-city">${city.nome}</div>
                <div class="ac-state">${city.microrregiao?.mesorregiao?.UF?.nome ?? city['UF']?.nome ?? ''}</div>
            </div>
            <span class="ac-badge">${city['UF']?.sigla ?? city.microrregiao?.mesorregiao?.UF?.sigla ?? ''}</span>
        `;
        div.addEventListener('click', () => selectCity(city));
        acBox.appendChild(div);
    });
    acBox.style.display = 'block';
}

function selectCity(city) {
    selectedCity = city;
    const uf     = city['UF']?.sigla ?? city.microrregiao?.mesorregiao?.UF?.sigla ?? '';
    input.value  = `${city.nome}${uf ? ' — ' + uf : ''}`;
    acBox.style.display = 'none';
    btnAna.disabled = false;
    btnAna.focus();
}

// Keyboard navigation
input.addEventListener('keydown', (e) => {
    const items = acBox.querySelectorAll('.it-autocomplete-item');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        acFocusIndex = Math.min(acFocusIndex + 1, items.length - 1);
        items.forEach((el, i) => el.classList.toggle('active', i === acFocusIndex));
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        acFocusIndex = Math.max(acFocusIndex - 1, 0);
        items.forEach((el, i) => el.classList.toggle('active', i === acFocusIndex));
    } else if (e.key === 'Enter') {
        if (acFocusIndex >= 0 && items[acFocusIndex]) {
            items[acFocusIndex].click();
        } else if (selectedCity) {
            triggerSearch();
        }
    } else if (e.key === 'Escape') {
        acBox.style.display = 'none';
    }
});

document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !acBox.contains(e.target)) {
        acBox.style.display = 'none';
    }
});

// ── Main search ─────────────────────────────────────
async function triggerSearch() {
    if (!selectedCity) { toast('Selecione uma cidade da lista primeiro.', 'error'); return; }

    const cityName = selectedCity.nome;
    const uf       = selectedCity['UF']?.sigla ?? selectedCity.microrregiao?.mesorregiao?.UF?.sigla ?? '';
    const cityCode = selectedCity.id;

    // Show results area, hide empty state
    document.getElementById('emptyState').style.display   = 'none';
    document.getElementById('resultsArea').style.display  = 'block';
    document.getElementById('cityNameDisplay').textContent = `${cityName}${uf ? ', ' + uf : ''}`;
    document.getElementById('cacheDate').textContent       = '—';

    // Reset KPI cards to loading state
    setKpiLoading();

    // Update button
    const btn  = document.getElementById('btnAnalyze');
    const icon = document.getElementById('btnIcon');
    const text = document.getElementById('btnText');
    btn.disabled  = true;
    icon.className = 'fas fa-circle-notch fa-spin';
    text.textContent = 'Analisando...';

    // Reset AI
    const aiEl = document.getElementById('aiAnalysis');
    aiEl.className = 'it-ai-text';
    aiEl.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="color:var(--primary-color,#4F46E5);margin-right:8px"></i> Bruce AI está processando os dados do IBGE...';

    try {
        const res  = await fetch(ROUTE_INDICATORS(cityCode, cityName));

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        renderKpis(data.indicators);
        renderChart(data.indicators);

        aiEl.className   = 'it-ai-text';
        aiEl.textContent = data.analysis;
        document.getElementById('cacheDate').textContent = new Date().toLocaleDateString('pt-BR');

        toast(`Análise de ${cityName} concluída!`, 'success');

    } catch (err) {
        console.error(err);
        aiEl.className   = 'it-ai-text';
        aiEl.textContent = 'Erro ao buscar dados. Verifique sua conexão e tente novamente.';
        toast('Falha ao buscar indicadores. Tente novamente.', 'error');
    } finally {
        btn.disabled     = false;
        icon.className   = 'fas fa-chart-bar';
        text.textContent = 'Analisar';
    }
}

// ── KPI rendering ───────────────────────────────────
function setKpiLoading() {
    ['populacao','renda','educacao','saneamento'].forEach(k => {
        document.getElementById(`val-${k}`).textContent  = '—';
        document.getElementById(`year-${k}`).textContent = '';
        document.getElementById(`bar-${k}`).style.width  = '0%';
    });
}

function renderKpis(indicators) {
    const configs = {
        populacao:  { prefix: '', suffix: ' hab.',   maxRef: 15000000, format: 'number' },
        renda:      { prefix: 'R$ ', suffix: '',     maxRef: 5000,     format: 'decimal' },
        educacao:   { prefix: '', suffix: '%',        maxRef: 100,      format: 'percent' },
        saneamento: { prefix: '', suffix: '%',        maxRef: 100,      format: 'percent' },
    };

    Object.entries(configs).forEach(([key, cfg]) => {
        const ind   = indicators[key];
        const valEl  = document.getElementById(`val-${key}`);
        const yearEl = document.getElementById(`year-${key}`);
        const barEl  = document.getElementById(`bar-${key}`);

        if (ind && ind.value !== null && ind.value !== 'N/A') {
            const num = parseFloat(ind.value);

            if (cfg.format === 'number') {
                valEl.textContent = cfg.prefix + Math.round(num).toLocaleString('pt-BR') + cfg.suffix;
            } else if (cfg.format === 'decimal') {
                valEl.textContent = cfg.prefix + num.toLocaleString('pt-BR', {minimumFractionDigits:0, maximumFractionDigits:2}) + cfg.suffix;
            } else {
                valEl.textContent = cfg.prefix + num.toLocaleString('pt-BR', {minimumFractionDigits:1, maximumFractionDigits:1}) + cfg.suffix;
            }

            yearEl.textContent = ind.year ? `Censo ${ind.year}` : '';
            const pct = Math.min((num / cfg.maxRef) * 100, 100);
            setTimeout(() => { barEl.style.width = pct + '%'; }, 100);
        } else {
            valEl.textContent  = 'N/D';
            yearEl.textContent = 'Sem dados';
        }
    });
}

function renderChart(indicators) {
    document.getElementById('chartPlaceholder').style.display = 'none';
    document.getElementById('chartBars').style.display        = 'block';

    const chartItems = {
        educacao:   { maxRef: 100,  format: v => v.toFixed(1) + '%' },
        saneamento: { maxRef: 100,  format: v => v.toFixed(1) + '%' },
        renda:      { maxRef: 5000, format: v => 'R$ ' + v.toLocaleString('pt-BR', {maximumFractionDigits:0}) },
    };

    Object.entries(chartItems).forEach(([key, cfg]) => {
        const ind  = indicators[key];
        const valEl = document.getElementById(`chart-val-${key}`);
        const barEl = document.getElementById(`chart-bar-${key}`);

        if (ind && ind.value !== null) {
            const num = parseFloat(ind.value);
            valEl.textContent = cfg.format(num);
            const pct = Math.min((num / cfg.maxRef) * 100, 100);
            setTimeout(() => { barEl.style.width = pct + '%'; }, 200);
        } else {
            valEl.textContent    = 'N/D';
            barEl.style.width    = '0%';
        }
    });
}

// ── Toast ────────────────────────────────────────────
function toast(msg, type = 'success') {
    const icons = { success: '✅', error: '❌' };
    const el = document.createElement('div');
    el.className = `it-toast ${type}`;
    el.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
    document.getElementById('it-toast-wrap').appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
</script>
@endpush
@endsection
