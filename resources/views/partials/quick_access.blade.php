@php
    $role = auth()->user()->role ?? 'common';

    // ── Botões por categoria ────────────────────────────────────────────
    $marketing = [];
    $gestao    = [];

    // Social AI Hub (todos)
    $marketing[] = ['icon' => 'fa-robot',          'label' => 'Social AI Hub',   'url' => url('/social-ai'),                 'color' => '#7C3AED', 'bg' => '#F4F3FF'];
    // WhatsApp Broadcast (ngo e manager)
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $marketing[] = ['icon' => 'fa-paper-plane', 'label' => 'Disparo em Massa','url' => route('whatsapp.broadcast.index'), 'color' => '#16A34A', 'bg' => '#F0FDF4'];
    }
    // Posts Redes Sociais
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $marketing[] = ['icon' => 'fa-share-nodes', 'label' => 'Redes Sociais',   'url' => url('/social/posts'),              'color' => '#0891B2', 'bg' => '#ECFEFF'];
    }
    // Campanhas (ngo)
    if (in_array($role, ['ngo', 'super_admin'])) {
        $marketing[] = ['icon' => 'fa-bullhorn',    'label' => 'Campanhas',        'url' => url('/ngo/campaigns'),             'color' => '#D97706', 'bg' => '#FFFBEB'];
    }
    // Landing Pages
    if ($role === 'ngo') {
        $marketing[] = ['icon' => 'fa-globe',       'label' => 'Landing Pages',    'url' => url('/ngo/landing-pages'),         'color' => '#DB2777', 'bg' => '#FDF2F8'];
    } elseif ($role === 'manager') {
        $marketing[] = ['icon' => 'fa-globe',       'label' => 'Landing Pages',    'url' => url('/manager/landing-pages'),     'color' => '#DB2777', 'bg' => '#FDF2F8'];
    }
    // Marketing IA / Estratégia (ngo e manager)
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $marketing[] = ['icon' => 'fa-wand-magic-sparkles', 'label' => 'Marketing IA', 'url' => url('/marketing'), 'color' => '#EA580C', 'bg' => '#FFF7ED'];
    }

    // ── Gestão ──────────────────────────────────────────────────────────
    // Nova Transação (todos)
    $gestao[] = ['icon' => 'fa-circle-plus',     'label' => 'Novo Lançamento',   'url' => url('/transactions/create'),       'color' => '#10B981', 'bg' => '#ECFDF5'];
    // Projetos (manager / ngo)
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $gestao[] = ['icon' => 'fa-diagram-project','label' => 'Projetos',         'url' => url('/projects'),                  'color' => '#4F46E5', 'bg' => '#EEF2FF'];
    }
    // Tarefas (todos)
    $gestao[] = ['icon' => 'fa-list-check',       'label' => 'Tarefas',           'url' => url('/tasks'),                     'color' => '#0284C7', 'bg' => '#F0F9FF'];
    // Agenda (manager)
    if ($role === 'manager') {
        $gestao[] = ['icon' => 'fa-calendar-days','label' => 'Agenda',             'url' => url('/manager/schedule'),          'color' => '#7C3AED', 'bg' => '#F4F3FF'];
    }
    // Editais (ngo)
    if (in_array($role, ['ngo', 'super_admin'])) {
        $gestao[] = ['icon' => 'fa-file-contract','label' => 'Editais',            'url' => url('/ngo/grants'),                'color' => '#B45309', 'bg' => '#FFFBEB'];
    }
    // Doadores/CRM (ngo)
    if (in_array($role, ['ngo', 'super_admin'])) {
        $gestao[] = ['icon' => 'fa-heart-handshake','label' => 'Doadores',         'url' => url('/ngo/donors'),                'color' => '#BE185D', 'bg' => '#FDF2F8'];
    }
    // Relatórios / Análise (ngo e manager)
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $gestao[] = ['icon' => 'fa-chart-bar',    'label' => 'Relatórios',         'url' => url('/smart-analysis'),            'color' => '#6D28D9', 'bg' => '#F4F3FF'];
    }
    // Prospecção (manager)
    if (in_array($role, ['manager', 'super_admin'])) {
        $gestao[] = ['icon' => 'fa-crosshairs', 'label' => 'Prospecção', 'url' => url('/prospecting'), 'color' => '#B45309', 'bg' => '#FFFBEB'];
    }
    // Inteligência Territorial (todos)
    $gestao[] = ['icon' => 'fa-map-location-dot','label' => 'Inteligência Terr.', 'url' => route('intelligence.territorial'), 'color' => '#0F766E', 'bg' => '#F0FDFA'];
