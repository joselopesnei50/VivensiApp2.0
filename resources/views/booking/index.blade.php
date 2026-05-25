<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Reunião – Vivensi</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        /* ── Card principal ── */
        .booking-card {
            display: flex;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.10);
            overflow: hidden;
            width: 100%;
            max-width: 860px;
            min-height: 540px;
        }

        /* ── Painel esquerdo ── */
        .panel-left {
            width: 300px;
            min-width: 300px;
            border-right: 1px solid #e8edf2;
            padding: 32px 28px;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .pl-logo {
            width: 54px; height: 54px;
            background: #0a0a0a;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; font-weight: 900; color: #fff;
            margin-bottom: 20px;
        }
        .pl-host {
            font-size: .72rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .pl-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 20px;
        }
        .pl-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 24px;
        }
        .pl-meta-row {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: .8rem;
            color: #475569;
            font-weight: 500;
        }
        .pl-meta-row svg { flex-shrink: 0; color: #94a3b8; }
        .pl-divider { height: 1px; background: #f1f5f9; margin: 16px 0; }
        .pl-desc {
            font-size: .78rem;
            color: #64748b;
            line-height: 1.65;
        }

        /* selected date/time badge shown during step 2 */
        .pl-selected-badge {
            margin-top: 20px;
            background: #f0f4ff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: .78rem;
            color: #3730a3;
            font-weight: 600;
            line-height: 1.5;
            display: none;
        }
        .pl-selected-badge.show { display: block; }

        /* ── Painel direito ── */
        .panel-right {
            flex: 1;
            padding: 32px 32px 32px;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* ── Back button ── */
        .back-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: none; border: 1px solid #e2e8f0;
            border-radius: 50%; width: 32px; height: 32px;
            cursor: pointer; color: #475569;
            justify-content: center;
            margin-bottom: 20px;
            transition: background .15s;
        }
        .back-btn:hover { background: #f1f5f9; }
        .back-btn[hidden] { display: none; }

        /* ── Step heading ── */
        .step-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 20px;
        }

        /* ═══════════════════════════════════════ STEP 1: Calendar */
        .calendar-wrap { display: flex; gap: 24px; flex: 1; }

        /* month calendar */
        .cal {
            flex: 1;
        }
        .cal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .cal-month {
            font-size: .9rem;
            font-weight: 800;
            color: #0f172a;
        }
        .cal-nav {
            background: none; border: none; cursor: pointer;
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #64748b;
            transition: background .15s;
        }
        .cal-nav:hover { background: #f1f5f9; }
        .cal-nav:disabled { opacity: .3; cursor: default; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
        .cal-dow {
            text-align: center;
            font-size: .65rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding-bottom: 8px;
        }
        .cal-day {
            aspect-ratio: 1;
            display: flex; align-items: center; justify-content: center;
            font-size: .82rem;
            font-weight: 500;
            color: #94a3b8;
            border-radius: 50%;
            cursor: default;
        }
        .cal-day.available {
            color: #0f172a;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s, color .15s;
        }
        .cal-day.available:hover { background: #e0e7ff; }
        .cal-day.selected { background: #4f6ef7 !important; color: #fff !important; font-weight: 800; }
        .cal-day.today { border: 1.5px solid #4f6ef7; color: #4f6ef7; font-weight: 800; }
        .cal-day.today.available { border-color: #4f6ef7; }

        /* timezone row */
        .tz-row {
            display: flex; align-items: center; gap: 6px;
            margin-top: 16px;
            font-size: .72rem; color: #94a3b8; font-weight: 500;
        }

        /* time slots */
        .slots-wrap {
            width: 160px;
            min-width: 160px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow-y: auto;
            max-height: 360px;
            padding-right: 4px;
        }
        .slots-wrap::-webkit-scrollbar { width: 4px; }
        .slots-wrap::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

        .slot-placeholder {
            font-size: .8rem;
            color: #94a3b8;
            text-align: center;
            padding: 20px 0;
            line-height: 1.6;
        }
        .slot-btn {
            background: none;
            border: 1.5px solid #4f6ef7;
            border-radius: 8px;
            padding: 10px 0;
            font-size: .85rem;
            font-weight: 700;
            color: #4f6ef7;
            cursor: pointer;
            transition: background .15s, color .15s;
            text-align: center;
        }
        .slot-btn:hover { background: #4f6ef7; color: #fff; }
        .slot-loading {
            display: flex; align-items: center; justify-content: center;
            padding: 20px 0;
        }
        .spinner {
            width: 20px; height: 20px;
            border: 2px solid #e2e8f0;
            border-top-color: #4f6ef7;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ═══════════════════════════════════════ STEP 2: Form */
        .booking-form { display: flex; flex-direction: column; gap: 18px; flex: 1; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label {
            font-size: .72rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .form-label span { color: #ef4444; }
        .form-input, .form-textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: .88rem;
            font-family: inherit;
            color: #0f172a;
            outline: none;
            transition: border-color .2s;
            background: #fff;
        }
        .form-input:focus, .form-textarea:focus { border-color: #4f6ef7; }
        .form-textarea { resize: vertical; min-height: 80px; }
        .form-error { font-size: .72rem; color: #ef4444; font-weight: 600; display: none; }
        .form-error.show { display: block; }

        .submit-btn {
            padding: 13px 28px;
            background: #4f6ef7;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: .9rem;
            font-weight: 800;
            cursor: pointer;
            transition: background .15s, transform .1s;
            align-self: flex-start;
        }
        .submit-btn:hover { background: #3b5bd9; }
        .submit-btn:active { transform: scale(.98); }
        .submit-btn:disabled { opacity: .6; cursor: default; }

        /* ═══════════════════════════════════════ STEP 3: Success */
        .success-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 12px;
            padding: 20px 0;
        }
        .success-icon {
            width: 64px; height: 64px;
            background: #ecfdf5;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .success-title {
            font-size: 1.2rem;
            font-weight: 900;
            color: #0f172a;
        }
        .success-desc {
            font-size: .85rem;
            color: #64748b;
            line-height: 1.65;
            max-width: 340px;
        }
        .success-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 24px;
            text-align: left;
            width: 100%;
            max-width: 360px;
        }
        .success-card-row {
            display: flex; align-items: flex-start; gap: 8px;
            font-size: .82rem; color: #475569; font-weight: 500;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .success-card-row:last-child { border-bottom: none; }
        .success-card-row strong { color: #0f172a; font-weight: 700; }
        .new-booking-btn {
            margin-top: 8px;
            padding: 10px 22px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
        }
        .new-booking-btn:hover { background: #e2e8f0; }

        /* ── Powered by ── */
        .powered-corner {
            position: absolute;
            top: 0; right: 0;
            background: #334155;
            color: #fff;
            font-size: .55rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 6px 10px 8px;
            border-radius: 0 16px 0 12px;
            line-height: 1.3;
            text-align: center;
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .booking-card { flex-direction: column; }
            .panel-left { width: 100%; min-width: unset; border-right: none; border-bottom: 1px solid #e8edf2; padding: 24px 20px; }
            .panel-right { padding: 24px 20px; }
            .calendar-wrap { flex-direction: column; }
            .slots-wrap { width: 100%; max-height: 200px; flex-direction: row; flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<div style="position:relative; width:100%; max-width:860px;">

<div class="booking-card" id="bookingCard">

    {{-- ═══ PAINEL ESQUERDO ═══ --}}
    <div class="panel-left">
        <div class="pl-logo">V</div>
        <p class="pl-host">Equipe Vivensi</p>
        <h2 class="pl-title">Reunião de 30 Minutos</h2>
        <div class="pl-meta">
            <div class="pl-meta-row">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                30 min
            </div>
            <div class="pl-meta-row">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Videoconferência
            </div>
            <div class="pl-meta-row">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Horário de Brasília
            </div>
        </div>
        <div class="pl-divider"></div>
        <p class="pl-desc">
            Agende uma conversa com nossa equipe para conhecer a plataforma, tirar dúvidas ou discutir como o Vivensi pode transformar a gestão da sua organização.
        </p>
        <div class="pl-selected-badge" id="selectedBadge"></div>
    </div>

    {{-- ═══ PAINEL DIREITO ═══ --}}
    <div class="panel-right">

        {{-- Back button --}}
        <button class="back-btn" id="backBtn" hidden onclick="goBack()">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        {{-- ─── STEP 1: Calendário ─── --}}
        <div id="step1">
            <h3 class="step-heading">Selecione uma data e horário</h3>
            <div class="calendar-wrap">
                {{-- Calendário mensal --}}
                <div class="cal">
                    <div class="cal-header">
                        <button class="cal-nav" id="prevMonth" onclick="changeMonth(-1)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <span class="cal-month" id="calMonthLabel"></span>
                        <button class="cal-nav" id="nextMonth" onclick="changeMonth(1)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                    <div class="tz-row">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Brasília (GMT-3)
                    </div>
                </div>

                {{-- Slots de horário --}}
                <div class="slots-wrap" id="slotsWrap">
                    <p class="slot-placeholder">← Selecione<br>uma data</p>
                </div>
            </div>
        </div>

        {{-- ─── STEP 2: Formulário ─── --}}
        <div id="step2" style="display:none; flex-direction:column; flex:1;">
            <h3 class="step-heading">Insira seus dados</h3>
            <form class="booking-form" id="bookingForm" onsubmit="submitBooking(event)">
                <div class="form-group">
                    <label class="form-label">Nome <span>*</span></label>
                    <input type="text" class="form-input" id="inputName" placeholder="Seu nome completo" required>
                    <span class="form-error" id="errName"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail <span>*</span></label>
                    <input type="email" class="form-input" id="inputEmail" placeholder="seu@email.com" required>
                    <span class="form-error" id="errEmail"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="tel" class="form-input" id="inputPhone" placeholder="(11) 99999-9999">
                </div>
                <div class="form-group">
                    <label class="form-label">Sobre o que você quer conversar?</label>
                    <textarea class="form-textarea" id="inputNotes" placeholder="Descreva brevemente o objetivo da reunião..."></textarea>
                </div>
                <div>
                    <button type="submit" class="submit-btn" id="submitBtn">Agendar Reunião →</button>
                    <span class="form-error" id="errGeneral" style="margin-top:8px;display:none;"></span>
                </div>
            </form>
        </div>

        {{-- ─── STEP 3: Confirmação ─── --}}
        <div id="step3" style="display:none;">
            <div class="success-wrap">
                <div class="success-icon">✅</div>
                <h3 class="success-title">Reunião agendada!</h3>
                <p class="success-desc" id="successDesc">
                    Você receberá um e-mail de confirmação com todos os detalhes.
                </p>
                <div class="success-card" id="successCard"></div>
                <button class="new-booking-btn" onclick="resetBooking()">Fazer novo agendamento</button>
            </div>
        </div>

    </div>{{-- /panel-right --}}
</div>{{-- /booking-card --}}

<div class="powered-corner">Powered by<br><strong>Vivensi</strong></div>

</div>{{-- /wrapper --}}

<script>
// ─── State ───────────────────────────────────────────────────────────────
const MONTHS_PT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const DAYS_PT   = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

let viewDate    = new Date();            // month being displayed
let selectedDate = null;                 // 'YYYY-MM-DD'
let selectedTime = null;                 // 'HH:MM'
let currentStep  = 1;

viewDate.setDate(1);

// ─── Calendar render ─────────────────────────────────────────────────────
function renderCalendar() {
    const year  = viewDate.getFullYear();
    const month = viewDate.getMonth();

    document.getElementById('calMonthLabel').textContent = `${MONTHS_PT[month]} ${year}`;

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    // Day-of-week headers
    DAYS_PT.forEach(d => {
        const el = document.createElement('div');
        el.className = 'cal-dow';
        el.textContent = d;
        grid.appendChild(el);
    });

    const firstDow = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    today.setHours(0,0,0,0);

    // Empty cells before month start
    for (let i = 0; i < firstDow; i++) {
        const el = document.createElement('div');
        el.className = 'cal-day';
        grid.appendChild(el);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const date   = new Date(year, month, d);
        const el     = document.createElement('div');
        const dateStr = toDateStr(year, month, d);
        const isToday   = date.getTime() === today.getTime();
        const isPast    = date < today;
        // Convert JS getDay() (0=Sun,1=Mon...) to ISO-like (Mon=1...Sun=0)
        const jsDay     = date.getDay();
        const isoDay    = jsDay === 0 ? 0 : jsDay; // same mapping used in PHP
        const isDayActive = ACTIVE_DAYS.includes(isoDay);

        el.className = 'cal-day';
        el.textContent = d;

        if (isToday) el.classList.add('today');

        if (!isPast && isDayActive) {
            el.classList.add('available');
            el.addEventListener('click', () => selectDate(dateStr, el));
        }

        if (dateStr === selectedDate) el.classList.add('selected');

        grid.appendChild(el);
    }

    // Prev button disabled if we're already in current month
    const nowMonth = new Date();
    nowMonth.setDate(1); nowMonth.setHours(0,0,0,0);
    document.getElementById('prevMonth').disabled = (viewDate <= nowMonth);
}

function toDateStr(y, m, d) {
    return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}

function changeMonth(delta) {
    viewDate.setMonth(viewDate.getMonth() + delta);
    renderCalendar();
    // Clear slots if month changed
    if (selectedDate) {
        const [sy, sm] = selectedDate.split('-').map(Number);
        if (sm - 1 !== viewDate.getMonth() || sy !== viewDate.getFullYear()) {
            selectedDate = null;
            selectedTime = null;
            document.getElementById('slotsWrap').innerHTML = '<p class="slot-placeholder">← Selecione<br>uma data</p>';
        }
    }
}

// ─── Date selection ───────────────────────────────────────────────────────
function selectDate(dateStr, el) {
    // Deselect previous
    document.querySelectorAll('.cal-day.selected').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    selectedDate = dateStr;
    selectedTime = null;

    loadSlots(dateStr);
}

async function loadSlots(dateStr) {
    const wrap = document.getElementById('slotsWrap');
    wrap.innerHTML = '<div class="slot-loading"><div class="spinner"></div></div>';

    try {
        const res  = await fetch(`{{ route('booking.slots') }}?date=${dateStr}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        renderSlots(data.slots || []);
    } catch (e) {
        wrap.innerHTML = '<p class="slot-placeholder" style="color:#ef4444;">Erro ao carregar horários.</p>';
    }
}

function renderSlots(slots) {
    const wrap = document.getElementById('slotsWrap');
    if (!slots.length) {
        wrap.innerHTML = '<p class="slot-placeholder">Nenhum horário disponível nesta data.</p>';
        return;
    }
    wrap.innerHTML = '';
    slots.forEach(slot => {
        const btn = document.createElement('button');
        btn.className = 'slot-btn';
        btn.textContent = slot;
        btn.addEventListener('click', () => selectSlot(slot));
        wrap.appendChild(btn);
    });
}

function selectSlot(time) {
    selectedTime = time;
    goToStep2();
}

// ─── Step transitions ─────────────────────────────────────────────────────
function goToStep2() {
    currentStep = 2;
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'flex';
    document.getElementById('backBtn').hidden = false;

    // Show badge in left panel
    const d = formatDatePT(selectedDate);
    document.getElementById('selectedBadge').innerHTML =
        `📅 <strong>${d}</strong><br>🕐 ${selectedTime} (Brasília)`;
    document.getElementById('selectedBadge').classList.add('show');
}

function goBack() {
    if (currentStep === 2) {
        currentStep = 1;
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
        document.getElementById('backBtn').hidden = true;
        document.getElementById('selectedBadge').classList.remove('show');
        selectedTime = null;
    }
}

function goToStep3(data) {
    currentStep = 3;
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'block';
    document.getElementById('backBtn').hidden = true;

    document.getElementById('successDesc').textContent =
        `${data.name}, sua reunião foi agendada com sucesso! Você receberá um e-mail de confirmação.`;

    document.getElementById('successCard').innerHTML = `
        <div class="success-card-row">
            <span>📅</span><span><strong>${data.date}</strong></span>
        </div>
        <div class="success-card-row">
            <span>🕐</span><span><strong>${data.time}</strong> — Horário de Brasília</span>
        </div>
        <div class="success-card-row">
            <span>⏱</span><span>30 minutos · Videoconferência</span>
        </div>
    `;
}

function resetBooking() {
    currentStep = 1;
    selectedDate = null;
    selectedTime = null;
    document.getElementById('step3').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    document.getElementById('backBtn').hidden = true;
    document.getElementById('selectedBadge').classList.remove('show');
    document.getElementById('bookingForm').reset();
    document.querySelectorAll('.form-error').forEach(e => { e.textContent=''; e.classList.remove('show'); });
    document.querySelectorAll('.cal-day.selected').forEach(e => e.classList.remove('selected'));
    document.getElementById('slotsWrap').innerHTML = '<p class="slot-placeholder">← Selecione<br>uma data</p>';
    renderCalendar();
}

// ─── Form submit ──────────────────────────────────────────────────────────
async function submitBooking(e) {
    e.preventDefault();
    clearErrors();

    const name  = document.getElementById('inputName').value.trim();
    const email = document.getElementById('inputEmail').value.trim();
    const phone = document.getElementById('inputPhone').value.trim();
    const notes = document.getElementById('inputNotes').value.trim();

    let valid = true;
    if (!name) { showError('errName', 'Nome é obrigatório.'); valid = false; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('errEmail', 'E-mail inválido.'); valid = false; }
    if (!valid) return;

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Agendando...';

    try {
        const res = await fetch('{{ route('booking.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name, email, phone: phone || null, notes: notes || null,
                meeting_date: selectedDate,
                meeting_time: selectedTime,
            }),
        });

        const data = await res.json();

        if (!res.ok) {
            const msg = data.error || data.message || 'Erro ao realizar agendamento.';
            showError('errGeneral', msg);
            document.getElementById('errGeneral').style.display = 'block';
        } else {
            goToStep3(data);
        }
    } catch (err) {
        showError('errGeneral', 'Erro de conexão. Tente novamente.');
        document.getElementById('errGeneral').style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Agendar Reunião →';
    }
}

function showError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.classList.add('show');
    el.style.display = 'block';
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(e => {
        e.textContent = '';
        e.classList.remove('show');
        e.style.display = 'none';
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function formatDatePT(dateStr) {
    const [y, m, d] = dateStr.split('-').map(Number);
    const date = new Date(y, m-1, d);
    const dow  = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date.getDay()];
    return `${dow}, ${d} de ${MONTHS_PT[m-1]} de ${y}`;
}

// ─── Init ─────────────────────────────────────────────────────────────────
// Active booking days from admin settings (ISO day numbers: Mon=1...Sun=0)
const ACTIVE_DAYS = {!! json_encode(array_map('intval', explode(',', \App\Models\SystemSetting::getValue('booking_days', '1,2,3,4,5'))), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};

renderCalendar();
</script>
</body>
</html>
