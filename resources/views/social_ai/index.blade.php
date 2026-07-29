@extends('layouts.app', ['title' => 'Social AI Hub'])

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   SOCIAL AI HUB — Premium Design System
   Base: white content, #f8fafc page bg, indigo accent
   ════════════════════════════════════════════════════ */

/* ── Page shell ─────────────────────────────────── */
.sai-wrapper {
    min-height: 100vh;
    background: #f8fafc;
    padding: 0;
}

/* ── Page header zone (white, pinned) ───────────── */
.sai-header {
    background: #ffffff;
    border-bottom: 1px solid #EAECF0;
    padding: 28px 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 0;
}
.sai-title-block h1 {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    color: #101828;
    margin: 0;
    line-height: 1.2;
}
.sai-title-block h1 span { color: var(--primary-color, #4F46E5); }
.sai-title-block p {
    color: #667085;
    margin: 4px 0 0;
    font-size: 0.88rem;
}

/* ── Page body ───────────────────────────────────── */
.sai-content { padding: 32px 36px; }

/* ── Quota pill ──────────────────────────────────── */
.sai-quota-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 12px;
    padding: 14px 22px;
    min-width: 200px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.sai-quota-card .label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #667085;
    font-weight: 700;
    white-space: nowrap;
}
.sai-quota-card .numbers {
    font-size: 1.4rem;
    font-weight: 800;
    color: #101828;
    line-height: 1;
    white-space: nowrap;
}
.sai-quota-card .numbers small {
    font-size: 0.85rem;
    font-weight: 400;
    color: #98A2B3;
}
.sai-quota-bar {
    width: 120px;
    height: 6px;
    background: #F2F4F7;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}
.sai-quota-bar-fill {
    height: 100%;
    border-radius: 10px;
    background: var(--primary-color, #4F46E5);
    transition: width 1s ease;
}
@keyframes shimmer {
    0%   { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}

/* ── Generator — hero card ───────────────────────── */
.sai-generator {
    background: #ffffff;
    border: 1px solid #EAECF0;
    border-top: 4px solid var(--primary-color, #4F46E5);
    border-radius: 16px;
    padding: 32px 36px 28px;
    margin-bottom: 36px;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 8px 24px rgba(16,24,40,0.05);
}
.sai-generator-title {
    font-size: 1rem;
    font-weight: 700;
    color: #101828;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sai-generator-title .icon {
    width: 34px;
    height: 34px;
    background: var(--primary-color, #4F46E5);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    color: #fff;
    flex-shrink: 0;
}
.sai-input-row {
    display: flex;
    gap: 10px;
    align-items: stretch;
}
.sai-input {
    flex: 1;
    background: #F9FAFB;
    border: 1.5px solid #D0D5DD;
    border-radius: 12px;
    padding: 15px 20px;
    color: #101828;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.sai-input::placeholder { color: #98A2B3; }
.sai-input:focus {
    background: #fff;
    border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
}
.sai-btn-generate {
    background: var(--primary-color, #4F46E5);
    border: none;
    border-radius: 12px;
    padding: 15px 28px;
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(79,70,229,0.35);
}
.sai-btn-generate:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79,70,229,0.45);
}
.sai-btn-generate:active:not(:disabled) { transform: translateY(0); }
.sai-btn-generate:disabled { opacity: 0.5; cursor: not-allowed; }
.sai-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 16px;
}
.sai-tags .label-hint {
    font-size: 0.72rem;
    color: #98A2B3;
    align-self: center;
    margin-right: 4px;
    font-weight: 600;
}
.sai-tag {
    background: #F9FAFB;
    border: 1px solid #EAECF0;
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 0.75rem;
    color: #475467;
    cursor: pointer;
    transition: all 0.15s;
    user-select: none;
    font-weight: 500;
}
.sai-tag:hover {
    background: var(--primary-bg, #EEF2FF);
    border-color: var(--primary-color, #4F46E5);
    color: var(--primary-color, #4F46E5);
}

/* ── Section label ──────────────────────────────── */
.sai-section-title {
    font-size: 0.7rem;
    font-weight: 700;
    color: #98A2B3;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sai-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #EAECF0;
}

/* ── Post Card ──────────────────────────────────── */
.sai-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    box-shadow: 0 1px 3px rgba(16,24,40,0.06), 0 4px 12px rgba(16,24,40,0.04);
}
.sai-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 4px 8px rgba(79,70,229,0.08), 0 16px 40px rgba(79,70,229,0.10);
}

/* ── Card Image Area ──────────────────────────────*/
.sai-card-img {
    position: relative;
    aspect-ratio: 1/1;
    overflow: hidden;
    background: #111;
}
.sai-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.sai-card:hover .sai-card-img img {
    transform: scale(1.04);
}
.sai-card-img .img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(7,7,15,0.8) 0%, transparent 50%);
}

/* ── Skeleton Loading ───────────────────────────── */
.sai-skeleton {
    aspect-ratio: 1/1;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 400px 100%;
    animation: shimmer 1.5s infinite linear;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.sai-skeleton .sk-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid rgba(79,70,229,0.2);
    border-top-color: var(--primary-color, #4F46E5);
    animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.sai-skeleton .sk-text {
    font-size: 0.8rem;
    color: var(--text-secondary, #9CA3AF);
    font-weight: 500;
}

/* ── Status Badge ───────────────────────────────── */
.sai-badge {
    position: absolute;
    top: 14px;
    left: 14px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.sai-badge.draft      { background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.sai-badge.processing { background: rgba(79,70,229,0.2); color: var(--primary-light, #818CF8); border: 1px solid rgba(79,70,229,0.4); animation: pulseBadge 2s infinite; }
.sai-badge.failed     { background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
.sai-badge.published  { background: rgba(14,165,233,0.2); color: #38bdf8; border: 1px solid rgba(14,165,233,0.3); }
.sai-badge.scheduled  { background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
@keyframes pulseBadge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ── Download Button (on hover) ─────────────────── */
.sai-img-actions {
    position: absolute;
    top: 14px;
    right: 14px;
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.3s;
}
.sai-card:hover .sai-img-actions { opacity: 1; }
.sai-img-btn {
    width: 34px;
    height: 34px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(8px);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.75rem;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.1);
    transition: background 0.2s;
    text-decoration: none;
}
.sai-img-btn:hover { background: rgba(79,70,229,0.6); color: #fff; }

/* ── Card Body ──────────────────────────────────── */
.sai-card-body {
    padding: 18px 20px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.sai-card-theme {
    font-size: 0.875rem;
    font-weight: 700;
    color: #101828;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sai-card-caption {
    font-size: 0.78rem;
    color: #667085;
    line-height: 1.6;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sai-card-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}
.sai-card-row { display: flex; gap: 8px; }
.sai-action-btn {
    flex: 1;
    padding: 9px 10px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    letter-spacing: 0.1px;
}
.sai-action-btn.copy {
    background: #F9FAFB;
    color: #475467;
    border-color: #EAECF0;
}
.sai-action-btn.copy:hover { background: #F2F4F7; color: #101828; }
.sai-action-btn.expand {
    background: #F4F3FF;
    color: #5B21B6;
    border-color: #DDD6FE;
}
.sai-action-btn.expand:hover { background: #EDE9FE; color: #5B21B6; text-decoration: none; }
.sai-action-btn.whatsapp {
    background: #F0FDF4;
    color: #166534;
    border-color: #BBF7D0;
    text-decoration: none;
}
.sai-action-btn.whatsapp:hover { background: #DCFCE7; color: #166534; }
.sai-action-btn.schedule {
    background: #EFF6FF;
    color: #1D4ED8;
    border-color: #BFDBFE;
}
.sai-action-btn.schedule:hover { background: #DBEAFE; color: #1D4ED8; }
.sai-action-btn.delete {
    background: transparent;
    color: #98A2B3;
    border-color: transparent;
    width: 100%;
    font-size: 0.72rem;
}
.sai-action-btn.delete:hover { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }

/* ── Empty State ─────────────────────────────────── */
.sai-empty {
    text-align: center;
    padding: 80px 20px;
    color: #98A2B3;
}
.sai-empty .empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}
.sai-empty p { font-size: 1rem; margin: 0; }

/* ── Toast Notification ─────────────────────────── */
#sai-toast-container {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.sai-toast {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 12px;
    padding: 14px 18px;
    color: #101828;
    font-size: 0.85rem;
    font-weight: 500;
    min-width: 300px;
    max-width: 380px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInToast 0.35s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: 0 4px 12px rgba(16,24,40,0.1), 0 12px 32px rgba(16,24,40,0.06);
}
.sai-toast.success { border-left: 3px solid #12B76A; }
.sai-toast.error   { border-left: 3px solid #F04438; }
.sai-toast.info    { border-left: 3px solid var(--primary-color, #4F46E5); }
.sai-toast .t-icon { font-size: 1.1rem; flex-shrink: 0; }
@keyframes slideInToast {
    from { transform: translateX(120%); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}

/* ── Modal ──────────────────────────────────────── */
.sai-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    z-index: 9000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.sai-modal-backdrop.open { display: flex; }
.sai-modal {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 20px;
    width: 100%;
    max-width: 580px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    position: relative;
    animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: 0 8px 24px rgba(16,24,40,0.08), 0 32px 64px rgba(16,24,40,0.12);
}
@keyframes modalIn {
    from { transform: scale(0.92) translateY(16px); opacity: 0; }
    to   { transform: scale(1) translateY(0);        opacity: 1; }
}
.sai-modal-close {
    position: absolute;
    top: 16px; right: 16px;
    width: 32px; height: 32px;
    background: #F9FAFB;
    border: 1px solid #EAECF0;
    border-radius: 8px;
    color: #667085;
    cursor: pointer;
    font-size: 0.85rem;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.sai-modal-close:hover { background: #F2F4F7; color: #101828; }
.sai-modal img { width: 100%; border-radius: 12px; margin-bottom: 20px; }
.sai-modal h4 { font-size: 1rem; font-weight: 700; color: #101828; margin-bottom: 10px; }
.sai-modal-caption {
    background: #F9FAFB;
    border: 1px solid #EAECF0;
    border-radius: 10px;
    padding: 14px 16px;
    color: #344054;
    font-size: 0.85rem;
    line-height: 1.7;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
}
.sai-modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.sai-modal-btn {
    flex: 1;
    padding: 11px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s;
}
.sai-modal-btn.primary {
    background: var(--primary-color, #4F46E5);
    color: #fff;
    box-shadow: 0 2px 8px rgba(79,70,229,0.3);
}
.sai-modal-btn.primary:hover { filter: brightness(1.08); transform: translateY(-1px); }
.sai-modal-btn.secondary {
    background: #F0FDF4;
    color: #166534;
    border-color: #BBF7D0;
}
.sai-modal-btn.secondary:hover { background: #DCFCE7; }

/* ── Failed State ───────────────────────────────── */
.sai-failed-img {
    aspect-ratio: 1/1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #fff5f5;
    color: #dc2626;
    font-size: 0.8rem;
    text-align: center;
    padding: 20px;
}
.sai-failed-img i { font-size: 2.5rem; opacity: 0.4; }

/* ── Schedule Modal extras ──────────────────────── */
.sai-platform-btns {
    display: flex;
    gap: 8px;
    margin: 12px 0 20px;
}
.sai-platform-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 11px;
    background: #F9FAFB;
    border: 1.5px solid #EAECF0;
    border-radius: 10px;
    color: #475467;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 0.8rem;
    font-weight: 600;
}
.sai-platform-btn:has(input:checked) {
    background: #F4F3FF;
    border-color: var(--primary-color, #4F46E5);
    color: var(--primary-color, #4F46E5);
}
.sai-platform-btn input[type="radio"] { display: none; }
.sai-modal-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #98A2B3;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    display: block;
}
.sai-datetime-input {
    width: 100%;
    background: #F9FAFB;
    border: 1.5px solid #D0D5DD;
    border-radius: 10px;
    padding: 12px 14px;
    color: #101828;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    color-scheme: light;
}
.sai-datetime-input:focus { background: #fff; border-color: var(--primary-color, #4F46E5); box-shadow: 0 0 0 4px rgba(79,70,229,0.08); }

/* ── Context Panel ──────────────────────────────── */
.sai-context-toggle {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 14px;
    cursor: pointer;
    width: fit-content;
    color: #667085;
    font-size: 0.8rem;
    font-weight: 600;
    user-select: none;
    transition: color 0.15s;
    padding: 6px 12px;
    background: #F9FAFB;
    border: 1px solid #EAECF0;
    border-radius: 20px;
}
.sai-context-toggle:hover { color: var(--primary-color, #4F46E5); border-color: var(--primary-color, #4F46E5); background: #F4F3FF; }
.sai-context-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}
.sai-context-arrow.open { transform: rotate(180deg); }
.sai-context-panel {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease, opacity 0.3s ease;
    opacity: 0;
}
.sai-context-panel.open {
    max-height: 260px;
    opacity: 1;
}
.sai-context-textarea {
    width: 100%;
    margin-top: 12px;
    background: #F9FAFB;
    border: 1.5px solid #D0D5DD;
    border-radius: 12px;
    padding: 14px 16px;
    color: #101828;
    font-size: 0.85rem;
    line-height: 1.6;
    resize: none;
    height: 120px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    font-family: inherit;
}
.sai-context-textarea::placeholder { color: #98A2B3; }
.sai-context-textarea:focus {
    background: #fff;
    border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
}
.sai-context-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding: 0 2px;
}
.sai-ctx-count {
    font-size: 0.7rem;
    color: #98A2B3;
    font-variant-numeric: tabular-nums;
}
.sai-ctx-count.near-limit { color: #f59e0b; }

/* ── Pagination override ─────────────────────────── */
.sai-pagination .page-link {
    background: #fff;
    border: 1px solid #EAECF0;
    color: #475467;
    border-radius: 8px !important;
    margin: 0 3px;
    transition: all 0.15s;
    font-size: 0.85rem;
    font-weight: 500;
}
.sai-pagination .page-item.active .page-link {
    background: var(--primary-color, #4F46E5);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 2px 8px rgba(79,70,229,0.3);
}
.sai-pagination .page-link:hover { background: #F4F3FF; color: var(--primary-color, #4F46E5); border-color: #DDD6FE; }

/* ── Banner Bruce IA ────────────────────────────────────────────────────── */
.sai-bruce-banner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 24px; padding: 24px 32px; margin-bottom: 22px;
    background: #0A0A0B;
    border: 1px solid rgba(255,122,26,.2);
    border-radius: 20px;
    flex-wrap: wrap;
}
.sai-bruce-body { flex: 1; min-width: 260px; }
.sai-bruce-tag {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,122,26,.12);
    border: 1px solid rgba(255,122,26,.3);
    color: #FF7A1A; font-size: .65rem; font-weight: 800;
    padding: 4px 12px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: 1.2px;
    margin-bottom: 10px;
}
.sai-bruce-title {
    color: #fff; font-size: 1.1rem; font-weight: 800;
    margin: 0 0 6px; letter-spacing: -.3px; line-height: 1.3;
}
.sai-bruce-desc {
    color: rgba(255,255,255,.55); font-size: .82rem;
    margin: 0 0 16px; max-width: 560px; line-height: 1.55;
}
.sai-bruce-cta {
    display: inline-flex; align-items: center; gap: 8px;
    background: #FF7A1A; color: #fff;
    font-size: .8rem; font-weight: 800;
    padding: 10px 22px; border-radius: 10px;
    text-decoration: none; letter-spacing: -.1px;
    transition: background .15s;
}
.sai-bruce-cta:hover { background: #ea580c; color: #fff; }
.sai-bruce-icon-wrap { flex-shrink: 0; }
.sai-bruce-icon-wrap img {
    width: 90px; height: 90px; border-radius: 22px;
    background: #0f0f1e;
    border: 1px solid rgba(255,255,255,.08);
    display: block;
}
@media (max-width: 640px) {
    .sai-bruce-banner { padding: 20px; }
    .sai-bruce-icon-wrap img { width: 70px; height: 70px; }
}

/* ── Options Panel (Formato + Estilo + Referência) ─────────────────────── */
/* Ajustado pra fundo CLARO da página (era escuro no design original). */
.sai-options-panel {
    margin-top: 22px; padding: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}
.sai-opt-row {
    display: grid; grid-template-columns: 1fr 2fr; gap: 24px;
    margin-bottom: 20px;
}
@media(max-width: 900px) { .sai-opt-row { grid-template-columns: 1fr; } }
.sai-opt-block { }
.sai-opt-label {
    display: flex; align-items: center; gap: 8px;
    font-size: .75rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px;
}
.sai-opt-label i { color: #4F46E5; }
.sai-opt-btns { display: flex; flex-wrap: wrap; gap: 6px; }
.sai-opt-btn {
    background: #ffffff; color: #475569;
    border: 1px solid #e2e8f0;
    padding: 8px 14px; border-radius: 8px;
    font-size: .78rem; font-weight: 600; cursor: pointer;
    transition: all .15s;
}
.sai-opt-btn:hover {
    background: #f1f5f9; color: #0f172a; border-color: #cbd5e1;
}
.sai-opt-btn.active {
    background: var(--primary-color, #4F46E5); color: #fff;
    border-color: var(--primary-color, #4F46E5);
    box-shadow: 0 4px 10px rgba(79,70,229,.25);
}
.sai-ref-block { padding-top: 16px; border-top: 1px dashed #cbd5e1; }
.sai-ref-row { display: flex; align-items: stretch; gap: 12px; }
.sai-ref-drop {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 8px; min-height: 120px;
    background: rgba(79,70,229,.04);
    border: 2px dashed rgba(79,70,229,.35);
    border-radius: 12px; cursor: pointer; padding: 16px;
    color: #64748b; font-size: .82rem; font-weight: 500;
    transition: background .15s, border-color .15s;
    text-align: center; position: relative; overflow: hidden;
}
.sai-ref-drop:hover { background: rgba(79,70,229,.08); border-color: rgba(79,70,229,.6); color: #4F46E5; }
.sai-ref-drop-icon { font-size: 1.6rem; color: rgba(79,70,229,.7); }
.sai-ref-drop img { max-height: 96px; max-width: 100%; border-radius: 8px; margin-top: 4px; }
.sai-ref-drop.has-image { padding: 8px; }
.sai-ref-drop.has-image .sai-ref-drop-icon,
.sai-ref-drop.has-image #refDropText { display: none; }
.sai-ref-opts {
    display: flex; flex-direction: column; gap: 10px;
    margin-top: 14px; padding: 14px; background: #ffffff;
    border-radius: 10px; border: 1px solid #e2e8f0;
}
.sai-ref-radio {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 12px; border-radius: 8px; cursor: pointer;
    background: #f8fafc; transition: background .15s;
    border: 1px solid transparent;
}
.sai-ref-radio:hover { background: #f1f5f9; border-color: #e2e8f0; }
.sai-ref-radio input { margin-top: 4px; flex-shrink: 0; accent-color: #4F46E5; }
.sai-ref-radio strong { display: block; font-size: .82rem; color: #0f172a; font-weight: 700; }
.sai-ref-radio small { display: block; font-size: .72rem; color: #64748b; margin-top: 2px; }
.sai-ref-remove {
    background: #fff; border: 1px solid #fecaca;
    color: #dc2626; font-size: .74rem; font-weight: 600;
    padding: 6px 12px; border-radius: 6px; cursor: pointer;
    align-self: flex-start;
}
.sai-ref-remove:hover { background: #fef2f2; border-color: #fca5a5; }

/* ── Variações de legenda no card ──────────────────────────────────────── */
.sai-variations {
    display: flex; gap: 6px; margin-top: 10px; margin-bottom: 6px;
    flex-wrap: wrap;
}
.sai-var-pill {
    background: rgba(79,70,229,.08); color: #a5b4fc;
    border: 1px solid rgba(79,70,229,.25);
    padding: 3px 9px; border-radius: 20px;
    font-size: .68rem; font-weight: 700; cursor: pointer;
    transition: background .15s, color .15s;
}
.sai-var-pill:hover { background: rgba(79,70,229,.18); color: #c7d2fe; }
.sai-var-pill.active {
    background: var(--primary-color, #4F46E5); color: #fff;
    border-color: transparent;
}
.sai-regen-btn {
    background: rgba(255,255,255,.03); color: rgba(255,255,255,.65);
    border: 1px solid rgba(255,255,255,.08);
    font-size: .72rem; font-weight: 600;
    padding: 6px 10px; border-radius: 6px; cursor: pointer;
    transition: background .15s;
}
.sai-regen-btn:hover:not(:disabled) { background: rgba(255,255,255,.08); color: #fff; }
.sai-regen-btn:disabled { opacity: .5; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="sai-wrapper">

    {{-- ── PAGE HEADER (full-width, white, pinned) ── --}}
    <div class="sai-header">
        <div class="sai-title-block">
            <h1>Social <span>AI Hub</span></h1>
            <p>Gere posts estratégicos com texto e imagem em segundos, direto pela IA.</p>
        </div>
        <div class="sai-quota-card">
            <div>
                <div class="label">Imagens / mês</div>
                <div class="numbers">{{ $quotaUsed }} <small>/ 60</small></div>
            </div>
            <div class="sai-quota-bar">
                <div class="sai-quota-bar-fill" style="width: {{ min(($quotaUsed / 60) * 100, 100) }}%"></div>
            </div>
        </div>
    </div>

<div class="sai-content">

    {{-- ── BANNER BRUCE IA ── --}}
    <div class="sai-bruce-banner">
        <div class="sai-bruce-body">
            <div class="sai-bruce-tag">
                <i class="fas fa-wand-magic-sparkles"></i>
                <span>Bruce IA explica</span>
            </div>
            <h3 class="sai-bruce-title">Como funciona o Social AI Hub?</h3>
            <p class="sai-bruce-desc">Entenda cada opção — formato, estilo visual, imagem de referência, variações de legenda — e as melhores práticas para tirar o máximo do Bruce.</p>
            <a href="{{ route('social-ai.about') }}" class="sai-bruce-cta">
                <i class="fas fa-book-open"></i> Entender como funciona
            </a>
        </div>
        <div class="sai-bruce-icon-wrap">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA">
        </div>
    </div>

    {{-- ── GENERATOR ────────────────────────────── --}}
    <div class="sai-generator">
        <div class="sai-generator-title">
            <div class="icon"><i class="fas fa-wand-magic-sparkles"></i></div>
            Sobre o que vamos postar hoje?
        </div>
        <div class="sai-input-row">
            <input type="text" id="postTheme" class="sai-input"
                placeholder="Ex: Captação de recursos para ONGs em 2026, dicas práticas..."
                maxlength="500"
                onkeydown="if(event.key==='Enter') generatePost()">
            <button id="btnGenerate" class="sai-btn-generate" onclick="generatePost()">
                <i class="fas fa-magic" id="btnIcon"></i>
                <span id="btnText">Gerar Post</span>
            </button>
        </div>

        {{-- Campo de contexto do usuário --}}
        <div class="sai-context-toggle" onclick="toggleContext()">
            <i class="fas fa-sliders" style="color:var(--primary-color,#4F46E5)"></i>
            <span>Personalizar instruções para a IA</span>
            <i class="fas fa-chevron-down sai-context-arrow" id="ctxArrow"></i>
        </div>
        <div class="sai-context-panel" id="ctxPanel">
            <textarea id="userContext" class="sai-context-textarea"
                placeholder="Descreva detalhes que a IA deve considerar. Exemplos:&#10;• Tom de voz: informal, técnico, inspirador, divertido...&#10;• Público-alvo: jovens universitários, mães empreendedoras, empresários...&#10;• Produto/serviço específico: nome, diferenciais, preço...&#10;• Estilo da imagem: minimalista, colorida, profissional...&#10;• Hashtags ou palavras obrigatórias..."
                maxlength="1000"
                oninput="updateCtxCount()"></textarea>
            <div class="sai-context-footer">
                <span style="color:rgba(255,255,255,0.25); font-size:0.72rem;">
                    <i class="fas fa-circle-info"></i> Opcional — quanto mais detalhes, melhor o resultado.
                </span>
                <span class="sai-ctx-count" id="ctxCount">0 / 1000</span>
            </div>
        </div>
        {{-- ── Formato + Estilo Visual + Referência (Rota 2) ──────────────── --}}
        <div class="sai-options-panel">
            <div class="sai-opt-row">
                <div class="sai-opt-block">
                    <div class="sai-opt-label"><i class="fas fa-image"></i> Formato</div>
                    <div class="sai-opt-btns" data-group="format">
                        <button type="button" class="sai-opt-btn active" data-value="square" onclick="pickOpt(this,'format')">
                            <i class="fas fa-square"></i> Feed 1:1
                        </button>
                        <button type="button" class="sai-opt-btn" data-value="story" onclick="pickOpt(this,'format')">
                            <i class="fas fa-mobile-screen"></i> Story 9:16
                        </button>
                    </div>
                </div>

                <div class="sai-opt-block">
                    <div class="sai-opt-label"><i class="fas fa-palette"></i> Estilo Visual</div>
                    <div class="sai-opt-btns" data-group="visual_style">
                        <button type="button" class="sai-opt-btn active" data-value="" onclick="pickOpt(this,'visual_style')">Padrão</button>
                        <button type="button" class="sai-opt-btn" data-value="photorealistic" onclick="pickOpt(this,'visual_style')">📷 Foto</button>
                        <button type="button" class="sai-opt-btn" data-value="illustration" onclick="pickOpt(this,'visual_style')">🎨 Ilustração</button>
                        <button type="button" class="sai-opt-btn" data-value="cartoon" onclick="pickOpt(this,'visual_style')">🎭 Cartoon</button>
                        <button type="button" class="sai-opt-btn" data-value="corporate" onclick="pickOpt(this,'visual_style')">💼 Corporativo</button>
                        <button type="button" class="sai-opt-btn" data-value="minimalist" onclick="pickOpt(this,'visual_style')">◻️ Minimalista</button>
                        <button type="button" class="sai-opt-btn" data-value="watercolor" onclick="pickOpt(this,'visual_style')">🖌️ Aquarela</button>
                    </div>
                </div>
            </div>

            <div class="sai-ref-block">
                <div class="sai-opt-label"><i class="fas fa-cloud-upload-alt"></i> Imagem de Referência (opcional)</div>
                <div class="sai-ref-row">
                    <label class="sai-ref-drop" for="referenceImage" id="refDropLabel">
                        <i class="fas fa-image sai-ref-drop-icon"></i>
                        <span id="refDropText">Clique para subir imagem (JPG/PNG/WEBP, máx 5MB)</span>
                        <img id="refPreview" alt="preview" style="display:none;">
                    </label>
                    <input type="file" id="referenceImage" accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="onRefChange(this)">
                </div>

                {{-- Opções que aparecem só quando há upload --}}
                <div class="sai-ref-opts" id="refOptsWrap" style="display:none;">
                    <label class="sai-ref-radio">
                        <input type="radio" name="ref_mode" value="reference" checked>
                        <div>
                            <strong>Usar como referência visual</strong>
                            <small>Descreva o estilo no campo de instruções — a IA gera uma imagem inspirada.</small>
                        </div>
                    </label>
                    <label class="sai-ref-radio">
                        <input type="radio" name="ref_mode" value="final">
                        <div>
                            <strong>Usar como imagem final do post</strong>
                            <small>Não gera imagem nova (economiza 1 crédito da cota). Só a legenda é gerada.</small>
                        </div>
                    </label>
                    <button type="button" class="sai-ref-remove" onclick="removeRef()">
                        <i class="fas fa-times"></i> Remover imagem
                    </button>
                </div>
            </div>
        </div>

        <div class="sai-tags">
            <span class="label-hint">Sugestões:</span>
            @php $role = auth()->user()->role ?? 'common'; @endphp

            @if($role === 'ngo')
                {{-- ONG / Terceiro Setor --}}
                <span class="sai-tag" onclick="setTheme('Impacto real do nosso projeto na comunidade')">Impacto do projeto</span>
                <span class="sai-tag" onclick="setTheme('Como fazer uma doação e transformar vidas')">Captação de doadores</span>
                <span class="sai-tag" onclick="setTheme('Transparência e prestação de contas da nossa ONG')">Transparência</span>
                <span class="sai-tag" onclick="setTheme('Chamada para novos voluntários — venha fazer parte!')">Chamada de voluntários</span>
                <span class="sai-tag" onclick="setTheme('Resultado da nossa última campanha social')">Resultado de campanha</span>
                <span class="sai-tag" onclick="setTheme('Edital aprovado: o que isso significa para a comunidade')">Edital aprovado</span>

            @elseif(in_array($role, ['manager', 'super_admin']))
                {{-- Gestor de Projetos --}}
                <span class="sai-tag" onclick="setTheme('Resultado alcançado no trimestre — nossa equipe entregou!')">Resultado do trimestre</span>
                <span class="sai-tag" onclick="setTheme('Lançamento de novo projeto estratégico')">Lançamento de projeto</span>
                <span class="sai-tag" onclick="setTheme('Como a tecnologia está transformando nossa gestão')">Gestão e tecnologia</span>
                <span class="sai-tag" onclick="setTheme('Destaque do profissional da semana na nossa equipe')">Destaque da equipe</span>
                <span class="sai-tag" onclick="setTheme('Meta batida! Celebrando a conquista da nossa equipe')">Meta alcançada</span>
                <span class="sai-tag" onclick="setTheme('Convite para parceria estratégica com nossa empresa')">Parceria estratégica</span>

            @else
                {{-- MEI / Empreendedor --}}
                <span class="sai-tag" onclick="setTheme('Promoção especial do meu produto ou serviço')">Promoção especial</span>
                <span class="sai-tag" onclick="setTheme('Novidade na minha loja — confira o que chegou!')">Novidade na loja</span>
                <span class="sai-tag" onclick="setTheme('Dica rápida para clientes do meu negócio')">Dica para clientes</span>
                <span class="sai-tag" onclick="setTheme('Depoimento real de cliente satisfeito com meu serviço')">Depoimento de cliente</span>
                <span class="sai-tag" onclick="setTheme('Lançamento de novo produto ou serviço da minha empresa')">Lançamento de serviço</span>
                <span class="sai-tag" onclick="setTheme('Bastidores do meu negócio — como tudo acontece por aqui')">Bastidores do negócio</span>
            @endif
        </div>
    </div>

    {{-- ── POSTS GRID ───────────────────────────── --}}
    @if($posts->total() > 0)
    <div class="sai-section-title">
        <i class="fas fa-layer-group" style="color:var(--primary-color,#4F46E5)"></i>
        Seus Rascunhos ({{ $posts->total() }})
    </div>
    @endif

    <div class="row g-4" id="postsGrid">
        @forelse($posts as $post)
        <div class="col-md-6 col-xl-4" id="postCard-{{ $post->id }}">
            <div class="sai-card">

                {{-- Image / Skeleton / Failed --}}
                @if($post->status === 'processing')
                    <div class="sai-skeleton">
                        <div class="sk-icon"></div>
                        <div class="sk-text">Gerando com IA...</div>
                    </div>
                @elseif($post->status === 'failed')
                    <div class="sai-failed-img">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Falha na geração</span>
                        @if($post->error_message)
                        <small style="opacity:.6; font-size:.7rem;">{{ Str::limit($post->error_message, 80) }}</small>
                        @endif
                    </div>
                @else
                    <div class="sai-card-img">
                        @if($post->image_path)
                            <img loading="lazy" src="{{ Storage::disk('public')->url($post->image_path) }}" alt="{{ $post->title_theme }}" loading="lazy">
                        @else
                            <img loading="lazy" src="https://placehold.co/600x600/111827/374151?text=Sem+Imagem" alt="Sem imagem">
                        @endif
                        <div class="img-overlay"></div>
                        <div class="sai-img-actions">
                            @if($post->image_path)
                            <a href="{{ Storage::disk('public')->url($post->image_path) }}" download class="sai-img-btn" title="Baixar imagem">
                                <i class="fas fa-download"></i>
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Status Badge --}}
                <div class="sai-badge {{ $post->status }}" style="top:14px;left:14px">
                    @if($post->status === 'processing')
                        <i class="fas fa-circle-notch fa-spin" style="margin-right:5px"></i>
                    @elseif($post->status === 'draft')
                        <i class="fas fa-circle-check" style="margin-right:5px"></i>
                    @elseif($post->status === 'failed')
                        <i class="fas fa-circle-xmark" style="margin-right:5px"></i>
                    @elseif($post->status === 'published')
                        <i class="fas fa-paper-plane" style="margin-right:5px"></i>
                    @endif
                    {{ ucfirst($post->status === 'draft' ? 'Pronto' : $post->status) }}
                </div>

                {{-- Body --}}
                <div class="sai-card-body">
                    <div class="sai-card-theme">
                        {{ $post->title_theme }}
                        @if($post->format === 'story')
                            <span style="background:rgba(139,92,246,.2);color:#a78bfa;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:5px;margin-left:6px;letter-spacing:.05em;">STORY</span>
                        @endif
                        @if($post->use_reference_as_final)
                            <span style="background:rgba(16,185,129,.2);color:#6ee7b7;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:5px;margin-left:6px;letter-spacing:.05em;">SUA FOTO</span>
                        @endif
                    </div>

                    {{-- Variações de legenda (se DeepSeek gerou trio) --}}
                    @if(!empty($post->caption_variations) && count($post->caption_variations) > 1)
                    <div class="sai-variations" id="vars-{{ $post->id }}">
                        @foreach($post->caption_variations as $idx => $capt)
                            <button type="button"
                                    class="sai-var-pill {{ $capt === $post->body_text ? 'active' : '' }}"
                                    onclick="chooseVariation({{ $post->id }}, {{ $idx }})">
                                Legenda {{ $idx + 1 }}
                            </button>
                        @endforeach
                    </div>
                    @endif

                    @if($post->body_text)
                    <div class="sai-card-caption" id="caption-{{ $post->id }}">{{ $post->body_text }}</div>
                    @elseif($post->status === 'processing')
                    <div class="sai-card-caption" style="font-style:italic">Gerando legenda...</div>
                    @endif

                    <div class="sai-card-actions">
                        @if($post->status === 'draft')
                        <div class="sai-card-row">
                            <button class="sai-action-btn copy" onclick="copyCaption({{ $post->id }}, document.getElementById('caption-{{ $post->id }}').textContent)" {{ !$post->body_text ? 'disabled' : '' }}>
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <button class="sai-action-btn expand" onclick="openModal({{ $post->id }}, `{{ addslashes($post->title_theme) }}`, document.getElementById('caption-{{ $post->id }}').textContent, `{{ $post->image_path ? Storage::disk('public')->url($post->image_path) : '' }}`)">
                                <i class="fas fa-expand"></i> Ver
                            </button>
                        </div>
                        <div class="sai-card-row">
                            <button type="button" class="sai-regen-btn" onclick="regenerateCaption({{ $post->id }}, this)">
                                <i class="fas fa-rotate-right"></i> Nova legenda
                            </button>
                            @if(!$post->use_reference_as_final)
                            <button type="button" class="sai-regen-btn" onclick="regenerateImage({{ $post->id }}, this)">
                                <i class="fas fa-image"></i> Nova imagem
                            </button>
                            @endif
                        </div>
                        <div class="sai-card-row">
                            <a href="{{ route('social-ai.to-broadcast', $post->id) }}" class="sai-action-btn whatsapp">
                                <i class="fab fa-whatsapp"></i> Broadcast
                            </a>
                            <button class="sai-action-btn schedule" onclick="openScheduleModal({{ $post->id }})">
                                <i class="fas fa-calendar-plus"></i> Agendar
                            </button>
                        </div>
                        @elseif($post->status === 'scheduled')
                        <div class="sai-card-row">
                            <button class="sai-action-btn copy" onclick="copyCaption({{ $post->id }}, `{{ addslashes($post->body_text) }}`)" {{ !$post->body_text ? 'disabled' : '' }}>
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <a href="{{ route('social.posts.index') }}" class="sai-action-btn expand">
                                <i class="fas fa-calendar"></i> Ver no Cal.
                            </a>
                        </div>
                        @endif
                        <button class="sai-action-btn delete" onclick="deletePost({{ $post->id }})">
                            <i class="fas fa-trash-alt"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 sai-empty">
            <div class="empty-icon"><i class="fas fa-robot"></i></div>
            <p>Nenhum post gerado ainda.<br>
            <span style="font-size:.85rem; opacity:.6">Digite um tema acima e deixe a IA trabalhar por você.</span></p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($posts->hasPages())
    <div class="d-flex justify-content-center mt-5 sai-pagination">
        {{ $posts->links() }}
    </div>
    @endif

</div>
</div>{{-- /sai-content --}}
</div>{{-- /sai-wrapper --}}

{{-- ── MODAL AGENDAMENTO ───────────────────────────── --}}
<div class="sai-modal-backdrop" id="scheduleModal" onclick="if(event.target===this) closeScheduleModal()">
    <div class="sai-modal">
        <button class="sai-modal-close" onclick="closeScheduleModal()"><i class="fas fa-xmark"></i></button>
        <h4 style="margin-bottom:6px"><i class="fas fa-calendar-plus" style="color:var(--primary-color,#4F46E5);margin-right:8px"></i>Agendar no Calendário</h4>
        <p style="font-size:.82rem;color:var(--text-secondary,#6B7280);margin-bottom:22px">O post será enviado para o Calendário de Publicação do Facebook/Instagram.</p>

        <span class="sai-modal-label">Plataforma</span>
        <div class="sai-platform-btns">
            <label for="sch_platform" class="sai-platform-btn">
                <input type="radio" name="sch_platform" value="facebook" checked id="sch_platform">
                <i class="fab fa-facebook-f"></i> Facebook
            </label>
            <label class="sai-platform-btn">
                <input type="radio" name="sch_platform" value="instagram" id="sch_platform">
                <i class="fab fa-instagram"></i> Instagram
            </label>
            <label class="sai-platform-btn">
                <input type="radio" name="sch_platform" value="both" id="sch_platform">
                <i class="fas fa-layer-group"></i> Ambas
            </label>
        </div>

        <span class="sai-modal-label">Data e Hora de Publicação</span>
        <input type="datetime-local" id="sch_datetime" class="sai-datetime-input">

        <div class="sai-modal-actions" style="margin-top:22px">
            <button class="sai-modal-btn secondary" onclick="closeScheduleModal()">
                Cancelar
            </button>
            <button class="sai-modal-btn primary" id="btnConfirmSchedule" onclick="confirmSchedule()">
                <i class="fas fa-calendar-check"></i> Agendar
            </button>
        </div>
    </div>
</div>

{{-- ── MODAL CONTEÚDO ───────────────────────────────── --}}
<div class="sai-modal-backdrop" id="postModal" onclick="if(event.target===this) closeModal()">
    <div class="sai-modal">
        <button class="sai-modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <img loading="lazy" id="modalImg" src="" alt="" style="display:none">
        <h4 id="modalTheme"></h4>
        <div class="sai-modal-caption" id="modalCaption"></div>
        <div class="sai-modal-actions">
            <button class="sai-modal-btn primary" onclick="copyModalCaption()">
                <i class="fas fa-copy"></i> Copiar Legenda
            </button>
            <button class="sai-modal-btn secondary" onclick="sendWhatsAppModal()">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
        </div>
    </div>
</div>

{{-- ── TOAST CONTAINER ─────────────────────────── --}}
<div id="sai-toast-container"></div>

@push('scripts')
<script>
const CSRF  = '{{ csrf_token() }}';
const ROUTE_GENERATE = '{{ route("social-ai.generate") }}';
const ROUTE_STATUS   = (id) => `/social-ai/${id}/status`;
const ROUTE_DESTROY  = (id) => `/social-ai/${id}`;

// ── Toast ──────────────────────────────────────────
function toast(msg, type = 'info') {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const el = document.createElement('div');
    el.className = `sai-toast ${type}`;
    el.innerHTML = `<span class="t-icon">${icons[type]}</span><span>${msg}</span>`;
    document.getElementById('sai-toast-container').appendChild(el);
    setTimeout(() => el.remove(), 4500);
}

// ── Context panel toggle ───────────────────────────
function toggleContext() {
    const panel = document.getElementById('ctxPanel');
    const arrow = document.getElementById('ctxArrow');
    panel.classList.toggle('open');
    arrow.classList.toggle('open');
}
function updateCtxCount() {
    const el  = document.getElementById('userContext');
    const cnt = document.getElementById('ctxCount');
    cnt.textContent = `${el.value.length} / 1000`;
    cnt.classList.toggle('near-limit', el.value.length > 800);
}

// ── Theme suggestions ──────────────────────────────
function setTheme(text) {
    document.getElementById('postTheme').value = text;
    document.getElementById('postTheme').focus();
}

// ── Generate ───────────────────────────────────────
// State das opções (formato / estilo visual)
window._saiOpts = { format: 'square', visual_style: '' };

function pickOpt(btn, group) {
    const scope = btn.closest('.sai-opt-btns');
    scope.querySelectorAll('.sai-opt-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    window._saiOpts[group] = btn.dataset.value;
}

// Upload de imagem de referência
function onRefChange(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        toast('Imagem muito grande (máx 5MB).', 'error');
        input.value = '';
        return;
    }
    const preview = document.getElementById('refPreview');
    const drop    = document.getElementById('refDropLabel');
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        drop.classList.add('has-image');
        document.getElementById('refOptsWrap').style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

function removeRef() {
    document.getElementById('referenceImage').value = '';
    document.getElementById('refPreview').style.display = 'none';
    document.getElementById('refDropLabel').classList.remove('has-image');
    document.getElementById('refOptsWrap').style.display = 'none';
}

async function generatePost() {
    const theme = document.getElementById('postTheme').value.trim();
    if (!theme) { toast('Digite um tema para gerar o post.', 'error'); return; }

    const btn  = document.getElementById('btnGenerate');
    const icon = document.getElementById('btnIcon');
    const text = document.getElementById('btnText');

    btn.disabled  = true;
    icon.className = 'fas fa-circle-notch fa-spin';
    text.textContent = 'Iniciando...';

    const userContext = document.getElementById('userContext').value.trim();
    const refInput = document.getElementById('referenceImage');
    const refFile = refInput.files[0] || null;
    const refMode = document.querySelector('input[name="ref_mode"]:checked')?.value || 'reference';

    // FormData pra suportar upload multipart
    const fd = new FormData();
    fd.append('theme', theme);
    if (userContext) fd.append('user_context', userContext);
    fd.append('format', window._saiOpts.format || 'square');
    if (window._saiOpts.visual_style) fd.append('visual_style', window._saiOpts.visual_style);
    if (refFile) {
        fd.append('reference_image', refFile);
        fd.append('use_reference_as_final', refMode === 'final' ? '1' : '0');
    }

    try {
        const res  = await fetch(ROUTE_GENERATE, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        const data = await res.json();

        if (data.success) {
            toast(data.message, 'success');
            document.getElementById('postTheme').value  = '';
            document.getElementById('userContext').value = '';
            updateCtxCount();
            removeRef();
            setTimeout(() => location.reload(), 1200);
        } else {
            toast(data.message || 'Erro ao iniciar geração.', 'error');
        }
    } catch (e) {
        toast('Falha na comunicação com o servidor.', 'error');
    } finally {
        btn.disabled  = false;
        icon.className = 'fas fa-magic';
        text.textContent = 'Gerar Post';
    }
}

// ── Regenerar só legenda / imagem / escolher variação ──────────────────
async function regenerateCaption(postId, btn) {
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Gerando...';
    try {
        const res = await fetch('/social-ai/' + postId + '/regenerate-caption', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            toast('Novas legendas geradas.', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            toast(data.message || 'Falha ao regenerar.', 'error');
            btn.innerHTML = original; btn.disabled = false;
        }
    } catch (e) {
        toast('Falha na comunicação.', 'error');
        btn.innerHTML = original; btn.disabled = false;
    }
}

async function regenerateImage(postId, btn) {
    if (!confirm('Gerar nova imagem consome 1 crédito da cota mensal. Continuar?')) return;
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Gerando...';
    try {
        const res = await fetch('/social-ai/' + postId + '/regenerate-image', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            toast('Nova imagem gerada.', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            toast(data.message || 'Falha ao regenerar imagem.', 'error');
            btn.innerHTML = original; btn.disabled = false;
        }
    } catch (e) {
        toast('Falha na comunicação.', 'error');
        btn.innerHTML = original; btn.disabled = false;
    }
}

async function chooseVariation(postId, index) {
    try {
        const res = await fetch('/social-ai/' + postId + '/choose-variation/' + index, {
            method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('caption-' + postId).textContent = data.body_text;
            // Atualiza pills active
            document.querySelectorAll('#vars-' + postId + ' .sai-var-pill').forEach((p, i) => {
                p.classList.toggle('active', i === index);
            });
        } else {
            toast(data.message || 'Falha ao escolher.', 'error');
        }
    } catch (e) {
        toast('Falha na comunicação.', 'error');
    }
}

// ── Copy caption ───────────────────────────────────
function copyCaption(id, text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => toast('Legenda copiada!', 'success'));
}

// ── WhatsApp share ─────────────────────────────────
function sendWhatsApp(text) {
    if (!text) return;
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
}

// ── Modal ──────────────────────────────────────────
let _modalCaption = '', _modalImg = '';
function openModal(id, theme, caption, imgUrl) {
    _modalCaption = caption;
    _modalImg     = imgUrl;
    document.getElementById('modalTheme').textContent   = theme;
    document.getElementById('modalCaption').textContent = caption || '—';
    const img = document.getElementById('modalImg');
    if (imgUrl) { img.src = imgUrl; img.style.display = 'block'; }
    else         { img.style.display = 'none'; }
    document.getElementById('postModal').classList.add('open');
}
function closeModal() {
    document.getElementById('postModal').classList.remove('open');
}
function copyModalCaption() {
    navigator.clipboard.writeText(_modalCaption).then(() => { toast('Legenda copiada!', 'success'); closeModal(); });
}
function sendWhatsAppModal() {
    sendWhatsApp(_modalCaption);
}

// ── Delete ─────────────────────────────────────────
async function deletePost(id) {
    if (!confirm('Excluir este rascunho permanentemente?')) return;
    try {
        const res  = await fetch(ROUTE_DESTROY(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            const card = document.getElementById(`postCard-${id}`);
            if (card) { card.style.transition = 'opacity .4s'; card.style.opacity = '0'; setTimeout(() => card.remove(), 400); }
            toast('Rascunho excluído.', 'info');
        } else {
            toast('Erro ao excluir.', 'error');
        }
    } catch (e) {
        toast('Falha na comunicação.', 'error');
    }
}

// ── Schedule Modal ─────────────────────────────────
let _schedulePostId = null;
function openScheduleModal(postId) {
    _schedulePostId = postId;
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const min = now.toISOString().slice(0, 16);
    const dt  = document.getElementById('sch_datetime');
    dt.min   = min;
    dt.value = min;
    document.getElementById('scheduleModal').classList.add('open');
}
function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('open');
    _schedulePostId = null;
}
async function confirmSchedule() {
    const platform = document.querySelector('input[name="sch_platform"]:checked')?.value;
    const datetime = document.getElementById('sch_datetime').value;
    if (!datetime) { toast('Selecione uma data e hora.', 'error'); return; }

    const btn = document.getElementById('btnConfirmSchedule');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Agendando...';

    try {
        const res  = await fetch(`/social-ai/${_schedulePostId}/schedule`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body:    JSON.stringify({ platform, scheduled_at: datetime }),
        });
        const data = await res.json();

        if (data.success) {
            closeScheduleModal();
            toast(data.message, 'success');
            setTimeout(() => window.location.href = data.calendar_url, 1800);
        } else {
            toast(data.message, 'error');
            if (data.connect_url) setTimeout(() => window.location.href = data.connect_url, 2500);
        }
    } catch (e) {
        toast('Falha na comunicação com o servidor.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-calendar-check"></i> Agendar';
    }
}

// ── Polling for processing posts ───────────────────
@php $processingIds = $posts->where('status', 'processing')->pluck('id')->toArray(); @endphp
@if(count($processingIds) > 0)
const processingIds = @json($processingIds);
let pollAttempts = {};

async function pollPost(id) {
    try {
        const res  = await fetch(ROUTE_STATUS(id), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (data.status !== 'processing') {
            // Reload the page to show the new card with proper content
            toast('Novo post gerado com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            pollAttempts[id] = (pollAttempts[id] || 0) + 1;
            if (pollAttempts[id] < 24) { // max ~4 min polling
                setTimeout(() => pollPost(id), 10000);
            }
        }
    } catch (e) {
        setTimeout(() => pollPost(id), 15000);
    }
}

processingIds.forEach(id => { pollAttempts[id] = 0; setTimeout(() => pollPost(id), 10000); });
@endif
</script>
@endpush

@endsection