@endphp

<div class="qa-wrap" style="margin-bottom: 24px;">

    <style>
    .qa-wrap {
        background: #ffffff;
        border: 1px solid #EAECF0;
        border-radius: 16px;
        padding: 20px 24px;
        box-shadow: 0 1px 3px rgba(16,24,40,.04), 0 4px 12px rgba(16,24,40,.03);
    }
    .qa-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }
    .qa-header-title {
        font-size: .65rem;
        font-weight: 900;
        color: #98A2B3;
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }
    .qa-sep {
        flex: 1;
        height: 1px;
        background: #F2F4F7;
    }
    .qa-cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    @media (max-width: 768px) {
        .qa-cols { grid-template-columns: 1fr; }
    }
    .qa-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .qa-group-label {
        font-size: .6rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.4px;
        color: #C0C7D2;
        padding: 0 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .qa-group-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #F2F4F7;
    }
    .qa-btns {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }
    .qa-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 13px;
        border-radius: 10px;
        font-size: .75rem;
        font-weight: 700;
        text-decoration: none;
        transition: transform .15s, box-shadow .15s, filter .15s;
        border: 1px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        line-height: 1;
    }
    .qa-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
        filter: brightness(1.04);
        text-decoration: none;
    }
    .qa-btn:active { transform: translateY(0); }
    .qa-btn-icon {
        width: 22px;
        height: 22px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .65rem;
        flex-shrink: 0;
    }
    </style>

    <div class="qa-header">
        <i class="fas fa-bolt" style="color: var(--primary-color, #4F46E5); font-size: .7rem;"></i>
        <span class="qa-header-title">Acesso Rápido</span>
        <div class="qa-sep"></div>
    </div>

    <div class="qa-cols">

        {{-- ── Marketing ───────────────────────────────────── --}}
        <div class="qa-group">
            <div class="qa-group-label">
                <i class="fas fa-megaphone" style="font-size:.55rem"></i> Marketing
            </div>
            <div class="qa-btns">
                @foreach($marketing as $btn)
                <a href="{{ $btn['url'] }}" class="qa-btn"
                   style="background: {{ $btn['bg'] }}; color: {{ $btn['color'] }}; border-color: {{ $btn['color'] }}18;">
                    <span class="qa-btn-icon" style="background: {{ $btn['color'] }}15;">
                        <i class="fas {{ $btn['icon'] }}"></i>
                    </span>
                    {{ $btn['label'] }}
                </a>
                @endforeach
            </div>
        </div>

        {{-- ── Gestão ───────────────────────────────────────── --}}
        <div class="qa-group">
            <div class="qa-group-label">
                <i class="fas fa-gears" style="font-size:.55rem"></i> Gestão
            </div>
            <div class="qa-btns">
                @foreach($gestao as $btn)
                <a href="{{ $btn['url'] }}" class="qa-btn"
                   style="background: {{ $btn['bg'] }}; color: {{ $btn['color'] }}; border-color: {{ $btn['color'] }}18;">
                    <span class="qa-btn-icon" style="background: {{ $btn['color'] }}15;">
                        <i class="fas {{ $btn['icon'] }}"></i>
                    </span>
                    {{ $btn['label'] }}
                </a>
                @endforeach
            </div>
        </div>

    </div>
</div>
