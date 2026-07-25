@php
    $role = auth()->user()->role ?? 'common';

    // 4 cards adaptados ao papel do usuário
    $cards = [
        [
            'icon'     => 'fa-circle-plus',
            'label'    => 'Novo Lançamento',
            'desc'     => 'Registre receitas e despesas',
            'url'      => url('/transactions/create'),
            'gradient' => 'linear-gradient(135deg, #10B981, #059669)',
            'shadow'   => 'rgba(16,185,129,.25)',
            'light'    => '#ECFDF5',
        ],
        $role === 'ngo' ? [
            'icon'     => 'fa-people-roof',
            'label'    => 'Beneficiários',
            'desc'     => 'Gerencie e consulte seus beneficiários',
            'url'      => url('/ngo/beneficiaries'),
            'gradient' => 'linear-gradient(135deg, #F97316, #EA580C)',
            'shadow'   => 'rgba(249,115,22,.30)',
            'light'    => '#FFF7ED',
            'featured' => true,
        ] : [
            'icon'     => 'fa-robot',
            'label'    => 'Social AI Hub',
            'desc'     => 'Crie posts com inteligência artificial',
            'url'      => url('/social-ai'),
            'gradient' => 'linear-gradient(135deg, #7C3AED, #6D28D9)',
            'shadow'   => 'rgba(124,58,237,.25)',
            'light'    => '#F4F3FF',
        ],
    ];

    // Card 3 — varia por papel
    if (in_array($role, ['ngo', 'manager', 'super_admin'])) {
        $cards[] = [
            'icon'     => 'fa-paper-plane',
            'label'    => 'Disparo em Massa',
            'desc'     => 'Envie mensagens via WhatsApp',
            'url'      => route('whatsapp.broadcast.index'),
            'gradient' => 'linear-gradient(135deg, #25D366, #128C7E)',
            'shadow'   => 'rgba(37,211,102,.25)',
            'light'    => '#F0FDF4',
        ];
    } else {
        $cards[] = [
            'icon'     => 'fa-list-check',
            'label'    => 'Minhas Tarefas',
            'desc'     => 'Acompanhe suas atividades',
            'url'      => url('/tasks'),
            'gradient' => 'linear-gradient(135deg, #0284C7, #0369A1)',
            'shadow'   => 'rgba(2,132,199,.25)',
            'light'    => '#F0F9FF',
        ];
    }

    // Card 4 — varia por papel
    if ($role === 'ngo') {
        $cards[] = [
            'icon'     => 'fa-file-contract',
            'label'    => 'Editais & Captação',
            'desc'     => 'Gerencie propostas e editais',
            'url'      => url('/ngo/grants'),
            'gradient' => 'linear-gradient(135deg, #F59E0B, #D97706)',
            'shadow'   => 'rgba(245,158,11,.25)',
            'light'    => '#FFFBEB',
        ];
    } elseif ($role === 'manager') {
        $cards[] = [
            'icon'     => 'fa-diagram-project',
            'label'    => 'Projetos',
            'desc'     => 'Visão geral dos seus projetos',
            'url'      => url('/projects'),
            'gradient' => 'linear-gradient(135deg, #4F46E5, #4338CA)',
            'shadow'   => 'rgba(79,70,229,.25)',
            'light'    => '#EEF2FF',
        ];
    } else {
        $cards[] = [
            'icon'     => 'fa-map-location-dot',
            'label'    => 'Inteligência Territorial',
            'desc'     => 'Dados IBGE para projetos sociais',
            'url'      => route('intelligence.territorial'),
            'gradient' => 'linear-gradient(135deg, #0D9488, #0F766E)',
            'shadow'   => 'rgba(13,148,136,.25)',
            'light'    => '#F0FDFA',
        ];
    }
@endphp

<style>
.qa-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
@media (max-width: 992px) { .qa-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .qa-grid { grid-template-columns: 1fr; } }

.qa-card {
    background: #fff;
    border: 1px solid #EAECF0;
    border-radius: 16px;
    padding: 22px 20px 20px;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
    overflow: hidden;
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s ease;
    box-shadow: 0 1px 3px rgba(16,24,40,.05);
}
.qa-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--qa-light);
    opacity: 0;
    transition: opacity .2s;
}
.qa-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 32px var(--qa-shadow);
    border-color: transparent;
    text-decoration: none;
}
.qa-card:hover::before { opacity: 1; }

.qa-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    color: #fff;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    box-shadow: 0 6px 16px var(--qa-shadow);
}
.qa-text { position: relative; z-index: 1; }
.qa-title {
    font-size: .88rem;
    font-weight: 800;
    color: #101828;
    letter-spacing: -.2px;
    margin: 0 0 3px;
    line-height: 1.2;
}
.qa-desc {
    font-size: .72rem;
    color: #667085;
    margin: 0;
    line-height: 1.4;
}
.qa-arrow {
    position: absolute;
    bottom: 16px;
    right: 18px;
    font-size: .7rem;
    color: #D0D5DD;
    transition: color .2s, transform .2s;
    z-index: 1;
}
.qa-card:hover .qa-arrow {
    color: #98A2B3;
    transform: translate(2px, -2px);
}
.qa-card-featured {
    border-color: rgba(249,115,22,.35);
    background: linear-gradient(160deg, #fff 60%, #fff7ed 100%);
    box-shadow: 0 4px 20px rgba(249,115,22,.12), 0 1px 3px rgba(16,24,40,.05);
}
.qa-card-featured::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #F97316, #EA580C);
    border-radius: 16px 16px 0 0;
}
.qa-card-featured:hover {
    box-shadow: 0 16px 40px rgba(249,115,22,.22);
}
</style>

<div class="qa-grid">
    @foreach($cards as $card)
    <a href="{{ $card['url'] }}"
       class="qa-card {{ !empty($card['featured']) ? 'qa-card-featured' : '' }}"
       style="--qa-shadow: {{ $card['shadow'] }}; --qa-light: {{ $card['light'] }};">
        <div class="qa-icon" style="background: {{ $card['gradient'] }};">
            <i class="fas {{ $card['icon'] }}"></i>
        </div>
        <div class="qa-text">
            <p class="qa-title">{{ $card['label'] }}</p>
            <p class="qa-desc">{{ $card['desc'] }}</p>
        </div>
        <i class="fas fa-arrow-up-right qa-arrow"></i>
    </a>
    @endforeach
</div>
